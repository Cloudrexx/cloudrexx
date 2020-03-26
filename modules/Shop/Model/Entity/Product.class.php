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
 * Class Products
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
namespace Cx\Modules\Shop\Model\Entity;

/**
 * Product which available in the Shop.
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
class Product extends \Cx\Model\Base\EntityBase implements \Gedmo\Translatable\Translatable {
    /**
     * @var string
     */
    protected $locale;
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string
     */
    protected $picture;

    /**
     * @var string
     */
    protected $distribution;

    /**
     * @var string
     */
    protected $normalprice = '0.00';

    /**
     * @var string
     */
    protected $resellerprice = '0.00';

    /**
     * @var integer
     */
    protected $stock = 10;

    /**
     * @var boolean
     */
    protected $stockVisible = true;

    /**
     * @var string
     */
    protected $discountprice = '0.00';

    /**
     * @var boolean
     */
    protected $discountActive;

    /**
     * @var boolean
     */
    protected $active = true;

    /**
     * @var boolean
     */
    protected $b2b = true;

    /**
     * @var boolean
     */
    protected $b2c = true;

    /**
     * @var \DateTime
     */
    protected $dateStart;

    /**
     * @var \DateTime
     */
    protected $dateEnd;

    /**
     * @var integer
     */
    protected $manufacturerId;

    /**
     * @var integer
     */
    protected $ord;

    /**
     * @var integer
     */
    protected $vatId;

    /**
     * @var integer
     */
    protected $weight;

    /**
     * @var string
     */
    protected $flags;

    /**
     * @var integer
     */
    protected $groupId;

    /**
     * @var integer
     */
    protected $articleId;

    /**
     * @var integer
     */
    protected $minimumOrderQuantity;

    /**
     * @var string
     */
    protected $uri;

    /**
     * @var string
     */
    protected $short;

    /**
     * @var string
     */
    protected $long;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $keys;

    /**
     * @var string
     */
    protected $code;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $discountCoupons;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $orderItems;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $relProductAttributes;

    /**
     * @var \Cx\Modules\Shop\Model\Entity\Manufacturer
     */
    protected $manufacturer;

    /**
     * @var \Cx\Modules\Shop\Model\Entity\DiscountgroupCountName
     */
    protected $discountgroupCountName;

    /**
     * @var \Cx\Modules\Shop\Model\Entity\ArticleGroup
     */
    protected $articleGroup;

    /**
     * @var \Cx\Modules\Shop\Model\Entity\Vat
     */
    protected $vat;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $categories;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $userGroups;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->discountCoupons = new \Doctrine\Common\Collections\ArrayCollection();
        $this->orderItems = new \Doctrine\Common\Collections\ArrayCollection();
        $this->relProductAttributes = new \Doctrine\Common\Collections\ArrayCollection();
        $this->categories = new \Doctrine\Common\Collections\ArrayCollection();
        $this->userGroups = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set translatable locale
     *
     * @param $locale
     */
    public function setTranslatableLocale($locale)
    {
        if (!is_string($locale) || !strlen($locale)) {
            $this->locale = $locale;
        }
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set picture
     *
     * @param string $picture
     */
    public function setPicture($picture)
    {
        $this->picture = $picture;
    }

    /**
     * Get picture
     *
     * @return string 
     */
    public function getPicture()
    {
        return $this->picture;
    }

    /**
     * Set distribution
     *
     * @param string $distribution
     */
    public function setDistribution($distribution)
    {
        $this->distribution = $distribution;
    }

    /**
     * Get distribution
     *
     * @return string 
     */
    public function getDistribution()
    {
        return $this->distribution;
    }

    /**
     * Set normalprice
     *
     * @param string $normalprice
     */
    public function setNormalprice($normalprice)
    {
        $this->normalprice = $normalprice;
    }

    /**
     * Get normalprice
     *
     * @return string 
     */
    public function getNormalprice()
    {
        return $this->normalprice;
    }

    /**
     * Set resellerprice
     *
     * @param string $resellerprice
     */
    public function setResellerprice($resellerprice)
    {
        $this->resellerprice = $resellerprice;
    }

    /**
     * Get resellerprice
     *
     * @return string 
     */
    public function getResellerprice()
    {
        return $this->resellerprice;
    }

    /**
     * Set stock
     *
     * @param integer $stock
     */
    public function setStock($stock)
    {
        $this->stock = $stock;
    }

    /**
     * Get stock
     *
     * @return integer 
     */
    public function getStock()
    {
        return $this->stock;
    }

    /**
     * Set stockVisible
     *
     * @param boolean $stockVisible
     */
    public function setStockVisible($stockVisible)
    {
        $this->stockVisible = $stockVisible;
    }

    /**
     * Get stockVisible
     *
     * @return boolean 
     */
    public function getStockVisible()
    {
        return $this->stockVisible;
    }

    /**
     * Set discountprice
     *
     * @param string $discountprice
     */
    public function setDiscountprice($discountprice)
    {
        $this->discountprice = $discountprice;
    }

    /**
     * Get discountprice
     *
     * @return string 
     */
    public function getDiscountprice()
    {
        return $this->discountprice;
    }

    /**
     * Set discountActive
     *
     * @param boolean $discountActive
     */
    public function setDiscountActive($discountActive)
    {
        $this->discountActive = $discountActive;
    }

    /**
     * Get discountActive
     *
     * @return boolean 
     */
    public function getDiscountActive()
    {
        return $this->discountActive;
    }

    /**
     * Set active
     *
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * Get active
     *
     * @return boolean 
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set b2b
     *
     * @param boolean $b2b
     */
    public function setB2b($b2b)
    {
        $this->b2b = $b2b;
    }

    /**
     * Get b2b
     *
     * @return boolean 
     */
    public function getB2b()
    {
        return $this->b2b;
    }

    /**
     * Set b2c
     *
     * @param boolean $b2c
     */
    public function setB2c($b2c)
    {
        $this->b2c = $b2c;
    }

    /**
     * Get b2c
     *
     * @return boolean 
     */
    public function getB2c()
    {
        return $this->b2c;
    }

    /**
     * Set dateStart
     *
     * @param \DateTime $dateStart
     */
    public function setDateStart($dateStart)
    {
        $this->dateStart = $dateStart;
    }

    /**
     * Get dateStart
     *
     * @return \DateTime 
     */
    public function getDateStart()
    {
        return $this->dateStart;
    }

    /**
     * Set dateEnd
     *
     * @param \DateTime $dateEnd
     */
    public function setDateEnd($dateEnd)
    {
        $this->dateEnd = $dateEnd;
    }

    /**
     * Get dateEnd
     *
     * @return \DateTime 
     */
    public function getDateEnd()
    {
        return $this->dateEnd;
    }

    /**
     * Set manufacturerId
     *
     * @param integer $manufacturerId
     */
    public function setManufacturerId($manufacturerId)
    {
        $this->manufacturerId = $manufacturerId;
    }

    /**
     * Get manufacturerId
     *
     * @return integer 
     */
    public function getManufacturerId()
    {
        return $this->manufacturerId;
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
     * Set vatId
     *
     * @param integer $vatId
     */
    public function setVatId($vatId)
    {
        $this->vatId = $vatId;
    }

    /**
     * Get vatId
     *
     * @return integer 
     */
    public function getVatId()
    {
        return $this->vatId;
    }

    /**
     * Set weight
     *
     * @param integer $weight
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
    }

    /**
     * Get weight
     *
     * @return integer 
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set flags
     *
     * @param string $flags
     */
    public function setFlags($flags)
    {
        $this->flags = $flags;
    }

    /**
     * Get flags
     *
     * @return string 
     */
    public function getFlags()
    {
        return $this->flags;
    }

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
     * Set articleId
     *
     * @param integer $articleId
     */
    public function setArticleId($articleId)
    {
        $this->articleId = $articleId;
    }

    /**
     * Get articleId
     *
     * @return integer 
     */
    public function getArticleId()
    {
        return $this->articleId;
    }

    /**
     * Set minimumOrderQuantity
     *
     * @param integer $minimumOrderQuantity
     */
    public function setMinimumOrderQuantity($minimumOrderQuantity)
    {
        $this->minimumOrderQuantity = $minimumOrderQuantity;
    }

    /**
     * Get minimumOrderQuantity
     *
     * @return integer 
     */
    public function getMinimumOrderQuantity()
    {
        return $this->minimumOrderQuantity;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set uri
     *
     * @param string $uri
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    /**
     * Get uri
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Set short
     *
     * @param string $short
     */
    public function setShort($short)
    {
        $this->short = $short;
    }

    /**
     * Get short
     *
     * @return string
     */
    public function getShort()
    {
        return $this->short;
    }

    /**
     * Set long
     *
     * @param string $long
     */
    public function setLong($long)
    {
        $this->long = $long;
    }

    /**
     * Get long
     *
     * @return string
     */
    public function getLong()
    {
        return $this->long;
    }

    /**
     * Set keys
     *
     * @param string $keys
     */
    public function setKeys($keys)
    {
        $this->keys = $keys;
    }

    /**
     * Get keys
     *
     * @return string
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * Set code
     *
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Add discountCoupon
     *
     * @param \Cx\Modules\Shop\Model\Entity\DiscountCoupon $discountCoupon
     */
    public function addDiscountCoupon(\Cx\Modules\Shop\Model\Entity\DiscountCoupon $discountCoupon)
    {
        $this->discountCoupons[] = $discountCoupon;
    }

    /**
     * Remove discountCoupon
     *
     * @param \Cx\Modules\Shop\Model\Entity\DiscountCoupon $discountCoupon
     */
    public function removeDiscountCoupon(\Cx\Modules\Shop\Model\Entity\DiscountCoupon $discountCoupon)
    {
        $this->discountCoupons->removeElement($discountCoupon);
    }

    /**
     * Get discountCoupons
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getDiscountCoupons()
    {
        return $this->discountCoupons;
    }

    /**
     * Add orderItem
     *
     * @param \Cx\Modules\Shop\Model\Entity\OrderItem $orderItem
     */
    public function addOrderItem(\Cx\Modules\Shop\Model\Entity\OrderItem $orderItem)
    {
        $this->orderItems[] = $orderItem;
    }

    /**
     * Remove orderItem
     *
     * @param \Cx\Modules\Shop\Model\Entity\OrderItem $orderItem
     */
    public function removeOrderItem(\Cx\Modules\Shop\Model\Entity\OrderItem $orderItem)
    {
        $this->orderItems->removeElement($orderItem);
    }

    /**
     * Get orderItems
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getOrderItems()
    {
        return $this->orderItems;
    }

    /**
     * Add relProductAttribute
     *
     * @param \Cx\Modules\Shop\Model\Entity\RelProductAttribute $relProductAttribute
     */
    public function addRelProductAttribute(\Cx\Modules\Shop\Model\Entity\RelProductAttribute $relProductAttribute)
    {
        $this->relProductAttributes[] = $relProductAttribute;
    }

    /**
     * Remove relProductAttribute
     *
     * @param \Cx\Modules\Shop\Model\Entity\RelProductAttribute $relProductAttribute
     */
    public function removeRelProductAttribute(\Cx\Modules\Shop\Model\Entity\RelProductAttribute $relProductAttribute)
    {
        $this->relProductAttributes->removeElement($relProductAttribute);
    }

    /**
     * Get relProductAttributes
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getRelProductAttributes()
    {
        return $this->relProductAttributes;
    }

    /**
     * Set manufacturer
     *
     * @param \Cx\Modules\Shop\Model\Entity\Manufacturer $manufacturer
     */
    public function setManufacturer(\Cx\Modules\Shop\Model\Entity\Manufacturer $manufacturer = null)
    {
        $this->manufacturer = $manufacturer;
    }

    /**
     * Get manufacturer
     *
     * @return \Cx\Modules\Shop\Model\Entity\Manufacturer 
     */
    public function getManufacturer()
    {
        return $this->manufacturer;
    }

    /**
     * Set discountgroup count name
     *
     * @param \Cx\Modules\Shop\Model\Entity\DiscountgroupCountName $discountgroupCountName
     */
    public function setDiscountgroupCountName(\Cx\Modules\Shop\Model\Entity\DiscountgroupCountName $discountgroupCountName = null)
    {
        $this->discountgroupCountName = $discountgroupCountName;
    }

    /**
     * Get discountgroup count name
     *
     * @return \Cx\Modules\Shop\Model\Entity\DiscountgroupCountName
     */
    public function getDiscountgroupCountName()
    {
        return $this->discountgroupCountName;
    }

    /**
     * Set article group
     *
     * @param \Cx\Modules\Shop\Model\Entity\ArticleGroup $articleGroup
     */
    public function setArticleGroup(\Cx\Modules\Shop\Model\Entity\ArticleGroup $articleGroup = null)
    {
        $this->articleGroup = $articleGroup;
    }

    /**
     * Get article group
     *
     * @return \Cx\Modules\Shop\Model\Entity\ArticleGroup
     */
    public function getArticleGroup()
    {
        return $this->articleGroup;
    }

    /**
     * Set vat
     *
     * @param \Cx\Modules\Shop\Model\Entity\Vat $vat
     */
    public function setVat(\Cx\Modules\Shop\Model\Entity\Vat $vat = null)
    {
        $this->vat = $vat;
    }

    /**
     * Get vat
     *
     * @return \Cx\Modules\Shop\Model\Entity\Vat 
     */
    public function getVat()
    {
        return $this->vat;
    }

    /**
     * Add category
     *
     * @param \Cx\Modules\Shop\Model\Entity\Category $category
     */
    public function addCategory(\Cx\Modules\Shop\Model\Entity\Category $category)
    {
        $this->categories[] = $category;
    }

    /**
     * Remove category
     *
     * @param \Cx\Modules\Shop\Model\Entity\Category $category
     */
    public function removeCategory(\Cx\Modules\Shop\Model\Entity\Category $category)
    {
        $this->categories->removeElement($category);
    }

    /**
     * Get categories
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * Add user group
     *
     * @param \Cx\Core\User\Model\Entity\Group $userGroup
     */
    public function addUserGroup(\Cx\Core\User\Model\Entity\Group $userGroup)
    {
        $this->userGroups[] = $userGroup;
    }

    /**
     * Remove user group
     *
     * @param \Cx\Core\User\Model\Entity\Group $userGroup
     */
    public function removeUserGroup(\Cx\Core\User\Model\Entity\Group $userGroup)
    {
        $this->userGroups->removeElement($userGroup);
    }

    /**
     * Get categories
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserGroups()
    {
        return $this->userGroups;
    }

    public function __toString()
    {
        return $this->getName();
    }

    public function getJsArray($groupCustomerId = 0, $isReseller = false)
    {
        $strJsArrProduct =
            '{';

        $products = $this->cx->getDb()->getEntityManager()->getRepository(
            get_class($this)
        )->findAll();

        $counter = count($products) - 1;
        foreach ($products as $product) {
            $id = $product->getId();
            $distribution = $product->getDistribution();
            $code = $product->getCode();
            $name = $product->getName();
            $price = $product->getNormalprice();
            $articleId = $product->getArticleId();

            if ($product->getDiscountActive()) {
                $price = $product->getDiscountprice();
            } else if ($isReseller) {
                $price = $product->getResellerprice();
            }

            // Determine discounted price from customer and article group matrix

            $discountGroupRepo = $this->cx->getDb()->getEntityManager()->getRepository('\Cx\Modules\Shop\Model\Entity\RelDiscountGroup');
            $discountCustomerGroup = $discountGroupRepo->findBy(
                array(
                    'customerGroupId' => $groupCustomerId,
                    'articleGroup' => $articleId
                )
            );
            $discountCustomerRate = 0;
            if (!empty($discountCustomerGroup)) {
                $discountCustomerRate = $discountCustomerGroup->getRate();
            }

            $price -= $price * $discountCustomerRate * 0.01;

            // Determine prices for various count discounts, if any
            $discountGroupRepo = $this->cx->getDb()->getEntityManager()->getRepository(
                '\Cx\Modules\Shop\Model\Entity\DiscountgroupCountRate'
            );
            $discountGroupCountRates = $discountGroupRepo->findBy(
                array(
                    'groupId' => $product->getGroupId()
                )
            );

            // Order the counts in reverse, from highest to lowest
            $strJsArrPrice = '';
            foreach ($discountGroupCountRates as $count => $discountGroupCountRate) {
                $rate = $discountGroupCountRate->getRate();
                // Deduct the customer type discount right away
                $discountPrice = $price - ($price * $rate * 0.01);
                $strJsArrPrice .=
                    ($strJsArrPrice ? ',' : '')
                    // Count followed by price
                    .$count.','
                    .\Cx\Modules\Shop\Controller\CurrencyController::getCurrencyPrice(
                        $discountPrice
                    );
            }

            $strJsArrPrice .=
                ($strJsArrPrice ? ',' : '').
                '0,'.\Cx\Modules\Shop\Controller\CurrencyController::getCurrencyPrice($price);

            $strJsArrProduct .=
                '"'.$id.'": {'.
                '"id": '.$id.','.
                '"code":"'.$code.'",'.
                '"title":"'.htmlspecialchars($name, ENT_QUOTES, CONTREXX_CHARSET).'",'.
                '"percent":'.
                // Use the VAT rate, not the ID, as it is not modified here
                (!empty($product->getVat()) ? $product->getVat()->getRate() : '0') .','.
                '"weight":'.($distribution == 'delivery'
                    ? '"'.\Cx\Modules\Shop\Controller\Weight::getWeightString($product->getWeight()).'"'
                    : '0' ).','.
                '"price":['.$strJsArrPrice.']}';
            if (!empty($counter--)) {
                $strJsArrProduct .= ',';
            }
        }
        $strJsArrProduct .= '}';

        return $strJsArrProduct;
    }
}
