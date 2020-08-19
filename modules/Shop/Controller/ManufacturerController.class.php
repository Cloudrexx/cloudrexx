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
 * ManufacturerController to handle manufacturers
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
namespace Cx\Modules\Shop\Controller;

/**
 * ManufacturerController to handle manufacturers
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class ManufacturerController extends \Cx\Core\Core\Model\Entity\Controller
{
    /**
     * Static class data with the manufacturers
     * @var   array
     */
    protected static $arrManufacturer = null;

    /**
     * Get ViewGenerator options for Manufacturer entity
     *
     * @global array $_ARRAYLANG containing the language variables
     * @param  array $options    predefined ViewGenerator options
     * @return array includes ViewGenerator options for Manufacturer entity
     */
    public function getViewGeneratorOptions($options)
    {
        global $_ARRAYLANG;

        $options['order']['overview'] = array(
            'id',
            'name',
            'uri'
        );
        $options['order']['form'] = array(
            'name',
            'uri'
        );

        $options['multiActions']['delete'] = array(
            'title' => $_ARRAYLANG['TXT_DELETE'],
            'jsEvent' => 'delete:manufacturer'
        );

        // Delete Event
        $scope = 'order';
        \ContrexxJavascript::getInstance()->setVariable(
            'CSRF_PARAM',
            \Cx\Core\Csrf\Controller\Csrf::code(),
            $scope
        );
        \ContrexxJavascript::getInstance()->setVariable(
            'TXT_CONFIRM_DELETE_MANUFACTURER',
            $_ARRAYLANG['TXT_CONFIRM_DELETE_MANUFACTURER'],
            $scope
        );
        \ContrexxJavascript::getInstance()->setVariable(
            'TXT_ACTION_IS_IRREVERSIBLE',
            $_ARRAYLANG['TXT_ACTION_IS_IRREVERSIBLE'],
            $scope
        );

        $options['fields'] = array(
            'id' => array(
                'table' => array(
                    'attributes' => array(
                        'class' => 'manufacturer-id',
                    ),
                ),
            ),
            'name' => array(
                'table' => array(
                    'attributes' => array(
                        'class' => 'manufacturer-name',
                    ),
                ),
                'sorting' => false,
            ),
            'uri' => array(
                'table' => array(
                    'attributes' => array(
                        'class' => 'manufacturer-uri',
                    ),
                ),
                'sorting' => false,
            ),
            'products' => array(
                'showOverview' => false,
                'showDetail' => false,
            ),
        );
        return $options;
    }

    /**
     * Initialise the Manufacturer array
     *
     * Uses the FRONTEND_LANG_ID constant to determine the language.
     * The array has the form
     *  array(
     *    'id' => Manufacturer ID,
     *    'name' => Manufacturer name,
     *    'url' => Manufacturer URI,
     *  )
     * @static
     * @param   array            $order      The optional sorting order.
     *                                        Defaults to null (unsorted)
     * @return  boolean                       True on success, false otherwise
     * @global  ADONewConnection  $objDatabase
     * @global  array             $_ARRAYLANG
     * @todo    Order the Manufacturers by their name
     */
    protected static function init($order=null)
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $repo = $cx->getDb()->getEntityManager()->getRepository(
            '\Cx\Modules\Shop\Model\Entity\Manufacturer'
        );
        $manufactures = $repo->findBy(array(), $order);

        if (!$manufactures) return false;
        self::$arrManufacturer = array();

        foreach ($manufactures as $manufacture) {
            $id = $manufacture->getId();
            $strName = $manufacture->getName();
            $strUrl = $manufacture->getUri();

            self::$arrManufacturer[$id] = array(
                'id' => $id,
                'name' => $strName,
                'url' => $strUrl,
            );
        }
        return true;
    }

    /**
     * Get the Manufacturer dropdown menu HTML code string.
     *
     * Used in the Product search form, see {@link products()}.
     * @static
     * @param   string  $menuName      The optional menu name.  Defaults to
     *                                 manufacturer_id
     * @param   integer $selectedId    The optional preselected Manufacturer ID
     * @param   boolean $includeNone   If true, a dummy option for "none" is
     *                                 included at the top
     * @return  string                 The Manufacturer dropdown menu HTML code
     * @global  ADONewConnection
     * @global  array
     */
    public static function getMenu(
        $menuName='manufacturerId', $selectedId=0, $includeNone=false
    ) {
        return \Html::getSelectCustom(
            $menuName, self::getMenuoptions($selectedId, $includeNone)
        );
    }

    /**
     * Returns the Manufacturer HTML dropdown menu options code
     *
     * Used in the Product search form, see {@link products()}.
     * @static
     * @param   integer $selectedId    The optional preselected Manufacturer ID
     * @param   boolean $includeNone   If true, a dummy option for "none" is
     *                                 included at the top
     * @return  string                 The Manufacturer dropdown menu options
     * @global  ADONewConnection  $objDatabase
     */
    public static function getMenuoptions($selectedId=0, $includeNone=false)
    {
        global $_ARRAYLANG;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        if ($cx->getMode() == \Cx\Core\Core\Controller\Cx::MODE_FRONTEND) {
            $noneLabel = $_ARRAYLANG['TXT_SHOP_MANUFACTURER_ALL'];
        } else {
            $noneLabel = $_ARRAYLANG['TXT_SHOP_PLEASE_SELECT'];
        }

        return
            ($includeNone
                ? '<option value="0">'. $noneLabel . '</option>'
                : '').
            \Html::getOptions(self::getNameArray(), $selectedId);
    }

    /**
     * Returns the array of Manufacturer names
     *
     * Call this only *after* updating the database, or the static
     * array in here will be outdated.
     * database table.
     * @return  array               The Manufacturer name array
     */
    protected static function getNameArray()
    {
        static $arrManufacturerName = null;
        if (is_null($arrManufacturerName)) {
            $arrManufacturerName = array();
            $count = 0;
            foreach (self::getArray($count, array('name' => 'ASC'), 0, 1000)
                     as $id => $arrManufacturer) {
                $arrManufacturerName[$id] = $arrManufacturer['name'];
            }
        }
        return $arrManufacturerName;
    }

    /**
     * Returns an array of Manufacturers
     *
     * The $filter parameter is unused, as this functionality is not
     * implemented yet.
     * Note that you *SHOULD* re-init() the array after changing the
     * database table.
     * See {@link init()} for details on the array.
     * @param   integer   $count    The count, by reference
     * @param   array    $order    The optional sorting order.
     *                              Defaults to null
     * @param   integer   $offset   The optional record offset.
     *                              Defaults to 0 (zero)
     * @param   integer   $limit    The optional record count limit
     *                              Defaults to null (all records)
     * @return  array               The Manufacturer array on success,
     *                              null otherwise
     * //@param   array     $filter   NOT IMPLEMENTED: The optional filter array.
     * //                             Defaults to null
     * @todo    Implement the filter
     */
    protected static function getArray(&$count, $order=null, $offset=0, $limit=null)//, $filter=null)
    {
        // Shut up the code analyzer
        if (is_null(self::$arrManufacturer)) self::init($order);
        $count = count(self::$arrManufacturer);
        return array_slice(self::$arrManufacturer, $offset, $limit, true);
    }
}