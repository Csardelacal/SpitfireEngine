<?php namespace spitfire\model\utils;

use ReflectionAttribute;
use ReflectionClass;
use spitfire\collection\Collection;
use spitfire\model\attribute\CharacterString;
use spitfire\model\attribute\Table as TableAttribute;
use spitfire\model\attribute\Column as ColumnAttribute;
use spitfire\model\attribute\Enum;
use spitfire\model\attribute\InIndex as InIndexAttribute;
use spitfire\model\attribute\Integer;
use spitfire\model\attribute\Primary;
use spitfire\model\attribute\References as ReferencesAttribute;
use spitfire\model\attribute\SoftDelete;
use spitfire\model\attribute\Text;
use spitfire\model\attribute\Timestamps;
use spitfire\model\Model;
use spitfire\storage\database\drivers\TableMigrationExecutorInterface;
use spitfire\storage\database\ForeignKey;
use spitfire\storage\database\identifiers\FieldIdentifier;
use spitfire\storage\database\Index;
use spitfire\storage\database\Layout;
use spitfire\storage\database\LayoutInterface;
use spitfire\storage\database\migration\schemaState\TableMigrationExecutor;

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
		$migrator = new TableMigrationExecutor($layout);
		
		$this->addColumns($migrator, $reflection);
		$this->addPrimary($migrator, $reflection);
		$this->addIndexes($migrator, $reflection);
		$this->addReferences($migrator, $reflection);
		#TODO : Add ID fields
		$this->addSoftDeletes($migrator, $reflection);
		$this->addTimestamps($migrator, $reflection);
		
		
		return $layout;
	}
	
	/**
	 * This method allows our application to add columns to our schema.
	 */
	private function addColumns(TableMigrationExecutorInterface $target, ReflectionClass $source) : void
	{
		$props = $source->getProperties();
		
		$available = [
			Integer::class => function (string $name, TableMigrationExecutorInterface $migrator, Integer $integer) {
				$migrator->int($name, $integer->isUnsigned());
			},
			CharacterString::class => function (string $name, TableMigrationExecutorInterface $migrator, CharacterString $string) {
				$migrator->string($name, $string->getLength());
			},
			Text::class => function (string $name, TableMigrationExecutorInterface $migrator, Text $string) {
				$migrator->text($name);
			},
			Enum::class => function (string $name, TableMigrationExecutorInterface $migrator, Enum $string) {
				$migrator->enum($name, $string->getOptions());
			}
		];
		
		foreach ($props as $prop) {
			/**
			 * This prevents an application from registering two types to a single
			 * column, which would lead to disaster.
			 */
			$found = false;
			
			foreach ($available as $type => $action) {
				/**
				 * Check if the column is of type
				 */
				$columnAttribute = $prop->getAttributes($type);
				
				/**
				 * If the property is not part of a field, we just continue.
				 */
				if (!$columnAttribute) {
					continue;
				}
				
				assert(count($columnAttribute) === 1);
				assert($found === false);
				
				/**
				 * @var ColumnAttribute
				 */
				$column = $columnAttribute[0]->newInstance();
				
				$action(
					$prop->getName(), #Note that the system uses the property name here
					$target,
					$column
				);
			}
		}
	}
	
	/**
	 * This method allows our application to add columns to our schema.
	 */
	private function addIndexes(TableMigrationExecutorInterface $target, ReflectionClass $source) : void
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
				->each(fn(InIndexAttribute $e) => $e->getContext());
			
			$target->index(
				$name,
				$columns->toArray()
			);
		}
	}
	
	
	/**
	 * This method allows our application to add columns to our schema.
	 */
	private function addPrimary(TableMigrationExecutorInterface $target, ReflectionClass $source) : void
	{
		$props = $source->getProperties();
		
		foreach ($props as $prop) {
			$columnAttributes = $prop->getAttributes(Primary::class);
			
			if (empty($columnAttributes)) {
				continue;
			}
			
			assert(count($columnAttributes) === 1);
			$target->primary('PRIMARY', $prop->getName());
		}
	}
	
	/**
	 * This method allows our application to add columns to our schema.
	 */
	private function addReferences(TableMigrationExecutorInterface $target, ReflectionClass $source) : void
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
			
			/**
			 * This last parameter is a bit more delicate, since the DBAL requires the system to provide a layout from
			 * which to extract metadata to build a compatible field. Please note that this requires the application
			 * to have the layout metadata in the class it's referencing to be able to perform any usable work.
			 */
			$layout = (new AttributeLayoutGenerator())->make(new ReflectionClass($reference->getModel()));
			
			/**
			 * Add the foreign key to the layout.
			 */
			$target->foreign(
				$prop->getName(),
				new TableMigrationExecutor($layout)
			);
		}
	}
	
	private function addSoftDeletes(TableMigrationExecutorInterface $migrator, ReflectionClass $reflection)
	{
		$tableAttribute = $reflection->getAttributes(SoftDelete::class);
		
		if (empty($tableAttribute)) {
			return;
		}
		
		assert($reflection->getProperty('removed'));
		$migrator->softDelete();
	}
	
	private function addTimestamps(TableMigrationExecutorInterface $migrator, ReflectionClass $reflection)
	{
		$tableAttribute = $reflection->getAttributes(Timestamps::class);
		
		if (empty($tableAttribute)) {
			return;
		}
		
		assert($reflection->getProperty('created'));
		assert($reflection->getProperty('updated'));
		$migrator->timestamps();
	}
}
