<?php namespace spitfire\model\relations;
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


use spitfire\collection\Collection;
use spitfire\model\ActiveRecord;
use spitfire\model\Field;
use spitfire\model\Model;
use spitfire\model\QueryBuilderInterface;

/**
 * A relationship describes how two models connect with each other. This is useful
 * for navigating models and building queries.
 * 
 * @template LOCAL of Model
 * @template REMOTE of Model
 */
interface RelationshipInterface
{
	
	/**
	 * 
	 * @return Field<LOCAL>
	 */
	public function localField() : Field;
	
	public function resolve(ActiveRecord $record) : RelationshipContent;
	
	/**
	 *
	 * @param Collection<ActiveRecord> $records
	 * @return Collection<RelationshipContent>
	 */
	public function resolveAll(Collection $records) : Collection;
	
	/**
	 * 
	 * @return RelationshipInjectorInterface<REMOTE>
	 */
	public function injector(): RelationshipInjectorInterface;
	
	/**
	 * 
	 * @return QueryBuilderInterface
	 */
	public function startQueryBuilder(ActiveRecord $parent): QueryBuilderInterface;
}
