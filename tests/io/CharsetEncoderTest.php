<?php namespace tests\spitfire\database\io;

use PHPUnit\Framework\TestCase;
use spitfire\storage\database\io\CharsetEncoder;

class CharsetEncoderTest extends TestCase
{
	
	public function testEncoder() {
		
		$string  = 'áéíóäëÖç';
		$encoder = new CharsetEncoder('utf-8', 'latin1');
		
		$this->assertEquals(true, is_string($encoder->encode($string)));
		$this->assertNotEquals($string, $encoder->encode($string));
		$this->assertEquals($string, $encoder->decode($encoder->encode($string)));
		
	}
	
}