<?php namespace spitfire\model\adapters;

use ArrayAccess;
use ChildrenField;
use Iterator;
use spitfire\exceptions\PrivateException;
use spitfire\Model;
use spitfire\storage\database\Query;
use function collect;

/**
 * Children refers to all models that refer to another (parent) model. This allows
 * to manage Relational databases like they're documents.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class ChildrenAdapter implements ArrayAccess, Iterator, AdapterInterface
{
	/**
	 * The field the parent uses to refer to this element.
	 *
	 * @var ChildrenField
	 */
	private $field;
	
	/**
	 * The model that this one refers to. Since, one Model can have several referring
	 * to it but a model can only refer to one other, we call this model parent.
	 *
	 * @var Model
	 */
	private $parent;
	
	/**
	 * The models that refer to the parent model. This can be either an array of 
	 * models or null, depending on whether the adapter has been populated during
	 * runtime.
	 *
	 * @var Model[]|null
	 */
	private $children;
	
	private $discarded = [];
	
	public function __construct(ChildrenField$field, Model$model, $data = null)
	{
		$this->field  = $field;
		$this->parent = $model;
		$this->children = $data;
	}
	
	/**
	 * Returns the query that would be used to retrieve the elements for this 
	 * adapter. This can be used to add restrictions and query the related records
	 * 
	 * @return Query
	 */
	public function getQuery()
	{
		
		$query = $this->field->getTable()->getDb()->getObjectFactory()
				  ->queryInstance($this->field->getTarget()->getTable());
		
		return $query->where($this->field->getReferencedField()->getName(), $this->parent->getQuery());
	}
	
	/**
	 * 
	 * @deprecated since version 0.1-dev 20190919
	 * @return Model
	 */
	public function pluck()
	{
		if ($this->children !== null) {
			return reset($this->children); 
		}
		
		return $this->getQuery()->fetch();
	}
	
	public function toArray()
	{
		if ($this->children !== null) {
			return $this->children; 
		}
		
		/*
		 * Inform the children that the parent being worked on is this
		 * 
		 * NOTE: This needs to be re-evaluated, it is potentially dangerous to mess
		 * with the state of the children programatically. These children will always
		 * be in a manipulated state and require writing to be back in sync.
		 * 
		 * Potentially, using the dbSetData() method on the children is less invasive
		 * and may lead to better results.
		 */
		$this->children = $this->getQuery()->fetchAll()->each(function ($c) {
			$c->{$this->field->getReferencedField()->getName()} = $this->parent;
			return $c;
		})->toArray();
		
		return $this->children;
	}
	
	public function current()
	{
		$this->children !== null? $this->children : $this->toArray();
		return current($this->children);
	}
	
	public function key()
	{
		$this->children !== null? $this->children : $this->toArray();
		return key($this->children);
	}
	
	public function next()
	{
		$this->children !== null? $this->children : $this->toArray();
		return next($this->children);
	}
	
	public function rewind()
	{
		$this->children !== null? $this->children : $this->toArray();
		return reset($this->children);
	}
	
	public function valid()
	{
		$this->children !== null? $this->children : $this->toArray();
		return !!current($this->children);
	}
	
	public function offsetExists($offset)
	{
		$this->children !== null? $this->children : $this->toArray();
		return isset($this->children[$offset]);
	}
	
	public function offsetGet($offset)
	{
		$this->children !== null? $this->children : $this->toArray();
		return $this->children[$offset];
	}
	
	public function offsetSet($offset, $value)
	{
		$this->children !== null? $this->children : $this->toArray();
		
		$previous = isset($this->children[$offset])? $this->children[$offset] : null;
		
		if ($offset === null) {
			$this->children[] = $value; 
		}
		else {
			$this->children[$offset] = $value; 
		}
		
		#Commit the changes to the database.
		$role  = $this->getField()->getRole();
		
		#We set the value but do not yet commit it, this will happen whenever the 
		#parent model is written.
		$value->{$role} = $this->getModel();
		
		if ($previous) {
			$previous->{$role} = null;
			$this->discarded[] = $previous;
		}
	}
	
	public function offsetUnset($offset)
	{
		$this->children !== null? $this->children : $this->toArray();
		unset($this->children[$offset]);
	}
	
	/**
	 * 
	 * 
	 * @return type
	 */
	public function commit()
	{
		collect($this->discarded)->each(function ($e) {
			$e->store();
		});
		
		collect($this->children)->each(function ($e) {
			$e->store();
		});
	}
	
	public function dbGetData()
	{
		return array();
	}
	
	/**
	 * This method does nothing as this field has no direct data in the DBMS and 
	 * therefore it just ignores whatever the database tries to input.
	 * 
	 * @param mixed $data
	 */
	public function dbSetData($data)
	{
		return;
	}
	
	/**
	 * Returns the parent model for this adapter. This allows any application to 
	 * trace what adapter this adapter belongs to.
	 * 
	 * @return \Model
	 */
	public function getModel()
	{
		return $this->parent;
	}
	
	public function isSynced()
	{
		return true;
	}
	
	public function rollback()
	{
		return true;
	}
	
	public function usrGetData()
	{
		return $this;
	}
	
	/**
	 * Defines the data inside this adapter. In case the user is trying to set 
	 * this adapter as the source for itself, which can happen in case the user
	 * is reading the adapter and expecting himself to save it back this function
	 * will do nothing.
	 * 
	 * @param ManyToManyAdapter|Model[] $data
	 * @todo Fix to allow for user input
	 * @throws PrivateException
	 */
	public function usrSetData($data)
	{
		if ($data === $this) {
			return;
		}
		
		foreach ($this->children as $child) {
			$role  = $this->getField()->getRole();
			
			#We set the value but do not yet commit it, this will happen whenever the 
			#parent model is written.
			$child->{$role} = null;
		}
		
		if ($data instanceof ManyToManyAdapter) {
			$this->children = $data->toArray();
		} elseif (is_array($data)) {
			$this->children = $data;
		} else {
			throw new PrivateException('Invalid data. Requires adapter or array');
		}
		
		foreach ($this->children as $child) {
			$role  = $this->getField()->getRole();
			
			#We set the value but do not yet commit it, this will happen whenever the 
			#parent model is written.
			$child->{$role} = $this->getModel();
		}
	}
	
	public function getField()
	{
		return $this->field;
	}
	
	public function __toString()
	{
		return "Array()";
	}
}
