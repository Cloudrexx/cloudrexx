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
 * Rel Discount Group Repository
 * Used for custom repository methods
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
namespace Cx\Modules\Shop\Model\Repository;

/**
 * Rel Discount Group Repository
 * Used for custom repository methods
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class RelDiscountGroupRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Returns the customer/article type discount rate to be applied
     * for the given group IDs
     *
     * Frontend use only.
     * @param   integer   $groupCustomerId    The customer group ID
     * @param   integer   $groupArticleId     The article group ID
     * @return  float                         The discount rate, if applicable,
     *                                        0 (zero) otherwise
     */
    function getDiscountRateCustomer($groupCustomerId, $groupArticleId)
    {
        $rate = $this->findOneBy(
            array(
                'customerGroupId' => $groupCustomerId,
                'articleGroupId' => $groupArticleId
            )
        );

        if (!empty($rate)) {
            return $rate->getRate();
        }
        return 0;
    }

    /**
     * Returns an array with all the customer/article type discount rates.
     *
     * The array has the structure
     *  array(
     *    customerGroupId => array(
     *      articleGroupId => discountRate,
     *      ...
     *    ),
     *    ...
     *  );
     * @return  array The discount rate array on success, empty array otherwise
     */
    function getDiscountRateCustomerArray()
    {
        $relDiscountGroups = $this->findAll();

        $arrDiscountRateCustomer = array();
        foreach ($relDiscountGroups as $relDiscountGroup) {
            $arrDiscountRateCustomer[$relDiscountGroup->getCustomerGroupId()]
            [$relDiscountGroup->getArticleGroupId()] =
                $relDiscountGroup->getRate();
        }

        return $arrDiscountRateCustomer;
    }

    /**
     * Store the customer/article group discounts in the database.
     *
     * Backend use only.
     * The array argument has the structure
     *  array(
     *    customerGroupId => array(
     *      articleGroupId => discountRate,
     *      ...
     *    ),
     *    ...
     *  );
     * @param   array     $arrDiscountRate  The array of discount rates
     *
     * @throws \Doctrine\ORM\OptimisticLockException handle orm interaction
     */
    function storeDiscountCustomer($arrDiscountRate)
    {
        $articleGroupRepo = $this->_em->getRepository(
            'Cx\Modules\Shop\Model\Entity\ArticleGroup'
        );
        $customerGroupRepo = $this->_em->getRepository(
            'Cx\Modules\Shop\Model\Entity\CustomerGroup'
        );
        foreach ($arrDiscountRate as $customerGroupId => $articleGroup) {
            foreach ($articleGroup as $articleGroupId => $discountRate) {
                if (empty($articleGroupId)) {
                    continue;
                }
                $discountGroup = $this->findOneBy(
                    array(
                        'articleGroupId' => $articleGroupId,
                        'customerGroupId' => $customerGroupId
                    )
                );

                if (empty($discountGroup)) {
                    $discountGroup =
                        new \Cx\Modules\Shop\Model\Entity\RelDiscountGroup();
                    $discountGroup->setArticleGroupId($articleGroupId);
                    $discountGroup->setArticleGroup(
                        $articleGroupRepo->find($articleGroupId)
                    );
                    $discountGroup->setCustomerGroupId($customerGroupId);
                    $discountGroup->setCustomerGroup(
                        $customerGroupRepo->find($customerGroupId)
                    );
                }
                $discountGroup->setRate($discountRate);
                $this->_em->persist($discountGroup);
            }
        }
        $this->_em->flush();
    }
}
