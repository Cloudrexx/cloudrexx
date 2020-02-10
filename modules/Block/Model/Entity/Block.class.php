<?php

/**
 * Cloudrexx
 *
 * @link      https://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2017
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
 * Cx\Modules\Block
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_block
 */

namespace Cx\Modules\Block\Model\Entity;

/**
 * Cx\Modules\Block\Model\Entity\Block
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Manuel Schenk <manuel.schenk@comvation.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_block
 */
class Block extends \Cx\Core_Modules\Widget\Model\Entity\WidgetParseTarget implements \Gedmo\Loggable\Loggable
{
    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var integer $start
     */
    protected $start;

    /**
     * @var integer $end
     */
    protected $end;

    /**
     * @var string $name
     */
    protected $name;

    /**
     * @var integer $random
     */
    protected $random;

    /**
     * @var integer $random2
     */
    protected $random2;

    /**
     * @var integer $random3
     */
    protected $random3;

    /**
     * @var integer $random4
     */
    protected $random4;

    /**
     * @var integer $showInCategory
     */
    protected $showInCategory;

    /**
     * @var integer $showInGlobal
     */
    protected $showInGlobal;

    /**
     * @var integer $showInDirect
     */
    protected $showInDirect;

    /**
     * @var integer $active
     */
    protected $active;

    /**
     * @var integer $order
     */
    protected $order;

    /**
     * @var integer $wysiwygEditor
     */
    protected $wysiwygEditor;

    /**
     * @var Doctrine\Common\Collections\Collection $relLangContents
     */
    protected $relLangContents;

    /**
     * @var Doctrine\Common\Collections\Collection $relPages
     */
    protected $relPages;

    /**
     * @var Doctrine\Common\Collections\Collection $targetingOptions
     */
    protected $targetingOptions;

    /**
     * @var Cx\Modules\Block\Model\Entity\Category
     */
    protected $category;

    /**
     * @var string $versionTargetingOption
     */
    protected $versionTargetingOption;

    /**
     * @var string $versionRelLangContent
     */
    protected $versionRelLangContent;

    /**
     * @var string $versionRelPageGlobal
     */
    protected $versionRelPageGlobal;

    /**
     * @var string $versionRelPageCategory
     */
    protected $versionRelPageCategory;

    /**
     * @var string $versionRelPageDirect
     */
    protected $versionRelPageDirect;

