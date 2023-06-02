<?php namespace spitfire\core\kernel;

use spitfire\contracts\core\kernel\InitScriptInterface;
use spitfire\contracts\core\kernel\ConsoleKernelInterface;
use spitfire\contracts\core\kernel\KernelInterface;
use spitfire\contracts\core\kernel\WebKernelInterface;
use spitfire\provider\Container;

class KernelFactory
{
	
	/**
	 * @var Container
	 */
	private Container $provider;
	
	public function __construct(Container $provider)
	{
		$this->provider = $provider;
	}
	
	/**
	 * 
	 * @template T of KernelInterface
	 * @param T $kernel
	 * @return T
	 */
	public function boot(KernelInterface $kernel) : KernelInterface
	{
		$interfaces = [
			ConsoleKernelInterface::class,
			WebKernelInterface::class
		];
		
		$this->provider->set(KernelInterface::class, $kernel);
		
		/**
		 * Spitfire provides three interfaces that an application can depend on. The generic
		 * kernel, the  console and the web kernels. The application can depend on those to
		 * construct behavior.
		 */
		foreach ($interfaces as $classname) {
			if ($kernel instanceof $classname) {
				$this->provider->set($classname, $kernel);
			}
		}
		
		/**
		 * Loop over the kernel's init script and execute them, making the kernel function.
		 */
		foreach ($kernel->initScripts() as $script) {
			$_init = new $script($kernel);
			assert($_init instanceof InitScriptInterface);
			$_init->exec();
		}
		
		/**
		 * Return the kernel, so the application can work as expected.
		 */
		return $kernel;
	}
}
