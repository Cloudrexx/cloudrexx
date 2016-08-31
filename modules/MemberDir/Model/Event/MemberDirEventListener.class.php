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
 * EventListener for MemberDir
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_memberdir
 */

namespace Cx\Modules\MemberDir\Model\Event;

/**
 * EventListener for MemberDir
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_memberdir
 */
class MemberDirEventListener implements \Cx\Core\Event\Model\Entity\EventListener {

    public function onEvent($eventName, array $eventArgs) {
        $this->$eventName(current($eventArgs));
    }

    public static function SearchFindContent($search) {
        $term_db = $search->getTerm();

        //For MemberDir
        $query = "SELECT tblValue.id, tblDir.name AS title,
                           CONCAT_WS(' ', `1`, `2`, '') AS content
                      FROM " . DBPREFIX . "module_memberdir_values AS tblValue,
                           " . DBPREFIX . "module_memberdir_directories AS tblDir
                     WHERE tblDir.dirid=tblValue.dirid
                       AND tblValue.`lang_id`=" . FRONTEND_LANG_ID . "
                       AND (   tblValue.`1` LIKE '%$term_db%'
                            OR tblValue.`2` LIKE '%$term_db%'
                            OR tblValue.`3` LIKE '%$term_db%'
                            OR tblValue.`4` LIKE '%$term_db%'
                            OR tblValue.`5` LIKE '%$term_db%'
                            OR tblValue.`6` LIKE '%$term_db%'
                            OR tblValue.`7` LIKE '%$term_db%'
                            OR tblValue.`8` LIKE '%$term_db%'
                            OR tblValue.`9` LIKE '%$term_db%'
                            OR tblValue.`10` LIKE '%$term_db%'
                            OR tblValue.`11` LIKE '%$term_db%'
                            OR tblValue.`12` LIKE '%$term_db%'
                            OR tblValue.`13` LIKE '%$term_db%'
                            OR tblValue.`14` LIKE '%$term_db%'
                            OR tblValue.`15` LIKE '%$term_db%'
                            OR tblValue.`16` LIKE '%$term_db%'
                            OR tblValue.`17` LIKE '%$term_db%'
                            OR tblValue.`18` LIKE '%$term_db%')";
        $result = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($search->getResultArray($query, 'MemberDir', '', 'mid=', $search->getTerm()));
        $search->appendResult($result);

        //For MemberDirCategory
        $categoryQuery = "SELECT dirid AS id, name AS title, description AS content,
                           MATCH (name, description) AGAINST ('%$term_db%') AS score
                      FROM " . DBPREFIX . "module_memberdir_directories
                     WHERE active='1'
                       AND lang_id=" . FRONTEND_LANG_ID . "
                       AND (   name LIKE ('%$term_db%')
                            OR description LIKE ('%$term_db%'))";
        $categoryResult = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($search->getResultArray($categoryQuery, 'MemberDir', '', 'id=', $search->getTerm()));
        $search->appendResult($categoryResult);
    }

}
