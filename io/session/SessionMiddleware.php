<?php namespace spitfire\io\session;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SessionHandlerInterface;
use spitfire\utils\Strings;

abstract class SessionMiddleware implements MiddlewareInterface
{
	
	private SessionHandlerInterface $handler;
	private string $name;
	
	public function __construct(SessionHandlerInterface $handler, string $name = null)
	{
		$this->handler = $handler;
		$this->name    = $name?: session_name();
	}
	
	/**
	 * 
	 * @todo Extraact the cookie negotiating logic to aa different component / interface.
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		/**
		 * We need access to the cookies for the request so we can locate 
		 * the appropriate session data for the request.
		 */
		$cookies = $request->getCookieParams();
		
		/**
		 * The browser has a session cookie. This might not be valid, but it indicates
		 * that the browser at least is attempting to recover a session.
		 */
		if (isset($cookies[$this->name])) {
			/**
			 * 
			 */
			$sid     = $cookies[$this->name];
			$session = new Session($this->handler, $sid);
			$session->load();
		}
		else {
			$sid     = Strings::random(40);
			$session = new Session($this->handler, $sid);
		}
		
		/**
		 * Pass the request to the underlying request handler
		 */
		$_ret = $handler->handle($request);
		
		if ($session->isStarted()) {
			/**
			 * @todo Future revisions should write using a user controllable mechanism so the session
			 * handler can determine how to send the information to the useer agent
			 */
			/*
			* This is a fallback mechanism that allows dynamic extension of sessions,
			* otherwise a twenty minute session would end after 20 minutes even
			* if the user was actively using it.
			*
			* Sessions are httponly, this means that they are not available to the client
			* application running within the user-agent. This should mititgate potential
			* XSS attacks that would use JS to extract the cookie to impersonate the user.
			*
			* Read on: http://php.net/manual/en/function.session-set-cookie-params.php
			*/
			$lifetime = $session->isDestroyed()? -1 : config('session.ttl', 2592000);
			
			setcookie(
				$this->name,
				$session->getId(),
				[
					'expires' => time() + $lifetime, 
					'path' => '/', 'samesite' => 'lax', 'secure' => true, 'httponly' => true]
			);
		}
		
		return $_ret;
	}
}
