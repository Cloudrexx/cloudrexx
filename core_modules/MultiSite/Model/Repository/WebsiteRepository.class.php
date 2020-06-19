<?php
/**
 * WebsiteRepository
 *
 * @copyright   Comvation AG
 * @author      Project Team SS4U <info@comvation.com>
 * @package     contrexx
 * @subpackage  coremodule_multisite
 */

namespace Cx\Core_Modules\MultiSite\Model\Repository;

/**
 * WebsiteRepositoryException
 *
 * @copyright   Comvation AG
 * @author      COMVATION Development Team <info@comvation.com>
 * @package     contrexx
 * @subpackage  coremodule_multisite
 */
class WebsiteRepositoryException extends \Exception {}

/**
 * WebsiteRepository
 *
 * @copyright   Comvation AG
 * @author      COMVATION Development Team <info@comvation.com>
 * @package     contrexx
 * @subpackage  coremodule_multisite
 */
class WebsiteRepository extends \Doctrine\ORM\EntityRepository {
    protected $websites = array();
    
    public function findByCreatedDateRange($startTime, $endTime) {
        
    }
    
    public function findByMail($mail) {
        if (empty($mail)) {
            return null;
        }
        foreach ($this->findAll() as $website) {
            if ($website->getOwner()->getEmail() == $mail) {
                return $website;
            }
        }
        return null;
    }
    
    public function findByName($name) {
        if (empty($name)) {
            return null;
        }
        
        $website = $this->findBy(array('name' => $name));
        if (count($website)>0) {
            return $website;
        } else {
            return null;
        }
    }
    
    public function findWebsitesBetween($startTime, $endTime) {
        
    }
    
    public function findOneForSale($productOptions, $saleOptions) {
        
        $baseSubscription  = isset($saleOptions['baseSubscription']) ? $saleOptions['baseSubscription'] : '';
        if ($baseSubscription instanceof \Cx\Modules\Order\Model\Entity\Subscription) {
            $productEntity = $baseSubscription->getProductEntity();
            if ($productEntity instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Website) {
                \Env::get('em')->remove($baseSubscription);
                return $productEntity;
            }
            throw new WebsiteRepositoryException('There is no product entity exists in the base subscription.');
        }
        
        $websiteThemeId = isset($saleOptions['themeId']) ? $saleOptions['themeId'] : null;
        $serviceServerId = isset($saleOptions['serviceServerId']) ? $saleOptions['serviceServerId'] : 0;
        $website = $this->initWebsite($saleOptions['websiteName'], $saleOptions['customer'], $websiteThemeId, $serviceServerId);
        
        \Env::get('em')->persist($website);
        // flush $website to database -> subscription will need the ID of $website
        // to properly work
        \Env::get('em')->flush();
        return $website;
    }
    
