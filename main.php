<?php
  /**
   * Private entrypoint to the application.
   *
   * @copyright Copyright 2016 Clay Freeman. All rights reserved.
   * @license   GNU General Public License v3 (GPL-3.0).
   */

  use \Pubkey2\Runtime\Registry;

  $r = Registry::getInstance();
  echo var_export(get_declared_classes(), true)."\n";
