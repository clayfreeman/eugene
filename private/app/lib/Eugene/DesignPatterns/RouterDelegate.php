<?php
  /**
   * This file provides a delegate interface for the `Router` class.
   *
   * @copyright  Copyright 2016 Clay Freeman. All rights reserved.
   * @license    GNU Lesser General Public License v3 (LGPL-3.0).
   */

  // Enable strict types for this file
  declare(strict_types = 1);

  namespace Eugene\DesignPatterns;

  // End script execution if the private root is not defined
  if (!defined('__PRIVATEROOT__')) die();

  /**
   * Interface for ensuring the implementation of a request receiver for the
   * `Router` class.
   */
  interface RouterDelegate {
    /**
     * This method is required for user request delivery.
     *
     * @param  array  $tokens  All tokens parsed from the request URL.
     */
    public static function receiveRequest(\Twig_Environment $twig,
      array $tokens): void;
  }
