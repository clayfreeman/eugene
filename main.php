<?php
  /**
   * Secret entrypoint to the application.
   *
   * @copyright Copyright 2016 Clay Freeman. All rights reserved.
   * @license   GNU General Public License v3 (GPL-3.0).
   */

  $r = \Pubkey2\Runtime\Registry::getInstance();
  $r->create('test', 'Registry class sample test.');
  echo $r->get('test')."\n";
