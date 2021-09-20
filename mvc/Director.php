<?php namespace spitfire\mvc;

use spitfire\cli\arguments\CLIParameters;

/**
 * The director allows an application to register a command that should be able 
 * to be invoked via CLI.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
abstract class Director
{
	
	/**
	 * Return an array that describes what parameters the application expects.
	 * This looks something like this:
	 * 
	 * <code>
	 * return [
	 * 	'-v' => '--verbose',
	 * 	'-t' => '--tag',
	 * 	'--verbose' => [
	 * 		'type' => 'bool',
	 * 		'description' => 'Provide verbose output'
	 * 	],
	 * 	'--tag' => [
	 * 		'required' => false,
	 * 		'type' => 'string',
	 * 		'description' => 'Selects which tag to use. If omitted, the script will ask for a tag'
	 * 	]
	 * ];
	 * </code>
	 * 
	 * The array may contain any of the following: a string or an array.
	 * 
	 * If the key contains a string, it redirects the settings to the appropriate
	 * key. In our example the '-v' can be redirected to '--verbose', but also 
	 * deprecated keys can be addressed like '--debug' to '--verbose'
	 * 
	 * If the key contains an array, three keys are expected:
	 * 
	 * - Required: Indicating whether the key is required to execute the director
	 * - Type: Allowing the system to pre-process input (arrays can be read from multiple params and ints can be type checked)
	 * - Description: If the user requests help or a list for the parameters, they will get this.
	 * 
	 * @return array
	 */
	public abstract function parameters() : array;
	
	/**
	 * Executes the director, handling control to the application implementing it.
	 * 
	 * @param array $parameters The array of parameters parsed from the input, using the data provided by parameters()
	 * @param CLIParameters $arguments The raw arguments read from the process
	 * @return int The return code
	 */
	public abstract function exec(array $parameters, CLIParameters $arguments): int;
	
}
