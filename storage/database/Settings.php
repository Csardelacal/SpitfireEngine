<?php namespace spitfire\storage\database;

/* 
 * The MIT License
 *
 * Copyright 2017 César de la Cal Bretschneider <cesar@magic3w.com>.
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
 * This class allows to retrieve database settings in a organized manner. This 
 * should make the transfer to environment based database settings much easier.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class Settings
{
	
	private $driver;
	private $server;
	private $user;
	private $password;
	private $schema;
	private $prefix;
	private $encoding;
	
	public function __construct($driver, $server, $user, $password, $schema, $prefix, $encoding) {
		$this->driver = $driver;
		$this->server = $server;
		$this->user = $user;
		$this->password = $password;
		$this->schema = $schema;
		$this->prefix = $prefix;
		$this->encoding = $encoding;
	}
	
	public function getDriver() {
		return $this->driver;
	}
	
	public function getServer() {
		return $this->server;
	}
	
	public function getUser() {
		return $this->user;
	}
	
	public function getPassword() {
		return $this->password;
	}
	
	public function getSchema() {
		return $this->schema;
	}
	
	public function getPrefix() {
		return $this->prefix;
	}
	
	public function getEncoding() {
		return $this->encoding;
	}
		
	/**
	 * Reads the settings from a URL.
	 * 
	 * @param Settings|string $url
	 * @return Settings
	 */
	public static function fromURL($url) {
		if ($url instanceof Settings) {
			return $url;
		}
		
		/**
		 * Extract the data that was provided by the URL. Then we should be able 
		 * to easily retrieve the data it provides.
		 */
		$data   = parse_url($url);
		parse_str($data['query']?? '', $query);
		
		$driver   = $data['scheme']?? 'mysqlpdo';
		$server   = $data['host']?? 'localhost';
		$user     = $data['user']?? 'root';
		$password = $data['password']?? '';
		$schema   = trim($data['path'], '\/')?? 'database';
		$prefix   = $query['prefix']  ?? null;
		$encoding = $query['encoding']?? 'utf8';
		
		return new Settings($driver, $server, $user, $password, $schema, $prefix, $encoding);
	}
	
}
