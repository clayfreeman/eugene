<?php
  /**
   * This file provides a `Registry` class responsible for providing an outlet
   * for storing runtime information.
   *
   * @copyright  Copyright 2016 Clay Freeman. All rights reserved.
   * @license    GNU Lesser General Public License v3 (LGPL-3.0).
   */

  // Enable strict types for this file
  declare(strict_types = 1);

  namespace Eugene\Runtime;

  // End script execution if the private root is not defined
  if (!defined('__PRIVATEROOT__')) die();

  // Create locally-scoped aliases for `HiddenString` and `Singleton`
  use \Eugene\{DesignPatterns\Singleton, Utilities\HiddenString};

  // Create a locally-scoped alias for all possible exceptions that might be
  // thrown by this class
  use \Eugene\Exceptions\{NameUnavailableError, NameUnlockError,
    ReadLockError, WriteLockError};

  /**
   * Provides a (temporary) centralized storage location for information
   * commonly required during runtime by various other mechanisms.
   */
  final class Registry extends Singleton {
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
     * Access-restricted storage location for all data within the object.
     *
     * @var  array
     */
    protected $storage = [];

    /**
     * An associative array of locking information whose keys intersect with the
     * registry's internal storage.
     *
     * Items equivalent to `null` represent items with a permanent write-lock
     * where items with a non-null string value represent items with a read-lock
     * using the given value as the lock's password.
     *
     * @var  array
     */
    protected $locks   = [];

    /**
     * An empty constructor to satisfy the parent's abstract method
     * prototype definition.
     */
    protected function __construct() {}

    /**
     * Used to create and store an item by the specified name.
     *
     * If the provided name is already in use then there will be no change to
     * the internal registry storage and `false` will be returned.
     *
     * This method is essentially a wrapper for `set()` that ensures the
     * requested name is not currently in use.
     *
     * @see     set()                 For more information on the underlying
     *                                operation that takes place when assigning
     *                                a value to a name and possible uncaught
     *                                exceptions from upstream.
     *
     * @param   string  $key          The name used to reference the
     *                                provided item.
     * @param   mixed   $data         The item to store in the registry.
     *
     * @throws  NameUnavailableError  Upon encountering an existing entry using
     *                                the specified name.
     *
     * @return  mixed                 A reference to the provided data (after
     *                                being stored in the registry).
     */
    public function create(string $key, $data) {
      // Check if the requested name is currently in use
      if ($this->isset($key)) throw new NameUnavailableError('Failed to '.
        'create name '.escapeshellarg($key).' in the Registry: the provided'.
        'name is already in-use.');
      // Defer assignment to the `set()` method and return the resulting
      // reference to the caller
      return $this->set($key, $data);
    }

    /**
     * Retrieves the value stored by the specified name.
     *
     * @param   string        $key    The name used to reference the
     *                                requested item.
     * @param   HiddenString  $key    The password to access the given item.
     *
     * @throws  NameUnavailableError  Upon determining that the provided name
     *                                does not exist.
     * @throws  ReadLockError         Upon determining that the provided name
     *                                is read-locked.
     *
     * @return  mixed                 The value stored at the specified name.
     */
    public function get(string $key, ?HiddenString $password = null) {
      // Check if the requested name exists
      if (!$this->isset($key)) throw new NameUnavailableError('Failed to '.
        'get name '.escapeshellarg($key).' in the Registry: the provided name'.
        'does not exist');
      // Check if the requested name is read-locked and either no password or an
      // invalid password was provided
      if ($this->isReadLocked($key) && ($password === null ||
          !password_verify($password->getString(),
          $this->locks[$key]->getString())))
        throw new ReadLockError('Failed to get name '.escapeshellarg($key).' '.
          'in the Registry: the provided name is read-locked');
      // Return the item stored by the specified name
      return $this->storage[$key];
    }

    /**
     * Determines if the specified name is read-locked.
     *
     * @param   string  $key  The name used to reference the requested item.
     *
     * @return  bool          `true`  if the name is read-locked,
     *                        `false` otherwise.
     */
    public function isReadLocked(string $key): bool {
      // Check if the internal locking system contains a value not equivalent to
      // `null` for the specified name
      return isset ($this->locks[$key]) &&
             strlen($this->locks[$key]->getString()) !== 0;
    }

    /**
     * Determines if the specified name is write-locked.
     *
     * @param   string  $key  The name used to reference the requested item.
     *
     * @return  bool          `true`  if the name is write-locked,
     *                        `false` otherwise.
     */
    public function isWriteLocked(string $key): bool {
      // Check if the internal locking system contains a value for the
      // specified name
      return isset ($this->locks[$key]);
    }

    /**
     * Check the internal storage for a value at the specified name.
     *
     * @param   string  $key  The name used to reference the requested item.
     *
     * @return  bool          `true`  if the name exists,
     *                        `false` otherwise.
     */
    public function isset(string $key): bool {
      // Check the internal storage for a value at the specified name
      return isset($this->storage[$key]);
    }

    /**
     * Locks the provided name from being altered (write-lock) or read
     * (read-lock) without first unlocking it.
     *
     * A read-lock is either a temporary or permanent lock to prevent read and
     * write access to the provided name without first unlocking it with the
     * correct password. This type of lock can be reversed if the password used
     * to lock the name is not lost.
     *
     * A write-lock is a permanent lock to prevent write access to the provided
     * name, however read access is not restricted. This type of lock only
     * applies to the name used by the object and not the object itself. Any
     * interface provided by the object, whether it be a public variable or
     * method, can still be used to mutate the object.
     *
     * Read-locks can be cleared via the `unlock()` method by providing the
     * initial lock password.
     *
     * @see     unlock()                 For more information regarding the
     *                                   process of clearing read-locks.
     *
     * @param   string        $key       The name that should be locked.
     * @param   HiddenString  $password  If a password (i.e. non-null value) is
     *                                   provided then a temporary read-lock
     *                                   will be placed, however if `null` is
     *                                   provided then a permanent write-lock
     *                                   will be placed.
     *
     * @return  bool                     `true`  if the provided name was
     *                                   locked, `false` otherwise.
     */
    public function lock(string $key, ?HiddenString $password = null): bool {
      // Check that the requested name is not currently locked
      if ($this->isset($key) && !$this->isWriteLocked($key)) {
        // Store the requested locking information in the registry
        $this->locks[$key] = new HiddenString(
          password_hash($password->getString(), PASSWORD_DEFAULT));
        // Return a valid state
        return true;
      } // Return a failure state
      return false;
    }

    /**
     * Used to store an item by the specified name (overriding any already
     * existing value).
     *
     * @param   string        $key   The name used to reference the item.
     * @param   mixed         $data  The item to store in the registry.
     * @param   HiddenString  $data  The item to store in the registry.
     *
     * @throws  ReadLockError        Upon encountering a read-lock using the
     *                               specified name.
     * @throws  WriteLockError       Upon encountering a write-lock using the
     *                               specified name.
     *
     * @return  mixed                A reference to the provided data (after
     *                               being stored in the registry).
     */
    public function set(string $key, $data, ?HiddenString $password = null) {
      // Check that the requested name is not currently locked
      if ($this->isWriteLocked($key))
        // Throw a write-lock related error if not read-locked
        if (!$this->isReadLocked($key))
          throw new WriteLockError('Failed to write using name '.
            escapeshellarg($key).' to the Registry: the provided name '.
            'is locked');
        // Throw a read-lock related error if read-locked and an invalid
        // password was provided
        else if ($password === null || !password_verify($password->getString(),
            $this->locks[$key]->getString()))
          throw new ReadLockError('Failed to write using name '.
            escapeshellarg($key).' to the Registry: the provided name '.
            'is locked');
      // Store the provided data at the requested name
      $this->storage[$key] = $data;
      // Return a reference to the stored data
      return $this->get($key);
    }

    /**
     * Attempts to unlock the requested name with the supplied password.
     *
     * @param   string        $key       The name that should be unlocked.
     * @param   HiddenString  $password  The password that was originally used
     *                                   to read-lock the specified name.
     *
     * @throws  NameUnlockError          Upon encountering an incorrect password
     *                                   supplied for use in unlocking a name.
     *
     * @return  bool                     `true`  if the specified name was
     *                                   unlocked, `false` otherwise.
     */
    public function unlock(string $key, HiddenString $password): bool {
      // Check that the requested name is currently read-locked
      if ($this->isset($key) && $this->isReadLocked($key)) {
        // Ensure that the provided password matches the read-lock password
        if (!password_verify($password->getString(),
            $this->locks[$key]->getString()))
          throw new NameUnlockError('Failed to unlock using name '.
            escapeshellarg($key).' in the Registry: the provided password '.
            'is invalid');
        // Remove the requested locking information from the registry
        unset($this->locks[$key]);
        // Return a valid state
        return true;
      } // Return a failure state
      return false;
    }

    /**
     * Undefines a given name's value in the registry.
     *
     * @param   string  $key    The name that should be undefined.
     *
     * @throws  ReadLockError   Upon encountering a read-lock using the
     *                          specified name.
     * @throws  WriteLockError  Upon encountering a write-lock using the
     *                          specified name.
     */
    public function unset(string $key, ?HiddenString $password = null): void {
      // Check that the requested name is not currently locked
      if ($this->isWriteLocked($key))
        // Throw a write-lock related error if not read-locked
        if (!$this->isReadLocked($key))
          throw new WriteLockError('Failed to unset using name '.
            escapeshellarg($key).' to the Registry: the provided name '.
            'is locked');
        // Throw a read-lock related error if read-locked and an invalid
        // password was provided
        else if ($password === null || !password_verify($password->getString(),
            $this->locks[$key]->getString()))
          throw new ReadLockError('Failed to unset using name '.
            escapeshellarg($key).' to the Registry: the provided name '.
            'is locked');
      // Remove the element by the specified name
      if (isset($this->storage[$key])) unset($this->storage[$key]);
    }
  }
