<?php namespace tests;

use PHPUnit\Framework\TestCase;
use spitfire\utils\Strings;

class StringTest extends TestCase
{
	
	public function testSlugSpaces() {
		$this->assertEquals('a-string-with-spaces', Strings::slug('a string with spaces'));
		$this->assertEquals('a-string-with-spaces', Strings::slug('a string with  spaces'));
		$this->assertEquals('a-string-with-spaces', Strings::slug('a string with   spaces'));
	}
	
	public function testSlugSpecialChars() {
		$this->assertEquals('a-string-with-special-chars', Strings::slug('a string with spëcìal chàrs'));
		$this->assertEquals('a-string-with-special-ch-rs', Strings::slug('a string with spëcìal chªrs'));
		$this->assertEquals('a-string-with-special-ch-rs', Strings::slug('a_string_with spëcìal chªrs'));
		$this->assertEquals('a-string-with-special-ch-rs', Strings::slug('a_string_with spëcìal ch&rs'));
		$this->assertEquals('a-string-with-special-ch-rs', Strings::slug('a_string_with spëcìal ch@rs'));
		$this->assertEquals('a-string-with-special-h-rs',  Strings::slug('a_string_with spëcìal ©h@rs'));
	}
	
	public function testSlugPunctuation() {
		$this->assertEquals('category-news', Strings::slug('category:news'));
		$this->assertEquals('category-news', Strings::slug('category;news'));
		$this->assertEquals('category-news', Strings::slug('category/news'));
	}
	
	public function testSlugUppercase() {
		$this->assertEquals('uppercase', Strings::slug('UPPERCASE'));
		$this->assertEquals('some-caps', Strings::slug('Some CaPS'));
	}
	
	/**
	 * Tests some of the most common usages of the camel2underscores function. This
	 * function is usually helpful when converting class names to environments 
	 * which are not case-sensitive.
	 */
	public function testCamelCase2UnderscoreConversions() {
		$this->assertEquals('some_string', Strings::camel2underscores('someString'));
		$this->assertEquals('some_string', Strings::camel2underscores('SomeString'));
	}
	
	/**
	 * Tests the function that converts underscore separated names into camel case 
	 * identifiers.
	 */
	public function testUnderscore2CamelCaseConversions() {
		$this->assertEquals('SomeString', Strings::underscores2camel('some_string'));
		$this->assertEquals('someString', Strings::underscores2camel('some_string', false));
	}
	
	/**
	 * Tests whether the escape method properly removes unsafe HTML
	 */
	public function testEscape() {
		$this->assertEquals('&lt;strong', Strings::escape('<strong'));
	}
	
	/**
	 * For HTML tags, we need the system to quote the content too.
	 */
	public function testQuote() {
		$this->assertEquals('&quot;strong', Strings::quote('"strong'));
		$this->assertEquals('&#039;strong', Strings::quote('\'strong'));
	}

	/**
	 * Replacing URLs in text is actually surprisingly difficult.
	 */
	public function testURL() {
		$this->assertEquals(
			'Hello world, checkout <a href="https://magic3w.com/?about=us&team=true">https://magic3w.com/?about=us&amp;team=true</a>',
			Strings::urls('Hello world, checkout https://magic3w.com/?about=us&team=true')
		);

		$this->assertEquals(
			'<a href="https://magic3w.com/?about=us&team=true">https://magic3w.com/?about=us&amp;team=true</a> &lt;&lt; Check this website',
			Strings::urls('https://magic3w.com/?about=us&team=true << Check this website')
		);
	}

	/**
     * Replacing URLs in text is actually surprisingly difficult.
	 */
	public function testURLWithHash() {
		$this->assertEquals(
			'Hello world, checkout <a href="https://magic3w.com/about#anchor">https://magic3w.com/about#anchor</a>',
			Strings::urls('Hello world, checkout https://magic3w.com/about#anchor')
		);
	}

}
