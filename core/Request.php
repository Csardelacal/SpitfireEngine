<?php namespace spitfire\core;

use magic3w\http\url\reflection\URLReflection;
use spitfire\io\Get;
use spitfire\exceptions\ApplicationException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\UploadedFileInterface;
use spitfire\io\stream\Stream;
use spitfire\io\UploadFile;

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
 */
class Request implements ServerRequestInterface
{
	
	/**
	 * The version of the HTTP protocol being used for this request.
	 *
	 * @var string
	 */
	private $version = '1.1';
	
	/**
	 * Contains data that can be retrieved out of the Path string of the URL. For
	 * example, the controller / action / object of the request or data about
	 * custom route parameters.
	 *
	 * @var UriInterface
	 */
	private $uri;
	
	/**
	 * The server parameter array contains all the runtime information the server
	 * has about itself and the current request. Usually this data is directly
	 * originated from $_SERVER
	 *
	 * @var string[]
	 */
	private $serverParams;
	
	/**
	 * Contains information what information was delivered via query parameters of
	 * the URL. This will be a special _GET object that will aid generating canonicals.
	 * If you're writing an application/component that cannot guarantee having the
	 * <i>request.replace_globals</i> enabled you should use this rather than $_GET
	 *
	 * @var \spitfire\io\Get
	 */
	private $get;
	
	/**
	 * The array tree of uploads, this is a mix of UploadInterfaces and arrays. The
	 * application can the use the array to manipulate the uploaded file.
	 *
	 * @see https://www.php-fig.org/psr/psr-7/meta/
	 * @var (array|UploadedFileInterface)[]
	 */
	private $uploads;
	
	/**
	 * This contains a mixed version of _POST and _FILES that allows your app to
	 * conveniently use the data generated by this two sources in a single place.
	 *
	 * @var mixed
	 */
	private $post;
	
	/**
	 *
	 * @var StreamInterface
	 */
	private $body;
	
	/**
	 *
	 * @var mixed
	 */
	private $attributes;
	
	/**
	 * Allows your app to maintain a copy of the COOKIE variable. This is especially
	 * useful when writing tests considering different requests as you will easily
	 * be able to swap the values.
	 *
	 * @var mixed
	 */
	private $cookie;
	
	/**
	 * This object allows your app to conveniently access the HTTP headers. These
	 * will contain information like DNT or User agent that can be relevant to your
	 * application and alter the experience the user receives.
	 *
	 * @var Headers
	 */
	private $headers;
	
	/**
	 * The HTTP verb that is used to indicate what the server should do with the data
	 * it receives from the client application. This allows us to specifically route
	 * some things in the application.
	 *
	 * @var string
	 */
	private $method;
	
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
	 * Returns the version of the HTTP protocol used to send this request. For PHP most of this
	 * is transparent when handling a request, so it's possible that this data is actually not
	 * properly poulated.
	 *
	 * @return string
	 */
	public function getProtocolVersion() : string
	{
		return $this->version;
	}
	
	/**
	 * Returns the name of the method used to request from this server. Currently we focus
	 * on supporting GET, POST, PUT, DELETE and OPTIONS
	 *
	 * @return string
	 */
	public function getMethod()
	{
		return $this->method;
	}
	
	/**
	 * The server params function returns the equivalent of the _SERVER
	 * superglobal. We generally set the content of the variable to _SERVER
	 * but you may come across implementations that override it completely
	 * or partially.
	 *
	 * @return string[]
	 */
	public function getServerParams()
	{
		return $this->serverParams;
	}
	
	/**
	 * Returns a tree of uploaded files. The tree contains arrays mixed with UploadedFileInterface
	 * objects that contain the actual metadata.
	 *
	 * @see https://www.php-fig.org/psr/psr-7/meta/
	 * @return (array|UploadedFileInterface)[]
	 */
	public function getUploadedFiles()
	{
		return $this->uploads;
	}
	
	/**
	 * Returns the request target. I am still not 100% certain what the request target is, it seems
	 * to be some kind of fallback that I am not certain how it works (apparently you can request
	 * wildcard data from some servers).
	 *
	 * @return string
	 */
	public function getRequestTarget() : string
	{
		if ($this->uri === null) {
			return '/';
		}
		
		$query = $this->uri->getQuery();
		return $this->uri->getPath() . ($query? '?' . $query: '');
	}
	
	/**
	 * Returns the URI used to request a resource from the server.
	 *
	 * @return UriInterface
	 */
	public function getUri() : UriInterface
	{
		return $this->uri;
	}
	
