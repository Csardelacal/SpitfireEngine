<?php namespace tests\spitfire\core\config;

use PHPUnit\Framework\TestCase;
use spitfire\_init\LoadConfiguration;
use spitfire\core\config\ConfigurationLoader;
use spitfire\core\Locations;

class LoadConfigurationTest extends TestCase
{
	
	public function testLoad()
	{
		$locations = new Locations(__DIR__ . '/_loadFixtures');
		$loader = new ConfigurationLoader($locations);
		
		$config = $loader->make();
		
		$this->assertEquals(false, $config->get('app.php.test', false));
		$this->assertEquals("hello world", $config->get('app.test'));
		$this->assertEquals("example", $config->get('app.demo'));
	}
}
