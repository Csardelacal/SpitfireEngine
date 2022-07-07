<?php namespace spitfire\model\utils;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;
use spitfire\collection\Collection;
use spitfire\model\attribute\Table as TableAttribute;
use spitfire\model\attribute\Column as ColumnAttribute;
use spitfire\model\attribute\InIndex as InIndexAttribute;
use spitfire\model\attribute\References as ReferencesAttribute;
use spitfire\model\Model;
use spitfire\storage\database\ForeignKey;
use spitfire\storage\database\identifiers\FieldIdentifier;
use spitfire\storage\database\Index;
use spitfire\storage\database\Layout;
use spitfire\storage\database\LayoutInterface;

class AttributeLayoutGenerator
{
	
	public function __construct()
	{
	}
	
	public function make(ReflectionClass $reflection) : LayoutInterface
	{
		assert($reflection->isSubclassOf(Model::class));
		
		$tableAttribute = $reflection->getAttributes(TableAttribute::class);
		assert(count($tableAttribute) === 1);
		
		$layout = new Layout($tableAttribute[0]->newInstance()->getName());
		$this->addColumns($layout, $reflection);
		$this->addIndexes($layout, $reflection);
		$this->addReferences($layout, $reflection);
		
		#TODO : Add timestamps
		#TODO : Add soft-deletes
		
		return $layout;
	}
	
	/**
	 * This method allows our application to add columns to our schema.
	 */
	private function addColumns(Layout $target, ReflectionClass $source) : void
	{
		$props = $source->getProperties();
		
		foreach ($props as $prop) {
			$columnAttribute = $prop->getAttributes(ColumnAttribute::class);
			assert(count($columnAttribute) === 1);
			
			/**
			 * @var ColumnAttribute
			 */
			$column = $columnAttribute[0]->newInstance();
			
			$target->putField(
				$prop->getName(), #Note that the system uses the property name here
				$column->getType(),
				$column->isNullable(),
				$column->isAutoincrement()
			);
		}
	}
	
	/**
	 * This method allows our application to add columns to our schema.
	 */
	private function addIndexes(Layout $target, ReflectionClass $source) : void
	{
		$props = $source->getProperties();
		
		/**
		 *
		 * @var Collection<InIndexAttribute>
		 */
		$attributes = new Collection();
		
		foreach ($props as $prop) {
			$columnAttributes = (new Collection($prop->getAttributes(InIndexAttribute::class)))
				->each(fn(ReflectionAttribute $ref) => $ref->newInstance()->withContext($prop->getName()));
			
			$attributes->add($columnAttributes);
		}
		
		$grouped = $attributes->groupBy(fn(InIndexAttribute $e) => $e->getName());
		
		foreach ($grouped as $name => $columnAttributes) {
			$columns = $columnAttributes
				->sort(fn(InIndexAttribute $a, InIndexAttribute $b) => $a->getPriority() <=> $b->getPriority())
				->each(fn(InIndexAttribute $e) => $target->getField($e->getContext()));
			
			$target->putIndex(new Index(
				$name,
				$columns,
				false,
				false
			));
		}
	}
	
	/**
	 * This method allows our application to add columns to our schema.
	 */
	private function addReferences(Layout $target, ReflectionClass $source) : void
	{
		$props = $source->getProperties();
		
		foreach ($props as $prop) {
			$referencesAttribute = $prop->getAttributes(ReferencesAttribute::class);
			
			if (empty($referencesAttribute)) {
				continue;
			}
			
			assert(count($referencesAttribute) === 1);
			
			/**
			 * @var ReferencesAttribute
			 */
			$reference = $referencesAttribute[0]->newInstance();
			
			$target->putIndex(new ForeignKey(
				sprintf('fk_%s', $prop->getName()),
				$target->getField($prop->getName()),
				new FieldIdentifier([$reference->getTable(), $reference->getColumn()])
			));
		}
	}
}
