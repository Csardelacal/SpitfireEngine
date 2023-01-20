<?php namespace spitfire\defer;

use AndrewBreksa\RSMQ\Exceptions\QueueNotFoundException;
use AndrewBreksa\RSMQ\RSMQClient;
use spitfire\defer\WorkerFactory;
use Predis\Client;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
	name: 'defer:process',
	description: 'Processes the defer queue'
)]

class QueueCommand extends Command
{
	
	public ContainerInterface $container;
	
	public function __construct(ContainerInterface $container)
	{
		$this->container = $container;
		parent::__construct();
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) : int
	{
		$client = new RSMQClient(
			new Client(['host' => 'redis'])
		);
		
		$queue = 'defer';
		
		try {
			$client->getQueueAttributes($queue);
			$client->setQueueAttributes($queue, 1200, 0, -1);
		}
		catch (QueueNotFoundException $e) {
			$client->createQueue($queue, 1200, 0, -1);
		}
		
		
		$worker = new WorkerFactory(
			$this->container,
			$client,
			$queue
		);
		
		$worker->make()->work();
		
		return Command::SUCCESS;
	}
}
