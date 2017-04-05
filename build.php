<?php
  // Ensure that PHARs can be created with the current configuration
  if (boolval(ini_get('phar.readonly'))) {
    echo "Unable to create PHAR files; Try again by running:\n";
    echo "$ php -d phar.readonly=0 ".escapeshellarg($argv[0])."\n";
    die();
  }

  // Define the required well-known paths for the application
  define('__DS__',      DIRECTORY_SEPARATOR);
  define('__APPDEST__', implode(__DS__, [__DIR__, 'private', 'app.phar']));

  // Remove any pre-existing compiled application
  @unlink(__APPDEST__);

  // Create a new PHAR at the expected location from the application's root
  $phar = new Phar(__APPDEST__);
  $phar->buildFromDirectory(implode(__DS__, [__PRIVATEROOT__, 'app']));

  // Create a default stub to the application's bootstrapper
  $phar->setDefaultStub(implode(__DS__, 'app', 'bootstrap.php'));

  // TODO: OpenSSL Signature of PHAR file
