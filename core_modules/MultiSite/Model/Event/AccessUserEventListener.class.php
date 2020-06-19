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
        // this method is used to propagate changes from the website to
        // the manager/customer-panel
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
                        // only fetch user-data in case there had been any changes
                        $params = self::fetchUserData($objUser, true);
                        if (
                            !empty($params) &&
                            !$objUser->isVerified()
                        ) {
// TODO: add language variable
                            throw new \Exception('Diese Funktion ist noch nicht freigeschalten. Aus Sicherheitsgründen bitten wir Sie, Ihre Anmeldung &uuml;ber den im Willkommens-E-Mail hinterlegten Link zu best&auml;tigen. Anschliessend wird Ihnen diese Funktion zur Verf&uuml;gung stehen. <a href="javascript:window.history.back()">Zur&uuml;ck</a>');
                        }

                        if (
                            \FWUser::getFWUserObject()->objUser->isLoggedIn() &&
                            $objUser->getId() != \FWUser::getFWUserObject()->objUser->getId()
                        ) {
// TODO: add language variable
                            throw new \Exception('Das Benutzerkonto des Websitebetreibers kann nicht ge&auml;ndert werden. <a href="javascript:window.history.back()">Zur&uuml;ck</a>');
                        }
                        
                        // this check must be done after the two verifications
                        // above, to ensure that the thrown exceptions above
                        // will prevent the execution of any code that follows
                        // the store event of the user afterwards
                        if (empty($params)) {
                            break;
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

                        try {
                            $objJsonData = new \Cx\Core\Json\JsonData();
                            $resp = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnMyServiceServer('executeOnManager', array('command' => 'updateUser', 'params' => $params));
                            \DBG::dump($resp);
                            if ($resp->status == 'error' || $resp->data->status == 'error') {
                                if (isset($resp->log)) {
                                    \DBG::appendLogs(array_map(function($logEntry) {return '(Website: './*$this->getName().*/') '.$logEntry;}, $resp->log));
                                }
// TODO: add language variable
                                throw new \Exception('Die Aktualisierung des Benutzerkontos hat leider nicht geklappt. <a href="javascript:window.history.back()">Zur&uuml;ck</a>');
                            }
                        } catch (\Exception $e) {
                            \DBG::msg($e->getMessage());
// TODO: add language variable
                            throw new \Exception('Die Aktualisierung des Benutzerkontos hat leider nicht geklappt. <a href="javascript:window.history.back()">Zur&uuml;ck</a>');
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
        // this event is used to propagate changes made on the
        // manager/customer-panel to the associated websites
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
                            \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnServiceServer('updateUser', $params, $websiteServiceServer, array(), true);
                        }
                    }
                    break;
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_HYBRID:
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_SERVICE:
                    // important: we must unset the userId argument
                    // as it can otherwise cause an other updateUser request on
                    // the service server.
                    // this can happen if the $webiste to be updated is
                    // currently disabled, in such case, the updateUser request
                    // is redirected to the service server. The latter will
                    // regurarily parse the updateUser request which will
                    // again trigger the updateUser request on all websites.
                    // At the end, this causes an infinite updateUser loop
                    $params['userId'] = 0;

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

    /**
     * Fetch data of an owner user in a pre-processed format,
     * so that it can be used for passing to the JsonMultiSite::updateUser()
     * API call.
     *
     * @param   \Cx\Core\User\Model\Entity\User|\User   $objUser The user to
     *                      fetch the data from.
     * @param   boolean $onlyOnChange   Whether to data shall only be returned
     *                      if there had been a modification on the user.
     * @return  array       Data of supplied user $objUser as array.
     *                      If $onlyOneChange is set to TRUE, then an empty
     *                      array is returned, in case the user $objUser
     *                      has not been altered in the current event.
     */
    public static function fetchUserData($objUser, $onlyOnChange = false) {
        if ($objUser instanceof \Cx\Core\User\Model\Entity\User) {
            // important: we do loose any local changes on $objUser here!
            $objFWUser = \FWUser::getFWUserObject();
            $objUser   = $objFWUser->objUser->getUser($objUser->getId());
        }

        \DBG::msg(__METHOD__. ': only on change: '.$onlyOnChange);
        // check if the profile has been modified,
        // if not, then the process shall be aborted
        //
        // note: additionally, we have to check if we're in the
        // process of synchronizing the user's password (
        // User::getHashedPassword()). If so, then we shall abort
        // the process as well, as otherwise we would end up in an
        // infinite loop
        if (
            $onlyOnChange &&
            empty($objUser->getHashedPassword())
        ) {
            \DBG::msg(__METHOD__. ': only update on diff');
            $originalUser = \FWUser::getFWUserObject()->objUser->getUser($objUser->getId(), true);
            $originalUserData = $originalUser->toArray();

            // fetch potentially modified user data
            $userData = $objUser->toArray();

            // clear last-activity of user profiles as this will
            // always be different
            $originalUserData['last_activity'] = null;
            $userData['last_activity'] = null;

            // clear last-auth of user profiles as this will
            // always be different
            $originalUserData['last_auth'] = null;
            $userData['last_auth'] = null;

            // ignore language changes,
            // as the selected language is specific to each website
            $originalUserData['frontend_lang_id'] = $userData['frontend_lang_id'];
            $originalUserData['backend_lang_id'] = $userData['backend_lang_id'];

            // ignore custom profile attributes
            $objUser->objAttribute->first();
            while (!$objUser->objAttribute->EOF) {
                // diff core attributes
                if ($objUser->objAttribute->isCoreAttribute()) {
                    $objUser->objAttribute->next();
                    continue;
                }
                // drop custom profile attribute data
                if (isset($originalUserData['profile'][$objUser->objAttribute->getId()])) {
                    unset($originalUserData['profile'][$objUser->objAttribute->getId()]);
                }
                if (isset($userData['profile'][$objUser->objAttribute->getId()])) {
                    unset($userData['profile'][$objUser->objAttribute->getId()]);
                }
                $objUser->objAttribute->next();
            }

            // check if user has been modified
            if ($userData == $originalUserData) {
                \DBG::msg(__METHOD__. ': no diff');
                return array();
            }

            \DBG::msg(__METHOD__. ': has diff');
            \DBG::log('originalUserData:');
            \DBG::dump($originalUserData);
            \DBG::log('new userData:');
            \DBG::dump($userData);
        }

        // Replace $objUser by a multisite user object.
        // This is required to have the method $objUser->getHashedPassword()
        // always return the hashed password of the user account.
        if (
            \Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite') ==
            \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_WEBSITE &&
            \Cx\Core\Setting\Controller\Setting::getValue('websiteState','MultiSite') ==
            \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_ONLINE
        ) {
            $user = new \Cx\Core_Modules\MultiSite\Model\Entity\User($objUser);
        } else {
            $user = new \Cx\Core_Modules\MultiSite\Model\Entity\User();
        }
        \DBG::msg(__METHOD__ . ': user is verified: '.$user->isVerified());
        $objUser = $user->getUser($objUser->getId());

        //get user's profile details
        $objUser->objAttribute->first();
        $arrUserDetails = array();
        while (!$objUser->objAttribute->EOF) {
            // do not sync custom attributes
            if (!$objUser->objAttribute->isCoreAttribute()) {
                $objUser->objAttribute->next();
                continue;
            }
            // do not sync title attribute, as its value is
            // customizable and different on every website
            if ($objUser->objAttribute->getId() == 'title') {
                $objUser->objAttribute->next();
                continue;
            }
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

        // fix birthday (convert timestamp into date format)
        if (!empty($params['multisite_user_profile_attribute']['birthday'][0])) {
            $birthday = date(ASCMS_DATE_FORMAT_DATE, $params['multisite_user_profile_attribute']['birthday'][0]);
            $params['multisite_user_profile_attribute']['birthday'][0] = $birthday;
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
