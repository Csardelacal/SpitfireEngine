<?php namespace spitfire\support\arrays;

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

/**
 * The dot notation accessor allows applications to access nested arrays by using a
 * simple string with dots for separators. It also provides the ability to access
 * the data without having to check intermediate steps
 *
 * In Spitfire this is mostly used in the configuration. This provides us with a
 * good mechanism to access configuration like this:
 *
 * config('vendor.com.magic3w.example', 'example);
 *
 * While using a pure array would lead to something like this:
 *
 * config()['vendor']['com']['magic3w']['example']?? 'example';
 *
 * If we wanted to make sure that all the arrays in the path are defined, we would
 * have to check whether each of them isset before accessing the data, which would
 * lead to several lines of code just to read the variable.
 */
class DotNotationAccessor
{
	
	/**
	 * Determines whether the accessor should return an array for the given data or
	 * whether the accessor should return null if the data did not hit a leaf.
	 */
	const ALLOW_ARRAY_RETURN = 0x0001;
	
	/**
	 * Contains the raw data for the accessor. This must be an array.
	 *
	 * @var mixed[]
	 */
	private $data;
	
	/**
	 * Instances a new dot notation accessor for an array. Please note that changes to the
	 * array will be broadcast to the original array.
	 *
	 * Since the accessor expects the array to be passed by reference, you cannot pass the
	 * result of a function call directly without getting a PHP warning.
	 *
	 * @param mixed[] $data
	 */
	public function __construct(array &$data)
	{
		$this->data = &$data;
	}
	
	/**
	 * Returns whether the original array contains the key that is being requested.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function has(string $key) : bool
	{
		$steps = explode('.', $key);
		$current = &$this->data;
		
		while ($steps) {
			$key = array_shift($steps);
			
			/*
			 * If the key does not exist, we cannot continue further down into the
			 * array. We need to stop here.
			 */
			if (!array_key_exists($key, $current)) {
				return false;
			}
			
			/**
			 * Step into the array
			 */
			$current = &$current[$key];
		}
		
		return true;
	}
	
	/**
	 * Returns the requested key from the array.
	 *
	 * @param string $key
	 * @param int $flags Currently only the ALLOW_ARRAY_RETURN flag is supported
	 * @return mixed
	 */
	public function get(string $key, int $flags = 0)
	{
		$steps = explode('.', $key);
		$current = &$this->data;
		
		while ($steps) {
			$step = array_shift($steps);
			
			/*
			 * If the step does not exist, we cannot continue further down into the
			 * array. We need to stop here. Leet the user know in a warning that this
			 * is technically not okay.
			 */
			if (!array_key_exists($step, $current)) {
				trigger_error(sprintf('%s does not exist', $step), E_USER_WARNING);
				return null;
			}
			
			$current = &$current[$step];
		}
		
		/**
		 * If we're not supposed to return arrays, but the data at hand is an array,
		 * we will return a null value.
		 */
		if (is_array($current) && !($flags & self::ALLOW_ARRAY_RETURN)) {
			return null;
		}
		
		return $current;
	}
	
	/**
	 * Writes a key to the array. This will override the data, arrays will not be merged.
	 *
	 * @param string $key
	 * @param mixed $data
	 * @return DotNotationAccessor
	 */
	public function set(string $key, $data) : DotNotationAccessor
	{
		$steps = explode('.', $key);
		$current = &$this->data;
		
		while (isset($steps[1])) {
			$key = array_shift($steps);
			
			/*
			 * If the key does not exist, we cannot continue further down into the
			 * array. We need to stop here.
			 */
			if (!array_key_exists($key, $current) || !is_array($current[$key])) {
				$current[$key] = [];
			}
			
			$current = &$current[$key];
		}
		
		$current[$steps[0]] = $data;
		return $this;
	}
}
