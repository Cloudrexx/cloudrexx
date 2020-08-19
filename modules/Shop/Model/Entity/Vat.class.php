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
 * Class Vat
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
namespace Cx\Modules\Shop\Model\Entity;

/**
 * The VAT is assigned to products. It contains a class name and rate.
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
class Vat extends \Cx\Model\Base\EntityBase implements \Gedmo\Translatable\Translatable {
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
    protected $rate = '0.00';

    /**
     * @var string
     */
    protected $class;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $products;


    /**
     * The current order goes to the shop country if true.
     * Defaults to true.
     * @var     boolean
     */
    protected static $isHomeCountry = true;

    /**
     * The current user is a reseller if true
     * Defaults to false.
     * @var     boolean
     */
    protected static $isReseller = false;

    /**
     * @var array $arrVatEnabled Indicates whether VAT is enabled for
     *                           customers or resellers, home or foreign
     *                           countries
     * Indexed as follows:
     * $arrVatEnabled[is_home_country ? 1 : 0][is_reseller ? 1 : 0] = is_enabled
     */
    protected static $arrVatEnabled = false;

    /**
     * @var boolean $arrVatIncluded  Indicates whether VAT is included for
     *                               customers or resellers, home or foreign
     *                               countries.
     * Indexed as follows:
     * $arrVatIncluded[is_home_country ? 1 : 0][is_reseller ? 1 : 0] = is_included
     */
    protected static $arrVatIncluded = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->products = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Initialize the Vat object with current values from the database.
     *
     * Set up two class array variables, one called $arrVatClass, like
     *  (ID => "class", ...)
     * and the other called $arrVatRate, like
     *  (ID => rate)
     * Plus initializes the various object variables.
     * May die() with a message if it fails to access its settings.
     * @global  ADONewConnection  $objDatabase    Database connection object
     * @return  void
     * @static
     */
    static function init()
    {
        \Cx\Core\Setting\Controller\Setting::init('Shop');
        static::$arrVatEnabled = array(
            // Foreign countries
            0 => array(
                // Customer
                0 => \Cx\Core\Setting\Controller\Setting::getValue(
                    'vat_enabled_foreign_customer'
                ),
                // Reseller
                1 => \Cx\Core\Setting\Controller\Setting::getValue(
                    'vat_enabled_foreign_reseller'
                ),
            ),
            // Home country
            1 => array(
                // Customer
                0 => \Cx\Core\Setting\Controller\Setting::getValue(
                    'vat_enabled_home_customer'
                ),
                // Reseller
                1 => \Cx\Core\Setting\Controller\Setting::getValue(
                    'vat_enabled_home_reseller'
                ),
            ),
        );
        static::$arrVatIncluded = array(
            // Foreign country
            0 => array(
                // Customer
                0 => \Cx\Core\Setting\Controller\Setting::getValue(
                    'vat_included_foreign_customer'
                ),
                // Reseller
                1 => \Cx\Core\Setting\Controller\Setting::getValue(
                    'vat_included_foreign_reseller'
                ),
            ),
            // Home country
            1 => array(
                // Customer
                0 => \Cx\Core\Setting\Controller\Setting::getValue(
                    'vat_included_home_customer'
                ),
                // Reseller
                1 => \Cx\Core\Setting\Controller\Setting::getValue(
                    'vat_included_home_reseller'
                ),
            ),
        );
        /*
        static::$vatDefaultId = \Cx\Core\Setting\Controller\Setting::getValue(
            'vat_default_id', 'Shop'
        );
        static::$vatDefaultRate = self::getRate(self::$vatDefaultId);
        static::$vatOtherId = \Cx\Core\Setting\Controller\Setting::getValue(
            'vat_other_id', 'Shop'
        );
        */
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
     * Set class
     *
     * @param string $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }

    /**
     * Get class
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Add product
     *
     * @param \Cx\Modules\Shop\Model\Entity\Product $product
     */
    public function addProduct(\Cx\Modules\Shop\Model\Entity\Product $product)
    {
        $this->products[] = $product;
    }

    /**
     * Remove product
     *
     * @param \Cx\Modules\Shop\Model\Entity\Product $product
     */
    public function removeProduct(\Cx\Modules\Shop\Model\Entity\Product $product)
    {
        $this->products->removeElement($product);
    }

    /**
     * Get products
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * Set the home country flag
     * @param   boolean     The optional home country flag
     */
    static function isHomeCountry($isHomeCountry)
    {
        static::$isHomeCountry = $isHomeCountry;
    }

    /**
     * Get the home country flag
     * @return boolean True if the shop home country and the ship-to country
     *                 are identical
     */
    static function getIsHomeCountry()
    {
        return static::$isHomeCountry;
    }

    /**
     * Set the reseller flag
     * @param   boolean     True if the current customer has the
     *                      reseller flag set
     */
    static function isReseller($isReseller)
    {
        static::$isReseller = $isReseller;
    }

    /**
     * Returns true if VAT is included, false otherwise
     * @return boolean True if VAT is included, false otherwise.
     */
    static function isIncluded()
    {
        if (!is_array(static::$arrVatIncluded)) static::init();
        return static::$arrVatIncluded[
            static::$isHomeCountry ? 1 : 0
        ][
            static::$isReseller ? 1 : 0
        ];
    }

    /**
     * Get vat rate
     *
     * @return string vat rate
     */
    public function __toString()
    {
        return $this->getRate();
    }
}
