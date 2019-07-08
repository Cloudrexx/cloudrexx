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
 * Shop Manager
 *
 * Administration of the Shop
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @author      Ivan Schmid <ivan.schmid@comvation.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  module_shop
 */

namespace Cx\Modules\Shop\Controller;

/**
 * Administration of the Shop
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @author      Ivan Schmid <ivan.schmid@comvation.com>
 * @access      public
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     3.0.0
 */
class ShopManager extends ShopLibrary
{
    /**
     * The Template object
     * @var   \Cx\Core\Html\Sigma
     */
    private static $objTemplate;
    private static $pageTitle = '';
    private static $defaultImage = '';

    private $act = '';

    /**
     * Constructor
     * @access  public
     * @return  shopmanager
     */
    function __construct()
    {
        global $_ARRAYLANG, $objTemplate;

        \Cx\Core\Setting\Controller\Setting::init('Shop', 'config');

        $this->checkProfileAttributes();
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        self::$defaultImage = file_exists($cx->getWebsiteImagesShopPath() . '/' . ShopLibrary::noPictureName) ?
                                $cx->getWebsiteImagesShopWebPath() . '/' . ShopLibrary::noPictureName :
                                $cx->getCodeBaseOffsetPath(). '/images/Shop/' . ShopLibrary::noPictureName;
        self::$objTemplate = new \Cx\Core\Html\Sigma($cx->getCodeBaseModulePath() . '/Shop/View/Template/Backend');
        self::$objTemplate->setErrorHandling(PEAR_ERROR_DIE);
//DBG::log("ARRAYLANG: ".var_export($_ARRAYLANG, true));
        self::$objTemplate->setGlobalVariable(
            $_ARRAYLANG
          + array(
            'SHOP_CURRENCY' => Currency::getActiveCurrencySymbol(),
            'CSRF_PARAM' => \Cx\Core\Csrf\Controller\Csrf::param()
        ));
    }

    protected function checkProfileAttributes() {
        $objUser = \FWUser::getFWUserObject()->objUser;

        $index_notes = \Cx\Core\Setting\Controller\Setting::getValue('user_profile_attribute_notes','Shop');
        if ($index_notes) {
            $objProfileAttribute = $objUser->objAttribute->getById($index_notes);
            $attributeNames = $objProfileAttribute->getAttributeNames($index_notes);
            if (empty($attributeNames)) {
                $index_notes = false;
            }
        }
        if (!$index_notes) {
//DBG::log("Customer::errorHandler(): Adding notes attribute...");
//            $objProfileAttribute = new User_Profile_Attribute();
            $objProfileAttribute = $objUser->objAttribute->getById(0);
//DBG::log("Customer::errorHandler(): NEW notes attribute: ".var_export($objProfileAttribute, true));
            $objProfileAttribute->setNames(array(
                1 => 'Notizen',
                2 => 'Notes',
// TODO: Translate
                3 => 'Notes', 4 => 'Notes', 5 => 'Notes', 6 => 'Notes',
            ));
            $objProfileAttribute->setType('text');
            $objProfileAttribute->setMultiline(true);
            $objProfileAttribute->setParent(0);
            $objProfileAttribute->setProtection(array(1));
//DBG::log("Customer::errorHandler(): Made notes attribute: ".var_export($objProfileAttribute, true));
            if (!$objProfileAttribute->store()) {
                throw new \Cx\Lib\Update_DatabaseException(
                    "Failed to create User_Profile_Attribute 'notes'");
            }

            //Re initialize shop setting
            \Cx\Core\Setting\Controller\Setting::init('Shop', 'config');
//DBG::log("Customer::errorHandler(): Stored notes attribute, ID ".$objProfileAttribute->getId());
            if (!(\Cx\Core\Setting\Controller\Setting::set('user_profile_attribute_notes', $objProfileAttribute->getId())
                && \Cx\Core\Setting\Controller\Setting::update('user_profile_attribute_notes'))) {
                throw new \Cx\Lib\Update_DatabaseException(
                    "Failed to update User_Profile_Attribute 'notes' setting");
            }
//DBG::log("Customer::errorHandler(): Stored notes attribute ID setting");
        }

        $index_group = \Cx\Core\Setting\Controller\Setting::getValue('user_profile_attribute_customer_group_id','Shop');
        if ($index_group) {
            $objProfileAttribute = $objUser->objAttribute->getById($index_notes);
            $attributeNames = $objProfileAttribute->getAttributeNames($index_group);
            if (empty($attributeNames)) {
                $index_group = false;
            }
        }
        if (!$index_group) {
//            $objProfileAttribute = new User_Profile_Attribute();
            $objProfileAttribute = $objUser->objAttribute->getById(0);
            $objProfileAttribute->setNames(array(
                1 => 'Kundenrabattgruppe',
                2 => 'Discount group',
// TODO: Translate
                3 => 'Kundenrabattgruppe', 4 => 'Kundenrabattgruppe',
                5 => 'Kundenrabattgruppe', 6 => 'Kundenrabattgruppe',
            ));
            $objProfileAttribute->setType('text');
            $objProfileAttribute->setParent(0);
            $objProfileAttribute->setProtection(array(1));
            if (!$objProfileAttribute->store()) {
                throw new \Cx\Lib\Update_DatabaseException(
                    "Failed to create User_Profile_Attribute 'notes'");
            }

            //Re initialize shop setting
            \Cx\Core\Setting\Controller\Setting::init('Shop', 'config');
            if (!(\Cx\Core\Setting\Controller\Setting::set('user_profile_attribute_customer_group_id', $objProfileAttribute->getId())
                && \Cx\Core\Setting\Controller\Setting::update('user_profile_attribute_customer_group_id'))) {
                throw new \Cx\Lib\Update_DatabaseException(
                    "Failed to update User_Profile_Attribute 'customer_group_id' setting");
            }
        }
    }


    private function setNavigation()
    {
        global $objTemplate, $_ARRAYLANG;

        $objTemplate->setVariable('CONTENT_NAVIGATION',
            "<a href='index.php?cmd=Shop".MODULE_INDEX."' class='".($this->act == '' ? 'active' : '')."'>".$_ARRAYLANG['TXT_ORDERS']."</a>".
            "<a href='index.php?cmd=Shop".MODULE_INDEX."&amp;act=categories' class='".($this->act == 'categories' ? 'active' : '')."'>".$_ARRAYLANG['TXT_CATEGORIES']."</a>".
            "<a href='index.php?cmd=Shop".MODULE_INDEX."&amp;act=products' class='".($this->act == 'products' ? 'active' : '')."'>".$_ARRAYLANG['TXT_PRODUCTS']."</a>".
            "<a href='index.php?cmd=Shop".MODULE_INDEX."&amp;act=manufacturer' class='".($this->act == 'manufacturer' ? 'active' : '')."'>".$_ARRAYLANG['TXT_SHOP_MANUFACTURER']."</a>".
            "<a href='index.php?cmd=Shop".MODULE_INDEX."&amp;act=customers' class='".($this->act == 'customers' ? 'active' : '')."'>".$_ARRAYLANG['TXT_CUSTOMERS_PARTNERS']."</a>".
            "<a href='index.php?cmd=Shop".MODULE_INDEX."&amp;act=statistics' class='".($this->act == 'statistics' ? 'active' : '')."'>".$_ARRAYLANG['TXT_STATISTIC']."</a>".
            "<a href='index.php?cmd=Shop".MODULE_INDEX."&amp;act=import' class='".($this->act == 'import' ? 'active' : '')."'>".$_ARRAYLANG['TXT_IMPORT_EXPORT']."</a>".
//            "<a href='index.php?cmd=Shop".MODULE_INDEX."&amp;act=pricelists' class='".($this->act == 'pricelists' ? 'active' : '')."'>".$_ARRAYLANG['TXT_PDF_OVERVIEW']."</a>".
            "<a href='index.php?cmd=Shop".MODULE_INDEX."&amp;act=settings' class='".($this->act == 'settings' ? 'active' : '')."'>".$_ARRAYLANG['TXT_SETTINGS']."</a>"
// TODO: Workaround for the language selection.  Remove when the new UI
// is introduced in the shop.
//            .
//            '<div style="float: right;">'.
//            $objInit->getUserFrontendLangMenu()
        );
    }


    /**
     * Set up the shop admin page
     */
    function getPage()
    {
        global $objTemplate, $_ARRAYLANG;

//\DBG::activate(DBG_ERROR_FIREPHP|DBG_LOG);
        if (!isset($_GET['act'])) {
            $_GET['act'] = '';
        }
        switch ($_GET['act']) {
            case 'mailtemplate_overview':
            case 'mailtemplate_edit':
                $_GET['tpl'] = 'mail';
                // No break on purpose
            case 'settings':
                $this->view_settings();
                break;
            case 'categories':
            case 'category_edit':
                // Includes PDF pricelists
                $this->view_categories();
                break;
            case 'products':
            case 'activate_products':
            case 'deactivate_products':
                $this->view_products();
                break;
            case 'delProduct':
            case 'deleteProduct':
                self::$pageTitle = $_ARRAYLANG['TXT_PRODUCT_CATALOG'];
                $this->delete_product();
                $this->view_products();
                break;
            case 'orders':
                $this->view_order_overview();
                break;
            case 'orderdetails':
                $this->view_order_details();
                break;
            case 'editorder':
                $this->view_order_details(true);
                break;
            case 'delorder':
                // Redirects back to Order overview
                $this->delete_order();
                break;
            case 'delcustomer':
                $this->delete_customer();
                $this->view_customers();
                break;
            case 'customer_activate':
            case 'customer_deactivate':
                $this->customer_activate();
                $this->view_customers();
                break;
            case 'customers':
                self::$pageTitle = $_ARRAYLANG['TXT_CUSTOMERS_PARTNERS'];
                $this->view_customers();
                break;
            case 'customerdetails':
                self::$pageTitle = $_ARRAYLANG['TXT_CUSTOMER_DETAILS'];
                $this->view_customer_details();
                break;
            case 'neweditcustomer':
                $this->view_customer_edit();
                break;
            case 'statistics':
                self::$pageTitle = $_ARRAYLANG['TXT_STATISTIC'];
                Orders::view_statistics(self::$objTemplate);
                break;
            case 'import':
                $this->_import();
                break;
            case 'manufacturer':
                $this->view_manufacturers();
                break;
            default:
                $this->view_order_overview();
                break;
        }
        \Message::show();
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder(self::$objTemplate);
        $objTemplate->setVariable(array(
            'CONTENT_TITLE' => self::$pageTitle,
            'ADMIN_CONTENT' => self::$objTemplate->get(),
        ));
        $this->act = (isset ($_REQUEST['act']) ? $_REQUEST['act'] : '');
        $this->setNavigation();
    }


    /**
     * Manages manufacturers
     */
    function view_manufacturers()
    {
        global $_ARRAYLANG;

        self::update_manufacturers();

        self::$pageTitle = $_ARRAYLANG['TXT_SHOP_MANUFACTURER'];
        self::$objTemplate->loadTemplateFile('module_shop_manufacturer.html');
        self::$objTemplate->setGlobalVariable($_ARRAYLANG);

        $uri = \Html::getRelativeUri();
        \Html::stripUriParam($uri, 'delete');
        $arrSorting = array(
          'id' => $_ARRAYLANG['TXT_SHOP_MANUFACTURER_ID'],
          'name' => $_ARRAYLANG['TXT_SHOP_MANUFACTURER_NAME'],
          'url' => $_ARRAYLANG['TXT_SHOP_MANUFACTURER_URL'],
        );
        $objSorting = new \Sorting($uri, $arrSorting, true, 'order_manufacturer');
        self::$objTemplate->setVariable(array(
            'SHOP_HEADER_ID' => $objSorting->getHeaderForField('id'),
            'SHOP_HEADER_NAME' => $objSorting->getHeaderForField('name'),
            'SHOP_HEADER_URL' => $objSorting->getHeaderForField('url'),
        ));

        $count = 0;
// TODO: Implement the filter in the Manufacturer class
        $filter = null;
        $limit = \Cx\Core\Setting\Controller\Setting::getValue('numof_manufacturers_per_page_backend','Shop');
        $arrManufacturers = Manufacturer::getArray($count,
            $objSorting->getOrder(), \Paging::getPosition(), $limit, $filter);
        $i = 0;
        foreach ($arrManufacturers as $manufacturer_id => $arrManufacturer) {
            self::$objTemplate->setVariable(array(
                'SHOP_MANUFACTURER_ID' => $manufacturer_id,
                'SHOP_MANUFACTURER_NAME' => $arrManufacturer['name'],
                'SHOP_MANUFACTURER_URL' => $arrManufacturer['url'],
                'SHOP_ROWCLASS' => 'row'.(++$i % 2 + 1),
            ));
            self::$objTemplate->parse("manufacturerRow");
        }
        $manufacturer_id = (!empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0);
        $name = $url = '';
        if (isset($arrManufacturers[$manufacturer_id])) {
            $name = $arrManufacturers[$manufacturer_id]['name'];
            $url = $arrManufacturers[$manufacturer_id]['url'];
        }
        if (!empty($_POST['name'])) $name = contrexx_input2raw($_POST['name']);
        if (!empty($_POST['url'])) $url = contrexx_input2raw($_REQUEST['url']);

        $currentUrl = clone \Env::get('Resolver')->getUrl();
        self::$objTemplate->setVariable(array(
            'SHOP_MANUFACTURER_PAGING' => \Paging::get($currentUrl, $_ARRAYLANG['TXT_SHOP_MANUFACTURER'], $count, $limit),
            'SHOP_EDIT_MANUFACTURER' => ($manufacturer_id
                ? $_ARRAYLANG['TXT_SHOP_MANUFACTURER_EDIT']
                : $_ARRAYLANG['TXT_SHOP_MANUFACTURER_ADD']),
            'SHOP_MANUFACTURER_NAME' => $name,
            'SHOP_MANUFACTURER_URL' => $url,
            'SHOP_MANUFACTURER_ID' => $manufacturer_id,
        ));
    }


    /**
     * Updates the Manufacturers in the database
     *
     * Stores or deletes records depending on the contents of the
     * current request
     * @return  boolean           True on success, null on noop, false otherwise
     */
    static function update_manufacturers()
    {
        global $_ARRAYLANG;

        // Delete any single manufacturer, if requested to
        if (!empty($_GET['delete'])) {
            $manufacturer_id = intval($_GET['delete']);
            return Manufacturer::delete($manufacturer_id);
        }
        // Multiaction: Only deleting implemented
        if (   !empty($_POST['multi_action'])
            && !empty($_POST['selected_manufacturer_id'])
            && is_array($_POST['selected_manufacturer_id'])) {
            switch ($_POST['multi_action']) {
              case 'delete':
                // Delete multiple selected manufacturers
                return Manufacturer::delete($_POST['selected_manufacturer_id']);
            }
        }
        if (!isset($_POST['bstore'])) return null;
        if (empty($_POST['name'])) {
            return \Message::error($_ARRAYLANG['TXT_SHOP_MANUFACTURER_ERROR_EMPTY_NAME']);
        }
        $manufacturer_id = (empty($_POST['id']) ? null : intval($_POST['id']));
        $name = (empty($_POST['name']) ? '' : contrexx_input2raw($_POST['name']));
        $url = (empty($_REQUEST['url']) ? '' : contrexx_input2raw($_REQUEST['url']));
//DBG::log("ShopManager::update_manufacturers(): Storing Manufacturer: $name, $url, $manufacturer_id");
        $result = Manufacturer::store($name, $url, $manufacturer_id);
        if ($result) {
            // Do not set up the same Manufacturer for editing again after
            // storing it successfully
            $_REQUEST['id'] = $_POST['name'] = $_POST['url'] = null;
            Manufacturer::flush();
        }
        return $result;
    }


