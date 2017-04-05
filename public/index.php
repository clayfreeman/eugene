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
    $privateDir = [dirname(__DIR__), 'private'];
    $appPhar    = implode(DIRECTORY_SEPARATOR,
      array_merge($privateDir, ['app.phar']));
    $bootstrap  = implode(DIRECTORY_SEPARATOR,
      array_merge($privateDir, ['app', 'bootstrap.php']));
    // Check if a pre-compiled application binary exists
    if (is_file($appPhar) && is_readable($appPhar)) {
      // Load the app archive to continue
      require_once($appPhar);
    } else {
      // Emit a warning to encourage the user to compile the app for security
      trigger_error('It is recommended to compile the application using '.
        '`php build.php`', E_USER_WARNING);
      // Load the app directly from disk to continue
      require_once($bootstrap);
    }
  })();
