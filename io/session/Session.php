<?php namespace spitfire\io\session;

use SessionHandlerInterface;
use spitfire\App;
use spitfire\support\arrays\DotNotationAccessor;
use spitfire\utils\Strings;

/**
 * The Session class allows your application to write data to a persistent space
 * that automatically expires after a given time. This class allows you to quickly
 * and comfortably select the persistence mechanism you want and continue working.
 *
 * This class is a <strong>singleton</strong>. I've been working on reducing the
 * amount of single instance objects inside of spitfire, but this class is somewhat
 * special. It represents a single and global resource inside of PHP and therefore
 * will only make the system unstable by allowing several instances.
 * 
 * @todo Make the namespacing feature more sensible
 */
class Session
{
	
	/**
	 * 
	 * @var bool
	 */
	private bool $started = false;
	
	/**
	 * 
	 * @var bool
	 */
	private bool $destroy = false;
	
	/**
	 * 
	 * @var string
	 */
	private string $id;
	
	/**
	 * The session handler is in charge of storing the data to disk once the system
	 * is done reading it.
	 *
	 * @var SessionHandlerInterface
	 */
	private $handler;
	
	/**
	 * 
	 * @var DotNotationAccessor
	 */
	private $content;
	
	/**
	 * The Session allows the application to maintain a persistence across HTTP
	 * requests by providing the user with a cookie and maintaining the data on
	 * the server. Therefore, you can consider all the data you read from the
	 * session to be safe because it stems from the server.
	 *
	 * You need to question the fact that the data actually belongs to the same
	 * user, since this may not be guaranteed all the time.
	 *
	 * @param SessionHandlerInterface $handler
	 * @param string $id
	 */
	public function __construct(SessionHandlerInterface $handler, string $id)
	{
		$this->id      = $id;
		$this->handler = $handler;
		
		$arr = [];
		$this->content = new DotNotationAccessor($arr);
	}
	
	/**
	 * Load the session using the provided handler.
	 * 
	 * @return void
	 */
	public function load() : void
	{
		/**
		 * 
		 * @var string|false
		 */
		$read = $this->handler->read($this->id);
		
		if ($read === false) {
			$this->id = Strings::random(40);
			return;
		}
		
		$arr = unserialize($read);
		
		$this->content = new DotNotationAccessor($arr);
		$this->started = true;
	}
	
	/**
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function set(string $key, $value) : void
	{
		/**
		 * Whenever a session was written to, it is marked as started. This allows the
		 * application to determine that it needs to send the appropiate cookie to the
		 * user.
		 */
		$this->started = true;
		$this->content->set($key, $value);
	}
	
	/**
	 * 
	 * @param string $key
	 * @return mixed
	 */
	public function get(string $key)
	{
		return $this->content->get($key);
	}
	
	public function getId() : string
	{
		return $this->id;
	}
	
	/**
	 * 
	 * @param mixed $userdata
	 * @deprecated 
	 * @todo Move to user authentication middleware or something
	 */
	public function lock($userdata) : void
	{
		
		$user = array();
		$user['ip']       = $_SERVER['REMOTE_ADDR'];
		$user['userdata'] = $userdata;
		$user['secure']   = true;
		
		$this->set('_SF_Auth', $user);
	}
	
	
	/**
	 * 
	 * @deprecated 
	 * @todo Move to user authentication middleware or something
	 */
	public function isSafe() : bool
	{
		
		$user = $this->get('_SF_Auth');
		if ($user) {
			$user['secure'] = $user['secure'] && ($user['ip'] == $_SERVER['REMOTE_ADDR']);
			
			$this->set('_SF_Auth', $user);
			return $user['secure'];
		}
		else {
			return false;
		}
	}
	
	
	/**
	 * 
	 * @return mixed
	 * @deprecated 
	 * @todo Move to user authentication middleware or something
	 */
	public function getUser()
	{
		
		$user = $this->get('_SF_Auth');
		return $user? $user['userdata'] : null;
	}
	
	public function isStarted() : bool
	{
		return $this->started;
	}
	
	/**
	 * Destroys the session. This code will automatically unset the session cookie,
	 * and delete the file (or whichever mechanism is used).
	 */
	public function destroy() : void
	{
		$this->destroy = true;
	}
	
	public function isDestroyed() : bool
	{
		return $this->destroy;
	}
}