	/**
	 * In our service this is not implemented. Calling it has no effect
	 */
	public function withRequestTarget($requestTarget) : Request
	{
		return $this;
	}
	
	/**
	 * Returns the array of query parameters (_GET) that the request was sent along
	 * with.
	 *
	 * @return mixed[]
	 */
	public function getQueryParams()
	{
		if ($this->get === null) {
			parse_str($this->uri->getQuery(), $get);
			$this->get = new Get($get);
		}
		
		return $this->get->getRaw();
	}
	
	/**
	 *
	 * @return mixed[]
	 */
	public function getParsedBody()
	{
		return $this->post;
	}
	
	/**
	 *
	 * @return mixed[]
	 */
	public function getAttributes()
	{
		return $this->attributes;
	}
	
	public function getAttribute($name, $default = null)
	{
		return array_key_exists($name, $this->attributes)? $this->attributes[$name] : $default;
	}
	
	/**
	 *
	 * @return string[]
	 */
	public function getCookieParams()
	{
		return $this->cookie;
	}
	
	/**
	 * Returns the content that is to be sent with the body. This is a string your
	 * application has to set beforehand.
	 *
	 * In case you're using Spitfire's Context object to manage the context the
	 * response will get the view it contains and render that before returning it.
	 *
	 * @return StreamInterface
	 */
	public function getBody() : StreamInterface
	{
		return $this->body;
	}
	
	public function withBody(StreamInterface $body)
	{
		$copy = clone $this;
		$copy->body = $body;
		return $copy;
	}
	
	public function getHeaders()
	{
		return $this->headers->all();
	}
	
	/**
	 * Override the request URI for the server.
	 *
	 * @return ServerRequestInterface
	 */
	public function withUri(UriInterface $uri, $preserveHost = false)
	{
		if ($preserveHost) {
			if (!$this->hasHeader('Host') && $uri->getHost()) {
				$copy = $this->withHeader('Host', $uri->getHost());
			}
			else {
				$copy = clone $this;
			}
		}
		else {
			$copy = $this->withHeader('Host', $uri->getHost());
		}
		
		
		$copy->uri = $uri;
		
		return $copy;
	}
	
	public function withMethod($method)
	{
		$copy = clone $this;
		$copy->method = $method;
		
		return $copy;
	}
	
	public function withProtocolVersion($version)
	{
		$copy = clone $this;
		$copy->version = $version;
		
		return $copy;
	}
	
	/**
	 *
	 * @param mixed[] $query
	 * @return Request
	 */
	public function withQueryParams(array $query) : Request
	{
		$copy = clone $this;
		$copy->get = new Get($query);
		
		return $copy;
	}
	
	/**
	 *
	 * @param null|mixed[] $data
	 */
	public function withParsedBody($data) : Request
	{
		$copy = clone $this;
		$copy->post = $data;
		return $copy;
	}
	
	/**
	 * Override a single attribute that the server received. Please note that
	 * this method does not support nested access, so you cannot override a
	 * key in a nested attribute.
	 *
	 * @return ServerRequestInterface
	 */
	public function withAttribute($name, $value)
	{
		$copy = clone $this;
		$copy->attributes[$name] = $value;
		return $copy;
	}
	
	
	/**
	 *
	 * @param string $name
	 * @return bool
	 */
	public function hasHeader($name): bool
	{
		return !empty($this->headers->get($name));
	}
	
	/**
	 *
	 * @param string $name
	 * @return string[]
	 */
	public function getHeader($name)
	{
		return $this->headers->get($name);
	}
	
	/**
	 * The header line represents the data that is being sent to the browser, some headers
	 * allow sending multiple values, separated by commas, which the client cannot receive
	 * unless we convert our arrays in comma separated strings.
	 *
	 * @param string $name
	 * @return string
	 */
	public function getHeaderLine($name): string
	{
		$header = $this->headers->get($name);
		
		if (empty($header)) {
			return implode(',', $header);
		}
		else {
			return '';
		}
	}
	
	/**
	 * Returns a copy of the response, with the header that the user has added.
	 *
	 * @param string $name
	 * @param string|string[] $value
	 * @return Request
	 */
	public function withHeader($name, $value): Request
	{
		$headers = $this->headers->all();
		$headers[$name] = (array)$value;
		
		$copy = clone $this;
		$copy->headers = $headers;
		return $copy;
	}
	
