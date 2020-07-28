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

/**
 * Class VatRate
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_pim
 */

namespace Cx\Modules\Pim\Model\Entity;

/**
 * Class VatRate
 * 
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_pim
 */
class VatRate extends \Cx\Model\Base\EntityBase {
    
    /**
     *
     * @var integer $id
     */
    protected $id;
    
    /**
     *
     * @var decimal $rate
     */
    protected $rate;
    
    /**
     *
     * @var string $vatClass
     */
    protected $vatClass;
    
    /**
     *
     * @var Cx\Modules\Pim\Model\Entity\Product $products
     */
    protected $products;
    
    public function __construct() {
        $this->products = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Get the id
     * 
     * @return integer $id
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set the id
     * 
     * @param integer $id
     */
    public function setId($id) {
        $this->id = $id;
    }
    
    /**
     * set the rate
     * 
     * @param decimal $rate
     */
    public function setRate($rate) {
        $this->rate = $rate;
    }
    
    /**
     * get the rate
     * 
     * @return decimal $rate
     */
    public function getRate() {
        return $this->rate;
    }
    
    /**
     * set the vat class
     * 
     * @param string $vatClass
     */
    public function setVatClass($vatClass) {
        $this->vatClass = $vatClass;
    }
    
    /**
     * get the vat class
     * 
     * @return string $vatClass
     */
    public function getVatClass() {
        return $this->vatClass;
    }
    
    /**
     * Get the products
     * 
     * @return \Doctrine\Common\Collections\ArrayCollection $products
     */
    public function getProducts()
    {
        return $this->products;
    }
    
    /**
     * Add the product
     * 
     * @param \Cx\Modules\Pim\Model\Entity\Product $product
     */
    public function addProduct(\Cx\Modules\Pim\Model\Entity\Product $product) {
        $this->products[] = $product;
        $product->setVatRate($this);
    }
    
}
