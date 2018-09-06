<?php

namespace Cx\Modules\Shop\Model\Entity;

/**
 * DiscountgroupCountRate
 */
class DiscountgroupCountRate extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer
     */
    protected $groupId;

    /**
     * @var integer
     */
    protected $count;

    /**
     * @var string
     */
    protected $rate;

    /**
     * @var \Cx\Modules\Shop\Model\Entity\DiscountgroupCountName
     */
    protected $discountgroupCountName;


    /**
     * Set groupId
     *
     * @param integer $groupId
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;
    }

    /**
     * Get groupId
     *
     * @return integer 
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * Set count
     *
     * @param integer $count
     */
    public function setCount($count)
    {
        $this->count = $count;
    }

    /**
     * Get count
     *
     * @return integer 
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * Set rate
     *
     * @param string $rate
     */
    public function setRate($rate)
    {
        $this->rate = $rate;
    }

    /**
     * Get rate
     *
     * @return string 
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * Set discountgroupCountName
     *
     * @param \Cx\Modules\Shop\Model\Entity\DiscountgroupCountName $discountgroupCountName
     */
    public function setDiscountgroupCountName(\Cx\Modules\Shop\Model\Entity\DiscountgroupCountName $discountgroupCountName = null)
    {
        $this->discountgroupCountName = $discountgroupCountName;
    }

    /**
     * Get discountgroupCountName
     *
     * @return \Cx\Modules\Shop\Model\Entity\DiscountgroupCountName 
     */
    public function getDiscountgroupCountName()
    {
        return $this->discountgroupCountName;
    }
}
