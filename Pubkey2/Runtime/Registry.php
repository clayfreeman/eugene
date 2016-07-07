<?php
  /**
   * This file provides a `Registry` class responsible for providing an outlet
   * for storing runtime information.
   *
   * @copyright Copyright 2016 Clay Freeman. All rights reserved.
   * @license   GNU General Public License v3 (GPL-3.0).
   */

  namespace Pubkey2\Runtime;

  // End script execution if the private root is not defined
  if (!defined('__PRIVATEROOT__')) die();

  // Create a locally-scoped alias for the `Singleton` class and its exceptions
  use \Pubkey2\{Exceptions\InstantiationFailureError, DesignPatterns\Singleton};

  /**
   * TODO
   */
  final class Registry extends Singleton {
    /**
     * [__construct description]
     */
    protected function __construct() {
      //
    }
  }
