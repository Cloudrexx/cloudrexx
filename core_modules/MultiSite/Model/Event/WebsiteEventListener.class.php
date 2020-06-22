<?php
/**
 * This listener is responsible to maintain the relations
 * to the associated domains (Cx\Core_Modules\MultiSite\Model\Entity\Domain)
 * of a website.
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      COMVATION Development Team <info@comvation.com>
 * @package     contrexx
 * @subpackage  coremodule_multisite
 */

namespace Cx\Core_Modules\MultiSite\Model\Event;

/**
 * WebsiteEventListenerException
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      COMVATION Development Team <info@comvation.com>
 * @package     contrexx
 * @subpackage  coremodule_multisite
 */
class WebsiteEventListenerException extends \Exception {}

/**
 * WebsiteEventListener
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      COMVATION Development Team <info@comvation.com>
 * @package     contrexx
 * @subpackage  coremodule_multisite
 */
class WebsiteEventListener implements \Cx\Core\Event\Model\Entity\EventListener {
    public function preUpdate($eventArgs) {
        \DBG::msg('MultiSite (WebsiteEventListener): preUpdate');
        $em      = $eventArgs->getEntityManager();
        $uow     = $em->getUnitOfWork();
        $website = $eventArgs->getEntity();
        $domains = $website->getDomains();

        //Update Website owner
        $mode = \Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite');
        if (in_array($mode, array(\Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_HYBRID, 
                                  \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_SERVICE))
        ) {
            $this->setOwnerUser($eventArgs);
        }

        foreach ($domains as $domain) {
            \DBG::msg('Update domain (map to new IP of Website): '.$domain->getName());
            if ($domain->getComponentType() == \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_WEBSITE
                && $domain->getWebsite()
                && !$domain->getComponentId()
            ) {
                \DBG::msg('Domain: Set component ID to website ID: '.$domain->getWebsite()->getId());
                $domain->setComponentId($domain->getWebsite()->getId());
                $uow->computeChangeSet(
                    $em->getClassMetadata('Cx\Core_Modules\MultiSite\Model\Entity\Domain'),
                    $domain
                );
            }
        }
    }

