<?php namespace spitfire\model\directors;
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


use ReflectionClass;
use spitfire\collection\Collection;
use spitfire\collection\TypedCollection;
use spitfire\exceptions\ApplicationException;
use spitfire\exceptions\user\NotFoundException;
use spitfire\model\Model;
use spitfire\model\utils\AttributeLayoutGenerator;
use spitfire\storage\database\diff\Generator;
use spitfire\storage\database\LayoutInterface;
use spitfire\storage\database\migration\schemaState\SchemaMigrationExecutor;
use spitfire\storage\database\Schema;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SchemaDiffDirector extends Command
{
	
	protected static $defaultName = 'model:diff';
	protected static $defaultDescription = 'Checks whether the models contain changes relative to the schema.';
	
	private Schema $schema;
	private string $modelDir;
	
	public function __construct(Schema $schema, string $modelDir)
	{
		$this->schema = $schema;
		$this->modelDir = $modelDir;
		parent::__construct();
	}
	
	/**
	 * 
	 * @return void
	 */
	protected function configure() : void
	{
		try {
			$this->addArgument(
				'dir',
				InputArgument::OPTIONAL,
				'The directory to retrieve models from',
				$this->modelDir
			);
		}
		catch (InvalidArgumentException $ex) {
			# This should not be happening.
		}
	}
	
	/**
	 * 
	 * @todo Not lovong the fact that this throws this many exceptions
	 * @throws NotFoundException
	 * @throws ApplicationException
	 * @throws InvalidArgumentException
	 */
	protected function execute(InputInterface $input, OutputInterface $output) : int
	{
		$dir = $input->getArgument('dir');
		assert(is_string($dir));
		
		$files = glob($dir . '/*.php');
		$models = new TypedCollection(ReflectionClass::class);
		
		if ($files === false) {
			throw new NotFoundException(sprintf('Could not read directory %s', $dir));
		}
		
		foreach ($files as $file) {
			$output->writeln($file);
			include $file;
		}
		
		foreach (get_declared_classes() as $classname) {
			$reflection = new ReflectionClass($classname);
			if ($reflection->isSubclassOf(Model::class)) {
				$models->push($reflection);
			}
		}
		
		foreach ($models as /** @var ReflectionClass */$model) {
			$output->writeln($model->getName());
		}
		
		foreach ($models as /** @var ReflectionClass */$model) {
			$layout = (new AttributeLayoutGenerator())->make(new SchemaMigrationExecutor($this->schema), $model);
			$this->upgrade($output, $layout);
		}
		
		
		
		return 0;
	}
	
	private function upgrade(OutputInterface $output, LayoutInterface $layout) : void
	{
		/**
		 * If the layout does not exist at all, we can skip it, since it's just missing.
		 */
		if (!$this->schema->hasLayoutByName($layout->getTableName())) {
			$output->writeln('Missing layout for model ' . $layout->getTableName());
			return;
		}
		
		/**
		 * @throws void
		 */
		$baseline = $this->schema->getLayoutByName($layout->getTableName());
		$diff = (new Generator($baseline, $layout))->make();
		
		foreach ($diff->left()->getFields() as $field) {
			$output->writeln('<bg=green>Add field</>');
			$output->writeln(sprintf(' %s', $field->getName()));
			$output->writeln(sprintf(' %s', $field->getType()));
			$output->writeln(sprintf(' %s', $field->isNullable()? 'Nullable' : 'Not null'));
		}
		
		foreach ($diff->right()->getFields() as $field) {
			$output->writeln(sprintf('<bg=red>Remove field</>'));
			$output->writeln(sprintf(' %s', $field->getName()));
			$output->writeln(sprintf(' %s', $field->getType()));
			$output->writeln(sprintf(' %s', $field->isNullable()? 'Nullable' : 'Not null'));
		}
		
		foreach ($diff->left()->getIndexes() as $index) {
			$output->writeln('<bg=green>Add index</>');
			$output->writeln(sprintf(' %s', $index->getName()));
			$output->writeln(sprintf(' %s', $index->getFields()->each(fn($e) => $e->getName())->join(',')));
		}
		
		foreach ($diff->right()->getIndexes() as $index) {
			$output->writeln('<bg=red>Remove index</>');
			$output->writeln(sprintf(' %s', $index->getName()));
			$output->writeln(sprintf(' %s', $index->getFields()->each(fn($e) => $e->getName())->join(',')));
		}
	}
}
