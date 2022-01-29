<?php namespace spitfire\core;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use spitfire\io\stream\StreamSourceInterface;
use spitfire\storage\objectStorage\FileInterface;

/**
 * Any HTTP response is built off a set of headers and a body that contains the
 * message being delivered. This class represents an HTTP response that can be
 * sent by the application.
 * 
 * I'd like to include the option to have in App return states. This way, if the
 * app provides a return URL for success or error the Response could automatically
 * handle that and send the user to the preferred location.
 * 
 * For this to work the application would have to define a onXXXXX GET parameter
 * that would be read. If the return state of the application is "success" the
 * Response should look for an "onsuccess" GET parameter and send the user to
 * that endpoint.
 */
class Response implements ResponseInterface
{
	
	/**
	 * The headers this response should be sent with. This includes anything from
	 * the status code, to redirections and even debugging messages.
	 * 
	 * [Notice] You should not include debugging messages into your headers in
	 * production environments.
	 *
	 * @var Headers
	 */
	private $headers;
	
	/**
	 * Contains the Body of the response. Usually HTML. You can put any kind of 
	 * data in the response body as long as it can be encoded properly with the
	 * defined encoding.
	 *
	 * @var StreamInterface
	 */
	private $body;
	
	/**
	 * Instantiates a new Response element. This element allows you application to
	 * generate several potential responses to a certain request and then pick the
	 * one it desires to use.
	 * 
	 * It also provides the App with the ability to discard a certain response 
	 * before it was sent and generate a completely new one.
	 * 
	 * @param StreamInterface $body
	 * @param int             $status
	 * @param mixed           $headers
	 */
	public function __construct(StreamInterface $body, $status = 200, $headers = null)
	{
		$this->body = $body;
		$this->headers = new Headers();
		$this->headers->status($status);
		
		if ($headers) {
			foreach ($headers as $header => $content) {
				$this->headers->set($header, $content);
			}
		}
	}
	
	/**
	 * We currently do not allow the user to override the protocol version of the response
	 * object to ensure that the behavior of our application is correct.
	 * 
	 * The caller will receive this instance back.
	 * 
	 * @return Response
	 */
	public function withProtocolVersion($version): Response 
	{
		/**
		 * We currently only support answering with the same method that the webserver
		 * enforces.
		 */
		return $this;
	}
	
	/**
	 * Return the protocol for the HTTP communication. For our response, we just assume
	 * it's going to be the one our webserver is using.
	 * 
	 * @return string
	 */
	public function getProtocolVersion(): string 
	{
		return $_SERVER['SERVER_PROTOCOL'];
	}
	
	/**
	 * 
	 */
	public function withStatus($code, $reasonPhrase = ''): Response 
	{
		return new Response($this->body, $code, $this->headers->all());
	}
	
	/**
	 * The integer representation of the status code.
	 * 
	 * @return int
	 */
	public function getStatusCode(): int 
	{
		return $this->headers->getStatus();
	}
	
	/**
	 * 
	 * 
	 * @return string
	 */
	public function getReasonPhrase(): string 
	{
		return $this->headers->getReasonPhrase();
	}
	
	/**
	 * Returns the headers object. This allows to manipulate the answer 
	 * headers for the current request.
	 * 
	 * @return Headers
	 */
	public function headers() 
	{
		return $this->headers;
	}
	
	/**
	 * 
	 * @return string[][]
	 */
	public function getHeaders() 
	{
		return $this->headers->all();
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
	 * Changes the headers object. This allows your application to quickly change
	 * all headers and replace everything the way you want it.
	 * 
	 * @param Headers $headers
	 * @return Response
	 */
	public function setHeaders(Headers $headers)
	{
		$this->headers = $headers;
		return $this;
	}
	
	/**
	 * Returns a copy of the response, with the header that the user has added.
	 * 
	 * @param string $name
	 * @param string|string[] $value
	 * @return Response
	 */
	public function withHeader($name, $value): Response 
	{
		$headers = $this->headers->all();
		$headers[$name] = (array)$value;
		
		return new Response($this->body, $this->headers->getStatus(), $headers);
	}
	
	/**
	 * Returns a copy of the response, but with additional information on a header,
	 * which allows the user to push data onto the header.
	 * 
	 * @param string $name
	 * @param string|string[] $value
	 * @return Response
	 */
	public function withAddedHeader($name, $value): Response 
	{
		$headers = $this->headers->all();
		$headers[$name] = array_merge($headers[$name], (array)$value);
		
		return new Response($this->body, $this->headers->getStatus(), $headers);
	}
	
	/**
	 * Returns a copy of the response, without the given header.
	 * 
	 * @param string $name
	 * @return Response
	 */
	public function withoutHeader($name): Response 
	{
		$headers = $this->headers->all();
		unset($headers[$name]);
		
		return new Response($this->body, $this->headers->getStatus(), $headers);
	}
	
	/**
	 * Creates a new response with the provided body. Please note that the response is immutable,
	 * which means that a copy of the object is created when setting a new body.
	 * 
	 * However, the body itself is not immutable, which means that often it is possible to modify
	 * the body while the response is immutable. This can lead to performance improvements when
	 * working with the body, but also lead to issues with stability. You should avoid creating a
	 * response prematurely, and wait until the body is complete.
	 * 
	 * @param StreamInterface $body
	 * @return Response
	 */
	public function withBody(StreamInterface $body): Response
	{
		return new Response($body, $this->getStatusCode(), $this->getHeaders());
	}
}
