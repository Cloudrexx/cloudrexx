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
 * Class EsiWidgetController
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_calendar
 * @version     1.0.0
 */

namespace Cx\Modules\Calendar\Controller;

/**
 * JsonAdapter Controller to handle EsiWidgets
 * Usage:
 * - Create a subclass that implements parseWidget()
 * - Register it as a Controller in your ComponentController
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_calendar
 * @version     1.0.0
 */

class EsiWidgetController extends \Cx\Core_Modules\Widget\Controller\EsiWidgetController {
    /**
     * Parses a widget
     *
     * @param string                                 $name     Widget name
     * @param \Cx\Core\Html\Sigma                    $template Widget template
     * @param \Cx\Core\Routing\Model\Entity\Response $response Response object
     * @param array                                  $params   Get parameters
     */
    public function parseWidget($name, $template, $response, $params)
    {
        global $_LANGID, $_ARRAYLANG;

        $matches = null;
        if (
            !preg_match('/^EVENTS(\d{1,2}|)_FILE$/', $name, $matches) ||
            MODULE_INDEX >= 2 ||
            !\Cx\Core\Setting\Controller\Setting::getValue(
                'calendarheadlines',
                'Config'
            )
        ) {
            return;
        }

        $category   = null;
        $catMatches = null;
        //The global $_LANGID is required in the method
        //CalendarHeadlines::getHeadlines()
        $_LANGID    = $params['locale']->getId();
        if (!$params['theme']) {
            return;
        }

        $filePath   = $params['theme']->getFolderName() . '/' . 'events' .
            $matches[1] . '.html';
        $fileSystem = \Cx\Core\Core\Controller\Cx::instanciate()
            ->getMediaSourceManager()
            ->getMediaType('themes')
            ->getFileSystem();
        $file = new \Cx\Core\ViewManager\Model\Entity\ViewManagerFile(
            $filePath,
            $fileSystem
        );
        if (!$fileSystem->fileExists($file)) {
            return;
        }

        $content = file_get_contents(
            $fileSystem->getFullPath($file) . $file->getFullName()
        );
        if (
            preg_match(
                '/\{CALENDAR_CATEGORY_([0-9]+)\}/',
                $content,
                $catMatches
            )
        ) {
            $category = $catMatches[1];
        }

        // check if set limit shall be ignored
        //
        // note: placeholder can never be at position 0,
        // as the content does always first contain the
        // opening template block calendar_headlines_row
        $listAll = (bool) strpos($content, '{CALENDAR_LIMIT_OFF}');

        // check if instead of upcoming events the archive shall be listed
        //
        // note: placeholder can never be at position 0,
        // as the content does always first contain the
        // opening template block calendar_headlines_row
        $listArchive = (bool) strpos($content, '{CALENDAR_LIST_ARCHIVE}');

        $_ARRAYLANG = array_merge(
            $_ARRAYLANG,
            \Env::get('init')->getComponentSpecificLanguageData('Calendar', true, $_LANGID)
        );

        $headlines = new CalendarHeadlines($content);
        $template->setVariable(
            $name,
            $headlines->getHeadlines(
                $category,
                $listAll,
                $listArchive
            )
        );

        //Set expiration date
        // get next event
        $calendarLib = new CalendarLibrary('.');
        $calendarLib->getSettings();

        $startDate = new \DateTime();

        switch ($calendarLib->arrSettings['frontendPastEvents']) {
            case CalendarLibrary::SHOW_EVENTS_OF_TODAY:
                // get next ending event starting from today 0:01
                // the event's day on midnight is our expiration date
                $startDate->setTime(0, 0, 0);
                break;

            case CalendarLibrary::SHOW_EVENTS_UNTIL_START:
                // TODO: implement logic
                //break;

            case CalendarLibrary::SHOW_EVENTS_UNTIL_END:
            default:
                // keep the start date to NOW
                // fixing the timezone offset is not required here
                break;
        }

        $eventManager = new CalendarEventManager(
            $startDate,
            null,
            null,
            null,
            true,
            false,
            true,
            0,
            1
        );
        $eventManager->getEventList();
        $cacheExpirationDate = null;

        // if there is an upcoming event, then set then expiration date of the
        // request based on that event
        if (isset($eventManager->eventList[0])) {
            $cacheExpirationDate = $eventManager->eventList[0]->endDate;
            switch ($calendarLib->arrSettings['frontendPastEvents']) {
                case CalendarLibrary::SHOW_EVENTS_OF_TODAY:
                    // event shall be shown till the end of the day
                    $cacheExpirationDate->setTime(23, 59, 59);
                    break;

                case CalendarLibrary::SHOW_EVENTS_UNTIL_START:
                    // TODO: implement logic
                    //break;

                case CalendarLibrary::SHOW_EVENTS_UNTIL_END:
                default:
                    // Event shall be shown until it ends.
                    // The expiration date has already been set properly above.
                    // Nothing more to do here.
                    break;
            }
        }

        // if there is no upcoming event, then set then expiration date to the
        // next recurrence periode (which might again contain new events)
        if (!$cacheExpirationDate) {
            $cacheExpirationDate = new \DateTime();
            $additionalYears = intval(
                $calendarLib->arrSettings['maxSeriesEndsYear']
            ) + 1;
            $cacheExpirationDate->modify(
                '+' . $additionalYears . ' years'
            );
        }
        $response->setExpirationDate($cacheExpirationDate);
    }
}
