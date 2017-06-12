<?php namespace spitfire\core;

use ArrayAccess;
use BadMethodCallException;
use spitfire\exceptions\OutOfBoundsException;
use spitfire\exceptions\OutOfRangeException;

/**
 * The collection class is intended to supercede the array and provide additional
 * functionality and ease of use to the programmer.
 */
class Collection implements ArrayAccess, CollectionInterface
{
	private $arr;
	
	/**
	 * The collection element allows to extend array functionality to provide
	 * programmers with simple methods to aggregate the data in the array.
	 * 
	 * @param Collection|mixed $e
	 */
	public function __construct($e = null) {
		if ($e === null)                  {	$this->arr = []; }
		elseif ($e instanceof Collection) { $this->arr = $e->toArray(); }
		elseif (is_array($e))             { $this->arr = $e; }
		else                              { $this->arr = [$e]; }
	}
	
	/**
	 * This method iterates over the elements of the array and applies a provided
	 * callback to each of them. The value your function returns if placed in the
	 * array.
	 * 
	 * @param callable|array $callable
	 * @return Collection
	 * @throws BadMethodCallException
	 */
	public function each($callable) {
		
		/*
		 * If the callback provided is not a valid callable then the function cannot
		 * properly continue.
		 */
		if (!is_callable($callable)) { 
			throw new BadMethodCallException('Invalid callable provided to collection::each()', 1703221329); 
		}
		
		return new Collection(array_map($callable, $this->arr));
	}
	
	/**
	 * Reduces the array to a single value using a callback function.
	 * 
	 * @param callable $callback
	 * @param mixed    $initial
	 * @return mixed
	 */
	public function reduce($callback, $initial = null) {
		return array_reduce($this->arr, $callback, $initial);
	}
	
	/**
	 * This function checks whether a collection contains only elements with a 
	 * given type. This function also accepts base types.
	 * 
	 * Following base types are accepted:
	 * 
	 * <ul>
	 * <li>int</li><li>float</li>
	 * <li>number</li><li>string</li>
	 * <li>array</li>
	 * <ul>
	 * 
	 * @param string $type Base type or class name to check.
	 * @return type
	 */
	public function containsOnly($type) {
		switch($type) {
			case 'int'   : return $this->reduce(function ($p, $c) { return $p && is_int($c); }, true);
			case 'float' : return $this->reduce(function ($p, $c) { return $p && is_float($c); }, true);
			case 'number': return $this->reduce(function ($p, $c) { return $p && is_numeric($c); }, true);
			case 'string': return $this->reduce(function ($p, $c) { return $p && is_string($c); }, true);
			case 'array' : return $this->reduce(function ($p, $c) { return $p && is_array($c); }, true);
			default      : return $this->reduce(function ($p, $c) use ($type) { return $p && is_a($c, $type); }, true);
		}
	}
	
	/**
	 * Reports whether the collection is empty.
	 * 
	 * @return boolean
	 */
	public function isEmpty() {
		return empty($this->arr);
	}
	
	/**
	 * Filters the collection using a callback. This allows a collection to shed
	 * values that are not useful to the programmer.
	 * 
	 * Please note that this will return a copy of the collection and the original
	 * collection will remain unmodified.
	 * 
	 * @param callable $callback
	 * @return \spitfire\core\Collection
	 */
	public function filter($callback = null) {
		#If there was no callback defined, then we filter the array without params
		if ($callback === null) { return new Collection(array_filter($this->arr)); }
		
		#Otherwise we use the callback parameter to filter the array
		return new Collection(array_filter($this->arr, $callback));
	}
	
	/**
	 * Counts the number of elements inside the collection.
	 * 
	 * @return int
	 */
	public function count() {
		return count($this->arr);
	}
	
	/**
	 * Adds up the elements in the collection. Please note that this method will
	 * double check to see if all the provided elements are actually numeric and
	 * can be added together.
	 * 
	 * @return int|float
	 * @throws BadMethodCallException
	 */
	public function sum() {
		if ($this->isEmpty())               { throw new BadMethodCallException('Collection is empty'); }
		if (!$this->containsOnly('number')) { throw new BadMethodCallException('Collection does contain non-numeric types'); }
		
		return array_sum($this->arr);
	}
	
	/**
	 * Returns the average value of the elements inside the collection.
	 * 
	 * @throws BadMethodCallException If the collection contains non-numeric values
	 * @return int|float
	 */
	public function avg() {
		return $this->sum() / $this->count();
	}
	
	/**
	 * Extracts a certain key from every element in the collection. This requires
	 * every element in the collection to be either an object or an array.
	 * 
	 * The method does not accept values that are neither array nor object, but 
	 * will return null if the key is undefined in the array or object being used.
	 * 
	 * @param mixed $key
	 */
	public function extract($key) {
		return new Collection(array_map(function ($e) use ($key) {
			if (is_array($e))  { return isset($e[$key])? $e[$key] : null; }
			if (is_object($e)) { return isset($e->$key)? $e->$key : null; }
			
			throw new OutOfBoundsException('Collection::extract requires array to contain only arrays and objects');
		}, $this->arr));
	}
	
	public function push($element) {
		$this->arr[] = $element;
		return $element;
	}
	
	public function current() {
		return current($this->arr);
	}
	
	public function key() {
		return key($this->arr);
	}
	
	public function next() {
		return next($this->arr);
	}
	
	public function offsetExists($offset) {
		return array_key_exists($offset, $this->arr);
	}
	
	public function offsetGet($offset) {
		if (!array_key_exists($offset, $this->arr)) {
			throw new OutOfRangeException('Undefined index: ' . $offset, 1703221322);
		}
		
		return $this->arr[$offset];
	}
	
	public function offsetSet($offset, $value) {
		$this->arr[$offset] = $value;
	}
	
	public function offsetUnset($offset) {
		unset($this->arr[$offset]);
	}
	
	public function rewind() {
		return reset($this->arr);
	}
	
	public function pluck() {
		return array_shift($this->arr);
	}
	
	/**
	 * Indicates whether the current element in the Iterator is valid. To achieve
	 * this we use the key() function in PHP which will return the key the array
	 * is currently forwarded to or (which is interesting to us) NULL in the event
	 * that the array has been forwarded past it's end.
	 * 
	 * @see key
	 * @return boolean
	 */
	public function valid() {
		return null !== key($this->arr);
	}
	
	public function toArray() {
		return $this->arr;
	}
	
	public function __isset($name) {
		return isset($this->arr[$name]);
	}

}
