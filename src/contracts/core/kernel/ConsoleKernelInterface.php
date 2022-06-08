<?php namespace spitfire\contracts\core\kernel;

use spitfire\core\kernel\exceptions\CommandNotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
interface ConsoleKernelInterface extends KernelInterface
{
	
	/**
	 * The boot method receives no parameters, and is intended to let the kernel
	 * execute some initial housekeeping and setup tasks before it starts executing
	 * the user's command.
	 */
	public function boot() : void;
	
	/**
	 * This method allows an application to register a command that the application
	 * wishes to expose to the end-user of the application.
	 *
	 * @param Command $command
	 * @return ConsoleKernelInterface
	 */
	public function register(Command $command) : ConsoleKernelInterface;
	
	/**
	 * The exec method takes a command, and a set of arguments to locate a single
	 * command and execute it.
	 *
	 * @throws CommandNotFoundException
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 */
	public function handle(InputInterface $input, OutputInterface $output) : int;
}
