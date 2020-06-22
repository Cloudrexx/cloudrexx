<?php
/**
 * Class CronController
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Project Team SS4U <info@comvation.com>
 * @package     contrexx
 * @subpackage  coremodule_multisite
 */

namespace Cx\Core_Modules\MultiSite\Controller;

/**
 * Class CronController
 *
 * The main Cron component
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Project Team SS4U <info@comvation.com>
 * @package     contrexx
 * @subpackage  coremodule_multisite
 */
class CronController extends \Cx\Core\Core\Model\Entity\Controller {
    
    /**
     * Constructor
     */
    public function __construct(\Cx\Core\Core\Model\Entity\SystemComponentController $systemComponentController, \Cx\Core\Core\Controller\Cx $cx)
    {
        parent::__construct($systemComponentController, $cx);
    }
    
    /**
     * Send the Notification email to Website CRM Contact
     */
    public function sendNotificationMails() {
        $em              = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager();
        $cronMailRepo    = $em->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\CronMail');
        $cronMailLogRepo = $em->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\CronMailLog');
        $contact         = new \Cx\Modules\Crm\Model\Entity\CrmContact();
        $crmLibObj       = new \Cx\Modules\Crm\Controller\CrmLibrary('Crm');
        
        //Get all the cronMails
        $cronMails       = $cronMailRepo->findBy(array('active' => true));
        if (!$cronMails) {
            return;
        }
        
        foreach ($cronMails as $cronMail) {
            $cronMailCriterias = $cronMail->getCronMailCriterias();
            //if there is no CronMailCriterias exists, proceed next CronMail
            if (!count($cronMailCriterias)) {
                continue;
            }
            $criterias = array();
            foreach ($cronMailCriterias as $cronMailCriteria) {
                list($tableAlias, $attribute) = explode('.', $cronMailCriteria->getAttribute());
                if (!isset($criterias[$tableAlias])) {
                    $criterias[$tableAlias] = array();
                }
                $criterias[$tableAlias][$attribute] = $cronMailCriteria->getCriteria();
            }
            
            //Get all the websites, owners and Contacts based on the criteria
            $results = $this->getMailDetailsByCriteria($criterias);
            if (empty($results)) {
                continue;
            }
            foreach ($results as $result) {
                $website   = null;
                $contactId = null;
                switch(true) {
                    case ($result instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Website):
                        $website   = $result;
                        $contactId = $crmLibObj->getCrmUserIdByUserId($result->getOwner()->getId());
                        break;
                    case ($result instanceof \Cx\Core\User\Model\Entity\User):
                        $website   = $em->getRepository('\Cx\Core_Modules\MultiSite\Model\Entity\Website')
                                    ->findWebsitesByCriteria(array('user.id' => $result->getId()));
                        $contactId = $crmLibObj->getCrmUserIdByUserId($result->getId());
                        break;
                    case (!empty($result['userId'])):
                        $website   = $em->getRepository('\Cx\Core_Modules\MultiSite\Model\Entity\Website')
                                      ->findWebsitesByCriteria(array('user.id' => $result['userId']));
                    default:
                        $contactId = $result['id'];
                        break;
                }
                
                if (empty($contactId) || !$contact->load($contactId)) {
                    continue;
                }
                
                if (!\FWValidator::isEmpty($contact->emailDelivery)) {
                    \DBG::msg(__METHOD__.": matched CronMail (ID={$cronMail->getId()}): User=".$contact->customerName . ' ' . $contact->family_name .(($website instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Website) ? '; Website='.$website->getName() : ''));
                    $this->sendMail($cronMail, $contact, $website, $cronMailLogRepo);
                }
            }
        }
    }
    
