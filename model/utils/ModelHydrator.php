<?php namespace spitfire\model\utils;

use ReflectionClass;
use ReflectionException;
use spitfire\model\Model;
use spitfire\model\relations\RelationshipContent;

class ModelHydrator
{
	
	public static function hydrate(Model $model)
	{
		
		$keys = $model->getActiveRecord()->keys();
		$reflection = new ReflectionClass($model);
		
		foreach ($keys as $k) {
			try {
				$prop = $reflection->getProperty($k);
				
				$value = $model->getActiveRecord()->get($k);
				
				if ($value instanceof RelationshipContent) {
					$value = $value->isSingle()? $value->getPayload()->first() : $value->getPayload();
				}
				
				if ($prop->getType() && !$prop->getType()->allowsNull() && $value === null) {
					continue;
				}
				
				/**
				 * @todo Remove the set accessible call, this is deprecated since PHP8.1
				 */
				$prop->setAccessible(true);
				$prop->setValue($model, $value);
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
