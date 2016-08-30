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
 * YmlRepository
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_datasource
 */

namespace Cx\Core\DataSource\Model\Entity;

/**
 * YmlRepository
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_datasource
 */

class YmlRepository extends DataSource {

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
        throw new \Exception('Not yet implemented');
    }

    /**
     * Adds a new entry to this DataSource
     * @param array $data Field=>value-type array. Not all fields may be required.
     * @throws \Exception If something did not go as planned
     */
    public function add($data) {
        throw new \Exception('Not yet implemented');
    }

    /**
     * Updates an existing entry of this DataSource
     * @param string $elementId ID of the element to update
     * @param array $data Field=>value-type array. Not all fields are required.
     * @throws \Exception If something did not go as planned
     */
    public function update($elementId, $data) {
        throw new \Exception('Not yet implemented');
    }

    /**
     * Drops an entry from this DataSource
     * @param string $elementId ID of the element to update
     * @throws \Exception If something did not go as planned
     */
    public function remove($elementId) {
        throw new \Exception('Not yet implemented');
    }
}
