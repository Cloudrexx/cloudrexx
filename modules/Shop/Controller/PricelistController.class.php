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
 * PricelistController to handle pricelists
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
namespace Cx\Modules\Shop\Controller;

/**
 * PricelistController to handle pricelists
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class PricelistController extends \Cx\Core\Core\Model\Entity\Controller
{
    /**
     * Get ViewGenerator options for entity
     *
     * @param $options array predefined ViewGenerator options
     * @return array includes ViewGenerator options for entity
     */
    public function getViewGeneratorOptions($options)
    {
        global $_ARRAYLANG;

        $options['order']['form'] = array(
            'pdfLink',
            'name',
            'lang',
        );

        $options['fields'] = array(
            'id' => array(
                'table' => array(
                    'attributes' => array(
                        'class' => 'pricelist-id',
                    ),
                ),
            ),
            'name' => array(
                'table' => array(
                    'attributes' => array(
                        'class' => 'pricelist-name',
                    ),
                ),
            ),
            'langId' => array(
                'showOverview' => false,
                'showDetail' => false,
            ),
            'borderOn' => array(
                'showOverview' => false
            ),
            'headerOn' => array(
                'showOverview' => false
            ),
            'headerLeft' => array(
                'showOverview' => false,
                'formfield' => array(
                    'adapter' => 'PriceList',
                    'method' => 'getLineFieldHeader'
                ),
            ),
            'headerRight' => array(
                'showOverview' => false,
                'type' => 'hidden'
            ),
            'footerOn' => array(
                'showOverview' => false
            ),
            'footerLeft' => array(
                'showOverview' => false,
                'formfield' => array(
                    'adapter' => 'PriceList',
                    'method' => 'getLineFieldFooter'
                ),
            ),
            'footerRight' => array(
                'showOverview' => false,
                'type' => 'hidden'
            ),
            'allCategories' => array(
                'showOverview' => false,
                'formfield' => array(
                    'adapter' => 'PriceList',
                    'method' => 'getAllCategoriesCheckbox'
                ),
                'storecallback' => array(
                    'adapter' => 'PriceList',
                    'method' => 'checkIfAllCategoriesAreSelected'
                ),
            ),
            'lang' => array(
                'showOverview' => false
            ),
            'categories' => array(
                'header' => '',
                'showOverview' => false,
                'mode' => 'associate',
                'formfield' => array(
                    'adapter' => 'PriceList',
                    'method' => 'getCategoryCheckboxesForPricelist'
                ),
            ),
            'pdfLink' => array(
                'custom' => true,
                'header' => $_ARRAYLANG['TXT_PDF_LINK'],
                'type' => 'div',
                'valueCallback' => array(
                    'adapter' => 'PriceList',
                    'method' => 'getGeneratedPdfLink'
                ),
                'table' => array(
                    'parse' =>  array(
                        'adapter' => 'PriceList',
                        'method' => 'getLinkElement'
                    ),
                ),
                'formfield' => array(
                    'adapter' => 'PriceList',
                    'method' => 'getLinkElement'
                ),
            )
        );

        return $options;
    }
}