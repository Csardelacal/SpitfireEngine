<?php namespace spitfire\storage\database;

use magic3w\http\url\reflection\URLReflection;

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
	
	/**
	 *
	 * @var string
	 */
	private $driver;
	
	/**
	 *
	 * @var string
	 */
	private $server;
	
	/**
	 *
	 * @var int
	 */
	private $port;
	
	/**
	 *
	 * @var string
	 */
	private $user;
	
	/**
	 *
	 * @var string
	 */
	private $password;
	
	/**
	 *
	 * @var string
	 */
	private $schema;
	
	/**
	 *
	 * @var string
	 */
	private $prefix;
	
	/**
	 *
	 * @var string
	 */
	private $encoding;
	
	/**
	 *
	 * @var array{driver: string, server: string, port: int, user: string, password: string, schema: string, prefix: string, encoding: string}
	 */
	private static $defaults = [
		'driver'   => 'mysqlpdo',
		'server'   => 'localhost',
		'port'     => 3306,
		'user'     => 'root',
		'password' => '',
		'schema'   => 'database',
		'prefix'   => '',
		'encoding' => 'utf8'
	];
	
	public function __construct(string $driver, string $server, int $port, string $user, string $password, string $schema, string $prefix, string $encoding)
	{
		$this->driver = $driver;
		$this->server = $server;
		$this->port   = $port;
		$this->user = $user;
		$this->password = $password;
		$this->schema = $schema;
		$this->prefix = $prefix;
		$this->encoding = $encoding;
	}
	
	public function getDriver() : string
	{
		return $this->driver;
	}
	
	public function getServer() : string
	{
		return $this->server;
	}
	
	public function getUser() : string
	{
		return $this->user;
	}
	
	public function getPassword() : string
	{
		return $this->password;
	}
	
	public function getSchema() : string
	{
		return $this->schema;
	}
	
	public function getPrefix() : string
	{
		return $this->prefix;
	}
	
	public function getEncoding() : string
	{
		return $this->encoding;
	}
	
	public function getPort() : int
	{
		return $this->port;
	}
	
	public function setDriver(string $driver) : Settings
	{
		$this->driver = $driver;
		return $this;
	}
	
	public function setServer(string $server) : Settings
	{
		$this->server = $server;
		return $this;
	}
	
	public function setPort(int $port) : Settings
	{
		$this->port = $port;
		return $this;
	}
	
	public function setUser(string $user) : Settings
	{
		$this->user = $user;
		return $this;
	}
	
	public function setPassword(string $password) : Settings
	{
		$this->password = $password;
		return $this;
	}
	
	public function setSchema(string $schema) : Settings
	{
		$this->schema = $schema;
		return $this;
	}
	
	public function setPrefix(string $prefix) : Settings
	{
		$this->prefix = $prefix;
		return $this;
	}
	
	public function setEncoding(string $encoding) : Settings
	{
		$this->encoding = $encoding;
		return $this;
	}
	
	/**
	 * Reads the settings from a URL. Since October 2017 we're focusing on providing
	 * URLs to store database credentials, which allow in turn to store the DB
	 * settings outside of the application and on the server itself.
	 *
	 * @param string $url
	 * @return Settings
	 */
	public static function fromURL(string $url) : Settings
	{
		
		/**
		 * Reflect the URL we received.
		 */
		$reflection = URLReflection::fromURL($url);
		
		return new Settings(
			$reflection->getScheme()?: self::$defaults['driver'],
			$reflection->getHost()?: self::$defaults['server'],
			$reflection->getPort()?: self::$defaults['port'],
			$reflection->getUser()?: self::$defaults['user'],
			$reflection->getPassword()?: self::$defaults['password'],
			substr($reflection->getPath(), 1)?: self::$defaults['schema'],
			$reflection->getQueryData()['prefix']?? self::$defaults['prefix'],
			$reflection->getQueryData()['encoding']?? self::$defaults['encoding']
		);
	}
	
	/**
	 *
	 * @param array{driver: string, server: string, port: int, user: string, password: string, schema: string, prefix: string, encoding: string} $arr
	 */
	public static function fromArray(array $arr) : Settings
	{
		$ops = $arr + self::$defaults;
		
		return new Settings(
			$ops['driver'],
			$ops['server'],
			$ops['port'],
			$ops['user'],
			$ops['password'],
			$ops['schema'],
			$ops['prefix'],
			$ops['encoding']
		);
	}
}
