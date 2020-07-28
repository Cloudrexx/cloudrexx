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
 * Invite
 *
 * @copyright   Cloudrexx AG
 * @author      Thomas Däppen <thomas.daeppen@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_calendar
*/
namespace Cx\Modules\Calendar\Model\Entity;

/**
 * Invite
 *
 * @copyright   Cloudrexx AG
 * @author      Thomas Däppen <thomas.daeppen@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_calendar
*/
class Invite extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var integer $timestamp
     */
    protected $timestamp;

    /**
     * @var string $inviteeType
     */
    protected $inviteeType;

    /**
     * @var integer $inviteeId
     */
    protected $inviteeId;

    /**
     * @var string aemail$
     */
    protected $email;

    /**
     * @var string token$
     */
    protected $token;

    /**
     * @var \Cx\Modules\Calendar\Model\Entity\Registration
     */
    protected $registration;

    /**
     * @var \Cx\Modules\Calendar\Model\Entity\Event
     */
    protected $event;

    const HTTP_REQUEST_PARAM_ID = 'i';
    const HTTP_REQUEST_PARAM_TOKEN = 't';
    const HTTP_REQUEST_PARAM_EVENT = 'id';
    const HTTP_REQUEST_PARAM_DATE = 'date';
    const HTTP_REQUEST_PARAM_RANDOM = 'r';

    public function __construct() {
        $this->inviteeType = \Cx\Modules\Calendar\Controller\MailRecipient::RECIPIENT_TYPE_MAIL;
        $this->inviteeId = 0;
    }

    /**
     * Set id
     *
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set timestamp
     *
     * @param integer $timestamp
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * Get timestamp
     *
     * @return integer $timestamp
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Set date
     *
     * @param DateTime $dateTime
     */
    public function setDate($dateTime)
    {
        $dateTimeInDbTimezone = clone($dateTime);
        $this->getComponent('DateTime')->intern2db($dateTimeInDbTimezone);
        $this->timestamp = $dateTimeInDbTimezone->getTimestamp();
    }

    /**
     * Get date
     *
     * @return DateTime $date
     */
    public function getDate()
    {
        $dateTime = new \DateTime();
        $dateTime->setTimestamp($this->timestamp);
        return $dateTime;
    }

    /**
     * Set invitee type
     *
     * @param string $inviteeType
     */
    public function setInviteeType($inviteeType)
    {
        $this->inviteeType = $inviteeType;
    }

    /**
     * Get invitee type
     *
     * @return string $inviteeType
     */
    public function getInviteeType()
    {
        return $this->inviteeType;
    }

    /**
     * Set invitee id
     *
     * @param integer $inviteeId
     */
    public function setInviteeId($inviteeId)
    {
        $this->inviteeId = $inviteeId;
    }

    /**
     * Get invitee id
     *
     * @return integer $inviteeId
     */
    public function getInviteeId()
    {
        return $this->inviteeId;
    }

    /**
     * Set email
     *
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Get email
     *
     * @return string $email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set token
     *
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * Get token
     *
     * @return string $token
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set registration
     *
     * @param \Cx\Modules\Calendar\Model\Entity\Registration $registration
     */
    public function setRegistration(\Cx\Modules\Calendar\Model\Entity\Registration $registration)
    {
        $this->registration = $registration;
    }

    /**
     * Get registration
     *
     * @return \Cx\Modules\Calendar\Model\Entity\Registration $registration
     */
    public function getRegistration()
    {
        return $this->registration;
    }

    /**
     * Set event
     *
     * @param \Cx\Modules\Calendar\Model\Entity\Event $event
     */
    public function setEvent(\Cx\Modules\Calendar\Model\Entity\Event $event)
    {
        $this->event = $event;
    }

    /**
     * Get event
     *
     * @return \Cx\Modules\Calendar\Model\Entity\Event $event
     */
    public function getEvent()
    {
        return $this->event;
    }
}
