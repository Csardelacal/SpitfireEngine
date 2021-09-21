<?php namespace spitfire\io\lock;


/* 
 * Copyright (C) 2021 César de la Cal Bretschneider <cesar@magic3w.com>.
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
 * MA 02110-1301  USA
 */

class FileLock implements LockInterface
{
	
	private $file;
	private $handle;
	
	public function __construct($file) 
	{
		$this->file = $file;
		$this->handle = fopen($file, file_exists($file)? 'r' : 'w+');
	}
	
	public function lock($wait = true): LockInterface 
	{
		if ($wait) { flock($this->handle, LOCK_EX); }
		elseif(!flock($this->handle, LOCK_EX | LOCK_NB)) { throw new LockUnavailableException('Could not obtain lock', 2001311655); }
		
		return $this;
	}

	public function unlock() 
	{
		flock($this->handle, LOCK_UN);
		return $this;
	}

	public function synchronize($fn, $wait = true) 
	{
		try {
			$this->lock($wait);
			$fn();
			$this->unlock();
		} 
		catch (LockUnavailableException$ex) { return; }
	}

}
