<?php
  /**
   * This file prepares a Standard PHP Library class autoloader function using
   * the `\Eugene\Utilities\Path` class for platform-independent support.
   *
   * @copyright  Copyright 2016 Clay Freeman. All rights reserved.
   * @license    GNU Lesser General Public License v3 (LGPL-3.0).
   */

  // Enable strict types for this file
  declare(strict_types = 1);

  namespace Eugene\Utilities;

  // End script execution if the private root is not defined
  if (!defined('__PRIVATEROOT__')) die();

  // Provide manual support for loading external dependencies
  { $_class = realpath(implode(__DS__, [__CLASSPATH__, 'Eugene', 'Utilities',
    'Path.php'])); require_once($_class); }
  { $_class = realpath(implode(__DS__, [__CLASSPATH__, 'Eugene',
    'DesignPatterns', 'Singleton.php'])); require_once($_class); }
  { $_class = realpath(implode(__DS__, [__CLASSPATH__, 'Eugene',
    'DesignPatterns', 'HiddenMembers.php'])); require_once($_class); }
  { $_class = realpath(implode(__DS__, [__CLASSPATH__, 'Eugene', 'Utilities',
    'Security.php'])); require_once($_class); }

  // Create a locally-scoped alias for the `Path` class and its exceptions
  use \Eugene\{Exceptions\PathResolutionError, Utilities\Path};

  // Create a locally-scoped alias for the `Singleton` class
  use \Eugene\DesignPatterns\Singleton;

  /**
   * Composer-inspired, fail-safe PSR-0/4 compliant autoloader class.
   *
   * @see  http://www.php-fig.org/psr/psr-0/  For more information about the
   *                                          PSR-0 autoloading standard.
   *
   * @see  http://www.php-fig.org/psr/psr-4/  For more information about the
   *                                          PSR-4 autoloading standard.
   */
  final class Autoload extends Singleton {
    // Safely hide members of this class (`Singleton` implies the use of
    // `PreventSerialize` to complete this feature)
    use \Eugene\DesignPatterns\HiddenMembers;

    /**
     * Disallow unlinks via `getInstance(true)`.
     *
     * @var  bool
     */
    protected $allowUnlink = false;

    /**
     * An array containing all PSR-0 autoloader entries.
     *
     * @var  array
     */
    protected $PSR0 = [];

    /**
     * An array containing all PSR-4 autoloader entries.
     *
     * @var  array
     */
    protected $PSR4 = [];

    /**
     * Register our autoloader using the SPL function `spl_autoload_register`.
     */
    protected function __construct() {
      spl_autoload_register([$this, 'run'], true, true);
      $this->addPSR4(__CLASSPATH__);
    }

    /**
     * Generic method to add an autoloader entry to the provided array.
     *
     * @see    https://getcomposer.org/doc/04-schema.md#autoload
     *         For more information regarding the syntax used for the namespace
     *         filter parameter of this method.
     *
     * @param  array   $type        The array to hold the autoloader entry.
     * @param  string  $searchPath  A filesystem base directory for includes.
     * @param  string  $filter      A namespace filter for this base directory.
     */
    protected function addItem(array& $type, string $searchPath,
        ?string $filter): void {
      // Initialize the provided filter for this PSR
      $filter        = $this->getFilterExpression($filter ?? '');
      $type[$filter] = $type[$filter] ?? [];
      // Check that this search path doesn't already exist for this filter
      if (!in_array($searchPath, $type[$filter]))
        // Add this search path to the array of search paths for this filter
        $type[$filter][] = $searchPath;
    }

    /**
     * Calls `addItem` using the PSR-0 autoloader array.
     *
     * @see    addItem()            For more information regarding how
     *                              autoloader entries are processed.
     *
     * @param  string  $searchPath  A filesystem base directory for includes.
     * @param  string  $filter      A namespace filter for this base directory.
     */
    public function addPSR0(string $searchPath, ?string $filter = null): void {
      // Add the provided search path and filter combination to its PSR array
      $this->addItem($this->PSR0, $searchPath, $filter);
    }

    /**
     * Calls `addItem` using the PSR-4 autoloader array.
     *
     * @see    addItem()            For more information regarding how
     *                              autoloader entries are processed.
     *
     * @param  string  $searchPath  A filesystem base directory for includes.
     * @param  string  $filter      A namespace filter for this base directory.
     */
    public function addPSR4(string $searchPath, ?string $filter = null): void {
      // Add the provided search path and filter combination to its PSR array
      $this->addItem($this->PSR4, $searchPath, $filter.'\\');
    }

    /**
     * Canonicalizes the provided class name into a uniform format.
     *
     * This method removes duplicate namespace separators and leading namespace
     * separators from the input string.
     *
     * @param   string  $class  Fully-qualified class name.
     *
     * @return  string          Canonical fully-qualified class name.
     */
    protected function canonicalizeClass(?string $class): string {
      // Remove any existing leading namespace separator or non-identifier
      // characters from the provided string
      $class = preg_replace('/([^a-z0-9_\\\\]+)/i', '',  ltrim($class, '\\'));
      // Remove duplicate namespace separators from the provided string
      return   preg_replace('/(\\\\{2,})/',         '\\',      $class);
    }

    /**
     * Builds an array of files to include from each PSR-0/4 autoloader entry.
     *
     * PSR-0 class names with underscores are converted to directory separators.
     *
     * PSR-4 namespace filters are treated as a prefix that are not included in
     * the file include paths.
     *
     * @param   string  $class  The class name used to generate file paths.
     *
     * @return  array           All file include paths based on the autoloader
     *                          entries and class name input.
     */
    protected function getFileIncludePaths(string $class) {
      // Build a list of search paths based on the array of PSR0 autoloaders
      $input = array_merge([], ...array_map(function($value) use ($class) {
        // Determine the relative file path based on the requested class
        $delta = explode('\\', $class); $terminalClass = array_pop($delta);
        $delta = array_merge($delta, explode('_', $terminalClass.'.php'));
        // Attempt to create a file path for each search path in the entry
        return array_filter(array_map(function($value) use ($delta) {
          try    { return Path::make($value, ...$delta); }
          catch (PathResolutionError $e) { return false; }
        }, $value));
      // Filter the search paths to try by matching the PSR0 autoloader to
      // the requested class name
      }, array_values(array_filter($this->PSR0, function($key) use ($class) {
        return preg_match($key, $class);
      }, ARRAY_FILTER_USE_KEY))));
      // Iterate over each PSR4 autoloader entry to check for applicability
      foreach ($this->PSR4 as $filter => $value)
        // Check if the filter matches the requested class, extract non-match
        if (preg_match($filter, $class, $matches)) {
          // Determine the relative file path based on the requested class
          $delta = explode('\\', $matches[1].'.php');
          // Add each resulting search path to the input array
          $input = array_merge($input, array_filter(array_map(
            // Attempt to create a file path for each search path in the entry
            function($value) use ($delta) {
              try    { return Path::make($value, ...$delta); }
              catch (PathResolutionError $e) { return false; }
            }, $value)));
          // Ensure that the resulting array is unique (to prevent extra work)
        } return array_filter(array_unique($input), 'file_exists');
    }

    /**
     * Converts a namespace filter/prefix into a regular expression to be used
     * in matching input class names.
     *
     * This method can be used for a fully-qualified class name, although it is
     * generally meant to be used for namespace filters/prefixes.
     *
     * @param   string  $class  Namespace filter/prefix or fully-qualified
     *                          class name.
     *
     * @return  string          Filter matching regular expression.
     */
    protected function getFilterExpression(?string $class): string {
      // Build a matching expression using the provided class
      return '/^'.preg_quote($this->canonicalizeClass($class), '/').'(.*)/';
    }

    /**
     * Imports all of Composer's installed package PSR-0/4 autoloaders.
     *
     * This file references `vendor/composer/installed.json` to determine which
     * autoloader definitions should be added.
     */
    public function importComposer(): void {
      // Initialize a static member to hold state
      static $runOnce = true;
      // Check that we have not ran already then mark as ran
      if ($runOnce) { $runOnce = false;
        // Determine the path to the `installed.json` file
        $installed = Path::make(__VENDORROOT__, 'composer', 'installed.json');
        // Load Composer's `installed.json` file to import autoloaders
        if (is_readable($installed)) {
          // Attempt to parse the file as JSON
          $installed = @json_decode(@file_get_contents($installed), true);
          // Ensure that the resulting content is an array
          if (is_array($installed)) {
            // Iterate over each package and import its autoloader definition
            foreach ($installed as $package) {
              // Fetch required keys from this package
              $name = $package['name']; $autoload = $package['autoload'];
              // Determine the base directory for this package
              $base = Path::make(__VENDORROOT__, ...explode('/', $name));
              // Add each PSR-0 autoloader definition
              foreach ($autoload['psr-0'] ?? [] as $filter => $dir)
                $this->addPSR0(Path::make($base, trim($dir, __DS__)), $filter);
              // Add each PSR-4 autoloader definition
              foreach ($autoload['psr-4'] ?? [] as $filter => $dir)
                $this->addPSR4(Path::make($base, trim($dir, __DS__)), $filter);
            }
          }
        }
      }
    }

    /**
     * Attempts to autoload the provided fully-qualified class name.
     *
     * This method uses the PSR-0/4 autoloader entries stored internally to
     * generate file include paths.
     *
     * Files that are considered mutable (i.e. writable or owned by the current
     * process user) or dangerous (i.e. containing disallowed tokens
     * `require_once`, `include`, etc) will refuse to load.
     *
     * @param  string  $class  Fully-qualified class name to include.
     */
    public function run(?string $class): void {
      $security = Security::getInstance();
      // Ensure that the requested class name is not empty
      if (strlen($class = $this->canonicalizeClass($class)) > 0) {
        // Fetch an array of all file paths to attempt to include
        $files = array_map(function($file) {
          // Require each resulting file path (should be fail-safe)
          require_once($file);
        }, array_filter($this->getFileIncludePaths($class),
        function($path) use ($security) {
          $dangerous = $security->fileIsDangerous($path);
          // Trigger a warning when a dangerous file is encountered
          if ($dangerous) trigger_error('Refusing to load insecure '.
            'file at '.escapeshellarg($path), E_USER_WARNING);
          return !$dangerous;
        }));
      }
    }
  }
