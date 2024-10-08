<?php
/*
 *
 * Copyright (C) 2023-2023 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-13 01  USA
 *
 */


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use spitfire\collection\Collection;
use spitfire\core\config\Configuration;
use spitfire\contracts\core\kernel;
use spitfire\core\exceptions\FailureException;
use spitfire\core\Response;
use spitfire\core\http\URLBuilder;
use spitfire\core\kernel\KernelFactory;
use spitfire\exceptions\ApplicationException;
use spitfire\exceptions\user\NotFoundException;
use spitfire\io\stream\Stream;
use spitfire\model\ModelFactory;
use spitfire\provider\NotFoundException as ProviderNotFoundException;
use spitfire\SpitFire;
use spitfire\storage\DriveDispatcher as StorageDriveDispatcher;

/**
 * This is a quick hand method to use Spitfire's main App class as a singleton.
 * It allows you to quickly access many of the components the framework provides
 * to make it easier to read and maintain the code being created.
 *
 * @staticvar type $sf
 * @return \spitfire\SpitFire
 */
function spitfire()
{
	static $sf = null;
	
	if ($sf !== null) {
		return $sf;
	} else {
		$sf = new SpitFire();
		return $sf;
	}
}

/**
 * A very basic function to dispatch a response to the server environment. This sets the
 * status code for the response, dispatches the headers that the application set, and finally
 * emits the body.
 *
 * @param ResponseInterface $message
 * @return void
 */
function emit(ResponseInterface $message) : void
{
	http_response_code($message->getStatusCode());
	
	foreach ($message->getHeaders() as $name => $values) {
		foreach ($values as $value) {
			header(sprintf('%s: %s', $name, $value), false);
		}
	}
	
	echo $message->getBody();
}

/**
 * Ths function will boot a kernel, instancing it and executing the necessary scripts to
 * initialize it.
 *
 * @throws ProviderNotFoundException
 * @template T of kernel\KernelInterface
 * @param T $kernel
 * @return T
 */
function boot(kernel\KernelInterface $kernel) : kernel\KernelInterface
{
	return spitfire()->provider()->get(KernelFactory::class)->boot($kernel);
}

/**
 * Raises an exception whenever the application runs into an scenario that was not
 * expected. This function functions in a very similar manner to an assertion (with
 * the exception of this being executed in production).
 *
 * The second parameter contains the name of the exception to be raised on failure,
 * an exception instance or a closure to be executed, which should then either end
 * execution or throw an exception itself.
 *
 * This is intended to reduce the boilerplate on code that looks like this:
 * if (!$condition) { throw new Exception(); }
 *
 * The code becomes a bit more readable and drops the negation, making it look like this:
 * assume($condition, Exception::class);
 *
 * Overall reducing the strain of visually analyzing the code.
 *
 * @throws AssertionError
 * @param bool $condition If the condition is true, the code will continue being executed
 * @param string|callable():string $failure
 * @return void
 */
function assume(bool $condition, $failure) : void
{
	/*
	 * If the condition is met, the function doesn't do anything. It just returns and allows
	 * the application to continue.
	 */
	if ($condition) {
		return;
	}
	
	/**
	 * Otherwise, we need to stop the execution. This should be done in the closure, but if the
	 * user does not raise any exception in the closure, our code will do.
	 */
	if (is_callable($failure)) {
		$failure = $failure();
	}
	
	/**
	 * The last case (if the failure code was a string) will instance a new exception without message,
	 * this is the most common way to use this, since it will be the mechanism activated when using
	 * the ::class magic constant.
	 *
	 * If the exception class does not exist, or is not a subclass of exception, we just throw an application
	 * exception to let the user know that something went wrong.
	 */
	throw new AssertionError($failure);
}

/**
 * @template T
 * @param callable():T $something
 * @return T
 */
function attempt(callable $something)
{
	try {
		return $something();
	}
	catch (Exception $e) {
		trigger_error($e->getMessage(), E_USER_ERROR);
	}
}

/**
 * This function allows the developer to stop the execution of the application.
 * It raises a failure exception that will prevent the code from continuing and
 * display a message to the end user that explains the situation.
 *
 * Whenever your application runs into a more specific issue, you should avoid
 * using this and instead raise a custom exception that offers the user support
 * to resolve the issue or additional information.
 *
 *
 * @param int $status
 * @param string $message
 * @return void
 * @throws FailureException
 */
function fail(int $status, string $message = '') : void
{
	throw new FailureException($message, $status);
}

/**
 * @template T
 * @param T|null $subject
 * @return T
 * @throws AssertionError
 */
function notnull($subject)
{
	if ($subject === null) {
		throw new AssertionError('Variable cannot be null');
	}
	
	return $subject;
}

/**
 * Shorthand function to create / retrieve the model the application is using
 * to store data. We could consider this a little DB handler factory.
 *
 * @return ModelFactory
 */
function db()
{
	try {
		return spitfire()->provider()->get(ModelFactory::class);
	}
	catch (ProviderNotFoundException) {
		trigger_error('db() invoked before Provider was ready', E_USER_ERROR);
	}
}

