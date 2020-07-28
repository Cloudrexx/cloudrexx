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
 * Registration
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_calendar
*/
namespace Cx\Modules\Calendar\Model\Entity;

/**
 * Registration
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_calendar
*/
class Registration extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var integer $date
     */
    protected $date;

    /**
     * @var dateTime $submissionDate
     */
    protected $submissionDate;

    /**
     * @var integer $type
     */
    protected $type;

    /**
     * @var \Cx\Modules\Calendar\Model\Entity\Invite
     */
    protected $invite;

    /**
     * @var integer $userId
     */
    protected $userId;

    /**
     * @var integer $langId
     */
    protected $langId;

    /**
     * @var integer $export
     */
    protected $export;

    /**
     * @var integer $paymentMethod
     */
    protected $paymentMethod;

    /**
     * @var integer $paid
     */
    protected $paid;

    /**
     * @var \Cx\Modules\Calendar\Model\Entity\RegistrationFormFieldValue
     */
    protected $registrationFormFieldValues;

    /**
     * @var \Cx\Modules\Calendar\Model\Entity\Event
     */
    protected $event;

    public function __construct()
    {
        $this->registrationFormFieldValues = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set date
     *
     * @param integer $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * Get date
     *
     * @return integer $date
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set submissionDate
     *
     * @param dateTime $submissionDate
     */
    public function setSubmissionDate($submissionDate)
    {
        $this->submissionDate = $submissionDate;
    }

    /**
     * Get submissionDate
     *
     * @return dateTime $submissionDate
     */
    public function getSubmissionDate()
    {
        return $this->submissionDate;
    }

    /**
     * Set type
     *
     * @param integer $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get type
     *
     * @return integer $type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set invite
     *
     * @param \Cx\Modules\Calendar\Model\Entity\Invite $invite
     */
    public function setInvite($invite)
    {
        $this->invite = $invite;
    }

    /**
     * Get invite
     *
     * @return \Cx\Modules\Calendar\Model\Entity\Invite $invite
     */
    public function getInvite()
    {
        return $this->invite;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * Get userId
     *
     * @return integer $userId
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set langId
     *
     * @param integer $langId
     */
    public function setLangId($langId)
    {
        $this->langId = $langId;
    }

    /**
     * Get langId
     *
     * @return integer $langId
     */
    public function getLangId()
    {
        return $this->langId;
    }

    /**
     * Set export
     *
     * @param integer $export
     */
    public function setExport($export)
    {
        $this->export = $export;
    }

    /**
     * Get export
     *
     * @return integer $export
     */
    public function getExport()
    {
        return $this->export;
    }

    /**
     * Set paymentMethod
     *
     * @param integer $paymentMethod
     */
    public function setPaymentMethod($paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * Get paymentMethod
     *
     * @return integer $paymentMethod
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * Set paid
     *
     * @param integer $paid
     */
    public function setPaid($paid)
    {
        $this->paid = $paid;
    }

    /**
     * Get paid
     *
     * @return integer $paid
     */
    public function getPaid()
    {
        return $this->paid;
    }

    /**
     * Add registrationFormFieldValues
     *
     * @param \Cx\Modules\Calendar\Model\Entity\RegistrationFormFieldValue $registrationFormFieldValue
     */
    public function addRegistrationFormFieldValue(\Cx\Modules\Calendar\Model\Entity\RegistrationFormFieldValue $registrationFormFieldValue)
    {
        $this->registrationFormFieldValues[] = $registrationFormFieldValue;
    }

    /**
     * Remove registrationFormFieldValues
     *
     * @param \Cx\Modules\Calendar\Model\Entity\RegistrationFormFieldValue $registrationFormFieldValues
     */
    public function removeRegistrationFormFieldValue(\Cx\Modules\Calendar\Model\Entity\RegistrationFormFieldValue $registrationFormFieldValues)
    {
        $this->registrationFormFieldValues->removeElement($registrationFormFieldValues);
    }

    /**
     * set $registrationFormFieldValues
     *
     * @param type $registrationFormFieldValues
     */
    public function setRegistrationFormFieldValues($registrationFormFieldValues) {
        $this->registrationFormFieldValues = $registrationFormFieldValues;
    }

    /**
     * Get RegistrationFormFieldValueByFieldId
     *
     * @param integer $fieldId field id
     *
     * @return null|\Cx\Modules\Calendar\Model\Entity\RegistrationFormFieldValue
     */
    public function getRegistrationFormFieldValueByFieldId($fieldId)
    {
        if (!$fieldId) {
            return null;
        }

        foreach ($this->registrationFormFieldValues as $formFieldValue) {
            $formField = $formFieldValue->getRegistrationFormField();
            if ($formField && ($formField->getId() == $fieldId)) {
                return $formFieldValue;
            }
        }
        return null;
    }

    /**
     * Get registrationFormFieldValues
     *
     * @return \Doctrine\Common\Collections\Collection $registrationFormFieldValues
     */
    public function getRegistrationFormFieldValues()
    {
        return $this->registrationFormFieldValues;
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
