<?php

/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2019
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
 * Users can be created and managed.
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Dario Graf <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_user
 */
namespace Cx\Core\User\Model\Entity;

/**
 * Validates the email of an User to a set of constraints
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_user
 * @version     5.0.0
 */
class UserValidateEmail extends \CxValidate
{
    /**
     * @var int ID of user
     */
    protected $userId;

    /**
     * UserValidateEmail constructor
     *
     * @param int   $userId      ID of user
     * @param array $constraints additional constraints
     */
    public function __construct($userId, $constraints = array())
    {
        $this->userId = $userId;
        parent::__construct($constraints);
    }

    /**
     * Checks if the given mail address is valid and unique
     *
     * @param string $mail Mail address to check
     *
     * @return boolean if email is valid
     */
    public function isValid($mail)
    {
        global $_CORELANG;

        $this->passesValidation = true;

        if (!\FWValidator::isEmail($mail)) {
            $this->messages[] = $_CORELANG['TXT_ACCESS_INVALID_EMAIL_ADDRESS'];
            $this->passesValidation = false;
        }

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('u')
           ->from('Cx\Core\User\Model\Entity\User', 'u')
           ->where($qb->expr()->eq('u.email', ':email'));
        if (!empty($this->userId)) {
            $qb->andWhere($qb->expr()->not($qb->expr()->eq('u.id', ':id')));
            $qb->setParameter('id', $this->userId);
        }
        $qb->setParameter('email', $mail);
        $existingEntity = $qb->getQuery()->getResult();

        if (!empty($existingEntity)) {
            $this->messages[] = $_CORELANG['TXT_ACCESS_EMAIL_ALREADY_USED'];
            $this->passesValidation = false;
        }
        return $this->passesValidation;
    }
}

/**
 * Validates the username of an User to a set of constraints
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_user
 * @version     5.0.0
 */
class UserValidateUsername extends \CxValidate
{
    /**
     * @var int ID of user
     */
    protected $userId;

    /**
     * UserValidateUsername constructor
     *
     * @param int   $userId      id of user
     * @param array $constraints additional constraints
     */
    public function __construct($userId, $constraints = array())
    {
        $this->userId = $userId;
        parent::__construct($constraints);
    }

    /**
     * Checks if the given username is valid and unique
     *
     * @param string $username username to check
     *
     * @return boolean if email is valid
     */
    public function isValid($username)
    {
        global $_CORELANG;

        $this->passesValidation = true;

        if (empty($username)) {
            return $this->passesValidation;
        }

        if (!$this->isValidUsername($username)) {
            $this->messages[] = $_CORELANG['TXT_ACCESS_INVALID_USERNAME'];
            $this->passesValidation = false;
        }

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('u')
            ->from('Cx\Core\User\Model\Entity\User', 'u')
            ->where($qb->expr()->eq('u.username', ':username'));
        if (!empty($this->userId)) {
            $qb->andWhere($qb->expr()->not($qb->expr()->eq('u.id', ':id')));
            $qb->setParameter('id', $this->userId);
        }
        $qb->setParameter('username', $username);
        $existingEntity = $qb->getQuery()->getResult();

        if (!empty($existingEntity)) {
            $this->messages[] = $_CORELANG['TXT_ACCESS_USERNAME_ALREADY_USED'];
            $this->passesValidation = false;
        }

        return $this->passesValidation;
    }


    /**
     * Returns true if the given $username is valid
     *
     * @param string $username username to check
     *
     * @return boolean if username is valid
     */
    protected function isValidUsername($username)
    {
        if (preg_match('/^[a-zA-Z0-9-_]*$/', $username)) {
            return true;
        }
        // For version 2.3, inspired by migrating Shop Customers to Users:
        // In addition to the above, also accept usernames that look like valid
        // e-mail addresses
        if (\FWValidator::isEmail($username)) {
            return true;
        }
        return false;
    }
}

/**
 * Users can be created and managed.
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Dario Graf <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_user
 */
class User extends \Cx\Model\Base\EntityBase {

    /**
     * Allow email access to everyone
     */
    const EMAIL_ACCESS_EVERYONE = 'everyone';

    /**
     * Allow email access to members only
     */
    const EMAIL_ACCESS_MEMBERS_ONLY = 'members_only';

