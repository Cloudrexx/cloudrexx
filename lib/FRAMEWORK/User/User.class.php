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
 * User Object
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Daeppen <thomas.daeppen@comvation.com>
 * @version     2.1.1
 * @package     cloudrexx
 * @subpackage  lib_framework
 */

/**
 * UserException
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_framework
 */
class UserException extends Exception {}


/**
 * User Object
 *
 * The User object is used for all user related operations.
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Daeppen <thomas.daeppen@comvation.com>
 * @version     2.0.0
 * @package     cloudrexx
 * @subpackage  lib_framework
 */
class User extends User_Profile
{
    /**
     * ID of loaded user
     * @var integer
     * @access private
     */
    protected $id;

    /**
     * Username of user
     * @var string
     * @access private
     */
    private $username;

    /**
     * E-Mail address of user
     * @var string
     * @access private
     */
    private $email;

    /**
     * Password of user
     * @var     string
     * @access  protected
     */
    protected $password;

    /**
     * Token used for auto login
     * @var string
     */
    protected $auth_token;

    /**
     * Timeout used for auto login
     * @var integer
     */
    protected $auth_token_timeout;

    /**
     * Language ID of user
     * @var integer
     * @access private
     */
    private $lang_id;

    /**
     * Language ID used for the frontend
     * @var integer
     * @access private
     */
    private $frontend_language;

    /**
     * Language ID used for the backend
     * @var integer
     * @access private
     */
    private $backend_language;

    /**
     * Active status of user
     * @var boolean
     * @access private
     */
    private $is_active;

    /**
     * verification status of user
     * @var boolean
     */
    protected $verified;

    /**
     * The ID of a user group that should be used as the primary one
     *
     * @var integer
     * @access private
     */
    private $primary_group;

    /**
     * Administrator status
     * @var boolean
     * @access private
     */
    private $is_admin;

    /**
     * Determines who is allowed to see the user's e-mail address
     * @var boolean
     * @access private
     */
    private $email_access;

    /**
     * Determines who is allowed to see the user's profile data.
     * @var string
     * @access private
     */
    private $profile_access;

    /**
     * Registration date of user (timestamp)
     * @var integer
     * @access private
     */
    private $regdate;

    /**
     * Expiration date of the account (timestamp)
     * @var integer
     * @access private
     */
    private $expiration;

    /**
     * Validity time period
     *
     * This integer represents the expiration of the user. A user won't be able to authenticate again after his validity time period has exceeded.
     * A validity time period of zero mean that the account has no expiration date.
     * @var integer
     * @access private
     */
    private $validity;

    /**
     * Key which will be used to reset the password
     * @var string
     * @access private
     */
    private $restore_key;

    /**
     * Date as timestamp when a new password was requested
     * @var integer
     * @access private
     */
    private $restore_key_time;

    /**
     * The networks the user is connected with
     * @var object
     * @access private
     */
    private $networks;

    /**
     * The last time the user had logged in (timestamp)
     * @var integer
     * @access private
     */
    private $last_auth;

    /**
     * If the last time the user try to logged in was successful
     * @var boolean
     * @access private
     */
    private $last_auth_status = 1;

    /**
     * The last time that the user was active (timestamp)
     * @var integer
     * @access private
     */
    private $last_activity;

    /**
     * Contains the number of currently loaded users
     * @var integer
     * @access private
     */
    private $filtered_search_count = 0;

    /**
     * Contains an array of all group IDS which the user is associated to
     * @var array
     * @access private
     */
    private $arrGroups;

    /**
     * Contains an array of all newsletter-list-IDs of which the user has a subscription of
     *
     * @var array
     * @access protected
     */
    private $arrNewsletterListIDs = array();

    /**
     * @access public
     */
    public $EOF;

    /**
     * Array which holds all loaded users for later usage
     * @var array
     * @access protected
     */
    protected $arrLoadedUsers = array();

    /**
     * Array that holds all users which were ever loaded
     * @var array
     * @access protected
     */
    protected  $arrCachedUsers = array();

    /**
     * @access private
     */
    private $arrAttributes = array(
        'id'                => 'int',
        'is_admin'          => 'int',
        'username'          => 'string',
        'regdate'           => 'int',
        'expiration'        => 'int',
        'validity'          => 'int',
        'last_auth'         => 'int',
        'last_activity'     => 'int',
        'primary_group'     => 'int',
        'email'             => 'string',
        'email_access'      => 'string',
        'frontend_lang_id'  => 'int',
        'backend_lang_id'   => 'int',
        'active'            => 'int',
        'verified'          => 'int',
        'profile_access'    => 'string',
        'restore_key'       => 'string',
        'restore_key_time'  => 'int',
    );

    /**
     * @access private
     */
    private $arrPrivacyAccessTypes = array(
        'everyone'      => array(
            'email'         => 'TXT_ACCESS_EVERYONE_ALLOWED_SEEING_EMAIL',
            'profile'       => 'TXT_ACCESS_EVERYONE_ALLOWED_SEEING_PROFILE',
        ),
        'members_only'  => array(
            'email'         => 'TXT_ACCESS_MEMBERS_ONLY_ALLOWED_SEEING_EMAIL',
            'profile'       => 'TXT_ACCESS_MEMBERS_ONLY_ALLOWED_SEEING_PROFILE',
        ),
        'nobody'        => array(
            'email'         => 'TXT_ACCESS_NOBODY_ALLOWED_SEEING_EMAIL',
            'profile'       => 'TXT_ACCESS_NOBODY_ALLOWED_SEEING_PROFILE',
        ),
    );

    /**
     * @access private
     */
    private $defaultProfileAccessTyp;

    /**
     * @access private
     */
    private $defaultEmailAccessType;

    /**
     * Contains the default hash algorithm to be used for password generation
     *
     * @var string
     */
    protected $defaultHashAlgorithm;

    /**
     * Contains the message if an error occurs
     * @var string
     */
    public $error_msg = array();


    /**
     * TRUE if user is authenticated
     *
     * If this is TRUE the methods {@link load()}
     * will be looked for further usage.
     * @todo    Explain this method in plain english...
     * @var     boolean
     * @access  private
     */
    private $loggedIn;


    public function __construct()
    {
        parent::__construct();
        $arrSettings = FWUser::getSettings();
// TODO:  Provide default values here in case the settings are missing!
        $this->defaultProfileAccessTyp = $arrSettings['default_profile_access']['value'];
        $this->defaultEmailAccessType = $arrSettings['default_email_access']['value'];
        $this->defaultHashAlgorithm = \PASSWORD_BCRYPT;
        $this->clean();
    }


    /**
     * Authenticate user against username and password
     *
     * Verifies the password of a username within the database.
     * If the password matches the appropriate users gets loaded
     * and the users last authentication time gets updated.
     * Returns TRUE on success or FALSE on failure.
     * @param   string    $username   The username
     * @param   string    $password   The raw password
     * @param   boolean   $backend    Tries to authenticate for the backend
     *                                if true, false otherwise
     * @return  boolean               True on success, false otherwise
     */
    public function auth($username, $password, $backend = false, $captchaCheckResult = false)
    {
        $userId = $this->checkLoginData($username, $password, $captchaCheckResult);

        if (!$userId || !$this->load($userId) || !$this->hasModeAccess($backend) || !$this->updateLastAuthTime()) {
            return false;
        }

        return true;
    }

    public function authByToken($userId, $authToken, $backend = false)
    {
        $userId = $this->checkAuthToken($userId, $authToken);

        if (!$userId || !$this->load($userId) || !$this->hasModeAccess($backend) || !$this->updateLastAuthTime()) {
            return false;
        }

        return true;
    }


