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
    // Load the pre-compiled application binary
    require_once(implode(DIRECTORY_SEPARATOR,
      [dirname(__DIR__), 'private', 'app.phar']));
  })();
