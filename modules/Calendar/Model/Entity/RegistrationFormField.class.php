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
 * RegistrationFormField
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_calendar
*/
namespace Cx\Modules\Calendar\Model\Entity;

/**
 * RegistrationFormField
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_calendar
*/
class RegistrationFormField extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var string $type
     */
    protected $type;

    /**
     * @var integer $required
     */
    protected $required;

    /**
     * @var integer $order
     */
    protected $order;

    /**
     * @var string $affiliation
     */
    protected $affiliation;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $registrationFormFieldNames;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $registrationFormFieldValues;

    /**
     * @var \Cx\Modules\Calendar\Model\Entity\RegistrationForm
     */
    protected $registrationForm;

    public function __construct()
    {
        $this->registrationFormFieldNames = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set type
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get type
     *
     * @return string $type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set required
     *
     * @param integer $required
     */
    public function setRequired($required)
    {
        $this->required = $required;
    }

    /**
     * Get required
     *
     * @return integer $required
     */
    public function getRequired()
    {
        return $this->required;
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
     * Set affiliation
     *
     * @param string $affiliation
     */
    public function setAffiliation($affiliation)
    {
        $this->affiliation = $affiliation;
    }

    /**
     * Get affiliation
     *
     * @return string $affiliation
     */
    public function getAffiliation()
    {
        return $this->affiliation;
    }

    /**
     * Add registrationFormFieldName
     *
     * @param \Cx\Modules\Calendar\Model\Entity\RegistrationFormFieldName $registrationFormFieldName
     */
    public function addRegistrationFormFieldName(\Cx\Modules\Calendar\Model\Entity\RegistrationFormFieldName $registrationFormFieldName)
    {
        $this->registrationFormFieldNames[] = $registrationFormFieldName;
    }

    /**
     * Remove registrationFormFieldNames
     *
     * @param \Cx\Modules\Calendar\Model\Entity\RegistrationFormFieldName $registrationFormFieldNames
     */
    public function removeRegistrationFormFieldName(\Cx\Modules\Calendar\Model\Entity\RegistrationFormFieldName $registrationFormFieldNames)
    {
        $this->registrationFormFieldNames->removeElement($registrationFormFieldNames);
    }

    /**
     * Set registrationFormFieldNames
     *
     * @param \Doctrine\Common\Collections\ArrayCollection $registrationFormFieldNames
     */
    public function setRegistrationFormFieldNames($registrationFormFieldNames)
    {
        $this->registrationFormFieldNames = $registrationFormFieldNames;
    }

    /**
     * Get registrationFormFieldNames
     *
     * @return \Doctrine\Common\Collections\Collection $registrationFormFieldNames
     */
    public function getRegistrationFormFieldNames()
    {
        return $this->registrationFormFieldNames;
    }

    /**
     * Get registrationFormFieldNames by langId
     *
     * @param integer $langId lang id
     *
     * @return null|\Cx\Modules\Calendar\Model\Entity\RegistrationFormFieldName
     */
    public function getRegistrationFormFieldNamesByLangId($langId)
    {
        if (!$langId) {
            return null;
        }

        foreach ($this->registrationFormFieldNames as $formFieldName) {
            if ($formFieldName->getLangId() == $langId) {
                return $formFieldName;
            }
        }

        return null;
    }

    /**
     * Add registrationFormFieldValue
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
     * Set registrationFormFieldValues
     *
     * @param \Doctrine\Common\Collections\Collection $registrationFormFieldValues
     */
    public function setRegistrationFormFieldValues($registrationFormFieldValues)
    {
        $this->registrationFormFieldValues = $registrationFormFieldValues;
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
     * Set registrationForm
     *
     * @param \Cx\Modules\Calendar\Model\Entity\RegistrationForm $registrationForm
     */
    public function setRegistrationForm(\Cx\Modules\Calendar\Model\Entity\RegistrationForm $registrationForm)
    {
        $this->registrationForm = $registrationForm;
    }

    /**
     * Get registrationForm
     *
     * @return \Cx\Modules\Calendar\Model\Entity\RegistrationForm $registrationForm
     */
    public function getRegistrationForm()
    {
        return $this->registrationForm;
    }
}