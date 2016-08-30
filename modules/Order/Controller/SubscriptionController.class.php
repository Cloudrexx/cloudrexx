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
 * SubscriptionController
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_order
 */

namespace Cx\Modules\Order\Controller;

/**
 *
 * SubscriptionController for displaying all the subscriptions.
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_order
 */
class SubscriptionController extends \Cx\Core\Core\Model\Entity\Controller {

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
     * @var \Cx\Modules\Order\Model\Repository\SubscriptionRepository $subscriptionRepo
     */
    protected $subscriptionRepo;

    /**
     * Controller for the Backend subscription views
     *
     * @param \Cx\Core\Core\Model\Entity\SystemComponentController $systemComponentController the system component controller object
     * @param \Cx\Core\Core\Controller\Cx                          $cx                        the cx object
     * @param \Cx\Core\Html\Sigma                                  $template                  the template object
     * @param string                                               $submenu                   the submenu name
     */
    public function __construct(\Cx\Core\Core\Model\Entity\SystemComponentController $systemComponentController, \Cx\Core\Core\Controller\Cx $cx) {
        parent::__construct($systemComponentController, $cx);

        $this->em                = $this->cx->getDb()->getEntityManager();
        $this->subscriptionRepo   = $this->em->getRepository('Cx\Modules\Order\Model\Entity\Subscription');

    }

    /**
     * Use this to parse your backend page
     *
     * @param \Cx\Core\Html\Sigma $template
     */
    public function parsePage(\Cx\Core\Html\Sigma $template) {
        $this->template = $template;

        $this->showSubscriptions();
    }

    public function showSubscriptions()
    {
        global $_ARRAYLANG;

        $term          = isset($_GET['term']) ? contrexx_input2raw($_GET['term']) : '';
        $filterProduct = isset($_GET['filter_product'])
                         ? contrexx_input2raw($_GET['filter_product']) : array();
        $filterState   = isset($_GET['filter_state'])
                         ? contrexx_input2raw($_GET['filter_state']) : array();

        if (!empty($term) || !empty($filterProduct) || !empty($filterState)) {
            $filter    = array('term' => $term, 'filterProduct' => $filterProduct, 'filterState' => $filterState);
            $subscriptions = $this->subscriptionRepo->findSubscriptionsBySearchTerm($filter);
        } else {
            $subscriptions = $this->subscriptionRepo->getSubscriptionsByCriteria(null, array('s.id' => 'DESC'));
        }

        $subscriptions = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($subscriptions);
        // setDataType is used to make the ViewGenerator load the proper options if $subscriptions is empty
        $subscriptions->setDataType('Cx\Modules\Order\Model\Entity\Subscription');

        $products = \Env::get('em')->getRepository('Cx\Modules\Pim\Model\Entity\Product')->findAll();
        $this->getSearchFilterDropDown($products, $filterProduct, 'product');

        $subscriptionStates = array(\Cx\Modules\Order\Model\Entity\Subscription::STATE_ACTIVE,
                                    \Cx\Modules\Order\Model\Entity\Subscription::STATE_INACTIVE,
                                    \Cx\Modules\Order\Model\Entity\Subscription::STATE_TERMINATED,
                                    \Cx\Modules\Order\Model\Entity\Subscription::STATE_CANCELLED
                              );
        $this->getSearchFilterDropDown($subscriptionStates, $filterState, 'state');

        $options = $this->getController('Backend')->getAllViewGeneratorOptions();
        $view = new \Cx\Core\Html\Controller\ViewGenerator($subscriptions, $options);

        $this->template->setVariable(array(
            'TXT_ORDER_SUBSCRIPTIONS_FILTER'       => $_ARRAYLANG['TXT_MODULE_ORDER_FILTER'],
            'TXT_ORDER_SUBSCRIPTIONS_SEARCH'       => $_ARRAYLANG['TXT_MODULE_ORDER_SEARCH'],
            'TXT_ORDER_SUBSCRIPTIONS_SEARCH_TERM'  => $_ARRAYLANG['TXT_MODULE_ORDER_SEARCH_TERM'],
            'ORDER_SUBSCRIPTIONS_SEARCH_VALUE'     => contrexx_raw2xhtml($term)
        ));
        if (isset($_GET['editid']) && !empty($_GET['editid']) || isset($_GET['add']) && !empty($_GET['add'])) {
            $this->template->hideBlock("subscription_filter");
        }
        $this->template->setVariable('SUBSCRIPTIONS_CONTENT', $view->render());
    }

    /**
     * Get search filter dropdown
     *
     * @param mixed   $filterDropDownValues
     * @param mixed   $selected
     * @param string  $block
     */
    public function getSearchFilterDropDown($filterDropDownValues, $selected, $block) {

        foreach ($filterDropDownValues as $filterDropDownValue) {
            $filterDropDownName = $filterDropDownValue;
            if (is_object($filterDropDownValue)) {
                $filterDropDownName  = $filterDropDownValue->getName();
                $filterDropDownValue = $filterDropDownValue->getId();
            }

            $selectedVal = in_array($filterDropDownValue, $selected) ? 'selected' : '';
            $this->template->setVariable(array(
                'ORDER_SUBSCRIPTION_'.strtoupper($block).'_NAME'         => contrexx_raw2xhtml($filterDropDownName),
                'ORDER_SUBSCRIPTION_'.strtoupper($block).'_VALUE'        => contrexx_raw2xhtml($filterDropDownValue),
                'ORDER_SUBSCRIPTION_'.strtoupper($block).'_SELECTED'     => $selectedVal,
            ));
            $this->template->parse('subscription_' . $block . '_filter');
        }
    }
}
