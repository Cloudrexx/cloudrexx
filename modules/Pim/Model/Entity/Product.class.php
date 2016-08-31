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
 * Product
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_pim
 */

namespace Cx\Modules\Pim\Model\Entity;

class ProductException extends \Exception {};

/**
 * Product
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_pim
 */
class Product extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var string $name
     */
    protected $name;

    /**
     * @var string $entityClass
     */
    protected $entityClass = null;

    /**
     * @var array $entityAttributes
     */
    protected $entityAttributes = array();

    /**
     * @var boolean $renewable
     */
    protected $renewable = false;

    /**
     * @var boolean $expirable
     */
    protected $expirable = false;

    /**
     * @var boolean $upgradable
     */
    protected $upgradable = false;

    /**
     * @var string $expirationUnit
     */
    protected $expirationUnit = null;

    /**
     * @var integer $expirationQuantifier
     */
    protected $expirationQuantifier = null;

    /**
     * @var string $cancellationUnit
     */
    protected $cancellationUnit = null;

    /**
     * @var integer $cancellationQuantifier
     */
    protected $cancellationQuantifier = null;

    /**
     * @var string $noteEntity
     */
    protected $noteEntity = null;

    /**
     * @var string $noteRenewal
     */
    protected $noteRenewal = null;

    /**
     * @var string $noteUpgrade
     */
    protected $noteUpgrade = null;

    /**
     * @var string $noteExpiration
     */
    protected $noteExpiration = null;

    /**
     * @var string $notePrice
     */
    protected $notePrice = null;

    /**
     * @var Cx\Modules\Order\Model\Entity\Subscription
     */
    protected $subscriptions;

    /**
     * @var Cx\Modules\Pim\Model\Entity\Price
     */
    protected $prices;

    /**
     * @var Cx\Modules\Pim\Model\Entity\Product
     */
    protected $upgrades;

    protected $renewalOptions = array();
    protected $defaultRenewalOption = array();

    const UNIT_DAY = 'day';
    const UNIT_MONTH = 'month';
    const UNIT_WEEK = 'week';
    const UNIT_YEAR = 'year';

    public function __construct() {
        $this->initRenewalConfig();
        $this->subscriptions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->prices = new \Doctrine\Common\Collections\ArrayCollection();
        $this->upgrades = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string $name
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set entityClass
     *
     * @param string $entityClass
     */
    public function setEntityClass($entityClass) {
        $this->entityClass = $entityClass;
    }

    /**
     * Get entityClass
     *
     * @return string $entityClass
     */
    public function getEntityClass() {
        return $this->entityClass;
    }

    /**
     * Set entityAttributes
     *
     * @param array $entityAttributes
     */
    public function setEntityAttributes($entityAttributes) {
        $this->entityAttributes = $entityAttributes;
    }

    /**
     * Get entityAttributes
     *
     * @return array $entityAttributes
     */
    public function getEntityAttributes() {
        return $this->entityAttributes;
    }

    /**
     * Set renewable
     *
     * @param boolean $renewable
     */
    public function setRenewable($renewable) {
        $this->renewable = $renewable;
    }

    /**
     * Get renewable
     *
     * @return boolean $renewable
     */
    public function getRenewable() {
        return $this->renewable;
    }

    public function isRenewable() {
        return $this->getRenewable();
    }

    /**
     * Set expirable
     *
     * @param boolean $expirable
     */
    public function setExpirable($expirable) {
        $this->expirable = $expirable;
    }

    /**
     * Get expirable
     *
     * @return boolean $expirable
     */
    public function getExpirable() {
        return $this->expirable;
    }

    public function isExpirable() {
        return $this->getExpirable();
    }

    /**
     * Set upgradable
     *
     * @param boolean $upgradable
     */
    public function setUpgradable($upgradable) {
        $this->upgradable = $upgradable;
    }

    /**
     * Get upgradable
     *
     * @return boolean $upgradable
     */
    public function getUpgradable() {
        return $this->upgradable;
    }

    public function isUpgradable() {
        return $this->getUpgradable();
    }

    /**
     * Set expirationUnit
     *
     * @param string $expirationUnit
     */
    public function setExpirationUnit($expirationUnit) {
        $this->expirationUnit = $expirationUnit;
    }

    /**
     * Get expirationUnit
     *
     * @return string $expirationUnit
     */
    public function getExpirationUnit() {
        return $this->expirationUnit;
    }

    /**
     * Set expirationQuantifier
     *
     * @param integer $expirationQuantifier
     */
    public function setExpirationQuantifier($expirationQuantifier) {
        $this->expirationQuantifier = $expirationQuantifier;
    }

    /**
     * Get expirationQuantifier
     *
     * @return integer $expirationQuantifier
     */
    public function getExpirationQuantifier() {
        return $this->expirationQuantifier;
    }

    /**
     * Set cancellationUnit
     *
     * @param string $cancellationUnit
     */
    public function setCancellationUnit($cancellationUnit)
    {
        $this->cancellationUnit = $cancellationUnit;
    }

    /**
     * Get cancellationUnit
     *
     * @return string $cancellationUnit
     */
    public function getCancellationUnit()
    {
        return $this->cancellationUnit;
    }

    /**
     * Set cancellationQuantifier
     *
     * @param integer $cancellationQuantifier
     */
    public function setCancellationQuantifier($cancellationQuantifier)
    {
        $this->cancellationQuantifier = $cancellationQuantifier;
    }

    /**
     * Get cancellationQuantifier
     *
     * @return integer $cancellationQuantifier
     */
    public function getCancellationQuantifier()
    {
        return $this->cancellationQuantifier;
    }

    /**
     * Set noteEntity
     *
     * @param string $noteEntity
     */
    public function setNoteEntity($noteEntity) {
        $this->noteEntity = $noteEntity;
    }

    /**
     * Get noteEntity
     *
     * @return string $noteEntity
     */
    public function getNoteEntity() {
        return $this->noteEntity;
    }

    /**
     * Set noteRenewal
     *
     * @param string $noteRenewal
     */
    public function setNoteRenewal($noteRenewal) {
        $this->noteRenewal = $noteRenewal;
    }

    /**
     * Get noteRenewal
     *
     * @return string $noteRenewal
     */
    public function getNoteRenewal() {
        return $this->noteRenewal;
    }

    /**
     * Set noteUpgrade
     *
     * @param string $noteUpgrade
     */
    public function setNoteUpgrade($noteUpgrade) {
        $this->noteUpgrade = $noteUpgrade;
    }

    /**
     * Get noteUpgrade
     *
     * @return string $noteUpgrade
     */
    public function getNoteUpgrade() {
        return $this->noteUpgrade;
    }

    /**
     * Set noteExpiration
     *
     * @param string $noteExpiration
     */
    public function setNoteExpiration($noteExpiration) {
        $this->noteExpiration = $noteExpiration;
    }

    /**
     * Get noteExpiration
     *
     * @return string $noteExpiration
     */
    public function getNoteExpiration() {
        return $this->noteExpiration;
    }

    /**
     * Set notePrice
     *
     * @param string $notePrice
     */
    public function setNotePrice($notePrice) {
        $this->notePrice = $notePrice;
    }

    /**
     * Get notePrice
     *
     * @return string $notePrice
     */
    public function getNotePrice() {
        return $this->notePrice;
    }

    /**
     * Add subscriptions
     *
     * @param Cx\Modules\Order\Model\Entity\Subscription $subscriptions
     */
    public function addSubscriptions(\Cx\Modules\Order\Model\Entity\Subscription $subscriptions)
    {
        $this->subscriptions[] = $subscriptions;
    }

    /**
     * Get subscriptions
     *
     * @return Doctrine\Common\Collections\Collection $subscriptions
     */
    public function getSubscriptions() {
        return $this->subscriptions;
    }

    /**
     * Add prices
     *
     * @param \Cx\Modules\Pim\Model\Entity\Price $prices
     */
    public function addPrices(\Cx\Modules\Pim\Model\Entity\Price $prices)
    {
        $this->prices[] = $prices;
    }

    /**
     * Get prices
     *
     * @return \Doctrine\Common\Collections\Collection $prices
     */
    public function getPrices()
    {
        return $this->prices;
    }

    /**
     * Add upgrade product to the existing upgrades
     *
     * @param type $upgrade
     */
    public function addUpgrades(Product $upgrades)
    {
        $this->upgrades[] = $upgrades;
    }

    /**
     * Get the available upgrades
     *
     * @return array Return the available upgrades
     */
    public function getUpgrades()
    {
        return $this->upgrades;
    }

    public function getRenewalOptions() {
        return $this->renewalOptions;
    }

    public function setRenewalOptions($renewalOptions) {
        $this->renewalOptions = $renewalOptions;
    }

    public function getDefaultRenewalOption() {
        return $this->defaultRenewalOption;
    }

    public function setDefaultRenewalOption($defaultRenewalOption) {
        return $this->defaultRenewalOption = $defaultRenewalOption;
    }

    public function getNewEntityForSale($saleOptions) {
        return \Env::get('em')->getRepository($this->entityClass)->findOneForSale($this->entityAttributes, $saleOptions);
    }

    public function getEntityById($entityId) {
        $entityIdKey = \Env::get('em')->getClassMetadata($this->entityClass)->getSingleIdentifierFieldName();
        return \Env::get('em')->getRepository($this->entityClass)->findOneBy(array($entityIdKey => $entityId));
    }

    public function getExpirationDate($expirationUnit = '', $expirationQuantifier = 0) {
        if (!$this->expirable) {
            throw new ProductException('Product is not expirable.');
        }
        $defaultExpiration = new \DateTime("+$this->expirationQuantifier $this->expirationUnit");
        $currentExpiration = new \DateTime("+$expirationQuantifier  $expirationUnit");

        if ($defaultExpiration > $currentExpiration) {
            return $defaultExpiration;
        }

        return $currentExpiration;
    }

    public function getRenewalDate($unit, $quantifier) {
        if (!$this->renewable) {
            throw new ProductException('Product is not renewable.');
        }
        if (!$this->isValidRenewalDefinition($unit, $quantifier)) {
            throw new ProductException("Invalid renewal definition supplied: $quantifier $unit");
        }

        $renewalDate = new \DateTime();
        $renewalDate->modify("+$quantifier $unit");
        return $renewalDate;
    }

    protected function isValidRenewalDefinition($unit, $quantifier) {
        return    isset($this->renewalOptions[$unit])
               && in_array($quantifier, $this->renewalOptions[$unit]);
    }

    public function getRenewalDefinition($unit, $quantifier) {
        if (empty($this->renewalOptions)) {
            $this->initRenewalConfig();
        }

        if ($this->isValidRenewalDefinition($unit, $quantifier)) {
            return array($unit, $quantifier);
        }

        return $this->defaultRenewalOption;
    }

    protected function initRenewalConfig() {
        $this->renewalOptions = array(
            self::UNIT_MONTH  => array(1   ),
            self::UNIT_YEAR    => array(1, 2),
        );
        $this->defaultRenewalOption = array(self::UNIT_YEAR, 1);
    }

    /**
     * Get the payment amount based on the unit and quantifier
     *
     * @param string $unit
     * @param integer $quantifier
     * @param \Cx\Modules\Crm\Model\Entity\Currency $currency
     *
     * @return \Cx\Core\Core\Controller\Cx|float|int
     */
    public function getPaymentAmount($unit = self::UNIT_MONTH, $quantifier = 1, $currency = null) {
        $paymentAmount = 0;
        $prices = $this->getPrices();
        $amount = 0;
        foreach ($prices as $price) {
            if ($price->getCurrency() != $currency) {
                continue;
            }
            $amount = $price->getAmount();
        }
        switch ($unit) {
            case self::UNIT_DAY:
                $paymentAmount = $amount * ($quantifier / 30);
                break;
            case self::UNIT_WEEK:
                $paymentAmount = $amount * ($quantifier / 4);
                break;
            case self::UNIT_MONTH:
                $paymentAmount = $amount * $quantifier;
                break;
            case self::UNIT_YEAR:
                $paymentAmount = $amount * $quantifier * 12;
                if ($quantifier > 1) {
                    $paymentAmount *= 0.9;
                }
                break;
        }
        return $paymentAmount;
    }

    public function __toString()
    {
        return $this->getName();
    }
}