    /**
     * Allow email access to nobody
     */
    const EMAIL_ACCESS_NOBODY = 'nobody';

    /**
     * Allow profile access to everyone
     */
    const PROFILE_ACCESS_EVERYONE = 'everyone';

    /**
     * Allow profile access to members only
     */
    const PROFILE_ACCESS_MEMBERS_ONLY = 'members_only';

    /**
     * Allow profile access to nobody
     */
    const PROFILE_ACCESS_NOBODY = 'nobody';

    /**
     * @var integer ID of the user
     */
    protected $id;

    /**
     * @var boolean Whether a user has superuser rights. A superuser has permission
     *     to read and write in all components as well as changing settings in
     *     the administration
     */
    protected $superUser = false;

    /**
     * @var string Username of the user
     */
    protected $username;

    /**
     * @var string The password needs to have 6 characters
     *     The password complexity can be changed in the settings
     */
    protected $password;

    /**
     * @var string Auth Token of the user
     */
    protected $authToken = '';

    /**
     * @var integer Timeout of the auth token
     */
    protected $authTokenTimeout = 0;

    /**
     * @var integer Timestamp of user registration, it will be created
     *     automatically
     */
    protected $regdate = 0;

    /**
     * @var integer Timestamp of expiration
     */
    protected $expiration = 0;

    /**
     * @var integer How long the account is available. For example unlimited (0)
     *     or only one day.
     */
    protected $validity = 0;

    /**
     * @var integer Timestamp of last authentication
     */
    protected $lastAuth = 0;

    /**
     * @var integer When a user failed to login this status will change to 0
     *     otherwise to 1
     *
     */
    protected $lastAuthStatus = 0;

    /**
     * @var integer Timestamp of the last time the user was active
     */
    protected $lastActivity = 0;

    /**
     * @var string Email address of the user
     */
    protected $email;

    /**
     * @var string Define access to users email address
     *    possibilities: everyone, members_only, nobody
     */
    protected $emailAccess = self::EMAIL_ACCESS_NOBODY;

    /**
     * @var integer Default frontend locale for the user. Set the ID of the
     *     locale
     */
    protected $frontendLangId = 0;

    /**
     * @var integer Default backend locale for the user. Set the ID of the
     *     locale
     */
    protected $backendLangId = 0;

    /**
     * @var boolean Whether the user account is active
     */
    protected $active = false;

    /**
     * @var boolean Whether the user is verified
     */
    protected $verified = true;

    /**
     * @var integer primary group of the user
     */
    protected $primaryGroup = 0;

    /**
     * @var string Define access to users profile
     *     possibilities: everyone, members_only, nobody
     */
    protected $profileAccess = self::PROFILE_ACCESS_MEMBERS_ONLY;

    /**
     * @var string Key that is used to reset the password
     */
    protected $restoreKey = '';

    /**
     * @var integer Validity period of the restore key
     */
    protected $restoreKeyTime = 0;

    /**
     * @var boolean Whether user is able to communicate with other users when
     *     using the module U2U
     */
    protected $u2uActive = false;

    /**
     * @var \Doctrine\Common\Collections\Collection All groups assigned to the user
     */
    protected $groups;

    /**
     * @var \Doctrine\Common\Collections\Collection All attributes that are
     *     assigned to the user
     */
    protected $userAttributeValues;

    /**
     * Constructor
     */
    public function __construct()
    {
        $arrSettings = \FWUser::getSettings();
        $this->profileAccess = $arrSettings['default_profile_access']['value'];
        $this->emailAccess = $arrSettings['default_email_access']['value'];

        $this->groups = new \Doctrine\Common\Collections\ArrayCollection();
        $this->userAttributeValues = new \Doctrine\Common\Collections\ArrayCollection();

    }

    public function initializeValidators()
    {
        $this->validators['username'] = new \Cx\Core\User\Model\Entity\UserValidateUsername($this->getId());
        $this->validators['email'] = new \Cx\Core\User\Model\Entity\UserValidateEmail($this->getId());
        $this->validators['password'] = new \CxValidateRegexp(array('pattern' => '/.+/'), true);
    }

    /**
     * Get id
     *
     * @return integer Id of the user
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set if user is super user
     *
     * @param boolean $superUser
     */
    public function setSuperUser($superUser)
    {
        $this->superUser = $superUser;
    }

