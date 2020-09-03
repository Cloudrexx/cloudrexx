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
class Pricelist extends \Cx\Model\Base\EntityBase
{
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
    protected $langId = 0;

    /**
     * @var boolean
     */
    protected $borderOn = true;

    /**
     * @var boolean
     */
    protected $headerOn = true;

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
    protected $footerOn = false;

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
    protected $allCategories = 0;

    /**
     * @var string font - not an entity attr
     */
    protected $font = 'Helvetica';

    /**
     * @var int font size header - not an entity attr
     */
    protected $fontSizeHeader = 8;

    /**
     * @var int font size footer - not an entity attr
     */
    protected $fontSizeFooter = 7;

    /**
     * @var int font size list - not an entity attr
     */
    protected $fontSizeList = 7;

    /**
     * @var string row color 1 - not an entity attr
     */
    protected $rowColor1 = 'dddddd';

    /**
     * @var string row color 2 - not an entity attr
     */
    protected $rowColor2 = 'ffffff';

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
     * Add category
     *
     * @param \Cx\Modules\Shop\Model\Entity\Category $categoy
     */
    public function addCategory(\Cx\Modules\Shop\Model\Entity\Category $category)
    {
        $category->addPricelist($this);
        $this->categories[] = $category;
    }

    /**
     * Remove category
     *
     * @param \Cx\Modules\Shop\Model\Entity\Category $category
     */
    public function removeCategory(\Cx\Modules\Shop\Model\Entity\Category $category)
    {
        $category->removePricelist($this);
        $this->categories->removeElement($category);
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

    /**
     * Set font
     *
     * @param string $font
     */
    public function setFont($font)
    {
        $this->font = $font;
    }

    /**
     * Get font
     *
     * @return string
     */
    public function getFont()
    {
        return $this->font;
    }

    /**
     * Set font size header
     *
     * @param int $fontSizeHeader
     */
    public function setFontSizeHeader($fontSizeHeader)
    {
        $this->fontSizeHeader = $fontSizeHeader;
    }

    /**
     * Get font size header
     *
     * @return int
     */
    public function getFontSizeHeader()
    {
        return $this->fontSizeHeader;
    }

    /**
     * Set font size footer
     *
     * @param int $fontSizeFooter
     */
    public function setFontSizeFooter($fontSizeFooter)
    {
        $this->fontSizeFooter = $fontSizeFooter;
    }

    /**
     * Get font size footer
     *
     * @return int
     */
    public function getFontSizeFooter()
    {
        return $this->fontSizeFooter;
    }

    /**
     * Set font size list
     *
     * @param int $fontSizeList
     */
    public function setFontSizeList($fontSizeList)
    {
        $this->fontSizeList = $fontSizeList;
    }

    /**
     * Get font size list
     *
     * @return int
     */
    public function getFontSizeList()
    {
        return $this->fontSizeList;
    }

    /**
     * Set row color 1
     *
     * @param string $rowColor1
     */
    public function setRowColor1($rowColor1)
    {
        $this->rowColor1 = $rowColor1;
    }

    /**
     * Get row color 1
     *
     * @return string
     */
    public function getRowColor1()
    {
        return $this->rowColor1;
    }

    /**
     * Set row color 2
     *
     * @param string $rowColor2
     */
    public function setRowColor2($rowColor2)
    {
        $this->rowColor2 = $rowColor2;
    }

    /**
     * Get row color 2
     *
     * @return string
     */
    public function getRowColor2()
    {
        return $this->rowColor2;
    }

}
