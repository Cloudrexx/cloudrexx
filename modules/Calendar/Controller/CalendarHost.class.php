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
 * Calendar
 *
 * @package    cloudrexx
 * @subpackage module_calendar
 * @author     Cloudrexx <info@cloudrexx.com>
 * @copyright  CLOUDREXX CMS - CLOUDREXX AG
 * @version    1.00
 */

namespace Cx\Modules\Calendar\Controller;
/**
 * Calendar Class Host
 *
 * @package    cloudrexx
 * @subpackage module_calendar
 * @author     Cloudrexx <info@cloudrexx.com>
 * @copyright  CLOUDREXX CMS - CLOUDREXX AG
 * @version    1.00
 */
class CalendarHost extends CalendarLibrary
{
    /**
     * Host Id
     *
     * @access public
     * @var integer
     */
    public $id;

    /**
     * Host title
     *
     * @access public
     * @var string
     */
    public $title;

    /**
     * URL of the host
     *
     * @access public
     * @var string
     */
    public $uri;

    /**
     * Category id
     *
     * @access public
     * @var integer
     */
    public $catId;

    /**
     * Key
     *
     * @access public
     * @var string
     */
    public $key;

    /**
     * Status
     *
     * @access public
     * @var boolean
     */
    public $status;

    /**
     * Confirmed
     *
     * @access public
     * @var boolean
     */
    public $confirmed;

    /**
     * Host Constructor
     *
     * Loads the host attributes if id provided
     *
     * @param integer $id Host id
     */
    function __construct($id=null){
        if($id != null) {
            self::get($id);
        }
    }

    /**
     * Loads the Host by Id
     *
     * @param integer $hostId Host id
     *
     * @return null
     */
    function get($hostId) {
        global $objDatabase, $_LANGID;

        $query = "SELECT  id,title,uri,cat_id,`key`,confirmed,status
                    FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_host
                   WHERE id = '".intval($hostId)."'
                   LIMIT 1";

        $objResult = $objDatabase->Execute($query);

        if ($objResult !== false) {
            $this->id = intval($hostId);
            $this->title = htmlentities($objResult->fields['title'], ENT_QUOTES, CONTREXX_CHARSET);
            $this->uri = htmlentities($objResult->fields['uri'], ENT_QUOTES, CONTREXX_CHARSET);
            $this->catId = intval($objResult->fields['cat_id']);
            $this->key = htmlentities($objResult->fields['key'], ENT_QUOTES, CONTREXX_CHARSET);
            $this->confirmed = intval($objResult->fields['confirmed']);
            $this->status = intval($objResult->fields['status']);
        }
    }

    /**
     * Save the Host data's into database
     *
     * @param array $data posted data from the form
     *
     * @return boolean true if the data updated successfully, false otherwise
     */
    function save($data) {
        global $objDatabase;

        $title      = contrexx_addslashes(contrexx_strip_tags($data['title']));
        $uri        = contrexx_addslashes(contrexx_strip_tags($data['uri']));

        if(substr($uri,-1) != '/') {
            $uri = $uri."/";
        }

        $category   = intval($data['category']);
        $key        = contrexx_addslashes(contrexx_strip_tags($data['key']));
        $status     = intval($data['status']);
        $confirmed  = intval(1);

        if(empty($key)) {
            $key = $this->generateKey();
        }

        if(intval($this->id) == 0) {
            $query = "INSERT INTO ".DBPREFIX."module_".$this->moduleTablePrefix."_host
                                  (`title`,`uri`,`cat_id`,`key`,`confirmed`,`status`)
                           VALUES ('".$title."','".$uri."','".$category."','".$key."','".$confirmed."','".$status."')";
        } else {
            $query = "UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_host
                         SET `title` = '".$title."',
                             `uri` = '".$uri."',
                             `cat_id` = '".$category."',
                             `key` = '".$key."',
                             `status` = '".$status."'
                       WHERE `id` = '".intval($this->id)."'";
        }

        $objResult = $objDatabase->Execute($query);

        if($objResult !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Swtich the host status
     *
     * @return boolean true if the data updated successfully, false otherwise
     */
    function switchStatus(){
        global $objDatabase;

        if($this->status == 1) {
            $hostStatus = 0;
        } else {
            $hostStatus = 1;
        }

        $query = "UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_host
                     SET status = '".intval($hostStatus)."'
                   WHERE id = '".intval($this->id)."'";

        $objResult = $objDatabase->Execute($query);

        if ($objResult !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Delete the host
     *
     * @return boolean true if the data updated successfully, false otherwise
     */
    function delete(){
        global $objDatabase;

        $query = "DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_host
                        WHERE id = '".intval($this->id)."'";

        $objResult = $objDatabase->Execute($query);

        if ($objResult !== false) {
            $query = "DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_note_host
                            WHERE host_id = '".intval($this->id)."'";

            $objResult = $objDatabase->Execute($query);

            if ($objResult !== false) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
