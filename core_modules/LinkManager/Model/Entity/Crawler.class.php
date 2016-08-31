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
 * Crawler
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_linkmanager
*/
namespace Cx\Core_Modules\LinkManager\Model\Entity;

/**
 * Crawler Entity
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_linkmanager
 */
class Crawler {

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
     * Start time of the crawler run
     * @var Timestamp
     */
    private $startTime;

    /**
     * End time of the crawler run
     * @var Timestamp
     */
    private $endTime;

    /**
     * Total number of links found in the crawler run
     * @var Integer
     */
    private $totalLinks;

    /**
     * Total number of broken links found in the crawler run
     * @var Integer
     */
    private $totalBrokenLinks;

    /**
     * crawler run status
     * @var String
     */
    private $runStatus;

    public function __construct() {
        //default values
        $this->id = 0;
        $this->lang = 0;
        $this->totalLinks = 0;
        $this->totalBrokenLinks = 0;
        $this->runStatus = 'running';
    }

    /**
     * get the id
     *
     * @return Integer $id
     */
    public function getId() {
        return $this->id;
    }

    /**
     * set the id
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
     * get the start time
     *
     * @return String
     */
    public function getStartTime() {
        return $this->startTime;
    }

    /**
     * Update the starttime to now
     */
    public function updateStartTime(){
        $date = new \DateTime("now");
        $this->setStartTime($date);
    }

    /**
     * set the start time
     *
     * @param String $startTime
     */
    public function setStartTime($startTime) {
        $this->startTime = $startTime;
    }

    /**
     * get the end time
     *
     * @return String
     */
    public function getEndTime() {
        return $this->endTime;
    }

    /**
     * Update the end time
     */
    public function updateEndTime() {
        $date = new \DateTime("now");
        $this->setEndTime($date);
    }

    /**
     * set the end time
     *
     * @param String $endTime
     */
    public function setEndTime($endTime) {
        $this->endTime = $endTime;
    }

    /**
     * get the total links
     *
     * @return Integer
     */
    public function getTotalLinks() {
        return $this->totalLinks;
    }

    /**
     * set the total links
     *
     * @param Integer $totalLinks
     */
    public function setTotalLinks($totalLinks) {
        $this->totalLinks = $totalLinks;
    }

    /**
     * Get the total broken links
     *
     * @return Integer
     */
    public function getTotalBrokenLinks() {
        return $this->totalBrokenLinks;
    }

    /**
     * set the total broken links
     *
     * @param Integer $totalBrokenLinks
     */
    public function setTotalBrokenLinks($totalBrokenLinks) {
        $this->totalBrokenLinks = $totalBrokenLinks;
    }

    /**
     * Get the run status
     *
     * @return Integer
     */
    public function getRunStatus() {
        return $this->runStatus;
    }

    /**
     * set the run status
     *
     * @param String $status
     */
    public function setRunStatus($runStatus) {
        $this->runStatus = $runStatus;
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
