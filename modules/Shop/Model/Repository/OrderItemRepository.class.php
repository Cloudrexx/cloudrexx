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
 * Order Item Repository
 * Used for custom repository methods
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
namespace Cx\Modules\Shop\Model\Repository;

/**
 * Order Item Repository
 * Used for custom repository methods
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class OrderItemRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Save order item
     *
     * @param array $values  order item information
     * @param int   $order   associated order
     */
    public function save($values, $order)
    {
        $settersAndIds = $this->getSettersAndIds($values);

        foreach ($settersAndIds['ids'] as $orderItemId) {
            if (empty($values['product_product_id-' . $orderItemId])) {
                continue;
            }

            $orderItem = $this->find($orderItemId);
            if (empty($orderItem)) {
                $orderItem = new $this->_entityName();
            }

            if (empty($values['product_quantity-' . $orderItemId])) {
                $this->_em->remove($orderItem);
                continue;
            }

            $orderItem->setOrder($order);

            foreach ($settersAndIds['setters'] as $key => $setter) {

                $value = $values[$key . '-' . $orderItemId];

                // get product name and set product relation
                if ($key == 'product_product_name') {
                    $product = $this->_em->getRepository(
                        '\Cx\Modules\Shop\Model\Entity\Product'
                    )->findOneBy(
                        array(
                            'id' => $values['product_product_id-'. $orderItemId]
                        )
                    );
                    $orderItem->setProduct($product);
                    $value = $product->getName();
                }
                $orderItem->$setter($value);
            }
            $this->_em->persist($orderItem);
        }
    }

    /**
     * Returns an array with all setters and ids. In this array are only
     * setter that have a matching attribute in the entity.
     *
     * @param array $values order item attributes
     *
     * @return array  contains setters and ids
     */
    protected function getSettersAndIds($values)
    {
        $settersAndKeys = array();
        $setters = array();
        $ids = array();

        foreach ($values as $key=>$value) {
            if (strpos($key, 'product') === 0) {
                // Explode Keys by _ to get the key without prefix
                $keyParts = explode('_', $key, 2);
                $keyWithoutPrefix = $keyParts[1];
                // Split method name and product id
                $methodAndKey = explode('-', $keyWithoutPrefix);
                $methodName = $methodAndKey[0];
                // Use as new key in settersAndKeys array
                $keyWithoutId = $keyParts[0] . '_' . $methodName;
                // Id of order item
                $orderItemId = $methodAndKey[1];

                // Push id into $ids array if it not already exist
                if (!in_array($orderItemId, $ids)) {
                    array_push($ids, $orderItemId);
                }

                // Push all setters into $setters array if it not already exist
                $setter = $this->getSetter($methodName);
                if (!empty($setter)) {
                    $setters[$keyWithoutId] = $setter;
                }
            }
        }

        $settersAndKeys['setters'] = $setters;
        $settersAndKeys['ids'] = $ids;

        return $settersAndKeys;
    }

    /**
     * Convert a string into an setter if the attribute exists in the entity.
     *
     * @param $attributeName string name of the attribute that should be
     *                              converted.
     * @return string
     */
    protected function getSetter($attributeName)
    {
        $columnNames = $this->_em->getClassMetadata(
            $this->_entityName
        )->getColumnNames();

        if (!in_array($attributeName, $columnNames)) {
            return '';
        }

        // Replace _ and set new word to uppercase, to get the setter name
        $setter = 'set' . str_replace(
            " ",
            "",
            mb_convert_case(
                str_replace(
                    "_",
                    " ",
                    $attributeName
                ),
                MB_CASE_TITLE
            )
        );
        return $setter;
    }
}
