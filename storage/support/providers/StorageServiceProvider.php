<?php namespace spitfire\storage\support\providers;
/*
 *
 * Copyright (C) 2023-2023 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-13 01  USA
 *
 */


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
			assert(method_exists($this, "init{$driver}"));
			
			$dispatcher->register($key, $this->{"init{$driver}"}($scope));
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
