<?php
/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2019
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
 * PricelistRepository
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_shop
 */
namespace Cx\Modules\Shop\Model\Repository;

/**
 * PricelistRepository
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_shop
 */
class PricelistRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Get a pricelist by category and id
     *
     * @param \Cx\Modules\Shop\Model\Entity\Category $category category to search
     * @param int $pricelistId entry id to search
     * @return \Cx\Modules\Shop\Model\Entity\Pricelist found pricelist
     */
    public function getPricelistByCategoryAndId($category, $pricelistId)
    {
        $pricelists = $category->getPricelists();
        foreach ($pricelists as $pricelist) {
            if ($pricelist->getId() == $pricelistId) {
                return $pricelist;
            }
        }
    }

    /**
     * Get category IDs by pricelist
     *
     * @param \Cx\Modules\Shop\Model\Entity\Pricelist $pricelist pricelist to
     *                                                           search
     * @return array all IDs of the related categories
     */
    public function getCategoryIdsByPricelist($pricelist)
    {
        $categories = $pricelist->getCategories();
        $categoryIds = array();
        foreach ($categories as $category) {
            $categoryIds[] = $category->getId();
        }

        return $categoryIds;
    }
}
