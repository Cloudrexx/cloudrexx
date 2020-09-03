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
 * Class Pricelists
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
namespace Cx\Modules\Shop\Model\Entity;

/**
 * A pricelist lists all products from selected categories. Header and footer
 * are editable.
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
class Pricelists extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var integer
     */
    protected $langId;

    /**
     * @var boolean
     */
    protected $borderOn;

    /**
     * @var boolean
     */
    protected $headerOn;

    /**
     * @var string
     */
    protected $headerLeft;

    /**
     * @var string
     */
    protected $headerRight;

    /**
     * @var boolean
     */
    protected $footerOn;

    /**
     * @var string
     */
    protected $footerLeft;

    /**
     * @var string
     */
    protected $footerRight;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $categories;

    /**
     * @var \Cx\Core\Locale\Model\Entity\Locale
     */
    protected $lang;

    /**
     * @var boolean
     */
    protected $allCategories;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->categories = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set langId
     *
     * @param integer $langId
     */
    public function setLangId($langId)
    {
        $this->langId = $langId;
    }

    /**
     * Get langId
     *
     * @return integer 
     */
    public function getLangId()
    {
        return $this->langId;
    }

    /**
     * Set borderOn
     *
     * @param boolean $borderOn
     */
    public function setBorderOn($borderOn)
    {
        $this->borderOn = $borderOn;
    }

    /**
     * Get borderOn
     *
     * @return boolean 
     */
    public function getBorderOn()
    {
        return $this->borderOn;
    }

    /**
     * Set headerOn
     *
     * @param boolean $headerOn
     */
    public function setHeaderOn($headerOn)
    {
        $this->headerOn = $headerOn;
    }

    /**
     * Get headerOn
     *
     * @return boolean 
     */
    public function getHeaderOn()
    {
        return $this->headerOn;
    }

    /**
     * Set headerLeft
     *
     * @param string $headerLeft
     */
    public function setHeaderLeft($headerLeft)
    {
        $this->headerLeft = $headerLeft;
    }

    /**
     * Get headerLeft
     *
     * @return string 
     */
    public function getHeaderLeft()
    {
        return $this->headerLeft;
    }

    /**
     * Set headerRight
     *
     * @param string $headerRight
     */
    public function setHeaderRight($headerRight)
    {
        $this->headerRight = $headerRight;
    }

    /**
     * Get headerRight
     *
     * @return string 
     */
    public function getHeaderRight()
    {
        return $this->headerRight;
    }

    /**
     * Set footerOn
     *
     * @param boolean $footerOn
     */
    public function setFooterOn($footerOn)
    {
        $this->footerOn = $footerOn;
    }

    /**
     * Get footerOn
     *
     * @return boolean 
     */
    public function getFooterOn()
    {
        return $this->footerOn;
    }

    /**
     * Set footerLeft
     *
     * @param string $footerLeft
     */
    public function setFooterLeft($footerLeft)
    {
        $this->footerLeft = $footerLeft;
    }

    /**
     * Get footerLeft
     *
     * @return string 
     */
    public function getFooterLeft()
    {
        return $this->footerLeft;
    }

    /**
     * Set footerRight
     *
     * @param string $footerRight
     */
    public function setFooterRight($footerRight)
    {
        $this->footerRight = $footerRight;
    }

    /**
     * Get footerRight
     *
     * @return string 
     */
    public function getFooterRight()
    {
        return $this->footerRight;
    }

    /**
     * Add categories
     *
     * @param \Cx\Modules\Shop\Model\Entity\Categories $categories
     */
    public function addCategory(\Cx\Modules\Shop\Model\Entity\Categories $categories)
    {
        $this->categories[] = $categories;
    }

    /**
     * Remove categories
     *
     * @param \Cx\Modules\Shop\Model\Entity\Categories $categories
     */
    public function removeCategory(\Cx\Modules\Shop\Model\Entity\Categories $categories)
    {
        $this->categories->removeElement($categories);
    }

    /**
     * Get categories
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * Set lang
     *
     * @param \Cx\Core\Locale\Model\Entity\Locale $lang
     */
    public function setLang(\Cx\Core\Locale\Model\Entity\Locale $lang = null)
    {
        $this->lang = $lang;
    }

    /**
     * Get lang
     *
     * @return \Cx\Core\Locale\Model\Entity\Locale
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * Set if entity has all categories
     *
     * @param boolean
     */
    public function setAllCategories($allCategories)
    {
        $this->allCategories = $allCategories;
    }

    /**
     * Get if entity has all categories
     *
     * @return boolean
     */
    public function getAllCategories()
    {
        return $this->allCategories;
    }
}
