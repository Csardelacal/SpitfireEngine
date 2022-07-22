<?php

declare(strict_types=1);

namespace spitfire\storage\database\drivers\mysqlpdo;

use PDO;
use PDOException as Exception;
use PDOStatement;
use spitfire\storage\database\query\ResultInterface;

/**
 * Copyright (c) 2006-2018 Doctrine Project
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is furnished to do
 * so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 *
 * @see https://github.com/doctrine/dbal/blob/3.3.x/src/Driver/PDO/Result.php
 */
final class MySQLResult implements ResultInterface
{
	/** @var PDOStatement */
	private $statement;
	
	/**
	 * @internal The result can be only instantiated by its driver connection or statement.
	 */
	public function __construct(PDOStatement $statement)
	{
		$this->statement = $statement;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function fetchNumeric()
	{
		return $this->fetch(PDO::FETCH_NUM);
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function fetchAssociative()
	{
		return $this->fetch(PDO::FETCH_ASSOC);
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function fetchOne()
	{
		return $this->fetch(PDO::FETCH_COLUMN);
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function fetchAllNumeric(): array
	{
		return $this->fetchAll(PDO::FETCH_NUM);
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function fetchAllAssociative(): array
	{
		return $this->fetchAll(PDO::FETCH_ASSOC);
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function fetchFirstColumn(): array
	{
		return $this->fetchAll(PDO::FETCH_COLUMN);
	}
	
	public function rowCount(): int
	{
		return $this->statement->rowCount();
	}
	
	public function columnCount(): int
	{
		return $this->statement->columnCount();
	}
	
	public function free(): void
	{
		$this->statement->closeCursor();
	}
	
	/**
	 * @return mixed|false
	 *
	 * @throws Exception
	 */
	private function fetch(int $mode)
	{
		return $this->statement->fetch($mode);
	}
	
	/**
	 * @return mixed[]
	 *
	 * @throws Exception
	 */
	private function fetchAll(int $mode): array
	{
		return $this->statement->fetchAll($mode);
	}
}
