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
 * Specific BackendController for this Component. Use this to easily create a backend view
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  modules_order
 */

namespace Cx\Modules\Order\Controller;

/**
 * Specific BackendController for this Component. Use this to easily create a backend view
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  modules_order
 */
class BackendController extends \Cx\Core\Core\Model\Entity\SystemComponentBackendController {

    /**
     * Template object
     */
    protected $template;


    /**
     * Returns a list of available commands (?act=XY)
     * @return array List of acts
     */
    public function getCommands() {
        $commands = array();

        if (\Permission::checkAccess(ComponentController::SUBSCRIPTION_ACCESS_ID, 'static', true)) {
            $commands[] = 'subscription';
        }

        if (\Permission::checkAccess(ComponentController::INVOICE_ACCESS_ID, 'static', true)) {
            $commands[] = 'Invoice';
        }

        if (\Permission::checkAccess(ComponentController::PAYMENT_ACCESS_ID, 'static', true)) {
            $commands[] = 'Payment';
        }

        return $commands;
    }

    /**
     * Use this to parse your backend page
     *
     * You will get the template located in /View/Template/{CMD}.html
     * You can access Cx class using $this->cx
     * To show messages, use \Message class
     * @param \Cx\Core\Html\Sigma $template Template for current CMD
     * @param array $cmd CMD separated by slashes
     */
    public function parsePage(\Cx\Core\Html\Sigma $template, array $cmd, &$isSingle = false) {
        // this class inherits from Controller, therefore you can get access to
        // Cx like this:
        $this->cx;
        $this->template = $template;
        $act = $cmd[0];

        //Check whether the page has the permission to access
        $this->checkAccessPermission($act);

        /* If the act is not empty, we are not on the first tab an we can use parsePage() from
           SystemComponentBackendController to create the view.
           If act is empty, we are on first tab where parent::parsePage() will not work, because ViewGenerator does
           not support views on first tab of components.
           We use a own controller for subscriptions because we have an filter there.
         */

        if ( $act != '' && $act != 'subscription') {
            parent::parsePage($this->template, $cmd, $isSingle);
        } else {
            $this->connectToController($act);
        }
        \Message::show();
    }

    /**
     * Trigger a controller according the act param from the url
     *
     * @param   string $act
     */
    public function connectToController($act)
    {

        $act = ucfirst($act);
        if (!empty($act)) {
            $controllerName = __NAMESPACE__.'\\'.$act.'Controller';
            if (!$controllerName && !class_exists($controllerName)) {
                return;
            }
            //  instantiate the view specific controller
            $objController = new $controllerName($this->getSystemComponentController(), $this->cx);
        } else {
            // instantiate the default View Controller
            $objController = new DefaultController($this->getSystemComponentController(), $this->cx);
        }
        $objController->parsePage($this->template, array());
    }

    /**
     * Check the Access Permission
     *
     * @param string $act
     */
    public function checkAccessPermission($act) {

        switch ($act) {
            case 'subscription':
                \Permission::checkAccess(ComponentController::SUBSCRIPTION_ACCESS_ID, 'static');
                break;
            case 'invoice':
                \Permission::checkAccess(ComponentController::INVOICE_ACCESS_ID, 'static');
                break;
            case 'payment':
                \Permission::checkAccess(ComponentController::PAYMENT_ACCESS_ID, 'static');
                break;
            default :
                \Permission::checkAccess(ComponentController::ORDER_ACCESS_ID, 'static');
                break;
        }
    }

