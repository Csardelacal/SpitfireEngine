<?php namespace spitfire\core\http\request\components;

trait hasHTTPMethod
{
	
	/**
	 * The HTTP verb that is used to indicate what the server should do with the data
	 * it receives from the client application. This allows us to specifically route
	 * some things in the application.
	 *
	 * @var string
	 */
	private $method;
	
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
	
	public function withMethod($method)
	{
		$copy = clone $this;
		$copy->method = $method;
		
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
	 * Returns the request method (and the real method). This allows the user to spoof a request
	 * to overcome certain poor PHP implementations.
	 *
	 * @return string
	 */
	public static function methodFromGlobals() : string
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
		$method = strtoupper($_SERVER['REQUEST_METHOD']?? 'GET');
		
		if (isset($_POST['_method']) && in_array(strtoupper($_POST['method']), ['GET', 'PUT', 'POST', 'PATCH', 'DELETE'])) {
			$method = $_POST['_method'];
		}
		
		return $method;
	}
}
