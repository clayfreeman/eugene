<?php
  /**
   * This file provides the `Address` class responsible for automatically
   * attempting to resolve hostnames into IP addresses and fetching PTR results
   * for IP addresses.
   *
   * @copyright Copyright 2016 Clay Freeman. All rights reserved.
   * @license   GNU General Public License v3 (GPL-3.0).
   */

  namespace Eugene\Utilities;

  // End script execution if the private root is not defined
  if (!defined('__PRIVATEROOT__')) die();

  // Create a locally-scoped alias for the `DNSResolutionError` class
  use \Eugene\Exceptions\DNSResolutionError;

  /**
   * Helper class to aid in managing IP addresses and hostnames.
   */
  class Address {
    /**
     * Storage location for IPv4/IPv6 addresses.
     *
     * @var array
     */
    protected $addresses = ['A' => [], 'AAAA' => []];

    /**
     * Storage location for a hostname.
     *
     * @var string
     */
    protected $hostname  = null;

    /**
     * Responsible for preparing an `Address` instance with the provided
     * IP Address/Hostname as input.
     *
     * @see    setupIPAddress()   For more information regarding how the
     *                            instance is prepared when using an IP address.
     *
     * @see    setupHostname()    For more information regarding how the
     *                            instance is prepared when using a hostname.
     *
     * @param  string $address    An IP Address or Hostname as input.
     *
     * @throws DNSResolutionError Upon failure when attempting to resolve the
     *                            provided hostname's DNS records.
     */
    public function __construct(string $address) {
      // Attempt to parse the provided parameter as an IP address
      if (filter_var($address, FILTER_VALIDATE_IP,
          FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) !== false)
        $this->setupIPAddress($address);
      // Fallback to parsing as a hostname with a chance of failure
      else $this->setupHostname($address);
    }

    /**
     * Fetches the internal array of addresses.
     *
     * @param  bool  $flat  Whether the resulting array should be flat.
     *
     * @return array        The addresses associated with this object.
     */
    public function getAddresses(bool $flat = false): array {
      $result = [];
      // Either return a flat or multidimensional array
      if ($flat === true) foreach ($this->addresses as $group)
        $result = array_merge($result, $group);
      else $result = $this->addresses;
      // Return the internal address structure
      return $result;
    }

    /**
     * Fetches the internal hostname.
     *
     * @return string The hostname associated with this object.
     */
    public function getHostname(): string {
      // Return the internal hostname
      return $this->hostname;
    }

    /**
     * Constructs the `Address` instance using a hostname as input.
     *
     * The provided hostname is assigned to the internal storage location and
     * then an attempt is made to resolve all A/AAAA records for the given
     * hostname. If DNS resolution fails then an exception is thrown.
     *
     * @param  string $hostname   The hostname used to setup the instance.
     *
     * @throws DNSResolutionError Upon failure when attempting to resolve the
     *                            provided hostname's DNS records.
     */
    protected function setupHostname(string $hostname) {
      // Add the provided hostname to the appropriate storage location
      $this->hostname = $hostname;
      // Attempt to resolve the provided hostname
      $records  = @dns_get_record($hostname, DNS_A | DNS_AAAA,
        $nameservers, $additional);
      // Merge any additional records into the primary array
      if (is_array($additional)) $records = array_merge($records, $additional);
      // Check for failure in resolving the provided hostname
      if ($records  === false) throw new DNSResolutionError('Could not '.
        'resolve the provided hostname: '.escapeshellarg($hostname));
      // Add each resulting address to the appropriate storage location
      foreach ($records as $record)
        if      (($record['type'] ?? null) === 'A')
          $this->addresses['A'][]    = $record['ip']   ?? false;
        else if (($record['type'] ?? null) === 'AAAA')
          $this->addresses['AAAA'][] = $record['ipv6'] ?? false;
      // Remove any invalid entries that might exist
      foreach ($this->addresses as &$addresses)
        $addresses = array_filter($addresses);
    }

    /**
     * Constructs the `Address` instance using an IP address as input.
     *
     * The provided IP address is added to the internal pool of addresses and
     * then an attempt is made to resolve a PTR record for the given address.
     *
     * @param string $address The IP address used to setup the instance.
     */
    protected function setupIPAddress(string $address) {
      // Add the provided address to the appropriate storage location
      if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false)
        $this->addresses['A'][]    = $address;
      if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false)
        $this->addresses['AAAA'][] = $address;
      // Attempt to resolve a PTR for the provided address
      $hostname = @gethostbyaddr($address);
      // Check if a valid hostname was found
      if ($hostname !== false && $hostname !== $address)
        // Save the resulting hostname for the provided address
        $this->hostname = $hostname;
    }
  }
