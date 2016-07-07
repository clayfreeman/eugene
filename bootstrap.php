<?php
  /**
   * This file serves as an application entrypoint routine responsible for
   * preparing the application's runtime.
   *
   * @copyright Copyright 2016 Clay Freeman. All rights reserved.
   * @license   GNU General Public License v3 (GPL-3.0).
   */

  // Define the current file's parent directory as the private root
  define('__PRIVATEROOT__', __DIR__);

  // Load the `Path` class for easier platform-specific path generation
  require_once(realpath(implode(DIRECTORY_SEPARATOR, [__PRIVATEROOT__,
    'Pubkey2', 'Utilities', 'Autoload.php'])));

  // Load the composer autoloader
  require_once(\Pubkey2\Utilities\Path::make([
    __PRIVATEROOT__, 'vendor', 'autoload.php'
  ]));
