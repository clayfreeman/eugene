<?php
  /**
   * This file provides an `Exception` subclass to describe a type of unexpected
   * error when encountering an invalid hostname.
   *
   * @copyright  Copyright 2016 Clay Freeman. All rights reserved.
   * @license    GNU General Public License v3 (GPL-3.0).
   */

  namespace Eugene\Exceptions;

  // End script execution if the private root is not defined
  if (!defined('__PRIVATEROOT__')) die();

  /**
   * An `Exception` subclass responsible for conveying an unexpected failure
   * when encountering an invalid hostname.
   */
  class InvalidHostnameException extends \Exception {}
