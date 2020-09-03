<?php

namespace Cx\Model\Proxies\__CG__\Cx\Modules\Shop\Model\Entity;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class DiscountCoupon extends \Cx\Modules\Shop\Model\Entity\DiscountCoupon implements \Doctrine\ORM\Proxy\Proxy
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
            return array('__isInitialized__', 'id', 'code', 'customerId', 'paymentId', 'productId', 'startTime', 'endTime', 'uses', 'global', 'minimumAmount', 'discountAmount', 'discountRate', 'payment', 'product', 'customer', 'validators', 'virtual');
        }

        return array('__isInitialized__', 'id', 'code', 'customerId', 'paymentId', 'productId', 'startTime', 'endTime', 'uses', 'global', 'minimumAmount', 'discountAmount', 'discountRate', 'payment', 'product', 'customer', 'validators', 'virtual');
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (DiscountCoupon $proxy) {
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
    public function setId($id)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setId', array($id));

        return parent::setId($id);
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
    public function setCode($code)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setCode', array($code));

        return parent::setCode($code);
    }

    /**
     * {@inheritDoc}
     */
    public function getCode()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getCode', array());

        return parent::getCode();
    }

    /**
     * {@inheritDoc}
     */
    public function setCustomerId($customerId)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setCustomerId', array($customerId));

        return parent::setCustomerId($customerId);
    }

    /**
     * {@inheritDoc}
     */
    public function getCustomerId()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getCustomerId', array());

        return parent::getCustomerId();
    }

    /**
     * {@inheritDoc}
     */
    public function setPaymentId($paymentId)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setPaymentId', array($paymentId));

        return parent::setPaymentId($paymentId);
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentId()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getPaymentId', array());

        return parent::getPaymentId();
    }

    /**
     * {@inheritDoc}
     */
    public function setProductId($productId)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setProductId', array($productId));

        return parent::setProductId($productId);
    }

    /**
     * {@inheritDoc}
     */
    public function getProductId()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getProductId', array());

        return parent::getProductId();
    }

    /**
     * {@inheritDoc}
     */
    public function setStartTime($startTime)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setStartTime', array($startTime));

        return parent::setStartTime($startTime);
    }

    /**
     * {@inheritDoc}
     */
    public function getStartTime()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getStartTime', array());

        return parent::getStartTime();
    }

    /**
     * {@inheritDoc}
     */
    public function setEndTime($endTime)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setEndTime', array($endTime));

        return parent::setEndTime($endTime);
    }

    /**
     * {@inheritDoc}
     */
    public function getEndTime()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getEndTime', array());

        return parent::getEndTime();
    }

    /**
     * {@inheritDoc}
     */
    public function setUses($uses)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setUses', array($uses));

        return parent::setUses($uses);
    }

    /**
     * {@inheritDoc}
     */
    public function getUses()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getUses', array());

        return parent::getUses();
    }

    /**
     * {@inheritDoc}
     */
    public function setGlobal($global)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setGlobal', array($global));

        return parent::setGlobal($global);
    }

    /**
     * {@inheritDoc}
     */
    public function getGlobal()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getGlobal', array());

        return parent::getGlobal();
    }

    /**
     * {@inheritDoc}
     */
    public function setMinimumAmount($minimumAmount)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setMinimumAmount', array($minimumAmount));

        return parent::setMinimumAmount($minimumAmount);
    }

    /**
     * {@inheritDoc}
     */
    public function getMinimumAmount()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getMinimumAmount', array());

        return parent::getMinimumAmount();
    }

    /**
     * {@inheritDoc}
     */
    public function setDiscountAmount($discountAmount)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setDiscountAmount', array($discountAmount));

        return parent::setDiscountAmount($discountAmount);
    }

    /**
     * {@inheritDoc}
     */
    public function getDiscountAmount()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getDiscountAmount', array());

        return parent::getDiscountAmount();
    }

    /**
     * {@inheritDoc}
     */
    public function setDiscountRate($discountRate)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setDiscountRate', array($discountRate));

        return parent::setDiscountRate($discountRate);
    }

    /**
     * {@inheritDoc}
     */
    public function getDiscountRate()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getDiscountRate', array());

        return parent::getDiscountRate();
    }

    /**
     * {@inheritDoc}
     */
    public function setPayment(\Cx\Modules\Shop\Model\Entity\Payment $payment = NULL)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setPayment', array($payment));

        return parent::setPayment($payment);
    }

    /**
     * {@inheritDoc}
     */
    public function getPayment()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getPayment', array());

        return parent::getPayment();
    }

    /**
     * {@inheritDoc}
     */
    public function setProduct(\Cx\Modules\Shop\Model\Entity\Product $product = NULL)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setProduct', array($product));

        return parent::setProduct($product);
    }

    /**
     * {@inheritDoc}
     */
    public function getProduct()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getProduct', array());

        return parent::getProduct();
    }

    /**
     * {@inheritDoc}
     */
    public function setCustomer(\Cx\Core\User\Model\Entity\User $customer = NULL)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setCustomer', array($customer));

        return parent::setCustomer($customer);
    }

    /**
     * {@inheritDoc}
     */
    public function getCustomer()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getCustomer', array());

        return parent::getCustomer();
    }

    /**
     * {@inheritDoc}
     */
    public function getUsedCount($customerId = 0)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getUsedCount', array($customerId));

        return parent::getUsedCount($customerId);
    }

    /**
     * {@inheritDoc}
     */
    public function getUsedAmount($customer_id = NULL, $order_id = NULL)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getUsedAmount', array($customer_id, $order_id));

        return parent::getUsedAmount($customer_id, $order_id);
    }

    /**
     * {@inheritDoc}
     */
    public function getDiscountAmountOrRate($amount, $customer_id = NULL)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getDiscountAmountOrRate', array($amount, $customer_id));

        return parent::getDiscountAmountOrRate($amount, $customer_id);
    }

    /**
     * {@inheritDoc}
     */
    public function redeem($order_id, $customer_id, $amount, $uses = 1)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'redeem', array($order_id, $customer_id, $amount, $uses));

        return parent::redeem($order_id, $customer_id, $amount, $uses);
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
