<?php namespace spitfire\core;

use magic3w\http\url\reflection\URLReflection;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\UploadedFileInterface;
use spitfire\core\http\request\components as components;
use spitfire\io\stream\Stream;

/*
 * Copyright (C) 2021 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301  USA
 */
/**
 * The request class is a component that allows developers to retrieve information
 * about data that usually is delivered by the webserver. For example, get and
 * post data are usually stored by this object in order to allow your app to
 * use it.
 * 
 * Please note that the request is an amalgamation of different components.
 */
class Request implements ServerRequestInterface
{
	use
	components\hasProtocolVersion,
	components\hasHTTPMethod,
	components\hasURI,
	components\hasServerParams,
	components\hasUploads,
	components\hasBody,
	components\hasParsedBody,
	components\hasAttributes,
	components\hasCookies,
	components\hasHeaders;
	
	/**
	 * Creates a new Request. This object 'simulates' a link between the user and
	 * the Application. It allows you to retrieve the data the user has sent with
	 * the Request and therefore adapt the behavior of your application accordingly.
	 *
	 * [Notice] This function does NOT validate the data it receives and assumes
	 * it is correct. This is due to this code being executed every single time
	 * your app is invoked and therefore being required to be lightweight.
	 *
	 * @param string                  $method
	 * @param UriInterface            $uri
	 * @param Headers                 $headers
	 * @param string[]                $cookies
	 * @param string[]                $serverParams
	 * @param StreamInterface         $body
	 * @param UploadedFileInterface[] $uploads
	 */
	public function __construct(
		string $method,
		UriInterface $uri,
		Headers $headers,
		$cookies,
		array $serverParams,
		StreamInterface $body,
		array $uploads
	) {
		#Import the data generated by external systems.
		$this->serverParams = $serverParams;
		$this->uri      = $uri;
		$this->body     = $body;
		$this->cookie   = $cookies;
		$this->headers  = $headers;
		$this->method   = $method;
		$this->uploads  = $uploads;
	}
	
	/**
	 * Extract a request object from the current SAPI request. This distills the
	 * context of the global request into an object that we can manipulate as we
	 * descent into the middleware and request handler stack.
	 *
	 * @return Request
	 */
	public static function fromGlobals() : Request
	{
		
		/**
		 * Extract the method from Globals. Allowing our application to respond in different manners
		 * if we so desire.
		 */
		$method = self::methodFromGlobals();
		
		/**
		 * The headers have their own little helper function that calls the apache_get_all_headers
		 * function. Counter-intuitively, this function also works in CLI, FastCGI and NginX
		 */
		$headers = Headers::fromGlobals();
		
		/**
		 * The URI is assembled by the URLReflection class.
		 */
		$uri = URLReflection::fromGlobals();
		
		$uploads = self::filesFromGlobals();
		
		/**
		 * The PSR strongly suggests that both the cookie and server variable should be directly
		 * read from the webserver.
		 */
		$cookies = $_COOKIE;
		$serverParams = $_SERVER;
		
		/**
		 * Open the body as a stream. In get requests this stream will contain nothing (obviously),
		 * but all other requests will be able to read data from this.
		 *
		 * Please note that this doesn't apply to simulated get requests, where the client requested
		 * the data to be treated as POST.
		 */
		$body = new Stream(fopen('php://input', 'r'), true, true, false);
		
		$request = new Request($method, $uri, $headers, $cookies, $serverParams, $body, $uploads);
		
		/**
		 * We default to assuming that the content type is undefined, and then loop over the
		 * headers to attempt and find a header that overrides this.
		 */
		$contentType = null;
		
		/**
		 * Loop over the content-type headers available (spoiler, it's only one) and extract the content
		 * type. Since the content type can be followed by a semicolon to add boundary information and
		 * charset information, we just parse the very first bit containing the mime type of the payload.
		 */
		foreach ($headers->get('content-type')?: [] as $_h) {
			$contentType = explode(';', $_h)[0];
		}
		
		/**
		 * If the sender is transmitting content that we understand, use it. This implies that the request
		 * gets parsed if the CLIENT sent a POST request, regardless of how the request was overridden,
		 * it also does this when the method is set to something manually that expects a parsed body.
		 */
		if (in_array($contentType, ['multipart/formdata', 'application/x-www-form-urlencoded'])) {
			$request->withParsedBody($_POST);
		}
		elseif ($contentType === 'application/json') {
			$request->withParsedBody(json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR));
		}
		
		return $request;
	}
}
