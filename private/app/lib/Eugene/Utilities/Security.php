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

  // Create locally-scoped aliases for the `HiddenString` and `Path` classes
  use \Eugene\Utilities\{HiddenString, Path};

  // Create locally-scoped aliases for the `KeyFactory` and `Password` classes
  use \ParagonIE\Halite\{Halite, KeyFactory, Password};

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
     * The symmetric key used for internal password hash encryption.
     *
     * @var  EncryptionKey
     */
    protected $key         = null;

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
      // Define some arrays of paths that should be conditionally allowed
      $ro = [__APPROOT__, __VENDORROOT__]; $rw = [__DATAROOT__];
      // Include `__CONFIGROOT__` and `__KEYROOT__` in non-strict mode
      if ($strict === false) { $ro[] = __CONFIGROOT__; $rw[] = __KEYROOT__; }
      // Define a list of allowed paths during application runtime based on the
      // restricted read-only and read-write paths
      $allowed = array_filter(array_merge($ro, $rw), function($input) {
        return !strstr($input, PATH_SEPARATOR); });
      // Restrict file access to prevent unauthorized tampering of application
      // (see http://php.net/manual/en/ini.core.php#ini.open-basedir for more
      // information regarding file restriction)
      ini_set('open_basedir', implode(PATH_SEPARATOR, $allowed));
    }

    /**
     * Hashes a password using ParagonIE's Halite library.
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

    /**
     * Rehashes a password using ParagonIE's Halite library.
     *
     * The provided hash will be updated by reference if necessary.
     *
     * @param   HiddenString  $password       The clear text to be hashed.
     * @param   string        $hash           The ciphertext to be rehashed.
     * @param   string        $level          The strength at which to generate
     *                                        the password hash.
     *
     * @see     \ParagonIE\Halite\KeyFactory  For more information regarding
     *                                        available hash strengths.
     *
     * @return  bool                          `true`  if the hash was changed,
     *                                        `false` otherwise.
     */
    public function passwordRehash(HiddenString $password, string &$hash,
        string $level = KeyFactory::INTERACTIVE): bool {
      // Determine if the provided password needs to be rehashed
      if (Password::needsRehash($hash, $this->key, $level)) {
        // Rehash the password if necessary
        $hash = $this->passwordHash($password, $level);
        // Return `true` if the password was rehashed
        return true;
        // Return `false` if the hash did not change
      } return false;
    }

    /**
     * Verifies a password against a hash using ParagonIE's Halite library.
     *
     * @param   HiddenString  $password  The clear text password to check.
     * @param   string        $hash      The ciphertext to validate against.
     *
     * @return  bool                     Whether the password matches the hash.
     */
    public function passwordVerify(HiddenString $password, string $hash): bool {
      // Defer cryptography to ParagonIE's Halite library (with defaults)
      return Password::verify($password->getValue(), $hash, $this->key);
    }
  }
