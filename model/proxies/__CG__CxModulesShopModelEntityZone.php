<?php

namespace Cx\Model\Proxies\__CG__\Cx\Modules\Shop\Model\Entity;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class Zone extends \Cx\Modules\Shop\Model\Entity\Zone implements \Doctrine\ORM\Proxy\Proxy
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
            return array('__isInitialized__', 'locale', 'id', 'active', 'name', 'relCountries', 'payments', 'shippers', 'stringRepresentationFields', 'stringRepresentationFormat', 'validators', 'virtual');
        }

        return array('__isInitialized__', 'locale', 'id', 'active', 'name', 'relCountries', 'payments', 'shippers', 'stringRepresentationFields', 'stringRepresentationFormat', 'validators', 'virtual');
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (Zone $proxy) {
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
    public function setTranslatableLocale($locale)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setTranslatableLocale', array($locale));

        return parent::setTranslatableLocale($locale);
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
    public function setName($name)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setName', array($name));

        return parent::setName($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getName', array());

        return parent::getName();
    }

    /**
     * {@inheritDoc}
     */
    public function addRelCountry(\Cx\Modules\Shop\Model\Entity\RelCountry $relCountry)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'addRelCountry', array($relCountry));

        return parent::addRelCountry($relCountry);
    }

    /**
     * {@inheritDoc}
     */
    public function removeRelCountry(\Cx\Modules\Shop\Model\Entity\RelCountry $relCountry)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'removeRelCountry', array($relCountry));

        return parent::removeRelCountry($relCountry);
    }

    /**
     * {@inheritDoc}
     */
    public function getRelCountries()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getRelCountries', array());

        return parent::getRelCountries();
    }

    /**
     * {@inheritDoc}
     */
    public function addPayment(\Cx\Modules\Shop\Model\Entity\Payment $payment)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'addPayment', array($payment));

        return parent::addPayment($payment);
    }

    /**
     * {@inheritDoc}
     */
    public function removePayment(\Cx\Modules\Shop\Model\Entity\Payment $payment)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'removePayment', array($payment));

        return parent::removePayment($payment);
    }

    /**
     * {@inheritDoc}
     */
    public function getPayments()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getPayments', array());

        return parent::getPayments();
    }

    /**
     * {@inheritDoc}
     */
    public function addShipper(\Cx\Modules\Shop\Model\Entity\Shipper $shipper)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'addShipper', array($shipper));

        return parent::addShipper($shipper);
    }

    /**
     * {@inheritDoc}
     */
    public function removeShipper(\Cx\Modules\Shop\Model\Entity\Shipper $shipper)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'removeShipper', array($shipper));

        return parent::removeShipper($shipper);
    }

    /**
     * {@inheritDoc}
     */
    public function getShippers()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getShippers', array());

        return parent::getShippers();
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
    public function getTranslatedFieldValue($fieldName)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getTranslatedFieldValue', array($fieldName));

        return parent::getTranslatedFieldValue($fieldName);
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
