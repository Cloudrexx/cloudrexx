<?php

/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2015
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


namespace Cx\Modules\Pim\Model\Entity;

/**
 * Cx\Modules\Pim\Model\Entity\Price
 */
class Price extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var decimal $amount
     */
    protected $amount;

    /**
     * @var \Cx\Modules\Crm\Model\Entity\Currency
     */
    protected $currency;

    /**
     * @var \Cx\Modules\Pim\Model\Entity\Product
     */
    protected $product;


    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set amount
     *
     * @param decimal $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * Get amount
     *
     * @return decimal $amount
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set currency
     *
     * @param \Cx\Modules\Crm\Model\Entity\Currency $currency
     */
    public function setCurrency(\Cx\Modules\Crm\Model\Entity\Currency $currency)
    {
        $this->currency = $currency;
    }

    /**
     * Get currency
     *
     * @return \Cx\Modules\Crm\Model\Entity\Currency $currency
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set product
     *
     * @param \Cx\Modules\Pim\Model\Entity\Product $product
     */
    public function setProduct(\Cx\Modules\Pim\Model\Entity\Product $product)
    {
        $this->product = $product;
    }

    /**
     * Get product
     *
     * @return \Cx\Modules\Pim\Model\Entity\Product $product
     */
    public function getProduct()
    {
        return $this->product;
    }
}
