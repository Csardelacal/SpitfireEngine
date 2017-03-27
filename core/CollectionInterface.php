<?php namespace spitfire\core;

/**
 * A collection is a set of values that can be iterated over and can apply certain
 * operations to it's values.
 * 
 * One quirk of the collection is that it superceeds both array like data that
 * is defined within the app's scope and for pointers - for which the application
 * does not know the scope ahead of reading it from a source.
 * 
 * The collection should always be able to provide an array for the data it 
 * contains via the toArray() method. It is possible though that this method
 * throws an exception to indicate that the data cannot be casted to array.
 */
interface CollectionInterface extends \Iterator
{
	
	/**
	 * Indicates whether the collection contains any values at all.
	 * 
	 * @return boolean
	 */
	function isEmpty();
	
	/**
	 * Counts the number of elements that the collection holds. This method may
	 * throw an exception if the set's size is undefined.
	 */
	function count();
	
	/**
	 * Removes the first element from the collection (shifts it off).
	 * 
	 * @return mixed
	 */
	function pluck();
	
	/**
	 * Uses a callback function to filter the elements of the array. The function
	 * passed will receive each element of the collection and if it returns true the 
	 * element will be removed from the collection.
	 * 
	 * @param callable $callback Function returning a boolean value that indicates 
	 *                           whether the element should be removed.
	 * 
	 * @return CollectionInterface The filtered collection.
	 */
	function filter($callback = null);
	
	/**
	 * Loops over the elements of the collection applying the callable function,
	 * the return value will be placed in the output collection.
	 * 
	 * @param callable $callable Function to be applied to each element
	 * @return CollectionInterface The collection of elements after the function 
	 *                             was applied
	 */
	function each($callable);
	
	/**
	 * Reduces the collection to a single element. It does this by looping over 
	 * the elements of the collection and combining the "initial" value or the 
	 * value of the previous iteration with the next value.
	 * 
	 * @param callable $callable
	 * @param mixed    $initial
	 */
	function reduce($callable, $initial = null);
	
	/**
	 * Returns the elements of the collection as a PHP array. Please note that in
	 * the event of a collection not being cast-able you will be required to catch
	 * the exception generated.
	 * 
	 * @return mixed
	 * @throws Exception If the collection cannot be casted to array
	 * @todo   Name the exception thrown.
	 */
	function toArray();
	
}
