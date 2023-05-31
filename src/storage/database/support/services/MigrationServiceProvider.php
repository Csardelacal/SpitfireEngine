<?php namespace spitfire\storage\database\support\services;

use Psr\Container\ContainerInterface;
use spitfire\contracts\ConfigurationInterface;
use spitfire\contracts\core\kernel\ConsoleKernelInterface;
use spitfire\contracts\core\kernel\KernelInterface;
use spitfire\contracts\core\LocationsInterface;
use spitfire\contracts\services\ProviderInterface;
use spitfire\storage\database\Connection;
use spitfire\storage\database\ConnectionInterface;
use spitfire\storage\database\support\commands\MigrateCommand;

class MigrationServiceProvider implements ProviderInterface
{
	
	/**
	 *
	 * @var ConfigurationInterface
	 */
	private $config;
	
	/**
	 *
	 * @var LocationsInterface
	 */
	private $locations;
	
	public function __construct(ConfigurationInterface $config, LocationsInterface $locations)
	{
		$this->config = $config;
		$this->locations = $locations;
	}
	
	/**
	 *
	 * @return void
	 */
	public function register(ContainerInterface $container) : void
	{
	}
	
	/**
	 *
	 * @return void
	 */
	public function init(ContainerInterface $container) : void
	{
		
		if (!$container->has(ConnectionInterface::class)) {
			return;
		}
		
		/**
		 * Please note that the order in which the migrations appear in the
		 * manifest file is relevant to the order in which they are applied
		 * and rolled back.
		 *
		 * Also, note that for security reasons, the system will disable
		 * executing migrations from the web interface.
		 */
		if ($container->get(KernelInterface::class) instanceof ConsoleKernelInterface) {
			
			/**
			 * Prepare the necessary components to prepare a command for running
			 * migrations.
			 */
			$connection = $container->get(ConnectionInterface::class);
			
			
			$migrationFile  = $this->config->get('app.database.migrations.file', 'bin/migrations.php');
			$schemaBaseline = $this->config->get('app.database.migrations.baseline', 'app/migrations/_schema.php');
			$schemaFile     = $this->config->get('app.database.schema', 'bin/schema.php');
			
			/**
			 * Register the available migration commands.
			 */
			$container->get(ConsoleKernelInterface::class)->register(
				new MigrateCommand(
					$connection,
					$this->locations->root($migrationFile),
					$this->locations->root($schemaBaseline),
					$this->locations->root($schemaFile)
				)
			);
		}
	}
}
