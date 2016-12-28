<?php
  /**
   * This file prepares a Standard PHP Library class autoloader function using
   * the `\Eugene\Utilities\Path` class for platform-independent support.
   *
   * @copyright  Copyright 2016 Clay Freeman. All rights reserved.
   * @license    GNU General Public License v3 (GPL-3.0).
   */

  // Enable strict types for this file
  declare(strict_types = 1);

  namespace Eugene\Utilities;

  // End script execution if the private root is not defined
  if (!defined('__PRIVATEROOT__')) die();

  // Provide manual support for loading external dependencies
  { $_class = realpath(implode(DIRECTORY_SEPARATOR, [__CLASSPATH__, 'Eugene',
    'Utilities', 'Path.php']));
  include_once($_class) or trigger_error('Could not load file at '.
    escapeshellarg($_class), E_USER_ERROR); }

  // Create a locally-scoped alias for the `Path` class and its exceptions
  use \Eugene\{Exceptions\PathResolutionError, Utilities\Path};

  // Register a silent, fail-safe autoloader for all project classes
  spl_autoload_register(function ($class) {
    // Split the provided class name by its namespace separators
    $class = explode('\\', $class); assert(count($class) > 0);
    // Append '.php' to the last component of the class
    array_push($class, array_pop($class).'.php');
    // Prepend the class path to the class' path component array
    array_unshift($class, __CLASSPATH__);
    try { // Attempt to create a platform-specific path string to load the class
      $class = Path::make(...$class);
      // Load the resulting path representing the requested class
      include_once($class) or trigger_error('Could not load file at '.
        escapeshellarg($class), E_USER_ERROR);
    } catch (PathResolutionError $e) {}
  }, true, true);
