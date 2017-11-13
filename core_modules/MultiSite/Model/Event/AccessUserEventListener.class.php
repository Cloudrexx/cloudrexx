<?php

/**
 * AccessUserEventListener

 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      COMVATION Development Team <info@comvation.com>
 * @package     contrexx
 * @subpackage  coremodule_multisite
 */

namespace Cx\Core_Modules\MultiSite\Model\Event;

/**
 * AccessUserEventListenerException
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      COMVATION Development Team <info@comvation.com>
 * @package     contrexx
 * @subpackage  coremodule_multisite
 */
class AccessUserEventListenerException extends \Exception {}

/**
 * AccessUserEventListener
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      COMVATION Development Team <info@comvation.com>
 * @package     contrexx
 * @subpackage  coremodule_multisite
 */
class AccessUserEventListener implements \Cx\Core\Event\Model\Entity\EventListener {
    public function postPersist($eventArgs) {
        \DBG::msg('MultiSite (AccessUserEventListener): postPersist');
        $objUser = $eventArgs->getEntity();
        try {
            \Cx\Core\Setting\Controller\Setting::init('MultiSite', '','FileSystem');
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_WEBSITE:
                    $websiteUserId = \Cx\Core\Setting\Controller\Setting::getValue('websiteUserId','MultiSite');
                    if (empty($websiteUserId)) {
                        //set user's id to websiteUserId
                        $componentRepo    = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager()->getRepository('Cx\Core\Core\Model\Entity\SystemComponent');
                        $component        = $componentRepo->findOneBy(array('name' => 'MultiSite'));
                        $objJsonMultiSite = $component->getController('JsonMultiSite');
                        $objJsonMultiSite->updateWebsiteOwnerId($objUser->getId());
                        //set the user as Administrator
                        $objUser->setAdminStatus(1);
                        $objUser->store();
                    }
                    break;
                default:
                    break;
            }
        } catch (\Exception $e) {
            \DBG::msg($e->getMessage());
        }
    }
    
    /**
     * PrePersist Event
     * 
     * @param type $eventArgs
     * @throws \Cx\Core\Error\Model\Entity\ShinyException
     */
    public function prePersist($eventArgs) {
        \DBG::msg('MultiSite (AccessUserEventListener): prePersist');
        $objUser = $eventArgs->getEntity();
        
        try {
            \Cx\Core\Setting\Controller\Setting::init('MultiSite', '','FileSystem');
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                 case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_MANAGER:
                 case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_HYBRID:
                     $multiSiteAffiliateId = isset($_COOKIE['MultiSiteAffiliateId']) ? $_COOKIE['MultiSiteAffiliateId'] : '';                     
                     if (   !empty($multiSiteAffiliateId)
                         && !\FWUser::getFWUserObject()->objUser->login()
                         && \Cx\Core_Modules\MultiSite\Controller\ComponentController::isValidAffiliateId($multiSiteAffiliateId)
                     ) {
                        $objUser->setProfile(
                            array(
                                \Cx\Core\Setting\Controller\Setting::getValue('affiliateIdReferenceProfileAttributeId','MultiSite') => array(0 => $multiSiteAffiliateId)
                            ),
                            true    
                        );
                     }
                     break;
                 case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_SERVICE:
                    if (!\Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::isIscRequest()) {
// TODO: add language variable
                        throw new \Exception('User management has been disabled as this Contrexx installation is being operated as a MultiSite Service Server.');
                    }
                    break;
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_WEBSITE:
                    //Check Admin Users quota
                    $this->checkQuota($objUser);
                    break;
                default:
                    break;
            }
        } catch (\Exception $e) {
            \DBG::msg($e->getMessage());
            throw new \Cx\Core\Error\Model\Entity\ShinyException($e->getMessage());
        }
    }

    public function preUpdate($eventArgs) {
        global $_ARRAYLANG;
        
        \DBG::msg('MultiSite (AccessUserEventListener): preUpdate');
        $objUser = $eventArgs->getEntity();
        
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_WEBSITE:
                    //Check Admin Users quota
                    $adminUsersList = \Cx\Core_Modules\MultiSite\Controller\ComponentController::getAllAdminUsers();
                    if (!array_key_exists($objUser->getId(), $adminUsersList)) {
                        $this->checkQuota($objUser);
                    }
                    
                    $websiteUserId = \Cx\Core\Setting\Controller\Setting::getValue('websiteUserId','MultiSite');
                    if ($websiteUserId == $objUser->getId() && !\Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::isIscRequest()) {
                        if (!$objUser->isVerified()) {
                            throw new \Exception('Diese Funktion ist noch nicht freigeschalten. Aus Sicherheitsgründen bitten wir Sie, Ihre Anmeldung &uuml;ber den im Willkommens-E-Mail hinterlegten Link zu best&auml;tigen. Anschliessend wird Ihnen diese Funktion zur Verf&uuml;gung stehen. <a href="javascript:window.history.back()">Zur&uuml;ck</a>');
                        }

                        if (\FWUser::getFWUserObject()->objUser->isLoggedIn() && ($objUser->getId() != \FWUser::getFWUserObject()->objUser->getId())) {
                            throw new \Exception('Das Benutzerkonto des Websitebetreibers kann nicht ge&auml;ndert werden. <a href="javascript:window.history.back()">Zur&uuml;ck</a>');
                        }
                        
                        $objWebsiteOwner = \FWUser::getFWUserObject()->objUser->getUser($websiteUserId);
                        $newEmail = $objUser->getEmail();
                        $response = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnMyServiceServer('executeOnManager', array('command' => 'isUniqueEmail', 'params' => array('currentEmail'=> $objWebsiteOwner->getEmail(),'newEmail' => $newEmail)));
                        if ($response && $response->data->status == 'error') {
                            $customerPanelUrl  = \Cx\Core\Routing\Url::fromMagic(ASCMS_PROTOCOL . '://' . $response->data->customerPanelDomain . '/')->toString();
                            $customerPanelLink = '<a class="alert-link" href="'.$customerPanelUrl.'" target="_blank">'.$response->data->customerPanelDomain.'</a>';
                            $mailLink          = '<a class="alert-link" href="mailto:'.$newEmail.'" target="_blank">'.$newEmail.'</a>';
                            throw new \Exception(sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_OWNER_EMAIL_UNIQUE_ERROR'], $mailLink, $customerPanelLink));
                        }
                        
                        $params = self::fetchUserData($objUser);
                        try {
                            $objJsonData = new \Cx\Core\Json\JsonData();
                            $resp = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnMyServiceServer('executeOnManager', array('command' => 'updateUser', 'params' => $params));
                            if ($resp->status == 'error' || $resp->data->status == 'error') {
                                if (isset($resp->log)) {
                                    \DBG::appendLogs(array_map(function($logEntry) {return '(Website: './*$this->getName().*/') '.$logEntry;}, $resp->log));
                                }
                                throw new \Exception('Die Aktualisierung des Benutzerkontos hat leider nicht geklapt. <a href="javascript:window.history.back()">Zur&uuml;ck</a>');
                            }
                        } catch (\Exception $e) {
                            \DBG::msg($e->getMessage());
                        }
// TODO: add language variable
                        //throw new \Exception('Das Benutzerkonto des Websitebetreibers kann nicht ge&auml;ndert werden. <a href="javascript:window.history.back()">Zur&uuml;ck</a>');
                    }
                    break;
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_SERVICE:
                    if (!\Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::isIscRequest()) {
// TODO: add language variable
                        throw new \Exception('User management has been disabled as this Contrexx installation is being operated as a MultiSite Service Server.');
                    }
                    break;
                default:
                    break;
            }
        } catch (\Exception $e) {
            \DBG::msg($e->getMessage());
            throw new \Cx\Core\Error\Model\Entity\ShinyException($e->getMessage());
        }
    }
    
    public function preRemove($eventArgs) {
        \DBG::msg('MultiSite (AccessUserEventListener): preRemove');
        $objUser = $eventArgs->getEntity();
        
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_WEBSITE:
                    $websiteUserId = \Cx\Core\Setting\Controller\Setting::getValue('websiteUserId','MultiSite');
                    if ($websiteUserId == $objUser->getId() && !\Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::isIscRequest()) {
// TODO: add language variable
                        throw new \Exception('Das Benutzerkonto des Websitebetreibers kann nicht ge&auml;ndert werden. <a href="javascript:window.history.back()">Zur&uuml;ck</a>');
                    }
                    break;
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_SERVICE:
                    if (!\Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::isIscRequest()) {
// TODO: add language variable
                        throw new \Exception('User management has been disabled as this Contrexx installation is being operated as a MultiSite Service Server.');
                    }
                    break;
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_MANAGER:
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_HYBRID:
                    $websiteRepository = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
                    $website = $websiteRepository->findWebsitesByCriteria(array('user.id' => $objUser->getId()));
                    if ($website) {
                        throw new \Exception('This user is linked with Websites, cannot able to delete');
                    }
                    
                    if (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite') == \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_MANAGER) {
                        $websiteServiceServers = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer')->findAll();
                        foreach ($websiteServiceServers as $serviceServer) {
                            $resp = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnServiceServer('removeUser', array('userId' => $objUser->getId()), $serviceServer);
                            if (   (isset($resp->status) && $resp->status == 'error')
                                || (isset($resp->data->status) && $resp->data->status == 'error')
                            ) {
                                if (isset($resp->log)) {
                                    \DBG::appendLogs(array_map(function($logEntry) {return '(Service: '.$serviceServer->getLabel().') '.$logEntry;}, $resp->log));
                                }
                                if (isset($resp->message)) {
                                    \DBG::appendLogs(array('(Service: '.$serviceServer->getLabel().') '.$resp->message));
                                }
                                throw new \Exception('Failed to delete this user');
                            }
                        }
                    }
                    break;
                default:
                    break;
            }
        } catch (\Exception $e) {
            \DBG::msg($e->getMessage());
            throw new \Cx\Core\Error\Model\Entity\ShinyException($e->getMessage());
        }
    }
    
    public function postUpdate($eventArgs) {
        \DBG::msg('MultiSite (AccessUserEventListener): postUpdate');
        
        $objUser = $eventArgs->getEntity();
        $params = self::fetchUserData($objUser);
        try {
            $objJsonData = new \Cx\Core\Json\JsonData();
            switch(\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_MANAGER:
                    //Find each associated service servers
                    $webServerRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer');
                    $webSiteRepo   = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
                    $websites      = $webSiteRepo->findWebsitesByCriteria(array('user.id' => $objUser->getId()));
                    
                    if (!isset($websites)) {
                        return;
                    }
                    
                    $affectedWebsiteServiceServerIds = array();
                    foreach ($websites as $website) {
                        if (in_array($website->getWebsiteServiceServerId(), $affectedWebsiteServiceServerIds)) {
                            continue;
                        }
                        $affectedWebsiteServiceServerIds[] = $website->getWebsiteServiceServerId();
                    }
                    foreach ($affectedWebsiteServiceServerIds as $websiteServiceServerId) {
                        $websiteServiceServer   = $webServerRepo->findOneBy(array('id' => $websiteServiceServerId));
                    
                        if ($websiteServiceServer) {
                            \DBG::msg('Going to update user '.$objUser->getId().' on WebsiteServiceServer '.$websiteServiceServer->getLabel());
                            \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnServiceServer('updateUser', $params, $websiteServiceServer);
                        }
                    }
                    break;
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_HYBRID:
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_SERVICE:
                    //find User's Website
                    $webRepo   = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
                    $websites  = $webRepo->findWebsitesByCriteria(array('user.id' => $objUser->getId()));
                    foreach ($websites as $website) {
                        \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnWebsite('updateUser', $params, $website);
                    }
                    break;
                default:
                    break;
            }
            
        } catch (\Exception $e) {
            \DBG::msg($e->getMessage());
        }
    }

    public static function fetchUserData($objUser) {
        if ($objUser instanceof \Cx\Core\User\Model\Entity\User) {
            $objFWUser = \FWUser::getFWUserObject();
            $objUser   = $objFWUser->objUser->getUser($objUser->getId());
        }
        //get user's profile details
        $objUser->objAttribute->first();
        $arrUserDetails = array();
        while (!$objUser->objAttribute->EOF) {
            $arrUserDetails[$objUser->objAttribute->getId()][] = $objUser->getProfileAttribute($objUser->objAttribute->getId());
            $objUser->objAttribute->next();
        }
        //get user's other details
        $params = array(
            'multisite_user_profile_attribute'          => $arrUserDetails,
            'multisite_user_account_email'              => $objUser->getEmail(),
            'multisite_user_account_frontend_language'  => $objUser->getFrontendLanguage(),
            'multisite_user_account_backend_language'   => $objUser->getBackendLanguage(),
            'multisite_user_account_email_access'       => $objUser->getEmailAccess(),
            'multisite_user_account_profile_access'     => $objUser->getProfileAccess(),
            'multisite_user_account_verified'           => $objUser->isVerified(),
            'multisite_user_account_restore_key'        => $objUser->getRestoreKey(),
            'multisite_user_account_restore_key_time'   => $objUser->getRestoreKeyTime(),
            'multisite_user_md5_password'               => $objUser->getHashedPassword(),
        );

        $arrSettings = \User_Setting::getSettings();
        if ($arrSettings['use_usernames']['status']) {
            $params['multisite_user_account_username'] = $objUser->getUsername();
        }

        if ($objUser->getId()) {
            $params['userId'] = $objUser->getId();
        }
        return $params;
    }
    
    /**
     * Check the Admin Users Quota
     * 
     * @param \User $objUser
     * @throws \Cx\Core\Error\Model\Entity\ShinyException
     */
    public function checkQuota(\User $objUser) {
        global $objInit, $_ARRAYLANG;
        
        $langData = $objInit->loadLanguageData('MultiSite');
        $_ARRAYLANG = array_merge($_ARRAYLANG, $langData);
                
        $userGroupIds     = $objUser->getAssociatedGroupIds();
        $backendGroupIds  = \Cx\Core_Modules\MultiSite\Controller\ComponentController::getBackendGroupIds();
        $backendGroupUser = count(array_intersect($backendGroupIds, $userGroupIds));
        if ($objUser->getAdminStatus() || $backendGroupUser)  {
            if (!$this->checkAdminUsersQuota()) {
                $options = \Cx\Core_Modules\MultiSite\Controller\ComponentController::getModuleAdditionalDataByType('Access');
                $errMsg = sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MAXIMUM_ADMINS_REACHED'], $options['AdminUser']);
                if (!\Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::isIscRequest()) {
                    throw new \Cx\Core\Error\Model\Entity\ShinyException($errMsg . ' <a href="index.php?cmd=Access">' . $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_GO_TO_OVERVIEW'] . '</a>');
                }
                throw new \Cx\Core\Error\Model\Entity\ShinyException($errMsg);
            }
        }
        
        return true;
    }

    /**
     * Check the Admin Users Quota
     * 
     * @return boolean true | false
     */
    public function checkAdminUsersQuota() {
        $options = \Cx\Core_Modules\MultiSite\Controller\ComponentController::getModuleAdditionalDataByType('Access');
        if (!empty($options['AdminUser']) && $options['AdminUser'] > 0) {
            $adminUsers = \Cx\Core_Modules\MultiSite\Controller\ComponentController::getAllAdminUsers();
            $adminUsersCount = count($adminUsers);
            if ($adminUsersCount >= $options['AdminUser']) {
                return false;
            }
        }
        return true;
    }
    
    public function onEvent($eventName, array $eventArgs) {        
        $this->$eventName(current($eventArgs));
    }
}