    public function postUpdate($eventArgs) {
        \DBG::msg('MultiSite (WebsiteEventListener): postUpdate');
        $em      = $eventArgs->getEntityManager();
        $website = $eventArgs->getEntity();
        $isWebsiteInactive = ($website->getStatus() == \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_DISABLED
                            || $website->getStatus() == \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_OFFLINE);
        
        switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
            case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_HYBRID:
                //disable website mail service
                if ($isWebsiteInactive) {
                    $this->disableWebsiteMailService($website);
                }
                
            case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_SERVICE:
                $websiteConfigPath = \Cx\Core\Setting\Controller\Setting::getValue('websitePath','MultiSite') . '/' . $website->getName() . \Env::get('cx')->getConfigFolderName();
                if (!file_exists($websiteConfigPath)) {
                    break;
                }
                \Cx\Core\Setting\Controller\Setting::init('MultiSite', '', 'FileSystem', $websiteConfigPath);
                \Cx\Core\Setting\Controller\Setting::set('websiteState', $website->getStatus());
                \Cx\Core\Setting\Controller\Setting::update('websiteState');

                // This will execute the flush-cache command on the website
                // $website due to the fact that we did just initialize the
                // MultiSite config of that website.
                // Therefore the method execAsync() will assume that this
                // website is currently loaded.
                // This cache-flush is essential for the following reasons:
                // - drop any existing page and esi cache (in case the website
                //   has been enabled or disabled)
                // - drop user cache to force re-fetch of MultiSite.yml that
                //   has just been upaded
                //
                // Execute `./cx MultiSite pass <website> sudo Cache clear all`
                // asynchronously.
                //
                // Note: the command 'sudo' will ensure that the 'Cache'
                // command will be executed even if the website is currently
                // disabled or offline.
                $cx = \Cx\Core\Core\Controller\Cx::instanciate();
                $cx->getComponent('Core')->execAsync(
                    'sudo',
                    array(
                        'Cache',
                        'clear',
                        'all',
                    )
                );

                // we must re-initialize the original MultiSite settings of the main installation
                \Cx\Core\Setting\Controller\Setting::init('MultiSite', '', 'FileSystem', null, \Cx\Core\Setting\Controller\Setting::REPOPULATE);
                break;

            case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_MANAGER:
// TODO: updating ip address of domain is somehow redundant. it should only be done if ip address of website has changed
                //disable website mail services
                if ($isWebsiteInactive) {
                    $this->disableWebsiteMailService($website);
                }
                
                $domains = $website->getDomains();
                foreach ($domains as $domain) {
                    \DBG::msg('Update domain (map to new IP of Website): '.$domain->getName());
                    \Env::get('cx')->getEvents()->triggerEvent('model/preUpdate', array(new \Doctrine\ORM\Event\LifecycleEventArgs($domain, $em)));
                }
                
                //hostName
                $websiteServiceServer = $website->getWebsiteServiceServer();

                //Update the Website Status and codeBase
                $params = array(
                    'websiteId'   => $website->getId(),
                    'status'      => $website->getStatus(),
                    'codeBase'    => $website->getCodeBase(),
                    'ownerId'     => $website->getOwner() ? $website->getOwner()->getId() : 0,
                    'userId'      => $website->getOwner() ? $website->getOwner()->getId() : 0,
                    'email'       => $website->getOwner() ? $website->getOwner()->getEmail() : '',
                    'mode'        => $website->getMode(),
                    'serverWebsiteId' => $website->getServerWebsite()
                        ? $website->getServerWebsite()->getId() : '',
                );
                \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnServiceServer('setWebsiteDetails', $params, $websiteServiceServer);
                break;
        }
    }
    
    /**
     * Disable website mail service
     * 
     * @param \Cx\Core_Modules\MultiSite\Model\Entity\Website $website website
     * 
     * @return boolean
     * @throws \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteException
     */
    public function disableWebsiteMailService(\Cx\Core_Modules\MultiSite\Model\Entity\Website $website) {
        if (\FWValidator::isEmpty($website)) {
            return;
        }
        try {
            $mailServiceServer = $website->getMailServiceServer();
            $mailAccountId     = $website->getMailAccountId();

            if (\FWValidator::isEmpty($mailServiceServer) || \FWValidator::isEmpty($mailAccountId)) {
                return;
            }
            
            $hostingController = \Cx\Core_Modules\MultiSite\Controller\ComponentController::getMailServerHostingController($mailServiceServer);
            
            $status = $hostingController->getMailServiceStatus($mailAccountId);
            if ($status == 'true') {
                $mailServiceServer->disableService($website);
            }
        } catch (Exception $e) {
            \DBG::log('Unable to disable the website mail service: ' . $e->getMessage());
            throw new \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteException('Unable to disable the website mail service');
        }
    }
    
    public function preRemove($eventArgs) {
        \DBG::msg('MultiSite (WebsiteEventListener): preRemove');
        $website = $eventArgs->getEntity();
        
        try {
             $website->destroy();
        } catch (\Exception $e) {
            throw new \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteException('Unable to delete the website'.  $e->getMessage());
        } 
    }

    /**
     * Set the user as the website owner
     * 
     * @global array $_ARRAYLANG language variable
     * 
     * @param object $eventArgs
     * @throws WebsiteEventListenerException
     */
    public function setOwnerUser($eventArgs) 
    {
        global $_ARRAYLANG;
        
        $website = $eventArgs->getEntity();
        
        // if the Website.owner field is changed: Update all the related subsequent processes
        if ($eventArgs->hasChangedField('owner')) {
            $params = array('ownerEmail' => $website->getOwner() ? $website->getOwner()->getEmail() : '');
            $resp   = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnWebsite('setWebsiteOwner', $params, $website);
            if ($resp && $resp->status == 'error' || !$resp) {
                throw new WebsiteEventListenerException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CHANGE_OWNER_USER_ERROR']);
            }
        }
    }

    public function onEvent($eventName, array $eventArgs) {        
        $this->$eventName(current($eventArgs));
    }
}
