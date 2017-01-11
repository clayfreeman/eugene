<?php
  /**
   * This file is responsible for declaring a configuration file scanner.
   *
   * @copyright  Copyright 2016 Clay Freeman. All rights reserved.
   * @license    GNU Lesser General Public License v3 (LGPL-3.0).
   */

  // Enable strict types for this file
  declare(strict_types = 1);

  namespace Eugene\Runtime;

  // End script execution if the private root is not defined
  if (!defined('__PRIVATEROOT__')) die();

  // Create locally-scoped aliases for the `Singleton` and `Registry` classes
  use \Eugene\{DesignPatterns\Singleton, Runtime\Registry};

  // Create a locally-scoped alias for the `Security` class
  use \Eugene\Utilities\Security;

  /**
   * Public interface for the `config` directory scanner.
   *
   * Files matching the globular expression `*.json` will be matched by the
   * `scan()` method and will attempt to be parsed.
   */
  final class Config extends Singleton {
    /**
     * Disallow unlinks via `getInstance(true)`.
     *
     * @var  bool
     */
    protected $allowUnlink = false;

    /**
     * Dispatch queue container for read-lock configured category recipients.
     *
     * The keys in this array represent the configured recipient, whereas the
     * value represents an array with the keys `category` and `password` to be
     * delivered to the recipient.
     *
     * @var  array
     */
    protected $dispatch    = [];

    /**
     * An empty constructor to satisfy the parent's abstract method
     * prototype definition.
     */
    protected function __construct() {}

    /**
     * Processes the dispatch queue (if non-empty) to deliver any newly created
     * credentials for configuration categories stored in the `Registry`.
     *
     * Recipients must implement `ConfigDelegate` to be invoked.
     */
    public function dispatchCredentials(): void {
      // Iterate over each class' collection of credentials
      foreach ($this->dispatch as $class => $credentials) {
        // Ensure that the target class exists and implements `ConfigDelegate`
        if (class_exists($class) && is_subclass_of($class,
            '\\Eugene\\DesignPatterns\\ConfigDelegate')) {
          // Iterate over each credential for delivery to this target
          foreach ($credentials as $credential)
            // Deliver this credential using the `ConfigDelegate` interface
            $class::receiveCredential($credential['category'],
              $credential['password']);
        } else trigger_error('This class is not applicable to receive '.
          'configuration credentials', E_USER_WARNING);
      }
    }

    /**
     * Attempts to parse the requested file from the `config` directory.
     *
     * The provided file path must be an absolute path to an immutable file
     * inside the `config` directory. The file must also match the following
     * JSON document specification (`*.json` naming scheme recommended for
     * autodetection in the `scan()` method):
     *
     * `{ "category": "...", "contents": ... }`
     *
     * Optionally, a `lock` key can be set as a sibling of `category` describing
     * how to lock the category. This value defaults to `false`, but may also be
     * `true` (to place a write lock), or an `array` (to place a read lock) with
     * fully-qualified class names that will be invoked to grant them category
     * access.
     *
     * The class names of a `lock` array must implement the `ConfigDelegate`
     * design pattern otherwise they will not be invoked.
     *
     * If the above requirements are met, the value of the `contents` key will
     * be assigned to the key with the value held by `category` in the
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
        if (!$registry->isWriteLocked($data['category'])) {
          // Assign the resulting data to the requested category
          $registry->set($data['category'], $data['contents']);
          // Check to see whether the category should be locked
          if ($data['lock'] === false) {}
          else if ($data['lock'] === true)
            // Perform a write lock if `true`
            $registry->lock($data['category']);
          else if (is_array($data['lock'])) {
            // Generate a password using `random_bytes()`
            $password = new \Eugene\Utilities\HiddenString(random_bytes(256));
            // Perform a read lock on the category
            $registry->lock($data['category'], $password);
            // Update each configured recipient's dispatch queue
            foreach ($data['lock'] as $recipient) {
              // Create a queue for this recipient if it doesn't exist
              $this->dispatch[$recipient] = $this->dispatch[$recipient] ?? [];
              // Append the password to this recipient's dispatch queue
              $this->dispatch[$recipient][] = ['category' => $data['category'],
                'password' => $password];
            } // Warn the user if there is an invalid value for this key
          } else trigger_error('Ignoring invalid value for \'lock\' key in '.
            'this configuration file', E_USER_WARNING);
        } else trigger_error('This configuration file\'s requested category'.
          'cannot be overridden', E_USER_WARNING);
      } else trigger_error('This configuration file is improperly '.
        'formatted', E_USER_WARNING);
    }

    /**
     * Ensures that the `config` directory and (optionally) the provided file
     * path are immutable by the current process.
     *
     * If a file path is provided, it will be checked to ensure that it is a
     * readable file contained within the `config` directory.
     *
     * @see    Security::isMutableEntry()  For more information regarding how
     *                                     directory entries are checked for
     *                                     mutability and a definition of
     *                                     mutability.
     *
     * @param  string  $file               An optional absolute file path to
     *                                     check in addition to the `config`
     *                                     directory.
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
     * Scans the `config` directory for configuration files matching the
     * globular expression `*.json` and attempts to parse them.
     *
     * @see  parse()  For more information regarding how configuration files
     *                will be loaded.
     */
    public function scan(): void {
      // Get a list of JSON files in the `config` directory
      $files = array_filter(array_map('realpath', glob(
        \Eugene\Utilities\Path::make(__CONFIGROOT__, '*.json'))));
      // Filter the globular expression result to contain only files
      $files = array_filter($files, 'is_file');
      // Attempt to parse each configuration file
      foreach ($files as $file) $this->parse($file);
    }
  }
