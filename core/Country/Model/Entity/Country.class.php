<?php

/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2016
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
 * Country
 *
 * @copyright   Cloudrexx AG
 * @author      Nicola Tommasi <nicola.tommasi@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_locale
 * @version     5.0.0
 */

namespace Cx\Core\Country\Model\Entity;

/**
 * Country
 *
 * @copyright   Cloudrexx AG
 * @author      Nicola Tommasi <nicola.tommasi@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_locale
 * @version     5.0.0
 */
class Country extends \Cx\Model\Base\EntityBase {
    /**
     * @var string $alpha2
     */
    protected $alpha2;

    /**
     * @var string $alpha3
     */
    protected $alpha3;

    /**
     * @var integer $ord
     */
    protected $ord;

    /**
     * @var Cx\Core\Locale\Model\Entity\Locale
     */
    protected $locales;

    /**
     * Country constructor.
     *
     * Creates new instance of Cx\Core\Country\Model\Entity\Country
     */
    public function __construct()
    {
        $this->locales = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Set alpha2
     *
     * @param string $alpha2
     */
    public function setAlpha2($alpha2)
    {
        $this->alpha2 = $alpha2;
    }

    /**
     * Get alpha2
     *
     * @return string $alpha2
     */
    public function getAlpha2()
    {
        return $this->alpha2;
    }

    /**
     * Set alpha3
     *
     * @param string $alpha3
     */
    public function setAlpha3($alpha3)
    {
        $this->alpha3 = $alpha3;
    }

    /**
     * Get alpha3
     *
     * @return string $alpha3
     */
    public function getAlpha3()
    {
        return $this->alpha3;
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
     * @return integer $ord
     */
    public function getOrd()
    {
        return $this->ord;
    }

    /**
     * Add locales
     *
     * @param \Cx\Core\Locale\Model\Entity\Locale $locales
     * @return Country
     */
    public function addLocale(\Cx\Core\Locale\Model\Entity\Locale $locales)
    {
        $this->locales[] = $locales;

        return $this;
    }

    /**
     * Remove locales
     *
     * @param \Cx\Core\Locale\Model\Entity\Locale $locales
     */
    public function removeLocale(\Cx\Core\Locale\Model\Entity\Locale $locales)
    {
        $this->locales->removeElement($locales);
    }

    /**
     * Add locales
     *
     * @param \Cx\Core\Locale\Model\Entity\Locale $locales
     */
    public function addLocales(\Cx\Core\Locale\Model\Entity\Locale $locales)
    {
        $this->locales[] = $locales;
    }

    /**
     * Get locales
     *
     * @return \Doctrine\Common\Collections\Collection $locales
     */
    public function getLocales()
    {
        return $this->locales;
    }

    /**
     * Returns the region and the alpha 2 code
     * using the php \Locale class
     *
     * The region is translated in the front/backend language
     *
     * @return string for example "Germany (DE)"
     */
    public function __toString()
    {
        if ($this->cx->getMode() == \Cx\Core\Core\Controller\Cx::MODE_BACKEND) {
            $inLocale = \FWLanguage::getBackendLanguageCodeById(LANG_ID);
        } else {
            $inLocale = \FWLanguage::getLanguageCodeById(LANG_ID);
        }
        return \Locale::getDisplayRegion('und_' . $this->alpha2, $inLocale) . ' (' . $this->alpha2 . ')';
    }
}
