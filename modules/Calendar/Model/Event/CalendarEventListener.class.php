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
 * EventListener for Calendar
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_calendar
 */

namespace Cx\Modules\Calendar\Model\Event;
use Cx\Core\MediaSource\Model\Entity\MediaSourceManager;
use Cx\Core\MediaSource\Model\Entity\MediaSource;
use Cx\Core\Event\Model\Entity\DefaultEventListener;

/**
 * EventListener for Calendar
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_calendar
 */
class CalendarEventListener extends DefaultEventListener {

    public function SearchFindContent($search) {
        $term_db = $search->getTerm();
        $query = \Cx\Modules\Calendar\Controller\CalendarEvent::getEventSearchQuery($term_db);
        $dateTime = $this->getComponent('DateTime');
        $pageUrl = function($pageUri, $searchData) use ($dateTime) {
            $date = $dateTime->createDateTimeForDb($searchData['startdate']);
            $dateTime->db2user($date);
            $timestamp = $date->getTimestamp();
            return $pageUri . '?id=' . $searchData['id'] . '&date=' . $timestamp;
        };
        $result = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($search->getResultArray($query, 'Calendar', 'detail', $pageUrl, $search->getTerm()));
        $search->appendResult($result);
    }

    public function mediasourceLoad(MediaSourceManager $mediaBrowserConfiguration)
    {
        global $_ARRAYLANG;
        $mediaType = new MediaSource('calendar',$_ARRAYLANG['TXT_CALENDAR'],array(
            $this->cx->getWebsiteImagesCalendarPath(),
            $this->cx->getWebsiteImagesCalendarWebPath(),
        ),array(16));
        $mediaBrowserConfiguration->addMediaType($mediaType);
    }

    public function onFlush($eventArgs) {
        $this->cx->getComponent('Cache')->deleteComponentFiles('Calendar');
        $this->cx->getComponent('Cache')->deleteComponentFiles('Home');
    }
}
