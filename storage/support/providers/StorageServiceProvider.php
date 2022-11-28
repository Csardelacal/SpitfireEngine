<?php namespace spitfire\storage\support\providers;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem as FlysystemFilesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Psr\Container\ContainerInterface;
use spitfire\contracts\services\ProviderInterface;
use spitfire\core\config\Configuration;
use spitfire\provider\Container;
use spitfire\storage\DriveDispatcher;
use spitfire\storage\FileSystem;

class StorageServiceProvider implements ProviderInterface
{
	
	/**
	 * 
	 * @var Configuration
	 */
	private $config;
	
	public function __construct(Configuration $config)
	{
		$this->config = $config;
	}
	
	public function register(ContainerInterface $container) : void
	{
		
		/**
		 * We need to be able to write to the container.
		 *
		 * @todo Introduce abstraction of container.
		 * @var Container
		 */
		$container = $container->get(Container::class);
		
		/**
		 *
		 * @var DriveDispatcher
		 */
		$dispatcher = $container->get(DriveDispatcher::class);
		$settings   = $this->config->splice('storage.engines');
		
		foreach ($settings->keys() as $key) {
			$scope = $settings->splice($key);
			$driver = $scope->get('driver');
			
			assert($driver);
			assert(method_exists($this, "init${driver}"));
			
			$dispatcher->register($key, $this->{"init${driver}"}($scope));
		}
		
		$container->set(DriveDispatcher::class, $dispatcher);
	}
	
	public function init(ContainerInterface $container) : void
	{
	}
	
	/**
	 * 
	 * @param Configuration $config
	 */
	public function initLocal(Configuration $config) : FileSystem
	{
		return new FileSystem(
			new FlysystemFilesystem(
				new LocalFilesystemAdapter(
					$config->get('root')
				)
			)
		);
	}
	
	/**
	 * 
	 * 
	 * @param Configuration $config
	 */
	public function initS3(Configuration $config) : FileSystem
	{
		return new FileSystem(
			new FlysystemFilesystem(
				new AwsS3V3Adapter(
					new S3Client([
						'endpoint' => $config->get('endpoint'),
						'use_path_style_endpoint' => $config->get('use_path_style_endpoint', true),
						'credentials' => [
							'key'    => $config->get('key'),
							'secret' => $config->get('secret')
						],
						'region' => $config->get('region'),
						'version' => 'latest'
					]),
					$config->get('bucket')
				)
			)
		);
	}
}
