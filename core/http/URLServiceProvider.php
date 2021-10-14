<?php namespace spitfire\core\http;

use spitfire\core\config\Configuration;
use spitfire\core\router\Router;
use spitfire\core\service\Provider;
use spitfire\SpitFire;

/**
 * Initializes the URL builder appropriately.
 */
class URLServiceProvider extends Provider
{
	
	public function register()
	{
		$config = $this->container->get(Configuration::class);
		
		$this->container->set(
			URLBuilder::class, 
			$this->container->assemble(URLBuilder::class, [
				'routes' => $this->container->get(Router::class)->getRoutes(),
				'root'   => SpitFire::baseUrl(),
				'assets' => $config->get('app.assets.location', SpitFire::baseUrl() . '/assets')
			])
		);
	}
	
	public function init()
	{
		
	}
}
