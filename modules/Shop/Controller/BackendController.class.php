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
 * Specific BackendController for this Component. Use this to easily create a
 * backend view
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_shop
 */

namespace Cx\Modules\Shop\Controller;


/**
 * Specific BackendController for this Component. Use this to easily create a
 * backend view
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_shop
 */
class BackendController extends \Cx\Core\Core\Model\Entity\SystemComponentBackendController
{
    // Order Id to edit
    protected $orderId = 0;

    /**
     * This is called by the ComponentController and does all the repeating work
     *
     * This loads the ShopManager and call getPage() from it. Only temporary,
     * since the entities are migrated individually
     *
     * @global array $_CORELANG Language data
     * @global array $subMenuTitle Submenu title
     * @global array $intAccessIdOffset access id offset
     * @global array $objTemplate object template
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Resolved page
     */
    public function getPage(
        \Cx\Core\ContentManager\Model\Entity\Page $page
    ) {
        global $_CORELANG, $subMenuTitle, $intAccessIdOffset, $objTemplate;

        $splitAct = explode('/', $_GET['act']);
        $act = $splitAct[0];
        $tpl = $splitAct[1];

        switch($act)  {
            case 'Category':
            case 'categories':
            case 'category_edit':
            case 'products':
            case 'activate_products':
            case 'deactivate_products':
            case 'delProduct':
            case 'deleteProduct':
            case 'Customer':
            case 'delcustomer':
            case 'customer_activate':
            case 'customer_deactivate':
            case 'customers':
            case 'customerdetails':
            case 'neweditcustomer':
            case 'Statistic':
            case 'statistics':
            case 'Import':
            case 'import':
            case 'Setting':
            case 'settings':
            case 'Vat':
            case 'Shipper':
            case 'Relcountry':
            case 'Zone':
            case 'Mail':
            case 'mailtemplate_overview':
            case 'mailtemplate_edit':
                $mappedNavItems = array(
                    'Category' => 'categories',
                    'Product' => 'products',
                    'Manage' => 'manage',
                    'Attribute' => 'attributes',
                    'Customer' => 'customers',
                    'RelDiscountGroup' => 'discounts',
                    'Statistic' => 'statistics',
                    'Import' => 'import',
                    'Setting' => 'settings',
                    'Vat' => 'vat',
                    'Shipper' => 'shipment',
                    'RelCountry' => 'countries',
                    'Zone' => 'zones',
                    'Mail' => 'mail',
                );
                $mappedCmdItems = array(
                    'categories' => 'Category',
                    'category_edit' => 'Category',
                    'products' => 'Product',
                    'activate_products' => 'Product',
                    'deactivate_products' => 'Product',
                    'delProduct' => 'Product',
                    'deleteProduct' => 'Product',
                    'manage' => 'Manage',
                    'delcustomer' => 'Customer',
                    'customer_activate' => 'Customer',
                    'customer_deactivate' => 'Customer',
                    'customers' => 'Customer',
                    'customerdetails' => 'Customer',
                    'neweditcustomer' => 'Customer',
                    'statistics' => 'Statistic',
                    'import' => 'Import',
                    'settings' => 'Setting',
                    'mailtemplate_overview' => 'Mail',
                );

                // Set act and tpl for cmd to build the navigation with the
                // BackendController method
                $cmdAct = !empty($act) ? $act : $_GET['act'];
                $cmdTpl = !empty($tpl) ? $tpl : $_GET['tpl'];

                if (!empty($mappedCmdItems[$cmdTpl])) {
                    $cmdTpl = $mappedCmdItems[$cmdTpl];
                }
                if (!empty($mappedCmdItems[$cmdAct])) {
                    $cmdAct = $mappedCmdItems[$cmdAct];
                }
                // Special case, because mailtemplate_edit ist defined in the
                // $_GET['act'] var and not as $_GET['tpl']
                if ($act === 'mailtemplate_edit') {
                    $cmdAct = 'Setting';
                    $cmdTpl = 'Mail';
                }
                $cmd[0] = $cmdAct;
                $cmd[1] = $cmdTpl;

                if (!empty($this->getCommands()[$act])
                    && in_array($tpl, $this->getCommands()[$act]['children'])
                ) {
                    if (!empty($mappedNavItems[$tpl])) {
                        $_REQUEST['tpl'] = $mappedNavItems[$tpl];
                        $_GET['tpl'] = $mappedNavItems[$tpl];
                    } else {
                        break;
                    }
                }
                if (!empty($mappedNavItems[$act])) {
                    $_GET['act'] = $mappedNavItems[$act];
                }

                $this->cx->getTemplate()->addBlockfile(
                    'CONTENT_OUTPUT',
                    'content_master',
                    'LegacyContentMaster.html'
                );
                $objTemplate = $this->cx->getTemplate();

                \Permission::checkAccess($intAccessIdOffset+13, 'static');
                $subMenuTitle = $_CORELANG['TXT_SHOP_ADMINISTRATION'];
                $objShopManager = new ShopManager();
                // Load Javascript File to move the HTML elements to the correct
                // positions. Because the placeholder CONTENT_NAVIGATION is no
                // longer in the same position in the ViewGenerator.
                \JS::registerJS('modules/Shop/View/Script/Fix.js');
                $navigation = $this->parseNavigation($cmd);
                $objShopManager->getPage($navigation->get());
                return;
        }
        if ($tpl) {
            $_GET['act'] = $tpl;
        }

        parent::getPage($page);
    }

