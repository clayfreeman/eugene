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

  // Attempt to run the router
  \Eugene\Runtime\Router::getInstance()->run();

  use \Eugene\Utilities\HiddenString;
  use \ParagonIE\Halite\KeyFactory;
  $security = \Eugene\Utilities\Security::getInstance();
  $hash     = $security->passwordHash(new HiddenString('test'));
  echo "\n<br />\nHash\n<br />\n".htmlentities(var_export($hash, true));
  if ($security->passwordRehash(new HiddenString('test'), $hash)) {
    echo "\n<br />\nRehash 1\n<br />\n".htmlentities(var_export($hash, true));
  } else if ($security->passwordRehash(new HiddenString('test'), $hash, KeyFactory::MODERATE)) {
    echo "\n<br />\nRehash 2\n<br />\n".htmlentities(var_export($hash, true));
  } else {
    echo "\n<br />\nHash\n<br />\n".htmlentities(var_export($hash, true));
  }
