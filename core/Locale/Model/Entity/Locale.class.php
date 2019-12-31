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
 * Locale
 *
 * @copyright   Cloudrexx AG
 * @author      Nicola Tommasi <nicola.tommasi@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_locale
 * @version     5.0.0
 */

namespace Cx\Core\Locale\Model\Entity;

/**
 * Locale
 *
 * @copyright   Cloudrexx AG
 * @author      Nicola Tommasi <nicola.tommasi@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_locale
 * @version     5.0.0
 */
class Locale extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var string $label
     */
    protected $label;

    /**
     * @var integer
     */
    protected $orderNo;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $locales;

    /**
     * @var \Cx\Core\Locale\Model\Entity\Language
     */
    protected $iso1;

    /**
     * @var \Cx\Core\Country\Model\Entity\Country
     */
    protected $country;

    /**
     * @var \Cx\Core\Locale\Model\Entity\Locale
     */
    protected $fallback;

    /**
     * @var \Cx\Core\Locale\Model\Entity\Language
     */
    protected $sourceLanguage;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $frontends;

    /**
     * Locale constructor.
     *
     * Creates new instance of \Cx\Core\Locale\Model\Entity\Locale
     */
    public function __construct()
    {
        $this->locales = new \Doctrine\Common\Collections\ArrayCollection();
        $this->frontends = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
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
     * Set label
     *
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * Get label
     *
     * @return string $label
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set order no
     *
     * @param integer $orderNo
     */
    public function setOrderNo($orderNo)
    {
        $this->orderNo = $orderNo;
    }

    /**
     * Get order no
     *
     * @return integer $orderNo
     */
    public function getOrderNo()
    {
        return $this->orderNo;
    }

    /**
     * Add locales
     *
     * @param \Cx\Core\Locale\Model\Entity\Locale $locales
     * @return Locale
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
     * Set iso1
     *
     * @param \Cx\Core\Locale\Model\Entity\Language $iso1
     */
    public function setIso1(\Cx\Core\Locale\Model\Entity\Language $iso1)
    {
        $this->iso1 = $iso1;
    }

    /**
     * Get iso1
     *
     * @return \Cx\Core\Locale\Model\Entity\Language $iso1
     */
    public function getIso1()
    {
        return $this->iso1;
    }

    /**
     * Set country
     *
     * @param \Cx\Core\Country\Model\Entity\Country $country
     */
    public function setCountry(\Cx\Core\Country\Model\Entity\Country $country = null)
    {
        $this->country = $country;
    }

    /**
     * Get country
     *
     * @return \Cx\Core\Country\Model\Entity\Country $country
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set fallback
     *
     * @param \Cx\Core\Locale\Model\Entity\Locale $fallback
     */
    public function setFallback(\Cx\Core\Locale\Model\Entity\Locale $fallback = null)
    {
        $this->fallback = $fallback;
    }

    /**
     * Get fallback
     *
     * @return \Cx\Core\Locale\Model\Entity\Locale $fallback
     */
    public function getFallback()
    {
        return $this->fallback;
    }

    /**
     * Set sourceLanguage
     *
     * @param \Cx\Core\Locale\Model\Entity\Language $sourceLanguage
     */
    public function setSourceLanguage(\Cx\Core\Locale\Model\Entity\Language $sourceLanguage)
    {
        $this->sourceLanguage = $sourceLanguage;
    }

    /**
     * Get sourceLanguage
     *
     * @return \Cx\Core\Locale\Model\Entity\Language $sourceLanguage
     */
    public function getSourceLanguage()
    {
        return $this->sourceLanguage;
    }

    /**
     * Add frontends
     *
     * @param \Cx\Core\View\Model\Entity\Frontend $frontends
     * @return Locale
     */
    public function addFrontend(\Cx\Core\View\Model\Entity\Frontend $frontends)
    {
        $this->frontends[] = $frontends;

        return $this;
    }

    /**
     * Remove frontends
     *
     * @param \Cx\Core\View\Model\Entity\Frontend $frontends
     */
    public function removeFrontend(\Cx\Core\View\Model\Entity\Frontend $frontends)
    {
        $this->frontends->removeElement($frontends);
    }

    /**
     * Add frontends
     *
     * @param \Cx\Core\View\Model\Entity\Frontend $frontends
     */
    public function addFrontends(\Cx\Core\View\Model\Entity\Frontend $frontends)
    {
        $this->frontends[] = $frontends;
    }

    /**
     * Get frontends
     *
     * @return \Doctrine\Common\Collections\Collection $frontends
     */
    public function getFrontends()
    {
        return $this->frontends;
    }

    /**
     * @return string The locale's label
     */
    public function __toString()
    {
        return $this->getLabel();
    }

    /**
     * Builds short form of locale containing iso1 and alpha2 code (if exists)
     *
     * @return string the short form (example: de-CH for swiss german)
     */
    public function getShortForm() {
        $iso1 = $this->iso1->getIso1();
        return $this->country ?  $iso1 . '-' . $this->country->getAlpha2() : $iso1;
    }
}