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
 * EventListener for News
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_news
 */

namespace Cx\Core_Modules\News\Model\Event;

/**
 * EventListener for News
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_news
 */
class NewsEventListener implements \Cx\Core\Event\Model\Entity\EventListener {

    /**
     * Callable on trigger Event
     *
     * @param string $eventName Event name
     * @param array  $eventArgs Arguments for the event
     */
    public function onEvent($eventName, array $eventArgs) {
        if (method_exists($this, $eventName)) {
            $this->$eventName($eventArgs);
        }
    }

    /**
     * Global search event listener
     * Appends the News search results to the search object
     *
     * @param array $eventArgs
     */
    private function SearchFindContent(array $eventArgs) {
        $search = current($eventArgs);
        $term_db = contrexx_raw2db($search->getTerm());
        $newsLib = new \Cx\Core_Modules\News\Controller\NewsLibrary();
        $query = '
            SELECT
                `id`,
                `text` AS "content",
                `title`,
                `date`,
                `redirect`,
                MATCH (
                    `text`,`title`,`teaser_text`
                ) AGAINST (
                    "%' . $term_db . '%"
                ) AS `score`
            FROM
                `' . DBPREFIX  . 'module_news` AS `tblN`
            INNER JOIN
                `' . DBPREFIX  . 'module_news_locale` AS `nl`
            ON
                `nl`.`news_id` = `tblN`.`id`
            WHERE
                (
                   `text` LIKE ("%' . $term_db . '%")
                    OR `title` LIKE ("%' . $term_db . '%")
                    OR `teaser_text` LIKE ("%' . $term_db . '%")
                )' .
            $newsLib->getNewsFilterQuery('tblN', '', '');

        $pageUrl = function($pageUri, $searchData) {
            static $objNewsLib = null;
            if (!$objNewsLib) {
                $objNewsLib = new \Cx\Core_Modules\News\Controller\NewsLibrary();
            }
            if (empty($searchData['redirect'])) {
                $newsId         = $searchData['id'];
                $newsCategories = $objNewsLib->getCategoriesByNewsId($newsId);
                $objUrl         = \Cx\Core\Routing\Url::fromModuleAndCmd(
                                        'News',
                                        $objNewsLib->findCmdById(
                                            'details', array_keys($newsCategories)
                                        ),
                                        FRONTEND_LANG_ID,
                                        array('newsid' => $newsId)
                                  );
                $pageUrlResult  = (string) $objUrl;
            } else {
                $pageUrlResult = preg_replace(
                    '/\\[\\[([A-Z0-9_-]+)\\]\\]/',
                    '{\\1}',
                    $searchData['redirect']
                );
                \LinkGenerator::parseTemplate($pageUrlResult);
            }
            return $pageUrlResult;
        };
        $result = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($search->getResultArray($query, 'News', '', $pageUrl, $search->getTerm()));
        $search->appendResult($result);
    }
}
