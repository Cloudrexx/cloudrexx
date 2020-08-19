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
 * Class DiscountgroupCountRate
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
namespace Cx\Modules\Shop\Model\Entity;

/**
 * DiscountgroupCountRate is used for the quantity discount group.
 * Defines the rate for a minimum quantity.
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
class DiscountgroupCountRate extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer
     */
    protected $groupId;

    /**
     * @var integer
     */
    protected $count = 1;

    /**
     * @var string
     */
    protected $rate = '0.00';

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
