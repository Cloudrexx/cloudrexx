<?php
/**
 * This listener is responsible for the synchronization of the domains
 * {@see Cx\Core_Modules\MultiSite\Model\Entity\Domain} within the
 * components of the MultiSite system (Website Manager / Website Service / Website)
 * as well as the DNS service.
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      COMVATION Development Team <info@comvation.com>
 * @package     contrexx
 * @subpackage  coremodule_multisite
 */

namespace Cx\Core_Modules\MultiSite\Model\Event;

/**
 * DomainEventListenerException
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      COMVATION Development Team <info@comvation.com>
 * @package     contrexx
 * @subpackage  coremodule_multisite
 */
class DomainEventListenerException extends \Exception {}

/**
 * DomainEventListener
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      COMVATION Development Team <info@comvation.com>
 * @package     contrexx
 * @subpackage  coremodule_multisite
 * @todo        Split up into DomainEventListener and NetDomainEventListener
 */
class DomainEventListener implements \Cx\Core\Event\Model\Entity\EventListener {
    public function postRemove($eventArgs) {
        try {
            \Cx\Core\Setting\Controller\Setting::init('MultiSite', '','FileSystem');
            $mode = \Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite');
            $domain  = $eventArgs->getEntity();
            $em = $eventArgs->getEntityManager();
            if ($domain instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Domain) {
                switch ($mode) {
                    case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_MANAGER:
                        $this->manipulateDnsRecord($domain, 'remove', 'postRemove', $eventArgs);
                        break;

                    case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_SERVICE:
                        $this->domainMapping($domain, $mode, 'unMapDomain');
                        //update the domain cache file
                        $this->updateDomainRepositoryCache($em);
                        break;
                    
                    case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_HYBRID:
                        $this->manipulateDnsRecord($domain, 'remove', 'postRemove', $eventArgs);
                        if($domain->getType() == \Cx\Core_Modules\MultiSite\Model\Entity\Domain::TYPE_MAIL_DOMAIN ||
                           $domain->getType() == \Cx\Core_Modules\MultiSite\Model\Entity\Domain::TYPE_WEBMAIL_DOMAIN) {
                            return;
                        }
                        //update the domain cache file
                        $this->updateDomainRepositoryCache($em);
                        break;
                    
                    default:
                        break;
                }
            } elseif ($domain instanceof \Cx\Core\Net\Model\Entity\Domain) {
                $this->domainMapping($domain, $mode, 'unMapDomain');
                
                switch ($mode) {
                    case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_WEBSITE:
                        $websiteName = \Cx\Core\Setting\Controller\Setting::getValue('websiteName','MultiSite');
                        if (\FWValidator::isEmpty($websiteName)) {
                            break;
                        }
                        $params = array(
                                'websiteName' => $websiteName, 
                                'command'     => 'deleteDomainAlias', 
                                'domainName'  => $domain->getName()
                                ); 
                        $this->domainManipulation($params);
                        break;
                        
                    default :
                        break;
                }
            }
        } catch (\Exception $e) {
            \DBG::msg($e->getMessage());
        }
    }

    public function preUpdate($eventArgs) {
        try {
            \Cx\Core\Setting\Controller\Setting::init('MultiSite', '','FileSystem');
            $mode   = \Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite');
            $domain = $eventArgs->getEntity();
            if ($domain instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Domain) {
                switch ($mode) {
                    case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_MANAGER:
                    case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_HYBRID:
                        $this->manipulateDnsRecord($domain, 'update', 'preUpdate', $eventArgs);
                        break;

                    case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_SERVICE:
                        if($domain->getType() == \Cx\Core_Modules\MultiSite\Model\Entity\Domain::TYPE_EXTERNAL_DOMAIN) {
                            $this->domainMapping($domain, $mode, 'updateDomain');
                        }
                        break;

                    default:
                        break;
                }
            } elseif ($domain instanceof \Cx\Core\Net\Model\Entity\Domain) {
                $this->domainMapping($domain, $mode, 'updateDomain');
                
                switch ($mode) {
                    case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_WEBSITE:
                        $websiteName = \Cx\Core\Setting\Controller\Setting::getValue('websiteName','MultiSite');
                        if (\FWValidator::isEmpty($websiteName)) {
                            break;
                        }
                        $domainRepository = new \Cx\Core\Net\Model\Repository\DomainRepository();
                        $oldDomain = $domainRepository->findOneBy(array('id' => $domain->getId()));
                        
                        $params = array(
                                'websiteName' => $websiteName, 
                                'command'     => 'renameDomainAlias', 
                                'domainName'  => $domain->getName(),
                                'oldDomainName' => $oldDomain->getName()
                                ); 
                        $this->domainManipulation($params);                        
                        break;
                    
                    default:
                        break;
                }
            }
        } catch (\Exception $e) {
            \DBG::msg($e->getMessage());
            throw new DomainEventListenerException($e->getMessage());
        }
    }

