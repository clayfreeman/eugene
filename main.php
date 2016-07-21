<?php
  /**
   * Secret entrypoint to the application.
   *
   * @copyright Copyright 2016 Clay Freeman. All rights reserved.
   * @license   GNU General Public License v3 (GPL-3.0).
   */

  use \Pubkey2\Exceptions\{DNSResolutionError, InvalidHostnameException};
  use \Pubkey2\Database\MySQL;

  $mysql = new MySQL('localhost', 'username', 'password', 3306);
