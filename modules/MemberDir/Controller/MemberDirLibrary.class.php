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
 * Member directory library
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_memberdir
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Modules\MemberDir\Controller;

/**
 * Member directory library
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_memberdir
 * @todo        Edit PHP DocBlocks!
 */
class MemberDirLibrary
{
    var $options = array();
    var $directories;
    var $firstDir;

    var $arrTickets = array();

    /**
     * Constructor
     */
    function __construct()
    {
        $this->setOptions();
        $this->setDirs();
    }

    /**
     * @ignore
     */
    function MemberDirLibrary()
    {
        $this->__construct();
    }

    /**
     * Returns a List with all Chars and links
     *
     * @param string $link
     * @return string with all links
     * @access protected
     */
    function _getCharList($link)
    {
        global $_CORELANG;

        $list = "<a href=\"".$link."&amp;sort=sc\">[&nbsp;#?&nbsp;]</a>&nbsp;";

        for ($i = 65; $i <= 90; $i++) {
            $list .= "<a href=\"".$link."&amp;sort=".chr($i+32)."\">[&nbsp;".chr($i)."&nbsp;]</a>&nbsp;";
        }

        $list .= "<a href=\"".$link."&amp;sort=all\"><b>[&nbsp;".$_CORELANG['TXT_ACCESS_ALL']."&nbsp;]</b></a>";

        return $list;
    }

    /**
     * Sets the options array
     *
     * @access public
     * @global ADONewConnection
     */
    function setOptions()
    {
        global $objDatabase;

        $query = "SELECT setname, setvalue FROM ".DBPREFIX."module_memberdir_settings";
        $objResult = $objDatabase->Execute($query);

        if (!$objResult) {
            echo $objDatabase->ErrorMsg();
        }

        while (!$objResult->EOF) {
            $this->options[$objResult->fields['setname']] = $objResult->fields['setvalue'];
            $objResult->MoveNext();
        }
    }

    /**
     * Sets the list of directories
     */
    function setDirs($id = 0, $basedir = false)
    {
        unset($this->directories);
        $this->directories = array();

        $this->getDir($id, 0);

        $keys = array_keys($this->directories);
        if (count($keys) > 0) {
            $this->firstDir = $keys[0];
        }

        foreach ($this->directories as $key => $dir) {
            if (!isset($dir['has_children'])) {
                $this->directories[$key]['has_children'] = 0;
            }
        }

        if ($basedir) {
            $dirid = 0;
            $this->directories[$dirid]['name'] = "Alle Verzeichnisse";
            $this->directories[$dirid]['description'] = "";
            $this->directories[$dirid]['active'] = "1";
            $this->directories[$dirid]['level'] = "0";
            $this->directories[$dirid]['displaymode'] = "2";
            $this->directories[$dirid]['sort'] = "1";
            $this->directories[$dirid]['parentdir']  = "0";
            $this->directories[$dirid]['pic1']  = "0";
            $this->directories[$dirid]['pic2']  = "0";
            $this->directories[$dirid]['lang'] = 0;
        }
    }

    /**
     * Get Dir
     *
     * Recursive function to get a tree of the directories
     * @param int $id
     * @return array or null
     */
    function getDir($id, $level)
    {
        global $objDatabase;

        $query = "SELECT dirid, parentdir, name, description,
                         active, displaymode, sort,
                         pic1, pic2, lang_id
                         FROM ".DBPREFIX."module_memberdir_directories
                  WHERE parentdir = $id";
        $objResult = $objDatabase->Execute($query);

        if ($objResult) {
            $dirs = array();

            while (!$objResult->EOF) {
                $dirid = $objResult->fields['dirid'];
                $this->directories[$dirid]['name'] = $objResult->fields['name'];
                $this->directories[$dirid]['description'] = $objResult->fields['description'];
                $this->directories[$dirid]['active'] = $objResult->fields['active'];
                $this->directories[$dirid]['level'] = $level;
                $this->directories[$dirid]['displaymode'] = $objResult->fields['displaymode'];
                $this->directories[$dirid]['sort'] = $objResult->fields['sort'];
                $this->directories[$dirid]['parentdir']  = $objResult->fields['parentdir'];
                $this->directories[$dirid]['pic1'] = $objResult->fields['pic1'];
                $this->directories[$dirid]['pic2'] = $objResult->fields['pic2'];
                $this->directories[$dirid]['lang'] = $objResult->fields['lang_id'];

                if ($objResult->fields['parentdir'] != 0) {
                    $this->directories[$objResult->fields['parentdir']]['has_children'] = true;
                }

                $this->getDir($dirid, $level+1);

                $objResult->MoveNext();
            }
        } else {
            echo $objDatabase->ErrorMsg();
        }
    }


    /**
     * Intialises a directory
     *
     * Inputs 16 fields in a database for a directory
     * @access public
     * @global ADONewConnection
     */
    function initDir($dirid)
    {
        global $objDatabase;

        for ($i= 1; $i <= 18; $i++) {
            $field = $i;
            $name = "Field".$field;

            $type = ($i < 12) ? "area" : "text";
            $query = "INSERT INTO ".DBPREFIX."module_memberdir_name
                      (field, dirid, name, active, `lang_id`) VALUES
                      ('$field', '$dirid', '$name', '0', '{$this->langId}')";
            $objDatabase->Execute($query);
        }
    }

