<?php namespace spitfire\core\exceptions;

use Exception;
use spitfire\exceptions\PublicExceptionInterface;

/**
 * A failure exception is a standard exception that is thrown when the application
 * wishes to report a failure to the user. This is a very generic type, and should
 * whenever possible be replaced by more specific issues that allow the application
 * to attempt to resolve the issue or give further details to the end-user.
 * 
 * Spitfire will then use an error page that is appropriate for the issue that
 * was raised. This is selected by the exception code. So a failure with a 500
 * error code will show a system error, while a failure with a 404 code should
 * present the user with a dialog that informs them that the resource can't be
 * located.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class FailureException extends Exception implements PublicExceptionInterface
{
	
	/**
	 * Creates a new failure exception. This indicates that the application can't
	 * continue and needs to be terminated.
	 * 
	 * @param string $message
	 * @param int $code
	 * @param \Throwable $previous
	 */
	public function __construct(string $message = "", int $code = 500, \Throwable $previous = NULL) 
	{
		parent::__construct($message, $code, $previous);
	}
	
	/**
	 * In this kind of exception, the http code is directly correlated with the 
	 * code of the exception.
	 * 
	 * @return int
	 */
	public function httpCode() : int
	{
		return $this->getCode();
	}
}
