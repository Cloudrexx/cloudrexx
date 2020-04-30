<?php

namespace Cx\Model\Proxies\__CG__\Cx\Core\User\Model\Entity;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class User extends \Cx\Core\User\Model\Entity\User implements \Doctrine\ORM\Proxy\Proxy
{
    /**
     * @var \Closure the callback responsible for loading properties in the proxy object. This callback is called with
     *      three parameters, being respectively the proxy object to be initialized, the method that triggered the
     *      initialization process and an array of ordered parameters that were passed to that method.
     *
     * @see \Doctrine\Common\Persistence\Proxy::__setInitializer
     */
    public $__initializer__;

    /**
     * @var \Closure the callback responsible of loading properties that need to be copied in the cloned object
     *
     * @see \Doctrine\Common\Persistence\Proxy::__setCloner
     */
    public $__cloner__;

    /**
     * @var boolean flag indicating if this object was already initialized
     *
     * @see \Doctrine\Common\Persistence\Proxy::__isInitialized
     */
    public $__isInitialized__ = false;

    /**
     * @var array properties to be lazy loaded, with keys being the property
     *            names and values being their default values
     *
     * @see \Doctrine\Common\Persistence\Proxy::__getLazyProperties
     */
    public static $lazyPropertiesDefaults = array();



    /**
     * @param \Closure $initializer
     * @param \Closure $cloner
     */
    public function __construct($initializer = null, $cloner = null)
    {

        $this->__initializer__ = $initializer;
        $this->__cloner__      = $cloner;
    }

    /**
     * {@inheritDoc}
     * @param string $name
     */
    public function __get($name)
    {
        $this->__initializer__ && $this->__initializer__->__invoke($this, '__get', array($name));

        return parent::__get($name);
    }