    /**
     * Send Cron mail to website User
     * 
     * @param \Cx\Core_Modules\MultiSite\Model\Entity\CronMail $cronMail        CronMail object
     * @param \Cx\Modules\Crm\Model\Entity\CrmContact          $objCrmUser      Crm contact object
     * @param mixed                                            $websiteObj      Website or websiteCollection object
     * @param object                                           $cronMailLogRepo cronMailLog repository object
     * 
     * @return boolean 
     */
    public function sendMail(
        \Cx\Core_Modules\MultiSite\Model\Entity\CronMail $cronMail, 
        \Cx\Modules\Crm\Model\Entity\CrmContact $objCrmUser, 
        $websiteObj,
        $cronMailLogRepo
    ) {
        //check already mail send to that owner
        $logCriteria = array('id' => $cronMail->getId(), 'contactId' => $objCrmUser->id);
        if ($websiteObj instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Website) {
            $logCriteria['websiteId'] = $websiteObj->getId();
        }
        $cronMailLogEntity = $cronMailLogRepo->getOneCronMailLogByCriteria($logCriteria);
        
        //If the owner already have a log and status success, proceed next
        if ($cronMailLogEntity && $cronMailLogEntity->getSuccess()) {
            return;
        }
        
        //create a new log
        if (!$cronMailLogEntity) {
            $cronMailLogEntity = new \Cx\Core_Modules\MultiSite\Model\Entity\CronMailLog();
            $cronMailLogEntity->setContactId($objCrmUser->id);
            if ($websiteObj instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Website) {
                $cronMailLogEntity->setWebsiteId($websiteObj->getId());
            }
            $cronMailLogEntity->setSuccess(false);
            $cronMailLogEntity->setToken(\Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::generateSecretKey());
            $cronMail->addCronMailLog($cronMailLogEntity);
            \Env::get('em')->persist($cronMailLogEntity);
            \Env::get('em')->flush();
        }

        $unSubscribeUrl = ComponentController::getApiProtocol() .
                           \Cx\Core\Setting\Controller\Setting::getValue('customerPanelDomain','MultiSite') .
                           '/' . \Cx\Core\Routing\Url::fromModuleAndCmd('MultiSite', 'NotificationUnsubscribe', null, array('i'=> $cronMailLogEntity->getId(),'t'=> $cronMailLogEntity->getToken()))->getPath();
            
        if ($websiteObj instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Website) {
            $search  = array(
                            '[[WEBSITE_CREATION_DATE]]',
                            '[[WEBSITE_NAME]]',
                            '[[WEBSITE_MAIL]]',
                            '[[CUSTOMER_MAIL]]',
                            '[[WEBSITE_DOMAIN]]',
                            '[[CUSTOMER_NAME]]',
                            '[[UNSUBSCRIBE]]'
                        );
            $replace = array(
                            $websiteObj->getCreationDate()->format(ASCMS_DATE_FORMAT_INTERNATIONAL_DATETIME),
                            $websiteObj->getName(),
                            $objCrmUser->email,
                            $objCrmUser->email,
                            $websiteObj->getName() . '.' . \Cx\Core\Setting\Controller\Setting::getValue('multiSiteDomain','MultiSite'),
                            $objCrmUser->customerName . ' ' . $objCrmUser->family_name,
                            $unSubscribeUrl
                        );
             $substitution = array();   
        } else {
            $websiteDetails = array();  
            if (!empty($websiteObj) && is_array($websiteObj)) {
                foreach ($websiteObj as $website) {
                    $websiteDetails[] = array(
                        'WEBSITE_CREATION_DATE' => $website->getCreationDate()->format(ASCMS_DATE_FORMAT_INTERNATIONAL_DATETIME),
                        'WEBSITE_NAME'          => $website->getName(),
                        'WEBSITE_MAIL'          => $objCrmUser->email,
                        'WEBSITE_DOMAIN'        => $website->getName() . '.' . \Cx\Core\Setting\Controller\Setting::getValue('multiSiteDomain','MultiSite')
                    );
                }
            }
            
            $search  = array(
                            '[[CUSTOMER_MAIL]]',
                            '[[CUSTOMER_NAME]]',
                            '[[UNSUBSCRIBE]]'
                        );
            $replace = array(
                            $objCrmUser->email,
                            $objCrmUser->customerName . ' ' . $objCrmUser->family_name,
                            $unSubscribeUrl
                        );
            $substitution = array('WEBSITE_LIST' => array(0 => array('WEBSITE_DETAIL' => $websiteDetails)));
        }
        
        //send mail to website owner
        $arrValues = array(
                        'section' => 'MultiSite',
                        'lang_id' => 1,
                        'key'     => $cronMail->getMailTemplateKey(),
                        'to'      => $objCrmUser->email,
                        'search'  => $search,
                        'replace' => $replace,
                        'substitution' => $substitution);
        
        \DBG::msg(__METHOD__." ID={$cronMail->getId()}) / User=".$objCrmUser->email.(($websiteObj instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Website) ? '; Website='.$websiteObj->getName() : ''));
        $mailStatus = \Cx\Core\MailTemplate\Controller\MailTemplate::send($arrValues);
        
        //If the owner already have a log and status failed, update the log
        $cronMailLogEntity->setSentDate(new \DateTime());
        $cronMailLogEntity->setSuccess($mailStatus ? true : false);
        \Env::get('em')->flush();
              
        return true;
    }
    
