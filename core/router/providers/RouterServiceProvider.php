<?php namespace spitfire\core\router\providers;

use Psr\Container\ContainerInterface;
use spitfire\contracts\services\ProviderInterface;
use spitfire\core\router\Router;
use spitfire\core\service\Provider;
use spitfire\provider\Container;

class RouterServiceProvider implements ProviderInterface
{
	
	public function register(ContainerInterface $container) : void
	{
	}
	
	public function init(ContainerInterface $container) : void
	{
		/**
		 *
		 * @var Container
		 */
		$container = $container->get(Container::class);
		
		$router = $container->assemble(Router::class, ['prefix' => '']);
		assert($router instanceof Router);
		$container->set(Router::class, $router);
	}
}
