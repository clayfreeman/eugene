<?php
  /**
   * This file provides the `NetworkEndpoint` class responsible for holding an
   * `Address` and port for connecting to a remote endpoint.
   *
   * @copyright  Copyright 2016 Clay Freeman. All rights reserved.
   * @license    GNU General Public License v3 (GPL-3.0).
   */

  // Enable strict types for this file
  declare(strict_types = 1);

  namespace Eugene\Utilities;

  // End script execution if the private root is not defined
  if (!defined('__PRIVATEROOT__')) die();

  // Create a locally-scoped alias for the `Address` class
  use \Eugene\Utilities\Address;

  /**
   * Helper class to aid in the representation of a remote endpoint.
   */
  class NetworkEndpoint {
    /**
     * Storage location for an `Address` instance.
     *
     * @var  Address
     */
    protected $address = null;

    /**
     * Storage location for a port number.
     *
     * @var  integer
     */
    protected $port    = null;

    /**
     * Constructs an instance of `NetworkEndpoint` by performing sanity checks
     * on the provided `Address` instance and port number.
     *
     * @param   Address  $addr            An `Address` instance holding at least
     *                                    one IP address for use in network
     *                                    connections.
     * @param   int      $port            A port number for use in network
     *                                    connections.
     *
     * @throws  InvalidPortException      If provided an out of bounds port.
     * @throws  InvalidHostnameException  If the provided `Address` instance
     *                                    contains no IP addresses.
     */
    public function __construct(Address $addr, int $port) {
      // Validate the provided port number
      if ($port < 1 || $port > 65535) throw new InvalidPortException('Could '.
        'not use the specified port: '.$port);
      // Ensure that there is at least one address available for use
      if (count($addr->getAddresses(true)) === 0)
        throw new InvalidHostnameException('Could not use the provided '.
          'hostname for network connections: '.escapeshellarg(
          $addr->getHostname()).' -> (0 total IP addresses)');
      // Store the provided arguments for later use
      $this->address = clone $addr; $this->port = $port;
    }

    /**
     * Fetches a random IP address from the internal `Address` instance.
     *
     * @return  string  The random IP address.
     */
    public function getAddress(): string {
      // Fetch a randomized array of all available addresses
      $addresses = $this->address->getAddresses(true); shuffle($addresses);
      // Return the first address in the array
      return array_shift($addresses);
    }

    /**
     * Fetches the originally provided internal port number.
     *
     * @return  int  The internal port.
     */
    public function getPort(): int {
      return $this->port;
    }
  }
