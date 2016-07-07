<?php
  /**
   * This file prepares a Standard PHP Library class autoloader function using
   * the `\Pubkey2\Utilities\Path` class for platform-independent support.
   *
   * @copyright Copyright 2016 Clay Freeman. All rights reserved.
   * @license   GNU General Public License v3 (GPL-3.0).
   */

  // End script execution if the private root is not defined
  if (!defined('__PRIVATEROOT__')) die();

  // Provide manual support for loading external dependencies
  { $_class = realpath(implode(DIRECTORY_SEPARATOR, [__PRIVATEROOT__, 'Pubkey2',
    'Utilities', 'Path.php']));
  if (is_file($_class) && is_readable($_class)) require_once($_class); }

  // Create a locally-scoped alias for the `Path` class and its exceptions
  use \Pubkey2\{Exceptions\PathResolutionError, Utilities\Path};

  // Register a silent, fail-safe autoloader for all project classes
  spl_autoload_register(function ($class) {
    // Split the provided class name by its namespace separators
    $class = explode('\\', $class); assert(count($class) > 0);
    // Append '.php' to the last component of the class
    array_push($class, array_pop($class).'.php');
    // Prepend the private root to the class' path component array
    array_unshift($class, __PRIVATEROOT__);
    try { // Attempt to create a platform-specific path string to load the class
      $class = Path::make($class);
      // Load the resulting path representing the requested class
      if (is_file($class) && is_readable($class)) require_once($class);
    } catch (PathResolutionError $e) {}
  }, true, true);
