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
 * JsonNews
 * Json controller for news module
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_news
 */

namespace Cx\Core_Modules\News\Controller;
use \Cx\Core\Json\JsonAdapter;

/**
 * JsonNews
 * Json controller for news module
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_news
 */
class JsonNews implements JsonAdapter {
    /**
     * List of messages
     * @var Array
     */
    private $messages = array();

    /**
     * Returns the internal name used as identifier for this adapter
     * @return String Name of this adapter
     */
    public function getName() {
        return 'News';
    }

    /**
     * Returns an array of method names accessable from a JSON request
     * @return array List of method names
     */
    public function getAccessableMethods() {
        return array('getAllNews');
    }

    /**
     * Returns all messages as string
     * @return String HTML encoded error messages
     */
    public function getMessagesAsString() {
        return implode('<br />', $this->messages);
    }
    /**
     * Returns default permission as object
     * @return Object
     */
    public function getDefaultPermissions() {
        return null;
    }

    /**
     * get all news list
     *
     * @return json result
     */
    public function getAllNews($data = array())
    {
        global $objDatabase;

        $searchTerm  = isset($data['get']['term'])
            ? contrexx_input2raw($data['get']['term'])
            : '';

        $id = isset($data['get']['id'])
            ? contrexx_input2int($data['get']['id'])
            : 0;

        $langId = isset($data['get']['langId'])
            ? contrexx_input2int($data['get']['langId'])
            : 0;

        if (empty($searchTerm)) {
            $this->messages[] = '';//TODO Show error message
        }

        $query = '
            SELECT
                    n.`id`,
                    nl.`title`

            FROM `'     . DBPREFIX . 'module_news`          AS `n`
            LEFT JOIN ' . DBPREFIX . 'module_news_locale    AS `nl`
            ON      nl.`news_id` = n.`id`
            WHERE   nl.`is_active`="1"
            AND     n.`status`="1"'
            . (!empty($id)
                ? ' AND n.`id`!="' . $id . '"'
                : ''
            )
            . (!empty($langId)
                ? ' AND nl.`lang_id`="' . $langId . '"'
                : ''
            )
            . ' AND (
                        nl.title        LIKE "%' .  contrexx_raw2db($searchTerm) . '%"
                    OR  nl.teaser_text  LIKE "%' .  contrexx_raw2db($searchTerm) . '%"
                )
            ORDER BY nl.`title`';
        $result = array();
        $objResult = $objDatabase->Execute($query);
        if (    $objResult
            &&  $objResult->RecordCount() > 0
        ) {
            while (!$objResult->EOF) {
                $result[$objResult->fields['id']] = $objResult->fields['title'];
                $objResult->MoveNext();
            }
        }
        return $result;
    }
}