    /**
     * Returns the fieldnames
     *
     * @access public
     * @global ADONewConnection
     * @global array
     */
    function getFieldData($dirid)
    {
        global $objDatabase, $_ARRAYLANG;

        $dirid_where = $dirid ? "WHERE dirid = '$dirid'" : '';

        $query = "SELECT field, dirid, name, active FROM ".DBPREFIX."module_memberdir_name
                $dirid_where
                ORDER BY field ASC";
        $objResult = $objDatabase->Execute($query);
        $fieldnames = array();
        if ($objResult) {
            while (!$objResult->EOF) {
                $index = $objResult->fields['field'];
                $fieldnames[$index] = array(
                    'name'   => $objResult->fields['name'],
                    'active' => $objResult->fields['active'],
                );
                $objResult->MoveNext();
            }
        }

        return $fieldnames;
    }

    /**
     * Checks if the string could be a url/e-mail-address or something
     *
     * @access protected
     * @return string String itself or a formatted string
     * @param string $str String which shall be checked
     */
    function checkStr($str)
    {
        if (preg_match("%^(http://)%", $str)) {
            return "<a href=\"".$str."\" title=\"$str\">".$str."</a>";
        } elseif (preg_match("%^(www\.)%", $str)) {
            return "<a href=\"http://".$str."\" title=\"\">".$str."</a>";
        } elseif (preg_match("%^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(.[a-zA-Z0-9-]+)*\.(([0-9]{1,3})|([a-zA-Z]{2,3})|(aero|coop|info|museum|name))$%", $str)) {
            return "<a href=\"mailto:".$str."\" title=\"\">".$str."</a>";
        } else {
            return $str;
        }
    }

    /**
     * Directory List
     *
     * Generates a list with all of the directories
     * @access private
     * @param string $name Name of the select
     * @param int $select Entry which shall be selected
     * @return string String with the xhtml selection code
     */
    function dirList($name, $selection, $width)
    {
        $select = "<select name=\"".$name."\" style=\"width: ".$width."px;\" onchange=\"dirChange(this.value)\">";

        foreach ($this->directories as $dirid => $directory) {
            $selected = ($selection == $dirid) ? "selected=\"selected\"" : "";

            $prefix = "";
            for ($i=1; $i<=$directory['level']; $i++) {
               $prefix .= "...";
            }

            $select .= "<option value=\"".$dirid."\" $selected>".$prefix.$directory['name']."</option>";
        }

        $select .= "</select>";

        return $select;
    }

    /**
     * export the member informations as a vcard (*.vcf)
     *
     * @param integer $id
     * @return void
     */
    function _exportVCard($id){
        global $objDatabase;
        //error_reporting(E_ALL);ini_set('display_errors',1);
        $query = "  SELECT `pic1`, `pic2`, `0`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`, `10`, `11`, `12`, `13`, `14`, `15`, `16`, `17`, `18`
                    FROM `".DBPREFIX."module_memberdir_values`
                    WHERE `id` = ".$id;

        if( ($objRS = $objDatabase->SelectLimit($query, 1)) !== false){
            \Env::get('ClassLoader')->loadFile(ASCMS_LIBRARY_PATH.'/PEAR/Contact_Vcard_Build.php');

            $vcard = new \Contact_Vcard_Build();

            $lastname   = $objRS->fields['1'];
            $firstname  = $objRS->fields['2'];
            $company    = $objRS->fields['3'];
            $phone      = $objRS->fields['4'];
            $mobile     = $objRS->fields['5'];
            $address    = $objRS->fields['6'];
            $zip        = $objRS->fields['7'];
            $city       = $objRS->fields['8'];
            $email      = $objRS->fields['9'];
            $fax        = $objRS->fields['10'];
            $homepage   = $objRS->fields['11'];
            $birthday   = $objRS->fields['12'];
            $special1   = $objRS->fields['13'];
            $special2   = $objRS->fields['14'];
            $special3   = $objRS->fields['15'];
            $special4   = $objRS->fields['16'];
            $special5   = $objRS->fields['17'];
            $special6   = $objRS->fields['18'];

            $vcard->setFormattedName("{$lastname} {$firstname}");
            $vcard->setName($lastname, $firstname, '', '' ,'');
            $vcard->addEmail($email);
            $vcard->addParam('TYPE','HOME');
            $vcard->addAddress($address, '', '', '', '', '', '');
            $vcard->addOrganization($company);
            $vcard->addTelephone($phone);
            $vcard->addParam('TYPE', 'HOME');
            $vcard->addTelephone($mobile);
            $vcard->addParam('TYPE', 'CELL');
            $vcard->setURL($homepage);
            $vcard->setBirthday($birthday);

            header('Content-Disposition: attachment; filename="'.$lastname.'_'.$firstname.'.vcf"');
            header('Content-Type: text/x-vcard');
            echo $vcard->fetch();
            die();
        }else{
            die('DB Error: '. $objDatabase->ErrorMsg());
        }
    }

    /**
     * Get media browser button
     *
     * @param string $id       Id of the button
     * @param string $callback Name of the callback function
     * @return string HTML element of browse button
     */
    protected function getMediaBrowserButton($id, $callback = '')
    {
        $options = array(
            'type'  => 'button',
            'views' => 'filebrowser',
            'id'    => $id,
            'style' => 'display: none;',
        );
        $mediaBrowser = new \Cx\Core_Modules\MediaBrowser\Model\Entity\MediaBrowser();
        $mediaBrowser->setOptions($options);
        if ($callback) {
            $mediaBrowser->setCallback($callback);
        }

        return $mediaBrowser->getXHtml();
    }
}
