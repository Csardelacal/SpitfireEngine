<?php namespace tests\spitfire\http\request\middleware;

use PHPUnit\Framework\TestCase;
use spitfire\http\request\middleware\InMemoryStore;
use spitfire\http\request\middleware\RateLimitStore;

/**
 * Unit tests for {@see InMemoryStore}.
 *
 * The "current time" is injected (a Closure) so the window behaviour can be
 * driven deterministically without sleeping.
 */
class InMemoryStoreTest extends TestCase
{

	public function testImplementsRateLimitStore()
	{
		$this->assertInstanceOf(RateLimitStore::class, new InMemoryStore());
	}

	public function testCountsHitsWithinTheWindow()
	{
		$now   = 1000;
		$store = new InMemoryStore(function() use (&$now) { return $now; });

		$this->assertSame(1, $store->hit('a', 60));

		$now = 1010;   // still inside the 60s window
		$this->assertSame(2, $store->hit('a', 60));
	}

	public function testExcludesHitsOutsideTheWindow()
	{
		$now   = 1000;
		$store = new InMemoryStore(function() use (&$now) { return $now; });

		$store->hit('a', 60);

		$now = 2000;   // 1000s later — the first hit is now outside the 60s window
		$this->assertSame(1, $store->hit('a', 60));   // only the new hit counts
	}

	public function testTracksKeysSeparately()
	{
		$store = new InMemoryStore();

		$store->hit('a', 60);
		$store->hit('a', 60);

		$this->assertSame(1, $store->hit('b', 60));   // b is independent of a
		$this->assertSame(3, $store->hit('a', 60));   // a keeps counting its own hits
	}

	public function testResetClearsHits()
	{
		$store = new InMemoryStore();

		$store->hit('a', 60);
		$store->hit('a', 60);
		$store->reset('a');

		$this->assertSame(1, $store->hit('a', 60));   // back to a fresh count
	}
}
