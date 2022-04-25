<?php namespace spitfire\storage\database\support\services;

use spitfire\core\kernel\ConsoleKernel;
use spitfire\core\service\Provider;
use spitfire\exceptions\ApplicationException;
use spitfire\storage\database\Connection;
use spitfire\storage\database\support\commands\MigrateCommand;

class MigrationServiceProvider extends Provider
{

	/**
	 * 
	 * @return void
	 */	
	public function register()
	{
	}
	
	/**
	 * 
	 * @return void
	 */
	public function init()
	{
		if (cli()) {
			$connection = $this->container->get(Connection::class);
			
			/**
			 * The migration command will allow the system to import migrations
			 * from the migrations manifest file which should contain an array
			 * of migrations to be performed to maintain the application at a
			 * modern state.
			 *
			 * Please note that the order in which the migrations appear in the
			 * manifest file is relevant to the order in which they are applied
			 * and rolled back.
			 */
			$file = config('app.database.migrations', 'bin/migrations.php');
			
			/**
			 * If there is no manifest, there is no way to consistently apply
			 * the migrations.
			 */
			if (!file_exists($file)) {
				throw new ApplicationException(sprintf('No migration manifest file in %s', $file), 2204110944);
			}
			
			/**
			 * List the migrations available to the application.
			 */
			$migrations = include(spitfire()->locations()->root($file));
			
			/**
			 * Register the available migration commands.
			 */
			$this->container->get(ConsoleKernel::class)->register(
				'migrate',
				new MigrateCommand($connection, $migrations)
			);
		}
	}
}
