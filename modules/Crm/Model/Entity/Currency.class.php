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


namespace Cx\Modules\Crm\Model\Entity;

/**
 * Cx\Modules\Crm\Model\Entity\Currency
 */
class Currency extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer $id
     */
    private $id;

    /**
     * @var string $name
     */
    private $name;

    /**
     * @var boolean $active
     */
    private $active;

    /**
     * @var integer $pos
     */
    private $pos;

    /**
     * @var string $hourly_rate
     */
    private $hourly_rate;

    /**
     * @var boolean $default_currency
     */
    private $default_currency;


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
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
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
     * @return boolean $active
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set pos
     *
     * @param integer $pos
     */
    public function setPos($pos)
    {
        $this->pos = $pos;
    }

    /**
     * Get pos
     *
     * @return integer $pos
     */
    public function getPos()
    {
        return $this->pos;
    }

    /**
     * Set hourly_rate
     *
     * @param string $hourlyRate
     */
    public function setHourlyRate($hourlyRate)
    {
        $this->hourly_rate = $hourlyRate;
    }

    /**
     * Get hourly_rate
     *
     * @return string $hourlyRate
     */
    public function getHourlyRate()
    {
        return $this->hourly_rate;
    }

    /**
     * Set default_currency
     *
     * @param boolean $defaultCurrency
     */
    public function setDefaultCurrency($defaultCurrency)
    {
        $this->default_currency = $defaultCurrency;
    }

    /**
     * Get default_currency
     *
     * @return boolean $defaultCurrency
     */
    public function getDefaultCurrency()
    {
        return $this->default_currency;
    }

    public function __toString()
    {
        return $this->getName();
    }
}
