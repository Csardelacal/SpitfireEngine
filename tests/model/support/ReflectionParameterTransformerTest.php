<?php namespace tests\spitfire\model\support;

use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use spitfire\core\Request;
use spitfire\model\support\ReflectionParameterTransformer;

class ReflectionParameterTransformerTest extends TestCase
{
	
	public function testEmptyParameters()
	{
		$closure = function (Request $request) {
			return $request;
		};
		
		$reflection = new ReflectionFunction($closure);
		$parameters = ReflectionParameterTransformer::transformParameters($reflection, []);
		
		$this->assertArrayNotHasKey('request', $parameters);
		$this->assertArrayNotHasKey(0, $parameters);
	}
}