    /**
     * Import and Export data from/to csv
     * @author  Reto Kohli <reto.kohli@comvation.com> (parts)
     */
    function _import()
    {
        global $_ARRAYLANG, $objDatabase;

        self::$pageTitle = $_ARRAYLANG['TXT_SHOP_IMPORT_TITLE'];
        self::$objTemplate->loadTemplateFile('module_shop_import.html');
        self::$objTemplate->setGlobalVariable(array(
            'TXT_SHOP_IMPORT_CATEGORIES_TIPS' =>
                contrexx_raw2xhtml($_ARRAYLANG['TXT_SHOP_IMPORT_CATEGORIES_TIPS']),
            'TXT_SHOP_IMPORT_CHOOSE_TEMPLATE_TIPS' =>
                contrexx_raw2xhtml($_ARRAYLANG['TXT_SHOP_IMPORT_CHOOSE_TEMPLATE_TIPS']),
        ));
        $objCSVimport = new CsvImport();
        // Delete template
        if (isset($_REQUEST['deleteImg'])) {
            $query = "
                DELETE FROM ".DBPREFIX."module_shop".MODULE_INDEX."_importimg
                 WHERE img_id=".$_REQUEST['img'];
            if ($objDatabase->Execute($query)) {
                \Message::ok($_ARRAYLANG['TXT_SHOP_IMPORT_SUCCESSFULLY_DELETED']);
            } else {
                \Message::error($_ARRAYLANG['TXT_SHOP_IMPORT_ERROR_DELETE']);
            }
        }
        // Save template
        if (isset($_REQUEST['SaveImg'])) {
            $query = "
                INSERT INTO ".DBPREFIX."module_shop".MODULE_INDEX."_importimg (
                    img_name, img_cats, img_fields_file, img_fields_db
                ) VALUES (
                    '".$_REQUEST['ImgName']."',
                    '".$_REQUEST['category']."',
                    '".$_REQUEST['pairs_left_keys']."',
                    '".$_REQUEST['pairs_right_keys']."'
                )";
            if ($objDatabase->Execute($query)) {
                \Message::ok($_ARRAYLANG['TXT_SHOP_IMPORT_SUCCESSFULLY_SAVED']);
            } else {
                \Message::error($_ARRAYLANG['TXT_SHOP_IMPORT_ERROR_SAVE']);
            }
        }
        $objCSVimport->initTemplateArray();
        $fileExists = false;
        $fileName   = isset($_POST['csvFile'])
                        ? contrexx_input2raw($_POST['csvFile'])
                        : '';
        $uploaderId = isset($_POST['importCsvUploaderId'])
                        ? contrexx_input2raw($_POST['importCsvUploaderId'])
                        : '';
        if (!empty($fileName) && !empty($uploaderId)) {
            $cx  = \Cx\Core\Core\Controller\Cx::instanciate();
            $objSession = $cx->getComponent('Session')->getSession();
            $tmpFile    = $objSession->getTempPath() . '/' . $uploaderId . '/' . $fileName;
            $fileExists = \Cx\Lib\FileSystem\FileSystem::exists($tmpFile);
        }
        // Import Categories
        // This is not subject to change, so it's hardcoded
        if (isset($_REQUEST['ImportCategories']) && $fileExists) {
            // delete existing categories on request only!
            // mind that this necessarily also clears all products and
            // their associated attributes!
            if (!empty($_POST['clearCategories'])) {
                Products::deleteByShopCategory(0, false, true);
                ShopCategories::deleteAll();
// NOTE: Removing Attributes is now disabled.  Optionally enable this.
//                Attributes::deleteAll();
            }
            $objCsv = new CsvBv($tmpFile);
            $importedLines = 0;
            $arrCategoryLevel = array(0,0,0,0,0,0,0,0,0,0);
            $line = $objCsv->NextLine();
            while ($line) {
                $level = 0;
                foreach ($line as $catName) {
                    ++$level;
                    if (!empty($catName)) {
                        $parentCatId = $objCSVimport->getCategoryId(
                            $catName,
                            $arrCategoryLevel[$level-1]
                        );
                        $arrCategoryLevel[$level] = $parentCatId;
                    }
                }
                ++$importedLines;
                $line = $objCsv->NextLine();
            }
            \Message::ok($_ARRAYLANG['TXT_SHOP_IMPORT_SUCCESSFULLY_IMPORTED_CATEGORIES'].
                ': '.$importedLines);
        }
        // Import
        if (isset($_REQUEST['importFileProducts']) && $fileExists) {
            if (isset($_POST['clearProducts']) && $_POST['clearProducts']) {
                Products::deleteByShopCategory(0, false, true);
                // The categories need not be removed, but it is done by design!
                ShopCategories::deleteAll();
// NOTE: Removing Attributes is now disabled.  Optionally enable this.
//                Attributes::deleteAll();
            }
            $arrFileContent = $objCSVimport->GetFileContent($tmpFile);
            $query = '
                SELECT img_id, img_name, img_cats, img_fields_file, img_fields_db
                  FROM '.DBPREFIX.'module_shop'.MODULE_INDEX.'_importimg
                 WHERE img_id='.$_REQUEST['ImportImage'];
            $objResult = $objDatabase->Execute($query);

            $arrCategoryName = preg_split(
                '/;/', $objResult->fields['img_cats'], null, PREG_SPLIT_NO_EMPTY
            );
            $arrFirstLine = $arrFileContent[0];
            $arrCategoryColumnIndex = array();
            for ($x=0; $x < count($arrCategoryName); ++$x) {
                foreach ($arrFirstLine as $index => $strColumnName) {
                    if ($strColumnName == $arrCategoryName[$x]) {
                        $arrCategoryColumnIndex[] = $index;
                    }
                }
            }
            $arrTemplateFieldName = preg_split(
                '/;/', $objResult->fields['img_fields_file'],
                null, PREG_SPLIT_NO_EMPTY
            );
            $arrDatabaseFieldIndex = array();
            for ($x=0; $x < count($arrTemplateFieldName); ++$x) {
                foreach ($arrFirstLine as $index => $strColumnName) {
                    if ($strColumnName == $arrTemplateFieldName[$x]) {
                        $arrDatabaseFieldIndex[] = $index;
                    }
                }
            }
            $arrProductFieldName = preg_split(
                '/;/', $objResult->fields['img_fields_db'],
                null, PREG_SPLIT_NO_EMPTY
            );
            $arrProductDatabaseFieldName = array();
            for ($x = 0; $x < count($arrProductFieldName); ++$x) {
                $dbname = $objCSVimport->DBfieldsName($arrProductFieldName[$x]);
                $arrProductDatabaseFieldName[$dbname] =
                    (isset($arrProductDatabaseFieldName[$dbname])
                        ? $arrProductDatabaseFieldName[$dbname].';'
                        : '').
                    $x;
            }
            $importedLines = 0;
            $errorLines = 0;
            // Array of IDs of newly inserted records
            $arrId = array();
            for ($x = 1; $x < count($arrFileContent); ++$x) {
                $category_id = false;
                for ($cat = 0; $cat < count($arrCategoryColumnIndex); ++$cat) {
                    $catName = $arrFileContent[$x][$arrCategoryColumnIndex[$cat]];
                    if (empty($catName) && !empty($category_id)) {
                        break;
                    }
                    if (empty($catName)) {
                        $category_id = $objCSVimport->GetFirstCat();
                    } else {
                        $category_id = $objCSVimport->getCategoryId($catName, $category_id);
                    }
                }
                if ($category_id == 0) {
                    $category_id = $objCSVimport->GetFirstCat();
                }
                $objProduct = new Product('', $category_id, '',
                    Distribution::TYPE_DELIVERY, 0, 1, 0, 0, 0);
                foreach ($arrProductDatabaseFieldName as $index => $strFieldIndex) {
                    $value = '';
                    if (strpos($strFieldIndex, ';')) {
                        $prod2line = explode(';', $strFieldIndex);
                        for ($z = 0; $z < count($prod2line); ++$z) {
                            $value .=
                                $arrFileContent[$x][$arrDatabaseFieldIndex[$prod2line[$z]]].
                                '<br />';
                        }
                    } else {
                        $value =
                            $arrFileContent[$x][$arrDatabaseFieldIndex[$strFieldIndex]];
                    }
                    $objProduct->$index($value);
                }
                if ($objProduct->store()) {
                    $arrId[] = $objProduct->id();
                    ++$importedLines;
                } else {
                    ++$errorLines;
                }
            }
            // Fix picture field and create thumbnails in case the import
            // contains images
            if (in_array('pictures', $arrProductFieldName)) {
                Products::makeThumbnailsById($arrId);
            }
            if ($importedLines) {
                \Message::ok($_ARRAYLANG['TXT_SHOP_IMPORT_SUCCESSFULLY_IMPORTED_PRODUCTS'].
                    ': '.$importedLines);
            }
            if ($errorLines) {
                \Message::error($_ARRAYLANG['TXT_SHOP_IMPORT_NOT_SUCCESSFULLY_IMPORTED_PRODUCTS'].': '.$errorLines);
            }
        } // end import
        $jsnofiles = '';
        $fileFields = $dblist = null;
        $arrTemplateArray = $objCSVimport->getTemplateArray();
        if (isset($_REQUEST['mode']) && $_REQUEST['mode'] != 'ImportImg') {
            if (count($arrTemplateArray) == 0) {
                self::$objTemplate->hideBlock('import_products');
                self::$objTemplate->touchBlock('import_products_no_template');
            } else {
                $imageChoice = $objCSVimport->GetImageChoice();
                self::$objTemplate->setVariable(array(
                    'IMAGE_CHOICE' => $imageChoice,
                ));
            }
        } else {
            if (!isset($_REQUEST['SelectFields'])) {
                $jsnofiles = "selectTab('import1');";
            } else {
                if (isset($_POST['mode']) && $_POST['csvFile'] == '') {
                    $jsnofiles = "selectTab('import4');";
                } else {
                    $jsnofiles = "selectTab('import2');";
                    if ($fileExists) {
                        $fileFields = '
                            <select name="FileFields" id="file_field" style="width: 200px;" size="10">
                                '.$objCSVimport->getFilefieldMenuOptions($tmpFile).'
                            </select>'."\n";
                    }
                    $dblist = '
                        <select name="DbFields" id="given_field" style="width: 200px;" size="10">
                            '.$objCSVimport->getAvailableNamesMenuOptions().'
                        </select>'."\n";
                }
            }
        }
        $jsSelectLayer = 'selectTab("import1");';
        if (isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'ImportImg') {
            $jsSelectLayer = 'selectTab("import2");';
        }
        $arrTemplateArray = $objCSVimport->getTemplateArray();
        if ($arrTemplateArray) {
            $arrName = $objCSVimport->getNameArray();
            self::$objTemplate->setVariable(
                'SHOP_IMPORT_TEMPLATE_MENU', \Html::getSelect(
                    'ImportImage', $arrName));
        } else {
            self::$objTemplate->touchBlock('import_products_no_template');
        }
        for ($x = 0; $x < count($arrTemplateArray); ++$x) {
            self::$objTemplate->setVariable(array(
                'IMG_NAME' => $arrTemplateArray[$x]['name'],
                'IMG_ID' => $arrTemplateArray[$x]['id'],
                'CLASS_NAME' => 'row'.($x % 2 + 1),
                // cms offset fix for admin images/icons:
                'SHOP_CMS_OFFSET' => \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteOffsetPath(),
            ));
            self::$objTemplate->parse('imgRow');
        }
        //initialize the uploader
        $uploader = new \Cx\Core_Modules\Uploader\Model\Entity\Uploader(); //create an uploader
        $uploader->setCallback('importUploaderCallback');
        $uploader->setOptions(array(
                    'id'                 => 'importCsvUploader',
                    'allowed-extensions' => array('csv', 'xls'),
                    'data-upload-limit'  => 1,
                    'style' => 'display:none'
        ));

        self::$objTemplate->setVariable(array(
            'SELECT_LAYER_ONLOAD' => $jsSelectLayer,
            'NO_FILES' => (isset($jsnofiles)  ? $jsnofiles  : ''),
            'FILE_FIELDS_LIST' => (isset($fileFields) ? $fileFields : ''),
            'DB_FIELDS_LIST' => (isset($dblist) ? $dblist : ''),
            'SHOP_IMPORT_CSV_UPLOADER_CODE' => $uploader->getXHtml(),
            'SHOP_IMPORT_CSV_UPLOADER_ID' => $uploader->getId(),
            // Export: instructions added
//            'SHOP_EXPORT_TIPS' => $tipText,
        ));
// TODO: !!! CSV EXPORT IS OBSOLETE AND DYSFUNCT !!!
/*
        // Export groups -- hardcoded
        $content_location = '';
        if (isset($_REQUEST['group'])) {
            $query = $fieldNames = $content_location = '';
            $arrPictures = null;
            switch ($_REQUEST['group']) {
                // products - plain fields:
                case 'tproduct':
                    $content_location = "ProdukteTabelle.csv";
                    $fieldNames = array(
                        'id', 'product_id', 'picture', 'title', 'catid', 'distribution',
                        'normalprice', 'resellerprice', 'short', 'long',
                        'stock', 'stock_visible', 'discountprice', 'discount_active',
                        'active', 'b2b', 'b2c', 'date_start', 'date_end',
                        'manufacturer', 'manufacturer_url', 'external_link',
                        'ord', 'vat_id', 'weight',
                        'flags', 'group_id', 'article_id', 'keywords', );
                    $query = "
                        SELECT id, product_id, picture, title, catid, distribution,
                               normalprice, resellerprice, short, long,
                               stock, stock_visible, discountprice, discount_active,
                               active, b2b, b2c, date_start, date_end,
                               manufacturer, manufacturer_url, external_link,
                               sort_order, vat_id, weight,
                               flags, group_id, article_id, keywords
                          FROM ".DBPREFIX."module_shop_products
                         ORDER BY id ASC";
                    break;
                // products - custom:
                case 'rproduct':
                    $content_location = "ProdukteRelationen.csv";
                    $fieldNames = array(
                        'id', 'product_id', 'picture', 'title',
                        'catid', 'category', 'parentcategory', 'distribution',
                        'normalprice', 'resellerprice', 'discountprice', 'discount_active',
                        'short', 'long',
                        'stock', 'stock_visible',
                        'active', 'b2b', 'b2c',
                        'date_start', 'date_end',
                        'manufacturer_name', 'manufacturer_website',
                        'manufacturer_url', 'external_link',
                        'ord',
                        'vat_percent', 'weight',
                        'discount_group', 'article_group', 'keywords', );
                    // c1.catid *MUST NOT* be NULL
                    // c2.catid *MAY* be NULL (if c1.catid is root)
                    // vat_id *MAY* be NULL
                    $query = "
                        SELECT p.id, p.product_id, p.picture, p.title,
                               p.catid, c1.catname as category, c2.catname as parentcategory, p.distribution,
                               p.normalprice, p.resellerprice, p.discountprice, p.discount_active,
                               p.short, p.long, p.stock, p.stock_visible,
                               p.active, p.b2b, p.b2c, p.date_start, p.date_end,
                               m.name as manufacturer_name,
                               m.url as manufacturer_website,
                               p.manufacturer_url, p.external_link,
                               p.ord,
                               v.percent as vat_percent, p.weight,
                               d.name AS discount_group,
                               a.name AS article_group,
                               p.keywords
                          FROM ".DBPREFIX."module_shop_products p
                         INNER JOIN ".DBPREFIX."module_shop_categories c1 ON p.catid=c1.catid
                          LEFT JOIN ".DBPREFIX."module_shop_categories c2 ON c1.parentid=c2.catid
                          LEFT JOIN ".DBPREFIX."module_shop_vat v ON vat_id=v.id
                          LEFT JOIN ".DBPREFIX."module_shop_manufacturer as m ON m.id = p.manufacturer
                          LEFT JOIN ".DBPREFIX."module_shop_discountgroup_count_name as d ON d.id = p.group_id
                          LEFT JOIN ".DBPREFIX."module_shop_article_group as a ON a.id = p.article_id
                         ORDER BY catid ASC, product_id ASC";
                    break;
                // customer - plain fields:
// TODO: Use Customer class!
                case 'tcustomer':
                    $content_location = "KundenTabelle.csv";
                    $fieldNames = array(
                        'customerid', 'username', 'password', 'prefix', 'company', 'firstname', 'lastname',
                        'address', 'city', 'zip', 'country_id', 'phone', 'fax', 'email',
                        'ccnumber', 'ccdate', 'ccname', 'cvc_code', 'company_note',
                        'is_reseller', 'register_date', 'customer_status', 'group_id', );
                    $query = "
                        SELECT customerid, username, password, prefix, company, firstname, lastname,
                               address, city, zip, country_id, phone, fax, email,
                               ccnumber, ccdate, ccname, cvc_code, company_note,
                               is_reseller, register_date, customer_status,
                               group_id
                          FROM ".DBPREFIX."module_shop_customers
                         ORDER BY lastname ASC, firstname ASC";
                    break;
                // customer - custom:
// TODO: Use Customer class!
                case 'rcustomer':
                    $content_location = "KundenRelationen.csv";
                    $fieldNames = array(
                        'customerid', 'username', 'firstname', 'lastname', 'prefix', 'company',
                        'address', 'zip', 'city', 'countries_name',
                        'phone', 'fax', 'email', 'is_reseller', 'register_date', 'group_name', );
                    $query = "
                        SELECT c.customerid, c.username, c.firstname, c.lastname, c.prefix, c.company,
                               c.address, c.zip, c.city, n.countries_name,
                               c.phone, c.fax, c.email, c.is_reseller, c.register_date,
                               d.name AS group_name
                          FROM ".DBPREFIX."module_shop_customers c
                         INNER JOIN ".DBPREFIX."module_shop_countries n ON c.country_id=n.countries_id
                          LEFT JOIN ".DBPREFIX."module_shop_customer_group d ON c.group_id=d.id
                         ORDER BY c.lastname ASC, c.firstname ASC";
                    break;
                // orders - plain fields:
                case 'torder':
                    $content_location = "BestellungenTabelle.csv";
                    $fieldNames = array(
                        'id', 'customer_id', 'currency_id', 'order_sum', 'sum',
                        'date_time', 'status', 'ship_prefix', 'ship_company', 'ship_firstname', 'ship_lastname',
                        'ship_address', 'ship_city', 'ship_zip', 'ship_country_id', 'ship_phone',
                        'vat_amount', 'currency_ship_price', 'shipment_id', 'payment_id', 'currency_payment_price',
                        'ip', 'host', 'lang_id', 'browser', 'note',
                        'last_modified', 'modified_by');
                    $query = "
                        SELECT id, customer_id, currency_id, order_sum, sum,
                               date_time, status, ship_prefix, ship_company, ship_firstname, ship_lastname,
                               ship_address, ship_city, ship_zip, ship_country_id, ship_phone,
                               vat_amount, currency_ship_price, shipment_id, payment_id, currency_payment_price,
                               ip, host, lang_id, browser, note,
                               last_modified, modified_by
                          FROM ".DBPREFIX."module_shop".MODULE_INDEX."_orders
                         ORDER BY id ASC";
                    break;
                // orders - custom:
                case 'rorder':
// TODO: Use Customer class!
                    $content_location = "BestellungenRelationen.csv";
                    $fieldNames = array(
                        'id', 'order_sum', 'vat_amount', 'currency_ship_price', 'currency_payment_price',
                        'sum', 'date_time', 'status', 'ship_prefix', 'ship_company',
                        'ship_firstname', 'ship_lastname', 'ship_address', 'ship_city', 'ship_zip',
                        'ship_phone', 'note',
                        'customer_id', 'username', 'firstname', 'lastname', 'prefix', 'company',
                        'address', 'zip', 'city', 'countries_name',
                        'phone', 'fax', 'email', 'is_reseller', 'register_date',
                        'currency_code', 'shipper_name', 'payment_name',
                        'account_number', 'bank_name', 'bank_code');
                    $query = "
                        SELECT o.id, o.order_sum, o.vat_amount, o.currency_ship_price, o.currency_payment_price,
                               o.sum, o.date_time, o.status, o.ship_prefix, o.ship_company,
                               o.ship_firstname, o.ship_lastname, o.ship_address, o.ship_city, o.ship_zip,
                               o.ship_phone, o.note,
                               o.customer_id,
                               c.username, c.firstname, c.lastname, c.prefix, c.company,
                               c.address, c.zip, c.city, n.countries_name,
                               c.phone, c.fax, c.email, c.is_reseller, c.register_date,
                               u.code AS currency_code, s.name AS shipper_name, p.name AS payment_name,
                               l.holder, l.bank, l.blz
                          FROM ".DBPREFIX."module_shop_orders o
                         INNER JOIN ".DBPREFIX."module_shop_customers c ON o.customer_id=c.customerid
                         INNER JOIN ".DBPREFIX."module_shop_countries n ON c.country_id=n.countries_id
                         INNER JOIN ".DBPREFIX."module_shop_currencies u ON o.currency_id=u.id
                          LEFT JOIN ".DBPREFIX."module_shop_shipper s ON o.shipment_id=s.id
                          LEFT JOIN ".DBPREFIX."module_shop_payment p ON o.payment_id=p.id
                          LEFT JOIN ".DBPREFIX."module_shop_lsv l ON o.id=l.order_id
                         ORDER BY o.id ASC";
                    break;
            } // switch

            if ($query && $objResult = $objDatabase->Execute($query)) {
                // field names
                $fileContent = '"'.join('";"', $fieldNames)."\"\n";
                while (!$objResult->EOF) {
                    $arrRow = $objResult->FetchRow();
                    $arrReplaced = array();
                    // Decode the pictures
                    foreach ($arrRow as $index => $field) {
                        if ($index == 'picture') {
                            $arrPictures = Products::get_image_array_from_base64($field);
                            $field =
                                'http://'.
                                $_SERVER['HTTP_HOST'].'/'.
                                ASCMS_SHOP_IMAGES_WEB_PATH.'/'.
                                $arrPictures[1]['img'];
                        }
                        $arrReplaced[] = str_replace('"', '""', $field);
                    }
                    $fileContent .= '"'.join('";"', $arrReplaced)."\"\n";
                }
                // Test the output for UTF8!
                if (strtoupper(CONTREXX_CHARSET) == 'UTF-8') {
                    $fileContent = utf8_decode($fileContent);
                }
// TODO: Add success message?
                // set content to filename and -type for download
                header("Content-Disposition: inline; filename=$content_location");
                header("Content-Type: text/comma-separated-values");
                echo($fileContent);
                exit();
            }
            \Message::error($_ARRAYLANG['TXT_SHOP_EXPORT_ERROR']);
        } else {
            // can't submit without a group selection
        } // if/else group
        // end export

        // make sure that language entries exist for all of
        // TXT_SHOP_EXPORT_GROUP_*, TXT_SHOP_EXPORT_GROUP_*_TIP !!
        $arrGroups = array('tproduct', 'rproduct', 'tcustomer', 'rcustomer', 'torder', 'rorder');
        $tipText = '';
        for ($i = 0; $i < count($arrGroups); ++$i) {
            self::$objTemplate->setCurrentBlock('groupRow');
            self::$objTemplate->setVariable(array(
                'SHOP_EXPORT_GROUP' => $_ARRAYLANG['TXT_SHOP_EXPORT_GROUP_'.strtoupper($arrGroups[$i])],
                'SHOP_EXPORT_GROUP_CODE' => $arrGroups[$i],
                'SHOP_EXPORT_INDEX' => $i,
                'CLASS_NAME' => 'row'.($i % 2 + 1),
            ));
            self::$objTemplate->parse('groupRow');
            $tipText .= 'Text['.$i.']=["","'.$_ARRAYLANG['TXT_SHOP_EXPORT_GROUP_'.strtoupper($arrGroups[$i]).'_TIP'].'"];';
        }
*/
    }


    /**
     * Attributes and options edit view
     * @access    private
     */
    function view_attributes_edit()
    {
        global $_ARRAYLANG, $_CONFIG;

        self::$pageTitle = $_ARRAYLANG['TXT_PRODUCT_CHARACTERISTICS'];
        self::$objTemplate->addBlockfile('SHOP_PRODUCTS_FILE', 'shop_products_block', 'module_shop_product_attributes.html');

//DBG::log("Shopmanager::view_attributes_edit(): Post: ".var_export($_POST, true));
        // delete Attribute
        if (!empty($_GET['delete_attribute_id'])) {
// TODO: Set messages in there
            $this->_deleteAttribute($_GET['delete_attribute_id']);
        } elseif (!empty($_POST['multi_action'])
               && $_POST['multi_action'] == 'delete'
               && !empty($_POST['selected_attribute_id'])) {
            $this->_deleteAttribute($_POST['selected_attribute_id']);
        }
        // store new option
        if (!empty($_POST['addAttributeOption']))
            $this->_storeNewAttributeOption();
        // update attribute options
        if (!empty($_POST['updateAttributeOptions']))
// TODO: Set messages in there
            $this->_updateAttributeOptions();
        // Clear the Product Attribute data present in Attributes.
        // This may have been changed above and would thus be out of date.
        Attributes::reset();

        $count = 0;
        $limit = $_CONFIG['corePagingLimit'];
        $order = "`id` ASC";
        $filter = (isset($_REQUEST['filter'])
            ? contrexx_input2raw($_REQUEST['filter']) : null);
        $arrAttributes = Attributes::getArray(
            $count, \Paging::getPosition(), $limit, $order, $filter);
//DBG::log("ShopManager::_showAttributeOptions(): count ".count($arrAttributes)." of $count, limit $limit, order $order, filter $filter");
        $rowClass = 1;
        foreach ($arrAttributes as $attribute_id => $objAttribute) {
            self::$objTemplate->setCurrentBlock('attributeList');
            self::$objTemplate->setVariable(array(
                'SHOP_PRODUCT_ATTRIBUTE_ROW_CLASS' => 'row'.(++$rowClass % 2 + 1),
                'SHOP_PRODUCT_ATTRIBUTE_ID' => $attribute_id,
                'SHOP_PRODUCT_ATTRIBUTE_NAME' => $objAttribute->getName(),
                'SHOP_PRODUCT_ATTRIBUTE_VALUE_MENU' =>
                    Attributes::getOptionMenu(
                        $attribute_id, 'option_id', '',
                        'setSelectedValue('.$attribute_id.')', 'width: 290px;'),
                'SHOP_PRODUCT_ATTRIBUTE_VALUE_INPUTBOXES' =>
                    Attributes::getInputs(
                        $attribute_id, 'option_name', 'value',
                        255, 'width: 200px;'),
                'SHOP_PRODUCT_ATTRIBUTE_PRICE_INPUTBOXES' =>
                    Attributes::getInputs(
                        $attribute_id, 'option_price', 'price',
                        9, 'width: 200px; text-align: right;'),
                'SHOP_PRODUCT_ATTRIBUTE_DISPLAY_TYPE' =>
                    Attributes::getDisplayTypeMenu(
                        $attribute_id, $objAttribute->getType(),
                        'updateOptionList('.$attribute_id.')'),
            ));
            self::$objTemplate->parseCurrentBlock();
        }
        // The same for a new Attribute
        $uri_param = '&cmd=Shop&act=products&tpl=attributes';
        self::$objTemplate->setVariable(array(
            'SHOP_PRODUCT_ATTRIBUTE_TYPE_MENU' =>
                Attributes::getDisplayTypeMenu(
                    0, 0, 'updateOptionList(0)'),
            'SHOP_PRODUCT_ATTRIBUTE_JS_VARS' =>
                Attributes::getAttributeJSVars(),
            'SHOP_PRODUCT_ATTRIBUTE_CURRENCY' => Currency::getDefaultCurrencySymbol(),
            'SHOP_PAGING' => \Paging::get($uri_param,
                $_ARRAYLANG['TXT_PRODUCT_CHARACTERISTICS'], $count, $limit),
        ));
    }


    /**
     * Partial view of the Attributes for a Product being edited
     *
     * Only called by {@see view_product_edit()}.
     * Mind that the $product_id may be empty (usually zero) for new Products.
     * @access  private
     * @param   integer   $product_id    The ID of the Product being edited
     * @return  void
     */
    private static function viewpart_product_attributes($product_id=null)
    {
        $i = 0;
        $count = 0;
        // If a Product is selected, check those Product Attribute values
        // associated with it
        $arrRelation = Attributes::getRelationArray($product_id);
        foreach (Attributes::getArray($count) as $attribute_id => $objAttribute) {
            // All options available for this Product Attribute
            $arrOptions = Attributes::getOptionArrayByAttributeId($attribute_id);
            $nameSelected = false;
            $order = 0;
            foreach ($arrOptions as $option_id => $arrOption) {
                $valueSelected = false;
                if (in_array($option_id, array_keys($arrRelation))) {
                    $valueSelected = true;
                    $nameSelected = true;
                    $order = $arrRelation[$option_id];
                }
                self::$objTemplate->setVariable(array(
                    'SHOP_PRODUCTS_ATTRIBUTE_ID' => $attribute_id,
                    'SHOP_PRODUCTS_ATTRIBUTE_VALUE_ID' => $option_id,
                    'SHOP_PRODUCTS_ATTRIBUTE_VALUE_TEXT' => $arrOption['value'].
                        ' ('.$arrOption['price'].' '.Currency::getDefaultCurrencySymbol().')',
                    'SHOP_PRODUCTS_ATTRIBUTE_VALUE_SELECTED' => ($valueSelected ? \Html::ATTRIBUTE_CHECKED : ''),
                ));
                self::$objTemplate->parse('optionList');
            }
            self::$objTemplate->setVariable(array(
                'SHOP_PRODUCTS_ATTRIBUTE_ROW_CLASS' => 'row'.(++$i % 2 + 1),
                'SHOP_PRODUCTS_ATTRIBUTE_ID' => $attribute_id,
                'SHOP_PRODUCTS_ATTRIBUTE_NAME' => $objAttribute->getName(),
                'SHOP_PRODUCTS_ATTRIBUTE_SELECTED' => ($nameSelected ? \Html::ATTRIBUTE_CHECKED : ''),
                'SHOP_PRODUCTS_ATTRIBUTE_DISPLAY_TYPE' => ($nameSelected ? 'block' : 'none'),
                'SHOP_PRODUCTS_ATTRIBUTE_SORTID' => $order,
            ));
            self::$objTemplate->parse('attributeList');
        }
    }