/**
 * This function provides a shorthand way of creating a response to a request, this
 * is very useful in combination with the view() function, allowing you to respond
 * from a controller with something like this:
 *
 * `return response(view('home'));`
 *
 * Or something along the lines of:
 *
 * `return response(view('notfound'), 404);
 *
 * @param StreamInterface $stream
 * @param int $code
 * @param string[][] $headers
 * @throws InvalidArgumentException
 * @return Response
 */
function response(StreamInterface $stream, int $code = 200, array $headers = []) : Response
{
	return new Response($stream, $code, '', $headers);
}

/**
 * The redirect function is a helper to quickly issue a response that causes a redirection,
 * this avoids having to create an explicit redirection response whenever the application
 * needs to redirect the user.
 *
 * Redirections are a clearly distinct type of response, and naming them in the code makes
 * our code more readable and scannable, since the person reading or refactoring the code
 * will immediately be familiar with the behavior of the application without the need to
 * read into the parameters of the function to understand it's behavior (like they would have
 * to with the response function).
 *
 * Note: This function does not perform validation of the URL the user is sent to. Please make
 * sure to not make the location parameter user controllable.
 *
 * @param string $location
 * @param int $code
 * @throws InvalidArgumentException
 * @throws RuntimeException
 * @return Response
 */
function redirect(string $location, int $code = 302) : Response
{
	/**
	 * The redirection cannot be performed unless the status code is a valid
	 * redirection code.
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status#redirection_messages
	 *
	 * While 300 is technically a redirection, it's a redirection that requires
	 * a body to work properly. This is not something this method supports.
	 *
	 * Please note the assertion. In a production environment this test is not
	 * performed. The status code should not be user controllable.
	 */
	assert(in_array($code, [301, 302, 303, 307, 308]));
	
	/**
	 * Create a new redirection response and send it to the user.
	 */
	return new Response(
		Stream::fromString('Redirecting...'),
		$code,
		'',
		[
			'location' => [ $location ]
		]
	);
}

/**
 * Retrieves the current path from the request. This will retrieve the path
 * without query string or document root.
 *
 * @see http://www.spitfirephp.com/wiki/index.php/NgiNX_Configuration For NGiNX setup
 * @throws ApplicationException
 * @return string
 */
function getPathInfo()
{
	assert(is_string($_SERVER['REQUEST_URI']));
	
	$base_url = spitfire()->baseUrl();
	list($path) = explode('?', substr($_SERVER['REQUEST_URI'], strlen($base_url)));
	
	if (strlen($path) !== 0) {
		return $path;
	}
	else {
		return  '/';
	}
}

/**
 * This function is a shorthand for "new Collection" which also allows fluent
 * usage of the collection in certain environments where the PHP version still
 * limits that behavior.
 *
 * @template T
 * @param T[] $elements
 * @return Collection<T>
 */
function collect($elements = [])
{
	return Collection::fromArray($elements);
}


/**
 * Returns the URLBuilder that helps you accessing all the functions that you need
 * to manipulate and generate URLs in Spitfire.
 *
 * @return URLBuilder
 */
function url() : URLBuilder
{
	try {
		return spitfire()->provider()->get(URLBuilder::class);
	}
	catch (ProviderNotFoundException $e) {
		trigger_error($e->getMessage(), E_USER_ERROR);
	}
}

/**
 * The clamp function is a math function that receives three arguments, a minimum,
 * a bias and a maximum. Clamp will either return the bias or the closest value
 * whenever the bias is outside the range of maximum and minimum.
 *
 * The first and the last parameter delimit the range. The second parameter is
 * the bias being tested.
 *
 * <code>within(1,  50, 100); //Outputs:  50</code>
 * <code>within(1, 500, 100); //Outputs: 100</code>
 * <code>within(1, -50, 100); //Outputs:   1</code>
 *
 * @param number $min
 * @param number $bias
 * @param number $max
 * @return number
 */
function clamp($min, $bias, $max)
{
	return min(max($min, $bias), $max);
}

/**
 * This is the deprecated naming for the clamp function.
 *
 * @deprecated since version 0.1-dev 2020-09-29
 * @see clamp
 * @param number $min
 * @param number $val
 * @param number $max
 * @return number
 */
function within($min, $val, $max)
{
	trigger_error('Function within() is deprecated, rename it to clamp()', E_USER_DEPRECATED);
	return clamp($min, $val, $max);
}

/**
 *
 * @return StorageDriveDispatcher
 */
function storage()
{
	try {
		return spitfire()->provider()->get(StorageDriveDispatcher::class);
	}
	catch (ProviderNotFoundException $e) {
		trigger_error($e->getMessage(), E_USER_ERROR);
	}
}

function mime(string $file) : string|false
{
	assert(file_exists($file), sprintf('File %s is not a file', $file));
	
	if (function_exists('mime_content_type')) {
		return mime_content_type($file);
	}
	else {
		$realpath = realpath($file);
		assert($realpath !== false);
		
		$result = system(sprintf('file -bi %s', escapeshellarg($realpath)));
		assert($result !== false);
		
		return explode(';', $result)[0];
	}
}

