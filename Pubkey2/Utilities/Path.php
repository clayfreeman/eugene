<?php
  /**
   * This file provides a `Path` class responsible for generating
   * platform-specific filesystem paths for use with internal functionaltiy.
   *
   * @copyright Copyright 2016 Clay Freeman. All rights reserved.
   * @license   GNU General Public License v3 (GPL-3.0).
   */

  namespace Pubkey2\Utilities;

  // End script execution if the private root is not defined
  if (!defined('__PRIVATEROOT__')) die();

  // Provide manual support for loading external dependencies
  { $_class = realpath(implode(DIRECTORY_SEPARATOR, [__PRIVATEROOT__, 'Pubkey2',
    'Exceptions', 'PathResolutionError.php']));
  silent_include($_class) or die('Could not load file at '.
    escapeshellarg($_class).".\n"); }

  // Create a locally-scoped alias for the `PathResolutionError` class
  use \Pubkey2\Exceptions\PathResolutionError;

  final class Path {
    /**
     * Prevent construction of this class to force a static-only interface.
     */
    private function __construct() {}

    /**
     * Creates a platform-specific path to a filesystem directory entry from an
     * array of path components.
     *
     * This method will call `implode()` on the provided array using the
     * platform-specific directory separator as the 'glue'. After the dirty path
     * is constructed, it is passed through `realpath()` to determine the
     * absolute path to the requested target.
     *
     * @param  array  $components  An array of path components where directory
     *                             separators should be inserted.
     *
     * @throws PathResolutionError Upon failure when attempting to resolve the
     *                             absolute path to the requested target.
     *
     * @return string              A string representing the `realpath()` result
     *                             using the appropriate directory separators.
     */
    public static function make(array $components): string {
      // Generate a string containing the dirty, platform-specific path
      $path = implode(DIRECTORY_SEPARATOR, $components);
      // Attempt to resolve the absolute path to the requested target
      $real_path = realpath($path);
      // Throw an exception if the absolute path could not be determined
      if ($real_path === false) throw new PathResolutionError('Failed to '.
        'determine the absolute path to '.escapeshellarg($path).': the '.
        'requested target presumably does not exist.');
      // Return the resulting path string
      return $real_path;
    }
  }
