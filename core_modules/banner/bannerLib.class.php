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
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_banner
 * @todo        Edit PHP DocBlocks!
 */

/**
 * Banner management
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_banner
 */
class bannerLibrary
{
    public $getLevels = array();
    public $getSublevels = array();
    public $levels = array();

    /**
    * Gets the categorie option menu string
    *
    * @global    object     $objDatabase
    * @param     string     $lang
    * @param     string     $selectedOption
    * @return    string     $modulesMenu
    */
    function getSettings()
    {
        global $objDatabase;
        $query = "SELECT name, value FROM ".DBPREFIX."module_banner_settings";
        $objResult = $objDatabase->Execute($query);
        while (!$objResult->EOF) {
            $this->arrSettings[$objResult->fields['name']] = $objResult->fields['value'];
            $objResult->MoveNext();
        }
    }


    /**
     * @param   integer $langId
     * @param   integer $selectedOption
     * @return  string
     * @todo    Remove the unused argument $langId
     */
    function getBannerGroupMenu($langId, $selectedOption="")
    {
        global $objDatabase;

        $strMenu = "";
        $query = "SELECT id, name, placeholder_name FROM ".DBPREFIX."module_banner_groups ORDER BY id";
        $objResult = $objDatabase->Execute($query);
        while (!$objResult->EOF) {
            $selected = ($selectedOption==$objResult->fields['id']) ? "selected" : "";
            $strMenu .="<option value=\"".$objResult->fields['id']."\" $selected>".$objResult->fields['placeholder_name']." - ".stripslashes($objResult->fields['name'])."</option>\n";
            $objResult->MoveNext();
        }
        return $strMenu;
    }


    function getLevels($id, $type)
    {
        global $objDatabase;

        $x = 0;
        //get selected levels
        $objResultCat = $objDatabase->Execute("SELECT page_id FROM ".DBPREFIX."module_banner_relations WHERE type='level' AND banner_id='".$id."'");
        if ($objResultCat !== false) {
            while (!$objResultCat->EOF) {
                $this->levels[$x] = $objResultCat->fields['page_id'];
                $x++;
                $objResultCat->MoveNext();
            }
        }

        //get all levels
        $objResultCat = $objDatabase->Execute("SELECT id, name, parentid, showcategories FROM ".DBPREFIX."module_directory_levels ORDER BY displayorder");

        if ($objResultCat !== false) {
            while (!$objResultCat->EOF) {
                $this->getLevels['name'][$objResultCat->fields['id']]            =$objResultCat->fields['name'];
                $this->getLevels['parentid'][$objResultCat->fields['id']]        =$objResultCat->fields['parentid'];
                $objResultCat->MoveNext();
            }
        }

        $options = '';

        //make levels dropdown
        if (!empty($this->getLevels['name'])) {
            foreach($this->getLevels['name'] as $levelKey => $levelName) {
                if ($this->getLevels['parentid'][$levelKey] == 0) {
                    if ($type == 1) {
                        if (!in_array($levelKey, $this->levels)) {
                            $options .= "<option value='".$levelKey."'>".$levelName."</option>";
                        }
                    }else{
                        if (in_array($levelKey, $this->levels)) {
                            $options .= "<option value='".$levelKey."'>".$levelName."</option>";
                        }
                    }

                    //get sublevels
                    $options .=$this->getSublevels($levelName, $levelKey, $type, '&nbsp;&nbsp;&nbsp;');
                }
            }
        }

        return $options;
    }


    function getSublevels($levelName, $parentId, $type, $spacer)
    {
        //get subcategories
        foreach($this->getLevels['name'] as $levelKey => $levelName) {
            if ($this->getLevels['parentid'][$levelKey] == $parentId) {
                if ($type == 1) {
                    if (!in_array($levelKey, $this->levels)) {
                        $options .= "<option value='".$levelKey."'>".$spacer.$levelName."</option>";
                    }
                }else{
                    if (in_array($levelKey, $this->levels)) {
                        $options .= "<option value='".$levelKey."'>".$levelName."</option>";
                    }
                }

                //get more subcategories
                $options .= $this->getSublevels($levelName, $levelKey, $type, $spacer.'&nbsp;&nbsp;&nbsp;');
            }
        }

        return $options;
    }
}

?>
