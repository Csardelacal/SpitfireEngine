<?php

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use spitfire\App;
use spitfire\collection\Collection;
use spitfire\core\http\URL;
use spitfire\core\config\Configuration;
use spitfire\core\kernel\KernelInterface;
use spitfire\cli\Console;
use spitfire\core\Environment;
use spitfire\core\exceptions\FailureException;
use spitfire\exceptions\ApplicationException;
use spitfire\io\request\Request;
use spitfire\io\media\FFMPEGManipulator;
use spitfire\io\media\GDManipulator;
use spitfire\io\media\ImagickManipulator;
use spitfire\io\media\MediaDispatcher;
use spitfire\locale\Domain;
use spitfire\locale\DomainGroup;
use spitfire\locale\Locale;
use spitfire\SpitFire;
use spitfire\storage\database\Settings;
use spitfire\storage\objectStorage\DriveDispatcher;
use spitfire\storage\objectStorage\NodeInterface;
use spitfire\validation\rules\RegexValidationRule;
use spitfire\validation\ValidationException;
use spitfire\validation\ValidationRule;

/**
 * This is a quick hand method to use Spitfire's main App class as a singleton.
 * It allows you to quickly access many of the components the framework provides
 * to make it easier to read and maintain the code being created.
 * 
 * @staticvar type $sf
 * @return \spitfire\SpitFire
 */
function spitfire() {
	static $sf = null;
	
	if ($sf !== null) { 
		return $sf; 
	} else {
		$sf = new SpitFire();
		return $sf;
	}
}

/**
 * 
 * Registers a new Application in Spitfire, allowing it to handle requests directed
 * to it.
 * 
 * @param string $name The name of the Application
 * @param string $namespace The namespace in which the requests will be sent to 
 *             the application.
 * @return App The App created by the system, use this to pass parameters and 
 *             configuration to the application.
 */
function app($name, $namespace) {
	$appName = $name . 'App';
	$app = new $appName(APP_DIRECTORY . $name . DIRECTORY_SEPARATOR, $namespace);
	spitfire()->registerApp($app, $namespace);
	return $app;
}

/**
 * Ths function will boot a kernel, instancing it and executing the necessary scripts to
 * initialize it.
 * 
 * @param class-string<KernelInterface> $kernel The name of the kernel to boot
 * @return KernelInterface
 * @throws ApplicationException
 */
