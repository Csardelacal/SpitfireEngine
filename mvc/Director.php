<?php

use spitfire\core\ContextCLI;
use spitfire\mvc\MVC;

abstract class Director extends MVC
{
	
	
	public function __construct(ContextCLI$intent) {
		parent::__construct($intent);
		$this->call = new _SF_Invoke();
	}
	
}