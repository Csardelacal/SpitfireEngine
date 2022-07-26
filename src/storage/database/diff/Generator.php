<?php namespace spitfire\storage\database\diff;

use spitfire\storage\database\Field;
use spitfire\storage\database\LayoutInterface;
use spitfire\storage\database\support\utils\Index as IndexUtils;

class Generator
{
	
	private LayoutInterface $left;
	private LayoutInterface $right;
	
	public function __construct(LayoutInterface $left, LayoutInterface $right)
	{
		$this->left = $left;
		$this->right = $right;
	}
	
	public function make() : LayoutDiff
	{
		return new LayoutDiff(
			$this->toBeAdded($this->left, $this->right),
			$this->toBeAdded($this->right, $this->left)
		);
	}
	
	public function toBeAdded(LayoutInterface $base, LayoutInterface $projection) : ToBeAdded
	{
		$_result = new ToBeAdded();
		
		foreach ($projection->getFields() as $field) {
			if (!$base->hasField($field->getName())) {
				$_result->addField($field);
			}
			elseif ($field->getNullable() !== $base->getField($field->getName())->getNullable()) {
				$_result->addField($field);
			}
			elseif ($field->getType() !== $base->getField($field->getName())->getType()) {
				$_result->addField($field);
			}
		}
		
		foreach ($projection->getIndexes() as $index) {
			
			/**
			 * If the base doesn't contain the index with said layout, the application must assume it
			 * is to be added.
			 */
			if (!$base->hasIndex($index->getName())) {
				$_result->addIndex($index);
			}
			/**
			 * In case the indexes are not identical, we need to make sure that we add them to the
			 * list of changes.
			 */
			elseif (!IndexUtils::equals($index, $base->getIndex($index->getName()))) {
				$_result->addIndex($index);
			}
		}
		
		return $_result;
	}
}
