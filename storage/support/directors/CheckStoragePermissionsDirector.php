<?php namespace spitfire\storage\support\directors;

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
class CheckStoragePermissionsDirector extends Director
{
	
	/**
	 * 
	 * @param array $parameters
	 * @param \spitfire\cli\arguments\CLIArguments $arguments
	 * @return int
	 */
	public function exec(array $parameters, \spitfire\cli\arguments\CLIArguments $arguments): int 
	{
		
		$success = true;
		
		/**
		 * Check if the local storage is writable, if this is the case, we continue
		 */
		if (is_writable(spitfire()->locations()->storage())) {
			console()->success('Storage is writable')->ln();
		}
		else {
			console()->error(sprintf('Storage (%s) is not writable', spitfire()->locations()->storage()))->ln();
			$success = false;
		}
		
		/**
		 * Check if the public storage directory is writable. If this is not the 
		 * case, the application cannot create files that can be served by the 
		 * web-server directly.
		 */
		if (is_writable(spitfire()->locations()->publicStorage())) {
			console()->success('Storage is writable')->ln();
		}
		else {
			console()->error(sprintf('Storage (%s) is not writable', spitfire()->locations()->publicStorage()))->ln();
			$success = false;
		}
		
		/*
		 * If any of our earlier checks failed, the application should return a non
		 * zero state.
		 */
		return $success? 0 : 1;
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
