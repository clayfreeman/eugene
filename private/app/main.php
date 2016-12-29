<?php
  /**
   * Secret entrypoint to the application.
   *
   * @copyright  Copyright 2016 Clay Freeman. All rights reserved.
   * @license    GNU Lesser General Public License v3 (LGPL-3.0).
   */

  // Enable strict types for this file
  declare(strict_types = 1);

  // End script execution if the private root is not defined
  if (!defined('__PRIVATEROOT__')) die();

  // Create a locally-scoped alias for all required classes
  use \Eugene\Database\MySQL;
  use \Eugene\Runtime\Registry;
  use \Eugene\Utilities\{Address, HiddenString, NetworkEndpoint};

  // Attempt to access the protected database configuration category
  Registry::getInstance()->get('database');

  // Create the database key of the registry to hold our database instance
  $database = Registry::getInstance()->create('database', new MySQL(
    new NetworkEndpoint(new Address('127.0.0.1'), 3306),
    new HiddenString('webdev'),
    new HiddenString(''),
    new HiddenString('webdev_test')));

  // Attempt to pull some sample data from the database
  $result = $database->query('SELECT * FROM test')->fetchAll();
  echo print_r($result, true);
