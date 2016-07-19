<?php namespace tests\core\annotation;

use PHPUnit_Framework_TestCase;
use ReflectionClass;
use spitfire\core\annotations\AnnotationParser;


class AnnotationParserTest extends PHPUnit_Framework_TestCase
{
	
	public function testParser() {
		
		$string = "/**\n * @param test A \n * @param test B \n */";
		$parser = new AnnotationParser();
		
		$annotations = $parser->parse($string);
		
		#Test the element is actually there
		$this->assertArrayHasKey('param', $annotations);
		
		#Ensure it did parse the same annotation twice and properly structure the array
		$this->assertCount(1, $annotations);
		$this->assertCount(2, $annotations['param']);
		
		#Test the value is what we expect
		$this->assertEquals('test', $annotations['param'][0][0]);
		$this->assertEquals('A',    $annotations['param'][0][1]);
		$this->assertEquals('B',    $annotations['param'][1][1]);
		
	}
	
	/**
	 * 
	 * @sometest test A
	 * @sometest test B
	 */
	public function testParserReflection() {
		
		$parser = new AnnotationParser();
		$reflec = new ReflectionClass($this);
		
		$annotations = $parser->parse($reflec->getMethod('testParserReflection'));
		
		#Test the element is actually there
		$this->assertArrayHasKey('sometest', $annotations);
		
		#Ensure it did parse the same annotation twice and properly structure the array
		$this->assertCount(1, $annotations);
		$this->assertCount(2, $annotations['sometest']);
		
		#Test the value is what we expect
		$this->assertEquals('test', $annotations['sometest'][0][0]);
		$this->assertEquals('A',    $annotations['sometest'][0][1]);
		$this->assertEquals('B',    $annotations['sometest'][1][1]);
	}
}