    /**
     * Returns a list of available commands (?act=XY)
     * @return array List of acts
     */
    public function getCommands()
    {
        return array(
            'Order',
            'Category' => array(
                'children' => array(
                    'Pricelist'
                ),
            ),
            'Product' => array(
                'children' => array(
                    'Manage',
                    'Attribute',
                    'DiscountgroupCountName',
                    'ArticleGroup'
                ),
            ),
            'Manufacturer' => array(
                'translatable' => true
            ),
            'Customer' => array(
                'children' => array(
                    'RelDiscountGroup',
                    'CustomerGroup'
                )
            ),
            'Statistic',
            'Import',
            'Setting' => array(
                'children' => array(
                    'Vat',
                    'Currency',
                    'Payment',
                    'Shipper',
                    'RelCountry',
                    'Zone',
                    'Mail',
                    'DiscountCoupon',
                    'PaymentProcessor'
                ),
            ),
        );
    }

    /**
     * Return true here if you want the first tab to be an entity view
     * @return boolean True if overview should be shown, false otherwise
     */
    protected function showOverviewPage()
    {
        return false;
    }

    /**
     * This function returns the ViewGeneration options for a given entityClass
     *
     * @access protected
     * @global $_ARRAYLANG
     * @param $entityClassName contains the FQCN from entity
     * @param $dataSetIdentifier if $entityClassName is DataSet, this is used
     *                           for better partition
     * @return array with options
     */
    protected function getViewGeneratorOptions($entityClassName, $dataSetIdentifier = '')
    {
        global $_ARRAYLANG;

        $options = parent::getViewGeneratorOptions(
            $entityClassName,
            $dataSetIdentifier
        );

        switch ($entityClassName) {
            case 'Cx\Modules\Shop\Model\Entity\Order':
                $options = $this->getSystemComponentController()->getController(
                    'Order'
                )->getViewGeneratorOptions($options);

                break;
            case 'Cx\Modules\Shop\Model\Entity\Manufacturer':
                $options = $this->getSystemComponentController()->getController(
                    'Manufacturer'
                )->getViewGeneratorOptions($options);
                if ($dataSetIdentifier != $entityClassName) {
                    break;
                }
                $options = $this->normalDelete(
                    $_ARRAYLANG['TXT_SHOP_CONFIRM_DELETE_MANUFACTURER'],
                    $options
                );
                break;
            case 'Cx\Modules\Shop\Model\Entity\Category':
                $options = $this->getSystemComponentController()->getController(
                    'Category'
                )->getViewGeneratorOptions($options);
                if ($dataSetIdentifier != $entityClassName) {
                    break;
                }
                // Delete event
                $options = $this->normalDelete(
                    $_ARRAYLANG['TXT_CONFIRM_DELETE_SHOP_CATEGORIES'],
                    $options
                );
                break;
            case 'Cx\Modules\Shop\Model\Entity\Pricelist':
                $options = $this->getSystemComponentController()->getController(
                    'Pricelist'
                )->getViewGeneratorOptions($options);
                break;
            case 'Cx\Modules\Shop\Model\Entity\Currency':
                $options = $this->getSystemComponentController()->getController(
                    'Currency'
                )->getViewGeneratorOptions($options);
                break;
            case 'Cx\Modules\Shop\Model\Entity\ArticleGroup':
                $options['functions']['editable'] = true;
                $options['functions']['edit'] = false;
                $options['functions']['sorting'] = false;
                $options['fields'] = array(
                    'name' => array(
                        'editable' => true,
                    ),
                    'relDiscountGroups' => array(
                        'showOverview' => false,
                        'showDetail' => false,
                    ),
                    'products' => array(
                        'showOverview' => false,
                        'showDetail' => false,
                    ),
                );
                break;
            case 'Cx\Modules\Shop\Model\Entity\CustomerGroup':
                $options['functions']['editable'] = true;
                $options['functions']['edit'] = false;
                $options['functions']['sorting'] = false;
                $options['fields'] = array(
                    'id' => array(
                        'showOverview' => false,
                    ),
                    'name' => array(
                        'editable' => true,
                    ),
                    'relDiscountGroups' => array(
                        'showOverview' => false,
                        'showDetail' => false,
                    ),
                );
                break;
            case 'Cx\Modules\Shop\Model\Entity\DiscountgroupCountName':
                $options = $this->getSystemComponentController()->getController(
                    'DiscountgroupCountName'
                )->getViewGeneratorOptions($options);
                break;
            case 'Cx\Modules\Shop\Model\Entity\Payment':
                $options = $this->getSystemComponentController()->getController(
                    'Payment'
                )->getViewGeneratorOptions($options);
                break;
            case 'Cx\Modules\Shop\Model\Entity\PaymentProcessor':
                $options = $this->getSystemComponentController()->getController(
                    'PaymentProcessor'
                )->getViewGeneratorOptions($options);
                break;
            case 'Cx\Modules\Shop\Model\Entity\Product':
                $options = $this->getSystemComponentController()->getController(
                    'Product'
                )->getViewGeneratorOptions($options);
                $options = $this->normalDelete(
                    $_ARRAYLANG['TXT_CONFIRM_DELETE_CATEGORY'],
                    $options
                );
                break;
            case 'Cx\Modules\Shop\Model\Entity\DiscountCoupon':
                $options = $this->getSystemComponentController()->getController(
                    'DiscountCoupon'
                )->getViewGeneratorOptions($options);
                break;
        }
        return $options;
    }

    /**
     * Set JavaScript variables for multi action delete.
     *
     * @param $message string message to display before delete
     * @param $options array  ViewGenerator options
     * @return array updated array with ViewGenerator options
     */
    protected function normalDelete($message, $options)
    {
        global $_ARRAYLANG;

        $options['multiActions']['delete'] = array(
            'title' => $_ARRAYLANG['TXT_DELETE'],
            'jsEvent' => 'delete:shopDelete'
        );

        // Delete Event
        $scope = 'shopDelete';
        \ContrexxJavascript::getInstance()->setVariable(
            'CSRF_PARAM',
            \Cx\Core\Csrf\Controller\Csrf::code(),
            $scope
        );
        \ContrexxJavascript::getInstance()->setVariable(
            'TXT_CONFIRM_DELETE',
            $message,
            $scope
        );
        \ContrexxJavascript::getInstance()->setVariable(
            'TXT_ACTION_IS_IRREVERSIBLE',
            $_ARRAYLANG['TXT_ACTION_IS_IRREVERSIBLE'],
            $scope
        );

        return $options;
    }
}
