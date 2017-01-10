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

  // Dump the server super global
  echo "<pre>\n".var_export($_SERVER, true)."\n</pre>";

  // Attempt to run the router
  \Eugene\Runtime\Router::getInstance()->run();