    /**
     * Checks username, password and captcha.
     *
     * @param  string  $username
     * @param  string  $password
     * @param  bool    $captchaCheckResult
     *
     * @return  mixed  false or user id
     */
    public function checkLoginData($username, $password, $captchaCheckResult = false)
    {
        // If the last login has failed and the captcha is wrong the login must be invalid.
        if ($_SESSION['auth']['loginLastAuthFailed'] && !$captchaCheckResult) {
            return false;
        }

        $loginByEmail = false;

        $arrSettings = User_Setting::getSettings();
        if (FWValidator::isEmail($username) || !$arrSettings['use_usernames']['status']) {
            $loginByEmail = true;
        }

// TODO: add verificationTimeout as configuration option
        $verificationExpired = time() - 30 * 86400;
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $userRepo = $em->getRepository('Cx\Core\User\Model\Entity\User');
        $qb = $userRepo->createQueryBuilder('u');
        if ($loginByEmail) {
            $column = 'u.email';
            $loginCheck = $username;
        } else {
            $column = 'u.username';
            $loginCheck = $username;
        }

        $qb->where($qb->expr()->eq($column, ':loginCheck'));

        if ($captchaCheckResult == false) {
            $qb->andWhere($qb->expr()->eq('u.lastAuthStatus', ':lastAuthStatus'));
            $qb->setParameter('lastAuthStatus', 1);
        }

        $qb->andWhere($qb->expr()->eq('u.active', ':active'))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('u.verified', ':verified'),
                    $qb->expr()->gte('u.regdate', ':regdate')
                )
            )->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('u.expiration', ':expirationNull'),
                    $qb->expr()->gt('u.expiration', ':expiration')
                )
            )->setParameter('loginCheck', $loginCheck)
            ->setParameter('active', 1)
            ->setParameter('verified', 1)
            ->setParameter('regdate', $verificationExpired)
            ->setParameter('expirationNull', 0)
            ->setParameter('expiration', time())
            ->setMaxResults(1);

        $user = $qb->getQuery()->getOneOrNullResult();

        // verify that the user is valid and active
        if (empty($user)) {
            return false;
        }
        // verify that the supplied password is valid
        if (!$user->getPassword()->matches($password)) {
            return false;
        }

        // user account is valid and supplied password is also valid
        return $user->getId();
    }

    public function checkAuthToken($userId, $authToken) {
        if (empty($authToken) || empty($userId)) {
            return false;
        }

        // TODO: add verificationTimeout as configuration option
        $verificationExpired = time() - 30 * 86400;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $userRepo = $em->getRepository('Cx\Core\User\Model\Entity\User');
        $qb = $userRepo->createQueryBuilder('u');
        $qb->where($qb->expr()->eq('u.authToken', ':authToken'))
            ->andWhere($qb->expr()->gte('u.authTokenTimeout', ':authTokenTimeout'))
            ->andWhere($qb->expr()->eq('u.active', ':active'))
            ->andWhere($qb->expr()->eq('u.id', ':id'))
            ->andWhere(
                $qb->expr()->orX(
                     $qb->expr()->eq('u.verified', ':verified'),
                    $qb->expr()->gte('u.regdate', ':regdate')
                )
            )->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('u.expiration', ':expirationNull'),
                    $qb->expr()->gt('u.expiration', ':expiration')
                )
            )->setParameters(
                array(
                    'authToken' => $authToken,
                    'authTokenTimeout' => time(),
                    'active' => 1,
                    'id' => intval($userId),
                    'verified' => 1,
                    'regdate' => $verificationExpired,
                    'expirationNull' => 0,
                    'expiration' => time()
                )
            )->setMaxResults(1);

        $user = $qb->getQuery()->getOneOrNullResult();

        if (empty($user)) {
            return false;
        }

        // destroy auth-token
        $user->setAuthToken('');
        $user->setAuthTokenTimeout(0);
        $em->persist($user);
        $em->flush();

        return $user->getId();
    }

    /**
     * Fetch the password hash of the currently loaded user from the database
     *
     * @return  string  Password hash of currently loaded user.
     */
    protected function fetchPasswordHashFromDatabase() {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $userRepo = $em->getRepository('Cx\Core\User\Model\Entity\User');
        $user = $userRepo->find($this->id);

        if (empty($user)) {
            throw new UserException('Unable to load unknown user');
        }

        return $user->getPassword();
    }

    /**
     * Clean user metadata
     *
     * Reset all user metadata for a new user.
     * The metadata includes the ID of the user, the username, e-mail address,
     * password, language ID, active and administration status, registration date,
     * restore key and restore key timeout and the ID's of the associated groups.
     */
    private function clean()
    {
        global $_LANGID;

        $this->id = 0;
        $this->username = '';
        $this->email = '';
        $this->email_access = $this->defaultEmailAccessType;
        $this->password = '';
        $this->auth_token = '';
        $this->auth_token_timeout = 0;
        $this->frontend_language = $_LANGID;
        $this->backend_language = $_LANGID;
        $this->is_active = false;
        $this->verified = true;
        $this->primary_group = 0;
        $this->is_admin = false;
        $this->profile_access = $this->defaultProfileAccessTyp;
        $this->regdate = 0;
        $this->expiration = 0;
        $this->validity = 0;
        $this->last_auth = 0;
        $this->last_activity = 0;
        $this->restore_key = '';
        $this->restore_key_time = 0;
        $this->arrGroups = null;
        $this->arrNewsletterListIDs = null;
        $this->EOF = true;
        $this->loggedIn = false;
        $this->networks = null;
    }


    /**
     * Delete the current loaded user account
     *
     * In the case that the current loaded user is the last available administrator
     * in the system, then the request will be refused and FALSE will be returned instead.
     * A user isn't able to delete its own account with which he is actually authenticated
     * at the moment unless the parameter $deleteOwnAccount is set to TRUE.
     * Returns TRUE on success or FALSE on failure.
     * @param   boolean       $deleteOwnAccount
     * @see     isLastAdmin()
     * @return boolean
     */
    public function delete($deleteOwnAccount = false)
    {
        global $objDatabase, $_CORELANG;

        $objFWUser = FWUser::getFWUserObject();
        if ($deleteOwnAccount || $this->id != $objFWUser->objUser->getId()) {
            if (!$this->isLastAdmin()) {
                \Env::get('cx')->getEvents()->triggerEvent('model/preRemove', array(new \Doctrine\ORM\Event\LifecycleEventArgs($this, \Env::get('em'))));
                $cx = \Cx\Core\Core\Controller\Cx::instanciate();
                $em = $cx->getDb()->getEntityManager();
                $userRepo = $em->getRepository('Cx\Core\User\Model\Entity\User');
                $user = $userRepo->find($this->id);

                if (empty($user)) {
                    return false;
                }

                $objDatabase->startTrans();
                if ($objDatabase->Execute('DELETE FROM `'.DBPREFIX.'access_user_network` WHERE `user_id` = ' . $this->id) !== false) {
                    $objDatabase->completeTrans();
                } else {
                    $objDatabase->failTrans();
                    $objDatabase->completeTrans();
                }

                try {
                    $em->remove($user);
                    $em->flush();

                    \Env::get('cx')->getEvents()->triggerEvent('model/postRemove', array(new \Doctrine\ORM\Event\LifecycleEventArgs($this, \Env::get('em'))));
                    //Clear cache
                    $cx->getEvents()->triggerEvent(
                        'clearEsiCache',
                        array(
                            'Widget',
                            $cx->getComponent('Access')->getUserDataBasedWidgetNames(),
                        )
                    );
                    \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Cache')->deleteComponentFiles('Access');

                    return true;
                } catch (\Doctrine\ORM\OptimisticLockException $e) {
                    $this->error_msg[] = sprintf($_CORELANG['TXT_ACCESS_USER_DELETE_FAILED'], $this->username);
                }
            } else {
                $this->error_msg[] = sprintf($_CORELANG['TXT_ACCESS_LAST_ADMIN_USER'], $this->username);
            }
        } else {
            $this->error_msg[] = $_CORELANG['TXT_ACCESS_UNABLE_DELETE_YOUR_USER'];
        }

        return false;
    }

    public static function forceDefaultEmailAccess()
    {
        $arrSettings = FWUser::getSettings();
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $userRepo = $em->getRepository('Cx\Core\User\Model\Entity\User');
        $users = $userRepo->findAll();

        try {
            foreach ($users as $user) {
                $user->setEmailAccess($arrSettings['default_email_access']['value']);
                $em->persist($user);
            }
            $em->flush();
            return true;
        } catch (\Doctrine\ORM\OptimisticLockException $e) {
            return false;
        }
    }


    public static function forceDefaultProfileAccess()
    {
        $arrSettings = FWUser::getSettings();
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $userRepo = $em->getRepository('Cx\Core\User\Model\Entity\User');
        $users = $userRepo->findAll();

        try {
            foreach ($users as $user) {
                $user->setProfileAccess($arrSettings['default_profile_access']['value']);
                $em->persist($user);
            }
            $em->flush();
            return true;
        } catch (\Doctrine\ORM\OptimisticLockException $e) {
            return false;
        }
    }


    public function getActiveStatus()
    {
        return $this->is_active;
    }


    public function isVerified()
    {
        return $this->verified;
    }


    public function getPrimaryGroupId()
    {
        if (empty($this->primary_group)) {
            $FWUser = FWUser::getFWUserObject();
            $this->arrGroups = $this->loadGroups(!$FWUser->isBackendMode());
            return count($this->arrGroups) ? $this->arrGroups[0] : 0;
        }
        return $this->primary_group;
    }


    public function getPrimaryGroupName()
    {
        $objFWUser = FWUser::getFWUserObject();
        if (empty($this->primary_group)) {
            $this->arrGroups = $this->loadGroups(!$objFWUser->isBackendMode());
            $groupId = isset($this->arrGroups[0]) ? $this->arrGroups[0] : 0;
        } else {
            $groupId = $this->primary_group;
        }

        $objGroup = $objFWUser->objGroup->getGroup($groupId);
        return htmlentities($objGroup->getName(), ENT_QUOTES, CONTREXX_CHARSET);
    }


    public function getAdminStatus()
    {
        return $this->is_admin;
    }


    /**
     * Returns an array containing the ids of the user's associated groups
     * @param boolean $activeOnly Wether to load only the active groups or all
     * @return array
     */
    public function getAssociatedGroupIds($activeOnly=false)
    {
        if (!isset($this->arrGroups)) {
            $this->arrGroups = $this->loadGroups($activeOnly);
        }
        return $this->arrGroups;
    }


    /**
     * Returns an array of all newsletter-list-IDs the user did subscribe to
     * @return      array   Newsletter-list-IDs
     */
    public function getSubscribedNewsletterListIDs()
    {
        if (!isset($this->arrNewsletterListIDs)) {
            $this->loadSubscribedNewsletterListIDs();
        }
        return $this->arrNewsletterListIDs;
    }


    public function getBackendLanguage()
    {
        if (!$this->backend_language) {
            global $_LANGID;
            $this->backend_language = $_LANGID;
        }
        return $this->backend_language;
    }


    public function getDynamicPermissionIds($reload=false)
    {
        if (!isset($this->arrCachedUsers[$this->id]['dynamic_access_ids']) || $reload) {
            $this->loadPermissionIds('dynamic');
        }
        return $this->arrCachedUsers[$this->id]['dynamic_access_ids'];
    }


    public function getEmail()
    {
        // START: WORKAROUND FOR ACCOUNTS SOLD IN THE SHOP
        $email = $this->getShopUserEmail();
        return (empty($email) ? $this->email : $email);
        // END: WORKAROUND FOR ACCOUNTS SOLD IN THE SHOP
    }


    public function getEmailAccess()
    {
        return $this->email_access;
    }


    public function getErrorMsg()
    {
        return $this->error_msg;
    }


    public function getExpirationDate()
    {
        return $this->expiration;
    }

    public function getFrontendLanguage()
    {
        if (!$this->frontend_language) {
            global $_LANGID;
            $this->frontend_language = $_LANGID;
        }
        return $this->frontend_language;
    }


    public function getId()
    {
        return $this->id;
    }
    
    public function getLastActivityTime()
    {
        return $this->last_activity;
    }


    public function getLastAuthenticationTime()
    {
        return $this->last_auth;
    }


    public function getPrivacyAccessMenu($attrs, $option)
    {
        global $_ARRAYLANG;

        $menu = '<select'.(!empty($attrs) ? ' '.$attrs : '').'>';
        foreach ($this->arrPrivacyAccessTypes as $type => $arrDesc) {
            $menu .= "<option value='".$type."'".($type == $this->{$option.'_access'} ? ' selected="selected"' : '').">".$_ARRAYLANG[$arrDesc[$option]]."</option>";
        }
        $menu .= '</select>';
        return $menu;
    }


    public function getProfileAccess()
    {
        return $this->profile_access;
    }


    public function getProfileAttribute($attributeId, $historyId = 0)
    {
        $attributeId = $this->objAttribute->getDefaultAttributeIdByAttributeId($attributeId);

        $value = false;
        if (isset($this->arrLoadedUsers[$this->id]['profile'][$attributeId][$historyId])) {
            $value = $this->arrLoadedUsers[$this->id]['profile'][$attributeId][$historyId];
        } else if (isset($this->arrCachedUsers[$this->id]['profile'][$attributeId][$historyId])) {
            $value = $this->arrCachedUsers[$this->id]['profile'][$attributeId][$historyId];
        }

        if ($attributeId == 'gender') {
            if ($value == 0) {
                $value = 'gender_undefined';
            } else {
                $value = $this->objAttribute->getDefaultAttributeIdByAttributeId(
                    $value
                );
            }
        }

        return $value;
    }


    public function getRegistrationDate()
    {
        return $this->regdate;
    }


    public function getRestoreKey()
    {
        return $this->restore_key;
    }


    public function getRestoreKeyTime()
    {
        return $this->restore_key_time;
    }


    public function getStaticPermissionIds($reload=false)
    {
        if (!isset($this->arrCachedUsers[$this->id]['static_access_ids']) || $reload) {
            $this->loadPermissionIds('static');
        }
        return $this->arrCachedUsers[$this->id]['static_access_ids'];
    }

    /**
     * Fetch instance of User specified by ID $id.
     *
     * @param   integer $id The ID of the user to fetch
     * @param   boolean $forceReload    Set to TRUE to refetch the user
                                        from the database. Otherwise, the
                                        user will be loaded from cache,
                                        in case it has been loaded
                                        before. Defaults to FALSE.
     * @return  mixed   Instance of \User if successful. Otherwise FALSE.
     */
    public function getUser($id, $forceReload = false)
    {
        $objUser = clone $this;
        $objUser->arrCachedUsers = &$this->arrCachedUsers;
        if ($objUser->load($id, $forceReload)) {
            return $objUser;
        }
        return false;
    }


    public function getUsername()
    {
        $arrSettings = User_Setting::getSettings();
        if (!$arrSettings['use_usernames']['status'] || empty($this->username)) {
            return $this->getEmail();
        }
        return $this->username;
    }


    public function getRealUsername()
    {
        return $this->username;
    }


    public function getValidityTimePeriod()
    {
        return $this->validity;
    }


    /**
     * Load user data
     *
     * Load all user data (username, email, lang_id, is_active, etc.) of
     * the user specified by ID $id into the current instance.
     *
     * @param integer $id   The ID of the user to load
     * @param   boolean $forceReload    Set to TRUE to refetch the user
                                        from the database. Otherwise, the
                                        user will be loaded from cache,
                                        in case it has been loaded
                                        before. Defaults to FALSE.
     * @throws UserException
     * @return boolean  TRUE on success, otherwise FALSE
     */
    private function load($id, $forceReload = false)
    {
        global $_LANGID;

        if ($this->isLoggedIn()) {
            throw new UserException("User->load(): Illegal method call - try getUser()!");
        }
        if ($id) {
            if ($forceReload || !isset($this->arrCachedUsers[$id])) {
                $this->loadUser($id);
            }
            $this->id = $id;
            $this->username = isset($this->arrCachedUsers[$id]['username']) ? $this->arrCachedUsers[$id]['username'] : '';
            $this->auth_token = '';
            $this->auth_token_timeout = 0;
            $this->email = isset($this->arrCachedUsers[$id]['email']) ? $this->arrCachedUsers[$id]['email'] : '';
            $this->email_access = isset($this->arrCachedUsers[$id]['email_access']) ? $this->arrCachedUsers[$id]['email_access'] : $this->defaultEmailAccessType;
            $this->frontend_language = isset($this->arrCachedUsers[$id]['frontend_lang_id']) ? $this->arrCachedUsers[$id]['frontend_lang_id'] : $_LANGID;
            $this->backend_language = isset($this->arrCachedUsers[$id]['backend_lang_id']) ? $this->arrCachedUsers[$id]['backend_lang_id'] : $_LANGID;
            $this->is_active = isset($this->arrCachedUsers[$id]['active']) ? (bool)$this->arrCachedUsers[$id]['active'] : false;
            $this->verified = isset($this->arrCachedUsers[$id]['verified']) ? (bool)$this->arrCachedUsers[$id]['verified'] : true;
            $this->primary_group = isset($this->arrCachedUsers[$id]['primary_group']) ? $this->arrCachedUsers[$id]['primary_group'] : 0;
            $this->is_admin = isset($this->arrCachedUsers[$id]['is_admin']) ? (bool)$this->arrCachedUsers[$id]['is_admin'] : false;
            $this->regdate = isset($this->arrCachedUsers[$id]['regdate']) ? $this->arrCachedUsers[$id]['regdate'] : 0;
            $this->expiration = isset($this->arrCachedUsers[$id]['expiration']) ? $this->arrCachedUsers[$id]['expiration'] : 0;
            $this->validity = isset($this->arrCachedUsers[$id]['validity']) ? $this->arrCachedUsers[$id]['validity'] : 0;
            $this->last_auth = isset($this->arrCachedUsers[$id]['last_auth']) ? $this->arrCachedUsers[$id]['last_auth'] : 0;
            $this->last_activity = isset($this->arrCachedUsers[$id]['last_activity']) ? $this->arrCachedUsers[$id]['last_activity'] : 0;
            $this->profile_access = isset($this->arrCachedUsers[$id]['profile_access']) ? $this->arrCachedUsers[$id]['profile_access'] : $this->defaultProfileAccessTyp;
            $this->restore_key = isset($this->arrCachedUsers[$id]['restore_key']) ? $this->arrCachedUsers[$id]['restore_key'] : '';
            $this->restore_key_time = isset($this->arrCachedUsers[$id]['restore_key_time']) ? $this->arrCachedUsers[$id]['restore_key_time'] : 0;
            $this->password = '';
            $this->arrGroups = null;
            $this->arrNewsletterListIDs = null;
            $this->networks = isset($this->arrCachedUsers[$id]['networks']) ? $this->arrCachedUsers[$id]['networks'] : new \Cx\Lib\User\User_Networks($id);
            $this->EOF = false;
            $this->loggedIn = false;
            return true;
        }
        $this->clean();
// TODO:  I guess this is wrong, then.
        return false;
    }

    /**
     * Loads a User object according to the given ID
     *
     * @param int $id ID of the user to be searched for
     */
    protected function loadUser($id)
    {
        if ($this->isLoggedIn()) {
            $arrDebugBackTrace =  debug_backtrace();
            die("User->loadUser(): Illegal method call in {$arrDebugBackTrace[0]['file']} on line {$arrDebugBackTrace[0]['line']}!");
        }

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $userRepo = $em->getRepository('Cx\Core\User\Model\Entity\User');
        $user = $userRepo->find($id);

        $this->arrLoadedUsers = array();
        if (empty($user)) {
            return;
        }

        $classMeta = $em->getClassMetadata('Cx\Core\User\Model\Entity\User');

        $id = $user->getId();
        foreach (array_keys($this->arrAttributes) as $column) {
            $getter = 'get' . \Doctrine\Common\Inflector\Inflector::classify($classMeta->getFieldName($column));
            $value = $user->$getter();
            $this->arrCachedUsers[$id][$column] = $value;
            $this->arrLoadedUsers[$id][$column] = $value;
        }

        foreach ($user->getUserAttributeValues() as $attributeValue) {
            $value = $attributeValue->getValue();
            $attributeId = $attributeValue->getUserAttribute()->getId();
            $convertedAttributeId = $this->objAttribute->getDefaultAttributeIdByAttributeId($attributeId);
            // default attributes like 'title' or 'firstname'
            $this->arrCachedUsers[$id]['profile'][$convertedAttributeId][0] = $value;
            $this->arrLoadedUsers[$id]['profile'][$convertedAttributeId][0] = $value;
        }

        $network = new \Cx\Lib\User\User_Networks($id);
        $this->arrCachedUsers[$id]['networks'] = $network;
        $this->arrLoadedUsers[$id]['networks'] = $network;
    }

    public function __clone()
    {
        $this->clean();
    }


    public function generateAuthToken() {
        $this->setAuthToken(bin2hex(openssl_random_pseudo_bytes(16)));
        return $this->auth_token;
    }

    public function setAuthToken($authToken) {
        global $_CONFIG;

        $this->auth_token = $authToken;
        $this->auth_token_timeout = time() + $_CONFIG['sessionLifeTime'];
    }

    /**
     * Set the restore-key
     *
     * The restore-key is used to reset the password or used for
     * the user verification process.
     *
     * @param   string  $restoreKey The restore-key to set. Must be a
     *                              MD5-hash. If left empty, a new
     *                              restore-key will be generated.
     */
    public function setRestoreKey($restoreKey = null)
    {
        if ($restoreKey) {
            $this->restore_key = $restoreKey;
            return;
        }

        $this->restore_key = md5($this->email . random_bytes(20));
        $this->restore_key_time = time() + 3600;
    }

    /**
     * Set the restore-key validity timeout
     *
     * @param   integer $seconds    Timeout specified in seconds.
     * @param   boolean $absolute   If set to TRUE, the argument
     *                              $seconds will be interpreted as
     *                              timestamp instead. Defaults to FALSE.
     */
    public function setRestoreKeyTime($seconds, $absolute = false)
    {
        if ($absolute) {
            $this->restore_key_time = $seconds;
        } else {
            $this->restore_key_time = time() + $seconds;
        }
    }


    public function releaseRestoreKey()
    {
        $this->restore_key = '';
        $this->restore_key_time = 0;
        return true;
    }


    /**
     * Load group ID's of user
     *
     * Returns an array with the ID's of all groups to which
     * the user is associated to.
     * @param boolean $onlyActiveGroups
     * @global ADONewConnection
     * @return mixed array on success, FALSE on failure
     */
    private function loadGroups($onlyActiveGroups=false)
    {
        global $objDatabase;

        $objResult = $objDatabase->Execute('
            SELECT tblRel.`group_id`
              FROM `'.DBPREFIX.'access_rel_user_group` AS tblRel
             INNER JOIN `'.DBPREFIX.'access_user_groups` AS tblGroup
             USING (`group_id`)
             WHERE tblRel.`user_id`='.$this->id.
            ($onlyActiveGroups ? ' AND tblGroup.`is_active` = 1' : '')
        );
        if (!$objResult) {
            return false;
        }
        $arrGroups = array();
        while (!$objResult->EOF) {
            array_push($arrGroups, $objResult->fields['group_id']);
            $objResult->MoveNext();
        }
        return $arrGroups;
    }

    private function loadSubscribedNewsletterListIDs()
    {
        global $objDatabase;

        $this->arrNewsletterListIDs = array();

        $objResult = $objDatabase->Execute('
            SELECT
                `newsletterCategoryID`
            FROM
                `'.DBPREFIX.'module_newsletter_access_user`
            WHERE `accessUserID`='.$this->id
        );

        if ($objResult) {
            while (!$objResult->EOF) {
                $this->arrNewsletterListIDs[] = $objResult->fields['newsletterCategoryID'];
                $objResult->MoveNext();
            }
        }
    }

    public function reset()
    {
        $this->clean();
    }


    /**
     * Load next user
     */
    public function next()
    {
        if (next($this->arrLoadedUsers) === false || !$this->load(key($this->arrLoadedUsers))) {
            $this->EOF = true;
        }
    }


    public function signUp()
    {
        $arrSettings = User_Setting::getSettings();
        if ($arrSettings['user_activation']['status']) {
            $this->restore_key = md5($this->username.$this->getHashedPassword().time());
            $this->restore_key_time = $arrSettings['user_activation_timeout']['status'] ? time() + $arrSettings['user_activation_timeout']['value'] * 3600 : 0;
        }
        return $this->store();
    }


    /**
     * Store user account
     *
     * This stores the metadata of the user, which includes the username,
     * password, email, language ID, activ status and the administration status,
     * to the database.
     * If it is a new user, it also sets the registration time to the current time.
     * @global ADONewConnection
     * @global array
     * @return boolean
     */
    public function store()
    {
        global $objDatabase, $_CORELANG, $_LANGID;

        //for calling postPersist and postUpdate based on $callPostUpdateEvent
        $callPostUpdateEvent = $this->id;
        $generatedPassword = '';

        // Track if a user account change is being flushed to the database.
        // If so, we'll trigger the postUpdate event, but only in that case.
        // Explanation: A flush would indicate that the user object has actually been altered.
        // This is a pseudo emulation of doctrine's own event system behavior which triggers
        // the postUpdate event on an entity only in case the entity has actually been altered.
        $userChangeStatus = null;

        if (!$this->validateUsername()) {
            return false;
        }
        if (!$this->validateEmail()) {
            return false;
        }
        if ($this->networks) {
            $this->networks->save();
        }

        if ($this->id) {
            // update existing account
            \Env::get('cx')->getEvents()->triggerEvent('model/preUpdate', array(new \Doctrine\ORM\Event\LifecycleEventArgs($this, \Env::get('em'))));
            $this->updateUser($userChangeStatus);
        } else {
            // add new account
            if(\FWValidator::isEmpty($this->getHashedPassword())){
                $generatedPassword = $this->make_password();
                $this->setPassword($generatedPassword);
            }

            \Env::get('cx')->getEvents()->triggerEvent('model/prePersist', array(new \Doctrine\ORM\Event\LifecycleEventArgs($this, \Env::get('em'))));
            $this->createUser();

            if(!\FWValidator::isEmpty($generatedPassword)) {
                $this->sendUserAccountInvitationMail($generatedPassword);
            }
        }

        if (!$this->storeGroupAssociations($userChangeStatus)) {
            $this->error_msg[] = $_CORELANG['TXT_ARRAY_COULD_NOT_SET_GROUP_ASSOCIATIONS'];
            return false;
        }

        if (!$this->storeNewsletterSubscriptions($userChangeStatus)) {
            $this->error_msg[] = $_CORELANG['TXT_ARRAY_COULD_NOT_SET_NEWSLETTER_ASSOCIATIONS'];
            return false;
        }

        if (!$this->storeProfile($userChangeStatus)) {
            $this->error_msg[] = $_CORELANG['TXT_ACCESS_FAILED_STORE_PROFILE'];
            return false;
        }

        if (!empty($callPostUpdateEvent)) {
            // only trigger postUpdate event in case an actual change on the user object has been flushed to the database
            if ($userChangeStatus) {
                \Env::get('cx')->getEvents()->triggerEvent('model/postUpdate', array(new \Doctrine\ORM\Event\LifecycleEventArgs($this, \Env::get('em'))));

                // Clear cache
                $cx = \Cx\Core\Core\Controller\Cx::instanciate();
                $cx->getEvents()->triggerEvent(
                    'clearEsiCache',
                    array(
                        'Widget',
                        $cx->getComponent('Access')->getUserDataBasedWidgetNames(),
                    )
                );
                \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Cache')->deleteComponentFiles('Access');
            }
        } else {
            \Env::get('cx')->getEvents()->triggerEvent('model/postPersist', array(new \Doctrine\ORM\Event\LifecycleEventArgs($this, \Env::get('em'))));

            // Clear cache
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $cx->getEvents()->triggerEvent(
                'clearEsiCache',
                array(
                    'Widget',
                    $cx->getComponent('Access')->getUserDataBasedWidgetNames(),
                )
            );
            \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Cache')->deleteComponentFiles('Access');
        }

        \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Cache')->getCacheDriver()->flushAll();

        return true;
    }

    /**
     * Send a invitation mail to the created user.
     * It used the old mail function
     *
     * @param string $generatedPassword
     */
    protected function sendUserAccountInvitationMail($generatedPassword) {

        $objUserMail = \FWUser::getFWUserObject()->getMail();
        if (
            (
                $objUserMail->load('user_account_invitation', $_LANGID) ||
                $objUserMail->load('user_account_invitation')
            ) &&
            ($objMail = new \Cx\Core\MailTemplate\Model\Entity\Mail()) !== false
        ) {

            $objMail->SetFrom($objUserMail->getSenderMail(), $objUserMail->getSenderName());
            $objMail->Subject = $objUserMail->getSubject();

            $placeholders = array(
                            '[[WEBSITE]]',
                            '[[FIRSTNAME]]',
                            '[[LASTNAME]]',
                            '[[EMAIL]]',
                            '[[PASSWORD]]',
                            '[[LINK]]',
                            '[[SENDER]]',
                            '[[YEAR]]',
                        );
            $domainRepository = new \Cx\Core\Net\Model\Repository\DomainRepository();
            $mainDomain = $domainRepository->getMainDomain()->getName();
            $placeholdersVal = array(
                            $mainDomain,
                            contrexx_raw2xhtml($this->getProfileAttribute('firstname')),
                            contrexx_raw2xhtml($this->getProfileAttribute('lastname')),
                            $this->getEmail(),
                            $generatedPassword,
                            ASCMS_PROTOCOL . '://'.$mainDomain.\Cx\Core\Core\Controller\Cx::getBackendFolderName(),
                            contrexx_raw2xhtml($objUserMail->getSenderName()),
                            date('Y'),
                        );

            if (in_array($objUserMail->getFormat(), array('multipart', 'text'))) {
                $objUserMail->getFormat() == 'text' ? $objMail->IsHTML(false) : false;
                $objMail->{($objUserMail->getFormat() == 'text' ? '' : 'Alt').'Body'} = str_replace(
                    $placeholders,
                    $placeholdersVal,
                    $objUserMail->getBodyText()
                );
            }
            if (in_array($objUserMail->getFormat(), array('multipart', 'html'))) {
                $objUserMail->getFormat() == 'html' ? $objMail->IsHTML(true) : false;
                $objMail->Body = str_replace(
                    $placeholders,
                    $placeholdersVal,
                    $objUserMail->getBodyHtml()
                );
            }

            $objMail->AddAddress($this->getEmail());

            $objMail->Send();
        }
    }

    /**
     * @param  mixed    $userChanged    If $userChanged is provided, then in case any account
     *                                  changes are being flushed to the database, $userChanged
     *                                  will be set to TRUE, otherwise it'll be left untouched.
     */
    protected function updateUser(&$userChanged = null) {
        global $_CORELANG;

        $passwordHasChanged = false;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $userRepo = $em->getRepository('Cx\Core\User\Model\Entity\User');
        $user = $userRepo->find($this->id);

        if (empty($user)) {
            $userChanged = false;
            return false;
        }

        // check if we have to drop any sessions  due to password change
        if (!empty($this->password)) {
            // check if we are about to set a new different password
            if ($user->getPassword() == $this->password) {
                $passwordHasChanged = true;
            }
        }

        $user->setUsername($this->username);
        $user->setSuperUser(intval($this->is_admin));
        $user->setEmail($this->email);
        $user->setEmailAccess($this->email_access);
        $user->setFrontendLangId($this->frontend_language);
        $user->setBackendLangId($this->backend_language);
        $user->setExpiration(intval($this->expiration));
        $user->setValidity(intval($this->validity));
        $user->setActive(intval($this->is_active));
        $user->setVerified(intval($this->verified));
        $user->setPrimaryGroup(intval($this->primary_group));
        $user->setProfileAccess($this->profile_access);
        $user->setRestoreKey($this->restore_key);
        $user->setRestoreKeyTime($this->restore_key_time);


        if (!empty($this->password)) {
            $user->setPassword(
                new \Cx\Core\Model\Model\Entity\Password($this->password)
            );
        }

        if (!empty($this->auth_token)) {
            $user->setAuthToken($this->auth_token);
            $user->setAuthTokenTimeout($this->auth_token_timeout);
        }

        try {
            $em->persist($user);
            $em->flush();

            $userChanged = true;
        } catch (\Doctrine\ORM\OptimisticLockException $e) {
            $this->error_msg[] = $_CORELANG['TXT_ACCESS_FAILED_TO_UPDATE_USER_ACCOUNT'];
            return false;
        }

        if ($passwordHasChanged) {
            // deletes all sessions which are using this user (except the session changing the password)
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $session = $cx->getComponent('Session')->getSession();
            $session->cmsSessionDestroyByUserId($this->id);
        }
    }

    protected function createUser() {
        global $_CORELANG;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $user = new \Cx\Core\User\Model\Entity\User();

        $user->setUsername($this->username);
        $user->setSuperUser(intval($this->is_admin));
        $user->setPassword($this->password);
        $user->setAuthToken($this->auth_token);
        $user->setAuthTokenTimeout($this->auth_token_timeout);
        $user->setEmail($this->email);
        $user->setEmailAccess($this->email_access);
        $user->setFrontendLangId($this->frontend_language);
        $user->setBackendLangId($this->backend_language);
        $user->setRegdate(time());
        $user->setExpiration(intval($this->expiration));
        $user->setValidity(intval($this->validity));
        $user->setLastAuth($this->last_auth);
        $user->setLastAuthStatus($this->last_auth_status);
        $user->setLastActivity($this->last_activity);
        $user->setActive(intval($this->is_active));
        $user->setVerified(intval($this->verified));
        $user->setPrimaryGroup(intval($this->primary_group));
        $user->setProfileAccess($this->profile_access);
        $user->setRestoreKey($this->restore_key);
        $user->setRestoreKeyTime($this->restore_key_time);

        try {
            $em->persist($user);
            $em->flush();

            $this->id = $user->getId();

            if (!$this->createProfile()) {
                $this->delete();
                $this->error_msg[] = $_CORELANG['TXT_ACCESS_FAILED_TO_ADD_USER_ACCOUNT'];
                return false;
            }
        } catch (\Doctrine\ORM\OptimisticLockException $e) {
            $this->error_msg[] = $_CORELANG['TXT_ACCESS_FAILED_TO_ADD_USER_ACCOUNT'];
            return false;
        }
    }

    /**
     * Add or update a network connection of the user
     *
     * @param string $oauth_provider the name of the provider
     * @param string $oauth_id the user id on the side of the provider
     */
    public function setNetwork($oauth_provider, $oauth_id) {
        $this->networks->setNetwork($oauth_provider, $oauth_id);
    }

    /**
     * Check whether it exists a user with the data
     *
     * @param string $oauth_provider the name of the provider
     * @param string $oauth_id the user id on the side of the provider
     * @return object the user object
     */
    public function getByNetwork($oauth_provider, $oauth_id) {
        global $objDatabase;
        self::removeOutdatedAccounts();

        $query = "SELECT tblN.`user_id` FROM `" . DBPREFIX . "access_user_network` AS tblN
                  WHERE tblN.`oauth_provider` = ? AND tblN.`oauth_id` = ?";
        $objResult = $objDatabase->SelectLimit($query, 1, -1, array($oauth_provider, $oauth_id));
        if ($objResult !== false) {
            $user = $this->getUser($objResult->fields['id']);
            if ($user->is_active) {
                return $user;
            }
        }
        return null;
    }

    /**
     * @return object
     */
    public function getNetworks() {
        return $this->networks;
    }

    /**
     * Load the network data
     */
    public function loadNetworks() {
        $this->networks = new \Cx\Lib\User\User_Networks($this->id);
    }

    /**
     * Store group associations
     *
     * Stores the group associations of the loaded user.
     * Returns TRUE on success, FALSE on failure.
     * @param  mixed    $associationChange    If $associationChange is provided, then in case any
     *                                      group association changes are being flushed to the
     *                                      database, $associationChange will be set to TRUE,
     *                                      otherwise it'll be left untouched.
     * @global ADONewConnection
     * @return boolean
     */
    private function storeGroupAssociations(&$associationChange = null)
    {
        global $objDatabase;

        $status = true;
        $arrCurrentGroups = $this->loadGroups();
        $arrAddedGroups = array_diff($this->getAssociatedGroupIds(), $arrCurrentGroups);
        $arrRemovedGroups = array_diff($arrCurrentGroups, $this->getAssociatedGroupIds());
        $groupAssociationChange = false;
        foreach ($arrRemovedGroups as $groupId) {
            if (!$objDatabase->Execute('
                DELETE FROM `'.DBPREFIX.'access_rel_user_group`
                 WHERE `group_id`='.$groupId.'
                   AND `user_id`='.$this->id)) {
                $status = false;
            } elseif ($objDatabase->Affected_Rows()) {
                // track flushed db change
                $groupAssociationChange = true;
            }
        }
        foreach ($arrAddedGroups as $groupId) {
            if (!$objDatabase->Execute('
                INSERT INTO `'.DBPREFIX.'access_rel_user_group` (
                    `user_id`, `group_id`
                ) VALUES (
                    '.$this->id.', '.$groupId.'
                )')) {
                $status = false;
            } elseif ($objDatabase->Affected_Rows()) {
                // track flushed db change
                $groupAssociationChange = true;
            }
        }
        if ($groupAssociationChange) {
            $associationChange = true;

            // flush all user based cache to ensure new permissions are enforced
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $cache = $cx->getComponent('Cache');
            $cache->clearUserBasedPageCache();
            $cache->clearUserBasedEsiCache();
        }
        return $status;
    }


    /**
     * Store the user's newsletter-list-subscriptions to the database
     * @param  mixed    $subscriptionChange    If $subscriptionChange is provided, then in case any
     *                                      newsletter list subscription changes are being
     *                                      flushed to the database, $subscriptionChange will
     *                                      be set to TRUE, otherwise it'll be left untouched.
     * @return      bool
     */
    private function storeNewsletterSubscriptions(&$subscriptionChange = null)
    {
        global $objDatabase;

        if (!isset($this->arrNewsletterListIDs)) {
            return true;
        }

        $categories = $this->arrNewsletterListIDs;

        if (count($categories)) {
            foreach (array_keys($categories) as $key) {
                // Make sure they're integers
                $categories[$key] = intval($categories[$key]);
                $query = sprintf('
                    INSERT IGNORE INTO `%smodule_newsletter_access_user` (
                        `accessUserId`, `newsletterCategoryID`, `code`
                    ) VALUES (
                        %s, %s, \'%s\'
                    )',
                    DBPREFIX,
                    $this->id,
                    $categories[$key],
                    \Cx\Modules\Newsletter\Controller\NewsletterLib::_emailCode()
                );
                $objDatabase->Execute($query);
                if ($objDatabase->Affected_Rows()) {
                    // track flushed db change
                    $subscriptionChange = true;
                }
            }
            $delString = implode(',', $categories);
            $query = sprintf('
                DELETE FROM `%smodule_newsletter_access_user`
                WHERE `newsletterCategoryID` NOT IN (%s)
                AND `accessUserId`=%s',
                DBPREFIX,
                $delString,
                $this->id
            );
        } else {
            $query = sprintf('
                DELETE FROM `%smodule_newsletter_access_user`
                WHERE `accessUserId`=%s',
                DBPREFIX,
                $this->id
            );

        }

        if ($objDatabase->Execute($query) === false) {
            return false;
        }

        if ($objDatabase->Affected_Rows()) {
            // track flushed db change
            $subscriptionChange = true;
        }

        return true;
    }


    private static function removeOutdatedAccounts()
    {
        global $objDatabase;
        static $userActivationTimeoutStatus = null;

        if (!isset($userActivationTimeoutStatus)) {
            $arrSettings = User_Setting::getSettings();
            $userActivationTimeoutStatus =
                !empty($arrSettings['user_activation_timeout']['status']);
        }

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('u')
            ->from('Cx\Core\User\Model\Entity\User', 'u')
            ->where($qb->expr()->eq('u.active', ':active'))
            ->andWhere($qb->expr()->not($qb->expr()->eq('u.restoreKey', ':restoreKey')))
            ->andWhere($qb->expr()->lt('u.restoreKeyTime', ':restoreKeyTime'))
            ->setParameters(array(
                'active' => 0,
                'restoreKey' => '',
                'restoreKeyTime' => time()
            ));

        $users = $qb->getQuery()->getResult();

        $userIds = array();
        foreach ($users as $user) {
            $userIds[] = $user->getId();
            $em->remove($user);
        }

        $em->flush();
        $objDatabase->Execute('
                DELETE tblN
                  FROM `'.DBPREFIX.'access_user_network` AS tblN
                  WHERE tblN.`user_id`= ('.implode(',', $userIds).');'
        );
    }


    /**
     * Returns true if the User name is valid and unique
     * @return  boolean       True if the User name is valid and unique,
     *                        false otherwise
     * @access  public        Called from the Shop!
     */
    public function validateUsername()
    {
        global $_CORELANG;

        if (empty($this->username)) {
            return true;
        }
        if (self::isValidUsername($this->username)) {
            if (self::isUniqueUsername($this->username, $this->id)) {
                return true;
            } else {
                $this->error_msg[] = $_CORELANG['TXT_ACCESS_USERNAME_ALREADY_USED'];
            }
        } else {
            $this->error_msg[] = $_CORELANG['TXT_ACCESS_INVALID_USERNAME'];
        }

        return false;
    }

    /**
     * Returns true if the User is logged in
     * @return  boolean       True if the User is logged in,
     *                        false otherwise
     */
    public function isLoggedIn()
    {
        return $this->loggedIn;
    }


    public function login($backend = false)
    {

        if ($this->loggedIn) return true;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $session = $cx->getComponent('Session');

        if (
            $session &&
            $session->getSession(false) &&
            $session->isInitialized() &&
            isset($_SESSION) &&
            is_object($_SESSION) &&
            $session->getSession()->userId &&
            $this->load($session->getSession()->userId) &&
            $this->getActiveStatus() &&
            $this->hasModeAccess($backend) &&
            $this->updateLastActivityTime()
        ) {
            $this->loggedIn = true;
            return true;
        }
        return false;
    }


    /**
     * Returns true if this Users' e-mail address is valid and unique.
     *
     * Otherwise, adds appropriate error messages, and returns false.
     * Required to be public by the Shop!
     * @return  boolean         True for valid and unique e-mail addresses,
     *                          false otherwise
     */
    public function validateEmail()
    {
        global $_CORELANG;

        if (FWValidator::isEmail($this->email)) {
            if (self::isUniqueEmail($this->email, $this->id)) {
                return true;
            } else {
                $this->error_msg[] = $_CORELANG['TXT_ACCESS_EMAIL_ALREADY_USED'];
            }
        } else {
            $this->error_msg[] = $_CORELANG['TXT_ACCESS_INVALID_EMAIL_ADDRESS'];
        }

        return false;
    }


    /**
     * Validate language id
     *
     * Checks if the language ids frontend_lang_id and backend_lang_id are valid language IDs.
     * In the case that the specified language isn't valid, the ID 0 is taken instead.
     * $scope could either be 'frontend' or 'backend'
     *
     * @throws UserException
     * @param string $scope
     */
    private function validateLanguageId($scope)
    {
        if ($scope == 'frontend') {
            $paramMethod = 'getLanguageParameter';
        } elseif ($scope == 'backend') {
            $paramMethod = 'getBackendLanguageParameter';
        } else {
            throw new UserException("User->validateLanguageId(): Scope is neither front- nor backend");
        }
        $this->{$scope.'_language'} =
            (FWLanguage::$paramMethod(
                $this->{$scope.'_language'}, $scope)
                  ? $this->{$scope.'_language'} : 0);
    }


    private function loadPermissionIds($type)
    {
        global $objDatabase;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $userRepo = $cx->getDb()->getEntityManager()->getRepository('Cx\Core\User\Model\Entity\User');
        $qb = $userRepo->createQueryBuilder('u');
        $qb->innerJoin('u.groups', 'g')
            ->where($qb->expr()->eq('u.id', ':id'))
            ->andWhere($qb->expr()->eq('g.active', ':active'))
            ->setParameters(array('id' => $this->id, 'active' => 1));
        $user = $qb->getQuery()->getOneOrNullResult();

        if (empty($user)) {
            return;
        }

        $groupIds = array();
        foreach ($user->getGroups() as $group) {
            if (!$group->isActive()) {
                continue;
            }
            $groupIds[] = $group->getId();
        }

        $query = '
            SELECT tblI.`access_id`
            FROM `'.DBPREFIX.'access_group_'.$type.'_ids` AS tblI
            WHERE tblI.`group_id` IN('.implode(',', $groupIds).')
            GROUP BY tblI.`access_id`
            ORDER BY tblI.`access_id`';
        $objAccessId = $objDatabase->Execute($query);
        if ($objAccessId !== false) {
            $this->arrCachedUsers[$this->id][$type.'_access_ids'] = array();
            while (!$objAccessId->EOF) {
                $this->arrCachedUsers[$this->id][$type.'_access_ids'][] = $objAccessId->fields['access_id'];
                $objAccessId->MoveNext();
            }
        }
    }


    public function hasModeAccess($backend = false)
    {
        if ($this->getAdminStatus()) {
            return true;
        }

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $qb = $cx->getDb()->getEntityManager()->getRepository(
            'Cx\Core\User\Model\Entity\Group'
        )->createQueryBuilder('g');

        $qb->innerJoin('g.users', 'u')
            ->where($qb->expr()->eq('u.id', ':userId'))
            ->andWhere($qb->expr()->eq('g.active', ':isActive'))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('g.type', ':type'),
                    $qb->expr()->eq('g.type', ':typeBackend')
                )
            )->setMaxResults(1)
            ->setParameters(
                array(
                    'userId' => $this->id,
                    'isActive' => 1,
                    'type' => ($backend ? 'backend' : 'frontend'),
                    'typeBackend' => 'backend'
                )
            );

        $user = $qb->getQuery()->getOneOrNullResult();

        if (empty($user)) {
            return false;
        }

        return true;
    }


    private function updateLastActivityTime()
    {
        $arrSettings = User_Setting::getSettings();
        $intervalvalue = (isset($arrSettings['session_user_interval']['value'])
            ? $arrSettings['session_user_interval']['value'] : 500);

        if (time() <= ($intervalvalue + $this->last_activity)) {
            return true;
        }

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $user = $em->getRepository('Cx\Core\User\Model\Entity\User')->find($this->id);

        if (empty($user)) {
            return true;
        }
        $user->setLastActivity(time());

        try {
            $em->persist($user);
            $em->flush();
        } catch (\Doctrine\ORM\OptimisticLockException $e) {
            return false;
        }
        return true;
    }


    private function updateLastAuthTime()
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $userRepo = $em->getRepository('Cx\Core\User\Model\Entity\User');
        $user = $userRepo->find($this->id);

        if (empty($user)) {
            return false;
        }

        // destroy expired auth token
        if ($user->getAuthTokenTimeout() < time()) {
            $user->setAuthToken('');
            $user->setAuthTokenTimeout(0);
        }

        // update authentication time
        $user->setLastAuth(time());

        try {
            $em->persist($user);
            $em->flush();
            return true;
        } catch (\Doctrine\ORM\OptimisticLockException $e) {
            return false;
        }
    }

    /**
     * Register a successful login.
     *
     * @static
     * @access  public
     * @param   string              $username
     * @global  ADONewConnection    $objDatabase
     */
    public function registerSuccessfulLogin()
    {
        global $objDatabase;
        
        $this->updateLastAuthTime();
        
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();

        // Flush all cache attached to the current session.
        // This is required as after the sign-in, the user might have a
        // greater access level which provides access to more or different
        // content.
        $cx->getComponent('Cache')->clearUserBasedPageCache(session_id());
        $cx->getComponent('Cache')->clearUserBasedEsiCache(session_id());

        // flush access block widgets (currently signed-in users, etc.)
        $cx->getEvents()->triggerEvent(
            'clearEsiCache',
            array(
                'Widget',
                $cx->getComponent('Access')->getSessionBasedWidgetNames(),
            )
        );

        $em = $cx->getDb()->getEntityManager();
        $user = $em->getRepository('Cx\Core\User\Model\Entity\User')->find($this->id);
        $user->setLastAuthStatus(1);
        $em->persist($user);

        try {
            $em->flush();
            return true;
        } catch (\Doctrine\ORM\OptimisticLockException $e) {
            return false;
        }
    }

    /**
     * Register a failed login.
     * This causes that the user needs to fill out
     * the captcha the next time he logs on.
     *
     * @static
     * @access  public
     * @param   string              $username
     * @global  ADONewConnection    $objDatabase
     */
    public static function registerFailedLogin($username)
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $userRepo = $em->getRepository('Cx\Core\User\Model\Entity\User');
        $qb = $userRepo->createQueryBuilder('u');
        $qb->where($qb->expr()->eq('u.email', ':email'))
            ->setParameter('email', $username);

        $arrUserSettings = \User_Setting::getSettings();
        if ($arrUserSettings['use_usernames']['status']) {
            $qb->orWhere($qb->expr()->eq('u.username', ':username'))
                ->setParameter('username', $username);
        }

        $user = $qb->getQuery()->getOneOrNullResult();

        if (empty($user)) {
            return false;
        }

        try {
            $user->setLastAuthStatus(0);
            $em->persist($user);
            $em->flush();
            return true;
        } catch (\Doctrine\ORM\OptimisticLockException $e) {
            return false;
        }
    }


    /**
     * Sets username of user
     *
     * This will set the attribute username of this object to $username
     * if the parameter $username is valid and isn't yet used by an other user.
     * @param string $username
     * @return boolean
     */
    public function setUsername($username)
    {
        $this->username = $username;
        $this->updateLoadedUserData('username', $this->username);
    }


    /**
     * Sets the validity period of the account
     *
     * Administrators cannot be restricted in their validity.
     * Returns TRUE.
     * @param integer $timestamp
     * @return boolean
     */
    public function setValidityTimePeriod($validity)
    {
        if ($this->getAdminStatus()) {
            $this->validity = 0;
            $this->setExpirationDate(0);
        } else {
            $this->validity = $validity;
            $this->setExpirationDate(($validitySeconds = $validity*60*60*24) ? mktime(23, 59, 59, date('m', time() + $validitySeconds), date('d', time() + $validitySeconds), date('Y', time() + $validitySeconds)) : 0);
        }

        $this->updateLoadedUserData('validity', $this->validity);

        return true;
    }


    public function setExpirationDate($expiration)
    {
        $this->expiration = $expiration;
        $this->updateLoadedUserData('expiration', $this->expiration);
    }


    /**
     * Sets email address of user
     *
     * This will set the attribute email of this object to $email
     * if the parameter $email is valid and isn't yet used by an other user.
     * @param string $email
     * @return boolean
     */
    public function setEmail($email)
    {
        // START: WORKAROUND FOR ACCOUNTS SOLD IN THE SHOP
        $emailPrefix = array();
        if (preg_match(
            '/^(shop_customer_[0-9]+_[0-9]+_[0-9]-).+$/',
            $this->email, $emailPrefix)) {
            $email = $emailPrefix[1].$email;
        }
        // END: WORKAROUND FOR ACCOUNTS SOLD IN THE SHOP
        $this->email = $email;

        $this->updateLoadedUserData('email', $this->email);
    }


    /**
     * Sets password of user
     *
     * This will set the attribute password of this object to the hash
     * of $password if $password is a valid password and if it was confirmed
     * by the second parameter $confirmedPassword.
     * @param   string    $password           The new password
     * @param   string    $confirmedPassword  The new password, again
     * @param   boolean   $reset
     * @param   boolean   $verify             Whether or not to verify if
     *                                        $password is a valid password
     *                                        according to the set password
     *                                        complexity rules.
     * @return  boolean                       True on success, false otherwise
     * @global  array     $_CORELANG
     */
    public function setPassword($password, $confirmedPassword=null, $reset=false, $verify=true)
    {
        global $_CORELANG, $_CONFIG;

        if ((empty($password) && empty($confirmedPassword) && $this->id && !$reset) || isset($_SESSION['user_id'])) {
            return true;
        }
        if (
            !$verify ||
            self::isValidPassword($password)
        ) {
            if (isset($confirmedPassword) && $password != $confirmedPassword) {
                $this->error_msg[] = $_CORELANG['TXT_ACCESS_PASSWORD_NOT_CONFIRMED'];
                return false;
            }
            $this->password = (string) \Cx\Core\Model\Model\Entity\Password::createFromPlaintext(
                $password
            );
            $this->updateLoadedUserData('password', $this->password);
            return true;
        }
        if (isset($_CONFIG['passwordComplexity']) && $_CONFIG['passwordComplexity'] == 'on') {
            $errorMsg = $_CORELANG['TXT_ACCESS_INVALID_PASSWORD_WITH_COMPLEXITY'];
        } else {
            $errorMsg = $_CORELANG['TXT_ACCESS_INVALID_PASSWORD'];
        }
        $this->error_msg[] = $errorMsg;
        return false;
    }

    /**
     * Set new password as hash of password
     * @param   string $hashedPassword The hash of the new password to be set
     */
    public function setHashedPassword($hashedPassword) {
        $this->password = $hashedPassword;
        $this->updateLoadedUserData('password', $this->password);
    }

    /**
     * Returns the hash of the newly set password of the user account if it has been changed.
     * This method only returns the password (its hash) of the user account in case it has
     * been changed using {@see \User::setPassword()}.
     * This method's purpose is to have the newly set password (its hash) available in
     * the model events through it.
     * @return  string  The newly set password of the user account
     */
    public function getHashedPassword() {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $user = $cx->getDb()->getEntityManager()->getRepository('Cx\Core\User\Model\Entity\User')->find(
            $this->getId()
        );

        if ($user) {
            return $user->getPassword();
        }
        return '';
    }


    /**
     * Set frontend language ID of user
     *
     * This will set the attribute frontend_lang_id of this object to $langId.
     * @param   integer   $langId
     * @return  void
     */
    public function setFrontendLanguage($langId)
    {
        $this->frontend_language = intval($langId);
        $this->validateLanguageId('frontend');
        $this->updateLoadedUserData('frontend_lang_id', $this->frontend_language);
    }


    /**
     * Set backend language ID of user
     *
     * This will set the attribute backend_lang_id of this object to $langId.
     * @param   integer   $langId
     * @return  void
     */
    public function setBackendLanguage($langId)
    {
        $this->backend_language = intval($langId);
        $this->validateLanguageId('backend');
        $this->updateLoadedUserData('backend_lang_id', $this->backend_language);
    }


    /**
     * Set active status of user
     *
     * This will set the attribute is_active of this object either
     * to TRUE or FALSE, depending of $status.
     * @param   boolean   $status
     * @return  void
     */
    public function setActiveStatus($status)
    {
        $this->is_active = (bool)$status;
        $this->updateLoadedUserData('active', $this->is_active);
    }

    /**
     * Set verification status of user
     *
     * This will set the attribute verified of this object either
     * to TRUE or FALSE, depending of $verified.
     * @param   boolean   $verified
     * @return  boolean   TRUE
     */
    public function setVerification($verified) {
        $this->verified = $verified;
        $this->updateLoadedUserData('verified', $this->verified);
        return true;
    }

    /**
     * Set the Id of a user group that should be used as the user's primary group
     *
     * @param integer $groupId
     * @return void
     */
    public function setPrimaryGroup($groupId)
    {
        if (!isset($this->arrGroups)) {
            $this->arrGroups = $this->loadGroups();
        }
        if (in_array($groupId, $this->arrGroups)) {
            $this->primary_group = $groupId;
        } elseif (count($this->arrGroups)) {
            $this->primary_group = $this->arrGroups[0];
        } else {
            $this->primary_group = 0;
        }

        $this->updateLoadedUserData('primary_group', $this->primary_group);
    }


    /**
     * Set administration status of user
     *
     * This will set the attribute is_admin of this object to $status.
     * If $status is FALSE then it will only be accepted if this object
     * isn't the only administrator.
     * @param boolean $status
     * @param boolean $force
     * @global array
     * @return boolean
     */
    public function setAdminStatus($status, $force = false)
    {
        global $_CORELANG;

        if ($status || !$this->isLastAdmin() || $force) {
            $this->is_admin = (bool)$status;
            $this->updateLoadedUserData('is_admin', $this->is_admin);
            return true;
        }
        $this->error_msg[] = sprintf($_CORELANG['TXT_ACCESS_CHANGE_PERM_LAST_ADMIN_USER'], $this->getUsername());
        return false;
    }


    /**
     * Set ID's of groups to which this user should belong to
     * @see     UserGroup, UserGroup::getGroups(), UserGroup::getId()
     * @param   array   $arrGroups
     * @return void
     */
    public function setGroups($arrGroups)
    {
        $objFWUser = FWUser::getFWUserObject();
        $objGroup = $objFWUser->objGroup->getGroups(null,null,array());
        $this->arrGroups = array();
        while (!$objGroup->EOF) {
            if (in_array($objGroup->getId(), $arrGroups)) {
                $this->arrGroups[] = $objGroup->getId();
            }
            $objGroup->next();
        }
    }


    /**
     * Set ID's of newsletter-list the which the user subscribed to
     *
     * @param array $arrNewsletterListIDs
     * @return void
     */
    public function setSubscribedNewsletterListIDs($arrNewsletterListIDs)
    {
        $this->arrNewsletterListIDs = $arrNewsletterListIDs;
    }


    public function setEmailAccess($emailAccess)
    {
        $this->email_access = in_array($emailAccess, array_keys($this->arrPrivacyAccessTypes))
            ? $emailAccess : $this->defaultEmailAccessType;
        $this->updateLoadedUserData('email_access', $this->email_access);
    }


    public function setProfileAccess($profileAccess)
    {
        $this->profile_access = in_array($profileAccess, array_keys($this->arrPrivacyAccessTypes))
            ? $profileAccess : $this->defaultProfileAccessTyp;
        $this->updateLoadedUserData('profile_access', $this->profile_access);
    }


    /**
     * Returns true if the current User has the only active admin account
     * present in the system.
     *
     * Returns false if either
     *  - the current User is not an admin, or
     *  - there are at least two active admins present
     * Note that true is returned if the database query fails, so the User
     * will not be allowed to be deleted.  You might have a whole different
     * kind of problem in that case anyway.
     * @global  ADONewConnection
     * @return  boolean
     */
    private function isLastAdmin()
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $userRepo = $cx->getDb()->getEntityManager()->getRepository('Cx\Core\User\Model\Entity\User');
        $qb = $userRepo->createQueryBuilder('u');
        $qb->select('COUNT(u.id) AS numof_admin')
           ->where($qb->expr()->eq('u.superUser', ':superUser'))
           ->andWhere($qb->expr()->eq('u.active', ':active'))
           ->setParameters(array('superUser' => 1, 'active' => 1));
        $count = $qb->getQuery()->getOneOrNullResult();

        return ($count < 2);
    }


    /**
     * Returns true if $email is a unique e-mail address in the system
     * @param   string    $email
     * @param   integer   $id
     * @return  boolean
     * @static
     */
    public static function isUniqueEmail($email, $id=0)
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $userRepo = $cx->getDb()->getEntityManager()->getRepository('Cx\Core\User\Model\Entity\User');
        self::removeOutdatedAccounts();

        $qb = $userRepo->createQueryBuilder('u');
        $qb->where($qb->expr()->eq('u.email', ':email'))
           ->andWhere($qb->expr()->not($qb->expr()->eq('u.id', ':id')))
           ->setMaxResults(1)
           ->setParameters(array('email' => $email, 'id' => $id));

        return !count($qb->getQuery()->getOneOrNullResult());
    }


    /**
     * Returns true if $username is a unique user name
     *
     * Returns false if the test for uniqueness fails, or if the $username
     * exists already.
     * If non-empty, the given User ID is excluded from the search, so the
     * User does not match herself.
     * @param   string    $username   The username to test
     * @param   integer   $id         The optional current User ID
     * @return  boolean               True if the username is available,
     *                                false otherwise
     * @static
     */
    protected static function isUniqueUsername($username, $id=0)
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $userRepo = $cx->getDb()->getEntityManager()->getRepository('Cx\Core\User\Model\Entity\User');
        self::removeOutdatedAccounts();

        $qb = $userRepo->createQueryBuilder('u');
        $qb->where($qb->expr()->eq('u.username', ':username'))
            ->andWhere($qb->expr()->not($qb->expr()->eq('u.id', ':id')))
            ->setMaxResults(1)
            ->setParameters(array('username' => $username, 'id' => $id));

        return !count($qb->getQuery()->getOneOrNullResult());
    }


    /**
     * Returns true if the given $username is valid
     * @param   string    $username
     * @return  boolean
     * @static
     */
    public static function isValidUsername($username)
    {
        if (preg_match('/^[a-zA-Z0-9-_]*$/', $username)) {
            return true;
        }
// For version 2.3, inspired by migrating Shop Customers to Users:
// In addition to the above, also accept usernames that look like valid
// e-mail addresses
// TODO: Maybe this should be restricted to MODULE_ID == 16 (Shop)?
        if (FWValidator::isEmail($username)) {
            return true;
        }
        return false;
    }


    /**
     * Returns true if the given $password is valid
     * @param   string    $password
     * @return  boolean
     */
    public static function isValidPassword($password)
    {
        global $_CONFIG;

        if (strlen($password) >= 6) {
            if (isset($_CONFIG['passwordComplexity']) && $_CONFIG['passwordComplexity'] == 'on') {
                // Password must contain the following characters: upper, lower case and numbers
                if (!preg_match('/[A-Z]+/', $password) || !preg_match('/[a-z]+/', $password) || !preg_match('/[0-9]+/', $password)) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }


    public function isAllowedToChangeEmailAccess()
    {
        if ($this->getAdminStatus()) {
            return true;
        }
        $arrSettings = User_Setting::getSettings();
        return $arrSettings['user_config_email_access']['status'];
    }


    public function isAllowedToChangeProfileAccess()
    {
        if ($this->getAdminStatus()) {
            return true;
        }
        $arrSettings = User_Setting::getSettings();
        return $arrSettings['user_config_profile_access']['status'];
    }


    public function isAllowedToDeleteAccount()
    {
        $arrSettings = User_Setting::getSettings();
        return $arrSettings['user_delete_account']['status'];
    }


    /**
     * Returns the e-mail address if the User accounts has been created
     * by the Shop.
     *
     * Such accounts have e-mail addresses that look like
     * "shop_customer_$orderId_$productId_$instance-$email"
     * Note that this is a temporary workaround and will be changed in
     * a future release.
     * @todo    All affected customers *MUST* be migrated properly from
     *          the Shop to the User administration
     * @return  string            The e-mail address if the account was
     *                            autocreated by the Shop, the empty string
     *                            otherwise.
     */
    private function getShopUserEmail()
    {
        $email = array();
        if (preg_match(
            '/^shop_customer_[0-9]+_[0-9]+_[0-9]-(.+)$/', $this->email, $email)) {
            return $email[1];
        }
        return '';
    }

    /**
     * Checks whether the user account is connected with a crm customer
     *
     * @return int|null id of crm user if the user is associated with a customer of crm module
     */
    public function getCrmUserId() {
        /**
         * @var \Cx\Core\Core\Controller\Cx $cx
         */
        $cx = \Env::get('cx');
        if (!$cx->getLicense()->isInLegalComponents('Crm')) {
            return false;
        }
        $db = $cx->getDb()->getAdoDb();
        $result = $db->SelectLimit("SELECT `id` FROM `" . DBPREFIX . "module_crm_contacts` WHERE `user_account` = " . intval($this->id), 1);
        if ($result->RecordCount() == 0) {
            return null;
        }
        return $result->fields['id'];
    }

    /**
     * Returns this user's timezone
     * @todo Implement a way to detect the real timezone
     * @todo Implement DateTime postResolve() to set $this->userTimezone again once the sign-in user has been loaded
     * @return \DateTimeZone User's timezone
     */
    public function getTimezone() {
        global $_CONFIG;

        return new \DateTimeZone($_CONFIG['timezone']);
    }

    /**
     * Update a specific profile attribute of the user
     *
     * @param   string  $attribute  ID of profile attribute to update
     * @param   string|integer|boolean  $value  Value to set the profile
     *                                          attribute to
     */
    protected function updateLoadedUserData($attribute, $value) {
        if (!$this->id) {
            return;
        }

        if (!isset($this->arrLoadedUsers[$this->id])) {
            return;
        }

        $this->arrLoadedUsers[$this->id][$attribute] = $value;
    }

    /**
     * Get object data as array
     *
     * @param    array   Return data of user object as array
     */
    public function toArray() {
        return $this->arrLoadedUsers[$this->id];
    }

    /**
     * Tries to form a valid and unique username from the words given
     *
     * Usually, you would use first and last names as parameters.
     * @return    string                The new user name on success,
     *                                  false otherwise
     */
    static function make_username($word1, $word2)
    {
//echo("User::makeUsername($word1, $word2): Entered<br />");
        // Just letters, please
        $word1 = preg_replace('/[^a-z]/i', '', $word1);
        $word2 = preg_replace('/[^a-z]/i', '', $word2);
        $usernames = array(
            $word2, "$word1$word2", "${word1}_$word2", "$word1.$word2", $word1,
        );
        $suffix = '';
        while (true) {
            foreach ($usernames as $username) {
//echo("Username /$username/$suffix/ ");
                if (!self::isValidUsername($username.$suffix)) {
//echo("not valid<br />");
                    continue;
                }
                if (!self::isUniqueUsername($username.$suffix)) {
//echo("not unique<br />");
                    continue;
                }
//echo("OK<br />");
                return $username.$suffix;
            }
            // Note that this method will run for a long time, or even
            // forever, for very common names.
            $suffix = intval(mt_rand(0, 99));
        }
        // Never reached
        return null;
    }


    /**
     * Returns a valid password
     *
     * Generated passwords consist of
     *  - at most 4 lower caps letters [qwertzupasdfghjkyxcvbnm],
     *  - at most 4 upper caps letters [QWERTZUPASDFGHJKLYXCVBNM],
     *  - at most 2 digits [23456789], and
     *  - at most 1 special character [-+_!?%&], if enabled.
     * If $length is less than 6, the length will be 6 characters.
     * If $length is bigger than 8, the length will be 8 characters.
     * @param     integer   $length       Desired password length,
     *                                    6 to 8 characters.  Defaults to 8
     * @param     boolean   $use_special  Use "special" characters [-+_!?%&]
     *                                    if true.  Defaults to false
     * @return    string                  The new password
     */
    static function make_password($length=8, $use_special=false)
    {
        static $lower = 'qwertzupasdfghjkyxcvbnm';
        static $upper = 'QWERTZUPASDFGHJKLYXCVBNM';
        static $digit = '23456789';
        static $special = '-+_!?%&';

        $length = min(max($length, 6), 8);
        while (true) {
            $password = '';
            $have_lower = 0;
            $have_upper = 0;
            $have_digit = 0;
            $have_other = 0;
            while (strlen($password) < $length) {
                if ($have_lower < 4 && mt_rand(0, 6) < 2) {
                    $password .= substr($lower, mt_rand(0, strlen($lower))-1, 1);
                    ++$have_lower;
                    continue;
                }
                if ($have_upper < 4 && mt_rand(0, 6) < 2) {
                    $password .= substr($upper, mt_rand(0, strlen($upper))-1, 1);
                    ++$have_upper;
                    continue;
                }
                if ($have_digit < 2 && mt_rand(0, 6) < 1) {
                    $password .= substr($digit, mt_rand(0, strlen($digit))-1, 1);
                    ++$have_digit;
                    continue;
                }
                if ($use_special && $have_other < 1 && mt_rand(0, 6) < 1) {
                    $password .= substr($special, mt_rand(0, strlen($special))-1, 1);
                    ++$have_other;
                }
            }
            if (self::isValidPassword($password)) return $password;
        }
        // Never reached
        return null;
    }


    /**
     * Set the active status of one or more Users
     *
     * The $mix_user_id parameter may either be a user ID or an array thereof.
     * Sets appropriate messages.
     * @param   mixed   $mix_user_id        The User ID or an array of those
     * @param   boolean $active             Activate (true) or deactivate
     *                                      (false) the User(s).
     * @return  void
     */
    static function set_active($mix_user_id, $active)
    {
        global $_CORELANG;

        if (empty($mix_user_id)) return;
        if (!is_array($mix_user_id)) {
            $mix_user_id = array($mix_user_id);
        }
        $count = 0;
        $objFWUser = \FWUser::getFWUserObject();
        foreach ($mix_user_id as $user_id) {
            $objUser = $objFWUser->objUser->getUser($user_id);
            if (!$objUser) {
                Message::warning(sprintf(
                    $_CORELANG['TXT_ACCESS_NO_USER_WITH_ID'], $user_id));
                continue;
            }
            // do not change the status of the currently signed-in user
            if ($objUser->getId() == $objFWUser->objUser->getId()) {
                continue;
            }

            $objUser->setActiveStatus($active);
            if (!$objUser->store()) {
                Message::warning(sprintf(
                    $_CORELANG['TXT_SHOP_ERROR_CUSTOMER_UPDATING'], $user_id));
                continue;
            }
            ++$count;
        }
        if ($count) {
            Message::ok(
                $_CORELANG['TXT_ACCESS_USER_ACCOUNT'.
                ($count > 1 ? 'S' : '').'_'.
                ($active ? '' : 'DE').'ACTIVATED']);
        }
        return;
    }

    /**
     * Generate hash of password with default hash algorithm
     *
     * @param string $password Password to be hashed
     *
     * @return string The generated hash of the supplied password
     * @throws  UserException   In case the password hash generation fails
     */
    public function hashPassword($password)
    {
        $hash = password_hash($password, $this->defaultHashAlgorithm);
        if ($hash !== false) {
            return $hash;
        }

        throw new UserException('Failed to generate a new password hash');
    }

    /**
     * Clears the cache
     *
     * Only use this when loading lots of users (export)!
     */
    public function clearCache() {
        $this->arrCachedUsers = array();
    }
}
