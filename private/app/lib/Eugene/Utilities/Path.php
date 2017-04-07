<?php
  /**
   * This file provides a `Path` class responsible for generating
   * platform-specific filesystem paths for use with internal functionaltiy.
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
  { $_class = implode(__DS__, [__CLASSPATH__, 'Eugene', 'Exceptions',
    'PathResolutionError.php']); require_once($_class); }
  use \Eugene\Exceptions\PathResolutionError;

  /**
   * Helper class to aid in making platform-specific filesystem paths via
   * `__DS__` implosion of path components.
   */
  final class Path {
    /**
     * Prevent construction of this class to force a static-only interface.
     */
    protected function __construct() {}

    /**
     * Creates a platform-specific path to a filesystem directory entry from an
     * array of path components.
     *
     * This method will call `implode()` on the provided array using the
     * platform-specific directory separator as the 'glue'. After the dirty path
     * is constructed, it is passed through `realpath()` to determine the
     * absolute path to the requested target.
     *
     * This method is tolerant of inexistent terminating components (i.e. files
     * that don't exist yet, but their parent directory does).
     *
     * @param   string  $components       A variadic argument list of path
     *                                    components where directory separators
     *                                    are needed.
     *
     * @throws  InvalidArgumentException  If any provided path component
     *                                    contains the directory separator
     *                                    character.
     * @throws  PathResolutionError       Upon failure when attempting to
     *                                    resolve the absolute path to the
     *                                    requested target.
     *
     * @return  string                    A string representing the `realpath()`
     *                                    result using the appropriate directory
     *                                    separators.
     */
    public static function make(?string ...$components): string {
      // Determine whether the root of the filesystem should be prepended
      $root = explode(__DS__, __DIR__)[0].__DS__;
      $root = reset($components) === null ||
              reset($components) === false ? $root : null;
      // Remove all `null` path components to avoid confusion
      $components = array_filter($components, function($input) {
        return $input !== null; });
      // If there were no provided path components, assume root of filesystem
      if (count($components) === 0) return $root; // TODO: realpath? --v
      // If only one path component was provided, return its value
      if (count($components) === 1) return $root.array_shift($components);
      // Only try to determine `realpath()` if non-phar path
      $path     = $root.implode(__DS__, $components);
      $realpath = $path;
      if (substr($components[0], 0, 7) != 'phar://') {
        // Fetch the last component to isolate the target's parent
        $lastComponent = array_pop($components);
        // Ensure that the target's parent can be resolved via `realpath()`
        $path     = $root.implode(__DS__, $components);
        $fail     = realpath($path) === false;
        $realpath = realpath($path   .= __DS__.$lastComponent);
        // If the parent cannot be resoved, throw an exception
        if ($fail === true) throw new PathResolutionError('Failed to determine '.
          'the absolute path to '.escapeshellarg($path).': the requested target '.
          'presumably does not exist');
        // If the target can be resolved using `realpath()` as well, prefer it;
        // otherwise assume the file doesn't exist (yet)
        $realpath = ($realpath !== false ? $realpath : $path);
      } return $realpath;
    }

    /**
     * Normalizes the provided path by resolving '.' and '..' path components.
     *
     * If a path is provided that starts with a leading forward (or backward)
     * slash, the path will be considered absolute and will remain as such.
     *
     * For all relative paths which consist of more '..' components than regular
     * components, the result will have a leading trail of '..' components to
     * make up the difference.
     *
     * All paths produced by this method will not contain a trailing forward
     * slash, except in the case of the root directory '/'.
     *
     * If a scheme is provided, the result of this method will be a URL encoded
     * RFC8089 URI with the provided scheme identifier. This method will
     * generate relative URIs which are not compliant with RFC8089 if a relative
     * path is given as input.
     *
     * @param   string  $path    The path that should be normalized.
     * @param   string  $scheme  If a scheme is provided, an RFC8089 URI string
     *                           will be produced as opposed to a file path.
     *
     * @return  string           The resulting normalized path.
     */
    public static function normalize(string $path,
        ?string $scheme = null): string {
      // Split the original path into an array of components (ignore '.')
      $original    = array_filter(preg_split('/[\\/\\\\]/', $path),
        function($item) { return  $item  !== '.'; }); $result = [];
      // Allow only the scheme name to be specified for simplicity
      if (($scheme =   rtrim($scheme,       '://')) !== '')
        $scheme   .= (strlen($scheme) > 0 ? '://' : null);
      // Determine if this is an absolute or relative path
      $prefix      = ($original[0] ?? null) === '' ? '/' : null;
      // Iterate over each original component to build our canonical path
      foreach ($original as $component) {
        // If this is supposed to be a URI path, it should be URL encoded
        if ($scheme !== '') $component = rawurlencode(rawurldecode($component));
        // Check if this component wants the previous directory removed
        if ($component === '..') {
          // If this is a relative path and there are no more components in the
          // result, then append this component to the prefix
          if (count($result) === 0 && $prefix !== '/')
            $prefix .= $component.'/';
          // Remove the last component from the result to get the parent
          array_pop($result);
          // In the default case, simply add the current component to the array
        } else $result[] = $component;
        // Assemble the final path with the resulting prefix
      } $result = rtrim($prefix.implode('/', array_filter($result)), '/');
      // If the string was trimmed to zero length, assume the root path
      return $scheme.(strlen($result) === 0 ? '/' : $result);
    }
  }
