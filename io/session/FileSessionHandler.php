<?php namespace spitfire\io\session;

use spitfire\exceptions\FileNotFoundException;
use spitfire\exceptions\PrivateException;
use spitfire\io\session\Session;

class FileSessionHandler extends SessionHandler
{

	private $directory;
	
	private $handle;
	
	private $locked = false;

	public function __construct($directory, $timeout = null) {
		$this->directory = $directory;
		parent::__construct($timeout);
	}

	public function close() {
		flock($this->handle, LOCK_UN);
		fclose($this->handle);
		return true;
	}

	public function destroy($id) {
		$file = sprintf('%s/sess_%s', $this->directory, $id);
		file_exists($file) && unlink($file);

		return true;
	}

	public function gc($maxlifetime) {
		if ($this->getTimeout()) { $maxlifetime = $this->getTimeout(); }

		foreach (glob("$this->directory/sess_*") as $file) {
			if (filemtime($file) + $maxlifetime < time() && file_exists($file)) {
				unlink($file);
			}
		}

		return true;
	}

	public function open($savePath, $sessionName) {
		if (empty($this->directory)) { 
			$this->directory = $savePath; 
		}

		if (!is_dir($this->directory) && !mkdir($this->directory, 0777, true)) {
			throw new FileNotFoundException($this->directory . 'does not exist and could not be created');
		}
		
		#Initialize the session itself
		$id   = Session::sessionId(false);
		$file = sprintf('%s/sess_%s', $this->directory, $id);
		
		$this->handle = fopen($file, 'r+');
		flock($this->handle, LOCK_EX);

		return true;
	}

	public function read($__garbage) {
		//The system can only read the first 8MB of the session.
		//We do hardcode to improve the performance since PHP will stop at EOF
		return (string) fread($this->handle, 8 * 1024 * 1024); 
	}

	public function write($__garbage, $data) {
		//If your session contains more than 8MB of data you're probably doing
		//something wrong.
		if (isset($data[8*1024*1024])) { 
			throw new PrivateException('Session length overflow', 171228); 
		}
		
		return fwrite($this->handle, $data);
	}

}
