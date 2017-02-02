<?php
  /**
   * This file is responsible for declaring `Security`, a collection of useful
   * security-related methods.
   *
   * @copyright  Copyright 2016 Clay Freeman. All rights reserved.
   * @license    GNU Lesser General Public License v3 (LGPL-3.0).
   */

  // Enable strict types for this file
  declare(strict_types = 1);

  namespace Eugene\Utilities;

  // End script execution if the private root is not defined
  if (!defined('__PRIVATEROOT__')) die();

  // Create a locally-scoped alias for the `Singleton` class
  use \Eugene\DesignPatterns\Singleton;

  /**
   * Collection of useful security-related methods to help improve overall
   * runtime application security.
   */
  final class Security extends Singleton {
    // Safely hide members of this class (`Singleton` implies the use of
    // `PreventSerialize` to complete this feature)
    use \Eugene\DesignPatterns\HiddenMembers;

    /**
     * Disallow unlinks via `getInstance(true)`.
     *
     * @var  bool
     */
    protected $allowUnlink = false;

    /**
     * An array of UIDs that this process can assume.
     *
     * @var  array
     */
    protected $uids        = [];

    /**
     * Responsible for determining the UIDs of the current process.
     */
    protected function __construct() {
      // Fetch runtime information about the current process
      $this->uids  = array_unique([posix_geteuid(), posix_getuid()]);
    }

    /**
     * Uses `scandir()` to recursively enumerate the provided file path.
     *
     * This method specializes in minimal required system calls being used for
     * optimal performance. If the provided file path does not exist or is not
     * a directory it will be returned in a single element array.
     *
     * @param   string  $path  An input file to recursively enumerate.
     *
     * @return  array          An array of absolute file paths.
     */
    public function fastRecursiveFileEnumerator(string $path): array {
      // Allocate an array to hold the results (initialized with the given path)
      $results = [$path];
      // Get a list of all directory entries for the provided path
      $scandir = @scandir($path, SCANDIR_SORT_NONE);
      // Iterate over each directory entry to expand child directories
      if (is_array($scandir)) foreach ($scandir as $file) {
        // Skip dot file results to prevent duplicate entries
        if (strlen($file) < 3 && ($file == '.' || $file == '..')) continue;
        // Convert the relative file name to an absolute file name
        $file = $path.__DS__.$file;
        // Expand this path and merge the results
        $results = array_merge($results,
          $this->fastRecursiveFileEnumerator($file));
      // Return the array filled with file paths
      } return $results;
    }

    /**
     * Determines whether the provided file path is considered mutable.
     *
     * Mutability is defined as the ability to write to a directory entry
     * directly or indirectly by using ownership to change file permissions.
     *
     * If the provided file path does not exist, `true` will be returned.
     *
     * @param   string  $file  The path to check for mutability.
     *
     * @return  bool           Whether the provided file is mutable.
     */
    public function fileIsMutable(string $file): bool {
      return     is_writable($file) || // Check if writable using `access(2)`
        ($owner = @fileowner($file)) === false || // Ensure we can get the owner
        in_array($owner, $this->uids); // Ensure that we don't own the file
    }

    /**
     * Determines whether the provided file path or any subsequent directory
     * entries are considered mutable
     *
     * If the provided file path does not exist, `true` will be returned.
     *
     * @see     fileIsMutable()  For more information regarding mutability test.
     *
     * @param   string  $file    The path to recursively check for mutability.
     *
     * @return  bool             Whether the provided file or any subsequent
     *                           directory entries are mutable.
     */
    public function fileIsRecursivelyMutable(string $file): bool {
      // Check if any recursive directory entry of the provided path is mutable
      return count(array_filter(array_map([$this, 'fileIsMutable'],
        $this->fastRecursiveFileEnumerator($file)))) > 0;
    }

    /**
     * Restricts filesystem access outside of required application areas.
     *
     * By default, this method configures `open_basedir` to allow access to the
     * following directories:
     *
     *  - `private/app`
     *  - `private/config`
     *  - `private/data`
     *  - `private/keys`
     *  - `vendor`
     *
     * However, when strict mode is enabled, access to `private/config` and
     * `private/keys` is revoked and access to configuration is arbitrated by
     * the `Registry` class. This is to allow secure storage of
     * application-specific credentials.
     *
     * During runtime, `open_basedir` can be configured and later restricted
     * further, but cannot be reversed once applied. The below link describes
     * how `open_basedir` functions during runtime.
     *
     * @see    http://php.net/manual/en/ini.core.php#ini.open-basedir
     *
     * @param  bool  $strict  Whether strict mode should be enabled.
     */
    public function lockdown(bool $strict = false): void {
      // Define some arrays of paths that should be conditionally allowed
      $ro = []; $rw = [__APPROOT__, __DATAROOT__, __VENDORROOT__];
      // Include `__CONFIGROOT__` and `__KEYROOT__` in non-strict mode
      if ($strict === false) { $ro[] = __CONFIGROOT__; $rw[] = __KEYROOT__; }
      // Ensure that only recursively immutable paths are allowed in the
      // read-only array of paths
      $ro = array_filter($ro, function($input) {
        if ($retval = $this->fileIsRecursivelyMutable($input))
          trigger_error('This path is recursively mutable', E_USER_WARNING);
        return !$retval; });
      // Define a list of allowed paths during application runtime based on the
      // restricted read-only and read-write paths
      $allowed = array_filter(array_merge($ro, $rw), function($input) {
        return !strstr($input, PATH_SEPARATOR); });
      // Restrict file access to prevent unauthorized tampering of application
      // (see http://php.net/manual/en/ini.core.php#ini.open-basedir for more
      // information regarding file restriction)
      ini_set('open_basedir', implode(PATH_SEPARATOR, $allowed));
    }
  }
