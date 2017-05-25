<?php namespace spitfire\core\router;

/**
 * The parameters a route reads out of a server or URI. This allows the application
 * to gracefully manage the parameters and give them over to the callback function
 * that should replace it.
 */
class Parameters
{
	
	/**
	 * This parameters are used to handle named parameters that a URL has handled
	 * from a Server or URI.
	 * 
	 * @var string[]
	 */
	private $parameters = Array();
	
	/**
	 * Some components are greedy (this means, they accept incomplete routes or 
	 * patterns as target for a string), the leftovers are stored in this array
	 * for the application to use at it's own discretion.
	 * 
	 * For example, Spitfire's default rules make heavy use of this tool as a way
	 * to retrieve the controller, action and object from a request.
	 *
	 * @var string[]
	 */
	private $unparsed   = Array();
	
	/**
	 * The extension that was parsed from a URL. This is considered a parameter in
	 * the context of the URL - since the route can provide it as additional information.
	 *
	 * @var string
	 */
	private $extension  = 'php';
	
	/**
	 * Returns the extension that the URL originally had in the system.
	 * 
	 * @return string
	 */
	public function getExtension() {
		return $this->extension;
	}
	
	public function setExtension($extension) {
		$this->extension = $extension;
		return $this;
	}
	
	/**
	 * Imports a set of parameters parsed by the router. Usually, this will be a
	 * single element provided by the route.
	 * 
	 * @param string[] $params
	 */
	public function addParameters($params) {
		$this->parameters = array_merge($this->parameters, $params);
		return $this;
	}
	
	/**
	 * Returns the parameter for the given parameter name. Please note that this 
	 * function may return boolean false and empty strings alike. You can use the
	 * === operator to compare the values and check if the returned one was 
	 * because the data was not set or empty.
	 * 
	 * @param string $name
	 * @return string
	 */
	public function getParameter($name) {
		return (isset($this->parameters[$name]))? $this->parameters[$name] : false;
	}
	
	/**
	 * Returns the list of parameters parsed from the URL path. Please note that 
	 * every parameter is sent as a URL portion and therefore a string.
	 * 
	 * @return string[]
	 */
	public function getParameters() {
		return $this->parameters;
	}
	
	/**
	 * Return the list of URL components that were unaffected by a 'greedy' route.
	 * That means that the parsed route was longer than the parameters parsed the
	 * route parsed.
	 * 
	 * @return string[]
	 */
	public function getUnparsed() {
		return $this->unparsed;
	}
	
	/**
	 * Sets the list of parameters this element holds. This is usually used 
	 * internally to indicate what parameters where passed with the route.
	 * 
	 * @param string[] $parameters
	 */
	public function setParameters($parameters) {
		$this->parameters = $parameters;
	}
	
	/**
	 * Allows to set the list of URL fragments that were sent with the request but
	 * were not parsed by the route. This function is usually called from within
	 * Spitfire's router to indicate the lack of a need for this elements.
	 * 
	 * The content of this URL will be useful to you when defining greedy URLs.
	 * 
	 * @param string[] $unparsed
	 */
	public function setUnparsed($unparsed) {
		$this->unparsed = $unparsed;
		return $this;
	}

	/**
	 * Replaces the parameters contained in this object in a string. This will
	 * look for prefixed versions of a kez and replace them with the key's value
	 * and then return the string.
	 * 
	 * @param string $string The string to search for matches and replace
	 */
	public function replaceInString($string) {
		foreach ($this->parameters as $key => $val) {
			$string = str_replace(':'.$key, $val, $string);
		}

		return $string;
	}

	public function merge($with) {
		$_return = new Parameters();
		$_return->setParameters(array_merge($this->getParameters(), $with->getParameters()));
		return $_return;
	}

}