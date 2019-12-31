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
 * Event
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_calendar
*/
namespace Cx\Modules\Calendar\Model\Entity;

/**
 * Event
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_calendar
*/
class Event extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var integer $type
     */
    protected $type;

    /**
     * @var datetime $startDate
     */
    protected $startDate;

    /**
     * @var datetime $endDate
     */
    protected $endDate;

    /**
     * @var boolean $useCustomDateDisplay
     */
    protected $useCustomDateDisplay;

    /**
     * @var integer $showStartDateList
     */
    protected $showStartDateList;

    /**
     * @var integer $showEndDateList
     */
    protected $showEndDateList;

    /**
     * @var integer $showStartTimeList
     */
    protected $showStartTimeList;

    /**
     * @var integer $showEndTimeList
     */
    protected $showEndTimeList;

    /**
     * @var integer $showTimeTypeList
     */
    protected $showTimeTypeList;

    /**
     * @var integer $showStartDateDetail
     */
    protected $showStartDateDetail;

    /**
     * @var integer $showEndDateDetail
     */
    protected $showEndDateDetail;

    /**
     * @var integer $showStartTimeDetail
     */
    protected $showStartTimeDetail;

    /**
     * @var integer $showEndTimeDetail
     */
    protected $showEndTimeDetail;

    /**
     * @var integer $showTimeTypeDetail
     */
    protected $showTimeTypeDetail;

    /**
     * @var integer $google
     */
    protected $google;

    /**
     * @var integer $access
     */
    protected $access;

    /**
     * @var integer $priority
     */
    protected $priority;

    /**
     * @var integer $price
     */
    protected $price;

    /**
     * @var string $link
     */
    protected $link;

    /**
     * @var string $pic
     */
    protected $pic;

    /**
     * @var string $attach
     */
    protected $attach;

    /**
     * @var integer $placeMediadirId
     */
    protected $placeMediadirId;

    /**
     * @var string $showIn
     */
    protected $showIn;

    /**
     * @var string $invitedGroups
     */
    protected $invitedGroups;

    /**
     * @var string $invitedCrmGroups
     */
    protected $invitedCrmGroups;

    /**
     * @var string $excludedCrmGroups
     */
    protected $excludedCrmGroups;

    /**
     * @var text $invitedMails
     */
    protected $invitedMails;

    /**
     * @var integer $invitationSent
     */
    protected $invitationSent;

    /**
     * @var string $invitationEmailTemplate
     */
    protected $invitationEmailTemplate;

    /**
     * @var integer $registration
     */
    protected $registration;

    /**
     * @var string $registrationNum
     */
    protected $registrationNum;

    /**
     * @var string $registrationNotification
     */
    protected $registrationNotification;

    /**
     * @var string $emailTemplate
     */
    protected $emailTemplate;

    /**
     * @var boolean $ticketSales
     */
    protected $ticketSales;

    /**
     * @var text $numSeating
     */
    protected $numSeating;

    /**
     * @var smallint $seriesStatus
     */
    protected $seriesStatus;

    /**
     * @var integer $independentSeries
     */
    protected $independentSeries;

    /**
     * @var integer $seriesType
     */
    protected $seriesType;

    /**
     * @var integer $seriesPatternCount
     */
    protected $seriesPatternCount;

    /**
     * @var string $seriesPatternWeekday
     */
    protected $seriesPatternWeekday;

    /**
     * @var integer $seriesPatternDay
     */
    protected $seriesPatternDay;

    /**
     * @var integer $seriesPatternWeek
     */
    protected $seriesPatternWeek;

    /**
     * @var integer $seriesPatternMonth
     */
    protected $seriesPatternMonth;

    /**
     * @var integer $seriesPatternType
     */
    protected $seriesPatternType;

    /**
     * @var integer $seriesPatternDouranceType
     */
    protected $seriesPatternDouranceType;

    /**
     * @var integer $seriesPatternEnd
     */
    protected $seriesPatternEnd;

    /**
     * @var datetime $seriesPatternEndDate
     */
    protected $seriesPatternEndDate;

    /**
     * @var integer $seriesPatternBegin
     */
    protected $seriesPatternBegin = 0;

    /**
     * @var text $seriesPatternExceptions
     */
    protected $seriesPatternExceptions;

    /**
     * @var text $seriesAdditionalRecurrences
     */
    protected $seriesAdditionalRecurrences;

    /**
     * @var boolean $status
     */
    protected $status;

    /**
     * @var boolean $confirmed
     */
    protected $confirmed;

    /**
     * @var boolean $showDetailView
     */
    protected $showDetailView;

    /**
     * @var string $author
     */
    protected $author;

    /**
     * @var boolean $allDay
     */
    protected $allDay;

    /**
     * @var boolean $locationType
     */
    protected $locationType;

    /**
     * @var integer $placeId
     */
    protected $placeId;

    /**
     * @var string $placeStreet
     */
    protected $placeStreet;

    /**
     * @var string $placeWebsite
     */
    protected $placeWebsite;

    /**
     * @var string $placeZip
     */
    protected $placeZip;

    /**
     * @var string $placeLink
     */
    protected $placeLink;

    /**
     * @var string $placePhone
     */
    protected $placePhone;

    /**
     * @var string $placeMap
     */
    protected $placeMap;

    /**
     * @var boolean $hostType
     */
    protected $hostType;

    /**
     * @var string $orgName
     */
    protected $orgName;

    /**
     * @var string $orgStreet
     */
    protected $orgStreet;

    /**
     * @var string $orgWebsite
     */
    protected $orgWebsite;

    /**
     * @var string $orgZip
     */
    protected $orgZip;

    /**
     * @var string $orgLink
     */
    protected $orgLink;

    /**
     * @var string $orgPhone
     */
    protected $orgPhone;

    /**
     * @var string $orgEmail
     */
    protected $orgEmail;

    /**
     * @var integer $hostMediadirId
     */
    protected $hostMediadirId;

    /**
     * @var string $registrationExternalLink
     */
    protected $registrationExternalLink;

    /**
     * @var boolean $registrationExternalFullyBooked
     */
    protected $registrationExternalFullyBooked;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $eventFields;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $invite;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $registrations;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $categories;

    /**
     * @var Cx\Modules\Calendar\Model\Entity\RegistrationForm
     */
    protected $registrationForm;

    public function __construct()
    {
        $this->eventFields = new \Doctrine\Common\Collections\ArrayCollection();
        $this->registrations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->categories = new \Doctrine\Common\Collections\ArrayCollection();
        $this->invite = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set startDate
     *
     * @param datetime $startDate
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }

    /**
     * Get startDate
     *
     * @return datetime $startDate
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param datetime $endDate
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
    }

    /**
     * Get endDate
     *
     * @return datetime $endDate
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set useCustomDateDisplay
     *
     * @param boolean $useCustomDateDisplay
     */
    public function setUseCustomDateDisplay($useCustomDateDisplay)
    {
        $this->useCustomDateDisplay = $useCustomDateDisplay;
    }

    /**
     * Get useCustomDateDisplay
     *
     * @return boolean $useCustomDateDisplay
     */
    public function getUseCustomDateDisplay()
    {
        return $this->useCustomDateDisplay;
    }

    /**
     * Set showStartDateList
     *
     * @param integer $showStartDateList
     */
    public function setShowStartDateList($showStartDateList)
    {
        $this->showStartDateList = $showStartDateList;
    }

    /**
     * Get showStartDateList
     *
     * @return integer $showStartDateList
     */
    public function getShowStartDateList()
    {
        return $this->showStartDateList;
    }

    /**
     * Set showEndDateList
     *
     * @param integer $showEndDateList
     */
    public function setShowEndDateList($showEndDateList)
    {
        $this->showEndDateList = $showEndDateList;
    }

    /**
     * Get showEndDateList
     *
     * @return integer $showEndDateList
     */
    public function getShowEndDateList()
    {
        return $this->showEndDateList;
    }

    /**
     * Set showStartTimeList
     *
     * @param integer $showStartTimeList
     */
    public function setShowStartTimeList($showStartTimeList)
    {
        $this->showStartTimeList = $showStartTimeList;
    }

    /**
     * Get showStartTimeList
     *
     * @return integer $showStartTimeList
     */
    public function getShowStartTimeList()
    {
        return $this->showStartTimeList;
    }

    /**
     * Set showEndTimeList
     *
     * @param integer $showEndTimeList
     */
    public function setShowEndTimeList($showEndTimeList)
    {
        $this->showEndTimeList = $showEndTimeList;
    }

    /**
     * Get showEndTimeList
     *
     * @return integer $showEndTimeList
     */
    public function getShowEndTimeList()
    {
        return $this->showEndTimeList;
    }

    /**
     * Set showTimeTypeList
     *
     * @param integer $showTimeTypeList
     */
    public function setShowTimeTypeList($showTimeTypeList)
    {
        $this->showTimeTypeList = $showTimeTypeList;
    }

    /**
     * Get showTimeTypeList
     *
     * @return integer $showTimeTypeList
     */
    public function getShowTimeTypeList()
    {
        return $this->showTimeTypeList;
    }

    /**
     * Set showStartDateDetail
     *
     * @param integer $showStartDateDetail
     */
    public function setShowStartDateDetail($showStartDateDetail)
    {
        $this->showStartDateDetail = $showStartDateDetail;
    }

    /**
     * Get showStartDateDetail
     *
     * @return integer $showStartDateDetail
     */
    public function getShowStartDateDetail()
    {
        return $this->showStartDateDetail;
    }

    /**
     * Set showEndDateDetail
     *
     * @param integer $showEndDateDetail
     */
    public function setShowEndDateDetail($showEndDateDetail)
    {
        $this->showEndDateDetail = $showEndDateDetail;
    }

    /**
     * Get showEndDateDetail
     *
     * @return integer $showEndDateDetail
     */
    public function getShowEndDateDetail()
    {
        return $this->showEndDateDetail;
    }

    /**
     * Set showStartTimeDetail
     *
     * @param integer $showStartTimeDetail
     */
    public function setShowStartTimeDetail($showStartTimeDetail)
    {
        $this->showStartTimeDetail = $showStartTimeDetail;
    }

    /**
     * Get showStartTimeDetail
     *
     * @return integer $showStartTimeDetail
     */
    public function getShowStartTimeDetail()
    {
        return $this->showStartTimeDetail;
    }

    /**
     * Set showEndTimeDetail
     *
     * @param integer $showEndTimeDetail
     */
    public function setShowEndTimeDetail($showEndTimeDetail)
    {
        $this->showEndTimeDetail = $showEndTimeDetail;
    }

    /**
     * Get showEndTimeDetail
     *
     * @return integer $showEndTimeDetail
     */
    public function getShowEndTimeDetail()
    {
        return $this->showEndTimeDetail;
    }

    /**
     * Set showTimeTypeDetail
     *
     * @param integer $showTimeTypeDetail
     */
    public function setShowTimeTypeDetail($showTimeTypeDetail)
    {
        $this->showTimeTypeDetail = $showTimeTypeDetail;
    }

    /**
     * Get showTimeTypeDetail
     *
     * @return integer $showTimeTypeDetail
     */
    public function getShowTimeTypeDetail()
    {
        return $this->showTimeTypeDetail;
    }

    /**
     * Set google
     *
     * @param integer $google
     */
    public function setGoogle($google)
    {
        $this->google = $google;
    }

    /**
     * Get google
     *
     * @return integer $google
     */
    public function getGoogle()
    {
        return $this->google;
    }

    /**
     * Set access
     *
     * @param integer $access
     */
    public function setAccess($access)
    {
        $this->access = $access;
    }

    /**
     * Get access
     *
     * @return integer $access
     */
    public function getAccess()
    {
        return $this->access;
    }

    /**
     * Set priority
     *
     * @param integer $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    /**
     * Get priority
     *
     * @return integer $priority
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set price
     *
     * @param integer $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * Get price
     *
     * @return integer $price
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set link
     *
     * @param string $link
     */
    public function setLink($link)
    {
        $this->link = $link;
    }

    /**
     * Get link
     *
     * @return string $link
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set pic
     *
     * @param string $pic
     */
    public function setPic($pic)
    {
        $this->pic = $pic;
    }

    /**
     * Get pic
     *
     * @return string $pic
     */
    public function getPic()
    {
        return $this->pic;
    }

    /**
     * Set attach
     *
     * @param string $attach
     */
    public function setAttach($attach)
    {
        $this->attach = $attach;
    }

    /**
     * Get attach
     *
     * @return string $attach
     */
    public function getAttach()
    {
        return $this->attach;
    }

    /**
     * Set placeMediadirId
     *
     * @param integer $placeMediadirId
     */
    public function setPlaceMediadirId($placeMediadirId)
    {
        $this->placeMediadirId = $placeMediadirId;
    }

    /**
     * Get placeMediadirId
     *
     * @return integer $placeMediadirId
     */
    public function getPlaceMediadirId()
    {
        return $this->placeMediadirId;
    }

    /**
     * Set showIn
     *
     * @param string $showIn
     */
    public function setShowIn($showIn)
    {
        $this->showIn = $showIn;
    }

    /**
     * Get showIn
     *
     * @return string $showIn
     */
    public function getShowIn()
    {
        return $this->showIn;
    }

    /**
     * Set invitedGroups
     *
     * @param string $invitedGroups
     */
    public function setInvitedGroups($invitedGroups)
    {
        $this->invitedGroups = $invitedGroups;
    }

    /**
     * Get invitedGroups
     *
     * @return string $invitedGroups
     */
    public function getInvitedGroups()
    {
        return $this->invitedGroups;
    }

    /**
     * Set invitedCrmGroups
     *
     * @param string $invitedCrmGroups
     */
    public function setInvitedCrmGroups($invitedCrmGroups)
    {
        $this->invitedCrmGroups = $invitedCrmGroups;
    }

    /**
     * Get invitedCrmGroups
     *
     * @return string $invitedCrmGroups
     */
    public function getInvitedCrmGroups()
    {
        return $this->invitedCrmGroups;
    }

    /**
     * Set excludedCrmGroups
     *
     * @param string $excludedCrmGroups
     */
    public function setExcludedCrmGroups($excludedCrmGroups)
    {
        $this->excludedCrmGroups = $excludedCrmGroups;
    }

    /**
     * Get excludedCrmGroups
     *
     * @return string $excludedCrmGroups
     */
    public function getExcludedCrmGroups()
    {
        return $this->excludedCrmGroups;
    }

    /**
     * Set invitedMails
     *
     * @param text $invitedMails
     */
    public function setInvitedMails($invitedMails)
    {
        $this->invitedMails = $invitedMails;
    }

    /**
     * Get invitedMails
     *
     * @return text $invitedMails
     */
    public function getInvitedMails()
    {
        return $this->invitedMails;
    }

    /**
     * Set invitationSent
     *
     * @param integer $invitationSent
     */
    public function setInvitationSent($invitationSent)
    {
        $this->invitationSent = $invitationSent;
    }

    /**
     * Get invitationSent
     *
     * @return integer $invitationSent
     */
    public function getInvitationSent()
    {
        return $this->invitationSent;
    }

    /**
     * Set invitationEmailTemplate
     *
     * @param string $invitationEmailTemplate
     */
    public function setInvitationEmailTemplate($invitationEmailTemplate)
    {
        $this->invitationEmailTemplate = $invitationEmailTemplate;
    }

    /**
     * Get invitationEmailTemplate
     *
     * @return \Doctrine\Common\Collections\ArrayCollection $invitationEmailTemplate
     */
    public function getInvitationEmailTemplate()
    {
        return $this->getMail($this->invitationEmailTemplate);
    }

    /**
     * Set registration
     *
     * @param integer $registration
     */
    public function setRegistration($registration)
    {
        $this->registration = $registration;
    }

    /**
     * Get registration
     *
     * @return integer $registration
     */
    public function getRegistration()
    {
        return $this->registration;
    }

    /**
     * Set registrationNum
     *
     * @param string $registrationNum
     */
    public function setRegistrationNum($registrationNum)
    {
        $this->registrationNum = $registrationNum;
    }

    /**
     * Get registrationNum
     *
     * @return string $registrationNum
     */
    public function getRegistrationNum()
    {
        return $this->registrationNum;
    }

    /**
     * Set registrationNotification
     *
     * @param string $registrationNotification
     */
    public function setRegistrationNotification($registrationNotification)
    {
        $this->registrationNotification = $registrationNotification;
    }

    /**
     * Get registrationNotification
     *
     * @return string $registrationNotification
     */
    public function getRegistrationNotification()
    {
        return $this->registrationNotification;
    }

    /**
     * Set emailTemplate
     *
     * @param string $emailTemplate
     */
    public function setEmailTemplate($emailTemplate)
    {
        $this->emailTemplate = $emailTemplate;
    }

    /**
     * Get emailTemplate
     *
     * @return \Doctrine\Common\Collections\ArrayCollection $emailTemplate
     */
    public function getEmailTemplate()
    {
        return $this->getMail($this->emailTemplate);
    }

    /**
     * Get mail
     *
     * @param string $mailString
     *
     * @return \Doctrine\Common\Collections\ArrayCollection $emailTemplates
     */
    public function getMail($mailString)
    {
        // return null if $mailString is empty
        if (empty($mailString)) {
            return null;
        }

        $emailTemplate = json_decode($mailString, true);
        if (empty($emailTemplate)) {
            return null;
        }

        $emailTemplates = new \Doctrine\Common\Collections\ArrayCollection();
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $mailRepo = $em->getRepository('Cx\Modules\Calendar\Model\Entity\Mail');
        foreach ($emailTemplate as $langId => $emailTemplateId) {
            $mail = $mailRepo->findOneById($emailTemplateId);
            if ($mail) {
                $mail->setEventLangId($langId);
                $emailTemplates[] = $mail;
            }
        }

        return $emailTemplates;
    }

    /**
     * Set ticketSales
     *
     * @param boolean $ticketSales
     */
    public function setTicketSales($ticketSales)
    {
        $this->ticketSales = $ticketSales;
    }

    /**
     * Get ticketSales
     *
     * @return boolean $ticketSales
     */
    public function getTicketSales()
    {
        return $this->ticketSales;
    }

    /**
     * Set numSeating
     *
     * @param text $numSeating
     */
    public function setNumSeating($numSeating)
    {
        $this->numSeating = $numSeating;
    }

    /**
     * Get numSeating
     *
     * @return text $numSeating
     */
    public function getNumSeating()
    {
        return $this->numSeating;
    }

    /**
     * Set seriesStatus
     *
     * @param smallint $seriesStatus
     */
    public function setSeriesStatus($seriesStatus)
    {
        $this->seriesStatus = $seriesStatus;
    }

    /**
     * Get seriesStatus
     *
     * @return smallint $seriesStatus
     */
    public function getSeriesStatus()
    {
        return $this->seriesStatus;
    }

    /**
     * Set independentSeries
     *
     * @param smallint $independentSeries
     */
    public function setIndependentSeries($independentSeries)
    {
        $this->independentSeries = $independentSeries;
    }

    /**
     * Get independentSeries
     *
     * @return smallint $independentSeries
     */
    public function getIndependentSeries()
    {
        return $this->independentSeries;
    }

    /**
     * Set seriesType
     *
     * @param integer $seriesType
     */
    public function setSeriesType($seriesType)
    {
        $this->seriesType = $seriesType;
    }

    /**
     * Get seriesType
     *
     * @return integer $seriesType
     */
    public function getSeriesType()
    {
        return $this->seriesType;
    }

    /**
     * Set seriesPatternCount
     *
     * @param integer $seriesPatternCount
     */
    public function setSeriesPatternCount($seriesPatternCount)
    {
        $this->seriesPatternCount = $seriesPatternCount;
    }

    /**
     * Get seriesPatternCount
     *
     * @return integer $seriesPatternCount
     */
    public function getSeriesPatternCount()
    {
        return $this->seriesPatternCount;
    }

    /**
     * Set seriesPatternWeekday
     *
     * @param string $seriesPatternWeekday
     */
    public function setSeriesPatternWeekday($seriesPatternWeekday)
    {
        $this->seriesPatternWeekday = $seriesPatternWeekday;
    }

    /**
     * Get seriesPatternWeekday
     *
     * @return string $seriesPatternWeekday
     */
    public function getSeriesPatternWeekday()
    {
        return $this->seriesPatternWeekday;
    }

    /**
     * Set seriesPatternDay
     *
     * @param integer $seriesPatternDay
     */
    public function setSeriesPatternDay($seriesPatternDay)
    {
        $this->seriesPatternDay = $seriesPatternDay;
    }

    /**
     * Get seriesPatternDay
     *
     * @return integer $seriesPatternDay
     */
    public function getSeriesPatternDay()
    {
        return $this->seriesPatternDay;
    }

    /**
     * Set seriesPatternWeek
     *
     * @param integer $seriesPatternWeek
     */
    public function setSeriesPatternWeek($seriesPatternWeek)
    {
        $this->seriesPatternWeek = $seriesPatternWeek;
    }

    /**
     * Get seriesPatternWeek
     *
     * @return integer $seriesPatternWeek
     */
    public function getSeriesPatternWeek()
    {
        return $this->seriesPatternWeek;
    }

    /**
     * Set seriesPatternMonth
     *
     * @param integer $seriesPatternMonth
     */
    public function setSeriesPatternMonth($seriesPatternMonth)
    {
        $this->seriesPatternMonth = $seriesPatternMonth;
    }

    /**
     * Get seriesPatternMonth
     *
     * @return integer $seriesPatternMonth
     */
    public function getSeriesPatternMonth()
    {
        return $this->seriesPatternMonth;
    }

    /**
     * Set seriesPatternType
     *
     * @param integer $seriesPatternType
     */
    public function setSeriesPatternType($seriesPatternType)
    {
        $this->seriesPatternType = $seriesPatternType;
    }

    /**
     * Get seriesPatternType
     *
     * @return integer $seriesPatternType
     */
    public function getSeriesPatternType()
    {
        return $this->seriesPatternType;
    }

    /**
     * Set seriesPatternDouranceType
     *
     * @param integer $seriesPatternDouranceType
     */
    public function setSeriesPatternDouranceType($seriesPatternDouranceType)
    {
        $this->seriesPatternDouranceType = $seriesPatternDouranceType;
    }

    /**
     * Get seriesPatternDouranceType
     *
     * @return integer $seriesPatternDouranceType
     */
    public function getSeriesPatternDouranceType()
    {
        return $this->seriesPatternDouranceType;
    }

    /**
     * Set seriesPatternEnd
     *
     * @param integer $seriesPatternEnd
     */
    public function setSeriesPatternEnd($seriesPatternEnd)
    {
        $this->seriesPatternEnd = $seriesPatternEnd;
    }

    /**
     * Get seriesPatternEnd
     *
     * @return integer $seriesPatternEnd
     */
    public function getSeriesPatternEnd()
    {
        return $this->seriesPatternEnd;
    }

    /**
     * Set seriesPatternEndDate
     *
     * @param datetime $seriesPatternEndDate
     */
    public function setSeriesPatternEndDate($seriesPatternEndDate)
    {
        $this->seriesPatternEndDate = $seriesPatternEndDate;
    }

    /**
     * Get seriesPatternEndDate
     *
     * @return datetime $seriesPatternEndDate
     */
    public function getSeriesPatternEndDate()
    {
        return $this->seriesPatternEndDate;
    }

    /**
     * Set seriesPatternBegin
     *
     * @param integer $seriesPatternBegin
     */
    public function setSeriesPatternBegin($seriesPatternBegin)
    {
        $this->seriesPatternBegin = $seriesPatternBegin;
    }

    /**
     * Get seriesPatternBegin
     *
     * @return integer $seriesPatternBegin
     */
    public function getSeriesPatternBegin()
    {
        return $this->seriesPatternBegin;
    }

    /**
     * Set seriesPatternExceptions
     *
     * @param text $seriesPatternExceptions
     */
    public function setSeriesPatternExceptions($seriesPatternExceptions)
    {
        $this->seriesPatternExceptions = $seriesPatternExceptions;
    }

    /**
     * Get seriesPatternExceptions
     *
     * @return text $seriesPatternExceptions
     */
    public function getSeriesPatternExceptions()
    {
        return $this->seriesPatternExceptions;
    }

    /**
     * Set seriesAdditionalRecurrences
     *
     * @param text $seriesAdditionalRecurrences
     */
    public function setSeriesAdditionalRecurrences($seriesAdditionalRecurrences)
    {
        $this->seriesAdditionalRecurrences = $seriesAdditionalRecurrences;
    }

    /**
     * Get seriesAdditionalRecurrences
     *
     * @return text $seriesAdditionalRecurrences
     */
    public function getSeriesAdditionalRecurrences()
    {
        return $this->seriesAdditionalRecurrences;
    }

    /**
     * Set status
     *
     * @param boolean $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Get status
     *
     * @return boolean $status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set confirmed
     *
     * @param boolean $confirmed
     */
    public function setConfirmed($confirmed)
    {
        $this->confirmed = $confirmed;
    }

    /**
     * Get confirmed
     *
     * @return boolean $confirmed
     */
    public function getConfirmed()
    {
        return $this->confirmed;
    }

    /**
     * Set showDetailView
     *
     * @param boolean $showDetailView
     */
    public function setShowDetailView($showDetailView)
    {
        $this->showDetailView = $showDetailView;
    }

    /**
     * Get showDetailView
     *
     * @return boolean $showDetailView
     */
    public function getShowDetailView()
    {
        return $this->showDetailView;
    }

    /**
     * Set author
     *
     * @param string $author
     */
    public function setAuthor($author)
    {
        $this->author = $author;
    }

    /**
     * Get author
     *
     * @return string $author
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set allDay
     *
     * @param boolean $allDay
     */
    public function setAllDay($allDay)
    {
        $this->allDay = $allDay;
    }

    /**
     * Get allDay
     *
     * @return boolean $allDay
     */
    public function getAllDay()
    {
        return $this->allDay;
    }

    /**
     * Set locationType
     *
     * @param boolean $locationType
     */
    public function setLocationType($locationType)
    {
        $this->locationType = $locationType;
    }

    /**
     * Get locationType
     *
     * @return boolean $locationType
     */
    public function getLocationType()
    {
        return $this->locationType;
    }

    /**
     * Set placeId
     *
     * @param integer $placeId
     */
    public function setPlaceId($placeId)
    {
        $this->placeId = $placeId;
    }

    /**
     * Get placeId
     *
     * @return integer $placeId
     */
    public function getPlaceId()
    {
        return $this->placeId;
    }

    /**
     * Set placeStreet
     *
     * @param string $placeStreet
     */
    public function setPlaceStreet($placeStreet)
    {
        $this->placeStreet = $placeStreet;
    }

    /**
     * Get placeStreet
     *
     * @return string $placeStreet
     */
    public function getPlaceStreet()
    {
        return $this->placeStreet;
    }

    /**
     * Set placeZip
     *
     * @param string $placeZip
     */
    public function setPlaceZip($placeZip)
    {
        $this->placeZip = $placeZip;
    }

    /**
     * Get placeZip
     *
     * @return string $placeZip
     */
    public function getPlaceZip()
    {
        return $this->placeZip;
    }

    /**
     * Set placeWebsite
     *
     * @param string $placeWebsite
     */
    public function setPlaceWebsite($placeWebsite)
    {
        $this->placeWebsite = $placeWebsite;
    }

    /**
     * Get placeWebsite
     *
     * @return string $placeWebsite
     */
    public function getPlaceWebsite()
    {
        return $this->placeWebsite;
    }

    /**
     * Set placeLink
     *
     * @param string $placeLink
     */
    public function setPlaceLink($placeLink)
    {
        $this->placeLink = $placeLink;
    }

    /**
     * Get placeLink
     *
     * @return string $placeLink
     */
    public function getPlaceLink()
    {
        return $this->placeLink;
    }

    /**
     * Get placePhone
     *
     * @return string $placePhone
     */
    public function getPlacePhone()
    {
        return $this->placePhone;
    }

    /**
     * Set placePhone
     *
     * @param string $placePhone
     */
    public function setPlacePhone($placePhone)
    {
        $this->placePhone = $placePhone;
    }

    /**
     * Set placeMap
     *
     * @param string $placeMap
     */
    public function setPlaceMap($placeMap)
    {
        $this->placeMap = $placeMap;
    }

    /**
     * Get placeMap
     *
     * @return string $placeMap
     */
    public function getPlaceMap()
    {
        return $this->placeMap;
    }

    /**
     * Set hostType
     *
     * @param boolean $hostType
     */
    public function setHostType($hostType)
    {
        $this->hostType = $hostType;
    }

    /**
     * Get hostType
     *
     * @return boolean $hostType
     */
    public function getHostType()
    {
        return $this->hostType;
    }

    /**
     * Set orgStreet
     *
     * @param string $orgStreet
     */
    public function setOrgStreet($orgStreet)
    {
        $this->orgStreet = $orgStreet;
    }

    /**
     * Get orgStreet
     *
     * @return string $orgStreet
     */
    public function getOrgStreet()
    {
        return $this->orgStreet;
    }

    /**
     * Set orgZip
     *
     * @param string $orgZip
     */
    public function setOrgZip($orgZip)
    {
        $this->orgZip = $orgZip;
    }

    /**
     * Get orgZip
     *
     * @return string $orgZip
     */
    public function getOrgZip()
    {
        return $this->orgZip;
    }

    /**
     * Set orgWebsite
     *
     * @param string $orgWebsite
     */
    public function setOrgWebsite($orgWebsite)
    {
        $this->orgWebsite = $orgWebsite;
    }

    /**
     * Get orgWebsite
     *
     * @return string $orgWebsite
     */
    public function getOrgWebsite()
    {
        return $this->orgWebsite;
    }

    /**
     * Set orgLink
     *
     * @param string $orgLink
     */
    public function setOrgLink($orgLink)
    {
        $this->orgLink = $orgLink;
    }

    /**
     * Get orgLink
     *
     * @return string $orgLink
     */
    public function getOrgLink()
    {
        return $this->orgLink;
    }

    /**
     * Get orgPhone
     *
     * @return string $orgPhone
     */
    public function getOrgPhone()
    {
        return $this->orgPhone;
    }

    /**
     * Set orgPhone
     *
     * @param string $orgPhone
     */
    public function setOrgPhone($orgPhone)
    {
        $this->orgPhone = $orgPhone;
    }

    /**
     * Set orgEmail
     *
     * @param string $orgEmail
     */
    public function setOrgEmail($orgEmail)
    {
        $this->orgEmail = $orgEmail;
    }

    /**
     * Get orgEmail
     *
     * @return string $orgEmail
     */
    public function getOrgEmail()
    {
        return $this->orgEmail;
    }

    /**
     * Set hostMediadirId
     *
     * @param integer $hostMediadirId
     */
    public function setHostMediadirId($hostMediadirId)
    {
        $this->hostMediadirId = $hostMediadirId;
    }

    /**
     * Get hostMediadirId
     *
     * @return integer $hostMediadirId
     */
    public function getHostMediadirId()
    {
        return $this->hostMediadirId;
    }

    /**
     * Set registrationExternalLink
     *
     * @param string $registrationExternalLink
     */
    public function setRegistrationExternalLink($registrationExternalLink)
    {
        $this->registrationExternalLink = $registrationExternalLink;
    }

    /**
     * Get registrationExternalLink
     *
     * @return string $registrationExternalLink
     */
    public function getRegistrationExternalLink()
    {
        return $this->registrationExternalLink;
    }

    /**
     * Set registrationExternalFullyBooked
     *
     * @param string $registrationExternalFullyBooked
     */
    public function setRegistrationExternalFullyBooked($registrationExternalFullyBooked)
    {
        $this->registrationExternalFullyBooked = $registrationExternalFullyBooked;
    }

    /**
     * Get registrationExternalFullyBooked
     *
     * @return string $registrationExternalFullyBooked
     */
    public function getRegistrationExternalFullyBooked()
    {
        return $this->registrationExternalFullyBooked;
    }

    /**
     * Add eventFields
     *
     * @param Cx\Modules\Calendar\Model\Entity\EventField $eventField
     */
    public function addEventField(\Cx\Modules\Calendar\Model\Entity\EventField $eventField)
    {
        $this->eventFields[] = $eventField;
    }

    /**
     * Remove eventFields
     *
     * @param \Cx\Modules\Calendar\Model\Entity\EventField $eventFields
     */
    public function removeEventField(\Cx\Modules\Calendar\Model\Entity\EventField $eventFields)
    {
        $this->eventFields->removeElement($eventFields);
    }

    /**
     * Set eventFields
     *
     * @param Doctrine\Common\Collections\Collection $eventFields
     */
    public function setEventFields($eventFields)
    {
        $this->eventFields = $eventFields;
    }

    /**
     * Set invite
     *
     * @param Cx\Modules\Calendar\Model\Entity\Invite $invite
     */
    public function setInvite($invite)
    {
        $this->invite= $invite;
    }

    /**
     * Add invites
     *
     * @param Cx\Modules\Calendar\Model\Entity\Invite $invite
     */
    public function addInvite(\Cx\Modules\Calendar\Model\Entity\Invite $invite)
    {
        $this->invite[] = $invite;
    }

    /**
     * Remove invite
     *
     * @param \Cx\Modules\Calendar\Model\Entity\Invite $invite
     */
    public function removeInvite(\Cx\Modules\Calendar\Model\Entity\Invite $invite)
    {
        $this->invite->removeElement($invite);
    }

    /**
     * Get invite
     *
     * @return Cx\Modules\Calendar\Model\Entity\Invite
     */
    public function getInvite()
    {
        return $this->invite;
    }

    /**
     * Get EventFieldByLangId
     *
     * @param integer $langId lang id
     *
     * @return null|\Cx\Modules\Calendar\Model\Entity\EventField
     */
    public function getEventFieldByLangId($langId)
    {
        if (!$this->eventFields) {
            return null;
        }

        foreach ($this->eventFields as $eventField) {
            if ($eventField->getLangId() == $langId) {
                return $eventField;
            }
        }
        return null;
    }

    /**
     * Get eventFields
     *
     * @return Doctrine\Common\Collections\Collection $eventFields
     */
    public function getEventFields()
    {
        return $this->eventFields;
    }

    /**
     * Add registrations
     *
     * @param Cx\Modules\Calendar\Model\Entity\Registration $registration
     */
    public function addRegistration(\Cx\Modules\Calendar\Model\Entity\Registration $registration)
    {
        $this->registrations[] = $registration;
    }

    /**
     * Remove registrations
     *
     * @param \Cx\Modules\Calendar\Model\Entity\Registration $registrations
     */
    public function removeRegistration(\Cx\Modules\Calendar\Model\Entity\Registration $registrations)
    {
        $this->registrations->removeElement($registrations);
    }

    /**
     * Set registrations
     *
     * @param Doctrine\Common\Collections\Collection $registrations
     */
    function setRegistrations($registrations)
    {
        $this->registrations = $registrations;
    }

    /**
     * Get registrations
     *
     * @return Doctrine\Common\Collections\Collection $registrations
     */
    public function getRegistrations()
    {
        return $this->registrations;
    }

    /**
     * Set categories
     * @param \Doctrine\Common\Collections\Collection $categories
     * @author Reto Kohli <reto.kohli@comvation.com>
     */
    public function setCategories(\Doctrine\Common\Collections\Collection $categories)
    {
        $this->categories = $categories;
    }

    /**
     * Get categories
     * @return \Doctrine\Common\Collections\Collection
     * @author Reto Kohli <reto.kohli@comvation.com>
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * Add a category
     * @param Category $category Category to add
     * @author Michael Ritter <michael.ritter@cloudrexx.com>
     */
    public function addCategories($category) {
        $category->addEvents($this);
        $this->categories[] = $category;
    }

    /**
     * Add categories
     *
     * @param \Cx\Modules\Calendar\Model\Entity\Category $categories
     * @return Event
     */
    public function addCategory(\Cx\Modules\Calendar\Model\Entity\Category $categories)
    {
        $this->categories[] = $categories;

        return $this;
    }

    /**
     * Remove categories
     *
     * @param \Cx\Modules\Calendar\Model\Entity\Category $categories
     */
    public function removeCategory(\Cx\Modules\Calendar\Model\Entity\Category $categories)
    {
        $this->categories->removeElement($categories);
    }

    /**
     * Set registrationForm
     *
     * @param Cx\Modules\Calendar\Model\Entity\RegistrationForm $registrationForm
     */
    public function setRegistrationForm(\Cx\Modules\Calendar\Model\Entity\RegistrationForm $registrationForm)
    {
        $this->registrationForm = $registrationForm;
    }

    /**
     * Get registrationForm
     *
     * @return Cx\Modules\Calendar\Model\Entity\RegistrationForm $registrationForm
     */
    public function getRegistrationForm()
    {
        return $this->registrationForm;
    }

}
