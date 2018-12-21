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
 * Class Currencies
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
namespace Cx\Modules\Shop\Model\Entity;

/**
 * A Currency is used to calculate the price. It consists of a code, symbol
 * (e.x. €) and a rate
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
class Currency extends \Cx\Model\Base\EntityBase implements \Gedmo\Translatable\Translatable {
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
    protected $code;

    /**
     * @var string
     */
    protected $symbol;

    /**
     * @var string
     */
    protected $rate;

    /**
     * @var integer
     */
    protected $ord;

    /**
     * @var boolean
     */
    protected $active;

    /**
     * @var boolean
     */
    protected $default;

    /**
     * @var string
     */
    protected $increment;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $orders;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->orders = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set symbol
     *
     * @param string $symbol
     */
    public function setSymbol($symbol)
    {
        $this->symbol = $symbol;
    }

    /**
     * Get symbol
     *
     * @return string 
     */
    public function getSymbol()
    {
        return $this->symbol;
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
     * Set default
     *
     * @param boolean $default
     */
    public function setDefault($default)
    {
        $this->default = $default;
    }

    /**
     * Get default
     *
     * @return boolean 
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * Set increment
     *
     * @param string $increment
     */
    public function setIncrement($increment)
    {
        $this->increment = $increment;
    }

    /**
     * Get increment
     *
     * @return string 
     */
    public function getIncrement()
    {
        return $this->increment;
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
     * Add orders
     *
     * @param \Cx\Modules\Shop\Model\Entity\Order $order
     */
    public function addOrder(\Cx\Modules\Shop\Model\Entity\Order $order)
    {
        $this->orders[] = $order;
    }

    /**
     * Remove orders
     *
     * @param \Cx\Modules\Shop\Model\Entity\Order $order
     */
    public function removeOrder(\Cx\Modules\Shop\Model\Entity\Order $order)
    {
        $this->orders->removeElement($order);
    }

    /**
     * Get orders
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getOrders()
    {
        return $this->orders;
    }

    /**
     * Returns the amount converted from the default to the active currency
     *
     * Note that the amount is rounded to five cents before formatting.
     *
     * @param   double  $price  The amount in default currency
     * @throws \Doctrine\ORM\ORMException
     * @return  string          Formatted amount in the active currency
     * @todo    In case that the {@link formatPrice()} function is localized,
     *          the returned value *MUST NOT* be treated as a number anymore!
     * @ToDo:   Get active currency id
     */
    static function getCurrencyPrice($price)
    {
        $activeCurrencyId = (int)$_SESSION['shop']['currencyId'];
        $currency = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager()->getRepository(
            '\Cx\Modules\Shop\Model\Entity\Currencies'
        )->findOneBy(array('id' => $activeCurrencyId));
        $rate = $currency->getRate();
        $increment = $currency->getIncrement();
        if ($increment <= 0) $increment = 0.01;
        return static::formatPrice(round($price*$rate/$increment)*$increment);
    }

    /**
     * Returns the formatted amount in a non-localized notation
     *
     * The optional $length is inserted into the sprintf()
     * format string and determines the maximum length of the number.
     * If present, the optional $padding character is inserted into the
     * sprintf() format string.
     * The optional $increment parameter overrides the increment value
     * of the *active* Currency, which is used by default.
     * The $increment value limits the number of digits printed after the
     * decimal point.
     * Currently, the number is formatted as a float, using no thousands,
     * and '.' as decimal separator.
     * @todo    Localize!  Create language and country dependant
     *          settings in the database, and make this behave accordingly.
     * @param   double  $price      The amount
     * @param   string  $length     The optional number length
     * @param   string  $padding    The optional padding character
     * @param   float   $increment  The optional increment
     * @return  double            The formatted amount
     */
    static function formatPrice(
        $price, $length='', $padding='', $increment=null
    ) {
        $decimals = 2;
        // New
        if (empty($increment)) {
            $activeCurrencyId = 1;
            $currency = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager()->getRepository(
                '\Cx\Modules\Shop\Model\Entity\Currencies'
            )->findOneBy(array('id' => $activeCurrencyId));
            $increment = $currency->getIncrement();
        }

        $increment = floatval($increment);
        if ($increment > 0) {
            $decimals = max(0, -floor(log10($increment)));
            $price = round($price/$increment)*$increment;
        }
        $price = sprintf('%'.$padding.$length.'.'.$decimals.'f', $price);

        return $price;
    }
}
