<?php
  /**
   * This file provides an `Exception` subclass to describe a type of unexpected
   * `Registry` failure.
   *
   * @copyright  Copyright 2016 Clay Freeman. All rights reserved.
   * @license    GNU Lesser General Public License v3 (LGPL-3.0).
   */

  // Enable strict types for this file
  declare(strict_types = 1);

  namespace Eugene\Exceptions;

  // End script execution if the private root is not defined
  if (!defined('__PRIVATEROOT__')) die();

  /**
   * An `Exception` subclass responsible for conveying an unexpected failure
   * during internal operations in a `Registry` class.
   */
  class WriteLockError extends \Exception {}
