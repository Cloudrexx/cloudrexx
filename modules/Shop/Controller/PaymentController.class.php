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
 * PaymentController to handle payments
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
namespace Cx\Modules\Shop\Controller;

/**
 * PaymentController to handle payments
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class PaymentController extends \Cx\Core\Core\Model\Entity\Controller
{
    /**
     * Array of available payment service data
     * @var     array
     * @access  private
     * @static
     */
    private static $arrPayments = null;

    /**
     * Get ViewGenerator options for Payment entity
     *
     * @global array $_ARRAYLANG containing the language variables
     *
     * @param $options array predefined ViewGenerator options
     *
     * @return array includes ViewGenerator options for Payment entity
     */
    public function getViewGeneratorOptions($options)
    {
        global $_ARRAYLANG;

        $options['functions']['editable'] = true;
        $options['functions']['edit'] = false;
        $options['functions']['sorting'] = false;
        $options['functions']['sortBy'] = array(
            'field' => array('ord' => SORT_ASC)
        );

        $options['functions']['status'] = array(
            'field' => 'active'
        );

        $options['order'] = array(
            'overview' => array(
                'name',
                'fee',
                'freeFrom',
                'paymentProcessor',
                'zones',
                'active'
            ),
            'form' => array(
                'name',
                'fee',
                'freeFrom',
                'paymentProcessor',
                'zones',
                'active'
            )
        );

        $options['fields'] = array(
            'id' => array(
                'showOverview' => false,
            ),
            'processorId' => array(
                'showOverview' => false,
                'showDetail' => false,
            ),
            'discountCoupons' => array(
                'showOverview' => false,
                'showDetail' => false,
            ),
            'orders' => array(
                'showOverview' => false,
                'showDetail' => false,
            ),
            'ord' => array(
                'showOverview' => false,
                'showDetail' => false,
            ),
            'name' => array(
                'header' => $_ARRAYLANG['payment'],
                'editable' => true,
            ),
            'fee' => array(
                'editable' => true,
            ),
            'freeFrom' => array(
                'editable' => true,
            ),
            'paymentProcessor' => array(
                'editable' => true,
            ),
            'zones' => array(
                'editable' => true,
                'showDetail' => false,
                'showOverview' => false,
            ),
        );

        return $options;
    }

    /**
     * Set up the payment array
     * @author  Reto Kohli <reto.kohli@comvation.com>
     * @since   2.1.0
     */
    static function init()
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $repo = $cx->getDb()->getEntityManager()->getRepository(
            'Cx\Modules\Shop\Model\Entity\Payment'
        );

        $payments = $repo->findBy(array(), array('ord' => 'ASC'));
        foreach ($payments as $payment) {
            self::$arrPayments[$payment->getId()] = array(
                'id' => $payment->getId(),
                'processor_id' => $payment->getProcessorId(),
                'name' => $payment->getName(),
                'fee' => $payment->getFee(),
                'free_from' => $payment->getFreeFrom(),
                'ord' => $payment->getOrd(),
                'active' => $payment->getActive(),
            );
        }
        return true;
    }

    /**
     * Return HTML code for the payment dropdown menu
     *
     * See {@see getPaymentMenuoptions()} for details.
     *
     * @global  array   $_ARRAYLANG     Language array
     *
     * @param   integer $selectedId     Optional preselected payment ID
     * @param   string  $onchange       Optional onchange function
     * @param   integer $countryId      Country ID
     *
     * @return  string                  HTML code for the dropdown menu
     * @throws \Doctrine\ORM\ORMException handle orm interaction fails
     */
    static function getPaymentMenu($selectedId=0, $onchange='', $countryId=0)
    {
        return \Html::getSelectCustom(
            'paymentId',
            self::getPaymentMenuoptions($selectedId, $countryId),
            FALSE, $onchange
        );
    }

    /**
     * Return HTML code for the payment dropdown menu options
     *
     * If no valid payment is selected, an additional option representing
     * "please choose" is prepended.
     *
     * @global  array   $_ARRAYLANG     Language array
     *
     * @param   integer $selectedId     Optional preselected payment ID
     * @param   integer $countryId      Country ID
     *
     * @return  string                  HTML code for the dropdown menu options
     * @throws \Doctrine\ORM\ORMException handle orm interaction fails
     */
    static function getPaymentMenuoptions($selectedId=0, $countryId=0)
    {
        global $_ARRAYLANG;

        $paymentMethods = self::getPaymentMethods($countryId);
        if (empty($paymentMethods[$selectedId]) && count($paymentMethods) > 1) {
            $paymentMethods[0] = $_ARRAYLANG['TXT_SHOP_PLEASE_SELECT'];
        }
        return \Html::getOptions($paymentMethods, $selectedId);
    }

    /**
     * Get the payment methods based on the country id
     *
     * @param integer $countryId Country ID
     *
     * @return array array of payment methods
     * @throws \Doctrine\ORM\ORMException handle orm interaction fails
     */
    static function getPaymentMethods($countryId = 0)
    {
        if (is_null(self::$arrPayments)) {
            self::init();
        }
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $repo = $cx->getDb()->getEntityManager()->getRepository(
            'Cx\Modules\Shop\Model\Entity\Payment'
        );
        // Get Payment IDs available in the selected country, if any, or all.
        $arrPaymentIds = ($countryId
            ? $repo->getCountriesRelatedPaymentIdArray(
                $countryId,
                \Cx\Modules\Shop\Controller\CurrencyController::
                    getCurrencyArray()
            )
            : array_keys(self::$arrPayments));

        if (empty($arrPaymentIds)) {
            return array();
        }

        $paymentMethods = array();
        foreach ($arrPaymentIds as $id) {
            $paymentMethods[$id] = self::$arrPayments[$id]['name'];
        }
        return $paymentMethods;
    }

    /**
     * Returns the array of available Payment names
     *
     * The array is indexed by the Payment IDs.
     * @see     Payment::init()
     * @return  array           The array of Payment names
     * @author  Reto Kohli <reto.kohli@comvation.com>
     * @since   3.0.0
     */
    static function getNameArray()
    {
        if (is_null(self::$arrPayments)) self::init();
        $arrPaymentName = array();
        foreach (self::$arrPayments as $payment_id => $arrPayment) {
            $arrPaymentName[$payment_id] = $arrPayment['name'];
        }
        return $arrPaymentName;
    }

    /**
     * Returns the array of available Payment service data
     * @see     Payment::init()
     * @return  array
     * @author  Reto Kohli <reto.kohli@comvation.com>
     * @since   2.1.0
     */
    static function getArray()
    {
        if (empty(self::$arrPayments)) self::init();
        return self::$arrPayments;
    }

    /**
     * Clear the Payments stored in the class
     *
     * Call this after updating the database.  The Payments will be
     * reinitialized on demand.
     */
    static function reset()
    {
        self::$arrPayments = null;
    }

    /**
     * Returns the ID of the payment processor for the given payment ID
     * @static
     * @param   integer   $paymentId    The payment ID
     * @return  integer                 The payment processor ID on success,
     *                                  false otherwise
     * @global  ADONewConnection  $objDatabase    Database connection object
     */
    static function getPaymentProcessorId($paymentId)
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $payment = $cx->getDb()->getEntityManager()->getRepository(
            'Cx\Modules\Shop\Model\Entity\Payment'
        )->find($paymentId);

        if (!empty($payment)) {
            return $payment->getPaymentProcessor()->getId();
        }
        return false;
    }

    /**
     * Returns the named property for the given Payment service
     * @param   integer   $payment_id       The Payment service ID
     * @param   string    $property_name    The property name
     * @return  string                      The property value
     * @author  Reto Kohli <reto.kohli@comvation.com>
     * @since   2.1.0
     */
    static function getProperty($payment_id, $property_name)
    {
        if (is_null(self::$arrPayments)) self::init();
        return
            (isset(self::$arrPayments[$payment_id])
            && isset(self::$arrPayments[$payment_id][$property_name])
                ? self::$arrPayments[$payment_id][$property_name]
                : false
            );
    }
}