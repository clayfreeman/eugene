<?php
  /**
   * This file provides an `Exception` subclass to describe a path resolution
   * error when using the `\Pubkey2\Utilities\Path` class.
   *
   * @copyright Copyright 2016 Clay Freeman. All rights reserved.
   * @license   GNU General Public License v3 (GPL-3.0).
   */

  namespace Pubkey2\Exceptions;

  // End script execution if the private root is not defined
  if (!defined('__PRIVATEROOT__')) die();

  /**
   * An `Exception` subclass responsible for conveying an unexpected failure
   * during absolute path resolution of a platform-specific dirty path.
   */
  class PathResolutionError extends \Exception {}
