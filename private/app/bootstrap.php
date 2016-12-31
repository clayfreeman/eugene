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
  define('__DS__',          DIRECTORY_SEPARATOR);
  define('__CLASSPATH__',   realpath(__DIR__.__DS__.'lib'));
  define('__APPROOT__',     realpath(__DIR__));
  define('__PRIVATEROOT__', realpath(dirname(__APPROOT__)));
  define('__CONFIGROOT__',  realpath(__PRIVATEROOT__.__DS__.'config'));
  define('__DATAROOT__',    realpath(__PRIVATEROOT__.__DS__.'data'));
  define('__KEYROOT__',     realpath(__PRIVATEROOT__.__DS__.'keys'));
  define('__PROJECTROOT__', realpath(dirname(__PRIVATEROOT__)));
  define('__PUBLICROOT__',  realpath(__PROJECTROOT__.__DS__.'public'));
  define('__VENDORROOT__',  realpath(__PROJECTROOT__.__DS__.'vendor'));

  // Check the PHP version number and complain if unsatisfactory
  { (version_compare(PHP_VERSION, $minimum = '7.1.0') >= 0) or trigger_error(
    'This project requires at least PHP '.$minimum.' to run', E_USER_ERROR); }

  // Ensure that we're running under a POSIX-based system
  function_exists('posix_kill') or trigger_error('This project requires the '.
    'POSIX extension to be loaded', E_USER_ERROR);

  // Ensure that `var_export` is disabled
  !function_exists('var_export') or trigger_error('For maximum security the '.
    '\'var_export()\' function should be disabled using the '.
    '\'disable_functions\' directive', E_USER_WARNING);

  // Load the composer vendor autoloader to include all composer software
  require_once(realpath(implode(__DS__, [__VENDORROOT__, 'autoload.php'])));
  // Run the application autoload utility setup file
  require_once(realpath(implode(__DS__,
    [__CLASSPATH__,  'Eugene', 'Utilities', 'Autoload.php'])));

  // Begin the non-strict lockdown phase of execution (to still allow
  // configuration file parsing)
  ($security = \Eugene\Utilities\Security::getInstance())->lockdown();
  // Scan for project configuration files (deferring all external side effects)
  ($config   = \Eugene\Runtime\Config::getInstance())->scan();
  // Migrate to the final (strict) lockdown phase to exclude access to the
  // configuration directory and process deferred side effects
  $security->lockdown(true); $config->dispatchCredentials();

  // Load the application's main logic file
  require_once(\Eugene\Utilities\Path::make(__APPROOT__, 'main.php'));
