<?php

/**
 * OrderSubscriptionEventListener
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Project Team SS4U <info@comvation.com>
 * @package     contrexx
 * @subpackage  coremodule_multisite
 */

namespace Cx\Core_Modules\MultiSite\Model\Event;

/**
 * Class OrderSubscriptionEventListenerException
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Project Team SS4U <info@comvation.com>
 * @package     contrexx
 * @subpackage  coremodule_multisite
 */
class OrderSubscriptionEventListenerException extends \Exception {}

/**
 * Class OrderSubscriptionEventListener
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Project Team SS4U <info@comvation.com>
 * @package     contrexx
 * @subpackage  coremodule_multisite
 */
class OrderSubscriptionEventListener implements \Cx\Core\Event\Model\Entity\EventListener {
    /**
     * @var array
     */
    protected $entitiesToPersistOnPostFlush = array();

    /**
     * @var boolean 
     */
    protected $entitiesToFlushOnPostFlush = false;
    
    public function preUpdate($eventArgs) {
        $subscription = $eventArgs->getEntity();
        
        //Update website owner
        $mode = \Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite');
        if (in_array($mode, array(\Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_MANAGER, 
                                  \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_HYBRID))
        ) {
            $this->updateWebsiteOwner($subscription);
            $this->entitiesToFlushOnPostFlush = true;
        }
        
        // in case product has been changed, we have to migrate the associated entity
        if ($eventArgs->hasChangedField('product')) {
            $oldProduct = $eventArgs->getOldValue('product');
            $this->updateProductEntity($subscription, $subscription->getProduct(), $oldProduct);
            return;
        }
    }
    
    public function postUpdate($eventArgs) {
        $subscription = $eventArgs->getEntity();
        $changeSet    = $eventArgs->getEntityManager()->getUnitOfWork()->getEntityChangeSet($subscription);

        // in case product has been changed, we shall triger the model event payComplete
        // this will update the license information on the associated websites
        if (!empty($changeSet['product'])) {            
            \Env::get('cx')->getEvents()->triggerEvent('model/payComplete', array(new \Doctrine\ORM\Event\LifecycleEventArgs($subscription, \Env::get('em'))));
            $this->registerAffiliateCredit($eventArgs->getEntity(), $eventArgs->getEntityManager());
        }
    }

    public function postPersist($eventArgs) {
        $this->registerAffiliateCredit($eventArgs->getEntity(), $eventArgs->getEntityManager());
    }