    /**
     * Get if user is super user
     *
     * This does exactly the same as isSuperUser, but this method is necessary for doctrine mapping
     *
     * @return boolean Whether user has superuser rights
     */
    public function getSuperUser()
    {
        return $this->superUser;
    }

    /**
     * Get if user is super user
     *
     * This does exactly the same as getSuperUser, but this method name is more intuitive
     *
     * @return boolean Whether user has superuser rights
     */
    public function isSuperUser()
    {
        return $this->getSuperUser();
    }

    /**
     * Set username
     *
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * Get username
     *
     * @return string username of the user
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set password
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * Get password
     *
     * @return string password of the users
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set authToken
     *
     * @param string $authToken
     */
    public function setAuthToken($authToken)
    {
        $this->authToken = $authToken;
    }

    /**
     * Get authToken
     *
     * @return string auth token of the user
     */
    public function getAuthToken()
    {
        return $this->authToken;
    }

    /**
     * Set authTokenTimeout
     *
     * @param integer $authTokenTimeout
     */
    public function setAuthTokenTimeout($authTokenTimeout)
    {
        $this->authTokenTimeout = $authTokenTimeout;
    }

    /**
     * Get authTokenTimeout
     *
     * @return integer timeout of the auth token
     */
    public function getAuthTokenTimeout()
    {
        return $this->authTokenTimeout;
    }

    /**
     * Set regdate
     *
     * @param integer $regdate
     */
    public function setRegdate($regdate)
    {
        $this->regdate = $regdate;
    }

    /**
     * Get regdate
     *
     * @return integer Timestamp of user registration
     */
    public function getRegdate()
    {
        return $this->regdate;
    }

    /**
     * Set expiration
     *
     * @param integer $expiration
     */
    public function setExpiration($expiration)
    {
        $this->expiration = $expiration;
    }

    /**
     * Get expiration
     *
     * @return integer Timestamp of expiration
     */
    public function getExpiration()
    {
        return $this->expiration;
    }

    /**
     * Set validity
     *
     * @param integer $validity
     */
    public function setValidity($validity)
    {
        $this->validity = $validity;
    }

    /**
     * Get validity
     *
     * @return integer How long the account is available
     */
    public function getValidity()
    {
        return $this->validity;
    }

    /**
     * Set lastAuth
     *
     * @param integer $lastAuth
     */
    public function setLastAuth($lastAuth)
    {
        $this->lastAuth = $lastAuth;
    }

    /**
     * Get lastAuth
     *
     * @return integer Timestamp of last authentication
     */
    public function getLastAuth()
    {
        return $this->lastAuth;
    }

    /**
     * Set lastAuthStatus
     *
     * @param integer $lastAuthStatus
     */
    public function setLastAuthStatus($lastAuthStatus)
    {
        $this->lastAuthStatus = $lastAuthStatus;
    }

    /**
     * Get lastAuthStatus
     *
     * @return integer Whether the user has successfully logged in
     */
    public function getLastAuthStatus()
    {
        return $this->lastAuthStatus;
    }

    /**
     * Set lastActivity
     *
     * @param integer $lastActivity
     */
    public function setLastActivity($lastActivity)
    {
        $this->lastActivity = $lastActivity;
    }

    /**
     * Get lastActivity
     *
     * @return integer Timestamp of the last time the user was active
     */
    public function getLastActivity()
    {
        return $this->lastActivity;
    }

    /**
     * Set email
     *
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Get email
     *
     * @return string Email address of the user
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set emailAccess
     *
     * @param string $emailAccess
     */
    public function setEmailAccess($emailAccess)
    {
        $this->emailAccess = $emailAccess;
    }

    /**
     * Get emailAccess
     *
     * @return string Define access to users email address
     */
    public function getEmailAccess()
    {
        return $this->emailAccess;
    }

    /**
     * Set frontendLangId
     *
     * @param integer $frontendLangId
     */
    public function setFrontendLangId($frontendLangId)
    {
        $this->frontendLangId = $frontendLangId;
    }

    /**
     * Get frontendLangId
     *
     * @return integer Default frontend locale for the user
     */
    public function getFrontendLangId()
    {
        return $this->frontendLangId;
    }