    /**
     * Get the date filter for the query
     * 
     * @param string  $fieldName      filter field name
     * @param string  $filterCriteria filter criteria
     * @param boolean $useTimeStamp   use datetime or timestamp in the query
     * 
     * @return string $condition
     */
    public function getDateFilter($fieldName, $filterCriteria, $useTimeStamp = false)
    {
        if (empty($fieldName) || empty($filterCriteria)) {
            return;
        }
        
        $criteria = preg_replace('#^\+#i', '-', $filterCriteria);  // +n days = (date - n days)
        $format   = preg_replace('/\b(ON|BEFORE|AFTER) \b/i', '', $criteria);
        
        $condition = '';
        $startDate = new \DateTime($format);
        $startDate->setTime(0, 0, 0);
        
        switch (true) {
            case preg_match('#^ON\ #i', $criteria):
            case preg_match('#^\-#i', $criteria):
                $condition = $fieldName . ' >= "' . self::parseTimeForFilter($startDate, $useTimeStamp) . '" ';
                $startDate->setTime(23, 59, 59);
                $condition .= ' AND ' . $fieldName . ' <= "' . self::parseTimeForFilter($startDate, $useTimeStamp) . '" ';
                break;
            case preg_match('#^BEFORE\ #i', $criteria):
                $condition = $fieldName . ' < "' . self::parseTimeForFilter($startDate, $useTimeStamp) . '" ';
                break;
            case preg_match('#^AFTER\ #i', $criteria):
                $startDate->setTime(23, 59, 59);
                $condition = $fieldName . ' > "' . self::parseTimeForFilter($startDate, $useTimeStamp) . '" ';
                break;
        }
        
        return $condition;
    }
    
    /**
     * Get the filter for the query
     * 
     * @param array $cronMailCriterias CronMail filter conditions
     * 
     * return array $conditions
     */
    public function getFilter($cronMailCriterias) 
    {
        $conditions     = array();
        $userDateField  = array('User.regdate', 'User.last_auth', 'User.last_activity');
        $dateFieldTypes = array(\Doctrine\DBAL\Types\Type::DATETIME, \Doctrine\DBAL\Types\Type::DATE);
        $classes        = array('Subscription' => 'Cx\Modules\Order\Model\Entity\Subscription',
                               'Website' => 'Cx\Core_Modules\MultiSite\Model\Entity\Website', 
                               'User'    => 'Cx\Core\User\Model\Entity\User');
        $em = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager();
        foreach ($cronMailCriterias as $alias => $criterias) {
            $classMetaData = $em->getClassMetadata($classes[$alias]);
            foreach ($criterias as $fieldName => $criteria) {
                $attribute = $classMetaData->getColumnName($fieldName);
                $fieldType = $classMetaData->getTypeOfColumn($attribute);
                $formattedCriteria  = $alias . '.' . $attribute;
                //for date field
                $isUserDateField = in_array($formattedCriteria, $userDateField);
                if ($isUserDateField || in_array($fieldType, $dateFieldTypes)) {
                    $conditions[] = $this->getDateFilter($formattedCriteria, $criteria, $isUserDateField);
                } else {
                    if (strpos($criteria, '!') === 0) {
                        $conditions[] = $formattedCriteria . ' != "' . substr($criteria, 1) . '" ';
                    } elseif (preg_match('/^(NOT\s+)?IN\s*\(.*\)$/i', $criteria)) {
                        $conditions[] = $formattedCriteria . ' ' . $criteria;
                    } else {
                        $conditions[] = $formattedCriteria . ' = "' . $criteria . '" ';
                    }
                }
            }
        }
        return $conditions;
    }

