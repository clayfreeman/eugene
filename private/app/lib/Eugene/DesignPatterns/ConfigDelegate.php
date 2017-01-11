<?php
  /**
   * This file provides a delegate interface for the `Config` class.
   *
   * @copyright  Copyright 2016 Clay Freeman. All rights reserved.
   * @license    GNU Lesser General Public License v3 (LGPL-3.0).
   */

  // Enable strict types for this file
  declare(strict_types = 1);

  namespace Eugene\DesignPatterns;

  // End script execution if the private root is not defined
  if (!defined('__PRIVATEROOT__')) die();

  // Create a locally-scoped alias for the `HiddenString` class
  use \Eugene\Utilities\HiddenString;

  /**
   * Interface for ensuring the implementation of a credential receiver for the
   * `Config` class.
   */
  interface ConfigDelegate {
    /**
     * This method is required for read-lock credential delivery.
     *
     * @param  string        $category  The category the credential belongs to.
     * @param  HiddenString  $password  The password for this category.
     */
    public static function receiveCredential(string $category,
      HiddenString $password): void;
  }
