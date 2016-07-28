<?php

	date_default_timezone_set('GMT');

	/**
	 * PHP Road
	 *
	 * PHP application framework
	 *
	 * @package		PHPRoad
	 * @author		Aleksey Bobkov, Andy Chentsov
	 * @since		Version 1.0
	 * @filesource
	 */

	/**
	 * This script initializes the PHP Road library
	 *
	 * @package		PHPRoad
	 * @category	PHPRoad
	 * @author		Aleksey Bobkov
	 */

	/*
	 * Init the constants
	 */

	/**
	 * Determines the PHP Road library version.
	 */
	define( "PHPR_VERSION", "1.0.0" );

	/**
	 * Determines the extension of the PHP files.
	 */
	define( "PHPR_EXT", "php" );

	/**
	 * Determines the path to the PHP Road system directory.
	 */
	define( "PATH_SYSTEM", str_replace("\\", "/", realpath(dirname(__FILE__)."/..") ) );

	if ( !strlen( trim($applicationRoot) ) )
		$applicationRoot = dirname($bootstrapPath);
	
	$path_app = str_replace("\\", "/", realpath($applicationRoot) );
	
	/*
	 * Load initial configuration
	 */
	
	$configScript = realpath( $path_app."/"."config/config.php" );
	if ( $configScript )
		include $configScript;

	/**
	 * Determines the path to the Application root directory.
	 */
	define( "PATH_APP", $path_app );

	require PATH_SYSTEM."/system/phpr.php";

	/*
	 * Setup the class loading
	 */

	require PATH_SYSTEM."/system/classloader.php";

	Phpr::$classLoader = new Phpr_ClassLoader();
	
	/**
	 * Loads a class with the specified name. 
	 * If the class requested is not found, the function attempts to invoke the Phpr_autoload($ClassName) function.
	 * Declare the Phpr_autoload function on the application code to allow the application classes to be loaded on demand.
	 * @param string $class_name Specifies the class name to load
	 */
	function Phpr_InternalAutoload($name) 
	{
		if(!Phpr::$classLoader->load($name) && function_exists("Phpr_autoload")) 
		{
			Phpr_autoload($name);
		}
	}
	
	if(function_exists("spl_autoload_register")) 
	{
		spl_autoload_register("Phpr_InternalAutoload");
	} 
	else 
	{
		function __autoload($name) 
		{
			Phpr_InternalAutoload($name);
		}
	}

	/*
	 * Turn off the magic quotes
	 */

	if (function_exists('set_magic_quotes_runtime'))
		@set_magic_quotes_runtime(0);

	/*
	 * Initialize the error handling engine
	 */

	error_reporting( E_ALL );

	require PATH_SYSTEM."/system/exceptions.php";

	/*
	 * Initialize the events object
	 */

	Phpr::$events = new Phpr_Events();

	/*
	 * Initialize the response object
	 */

	Phpr::$response = new Phpr_Response();

	/*
	 * Initialize the session object
	 */

	Phpr::$session = new Phpr_Session();

	/*
	 * Initialize the security system
	 */
	Phpr::$security = new Phpr_Security();

	/*
	 * Configure the application and initialize the request object
	 */

	if ( Phpr::$router === null ) Phpr::$router = new Phpr_Router();

	$appInitScript = realpath( PATH_APP."/"."init/init.php" );
	if ( $appInitScript )
		include $appInitScript;

	Phpr::$config = new Phpr_Config();
	
	Phpr::$request = new Phpr_Request();	

	include PATH_SYSTEM."/system/class_functions.php";
	
	if ( file_exists( PATH_APP."/"."init/custom_helpers.php" ) )
		include PATH_APP."/"."init/custom_helpers.php";

	/*
	 * Initialize the core objects
	 */

	if ( Phpr::$errorLog === null ) Phpr::$errorLog = new Phpr_ErrorLog();
	if ( Phpr::$traceLog === null ) Phpr::$traceLog = new Phpr_TraceLog();

	Phpr::$lang = new Phpr_Language();

	/*
	 * Run modules initialization scripts
	 */
	
	function init_lemonstand_modules()
	{
		$iterator = new DirectoryIterator(PATH_APP.'/modules');
		foreach ( $iterator as $dir )
		{
			if ( $dir->isDir() && !$dir->isDot() )
			{
				if (file_exists($initDir = $dir->getPathname().'/init'))
				{
					$fileIterator = new DirectoryIterator( $initDir );
					foreach ( $fileIterator as $file )
					{
						if ( $file->isFile())
						{
							$info = pathinfo($file->getPathname());
							if (isset($info['extension']) && $info['extension'] == PHPR_EXT)
								include($file->getPathname());
						}
					}
				}
			}
		}
	}

	init_lemonstand_modules();
	
	Phpr::$session->restoreDbData();

?>