function boot(string $kernel) : KernelInterface
{
	$reflection = new ReflectionClass($kernel);
	$provider   = spitfire()->provider();
	
	/**
	 * Ensure that the kernel is a kernel. If it's not a kernel, the application can't
	 * use it and it will most definitely malfunction.
	 */
	assert($reflection->isSubclassOf(KernelInterface::class), 'Cannot boot non-kernel class');
	
	/**
	 * Instance the new kernel. Kernels must be able to be instanced with minimum
	 * available configuration and set up. At this point no service providers or 
	 * similar are running.
	 * 
	 * We use the service provider for this, allowing the developer to potentially
	 * override the kernel with custom logic.
	 */
	$instance = $provider->get($kernel);
	
	/**
	 * Loop over the kernel's init script and execute them, making the kernel function.
	 */
	foreach ($instance->initScripts() as $script) {
		(new $script($instance))->exec();
	}
	
	/**
	 * Set the kernel as the currently running kernel in the service container so the app
	 * can find the kernel if needed
	 */
	$provider->set(KernelInterface::class, $kernel);
	
	/**
	 * Return the kernel, so the application can work as expected.
	 */
	return $instance;
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
 * @param bool $condition If the condition is true, the code will continue being executed
 * @param string|Closure|Exception $failure 
 * @return void
 */
function assume(bool $condition, $failure) : void
{
	/*
	 * If the condition is met, the function doesn't do anything. It just returns and allows
	 * the application to continue.
	 */
	if ($condition) { return; }
	
	/**
	 * Otherwise, we need to stop the execution. This should be done in the closure, but if the
	 * user does not raise any exception in the closure, our code will do.
	 */
	if ($failure instanceof Closure) { 
		$failure(); 
		throw new Exception('Failed to meet assumption');
	}
	
	/**
	 * If the user provided an exception instance, we throw the provided exception.
	 */
	if ($failure instanceof Exception) { throw $failure; }
	
	/**
	 * The last case (if the failure code was a string) will instance a new exception without message,
	 * this is the most common way to use this, since it will be the mechanism activated when using
	 * the ::class magic constant.
	 */
	throw new $failure();
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
 * Shorthand function to create / retrieve the model the application is using
 * to store data. We could consider this a little DB handler factory.
 *
 * @return \spitfire\storage\database\DB
 */
function db() 
{
	static $db = null;
	
	#If we're requesting the standard driver and have it cached, we use this
	if ($db !== null) { return $db; }
	
	#If no options were passed, we try to fetch them from the configuration
	$driver   = config('storage.database.driver', 'mysqlpdo');
	$settings = Settings::fromURL(config('storage.database.' . $driver));
	
	#Instantiate the driver
	$driver = 'spitfire\storage\database\drivers\\' . $settings->getDriver() . '\Driver';
	$driver = new $driver($settings);
	
	#If no options were provided we will assume that this is the standard DB handler
	return $db = $driver;
}

/**
 * Escapes quotes from HTML. This will replace both ' and " with &#039; and &quot; 
 * respectively. This does not escape the other HTML entities, for that refer to
 * _e();
 * 
 * Usually you should use this function like _q(_e($str));
 * 
 * @param type $str
 * @return type
 */
function _q($str) {
	return \spitfire\utils\Strings::quote($str);
}

/**
 * Escapes the HTML from a string. This function will not escape the quotes, please
 * refer to the _q wrapper for that.
 * 
 * @see _q()
 * @param string $str
 * @return string
 */
function _e($str) {
	return \spitfire\utils\Strings::escape($str);
}

/**
 * Helper to convert URLs in text to actual links. By default, the application will
 * translate the URL to a pure HTML link, but you can pass a callable to the system
 * to change the output.
 * 
 * @param string $str
 * @param Closure|callable $cb
 * @return string
 */
function _u($str, $cb = null) {
	return \spitfire\utils\Strings::urls($str, $cb);
}

/**
 * Returns HTML escaped string and if desired it adds ellipsis. If the string is
 * numeric it will reduce unnecessary decimals.
 * 
 * @param String $str
 * @param int $maxlength
 * @return String
 */
function __($str, $maxlength = false) {
	if ($maxlength) { $str = \spitfire\utils\Strings::ellipsis ($str, $maxlength); }
	return _u($str);
}

/**
 * Translation helper.
 * 
 * Depending on the arguments this function receives, it will have one of several
 * behaviors.
 * 
 * If the first argument is a spitfire\locale\Locale and the function receives a
 * optional second parameter, then it will assign the locale to either the global
 * domain / the domain provided in the second parameter.
 * 
 * Otherwise, if the first parameter is a string, it will call the default locale's
 * say method. Which will translate the string using the standard locale.
 * 
 * If no parameters are provided, this function returns a DomainGroup object,
 * which provides access to the currency and date functions as well as the other
 * domains that the system has for translations.
 * 
 * @return string|DomainGroup 
 */
function _t() {
	static $domains = null;
	
	#If there are no domains we need to set them up first
	if ($domains === null) { $domains = new DomainGroup(); }
	
	#Get the functions arguments afterwards
	$args = func_get_args();
	
	#If the first parameter is a Locale, then we proceed to registering it so it'll
	#provide translations for the programs
	if (isset($args[0]) && $args[0] instanceof Locale) {
		$locale = array_shift($args);
		$domain = array_shift($args);
		
		return $domains->putDomain($domain, new Domain($domain, $locale));
	}
	
	#If the args is empty, then we give return the domains that allow for printing
	#and localizing of the data.
	if (empty($args)) {
		return $domains;
	}
	
	return call_user_func_array(Array($domains->getDefault(), 'say'), $args);
}

function console() {
	static $console = null;
	
	if ($console === null) {
		$console = new Console();
	}
	
	return $console;
}

/**
 * Tests a value against a provided set of rules, if the 
 */
function validate($value, $rules) : void
{
	
	$messages = collect();
	
	collect($rules)
		->each(function ($e) {
			if ($e instanceof ValidationRule) { return $e; }
			if ($e instanceof Closure) { return new ClosureValidationRule($e); }
			if (is_string($e)) { return new RegexValidationRule($e, 'Invalid format'); }
			throw new ValidationException('Invalid validation rules');
		})
		->each(function (ValidationRule $rule) use ($messages, $value) {
			$messages->push($rule->test($value));
		})->filter();
	
	if (!$messages->isEmpty()) {
		throw new ValidationException('Validation failure', 2102011631, $messages->toArray());
	}
}

/**
 * Retrieves the current path from the request. This will retrieve the path 
 * without query string or document root.
 * 
 * @see http://www.spitfirephp.com/wiki/index.php/NgiNX_Configuration For NGiNX setup
 * @return string
 */
function getPathInfo() {
	$base_url = spitfire()->baseUrl();
	list($path) = explode('?', substr($_SERVER['REQUEST_URI'], strlen($base_url)));
	
	if (strlen($path) !== 0) { return $path; }
	else                     { return  '/';  }
}

function _def(&$a, $b) {
	return ($a)? $a : $b;
}

/**
 * This function is a shorthand for "new Collection" which also allows fluent
 * usage of the collection in certain environments where the PHP version still
 * limits that behavior.
 * 
 * @param mixed $elements
 * @return Collection
 */
function collect($elements = []) {
	return new Collection($elements);
}


/**
 * Creates a new URL. Use this class to generate dynamic URLs or to pass
 * URLs as parameters. For consistency (double base prefixes and this
 * kind of misshaps aren't funny) use this object to pass or receive URLs
 * as paramaters.
 * 
 * Please note that when passing a URL that contains the URL as a string like
 * "/hello/world?a=b&c=d" you cannot pass any other parameters. It implies that
 * you already have a full URL.
 * 
 * You can pass any amount of parameters to this class,
 * the constructor will try to automatically parse the URL as good as possible.
 * <ul>
 *		<li>Arrays are used as _GET</li>
 * 	<li>App objects are used to identify the namespace</li>
 *		<li>Strings that contain / or ? will be parsed and added to GET and path</li>
 *		<li>The rest of strings will be pushed to the path.</li>
 * </ul>
 */
function url() {
	#Get the parameters the first time
	$params = func_get_args();

	#Get the controller, and the action
	$controller = null;
	$action     = null;
	$object     = Array();

	#Get the object
	while(!empty($params) && (!is_array(reset($params)) || (!$controller && $app->getControllerLocator()->hasController(reset($params))))) {
		if     (!$controller) { $controller = array_shift($params); }
		elseif (!$action)     { $action     = array_shift($params); }
		else                  { $object[]   = array_shift($params); }
	}
	
	#Get potential environment variables that can be used for additional information
	#like loccalization
	$get          = array_shift($params);
	$environment  = array_shift($params);
	
	return new URL($controller, $action, $object, 'php', $get, $environment);
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
function clamp($min, $bias, $max) {
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
function within($min, $val, $max) {
	trigger_error('Function within() is deprecated, rename it to clamp()', E_USER_DEPRECATED);
	return clamp($min, $val, $max);
}

function media() {
	static $dispatcher = null;
	
	if (!$dispatcher) {
		$dispatcher = new MediaDispatcher();
		$dispatcher->register('image/png', new GDManipulator());
		$dispatcher->register('image/jpg', new GDManipulator());
		$dispatcher->register('image/webp', new GDManipulator());
		$dispatcher->register('image/psd', new ImagickManipulator());
		$dispatcher->register('image/gif', new FFMPEGManipulator());
		$dispatcher->register('video/mp4', new FFMPEGManipulator());
		$dispatcher->register('video/quicktime', new FFMPEGManipulator());
		$dispatcher->register('image/jpeg', new GDManipulator());
		$dispatcher->register('image/vnd.adobe.photoshop', new ImagickManipulator());
	}
	
	return $dispatcher;
}

/**
 * 
 * @staticvar type $dispatcher
 * @param type $uri
 * @return DriveDispatcher|NodeInterface
 */
function storage() {
	
	static $dispatcher = null;
	
	if (!$dispatcher) {
		$dispatcher = new \spitfire\storage\objectStorage\DriveDispatcher();
		$dispatcher->init();
	}
	
	return $dispatcher;
}

function mime($file) {
	if (function_exists('mime_content_type')) { return mime_content_type($file); }
	else { return explode(';', system(sprintf('file -bi %s', escapeshellarg(realpath($file)))))[0]; }
}

/**
 * Allows the application to manage a central event dispatching system, instead 
 * of relying on every component to build a custom one. If your component doesn't
 * wish to share it's hooks and plugins please @see Target
 * 
 * @staticvar \spitfire\core\event\EventDispatcher $dispatcher
 * @return \spitfire\core\event\EventDispatcher
 */
function event(\spitfire\core\event\Event$event = null) {
	static $dispatcher = null;
	
	if ($dispatcher === null) {
		$dispatcher = new \spitfire\core\event\EventDispatcher();
		\spitfire\core\event\RecipeLoader::import();
	}
	
	if ($event !== null) {
		$dispatcher->dispatch($event);
		return $event;
	}
	
	return $dispatcher;
}

/**
 * 
 * @staticvar \spitfire\io\lock\FileLockFactory $handler
 * @param \spitfire\io\lock\FileLockFactory $set
 * @return \spitfire\io\lock\FileLockFactory
 */
function lock($set = null) {
	static $handler;
	
	if ($set) { $handler = $set; }
	if (!$handler) { $handler = new \spitfire\io\lock\FileLockFactory(dirname(dirname(__FILE__)) . '/bin/usr/lock'); }
	
	return $handler;
}

function basedir($set = null) 
{
	static $override = null;
	if ($set) { $override = $set; }
	
	/*
	 * Three dirnames for:
	 * 1. engine folder
	 * 2. spitfire folder
	 * 3. vendor folder
	 */
	return $override?: rtrim(dirname(dirname(dirname(__DIR__))), '\/') . DIRECTORY_SEPARATOR;
}

/**
 * 
 * 
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
		if (!file_exists($manifest)) { throw new \spitfire\exceptions\ApplicationException(sprintf('No asset manifest for %s', $scope)); }
		
		/*
		 * If the key exists, we generate a manifest object.
		 */
		$scopes[$scope] = new \spitfire\cache\MemoryCache(json_decode(file_get_contents($manifest)));
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
	
	/*
	 * Look for the asset inside the memory cache. This allows us to make use of 
	 * the versioned files without having to look them up every single time.
	 */
	$asset = $scopes[$scope]->get($name, function ($name) { 
		throw new \spitfire\exceptions\user\NotFoundException(sprintf('Asset %s is not available', $name));
	});
	
	return '/' . implode('/', array_filter([ 
			trim($base, '/'),
			trim($scope?? '', '/'),
			trim($asset, '/')
		])
	);
}

/**
 * Convenience method for accessing the TaskFactory for deferring tasks. You can
 * pass a task class to be deferred, a bunch of settings and a time to execute
 * this task at.
 * 
 * Please refer to the documentation to enable the daemon required to process the 
 * queue.
 * 
 * @staticvar spitfire\defer\TaskFactory $async
 * @param string $task
 * @param mixed $options
 * @param int $defer
 * @param int $ttl
 * @return void
 */
function defer(string $task, $options, int $defer = 0, int $ttl = 10) : void
{
	static $async = null;
	
	if (!$async) {
		$async = spitfire()->provider()->get(\spitfire\defer\TaskFactory::class);
	}
	
	$async->defer($task, $options, $defer, $ttl);
}

/**
 * Returns an application configuration. Note that configuration in Spitfire is tiered,
 * configuration and environments.
 * 
 * You as the developer should establish the configuration so the components you add to
 * your application work as expected, and should expose the environment to the person
 * deploying the application so they can customize the behavior.
 * 
 * 
 * @param string $key The key in the configuration
 * @param mixed  $fallback The value to return if the configuration does not contain the key
 * @return mixed The data for this configuration entry
 */
function config($key, $fallback = null)
{
	return spitfire()->provider()->get(Configuration::class)->get($key, $fallback);
}

/**
 * This function should ONLY be invoked from the config files. This data is not cached, and therefore
 * not always available during runtime.
 * 
 * Reads a value from the current environment.
 * 
 * @param string Set the environment, or look up the current env
 * @return string|null
 */
function env(string $param) :? string
{
	$provider = spitfire()->provider();
	
	/**
	 * If no parameter was given, the application would be unable to work with it.
	 */
	assert($param !== '');
	
	/**
	 * If the user is performing an empty lookup, or the environment has not yet been
	 * set, the application should fail.
	 */
	if (!$provider->has(Environment::class)) {
		throw new ApplicationException('Environment was read before it was loaded');
	}
	
	/**
	 * @var Environment
	 */
	$env = $provider->get(Environment::class);
	
	return $env->read($param);
}

/**
 * Allows the application to reliably determine whether it is running in a console or 
 * on a web server environment. 
 */
function cli() : bool
{
	return php_sapi_name() === 'cli';
}
