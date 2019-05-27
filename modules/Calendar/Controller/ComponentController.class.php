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
 * Main controller for Calendar
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_calendar
 */

namespace Cx\Modules\Calendar\Controller;

/**
 * Main controller for Calendar
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_calendar
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {

    /**
     * Returns all Controller class names for this component (except this)
     *
     * Be sure to return all your controller classes if you add your own
     * @return array List of Controller class names (without namespace)
     */
    public function getControllerClasses()
    {
        return array('EsiWidget', 'JsonCalendar');
    }

    /**
     * Returns a list of JsonAdapter class names
     *
     * The array values might be a class name without namespace. In that case
     * the namespace \Cx\{component_type}\{component_name}\Controller is used.
     * If the array value starts with a backslash, no namespace is added.
     *
     * Avoid calculation of anything, just return an array!
     * @return array List of ComponentController classes
     */
    public function getControllersAccessableByJson()
    {
        return array('EsiWidgetController', 'JsonCalendarController');
    }

    /**
     * Load your component.
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $_CORELANG, $subMenuTitle, $objTemplate;
        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:

                define('CALENDAR_MANDATE', MODULE_INDEX);

                $objCalendar = new \Cx\Modules\Calendar\Controller\Calendar($page->getContent(), MODULE_INDEX);
                $page->setContent($objCalendar->getCalendarPage($page));
                break;

            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:
                $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
                $objTemplate = $this->cx->getTemplate();
                \Permission::checkAccess(16, 'static');
                $subMenuTitle = $_CORELANG['TXT_CALENDAR'];
                $objCalendarManager = new \Cx\Modules\Calendar\Controller\CalendarManager();
                $objCalendarManager->getCalendarPage();
                break;

            default:
                break;
        }
    }

    /**
     * Do something after system initialization
     *
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE.
     * This event must be registered in the postInit-Hook definition
     * file config/postInitHooks.yml.
     * @param \Cx\Core\Core\Controller\Cx   $cx The instance of \Cx\Core\Core\Controller\Cx
     */
    public function postInit(\Cx\Core\Core\Controller\Cx $cx)
    {
        // Get Calendar Events
        $widgetController = $this->getComponent('Widget');
        foreach (CalendarLibrary::getHeadlinePlaceholders() as $widgetName) {
            $widget = new \Cx\Core_Modules\Widget\Model\Entity\EsiWidget(
                $this,
                $widgetName
            );
            $widget->setEsiVariable(
                \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_THEME |
                \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_CHANNEL
            );
            $widgetController->registerWidget(
                $widget
            );
        }
    }

    /**
     * Register your event listeners here
     *
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE.
     * Keep in mind, that you can also register your events later.
     * Do not do anything else here than initializing your event listeners and
     * list statements like
     * $this->cx->getEvents()->addEventListener($eventName, $listener);
     */
    public function registerEventListeners() {
        $eventListener = new \Cx\Modules\Calendar\Model\Event\CalendarEventListener($this->cx);
        $this->cx->getEvents()->addEventListener('SearchFindContent', $eventListener);
        $this->cx->getEvents()->addEventListener('mediasource.load', $eventListener);

        foreach ($this->getEntityClasses() as $entityClassName) {
            $this->cx->getEvents()->addModelListener(\Doctrine\ORM\Events::onFlush, $entityClassName, $eventListener);
        }
   }

    /**
     * {@inheritdoc}
     */
   public function adjustResponse(
        \Cx\Core\Routing\Model\Entity\Response $response
    ) {
        $page = $response->getPage();
        if (
            !$page ||
            $page->getModule() !== $this->getName() ||
            !in_array($page->getCmd(), array('detail', 'register', 'sign'))
        ) {
            return;
        }

        $calendar = new \Cx\Modules\Calendar\Controller\Calendar('');
        $calendar->loadEventManager();
        $event = $calendar->getEventManager()->eventList[0];
        if (!$event) {
            return;
        }

        //Set the Page Title
        $pageTitle = $this->getPageTitle($event, $page->getCmd());
        if ($pageTitle) {
            $page->setTitle($pageTitle);
            $page->setContentTitle($pageTitle);
            $page->setMetaTitle($pageTitle);
        }

        //Set the Page Meta Description
        if ($page->getCmd() == 'detail') {
            $metaDesc = $this->getPageDescription($event);
            if ($metaDesc) {
                $page->setMetadesc($metaDesc);
            }
        }

        // Set the Page Meta Image
        if ($event->pic) {
            $page->setMetaimage($event->pic);
        }
   }

   /**
    * Get the Page title
    *
    * @param \Cx\Modules\Calendar\Controller\CalendarEvent $event Event object
    * @param string                                        $cmd   Page CMD
    *
    * @return string
    */
    protected function getPageTitle(CalendarEvent $event, $cmd)
    {
        $eventTitle = html_entity_decode(
            $event->title,
            ENT_QUOTES,
            CONTREXX_CHARSET
        );
        if ($cmd === 'detail') {
            return $eventTitle;
        }

        if (in_array($cmd, array('register', 'sign'))) {
            if (
                !$event->status ||
                ($event->access == 1 && !\FWUser::getFWUserObject()->objUser->login())
            ) {
                return '';
            }
            $calendarLib = new CalendarLibrary('.');
            return $calendarLib->format2userDate($event->startDate)
                . ": " . $eventTitle;
        }
    }

    /**
     * Get the Page description
     *
     * @param \Cx\Modules\Calendar\Controller\CalendarEvent $event Event object
     *
     * @return string
     */
    protected function getPageDescription(CalendarEvent $event)
    {
        // Set the meta page description to the teaser text if displaying calendar details
        $teaser = html_entity_decode($event->teaser, ENT_QUOTES, CONTREXX_CHARSET);
        if ($teaser) {
            return contrexx_raw2xhtml(contrexx_strip_tags($teaser));
        }

        $description = html_entity_decode($event->description, ENT_QUOTES, CONTREXX_CHARSET);
        return contrexx_raw2xhtml(contrexx_strip_tags($description));
    }
}
