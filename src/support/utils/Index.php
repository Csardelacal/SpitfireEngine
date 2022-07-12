<?php namespace spitfire\storage\database\support\utils;

use spitfire\storage\database\Field;
use spitfire\storage\database\IndexInterface;

class Index
{
	
	public static function equals(IndexInterface $a, IndexInterface $b) : bool
	{
		
		/**
		 * If the index has become unique/not-unique we also need to report the change.
		 */
		if ($a->isUnique() !== $b->isUnique()) {
			return false;
		}
		
		/**
		 * If the index is a primary key on one side and not the other it's an issue.
		 */
		if ($a->isPrimary() !== $b->isPrimary()) {
			return false;
		}
		
		/**
		 * Compare if the indexes are comprised of the same fields. Please note that we explicitly
		 * do not sort the fields. A change in field order is a change to the index. A considerable
		 * one at that.
		 */
		$_a = $a->getFields()->each(fn(Field $e) : string => $e->getName())->join(':');
		$_b = $b->getFields()->each(fn(Field $e) : string => $e->getName())->join(':');
		
		return $_a !== $_b;
	}
}
