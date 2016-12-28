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
  define('__CLASSPATH__',   realpath(__DIR__.__DS__.'lib'));
  define('__APPROOT__',     realpath(__DIR__));
  define('__DATAROOT__',    realpath(__APPROOT__.__DS__.'data'));
  define('__PRIVATEROOT__', realpath(dirname(__APPROOT__)));
  define('__CONFIGROOT__',  realpath(__PRIVATEROOT__.__DS__.'config'));
  define('__PROJECTROOT__', realpath(dirname(__PRIVATEROOT__)));
  define('__PUBLICROOT__',  realpath(__PROJECTROOT__.__DS__.'public'));

  // Check the PHP version number and complain if unsatisfactory
  { (version_compare(PHP_VERSION, $minimum = '7.1.0') >= 0) or trigger_error(
    'This project requires at least PHP '.$minimum.' to run', E_USER_ERROR); }

  // Ensure that `var_export` is disabled
  !function_exists('var_export') or trigger_error('For maximum security the '.
    '\'var_export(...)\' function should be disabled using the '.
    '\'disable_functions\' directive', E_USER_WARNING);

  // Define a list of allowed paths during application runtime. Include
  // directories should be checked for read-only access in addition to this
  // preventative security measure
  $allowedDirectories = array_filter([__DATAROOT__, __CLASSPATH__,
    __PROJECTROOT__.__DS__.'vendor'], function($input) {
      return is_dir($input) && !stristr($input, PATH_SEPARATOR);
  }); echo var_export($allowedDirectories, true)."\n";
  // Restrict file access to prevent unauthorized tampering of application (see
  // http://php.net/manual/en/ini.core.php#ini.open-basedir for more information
  // regarding file restriction)
  ini_set('open_basedir', implode(PATH_SEPARATOR, $allowedDirectories));

  // Run the application autoload utility setup file
  require_once(realpath(implode(__DS__,
    [__CLASSPATH__, 'Eugene', 'Utilities', 'Autoload.php'])));

  // Scan for project configuration files
  \Eugene\Runtime\Config::getInstance()->scan();

  // Load the composer vendor autoloader to include all composer software
  require_once(\Eugene\Utilities\Path::make(
    __PROJECTROOT__, 'vendor', 'autoload.php'));
