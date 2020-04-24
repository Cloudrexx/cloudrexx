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
 * Class OrderRepository
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_order
 */

namespace Cx\Modules\Order\Model\Repository;

/**
 * OrderRepositoryException
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_order
 */
class OrderRepositoryException extends \Exception {}

/**
 * Class OrderRepository
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_order
 */
class OrderRepository extends \Doctrine\ORM\EntityRepository {

    /**
     * Get orders by the search term
     *
     * @param type $term    Search term
     * @param type $contact Crm Contact id or \User object
     *
     * @return object
     */
    public function findOrdersBySearchTerm($term, $contact)
    {
        $contactId = null;
        if ($contact instanceof \User) {
            $contactId = $contact->getCrmUserId();
            // selected user is not a crm user
            if (empty($contactId)) {
                return array();
            }
        } else {
            $contactId = $contact;
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb ->select('o')
            ->from('\Cx\Modules\Order\Model\Entity\Order', 'o')
            ->leftJoin('o.subscriptions', 's');

        $conditions = array();
        if (!empty($term)) {
            $subscriptionRepository = \Env::get('em')->getRepository('Cx\Modules\Order\Model\Entity\Subscription');
            $subscriptions          = $subscriptionRepository->findSubscriptionsBySearchTerm(array('term' => $term));
            if (empty($subscriptions)) {
                return array();
            }
            $subscriptionIds = array();
            foreach ($subscriptions as $subscription) {
                $subscriptionIds[] = $subscription->getId();
            }
            $conditions[] = $qb->expr()->in('s.id', $subscriptionIds);
        }

        if (!empty($contactId)) {
            $conditions[] = 'o.contactId = :contactId';
            $qb->setParameter('contactId', $contactId);
        }

        $first = true;
        foreach ($conditions as $condition) {
            $method = $first ? 'where' : 'andWhere';
            $qb->$method($condition);

            $first = false;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Check the order count by the $crmId
     *
     * @param integer $crmId Crm User Id
     *
     * @return boolean
     */
    public function hasOrderByCrmId($crmId = 0) {
        if (empty($crmId)) {
            return;
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('count(o.id)')
           ->from('\Cx\Modules\Order\Model\Entity\Order', 'o')
           ->where('o.contactId = :contactId');
        $qb->setParameter('contactId', $crmId);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Create a new Order
     *
     * @param integer $productId            productId
     * @param object  $objUser              \User object
     * @param string  $transactionReference transactionReference
     * @param array   $subscriptionOptions  subscriptionOptions
     *
     * @return boolean
     * @throws OrderRepositoryException
     */
    public function createOrder($productId, \Cx\Modules\Crm\Model\Entity\Currency $currency, \User $objUser , $transactionReference, $subscriptionOptions = array()) {
        if (
               \FWValidator::isEmpty($productId)
            || \FWValidator::isEmpty($subscriptionOptions)
            || \FWValidator::isEmpty($transactionReference)
            || \FWValidator::isEmpty($currency)
        ) {
            return;
        }

        $contactId = $objUser->getCrmUserId();
        if (\FWValidator::isEmpty($contactId)) {
            return;
        }

        try {
            $order = new \Cx\Modules\Order\Model\Entity\Order();
            $order->setContactId($contactId);
            $order->setCurrency($currency);
            $productRepository = \Env::get('em')->getRepository('Cx\Modules\Pim\Model\Entity\Product');
            $product = $productRepository->findOneBy(array('id' => $productId));

            //create subscription
            $subscription = $order->createSubscription($product, $subscriptionOptions);
            // set discount price for first payment period of subscription
            if (!empty($subscriptionOptions['oneTimeSalePrice'])) {
                $subscription->setPaymentAmount($subscriptionOptions['oneTimeSalePrice']);
            }

            $order->billSubscriptions();
            $invoices = $order->getInvoices();

            if (!empty($invoices)) {
                \DBG::msg(__METHOD__.": order has invoices");
                $paymentRepo = \Env::get('em')->getRepository('\Cx\Modules\Order\Model\Entity\Payment');
                foreach ($invoices as $invoice) {
                    if (!$invoice->getPaid()) {
                        \DBG::msg(__METHOD__.": lookup payment with transaction-reference $transactionReference and amount ".$invoice->getAmount());
                        $payment = $paymentRepo->findOneByCriteria(array('amount' => $invoice->getAmount(), 'transactionReference' => $transactionReference, 'invoice' => null));
                        if ($payment) {
                            \DBG::msg(__METHOD__.": payment found");
                            //set subscription-id to Subscription::$externalSubscriptionId
                            if ($subscription) {
                                \DBG::msg(__METHOD__.": trying to link to new subscription to the external subscription ID");
                                $referenceArry = explode('|', $payment->getTransactionReference());
                                if (isset($referenceArry[4]) && !empty($referenceArry[4])) {
                                    $subscription->setExternalSubscriptionId($referenceArry[4]);
                                }
                            }
                            $transactionData = $payment->getTransactionData();
                            if (   !\FWValidator::isEmpty($transactionData)
                                && isset($transactionData['contact'])
                                && isset($transactionData['contact']['id'])
                            ) {
                                \DBG::msg(__METHOD__.": set externalPaymentCustomerIdProfileAttributeId of user to ".$transactionData['contact']['id']);
                                $objUser->setProfile(
                                    array(
                                        \Cx\Core\Setting\Controller\Setting::getValue('externalPaymentCustomerIdProfileAttributeId','MultiSite') => array(0 => $transactionData['contact']['id'])
                                    ),
                                    true
                                );

                                if (!$objUser->store()) {
                                    \DBG::msg('Order::createOrder() Updating user failed: '.$objUser->getErrorMsg());
                                }
                            }
                            $invoice->addPayment($payment);
                            $payment->setInvoice($invoice);
                            \Env::get('em')->persist($invoice);
                            \Env::get('em')->persist($payment);
                            break;
                        }
                    }
                }
            }

            \Env::get('em')->persist($order);
            \Env::get('em')->flush();

            return $order;
        } catch (\Exception $e) {
            throw new OrderRepositoryException($e->getMessage());
        }
    }

    /**
     * Get the orders ordered by ID in descending order.
     *
     * @return array
     */
    public function getAllByDesc() {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('o')
           ->from('\Cx\Modules\Order\Model\Entity\Order', 'o')
           ->orderBy('o.id', 'DESC');

        return $qb->getQuery()->getResult();
    }
}
