<?php namespace spitfire\storage\database\pagination;

use spitfire\storage\database\Query;

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

/**
 * This class is the base for the database query pagination inside of Spitfire.
 * It provides the necessary tools to generate a list of pages inside your 
 * applications so queries aren't able to collapse your system / clients.
 * 
 * By default this class includes a getEmpty method that returns a message when 
 * no results are available. Although it is not a good practice to allow classes
 * perform actions that aren't strictly related to their task. But the improvement
 * on readability gained in Views is worth the change.
 * 
 * @link http://www.spitfirephp.com/wiki/index.php/Database/pagination Related data and tutorials
 * 
 * @todo Somehow this class should cache the counts, so the database doesn't need to read the data every time.
 * @todo This class should help paginating without the use of LIMIT
 */
class Paginator
{
	private $query;
	
	/**
	 *
	 * @var RendererInterface
	 */
	private $renderer;
	private $maxJump = 3;

	/** @var URL */
	private $url;
	private $pageCount;
	private $param     = 'page';
	
	public function __construct(Query $query = null, $name = null) {
		if ($query !== null && $query->getResultsPerPage() < 1) {
			$query->setResultsPerPage(20);
		}
		
		$this->query = $query;
		$this->name  = $name;
		$this->query->setPage((int)$_GET[$this->param][$this->getName()]);
	}
	
	public function getCurrentPage () {
		return $this->query->getPage();
	}
	
	public function getPageCount() {
		if ($this->pageCount !== null) return $this->pageCount;
		
		$rpp     = $this->query->getResultsPerPage();
		$this->query->setResultsPerPage(-1);
		$results = $this->query->count();
		$this->query->setResultsPerPage($rpp);
		
		return $this->pageCount = ceil($results/$rpp);
	}
	
	/**
	 * Returns the paginator URL. The URL will be used to replace the value of the
	 * parameter this class uses to add an entry for this pagination.
	 * 
	 * @return URL
	 */
	public function getURL() {
		if (isset($this->url)) {
			return $this->url;
		} else {
			return $this->url = URL::current();
		}
	}

	/**
	 * @param int $page
	 *
	 * @return URL
	 */
	public function makeURL($page) {
		if (!$this->isValidPageNumber($page)) return null;
		
		$url   = $this->getURL();
		$pages = $url->getParameter($this->param);
		$name  = $this->getName();
		
		if (!is_array($pages)) $pages = Array();
		$pages[$name] = $page;
		$url->setParam($this->param, $pages);
		return $url;
	}
	
	public function getName() {
		return ($this->name !== null)? $this->name : '*';
	}
	
	/**
	 * This function calculates the pages to be displayed in the pagination. It 
	 * calculates the ideal amount of pages to be displayed (based on the max you want)
	 * and generates an array with the numbers for those pages.
	 * 
	 * If you use the default maxJump of 3 you will always receive up to 9 pages.
	 * Those include the first, the last, the current and the three higher and lower
	 * pages. For page 7/20 you will receive (1,4,5,6,7,8,9,10,20).
	 * 
	 * In case the pagination doesn't find enough elements whether on the right or
	 * left it will try to extend this with results on the other one. This avoids
	 * broken looking paginations when reaching the final results of a set.
	 * 
	 * @return array
	 */
	public function getPageNumbers() {
		$pages = collect(range(
			$this->getCurrentPage() - $this->maxJump, 
			$this->getCurrentPage() + $this->maxJump
		));
		
		$pages->push(1);
		$pages->push($this->getPageCount());
		
		return $pages->unique()->sort()->filter(function($e) { return $e > 0 && $e < $this->getPageCount(); });
	}
	
	/**
	 * Sets the URL base that is used for pagination URL's. By default no
	 * URL and page are used for parameters
	 * @param URL $url
	 * @param string $param
	 */
	public function setURL(URL $url, $param) {
		$this->url   = $url;
		$this->param = $param;
	}

	public function __toString() {
		$pages      = $this->getPageNumbers();
		$previous   = 0;
		$current    = $this->getCurrentPage();
		
		if (empty($pages)) {
			return $this->renderer->emptyResultMessage();
		}
		
		
		$_ret = $this->renderer->before();
		$_ret.= $this->renderer->first();
		$_ret.= $this->renderer->previous($current - 1);
		
		foreach ($pages as $page) { 
			if ($page > $previous + 1) { $_ret.= $this->renderer->gap(); }
			$_ret.= $this->renderer->page($page); 
		}
		
		$_ret.= $this->renderer->next($current + 1);
		$_ret.= $this->renderer->last($this->getPageCount());
		
		$_ret.= $this->renderer->after();
		
		return $_ret;
	}
	
}
