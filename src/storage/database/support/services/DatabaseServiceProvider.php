<?php namespace spitfire\storage\database\support\services;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use spitfire\contracts\ConfigurationInterface;
use spitfire\contracts\core\LocationsInterface;
use spitfire\contracts\services\ProviderInterface;
use spitfire\exceptions\ApplicationException;
use spitfire\provider\Container;
use spitfire\storage\database\Connection;
use spitfire\storage\database\ConnectionGlobal;
use spitfire\storage\database\ConnectionInterface;
use spitfire\storage\database\ConnectionManager;

class DatabaseServiceProvider implements ProviderInterface
{
	
	/**
	 *
	 * @return void
	 */
	public function register(ContainerInterface $container) : void
	{
		$drive   = $container->get(LocationsInterface::class);
		$config  = $container->get(ConfigurationInterface::class);
		$default = $config->get('database.default');
		$schema  = $drive->root($config->get('app.database.schema', 'bin/schema.php'));
		$manager = new ConnectionManager($container, $config->splice('database.connections'), $schema);
		
		/**
		 *
		 * @var Container
		 */
		$container = $container->get(Container::class);
		
		$container->set(ConnectionManager::class, $manager);
		$container->set(ConnectionInterface::class, new ConnectionGlobal());
		$container->singleton(Connection::class, fn() => $manager->get($default));
	}
	
	/**
	 *
	 * @return void
	 */
	public function init(ContainerInterface $container) : void
	{
	}
}
