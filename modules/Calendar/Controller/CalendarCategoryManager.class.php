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
 * Calendar Class Catagory Manager
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx <info@cloudrexx.com>
 * @version     $Id: index.inc.php,v 1.00 $
 * @package     cloudrexx
 * @subpackage  module_calendar
 */
namespace Cx\Modules\Calendar\Controller;


/**
 * CalendarCategoryManager
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx <info@cloudrexx.com>
 * @version     $Id: index.inc.php,v 1.00 $
 * @package     cloudrexx
 * @subpackage  module_calendar
 */
class CalendarCategoryManager extends CalendarLibrary
{
    /**
     * Category List
     *
     * @access public
     * @var array
     */
    public $categoryList = array();

    /**
     * Only Active
     *
     * @access private
     * @var boolean
     */
    private $onlyActive;

    /**
     * Category selection dropdown view modes
     *
     * @see
     */
    const DROPDOWN_TYPE_FILTER = 'filter';
    const DROPDOWN_TYPE_ASSIGN = 'assign';
    const DROPDOWN_TYPE_DEFAULT = 'default';

    /**
     * Constructor
     *
     * @param boolean $onlyActive
     */
    function __construct($onlyActive=false){
        $this->onlyActive = $onlyActive;
    }

    /**
     * Returns all the calendar categories
     *
     * @global object  $objDatabase
     * @global integer $_LANGID
     * @return array Returns all calendar categories
     */
    function getCategoryList() {
        global $objDatabase,$_LANGID;

        $onlyActive_where = ($this->onlyActive == true ? ' WHERE status=1' : '');

        $query = "SELECT category.id AS id
                    FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_category AS category
                         ".$onlyActive_where."
                ORDER BY category.pos";

        $objResult = $objDatabase->Execute($query);

        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $objCategory = new \Cx\Modules\Calendar\Controller\CalendarCategory(intval($objResult->fields['id']));
                $this->categoryList[] = $objCategory;
                $objResult->MoveNext();
            }
        }
    }

    /**
     * Sets the category placeholder's to the template
     *
     * @global object $objInit
     * @global array $_ARRAYLANG
     * @param object $objTpl
     * @param integer $categoryId
     */
    function showCategory($objTpl, $categoryId) {
        global $objInit, $_ARRAYLANG;

        $objCategory = new \Cx\Modules\Calendar\Controller\CalendarCategory(intval($categoryId));
        $this->categoryList[$categoryId] = $objCategory;

        $objCategory->getData();

        $objTpl->setVariable(array(
            $this->moduleLangVar.'_CATEGORY_ID'              => $objCategory->id,
            $this->moduleLangVar.'_CATEGORY_STATUS'          => $objCategory->status==0 ? $_ARRAYLANG['TXT_CALENDAR_INACTIVE'] : $_ARRAYLANG['TXT_CALENDAR_ACTIVE'],
            $this->moduleLangVar.'_CATEGORY_NAME'            => $objCategory->name,
            $this->moduleLangVar.'_CATEGORY_NAME_MASTER'     => $objCategory->arrData['name'][0],
        ));
    }

    /**
     * Sets the category placeholder's to the template for the list view
     *
     * @global array $_ARRAYLANG
     * @param object $objTpl
     */
    function showCategoryList($objTpl) {
        global $_ARRAYLANG;

        $i=0;
        foreach ($this->categoryList as $key => $objCategory) {
            $objTpl->setVariable(array(
                $this->moduleLangVar.'_CATEGORY_ROW'     => $i%2==0 ? 'row1' : 'row2',
                $this->moduleLangVar.'_CATEGORY_ID'      => $objCategory->id,
                $this->moduleLangVar.'_CATEGORY_LED'     => $objCategory->status==0 ? 'red' : 'green',
                $this->moduleLangVar.'_CATEGORY_STATUS'  => $objCategory->status==0 ? $_ARRAYLANG['TXT_CALENDAR_INACTIVE'] : $_ARRAYLANG['TXT_CALENDAR_ACTIVE'],
                $this->moduleLangVar.'_CATEGORY_SORT'    => $objCategory->pos,
                $this->moduleLangVar.'_CATEGORY_TITLE'   => $objCategory->name,
                $this->moduleLangVar.'_CATEGORY_EVENTS'  => $objCategory->countEntries(true),
            ));

            $i++;
            $objTpl->parse('categoryList');
        }

        if(count($this->categoryList) == 0) {
            $objTpl->hideBlock('categoryList');

            $objTpl->setVariable(array(
                'TXT_'.$this->moduleLangVar.'_NO_CATEGORIES_FOUND' => $_ARRAYLANG['TXT_CALENDAR_NO_CATEGORIES_FOUND'],
            ));

            $objTpl->parse('emptyCategoryList');
        }
    }

    /**
     * Return the options for any Category menu
     * @global  array   $_ARRAYLANG
     * @param   array   $selected_ids   The IDs to be preselected.
     *                                  Note that the array may be empty
     * @param   string  $type           The options type
     * @return  string                  The HTML options
     * @author  Reto Kohli <reto.kohli@comvation.com>
     *          - Add class constants for option types
     *          - Use \Html::getOptions() in order to handle multiselect
     */
    function getCategoryDropdown($selected_ids,
        $type=self::DROPDOWN_TYPE_DEFAULT)
    {
        global $_ARRAYLANG;
        if (!is_array($selected_ids)) {
            $selected_ids = array();
        }
        $this->getSettings();
        $arrOptions = array();
        foreach ($this->categoryList as $objCategory) {
            $arrOptions[$objCategory->id] = $objCategory->name
                . ($this->arrSettings['countCategoryEntries'] != 2
                    ? ' ('.$objCategory->countEntries(false, true).')' : '');
        }
        $options = ''; // Default case: prepend nothing
        switch ($type) {
            case static::DROPDOWN_TYPE_FILTER:
                $options = "<option value=''>"
                    . $_ARRAYLANG['TXT_CALENDAR_ALL_CAT'] . "</option>";
                break;
            case static::DROPDOWN_TYPE_ASSIGN:
                $options = "<option value=''>"
                    . $_ARRAYLANG['TXT_CALENDAR_PLEASE_CHOOSE'] . "</option>";
                break;
        }
        return $options . \Html::getOptions($arrOptions, $selected_ids);
    }

}