    /**
     * Store a new attribute option
     * @access    private
     * @return    string    $statusMessage    Status message
     */
    function _storeNewAttributeOption()
    {
        global $_ARRAYLANG;

//DBG::log("Shopmanager::_storeNewAttributeOption(): Post: ".var_export($_POST, true));

        if (empty($_POST['attribute_name'][0])) {
            return $_ARRAYLANG['TXT_DEFINE_NAME_FOR_OPTION'];
        }
        if (empty($_POST['option_id'][0])
         || !is_array($_POST['option_id'][0])) {
            return $_ARRAYLANG['TXT_DEFINE_VALUE_FOR_OPTION'];
        }
        $arrOptionId = contrexx_input2int($_POST['option_id'][0]);
        $arrOptionValue =
            (   empty($_POST['option_name'])
             || !is_array($_POST['option_name'])
                ? array() : contrexx_input2raw($_POST['option_name']));
        $arrOptionPrice =
            (   empty($_POST['option_price'])
             || !is_array($_POST['option_price'])
            ? array() : contrexx_input2float($_POST['option_price']));
        $attribute_name = contrexx_input2raw($_POST['attribute_name'][0]);
        $attribute_type = (empty($_POST['attribute_type'][0])
            ? Attribute::TYPE_MENU_OPTIONAL
            : intval($_POST['attribute_type'][0]));
//DBG::log("Attribute name: $attribute_name, type: $attribute_type");
        $objAttribute = new Attribute(
            $attribute_name,
            $attribute_type);
//DBG::log("New Attribute: ".var_export($objAttribute, true));
        $i = 0;
        foreach ($arrOptionId as $option_id) {
            $objAttribute->addOption(
                // Option names may be empty or missing altogether!
                (isset ($arrOptionValue[$option_id])
                    ? $arrOptionValue[$option_id] : ''),
                $arrOptionPrice[$option_id],
                ++$i);
        }
//DBG::log("New Options: ".var_export($objAttribute, true));
        if (!$objAttribute->store()) {
            return \Message::error(
                $_ARRAYLANG['TXT_SHOP_ERROR_INSERTING_PRODUCTATTRIBUTE']);
        }
        return true;
    }


    /**
     * Updates Attribute options in the database
     * @access    private
     * @return    boolean           True on success, null on noop, or
     *                              false otherwise
     */
    function _updateAttributeOptions()
    {
        global $_ARRAYLANG;

        $arrAttributeName = contrexx_input2raw($_POST['attribute_name']);
        $arrAttributeType = contrexx_input2int($_POST['attribute_type']);
        $arrAttributeList = contrexx_input2int($_POST['option_id']);
        $arrOptionValue = contrexx_input2raw(
            isset($_POST['option_name']) ? $_POST['option_name'] : NULL);
        $arrOptionPrice = contrexx_input2float($_POST['option_price']);
        $flagChangedAny = false;
        foreach ($arrAttributeList as $attribute_id => $arrOptionIds) {
            $flagChanged = false;
            $objAttribute = Attribute::getById($attribute_id);
            if (!$objAttribute) {
                return \Message::error($_ARRAYLANG['TXT_SHOP_ERROR_UPDATING_RECORD']);
            }
            $name = $arrAttributeName[$attribute_id];
            $type = $arrAttributeType[$attribute_id];
            if (   $name != $objAttribute->getName()
                || $type != $objAttribute->getType()) {
                $objAttribute->setName($name);
                $objAttribute->setType($type);
                $flagChanged = true;
            }
            $arrOptions = $objAttribute->getOptionArray();
            foreach ($arrOptionIds as $option_id) {
                // Make sure these values are defined if empty:
                // The option name and price
                if (empty($arrOptionValue[$option_id]))
                    $arrOptionValue[$option_id] = '';
                if (empty($arrOptionPrice[$option_id]))
                    $arrOptionPrice[$option_id] = '0.00';
                if (isset($arrOptions[$option_id])) {
                    if (   $arrOptionValue[$option_id] != $arrOptions[$option_id]['value']
                        || $arrOptionPrice[$option_id] != $arrOptions[$option_id]['price']) {
                        $objAttribute->changeValue($option_id, $arrOptionValue[$option_id], $arrOptionPrice[$option_id]);
                        $flagChanged = true;
                    }
                } else {
                    $objAttribute->addOption($arrOptionValue[$option_id], $arrOptionPrice[$option_id]);
                    $flagChanged = true;
                }
            }
            // Delete values that are no longer present in the post
            foreach (array_keys($arrOptions) as $option_id) {
                if (!in_array($option_id, $arrAttributeList[$attribute_id])) {
                    $objAttribute->deleteValueById($option_id);
                }
            }
            if ($flagChanged) {
                $flagChangedAny = true;
                if (!$objAttribute->store()) {
                    return \Message::error($_ARRAYLANG['TXT_SHOP_ERROR_UPDATING_RECORD']);
                }
            }
        }
/*
        // Delete Product Attributes with no values
        foreach (array_keys(Attributes::getNameArray()) as $attribute_id) {
            if (!array_key_exists($attribute_id, $arrAttributeList)) {
                $objAttribute = Attribute::getById($attribute_id);
                if (!$objAttribute)
                    return \Message::error($_ARRAYLANG['TXT_SHOP_ERROR_UPDATING_RECORD']);
                if (!$objAttribute->delete())
                    return \Message::error($_ARRAYLANG['TXT_SHOP_ERROR_UPDATING_RECORD']);
            }
        }
*/
        if ($flagChangedAny) {
            \Message::ok($_ARRAYLANG['TXT_DATA_RECORD_UPDATED_SUCCESSFUL']);
        }
        return true;
    }


    /**
     * Delete one or more Attribute
     * @access  private
     * @param   mixed     $attribute_id     The Attribute ID or an array of IDs
     * @return  string                      The empty string on success,
     *                                      some status message on failure
     */
    function _deleteAttribute($attribute_id)
    {
        global $_ARRAYLANG;

        $arrAttributeId = $attribute_id;
        if (!is_array($attribute_id)) {
            $arrAttributeId = array($attribute_id);
        }
        foreach ($arrAttributeId as $attribute_id) {
            $objAttribute = Attribute::getById($attribute_id);
            if (!$objAttribute) {
                return \Message::error(
                    $_ARRAYLANG['TXT_SHOP_ATTRIBUTE_ERROR_NOT_FOUND']);
            }
            if (!$objAttribute->delete()) {
                return \Message::error(
                    $_ARRAYLANG['TXT_SHOP_ATTRIBUTE_ERROR_DELETING']);
            }
        }
        return \Message::ok(
            $_ARRAYLANG['TXT_SHOP_ATTRIBUTE'.
            (count($arrAttributeId) > 1 ? 'S' : '').
            '_SUCCESSFULLY_DELETED']);
    }


    /**
     * Set up the common elements and individual content of various
     * settings pages
     *
     * Includes VAT, shipping, countries, zones and more
     * @access private
     * @static
     */
    static function view_settings()
    {
        global $_ARRAYLANG;

        \Cx\Core\Setting\Controller\Setting::init('Shop', 'config');
        if (ShopSettings::storeSettings() === false) {
            // Triggers update
            ShopSettings::errorHandler();
            \Cx\Core\Setting\Controller\Setting::init('Shop', 'config');
        }
        // $success may also be '', in which case no changed setting has
        // been detected.
        // Refresh the Settings, so changes are made visible right away
        \Cx\Core\Setting\Controller\Setting::init('Shop', 'config');
        self::$pageTitle = $_ARRAYLANG['TXT_SETTINGS'];
        self::$objTemplate->loadTemplateFile('module_shop_settings.html');
        if (empty($_GET['tpl'])) $_GET['tpl'] = '';
        switch ($_GET['tpl']) {
            case 'currency':
                self::view_settings_currency();
                break;
            case 'payment':
                Payment::view_settings(self::$objTemplate);
                break;
            case 'shipment':
                self::view_settings_shipment();
                break;
            case 'countries':
                self::view_settings_countries();
                break;
            case 'zones':
                self::view_settings_zones();
                break;
            case 'mail':
                self::view_settings_mail();
                break;
            case 'vat':
                self::view_settings_vat();
                break;
            case 'coupon':
                self::$objTemplate->addBlockfile('SHOP_SETTINGS_FILE',
                    'settings_block', 'module_shop_discount_coupon.html');
                Coupon::edit(self::$objTemplate);
                break;
            default:
                self::view_settings_general();
                break;
        }
    }


    /**
     * The currency settings view
     */
    static function view_settings_currency()
    {
        self::$objTemplate->addBlockfile('SHOP_SETTINGS_FILE',
            'settings_block', 'module_shop_settings_currency.html');
        $i = 0;
        foreach (Currency::getCurrencyArray() as $currency) {
            self::$objTemplate->setVariable(array(
                'SHOP_CURRENCY_STYLE' => 'row'.(++$i % 2 + 1),
                'SHOP_CURRENCY_ID' => $currency['id'],
                'SHOP_CURRENCY_CODE' => $currency['code'],
                'SHOP_CURRENCY_SYMBOL' => $currency['symbol'],
                'SHOP_CURRENCY_NAME' => $currency['name'],
                'SHOP_CURRENCY_RATE' => $currency['rate'],
                'SHOP_CURRENCY_INCREMENT' => $currency['increment'],
                'SHOP_CURRENCY_ACTIVE' => ($currency['active']
                    ? \Html::ATTRIBUTE_CHECKED : ''),
                'SHOP_CURRENCY_STANDARD' => ($currency['default']
                    ? \Html::ATTRIBUTE_CHECKED : ''),
            ));
            self::$objTemplate->parse('shopCurrency');
        }
        $str_js = '';
        foreach (Currency::get_known_currencies_increment_array()
                as $code => $increment) {
            // This seems like a sensible default for the few unknown ones
            if (!is_numeric($increment)) $increment = 0.01;
            $str_js .=
                ($str_js ? ',' : '').
                '"'.$code.'":"'.$increment.'"';
        };
        self::$objTemplate->setVariable(array(
            'SHOP_CURRENCY_NAME_MENUOPTIONS' => \Html::getOptions(
                Currency::get_known_currencies_name_array()),
            'SHOP_CURRENCY_INCREMENT_JS_ARRAY' =>
                'var currency_increment = {'.$str_js.'};',
        ));
    }


    /**
     * The shipment settings view
     */
    static function view_settings_shipment()
    {
        // start show shipment
        self::$objTemplate->addBlockfile('SHOP_SETTINGS_FILE',
            'settings_block', 'module_shop_settings_shipment.html');
        self::$objTemplate->setGlobalVariable(
            'SHOP_CURRENCY', Currency::getDefaultCurrencySymbol()
        );
        $arrShipments = Shipment::getShipmentsArray();
        $i = 0;
        foreach (Shipment::getShippersArray() as $shipper_id => $arrShipper) {
            $zone_id = Zones::getZoneIdByShipperId($shipper_id);
            // Inner block first
            self::$objTemplate->setCurrentBlock('shopShipment');
            // Show all possible shipment conditions for each shipper
            if (isset($arrShipments[$shipper_id])) {
                foreach ($arrShipments[$shipper_id] as $shipment_id => $arrConditions) {
                    self::$objTemplate->setVariable(array(
                        'SHOP_SHIPMENT_STYLE' => 'row'.(++$i % 2 + 1),
                        'SHOP_SHIPPER_ID' => $shipper_id,
                        'SHOP_SHIPMENT_ID' => $shipment_id,
                        'SHOP_SHIPMENT_MAX_WEIGHT' => $arrConditions['max_weight'],
                        'SHOP_SHIPMENT_PRICE_FREE' => $arrConditions['free_from'],
                        'SHOP_SHIPMENT_COST' => $arrConditions['fee'],
                    ));
                    //self::$objTemplate->parseCurrentBlock();
                    self::$objTemplate->parse('shopShipment');
                }
            }
            // Outer block
            self::$objTemplate->setCurrentBlock('shopShipper');
            self::$objTemplate->setVariable(array(
                'SHOP_SHIPMENT_STYLE' => 'row'.(++$i % 2 + 1),
                'SHOP_SHIPPER_ID' => $shipper_id,
//                'SHOP_SHIPPER_MENU' => Shipment::getShipperMenu(0, $shipper_id),
                'SHOP_SHIPPER_NAME' => \Html::getInputText(
                    'shipper_name['.$shipper_id.']', $arrShipper['name']),
                'SHOP_ZONE_SELECTION' => Zones::getMenu(
                    $zone_id, 'zone_id['.$shipper_id.']'),
                'SHOP_SHIPPER_STATUS' => ($arrShipper['active']
                    ? \Html::ATTRIBUTE_CHECKED : ''),
            ));
            self::$objTemplate->parse('shopShipper');
        }
        self::$objTemplate->setVariable(
            'SHOP_ZONE_SELECTION_NEW', Zones::getMenu(0, 'zone_id_new')
        );
    }


    /**
     * The country settings view
     */
    static function view_settings_countries()
    {
        self::$objTemplate->addBlockfile('SHOP_SETTINGS_FILE',
            'settings_block', 'module_shop_settings_countries.html');
        $selected = '';
        $notSelected = '';
        $count = 0;
        foreach (\Cx\Core\Country\Controller\Country::getArray($count) as $country_id => $arrCountry) {
            if (empty($arrCountry['active'])) {
                $notSelected .=
                    '<option value="'.$country_id.'">'.
                    $arrCountry['name']."</option>\n";
            } else {
                $selected .=
                    '<option value="'.$country_id.'">'.
                    $arrCountry['name']."</option>\n";
            }
        }
        self::$objTemplate->setVariable(array(
            'SHOP_COUNTRY_SELECTED_OPTIONS' => $selected,
            'SHOP_COUNTRY_NOTSELECTED_OPTIONS' => $notSelected,
        ));
    }


    /**
     * The zones settings view
     */
    static function view_settings_zones()
    {
        self::$objTemplate->addBlockfile('SHOP_SETTINGS_FILE',
            'settings_block', 'module_shop_settings_zones.html');
        $arrZones = Zones::getZoneArray();
        $selectFirst = false;
        $strZoneOptions = '';
        foreach ($arrZones as $zone_id => $arrZone) {
            // Skip zone "All"
            if ($zone_id == 1) continue;
            $strZoneOptions .=
                '<option value="'.$zone_id.'"'.
                ($selectFirst ? '' : \Html::ATTRIBUTE_SELECTED).
                '>'.$arrZone['name']."</option>\n";
            $arrCountryInZone = \Cx\Core\Country\Controller\Country::getArraysByZoneId($zone_id);
            $strSelectedCountries = '';
            foreach ($arrCountryInZone['in'] as $country_id => $arrCountry) {
                $strSelectedCountries .=
                    '<option value="'.$country_id.'">'.
                    $arrCountry['name'].
                    "</option>\n";
            }
            $strCountryList = '';
            foreach ($arrCountryInZone['out'] as $country_id => $arrCountry) {
                $strCountryList .=
                    '<option value="'.$country_id.'">'.
                    $arrCountry['name'].
                    "</option>\n";
            }
            self::$objTemplate->setVariable(array(
                'SHOP_ZONE_ID' => $zone_id,
                'ZONE_ACTIVE_STATUS' => ($arrZone['active'] ? \Html::ATTRIBUTE_CHECKED : '') ,
                'SHOP_ZONE_NAME' => $arrZone['name'],
                'SHOP_ZONE_DISPLAY_STYLE' => ($selectFirst ? 'display: none;' : 'display: block;'),
                'SHOP_ZONE_SELECTED_COUNTRIES_OPTIONS' => $strSelectedCountries,
                'SHOP_COUNTRY_LIST_OPTIONS' => $strCountryList
            ));
            self::$objTemplate->parse('shopZones');
            $selectFirst = true;
        }
        self::$objTemplate->setVariable(array(
            'SHOP_ZONES_OPTIONS' => $strZoneOptions,
            'SHOP_ZONE_COUNTRY_LIST' => \Cx\Core\Country\Controller\Country::getMenuoptions(),
        ));
    }


    /**
     * The mailtemplate settings view
     *
     * Stores MailTemplates posted from the {@see MailTemplate::edit()} view.
     * Deletes a MailTemplate on request from the
     * {@see MailTemplate::overview()} view.
     * Includes both the overview and the edit view, activates one depending
     * on the outcome of the call to {@see MailTemplate::storeFromPost()}
     * or the current active_tab.
     * @return  boolean               True on success, false otherwise
     */
    static function view_settings_mail()
    {
        global $_CORELANG;

// TODO: TEMPORARY.  Remove when a proper update is available.
$template = \Cx\Core\MailTemplate\Controller\MailTemplate::get('Shop', 'order_confirmation');
//die(var_export($template, true));
if (!$template) {
    ShopMail::errorHandler();
}

        $result = true;
        $_REQUEST['active_tab'] = 1;
        if (   isset($_REQUEST['act'])
            && $_REQUEST['act'] == 'mailtemplate_edit') {
            $_REQUEST['active_tab'] = 2;
        }
        \Cx\Core\MailTemplate\Controller\MailTemplate::deleteTemplate('Shop');
        // If there is anything to be stored, and if that fails, return to
        // the edit view in order to save the posted form content
        $result_store = \Cx\Core\MailTemplate\Controller\MailTemplate::storeFromPost('Shop');
        if ($result_store === false) {
            $_REQUEST['active_tab'] = 2;
        }
        $objTemplate = null;
        $result &= \Cx\Core\Setting\Controller\Setting::show_external(
            $objTemplate,
            $_CORELANG['TXT_CORE_MAILTEMPLATES'],
            \Cx\Core\MailTemplate\Controller\MailTemplate::overview('Shop', 'config',
                \Cx\Core\Setting\Controller\Setting::getValue('numof_mailtemplate_per_page_backend','Shop')
            )->get()
        );
        $result &= \Cx\Core\Setting\Controller\Setting::show_external(
            $objTemplate,
            (empty($_REQUEST['key'])
              ? $_CORELANG['TXT_CORE_MAILTEMPLATE_ADD']
              : $_CORELANG['TXT_CORE_MAILTEMPLATE_EDIT']),
            \Cx\Core\MailTemplate\Controller\MailTemplate::edit('Shop')->get()
        );
        self::$objTemplate->addBlock('SHOP_SETTINGS_FILE',
            'settings_block', $objTemplate->get());
        self::$objTemplate->touchBlock('settings_block');
        return $result;
    }


    static function view_settings_vat()
    {
        global $_ARRAYLANG;

// TODO: Temporary.  Remove in release with working update
// Returns NULL on missing entries even when other settings are properly loaded
$vat_number = \Cx\Core\Setting\Controller\Setting::getValue('vat_number','Shop');
if (is_null($vat_number)) {
    \Cx\Core\Setting\Controller\Setting::add('vat_number', '12345678', 1, 'text', '', 'config');
}

        // Shop general settings template
        self::$objTemplate->addBlockfile('SHOP_SETTINGS_FILE',
            'settings_block', 'module_shop_settings_vat.html');
        self::$objTemplate->setGlobalVariable($_ARRAYLANG);
        $enabled_home_customer = \Cx\Core\Setting\Controller\Setting::getValue('vat_enabled_home_customer','Shop');
        $included_home_customer = \Cx\Core\Setting\Controller\Setting::getValue('vat_included_home_customer','Shop');
        $enabled_home_reseller = \Cx\Core\Setting\Controller\Setting::getValue('vat_enabled_home_reseller','Shop');
        $included_home_reseller = \Cx\Core\Setting\Controller\Setting::getValue('vat_included_home_reseller','Shop');
        $enabled_foreign_customer = \Cx\Core\Setting\Controller\Setting::getValue('vat_enabled_foreign_customer','Shop');
        $included_foreign_customer = \Cx\Core\Setting\Controller\Setting::getValue('vat_included_foreign_customer','Shop');
        $enabled_foreign_reseller = \Cx\Core\Setting\Controller\Setting::getValue('vat_enabled_foreign_reseller','Shop');
        $included_foreign_reseller = \Cx\Core\Setting\Controller\Setting::getValue('vat_included_foreign_reseller','Shop');
        self::$objTemplate->setVariable(array(
            'SHOP_VAT_NUMBER' => \Cx\Core\Setting\Controller\Setting::getValue('vat_number','Shop'),
            'SHOP_VAT_CHECKED_HOME_CUSTOMER' => ($enabled_home_customer ? \Html::ATTRIBUTE_CHECKED : ''),
            'SHOP_VAT_DISPLAY_HOME_CUSTOMER' => ($enabled_home_customer ? 'block' : 'none'),
            'SHOP_VAT_SELECTED_HOME_CUSTOMER_INCLUDED' => ($included_home_customer ? \Html::ATTRIBUTE_SELECTED : ''),
            'SHOP_VAT_SELECTED_HOME_CUSTOMER_EXCLUDED' => ($included_home_customer ? '' : \Html::ATTRIBUTE_SELECTED),
            'SHOP_VAT_CHECKED_HOME_RESELLER' => ($enabled_home_reseller ? \Html::ATTRIBUTE_CHECKED : ''),
            'SHOP_VAT_DISPLAY_HOME_RESELLER' => ($enabled_home_reseller ? 'block' : 'none'),
            'SHOP_VAT_SELECTED_HOME_RESELLER_INCLUDED' => ($included_home_reseller ? \Html::ATTRIBUTE_SELECTED : ''),
            'SHOP_VAT_SELECTED_HOME_RESELLER_EXCLUDED' => ($included_home_reseller ? '' : \Html::ATTRIBUTE_SELECTED),
            'SHOP_VAT_CHECKED_FOREIGN_CUSTOMER' => ($enabled_foreign_customer ? \Html::ATTRIBUTE_CHECKED : ''),
            'SHOP_VAT_DISPLAY_FOREIGN_CUSTOMER' => ($enabled_foreign_customer ? 'block' : 'none'),
            'SHOP_VAT_SELECTED_FOREIGN_CUSTOMER_INCLUDED' => ($included_foreign_customer ? \Html::ATTRIBUTE_SELECTED : ''),
            'SHOP_VAT_SELECTED_FOREIGN_CUSTOMER_EXCLUDED' => ($included_foreign_customer ? '' : \Html::ATTRIBUTE_SELECTED),
            'SHOP_VAT_CHECKED_FOREIGN_RESELLER' => ($enabled_foreign_reseller ? \Html::ATTRIBUTE_CHECKED : ''),
            'SHOP_VAT_DISPLAY_FOREIGN_RESELLER' => ($enabled_foreign_reseller ? 'block' : 'none'),
            'SHOP_VAT_SELECTED_FOREIGN_RESELLER_INCLUDED' => ($included_foreign_reseller ? \Html::ATTRIBUTE_SELECTED : ''),
            'SHOP_VAT_SELECTED_FOREIGN_RESELLER_EXCLUDED' => ($included_foreign_reseller ? '' : \Html::ATTRIBUTE_SELECTED),
            'SHOP_VAT_DEFAULT_MENUOPTIONS' => Vat::getMenuoptions(
                \Cx\Core\Setting\Controller\Setting::getValue('vat_default_id','Shop'), true),
            'SHOP_VAT_OTHER_MENUOPTIONS' => Vat::getMenuoptions(
                \Cx\Core\Setting\Controller\Setting::getValue('vat_other_id','Shop'), true),
        ));
        // start value added tax (VAT) display
        // fill in the VAT fields of the template
        $i = 0;
        foreach (Vat::getArray() as $vat_id => $arrVat) {
            self::$objTemplate->setVariable(array(
                'SHOP_ROWCLASS' => 'row'.(++$i % 2 + 1),
                'SHOP_VAT_ID' => $vat_id,
                'SHOP_VAT_RATE' => $arrVat['rate'],
                'SHOP_VAT_CLASS' => $arrVat['class'],
            ));
            self::$objTemplate->parse('vatRow');
        }
    }


