<?php namespace spitfire\storage\database\migration\relational;

use spitfire\storage\database\drivers\SchemaMigrationExecutorInterface;
use spitfire\storage\database\drivers\TableMigrationExecutorInterface;
use spitfire\storage\database\MigrationOperationInterface;

class TagLayoutMigration implements MigrationOperationInterface
{
	
	public function up(SchemaMigrationExecutorInterface $schema) : void
	{
		if ($schema->has('_tags')) {
			return;
		}
		
		$schema->add('_tags', function (TableMigrationExecutorInterface $table) {
			$table->id();
			$table->string('tag', 255);
			$table->timestamps();
		});
	}
	
	public function down(SchemaMigrationExecutorInterface $schema): void
	{
		if ($schema->has('_tags')) {
			$schema->drop('_tags');
		}
	}
	
	public function identifier(): string
	{
		return 'create._tags';
	}
	
	public function description(): string
	{
		return 'Tags for keeping tabs of migrations and schema status';
	}
}
