<?php

/**
 * Web distribution interface for Multisite hosts
 * 
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_multisite
 */

namespace Cx\Core_Modules\MultiSite\Controller;

class WebDistributionControllerException extends \Exception {}

/**
 * Manage web distributions
 * 
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_multisite
 */
interface WebDistributionController {

    /**
     * Create a Customer
     * @param \Cx\Core\Model\Model\Entity\Subscription
     * @throws MultiSiteDbException On error
     */
    public function createCustomer(\Cx\Core_Modules\MultiSite\Model\Entity\Customer $customer);
    
    /**
     * Create new site/domain
     * 
     * @param string  $domain         Name of the site/domain to create
     * @param string  $dnsTarget      (reference) Target of the vanity DNS entry
     */
    public function createWebDistribution($domain, &$dnsTarget);
    
    /**
     * Renaming the site/domain
     * 
     * @param string $oldDomainName old domain name
     * @param string $newDomainName new domain name
     */
    public function renameWebDistribution($oldDomainName, $newDomainName);
    
    /**
     * Remove the site by the domain name.
     * 
     * @param string $domain Domain name to remove
     */
    public function deleteWebDistribution($domain);
    
    /**
     * Get all the sites under the existing subscription
     */
    public function getAllWebDistributions();

    /**
     * Creates an alias for a distribution
     * @param string $mainName System name of the distribution
     * @param string $aliasName Alias FQDN
     * @throws WebDistributionControllerException If any error occurs
     */
    public function createWebDistributionAlias($mainName, $aliasName);

    /**
     * Renames an alias for a distribution
     * @param string $mainName System name of the distribution
     * @param string $oldAliasName Alias FQDN
     * @param string $newAliasName Alias FQDN
     * @throws WebDistributionControllerException If any error occurs
     */
    public function renameWebDistributionAlias($mainName, $oldAliasName, $newAliasName);

    /**
     * Deletes an alias for a distribution
     * @param string $mainName System name of the distribution
     * @param string $aliasName Alias FQDN
     * @throws WebDistributionControllerException If any error occurs
     */
    public function deleteWebDistributionAlias($mainName, $aliasName);

    /**
     * Returns all web distributions
     * @param string $websiteName (optional) Name of the website to get aliases for
     * @throws WebDistributionControllerException If any error occurs
     */
    public function getAllWebDistributionAliases($websiteName = '');
}
