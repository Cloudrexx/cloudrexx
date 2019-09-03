<?php

/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2018
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

class UserValidateEmail extends \CxValidate
{
    protected $userId;

    public function __construct($userId, $constraints = array())
    {
        $this->userId = $userId;
        parent::__construct($constraints);
    }

    /**
     * Checks if the given mail address is valid and unique
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

class UserValidateUsername extends \CxValidate
{
    protected $userId;

    public function __construct($userId, $constraints = array())
    {
        $this->userId = $userId;
        parent::__construct($constraints);
    }

    /**
     * Checks if the given username is valid and unique
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
     * @param string $username
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
    protected $authToken = '';

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
    protected $lastAuthStatus = 1;

    /**
     * @var integer
     */
    protected $lastActivity = 0;

    /**
     * @var string
     */
    protected $email;

    /**
     * @var enum_user_user_emailaccess
     */
    protected $emailAccess = 'nobody';

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
     * @var enum_user_user_profileaccess
     */
    protected $profileAccess = 'members_only';

    /**
     * @var string
     */
    protected $restoreKey = '';

    /**
     * @var integer
     */
    protected $restoreKeyTime = 0;

    /**
     * @var enum_user_user_u2uactive
     */
    protected $u2uActive = '1';

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
        $this->group = new \Doctrine\Common\Collections\ArrayCollection();
        $this->userAttributeValue = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function initializeValidators()
    {
        $this->validators['username'] = new \Cx\Core\User\Model\Entity\UserValidateUsername($this->getId());
        $this->validators['email'] = new \Cx\Core\User\Model\Entity\UserValidateEmail($this->getId());
        $this->validators['password'] = new \CxValidateRegexp(array('pattern' => '/.+/'), false);
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
     * @return User
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
     * Set plaintext (/unhashed) password
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        if (empty($password)) {
            return;
        }
        $this->checkPasswordValidity($password);
        $this->password = $this->hashPassword($password);
    }

    /**
     * Get hashed password
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
     * @return User
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
     * @return User
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
     * @return User
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
     * @return User
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
     * @return User
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
     * @return User
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
     * @return User
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
     * @return User
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
     * @return User
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
     * @return User
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
     * @return User
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
     * @return User
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
     * @return User
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
     * @return User
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
     * @return User
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
     * @return User
     */
    public function setRestoreKey($restoreKey = null)
    {
        $this->restoreKey = !empty($restoreKey)
                            ? $restoreKey
                            : md5($this->email . $this->regdate . time());
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
     * @return User
     */
    public function setRestoreKeyTime($restoreKeyTime)
    {
        $this->restoreKeyTime = $restoreKeyTime;
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
     * @return User
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
     * @return User
     */
    public function addGroup(\Cx\Core\User\Model\Entity\Group $group)
    {
        $this->group[] = $group;

        return $this;
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
     * Return the first- and lastname if they are defined. If this is not the
     * case, check if a username exists. If this also does not exist, the
     * e-mail address will be returned.
     *
     * @return string firstname & lastname, username or email
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
     * Returns true if the given $password is valid
     * @param   string    $password
     * @return  boolean
     */
    protected function checkPasswordValidity($password)
    {
        global $_CONFIG, $_CORELANG;

        if (strlen($password) < 6) {
            throw new \Cx\Core\Error\Model\Entity\ShinyException(
                $_CORELANG['TXT_ACCESS_INVALID_PASSWORD']
            );
        }
        if (
            isset($_CONFIG['passwordComplexity']) &&
            $_CONFIG['passwordComplexity'] == 'on'
        ) {
            // Password must contain the following characters: upper, lower
            // case and numbers
            if (
                !preg_match('/[A-Z]+/', $password) ||
                !preg_match('/[a-z]+/', $password) ||
                !preg_match('/[0-9]+/', $password)
            ) {
                throw new \Cx\Core\Error\Model\Entity\ShinyException(
                    $_CORELANG['TXT_ACCESS_INVALID_PASSWORD_WITH_COMPLEXITY']
                );
            }
        }
    }

    /**
     * Generate hash of password with default hash algorithm
     *
     * @param string $password Password to be hashed
     *
     * @return string The generated hash of the supplied password
     * @throws  \Cx\Core\Error\Model\Entity\ShinyException In case the password
     *                                                    hash generation fails
     */
    protected function hashPassword($password)
    {
        $hash = password_hash($password, \PASSWORD_BCRYPT);
        if ($hash !== false) {
            return $hash;
        }

        throw new \Cx\Core\Error\Model\Entity\ShinyException(
            'Failed to generate a new password hash'
        );
    }

    /**
     * Return the first- and lastname if they are defined. If this is not the
     * case, check if a username exists. If this also does not exist, the
     * e-mail address will be returned.
     *
     * @return string firstname & lastname, username or email
     */
    public function __toString()
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $userName = '';

        // values which we would like to get
        $profileAttrs = array(
            'lastname' => '',
            'firstname' => ''
        );

        $attrNameRepo = $em->getRepository(
            'Cx\Core\User\Model\Entity\UserAttributeName'
        );

        foreach ($profileAttrs as $name => $value) {
            $selectedAttrName = $attrNameRepo->findOneBy(
                array('name' => $name)
            );

            $userAttrValues = array();
            if (!empty($this->getUserAttributeValue())) {
                $userAttrValues = $this->getUserAttributeValue();
            }

            if (is_array($userAttrValues)) {
                // must be converted, since $userAttrValues is an array
                $collection = new \Doctrine\Common\Collections\ArrayCollection(
                    $userAttrValues
                );
            } else {
                $collection = $userAttrValues;
            }


            if (!empty($selectedAttrName)) {
                $attrId = $selectedAttrName->getAttributeId();
                $selectedAttrValue = $collection->filter(
                    function($attrValue) use ($attrId) {
                        if ($attrId == $attrValue->getAttributeId()) {
                            return $attrValue;
                        }
                    }
                )->first();

                if (!empty($value)) {
                    $profileAttrs[$name] = $selectedAttrValue->getValue();
                }
            }
        }

        if (
            !empty($profileAttrs['firstname']) ||
            !empty($profileAttrs['lastname'])
        ) {
            $userName = trim(
                $profileAttrs['firstname'].' '. $profileAttrs['lastname']
            );
        } else if (!empty($this->getUsername())) {
            $userName = $this->getUsername();
        } else if (!empty($this->getEmail())) {
            $userName = $this->getEmail();
        } else {
            $userName = parent::__toString();
        }

        return $userName;
    }
}
