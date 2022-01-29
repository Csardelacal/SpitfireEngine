<?php namespace tests\spitfire;

use magic3w\http\url\reflection\URLReflection;
use PHPUnit\Framework\TestCase;
use spitfire\collection\Collection;
use spitfire\core\config\Configuration;
use spitfire\core\Headers;
use spitfire\core\http\URLBuilder;
use spitfire\core\Request;
use spitfire\core\router\Router;
use spitfire\io\stream\Stream;

use function spitfire;

class URLTest extends TestCase
{
	
	private $setup = false;
	private $router;
	
	/**
	 * 
	 * @var URLBuilder
	 */
	private $builder;
	
	public function setUp() : void 
	{
		
		/**
		 * Assemble a faux request that can be used to assemble the URL builder. The request
		 * we pass here is mostly meaningless, because it's only used to create URLs based on
		 * the current context like current().
		 */
		$uri = new URLReflection('http', 'localhost', 80, '/', '');
		$headers = new Headers();
		$request = new Request('GET', $uri, $headers, [], $_SERVER, new Stream(null, false, false, false));
		
		/**
		 * Assemble a collection of routes that can be used for testing. We do this by creating
		 * a small router that we can use to generate a few faux routes that then are extracted
		 * from the router into the builder.
		 */
		$router = spitfire()->provider()->assemble(Router::class, ['prefix' => '']);
		$router->request('/', ['HomeController', 'index']);
		$router->request('/me/{b}', ['AccountController', 'test']);
		$router->request('/{a:test}/{b:posts}', ['TestController', 'index']);
		$router->request('/static/{page:homepage}', ['ContentController', 'page']);
		
		$this->builder = new URLBuilder($request, $router->getRoutes());
	}
	
	public function testPrerequisiteBaseURL()
	{
		$this->assertEquals('/', $this->builder->to('/'));
	}
	
	public function testBlankSerializer()
	{
		$this->assertEquals('', $this->builder->to(''));
	}
	
	public function testBlankSerializer2()
	{
		$url = $this->builder->to(['HomeController', 'index']);
		$this->assertEquals('/', $url);
	}
	
	public function testAnotherSerializer()
	{
		$url = $this->builder->to(['AccountController', 'test'], ['b' => 'test']);
		$this->assertEquals('/me/test', $url);
	}
	
	public function testAnotherSerializerWithParams()
	{
		$url = $this->builder->to(['AccountController', 'test'], ['b' => 'test'], ['a' => 3]);
		$this->assertEquals('/me/test?a=3', $url);
	}
	
	public function testArrayReverser()
	{
		$url = $this->builder->to(['TestController', 'index'], ['a' => 'my', 'b' => 'url']);
		$this->assertEquals('/my/url', $url);
	}
}
