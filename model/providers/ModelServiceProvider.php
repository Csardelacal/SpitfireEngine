<?php namespace spitfire\model\providers;

use Psr\Container\ContainerInterface;
use spitfire\contracts\ConfigurationInterface;
use spitfire\contracts\services\ProviderInterface;
use spitfire\core\kernel\ConsoleKernel;
use spitfire\core\Locations;
use spitfire\model\directors\SchemaDiffDirector;
use spitfire\model\ModelFactory;
use spitfire\provider\Container;
use spitfire\storage\database\ConnectionInterface;
use spitfire\storage\database\Schema;

class ModelServiceProvider implements ProviderInterface
{
	
	public function register(ContainerInterface $container) : void
	{
		
		/**
		 * Without a databsae connection available, this system is useless.
		 */
		if (!$container->has(ConnectionInterface::class)) {
			return;
		}
		
		/**
		 * The model factory should make it easy for us to access the database
		 * without initializing it, since we rely on the application having all
		 * the bits and pieces ready for us.
		 */
		$container->get(Container::class)->set(
			ModelFactory::class,
			new ModelFactory($container->get(ConnectionInterface::class))
		);
	}
	
	public function init(ContainerInterface $container) : void
	{
		
		if (!$container->has(ConnectionInterface::class)) {
			return;
		}
		
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
