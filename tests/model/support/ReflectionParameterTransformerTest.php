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
	
	/**
	 * This test is meant to detect whether the transformers have issues with overrides.
	 * The idea behind it is that the function we were using ($a[$name]?? $a[$index]) would
	 * cause trouble when the parameter was null.
	 */
	public function testOverridePriorities()
	{
		
		$closure = function (string $a = null, string $b = null) {
			return $a . $b;
		};
		
		$input = [
			'a' => null,
			'b' => null,
			0   => 'bad'
		];
		$reflection = new ReflectionFunction($closure);
		$parameters = ReflectionParameterTransformer::transformParameters($reflection, $input);
		
		$this->assertNotEquals('bad', $parameters['a']);
		$this->assertEquals(null, $parameters['a']);
	}
}
