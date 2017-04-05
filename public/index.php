<?php
  /**
   * Public entrypoint to the application.
   *
   * @copyright  Copyright 2016 Clay Freeman. All rights reserved.
   * @license    GNU Lesser General Public License v3 (LGPL-3.0).
   */

  // Enable strict types for this file
  declare(strict_types = 1);

  // Run the application bootstrap routine
  (function() {
    // Build an array of path components referring to important items
    $appPhar    = implode(DIRECTORY_SEPARATOR,
      [dirname(__DIR__), 'private', 'app.phar']);
    $bootstrap  = implode(DIRECTORY_SEPARATOR,
      [dirname(__DIR__), 'private', 'app', 'bootstrap.php']);
    // Check if a pre-compiled application binary exists
    require_once(is_file($appPhar) && is_readable($appPhar) ?
      $appPhar : $bootstrap);
  })();
