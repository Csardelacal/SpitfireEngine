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
	
	private $config;
	
	public function __construct(Configuration $config)
	{
		$this->config = $config;
	}
	
	public function register(ContainerInterface $container)
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
		$settings   = $this->config->get('storage.engines', []);
		
		foreach ($settings as $key => $value) {
			assert(!empty($value['driver']));
			assert(method_exists($this, "init${value['driver']}"));
			
			$dispatcher->register($key, $this->{"init${value['driver']}"}($value));
		}
		
		$container->set(DriveDispatcher::class, $dispatcher);
	}
	
	public function init(ContainerInterface $container)
	{
	}
	
	public function initLocal(array $config)
	{
		return new FileSystem(
			new FlysystemFilesystem(
				new LocalFilesystemAdapter(
					$config['root']
				)
			)
		);
	}
	
	public function initS3(array $config)
	{
		return new FileSystem(
			new FlysystemFilesystem(
				new AwsS3V3Adapter(
					new S3Client([
						'endpoint' => $config['endpoint'],
						'use_path_style_endpoint' => $config['use_path_style_endpoint'],
						'credentials' => [
							'key'    => $config['key'],
							'secret' => $config['secret']
						],
						'region' => $config['region'],
						'version' => 'latest'
					]),
					$config['bucket']
				)
			)
		);
	}
}
