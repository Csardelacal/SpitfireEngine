<?php namespace spitfire\core\http\request\components;

use Psr\Http\Message\ServerRequestInterface;

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
trait HasHTTPMethod
{
	
	/**
	 * The HTTP verb that is used to indicate what the server should do with the data
	 * it receives from the client application. This allows us to specifically route
	 * some things in the application.
	 *
	 * @var string
	 */
	private $method;
	
	/**
	 * Returns the name of the method used to request from this server. Currently we focus
	 * on supporting GET, POST, PUT, DELETE and OPTIONS
	 *
	 * @return string
	 */
	public function getMethod()
	{
		return $this->method;
	}
	
	public function withMethod($method) : ServerRequestInterface
	{
		#For this trait to work properly it needs to be applied to a class that implements
		#the request interface.
		assert($this instanceof ServerRequestInterface);
		
		$copy = clone $this;
		$copy->method = $method;
		
		return $copy;
	}
	
	/**
	 * Whether this request was posted.
	 *
	 * @return bool
	 */
	public function isPost() : bool
	{
		return $this->method === 'POST';
	}
	
	/**
	 * Returns the request method (and the real method). This allows the user to spoof a request
	 * to overcome certain poor PHP implementations.
	 *
	 * @return string
	 */
	public static function methodFromGlobals() : string
	{
		/**
		 * Extract the request method from the server. Please note that we allow
		 * applications to override the request method by sending a payload to the
		 * webserver. This fixes a few behavioral issues that we run into when working
		 * with PHP and REST APIs
		 *
		 * For example, PUT in HTTP and PHP is not intended to parse the request body
		 * like POST would. But in REST, PUT is basically a POST that overwrites data
		 * if it already existed.
		 *
		 * For consistency, this method emulates the behavior of Laravel's mechanism
		 * really closely.
		 */
		$method = strtoupper($_SERVER['REQUEST_METHOD']?? 'GET');
		
		if (self::isMethodOverridden()) {
			$method = $_POST['_method'];
		}
		
		return $method;
	}
	
	private static function isMethodOverridden()
	{
		if (!isset($_POST['_method'])) {
			return false;
		}
		return in_array(strtoupper($_POST['method']), ['GET', 'PUT', 'POST', 'PATCH', 'DELETE']);
	}
}
