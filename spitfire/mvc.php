<?php

/**
 * This class will autoinclude functions the application needs to run and return
 * the result
 */
class _SF_Invoke
{
	public function __call($function_name, $arguments) {
		if ( !is_callable($function_name) ) {
			$file = 'bin/functions/'.$function_name.'.php';
			if (file_exists($file)) include $file;
			else throw new privateException('Undefined function: '. $function_name);
		}
		
		return call_user_func_array($function_name, $arguments);
		
	}
}
