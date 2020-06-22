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
 * CSV Import
 * @author Cloudrexx Development Team <info@cloudrexx.com>
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @package     cloudrexx
 * @subpackage  module_shop
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Modules\Shop\Controller;

/**
 * CSV Import
 * @author Cloudrexx Development Team <info@cloudrexx.com>
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @package     cloudrexx
 * @subpackage  module_shop
 * @todo        Edit PHP DocBlocks!
 */
class CsvImport
{
    static private $separator = ';';
    static private $delimiter = '"';
    static private $escapor   = '"';

    private $arrTemplateArray = false;
    private $arrName2Fieldname = false;


    function __construct()
    {
        global $_ARRAYLANG;

        $this->arrName2Fieldname = array(
// TODO: Optional.  Include it?
//            $_ARRAYLANG['TXT_SHOP_ID'] => 'id',
            $_ARRAYLANG['TXT_SHOP_PRODUCT_CUSTOM_ID'] => 'code',
            $_ARRAYLANG['TXT_SHOP_IMAGE'] => 'pictures',
            $_ARRAYLANG['TXT_PRODUCT_NAME'] => 'name',
            $_ARRAYLANG['TXT_DISTRIBUTION'] => 'distribution',
            $_ARRAYLANG['TXT_CUSTOMER_PRICE'] => 'price',
            $_ARRAYLANG['TXT_RESELLER_PRICE'] => 'resellerprice',
            $_ARRAYLANG['TXT_SHOP_PRICE_SPECIAL_OFFER'] => 'discountprice',
            $_ARRAYLANG['TXT_SPECIAL_OFFER'] => 'discount_active',
            $_ARRAYLANG['TXT_SHORT_DESCRIPTION'] => 'short',
            $_ARRAYLANG['TXT_DESCRIPTION'] => 'long',
            $_ARRAYLANG['TXT_STOCK'] => 'stock',
            $_ARRAYLANG['TXT_B2B'] => 'b2b',
            $_ARRAYLANG['TXT_B2C'] => 'b2c',
            $_ARRAYLANG['TXT_WEIGHT'] => 'weight',
        );
        $this->initTemplateArray();
    }


    function getTemplateArray()
    {
        return $this->arrTemplateArray;
    }


    /**
     * Returns an array of template names, indexed by respective IDs
     * @return  array           The array of template names
     * @author  Reto Kohli <reto.kohli@comvation.com>
     * @since   3.0.0
     */
    function getNameArray()
    {
        $arrName = array();
        foreach ($this->arrTemplateArray as $arrTemplate) {
            $arrName[$arrTemplate['id']] = $arrTemplate['name'];
        }
        return $arrName;
    }


    function initTemplateArray()
    {
        global $objDatabase;

        $query = "
            SELECT img_id, img_name, img_cats, img_fields_file, img_fields_db
              FROM ".DBPREFIX."module_shop".MODULE_INDEX."_importimg
             ORDER BY img_id";
        $objResult = $objDatabase->Execute($query);
        $this->arrTemplateArray = array();
        while ($objResult && !$objResult->EOF) {
            $this->arrTemplateArray[] = array(
                'id' => $objResult->fields['img_id'],
                'name' => $objResult->fields['img_name'],
                'cat' => $objResult->fields['img_cats'],
                'fields_file' => $objResult->fields['img_fields_file'],
                'fields_db' => $objResult->fields['img_fields_db'],
            );
            $objResult->MoveNext();
        }
    }


    function GetImgListDelete($DeleteText)
    {
        $content = '';
        for ($x = 0; $x < count($this->arrTemplateArray); ++$x) {
            $content .=
                $this->arrTemplateArray[$x]['name'].
                '<a href="javascript:DeleteImg('.
                $this->arrTemplateArray[$x]['id'].');">'.
                $DeleteText.'</a><br />';
        }
        return $content;
    }

