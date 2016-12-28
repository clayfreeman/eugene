<?php
  /**
   * This file serves as an application entrypoint routine responsible for
   * preparing the application's runtime.
   *
   * @copyright  Copyright 2016 Clay Freeman. All rights reserved.
   * @license    GNU General Public License v3 (GPL-3.0).
   */

  // Enable strict types for this file
  declare(strict_types = 1);

  // Display and enable *all* types of errors
  ini_set('display_errors',         '1');
  ini_set('display_startup_errors', '1');
  ini_set('log_errors',             '1');
  ini_set('log_errors_max_len',     '0');
  error_reporting(E_ALL | E_STRICT);

  // Define the required path constants for the application
  define('__DS__',          DIRECTORY_SEPARATOR);
  define('__CLASSPATH__',   realpath(__DIR__.__DS__.'vendor'));
  define('__APPROOT__',     realpath(__DIR__));
  define('__PRIVATEROOT__', realpath(dirname(__APPROOT__)));
  define('__PROJECTROOT__', realpath(dirname(__PRIVATEROOT__)));
  define('__PUBLICROOT__',  realpath(__PROJECTROOT__.__DS__.'public'));

  // Ensure that `var_export` is disabled
  !function_exists('var_export') or trigger_error('For maximum security the '.
    '\'var_export(...)\' function should be disabled using the '.
    '\'disable_functions\' directive', E_USER_WARNING);

  // Check the PHP version number and complain if unsatisfactory
  { (version_compare(PHP_VERSION, $minimum = '7.1.0') >= 0) or trigger_error(
    'This project requires at least PHP '.$minimum.' to run', E_USER_ERROR); }

  // Run the application autoload utility setup file
  require_once(realpath(implode(__DS__, [__CLASSPATH__, 'Eugene', 'Utilities',
    'Autoload.php']))) or trigger_error('Could not load the project\'s '.
    'autoload utility', E_USER_ERROR);

  // Create a locally-scoped alias for the `Path` class
  use \Eugene\Utilities\Path;

  // Define the public root directory
  define('__PUBLICROOT__', Path::make(__PRIVATEROOT__, 'public'));
  // Define the config root directory
  define('__CONFIGROOT__', Path::make(__PRIVATEROOT__, 'config'));
  // Ensure that the config directory exists
  is_dir( __CONFIGROOT__ ) or trigger_error('The `config` path is not a '.
    'directory', E_USER_ERROR);

  // Scan for project configuration files
  \Eugene\Runtime\Config::getInstance()->scan();

  // Load the composer vendor autoloader to include all composer software
  silent_include(Path::make(__PRIVATEROOT__, 'vendor', 'autoload.php')) or
    trigger_error('Could not load composer\'s autoload utility', E_USER_ERROR);

  // Restrict file access to the public document root (see
  // http://php.net/manual/en/ini.core.php#ini.open-basedir for more information
  // regarding file restriction)
  // ini_set('open_basedir', Path::make(__DATAROOT__, null));

  /**
   * Attempt to silently include the provided path.
   *
   * @param  string  $path  The desired path to include.
   *
   * @return bool           `true`  if included successfully,
   *                        `false` on failure.
   */
  function silent_include(string $path = null): bool {
    // Silently attempt to include the provided path
    return is_file($path) && is_readable($path) && include_once($path);
  }
