<?php

/**
 * Main controller for Multisite
 * 
 * @copyright   Comvation AG
 * @author      Project Team SS4U <info@comvation.com>
 * @package     contrexx
 * @subpackage  coremodule_multisite
 */

namespace Cx\Core_Modules\MultiSite\Controller;

class DnsControllerException extends \Exception {}

/**
 * manage the Dns Records
 * 
 * @copyright   Comvation AG
 * @author      Project Team SS4U <info@comvation.com>
 * @package     contrexx
 * @subpackage  coremodule_multisite
 */
interface DnsController {

    /**
     * add the DNS record
     * 
     * @param string  $type   DNS-Record type
     * @param string  $host   DNS-Record host
     * @param string  $value  DNS-Record value
     * @param string  $zone   Name of DNS-Zone
     * @param integer $zoneId Id of plesk subscription to add the record to
     *
     * @return integer
     * @throws ApiRequestException On error
     */
    public function addDnsRecord($type = 'A', $host, $value, $zone, $zoneId);

    /**
     * Remove the DNS Record
     * 
     * @param string  $type     DNS-Record-type
     * @param string  $host     DNS-Record-host
     * @param integer $recordId DNS-Record-Id
     * 
     * @return object
     * @throws ApiRequestException On error
     */
    public function removeDnsRecord($type, $host, $recordId);

    /**
     * Update the Dns Record
     * 
     * @param string  $type   DNS-Record type
     * @param string  $host   DNS-Record host
     * @param string  $value  DNS-Record value
     * @param string  $zone   Name of DNS-Zone
     * @param integer $zoneId Id of plesk subscription to update the record
     * @param integer $recordId DNS-Record id
     *
     * @return object
     * @throws ApiRequestException On error
     */
    public function updateDnsRecord($type, $host, $value, $zone, $zoneId, $recordId);

    /**
     * Get the all Dns Records
     * 
     * @return array
     * @throws ApiRequestException On error
     */
    public function getDnsRecords();
}
