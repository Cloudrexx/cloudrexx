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
 * Link
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_linkmanager
*/
namespace Cx\Core_Modules\LinkManager\Model\Entity;

/**
 * Link Entity
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_linkmanager
 */
class Link {

    /**
     * Id of the crawler run
     * @var Integer
     */
    private $id;

    /**
     * lang of the crawler run
     * @var Integer
     */
    private $lang;

    /**
     * requestedPath
     * @var string
     */
    private $requestedPath;

    /**
     * refererPath
     * @var string
     */
    private $refererPath;

    /**
     * leadPath
     * @var string
     */
    private $leadPath;

    /**
     * linkStatusCode
     * @var integer
     */
    private $linkStatusCode;

    /**
     * linkStatus
     * @var integer
     */
    private $linkStatus;

    /**
     * entry page title
     * @var string
     */
    private $entryTitle;

    /**
     * The module name
     * @var string
     */
    private $moduleName;

    /**
     * module action
     * @var string
     */
    private $moduleAction;

    /**
     * module parameter
     * @var string
     */
    private $moduleParams;

    /**
     * link detected time
     * @var datetime
     */
    private $detectedTime;

    /**
     * Flag status
     * @var Integer
     */
    private $flagStatus;

    /**
     * updated user name
     * @var Integer
     */
    private $updatedBy;

    /**
     * link recheck
     * @var Integer
     */
    private $linkRecheck;

    /**
     * requestedLinkType
     * @var Integer
     */
    private $requestedLinkType;

    /**
     * brokenLinkText
     * @var String
     */
    private $brokenLinkText;

    public function __construct() {
        //default values
        $this->id                = 0;
        $this->lang              = 0;
        $this->requestedPath     = '';
        $this->refererPath       = '';
        $this->leadPath          = '';
        $this->linkStatusCode    = 0;
        $this->moduleName        = '';
        $this->moduleAction      = '';
        $this->moduleParams      = '';
        $this->detectedTime      = '';
        $this->flagStatus        = false;
        $this->linkStatus        = false;
        $this->linkRecheck       = false;
        $this->updatedBy         = 0;
        $this->requestedLinkType = '';
        $this->brokenLinkText    = '';
    }

    /**
     * Get the id
     *
     * @return Integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set the id
     *
     * @param Integer $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * get the lang
     *
     * @return Integer
     */
    public function getLang() {
        return $this->lang;
    }

    /**
     * set the lang
     *
     * @param Integer $lang
     */
    public function setLang($lang) {
        $this->lang = $lang;
    }

    /**
     * get the link status code
     *
     * @return Integer
     */
    public function getLinkStatusCode() {
        return $this->linkStatusCode;
    }

    /**
     * set the link status code
     *
     * @param Integer $linkStatusCode
     */
    public function setLinkStatusCode($linkStatusCode) {
        $this->linkStatusCode = $linkStatusCode;
    }

    /**
     * get the link status
     *
     * @return Boolean
     */
    public function getLinkStatus() {
        return $this->linkStatus;
    }

    /**
     * Set the link status
     *
     * @param Boolean $linkStatus
     */
    public function setLinkStatus($linkStatus) {
        $this->linkStatus = $linkStatus;
    }

    /**
     * get the detected time
     *
     * @return timestamp
     */
    public function getDetectedTime() {
        return $this->detectedTime;
    }

    /**
     * set the current time as detected time
     */
    public function updateDetectedTime(){
        $date = new \DateTime("now");
        $this->setDetectedTime($date);
    }

    /**
     * set the detected time
     *
     * @param timestamp $detectedTime
     */
    public function setDetectedTime($detectedTime) {
        $this->detectedTime = $detectedTime;
    }

    /**
     * get the requested path
     *
     * @return String
     */
    public function getRequestedPath() {
        return $this->requestedPath;
    }

