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

  // Define the absolute path to the `config` directory
  define('__CONFIGROOT__', realpath(\Eugene\Utilities\Path::make(
    __PRIVATEROOT__, 'config')));
  // Ensure that the config directory exists
  (file_exists(__CONFIGROOT__) && !is_dir(__CONFIGROOT__)) or trigger_error(
    'The `config` path must be a directory; file or otherwise found '.
    'instead', E_USER_ERROR);

  // Create a locally-scoped alias for the `Security` class
  use \Eugene\Utilities\Security;

  // Create a locally-scoped alias for all possible exceptions that might be
  // thrown by this class
  use \Eugene\Exceptions\{NameUnavailableError, NameUnlockError,
    ReadLockError, WriteLockError};

  /**
   * TODO
   */
  final class Config extends Singleton {
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
     * [sanityCheck description]
     *
     * @param  string  $file  [description]
     */
    protected function sanityCheck(?string $file = null): void {
      // Ensure that the `config` directory is non-writable by this process
      !Security::isMutableEntry(__CONFIGROOT__) or trigger_error(
        'The `config` directory should not be mutable by PHP (see this '.
        'method\'s documentation to find a definition of '.
        'mutability)', E_USER_WARNING);
      if ($file !== null) {
        if (substr(realpath($file), 0, strlen(__CONFIGROOT__)) !== __CONFIGROOT__)
      }
    }

    /**
     * Scans the `config` directory for configuration files to load.
     *
     * The `config` directory will be checked to ensure that the current process
     * doesn't have write permissions to it. All subsequent configuration files
     * will be automatically passed to the `parse(...)` method to be loaded.
     *
     * TODO: MOVE THIS DEFINITION
     * Mutability is defined as the ability to write to a directory entry
     * directly or indirectly by using ownership to change file permissions.
     *
     * @see  Security::isMutableEntry()  For more information regarding how
     *                                   directory entries are checked for
     *                                   mutability and a definition of
     *                                   mutability.
     * @see  parse()                     For more information regarding how
     *                                   configuration files will be loaded.
     */
    public function scan(): void {
      // Get a list of JSON files in the `config` directory
      $files = glob(\Eugene\Utilities\Path::make($configPath, '*.json'));
      // Filter the globular expression result to contain only files
      $files = array_filter(function($input) {
        // Check whether the directory entry is a file
        return is_file($input);
      }, $files); // Attempt to parse each configuration file
      foreach ($files as $file) $this->parse($file);
    }

    /**
     * Attempts to parse the requested file from the `config` directory.
     *
     * The provided file path must be an absolute path to a non-writable file
     * inside the `config` directory. The file must also match the following
     * JSON document specification (`*.json` naming scheme recommended):
     *
     * `{ "category": "...", "contents": ... }`
     *
     * If the above requirements are met, the value of the "contents" key will
     * be assigned to the key with the value held by "category" in the
     * `\Eugene\Runtime\Registry` class. This value can be accessed via this
     * class' `getCategory(...)` method.
     *
     * @param  string  $file  Absolute (non-writable) file path.
     */
    public function parse(string $file): void {
      // Ensure that we're using the absolute path
    }
  }
