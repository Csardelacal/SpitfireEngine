<?php namespace spitfire\model\utils;

use Closure;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty as Property;
use spitfire\collection\Collection;
use spitfire\model\attribute\CharacterString;
use spitfire\model\attribute\Table as TableAttribute;
use spitfire\model\attribute\EnumType;
use spitfire\model\attribute\Id;
use spitfire\model\attribute\InIndex as InIndexAttribute;
use spitfire\model\attribute\Integer as IntAttribute;
use spitfire\model\attribute\LongInteger as LongAttribute;
use spitfire\model\attribute\Primary;
use spitfire\model\attribute\References as ReferencesAttribute;
use spitfire\model\attribute\SoftDelete;
use spitfire\model\attribute\Text;
use spitfire\model\attribute\Timestamps;
use spitfire\model\Model;
use spitfire\storage\database\drivers\TableMigrationExecutorInterface as MigratorInterface;
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
		assert(count($tableAttribute) <= 1);
		
		$layout = new Layout($reflection->getName()::getTableName());
		$migrator = new TableMigrationExecutor($layout);
		
		$this->addColumns($migrator, $reflection);
		$this->addPrimary($migrator, $reflection);
		$this->addIndexes($migrator, $reflection);
		$this->addReferences($migrator, $reflection);
		$this->addId($migrator, $reflection);
		$this->addSoftDeletes($migrator, $reflection);
		$this->addTimestamps($migrator, $reflection);
		
		
		return $layout;
	}
	
	/**
	 * This method allows our application to add columns to our schema.
	 *
	 * @todo This function is way longer than it should be and way more complicated than it
	 * should.
	 */
	private function addColumns(MigratorInterface $target, ReflectionClass $source) : void
	{
		$props = $source->getProperties();
		
		$available = [
			IntAttribute::class => function (Property $prop, MigratorInterface $migrator, IntAttribute $integer) {
				$nullable = $integer->isNullable()?? $prop->getType()->allowsNull();
				$migrator->int($prop->getName(), $integer->isUnsigned(), $nullable);
			},
			LongAttribute::class => function (Property $prop, MigratorInterface $migrator, LongAttribute $integer) {
				$nullable = $integer->isNullable()?? $prop->getType()->allowsNull();
				$migrator->long($prop->getName(), $integer->isUnsigned(), $nullable);
			},
			CharacterString::class => function (Property $prop, MigratorInterface $migrator, CharacterString $string) {
				$nullable = $string->isNullable()?? $prop->getType()->allowsNull();
				$migrator->string($prop->getName(), $string->getLength(), $nullable);
			},
			Text::class => function (Property $prop, MigratorInterface $migrator, Text $string) {
				$nullable = $string->isNullable()?? $prop->getType()->allowsNull();
				$migrator->text($prop->getName(), $nullable);
			},
			EnumType::class => function (Property $prop, MigratorInterface $migrator, EnumType $string) {
				$nullable = $string->isNullable()?? $prop->getType()->allowsNull();
				$migrator->enum($prop->getName(), $string->getOptions(), $nullable);
			}
		];
		
		
		/**
		 *
		 * @template T
		 * @param class-string<T> $classname
		 * @return callable(string,MigratorInterface,T):void $action
		 */
		$transformer = function (string $classname) use ($available) : Closure {
			return $available[$classname];
		};
		
		foreach ($props as $prop) {
			/**
			 * This prevents an application from registering two types to a single
			 * column, which would lead to disaster.
			 */
			$found = false;
			
			foreach (array_keys($available) as /** @var class-string */ $type) {
				/**
				 * Check if the column is of type
				 */
				$columnAttribute = $prop->getAttributes($type);
				
				/**
				 * If the property is not part of a field, we just continue.
				 */
				if (empty($columnAttribute)) {
					continue;
				}
				
				assert(count($columnAttribute) === 1);
				assert($found === false);
				
				$column = $columnAttribute[0]->newInstance();
				assert($column instanceof $type);
				
				$transformer($type)($prop, $target, $column);
				
				$found = true;
			}
		}
	}
	
	/**
	 * This method allows our application to add columns to our schema.
	 */
	private function addIndexes(MigratorInterface $target, ReflectionClass $source) : void
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
		
		foreach ($grouped as $name => /** @var Collection<InIndexAttribute> */$columnAttributes) {
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
	private function addPrimary(MigratorInterface $target, ReflectionClass $source) : void
	{
		$props = $source->getProperties();
		
		foreach ($props as $prop) {
			$columnAttributes = $prop->getAttributes(Primary::class);
			
			if (empty($columnAttributes)) {
				continue;
			}
			
			assert(count($columnAttributes) === 1);
			$target->primary($prop->getName());
		}
	}
	
	/**
	 * This method allows our application to add columns to our schema.
	 */
	private function addReferences(MigratorInterface $target, ReflectionClass $source) : void
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
	
	private function addSoftDeletes(MigratorInterface $migrator, ReflectionClass $reflection)
	{
		$tableAttribute = $reflection->getAttributes(SoftDelete::class);
		
		if (empty($tableAttribute)) {
			return;
		}
		
		assert($reflection->getProperty('removed'));
		$migrator->softDelete();
	}
	
	private function addTimestamps(MigratorInterface $migrator, ReflectionClass $reflection)
	{
		$tableAttribute = $reflection->getAttributes(Timestamps::class);
		
		if (empty($tableAttribute)) {
			return;
		}
		
		assert($reflection->getProperty('created'));
		assert($reflection->getProperty('updated'));
		$migrator->timestamps();
	}
	
	private function addId(MigratorInterface $migrator, ReflectionClass $reflection)
	{
		$tableAttribute = $reflection->getAttributes(Id::class);
		
		if (empty($tableAttribute)) {
			return;
		}
		
		assert($reflection->getProperty('_id'));
		$migrator->id();
	}
}
