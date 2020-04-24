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
 * DefaultController
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_order
 */

namespace Cx\Modules\Order\Controller;

/**
 *
 * DefaultController for displaying all the orders.
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_order
 */
class DefaultController extends \Cx\Core\Core\Model\Entity\Controller {

    /**
     * Em instance
     * @var \Doctrine\ORM\EntityManager em
     */
    protected $em;

    /**
     * Sigma template instance
     * @var Cx\Core\Html\Sigma  $template
     */
    protected $template;

    /**
     * OrderRepository instance
     * @var \Cx\Modules\Order\Model\Repository\OrderRepository $orderRepository
     */
    protected $orderRepository;

    /**
     * module name
     * @var string $moduleName
     */
    public $moduleName = 'Order';

    /**
     * module name for language placeholder
     * @var string $moduleNameLang
     */
    public $moduleNameLang = 'ORDER';

    /**
     * Controller for the Backend Orders views
     *
     * @param \Cx\Core\Core\Model\Entity\SystemComponentController $systemComponentController the system component controller object
     * @param \Cx\Core\Core\Controller\Cx                          $cx                        the cx object
     * @param \Cx\Core\Html\Sigma                                  $template                  the template object
     */
    public function __construct(\Cx\Core\Core\Model\Entity\SystemComponentController $systemComponentController, \Cx\Core\Core\Controller\Cx $cx) {
        parent::__construct($systemComponentController, $cx);

        $this->em                = $this->cx->getDb()->getEntityManager();
        $this->orderRepository   = $this->em->getRepository('Cx\Modules\Order\Model\Entity\Order');
    }

    /**
     * Use this to parse your backend page
     *
     * @param \Cx\Core\Html\Sigma $template
     */
    public function parsePage(\Cx\Core\Html\Sigma $template) {
        $this->template = $template;

        $this->showOrders();
    }

    public function showOrders()
    {
        global $_ARRAYLANG;

        $term          = isset($_GET['filter-term']) ? contrexx_input2raw($_GET['filter-term']) : '';
        $filterUserId  = isset($_GET['filter-user-id']) ? contrexx_input2raw($_GET['filter-user-id']) : 0;
        $objFilterUser = null;

        if (!empty($term) || !empty($filterUserId)) {
            if ($filterUserId) {
                $objFilterUser = \FWUser::getFWUserObject()->objUser->getUser($filterUserId);
            }
            $orders = $this->orderRepository->findOrdersBySearchTerm($term, $objFilterUser);
        } else {
            $orders = $this->orderRepository->getAllByDesc();
        }

        //User Live Search implementation
        \JS::activate('cx');
        \FWUser::getUserLiveSearch(array(
            'minLength' => 3,
            'canCancel' => true,
            'canClear'  => true
        ));

        $orders = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($orders);
        // setDataType is used to make the ViewGenerator load the proper options if $orders is empty
        $orders->setDataType('Cx\Modules\Order\Model\Entity\Order');
        $options = $this->getController('Backend')->getAllViewGeneratorOptions();
        $view = new \Cx\Core\Html\Controller\ViewGenerator($orders, $options);

        if ((isset($_GET['editid']) && !empty($_GET['editid'])) || (isset($_GET['add']) && !empty($_GET['add']))) {
            $this->template->hideBlock("order_filter");
        } else {
            \FWUser::getUserLiveSearch(array(
                'minLength' => 1,
                'canCancel' => true,
                'canClear'  => true
            ));

            $this->template->setVariable(array(
                'TXT_MODULE_ORDER_SEARCH'       => $_ARRAYLANG['TXT_MODULE_ORDER_SEARCH'],
                'TXT_MODULE_ORDER_FILTER'       => $_ARRAYLANG['TXT_MODULE_ORDER_FILTER'],
                'TXT_MODULE_ORDER_SEARCH_TERM'  => $_ARRAYLANG['TXT_MODULE_ORDER_SEARCH_TERM'],
                'ORDER_SEARCH_VALUE'            => isset($_GET['filter-term']) ? contrexx_input2xhtml($_GET['filter-term']) : '',
                'ORDER_USER_ID'                 => contrexx_raw2xhtml($filterUserId),
                'ORDER_USER_NAME'               => $objFilterUser ? contrexx_raw2xhtml(\FWUser::getParsedUserTitle($objFilterUser)) : '',
            ));
        }

        $this->template->setVariable('ORDERS_CONTENT', $view->render());
    }
}
