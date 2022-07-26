<?php namespace spitfire\model\directors;

use ReflectionClass;
use spitfire\collection\Collection;
use spitfire\model\Model;
use spitfire\model\utils\AttributeLayoutGenerator;
use spitfire\storage\database\diff\Generator;
use spitfire\storage\database\Schema;
use Symfony\Component\Console\Command\Command;
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
	
	protected function configure()
	{
		$this->addArgument(
			'dir',
			InputArgument::OPTIONAL,
			'The directory to retrieve models from',
			$this->modelDir
		);
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) : int
	{
		$files = glob($input->getArgument('dir') . '/*.php');
		$models = new Collection();
		
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
			$layout = (new AttributeLayoutGenerator())->make($model);
			
			if ($this->schema->hasLayoutByName($layout->getTableName())) {
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
			else {
				$output->writeln('Missing layout for model ' . $model->getName());
			}
		}
		
		
		
		return 0;
	}
}
