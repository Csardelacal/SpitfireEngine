<?php

/**
 * This is the main file of Spitfire, it is in charge of loading
 * system settings (for custom operation) and also of summoning
 * spitfire and loading the adequate controller for every single
 * request. It also makes sure that error logging is sent to
 * terminal / log file instead of to the user.
 * 
 * @package Spitfire
 * @author César de la Cal <cesar@magic3w.com>
 * @copyright 2012 Magic3W - All rights reserved
 */

/* Set error handling directives. AS we do not want Apache / PHP
 * to send the data to the user but to our terminal we will tell
 * it to output the errors. Thanks to this linux command:
 * # tail -f *logfile*
 * We can watch errors happening live. Grepping them can also help
 * filtering.
 */
ini_set("log_errors" , "1");
ini_set("error_log" , "logs/error_log.log");
ini_set("display_errors" , "0");

/* Include settings and Spitfire core.
 */
include 'spitfire/include.php';
include 'bin/settings.php';

/* PACKAGE FUNCTIONS AND DATA_______________________________________
 * Include package specific functions and data.
 */

/* SESSION DEFAULTS AND START_______________________________________
 * This sets basic settings about user sessions and their duration,
 * it enables the user to revisit the system after 24 hours without
 * logging in again.
 * 
 * grab a cookie from the user's machine and detect if the user has
 * a valid one. If he has he can enter the system. This cookie is made
 * of the user's Id and a random number we'll store into our DB. After
 * the time of 6 months the cookie should be removed.
 */
$month = 3600*24*30;
ini_set('session.gc_maxlifetime',$month);
ini_set('session.save_path', "bin/usr/sessions");
session_start();
ini_set('memory_limit', '64M');/**/

/* Call the selected controller with the selected method. */
if (getPath() === true) {
	#Import and instance the controller
	$_controller = 'controller_'.controller;
	$controller = new $_controller();
	#Check if the action is available
	$method = Array($controller, action);
	#Create a view-controller model
	$GLOBALS['_SF_ViewData'] = array();
	#Fire!
	if (is_callable($method)) call_user_func_array($method, Array(object, $_GET));
	else throw new publicException(E_PAGE_NOT_FOUND, 404);

	$v = new view(controller, action);
	$v->render();

} else throw new privateException('getPath failed.', 0);
