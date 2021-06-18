<?php namespace spitfire\core\kernel;

use spitfire\_init\LoadConfiguration;
use spitfire\cli\arguments\Parser;
use spitfire\collection\Collection;
use spitfire\core\kernel\exceptions\CommandNotFoundException;
use spitfire\mvc\Director;
use function spitfire;

/* 
 * The MIT License
 *
 * Copyright 2021 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * The console kernel provides mechanisms to allow a user to interact with the
 * application via the command line interface.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class ConsoleKernel implements KernelInterface
{
	
	/**
	 *
	 * @var Collection<Director>
	 */
	private $commands;
	
	public function __construct() 
	{
		$this->commands = new Collection();
	}
	
	/**
	 * The boot method receives no parameters, and is intended to let the kernel
	 * execute some initial housekeeping and setup tasks before it starts executing
	 * the user's command.
	 */
	public function boot()
	{
		
	}
	
	/**
	 * This method allows an application to register a command that the application
	 * wishes to expose to the end-user of the application.
	 * 
	 * @param string $command
	 * @param Director $body
	 * @return ConsoleKernel
	 */
	public function register(string $command, Director $body) : ConsoleKernel
	{
		$this->commands[$command] = $body;
		return $this;
	}
	
	/**
	 * The exec method takes a command, and a set of arguments to locate a single
	 * director and execute it.
	 * 
	 * @throws CommandNotFoundException
	 * @param string $command
	 * @param array $arguments
	 * @return int
	 */
	public function exec(string $command, array $arguments) : int
	{
		if (!$this->commands->has($command)) {
			throw new CommandNotFoundException(sprintf('Command %s is not available', $command));
		}
		
		/*@var $director Director*/
		$director = $this->commands[$command];
		
		/*
		 * This call applies the arguments we received from the user to the paramters
		 * the application expects in order to proceed.
		 * 
		 * In order for this to work properly, the parser needs to understand the
		 * input that the application expects, and match it against the input that
		 * the user provided.
		 */
		$extracted = spitfire()->provider()->get(Parser::class)
			->make($director->parameters())
			->apply($arguments);
		
		return $director->exec($extracted->parameters(), $extracted->arguments());
	}
	
	/**
	 * The all method is intended as a mechanism to return a list of directors
	 * available to the user, making it easier for them to select a command to
	 * execute.
	 * 
	 * @return Collection<Director>
	 */
	public function all() : Collection
	{
		return $this->commands;
	}
	
	/**
	 * The list of init scripts that need to be executed in order for the kernel to
	 * be usable.
	 * 
	 * @return array
	 */
	public function initScripts(): array 
	{
		return [
			LoadConfiguration::class
		];
	}
}
