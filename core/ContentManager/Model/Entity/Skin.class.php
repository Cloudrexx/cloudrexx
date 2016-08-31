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
 * Skin
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_contentmanager
 */

namespace Cx\Core\ContentManager\Model\Doctrine\Entity;

/**
 * Skin
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_contentmanager
 */
class Skin
{
    /**
     * @var integer $id
     */
    private $id;

    /**
     * @var string $themesname
     */
    private $themesname;

    /**
     * @var string $foldername
     */
    private $foldername;

    /**
     * @var boolean $expert
     */
    private $expert;

    /**
     * @var Cx\Core\ContentManager\Model\Entity\Page
     */
    private $pages;

    public function __construct()
    {
        $this->pages = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set themesname
     *
     * @param string $themesname
     */
    public function setThemesname($themesname)
    {
        $this->themesname = $themesname;
    }

    /**
     * Get themesname
     *
     * @return string $themesname
     */
    public function getThemesname()
    {
        return $this->themesname;
    }

    /**
     * Set foldername
     *
     * @param string $foldername
     */
    public function setFoldername($foldername)
    {
        $this->foldername = $foldername;
    }

    /**
     * Get foldername
     *
     * @return string $foldername
     */
    public function getFoldername()
    {
        return $this->foldername;
    }

    /**
     * Set expert
     *
     * @param boolean $expert
     */
    public function setExpert($expert)
    {
        $this->expert = $expert;
    }

    /**
     * Get expert
     *
     * @return boolean $expert
     */
    public function getExpert()
    {
        return $this->expert;
    }

    /**
     * Add pages
     *
     * @param Cx\Core\ContentManager\Model\Entity\Page $pages
     */
    public function addPages(\Cx\Core\ContentManager\Model\Entity\Page $pages)
    {
        $this->pages[] = $pages;
    }

    /**
     * Get pages
     *
     * @return Doctrine\Common\Collections\Collection $pages
     */
    public function getPages()
    {
        return $this->pages;
    }
}