    public function prePersist($eventArgs) {
        try {
            \Cx\Core\Setting\Controller\Setting::init('MultiSite', '','FileSystem');
            $mode = \Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite');
            $domain  = $eventArgs->getEntity();
            if ($domain instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Domain) {
                switch ($mode) {
                    case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_MANAGER:
                    case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_HYBRID:
                        $this->manipulateDnsRecord($domain, 'add', 'prePersist', $eventArgs);
                        break;

                    case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_SERVICE:
                        if($domain->getType() == \Cx\Core_Modules\MultiSite\Model\Entity\Domain::TYPE_EXTERNAL_DOMAIN) {
                            $this->domainMapping($domain, $mode, 'mapDomain');
                        }
                        break;

                    default:
                        break;
                }
            } elseif ($domain instanceof \Cx\Core\Net\Model\Entity\Domain) {
                // The mapping of $domain must only be performed for external domains. The BaseDN and FQDN must not be mapped, as they have already been mapped by the manager.
                // Note: It is important that the BaseDN and FQDN are not being stored in the DomainRepository.yml file.
                // Otherwise the domain mapping would result in an infinite loop
                $this->domainMapping($domain, $mode, 'mapDomain');
            }
        } catch (\Exception $e) {
            \DBG::msg($e->getMessage());
            throw new DomainEventListenerException($e->getMessage());
        }
    }

    public function postPersist($eventArgs) {
        try {
            \Cx\Core\Setting\Controller\Setting::init('MultiSite', '','FileSystem');
            $mode = \Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite');
            $em = $eventArgs->getEntityManager();
            $domain  = $eventArgs->getEntity();
            switch ($mode) {
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_SERVICE:
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_HYBRID:
                    $this->updateDomainRepositoryCache($em);
                    break;
                
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_WEBSITE:
                    $websiteName = \Cx\Core\Setting\Controller\Setting::getValue('websiteName','MultiSite');
                    if (!($domain instanceof \Cx\Core\Net\Model\Entity\Domain) || \FWValidator::isEmpty($websiteName)) {
                        break;
                    }
                    
                    $params = array(
                            'websiteName' => $websiteName, 
                            'command'     => 'createDomainAlias', 
                            'domainName'  => $domain->getName()
                            ); 
                    $this->domainManipulation($params);
                    break;
                    
                default:
                    break;
            }
        } catch (\Exception $e) {
            \DBG::msg($e->getMessage());
        }
    }

