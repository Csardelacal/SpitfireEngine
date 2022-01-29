<?php namespace spitfire\core\config\directors;

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

use spitfire\mvc\Director;

/**
 * 
 */
class BuildConfigurationDirector extends Director
{
	
	/**
	 * 
	 * @param array $parameters
	 * @param \spitfire\cli\arguments\CLIParameters $arguments
	 * @return int
	 */
	public function exec(array $parameters, \spitfire\cli\arguments\CLIParameters $arguments): int 
	{
		
		$cacheFile = spitfire()->locations()->root('bin/config.php');
		
		/**
		 * Check if the local storage is writable, if this is the case, we continue
		 */
		if (is_writable($cacheFile)) {
			console()->success('Storage is writable')->ln();
		}
		else {
			console()->error(sprintf('Storage (%s) is not writable', $cacheFile))->ln();
			return 1;
		}
		
		/**
		 * At this point in time, the application MUST have loaded the configuration from 
		 * the disk (even if it's the cached version), so we can just as Spitfire for the
		 * config and write it back to the disk.
		 */
		$config = spitfire()->config();
		file_put_contents($cacheFile, sprintf('return %s;', var_export($config->export())));
		
		/**
		 * We've successfully written the data back to the drive, and can now continue working
		 * normally.
		 */
		return 0;
	}
	
	/**
	 * We do not accept any parameters to the storage writable check.
	 * 
	 * @return array
	 */
	public function parameters(): array 
	{
		return [];
	}
}
