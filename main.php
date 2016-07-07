<?php
  // Define the current file's parent directory as the private root
  define('__PRIVATEROOT__', __DIR__);

  // Load the `Path` class for easier platform-specific path generation
  require_once(realpath(implode(DIRECTORY_SEPARATOR, [__PRIVATEROOT__,
    'Pubkey2', 'Utilities', 'Autoload.php'])));

  // Create a locally-scoped alias for the `Path` class
  use \Pubkey2\Utilities\Path;

  // Load the composer autoloader
  require_once(Path::make([__PRIVATEROOT__, 'vendor', 'autoload.php']));
