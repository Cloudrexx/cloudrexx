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
 * LegacyDatabaseRepository
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_datasource
 */

namespace Cx\Core\DataSource\Model\Entity;

/**
 * LegacyDatabaseRepository
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_datasource
 */
class LegacyDatabaseRepository extends DataSource {

    /**
     * Gets one or more entries from this DataSource
     *
     * If an argument is not provided, no restriction is made for this argument.
     * So if this is called without any arguments, all entries of this
     * DataSource are returned.
     * If no entry is found, an empty array is returned.
     * @param string $elementId (optional) ID of the element if only one is to be returned
     * @param array $filter (optional) field=>value-type condition array, only supports = for now
     * @param array $order (optional) field=>order-type array, order is either "ASC" or "DESC"
     * @param int $limit (optional) If set, no more than $limit results are returned
     * @param int $offset (optional) Entry to start with
     * @param array $fieldList (optional) Limits the result to the values for the fields in this list
     * @throws \Exception If something did not go as planned
     * @return array Two dimensional array (/table) of results (array($row=>array($fieldName=>$value)))
     */
    public function get(
        $elementId = null,
        $filter = array(),
        $order = array(),
        $limit = 0,
        $offset = 0,
        $fieldList = array()
    ) {
        $tableName = DBPREFIX . $this->getIdentifier();

        // $elementId
        $whereList = array();
        if (isset($elementId)) {
            $whereList[] = '`id` = "' . contrexx_raw2db($elementId) . '"';
        }

        // $filter
        if (count($filter)) {
            foreach ($filter as $field => $value) {
                if (count($fieldList) && !in_array($field, $fieldList)) {
                    continue;
                }
                $whereList[] = '`' . contrexx_raw2db($field) . '` = "' . contrexx_raw2db($value) . '"';
            }
        }

        // $order
        $orderList = array();
        if (count($order)) {
            foreach ($order as $field => $ascdesc) {
                if (count($fieldList) && !in_array($field, $fieldList)) {
                    continue;
                }
                if (!in_array($ascdesc, array('ASC', 'DESC'))) {
                    $ascdesc = 'ASC';
                }
                $orderList[] = '`' . contrexx_raw2db($field) . '` ' . $ascdesc;
            }
        }

        // $limit, $offset
        $limitQuery = '';
        if ($limit) {
            $limitQuery = 'LIMIT ' . intval($limit);
            if ($offset) {
                $limitQuery .= ',' . intval($offset);
            }
        }

        // $fieldList
        $fieldListQuery = '*';
        if (count($fieldList)) {
            $fieldListQuery = '`' . implode('`, `', $fieldList) . '`';
        }

        // query parsing
        $whereQuery = '';
        if (count($whereList)) {
            $whereQuery = 'WHERE ' . implode(' AND ', $whereList);
        }
        $orderQuery = '';
        if (count($orderList)) {
            $orderQuery = 'ORDER BY ' . implode(', ', $orderList);
        }
        $query = '
            SELECT
                ' . $fieldListQuery . '
            FROM
                `' . $tableName . '`
            ' . $whereQuery . '
            ' . $orderQuery . '
            ' . $limitQuery . '
        ';

        $result = $this->cx->getDb()->getAdoDb()->query($query);
        $data = array();
        while (!$result->EOF) {
            $data[] = $result->fields;
            $result->MoveNext();
        }

        return $data;//new \Cx\Core_Modules\Listing\Model\Entity\DataSet($data);//array($query);
    }

    /**
     * Adds a new entry to this DataSource
     * @param array $data Field=>value-type array. Not all fields may be required.
     * @throws \BadMethodCallException ALWAYS! Legacy is not intended to be used for write access!
     */
    public function add($data) {
        throw new \BadMethodCallException('Access denied');
    }

    /**
     * Updates an existing entry of this DataSource
     * @param string $elementId ID of the element to update
     * @param array $data Field=>value-type array. Not all fields are required.
     * @throws \BadMethodCallException ALWAYS! Legacy is not intended to be used for write access!
     */
    public function update($elementId, $data) {
        throw new \BadMethodCallException('Access denied');
    }

    /**
     * Drops an entry from this DataSource
     * @param string $elementId ID of the element to update
     * @throws \BadMethodCallException ALWAYS! Legacy is not intended to be used for write access!
     */
    public function remove($elementId) {
        throw new \BadMethodCallException('Access denied');
    }
}
