<?php namespace tests\spitfire\core;

use BadMethodCallException;
use PHPUnit\Framework\TestCase;
use spitfire\core\Headers;

class HeadersTest extends TestCase
{
	
	public function testContentType() {
		
		$t = new Headers();
		
		$t->contentType('php', 'utf-8');
		$this->assertEquals('text/html;charset=utf-8', $t->get('Content-type')[0]);
		
		$t->contentType('html', 'utf-8');
		$this->assertEquals('text/html;charset=utf-8', $t->get('Content-type')[0]);
		
		$t->contentType('json', 'utf-8');
		$this->assertEquals('application/json;charset=utf-8', $t->get('Content-type')[0]);
		
		$t->contentType('xml', 'utf-8');
		$this->assertEquals('application/xml;charset=utf-8', $t->get('Content-type')[0]);
		
	}
	
	/**
	 */
	public function testInvalidStatus() {
		$t = new Headers();
		
		$this->expectException(BadMethodCallException::class);
		$t->status('22');
	}
	
}