    public function findWebsitesByCriteria($criteria = array())
    {
        if (empty($criteria)) {
            return;
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('website')
                ->from('\Cx\Core_Modules\MultiSite\Model\Entity\Website', 'website')
                ->leftJoin('website.owner', 'user');

        $i = 1;
        $isServerWebsiteInCriteria = false;
        foreach ($criteria as $fieldType => $value) {
            if (method_exists($qb->expr(), $fieldType) && is_array($value)) {
                foreach ($value as $condition) {
                    if (preg_match('#^serverWebsite#', $condition[0])) {
                        $isServerWebsiteInCriteria = true;
                    }
                    $condition[1] = isset($condition[1]) && !is_array($condition[1])
                        ? $qb->expr()->literal($condition[1]) : $condition[1];
                    $qb->andWhere(
                        call_user_func(
                            array($qb->expr(), $fieldType),
                            $condition[0], $condition[1]
                        )
                    );
                }
            } else {
                if (preg_match('#^serverWebsite#', $fieldType)) {
                    $isServerWebsiteInCriteria = true;
                }
                $qb->andWhere($fieldType . ' = ?' . $i)->setParameter($i, $value);
            }
            $i++;
        }

        if ($isServerWebsiteInCriteria) {
            $qb->leftJoin('website.serverWebsite', 'serverWebsite');
        }
        return $qb->getQuery()->getResult();
    }
    
    public function findWebsitesBySearchTerms($term) {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('website')
                ->from('\Cx\Core_Modules\MultiSite\Model\Entity\Website', 'website')
                ->leftJoin('website.owner', 'user')
                ->leftJoin('website.domains', 'domain')
                ->where('website.id = :id')->setParameter('id', $term)
                ->orWhere('user.email LIKE ?1')
                ->orWhere('website.name LIKE ?1')
                ->orWhere('website.ftpUser LIKE ?1')
                ->orWhere('domain.name LIKE ?1')
                ->setParameter(1, '%' . $term . '%')
                ->groupBy('website.id');
        
        return $qb->getQuery()->getResult();
    }
    
    /**
     * Initializing the website
     * 
     * @param string $websiteName
     * @param \User $objUser
     * @param integer $websiteThemeId
     * @param integer $serviceServerId
     * @return \Cx\Core_Modules\MultiSite\Model\Entity\Website
     */
    public function initWebsite($websiteName = '', \User $objUser = null, $websiteThemeId = 0, $serviceServerId = 0) {
        global $_ARRAYLANG;
        
        if (empty($websiteName)) {
            return;
        }
        
        $basepath = \Cx\Core\Setting\Controller\Setting::getValue('websitePath','MultiSite');
        $websiteServiceServer = null;
        $websiteServiceServerId = !empty($serviceServerId)
                                  ? $serviceServerId 
                                  : \Cx\Core\Setting\Controller\Setting::getValue('defaultWebsiteServiceServer', 'MultiSite');
        if (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite') == \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_MANAGER) {
            //get default service server
            $websiteServiceServer = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer')
            ->findOneById($websiteServiceServerId);
            
            if (!$websiteServiceServer) {
                \DBG::log(__METHOD__. ' failed!. : This service server ('.$websiteServiceServerId.') doesnot exists.');
                throw new WebsiteRepositoryException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_SERVICE_SERVER']);
            }
        }
        
        $website = new \Cx\Core_Modules\MultiSite\Model\Entity\Website($basepath, $websiteName, $websiteServiceServer, $objUser, false, $websiteThemeId);
        return $website;
    }
    
    /**
     * Find websites by the search term
     * 
     * @param string $term
     * 
     * @return array
     */
    public function findByTerm($term) {
        if (empty($term)) {
            return array();
        }
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb ->select('website')
            ->from('\Cx\Core_Modules\MultiSite\Model\Entity\Website', 'website')
            ->leftJoin('website.domains', 'domain')
            ->where('website.name LIKE ?1')->setParameter(1, '%' . contrexx_raw2db($term) . '%')
            ->orWhere('domain.name LIKE ?2')->setParameter(2, '%' . contrexx_raw2db($term) . '%');
        
        $websites = $qb->getQuery()->getResult();
        return !empty($websites) ? $websites : array();
    }

    /**
     * get the websites by search term and subscription id
     * 
     * @param string  $term
     * @param integer $subscriptionId
     * 
     * @return array
     */
    public function getWebsitesByTermAndSubscription($term, $subscriptionId) {
        
        $where = array();
        if (!empty($term)) {
            $where[] = '(`Website`.`name` LIKE "%' . contrexx_raw2db($term) . '%" '
                        . 'OR `Domain`.`name` LIKE "%' . contrexx_raw2db($term) . '%" '
                        . 'OR `Subscription`.`description` LIKE "%' . contrexx_raw2db($term) . '%")';
        }
        if (!empty($subscriptionId)) {
            $where[] = '`Subscription`.`id` = ' . $subscriptionId;
        }
        $condition = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';

        //Get the ids of both free and paid product.
        $em = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager();
        $componentRepo  = $em->getRepository('Cx\Core\Core\Model\Entity\SystemComponent');
        $component      = $componentRepo->findOneBy(array('name' => 'MultiSite'));
        $freeProductIds = $component->getProductIdsByEntityClass('Website');
        $costProductIds = $component->getProductIdsByEntityClass('WebsiteCollection');

        //create a RSM to get the websites based on the search term and subscription
        $rsm = new \Doctrine\ORM\Query\ResultSetMapping();
        $rsm->addEntityResult('Cx\Core_Modules\MultiSite\Model\Entity\Website', 'Website');
        $rsm->addFieldResult('Website', 'WebsiteId', 'id');
        $rsm->addFieldResult('Website', 'name', 'name');
        $rsm->addFieldResult('Website', 'status', 'status');
        $rsm->addFieldResult('Website', 'domains', 'domains');
        
        $rsm->addJoinedEntityResult('Cx\Core\User\Model\Entity\User', 'User', 'Website', 'owner');
        $rsm->addFieldResult('User', 'UserId', 'id');
        
        $query = 'SELECT `Website`.`id` as WebsiteId, `Website`.`name`, `Website`.`status`, 
                                 `User`.`id` as UserId
                                FROM 
                                    `' . DBPREFIX . 'core_module_multisite_website` As Website
                                LEFT JOIN 
                                    `' . DBPREFIX . 'access_users` As User
                                ON
                                    `User`.`id` = `Website`.`ownerId`
                                LEFT JOIN 
                                    `' . DBPREFIX . 'module_order_subscription` As Subscription
                                ON 
                                    IF(`Website`.`websiteCollectionId` IS NULL, 
                                        `Subscription`.`product_id` IN (' . implode(',', $freeProductIds) . ') 
                                            AND `Subscription`.`product_entity_id` = `Website`.`id`, 
                                        `Subscription`.`product_id` IN (' . implode(',', $costProductIds) . ') 
                                            AND `Subscription`.`product_entity_id` = `Website`.`websiteCollectionId`
                                    ) 
                                LEFT JOIN 
                                    `' . DBPREFIX . 'core_module_multisite_website_domain` As WebsiteDomain
                                ON 
                                    `WebsiteDomain`.`website_id` = `Website`.`id`
                                LEFT JOIN 
                                    `' . DBPREFIX . 'core_module_multisite_domain` As Domain
                                ON 
                                    `Domain`.`id` = `WebsiteDomain`.`domain_id` ' . $condition;
        
        $queryObj  = $em->createNativeQuery($query, $rsm);
        
        return $queryObj->getResult();
    }
}
