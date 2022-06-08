<?php namespace spitfire\core\resource;

use spitfire\cli\arguments\CLIParameters;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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


class PublisherDirector extends Command
{
	
	protected static $defaultName = 'resources:publish';
	protected static $defaultDescription = 'Import resources from packages to the main repository.';
	
	private $publisher;
	private $manifestFile;
	
	public function __construct(string $manifestFile, Publisher $publisher)
	{
		$this->manifestFile = $manifestFile;
		$this->publisher = $publisher;
		
		parent::__construct();
	}
	
	protected function configure() : void
	{
		$this
			->addArgument(
				'tag',
				InputArgument::REQUIRED,
				'Selects which tag to use. If omitted, the script will ask for a tag'
			);
	}
	
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$publisher = $this->publisher;
		$file      = $this->manifestFile;
		$manifest  = file_exists($file)? json_decode(file_get_contents($file), true) : [];
		$tag       = $input->getArgument('tag');
		
		/**
		 * Get the list of files that have been published to the system already,
		 * so we can make a diff to the ones we're currently publishing.
		 */
		$published  = $manifest && isset($manifest[$tag])? $manifest[$tag] : [];
		$publishing = [];
		
		/**
		 * Loop over the files we're publishing. And generate their MD5. We can then
		 * continue by asking the user whether they are interested in continuing.
		 */
		foreach ($publisher->get($tag) as $publication) {
			list($from, $to) = $publication;
			
			$publishing = array_merge($publishing, $this->calculateChanges($from, $to));
		}
		
		foreach ($published as $existing => $meta) {
			if (!file_exists($existing)) {
				#If the file has not yet been created we can create it without issue
				continue;
			}
			
			if (!is_dir($existing) && md5_file($existing) != $meta['md5']) {
				#If the file has been modified since it was published, we need to warn
				#the user about the situation and stop the publishing.
				$output->writeln(sprintf(
					'<error>File %s was modified on disk since it was published. '.
					'Revert or delete the file to continue</>',
					$existing
				));
				return -1;
			}
			
			if (is_dir($existing) !== is_dir($publishing[$existing]['src'])) {
				#This checks whether the target is a directory, and whether the file
				#that we intend to overwrite it with is a directory. If this is the
				#case we fail with a message indicating that this is unacceptable.
				
				$output->writeln(sprintf(
					'<error>File %s is a directory and being overwritten by a file, or viceversa</>',
					$existing
				));
				return -1;
			}
		}
		
		foreach ($publishing as $target => $meta) {
			if (!isset($published[$target]) && file_exists($target)) {
				#In this scenario, the file does exist on the drive, but the
				#publishing file is unaware of it's existence. Making it impossible to
				#override safely, since we didn't put it there.
				
				$output->writeln(sprintf(
					'<error>File %s exists, but was not published by Spitfire. Delete the file to continue</>',
					$target
				));
				return -1;
			}
		}
		
		
		/**
		 * Loop over the published items once more, and remove all items that were published,
		 * but are no longer being published.
		 */
		foreach ($published as $existing => $meta) {
			if (!isset($publishing[$existing])) {
				unlink($existing);
			}
		}
		
		/**
		 * Loop over our publications and create the files or replace them as necessary.
		 */
		foreach ($publishing as $replace => $meta) {
			if (is_dir($meta['src']) && !file_exists($replace)) {
				mkdir($replace);
			}
			
			if (!is_dir($meta['src'])) {
				copy($meta['src'], $replace);
			}
		}
		
		$manifest[$tag] = $publishing;
		file_put_contents($file, json_encode($manifest, JSON_PRETTY_PRINT));
		
		return 0;
	}
	
	/**
	 * This function recursively generates a changeset for the files to be published.
	 *
	 * @param string $from
	 * @param string $to
	 * @return string[][] An array containing the changes
	 */
	private function calculateChanges(string $from, string $to)
	{
		if (is_dir($from)) {
			$dir = dir($from);
			$_ret = [];
			
			while ($file = $dir->read()) {
				if ($file == '.') {
					continue;
				}
				if ($file == '..') {
					continue;
				}
				
				$_ret[$to] = ['md5' => null, 'src' => $from];
				$_ret = array_merge($_ret, $this->calculateChanges($from . DIRECTORY_SEPARATOR . $file, $to . DIRECTORY_SEPARATOR . $file));
			}
			
			return $_ret;
		}
		else {
			return [
				$to => [
					'src' => $from,
					'md5' => md5_file($from)
				]
			];
		}
	}
}
