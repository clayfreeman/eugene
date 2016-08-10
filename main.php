<?php
  /**
   * Secret entrypoint to the application.
   *
   * @copyright Copyright 2016 Clay Freeman. All rights reserved.
   * @license   GNU General Public License v3 (GPL-3.0).
   */

  use \Eugene\Exceptions\{DNSResolutionError, InvalidHostnameException};
  use \Eugene\Database\MySQL;
  use \Eugene\Utilities\HiddenString;

  $mysql = new MySQL(new HiddenString('localhost'),
                     new HiddenString('webdev'),
                     new HiddenString(''));
  $mysql->use('webdev_test');
  $mysql->insert('bin_groups',
    ['default_grain_id', 'name'],
    [1, 'Hello, World!']);
