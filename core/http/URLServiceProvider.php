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
		$config = $this->container->get(Configuration::class);
		
		/**
		 * 
		 * @var Container
		 */
		$container = $this->container->get(Container::class);
		
		$container->set(
			URLBuilder::class, 
			$container->assemble(URLBuilder::class, [
				'routes' => $container->get(Router::class)->getRoutes(),
				'root'   => SpitFire::baseUrl(),
				'assets' => $config->get('app.assets.location', SpitFire::baseUrl() . '/assets')
			])
		);
	}
	
	public function init(ContainerInterface $container)
	{
	}
}
