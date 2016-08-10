<?php
  /**
   * //
   *
   * @copyright Copyright 2016 Clay Freeman. All rights reserved.
   * @license   GNU General Public License v3 (GPL-3.0).
   */

  namespace Eugene\Database;

  // End script execution if the private root is not defined
  if (!defined('__PRIVATEROOT__')) die();

  // Create a locally-scoped alias for the `Address` class and its exceptions
  use \Eugene\{Exceptions\DNSResolutionError, Utilities\Address};

  // Create a locally-scoped alias for the `InvalidHostnameException` class
  use \Eugene\Exceptions\InvalidHostnameException;

  /**
   * //
   */
  final class MySQL {
    /**
     * //
     */
    public function __construct(string $hostname, string $username,
        string $password, int $port = 3306) {
      // Validate the provided port number
      if ($port < 1 || $port > 65535) throw new InvalidPortException('Could '.
        'not use the specified port: '.$port);
      // Attempt to resolve the provided hostname
      $addresses = (new Address($hostname))->getAddresses(true);
      // Ensure that there is at least one address available for use
      if (shuffle($addresses) === false || count($addresses) === 0)
        throw new InvalidHostnameException('Could not used the provided '.
          'hostname for connecting to MySQL: '.escapeshellarg($hostname));
      // Grab the first address from the array of addresses
      $address = array_shift($addresses);
      echo var_export($address, true)."\n";
      // Create a new PDO instance with the filtered parameters
      $this->connection = new PDO('mysql:charset=utf8mb4;host='.$address.
        ';port='.$port, $username, $password);
    }
  }