    /**
     * Get the file field menu options
     *
     * @param string $file file name with path
     *
     * @return string
     */
    function getFilefieldMenuOptions($file)
    {
        $csv_source = new CsvBv(
            $file,
            CsvImport::$separator, CsvImport::$delimiter, CsvImport::$escapor
        );
        $csv_source->SkipEmptyRows(true);
        $csv_source->TrimFields(true);
        $arrFileContent = $csv_source->csv2Array();
        $strOptions = '';
        for ($x = 0; $x < count($arrFileContent[0]); ++$x) {
            $strOptions .=
                "<option name='$x'>".
                $arrFileContent[0][$x].
                "</option>\n";
        }
        return $strOptions;
    }


    /**
     * Return the menu options of available names that can be assigned
     * to the fields of the file to be imported.
     * @return  array           The available names
     */
    function getAvailableNamesMenuOptions()
    {
        if (empty ($this->arrName2Fieldname)) {
            return '';
        }
        $strOptions = '';
        foreach (array_keys($this->arrName2Fieldname) as $name) {
            $strOptions .= "<option value=\"$name\">$name</option>\n";
        }
        return $strOptions;
    }

    /**
     * Get the file content from the csv file
     *
     * @param string $file file name with path
     *
     * @return array
     */
    function GetFileContent($file)
    {
        $csv_source = new CsvBv(
            $file,
            CsvImport::$separator, CsvImport::$delimiter, CsvImport::$escapor
        );
        $csv_source->SkipEmptyRows(true);
        $csv_source->TrimFields(true);
        $FileContent = $csv_source->csv2Array();
        return $FileContent;
    }


    function GetImageChoice()
    {
        $content = '<select name="ImportImage">';
        for ($x = 0; $x < count($this->arrTemplateArray); ++$x) {
            $content .=
                '<option value="'.$this->arrTemplateArray[$x]['id'].'">'.
                $this->arrTemplateArray[$x]['name'].'</option>';
        }
        $content .= '</select>';
        return $content;
    }


    function DBfieldsName($name='')
    {
        if (empty($name)) {
            return $this->arrName2Fieldname;
        }
        return $this->arrName2Fieldname[$name];
    }


    /**
     * Returns the ID of the ShopCategory with the given name and
     * parent ID, if present.
     *
     * If the ShopCategory cannot be found, a new ShopCategory
     * with the given name is inserted and its ID returned.
     * @static
     * @param   string      $catName    The ShopCategory name
     * @param   integer     $catParent  The optional parent ShopCategory ID,
     *                                  or null to ignore it (default)
     * @return  integer                 The ID of the ShopCategory,
     *                                  or 0 on failure.
     * @author  Unknown <info@cloudrexx.com> (Original author)
     * @author  Reto Kohli <reto.kohli@comvation.com> (Made static)
     */
    static function getCategoryId($catName, $catParent=null)
    {
        $objCategory = ShopCategories::getChildNamed($catName, $catParent);
        if ($objCategory) {
            return $objCategory->id();
        }
        return CsvImport::InsertNewCat($catName, $catParent);
    }


    /**
     * Returns the ID of the first ShopCategory found in the database.
     *
     * If none is available, a default ShopCateogry named 'Import'
     * is inserted and its ID returned instead.
     * @static
     * @return  integer     The ShopCategory, or 0 on failure
     * @author  Unknown <info@cloudrexx.com> (Original author)
     * @author  Reto Kohli <reto.kohli@comvation.com> (Added creation of default ShopCategory, made static)
     */
    static function GetFirstCat()
    {
        $category_id = ShopCategory::getNextShopCategoryId();
        if ($category_id) return $category_id;
        return CsvImport::InsertNewCat('Import', 0);
    }


    /**
     * Insert a new ShopCategory into the database.
     *
     * @static
     * @param   string      $catName    The new ShopCategory name
     * @param   integer     $catParent  The parent ShopCategory ID
     * @return  integer                 The ID of the new ShopCategory,
     *                                  or 0 on failure.
     * @author  Unknown <info@cloudrexx.com> (Original author)
     * @author  Reto Kohli <reto.kohli@comvation.com> (Made static)
     */
    static function InsertNewCat($catName, $catParent)
    {
        $objCategory = new ShopCategory($catName, '', '', $catParent);
        if ($objCategory->store()) {
            return $objCategory->id();
        }
        return 0;
    }

}
