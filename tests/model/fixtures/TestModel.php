<?php namespace tests\spitfire\model\fixtures;
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


use spitfire\model\attribute\BelongsToOne;
use spitfire\model\attribute\InIndex;
use spitfire\model\attribute\Integer;
use spitfire\model\attribute\References;
use spitfire\model\attribute\Table;
use spitfire\model\Model;
use spitfire\model\traits\WithId;

#[Table('test')]
class TestModel extends Model
{
	
	use WithId;
	
	/**
	 *
	 * @var string
	 */
	#[Integer()]
	private ?string $test;
	
	
	/**
	 *
	 * @var int
	 */
	#[Integer(true)]
	#[InIndex('test', 2)]
	private int $example;
	
	/**
	 *
	 * @var int
	 */
	#[Integer(true)]
	#[InIndex('test', 1)]
	private int $example2;
	
	/**
	 *
	 * @var int
	 */
	#[BelongsToOne(ForeignModel::class, 'test')]
	#[References(ForeignModel::class)]
	private int $foreign;
	
	public function getTest(): string
	{
		return $this->test;
	}
}
