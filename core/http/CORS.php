<?php namespace spitfire\core\http;

use spitfire\core\Headers;


/* 
 * The MIT License
 *
 * Copyright 2016 César de la Cal Bretschneider <cesar@magic3w.com>.
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
 * Cross-Origin-Resource-Sharing allows the application to determine whether a 
 * user agent referred from another server can access a resource on this server
 * and which parameters the HTTP request may have.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class CORS 
{
	
	/**
	 * This object is the request being manipulated by the CORS object in question.
	 * This object has only setters, and therefore should provide a consistent
	 * method chaining API
	 *
	 * @var Headers
	 */
	private $headers;
	
	/**
	 * Initialize the CORS manipulator. Please note that the CORS object only provides
	 * functions to write to a header, this object cannot read the data it wrote 
	 * back from the request.
	 * 
	 * @param Headers $headers
	 */
	public function __construct(Headers$headers) {
		$this->headers = $headers;
	}
	
	/**
	 * Sets the origin the request can be sent from. This should usually be one of the
	 * following values:
	 * 
	 * A: True, for request can be performed from any origin.
	 * B: False, indicating that CORS is not supported for this endpoint.
	 * C: A string with the origin that accepts this, this will usually be $_SERVER['HTTP_REFERER'] server
	 * 
	 * @param bool|string $origin
	 * @return CORS
	 */
	public function origin($origin) {
		if ($origin === true)      { $this->headers->set('Access-Control-Allow-Origin', '*'); }
		elseif ($origin === false) { $this->headers->set('Access-Control-Allow-Origin', 'null'); }
		else                       { $this->headers->set('Access-Control-Allow-Origin', $origin); }
		
		return $this;
	}
	
	/**
	 * Accepts a list of headers that can be passed by the client to the endpoint being
	 * invoked. If the developer passes true, the system will accept any header
	 * via a CORS request.
	 * 
	 * @param string[]|true $headers
	 * @return CORS
	 */
	public function headers($headers) {
		
		if ($headers === true) { $this->headers->set('Access-Control-Allow-Headers', '*'); } 
		else { $this->headers->set('Access-Control-Allow-Headers', collect($headers)->join(', ')); }
		
		return $this;
	}
	
	/**
	 * Allows the application to decide whether CORS requests should be allowed 
	 * to send credentials. The header does not support any configuration, it's
	 * either true or not.
	 * 
	 * @param bool $allow
	 */
	public function credentials($allow = true) {
		if ($allow) { $this->headers->set('Access-Control-Allow-Credentials', 'true'); }
		else        { $this->headers->unset('Access-Control-Allow-Credentials'); }
	}
	
}

