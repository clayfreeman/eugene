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

  // Attempt to run the router
  \Eugene\Runtime\Router::getInstance()->run();

  // Dump the registry
  echo "<pre>\n".htmlentities(print_r(
    \Eugene\Runtime\Registry::getInstance(), true))."\n</pre>";

  // Dump the registry
  echo "<pre>\n".htmlentities(serialize(
    \Eugene\Runtime\Registry::getInstance()))."\n</pre>";

  // Dump the registry
  echo "<pre>\n".var_dump(\Eugene\Runtime\Registry::getInstance())."\n</pre>";

  // Dump the registry
  echo "<pre>\n".htmlentities(print_r((object)
    (\Eugene\Runtime\Registry::getInstance()), true))."\n</pre>";
