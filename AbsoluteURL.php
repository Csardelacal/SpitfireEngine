<?php

use spitfire\core\Environment;

class AbsoluteURL extends URL
{
	
	const PROTO_HTTP  = 'http';
	const PROTO_HTTPS = 'https';
	
	private $domain;
	
	private $proto    = self::PROTO_HTTP;
	
	
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
	
	public function getRoutes() {
		#Check whether the domain is a string. 
		#Because if that's the case we don't need it
		if (!is_array($this->domain))          { return parent::getRoutes(); }
		if (null == $r = $this->getReverser()) { return parent::getRoutes(); }
		if (!$r->reverse($this->domain))       { return parent::getRoutes(); }
		
		return $r->getServer()->getRoutes();
	}
	
	public function getReverser() {
		
		$router  = \spitfire\core\router\Router::getInstance();
		$servers = $router->getServers();
		
		foreach ($servers as $s) {
			/*@var $s \spitfire\core\router\Server*/
			
			if ($s->getReverser()->reverse($this->domain)) {
				return $s->getReverser();
			}
		}
		
		return null;
	}

	public function __toString() {
		$rel    = parent::__toString();
		$proto  = $this->proto;
		$domain = is_array($this->domain)? $this->getReverser()->reverse($this->domain) : $this->domain;
		
		return $proto . '://' . $domain . $rel;
	}
}
