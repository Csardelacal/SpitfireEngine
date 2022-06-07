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

use spitfire\core\config\Configuration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 
 * @todo Once we make SF depend on PHP8 we can prefix this like
 * 
 * #[AsCommand(
 *     name: 'config:build',
 *     description: 'Initializes the configuration and writes it to disk.',
 *     hidden: false,
 *     aliases: ['config:cache']
 * )]
 */
class BuildConfigurationDirector extends Command
{

	protected static $defaultName = 'config:build';
	protected static $defaultDescription = 'Initializes the configuration and writes it to disk.';

	private $config;
	
	/**
	 * 
	 * @var string
	 */
	private $configFile;
	
	public function __construct(string $configFile, Configuration $config)
	{
		$this->config = $config;
		$this->configFile = $configFile;
		parent::__construct();
	}
	
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$cacheFile = $this->configFile;
		
		/**
		 * Check if the local storage is writable, if this is the case, we continue
		 */
		if (is_writable($cacheFile)) {
			$output->writeln('Storage is writable');
		}
		else {
			$output->writeln(sprintf('Storage (%s) is not writable', $cacheFile));
			return 1;
		}
		
		/**
		 * At this point in time, the application MUST have loaded the configuration from 
		 * the disk (even if it's the cached version), so we can just as Spitfire for the
		 * config and write it back to the disk.
		 */
		file_put_contents($cacheFile, sprintf('return %s;', var_export($this->config->export())));
		
		/**
		 * We've successfully written the data back to the drive, and can now continue working
		 * normally.
		 */
		return 0;
	}
	
}
