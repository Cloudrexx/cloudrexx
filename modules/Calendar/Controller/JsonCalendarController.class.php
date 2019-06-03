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
 * JSON Adapter for Calendar module
 * @copyright   Cloudrexx AG
 * @author      ss4u <ss4ugroup@gmail.com>
 * @author      Thomas Wirz <thomas.wirz@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_calendar
 */

namespace Cx\Modules\Calendar\Controller;

/**
 * JSON Adapter for Calendar module
 * @copyright   Cloudrexx AG
 * @author      Thomas Wirz <thomas.wirz@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_calendar
 */
class JsonCalendarController extends \Cx\Core\Core\Model\Entity\Controller implements \Cx\Core\Json\JsonAdapter {
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
        return 'Calendar';
    }

    /**
     * Returns an array of method names accessable from a JSON request
     * @return array List of method names
     */
    public function getAccessableMethods() {
        return array('getExeceptionDates', 'getRecipientCount');
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
        return new \Cx\Core_Modules\Access\Model\Entity\Permission(
            array(), // no specific protocol forced
            array('get'), // only GET required
            true, // requires login
            array(), // no specific user group
            array(180) // event management
        );
    }

    /**
     * Returns all series dates from the given post data
     *
     * @return array Array of dates
     */
    public function getExeceptionDates() {
        $calendarLib = new \Cx\Modules\Calendar\Controller\CalendarLibrary();
        return $calendarLib->getExeceptionDates();
    }

    /**
     * Get the count of the selected recipients of a event invitation
     *
     * @param   array   $params     List of get and post parameters which were
     *                              sent to the json adapter.
     *
     * @return  integer            The count of the recipients
     */
    public function getRecipientCount($params = array())
    {
        global $_ARRAYLANG;

        $event = new \Cx\Modules\Calendar\Controller\CalendarEvent();
        if (intval($params['get']['id']) != 0) {
            $event->get(
                intval($params['get']['id']),
                null,
                intval($params['get']['lang_id'])
            );

            if (empty($event->id)) {
                return 0;
            }
        }

        $_ARRAYLANG = \Env::get('init')->getComponentSpecificLanguageData(
            $this->getName(),
            false
        );

        // load current lists of invited people directly from the get params,
        // because they are not necessarily stored in the database yet
        // 1.) load access users
        if (
            isset($params['get']['selectedGroups']) &&
            is_array($params['get']['selectedGroups'])
        ) {
            $event->invitedGroups = $params['get']['selectedGroups'];
        }

        // 2.) load crm users
        $invited = $params['get']['invite_crm_memberships'];
        $excluded = $params['get']['excluded_crm_memberships'];
        $event->invitedCrmGroups = is_array($invited) ? contrexx_input2int($invited) : array();
        $event->excludedCrmGroups = is_array($excluded) ? contrexx_input2int($excluded) : array();

        // 3.) load emails which were entered manually
        $event->invitedMails = $params['get']['invitedMails'];

        // get the send to filter
        $sendInvitationTo = $params['get']['sendMailTo'];
        $calendarManager = new \Cx\Modules\Calendar\Controller\CalendarMailManager();
        $recipientsCount = $calendarManager->getSendMailRecipientsCount(
            \Cx\Modules\Calendar\Controller\CalendarMailManager::MAIL_INVITATION,
            $event,
            0,
            null,
            $sendInvitationTo
        );
        return $recipientsCount;
    }
}
