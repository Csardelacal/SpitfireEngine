<?php namespace spitfire\http\request\middleware;

/**
 * A process-local {@see RateLimitStore}. It keeps hit timestamps in memory, so
 * it is ideal for tests and single-process deployments. Multi-process or shared
 * deployments should use a store that is visible to every worker (redis, a
 * database, ...) so the limit is enforced across the whole application, not
 * just one process.
 */
class InMemoryStore implements RateLimitStore
{

	/**
	 * Bucket key => list of hit timestamps.
	 *
	 * @var array<string,int[]>
	 */
	private array $hits = array();

	/**
	 * The "current time" source. Injectable so tests can drive the window
	 * deterministically without sleeping.
	 *
	 * @var \Closure():int
	 */
	private \Closure $now;

	public function __construct(?\Closure $now = null)
	{
		$this->now = $now ?: function(): int { return time(); };
	}

	public function hit(string $key, int $window): int
	{
		$now    = ($this->now)();
		$recent = array_values(array_filter(
			$this->hits[$key] ?: array(),
			function (int $time) use ($now, $window): bool { return ($now - $time) < $window; }
		));

		$recent[] = $now;
		$this->hits[$key] = $recent;

		return count($recent);
	}

	public function reset(string $key): void
	{
		unset($this->hits[$key]);
	}
}
