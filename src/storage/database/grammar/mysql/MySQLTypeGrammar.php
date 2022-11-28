<?php namespace spitfire\storage\database\grammar\mysql;

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
 * This class provides translations between the names of the types in Spitfire
 * and those in MySQL.
 */
class MySQLTypeGrammar
{
	
	const KEYWORD_UNSIGNED = 'unsigned';
	
	public static function int(?string $unsigned = null) : string
	{
		return $unsigned === self::KEYWORD_UNSIGNED? 'INT UNSIGNED' : 'INT';
	}
	
	public static function long(?string $unsigned = null) : string
	{
		return $unsigned === self::KEYWORD_UNSIGNED? 'BIGINT UNSIGNED' : 'BIGINT';
	}
	
	public static function float(?string $unsigned = null) : string
	{
		return $unsigned === self::KEYWORD_UNSIGNED? 'FLOAT UNSIGNED' : 'FLOAT';
	}
	
	public static function double(?string $unsigned = null) : string
	{
		return $unsigned === self::KEYWORD_UNSIGNED? 'DOUBLE UNSIGNED' : 'DOUBLE';
	}
	
	public static function bool() : string
	{
		return 'TINYINT UNSIGNED';
	}
	
	/**
	 * 
	 * @param numeric-string $size
	 */
	public static function string(string $size = '255') : string
	{
		assert(intval($size) > 0);
		return sprintf('VARCHAR(%d)', $size);
	}
	
	public static function text() : string
	{
		return 'TEXT';
	}
	
	public static function enum(string $types) : string
	{
		return sprintf('ENUM(%d)', $types);
	}
	
	public static function blob() : string
	{
		return 'BLOB';
	}
}
