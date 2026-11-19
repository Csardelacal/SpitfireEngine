<?php namespace spitfire\http\request\middleware;

/**
 * A rate-limit counter. The middleware only needs to ask "how many times has
 * this key been hit within the window?" — where the counts actually live
 * (memory, redis, database, ...) is an implementation detail the consuming
 * application chooses.
 */
interface RateLimitStore
{

	/**
	 * Records a hit against the given key and returns the number of hits still
	 * inside the window, including the one just recorded.
	 *
	 * @param string $key The bucket key (see RateLimitMiddleware).
	 * @param int $window The window, in seconds.
	 * @return int
	 */
	public function hit(string $key, int $window): int;

	/**
	 * Clears any recorded hits for the key (e.g. for admin tooling or tests).
	 *
	 * @param string $key
	 */
	public function reset(string $key): void;
}
