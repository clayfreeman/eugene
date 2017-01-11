<?php
  /**
   * An example route target delegate for the `Router` class.
   *
   * @copyright  Copyright 2016 Clay Freeman. All rights reserved.
   * @license    GNU Lesser General Public License v3 (LGPL-3.0).
   */

  // Enable strict types for this file
  declare(strict_types = 1);

  namespace Examples\Router;

  // End script execution if the private root is not defined
  if (!defined('__PRIVATEROOT__')) die();

  // Create a locally-scoped alias for the `RouterDelegate` class
  use \Eugene\DesignPatterns\RouterDelegate;

  /**
   * An example route target delegate for the `Router` class.
   */
  final class RouteTarget implements RouterDelegate {
    /**
     * An example route target endpoint for the `Router` class.
     *
     * This route expects the 'name' token to be available.
     *
     * @param  array  $tokens  All tokens parsed from the request URL.
     */
    public static function receiveRequest(array $tokens): void {
      // Show a greeting based on the provided tokens
      echo 'Hello, '.htmlentities($tokens['name'] ?? 'World')."!\n";
    }
  }