    /**
     * Get the contact filter for the query
     * 
     * @param array $criterias filter conditions
     * 
     * @return boolean|array
     */
    protected function getFilterForContact($criterias)
    {
        if (empty($criterias)) {
            return array();
        }
        
        $conditions = array();
        foreach ($criterias as $fieldName => $criteria) {
            if ($fieldName == 'membership') {
                $conditions[] = '(SELECT 1 FROM `'. DBPREFIX .'module_crm_customer_membership` as Membership WHERE `Membership`.`contact_id` = `Contact`.`id` AND `Membership`.`membership_id` = ' . $criteria . ' LIMIT 1)';
            } else {
                $conditions[] = $fieldName . ' = "'. $criteria .'"';
            }
        }
        
        return $conditions;
    }
    
    /**
     * get the websites or owners or contact by criteria
     * 
     * @param array $cronMailCriterias filter conditions
     * 
     * @return array $objResult resulting array
     */
    public function getMailDetailsByCriteria($cronMailCriterias)
    {
        try {
            $em = $this->cx->getDb()->getEntityManager();
            if (isset($cronMailCriterias['Website']) || isset($cronMailCriterias['Subscription'])) {
                $componentRepo  = $em->getRepository('Cx\Core\Core\Model\Entity\SystemComponent');
                $component      = $componentRepo->findOneBy(array('name' => 'MultiSite'));
                $freeProductIds = $component->getProductIdsByEntityClass('Website');
                $costProductIds = $component->getProductIdsByEntityClass('WebsiteCollection');
                //NativeQuery for Website and Subscription Criteria
                $rsm   = self::getResultSetMapping(
                            array(
                                'Cx\Core_Modules\MultiSite\Model\Entity\Website' => '',
                                'Cx\Core\User\Model\Entity\User' => 'Website.owner'
                            ),
                            array(
                                'Website' => array('id', 'name', 'creationDate'),
                                'User'    => array('id', 'email')
                            )
                        );
                //we must set the alias name for all the select fields. Alias Format like as Table Alias name + Field name in CC Format
                $query = 'SELECT `Website`.`id` as WebsiteId, `Website`.`name` as WebsiteName, 
                                 `Website`.`creationDate` as WebsiteCreationDate, 
                                 `User`.`id` as UserId, `User`.`email` as UserEmail
                                FROM 
                                    `' . DBPREFIX . 'core_module_multisite_website` As Website
                                LEFT JOIN 
                                    `' . DBPREFIX . 'access_users` As User
                                ON
                                    `User`.`id` = `Website`.`ownerId`
                                LEFT JOIN
                                    `' . DBPREFIX . 'module_crm_contacts` As Contact
                                ON
                                    `Contact`.`user_account` = `User`.`id`
                                LEFT JOIN 
                                    `' . DBPREFIX . 'module_order_subscription` As Subscription
                                ON 
                                    IF(`Website`.`websiteCollectionId` IS NULL, 
                                       `Subscription`.`product_id` IN (' . implode(',', $freeProductIds) . ') 
                                            AND `Subscription`.`product_entity_id` = `Website`.`id`, 
                                        `Subscription`.`product_id` IN (' . implode(',', $costProductIds) . ') 
                                            AND `Subscription`.`product_entity_id` = `Website`.`websiteCollectionId`
                                    )';
            } elseif (isset($cronMailCriterias['User'])) {
                //NativeQuery for User Criteria
                $rsm   = self::getResultSetMapping(
                            array('Cx\Core\User\Model\Entity\User' => ''),
                            array('User' => array('id', 'email'))
                        );
                $query = 'SELECT `User`.`id` as UserId, 
                                 `User`.`email` as UserEmail
                                FROM `' . DBPREFIX . 'access_users` as User
                                LEFT JOIN
                                    `' . DBPREFIX . 'module_crm_contacts` As Contact
                                ON
                                    `Contact`.`user_account` = `User`.`id`';
            } elseif (isset($cronMailCriterias['Contact'])) {
                return $this->getContactCrmUsersByCriteria($cronMailCriterias['Contact']);
            }

            //If the Contact criteria is exists, add it into $conditions
            $conditionsForContact = array();
            if (isset($cronMailCriterias['Contact'])) {
                $conditionsForContact = $this->getFilterForContact($cronMailCriterias['Contact']);
                unset($cronMailCriterias['Contact']);
            }
            
            //add criteria of Website, User, Subscription
            $conditionsForOthers = $this->getFilter($cronMailCriterias);
            $conditions = array_merge($conditionsForContact, $conditionsForOthers);
            $query .= !empty($conditions) ? ' WHERE ' . implode(' AND ', array_filter($conditions)) : '';
            
            $queryObj  = $em->createNativeQuery($query, $rsm);
            $objResult = $queryObj->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            \DBG::dump('CronController (getWebsiteOwnersByCriteria) : Failed to get the website owners by criteria' . $e->getMessage());
            $objResult = array();
        }
        
        return $objResult;
    }
    