    /**
     * Register the affiliate commission to a subscription
     */
    public function registerAffiliateCredit($subscription, $em) {
        // abort in case the affiliate system is not active
        $affiliateSystemActive = \Cx\Core\Setting\Controller\Setting::getValue('affiliateSystem','MultiSite');
        if (!$affiliateSystemActive) {
            return;
        }

        $productEntity = $subscription->getProductEntity();
        if (   !($productEntity instanceof \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteCollection)
            && !($productEntity instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Website)
        ) {
            return;
        }

        // check if subscription is active 
        if ($subscription->getState() != \Cx\Modules\Order\Model\Entity\Subscription::STATE_ACTIVE) {
            return;
        }

        // check if subscription is free of charge
        if (!$subscription->getPaymentAmount()) {
            return;
        }

        // check if subscription has been paid
        if (!in_array($subscription->getPaymentState(), array(\Cx\Modules\Order\Model\Entity\Subscription::PAYMENT_PAID, \Cx\Modules\Order\Model\Entity\Subscription::PAYMENT_RENEWAL))) {
            return;
        }
        
        // check if a credit for the subscription has already been issued
        $qb = \Env::get('em')->createQueryBuilder();
        $qb->select('ac')
            ->from('Cx\Core_Modules\MultiSite\Model\Entity\AffiliateCredit', 'ac')
            ->where('ac.subscription = ?1')
            ->setParameter(1, $subscription->getId());
        if ($qb->getQuery()->getResult()) {
            return;
        }

        // initialize new credit for affiliate commission
        $affiliateCredit = new \Cx\Core_Modules\MultiSite\Model\Entity\AffiliateCredit(); 
        $affiliateCredit->setSubscription($subscription);
        $affiliateCredit->setCredited(false);
        
        // set currency
        $currencyId= \Cx\Modules\Crm\Controller\CrmLibrary::getDefaultCurrencyId();
        $currency  = $em->getRepository('\Cx\Modules\Crm\Model\Entity\Currency')->findOneById($currencyId);
        $affiliateCredit->setCurrency($currency);

        // get user of new subscription
        $crmContactId = $subscription->getOrder()->getContactId();
        $userId = \Cx\Modules\Crm\Controller\CrmLibrary::getUserIdByCrmUserId($crmContactId);
        $user = \FWUser::getFWUserObject()->objUser->getUser($userId);
        if (!$user) {
            return;
        }
        $affiliateIdReferenceProfileAttributeId = \Cx\Core\Setting\Controller\Setting::getValue('affiliateIdReferenceProfileAttributeId','MultiSite');
        $affiliateIdProfileAttributeId = \Cx\Core\Setting\Controller\Setting::getValue('affiliateIdProfileAttributeId','MultiSite');
        // get affiliateId of referer
        $refereeId = $user->getProfileAttribute($affiliateIdReferenceProfileAttributeId);
        // in case the user did not follow an affiliate-link,
        // we can abort the process
        if (empty($refereeId)) {
            return;
        }
        // fetch the affiliate-user who did refere the new user to cloudrexx
        $objUser = \FWUser::getFWUserObject()->objUser->getUsers(array(
            $affiliateIdProfileAttributeId => $refereeId,
            'active' => true,
            'verified' => true,
        ));
        if (!$objUser) {
            return;
        }
        $objRefereeUser = null;
        while(!$objUser->EOF) {
            // verify the affiliate-ID again
            if ($objUser->getProfileAttribute($affiliateIdProfileAttributeId) === $refereeId) {
                $objRefereeUser = $objUser;
                break;
            }
            $objUser->next();
        }
        // no referee found -> self signup without recommendation (affiliate system)
        if (!$objRefereeUser) {
            return;
        }
        // set referee that shall receive the affiliate commission
        $referee = $em->getRepository('Cx\Core\User\Model\Entity\User')->findOneById($objRefereeUser->getId());
        $affiliateCredit->setReferee($referee);

        // set commission
// TODO: implement commission rate as configuration option
        $commissionRate = 10 / 100;
        $affiliateCredit->setAmount($subscription->getPaymentAmount() * $commissionRate);

        // put credit into buffer to be persisted to the database in the postFlush event
        $this->entitiesToPersistOnPostFlush[] = $affiliateCredit;
    }
    
