<?php
  /**
   * Defines a hardened, MySQL-specific PDO wrapper for setting up secure
   * database connections.
   *
   * @copyright  Copyright 2016 Clay Freeman. All rights reserved.
   * @license    GNU General Public License v3 (GPL-3.0).
   */

  namespace Eugene\Database;

  // End script execution if the private root is not defined
  if (!defined('__PRIVATEROOT__')) die();

  // Create a locally-scoped alias for the `NetworkEndpoint` class
  use \Eugene\Utilities\NetworkEndpoint;

  // Create a locally-scoped alias for the `HiddenString` class
  use \Eugene\Utilities\HiddenString;

  /**
   * PDO wrapper class to securely setup connections to MySQL servers.
   */
  final class MySQL extends \PDO {
    /**
     * Replacement constructor for the default constructor provided by PDO.
     *
     * This constructor is responsible for creating hardened instances of PDO
     * connections to MySQL.
     *
     * Injection via disagreement of client/server character set is fixed by
     * using the 'utf8mb4' character set and disabling emulated prepared
     * statements. An answer posted at http://stackoverflow.com/a/12118602
     * shows an example of this attack.
     *
     * @param  NetworkEndpoint  $hostname  A `NetworkEndpoint` for the database.
     * @param  HiddenString     $username  The database login username.
     * @param  HiddenString     $password  The database login password.
     */
    public function __construct(NetworkEndpoint $endpoint,
        HiddenString $username, HiddenString $password) {
      // Create a new PDO instance with the filtered parameters
      try { parent::__construct('mysql:charset=utf8mb4;host=['.
          $endpoint->getAddress().'];port='.$endpoint->getPort(),
          $username, $password, [
        // Ensure that emulated prepared statements are disabled for security
        \PDO::ATTR_EMULATE_PREPARES   => false,
        // Fetch associative array result sets by default
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        // Rethrow with only the error text to prevent showing PDO arguments
      ]); } catch (\Exception $e) { throw new \Exception($e->getMessage()); }
    }
  }