    /**
     * Get the Contact Crm users by criteria
     * 
     * @param array $criterias filter conditions
     * 
     * @return boolean|array
     */
    protected function getContactCrmUsersByCriteria($criterias) {
        global $objDatabase;

        $query = 'SELECT `Contact`.`id`, '
                      . '`Contact`.`user_account` as userId '
                      . 'FROM `' . DBPREFIX . 'module_crm_contacts` as Contact';
        
        $conditions   = $this->getFilterForContact($criterias);
        //Fetch the CRM persons alone
        $conditions[] = 'Contact.contact_type = 2';
        $query .= !empty($conditions) ? ' WHERE ' . implode(' AND ', array_filter($conditions)) : '';
        
        $objCrmUser = $objDatabase->Execute($query);
        if ($objCrmUser && $objCrmUser->RecordCount() > 0) {
            $crmUsers = array();
            while (!$objCrmUser->EOF) {
                $crmUsers[$objCrmUser->fields['id']] = array(
                    'id'        => $objCrmUser->fields['id'],
                    'userId'    => $objCrmUser->fields['userId'],
                );
                $objCrmUser->MoveNext();
            }
            return $crmUsers;
        }
        return false;
    }
    
    /**
     * Get the result set mapping object
     * 
     * @param array  $entityClasses  array key as alias ans value as entity classe namespace
     * @param array  $requiredFields required fields list
     * 
     * @return \Doctrine\ORM\Query\ResultSetMapping
     */
    private static function getResultSetMapping($entityClasses = array(), $requiredFields = array())
    {
        if (empty($entityClasses)) {
            return;
        }
        
        $rsm = new \Doctrine\ORM\Query\ResultSetMapping();
        $first = true;
        foreach ($entityClasses as $class => $options) {
            $splitClass = explode('\\', $class);
            $alias      = end($splitClass);
            if ($first) {
                $rsm->addEntityResult($class, $alias);
            } else {
                list($parentAlias, $relationField) = explode('.', $options);
                $rsm->addJoinedEntityResult($class, $alias, $parentAlias, $relationField);
            }
            foreach ($requiredFields[$alias] as $field) {
                $rsm->addFieldResult($alias, $alias.ucfirst($field), $field);
            }
            $first = false;
        }
        
        return $rsm;
    }
    
    /**
     * Get the timestamp or date value based on the date time object
     * 
     * @param \DateTime $date      datetime object
     * @param boolean   $timeStamp return value should be a timestamp or not
     * 
     * @return string
     */
    public static function parseTimeForFilter(\DateTime $date, $timeStamp = false) {
        return $timeStamp ? $date->getTimestamp() : $date->format('Y-m-d H:i:s');
    }
}
