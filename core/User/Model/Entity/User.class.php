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


namespace Cx\Core\User\Model\Entity;

/**
 * Cx\Core\User\Model\Entity\User
 */
class User extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer $id
     */
    private $id;

    /**
     * @var integer $isAdmin
     */
    private $isAdmin;

    /**
     * @var string $username
     */
    private $username;

    /**
     * @var string $password
     */
    private $password;

    /**
     * @var string $authToken
     */
    private $authToken;

    /**
     * @var integer $authTokenTimeout
     */
    private $authTokenTimeout;

    /**
     * @var integer $regdate
     */
    private $regdate;

    /**
     * @var integer $expiration
     */
    private $expiration;

    /**
     * @var integer $validity
     */
    private $validity;

    /**
     * @var integer $lastAuth
     */
    private $lastAuth;

    /**
     * @var integer $lastAuthStatus
     */
    private $lastAuthStatus;

    /**
     * @var integer $lastActivity
     */
    private $lastActivity;

    /**
     * @var string $email
     */
    private $email;

    /**
     * @var string $emailAccess
     */
    private $emailAccess;

    /**
     * @var integer $frontendLangId
     */
    private $frontendLangId;

    /**
     * @var integer $backendLangId
     */
    private $backendLangId;

    /**
     * @var integer $active
     */
    private $active;

    /**
     * @var integer $verified
     */
    private $verified;

    /**
     * @var integer $primaryGroup
     */
    private $primaryGroup;

    /**
     * @var string $profileAccess
     */
    private $profileAccess;

    /**
     * @var string $restoreKey
     */
    private $restoreKey;

    /**
     * @var integer $restoreKeyTime
     */
    private $restoreKeyTime;

    /**
     * @var string $u2uActive
     */
    private $u2uActive;

    /**
     * @var \Cx\Core\User\Model\Entity\UserProfile
     */
    private $userProfile;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $group;

    public function __construct()
    {
        $arrSettings = \FWUser::getSettings();
        $this->isAdmin = 0;
        $this->authToken = 0;
        $this->authTokenTimeout = 0;
        $this->regdate = 0;
        $this->expiration = 0;
        $this->validity = 0;
        $this->lastAuth = 0;
        $this->lastAuthStatus = 0;
        $this->lastActivity = 0;
        $this->emailAccess = $arrSettings['default_email_access']['value'];
        $this->frontendLangId = 0;
        $this->backendLangId = 0;
        $this->active = false;
        $this->verified = true;
        $this->primaryGroup = 0;
        $this->profileAccess = $arrSettings['default_profile_access']['value'];
        $this->restoreKey = '';
        $this->restoreKeyTime = '';
        $this->u2uActive = 0;

        $this->group = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * set id
     *
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
    /**
     * Set isAdmin
     *
     * @param integer $isAdmin
     */
    public function setIsAdmin($isAdmin)
    {
        $this->isAdmin = $isAdmin;
    }

    /**
     * Get isAdmin
     *
     * @return integer $isAdmin
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
     * @return string $username
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
     * @return string $password
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
     * @return string $authToken
     */
    public function getAuthToken()
    {
        return $this->authToken;
    }

    /**
     * Set $authTokenTimeout
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
     * @return integer $authTokenTimeout
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
     * @return integer $regdate
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
     * @return integer $expiration
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
     * @return integer $validity
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
     * @return integer $lastAuth
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
     * @return integer $lastAuthStatus
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
     * @return integer $lastActivity
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
     * @return string $email
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
     * @return string $emailAccess
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
     * @return integer $frontendLangId
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
     * @return integer $backendLangId
     */
    public function getBackendLangId()
    {
        return $this->backendLangId;
    }

    /**
     * Set active
     *
     * @param integer $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * Get active
     *
     * @return integer $active
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set verified
     *
     * @param integer $verified
     */
    public function setVerified($verified)
    {
        $this->verified = $verified;
    }

    /**
     * Get verified
     *
     * @return integer $verified
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
     * @return integer $primaryGroup
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
     * @return string $profileAccess
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
     * @return string $restoreKey
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
    public function setRestoreKeyTime($restoreKeyTime)
    {
        $this->restoreKeyTime = $restoreKeyTime;
    }

    /**
     * Get restoreKeyTime
     *
     * @return integer $restoreKeyTime
     */
    public function getRestoreKeyTime()
    {
        return $this->restoreKeyTime;
    }

    /**
     * Set u2uActive
     *
     * @param string $u2uActive
     */
    public function setU2uActive($u2uActive)
    {
        $this->u2uActive = $u2uActive;
    }

    /**
     * Get u2uActive
     *
     * @return string $u2uActive
     */
    public function getU2uActive()
    {
        return $this->u2uActive;
    }

    /**
     * Set userProfile
     *
     * @param \Cx\Core\User\Model\Entity\UserProfile $userProfile
     */
    public function setUserProfile(\Cx\Core\User\Model\Entity\UserProfile $userProfile)
    {
        $this->userProfile = $userProfile;
    }

    /**
     * Get userProfile
     *
     * @return \Cx\Core\User\Model\Entity\UserProfile $userProfile
     */
    public function getUserProfile()
    {
        return $this->userProfile;
    }

    /**
     * Add group
     *
     * @param \Cx\Core\User\Model\Entity\Group $group
     */
    public function addGroup(\Cx\Core\User\Model\Entity\Group $group)
    {
        $group->addUser($this);
        $this->group[] = $group;
    }

    /**
     * Remove the group
     * 
     * @param \Cx\Core\User\Model\Entity\Group $group
     */
    public function removeGroup(\Cx\Core\User\Model\Entity\Group $group) {
        $group->removeUser($this);
        $this->group->removeElement($group);
    }
    
    /**
     * Get group
     *
     * @return \Doctrine\Common\Collections\Collection $group
     */
    public function getGroup()
    {
        return $this->group;
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
}
