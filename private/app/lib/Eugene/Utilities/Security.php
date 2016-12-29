<?php
  /**
   * TODO
   *
   * @copyright  Copyright 2016 Clay Freeman. All rights reserved.
   * @license    GNU General Public License v3 (GPL-3.0).
   */

  // Enable strict types for this file
  declare(strict_types = 1);

  namespace Eugene\Utilities;

  // End script execution if the private root is not defined
  if (!defined('__PRIVATEROOT__')) die();

  // Create locally-scoped aliases for the `Singleton` and `Path` classes
  use \Eugene\{DesignPatterns\Singleton, Utilities\Path};

  /**
   * TODO
   */
  final class Security extends Singleton {
    /**
     * Disallow unlinks via `getInstance(true)`.
     *
     * @var  bool
     */
    protected $allowUnlink = false;

    /**
     * An empty constructor to satisfy the parent's abstract method
     * prototype definition.
     */
    protected function __construct() {}

    /**
     * Determines whether the provided file path is considered mutable.
     *
     * Mutability is defined as the ability to write to a directory entry
     * directly or indirectly by using ownership to change file permissions.
     *
     * If the provided file path does not exist, `false` will be returned.
     *
     * @param   string  $file  The path to check for mutability.
     *
     * @return  bool           Whether the provided file is mutable.
     */
    public function fileIsMutable(string $file): bool {
      // Check whether the provided file path exists
      if (file_exists($file)) {
        // Check whether the file is writable or is owned by this process
        return is_writable($file) || fileowner($file) == posix_getuid();
        // TODO: RESTRICT USAGE TO POSIX SYSTEMS
      } return false;
    }

    /**
     * Determines whether the provided file path or any subsequent directory
     * entries are considered mutable
     *
     * If the provided file path does not exist, `false` will be returned.
     *
     * @see     fileIsMutable()  For more information regarding mutability test.
     *
     * @param   string  $file    The path to recursively check for mutability.
     *
     * @return  bool             Whether the provided file or any subsequent
     *                           directory entries are mutable.
     */
    public function fileIsRecursivelyMutable(string $file): bool {
      // Assume that the provided file path is not recursively mutable
      $result = false;
      // Ensure that the file exists before continuing
      if (file_exists($file)) {
        // Begin by checking the provided file path itself
        $result = $this->fileIsMutable($file);
        if ($result === false && is_dir($file)) {
          // Create a `RecursiveDirectoryIterator` for the provided file path
          $entries = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($file,
                \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST);
          // Check the children of the provided file path
          foreach ($entries as $name => $entry) {
            // Check this specific child node for mutability
            $result = $this->fileIsMutable($name);
            // If the file is mutable, stop looping to save time
            if ($result === true) break;
          }
        } // Return the mutability test results
      } return $result;
    }

    /**
     * [lockdown description]
     *
     * @param  bool  $strict  [description]
     */
    public function lockdown(bool $strict = false): void {
      // Define an array of paths that should be allowed if itself and all
      // children are read-only
      $ro = [__APPROOT__, Path::make(__PROJECTROOT__, 'vendor')];
      $rw = [__DATAROOT__];
      // If operating under strict mode, `__CONFIGROOT__` should be excluded
      if ($strict === false) $ro[] = __CONFIGROOT__;
      // Filter the allowed read-only paths to only recursively immutable paths
      $ro = array_filter($ro, function($input) {
        return Security::getInstance()->fileIsRecursivelyMutable($input); });
      // Define a list of allowed paths during application runtime based on the
      // restricted read-only and read-write paths
      $allowed = array_filter(array_merge($ro, $rw), function($input) {
        return is_dir($input) && is_readable($input) &&
          !stristr($input, PATH_SEPARATOR); });
      echo var_export($allowed, true)."\n";
      // Restrict file access to prevent unauthorized tampering of application
      // (see http://php.net/manual/en/ini.core.php#ini.open-basedir for more
      // information regarding file restriction)
      ini_set('open_basedir', implode(PATH_SEPARATOR, $allowed));
    }
  }
