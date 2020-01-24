<?php

use spitfire\core\time\TimeDiff;

class Time
{
	
	/**
	 *
	 * @param int      $time
	 * @param int|null $to
	 *
	 * @return string
	 */
	public static function relative($time, $to = null) {
		
		$multiples = [
			'minute' => 60,
			'hour' => 3600,
			'day' => 3600*24,
			'week' => 3600*24*7,
			'month' => 3600*24*30,
			'year' => 3600*24*365
		];
		
		$to = ($to === null)? time() : $to;
		$diff = abs($to - $time);
		$unit = 'second';
		$scale = 1;
		$future = $time > $to;
		
		foreach ($multiples as $multiple => $amt) {
			if ($diff > $amt) { 
				$unit = $multiple; 
				$scale = $amt;
			}
		}
		
		return new TimeDiff($future, (int)($diff / $scale), $unit);
	}
	
}
