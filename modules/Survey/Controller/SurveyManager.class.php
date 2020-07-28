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
 * SurveyManager
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @version        1.0.0
 * @package     cloudrexx
 * @subpackage  module_survey
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\Survey\Controller;

/**
 * SurveyManager
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @access        public
 * @version        1.0.0
 * @package     cloudrexx
 * @subpackage  module_survey
 */
class SurveyManager extends SurveyLibrary {
    /**
     * Template object
     *
     * @access private
     * @var object
     */
    var $_objTpl;

    /**
     * Page title
     *
     * @access private
     * @var string
     */
    var $_pageTitle;

    /**
     * Error status message
     *
     * @access private
     * @var string
     */
    var $_strErrMessage = '';

    /**
     * Ok status message
     *
     * @access private
     * @var string
     */
    var $_strOkMessage = '';

    /**
     * PHP5 constructor
     *
     * @global object $objTemplate
     * @global array $_ARRAYLANG
     */
    function __construct() {
        global $objTemplate, $_ARRAYLANG, $objDatabase;

        parent::__construct();

        $this->_objTpl = new \Cx\Core\Html\Sigma(ASCMS_MODULE_PATH.'/Survey/View/Template/Backend');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);

        $objTemplate->setVariable("CONTENT_NAVIGATION",
                "<a href='index.php?cmd=Survey' title='".$_ARRAYLANG['TXT_OVERVIEW']."'>".$_ARRAYLANG['TXT_OVERVIEW']."</a>
                 <a href='index.php?cmd=Survey&act=createOrCopy' title='".$_ARRAYLANG['TXT_CREATE_SURVEY']."'>".$_ARRAYLANG['TXT_CREATE_SURVEY']."</a>
                 <a href='index.php?cmd=Survey&act=settings' title='".$_ARRAYLANG['SETTINGS_TEXT']."'>".$_ARRAYLANG['SETTINGS_TEXT']."</a>");
    }

    /**
     * Set the backend page
     * @access public
     * @global object $objTemplate
     * @global array $_ARRAYLANG
     **/
    function getPage() {
        global $objTemplate, $_ARRAYLANG;

        if(!isset($_GET['act'])) {
            $_GET['act']='';
        }
        switch($_GET['act']) {
            case "listoverview":
                $this->listOverview();
                break;
            case "createOrCopy":
                $this->createOrCopy();
                break;
            case "copyEditSurvey":
                $this->copyEditSurvey();
                break;
            case "modify_survey":
            case "addSurvey":
            case "editSurvey":
                $this->_modifySurvey();
                break;
            case "settings":
                $this->Settings();
                break;
            case "csvSurvey":
                $this->csvSurvey();
                break;
            case "deletesurvey":
                $this->deleteSurvey();
                break;
            case "resetSurvey":
                $this->resetSurvey();
                break;
            case "deleteQuestions":
                $this->deleteQuestions();
                break;
            case "analyseSurvey":
                $this->AnalyseSurvey();
                break;
            case "questionAnalyseSurvey":
                $this->QuestionAnalyseSurvey();
                break;
            case "addQuestions":
                $this->addQuestions();
                break;
            case "editQuestionsOverview":
                $this->EditQuestionsOverview();
                break;
            case "editQuestions":
                $this->EditQuestions();
                break;
            case "SurveyChangeStatus":
                $this->SurveyChangeStatus();
                break;
            case "SurveyHomeChange":
                $this->SurveyHomeChange();
                break;
            case "csvAdditionalinfo":
                $this->csvAdditionalinfo();
                break;
            default:
                $this->surveyOverview();
                break;
        }

        $objTemplate->setVariable(array(
                'CONTENT_TITLE'        => $this->_pageTitle,
                'CONTENT_OK_MESSAGE'     => $this->_strOkMessage,
                'CONTENT_STATUS_MESSAGE' => $this->_strErrMessage,
                'ADMIN_CONTENT'        => $this->_objTpl->get()
        ));
    }


    /**
     * Returns the allowed maximum element per page. Can be used for paging.
     *
     * @global  array
     * @return  integer     allowed maximum of elements per page.
     */
    function getPagingLimit() {
        global $_CONFIG;

        return intval($_CONFIG['corePagingLimit']);
    }


