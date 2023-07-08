<?php namespace spitfire\model\utils;
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
use ReflectionException;
use ReflectionProperty;
use spitfire\model\Model;
use spitfire\model\ReflectionModel;
use spitfire\model\relations\RelationshipContent;

class ModelHydrator
{
	
	public static function hydrate(Model $model) : void
	{
		
		$keys = $model->getActiveRecord()->keys();
		$reflection = new ReflectionModel($model::class);
		
		foreach ($keys as $k) {
			try {
				$reflection->hasProperty($k) && $reflection->writeToProperty(
					$model,
					$k,
					$model->getActiveRecord()->get($k)
				);
			}
			/**
			 * We actually don't care if the reflection couldn't load the property, if
			 * the model doesn't have it, the application should not be able to load it.
			 * It is very much recommended to make sure that the model has all the necessary
			 * properties to work, but if the database has extraneous data we shouldn't
			 * kill the application right away.
			 * For the sake of debugging, a notice is raised.
			 */
			catch (ReflectionException $e) {
				trigger_error(sprintf('Model is missing property %s', $k), E_USER_NOTICE);
			}
		}
	}
}
