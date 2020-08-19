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
 * ProductController to handle products
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
namespace Cx\Modules\Shop\Controller;

/**
 * ProductController to handle products
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class ProductController extends \Cx\Core\Core\Model\Entity\Controller
{
    const DEFAULT_VIEW_NONE = 0;
    const DEFAULT_VIEW_MARKED = 1;
    const DEFAULT_VIEW_DISCOUNTS = 2;
    const DEFAULT_VIEW_LASTFIVE = 3;
    const DEFAULT_VIEW_COUNT = 4;

    /**
     * Get ViewGenerator options for entity
     *
     * @param $options array predefined ViewGenerator options
     *
     * @return array includes ViewGenerator options for entity
     */
    public function getViewGeneratorOptions($options)
    {
        global $_ARRAYLANG;

        $options['template']['tableView'] = 'modules/Shop/View/Template/Backend/CustomSearch.html';

        $options['showPrimaryKeys'] = true;
        $options['functions']['copy'] = true;
        $options['functions']['editable'] = true;
        $options['functions']['searching'] = true;
        $options['functions']['filtering'] = true;
        $options['functions']['sortBy'] = array(
            'field' => array('ord' => SORT_ASC)
        );
        $options['functions']['status'] = array(
            'field' => 'active'
        );

        $options['tabs']['overview']['header'] = $_ARRAYLANG[
            'TXT_PRODUCT_INFORMATIONS'
        ];
        $options['tabs']['product-option'] = array(
            'header' => $_ARRAYLANG['TXT_PRODUCT_OPTIONS'],
            'fields' => array(
                'relProductAttributes'
            )
        );
        $options['tabs']['product-picture'] = array(
            'header' => $_ARRAYLANG['TXT_PRODUCT_IMAGE'],
            'fields' => array(
                'picture'
            )
        );
        $options['tabs']['product-status'] = array(
            'header' => $_ARRAYLANG['TXT_PRODUCT_STATUS'],
            'fields' => array(
                'active',
                'b2b',
                'b2c',
                'dateStart',
                'dateEnd'
            )
        );

        $options['order']['overview'] = array(
            'id',
            'active',
            'flags',
            'name',
            'code',
            'discountActive',
            'discountprice',
            'normalprice',
            'resellerprice',
            'vat',
            'distribution',
            'stock',
        );
        $options['order']['form'] = array(
            'id',
            'name',
            'categories',
            'code',
            'normalprice',
            'resellerprice',
            'discountprice',
            'discountActive',
            'articleGroup',
            'discountgroupCountName',
            'vat',
            'distribution',
            'userGroups',
            'weight',
            'short',
            'long',
            'keys',
            'manufacturer',
            'uri',
            'stock',
            'stockVisible',
            'minimumOrderQuantity',
            'relProductAttributes',
            'picture',
            'active',
            'b2b',
            'b2c',
            'dateStart',
            'dateEnd'
        );

        $options['fields'] = array(
            'locale' => array(
                'allowFiltering' => false,
            ),
            'id' => array(
                'allowFiltering' => false,
            ),
            'flags' => array(
                'header' => $this->getFlagHeader(),
                'editable' => true,
                'sorting' => false,
                'allowFiltering' => false,
                'type' => 'checkboxes',
                'validValues' => '__SHOWONSTARTPAGE__',
                'storecallback' => array(
                    'adapter' => 'Product',
                    'method' => 'toggleCheckbox'
                ),
            ),
            'name' => array(
                'header' => $_ARRAYLANG['TXT_PRODUCT_NAME'],
                'allowFiltering' => false,
                'allowSearching' => true,
                'table' => array(
                    'attributes' => array(
                        'class' => 'product-name',
                    ),
                    'parse' => array(
                        'adapter' => 'Product',
                        'method' => 'addEditLink'
                    )
                ),
            ),
            'discountActive' => array(
                'editable' => true,
                'header' => '',
                'sorting' => false,
                'allowFiltering' => false,
                'table' => array(
                    'attributes' => array(
                        'class' => 'small'
                    ),
                    'parse' => array(
                        'adapter' => 'Product',
                        'method' => 'getDiscountActiveOverviewCheckbox'
                    )
                ),
                'storecallback' => array(
                    'adapter' => 'Product',
                    'method' => 'storeDiscountActive'
                ),
            ),
            'discountprice' => array(
                'header' => '<span id="discountHeader">'
                    . $_ARRAYLANG['discountprice'] . '</span>',
                'formtext' =>  $_ARRAYLANG['discountprice'],
                'editable' => true,
                'allowFiltering' => false,
                'attributes' => array(
                    'class' => 'small',
                ),
                'formfield' => array(
                    'adapter' => 'Product',
                    'method' => 'getDiscountPrice'
                ),
            ),
            'normalprice' => array(
                'formtext' => $_ARRAYLANG['normalprice-detail'],
                'editable' => true,
                'allowFiltering' => false,
                'attributes' => array(
                    'class' => 'small',
                ),
            ),
            'resellerprice' => array(
                'formtext' => $_ARRAYLANG['resellerprice-detail'],
                'editable' => true,
                'allowFiltering' => false,
                'attributes' => array(
                    'class' => 'small',
                ),
            ),
            'vat' => array(
                'formtext' => $_ARRAYLANG['vat-detail'],
                'header' => $_ARRAYLANG['TXT_SHOP_PRODUCT_VAT_RATE'],
                'editable' => true,
                'sorting' => false,
                'allowFiltering' => false,
                'table' => array(
                    'parse' => array(
                        'adapter' => 'Product',
                        'method' => 'getOverviewVatDropdown'
                    )
                ),
                'formfield' => array(
                    'adapter' => 'Product',
                    'method' => 'getDetailVatDropdown'
                ),
            ),
            'stock' => array(
                'editable' => true,
                'allowFiltering' => false,
                'attributes' => array(
                    'class' => 'small',
                ),
                'formfield' => array(
                    'adapter' => 'Product',
                    'method' => 'getDetailStock'
                ),
            ),
            'pdfTemplate' => array(
                'showOverview' => false,
            ),
            'distribution' => array(
                'type' => 'select',
                'formfield' => array(
                    'adapter' => 'Product',
                    'method' => 'getDistributionDropdown'
                ),
                'table' => array(
                    'parse' => function($value) {
                        global $_ARRAYLANG;
                        return $_ARRAYLANG[
                            'TXT_DISTRIBUTION_' . strtoupper($value)
                        ];
                    }
                ),
                'allowFiltering' => false,
            ),
            'code' => array(
                'editable' => true,
                'header' => $_ARRAYLANG['TXT_SHOP_PRODUCT_CODE'],
                'formtext' => $_ARRAYLANG['productCode'],
                'allowFiltering' => false,
                'attributes' => array(
                    'size' => '10'
                ),
            ),
            'picture' => array(
                'showOverview' => false,
                'header' => $_ARRAYLANG['TXT_SHOP_EDIT_OR_ADD_IMAGE'],
                'allowFiltering' => false,
                'formfield' => array(
                    'adapter' => 'Product',
                    'method' => 'getImageBrowser'
                ),
                'storecallback' => array(
                    'adapter' => 'Product',
                    'method' => 'storePicture'
                )
            ),
            'groupId' => array(
                'showOverview' => false,
                'showDetail' => false,
                'allowFiltering' => false,
            ),
            'stockVisible' => array(
                'showOverview' => false,
                'allowFiltering' => false,
            ),
            'active' => array(
                'allowFiltering' => false,
            ),
            'b2b' => array(
                'showOverview' => false,
                'allowFiltering' => false,
            ),
            'b2c' => array(
                'showOverview' => false,
                'allowFiltering' => false,
            ),
            'categories' => array(
                'showOverview' => false,
                'mode' => 'associate',
                'storecallback' => array(
                    'adapter' => 'Product',
                    'method' => 'storeCategories',
                ),
                'filterOptionsField' => array(
                    'adapter' => 'Product',
                    'method' => 'getCategoryFilter'
                ),
            ),
            'dateStart' => array(
                'showOverview' => false,
                'allowFiltering' => false,
                'postCallback' => array(
                    'adapter' => 'Product',
                    'method' => 'setEmptyDateToNull'
                ),
            ),
            'dateEnd' => array(
                'showOverview' => false,
                'allowFiltering' => false,
                'postCallback' => array(
                    'adapter' => 'Product',
                    'method' => 'setEmptyDateToNull'
                ),
            ),
            'manufacturerId' => array(
                'showOverview' => false,
                'showDetail' => false,
                'allowFiltering' => false,
            ),
            'ord' => array(
                'showOverview' => false,
                'showDetail' => false,
                'allowFiltering' => false,
            ),
            'vatId' => array(
                'showOverview' => false,
                'showDetail' => false,
                'allowFiltering' => false,
            ),
            'weight' => array(
                'showOverview' => false,
                'showDetail' => false,
                'allowFiltering' => false,
                'type' => 'string',
                'valueCallback' => array(
                    'adapter' => 'Product',
                    'method' => 'getWeight'
                ),
                'storecallback' => array(
                    'adapter' => 'Product',
                    'method' => 'storeWeight',
                ),
            ),
            'articleId' => array(
                'showOverview' => false,
                'showDetail' => false,
                'allowFiltering' => false,
            ),
            'minimumOrderQuantity' => array(
                'showOverview' => false,
                'allowFiltering' => false,
            ),
            'uri' => array(
                'showOverview' => false,
                'allowFiltering' => false,
                'tooltip' => $_ARRAYLANG['TXT_WITH_HTTP'],
            ),
            'short' => array(
                'showOverview' => false,
                'type' => 'wysiwyg',
                'mode' => 'small',
                'allowFiltering' => false,
            ),
            'long' => array(
                'showOverview' => false,
                'type' => 'wysiwyg',
                'mode' => 'full',
                'allowFiltering' => false,
            ),
            'keys' => array(
                'showOverview' => false,
                'tooltip' => $_ARRAYLANG['TXT_SHOP_KEYWORDS_TOOLTIP'],
                'allowFiltering' => false,
            ),
            'discountCoupons' => array(
                'showOverview' => false,
                'showDetail' => false,
                'allowFiltering' => false,
            ),
            'orderItems' => array(
                'showOverview' => false,
                'showDetail' => false,
                'allowFiltering' => false,
            ),
            'relProductAttributes' => array(
                'showOverview' => false,
                'allowFiltering' => false,
                'formfield' => array(
                    'adapter' => 'Product',
                    'method' => 'getProductAttributes'
                ),
                'storecallback' => function() {
                    return null;
                },
                'postCallback' => array(
                    'adapter' => 'Product',
                    'method' => 'storeProductAttributes'
                ),
            ),
            'manufacturer' => array(
                'showOverview' => false,
                'allowFiltering' => false,
            ),
            'discountgroupCountName' => array(
                'showOverview' => false,
                'mode' => 'associate',
                'allowFiltering' => false,
            ),
            'articleGroup' => array(
                'showOverview' => false,
                'mode' => 'associate',
                'allowFiltering' => false,
            ),
            'userGroups' => array(
                'showOverview' => false,
                'mode' => 'associate',
                'allowFiltering' => false,
            ),
        );

        \ContrexxJavascript::getInstance()->setVariable(
            'DISTRIBUTION_DELIVERY_INDEX',
            Distribution::TYPE_DELIVERY,
            'shop_product'
        );
        \ContrexxJavascript::getInstance()->setVariable(
            'DISTRIBUTION_DOWNLOAD_INDEX',
            Distribution::TYPE_DOWNLOAD,
            'shop_product'
        );

        \Cx\Core\Setting\Controller\Setting::init('Shop', 'config');

        if (\Cx\Core\Setting\Controller\Setting::getValue('weight_enable','Shop')) {
            $options['fields']['weight']['showDetail'] = true;
        }

        return $options;
    }

    /**
     * Return a div with an icon and tooltip for the flag attribute.
     *
     * @return \Cx\Core\Html\Model\Entity\HtmlElement
     */
    protected function getFlagHeader()
    {
        global $_ARRAYLANG;
        $wrapper = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
        $img = new \Cx\Core\Html\Model\Entity\HtmlElement('img');
        $tooltipTrigger = new \Cx\Core\Html\Model\Entity\HtmlElement('span');
        $empty = new \Cx\Core\Html\Model\Entity\TextElement('');
        $tooltipMessage = new \Cx\Core\Html\Model\Entity\HtmlElement('span');
        $message = new \Cx\Core\Html\Model\Entity\TextElement(
            $_ARRAYLANG['TXT_SHOP_SHOW_PRODUCT_ON_START_PAGE_TIP']
        );

        $img->setAttributes(
            array(
                'style' => 'display:block',
                'src' => $this->cx->getCodeBaseCoreWebPath()
                    . '/Core/View/Media/icons/home.gif'
            )
        );

        $tooltipTrigger->addClass('tooltip-trigger icon-info');
        $tooltipMessage->addClass('tooltip-message');
        $tooltipTrigger->addChild($empty);
        $tooltipMessage->addChild($message);
        $wrapper->addChildren(array($img, $tooltipTrigger, $tooltipMessage));

        return $wrapper;
    }

    /**
     * Returns an array of image names, widths and heights from
     * the base64 encoded string taken from the database
     *
     * The array returned looks like
     *  array(
     *    1 => array(
     *      'img' => <image1>,
     *      'width' => <image1.width>,
     *      'height' => <image1.height>
     *    ),
     *    2 => array( ... ), // The same as above, three times in total
     *    3 => array( ... ),
     * )
     * @param   string  $base64Str  The base64 encoded image string
     * @return  array               The decoded image array
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function get_image_array_from_base64($base64Str)
    {
        // Pre-init array to avoid "undefined index" notices
        $arrPictures = array(
            1 => array('img' => '', 'width' => 0, 'height' => 0),
            2 => array('img' => '', 'width' => 0, 'height' => 0),
            3 => array('img' => '', 'width' => 0, 'height' => 0)
        );
        if (strpos($base64Str, ':') === false)
            // have to return an array with the desired number of elements
            // and an empty file name in order to show the "dummy" picture(s)
            return $arrPictures;
        $i = 0;
        foreach (explode(':', $base64Str) as $imageData) {
            $shopImage = $shopImage_width = $shopImage_height = null;
            list($shopImage, $shopImage_width, $shopImage_height) = explode(
                '?', $imageData
            );
            $shopImage        = base64_decode($shopImage);
            $shopImage_width  = base64_decode($shopImage_width);
            $shopImage_height = base64_decode($shopImage_height);
            $arrPictures[++$i] = array(
                'img' => $shopImage,
                'width' => $shopImage_width,
                'height' => $shopImage_height,
            );
        }
        return $arrPictures;
    }

    /**
     * Returns HTML code for dropdown menu options to choose the default
     * view on the Shop starting page.
     *
     * Possible choices are defined by global constants
     * self::DEFAULT_VIEW_* and corresponding language variables.
     * @static
     * @param   string   $selected     The optional preselected view index
     *
     * @return  string                  The HTML menu options
     */
    static function getDefaultViewMenuoptions($selected='')
    {
        global $_ARRAYLANG;

        $strMenuoptions = '';
        for ($i = 0; $i < self::DEFAULT_VIEW_COUNT; ++$i) {
            $strMenuoptions .=
                "<option value='$i'".
                ($selected == $i ? ' selected="selected"' : '').'>'.
                $_ARRAYLANG['TXT_SHOP_PRODUCT_DEFAULT_VIEW_'.$i].
                "</option>\n";
        }
        return $strMenuoptions;
    }

    /**
     * Returns an array of Products selected by parameters as available in
     * the Shop.
     *
     * The $count parameter is set to the number of records found.
     * After it returns, it contains the actual number of matching Products.
     * @param   integer     $count          The desired number of Products,
     *                                      by reference.  Set to the actual
     *                                      total matching number.
     * @param   integer     $offset         The Product offset
     * @param   integer|array   $product_id     The optional Product ID,
     *                                      or an array of such
     * @param   integer     $category_id    The ShopCategory ID
     * @param   integer     $manufacturer_id  The Manufacturer ID
     * @param   string      $pattern        A search pattern
     * @param   boolean     $flagSpecialoffer Limit results to special offers
     *                                      if true.  Disabled if either
     *                                      the Product ID, Category ID,
     *                                      Manufacturer ID, or the search
     *                                      pattern is non-empty.
     * @param   boolean     $flagLastFive   Limit results to the last five
     *                                      Products added to the Shop if true.
     *                                      Note: You may specify an integer
     *                                      count as well, this will set the
     *                                      limit accordingly.
     * @param   string     $orderSetting   The sorting order setting, defaults
     *                                      to the order field value ascending,
     *                                      Product ID descending
     * @param   boolean     $flagIsReseller The reseller status of the
     *                                      current customer, ignored if
     *                                      it's the empty string
     * @param   boolean     $flagShowInactive   Include inactive Products
     *                                      if true.  Backend use only!
     * @return  array                       Array of Product objects,
     *                                      or false if none were found
     * @static
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    static function getByShopParams(
        &$count, $offset=0,
        $product_id=null, $category_id=null,
        $manufacturer_id=null, $pattern=null,
        $flagSpecialoffer=false, $flagLastFive=false,
        $orderSetting='',
        $flagIsReseller=null,
        $flagShowInactive=false
    ) {
        global $objDatabase, $_CONFIG;

//\DBG::activate(DBG_ADODB_ERROR|DBG_LOG_FIREPHP);

        // Do not show any Products if no selection is made at all
        if (   empty($product_id)
            && empty($category_id)
            && empty($manufacturer_id)
            && empty($pattern)
            && empty($flagSpecialoffer)
            && empty($flagLastFive)
            && empty($flagShowInactive) // Backend only!
        ) {
            $count = 0;
            return array();
        }
// NOTE:
// This was an optimization, but does not (yet) consider the other parameters.
//        if ($product_id) {
//            // Select single Product by ID
//            $objProduct = Product::getById($product_id);
//            // Inactive Products MUST NOT be shown in the frontend
//            if (   $objProduct
//                && ($flagShowInactive || $objProduct->active())) {
//                $count = 1;
//                return array($objProduct);
//            }
//            $count = 0;
//            return false;
//        }
        list($querySelect, $queryCount, $queryTail, $queryOrder) =
            \Cx\Modules\Shop\Controller\Products::getQueryParts(
                $product_id, $category_id, $manufacturer_id, $pattern,
                $flagSpecialoffer, $flagLastFive, $orderSetting,
                $flagIsReseller, $flagShowInactive
            );
        $limit = ($count > 0
            ? $count
            : (!empty($_CONFIG['corePagingLimit'])
                ? $_CONFIG['corePagingLimit'] : 10));
        $count = 0;
//\DBG::activate(DBG_ADODB);
        $objResult = $objDatabase->SelectLimit(
            $querySelect.$queryTail.$queryOrder, $limit, $offset
        );
        if (!$objResult) return Products::errorHandler();
//\DBG::deactivate(DBG_ADODB);
        $arrProduct = array();
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $productRepo = $cx->getDb()->getEntityManager()->getRepository(
            'Cx\Modules\Shop\Model\Entity\Product'
        );
        while (!$objResult->EOF) {
            $product_id = $objResult->fields['id'];
            $objProduct = $productRepo->find($product_id);
            if ($objProduct)
                $arrProduct[$product_id] = $objProduct;
            $objResult->MoveNext();
        }
        $objResult = $objDatabase->Execute($queryCount.$queryTail);
        if (!$objResult) return false;
        $count = $objResult->fields['numof_products'];
//\DBG::log("Products::getByShopParams(): Set count to $count");
        return $arrProduct;
    }


    /**
     * Create thumbnails and update the corresponding Product records
     *
     * Scans the Products with the given IDs.  If a non-empty picture string
     * with a reasonable extension is encountered, determines whether
     * the corresponding thumbnail is available and up to date or not.
     * If not, tries to load the file and to create a thumbnail.
     * If it succeeds, it also updates the picture field with the base64
     * encoded entry containing the image width and height.
     * Note that only single file names are supported!
     * Also note that this method returns a string with information about
     * problems that were encountered.
     * It skips records which contain no or invalid image
     * names, thumbnails that cannot be created, and records which refuse
     * to be updated!
     * The reasoning behind this is that this method is currently only called
     * from within some {@link _import()} methods.  The focus lies on importing
     * Products; whether or not thumbnails can be created is secondary, as the
     * process can be repeated if there is a problem.
     * @param   integer     $arrId      The array of Product IDs
     * @return  boolean                 True on success, false on any error
     * @global  ADONewConnection  $objDatabase    Database connection object
     * @global  array
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function makeThumbnailsById($arrId)
    {
        global $_ARRAYLANG;

        if (!is_array($arrId)) return false;
        $error = false;
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $productRepo = $cx->getDb()->getEntityManager()->getRepository(
            'Cx\Modules\Shop\Model\Entity\Product'
        );
        $objImageManager = new \ImageManager();
        foreach ($arrId as $product_id) {
            if ($product_id <= 0) {
                \Message::error(
                    sprintf(
                        $_ARRAYLANG['TXT_SHOP_INVALID_PRODUCT_ID'],
                        $product_id
                    )
                );
                $error = true;
                continue;
            }
            $objProduct = $productRepo->find($product_id);
            if (!$objProduct) {
                \Message::error(
                    sprintf(
                        $_ARRAYLANG['TXT_SHOP_INVALID_PRODUCT_ID'],
                        $product_id
                    )
                );
                $error = true;
                continue;
            }
            $imageName = $objProduct->getPicture();
            $imagePath = \Cx\Core\Core\Controller\Cx::instanciate()
                    ->getWebsiteImagesShopPath() . '/' . $imageName;
            // only try to create thumbs from entries that contain a
            // plain text file name (i.e. from an import)
            if (   $imageName == ''
                || !preg_match('/\.(?:jpg|jpeg|gif|png)$/i', $imageName)
            ) {
                \Message::error(
                    sprintf(
                        $_ARRAYLANG['TXT_SHOP_UNSUPPORTED_IMAGE_FORMAT'],
                        $product_id, $imageName
                    )
                );
                $error = true;
                continue;
            }
            // if the picture is missing, skip it.
            if (!file_exists($imagePath)) {
                \Message::error(
                    sprintf(
                        $_ARRAYLANG['TXT_SHOP_MISSING_PRODUCT_IMAGE'],
                        $product_id, $imageName
                    )
                );
                $error = true;
                continue;
            }
            $thumbResult = true;
            $width  = 0;
            $height = 0;
            // If the thumbnail exists and is newer than the picture,
            // don't create it again.
            $thumb_name = \ImageManager::getThumbnailFilename($imagePath);
            if (file_exists($thumb_name) &&
                filemtime($thumb_name) > filemtime($imagePath)
            ) {
                //$this->addMessage("Hinweis: Thumbnail fuer Produkt ID '$product_id' existiert bereits");
                // Need the original size to update the record, though
                list($width, $height) =
                    $objImageManager->_getImageSize($imagePath);
            } else {
                // Create thumbnail, get the original size.
                // Deleting the old thumb beforehand is integrated into
                // _createThumbWhq().
                $thumbResult = $objImageManager->_createThumbWhq(
                    \Cx\Core\Core\Controller\Cx::instanciate()
                        ->getWebsiteImagesShopPath() . '/',
                    \Cx\Core\Core\Controller\Cx::instanciate()
                        ->getWebsiteImagesShopWebPath() . '/',
                    $imageName,
                    \Cx\Core\Setting\Controller\Setting::getValue(
                        'thumbnail_max_width', 'Shop'
                    ),
                    \Cx\Core\Setting\Controller\Setting::getValue(
                        'thumbnail_max_height', 'Shop'
                    ),
                    \Cx\Core\Setting\Controller\Setting::getValue(
                        'thumbnail_quality', 'Shop'
                    )
                );
                $width  = $objImageManager->orgImageWidth;
                $height = $objImageManager->orgImageHeight;
            }
            // The database needs to be updated, however, as all Products
            // have been imported.
            if ($thumbResult) {
                $shopPicture =
                    base64_encode($imageName).
                    '?'.base64_encode($width).
                    '?'.base64_encode($height).
                    ':??:??';
                $objProduct->setPicture($shopPicture);
                $cx->getDb()->getEntityManager()->persist($objProduct);
            } else {
                \Message::error(
                    sprintf(
                        $_ARRAYLANG[
                            'TXT_SHOP_ERROR_CREATING_PRODUCT_THUMBNAIL'
                        ],
                        $product_id, $imageName
                    )
                );
                $error = true;
            }
        }
        $cx->getDb()->getEntityManager()->flush();
        return $error;
    }

    /**
     * Returns the HTML dropdown menu options for the product sorting
     * order menu
     * @return    string            The HTML code string
     * @author    Reto Kohli <reto.kohli@comvation.com>
     * @static
     */
    static function getProductSortingMenuoptions()
    {
        global $_ARRAYLANG;

        $arrAvailableOrder = array(
            1 => $_ARRAYLANG['TXT_SHOP_PRODUCT_SORTING_INDIVIDUAL'],
            2 => $_ARRAYLANG['TXT_SHOP_PRODUCT_SORTING_ALPHABETIC'],
            3 => $_ARRAYLANG['TXT_SHOP_PRODUCT_SORTING_PRODUCTCODE'],
        );
        return \Html::getOptions($arrAvailableOrder,
            \Cx\Core\Setting\Controller\Setting::getValue(
                'product_sorting','Shop'
            )
        );
    }

    /**
     * Delete Products from the ShopCategory given by its ID.
     *
     * If deleting one of the Products fails, aborts and returns false
     * immediately without trying to delete the remaining Products.
     * Deleting the ShopCategory after this method failed will most
     * likely result in Product bodies in the database!
     * @param   integer     $category_id        The ShopCategory ID
     * @param   boolean     $flagDeleteImages   Delete images, if true
     * @param   boolean     $recursive          Delete Products from
     *                                          subcategories, if true
     * @return  boolean                         True on success, null on noop,
     *                                          false otherwise
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function deleteByShopCategory($category_id, $flagDeleteImages=false,
                                         $recursive=false)
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $catRepo = $em->getRepository('Cx\Modules\Shop\Model\Entity\Category');

        // Verify that the Category still exists
        $objShopCategory = $catRepo->find($category_id);
        if (!$objShopCategory) {
//\DBG::log("Products::deleteByShopCategory($category_id, $flagDeleteImages): Info: Category ID $category_id does not exist");
            return null;
        }

        $products = $objShopCategory->getProducts();
        if (empty($products)) {
//\DBG::log("Products::deleteByShopCategory($category_id, $flagDeleteImages): Failed to get Product IDs in that Category");
            return false;
        }
        // Look whether this is within a virtual ShopCategory
        $virtualContainer = '';
        $parent_id = $category_id;
        do {
            $objShopCategory = $catRepo->find($parent_id);
            if (!$objShopCategory) {
//\DBG::log("Products::deleteByShopCategory($category_id, $flagDeleteImages): Failed to get parent Category");
                return false;
            }
            if ($objShopCategory->isVirtual()) {
                // The name of any virtual ShopCategory is used to mark
                // Products within
                $virtualContainer = $objShopCategory->getName();
                break;
            }
            $parent_id = $objShopCategory->getParentId();
        } while ($parent_id != 0);

        // Remove the Products in one way or another
        foreach ($products as $product) {
            if (empty($product)) {
//\DBG::log("Products::deleteByShopCategory($category_id, $flagDeleteImages): Failed to get Product IDs $product_id");
                return false;
            }
            if ($virtualContainer != ''
                && $product->getFlags() != '') {
                // Virtual ShopCategories and their content depends on
                // the Product objects' flags.
                $product->removeFlag($virtualContainer);
                $em->persist($product);
                if (!self::changeFlagsByProductCode(
                    $product->getCode(),
                    $product->getFlags()
                )) {
//\DBG::log("Products::deleteByShopCategory($category_id, $flagDeleteImages): Failed to update Product flags for ID ".$objProduct->id());
                    return false;
                }

            } else {
                // Normal, non-virtual ShopCategory.
                // Remove Products having the same Product code.
                // Don't delete Products having more than one Category assigned.
                // Instead, remove them from the chosen Category only.
                $arrCategoryId = $product->getCategories();
                if (count($arrCategoryId) > 1) {
                    $product->removeCategory($objShopCategory);
                    $em->persist($product);
                } else {
                    $em->remove($product);
                }
            }
        }
        $em->flush();

        if ($recursive) {
            $arrCategories = $objShopCategory->getChildren();
            foreach ($arrCategories as $category) {
                if (!self::deleteByShopCategory(
                    $category->getId(), $flagDeleteImages, $recursive)) {
                    \DBG::log(
                        "ERROR: Failed to delete Products in Category ID "
                        . $category->getId()
                    );
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Apply the flags to all Products matching the given Product code
     *
     * Any Product and ShopCategory carrying one or more of the names
     * of any ShopCategory marked as "__VIRTUAL__" is cloned and added
     * to that category.  Those having any such flags removed are deleted
     * from the respective category.  Identical copies of the same Products
     * are recognized by their "product_id" (the Product code).
     *
     * Note that in this current version, only the flags of Products are
     * tested and applied.  Products are cloned and added together with
     * their immediate parent ShopCategories (aka "Article").
     *
     * Thus, all Products within the same "Article" ShopCategory carry the
     * same flags, as does the containing ShopCategory itself.
     * @param   integer     $productCode  The Product code (*NOT* the ID).
     *                                    This must be non-empty!
     * @param   string      $strNewFlags  The new flags for the Product
     * @static
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    static function changeFlagsByProductCode($productCode, $strNewFlags)
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();

        if (empty($productCode)) return false;
        // Get all available flags.  These are represented by the names
        // of virtual root ShopCategories.
        $arrVirtual = ShopCategories::getVirtualCategoryNameArray();

        // Get the affected identical Products
        $arrProduct = self::getByCustomId($productCode);
        // No way we can do anything useful without them.
        if (count($arrProduct) == 0) return false;

        // Get the Product flags.  As they're all the same, we'll use the
        // first one here.
        // Note that this object is used for reference only and is never stored.
        // Its database entry will be updated along the way, however.
        $_objProduct = $arrProduct[0];
        $strOldFlags = $_objProduct->getFlags();
        // Flag indicating whether the article has been cloned already
        // for all new flags set.
        $flagCloned = false;

        // Now apply the changes to all those identical Products, their parent
        // ShopCategories, and all sibling Products within them.
        foreach ($arrProduct as $objProduct) {
            // Get the containing article ShopCategory.
            $category = $objProduct->getCategories();
            $objArticleCategory = $category->first();
            if (!$objArticleCategory) continue;

            // Get parent (subgroup)
            $objSubGroupCategory =
                ShopCategory::getById($objArticleCategory->getParentId());
            // This should not happen!
            if (!$objSubGroupCategory) continue;
            $subgroupName = $objSubGroupCategory->getName();

            // Get grandparent (group, root ShopCategory)
            $objRootCategory =
                ShopCategory::getById($objSubGroupCategory->getParentId());
            if (!$objRootCategory) continue;

            // Apply the new flags to all Products and Article ShopCategories.
            // Update the flags of the original Article ShopCategory first
            $objArticleCategory->getFlags($strNewFlags);
            $em->persist($objArticleCategory);

            // Get all sibling Products affected by the same flags
            $arrSiblingProducts = Products::getByShopCategory(
                $objArticleCategory->getId()
            );

            // Set the new flag set for all Products within the Article
            // ShopCategory.
            foreach ($arrSiblingProducts as $objProduct) {
                $objProduct->getFlags($strNewFlags);
                $em->persist($objProduct);
            }

            // Check whether this group is affected by the changes.
            // If its name matches one of the flags, the Article and subgroup
            // may have to be removed.
            $strFlag = $objRootCategory->getName();
            if (preg_match("/$strFlag/", $strNewFlags))
                // The flag is still there, don't bother.
                continue;

            // Also check whether this is a virtual root ShopCategory.
            if (in_array($strFlag, $arrVirtual)) {
                // It is one of the virtual roots, and the flag is missing.
                // So the Article has to be removed from this group.
                $em->remove($objArticleCategory);
                $objArticleCategory = false;
                // And if the subgroup happens to contain no more
                // "Article", delete it as well.
                $arrChildren = $objSubGroupCategory->getChildren();
                if (count($arrChildren) == 0)
                    $em->remove($objSubGroupCategory);
                continue;
            }

            // Here, the virtual ShopCategory groups have been processed,
            // the only ones left are the "normal" ShopCategories.
            // Clone one of the Article ShopCategories for each of the
            // new flags set.
            // Already did that?
            if ($flagCloned) continue;

            // Find out what flags have been added.
            foreach ($arrVirtual as $strFlag) {
                // That flag is not present in the new flag set.
                if (!preg_match("/$strFlag/", $strNewFlags)) continue;
                // But it has been before.  The respective branch has
                // been truncated above already.
                if (preg_match("/$strFlag/", $strOldFlags)) continue;

                // That is a new flag for which we have to clone the Article.
                // Get the affected grandparent (group, root ShopCategory)
                $objTargetRootCategory =
                    ShopCategories::getChildNamed($strFlag, 0, false);
                if (!$objTargetRootCategory) continue;
                // Check whether the subgroup exists already
                $objTargetSubGroupCategory =
                    ShopCategories::getChildNamed(
                        $subgroupName, $objTargetRootCategory->getId(), false);
                if (!$objTargetSubGroupCategory) {
                    // Nope, add the subgroup.
                    $objSubGroupCategory->makeClone();
                    $objSubGroupCategory->getParent()->map(
                        function($category) { return $category->getId(); }
                    );
                    $em->persist($objSubGroupCategory);
                    $objTargetSubGroupCategory = $objSubGroupCategory;
                }

                // Check whether the Article ShopCategory exists already
                $objTargetArticleCategory =
                    ShopCategories::getChildNamed(
                        $objArticleCategory->getName(),
                        $objTargetSubGroupCategory->getId(),
                        false
                    );
                if ($objTargetArticleCategory) {
                    // The Article Category already exists.
                } else {
                    // Nope, clone the "Article" ShopCategory and add it to the
                    // subgroup.  Note that the flags have been set already
                    // and don't need to be changed again here.
                    // Also note that the cloning process includes all content
                    // of the Article ShopCategory, but the flags will remain
                    // unchanged. That's why the flags have already been
                    // changed right at the beginning of the process.
                    $objArticleCategory->makeClone(true, true);
                    $objArticleCategory->getParent()->map(
                        function($category) { return $category->getId(); }
                    );
                    $em->persist($objArticleCategory);
                    $objTargetArticleCategory = $objArticleCategory;
                }
            } // foreach $arrVirtual
        } // foreach $arrProduct
        // And we're done!
        $em->flush();
        return true;
    }

    /**
     * Returns an array of Product objects sharing the same Product code.
     * @param   string      $customId   The Product code
     * @return  mixed                   The array of matching Product objects
     *                                  on success, false otherwise.
     * @static
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    static function getByCustomId($customId)
    {
        if (empty($customId)) return false;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $productRepo = $em->getRepository(
            '\Cx\Modules\Shop\Model\Entity\Product'
        );
        $products = $productRepo->findBy(array('code' => $customId));

        return $products;
    }
}