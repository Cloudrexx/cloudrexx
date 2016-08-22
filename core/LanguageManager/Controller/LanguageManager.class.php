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
 * Language
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  core_languagemanager
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Core\LanguageManager\Controller;

/**
 * Language Manager
 *
 * This class provides all the language functions and options for the core CMS system
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  core_languagemanager
 */
class LanguageManager
{
    /**
     * Sigma template instance
     * 
     * @var Cx\Core\Html\Sigma  $template
     */
    protected $template;
    public $pageTitle='';
    public $strErrMessage = '';
    public $strOkMessage = '';
    public $arrLang = array();
    public $filePath='';
    public $hideVariables = true;
    public $langIDs = array();

    private $act = '';
    
    /**
     * Constructor
     * @global  ADONewConnection
     * @global  array
     * @global  \Cx\Core\Html\Sigma
     * @return  void
     */
    function __construct()
    {
        global  $objDatabase, $_CORELANG, $objTemplate;

        $arrTables = array();

        $objRS = $objDatabase->Execute("SELECT `id` FROM ".DBPREFIX."languages ORDER BY `id`");
        while(!$objRS->EOF) {
            array_push($this->langIDs, $objRS->fields['id']);
            $objRS->MoveNext();
        }
        $this->filePath = ASCMS_LANGUAGE_PATH.'/';
        // get tables in database
        $objResult = $objDatabase->MetaTables('TABLES');
        if ($objResult !== false) {
            $arrTables = $objResult;
        }
        if (in_array(DBPREFIX."language_variable_names",$arrTables) && in_array(DBPREFIX."language_variable_content",$arrTables)) {
            $this->hideVariables = false;
        }        
        $objResult = $objDatabase->Execute("SELECT id,name FROM ".DBPREFIX."languages");
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $this->arrLang[$objResult->fields['id']]=$objResult->fields['name'];
                $objResult->MoveNext();
            }
        }
        $objDatabase->Execute("OPTIMIZE TABLE ".DBPREFIX."languages");
        $objDatabase->Execute("OPTIMIZE TABLE ".DBPREFIX."language_variable_content");
        $objDatabase->Execute("OPTIMIZE TABLE ".DBPREFIX."language_variable_names");
        
        $this->template = new \Cx\Core\Html\Sigma(ASCMS_CORE_PATH . '/LanguageManager/View/Template/Backend');
        $this->template->setErrorHandling(PEAR_ERROR_DIE);    
    }
    private function setNavigation()
    {
        global $objTemplate, $_ARRAYLANG;

        $objTemplate->setVariable("CONTENT_NAVIGATION","
            <a href='index.php?cmd=LanguageManager' class='".($this->act == '' ? 'active' : '')."'>".$_ARRAYLANG['TXT_LANGUAGE_LIST']."</a>"
            .($this->hideVariables == false ? "<a href='index.php?cmd=LanguageManager&amp;act=vars' class='".($this->act == 'vars' ? 'active' : '')."'>".$_ARRAYLANG['TXT_VARIABLE_LIST']."</a>
            <a href='index.php?cmd=LanguageManager&amp;act=mod' class='".($this->act == 'mod' ? 'active' : '')."'>".$_ARRAYLANG['TXT_ADD_LANGUAGE_VARIABLES']."</a>
            <a href='index.php?cmd=LanguageManager&amp;act=writefiles' class='".($this->act == 'writefiles' ? 'active' : '')."' title='".$_ARRAYLANG['TXT_WRITE_VARIABLES_TO_FILES']."'>".$_ARRAYLANG['TXT_WRITE_VARIABLES_TO_FILES']."</a>": ""));
    }
    
    protected function isInFullMode() {
        global $_CONFIG, $objDatabase;
        return \Cx\Core_Modules\License\License::getCached($_CONFIG, $objDatabase)->isInLegalComponents("fulllanguage");
    }


    /**
     * Gets the requested methods
     *
     * @global   array
     * @global   \Cx\Core\Html\Sigma
     * @return   string    Parsed content
     */
    function getLanguagePage()
    {
        global $_CORELANG, $objTemplate;

        if (!isset($_GET['act'])) {
            $_GET['act'] = "";
        }

        switch($_GET['act']) {
            case 'dellang':
                \Permission::checkAccess(49, 'static');
                $this->deleteLanguage();
                $this->languageOverview();
            break;
            case 'vars':
                $this->listVariables();
            break;
            case 'varOfId':
                $this->_getVarOfId();
                break;
            case 'mod':
                \Permission::checkAccess(48, 'static');
                $this->addUpdateVariable();
                $this->modifyVariables();
            break;
            case 'add':
                \Permission::checkAccess(50, 'static');
                $this->addLanguage();
                $this->languageOverview();
            break;
            case 'del':
                \Permission::checkAccess(48, 'static');
                $this->deleteVariable();
                $this->listVariables();
            break;
            case 'writefiles':
                \Permission::checkAccess(48, 'static');
                $this->createFiles();
                $this->listVariables();
                break;
            default:                
                \Permission::checkAccess(50, 'static');
                $this->modifyLanguage();
                $this->languageOverview();
        }
        $objTemplate->setVariable(array(
            'CONTENT_TITLE'          => $this->pageTitle,
            'CONTENT_OK_MESSAGE'     => $this->strOkMessage,
            'CONTENT_STATUS_MESSAGE' => $this->strErrMessage,
            'ADMIN_CONTENT'          => $this->template->get()
        ));

        $this->act = $_GET['act'];
        $this->setNavigation();
    }


    /**
     * deletes the selected language
     *
     * @global    array
     * @global    ADONewConnection
     * @return    boolean    True on success, false on failure
     */
    function deleteLanguage()
    {
        global $_ARRAYLANG, $objDatabase;
        if (!empty($_REQUEST['id'])) {
            
            $pageRepo = \Env::get('em')->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
            $pages = $pageRepo->findBy(array(
                'lang' => intval($_REQUEST['id']),
            ));
            if (count($pages)) {
                if ($objDatabase->Execute("DELETE FROM ".DBPREFIX."languages WHERE id=".intval($_REQUEST['id'])) !== false) {
                    $objDatabase->Execute("DELETE FROM ".DBPREFIX."language_variable_content WHERE lang_id=".intval($_REQUEST['id']));
                    $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_gallery_language WHERE lang_id=".intval($_REQUEST['id']));
                    $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_gallery_language_pics WHERE lang_id=".intval($_REQUEST['id']));
                    $this->strOkMessage = $_ARRAYLANG['TXT_STATUS_SUCCESSFULLY_DELETE'];
                    return true;
                }
            }
            $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'];
        }
        return false;
    }


    /**
     * deletes the selected language variables
     *
     * @global  array
     * @global  ADONewConnection
     * @return  boolean     True on success, false on failure
     */
    function deleteVariable()
    {
        global $_ARRAYLANG, $objDatabase;
        if (!empty($_REQUEST['id'])) {
            $objDatabase->Execute("DELETE FROM ".DBPREFIX."language_variable_names WHERE id=".intval($_REQUEST['id']));
            $objDatabase->Execute("DELETE FROM ".DBPREFIX."language_variable_content WHERE varid=".intval($_REQUEST['id']));
            $this->strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_DELETED_SUCCESSFUL'];
            return true;
        }
        return false;
    }


    /**
     * Adds a new language and imports language variables from default language
     * @global  array
     * @global  ADONewConnection
     * @return  boolean    True on success, false on failure
     */
    function addLanguage()
    {
        global $_ARRAYLANG, $objDatabase;

        if (empty($_POST['name']) || empty($_POST['shortName']) || empty($_POST['charset'])) {
            return false;
        }
        $shortName = mysql_escape_string($_POST['shortName']);
        $name = mysql_escape_string($_POST['name']);
        $charset = mysql_escape_string($_POST['charset']);

        $objResult = $objDatabase->Execute("
            SELECT lang
              FROM ".DBPREFIX."languages
             WHERE lang='$shortName'
        ");
        if (!$objResult) {
            $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'];
            return false;
        }
        if ($objResult->RecordCount() > 0) {
            // Language exists already.
// TODO: Add a more suitable error message here.
            $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'];
            return false;
        }
        $objDatabase->Execute("
            INSERT INTO ".DBPREFIX."languages
               SET lang='".contrexx_addslashes($shortName)."',
                   name='".contrexx_addslashes($name)."',
                   charset='".contrexx_addslashes($charset)."',
                   is_default='false'
        ");
        $newLanguageId = $objDatabase->Insert_ID();

        $objResult = $objDatabase->SelectLimit("
            SELECT id FROM ".DBPREFIX."languages
             WHERE is_default='true'", 1
        );
        if ($objResult) {
            while (!$objResult->EOF) {
                $defaultLanguageId = $objResult->fields['id'];
                $objResult->MoveNext();
            }
        }
        $objResult = $objDatabase->Execute("
            SELECT varid,content
              FROM ".DBPREFIX."language_variable_content
             WHERE lang_id=$defaultLanguageId
        ");
        if ($objResult) {
            while (!$objResult->EOF) {
                $arrContent[$objResult->fields['varid']] = $objResult->fields['content'];
                $objResult->MoveNext();
            }
        }
        foreach ($arrContent as $key => $content) {
            $objDatabase->Execute("
                INSERT INTO ".DBPREFIX."language_variable_content
                   SET varid=$key,
                       content='".addslashes($content)."',
                       lang_id=$newLanguageId,
                       status=0
            ");
        }
        $objResult = $objDatabase->Execute("
            SELECT gallery_id, name, value
              FROM ".DBPREFIX."module_gallery_language
             WHERE lang_id=$defaultLanguageId
        ");
        if ($objResult) {
            while (!$objResult->EOF) {
                $objDatabase->Execute("
                    INSERT INTO ".DBPREFIX."module_gallery_language
                       SET gallery_id=".$objResult->fields['gallery_id'].",
                           lang_id=$newLanguageId,
                           name='".$objResult->fields['name']."',
                           value='".$objResult->fields['value']."'
                ");
                $objResult->MoveNext();
            }
        }
        $objResult = $objDatabase->Execute("
            SELECT picture_id, name, `desc`
              FROM ".DBPREFIX."module_gallery_language_pics
             WHERE lang_id=$defaultLanguageId
        ");
        if ($objResult) {
            while (!$objResult->EOF) {
                $objDatabase->Execute("
                    INSERT INTO ".DBPREFIX."module_gallery_language_pics
                       SET picture_id=".$objResult->fields['picture_id'].",
                           lang_id=$newLanguageId,
                           name='".$objResult->fields['name']."',
                           `desc`='".$objResult->fields['desc']."'
                ");
                $objResult->MoveNext();
            }
        }
        $this->strOkMessage = $_ARRAYLANG['TXT_NEW_LANGUAGE_ADDED_SUCCESSFUL'];
        return true;
    }


    /**
     * Gets the language add variable page
     *
     * @global  array
     * @global  ADONewConnection
     * @return  boolean     True on success, false on failure
     */
    function addUpdateVariable()
    {
        global $_ARRAYLANG, $objDatabase;

        $moduleId = intval($_POST['moduleId']);
        $regex = '#\[[\'"](.*)[\'"]\][[:space:]]*=[[:space:]]*["\'](.*)["\'];#';
        //multiple variables
        if (!empty($_REQUEST['backend_lang_vars']) || !empty($_REQUEST['frontend_lang_vars'])) {
            $backendVars = array();
            $frontendVars = array();
            $bothVars = array();  //for identical backend and frontend variables
            $_REQUEST['backend_lang_vars'] = contrexx_stripslashes($_REQUEST['backend_lang_vars']);
            $_REQUEST['frontend_lang_vars'] = contrexx_stripslashes($_REQUEST['frontend_lang_vars']);
            $backendVarLines = explode("\n", $_REQUEST['backend_lang_vars']);
            $frontendVarLines = explode("\n", $_REQUEST['frontend_lang_vars']);
            $result = array();
            foreach ($backendVarLines as $backendVar) {
                if (trim($backendVar) == '' || substr(trim($backendVar), 0, 11) != '$_ARRAYLANG') {
                    continue;
                }
                preg_match($regex, $backendVar, $result);    //ugly key => val regex
                if (!empty($result[1]) && !empty($result[2])) {
                    $backendVars[$result[1]] = $result[2];
                } else {
                    $this->strErrMessage .= 'Invalid $_ARRAYLANG format (backend) - regex: '.$regex.'<br />';
                }
            }

            foreach ($frontendVarLines as $frontendVar) {
                if (trim($frontendVar) == '' || substr(trim($frontendVar), 0, 11) != '$_ARRAYLANG') {
                    continue;
                }
                preg_match($regex, $frontendVar, $result);
                if (!empty($result[1]) && !empty($result[2])) {
                    if (in_array($result[1], array_keys($backendVars))) {
                        if (in_array($result[2], $backendVars)) {
                            $bothVars[$result[1]] = $result[2];
                            unset($frontendVars[$result[1]]);
                            unset($backendVars[$result[1]]);
                            continue;
                        }
                    }
                    $frontendVars[$result[1]] = $result[2];
                } else {
                    $this->strErrMessage .= 'Invalid $_ARRAYLANG format (frontend ) - regex: '.$regex.'<br />';
                }
            }

            //_writeVarsToDB($name, $content, $moduleId, $isBackend, $isFrontend, $langId = 1, $status = 1)
            foreach ($backendVars as $varName => $varValue) {
                $this->_writeVarsToDB($varName, $varValue, $moduleId, 1, 0);
            }
            foreach ($frontendVars as $varName => $varValue) {
                $this->_writeVarsToDB($varName, $varValue, $moduleId, 0, 1);
            }
            foreach ($bothVars as $varName => $varValue) {
                $this->_writeVarsToDB($varName, $varValue, $moduleId, 1, 1);
            }

            if (isset($_POST['writeFiles']) && !empty($_POST['writeFiles'])) {
                $this->createFiles();
            }
            return true;
        }

        //single variable
        if (!empty($_POST['submit']) && !empty($_POST['name'])) {
            $name = contrexx_addslashes($_POST['name']);
            $adminzone = intval($_POST['backend']);
            $website = intval($_POST['frontend']);
            $moduleId = intval($_POST['moduleId']);

            // Add new variable
            if (empty($_POST['id'])) {
                $objResult = $objDatabase->Execute("SELECT name
                              FROM ".DBPREFIX."language_variable_names
                             WHERE name = '".$name."'
                               AND module_id =".$moduleId);
                if ($objResult !== false) {
                    if ($objResult->RecordCount()>=1) {
                        $this->strErrMessage= $_ARRAYLANG['TXT_LANGUAGE_VARIABLE_ALREADY_EXIST'];
                        return false;
                    } else {
                        $objDatabase->Execute("INSERT INTO ".DBPREFIX."language_variable_names
                                           SET name='".$name."',
                                               module_id='".$moduleId."',
                                               backend='".$adminzone."',
                                               frontend='".$website."'");
                        $varId = $objDatabase->Insert_ID();
                        foreach ($_POST['content'] as $langId => $content) {
                            $status = intval($_POST['status'][$langId]);
                            $objDatabase->Execute("INSERT INTO ".DBPREFIX."language_variable_content
                                                SET varid=".$varId.",
                                                    content='".contrexx_addslashes($content)."',
                                                    status=".$status.",
                                                    lang_id=".intval($langId));
                        }
                        $this->strOkMessage= $_ARRAYLANG['TXT_LANGUAGE_VARIABLE_ADDED_SUCCESSFUL'];
                        if (isset($_POST['writeFiles']) && !empty($_POST['writeFiles'])) {
                            $this->createFiles();
                        }
                        return true;
                    }
                }
            } else {
                // Update variable
                // Edit not add
                $id = intval($_POST['id']);

                $objDatabase->Execute("UPDATE ".DBPREFIX."language_variable_names
                               SET name='".$name."',
                                   module_id='".$moduleId."',
                                   backend='".$adminzone."',
                                   frontend='".$website."'
                             WHERE id=".$id);

                foreach ($_POST['content'] as $langId => $content) {
                    $status = intval($_POST['status'][$langId]);

                    $objDatabase->Execute("UPDATE ".DBPREFIX."language_variable_content
                                   SET content='".contrexx_addslashes($content)."',
                                       status='".$status."'
                                 WHERE varid=".$id."
                                   AND lang_id=".intval($langId));
                }
                $this->strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_UPDATED_SUCCESSFUL'];
                if (isset($_POST['writeFiles']) && !empty($_POST['writeFiles'])) {
                    $this->createFiles();
                }
                return true;
            }
        }
        return false;
    }


    /**
     * Write language variable to the database
     *
     * @param string $name      Name of the variable (_ARRAYLANG key)
     * @param string $content   Content of the variable
     * @param int $moduleId     ID of the module
     * @param int $isBackend    Whether the var is for the backend (1 yes, 0 no)
     * @param int $isFrontend   Whether the var is for the frontend (1 yes, 0 no)
     * @param int $langId       ID of the language of the variable (default 1)
     * @param int $status       Status of the variable (1 enabled, 0 disabled. default 1)
     * @return  boolean         True on success, false on failure
     */
    function _writeVarsToDB($name, $content, $moduleId, $isBackend, $isFrontend, $langId=1, $status=1)
    {
        global $objDatabase, $_ARRAYLANG;

        $objResult = $objDatabase->Execute("
            SELECT id, name, module_id, backend, frontend
              FROM ".DBPREFIX."language_variable_names
             WHERE name='$name'
               AND module_id=$moduleId
        ");
        if (!$objResult) {
            return false;
        }
        if ($objResult->RecordCount() > 0) {
            // var already exists, update it
            $objResult = $objDatabase->Execute("
                UPDATE ".DBPREFIX."language_variable_names
                   SET backend=$isBackend,
                       frontend=$isFrontend
                 WHERE id=".$objResult->fields['id']
            );
            if ($objResult) {
                foreach ($this->langIDs as $_langId) {
                    $_status = ($_langId == $langId) ? $status : 0;  //use $status only for the first language
                    $objResult = $objDatabase->Execute("
                        UPDATE ".DBPREFIX."language_variable_content
                           SET content='".addslashes($content)."',
                               status=$_status
                         WHERE varid=".$objResult->fields['id']."
                           AND lang_id=$_langId
                    ");
                    if (!$objResult) {
                        $this->strErrMessage .= "Database Error: ".$objDatabase->ErrorMsg().'<br />';
                        return false;
                    }
                }
            }
            $this->strErrMessage .= $name.' (ID: '.$objResult->fields['id'].'): '.$_ARRAYLANG['TXT_LANGUAGE_VARIABLE_ALREADY_EXIST'].', variable updated<br />';
            return true;
        }
        // var doesn't exist yet, insert it
        $objDatabase->Execute("
            INSERT INTO ".DBPREFIX."language_variable_names
               SET name='$name',
                   module_id='$moduleId',
                   backend='$isBackend',
                   frontend='$isFrontend'
        ");
        $varId = $objDatabase->Insert_ID();
        foreach ($this->langIDs as $_langId) {
            $_status = ($_langId == $langId ? $status : 0);
            $objDatabase->Execute("
                INSERT INTO ".DBPREFIX."language_variable_content
                   SET varid=$varId,
                       content='".addslashes($content)."',
                       status=$_status,
                       lang_id=$_langId
            ");
        }
        $this->strOkMessage .= "$name (ID: $varId): ".$_ARRAYLANG['TXT_LANGUAGE_VARIABLE_ADDED_SUCCESSFUL'].'<br />';
        return true;
    }


    /**
    * Sets the language add/mod variable page
    *
    * @global    ADONewConnection
    * @global    array
    * @global    \Cx\Core\Html\Sigma
    * @return    void
    */
    function modifyVariables()
    {
        global $objDatabase, $_ARRAYLANG;

        $variableName = "";
        $variableId = "";
        $variableModule = "";

        $this->template->loadTemplateFile('language_mod.html');
        $this->pageTitle = $_ARRAYLANG['TXT_ADD_LANGUAGE_VARIABLES'];

        $this->template->setVariable(array(
            'TXT_NAME'                   => $_ARRAYLANG['TXT_NAME'],
            'TXT_VALUE_CONTROL_LANGUAGE' => $_ARRAYLANG['TXT_VALUE_CONTROL_LANGUAGE'],
            'TXT_MODULE'                 => $_ARRAYLANG['TXT_MODULE'],
            'TXT_SELECT_MODULE'          => $_ARRAYLANG['TXT_SELECT_MODULE'],
            'TXT_STORE'                  => $_ARRAYLANG['TXT_SAVE'],
            'TXT_WEB_PAGES'              => $_ARRAYLANG['TXT_WEB_PAGES'],
            'TXT_ADMINISTRATION_PAGES'   => $_ARRAYLANG['TXT_ADMINISTRATION_PAGES'],
            'TXT_APPLICATION_RANGE'      => $_ARRAYLANG['TXT_APPLICATION_RANGE'],
            'TXT_LANGUAGE_NAME_REQUIRED' => $_ARRAYLANG['TXT_LANGUAGE_NAME_REQUIRED'],
            'TXT_APPLICATION_RANGE_REQUIRED'  => $_ARRAYLANG['TXT_APPLICATION_RANGE_REQUIRED'],
            'TXT_WRITE_VARIABLES_TO_FILES' => $_ARRAYLANG['TXT_WRITE_VARIABLES_TO_FILES']
        ));

        $objResult = $objDatabase->Execute("SELECT id,name,lang FROM ".DBPREFIX."languages ORDER BY id");
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $arrayLang[$objResult->fields['id']]=$objResult->fields['name']." (".$objResult->fields['lang'].")";
                $objResult->MoveNext();
            }
        }

        $lastId = 1;

        if (isset($_GET['id']))
        //---------------------------
        // mod status
        //---------------------------
        {
            $this->template->setVariable("TXT_LANGUAGE_SETTING", $_ARRAYLANG['TXT_MOD_LANGUAGE_VARIABLES']);
            $variableId = intval($_GET['id']);
            $objResult = $objDatabase->SelectLimit("SELECT id,
                               name,
                               module_id,
                               backend,
                               frontend
                          FROM ".DBPREFIX."language_variable_names
                          WHERE id = ".$variableId, 1);
            if ($objResult !== false) {
                while (!$objResult->EOF) {
                    $variableName=$objResult->fields['name'];
                    $variableAdminzone=$objResult->fields['backend'];
                    $variableWebsite=$objResult->fields['frontend'];
                    $variableModule=$objResult->fields['module_id'];
                    $objResult->MoveNext();
                }
            }
            $objResult = $objDatabase->Execute("SELECT content,
                               lang_id,
                               status
                          FROM ".DBPREFIX."language_variable_content
                         WHERE varid = ".$variableId."
                      ORDER BY varid");
            if ($objResult !== false) {
                while (!$objResult->EOF) {
                    $variableContent[$objResult->fields['lang_id']]=$objResult->fields['content'];
                    $variableStatus[$objResult->fields['lang_id']]=$objResult->fields['status'];
                    $objResult->MoveNext();
                }
            }
            foreach ($arrayLang as $k => $v) {
                $checked="";
                if ($variableStatus[$k]==1) {
                    $checked="checked";
                }
                //echo htmlspecialchars($variableContent[$k], ENT_QUOTES, CONTREXX_CHARSET);
                $content=htmlspecialchars($variableContent[$k], ENT_QUOTES, CONTREXX_CHARSET);
                $strLangInputFields .="<input type='text' name='content[$k]' size=80 value=\"".$content."\" />&nbsp;\n
                                       <input type='checkbox' name='status[$k]' id='status_$k' value='1' ".$checked." /> <label for='status_$k'>$v</label><br />\n";
                $lastId = $variableId;
            }
        } else
        //---------------------------
        // Add status
        //---------------------------
        {
            $this->template->setVariable("TXT_LANGUAGE_SETTING", $_ARRAYLANG['TXT_ADD_LANGUAGE_VARIABLES']);
            foreach ($arrayLang as $k => $v) {
                $strLangInputFields .="<input type='text' onchange=\"copyValues($k, this.value)\" name='content[$k]' size=80 value='' />&nbsp;\n
                                       <input type='checkbox' onchange=\"check($k)\" name='status[$k]' id='status_$k' value='1' checked /> <label for='status_$k'>$v</label><br />\n";
                $lastId = $lastId < $k ? $k : $lastId;

            }
            $variableAdminzone = isset($_REQUEST['backend']) ? $_REQUEST['backend'] : '';
            $variableWebsite = isset($_REQUEST['frontend']) ? $_REQUEST['frontend'] : '';
        }

        if ($variableAdminzone==1) {
            $variableAdminzone="checked";
        } else {
            $variableAdminzone="";
        }
        if ($variableWebsite==1) {
            $variableWebsite="checked";
        } else {
            $variableWebsite="";
        }

        $this->template->setVariable(array(
            'LANGUAGE_VARIABLE_NAME'    => $variableName,
            'LANGUAGE_INPUT_FIELDS'        => $strLangInputFields,
            'LANGUAGE_ADMINZONE'        => $variableAdminzone,
            'LANGUAGE_WEBSITE'            => $variableWebsite,
            'LANGUAGE_MODULES_MENU'        => $this->getSearchOptionMenu("modules",$variableModule),
            'LANGUAGE_VARIABLE_ID'        => $variableId,
            'LAST_ID'                    => $lastId+1
        ));
    }


    function _getVarOfId()
    {
        $_GET['id'] = isset($_REQUEST['langVarId']) ? intval($_REQUEST['langVarId']) : 0;

        if ($_GET['id'] > 0) {
            \Permission::checkAccess(48, 'static');
            $this->addUpdateVariable();
            $this->modifyVariables();
        }
    }


    /**
     * Set the language variable default page
     *
     * @global    array
     * @global    ADONewConnection
     * @global    \Cx\Core\Html\Sigma
     * @return    void
     */
    function listVariables()
    {
        global $_ARRAYLANG, $objDatabase;

        //init variables
        $q_lang = "";
        $q_module = "";
        $q_status = "";
        $q_zone = "";
        $i=0;
        $zoneMenu ="";
        $selected1="";
        $selected2="";
        $selected3="";


        $this->template->loadTemplateFile('language_list.html');
        $this->pageTitle = $_ARRAYLANG['TXT_VARIABLE_LIST'];
        
        if (!isset($_SESSION['lang'])) {
            $_SESSION['lang'] = array();
        }

        if (!isset($_SESSION['lang']['term'])) $_SESSION['lang']['term']="";
        if (!isset($_SESSION['lang']['langId'])) $_SESSION['lang']['langId']="";
        if (!isset($_SESSION['lang']['status'])) $_SESSION['lang']['status']="";
        if (!isset($_SESSION['lang']['zone'])) $_SESSION['lang']['zone']="both";
        if (!isset($_SESSION['lang']['moduleId'])) $_SESSION['lang']['moduleId'] = "";

        if (isset($_POST['term'])) {
            $_SESSION['lang']['term']= contrexx_addslashes($_POST['term']);
        }
        if (isset($_POST['lang'])) {
            $_SESSION['lang']['langId']=intval($_POST['lang']);
        }
        if (isset($_POST['status'])) {
            $_SESSION['lang']['status']=intval($_POST['status']);
        }
        if (isset($_POST['zone'])) {
            $_SESSION['lang']['zone']= contrexx_addslashes($_POST['zone']);
        }
        if (isset($_POST['module'])) {
            $_SESSION['lang']['moduleId'] = intval($_POST['module']);
        }

        $term = $_SESSION['lang']['term'];
        $lang = $_SESSION['lang']['langId'];
        $status = $_SESSION['lang']['status'];
        $zone = $_SESSION['lang']['zone'];
        $module = $_SESSION['lang']['moduleId'];

        if ($zone=="frontend") {
            $selected1="selected";
        } elseif ($zone == "backend") {
            $selected2="selected";
        } elseif ($zone == "both" || $zone == "") {
            $zone = "both";
            $selected3="selected";
        }

        $zoneMenu .="<option value='both' ".$selected3.">".$_ARRAYLANG['TXT_SECTION']."</option>\n";
        $zoneMenu .="<option value='frontend' ".$selected1.">".$_ARRAYLANG['TXT_WEB_PAGES']."</option>\n";
        $zoneMenu .="<option value='backend' ".$selected2.">".$_ARRAYLANG['TXT_ADMINISTRATION_PAGES']."</option>\n";
        $this->template->setVariable("LANGUAGE_ZONE_MENU", $zoneMenu);

        //Begin language varibales
        $this->template->setVariable(array(
            'TXT_CONFIRM_DELETE_DATA'                    => $_ARRAYLANG['TXT_CONFIRM_DELETE_DATA'],
            'TXT_ACTION_IS_IRREVERSIBLE'                 => $_ARRAYLANG['TXT_ACTION_IS_IRREVERSIBLE'],
            'TXT_ATTENTION_SYSTEM_FUNCTIONALITY_AT_RISK' => $_ARRAYLANG['TXT_ATTENTION_SYSTEM_FUNCTIONALITY_AT_RISK'],
            'TXT_MODULE'                                 => $_ARRAYLANG['TXT_MODULE'],
            'TXT_LANGUAGE'                               => $_ARRAYLANG['TXT_LANGUAGE'],
            'TXT_STATUS'                                 => $_ARRAYLANG['TXT_STATUS'],
            'TXT_CONTROLLED'                             => $_ARRAYLANG['TXT_CONTROLLED'],
            'TXT_OPEN_ISSUE'                             => $_ARRAYLANG['TXT_OPEN_ISSUE'],
            'TXT_LANGUAGE_DEPENDANT_SYSTEM_VARIABLES'    => $_ARRAYLANG['TXT_LANGUAGE_DEPENDANT_SYSTEM_VARIABLES'],
            'TXT_FOUND'                                  => $_ARRAYLANG['TXT_FOUND'],
            'TXT_NAME'                                   => $_ARRAYLANG['TXT_NAME'],
            'TXT_VALUE'                                  => $_ARRAYLANG['TXT_VALUE'],
            'TXT_DISPLAY'                                => $_ARRAYLANG['TXT_DISPLAY'],
            'TXT_ADMIN'                                  => $_ARRAYLANG['TXT_ADMINISTRATION_PAGES'],
            'TXT_PUBLIC'                                 => $_ARRAYLANG['TXT_WEB_PAGES']
        ));
        //End language variables

        if (isset($_POST['Submit'])) {
            if (empty($lang)) {
                $objResult = $objDatabase->Execute("SELECT id FROM ".DBPREFIX."languages WHERE is_default='true'");
                if ($objResult !== false) {
                    while (!$objResult->EOF) {
                        $q_lang = "AND con.lang_id=".intval($objResult->fields['id'])." ";
                        $objResult->MoveNext();
                    }
                }
            } else {
                $q_lang = "AND con.lang_id=".intval($lang)." ";
            }

            if ($zone <> "both") {
                $q_zone = "AND nam.$zone=1 ";
            }

            if ($module != 0) {
                $q_module = "AND nam.module_id = ".$module." ";
            }

            if ($status=="0" || $status=="1") {
                $q_status = "AND con.status=".intval($status)." ";
            }

            $q = "SELECT con.content AS content,
                         con.status AS status,
                         con.lang_id AS lang,
                         nam.name AS name,
                         nam.id AS varid,
                         modu.name AS module,
                         nam.backend AS backend,
                         nam.frontend AS frontend
                  FROM ".DBPREFIX."language_variable_content AS con,
                       ".DBPREFIX."language_variable_names AS nam,
                       ".DBPREFIX."modules AS modu
                  WHERE modu.id=nam.module_id
                    AND con.varid=nam.id
                    AND (nam.name LIKE '%".$term."%' OR con.content LIKE '%".$term."%') ".$q_zone.$q_lang.$q_module.$q_status."
                  ORDER BY nam.id";

            $objResult = $objDatabase->Execute($q);
            if ($objResult !== false && $objResult->RecordCount() > 0) {
                $numRows = $objResult->RecordCount();
                while (!$objResult->EOF) {
                    if (($i % 2) == 0) {$class="row1";} else {$class="row2";}

                    if (intval($objResult->fields['backend'])==1) {
                        $this->template->setVariable("LANGUAGE_ADMIN","<img alt='' src='../core/Core/View/Media/icons/check.gif' />");
                    }
                    if (intval($objResult->fields['frontend'])==1) {
                        $this->template->setVariable("LANGUAGE_WEBSITE","<img alt='' src='../core/Core/View/Media/icons/check.gif' />");
                    }
                    $this->template->setVariable(array(
                        'LANGUAGE_ROWCLASS'        => $class,
                        'LANGUAGE_ID'            => $objResult->fields['varid'],
                        'LANGUAGE_VARIABLENAME'    => $objResult->fields['name'],
                        'LANGUAGE_CONTENT'        => htmlspecialchars($objResult->fields['content'], ENT_QUOTES, CONTREXX_CHARSET),
                        'LANGUAGE_MODULE'        => $objResult->fields['module'],
                        'LANGUAGE_LANG'            => $this->arrLang[$objResult->fields['lang']]
                    ));
                    // not carefully checked variable
                    if (intval($objResult->fields['status']==1)) {
                        $langStatus ="<img alt='' src=\"../core/Core/View/Media/icons/led_green.gif\" />";
                    } else {
                        $langStatus ="<img alt='' src=\"../core/Core/View/Media/icons/led_red.gif\" />";
                    }
                    $this->template->setVariable("LANGUAGE_STATUS",$langStatus);
                    $this->template->parse('languageRow');
                    $i++;
                    $objResult->MoveNext();
                }
            } else {
                $this->template->hideBlock('languageSearchTable');
            }
        } else {
            $this->template->hideBlock('languageSearchTable');
        }
        $this->template->setVariable(array(
            'LANGUAGE_STATS'        => $numRows,
            'LANGUAGE_MODULES_MENU'    => $this->getSearchOptionMenu("modules",$module),
            'LANGUAGE_LANG_MENU'    => $this->getSearchOptionMenu("languages",$lang),
            'LANGUAGE_SEARCHTERM'    => $term
        ));
    }


    /**
     * Set the language list page
     *
     * @global    array
     * @global    ADONewConnection
     * @global    \Cx\Core\Html\Sigma
     * @return    void
     */
    function languageOverview()
    {
        global $_ARRAYLANG, $objDatabase;
        // init vars
        $i=0;

        \JS::activate('cx');
        $cxjs = \ContrexxJavascript::getInstance();
        $cxjs->setVariable('copyTitle', $_ARRAYLANG['TXT_LANGUAGE_COPY_TITLE'], 'language/lang');
        $cxjs->setVariable('copyText', $_ARRAYLANG['TXT_LANGUAGE_COPY_TEXT'], 'language/lang');
        $cxjs->setVariable('copySuccess', $_ARRAYLANG['TXT_LANGUAGE_COPY_SUCCESS'], 'language/lang');
        $cxjs->setVariable('linkTitle', $_ARRAYLANG['TXT_LANGUAGE_LINK_TITLE'], 'language/lang');
        $cxjs->setVariable('linkText', $_ARRAYLANG['TXT_LANGUAGE_LINK_TEXT'], 'language/lang');
        $cxjs->setVariable('linkSuccess', $_ARRAYLANG['TXT_LANGUAGE_LINK_SUCCESS'], 'language/lang');
        $cxjs->setVariable('warningTitle', $_ARRAYLANG['TXT_LANGUAGE_WARNING_TITLE'], 'language/lang');
        $cxjs->setVariable('warningText', $_ARRAYLANG['TXT_LANGUAGE_WARNING_TEXT'], 'language/lang');
        $cxjs->setVariable('waitTitle', $_ARRAYLANG['TXT_LANGUAGE_WAIT_TITLE'], 'language/lang');
        $cxjs->setVariable('waitText', $_ARRAYLANG['TXT_LANGUAGE_WAIT_TEXT'], 'language/lang');
        $cxjs->setVariable('yesOption', $_ARRAYLANG['TXT_YES'], 'language/lang');
        $cxjs->setVariable('noOption', $_ARRAYLANG['TXT_NO'], 'language/lang');
        $cxjs->setVariable('langRemovalLabel', $_ARRAYLANG['TXT_LANGUAGE_MANAGER_LABEL_LANG_REMOVAL'], 'language/lang');
        $cxjs->setVariable('langRemovalContent', $_ARRAYLANG['TXT_LANGUAGE_MANAGER_LANG_REMOVAL_CONTENT'], 'language/lang');
        
        $this->template->loadTemplateFile('language_langlist.html');
        $this->pageTitle = $_ARRAYLANG['TXT_LANGUAGE_LIST'];
        
        if (!$this->isInFullMode()) {
            $this->hideVariables = true;
            $this->template->hideBlock('extendedTitles');
            $this->template->hideBlock('extendedHeaders');
        } else {
            $this->template->touchBlock('extendedTitles');
        }
        
        //begin language variables
        $this->template->setVariable(array(
            'TXT_ADD_NEW_LANGUAGE'             => $_ARRAYLANG['TXT_ADD_NEW_LANGUAGE'],
            'TXT_NAME'                         => $_ARRAYLANG['TXT_NAME'],
            'TXT_SHORT_NAME'                 => $_ARRAYLANG['TXT_SHORT_NAME'],
            'TXT_CHARSET'                    => $_ARRAYLANG['TXT_CHARSET'],
            'TXT_ADD'                           => $_ARRAYLANG['TXT_ADD'],
            'TXT_LANGUAGE_LIST'              => $_ARRAYLANG['TXT_LANGUAGE_LIST'],
            'TXT_ID'                         => $_ARRAYLANG['TXT_ID'],
            'TXT_SHORT_FORM'                 => $_ARRAYLANG['TXT_SHORT_FORM'],
            'TXT_STANDARD_LANGUAGE'          => $_ARRAYLANG['TXT_STANDARD_LANGUAGE'],
            'TXT_ACTION'                     => $_ARRAYLANG['TXT_ACTION'],
            'TXT_ACCEPT_CHANGES'             => $_ARRAYLANG['TXT_ACCEPT_CHANGES'],
            'TXT_REMARK'                     => $_ARRAYLANG['TXT_REMARK'],
            'TXT_ADD_DELETE_LANGUAGE_REMARK' => $_ARRAYLANG['TXT_ADD_DELETE_LANGUAGE_REMARK'],
            'TXT_CONFIRM_DELETE_DATA'        => $_ARRAYLANG['TXT_CONFIRM_DELETE_DATA'],
            'TXT_ACTION_IS_IRREVERSIBLE'     => $_ARRAYLANG['TXT_ACTION_IS_IRREVERSIBLE'],
            'TXT_VALUE'                      => $_ARRAYLANG['TXT_VALUE'],
            'TXT_MODULE'                     => $_ARRAYLANG['TXT_MODULE'],
            'TXT_LANGUAGE'                   => $_ARRAYLANG['TXT_LANGUAGE'],
            'TXT_STATUS'                     => $_ARRAYLANG['TXT_STATUS'],
            'TXT_VIEW'                       => $_ARRAYLANG['TXT_VIEW'],
            'TXT_CONTROLLED'                 => $_ARRAYLANG['TXT_CONTROLLED'],
            'TXT_OPEN_ISSUE'                 => $_ARRAYLANG['TXT_OPEN_ISSUE'],
            'TXT_SHORT_NAME'                 => $_ARRAYLANG['TXT_SHORT_NAME'],
            'TXT_LANGUAGE_DEPENDANT_SYSTEM_VARIABLES'=> $_ARRAYLANG['TXT_LANGUAGE_DEPENDANT_SYSTEM_VARIABLES'],
            'TXT_ADMINISTRATION_PAGES'       => $_ARRAYLANG['TXT_ADMINISTRATION_PAGES'],
            'TXT_WEB_PAGES'                  => $_ARRAYLANG['TXT_WEB_PAGES'],
            'TXT_SECTION'                    => $_ARRAYLANG['TXT_SECTION'],            
            'TXT_CORE_FALLBACK'              => $_ARRAYLANG['TXT_CORE_FALLBACK'],
            'TXT_LANGUAGE_MANAGER_OK'        => $_ARRAYLANG['TXT_LANGUAGE_MANAGER_OK']
        ));
        $this->template->setGlobalVariable(array(
            'TXT_DEFAULT_LANGUAGE' => $_ARRAYLANG['TXT_STANDARD_LANGUAGE'],
            'TXT_CORE_NONE'        => $_ARRAYLANG['TXT_CORE_NONE'],
            'CMD'                  => contrexx_input2xhtml($_GET['cmd']),
            'TXT_LANGUAGE_ACTION_COPY'       => $_ARRAYLANG['TXT_LANGUAGE_ACTION_COPY'],
            'TXT_LANGUAGE_ACTION_LINK'       => $_ARRAYLANG['TXT_LANGUAGE_ACTION_LINK'],
        ));        
        //end language variables
        if ($this->hideVariables == true) {
            $this->template->setGlobalVariable(array('LANGUAGE_ADMIN_STYLE' => 'display: none'));
        } else {
            $this->template->setGlobalVariable(array('LANGUAGE_ADMIN_STYLE' => 'display: block'));
        }
                
        $arrLanguages  = \FWLanguage::getActiveFrontendLanguages();
        $this->template->setVariable('LANGUAGE_MANAGER_ACTIVE_LANGIDS' , implode(', ', array_keys($arrLanguages)));
        $objResult = $objDatabase->Execute("SELECT * FROM ".DBPREFIX."languages ORDER BY id");
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $checked = "";
                if ($objResult->fields['is_default']=="true") {
                  $checked = "checked";
                }
                $status ="<input type='radio' name='langDefaultStatus' onchange='updateCurrent();' value='".$objResult->fields['id']."' $checked />";

                $checked = "";
                if ($objResult->fields['frontend']==1) {
                  $checked = "checked";
                }
                $activeStatus ="<input type='checkbox' name='langActiveStatus[".$objResult->fields['id']."]' onchange='updateCurrent();' value='1' $checked />";
                $checked = "";
                if ($objResult->fields['backend']==1) {
                  $checked = "checked";
                }
                
                $selectedLang = '';                
                switch ($objResult->fields['fallback']) {
                    case '':
                        $this->template->setVariable('NONE_SELECTED', 'selected="selected"');
                        break;
                    case '0':
                        $this->template->setVariable('LANGUAGE_DEFAULT_SELECTED', 'selected="selected"');
                        break;        
                    default:
                        $selectedLang = $objResult->fields['fallback'];                        
                }
                // set fallback language drop down                
                foreach ($arrLanguages as $langId => $arrLanguage) {      
                    $selected = ($langId == $selectedLang) ? 'selected="selected"' : '';
                    $this->template->setVariable(array(
                        'LANGUAGE_LANG_ID'         => $langId,
                        'LANGUAGE_LANG_OPTION'     => contrexx_raw2xhtml($arrLanguage['name']),
                        'LANGUAGE_OPTION_SELECTED' => $selected
                    ));
                    $this->template->parse('fallbackLanguages');
                }                    
                
                $adminStatus ="<input type='checkbox' name='langAdminStatus[".$objResult->fields['id']."]' value='1' $checked />";
                $this->template->setVariable(array(
                    'LANGUAGE_ROWCLASS'            => 'row'.(($i++ % 2)+1),
                    'LANGUAGE_LANG_ID'            => $objResult->fields['id'],
                    'LANGUAGE_LANG_NAME'        => $objResult->fields['name'],
                    'LANGUAGE_LANG_SHORTNAME'   => $objResult->fields['lang'],
                    'LANGUAGE_LANG_CHARSET'        => $objResult->fields['charset'],
                    'LANGUAGE_LANG_STATUS'        => $status,
                    'LANGUAGE_ACTIVE_STATUS'    => $activeStatus,
                    'LANGUAGE_ADMIN_STATUS'        => $adminStatus
                ));
                                
                if (!$this->isInFullMode()) {
                    $this->template->hideBlock('extendedOptions');
                }
                $this->template->parse('languageRow');
                $objResult->MoveNext();
            }
        }    
    }


    /**
     * add and modify language values
     *
     * @global  array
     * @global  ADONewConnection
     * @return  boolean     True on success, false on failure
     */
    function modifyLanguage()
    {
        global $_ARRAYLANG, $_CONFIG, $objDatabase;

        $langRemovalStatus = isset($_POST['removeLangVersion']) ? contrexx_input2raw($_POST['removeLangVersion']) : false;
        if (!empty($_POST['submit']) AND (isset($_POST['addLanguage']) && $_POST['addLanguage']=="true")) {
            //-----------------------------------------------
            // Add new language with all variables
            //-----------------------------------------------
            if (!empty($_POST['newLangName']) AND !empty($_POST['newLangShortname'])) {
                $newLangShortname = addslashes(strip_tags($_POST['newLangShortname']));
                $newLangName = addslashes(strip_tags($_POST['newLangName']));
                $newLangCharset = addslashes(strip_tags($_POST['newLangCharset']));
                $objResult = $objDatabase->Execute("SELECT lang FROM ".DBPREFIX."languages WHERE lang='".$newLangShortname."'");
                if ($objResult !== false) {
                    if ($objResult->RecordCount()>=1) {
                        $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'];
                        return false;
                    } else {
                        $objDatabase->Execute("INSERT INTO ".DBPREFIX."languages SET lang='".$newLangShortname."',
                                                                           name='".$newLangName."',
                                                                           charset='".$newLangCharset."',
                                                                           is_default='false'");
                        $newLanguageId = $objDatabase->Insert_ID();
                        if (!empty($newLanguageId)) {
                            $objResult = $objDatabase->SelectLimit("SELECT id FROM ".DBPREFIX."languages WHERE is_default='true'", 1);
                            if ($objResult !== false && !$objResult->EOF) {
                                $defaultLanguage=$objResult->fields['id'];

                                $objResult = $objDatabase->Execute("SELECT varid,content,module FROM ".DBPREFIX."language_variable_content WHERE 1 AND lang=".$defaultLanguage);
                                if ($objResult !== false) {
                                    while (!$objResult->EOF) {
                                        $arrayLanguageContent[$objResult->fields['varid']]=stripslashes($objResult->fields['content']);
                                        $arrayLanguageModule[$objResult->fields['varid']]=$objResult->fields['module'];
                                        $objResult->MoveNext();
                                    }
                                    foreach ($arrayLanguageContent as $varid => $content) {
                                        $LanguageModule = $arrayLanguageModule[$varid];
                                        $objDatabase->Execute("INSERT INTO ".DBPREFIX."language_variable_content SET varid=".$varid.", content='".addslashes($content)."', module=".$LanguageModule.", lang=".$newLanguageId.", status=0");
                                    }
                                    $this->strOkMessage = $_ARRAYLANG['TXT_NEW_LANGUAGE_ADDED_SUCCESSFUL'];
                                    return true;
                                }
                            }
                        } else {
                            $this->strErrMessage = $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'];
                            return false;
                        }
                    }
                }
            }
        } elseif (!empty($_POST['submit']) AND ( $_POST['modLanguage'] == "true")) {
            $eventArgs       = array('langRemovalStatus' => $langRemovalStatus);
            $frontendLangIds = array_keys(\FWLanguage::getActiveFrontendLanguages());
            $postLangIds     = array_keys($_POST['langActiveStatus']);
            foreach (array_keys(\FWLanguage::getLanguageArray()) as $langId) {
                $isLangInPost     = in_array($langId, $postLangIds);
                $isLangInFrontend = in_array($langId, $frontendLangIds);
                if ($isLangInPost == $isLangInFrontend) {
                    continue;
                }
                $eventArgs['langData'][] = array(
                    'langId' => $langId,
                    'status' => $isLangInPost && !$isLangInFrontend
                );
            }

            //Trigger the event 'languageStatusUpdate' 
            //if the language is activated/deactivated for frontend 
            if (!empty($eventArgs)) {
                $evm = \Cx\Core\Core\Controller\Cx::instanciate()->getEvents();
                $evm->triggerEvent(
                    'languageStatusUpdate',
                    array(
                        $eventArgs,
                        new \Cx\Core\Model\RecursiveArrayAccess(array())
                    )
                );
            }

            //-----------------------------------------------
            // Update languages
            //-----------------------------------------------
            foreach ($_POST['langName'] as $id => $name) {
                $active = 0;
                if (isset($_POST['langActiveStatus'][$id]) && $_POST['langActiveStatus'][$id]==1 ) {
                    $languageCode = \FWLanguage::getLanguageCodeById($id);
                    $pageRepo = \Env::get('em')->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
                    $alias = $pageRepo->findBy(array(
                        'type' => \Cx\Core\ContentManager\Model\Entity\Page::TYPE_ALIAS,
                        'slug' => $languageCode,
                    ), true);

                    if (count($alias)) {
                        if (is_array($alias)) $alias = $alias[0];
                        $id   = $alias->getNode()->getId();
                        $config = \Env::get('config');
                        $link = 'http://' . $config['domainUrl'] . ASCMS_PATH_OFFSET . '/' . $alias->getSlug();
                        $lang = \Env::get('lang');
                        $this->strErrMessage  =
                            $lang['TXT_CORE_REMOVE_ALIAS_TO_ACTIVATE_LANGUAGE'] . ':<br />
                            <a href="index.php?cmd=Alias&act=modify&id=' . $id . '" target="_blank">' . $link . '</a>';
                        return false;
                    }

                    $active = 1;
                }
                $status = "false";
                if ($_POST['langDefaultStatus']==$id) {
                    $status = "true";
                }
                $adminstatus = 0;
                if (isset($_POST['langAdminStatus'][$id]) && $_POST['langAdminStatus'][$id]==1) {
                    $adminstatus = 1;
                }
                $fallBack = (isset($_POST['fallBack'][$id]) && $_POST['fallBack'][$id] != "" ) ? intval($_POST['fallBack'][$id]) : 'NULL';                
                $objDatabase->Execute("UPDATE ".DBPREFIX."languages SET 
                                        name='".$name."',
                                        frontend=".$active.",
                                        is_default='".$status."',
                                        backend='".$adminstatus."',
                                        fallback=".$fallBack."
                                        WHERE id=".$id);
            }
            $this->strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_UPDATED_SUCCESSFUL'];
            \FWLanguage::init();
            return true;
        }
        return false;
    }


    /**
     * Build a list of options from the languages or modules tables
     *
     * @param   string  $dbTableName    'languages' or 'modules'
     * @param   string  $selectedOption The default value to be selected
     * @return  string  The '<option>...</option>...' string created
     */
    function getSearchOptionMenu($dbTableName, $selectedOption="")
    {
        global $objDatabase;
        $strMenu = "";
        if ($dbTableName=="languages" OR $dbTableName=="modules") {
            $q = "SELECT id, name FROM ".DBPREFIX.$dbTableName." WHERE 1 ORDER BY id";
            $objResult = $objDatabase->Execute($q);
            if ($objResult !== false) {
                while (!$objResult->EOF) {
                    $selected = "";
                    if ($selectedOption==$objResult->fields['id'] || $objResult->fields['id'] == $_REQUEST['moduleId']) {
                        $selected = "selected";
                    }
                    if ($objResult->fields['id']!=0) {
                        $name = $objResult->fields['name'];
                        if ($dbTableName == 'modules') {
                            switch ($objResult->fields['name']) {
                                case 'Media1':
                                    $name = 'Media';
                                    break;

                                case 'Media2':
                                case 'Media3':
                                    $name = '';
                                    break;
                            }
                        }
                        if (!empty($name)) {
                            $strMenu .="<option value=\"".$objResult->fields['id']."\" ".$selected.">".$name."</option>\n";
                        }
                    }
                    $objResult->MoveNext();
                }
            }
        }
        return $strMenu;
    }


    /**
     * Checks whether the language directory is valid and writeable
     *
     * @return  boolean     True on success, false on failure
     */
    function checkPermissions()
    {
        if (is_writeable($this->filePath) AND
           is_dir($this->filePath)) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * createXML: parse out the XML
     *
     * @global  ADONewConnection
     * @global  array
     * @return  void
     */
    function createFiles()
    {
        global $objDatabase, $_ARRAYLANG;

        $arrModules = array();
        $arrLanguages = array();
        $arrModulesPath = array();
        $arrModuleVariables = array();
        $arrErrorFiles = array();
        $objFile = new File();

        $strHeader = "/**\n* Contrexx CMS\n* generated date ".date('r',time())."\n**/\n\n";

        // generate the arrays $arrModulesPath and $arrModules
        $query = "SELECT id, name, is_core FROM ".DBPREFIX."modules";
        $objResult = $objDatabase->Execute($query);
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                if (strlen($objResult->fields['name'])>0) {
                    switch($objResult->fields['name']) {
                        case 'core':
                            $arrModulesPath[$objResult->fields['name']]['sys'] = ASCMS_DOCUMENT_ROOT;
                            $arrModulesPath[$objResult->fields['name']]['web'] = ASCMS_PATH_OFFSET;
                            break;
                        case 'Media1':
                            $arrModulesPath['Media']['sys'] = ASCMS_CORE_MODULE_PATH.'/Media';
                            $arrModulesPath['Media']['web'] = ASCMS_CORE_MODULE_WEB_PATH.'/Media';
                            $objResult->fields['name'] = 'Media';
                            break;
                        case 'Media2':
                        case 'Media3':
                            $objResult->fields['name'] = "";
                            break;
                        default:
                        $arrModulesPath[$objResult->fields['name']]['sys'] = ($objResult->fields['is_core'] == 1 ? ASCMS_CORE_MODULE_PATH : ASCMS_MODULE_PATH).'/'.$objResult->fields['name'];
                        $arrModulesPath[$objResult->fields['name']]['web'] = ($objResult->fields['is_core'] == 1 ? ASCMS_CORE_MODULE_WEB_PATH : ASCMS_MODULE_WEB_PATH).'/'.$objResult->fields['name'];
                    }
                    if (!empty($objResult->fields['name'])) {
                        $arrModulesPath[$objResult->fields['name']]['sys'] .= '/lang/';
                        $arrModulesPath[$objResult->fields['name']]['web'] .= '/lang/';
                    }
                }
                $arrModules[$objResult->fields['id']] = array(
                    'id'    =>    $objResult->fields['id'],
                    'name'    =>    $objResult->fields['name']
                );
                $objResult->MoveNext();
            }
        }

        // get language array
        $query = "SELECT id, lang FROM ".DBPREFIX."languages";
        $objResult = $objDatabase->Execute($query);
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $arrLanguages[$objResult->fields['id']] = array(
                    'id'    => $objResult->fields['id'],
                    'lang'    => $objResult->fields['lang']
                );
                $objResult->MoveNext();
            }
        }

        // get language variables
        $query = "SELECT vn.name, vn.module_id, vn.backend, vn.frontend, vc.content, vc.lang_id
                    FROM ".DBPREFIX."language_variable_names AS vn,
                         ".DBPREFIX."language_variable_content AS vc
                   WHERE vn.id=vc.varid";

        // generate array $arrModuleVariables including the variables
        $objResult = $objDatabase->Execute($query);
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                if ($objResult->fields['module_id'] == 0) {
                    $moduleId = 1;
                } else {
                    $moduleId = $objResult->fields['module_id'];
                }
                if ($objResult->fields['backend'] == 1) {
                    $arrModuleVariables[$moduleId][$objResult->fields['lang_id']]['backend'][$objResult->fields['name']] = $objResult->fields['content'];
                }
                if ($objResult->fields['frontend'] == 1) {
                    $arrModuleVariables[$moduleId][$objResult->fields['lang_id']]['frontend'][$objResult->fields['name']] = $objResult->fields['content'];
                }
                $objResult->MoveNext();
            }
        }
        // generate array $arrOutput with the data to write into files
        foreach ($arrModuleVariables as $moduleId => $arrLanguageVariables) {
            foreach ($arrLanguageVariables as $langId => $arrModeVariables) {
                $filePath = $arrModulesPath[$arrModules[$moduleId]['name']]['sys'].$arrLanguages[$langId]['lang'].'/';
                $webFilePath = $arrModulesPath[$arrModules[$moduleId]['name']]['web'].$arrLanguages[$langId]['lang'].'/';
                if (!file_exists($filePath)) {
                    $objFile->mkDir($arrModulesPath[$arrModules[$moduleId]['name']]['sys'], $arrModulesPath[$arrModules[$moduleId]['name']]['web'], $arrLanguages[$langId]['lang'].'/');
                }
                foreach ($arrModeVariables as $strMode => $arrVariables) {
                    $fileName = $strMode.".php";
                    $arrOutput[$filePath.$fileName]['filename'] = $fileName;
                    $arrOutput[$filePath.$fileName]['path'] = $filePath;
                    $arrOutput[$filePath.$fileName]['webpath'] = $webFilePath;
                    foreach ($arrVariables as $strName => $strContent) {
                        //$strContent = stripslashes(stripslashes($strContent));
                        //$strContent = str_replace("\"", "\\\"", $strContent);
                        //$strContent = addslashes($strContent);
                        if (isset($arrOutput[$filePath.$fileName]['content'])) {
                            $arrOutput[$filePath.$fileName]['content'] .= "$"."_ARRAYLANG['".$strName."'] = \"".$strContent."\";\n";
                        } else {
                            $arrOutput[$filePath.$fileName]['content'] = "$"."_ARRAYLANG['".$strName."'] = \"".$strContent."\";\n";
                        }
                    }
                }
            }
        }
        unset($arrModuleVariables);
        // write variables to files
        foreach ($arrOutput as $file => $strOutput) {
            $objFile->setChmod($strOutput['path'], $strOutput['webpath'], '');
            $objFile->setChmod($strOutput['path'], $strOutput['webpath'], $strOutput['filename']);
            $fileHandle = fopen($file,"w");
            if ($fileHandle) {
                @fwrite($fileHandle,"<?php\n".$strHeader.$strOutput['content']."?>\n");
                @fclose($fileHandle);
            } else {
                array_push($arrErrorFiles,$file);
            }
        }

        unset($arrOutput);
        if (count($arrErrorFiles)>0) {
            foreach ($arrErrorFiles as $file) {
                $this->strErrMessage .= "<br />".$_ARRAYLANG['TXT_COULD_NOT_WRITE_TO_FILE']." (".$file.")";
            }
        } else {
            $this->strOkMessage .= "<br />".$_ARRAYLANG['TXT_SUCCESSFULLY_EXPORTED_TO_FILES'];
        }
    }
}
