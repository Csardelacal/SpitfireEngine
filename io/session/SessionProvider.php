<?php namespace spitfire\io\session;

use Psr\Container\ContainerInterface;
use SessionHandler;
use spitfire\core\config\Configuration;
use spitfire\core\service\Provider;
use spitfire\exceptions\ApplicationException;
use spitfire\provider\Container;

class SessionProvider extends Provider
{
	
	public function init(ContainerInterface $container) : void
	{
	}
	
	public function register(ContainerInterface $container) : void
	{
		
		/**
		 *
		 * @var Container
		 */
		$container = $container->get(Container::class);
		
		$config = $container->get(Configuration::class);
		$settings = $config->splice('session.driver');
		
		switch ($settings->get('handler', null)) {
			/**
			 * Assuming the developer selected no session handling mechanism
			 * at all, we will default to using file based sessions
			 */
			case 'php':
			case '':
			case null:
				$handler = new SessionHandler();
				$container->set(SessionHandler::class, $handler);
				break;
			/**
			 * The user provided a configuration that we cannot associate with any
			 * session handler that ships with spitfire, making it impossible to find this
			 * session.
			 */
			default:
				throw new ApplicationException('No valid session handler was found', 2105271304);
		}
	}
}
