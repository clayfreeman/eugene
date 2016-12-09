<?php
  /**
   * This file provides an object responsible for hiding the contents of a
   * string from error logs and other outlets.
   *
   * @copyright  Copyright 2016 Clay Freeman. All rights reserved.
   * @license    GNU General Public License v3 (GPL-3.0).
   */

  // Enable strict types for this file
  declare(strict_types = 1);

  namespace Eugene\Utilities;

  // End script execution if the private root is not defined
  if (!defined('__PRIVATEROOT__')) die();

  /**
   * Responsible for encapsulating a raw `string` so that its value is not
   * leaked in error logs and exceptions.
   *
   * This class is a wrapper for Halite's `HiddenString` class written by
   * Paragon Initiative Enterprises, LLC. The purpose of this wrapper is to
   * change the default behavior of `HiddenString` to always prevent inline use
   * and serialization.
   */
  final class HiddenString {
    /**
     * The internal storage location for the `HiddenString` value.
     *
     * @var  HiddenString
     */
    protected $value = null;

    /**
     * Creates an instance of `HiddenString` with the contents of the provided
     * raw `string`.
     *
     * @param  string  $contents  The value that the instance should represent.
     */
    public function __construct(string $contents) {
      $this->value = new \ParagonIE\Halite\HiddenString($contents, true, true);
    }

    /**
     * Fetches the internal `string` value held by the `HiddenString` instance.
     *
     * @return  string  The internal `string` value held by the `HiddenString`.
     */
    public function getString(): string {
      return $this->value->getString();
    }
  }
