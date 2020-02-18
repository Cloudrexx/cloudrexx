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
 * CategoryController to handle categories
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
namespace Cx\Modules\Shop\Controller;

/**
 * CategoryController to handle categories
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class CategoryController extends \Cx\Core\Core\Model\Entity\Controller
{
    /**
     * Get ViewGenerator options for Category entity
     *
     * @param $options array predefined ViewGenerator options
     * @return array includes ViewGenerator options for Category entity
     */
    public function getViewGeneratorOptions($options)
    {
        $options['order']['overview'] = array(
            'id',
            'active',
            'name'
        );
        $options['order']['form'] = array(
            'name',
            'parentCategory',
            'active',
            'picture',
            'shortDescription',
            'description'
        );
        $options['functions']['sortBy'] = array(
            'field' => array('ord' => SORT_ASC)
        );
        $options['functions']['sorting'] = false;

        $options['fields'] = array(
            'id' => array(
                'table' => array(
                    'attributes' => array(
                        'class' => 'category-id',
                    ),
                ),
            ),
            'active' => array(
                'showOverview' => false,
                'sorting' => false,
            ),
            'name' => array(
                'table' => array(
                    'attributes' => array(
                        'class' => 'category-name',
                    ),
                ),
            ),
            'parentCategory' => array(
                'showOverview' => false,
            ),
            'parentId' => array(
                'showOverview' => false,
                'showDetail' => false,
            ),
            'picture' => array(
                'showOverview' => false,
                'type' => 'image',
            ),
            'shortDescription' => array(
                'showOverview' => false,
            ),
            'description' => array(
                'showOverview' => false,
            ),
            'ord' => array(
                'showOverview' => false,
                'type' => 'hidden',
            ),
            'flags' => array(
                'showOverview' => false,
                'type' => 'hidden',
            ),
            'children' => array(
                'showOverview' => false,
                'showDetail' => false,
            ),
            'pricelists' => array(
                'showOverview' => false,
                'showDetail' => false,
            ),
            'products' => array(
                'showOverview' => false,
                'showDetail' => false,
            ),
        );
        return $options;
    }
}