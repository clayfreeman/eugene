<?php
  /**
   * This file is responsible for declaring `Crypto`, a collection of useful
   * crypto-related methods.
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
   * Collection of useful crypto-related methods to help improve overall
   * cryptographic operations.
   */
  final class Crypto extends Singleton {
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
      if (is_file($keyPath)) {
        // Load the encryption key from the filesystem
        $key       = KeyFactory::loadEncryptionKey($keyPath);
        $this->key = function() use ($key) { return $key; };
      } else {
        // Generate an encryption key and save it to the filesystem
        $key       = KeyFactory::generateEncryptionKey();
        $this->key = function() use ($key) { return $key; };
        KeyFactory::save($key, $keyPath);
      }
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
