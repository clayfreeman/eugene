<?php
  /**
   * Secret entrypoint to the application.
   *
   * @copyright  Copyright 2016 Clay Freeman. All rights reserved.
   * @license    GNU Lesser General Public License v3 (LGPL-3.0).
   */

  // Enable strict types for this file
  declare(strict_types = 1);

  // End script execution if the private root is not defined
  if (!defined('__PRIVATEROOT__')) die();

  // Test the password hashing method of the `Security` class
  echo ($cipher = \Eugene\Utilities\Security::getInstance()->passwordHash(
    new \Eugene\Utilities\HiddenString('test')))."\n";
  echo var_export(\Eugene\Utilities\Security::getInstance()->passwordVerify(
    new \Eugene\Utilities\HiddenString('test'), $cipher), true)."\n";
