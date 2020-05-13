<?php namespace spitfire\mvc;

use spitfire\mvc\MVC;

class ViewElement extends \spitfire\io\template\Template
{
	private $file;
	private $data;
	
	public function __construct($file, $data = []) {
		parent::__construct($file);
		$this->data = $data;
	}
	
	public function set ($key, $value) {
		$this->data[$key] = $value;
		return $this;
	}
	
	public function render ($__data) {
		echo '<!-- Started: ' . $this->file .' -->' . PHP_EOL;
		return parent::render(array_merge($this->data, $__data));
	}
	
	public function __toString() {
		return $this->render($this->data);
	}
}