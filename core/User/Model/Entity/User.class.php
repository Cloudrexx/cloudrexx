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
 * @version     5.0.0
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
 * @version     5.0.0
 */
class User extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var boolean
     */
    protected $isAdmin = false;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $authToken = '0';

    /**
     * @var integer
     */
    protected $authTokenTimeout = 0;

    /**
     * @var integer
     */
    protected $regdate = 0;

    /**
     * @var integer
     */
    protected $expiration = 0;

    /**
     * @var integer
     */
    protected $validity = 0;

    /**
     * @var integer
     */
    protected $lastAuth = 0;

    /**
     * @var integer
     */
    protected $lastAuthStatus = 0;

    /**
     * @var integer
     */
    protected $lastActivity = 0;

    /**
     * @var string
     */
    protected $email;

    /**
     * @var string enum_user_user_emailaccess
     */
    protected $emailAccess;

    /**
     * @var integer
     */
    protected $frontendLangId = 0;

    /**
     * @var integer
     */
    protected $backendLangId = 0;

    /**
     * @var boolean
     */
    protected $active = true;

    /**
     * @var boolean
     */
    protected $verified = true;

    /**
     * @var integer
     */
    protected $primaryGroup = 0;

    /**
     * @var string enum_user_user_profileaccess
     */
    protected $profileAccess;

    /**
     * @var string
     */
    protected $restoreKey = '';

    /**
     * @var integer
     */
    protected $restoreKeyTime = 0;

    /**
     * @var boolean
     */
    protected $u2uActive = false;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $group;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $userAttributeValue;

    /**
     * Constructor
     */
    public function __construct()
    {
        $arrSettings = \FWUser::getSettings();
        $this->profileAccess = $arrSettings['default_profile_access']['value'];
        $this->emailAccess = $arrSettings['default_email_access']['value'];

        $this->group = new \Doctrine\Common\Collections\ArrayCollection();
        $this->userAttributeValue = new \Doctrine\Common\Collections\ArrayCollection();

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
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set isAdmin
     *
     * @param boolean $isAdmin
     * @return User
     */
    public function setIsAdmin($isAdmin)
    {
        $this->isAdmin = $isAdmin;
    }

    /**
     * Get isAdmin
     *
     * @return boolean 
     */
    public function getIsAdmin()
    {
        return $this->isAdmin;
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
     * @return string 
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
     * @return string 
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
     * @return string 
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
     * @return integer 
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
     * @return integer 
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
     * @return integer 
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
     * @return integer 
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
     * @return integer 
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
     * @return integer 
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
     * @return integer 
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
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set emailAccess
     *
     * @param enum_user_user_emailaccess $emailAccess
     */
    public function setEmailAccess($emailAccess)
    {
        $this->emailAccess = $emailAccess;
    }

    /**
     * Get emailAccess
     *
     * @return enum_user_user_emailaccess 
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
     * @return integer 
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
     * @return integer 
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
     * @return boolean 
     */
    public function getActive()
    {
        return $this->active;
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
     * @return boolean 
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
     * @return integer 
     */
    public function getPrimaryGroup()
    {
        return $this->primaryGroup;
    }

    /**
     * Set profileAccess
     *
     * @param enum_user_user_profileaccess $profileAccess
     */
    public function setProfileAccess($profileAccess)
    {
        $this->profileAccess = $profileAccess;
    }

    /**
     * Get profileAccess
     *
     * @return enum_user_user_profileaccess 
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
     * @return string 
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
     * @return integer 
     */
    public function getRestoreKeyTime()
    {
        return $this->restoreKeyTime;
    }

    /**
     * Set u2uActive
     *
     * @param enum_user_user_u2uactive $u2uActive
     */
    public function setU2uActive($u2uActive)
    {
        $this->u2uActive = $u2uActive;
    }

    /**
     * Get u2uActive
     *
     * @return enum_user_user_u2uactive 
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
        $this->group[] = $group;
    }

    /**
     * Remove group
     *
     * @param \Cx\Core\User\Model\Entity\Group $group
     */
    public function removeGroup(\Cx\Core\User\Model\Entity\Group $group)
    {
        $this->group->removeElement($group);
    }

    /**
     * Get group
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Add userAttributeValue
     *
     * @param \Cx\Core\User\Model\Entity\UserAttributeValue $userAttributeValue
     */
    public function addUserAttributeValue(\Cx\Core\User\Model\Entity\UserAttributeValue $userAttributeValue)
    {
        $this->userAttributeValue[] = $userAttributeValue;
    }

    /**
     * Remove userAttributeValue
     *
     * @param \Cx\Core\User\Model\Entity\UserAttributeValue $userAttributeValue
     */
    public function removeUserAttributeValue(\Cx\Core\User\Model\Entity\UserAttributeValue $userAttributeValue)
    {
        $this->userAttributeValue->removeElement($userAttributeValue);
    }

    /**
     * Get userAttributeValue
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserAttributeValue()
    {
        return $this->userAttributeValue;
    }

    /**
     * Check if the user is backend group
     *
     * @return boolean
     */
    public function isBackendGroupUser()
    {
        if (!$this->group) {
            return false;
        }

        foreach ($this->group as $group) {
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
     * @return \Cx\Core\User\Model\Entity\UserAttributeValue
     */
    public function getAttributeValue($attributeId)
    {
        foreach ($this->userAttributeValue as $value) {
            if ($value->getAttributeId() == $attributeId) {
                return $value;
            }
        }
        return new \Cx\Core\User\Model\Entity\UserAttributeValue();
    }

    public function getProfileAttribute($profileId)
    {
        $attr = \FWUser::getFWUserObject()->objUser->objAttribute;
        if ($attr->isCoreAttribute($profileId)) {
            $attrId = $attr->getAttributeIdByProfileAttributeId($profileId);
        } else {
            $attrId = $profileId;
        }

        if (empty($attrId)) {
            return new \Cx\Core\User\Model\Entity\UserAttributeValue();
        }

        return $this->getAttributeValue($attrId);
    }

    public function releaseRestoreKey()
    {
        $this->setRestoreKey('');
        $this->setRestoreKeyTime(0);
    }

    /**
     * Get associated group ids
     *
     * @param boolean $activeOnly Wether to load only the active groups or all
     * @retrun array
     */
    public function getAssociatedGroupIds($activeOnly = false)
    {
        $groupIds = array();
        foreach ($this->getGroup() as $group) {
            if ($activeOnly && !$group->getIsActive()) {
                continue;
            }
            $groupIds[] = $group->getGroupId();
        }
        return $groupIds;
    }

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
        $result = $db->SelectLimit(
            "SELECT `id` FROM `" . DBPREFIX . "module_crm_contacts` WHERE `user_account` = " . intval($this->getId()), 1
        );
        if ($result->RecordCount() == 0) {
            return null;
        }
        return $result->fields['id'];
    }
}