    /**
     * set the requested path
     *
     * @param String $requestedPath
     */
    public function setRequestedPath($requestedPath) {
        $this->requestedPath = $requestedPath;
    }

    /**
     * get the refer path
     *
     * @return String
     */
    public function getRefererPath() {
        return $this->refererPath;
    }

    /**
     * set the refer path
     *
     * @param String $refererPath
     */
    public function setRefererPath($refererPath) {
        $this->refererPath = $refererPath;
    }

    /**
     * get the refer path
     *
     * @return String
     */
    public function getLeadPath() {
        return $this->leadPath;
    }

    /**
     * set the refer path
     *
     * @param String $refererPath
     */
    public function setLeadPath($leadPath) {
        $this->leadPath = $leadPath;
    }

    /**
     * get the entry title
     *
     * @return String
     */
    public function getEntryTitle() {
        return $this->entryTitle;
    }

    /**
     * set the entry title
     *
     * @param String $entryTitle
     */
    public function setEntryTitle($entryTitle) {
        $this->entryTitle = $entryTitle;
    }

    /**
     * get the module name
     *
     * @return String
     */
    public function getModuleName() {
        return $this->moduleName;
    }

    /**
     * set the module name
     *
     * @param String $moduleName
     */
    public function setModuleName($moduleName) {
        $this->moduleName = $moduleName;
    }

    /**
     * get the module action
     *
     * @return String
     */
    public function getModuleAction() {
        return $this->moduleAction;
    }

    /**
     * set the module action
     *
     * @param String $moduleAction
     */
    public function setModuleAction($moduleAction) {
        $this->moduleAction = $moduleAction;
    }

    /**
     * get the module parameters
     *
     * @return String
     */
    public function getModuleParams() {
        return $this->moduleParams;
    }

    /**
     * set the module parameters
     *
     * @param String $moduleParams
     */
    public function setModuleParams($moduleParams) {
        $this->moduleParams = $moduleParams;
    }

    /**
     * get the flaf status
     *
     * @return Boolean
     */
    public function getFlagStatus() {
        return $this->flagStatus;
    }

    /**
     * set the flag status
     *
     * @param Boolean $flagStatus
     */
    public function setFlagStatus($flagStatus) {
        $this->flagStatus = $flagStatus;
    }

    /**
     * get the link recheck status
     *
     * @return Boolean
     */
    public function getLinkRecheck() {
        return $this->linkRecheck;
    }

    /**
     * set the link rechecked
     *
     * @param Boolean $linkRecheck
     */
    public function setLinkRecheck($linkRecheck) {
        $this->linkRecheck = $linkRecheck;
    }

    /**
     * get the updated user id
     *
     * @return Integer
     */
    public function getUpdatedBy() {
        return $this->updatedBy;
    }

    /**
     * set the updated by user id
     *
     * @param Integer $updatedBy
     */
    public function setUpdatedBy($updatedBy) {
        $this->updatedBy = $updatedBy;
    }

    /**
     * get the requested link type
     *
     * @return String
     */
    public function getRequestedLinkType() {
        return $this->requestedLinkType;
    }

    /**
     * set the requested link type
     *
     * @param String $requestedLinkType
     */
    public function setRequestedLinkType($requestedLinkType) {
        $this->requestedLinkType = $requestedLinkType;
    }

    /**
     * get the broken link text
     *
     * @return String
     */
    public function getBrokenLinkText() {
        return $this->brokenLinkText;
    }

    /**
     * set the broken link text
     *
     * @param String $brokenLinkText
     */
    public function setBrokenLinkText($brokenLinkText) {
        $this->brokenLinkText = $brokenLinkText;
    }

    /**
     * Update values from array
     *
     * @param Array $newData
     */
    public function updateFromArray($newData) {
        foreach ($newData as $key => $value) {
            try {
                call_user_func(array($this, "set".ucfirst($key)), $value);
            }
            catch (Exception $e) {
                \DBG::log("\r\nskipped ".$key);
            }
        }
    }
}
