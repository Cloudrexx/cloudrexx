<?php

/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2015
 * 
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Cloudrexx" is a registered trademark of Cloudrexx AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */
 
/**
* User Management
* @copyright    CLOUDREXX CMS - CLOUDREXX AG
* @author       CLOUDREXX Development Team <info@cloudrexx.com>
* @package      cloudrexx
* @subpackage   coremodule_access
* @version      1.0.0
*/

namespace Cx\Core_Modules\Access\Controller;

/**
* Frontend for the user management
* @copyright    CLOUDREXX CMS - CLOUDREXX AG
* @author       CLOUDREXX Development Team <info@cloudrexx.com>
* @package      cloudrexx
* @subpackage   coremodule_access
* @version      1.0.0
*/
class Access extends \Cx\Core_Modules\Access\Controller\AccessLib
{
    private $arrStatusMsg = array('ok' => array(), 'error' => array());

    public function __construct($pageContent)
    {
        parent::__construct();

        $this->_objTpl = new \Cx\Core\Html\Sigma('.');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);
        $this->_objTpl->setTemplate($pageContent);
    }

    public function getPage(&$metaPageTitle, &$pageTitle)
    {
        $cmd = isset($_REQUEST['cmd']) ? explode('_', $_REQUEST['cmd']) : array(0 => null);
        $groupId = isset($cmd[1]) ? intval($cmd[1]) : null;

        // add whole component's language data to every application page of component
        $this->_objTpl->setVariable(\Env::get('init')->getComponentSpecificLanguageData('Access'));
        \Cx\Lib\SocialLogin::parseSociallogin($this->_objTpl, 'access_');
        \Cx\Core\Csrf\Controller\Csrf::add_code();
        switch ($cmd[0]) {
            case 'signup':
                $this->signUp();
                break;

            case 'settings':
                $this->settings();
                break;

            case 'members':
                $this->members($groupId);
                break;

            case 'user':
                $this->user($metaPageTitle, $pageTitle);
                break;

            default:
                $this->dashboard();
                break;
        }

        return $this->_objTpl->get();
    }

    public function dashboard()
    {

    }

    private function user(&$metaPageTitle, &$pageTitle)
    {
        global $_CONFIG;

        $objFWUser = \FWUser::getFWUserObject();
        $objUser = $objFWUser->objUser->getUser(!empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0);
        if ($objUser) {

            if ($objUser->getProfileAccess() != 'everyone') {
                if (!$objFWUser->objUser->login()) {
                    \Cx\Core\Csrf\Controller\Csrf::header('Location: '.CONTREXX_DIRECTORY_INDEX.'?section=Login&redirect='.base64_encode(ASCMS_PROTOCOL.'://'.$_CONFIG['domainUrl'].CONTREXX_SCRIPT_PATH.'?section=Access&cmd=user&id='.$objUser->getId()));
                    exit;
                }

                if ($objUser->getId() != $objFWUser->objUser->getId()
                    && $objUser->getProfileAccess() == 'nobody'
                    && !$objFWUser->objUser->getAdminStatus()
                ) {
                    \Cx\Core\Csrf\Controller\Csrf::header('Location: '.CONTREXX_DIRECTORY_INDEX.'?section=Login&cmd=noaccess&redirect='.base64_encode(ASCMS_PROTOCOL.'://'.$_CONFIG['domainUrl'].CONTREXX_SCRIPT_PATH.'?section=Access&cmd=user&id='.$objUser->getId()));
                    exit;
                }
            }

            $metaPageTitle = \FWUser::getParsedUserTitle($objUser);
            $pageTitle = contrexx_raw2xhtml(\FWUser::getParsedUserTitle($objUser));
            $this->_objTpl->setGlobalVariable(array(
                'ACCESS_USER_ID'            => $objUser->getId(),
                'ACCESS_USER_USERNAME'      => contrexx_raw2xhtml($objUser->getUsername()),
                'ACCESS_USER_PRIMARY_GROUP' => contrexx_raw2xhtml($objUser->getPrimaryGroupName()),
            ));

            if ($objUser->getEmailAccess() == 'everyone' ||
                   $objFWUser->objUser->login()
                && ($objUser->getId() == $objFWUser->objUser->getId() ||
                    $objUser->getEmailAccess() == 'members_only' ||
                    $objFWUser->objUser->getAdminStatus())
            ) {
                $this->parseAccountAttribute($objUser, 'email');
            } elseif ($this->_objTpl->blockExists('access_user_email')) {
                $this->_objTpl->hideBlock('access_user_email');
            }

            $nr = 0;
            while (!$objUser->objAttribute->EOF) {
                $objAttribute = $objUser->objAttribute->getById($objUser->objAttribute->getId());
                $this->parseAttribute($objUser, $objAttribute->getId(), 0, false, false, false, false, true, array('_CLASS' => $nr % 2 + 1)) ? $nr++ : false;
                $objUser->objAttribute->next();
            }

            $this->_objTpl->setVariable("ACCESS_REFERER", $_SERVER['HTTP_REFERER']);
        } else {
            // or would it be better to redirect to the home page?
            \Cx\Core\Csrf\Controller\Csrf::header('Location: index.php?section=Access&cmd=members');
            exit;
        }
    }

    private function members($groupId = null)
    {
        global $_ARRAYLANG, $_CONFIG;

        $groupId = !empty($groupId) ? $groupId : (isset($_REQUEST['groupId']) ? intval($_REQUEST['groupId']) : 0);
        $search = isset($_REQUEST['search']) && !empty($_REQUEST['search']) ? preg_split('#\s+#', $_REQUEST['search']) : array();
        $limitOffset = isset($_GET['pos']) ? intval($_GET['pos']) : 0;
        $usernameFilter = isset($_REQUEST['username_filter']) && $_REQUEST['username_filter'] != '' && in_array(ord($_REQUEST['username_filter']), array_merge(array(48), range(65, 90))) ? $_REQUEST['username_filter'] : null;
        $userFilter = array('active' => true);

        $this->parseLetterIndexList('index.php?section=Access&amp;cmd=members&amp;groupId='.$groupId, 'username_filter', $usernameFilter);

        $this->_objTpl->setVariable('ACCESS_SEARCH_VALUE', htmlentities(join(' ', $search), ENT_QUOTES, CONTREXX_CHARSET));

        if ($groupId) {
            $userFilter['group_id'] = $groupId;
        }
        if ($usernameFilter !== null) {
            $userFilter['username'] = array('REGEXP' => '^'.($usernameFilter == '0' ? '[0-9]|-|_' : $usernameFilter));
        }

        $objFWUser = \FWUser::getFWUserObject();
        $objGroup = $objFWUser->objGroup->getGroup($groupId);
        if ($objGroup->getType() == 'frontend' && $objGroup->getUserCount() > 0 && ($objUser = $objFWUser->objUser->getUsers($userFilter, $search, array('username' => 'asc'), null, $_CONFIG['corePagingLimit'], $limitOffset)) && $userCount = $objUser->getFilteredSearchUserCount()) {

            if ($userCount > $_CONFIG['corePagingLimit']) {
                $this->_objTpl->setVariable('ACCESS_USER_PAGING', getPaging($userCount, $limitOffset, "&groupId=".$groupId."&search=".htmlspecialchars(implode(' ',$search), ENT_QUOTES, CONTREXX_CHARSET)."&username_filter=".$usernameFilter, "<strong>".$_ARRAYLANG['TXT_ACCESS_MEMBERS']."</strong>"));
            }

            $this->_objTpl->setVariable('ACCESS_GROUP_NAME', (($objGroup = $objFWUser->objGroup->getGroup($groupId)) && $objGroup->getId()) ? htmlentities($objGroup->getName(), ENT_QUOTES, CONTREXX_CHARSET) : $_ARRAYLANG['TXT_ACCESS_MEMBERS']);

            $arrBuddyIds = \Cx\Modules\U2u\Controller\U2uLibrary::getIdsOfBuddies();

            $nr = 0;
            while (!$objUser->EOF) {
                $this->parseAccountAttributes($objUser);
                $this->_objTpl->setVariable('ACCESS_USER_ID', $objUser->getId());
                $this->_objTpl->setVariable('ACCESS_USER_CLASS', $nr++ % 2 + 1);

                if ($objUser->getProfileAccess() == 'everyone' ||
                    $objFWUser->objUser->login() &&
                    (
                        $objUser->getId() == $objFWUser->objUser->getId() ||
                        $objUser->getProfileAccess() == 'members_only' ||
                        $objFWUser->objUser->getAdminStatus()
                    )
                ) {
                    $objUser->objAttribute->first();

                    while (!$objUser->objAttribute->EOF) {
                        $objAttribute = $objUser->objAttribute->getById($objUser->objAttribute->getId());
                        $this->parseAttribute($objUser, $objAttribute->getId(), 0, false, false, false, false, false);
                        $objUser->objAttribute->next();
                    }
                } else {
                    $this->parseAttribute($objUser, 'picture', 0, false, false, false, false, false);
                    $this->parseAttribute($objUser, 'gender', 0, false, false, false, false, false);
                }

                if($this->_objTpl->blockExists('u2u_addaddress')){
                    if($objUser->getId() == $objFWUser->objUser->getId() || in_array($objUser->getId(), $arrBuddyIds)){
                        $this->_objTpl->hideBlock('u2u_addaddress');
                    }else{
                        $this->_objTpl->touchBlock('u2u_addaddress');
                    }
                }
                $this->_objTpl->parse('access_user');
                $objUser->next();
            }

            $this->_objTpl->parse('access_members');
        } else {
            $this->_objTpl->hideBlock('access_members');
        }
    }

    private function settings()
    {
        global $_CONFIG, $_ARRAYLANG;

        $objFWUser = \FWUser::getFWUserObject();
        if (!$objFWUser->objUser->login()) {
            \Cx\Core\Csrf\Controller\Csrf::header('Location: '.CONTREXX_DIRECTORY_INDEX.'?section=Login&redirect='.base64_encode(ASCMS_PROTOCOL.'://'.$_CONFIG['domainUrl'].CONTREXX_SCRIPT_PATH.'?section=Access&cmd='.rawurlencode($_REQUEST['cmd'])));
            exit;
        }
        $settingsDone = false;
        $objFWUser->objUser->loadNetworks();

        if (isset($_POST['access_delete_account'])) {
            // delete account
            \Cx\Core\Csrf\Controller\Csrf::check_code();
            if ($objFWUser->objUser->checkPassword(isset($_POST['access_user_password']) ? $_POST['access_user_password'] : null)) {
                if ($objFWUser->objUser->isAllowedToDeleteAccount()) {
                    if ($objFWUser->objUser->delete(true)) {
                        $this->_objTpl->setVariable('ACCESS_SETTINGS_MESSAGE', $_ARRAYLANG['TXT_ACCESS_YOUR_ACCOUNT_SUCCSESSFULLY_DELETED']);
                        if ($this->_objTpl->blockExists('access_settings')) {
                            $this->_objTpl->hideBlock('access_settings');
                        }
                        if ($this->_objTpl->blockExists('access_settings_done')) {
                            $this->_objTpl->touchBlock('access_settings_done');
                        }
                        return;
                    } else {
                        $this->_objTpl->setVariable('ACCESS_SETTINGS_MESSAGE', implode('<br />', $objFWUser->objUser->getErrorMsg()));
                    }
                } else {
                    $this->_objTpl->setVariable('ACCESS_SETTINGS_MESSAGE', $_ARRAYLANG['TXT_ACCESS_NOT_ALLOWED_TO_DELETE_ACCOUNT']);
                }
            } else {
                $this->_objTpl->setVariable('ACCESS_SETTINGS_MESSAGE', $_ARRAYLANG['TXT_ACCESS_INVALID_EXISTING_PASSWORD']);
            }
        } elseif (isset($_POST['access_change_password'])) {
            // change password
            \Cx\Core\Csrf\Controller\Csrf::check_code();
            if (!empty($_POST['access_user_current_password']) && $objFWUser->objUser->checkPassword(trim(contrexx_stripslashes($_POST['access_user_current_password'])))) {
                $this->_objTpl->setVariable(
                    'ACCESS_SETTINGS_MESSAGE',
                    ($objFWUser->objUser->setPassword(
                        isset($_POST['access_user_password']) ?
                            trim(contrexx_stripslashes($_POST['access_user_password']))
                            : '',
                        isset($_POST['access_user_password_confirmed']) ?
                            trim(contrexx_stripslashes($_POST['access_user_password_confirmed']))
                            : '',
                        true
                    ) && $objFWUser->objUser->store()) ?
                        $_ARRAYLANG['TXT_ACCESS_PASSWORD_CHANGED_SUCCESSFULLY'].(($settingsDone = true) && false)
                        : implode('<br />', $objFWUser->objUser->getErrorMsg())
                );
            } else {
                $this->_objTpl->setVariable('ACCESS_SETTINGS_MESSAGE', $_ARRAYLANG['TXT_ACCESS_INVALID_EXISTING_PASSWORD']);
            }
        } elseif (isset($_POST['access_store'])) {
            // store profile
            \Cx\Core\Csrf\Controller\Csrf::check_code();
            $status = true;

            isset($_POST['access_user_username']) ? $objFWUser->objUser->setUsername(trim(contrexx_stripslashes($_POST['access_user_username']))) : null;
            $objFWUser->objUser->setEmail(isset($_POST['access_user_email']) ? trim(contrexx_stripslashes($_POST['access_user_email'])) : $objFWUser->objUser->getEmail());

            $currentLangId = $objFWUser->objUser->getFrontendLanguage();
            $objFWUser->objUser->setFrontendLanguage(isset($_POST['access_user_frontend_language']) ? intval($_POST['access_user_frontend_language']) : $objFWUser->objUser->getFrontendLanguage());
            $objFWUser->objUser->setEmailAccess(isset($_POST['access_user_email_access']) && $objFWUser->objUser->isAllowedToChangeEmailAccess() ? contrexx_stripslashes($_POST['access_user_email_access']) : $objFWUser->objUser->getEmailAccess());
            $objFWUser->objUser->setProfileAccess(isset($_POST['access_user_profile_access']) && $objFWUser->objUser->isAllowedToChangeProfileAccess() ? contrexx_stripslashes($_POST['access_user_profile_access']) : $objFWUser->objUser->getProfileAccess());

            if (isset($_POST['access_profile_attribute']) && is_array($_POST['access_profile_attribute'])) {
                $arrProfile = $_POST['access_profile_attribute'];

                if (   !empty($_POST['access_image_uploader_id'])
                    && isset($_POST['access_profile_attribute_images'])
                    && is_array($_POST['access_profile_attribute_images'])
                    && ($result = $this->addUploadedImagesToProfile($objFWUser->objUser, $arrProfile, $_POST['access_profile_attribute_images'], $_POST['access_image_uploader_id'])) !== true
                ) {
                    $status = false;
                }

                $objFWUser->objUser->setProfile($arrProfile);
            }

            $objFWUser->objUser->setSubscribedNewsletterListIDs(isset($_POST['access_user_newsletters']) && is_array($_POST['access_user_newsletters']) ? $_POST['access_user_newsletters'] : array());

            if ($status) {
                $arrSettings = \User_Setting::getSettings();
                if (
                    // if user_account_verification is false (0), then we do not need to do checkMandatoryCompliance(), because
                    // the required fields do not need to be set. This means its not necessary in signup that the required fields are already set
                    // this is a setting which you can set in the user management backend
                    (
                        !$arrSettings['user_account_verification']['value']
                        || $objFWUser->objUser->checkMandatoryCompliance()
                    )
                    && $objFWUser->objUser->store()
                ) {
                    $msg = $_ARRAYLANG['TXT_ACCESS_USER_ACCOUNT_STORED_SUCCESSFULLY'];
                    $settingsDone = true;
                    $this->setLanguageCookie($currentLangId, $objFWUser->objUser->getFrontendLanguage());
                } else {
                    $msg = implode('<br />', $objFWUser->objUser->getErrorMsg());
                }
            } else {
                $msg = implode('<br />', $result);
            }
            $this->_objTpl->setVariable('ACCESS_SETTINGS_MESSAGE', $msg);
        } elseif ($_GET['act'] == 'disconnect') {
            $objFWUser->objUser->getNetworks()->deleteNetwork($_GET['provider']);
            $currentUrl = clone \Env::get('Resolver')->getUrl();
            $currentUrl->setParams(array(
                'act' => null,
                'provider' => null,
            ));
            header('Location: ' . $currentUrl->__toString());
            exit;
        }

        $uploader = $this->getImageUploader();
        
        $this->parseAccountAttributes($objFWUser->objUser, true);
        $this->parseNewsletterLists($objFWUser->objUser);

        while (!$objFWUser->objUser->objAttribute->EOF) {
            $objAttribute = $objFWUser->objUser->objAttribute->getById($objFWUser->objUser->objAttribute->getId());

            if (   !$objAttribute->isProtected()
                || (\Permission::checkAccess($objAttribute->getAccessId(), 'dynamic', true)
                    || $objAttribute->checkModifyPermission())
            ) {
                $this->parseAttribute($objFWUser->objUser, $objAttribute->getId(), 0, true);
            }

            $objFWUser->objUser->objAttribute->next();
        }

        $this->attachJavaScriptFunction('accessSetWebsite');

        $this->_objTpl->setVariable(array(
            'ACCESS_DELETE_ACCOUNT_BUTTON'  => '<input type="submit" name="access_delete_account" value="'.$_ARRAYLANG['TXT_ACCESS_DELETE_ACCOUNT'].'" />',
            'ACCESS_USER_PASSWORD_INPUT'    => '<input type="password" name="access_user_password" />',
            'ACCESS_STORE_BUTTON'           => '<input type="submit" name="access_store" value="'.$_ARRAYLANG['TXT_ACCESS_SAVE'].'" />',
            'ACCESS_CHANGE_PASSWORD_BUTTON' => '<input type="submit" name="access_change_password" value="'.$_ARRAYLANG['TXT_ACCESS_CHANGE_PASSWORD'].'" />',
            'ACCESS_JAVASCRIPT_FUNCTIONS'   => $this->getJavaScriptCode(),
            'ACCESS_IMAGE_UPLOADER_ID'      => $uploader->getId(),
            'ACCESS_IMAGE_UPLOADER_CODE'    => $uploader->getXHtml(),
        ));

        $arrSettings = \User_Setting::getSettings();

        if (function_exists('curl_init') && $arrSettings['sociallogin']['status']) {
            $this->parseNetworks($objFWUser->objUser);
        }

        if ($this->_objTpl->blockExists('access_user_networks')) {
            $this->_objTpl->{function_exists('curl_init') && $arrSettings['sociallogin']['status'] ? 'touchBlock' : 'hideBlock'}('access_user_networks');
        }
        if ($this->_objTpl->blockExists('access_settings')) {
            $this->_objTpl->{$settingsDone ? 'hideBlock' : 'touchBlock'}('access_settings');
        }
        if ($this->_objTpl->blockExists('access_settings_done')) {
            $this->_objTpl->{$settingsDone ? 'touchBlock' : 'hideBlock'}('access_settings_done');
        }
    }

    /**
     * Parse the network settings page
     *
     * @param object the user object of the current logged in user
     */
    private function parseNetworks($objUser) {
        global $_ARRAYLANG;

        $availableProviders = \Cx\Lib\SocialLogin::getProviders();
        foreach ($availableProviders as $index => $provider) {
            if (!$provider->isActive()) {
                unset($availableProviders[$index]);
            }
        }
        $userNetworks = $objUser->getNetworks()->getNetworksAsArray();

        $this->_objTpl->setGlobalVariable(array(
            'TXT_ACCESS_SOCIALLOGIN_PROVIDER' => $_ARRAYLANG['TXT_ACCESS_SOCIALLOGIN_PROVIDER'],
            'TXT_ACCESS_SOCIALLOGIN_STATE'    => $_ARRAYLANG['TXT_ACCESS_SOCIALLOGIN_STATE'],
        ));

        // get current url for redirect parameter
        $currentUrl = clone \Env::get('Resolver')->getUrl();

        if (!$this->_objTpl->blockExists('access_sociallogin_provider')) {
            return null;
        }

        // parse the connect buttons
        foreach ($availableProviders as $providerName => $providerSettings) {
            if (empty($userNetworks[$providerName])) {
                $state = $_ARRAYLANG['TXT_ACCESS_SOCIALLOGIN_DISCONNECTED'];
                $class = 'disconnected';
                $uri = contrexx_raw2xhtml(
                    \Cx\Lib\SocialLogin::getLoginUrl($providerName,
                        base64_encode($currentUrl->__toString())
                    )
                );
                $uriAction = $_ARRAYLANG['TXT_ACCESS_SOCIALLOGIN_CONNECT'];
            } else {
                $state = $_ARRAYLANG['TXT_ACCESS_SOCIALLOGIN_CONNECTED'];
                $class = 'connected';
                $disconnectUrl = clone \Env::get('Resolver')->getUrl();
                $disconnectUrl->setParam('act', 'disconnect');
                $disconnectUrl->setParam('provider', $providerName);
                $uri = $disconnectUrl->__toString();
                $uriAction = $_ARRAYLANG['TXT_ACCESS_SOCIALLOGIN_DISCONNECT'];
            }

            $this->_objTpl->setVariable(array(
                'ACCESS_SOCIALLOGIN_PROVIDER_NAME_UPPER' => contrexx_raw2xhtml(ucfirst($providerName)),
                'ACCESS_SOCIALLOGIN_PROVIDER_STATE'      => $state,
                'ACCESS_SOCIALLOGIN_PROVIDER_STATE_CLASS'=> $class,
                'ACCESS_SOCIALLOGIN_PROVIDER_NAME'       => contrexx_raw2xhtml($providerName),
                'ACCESS_SOCIALLOGIN_URL'                 => $uri,
                'ACCESS_SOCIALLOGIN_URL_ACTION'          => $uriAction,
            ));

            if ($class == 'disconnected') {
                $this->_objTpl->parse('access_sociallogin_provider_disconnected');
                $this->_objTpl->hideBlock('access_sociallogin_provider_connected');
            } else {
                $this->_objTpl->parse('access_sociallogin_provider_connected');
                $this->_objTpl->hideBlock('access_sociallogin_provider_disconnected');
            }

            $this->_objTpl->parse('access_sociallogin_provider');
        }
    }

    private function setLanguageCookie($currentLangId, $newLangId)
    {
        global $objInit;

        // set a new cookie if the language id had been changed
        if ($currentLangId != $newLangId) {
            // check if the desired language is active at all. otherwise set default language
    $objInit->arrLang[$newLangId]['frontend'];
            if (   $objInit->arrLang[$newLangId]['frontend']
                || ($newLangId = $objInit->defaultFrontendLangId)) {
                setcookie("langId", $newLangId, time()+3600*24*30, ASCMS_PATH_OFFSET.'/');
            }
        }
    }

    private function confirmSignUp($userId, $restoreKey)
    {
        global $_ARRAYLANG, $_CONFIG;

        $objFWUser = \FWUser::getFWUserObject();
        if (($objUser = $objFWUser->objUser->getUser($userId)) && $objUser->getRestoreKey() == $restoreKey) {
            $arrSettings = \User_Setting::getSettings();
            if (!$arrSettings['user_activation_timeout']['status'] || $objUser->getRestoreKeyTime() >= time()) {
                if ($objUser->finishSignUp()) {
                    return true;
                }
                $this->arrStatusMsg['error'] = array_merge($this->arrStatusMsg['error'], $objUser->getErrorMsg());
            } else {
                $this->arrStatusMsg['error'][] = $_ARRAYLANG['TXT_ACCESS_ACTIVATION_TIME_EXPIRED'];
                $this->arrStatusMsg['error'][] = '<a href="'.CONTREXX_DIRECTORY_INDEX.'?section=Access&amp;cmd=signup" title="'.$_ARRAYLANG['TXT_ACCESS_REGISTER_NEW_ACCOUNT'].'">'.$_ARRAYLANG['TXT_ACCESS_REGISTER_NEW_ACCOUNT'].'</a>';
            }
        } else {
            $mailSubject = str_replace('%HOST%', 'http://'.$_CONFIG['domainUrl'], $_ARRAYLANG['TXT_ACCESS_ACCOUNT_ACTIVATION_NOT_POSSIBLE']);
            $adminEmail = '<a href="mailto:'.$_CONFIG['coreAdminEmail'].'?subject='.$mailSubject.'" title="'.$_CONFIG['coreAdminEmail'].'">'.$_CONFIG['coreAdminEmail'].'</a>';
            $this->arrStatusMsg['error'][] = str_replace('%EMAIL%', $adminEmail, $_ARRAYLANG['TXT_ACCESS_INVALID_USERNAME_OR_ACTIVATION_KEY']);
        }

        return false;
    }

    private function signUp()
    {
        global $_ARRAYLANG, $_CORELANG;

        if (!empty($_GET['u']) && !empty($_GET['k'])) {
            $this->_objTpl->hideBlock('access_signup_store_success');
            $this->_objTpl->hideBlock('access_signup_store_error');

            if ($this->confirmSignUp(intval($_GET['u']), contrexx_stripslashes($_GET['k']))) {
                $this->_objTpl->setVariable('ACCESS_SIGNUP_MESSAGE', $_ARRAYLANG['TXT_ACCESS_ACCOUNT_SUCCESSFULLY_ACTIVATED']);
                $this->_objTpl->parse('access_signup_confirm_success');
                $this->_objTpl->hideBlock('access_signup_confirm_error');
            } else {
                $this->_objTpl->setVariable('ACCESS_SIGNUP_MESSAGE', implode('<br />', $this->arrStatusMsg['error']));
                $this->_objTpl->parse('access_signup_confirm_error');
                $this->_objTpl->hideBlock('access_signup_confirm_success');
            }

            return;
        } else {
            $this->_objTpl->hideBlock('access_signup_confirm_success');
            $this->_objTpl->hideBlock('access_signup_confirm_error');
        }

        $arrSettings = \User_Setting::getSettings();

        $objUser = null;
        if (!empty($_SESSION['user_id'])) {
            $objUser = \FWUser::getFWUserObject()->objUser->getUser($_SESSION['user_id']);
            if ($objUser) {
                $objUser->releaseRestoreKey();

                $active = $arrSettings['sociallogin_active_automatically']['status'];
                $objUser->setActiveStatus($active);
                $this->_objTpl->hideBlock('access_logindata');
            }
        }

        if (!$objUser) {
            $objUser = new \User();
        }

        if (isset($_POST['access_signup'])) {
            $objUser->setUsername(isset($_POST['access_user_username']) ? trim(contrexx_stripslashes($_POST['access_user_username'])) : '');
            $objUser->setEmail(isset($_POST['access_user_email']) ? trim(contrexx_stripslashes($_POST['access_user_email'])) : '');
            $objUser->setFrontendLanguage(isset($_POST['access_user_frontend_language']) ? intval($_POST['access_user_frontend_language']) : 0);

            $assignedGroups = $objUser->getAssociatedGroupIds();
            if (empty($assignedGroups)) {
                $objUser->setGroups(explode(',', $arrSettings['assigne_to_groups']['value']));
            }

            $objUser->setSubscribedNewsletterListIDs(isset($_POST['access_user_newsletters']) && is_array($_POST['access_user_newsletters']) ? $_POST['access_user_newsletters'] : array());

            if (
                (
                    // either no profile attributes are set
                    (!isset($_POST['access_profile_attribute']) || !is_array($_POST['access_profile_attribute']))
                    ||
                    // otherwise try to adopt them
                    (
                        ($arrProfile = $_POST['access_profile_attribute'])
                        && (
                            // either no profile images are set
                            (!isset($_POST['access_profile_attribute_images']) || !is_array($_POST['access_profile_attribute_images']))
                            ||
                            // otherwise try to upload them
                            ($uploadImageError = $this->addUploadedImagesToProfile($objUser, $arrProfile, $_POST['access_profile_attribute_images'], $_POST['access_image_uploader_id'])) === true
                        )
                        && $objUser->setProfile($arrProfile)
                    )
                )
                && $objUser->setPassword(
                    isset($_POST['access_user_password']) ?
                        trim(contrexx_stripslashes($_POST['access_user_password']))
                    :   '',
                    isset($_POST['access_user_password_confirmed'])?
                        trim(contrexx_stripslashes($_POST['access_user_password_confirmed']))
                    :    ''
                )
                &&
                // if user_account_verification is false (0), then we do not need to do checkMandatoryCompliance(), because
                // the required fields do not need to be set. This means its not necessary in signup that the required fields are already set
                // this is a setting which you can set in the user management backend
                (
                    !$arrSettings['user_account_verification']['value']
                    || $objUser->checkMandatoryCompliance()
                )
                && $this->checkCaptcha()
                && $this->checkToS()
                && $objUser->signUp()
            ) {
                if ($this->handleSignUp($objUser)) {
                    if (isset($_SESSION['user_id'])) {
                        unset($_SESSION['user_id']);
                    }
                    $this->_objTpl->setVariable('ACCESS_SIGNUP_MESSAGE', implode('<br />', $this->arrStatusMsg['ok']));
                    $this->_objTpl->parse('access_signup_store_success');
                    $this->_objTpl->hideBlock('access_signup_store_error');
                } else {
                    $this->_objTpl->setVariable('ACCESS_SIGNUP_MESSAGE', implode('<br />', $this->arrStatusMsg['error']));
                    $this->_objTpl->parse('access_signup_store_error');
                    $this->_objTpl->hideBlock('access_signup_store_success');
                }

                $this->_objTpl->hideBlock('access_signup_form');
                return;
            } else {
                if (is_array($uploadImageError)) {
                    $this->arrStatusMsg['error'] = array_merge($this->arrStatusMsg['error'], $uploadImageError);
                }
                $this->arrStatusMsg['error'] = array_merge($this->arrStatusMsg['error'], $objUser->getErrorMsg());

                $this->_objTpl->hideBlock('access_signup_store_success');
                $this->_objTpl->hideBlock('access_signup_store_error');
            }
        } else {
            $this->_objTpl->hideBlock('access_signup_store_success');
            $this->_objTpl->hideBlock('access_signup_store_error');
        }

        $this->parseAccountAttributes($objUser, true);

        while (!$objUser->objAttribute->EOF) {
            $objAttribute = $objUser->objAttribute->getById($objUser->objAttribute->getId());

            if (!$objAttribute->isProtected() ||
                (
                    \Permission::checkAccess($objAttribute->getAccessId(), 'dynamic', true) ||
                    $objAttribute->checkModifyPermission()
                )
            ) {
                $this->parseAttribute($objUser, $objAttribute->getId(), 0, true);
            }

            $objUser->objAttribute->next();
        }

        $this->parseNewsletterLists($objUser);

        $this->attachJavaScriptFunction('accessSetWebsite');

        $uploader = $this->getImageUploader();
        $this->_objTpl->setVariable(array(
            'ACCESS_SIGNUP_BUTTON'          => '<input type="submit" name="access_signup" value="'.$_ARRAYLANG['TXT_ACCESS_CREATE_ACCOUNT'].'" />',
            'ACCESS_JAVASCRIPT_FUNCTIONS'   => $this->getJavaScriptCode(),
            'ACCESS_IMAGE_UPLOADER_ID'      => $uploader->getId(),
            'ACCESS_IMAGE_UPLOADER_CODE'    => $uploader->getXHtml(),
            'ACCESS_SIGNUP_MESSAGE'         => implode("<br />\n", $this->arrStatusMsg['error'])
        ));

        if (!$arrSettings['use_usernames']['status']) {
            if ($this->_objTpl->blockExists('access_user_username')) {
                $this->_objTpl->hideBlock('access_user_username');
            }
        }

        // set captcha
        if ($this->_objTpl->blockExists('access_captcha')) {
            if ($arrSettings['user_captcha']['status']) {
                $this->_objTpl->setVariable(array(
                    'ACCESS_CAPTCHA_CODE' => \Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->getCode(),
                    'TXT_ACCESS_CAPTCHA'  => $_CORELANG['TXT_CORE_CAPTCHA'],
                ));
                $this->_objTpl->parse('access_captcha');
            } else {
                $this->_objTpl->hideBlock('access_captcha');
            }
        }

        // set terms and conditions
        if ($this->_objTpl->blockExists('access_tos')) {
            if ($arrSettings['user_accept_tos_on_signup']['status']) {
                $uriTos = CONTREXX_SCRIPT_PATH.'?section=Agb';
                $this->_objTpl->setVariable(array(
                    'TXT_ACCESS_TOS' => $_ARRAYLANG['TXT_ACCESS_TOS'],
                    'ACCESS_TOS'     => '<input type="checkbox" name="access_user_tos" id="access_user_tos"'.(!empty($_POST['access_user_tos']) ? ' checked="checked"' : '').' /><label for="access_user_tos">'.sprintf($_ARRAYLANG['TXT_ACCESS_ACCEPT_TOS'], $uriTos).'</label>'
                ));
                $this->_objTpl->parse('access_tos');
            } else {
                $this->_objTpl->hideBlock('access_tos');
            }
        }

        $this->_objTpl->parse('access_signup_form');
    }


    private function checkCaptcha()
    {
        global $_ARRAYLANG;

        $arrSettings = \User_Setting::getSettings();
        if (!$arrSettings['user_captcha']['status'] || \Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->check()) {
            return true;
        }

        $this->arrStatusMsg['error'][] = $_ARRAYLANG['TXT_ACCESS_INVALID_CAPTCHA_CODE'];
        return false;
    }


    private function checkTos()
    {
        global $_ARRAYLANG;

        $arrSettings = \User_Setting::getSettings();
        if (!$arrSettings['user_accept_tos_on_signup']['status'] || !empty($_POST['access_user_tos'])) {
            return true;
        }

        $this->arrStatusMsg['error'][] = $_ARRAYLANG['TXT_ACCESS_TOS_NOT_CHECKED'];
        return false;
    }


    function handleSignUp($objUser)
    {
        global $_ARRAYLANG, $_CONFIG, $_LANGID;

        $objFWUser = \FWUser::getFWUserObject();
        $objUserMail = $objFWUser->getMail();
        $arrSettings = \User_Setting::getSettings();

        if ($arrSettings['user_activation']['status']) {
            $mail2load = 'reg_confirm';
            $mail2addr = $objUser->getEmail();
        } else {
            $mail2load = 'new_user';
            $mail2addr = $arrSettings['notification_address']['value'];
        }

        if (
            (
                $objUserMail->load($mail2load, $_LANGID) ||
                $objUserMail->load($mail2load)
            ) &&
            (\Env::get('ClassLoader')->loadFile(ASCMS_LIBRARY_PATH.'/phpmailer/class.phpmailer.php')) &&
            ($objMail = new \PHPMailer()) !== false
        ) {
            if ($_CONFIG['coreSmtpServer'] > 0 && \Env::get('ClassLoader')->loadFile(ASCMS_CORE_PATH.'/SmtpSettings.class.php')) {
                if (($arrSmtp = \SmtpSettings::getSmtpAccount($_CONFIG['coreSmtpServer'])) !== false) {
                    $objMail->IsSMTP();
                    $objMail->Host = $arrSmtp['hostname'];
                    $objMail->Port = $arrSmtp['port'];
                    $objMail->SMTPAuth = true;
                    $objMail->Username = $arrSmtp['username'];
                    $objMail->Password = $arrSmtp['password'];
                }
            }

            $objMail->CharSet = CONTREXX_CHARSET;
            $objMail->SetFrom($objUserMail->getSenderMail(), $objUserMail->getSenderName());
            $objMail->Subject = $objUserMail->getSubject();

            if (in_array($objUserMail->getFormat(), array('multipart', 'text'))) {
                $objUserMail->getFormat() == 'text' ? $objMail->IsHTML(false) : false;
                $objMail->{($objUserMail->getFormat() == 'text' ? '' : 'Alt').'Body'} = str_replace(
                    array(
                        '[[HOST]]',
                        '[[USERNAME]]',
                        '[[ACTIVATION_LINK]]',
                        '[[HOST_LINK]]',
                        '[[SENDER]]',
                        '[[LINK]]'
                    ),
                    array(
                        $_CONFIG['domainUrl'],
                        $objUser->getUsername(),
                        'http://'.$_CONFIG['domainUrl'].CONTREXX_SCRIPT_PATH.'?section=Access&cmd=signup&u='.($objUser->getId()).'&k='.$objUser->getRestoreKey(),
                        'http://'.$_CONFIG['domainUrl'],
                        $objUserMail->getSenderName(),
                        'http://'.$_CONFIG['domainUrl'].ASCMS_PATH_OFFSET.ASCMS_BACKEND_PATH.'/index.php?cmd=Access&act=user&tpl=modify&id='.$objUser->getId()
                    ),
                    $objUserMail->getBodyText()
                );
            }
            if (in_array($objUserMail->getFormat(), array('multipart', 'html'))) {
                $objUserMail->getFormat() == 'html' ? $objMail->IsHTML(true) : false;
                $objMail->Body = str_replace(
                    array(
                        '[[HOST]]',
                        '[[USERNAME]]',
                        '[[ACTIVATION_LINK]]',
                        '[[HOST_LINK]]',
                        '[[SENDER]]',
                        '[[LINK]]'
                    ),
                    array(
                        $_CONFIG['domainUrl'],
                        htmlentities($objUser->getUsername(), ENT_QUOTES, CONTREXX_CHARSET),
                        'http://'.$_CONFIG['domainUrl'].CONTREXX_SCRIPT_PATH.'?section=Access&cmd=signup&u='.($objUser->getId()).'&k='.$objUser->getRestoreKey(),
                        'http://'.$_CONFIG['domainUrl'],
                        htmlentities($objUserMail->getSenderName(), ENT_QUOTES, CONTREXX_CHARSET),
                        'http://'.$_CONFIG['domainUrl'].ASCMS_PATH_OFFSET.ASCMS_BACKEND_PATH.'/index.php?cmd=Access&act=user&tpl=modify&id='.$objUser->getId()
                    ),
                    $objUserMail->getBodyHtml()
                );
            }

            $objMail->AddAddress($mail2addr);

            if ($objMail->Send()) {
                $this->arrStatusMsg['ok'][] = $_ARRAYLANG['TXT_ACCESS_ACCOUNT_SUCCESSFULLY_CREATED'];
                if ($arrSettings['user_activation']['status']) {
                    $timeoutStr = '';
                    if ($arrSettings['user_activation_timeout']['status']) {
                        if ($arrSettings['user_activation_timeout']['value'] > 1) {
                            $timeoutStr = $arrSettings['user_activation_timeout']['value'].' '.$_ARRAYLANG['TXT_ACCESS_HOURS_IN_STR'];
                        } else {
                            $timeoutStr = ' '.$_ARRAYLANG['TXT_ACCESS_HOUR_IN_STR'];
                        }

                        $timeoutStr = str_replace('%TIMEOUT%', $timeoutStr, $_ARRAYLANG['TXT_ACCESS_ACTIVATION_TIMEOUT']);
                    }
                    $this->arrStatusMsg['ok'][] = str_replace('%TIMEOUT%', $timeoutStr, $_ARRAYLANG['TXT_ACCESS_ACTIVATION_BY_USER_MSG']);
                } else {
                    $this->arrStatusMsg['ok'][] = str_replace("%HOST%", $_CONFIG['domainUrl'], $_ARRAYLANG['TXT_ACCESS_ACTIVATION_BY_SYSTEM']);
                }
                return true;
            }
        }

        $mailSubject = str_replace("%HOST%", "http://".$_CONFIG['domainUrl'], $_ARRAYLANG['TXT_ACCESS_COULD_NOT_SEND_ACTIVATION_MAIL']);
        $adminEmail = '<a href="mailto:'.$_CONFIG['coreAdminEmail'].'?subject='.$mailSubject.'" title="'.$_CONFIG['coreAdminEmail'].'">'.$_CONFIG['coreAdminEmail'].'</a>';
        $this->arrStatusMsg['error'][] = str_replace("%EMAIL%", $adminEmail, $_ARRAYLANG['TXT_ACCESS_COULD_NOT_SEND_EMAIL']);
        return false;
    }
}
?>
