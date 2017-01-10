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

  // Create locally-scoped aliases for `ConfigDelegate` and `Singleton`
  use \Eugene\DesignPatterns\{ConfigDelegate, Singleton};

  // Create locally-scoped aliases for `Registry` and `HiddenString`
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
    protected function parse(?string $url, ?string $target): void {
      // Trigger an error if the provided target doesn't exist or won't work
      if (strlen($target ?? '') === 0 || !class_exists($target) ||
          !is_subclass_of($target, '\\Eugene\\DesignPatterns\\RouteDelegate')) {
        trigger_error('This target is not applicable to receive routed '.
          'requests; ignoring route', E_USER_WARNING); // return; // TODO
      } $expr = // Define our regular expression to parse URLs
        '/# Only match if surrounded by start of string
        (?:^
          # Determine if the token is optional and match the colon marker
        (?P<optional>\\?)?:
          # Determine if a type should be enforced for the token
        (?:<(?P<type>[a-z_][a-z0-9_]*)>)?
          # Match the provided name for the token
            (?P<name>[a-z_][a-z0-9_]*)  ?
          # Only match if surrounded by end of string
        $)/ixm';
      $types   = [ // Define our type matching expressions
        'int'    => '\\d+',
        'float'  => '\\.|\\d+|\\d+\\.|\\.\\d+|\\d+\\.\\d+',
        'string' => '[^\\/]+'
      ]; // Join each path component by a terminal character to match as a path
         // separator and prepend the matching expression prefix
      $this->routes['/^'.implode(null, array_filter(array_map(
        // Replace each item in the array with its own matching expression
        function($input) use ($types, $expr) {
          // Attempt to parse the provided path component using our expression
          if (preg_match($expr, $input, $matches)) {
            // Remove empty entries in the array of matches
            $matches = array_filter($matches, function($input) {
              return isset($input) && strlen($input) > 0;
            }); // Check if a name was provided before continuing
            if ($name = $matches['name'] ?? false) {
              // Determine whether this token is optional
              $optional = ($matches['optional'] ?? null);
              // Determine the specific type matching expression
              $type = $types[$matches['type'] ?? 'string'] ?? false;
              // Ensure that a valid type was provided before continuing
              if ($type !== false) {
                // Determine the name matching expression for this group
                $name = ($name ? '?P<'.$name.'>' : null);
                // Return the assembled token matching expression
                return '(\\/('.$name.$type.'))'.$optional;
              } else trigger_error('Invalid type provided for this route '.
                'configuration; ignoring token', E_USER_WARNING);
            } else trigger_error('Name not provided for this route '.
              'configuration; ignoring token', E_USER_WARNING);
            // Return `false` if no name was provided
            return false;
            // If this component cannot be parsed, treat it as a terminal
            // sequence of characters to match
          } else return '\\/'.preg_quote($input, '/');
          // Remove all empty or `null` path components from the URL
        }, array_filter(explode('/', $url ?? ''), function($input) {
          // Check that the provided input is non-`null` and non-empty
          return isset($input) && strlen($input) > 0;
          // Append the matching expression suffix
        })))).'\\/?$/'] = $target;
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

    /**
     * TODO
     */
    public function run(): void {
      // Fetch the desired URL from the client's request
      $url = $_SERVER['REQUEST_URI'];
      echo "<pre>\n".htmlentities(var_export($this->routes, true))."\n</pre>";
      // Iterate over each configured route to determine eligibility
      foreach ($this->routes as $route => $destination) {
        // Attempt to match the desired URL to this route
        if (preg_match($route, $url, $matches)) {
          // Remove all numeric keys from the array of matches
          $matches = array_filter($matches, function($input) {
            return is_string($input) && strlen($input) > 0;
          }, ARRAY_FILTER_USE_KEY);
          // Dump the matches for this route
          echo "<pre>\n".htmlentities(var_export($matches, true))."\n</pre>";
          // Stop trying additional routes on our first successful match
          break;
        }
      }
    }
  }
