<?php namespace spitfire\defer;

/*
 *
 * Copyright (C) 2023 César de la Cal Bretschneider <cesar@magic3w.com>.
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
 * MA 02110-2023  USA
 *
 */

use AndrewBreksa\RSMQ\ExecutorInterface;
use AndrewBreksa\RSMQ\Message;
use AndrewBreksa\RSMQ\QueueWorker;
use AndrewBreksa\RSMQ\RSMQClient;
use AndrewBreksa\RSMQ\WorkerSleepProvider;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use spitfire\provider\Container;
use Throwable;

class WorkerFactory
{
	
	/**
	 *
	 * @var ContainerInterface
	 */
	private $container;
	
	/**
	 *
	 * @var RSMQClient
	 */
	private $client;
	
	/**
	 *
	 * @var string
	 */
	private $queue;
	
	/**
	 *
	 * @param RSMQClient $client
	 * @param string $queue
	 */
	public function __construct(ContainerInterface $container, RSMQClient $client, string $queue)
	{
		$this->client = $client;
		$this->queue = $queue;
		$this->container = $container;
	}
	
	public function make() : QueueWorker
	{
		
		$executor = new class($this->container) implements ExecutorInterface
		{
			
			/**
			 *
			 * @var ContainerInterface
			 */
			private $container;
			
			public function __construct(ContainerInterface $container)
			{
				$this->container = $container;
			}
			
			/**
			 *
			 * @throws NotFoundExceptionInterface
			 * @throws ContainerExceptionInterface
			 */
			public function __invoke(Message $message) : bool
			{
				$payload = json_decode($message->getMessage());
				$started = microtime(true);
				
				/**
				 * Assert that the message is not malformed. Otherwise our component
				 * will throw an exception.
				 */
				assert(is_object($payload));
				assert(isset($payload->task));
				assert(isset($payload->settings));
				assert(isset($payload->expires));
				assert($this->container instanceof Container);
				assert(is_string($payload->task) && class_exists($payload->task));
				
				/**
				 *
				 * @var LoggerInterface
				 */
				$logger = $this->container->get(LoggerInterface::class);
				
				try {
					if (!$this->container->has($payload->task)) {
						throw new \Exception(sprintf('Bad task %s', $payload->task));
					}
					
					/**
					 * @var \spitfire\defer\Task
					 */
					$task = $this->container->get($payload->task);
					
					
					
					/**
					 * If a task is not a task that we can execute, we need to not execute it since it may
					 * cause behavior that we did not anticipate.
					 */
					assert($task instanceof Task);
					
					/**
					 * Execute the actual task
					 */
					$task->body($payload->settings);
					$logger->info(sprintf(
						'Task processed successfully - %s (%s) {%.2f sec}',
						$payload->task,
						is_scalar($payload->settings)? $payload->settings : 'object',
						microtime(true) - $started
					));
					
					$logger->info(json_encode($payload, JSON_THROW_ON_ERROR));
					
					if ($payload->then?? false) {
						$next = $payload->then;
						$this->container->get(TaskFactory::class)->defer(...$next);
					}
				}
				catch (Throwable $e) {
					$logger->error(sprintf(
						'Task failed - %s (%s) {%.2f sec}',
						$payload->task,
						is_scalar($payload->settings)? $payload->settings : 'object',
						microtime(true) - $started
					));
					
					$logger->error(json_encode($payload)?: 'JSON Error');
					$logger->error(get_class($e));
					$logger->error($e->getMessage());
					$logger->error($e->getTraceAsString());
					$logger->error('JSON: ' . json_encode((array)$e));
					
					$logger->error($payload->expires < time()? 'Task will be retried' : 'Task abandoned');
					return $payload->expires > time();
				}
				
				return true;
			}
		};
		
		$sleepProvider = new class() implements WorkerSleepProvider
		{
			public function getSleep() : ?int
			{
				/**
				 * This allows you to return null to stop the worker, which can
				 * be used with something like redis to mark.
				 *
				 * Note that this method is called _before_ we poll for a message,
				 * and therefore if it returns null we'll eject before we process
				 * a message.
				 *
				 * @todo Listen for a signal maybe?
				 */
				return 1;
			}
		};
		
		return new QueueWorker($this->client, $executor, $sleepProvider, $this->queue);
	}
}