    /**
     * Set backendLangId
     *
     * @param integer $backendLangId
     */
    public function setBackendLangId($backendLangId)
    {
        $this->backendLangId = $backendLangId;
    }

    /**
     * Get backendLangId
     *
     * @return integer Default backend locale for the user
     */
    public function getBackendLangId()
    {
        return $this->backendLangId;
    }

    /**
     * Set active
     *
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * Get active
     *
     * This does exactly the same as getActive, but this method is necessary for doctrine mapping
     *
     * @return boolean Whether the user account is active
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Get active
     *
     * This does exactly the same as getActive, but this method name is more intuitive
     *
     * @return integer Whether the user account is active
     */
    public function isActive()
    {
        return $this->getActive();
    }

    /**
     * Set verified
     *
     * @param boolean $verified
     */
    public function setVerified($verified)
    {
        $this->verified = $verified;
    }

    /**
     * Get verified
     *
     * @return boolean Whether the user is verified
     */
    public function getVerified()
    {
        return $this->verified;
    }

    /**
     * Set primaryGroup
     *
     * @param integer $primaryGroup
     */
    public function setPrimaryGroup($primaryGroup)
    {
        $this->primaryGroup = $primaryGroup;
    }

    /**
     * Get primaryGroup
     *
     * @return integer primary group of the user
     */
    public function getPrimaryGroup()
    {
        return $this->primaryGroup;
    }

    /**
     * Set profileAccess
     *
     * @param string $profileAccess
     */
    public function setProfileAccess($profileAccess)
    {
        $this->profileAccess = $profileAccess;
    }

    /**
     * Get profileAccess
     *
     * @return string Define access to users profile
     */
    public function getProfileAccess()
    {
        return $this->profileAccess;
    }

    /**
     * Set restoreKey
     *
     * @param string $restoreKey
     */
    public function setRestoreKey($restoreKey = null)
    {
        $this->restoreKey = !empty($restoreKey)
                            ? $restoreKey
                            : md5($this->email . random_bytes(20));
    }

    /**
     * Get restoreKey
     *
     * @return string Key that is used to reset the password
     */
    public function getRestoreKey()
    {
        return $this->restoreKey;
    }

    /**
     * Set restoreKeyTime
     *
     * @param integer $restoreKeyTime
     */
    public function setRestoreKeyTime($restoreKeyTime = null)
    {
        $this->restoreKeyTime = !empty($restoreKeyTime) ? $restoreKeyTime : time() + 3600;
    }

    /**
     * Get restoreKeyTime
     *
     * @return integer Validity period of the restore key
     */
    public function getRestoreKeyTime()
    {
        return $this->restoreKeyTime;
    }

    /**
     * Set u2uActive
     *
     * @param boolean $u2uActive
     */
    public function setU2uActive($u2uActive)
    {
        $this->u2uActive = $u2uActive;
    }

    /**
     * Get u2uActive
     *
     * @return boolean Whether user is able to communicate with other users when
     *     using the module U2U
     */
    public function getU2uActive()
    {
        return $this->u2uActive;
    }

    /**
     * Add group
     *
     * @param \Cx\Core\User\Model\Entity\Group $group
     */
    public function addGroup(\Cx\Core\User\Model\Entity\Group $group)
    {
        $this->groups[] = $group;
    }

    /**
     * Remove group
     *
     * @param \Cx\Core\User\Model\Entity\Group $group
     */
    public function removeGroup(\Cx\Core\User\Model\Entity\Group $group)
    {
        $this->groups->removeElement($group);
    }
    
    /**
     * Get group
     *
     * @return \Doctrine\Common\Collections\Collection All groups assigned to
     *     the user
     * @deprecated
     * @see \Cx\Core\User\Model\Entity\User::getGroups()
     */
    public function getGroup()
    {
        return $this->getGroups();
    }

    /**
     * Get groups
     *
     * @return \Doctrine\Common\Collections\Collection All groups assigned to
     *     the user
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Add userAttributeValue
     *
     * @param \Cx\Core\User\Model\Entity\UserAttributeValue $userAttributeValue
     */
    public function addUserAttributeValue(\Cx\Core\User\Model\Entity\UserAttributeValue $userAttributeValue)
    {
        $this->userAttributeValues[] = $userAttributeValue;
    }

