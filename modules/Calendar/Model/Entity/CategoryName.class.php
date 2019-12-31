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
 * CategoryName
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_calendar
*/
namespace Cx\Modules\Calendar\Model\Entity;

/**
 * CategoryName
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_calendar
*/
class CategoryName extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer $catId
     */
    protected $catId;

    /**
     * @var string $name
     */
    protected $name;

    /**
     * @var integer $langId
     */
    protected $langId;

    /**
     * @var \Cx\Modules\Calendar\Model\Entity\Category
     */
    protected $category;


    /**
     * Set catId
     *
     * @param integer $catId
     */
    public function setCatId($catId)
    {
        $this->catId = $catId;
    }

    /**
     * Get catId
     *
     * @return integer $catId
     */
    public function getCatId()
    {
        return $this->catId;
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
     * @return integer $langId
     */
    public function getLangId()
    {
        return $this->langId;
    }

    /**
     * Set category
     *
     * @param Cx\Modules\Calendar\Model\Entity\Category $category
     */
    public function setCategory(\Cx\Modules\Calendar\Model\Entity\Category $category)
    {
        $this->category = $category;
    }

    /**
     * Get category
     *
     * @return Cx\Modules\Calendar\Model\Entity\Category $category
     */
    public function getCategory()
    {
        return $this->category;
    }
}
