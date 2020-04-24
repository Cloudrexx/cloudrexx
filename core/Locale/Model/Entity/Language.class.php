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
 * Language
 *
 * @copyright   Cloudrexx AG
 * @author      Nicola Tommasi <nicola.tommasi@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_locale
 * @version     5.0.0
 */

namespace Cx\Core\Locale\Model\Entity;

/**
 * Language
 *
 * @copyright   Cloudrexx AG
 * @author      Nicola Tommasi <nicola.tommasi@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_locale
 * @version     5.0.0
 */
class Language extends \Cx\Model\Base\EntityBase {
    /**
     * @var string $iso1
     */
    protected $iso1;

    /**
     * @var string $iso3
     */
    protected $iso3;

    /**
     * @var boolean $source
     */
    protected $source;

    /**
     * @var \Cx\Core\Locale\Model\Entity\Backend
     */
    protected $backend;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $localeRelatedBySourceLanguages;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $localeRelatedByIso1s;

    /**
     * Language constructor.
     *
     * Creates new instance of \Cx\Core\Locale\Model\Entity\Language
     */
    public function __construct()
    {
        $this->localeRelatedBySourceLanguages = new \Doctrine\Common\Collections\ArrayCollection();
        $this->localeRelatedByIso1s = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set iso1
     * @todo: Remove this method after doctrine update
     *
     * @param string $iso1
     */
    public function setId($iso1) {
        $this->setIso1($iso1);
    }

    /**
     * Get iso1
     * This method fixes the error when getting the identifier in the metadata class info,
     * which automatically calls getId() on an entity to get the identifier
     * @todo: Remove this method after doctrine update
     *
     * @return  string $iso1
     */
    public function getId() {
        return $this->getIso1();
    }
    
    /**
     * Set iso1
     *
     * @param string $iso1
     */
    public function setIso1($iso1)
    {
        $this->iso1 = $iso1;
    }

    /**
     * Get iso1
     *
     * @return string $iso1
     */
    public function getIso1()
    {
        return $this->iso1;
    }

    /**
     * Set iso3
     *
     * @param string $iso3
     */
    public function setIso3($iso3 = null)
    {
        $this->iso3 = $iso3;
    }

    /**
     * Get iso3
     *
     * @return string $iso3
     */
    public function getIso3()
    {
        return $this->iso3;
    }

    /**
     * Set source
     *
     * @param boolean $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * Get source
     *
     * @return boolean $source
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set backend
     *
     * @param \Cx\Core\Locale\Model\Entity\Backend $backend
     */
    public function setBackend(\Cx\Core\Locale\Model\Entity\Backend $backend)
    {
        $this->backend = $backend;
    }

    /**
     * Get backend
     *
     * @return \Cx\Core\Locale\Model\Entity\Backend $backend
     */
    public function getBackend()
    {
        return $this->backend;
    }

    /**
     * Add localeRelatedBySourceLanguages
     *
     * @param \Cx\Core\Locale\Model\Entity\Locale $localeRelatedBySourceLanguages
     * @return Language
     */
    public function addLocaleRelatedBySourceLanguage(\Cx\Core\Locale\Model\Entity\Locale $localeRelatedBySourceLanguages)
    {
        $this->localeRelatedBySourceLanguages[] = $localeRelatedBySourceLanguages;

        return $this;
    }

    /**
     * Remove localeRelatedBySourceLanguages
     *
     * @param \Cx\Core\Locale\Model\Entity\Locale $localeRelatedBySourceLanguages
     */
    public function removeLocaleRelatedBySourceLanguage(\Cx\Core\Locale\Model\Entity\Locale $localeRelatedBySourceLanguages)
    {
        $this->localeRelatedBySourceLanguages->removeElement($localeRelatedBySourceLanguages);
    }

    /**
     * Add localeRelatedBySourceLanguages
     *
     * @param \Cx\Core\Locale\Model\Entity\Locale $localeRelatedBySourceLanguages
     */
    public function addLocaleRelatedBySourceLanguages(\Cx\Core\Locale\Model\Entity\Locale $localeRelatedBySourceLanguages)
    {
        $this->localeRelatedBySourceLanguages[] = $localeRelatedBySourceLanguages;
    }

    /**
     * Get localeRelatedBySourceLanguages
     *
     * @return \Doctrine\Common\Collections\Collection $localeRelatedBySourceLanguages
     */
    public function getLocaleRelatedBySourceLanguages()
    {
        return $this->localeRelatedBySourceLanguages;
    }

    /**
     * Add localeRelatedByIso1s
     *
     * @param \Cx\Core\Locale\Model\Entity\Locale $localeRelatedByIso1s
     * @return Language
     */
    public function addLocaleRelatedByIso1(\Cx\Core\Locale\Model\Entity\Locale $localeRelatedByIso1s)
    {
        $this->localeRelatedByIso1s[] = $localeRelatedByIso1s;

        return $this;
    }

    /**
     * Remove localeRelatedByIso1s
     *
     * @param \Cx\Core\Locale\Model\Entity\Locale $localeRelatedByIso1s
     */
    public function removeLocaleRelatedByIso1(\Cx\Core\Locale\Model\Entity\Locale $localeRelatedByIso1s)
    {
        $this->localeRelatedByIso1s->removeElement($localeRelatedByIso1s);
    }

    /**
     * Add localeRelatedByIso1s
     *
     * @param \Cx\Core\Locale\Model\Entity\Locale $localeRelatedByIso1s
     */
    public function addLocaleRelatedByIso1s(\Cx\Core\Locale\Model\Entity\Locale $localeRelatedByIso1s)
    {
        $this->localeRelatedByIso1s[] = $localeRelatedByIso1s;
    }

    /**
     * Get localeRelatedByIso1s
     *
     * @return \Doctrine\Common\Collections\Collection $localeRelatedByIso1s
     */
    public function getLocaleRelatedByIso1s()
    {
        return $this->localeRelatedByIso1s;
    }

    /**
     * Returns the language and the iso1 code
     * using the php \Locale class
     *
     * The language is translated in the front/backend language
     *
     * @return string for example "German (de)"
     */
    public function __toString()
    {
        if ($this->cx->getMode() == \Cx\Core\Core\Controller\Cx::MODE_BACKEND) {
            $inLocale = \FWLanguage::getBackendLanguageCodeById(LANG_ID);
        } else {
            $inLocale = \FWLanguage::getLanguageCodeById(LANG_ID);
        }
        return \Locale::getDisplayLanguage($this->iso1, $inLocale) . ' (' . $this->iso1 . ')';
    }
}
