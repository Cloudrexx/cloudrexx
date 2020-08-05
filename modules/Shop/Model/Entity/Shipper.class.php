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
 * Class Shipper
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
namespace Cx\Modules\Shop\Model\Entity;

/**
 * Shipper  has multiple shipment costs.A shipper can also only be available in
 * a certain zone. When placing an order, the shipping costs corresponding to
 * the weight of the order are selected.
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
class Shipper extends \Cx\Model\Base\EntityBase implements \Gedmo\Translatable\Translatable {
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
     * @var integer
     */
    protected $ord;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $orders;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $shipmentCosts;

    /**
     * @var \Cx\Modules\Shop\Model\Entity\Zones
     */
    protected $zone;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->orders = new \Doctrine\Common\Collections\ArrayCollection();
        $this->shipmentCosts = new \Doctrine\Common\Collections\ArrayCollection();
        $this->zones = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Add order
     *
     * @param \Cx\Modules\Shop\Model\Entity\Order $order
     */
    public function addOrder(\Cx\Modules\Shop\Model\Entity\Order $order)
    {
        $this->orders[] = $order;
    }

    /**
     * Remove order
     *
     * @param \Cx\Modules\Shop\Model\Entity\Orders $order
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
     * Add shipmentCost
     *
     * @param \Cx\Modules\Shop\Model\Entity\ShipmentCost $shipmentCost
     */
    public function addShipmentCost(\Cx\Modules\Shop\Model\Entity\ShipmentCost $shipmentCost)
    {
        $this->shipmentCosts[] = $shipmentCost;
    }

    /**
     * Remove shipmentCost
     *
     * @param \Cx\Modules\Shop\Model\Entity\ShipmentCost $shipmentCost
     */
    public function removeShipmentCost(\Cx\Modules\Shop\Model\Entity\ShipmentCost $shipmentCost)
    {
        $this->shipmentCosts->removeElement($shipmentCost);
    }

    /**
     * Get shipmentCosts
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getShipmentCosts()
    {
        return $this->shipmentCosts;
    }

    /**
     * Set zone
     *
     * @param \Cx\Modules\Shop\Model\Entity\Zone $zone
     */
    public function setZone(\Cx\Modules\Shop\Model\Entity\Zone $zone)
    {
        $this->zone = $zone;
    }

    /**
     * Get zone
     *
     * @return \Cx\Modules\Shop\Model\Entity\Zones
     */
    public function getZone()
    {
        return $this->zone;
    }

    /**
     * Get Name
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }

    /**
     * Returns the shipment arrays (shippers and shipment costs) in JavaScript
     * syntax.
     * Backend use only.
     *
     * @throws \Doctrine\ORM\ORMException
     * @return string The Shipment arrays definition
     */
    public function getJsArray()
    {
        $shippers = $this->cx->getDb()->getEntityManager()->getRepository(
            get_class($this)
        )->findAll();

        $strJsArrays = '{';
        $counter = count($shippers)-1;
        foreach ($shippers as $shipper) {
            $strJsArrays .= '"'. $shipper->getId()
                . '":[';

            $shipmentCost = $shipper->getShipmentCosts();
            for ($i = count($shipmentCost); $i > 0; $i--) {
                $index = $i - 1;
                $strJsArrays .=
                    '["'
                    .$shipmentCost[$index]->getId().'", "'.
                    \Cx\Modules\Shop\Controller\Weight::getWeightString(
                        $shipmentCost[$index]->getMaxWeight()
                    ).'","' .
                    \Cx\Modules\Shop\Controller\CurrencyController::getCurrencyPrice(
                        $shipmentCost[$index]->getFreeFrom()
                    ). '","' .
                    \Cx\Modules\Shop\Controller\CurrencyController::getCurrencyPrice(
                        $shipmentCost[$index]->getFee()
                    ) . '"]';
                if (!empty($index)) {
                    $strJsArrays .= ',';
                }
            }

            $strJsArrays .= ']';

            if (!empty($counter--)) {
                $strJsArrays .= ',';
            }
        }
        $strJsArrays .= '}';
        return $strJsArrays;
    }

}