    static function view_settings_general()
    {
        // General settings
        self::$objTemplate->addBlockfile('SHOP_SETTINGS_FILE',
            'settings_block', 'module_shop_settings_general.html');

// TODO: Temporary.  Remove in release with working update
// Returns NULL on missing entries even when other settings are properly loaded
$test = \Cx\Core\Setting\Controller\Setting::getValue('shopnavbar_on_all_pages','Shop');
if ($test === NULL) {
    ShopSettings::errorHandler();
    \Cx\Core\Setting\Controller\Setting::init('Shop', 'config');
}

        self::$objTemplate->setVariable(array(
            'SHOP_CONFIRMATION_EMAILS' => \Cx\Core\Setting\Controller\Setting::getValue('email_confirmation','Shop'),
            'SHOP_CONTACT_EMAIL' => \Cx\Core\Setting\Controller\Setting::getValue('email','Shop'),
            'SHOP_CONTACT_COMPANY' => \Cx\Core\Setting\Controller\Setting::getValue('company','Shop'),
            'SHOP_CONTACT_ADDRESS' => \Cx\Core\Setting\Controller\Setting::getValue('address','Shop'),
            'SHOP_CONTACT_TEL' => \Cx\Core\Setting\Controller\Setting::getValue('telephone','Shop'),
            'SHOP_CONTACT_FAX' => \Cx\Core\Setting\Controller\Setting::getValue('fax','Shop'),
            // Country settings
            'SHOP_GENERAL_COUNTRY_MENUOPTIONS' => \Cx\Core\Country\Controller\Country::getMenuoptions(
                \Cx\Core\Setting\Controller\Setting::getValue('country_id','Shop')),
            // Thumbnail settings
            'SHOP_THUMBNAIL_MAX_WIDTH' => \Cx\Core\Setting\Controller\Setting::getValue('thumbnail_max_width','Shop'),
            'SHOP_THUMBNAIL_MAX_HEIGHT' => \Cx\Core\Setting\Controller\Setting::getValue('thumbnail_max_height','Shop'),
            'SHOP_THUMBNAIL_QUALITY' => \Cx\Core\Setting\Controller\Setting::getValue('thumbnail_quality','Shop'),
            // Enable weight setting
            'SHOP_WEIGHT_ENABLE_CHECKED' => (\Cx\Core\Setting\Controller\Setting::getValue('weight_enable','Shop')
                ? \Html::ATTRIBUTE_CHECKED : ''),
            'SHOP_SHOW_PRODUCTS_DEFAULT_OPTIONS' => Products::getDefaultViewMenuoptions(
                \Cx\Core\Setting\Controller\Setting::getValue('show_products_default','Shop')),
            'SHOP_PRODUCT_SORTING_MENUOPTIONS' => Products::getProductSortingMenuoptions(),
            // Order amount upper limit
            'SHOP_ORDERITEMS_AMOUNT_MAX' => Currency::formatPrice(
                \Cx\Core\Setting\Controller\Setting::getValue('orderitems_amount_max','Shop')),
            // Order amount lower limit
            'SHOP_ORDERITEMS_AMOUNT_MIN' => Currency::formatPrice(
                \Cx\Core\Setting\Controller\Setting::getValue('orderitems_amount_min','Shop')),
            'SHOP_CURRENCY_CODE' => Currency::getCurrencyCodeById(
                Currency::getDefaultCurrencyId()),
            // New extended settings in V3.0.0
            'SHOP_SETTING_CART_USE_JS' =>
                \Html::getCheckbox('use_js_cart', 1, false,
                    \Cx\Core\Setting\Controller\Setting::getValue('use_js_cart','Shop')),
            'SHOP_SETTING_SHOPNAVBAR_ON_ALL_PAGES' =>
                \Html::getCheckbox('shopnavbar_on_all_pages', 1, false,
                    \Cx\Core\Setting\Controller\Setting::getValue('shopnavbar_on_all_pages','Shop')),
            'SHOP_SETTING_REGISTER' => \Html::getSelectCustom('register',
                ShopLibrary::getRegisterMenuoptions(
                    \Cx\Core\Setting\Controller\Setting::getValue('register','Shop')), false, '',
                    'style="width: 270px;"'),
            'SHOP_SETTING_NUMOF_PRODUCTS_PER_PAGE_BACKEND' =>
                \Cx\Core\Setting\Controller\Setting::getValue('numof_products_per_page_backend','Shop'),
            'SHOP_SETTING_NUMOF_ORDERS_PER_PAGE_BACKEND' =>
                \Cx\Core\Setting\Controller\Setting::getValue('numof_orders_per_page_backend','Shop'),
            'SHOP_SETTING_NUMOF_CUSTOMERS_PER_PAGE_BACKEND' =>
                \Cx\Core\Setting\Controller\Setting::getValue('numof_customers_per_page_backend','Shop'),
            'SHOP_SETTING_NUMOF_MANUFACTURERS_PER_PAGE_BACKEND' =>
                \Cx\Core\Setting\Controller\Setting::getValue('numof_manufacturers_per_page_backend','Shop'),
            'SHOP_SETTING_NUMOF_MAILTEMPLATE_PER_PAGE_BACKEND' =>
                \Cx\Core\Setting\Controller\Setting::getValue('numof_mailtemplate_per_page_backend','Shop'),
            'SHOP_SETTING_NUMOF_COUPON_PER_PAGE_BACKEND' =>
                \Cx\Core\Setting\Controller\Setting::getValue('numof_coupon_per_page_backend','Shop'),
            'SHOP_SETTING_NUMOF_PRODUCTS_PER_PAGE_FRONTEND' =>
                \Cx\Core\Setting\Controller\Setting::getValue('numof_products_per_page_frontend','Shop'),
            'SHOP_SETTING_NUM_CATEGORIES_PER_ROW' =>
                \Cx\Core\Setting\Controller\Setting::getValue('num_categories_per_row','Shop'),

// TODO: Use \Cx\Core\Setting\Controller\Setting::show(), and add a proper setting type!
            'SHOP_SETTING_USERGROUP_ID_CUSTOMER' =>
                \Html::getSelect(
                    'usergroup_id_customer',
                    \UserGroup::getNameArray(),
                    \Cx\Core\Setting\Controller\Setting::getValue('usergroup_id_customer','Shop'),
                    '', '', 'tabindex="0" style="width: 270px;"'),
            'SHOP_SETTING_USERGROUP_ID_RESELLER' =>
                \Html::getSelect(
                    'usergroup_id_reseller',
                    \UserGroup::getNameArray(),
                    \Cx\Core\Setting\Controller\Setting::getValue('usergroup_id_reseller','Shop'),
                    '', '', 'tabindex="0" style="width: 270px;"'),
            'SHOP_SETTING_USER_PROFILE_ATTRIBUTE_CUSTOMER_GROUP_ID' =>
                \Html::getSelect(
                    'user_profile_attribute_customer_group_id',
                    \User_Profile_Attribute::getCustomAttributeNameArray(),
                    \Cx\Core\Setting\Controller\Setting::getValue('user_profile_attribute_customer_group_id','Shop'),
                    '', '', 'tabindex="0" style="width: 270px;"'),
            'SHOP_SETTING_USER_PROFILE_ATTRIBUTE_NOTES' =>
                \Html::getSelect(
                    'user_profile_attribute_notes',
                    \User_Profile_Attribute::getCustomAttributeNameArray(),
                    \Cx\Core\Setting\Controller\Setting::getValue('user_profile_attribute_notes','Shop'),
                    '', '', 'tabindex="0" style="width: 270px;"'),

            // product attribute behavior
            'SHOP_ACTIVATE_PRODUCT_ATTRIBUTE_CHILDREN_CHECKED' => (\Cx\Core\Setting\Controller\Setting::getValue('activate_product_attribute_children','Shop')
                ? \Html::ATTRIBUTE_CHECKED : ''),

            // always show 'please select' option in product attribute's dropdowns
            'SHOP_FORCE_SELECT_OPTION_CHECKED' => (\Cx\Core\Setting\Controller\Setting::getValue('force_select_option','Shop')
                ? \Html::ATTRIBUTE_CHECKED : ''),

            // don't allow (anonymous) checkout with an email address
            // of which a user account does already exist
            'SHOP_VERIFY_ACCOUNT_EMAIL_CHECKED' => (\Cx\Core\Setting\Controller\Setting::getValue('verify_account_email','Shop')
                ? \Html::ATTRIBUTE_CHECKED : ''),
        ));
    }


    /**
     * Produces the Categories overview
     * @return  boolean               True on success, false otherwise
     */
    function view_categories()
    {
        global $_ARRAYLANG;

        if (   isset ($_REQUEST['tpl'])) {
            if (   $_REQUEST['tpl'] == 'pricelists'
                || $_REQUEST['tpl'] == 'pricelist_edit') {
                return self::view_pricelists();
            }
        }
        $this->delete_categories();
        $this->store_category();
        $this->update_categories();
        $this->toggle_category();
        $i = 1;
        self::$pageTitle = $_ARRAYLANG['TXT_CATEGORIES'];
        self::$objTemplate->loadTemplateFile('module_shop_categories.html');
        self::$objTemplate->setGlobalVariable($_ARRAYLANG);
        // ID of the category to be edited, if any
        $category_id = (isset($_REQUEST['category_id'])
            ? intval($_REQUEST['category_id']) : 0);
        // Get the tree array of all ShopCategories
        $arrShopCategories =
            ShopCategories::getTreeArray(true, false, false);
        // Default to the list tab
        $flagEditTabActive = false;
        $parent_id = 0;
        $name = '';
        $short = '';
        $desc = '';
        $active = true;
        $virtual = false;
        $pictureFilename = NULL;
        $picturePath = $thumbPath = self::$defaultImage;
        if ($category_id) {
            // Edit the selected category:  Flip view to the edit tab
            $flagEditTabActive = true;
            $objCategory = ShopCategory::getById($category_id);
            if ($objCategory) {
                $parent_id = $objCategory->parent_id();
                $name = contrexx_raw2xhtml($objCategory->name());
                $short = $objCategory->shortDescription();
                $desc = $objCategory->description();
                $active = $objCategory->active();
                $virtual = $objCategory->virtual();
                $pictureFilename = $objCategory->picture();
                if ($pictureFilename != '') {
                    $picturePath = \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteImagesShopWebPath().'/'.$pictureFilename;
                    $thumbPath = \ImageManager::getThumbnailFilename(\Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteImagesShopWebPath().'/'.$pictureFilename);
                }
            }
        }
        $max_width = intval(\Cx\Core\Setting\Controller\Setting::getValue('thumbnail_max_width','Shop'));
        $max_height = intval(\Cx\Core\Setting\Controller\Setting::getValue('thumbnail_max_height','Shop'));
        if (empty($max_width)) $max_width = 1e5;
        if (empty($max_height)) $max_height = 1e5;
        $count = ShopCategories::getTreeNodeCount();
        self::$objTemplate->setVariable(array(
            'TXT_SHOP_CATEGORY_ADD_OR_EDIT' => ($category_id
                ? $_ARRAYLANG['TXT_SHOP_CATEGORY_EDIT']
                : $_ARRAYLANG['TXT_SHOP_CATEGORY_NEW']),
            'TXT_ADD_NEW_SHOP_GROUP' => ($category_id
                ? $_ARRAYLANG['TXT_EDIT_PRODUCT_GROUP']
                : $_ARRAYLANG['TXT_ADD_NEW_PRODUCT_GROUP']),
            'SHOP_CATEGORY_ID' => $category_id,
            'SHOP_CATEGORY_NAME' => $name,
            'SHOP_CATEGORY_MENUOPTIONS' => ShopCategories::getMenuoptions(
                $parent_id, false),
            'SHOP_THUMB_IMG_HREF' => $thumbPath,
            'SHOP_CATEGORY_IMAGE_FILENAME' => ($pictureFilename == ''
                ? $_ARRAYLANG['TXT_SHOP_IMAGE_UNDEFINED'] : $pictureFilename),
            'SHOP_PICTURE_REMOVE_DISPLAY' => ($pictureFilename == ''
                ? \Html::CSS_DISPLAY_NONE : \Html::CSS_DISPLAY_INLINE),
            'SHOP_CATEGORY_VIRTUAL_CHECKED' =>
                ($virtual ? \Html::ATTRIBUTE_CHECKED : ''),
            'SHOP_CATEGORY_ACTIVE_CHECKED' =>
                ($active ? \Html::ATTRIBUTE_CHECKED : ''),
            'SHOP_CATEGORY_SHORT_DESCRIPTION' => $short,
            'SHOP_CATEGORY_DESCRIPTION' => $desc,
            'SHOP_CATEGORY_EDIT_ACTIVE' => ($flagEditTabActive ? 'active' : ''),
            'SHOP_CATEGORY_EDIT_DISPLAY' => ($flagEditTabActive ? 'block' : 'none'),
            'SHOP_CATEGORY_LIST_ACTIVE' => ($flagEditTabActive ? '' : 'active'),
            'SHOP_CATEGORY_LIST_DISPLAY' => ($flagEditTabActive ? 'none' : 'block'),
            'SHOP_IMAGE_WIDTH' => $max_width,
            'SHOP_IMAGE_HEIGHT' => $max_height,
            'SHOP_TOTAL_CATEGORIES' => $count,
        ));
        if ($pictureFilename) {
            self::$objTemplate->setVariable(array(
                'SHOP_PICTURE_IMG_HREF' => $picturePath,
            ));
        }
        // mediabrowser
        $mediaBrowserOptions = array(
            'type'                      => 'button',
            'startmediatype'            => 'shop',
            'views'                     => 'filebrowser',
            'id'                        => 'media_browser_shop',
            'style'                     => 'display:none'
        );
        self::$objTemplate->setGlobalVariable(array(
            'MEDIABROWSER_BUTTON' => self::getMediaBrowserButton($mediaBrowserOptions, 'setSelectedImage')
        ));

        self::$objTemplate->parse('category_edit');
// TODO: Add controls to fold parent categories
//        $level_prev = null;
        $arrLanguages = \FWLanguage::getActiveFrontendLanguages();
        // Intended to show an edit link for all active frontend languages.
        // However, the design doesn't like it.  Limit to the current one.
        $arrLanguages = array(FRONTEND_LANG_ID => $arrLanguages[FRONTEND_LANG_ID]);
        foreach ($arrShopCategories as $arrShopCategory) {
            $category_id = $arrShopCategory['id'];
            $level = $arrShopCategory['level'];
            self::$objTemplate->setGlobalVariable(array(
                'SHOP_ROWCLASS' => 'row'.(++$i % 2 + 1),
                'SHOP_CATEGORY_ID' => $category_id,
                'SHOP_CATEGORY_NAME' => htmlentities(
                    $arrShopCategory['name'], ENT_QUOTES, CONTREXX_CHARSET),
                'SHOP_CATEGORY_ORD' => $arrShopCategory['ord'],
                'SHOP_CATEGORY_LEVELSPACE' => str_repeat('|----', $level),
                'SHOP_CATEGORY_ACTIVE' => ($arrShopCategory['active']
                    ? $_ARRAYLANG['TXT_ACTIVE']
                    : $_ARRAYLANG['TXT_INACTIVE']),
                'SHOP_CATEGORY_ACTIVE_VALUE' => intval($arrShopCategory['active']),
                'SHOP_CATEGORY_ACTIVE_CHECKED' => ($arrShopCategory['active']
                    ? \Html::ATTRIBUTE_CHECKED : ''),
                'SHOP_CATEGORY_ACTIVE_PICTURE' => ($arrShopCategory['active']
                    ? 'status_green.gif' : 'status_red.gif'),
                'SHOP_CATEGORY_VIRTUAL_CHECKED' => ($arrShopCategory['virtual']
                    ? \Html::ATTRIBUTE_CHECKED : ''),
            ));
            // All languages active
            foreach ($arrLanguages as $lang_id => $arrLanguage) {
                self::$objTemplate->setVariable(array(
                    'SHOP_CATEGORY_LANGUAGE_ID' => $lang_id,
                    'SHOP_CATEGORY_LANGUAGE_EDIT' =>
                        sprintf($_ARRAYLANG['TXT_SHOP_CATEGORY_LANGUAGE_EDIT'],
                            $lang_id,
                            $arrLanguage['lang'],
                            $arrLanguage['name']),
                ));
                self::$objTemplate->parse('category_language');
            }
// TODO: Implement a folded hierarchy view
//            self::$objTemplate->touchBlock('category_row');
//            if ($level !== $level_prev) {
//                self::$objTemplate->touchBlock('folder');
//            }
//            $level_prev = $level;
            self::$objTemplate->parse('category_row');
        }
        return true;
    }


    /**
     * Insert or update a ShopCategory with data provided in the request.
     * @return  boolean                 True on success, null on noop,
     *                                  false otherwise.
     * @author  Reto Kohli <reto.kohli@comvation.com> (parts)
     */
    function store_category()
    {
        global $_ARRAYLANG;

        if (empty($_POST['bcategory'])) {
//DBG::log("store_category(): Nothing to do");
            return null;
        }
        $category_id = intval($_POST['category_id']);
        $name = contrexx_input2raw($_POST['name']);
        $active = isset($_POST['active']);
        $virtual = isset($_POST['virtual']);
        $parentid = intval($_POST['parent_id']);
        $picture = contrexx_input2raw($_POST['image_href']);
        $short = contrexx_input2raw($_POST['short']);
        $long = contrexx_input2raw($_POST['desc']);
        $objCategory = null;
        if ($category_id > 0) {
            // Update existing ShopCategory
            $objCategory = ShopCategory::getById($category_id);
            if (!$objCategory) {
                return \Message::error(sprintf(
                    $_ARRAYLANG['TXT_SHOP_CATEGORY_MISSING'], $category_id));
            }
            // Check validity of the IDs of the category and its parent.
            // If the values are identical, leave the parent ID alone!
            if ($category_id != $parentid) $objCategory->parent_id($parentid);
            $objCategory->name($name);
            $objCategory->shortDescription($short);
            $objCategory->description($long);
            $objCategory->active($active);
        } else {
            // Add new ShopCategory
            $objCategory = new ShopCategory(
                $name, $short, $long, $parentid, $active, 0);
        }
        // Ignore the picture if it's the default image!
        // Storing it would be pointless, and we should
        // use the picture of a contained Product instead.
        if (   $picture
            && (   $picture == self::$defaultImage
                || !self::moveImage($picture))) {
            $picture = '';
        }
        $objCategory->picture($picture);
        $objCategory->virtual($virtual);
        if (!$objCategory->store()) {
            return \Message::error($_ARRAYLANG['TXT_SHOP_DATABASE_QUERY_ERROR']);
        }
        if ($picture) {
//DBG::log("store_category(): Making thumb");
            $objImage = new \ImageManager();
            if (!$objImage->_createThumbWhq(
                \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteImagesShopPath().'/',
                \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteImagesShopWebPath().'/',
                $picture,
                \Cx\Core\Setting\Controller\Setting::getValue('thumbnail_max_width','Shop'),
                \Cx\Core\Setting\Controller\Setting::getValue('thumbnail_max_height','Shop'),
                \Cx\Core\Setting\Controller\Setting::getValue('thumbnail_quality','Shop')
            )) {
                \Message::warning($_ARRAYLANG['TXT_SHOP_ERROR_CREATING_CATEGORY_THUMBNAIL']);
            }
        }
        // Avoid showing/editing the modified ShopCategory again.
        // view_categories() tests the $_REQUEST array!
        unset($_REQUEST['category_id']);
        return \Message::ok($_ARRAYLANG['TXT_SHOP_CATEGORY_STORED_SUCCESSFULLY']);
    }


    /**
     * Update all ShopCategories with the data provided by the request.
     * @return  boolean                 True on success, null on noop,
     *                                  false otherwise.
     * @author  Reto Kohli <reto.kohli@comvation.com> (parts)
     */
    function update_categories()
    {
        global $_ARRAYLANG;

        if (empty($_POST['bcategories'])) {
            return null;
        }
        $success = null;
        foreach ($_POST['update_category_id'] as $category_id) {
            $ord = $_POST['ord'][$category_id];
            $ord_old = $_POST['ord_old'][$category_id];
//            $active = isset($_POST['active'][$category_id]);
//            $active_old = intval($_POST['active_old'][$category_id]);
//            $virtual = isset($_POST['virtual'][$category_id]);
//            $virtual_old = isset($_POST['virtual_old'][$category_id]);
//DBG::log("Shopmanager::update_categories(): ord $ord, ord_old $ord_old, active $active, active_old $active_old"); // virtual $virtual, virtual_old $virtual_old,
            if ($ord != $ord_old
//             || $active != $active_old
//             || $virtual != $virtual_old
            ) {
                $objCategory = ShopCategory::getById($category_id);
                $objCategory->ord($ord);
//                $objCategory->active($active);
//                $objCategory->virtual($virtual);
                if ($objCategory->store()) {
                    if (is_null($success)) $success = true;
                } else {
                    // Mind that this graciously returns false.
                    $success = \Message::error(sprintf(
                        $_ARRAYLANG['TXT_SHOP_CATEGORY_ERROR_UPDATING'],
                        $category_id));
                }
            }
        }
        if ($success) {
            \Message::ok(
                $_ARRAYLANG['TXT_SHOP_CATEGORIES_UPDATED_SUCCESSFULLY']);
        }
        return $success;
    }


    /**
     * Deletes one or more ShopCategories
     *
     * Only succeeds if there are no subcategories, and if all contained
     * Products can be deleted as well.  Products that are present in any
     * order won't be deleted.
     * @param   integer     $category_id    The optional ShopCategory ID.
     *                                      If this is no valid ID, it is taken
     *                                      from the request parameters
     *                                      $_GET['delete_category_id'], or
     *                                      $_POST['selectedCatId'], in this
     *                                      order.
     * @return  boolean                     True on success, null on noop,
     *                                      false otherwise.
     */
    function delete_categories($category_id=0)
    {
        global $objDatabase, $_ARRAYLANG;

        $arrCategoryId = array();
        $deleted = false;
        if (empty($category_id)) {
            if (!empty($_GET['delete_category_id'])) {
                array_push($arrCategoryId, $_GET['delete_category_id']);
            } elseif (!empty($_POST['selected_category_id'])
                   && is_array($_POST['selected_category_id'])) {
                $arrCategoryId = $_POST['selected_category_id'];
            }
        } else {
            array_push($arrCategoryId, $category_id);
        }
        if (empty($arrCategoryId)) {
            return null;
        }
        // When multiple IDs are posted, the list must be reversed,
        // so subcategories are removed first
        $arrCategoryId = array_reverse($arrCategoryId);
//DBG::log("delete_categories($category_id): Got ".var_export($arrCategoryId, true));
        foreach ($arrCategoryId as $category_id) {
            // Check whether this category has subcategories
            $arrChildId =
                ShopCategories::getChildCategoryIdArray($category_id, false);
//DBG::log("delete_categories($category_id): Children of $category_id: ".var_export($arrChildId, true));
            if (count($arrChildId)) {
                \Message::warning(
                    $_ARRAYLANG['TXT_CATEGORY_NOT_DELETED_BECAUSE_IN_USE'].
                    "&nbsp;(".$_ARRAYLANG['TXT_CATEGORY']."&nbsp;".$category_id.")");
                continue;
            }
            // Get Products in this category
            $count = 1e9;
            $arrProducts = Products::getByShopParams($count, 0, null,
                $category_id, null, null, false, false, '', null, true);
//DBG::log("delete_categories($category_id): Products in $category_id: ".var_export($arrProducts, true));
            // Delete the products in the category
            foreach ($arrProducts as $objProduct) {
                // Check whether there are orders with this Product ID
                $product_id = $objProduct->id();
                $query = "
                    SELECT 1
                      FROM ".DBPREFIX."module_shop".MODULE_INDEX."_order_items
                     WHERE product_id=$product_id";
                $objResult = $objDatabase->Execute($query);
                if (!$objResult || $objResult->RecordCount()) {
                    \Message::error(
                        $_ARRAYLANG['TXT_COULD_NOT_DELETE_ALL_PRODUCTS'].
                        "&nbsp;(".
                        sprintf($_ARRAYLANG['TXT_SHOP_CATEGORY_ID_FORMAT'],
                            $category_id).")");
                    continue 2;
                }
            }
            if (Products::deleteByShopCategory($category_id) === false) {
                \Message::error($_ARRAYLANG['TXT_ERROR_DELETING_PRODUCT'].
                    "&nbsp;(".$_ARRAYLANG['TXT_CATEGORY']."&nbsp;".$category_id.")");
                continue;
            }
            // Delete the Category now
            $result = ShopCategories::deleteById($category_id);
            if ($result === null) {
                continue;
            }
            if ($result === false) {
                return self::error_database();
            }
            $deleted = true;
        }
        if ($deleted) {
            return \Message::ok($_ARRAYLANG['TXT_DELETED_CATEGORY_AND_PRODUCTS']);
        }
        return null;
    }