    public function __construct()
    {
        $this->relLangContents = new \Doctrine\Common\Collections\ArrayCollection();
        $this->relPages = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set id
     *
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
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
     * Set start
     *
     * @param integer $start
     */
    public function setStart($start)
    {
        $this->start = $start;
    }

    /**
     * Get start
     *
     * @return integer $start
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Set end
     *
     * @param integer $end
     */
    public function setEnd($end)
    {
        $this->end = $end;
    }

    /**
     * Get end
     *
     * @return integer $end
     */
    public function getEnd()
    {
        return $this->end;
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
     * Set random
     *
     * @param integer $random
     */
    public function setRandom($random)
    {
        $this->random = $random;
    }

    /**
     * Get random
     *
     * @return integer $random
     */
    public function getRandom()
    {
        return $this->random;
    }

    /**
     * Set random2
     *
     * @param integer $random2
     */
    public function setRandom2($random2)
    {
        $this->random2 = $random2;
    }

    /**
     * Get random2
     *
     * @return integer $random2
     */
    public function getRandom2()
    {
        return $this->random2;
    }

    /**
     * Set random3
     *
     * @param integer $random3
     */
    public function setRandom3($random3)
    {
        $this->random3 = $random3;
    }

    /**
     * Get random3
     *
     * @return integer $random3
     */
    public function getRandom3()
    {
        return $this->random3;
    }

    /**
     * Set random4
     *
     * @param integer $random4
     */
    public function setRandom4($random4)
    {
        $this->random4 = $random4;
    }

    /**
     * Get random4
     *
     * @return integer $random4
     */
    public function getRandom4()
    {
        return $this->random4;
    }

    /**
     * Set showInCategory
     *
     * @param integer $showInCategory
     */
    public function setShowInCategory($showInCategory)
    {
        $this->showInCategory = $showInCategory;
    }

    /**
     * Get showInCategory
     *
     * @return integer $showInCategory
     */
    public function getShowInCategory()
    {
        return $this->showInCategory;
    }

    /**
     * Set showInGlobal
     *
     * @param integer $showInGlobal
     */
    public function setShowInGlobal($showInGlobal)
    {
        $this->showInGlobal = $showInGlobal;
    }

    /**
     * Get showInGlobal
     *
     * @return integer $showInGlobal
     */
    public function getShowInGlobal()
    {
        return $this->showInGlobal;
    }

    /**
     * Set showInDirect
     *
     * @param integer $showInDirect
     */
    public function setShowInDirect($showInDirect)
    {
        $this->showInDirect = $showInDirect;
    }

    /**
     * Get showInDirect
     *
     * @return integer $showInDirect
     */
    public function getShowInDirect()
    {
        return $this->showInDirect;
    }

    /**
     * Set active
     *
     * @param integer $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * Get active
     *
     * @return integer $active
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set order
     *
     * @param integer $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * Get order
     *
     * @return integer $order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set wysiwygEditor
     *
     * @param integer $wysiwygEditor
     */
    public function setWysiwygEditor($wysiwygEditor)
    {
        $this->wysiwygEditor = $wysiwygEditor;
    }

    /**
     * Get wysiwygEditor
     *
     * @return integer $wysiwygEditor
     */
    public function getWysiwygEditor()
    {
        return $this->wysiwygEditor;
    }

    /**
     * Add relLangContent
     *
     * @param Cx\Modules\Block\Model\Entity\RelLangContent $relLangContent
     */
    public function addRelLangContent(\Cx\Modules\Block\Model\Entity\RelLangContent $relLangContent)
    {
        $this->relLangContents[] = $relLangContent;
    }

    /**
     * Set relLangContents
     *
     * @param Doctrine\Common\Collections\Collection $relLangContents
     */
    public function setRelLangContents($relLangContents)
    {
        $this->relLangContents = $relLangContents;
    }

    /**
     * Get relLangContents
     *
     * @return Doctrine\Common\Collections\Collection $relLangContents
     */
    public function getRelLangContents()
    {
        return $this->relLangContents;
    }

    /**
     * Add relPage
     *
     * @param Cx\Modules\Block\Model\Entity\RelPage $relPage
     */
    public function addRelPage(\Cx\Modules\Block\Model\Entity\RelPage $relPage)
    {
        $this->relPages[] = $relPage;
    }

    /**
     * Set relPages
     *
     * @param Doctrine\Common\Collections\Collection $relPages
     */
    public function setRelPages($relPages)
    {
        $this->relPages = $relPages;
    }

    /**
     * Get relPages
     *
     * @return Doctrine\Common\Collections\Collection $relPages
     */
    public function getRelPages()
    {
        return $this->relPages;
    }

    /**
     * Add targetingOption
     *
     * @param Cx\Modules\Block\Model\Entity\TargetingOption $targetingOption
     */
    public function addTargetingOption(\Cx\Modules\Block\Model\Entity\TargetingOption $targetingOption)
    {
        $this->targetingOptions[] = $targetingOption;
    }

    /**
     * Set targetingOptions
     *
     * @param Doctrine\Common\Collections\Collection $targetingOptions
     */
    public function setTargetingOptions($targetingOptions)
    {
        $this->targetingOptions = $targetingOptions;
    }

    /**
     * Get targetingOptions
     *
     * @return Doctrine\Common\Collections\Collection $targetingOptions
     */
    public function getTargetingOptions()
    {
        return $this->targetingOptions;
    }

    /**
     * Set category
     *
     * @param Cx\Modules\Block\Model\Entity\Category $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * Get category
     *
     * @return Cx\Modules\Block\Model\Entity\Category $category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set versionTargetingOption
     *
     * @param string $versionTargetingOption
     */
    public function setVersionTargetingOption($versionTargetingOption)
    {
        $this->versionTargetingOption = $versionTargetingOption;
    }

    /**
     * Get versionTargetingOption
     *
     * @return string $versionTargetingOption
     */
    public function getVersionTargetingOption()
    {
        return $this->versionTargetingOption;
    }

    /**
     * Set versionRelLangContent
     *
     * @param string $versionRelLangContent
     */
    public function setVersionRelLangContent($versionRelLangContent)
    {
        $this->versionRelLangContent = $versionRelLangContent;
    }

    /**
     * Get versionRelLangContent
     *
     * @return string $versionRelLangContent
     */
    public function getVersionRelLangContent()
    {
        return $this->versionRelLangContent;
    }

    /**
     * Set versionRelPageGlobal
     *
     * @param string $versionRelPageGlobal
     */
    public function setVersionRelPageGlobal($versionRelPageGlobal)
    {
        $this->versionRelPageGlobal = $versionRelPageGlobal;
    }

    /**
     * Get versionRelPageGlobal
     *
     * @return string $versionRelPageGlobal
     */
    public function getVersionRelPageGlobal()
    {
        return $this->versionRelPageGlobal;
    }

    /**
     * Set versionRelPageCategory
     *
     * @param string $versionRelPageCategory
     */
    public function setVersionRelPageCategory($versionRelPageCategory)
    {
        $this->versionRelPageCategory = $versionRelPageCategory;
    }

    /**
     * Get versionRelPageCategory
     *
     * @return string $versionRelPageCategory
     */
    public function getVersionRelPageCategory()
    {
        return $this->versionRelPageCategory;
    }

    /**
     * Set versionRelPageDirect
     *
     * @param string $versionRelPageDirect
     */
    public function setVersionRelPageDirect($versionRelPageDirect)
    {
        $this->versionRelPageDirect = $versionRelPageDirect;
    }

    /**
     * Get versionRelPageDirect
     *
     * @return string $versionRelPageDirect
     */
    public function getVersionRelPageDirect()
    {
        return $this->versionRelPageDirect;
    }

    /**
     * Returns this block's content. Required WidgetParseTarget getter
     * @param int $langId Internal language/locale ID
     * @return string Block content
     */
    public function getContent($localeId) {
        foreach ($this->relLangContents as $content) {
            if (!$content->getActive()) {
                continue;
            }
            if ($content->getLocale()->getId() == $localeId) {
                return $content->getContent();
            }
        }

        return '';
    }

    /**
     * Returns the name of the attribute which contains content that may contain a widget
     * @param string $widgetName
     * @return string Attribute name
     */
    public function getWidgetContentAttributeName($widgetName)
    {
        return 'content';
    }
}
