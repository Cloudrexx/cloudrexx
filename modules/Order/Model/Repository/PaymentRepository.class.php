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
 * Class PaymentRepository
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_order
 */

namespace Cx\Modules\Order\Model\Repository;

/**
 * Class PaymentRepository
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_order
 */
class PaymentRepository extends \Doctrine\ORM\EntityRepository {

    /**
     * Get the payment by criteria
     *
     * @param string $criteria
     *
     * @return object
     */
    public function findOneByCriteria($criteria) {
        if (empty($criteria)) {
            return;
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('p')
           ->from('\Cx\Modules\Order\Model\Entity\Payment', 'p');

        $i = 1;
        foreach ($criteria as $key => $value) {
            switch ($key) {
                case 'transactionReference':
                    $operator = ' LIKE ?';
                    $term = $value . '%';
                    break;

                default:
                    $operator = ' = ?';
                    $term = $value;
                    break;
            }

            if ($i == 1) {
                if (is_null($value)) {
                    $qb->where("p.$key IS NULL");
                } else {
                    $qb->where('p.' . $key . $operator . $i)->setParameter($i, $term);
                }
            } else {
                if (is_null($value)) {
                    $qb->andWhere("p.$key IS NULL");
                } else {
                    $qb->andWhere('p.' . $key . $operator . $i)->setParameter($i, $term);
                }
            }
            $i++;
        }

        $result = $qb->getQuery()->getResult();
        if (!$result) {
            return;
        }

        return current($result);
    }
}
