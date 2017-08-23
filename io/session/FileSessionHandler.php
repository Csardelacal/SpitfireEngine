<?php namespace spitfire\io\session;

use spitfire\io\session\Session;

class FileSessionHandler extends SessionHandler
{

	private $directory;
	
	private $handle;
	
	private $src;

	public function __construct($directory, $timeout = null) {
		$this->directory = $directory;
		parent::__construct($timeout);
	}

	public function close() {
		$this->handle && flock($this->handle, LOCK_UN);
		return true;
	}

	public function destroy($id) {
		$file = sprintf('%s/sess_%s', $this->directory, $id);
		$this->handle = null;
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
			throw new \spitfire\exceptions\FileNotFoundException($this->directory . 'does not exist and could not be created');
		}

		return true;
	}

	public function read($__garbage) {
		$id = Session::sessionId(false);
		$file = sprintf('%s/sess_%s', $this->directory, $id);

		if (!file_exists($file)) { return ''; }
		
		$this->handle = fopen($file, 'r+');
		flock($this->handle, LOCK_EX);
		
		$this->src = (string)fread($this->handle, filesize($file));
		return $this->src;
	}

	public function write($__garbage, $data) {
		
		if (!$this->handle) {
			$id = Session::sessionId(false);
			$this->handle = fopen(sprintf('%s/sess_%s', $this->directory, $id), 'w+');
		}
		
		if ($data === $this->src) {
			return true;
		}
		
		ftruncate($this->handle, 0);
		rewind($this->handle);
		return fwrite($this->handle, $data) !== false;
	}

}

/*
 * Migrated the session handler to regular old file writes, I hope this 
will prevent the app from making an excessive amount of syscalls on 
behalf of the session.

I'm also testing not writing the session if the data is already 
available. Which should reduce disk IO too.

Not 100 % certain how smart linux is about this. Maybe if the file was
opened for writing but not actually written, it will not flush the file
to drive.
 */
