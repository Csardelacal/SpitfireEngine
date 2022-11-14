<?php namespace spitfire\utils;

use Closure;

trait Mixin
{
	
	private object $mixin;
	
	/**
	 *
	 * @param object|Closure $mixin
	 * @return void
	 */
	protected function mixin(object $mixin) : void
	{
		$this->mixin = $mixin;
	}
	
	/**
	 * 
	 * @param string $name
	 * @param mixed[] $args
	 * @return mixed
	 */
	public function __call(string $name, array $args)
	{
		$mixin = $this->mixin instanceof Closure? ($this->mixin)() : $this->mixin;
		if (method_exists($mixin, $name)) {
			return $mixin->$name(...$args);
		}
	}
}
