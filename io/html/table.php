<?php

namespace spitfire\io\html;

class HTMLTable extends HTMLElement
{
	private $rows;
	
	public function setHeaders()
	{
		$row = new HTMLTableRow();
		$headers = func_get_args();
		foreach ($headers as $h) {
			$row->putCell($h);
		}
		$this->putRow($row);
	}
	
	public function putRow($row)
	{
		$this->rows[] = $row;
	}
	
	public function getChildren()
	{
		return  $this->rows;
	}
	
	public function getParams()
	{
		return array('cellspacing' => 0);
	}
	
	public function getTag()
	{
		return 'table';
	}
}
