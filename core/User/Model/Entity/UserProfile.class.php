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


namespace Cx\Core\User\Model\Entity;

/**
 * Cx\Core\User\Model\Entity\UserProfile
 */
class UserProfile extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer $userId
     */
    private $userId;

    /**
     * @var string $gender
     */
    private $gender;

    /**
     * @var mixed
     */
    protected $designation;

    /**
     * @var string $firstname
     */
    private $firstname;

    /**
     * @var string $lastname
     */
    private $lastname;

    /**
     * @var string $company
     */
    private $company;

    /**
     * @var string $address
     */
    private $address;

    /**
     * @var string $city
     */
    private $city;

    /**
     * @var string $zip
     */
    private $zip;

    /**
     * @var integer $country
     */
    private $country;

    /**
     * @var string $phoneOffice
     */
    private $phoneOffice;

    /**
     * @var string $phonePrivate
     */
    private $phonePrivate;

    /**
     * @var string $phoneMobile
     */
    private $phoneMobile;

    /**
     * @var string $phoneFax
     */
    private $phoneFax;

    /**
     * @var string $birthday
     */
    private $birthday;

    /**
     * @var string $website
     */
    private $website;

    /**
     * @var string $profession
     */
    private $profession;

    /**
     * @var string $interests
     */
    private $interests;

    /**
     * @var string $signature
     */
    private $signature;

    /**
     * @var string $picture
     */
    private $picture;

    /**
     * @var Cx\Core\User\Model\Entity\User
     */
    private $users;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $userTitle;

    /**
     * @var \Cx\Core\User\Model\Entity\ProfileTitle
     */
    protected $userAttributeValues;

    public function __construct()
    {
        $this->userAttributeValues = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set gender
     *
     * @param string $gender
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
    }

    /**
     * Get gender
     *
     * @return string $gender
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set designation
     *
     * @param string $designation
     */
    public function setDesignation($designation)
    {
        $this->designation = $designation;
    }

    /**
     * Get designation
     *
     * @return string $designation
     */
    public function getDesignation()
    {
        return $this->designation;
    }

    /**
     * Set firstname
     *
     * @param string $firstname
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    }

    /**
     * Get firstname
     *
     * @return string $firstname
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    }

    /**
     * Get lastname
     *
     * @return string $lastname
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set company
     *
     * @param string $company
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }

    /**
     * Get company
     *
     * @return string $company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Set address
     *
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * Get address
     *
     * @return string $address
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set city
     *
     * @param string $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * Get city
     *
     * @return string $city
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set zip
     *
     * @param string $zip
     */
    public function setZip($zip)
    {
        $this->zip = $zip;
    }

    /**
     * Get zip
     *
     * @return string $zip
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * Set country
     *
     * @param integer $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * Get country
     *
     * @return integer $country
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set phoneOffice
     *
     * @param string $phoneOffice
     */
    public function setPhoneOffice($phoneOffice)
    {
        $this->phoneOffice = $phoneOffice;
    }

    /**
     * Get phoneOffice
     *
     * @return string $phoneOffice
     */
    public function getPhoneOffice()
    {
        return $this->phoneOffice;
    }

    /**
     * Set phonePrivate
     *
     * @param string $phonePrivate
     */
    public function setPhonePrivate($phonePrivate)
    {
        $this->phonePrivate = $phonePrivate;
    }

    /**
     * Get phonePrivate
     *
     * @return string $phonePrivate
     */
    public function getPhonePrivate()
    {
        return $this->phonePrivate;
    }

    /**
     * Set phoneMobile
     *
     * @param string $phoneMobile
     */
    public function setPhoneMobile($phoneMobile)
    {
        $this->phoneMobile = $phoneMobile;
    }

    /**
     * Get phoneMobile
     *
     * @return string $phoneMobile
     */
    public function getPhoneMobile()
    {
        return $this->phoneMobile;
    }

    /**
     * Set phoneFax
     *
     * @param string $phoneFax
     */
    public function setPhoneFax($phoneFax)
    {
        $this->phoneFax = $phoneFax;
    }

    /**
     * Get phoneFax
     *
     * @return string $phoneFax
     */
    public function getPhoneFax()
    {
        return $this->phoneFax;
    }

    /**
     * Set birthday
     *
     * @param string $birthday
     */
    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;
    }

    /**
     * Get birthday
     *
     * @return string $birthday
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * Set website
     *
     * @param string $website
     */
    public function setWebsite($website)
    {
        $this->website = $website;
    }

    /**
     * Get website
     *
     * @return string $website
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Set profession
     *
     * @param string $profession
     */
    public function setProfession($profession)
    {
        $this->profession = $profession;
    }

    /**
     * Get profession
     *
     * @return string $profession
     */
    public function getProfession()
    {
        return $this->profession;
    }

    /**
     * Set interests
     *
     * @param string $interests
     */
    public function setInterests($interests)
    {
        $this->interests = $interests;
    }

    /**
     * Get interests
     *
     * @return string $interests
     */
    public function getInterests()
    {
        return $this->interests;
    }

    /**
     * Set signature
     *
     * @param string $signature
     */
    public function setSignature($signature)
    {
        $this->signature = $signature;
    }

    /**
     * Get signature
     *
     * @return string $signature
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * Set picture
     *
     * @param string $picture
     */
    public function setPicture($picture)
    {
        $this->picture = $picture;
    }

    /**
     * Get picture
     *
     * @return string $picture
     */
    public function getPicture()
    {
        return $this->picture;
    }

    /**
     * Set users
     *
     * @param \Cx\Core\User\Model\Entity\User $users
     */
    public function setUsers(\Cx\Core\User\Model\Entity\User $users)
    {
        $this->users = $users;
    }

    /**
     * Get users
     *
     * @return \Cx\Core\User\Model\Entity\User $users
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Set userTitle
     *
     * @param \Cx\Core\User\Model\Entity\ProfileTitle $userTitle
     */
    public function setUserTitle(\Cx\Core\User\Model\Entity\ProfileTitle $userTitle)
    {
        $this->userTitle = $userTitle;
    }

    /**
     * Get userTitle
     *
     * @return \Cx\Core\User\Model\Entity\ProfileTitle $userTitle
     */
    public function getUserTitle()
    {
        return $this->userTitle;
    }

    /**
     * Add userAttributeValues
     *
     * @param \Cx\Core\User\Model\Entity\UserAttributeValue $userAttributeValues
     * @return UserProfile
     */
    public function addUserAttributeValue(\Cx\Core\User\Model\Entity\UserAttributeValue $userAttributeValues)
    {
        $this->userAttributeValues[] = $userAttributeValues;

        return $this;
    }

    /**
     * Remove userAttributeValues
     *
     * @param \Cx\Core\User\Model\Entity\UserAttributeValue $userAttributeValues
     */
    public function removeUserAttributeValue(\Cx\Core\User\Model\Entity\UserAttributeValue $userAttributeValues)
    {
        $this->userAttributeValues->removeElement($userAttributeValues);
    }

    /**
     * Add userAttributeValues
     *
     * @param Cx\Core\User\Model\Entity\UserAttributeValue $userAttributeValue
     */
    public function addUserAttributeValues(\Cx\Core\User\Model\Entity\UserAttributeValue $userAttributeValue)
    {
        $this->userAttributeValues[] = $userAttributeValue;
    }

    /**
     * Get userAttributeValue
     *
     * @return Doctrine\Common\Collections\Collection $userAttributeValues
     */
    public function getUserAttributeValues()
    {
        return $this->userAttributeValues;
    }
}
