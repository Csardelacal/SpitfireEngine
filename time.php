<?php

class Time
{
	
	/**
	 * 
	 * @fixme lang() died and is no longer usable.
	 * @param type $time
	 * @param type $to
	 * @return type
	 */
	public static function relative($time, $to = null) {
		$to = ($to === null)? time() : $to;
		$lang = _t()->getDefault();
		$diff = $to - $time;
		
		if ($diff > 0) {
			if (1 < $ret = (int)($diff / (3600*24*365))) { return $lang->say('%s years ago', $ret); }
			if (1 < $ret = (int)($diff / (3600*24*30)))  { return $lang->say('%s months ago', $ret); }
			if (1 < $ret = (int)($diff / (3600*24*7)))   { return $lang->say('%s weeks ago', $ret); }
			if (1 < $ret = (int)($diff / (3600*24)))     { return $lang->say('%s days ago', $ret); }
			if (1 < $ret = (int)($diff / (3600)))        { return $lang->say('%s hours ago', $ret); }
			if (1 < $ret = (int)($diff / (60)))          { return $lang->say('%s minutes ago', $ret); }
			return $lang->say('%s seconds ago', $diff); 
		}
	}
	
}