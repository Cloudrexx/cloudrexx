<?php
/**
 * WebsiteCollectionEventListener class
 * 
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      COMVATION Development Team <info@comvation.com>
 * @package     contrexx
 * @subpackage  coremodule_multisite
 */

namespace Cx\Core_Modules\MultiSite\Model\Event;

/**
 * WebsiteCollectionEventListenerException
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      COMVATION Development Team <info@comvation.com>
 * @package     contrexx
 * @subpackage  coremodule_multisite
 */
class WebsiteCollectionEventListenerException extends \Exception {}

/**
 * WebsiteCollectionEventListener
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      COMVATION Development Team <info@comvation.com>
 * @package     contrexx
 * @subpackage  coremodule_multisite
 */
class WebsiteCollectionEventListener implements \Cx\Core\Event\Model\Entity\EventListener {
    
    public function postPersist($eventArgs) {
        \DBG::msg(__METHOD__);
        $this->assignToSubscription($eventArgs);
    }

    protected function assignToSubscription($eventArgs) {
        $websiteCollection = $eventArgs->getEntity();
        $tempData = $websiteCollection->getTempData();
        if (!empty($tempData['assignedSubscriptionId'])) {
            $subscription = \Env::get('em')->getRepository('Cx\Modules\Order\Model\Entity\Subscription')->findOneById($tempData['assignedSubscriptionId']);
            if ($subscription) {
                $subscription->setProductEntity($websiteCollection);
            }
            $websiteCollection->setTempData(array());
            \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager()->flush();
        }
    }
    
    /**
     * Pay Complete Event
     * 
     * @param type $eventArgs
     */
    public function payComplete($eventArgs) {
        \DBG::msg('MultiSite (WebsiteCollectionEventListener): payComplete');
        $subscription           = $eventArgs->getEntity();
        $websiteCollection      = $subscription->getProductEntity();
        $entityAttributes       = $subscription->getProduct()->getEntityAttributes();
        $websiteTemplate        = null;
        
        if (!($websiteCollection instanceof \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteCollection)) {
            return;
        }
        
        \DBG::msg(__METHOD__ . ': Subscription::$productEntity is WebsiteCollection');

        if (isset($entityAttributes['websiteTemplate'])) {
            $websiteTemplate = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\WebsiteTemplate')->findOneById($entityAttributes['websiteTemplate']);
            if ($websiteTemplate) {
                $websiteCollection->setWebsiteTemplate($websiteTemplate);
            }
        }

        if ($subscription->getExpirationDate()) {
            $entityAttributes['subscriptionExpiration'] = $subscription->getExpirationDate()->getTimestamp();
        }
        
        foreach ($websiteCollection->getWebsites() as $website) {
            if (!($website instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Website)) {
                continue;
            }

            $entityAttributes['initialSignUp'] = false;
            switch ($website->getStatus()) {
                case \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_INIT:
                    // perform initial sign-up in case the user has not yet been verified
                    $entityAttributes['initialSignUp'] = !\FWUser::getFWUserObject()->objUser->getUser($website->getOwner()->getId(), true)->isVerified();
                    $website->setup($entityAttributes);
                    break;
                
                case \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_DISABLED:
                    $website->setStatus(\Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_OFFLINE);
                    
                case \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_ONLINE:
                case \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_OFFLINE:
                    if ($websiteTemplate instanceof \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteTemplate) {
                        $website->setupLicense($entityAttributes);
                    }
                    break;

                default:
                    break;
            }
        }
        
    }
    /**
     * Terminated Event Change the website status to offline
     * 
     * @param object $eventArgs
     */
    public function terminated($eventArgs) {
        \DBG::msg('MultiSite (WebsiteCollectionEventListener): terminated');
        $subscription      = $eventArgs->getEntity();
        $websiteCollection = $subscription->getProductEntity();
        
        //Set all the associated websiteCollections website to offline
        if ($websiteCollection instanceof \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteCollection) {
            foreach ($websiteCollection->getWebsites() as $website) {
                $website->setStatus(\Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_DISABLED);
            }
        }
        
    }

    /**
     * Remove the websites under the websiteCollection
     * 
     * @param object $eventArgs
     * @throws WebsiteCollectionEventListenerException
     */
    public function preRemove($eventArgs) {
        \DBG::msg('MultiSite (WebsiteCollectionEventListener): preRemove');
        $websiteCollection = $eventArgs->getEntity();
        $websites = $websiteCollection->getWebsites();

        try {
            if (!\FWValidator::isEmpty($websites)) {
                foreach ($websites as $website) {
                    \Env::get('em')->remove($website);
                }
                \Env::get('em')->flush();
            }
        } catch (\Exception $e) {
            \DBG::log($e->getMessage());
            throw new WebsiteCollectionEventListenerException('Unable to delete the website.');
        }
    }

    public function onEvent($eventName, array $eventArgs) {        
        $this->$eventName(current($eventArgs));
    }
}
