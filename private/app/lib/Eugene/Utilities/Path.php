<?php
  /**
   * This file provides a `Path` class responsible for generating
   * platform-specific filesystem paths for use with internal functionaltiy.
   *
   * @copyright  Copyright 2016 Clay Freeman. All rights reserved.
   * @license    GNU Lesser General Public License v3 (LGPL-3.0).
   */

  // Enable strict types for this file
  declare(strict_types = 1);

  namespace Eugene\Utilities;

  // End script execution if the private root is not defined
  if (!defined('__PRIVATEROOT__')) die();

  // Provide manual support for loading external dependencies
  { $_class = realpath(implode(__DS__, [__CLASSPATH__, 'Eugene', 'Exceptions',
    'PathResolutionError.php'])); require_once($_class); }

  /**
   * Helper class to aid in making platform-specific filesystem paths via
   * `__DS__` implosion of path components.
   */
  final class Path {
    /**
     * Prevent construction of this class to force a static-only interface.
     */
    protected function __construct() {}

    /**
     * Creates a platform-specific path to a filesystem directory entry from an
     * array of path components.
     *
     * This method will call `implode()` on the provided array using the
     * platform-specific directory separator as the 'glue'. After the dirty path
     * is constructed, it is passed through `realpath()` to determine the
     * absolute path to the requested target.
     *
     * @param   string  $components  A variadic argument list of path components
     *                               where directory separators are needed.
     *
     * @throws  PathResolutionError  Upon failure when attempting to resolve the
     *                               absolute path to the requested target.
     *
     * @return  string               A string representing the `realpath()`
     *                               result using the appropriate directory
     *                               separators.
     */
    public static function make(?string ...$components): string {
      // Generate a string containing the dirty, platform-specific path
      $path = implode(__DS__, $components);
      // Attempt to resolve the absolute path to the requested target
      $real_path = realpath($path);
      // Throw an exception if the absolute path could not be determined
      if ($real_path === false) throw new
        \Eugene\Exceptions\PathResolutionError('Failed to determine the '.
          'absolute path to '.escapeshellarg($path).': the requested target '.
          'presumably does not exist.');
      // Return the resulting path string
      return $real_path;
    }
  }
