<?php
  /**
   * Public entrypoint to the application.
   *
   * @copyright  Copyright 2016 Clay Freeman. All rights reserved.
   * @license    GNU General Public License v3 (GPL-3.0).
   */

  // Enable strict types for this file
  declare(strict_types = 1);

  // Load the `Path` class for easier platform-specific path generation
  $bootstrap = realpath(dirname(__DIR__).DIRECTORY_SEPARATOR.'bootstrap.php');
  (is_file($bootstrap) && is_readable($bootstrap) && include_once($bootstrap))
    or die("Could not load the bootstrap routine.\n");

  // Load the application logic file
  silent_include(\Eugene\Utilities\Path::make([__PRIVATEROOT__, 'main.php']))
    or die("Could not load the application logic file.\n");
