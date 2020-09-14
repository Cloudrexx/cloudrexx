<?php

namespace Cx\Model\Proxies\__CG__\Cx\Core\MediaSource\Model\Entity;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class MediaSource extends \Cx\Core\MediaSource\Model\Entity\MediaSource implements \Doctrine\ORM\Proxy\Proxy
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
            return array('__isInitialized__', 'supportedOperations', 'name', 'position', 'humanName', 'directory', 'accessIds', 'fileSystem', 'systemComponentController', 'id', 'identifier', 'type', 'options', 'dataAccesses', 'validators', 'virtual', 'stringRepresentationFields', 'stringRepresentationFormat', 'stringRepresentationBlank');
        }

        return array('__isInitialized__', 'supportedOperations', 'name', 'position', 'humanName', 'directory', 'accessIds', 'fileSystem', 'systemComponentController', 'id', 'identifier', 'type', 'options', 'dataAccesses', 'validators', 'virtual', 'stringRepresentationFields', 'stringRepresentationFormat', 'stringRepresentationBlank');
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (MediaSource $proxy) {
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
    public function getName()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getName', array());

        return parent::getName();
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
    public function getDirectory()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getDirectory', array());

        return parent::getDirectory();
    }

    /**
     * {@inheritDoc}
     */
    public function getAccessIds()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getAccessIds', array());

        return parent::getAccessIds();
    }

    /**
     * {@inheritDoc}
     */
    public function setAccessIds($accessIds)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setAccessIds', array($accessIds));

        return parent::setAccessIds($accessIds);
    }

    /**
     * {@inheritDoc}
     */
    public function checkAccess()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'checkAccess', array());

        return parent::checkAccess();
    }

    /**
     * {@inheritDoc}
     */
    public function getHumanName()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getHumanName', array());

        return parent::getHumanName();
    }

    /**
     * {@inheritDoc}
     */
    public function setHumanName($humanName)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setHumanName', array($humanName));

        return parent::setHumanName($humanName);
    }

    /**
     * {@inheritDoc}
     */
    public function getPosition()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getPosition', array());

        return parent::getPosition();
    }

    /**
     * {@inheritDoc}
     */
    public function setPosition($position)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setPosition', array($position));

        return parent::setPosition($position);
    }

    /**
     * {@inheritDoc}
     */
    public function getFileSystem()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getFileSystem', array());

        return parent::getFileSystem();
    }

    /**
     * {@inheritDoc}
     */
    public function getSystemComponentController()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getSystemComponentController', array());

        return parent::getSystemComponentController();
    }

    /**
     * {@inheritDoc}
     */
    public function listFields()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'listFields', array());

        return parent::listFields();
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifierFieldNames()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getIdentifierFieldNames', array());

        return parent::getIdentifierFieldNames();
    }

    /**
     * {@inheritDoc}
     */
    public function get($elementId = array (
), $filter = array (
), $order = array (
), $limit = 0, $offset = 0, $fieldList = array (
))
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'get', array($elementId, $filter, $order, $limit, $offset, $fieldList));

        return parent::get($elementId, $filter, $order, $limit, $offset, $fieldList);
    }

    /**
     * {@inheritDoc}
     */
    public function add($data)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'add', array($data));

        return parent::add($data);
    }

    /**
     * {@inheritDoc}
     */
    public function update($elementId, $data)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'update', array($elementId, $data));

        return parent::update($elementId, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function remove($elementId)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'remove', array($elementId));

        return parent::remove($elementId);
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
    public function setIdentifier($identifier)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setIdentifier', array($identifier));

        return parent::setIdentifier($identifier);
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getIdentifier', array());

        return parent::getIdentifier();
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
    public function setOptions($options)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setOptions', array($options));

        return parent::setOptions($options);
    }

    /**
     * {@inheritDoc}
     */
    public function getOptions()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getOptions', array());

        return parent::getOptions();
    }

    /**
     * {@inheritDoc}
     */
    public function getOption($key)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getOption', array($key));

        return parent::getOption($key);
    }

    /**
     * {@inheritDoc}
     */
    public function isVersionable()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isVersionable', array());

        return parent::isVersionable();
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrentVersion(array $elementId)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getCurrentVersion', array($elementId));

        return parent::getCurrentVersion($elementId);
    }

    /**
     * {@inheritDoc}
     */
    public function addDataAccess(\Cx\Core_Modules\DataAccess\Model\Entity\DataAccess $dataAccesses)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'addDataAccess', array($dataAccesses));

        return parent::addDataAccess($dataAccesses);
    }

    /**
     * {@inheritDoc}
     */
    public function removeDataAccess(\Cx\Core_Modules\DataAccess\Model\Entity\DataAccess $dataAccesses)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'removeDataAccess', array($dataAccesses));

        return parent::removeDataAccess($dataAccesses);
    }

    /**
     * {@inheritDoc}
     */
    public function setDataAccesses(\Cx\Core_Modules\DataAccess\Model\Entity\DataAccess $dataAccesses)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setDataAccesses', array($dataAccesses));

        return parent::setDataAccesses($dataAccesses);
    }

    /**
     * {@inheritDoc}
     */
    public function getDataAccesses()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getDataAccesses', array());

        return parent::getDataAccesses();
    }

    /**
     * {@inheritDoc}
     */
    public function hasField($field)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'hasField', array($field));

        return parent::hasField($field);
    }

    /**
     * {@inheritDoc}
     */
    public function getSupportedOperations()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getSupportedOperations', array());

        return parent::getSupportedOperations();
    }

    /**
     * {@inheritDoc}
     */
    public function supportsOperation($operation)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'supportsOperation', array($operation));

        return parent::supportsOperation($operation);
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
