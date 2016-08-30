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
 * EventListener for Directory
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_directory
 */

namespace Cx\Modules\Directory\Model\Event;

/**
 * EventListener for Directory
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_directory
 */
class DirectoryEventListener implements \Cx\Core\Event\Model\Entity\EventListener {

    public function onEvent($eventName, array $eventArgs) {
        $this->$eventName(current($eventArgs));
    }

    public static function SearchFindContent($search) {
        $term_db = $search->getTerm();

        //For Directory
        $query = "SELECT id, title, description AS content,
                           MATCH (title, description) AGAINST ('%$term_db%') AS score
                      FROM " . DBPREFIX . "module_directory_dir
                     WHERE status='1'
                       AND (   title LIKE ('%$term_db%')
                            OR description LIKE ('%$term_db%')
                            OR searchkeys LIKE ('%$term_db%')
                            OR company_name LIKE ('%$term_db%'))";
        $result = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($search->getResultArray($query, 'Directory', 'detail', 'id=', $search->getTerm()));
        $search->appendResult($result);

        //For Directory Category
        $categoryQuery = "SELECT id, name AS title, description AS content,
                           MATCH (name, description) AGAINST ('%$term_db%') AS score
                      FROM " . DBPREFIX . "module_directory_categories
                     WHERE status='1'
                       AND (   name LIKE ('%$term_db%')
                            OR description LIKE ('%$term_db%'))";
        $categoryResult = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($search->getResultArray($categoryQuery, 'Directory', '', 'lid=', $search->getTerm()));
        $search->appendResult($categoryResult);
    }

}
