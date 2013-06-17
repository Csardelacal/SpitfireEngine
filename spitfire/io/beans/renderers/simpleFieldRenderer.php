<?php

namespace spitfire\io\beans\renderers;

use Model;
use CoffeeBean;
use spitfire\io\beans\BasicField;
use spitfire\io\beans\ChildBean;
use spitfire\io\beans\TextField;
use spitfire\io\beans\LongTextField;
use spitfire\io\beans\FileField;
use spitfire\io\beans\ReferenceField;
use spitfire\io\html\HTMLInput;
use spitfire\io\html\HTMLTextArea;
use spitfire\io\html\HTMLLabel;
use spitfire\io\html\HTMLDiv;
use spitfire\io\html\HTMLOption;
use spitfire\io\html\HTMLSelect;

class SimpleFieldRenderer {
	
	public function renderForm($field) {
		if ($field instanceof ReferenceField) {
			return $this->renderReferencedField($field);
		}
		elseif ($field instanceof ChildBean) {
			return $this->renderChildBean($field);
		}
		elseif ($field instanceof BasicField) {
			return $this->renderBasicField($field);
		}
		else return $field;
		//TODO: Do something real here
	}
	
	public function renderList($field) {
		return __(strip_tags(strval($field)), 100);
	}
	
	public function renderBasicField($field) {
		if ($field instanceof TextField) {
			$input = new HTMLInput('text', $field->getName(), $field->getValue());
			$label = new HTMLLabel($input, $field->getCaption());
			return new HTMLDiv($label, $input, Array('class' => 'field'));
		}
		if ($field instanceof LongTextField) {
			$input = new HTMLTextArea('text', $field->getName(), $field->getValue());
			$label = new HTMLLabel($input, $field->getCaption());
			return new HTMLDiv($label, $input, Array('class' => 'field'));
		}
		elseif ($field instanceof FileField) {
			$input = new HTMLInput('file', $field->getName(), $field->getValue());
			$label = new HTMLLabel($input, $field->getCaption());
			return new HTMLDiv($label, $input, Array('class' => 'field'));
		}
		//TODO: Add more options
		else return $field;
	}
	
	public function renderReferencedField($field) {
		$record = $field->getValue();
		$selected = ($record)? implode('|',$record->getPrimaryData()) : '';
		$select = new HTMLSelect($field->getName(), $selected);
		$label = new HTMLLabel($select, $field->getCaption());
		
		$reference = Model::getInstance($field->getBean()->model)->getField($field->getModelField());
		$query = db()->table($reference->getTarget())->getAll();
		$query->setPage(-1);
		$possibilities = $query->fetchAll();
		
		$select->addChild(new HTMLOption(null, 'Pick'));
		
		foreach ($possibilities as $possibility) {
			$select->addChild(new HTMLOption(implode('|', $possibility->getPrimaryData()), strval($possibility)));
		}
		
		return new HTMLDiv($label, $select, Array('class' => 'field'));
	}
	
	public function renderChildBean($field) {
		$child     = $field->getRelation();
		$childbean = CoffeeBean::getBean($child['bean']);
		$childbean->setParent($field->getBean());
		
		if ($field->getBean()->getRecord()) {
			$query  = $field->getBean()->getRecord()->getChildren($childbean->model, $child['role']);
			$query->setPage(-1);
			$children = $query->fetchAll();
		}
		
		$ret = new HTMLDiv();
		
		if (!empty($children)) {
			foreach ($children as $record) {
				$childbean->setDBRecord($record);
				$fields = $childbean->getFields();
				$ret->addChild($subform = new HTMLDiv());
				$subform->addChild('<h1>' . $record . '</h1>');
				foreach ($fields as $f) 
					if (!($f instanceof ReferenceField && $f->getModelField() == $field->getBean()->getName()))
						$subform->addChild ($this->renderForm($f));
			}
		}
		
		$count = (empty($children))? 0 : count($children);
		do {
			$childbean = CoffeeBean::getBean($child['bean']);
			$childbean->setParent($field->getBean());
			$childbean->setDBRecord(null);
			$fields = $childbean->getFields();
			$ret->addChild('<h1>New record</h1>');
			foreach ($fields as $f) $ret->addChild ($this->renderForm($f));
			$count++;
		} while ($count < $field->getMinimumEntries());
		
		return $ret;
	}
}