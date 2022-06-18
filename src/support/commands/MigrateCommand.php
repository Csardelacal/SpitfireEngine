<?php namespace spitfire\storage\database\support\commands;

use spitfire\exceptions\ApplicationException;
use spitfire\storage\database\Connection;
use spitfire\storage\database\migration\schemaState\SchemaMigrationExecutor;
use spitfire\storage\database\MigrationOperationInterface;
use spitfire\storage\database\Schema;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends Command
{
	
	protected static $defaultName = 'migration:migrate';
	protected static $defaultDescription = 'Performs migrations.';
	
	/**
	 *
	 * @var Connection
	 */
	private $connection;
	
	/**
	 *
	 * @var string
	 */
	private $migrationManifestFile;
	
	/**
	 *
	 * @var string
	 */
	private $schemaBaseline;
	
	/**
	 *
	 * @var string
	 */
	private $schemaCacheFile;
	
	/**
	 *
	 * @param Connection $connection
	 * @param string $migrationManifestFile
	 * @param string $schemaBaseline
	 * @param string $schemaCacheFile
	 */
	public function __construct(Connection $connection, string $migrationManifestFile, string $schemaBaseline, string $schemaCacheFile)
	{
		$this->connection = $connection;
		$this->migrationManifestFile = $migrationManifestFile;
		$this->schemaBaseline = $schemaBaseline;
		$this->schemaCacheFile = $schemaCacheFile;
		parent::__construct();
	}
	
	protected function configure()
	{
		$this->addOption(
			'dump',
			null,
			InputOption::VALUE_NONE | InputOption::VALUE_NEGATABLE,
			'Dump the schema file to cache'
		);
	}
	
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		/**
		 * The migration command will allow the system to import migrations
		 * from the migrations manifest file which should contain an array
		 * of migrations to be performed to maintain the application at a
		 * modern state.
		 *
		 * Please note that the order in which the migrations appear in the
		 * manifest file is relevant to the order in which they are applied
		 * and rolled back.
		 */
		$file = $this->migrationManifestFile;
		
		/**
		 * Get the baseline schema. This represents the state the application expects
		 * the schema to be in before attempting any migrations.
		 */
		if (file_exists($this->schemaBaseline)) {
			$schema = include $this->schemaBaseline;
		}
		else {
			$schema = new Schema($this->connection->getSchema()->getName());
		}
		
		/**
		 * If there is no manifest, there is no way to consistently apply
		 * the migrations.
		 */
		if (!file_exists($file)) {
			throw new ApplicationException(sprintf('No migration manifest file in %s', $file), 2204110944);
		}
		
		$connection = clone $this->connection;
		$connection->setSchema($schema);
		
		/**
		 * List the migrations available to the application.
		 */
		$migrations = include($file);
		
		/**
		 * Fast forward the base schema to match the currently applied schema on the database.
		 * This should prevent the application from having inconsistent states.
		 */
		foreach ($migrations as $migration) {
			$output->writeln('Checking ' . $migration->identifier());
			if ($connection->contains($migration)) {
				$migration->up(new SchemaMigrationExecutor($connection->getSchema()));
			}
			else {
				echo 'Not found', PHP_EOL;
			}
		}
		
		/**
		 * Apply migrations to the server to ensure that the schema is up-to-date.
		 */
		foreach	($migrations as $migration) {
			if (!$connection->contains($migration)) {
				assert($migration instanceof MigrationOperationInterface);
				$output->writeln('Applying ' . $migration->identifier());
				$connection->apply($migration);
			}
			else {
				$output->writeln('Skipping...');
			}
		}
		
		/**
		 * Write the schema to disk. If the server
		 */
		if ($input->getOption('dump') !== false) {
			$output->writeln('Writing schema to disk...');
			
			/**
			 * Write the schema file to the cache. Please note that while this is technically a
			 * cache file, it should generally be committed when using an application that has
			 * several backend servers accessing a single database.
			 *
			 * This is why this file is stored in /bin/schema.php by default.
			 */
			file_put_contents(
				$this->schemaCacheFile,
				sprintf('<?php return %s;', var_export($schema, true))
			);
		}
		
		return 0;
	}
}
