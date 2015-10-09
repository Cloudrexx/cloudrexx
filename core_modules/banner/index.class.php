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
 * Banner management
 *
 * This module will get all the news pages
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_banner
 * @todo        Edit PHP DocBlocks!
 */

/**
 * Banner
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_banner
 */
class Banner extends bannerLibrary
{
    public $arrGroups = array();

    /**
     * PHP5 constructor
     * @param  string  $pageContent
     * @global string  $_LANGID
     * @access public
     */
    function __construct()
    {
        global $_LANGID;
        $this->_getBannerGroupStatus();
        $this->langId = $_LANGID;
    }


    /**
     * Initialized the banner group array
     *
     * @global    object     $objDatabase
     */
    function _getBannerGroupStatus()
    {
        global $objDatabase;
        $query = "SELECT id, status FROM ".DBPREFIX."module_banner_groups";
        $objResult = $objDatabase->Execute($query);
        if ($objResult) {
            while (!$objResult->EOF) {
                $this->arrGroups[$objResult->fields['id']] = $objResult->fields['status'];
                $objResult->MoveNext();
            }
        }
    }


    /**
     * Get page
     *
     * @access public
     * @global object $objDatabase
     * @return string bannerCode
     */
    function getBannerCode($groupId, $pageId)
    {
        global $objDatabase;

        $groupId = intval($groupId);
        $pageId  = intval($pageId);

        $debugMessage = '';

        if (!empty($this->arrGroups[$groupId])) {
            ///////////////////////////////////
            // The Banner group is active
            ///////////////////////////////////
            if (isset($_GET['teaserId'])) {
                $teaserId=intval($_GET['teaserId']);

                $query = "SELECT system.banner_code AS banner_code,
                                 system.id AS id
                            FROM ".DBPREFIX."module_banner_relations AS relation,
                                 ".DBPREFIX."module_banner_system AS system
                           WHERE relation.group_id = ".$groupId."
                             AND relation.page_id = ".$teaserId."
                             AND relation.banner_id = system.id
                             AND relation.type='teaser'
                             AND system.status=1";
            } elseif (isset($_GET['lid'])) {
                $levelId=intval($_GET['lid']);

                $query = "SELECT system.banner_code AS banner_code,
                                 system.id AS id
                            FROM ".DBPREFIX."module_banner_relations AS relation,
                                 ".DBPREFIX."module_banner_system AS system
                           WHERE relation.group_id = ".$groupId."
                             AND relation.page_id = ".$levelId."
                             AND relation.banner_id = system.id
                             AND relation.type='level'
                             AND system.status=1";
            } else {
                $query = "SELECT system.banner_code AS banner_code,
                                 system.id AS id
                            FROM ".DBPREFIX."module_banner_relations AS relation,
                                 ".DBPREFIX."module_banner_system AS system
                           WHERE relation.group_id = ".$groupId."
                             AND relation.page_id = ".$pageId."
                             AND relation.banner_id = system.id
                             AND relation.type='content'
                             AND system.status=1";
            }

            $objResult = $objDatabase->Execute($query);
            $counBanner = $objResult->RecordCount();

            if ($objResult !== false && $counBanner>=1) {
                $arrRandom = array();

                while (!$objResult->EOF) {
                    $arrRandom[$objResult->fields['id']] = stripslashes($objResult->fields['banner_code']);
                    $objResult->MoveNext();
                }

                $ranId = @array_rand($arrRandom, 1);

                $this->updateViews($ranId);

                $bannerCode = $arrRandom[$ranId];
                $bannerCode = str_replace('<a ', '<a onclick="bannerClicks(\''.$ranId.'\')" ', $bannerCode);

                return $debugMessage.$bannerCode;
            } else {
                ///////////////////////////////////
                // show the default banner for this group
                ///////////////////////////////////
                $query = "SELECT id, banner_code FROM ".DBPREFIX."module_banner_system WHERE parent_id = ".$groupId." AND is_default=1 AND status=1";
                $objResult = $objDatabase->SelectLimit($query, 1);
                if ($objResult !== false) {

                    $this->updateViews($objResult->fields['id']);

                    $bannerCode = $bannerCode = stripslashes($objResult->fields['banner_code']);
                    $bannerCode = str_replace('<a ', '<a onclick="bannerClicks(\''.$objResult->fields['id'].'\')" ', $bannerCode);

                    return $debugMessage.$bannerCode;
                }
            }
        //} else {
            ///////////////////////////////////
            // The Banner group is inactive
            ///////////////////////////////////
        }
        return $debugMessage;
    }


    function updateViews($bannerId)
    {
        global $objDatabase;

        $objDatabase->Execute("
            UPDATE ".DBPREFIX."module_banner_system
               SET views=views+1
             WHERE id=$bannerId
        ");
    }


    function updateClicks($bannerId)
    {
        global $objDatabase;
        $objDatabase->Execute("
            UPDATE ".DBPREFIX."module_banner_system
               SET clicks=clicks+1
             WHERE id=$bannerId
        ");
    }


    function getBannerJS()
    {
        return "
<script language='JavaScript'>
<!--

function bannerClicks(bannerId)
{
    img=document.createElement('img');
    img.src='?bannerId='+bannerId;
    img='';
}

//-->
</script>";
    }

}

?>
