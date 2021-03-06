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
        } else trigger_error(escapeshellarg($class).' is not applicable to '.
          'receive configuration credentials', E_USER_WARNING);
      }
    }

    /**
     * Attempts to parse the requested file from the `config` directory.
     *
     * The provided file path must be an absolute path to an immutable file
     * inside the `config` directory. The file must also match the following
     * JSON document specification (`*.json` naming scheme required for
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
     * @param  string  $file  Absolute file path to an immutable file.
     */
    protected function parse(string $file): void {
      // Ensure that we're using the absolute path
      $file = realpath($file);
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
            escapeshellarg($file), E_USER_WARNING);
        } else trigger_error('The requested category in '.
          escapeshellarg($file).' cannot be overridden', E_USER_WARNING);
      } else trigger_error('The configuration file at '.
        escapeshellarg($file).' is improperly formatted', E_USER_WARNING);
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
