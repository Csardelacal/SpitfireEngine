<?php namespace spitfire\model\attribute;

use Attribute;

/**
 * This attribute indeicates that a property is part of an index. Properties that share
 * an index by name, will be combined.
 *
 * Properties with the highest priority will be set to be at the beginning of the index.
 * Please note that most DBMS will only have performance gains derived from querying a
 * column when including restrictions for all higher priority items.
 *
 * For example, an index(a,b) will boost the performance of queries including a, and a and b,
 * but not of queries including only restrictions for b.
 *
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Primary
{
	
}
