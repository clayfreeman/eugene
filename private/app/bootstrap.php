<?php
  /**
   * This file serves as an application entrypoint routine responsible for
   * preparing the application's runtime.
   *
   * @copyright  Copyright 2016 Clay Freeman. All rights reserved.
   * @license    GNU Lesser General Public License v3 (LGPL-3.0).
   */

  // Enable strict types for this file
  declare(strict_types = 1);

  // Display and enable *all* types of errors
  ini_set('display_errors',                               '1');
  ini_set('display_startup_errors',                       '1');
  ini_set('log_errors', php_sapi_name() === 'cli' ? '0' : '1');
  ini_set('log_errors_max_len',                           '0');
  error_reporting(E_ALL | E_STRICT);

  // Define the required path constants for the application
  define('__DS__',           DIRECTORY_SEPARATOR);
  define('__CLASSPATH__',    realpath(__DIR__.__DS__.'lib'));
  define('__TEMPLATEROOT__', realpath(__DIR__.__DS__.'templates'));
  define('__APPROOT__',      realpath(__DIR__));
  define('__PRIVATEROOT__',  realpath(dirname(__APPROOT__)));
  define('__CONFIGROOT__',   realpath(__PRIVATEROOT__.__DS__.'config'));
  define('__DATAROOT__',     realpath(__PRIVATEROOT__.__DS__.'data'));
  define('__KEYROOT__',      realpath(__PRIVATEROOT__.__DS__.'keys'));
  define('__PROJECTROOT__',  realpath(dirname(__PRIVATEROOT__)));
  define('__PUBLICROOT__',   realpath(__PROJECTROOT__.__DS__.'public'));
  define('__VENDORROOT__',   realpath(__PROJECTROOT__.__DS__.'vendor'));
  define('__STARTTIME__',    microtime(true));

  // Check the PHP version number and complain if unsatisfactory
  { (version_compare(PHP_VERSION, $minimum = '7.1.0') >= 0) or trigger_error(
    'This project requires at least PHP '.$minimum.' to run', E_USER_ERROR); }

  // Ensure that we're running under a POSIX-based system
  function_exists('posix_kill') or trigger_error('This project requires the '.
    'POSIX extension to be loaded', E_USER_ERROR);

  // Run the application autoload utility setup file
  require_once(realpath(implode(__DS__, [__CLASSPATH__,  'Eugene',
    'Utilities', 'Autoload.php'])));

  { // Begin the non-strict lockdown phase of execution (to still allow
    // configuration file parsing)
    ($security = \Eugene\Utilities\Security::getInstance())->lockdown();
    // Add security exceptions for the Twig templating engine
    $security->addDangerException(\Eugene\Utilities\Path::make(__VENDORROOT__,
      'twig', 'twig', 'lib', 'Twig', 'Cache', 'Filesystem.php'));
    $security->addDangerException(\Eugene\Utilities\Path::make(__VENDORROOT__,
      'twig', 'twig', 'lib', 'Twig', 'Environment.php'));
    // Import all installed Composer package autoloader definitions
    \Eugene\Utilities\Autoload::getInstance()->importComposer();
    // Initialize cryptographic operations by fetching an instance to `Crypto`
    \Eugene\Utilities\Crypto::getInstance();
    // Scan for project configuration files (deferring all side effects)
    ($config   = \Eugene\Runtime\Config::getInstance())->scan();
    // Migrate to the final (strict) lockdown phase to exclude access to the
    // configuration and secret key directory and process deferred side effects
    $security->lockdown(true); $config->dispatchCredentials(); }

  // Load the application's main logic file
  require_once(\Eugene\Utilities\Path::make(__APPROOT__, 'main.php'));
