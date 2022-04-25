<?php namespace spitfire\storage\database\support\services;

use spitfire\core\service\Provider;
use spitfire\storage\database\Connection;
use spitfire\storage\database\ConnectionManager;

class DatabaseServiceProvider extends Provider
{
	
	/**
	 * 
	 * @return void
	 */
	public function register()
	{
		$default = config('database.default');
		$manager = new ConnectionManager(config('database.connections'));
		
		$this->container->set(ConnectionManager::class, $manager);
		$this->container->set(Connection::class, $manager->get($default));
	}
	
	/**
	 * 
	 * @return void
	 */
	public function init()
	{
	}
}
