<?php namespace spitfire\utils;

use Closure;

trait Mixin
{
	
	private object $mixin;
	
	protected function mixin(object $mixin)
	{
		$this->mixin = $mixin;
	}
	
	public function __call($name, $args)
	{
		$mixin = $this->mixin instanceof Closure? ($this->mixin)() : $this->mixin;
		if (method_exists($mixin, $name)) {
			return $mixin->$name(...$args);
		}
	}
}
