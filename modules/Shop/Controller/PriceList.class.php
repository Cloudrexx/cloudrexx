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
 * Pricelist
 *
 * Creates a PDF document with product price information
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Kaelin <gwanun@astalavista.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  module_shop
 */

namespace Cx\Modules\Shop\Controller;

/**
 * Pricelist
 *
 * Creates a PDF document with product price information
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Kaelin <gwanun@astalavista.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  module_shop
 * @todo        Font, color and basic layout should be configurable in the backend
 */
class PriceList
{
    /**
     * The list ID
     * @var   integer
     */
    private $id = null;
    function id()
    {
        return $this->id;
    }
    /**
     * The currency ID
     * @var   integer
     */
    private $currency_id = null;
    function currency_id($currency_id=null)
    {
        if (isset($currency_id)) {
            $currency_id = intval($currency_id);
            if ($currency_id <= 0) return null;
            $this->currency_id = $currency_id;
        }
        return $this->currency_id;
    }
    /**
     * The language ID
     * @var   integer
     */
    private $lang_id = null;
    function lang_id($lang_id=null)
    {
        if (isset($lang_id)) {
            $lang_id = intval($lang_id);
            if ($lang_id <= 0) return null;
            $this->lang_id = $lang_id;
        }
        return $this->lang_id;
    }

    /**
     * Pricelist font face
     * @var   string
     */
    private $font = 'Helvetica';
    function font($font=null)
    {
        if (isset($font)) {
            $font = trim(strip_tags($font));
            if (!$font) return null;
            $this->font = $font;
        }
        return $this->font;
    }
    /**
     * Odd row background color, in CSS hex format (RRGGBB)
     * @var   string
     */
    private $row_color_1 = 'dddddd';
    function row_color_1($row_color_1=null)
    {
        if (isset($row_color_1)) {
            $row_color_1 = trim(strip_tags($row_color_1));
            if (!$row_color_1) return null;
            $this->row_color_1 = $row_color_1;
        }
        return $this->row_color_1;
    }
    /**
     * Even row background color, in CSS hex format (RRGGBB)
     * @var   string
     */
    private $row_color_2 = 'ffffff';
    function row_color_2($row_color_2=null)
    {
        if (isset($row_color_2)) {
            $row_color_2 = trim(strip_tags($row_color_2));
            if (!$row_color_2) return null;
            $this->row_color_2 = $row_color_2;
        }
        return $this->row_color_2;
    }
    /**
     * Header font size
     * @var   integer
     */
    private $font_size_header = 8;
    function font_size_header($font_size_header=null)
    {
        if (isset($font_size_header)) {
            $font_size_header = intval($font_size_header);
            if ($font_size_header <= 0) return null;
            $this->font_size_header = $font_size_header;
        }
        return $this->font_size_header;
    }
    /**
     * Footer font size
     * @var   integer
     */
    private $font_size_footer = 7;
    function font_size_footer($font_size_footer=null)
    {
        if (isset($font_size_footer)) {
            $font_size_footer = intval($font_size_footer);
            if ($font_size_footer <= 0) return null;
            $this->font_size_footer = $font_size_footer;
        }
        return $this->font_size_footer;
    }
    /**
     * List font size
     * @var   integer
     */
    private $font_size_list = 7;
    function font_size_list($font_size_list=null)
    {
        if (isset($font_size_list)) {
            $font_size_list = intval($font_size_list);
            if ($font_size_list <= 0) return null;
            $this->font_size_list = $font_size_list;
        }
        return $this->font_size_list;
    }
    /**
     * List name
     * @var   string
     */
    private $name = null;
    function name($name=null)
    {
        if (isset($name)) {
            $name = trim(strip_tags($name));
            if (!$name) return null;
            $this->name = $name;
        }
        return $this->name;
    }
    /**
     * Border enabled?
     * @var   boolean
     */
    private $border = true;
    function border($border=null)
    {
        if (isset($border)) {
            $this->border = (boolean)$border;
        }
        return $this->border;
    }
    /**
     * Header enabled?
     * @var   boolean
     */
    private $header = true;
    function header($header=null)
    {
        if (isset($header)) {
            $this->header = (boolean)$header;
        }
        return $this->header;
    }
    /**
     * Left header content
     * @var   string
     */
    private $header_left = null;
    function header_left($header_left=null)
    {
        if (isset($header_left)) {
            $header_left = trim(strip_tags($header_left));
            $this->header_left = $header_left;
        }
        return $this->header_left;
    }
    /**
     * Right header content
     * @var   string
     */
    private $header_right = null;
    function header_right($header_right=null)
    {
        if (isset($header_right)) {
            $header_right = trim(strip_tags($header_right));
            $this->header_right = $header_right;
        }
        return $this->header_right;
    }
    /**
     * Footer enabled?
     * @var   boolean
     */
    private $footer = true;
    function footer($footer=null)
    {
        if (isset($footer)) {
            $this->footer = (boolean)$footer;
        }
        return $this->footer;
    }
    /**
     * Left footer content
     * @var   string
     */
    private $footer_left = null;
    function footer_left($footer_left=null)
    {
        if (isset($footer_left)) {
            $footer_left = trim(strip_tags($footer_left));
            $this->footer_left = $footer_left;
        }
        return $this->footer_left;
    }
    /**
     * Right footer content
     * @var   string
     */
    private $footer_right = null;
    function footer_right($footer_right=null)
    {
        if (isset($footer_right)) {
            $footer_right = trim(strip_tags($footer_right));
            $this->footer_right = $footer_right;
        }
        return $this->footer_right;
    }
    /**
     * Category IDs included in the list (comma separated, or "*" for all)
     * @var   string
     */
    private $arrCategoryId = array('*');
    /**
     * Returns the comma separated list of Categories, or "*" for all
     *
     * Optionally sets the Category IDs to the value of $category_ids.
     * $category_ids may be a comma separated string, or an array of IDs.
     * @param   mixed   $category_ids   An optional comma separated list of
     *                                  Category IDs, or an array of IDs
     * @return  string                  The comma separated list of Category IDs
     */
    function category_ids($category_ids=null)
    {
        if (isset($category_ids)) {
            if (is_array($category_ids)) {
                $category_ids = join(',', $category_ids);
            }
            $category_ids = trim(strip_tags($category_ids));
            if (!$category_ids) $category_ids = '*';
            $this->arrCategoryId = preg_split('/\s*,\s*/', $category_ids,
                null, PREG_SPLIT_NO_EMPTY);
        }
        return join(',', $this->arrCategoryId);
    }


