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
 * Calendar
 *
 * @package    cloudrexx
 * @subpackage module_calendar
 * @author     Cloudrexx <info@cloudrexx.com>
 * @copyright  CLOUDREXX CMS - CLOUDREXX AG
 * @version    1.00
 */

namespace Cx\Modules\Calendar\Controller;
/**
 * Calendar Class Headlines
 *
 * @package    cloudrexx
 * @subpackage module_calendar
 * @author     Cloudrexx <info@cloudrexx.com>
 * @copyright  CLOUDREXX CMS - CLOUDREXX AG
 * @version    1.00
 */
class CalendarHeadlines extends CalendarLibrary
{
    /**
     * Event manager object
     *
     * @var CalendarEventManager     Local instance of CalendarEventManager
     */
    protected $objEventManager;

    /**
     * Headlines constructor
     *
     * @param string $pageContent Template content
     */
    public function __construct($pageContent) {
        parent::__construct('.');
        $this->getSettings();

        $this->pageContent = $pageContent;

        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
    }

    /**
     * Initialize a local instance of the event manager (CalendarEventManager)
     *
     * @param   integer $categoryId Limit events to a specific category
     * @param   boolean $listAll    If set to TRUE, then all events (without
     *                              limit) will be loaded.
     * @param   boolean $archive    If set to TRUE, then not the upcoming events
     *                              will be loaded, but instead the past events.
     */
    protected function loadEventManager(
        $categoryId = null,
        $listAll = false,
        $archive = false
    ) {
        if (
            $this->arrSettings['headlinesStatus'] != 1 ||
            !$this->_objTpl->blockExists('calendar_headlines_row')
        ) {
            return;
        }

        // set event range (upcoming or past events)
        if ($archive) {
            $sortDirection = 'DESC';
            $startDate = null;
            $endDate = new \DateTime();
        } else {
            $sortDirection = 'ASC';
            $startDate = new \DateTime();

            switch ($this->arrSettings['frontendPastEvents']) {
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

            $endDate = new \DateTime();
            $endDate->setTime(23, 59, 59);
            $endDate->modify('+10 years');
        }

        // set category filter
        if (!$categoryId && !empty($this->arrSettings['headlinesCategory'])) {
            $categoryId = intval($this->arrSettings['headlinesCategory']);
        }

        // set limit
        $startPos = 0;
        if ($listAll) {
            $endPos = 'n';
        } else {
            $endPos = $this->arrSettings['headlinesNum'];
        }

        // initialize event manager
        $this->objEventManager = new \Cx\Modules\Calendar\Controller\CalendarEventManager(
            $startDate,
            $endDate,
            $categoryId,
            null,
            true,
            false,
            true,
            $startPos,
            $endPos,
            $sortDirection
        );
        // load events based on initialized configuration
        $this->objEventManager->getEventList();
    }

    /**
     * Return's headlines
     *
     * @param   integer $categoryId Only list events of a specific category
     * @param   boolean $listAll    If set to TRUE, then all events (without
     *                              limit) will be listed.
     * @param   boolean $archive    If set to TRUE, then not the upcoming events
     *                              will be parsed, but instead the past events.
     * @return string parsed template content
     */
    public function getHeadlines(
        $categoryId = null,
        $listAll = false,
        $archive = false
    ) {
        global $_CONFIG;

        \LinkGenerator::parseTemplate($this->pageContent);
        $this->_objTpl->setTemplate($this->pageContent,true,true);

        if($this->arrSettings['headlinesStatus'] == 1) {
            if($this->_objTpl->blockExists('calendar_headlines_row')) {
                $this->loadEventManager($categoryId, $listAll, $archive);
                if (!empty($this->objEventManager->eventList)) {
                    $this->objEventManager->showEventList($this->_objTpl);
                }
            }
        } else {
            if ($this->_objTpl->blockExists('calendar_headlines_row')) {
                $this->_objTpl->hideBlock('calendar_headlines_row');
            }
        }

        return $this->_objTpl->get();
    }
}