    /**
     * 
     * @return array
     */
    public function __sleep()
    {
        if ($this->__isInitialized__) {
            return array('__isInitialized__', 'id', 'superUser', 'username', 'password', 'authToken', 'authTokenTimeout', 'regdate', 'expiration', 'validity', 'lastAuth', 'lastAuthStatus', 'lastActivity', 'email', 'emailAccess', 'frontendLangId', 'backendLangId', 'active', 'verified', 'primaryGroup', 'profileAccess', 'restoreKey', 'restoreKeyTime', 'u2uActive', 'groups', 'userAttributeValues', 'validators', 'virtual');
        }

        return array('__isInitialized__', 'id', 'superUser', 'username', 'password', 'authToken', 'authTokenTimeout', 'regdate', 'expiration', 'validity', 'lastAuth', 'lastAuthStatus', 'lastActivity', 'email', 'emailAccess', 'frontendLangId', 'backendLangId', 'active', 'verified', 'primaryGroup', 'profileAccess', 'restoreKey', 'restoreKeyTime', 'u2uActive', 'groups', 'userAttributeValues', 'validators', 'virtual');
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (User $proxy) {
                $proxy->__setInitializer(null);
                $proxy->__setCloner(null);

                $existingProperties = get_object_vars($proxy);

                foreach ($proxy->__getLazyProperties() as $property => $defaultValue) {
                    if ( ! array_key_exists($property, $existingProperties)) {
                        $proxy->$property = $defaultValue;
                    }
                }
            };

        }
    }

    /**
     * 
     */
    public function __clone()
    {
        $this->__cloner__ && $this->__cloner__->__invoke($this, '__clone', array());
    }

    /**
     * Forces initialization of the proxy
     */
    public function __load()
    {
        $this->__initializer__ && $this->__initializer__->__invoke($this, '__load', array());
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __isInitialized()
    {
        return $this->__isInitialized__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setInitialized($initialized)
    {
        $this->__isInitialized__ = $initialized;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setInitializer(\Closure $initializer = null)
    {
        $this->__initializer__ = $initializer;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __getInitializer()
    {
        return $this->__initializer__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setCloner(\Closure $cloner = null)
    {
        $this->__cloner__ = $cloner;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific cloning logic
     */
    public function __getCloner()
    {
        return $this->__cloner__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     * @static
     */
    public function __getLazyProperties()
    {
        return self::$lazyPropertiesDefaults;
    }

    
    /**
     * {@inheritDoc}
     */
    public function initializeValidators()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'initializeValidators', array());

        return parent::initializeValidators();
    }

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        if ($this->__isInitialized__ === false) {
            return (int)  parent::getId();
        }


        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getId', array());

        return parent::getId();
    }

    /**
     * {@inheritDoc}
     */
    public function setSuperUser($superUser)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setSuperUser', array($superUser));

        return parent::setSuperUser($superUser);
    }

    /**
     * {@inheritDoc}
     */
    public function getSuperUser()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getSuperUser', array());

        return parent::getSuperUser();
    }

    /**
     * {@inheritDoc}
     */
    public function isSuperUser()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isSuperUser', array());

        return parent::isSuperUser();
    }

    /**
     * {@inheritDoc}
     */
    public function getIsAdmin()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getIsAdmin', array());

        return parent::getIsAdmin();
    }

    /**
     * {@inheritDoc}
     */
    public function setUsername($username)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setUsername', array($username));

        return parent::setUsername($username);
    }

    /**
     * {@inheritDoc}
     */
    public function getUsername()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getUsername', array());

        return parent::getUsername();
    }

    /**
     * {@inheritDoc}
     */
    public function setPassword($password)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setPassword', array($password));

        return parent::setPassword($password);
    }

    /**
     * {@inheritDoc}
     */
    public function getPassword()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getPassword', array());

        return parent::getPassword();
    }

    /**
     * {@inheritDoc}
     */
    public function setAuthToken($authToken)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setAuthToken', array($authToken));

        return parent::setAuthToken($authToken);
    }

    /**
     * {@inheritDoc}
     */
    public function getAuthToken()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getAuthToken', array());

        return parent::getAuthToken();
    }

    /**
     * {@inheritDoc}
     */
    public function setAuthTokenTimeout($authTokenTimeout)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setAuthTokenTimeout', array($authTokenTimeout));

        return parent::setAuthTokenTimeout($authTokenTimeout);
    }

    /**
     * {@inheritDoc}
     */
    public function getAuthTokenTimeout()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getAuthTokenTimeout', array());

        return parent::getAuthTokenTimeout();
    }

    /**
     * {@inheritDoc}
     */
    public function setRegdate($regdate)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setRegdate', array($regdate));

        return parent::setRegdate($regdate);
    }

    /**
     * {@inheritDoc}
     */
    public function getRegdate()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getRegdate', array());

        return parent::getRegdate();
    }

    /**
     * {@inheritDoc}
     */
    public function setExpiration($expiration)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setExpiration', array($expiration));

        return parent::setExpiration($expiration);
    }

    /**
     * {@inheritDoc}
     */
    public function getExpiration()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getExpiration', array());

        return parent::getExpiration();
    }

    /**
     * {@inheritDoc}
     */
    public function setValidity($validity)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setValidity', array($validity));

        return parent::setValidity($validity);
    }

    /**
     * {@inheritDoc}
     */
    public function getValidity()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getValidity', array());

        return parent::getValidity();
    }

    /**
     * {@inheritDoc}
     */
    public function setLastAuth($lastAuth)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setLastAuth', array($lastAuth));

        return parent::setLastAuth($lastAuth);
    }

    /**
     * {@inheritDoc}
     */
    public function getLastAuth()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getLastAuth', array());

        return parent::getLastAuth();
    }

    /**
     * {@inheritDoc}
     */
    public function setLastAuthStatus($lastAuthStatus)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setLastAuthStatus', array($lastAuthStatus));

        return parent::setLastAuthStatus($lastAuthStatus);
    }

    /**
     * {@inheritDoc}
     */
    public function getLastAuthStatus()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getLastAuthStatus', array());

        return parent::getLastAuthStatus();
    }

    /**
     * {@inheritDoc}
     */
    public function setLastActivity($lastActivity)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setLastActivity', array($lastActivity));

        return parent::setLastActivity($lastActivity);
    }

    /**
     * {@inheritDoc}
     */
    public function getLastActivity()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getLastActivity', array());

        return parent::getLastActivity();
    }

    /**
     * {@inheritDoc}
     */
    public function setEmail($email)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setEmail', array($email));

        return parent::setEmail($email);
    }

    /**
     * {@inheritDoc}
     */
    public function getEmail()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getEmail', array());

        return parent::getEmail();
    }

    /**
     * {@inheritDoc}
     */
    public function setEmailAccess($emailAccess)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setEmailAccess', array($emailAccess));

        return parent::setEmailAccess($emailAccess);
    }

    /**
     * {@inheritDoc}
     */
    public function getEmailAccess()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getEmailAccess', array());

        return parent::getEmailAccess();
    }

    /**
     * {@inheritDoc}
     */
    public function setFrontendLangId($frontendLangId)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setFrontendLangId', array($frontendLangId));

        return parent::setFrontendLangId($frontendLangId);
    }

    /**
     * {@inheritDoc}
     */
    public function getFrontendLangId()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getFrontendLangId', array());

        return parent::getFrontendLangId();
    }

    /**
     * {@inheritDoc}
     */
    public function setBackendLangId($backendLangId)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setBackendLangId', array($backendLangId));

        return parent::setBackendLangId($backendLangId);
    }

    /**
     * {@inheritDoc}
     */
    public function getBackendLangId()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getBackendLangId', array());

        return parent::getBackendLangId();
    }

    /**
     * {@inheritDoc}
     */
    public function setActive($active)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setActive', array($active));

        return parent::setActive($active);
    }

    /**
     * {@inheritDoc}
     */
    public function getActive()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getActive', array());

        return parent::getActive();
    }

    /**
     * {@inheritDoc}
     */
    public function isActive()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isActive', array());

        return parent::isActive();
    }

    /**
     * {@inheritDoc}
     */
    public function setVerified($verified)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setVerified', array($verified));

        return parent::setVerified($verified);
    }

    /**
     * {@inheritDoc}
     */
    public function getVerified()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getVerified', array());

        return parent::getVerified();
    }

    /**
     * {@inheritDoc}
     */
    public function setPrimaryGroup($primaryGroup)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setPrimaryGroup', array($primaryGroup));

        return parent::setPrimaryGroup($primaryGroup);
    }

    /**
     * {@inheritDoc}
     */
    public function getPrimaryGroup()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getPrimaryGroup', array());

        return parent::getPrimaryGroup();
    }

    /**
     * {@inheritDoc}
     */
    public function setProfileAccess($profileAccess)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setProfileAccess', array($profileAccess));

        return parent::setProfileAccess($profileAccess);
    }

    /**
     * {@inheritDoc}
     */
    public function getProfileAccess()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getProfileAccess', array());

        return parent::getProfileAccess();
    }

    /**
     * {@inheritDoc}
     */
    public function setRestoreKey($restoreKey = NULL)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setRestoreKey', array($restoreKey));

        return parent::setRestoreKey($restoreKey);
    }

    /**
     * {@inheritDoc}
     */
    public function getRestoreKey()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getRestoreKey', array());

        return parent::getRestoreKey();
    }

    /**
     * {@inheritDoc}
     */
    public function setRestoreKeyTime($restoreKeyTime)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setRestoreKeyTime', array($restoreKeyTime));

        return parent::setRestoreKeyTime($restoreKeyTime);
    }

    /**
     * {@inheritDoc}
     */
    public function getRestoreKeyTime()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getRestoreKeyTime', array());

        return parent::getRestoreKeyTime();
    }

    /**
     * {@inheritDoc}
     */
    public function setU2uActive($u2uActive)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setU2uActive', array($u2uActive));

        return parent::setU2uActive($u2uActive);
    }

    /**
     * {@inheritDoc}
     */
    public function getU2uActive()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getU2uActive', array());

        return parent::getU2uActive();
    }

    /**
     * {@inheritDoc}
     */
    public function addGroup(\Cx\Core\User\Model\Entity\Group $group)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'addGroup', array($group));

        return parent::addGroup($group);
    }

    /**
     * {@inheritDoc}
     */
    public function removeGroup(\Cx\Core\User\Model\Entity\Group $group)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'removeGroup', array($group));

        return parent::removeGroup($group);
    }

    /**
     * {@inheritDoc}
     */
    public function getGroup()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getGroup', array());

        return parent::getGroup();
    }

    /**
     * {@inheritDoc}
     */
    public function getGroups()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getGroups', array());

        return parent::getGroups();
    }

    /**
     * {@inheritDoc}
     */
    public function addUserAttributeValue(\Cx\Core\User\Model\Entity\UserAttributeValue $userAttributeValue)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'addUserAttributeValue', array($userAttributeValue));

        return parent::addUserAttributeValue($userAttributeValue);
    }

    /**
     * {@inheritDoc}
     */
    public function removeUserAttributeValue(\Cx\Core\User\Model\Entity\UserAttributeValue $userAttributeValue)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'removeUserAttributeValue', array($userAttributeValue));

        return parent::removeUserAttributeValue($userAttributeValue);
    }

    /**
     * {@inheritDoc}
     */
    public function getUserAttributeValue()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getUserAttributeValue', array());

        return parent::getUserAttributeValue();
    }

    /**
     * {@inheritDoc}
     */
    public function getUserAttributeValues()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getUserAttributeValues', array());

        return parent::getUserAttributeValues();
    }

    /**
     * {@inheritDoc}
     */
    public function isBackendGroupUser()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isBackendGroupUser', array());

        return parent::isBackendGroupUser();
    }

    /**
     * {@inheritDoc}
     */
    public function getAttributeValue($attributeId)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getAttributeValue', array($attributeId));

        return parent::getAttributeValue($attributeId);
    }

    /**
     * {@inheritDoc}
     */
    public function getProfileAttribute($attributeId)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getProfileAttribute', array($attributeId));

        return parent::getProfileAttribute($attributeId);
    }

    /**
     * {@inheritDoc}
     */
    public function getComponentController()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getComponentController', array());

        return parent::getComponentController();
    }

    /**
     * {@inheritDoc}
     */
    public function setVirtual($virtual)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setVirtual', array($virtual));

        return parent::setVirtual($virtual);
    }

    /**
     * {@inheritDoc}
     */
    public function isVirtual()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isVirtual', array());

        return parent::isVirtual();
    }

    /**
     * {@inheritDoc}
     */
    public function initializeValidators()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'initializeValidators', array());

        return parent::initializeValidators();
    }

    /**
     * {@inheritDoc}
     */
    public function validate()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'validate', array());

        return parent::validate();
    }

    /**
     * {@inheritDoc}
     */
    public function __call($methodName, $arguments)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, '__call', array($methodName, $arguments));

        return parent::__call($methodName, $arguments);
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, '__toString', array());

        return parent::__toString();
    }

}
