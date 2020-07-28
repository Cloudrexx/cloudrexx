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
 * Mail
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_calendar
*/
namespace Cx\Modules\Calendar\Model\Entity;

/**
 * Mail
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_calendar
*/
class Mail extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var string $title
     */
    protected $title;

    /**
     * @var text $contentText
     */
    protected $contentText;

    /**
     * @var text $contentHtml
     */
    protected $contentHtml;

    /**
     * @var text $recipients
     */
    protected $recipients;

    /**
     * @var integer $langId
     */
    protected $langId;

    /**
     * @var integer $actionId
     */
    protected $actionId;

    /**
     * @var integer $isDefault
     */
    protected $isDefault;

    /**
     * @var integer $status
     */
    protected $status;

    /**
     * @var integer $eventLangId
     */
    protected $eventLangId;

    /**
     * Constructor
     */
    public function __construct() {
        $this->status    = 0;
        $this->isDefault = 0;
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
     * Set contentText
     *
     * @param text $contentText
     */
    public function setContentText($contentText)
    {
        $this->contentText = $contentText;
    }

    /**
     * Get contentText
     *
     * @return text $contentText
     */
    public function getContentText()
    {
        return $this->contentText;
    }

    /**
     * Set contentHtml
     *
     * @param text $contentHtml
     */
    public function setContentHtml($contentHtml)
    {
        $this->contentHtml = $contentHtml;
    }

    /**
     * Get contentHtml
     *
     * @return text $contentHtml
     */
    public function getContentHtml()
    {
        return $this->contentHtml;
    }

    /**
     * Set recipients
     *
     * @param text $recipients
     */
    public function setRecipients($recipients)
    {
        $this->recipients = $recipients;
    }

    /**
     * Get recipients
     *
     * @return text $recipients
     */
    public function getRecipients()
    {
        return $this->recipients;
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
     * Set actionId
     *
     * @param integer $actionId
     */
    public function setActionId($actionId)
    {
        $this->actionId = $actionId;
    }

    /**
     * Get actionId
     *
     * @return integer $actionId
     */
    public function getActionId()
    {
        return $this->actionId;
    }

    /**
     * Set isDefault
     *
     * @param integer $isDefault
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = $isDefault;
    }

    /**
     * Get isDefault
     *
     * @return integer $isDefault
     */
    public function getIsDefault()
    {
        return $this->isDefault;
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
     * Set eventLangId
     *
     * @param integer $eventLangId
     */
    public function setEventLangId($eventLangId)
    {
        $this->eventLangId = $eventLangId;
    }

    /**
     * Get eventLangId
     *
     * @return integer $eventLangId
     */
    public function getEventLangId()
    {
        return $this->eventLangId;
    }
}