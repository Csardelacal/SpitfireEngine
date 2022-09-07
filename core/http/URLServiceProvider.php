<?php namespace spitfire\core\http;

use Psr\Container\ContainerInterface;
use spitfire\core\config\Configuration;
use spitfire\core\router\Router;
use spitfire\core\service\Provider;
use spitfire\provider\Container;
use spitfire\SpitFire;

/**
 * Initializes the URL builder appropriately.
 */
class URLServiceProvider extends Provider
{
	
	public function register(ContainerInterface $container)
	{
		
	}
	
	/**
	 * @todo fixme: The fact that we do not create the URL Builder as a singleton, does
	 * lead to potential overhead building the object over and over whenever we need a 
	 * URL.
	 */
	public function init(ContainerInterface $container)
	{
		$config = $container->get(Configuration::class);
		
		/**
		 *
		 * @var Container
		 */
		$container = $container->get(Container::class);
		
		$container
			->service(URLBuilder::class)
			->with('root', SpitFire::baseUrl())
			->with('routes', $container->get(Router::class)->getRoutes())
			->with('assets', $config->get('app.assets.location', SpitFire::baseUrl() . '/assets'));
	}
}