	/**
	 * Returns a copy of the response, but with additional information on a header,
	 * which allows the user to push data onto the header.
	 *
	 * @param string $name
	 * @param string|string[] $value
	 * @return Request
	 */
	public function withAddedHeader($name, $value): Request
	{
		$headers = $this->headers->all();
		$headers[$name] = array_merge($headers[$name], (array)$value);
		
		$copy = clone $this;
		$copy->headers = $headers;
		return $copy;
	}
	
	/**
	 * Returns a copy of the response, without the given header.
	 *
	 * @param string $name
	 * @return Request
	 */
	public function withoutHeader($name): Request
	{
		$headers = $this->headers->all();
		unset($headers[$name]);
		
		$copy = clone $this;
		$copy->headers = $headers;
		return $copy;
	}
	
	/**
	 * Removes the attribute from the request.
	 *
	 * @return Request
	 */
	public function withoutAttribute($name)
	{
		$copy = clone $this;
		unset($copy->attributes[$name]);
		return $copy;
	}
	
	/**
	 *
	 * @param string[] $cookies
	 */
	public function withCookieParams(array $cookies)
	{
		$copy = clone $this;
		$copy->cookie = $cookies;
		return $copy;
	}
	
	/**
	 *
	 * @param (array|UploadedFileInterface)[] $uploadedFiles
	 */
	public function withUploadedFiles(array $uploadedFiles)
	{
		$copy = clone $this;
		$copy->uploads = $uploadedFiles;
		return $copy;
	}
	
	/**
	 * Whether this request was posted.
	 *
	 * @return bool
	 */
	public function isPost() : bool
	{
		return $this->method === 'POST';
	}
	
	/**
	 * If the request contains range information (the client only wishes to retrieve a subset of the
	 * resource), this endpoint will return true.
	 *
	 * @return bool
	 */
	public function isRange() : bool
	{
		return !empty($this->headers->get('Range'));
	}
	
	/**
	 * For ranged requests, this function provides a convenience access to the range data.
	 * This method only supports ranges in Bytes
	 *
	 * @return array{int,int|null}
	 */
	public function getRange() : array
	{
		$sent = $this->headers->get('Range')[0];
		
		if (!\spitfire\utils\Strings::startsWith($sent, 'bytes=')) {
			throw new ApplicationException('Malformed range sent', 416);
		}
		
		if (strstr($sent, ',')) {
			throw new ApplicationException('Spitfire does not accept multiple ranges', 416);
		}
		
		$pieces = explode('-', substr($_SERVER['HTTP_RANGE'], 6));
		
		return [
			(int)array_shift($pieces),
			(int)array_shift($pieces)?: null
		];
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
		 * Extract the request method from the server. Please note that we allow
		 * applications to override the request method by sending a payload to the
		 * webserver. This fixes a few behavioral issues that we run into when working
		 * with PHP and REST APIs
		 *
		 * For example, PUT in HTTP and PHP is not intended to parse the request body
		 * like POST would. But in REST, PUT is basically a POST that overwrites data
		 * if it already existed.
		 *
		 * For consistency, this method emulates the behavior of Laravel's mechanism
		 * really closely.
		 */
		$method = $_method = strtoupper($_SERVER['REQUEST_METHOD']?? 'GET');
		
		if (isset($_POST['_method']) && in_array(strtoupper($_POST['method']), ['GET', 'PUT', 'POST', 'PATCH', 'DELETE'])) {
			$method = $_POST['_method'];
		}
		
		/**
		 * The headers have their own little helper function that calls the apache_get_all_headers
		 * function. Counter-intuitively, this function also works in CLI, FastCGI and NginX
		 */
		$headers = Headers::fromGlobals();
		
		/**
		 * The URI is assembled by the URLReflection class.
		 */
		$uri = URLReflection::fromGlobals();
		
		$uploads = UploadFile::fromGlobals();
		
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
		 * Pay close attention to the _ in the method variable name here. This implies that the request
		 * gets parsed if the CLIENT sent a POST request, regardless of how the request was overridden,
		 * it also does this when the method is set to something manually that expects a parsed body.
		 */
		if ($_method === 'POST' || $method === 'PUT') {
			if (in_array($contentType, ['multipart/formdata', 'application/x-www-form-urlencoded'])) {
				$request->withParsedBody($_POST);
			}
			elseif ($contentType === 'application/json') {
				$request->withParsedBody(json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR));
			}
		}
		
		return $request;
	}
}
