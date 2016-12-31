<?php

use spitfire\SpitFire;
use spitfire\core\Environment;

class absoluteURL extends URL
{
	
	const PROTO_HTTP  = 'http';
	const PROTO_HTTPS = 'https';
	
	public $domain;
	
	private $proto;
	
	public function __construct() {
		#This could be written nicer in PHP7 with the splatter operator
		$args = func_get_args();
		call_user_func_array(array('parent', '__construct'), $args);
		
		#Set the defaults
		$this->proto  = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'? self::PROTO_HTTPS : self::PROTO_HTTP;
		$this->domain = Environment::get('server_name')? Environment::get('server_name') : $_SERVER['SERVER_NAME'];
	}
	
	/**
	 * Set the domain name this URL points to. This is intended to address
	 * Spitfire apps that work on a multi-domain environment / subdomains
	 * and require linking to itself on another domain. They are also good 
	 * for sharing / email links where the URL without server name would
	 * be useless.
	 * 
	 * @param string $domain The domain of the URL. I.e. www.google.com
	 * @return absoluteURL
	 */
	public function setDomain($domain) {
		$this->domain = $domain;
		return $this;
	}
	
	public function getDomain() {
		return $this->domain;
	}
	
	public static function current() {
		return new self(get_path_info(), $_GET);
	}
	
	public static function asset($asset_name, $app = null) {
		$path = parent::asset($asset_name, $app);
		
		$proto  = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'? self::PROTO_HTTPS : self::PROTO_HTTP;
		$domain = Environment::get('server_name')? Environment::get('server_name') : $_SERVER['SERVER_NAME'];
		
		return $proto . '://' . $domain . $path;
	}
	
	public static function canonical() {
		
		#Get the relative canonical URI
		$canonical = URL::canonical();
		
		#Prepend protocol and server and return it
		return $canonical->toAbsolute();
	}

	public function __toString() {
		$rel = parent::__toString();
		$proto  = $this->proto;
		$domain = $this->domain;
		
		return $proto . '://' . $domain . $rel;
	}
}
