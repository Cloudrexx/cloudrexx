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
 * Home content
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_directory
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Modules\Directory\Controller;

/**
 * Home content
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_directory
 */
class DirHomeContent extends DirectoryLibrary
{
    private $_pageContent;
    private $_objTemplate;
    private $rssPath;
    private $rssWebPath;
    private $settings = array();

    //local settings
    private $rows = 2;
    private $subLimit = 5;
    private $rowWidth = "50%";
    private $arrRows = array();
    private $arrRowsIndex = array();

    public $navtree;
    public $navtreeLevels = array();
    public $navtreeCategories = array();

    /**
     * Constructor PHP5
     */
    function __construct($pageContent)
    {
        $this->_pageContent = $pageContent;
        $this->_objTemplate = new \Cx\Core\Html\Sigma('.');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTemplate);
        $this->rssPath = ASCMS_DIRECTORY_FEED_PATH . '/';
        $this->rssWebPath = ASCMS_DIRECTORY_FEED_WEB_PATH. '/';
        $this->settings = $this->getSettings();
    }


    public static function getObj($pageContent)
    {
        global $objInit, $_ARRAYLANG;

        $_ARRAYLANG = array_merge($_ARRAYLANG, $objInit->loadLanguageData('Directory'));
        static $obj = null;
        if (is_null($obj)) {
            $obj = new dirHomeContent($pageContent);
        }
        return $obj;
    }


    function getContent()
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;

        $this->_objTemplate->setTemplate($this->_pageContent,true,true);

        $this->numLevels = array();
        $this->numCategories = array();

        if(isset($_GET['lid'])){
            $lId = intval($_GET['lid']);
        }else{
            $lId = 0;
        }

        if(isset($_GET['cid'])){
            $cId = intval($_GET['cid']);
        }else{
            $cId = 0;
        }

        //xml link
        $xmlLink = $this->rssWebPath."directory_latest.xml";

        //get search
        $this->getSearch();


        if($this->settings['levels']['value'] == 1){
            $arrAttributes = $this->showLevels($lId);
        }else{
            $arrAttributes = $this->showCategories($cId);
        }

        $objResult = $objDatabase->Execute("SELECT COUNT(1) AS dir_count FROM ".DBPREFIX."module_directory_dir WHERE status = 1");
        $insertFeeds = str_replace('%COUNT%', '<b>'.$objResult->fields['dir_count'].'</b>', $_ARRAYLANG['TXT_INSERT_FEEDS']);
        $this->_objTemplate->parse('showInsertFeeds');

        if($this->settings['description']['value'] == 0){
            $arrAttributes['description'] = "";
        }

        //select View
        if ($this->settings['indexview']['value'] == 1) {
            $this->arrRows = array();
            sort($this->arrRowsIndex);

            $i = 0;
            foreach($this->arrRowsIndex as $rowKey => $rowName){
                if ($index != substr($rowName, 0, 1)) {
                    $index = substr($rowName, 0, 1);
                    if($i%$this->rows==0){
                        $i=1;
                    }else{
                        $i++;
                    }

                    $this->arrRows[$i] .= "<br /><b>".$index."</b><br />".substr($rowName,1);
                } else {
                    $this->arrRows[$i] .= substr($rowName,1);
                }
            }
        }

        // set variables
        $this->_objTemplate->setVariable(array(
            'TYPE_SELECTION' => $this->typeSelection,
            'DIRECTORY_ROW_WIDTH' => $this->rowWidth,
            'DIRECTORY_ROW1' => $this->arrRows[1]."<br />",
            'DIRECTORY_ROW2' => $this->arrRows[2]."<br />",
            'DIRECTORY_TITLE' => $arrAttributes['title'],
            'DIRECTORY_XML_LINK' => $xmlLink,
            'DIRECTORY_INSERT_FEEDS' => $insertFeeds,
        ));
        return $this->_objTemplate->get();
    }


    function showLevels($parentId)
    {
        global $objDatabase, $_ARRAYLANG;

        if(!isset($showlevels)){
            $arrLevel['showlevels'] = 1;
        }

        //get levels
        $objResult = $objDatabase->Execute("SELECT id, parentid, name FROM ".DBPREFIX."module_directory_levels WHERE status = '1' AND parentid ='".contrexx_addslashes($parentId)."' ORDER BY displayorder");

        if($objResult !== false){
            while(!$objResult->EOF){
                $this->levels['name'][$objResult->fields['id']] = $objResult->fields['name'];
                $this->levels['parentid'][$objResult->fields['id']] = $objResult->fields['parentid'];
                $objResult->MoveNext();
            }
        }

        //get level attributes
        $objResult = $objDatabase->Execute("SELECT id, name, description, showcategories, showlevels FROM ".DBPREFIX."module_directory_levels WHERE status = '1' AND id =".contrexx_addslashes($parentId)." LIMIT 1");
        if($objResult !== false){
            while(!$objResult->EOF){
                $arrLevel['title'] = $objResult->fields['name'];
                $arrLevel['description'] = $objResult->fields['description'];
                $arrLevel['showentries'] = $objResult->fields['showentries'];
                $arrLevel['showcategories'] = $objResult->fields['showcategories'];
                $arrLevel['showlevels'] = $objResult->fields['showlevels'];
                $objResult->MoveNext();
            }
        }

        //show level
        $i = 1;
        if(!empty($this->levels) && $arrLevel['showlevels'] == 1 && !isset($_GET['cid'])){
            foreach($this->levels['name'] as $levelKey => $levelName){
                //count entries
                $count = $this->count($levelKey, '');

                $class= $parentId==0 ? "catLink" : "subcatLink";
                $this->arrRows[$i] .= "<a class='catLink' href='".CONTREXX_DIRECTORY_INDEX."?section=Directory&amp;lid=".$levelKey."''>".htmlentities($levelName, ENT_QUOTES, CONTREXX_CHARSET)."</a>&nbsp;(".$count.")<br />";
                array_push($this->arrRowsIndex, substr(htmlentities($levelName, ENT_QUOTES, CONTREXX_CHARSET), 0, 1)."<a class='catLink' href='".CONTREXX_DIRECTORY_INDEX."?section=Directory&amp;lid=".$levelKey."''>".htmlentities($levelName, ENT_QUOTES, CONTREXX_CHARSET)."</a>&nbsp;(".$count.")<br />");

                //get level
                if($this->levels['parentid'][$levelKey] == 0){
                    $objResult = $objDatabase->Execute("SELECT id, name FROM ".DBPREFIX."module_directory_levels WHERE status = '1' AND parentid =".contrexx_addslashes($levelKey)." ORDER BY displayorder LIMIT ".contrexx_addslashes($this->subLimit)."");
                    if($objResult !== false){
                        while(!$objResult->EOF){
                            $this->arrRows[$i] .= "<a class='subcatLink' href='".CONTREXX_DIRECTORY_INDEX."?section=Directory&amp;lid=".$objResult->fields['id']."''>".htmlentities($objResult->fields['name'], ENT_QUOTES, CONTREXX_CHARSET)."</a>, ";
                            $objResult->MoveNext();
                        }
                    }

                    if($objResult->RecordCount() != 0){
                        $this->arrRows[$i] .= "<br />";
                    }
                }

                if($i%$this->rows==0){
                    $i=1;
                }else{
                    $i++;
                }
            }
        }

        if($arrLevel['showcategories'] == 1){
            if(isset($_GET['cid'])){
                $arrCategories = $this->showCategories(intval($_GET['cid']));
                $arrLevel['title'] = $arrCategories['title'];
                $arrLevel['description'] = $arrCategories['description'];
                $arrLevel['showentries'] = $arrCategories['showentries'];
            }else{
                $this->showCategories(0);
            }
        }
        return $arrLevel;
    }


    function showCategories($parentId)
    {
        global $objDatabase, $_ARRAYLANG;

        if(!empty($_GET['lid'])){
            $levelLink = "&amp;lid=".intval($_GET['lid']);
        }else{
            $levelLink = "";
        }

        //get categories
        $objResult = $objDatabase->Execute("SELECT id, parentid, name, showentries FROM ".DBPREFIX."module_directory_categories WHERE status = '1' AND parentid =".contrexx_addslashes($parentId)." ORDER BY displayorder");

        if($objResult !== false){
            while(!$objResult->EOF){
                $this->categories['name'][$objResult->fields['id']] = $objResult->fields['name'];
                $this->categories['parentid'][$objResult->fields['id']] = $objResult->fields['parentid'];
                $objResult->MoveNext();
            }
        }

        //get categorie attributes
        $objResult = $objDatabase->Execute("SELECT id, name, description, showentries FROM ".DBPREFIX."module_directory_categories WHERE status = '1' AND id =".contrexx_addslashes($parentId)." LIMIT 1");
            if($objResult !== false){
                while(!$objResult->EOF){
                    $arrCategories['title'] = $objResult->fields['name'];
                    $arrCategories['description'] = $objResult->fields['description'];
                    $arrCategories['showentries'] = $objResult->fields['showentries'];
                    $objResult->MoveNext();
                }
            }

        //show categories
        $i = 1;
        if(!empty($this->categories)){
            foreach($this->categories['name'] as $catKey => $catName){
                //count entries
                $count = $this->count($_GET['lid'], $catKey);

                $class= $parentId==0 ? "catLink" : "subcatLink";
                $this->arrRows[$i] .= "<a class='catLink' href='".CONTREXX_DIRECTORY_INDEX."?section=Directory".$levelLink."&amp;cid=".$catKey."''>".htmlentities($catName, ENT_QUOTES, CONTREXX_CHARSET)."</a>&nbsp;(".$count.")<br />";
                array_push($this->arrRowsIndex, substr(htmlentities($catName, ENT_QUOTES, CONTREXX_CHARSET), 0, 1)."<a class='catLink' href='".CONTREXX_DIRECTORY_INDEX."?section=Directory".$levelLink."&amp;cid=".$catKey."''>".htmlentities($catName, ENT_QUOTES, CONTREXX_CHARSET)."</a>&nbsp;(".$count.")<br />");


                //get subcategories
                if($this->categories['parentid'][$catKey] == 0){
                    $objResult = $objDatabase->Execute("SELECT id, name FROM ".DBPREFIX."module_directory_categories WHERE status = '1' AND parentid =".contrexx_addslashes($catKey)." ORDER BY displayorder LIMIT ".contrexx_addslashes($this->subLimit)."");
                    if($objResult !== false){
                        while(!$objResult->EOF){
                            $this->arrRows[$i] .= "<a class='subcatLink' href='".CONTREXX_DIRECTORY_INDEX."?section=Directory".$levelLink."&amp;cid=".$objResult->fields['id']."''>".htmlentities($objResult->fields['name'], ENT_QUOTES, CONTREXX_CHARSET)."</a>, ";
                            $objResult->MoveNext();
                        }
                    }

                    if($objResult->RecordCount() != 0){
                        $this->arrRows[$i] .= "<br />";
                    }
                }

                if($i%$this->rows==0){
                    $i=1;
                }else{
                    $i++;
                }
            }
        }else{
            $this->_objTemplate->hideBlock('showCategories');
        }

        return $arrCategories;
    }


    /**
     * get search
     * @access     public
     * @param        string    $id
     */
    function getSearch()
    {
        global $objDatabase, $_ARRAYLANG, $template;

        $arrDropdown['language'] = $this->getLanguages(contrexx_addslashes($_REQUEST['language']));
        $arrDropdown['platform'] = $this->getPlatforms(contrexx_addslashes($_REQUEST['platform']));
        $arrDropdown['canton'] = $this->getCantons(contrexx_addslashes($_REQUEST['canton']));
        $arrDropdown['spez_field_21'] = $this->getSpezDropdown(contrexx_addslashes($_REQUEST['spez_field_21']),'spez_field_21');
        $arrDropdown['spez_field_22'] = $this->getSpezDropdown(contrexx_addslashes($_REQUEST['spez_field_22']),'spez_field_22');
        $arrDropdown['spez_field_23'] = $this->getSpezVotes(contrexx_addslashes($_REQUEST['spez_field_23']),'spez_field_23');
        $arrDropdown['spez_field_24'] = $this->getSpezVotes(contrexx_addslashes($_REQUEST['spez_field_24']),'spez_field_24');
        $expSearch = '';
        $javascript = '
<script type="text/javascript">
<!--
function toggle(target)
{
  obj = document.getElementById(target);
  obj.style.display = (obj.style.display==\'none\') ? \'inline\' : \'none\';
  if (obj.style.display==\'none\' && target == \'hiddenSearch\'){
    document.getElementById(\'searchCheck\').value = \'norm\';
  }else if(obj.style.display==\'inline\' && target == \'hiddenSearch\'){
    document.getElementById(\'searchCheck\').value = \'exp\';
  }
}
-->
</script>
';

        //get levels
        if ($this->settings['levels']['value'] == 1) {
            $lid = intval($_REQUEST['lid']);
            $options = $this->getSearchLevels($lid);
            $name = $_ARRAYLANG['TXT_LEVEL'];
            $field = '<select name="lid" style="width:194px;"><option value=""></option>'.$options.'</select>';

            // set variables
            $expSearch    .= '
<tr>
  <td width="100" height="20" style="border: 0px solid #ff0000;">'.$name.'</td>
  <td style="border: 0px solid #ff0000;">'.$field.'</td>
</tr>
';
        }

        //get categories
        $cid = intval($_REQUEST['cid']);
        $options = $this->getSearchCategories($cid);
        $name = $_ARRAYLANG['TXT_DIR_F_CATEGORIE'];
        $field = '<select name="cid" style="width:194px;"><option value=""></option>'.$options.'</select>';

        // set variables
        $expSearch    .= '
<tr>
  <td width="100" height="20" style="border: 0px solid #ff0000;">'.$name.'</td>
  <td style="border: 0px solid #ff0000;">'.$field.'</td>
</tr>
';

        //get exp search fields
        $objResult = $objDatabase->Execute("SELECT id, name, title, typ FROM ".DBPREFIX."module_directory_inputfields WHERE exp_search='1' AND is_search='1' ORDER BY sort");
        if($objResult !== false){
            while(!$objResult->EOF){
                if($objResult->fields['typ'] == 5 || $objResult->fields['typ'] == 6) {
                    $name = $objResult->fields['title'];
                } else {
                    if (!empty($_ARRAYLANG[$objResult->fields['title']])) {
                        $name = $_ARRAYLANG[$objResult->fields['title']];
                    } else {
                        $name = $objResult->fields['title'];
                    }
                }

                if($objResult->fields['typ'] == 1 || $objResult->fields['typ'] == 2 || $objResult->fields['typ'] == 5 || $objResult->fields['typ'] == 6){
                    $field =
                        '<input maxlength="100" size="30" name="'.
                        $objResult->fields['name'].'" value="'.
                        contrexx_addslashes($_REQUEST[$objResult->fields['name']]).'" />';
                }else{
                    $field =
                        '<select name="'.$objResult->fields['name'].
                        '" style="width:194px;">'.
                        $arrDropdown[$objResult->fields['name']].'</select>';
                }
                // set variables
                $expSearch .= '
<tr>
  <td width="100" height="20">'.$name.'</td>
  <td>'.$field.'</td>
</tr>
';
                $objResult->MoveNext();
            }
        }

        $html = '
<div class="directorySearch">
  <form action="index.php?" method="get" name="directorySearch" id="directorySearch">
    <input name="term" value="'.(!empty($_GET['term']) ? htmlentities($_GET['term'], ENT_QUOTES, CONTREXX_CHARSET) : '').'" size="25" maxlength="100" />
    <input id="searchCheck" type="hidden" name="check" value="norm" size="10" />
    <input type="hidden" name="section" value="Directory" size="10" />
    <input type="hidden" name="cmd" value="search" size="10" />
    <input type="submit" value="'.$_ARRAYLANG['TXT_DIR_F_SEARCH'].'" name="search" />
    &raquo; <a onclick="javascript:toggle(\'hiddenSearch\')" href="javascript:{}">'.$_ARRAYLANG['TXT_DIRECTORY_EXP_SEARCH'].'</a><br />
    <div style="display: none;" id="hiddenSearch">
      <br />
      <table width="100%" cellspacing="0" cellpadding="0" border="0">
        '.$expSearch.'
      </table>
    </div>
  </form>
</div>';

        // set variables
        $this->_objTemplate->setVariable(array(
            'DIRECTORY_SEARCH' =>    $javascript.$html,
        ));
    }
}

?>
