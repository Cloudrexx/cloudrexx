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

namespace Cx\Core\DateTime\Controller;

/**
 * ComponentControlle for DateTime component
 * This component handles timezones and provides methods for conversion
 *
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @package     contrexx
 * @subpackage  core_datetime
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {

    /**
     * @var \DateTimeZone Database timezone
     */
    protected $databaseTimezone;

    /**
     * @var \DateTimeZone Internal timezone
     */
    protected $internalTimezone;

    /**
     * Returns the controller class names for this component
     * @return array List of controller names
     */
    public function getControllerClasses() {
        return array();
    }

    /**
     * Sets the user's and the database timezone
     * Please note that there's also the user's timezone. Since the user could
     * change (login/logout) during the request, we get it on demand.
     */
    public function postComponentLoad() {
        global $_CONFIG;

        $databaseTimezoneString = $this->cx->getDb()->getDb()->getTimezone();
        $this->databaseTimezone = new \DateTimeZone($databaseTimezoneString);

        $internalTimezoneString = $_CONFIG['timezone'];
        $this->internalTimezone = new \DateTimeZone($internalTimezoneString);
    }

    /**
     * Converts a \DateTime object in DB timezone to internal timezone
     * @param \DateTime $datetime DateTime in database timezone
     * @return \DateTime DateTime in internal timezone
     */
    public function db2intern(\DateTime $datetime) {
        return $datetime->setTimezone($this->internalTimezone);
    }

    /**
     * Converts a \DateTime object in internal timezone to a user's timezone
     * @param \DateTime $datetime DateTime in internal timezone
     * @param \User $user (optional) User object to get timezone of
     * @return \DateTime DateTime in user's timezone
     */
    public function intern2user(\DateTime $datetime, $user = null) {
        $userTimezone = \FWUser::getFWUserObject()->objUser->getTimezone();
        if ($user) {
            $userTimezone = $user->getTimezone();
        }
        return $datetime->setTimezone($userTimezone);
    }

    /**
     * Converts a \DateTime object in user's timezone to internal timezone
     * @param \DateTime $datetime DateTime in user's timezone
     * @return \DateTime DateTime in internal timezone
     */
    public function user2intern(\DateTime $datetime) {
        return $datetime->setTimezone($this->internalTimezone);
    }

    /**
     * Converts a \DateTime object in internal timezone to DB timezone
     * @param \DateTime $datetime DateTime in internal timezone
     * @return \DateTime DateTime in DB timezone
     */
    public function intern2db(\DateTime $datetime) {
        return $datetime->setTimezone($this->databaseTimezone);
    }

    /**
     * Converts a \DateTime object in DB timezone to a user's timezone
     * @param \DateTime $datetime DateTime in database timezone
     * @param \User $user (optional) User object to get timezone of
     * @return \DateTime DateTime in user's timezone
     */
    public function db2user(\DateTime $datetime, $user = null) {
        return $this->intern2user($this->db2intern($datetime), $user);
    }

    /**
     * Converts a \DateTime object in a user's timezone to DB timezone
     * @param \DateTime $datetime DateTime in user's timezone
     * @return \DateTime DateTime in database timezone
     */
    public function user2db(\DateTime $datetime) {
        return $this->intern2db($this->user2intern($datetime));
    }

    /**
     * Returns a \DateTime object in a user's timezone
     * @param string A date/time string. Argument for \DateTime::construct()
     * @param \User $user (optional) User object to get timezone of
     * @return \DateTime DateTime object in user's timezone
     */
    public function createDateTimeForUser($time, $user = null) {
        $userTimezone = \FWUser::getFWUserObject()->objUser->getTimezone();
        if ($user) {
            $userTimezone = $user->getTimezone();
        }
        return new \DateTime($time, $userTimezone);
    }

    /**
     * Returns a \DateTime object in DB timezone
     * @param string A date/time string. Argument for \DateTime::construct()
     * @return \DateTime DateTime object in DB timezone
     */
    public function createDateTimeForDb($time) {
        return new \DateTime($time, $this->databaseTimezone);
    }
}
