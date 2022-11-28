<?php namespace spitfire\io\session;

use Psr\Container\ContainerInterface;
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
		$settings = $config->splice('spitfire.io.session');
		
		switch ($settings->get('handler', null)) {
			/**
			 * If the file session handler is used, the system will write the
			 * sessions to the folder the user indicated for this. Please note that
			 * if this folder is not writable, the system will fail.
			 */
			case 'file':
				$_session = new Session(new FileSessionHandler($settings->get('directory', session_save_path())));
				$container->set(Session::class, $_session);
				break;
			/**
			 * Assuming the developer selected no session handling mechanism
			 * at all, we will default to using file based sessions
			 */
			case '':
			case null:
				$container->set(Session::class, new Session(new FileSessionHandler(session_save_path())));
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
