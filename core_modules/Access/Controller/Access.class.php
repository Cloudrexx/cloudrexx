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

            case 'export':
                $this->export();
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
                'ACCESS_USER_REGDATE'       => date(ASCMS_DATE_FORMAT_DATE, $objUser->getRegistrationDate()),
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
            $objUser->objAttribute->first();
            while (!$objUser->objAttribute->EOF) {
                $objAttribute = $objUser->objAttribute->getById($objUser->objAttribute->getId());
                if ($objAttribute->checkReadPermission()) {
                    $this->parseAttribute($objUser, $objAttribute->getId(), 0, false, false, false, false, true, array('_CLASS' => $nr % 2 + 1)) ? $nr++ : false;
                }
                $objUser->objAttribute->next();
            }

            $this->_objTpl->setVariable("ACCESS_REFERER", '$(HTTP_REFERER)');
        } else {
            // or would it be better to redirect to the home page?
            \Cx\Core\Csrf\Controller\Csrf::header('Location: index.php?section=Access&cmd=members');
            exit;
        }
    }

    /**
     * Export user section
     *
     * This section lists the existing active frontend user groups.
     * Additionally is provides the ability to export the members of the
     * active frontend groups as CSV file.
     */
    protected function export() {
        global $_CORELANG;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $requestParams = $cx->getRequest()->getUrl()->getParamArray();

        // check if CSV export has been requested
        if ($cx->getRequest()->hasParam('export')) {
            // filter export by group
            $groupId = 0;
            if ($cx->getRequest()->hasParam('groupId')) {
                $groupId = intval($cx->getRequest()->getParam('groupId'));
            }

            // export users as CSV
            $this->exportUsers($groupId);

            // note: this code is never reached as exportUsers does throw an
            // InstanceException
        }

        // abort in case the template block for listing the existing
        // user groups is missing
        if (!$this->_objTpl->blockExists('access_group_list')) {
            return;
        }

        // fetch active frontend groups
        $objGroup = \FWUser::getFWUserObject()->objGroup->getGroups(
            array(
                'type' => 'frontend',
                'is_active' => true,
            )
        );

        // all text-variable 'All'
        $this->_objTpl->setVariable('TXT_USER_ALL', $_CORELANG['TXT_USER_ALL']);

        // parse list of active frontend groups
        while (!$objGroup->EOF) {
            $this->_objTpl->setVariable(array(
                'ACCESS_GROUP_ID'    => $objGroup->getId(),
                'ACCESS_GROUP_NAME'  => contrexx_raw2xhtml(
                    $objGroup->getName()
                ),
                'ACCESS_GROUP_DESCRIPTION'  => contrexx_raw2xhtml(
                    $objGroup->getDescription()
                ),
            ));

            $this->_objTpl->parse('access_group_list');
            $objGroup->next();
        }
    }

    /**
     * Sanitize the array $filter by ensuring that is only contains
     * valid keys specified by $allowedFilterKeys.
     * 
     * @param   array   $filter Nested array containing profile attribute
     *                          filter conditions.
     * @param   array   $allowedFilterKeys  Array consisting of keys that
     *                                      are allowed to be used as filter
     *                                      keys.
     */
    protected function sanitizeProfileFilter(&$filter, $allowedFilterKeys) { 
        // verify that the requested filter is valid
        foreach ($filter as $attribute => &$argument) {
            // verify $attribute
            if (   !in_array(strtoupper($attribute), $allowedFilterKeys)
                && (!is_int($attribute) || !is_array($argument))
            ) {
                unset($filter[$attribute]);
                continue;
            }

            if (is_array($argument)) {
                $this->sanitizeProfileFilter($argument, $allowedFilterKeys);
                // in case $argument contains no valid filters, we shall
                // remove it completely
                if (empty($argument)) {
                    unset($filter[$attribute]);
                }
            }
        }
    }

    /**
     * Identifies all valid filter keys (of the current request) to be used
     * for filtering the users. 
     * Valid filter arguments can be specified in the application
     * template in the form of template placeholders. I.e. add the
     * following placeholder to allow filtering by firstname:
     *     {ACCESS_FILTER_PROFILE_ATTRIBUTE_FIRSTNAME}
     *
     * @return  array   Array consisting of valid filter keys to be used for
     *                  filtering users.
     */
    protected function fetchAllowedFilterAttributes() {
        // fetch all placeholders from current application template
        $placeholders = $this->_objTpl->getPlaceholderList();
        $filterAttributePlaceholderPrefix = $this->modulePrefix.'FILTER_PROFILE_ATTRIBUTE_';

        // filter out special placeholders that identify allowed filter attributes
        $allowedFilterAttributes = preg_filter('/^' . $filterAttributePlaceholderPrefix . '/', '', $placeholders);

        // verify that attributes are valid
        $objFWUser = \FWUser::getFWUserObject();
        foreach ($allowedFilterAttributes as $idx => $attributeId) {
            $objAttribute = $objFWUser->objUser->objAttribute->getById(strtolower($attributeId));

            // unkown attribute -> drop it from filter
            if ($objAttribute->EOF) {
                unset($allowedFilterAttributes[$idx]);
                continue;
            }

            // user does not have read access to attribute -> drop it from filter
            if (!$objAttribute->checkReadPermission()) {
                unset($allowedFilterAttributes[$idx]);
                continue;
            }
        }

        // add filter join methods (OR and AND) to allowed filter attributes
        $allowedFilterAttributes = array_merge($allowedFilterAttributes, array('AND', 'OR', '=', '<', '>', '!=', '<', '>', 'REGEXP', 'LIKE'));

        return $allowedFilterAttributes;
    }

    /**
     * Fetch sort flags from current application template
     *
     * Identifies all sort flags (of the current request) to be used
     * for sorting the users.
     * Valid sort arguments can be specified in the application
     * template in the form of template placeholders having the following
     * scheme: {ACCESS_SORT_<attribute-ID>_<direction>}
     * I.e. add the following placeholder to sort by attribute 'firstname'
     * in descending order:
     * {ACCESS_SORT_FIRSTNAME_DESC}
     *
     * @return  array   Array consisting of valid sort flagsto be used for
     *                  sorting the users.
     */
    protected function fetchSortFlags() {
        // fetch all placeholders from current application template
        $placeholders = $this->_objTpl->getPlaceholderList();
        $sortPlaceholderPrefix = $this->modulePrefix.'SORT_';

        // filter out special placeholders that identify sort flags
        $sortFlags = preg_filter('/^' . $sortPlaceholderPrefix . '/', '', $placeholders);

        $sortBy = array();
        foreach ($sortFlags as $sortFlag) {
            list($attribute, $direction) = array_map('strtolower', explode('_', $sortFlag));
            $sortBy[$attribute] = $direction;
        }

        return $sortBy;
    }

    private function members($groupId = null)
    {
        global $_ARRAYLANG, $_CONFIG;

        if (empty($groupId)) {
            $groupId = isset($_REQUEST['groupId']) ? intval($_REQUEST['groupId']) : 0;
        }

        $search = isset($_REQUEST['search']) && !empty($_REQUEST['search']) ? preg_split('#\s+#', $_REQUEST['search']) : array();
        $limitOffset = isset($_GET['pos']) ? intval($_GET['pos']) : 0;
        $usernameFilter = isset($_REQUEST['username_filter']) && $_REQUEST['username_filter'] != '' && in_array(ord($_REQUEST['username_filter']), array_merge(array(48), range(65, 90))) ? $_REQUEST['username_filter'] : null;

        $userFilter = array('AND' => array());
        $userFilter['AND'][] = array('active' => true);
        $profileFilter = array();

        $limit = $_CONFIG['corePagingLimit'];
        if ($this->_objTpl->placeholderExists($this->modulePrefix . 'LIMIT_OFF')) {
            $limit = null;
        }

        if (isset($_REQUEST['profile_filter']) && is_array($_REQUEST['profile_filter'])) {
            $profileFilter = $_REQUEST['profile_filter'];

            // decode URL notation in supplied profile filter arguments
            array_walk_recursive($profileFilter, function(&$value, $key) {$value = urldecode($value);});

            // Ensure profile filter does only contain allowed filter arguments.
            $this->sanitizeProfileFilter($profileFilter, $this->fetchAllowedFilterAttributes());
            if (!empty($profileFilter)) {
                $userFilter['AND'][] = $profileFilter;
            }
        }

        $sort = array('username' => 'asc');
        $sortFlags = $this->fetchSortFlags();
        if ($sortFlags) {
            $sort = $sortFlags;
        }

        $this->parseLetterIndexList('index.php?section=Access&amp;cmd=members&amp;groupId='.$groupId, 'username_filter', $usernameFilter);

        $this->_objTpl->setVariable('ACCESS_SEARCH_VALUE', htmlentities(join(' ', $search), ENT_QUOTES, CONTREXX_CHARSET));

        if ($groupId) {
            $userFilter['AND'][] = array('group_id' => $groupId);
        }
        if ($usernameFilter !== null) {
            $userFilter['AND'][] = array('username' => array('REGEXP' => '^'.($usernameFilter == '0' ? '[0-9]|-|_' : $usernameFilter)));
        }

        $objFWUser = \FWUser::getFWUserObject();
        $objGroup = $objFWUser->objGroup->getGroup($groupId);
        if ($objGroup->getType() == 'frontend' && $objGroup->getUserCount() > 0 && ($objUser = $objFWUser->objUser->getUsers($userFilter, $search, $sort, null, $limit, $limitOffset)) && $userCount = $objUser->getFilteredSearchUserCount()) {

            if ($limit && $userCount > $limit) {
                $params = '';
                if ($groupId) {
                    $params .= '&groupId='.$groupId;
                }
                if (count($search)) {
                    $params .= '&search='.htmlspecialchars(implode(' ',$search), ENT_QUOTES, CONTREXX_CHARSET);
                }
                if ($usernameFilter) {
                    $params .= '&username_filter='.$usernameFilter;
                }
                if (count($profileFilter)) {
                    $params .= '&'.http_build_query(array('profile_filter' => $profileFilter));
                }
                $this->_objTpl->setVariable('ACCESS_USER_PAGING', getPaging($userCount, $limitOffset, $params, "<strong>".$_ARRAYLANG['TXT_ACCESS_MEMBERS']."</strong>"));
            }

            $this->_objTpl->setVariable('ACCESS_GROUP_NAME', (($objGroup = $objFWUser->objGroup->getGroup($groupId)) && $objGroup->getId()) ? htmlentities($objGroup->getName(), ENT_QUOTES, CONTREXX_CHARSET) : $_ARRAYLANG['TXT_ACCESS_MEMBERS']);

            $arrBuddyIds = \Cx\Modules\U2u\Controller\U2uLibrary::getIdsOfBuddies();

            $nr = 0;
            while (!$objUser->EOF) {
                $this->parseAccountAttributes($objUser);
                $this->_objTpl->setVariable('ACCESS_USER_ID', $objUser->getId());
                $this->_objTpl->setVariable('ACCESS_USER_CLASS', $nr++ % 2 + 1);
                $this->_objTpl->setVariable('ACCESS_USER_REGDATE', date(ASCMS_DATE_FORMAT_DATE, $objUser->getRegistrationDate()));

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
                        if ($objAttribute->checkReadPermission()) {
                            $this->parseAttribute($objUser, $objAttribute->getId(), 0, false, false, false, false, false);
                        }
                        $objUser->objAttribute->next();
                    }
                } else {
                    foreach (array('picture', 'gender') as $attributeId) {
                        $objAttribute = $objUser->objAttribute->getById($attributeId);
                        if ($objAttribute->checkReadPermission()) {
                            $this->parseAttribute($objUser, $attributeId, 0, false, false, false, false, false);
                        }
                    }
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
            if ($this->_objTpl->blockExists('access_no_members')) {
                $this->_objTpl->hideBlock('access_no_members');
            }
        } else {
            $this->_objTpl->hideBlock('access_members');
            if ($this->_objTpl->blockExists('access_no_members')) {
                $this->_objTpl->touchBlock('access_no_members');
            }
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

        $act = isset($_GET['act']) ? $_GET['act'] : '';
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
                // fetch current profile data
                $arrOriginalProfileData = $this->fetchProfileDataOfUser($objFWUser->objUser);

                // profile modifications to be stored
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
                    // fetch new profile data
                    $arrNewProfileData = $this->fetchProfileDataOfUser($objFWUser->objUser);

                    // identify changed attributes
                    $profileDiff = array();
                    foreach ($arrOriginalProfileData as $key => $value) {
                        if (!isset($arrNewProfileData[$key]) ||
                            $arrNewProfileData[$key] != $value
                        ) {
                            $profileDiff[] = $key;
                        }
                    }

                    // send notification mail regarding modified user profile
                    $this->sendProfileChangeNotificationMail($objFWUser->objUser, $profileDiff, $arrOriginalProfileData, $arrNewProfileData);

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
        } elseif ($act == 'disconnect') {
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

        $objFWUser->objUser->objAttribute->first();
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

        $this->_objTpl->setGlobalVariable(array(
            'ACCESS_USER_ID'  => $objFWUser->objUser->getId(),
        ));

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
     * Send mail notification (signup_notification) regarding a signed-up
     * user profile
     *
     * @todo    Migrate this code to an event listener as soon as User
     *          is a proper doctrine entity
     * @param   \User   $objUser    The user who did sign-up
     * @param   array   $changedAttributes  A one-dimensional list of user
     *                                      profile attributes that had
     *                                      been set.
     * @param   array   $newProfileData     Two-dimensional array of the user's
     *                                      profile data after the
     *                                      modification.
     */
    protected function sendSignUpNotificationMail($objUser, $profileData) {
        $arrSettings = \User_Setting::getSettings();
        if (!$arrSettings['signup_notification_address']['status']) {
            return;
        }

        $objMail = new \Cx\Core\MailTemplate\Model\Entity\Mail();
        if (!$objMail) {
            return;
        }

        if (!$this->preprocessProfileNotificationMail('signup_notification', $objMail, $objUser, array_keys($profileData), $profileData)) {
            return;
        }

        $recipientAddrs = array_map('trim', explode(',', $arrSettings['signup_notification_address']['value']));
        foreach ($recipientAddrs as $recipientMail) {
            $objMail->AddAddress($recipientMail);
            $objMail->Send();
            $objMail->ClearAddresses();
        }
    }

    /**
     * Send mail notification (user_profile_modification) regarding a modified
     * user profile
     *
     * @todo    Migrate this code to an event listener as soon as User
     *          is a proper doctrine event
     * @param   \User   $objUser    The user on which the profile modifications
     *                              had been made on.
     * @param   array   $changedAttributes  A one-dimensional list of user
     *                                      profile attributes that had
     *                                      been modified.
     * @param   array   $oldProfileData     Two-dimensional array of the user's
     *                                      profile data before the
     *                                      modification.
     * @param   array   $newProfileData     Two-dimensional array of the user's
     *                                      profile data after the
     *                                      modification.
     */
    protected function sendProfileChangeNotificationMail($objUser, $changedAttributes, $oldProfileData, $newProfileData) {
        // do skip mail notification if no attributes have been changed
        if (empty($changedAttributes)) {
            return;
        }

        $arrSettings = \User_Setting::getSettings();
        if (!$arrSettings['user_change_notification_address']['status']) {
            return;
        }

        $objMail = new \Cx\Core\MailTemplate\Model\Entity\Mail();
        if (!$objMail) {
            return;
        }

        if (!$this->preprocessProfileNotificationMail('user_profile_modification', $objMail, $objUser, $changedAttributes, $newProfileData, $oldProfileData)) {
            return;
        }

        $recipientAddrs = array_map('trim', explode(',', $arrSettings['user_change_notification_address']['value']));
        foreach ($recipientAddrs as $recipientMail) {
            $objMail->AddAddress($recipientMail);
            $objMail->Send();
            $objMail->ClearAddresses();
        }
    }

    /**
     * Preprocess the notification mail sent after a new user did sign-up or
     * after a profile modification had been done
     *
     * @param   string  $type   The notification action. One of:
     *                          user_profile_modification / signup_notification
     * @param   \Cx\Core\MailTemplate\Model\Entity\Mail The mail instance
     * @param   \User   $objUser    The user that triggered the event
     * @param   array   $changedAttributes  A one-dimensional list of user
     *                                      profile attributes that had
     *                                      been stored.
     * @param   array   $newProfileData     Two-dimensional array of the user's
     *                                      profile data after the event.
     * @param   array   $oldProfileData     Two-dimensional array of the user's
     *                                      profile data before the event.
     *                                      If argument is set, then any
     *                                      associated element in
     *                                      $newProfileData will be marked
     *                                      as changed.
     */
    protected function preprocessProfileNotificationMail($type, $objMail, $objUser, $changedAttributes, $newProfileData, $oldProfileData = array()) {
        global $_ARRAYLANG;

        $objFWUser = \FWUser::getFWUserObject();

        // load email template
        $objUserMail = $objFWUser->getMail();
        if (
            !$objUserMail->load($type, FRONTEND_LANG_ID) &&
            !$objUserMail->load($type)
        ) {
            return false;
        }

        // whether or not we shall output the difference
        // of the profile before and after the event
        $showDiff = false;
        if (!empty($oldProfileData)) {
            $showDiff = true;
        }

        $objMail->SetFrom($objUserMail->getSenderMail(), $objUserMail->getSenderName());
        $objMail->Subject = $objUserMail->getSubject();

        $isTextMail  = in_array($objUserMail->getFormat(), array('multipart', 'text'));
        $isHtmlMail  = in_array($objUserMail->getFormat(), array('multipart', 'html'));

        // fetch domain repo
        $domainRepo = new \Cx\Core\Net\Model\Repository\DomainRepository();

        // placeholder list
        $searchTerms = array(
            // those are the legacy placeholders
            '[HOST]',
            '[USER_ID]',
            '[PROFILE_NAME]',
            '[YEAR]',

            // now follow the new, regular placeholders
            'HOST',
            'USER_ID',
            'PROFILE_NAME',
            'YEAR',
            'PROFILE_ATTRIBUTE_LIST',
        );

        // general replacement data
        $replaceTerms = array(
            $domainRepo->getMainDomain()->getName(),
            $objUser->getId(),
            \FWUser::getParsedUserTitle($objUser),
            date('Y'),
        );

        // data for plain text version
        $replaceTextTerms = array_merge(
            $replaceTerms,
            $replaceTerms
        );

        // data for HTML version
        $replaceHtmlTerms = $replaceTextTerms;

        // check if we shall parse the profile data into the legacy placeholder
        $parseProfilePlaceholder = false;
        if ((
                $isTextMail &&
                preg_match('/\[PROFILE_DATA\]/', $objUserMail->getBodyText())
            ) || (
                $isHtmlMail &&
                preg_match('/\[PROFILE_DATA\]/', $objUserMail->getBodyHtml())
            )
        ) {
            $parseProfilePlaceholder = true;
            $profileDataText = '';
            $profileDataHtml = array();

            // legacy placeholder notation
            $searchTerms[] = '[PROFILE_DATA]';
            // new, regular placeholder notation
            $searchTerms[] = 'PROFILE_DATA';
        }

        $attributeDataText = array();
        $attributeDataHtml = array();

        $profileAttributes = array_keys($this->fetchProfileDataOfUser($objFWUser->objUser));
        $idx = 0;
        foreach ($profileAttributes as $attribute) {
            switch ($attribute) {
                case 'email':
                    // as email is no a regular profile attribute,
                    // but an account attribute, we have to fetch it
                    // manually
                    $label = $_ARRAYLANG['TXT_ACCESS_EMAIL'];
                    $attributeType = 'email';
                    break;

                default:
                    // fetch meta-data of attribute
                    $objAttribute = $objUser->objAttribute->getById($attribute);
                    $label = $objAttribute->getName();
                    $attributeType = $objAttribute->getType();
                    break;
            }


            // preprocess attribute data for output
            $oldValue = '';
            if ($showDiff) {
                $oldValue = $oldProfileData[$attribute][0];
            }
            $newValue = $newProfileData[$attribute][0];
            switch ($attributeType) {
                case 'date':
                    if ($showDiff && !empty($oldValue)) {
                        $oldValue = date(ASCMS_DATE_FORMAT_DATE, $oldValue);
                    }
                    if (!empty($newValue)) {
                        $newValue = date(ASCMS_DATE_FORMAT_DATE, $newValue);
                    }
                    break;

                case 'checkbox':
                    if ($showDiff) {
                        $oldValue = $oldValue ? $_ARRAYLANG['TXT_ACCESS_YES'] : $_ARRAYLANG['TXT_ACCESS_NO'];
                    }
                    $newValue = $newValue ? $_ARRAYLANG['TXT_ACCESS_YES'] : $_ARRAYLANG['TXT_ACCESS_NO'];
                    break;

                case 'menu':
                    switch ($attribute) {
                        case 'gender':
                            break;
                        case 'title':
                            if ($showDiff) {
                                $oldValue = 'title_' . $oldValue;
                            }
                            $newValue = 'title_' . $newValue;
                            break;
                        case 'country':
                            if ($showDiff) {
                                $oldValue = 'country_' . $oldValue;
                            }
                            $newValue = 'country_' . $newValue;
                            break;
                    }
                    if ($showDiff) {
                        $objAttributeValue = $objAttribute->getById($oldValue);
                        if ($objAttributeValue->getId()) {
                            $oldValue = $objAttributeValue->getName();
                        } else {
                            $oldValue = '';
                        }
                    }
                    
                    $objAttributeValue = $objAttribute->getById($newValue);
                    if ($objAttributeValue->getId()) {
                        $newValue = $objAttributeValue->getName();
                    } else {
                        $newValue = '';
                    }
                    break;
                    
                default:
                    break;
            }

            // data for block template
            $attributeData = array();
            $attributeData['PROFILE_ATTRIBUTE_NAME'] = $label;
            $attributeData['PROFILE_ATTRIBUTE_VALUE'] = $newValue;
            if ($showDiff) {
                $attributeData['PROFILE_ATTRIBUTE_OLD_VALUE'] = $oldValue;
            }
            // the index $idx is required for referencing the array
            // elements below in case the attribute's value has changed
            $idx++;
            $attributeDataText[$idx] = $attributeData;
            $attributeDataHtml[$idx] = contrexx_raw2xhtml($attributeData);

            // abort in case the attribute had not been changed,
            // as the following code is only related to attributes that
            // have been changed
            if (!in_array($attribute, $changedAttributes)) {
                continue;
            }

            // touch block changed (if case we shall show which attributes
            // have changed)
            if ($showDiff) {
                $attributeDataText[$idx]['PROFILE_ATTRIBUTE_CHANGED'] = array(0 => array());
                $attributeDataHtml[$idx]['PROFILE_ATTRIBUTE_CHANGED'] = array(0 => array());
            }

            // data for placeholder PROFILE_DATA
            if ($parseProfilePlaceholder) {
                $attributeInfo = array(
                    'label' => $label,
                    'new'   => $newValue,
                );
                // plain text version
                if ($showDiff) {
                    $profileDataText .= $label . ":\t" . $oldValue . ' => ' . $newValue . "\n";
                    $attributeInfo['old'] = $oldValue;
                } else {
                    $profileDataText .= $label . ":\t" . $newValue . "\n";
                }

                // html version
                $profileDataHtml[] = $attributeInfo;
            }
        }

        // add PROFILE_ATTRIBUTE_LIST data
        $replaceTextTerms[] = $attributeDataText;
        $replaceHtmlTerms[] = $attributeDataHtml;

        if ($isTextMail) {
            // add profile placeholder data
            if ($parseProfilePlaceholder) {
                // assign data twice. Once for legacy placeholder notation
                // and once for the new, regular placeholder notation
                $replaceTextTerms[] = $profileDataText;
                $replaceTextTerms[] = $profileDataText;
            }

            // preprocess substitution data
            $substitution = array_combine(
                $searchTerms,
                $replaceTextTerms
            );

            // deactivate Html version in case we're sending a plain text mail (no multipart)
            $objUserMail->getFormat() == 'text' ? $objMail->IsHTML(false) : false;

            // parse body of mail
            $body = $objUserMail->getBodyText();
            \Cx\Core\MailTemplate\Controller\MailTemplate::substitute($body, $substitution);
            $body = preg_replace('/\[\[([A-Za-z0-9_]*?)\]\]/', '{\\1}', $body);
            \LinkGenerator::parseTemplate($body, true);
            \Cx\Core\MailTemplate\Controller\MailTemplate::clearEmptyPlaceholders($body);
            $objMail->{($objUserMail->getFormat() == 'text' ? '' : 'Alt').'Body'} = $body;
        }

        if ($isHtmlMail) {
            // add profile placeholder data
            if ($parseProfilePlaceholder) {
                $htmlData = $this->generateHtmlForProfileNotificationPlaceholder($profileDataHtml, $showDiff);

                // assign data twice. Once for legacy placeholder notation
                // and once for the new, regular placeholder notation
                $replaceHtmlTerms[] = $htmlData;
                $replaceHtmlTerms[] = $htmlData;
            }

            // preprocess substitution data
            $substitution = array_combine(
                $searchTerms,
                $replaceHtmlTerms
            );

            // deactivate plaintext version in case we're sending a plain Html mail (no multipart)
            $objUserMail->getFormat() == 'html' ? $objMail->IsHTML(true) : false;

            // parse body of mail
            $body = $objUserMail->getBodyHtml();
            \Cx\Core\MailTemplate\Controller\MailTemplate::substitute($body, $substitution);
            $body = preg_replace('/\[\[([A-Za-z0-9_]*?)\]\]/', '{\\1}', $body);
            \LinkGenerator::parseTemplate($body, true);
            \Cx\Core\MailTemplate\Controller\MailTemplate::clearEmptyPlaceholders($body);
            $objMail->Body = $body;
        }

        return true;
    }

    /**
     * Generate a Html table of user's changed profile attributes
     *
     * @param   array   $data   List of profile attributes. The array should
     *                          have the following format:
     *                          <pre>array(
     *                              array(
     *                                  'label' => '<attribute-#1-name>',
     *                                  'new'   => '<value-after-event>',
     *                                  'old'   => '<value-before-event>',
     *                              ),
     *                              array(
     *                                  'label' => '<attribute-#2-name>',
     *                                  'new'   => '<value-after-event>',
     *                                  'old'   => '<value-before-event>',
     *                              ),
     *                          )</pre>
     * @param   boolean $showDiff   Whether or not to output the differnce
     *                              of the changed profile attributes
     * @return  string  The generated Html table
     */
    protected function generateHtmlForProfileNotificationPlaceholder($data, $showDiff = false) {
        global $_ARRAYLANG;

        $htmlTable = new \Cx\Core\Html\Model\Entity\HtmlElement('table');
        $htmlTable->setAttribute('width', '100%');

        $htmlTableHead = new \Cx\Core\Html\Model\Entity\HtmlElement('thead');
        $htmlTable->addChild($htmlTableHead);

        $htmlTableHeadRow = new \Cx\Core\Html\Model\Entity\HtmlElement('tr');
        $htmlTableHead->addChild($htmlTableHeadRow);

        $htmlTableHeadCellAttribute = new \Cx\Core\Html\Model\Entity\HtmlElement('td');
        $htmlTableHeadRow->addChild($htmlTableHeadCellAttribute);

        $htmlValueAttributeStrong = new \Cx\Core\Html\Model\Entity\HtmlElement('strong');
        $htmlTableHeadCellAttribute->addChild($htmlValueAttributeStrong);
        $htmlValueAttributeStrong->addChild(new \Cx\Core\Html\Model\Entity\TextElement($_ARRAYLANG['TXT_ACCESS_ATTRIBUTE']));

        if ($showDiff) {
            $htmlTableHeadCellOldValue = new \Cx\Core\Html\Model\Entity\HtmlElement('td');
            $htmlTableHeadRow->addChild($htmlTableHeadCellOldValue);

            $htmlValueOldStrong = new \Cx\Core\Html\Model\Entity\HtmlElement('strong');
            $htmlTableHeadCellOldValue->addChild($htmlValueOldStrong);
            $htmlValueOldStrong->addChild(new \Cx\Core\Html\Model\Entity\TextElement($_ARRAYLANG['TXT_ACCESS_OLD_VALUE']));
        }

        $htmlTableHeadCellNewValue = new \Cx\Core\Html\Model\Entity\HtmlElement('td');
        $htmlTableHeadRow->addChild($htmlTableHeadCellNewValue);

        $htmlValueNewStrong = new \Cx\Core\Html\Model\Entity\HtmlElement('strong');
        $htmlTableHeadCellNewValue->addChild($htmlValueNewStrong);
        if ($showDiff) {
            $label = $_ARRAYLANG['TXT_ACCESS_NEW_VALUE'];
        } else {
            $label = $_ARRAYLANG['TXT_ACCESS_VALUE'];
        }
        $htmlValueNewStrong->addChild(new \Cx\Core\Html\Model\Entity\TextElement($label));

        $htmlTableBody = new \Cx\Core\Html\Model\Entity\HtmlElement('tbody');
        $htmlTable->addChild($htmlTableBody);

        foreach ($data as $attribute) {
            $htmlTableBodyRow = new \Cx\Core\Html\Model\Entity\HtmlElement('tr');
            $htmlTableBody->addChild($htmlTableBodyRow);

            $htmlTableBodyCellAttribute = new \Cx\Core\Html\Model\Entity\HtmlElement('td');
            $htmlTableBodyRow->addChild($htmlTableBodyCellAttribute);
            $htmlTableBodyCellAttribute->setAttribute('class', 'attribute');
            $htmlTableBodyCellAttribute->addChild(new \Cx\Core\Html\Model\Entity\TextElement(contrexx_raw2xhtml($attribute['label'])));

            if ($showDiff) {
                $htmlTableBodyCellOldValue = new \Cx\Core\Html\Model\Entity\HtmlElement('td');
                $htmlTableBodyRow->addChild($htmlTableBodyCellOldValue);
                $htmlTableBodyCellOldValue->setAttribute('class', 'value old');
                $htmlTableBodyCellOldValue->addChild(new \Cx\Core\Html\Model\Entity\TextElement(contrexx_raw2xhtml($attribute['old'])));
            }

            $htmlTableBodyCellNewValue = new \Cx\Core\Html\Model\Entity\HtmlElement('td');
            $htmlTableBodyRow->addChild($htmlTableBodyCellNewValue);
            $htmlTableBodyCellNewValue->setAttribute('class', 'value new');
            $htmlTableBodyCellNewValue->addChild(new \Cx\Core\Html\Model\Entity\TextElement(contrexx_raw2xhtml($attribute['new'])));
        }

        return (string) $htmlTable;
    }

    /**
     * Get profile data of a user as an array
     *
     * @param   \User   $objUser  User of which its profile data
     *                                      shall be returned.
     * @return  array   Two-dimensional array of the user's profile data.
     *                  The array key represents the profile's attribute-ID
     *                  and the array's value the assocated attribute's value.
     */
    protected function fetchProfileDataOfUser($objUser) {
        //get user's profile details
        $objUser->objAttribute->first();
        $arrUserDetails = array(
            'email' => array($objUser->getEmail()),
        );
        while (!$objUser->objAttribute->EOF) {
            $arrUserDetails[$objUser->objAttribute->getId()][] = $objUser->getProfileAttribute($objUser->objAttribute->getId());
            $objUser->objAttribute->next();
        }
        
        return $arrUserDetails;
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
                    // fetch profile data
                    $profileData = $this->fetchProfileDataOfUser($objUser);

                    // send notification mail regarding signed-up user
                    $this->sendSignUpNotificationMail($objUser, $profileData);

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

            $this->_objTpl->hideBlock('access_signup_form');
            \Cx\Lib\SocialLogin::hideLogin($this->_objTpl, 'access_');

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
                \Cx\Lib\SocialLogin::hideLogin($this->_objTpl, 'access_');
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
            ($objMail = new \Cx\Core\MailTemplate\Model\Entity\Mail()) !== false
        ) {

            $objMail->SetFrom($objUserMail->getSenderMail(), $objUserMail->getSenderName());
            $objMail->Subject = $objUserMail->getSubject();

            $isTextMail  = in_array($objUserMail->getFormat(), array('multipart', 'text'));
            $isHtmlMail  = in_array($objUserMail->getFormat(), array('multipart', 'html'));
            $searchTerms = array(
                '[[HOST]]',
                '[[USERNAME]]',
                '[[ACTIVATION_LINK]]',
                '[[HOST_LINK]]',
                '[[SENDER]]',
                '[[LINK]]',
                '[[YEAR]]',
            );
            $replaceTextTerms = array(
                $_CONFIG['domainUrl'],
                $objUser->getUsername(),
                'http://'.$_CONFIG['domainUrl'].CONTREXX_SCRIPT_PATH.'?section=Access&cmd=signup&u='.($objUser->getId()).'&k='.$objUser->getRestoreKey(),
                'http://'.$_CONFIG['domainUrl'],
                $objUserMail->getSenderName(),
                'http://'.$_CONFIG['domainUrl'].ASCMS_PATH_OFFSET.ASCMS_BACKEND_PATH.'/index.php?cmd=Access&act=user&tpl=modify&id='.$objUser->getId(),
                date('Y'),
            );
            $replaceHtmlTerms = array(
                $_CONFIG['domainUrl'],
                contrexx_raw2xhtml($objUser->getUsername()),
                'http://'.$_CONFIG['domainUrl'].CONTREXX_SCRIPT_PATH.'?section=Access&cmd=signup&u='.($objUser->getId()).'&k='.$objUser->getRestoreKey(),
                'http://'.$_CONFIG['domainUrl'],
                contrexx_raw2xhtml($objUserMail->getSenderName()),
                'http://'.$_CONFIG['domainUrl'].ASCMS_PATH_OFFSET.ASCMS_BACKEND_PATH.'/index.php?cmd=Access&act=user&tpl=modify&id='.$objUser->getId(),
                date('Y'),
            );
            if ($mail2load == 'reg_confirm') {
                $imagePath = 'http://'.$_CONFIG['domainUrl']
                    . \Cx\Core\Core\Controller\Cx::instanciate()
                    ->getWebsiteImagesAccessProfileWebPath().'/';
                $objUser->objAttribute->first();
                while (!$objUser->objAttribute->EOF) {
                    $objAttribute = $objUser->objAttribute->getById(
                        $objUser->objAttribute->getId()
                    );

                    $placeholderName  = strtoupper($objUser->objAttribute->getId());
                    $searchTerms[]    = '[[USER_' . $placeholderName . ']]';
                    $placeholderValue = $this->parseAttribute($objUser, $objAttribute->getId(), 0, false, true);
                    if (
                        $objAttribute->getType() == 'image' &&
                        $objAttribute->getId() == 'picture'
                    ) {
                        $path = $imagePath.'0_noavatar.gif';
                        $imgName = $objUser->getProfileAttribute($objAttribute->getId());
                        if (\Cx\Lib\FileSystem\FileSystem::exists($imagePath . $imgName)) {
                            $path = $imagePath . $imgName;
                        }
                        $replaceHtmlTerms[] = \Html::getImageByPath($path, 'alt="'.$objUser->getEmail().'"');
                        $replaceTextTerms[] = $path;
                    } else {
                        if (in_array($objUser->objAttribute->getType(), array('text', 'menu'))) {
                            $replaceTextTerms[] = html_entity_decode($placeholderValue, ENT_QUOTES, CONTREXX_CHARSET);
                            $replaceHtmlTerms[] = html_entity_decode($placeholderValue, ENT_QUOTES, CONTREXX_CHARSET);
                        } else {
                            $replaceTextTerms[] = $placeholderValue;
                            $replaceHtmlTerms[] = $placeholderValue;
                        }
                    }
                    $objUser->objAttribute->next();
                }
            }

            if ($isTextMail) {
                $objUserMail->getFormat() == 'text' ? $objMail->IsHTML(false) : false;
                $objMail->{($objUserMail->getFormat() == 'text' ? '' : 'Alt').'Body'} = str_replace(
                    $searchTerms,
                    $replaceTextTerms,
                    $objUserMail->getBodyText()
                );
            }

            if ($isHtmlMail) {
                $objUserMail->getFormat() == 'html' ? $objMail->IsHTML(true) : false;
                $objMail->Body = str_replace(
                    $searchTerms,
                    $replaceHtmlTerms,
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

