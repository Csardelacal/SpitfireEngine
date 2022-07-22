<?php

declare(strict_types=1);

namespace spitfire\storage\database\query;

use PDOException as Exception;

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
 * @see https://github.com/doctrine/dbal/blob/3.3.x/src/Driver/Result.php
 */

/**
 * Driver-level statement execution result.
 */
interface ResultInterface
{
	/**
	 * Returns the next row of the result as a numeric array or FALSE if there are no more rows.
	 *
	 * @return array<mixed>|false
	 *
	 * @throws Exception
	 */
	public function fetchNumeric();
	
	/**
	 * Returns the next row of the result as an associative array or FALSE if there are no more rows.
	 *
	 * @return array<string,mixed>|false
	 *
	 * @throws Exception
	 */
	public function fetchAssociative();
	
	/**
	 * Returns the first value of the next row of the result or FALSE if there are no more rows.
	 *
	 * @return mixed|false
	 *
	 * @throws Exception
	 */
	public function fetchOne();
	
	/**
	 * Returns an array containing all of the result rows represented as numeric arrays.
	 *
	 * @return array<array<mixed>>
	 *
	 * @throws Exception
	 */
	public function fetchAllNumeric(): array;
	
	/**
	 * Returns an array containing all of the result rows represented as associative arrays.
	 *
	 * @return array<array<string,mixed>>
	 *
	 * @throws Exception
	 */
	public function fetchAllAssociative(): array;
	
	/**
	 * Returns an array containing the values of the first column of the result.
	 *
	 * @return array<mixed>
	 *
	 * @throws Exception
	 */
	public function fetchFirstColumn(): array;
	
	/**
	 * Returns the number of rows affected by the DELETE, INSERT, or UPDATE statement that produced the result.
	 *
	 * If the statement executed a SELECT query or a similar platform-specific SQL (e.g. DESCRIBE, SHOW, etc.),
	 * some database drivers may return the number of rows returned by that query. However, this behaviour
	 * is not guaranteed for all drivers and should not be relied on in portable applications.
	 *
	 * @return int The number of rows.
	 *
	 * @throws Exception
	 */
	public function rowCount(): int;
	
	/**
	 * Returns the number of columns in the result
	 *
	 * @return int The number of columns in the result. If the columns cannot be counted,
	 *             this method must return 0.
	 *
	 * @throws Exception
	 */
	public function columnCount(): int;
	
	/**
	 * Discards the non-fetched portion of the result, enabling the originating statement to be executed again.
	 */
	public function free(): void;
}