    /**
     * Toggles the active state of a ShopCategory
     *
     * The ShopCategory ID may be present in $_REQUEST['toggle_category_id'].
     * If it's not, returns NULL immediately.
     * Otherwise, will add a message indicating success or failure,
     * and redirect back to the category overview.
     * @global  array       $_ARRAYLANG
     * @return  boolean                     Null on noop
     */
    function toggle_category()
    {
        global $_ARRAYLANG;

        if (empty($_REQUEST['toggle_category_id'])) return NULL;
        $category_id = intval($_REQUEST['toggle_category_id']);
        $result = ShopCategories::toggleStatusById($category_id);
        if (is_null($result)) {
            // NOOP
            return;
        }
        if ($result) {
            \Message::ok($_ARRAYLANG['TXT_SHOP_CATEGORY_UPDATED_SUCCESSFULLY']);
        } else {
            \Message::error(sprintf(
                $_ARRAYLANG['TXT_SHOP_CATEGORY_ERROR_UPDATING'], $category_id));
        }
        \Cx\Core\Csrf\Controller\Csrf::redirect('index.php?cmd=Shop&act=categories');
    }


    /**
     * Delete one or more Products from the database.
     *
     * Checks whether either of the request parameters 'id' (integer) or
     * 'selectedProductId' (array) is present, in that order, and takes the
     * ID of the Product(s) from the first one available, if any.
     * If none of them is set, uses the value of the $product_id argument,
     * if that is valid.
     * Note that this method returns true if no record was deleted because
     * no ID was supplied.
     * @param   integer     $product_id     The optional Product ID
     *                                      to be deleted.
     * @return  boolean                     True on success, false otherwise
     */
    function delete_product($product_id=0)
    {
        $arrProductId = array();
        if (empty($product_id)) {
            if (!empty($_REQUEST['id'])) {
                $arrProductId[] = $_REQUEST['id'];
            } elseif (!empty($_REQUEST['selectedProductId'])) {
                // This argument is an array!
                $arrProductId = $_REQUEST['selectedProductId'];
            }
        } else {
            $arrProductId[] = $product_id;
        }

        $result = true;
        if (count($arrProductId) > 0) {
            foreach ($arrProductId as $product_id) {
                $objProduct = Product::getById($product_id);
                if (!$objProduct) continue;
//                $code = $objProduct->code();
//                if (empty($code)) {
                    $result &= $objProduct->delete();
//                } else {
//                    $result &= !Products::deleteByCode($objProduct->code());
//                }
            }
        }
        return $result;
    }

    /**
     * Manage products
     *
     * Add and edit products
     * @access  public
     * @return  string
     * @author  Reto Kohli <reto.kohli@comvation.com> (parts)
     */
    function view_product_edit()
    {
        global $_ARRAYLANG;

        self::store_product();
        $product_id = (isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0);
        $objProduct = null;
        self::$objTemplate->addBlockfile('SHOP_PRODUCTS_FILE',
            'shop_products_block', 'module_shop_product_manage.html');
        self::$objTemplate->setGlobalVariable($_ARRAYLANG);
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        self::$objTemplate->setVariable(array(
            'SHOP_DELETE_ICON' => $cx->getCodeBaseCoreWebPath() . '/Core/View/Media/icons/delete.gif',
            'SHOP_NO_PICTURE_ICON' => self::$defaultImage
        ));
        if ($product_id > 0) {
            $objProduct = Product::getById($product_id);
        }
        if (!$objProduct) {
            $objProduct = new Product('', 0, '', '', 0, 1, 0, 0);
        }
        $this->viewpart_product_attributes($product_id);
        $arrImages = Products::get_image_array_from_base64($objProduct->pictures());
// Virtual Categories are disabled FTTB
//        $flagsSelection =
//            ShopCategories::getVirtualCategoriesSelectionForFlags(
//                $objProduct->flags()
//            );
//        if ($flagsSelection) {
//            self::$objTemplate->setVariable(
//                'SHOP_FLAGS_SELECTION', $flagsSelection);
//        }
//
        // media browser
        $mediaBrowserOptions = array(
            'type'                      => 'button',
            'startmediatype'            => 'shop',
            'views'                     => 'filebrowser',
            'id'                        => 'media_browser_shop',
            'style'                     => 'display:none'
        );

        self::$objTemplate->setVariable(array(
            'MEDIABROWSER_BUTTON' => self::getMediaBrowserButton($mediaBrowserOptions, 'setSelectedImage')
        ));

        $distribution = $objProduct->distribution();
        // Available active frontend groups, and those assigned to the product
        $objGroup = \FWUser::getFWUserObject()->objGroup->getGroups(
            array('type' => 'frontend', 'is_active' => true),
            array('group_id' => 'asc'));
        $usergroup_ids = $objProduct->usergroup_ids();
        $arrAssignedFrontendGroupId = explode(',', $usergroup_ids);
        $strActiveFrontendGroupOptions = '';
        $strAssignedFrontendGroupOptions = '';
        while ($objGroup && !$objGroup->EOF) {
            $strOption =
                '<option value="'.$objGroup->getId().'">'.
                htmlentities($objGroup->getName(), ENT_QUOTES, CONTREXX_CHARSET).
                '</option>';
            if (in_array($objGroup->getId(), $arrAssignedFrontendGroupId)) {
                $strAssignedFrontendGroupOptions .= $strOption;
            } else {
                $strActiveFrontendGroupOptions .= $strOption;
            }
            $objGroup->next();
        }
        $discount_group_count_id = $objProduct->group_id();
        $discount_group_article_id = $objProduct->article_id();
        $keywords = $objProduct->keywords();
//die($objProduct->category_id());
        // Product assigned to multiple Categories
        $arrAssignedCategories =
            ShopCategories::getAssignedShopCategoriesMenuoptions(
                $objProduct->category_id());
        // Date format for Datepicker:
        // Clear the date if none is set; there's no point in displaying
        // "01/01/1970" instead
        $start_date = $end_date = '';
        $start_time = strtotime($objProduct->date_start());
        // Note that the check for ">0" is necessary, as some systems return
        // crazy values for empty dates (it may even fail like this)!
        if ($start_time > 0) $start_date =
            date(ASCMS_DATE_FORMAT_DATE, $start_time);
        $end_time = strtotime($objProduct->date_end());
        if ($end_time > 0) $end_date = date(ASCMS_DATE_FORMAT_DATE, $end_time);
//DBG::log("Dates from ".$objProduct->date_start()." ($start_time, $start_date) to ".$objProduct->date_start()." ($end_time, $end_date)");
        $websiteImagesShopPath    = $cx->getWebsiteImagesShopPath() . '/';
        $websiteImagesShopWebPath = $cx->getWebsiteImagesShopWebPath() . '/';
        self::$objTemplate->setVariable(array(
            'SHOP_PRODUCT_ID' => (isset($_REQUEST['new']) ? 0 : $objProduct->id()),
            'SHOP_PRODUCT_CODE' => contrexx_raw2xhtml($objProduct->code()),
// Unused
//            'SHOP_DATE' => date('Y-m-d H:m'),
            'SHOP_PRODUCT_NAME' => contrexx_raw2xhtml($objProduct->name()),
            'SHOP_CATEGORIES_ASSIGNED' => $arrAssignedCategories['assigned'],
            'SHOP_CATEGORIES_AVAILABLE' => $arrAssignedCategories['available'],
            'SHOP_CUSTOMER_PRICE' => contrexx_raw2xhtml(
                Currency::formatPrice($objProduct->price())),
            'SHOP_RESELLER_PRICE' => contrexx_raw2xhtml(
                Currency::formatPrice($objProduct->resellerprice())),
            'SHOP_DISCOUNT' => contrexx_raw2xhtml(
                Currency::formatPrice($objProduct->discountprice())),
            'SHOP_SPECIAL_OFFER' => ($objProduct->discount_active() ? \Html::ATTRIBUTE_CHECKED : ''),
            'SHOP_VAT_MENUOPTIONS' => Vat::getMenuoptions(
                $objProduct->vat_id(), true),
            'SHOP_SHORT_DESCRIPTION' => new \Cx\Core\Wysiwyg\Wysiwyg(
                'short', $objProduct->short()),
            'SHOP_DESCRIPTION' => new \Cx\Core\Wysiwyg\Wysiwyg(
                'long', $objProduct->long(), 'full'),
            'SHOP_STOCK' => $objProduct->stock(),
            'SHOP_MIN_ORDER_QUANTITY' => $objProduct->minimum_order_quantity(),
            'SHOP_MANUFACTURER_URL' => contrexx_raw2xhtml($objProduct->uri()),
// TODO: Any attributes for the datepicker input?
            'SHOP_DATE_START' => \Html::getDatepicker('date_start',
                array('defaultDate' => $start_date),
                ''),
            'SHOP_DATE_END' => \Html::getDatepicker('date_end',
                array('defaultDate' => $end_date),
                ''),
            'SHOP_ARTICLE_ACTIVE' => ($objProduct->active() ? \Html::ATTRIBUTE_CHECKED : ''),
            'SHOP_B2B' => ($objProduct->b2b() ? \Html::ATTRIBUTE_CHECKED : ''),
            'SHOP_B2C' => ($objProduct->b2c() ? \Html::ATTRIBUTE_CHECKED : ''),
            'SHOP_STOCK_VISIBILITY' => ($objProduct->stock_visible()
                ? \Html::ATTRIBUTE_CHECKED : ''),
            'SHOP_MANUFACTURER_MENUOPTIONS' =>
                Manufacturer::getMenuoptions($objProduct->manufacturer_id(), true),
            'SHOP_PICTURE1_IMG_SRC' =>
                (   !empty($arrImages[1]['img'])
                 && is_file(\ImageManager::getThumbnailFilename($websiteImagesShopPath . $arrImages[1]['img']))
                        ? contrexx_raw2encodedUrl(\ImageManager::getThumbnailFilename($websiteImagesShopWebPath . $arrImages[1]['img']))
                    : self::$defaultImage),
            'SHOP_PICTURE2_IMG_SRC' =>
                (   !empty($arrImages[2]['img'])
                 && is_file(\ImageManager::getThumbnailFilename($websiteImagesShopPath . $arrImages[2]['img']))
                        ? contrexx_raw2encodedUrl(\ImageManager::getThumbnailFilename($websiteImagesShopWebPath . $arrImages[2]['img']))
                    : self::$defaultImage),
            'SHOP_PICTURE3_IMG_SRC' =>
                (   !empty($arrImages[3]['img'])
                 && is_file(\ImageManager::getThumbnailFilename($websiteImagesShopPath . $arrImages[3]['img']))
                    ? contrexx_raw2encodedUrl(\ImageManager::getThumbnailFilename($websiteImagesShopWebPath . $arrImages[3]['img']))
                    : self::$defaultImage),
            'SHOP_PICTURE1_IMG_SRC_NO_THUMB' =>
                (   !empty($arrImages[1]['img'])
                 && is_file($websiteImagesShopPath . $arrImages[1]['img'])
                    ? $websiteImagesShopWebPath . $arrImages[1]['img']
                    : self::$defaultImage),
            'SHOP_PICTURE2_IMG_SRC_NO_THUMB' =>
                (   !empty($arrImages[2]['img'])
                 && is_file($websiteImagesShopPath . $arrImages[2]['img'])
                    ? $websiteImagesShopWebPath . $arrImages[2]['img']
                    : self::$defaultImage),
            'SHOP_PICTURE3_IMG_SRC_NO_THUMB' =>
                (   !empty($arrImages[3]['img'])
                 && is_file($websiteImagesShopPath . $arrImages[3]['img'])
                    ? $websiteImagesShopWebPath . $arrImages[3]['img']
                    : self::$defaultImage),
            'SHOP_PICTURE1_IMG_WIDTH' => $arrImages[1]['width'],
            'SHOP_PICTURE1_IMG_HEIGHT' => $arrImages[1]['height'],
            'SHOP_PICTURE2_IMG_WIDTH' => $arrImages[2]['width'],
            'SHOP_PICTURE2_IMG_HEIGHT' => $arrImages[2]['height'],
            'SHOP_PICTURE3_IMG_WIDTH' => $arrImages[3]['width'],
            'SHOP_PICTURE3_IMG_HEIGHT' => $arrImages[3]['height'],
            'SHOP_DISTRIBUTION_MENU' => Distribution::getDistributionMenu(
                $objProduct->distribution(), 'distribution',
                'distributionChanged();', 'style="width: 220px"'),
            'SHOP_WEIGHT' => ($distribution == 'delivery'
                ? Weight::getWeightString($objProduct->weight()) : '0 g'),
            // User group menu, returns 'userGroupId'
            'SHOP_GROUPS_AVAILABLE' => $strActiveFrontendGroupOptions,
            'SHOP_GROUPS_ASSIGNED' => $strAssignedFrontendGroupOptions,
            'SHOP_ACCOUNT_VALIDITY_OPTIONS' => \FWUser::getValidityMenuOptions(
                ($distribution == 'download' ? $objProduct->weight() : 0)),
            'SHOP_CREATE_ACCOUNT_YES_CHECKED' =>
                (empty($usergroup_ids) ? '' : \Html::ATTRIBUTE_CHECKED),
            'SHOP_CREATE_ACCOUNT_NO_CHECKED' =>
                (empty($usergroup_ids) ? \Html::ATTRIBUTE_CHECKED : ''),
            'SHOP_DISCOUNT_GROUP_COUNT_MENU_OPTIONS' =>
                Discount::getMenuOptionsGroupCount($discount_group_count_id),
            'SHOP_DISCOUNT_GROUP_ARTICLE_MENU_OPTIONS' =>
                Discount::getMenuOptionsGroupArticle($discount_group_article_id),
            'SHOP_KEYWORDS' => contrexx_raw2xhtml($keywords),
            // Enable JavaScript functionality for the weight if enabled
            'SHOP_WEIGHT_ENABLED' => (\Cx\Core\Setting\Controller\Setting::getValue('weight_enable','Shop')
                ? 1 : 0),
        ));

        $activateChildrenOfProductAttribute = \Cx\Core\Setting\Controller\Setting::getValue('activate_product_attribute_children','Shop');
        \ContrexxJavascript::getInstance()->setVariable('activate_product_attribute_children', $activateChildrenOfProductAttribute, 'shop');

        return true;
    }


    /**
     * Stores the posted Product, if any
     * @return  boolean           True on success, null on noop, false otherwise
     */
    static function store_product()
    {
        global $_ARRAYLANG;

        if (!isset($_POST['bstore'])) {
            return null;
        }
        $product_name = contrexx_input2raw($_POST['product_name']);
        $product_code = contrexx_input2raw($_POST['product_code']);
        // Multiple Categories
        $category_id = isset($_POST['shopCategoriesAssigned'])
                        ? contrexx_input2raw(join(',', $_POST['shopCategoriesAssigned']))
                        : '';
        $customer_price = $_POST['customer_price'];
        $reseller_price = $_POST['reseller_price'];
        $discount_active = !empty($_POST['discount_active']);
        $discount_price = $_POST['discount_price'];
//DBG::log("ShopManager::store_product(): customer_price $customer_price, reseller_price $reseller_price, discount_price $discount_price");
        $vat_id = $_POST['vat_id'];
        $short = contrexx_input2raw($_POST['short']);
        $long = contrexx_input2raw($_POST['long']);
        $stock = $_POST['stock'];
        $stock_visible = !empty($_POST['stock_visible']);
        $uri = contrexx_input2raw($_POST['uri']);
        $active = !empty($_POST['articleActive']);
        $b2b = !empty($_POST['B2B']);
        $b2c = !empty($_POST['B2C']);
        $date_start = contrexx_input2raw($_POST['date_start']);
        $date_end = contrexx_input2raw($_POST['date_end']);
        $manufacturer_id = isset($_POST['manufacturer_id']) ? contrexx_input2int($_POST['manufacturer_id']) : 0;
        $minimum_order_quantity = $_POST['minimum_order_quantity'];
// Currently not used on the detail page
//        $flags = (isset($_POST['Flags'])
//                ? join(' ', $_POST['Flags']) : '');
        $distribution = $_POST['distribution'];
        // Different meaning of the "weight" field for downloads!
        // The getWeight() method will treat purely numeric values
        // like the validity period (in days) the same as a weight
        // without its unit and simply return its integer value.
        $weight = ($distribution == 'delivery'
            ? Weight::getWeight($_POST['weight'])
            : $_POST['accountValidity']);
        // Assigned frontend groups for protected downloads
        $usergroup_ids = (isset($_POST['groupsAssigned'])
            ? implode(',', $_POST['groupsAssigned']) : '');
        $discount_group_count_id = $_POST['discount_group_count_id'];
        $discount_group_article_id = $_POST['discount_group_article_id'];
//DBG::log("ShopManager::store_product(): Set \$discount_group_article_id to $discount_group_article_id");
        $keywords = contrexx_input2raw($_POST['keywords']);
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();

        for ($i = 1; $i <= 3; ++$i) {
            // Images outside the above directory are copied to the shop image folder.
            // Note that the image paths below do not include the document root, but
            // are relative to it.
            $picture = contrexx_input2raw($_POST['productImage'.$i]);
            // Ignore the picture if it's the default image!
            // Storing it would be pointless.
            // Images outside the above directory are copied to the shop image folder.
            // Note that the image paths below do not include the document root, but
            // are relative to it.
            if (   $picture == self::$defaultImage
                || !self::moveImage($picture)) {
                $picture = '';
            }
            // Update the posted path (used below)
            $_POST['productImage'.$i] = $picture;

            //Set the image width and height If empty
            if (   !empty($_POST['productImage' . $i . '_width'])
                && !empty($_POST['productImage' . $i . '_height'])
            ) {
                continue;
            }

            $picturePath = $cx->getWebsiteImagesShopPath(). '/' . $picture;
            if (!\Cx\Lib\FileSystem\FileSystem::exists($picturePath)) {
                continue;
            }

            $pictureSize = getimagesize($picturePath);
            $_POST['productImage' . $i . '_width']  = $pictureSize[0];
            $_POST['productImage' . $i . '_height'] = $pictureSize[1];
        }

        // add all to pictures DBstring
        $imageName =
                 base64_encode($_POST['productImage1'])
            .'?'.base64_encode($_POST['productImage1_width'])
            .'?'.base64_encode($_POST['productImage1_height'])
            .':'.base64_encode($_POST['productImage2'])
            .'?'.base64_encode($_POST['productImage2_width'])
            .'?'.base64_encode($_POST['productImage2_height'])
            .':'.base64_encode($_POST['productImage3'])
            .'?'.base64_encode($_POST['productImage3_width'])
            .'?'.base64_encode($_POST['productImage3_height']);

        // Note that the flags of the Product *MUST NOT* be changed
        // when inserting or updating the Product data, as the original
        // flags are needed for their own update later.

        $objProduct = null;
        $product_id = intval($_POST['id']);
        if ($product_id) {
            $objProduct = Product::getById($product_id);
        }
        $new = false;
        if (!$objProduct) {
            $new = true;
            $objProduct = new Product(
                $product_code,
                $category_id,
                $product_name,
                $distribution,
                $customer_price,
                $active,
                0,
                $weight
            );
            if (!$objProduct->store()) {
                return \Message::error($_ARRAYLANG['TXT_SHOP_PRODUCT_ERROR_STORING']);
            }
//            $product_id = $objProduct->id();
        }

        // Apply the changes to all Products with the same Product code.
// Note: This is disabled for the time being, as virtual categories are, too.
//        if ($product_code != '') {
//            $arrProduct = Products::getByCustomId($product_code);
//        } else {
//            $arrProduct = array($objProduct);
//        }
//        if (!is_array($arrProduct)) return false;
//        foreach ($arrProduct as $objProduct) {
            // Update each product
            $objProduct->code($product_code);
// NOTE: Only change the parent ShopCategory for a Product
// that is in a real ShopCategory.
            $objProduct->category_id($category_id);
            $objProduct->name($product_name);
            $objProduct->distribution($distribution);
            $objProduct->price($customer_price);
            $objProduct->active($active);
// On the overview only: $objProduct->ord();
            $objProduct->weight($weight);
            $objProduct->resellerprice($reseller_price);
            $objProduct->discount_active($discount_active);
            $objProduct->discountprice($discount_price);
            $objProduct->vat_id($vat_id);
            $objProduct->short($short);
            $objProduct->long($long);
            $objProduct->stock($stock);
            $objProduct->minimum_order_quantity($minimum_order_quantity);
            $objProduct->stock_visible($stock_visible);
            $objProduct->uri($uri);
            $objProduct->b2b($b2b);
            $objProduct->b2c($b2c);
            $objProduct->date_start($date_start);
            $objProduct->date_end($date_end);
            $objProduct->manufacturer_id($manufacturer_id);
            $objProduct->pictures($imageName);
// Currently not used on the detail page
//                $objProduct->flags($flags);
            $objProduct->usergroup_ids($usergroup_ids);
            $objProduct->group_id($discount_group_count_id);
            $objProduct->article_id($discount_group_article_id);
            $objProduct->keywords($keywords);
//DBG::log("ShopManager::store_product(): Product: reseller_price ".$objProduct->resellerprice());

            // Remove old Product Attributes.
            // They are re-added below.
            $objProduct->clearAttributes();
            // Add current product attributes
            if (   isset($_POST['options'])
                && is_array($_POST['options'])) {
                foreach ($_POST['options'] as $valueId => $nameId) {
                    $order = intval($_POST['productOptionsSortId'][$nameId]);
                    $objProduct->addAttribute(intval($valueId), $order);
                }
            }
            // Mind that this will always be an *update*, see the call to
            // store() above.
            if (!$objProduct->store()) {
                return \Message::error($_ARRAYLANG['TXT_SHOP_PRODUCT_ERROR_STORING']);
            }
//        }
        // Add/remove Categories and Products to/from
        // virtual ShopCategories.
        // Note that this *MUST* be called *AFTER* the Product is updated
        // or inserted.
// Virtual categories are disabled for the time being
//        Products::changeFlagsByProductCode(
//            $product_code, $flags
//        );
        $objImage = new \ImageManager();
        $arrImages = Products::get_image_array_from_base64($imageName);
        // Create thumbnails if not available, or update them
        foreach ($arrImages as $arrImage) {
            if (   !empty($arrImage['img'])
                && $arrImage['img'] != ShopLibrary::noPictureName) {
                if (!$objImage->_createThumbWhq(
                    \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteImagesShopPath().'/',
                    \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteImagesShopWebPath().'/',
                    $arrImage['img'],
                    \Cx\Core\Setting\Controller\Setting::getValue('thumbnail_max_width','Shop'),
                    \Cx\Core\Setting\Controller\Setting::getValue('thumbnail_max_height','Shop'),
                    \Cx\Core\Setting\Controller\Setting::getValue('thumbnail_quality','Shop')
                )) {
                    \Message::error(sprintf($_ARRAYLANG['TXT_SHOP_COULD_NOT_CREATE_THUMBNAIL'],
                        $arrImage['img']));
                }
            }
        }
        \Message::ok($new
            ? $_ARRAYLANG['TXT_DATA_RECORD_ADDED_SUCCESSFUL']
            : $_ARRAYLANG['TXT_DATA_RECORD_UPDATED_SUCCESSFUL']);

        switch ($_POST['afterStoreAction']) {
          case 'newEmpty':
            \Cx\Core\Csrf\Controller\Csrf::redirect(
                'index.php?cmd=Shop'.MODULE_INDEX.'&act=products&tpl=manage');
          case 'newTemplate':
            \Cx\Core\Csrf\Controller\Csrf::redirect('index.php?cmd=Shop'.MODULE_INDEX.
                '&act=products&tpl=manage&id='.$objProduct->id().'&new=1');
        }
        \Cx\Core\Csrf\Controller\Csrf::redirect('index.php?cmd=Shop'.MODULE_INDEX.'&act=products');
        // Never reached
        return true;
    }


