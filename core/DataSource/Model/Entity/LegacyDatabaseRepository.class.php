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
     * Field list cache
     * @var array List of fields
     */
    protected $fieldList = array();

    /**
     * Identifier field list cache
     * @var array List of fields
     */
    protected $identifierFieldList = array();

    /**
     * Returns a list of field names this DataSource consists of
     * @return array List of field names
     */
    public function listFields() {
        if (!count($this->fieldList)) {
            $this->initializeFields();
        }
        return $this->fieldList;
    }

    /**
     * Initialize field caches
     */
    protected function initializeFields() {
        $tableName = DBPREFIX . $this->getIdentifier();
        $result = $this->cx->getDb()->getAdoDb()->query(
            'SHOW COLUMNS FROM `' . $tableName . '`'
        );
        while (!$result->EOF) {
            $this->fieldList[] = $result->fields['Field'];
            if ($result->fields['Key'] == 'PRI') {
                $this->identifierFieldList[] = $result->fields['Field'];
            }
            $result->MoveNext();
        }
        return $this->fieldList;
    }

    /**
     * @inheritdoc
     */
    public function getIdentifierFieldNames() {
        if (!count($this->identifierFieldList)) {
            $this->initializeFields();
        }
        return $this->identifierFieldList;
    }

    /**
     * Gets one or more entries from this DataSource
     *
     * If an argument is not provided, no restriction is made for this argument.
     * So if this is called without any arguments, all entries of this
     * DataSource are returned.
     * If no entry is found, an empty array is returned.
     * @param array $elementId (optional) field=>value-type condition array identifying an entry
     * @param array $filter (optional) field=>value-type condition array, only supports = for now
     * @param array $order (optional) field=>order-type array, order is either "ASC" or "DESC"
     * @param int $limit (optional) If set, no more than $limit results are returned
     * @param int $offset (optional) Entry to start with
     * @param array $fieldList (optional) Limits the result to the values for the fields in this list
     * @throws \Exception If something did not go as planned
     * @return array Two dimensional array (/table) of results (array($row=>array($fieldName=>$value)))
     */
    public function get(
        $elementId = array(),
        $filter = array(),
        $order = array(),
        $limit = 0,
        $offset = 0,
        $fieldList = array()
    ) {
        $tableName = DBPREFIX . $this->getIdentifier();
        $whereList = array();

        // $filter
        foreach ($filter as $field => $filterExpr) {
            foreach ($filterExpr as $operation=>$value) {
                if ($operation != 'eq') {
                    throw new \InvalidArgumentException(
                        'Operation "' . $operation . '" is not supported'
                    );
                }
                if (count($fieldList) && !in_array($field, $fieldList)) {
                    continue;
                }
                $whereList[] = '`' . contrexx_raw2db($field) . '` = "' . contrexx_raw2db($value) . '"';
            }
        }

        // $elementId
        foreach ($elementId as $field => $value) {
            if (count($fieldList) && !in_array($field, $fieldList)) {
                continue;
            }
            $whereList[] = '`' . contrexx_raw2db($field) . '` = "' . contrexx_raw2db($value) . '"';
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
     * @param array $elementId field=>value-type condition array identifying an entry
     * @param array $data Field=>value-type array. Not all fields are required.
     * @throws \BadMethodCallException ALWAYS! Legacy is not intended to be used for write access!
     */
    public function update($elementId, $data) {
        throw new \BadMethodCallException('Access denied');
    }

    /**
     * Drops an entry from this DataSource
     * @param array $elementId field=>value-type condition array identifying an entry
     * @throws \BadMethodCallException ALWAYS! Legacy is not intended to be used for write access!
     */
    public function remove($elementId) {
        throw new \BadMethodCallException('Access denied');
    }
}
