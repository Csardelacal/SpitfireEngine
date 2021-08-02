<?php namespace spitfire\mvc;

/**
 * This class just ensures that applications have controllers that are explicitly 
 * designed to be served publicly. If your controller does not extend this class,
 * Spitfire will deny access to any requests headed it's way.
 * 
 * While this is not technically required with the new Routers, it provides a good
 * level of enforcement to explicitly declare publicly served content, since
 * otherwise.
 * 
 * Currently, the controllers provide no methods, this means that your application
 * can use any methods for itself. Future revisions may include a property that 
 * provides utils for your controller.
 * 
 * Please note that you should avoid routing any requests to methods starting with 
 * an underscore (except for the magic __invoke method)
 */
abstract class Controller
{
	
}
