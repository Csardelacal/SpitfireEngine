<?php namespace spitfire\io\session;

use SessionHandlerInterface;

abstract class SessionHandler implements SessionHandlerInterface
{
	
	private $timeout = null;
	
	public function __construct($timeout)
	{
		$this->timeout = $timeout;
	}
	
	public function attach()
	{
		session_set_save_handler($this);
	}
	
	public function getTimeout()
	{
		return $this->timeout;
	}
	
	public function setTimeout($timeout)
	{
		$this->timeout = $timeout;
		return $this;
	}
	
	public function start($savePath, $sessionName)
	{
		
		/**
		 * Open the session. The start method is kinda special, since we need to
		 * set the cookies right after opening it. So we register this hook that
		 * will open the session and then send the cookies.
		 */
		$this->open($savePath, $sessionName);
	}
	
	abstract public function open($savePath, $sessionName) : bool;
	abstract public function close() : bool;
	abstract public function read($id) : string|false;
	abstract public function write($id, $data) : bool;
	abstract public function destroy($id) : bool;
	abstract public function gc($maxlifetime) : int|false;
}
