<?php

namespace Cx\Model\Proxies\__CG__\Cx\Modules\Calendar\Model\Entity;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class EventField extends \Cx\Modules\Calendar\Model\Entity\EventField implements \Doctrine\ORM\Proxy\Proxy
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
            return array('__isInitialized__', 'eventId', 'title', 'langId', 'teaser', 'description', 'redirect', 'place', 'placeCity', 'placeCountry', 'orgName', 'orgCity', 'orgCountry', 'event', 'validators', 'virtual', 'stringRepresentationFields', 'stringRepresentationFormat', 'stringRepresentationBlank');
        }

        return array('__isInitialized__', 'eventId', 'title', 'langId', 'teaser', 'description', 'redirect', 'place', 'placeCity', 'placeCountry', 'orgName', 'orgCity', 'orgCountry', 'event', 'validators', 'virtual', 'stringRepresentationFields', 'stringRepresentationFormat', 'stringRepresentationBlank');
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (EventField $proxy) {
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
    public function setEventId($eventId)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setEventId', array($eventId));

        return parent::setEventId($eventId);
    }

    /**
     * {@inheritDoc}
     */
    public function getEventId()
    {
        if ($this->__isInitialized__ === false) {
            return (int)  parent::getEventId();
        }


        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getEventId', array());

        return parent::getEventId();
    }

    /**
     * {@inheritDoc}
     */
    public function setTitle($title)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setTitle', array($title));

        return parent::setTitle($title);
    }

    /**
     * {@inheritDoc}
     */
    public function getTitle()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getTitle', array());

        return parent::getTitle();
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
        if ($this->__isInitialized__ === false) {
            return (int)  parent::getLangId();
        }


        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getLangId', array());

        return parent::getLangId();
    }

    /**
     * {@inheritDoc}
     */
    public function setTeaser($teaser)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setTeaser', array($teaser));

        return parent::setTeaser($teaser);
    }

    /**
     * {@inheritDoc}
     */
    public function getTeaser()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getTeaser', array());

        return parent::getTeaser();
    }

    /**
     * {@inheritDoc}
     */
    public function setDescription($description)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setDescription', array($description));

        return parent::setDescription($description);
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getDescription', array());

        return parent::getDescription();
    }

    /**
     * {@inheritDoc}
     */
    public function setRedirect($redirect)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setRedirect', array($redirect));

        return parent::setRedirect($redirect);
    }

    /**
     * {@inheritDoc}
     */
    public function getRedirect()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getRedirect', array());

        return parent::getRedirect();
    }

    /**
     * {@inheritDoc}
     */
    public function setPlace($place)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setPlace', array($place));

        return parent::setPlace($place);
    }

    /**
     * {@inheritDoc}
     */
    public function getPlace()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getPlace', array());

        return parent::getPlace();
    }

    /**
     * {@inheritDoc}
     */
    public function setPlaceCity($placeCity)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setPlaceCity', array($placeCity));

        return parent::setPlaceCity($placeCity);
    }

    /**
     * {@inheritDoc}
     */
    public function getPlaceCity()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getPlaceCity', array());

        return parent::getPlaceCity();
    }

    /**
     * {@inheritDoc}
     */
    public function setPlaceCountry($placeCountry)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setPlaceCountry', array($placeCountry));

        return parent::setPlaceCountry($placeCountry);
    }

    /**
     * {@inheritDoc}
     */
    public function getPlaceCountry()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getPlaceCountry', array());

        return parent::getPlaceCountry();
    }

    /**
     * {@inheritDoc}
     */
    public function setOrgName($orgName)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setOrgName', array($orgName));

        return parent::setOrgName($orgName);
    }

    /**
     * {@inheritDoc}
     */
    public function getOrgName()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getOrgName', array());

        return parent::getOrgName();
    }

    /**
     * {@inheritDoc}
     */
    public function setOrgCity($orgCity)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setOrgCity', array($orgCity));

        return parent::setOrgCity($orgCity);
    }

    /**
     * {@inheritDoc}
     */
    public function getOrgCity()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getOrgCity', array());

        return parent::getOrgCity();
    }

    /**
     * {@inheritDoc}
     */
    public function setOrgCountry($orgCountry)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setOrgCountry', array($orgCountry));

        return parent::setOrgCountry($orgCountry);
    }

    /**
     * {@inheritDoc}
     */
    public function getOrgCountry()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getOrgCountry', array());

        return parent::getOrgCountry();
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
