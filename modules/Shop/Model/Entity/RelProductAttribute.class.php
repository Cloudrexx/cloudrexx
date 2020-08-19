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
 * Class RelProductAttribute
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
namespace Cx\Modules\Shop\Model\Entity;

/**
 * Relation between product and attribut option. A Product could has custom
 * attribut options. A option has attributes. The relations can be ordered.
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
class RelProductAttribute extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer
     */
    protected $productId;

    /**
     * @var integer
     */
    protected $optionId;

    /**
     * @var integer
     */
    protected $ord;

    /**
     * @var \Cx\Modules\Shop\Model\Entity\Product
     */
    protected $product;

    /**
     * @var \Cx\Modules\Shop\Model\Entity\Option
     */
    protected $option;


    /**
     * Set productId
     *
     * @param integer $productId
     */
    public function setProductId($productId)
    {
        $this->productId = $productId;
    }

    /**
     * Get productId
     *
     * @return integer 
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * Set optionId
     *
     * @param integer $optionId
     */
    public function setOptionId($optionId)
    {
        $this->optionId = $optionId;
    }

    /**
     * Get optionId
     *
     * @return integer 
     */
    public function getOptionId()
    {
        return $this->optionId;
    }

    /**
     * Set ord
     *
     * @param integer $ord
     */
    public function setOrd($ord)
    {
        $this->ord = $ord;
    }

    /**
     * Get ord
     *
     * @return integer 
     */
    public function getOrd()
    {
        return $this->ord;
    }

    /**
     * Set product
     *
     * @param \Cx\Modules\Shop\Model\Entity\Product $product
     */
    public function setProduct(\Cx\Modules\Shop\Model\Entity\Product $product = null)
    {
        $this->product = $product;
    }

    /**
     * Get product
     *
     * @return \Cx\Modules\Shop\Model\Entity\Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Set option
     *
     * @param \Cx\Modules\Shop\Model\Entity\Option $option
     */
    public function setOption(\Cx\Modules\Shop\Model\Entity\Option $option = null)
    {
        $this->option = $option;
    }

    /**
     * Get option
     *
     * @return \Cx\Modules\Shop\Model\Entity\Option 
     */
    public function getOption()
    {
        return $this->option;
    }
}
