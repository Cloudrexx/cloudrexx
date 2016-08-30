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
                `text` AS `content`,
                `title`,
                `date`,
                `redirect`,
                `MATCH` (
                    `text`,`title`,`teaser_text`
                ) `AGAINST` (
                    "%`' . $term_db . '`%"
                ) AS `score`
            FROM
                `' . DBPREFIX  . 'module_news` AS `tblN`
            INNER JOIN
                `' . DBPREFIX  . 'module_news_locale` AS `tblL`
            ON
                `tblL`.`news_id` = `tblN`.`id`
            WHERE
                (
                   `text` `LIKE` ("%`' . $term_db . '`%")
                    OR `title` `LIKE` ("%`' . $term_db . '`%")
                    OR `teaser_text` `LIKE` ("%`' . $term_db . '`%")
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
                $pageUrlResult  = $objUrl->toString();
            } else {
                $pageUrlResult = $searchData['redirect'];
            }
            return $pageUrlResult;
        };
        $result = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($search->getResultArray($query, 'News', '', $pageUrl, $search->getTerm()));
        $search->appendResult($result);
    }

    /**
     * Update the news locales
     * while activate/deactivate a language in the Administrative -> Language
     *
     * @param array $eventArgs Arguments for the event
     *
     * @return boolean
     */
    protected function languageStatusUpdate(array $eventArgs)
    {
        global $objDatabase;

        if (empty($eventArgs[0])) {
            return;
        }

        $defaultLangId = \FWLanguage::getDefaultLangId();
        foreach ($eventArgs[0]['langData'] as $args) {
            $langId     = isset($args['langId']) ? $args['langId'] : 0;
            $langStatus = isset($args['status']) ? $args['status'] : 0;

            if (    empty($langId)
                ||  !isset($args['status'])
                ||  (    !$langStatus
                     &&  !$eventArgs[0]['langRemovalStatus']
                    )
            ) {
                continue;
            }

            //Update the news locale
            $newsQuery = $langStatus ?
                            'INSERT IGNORE INTO
                                `' . DBPREFIX . 'module_news_locale`
                                (   `news_id`,
                                    `lang_id`,
                                    `is_active`,
                                    `title`,
                                    `text`,
                                    `teaser_text`
                                )
                                SELECT `news_id`,
                                        ' . $langId . ',
                                        0,
                                        `title`,
                                        `text`,
                                        `teaser_text`
                                    FROM `' . DBPREFIX . 'module_news_locale`
                                    WHERE lang_id = ' . $defaultLangId
                        :   'DELETE FROM `' . DBPREFIX . 'module_news_locale`
                                WHERE lang_id = ' . $langId;
            $objDatabase->Execute($newsQuery);

            //Update the news category locale
            $catQuery = $langStatus ?
                            'INSERT IGNORE INTO
                                `' . DBPREFIX . 'module_news_categories_locale`
                                (   `category_id`,
                                    `lang_id`,
                                    `name`
                                )
                                SELECT `category_id`,
                                        ' . $langId . ',
                                        `name`
                                    FROM `' . DBPREFIX . 'module_news_categories_locale`
                                    WHERE lang_id = ' . $defaultLangId
                        :   'DELETE FROM `' . DBPREFIX . 'module_news_categories_locale`
                                WHERE lang_id = ' . $langId;
            $objDatabase->Execute($catQuery);
        }
    }
}
