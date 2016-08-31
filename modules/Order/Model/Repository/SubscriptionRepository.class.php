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
 * Class SubscriptionRepository
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_order
 */

namespace Cx\Modules\Order\Model\Repository;

/**
 * Class SubscriptionRepository
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @author      Thomas Däppen <thomas.daeppen@comvation.com>
 * @package     cloudrexx
 * @subpackage  module_order
 */
class SubscriptionRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Fetch expired Subscriptions
     *
     * @param   mixed   $status Optional argument to filter the expired subscriptions
     *                          by status (Subscription::$state).
     *                          Specify single status as string or multiple status as array.
     * @return  array   Returns an array of Subscription objects. If none are found, NULL is returned.
     */
    public function getExpiredSubscriptions($status = null)
    {
        $now = new \DateTime('now');
        $qb  = \Env::get('em')->createQueryBuilder();
        $qb->select('s')
                ->from('\Cx\Modules\Order\Model\Entity\Subscription', 's')
                ->andWhere('s.expirationDate <= :expirationDate')
                ->setParameter('expirationDate', $now->format("Y-m-d H:i:s"));
        if ($status) {
            if (is_array($status)) {
                $qb->andWhere($qb->expr()->in('s.state', $status));
            } else {
                $qb->andWhere('s.state = :state')->setParameter('state', $status);
            }
        }
        return $qb->getQuery()->getResult();
    }

    /**
     * Find the subscriptions by the filter
     *
     * @param string $filter
     *
     * @return array
     */
    function findSubscriptionsBySearchTerm($filter) {
        if (empty($filter)) {
            return array();
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('p')
            ->from('\Cx\Modules\Pim\Model\Entity\Product', 'p')
            ->groupBy('p.entityClass');

        $products = $qb->getQuery()->getResult();

        $subscriptions = array();
        foreach ($products as $product) {
            $ids  = array();
            $repo = $this->getEntityManager()->getRepository($product->getEntityClass());
            if ($repo && method_exists($repo, 'findByTerm')) {
                if (!empty($filter['term'])) {
                    $entities = $repo->findByTerm($filter['term']);
                    if (empty($entities)) {
                        continue;
                    }
                    $entityClassMetaData = $this->getEntityManager()->getClassMetadata($product->getEntityClass());
                    $primaryKeyName      = $entityClassMetaData->getSingleIdentifierFieldName();
                    $methodName          = 'get'. ucfirst($primaryKeyName);
                    foreach ($entities as $entity) {
                        $ids[] = $entity->$methodName();
                    }
                    $options = array('in' => array(array('s.productEntityId', $ids)), 'p.entityClass' => $product->getEntityClass());
                }
            }

            if (!empty($filter['filterProduct'])) {
                $options['in'][]   = array('p.id', $filter['filterProduct']);
            }
            if (!empty($filter['filterState'])) {
                $options['in'][]   = array('s.state', $filter['filterState']);
            }
            $subscriptions = array_merge($subscriptions, $this->getSubscriptionsByCriteria($options));
        }

        return $subscriptions;
    }

    /**
     * Get the subscriptions by criteria
     *
     * @param array $criteria
     *
     * @return array
     */
    function getSubscriptionsByCriteria($criteria, $order) {
        if (empty($criteria) && empty($order)) {
            return array();
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('s')
            ->from('\Cx\Modules\Order\Model\Entity\Subscription', 's')
            ->leftJoin('s.product', 'p')
            ->leftJoin('s.order', 'o');

        if (!empty($order)) {
            foreach ($order as $field => $type) {
                $qb->orderBy($field, $type);
            }
        }

        $i = 1;
        foreach ($criteria as $fieldType => $value) {
            if (method_exists($qb->expr(), $fieldType) && is_array($value)) {
                foreach ($value as $condition) {
                    $qb->andWhere(call_user_func(array($qb->expr(), $fieldType), $condition[0], $condition[1]));
                }
            } else {
                $qb->andWhere($fieldType . ' = ?' . $i)->setParameter($i, $value);
            }
            $i++;
        }
        $subscriptions = $qb->getQuery()->getResult();

        return !empty($subscriptions) ? $subscriptions : array();
    }
}
