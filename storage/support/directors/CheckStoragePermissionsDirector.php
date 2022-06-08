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

use spitfire\core\Locations;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 */
class CheckStoragePermissionsDirector extends Command
{
	
	protected static $defaultName = 'filesystem:check';
	protected static $defaultDescription = 'Checks whether the storage has the necessary permissions.';
	
	private $locations;
	
	public function __construct(Locations $locations)
	{
		$this->locations = $locations;
		parent::__construct();
	}
	
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		
		$success = true;
		
		/**
		 * Check if the local storage is writable, if this is the case, we continue
		 */
		if (is_writable($this->locations->storage())) {
			$output->writeln('Storage is writable');
		}
		else {
			$output->writeln(sprintf('<error>Failure</> Storage (%s) is not writable', $this->locations->storage()));
			$success = false;
		}
		
		/**
		 * Check if the public storage directory is writable. If this is not the
		 * case, the application cannot create files that can be served by the
		 * web-server directly.
		 */
		if (is_writable($this->locations->publicStorage())) {
			$output->writeln('Storage is writable');
		}
		else {
			$output->writeln(sprintf(
				'<error>Failure</> Storage (%s) is not writable',
				$this->locations->publicStorage()
			));
			$success = false;
		}
		
		/*
		 * If any of our earlier checks failed, the application should return a non
		 * zero state.
		 */
		return $success? 0 : 1;
	}
}
