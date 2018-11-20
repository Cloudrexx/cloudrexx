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
 * Class RelCountries
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
namespace Cx\Modules\Shop\Model\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Country available in the shop. Can be limited per zone.
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
class RelCountries extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer
     */
    protected $zoneId;

    /**
     * @var integer
     */
    protected $countryId;

    /**
     * @var \Cx\Modules\Shop\Model\Entity\Zones
     */
    protected $zones;


    /**
     * Set zoneId
     *
     * @param integer $zoneId
     * @return RelCountries
     */
    public function setZoneId($zoneId)
    {
        $this->zoneId = $zoneId;

        return $this;
    }

    /**
     * Get zoneId
     *
     * @return integer 
     */
    public function getZoneId()
    {
        return $this->zoneId;
    }

    /**
     * Set countryId
     *
     * @param integer $countryId
     * @return RelCountries
     */
    public function setCountryId($countryId)
    {
        $this->countryId = $countryId;

        return $this;
    }

    /**
     * Get countryId
     *
     * @return integer 
     */
    public function getCountryId()
    {
        return $this->countryId;
    }

    /**
     * Set zones
     *
     * @param \Cx\Modules\Shop\Model\Entity\Zones $zones
     * @return RelCountries
     */
    public function setZones(\Cx\Modules\Shop\Model\Entity\Zones $zones = null)
    {
        $this->zones = $zones;

        return $this;
    }

    /**
     * Get zones
     *
     * @return \Cx\Modules\Shop\Model\Entity\Zones 
     */
    public function getZones()
    {
        return $this->zones;
    }
}
