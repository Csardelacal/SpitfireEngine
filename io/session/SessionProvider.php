<?php namespace spitfire\io\session;

use spitfire\core\config\Configuration;
use spitfire\core\service\Provider;
use spitfire\exceptions\ApplicationException;

class SessionProvider extends Provider
{
	
	public function init() 
	{
	}
	
	public function register()
	{
		$config = $this->container->get(Configuration::class);
		$settings = $config->get('spitfire.io.session');
		
		switch ($settings['handler']?? null) {
			/**
			 * If the file session handler is used, the system will write the 
			 * sessions to the folder the user indicated for this. Please note that
			 * if this folder is not writable, the system will fail.
			 */
			case 'file':
				$_session = new Session(new FileSessionHandler($settings['directory']?? session_save_path()));
				$this->container->set(Session::class, $_session);
				break;
			/**
			 * Assuming the developer selected no session handling mechanism
			 * at all, we will default to using file based sessions
			 */
			case '':
			case null:
				$this->container->set(Session::class, new Session(new FileSessionHandler(session_save_path())));
				break;
			/**
			 * The user provided a configuration that we cannot associate with any
			 * session handler that ships with spitfire, making it impossible to find this 
			 * session.
			 */
			default:
				throw new ApplicationException('No valid session handler was found', 2105271304);
			break;
		}
	}
}
