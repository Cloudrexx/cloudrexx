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
 * Livecam Library
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @version        1.0.0
 * @package     cloudrexx
 * @subpackage  module_livecam
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Modules\Livecam\Controller;

/**
 * Livecam Library
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @access        private
 * @version        1.0.0
 * @package     cloudrexx
 * @subpackage  module_livecam
 */
class LivecamLibrary
{
    /**
    * Settings array
    *
    * @access public
    * @var array
    */
    var $arrSettings = array();

    /**
    * Get settings
    *
    * Initialize the settings
    *
    * @access public
    */
    function getSettings()
    {

        global $objDatabase;

        $query = "SELECT setname, setvalue FROM ".DBPREFIX."module_livecam_settings";
        $objResult = $objDatabase->Execute($query);
        while (!$objResult->EOF) {
            $this->arrSettings[$objResult->fields['setname']] = $objResult->fields['setvalue'];
            $objResult->MoveNext();
        }

    }


    /**
     * Get cam settings
     *
     * @param int $id
     * @return array
     */
    public function getCamSettings($id=0)
    {
        global $objDatabase;

        $id = intval($id);

        $query = "  SELECT  id,
                            currentImagePath,
                            archivePath,
                            thumbnailPath,
                            maxImageWidth,
                            thumbMaxSize,
                            shadowboxActivate,
                            showFrom,
                            showTill
                    FROM ".DBPREFIX."module_livecam";
        if ($id != 0) {
            // select only one
            $query .= " WHERE id = ".$id;
        }
        $result = $objDatabase->Execute($query);
        if ($result === false) {
            //throw new DatabaseError("error getting the camera setting");
            return;
        }

        $ret = array();
        if ($result->RecordCount()) {
            while (!$result->EOF) {
                $cam = Array(
                    "currentImagePath"          => $result->fields['currentImagePath'],
                    "archivePath"               => $result->fields['archivePath'],
                    "thumbnailPath"             => $result->fields['thumbnailPath'],
                    "maxImageWidth"             => $result->fields['maxImageWidth'],
                    "thumbMaxSize"              => $result->fields['thumbMaxSize'],
                    "shadowboxActivate"          => $result->fields['shadowboxActivate'],
                    "showFrom"                  => $result->fields['showFrom'],
                    "showTill"                  => $result->fields['showTill']
                );
                $ret[$result->fields['id']] = $cam;
                $result->MoveNext();
            }
        }

        if ($id == 0) {
            // return all cams
            return $ret;
        } else {
            // there is only one. return that
            return array_pop($ret);
        }
    }
}
