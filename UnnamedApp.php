<?php namespace spitfire;

class UnnamedApp extends App
{
	
	public function directory(): string {
		return basedir();
	}

	public function enable() {
		#Do nothing, by default, this app will initialize with a default state
	}

	public function namespace() {
		return '\\';
	}

}