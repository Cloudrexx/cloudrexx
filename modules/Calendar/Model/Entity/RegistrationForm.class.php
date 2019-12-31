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
 * RegistrationForm
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_calendar
*/
namespace Cx\Modules\Calendar\Model\Entity;

/**
 * RegistrationForm
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_calendar
*/
class RegistrationForm extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var integer $status
     */
    protected $status;

    /**
     * @var integer $order
     */
    protected $order;

    /**
     * @var string $title
     */
    protected $title;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $events;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $registrationFormFields;

    public function __construct()
    {
        $this->status = 0;
        $this->order  = 99;
        $this->events = new \Doctrine\Common\Collections\ArrayCollection();
        $this->registrationFormFields = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set status
     *
     * @param integer $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Get status
     *
     * @return integer $status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set order
     *
     * @param integer $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * Get order
     *
     * @return integer $order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get title
     *
     * @return string $title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Add event
     *
     * @param \Cx\Modules\Calendar\Model\Entity\Event $event
     */
    public function addEvent(\Cx\Modules\Calendar\Model\Entity\Event $event)
    {
        $this->events[] = $event;
    }

    /**
     * Remove events
     *
     * @param \Cx\Modules\Calendar\Model\Entity\Event $events
     */
    public function removeEvent(\Cx\Modules\Calendar\Model\Entity\Event $events)
    {
        $this->events->removeElement($events);
    }

    /**
     * set events
     *
     * @param \Doctrine\Common\Collections\Collection $events
     */
    public function setEvents($events)
    {
        $this->events = $events;
    }

    /**
     * Get events
     *
     * @return \Doctrine\Common\Collections\Collection $events
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * Add registrationFormField
     *
     * @param \Cx\Modules\Calendar\Model\Entity\RegistrationFormField $registrationFormField
     */
    public function addRegistrationFormField(\Cx\Modules\Calendar\Model\Entity\RegistrationFormField $registrationFormField)
    {
        $this->registrationFormFields[] = $registrationFormField;
    }

    /**
     * Remove registrationFormFields
     *
     * @param \Cx\Modules\Calendar\Model\Entity\RegistrationFormField $registrationFormFields
     */
    public function removeRegistrationFormField(\Cx\Modules\Calendar\Model\Entity\RegistrationFormField $registrationFormFields)
    {
        $this->registrationFormFields->removeElement($registrationFormFields);
    }

    /**
     * Get RegistrationFormFieldById
     *
     * @param integer $id id
     *
     * @return null|\Cx\Modules\Calendar\Model\Entity\RegistrationFormField
     */
    public function getRegistrationFormFieldById($id)
    {
        if (!$id) {
            return null;
        }

        foreach ($this->registrationFormFields as $formField) {
            if ($formField->getId() == $id) {
                return $formField;
            }
        }
        return null;
    }

    /**
     * Set RegistrationFormFields
     *
     * @param \Doctrine\Common\Collections\Collection $registrationFormFields
     */
    public function setRegistrationFormFields($registrationFormFields) {
        $this->registrationFormFields = $registrationFormFields;
    }

    /**
     * Get registrationFormFields
     *
     * @return \Doctrine\Common\Collections\Collection $registrationFormFields
     */
    public function getRegistrationFormFields()
    {
        return $this->registrationFormFields;
    }
}