    /**
     * This function returns the ViewGeneration options for a given entityClass
     *
     * @access protected
     * @global $_ARRAYLANG
     * @param $entityClassName contains the FQCN from entity
     * @param $dataSetIdentifier if $entityClassName is DataSet, this is used for better partition
     * @return array with options
     */
    protected function getViewGeneratorOptions($entityClassName, $dataSetIdentifier = '') {
        global $_ARRAYLANG;

        $classNameParts = explode('\\', $entityClassName);
        $classIdentifier = end($classNameParts);

        $langVarName = 'TXT_' . strtoupper($this->getType() . '_' . $this->getName() . '_ACT_' . $classIdentifier);
        $header = '';
        if (isset($_ARRAYLANG[$langVarName])) {
            $header = $_ARRAYLANG[$langVarName];
        }
        switch ($entityClassName) {
            case 'Cx\Modules\Order\Model\Entity\Order':
                return array(
                        'header'    => $_ARRAYLANG['TXT_MODULE_ORDER_ACT_DEFAULT'],
                        'functions' => array(
                            'add'       => true,
                            'edit'      => true,
                            'delete'    => true,
                            'sorting'   => true,
                            'paging'    => true,
                            'filtering' => false,
                        ),
                        'fields' => array(
                            'contactId' => array(
                                'header' => 'contactId',
                                'table' => array(
                                    'parse' => function($value) {
                                        global $_ARRAYLANG;
                                        $userId   = \Cx\Modules\Crm\Controller\CrmLibrary::getUserIdByCrmUserId($value);
                                        $userName = \FWUser::getParsedUserTitle($userId);
                                        $crmDetailLink = "<a href='index.php?cmd=Crm&amp;act=customers&amp;tpl=showcustdetail&amp;id={$value}'
                                                    title='{$_ARRAYLANG['TXT_MODULE_ORDER_CRM_CONTACT']}'>
                                                    <img
                                                        src='".\Env::get('cx')->getCodeBaseCoreWebPath()."/Core/View/Media/navigation_level_1_189.png'
                                                        width='16' height='16'
                                                        alt='{$_ARRAYLANG['TXT_MODULE_ORDER_CRM_CONTACT']}'
                                                    />
                                                </a>";

                                        $url = "<a href='index.php?cmd=Access&amp;act=user&amp;tpl=modify&amp;id={$userId}'
                                       title='{$_ARRAYLANG['TXT_MODULE_ORDER_MODIY_USER_ACCOUNT']}'>" .
                                            $userName .
                                            "</a>" .
                                            $crmDetailLink;
                                            return $url;
                                    },
                                ),
                            ),
                            'subscriptions' => array(
                                'header' => 'subscriptions',
                                'table'  => array(
                                    'parse' => function ($subscriptions) {
                                        $result = array();
                                        foreach ($subscriptions as $subscription) {
                                            $productEntity     = $subscription->getProductEntity();
                                            if(!$productEntity) {
                                                continue;
                                            }
                                            $productEntityName = $subscription->getProduct()->getName();
                                            $productEditLink = $productEntity;
                                            if (method_exists($productEntity, 'getEditLink')) {
                                                $productEditLink = $productEntity->getEditLink();
                                            }
                                            $subscriptionEditUrl = '<a href=​index.php?cmd=Order&act=subscription&editid='. $subscription->getId() .'>' . $productEntityName . '</a>';

                                            $result[] = $subscriptionEditUrl . ' (' . $productEditLink . ')';
                                        }

                                        return implode(', ', $result);
                                    }
                                )
                            ),
                        ),
                    );
                break;
            case 'Cx\Modules\Order\Model\Entity\Subscription':
                return array(
                    'header'    => $_ARRAYLANG['TXT_MODULE_ORDER_ACT_SUBSCRIPTION'],
                    'functions' => array(
                        'add'       => true,
                        'edit'      => true,
                        'delete'    => true,
                        'sorting'   => true,
                        'paging'    => true,
                        'filtering' => false,
                    ),
                    'fields' => array(
                        'id' => array(
                            'header' => $_ARRAYLANG['TXT_MODULE_ORDER_SUBSCRIPTION_ID']
                        ),
                        'subscriptionDate' => array(
                            'header' => $_ARRAYLANG['TXT_MODULE_ORDER_SUBSCRIPTION_DATE']
                        ),
                        'expirationDate' => array(
                            'header' => $_ARRAYLANG['TXT_MODULE_ORDER_SUBSCRIPTION_EXPIRATION_DATE']
                        ),
                        'productEntityId' => array(
                            'header' => $_ARRAYLANG['TXT_MODULE_ORDER_SUBSCRIPTION_PRODUCT_ENTITY'],
                            'table' => array(
                                'parse' => function($value, $rowData) {
                                    $subscriptionRepo = \Env::get('em')->getRepository(
                                        'Cx\Modules\Order\Model\Entity\Subscription'
                                    );
                                    $subscription  = $subscriptionRepo->findOneBy(array('id' => $rowData['id']));
                                    $productEntity = $subscription->getProductEntity();
                                    if(!$productEntity) {
                                        return;
                                    }
                                    $productEditLink = $productEntity;
                                    if (method_exists($productEntity, 'getEditLink')) {
                                        $productEditLink = $productEntity->getEditLink();
                                    }

                                    return $productEditLink;
                                }
                            )
                        ),
                        'paymentAmount' => array(
                            'header' => $_ARRAYLANG['TXT_MODULE_ORDER_SUBSCRIPTION_PAYMENT_AMOUNT'],
                            'table' => array(
                                'parse' => function($value, $rowData) {
                                    if (\FWValidator::isEmpty(floatval($value))) {
                                        return null;
                                    }
                                    $subscriptionRepo = \Env::get('em')->getRepository(
                                        'Cx\Modules\Order\Model\Entity\Subscription'
                                    );
                                    $subscription    = $subscriptionRepo->findOneBy(array('id' => $rowData['id']));
                                    $currency = '';
                                    $order = $subscription->getOrder();
                                    if ($order) {
                                        $currency  = !\FWValidator::isEmpty($order->getCurrency()) ? $order->getCurrency() : '';
                                    }
                                    $paymentInterval = $subscription->getRenewalUnit();
                                    return $value . ' ' . $currency . ' / ' . $paymentInterval;
                                }
                            )
                        ),
                        'renewalUnit' => array(
                            'header' => $_ARRAYLANG['TXT_MODULE_ORDER_SUBSCRIPTION_RENEWAL_UNIT'],
                            'table' => array(
                                'parse' => function($value, $rowData) {
                                    if (empty($value)) {
                                        return null;
                                    }
                                    $subscriptionRepo = \Env::get('em')->getRepository(
                                        'Cx\Modules\Order\Model\Entity\Subscription'
                                    );
                                    $subscription    = $subscriptionRepo->findOneBy(array('id' => $rowData['id']));
                                    $renewalDate     = '';
                                    if ($subscription->getRenewalDate()) {
                                        $renewalDate  = $subscription->getRenewalDate();
                                        $quantifier   = $subscription->getRenewalQuantifier();
                                        $renewalDate->modify("-$quantifier $value");
                                        return $renewalDate->format('d.M.Y H:i:s');
                                    }
                                    return $renewalDate;
                                }
                            )
                        ),
                        'renewalQuantifier' => array(
                            'showOverview' => false
                        ),
                        'renewalDate' => array(
                            'header' => $_ARRAYLANG['TXT_MODULE_ORDER_SUBSCRIPTION_RENEWAL_DATE']
                        ),
                        'description' => array(
                            'header' => $_ARRAYLANG['TXT_MODULE_ORDER_SUBSCRIPTION_DESCRIPTION']
                        ),
                        'state' => array(
                            'header' => $_ARRAYLANG['TXT_MODULE_ORDER_SUBSCRIPTION_STATE']
                        ),
                        'terminationDate' => array(
                            'header' => $_ARRAYLANG['TXT_MODULE_ORDER_SUBSCRIPTION_TERMI_DATE']
                        ),
                        'note' => array(
                            'header' => $_ARRAYLANG['TXT_MODULE_ORDER_SUBSCRIPTION_NOTE']
                        ),
                        'product'  => array(
                            'header' => $_ARRAYLANG['TXT_MODULE_ORDER_SUBSCRIPTION_PRODUCT'],
                            'table' => array(
                                'parse' => function($value, $rowData) {
                                    $subscriptionRepo = \Env::get('em')->getRepository(
                                        'Cx\Modules\Order\Model\Entity\Subscription'
                                    );
                                    $subscription  = $subscriptionRepo->findOneBy(array('id' => $rowData['id']));
                                    $product       = $subscription->getProduct();
                                    if (!$product) {
                                        return;
                                    }
                                    return $product->getName();
                                }
                            )
                        ),
                        'paymentState' => array(
                            'showOverview' => false
                        ),
                        'externalSubscriptionId' => array(
                            'showOverview' => false
                        ),
                        'order' => array(
                            'showOverview' => false
                        ),

                    ),
                );
                break;
            default:
                return array(
                    'header' => $header,
                    'functions' => array(
                        'add'       => true,
                        'edit'      => true,
                        'delete'    => true,
                        'sorting'   => true,
                        'paging'    => true,
                        'filtering' => false,
                    ),
                );
        }
    }
}
