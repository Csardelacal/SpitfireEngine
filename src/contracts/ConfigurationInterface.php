<?php namespace spitfire\contracts;

/**
 * Configuration contains an array of data parsed from a configuration file (or
 * multiple, in the event of the configuration referring to a directory that contains
 * configuration files).
 * 
 * Due to the fact that configuration is cached, the system generates configuration
 * by invoking a static method that will recursively walk over all the files and 
 * import the data, assembling a tree of arrays.
 * 
 * When caching the configuration, the loaded environments are also cached and 
 * therefore your application's cache will need to be rebuilt in order to load 
 * new environments.
 * 
 * Configuration files are automatically flattened, so that information can be 
 * read with dot notation easily.
 * 
 * NOTE: Configuration does not support arrays (this is why they are flattened). I seem
 * to get tripped up by this concept myself a lot, and this is why I'm adding this
 * note. If you need to configure something in an array style fashion you're probably
 * better off using service providers.
 */
interface ConfigurationInterface
{
	
	/**
	 * Retrieve a configuration from the repository. You may not retrieve a config
	 * as an array.
	 * 
	 * @param string $key
	 * @param mixed $fallback
	 */
	public function get(string $key, $fallback = null);
}