<?php
  // Ensure that PHARs can be created with the current configuration
  if (boolval(ini_get('phar.readonly'))) {
    echo "Unable to create PHAR files; Try again by running:\n";
    echo "$ php -d phar.readonly=0 ".escapeshellarg($argv[0])."\n";
    die();
  }

  // Define the required well-known paths for the application
  define('__DS__',          DIRECTORY_SEPARATOR);
  define('__PRIVATEROOT__', implode(__DS__, [__DIR__,         'private']));
  define('__APPDEST__',     implode(__DS__, [__PRIVATEROOT__, 'app.phar']));
  define('__PUBKEY__',      __APPDEST__.'.pubkey');
  define('__SECKEY__',      __APPDEST__.'.seckey');

  // Ensure that a secret key exists in the private directory
  $secret = @openssl_pkey_get_private(@file_get_contents(__SECKEY__));
  if (!is_file(__SECKEY__) || !is_readable(__SECKEY__) || $secret === false) {
    echo "Unable to sign PHAR file; cannot access secret key.\n";
    echo "You can generate a secret key by running:\n";
    echo "$ openssl genrsa -out ".escapeshellarg(__SECKEY__)." 8192\n";
    die();
  }

  // Remove any pre-existing compiled application
  @unlink(__APPDEST__); @unlink(__PUBKEY__);

  // Create a new PHAR at the expected location from the application's root
  $phar = new Phar(__APPDEST__);
  $phar->buildFromDirectory(implode(__DS__, [__PRIVATEROOT__, 'app']));

  // Create a default stub to the application's bootstrapper
  $phar->setDefaultStub('bootstrap.php');

  // Sign the PHAR with the resulting secret key
  openssl_pkey_export($secret, $secret_out);
  file_put_contents(__PUBKEY__, openssl_pkey_get_details($secret)['key']);
  $phar->setSignatureAlgorithm(Phar::OPENSSL, $secret_out);
