<?php

namespace spitfire\model;

use Model;

/**
 * Elements of this class indicate a connection between two Models, this allows
 * spitfire to let you use relational databases just like they were objects
 * (connected to each other). While real elements are scattered around tables
 * in no apparent manner.
 * 
 * This class doesn't indicate how the fields inside objects relate as their
 * names are automatically inherited by the source model when referencing. Names
 * for the fields are always given in a <i>alias</i>_<i>srcfield</i> fashion to 
 * improve understanding.
 * 
 * [NOTICE] The model doesn't check for the availability of field names, in case
 * you have duplicate field names due to references the system may behave in an 
 * unexpected way.
 * 
 * @author César de la cal <cesar@magic3w.com>
 * @last-revision 2013-05-16
 */
class Reference extends Field
{
	/**
	 * Indicates which model is the target of the reference. If model A 
	 * references model B, then B is the target.
	 * @var Model
	 */
	private $target;
	
	/**
	 * Creates a new reference between two models, this allows them to access
	 * data through the ORM, therefore not requiring additional user generated
	 * queries to retrieve data.
	 * 
	 * @param Model  $source
	 * @param Model  $target
	 * @param string $alias
	 */
	public function __construct(Model$target) {
		$this->target = $target;
	}
	
	/**
	 * Defines the target model, this model is the one 'donating' it's 
	 * primary key to the source so source can reference it as a parent
	 * model.
	 * 
	 * @param Model $target
	 */
	public function setTarget(Model$target) {
		$this->target = $target;
	}
	
	/**
	 * Returns the target model (the parent model of the source). Which offers
	 * it's primary keys to the target so it can reference them.
	 * 
	 * @return Model
	 */
	public function getTarget() {
		return $this->target;
	}
	
	/**
	 * Returns a list of the fields this reference generates on the source
	 * Model. It copies the list of primary fields from the target model and
	 * prepends the role (separated by underscores) to the name of the field.
	 * 
	 * This function is just included for convenience. It just generates the
	 * data in a easy way to pick, so you cannot modify the way this is done
	 * or the fields below.
	 * 
	 * @return Field[]
	 */
	public function makePhysical() {
		$fields   = $this->getTarget()->getPrimary();
		$physical = Array();
		$_return  = Array();
		
		foreach ($fields as $field) 
			$physical = array_merge ($physical, $field->getPhysical());
		
		foreach ($physical as $remote_field) {
			$field = clone $remote_field;
			$field->setName($this->getName() . $field->getName());
			$field->setLogicalField($this);
			$field->setReferencedField($remote_field);
			$_return[] = $field;
		}
		
		return $_return;
	}

	/**
	 * Defines this field and all of it's children as reference fields.
	 * 
	 * @return type
	 */
	public function getDataType() {
		return Field::TYPE_REFERENCE;
	}
	
}