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

  // Create locally-scoped aliases for the `Singleton` and `Path` classes
  use \Eugene\{DesignPatterns\Singleton, Utilities\Path};

  // Create locally-scoped aliases for the `KeyFactory` and `Password` classes
  use \ParagonIE\Halite\{Halite, KeyFactory, Password};

  /**
   * Collection of useful security-related methods to help improve overall
   * runtime application security.
   */
  final class Security extends Singleton {
    /**
     * Disallow unlinks via `getInstance(true)`.
     *
     * @var  bool
     */
    protected $allowUnlink = false;

    /**
     * Responsible for generating the symmetric encryption key used by Halite if
     * it doesn't already exist.
     */
    protected function __construct() {
      // Ensure that Halite is able to work correctly
      Halite::isLibsodiumSetupCorrectly(true) or die();
      // Check if the encryption key exists
      $keyPath = Path::make(__KEYROOT__, 'default.key');
      if (is_file($keyPath))
        // Load the encryption key from the filesystem
        $this->key = KeyFactory::loadEncryptionKey($keyPath);
      else {
        // Generate an encryption key and save it to the filesystem
        $this->key = KeyFactory::generateEncryptionKey();
        KeyFactory::save($this->key, $keyPath);
      }
    }

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
     * Restricts filesystem access outside of required application areas.
     *
     * By default, this method configures `open_basedir` to allow access to the
     * following directories:
     *
     *  - `private/app` (read-only)
     *  - `private/config` (read-only)
     *  - `private/data` (read-write)
     *  - `private/keys` (read-write)
     *  - `vendor` (read-only)
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
      // Define an array of paths that should be allowed if itself and all
      // children are read-only
      $ro = [__APPROOT__, __VENDORROOT__];
      $rw = [__DATAROOT__];
      // If operating under strict mode, `__CONFIGROOT__` and `__KEYROOT__`
      // should be excluded
      if ($strict === false) {
        $ro[] = __CONFIGROOT__;
        $rw[] = __KEYROOT__;
      } // Filter the allowed read-only paths to recursively immutable paths
      $ro = array_filter($ro, function($input) {
        return Security::getInstance()->fileIsRecursivelyMutable($input); });
      // Define a list of allowed paths during application runtime based on the
      // restricted read-only and read-write paths
      $allowed = array_filter(array_merge($ro, $rw), function($input) {
        return is_dir($input) && is_readable($input) &&
          !stristr($input, PATH_SEPARATOR); });
      // Restrict file access to prevent unauthorized tampering of application
      // (see http://php.net/manual/en/ini.core.php#ini.open-basedir for more
      // information regarding file restriction)
      ini_set('open_basedir', implode(PATH_SEPARATOR, $allowed));
    }

    /**
     * Hashes a password using ParagonIE's Halite library with secure defaults.
     *
     * @param   HiddenString  $password       The clear text to be hashed.
     * @param   string        $level          The strength at which to generate
     *                                        the password hash.
     *
     * @see     \ParagonIE\Halite\KeyFactory  For more information regarding
     *                                        available hash strengths.
     *
     * @return  string                        The resulting ciphertext.
     */
    public function passwordHash(HiddenString $password,
        string $level = KeyFactory::INTERACTIVE): string {
      // Defer cryptography to ParagonIE's Halite library (with defaults)
      return Password::hash($password->getValue(), $this->key, $level);
    }
  }