    /**
     * Show the stored orders
     * @access  public
     * @global  ADONewConnection  $objDatabase    Database connection object
     * @global  array   $_ARRAYLANG
     * @global  array   $_CONFIG
     * @author  Reto Kohli <reto.kohli@comvation.com> (parts)
     */
    function view_order_overview()
    {
        global $_ARRAYLANG;

        self::$pageTitle = $_ARRAYLANG['TXT_ORDERS'];
        // A return value of null means that nothing had to be done
        Orders::updateStatusFromGet();
        self::$objTemplate = null;
        return Orders::view_list(self::$objTemplate);
    }


    /**
     * OBSOLETE -- Moved to Order class
     * Set up details of the selected order
     * @access  public
     * @param   boolean           $edit           Edit if true, view otherwise
     * @global  ADONewConnection  $objDatabase    Database connection object
     * @global  array             $_ARRAYLANG     Language array
     * @author  Reto Kohli <reto.kohli@comvation.com> (parts)
     */
    function view_order_details($edit=false)
    {
        global $_ARRAYLANG;

        // Storing can only fail if an order is posted.
        // If there is nothing to do, it will return null.
        $result = Order::storeFromPost();
        if ($result === false) {
            // Edit again after failing to store
            $edit = true;
        } elseif ($result === true) {
            $edit = false;
        }
        if ($edit) {
            self::$pageTitle = $_ARRAYLANG['TXT_EDIT_ORDER'];
            self::$objTemplate->loadTemplateFile('module_shop_order_edit.html');
        } else {
            self::$pageTitle = $_ARRAYLANG['TXT_ORDER_DETAILS'];
            self::$objTemplate->loadTemplateFile('module_shop_order_details.html');
        }
        return Order::view_detail(self::$objTemplate, $edit);
    }


    /**
     * Delete one or more Orders
     *
     * If the $order_id parameter value is empty, checks $_GET['order_id']
     * and $_POST['selectedOrderId'] in this order for Order IDs.
     * Backend use only.  Redirects to the Order overview
     * @version 3.0.0
     * @param   integer     $order_id   The optional Order ID to be deleted
     * @return  void
     */
    function delete_order($order_id=null)
    {
        global $_ARRAYLANG;

        $arrOrderId = array();

        // prepare the array $arrOrderId with the ids of the orders to delete
        if (empty($order_id)) {
            if (!empty($_GET['order_id'])) {
                array_push($arrOrderId, $_GET['order_id']);
            } elseif (!empty($_POST['selectedOrderId'])) {
                $arrOrderId = $_POST['selectedOrderId'];
            }
        } else {
            array_push($arrOrderId, $order_id);
        }
        if (empty($arrOrderId)) return null;
        $stockUpdate = !empty($_GET['stock_update']);
        $result = true;
        foreach ($arrOrderId as $oId) {
            $result &= Order::deleteById($oId, $stockUpdate);
        }
        if ($result) {
            \Message::ok($_ARRAYLANG['TXT_ORDER_DELETED']);
        }
// TODO: Add error message
        \Cx\Core\Csrf\Controller\Csrf::redirect('index.php?cmd=Shop&act=orders');
    }


    /**
     * Show Customers
     */
    function view_customers()
    {
        global $_ARRAYLANG;

        $template = (isset($_GET['tpl']) ? $_GET['tpl'] : '');
        if ($template == 'discounts') {
            return $this->view_customer_discounts();
        }
        if ($template == 'groups') {
            return $this->view_customer_groups();
        }
        $this->toggleCustomer();
        $i = 0;
        self::$objTemplate->loadTemplateFile("module_shop_customers.html");
        $customer_active = null;
        $customer_type = null;
        $searchterm = null;
        $listletter = null;
        $group_id_customer = \Cx\Core\Setting\Controller\Setting::getValue('usergroup_id_customer','Shop');
        $group_id_reseller = \Cx\Core\Setting\Controller\Setting::getValue('usergroup_id_reseller','Shop');
        $uri = \Html::getRelativeUri();
// TODO: Strip what URI parameters?
        \Html::stripUriParam($uri, 'active');
        \Html::stripUriParam($uri, 'customer_type');
        \Html::stripUriParam($uri, 'searchterm');
        \Html::stripUriParam($uri, 'listletter');
        $uri_sorting = $uri;
        $arrFilter = array();
        if (   isset($_REQUEST['active'])
            && $_REQUEST['active'] != '') {
            $customer_active = intval($_REQUEST['active']);
            $arrFilter['active'] = $customer_active;
            \Html::replaceUriParameter($uri_sorting, "active=$customer_active");
        }
        if (   isset($_REQUEST['customer_type'])
            && $_REQUEST['customer_type'] != '') {
            $customer_type = intval($_REQUEST['customer_type']);
            switch ($customer_type) {
              case 0:
                $arrFilter['group'] = array($group_id_customer);
                break;
              case 1:
                $arrFilter['group'] = array($group_id_reseller);
                break;
            }
            \Html::replaceUriParameter($uri_sorting, "customer_type=$customer_type");
        } else {
            $arrFilter['group'] = array($group_id_customer, $group_id_reseller);
        }
//DBG::log("Group filter: ".var_export($arrFilter, true));
        if (!empty($_REQUEST['searchterm'])) {
            $searchterm = trim(strip_tags(contrexx_input2raw(
                $_REQUEST['searchterm'])));
            \Html::replaceUriParameter($uri_sorting, "searchterm=$searchterm");
        } elseif (!empty($_REQUEST['listletter'])) {
            $listletter = contrexx_input2raw($_REQUEST['listletter']);
            \Html::replaceUriParameter($uri_sorting, "listletter=$listletter");
        }
        $arrSorting = array(
            'id' => $_ARRAYLANG['TXT_SHOP_CUSTOMER_ID'],
            'company' => $_ARRAYLANG['TXT_SHOP_CUSTOMER_COMPANY'],
            'firstname' => $_ARRAYLANG['TXT_SHOP_CUSTOMER_FIRSTNAME'],
            'lastname' => $_ARRAYLANG['TXT_SHOP_CUSTOMER_LASTNAME'],
            'address' => $_ARRAYLANG['TXT_SHOP_CUSTOMER_ADDRESS'],
            'zip' => $_ARRAYLANG['TXT_SHOP_CUSTOMER_ZIP'],
            'city' => $_ARRAYLANG['TXT_SHOP_CUSTOMER_CITY'],
            'phone' => $_ARRAYLANG['TXT_SHOP_CUSTOMER_PHONE'],
            'email' => $_ARRAYLANG['TXT_SHOP_CUSTOMER_EMAIL'],
            'active' => $_ARRAYLANG['TXT_SHOP_CUSTOMER_ACTIVE'],
        );
        $objSorting = new \Sorting($uri_sorting, $arrSorting, false,
            'order_shop_customer');
        self::$objTemplate->setVariable(array(
            'SHOP_HEADING_CUSTOMER_ID' => $objSorting->getHeaderForField('id'),
            'SHOP_HEADING_CUSTOMER_COMPANY' => $objSorting->getHeaderForField('company'),
            'SHOP_HEADING_CUSTOMER_FIRSTNAME' => $objSorting->getHeaderForField('firstname'),
            'SHOP_HEADING_CUSTOMER_LASTNAME' => $objSorting->getHeaderForField('lastname'),
            'SHOP_HEADING_CUSTOMER_ADDRESS' => $objSorting->getHeaderForField('address'),
            'SHOP_HEADING_CUSTOMER_ZIP' => $objSorting->getHeaderForField('zip'),
            'SHOP_HEADING_CUSTOMER_CITY' => $objSorting->getHeaderForField('city'),
            'SHOP_HEADING_CUSTOMER_PHONE' => $objSorting->getHeaderForField('phone'),
            'SHOP_HEADING_CUSTOMER_EMAIL' => $objSorting->getHeaderForField('email'),
            'SHOP_HEADING_CUSTOMER_ACTIVE' => $objSorting->getHeaderForField('active'),
        ));
        $limit = \Cx\Core\Setting\Controller\Setting::getValue('numof_customers_per_page_backend','Shop');
        $objCustomer = Customers::get(
            $arrFilter, ($listletter ? $listletter.'%' : $searchterm),
            array($objSorting->getOrderField() => $objSorting->getOrderDirection()),
            $limit, \Paging::getPosition());
        $count = ($objCustomer ? $objCustomer->getFilteredSearchUserCount() : 0);
        while ($objCustomer && !$objCustomer->EOF) {
//DBG::log("Customer: ".var_export($objCustomer, true));
            self::$objTemplate->setVariable(array(
                'SHOP_ROWCLASS' => 'row'.(++$i % 2 + 1),
                'SHOP_CUSTOMERID' => $objCustomer->getId(),
                'SHOP_COMPANY' => $objCustomer->company(),
                'SHOP_FIRSTNAME' => $objCustomer->firstname(),
                'SHOP_LASTNAME' => $objCustomer->lastname(),
                'SHOP_ADDRESS' => $objCustomer->address(),
                'SHOP_ZIP' => $objCustomer->zip(),
                'SHOP_CITY' => $objCustomer->city(),
                'SHOP_PHONE' => $objCustomer->phone(),
                'SHOP_EMAIL' => $objCustomer->email(),
                'SHOP_CUSTOMER_STATUS_IMAGE' =>
                    ($objCustomer->active() ? 'led_green.gif' : 'led_red.gif'),
                'SHOP_CUSTOMER_ACTIVE' => ($objCustomer->active()
                    ? $_ARRAYLANG['TXT_ACTIVE'] : $_ARRAYLANG['TXT_INACTIVE']),
            ));
            self::$objTemplate->parse('shop_customer');
            $objCustomer->next();
        }
//        if ($count == 0) self::$objTemplate->hideBlock('shop_customers');
        $paging = \Paging::get($uri_sorting,
            $_ARRAYLANG['TXT_CUSTOMERS_ENTRIES'], $count, $limit, true);
        self::$objTemplate->setVariable(array(
            'SHOP_CUSTOMER_PAGING' => $paging,
            'SHOP_CUSTOMER_TERM' => htmlentities($searchterm),
            'SHOP_LISTLETTER_LINKS' => Orders::getListletterLinks($listletter),
            'SHOP_CUSTOMER_TYPE_MENUOPTIONS' =>
                Customers::getTypeMenuoptions($customer_type, true),
            'SHOP_CUSTOMER_STATUS_MENUOPTIONS' =>
                Customers::getActiveMenuoptions($customer_active, true),
//            'SHOP_LISTLETTER_MENUOPTIONS' => self::getListletterMenuoptions,
        ));
        return true;
    }


    /**
     * Toggles the active state of a Customer
     *
     * The Customer ID may be present in $_REQUEST['toggle_customer_id'].
     * If it's not, returns NULL immediately.
     * Otherwise, will add a message indicating success or failure,
     * and redirect back to the customer overview.
     * @global  array       $_ARRAYLANG
     * @return  boolean                     Null on noop
     */
    function toggleCustomer()
    {
        global $_ARRAYLANG;

        if (empty($_REQUEST['toggle_customer_id'])) return NULL;
        $customer_id = intval($_REQUEST['toggle_customer_id']);
        $result = Customers::toggleStatusById($customer_id);
        if (is_null($result)) {
            // NOOP
            return;
        }
        if ($result) {
            \Message::ok($_ARRAYLANG['TXT_SHOP_CUSTOMER_UPDATED_SUCCESSFULLY']);
        } else {
            \Message::error(sprintf(
                $_ARRAYLANG['TXT_SHOP_ERROR_CUSTOMER_UPDATING'], $customer_id));
        }
        \Cx\Core\Csrf\Controller\Csrf::redirect('index.php?cmd=Shop&act=customers');
    }


    /**
     * Deletes a Customer
     *
     * Picks the Customer ID from either $_GET['customer_id'] or
     * $_POST['selected_customer_id'], in that order, whichever is present
     * first.
     * Sets appropriate messages.
     * Aborts immediately upon errors, so the remaining Customers won't be
     * deleted.
     * @return  boolean           True on success, false otherwise
     */
    function delete_customer()
    {
        global $_ARRAYLANG;

        $arrCustomerId = array();
        if (isset($_GET['customer_id']) && !empty($_GET['customer_id'])) {
            $arrCustomerId = array(intval($_GET['customer_id']));
        } elseif (!empty($_POST['selected_customer_id'])) {
            $arrCustomerId = array_map("intval", $_POST['selected_customer_id']);
        }
        if (empty($arrCustomerId)) return true;
        foreach ($arrCustomerId as $customer_id) {
            $objCustomer = Customer::getById($customer_id);
            if (!$objCustomer) {
                return \Message::error(sprintf(
                    $_ARRAYLANG['TXT_SHOP_ERROR_CUSTOMER_QUERYING'],
                    $customer_id));
            }
            // Deletes associated Orders as well!
            if (!$objCustomer->delete()) {
// TODO: Messages *SHOULD* be set up by the User class
                \Message::error(sprintf(
                    $_ARRAYLANG['TXT_SHOP_ERROR_CUSTOMER_DELETING'],
                    $customer_id));
                return false;
            }
        }
        \Message::ok($_ARRAYLANG['TXT_CUSTOMER_DELETED']);
        return \Message::ok($_ARRAYLANG['TXT_ALL_ORDERS_DELETED']);
    }


    /**
     * Activates or deactivates Users
     *
     * Picks User IDs from $_POST['selected_customer_id'] and the desired active
     * status from $_POST['multi_action'].
     * If either is empty, does nothing.
     * Appropriate messages are set by {@see User::set_active()}.
     * @return  void
     */
    static function customer_activate()
    {
        if (empty($_POST['selected_customer_id'])) return;
        $active = null;
        switch ($_POST['multi_action']) {
          case 'activate':
            $active = true;
            break;
          case 'deactivate':
            $active = false;
            break;
          default:
            return;
        }
        \User::set_active($_POST['selected_customer_id'], $active);
    }


    /**
     * Set up the customer details
     */
    function view_customer_details()
    {
        global $_ARRAYLANG;

        self::$objTemplate->loadTemplateFile("module_shop_customer_details.html");
        if (isset($_POST['store'])) {
            self::storeCustomerFromPost();
        }
        $customer_id = intval($_REQUEST['customer_id']);
        $objCustomer = Customer::getById($customer_id);
        if (!$objCustomer) {
            return \Message::error($_ARRAYLANG['TXT_SHOP_CUSTOMER_ERROR_NOT_FOUND']);
        }
        $customer_type = ($objCustomer->is_reseller()
            ? $_ARRAYLANG['TXT_RESELLER'] : $_ARRAYLANG['TXT_CUSTOMER']);
        $active = ($objCustomer->active()
            ? $_ARRAYLANG['TXT_ACTIVE'] : $_ARRAYLANG['TXT_INACTIVE']);
        self::$objTemplate->setVariable(array(
            'SHOP_CUSTOMERID' => $objCustomer->id(),
            'SHOP_GENDER' => $_ARRAYLANG['TXT_SHOP_'.strtoupper($objCustomer->gender())],
            'SHOP_LASTNAME' => $objCustomer->lastname(),
            'SHOP_FIRSTNAME' => $objCustomer->firstname(),
            'SHOP_COMPANY' => $objCustomer->company(),
            'SHOP_ADDRESS' => $objCustomer->address(),
            'SHOP_CITY' => $objCustomer->city(),
            'SHOP_USERNAME' => $objCustomer->username(),
            'SHOP_COUNTRY' => \Cx\Core\Country\Controller\Country::getNameById($objCustomer->country_id()),
            'SHOP_ZIP' => $objCustomer->zip(),
            'SHOP_PHONE' => $objCustomer->phone(),
            'SHOP_FAX' => $objCustomer->fax(),
            'SHOP_EMAIL' => $objCustomer->email(),
            'SHOP_CUSTOMER_BIRTHDAY' => date(ASCMS_DATE_FORMAT_DATE, $objCustomer->getProfileAttribute('birthday')),
            'SHOP_COMPANY_NOTE' => $objCustomer->companynote(),
            'SHOP_IS_RESELLER' => $customer_type,
            'SHOP_REGISTER_DATE' => date(ASCMS_DATE_FORMAT_DATETIME,
                $objCustomer->register_date()),
            'SHOP_CUSTOMER_STATUS' => $active,
            'SHOP_DISCOUNT_GROUP_CUSTOMER' => Discount::getCustomerGroupName(
                $objCustomer->group_id()),
        ));
// TODO: TEST
        $count = NULL;
        $orders = Orders::getArray($count, NULL, array('customer_id' => $objCustomer->id()), \Paging::getPosition(),
                \Cx\Core\Setting\Controller\Setting::getValue('numof_orders_per_page_backend','Shop'));
        $i = 1;
        foreach ($orders as $order) {
            Currency::init($order->currency_id());
            self::$objTemplate->setVariable(array(
                'SHOP_ROWCLASS' => 'row'.(++$i % 2 + 1),
                'SHOP_ORDER_ID' => $order->id(),
                'SHOP_ORDER_ID_CUSTOM' => ShopLibrary::getCustomOrderId(
                    $order->id(), $order->date_time()),
                'SHOP_ORDER_DATE' => $order->date_time(),
                'SHOP_ORDER_STATUS' =>
                    $_ARRAYLANG['TXT_SHOP_ORDER_STATUS_'.$order->status()],
                'SHOP_ORDER_SUM' =>
                    Currency::getDefaultCurrencySymbol().' '.
                    Currency::getDefaultCurrencyPrice($order->sum()),
            ));
            self::$objTemplate->parse('orderRow');
        }
        return true;
    }


    /**
     * Edit a Customer
     * @author    Reto Kohli <reto.kohli@comvation.com>
     */
    function view_customer_edit()
    {
        global $_ARRAYLANG;

        self::$objTemplate->loadTemplateFile("module_shop_edit_customer.html");
        $customer_id = (isset($_REQUEST['customer_id'])
            ? intval($_REQUEST['customer_id']) : null);
        if (isset($_POST['store'])) {
            $customer_id = $this->storeCustomerFromPost();
        }
        $username = (isset($_POST['username'])
            ? trim(strip_tags(contrexx_input2raw($_POST['username']))) : null);
        $password = (isset($_POST['password'])
            ? trim(strip_tags(contrexx_input2raw($_POST['password']))) : null);
        $company = (isset($_POST['company'])
            ? trim(strip_tags(contrexx_input2raw($_POST['company']))) : null);
        $gender = (isset($_POST['gender'])
            ? trim(strip_tags(contrexx_input2raw($_POST['gender']))) : null);
        $firstname = (isset($_POST['firstname'])
            ? trim(strip_tags(contrexx_input2raw($_POST['firstname']))) : null);
        $lastname = (isset($_POST['lastname'])
            ? trim(strip_tags(contrexx_input2raw($_POST['lastname']))) : null);
        $address = (isset($_POST['address'])
            ? trim(strip_tags(contrexx_input2raw($_POST['address']))) : null);
        $city = (isset($_POST['city'])
            ? trim(strip_tags(contrexx_input2raw($_POST['city']))) : null);
        $zip = (isset($_POST['zip'])
            ? trim(strip_tags(contrexx_input2raw($_POST['zip']))) : null);
        $country_id = (isset($_POST['country_id'])
            ? intval($_POST['country_id']) : null);
        $phone = (isset($_POST['phone'])
            ? trim(strip_tags(contrexx_input2raw($_POST['phone']))) : null);
        $fax = (isset($_POST['fax'])
            ? trim(strip_tags(contrexx_input2raw($_POST['fax']))) : null);
        $email = (isset($_POST['email'])
            ? trim(strip_tags(contrexx_input2raw($_POST['email']))) : null);
        $companynote = (isset($_POST['companynote'])
            ? trim(strip_tags(contrexx_input2raw($_POST['companynote']))) : null);
        $is_reseller = (isset($_POST['customer_type'])
            ? intval($_POST['customer_type']) : null);
        $registerdate = time();
        $active = !empty($_POST['active']);
        $customer_group_id = (isset($_POST['customer_group_id'])
            ? intval($_POST['customer_group_id']) : null);
        $lang_id = (isset($_POST['customer_lang_id'])
            ? intval($_POST['customer_lang_id']) : FRONTEND_LANG_ID);
        if ($customer_id) {
            $objCustomer = Customer::getById($customer_id);
            if (!$objCustomer) {
                return \Message::error($_ARRAYLANG['TXT_SHOP_CUSTOMER_ERROR_LOADING']);
            }
            self::$pageTitle = $_ARRAYLANG['TXT_EDIT_CUSTOMER'];
            $username = $objCustomer->username();
            $password = '';
            $company = $objCustomer->company();
            $gender = $objCustomer->gender();
            $firstname = $objCustomer->firstname();
            $lastname = $objCustomer->lastname();
            $address = $objCustomer->address();
            $city = $objCustomer->city();
            $zip = $objCustomer->zip();
            $country_id = $objCustomer->country_id();
            $phone = $objCustomer->phone();
            $fax = $objCustomer->fax();
            $email = $objCustomer->email();
            $companynote = $objCustomer->companynote();
            $is_reseller = $objCustomer->is_reseller();
            $registerdate = $objCustomer->getRegistrationDate();
            $active = $objCustomer->active();
            $customer_group_id = $objCustomer->group_id();
            $lang_id = $objCustomer->getFrontendLanguage();
        } else {
            self::$pageTitle = $_ARRAYLANG['TXT_ADD_NEW_CUSTOMER'];
            self::$objTemplate->setVariable(
                'SHOP_SEND_LOGING_DATA_STATUS', \Html::ATTRIBUTE_CHECKED);
            $customer_id = null;
        }
        self::$objTemplate->setVariable(array(
            'SHOP_CUSTOMERID' => $customer_id,
            'SHOP_COMPANY' => $company,
            'SHOP_GENDER_MENUOPTIONS' => Customers::getGenderMenuoptions($gender),
            'SHOP_LASTNAME' => $lastname,
            'SHOP_FIRSTNAME' => $firstname,
            'SHOP_ADDRESS' => $address,
            'SHOP_ZIP' => $zip,
            'SHOP_CITY' => $city,
            'SHOP_EMAIL' => $email,
            'SHOP_PHONE' => $phone,
            'SHOP_FAX' => $fax,
            'SHOP_CUSTOMER_BIRTHDAY' => date(ASCMS_DATE_FORMAT_DATE, $objCustomer->getProfileAttribute('birthday')),
            'SHOP_USERNAME' => $username,
            'SHOP_PASSWORD' => $password,
            'SHOP_COMPANY_NOTE' => $companynote,
            'SHOP_REGISTER_DATE' => date(ASCMS_DATE_FORMAT_DATETIME, $registerdate),
            'SHOP_COUNTRY_MENUOPTIONS' =>
                \Cx\Core\Country\Controller\Country::getMenuoptions($country_id),
            'SHOP_DISCOUNT_GROUP_CUSTOMER_MENUOPTIONS' =>
                Discount::getMenuOptionsGroupCustomer($customer_group_id),
            'SHOP_CUSTOMER_TYPE_MENUOPTIONS' =>
                Customers::getTypeMenuoptions($is_reseller),
            'SHOP_CUSTOMER_ACTIVE_MENUOPTIONS' =>
                Customers::getActiveMenuoptions($active),
            'SHOP_LANG_ID_MENUOPTIONS' => \FWLanguage::getMenuoptions($lang_id),
        ));
        return true;
    }


