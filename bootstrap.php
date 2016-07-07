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
  silent_include(realpath(implode(DIRECTORY_SEPARATOR, [__PRIVATEROOT__,
    'Pubkey2', 'Utilities', 'Autoload.php']))) or die('Could not load the '.
    "project's autoload utility.\n");

  // Load the composer autoloader
  silent_include(\Pubkey2\Utilities\Path::make([
    __PRIVATEROOT__, 'vendor', 'autoload.php'
  ])) or die("Could not load composer's autoload utility.\n");

  /**
   * Attempt to silently include the provided path.
   *
   * @param  string $path The desired path to include.
   *
   * @return bool         `true` if included successfully, `false` on failure.
   */
  function silent_include(string $path = null): bool {
    // Silently attempt to include the provided path
    return is_file($path) && is_readable($path) && @include_once($path);
  }
