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

  // Create locally-scoped aliases for the `Singleton` and `Registry` classes
  use \Eugene\{DesignPatterns\Singleton, Runtime\Registry};

  // Create locally-scoped aliases for the `Path` and `Security` classes
  use \Eugene\Utilities\{Path,Security};

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
     * TODO: MOVE THIS DEFINITION
     *
     * @see  Security::isMutableEntry()  For more information regarding how
     *                                   directory entries are checked for
     *                                   mutability and a definition of
     *                                   mutability.
     *
     * @param  string  $file  An optional absolute file path to check in
     *                        addition to the `config` directory.
     */
    protected function sanityCheck(?string $file = null): void {
      // Fetch a reference to the `Security` instance
      $security = Security::getInstance();
      // Ensure that the `config` directory is non-writable by this process
      !$security->fileIsMutable(__CONFIGROOT__) or trigger_error(
        'The `config` directory should not be mutable by PHP (see this '.
        'method\'s documentation to find a definition of '.
        'mutability)', E_USER_WARNING);
      // Only run file-specific tests if a file path was provided
      if ($file !== null) {
        // Ensure that the file path resides in the configuration root
        if (substr($file, 0, strlen(__CONFIGROOT__)) !== __CONFIGROOT__)
          trigger_error('This configuration file is not contained within the '.
            '`config` directory', E_USER_ERROR);
        if (!is_file($file))
          trigger_error('This configuration path is not a file', E_USER_ERROR);
        if (!is_readable($file))
          trigger_error('This configuration file is not '.
            'readable', E_USER_ERROR);
        if ($security->fileIsMutable($file))
          trigger_error('This configuration file should not be mutable by PHP '.
            '(see this method\'s documentation to find a definition of '.
            'mutability)', E_USER_WARNING);
      }
    }

    /**
     * TODO: http://php.net/manual/en/ini.core.php#ini.open-basedir
     *
     * Scans the `config` directory for configuration files matching the
     * globular expression `*.json` and attempts to load them.
     *
     * @see  parse()  For more information regarding how configuration files
     *                will be loaded.
     */
    public function scan(): void {
      // Get a list of JSON files in the `config` directory
      $files = array_filter(array_map('realpath', glob(
        Path::make(__CONFIGROOT__, '*.json'))));
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
     * Optionally, a `lock` key can be set as a sibling of `category` describing
     * how to lock the category. This value defaults to `false`, but may also be
     * `true` (to place a write lock), or an `array` with `callable` members (to
     * place a read lock) that will be invoked to grant them category access.
     *
     * The `callable` members of a `lock` array must accept a `string` key for
     * their first parameter and a `string` password for their second parameter.
     *
     * If the above requirements are met, the value of the "contents" key will
     * be assigned to the key with the value held by "category" in the
     * `Registry` class.
     *
     * @param  string  $file  Absolute (non-writable) file path.
     */
    protected function parse(string $file): void {
      // Ensure that we're using the absolute path
      $file = realpath($file);
      // Perform sanity checks on this file path
      $this->sanityCheck($file);
      // Read and JSON decode the file
      $data = json_decode(file_get_contents($file), true);
      // Ensure that the data array is properly formatted
      if (is_array($data) && isset($data['category'])
                          && isset($data['contents'])) {
        // Fetch a reference to the `Registry` instance
        $registry     = Registry::getInstance();
        // Set default values in the data array where necessary
        $data['lock'] = $data['lock'] ?? false;
        // Ensure that the category is not locked
        if (!$registry->isWriteLocked()) {
          // Assign the resulting data to the requested category
          $registry->set($data['category'], $data['contents']);
          // Check to see whether the category should be locked
          if ($data['lock'] === true)
            // Perform a write lock if `true`
            $registry->lock($data['category']);
          else if (is_array($data['lock'])) {
            // TODO: Lock with random password and inform `lock` members
          }
        } else trigger_error('This configuration file\'s requested category'.
          'cannot be overridden', E_USER_WARNING);
      } else trigger_error('This configuration file is improperly '.
        'formatted', E_USER_WARNING);
    }
  }
