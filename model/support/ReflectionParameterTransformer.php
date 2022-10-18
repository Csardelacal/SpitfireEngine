<?php namespace spitfire\model\support;

use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionNamedType;
use ReflectionType;
use spitfire\exceptions\user\ApplicationException;
use spitfire\model\Model;

/**
 * This class allows different components to reuse the logic the database has
 * to create parameters for different functions/methods.
 *
 * The transformer receives:
 *
 * * A reflection of a function or method that may contain parameters expecting a subtype of model
 * * A list of parameters that we provide for this function that may contain ID for a db record
 *
 * The transformer then returns:
 *
 *  * The parameters, replacing those that were incompatible with their expected model type with a looked up version
 *
 *
 * NOTE: This sorts the parameters by the order they are expected in within the parameters, but keys
 * them according to the names of the parameters. For example:
 */
class ReflectionParameterTransformer
{
	
	public static function transformParameters(ReflectionFunctionAbstract $reflection, array $parameters)
	{
		$expected = $reflection->getParameters();
		$_return = [];
		
		foreach ($expected as $idx => $_t) {
			$_e = $_t->getType();
			$_n = $_t->getName();
			
			/**
			 * The parameters may be missing an entry, this is perfectly acceptable for this
			 * transformer since it does not expect the parameters to be complete. That's for
			 * the service provider to fill.
			 */
			if (!array_key_exists($_n, $parameters) && !array_key_exists($idx, $parameters)) {
				continue;
			}
			
			$param = array_key_exists($_n, $parameters)? $parameters[$_n] : $parameters[$idx];
			
			/**
			 * If the parameter is already a model, it doesn't make any sense to repeat
			 * the operation.
			 */
			if ($param instanceof Model) {
				$_return[$_n] = $param;
				continue;
			}
			
			/**
			 * Make sure the type we're trying to work with is a subtype of model. Otherwise, the application
			 * cannot resolve it.
			 */
			if (!self::isSubtypeOf($_e, Model::class)) {
				$_return[$_n] = $param;
				continue;
			}
			
			assert($_e instanceof ReflectionNamedType);
			
			/**
			 * Make the model and push it onto the parameters.
			 */
			$_return[$_n] = self::make($_e, $param);
		}
		
		return $_return;
	}
	
	private static function isSubtypeOf(?ReflectionType $_e, string $classname) : bool
	{
		
		/**
		 * If no type was specified at all for our incoming parameter, we cannot
		 * resolve it and will use the data we received without editing.
		 */
		if ($_e === null) {
			return false;
		}
		
		/**
		 * If the type is not named, we do not care for it, since we can't work with
		 * it either.
		 */
		if (!($_e instanceof ReflectionNamedType)) {
			return false;
		}
		
		if ($_e->isBuiltin()) {
			return false;
		}
		
		/**
		 * For our next trick, we're going to need a reflection of the class that the
		 * developer is expecting us to provide, otherwise we cannot tell if it is a
		 * subclass of model.
		 */
		$_type = $_e->getName();
		$class = new ReflectionClass($_type);
		
		/**
		 * Lastly, if the class we expect is not a subtype of model, we also ignore it,
		 * since the dependency provider will take care of this one.
		 */
		if (!$class->isSubclassOf($classname)) {
			return false;
		}
		
		return true;
	}
	
	private static function accepts(ReflectionNamedType $_e, $model) : bool
	{
		$_type = $_e->getName();
		return $model instanceof $_type;
	}
	
	private static function make(ReflectionNamedType $_e, $param) : Model
	{
		/**
		 * Fetch the database record that we expected from the database.
		 */
		$model = db()->fetch($_e->getName(), $param);
		
		/**
		 * Perform a sanity check in the development environment to ensure the consistency
		 * of our data.
		 */
		assert($model === null || self::accepts($_e, $model));
		
		/**
		 * As a safeguard, we ensure that if the method we're trying to build parameters for
		 * accepts no nullable type for this we throw an exception if our query came up empty.
		 */
		if ($model === null && !$_e->allowsNull()) {
			throw new ApplicationException('Could not resolve the model for a parameter that does not accept null', 2111161127);
		}
		
		return $model;
	}
}
