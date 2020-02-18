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
 * DiscountCouponController to handle discount coupons
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
namespace Cx\Modules\Shop\Controller;

/**
 * DiscountCouponController to handle discount coupons
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class DiscountCouponController extends \Cx\Core\Core\Model\Entity\Controller
{
    /**
     * Get ViewGenerator options for DiscountCoupon entity
     *
     * @param $options array predefined ViewGenerator options
     *
     * @return array includes ViewGenerator options for DiscountCoupon entity
     * @throws \Doctrine\ORM\ORMException handle orm interaction fails
     */
    public function getViewGeneratorOptions($options)
    {
        global $_ARRAYLANG;

        $this->setJavaScriptVariables();
        \JS::registerJS(
            $this->cx->getModuleFolderName() . '/Shop/View/Script/DiscountCoupon.js'
        );

        $options['order']['overview'] = array(
            'code',
            'startTime',
            'endTime',
            'minimumAmount',
            'discountRate',
            'discountAmount',
            'uses',
            'global',
            'customer',
            'product',
            'payment',
            'link'
        );

        $options['order']['form'] = array(
            'customer',
            'code',
            'startTime',
            'endTime',
            'minimumAmount',
            'type',
            'discountRate',
            'discountAmount',
            'uses',
            'global',
            'product',
            'payment',
        );

        $defaultCurrency = $this->cx->getDb()->getEntityManager()->getRepository(
            '\Cx\Modules\Shop\Model\Entity\Currency'
        )->getDefaultCurrency();

        $options['fields'] = array(
            'id' => array(
                'showOverview' => false,
            ),
            'customerId' => array(
                'showOverview' => false,
                'showDetail' => false,
            ),
            'paymentId' => array(
                'showOverview' => false,
                'showDetail' => false,
            ),
            'productId' => array(
                'showOverview' => false,
                'showDetail' => false,
            ),
            'code' => array(
                'header' => $_ARRAYLANG['TXT_SHOP_DISCOUNT_COUPON_CODE']
            ),
            'customer' => array(
                'type' => 'hidden',
                'header' => $_ARRAYLANG['TXT_SHOP_CUSTOMER'],
                'table' => array(
                    'parse' => array(
                        'adapter' => 'DiscountCoupon',
                        'method' => 'getUsernameOrAny'
                    )
                ),
            ),
            'startTime' => array(
                'type' => 'date',
                'valueCallback' => array(
                    'adapter' => 'DiscountCoupon',
                    'method' => 'formatDate'
                ),
                'table' => array(
                    'parse' => array(
                        'adapter' => 'DiscountCoupon',
                        'method' => 'getDashIfEmpty'
                    )
                ),
                'storecallback' => array(
                    'adapter' => 'DiscountCoupon',
                    'method' => 'convertDateToInt'
                )
            ),
            'payment' => array(
                'table' => array(
                    'parse' => array(
                        'adapter' => 'DiscountCoupon',
                        'method' => 'getNameOrAny'
                    ),
                ),
                'formfield' => array(
                    'adapter' => 'DiscountCoupon',
                    'method' => 'getPaymentDropdown'
                ),
            ),
            'product' => array(
                'table' => array(
                    'parse' => array(
                        'adapter' => 'DiscountCoupon',
                        'method' => 'getNameOrAny'
                    ),
                ),
                'formfield' => array(
                    'adapter' => 'DiscountCoupon',
                    'method' => 'getProductDropdown'
                ),
            ),
            'endTime' => array(
                'type' => 'date',
                'table' => array(
                    'parse' => array(
                        'adapter' => 'DiscountCoupon',
                        'method' => 'getEndTime'
                    )
                ),
                'formfield' => array(
                    'adapter' => 'DiscountCoupon',
                    'method' => 'addUnlimitedEndTimeCheckbox'
                ),
                'valueCallback' => array(
                    'adapter' => 'DiscountCoupon',
                    'method' => 'formatDate'
                ),
                'storecallback' => array(
                    'adapter' => 'DiscountCoupon',
                    'method' => 'convertDateToInt'
                ),
            ),
            'minimumAmount' => array(
                'attributes' => array(
                    'style' => 'text-align: right'
                ),
                'header' => sprintf(
                    $_ARRAYLANG['minimumAmount'],
                    $defaultCurrency->getCode()
                ),
                'table' => array(
                    'parse' => array(
                        'adapter' => 'DiscountCoupon',
                        'method' => 'getNullPriceIfEmpty'
                    )
                ),
            ),
            'discountRate' => array(
                'attributes' => array(
                    'style' => 'text-align: right'
                ),
                'storecallback' => array(
                    'adapter' => 'DiscountCoupon',
                    'method' => 'setDiscountRate'
                ),
                'table' => array(
                    'parse' => array(
                        'adapter' => 'DiscountCoupon',
                        'method' => 'getDashIfEmpty'
                    ),
                ),
            ),
            'discountAmount' => array(
                'attributes' => array(
                    'style' => 'text-align: right;'
                ),
                'header' => sprintf(
                    $_ARRAYLANG['discountAmount'],
                    $defaultCurrency->getCode()
                ),
                'storecallback' => array(
                    'adapter' => 'DiscountCoupon',
                    'method' => 'setDiscountAmount'
                ),
                'table' => array(
                    'parse' => array(
                        'adapter' => 'DiscountCoupon',
                        'method' => 'getNullPriceIfEmpty'
                    )
                ),
                'tooltip' => '<strong>' .
                    $_ARRAYLANG['TXT_SHOP_DISCOUNTS_SALE_NOTE_TITLE'] .
                    '</strong><br/>' . $_ARRAYLANG['TXT_SHOP_DISCOUNTS_SALE_NOTE_TEXT'] .
                    '<br/><br/><strong>' .
                    $_ARRAYLANG['TXT_SHOP_DISCOUNTS_MULTIPLE_VAT_NOTE_TITLE'] .
                    '</strong> <br/>' .
                    $_ARRAYLANG['TXT_SHOP_DISCOUNTS_MULTIPLE_VAT_NOTE_TEXT']
            ),
            'uses' => array(
                'formfield' => array(
                    'adapter' => 'DiscountCoupon',
                    'method' => 'addUnlimitedUsesCheckbox',
                ),
                'storecallback' => array(
                    'adapter' => 'DiscountCoupon',
                    'method' => 'setUnlimitedUsesIfEmpty',
                ),
                'table' => array(
                    'parse' => array(
                        'adapter' => 'DiscountCoupon',
                        'method' => 'getUseStatus',
                    ),
                ),
            ),
            'global' => array(
                'formfield' => array(
                    'adapter' => 'DiscountCoupon',
                    'method' => 'getGlobalAndUserCheckboxes'
                ),
                'storecallback' => array(
                    'adapter' => 'DiscountCoupon',
                    'method' => 'checkIfCouponIsGlobal'
                ),
                'table' => array(
                    'parse' => array(
                        'adapter' => 'DiscountCoupon',
                        'method' => 'getInfoIfIsGlobalOrCustomer'
                    ),
                ),
            ),
            'type' => array(
                'custom' => true,
                'header' => $_ARRAYLANG['TXT_SHOP_DISCOUNT_COUPON_TYPE'],
                'showOverview' => false,
                'formfield' => array(
                    'adapter' => 'DiscountCoupon',
                    'method' => 'getTypeCheckboxes'
                ),
            ),
            'link' => array(
                'custom' => true,
                'showDetail' => false,
                'table' => array(
                    'parse' => array(
                        'adapter' => 'DiscountCoupon',
                        'method' => 'getCouponLink',
                    ),
                    'attributes' => array(
                        'class' => 'shop-coupon-link',
                    ),
                ),
            ),
        );

        return $options;
    }

    /**
     * Sets all variables used in the JavaScript code.
     */
    protected function setJavaScriptVariables()
    {
        global $_ARRAYLANG;

        $cxJs = \ContrexxJavascript::getInstance();
        $scope = 'Shop';
        $cxJs->setVariable(
            'SHOP_GET_NEW_DISCOUNT_COUPON',
            \Cx\Modules\Shop\Controller\DiscountCouponController::getNewCode(),
            $scope
        );
        $cxJs->setVariable(
            'TXT_SHOP_GENERATE_NEW_CODE',
            $_ARRAYLANG['TXT_SHOP_DISCOUNT_COUPON_CODE_NEW'],
            $scope
        );
    }

    /**
     * Returns a unique Coupon code with eight characters
     *
     * @see User::make_password()
     *
     * @return string The Coupon code
     */
    static function getNewCode()
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $couponRepo = $cx->getDb()->getEntityManager()->getRepository(
            'Cx\Modules\Shop\Model\Entity\DiscountCoupon'
        );
        $code = null;
        while (true) {
            $code = \User::make_password(8, false);
            $coupon = $couponRepo->findOneBy(array('code' => $code));
            if (empty($coupon)) break;
        }
        return $code;
    }

}