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
 * RegistrationFormFieldValue
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_calendar
*/
namespace Cx\Modules\Calendar\Model\Entity;

/**
 * RegistrationFormFieldValue
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_calendar
*/
class RegistrationFormFieldValue extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer $regId
     */
    protected $regId;

    /**
     * @var integer $fieldId
     */
    protected $fieldId;

    /**
     * @var text $value
     */
    protected $value;

    /**
     * @var \Cx\Modules\Calendar\Model\Entity\Registration
     */
    protected $registration;

    /**
     * @var \Cx\Modules\Calendar\Model\Entity\RegistrationFormField
     */
    protected $registrationFormField;


    /**
     * Set regId
     *
     * @param integer $regId
     */
    public function setRegId($regId)
    {
        $this->regId = $regId;
    }

    /**
     * Get regId
     *
     * @return integer $regId
     */
    public function getRegId()
    {
        return $this->regId;
    }

    /**
     * Set fieldId
     *
     * @param integer $fieldId
     */
    public function setFieldId($fieldId)
    {
        $this->fieldId = $fieldId;
    }

    /**
     * Get fieldId
     *
     * @return integer $fieldId
     */
    public function getFieldId()
    {
        return $this->fieldId;
    }

    /**
     * Set value
     *
     * @param text $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Get value
     *
     * @return text $value
     */
    public function getValue()
    {
        return $this->value;
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
     * Set registrationFormField
     *
     * @param \Cx\Modules\Calendar\Model\Entity\RegistrationFormField $registrationFormField
     */
    public function setRegistrationFormField(\Cx\Modules\Calendar\Model\Entity\RegistrationFormField $registrationFormField)
    {
        $this->registrationFormField = $registrationFormField;
    }

    /**
     * Get registrationFormField
     *
     * @return \Cx\Modules\Calendar\Model\Entity\RegistrationFormField $registrationFormField
     */
    public function getRegistrationFormField()
    {
        return $this->registrationFormField;
    }
}