    /**
     * Counts all existing entries in the database.
     *
     * @global  ADONewConnection
     * @return  integer     number of entries in the database
     */
    function countEntries($table, $where=null) {
        global $objDatabase;

        $objEntryResult = $objDatabase->Execute('SELECT  COUNT(*) AS numberOfEntries
                                                    FROM    '.DBPREFIX.'module_'.$table.$where);

        return intval($objEntryResult->fields['numberOfEntries']);
    }
    /**
     * Counts all existing entries in the database.
     *
     * @global  ADONewConnection
     * @return  integer     number of entries in the database
     */
    function countEntriesOfJoin($table) {
        global $objDatabase;

        $objEntryResult = $objDatabase->Execute('SELECT  COUNT(*) AS numberOfEntries
                                                    FROM    ('.$table.') AS num');
        return intval($objEntryResult->fields['numberOfEntries']);
    }

    function createOrCopy() {
        global $_ARRAYLANG, $objDatabase;
        $this->_pageTitle = $_ARRAYLANG['TXT_EDIT_SURVEY_TXT'];
        $this->_objTpl->loadTemplateFile('module_Create_copy.html');
        // Parsing javascript function to the place holder.
        $this->_objTpl->setVariable(array(
            'TXT_TITLE_ADD'        =>   $_ARRAYLANG['TXT_CREATE_SURVEY'],
            'SELECT_CREATE'        =>   "checked",
        ));
        $objData = $objDatabase->Execute('SELECT `id`, `title` FROM `'.DBPREFIX.'module_survey_surveygroup` ORDER BY id DESC');
        while(!$objData->EOF) {
            $this->_objTpl->setVariable(array(
                'TXT_SURVEY_TITLE' => contrexx_raw2xhtml($objData->fields['title']),
                'TXT_SURVEY_ID'    => contrexx_raw2xhtml($objData->fields['id'])
            ));
            $this->_objTpl->parse('ShowSurveys');
            $objData->MoveNext();
        }
        if(isset($_POST['create_submit'])) {
            if($_POST['createSurvey'] == "create") {
                \Cx\Core\Csrf\Controller\Csrf::header("Location: ".ASCMS_PATH_OFFSET.ASCMS_BACKEND_PATH."/index.php?cmd=Survey&act=addSurvey");
            } else {
                $selectedSurvey = (int) $_REQUEST['selectSurvey'];
                \Cx\Core\Csrf\Controller\Csrf::header("Location: ".ASCMS_PATH_OFFSET.ASCMS_BACKEND_PATH."/index.php?cmd=Survey&act=modify_survey&copy=1&id={$selectedSurvey}");
            }
        }
    }

    function copyEditSurvey() {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;
        $this->_pageTitle = $_ARRAYLANG['TXT_CREATE_SURVEY'];
        $this->_objTpl->loadTemplateFile('module_add_surveyone.html');

        $id = contrexx_input2raw($_REQUEST['id']);
        // Parsing javascript function to the place holder.
        $this->_objTpl->setVariable(array(
            'CREATE_SURVEY_JAVASCRIPT'      => $this->getCreateSurveyJavascript(),
            'TXT_BUTTON'                    => $_ARRAYLANG['TXT_SAVE_TXT'],
            'TXT_TITLE_ADD'                 => $_ARRAYLANG['TXT_EDIT_SURVEY_TXT'],
            'TXT_TITLE'                     => $_ARRAYLANG['TXT_TITLE'],
            'TXT_UNIQUE_USER_VERIFICATION'  => $_ARRAYLANG['TXT_UNIQUE_USER_VERIFICATION'],
            'TXT_IS_HOME_BOX'               => $_ARRAYLANG['TXT_IS_HOME_BOX'],
            'TXT_YES'                       => $_ARRAYLANG['TXT_YES'],
            'TXT_NO'                        => $_ARRAYLANG['TXT_NO'],
            'TXT_DESCRIPTION'               => $_ARRAYLANG['TXT_DESCRIPTION'],
            'TXT_BEGINNING_SURVEY'          => $_ARRAYLANG['TXT_BEGINNING_SURVEY'],
            'TXT_ADDITIONALINFO_SURVEY'     => $_ARRAYLANG['TXT_ADDITIONALINFO_SURVEY'],
            'TXT_BELOW_SUBMIT'              => $_ARRAYLANG['TXT_BELOW_SUBMIT'],
            'TXT_THANK_MSG'                 => $_ARRAYLANG['TXT_THANK_MSG'],
            'TXT_COOKIE_BASED'              => $_ARRAYLANG['TXT_COOKIE_BASED'],
            'TXT_EMAIL_BASED'               => $_ARRAYLANG['TXT_EMAIL_BASED'],
            'TXT_ADDITIONAL_FIELDS_LABEL'   => $_ARRAYLANG['TXT_ADDITIONAL_FIELDS_LABEL'],
            'TXT_SALUTATION'                => $_ARRAYLANG['TXT_SALUTATION'],
            'TXT_NICKNAME'                  => $_ARRAYLANG['TXT_NICKNAME'],
            'TXT_FIRSTNAME'                 => $_ARRAYLANG['TXT_FIRSTNAME'],
            'TXT_LASTNAME'                  => $_ARRAYLANG['TXT_LASTNAME'],
            'TXT_AGEGROUP'                  => $_ARRAYLANG['TXT_AGEGROUP'],
            'TXT_EMAIL_TEXT'                => $_ARRAYLANG['TXT_EMAIL_TEXT'],
            'TXT_TELEPHONE'                 => $_ARRAYLANG['TXT_TELEPHONE'],
            'TXT_STREET'                    => $_ARRAYLANG['TXT_STREET'],
            'TXT_ZIPCODE'                   => $_ARRAYLANG['TXT_ZIPCODE'],
            'TXT_PLACEOFREC'                => $_ARRAYLANG['TXT_PLACEOFREC'],
            'TXT_SHOW'                      => $_ARRAYLANG['TXT_SHOW'],
            'TXT_HIDE'                      => $_ARRAYLANG['TXT_HIDE']
        ));

        if(isset($_POST['survey_submit'])) {
            $this->_objTpl->setVariable(array(
                'TXT_NEXT' => '<a href="index.php?cmd=Survey&act=editQuestionsOverview&id='.$id.'&linkId='.$id.'" title="Done">
                               <input type="button" name="Next" value="'.$_ARRAYLANG['TXT_SURVEY_NEXT_TXT'].'">
                               </a>'
            ));
            $title         = contrexx_input2db($_POST['title']);
            $description   = contrexx_input2db($_POST['Description']);
            $method        = contrexx_input2db($_POST['votingRestrictionMethod']);
            $text1         = contrexx_input2db($_POST['text1']);
            $text2         = contrexx_input2db($_POST['text2']);
            $thanksMSG     = contrexx_input2db($_POST['thanksMSG']);
            $textAfterButton = contrexx_input2db($_POST['textAfterButton']);
            // Insert Query for Inserting the Fields Posted
            $insertSurvey = 'UPDATE `'.DBPREFIX.'module_survey_surveygroup` SET
                           `title` = "'.$title.'",
                           `UserRestriction` = "'.$method.'",
                       `text1` = "'.$text1.'",
                                           `text2` = "'.$text2.'",
                                           `thanksMSG` = "'.$thanksMSG.'",
                                           `description` = "'.$description.'",
                       `textAfterButton` = "'.$textAfterButton.'",
                                            `additional_salutation` = "'.((isset($_POST['additional_salutation'])&&($_POST['additional_salutation']=='on'))?1:0).'",
                                            `additional_nickname` = "'.((isset($_POST['additional_nickname'])&&($_POST['additional_nickname']=='on'))?1:0).'",
                                            `additional_forename` = "'.((isset($_POST['additional_forename'])&&($_POST['additional_forename']=='on'))?1:0).'",
                                            `additional_surname` = "'.((isset($_POST['additional_surname'])&&($_POST['additional_surname']=='on'))?1:0).'",
                                            `additional_agegroup` = "'.((isset($_POST['additional_agegroup'])&&($_POST['additional_agegroup']=='on'))?1:0).'",
                                            `additional_email` = "'.((isset($_POST['additional_email'])&&($_POST['additional_email']=='on'))?1:0).'",
                                            `additional_phone` = "'.((isset($_POST['additional_phone'])&&($_POST['additional_phone']=='on'))?1:0).'",
                                            `additional_street` = "'.((isset($_POST['additional_street'])&&($_POST['additional_street']=='on'))?1:0).'",
                                            `additional_zip` = "'.((isset($_POST['additional_zip'])&&($_POST['additional_zip']=='on'))?1:0).'",
                                            `additional_city` = "'.((isset($_POST['additional_city'])&&($_POST['additional_city']=='on'))?1:0).'"
                                            WHERE id = "'.$id.'"';
            $objDatabase->Execute($insertSurvey);

            $objResult = $objDatabase->Execute('SELECT 1 FROM '.DBPREFIX.'module_survey_surveyQuestions WHERE survey_id='.$id);
            if($objResult->EOF) {
                $cid = contrexx_input2raw($_REQUEST['copyId']);
                $objCopyResult = $objDatabase->Execute('SELECT isCommentable,
                                                                QuestionType,
                                                                Question,
                                                                pos,
                                                                column_choice
                                                         FROM `'.DBPREFIX.'module_survey_surveyQuestions` WHERE survey_id= "'.$cid.'" ORDER BY id');

                $objDatabase->Execute('INSERT INTO `'.DBPREFIX.'module_survey_surveyQuestions`
                                                    SET survey_id = 0,
                                                    isCommentable = "'.contrexx_raw2db($objCopyResult->fields['isCommentable']).'",
                                                    QuestionType = "'.contrexx_raw2db($objCopyResult->fields['QuestionType']).'",
                                                    Question = "'.contrexx_raw2db($objCopyResult->fields['Question']).'",
                                                    pos = "'.contrexx_raw2db($objCopyResult->fields['pos']).'",
                                                    column_choice = "'.contrexx_raw2db($objCopyResult->fields['column_choice']).'"');
                $insertSurvey = 'UPDATE `'.DBPREFIX.'module_survey_surveyQuestions`
                                     SET  `survey_id` = "'.contrexx_raw2db($id).'" WHERE survey_id = 0';
                $objDatabase->Execute($insertSurvey);

                // to get the current question id
                $objResult = $objDatabase->Execute('SELECT `id` FROM '.DBPREFIX.'module_survey_surveyQuestions
                                                    WHERE survey_id='.$id.' ORDER BY id');
                while(!$objResult->EOF) {
                    $currentId[] = $objResult->fields['id'];
                    $objResult->MoveNext();
                }
                // select query for updating the new answer in tables
                $objResult = $objDatabase->Execute('SELECT `id` FROM '.DBPREFIX.'module_survey_surveyQuestions
                                                    WHERE survey_id='.$cid.' ORDER BY id');
                while(!$objResult->EOF) {
                    $question_id[] = $objResult->fields['id'];
                    $objResult->MoveNext();
                }
                // loop populate the answers for the questions
                for($i=0;$i<count($currentId);$i++) {
                    $objAnsResult = $objDatabase->Execute('SELECT answer FROM `'.DBPREFIX.'module_survey_surveyAnswers` WHERE question_id= "'.$question_id[$i].'"');
                    $objDatabase->Execute('INSERT INTO `'.DBPREFIX.'module_survey_surveyAnswers`
                                            SET `question_id` = 0,
                                                `votes` = 0,
                                                 `answer` = "'.$objAnsResult->fields['answer'].'"');
                    $insertAnswers = 'UPDATE `'.DBPREFIX.'module_survey_surveyAnswers`
                           SET  `question_id` = "'.contrexx_raw2db($currentId[$i]).'" WHERE question_id =  0';
                    $objDatabase->Execute($insertAnswers);
                }
                // loop populate the Column Choice for the questions
                for($i=0;$i<count($currentId);$i++) {
                    $objAnsResult = $objDatabase->Execute('SELECT choice FROM `'.DBPREFIX.'module_survey_columnChoices` WHERE     question_id= "'.$question_id[$i].'"');
                    $objDatabase->Execute('INSERT INTO `'.DBPREFIX.'module_survey_columnChoices`
                                            SET `question_id` = 0,
                                                `choice` = "'.contrexx_raw2db($objAnsResult->fields['choice']).'"');
                    $insertAnswers = 'UPDATE `'.DBPREFIX.'module_survey_columnChoices`
                           SET  `question_id` = "'.contrexx_raw2db($currentId[$i]).'" WHERE question_id =  0';
                    $objDatabase->Execute($insertAnswers);
                }
            }
            $this->_strOkMessage = $_ARRAYLANG['TXT_SURVEY_UPDATE_SUC_TXT'];
        }

        $objResult = $objDatabase->Execute('SELECT * FROM '.DBPREFIX.'module_survey_surveygroup
               WHERE id='.$id.' ORDER BY id desc');
        if(!$objResult->EOF) {
            $additional_nickname = $objResult->fields['additional_nickname'];
            $additional_forename = $objResult->fields['additional_forename'];
            $additional_surname  = $objResult->fields['additional_surname'];
            $additional_phone    = $objResult->fields['additional_phone'];
            $additional_street   = $objResult->fields['additional_street'];
            $additional_zip      = $objResult->fields['additional_zip'];
            $additional_city     = $objResult->fields['additional_city'];
            $additional_email    = $objResult->fields['additional_email'];
            $additional_salutation= $objResult->fields['additional_salutation'];
            $additional_agegroup = $objResult->fields['additional_agegroup'];
            if($objResult->fields['isHomeBox'] == "1") {
                $this->_objTpl->setVariable(array(
                        'DB_YES_SURVEY_HOMEBOX'  =>   "checked"
                ));
            }else {
                $this->_objTpl->setVariable(array(
                        'DB_NO_SURVEY_HOMEBOX'  =>   "checked"
                ));
            }
            if($objResult->fields['UserRestriction'] == "email") {
                $this->_objTpl->setVariable(array(
                        'SELECT_RESTRICTION_EMAIL'  =>   "checked"
                ));
            }else {
                $this->_objTpl->setVariable(array(
                        'SELECT_RESTRICTION_COOKIE'  =>   "checked"
                ));
            }
            if(isset($_POST['survey_submit'])) {
                $copyOf = $objResult->fields['title'];
            }else {
                $copyOf = "Copy of ".$objResult->fields['title'];
            }

            $description = contrexx_remove_script_tags($objResult->fields['description']);
            $textAfterButton = contrexx_remove_script_tags($objResult->fields['textAfterButton']);
            $text1 = contrexx_remove_script_tags($objResult->fields['text1']);
            $text2 = contrexx_remove_script_tags($objResult->fields['text2']);
            $thanksMSG = contrexx_remove_script_tags($objResult->fields['thanksMSG']);

            $strMessageInputHTML = new \Cx\Core\Wysiwyg\Wysiwyg('Description', contrexx_raw2xhtml($description), 'full');
            $strMessageText1     = new \Cx\Core\Wysiwyg\Wysiwyg('text1', contrexx_raw2xhtml($text1), 'full');
            $strMessageText2     = new \Cx\Core\Wysiwyg\Wysiwyg('text2', contrexx_raw2xhtml($text2), 'full');
            $strMessageAftButton = new \Cx\Core\Wysiwyg\Wysiwyg('textAfterButton', contrexx_raw2xhtml($textAfterButton), 'full');
            $strMessageThanksMSG = new \Cx\Core\Wysiwyg\Wysiwyg('thanksMSG', contrexx_raw2xhtml($thanksMSG), 'full');

            $this->_objTpl->setVariable(array(
                'DB_SURVEY_DESC'                        => $strMessageInputHTML,
                'TEXT1'                                 => $strMessageText1,
                'TEXT2'                                 => $strMessageText2,
                'DB_TEXT_AFTER_BUTTON'                  => $strMessageAftButton,
                'THANKS_MSG'                            => $strMessageThanksMSG,
                'DB_SURVEY_TITLE'                       => contrexx_raw2xhtml($copyOf),
                'VOTING_FLAG_ADDITIONAL_NICKNAME'       => $additional_nickname ? 'checked="checked"' : '',
                'VOTING_FLAG_ADDITIONAL_FORENAME'       => $additional_forename ? 'checked="checked"' : '',
                'VOTING_FLAG_ADDITIONAL_SURNAME'        => $additional_surname  ? 'checked="checked"' : '',
                'VOTING_FLAG_ADDITIONAL_PHONE'          => $additional_phone    ? 'checked="checked"' : '',
                'VOTING_FLAG_ADDITIONAL_STREET'         => $additional_street   ? 'checked="checked"' : '',
                'VOTING_FLAG_ADDITIONAL_ZIP'            => $additional_zip      ? 'checked="checked"' : '',
                'VOTING_FLAG_ADDITIONAL_CITY'           => $additional_city     ? 'checked="checked"' : '',
                'VOTING_FLAG_ADDITIONAL_EMAIL'          => $additional_email    ? 'checked="checked"' : '',
                'VOTING_FLAG_ADDITIONAL_SALUTATION'     => $additional_salutation  ? 'checked="checked"' : '',
                'VOTING_FLAG_ADDITIONAL_AGEGROUP'       => $additional_agegroup    ? 'checked="checked"' : ''
            ));
        }
    }

    function EditSurvey() {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;

        $this->_pageTitle = $_ARRAYLANG['TXT_EDIT_SURVEY_TXT'];
        $this->_objTpl->loadTemplateFile('module_add_surveyone.html');
        $CSRF_PARAM = \Cx\Core\Csrf\Controller\Csrf::param();

        $id = isset($_REQUEST['id']) ? contrexx_input2raw($_REQUEST['id']) : 0;
        $homeBoxCheck = $objDatabase->Execute('SELECT * FROM `'.DBPREFIX.'module_survey_surveygroup` WHERE isHomeBox="1" AND id!="'.$id.'"');
        if(!$homeBoxCheck->EOF) {
            $hiddenField = "<input type='hidden' name='hidfield' id='hidfield' value='present'>";
        }else {
            $hiddenField = "<input type='hidden' name='hidfield' id='hidfield' value=''>";
        }

        // Parsing javascript function to the place holder.
        $this->_objTpl->setVariable(array(
            'HIDDEN_FIELD'              => $hiddenField,
            'CREATE_SURVEY_JAVASCRIPT'  => $this->getCreateSurveyJavascript(),
            'TXT_BUTTON'                => $_ARRAYLANG['TXT_SAVE_TXT'],
            'TXT_TITLE_ADD'             => $_ARRAYLANG['TXT_EDIT_SURVEY_TXT'],
            'TXT_TITLE'                 => $_ARRAYLANG['TXT_TITLE'],
            'TXT_UNIQUE_USER_VERIFICATION'=> $_ARRAYLANG['TXT_UNIQUE_USER_VERIFICATION'],
            'TXT_IS_HOME_BOX'           => $_ARRAYLANG['TXT_IS_HOME_BOX'],
            'TXT_YES'                   => $_ARRAYLANG['TXT_YES'],
            'TXT_NO'                    => $_ARRAYLANG['TXT_NO'],
            'TXT_DESCRIPTION'           => $_ARRAYLANG['TXT_DESCRIPTION'],
            'TXT_BEGINNING_SURVEY'      => $_ARRAYLANG['TXT_BEGINNING_SURVEY'],
            'TXT_ADDITIONALINFO_SURVEY' => $_ARRAYLANG['TXT_ADDITIONALINFO_SURVEY'],
            'TXT_BELOW_SUBMIT'          => $_ARRAYLANG['TXT_BELOW_SUBMIT'],
            'TXT_THANK_MSG'             => $_ARRAYLANG['TXT_THANK_MSG'],
            'TXT_COOKIE_BASED'          => $_ARRAYLANG['TXT_COOKIE_BASED'],
            'TXT_EMAIL_BASED'           => $_ARRAYLANG['TXT_EMAIL_BASED'],
            'TXT_ADDITIONAL_FIELDS_LABEL'=> $_ARRAYLANG['TXT_ADDITIONAL_FIELDS_LABEL'],
            'TXT_SALUTATION'            => $_ARRAYLANG['TXT_SALUTATION'],
            'TXT_NICKNAME'              => $_ARRAYLANG['TXT_NICKNAME'],
            'TXT_FIRSTNAME'             => $_ARRAYLANG['TXT_FIRSTNAME'],
            'TXT_LASTNAME'              => $_ARRAYLANG['TXT_LASTNAME'],
            'TXT_AGEGROUP'              => $_ARRAYLANG['TXT_AGEGROUP'],
            'TXT_EMAIL_TEXT'            => $_ARRAYLANG['TXT_EMAIL_TEXT'],
            'TXT_TELEPHONE'             => $_ARRAYLANG['TXT_TELEPHONE'],
            'TXT_STREET'                => $_ARRAYLANG['TXT_STREET'],
            'TXT_ZIPCODE'               => $_ARRAYLANG['TXT_ZIPCODE'],
            'TXT_SHOW'                  => $_ARRAYLANG['TXT_SHOW'],
            'TXT_HIDE'                  => $_ARRAYLANG['TXT_HIDE'],
            'TXT_PLACEOFREC'            => $_ARRAYLANG['TXT_PLACEOFREC']
        ));

        $link = "'index.php?".$CSRF_PARAM."&cmd=Survey&act=editQuestionsOverview&id=".$id."&linkId=".$id."'";
        $this->_objTpl->setVariable(array(
            'TXT_NEXT' => '<input type="button" name="Next" value="'.$_ARRAYLANG['TXT_SURVEY_NEXT_TXT'].'" onclick= "window.location='.$link.'" />',
        ));
        if(isset($_POST['survey_submit'])) {
            $title         = contrexx_input2db($_POST['title']);
            $description   = contrexx_input2db($_POST['Description']);
            $method        = contrexx_input2db($_POST['votingRestrictionMethod']);
            $text1         = contrexx_input2db($_POST['text1']);
            $text2         = contrexx_input2db($_POST['text2']);
            $thanksMSG     = contrexx_input2db($_POST['thanksMSG']);
            $textAfterButton = contrexx_input2db($_POST['textAfterButton']);
            // Insert Query for Inserting the Fields Posted

            $insertSurvey = 'UPDATE `'.DBPREFIX.'module_survey_surveygroup` SET
                                `title` = "'.$title.'",
                                `UserRestriction` = "'.$method.'",
                                `updated` = now(),
                                `description` = "'.$description.'",
                                `text1` = "'.$text1.'",
                                `text2` = "'.$text2.'",
                                `thanksMSG` = "'.$thanksMSG.'",
                                `additional_salutation` = "'.((isset($_POST['additional_salutation'])&&($_POST['additional_salutation']=='on'))?1:0).'",
                                `additional_nickname` = "'.((isset($_POST['additional_nickname'])&&($_POST['additional_nickname']=='on'))?1:0).'",
                                `additional_forename` = "'.((isset($_POST['additional_forename'])&&($_POST['additional_forename']=='on'))?1:0).'",
                                `additional_surname` = "'.((isset($_POST['additional_surname'])&&($_POST['additional_surname']=='on'))?1:0).'",
                                `additional_agegroup` = "'.((isset($_POST['additional_agegroup'])&&($_POST['additional_agegroup']=='on'))?1:0).'",
                                `additional_email` = "'.((isset($_POST['additional_email'])&&($_POST['additional_email']=='on'))?1:0).'",
                                `additional_phone` = "'.((isset($_POST['additional_phone'])&&($_POST['additional_phone']=='on'))?1:0).'",
                                `additional_street` = "'.((isset($_POST['additional_street'])&&($_POST['additional_street']=='on'))?1:0).'",
                                `additional_zip` = "'.((isset($_POST['additional_zip'])&&($_POST['additional_zip']=='on'))?1:0).'",
                                `textAfterButton` = "'.$textAfterButton.'",
                                `additional_city` = "'.((isset($_POST['additional_city'])&&($_POST['additional_city']=='on'))?1:0).'" WHERE id = "'.$id.'"';
            $objDatabase->Execute($insertSurvey);

            $this->_strOkMessage = $_ARRAYLANG['TXT_SURVEY_UPDATE_SUC_TXT'];
        }
        $objResult = $objDatabase->Execute('SELECT * FROM '.DBPREFIX.'module_survey_surveygroup
                                            WHERE id='.$id.' ORDER BY id desc');
        if(!$objResult->EOF) {
            $additional_salutation  = $objResult->fields['additional_salutation'];
            $additional_nickname    = $objResult->fields['additional_nickname'];
            $additional_forename    = $objResult->fields['additional_forename'];
            $additional_surname     = $objResult->fields['additional_surname'];
            $additional_agegroup    = $objResult->fields['additional_agegroup'];
            $additional_phone       = $objResult->fields['additional_phone'];
            $additional_street      = $objResult->fields['additional_street'];
            $additional_zip         = $objResult->fields['additional_zip'];
            $additional_city        = $objResult->fields['additional_city'];
            $additional_email       = $objResult->fields['additional_email'];
            $textAfterButton        = $objResult->fields['textAfterButton'];

            if($objResult->fields['isHomeBox'] == "1") {
                $this->_objTpl->setVariable(array(
                    'DB_YES_SURVEY_HOMEBOX' => "checked"
                ));
            }else {
                $this->_objTpl->setVariable(array(
                        'DB_NO_SURVEY_HOMEBOX'  =>   "checked"
                ));
            }
            if($objResult->fields['UserRestriction'] == "email") {
                $this->_objTpl->setVariable(array(
                        'SELECT_RESTRICTION_EMAIL'  =>   "checked"
                ));
            }else {
                $this->_objTpl->setVariable(array(
                        'SELECT_RESTRICTION_COOKIE'  =>   "checked"
                ));
            }

            $description        = contrexx_remove_script_tags($objResult->fields['description']);
            $textAfterButton    = contrexx_remove_script_tags($objResult->fields['textAfterButton']);
            $text1              = contrexx_remove_script_tags($objResult->fields['text1']);
            $text2              = contrexx_remove_script_tags($objResult->fields['text2']);
            $thanksMSG          = contrexx_remove_script_tags($objResult->fields['thanksMSG']);

            $strMessageInputHTML = new \Cx\Core\Wysiwyg\Wysiwyg('Description', contrexx_raw2xhtml($description), 'full');
            $strMessageText1     = new \Cx\Core\Wysiwyg\Wysiwyg('text1', contrexx_raw2xhtml($text1), 'full');
            $strMessageText2     = new \Cx\Core\Wysiwyg\Wysiwyg('text2', contrexx_raw2xhtml($text2), 'full');
            $strMessageAftButton = new \Cx\Core\Wysiwyg\Wysiwyg('textAfterButton', contrexx_raw2xhtml($textAfterButton), 'full');
            $strMessageThanksMSG = new \Cx\Core\Wysiwyg\Wysiwyg('thanksMSG', contrexx_raw2xhtml($thanksMSG), 'full');

            $this->_objTpl->setVariable(array(
                'DB_SURVEY_DESC'                    => $strMessageInputHTML,
                'TEXT1'                             => $strMessageText1,
                'TEXT2'                             => $strMessageText2,
                'DB_TEXT_AFTER_BUTTON'              => $strMessageAftButton,
                'THANKS_MSG'                        => $strMessageThanksMSG,
                'DB_SURVEY_TITLE'                   => contrexx_raw2xhtml($objResult->fields['title']),
                'VOTING_FLAG_ADDITIONAL_NICKNAME'   => $additional_nickname   ? 'checked="checked"' : '',
                'VOTING_FLAG_ADDITIONAL_FORENAME'   => $additional_forename   ? 'checked="checked"' : '',
                'VOTING_FLAG_ADDITIONAL_SURNAME'    => $additional_surname    ? 'checked="checked"' : '',
                'VOTING_FLAG_ADDITIONAL_PHONE'      => $additional_phone      ? 'checked="checked"' : '',
                'VOTING_FLAG_ADDITIONAL_STREET'     => $additional_street     ? 'checked="checked"' : '',
                'VOTING_FLAG_ADDITIONAL_ZIP'        => $additional_zip        ? 'checked="checked"' : '',
                'VOTING_FLAG_ADDITIONAL_CITY'       => $additional_city       ? 'checked="checked"' : '',
                'VOTING_FLAG_ADDITIONAL_EMAIL'      => $additional_email      ? 'checked="checked"' : '',
                'VOTING_FLAG_ADDITIONAL_SALUTATION' => $additional_salutation ? 'checked="checked"' : '',
                'VOTING_FLAG_ADDITIONAL_AGEGROUP'   => $additional_agegroup   ? 'checked="checked"' : ''
            ));
        }
    }

    function EditQuestionsOverview() {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;
        $id = isset($_REQUEST['id']) ? contrexx_input2raw($_REQUEST['id']) : 0;
        $linkId = isset($_REQUEST['linkId']) ? contrexx_input2raw($_REQUEST['id']) :0;
        \JS::activate('greybox');

        $objResult = $objDatabase->Execute('SELECT 1 FROM '.DBPREFIX.'module_survey_surveyQuestions WHERE survey_id='.$id);
        if ($objResult->RecordCount() == 0) {
            \Cx\Core\Csrf\Controller\Csrf::header("Location: ".ASCMS_PATH_OFFSET.ASCMS_BACKEND_PATH."/index.php?cmd=Survey&act=addQuestions&surveyId=".$id."&linkId=".$linkId);
        }

        $this->_pageTitle = $_ARRAYLANG['TXT_QUESTION_OVERVIEW'];
        $this->_objTpl->loadTemplateFile('module_edit_Question.html');
        $mes = isset($_REQUEST['mes']) ? contrexx_input2raw($_REQUEST['mes']) : '';

        if($mes == "updated") {
            $this->_strOkMessage = $_ARRAYLANG['TXT_QUESTION_UPDATE_SUC_TXT'];
        } elseif ($mes == "deleted") {
            $this->_strOkMessage = $_ARRAYLANG['TXT_QUESTION_DELETED_SUC_TXT'];
        }

        $this->_objTpl->setVariable(array(
            'ADD_QUESTION_HERE' => '<a href="index.php?cmd=Survey&act=addQuestions&surveyId='.$linkId.'&linkId='.$linkId.'">
                                    <input type="button" value="'.$_ARRAYLANG['TXT_QUESTION_ADD_TXT'].'"></a>'
        ));
        // Parsing javascript function to the place holder.
        $this->_objTpl->setVariable(array(
            'QUESTION_SURVEY_JAVASCRIPT'    => $this->getQuestionSurveyJavascript(),
            'TXT_QUESTION_OVERVIEW'         => $_ARRAYLANG['TXT_QUESTION_OVERVIEW'],
            'TXT_SORTING'                   => $_ARRAYLANG['TXT_SORTING'],
            'TXT_QUESTION'                  => $_ARRAYLANG['TXT_QUESTION'],
            'TXT_ANALYSE_QUESTION_PREVIEW'  => $_ARRAYLANG['TXT_ANALYSE_QUESTION_PREVIEW'],
            'TXT_SURVEY_EDIT_TXT'           => $_ARRAYLANG['TXT_SURVEY_EDIT_TXT'],
            'TXT_SURVEY_DELETE_TXT'         => $_ARRAYLANG['TXT_SURVEY_DELETE_TXT'],
            'TXT_CREATED_AT'                => $_ARRAYLANG['TXT_CREATED_AT'],
            'TXT_QUESTION_TYPE'             => $_ARRAYLANG['TXT_QUESTION_TYPE'],
            'TXT_IS_COMMENTABLE'            => $_ARRAYLANG['TXT_IS_COMMENTABLE'],
            'TXT_COUNTER'                   => $_ARRAYLANG['TXT_COUNTER'],
            'TXT_FUNCTIONS'                 => $_ARRAYLANG['TXT_FUNCTIONS'],
            'TXT_SELECT_ALL'                => $_ARRAYLANG['TXT_SELECT_ALL'],
            'TXT_DESELECT_ALL'              => $_ARRAYLANG['TXT_DESELECT_ALL'],
            'TXT_SELECT_ACTION'             => $_ARRAYLANG['TXT_SELECT_ACTION'],
            'TXT_SAVE_SORTING'              => $_ARRAYLANG['TXT_SAVE_SORTING'],
            'TXT_DELETE_SELECTED'           => $_ARRAYLANG['TXT_DELETE_SELECTED'],
            'TXT_TEXT_ROW'                  => $_ARRAYLANG['TXT_TEXT_ROW']
        ));
        //sort
        //  SET pos = '".intval($_POST['form_pos'][$x])."'
        if (isset($_GET['chg']) and $_GET['chg'] == 1 ) {
            for ($x = 0; $x < count($_POST['form_id']); $x++) {
                $query = "UPDATE ".DBPREFIX."module_survey_surveyQuestions
                            SET pos = '".intval(contrexx_input2db($_POST['form_pos'][$x]))."'
                            WHERE id = '".intval($_POST['form_id'][$x])."'";
                $objDatabase->Execute($query);
            }
            $this->_strOkMessage = $_ARRAYLANG['TXT_SURVEY_SORTING_SAVE_TXT'];
        }

        /* Start Paging ------------------------------------ */
        $queryPage = 'SELECT * FROM '.DBPREFIX.'module_survey_surveyQuestions
                        WHERE survey_id='.$id.' ORDER BY pos,id DESC';
        $intPos             = (isset($_GET['pos'])) ? intval(contrexx_input2raw($_GET['pos'])) : 0;
        // TODO: Never used
        $intPerPage         = $this->getPagingLimit();
        $noOfQuestions      = $this->countEntriesOfJoin($queryPage);
        $strPagingSource    = ($noOfQuestions) ? getPaging($noOfQuestions, $intPos, '&amp;cmd=Survey&amp;act=editQuestionsOverview&amp;id='.$linkId.'&amp;linkId='.$linkId, false, $intPerPage) : '';
        $this->_objTpl->setVariable('ENTRIES_PAGING', $strPagingSource);
        $limit = $this->getPagingLimit();                 //how many items to show per page

        $start = ($intPos) ? $intPos : 0;
        /* End Paging -------------------------------------- */
        $objResult = $objDatabase->Execute('SELECT * FROM '.DBPREFIX.'module_survey_surveyQuestions
               WHERE survey_id='.$id.' ORDER BY pos,id DESC LIMIT '.$start.','.$limit);
        $row = 'row1';
        while(!$objResult->EOF) {
            $InputType = $objResult->fields['QuestionType'];
            $IsCommentable = $objResult->fields['isCommentable'];
            if(!empty($InputType)) {
                switch($InputType) {
                    case "1":
                        $Radio = $_ARRAYLANG['TXT_MULTIPLE_CHOICE_ONE_ANSWER'];
                        break;
                    case "2":
                        $Radio = $_ARRAYLANG['TXT_MULTIPLE_CHOICE_MULTIPLE_ANSWER'];
                        break;
                    case "3":
                        $Radio = $_ARRAYLANG['TXT_MATRIX_CHOICE_ONE_ANSWER_PER_ROW'];
                        break;
                    case "4":
                        $Radio = $_ARRAYLANG['TXT_MATRIX_CHOICE_MULTIPLE_ANSWER_PER_ROW'];
                        break;
                    case "5":
                        $Radio = $_ARRAYLANG['TXT_SINGLE_TEXTBOX'];
                        break;
                    case "6":
                        $Radio = $_ARRAYLANG['TXT_MULTIPLE_TEXTBOX'];
                        break;
                    case "7":
                        $Radio = $_ARRAYLANG['TXT_TEXT_ROW'];
                        break;
                }
            }
            if($IsCommentable == "1") {
                $comment = "Yes";
            }else {
                $comment = "No";
            }
            // for question Title with tool tip
            $surveynameVar =  contrexx_raw2xhtml($objResult->fields['Question']);
            $surveyShot = substr($surveynameVar, 0, 20);
            if(strlen($surveynameVar) > 20) {
                if($surveynameVar != "") {
                    $surveyTemp = $surveyShot.'..<a href="#" title="'.$surveynameVar.'" class="tooltip"><img border="0" src="'.ASCMS_PATH_OFFSET.ASCMS_MODULE_FOLDER.'/Survey/View/Media/comment.gif"><a>';
                }
            }else {
                if($surveynameVar != "") {
                    $surveyTemp = $surveyShot;
                }
            }
            $this->_objTpl->setVariable(array(
                    'TXT_SURVEY_ID'              => contrexx_raw2xhtml($objResult->fields['id']),
                    'TXT_SURVEY_POS'                  => contrexx_raw2xhtml($objResult->fields['pos']),
                    'TXT_SURVEY_QUESTION'          => $surveyTemp,
                    'TXT_SURVEY_QUESTION_CREATED_AT'  => contrexx_raw2xhtml($objResult->fields['created']),
                    'TXT_SURVEY_QUESTION_TYPE'        => contrexx_raw2xhtml($Radio),
                    'TXT_SURVEY_QUESTION_COMMENTABLE' => contrexx_raw2xhtml($comment),
                    'TXT_ANALYSE_QUESTION_PREVIEW'    => $_ARRAYLANG['TXT_ANALYSE_QUESTION_PREVIEW'],
                    'TXT_SURVEY_EDIT_TXT'          => $_ARRAYLANG['TXT_SURVEY_EDIT_TXT'],
                    'TXT_SURVEY_DELETE_TXT'          => $_ARRAYLANG['TXT_SURVEY_DELETE_TXT'],
                    'TXT_SURVEY_COUNTER'              => contrexx_raw2xhtml($objResult->fields['votes'])." votes",
                    'TXT_LINKID'                      => contrexx_raw2xhtml($linkId),
                    'ENTRY_ROWCLASS'                  => $row = ($row == 'row1') ? 'row2' : 'row1',
            ));
            $this->_objTpl->parse('ShowQuestions');
            $objResult->MoveNext();
        }
    }

    function EditQuestions() {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;
        \JS::activate('greybox');

        $this->_pageTitle = $_ARRAYLANG['TXT_EDIT_QUESTION_TXT'];
        $this->_objTpl->loadTemplateFile('module_add_survey.html');
        $id = isset($_REQUEST['id']) ? contrexx_input2raw($_REQUEST['id']) : 0;
        $linkId = isset($_REQUEST['linkId']) ? contrexx_input2raw($_REQUEST['linkId']) : 0;
        $Answerhide="";

        // Parsing javascript function to the place holder.
        $this->_objTpl->setVariable(array(
                'CREATE_SURVEY_JAVASCRIPT'                  => $this->getEditQuestionJavascript(),
                'SURVEY_IMAGE_PATH'                         => ASCMS_PATH_OFFSET.ASCMS_MODULE_FOLDER.'/Survey/View/Media',
                'TXT_ADD_QUESTION'                          => $_ARRAYLANG['TXT_EDIT_QUESTION_TXT'],
                'TXT_SELECT_QUESTION'                       => $_ARRAYLANG['TXT_SELECT_QUESTION'],
                'TXT_QUESTION_TYPE'                         => $_ARRAYLANG['TXT_QUESTION_TYPE'],
                'TXT_MULTIPLE_CHOICE_ONE_ANSWER'            => $_ARRAYLANG['TXT_MULTIPLE_CHOICE_ONE_ANSWER'],
                'TXT_MULTIPLE_CHOICE_MULTIPLE_ANSWER'       => $_ARRAYLANG['TXT_MULTIPLE_CHOICE_MULTIPLE_ANSWER'],
                'TXT_MATRIX_CHOICE_ONE_ANSWER_PER_ROW'      => $_ARRAYLANG['TXT_MATRIX_CHOICE_ONE_ANSWER_PER_ROW'],
                'TXT_MATRIX_CHOICE_MULTIPLE_ANSWER_PER_ROW' => $_ARRAYLANG['TXT_MATRIX_CHOICE_MULTIPLE_ANSWER_PER_ROW'],
                'TXT_QUESTION_TEXT'                         => $_ARRAYLANG['TXT_QUESTION_TEXT'],
                'TXT_ANSWER_CHOICE'                         => $_ARRAYLANG['TXT_ANSWER_CHOICE'],
                'TXT_ADD_COMMENT'                           => $_ARRAYLANG['TXT_ADD_COMMENT'],
                'TXT_YES'                                   => $_ARRAYLANG['TXT_YES'],
                'TXT_NO'                                    => $_ARRAYLANG['TXT_NO'],
                'TXT_HELP_TXT'                              => $_ARRAYLANG['TXT_HELP_TXT'],
                'TXT_SAVE_TXT'                              => $_ARRAYLANG['TXT_SAVE_TXT'],
                'TXT_HELP_IMAGE_TXT'                        => $_ARRAYLANG['TXT_HELP_IMAGE_TXT'],
                'TXT_COLUMN_CHOICE'                         => $_ARRAYLANG['TXT_COLUMN_CHOICE'],
                'TXT_SINGLE_TEXTBOX'                        => $_ARRAYLANG['TXT_SINGLE_TEXTBOX'],
                'TXT_MULTIPLE_TEXTBOX'                      => $_ARRAYLANG['TXT_MULTIPLE_TEXTBOX'],
                'TXT_TEXT_ROW'                              => $_ARRAYLANG['TXT_TEXT_ROW']
        ));

        $link = 'index.php?cmd=Survey&act=editQuestionsOverview&id='.$linkId.'&linkId='.$linkId.'&'.\Cx\Core\Csrf\Controller\Csrf::param();
        // Parsing back button to place holder.
        $this->_objTpl->setVariable(array(
            'TXT_BACK'          => '<input type="button" name="Back" value="'.$_ARRAYLANG['TXT_BACK_SURVEY_TXT'].'" onclick= "window.location=\''.$link.'\'" />',
            'ADD_QUESTION_HERE' => '<a href="index.php?cmd=Survey&act=addQuestions&surveyId='.$linkId.'&linkId='.$linkId.'" title="'.$_ARRAYLANG['TXT_QUESTION_ADD_TXT'].'">
                                   '.$_ARRAYLANG['TXT_QUESTION_ADD_TXT'].'</a>'
        ));

        $objResult = $objDatabase->Execute('SELECT * FROM '.DBPREFIX.'module_survey_surveyQuestions WHERE `id`='.$id.' ORDER BY `id` DESC');
        if(!$objResult->EOF) {
            $InputType     = $objResult->fields['QuestionType'];
            $IsCommentable = $objResult->fields['isCommentable'];
            $survey_id     = $objResult->fields['survey_id'];
            $RadioSel      = '';
            $CheckSel      = '';
            $MatrixSel     = '';
            $MatrixMulSel  = '';
            $Singletextbox = '';
            $Multitextbox  = '';
            $addcomments   = '';
            $questHide     = '';
            $TextRow       = '';
            if(!empty($InputType)) {
                switch($InputType) {
                    case "1":
                        $RadioSel = "selected";
                        $help1 = "";
                        $help2 = "none";
                        $help3 = "none";
                        $help4 = "none";
                        $help5 = "none";
                        $help6 = "none";
                        $help7 = "none";
                        $colhide = "display:none";
                        $RowHide = "none";
                        break;
                    case "2":
                        $CheckSel = "selected";
                        $help1 = "none";
                        $help2 = "";
                        $help3 = "none";
                        $help4 = "none";
                        $help5 = "none";
                        $help6 = "none";
                        $help7 = "none";
                        $colhide = "display:none";
                        $RowHide = "none";
                        break;
                    case "3":
                        $MatrixSel = "selected";
                        $help1 = "none";
                        $help2 = "none";
                        $help3 = "";
                        $help4 = "none";
                        $help5 = "none";
                        $help6 = "none";
                        $help7 = "none";
                        $colhide = "";
                        $RowHide = "none";
                        break;
                    case "4":
                        $MatrixMulSel = "selected";
                        $help1 = "none";
                        $help2 = "none";
                        $help3 = "none";
                        $help4 = "";
                        $help5 = "none";
                        $help6 = "none";
                        $help7 = "none";
                        $colhide = " ";
                        $RowHide = "none";
                        break;
                    case "5":
                        $Singletextbox = "selected";
                        $help1 = "none";
                        $help2 = "none";
                        $help3 = "none";
                        $help4 = "none";
                        $help5 = "";
                        $help6 = "none";
                        $help7 = "none";
                        $Answerhide = "none";
                        $colhide = "display:none";
                        $RowHide = "none";
                        break;
                    case "6":
                        $Multitextbox = "selected";
                        $help1 = "none";
                        $help2 = "none";
                        $help3 = "none";
                        $help4 = "none";
                        $help5 = "none";
                        $help6 = "";
                        $help7 = "none";
                        $colhide = "display:none";
                        $RowHide = "none";
                        break;
                    case "7":
                        $TextRow="selected";
                        $help1 = "none";
                        $help2 = "none";
                        $help3 = "none";
                        $help4 = "none";
                        $help5 = "none";
                        $help6 = "none";
                        $help7 = "";
                        $Answerhide = "none";
                        $addcomments = "display:none";
                        $colhide = "display:none";
                        $questHide = "none";
                        $RowHide = "";
                        break;
                }
            }
            if($IsCommentable == "1") {
                $this->_objTpl->setVariable(array(
                        'SELECT_COMMENTABLE_YES'  =>   "checked"
                ));
            }else {
                $this->_objTpl->setVariable(array(
                        'SELECT_COMMENTABLE_NO'  =>   "checked"
                ));
            }

            $query="SELECT answer,id FROM ".DBPREFIX."module_survey_surveyAnswers WHERE question_id='$id' ORDER BY id";
            $objResults = $objDatabase->Execute($query);
            $i=0;
            $votingoptions = '';
            $voltingresults = array();
            while (!$objResults->EOF) {
                $votingoptions .= contrexx_remove_script_tags($objResults->fields['answer'])."\n";
                $voltingresults[$i]=$objResults->fields['id'];
                $i++;
                $objResults->MoveNext();
            }

            // to get the id of column choices
            $query="SELECT choice,id FROM ".DBPREFIX."module_survey_columnChoices WHERE question_id='$id' ORDER BY id";
            $objResults = $objDatabase->Execute($query);
            $j=0;
            $coloptions = '';
            $Colvoltingresults = '';
            while (!$objResults->EOF) {
                $coloptions .= contrexx_remove_script_tags($objResults->fields['choice'])."\n";
                $Colvoltingresults[$j] = contrexx_remove_script_tags($objResults->fields['id']);
                $j++;
                $objResults->MoveNext();
            }

            /* swap question and answer for matrix type question */
            if ($InputType == 3 || $InputType == 4) {
                $temp = $coloptions;
                $coloptions = $votingoptions;
                $votingoptions = $temp;
            }

            $this->_objTpl->setVariable(array(
                'DB_QUESTION_TITLE'   => contrexx_raw2xhtml($objResult->fields['Question']),
                'DB_QUESTION_OPTIONS' => $votingoptions,
                'DB_COLUMN_OPTIONS'   => $coloptions,
                'TXT_RADIO_SEL'       => $RadioSel,
                'TXT_CHECK_SEL'       => $CheckSel,
                'TXT_MATRIX_SEL'      => $MatrixSel,
                'TXT_MATRIXMUL_SEL'   => $MatrixMulSel,
                'TXT_SINGLEBOX_SEL'   => $Singletextbox,
                'TXT_MULTIPLEBOX_SEL' => $Multitextbox,
                'TXT_HELPONE_SEL'     => $help1,
                'TXT_HELPTWO_SEL'     => $help2,
                'TXT_HELPTHREE_SEL'   => $help3,
                'TXT_HELPFOUR_SEL'    => $help4,
                'TXT_HELPFIVE_SEL'    => $help5,
                'TXT_HELPSIX_SEL'     => $help6,
                'TXT_ADDCOMMENTHIDE'  => $addcomments,
                'TXT_ANSWERHIDE'      => $Answerhide,
                'TXT_COLHIDE'         => $colhide,
                'TXT_QUESTHIDE'       => $questHide,
                'TXT_RTEXTHIDE'       => $RowHide,
                'IDS_ANSWERS'         => implode($voltingresults,";"),
                'COL_IDS_ANSWERS'     => (is_array($Colvoltingresults)?implode($Colvoltingresults,";"):$Colvoltingresults),
                'TXT_TEXTROW_SEL'     => $TextRow,
                'TXT_HELPSEVEN_SEL'   => $help7
            ));
        }
        if(isset($_POST['surveyQuestions_submit'])) {
            $questionType  = contrexx_input2raw($_POST['questionType']);
            if($questionType != 7) {
                $Question      = contrexx_input2db($_POST['Question']);
            } else {
                $Question      = contrexx_input2db($_POST['QuestionRow']);
            }
            if ($questionType != 3 && $questionType != 4) {
                $options       = explode ("\n", contrexx_input2raw($_POST['QuestionAnswers']));
                $ColChoices    = explode ("\n", contrexx_input2raw($_POST['ColumnChoices']));
            } else {
                $options       = explode ("\n", contrexx_input2raw($_POST['ColumnChoices']));
                $ColChoices    = explode ("\n", contrexx_input2raw($_POST['QuestionAnswers']));
            }
            if(($questionType == 3) || ($questionType == 4)) {
                $colChoic      = implode($ColChoices,";");
            }else {
                $colChoic      = "";
            }
            if($questionType == 5) {
                $query="DELETE FROM ".DBPREFIX."module_survey_surveyAnswers WHERE question_id='".intval($id)."'";
                $objDatabase->Execute($query);
            }else {
                $optionsid     = explode (";", contrexx_input2raw($_POST['votingresults']));
                $looptimes     = max(count($options),count($optionsid));
            }
            // loops count for column choices
            $Coloptionsid     = explode (";", contrexx_input2raw($_POST['Colvoltingresults']));
            $collooptimes     = max(count($ColChoices),count($Coloptionsid));

            $commentable   = contrexx_input2raw($_POST['Iscomment']);

            // Insert Query for Inserting the Fields Posted
            $insertSurvey = 'UPDATE `'.DBPREFIX.'module_survey_surveyQuestions`
                              SET  `survey_id` = "'.contrexx_raw2db($survey_id).'",
                                           `isCommentable` = "'.contrexx_raw2db($commentable).'",
                                           `QuestionType` = "'.contrexx_raw2db($questionType).'",
                                           `column_choice` = "'.contrexx_raw2db($colChoic).'",
                                           `Question` = "'.$Question.'" WHERE id='.$id;
            $objDatabase->Execute($insertSurvey);

            for ($i=0;$i<$looptimes;$i++) {
                if (trim($options[$i])!="") {
                    if ($optionsid[$i]!="") {
                        $query="UPDATE ".DBPREFIX."module_survey_surveyAnswers set answer='".contrexx_raw2db(trim($options[$i]))."' WHERE id='".intval($optionsid[$i])."'";
                        $objDatabase->Execute($query);
                    } else {
                        $query="INSERT INTO ".DBPREFIX."module_survey_surveyAnswers (question_id,answer) values ('".intval(contrexx_input2db($_REQUEST['id']))."','".contrexx_raw2db(trim($options[$i]))."')";
                        $objDatabase->Execute($query);
                    }
                }elseif ($optionsid[$i]!="") {
                    $query="DELETE FROM ".DBPREFIX."module_survey_surveyAnswers WHERE id='".intval($optionsid[$i])."'";
                    $objDatabase->Execute($query);
                }
            }
            // updating query loop for column choices
            for ($i=0;$i<$collooptimes;$i++) {
                if (trim($ColChoices[$i])!="") {
                    if ($Coloptionsid[$i]!="") {
                        $query="UPDATE ".DBPREFIX."module_survey_columnChoices set choice='".contrexx_raw2db(trim($ColChoices[$i]))."' WHERE id='".intval($Coloptionsid[$i])."'";
                        $objDatabase->Execute($query);
                    } else {
                        $query="INSERT INTO ".DBPREFIX."module_survey_columnChoices (question_id,choice) values ('".intval(contrexx_raw2db($_REQUEST['id']))."','".contrexx_raw2db(trim($ColChoices[$i]))."')";
                        $objDatabase->Execute($query);
                    }
                }elseif ($Coloptionsid[$i]!="") {
                    $query="DELETE FROM ".DBPREFIX."module_survey_columnChoices WHERE id='".intval($Coloptionsid[$i])."'";
                    $objDatabase->Execute($query);
                }
            }
            \Cx\Core\Csrf\Controller\Csrf::header("Location: ".ASCMS_PATH_OFFSET.ASCMS_BACKEND_PATH."/index.php?cmd=Survey&act=editQuestionsOverview&id=$survey_id&linkId=$linkId&mes=updated");
        }
    }

    //Analysing the question in the survey
    function AnalyseSurvey() {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;
        $this->_pageTitle = $_ARRAYLANG['TXT_ANALYSE_SURVEY_OVERVIEW'];
        $this->_objTpl->loadTemplateFile('module_analys_overview.html');
        $id = contrexx_input2raw($_REQUEST['id']);
        $objResult = $objDatabase->Execute('SELECT Question,votes,skipped,id FROM '.DBPREFIX.'module_survey_surveyQuestions
                                            WHERE survey_id='.$id.' ORDER BY pos,id DESC');
        $coun = 1;
        $row = 'row1';

        $this->_objTpl->setVariable(array(
            'TXT_ANALYSE_SURVEY_OVERVIEW'   => $_ARRAYLANG['TXT_ANALYSE_SURVEY_OVERVIEW'],
            'TXT_SNO'                       => $_ARRAYLANG['TXT_SNO'],
            'TXT_QUESTION'                  => $_ARRAYLANG['TXT_QUESTION'],
            'TXT_ANSWERED_QUESTION'         => $_ARRAYLANG['TXT_ANSWERED_QUESTION'],
            'TXT_SKIPPED_QUESTION'          => $_ARRAYLANG['TXT_SKIPPED_QUESTION'],
            'TXT_TOTAL'                     => $_ARRAYLANG['TXT_TOTAL'],
            'TXT_BACK'                      => $_ARRAYLANG['TXT_BACK_SURVEY_TXT'],
            'TXT_SAVE_TXT'                  => $_ARRAYLANG['TXT_SAVE_TXT'],
            'TXT_FUNCTIONS'                 => $_ARRAYLANG['TXT_FUNCTIONS'],
        ));

        while(!$objResult->EOF) {
            $question = $objResult->fields['Question'];
            $total    = $objResult->fields['votes'];
            $skipped  = $objResult->fields['skipped'];
            $ids  = $objResult->fields['id'];
            $this->_objTpl->setVariable(array(
                    'TXT_SURVEY_COUNT'   => $coun,
                    'TXT_SURVEY_ID'      => $ids,
                    'TXT_SURVEY_QUES'    => contrexx_raw2xhtml($question),
                    'TXT_SURVEY_VOTES'   => $total,
                    'TXT_SURVEY_SKIPPED' => $skipped,                                                    'TXT_SUR_ID'      =>    $id,
                    'ENTRY_ROWCLASS'     => $row = ($row == 'row1') ? 'row2' : 'row1'
            ));
            $this->_objTpl->parse('Show_Analyse');
            $coun++;
            $objResult->MoveNext();
        }

        $query =   $objDatabase->Execute('SELECT sum(votes) AS t,sum(skipped) AS s FROM '.DBPREFIX.'module_survey_surveyQuestions WHERE survey_id='.$id);
        while(!$query->EOF) {
            $votes = $query->fields['t'];
            $skipd = $query->fields['s'];
            $query->MoveNext();
        }
        $this->_objTpl->setVariable(array(
            'TXT_ANSWER_TOTAL'  => contrexx_raw2xhtml($votes),
            'TXT_SKIPPED_TOTAL' => contrexx_raw2xhtml($skipd)
        ));
    }

    //Analysing the Question
    function QuestionAnalyseSurvey() {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;
        $this->_pageTitle = $_ARRAYLANG['TXT_ANALYSE_SURVEY_QUESTION_TXT'];
        $this->_objTpl->loadTemplateFile('module_analys_question.html');

        // For question preview code -- Start //

        $id=contrexx_input2raw($_REQUEST['id']);
        //Query to get the Question and answer details.
        $QuestionDatas = $objDatabase->Execute('SELECT groups.title As title,
                                                        groups.description,
                                                        groups.isActive,
                                                        groups.id,
                                                        groups.created,
                                                        Questions.id AS questionId,
                                                        Questions.isCommentable,
                                                        groups.isHomeBox,
                                                        Questions.QuestionType,
                                                        Questions.Question,
                                                        Questions.column_choice
                                                FROM '.DBPREFIX.'module_survey_surveygroup AS groups
                                                LEFT JOIN '.DBPREFIX.'module_survey_surveyQuestions AS Questions
                                                ON groups.id=Questions.survey_id
                                                WHERE groups.isActive != "0"
                                                AND Questions.id="'.$id.'"');
        $cou = 1;

        while(!$QuestionDatas->EOF) {
            $answerId      = $QuestionDatas->fields['questionId'];
            $InputType     = $QuestionDatas->fields['QuestionType'];
            $IsCommentable = $QuestionDatas->fields['isCommentable'];
            $Column_choice = $QuestionDatas->fields['column_choice'];
            $commentBox    = '';

            if($IsCommentable == "1") {
                $commentBox = "<div style='clear:both;'><label style='float:left;padding:8px;10px;8px;0px;'>$_ARRAYLANG[TXT_SURVEY_COMMENT]</label> <textarea name='comment' rows='6' style='width:150px;'></textarea></div>";
            }

            $query = "SELECT id, answer FROM ".DBPREFIX."module_survey_surveyAnswers WHERE
                   question_id='$answerId' ORDER BY id";
            $objResult = $objDatabase->Execute($query);

            $SurveyOptionText = "";
            if(!empty($Column_choice)) {
                $countss = $objResult->RecordCount();
                $col = explode (";", $Column_choice);
                $SurveyOptionText .= "<table><tr><td>&nbsp;</td>";
                for($i=0;$i<count($col); $i++) {
                    if(trim($col[$i]) != "")
                        $SurveyOptionText .="<td><span style='padding:3px 20px 2px 0px;'>".contrexx_remove_script_tags($col[$i])."</span></td>";
                }
                $SurveyOptionText .="</tr>";
            }
            if($InputType == 6) {
                $SurveyOptionText .= "<table>";
            }
            $j = 1;
            while (!$objResult->EOF) {
                if(trim($objResult->fields['answer']) != "") {
                    if(!empty($InputType)) {
                        switch($InputType) {
                            case "1":
                                $SurveyOptionText .="<input style='float:left;' type='radio' name='votingoption_$cou' value='".$objResult->fields['id']."' /> <span style='float:left;padding:3px 20px 2px 0px;'>".contrexx_remove_script_tags($objResult->fields['answer'])."</span>";
                                break;
                            case "2":
                                $SurveyOptionText .="<input style='float:left;' type='checkbox' name='votingoption_".$cou."[]' value='".$objResult->fields['id']."' /> <span style='float:left;padding:3px 20px 2px 0px;'>".contrexx_remove_script_tags($objResult->fields['answer'])."</span>";
                                break;
                            case "3":
                                $SurveyOptionText .="<tr><td><span style='padding:3px 20px 2px 0px;'>".contrexx_remove_script_tags($objResult->fields['answer'])."</span></td>";
                                for($i=0;$i<count($col); $i++) {
                                    if(trim($col[$i]) != "")
                                        $SurveyOptionText .="<td><input style='float:left;' type='radio' name='votingoption_".$cou."_".$j."' value='".$objResult->fields['id']."' /></td>";
                                }
                                $SurveyOptionText .="</tr>";
                                $j=$j+1;
                                break;
                            case "4":
                                $SurveyOptionText .="<tr><td><span style='padding:3px 20px 2px 0px;'>".contrexx_remove_script_tags($objResult->fields['answer'])."</span></td>";
                                for($i=0;$i<count($col); $i++) {
                                    if(trim($col[$i]) != "")
                                        $SurveyOptionText .="<td><input style='float:left;' type='checkbox' name='votingoption_".$cou."_".$j."[]' value='".$objResult->fields['id']."' /></td>";
                                }
                                $SurveyOptionText .="</tr>";
                                $j=$j+1;
                                break;

                            case "5":
                                $SurveyOptionText .="<span style='float:left;padding:3px 20px 2px 0px;'> Answer :</span><input style='float:left;width:250px;' type='text' name='votingoption_$cou' value='' /> ";
                                break;
                            case "6":
                                $SurveyOptionText .="<tr>
             <td style='width:5%'><span  style='float:left;padding:3px 10px 2px 0px;'>".contrexx_remove_script_tags($objResult->fields['answer'])."</span></td>
             <td><input style='float:left;width:250px;' type='text' name='votingoption_$cou' value='' />
             </td></tr>";
                                break;
                        }
                    }
                }
                $objResult->MoveNext();
            }
            if(!empty($Column_choice)) {
                $SurveyOptionText .= "</table>";
            }
            if($InputType == 6) {
                $SurveyOptionText .= "</table>";
            }
            $this->_objTpl->setVariable(array(
                'GRAND_TITLE'            => contrexx_raw2xhtml($QuestionDatas->fields['title']),
                'SURVEY_TITLE'          => contrexx_raw2xhtml($QuestionDatas->fields['Question']),
                'SURVEY_OPTIONS_TEXT'   => $SurveyOptionText,
                'SURVEY_COMMENT_BOX'    => $commentBox
            ));
            $this->_objTpl->parse('Total_survey_preview');
            $cou++;
            $QuestionDatas->MoveNext();
        }
        // For question preview code -- end //
        $sid = contrexx_input2raw($_REQUEST['sid']);
        $objResult = $objDatabase->Execute('SELECT q.id as question_id, q.Question, q.votes AS svotes, q.skipped AS skip,
                                                   q.QuestionType, q.column_choice, answers.*
                                            FROM '.DBPREFIX.'module_survey_surveyAnswers AS answers
                                            LEFT JOIN '.DBPREFIX.'module_survey_surveyQuestions AS q
                                            ON q.id=answers.question_id WHERE question_id='.$id.' ORDER BY answers.id');
        $coun = 1;
        $this->_objTpl->setVariable(array(
            'TXT_ANALYSE_QUESTION_PREVIEW'  => $_ARRAYLANG['TXT_ANALYSE_QUESTION_PREVIEW'],
            'TXT_SNO'                       => $_ARRAYLANG['TXT_SNO'],
            'TXT_QUESTION'                  => $_ARRAYLANG['TXT_QUESTION'],
            'TXT_ANSWERED_QUESTION'         => $_ARRAYLANG['TXT_ANSWERED_QUESTION'],
            'TXT_SKIPPED_QUESTION'          => $_ARRAYLANG['TXT_SKIPPED_QUESTION'],
            'TXT_TOTAL'                     => $_ARRAYLANG['TXT_TOTAL'],
            'TXT_BACK'                      => $_ARRAYLANG['TXT_BACK_SURVEY_TXT'],
            'TXT_SURVID'                    => contrexx_raw2xhtml($sid)
        ));

        $images           = 1;
        $votingResultText = '';
        $answered         = '';
        $skippedvotes     = '';
        $Question         = '';

        /*
         * Matrix Questions follows diiferent design for result display
        */
        if ($objResult->fields['QuestionType'] == 3 || $objResult->fields['QuestionType'] == 4) {
            $choices = explode(';', $objResult->fields['column_choice']);
            $votingResultText .= "<table width='100%'><tr>";
            $votingResultText .= "<td width='25%'>&nbsp;</td>";
            foreach ($choices as $choice) {
                if($choice != '') {
                    $votingResultText .= "<td>".contrexx_remove_script_tags($choice)."</td>";
                }
            }
            $votingResultText .= "</tr>";

            /*
             * Fetch choice votes of corresponding question
            */
            $choiceVotes = array();
            $query = 'SELECT `votes` FROM '.DBPREFIX.'module_survey_surveyAnswers WHERE `question_id` = '.$objResult->fields['question_id'].' ORDER BY id';
            $objVote = $objDatabase->Execute($query);
            while (!$objVote->EOF) {
                $choiceVotes[] = json_decode($objVote->fields['votes']);
                $objVote->MoveNext();
            }
        }

        while(!$objResult->EOF) {
            $Question = $objResult->fields['Question'];
            $Qid = $objResult->fields['question_id'];
            $QType = $objResult->fields['QuestionType'];
            $votes=intval($objResult->fields['votes']);
            $answered=intval($objResult->fields['svotes']);
            $skippedvotes=intval($objResult->fields['skip']);
            if($QType == 5) {
                $votingVotes=intval($objResult->fields['svotes'])+intval($objResult->fields['skip']);
            }else {
                $votingVotes=intval($objResult->fields['svotes']);
            }
            $votingVotes = ($votingVotes) ? $votingVotes : 1;

            $percentage = 0;
            $imagewidth = 1; //Mozilla Bug if image width=0
            if ($QType == 3 || $QType == 4) {
                $votingResultText .= "<tr><td>".contrexx_remove_script_tags($objResult->fields['answer'])."</td>";
                foreach ($choiceVotes[$coun-1] as $vote) {
                    $percent = round(($vote / $votingVotes)*100, 2);
                    $votingResultText .= "<td>$percent%($vote / $votingVotes)</td>";
                }
                $votingResultText .= "</tr>";
            } elseif($QType == 5) {
                $query = "SELECT answers FROM ".DBPREFIX."module_survey_poll_result WHERE
                           question_id='$answerId' ORDER BY id";
                $objAnswers = $objDatabase->Execute($query);
                $rowCounter  = 0;
                while(!$objAnswers->EOF) {
                    if($objAnswers->fields['answers'] != "") {
                        $answers = $objAnswers->fields['answers'];
                        $rowCounter += 1;
                        if ($rowCounter%2 == 0) {
                            $rowClass = "row2";
                        } else {
                            $rowClass = "row1";
                        }
                        $votingResultText .= '<tr class="$rowClass"> <td>'.$answers.'</td></tr>';
                    }
                    $objAnswers->MoveNext();
                }

            } elseif($QType == 6) {
                $query = "SELECT answers FROM ".DBPREFIX."module_survey_poll_result WHERE
                           question_id='$answerId' ORDER BY id";
                $objAnswers = $objDatabase->Execute($query);
                $rowCounter  = 1;
                while(!$objAnswers->EOF) {
                    if($objAnswers->fields['answers'] != "") {
                        $answers = $objAnswers->fields['answers'];
                        $ansArr  = json_decode($answers);
                        $answer  = implode($ansArr,",");
                        $dumVar  = str_replace(","," ",$answer);
                        if (trim($dumVar) != '') {
                            $rowCounter += 1;
                            if ($rowCounter%2 == 0) {
                                $rowClass = "row2";
                            } else {
                                $rowClass = "row1";
                            }
                            $votingResultText .= "<tr height='20px' class='$rowClass'> <td>$answer</td></tr>";
                        }
                    }
                    $objAnswers->MoveNext();
                }
                break;
            } elseif($votes>0) {
                $percentage = (round(($votes/$votingVotes)*10000))/100;
                $imagewidth = round($percentage,0);

                $votingResultText .= contrexx_remove_script_tags($objResult->fields['answer'])."<br />\n";
                $votingResultText .= "<img src='../core/Core/View/Media/icons/$images.gif' width='$imagewidth%' height=\"10\" alt=\"$votes ".$_ARRAYLANG['TXT_VOTES']." / $percentage %\" />";
                $votingResultText .= "&nbsp;<font size='1'>$votes ".$_ARRAYLANG['TXT_VOTES']." / $percentage %</font><br />\n";
            }
            $coun++;
            $objResult->MoveNext();
        }
        if ($objResult->fields['QuestionType'] == 3) {
            $votingResultText .= "</table>";
        }


        $this->_objTpl->setVariable(array(
                'TXT_SURVEY_COMMENT'    => $_ARRAYLANG['TXT_SURVEY_COMMENT'],
                'TXT_SURVEY_ANSWER'     => $_ARRAYLANG['TXT_SURVEY_ANSWER'],
                'TXT_SURVEY_VOTES'      => $votingResultText,
                'TXT_ANSWERED_COUNT'    => contrexx_raw2xhtml($answered),
                'TXT_SKIPPED_COUNT'     => contrexx_raw2xhtml($skippedvotes),
                'TXT_SURVEY_QUESTION'   => contrexx_raw2xhtml($Question)
        ));

        if($IsCommentable == "1") {
            $query       = 'SELECT `comment` FROM '.DBPREFIX.'module_survey_poll_result WHERE `question_id` = '.$id;
            $objComments = $objDatabase->Execute($query);
            $rowCounter  = 0;
            while(!$objComments->EOF) {
                if($objComments->fields['comment'] != "") {
                    $comments    = $objComments->fields['comment'];
                    $rowCounter += 1;
                    if ($rowCounter%2 == 0) {
                        $rowClass = "row2";
                    } else {
                        $rowClass = "row1";
                    }
                    $this->_objTpl->setVariable(array(
                        'TXT_COMMENTS' => contrexx_raw2xhtml($comments),
                        'ROW_CLASS'    => $rowClass
                    ));
                    $this->_objTpl->parse('survey_question_comments');
                }
                $objComments->MoveNext();
            }
        } else {
            $this->_objTpl->hideBlock('survey_question_commentable');
        }
    }

    //Survey Overview
    function surveyOverview() {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;
        \JS::activate('greybox');

        $this->_pageTitle = $_ARRAYLANG['TXT_SURVEY_OVERVIEW'];
        $this->_objTpl->loadTemplateFile('module_survey_overview.html');

        /* Start Paging ------------------------------------ */
        $intPos             = (isset($_GET['pos'])) ? intval(contrexx_input2raw($_GET['pos'])) : 0;
        // TODO: Never used
        $intPerPage         = $this->getPagingLimit();

        $strPagingSource    = getPaging($this->countEntries('survey_surveygroup'), $intPos, '&amp;cmd=Survey', false, $intPerPage);
        $this->_objTpl->setVariable('ENTRIES_PAGING', $strPagingSource);
        $limit = $this->getPagingLimit();                 //how many items to show per page
        $start = ($intPos) ? $intPos : 0;

        $objResult = $objDatabase->Execute('SELECT * FROM '.DBPREFIX.'module_survey_surveygroup ORDER BY id desc LIMIT '.$start.','.$limit);
        $row = 'row1';

        if ($objResult->RecordCount() == 0) {
            \Cx\Core\Csrf\Controller\Csrf::header("Location: ".ASCMS_PATH_OFFSET.ASCMS_BACKEND_PATH."/index.php?cmd=Survey&act=createOrCopy");
        }

        $this->_objTpl->setVariable(array(
            'SHOW_SURVEY_JAVASCRIPT'    => $this->getCreateSurveyJavascript(),
            'TXT_SURVEY_OVERVIEW'       => $_ARRAYLANG['TXT_SURVEY_OVERVIEW'],
            'TXT_SYMBOL'        => $_ARRAYLANG['TXT_SYMBOL'],
            'TXT_STATUS'               => $_ARRAYLANG['TXT_STATUS'],
            'TXT_HOME'               => $_ARRAYLANG['TXT_HOME'],
            'TXT_SURVEY_TITLE'           => $_ARRAYLANG['TXT_SURVEY_TITLE'],
            'TXT_CREATED_AT'            => $_ARRAYLANG['TXT_CREATED_AT'],
            'TXT_MODIFIED_AT'        => $_ARRAYLANG['TXT_MODIFIED_AT'],
            'TXT_COUNTER'        => $_ARRAYLANG['TXT_COUNTER'],
            'TXT_FUNCTIONS'        => $_ARRAYLANG['TXT_FUNCTIONS'],
            'TXT_SELECT_ALL'        => $_ARRAYLANG['TXT_SELECT_ALL'],
            'TXT_DESELECT_ALL'        => $_ARRAYLANG['TXT_DESELECT_ALL'],
            'TXT_DESCRIPTION'        => $_ARRAYLANG['TXT_DESCRIPTION'],
            'TXT_SELECT_ACTION'        => $_ARRAYLANG['TXT_SELECT_ACTION'],
            'TXT_ACTIVATE'        => $_ARRAYLANG['TXT_ACTIVATE'],
            'TXT_DEACTIVATE'        => $_ARRAYLANG['TXT_DEACTIVATE'],
            'TXT_DELETE_SELECTED'    => $_ARRAYLANG['TXT_DELETE_SELECTED']
        ));

        while(!$objResult->EOF) {
            if($objResult->fields['isActive'] == "1") {
                $activeImage = "../core/Core/View/Media/icons/led_green.gif";
                $activeTitle = $_ARRAYLANG['TXT_ACTIVE'] ;
            }
            else {
                $activeImage = "../core/Core/View/Media/icons/led_red.gif";
                $activeTitle = $_ARRAYLANG['TXT_INACTIVE'] ;
            }
            // Passign the home box status
            $checked = "";
            if($objResult->fields['isHomeBox'] == "1") {
                $checked = "checked";
            }
            // for desctiption with tool tip
            $descVar =  contrexx_remove_script_tags($objResult->fields['description']);
            $Descshort = substr($descVar, 0, 20);
            if(trim($descVar) != "") {
                $descTemp = $Descshort.'..<a href="#" title="'.$descVar.'" class="tooltip"><img border="0" src="'.ASCMS_PATH_OFFSET.ASCMS_MODULE_FOLDER.'/Survey/View/Media/comment.gif"><a>';
            }
            else {
                $descTemp = $_ARRAYLANG['TXT_NO_DESCRIPTION_TXT'];
            }

            // for survey Title with tool tip
            $surveynameVar = contrexx_raw2xhtml($objResult->fields['title']);
            $surveyShot = substr($surveynameVar, 0, 20);
            $surveyTemp = '';
            if(strlen($surveynameVar) > 20) {
                if($surveynameVar != "") {
                    $surveyTemp = $surveyShot.'..<a href="#" title="'.$surveynameVar.'" class="tooltip"><img border="0" src="'.ASCMS_PATH_OFFSET.ASCMS_MODULE_FOLDER.'/Survey/View/Media/comment.gif"><a>';
                }
            }else {
                if($surveynameVar != "") {
                    $surveyTemp = $surveyShot;
                }
            }

            $this->_objTpl->setVariable(array(
                    'TXT_SURVEY_ID'            => $objResult->fields['id'],
                    'TXT_SURVEY_TITLE_LABEL'   => $surveyTemp,
                    'TXT_SURVEY_CREATED_AT'    => $objResult->fields['created'],
                    'TXT_SURVEY_UPDATED_AT'    => $objResult->fields['updated'],
                    'TXT_SURVEY_ACTIVE_IMAGE'  => $activeImage,
                    'TXT_SURVEY_ACTIVE_TITLE'  => $activeTitle,
                    'TXT_SURVEY_DESCRIPTION'   => $descTemp,
                    'DB_YES_SURVEY_HOMEBOX'    => $checked,
                    'TXT_SURVEY_PREVIEW_TXT'   => $_ARRAYLANG['TXT_SURVEY_PREVIEW_TXT'],
                    'TXT_SURVEY_ANALYSE_TXT'   => $_ARRAYLANG['TXT_SURVEY_ANALYSE_TXT'],
                    'TXT_SURVEY_REFRESH_TXT'   => $_ARRAYLANG['TXT_SURVEY_REFRESH_TXT'],
                    'TXT_SURVEY_EDIT_TXT'      => $_ARRAYLANG['TXT_SURVEY_EDIT_TXT'],
                    'TXT_SURVEY_DELETE_TXT'    => $_ARRAYLANG['TXT_SURVEY_DELETE_TXT'],
                    'TXT_SURVEY_ACTIVE'        => $objResult->fields['isActive'],
                    'TXT_SURVEY_COUNTER'       => $objResult->fields['votes']." Antworten",
                    'ENTRY_ROWCLASS'           => $row = ($row == 'row1') ? 'row2' : 'row1',
            ));
            $this->_objTpl->parse('showEntries');
            $objResult->MoveNext();
        }
    }


//Address List Overview
// TODO: Remove methode if obsolete
    function listOverview() {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;
        $this->_pageTitle = $_ARRAYLANG['TXT_SURVEYOVERVIEW'];
        $this->_objTpl->loadTemplateFile('module_list_overview.html');
        // Parsing javascript function to the place holder.
        $this->_objTpl->setVariable(array(
                'SHOW_SURVEY_JAVASCRIPT'    =>   $this->getCreateAddressJavascript()
        ));


        /* Start Paging ------------------------------------ */
        /*    $intPos             = (isset($_GET['pos'])) ? intval($_GET['pos']) : 0;
        // TODO: Never used
            $intPerPage         = $this->getPagingLimit();

            $strPagingSource    = getPaging($this->countEntries('survey_surveygroup'), $intPos, '&amp;section=Survey&amp;cmd=activesurveys', false, $intPerPage);
            $this->_objTpl->setVariable('ENTRIES_PAGING', $strPagingSource);
        $limit = $this->getPagingLimit();                 //how many items to show per page
        $page = $_REQUEST['pos'];
        if($page){
        $start = $page;             //first item to display on this page
            }else{
            $start = 0;                //if no page var is given, set start to 0
        }    */
        /* End Paging -------------------------------------- */



        $objResult =   $objDatabase->Execute('SELECT  * FROM '.DBPREFIX.'module_survey_addresslist ORDER BY id desc');
        $row = 'row1';

        $this->_objTpl->setVariable(array(
                'TXT_ADDRESS_LIST_OVERVIEW'    => $_ARRAYLANG['TXT_ADDRESS_LIST_OVERVIEW'],
                'TXT_SYMBOL'            => $_ARRAYLANG['TXT_SYMBOL'],
                'TXT_STATUS'               => $_ARRAYLANG['TXT_STATUS'],
                'TXT_SURVEY_TITLE'           => $_ARRAYLANG['TXT_SURVEY_TITLE'],
                'TXT_CREATED_AT'                 => $_ARRAYLANG['TXT_CREATED_AT'],
                'TXT_MODIFIED_AT'        => $_ARRAYLANG['TXT_MODIFIED_AT'],
                'TXT_COUNTER'            => $_ARRAYLANG['TXT_COUNTER'],
                'TXT_FUNCTIONS'            => $_ARRAYLANG['TXT_FUNCTIONS'],
                'TXT_SELECT_ALL'        => $_ARRAYLANG['TXT_SELECT_ALL'],
                'TXT_DESELECT_ALL'        => $_ARRAYLANG['TXT_DESELECT_ALL'],
                'TXT_DESCRIPTION'        => $_ARRAYLANG['TXT_DESCRIPTION'],
                'TXT_SELECT_ACTION'        => $_ARRAYLANG['TXT_SELECT_ACTION'],
                'TXT_ACTIVATE'            => $_ARRAYLANG['TXT_ACTIVATE'],
                'TXT_DEACTIVATE'        => $_ARRAYLANG['TXT_DEACTIVATE'],
                'TXT_DELETE_SELECTED'        => $_ARRAYLANG['TXT_DELETE_SELECTED'],

        ));

        while(!$objResult->EOF) {
            /*     if($objResult->fields['isActive'] == "1") {
                     $activeImage = "../core/Core/View/Media/icons/led_green.gif";
                     $activeTitle = $_ARRAYLANG['TXT_ACTIVE'] ;
                 }
                 else {
                     $activeImage = "../core/Core/View/Media/icons/led_red.gif";
                     $activeTitle = $_ARRAYLANG['TXT_INACTIVE'] ;
                 }  */
            $list_id=$objResult->fields['id'];
            $objResultcount =   $objDatabase->Execute('SELECT  * FROM '.DBPREFIX.'module_survey_addressbook WHERE listid='.$list_id.' ORDER BY id desc');
            $Addresscount = $objResultcount->RecordCount();

            // for survey Title with tool tip
            $listnameVar =  htmlspecialchars($objResult->fields['listname'],ENT_QUOTES);
            $listnameShot = substr($listnameVar, 0, 20);
            if(strlen($listnameVar) > 20) {
                if($listnameVar != "") {
                    $listTemp = $listnameShot.'..<a href="#" title="'.htmlspecialchars($listnameVar).'" class="tooltip"><img border="0" src="'.ASCMS_PATH_OFFSET.ASCMS_MODULE_FOLDER.'/Survey/View/Media/comment.gif"><a>';
                }
            }else {
                if($listnameVar != "") {
                    $listTemp = $listnameShot;
                }
            }


            $this->_objTpl->setVariable(array(
                    'TXT_LIST_ID'           =>    $objResult->fields['id'],
                    'TXT_LIST_TITLE_LABEL'       =>    $listTemp,
                    'TXT_LIST_CREATED_AT'         =>  $objResult->fields['created'],
                    'TXT_LIST_UPDATED_AT'         =>  $objResult->fields['updated'],
                    'TXT_LIST_COUNTER'          => $Addresscount,
                    'ENTRY_ROWCLASS'                =>  $row = ($row == 'row1') ? 'row2' : 'row1',
            ));
            $this->_objTpl->parse('showEntries');
            $objResult->MoveNext();
        }

    }


    function SurveyChangeStatus() {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;
        $status = ((isset($_GET['status']))&&($_GET['status'] == 0)) ? 1 : 0;

        if (isset($_GET['status'])) {
            $id     = contrexx_input2raw($_GET['id']);
            $query = 'UPDATE '.DBPREFIX.'module_survey_surveygroup SET isActive='.$status.' WHERE id = '.$id;
            $objDatabase->Execute($query);
            if($status == 1) {
                $this->_strOkMessage = $_ARRAYLANG['TXT_SURVEY_ACTIVATED_SUCC_TXT'];
            }else {
                $this->_strOkMessage = $_ARRAYLANG['TXT_SURVEY_DEACTIVATED_SUCC_TXT'];
            }
        }

        $request_type = isset($_REQUEST['type']) ? contrexx_input2raw($_REQUEST['type']) : '';
        if($request_type == "activate") {
            $arrStatusNote = contrexx_input2raw($_POST['selectedEntriesId']);
            if($arrStatusNote != null) {
                foreach ($arrStatusNote as $noteId) {
                    $query = "UPDATE ".DBPREFIX."module_survey_surveygroup SET isActive='1' WHERE id=$noteId";
                    $objDatabase->Execute($query);
                }
            }
            $this->_strOkMessage = $_ARRAYLANG['TXT_SURVEY_ACTIVATED_SUCC_TXT'];
        } elseif($request_type == "deactivate") {
            $arrStatusNote = contrexx_input2raw($_POST['selectedEntriesId']);
            if($arrStatusNote != null) {
                foreach ($arrStatusNote as $noteId) {
                    $query = "UPDATE ".DBPREFIX."module_survey_surveygroup SET isActive='0' WHERE id=$noteId";
                    $objDatabase->Execute($query);
                }
            }
            $this->_strOkMessage = $_ARRAYLANG['TXT_SURVEY_DEACTIVATED_SUCC_TXT'];
        }
        $this->surveyOverview();
    }

    /*
    This is the Function used to change the home survey
    */
    function SurveyHomeChange() {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;

        $id           = contrexx_input2raw($_GET['id']);
        $activeStatus = $objDatabase->Execute('SELECT `isActive` FROM '.DBPREFIX.'module_survey_surveygroup WHERE id = '.$id);
        $status = $activeStatus->fields['isActive'];
        if($status == 1) {
            // Updating all the records to isHomeBox = 0
            $query = 'UPDATE '.DBPREFIX.'module_survey_surveygroup SET isHomeBox=0';
            $objDatabase->Execute($query);
            //Updaing the particular survey to home survey
            $query1 = "UPDATE ".DBPREFIX."module_survey_surveygroup SET isHomeBox=1 WHERE id=$id";
            $objDatabase->Execute($query1);

            $this->_strOkMessage = $_ARRAYLANG['TXT_SURVEY_HOME_SUCC_TXT'];
        }else {
            $this->_strErrMessage = $_ARRAYLANG['TXT_SURVEY_HOME_ERR_TXT'];
        }
        // calling the surveyOverview() function
        $this->surveyOverview();
    }

    function deleteQuestions() {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;
        $id  = contrexx_input2raw($_GET['id']);
        $lid = contrexx_input2raw($_GET['linkId']);
        if(!empty($id)) {
            $deleteQuery = 'DELETE FROM `'.DBPREFIX.'module_survey_surveyQuestions` WHERE id = '.$id;
            $objDatabase->Execute($deleteQuery);
            $deleteQuery = 'DELETE FROM `'.DBPREFIX.'module_survey_surveyAnswers` WHERE question_id = '.$id;
            $objDatabase->Execute($deleteQuery);
            $this->_strOkMessage = $_ARRAYLANG['TXT_QUESTION_DELETED_SUC_TXT'];
        }
        if($_POST['selectedEntriesId']) {
            $deleteIds = contrexx_input2raw($_POST['selectedEntriesId']);
            foreach($deleteIds as $id) {
                $deleteQuery = 'DELETE FROM `'.DBPREFIX.'module_survey_surveyQuestions` WHERE id = '.$id;
                $objDatabase->Execute($deleteQuery);
                $deleteQuery = 'DELETE FROM `'.DBPREFIX.'module_survey_surveyAnswers` WHERE question_id = '.$id;
                $objDatabase->Execute($deleteQuery);
                $this->_strOkMessage = $_ARRAYLANG['TXT_QUESTION_DELETED_SUC_TXT'];

            }
        }
        \Cx\Core\Csrf\Controller\Csrf::header("Location: ".ASCMS_PATH_OFFSET.ASCMS_BACKEND_PATH."/index.php?cmd=Survey&act=editQuestionsOverview&id=$lid&linkId=$lid&mes=deleted");
    }

    function deleteSurvey() {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;
        $id = (isset($_GET['id'])) ? contrexx_input2raw($_GET['id']) : 0;

        if(!empty($id)) {
            $deleteQuery = 'DELETE FROM `'.DBPREFIX.'module_survey_surveygroup` WHERE id = '.$id;
            $objDatabase->Execute($deleteQuery);
            $deleteQuery = 'DELETE FROM `'.DBPREFIX.'module_survey_surveyQuestions` WHERE survey_id = '.$id;
            $objDatabase->Execute($deleteQuery);
            $this->_strOkMessage = $_ARRAYLANG['TXT_SURVEY_DELETE_SUC_TXT'];
        } else {
            $deleteIds = contrexx_input2raw($_POST['selectedEntriesId']);
            foreach($deleteIds as $id) {
                $deleteQuery = 'DELETE FROM `'.DBPREFIX.'module_survey_surveygroup` WHERE id = '.$id;
                $objDatabase->Execute($deleteQuery);
                $deleteQuery = 'DELETE FROM `'.DBPREFIX.'module_survey_surveyQuestions` WHERE survey_id = '.$id;
                $objDatabase->Execute($deleteQuery);
                $this->_strOkMessage = $_ARRAYLANG['TXT_SURVEY_DELETE_SUC_TXT'];
            }
        }

        $this->surveyOverview();
    }

    // Reset the Survey
    function resetSurvey() {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;
        $id = contrexx_input2raw($_GET['id']);

        if(!empty($id)) {
            $deleteQuery = 'DELETE FROM `'.DBPREFIX.'module_survey_email` WHERE survey_id = '.$id;
            $objDatabase->Execute($deleteQuery);

            $this->_strOkMessage = $_ARRAYLANG['TXT_SURVEY_RESETED_SUC_TXT'];
        }

        $this->surveyOverview();
    }

    // Function for adding the survey
    function AddSurvey() {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;
        $this->_pageTitle = $_ARRAYLANG['TXT_SURVEY_ADD_TXT'];
        $this->_objTpl->loadTemplateFile('module_add_surveyone.html');

        $homeBoxCheck = $objDatabase->Execute('SELECT 1 FROM `'.DBPREFIX.'module_survey_surveygroup` WHERE isHomeBox="1"');
        if(!$homeBoxCheck->EOF) {
            $hiddenField = "<input type='hidden' name='hidfield' id='hidfield' value='present'>";
        }else {
            $hiddenField = "<input type='hidden' name='hidfield' id='hidfield' value=''>";
        }

        $strMessageInputHTML = new \Cx\Core\Wysiwyg\Wysiwyg('Description', '', 'full');
        $strMessageText1     = new \Cx\Core\Wysiwyg\Wysiwyg('text1', '', 'full');
        $strMessageText2     = new \Cx\Core\Wysiwyg\Wysiwyg('text2', '', 'full');
        $strMessageAftButton = new \Cx\Core\Wysiwyg\Wysiwyg('textAfterButton', '', 'full');
        $strMessageThanksMSG = new \Cx\Core\Wysiwyg\Wysiwyg('thanksMSG', '', 'full');

        // Parsing javascript function to the place holder.
        $this->_objTpl->setVariable(array(
            'HIDDEN_FIELD'                  => $hiddenField,
            'DB_SURVEY_DESC'                => $strMessageInputHTML,
            'TEXT1'                         => $strMessageText1,
            'TEXT2'                         => $strMessageText2,
            'DB_TEXT_AFTER_BUTTON'          => $strMessageAftButton,
            'THANKS_MSG'                    => $strMessageThanksMSG,
            'CREATE_SURVEY_JAVASCRIPT'      => $this->getCreateSurveyJavascript(),
            'DB_NO_SURVEY_HOMEBOX'          => "checked",
            'TXT_BUTTON'                    => $_ARRAYLANG['TXT_SURVEY_CREATE_TXT'],
            'SELECT_RESTRICTION_COOKIE'     => "checked",
            'TXT_TITLE_ADD'                 => $_ARRAYLANG['TXT_CREATE_SURVEY'],
            'TXT_TITLE'                     => $_ARRAYLANG['TXT_TITLE'],
            'TXT_UNIQUE_USER_VERIFICATION'  => $_ARRAYLANG['TXT_UNIQUE_USER_VERIFICATION'],
            'TXT_IS_HOME_BOX'               => $_ARRAYLANG['TXT_IS_HOME_BOX'],
            'TXT_YES'                       => $_ARRAYLANG['TXT_YES'],
            'TXT_NO'                        => $_ARRAYLANG['TXT_NO'],
            'TXT_DESCRIPTION'               => $_ARRAYLANG['TXT_DESCRIPTION'],
            'TXT_BEGINNING_SURVEY'          => $_ARRAYLANG['TXT_BEGINNING_SURVEY'],
            'TXT_ADDITIONALINFO_SURVEY'     => $_ARRAYLANG['TXT_ADDITIONALINFO_SURVEY'],
            'TXT_BELOW_SUBMIT'              => $_ARRAYLANG['TXT_BELOW_SUBMIT'],
            'TXT_THANK_MSG'                 => $_ARRAYLANG['TXT_THANK_MSG'],
            'TXT_COOKIE_BASED'              => $_ARRAYLANG['TXT_COOKIE_BASED'],
            'TXT_EMAIL_BASED'               => $_ARRAYLANG['TXT_EMAIL_BASED'],
            'TXT_ADDITIONAL_FIELDS_LABEL'   => $_ARRAYLANG['TXT_ADDITIONAL_FIELDS_LABEL'],
            'TXT_SALUTATION'                => $_ARRAYLANG['TXT_SALUTATION'],
            'TXT_NICKNAME'                  => $_ARRAYLANG['TXT_NICKNAME'],
            'TXT_FIRSTNAME'                 => $_ARRAYLANG['TXT_FIRSTNAME'],
            'TXT_LASTNAME'                  => $_ARRAYLANG['TXT_LASTNAME'],
            'TXT_AGEGROUP'                  => $_ARRAYLANG['TXT_AGEGROUP'],
            'TXT_EMAIL_TEXT'                => $_ARRAYLANG['TXT_EMAIL_TEXT'],
            'TXT_TELEPHONE'                 => $_ARRAYLANG['TXT_TELEPHONE'],
            'TXT_STREET'                    => $_ARRAYLANG['TXT_STREET'],
            'TXT_ZIPCODE'                   => $_ARRAYLANG['TXT_ZIPCODE'],
            'TXT_PLACEOFREC'                => $_ARRAYLANG['TXT_PLACEOFREC'],
            'TXT_HIDE'                      => $_ARRAYLANG['TXT_HIDE'],
            'TXT_SHOW'                      => $_ARRAYLANG['TXT_SHOW']
        ));

        if(isset($_POST['survey_submit'])) {
            $title          = contrexx_input2db($_POST['title']);
            $description    = contrexx_input2db($_POST['Description']);
            $method         = contrexx_input2db($_POST['votingRestrictionMethod']);
            $textAfterButton= contrexx_input2db($_POST['textAfterButton']);
            $text1          = contrexx_input2db($_POST['text1']);
            $text2          = contrexx_input2db($_POST['text2']);
            $thanksMSG      = contrexx_input2db($_POST['thanksMSG']);
            $isHome         = (contrexx_input2raw($_POST['hidfield']) == '') ? 1 : 0;

            // Insert Query for Inserting the Fields Posted
            $insertSurvey = 'INSERT INTO `'.DBPREFIX.'module_survey_surveygroup`
                            SET `title` = "'.$title.'",
                                           `UserRestriction` = "'.$method.'",
                                           `description` = "'.$description.'",
                                           `textAfterButton` = "'.$textAfterButton.'",
                                           `text1` = "'.$text1.'",
                                           `text2` = "'.$text2.'",
                       `thanksMSG` = "'.$thanksMSG.'",
                                           `isHomeBox` = "'.$isHome.'",
                                           `additional_salutation` = "'.((isset($_POST['additional_salutation'])&&($_POST['additional_salutation']=='on'))?1:0).'",
                                            `additional_nickname` = "'.((isset($_POST['additional_nickname'])&&($_POST['additional_nickname']=='on'))?1:0).'",
                                            `additional_forename` = "'.((isset($_POST['additional_forename'])&&($_POST['additional_forename']=='on'))?1:0).'",
                                            `additional_surname` = "'.((isset($_POST['additional_surname'])&&($_POST['additional_surname']=='on'))?1:0).'",
                                            `additional_agegroup` = "'.((isset($_POST['additional_agegroup'])&&($_POST['additional_agegroup']=='on'))?1:0).'",
                                            `additional_email` = "'.((isset($_POST['additional_email'])&&($_POST['additional_email']=='on'))?1:0).'",
                                            `additional_phone` = "'.((isset($_POST['additional_phone'])&&($_POST['additional_phone']=='on'))?1:0).'",
                                            `additional_street` = "'.((isset($_POST['additional_street'])&&($_POST['additional_street']=='on'))?1:0).'",
                                            `additional_zip` = "'.((isset($_POST['additional_zip'])&&($_POST['additional_zip']=='on'))?1:0).'",
                                           `additional_city` = "'.((isset($_POST['additional_city'])&&($_POST['additional_city']=='on'))?1:0).'"';
            $objDatabase->Execute($insertSurvey);

            // Last Inserted Id
            $lastId = $objDatabase->Insert_Id();
            \Cx\Core\Csrf\Controller\Csrf::header("Location: ".ASCMS_PATH_OFFSET.ASCMS_BACKEND_PATH."/index.php?cmd=Survey&act=addQuestions&surveyId=$lastId");
        }
    }


    // Function for adding the Questions to the survey
    function addQuestions() {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;
        \JS::activate('greybox');

        $this->_pageTitle = $_ARRAYLANG['TXT_SURVEY_ADDQUESTION_TXT'];
        $this->_objTpl->loadTemplateFile('module_add_survey.html');

        // Parsing javascript function to the place holder.
        $this->_objTpl->setVariable(array(
                'CREATE_SURVEY_JAVASCRIPT'              => $this->getCreateSurveyJavascript(),
                'SELECT_COMMENTABLE_NO'                 => "checked",
                'SURVEY_IMAGE_PATH'                     => ASCMS_PATH_OFFSET.ASCMS_MODULE_FOLDER.'/Survey/View/Media',
                'WELCOME_MSD'                           => $_ARRAYLANG['TXT_WELCOME_MSG'],
                'TXT_ADD_QUESTION'                      => $_ARRAYLANG['TXT_SURVEY_CREATEQUESTION_TXT'],
                'TXT_SELECT_QUESTION'                   => $_ARRAYLANG['TXT_SELECT_QUESTION'],
                'TXT_QUESTION_TYPE'                     => $_ARRAYLANG['TXT_QUESTION_TYPE'],
                'TXT_MULTIPLE_CHOICE_ONE_ANSWER'        => $_ARRAYLANG['TXT_MULTIPLE_CHOICE_ONE_ANSWER'],
                'TXT_MULTIPLE_CHOICE_MULTIPLE_ANSWER'   => $_ARRAYLANG['TXT_MULTIPLE_CHOICE_MULTIPLE_ANSWER'],
                'TXT_MATRIX_CHOICE_ONE_ANSWER_PER_ROW'  => $_ARRAYLANG['TXT_MATRIX_CHOICE_ONE_ANSWER_PER_ROW'],
                'TXT_MATRIX_CHOICE_MULTIPLE_ANSWER_PER_ROW' => $_ARRAYLANG['TXT_MATRIX_CHOICE_MULTIPLE_ANSWER_PER_ROW'],
                'TXT_SINGLE_TEXTBOX'                    => $_ARRAYLANG['TXT_SINGLE_TEXTBOX'],
                'TXT_QUESTION_TEXT'                     => $_ARRAYLANG['TXT_QUESTION_TEXT'],
                'TXT_ANSWER_CHOICE'                     => $_ARRAYLANG['TXT_ANSWER_CHOICE'],
                'TXT_ADD_COMMENT'                       => $_ARRAYLANG['TXT_ADD_COMMENT'],
                'TXT_YES'                               => $_ARRAYLANG['TXT_YES'],
                'TXT_NO'                                => $_ARRAYLANG['TXT_NO'],
                'TXT_HELP_TXT'                          => $_ARRAYLANG['TXT_HELP_TXT'],
                'TXT_HELP_IMAGE_TXT'                    => $_ARRAYLANG['TXT_HELP_IMAGE_TXT'],
                'TXT_SAVE_TXT'                          => $_ARRAYLANG['TXT_SAVE_TXT'],
                'TXT_COLUMN_CHOICE'                     => $_ARRAYLANG['TXT_COLUMN_CHOICE'],
                'TXT_MULTIPLE_TEXTBOX'                  => $_ARRAYLANG['TXT_MULTIPLE_TEXTBOX'],
                'TXT_TEXT_ROW'                => $_ARRAYLANG['TXT_TEXT_ROW'],
                'TXT_HELPONE_SEL'                       => 'none',
                'TXT_HELPTWO_SEL'                       => 'none',
                'TXT_HELPTHREE_SEL'                     => 'none',
                'TXT_HELPFOUR_SEL'                      => 'none',
                'TXT_HELPFIVE_SEL'                      => 'none',
                'TXT_HELPSIX_SEL'                       => 'none',
                'TXT_COLHIDE'                           => 'display:none',
                'TXT_RTEXTHIDE'                         => 'none',
                'TXT_HELPSEVEN_SEL'                     => 'none'
        ));
        if(isset($_POST['surveyQuestions_submit'])) {
            $surveyId      = contrexx_input2raw($_REQUEST['surveyId']);
            $questionType  = contrexx_input2raw($_POST['questionType']);
            $columnChoices = contrexx_input2raw($_POST['ColumnChoices']);
            $questionAnswers= contrexx_input2raw($_POST['QuestionAnswers']);

            $vote = 0;
            $Question = ($questionType != 7)?contrexx_input2db($_POST['Question']):contrexx_input2db($_POST['QuestionRow']);

            if(($questionType == 3) || ($questionType == 4)) {
                $options       = explode ("\n", $columnChoices);
                $ColChoices    = explode ("\n", $questionAnswers);
                $colChoic      = implode($ColChoices,";");
                $vote = array();
                foreach ($ColChoices as $key => $value) {
                    $vote[$key] = 0;
                }
                $vote = json_encode($vote);
            }else {
                $options       = explode ("\n", $questionAnswers);
                $ColChoices    = explode ("\n", $columnChoices);
                $colChoic      = "";
            }
            if($questionType == 5)
                $options[0] = "Answer";
                $commentable= contrexx_input2db($_POST['Iscomment']);
            if($questionType ==7) {
                $options[0] = "Answer";
                $commentable= contrexx_input2db($_POST['Iscomment']);
            }

            $sorting_id = 0;
            $objResult = $objDatabase->Execute('SELECT MAX(`pos`) as `pos` FROM `'.DBPREFIX.'module_survey_surveyQuestions` WHERE `survey_id` ='.$surveyId);
            $sorting_id = $objResult->fields['pos'] + 1;

            // Insert Query for Inserting the Fields Posted
            $insertSurvey = 'INSERT INTO `'.DBPREFIX.'module_survey_surveyQuestions`
                            SET `survey_id` = "'.contrexx_raw2db($surveyId).'",
                                `isCommentable` = "'.$commentable.'",
                                `QuestionType` = "'.contrexx_raw2db($questionType).'",
                                `Question` = "'.$Question.'",
                                `pos` = '.$sorting_id.',
                                `column_choice` = "'.contrexx_raw2db($colChoic).'" ';
            $objDatabase->Execute($insertSurvey);
            $lastId = $objDatabase->Insert_Id();
            for ($i=0;$i<count($options);$i++) {
                if(trim($options[$i]) != "") {
                    $insertSurvey = 'INSERT INTO `'.DBPREFIX.'module_survey_surveyAnswers`
                            SET  `question_id` = "'.$lastId.'",
                                `answer` = "'.contrexx_raw2db($options[$i]).'",
                                                `votes` = "'.contrexx_raw2db($vote).'"';
                    $objDatabase->Execute($insertSurvey);
                }
            }
            // loop for inserting the column choices
            for ($i=0;$i<count($ColChoices);$i++) {
                if($ColChoices[$i] != "") {
                    $insertSurvey = 'INSERT INTO `'.DBPREFIX.'module_survey_columnChoices`
                            SET `question_id` = "'.$lastId.'",
                                `choice` = "'.contrexx_raw2db($ColChoices[$i]).'"';
                    $objDatabase->Execute($insertSurvey);
                }
            }

            $this->_strOkMessage = $_ARRAYLANG['TXT_SURVEY_ADDED_SUC_TXT'];
            if (isset($_REQUEST['linkId']) && ($_REQUEST['linkId'] != "")) {
                $ids = contrexx_input2raw($_REQUEST['linkId']);
                $link = "index.php?cmd=Survey&act=editQuestionsOverview&id=".$ids."&linkId=".$ids."&".\Cx\Core\Csrf\Controller\Csrf::param();
                $this->_objTpl->setVariable(array(
                    'ADD_QUESTION_HERE' => '<a href="index.php?cmd=Survey&act=addQuestions&surveyId='.$surveyId.'" title="'.$_ARRAYLANG['TXT_SURVEY_ADDQUESTION_TXT'].'">
                                           '.$_ARRAYLANG['TXT_SURVEY_ADDQUESTION_ANOTHER_TXT'].'</a>',
                    'TXT_DONE'          => '<input type="button" name="Done" value="'.$_ARRAYLANG['TXT_SURVEY_DONE_TXT'].'" onclick= "window.location=\''.$link.'\'" />',
                    'TXT_PREVIEW'       => '<input type="button" name="Preview" value="'.$_ARRAYLANG['TXT_SURVEY_PREVIEW_TXT'].'" onClick="window.open('."'".'../index.php?section=Survey&cmd=surveypreview&id='.$surveyId."'".')">'
                ));
            } else {
                $link = 'index.php?cmd=Survey&act=editQuestionsOverview&id='.$surveyId.'&linkId='.$surveyId.'&'.\Cx\Core\Csrf\Controller\Csrf::param();
                $this->_objTpl->setVariable(array(
                    'ADD_QUESTION_HERE' => '<a href="index.php?cmd=Survey&act=addQuestions&surveyId='.$surveyId.'" title="'.$_ARRAYLANG['TXT_SURVEY_ADDQUESTION_TXT'].'">
                                           '.$_ARRAYLANG['TXT_SURVEY_ADDQUESTION_ANOTHER_TXT'].'</a>',
                    'TXT_DONE'          => '<input type="button" name="Done" value="'.$_ARRAYLANG['TXT_SURVEY_DONE_TXT'].'" onclick= "window.location=\''.$link.'\'" />',
                    'TXT_PREVIEW'       => '<input type="button" name="Preview" value="'.$_ARRAYLANG['TXT_SURVEY_PREVIEW_TXT'].'" onClick="window.open('."'".'../index.php?section=Survey&cmd=surveypreview&id='.$surveyId."'".')">'
                ));
            }
        }
    }


    // Function for settings of salutation and age
    function Settings() {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;
        $this->_pageTitle = $_ARRAYLANG['SETTINGS_TEXT'];
        $this->_objTpl->loadTemplateFile('module_add_settings.html');
        if(isset($_REQUEST['mes']) && ($_REQUEST['mes'] == "update")) {
            $this->_strOkMessage = $_ARRAYLANG['TXT_SURVEY_SETTING_SUCC_TXT'];
        }
        $objResult =   $objDatabase->Execute('SELECT  * FROM '.DBPREFIX.'module_survey_settings ORDER BY id desc LIMIT 1');
        // Parsing javascript function to the place holder.
        $this->_objTpl->setVariable(array(
            'CREATE_SETTING_JAVASCRIPT' => $this->getCreateSettingJavascript(),
            'TXT_SAVE_TXT'              => $_ARRAYLANG['TXT_SAVE_TXT'],
            'SETTINGS_TEXT'             => $_ARRAYLANG['SETTINGS_TEXT'],
            'ADD_SALUTATION'            => $_ARRAYLANG['ADD_SALUTATION'],
            'AGE_GROUP'                 => $_ARRAYLANG['AGE_GROUP']
        ));

        while(!$objResult->EOF) {
            $SalutationValue = $objResult->fields['salutation'];
            $AgeGroupValue = $objResult->fields['agegroup'];
            $Salutation = explode ("--", $SalutationValue);
            $AgeGroup = explode ("--", $AgeGroupValue);
            $FinalSalutation = '';
            $FinalAgeGroup = '';

            foreach($Salutation as $value) {
                if(trim($value) != "")
                    $FinalSalutation .= $value."\n";
            }
            foreach($AgeGroup as $value) {
                if(trim($value) != "")
                    $FinalAgeGroup .= $value."\n";
            }
            $this->_objTpl->setVariable(array(
                'TXT_SETTING_ID'        => contrexx_raw2xhtml($objResult->fields['id']),
                'DB_SALUTATION_VALUE'   => contrexx_raw2xhtml($FinalSalutation),
                'DB_AGEGROUP_VALUE'     => contrexx_raw2xhtml($FinalAgeGroup)
            ));
            //$this->_objTpl->parse('showEntries');*/
            $objResult->MoveNext();
        }

        if(isset($_POST['settings_submit'])) {

            $Salutation    = contrexx_input2raw($_POST['salutation']);
            $ageGroup      = contrexx_input2raw($_POST['Age_group']);
            $id            = contrexx_input2raw($_POST['settings_id']);
            $SalutationVal = explode ("\n", $Salutation);
            $ageGroupVal   = explode ("\n", $ageGroup);

            foreach($SalutationVal as $row) {
                if(trim($row) != "")
                    $FinalSalutationVal .= $row."--";
            }
            foreach($ageGroupVal as $value) {
                if(trim($value) != "")
                    $FinalageGroupVal .= $value."--";
            }

            // Insert Query for Inserting the Fields Posted
            $insertSurvey = 'UPDATE `'.DBPREFIX.'module_survey_settings` SET
                            `salutation` = "'.contrexx_raw2db($FinalSalutationVal).'",
                    `agegroup` = "'.contrexx_raw2db($FinalageGroupVal).'"
                             WHERE id = "'.$id.'"';
            $objDatabase->Execute($insertSurvey);

            \Cx\Core\Csrf\Controller\Csrf::header("Location: ".ASCMS_PATH_OFFSET.ASCMS_BACKEND_PATH."/index.php?cmd=Survey&act=settings&mes=update");
        }

    }


    function getCreateSettingJavascript() {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;
        $ENTER_SALUTATION = $_ARRAYLANG['ENTER_SALUTATION'];
        $ENTER_AGE_GROUP  = $_ARRAYLANG['ENTER_AGE_GROUP'];
        $javascript = <<<END

        <script language="JavaScript" type="text/javascript">

        function trim(sString){
              while (sString.substring(0,1) == ' '){
               sString = sString.substring(1, sString.length);
              }
              while (sString.substring(sString.length-1, sString.length) == ' '){
               sString = sString.substring(0,sString.length-1);
              }
         return sString;
        }
        function ltrim(s){
           var l=0;
           while(l < s.length && s[l] == ' ')
           {    l++; }
           return s.substring(l, s.length);
        }
        function rtrim(s){
           var r=s.length -1;
           while(r > 0 && s[r] == ' ')
           {    r-=1;    }
           return s.substring(0, r+1);
        }

       function checkValidations() {

           var salutation    = document.getElementById("salutation").value;
           var Age_group        = document.getElementById("Age_group").value;


           if(trim(salutation) == "") {
             alert("$ENTER_SALUTATION");
             document.getElementById("salutation").focus();
             document.getElementById("salutation").value="";
           return false;
           }if(trim(Age_group) == "") {
             alert("$ENTER_AGE_GROUP");
             document.getElementById("Age_group").focus();
             document.getElementById("Age_group").value="";
           return false;
           }
             return true;

       }
        </script>
END;
        return $javascript;
    }
    function getQuestionSurveyJavascript() {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;

        $TXT_SURVEY_NOITEM_SELECTED_ERR = $_ARRAYLANG['TXT_SURVEY_NOITEM_SELECTED_ERR'];
        $TXT_SURVEY_CONFIRM_DELETE_ERR  = $_ARRAYLANG['TXT_SURVEY_CONFIRM_DELETE_ERR'];
        $TXT_SURVEY_SORTING_NUMBER_ERR = $_ARRAYLANG['TXT_SURVEY_SORTING_NUMBER_ERR'];
        $TXT_SURVEY_SORTING_NUMBER_NUM_ERR  = $_ARRAYLANG['TXT_SURVEY_SORTING_NUMBER_NUM_ERR'];
        $TXT_SURVEY_SORTING_NUMBER_NOTSAME_ERR = $_ARRAYLANG['TXT_SURVEY_SORTING_NUMBER_NOTSAME_ERR'];
        $TXT_SURVEY_SELECT_ANSWER_INPUT_ERR = $_ARRAYLANG['TXT_SURVEY_SELECT_ANSWER_INPUT_ERR'];
        $TXT_SURVEY_ENTER_QUESTION_ERR = $_ARRAYLANG['TXT_SURVEY_ENTER_QUESTION_ERR'];
        $TXT_SURVEY_ENTER_ANSWER_ERR = $_ARRAYLANG['TXT_SURVEY_ENTER_ANSWER_ERR'];
        $TXT_SURVEY_ENTER_COLUMN_ERR = $_ARRAYLANG['TXT_SURVEY_ENTER_COLUMN_ERR'];
        $TXT_SURVEY_ENTER_TITLE_ERR = $_ARRAYLANG['TXT_SURVEY_ENTER_TITLE_ERR'];
        $TXT_SURVEY_HOMEBOX_ERR = $_ARRAYLANG['TXT_SURVEY_HOMEBOX_ERR'];
        $CSRF_PARAM = \Cx\Core\Csrf\Controller\Csrf::param();

        $javascript = <<<END
        <script language="JavaScript" type="text/javascript">
       function IsNumeric(strString){
        //  check for valid numeric strings
        var strValidChars = "0123456789";
          var strChar;
          var blnResult = true;
          if (strString.length == 0) return false;
          //  test strString consists of valid characters listed above
          for (i = 0; i < strString.length && blnResult == true; i++)
             {
             strChar = strString.charAt(i);
             if (strValidChars.indexOf(strChar) == -1)
                {
                blnResult = false;
                }
             }
          return blnResult;
       }
       function gup( name )
       {
         name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
         var regexS = "[\\?&]"+name+"=([^&#]*)";
         var regex = new RegExp( regexS );
         var results = regex.exec( window.location.href );
         if( results == null )
           return "";
         else
           return results[1];
       }
       function selectMultiAction() {
           with (document.frmShowQuestionEntries) {
                             var chks = document.getElementsByName('selectedEntriesId[]');
                             var hasChecked = false;
                             // Get the checkbox array length and iterate it to see if any of them is selected
                             for (var i = 0; i < chks.length; i++){
                                if (chks[i].checked){
                                      hasChecked = true;
                                      break;
                                }
                             }
                               if (!hasChecked) {
                                      alert("$TXT_SURVEY_NOITEM_SELECTED_ERR");
                                      document.frmShowQuestionEntries.frmShowEntries_MultiAction.value=0;
                                      document.frmShowQuestionEntries.frmShowEntries_MultiAction.focus();
                                      return false;
                               }
        switch (frmShowEntries_MultiAction.value) {

            case 'delete':
                if (confirm("$TXT_SURVEY_CONFIRM_DELETE_ERR")) {
                                   var qqid = gup('id');
                    action='index.php?cmd=Survey&act=deleteQuestions&$CSRF_PARAM&id='+qqid+'&linkId='+qqid;
                    submit();
                }
                else{
                  frmShowEntries_MultiAction.value=0;
                }

            break;
            default: //do nothing
        }

                if(frmShowEntries_MultiAction.value == "save"){
                             var sortText = document.getElementsByName('form_pos[]');
                             var SortArray = new Array();
                             var cond=0;
                             for (var i = 0; i < sortText.length; i++){

                   if(sortText[i].value==""){
                                            alert("$TXT_SURVEY_SORTING_NUMBER_ERR");
                                      document.frmShowQuestionEntries.frmShowEntries_MultiAction.value=0;
                                      document.frmShowQuestionEntries.frmShowEntries_MultiAction.focus();
                                            cond=1;
                                            return false;
                                            break;
                                       }
                   else if(IsNumeric(sortText[i].value) == false){
                            alert("$TXT_SURVEY_SORTING_NUMBER_NUM_ERR");
                      document.frmShowQuestionEntries.frmShowEntries_MultiAction.value=0;
                                           document.frmShowQuestionEntries.frmShowEntries_MultiAction.focus();
                                            cond=1;
                                            return false;
                                            break;
                                       }

                                  for (var j = i+1; j < sortText.length; j++){

                    if(sortText[i].value==sortText[j].value){
                                            alert("$TXT_SURVEY_SORTING_NUMBER_NOTSAME_ERR");
                                      document.frmShowQuestionEntries.frmShowEntries_MultiAction.value=0;
                                      document.frmShowQuestionEntries.frmShowEntries_MultiAction.focus();
                                            cond=1;
                                            return false;
                                            break;
                                       }

                                  }
                                      if(cond == 1){
                                         break;
                                      }
                             }
                                   var qid = gup('id');
                    action='index.php?cmd=Survey&act=editQuestionsOverview&$CSRF_PARAM&id='+qid+'&linkId='+qid+'&chg=1';
                    submit();
                  }

    }
}

       function deleteEntry(entryId){
         var qsid = gup('id');
            if(confirm("$TXT_SURVEY_CONFIRM_DELETE_ERR"))
                 window.location.replace("index.php?cmd=Survey&act=deleteQuestions&$CSRF_PARAM&id="+entryId+"&linkId="+qsid);
        }
      </script>
END;
        return $javascript;
    }
    function getEditQuestionJavascript() {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;

        $TXT_SURVEY_NOITEM_SELECTED_ERR = $_ARRAYLANG['TXT_SURVEY_NOITEM_SELECTED_ERR'];
        $TXT_SURVEY_CONFIRM_DELETE_ERR  = $_ARRAYLANG['TXT_SURVEY_CONFIRM_DELETE_ERR'];
        $TXT_SURVEY_SORTING_NUMBER_ERR = $_ARRAYLANG['TXT_SURVEY_SORTING_NUMBER_ERR'];
        $TXT_SURVEY_SORTING_NUMBER_NUM_ERR  = $_ARRAYLANG['TXT_SURVEY_SORTING_NUMBER_NUM_ERR'];
        $TXT_SURVEY_SORTING_NUMBER_NOTSAME_ERR = $_ARRAYLANG['TXT_SURVEY_SORTING_NUMBER_NOTSAME_ERR'];
        $TXT_SURVEY_SELECT_ANSWER_INPUT_ERR = $_ARRAYLANG['TXT_SURVEY_SELECT_ANSWER_INPUT_ERR'];
        $TXT_SURVEY_ENTER_QUESTION_ERR = $_ARRAYLANG['TXT_SURVEY_ENTER_QUESTION_ERR'];
        $TXT_SURVEY_ENTER_ANSWER_ERR = $_ARRAYLANG['TXT_SURVEY_ENTER_ANSWER_ERR'];
        $TXT_SURVEY_ENTER_COLUMN_ERR = $_ARRAYLANG['TXT_SURVEY_ENTER_COLUMN_ERR'];
        $TXT_SURVEY_ENTER_TITLE_ERR = $_ARRAYLANG['TXT_SURVEY_ENTER_TITLE_ERR'];
        $TXT_SURVEY_HOMEBOX_ERR = $_ARRAYLANG['TXT_SURVEY_HOMEBOX_ERR'];


        $javascript = <<<END
        <script language="JavaScript" type="text/javascript">
        function trim(sString){
              while (sString.substring(0,1) == ' '){
               sString = sString.substring(1, sString.length);
              }
              while (sString.substring(sString.length-1, sString.length) == ' '){
               sString = sString.substring(0,sString.length-1);
              }
         return sString;
        }
        function ltrim(s){
           var l=0;
           while(l < s.length && s[l] == ' ')
           {    l++; }
           return s.substring(l, s.length);
        }
        function rtrim(s){
           var r=s.length -1;
           while(r > 0 && s[r] == ' ')
           {    r-=1;    }
           return s.substring(0, r+1);
        }

       function showColumnTab(){
         var matrix = document.getElementById("questionType").value;

         var helplink;
        if((matrix == 3) || (matrix == 4)){

         document.getElementById("col").style.display='';

         }else{
         document.getElementById("col").style.display='none';
         }

     if(matrix == 5){
         document.getElementById("answer").style.display='none';
      document.getElementById("addComent").style.display='';
          document.getElementById("RowTextfield").style.display='none';
       document.getElementById("qTextfield").style.display='';
         }
    else if(matrix == 7){
      document.getElementById("addComent").style.display='none';
         document.getElementById("answer").style.display='none';
     document.getElementById("qTextfield").style.display='none';
         document.getElementById("RowTextfield").style.display='';
         }
       else{
      document.getElementById("addComent").style.display='';
         document.getElementById("answer").style.display='';
         document.getElementById("RowTextfield").style.display='none';
     document.getElementById("qTextfield").style.display='';
         }

     if(matrix == 1){
        document.getElementById("help1").style.display="";
        document.getElementById("help2").style.display="none";
        document.getElementById("help3").style.display="none";
        document.getElementById("help4").style.display="none";
        document.getElementById("help5").style.display="none";
        document.getElementById("help6").style.display="none";
        document.getElementById("help7").style.display="none";

     }else if(matrix == 2){
        document.getElementById("help1").style.display="none";
        document.getElementById("help2").style.display="";
        document.getElementById("help3").style.display="none";
        document.getElementById("help4").style.display="none";
        document.getElementById("help5").style.display="none";
        document.getElementById("help6").style.display="none";
        document.getElementById("help7").style.display="none";
     }else if(matrix == 3){
        document.getElementById("help1").style.display="none";
        document.getElementById("help2").style.display="none";
        document.getElementById("help3").style.display="";
        document.getElementById("help4").style.display="none";
        document.getElementById("help5").style.display="none";
        document.getElementById("help6").style.display="none";
        document.getElementById("help7").style.display="none";
     }else if(matrix == 4){
        document.getElementById("help1").style.display="none";
        document.getElementById("help2").style.display="none";
        document.getElementById("help3").style.display="none";
        document.getElementById("help4").style.display="";
        document.getElementById("help5").style.display="none";
        document.getElementById("help6").style.display="none";
        document.getElementById("help7").style.display="none";
     }else if(matrix == 5){
        document.getElementById("help1").style.display="none";
        document.getElementById("help2").style.display="none";
        document.getElementById("help3").style.display="none";
        document.getElementById("help4").style.display="none";
        document.getElementById("help5").style.display="";
        document.getElementById("help6").style.display="none";
        document.getElementById("help7").style.display="none";
     }else if(matrix == 6){
        document.getElementById("help1").style.display="none";
        document.getElementById("help2").style.display="none";
        document.getElementById("help3").style.display="none";
        document.getElementById("help4").style.display="none";
        document.getElementById("help5").style.display="none";
        document.getElementById("help6").style.display="";
        document.getElementById("help7").style.display="none";
     }else if(matrix == 7){
        document.getElementById("help1").style.display="none";
        document.getElementById("help2").style.display="none";
        document.getElementById("help3").style.display="none";
        document.getElementById("help4").style.display="none";
        document.getElementById("help5").style.display="none";
        document.getElementById("help6").style.display="none";
        document.getElementById("help7").style.display="";
        }
    else{
        document.getElementById("help1").style.display="none";
        document.getElementById("help2").style.display="none";
        document.getElementById("help3").style.display="none";
        document.getElementById("help4").style.display="none";
        document.getElementById("help5").style.display="none";
        document.getElementById("help6").style.display="none";
        document.getElementById("help7").style.display="none";
     }
       }
       function checkValidations_one() {

           var title = document.getElementById("title").value;

           if(trim(title) == "") {
             alert("$TXT_SURVEY_ENTER_TITLE_ERR");
             document.getElementById("title").focus();
             document.getElementById("title").value="";
           return false;
           }
           var Yeshome = document.getElementById("Yeshome").checked;
           var isHomeBox = document.getElementById("hidfield").value;
             if(Yeshome){
                  if(trim(isHomeBox) == "present") {
                    alert("$TXT_SURVEY_HOMEBOX_ERR");
                    document.getElementById("Yeshome").focus();
                  return false;
                  }
             }
             return true;

       }

       function checkValidations() {

           var questionType    = document.getElementById("questionType").value;
           var Question        = document.getElementById("Question").value;
       var QuestionRow     = document.getElementById("QuestionRow").value;
           var QuestionAnswers = document.getElementById("QuestionAnswers").value;


           if(trim(questionType) == "") {
             alert("$TXT_SURVEY_SELECT_ANSWER_INPUT_ERR");
             document.getElementById("questionType").focus();
             document.getElementById("questionType").value="";
           return false;
           }if((trim(Question) == "") && (questionType != 7)) {
             alert("$TXT_SURVEY_ENTER_QUESTION_ERR");
             document.getElementById("Question").focus();
             document.getElementById("Question").value="";
           return false;
           }if((trim(QuestionRow) == "") && (questionType == 7)) {
             alert("$TXT_SURVEY_ENTER_QUESTION_ERR");
             document.getElementById("QuestionRow").focus();
             document.getElementById("QuestionRow").value="";
           return false;
           }
       if(trim(QuestionAnswers) == "") {
            if((questionType != 5) && (questionType != 7)){
             alert("$TXT_SURVEY_ENTER_ANSWER_ERR");
             document.getElementById("QuestionAnswers").focus();
             document.getElementById("QuestionAnswers").value="";
           return false;
         }
           }
           if(questionType == 3){
             var ColumnChoices = document.getElementById("ColumnChoices").value;
             if(trim(ColumnChoices) == '') {
                    alert("$TXT_SURVEY_ENTER_COLUMN_ERR");
                    document.getElementById("ColumnChoices").focus();
                    document.getElementById("ColumnChoices").value="";
                 return false;
          }
           }
           if(questionType == 4){
             var ColumnChoices = document.addsurvey.ColumnChoices.value;
             var chio = trim(ColumnChoices);
             if(chio == '') {
                    alert("$TXT_SURVEY_ENTER_COLUMN_ERR");
                    document.getElementById("ColumnChoices").focus();
                    document.getElementById("ColumnChoices").value="";
                    return false;

          }
           }
             return true;

       }

       </script>

END;
        return $javascript;
    }

    function getCreateSurveyJavascript() {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;

        $TXT_SURVEY_NOITEM_SELECTED_ERR = $_ARRAYLANG['TXT_SURVEY_NOITEM_SELECTED_ERR'];
        $TXT_SURVEY_CONFIRM_DELETE_ERR  = $_ARRAYLANG['TXT_SURVEY_CONFIRM_DELETE_ERR'];
        $TXT_SURVEY_SORTING_NUMBER_ERR = $_ARRAYLANG['TXT_SURVEY_SORTING_NUMBER_ERR'];
        $TXT_SURVEY_SORTING_NUMBER_NUM_ERR  = $_ARRAYLANG['TXT_SURVEY_SORTING_NUMBER_NUM_ERR'];
        $TXT_SURVEY_SORTING_NUMBER_NOTSAME_ERR = $_ARRAYLANG['TXT_SURVEY_SORTING_NUMBER_NOTSAME_ERR'];
        $TXT_SURVEY_SELECT_ANSWER_INPUT_ERR = $_ARRAYLANG['TXT_SURVEY_SELECT_ANSWER_INPUT_ERR'];
        $TXT_SURVEY_ENTER_QUESTION_ERR = $_ARRAYLANG['TXT_SURVEY_ENTER_QUESTION_ERR'];
        $TXT_SURVEY_ENTER_ANSWER_ERR = $_ARRAYLANG['TXT_SURVEY_ENTER_ANSWER_ERR'];
        $TXT_SURVEY_ENTER_COLUMN_ERR = $_ARRAYLANG['TXT_SURVEY_ENTER_COLUMN_ERR'];
        $TXT_SURVEY_ENTER_TITLE_ERR = $_ARRAYLANG['TXT_SURVEY_ENTER_TITLE_ERR'];
        $TXT_SURVEY_HOMEBOX_ERR = $_ARRAYLANG['TXT_SURVEY_HOMEBOX_ERR'];
        $TXT_SURVEY_SELECT_EMAIL_ERR = $_ARRAYLANG['TXT_SURVEY_SELECT_EMAIL_ERR'];
        $TXT_SHOW = $_ARRAYLANG['TXT_SHOW'];
        $TXT_HIDE = $_ARRAYLANG['TXT_HIDE'];
        $CSRF_PARAM = \Cx\Core\Csrf\Controller\Csrf::param();

        $javascript = <<<END
        <script language="JavaScript" type="text/javascript">

        function trim(sString){
              while (sString.substring(0,1) == ' '){
               sString = sString.substring(1, sString.length);
              }
              while (sString.substring(sString.length-1, sString.length) == ' '){
               sString = sString.substring(0,sString.length-1);
              }
         return sString;
        }
        function ltrim(s){
           var l=0;
           while(l < s.length && s[l] == ' ')
           {    l++; }
           return s.substring(l, s.length);
        }
        function rtrim(s){
           var r=s.length -1;
           while(r > 0 && s[r] == ' ')
           {    r-=1;    }
           return s.substring(0, r+1);
        }
       function IsNumeric(strString){
        //  check for valid numeric strings
        var strValidChars = "0123456789";
          var strChar;
          var blnResult = true;
          if (strString.length == 0) return false;
          //  test strString consists of valid characters listed above
          for (i = 0; i < strString.length && blnResult == true; i++)
             {
             strChar = strString.charAt(i);
             if (strValidChars.indexOf(strChar) == -1)
                {
                blnResult = false;
                }
             }
          return blnResult;
       }
       function showColumnTab(){
         var matrix = document.getElementById("questionType").value;

         var helplink;
        if((matrix == 3) || (matrix == 4)){

         document.getElementById("col").style.display='';

         }else{
         document.getElementById("col").style.display='none';
         }

     if(matrix == 5){
         document.getElementById("answer").style.display='none';
      document.getElementById("addComent").style.display='';
          document.getElementById("RowTextfield").style.display='none';
       document.getElementById("qTextfield").style.display='';
         }
    else if(matrix == 7){
      document.getElementById("addComent").style.display='none';
         document.getElementById("answer").style.display='none';
     document.getElementById("qTextfield").style.display='none';
         document.getElementById("RowTextfield").style.display='';
         }
       else{
      document.getElementById("addComent").style.display='';
         document.getElementById("answer").style.display='';
         document.getElementById("RowTextfield").style.display='none';
     document.getElementById("qTextfield").style.display='';
         }

     if(matrix == 1){
        document.getElementById("help1").style.display="";
        document.getElementById("help2").style.display="none";
        document.getElementById("help3").style.display="none";
        document.getElementById("help4").style.display="none";
        document.getElementById("help5").style.display="none";
        document.getElementById("help6").style.display="none";
        document.getElementById("help7").style.display="none";
     }else if(matrix == 2){
        document.getElementById("help1").style.display="none";
        document.getElementById("help2").style.display="";
        document.getElementById("help3").style.display="none";
        document.getElementById("help4").style.display="none";
        document.getElementById("help5").style.display="none";
        document.getElementById("help6").style.display="none";
        document.getElementById("help7").style.display="none";
     }else if(matrix == 3){
        document.getElementById("help1").style.display="none";
        document.getElementById("help2").style.display="none";
        document.getElementById("help3").style.display="";
        document.getElementById("help4").style.display="none";
        document.getElementById("help5").style.display="none";
        document.getElementById("help6").style.display="none";
        document.getElementById("help7").style.display="none";
     }else if(matrix == 4){
        document.getElementById("help1").style.display="none";
        document.getElementById("help2").style.display="none";
        document.getElementById("help3").style.display="none";
        document.getElementById("help4").style.display="";
        document.getElementById("help5").style.display="none";
        document.getElementById("help6").style.display="none";
        document.getElementById("help7").style.display="none";
     }else if(matrix == 5){
        document.getElementById("help1").style.display="none";
        document.getElementById("help2").style.display="none";
        document.getElementById("help3").style.display="none";
        document.getElementById("help4").style.display="none";
        document.getElementById("help5").style.display="";
        document.getElementById("help6").style.display="none";
        document.getElementById("help7").style.display="none";
     }else if(matrix == 6){
        document.getElementById("help1").style.display="none";
        document.getElementById("help2").style.display="none";
        document.getElementById("help3").style.display="none";
        document.getElementById("help4").style.display="none";
        document.getElementById("help5").style.display="none";
        document.getElementById("help6").style.display="";
        document.getElementById("help7").style.display="none";
     }else if(matrix == 7){
        document.getElementById("help1").style.display="none";
        document.getElementById("help2").style.display="none";
        document.getElementById("help3").style.display="none";
        document.getElementById("help4").style.display="none";
        document.getElementById("help5").style.display="none";
        document.getElementById("help6").style.display="none";
        document.getElementById("help7").style.display="";
    } else {
        document.getElementById("help1").style.display="none";
        document.getElementById("help2").style.display="none";
        document.getElementById("help3").style.display="none";
        document.getElementById("help4").style.display="none";
        document.getElementById("help5").style.display="none";
        document.getElementById("help6").style.display="none";
        document.getElementById("help7").style.display="none";
     }
       }

       function checkValidations_one() {
           var title = document.getElementById("title").value;
           var Restrict = document.getElementById("votingRestrictionMethod").checked;

       if(Restrict == false) {
        var emailFiled = document.getElementById("additional_email").checked;
        if(emailFiled == false) {
            alert("$TXT_SURVEY_SELECT_EMAIL_ERR");
            return false;
        }
       }

           if(trim(title) == "") {
             alert("$TXT_SURVEY_ENTER_TITLE_ERR");
             document.getElementById("title").focus();
             document.getElementById("title").value="";
           return false;
           }
           return true;
       }

       function checkValidations() {

           var questionType    = document.getElementById("questionType").value;
           var Question        = document.getElementById("Question").value;
       var QuestionRow     = document.getElementById("QuestionRow").value;
           var QuestionAnswers = document.getElementById("QuestionAnswers").value;


           if(trim(questionType) == "") {
             alert("$TXT_SURVEY_SELECT_ANSWER_INPUT_ERR");
             document.getElementById("questionType").focus();
             document.getElementById("questionType").value="";
           return false;
           }if((trim(Question) == "") && (questionType != 7)) {
             alert("$TXT_SURVEY_ENTER_QUESTION_ERR");
             document.getElementById("Question").focus();
             document.getElementById("Question").value="";
           return false;
           }if((trim(QuestionRow) == "") && (questionType == 7)) {
             alert("$TXT_SURVEY_ENTER_QUESTION_ERR");
             document.getElementById("QuestionRow").focus();
             document.getElementById("QuestionRow").value="";
           return false;
           }
       if(trim(QuestionAnswers) == "") {
            if((questionType != 5) && (questionType != 7)){
             alert("$TXT_SURVEY_ENTER_ANSWER_ERR");
             document.getElementById("QuestionAnswers").focus();
             document.getElementById("QuestionAnswers").value="";
           return false;
         }
           }
           if(questionType == 3){
             var ColumnChoices = document.getElementById("ColumnChoices").value;
             if(trim(ColumnChoices) == '') {
                    alert("$TXT_SURVEY_ENTER_COLUMN_ERR");
                    document.getElementById("ColumnChoices").focus();
                    document.getElementById("ColumnChoices").value="";
                 return false;
          }
           }
           if(questionType == 4){
             var ColumnChoices = document.addsurvey.ColumnChoices.value;
             var chio = trim(ColumnChoices);
             if(chio == '') {
                    alert("$TXT_SURVEY_ENTER_COLUMN_ERR");
                    document.getElementById("ColumnChoices").focus();
                    document.getElementById("ColumnChoices").value="";
                    return false;

          }
           }
             return true;

       }

       function selectMultiAction() {
           with (document.frmShowSurveyEntries) {
                             var chks = document.getElementsByName('selectedEntriesId[]');
                             var hasChecked = false;
                             // Get the checkbox array length and iterate it to see if any of them is selected
                             for (var i = 0; i < chks.length; i++){
                                if (chks[i].checked){
                                      hasChecked = true;
                                      break;
                                }
                             }
                               if (!hasChecked) {
                                      alert("$TXT_SURVEY_NOITEM_SELECTED_ERR");
                                      document.frmShowSurveyEntries.frmShowEntries_MultiAction.value=0;
                                      document.frmShowSurveyEntries.frmShowEntries_MultiAction.focus();
                                      return false;
                               }
        switch (frmShowEntries_MultiAction.value) {

            case 'delete':
                if (confirm("$TXT_SURVEY_CONFIRM_DELETE_ERR")) {
                    action='index.php?cmd=Survey&act=deletesurvey&$CSRF_PARAM';
                    submit();
                }
                else{
                  frmShowEntries_MultiAction.value=0;
                }

            break;
            default: //do nothing
        }

                if(frmShowEntries_MultiAction.value == "activate"){
                    action='index.php?cmd=Survey&act=SurveyChangeStatus&type=activate&$CSRF_PARAM';
                    submit();
                }
                if(frmShowEntries_MultiAction.value == "deactivate"){
                    action='index.php?cmd=Survey&act=SurveyChangeStatus&type=deactivate&$CSRF_PARAM';
                    submit();
                }
    }
}

       function deleteEntry(entryId){
            if(confirm("$TXT_SURVEY_CONFIRM_DELETE_ERR"))
                 window.location.replace("index.php?cmd=Survey&$CSRF_PARAM&act=deletesurvey&id="+entryId);
        }
        function displayToggle(tag, elem){
            if (document.getElementById(elem).style.display == "none") {
                document.getElementById(elem).style.display="block";
                tag.innerHTML = "$TXT_HIDE";
                tag.title = "$TXT_HIDE";
            } else {
                document.getElementById(elem).style.display = "none";
                tag.innerHTML = "$TXT_SHOW";
                tag.title = "$TXT_SHOW";
            }
        }
        function activateSurvey() {
            for (var i=0; i < document.frmShowSurveyEntries.Yeshome.length; i++)
            {
                if (document.frmShowSurveyEntries.Yeshome[i].checked)
                {
                    var rad_val = document.frmShowSurveyEntries.Yeshome[i].value;
                }
            }
            document.frmShowSurveyEntries.action='index.php?cmd=Survey&act=SurveyHomeChange&id='+rad_val;
            document.frmShowSurveyEntries.submit();
        }
      </script>
END;
        return $javascript;
    }

    function getCreateAddressJavascript() {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;

        \JS::activate('jquery');

        $javascript = <<<END
        <style>
        #colchoice {
         display:none;
        }
        </style>
        <script language="JavaScript" type="text/javascript">
        $(document).ready(function() {
        defaCheck();
       });


        function defaCheck(){
                 var matrixs = document.getElementById("questionType").value;
         if((matrixs == 3) || (matrixs == 4)){
         document.getElementById("colchoice").style.display='block';
         }
        }

        function trim(sString){
              while (sString.substring(0,1) == ' '){
               sString = sString.substring(1, sString.length);
              }
              while (sString.substring(sString.length-1, sString.length) == ' '){
               sString = sString.substring(0,sString.length-1);
              }
         return sString;
        }
        function ltrim(s){
           var l=0;
           while(l < s.length && s[l] == ' ')
           {    l++; }
           return s.substring(l, s.length);
        }
        function rtrim(s){
           var r=s.length -1;
           while(r > 0 && s[r] == ' ')
           {    r-=1;    }
           return s.substring(0, r+1);
        }
       function IsNumeric(strString){
        //  check for valid numeric strings
        var strValidChars = "0123456789";
          var strChar;
          var blnResult = true;
          if (strString.length == 0) return false;
          //  test strString consists of valid characters listed above
          for (i = 0; i < strString.length && blnResult == true; i++)
             {
             strChar = strString.charAt(i);
             if (strValidChars.indexOf(strChar) == -1)
                {
                blnResult = false;
                }
             }
          return blnResult;
       }
       function showColumnTab(){
         var matrix = document.getElementById("questionType").value;
         if((matrix == 3) || (matrix == 4)){
         document.getElementById("colchoice").style.display='block';

         }else{
         document.getElementById("colchoice").style.display='none';
         }
         alert(matrix);
     if(matrix == 5){
         document.getElementById("answer").style.display='none';
         document.getElementById("labelid").innerHTML=[[QUESTION_TEXT]];
         }
        else if(matrix == 7){
         document.getElementById("answer").style.display='none';
         document.getElementById("labelid").innerHTML=[[TXT_TEXT_ROW]];
         }

   else{
         document.getElementById("answer").style.display='';
         document.getElementById("labelid").innerHTML='Question Text';
         }

       }
       function checkValidations_one() {

           var title = document.getElementById("title").value;

           if(trim(title) == "") {
             alert("Enter the title");
             document.getElementById("title").focus();
             document.getElementById("title").value="";
           return false;
           }

             return true;

       }

       function checkValidations() {


           var AddressDetails = document.getElementById("AddressDetails").value;

    if(trim(AddressDetails) == "") {
             alert("Please Enter the answer for the Question");
             document.getElementById("AddressDetails").focus();
             document.getElementById("AddressDetails").value="";
           return false;
           }

             return true;

       }

       function selectMultiAction() {
           with (document.frmShowSurveyEntries) {
                             var chks = document.getElementsByName('selectedEntriesId[]');
                             var hasChecked = false;
                             // Get the checkbox array length and iterate it to see if any of them is selected
                             for (var i = 0; i < chks.length; i++){
                                if (chks[i].checked){
                                      hasChecked = true;
                                      break;
                                }
                             }
                               if (!hasChecked) {
                                      alert("No item selected for this action");
                                      document.frmShowSurveyEntries.frmShowEntries_MultiAction.value=0;
                                      document.frmShowSurveyEntries.frmShowEntries_MultiAction.focus();
                                      return false;
                               }
        switch (frmShowEntries_MultiAction.value) {

            case 'delete':
                if (confirm("Are you Sure! You Want to delete the Entry")) {
                    action='index.php?cmd=Survey&act=deletesurvey&$CSRF_PARAM';
                    submit();
                }
                else{
                  frmShowEntries_MultiAction.value=0;
                }

            break;
            default: //do nothing
        }

                if(frmShowEntries_MultiAction.value == "activate"){
                    action='index.php?cmd=Survey&act=SurveyChangeStatus&type=activate&$CSRF_PARAM';
                    submit();
                }
                if(frmShowEntries_MultiAction.value == "deactivate"){
                    action='index.php?cmd=Survey&act=SurveyChangeStatus&type=deactivate&$CSRF_PARAM';
                    submit();
                }
    }
}

       function deleteEntry(entryId){
            if(confirm("Are you Sure! You Want to delete the Entry"))
                 window.location.replace("index.php?cmd=Survey&act=deletesurvey&$CSRF_PARAM&id="+entryId);
        }
      </script>
END;
        return $javascript;
    }

    // csv function is used to downloaded result of the survey
    function csvSurvey() {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;
        $id = contrexx_input2raw($_GET['id']);

        if(!empty($id)) {
            $objData = $objDatabase->Execute('SELECT `title` FROM `'.DBPREFIX.'module_survey_surveygroup` WHERE id  = '.$id.' ORDER BY id');
            $CsvContent = "Survey Title: ".contrexx_remove_script_tags($objData->fields['title']);

            $objResult =   $objDatabase->Execute('SELECT `id`, `Question`, `QuestionType`, `column_choice` FROM '.DBPREFIX.'module_survey_surveyQuestions WHERE survey_id = '.$id.' AND QuestionType !=7 ORDER BY pos,id DESC');
            $i=1;
            while(!$objResult->EOF) {
                $CsvContent .="\n\n";
                $Question = contrexx_remove_script_tags($objResult->fields['Question']);
                $QuestionName = str_replace(",", " ", $Question);
                $CsvContent .=";$i;$QuestionName;";

                $QuestionId = $objResult->fields['id'];
                $QuestionType=contrexx_remove_script_tags($objResult->fields['QuestionType']);
                $CsvContent .="\n\n\n";
                if($QuestionType == 3 || $QuestionType == 4) {
                    $choiceArr = explode(';', str_replace("\r", "",$objResult->fields['column_choice']));
                    $choice = implode(',', $choiceArr);
                    $CsvContent .= ";;Option;Vote [".$choice."]";
                } elseif ($QuestionType == 6) {
                    $objTextQus = $objDatabase->Execute('SELECT `answer` FROM '.DBPREFIX.'module_survey_surveyAnswers WHERE question_id = '.$QuestionId.' ORDER BY id');
                    $MultiTextQues = "";
                    while(!$objTextQus->EOF) {
                        $MultiTextQues .= trim($objTextQus->fields['answer'], "\r").";";
                        $objTextQus->MoveNext();
                    }
                    $CsvContent .=";;".$MultiTextQues;
                } else {
                    $CsvContent .=";;Option;Vote ";
                }
                $CsvContent .="\n\n";

                if($QuestionType == 5) {
                    $objResultsingle =  $objDatabase->Execute('SELECT `answers` FROM '.DBPREFIX.'module_survey_poll_result WHERE question_id = '.$QuestionId.' ORDER BY id');
                    while(!$objResultsingle->EOF) {
                        $singleText = contrexx_remove_script_tags($objResultsingle->fields['answers']);
                        if ($singleText != '') {
                            $CsvContent .=";;;$singleText;";
                            $CsvContent .="\n";
                        }
                        $objResultsingle->MoveNext();
                    }
                } elseif($QuestionType == 6) {
                    $objResultsingle =  $objDatabase->Execute('SELECT `answers` FROM '.DBPREFIX.'module_survey_poll_result WHERE question_id = '.$QuestionId.' ORDER BY id');
                    while(!$objResultsingle->EOF) {
                        $answers = json_decode($objResultsingle->fields['answers']);
                        $isEmpty = 0;
                        foreach ($answers as $answer) {
                            if ($answer != '') {
                                $isEmpty = 1;
                                break;
                            }
                        }
                        if ($isEmpty == 1) {
                            $CsvContent .=";;";
                            foreach($answers as $answer) {
                                $CsvContent .="$answer;";
                            }
                            $CsvContent .="\n";
                        }
                        $objResultsingle->MoveNext();
                    }
                } else {
                    $objResult1 =   $objDatabase->Execute('SELECT `answer`,`votes` FROM '.DBPREFIX.'module_survey_surveyAnswers WHERE question_id = '.$QuestionId.' ORDER BY id');
                    while(!$objResult1->EOF) {
                        $answer = contrexx_remove_script_tags($objResult1->fields['answer']);
                        $answerName = preg_replace("/[\n\r]/","",$answer);
                        $vote = $objResult1->fields['votes'];
                        $CsvContent .=";;$answerName;$vote";
                        $CsvContent .="\n";
                        $objResult1->MoveNext();
                    }
                }
                $i++;
                $objResult->MoveNext();
            }
            \Cx\Core\Csrf\Controller\Csrf::header("Content-Type: text/csv");
            \Cx\Core\Csrf\Controller\Csrf::header("Content-Disposition: Attachment; filename=\"export.csv\"");
            echo utf8_decode($CsvContent);
            exit;
            $this->_strOkMessage = $_ARRAYLANG['TXT_SURVEY_CLEARED_SUC_TXT'];
        }
        $this->surveyOverview();
    }

    //function to export the additional details of survey
    function csvAdditionalinfo() {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;
        $id = contrexx_input2raw($_GET['id']);
        $CsvData = '';

        if(!empty($id)) {
            $objDatasurvey = $objDatabase->Execute('SELECT `title` FROM `'.DBPREFIX.'module_survey_surveygroup` WHERE id = '.$id.' ORDER BY id');
            $CsvData .="Survey Title:";
            $CsvData .= trim(contrexx_remove_script_tags($objDatasurvey->fields['title']));

            $objDateuser = $objDatabase->Execute('SELECT * FROM `'.DBPREFIX.'module_survey_addtionalfields` WHERE survey_id  = '.$id.' ORDER BY id');

            $i=1;
            $CsvData .= "\n\n\n"."No;Date;Salutation;Nickname;Forename;Surname;Age-group;E-mail;Telephone;Street;Zipcode;City;";
            /*
             * Fetch question info
            */
            $Questionquery = 'SELECT `id` as `QuestionId`, `Question`, `QuestionType`, `isCommentable`, `column_choice`
                        FROM `'.DBPREFIX.'module_survey_surveyQuestions`
                        WHERE `survey_id` = '.$id.'
                        ORDER BY `id`';
            $objQuestion = $objDatabase->Execute($Questionquery);
            $Qno = 1;

            while (!$objQuestion->EOF) {
                $CsvData .= "Question".$Qno.";";
                $query = 'SELECT `answer` FROM `'.DBPREFIX.'module_survey_surveyAnswers` WHERE `question_id` = '.$objQuestion->fields['QuestionId'].' ORDER BY id';
                $objAnswer = $objDatabase->Execute($query);

                $answer = '';
                while (!$objAnswer->EOF) {
                    $answer = trim($objAnswer->fields['answer'], "\r");
                    if ($answer != '') {
                        $CsvData .= $answer.";";
                    }
                    $objAnswer->MoveNext();
                }
                if ($objQuestion->fields['isCommentable'] == 1) {
                    $CsvData .= "Comment;";
                }
                $Qno++;
                $objQuestion->MoveNext();
            }

            while(!$objDateuser->EOF) {
                $CsvData   .= "\n";
                $date       = trim(contrexx_raw2xhtml($objDateuser->fields['added_date']));
                $Salutation = trim(contrexx_raw2xhtml($objDateuser->fields['salutation']));
                $Nickname   = trim(contrexx_raw2xhtml($objDateuser->fields['nickname']));
                $Forename   = trim(contrexx_raw2xhtml($objDateuser->fields['forename']));
                $Surname    = trim(contrexx_raw2xhtml($objDateuser->fields['surname']));
                $Agegroup   = trim(contrexx_raw2xhtml($objDateuser->fields['agegroup']));
                $Telephone  = trim(contrexx_raw2xhtml($objDateuser->fields['phone']));
                $Street     = trim(contrexx_raw2xhtml($objDateuser->fields['street']));
                $Zipcode    = trim(contrexx_raw2xhtml($objDateuser->fields['zip']));
                $Email      = trim(contrexx_raw2xhtml($objDateuser->fields['email']));
                $City       = trim(contrexx_raw2xhtml($objDateuser->fields['city']));

                $CsvData   .= "$i;$date;$Salutation;$Nickname;$Forename;$Surname;$Agegroup;$Email;$Telephone;$Street;$Zipcode;$City;";

                $objQuestion = $objDatabase->Execute($Questionquery);
                while (!$objQuestion->EOF) {
                    $CsvData .= $objQuestion->fields['Question'].";";

                    $choiceArr = explode(';', $objQuestion->fields['column_choice']);
                    $query = "SELECT `comment`, `answers` FROM `".DBPREFIX."module_survey_poll_result`
                                WHERE `user_id` = ".$objDateuser->fields['id']."
                                AND `question_id` = ".$objQuestion->fields['QuestionId']."
                                LIMIT 1";
                    $objPollResult = $objDatabase->Execute($query);

                    $query = 'SELECT `id` as `answer_id`, `answer` FROM `'.DBPREFIX.'module_survey_surveyAnswers` WHERE `question_id` = '.$objQuestion->fields['QuestionId'].' ORDER BY id';
                    $objAnswer = $objDatabase->Execute($query);
                    $multiQno = 0;

                    while (!$objAnswer->EOF) {
                        $answer_id = $objAnswer->fields['answer_id'];
                        $poll_result = $objPollResult->fields['answers'];
                        switch ($objQuestion->fields['QuestionType']) {
                            case '1':
                                if ($answer_id == $poll_result) {
                                    $CsvData .= "x;";
                                } else {
                                    $CsvData .= ";";
                                }
                                break;
                            case '2':
                                $result = json_decode($poll_result);
                                if ($result != null) {
                                    if (in_array($answer_id, $result)) {
                                        $CsvData .= "x;";
                                    } else {
                                        $CsvData .= ";";
                                    }
                                } else {
                                    $CsvData .= ";";
                                }
                                break;
                            case '3':
                                $result = json_decode($poll_result);
                                $ansArr = array();
                                $choArr = array();

                                if (is_array($result)) {
                                    foreach ($result as $val) {
                                        $valArr = explode('_', $val);
                                        $ansArr[] = $valArr[0];
                                        $choArr[] = isset($valArr[1]) ? $valArr[1] : '';
                                    }
                                }

                                if ($result != null) {
                                    if (in_array($answer_id, $ansArr)) {
                                        $ansKey = array_keys($ansArr,$answer_id);
                                        $choKey = $choArr[$ansKey[0]];
                                        $CsvData .= trim($choiceArr[$choKey[0]], "\r").";";
                                    } else {
                                        $CsvData .= ";";
                                    }
                                } else {
                                    $CsvData .= ";";
                                }
                                break;
                            case '4':
                                $json_result = json_decode($poll_result);
                                $poll_result_str = (is_array($json_result)) ? serialize($json_result) : '';
                                if (strlen($poll_result_str) != 0) {
                                    $found = 0;
                                    foreach ($json_result as $result) {
                                        $ansCount = 0;
                                        $ansArr = array();
                                        $choArr = array();

                                        if (is_array($result)) {
                                            foreach ($result as $val) {
                                                $valArr = explode('_', $val);
                                                $ansArr[] = $valArr[0];
                                                $choArr[] = $valArr[1];
                                            }
                                        }

                                        if (in_array($answer_id, $ansArr)) {
                                            $ansKey = array_keys($ansArr,$answer_id);
                                            $temp =array();
                                            $choKey = array();
                                            foreach ($ansKey as $key) {
                                                $temp[] = $choArr[$key];
                                            }
                                            foreach ($temp as $key) {
                                                $choKey[] = trim($choiceArr[$key], "\r");
                                            }
                                            if ($found == 1) {
                                                $CsvData = substr_replace($CsvData, "", -1);
                                            }
                                            $CsvData .= implode(',',$choKey).";";
                                            $found = 1;
                                        }
                                        if (empty($ansArr) && $found == 0) {
                                            $CsvData .= ";";
                                            $found = 1;
                                        }
                                    }
                                } else {
                                    $CsvData .= ";";
                                }
                                break;
                            case 5:
                                $CsvData .= $poll_result.";";
                                break;
                            case 6:
                                $result = json_decode($poll_result);
                                $CsvData .= $result[$multiQno++].";";
                                break;
                            default:
                                $CsvData .= ";";
                        }
                        $objAnswer->MoveNext();
                    }

                    if ($objQuestion->fields['isCommentable'] == 1) {
                        $CsvData .= preg_replace("/\n|\r/", " ", $objPollResult->fields['comment']).";";
                    }
                    $objQuestion->MoveNext();
                }

                $i++;
                $objDateuser->MoveNext();
            }

            \Cx\Core\Csrf\Controller\Csrf::header("Content-Type: text/csv");
            \Cx\Core\Csrf\Controller\Csrf::header("Content-Disposition: Attachment; filename=\"exportAdditionaldetails.csv\"");
            echo utf8_decode($CsvData);
            exit;
        }
    }

    function _modifySurvey()
    {
        global $_ARRAYLANG;

        $objTpl = $this->_objTpl;
        $objTpl->loadTemplateFile("module_survey_modify_survey.html");

        $id   = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $copy = isset($_GET['copy']);

        $this->_pageTitle = !empty($id) ?  $_ARRAYLANG['TXT_SURVEY_EDIT_TXT'] : $_ARRAYLANG['TXT_CREATE_SURVEY'];

        $objSurvey = new SurveyEntry();

        $objSurvey->id                       = $id;
        $objSurvey->title                    = isset($_POST['title']) ? contrexx_input2raw($_POST['title']) : '';
        $objSurvey->description              = isset($_POST['Description']) ? contrexx_input2raw($_POST['Description']) : '';
        $objSurvey->surveyType               = isset($_POST['votingRestrictionMethod']) ? contrexx_input2raw($_POST['votingRestrictionMethod']) : 'cookie';
        $objSurvey->textBelowSubmit          = isset($_POST['below_submit_button']) ? contrexx_input2raw($_POST['below_submit_button']) : '';
        $objSurvey->textBeginSurvey          = isset($_POST['begin_survey']) ? contrexx_input2raw($_POST['begin_survey']) : '';
        $objSurvey->textBeforeSubscriberInfo = isset($_POST['before_sub_info']) ? contrexx_input2raw($_POST['before_sub_info']) : '';
        $objSurvey->textFeedbackMsg          = isset($_POST['feedback_msg']) ? contrexx_input2raw($_POST['feedback_msg']) : '';

        foreach ($objSurvey->additionalFields as $additionalField) {
            $objSurvey->{$additionalField}   = isset($_POST["additional_{$additionalField}"]) ? 1 : 0;
        }

        if (isset($_POST['save_survey'])) {
            if ($objSurvey->validate()) {
                $objSurvey->id = !$copy ? $objSurvey->id : 0;
                if ($objSurvey->save()) {
                    $this->_strOkMessage = implode("<br />", $objSurvey->okMsg);
                    $this->surveyOverview();
                    return;
                } else {
                    $this->_strErrMessage = implode("<br />", $objSurvey->errorMsg);
                }
            } else {
                $this->_strErrMessage = implode("<br />", $objSurvey->errorMsg);
            }
        } elseif (!empty($objSurvey->id)) {
            $objSurvey->get();
        }

        foreach ($objSurvey->additionalFields as $additionalField) {
            $objTpl->setVariable(array(
                $this->moduleLangVar.'_ADDITIONAL_FIELD_NAME'      => $additionalField,
                $this->moduleLangVar.'_ADDITIONAL_FIELD'           => $objSurvey->{$additionalField} ? "checked='checked'" : '',
                "TXT_{$this->moduleLangVar}_ADDITIONAL_FIELD_NAME" => $_ARRAYLANG["TXT_{$this->moduleLangVar}_ADDITIONAL_FIELD_".  strtoupper($additionalField)]
            ));

            $objTpl->parse('surveyAdditionalFields');
        }

        $objSurveyQuestionManager = new SurveyQuestionManager($objSurvey->id);

        $objTpl->setGlobalVariable(array(
            $this->moduleLangVar.'_TITLE_MODIFY'     => !empty($objSurvey->id) ?  $_ARRAYLANG['TXT_SURVEY_EDIT_TXT'] : $_ARRAYLANG['TXT_CREATE_SURVEY'],
            'TXT_'.$this->moduleLangVar.'_GENERAL'   => $_ARRAYLANG['TXT_SURVEY_GENERAL'],
            'TXT_'.$this->moduleLangVar.'_START'     => $_ARRAYLANG['TXT_SURVEY_START'],
            'TXT_'.$this->moduleLangVar.'_QUESTIONS' => $_ARRAYLANG['TXT_SURVEY_QUESTIONS'],
            'TXT_'.$this->moduleLangVar.'_FINISH'    => $_ARRAYLANG['TXT_SURVEY_FINISH'],
            'TXT_'.$this->moduleLangVar.'_FEEDBACK'  => $_ARRAYLANG['TXT_SURVEY_FEEDBACK'],
            'TXT_'.$this->moduleLangVar.'_CANCEL'    => $_ARRAYLANG['TXT_SURVEY_CANCEL'],
            'TXT_'.$this->moduleLangVar.'_NEXT'      => $_ARRAYLANG['TXT_SURVEY_NEXT'],
            'TXT_'.$this->moduleLangVar.'_SAVE'      => $_ARRAYLANG['TXT_SURVEY_SAVE'],
            'TXT_'.$this->moduleLangVar.'_MODIFY_QUESTION' => $_ARRAYLANG['TXT_SURVEY_MODIFY_QUESTION'],
            'TXT_'.$this->moduleLangVar.'_OK'        => $_ARRAYLANG['TXT_SURVEY_OK'],

            'TXT_'.$this->moduleLangVar.'_QUESTION_OVERVIEW'         => $_ARRAYLANG['TXT_QUESTION_OVERVIEW'],
            'TXT_'.$this->moduleLangVar.'_SORTING'                   => $_ARRAYLANG['TXT_SORTING'],
            'TXT_'.$this->moduleLangVar.'_QUESTION'                  => $_ARRAYLANG['TXT_QUESTION'],
            'TXT_'.$this->moduleLangVar.'_ANALYSE_QUESTION_PREVIEW'  => $_ARRAYLANG['TXT_ANALYSE_QUESTION_PREVIEW'],
            'TXT_'.$this->moduleLangVar.'_SURVEY_EDIT_TXT'           => $_ARRAYLANG['TXT_SURVEY_EDIT_TXT'],
            'TXT_'.$this->moduleLangVar.'_SURVEY_DELETE_TXT'         => $_ARRAYLANG['TXT_SURVEY_DELETE_TXT'],
            'TXT_'.$this->moduleLangVar.'_CREATED_AT'                => $_ARRAYLANG['TXT_CREATED_AT'],
            'TXT_'.$this->moduleLangVar.'_QUESTION_TYPE'             => $_ARRAYLANG['TXT_QUESTION_TYPE'],
            'TXT_'.$this->moduleLangVar.'_IS_COMMENTABLE'            => $_ARRAYLANG['TXT_IS_COMMENTABLE'],
            'TXT_'.$this->moduleLangVar.'_COUNTER'                   => $_ARRAYLANG['TXT_COUNTER'],
            'TXT_'.$this->moduleLangVar.'_FUNCTIONS'                 => $_ARRAYLANG['TXT_FUNCTIONS'],
            'TXT_'.$this->moduleLangVar.'_SELECT_ALL'                => $_ARRAYLANG['TXT_SELECT_ALL'],
            'TXT_'.$this->moduleLangVar.'_DESELECT_ALL'              => $_ARRAYLANG['TXT_DESELECT_ALL'],
            'TXT_'.$this->moduleLangVar.'_SELECT_ACTION'             => $_ARRAYLANG['TXT_SELECT_ACTION'],
            'TXT_'.$this->moduleLangVar.'_SAVE_SORTING'              => $_ARRAYLANG['TXT_SAVE_SORTING'],
            'TXT_'.$this->moduleLangVar.'_DELETE_SELECTED'           => $_ARRAYLANG['TXT_DELETE_SELECTED'],
            'TXT_'.$this->moduleLangVar.'_ADD_QUESTION'              => $_ARRAYLANG['TXT_QUESTION_ADD_TXT'],

            'SURVEY_IMAGE_PATH'                     => ASCMS_PATH_OFFSET.ASCMS_MODULE_FOLDER.'/Survey/View/Media',
            'WELCOME_MSD'                           => $_ARRAYLANG['TXT_WELCOME_MSG'],
            'TXT_ADD_QUESTION'                      => $_ARRAYLANG['TXT_SURVEY_CREATEQUESTION_TXT'],
            'TXT_SELECT_QUESTION'                   => $_ARRAYLANG['TXT_SELECT_QUESTION'],
            'TXT_QUESTION_TYPE'                     => $_ARRAYLANG['TXT_QUESTION_TYPE'],
            'TXT_MULTIPLE_CHOICE_ONE_ANSWER'        => $_ARRAYLANG['TXT_MULTIPLE_CHOICE_ONE_ANSWER'],
            'TXT_MULTIPLE_CHOICE_MULTIPLE_ANSWER'   => $_ARRAYLANG['TXT_MULTIPLE_CHOICE_MULTIPLE_ANSWER'],
            'TXT_MATRIX_CHOICE_ONE_ANSWER_PER_ROW'  => $_ARRAYLANG['TXT_MATRIX_CHOICE_ONE_ANSWER_PER_ROW'],
            'TXT_MATRIX_CHOICE_MULTIPLE_ANSWER_PER_ROW' => $_ARRAYLANG['TXT_MATRIX_CHOICE_MULTIPLE_ANSWER_PER_ROW'],
            'TXT_SINGLE_TEXTBOX'                    => $_ARRAYLANG['TXT_SINGLE_TEXTBOX'],
            'TXT_QUESTION_TEXT'                     => $_ARRAYLANG['TXT_QUESTION_TEXT'],
            'TXT_ANSWER_CHOICE'                     => $_ARRAYLANG['TXT_ANSWER_CHOICE'],
            'TXT_ADD_COMMENT'                       => $_ARRAYLANG['TXT_ADD_COMMENT'],
            'TXT_YES'                               => $_ARRAYLANG['TXT_YES'],
            'TXT_NO'                                => $_ARRAYLANG['TXT_NO'],
            'TXT_HELP_TXT'                          => $_ARRAYLANG['TXT_HELP_TXT'],
            'TXT_HELP_IMAGE_TXT'                    => $_ARRAYLANG['TXT_HELP_IMAGE_TXT'],
            'TXT_SAVE_TXT'                          => $_ARRAYLANG['TXT_SAVE_TXT'],
            'TXT_COLUMN_CHOICE'                     => $_ARRAYLANG['TXT_COLUMN_CHOICE'],
            'TXT_MULTIPLE_TEXTBOX'                  => $_ARRAYLANG['TXT_MULTIPLE_TEXTBOX'],
            'TXT_TEXT_ROW'                          => $_ARRAYLANG['TXT_TEXT_ROW'],

            $this->moduleLangVar.'_ID'              => (int) $objSurvey->id,
            $this->moduleLangVar.'_TITLE'           => contrexx_raw2xhtml($objSurvey->title),
            $this->moduleLangVar.'_DESCRIPTION'     => contrexx_raw2xhtml($objSurvey->description),
            $this->moduleLangVar.'_BEGIN_SURVEY'    => new \Cx\Core\Wysiwyg\Wysiwyg('begin_survey', contrexx_raw2xhtml($objSurvey->textBeginSurvey), 'full'),
            $this->moduleLangVar.'_BEFORE_SUB_INFO' => new \Cx\Core\Wysiwyg\Wysiwyg('before_sub_info', contrexx_raw2xhtml($objSurvey->textBeforeSubscriberInfo), 'full'),
            $this->moduleLangVar.'_BELOW_SUBMIT'    => new \Cx\Core\Wysiwyg\Wysiwyg('below_submit_button', contrexx_raw2xhtml($objSurvey->textBelowSubmit), 'full'),
            $this->moduleLangVar.'_FEEDBACK_MSG'    => new \Cx\Core\Wysiwyg\Wysiwyg('feedback_msg', contrexx_raw2xhtml($objSurvey->textFeedbackMsg), 'full'),
            $this->moduleLangVar.'_TYPE_COOKIE'     => $objSurvey->surveyType == 'cookie' ? "checked='checked'" : '',
            $this->moduleLangVar.'_TYPE_EMAIL'      => $objSurvey->surveyType == 'email' ? "checked='checked'" : '',
            $this->moduleLangVar.'_QUESTIONS'       => $objSurveyQuestionManager->showQuestions(),

            'TXT_BUTTON'                    => $_ARRAYLANG['TXT_SURVEY_CREATE_TXT'],
            'TXT_TITLE'                     => $_ARRAYLANG['TXT_TITLE'],
            'TXT_UNIQUE_USER_VERIFICATION'  => $_ARRAYLANG['TXT_UNIQUE_USER_VERIFICATION'],
            'TXT_IS_HOME_BOX'               => $_ARRAYLANG['TXT_IS_HOME_BOX'],
            'TXT_YES'                       => $_ARRAYLANG['TXT_YES'],
            'TXT_NO'                        => $_ARRAYLANG['TXT_NO'],
            'TXT_DESCRIPTION'               => $_ARRAYLANG['TXT_DESCRIPTION'],
            'TXT_BEGINNING_SURVEY'          => $_ARRAYLANG['TXT_BEGINNING_SURVEY'],
            'TXT_ADDITIONALINFO_SURVEY'     => $_ARRAYLANG['TXT_ADDITIONALINFO_SURVEY'],
            'TXT_BELOW_SUBMIT'              => $_ARRAYLANG['TXT_BELOW_SUBMIT'],
            'TXT_THANK_MSG'                 => $_ARRAYLANG['TXT_THANK_MSG'],
            'TXT_COOKIE_BASED'              => $_ARRAYLANG['TXT_COOKIE_BASED'],
            'TXT_EMAIL_BASED'               => $_ARRAYLANG['TXT_EMAIL_BASED'],
            'TXT_ADDITIONAL_FIELDS_LABEL'   => $_ARRAYLANG['TXT_ADDITIONAL_FIELDS_LABEL'],
        ));

    }
}
?>
