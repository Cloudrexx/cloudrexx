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
 * EventListener for DocSys
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_docsys
 */

namespace Cx\Modules\DocSys\Model\Event;

/**
 * EventListener for DocSys
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_docsys
 */
class DocSysEventListener implements \Cx\Core\Event\Model\Entity\EventListener {

    public function onEvent($eventName, array $eventArgs) {
        $this->$eventName(current($eventArgs));
    }

    public static function SearchFindContent($search) {
        $term_db = $search->getTerm();
        $query = "SELECT id, text AS content, title,
                     MATCH (text, title) AGAINST ('%$term_db%') AS score
                      FROM " . DBPREFIX . "module_docsys
                     WHERE (   text LIKE ('%$term_db%')
                            OR title LIKE ('%$term_db%'))
                       AND lang=" . FRONTEND_LANG_ID . "
                       AND status=1
                       AND (startdate<='" . date('Y-m-d') . "' OR startdate='0000-00-00')
                       AND (enddate>='" . date('Y-m-d') . "' OR enddate='0000-00-00')";
        $result = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($search->getResultArray($query, 'DocSys', 'details', 'id=', $search->getTerm()));
        $search->appendResult($result);
    }

}
