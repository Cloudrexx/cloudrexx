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
 * Lsv Repository
 * Used for custom repository methods
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
namespace Cx\Modules\Shop\Model\Repository;

/**
 * Lsv Repository
 * Used for custom repository methods
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class LsvRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Save lsv
     *
     * @param array $values  lsv information
     * @param int   $orderId id of associated order
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save($values, $orderId)
    {
        $lsv = $this->find($orderId);

        if (empty($lsv)) {
            $lsv = new $this->_entityName();
        }

        $columnNames = $this->_em->getClassMetadata(
            $this->_entityName
        )->getColumnNames();

        foreach ($columnNames as $columnName) {
            $value = $values[$columnName];
            if (empty($value)) {
                continue;
            }

            $setter = 'set' . ucfirst($columnName);
            $lsv->$setter($value);
        }
        $this->_em->persist($lsv);
        $this->_em->flush();
    }
}
