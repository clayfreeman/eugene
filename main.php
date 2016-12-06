<?php
  /**
   * Secret entrypoint to the application.
   *
   * @copyright  Copyright 2016 Clay Freeman. All rights reserved.
   * @license    GNU General Public License v3 (GPL-3.0).
   */

  use \Eugene\Database\MySQL;
  use \Eugene\Exceptions\{DNSResolutionError, InvalidHostnameException};
  use \Eugene\Utilities\{Address, HiddenString, NetworkEndpoint};

  $mysql  = new MySQL(new NetworkEndpoint(new Address('127.0.0.1'), 3306),
                      new HiddenString('webdev'),
                      new HiddenString(''));
  $result = $mysql->query('SELECT * FROM webdev_test.test')->fetchAll();
  echo var_export($result, true)."\n";
