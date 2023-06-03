<?php namespace spitfire\utils;

/*
 *
 * Copyright (C) 2023-2023 César de la Cal Bretschneider <cesar@magic3w.com>.
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
 * MA 02110-13 01  USA
 *
 */

use spitfire\collection\Collection;

class Strings
{
	
	/**
	 * Turns camelCased strings into under_scored strings. This is specially useful
	 * for class to URL conversion and the other way around.
	 *
	 * @param String $str
	 * @return string
	 */
	public static function snake(string $str) : string
	{
		$_ret = preg_replace('/[A-Z]/', '_$0', $str);
		return strtolower(trim($_ret, '_'));
	}
	
	
	/**
	 * Converts under_score separated strings into camelCased. Allowing an application
	 * to retrieve a class name from a case insensitive environment.
	 *
	 * @param string  $str  The input string (example: camel_case)
	 * @param boolean $high Defines whether the first letter should be uppercase.
	 *                      "CamelCase" (true) or "camelCase" (false)
	 * @return string
	 */
	public static function camel(string $str, bool $high = true) : string
	{
		$_ret = preg_replace_callback('/\_[a-z]/', function ($e) {
			return strtoupper($e[0][1]);
		}, $str);
		return $high? ucfirst($_ret) : $_ret;
	}
	
	public static function ellipsis(string $str, int $targetlength, string $char = '…') : string
	{
		$newlen = $targetlength - mb_strlen($char);
		return (mb_strlen($str) > $newlen)? mb_substr($str, 0, $newlen) . $char : $str;
	}
	
	public static function slug(string $string) : string
	{
		
		$str = strtolower(preg_replace(
			array('/[^\p{L}0-9_\-\s]/u', '/[ \-\_ª]+/'),
			array('-' /*Remove non-alphanumeric characters*/, '-' /*Remove multiple spaces*/),
			$string
		));
		
		/**
		 * @see http://stackoverflow.com/questions/10444885/php-replace-foreign-characters-in-a-string
		 */
		return preg_replace(
			'/&([A-Za-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);/i',
			'$1', //Remove accents
			htmlentities($str, ENT_QUOTES, 'UTF-8')
		);
	}
	
	public static function endsWith(string $haystack, string $needle) : bool
	{
		if ($needle === '') {
			return true;
		}
		return strcmp(substr($haystack, 0 - strlen($needle)), $needle) === 0;
	}
	
	public static function rTrimString(string $haystack, string $needle) : string
	{
		if (self::endsWith($haystack, $needle)) {
			return substr($haystack, 0, 0 - strlen($needle));
		}
		
		return $haystack;
	}
	
	public static function startsWith(string $haystack, string $needle) : bool
	{
		if (empty($needle)) {
			return true;
		}
		return strpos($haystack, $needle) === 0;
	}
	
	public static function plural(string $string) : string
	{
		if (Strings::endsWith($string, 'y')) {
			return substr($string, 0, -1) .'ies';
		} else {
			return $string . 's';
		}
	}
	
	public static function singular(string $string) : string
	{
		if (Strings::endsWith($string, 'ies')) {
			return substr($string, 0, -3) .'y';
		} elseif (Strings::endsWith($string, 's')) {
			return substr($string, 0, -1);
		} else {
			return $string;
		}
	}
	
	public static function urls(string $str, callable $cb = null) : string
	{
		$urlRegex = '#(https?://[a-zA-z0-9%&?/.\-_=+;@\#]+)#';
		$flip = false;
		
		return Collection::fromArray(preg_split($urlRegex, $str, 0, PREG_SPLIT_DELIM_CAPTURE))->each(function ($e) use (&$flip, $cb) {
			$flip = !$flip;
			if ($flip) {
				#HTML
				return Strings::escape($e);
			}
			else {
				#URL
				return ($cb ?: function ($url) {
					return sprintf('<a href="%s">%s</a>', $url, Strings::escape($url));
				})($e);
			}
		})->join('');
	}
	
	public static function quote(string $str) : string
	{
		return str_replace(['\'', '"'], ['&#039;', '&quot;'], $str);
	}
	
	/**
	 * This method allows your application to safely print HTML to the output buffer
	 * without having to worry about potential HTML injections.
	 *
	 * Please note though, that this method does not protect your application from
	 * executing javascript if the output is used in the wrong location.
	 *
	 * @param string $str
	 * @return string
	 */
	public static function escape($str)
	{
		return htmlspecialchars($str, ENT_HTML5);
	}
	
	/**
	 * Offsets the line by a character. For example, when you're printing text to
	 * HTML you wish it to be indented the same way as the rest of your HTML
	 *
	 * @param string $str
	 * @param int    $times
	 * @param string $character
	 * @return string
	 */
	public static function indent($str, $times = 1, $character = "\t")
	{
		$offset = str_repeat($character, $times);
		return $offset . str_replace(PHP_EOL, PHP_EOL . $offset, $str);
	}
	
	/**
	 * Generates a random base64 URL encoded string that can be used as a unique identifier for
	 * sessions or similar.
	 * 
	 * @param int $length
	 * @return string
	 */
	public static function random(int $length)
	{
		return substr(
			str_replace(
				['+', '/', '='], 
				['-', '_', ''], 
				base64_encode(random_bytes($length))),
			0, 
			$length
		);
	}
}
