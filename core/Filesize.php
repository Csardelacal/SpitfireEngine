<?php

namespace spitfire\core;

use spitfire\exceptions\PrivateException;

class Filesize {
	/** @var int */
	public $bytes;

	/**
	 * Creates the formatter with the passed value
	 *
	 * @param int $value
	 */
	function __construct($value){
		$this->bytes = $value;
	}

	/**
	 * Parses the size provides as a string and returns a Filesize instance or throws on failure
	 * Yes, it parses Bb as byte.
	 *
	 * @param string $str
	 *
	 * @return Filesize
	 * @throws PrivateException
	 */
	static function parse($str){
		if (!preg_match('~^(\d+)\s*([TGMkB])b?$~i', $str, $matches))
			throw new PrivateException("Unable to parse file size ($str)");
		$unit = $matches[2];
		$value = intval($matches[1], 10);
		switch(strtoupper($unit)){
			case 'T':
				$value *= 1024;
			case 'G':
				$value *= 1024;
			case 'M':
				$value *= 1024;
			case 'K':
				$value *= 1024;
			break;
		}
		return new Filesize($value);
	}

	const UNITS = 'KMGT';

	function __toString(){
		$bytes = $this->bytes;
		for ($i = -1; $bytes > 1024; $i++, $bytes /= 1024);
		return "$bytes ".($i!==-1?self::UNITS[$i]:'').'B';
	}
}
