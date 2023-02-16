<?php namespace spitfire\model\utils;

/*
 *
 * Copyright (C) 2023-2023 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-13 01  USA
 *
 */


use ReflectionAttribute;
use ReflectionClass;
use spitfire\collection\Collection;
use spitfire\model\attribute\Table as TableAttribute;
use spitfire\model\attribute\Id;
use spitfire\model\attribute\InIndex as InIndexAttribute;
use spitfire\model\attribute\Primary;
use spitfire\model\attribute\References as ReferencesAttribute;
use spitfire\model\attribute\SoftDelete;
use spitfire\model\attribute\Timestamps;
use spitfire\model\Model;
use spitfire\model\ReflectionModel;
use spitfire\storage\database\drivers\TableMigrationExecutorInterface as MigratorInterface;
use spitfire\storage\database\Layout;
use spitfire\storage\database\LayoutInterface;
use spitfire\storage\database\migration\schemaState\TableMigrationExecutor;

class AttributeLayoutGenerator
{
	
	public function __construct()
	{
	}
	
	/**
	 *
	 * @param ReflectionClass<Model> $reflection
	 * @return LayoutInterface
	 */
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
	 *
	 * @param MigratorInterface $target
	 * @param ReflectionClass<Model> $source
	 * @return void
	 */
	private function addColumns(MigratorInterface $target, ReflectionClass $source) : void
	{
		
		$props = (new ReflectionModel($source->getName()))->getFields();
		
		foreach ($props as $prop) {
			$prop->migrate($target);
		}
	}
	
	/**
	 * This method allows our application to add columns to our schema.
	 *
	 * @param MigratorInterface $target
	 * @param ReflectionClass<Model> $source
	 * @return void
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
			$columnAttributes = (Collection::fromArray($prop->getAttributes(InIndexAttribute::class)))
				->each(fn(ReflectionAttribute $ref) : InIndexAttribute => $ref->newInstance()->withContext($prop->getName()));
			
			$attributes->add($columnAttributes);
		}
		
		$grouped = $attributes->groupBy(fn(InIndexAttribute $e) => $e->getName());
		
		foreach ($grouped as $name => /** @var Collection<InIndexAttribute> */$columnAttributes) {
			$columns = $columnAttributes
				->sort(fn(InIndexAttribute $a, InIndexAttribute $b) => $a->getPriority() <=> $b->getPriority())
				->each(fn(InIndexAttribute $e) => $e->getContext());
			
			assert(is_string($name));
			assert(!is_numeric($name));
			
			$target->index(
				$name,
				$columns->toArray()
			);
		}
	}
	
	
	/**
	 * This method allows our application to add columns to our schema.
	 *
	 * @param MigratorInterface $target
	 * @param ReflectionClass<Model> $source
	 * @return void
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
	 *
	 * @param MigratorInterface $target
	 * @param ReflectionClass<Model> $source
	 * @return void
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
	
	/**
	 *
	 * @param MigratorInterface $migrator
	 * @param ReflectionClass<Model> $reflection
	 * @return void
	 */
	private function addSoftDeletes(MigratorInterface $migrator, ReflectionClass $reflection)
	{
		$tableAttribute = $reflection->getAttributes(SoftDelete::class);
		
		if (empty($tableAttribute)) {
			return;
		}
		
		assert($reflection->getProperty('removed'));
		$migrator->softDelete();
	}
	
	/**
	 *
	 * @param MigratorInterface $migrator
	 * @param ReflectionClass<Model> $reflection
	 * @return void
	 */
	private function addTimestamps(MigratorInterface $migrator, ReflectionClass $reflection)
	{
		$tableAttribute = $reflection->getAttributes(Timestamps::class);
		
		if (empty($tableAttribute)) {
			return;
		}
		
		assert($reflection->hasProperty('created'));
		assert($reflection->hasProperty('updated'));
		$migrator->timestamps();
	}
	
	/**
	 *
	 * @param MigratorInterface $migrator
	 * @param ReflectionClass<Model> $reflection
	 * @return void
	 */
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