    /**
     * Constructor
     *
     * Uses FRONTEND_LANG_ID for $lang_id, and the default for $currency_id
     * if either is empty.
     * Note that the language ID is overridden by {@see load()} if it is
     * defined for this Pricelist.
     * @param   integer   $currency_id    The optional Currency ID
     * @param   integer   $lang_id        The optional language ID
     * @return  Pricelist                 The Pricelist
     */
    function __construct($list_id, $currency_id=null, $lang_id=null)
    {
        $this->id = intval($list_id);
        $this->currency_id = intval($currency_id);
        if (empty($this->currency_id)) {
            $this->currency_id = Currency::getDefaultCurrencyId();
        }
        $this->lang_id = intval($lang_id);
        if (empty($this->lang_id)) {
            $this->lang_id = FRONTEND_LANG_ID;
        }
    }


    /**
     * Creates a PDF document and sends this pricelist to the client
     *
     * Unfortunately, ezpdf does not return anything after printing the
     * document, so there's no way to tell whether it has succeeded.
     * Thus, you should not rely on the return value, except when it is
     * false -- in that case, loading of some data failed.
     * @return  boolean           False on failure, true on supposed success
     */
    function send_as_pdf()
    {
        global $objInit, $_ARRAYLANG;

        if (!$this->load()) {
            return \Message::error($_ARRAYLANG['TXT_SHOP_PRICELIST_ERROR_LOADING']);
        }
        $objPdf = new \Cezpdf('A4');
        $objPdf->setEncryption('', '', array('print'));
        $objPdf->selectFont(\Cx\Core\Core\Controller\Cx::instanciate()->getCodeBaseLibraryPath() . '/ezpdf/fonts/' . $this->font);
        $objPdf->ezSetMargins(0, 0, 0, 0); // Reset margins
        $objPdf->setLineStyle(0.5);
        $marginTop = 30;
        $biggerCountTop = $biggerCountBottom = 0;
        $arrHeaderLeft = $arrHeaderRight = $arrFooterLeft = $arrFooterRight =
            array();
        if ($this->header) { // header should be shown
            $arrHeaderLeft = explode("\n", $this->header_left);
            $arrHeaderRight = explode("\n", $this->header_right);
            $countLeft = count($arrHeaderLeft);
            $countRight = count($arrHeaderRight);
            $biggerCountTop = ($countLeft > $countRight
                ? $countLeft : $countRight);
            $marginTop = ($biggerCountTop * 14)+36;
        }
        // Bottom margin
        $marginBottom = 20;
        $arrFooterRight = array();
        if ($this->footer) { // footer should be shown
            // Old, obsolete:
            $this->footer_left = str_replace('<--DATE-->',
                date(ASCMS_DATE_FORMAT_DATE, time()), $this->footer_left);
            $this->footer_right = str_replace('<--DATE-->',
                date(ASCMS_DATE_FORMAT_DATE, time()), $this->footer_right);
            // New:
            $this->footer_left = str_replace('[DATE]',
                date(ASCMS_DATE_FORMAT_DATE, time()), $this->footer_left);
            $this->footer_right = str_replace('[DATE]',
                date(ASCMS_DATE_FORMAT_DATE, time()), $this->footer_right);
            $arrFooterLeft = explode("\n", $this->footer_left);
            $arrFooterRight = explode("\n", $this->footer_right);
            $countLeft = count($arrFooterLeft);
            $countRight = count($arrFooterRight);
            $biggerCountBottom = ($countLeft > $countRight
                ? $countLeft : $countRight);
            $marginBottom = ($biggerCountBottom * 20)+20;
        }
        // Borders
        if ($this->border) {
            $linesForAllPages = $objPdf->openObject();
            $objPdf->saveState();
            $objPdf->setStrokeColor(0, 0, 0, 1);
            $objPdf->rectangle(10, 10, 575.28, 821.89);
            $objPdf->restoreState();
            $objPdf->closeObject();
            $objPdf->addObject($linesForAllPages, 'all');
        }
        // Header
        $headerArray = array();
        $startpointY = 0;
        if ($this->header) {
            $objPdf->ezSetY(830);
            $headerForAllPages = $objPdf->openObject();
            $objPdf->saveState();
            for ($i = 0; $i < $biggerCountTop; ++$i) {
                $headerArray[$i] = array(
                    'left' => (isset($arrHeaderLeft[$i]) ? $arrHeaderLeft[$i] : ''),
                    'right' => (isset($arrHeaderRight[$i]) ? $arrHeaderRight[$i] : ''),
                );
            }
            $tempY = $objPdf->ezTable($headerArray, '', '', array(
                'showHeadings' => 0,
                'fontSize' => $this->font_size_header,
                'shaded' => 0,
                'width' => 540,
                'showLines' => 0,
                'xPos' => 'center',
                'xOrientation' => 'center',
                'cols' => array('right' => array('justification' => 'right')),
            ));
            $tempY -= 5;
            if ($this->border) {
                $objPdf->setStrokeColor(0, 0, 0);
                $objPdf->line(10, $tempY, 585.28, $tempY);
            }
            $startpointY = $tempY - 5;
            $objPdf->restoreState();
            $objPdf->closeObject();
            $objPdf->addObject($headerForAllPages, 'all');
        }
        // Footer
        $pageNumbersX = $pageNumbersY = $pageNumbersFont = 0;
        if ($this->footer) {
            $footerForAllPages = $objPdf->openObject();
            $objPdf->saveState();
            $tempY = $marginBottom - 5;
            if ($this->border) {
                $objPdf->setStrokeColor(0, 0, 0);
                $objPdf->line(10, $tempY, 585.28, $tempY);
            }
            // length of the longest word
            $longestWord = 0;
            foreach ($arrFooterRight as $line) {
                if ($longestWord < strlen($line)) {
                    $longestWord = strlen($line);
                }
            }
            for ($i = $biggerCountBottom-1; $i >= 0; --$i) {
                if (empty($arrFooterLeft[$i])) $arrFooterLeft[$i] = '';
                if (empty($arrFooterRight[$i])) $arrFooterRight[$i] = '';
                if (   $arrFooterLeft[$i] == '<--PAGENUMBER-->' // Old, obsolete
                    || $arrFooterLeft[$i] == '[PAGENUMBER]') {
                    $pageNumbersX = 65;
                    $pageNumbersY = $tempY-18-($i*$this->font_size_footer);
                    $pageNumbersFont = $this->font_size_list;
                } else {
                    $objPdf->addText(
                        25, $tempY-18-($i*$this->font_size_footer),
                        $this->font_size_footer, $arrFooterLeft[$i]);
                }
                if (   $arrFooterRight[$i] == '<--PAGENUMBER-->' // Old, obsolete
                    || $arrFooterRight[$i] == '[PAGENUMBER]') {
                    $pageNumbersX = 595.28-25;
                    $pageNumbersY = $tempY-18-($i*$this->font_size_footer);
                    $pageNumbersFont = $this->font_size_list;
                } else {
                    // Properly align right
                    $width = $objPdf->getTextWidth($this->font_size_footer, $arrFooterRight[$i]);
                    $objPdf->addText(
                        595.28-$width-25, $tempY-18-($i*$this->font_size_footer),
                        $this->font_size_footer, $arrFooterRight[$i]);
                }
            }
            $objPdf->restoreState();
            $objPdf->closeObject();
            $objPdf->addObject($footerForAllPages, 'all');
        }
        // Page numbers
        if (isset($pageNumbersX)) {
            $objPdf->ezStartPageNumbers(
                $pageNumbersX, $pageNumbersY, $pageNumbersFont, '',
                $_ARRAYLANG['TXT_SHOP_PRICELIST_FORMAT_PAGENUMBER'], 1);
        }
        // Margins
        $objPdf->ezSetMargins($marginTop, $marginBottom, 30, 30);
        // Product table
        if (isset($startpointY)) {
            $objPdf->ezSetY($startpointY);
        }
        $objInit->backendLangId = $this->lang_id;
        $_ARRAYLANG = $objInit->loadLanguageData('Shop');
        Currency::setActiveCurrencyId($this->currency_id);
        $currency_symbol = Currency::getActiveCurrencySymbol();
        $category_ids = $this->category_ids();
        if ($category_ids == '*') $category_ids = null;
        $count = 1000; // Be sensible!
        // Pattern is "%" because all-empty parameters will result in an
        // empty array!
        $arrProduct = Products::getByShopParams($count, 0, null,
            $category_ids, null, '%', null, null,
            '`category_id` ASC, `name` ASC');
        $arrCategoryName = ShopCategories::getNameArray();
        $arrOutput = array();
        foreach ($arrProduct as $product_id => $objProduct) {
            $categoryIds = explode(',', $objProduct->category_id());
            $arrCategoryNames = array();
            foreach ($categoryIds as $categoryId){
               $arrCategoryNames[] = $arrCategoryName[$categoryId];
            }
//$objProduct = new Product();
            $arrOutput[$product_id] = array(
                'product_name' => self::decode($objProduct->name()),
                'category_name' => self::decode(implode(', ', $arrCategoryNames)),
                'product_code' => self::decode($objProduct->code()),
                'product_id' => self::decode($objProduct->id()),
                'price' =>
                    ($objProduct->discount_active()
                        ? "S " . Currency::formatPrice($objProduct->discountprice())
                        : Currency::formatPrice($objProduct->price())) .
                    ' ' . $currency_symbol,
            );
        }
        $objPdf->ezTable($arrOutput, array(
            'product_name' => '<b>'.self::decode($_ARRAYLANG['TXT_SHOP_PRODUCT_NAME']).'</b>',
            'category_name' => '<b>'.self::decode($_ARRAYLANG['TXT_SHOP_CATEGORY_NAME']).'</b>',
            'product_code' => '<b>'.self::decode($_ARRAYLANG['TXT_SHOP_PRODUCT_CODE']).'</b>',
            'product_id' => '<b>'.self::decode($_ARRAYLANG['TXT_ID']).'</b>',
            'price' => '<b>'.self::decode($_ARRAYLANG['TXT_SHOP_PRICE']).'</b>'), '',
            array(
                'showHeadings' => 1,
                'fontSize' => $this->font_size_list,
                'width' => 530,
                'innerLineThickness' => 0.5,
                'outerLineThickness' => 0.5,
                'shaded' => 2,
                'shadeCol' => array(
                    hexdec(substr($this->row_color_1, 0, 2))/255,
                    hexdec(substr($this->row_color_1, 2, 2))/255,
                    hexdec(substr($this->row_color_1, 4, 2))/255,
                ),
                'shadeCol2' => array(
                    hexdec(substr($this->row_color_2, 0, 2))/255,
                    hexdec(substr($this->row_color_2, 2, 2))/255,
                    hexdec(substr($this->row_color_2, 4, 2))/255,
                ),
                // Note: 530 points in total
                'cols' => array(
                    'product_name' => array('width' => 255),
                    'category_name' => array('width' => 130),
                    'product_code' => array('width' => 50),
                    'product_id' => array('width' => 40, 'justification' => 'right'),
                    'price' => array('width' => 55, 'justification' => 'right')
                ),
            )
        );
        $objPdf->ezStream();
        // Never reached
        return true;
    }


