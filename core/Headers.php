<?php namespace spitfire\core;

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

use InvalidArgumentException;
use spitfire\core\http\CORS;

/**
 * The headers file allows an application to manipulate the headers of the response
 * before sending them to the client.
 *
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class Headers
{
	
	private int $status = 200;
	private string $reasonPhrase = self::STATES[200];
	
	/**
	 * A two-dimensional array of strings that represents the headers the system sends
	 * in response to a request.
	 * 
	 * @var array<string,string[]>
	 */
	private array $headers = array(
		'content-type' => ['text/html;charset=utf-8'],
		'x-powered-by' => ['Spitfire'],
		'x-version'    => ['0.1 Beta']
	);
	
	const STATES = array(
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		204 => 'No content',
		206 => 'Partial Content',
		301 => 'Moved Permanently',
		302 => 'Found',
		304 => 'Not modified',
		400 => 'Invalid request',
		401 => 'Unauthorized',
		403 => 'Forbidden',
		404 => 'Not Found',
		418 => 'Im a teapot',
		419 => 'Page expired',
		429 => 'Too many requests',
		451 => 'Unavailable for legal reasons',
		410 => 'Gone',
		416 => 'Range not satisfiable',
		500 => 'Server Error',
		501 => 'Not implemented',
		503 => 'Service Unavailable'
	);
	
	/**
	 * 
	 * @param string $header
	 * @param string[] $value
	 * @return self
	 */
	public function set(string $header, array $value) : self
	{
		$this->headers[strtolower($header)] = $value;
		return $this;
	}
	
	/**
	 * 
	 * @param string $header
	 * @param string $value
	 * @return self
	 */
	public function replace(string $header, string $value) : self
	{
		$this->headers[strtolower($header)] = [$value];
		return $this;
	}
	
	/**
	 * 
	 * @param string $header
	 * @param string $value
	 * @return self
	 */
	public function addTo(string $header, string $value) : self
	{
		$this->headers[strtolower($header)] = array_merge($this->headers[$header]?? [], [$value]);
		return $this;
	}
	
	/**
	 * 
	 * @param string $header
	 * @param string[] $value
	 * @return self
	 */
	public function append(string $header, array $value) : self
	{
		$this->headers[strtolower($header)] = array_merge($this->headers[$header]?? [], $value);
		return $this;
	}
	
	/**
	 * Allows the application to remove a header from the current response. While
	 * this is usually not the case (applications shouldn't be fighting internally)
	 * it might be the case that your application needs to unset a header that
	 * was set earlier.
	 *
	 * @param string $header
	 * @return $this
	 */
	public function unset(string $header)
	{
		$header = strtolower($header);
		
		if (array_key_exists($header, $this->headers)) {
			unset($this->headers[$header]);
		}
		return $this;
	}
	
	/**
	 *
	 * @return string[]
	 */
	public function get(string $header) : array
	{
		return $this->headers[strtolower($header)]?? [];
	}
	
	/**
	 *
	 * @return string[][]
	 */
	public function all() : array
	{
		return $this->headers;
	}
	
	/**
	 * Send the headers to the client. Once the headers have been sent, the application
	 * can no longer manipulate the headers. This method must be called before any
	 * output is sent to the browser.
	 *
	 * Usually Spitfire will buffer all the output, so this should usually not be
	 * an issue.
	 */
	public function send() : void
	{
		http_response_code((int)$this->status);
		
		foreach ($this->headers as $header => $values) {
			$first = true;
			
			foreach ($values as $value) {
				header(sprintf("%s: %s", $header, $value), $first);
				$first = false;
			}
		}
	}
	
	/**
	 * This method allows the application to define the content type it wishes to
	 * send with the response. It prevents the user from having to define the
	 * headers manually and automatically sets an adequate charset depending on
	 * the app's settings.
	 *
	 * @param string $str
	 * @param string|null $encoding
	 */
	public function contentType($str, string $encoding = null) : void
	{
		
		if ($encoding === null) {
			$encoding = config('app.http.encoding', 'utf-8');
		}
		
		switch ($str) {
			case 'php':
			case 'html':
				$this->replace('content-type', 'text/html;charset=' . $encoding);
				break;
			case 'xml':
				$this->replace('content-type', 'application/xml;charset=' . $encoding);
				break;
			case 'json':
				$this->replace('content-type', 'application/json;charset=' . $encoding);
				break;
			default:
				$this->replace('content-type', $str);
		}
	}
	
	/**
	 * Manipulate these Header's CORS options. This returns a CORS object that can
	 * be used to define how applications running on clients on a different origin
	 * are allowed to interact with the resources on this server.
	 *
	 * @return CORS
	 */
	public function cors()
	{
		return new CORS($this);
	}
	
	/**
	 * @throws InvalidArgumentException
	 * @return void
	 */
	public function status(int $code = 200, string $reasonPhrase = '') : void
	{
		if (!isset(self::STATES[$code])) {
			throw new InvalidArgumentException(sprintf('Status code %s is not valid', $code));
		}
		
		$this->status = $code;
		$this->reasonPhrase = $reasonPhrase? $reasonPhrase : self::STATES[$code];
	}
	
	public function getStatus() : int
	{
		return $this->status;
	}
	
	public function getReasonPhrase() : string
	{
		return $this->reasonPhrase;
	}
	
	/**
	 * Converts these headers into a redirect to a new location.
	 * 
	 * @throws InvalidArgumentException
	 */
	public function redirect(string $location, int $status = 302) : void
	{
		$this->status($status);
		$this->replace('location', $location);
		$this->replace('expires', date("r", time()));
		$this->replace('cache-control', 'no-cache, must-revalidate');
	}
	
	public static function fromGlobals() : Headers
	{
		
		$headers = new Headers();
		$received = getallheaders();
		
		foreach (array_change_key_case($received, CASE_LOWER) as $header => $content) {
			assert(is_string($content));
			$headers->replace($header, $content);
		}
		
		return $headers;
	}
}