    /**
     * Remove userAttributeValue
     *
     * @param \Cx\Core\User\Model\Entity\UserAttributeValue $userAttributeValue
     */
    public function removeUserAttributeValue(\Cx\Core\User\Model\Entity\UserAttributeValue $userAttributeValue)
    {
        $this->userAttributeValues->removeElement($userAttributeValue);
    }

    /**
     * Get userAttributeValues
     *
     * @return \Doctrine\Common\Collections\Collection  All attributes that are
     *     assigned to the user
     * @deprecated
     * @see \Cx\Core\User\Model\Entity\User::getUserAttributeValues()
     */
    public function getUserAttributeValue()
    {
        return $this->getUserAttributeValues();
    }

    /**
     * Get userAttributeValues
     *
     * @return \Doctrine\Common\Collections\Collection  All attributes that are
     *     assigned to the user
     */
    public function getUserAttributeValues()
    {
        return $this->userAttributeValues;
    }

    /**
     * Check if the user is backend group
     *
     * @return boolean Whether user of a backend group
     */
    public function isBackendGroupUser()
    {
        if (!$this->getGroups()) {
            return false;
        }

        foreach ($this->getGroups() as $group) {
            if ($group->getType() === 'backend') {
                return true;
            }
        }
        return false;
    }

    /**
     * Get AttributeValue from AttributeValues
     *
     * @param int $attributeId id to find AttributeValue
     * @return \Cx\Core\User\Model\Entity\UserAttributeValue $userAttributeValue
     */
    public function getAttributeValue($attributeId)
    {
        $attributeValue = $this->cx->getDb()->getEntityManager()->getRepository(
            'Cx\Core\User\Model\Entity\UserAttributeValue'
        )->findOneBy(array('userAttribute' => $attributeId, 'user' => $this->getId()));

        if ($attributeValue) {
            return $attributeValue;
        }
        return new \Cx\Core\User\Model\Entity\UserAttributeValue();
    }

    /**
     * Get AttributeValue by profile attribute
     *
     * @param string $attributeId profile id to find AttributeValue (e.g. 'title')
     * @return \Cx\Core\User\Model\Entity\UserAttributeValue $userAttributeValue
     */
    public function getProfileAttribute($attributeId)
    {
        $attr = \FWUser::getFWUserObject()->objUser->objAttribute;
        if ($attr->isDefaultAttribute($attributeId)) {
            $attributeId = $attr->getAttributeIdByDefaultAttributeId($attributeId);
        }

        if (empty($attributeId)) {
            return new \Cx\Core\User\Model\Entity\UserAttributeValue();
        }

        return $this->getAttributeValue($attributeId);
    }

    /**
     * Release restore key
     */
    public function releaseRestoreKey()
    {
        $this->setRestoreKey('');
        $this->setRestoreKeyTime(0);
    }

    /**
     * Get associated group ids
     *
     * @param boolean $activeOnly Whether to load only the active groups or all
     * @retrun array $groupIds
     */
    public function getAssociatedGroupIds($activeOnly = false)
    {
        $groupIds = array();
        foreach ($this->getGroups() as $group) {
            if ($activeOnly && !$group->getIsActive()) {
                continue;
            }
            $groupIds[] = $group->getGroupId();
        }
        return $groupIds;
    }

    /**
     * Get the username if it exists otherwise get the email
     *
     * @return string $usernameOrEmail
     */
    public function getUsernameOrEmail()
    {
        $arrSettings = \User_Setting::getSettings();
        if (!$arrSettings['use_usernames']['status'] || empty($this->getUsername())) {
            return $this->getEmail();
        }
        return $this->getUsername();
    }

    /**
     * Checks whether the user account is connected with a crm customer
     *
     * @return int|null|boolean id of crm user if the user is associated with a customer of crm module $crmId
     */
    public function getCrmUserId()
    {
        if (!$this->cx->getLicense()->isInLegalComponents('Crm')) {
            return false;
        }
        $db = $this->cx->getDb()->getAdoDb();
        $result = $db->SelectLimit(
            'SELECT `id` FROM `' . DBPREFIX . 'module_crm_contacts` WHERE `user_account` = ' . intval($this->getId()), 1
        );
        if ($result->RecordCount() == 0) {
            return null;
        }
        return $result->fields['id'];
    }
}
