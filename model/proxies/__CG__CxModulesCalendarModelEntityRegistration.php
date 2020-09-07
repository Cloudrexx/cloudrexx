<?php

namespace Cx\Model\Proxies\__CG__\Cx\Modules\Calendar\Model\Entity;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class Registration extends \Cx\Modules\Calendar\Model\Entity\Registration implements \Doctrine\ORM\Proxy\Proxy
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
            return array('__isInitialized__', 'id', 'date', 'submissionDate', 'type', 'invite', 'userId', 'langId', 'export', 'paymentMethod', 'paid', 'registrationFormFieldValues', 'event', 'validators', 'virtual');
        }

        return array('__isInitialized__', 'id', 'date', 'submissionDate', 'type', 'invite', 'userId', 'langId', 'export', 'paymentMethod', 'paid', 'registrationFormFieldValues', 'event', 'validators', 'virtual');
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (Registration $proxy) {
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
    public function setDate($date)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setDate', array($date));

        return parent::setDate($date);
    }

    /**
     * {@inheritDoc}
     */
    public function getDate()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getDate', array());

        return parent::getDate();
    }

    /**
     * {@inheritDoc}
     */
    public function setSubmissionDate($submissionDate)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setSubmissionDate', array($submissionDate));

        return parent::setSubmissionDate($submissionDate);
    }

    /**
     * {@inheritDoc}
     */
    public function getSubmissionDate()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getSubmissionDate', array());

        return parent::getSubmissionDate();
    }

    /**
     * {@inheritDoc}
     */
    public function setType($type)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setType', array($type));

        return parent::setType($type);
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getType', array());

        return parent::getType();
    }

    /**
     * {@inheritDoc}
     */
    public function setInvite($invite)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setInvite', array($invite));

        return parent::setInvite($invite);
    }

    /**
     * {@inheritDoc}
     */
    public function getInvite()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getInvite', array());

        return parent::getInvite();
    }

    /**
     * {@inheritDoc}
     */
    public function setUserId($userId)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setUserId', array($userId));

        return parent::setUserId($userId);
    }

    /**
     * {@inheritDoc}
     */
    public function getUserId()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getUserId', array());

        return parent::getUserId();
    }

    /**
     * {@inheritDoc}
     */
    public function setLangId($langId)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setLangId', array($langId));

        return parent::setLangId($langId);
    }

    /**
     * {@inheritDoc}
     */
    public function getLangId()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getLangId', array());

        return parent::getLangId();
    }

    /**
     * {@inheritDoc}
     */
    public function setExport($export)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setExport', array($export));

        return parent::setExport($export);
    }

    /**
     * {@inheritDoc}
     */
    public function getExport()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getExport', array());

        return parent::getExport();
    }

    /**
     * {@inheritDoc}
     */
    public function setPaymentMethod($paymentMethod)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setPaymentMethod', array($paymentMethod));

        return parent::setPaymentMethod($paymentMethod);
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentMethod()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getPaymentMethod', array());

        return parent::getPaymentMethod();
    }

    /**
     * {@inheritDoc}
     */
    public function setPaid($paid)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setPaid', array($paid));

        return parent::setPaid($paid);
    }

    /**
     * {@inheritDoc}
     */
    public function getPaid()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getPaid', array());

        return parent::getPaid();
    }

    /**
     * {@inheritDoc}
     */
    public function addRegistrationFormFieldValue(\Cx\Modules\Calendar\Model\Entity\RegistrationFormFieldValue $registrationFormFieldValue)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'addRegistrationFormFieldValue', array($registrationFormFieldValue));

        return parent::addRegistrationFormFieldValue($registrationFormFieldValue);
    }

    /**
     * {@inheritDoc}
     */
    public function removeRegistrationFormFieldValue(\Cx\Modules\Calendar\Model\Entity\RegistrationFormFieldValue $registrationFormFieldValues)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'removeRegistrationFormFieldValue', array($registrationFormFieldValues));

        return parent::removeRegistrationFormFieldValue($registrationFormFieldValues);
    }

    /**
     * {@inheritDoc}
     */
    public function setRegistrationFormFieldValues($registrationFormFieldValues)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setRegistrationFormFieldValues', array($registrationFormFieldValues));

        return parent::setRegistrationFormFieldValues($registrationFormFieldValues);
    }

    /**
     * {@inheritDoc}
     */
    public function getRegistrationFormFieldValueByFieldId($fieldId)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getRegistrationFormFieldValueByFieldId', array($fieldId));

        return parent::getRegistrationFormFieldValueByFieldId($fieldId);
    }

    /**
     * {@inheritDoc}
     */
    public function getRegistrationFormFieldValues()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getRegistrationFormFieldValues', array());

        return parent::getRegistrationFormFieldValues();
    }

    /**
     * {@inheritDoc}
     */
    public function setEvent(\Cx\Modules\Calendar\Model\Entity\Event $event)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setEvent', array($event));

        return parent::setEvent($event);
    }

    /**
     * {@inheritDoc}
     */
    public function getEvent()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getEvent', array());

        return parent::getEvent();
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