    /**
     * Store a customer
     *
     * Sets a Message according to the outcome.
     * Note that failure to send the e-mail with login data is not
     * considered an error and will only produce a warning.
     * @return  integer       The Customer ID on success, null otherwise
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function storeCustomerFromPost()
    {
        global $_ARRAYLANG;

        $username = trim(strip_tags(contrexx_input2raw($_POST['username'])));
        $password = trim(strip_tags(contrexx_input2raw($_POST['password'])));
        $company = trim(strip_tags(contrexx_input2raw($_POST['company'])));
        $gender = trim(strip_tags(contrexx_input2raw($_POST['gender'])));
        $firstname = trim(strip_tags(contrexx_input2raw($_POST['firstname'])));
        $lastname = trim(strip_tags(contrexx_input2raw($_POST['lastname'])));
        $address = trim(strip_tags(contrexx_input2raw($_POST['address'])));
        $city = trim(strip_tags(contrexx_input2raw($_POST['city'])));
        $zip = trim(strip_tags(contrexx_input2raw($_POST['zip'])));
        $country_id = intval($_POST['country_id']);
        $phone = trim(strip_tags(contrexx_input2raw($_POST['phone'])));
        $fax = trim(strip_tags(contrexx_input2raw($_POST['fax'])));
        $birthday = trim(strip_tags(contrexx_input2raw($_POST['shop_customer_birthday'])));
        $email = trim(strip_tags(contrexx_input2raw($_POST['email'])));
        $companynote = trim(strip_tags(contrexx_input2raw($_POST['companynote'])));
        $customer_active = intval($_POST['active']);
        $is_reseller = intval($_POST['customer_type']);
        $customer_group_id = intval($_POST['customer_group_id']);
//        $registerdate = trim(strip_tags(contrexx_input2raw($_POST['registerdate'])));
        $lang_id = (isset($_POST['customer_lang_id'])
            ? intval($_POST['customer_lang_id']) : FRONTEND_LANG_ID);
        $customer_id = intval($_REQUEST['customer_id']);
        $objCustomer = Customer::getById($customer_id);
        if (!$objCustomer) $objCustomer = new Customer();
        $objCustomer->gender($gender);
        $objCustomer->company($company);
        $objCustomer->firstname($firstname);
        $objCustomer->lastname($lastname);
        $objCustomer->address($address);
        $objCustomer->city($city);
        $objCustomer->zip($zip);
        $objCustomer->country_id($country_id);
        $objCustomer->phone($phone);
        $objCustomer->fax($fax);
        $objCustomer->email($email);
        $objCustomer->setProfile(array('birthday' => array(0 => $birthday)));
        $objCustomer->companynote($companynote);
        $objCustomer->active($customer_active);
        $objCustomer->is_reseller($is_reseller);
        // Set automatically: $objCustomer->setRegisterDate($registerdate);
        $objCustomer->group_id($customer_group_id);
        $objCustomer->username($username);
        if (isset($_POST['sendlogindata']) && $password == '') {
            $password = \User::make_password();
        }
        if ($password != '') {
            $objCustomer->setPassword($password);
        }
        $objCustomer->setFrontendLanguage($lang_id);
        if (!$objCustomer->store()) {
            foreach ($objCustomer->error_msg as $message) {
                \Message::error($message);
            }
            return null;
        }
        \Message::ok($_ARRAYLANG['TXT_DATA_RECORD_UPDATED_SUCCESSFUL']);
        if (isset($_POST['sendlogindata'])) {
// TODO: Use a common sendLogin() method
            $lang_id = $objCustomer->getFrontendLanguage();
            $arrSubs = $objCustomer->getSubstitutionArray();
            $arrSubs['CUSTOMER_LOGIN'] = array(0 => array(
                'CUSTOMER_USERNAME' => $username,
                'CUSTOMER_PASSWORD' => $password,
            ));
//DBG::log("Subs: ".var_export($arrSubs, true));
            // Select template for sending login data
            $arrMailTemplate = array(
                'key' => 'customer_login',
                'section' => 'Shop',
                'lang_id' => $lang_id,
                'to' => $email,
                'substitution' => $arrSubs,
            );
            if (!\Cx\Core\MailTemplate\Controller\MailTemplate::send($arrMailTemplate)) {
                \Message::warning($_ARRAYLANG['TXT_MESSAGE_SEND_ERROR']);
                return $objCustomer->id();
            }
            \Message::ok(sprintf($_ARRAYLANG['TXT_EMAIL_SEND_SUCCESSFULLY'],
                $email));
        }
        return $objCustomer->id();
    }


    function view_products()
    {
        global $_ARRAYLANG;

        self::$objTemplate->loadTemplateFile('module_shop_products.html');
        $tpl = (empty($_REQUEST['tpl']) ? '' : $_REQUEST['tpl']);
        switch ($tpl) {
            case 'attributes':
                $this->view_attributes_edit();
                break;
            case 'manage':
                self::$pageTitle = $_ARRAYLANG['TXT_ADD_PRODUCTS'];
                $this->view_product_edit();
                break;
            case 'discounts':
                self::$pageTitle = $_ARRAYLANG['TXT_SHOP_DISCOUNT_COUNT_GROUPS'];
                $this->view_discount_groups_count();
                break;
            case 'groups':
                self::$pageTitle = $_ARRAYLANG['TXT_SHOP_ARTICLE_GROUPS'];
                $this->view_article_groups();
                break;
            default:
                self::$pageTitle = $_ARRAYLANG['TXT_PRODUCT_CATALOG'];
                $this->view_product_overview();
        }
//        self::$objTemplate->parse('shop_products_block');
    }


    /**
     * Show Products
     */
    function view_product_overview()
    {
        global $_ARRAYLANG;

        if (isset($_POST['bsubmit'])) {
            $this->update_products();
        }
        if (isset($_POST['multi_action'])) {
            if ($_POST['multi_action'] == 'activate') {
                Products::set_active($_POST['selectedProductId'], true);
            } elseif ($_POST['multi_action'] == 'deactivate') {
                Products::set_active($_POST['selectedProductId'], false);
            }
        }
        self::$objTemplate->addBlockfile(
            'SHOP_PRODUCTS_FILE', 'shop_products_block',
            'module_shop_product_catalog.html'
        );
        self::$objTemplate->setGlobalVariable($_ARRAYLANG);
        $category_id = (empty($_REQUEST['category_id'])
            ? null : intval($_REQUEST['category_id']));
//DBG::log("Requested Category ID: $category_id");
        $manufacturer_id = (empty($_REQUEST['manufacturer_id'])
            ? null : intval($_REQUEST['manufacturer_id']));
        $flagSpecialoffer = (isset($_REQUEST['specialoffer']));
        $searchTerm = (empty($_REQUEST['searchterm'])
            ? null : trim(contrexx_input2raw($_REQUEST['searchterm'])));

        $url = \Html::getRelativeUri();
// TODO: Strip URL parameters: Which?
//        \Html::stripUriParam($url, '');
        $arrSorting = array(
            '`product`.`id`' => $_ARRAYLANG['TXT_SHOP_ID'],
            '`product`.`active`' => $_ARRAYLANG['TXT_SHOP_PRODUCT_ACTIVE'],
            '`product`.`ord`' => $_ARRAYLANG['TXT_SHOP_PRODUCT_ORDER'],
//            'shown_on_startpage' => $_ARRAYLANG['TXT_SHOP_PRODUCT_SHOWN_ON_STARTPAGE'],
            'name' => $_ARRAYLANG['TXT_SHOP_PRODUCT_NAME'],
            'code' => $_ARRAYLANG['TXT_SHOP_PRODUCT_CODE'],
//            'discount_active' => $_ARRAYLANG['TXT_SHOP_PRODUCT_DISCOUNT_ACTIVE'],
            '`product`.`discountprice`' => $_ARRAYLANG['TXT_SHOP_PRODUCT_DISCOUNTPRICE'],
            '`product`.`normalprice`' => $_ARRAYLANG['TXT_SHOP_PRODUCT_NORMALPRICE'],
            '`product`.`resellerprice`' => $_ARRAYLANG['TXT_SHOP_PRODUCT_RESELLERPRICE'],
//            '`product`.`vat_rate`' => $_ARRAYLANG['TXT_SHOP_PRODUCT_VAT_RATE'],
            '`product`.`distribution`' => $_ARRAYLANG['TXT_SHOP_PRODUCT_DISTRIBUTION'],
            '`product`.`stock`' => $_ARRAYLANG['TXT_SHOP_PRODUCT_STOCK'],
        );
        $objSorting = new \Sorting($url, $arrSorting, false, 'order_shop_product');
        $limit = \Cx\Core\Setting\Controller\Setting::getValue('numof_products_per_page_backend','Shop');
        $tries = 2;
        while ($tries--) {
            // have to set $count again because it will be set to 0 in Products::getByShopParams
            $count = $limit;
            // Mind that $count is handed over by reference.
            $arrProducts = Products::getByShopParams(
                $count, \Paging::getPosition(),
                0, $category_id, $manufacturer_id, $searchTerm,
                $flagSpecialoffer, false, $objSorting->getOrder(),
                null, true // Include inactive Products
            );
            if (count($arrProducts) > 0 || \Paging::getPosition() == 0) {
                break;
            }
            \Paging::reset();
        }
        self::$objTemplate->setVariable(array(
            'SHOP_CATEGORY_MENU' => \Html::getSelect( 'category_id',
                array(0 => $_ARRAYLANG['TXT_ALL_PRODUCT_GROUPS'])
                  + ShopCategories::getNameArray(), $category_id),
            'SHOP_SEARCH_TERM' => $searchTerm,
            'SHOP_PRODUCT_TOTAL' => $count,
        ));
        if (empty($arrProducts)) {
            self::$objTemplate->touchBlock('no_product');
            return true;
        }
        self::$objTemplate->setVariable(array(
            // Paging shown only when there are results
            'SHOP_PRODUCT_PAGING' => \Paging::get($url,
                '<b>'.$_ARRAYLANG['TXT_PRODUCTS'].'</b>', $count, $limit, true),
            'SHOP_HEADING_PRODUCT_ID' => $objSorting->getHeaderForField('`product`.`id`'),
            'SHOP_HEADING_PRODUCT_ACTIVE' => $objSorting->getHeaderForField('`product`.`active`'),
            'SHOP_HEADING_PRODUCT_ORD' => $objSorting->getHeaderForField('`product`.`ord`'),
            'SHOP_HEADING_PRODUCT_NAME' => $objSorting->getHeaderForField('name'),
            'SHOP_HEADING_PRODUCT_CODE' => $objSorting->getHeaderForField('code'),
            'SHOP_HEADING_PRODUCT_DISCOUNTPRICE' => $objSorting->getHeaderForField('`product`.`discountprice`'),
            'SHOP_HEADING_PRODUCT_NORMALPRICE' => $objSorting->getHeaderForField('`product`.`normalprice`'),
            'SHOP_HEADING_PRODUCT_RESELLERPRICE' => $objSorting->getHeaderForField('`product`.`resellerprice`'),
//            'SHOP_HEADING_PRODUCT_VAT_RATE' => $objSorting->getHeaderForField('vat_rate'),
            'SHOP_HEADING_PRODUCT_DISTRIBUTION' => $objSorting->getHeaderForField('`product`.`distribution`'),
            'SHOP_HEADING_PRODUCT_STOCK' => $objSorting->getHeaderForField('`product`.`stock`'),
        ));
        $arrLanguages = \FWLanguage::getActiveFrontendLanguages();
        // Intended to show an edit link for all active frontend languages.
        // However, the design doesn't like it.  Limit to the current one.
        $arrLanguages = array(FRONTEND_LANG_ID => $arrLanguages[FRONTEND_LANG_ID]);
        $i = 0;

        \JS::activate('schedule-publish-tooltip', array());
        foreach ($arrProducts as $objProduct) {
            $productStatus = 'inactive';
            if ($objProduct->active()) {
                $hasScheduledPublishing =   $objProduct->date_start() != '0000-00-00 00:00:00'
                                         || $objProduct->date_end() != '0000-00-00 00:00:00';
                $productStatus = 'active';
                if ($hasScheduledPublishing) {
                    $productStatus =  $objProduct->getActiveByScheduledPublishing()
                                    ? 'scheduled active' : 'scheduled inactive';
                }
            }

            $discount_active = '';
            $specialOfferValue = '';
            if ($objProduct->discount_active()) {
                $discount_active = \Html::ATTRIBUTE_CHECKED;
                $specialOfferValue = 1;
            }
            self::$objTemplate->setGlobalVariable(array(
                'SHOP_ROWCLASS' => 'row'.(++$i % 2 + 1),
                'SHOP_PRODUCT_ID' => $objProduct->id(),
                'SHOP_PRODUCT_CODE' => $objProduct->code(),
                'SHOP_PRODUCT_NAME' => contrexx_raw2xhtml($objProduct->name()),
                'SHOP_PRODUCT_PRICE1' => Currency::formatPrice($objProduct->price()),
                'SHOP_PRODUCT_PRICE2' => Currency::formatPrice($objProduct->resellerprice()),
                'SHOP_PRODUCT_DISCOUNT' => Currency::formatPrice($objProduct->discountprice()),
                'SHOP_PRODUCT_SPECIAL_OFFER' => $discount_active,
                'SHOP_SPECIAL_OFFER_VALUE_OLD' => $specialOfferValue,
                'SHOP_PRODUCT_VAT_MENU' => Vat::getShortMenuString(
                    $objProduct->vat_id(),
                    'taxId['.$objProduct->id().']'),
                'SHOP_PRODUCT_VAT_ID' => ($objProduct->vat_id()
                    ? $objProduct->vat_id() : 'NULL'),
                'SHOP_PRODUCT_DISTRIBUTION' => $objProduct->distribution(),
                'SHOP_PRODUCT_STOCK' => $objProduct->stock(),
                'SHOP_PRODUCT_SHORT_DESC' => $objProduct->short(),
                'SHOP_PRODUCT_STATUS_CLASS' => $productStatus,
                'SHOP_SORT_ORDER' => $objProduct->ord(),
//                'SHOP_DISTRIBUTION_MENU' => Distribution::getDistributionMenu($objProduct->distribution(), "distribution[".$objProduct->id()."]"),
//                'SHOP_PRODUCT_WEIGHT' => Weight::getWeightString($objProduct->weight()),
                'SHOP_DISTRIBUTION' => $_ARRAYLANG['TXT_DISTRIBUTION_'.
                    strtoupper($objProduct->distribution())],
                'SHOP_SHOW_PRODUCT_ON_START_PAGE_CHECKED' =>
                    ($objProduct->shown_on_startpage()
                      ? \Html::ATTRIBUTE_CHECKED : ''),
                'SHOP_SHOW_PRODUCT_ON_START_PAGE_OLD' =>
                    ($objProduct->shown_on_startpage() ? '1' : ''),
// This is used when the Product name can be edited right on the overview
                'SHOP_PRODUCT_NAME' => contrexx_raw2xhtml($objProduct->name()),
            ));
            // All languages active
            foreach ($arrLanguages as $lang_id => $arrLanguage) {
                self::$objTemplate->setVariable(array(
                    'SHOP_PRODUCT_LANGUAGE_ID' => $lang_id,
                    'SHOP_PRODUCT_LANGUAGE_EDIT' =>
                        sprintf($_ARRAYLANG['TXT_SHOP_PRODUCT_LANGUAGE_EDIT'],
                            $lang_id,
                            $arrLanguage['lang'],
                            $arrLanguage['name']),
                ));
                self::$objTemplate->parse('product_language');
            }
            self::$objTemplate->touchBlock('productRow');
            self::$objTemplate->parse('productRow');
        }
        return true;
    }


    /**
     * Store any Products that have been modified.
     *
     * Takes the Product data directly from the various fields of the
     * $_POST array.  Only updates the database records for Products that
     * have at least one of their values changed.
     * @return  boolean                     True on success, false otherwise.
     * @global  array       $_ARRAYLANG     Language array
     */
    function update_products()
    {
        global $_ARRAYLANG;

        $arrError = array();
        foreach (array_keys($_POST['product_id']) as $product_id) {
            $product_code =
                contrexx_input2raw($_POST['identifier'][$product_id]);
            $product_code_old =
                contrexx_input2raw($_POST['identifierOld'][$product_id]);
            $ord = intval($_POST['ord'][$product_id]);
            $ord_old = intval($_POST['ordOld'][$product_id]);
            $discount_active = (isset($_POST['discount_active'][$product_id]) ? 1 : 0);
            $special_offer_old = $_POST['specialOfferOld'][$product_id];
            $discount_price = floatval($_POST['discount_price'][$product_id]);
            $discountOld = floatval($_POST['discountOld'][$product_id]);
            $normalprice = floatval($_POST['price1'][$product_id]);
            $normalpriceOld = floatval($_POST['price1Old'][$product_id]);
            $resellerprice = floatval($_POST['price2'][$product_id]);
            $resellerpriceOld = floatval($_POST['price2Old'][$product_id]);
            $stock = intval($_POST['stock'][$product_id]);
            $stockOld = intval($_POST['stockOld'][$product_id]);
//            $status = (isset($_POST['active'][$product_id]) ? 1 : 0);
//            $statusOld = $_POST['activeOld'][$product_id];
            $vat_id = (isset($_POST['taxId'][$product_id])
                ? intval($_POST['taxId'][$product_id]) : 0);
            $vat_id_old = intval($_POST['taxIdOld'][$product_id]);
            $shownOnStartpage =
                (empty($_POST['shownonstartpage'][$product_id]) ? 0 : 1);
            $shownOnStartpageOld =
                (empty($_POST['shownonstartpageOld'][$product_id]) ? 0 : 1);
// This is used when the Product name can be edited right on the overview
            $name = (isset($_POST['name'][$product_id])
                ? contrexx_input2raw($_POST['name'][$product_id]) : null);
            $nameOld = (isset($_POST['nameOld'][$product_id])
                ? contrexx_input2raw($_POST['nameOld'][$product_id]) : null);
/*  Distribution and weight have been removed from the overview due to the
    changes made to the delivery options.
            $distribution = $_POST['distribution'][$product_id];
            $distributionOld = $_POST['distributionOld'][$product_id];
            $weight = $_POST['weight'][$product_id];
            $weightOld = $_POST['weightOld'][$product_id];
            // Flag used to determine whether the record has to be
            // updated in the database
            $updateProduct = false;
            // Check whether the weight was changed
            if ($weight != $weightOld) {
                // Changed.
                // If it's empty, set to NULL and don't complain.
                // The NULL weight will be silently ignored by the database.
                if ($weight == '') {
                    $weight = 'NULL';
                } else {
                    // Check the format
                    $weight = Weight::getWeight($weight);
                    // The NULL weight will be silently ignored by the database.
                    if ($weight === 'NULL') {
                        // 'NULL', the format was invalid. cast error
                        \Message::error($_ARRAYLANG['TXT_WEIGHT_INVALID_IGNORED']);
                    } else {
                        // If getWeight() returns any other value, the format
                        // is valid.  Verify that the numeric value has changed
                        // as well; might be that the user simply removed the
                        // unit ('g').
                        if ($weight != Weight::getWeight($weightOld)) {
                            // Really changed
                            $updateProduct = true;
                        }
                        // Otherwise, the new amd old values are the same.
                    }
                }
            }
            if ($updateProduct === false) {
                // reset the weight to the old and, hopefully, correct value,
                // in case the record is updated anyway
                $weight = Weight::getWeight($weightOld);
            }
*/
            // Check if any one value has been changed
            if (   $product_code != $product_code_old
                || $ord != $ord_old
                || $discount_active != $special_offer_old
                || $discount_price != $discountOld
                || $normalprice != $normalpriceOld
                || $resellerprice != $resellerpriceOld
                || $stock != $stockOld
//                || $status != $statusOld
                || $vat_id != $vat_id_old
                || $shownOnStartpage != $shownOnStartpageOld
// This is used when the Product name can be edited right on the overview
                || $name != $nameOld
/*              || $distribution != $distributionOld
                // Weight, see above
                || $updateProduct*/
            ) {
                $arrProducts =
//                    ($product_code_old != ''
//                        ? Products::getByCustomId($product_code_old) :
                    array(Product::getById($product_id))
//                );
                    ;
                if (!is_array($arrProducts)) {
                    continue;
                }
                foreach ($arrProducts as $objProduct) {
                    if (!$objProduct) {
                        $arrError[$product_code] = true;
                        continue;
                    }
                    $objProduct->code($product_code);
                    $objProduct->ord($ord);
                    $objProduct->discount_active($discount_active);
                    $objProduct->discountprice($discount_price);
                    $objProduct->price($normalprice);
                    $objProduct->resellerprice($resellerprice);
                    $objProduct->stock($stock);
//                    $objProduct->active($status);
                    $objProduct->vat_id($vat_id);
//                    $objProduct->distribution($distribution);
//                    $objProduct->weight($weight);
                    $objProduct->shown_on_startpage($shownOnStartpage);
// This is used when the Product name can be edited right on the overview
                    // Note: No need to check whether it is valid; if it's set
                    // to null above name() will do nothing but return the
                    // current name
                    $objProduct->name($name);
                    if (!$objProduct->store()) {
                        $arrError[$product_code] = true;
                    }
                }
            }
        }
        if (empty($arrError)) {
            \Message::ok($_ARRAYLANG['TXT_DATA_RECORD_UPDATED_SUCCESSFUL']);
            return true;
        }
        \Message::error($_ARRAYLANG['TXT_SHOP_ERROR_UPDATING_RECORD']);
        return false;
    }


    static function getMonthDropdownMenu($selected=NULL)
    {
        global $_ARRAYLANG;

        $strMenu = '';
        $months = explode(',', $_ARRAYLANG['TXT_MONTH_ARRAY']);
        foreach ($months as $index => $name) {
            $monthNumber = $index + 1;
            $strMenu .=
                '<option value="'.$monthNumber.'"'.
                ($selected == $monthNumber ? \Html::ATTRIBUTE_SELECTED : '').
                ">$name</option>\n";
        }
        return $strMenu;
    }


    static function getYearDropdownMenu($selected=NULL, $startYear=NULL)
    {
        $strMenu = '';
        $yearNow = date('Y');
        while ($startYear <= $yearNow) {
            $strMenu .=
                "<option value='$startYear'".
                ($selected == $startYear ? \Html::ATTRIBUTE_SELECTED :   '').
                ">$startYear</option>\n";
            ++$startYear;
        }
        return $strMenu;
    }


    /**
     * Set the database query error Message
     * @global    array       $_ARRAYLANG
     * @return    boolean     False
     */
    function error_database()
    {
        global $_ARRAYLANG;

//DBG::log("admin.class.php::error_database()");
        return \Message::error($_ARRAYLANG['TXT_SHOP_DATABASE_QUERY_ERROR']);
    }


