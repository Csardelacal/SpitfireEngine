<?php namespace spitfire\model\attribute;

use Attribute;

/**
 * The table attribute allows a class to indicate that it holds a Model,
 * which can be mapped to a table with properties that can be mapped to
 * columns in the DBMS.
 *
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Timestamps
{
	
}
