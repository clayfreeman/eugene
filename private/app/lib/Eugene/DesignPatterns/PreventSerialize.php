<?php
  /**
   * This file provides a trait to prevent (un)serialization of classes.
   *
   * @copyright  Copyright 2016 Clay Freeman. All rights reserved.
   * @license    GNU Lesser General Public License v3 (LGPL-3.0).
   */

  // Enable strict types for this file
  declare(strict_types = 1);

  namespace Eugene\DesignPatterns;

  // End script execution if the private root is not defined
  if (!defined('__PRIVATEROOT__')) die();

  /**
   * Trait to ensure that classes cannot be meaningfully serialized or
   * unserialized using magic methods.
   */
  trait PreventSerialize {
    /**
     * Implementation of the `__sleep()` magic method to prevent this class from
     * being serialized in a meaningful way.
     *
     * @return  array  An empty array.
     */
    final public function __sleep(): array { return []; }

    /**
     * Implementation of the `__wakeup()` magic method to disregard all incoming
     * requests to unserialize data.
     */
    final private function __wakeup(): void {}
  }
