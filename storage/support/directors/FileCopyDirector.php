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

use League\Flysystem\UnableToWriteFile;
use spitfire\core\Locations;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 */
class FileCopyDirector extends Command
{
	
	protected static $defaultName = 'filesystem:copy';
	protected static $defaultDescription = 'Copy a file from one location to another.';
	
	protected function configure() : void
	{
		$this->addArgument('from', InputArgument::REQUIRED, 'The location to copy from');
		$this->addArgument('to', InputArgument::REQUIRED, 'The location to copy to');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		
		$output->writeln($input->getArgument('from'));
		$output->writeln($input->getArgument('to'));
		
		try {
			storage()->writeStream($input->getArgument('to'), storage()->readStream($input->getArgument('from')));
			return 0;
		}
		catch (UnableToWriteFile $e) {
			
			/*
			 * If any of our earlier checks failed, the application should return a non
			 * zero state.
			 */
			return 1;
		}
	}
}
