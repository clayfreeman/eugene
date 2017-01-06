<?php
  /**
   * This file is responsible for declaring a URL router.
   *
   * https://regex101.com/r/SYEh3p/10
   *
   * @copyright  Copyright 2016 Clay Freeman. All rights reserved.
   * @license    GNU Lesser General Public License v3 (LGPL-3.0).
   */

  // Enable strict types for this file
  declare(strict_types = 1);

  namespace Eugene\Runtime;

  // End script execution if the private root is not defined
  if (!defined('__PRIVATEROOT__')) die();

  // Create locally-scoped aliases for the `ConfigDelegate` and `Singleton` classes
  use \Eugene\DesignPatterns\{ConfigDelegate, Singleton};

  // Create locally-scoped aliases for the `Registry` and `HiddenString` classes
  use \Eugene\{Runtime\Registry, Utilities\HiddenString};

  /**
   * TODO
   */
  final class Router extends Singleton implements ConfigDelegate {
    /**
     * Disallow unlinks via `getInstance(true)`.
     *
     * @var  bool
     */
    protected $allowUnlink = false;

    /**
     * An empty constructor to satisfy the parent's abstract method
     * prototype definition.
     */
    protected function __construct() {}

    /**
     * TODO
     *
     * @param  string  $url     [description]
     * @param  string  $target  [description]
     */
    protected function parse(?string $url, string $target): void {
      $types   = [ // Define our type matching expressions
        'int'    => '\\d+',
        'float'  => '\\.|\\d+|\\d+\\.|\\.\\d+|\\d+\\.\\d+',
        'string' => '[^\\/]+'
      ]; $expr = // Define our regular expression to parse URLs
        "/# Only match if surrounded by start of string
        (?:^
          # Determine if the token is optional and match the colon marker
        (?P<optional>\\?)?:
          # Determine if a type should be enforced for the token
        (?:<(?P<type>string|int|float)>)?
          # Match the provided name for the token
            (?P<name>[a-z_][a-z0-9_]*)  ?
          # Only match if surrounded by end of string
        $)/ixm";
      // Split the URL into path components (removing extraneous `null`s) and
      // attempt to parse the components using our expression. After parsing,
      // reassemble the URL into a path matching regular expression
      $route = '/^\\/'.implode('\\/', array_filter(array_map(
        function($input) use ($types, $expr) {
          if (preg_match($expr, $input, $matches)) {
            if ($name = $matches['name'] ?? false) {
              // Determine the specific type matching expression
              $type = $types[$matches['type'] ?? 'string'] ?? $types['string'];
              // Determine the name matching expression for this group
              $name = ($name ? '?P<'.$name.'>' : null);
              // Determine whether this token is optional
              $optional = ($matches['optional'] ?? null);
              // Return the assembled token matching expression
              return '('.$name.$type.')'.$optional;
              // Return `false` if no name was provided
            } return false;
          } else return preg_quote($input, '/');
        }, array_filter(explode('/', $url ?? ''), function($input) {
          return strlen($input) > 0;
        })))).'\\/?$/'; echo var_export($route, true)."\n";
    }

    /**
     * This method is used to receive read-locked configuration category
     * credentials for routing.
     *
     * @param  string        $category  The category the credential belongs to.
     * @param  HiddenString  $password  The password for this category.
     */
    public function receiveCredential(string $category,
        HiddenString $password): void {
      // Fetch the read-locked category from the `Registry` class
      $results = Registry::getInstance()->get($category, $password);
      // Iterate over each result for parsing
      foreach ($results as $result)
        // Use default `null` values for URL and target
        $this->parse($result['url'] ?? null, $result['target'] ?? null);
    }
  }