    private function manipulateDnsRecord($domain, $operation, $event, $eventArgs) {
        $this->logEvent('DNS '.$operation, $domain);
        
        $domainType = $domain->getType();
        
        if (\Cx\Core_Modules\MultiSite\Model\Entity\Domain::TYPE_EXTERNAL_DOMAIN == $domainType) {
            return;
        }
        
        switch ($domainType) {
            case \Cx\Core_Modules\MultiSite\Model\Entity\Domain::TYPE_FQDN:
                // FQDN shall not be persisted in postPersist event
                // as it already gets persisted in prePersist event.
                if ($event == 'postPersist') {
                    return;
                }

                // FQDN always points to ServiceServer
                $type = 'CNAME';
                $value = $domain->getWebsite()->getServiceServer()->getHostname();

                break;

            case \Cx\Core_Modules\MultiSite\Model\Entity\Domain::TYPE_BASE_DOMAIN:
                // In prePersist event, the DNS-record of BaseDN can't be created
                // yet, as the FQDN, on which the BaseDN depends on, has not yet
                // been flushed to the database.
                if ($event == 'prePersist' || $event == 'postPersist') {
                    return;
                }
                // In case MultiSite is operated in 'hybrid'-mode, then
                // the BaseDN is the same as FQDN. In that case we won't create the
                // BaseDN record as it would be an invalid DNS-record.
                if (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite') == \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_HYBRID) {
                    return;
                }
                $type = 'CNAME';
                // @TODO: Vanity URL should be handled by manager
                $value = $domain->getWebsite()->getIpAddress();
                break;
            case \Cx\Core_Modules\MultiSite\Model\Entity\Domain::TYPE_MAIL_DOMAIN:
            case \Cx\Core_Modules\MultiSite\Model\Entity\Domain::TYPE_WEBMAIL_DOMAIN:
                
                $type = 'A';

                if ($operation == 'remove') {
                    break;
                }
                
                $value= $domain->getWebsite()->getMailServiceServer()->getIpAddress();
                break;
                
            default:
                $type = 'CNAME';
                
                if ($domain->getWebsite() instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Website) {
                    $value= $domain->getWebsite()->getBaseDn()->getName();
                } else {
                    $config = \Env::get('config');
                    $value  = $config['domainUrl'];
                }
                break;
        }

        // add DNS record through hosting controller
        switch ($operation) {
            case 'add':
                $hostingController = \Cx\Core_Modules\MultiSite\Controller\ComponentController::getHostingController();
                $recordId = $hostingController->addDnsRecord($type, $domain->getName(), $value, \Cx\Core\Setting\Controller\Setting::getValue('multiSiteDomain','MultiSite'), \Cx\Core\Setting\Controller\Setting::getValue('pleskMasterSubscriptionId','MultiSite'));
                \DBG::msg(__METHOD__.": Set pleskId: $recordId");
                $domain->setPleskId($recordId);
                break;

            case 'remove':
                $hostingController = \Cx\Core_Modules\MultiSite\Controller\ComponentController::getHostingController();
                $hostingController->removeDnsRecord($type, $domain->getName(), $domain->getPleskId());
                break;

            case 'update':
                $hostingController = \Cx\Core_Modules\MultiSite\Controller\ComponentController::getHostingController();
                $recordId = $hostingController->updateDnsRecord($type, $domain->getName(), $value, \Cx\Core\Setting\Controller\Setting::getValue('multiSiteDomain','MultiSite'), \Cx\Core\Setting\Controller\Setting::getValue('pleskMasterSubscriptionId','MultiSite'), $domain->getPleskId());

                // check if DNS-record was updated
                if ($recordId == $domain->getPleskId()) {
                    break;
                }

                // link to new DNS-record
                \DBG::msg(__METHOD__.": Set new pleskId: $recordId");
                $domain->setPleskId($recordId);

                // if event has been triggered within the preUpdate model event,
                // we'll have to tell the UOW that we did alter the domain
                if ($eventArgs instanceof \Doctrine\ORM\Event\PreUpdateEventArgs) {
                    $em = $eventArgs->getEntityManager();
                    $uow = $em->getUnitOfWork();
                    $uow->recomputeSingleEntityChangeSet(
                        $em->getClassMetadata('Cx\Core_Modules\MultiSite\Model\Entity\Domain'),
                        $domain
                    );
                }
                break;

            default:
                break;
        }
    }

    private function updateDomainRepositoryCache($em) {
        $domainRepository = $em->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Domain');
        $domainRepository->exportDomainAndWebsite();
    }