    /**
     * (Re-)load this Pricelist
     *
     * Note that the language ID set in the dataset will override the one
     * set in the constructor iff it's not empty.
     * @return  boolean         True on success, false otherwise
     */
    function load()
    {
        global $objDatabase;

        $objResult = $objDatabase->Execute("
            SELECT `id`, `name`, `lang_id`,
                   `border_on`, `header_on`, `header_left`, `header_right`,
                   `footer_on`, `footer_left`, `footer_right`, `categories`
              FROM ".DBPREFIX."module_shop".MODULE_INDEX."_pricelists
             WHERE id=$this->id");
        if (!$objResult) {
            return false;
        }
        if ($objResult->EOF) {
            return false;
        }
        $this->name = self::decode($objResult->fields['name']);
        if ($objResult->fields['lang_id']) {
            $this->lang_id = $objResult->fields['lang_id'];
        }
        $this->border = $objResult->fields['border_on'];
        $this->header = $objResult->fields['header_on'];
        $this->header_left = self::decode($objResult->fields['header_left']);
        $this->header_right = self::decode($objResult->fields['header_right']);
        $this->footer = $objResult->fields['footer_on'];
        $this->footer_left = self::decode($objResult->fields['footer_left']);
        $this->footer_right = self::decode($objResult->fields['footer_right']);
        $this->category_ids($objResult->fields['categories']);
        return true;
    }


    /**
     * Returns the absolute URL for linking to the PDF list with the given ID
     * @param   integer   $list_id      The list ID
     * @return  string                  The list URL
     */
    static function getUrl($list_id)
    {
        return
            \Cx\Core\Routing\Url::fromModuleAndCmd('Shop', '', '',
                array('act' => 'pricelist', 'list_id' => $list_id, )
            )->toString();
    }


    /**
     * Returns the string decoded from UTF-8 if necessary
     * @param   string    $string       The string to be decoded
     * @return  string                  The decoded string
     */
    static function decode($string)
    {
        return $string;
    }


    /**
     * Returns an array of Pricelist names, indexed by their respective ID
     *
     * Backend use only.
     * @return  array             The Pricelist name array
     */
    static function getNameArray()
    {
        global $objDatabase;

        $query = "
            SELECT `id`, `name`
              FROM `".DBPREFIX."module_shop".MODULE_INDEX."_pricelists`
             ORDER BY `name` ASC";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
            return ShopManager::error_database();
        }
        if ($objResult->EOF) {
            return ShopManager::information_no_data();
        }
        $arrName = array();
        while (!$objResult->EOF) {
            $arrName[$objResult->fields['id']] = $objResult->fields['name'];
            $objResult->MoveNext();
        }
        return $arrName;
    }


