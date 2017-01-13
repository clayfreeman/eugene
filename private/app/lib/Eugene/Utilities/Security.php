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
     * TODO
     *
     * @param   string  $path  [description]
     *
     * @return  array          [description]
     */
    protected function fastRecursiveFileEnumerator(string $path): array {
      // Allocate an array to hold the results (initialized with the given path)
      $results = [$path];
      // Get a list of all directory entries for the provided path
      $scandir = @scandir($path, SCANDIR_SORT_NONE);
      // Iterate over each directory entry to expand child directories
      if (is_array($scandir)) foreach ($scandir as $file) {
        // Skip dot file results to prevent duplicate entries
        if ($file == '.' || $file == '..') continue;
        // Convert the relative file name to an absolute file name
        $file = $path.__DS__.$file;
        // Expand this path and merge the results
        $results = array_merge($results, 
          $this->fastRecursiveFileEnumerator($file));
      // Return the array filled with file paths
      } return $results;
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
      // Fetch runtime information about the current process
      $gid  = array_merge([posix_getegid(), posix_getgid()], posix_getgroups());
      $uid  =             [posix_geteuid(), posix_getuid()];
      // Attempt to fetch ownership and file mode information for the given path
      $stat =   @stat($file);
      // Ensure that an error did not occur while checking this file
      if (!is_array($stat)) trigger_error('Could not stat() this '.
        'file', E_USER_ERROR);
      // Ensure that this file is not owned by UID/GID zero
      if ($root = ($stat['uid'] == 0 || $stat['gid'] == 0))
        trigger_error('UID/GID cannot be zero (sanity check)', E_USER_WARNING);
      return $root || // In addition to the warning, mark the file as mutable
        // Check if the file can be modified via other or user access
        (in_array($stat['uid'], $uid) || ($stat['mode'] & 0002) != 0 ||
        // Check if the file can be modified via group access
        (in_array($stat['gid'], $gid) && ($stat['mode'] & 0020) != 0));
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
          // Call `fastRecursiveFileEnumerator` with the provided file path
          $entries = $this->fastRecursiveFileEnumerator($file);
          // Check the children of the provided file path
          foreach ($entries as $name) {
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
        if ($retval = Security::getInstance()->fileIsRecursivelyMutable($input))
          trigger_error('This path is recursively mutable', E_USER_WARNING);
        return !$retval; });
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
