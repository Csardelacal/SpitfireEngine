<?php namespace spitfire\io;

use spitfire\io\session\Session;
use spitfire\exceptions\user\ApplicationException;

class XSSToken
{
	
	private $timeout;
	
	public function __construct($timeout = 600)
	{
		$this->timeout = $timeout;
	}
	
	public function getValue()
	{
		$session = spitfire()->provider()->get(Session::class);
		
		if (false == $xss_token = $session->get('_XSS_')) {
			$xss_token = str_replace(['/', '='], [''], base64_encode(function_exists('random_bytes')? random_bytes(50) : rand()));
			$session->set('_XSS_', $xss_token);
		}
		
		$expires = time() + $this->timeout;
		$salt    = str_replace(['/', '='], [''], base64_encode(random_bytes(20)));
		$hash    = hash('sha512', implode('.', [$expires, $salt, $xss_token]));
		
		return implode(':', [$expires, $salt, $hash]);
	}
	
	public function verify($token)
	{
		$session = spitfire()->provider()->get(Session::class); 
		
		if (false == $xss_token = $session->get('_XSS_')) {
			$xss_token = base64_encode(function_exists('random_bytes')? random_bytes(50) : rand());
			$session->set('_XSS_', $xss_token);
		}
		
		$pieces = explode(':', $token);
		
		if (count($pieces) !== 3) {
			throw new ApplicationException('Malformed XSRF Token', 401);
		}
		
		list($expires, $salt, $hash) = $pieces;
		
		if ($expires < time()) {
			return false;
		}
		
		$check = hash('sha512', implode('.', [$expires, $salt, $xss_token]));
		
		return $hash === $check;
	}
	
	public function __toString()
	{
		return $this->getValue();
	}
}
