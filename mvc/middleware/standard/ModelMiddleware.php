<?php namespace spitfire\mvc\middleware\standard;

use spitfire\core\Context;
use spitfire\core\Response;
use spitfire\mvc\middleware\MiddlewareInterface;

/* 
 * The MIT License
 *
 * Copyright 2018 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

class ModelMiddleware implements MiddlewareInterface
{
	
	private $db;
	
	public function __construct($db) {
		$this->db = $db;
	}
	
	/**
	 * 
	 * @param Context $context
	 */
	public function before(Context $context) {
		$controller = new \ReflectionClass($context->controller);
		$action     = $controller->getMethod($context->action);
		$object     = $context->object;
		
		$params     = $action->getParameters();
		
		for ($i = 0; $i < count($params); $i++) {
			if ($params[$i]->getClass() !== \spitfire\Model::class) { continue; }
			
			$table = $this->db->table(substr($params[$i]->getClass()->getName(), 0 - strlen('model')));
			$object[$i] = $table->getById($object[$i]);
		}
		
		$context->object = $object;
	}
	
	public function after(Context $context, Response $response) {
		
	}

}