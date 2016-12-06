<?php
  /**
   * This file provides an object responsible for hiding the contents of a
   * string from error logs and other outlets.
   *
   * @copyright  Copyright 2016 Clay Freeman. All rights reserved.
   * @license    GNU General Public License v3 (GPL-3.0).
   */

  namespace Eugene\Utilities;

  // End script execution if the private root is not defined
  if (!defined('__PRIVATEROOT__')) die();

  /**
   * Responsible for encapsulating a raw `string` so that its value is not
   * leaked in error logs and exceptions.
   */
  final class HiddenString {
    /**
     * The internal storage location for the `string` value.
     *
     * @var  string
     */
    protected $value = null;

    /**
     * Creates an instance of `HiddenString` with the contents of the provided
     * raw `string`.
     *
     * @param  string  $contents The value that the instance should represent.
     */
    public function __construct(string $contents) {
      $this->value = $contents;
    }

    /**
     * Uses the internal `string` value to represent the class instance during
     * inline operations.
     *
     * @return  string  The internal `string` value held by the instance.
     */
    public function __toString(): string {
      return $this->value;
    }
  }