    /**
     * Switch the product
     * 
     * @global array $_ARRAYLANG
     * 
     * @param \Cx\Modules\Pim\Model\Entity\Product $newProduct
     * @param \Cx\Modules\Pim\Model\Entity\Product $oldProduct
     * 
     * @throws \Cx\Core\Error\Model\Entity\ShinyException
     */
    public function updateProductEntity(\Cx\Modules\Order\Model\Entity\Subscription $subscription, 
                                  \Cx\Modules\Pim\Model\Entity\Product $newProduct, 
                                  \Cx\Modules\Pim\Model\Entity\Product $oldProduct)
    {
        global $_ARRAYLANG;
        
        $newProductEntityClass = $newProduct->getEntityClass();
        $oldProductEntityClass = $oldProduct->getEntityClass();
        
        if (empty($newProductEntityClass) || empty($oldProductEntityClass)) {
            return;
        }
        
        $productOptions        = $newProduct->getEntityAttributes();
        $websiteCollectionRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\WebsiteCollection');

        if (   $oldProductEntityClass == 'Cx\Core_Modules\MultiSite\Model\Entity\WebsiteCollection' 
            && $newProductEntityClass == 'Cx\Core_Modules\MultiSite\Model\Entity\Website'
        ) {
            \DBG::msg(__METHOD__.': Downgrade from WebsiteCollection to Website');
            throw new \Cx\Core\Error\Model\Entity\ShinyException(sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_UPDATE_SUBSCRIPTION_DOWNGRADE_ERROR'], $oldProduct->getName(), $newProduct->getName()));
        } elseif (   $oldProductEntityClass == 'Cx\Core_Modules\MultiSite\Model\Entity\WebsiteCollection'
                  && $newProductEntityClass == 'Cx\Core_Modules\MultiSite\Model\Entity\WebsiteCollection'
        ) {
            \DBG::msg(__METHOD__.': Update WebsiteCollection');
            $productEntityObj = $subscription->getProductEntity();
            if ($productEntityObj instanceof \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteCollection) {
                $websiteCollectionRepo->setWebsiteCollectionMetaInformation($productEntityObj, $productOptions);
                $em = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager();
                $em->getUnitOfWork()->computeChangeSet(
                    $em->getClassMetadata('Cx\Core_Modules\MultiSite\Model\Entity\WebsiteCollection'), $productEntityObj
                );
            }
        } elseif (   $oldProductEntityClass == 'Cx\Core_Modules\MultiSite\Model\Entity\Website'
                  && $newProductEntityClass == 'Cx\Core_Modules\MultiSite\Model\Entity\WebsiteCollection'
        ) {
            \DBG::msg('Upgrade from Website to WebsiteCollection');
            $websiteCollection = new \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteCollection();
            $websiteCollectionRepo->setWebsiteCollectionMetaInformation($websiteCollection, $productOptions);

            // fetch old product entity of subscription
            $oldProductEntity = $oldProduct->getEntityById($subscription->getProductEntityId());

            // set new product entity
            $subscription->setProductEntity($websiteCollection);
            $websiteCollection->setTempData(array('assignedSubscriptionId' => $subscription->getId()));
            $this->entitiesToPersistOnPostFlush[] = $websiteCollection;
     
            // attach existing Website to new WebsiteCollection (in case the subscription used to have a Website)
            if ($oldProductEntity instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Website) {
                $websiteCollection->addWebsite($oldProductEntity);
            }
        }
    }

    public function postFlush($eventArgs) {
        // check if there are any new entities present we need to persist
        if (!count($this->entitiesToPersistOnPostFlush) && !$this->entitiesToFlushOnPostFlush) {
            return;
        }

        $this->entitiesToFlushOnPostFlush = false;
        // persist the new entities
        $em = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager();
        if ($this->entitiesToPersistOnPostFlush) {
            while ($entity = array_shift($this->entitiesToPersistOnPostFlush)) {
                $em->persist($entity);
            }
        }
        $em->flush();
    }

    /**
     * Pay Complete Event
     * 
     * @param type $eventArgs
     */
    public function payComplete($eventArgs) {
        $subscription           = $eventArgs->getEntity();
        $productEntity          = $subscription->getProductEntity();
        $entityAttributes       = $subscription->getProduct()->getEntityAttributes();
        $websiteTemplate        = null;
        
        if ($productEntity instanceof \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteCollection) {
            $websites = $productEntity->getWebsites();
        } elseif ($productEntity instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Website) {
            $websites = array($productEntity);
        } else {
            return;
        }
        
        \DBG::msg(__METHOD__ . ': Subscription::$productEntity is '.get_class($productEntity));

        // load website template
        if (isset($entityAttributes['websiteTemplate'])) {
            $websiteTemplate = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\WebsiteTemplate')->findOneById($entityAttributes['websiteTemplate']);
        }

        // assign website template to WebsiteCollection
        if ($websiteTemplate && $productEntity instanceof \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteCollection) {
            $productEntity->setWebsiteTemplate($websiteTemplate);
        }

        if ($subscription->getExpirationDate()) {
            $entityAttributes['subscriptionExpiration'] = $subscription->getExpirationDate()->getTimestamp();
        }
        
        foreach ($websites as $website) {
            if (!($website instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Website)) {
                continue;
            }

            $entityAttributes['initialSignUp'] = false;
            switch ($website->getStatus()) {
                case \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_INIT:
                    // perform initial sign-up in case the user has not yet been verified
                    $entityAttributes['initialSignUp'] = !\FWUser::getFWUserObject()->objUser->getUser($website->getOwner()->getId(), true)->isVerified();
// why return????
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
     * Migrate expired subscriptions to new products
     * 
     * @param object $eventArgs
     */
    public function expired($eventArgs) {
        $subscription      = $eventArgs->getEntity();
        $productEntity     = $subscription->getProductEntity();
        $entityAttributes  = $subscription->getProduct()->getEntityAttributes();
        $websiteTemplate   = null;

        // abort in case the subscription is not of our concern (not based on a Website or WebsiteCollection)
        if (   !($productEntity instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Website)
            && !($productEntity instanceof \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteCollection)
        ) {
            return;
        }

        // load website template
        if (isset($entityAttributes['websiteTemplate'])) {
            $websiteTemplate = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\WebsiteTemplate')->findOneById($entityAttributes['websiteTemplate']);
        }

        // abort in case the associated product of the expired subscription
        // does not have a WebsiteTemplate defined
        if (!$websiteTemplate || !($websiteTemplate instanceof \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteTemplate)) {
            return;
        }

        // Fetch the Product the expired websites shall be migrated to.
        // Abort in case no migration path has been defined.
        $migrationProduct = $websiteTemplate->getMigrationProductOnExpiration();
        if (!$migrationProduct || !($migrationProduct instanceof \Cx\Modules\Pim\Model\Entity\Product)) {
            return;
        }

        // migrate the subscription to the new product
        \DBG::msg("Migrate subscription {$subscription->getId()} from {$subscription->getProduct()->getName()} to {$migrationProduct->getName()}");
        $subscription->setProduct($migrationProduct);

        // set new expiration date of subscription
        if ($migrationProduct->isExpirable()) {
            $subscription->setExpirationDate($migrationProduct->getExpirationDate());
        } else {
            $subscription->setExpirationDate(null);
        }

        // Flushing the subscription at this point is basically obsolete,
        // as the cronjob Cx\Modules\Order\Controller\ComponentController::touchProductEntitiesOfExpiredSubscriptions
        // does also perform a flush afterwards.
        // However, we could end up in a situation where there are too many subscriptions
        // to be flushed which could result in a memory overflow or timeout exception.
        // Therefore we shall perform a flush operation for each individual subscription.
        // This will slow down the overall operation but shall ensure that the task can
        // get done, even if it may require for the operation (cronjob) to be run more
        // then once to get done.
        \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager()->flush();
    }

    /**
     * Terminated Event Change the website status to offline
     * 
     * @param object $eventArgs
     */
    public function terminated($eventArgs) {
        $subscription      = $eventArgs->getEntity();
        $productEntity     = $subscription->getProductEntity();
        
        if ($productEntity instanceof \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteCollection) {
            // disable all associated websites of websiteCollection
            foreach ($productEntity->getWebsites() as $website) {
                $website->setStatus(\Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_DISABLED);
            }
        } elseif ($productEntity instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Website) {
            // disable associated website
            $productEntity->setStatus(\Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_DISABLED);
        }
    }

    /**
     * Update the website Owner
     * 
     * @global array $_ARRAYLANG language variable
     * 
     * @param \Cx\Modules\Order\Model\Entity\Subscription $subscription
     * @param object $eventArgs
     * 
     * @throws OrderSubscriptionEventListenerException
     */
    public function updateWebsiteOwner(\Cx\Modules\Order\Model\Entity\Subscription $subscription) {
        global  $_ARRAYLANG;
        
        $em = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager();
        
        $productEntity = $subscription->getProductEntity();
        $order   = $subscription->getOrder();
        $userId  = \Cx\Modules\Crm\Controller\CrmLibrary::getUserIdByCrmUserId($order->getContactId());
        $objUser = $em->getRepository('\Cx\Core\User\Model\Entity\User')->findOneById($userId);
        if (!$objUser) {
            throw new OrderSubscriptionEventListenerException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CHANGE_OWNER_USER_ERROR']);
        }
        
        if ($productEntity instanceof \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteCollection) {
            //Update the subscription details of all the websites associated owner
            foreach ($productEntity->getWebsites() as $website) {
                $website->setOwner($objUser);
            }
        } elseif ($productEntity instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Website) {
            //Update the subscription details of the website associated owner
            $productEntity->setOwner($objUser);
        }
    }
    
    public function onEvent($eventName, array $eventArgs) {
        \DBG::msg(__METHOD__.": $eventName");
        $this->$eventName(current($eventArgs));
    }
}
