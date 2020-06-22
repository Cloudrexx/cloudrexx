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
 * EventField
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_calendar
*/
namespace Cx\Modules\Calendar\Model\Entity;

/**
 * EventField
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_calendar
*/
class EventField extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer $eventId
     */
    protected $eventId;

    /**
     * @var string $title
     */
    protected $title;

    /**
     * @var integer $langId
     */
    protected $langId;

    /**
     * @var text $teaser
     */
    protected $teaser;

    /**
     * @var text $description
     */
    protected $description;

    /**
     * @var string $redirect
     */
    protected $redirect;

    /**
     * @var string $place
     */
    protected $place;

    /**
     * @var string $placeCity
     */
    protected $placeCity;

    /**
     * @var string $placeCountry
     */
    protected $placeCountry;

    /**
     * @var string $orgName
     */
    protected $orgName;

    /**
     * @var string $orgCity
     */
    protected $orgCity;

    /**
     * @var string $orgCountry
     */
    protected $orgCountry;

    /**
     * @var \Cx\Modules\Calendar\Model\Entity\Event
     */
    protected $event;


    /**
     * Set eventId
     *
     * @param integer $eventId
     */
    public function setEventId($eventId)
    {
        $this->eventId = $eventId;
    }

    /**
     * Get eventId
     *
     * @return integer $eventId
     */
    public function getEventId()
    {
        return $this->eventId;
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
     * Set teaser
     *
     * @param text $teaser
     */
    public function setTeaser($teaser)
    {
        $this->teaser = $teaser;
    }

    /**
     * Get teaser
     *
     * @return text $teaser
     */
    public function getTeaser()
    {
        return $this->teaser;
    }

    /**
     * Set description
     *
     * @param text $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Get description
     *
     * @return text $description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set redirect
     *
     * @param string $redirect
     */
    public function setRedirect($redirect)
    {
        $this->redirect = $redirect;
    }

    /**
     * Get redirect
     *
     * @return string $redirect
     */
    public function getRedirect()
    {
        return $this->redirect;
    }

    /**
     * Set place
     *
     * @param string $place
     */
    public function setPlace($place)
    {
        $this->place = $place;
    }

    /**
     * Get place
     *
     * @return string $place
     */
    public function getPlace()
    {
        return $this->place;
    }

    /**
     * Set placeCity
     *
     * @param string $placeCity
     */
    public function setPlaceCity($placeCity)
    {
        $this->placeCity = $placeCity;
    }

    /**
     * Get placeCity
     *
     * @return string $placeCity
     */
    public function getPlaceCity()
    {
        return $this->placeCity;
    }

    /**
     * Set placeCountry
     *
     * @param string $placeCountry
     */
    public function setPlaceCountry($placeCountry)
    {
        $this->placeCountry = $placeCountry;
    }

    /**
     * Get placeCountry
     *
     * @return string $placeCountry
     */
    public function getPlaceCountry()
    {
        return $this->placeCountry;
    }

    /**
     * Set orgName
     *
     * @param string $orgName
     */
    public function setOrgName($orgName)
    {
        $this->orgName = $orgName;
    }

    /**
     * Get orgName
     *
     * @return string $orgName
     */
    public function getOrgName()
    {
        return $this->orgName;
    }

    /**
     * Set orgCity
     *
     * @param string $orgCity
     */
    public function setOrgCity($orgCity)
    {
        $this->orgCity = $orgCity;
    }

    /**
     * Get orgCity
     *
     * @return string $orgCity
     */
    public function getOrgCity()
    {
        return $this->orgCity;
    }

    /**
     * Set orgCountry
     *
     * @param string $orgCountry
     */
    public function setOrgCountry($orgCountry)
    {
        $this->orgCountry = $orgCountry;
    }

    /**
     * Get orgCountry
     *
     * @return string $orgCountry
     */
    public function getOrgCountry()
    {
        return $this->orgCountry;
    }

    /**
     * Set event
     *
     * @param Cx\Modules\Calendar\Model\Entity\Event $event
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