/**
 *
 * @todo Move this code into URLBuilder::asset and invoke it from there.
 * @param string $name
 * @param string $scope A directory to scope this to. If an application builds it's
 *                      own assets and generates a manifest file, it will be located here.
 *                      The mix-manifest file is required to be located in the root
 *                      of this directory.
 *
 *                      Note that the scope must be relative to the public directory,
 *                      if this is not the case, the system will generate bad URLs
 *                      or fail to locate the manifests.
 *
 *                      If, for example, your application publishes it's assets to the
 *                      assets/vendor/myapp older within public folder, this is exactly
 *                      the string it should pass
 * @return string
 * @throws ApplicationException
 * @throws AssertionError
 */
function asset(string $name, string $scope = 'assets/') : string
{
	static $scopes = [];
	
	if (!isset($scopes[$scope])) {
		/*
		 * Check if the manifest file exists. In most cases, laravel-mix will generate
		 * a simple dictionary that contains an entry and the exact same file name
		 * as the alue for that entry. But sometimes it contains disambiguations.
		 */
		$manifest = spitfire()->locations()->public(rtrim($scope, '\/') . DIRECTORY_SEPARATOR . 'mix-manifest.json');
		if (!file_exists($manifest)) {
			throw new \spitfire\exceptions\ApplicationException(sprintf('No asset manifest for %s', $scope));
		}
		
		$content = file_get_contents($manifest);
		assert($content !== false);
		/*
		 * If the key exists, we generate a manifest object.
		 */
		$scopes[$scope] = json_decode($content);
	}
	
	/**
	 * It's possible that the developer has set an asset location to serve static assets
	 * from. This may be a CDN or a separate domain (to protect the users against origin
	 * based attacks - this seems to be an issue with flash files).
	 *
	 * @see https://secure.phabricator.com/book/phabricator/article/configuring_file_domain/
	 *
	 * The location defined in `app.assets.location` needs to point to a mirror of the app's
	 * public directory.
	 */
	$base = config('app.assets.location', SpitFire::baseUrl());
	
	assert(is_string($base));
	
	/*
	 * Look for the asset inside the memory cache. This allows us to make use of
	 * the versioned files without having to look them up every single time.
	 */
	assume(isset($scopes[$scope][$name]), fn() => throw new NotFoundException);
	
	$asset = $scopes[$scope][$name];
	
	return '/' . implode('/', array_filter([
			trim($base, '/'),
			trim($scope, '/'),
			trim($asset, '/')
		]));
}

/**
 * Returns an application configuration. Note that configuration in Spitfire is tiered,
 * configuration and environments.
 *
 * You as the developer should establish the configuration so the components you add to
 * your application work as expected, and should expose the environment to the person
 * deploying the application so they can customize the behavior.
 *
 * @param string $key The key in the configuration
 * @param mixed  $fallback The value to return if the configuration does not contain the key
 * @return mixed The data for this configuration entry
 */
function config($key, $fallback = null) : mixed
{
	try {
		return spitfire()->provider()->get(Configuration::class)->get($key, $fallback);
	}
	catch (ProviderNotFoundException) {
		trigger_error('config() invoked before Provider was ready', E_USER_ERROR);
	}
}

/**
 * This function should ONLY be invoked from the config files. This data is not cached, and therefore
 * not always available during runtime.
 *
 * Reads a value from the current environment.
 *
 * @param string $param Set the environment, or look up the current env
 * @return string|null
 */
function env(string $param, string $fallback = null) :? string
{
	/**
	 * If no parameter was given, the application would be unable to work with it.
	 */
	assert($param !== '');
	
	if (!array_key_exists($param, $_ENV)) {
		return $fallback;
	}
	
	assert(is_string($_ENV[$param]));
	
	return $_ENV[$param];
}

/**
 * Allows the application to reliably determine whether it is running in a console or
 * on a web server environment.
 */
function cli() : bool
{
	return php_sapi_name() === 'cli';
}

/**
 *
 * @template IN of object
 * @template OUT of object
 * @param IN $ctx
 * @param callable(IN):OUT $do
 * @return OUT
 */
function scope(object $ctx, callable $do)
{
	return $do($ctx);
}

/**
 * Redirects the output of a function to a given stream, and then returns the
 * return value of given function
 * 
 * @template RETURN
 * @param callable():RETURN $fn
 * @return RETURN
 */
function redirectOutput(StreamInterface $stream, $fn) : mixed
{
	/**
	 * We create an output buffer that proceeds to write everything to the stream
	 * we provided. This allows the application to send the output to StdErr, a file
	 * or anything like that.
	 *
	 * We pass a two to the chunk size to cause the buffer to always output the data
	 * and use it as a redirection instead of a buffer.
	 */
	ob_start(fn($str) => $stream->write($str)? '' : '', 2);
	
	/**
	 * Call the function that could be producing output. All the output will be sent
	 * to the buffer and stream.
	 */
	$_return = $fn();
	
	/**
	 * Terminate and flush the buffer, then return the value we provided.
	 */
	ob_end_flush();
	return $_return;
}
