<?php namespace spitfire\contracts\core;

use Closure;

/**
 * Provider is a dependency injection mechanism. It allows your
 * application to request an instance of a certain class and it
 * will 'autowire' it so all the depencencies needed for the
 * object to function are already provided. Hence the name
 *
 * @todo This should allow certain components to require something 
 * slightly more complex than the psr container.
 * @todo Merge the container package into the main repository.
 * 
 * @author César de la Cal Bretschneider
 */
interface ContainerInterface extends \Psr\Container\ContainerInterface
{
	
	/**
	 * The set method allows the application to define a service for a certain key.
	 *
	 * @param string $id   The key / classname the service should be located at
	 * @param mixed  $item The service. May be an instance of a class, a string containing a classname, or a service
	 * @return ContainerInterface
	 */
	public function set($id, $item);
	
	/**
	 *
	 * @param string $id
	 * @param Closure $callable
	 * @return ContainerInterface
	 */
	public function factory($id, Closure $callable);
	
	
	/**
	 *
	 * @param string $id
	 * @param Closure $callable
	 * @return ContainerInterface
	 */
	public function singleton($id, Closure $callable);
	
	/**
	 * Uses the container as a factory, you provide arguments that may not be
	 * automatically resolved or you wish to override.
	 *
	 * @param class-string $id
	 * @param mixed[] $parameters
	 * @return object
	 */
	public function assemble($id, $parameters = []);
	
	
	/**
	 * Call makes it possible for applications to pass a closure with a certain
	 * set of requirements, similar to how Javascript DI works, and our container
	 * will provide the right arguments for the task.
	 *
	 * @param callable $fn
	 * @param mixed[] $params
	 * @return mixed The result of the function
	 */
	public function call(callable $fn, array $params = []);
	
	
	/**
	 * Invokes a method on an object, providing all the dependencies necessary to
	 * make the method work.
	 *
	 * This method is a bad candidate for classes where you already know the name
	 * of the method being executed (since it makes it hard for IDEs to find usages)
	 * but it is a good replacement for any situation where you were forced to use
	 * a construct like `user_func_array` to invoke a method.
	 *
	 * @param object $object
	 * @param string $method
	 * @param mixed[] $params
	 * @return mixed Passthrough of the data returned by the method
	 */
	public function callMethod(object $object, $method, $params = []);
}
