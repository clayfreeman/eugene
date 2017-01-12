<?php
  /**
   * This file provides a trait to hide all internal class members.
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
   * Trait to ensure that all class members are hidden from functions such as
   * `serialize()`, `print_r()`, and `var_dump()`.
   *
   * This will not work for `var_export()`, hence our recommendation for
   * disabling it.
   */
  trait HiddenMembers {
    /**
     * Implementation of the `__debugInfo()` magic method to prevent members of
     * this class from being revealed via `print_r()` and `var_dump()`.
     *
     * @return  array  An empty array.
     */
    public function __debugInfo(): array  { return []; }

    /**
     * Implementation of the `__sleep()` magic method to prevent this class from
     * being serialized in a meaningful way.
     *
     * @return  array  An empty array.
     */
    public function     __sleep(): array  { return []; }

    /**
     * Implementation of the `__toString()` magic method to force the `string`
     * representation of the class to be the class name.
     *
     * @return  string  The class name.
     */
    public function  __toString(): string { return get_class($this); }
  }
