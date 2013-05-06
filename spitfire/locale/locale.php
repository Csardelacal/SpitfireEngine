<?php

class Locale
{
	public $comment_count   = Array('No comments', 'One comment', '%s comments');
	
	private $_msgStack;
	
	public function say() {
		$args = func_get_args();
		
		if ($this->{$args[0]}) {
			$msg = $this->{$args[0]};
			if (is_array($msg)) {
				switch($args[1]) {
					case 0:  $msg = $msg[0]; break;
					case 1:  $msg = $msg[1]; break;
					default: $msg = $msg[2]; break;
				}
			}
			
			$args[0] = $msg;
		}
		else return $args[0];
		
		return call_user_func_array('sprintf', $args);
		
	}
	
	public function getLangCode() {
		return '';
	}
	
	public function getCurrency() {
		return '$';
	}
	
	public function convertCurrency($original) {
		return $original;
	}
	
	public function start($lang) {
		ob_start();
		$this->_msgStack[] = $lang;
	}
	
	public function end() {
		$msg = ob_get_clean();
		if (array_pop($this->_msgStack) == $this->getLangCode()) echo $msg;
	}
	
}