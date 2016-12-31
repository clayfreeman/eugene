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
  use \Eugene\Exceptions\PathResolutionError;

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
     * This method is tolerant of inexistent terminating components (i.e. files
     * that don't exist yet, but their parent directory does).
     *
     * @param   string  $components       A variadic argument list of path
     *                                    components where directory separators
     *                                    are needed.
     *
     * @throws  InvalidArgumentException  If any provided path component
     *                                    contains the directory separator
     *                                    character.
     * @throws  PathResolutionError       Upon failure when attempting to
     *                                    resolve the absolute path to the
     *                                    requested target.
     *
     * @return  string                    A string representing the `realpath()`
     *                                    result using the appropriate directory
     *                                    separators.
     */
    public static function make(?string ...$components): string {
      // Determine whether the root of the filesystem should be prepended
      $root = explode(__DS__, __DIR__)[0].__DS__;
      $root = reset($components) === null ||
              reset($components) === false ? $root : null;
      // Remove all `null` path components to avoid confusion
      $components = array_filter($components, function($input) {
        return $input !== null; });
      // If there were no provided path components, assume root of filesystem
      if (count($components) === 0) return $root; // TODO: realpath? --v
      // If only one path component was provided, return its value
      if (count($components) === 1) return $root.array_shift($components);
      // Fetch the last component to isolate the target's parent
      $lastComponent = array_pop($components);
      // Ensure that the target's parent can be resolved via `realpath()`
      $path     = $root.implode(__DS__, $components);
      $fail     = realpath($path) === false;
      $realpath = realpath($path   .= __DS__.$lastComponent);
      // If the parent cannot be resoved, throw an exception
      if ($fail === true) throw new PathResolutionError('Failed to determine '.
        'the absolute path to '.escapeshellarg($path).': the requested target '.
        'presumably does not exist');
      // If the target can be resolved using `realpath()` as well, prefer it;
      // otherwise assume the file doesn't exist (yet)
      $realpath = ($realpath !== false ? $realpath : $path);
      return $realpath;
    }
  }
