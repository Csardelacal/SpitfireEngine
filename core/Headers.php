<?php namespace spitfire\core;

use BadMethodCallException;
use spitfire\core\http\CORS;

/**
 * The headers file allows an application to manipulate the headers of the response
 * before sending them to the client.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class Headers
{
	
	private $status = 200;
	
	private $headers = array(
		'Content-type' => ['text/html;charset=utf-8'],
		'x-Powered-By' => ['Spitfire'],
		'x-version'    => ['0.1 Beta']
	);
	
	private $states = array(
		 200 => '200 OK',
		 201 => '201 Created',
		 202 => '202 Accepted',
		 204 => '204 No content',
		 206 => '206 Partial Content',
		 301 => '301 Moved Permanently',
		 302 => '302 Found',
		 304 => '304 Not modified',
		 400 => '400 Invalid request',
		 401 => '401 Unauthorized',
		 403 => '403 Forbidden',
		 404 => '404 Not Found',
		 418 => '418 Im a teapot',
		 419 => '419 Page expired',
		 429 => '429 Too many requests',
		 451 => '451 Unavailable for legal reasons',
		 410 => '410 Gone',
		 416 => '416 Range not satisfiable',
		 500 => '500 Server Error',
		 501 => '501 Not implemented',
		 503 => '503 Service Unavailable'
	);
	
	public function set($header, $value)
	{
		$this->headers[$header] = (array)$value;
		return $this;
	}
	
	/**
	 * Allows the application to remove a header from the current response. While
	 * this is usually not the case (applications shouldn't be fighting internally)
	 * it might be the case that your application needs to unset a header that 
	 * was set earlier.
	 * 
	 * @param type $header
	 * @return $this
	 */
	public function unset($header)
	{
		if (array_key_exists($header, $this->headers)) {
			unset($this->headers[$header]); 
		}
		return $this;
	}
	
	/**
	 * 
	 * @return string[]
	 */
	public function get($header) : array
	{
		return $this->headers[$header]?? [];
	}
	
	public function all()
	{
		return $this->headers;
	}
	
	/**
	 * Send the headers to the client. Once the headers have been sent, the application
	 * can no longer manipulate the headers. This method must be called before any
	 * output is sent to the browser.
	 * 
	 * Usually Spitfire will buffer all the output, so this should usually not be
	 * an issue.
	 */
	public function send()
	{
		http_response_code((int)$this->status);
		
		foreach ($this->headers as $header => $value) {
			header("$header: $value");
		}
	}
	
	/**
	 * This method allows the application to define the content type it wishes to
	 * send with the response. It prevents the user from having to define the 
	 * headers manually and automatically sets an adequate charset depending on
	 * the app's settings.
	 * 
	 * @param string $str
	 * @param string|null $encoding
	 */
	public function contentType($str, string $encoding = null)
	{
		
		if ($encoding === null) {
			$encoding = config('app.http.encoding', 'utf-8');
		}
		
		switch ($str) {
			case 'php':
			case 'html':
				$this->set('Content-type', 'text/html;charset=' . $encoding);
				break;
			case 'xml':
				$this->set('Content-type', 'application/xml;charset=' . $encoding);
				break;
			case 'json':
				$this->set('Content-type', 'application/json;charset=' . $encoding);
				break;
			default:
				$this->set('Content-type', $str);
		}
	}
	
	/**
	 * Manipulate these Header's CORS options. This returns a CORS object that can
	 * be used to define how applications running on clients on a different origin
	 * are allowed to interact with the resources on this server.
	 * 
	 * @return CORS
	 */
	public function cors()
	{
		return new CORS($this);
	}
	
	public function status($code = 200)
	{
		#Check if the call was valid
		if (!is_numeric($code)) {
			throw new BadMethodCallException('Invalid argument. Requires a number', 1509031352); 
		}
		if (!isset($this->states[$code])) {
			throw new BadMethodCallException('Invalid status code', 1509031353); 
		}
		
		$this->status = $code;
	}
	
	public function getStatus() 
	{
		return $this->status;
	}
	
	public function getReasonPhrase() 
	{
		return $this->states[$this->status];
	}
	
	public function redirect($location, $status = 302)
	{
		$this->status($status);
		$this->set('Location', $location);
		$this->set('Expires', date("r", time()));
		$this->set('Cache-Control', 'no-cache, must-revalidate');
	}
	
	public static function fromGlobals() : Headers
	{
		
		$headers = new Headers();
		
		foreach (array_change_key_case(getallheaders(), CASE_LOWER) as $header => $content) {
			$headers->set($header, $content);
		}
		
		return $headers;
	}
}
