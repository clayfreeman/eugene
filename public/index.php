<?php
  /**
   * Public entrypoint to the application.
   *
   * @copyright  Copyright 2016 Clay Freeman. All rights reserved.
   * @license    GNU General Public License v3 (GPL-3.0).
   */

  // Enable strict types for this file
  declare(strict_types = 1);

  // Run the application bootstrap routine
  // implode(DIRECTORY_SEPARATOR, [dirname(__DIR__), 'private', 'app', 'bootstrap.php'])
  $bootstrap = realpath();
  (is_file($bootstrap) && is_readable($bootstrap) && include_once($bootstrap))
    or trigger_error("Could not load the bootstrap routine", E_USER_ERROR);

  // Load the application logic file
  silent_include(\Eugene\Utilities\Path::make(__PRIVATEROOT__, 'main.php'))
    or trigger_error("Could not load the application logic file", E_USER_ERROR);
