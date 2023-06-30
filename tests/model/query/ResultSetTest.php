<?php namespace tests\spitfire\model\query;

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

use PHPUnit\Framework\TestCase;
use spitfire\collection\Collection;
use spitfire\model\query\ResultSet;
use spitfire\model\query\ResultSetMapping;
use spitfire\storage\database\ConnectionGlobal;
use spitfire\storage\database\query\ResultInterface;
use tests\spitfire\model\fixtures\TestModel;

class ResultSetTest extends TestCase
{
	
	/**
	 * 2023-06-30
	 * This test was introduced because the fetchAll method counterintuitively
	 * has it's dimensions swapped. It returns a Collection containing as many
	 * entries as mappings and each of those collections contains the records
	 */
	public function testFetchAllFromEmptyResultSet()
	{
		$resultset = new ResultSet(
			new class () implements ResultInterface {
				public function fetchAllAssociative(): array { return []; }
				public function fetchAllNumeric(): array { return []; }
				public function fetchNumeric(): array { return []; }
				public function fetchAssociative(): array { return []; }
				public function fetchOne() { return false; }
				public function fetchFirstColumn() : array { return []; }
				public function columnCount(): int { return 0; }
				public function rowCount(): int { return 0; }
				public function free(): void { }
			},
			new ResultSetMapping(new TestModel(new ConnectionGlobal()))
		);
		
		$this->assertCount(0, $resultset->fetchAll());
	}
}
