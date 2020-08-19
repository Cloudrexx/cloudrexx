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
 * Class Zones
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
namespace Cx\Modules\Shop\Model\Entity;

/**
 * Can contains multiple countries. A Zone can be assigned to shippers
 * and payment methods.
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
class Zone extends \Cx\Model\Base\EntityBase implements \Gedmo\Translatable\Translatable {
    /**
     * @var string
     */
    protected $locale;
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var boolean
     */
    protected $active = true;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $relCountries;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $payments;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $shippers;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->relCountries = new \Doctrine\Common\Collections\ArrayCollection();
        $this->payments = new \Doctrine\Common\Collections\ArrayCollection();
        $this->shippers = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Add relCountry
     *
     * @param \Cx\Modules\Shop\Model\Entity\RelCountry $relCountry
     */
    public function addRelCountry(\Cx\Modules\Shop\Model\Entity\RelCountry $relCountry)
    {
        $this->relCountries[] = $relCountry;
    }

    /**
     * Remove relCountry
     *
     * @param \Cx\Modules\Shop\Model\Entity\RelCountry $relCountry
     */
    public function removeRelCountry(\Cx\Modules\Shop\Model\Entity\RelCountry $relCountry)
    {
        $this->relCountries->removeElement($relCountry);
    }

    /**
     * Get relCountries
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getRelCountries()
    {
        return $this->relCountries;
    }

    /**
     * Add payment
     *
     * @param \Cx\Modules\Shop\Model\Entity\Payment $payment
     */
    public function addPayment(\Cx\Modules\Shop\Model\Entity\Payment $payment)
    {
        $this->payments[] = $payment;
    }

    /**
     * Remove payment
     *
     * @param \Cx\Modules\Shop\Model\Entity\Payment $payment
     */
    public function removePayment(\Cx\Modules\Shop\Model\Entity\Payment $payment)
    {
        $this->payments->removeElement($payment);
    }

    /**
     * Get payments
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPayments()
    {
        return $this->payments;
    }

    /**
     * Add shipper
     *
     * @param \Cx\Modules\Shop\Model\Entity\Shipper $shipper
     */
    public function addShipper(\Cx\Modules\Shop\Model\Entity\Shipper $shipper)
    {
        $this->shippers[] = $shipper;
    }

    /**
     * Remove shipper
     *
     * @param \Cx\Modules\Shop\Model\Entity\Shipper $shipper
     */
    public function removeShipper(\Cx\Modules\Shop\Model\Entity\Shipper $shipper)
    {
        $this->shippers->removeElement($shipper);
    }

    /**
     * Get shippers
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getShippers()
    {
        return $this->shippers;
    }
}
