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


namespace Cx\Core_Modules\Workbench\Model\Entity;

abstract class NavCommand extends Command {

    /**
     * Returns a recursive list of all backend navigation entries
     */
    protected function getEntries() {
        $entries = array();
        $query = '
            SELECT
                `area_id`,
                `parent_area_id`,
                `type`,
                `scope`,
                `area_name`,
                `is_active`,
                `uri`,
                `target`,
                `module_id`,
                `order_id`,
                `access_id`
            FROM
                `' . DBPREFIX . 'backend_areas`
            WHERE
                (
                    `type` = \'navigation\' OR
                    `type` = \'group\'
                )
            ORDER BY
                `parent_area_id` ASC,
                `order_id` ASC
        ';
        $res = $this->interface->getDb()->getAdoDb()->execute($query);
        $parent = &$entries;
        // this works for 2 dimensions only (but backend nav does not have more)
        while (!$res->EOF) {
            if ($res->fields['parent_area_id'] != 0) {
                $parent = &$entries[$res->fields['parent_area_id']];
                if (!isset($parent['children'])) {
                    $parent['children'] = array();
                }
                $parent = &$parent['children'];
            } else {
                $parent = &$entries;
            }
            $parent[$res->fields['area_id']] = $res->fields;
            /*if (isset($_CORELANG[$res->fields['area_name']])) {
                $parent[$res->fields['area_id']]['area_name'] = $_CORELANG[$res->fields['area_name']];
            }*/
            $res->moveNext();
        }
        return $entries;
    }

    /**
     * Adds a new entry to the backend navigation
     * @param array $entry Entry info: array('area_name'=>{name}, 'uri'=>{uri}, 'module_id'=>{module id}, 'access_id'=>{access_id})
     * @param int $parentId ID of the parent entry (default 0)
     * @param int $pos Position of the new element within all elements sharing the same parent ID (default 0)
     */
    protected function insertEntry(array $entry, $parentId = 0, $pos = 0) {
        // move later entries
        $query = '
            UPDATE
                `' . DBPREFIX . 'backend_areas`
            SET
                `order_id` = `order_id` + 1
            WHERE
                `parent_area_id` = ' . $parent_id . ' AND
                `type` = \'navigation\' AND
                `order_id` >= ' . $pos . '
        ';
        // insert new
        $query = '
            INSERT INTO
                `' . DBPREFIX . 'backend_areas` (
                    `parent_area_id`,
                    `type`,
                    `scope`,
                    `area_name`,
                    `is_active`,
                    `uri`,
                    `target`,
                    `module_id`,
                    `order_id`,
                    `access_id`
                )
            VALUES (
                \'' . $parentId . '\',
                \'navigation\',
                \'backend\',
                \'' . $entry['area_name'] . '\',
                1,
                \'' . $entry['uri'] . '\',
                \'_self\',
                ' . $entry['module_id'] . ',
                ' . $pos . ',
                ' . $entry['access_id'] . '
            )
        ';
        // revert moving later entries if insert has failed?
    }

    /**
     * Removes the backend navigation entry with the given ID
     * (only if it's an entry of type 'navigation')
     * @param int $id ID of backend navigation entry to remove
     */
    protected function removeEntry($id) {
        // get required info
        $query = '
            SELECT
                `order_id`,
                `parent_area_id`
            FROM
                `' . DBPREFIX . 'backend_areas`
            WHERE
                `area_id` = ' . $id . ' AND
                `type` = \'navigation\'
        ';
        // remove if info was found
        $query = '
            DELETE FROM
                `' . DBPREFIX . 'backend_areas`
            WHERE
                `area_id` = ' . $id . ' AND
                `type` = \'navigation\'
        ';
        // move later entries (if one was removed)
        $query = '
            UPDATE
                `' . DBPREFIX . 'backend_areas`
            SET
                `order_id` = `order_id` - 1
            WHERE
                `parent_area_id` = ' . $parent_id . ' AND
                `type` = \'navigation\' AND
                `order_id` >= ' . $pos . '
        ';
    }
}