    private function domainMapping($domain, $mode, $event) {
        $this->logEvent($event, $domain);
        $params = array(
            // The actual domain name that shall be mapped to a MultiSite component (Website, Service or Manager)
            'domainName'        => $domain->getName(),

            // If Net-domain, then we take the ID of the Net-Domain as new coreNetDomainId.
            // This only works as the YamlRepository (on which the Net-domain is managed by,
            // assigns a new ID as soon as a new Net-domain gets attached to the YamlRepository.
            // Otherwise, if MultiSite-domain, then we are in the process of forwarding a
            // mapping request to the Manager Server. Therefore, the coreNetDomainId can be
            // fetched from the domain object itself.
            'coreNetDomainId'   => ($domain instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Domain) ? $domain->getCoreNetDomainId() : $domain->getId(),

            // If Net-domain, then we are in the process of mapping a new domain to the currently
            // running MultiSite component (Website, Service or Manager) which is identified by $mode.
            // Otherwise, if MultiSite-domain, then we are in the process of forwarding a
            // mapping request to the Manager Server. Therefore, the componentType can be
            // fetched from the domain object itself.
            'componentType'     => ($domain instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Domain) ? $domain->getComponentType() : $mode,

            // If Net-domain, then componentId has to be set to 0 to mark the domain
            // being mapped to the current MultiSite component (Website, Service or Manager).
            // Otherwise, if MultiSite-domain, then we are in the process of forwarding a
            // mapping request to the Manager Server. Therefore, the componentId can be
            // fetched from the domain object itself.
            'componentId'       => ($domain instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Domain) ? $domain->getComponentId() : 0, 

            // If MultiSite-domain, then the domain could be a fqdn or a baseDn domain. Otherwise it is an alias domain.
            'type'              => ($domain instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Domain) ? $domain->getType() : \Cx\Core_Modules\MultiSite\Model\Entity\Domain::TYPE_EXTERNAL_DOMAIN,
        );

        switch ($mode) {
            case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_WEBSITE:
                $result = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnMyServiceServer($event, $params);
                break;

            case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_SERVICE:
                $result = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnManager($event, $params);
                break;

            case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_MANAGER:
            case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_HYBRID:
                $config = \Env::get('config');
                $params['auth'] = json_encode(array('sender' => $config['domainUrl']));
                try {
                    $objJsonMultiSite = new \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController();
                    $objJsonMultiSite->$event(array('post' => $params));
                } catch (\Exception $e) {
                    throw new DomainEventListenerException($e->getMessage());
                }
                return;
                break;

            default:
                return;
                break;
        }

        if (!$result || $result->status != 'success') {
            if (isset($result->log)) {
                \DBG::appendLogs(array_map(function($logEntry) {return '(DNS) '.$logEntry;}, $result->log));
            }
            throw new DomainEventListenerException($result->message);
        }
        
        if ($result->data->status == 'error') {
            if (isset($result->data->log)) {
                \DBG::appendLogs(array_map(function($logEntry) {return '(DNS) '.$logEntry;}, $result->data->log));
            }
            throw new \Cx\Core\Error\Model\Entity\ShinyException("The domain ".$domain->getName()." can't be used for this website as there is already another website by that domain present.");
        }

        if (isset($result->data->log)) {
            \DBG::appendLogs(array_map(function($logEntry) {return '(DNS) '.$logEntry;}, $result->data->log));
        }
    }

    private function domainManipulation($params)
    {
        if (\FWValidator::isEmpty($params) || !is_array($params)) {
            return;
        }

        $result = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnMyServiceServer('domainManipulation', $params);
        if (!$result || $result->status == 'error') {
            if ($result && isset($result->message)) {
                \DBG::log($result->message);
            }
            throw new DomainEventListenerException('MultiSite (DomainEventListener): domainManipulation() Failed.');
        }
        return true;
    }
    
    protected function logEvent($eventName, $domain) {
        $type = method_exists($domain, 'getType') ? ' / '.$domain->getType() : '';
        \DBG::msg("MultiSite (DomainEventListener): $eventName ({$domain->getName()} / ".get_class($domain).$type.")");
    }
    
    public function onEvent($eventName, array $eventArgs) {        
        $this->$eventName(current($eventArgs));
    }
}