    /**
     * Set the no records information Message
     * @global    array       $_ARRAYLANG
     * @return    null        Null
     */
    function information_no_data()
    {
        global $_ARRAYLANG;

//DBG::log("admin.class.php::information_no_data()()");
        \Message::information($_ARRAYLANG['TXT_SHOP_DATABASE_INFORMATION_NO_RECORDS']);
        return null;
    }


    /**
     * Shows an overview of all pricelists
     *
     * Also processes requests for deleting one or more Pricelists.
     * @global  array           $_ARRAYLANG
     * @global  ADOConnection   $objDatabase    Database connection object
     */
    function view_pricelists()
    {
        global $_ARRAYLANG;

        self::$pageTitle = $_ARRAYLANG['TXT_PDF_OVERVIEW'];
        // Note that the "list_id" index may be set but empty in order to
        // create a new pricelist.
        if (isset($_REQUEST['list_id'])) {
            return self::view_pricelist_edit();
        }
        PriceList::deleteByRequest();
        self::$objTemplate->loadTemplateFile("module_shop_pricelist_overview.html");
        self::$objTemplate->setGlobalVariable($_ARRAYLANG);
        $arrName = PriceList::getNameArray();
        $i = 0;
        foreach ($arrName as $list_id => $name) {
            $url = PriceList::getUrl($list_id);
//DBG::log("URL: $url");
            self::$objTemplate->setVariable(array(
                'SHOP_PRICELIST_ROWCLASS' => 'row'.(++$i % 2 + 1),
                'SHOP_PRICELIST_ID' => $list_id,
                'SHOP_PRICELIST_NAME' => contrexx_raw2xhtml($name),
                'SHOP_PRICELIST_LINK_PDF' =>
                    "<a href='$url' target='_blank'".
                    " title='".$_ARRAYLANG['TXT_DISPLAY']."'>$url</a>",
            ));
            self::$objTemplate->parse('shop_pricelist');
        }
    }


    /**
     * Edit a pricelist
     * @global  ADOConnection   $objDatabase
     * @global  array           $_ARRAYLANG
     * @return  boolean                         True on success, false otherwise
     */
    static function view_pricelist_edit()
    {
        global $_ARRAYLANG;

        $list_id = null;
        $objList = PriceList::getFromPost();
        if ($objList) {
            $result = $objList->store();
            if ($result) {
                if (isset ($_REQUEST['list_id']))
                    unset($_REQUEST['list_id']);
//die("Showing lists");
                return self::view_pricelists();
            }
        }
        $list_id = (isset($_GET['list_id']) ? $_GET['list_id'] : null);
        $objList = PriceList::getById($list_id);
        if (!$objList) $objList = new PriceList(null);
        $list_id = $objList->id();
        self::$objTemplate->loadTemplateFile("module_shop_pricelist_details.html");
        self::$objTemplate->setGlobalVariable($_ARRAYLANG);
        self::$objTemplate->setVariable(array(
            'SHOP_PRICELIST_EDIT' => $_ARRAYLANG[($list_id
                ? 'TXT_SHOP_PRICELIST_EDIT' : 'TXT_SHOP_PRICELIST_ADD')],
            'SHOP_PRICELIST_ID' => $list_id,
            'SHOP_PRICELIST_LINK_PDF' =>
                ($list_id ? PriceList::getUrl($list_id) : ''),
            'SHOP_PRICELIST_NAME' => $objList->name(),
            'SHOP_PRICELIST_LANGUAGE_MENUOPTIONS' => \Html::getOptions(
                \FWLanguage::getNameArray(), $objList->lang_id()),
            'SHOP_PRICELIST_BORDER_CHECKED' => ($objList->border()
                ? \Html::ATTRIBUTE_CHECKED : ''),
            'SHOP_PRICELIST_HEADER_CHECKED' => ($objList->header()
                ? \Html::ATTRIBUTE_CHECKED : ''),
            'SHOP_PRICELIST_HEADER_LEFT' => $objList->header_left(),
            'SHOP_PRICELIST_HEADER_RIGHT' => $objList->header_right(),
            'SHOP_PRICELIST_FOOTER_CHECKED' => ($objList->footer()
                ? \Html::ATTRIBUTE_CHECKED : ''),
            'SHOP_PRICELIST_FOOTER_LEFT' => $objList->footer_left(),
            'SHOP_PRICELIST_FOOTER_RIGHT' => $objList->footer_right(),
        ));
        $category_ids = $objList->category_ids();
        $category_all = false;
        if (empty($category_ids) || $category_ids == '*') {
            $category_all = true;
            self::$objTemplate->setVariable(
                'SHOP_PRICELIST_CATEGORY_ALL_CHECKED', \Html::ATTRIBUTE_CHECKED);
        }
        // Get all categories
        $arrCategories = ShopCategories::getTreeArray(true, false);
        if (empty($arrCategories)) {
            Message::warning($_ARRAYLANG['TXT_SHOP_WARNING_NO_CATEGORIES']);
        }
        $i = 0;
        foreach ($arrCategories as $objCategory) {
            $category_id = $objCategory['id'];
            $selected =
                (   $category_all
                 || preg_match('/(?:^|,)\s*'.$category_id.'\s*(?:,|$)/',
                        $category_ids));
//DBG::log("Category ID $category_id, ".($selected ? "selected" : "NOT"));
            self::$objTemplate->setVariable(array(
                'SHOP_CATEGORY_ID' => contrexx_raw2xhtml($category_id),
                'SHOP_CATEGORY_NAME' => contrexx_raw2xhtml($objCategory['name']),
                'SHOP_CATEGORY_LEVELSPACE' => str_repeat('|----', $objCategory['level']),
                'SHOP_CATEGORY_DISABLED' => ($category_all
                    ? \Html::ATTRIBUTE_DISABLED : ''),
                'SHOP_CATEGORY_CHECKED' => ($selected ? \Html::ATTRIBUTE_CHECKED : ''),
                'SHOP_CATEGORY_ROWCLASS' => 'row'.(++$i% 2 + 1),
            ));
            self::$objTemplate->parse('shop_category');
        }
        return true;
    }


    /**
     * Send an e-mail to the Customer with the confirmation that the Order
     * with the given Order ID has been processed
     * @param   integer   $order_id     The order ID
     * @return  boolean                 True on success, false otherwise
     */
    static function sendProcessedMail($order_id)
    {
        $arrSubstitution =
              Orders::getSubstitutionArray($order_id, false, false)
            + self::getSubstitutionArray();
        $lang_id = $arrSubstitution['LANG_ID'];
        // Select template for: "Your order has been processed"
        $arrMailTemplate = array(
            'section' => 'Shop',
            'key' => 'order_complete',
            'lang_id' => $lang_id,
            'to' =>
                $arrSubstitution['CUSTOMER_EMAIL'],
                //.','.\Cx\Core\Setting\Controller\Setting::getValue('email_confirmation','Shop'),
            'substitution' => &$arrSubstitution,
        );
        if (!\Cx\Core\MailTemplate\Controller\MailTemplate::send($arrMailTemplate)) return false;
        return $arrSubstitution['CUSTOMER_EMAIL'];
    }


    /**
     * Show the count discount editing page
     * @return    boolean             True on success, false otherwise
     * @author    Reto Kohli <reto.kohli@comvation.com>
     */
    function view_discount_groups_count()
    {
        global $_ARRAYLANG;

        if (isset($_POST['discountStore'])) {
            $this->store_discount_count();
        }
        if (isset($_GET['deleteDiscount'])) {
            $this->delete_discount_count();
        }
        // Force discounts to be reinitialised
        Discount::flush();

        self::$objTemplate->addBlockfile('SHOP_PRODUCTS_FILE', 'shop_products_block', 'module_shop_discount_groups_count.html');

        // Discounts overview
        $arrDiscounts = Discount::getDiscountCountArray();
        $i = 0;
        foreach ($arrDiscounts as $id => $arrDiscount) {
            $name = $arrDiscount['name'];
            $unit = $arrDiscount['unit'];
            self::$objTemplate->setVariable(array(
                'SHOP_DISCOUNT_ID' => $id,
                'SHOP_DISCOUNT_GROUP_NAME' => contrexx_raw2xhtml($name),
                'SHOP_DISCOUNT_GROUP_UNIT' => contrexx_raw2xhtml($unit),
                'SHOP_DISCOUNT_ROW_STYLE' => 'row'.(++$i % 2 + 1),
            ));
            self::$objTemplate->parse('discount');
        }

        // Add/edit Discount
        $id = 0;
        $arrDiscountRates = array();
        if (!empty($_GET['editDiscount'])) {
            $id = intval($_GET['id']);
            $arrDiscountRates = Discount::getDiscountCountRateArray($id);
            self::$objTemplate->setGlobalVariable(array(
                'SHOP_DISCOUNT_EDIT_CLASS' => 'active',
                'SHOP_DISCOUNT_EDIT_DISPLAY' => 'block',
                'SHOP_DISCOUNT_LIST_CLASS' => '',
                'SHOP_DISCOUNT_LIST_DISPLAY' => 'none',
                'TXT_ADD_OR_EDIT' => $_ARRAYLANG['TXT_EDIT'],
            ));
        } else {
            self::$objTemplate->setGlobalVariable(array(
                'SHOP_DISCOUNT_EDIT_CLASS' => '',
                'SHOP_DISCOUNT_EDIT_DISPLAY' => 'none',
                'SHOP_DISCOUNT_LIST_CLASS' => 'active',
                'SHOP_DISCOUNT_LIST_DISPLAY' => 'block',
                'TXT_ADD_OR_EDIT' => $_ARRAYLANG['TXT_ADD'],
            ));
        }
        self::$objTemplate->setCurrentBlock('discountName');
        self::$objTemplate->setVariable(array(
            'SHOP_DISCOUNT_ID_EDIT' => $id,
            'SHOP_DISCOUNT_ROW_STYLE' => 'row'.(++$i % 2 + 1),
        ));
        if (isset($arrDiscounts[$id])) {
            $arrDiscount = $arrDiscounts[$id];
            $name = $arrDiscount['name'];
            $unit = $arrDiscount['unit'];
            self::$objTemplate->setVariable(array(
                'SHOP_DISCOUNT_GROUP_NAME' => $name,
                'SHOP_DISCOUNT_GROUP_UNIT' => $unit,
            ));
        }
        self::$objTemplate->parse('discountName');
        self::$objTemplate->setCurrentBlock('discountType');
        self::$objTemplate->setVariable(array(
            'SHOP_DISCOUNT_GROUP_TYPE_OPTIONS' =>
            \Html::getRadioGroup(
                'discountGroupType',
                array(
                    $_ARRAYLANG['TXT_YES'],
                    $_ARRAYLANG['TXT_NO']
                ),
                Discount::isDiscountCumulative($id)
            )
        ));
        self::$objTemplate->touchBlock('discountType');
        self::$objTemplate->parse('discountType');
        self::$objTemplate->setCurrentBlock('discountRate');
        if (isset($arrDiscountRates)) {
            $arrDiscountRates = array_reverse($arrDiscountRates, true);
            foreach ($arrDiscountRates as $count => $rate) {
                self::$objTemplate->setVariable(array(
                    'SHOP_DISCOUNT_COUNT' => $count,
                    'SHOP_DISCOUNT_RATE' => $rate,
                    'SHOP_DISCOUNT_RATE_INDEX' => $i,
                    'SHOP_DISCOUNT_ROW_STYLE' => 'row'.(++$i % 2 + 1),
                ));
                self::$objTemplate->parse('discountRate');
            }
        }
        // Add a few empty rows for adding new counts and rates
        for ($j = 0; $j < 5; ++$j) {
            self::$objTemplate->setVariable(array(
                'SHOP_DISCOUNT_COUNT' => '',
                'SHOP_DISCOUNT_RATE' => '',
                'SHOP_DISCOUNT_RATE_INDEX' => $i,
                'SHOP_DISCOUNT_ROW_STYLE' => 'row'.(++$i % 2 + 1),
            ));
            self::$objTemplate->parse('discountRate');
        }
        return true;
    }


    /**
     * Store the count discounts after editing
     * @return    boolean             True on success, false otherwise
     * @author    Reto Kohli <reto.kohli@comvation.com>
     */
    function store_discount_count()
    {
        if (!isset($_POST['discountId'])) return true;
        $discountId = intval($_POST['discountId']);
        $discountGroupType = contrexx_input2int($_POST['discountGroupType']);
        $discountGroupName = contrexx_input2raw($_POST['discountGroupName']);
        $discountGroupUnit = contrexx_input2raw($_POST['discountGroupUnit']);
        $arrDiscountCount = contrexx_input2int($_POST['discountCount']);
        $arrDiscountRate = contrexx_input2float($_POST['discountRate']);
        return Discount::storeDiscountCount(
            $discountId, $discountGroupType, $discountGroupName,
            $discountGroupUnit, $arrDiscountCount, $arrDiscountRate
        );
    }


    /**
     * Delete the count discount selected by its ID from the GET request
     * @return    boolean             True on success, false otherwise
     * @author    Reto Kohli <reto.kohli@comvation.com>
     */
    function delete_discount_count()
    {
        if (!isset($_GET['id'])) return true;
        $discountId = $_GET['id'];
        return Discount::deleteDiscountCount($discountId);
    }


    /**
     * Show the customer groups for editing
     * @return    boolean             True on success, false otherwise
     * @author    Reto Kohli <reto.kohli@comvation.com>
     */
    function view_customer_groups()
    {
        global $_ARRAYLANG;

        if (isset($_GET['delete'])) {
            Discount::deleteCustomerGroup($_GET['id']);
        }
        if (isset($_POST['store'])) {
            Discount::storeCustomerGroup($_POST['groupName'], $_POST['id']);
        }
        Discount::flush();

        self::$objTemplate->loadTemplateFile('module_shop_discount_groups_customer.html');

        // Group overview
        $arrGroups = Discount::getCustomerGroupArray();
        self::$objTemplate->setCurrentBlock('shopGroup');
        $i = 0;
        foreach ($arrGroups as $id => $arrGroup) {
            self::$objTemplate->setVariable(array(
                'SHOP_GROUP_ID' => $id,
                'SHOP_GROUP_NAME' => $arrGroup['name'],
                'SHOP_ROW_STYLE' => 'row'.(++$i % 2 + 1),
            ));
            self::$objTemplate->parse('shopGroup');
        }

        // Add/edit Group
        $id = 0;
        if (!empty($_GET['edit'])) {
            $id = intval($_GET['id']);
            self::$objTemplate->setGlobalVariable(array(
                'SHOP_GROUP_EDIT_CLASS' => 'active',
                'SHOP_GROUP_EDIT_DISPLAY' => 'block',
                'SHOP_GROUP_LIST_CLASS' => '',
                'SHOP_GROUP_LIST_DISPLAY' => 'none',
                'TXT_ADD_OR_EDIT' => $_ARRAYLANG['TXT_EDIT'],
            ));
        } else {
            self::$objTemplate->setGlobalVariable(array(
                'SHOP_GROUP_EDIT_CLASS' => '',
                'SHOP_GROUP_EDIT_DISPLAY' => 'none',
                'SHOP_GROUP_LIST_CLASS' => 'active',
                'SHOP_GROUP_LIST_DISPLAY' => 'block',
                'TXT_ADD_OR_EDIT' => $_ARRAYLANG['TXT_ADD'],
            ));
        }
        self::$objTemplate->setCurrentBlock('shopGroupName');
        self::$objTemplate->setVariable(array(
            'SHOP_GROUP_ID_EDIT' => $id,
            'SHOP_ROW_STYLE' => 'row'.(++$i % 2 + 1),
        ));
        if (isset($arrGroups[$id])) {
            self::$objTemplate->setVariable(
                'SHOP_GROUP_NAME', $arrGroups[$id]['name']
            );
        }
        self::$objTemplate->parse('shopGroupName');
        return true;
    }


    /**
     * Show the article groups for editing
     * @return    boolean             True on success, false otherwise
     * @author    Reto Kohli <reto.kohli@comvation.com>
     */
    function view_article_groups()
    {
        global $_ARRAYLANG;

        if (isset($_GET['delete'])) {
            Discount::deleteArticleGroup($_GET['id']);
        }
        if (isset($_POST['store'])) {
            Discount::storeArticleGroup(
                $_POST['groupName'], $_POST['id']
            );
        }
        // Force discounts to be reinitialised
        Discount::flush();

        self::$objTemplate->addBlockfile('SHOP_PRODUCTS_FILE',
            'shop_products_block', 'module_shop_discount_groups_article.html');
        // Group overview
        $arrGroups = Discount::getArticleGroupArray();
        self::$objTemplate->setCurrentBlock('shopGroup');
        $i = 0;
        foreach ($arrGroups as $id => $arrGroup) {
            self::$objTemplate->setVariable(array(
                'SHOP_GROUP_ID' => $id,
                'SHOP_GROUP_NAME' => $arrGroup['name'],
                'SHOP_ROW_STYLE' => 'row'.(++$i % 2 + 1),
            ));
            self::$objTemplate->parseCurrentBlock();
        }
        // Add/edit Group
        $id = 0;
        if (!empty($_GET['edit'])) {
            $id = intval($_GET['id']);
            self::$objTemplate->setGlobalVariable(array(
                'SHOP_GROUP_EDIT_CLASS' => 'active',
                'SHOP_GROUP_EDIT_DISPLAY' => 'block',
                'SHOP_GROUP_LIST_CLASS' => '',
                'SHOP_GROUP_LIST_DISPLAY' => 'none',
                'TXT_ADD_OR_EDIT' => $_ARRAYLANG['TXT_EDIT'],
            ));
        } else {
            self::$objTemplate->setGlobalVariable(array(
                'SHOP_GROUP_EDIT_CLASS' => '',
                'SHOP_GROUP_EDIT_DISPLAY' => 'none',
                'SHOP_GROUP_LIST_CLASS' => 'active',
                'SHOP_GROUP_LIST_DISPLAY' => 'block',
                'TXT_ADD_OR_EDIT' => $_ARRAYLANG['TXT_ADD'],
            ));
        }
        self::$objTemplate->setCurrentBlock('shopGroupName');
        self::$objTemplate->setVariable(array(
            'SHOP_GROUP_ID_EDIT' => $id,
            'SHOP_ROW_STYLE' => 'row'.(++$i % 2 + 1),
        ));
        if (isset($arrGroups[$id])) {
            self::$objTemplate->setVariable('SHOP_GROUP_NAME', $arrGroups[$id]['name']);
        }
        self::$objTemplate->parseCurrentBlock();
        return true;
    }


    /**
     * Show the customer and article group discounts for editing.
     *
     * Handles storing of the discounts as well.
     * @return    boolean             True on success, false otherwise
     * @author    Reto Kohli <reto.kohli@comvation.com>
     */
    function view_customer_discounts()
    {
        if (!empty($_POST['store'])) {
            $this->store_discount_customer();
        }
        self::$objTemplate->loadTemplateFile("module_shop_discount_customer.html");
        // Discounts overview
        $arrCustomerGroups = Discount::getCustomerGroupArray();
        $arrArticleGroups = Discount::getArticleGroupArray();
        $arrRate = null;
        $arrRate = Discount::getDiscountRateCustomerArray();
        $i = 0;
        // Set up the customer groups header
        self::$objTemplate->setVariable(array(
//            'SHOP_CUSTOMER_GROUP_COUNT_PLUS_1' => count($arrCustomerGroups) + 1,
            'SHOP_CUSTOMER_GROUP_COUNT' => count($arrCustomerGroups),
            'SHOP_DISCOUNT_ROW_STYLE' => 'row'.(++$i % 2 + 1),
        ));
        foreach ($arrCustomerGroups as $id => $arrCustomerGroup) {
            self::$objTemplate->setVariable(array(
                'SHOP_CUSTOMER_GROUP_ID' => $id,
                'SHOP_CUSTOMER_GROUP_NAME' => $arrCustomerGroup['name'],
            ));
            self::$objTemplate->parse('customer_group_header_column');
            self::$objTemplate->touchBlock('article_group_header_column');
            self::$objTemplate->parse('article_group_header_column');
        }
        foreach ($arrArticleGroups as $groupArticleId => $arrArticleGroup) {
//DBG::log("Article group ID $groupArticleId");
            foreach ($arrCustomerGroups as $groupCustomerId => $arrCustomerGroup) {
                $rate = (isset($arrRate[$groupCustomerId][$groupArticleId])
                    ? $arrRate[$groupCustomerId][$groupArticleId] : 0);
                self::$objTemplate->setVariable(array(
                    'SHOP_CUSTOMER_GROUP_ID' => $groupCustomerId,
                    'SHOP_DISCOUNT_RATE' => sprintf('%2.2f', $rate),
//                    'SHOP_DISCOUNT_ROW_STYLE' => 'row'.(++$i % 2 + 1),
                ));
                self::$objTemplate->parse('discount_column');
            }
            self::$objTemplate->setVariable(array(
                'SHOP_ARTICLE_GROUP_ID' => $groupArticleId,
                'SHOP_ARTICLE_GROUP_NAME' => $arrArticleGroup['name'],
                'SHOP_DISCOUNT_ROW_STYLE' => 'row'.(++$i % 2 + 1),
            ));
            self::$objTemplate->parse('article_group_row');
        }
        self::$objTemplate->setGlobalVariable(
            'SHOP_DISCOUNT_ROW_STYLE', 'row'.(++$i % 2 + 1));
//        self::$objTemplate->touchBlock('article_group_header_row');
//        self::$objTemplate->parse('article_group_header_row');
        return true;
    }


    /**
     * Store the customer and article group discount rates after editing
     * @return    boolean             True on success, false otherwise
     * @author    Reto Kohli <reto.kohli@comvation.com>
     */
    function store_discount_customer()
    {
        return Discount::storeDiscountCustomer($_POST['discountRate']);
    }


    /**
     * OBSOLETE
     * Deletes the customer group selected by its ID from the GET request
     * @return    boolean             True on success, false otherwise
     * @author    Reto Kohli <reto.kohli@comvation.com>
     * @todo      Seems that this is unused!?
     */
    function delete_customer_group()
    {
die("Shopmanager::delete_customer_group(): Obsolete method called");
//        if (empty($_GET['id'])) return true;
//        return Discount::deleteCustomerGroup($_GET['id']);
    }


    /**
     * OBSOLETE
     * Deletes the article group selected by its ID from the GET request
     * @return    boolean             True on success, false otherwise
     * @author    Reto Kohli <reto.kohli@comvation.com>
     */
    function delete_article_group()
    {
die("Shopmanager::delete_article_group(): Obsolete method called");
//        if (empty($_GET['id'])) return true;
//        return Discount::deleteCustomerGroup($_GET['id']);
    }

    /**
     * Get Media Browser
     *
     * @param object $objTpl            Template object
     * @param string $placeholderKey    Place holder name
     * @param string $placeholderValue  Display name
     * @param array  $options           options Ex:type, id.. etc
     * @param string $callback          callback function name
     *
     * @return null
     */
    public static function getMediaBrowserButton($options = array(), $callback = '')
    {
        global $_ARRAYLANG;

        if (empty($options)) {
            return;
        }

        // Mediabrowser
        $mediaBrowser = new \Cx\Core_Modules\MediaBrowser\Model\Entity\MediaBrowser();
        $mediaBrowser->setOptions($options);
        if ($callback) {
            $mediaBrowser->setCallback($callback);
        }

        return $mediaBrowser->getXHtml($_ARRAYLANG['TXT_SHOP_EDIT_OR_ADD_IMAGE']);
    }
}
