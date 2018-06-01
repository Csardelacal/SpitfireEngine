<?php namespace spitfire\exceptions;

use BadMethodCallException;
use Exception;
use spitfire\core\Environment;
use spitfire\core\Response;
use spitfire\io\template\Template;
use Throwable;
use function current_context;
use function spitfire;

/**
 * Silent exception handler.
 * 
 * Whenever an uncaught exception reaches the server it will use this
 * function for "discrete" failure. The function retrieves (depending
 * on the error) a error page and logs the error so it can be  
 * analyzed later.
 * In case there is a failover, and the function fails or cannot
 * find a file to display the error page it will try to handle the error
 * by causing a "white screen of death" to the user adding error information
 * to a HTML comment block. As it is the only failsafe way of communication
 * when there is a DB Error or permission error on the log files.
 * 
 * @param Exception $e
 */

class ExceptionHandler {

	private $msgs     = Array();

	public function __construct() {
		set_exception_handler( Array($this, 'exceptionHandle'));
		register_shutdown_function( Array($this, 'shutdownHook'));
	}
	
	/**
	 * 
	 * @param \Throwable|\Exception $e
	 */
	public function exceptionHandle ($e) {
		if (!$e instanceof Exception && !$e instanceof Throwable) {
			throw new BadMethodCallException('Requires throwable type to work.', 1608011002);
		}
		
		try {
			while(ob_get_clean()); //The content generated till now is not valid. DESTROY. DESTROY!

			$response  = new Response(null);
			$basedir   = spitfire()->getCWD();
			$extension = current_context() && current_context()->request->getPath()? '.' . current_context()->request->getPath()->getFormat() : '';
			
			$template = new Template([
				 "{$basedir}/bin/error_pages/{$e->getCode()}{$extension}.php",
				 "{$basedir}/bin/error_pages/default{$extension}.php",
				 "{$basedir}/bin/error_pages/{$e->getCode()}.php",
				 "{$basedir}/bin/error_pages/default.php"
			]);
			
			if ( $e instanceof PublicException) {
				$response->getHeaders()->status($e->getCode());
			}
			
			$response->setBody($template->render(Environment::get('debug_mode')? [
				'code'    => $e instanceof PublicException? $e->getCode() : 500,
				'message' => $e instanceof PublicException? $e->getMessage() : 'Server error'
			] : [
				'code'      => $e instanceof PublicException? $e->getCode() : 500,
				'message'   => $e->getMessage(),
				'exception' => $e
			]));
			
			$response->send();

		} catch (Exception $e) { //Whatever happens, it won't leave this function
			echo '<!--'.$e->getMessage().'-->';
			ob_flush();
			die();
		}
	}
	
	public function shutdownHook () {
		$last_error = error_get_last();
		
		switch($last_error['type']){
			case E_ERROR:
			case E_CORE_ERROR:
			case E_COMPILE_ERROR:
			case E_USER_ERROR:
			case E_PARSE:
			case E_RECOVERABLE_ERROR:
				while(ob_get_clean()); 
				get_error_page(500, $last_error['message'] . "@$last_error[file] [$last_error[line]]", print_r($last_error, 1) );
		}
		
		while ($ob = ob_get_clean()) echo $ob;
	}

	public function log ($msg) {
		$this->msgs[] = $msg;
	}

	public function getMessages () {
		return $this->msgs;
	}
	
}
