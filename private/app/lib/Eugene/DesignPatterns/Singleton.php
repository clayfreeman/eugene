<?php
  /**
   * This file provides an abstract `Singleton` class responsible for outlining
   * the singleton design pattern used throughout this project.
   *
   * @copyright  Copyright 2016 Clay Freeman. All rights reserved.
   * @license    GNU Lesser General Public License v3 (LGPL-3.0).
   */

  // Enable strict types for this file
  declare(strict_types = 1);

  namespace Eugene\DesignPatterns;

  // End script execution if the private root is not defined
  if (!defined('__PRIVATEROOT__')) die();

  // Provide manual support for loading external dependencies
  { $_class = implode(__DS__, [__CLASSPATH__, 'Eugene', 'Exceptions',
    'InstantiationFailureError.php']); require_once($_class); }
  { $_class = implode(__DS__, [__CLASSPATH__, 'Eugene', 'DesignPatterns', 
    'PreventSerialize.php']);          require_once($_class); }

  // Create a locally-scoped alias for the `InstantiationFailureError` class
  use \Eugene\Exceptions\InstantiationFailureError;

  /**
   * Implementation source for the singleton design pattern.
   *
   * Uses an abstract class to force a subclass definition of a default
   * constructor and defines common methods for fetching and/or unlinking the
   * current instance.
   */
  abstract class Singleton {
    // Prevent serialization of any `Singleton` class
    use \Eugene\DesignPatterns\PreventSerialize;

    /**
     * Allows the subclass to determine whether it can be unlinked via
     * subsequent calls to `getInstance(true)`.
     *
     * @var  bool
     */
    protected $allowUnlink = true;

    /**
     * Disregard all incoming requests to clone data.
     */
    final private function __clone() {}

    /**
     * Enforce the subclass to define a default constructor so that it can be
     * instantiated using the singleton design pattern.
     *
     * @see   getInstance()  For more information regarding how an instance is
     *                       fetched (created) on request.
     */
    abstract protected function __construct();

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
     *                                     instance. This argument is ignored if
     *                                     the subclass disallows unlinking.
     *
     * @throws  InstantiationFailureError  Upon failure during external
     *                                     instantiation of the called class.
     *
     * @return  Singleton                  The only instance for a given
     *                                     `Singleton`.
     */
    final public static function getInstance(
        bool $unlink = false): Singleton {
      // Declare storage for the only instance of `Singleton`
      static $instance  = null;
      // Determine if `Singleton` should be (re)instantiated
      if ($instance === null || ($unlink === true &&
          $instance->allowUnlink === true))
        // There is no current instance of `Singleton`; instantiate one
        $instance = new static;
      // Check if the type of the instance is invalid
      if (!($instance instanceof Singleton))
        // Throw an exception regarding the failure state of the method
        throw new InstantiationFailureError('Failed to create a new instance '.
          'of '.get_called_class().': called class is not instantiable');
      // Return the currently held instance
      return $instance;
    }
  }
