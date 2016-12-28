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

  // Create a locally-scoped alias for the `Singleton` class
  use \Eugene\DesignPatterns\Singleton;

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
     * @param   string  $file  The file path to check for mutability.
     *
     * @return  bool           Whether the provided file is mutable.
     */
    public function fileIsMutable(string $file): bool {
      // Check whether the provided file path exists
      if (file_exists($file)) {
        // Check whether the file is writable or is owned by this process
        return is_writable($file) || fileowner($file) === posix_getuid();
        // TODO: RESTRICT USAGE TO POSIX SYSTEMS
      } return false;
    }
  }
