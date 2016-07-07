<?php
  /**
   * This file provides an abstract `Singleton` class responsible for outlining
   * the singleton design pattern used throughout this project.
   *
   * @copyright Copyright 2016 Clay Freeman. All rights reserved.
   * @license   GNU General Public License v3 (GPL-3.0).
   */

  namespace Pubkey2\DesignPatterns;

  // End script execution if the private root is not defined
  if (!defined('__PRIVATEROOT__')) die();

  // Create a locally-scoped alias for the `InstantiationFailureError` class
  use \Pubkey2\Exceptions\InstantiationFailureError;

  /**
   * Implementation source for the singleton design pattern.
   *
   * Uses an abstract class to force a subclass definition of a default
   * constructor and defines common methods for fetching and/or unlinking the
   * current instance.
   */
  abstract class Singleton {
    /**
     * Disregard all incoming requests to clone data.
     */
    final private function __clone() {}

    /**
     * Enforce the subclass to define a default constructor so that it can be
     * instantiated using the singleton design pattern.
     *
     * @todo                 Assign `void` return type in future version of PHP.
     *
     * @see   getInstance()  For more information regarding how an instance is
     *                       fetched (created) on request.
     */
    abstract protected function __construct();

    /**
     * Disregard all incoming requests to serialize data.
     */
    final public function __sleep() {}

    /**
     * Disregard all incoming requests to unserialize data.
     */
    final private function __wakeup() {}

    /**
     * Fetches the only instance for a given `Singleton` class or creates an
     * instance if the `Singleton` has not already been linked to one.
     *
     * Lazy instantiation is expected to take place during any call to this
     * method when the internal instance storage location contains a value
     * equivalent to `null` or an unlink is requested for the current instance.
     *
     * @param   bool       $unlink         Whether the current instance should
     *                                     be unlinked and replaced with a new
     *                                     instance.
     *
     * @throws  InstantiationFailureError  Upon failure during external
     *                                      instantiation of the called class.
     *
     * @return  Singleton                  The only instance for a given
     *                                     `Singleton`.
     */
    final public static function getInstance(
        bool $unlink = false): Singleton {
      // Declare storage for the only instance of `Singleton`
      static $instance  = null;
      // Determine if `Singleton` should be (re)instantiated
      if ($instance === null || $unlink === true)
        // There is no current instance of `Singleton`; instantiate one
        $instance = new static;
      // Check if the type of the instance is invalid
      if (!($instance instanceof Singleton))
        // Throw an exception regarding the failure state of the method
        throw new \Pubkey2\Exceptions\InstantiationFailureError('Failed to '.
          'create a new instance of '.get_called_class().': called class is '.
          'not instantiable.');
      // Return the currently held instance
      return $instance;
    }
  }
