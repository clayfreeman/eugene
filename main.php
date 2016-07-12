<?php
  /**
   * Secret entrypoint to the application.
   *
   * @copyright Copyright 2016 Clay Freeman. All rights reserved.
   * @license   GNU General Public License v3 (GPL-3.0).
   */

  use \Pubkey2\Exceptions\{NameUnavailableError, ReadLockError};
  use \Pubkey2\Runtime\Registry;

  // Get a reference to the singleton instance of `Registry`
  $r = Registry::getInstance();
  // Attempt to create an entry named 'test'
  try { $r->create('test', 'Registry class sample test.'); }
  catch (NameUnavailableError $e) {}
  // Attempt to retrieve the value held by the entry named 'test'
  try { echo $r->get('test')."\n"; }
  catch (NameUnavailableError $e) {}
  catch (ReadLockError $e) {}
