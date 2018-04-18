<?php

namespace spitfire\storage\database\drivers;

use spitfire\storage\database\drivers\sql\SQLRestrictionGroup;

class MysqlPDORestrictionGroup extends SQLRestrictionGroup
{
	public function __toString() {
		if ($this->isEmpty()) { return ''; }
		return sprintf('(%s)', implode(' ' . $this->getType() .' ', $this->getRestrictions()));
	}
}