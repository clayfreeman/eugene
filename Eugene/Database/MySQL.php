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

  // Create a locally-scoped alias for the `HiddenString` class
  use \Eugene\Utilities\HiddenString;

  // Create a locally-scoped alias for the `InvalidHostnameException` class
  use \Eugene\Exceptions\InvalidHostnameException;

  /**
   * //
   */
  final class MySQL {
    protected $connection = null;

    /**
     * [__construct description]
     *
     * @param string       $hostname [description]
     * @param HiddenString $username [description]
     * @param HiddenString $password [description]
     * @param integer      $port     [description]
     */
    public function __construct(string $hostname, HiddenString $username,
        HiddenString $password, int $port = 3306) {
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
      // Create a new PDO instance with the filtered parameters
      try { $this->connection = new \PDO('mysql:charset=utf8mb4;host=['.
          $address.'];port='.$port, $username, $password, [
        // Ensure that emulated prepared statements are disabled for security
        \PDO::ATTR_EMULATE_PREPARES   => false,
        // Fetch associative array result sets by default
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        // Throw again with only the error text to prevent showing PDO arguments
      ]); } catch (\Exception $e) { throw new \Exception($e->getMessage()); }
    }

    /**
     * Inserts the provided data into the requested table name.
     *
     * @param  string $table  The name of the table to insert data.
     * @param  array  $fields An array of field (column) names.
     * @param  array  $values An array of values corresponding to the provided
     *                        field names.
     *
     * @return [type]         [description]
     */
    public function insert(string $table, array $fields, array $values) {
      // Sanitize and prepare the table and fields arguments
      $table     = $this->sanitizeName(new HiddenString($table));
      // Generate an array containing a unique ID associated with each newly
      // sanitized field name
      $newFields = []; foreach ($fields as $field) {
        while (array_key_exists($id = uniqid(':'), $newFields));
        $newFields[$id] = $this->sanitizeName(new HiddenString($field)); }
      // Use the new array of fields to generate a field string
      $fields    = implode(', ', $newFields);
      // Generate a value placeholder string from the unique field IDs
      $values    = array_values($values);
      $valueKeys = array_keys($newFields);
      $valueStr  = implode(', ', $valueKeys);
      // Prepare a generic statement using the provided table and fields
      $SQLstr    = "INSERT INTO {$table} ({$fields}) VALUES ({$valueStr})";
      $statement = $this->connection->prepare($SQLstr);
      // Execute the prepared statement with the provided values
      $statement->execute(array_combine($valueKeys, $values));
    }

    /**
     * [sanitizeName description]
     *
     * @param  HiddenString $name   [description]
     * @param  [type]       $second [description]
     *
     * @return [type]               [description]
     */
    protected function sanitizeName(HiddenString $name,
        HiddenString $second = null): HiddenString {
      // Check if the name contains any disallowed characters
      if (!preg_match('/^[0-9a-z$_]+$/i', $name.$second))
        throw new \Exception('Invalid characters in the provided name');
      // Ensure that the name is encapsulated in backtick characters
      return new HiddenString($name.(isset($second) ? '.'.$second : null));
    }

    public function use($database) {
      // Sanitize the provided database name
      $database = $this->sanitizeName(new HiddenString($database));
      // Switch to the requested database
      $SQLstr = "USE {$database}";
      $this->connection->exec($SQLstr);
    }
  }
