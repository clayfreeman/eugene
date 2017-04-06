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

  // Make the appropriate checks for PHAR compatibility and security
  extension_loaded('Phar') or trigger_error('This project requires the PHAR '.
    'extension to be loaded', E_USER_ERROR);
  boolval(ini_get('phar.readonly')) or trigger_error('This project requires '.
    'that '.escapeshellarg('phar.readonly').' is enabled', E_USER_ERROR);
  boolval(ini_get('phar.require_hash')) or trigger_error('This project '.
    'requires that '.escapeshellarg('phar.require_hash').' is '.
    'enabled', E_USER_ERROR);

  // Define the required path constants for the application
  define('__DS__',           DIRECTORY_SEPARATOR);
  define('__COMPILED__',     strlen(Phar::running()) > 0);
  define('__APPROOT__',      __COMPILED__ ? Phar::running()      : __DIR__);
  define('__APPFILE__',      __COMPILED__ ? Phar::running(false) : __DIR__);
  define('__CLASSPATH__',    __APPROOT__.__DS__.'lib');
  define('__TEMPLATEROOT__', __APPROOT__.__DS__.'templates');
  define('__VENDORROOT__',   __APPROOT__.__DS__.'vendor');
  define('__PRIVATEROOT__',  realpath(dirname(__APPFILE__)));
  define('__PROJECTROOT__',  realpath(dirname(__PRIVATEROOT__)));
  define('__PUBLICROOT__',   realpath(__PROJECTROOT__.__DS__.'public'));
  define('__CONFIGROOT__',   realpath(__PRIVATEROOT__.__DS__.'config'));
  define('__DATAROOT__',     realpath(__PRIVATEROOT__.__DS__.'data'));
  define('__KEYROOT__',      realpath(__PRIVATEROOT__.__DS__.'keys'));
  define('__STARTTIME__',    microtime(true));

  // Check the PHP version number and complain if unsatisfactory
  { (version_compare(PHP_VERSION, $minimum = '7.1.0') >= 0) or trigger_error(
    'This project requires at least PHP '.$minimum.' to run', E_USER_ERROR); }

  // Ensure that we're running under a POSIX-based system
  function_exists('posix_kill') or trigger_error('This project requires the '.
    'POSIX extension to be loaded', E_USER_ERROR);

  // Warn the user about the security benefits of running a PHAR versus scripted
  __COMPILED__ or trigger_error('Currently running in scripted mode. There '.
    'are many security benefits to running a PHAR; see '.
    'http://php.net/manual/en/intro.phar.php for more info', E_USER_WARNING);

  // Run the application autoload utility setup file
  require_once(implode(__DS__, [__CLASSPATH__,  'Eugene',
    'Utilities', 'Autoload.php']));

  { // Begin the non-strict lockdown phase of execution (to still allow
    // configuration file parsing)
    ($security = \Eugene\Utilities\Security::getInstance())->lockdown();
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