    /**
     * Returns the Pricelist for the given ID
     *
     * @param   integer   $list_id    The Pricelist ID
     * @return  Pricelist             The Pricelist on success, false otherwise
     */
    static function getById($list_id)
    {
        global $objDatabase;

        $list_id = intval($list_id);
        if ($list_id <= 0) return false;
        $query = "
            SELECT `id`, `name`, `lang_id`,
                   `border_on`,
                   `header_on`, `header_left`, `header_right`,
                   `footer_on`, `footer_left`, `footer_right`,
                   `categories`
              FROM ".DBPREFIX."module_shop".MODULE_INDEX."_pricelists
             WHERE id=$list_id";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
            return ShopManager::error_database();
        }
        if ($objResult->EOF) {
            return ShopManager::information_no_data();
        }
        $objList = new PriceList($list_id, null, $objResult->fields['lang_id']);
        $objList->name($objResult->fields['name']);
        $objList->border($objResult->fields['border_on']);
        $objList->header($objResult->fields['header_on']);
        $objList->header_left($objResult->fields['header_left']);
        $objList->header_right($objResult->fields['header_right']);
        $objList->footer($objResult->fields['footer_on']);
        $objList->footer_left($objResult->fields['footer_left']);
        $objList->footer_right($objResult->fields['footer_right']);
        $objList->category_ids($objResult->fields['categories']);
        return $objList;
    }


    /**
     * Returns a Pricelist with properties taken from the posted data
     *
     * Backend use only.  Takes its arguments directly from the form values.
     * If no form has been posted, returns null.
     * @global  array       $_ARRAYLANG
     * @return  Pricelist                 The Pricelist on success,
     *                                    null otherwise
     */
    static function getFromPost()
    {
        global $_ARRAYLANG;

        if (empty($_POST['bsubmit'])) return null;
        $list_id = (isset($_POST['list_id'])
            ? intval($_POST['list_id'])
            : (isset($_GET['list_id'])
                ? intval($_GET['list_id']) : null));
        $objList = self::getById($list_id);
        if (!$objList) $objList = new PriceList(null);
        $objList->name(
            (empty($_POST['name'])
              ? $_ARRAYLANG['TXT_NO_NAME'] : $_POST['name']));
        $objList->lang_id(empty($_POST['lang_id']) ? null : $_POST['lang_id']);
        $objList->border(!empty($_POST['border']));
        $objList->header(!empty($_POST['header']));
        $objList->header_left(contrexx_input2raw($_POST['header_left']));
        $objList->header_right(contrexx_input2raw($_POST['header_right']));
        $objList->footer(!empty($_POST['footer']));
        $objList->footer_left(contrexx_input2raw($_POST['footer_left']));
        $objList->footer_right(contrexx_input2raw($_POST['footer_right']));
        $category_ids = '';
        if (empty($_POST['category_all'])
            && !empty($_POST['category_id'])) {
            foreach ($_POST['category_id'] as $category_id) {
                $category_ids .=
                    ($category_ids ? ',' : '').
                    contrexx_input2raw($category_id);
            }
        }
        // Both if no or all categories were selected, select all.
        if (empty($category_ids)) $category_ids = '*';
        $objList->category_ids($category_ids);
        return $objList;
    }


    static function deleteByRequest()
    {
        global $_ARAYLANG;

        if (!empty($_REQUEST['delete_list_id'])) {
            $result = self::deleteById($_REQUEST['delete_list_id']);
            if ($result) {
                return \Message::ok($_ARAYLANG['TXT_SHOP_PRICELIST_DELETED_SUCCESSFULLY']);
            }
            return \Message::error($_ARAYLANG['TXT_SHOP_PRICELIST_ERROR_FAILED_TO_DELETE']);
        }

        if (   !empty($_REQUEST['multi_action'])
            && !empty($_POST['list_id'])) {
            $result = self::deleteById($_POST['list_id']);
            if ($result) {
                return \Message::ok($_ARAYLANG['TXT_SHOP_PRICELISTS_DELETED_SUCCESSFULLY']);
            }
            return \Message::error($_ARAYLANG['TXT_SHOP_PRICELISTS_ERROR_FAILED_TO_DELETE']);
        }
        return null;
    }


    /**
     * Deletes one or more Pricelists from the database
     * @param   mixed     $mixed_list_id    A Pricelist ID or an array thereof
     * @return  boolean                     True on success, false otherwise
     */
    static function deleteById($mixed_list_id)
    {
        if (!is_array($mixed_list_id)) {
            $mixed_list_id = array($mixed_list_id);
        }
        $result = true;
        foreach ($mixed_list_id as $list_id) {
            $result &= self::_deleteById($list_id);
        }
        return $result;
    }


    /**
     * Deletes the Pricelist with the given ID
     *
     * Doesn't care whether the ID exists in the database.
     * @param   integer   $list_id    The Pricelist ID to be deleted
     * @return  boolean               True on success, false otherwise
     */
    private static function _deleteById($list_id)
    {
        global $objDatabase;

        $query = "
            DELETE FROM `".DBPREFIX."module_shop".MODULE_INDEX."_pricelists`
             WHERE `id`=$list_id";
        return (boolean)$objDatabase->Execute($query);
    }


    /**
     * Stores this Pricelist
     * @return  boolean         True on success, false otherwise
     */
    function store()
    {
        if ($this->id) {
            return self::update();
        }
        return self::insert();
    }


    /**
     * Updates this Pricelist
     * @return  boolean         True on success, false otherwise
     */
    function update()
    {
        global $objDatabase, $_ARRAYLANG;

        $query = "
            UPDATE `".DBPREFIX."module_shop".MODULE_INDEX."_pricelists`
               SET `name`='".contrexx_raw2db($this->name)."',
                   `lang_id`=".intval($this->lang_id).",
                   `border_on`=".intval($this->border).",
                   `header_on`=".intval($this->header).",
                   `header_left`='".contrexx_raw2db($this->header_left)."',
                   `header_right`='".contrexx_raw2db($this->header_right)."',
                   `footer_on`=".intval($this->footer).",
                   `footer_left`='".contrexx_raw2db($this->footer_left)."',
                   `footer_right`='".contrexx_raw2db($this->footer_right)."',
                   `categories`='".join(',', $this->arrCategoryId)."'
             WHERE `id`=$this->id";
        if ($objDatabase->Execute($query)) {
            return \Message::ok($_ARRAYLANG['TXT_SHOP_PRICELIST_UPDATED_SUCCESSFULLY']);
        }
        return \Message::error($_ARRAYLANG['TXT_SHOP_PRICELIST_ERROR_UPDATING']);
    }


    /**
     * Inserts this Pricelist
     *
     * Updates the ID property accordingly.
     * @return  boolean         True on success, false otherwise
     */
    function insert()
    {
        global $objDatabase, $_ARRAYLANG;

        $query = "
            INSERT INTO `".DBPREFIX."module_shop".MODULE_INDEX."_pricelists` (
              `name`, `lang_id`, `border_on`,
              `header_on`, `header_left`, `header_right`,
              `footer_on`, `footer_left`, `footer_right`,
              `categories`
            ) VALUES (
              '".contrexx_raw2db($this->name)."',
              ".intval($this->lang_id).",
              ".intval($this->border).",
              ".intval($this->header).",
              '".contrexx_raw2db($this->header_left)."',
              '".contrexx_raw2db($this->header_right)."',
              ".intval($this->footer).",
              '".contrexx_raw2db($this->footer_left)."',
              '".contrexx_raw2db($this->footer_right)."',
              '".join(',', $this->arrCategoryId)."'
            )";
        if ($objDatabase->Execute($query)) {
            $this->id($objDatabase->Insert_ID());
            return \Message::ok($_ARRAYLANG['TXT_SHOP_PRICELIST_INSERTED_SUCCESSFULLY']);
        }
        return \Message::error($_ARRAYLANG['TXT_SHOP_PRICELIST_ERROR_INSERTING']);
    }

}
