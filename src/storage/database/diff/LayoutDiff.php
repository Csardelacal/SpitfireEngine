<?php namespace spitfire\storage\database\diff;

class LayoutDiff
{
	
	private ToBeAdded $left;
	private ToBeAdded $right;
	
	public function __construct(ToBeAdded $left, ToBeAdded $right)
	{
		$this->left = $left;
		$this->right = $right;
	}
	
	public function left() : ToBeAdded
	{
		return $this->left;
	}
	
	public function right() : ToBeAdded
	{
		return $this->right;
	}
}
