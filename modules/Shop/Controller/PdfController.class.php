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
 * PdfController to create pdfs
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_shop
 */

namespace Cx\Modules\Shop\Controller;

/**
 * PdfController to create pdfs
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_shop
 */
class PdfController extends \Cx\Core\Core\Model\Entity\Controller
{
    /**
     * Creates a PDF document and sends this pricelist to the client
     *
     * Unfortunately, ezpdf does not return anything after printing the
     * document, so there's no way to tell whether it has succeeded.
     * Thus, you should not rely on the return value, except when it is
     * false -- in that case, loading of some data failed.
     *
     * @param int $pricelistId id of pricelist
     * @param int $currencyId  id of currency
     * @throws \Doctrine\ORM\ORMException
     * @return  boolean False on failure, true on supposed success
     */
    public function generatePdfPricelist($pricelistId, $currencyId = 0)
    {
        global $objInit, $_ARRAYLANG;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $repo = $cx->getDb()->getEntityManager()->getRepository(
            '\Cx\Modules\Shop\Model\Entity\Pricelist'
        );

        $pricelist = $repo->find($pricelistId);

        if (empty($pricelist)) {
            return \Message::error(
                $_ARRAYLANG['TXT_SHOP_PRICELIST_ERROR_LOADING']
            );
        }

        $objPdf = new \Cezpdf('A4');
        $objPdf->setEncryption('', '', array('print'));
        $objPdf->selectFont(
            \Cx\Core\Core\Controller\Cx::instanciate()
                ->getCodeBaseLibraryPath()
            . '/ezpdf/fonts/' . $pricelist->getFont()
        );
        $objPdf->ezSetMargins(0, 0, 0, 0); // Reset margins
        $objPdf->setLineStyle(0.5);
        $marginTop = 30;
        $biggerCountTop = $biggerCountBottom = 0;
        $arrHeaderLeft = $arrHeaderRight = $arrFooterLeft = $arrFooterRight =
            array();
        if ($pricelist->getHeaderOn()) { // header should be shown
            $arrHeaderLeft = explode("\n", $pricelist->getHeaderLeft());
            $arrHeaderRight = explode("\n", $pricelist->getHeaderRight());
            $countLeft = count($arrHeaderLeft);
            $countRight = count($arrHeaderRight);
            $biggerCountTop = ($countLeft > $countRight
                ? $countLeft : $countRight);
            $marginTop = ($biggerCountTop * 14)+36;
        }
        // Bottom margin
        $marginBottom = 20;
        $arrFooterRight = array();
        if ($pricelist->getFooterOn()) { // footer should be shown
            // Old, obsolete:
            $pricelist->setFooterLeft(
                str_replace(
                    '<--DATE-->',
                    date(
                        ASCMS_DATE_FORMAT_DATE, time()
                    ),
                    $pricelist->getFooterLeft()
                )
            );
            $pricelist->setFooterRight(
                str_replace(
                    '<--DATE-->',
                    date(
                        ASCMS_DATE_FORMAT_DATE, time()
                    ), $pricelist->getFooterRight()
                )
            );
            // New:
            $pricelist->setFooterLeft(
                str_replace(
                    '[DATE]',
                    date(
                        ASCMS_DATE_FORMAT_DATE, time()
                    ), $pricelist->getFooterLeft()
                )
            );
            $pricelist->setFooterRight(
                str_replace(
                    '[DATE]',
                    date(
                        ASCMS_DATE_FORMAT_DATE, time()
                    ), $pricelist->getFooterRight()
                )
            );
            $arrFooterLeft = explode("\n", $pricelist->getFooterLeft());
            $arrFooterRight = explode("\n", $pricelist->getFooterRight());
            $countLeft = count($arrFooterLeft);
            $countRight = count($arrFooterRight);
            $biggerCountBottom = ($countLeft > $countRight
                ? $countLeft : $countRight);
            $marginBottom = ($biggerCountBottom * 20)+20;
        }
        // Borders
        if ($pricelist->getBorderOn()) {
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
        if ($pricelist->getHeaderOn()) {
            $objPdf->ezSetY(830);
            $headerForAllPages = $objPdf->openObject();
            $objPdf->saveState();
            for ($i = 0; $i < $biggerCountTop; ++$i) {
                $headerArray[$i] = array(
                    'left' => (isset($arrHeaderLeft[$i]) ? $arrHeaderLeft[$i] : ''),
                    'right' => (isset($arrHeaderRight[$i]) ? $arrHeaderRight[$i] : ''),
                );
            }
            $tempY = $objPdf->ezTable(
                $headerArray, '', '', array(
                    'showHeadings' => 0,
                    'fontSize' => $pricelist->getFontSizeHeader(),
                    'shaded' => 0,
                    'width' => 540,
                    'showLines' => 0,
                    'xPos' => 'center',
                    'xOrientation' => 'center',
                    'cols' => array(
                        'right' => array('justification' => 'right')
                    ),
                )
            );
            $tempY -= 5;
            if ($pricelist->getBorderOn()) {
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
        if ($pricelist->getFooterOn()) {
            $footerForAllPages = $objPdf->openObject();
            $objPdf->saveState();
            $tempY = $marginBottom - 5;
            if ($pricelist->getBorderOn()) {
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
                    $pageNumbersY = $tempY-18-(
                        $i*$pricelist->getFontSizeFooter()
                    );
                    $pageNumbersFont = $pricelist->getFontSizeList();
                } else {
                    $objPdf->addText(
                        25, $tempY-18-($i*$pricelist->getFontSizeFooter()),
                        $pricelist->getFontSizeFooter(), $arrFooterLeft[$i]
                    );
                }
                if ($arrFooterRight[$i] == '<--PAGENUMBER-->' // Old, obsolete
                    || $arrFooterRight[$i] == '[PAGENUMBER]') {
                    $pageNumbersX = 595.28-25;
                    $pageNumbersY = $tempY-18-(
                        $i*$pricelist->getFontSizeFooter()
                    );
                    $pageNumbersFont = $pricelist->getFontSizeList();
                } else {
                    // Properly align right
                    $width = $objPdf->getTextWidth(
                        $pricelist->getFontSizeFooter(), $arrFooterRight[$i]
                    );
                    $objPdf->addText(
                        595.28-$width-25, $tempY-18-(
                            $i*$pricelist->getFontSizeFooter()
                        ),
                        $pricelist->getFontSizeFooter(), $arrFooterRight[$i]
                    );
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
                $_ARRAYLANG['TXT_SHOP_PRICELIST_FORMAT_PAGENUMBER'], 1
            );
        }
        // Margins
        $objPdf->ezSetMargins($marginTop, $marginBottom, 30, 30);
        // Product table
        if (isset($startpointY)) {
            $objPdf->ezSetY($startpointY);
        }
        $objInit->backendLangId = $pricelist->getLangId();
        $_ARRAYLANG = $objInit->loadLanguageData('Shop');
        \Cx\Modules\Shop\Controller\CurrencyController::setActiveCurrencyId($currencyId, $pricelist->getLangId());
        $currency_symbol = \Cx\Modules\Shop\Controller\CurrencyController::getActiveCurrencySymbol();
        $category_ids = $repo->getCategoryIdsByPricelist($pricelist);
        if ($pricelist->getAllCategories()) $category_ids = array();
        $count = 1000; // Be sensible!
        // Pattern is "%" because all-empty parameters will result in an
        // empty array!
        $arrProduct = Products::getByShopParams(
            $count, 0, null, $category_ids, null, '%', null, null,
            '`category_product`.`category_id` ASC, `name` ASC', null, false,
            $pricelist->getLangId()
        );

        $arrCategoryName = ShopCategories::getNameArray(
            false, $pricelist->getLangId()
        );
        $arrOutput = array();
        foreach ($arrProduct as $product_id => $objProduct) {
            $categoryIds = explode(',', $objProduct->category_id());
            $arrCategoryNames = array();
            foreach ($categoryIds as $categoryId) {
                $arrCategoryNames[] = $arrCategoryName[$categoryId];
            }

            $arrOutput[$product_id] = array(
                'product_name' => self::decode($objProduct->name()),
                'category_name' => self::decode(implode(', ', $arrCategoryNames)),
                'product_code' => self::decode($objProduct->code()),
                'product_id' => self::decode($objProduct->id()),
                'price' =>
                    ($objProduct->discount_active()
                        ? "S " . \Cx\Modules\Shop\Controller\CurrencyController::formatPrice($objProduct->discountprice())
                        : \Cx\Modules\Shop\Controller\CurrencyController::formatPrice($objProduct->price())) .
                    ' ' . $currency_symbol,
            );
        }
        $objPdf->ezTable(
            $arrOutput,
            array(
                'product_name' => '<b>'.$this->decode(
                    $_ARRAYLANG['TXT_SHOP_PRODUCT_NAME']
                ).'</b>',
                'category_name' => '<b>'.$this->decode(
                    $_ARRAYLANG['TXT_SHOP_CATEGORY_NAME']
                ).'</b>',
                'product_code' => '<b>'.$this->decode(
                    $_ARRAYLANG['TXT_SHOP_PRODUCT_CODE']
                ).'</b>',
                'product_id' => '<b>'.$this->decode(
                    $_ARRAYLANG['TXT_ID']
                ).'</b>',
                'price' => '<b>'.$this->decode(
                    $_ARRAYLANG['TXT_SHOP_PRICE']
                ).'</b>'
            ), '',
            array(
                'showHeadings' => 1,
                'fontSize' => $pricelist->getFontSizeList(),
                'width' => 530,
                'innerLineThickness' => 0.5,
                'outerLineThickness' => 0.5,
                'shaded' => 2,
                'shadeCol' => array(
                    hexdec(substr($pricelist->getRowColor1(), 0, 2))/255,
                    hexdec(substr($pricelist->getRowColor1(), 2, 2))/255,
                    hexdec(substr($pricelist->getRowColor1(), 4, 2))/255,
                ),
                'shadeCol2' => array(
                    hexdec(substr($pricelist->getRowColor2(), 0, 2))/255,
                    hexdec(substr($pricelist->getRowColor2(), 2, 2))/255,
                    hexdec(substr($pricelist->getRowColor2(), 4, 2))/255,
                ),
                // Note: 530 points in total
                'cols' => array(
                    'product_name' => array('width' => 255),
                    'category_name' => array('width' => 130),
                    'product_code' => array('width' => 50),
                    'product_id' => array(
                        'width' => 40, 'justification' => 'right'
                    ),
                    'price' => array('width' => 55, 'justification' => 'right')
                ),
            )
        );
        $objPdf->ezStream();
        // Never reached
        return true;
    }

    /**
     * Returns the string decoded from UTF-8 if necessary
     * @param   string    $string       The string to be decoded
     * @return  string                  The decoded string
     */
    protected function decode($string)
    {
        global $_CONFIG;

        return ($_CONFIG['coreCharacterEncoding'] == 'UTF-8'
            ? utf8_decode($string) : $string);
    }
}