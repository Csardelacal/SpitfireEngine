<?php namespace spitfire\core;

use spitfire\io\Get;
use spitfire\core\router\Router;

/**
 * The request class is a component that allows developers to retrieve information
 * about data that usually is delivered by the webserver. For example, get and 
 * post data are usually stored by this object in order to allow your app to 
 * use it.
 */
class Request
{
	/**
	 * Contains data that can be retrieved out of the Path string of the URL. For
	 * example, the controller / action / object of the request or data about
	 * custom route parameters.
	 * 
	 * @var Path 
	 */
	private $path;
	
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
	 * This contains a mixed version of _POST and _FILES that allows your app to 
	 * conveniently use the data generated by this two sources in a single place.
	 *
	 * @var mixed
	 */
	private $post;
	
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
	 * Your app may use this to alter the response being sent to the user at the 
	 * end of execution. Please notice that this object is normally ignored when
	 * using the CLI.
	 *
	 * @var Response 
	 */
	private $response;

	/** @var string "GET" or "POST" */
	private $method;

	/** @var bool */
	private $https;
	
	/**
	 * The context this request is being handled in. The context allows Spitfire 
	 * to virtually run 'several instances' of a Request in a single run. This is
	 * usually especially interesting to people who are testing a Spitfire app.
	 * 
	 * @var Context
	 */
	private $context;
	
	/**
	 * Usually, the Request will be a singleton. The most convenient way of using
	 * it is accessing via Request::get() or request() as they will provide a 
	 * consistent way to do so.
	 * 
	 * @var Request 
	 */
	static  $instance;
	
	/**
	 * Creates a new Request. This object 'simulates' a link between the user and
	 * the Application. It allows you to retrieve the data the user has sent with
	 * the Request and therefore adapt the behavior of your application accordingly.
	 * 
	 * [Notice] This function does NOT validate the data it receives and assumes
	 * it is correct. This is due to this code being executed every single time
	 * your app is invoked and therefore being required to be lightweight.
	 * 
	 * @param \spitfire\core\Path $path
	 * @param \spitfire\io\Get    $get
	 * @param mixed               $post
	 * @param mixed               $cookie
	 * @param Headers             $headers
	 * @param string              $method
	 * @param bool                $https
	 */
	public function __construct(Path$path, Get$get, $post, $cookie, $headers, $method, $https) {
		#Import the data generated by external systems.
		$this->path     = $path;
		$this->get      = $get;
		$this->post     = $post;
		$this->cookie   = $cookie;
		$this->headers  = $headers;
		$this->method   = $method;
		$this->https    = $https;
		
		#Create the response object
		$this->response = new Response(null);
		#Set this as the current instance.
		self::$instance = $this;
	}
	
	/**
	 * Returns the response object this Request is using. You can use it to set
	 * custom response bodies or headers within your application. This is dropped
	 * in case the context is rebuilt.
	 * 
	 * @return Response
	 */
	public function getResponse() {
		return $this->response;
	}
	
	/**
	 * Automatically creates a context from the available data. This allows Spitfire
	 * to create a single interface that your app can use to access all it's 
	 * components in a convenient way.
	 * 
	 * @return type
	 */
	public function makeContext() {
		$this->context = Context::create();
		return $this->context;
	}
	
	public function getPath() {
		return $this->path;
	}
	
	public function setPath($path) {
		$this->path = $path;
	}
	
	public function getGet() {
		return $this->get;
	}

	public function getPost() {
		return $this->post;
	}

	public function getCookie() {
		return $this->cookie;
	}

	public function getHeaders() {
		return $this->headers;
	}

	public function getContext() {
		return $this->context;
	}

	public function setGet(\spitfire\io\Get $get) {
		$this->get = $get;
		return $this;
	}

	public function setPost($post) {
		$this->post = $post;
		return $this;
	}
	
	public function isPost() {
		return $this->method === 'POST';
	}
	
	public function isRange() {
		return isset($_SERVER['HTTP_RANGE']);
	}
	
	public function getRange() {
		$sent = $_SERVER['HTTP_RANGE'];
		
		if (!\spitfire\utils\Strings::startsWith($sent, 'bytes=')) {
			throw new \spitfire\exceptions\PublicException('Malformed range sent', 416);
		}
		
		if (strstr($sent, ',')) {
			throw new \spitfire\exceptions\PublicException('Spitfire does not accept multiple ranges', 416);
		}
		
		$pieces = explode('-', substr($_SERVER['HTTP_RANGE'], 6));
		
		return [
			array_shift($pieces),
			array_shift($pieces)?: null
		];
	}

	public function setCookie($cookie) {
		$this->cookie = $cookie;
		return $this;
	}

	public function setHeaders(request\Headers $headers) {
		$this->headers = $headers;
		return $this;
	}

	public function setContext(Context $context) {
		$this->context = $context;
		return $this;
	}
	
	/**
	 * This method allows to push the current request back to the server and 
	 * therefore allowing your application to alter the request the app perceives
	 * at runtime.
	 * 
	 * Please note that the recommended usage of this tool is during testing and
	 * bootstraping. You're probably making poor choices if your code uses this function.
	 * 
	 * @param \spitfire\core\Request $request
	 * @return \spitfire\core\Request
	 */
	public static function toServer(Request$request) {
		$_GET    = $request->getGet();
		$_POST   = $request->getPost();
		$_COOKIE = $request->getCookie();
		$_SERVER = $request->getHeaders();
		
		self::$instance = $request;
		
		return $request;
	}
	
	public static function fromServer() {
		$get     = $_GET    = $_GET instanceof Get ? clone $_GET : new Get($_GET);
		$post    = $_POST   = array_replace_recursive(\spitfire\io\Post::init(), \spitfire\io\Upload::init());
		$cookie  = $_COOKIE;
		$headers = $_SERVER;
		
		$https   = isset($_SERVER['HTTPS'])? 'on' : '';
		$path    = Router::getInstance()->rewrite($_SERVER['HTTP_HOST'], getPathInfo(), $_SERVER['REQUEST_METHOD'], $https);
		
		if ($path instanceof Path) { return new Request($path, $get, $post, $cookie, $headers, $_SERVER['REQUEST_METHOD'], $https); }
		else                       { return $path; }
	}

	/**
	 * This function allows to use a singleton pattern on the request. We don't 
	 * need more than one request at a time, so we can use this function to avoid
	 * storing it in several places.
	 * 
	 * @return Request
	 */
	public static function get() {
		if (self::$instance) { return self::$instance; }
		else { return self::$instance = self::fromServer(); }
	}
}
