<?php namespace spitfire\storage\database\migration;

use spitfire\exceptions\ApplicationException;

interface TagManagerInterface
{
	
	/**
	 * Lists the database's tags. Tags are used as an in-channel mechanism to keep track
	 * of the database's state, like migrations and similar.
	 *
	 * @return string[] Indicating whether the migration is already applied
	 * @throws ApplicationException If the migration could not be applied
	 */
	public function listTags() : array;
	
	/**
	 * Tag the database.
	 *
	 * @param string $tag
	 * @throws ApplicationException If the migration could not be applied
	 */
	public function tag(string $tag) : void;
	
	/**
	 * Remove a tag from the database.
	 *
	 * @param string $tag
	 * @throws ApplicationException If the migration could not be applied
	 */
	public function untag(string $tag) : void;
}
