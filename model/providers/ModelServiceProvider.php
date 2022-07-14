<?php namespace spitfire\model\providers;

use Psr\Container\ContainerInterface;
use spitfire\contracts\ConfigurationInterface;
use spitfire\contracts\services\ProviderInterface;
use spitfire\core\kernel\ConsoleKernel;
use spitfire\core\Locations;
use spitfire\model\directors\SchemaDiffDirector;
use spitfire\storage\database\Schema;

class ModelServiceProvider implements ProviderInterface
{
	
	public function register(ContainerInterface $container)
	{
		
	}
	
	public function init(ContainerInterface $container)
	{
		
		$kernel = $container->get(ConsoleKernel::class);
		
		/*
		 * We only need to register the directors if our kernel is actually the
		 * console kernel. We cannot work with directors on the web server.
		 */
		$locations = $container->get(Locations::class);
		
		$config  = $container->get(ConfigurationInterface::class);
		$schema  = $config->get('app.database.schema', 'bin/schema.php');
		
		$kernel->register(
			new SchemaDiffDirector(
				file_exists($locations->root($schema))? include $locations->root($schema) : new Schema(''),
				$locations->root('app/models')
			)
		);
	}
}