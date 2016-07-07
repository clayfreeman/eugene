<?php
  /**
   * Temporary entrypoint to the application.
   *
   * @copyright Copyright 2016 Clay Freeman. All rights reserved.
   * @license   GNU General Public License v3 (GPL-3.0).
   */

  // Load the `Path` class for easier platform-specific path generation
  $bootstrap = realpath(__DIR__.DIRECTORY_SEPARATOR.'bootstrap.php');
  (is_file($bootstrap) && is_readable($bootstrap) && include_once($bootstrap))
    or die("Could not load the bootstrap routine.\n");
