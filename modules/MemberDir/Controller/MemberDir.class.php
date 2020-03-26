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


namespace Cx\Modules\MemberDir\Controller;

/**
 * Member directory
 *
 * Frontend memberdir class
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_memberdir
 */
class MemberDir extends MemberDirLibrary
{
    var $langId;
    var $_objTpl;
    var $statusMessage;
    var $error;


    /**
    * Constructor
    *
    * @param  string
    * @access public
    */
    function MemberDir($pageContent)
    {
        $this->__construct($pageContent);
    }

    /**
     * PHP5 constructor
     * @param  string  $pageContent
     * @global integer
     * @access public
     */
    function __construct($pageContent)
    {
        global $_LANGID;
        $this->pageContent = $pageContent;
        $this->langId = $_LANGID;

        $this->_objTpl = new \Cx\Core\Html\Sigma('.');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);
        $this->_objTpl->setTemplate($this->pageContent, true, true);
        parent::__construct();
    }


    /**
     * Get Page
     *
     * @access public
     * @return string Page content
     */
    function getPage()
    {
        global $_ARRAYLANG;

        if (!isset($_GET['cmd'])){
            $_GET['cmd'] = '';
        }

        if (isset($_GET['mid'])) {
            $this->_show();
        } elseif (isset($_GET['exportvcf'])) {
            $this->_exportVCard(intval($_GET['id']));
        } elseif (isset($_GET['id']) || isset($_GET['search'])) {
            $this->_memberList();
        } else {
            $this->_categoryList();
        }

        $this->_objTpl->setGlobalVariable(array(
            "MEMBERDIR_KEYWORD"       => (empty($_GET['keyword'])) ? "" : htmlentities(contrexx_stripslashes($_GET['keyword']), ENT_QUOTES, CONTREXX_CHARSET),
            "MEMBERDIR_SEARCH"        => $_ARRAYLANG['TXT_SEARCH'],
            "MEMBERDIR_DROPDOWN"      => $this->dirList("id", isset($_GET['id']) ? intval($_GET['id']) : 0, 200)
        ));

        return $this->_objTpl->get();
    }


    /**
     * Memberlist
     *
     * @access private
     * @global ADONewConnection
     * @global array
     * @global array
     */
    function _memberList()
    {
        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        $this->setDirs(0, true);

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        $dirid = intval($_GET['id']);

        $this->_objTpl->setGlobalVariable(array(
            "TXT_OVERVIEW"  => $_ARRAYLANG['TXT_OVERVIEW']
        ));

        $treeid = $dirid;
        $tree = array();
        while ($treeid > 0) {
            $temp = array(
                'id' => $treeid,
                'name'  => $this->directories[$treeid]['name']);
            $tree[] = $temp;

            $treeid = $this->directories[$treeid]['parentdir'];
        }

        $tree = array_reverse($tree);

        foreach($tree as $branch) {
            $this->_objTpl->setVariable(array(
                "MEMBERDIR_DIRID"       => $branch['id'],
                "MEMBERDIR_DIRNAME"     => $branch['name']
            ));

            $this->_objTpl->parse("tree-element");
        }

        $this->_objTpl->parse("tree");

        if ($this->directories[$dirid]['displaymode'] == 0 || $this->directories[$dirid]['displaymode'] == 1) {
            $lastlevel = 0;
            if ($this->directories[$dirid]['has_children']) {
                $this->_objTpl->setVariable(array(
                  "TXT_CATEGORY_TREE_DESC"                  => "<div style=\"margin-bottom: 5px;\">".$_ARRAYLANG['TXT_SUBDIRECTORIES']."</div>",
                  'TXT_MEMBERDIR_EXPORT_CONTACT_AS_VCARD'   => $_ARRAYLANG['TXT_MEMBERDIR_EXPORT_CONTACT_AS_VCARD'],
                ));
            }

            foreach ($this->directories as $dirkey => $directory) {
                // check language
                if ($directory['lang'] != 0 && $directory['lang'] != $this->langId) {
                    continue;
                }
                if ($directory['active'] && $directory['parentdir'] == $dirid && $dirkey != 0) {
                    $this->_objTpl->setVariable(array(
                        "MEMBERDIR_DIR_ID"      => $dirkey,
                        "MEMBERDIR_DIR_NAME"    => $directory['name'],
                        "MEMBERDIR_IMAGE_SRC"   => "pixel.gif"
                    ));

                    $this->_objTpl->parse("category");
                }
            }


            $this->_objTpl->parse("category_list");
            $this->_objTpl->hideBlock("category_show");
        }

        if ($this->directories[$dirid]['displaymode'] == 0 || $this->directories[$dirid]['displaymode'] == 2) {

            if (empty($_GET['sort'])) {
                $_GET['sort'] = "";
            }

            if (empty($_GET['search'])) {
                $_GET['search'] = "";
            }

            $keyword = (isset($_GET['keyword'])) ? contrexx_addslashes($_GET['keyword']) : "";

            $sort = contrexx_addslashes($_GET['sort']);

            $this->_objTpl->setGlobalVariable(array(
                "MEMBERDIR_DIRID"         => $dirid,
                "MEMBERDIR_CHAR_LIST"     => $this->_getCharList(CONTREXX_DIRECTORY_INDEX."?section=MemberDir&amp;cmd=".htmlentities($_GET['cmd'], ENT_QUOTES, CONTREXX_CHARSET)."&amp;id=".$dirid."&amp;sort=$sort"),
                "MEMBERDIR_DESCRIPTION"   => nl2br($this->directories[$dirid]['description']),
            ));

            $sortField = $this->directories[$dirid]['sort'];

            if ($sort == "sc") {
                /* Special Chars */
                $query = "SELECT *
                          FROM ".DBPREFIX."module_memberdir_values
                          WHERE `1` REGEXP '^[^a-zA-Z]' AND
                          `dirid` = '$dirid'";
            } elseif (preg_match("%^[a-z]$%i", $sort)) {
                /* Sort by char */
                $query = "SELECT *
                          FROM ".DBPREFIX."module_memberdir_values
                          WHERE `1` REGEXP '^".$sort."' AND
                          `dirid` = '$dirid'";
            } elseif ($_GET['search'] == "search") {
                /* Search */
                $query = "SELECT *
                          FROM ".DBPREFIX."module_memberdir_values
                          WHERE (
                            `1` LIKE '%$keyword%' OR
                            `2` LIKE '%$keyword%' OR
                            `3` LIKE '%$keyword%' OR
                            `4` LIKE '%$keyword%' OR
                            `5` LIKE '%$keyword%' OR
                            `6` LIKE '%$keyword%' OR
                            `7` LIKE '%$keyword%' OR
                            `8` LIKE '%$keyword%' OR
                            `9` LIKE '%$keyword%' OR
                            `10` LIKE '%$keyword%' OR
                            `11` LIKE '%$keyword%' OR
                            `12` LIKE '%$keyword%' OR
                            `13` LIKE '%$keyword%' OR
                            `14` LIKE '%$keyword%' OR
                            `15` LIKE '%$keyword%' OR
                            `16` LIKE '%$keyword%' OR
                            `17` LIKE '%$keyword%' OR
                            `18` LIKE '%$keyword%'
                            ) ";
                if ($dirid !=0) {
                    $query .= " AND `dirid` = '$dirid'";
                }
                $objResult = $objDatabase->Execute($query);
            } elseif ($sort == "all") {
                /* All */
                $query = "SELECT *
                          FROM ".DBPREFIX."module_memberdir_values
                          WHERE `dirid` = '$dirid'";
            } else {
                if ($this->options['default_listing']) {
                    $query = "SELECT *
                          FROM ".DBPREFIX."module_memberdir_values
                          WHERE `dirid` = '$dirid'";
                }
            }

            if ($this->options['default_listing']) {
                $query .= " ORDER BY `".$sortField."` ASC";


                $pos = (isset($_GET['pos'])) ? intval($_GET['pos']) : 0;

                $objResult = $objDatabase->Execute($query);
            }

            if ($objResult) {
                $count = $objResult->RecordCount();
                $paging = getPaging($count, $pos, "&amp;section=MemberDir&amp;cmd=".htmlentities($_GET['cmd'], ENT_QUOTES, CONTREXX_CHARSET)."&amp;id=$dirid&amp;sort=$sort&amp;search=".htmlentities(contrexx_stripslashes($_GET['search']), ENT_QUOTES, CONTREXX_CHARSET)."&amp;keyword=$keyword", "<b>".$_ARRAYLANG['TXT_MEMBERDIR_ENTRIES']."</b>", true, $_CONFIG['corePagingLimit']);

                $this->_objTpl->setVariable("MEMBERDIR_PAGING", $paging);

                $objResult = $objDatabase->SelectLimit($query, $_CONFIG['corePagingLimit'], $pos);

                if ($objResult) {
                    $rowid = 1;
                    while (!$objResult->EOF) {
                        $fieldnames = $this->getFieldData($dirid);
                        for ($i=1; $i<17; $i++) {
                            $placeholder = $this->getPlaceholderName($fieldnames[$i]['name']);
                            $replace[$placeholder] = $objResult->fields["$i"];
                        }
                        if ($dirid == 0) {
                            $replace["FIELD_CATEGORY"] = $_ARRAYLANG['TXT_DIRECTORY'].": <strong>".$this->directories[$objResult->fields['dirid']]['name']."</strong><br />";
                        }


                        if ($this->directories[$objResult->fields['dirid']] && $objResult->fields['pic1'] != "none") {
                            $src = $objResult->fields['pic1'];
                            $size = getimagesize(ASCMS_PATH.$src);
                            $width = ($this->options['max_width'] < $size[0]) ? $this->options['max_width'] : $size[0];
                            $height = ($this->options['max_height'] < $size[1]) ? $this->options['max_height'] : $size[1];
                            $this->_objTpl->setVariable(array(
                              "FIELD_PIC1" => "<img src=\"$src\" alt=\"\" style=\"width: ".$width."px; height: ".$height."px;\" /><br />"
                            ));
                        }

                        if ($this->directories[$objResult->fields['dirid']] && $objResult->fields['pic2'] != "none") {
                            $src = $objResult->fields['pic2'];
                            $size = getimagesize(ASCMS_PATH.$src);
                            $width = ($this->options['max_width'] < $size[0]) ? $this->options['max_width'] : $size[0];
                            $height = ($this->options['max_height'] < $size[1]) ? $this->options['max_height'] : $size[1];
                            $this->_objTpl->setVariable(array(
                              "FIELD_PIC2" => "<img src=\"$src\" alt=\"\" style=\"width: ".$width."px; height: ".$height."px;\" /><br />"
                            ));
                        }

                        $this->_objTpl->setVariable($replace);
                        $this->_objTpl->setVariable(array(
                            "MEMBERDIR_ROW"     => $rowid,
                            "MEMBERDIR_ID"      => $objResult->fields['id'],
                            "FIELD_DIRECTORY"   => $this->directories[$dirid]['name']
                        ));
                        $this->_objTpl->parse("memberdir_row");

                        $rowid = ($rowid == 2) ? 1 : 2;

                        $objResult->MoveNext();
                    }
                }
            }
            $this->_objTpl->touchBlock("category_show");
            $this->_objTpl->parse("category_show");
        }
    }


    /**
     * Detail view of an entry
     *
     * @access private
     * @global ADONewConnection
     * @global array
     */
    function _show()
    {
        global $objDatabase, $_ARRAYLANG;

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        $id = intval($_GET['mid']);

        $query = "SELECT * FROM ".DBPREFIX."module_memberdir_values
                 WHERE id = '".$id."'";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
            echo $objDatabase->ErrorMsg();
        }

        if ($this->directories[$objResult->fields['dirid']] && $objResult->fields['pic1'] != "none") {
            $src = $objResult->fields['pic1'];
            $size = getimagesize(ASCMS_PATH.$src);
            $width = ($this->options['max_width'] < $size[0]) ? $this->options['max_width'] : $size[0];
            $height = ($this->options['max_height'] < $size[1]) ? $this->options['max_height'] : $size[1];
            $this->_objTpl->setVariable(array(
              "MEMBERDIR_PIC1" => "<img src=\"$src\" alt=\"\" style=\"width: ".$width."px; height: ".$height."px;\" /><br />"
            ));
        }

        if ($this->directories[$objResult->fields['dirid']] && $objResult->fields['pic2'] != "none") {
            $src = $objResult->fields['pic2'];
            $size = getimagesize(ASCMS_PATH.$src);
            $width = ($this->options['max_width'] < $size[0]) ? $this->options['max_width'] : $size[0];
            $height = ($this->options['max_height'] < $size[1]) ? $this->options['max_height'] : $size[1];
            $this->_objTpl->setVariable(array(
              "MEMBERDIR_PIC2" => "<img src=\"$src\" alt=\"\" style=\"width: ".$width."px; height: ".$height."px;\" /><br />"
            ));
        }

        if ($this->_objTpl->blockExists("row")) {
            $this->_objTpl->setVariable(array(
                "MEMBERDIR_FIELD_NAME"      => $_ARRAYLANG['TXT_DIRECTORY'],
                "MEMBERDIR_FIELD_VALUE"     => $this->directories[$objResult->fields['dirid']]['name']
            ));
            $this->_objTpl->parse("row");
        } else {
           $this->_objTpl->setVariable(array(
               "MEMBERDIR_FIELD_DIRECTORY" => $this->directories[$objResult->fields['dirid']]['name']
           ));
        }

        $fields = $this->getFieldData($objResult->fields['dirid']);

        foreach ($fields as $key => $field) {
            if ($this->_objTpl->blockExists("row")) {
                // Automatic listing
                if ($field['active']) {
                    if(preg_match('#\s[a-zA-Z]([.]?([[:alnum:]_-]+)*)?@([[:alnum:]\-_]+\.)+[a-zA-Z]{2,4}#', $objResult->fields[$key], $matches)){
                        $objResult->fields[$key] = preg_replace("#\s[a-zA-Z]([.]?([[:alnum:]_-]+)*)?@([[:alnum:]\-_]+\.)+[a-zA-Z]{2,4}#", "<a href=\"mailto:".$matches[0]."\">".$matches[0]."</a>", $objResult->fields[$key]);
                    }
                    $subs = array();
                    if(strpos($objResult->fields[$key], 'http://') !== false){
                        preg_match('#http://([a-zA-Z0-9_\-\.]+\.[a-zA-Z]{1,}[a-zA-Z0-9_\-\#\%\&/\?\.\=]+)#', $objResult->fields[$key], $subs);
                        $objResult->fields[$key] = '<a href="http://'.$subs[1].'" title="http://'.$subs[1].'" target="_blank">'.$objResult->fields[$key].'</a>';
                    }
                    if(strpos($objResult->fields[$key], 'www.') !== false){
                        preg_match('#www\.([a-zA-Z0-9_\-\.]+\.[a-zA-Z]{1,}[a-zA-Z0-9_\-\#\%\&/\?\.\=]+)#', $objResult->fields[$key], $subs);
                        $objResult->fields[$key] = '<a href="http://www.'.$subs[1].'" title="http://www.'.$subs[1].'" target="_blank">'.$objResult->fields[$key].'</a>';
                    }
                    $this->_objTpl->setVariable(array(
                        "MEMBERDIR_FIELD_NAME"      => $field['name'],
                        "MEMBERDIR_FIELD_VALUE"     => ($key > 13) ? nl2br($objResult->fields[$key]) : $this->checkStr($objResult->fields[$key])
                    ));
                    $this->_objTpl->parse("row");
                }
            } else {
                // manual listing
                $name = ($key <= 12 ) ? strtoupper($field['name']) : $key;
                $this->_objTpl->setVariable(array(
                    "MEMBERDIR_FIELD_".$name => ($key > 12) ? nl2br($objResult->fields[$key]) : $this->checkStr($objResult->fields[$key])
                ));
            }
        }

        $this->_objTpl->setVariable('MEMBERDIR_VCARD', '<a style="float: right; padding-right: 10px" href="index.php?section=MemberDir&amp;exportvcf=1&amp;id='.$id.'"> vCard <img border="0" alt="vcard" title="vcard" src="modules/MemberDir/View/Media/vcard.gif" style="margin-top: 3px;" /></a>');

        $this->_objTpl->parse("memberdir_detail_view");
    }
    /*
    private function _categoryList()
    {
        global $objDatabase;

        $parList = array();
        foreach ($this->directories as $key => $dir) {
            $parList[$dir['parentdir']][] = $key;
        }

        $catTree = $this->buildCategoryTree($parList);

    }

    private function parseCategoryList($catTree)
    {
        foreach ($catTree as $key => $cat) {
            $this->_objTpl->setVariable(array(
                 "MEMBERDIR_PARENT_ID"     => $cat['parentdir'],
                 "MEMBERDIR_PADDING_LEFT"  => $cat['level'] * 20
            ));
            $this->_objTpl->parse("")
        }
    }*/

    /**
     * Show the list of categories
     */
    function _categoryList()
    {
    global $objDatabase, $_ARRAYLANG;

    //evaluate template and render directory tree.
           $directoryIds = array_keys($this->directories);
    $len = count($this->directories);
    for($i = 0; $i < $len; $i++)
    {
        //the current directory.
        $directory = &$this->directories[$directoryIds[$i]];
        $directoryId = $directoryIds[$i];

        //skip directories from other languages and disabled directories
        //      display should work.
            if ($directory['lang'] != $this->langId && $directory['lang'] != 0 || !$directory['active']) {
                continue;
            }

        //subleveling I: get our data together.

        //levels are zerobased and increase during subleveling
        $lastDirectoryLevel = 0; //level before current directory
        $nextDirectoryLevel = 0; //level after current directory

        if($i > 0)
        $lastDirectoryLevel = $this->directories[$directoryIds[$i-1]]['level'];
        if($i < $len-1)
        $nextDirectoryLevel = $this->directories[$directoryIds[$i+1]]['level'];


        //subleveling II: parse template

        //get level difference. 0 means same, >0 means sublevel (, <0 means parent level)
        $levelDelta = $directory['level'] - $lastDirectoryLevel;
        //open sublevels as needed
        if($levelDelta > 0) { //open sublevel
        //no loop because our data guarantees a level increase of max. 1 level
        $this->_objTpl->setVariable(array(
                "MEMBERDIR_PARENT_ID" => $directory['parentdir'],
            "MEMBERDIR_PADDING_LEFT"  => $directory['level'] * 20
        ));
        $this->_objTpl->parse("div-block-beginning");
        } else { //parent or same level
        $this->_objTpl->hideBlock('div-block-beginning');
        }
        //get level difference. (0 means same, >0 means sublevel,) <0 means parent level
        $levelDelta = $nextDirectoryLevel - $directory['level'];
        if($levelDelta < 0)
        { //close sublevels as needed
        for($j = 0; $j < abs($levelDelta); $j++) {
            $this->_objTpl->touchBlock("div-block-ending");
            $this->_objTpl->parse("div-block-ending");
        }
        }


        //fill in data of current directory

        //set plus sign if childs are available...
        if ($directory['has_children']) {
        $this->_objTpl->setVariable(array(
          "MEMBERDIR_DIR_ID"      => $directoryId,
          "MEMBERDIR_IMAGE_SRC"   => "pluslink.gif"
          ));
        } else { //...else show nothing
        $this->_objTpl->setVariable(array(
          "MEMBERDIR_IMAGE_SRC"   => "pixel.gif"
          ));
        }

        //set name / id
        $this->_objTpl->setVariable(array(
          "MEMBERDIR_DIR_ID"      => $directoryId,
          "MEMBERDIR_DIR_NAME"    => $directory['name'],
          ));

        //finally parse.
        $this->_objTpl->parse("category");
    }
    }

    function getPlaceholderName($name)
    {
        $name = strtoupper($name);
        return "FIELD_".$name;
    }

    /**
     * Build a category tree recursively
     *
     * @param int $parList
     * @param int $parent
     * @return array
     */
    private function buildCategoryTree($parList, $parent=0)
    {
        $arr = array();
        foreach ($parList[$parent] as $value) {
            $id = array_push($arr, $this->directories[$value]);
            if ($this->directories[$value]['has_children']) {
               $arr[$id-1]['children'] = $this->buildCategoryTree($parList, $value);
            }
        }
        return $arr;
    }
}
