<?php
  /**
   * TODO
   *
   * @copyright  Copyright 2016 Clay Freeman. All rights reserved.
   * @license    GNU General Public License v3 (GPL-3.0).
   */

  // Enable strict types for this file
  declare(strict_types = 1);

  namespace Eugene\Runtime;

  // End script execution if the private root is not defined
  if (!defined('__PRIVATEROOT__')) die();

  // Create a locally-scoped alias for all possible exceptions that might be
  // thrown by this class
  use \Eugene\Exceptions\{NameUnavailableError, NameUnlockError,
    ReadLockError, WriteLockError};

  /**
   * TODO
   */
  final class ConfigParser extends Singleton {
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
     * Scans the `config` directory for configuration files to load.
     *
     * The `config` directory will be checked to ensure that the current process
     * doesn't have write permissions to it. All subsequent configuration files
     * will be automatically passed to the `parse(...)` method to be loaded.
     *
     * @see  parse()  For more information regarding how configuration files
     *                will be loaded.
     */
    public function scan(): void {
      //
    }

    /**
     * [parse description]
     *
     * @param  string  $file  [description]
     */
    public function parse(string $file): void {
      //
    }
  }
