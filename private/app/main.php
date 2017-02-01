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

  echo htmlentities(print_r(\Eugene\Runtime\Registry::getInstance(), true))."\n";
  echo htmlentities(print_r(\Eugene\Utilities\Security::getInstance(), true))."\n";
  var_dump(\Eugene\Runtime\Registry::getInstance()); echo "\n";
  var_dump(\Eugene\Utilities\Security::getInstance()); echo "\n";
  echo htmlentities(var_export(\Eugene\Runtime\Registry::getInstance(), true))."\n";
  echo htmlentities(var_export(\Eugene\Utilities\Security::getInstance(), true))."\n";

  // Attempt to run the router
  \Eugene\Runtime\Router::getInstance()->run();
