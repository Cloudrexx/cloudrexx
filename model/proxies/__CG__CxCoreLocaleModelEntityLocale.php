<?php

namespace Cx\Model\Proxies\__CG__\Cx\Core\Locale\Model\Entity;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class Locale extends \Cx\Core\Locale\Model\Entity\Locale implements \Doctrine\ORM\Proxy\Proxy
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
            return array('__isInitialized__', 'id', 'label', 'orderNo', 'locales', 'iso1', 'country', 'fallback', 'sourceLanguage', 'frontends', 'validators', 'virtual', 'stringRepresentationFields', 'stringRepresentationFormat', 'stringRepresentationBlank');
        }

        return array('__isInitialized__', 'id', 'label', 'orderNo', 'locales', 'iso1', 'country', 'fallback', 'sourceLanguage', 'frontends', 'validators', 'virtual', 'stringRepresentationFields', 'stringRepresentationFormat', 'stringRepresentationBlank');
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (Locale $proxy) {
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
    public function setLabel($label)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setLabel', array($label));

        return parent::setLabel($label);
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getLabel', array());

        return parent::getLabel();
    }

    /**
     * {@inheritDoc}
     */
    public function setOrderNo($orderNo)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setOrderNo', array($orderNo));

        return parent::setOrderNo($orderNo);
    }

    /**
     * {@inheritDoc}
     */
    public function getOrderNo()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getOrderNo', array());

        return parent::getOrderNo();
    }

    /**
     * {@inheritDoc}
     */
    public function addLocale(\Cx\Core\Locale\Model\Entity\Locale $locales)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'addLocale', array($locales));

        return parent::addLocale($locales);
    }

    /**
     * {@inheritDoc}
     */
    public function removeLocale(\Cx\Core\Locale\Model\Entity\Locale $locales)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'removeLocale', array($locales));

        return parent::removeLocale($locales);
    }

    /**
     * {@inheritDoc}
     */
    public function addLocales(\Cx\Core\Locale\Model\Entity\Locale $locales)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'addLocales', array($locales));

        return parent::addLocales($locales);
    }

    /**
     * {@inheritDoc}
     */
    public function getLocales()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getLocales', array());

        return parent::getLocales();
    }

    /**
     * {@inheritDoc}
     */
    public function setIso1(\Cx\Core\Locale\Model\Entity\Language $iso1)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setIso1', array($iso1));

        return parent::setIso1($iso1);
    }

    /**
     * {@inheritDoc}
     */
    public function getIso1()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getIso1', array());

        return parent::getIso1();
    }

    /**
     * {@inheritDoc}
     */
    public function setCountry(\Cx\Core\Country\Model\Entity\Country $country = NULL)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setCountry', array($country));

        return parent::setCountry($country);
    }

    /**
     * {@inheritDoc}
     */
    public function getCountry()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getCountry', array());

        return parent::getCountry();
    }

    /**
     * {@inheritDoc}
     */
    public function setFallback(\Cx\Core\Locale\Model\Entity\Locale $fallback = NULL)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setFallback', array($fallback));

        return parent::setFallback($fallback);
    }

    /**
     * {@inheritDoc}
     */
    public function getFallback()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getFallback', array());

        return parent::getFallback();
    }

    /**
     * {@inheritDoc}
     */
    public function setSourceLanguage(\Cx\Core\Locale\Model\Entity\Language $sourceLanguage)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setSourceLanguage', array($sourceLanguage));

        return parent::setSourceLanguage($sourceLanguage);
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceLanguage()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getSourceLanguage', array());

        return parent::getSourceLanguage();
    }

    /**
     * {@inheritDoc}
     */
    public function addFrontend(\Cx\Core\View\Model\Entity\Frontend $frontends)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'addFrontend', array($frontends));

        return parent::addFrontend($frontends);
    }

    /**
     * {@inheritDoc}
     */
    public function removeFrontend(\Cx\Core\View\Model\Entity\Frontend $frontends)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'removeFrontend', array($frontends));

        return parent::removeFrontend($frontends);
    }

    /**
     * {@inheritDoc}
     */
    public function addFrontends(\Cx\Core\View\Model\Entity\Frontend $frontends)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'addFrontends', array($frontends));

        return parent::addFrontends($frontends);
    }

    /**
     * {@inheritDoc}
     */
    public function getFrontends()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getFrontends', array());

        return parent::getFrontends();
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, '__toString', array());

        return parent::__toString();
    }

    /**
     * {@inheritDoc}
     */
    public function getShortForm()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getShortForm', array());

        return parent::getShortForm();
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

}
