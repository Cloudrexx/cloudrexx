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
 * Survey
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @version        1.0.0
 * @package     cloudrexx
 * @subpackage  module_survey
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Modules\Survey\Controller;

/**
 * Survey
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @access        public
 * @version        1.0.0
 * @package     cloudrexx
 * @subpackage  module_survey
 */
class Survey {
    /**
     * Template object
     *
     * @access private
     * @var object
     */
    var $_objTpl;

    /**
     * PHP5 constructor
     *
     * @global object $objTemplate
     * @global array $_ARRAYLANG
     */
    function __construct($pageContent) {
        $this->_objTpl = new \Cx\Core\Html\Sigma('.');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);
        $this->_objTpl->setTemplate($pageContent);
    }

    /**
     * Get content page
     *
     * @access public
     */
    function getPage() {
        if(isset($_GET['cmd'])) {
            $action=$_GET['cmd'];
        } elseif(isset($_GET['act'])) {
            $action=$_GET['act'];
        } else {
            $action='';
        }
        switch ($action) {
            case 'surveypreview':
                $this->SurveyPreview();
                break;
            case 'surveyattend':
                $this->SurveyAttend();
                break;

            case 'questionpreview':
                $this->QuestionPreview();
                break;
            case 'homesurvey':
                $this->surveyOverview();
                break;
            case 'activesurveys':
                $this->activeSurveys();
                break;
            case 'surveybyId':
                $this->surveyById();
                break;
            default:
                $this->surveyOverview();
                break;
        }
        return $this->_objTpl->get();
    }


    /**
     * Show onerview page
     *
     * @access Authendicated
     */
    function checkUserRestriction($type, $surveyId, $email) {
        global $_ARRAYLANG,$objDatabase;
        $retVal = '';

        $ipId=$_SERVER['REMOTE_ADDR'];
        if($type == "cookie") {
            $value = isset($_COOKIE["votingcookie_$surveyId"]) ? contrexx_input2raw($_COOKIE["votingcookie_$surveyId"]) : '';
            $arrValue = explode ("-", $value);
            if(in_array($ipId, $arrValue))
                $retVal = "yes";
            else
                $retVal = "no";
        }elseif($type == "email") {
            $useremail = $email;
            // Selecting the email from the survey_email table
            $Getemail = $objDatabase->Execute('SELECT email FROM '.DBPREFIX.'module_survey_email WHERE
                                                 email="'.$useremail.'" AND survey_id="'.$surveyId.'"');
            $ret = $Getemail->RecordCount();   // Getting the record count
            if($ret == 0)
                $retVal = "no";
            else
                $retVal = "yes";
        }
        return $retVal;
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
    /**
     * Show onerview page of active surveys
     *
     * @access Authendicated
     */
    function activeSurveys() {
        global $_ARRAYLANG,$objDatabase;

        /* Start Paging ------------------------------------ */
        $intPos             = (isset($_GET['pos'])) ? intval(contrexx_input2raw($_GET['pos'])) : 0;
        // TODO: Never used
        $intPerPage         = $this->getPagingLimit();

        $activesurveyQuery = 'SELECT  * FROM '.DBPREFIX.'module_survey_surveygroup WHERE isActive !=0 ORDER BY id desc';

        $strPagingSource    = getPaging($this->countEntriesOfJoin($activesurveyQuery), $intPos, '&amp;section=Survey&amp;cmd=activesurveys', false, $intPerPage);
        $this->_objTpl->setVariable('ENTRIES_PAGING', $strPagingSource);
        $limit = $this->getPagingLimit();                 //how many items to show per page
        $page = isset($_REQUEST['pos']) ? contrexx_input2raw($_REQUEST['pos']) : 0;
        if($page) {
            $start = $page;             //first item to display on this page
        }else {
            $start = 0;                //if no page var is given, set start to 0
        }
        /* End Paging -------------------------------------- */
        $objResult =   $objDatabase->Execute('SELECT  * FROM '.DBPREFIX.'module_survey_surveygroup WHERE isActive !=0 ORDER BY id desc LIMIT '.$start.', '.$limit);
        $row = 'row2';

        $this->_objTpl->setVariable(array(
            'TXT_SURVEY_OVERVIEW'    => $_ARRAYLANG['TXT_SURVEY_OVERVIEW'],
            'TXT_STATUS'               => $_ARRAYLANG['TXT_STATUS'],
            'TXT_SURVEY_TITLE'           => $_ARRAYLANG['TXT_SURVEY_TITLE'],
            'TXT_CREATED_AT'            => $_ARRAYLANG['TXT_CREATED_AT'],
            'TXT_MODIFIED_AT'        => $_ARRAYLANG['TXT_MODIFIED_AT'],
            'TXT_COUNTER'        => $_ARRAYLANG['TXT_COUNTER'],
            'TXT_FUNCTIONS'        => $_ARRAYLANG['TXT_FUNCTIONS'],
            'TXT_ACTIVATE'        => $_ARRAYLANG['TXT_ACTIVATE'],
            'TXT_DEACTIVATE'        => $_ARRAYLANG['TXT_DEACTIVATE'],
            'TXT_DESCRIPTION'        => $_ARRAYLANG['TXT_DESCRIPTION'],
            'TXT_DELETE_SELECTED'    => $_ARRAYLANG['TXT_DELETE_SELECTED'],
            'ACTIVESYRVEY_JAVASCRIPT'   => $this->getShowActiveSurveyJavaScript(),
        ));

        while(!$objResult->EOF) {
            if($objResult->fields['isActive'] == "1") {
                $activeImage = ASCMS_PATH_OFFSET.ASCMS_MODULE_FOLDER.'/Survey/View/Media/led_green.gif';
                $activeTitle = '';
            }
            else {
                $activeImage = ASCMS_PATH_OFFSET.ASCMS_MODULE_FOLDER.'/Survey/View/Media/led_red.gif';
                $activeTitle = $_ARRAYLANG['TXT_INACTIVE'] ;
            }
            $descVar = $objResult->fields['description'];
            $Descshort = substr($descVar, 0, 20);
            if($descVar != "") {
                $descTemp  = $Descshort;
                $descTitle = $descVar;
            } else {
                $descTemp = $_ARRAYLANG['TXT_NODESCRIPTION'];
            }
            $survnam =  $objResult->fields['title'];
            $surName = substr($survnam, 0, 20);
            if(strlen($survnam) > 20) {
                if($survnam != "") {
                    $SurveyTemp = $surName;
                    $surveyTitle = $survnam;
                }
            }else {
                if($survnam != "") {
                    $SurveyTemp = $surName;
                    $surveyTitle = $survnam;
                }
            }
            $this->_objTpl->setVariable(array(
                'TXT_SURVEY_ID'                => contrexx_raw2xhtml($objResult->fields['id']),
                'TXT_SURVEY_TITLE_LABEL'    => contrexx_raw2xhtml($SurveyTemp),
                'TXT_SURVEY_CREATED_AT'         => contrexx_raw2xhtml($objResult->fields['created']),
                'TXT_SURVEY_UPDATED_AT'         => contrexx_raw2xhtml($objResult->fields['updated']),
                'TXT_SURVEY_ACTIVE_IMAGE'       => $activeImage,
                'TXT_SURVEY_ACTIVE_TITLE'       => $activeTitle,
                'TXT_SURVEY_DESCRIPTION'        => contrexx_raw2xhtml($descTemp),
                'TXT_SURVEY_DESC_TITLE'         => contrexx_raw2xhtml($descTitle),
                'TXT_SURVEY_NAME_TITLE'         => contrexx_raw2xhtml($surveyTitle),
                'TXT_SURVEY_ACTIVE'             => $objResult->fields['isActive'],
                'TXT_SURVEY_COUNTER'        => contrexx_raw2xhtml($objResult->fields['votes']),
                'ENTRY_ROWCLASS'                => $row = ($row == 'row1') ? 'row2' : 'row1',
            ));
            $this->_objTpl->parse('showEntries');
            $objResult->MoveNext();
        }
    }

    /**
     * Javascript function for active survey functrion
     *
     * @access Authendicated
     */
    function getShowActiveSurveyJavaScript() {
        global $_ARRAYLANG;
        $javascript = <<<END
        <script language="JavaScript" src="lib/javascript/set_checkboxes.js" type="text/javascript"></script>
END;
        return $javascript;

    }
    /**
     * Show Showing the survey question based on the id passed and allowing the calculation for that
     *
     * @access Authendicated
     */
    function surveyById() {
        global $_ARRAYLANG,$objDatabase;

        // Getting the id of the particular survey from the request
        $idOfSurvey = isset($_REQUEST['id']) ? contrexx_input2raw($_REQUEST['id']) : 0;

        //Query to get the Question and answer details for that particular id.
        $QuestionDatas = $objDatabase->Execute('SELECT groups.title As title,
                                                        groups.*,
                                                        Questions.id AS questionId,
                                                        Questions.isCommentable,
                                                        groups.isHomeBox,
                                                        Questions.QuestionType,
                                                        Questions.Question,
                                                        Questions.pos,
                                                        Questions.column_choice
                                                FROM '.DBPREFIX.'module_survey_surveygroup AS groups
                                                LEFT JOIN '.DBPREFIX.'module_survey_surveyQuestions AS Questions
                                                ON groups.id = Questions.survey_id
                                                WHERE groups.isActive != "0"
                                                AND groups.id='.$idOfSurvey.'
                                                ORDER BY Questions.pos,Questions.id DESC');

        $Restriction     = $QuestionDatas->fields['UserRestriction'];
        $surveyId        = $QuestionDatas->fields['id'];
        $textAfterButton = $QuestionDatas->fields['textAfterButton'];
        $text1           = $QuestionDatas->fields['text1'];
        $text2           = $QuestionDatas->fields['text2'];
        $emailAdd        = isset($_POST['additional_email']) ? trim(contrexx_input2raw($_POST['additional_email'])) : '';
        // Checking whether it is restricted or not.
        $isRestricted    = $this->checkUserRestriction($Restriction,$surveyId,$emailAdd);

        if($isRestricted == "no") {
            $this->_objTpl->setVariable(array(
                'DB_TEXT_AFTER_BUTTON' => contrexx_remove_script_tags($textAfterButton),
                'TEXT1'                => contrexx_remove_script_tags($text1),
                'TEXT2'                => contrexx_remove_script_tags($text2)
            ));

            $count = $QuestionDatas->RecordCount();
            if(!$QuestionDatas->EOF) {
                // Static Place holders for labels
                $this->_objTpl->setVariable(array(
                    'VOTING_SURVEY_JAVASCRIPT' => $this->getVotingSurveyJavascript(),
                    'TXT_SUBMIT'               => '<input type="submit" class="btn btn-default" name="submit_survey" value="'.$_ARRAYLANG['TXT_SUBMIT'].'" style="margin-left:0px;" onclick="return AdditionalValidate()">',
                    'TXT_CANCEL'               => '<input type="reset" class="btn btn-default" name="submit_reset" value="'.$_ARRAYLANG['TXT_CANCEL'].'" style="margin-left:149px;">'
                ));
            } else {
                $this->_objTpl->setVariable(array(
                    'TXT_SUBMIT' => $_ARRAYLANG['TXT_NO_SURVEY_HOME']
                ));
            }

            $text_row = '';
            $cou = 1;
            while(!$QuestionDatas->EOF) {
                // This is the additional field information
                $additionalInfo = $QuestionDatas->fields['additional_salutation']."-".
                                    $QuestionDatas->fields['additional_nickname']."-".
                                    $QuestionDatas->fields['additional_forename']."-".
                                    $QuestionDatas->fields['additional_surname']."-".
                                    $QuestionDatas->fields['additional_agegroup']."-".
                                    $QuestionDatas->fields['additional_phone']."-".
                                    $QuestionDatas->fields['additional_street']."-".
                                    $QuestionDatas->fields['additional_zip']."-".
                                    $QuestionDatas->fields['additional_city']."-".
                                    $QuestionDatas->fields['additional_email'];

                $answerId          = $QuestionDatas->fields['questionId'];
                $InputType         = $QuestionDatas->fields['QuestionType'];
                $TextRowTitle      = $QuestionDatas->fields['Question'];
                $IsCommentable     = $QuestionDatas->fields['isCommentable'];
                $Column_choice     = $QuestionDatas->fields['column_choice'];
                $SurvId            = $QuestionDatas->fields['id'];
                $additional_fields = $this->_create_additional_input_fields($QuestionDatas);
                $inputtyp[]        = $InputType;

                if($IsCommentable == "1") {
                    $commentBox = "<div style='clear:both;padding-top: 10px;'><label style='vertical-align:top;width:150px;float:left;'>$_ARRAYLANG[TXT_COMMENT]</label><textarea name='comment_".$cou."' rows='6' cols='50' style='width:520px;'></textarea></div>";
                }else {
                    $commentBox = '';
                }

                $query     = "SELECT id, answer FROM ".DBPREFIX."module_survey_surveyAnswers WHERE question_id='$answerId' ORDER BY id";
                $objResult = $objDatabase->Execute($query);

                $SurveyOptionText = "";
                if(!empty($Column_choice)) {
                    $col = explode (";", $Column_choice);
                    $SurveyOptionText .= "<table width='100%'><tr><td>&nbsp;</td>";
                    for($i=0;$i<count($col); $i++) {
                        if(trim($col[$i]) != "") {
                            $SurveyOptionText .="<td style='padding-right:10px;text-align: center;'>".(contrexx_raw2xhtml($col[$i]))."</td>";
                        }
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
                                $SurveyOptionText .="<div class='radio'><label><input type='radio' name='votingoption_$cou' value='".contrexx_raw2xhtml($objResult->fields['id'])."' />".contrexx_raw2xhtml($objResult->fields['answer'])."</label></div>";
                                break;
                            case "2":
                                $SurveyOptionText .="<div class='checkbox'><label><input type='checkbox' name='votingoption_".$cou."[]' value='".contrexx_raw2xhtml($objResult->fields['id'])."' />".contrexx_raw2xhtml($objResult->fields['answer'])."</label></div>";
                                break;
                            case "3":
                                $SurveyOptionText .="<tr><td width='150px;'>".contrexx_raw2xhtml($objResult->fields['answer'])."</td>";
                                for($i=0;$i<count($col); $i++) {
                                    if(trim($col[$i]) != "") {
                                        $SurveyOptionText .="<td align='center'><input style='float:none;' type='radio' name='votingoption_".$cou."_".$j."' value='".contrexx_raw2xhtml($objResult->fields['id'])."_".$i."' /></td>";
                                    }
                                }
                                $SurveyOptionText .="</tr>";
                                $j=$j+1;
                                break;
                            case "4":
                                $SurveyOptionText .="<tr><td width='150px;'>".contrexx_raw2xhtml($objResult->fields['answer'])."</td>";
                                for($i=0;$i<count($col); $i++) {
                                    if(trim($col[$i]) != "") {
                                        $SurveyOptionText .="<td align='center'><input style='float:none;' type='checkbox' name='votingoption_".$cou."_".$j."[]' value='".contrexx_raw2xhtml($objResult->fields['id'])."_".$i."' /></td>";
                                    }
                                }
                                $SurveyOptionText .="</tr>";
                                $j=$j+1;
                                break;
                            case "5":
                                $SurveyOptionText .="<tr> <td width='150px'>&nbsp;</td> <td><input style='float:left;width:250px;margin-left:150px;' type='text' name='votingoption_$cou' value='' />
                                <input type='hidden' name='votingoptions_".$cou."' value='".contrexx_raw2xhtml($objResult->fields['id'])."' /> </td></tr>";
                                break;
                            case "6":
                                $SurveyOptionText .="<tr>
                                 <td width='144' style='display:block;'>".contrexx_raw2xhtml($objResult->fields['answer'])."</td>
                                 <td><input style='float:left;width:250px;' type='text' name='votingoption_$cou"."_".contrexx_raw2xhtml($objResult->fields['id'])."' value='' />
                                 <input type='hidden' name='votingoption_".$cou."[]' value='".contrexx_raw2xhtml($objResult->fields['id'])."' />
                                 </td></tr>";
                                break;
                            case "7":
                                $text_row = contrexx_raw2xhtml($TextRowTitle);
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

                $quest          = contrexx_raw2xhtml($QuestionDatas->fields['Question']);
                $question_wrap  = wordwrap($quest,130,"<br />\n");
                if($InputType != 7) {
                    $SurveyOptionTexts = $SurveyOptionText;
                    $question_wrap = wordwrap($quest,130,"<br />\n");
                    if ($InputType == "5") {
                        $question_wrap = wordwrap($quest,130,"<br />\n")."<br>";
                    }
                }else {
                    $SurveyOptionTexts  = $text_row;
                    $question_wrap      = "";
                }
                $this->_objTpl->setVariable(array(
                    'GRAND_TITLE'        => contrexx_raw2xhtml($QuestionDatas->fields['title']),
                    'SURVEY_TITLE'          => $question_wrap,
                    'SURVEY_OPTIONS_TEXT'   => $SurveyOptionTexts,
                    'SURVEY_COMMENT_BOX'    => $commentBox,
                    'SURVEY_ID'             => $idOfSurvey,
                    'SURVEY_TEXT_ROW'       => $text_row,
                    'TXT_ADDINFO'           => '<input type="hidden" id="addInfo" name="addInfo" value="'.contrexx_raw2xhtml($additionalInfo).'">',
                    'TXT_HIDDENFIELD'       => '<input type="hidden" name="Survey_id_'.$cou.'" value="'.contrexx_raw2xhtml($QuestionDatas->fields['questionId']).'"/>'
                ));
                $this->_objTpl->parse('Total_surveys');
                $cou++;
                $QuestionDatas->MoveNext();
            }
            $inpTypStr = implode(",", $inputtyp);

            // Place Holders for addtional fields in the database
            if (sizeof($additional_fields)) {
                $this->_objTpl->parse('additional_fields');
                foreach ($additional_fields as $field) {
                    list($name, $label, $tag) = $field;
                    $this->_objTpl->setVariable(array(
                        'VOTING_ADDITIONAL_INPUT_LABEL' => contrexx_remove_script_tags($label),
                        'VOTING_ADDITIONAL_INPUT'       => contrexx_remove_script_tags($tag),
                        'VOTING_ADDITIONAL_NAME'        => contrexx_remove_script_tags($name)
                    ));
                    $this->_objTpl->parse('additional_elements');
                }
            } else {
                $this->_objTpl->parse('additional_fields');
            }

            // Calculation when the survey is submitted
            if(isset($_POST['submit_survey'])) {
                //Checking whether the answered to any single question
                $AnswerCountCheck      = 0;
                $AnswerQuestCountCheck = 0;
                $skippedCountCheck     = 0;

                for($i=1;$i<=$count;$i++) {
                    $votes = isset($_REQUEST["votingoption_$i"]) ? contrexx_input2raw($_REQUEST["votingoption_$i"]) : '';
                    $question_id = $_REQUEST["Survey_id_$i"];
                    // Option Count calculation
                    $query = "SELECT id, answer FROM ".DBPREFIX."module_survey_surveyAnswers WHERE
                           question_id='$question_id' ORDER BY id";
                    $objResultcount = $objDatabase->Execute($query);
                    $countss = $objResultcount->RecordCount();

                    $typ = $i-1;
                    $type = $inputtyp[$typ];

                    if(!empty($type)) {
                        switch($type) {
                        case "1":
                            if(!empty($votes)) {
                                $AnswerCountCheck++;
                                $AnswerQuestCountCheck++;
                            }else {
                                $skippedCountCheck++;
                            }
                            break;
                        case "2":
                            if(!empty($votes)) {
                                $AnswerCountCheck++;
                                $AnswerQuestCountCheck++;
                            }else {
                                $skippedCountCheck++;
                            }
                            break;
                        case "3":
                            $votearr = '';
                            for($j=1;$j<=$countss;$j++) {
                                $votess = isset($_REQUEST["votingoption_".$i."_".$j]) ? contrexx_input2raw($_REQUEST["votingoption_".$i."_".$j]) : null;
                                $votearr .= $votess;
                                if(!empty($votess)) {
                                    $AnswerCountCheck++;
                                }
                            }

                            (!empty($votearr)) ? $AnswerQuestCountCheck++ : $skippedCountCheck++;

                            $votearr = "";
                            break;
                        case "4":
                            $votearr = '';
                            for($j=1;$j<=$countss;$j++) {
                                $votess = isset($_REQUEST["votingoption_".$i."_".$j]) ? contrexx_input2raw($_REQUEST["votingoption_".$i."_".$j]) : array();
                                foreach($votess as $id) {
                                    $votearr .= $id;
                                    if(!empty($id)) {
                                        $AnswerCountCheck++;
                                    }
                                }
                            }
                            (!empty($votearr)) ? $AnswerQuestCountCheck++ : $skippedCountCheck++;
                            $votearr = "";
                            break;
                        case "5":
                            if(!empty($votes)) {
                                $vot = isset($_REQUEST["votingoptions_$i"]) ? contrexx_input2raw($_REQUEST["votingoptions_$i"]) : '';
                                $AnswerQuestCountCheck++;
                            }else {
                                $skippedCountCheck++;
                            }
                            break;
                        case "6":
                            $ansids = contrexx_input2raw($_REQUEST["votingoption_$i"]);
                            $s=0;
                            foreach($ansids as $id) {
                                $values = isset($_REQUEST["votingoption_".$i."_".$id]) ? contrexx_input2raw($_REQUEST["votingoption_".$i."_".$id]) : '';
                                if(!empty($values)) {
                                    $s = 1;
                                    $AnswerCountCheck++;
                                }
                            }
                            ($s == 1) ? $AnswerQuestCountCheck++ : $skippedCountCheck++;
                            break;
                        }
                    }
                }

                if($AnswerQuestCountCheck == 0) {
                    $this->_objTpl->setVariable(array(
                        'SURVEY_SUCCESS' => "<div class='text-danger'>$_ARRAYLANG[TXT_SURVEY_NO_ANSWERED_ERR]</div>"
                    ));
                } else {    // End Of if the checking for the attend any one question
                    $emailAdd = isset($_POST['additional_email']) ? trim($_POST['additional_email']) : '';
                    $user_id  = 0;
                    // Checking whether it is restricted or not.
                    //$isRestricted = $this->checkUserRestriction($Restriction,$surveyId,$emailAdd);
                    // making thr count increment for survey response

                    $query="UPDATE ".DBPREFIX."module_survey_surveygroup set votes=votes+1 WHERE id='".$SurvId."'";
                    $objDatabase->Execute($query);

                    // Query for inserting additional details
                    $sql = 'INSERT INTO '. DBPREFIX .'module_survey_addtionalfields SET ' .
                            "survey_id  = '". intval($SurvId)                               . "', ".
                            "salutation = '". (isset($_POST['additional_salutation'])?contrexx_input2db($_POST['additional_salutation']):'') . "', ".
                            "nickname   = '". (isset($_POST['additional_nickname'])?contrexx_input2db($_POST['additional_nickname']):'') . "', ".
                            "forename   = '". (isset($_POST['additional_forename'])?contrexx_input2db($_POST['additional_forename']):'') . "', ".
                            "surname    = '". (isset($_POST['additional_surname'])?contrexx_input2db($_POST['additional_surname']):'') . "', ".
                            "agegroup   = '". (isset($_POST['additional_agegroup'])?contrexx_input2db($_POST['additional_agegroup']):'') . "', ".
                            "phone      = '". (isset($_POST['additional_phone'])?contrexx_input2db($_POST['additional_phone']):'') . "', ".
                            "street     = '". (isset($_POST['additional_street'])?contrexx_input2db($_POST['additional_street']):'') . "', ".
                            "zip        = '". (isset($_POST['additional_zip'])?contrexx_input2db($_POST['additional_zip']):'') . "', ".
                            "city       = '". (isset($_POST['additional_city'])?contrexx_input2db($_POST['additional_city']):'') . "', ".
                            "email      = '". $emailAdd . "'  ";
                    $objDatabase->Execute($sql);
                    $user_id = $objDatabase->Insert_ID();

                    for($i=1;$i<=$count;$i++) {
                        $votes       = isset($_REQUEST["votingoption_$i"]) ? contrexx_input2raw($_REQUEST["votingoption_$i"]) : '';
                        $question_id = contrexx_input2raw($_REQUEST["Survey_id_$i"]);
                        // Option Count calculation
                        $query = "SELECT id, answer FROM ".DBPREFIX."module_survey_surveyAnswers WHERE
                   question_id='$question_id' ORDER BY id";
                        $objResultcount = $objDatabase->Execute($query);
                        $countss        = $objResultcount->RecordCount();

                        $comment = '';
                        $answers = '';
                        $typ  = $i-1;
                        $type = $inputtyp[$typ];

                        if(!empty($type)) {
                            switch($type) {
                                case "1":
                                    $comment = isset($_REQUEST["comment_$i"]) ? contrexx_input2raw($_REQUEST["comment_$i"]) : '';
                                    $answers = isset($_REQUEST['votingoption_'.$i]) ? contrexx_input2raw($_REQUEST['votingoption_'.$i]) : '';
                                    if(!empty($votes)) {
                                        $query="UPDATE ".DBPREFIX."module_survey_surveyAnswers set votes=votes+1 WHERE id='".$votes."'";
                                        $objDatabase->Execute($query);
                                        $query="UPDATE ".DBPREFIX."module_survey_surveyQuestions set votes=votes+1
                                            WHERE id='".$question_id."'";
                                        $objDatabase->Execute($query);
                                    }else {
                                        $query="UPDATE ".DBPREFIX."module_survey_surveyQuestions set skipped=skipped+1
                                            WHERE id='".$question_id."'";
                                        $objDatabase->Execute($query);
                                    }
                                break;
                                case "2":
                                    $comment = isset($_REQUEST["comment_$i"]) ? contrexx_input2raw($_REQUEST["comment_$i"]) : '';
                                    $answers = isset($_REQUEST["votingoption_$i"]) ? json_encode(contrexx_input2raw($_REQUEST["votingoption_$i"])) : '';
                                    if(!empty($votes)) {
                                        foreach($votes as $id) {
                                            $query="UPDATE ".DBPREFIX."module_survey_surveyAnswers set votes=votes+1 WHERE id='".$id."'";
                                            $objDatabase->Execute($query);
                                        }
                                        $query="UPDATE ".DBPREFIX."module_survey_surveyQuestions set votes=votes+1
                                            WHERE id='".$question_id."'";
                                        $objDatabase->Execute($query);
                                    }else {
                                        $query="UPDATE ".DBPREFIX."module_survey_surveyQuestions set skipped=skipped+1
                                            WHERE id='".$question_id."'";
                                        $objDatabase->Execute($query);
                                    }
                                break;
                                case "3":
                                    $comment = isset($_REQUEST["comment_$i"]) ? contrexx_input2raw($_REQUEST["comment_$i"]) : '';
                                    $answerarr = array();
                                    $choiceVote = array();

                                    $query = "SELECT `column_choice` FROM ".DBPREFIX."module_survey_surveyQuestions WHERE id = ".$question_id;
                                    $objChoices = $objDatabase->Execute($query);
                                    $choices = explode(';',$objChoices->fields['column_choice']);

                                    $query = "SELECT `votes` FROM ".DBPREFIX."module_survey_surveyAnswers WHERE `question_id` = ".$question_id." ORDER BY id";
                                    $objChoiceAns = $objDatabase->Execute($query);

                                    $choice_count = 0;
                                    while (!$objChoiceAns->EOF) {
                                        if ($objChoiceAns->fields['votes'] != '') {
                                            $choiceVote[] = json_decode($objChoiceAns->fields['votes']);
                                        } else {
                                            $choiceVote[$choice_count] = array();
                                            foreach ($choices as $key => $choice) {
                                                $choiceVote[$choice_count][$key] = 0;
                                            }
                                        }
                                        $choice_count++;
                                        $objChoiceAns->MoveNext();
                                    }

                                    for($j=1;$j<=$countss;$j++) {
                                        $votess = isset($_REQUEST["votingoption_".$i."_".$j]) ? contrexx_input2raw($_REQUEST["votingoption_".$i."_".$j]) : '';
                                        $votearr .= $votess;
                                        $answerarr[] = $votess;
                                        $temp = explode('_', $votess);
                                        if (isset($temp[1])) {
                                            $choiceVote[$j-1][$temp[1]]++;
                                        }

                                        $query="UPDATE ".DBPREFIX."module_survey_surveyAnswers set votes='".json_encode($choiceVote[$j-1])."' WHERE id='".$temp[0]."'";
                                        $objDatabase->Execute($query);
                                    }

                                    $answers = json_encode($answerarr);
                                    if(!empty($votearr)) {
                                        $query="UPDATE ".DBPREFIX."module_survey_surveyQuestions set votes=votes+1
                                            WHERE id='".$question_id."'";
                                        $objDatabase->Execute($query);
                                    }
                                    else {
                                        $query="UPDATE ".DBPREFIX."module_survey_surveyQuestions set skipped=skipped+1
                                            WHERE id='".$question_id."'";
                                        $objDatabase->Execute($query);
                                    }
                                    $votearr = "";
                                break;
                                case "4":
                                    $comment = isset($_REQUEST["comment_$i"]) ? contrexx_input2raw($_REQUEST["comment_$i"]) : '';
                                    $answerarr = array();
                                    $choiceVote = array();

                                    $query = "SELECT `column_choice` FROM ".DBPREFIX."module_survey_surveyQuestions WHERE id = ".$question_id;
                                    $objChoices = $objDatabase->Execute($query);
                                    $choices = explode(';',$objChoices->fields['column_choice']);
                                    $query = "SELECT `votes` FROM ".DBPREFIX."module_survey_surveyAnswers WHERE `question_id` = ".$question_id." ORDER BY id";
                                    $objChoiceAns = $objDatabase->Execute($query);

                                    $choice_count = 0;
                                    while (!$objChoiceAns->EOF) {
                                        if ($objChoiceAns->fields['votes'] != '' && strlen($objChoiceAns->fields['votes']) > 5) {
                                            $choiceVote[] = json_decode($objChoiceAns->fields['votes']);
                                        } else {
                                            $choiceVote[$choice_count] = array();
                                            foreach ($choices as $key => $choice) {
                                                $choiceVote[$choice_count][$key] = 0;
                                            }
                                        }
                                        $choice_count++;
                                        $objChoiceAns->MoveNext();
                                    }

                                    for($j=1;$j<=$countss;$j++) {
                                        $votess = isset($_REQUEST["votingoption_".$i."_".$j]) ? contrexx_input2raw($_REQUEST["votingoption_".$i."_".$j]) : array();
                                        $answerarr[] = $votess;
                                        foreach($votess as $id) {
                                            $votearr .= $id;
                                            $temp = explode('_', $id);
                                            if (isset($temp[1])) {
                                                $choiceVote[$j-1][$temp[1]]++;
                                            }
                                        }

                                        $votessid = !empty($votess) ? explode('_', $votess[0]) : 0;
                                        $query="UPDATE ".DBPREFIX."module_survey_surveyAnswers set votes='".json_encode($choiceVote[$j-1])."' WHERE id='".$votessid[0]."'";
                                        $objDatabase->Execute($query);
                                    }

                                    $answers = json_encode($answerarr);
                                    if(!empty($votearr)) {
                                        $query="UPDATE ".DBPREFIX."module_survey_surveyQuestions set votes=votes+1
                                            WHERE id='".$question_id."'";
                                        $objDatabase->Execute($query);
                                    }
                                    else {
                                        $query="UPDATE ".DBPREFIX."module_survey_surveyQuestions set skipped=skipped+1
                                            WHERE id='".$question_id."'";
                                        $objDatabase->Execute($query);
                                    }
                                    $votearr = "";
                                break;
                                case "5":
                                    $comment = isset($_REQUEST["comment_$i"]) ? contrexx_input2raw($_REQUEST["comment_$i"]) : '';
                                    $answers = isset($_REQUEST["votingoption_$i"]) ? contrexx_input2raw($_REQUEST["votingoption_$i"]) : '';
                                    if(!empty($votes)) {
                                        $vot = contrexx_input2raw($_REQUEST["votingoptions_$i"]);
                                        $query="UPDATE ".DBPREFIX."module_survey_surveyAnswers set votes=votes+1 WHERE id='".$vot."'";
                                        $objDatabase->Execute($query);
                                        $query="UPDATE ".DBPREFIX."module_survey_surveyQuestions set votes=votes+1
                                            WHERE id='".$question_id."'";
                                        $objDatabase->Execute($query);
                                    } else {
                                        $query="UPDATE ".DBPREFIX."module_survey_surveyQuestions set skipped=skipped+1
                                            WHERE id='".$question_id."'";
                                        $objDatabase->Execute($query);
                                    }
                                break;
                                case "6":
                                    $comment = contrexx_input2raw($_REQUEST["comment_$i"]);
                                    $answerarr = array();
                                    $ansids = contrexx_input2raw($_REQUEST["votingoption_$i"]);
                                    $s=0;
                                    foreach($ansids as $id) {
                                        $values = contrexx_input2raw($_REQUEST["votingoption_".$i."_".$id]);
                                        $answerarr[] = $values;
                                        if(!empty($values)) {
                                            $s = 1;
                                            $query="UPDATE ".DBPREFIX."module_survey_surveyAnswers set votes=votes+1 WHERE id='".$id."'";
                                            $objDatabase->Execute($query);
                                        }
                                    }
                                    $answers = json_encode($answerarr);
                                    if($s == 1) {
                                        $query="UPDATE ".DBPREFIX."module_survey_surveyQuestions set votes=votes+1
                                            WHERE id='".$question_id."'";
                                        $objDatabase->Execute($query);
                                    } else {
                                        $query="UPDATE ".DBPREFIX."module_survey_surveyQuestions set skipped=skipped+1
                                            WHERE id='".$question_id."'";
                                        $objDatabase->Execute($query);
                                    }
                                    break;
                            }
                        }

                        //Insert answers and corresponding questions with userid
                        $query = "INSERT INTO `".DBPREFIX."module_survey_poll_result`
                                    SET `survey_id` = ".$surveyId.",
                                        `question_id` = ".contrexx_raw2db($question_id).",
                                        `user_id`     = ".contrexx_raw2db($user_id).",
                                        `comment`     = '".contrexx_raw2db($comment)."',
                                        `answers`     = '".contrexx_raw2db($answers)."'";
                        $objDatabase->Execute($query);
                    }

                    // Insrting for user restriction tables          $Restriction;           $surveyId;
                    $objFWUser = \FWUser::getFWUserObject();
                    $useremail = $objFWUser->objUser->getEmail();
                    $ipId=$_SERVER['REMOTE_ADDR'];
                    if($Restriction == "email") {
                        $insertSurvey = 'INSERT INTO `'.DBPREFIX.'module_survey_email`
                            SET `id` = "",
                                `survey_id` = "'.$surveyId.'",
                                `email` = "'.contrexx_raw2db($emailAdd).'",
                                `voted` = "1" ';
                        $objDatabase->Execute($insertSurvey);
                    }else {
                        $cookieVal = isset($_COOKIE["votingcookie_$surveyId"]) ? contrexx_input2raw($_COOKIE["votingcookie_$surveyId"]) : '';
                        $cookieVal = $cookieVal."-".$ipId;
                        setcookie ("votingcookie_$surveyId", $cookieVal, time()+3600*24); // 1 Day
                    }

                    \Cx\Core\Csrf\Controller\Csrf::header("Location: ".CONTREXX_SCRIPT_PATH."?section=Survey&cmd=surveyattend&id=".$idOfSurvey);
                    $this->_objTpl->setVariable(array(
                        'SURVEY_SUCCESS' => "<div style='padding:0px; color:rgb(2, 146, 2);'>$_ARRAYLANG[TXT_SURVEY_COMPLETED]</div>"
                    ));
                }// End Of else the checking for the attend any one question
            }
        } elseif ($surveyId == "") { // end of user restriction checking condition
            $this->_objTpl->setVariable(array(
                'TXT_SUBMIT' => "<div class='text-danger'>$_ARRAYLANG[TXT_NO_SURVEY_HOME]</div>"
            ));
        } else {
            $this->_objTpl->setVariable(array(
                'SURVEY_SUCCESS' => "<div class='text-danger'>$_ARRAYLANG[TXT_SURVEY_ALREADY_ATTEND]</div>"
            ));
        }
    }


    function _create_additional_input_fields($settings) {
        global $_ARRAYLANG,$objDatabase;

        $input_template = '<input name="%name" id="%name" type="%type"  style="width:250px;"/>';
        $input_template_textarea = '<textarea name="%name" id="%name" > </textarea>';
        $objResult =   $objDatabase->Execute('SELECT  * FROM '.DBPREFIX.'module_survey_settings ORDER BY id desc LIMIT 1');
        $SalutationValue = $objResult->fields['salutation'];
        $AgeGroupValue = $objResult->fields['agegroup'];
        $Salutation = explode ("--", $SalutationValue);
        $AgeGroup = explode ("--", $AgeGroupValue);
        $input_template_SelectBox = '<select style="width:256px;" name="%name" id="%name" >
                                   <option value="0">Bitte w&auml;hlen</option>';
        foreach($Salutation as $row) {
            if(trim($row) != "")
                $input_template_SelectBox .= '<option value="'.$row.'">'.$row.'</option>';
        }
        $input_template_SelectBox .= '</select>';

        $input_template_AgeSelectBox = '<select style="width:256px;" name="%name" id="%name" >
                                   <option value="0">Bitte w&auml;hlen</option>';
        foreach($AgeGroup as $row) {
            if(trim($row) != "")
                $input_template_AgeSelectBox .= '<option value="'.$row.'">'.$row.'</option>';
        }
        $input_template_AgeSelectBox .= '</select>';

        $additionals = array(
                'additional_salutation' => array('select',     $_ARRAYLANG['TXT_SALUTATION_TXT']."&nbsp;<font color='red'>*</font>"),
                'additional_nickname' => array('text',     $_ARRAYLANG['TXT_ADDITIONAL_NICKNAME']."&nbsp;<font color='red'>*</font>"),
                'additional_forename' => array('text',     $_ARRAYLANG['TXT_ADDITIONAL_FORENAME']."&nbsp;<font color='red'>*</font>"),
                'additional_surname'  => array('text',     $_ARRAYLANG['TXT_ADDITIONAL_SURNAME' ]."&nbsp;<font color='red'>*</font>"),
                'additional_agegroup' => array('select',     $_ARRAYLANG['TXT_AGEGROUP_TXT']."&nbsp;<font color='red'>*</font>"),
                'additional_phone'    => array('text',     $_ARRAYLANG['TXT_ADDITIONAL_PHONE'   ]."&nbsp;<font color='red'></font>"),
                'additional_street'   => array('text',     $_ARRAYLANG['TXT_ADDITIONAL_STREET'  ]."&nbsp;<font color='red'>*</font>"),
                'additional_zip'      => array('text',     $_ARRAYLANG['TXT_ADDITIONAL_ZIP'     ]."&nbsp;<font color='red'>*</font>"),
                'additional_city'     => array('text',     $_ARRAYLANG['TXT_ADDITIONAL_CITY'    ]."&nbsp;<font color='red'>*</font>"),
                'additional_email'    => array('text',     $_ARRAYLANG['TXT_ADDITIONAL_EMAIL'   ]."&nbsp;<font color='red'>*</font>"),
                //'additional_comment'  => array('textarea', $_ARRAYLANG['TXT_ADDITIONAL_COMMENT' ]."&nbsp;<font color='red'>*</font>"),
        );
        $retval = array();

        foreach ($additionals as $name => $data) {
            if (!$settings->fields[$name]) continue;

            list($type, $label) = $data;
            if($name == "additional_salutation") {
                $input_tag =
                        str_replace('%name',  $name,
                        str_replace('%label', $label,
                        str_replace('%type',  $type,
                        ($type == 'select' ? $input_template_SelectBox : $input_template)
                )));
            }else {
                $input_tag =
                        str_replace('%name',  $name,
                        str_replace('%label', $label,
                        str_replace('%type',  $type,
                        ($type == 'select' ? $input_template_AgeSelectBox : $input_template)
                )));
            }
            $retval[] = array($name, $label, $input_tag);
        }
        return $retval;
    }

    /**
     * Show onerview page
     *
     * @access Authendicated
     */
    function surveyOverview() {
        global $_ARRAYLANG,$objDatabase;
        //Query to get the Question and answer details.
        $QuestionDatas = $objDatabase->Execute('SELECT groups.title As title,
                                                        groups.*,
                                                        Questions.id AS questionId,
                                                        Questions.isCommentable,
                                                        groups.isHomeBox,
                                                        Questions.
                                                        QuestionType,
                                                        Questions.Question,
                                                        Questions.pos,
                                                        Questions.column_choice
                                                FROM '.DBPREFIX.'module_survey_surveygroup AS groups
                                                LEFT JOIN '.DBPREFIX.'module_survey_surveyQuestions AS Questions
                                                ON groups.id=Questions.survey_id
                                                WHERE groups.isActive != "0"
                                                AND isHomeBox="1"
                                                ORDER BY Questions.pos,Questions.id DESC');
        $Restriction     = $QuestionDatas->fields['UserRestriction'];
        $surveyId        = $QuestionDatas->fields['id'];
        $textAfterButton = $QuestionDatas->fields['textAfterButton'];
        $text1           = $QuestionDatas->fields['text1'];
        $text2           = $QuestionDatas->fields['text2'];
        $emailAdd        = isset($_POST['additional_email']) ? trim(contrexx_input2raw($_POST['additional_email'])) : '';
        // Checking whether it is restricted or not.
        $isRestricted    = $this->checkUserRestriction($Restriction,$surveyId,$emailAdd);

        if($isRestricted == "no") {
            $this->_objTpl->setVariable(array(
                'DB_TEXT_AFTER_BUTTON' => contrexx_remove_script_tags($textAfterButton),
                'TEXT1'                => contrexx_remove_script_tags($text1),
                'TEXT2'                => contrexx_remove_script_tags($text2)
            ));

            $count = $QuestionDatas->RecordCount();
            if(!$QuestionDatas->EOF) {
                // Static Place holders for labels
                $this->_objTpl->setVariable(array(
                    'VOTING_SURVEY_JAVASCRIPT' => $this->getVotingSurveyJavascript(),
                    'TXT_SUBMIT'               => '<input class="btn btn-default" type="submit" name="submit_survey" value="'.$_ARRAYLANG['TXT_SUBMIT'].'" style="margin-left:0px;" onclick="return AdditionalValidate()">',
                    'TXT_CANCEL'               => '<input class="btn btn-default" type="reset" name="submit_reset" value="'.$_ARRAYLANG['TXT_CANCEL'].'" style="margin-left:149px;">'
                ));
            } else {
                $this->_objTpl->setVariable(array(
                    'TXT_SUBMIT' => $_ARRAYLANG['TXT_NO_SURVEY_HOME']
                ));
            }

            $text_row = '';
            $cou = 1;
            while(!$QuestionDatas->EOF) {
                // This is the additional field information
                $additionalInfo = $QuestionDatas->fields['additional_salutation']."-".
                                    $QuestionDatas->fields['additional_nickname']."-".
                                    $QuestionDatas->fields['additional_forename']."-".
                                    $QuestionDatas->fields['additional_surname']."-".
                                    $QuestionDatas->fields['additional_agegroup']."-".
                                    $QuestionDatas->fields['additional_phone']."-".
                                    $QuestionDatas->fields['additional_street']."-".
                                    $QuestionDatas->fields['additional_zip']."-".
                                    $QuestionDatas->fields['additional_city']."-".
                                    $QuestionDatas->fields['additional_email'];

                $answerId          = $QuestionDatas->fields['questionId'];
                $InputType         = $QuestionDatas->fields['QuestionType'];
                $TextRowTitle      = $QuestionDatas->fields['Question'];
                $IsCommentable     = $QuestionDatas->fields['isCommentable'];
                $Column_choice     = $QuestionDatas->fields['column_choice'];
                $SurvId            = $QuestionDatas->fields['id'];
                $additional_fields = $this->_create_additional_input_fields($QuestionDatas);
                $inputtyp[]        = $InputType;

                if($IsCommentable == "1") {
                    $commentBox = "<div style='clear:both;padding-top: 10px;'><label style='vertical-align:top;width:150px;float:left;'>$_ARRAYLANG[TXT_COMMENT]</label><textarea name='comment_".$cou."' rows='6' cols='50' style='width:520px;'></textarea></div>";
                }else {
                    $commentBox = '';
                }

                $query     = "SELECT id, answer FROM ".DBPREFIX."module_survey_surveyAnswers WHERE question_id='$answerId' ORDER BY id";
                $objResult = $objDatabase->Execute($query);

                $SurveyOptionText = "";
                if(!empty($Column_choice)) {
                    $col = explode (";", $Column_choice);
                    $SurveyOptionText .= "<table width='100%'><tr><td>&nbsp;</td>";
                    for($i=0;$i<count($col); $i++) {
                        if(trim($col[$i]) != "") {
                            $SurveyOptionText .="<td style='padding-right:10px;text-align: center;'>".(contrexx_raw2xhtml($col[$i]))."</td>";
                        }
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
                                $SurveyOptionText .="<div class='radio'><label><input type='radio' name='votingoption_$cou' value='".contrexx_raw2xhtml($objResult->fields['id'])."' />".contrexx_raw2xhtml($objResult->fields['answer'])."</label></div>";
                                break;
                            case "2":
                                $SurveyOptionText .="<div class='checkbox'><label><input type='checkbox' name='votingoption_".$cou."[]' value='".contrexx_raw2xhtml($objResult->fields['id'])."' />".contrexx_raw2xhtml($objResult->fields['answer'])."</label></div>";
                                break;
                            case "3":
                                $SurveyOptionText .="<tr><td width='150px;'>".contrexx_raw2xhtml($objResult->fields['answer'])."</td>";
                                for($i=0;$i<count($col); $i++) {
                                    if(trim($col[$i]) != "") {
                                        $SurveyOptionText .="<td align='center'><input style='float:none;' type='radio' name='votingoption_".$cou."_".$j."' value='".contrexx_raw2xhtml($objResult->fields['id'])."_".$i."' /></td>";
                                    }
                                }
                                $SurveyOptionText .="</tr>";
                                $j=$j+1;
                                break;
                            case "4":
                                $SurveyOptionText .="<tr><td width='150px;'>".contrexx_raw2xhtml($objResult->fields['answer'])."</td>";
                                for($i=0;$i<count($col); $i++) {
                                    if(trim($col[$i]) != "") {
                                        $SurveyOptionText .="<td align='center'><input style='float:none;' type='checkbox' name='votingoption_".$cou."_".$j."[]' value='".contrexx_raw2xhtml($objResult->fields['id'])."_".$i."' /></td>";
                                    }
                                }
                                $SurveyOptionText .="</tr>";
                                $j=$j+1;
                                break;
                            case "5":
                                $SurveyOptionText .="<tr> <td width='150px'>&nbsp;</td> <td><input style='float:left;width:250px;margin-left:150px;' type='text' name='votingoption_$cou' value='' />
                                <input type='hidden' name='votingoptions_".$cou."' value='".contrexx_raw2xhtml($objResult->fields['id'])."' /> </td></tr>";
                                break;
                            case "6":
                                $SurveyOptionText .="<tr>
                                 <td width='144' style='display:block;'>".contrexx_raw2xhtml($objResult->fields['answer'])."</td>
                                 <td><input style='float:left;width:250px;' type='text' name='votingoption_$cou"."_".contrexx_raw2xhtml($objResult->fields['id'])."' value='' />
                                 <input type='hidden' name='votingoption_".$cou."[]' value='".contrexx_raw2xhtml($objResult->fields['id'])."' />
                                 </td></tr>";
                                break;
                            case "7":
                                $text_row = contrexx_raw2xhtml($TextRowTitle);
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

                $quest          = contrexx_raw2xhtml($QuestionDatas->fields['Question']);
                $question_wrap  = wordwrap($quest,130,"<br />\n");
                if($InputType != 7) {
                    $SurveyOptionTexts = $SurveyOptionText;
                    $question_wrap = wordwrap($quest,130,"<br />\n");
                    if ($InputType == "5") {
                        $question_wrap = wordwrap($quest,130,"<br />\n")."<br>";
                    }
                }else {
                    $SurveyOptionTexts  = $text_row;
                    $question_wrap      = "";
                }
                $this->_objTpl->setVariable(array(
                    'GRAND_TITLE'        => contrexx_raw2xhtml($QuestionDatas->fields['title']),
                    'SURVEY_TITLE'          => $question_wrap,
                    'SURVEY_OPTIONS_TEXT'   => $SurveyOptionTexts,
                    'SURVEY_COMMENT_BOX'    => $commentBox,
                    'SURVEY_ID'             => $idOfSurvey,
                    'SURVEY_TEXT_ROW'       => $text_row,
                    'TXT_ADDINFO'           => '<input type="hidden" id="addInfo" name="addInfo" value="'.contrexx_raw2xhtml($additionalInfo).'">',
                    'TXT_HIDDENFIELD'       => '<input type="hidden" name="Survey_id_'.$cou.'" value="'.contrexx_raw2xhtml($QuestionDatas->fields['questionId']).'"/>'
                ));
                $this->_objTpl->parse('Total_surveys');
                $cou++;
                $QuestionDatas->MoveNext();
            }
            $inpTypStr = implode(",", $inputtyp);

            // Place Holders for addtional fields in the database
            if (sizeof($additional_fields)) {
                $this->_objTpl->parse('additional_fields');
                foreach ($additional_fields as $field) {
                    list($name, $label, $tag) = $field;
                    $this->_objTpl->setVariable(array(
                        'VOTING_ADDITIONAL_INPUT_LABEL' => contrexx_remove_script_tags($label),
                        'VOTING_ADDITIONAL_INPUT'       => contrexx_remove_script_tags($tag),
                        'VOTING_ADDITIONAL_NAME'        => contrexx_remove_script_tags($name)
                    ));
                    $this->_objTpl->parse('additional_elements');
                }
            } else {
                $this->_objTpl->parse('additional_fields');
            }

            // Calculation when the survey is submitted
            if(isset($_POST['submit_survey'])) {
                //Checking whether the answered to any single question
                $AnswerCountCheck      = 0;
                $AnswerQuestCountCheck = 0;
                $skippedCountCheck     = 0;

                for($i=1;$i<=$count;$i++) {
                    $votes = isset($_REQUEST["votingoption_$i"]) ? contrexx_input2raw($_REQUEST["votingoption_$i"]) : '';
                    $question_id = $_REQUEST["Survey_id_$i"];
                    // Option Count calculation
                    $query = "SELECT id, answer FROM ".DBPREFIX."module_survey_surveyAnswers WHERE
                           question_id='$question_id' ORDER BY id";
                    $objResultcount = $objDatabase->Execute($query);
                    $countss = $objResultcount->RecordCount();

                    $typ = $i-1;
                    $type = $inputtyp[$typ];

                    if(!empty($type)) {
                        switch($type) {
                        case "1":
                            if(!empty($votes)) {
                                $AnswerCountCheck++;
                                $AnswerQuestCountCheck++;
                            }else {
                                $skippedCountCheck++;
                            }
                            break;
                        case "2":
                            if(!empty($votes)) {
                                $AnswerCountCheck++;
                                $AnswerQuestCountCheck++;
                            }else {
                                $skippedCountCheck++;
                            }
                            break;
                        case "3":
                            $votearr = '';
                            for($j=1;$j<=$countss;$j++) {
                                $votess = isset($_REQUEST["votingoption_".$i."_".$j]) ? contrexx_input2raw($_REQUEST["votingoption_".$i."_".$j]) : null;
                                $votearr .= $votess;
                                if(!empty($votess)) {
                                    $AnswerCountCheck++;
                                }
                            }

                            (!empty($votearr)) ? $AnswerQuestCountCheck++ : $skippedCountCheck++;

                            $votearr = "";
                            break;
                        case "4":
                            $votearr = '';
                            for($j=1;$j<=$countss;$j++) {
                                $votess = isset($_REQUEST["votingoption_".$i."_".$j]) ? contrexx_input2raw($_REQUEST["votingoption_".$i."_".$j]) : array();
                                foreach($votess as $id) {
                                    $votearr .= $id;
                                    if(!empty($id)) {
                                        $AnswerCountCheck++;
                                    }
                                }
                            }
                            (!empty($votearr)) ? $AnswerQuestCountCheck++ : $skippedCountCheck++;
                            $votearr = "";
                            break;
                        case "5":
                            if(!empty($votes)) {
                                $vot = isset($_REQUEST["votingoptions_$i"]) ? contrexx_input2raw($_REQUEST["votingoptions_$i"]) : '';
                                $AnswerQuestCountCheck++;
                            }else {
                                $skippedCountCheck++;
                            }
                            break;
                        case "6":
                            $ansids = contrexx_input2raw($_REQUEST["votingoption_$i"]);
                            $s=0;
                            foreach($ansids as $id) {
                                $values = isset($_REQUEST["votingoption_".$i."_".$id]) ? contrexx_input2raw($_REQUEST["votingoption_".$i."_".$id]) : '';
                                if(!empty($values)) {
                                    $s = 1;
                                    $AnswerCountCheck++;
                                }
                            }
                            ($s == 1) ? $AnswerQuestCountCheck++ : $skippedCountCheck++;
                            break;
                        }
                    }
                }

                if($AnswerQuestCountCheck == 0) {
                    $this->_objTpl->setVariable(array(
                        'SURVEY_SUCCESS' => "<div class='text-danger'>$_ARRAYLANG[TXT_SURVEY_NO_ANSWERED_ERR]</div>"
                    ));
                } else {    // End Of if the checking for the attend any one question
                    $emailAdd = isset($_POST['additional_email']) ? trim($_POST['additional_email']) : '';
                    $user_id  = 0;
                    // Checking whether it is restricted or not.
                    //$isRestricted = $this->checkUserRestriction($Restriction,$surveyId,$emailAdd);
                    // making thr count increment for survey response

                    $query="UPDATE ".DBPREFIX."module_survey_surveygroup set votes=votes+1 WHERE id='".$SurvId."'";
                    $objDatabase->Execute($query);

                    // Query for inserting additional details
                    $sql = 'INSERT INTO '. DBPREFIX .'module_survey_addtionalfields SET ' .
                            "survey_id  = '". intval($SurvId)                               . "', ".
                            "salutation = '". (isset($_POST['additional_salutation'])?contrexx_input2db($_POST['additional_salutation']):'') . "', ".
                            "nickname   = '". (isset($_POST['additional_nickname'])?contrexx_input2db($_POST['additional_nickname']):'') . "', ".
                            "forename   = '". (isset($_POST['additional_forename'])?contrexx_input2db($_POST['additional_forename']):'') . "', ".
                            "surname    = '". (isset($_POST['additional_surname'])?contrexx_input2db($_POST['additional_surname']):'') . "', ".
                            "agegroup   = '". (isset($_POST['additional_agegroup'])?contrexx_input2db($_POST['additional_agegroup']):'') . "', ".
                            "phone      = '". (isset($_POST['additional_phone'])?contrexx_input2db($_POST['additional_phone']):'') . "', ".
                            "street     = '". (isset($_POST['additional_street'])?contrexx_input2db($_POST['additional_street']):'') . "', ".
                            "zip        = '". (isset($_POST['additional_zip'])?contrexx_input2db($_POST['additional_zip']):'') . "', ".
                            "city       = '". (isset($_POST['additional_city'])?contrexx_input2db($_POST['additional_city']):'') . "', ".
                            "email      = '". $emailAdd . "'  ";
                    $objDatabase->Execute($sql);
                    $user_id = $objDatabase->Insert_ID();

                    for($i=1;$i<=$count;$i++) {
                        $votes       = isset($_REQUEST["votingoption_$i"]) ? contrexx_input2raw($_REQUEST["votingoption_$i"]) : '';
                        $question_id = contrexx_input2raw($_REQUEST["Survey_id_$i"]);
                        // Option Count calculation
                        $query = "SELECT id, answer FROM ".DBPREFIX."module_survey_surveyAnswers WHERE
                   question_id='$question_id' ORDER BY id";
                        $objResultcount = $objDatabase->Execute($query);
                        $countss        = $objResultcount->RecordCount();

                        $comment = '';
                        $answers = '';
                        $typ  = $i-1;
                        $type = $inputtyp[$typ];

                        if(!empty($type)) {
                            switch($type) {
                                case "1":
                                    $comment = isset($_REQUEST["comment_$i"]) ? contrexx_input2raw($_REQUEST["comment_$i"]) : '';
                                    $answers = isset($_REQUEST['votingoption_'.$i]) ? contrexx_input2raw($_REQUEST['votingoption_'.$i]) : '';
                                    if(!empty($votes)) {
                                        $query="UPDATE ".DBPREFIX."module_survey_surveyAnswers set votes=votes+1 WHERE id='".$votes."'";
                                        $objDatabase->Execute($query);
                                        $query="UPDATE ".DBPREFIX."module_survey_surveyQuestions set votes=votes+1
                                            WHERE id='".$question_id."'";
                                        $objDatabase->Execute($query);
                                    }else {
                                        $query="UPDATE ".DBPREFIX."module_survey_surveyQuestions set skipped=skipped+1
                                            WHERE id='".$question_id."'";
                                        $objDatabase->Execute($query);
                                    }
                                break;
                                case "2":
                                    $comment = isset($_REQUEST["comment_$i"]) ? contrexx_input2raw($_REQUEST["comment_$i"]) : '';
                                    $answers = isset($_REQUEST["votingoption_$i"]) ? json_encode(contrexx_input2raw($_REQUEST["votingoption_$i"])) : '';
                                    if(!empty($votes)) {
                                        foreach($votes as $id) {
                                            $query="UPDATE ".DBPREFIX."module_survey_surveyAnswers set votes=votes+1 WHERE id='".$id."'";
                                            $objDatabase->Execute($query);
                                        }
                                        $query="UPDATE ".DBPREFIX."module_survey_surveyQuestions set votes=votes+1
                                            WHERE id='".$question_id."'";
                                        $objDatabase->Execute($query);
                                    }else {
                                        $query="UPDATE ".DBPREFIX."module_survey_surveyQuestions set skipped=skipped+1
                                            WHERE id='".$question_id."'";
                                        $objDatabase->Execute($query);
                                    }
                                break;
                                case "3":
                                    $comment = isset($_REQUEST["comment_$i"]) ? contrexx_input2raw($_REQUEST["comment_$i"]) : '';
                                    $answerarr = array();
                                    $choiceVote = array();

                                    $query = "SELECT `column_choice` FROM ".DBPREFIX."module_survey_surveyQuestions WHERE id = ".$question_id;
                                    $objChoices = $objDatabase->Execute($query);
                                    $choices = explode(';',$objChoices->fields['column_choice']);

                                    $query = "SELECT `votes` FROM ".DBPREFIX."module_survey_surveyAnswers WHERE `question_id` = ".$question_id." ORDER BY id";
                                    $objChoiceAns = $objDatabase->Execute($query);

                                    $choice_count = 0;
                                    while (!$objChoiceAns->EOF) {
                                        if ($objChoiceAns->fields['votes'] != '') {
                                            $choiceVote[] = json_decode($objChoiceAns->fields['votes']);
                                        } else {
                                            $choiceVote[$choice_count] = array();
                                            foreach ($choices as $key => $choice) {
                                                $choiceVote[$choice_count][$key] = 0;
                                            }
                                        }
                                        $choice_count++;
                                        $objChoiceAns->MoveNext();
                                    }

                                    for($j=1;$j<=$countss;$j++) {
                                        $votess = isset($_REQUEST["votingoption_".$i."_".$j]) ? contrexx_input2raw($_REQUEST["votingoption_".$i."_".$j]) : '';
                                        $votearr .= $votess;
                                        $answerarr[] = $votess;
                                        $temp = explode('_', $votess);
                                        if (isset($temp[1])) {
                                            $choiceVote[$j-1][$temp[1]]++;
                                        }

                                        $query="UPDATE ".DBPREFIX."module_survey_surveyAnswers set votes='".json_encode($choiceVote[$j-1])."' WHERE id='".$temp[0]."'";
                                        $objDatabase->Execute($query);
                                    }

                                    $answers = json_encode($answerarr);
                                    if(!empty($votearr)) {
                                        $query="UPDATE ".DBPREFIX."module_survey_surveyQuestions set votes=votes+1
                                            WHERE id='".$question_id."'";
                                        $objDatabase->Execute($query);
                                    }
                                    else {
                                        $query="UPDATE ".DBPREFIX."module_survey_surveyQuestions set skipped=skipped+1
                                            WHERE id='".$question_id."'";
                                        $objDatabase->Execute($query);
                                    }
                                    $votearr = "";
                                break;
                                case "4":
                                    $comment = isset($_REQUEST["comment_$i"]) ? contrexx_input2raw($_REQUEST["comment_$i"]) : '';
                                    $answerarr = array();
                                    $choiceVote = array();

                                    $query = "SELECT `column_choice` FROM ".DBPREFIX."module_survey_surveyQuestions WHERE id = ".$question_id;
                                    $objChoices = $objDatabase->Execute($query);
                                    $choices = explode(';',$objChoices->fields['column_choice']);
                                    $query = "SELECT `votes` FROM ".DBPREFIX."module_survey_surveyAnswers WHERE `question_id` = ".$question_id." ORDER BY id";
                                    $objChoiceAns = $objDatabase->Execute($query);

                                    $choice_count = 0;
                                    while (!$objChoiceAns->EOF) {
                                        if ($objChoiceAns->fields['votes'] != '' && strlen($objChoiceAns->fields['votes']) > 5) {
                                            $choiceVote[] = json_decode($objChoiceAns->fields['votes']);
                                        } else {
                                            $choiceVote[$choice_count] = array();
                                            foreach ($choices as $key => $choice) {
                                                $choiceVote[$choice_count][$key] = 0;
                                            }
                                        }
                                        $choice_count++;
                                        $objChoiceAns->MoveNext();
                                    }

                                    for($j=1;$j<=$countss;$j++) {
                                        $votess = isset($_REQUEST["votingoption_".$i."_".$j]) ? contrexx_input2raw($_REQUEST["votingoption_".$i."_".$j]) : array();
                                        $answerarr[] = $votess;
                                        foreach($votess as $id) {
                                            $votearr .= $id;
                                            $temp = explode('_', $id);
                                            if (isset($temp[1])) {
                                                $choiceVote[$j-1][$temp[1]]++;
                                            }
                                        }

                                        $votessid = !empty($votess) ? explode('_', $votess[0]) : 0;
                                        $query="UPDATE ".DBPREFIX."module_survey_surveyAnswers set votes='".json_encode($choiceVote[$j-1])."' WHERE id='".$votessid[0]."'";
                                        $objDatabase->Execute($query);
                                    }

                                    $answers = json_encode($answerarr);
                                    if(!empty($votearr)) {
                                        $query="UPDATE ".DBPREFIX."module_survey_surveyQuestions set votes=votes+1
                                            WHERE id='".$question_id."'";
                                        $objDatabase->Execute($query);
                                    }
                                    else {
                                        $query="UPDATE ".DBPREFIX."module_survey_surveyQuestions set skipped=skipped+1
                                            WHERE id='".$question_id."'";
                                        $objDatabase->Execute($query);
                                    }
                                    $votearr = "";
                                break;
                                case "5":
                                    $comment = isset($_REQUEST["comment_$i"]) ? contrexx_input2raw($_REQUEST["comment_$i"]) : '';
                                    $answers = isset($_REQUEST["votingoption_$i"]) ? contrexx_input2raw($_REQUEST["votingoption_$i"]) : '';
                                    if(!empty($votes)) {
                                        $vot = contrexx_input2raw($_REQUEST["votingoptions_$i"]);
                                        $query="UPDATE ".DBPREFIX."module_survey_surveyAnswers set votes=votes+1 WHERE id='".$vot."'";
                                        $objDatabase->Execute($query);
                                        $query="UPDATE ".DBPREFIX."module_survey_surveyQuestions set votes=votes+1
                                            WHERE id='".$question_id."'";
                                        $objDatabase->Execute($query);
                                    } else {
                                        $query="UPDATE ".DBPREFIX."module_survey_surveyQuestions set skipped=skipped+1
                                            WHERE id='".$question_id."'";
                                        $objDatabase->Execute($query);
                                    }
                                break;
                                case "6":
                                    $comment = contrexx_input2raw($_REQUEST["comment_$i"]);
                                    $answerarr = array();
                                    $ansids = contrexx_input2raw($_REQUEST["votingoption_$i"]);
                                    $s=0;
                                    foreach($ansids as $id) {
                                        $values = contrexx_input2raw($_REQUEST["votingoption_".$i."_".$id]);
                                        $answerarr[] = $values;
                                        if(!empty($values)) {
                                            $s = 1;
                                            $query="UPDATE ".DBPREFIX."module_survey_surveyAnswers set votes=votes+1 WHERE id='".$id."'";
                                            $objDatabase->Execute($query);
                                        }
                                    }
                                    $answers = json_encode($answerarr);
                                    if($s == 1) {
                                        $query="UPDATE ".DBPREFIX."module_survey_surveyQuestions set votes=votes+1
                                            WHERE id='".$question_id."'";
                                        $objDatabase->Execute($query);
                                    } else {
                                        $query="UPDATE ".DBPREFIX."module_survey_surveyQuestions set skipped=skipped+1
                                            WHERE id='".$question_id."'";
                                        $objDatabase->Execute($query);
                                    }
                                    break;
                            }
                        }

                        //Insert answers and corresponding questions with userid
                        $query = "INSERT INTO `".DBPREFIX."module_survey_poll_result`
                                    SET `survey_id` = ".$surveyId.",
                                        `question_id` = ".contrexx_raw2db($question_id).",
                                        `user_id`     = ".contrexx_raw2db($user_id).",
                                        `comment`     = '".contrexx_raw2db($comment)."',
                                        `answers`     = '".contrexx_raw2db($answers)."'";
                        $objDatabase->Execute($query);
                    }

                    // Insrting for user restriction tables          $Restriction;           $surveyId;
                    $objFWUser = \FWUser::getFWUserObject();
                    $useremail = $objFWUser->objUser->getEmail();
                    $ipId=$_SERVER['REMOTE_ADDR'];
                    if($Restriction == "email") {
                        $insertSurvey = 'INSERT INTO `'.DBPREFIX.'module_survey_email`
                            SET `id` = "",
                                `survey_id` = "'.$surveyId.'",
                                `email` = "'.contrexx_raw2db($emailAdd).'",
                                `voted` = "1" ';
                        $objDatabase->Execute($insertSurvey);
                    }else {
                        $cookieVal = isset($_COOKIE["votingcookie_$surveyId"]) ? contrexx_input2raw($_COOKIE["votingcookie_$surveyId"]) : '';
                        $cookieVal = $cookieVal."-".$ipId;
                        setcookie ("votingcookie_$surveyId", $cookieVal, time()+3600*24); // 1 Day
                    }

                    \Cx\Core\Csrf\Controller\Csrf::header("Location: ".CONTREXX_SCRIPT_PATH."?section=Survey&cmd=surveyattend&id=".$idOfSurvey);
                    $this->_objTpl->setVariable(array(
                        'SURVEY_SUCCESS' => "<div class='text-success'>$_ARRAYLANG[TXT_SURVEY_COMPLETED]</div>"
                    ));
                }// End Of else the checking for the attend any one question
            }
        } elseif ($surveyId == "") { // end of user restriction checking condition
            $this->_objTpl->setVariable(array(
                'TXT_SUBMIT' => "<div class='text-danger'>$_ARRAYLANG[TXT_NO_SURVEY_HOME]</div>"
            ));
        } else {
            $this->_objTpl->setVariable(array(
                'SURVEY_SUCCESS' => "<div class='text-danger'>$_ARRAYLANG[TXT_SURVEY_ALREADY_ATTEND]</div>"
            ));
        }
    }

    /**
     * Show Preview of all the survey questions page
     *
     * @access Authendicated
     */
    function surveypreview() {
        global $_ARRAYLANG,$objDatabase;
        $id = isset($_REQUEST['id']) ? contrexx_input2raw($_REQUEST['id']) : 0;

        //Query to get the Question and answer details.
        $QuestionDatas = $objDatabase->Execute('SELECT groups.title As title,
                                                        groups.*,
                                                        Questions.id AS questionId,
                                                        Questions.isCommentable,
                                                        groups.isHomeBox,
                                                        Questions.QuestionType,
                                                        Questions.Question,
                                                        Questions.pos,
                                                        Questions.column_choice
                                                FROM '.DBPREFIX.'module_survey_surveygroup AS groups
                                                LEFT JOIN '.DBPREFIX.'module_survey_surveyQuestions AS Questions
                                                ON groups.id = Questions.survey_id
                                                WHERE groups.isActive != "0"
                                                AND groups.id='.$id.'
                                                ORDER BY Questions.pos,Questions.id DESC');
        $cou = 1;

        $textAfterButton = $QuestionDatas->fields['textAfterButton'];
        $text1           = $QuestionDatas->fields['text1'];
        $text2           = $QuestionDatas->fields['text2'];
        $additional_fields = array();

        $this->_objTpl->setVariable(array(
            'DB_TEXT_AFTER_BUTTON'  => contrexx_remove_script_tags($textAfterButton),
            'TEXT1'                 => contrexx_remove_script_tags($text1),
            'TEXT2'                 => contrexx_remove_script_tags($text2)
        ));

        $this->_objTpl->setVariable(array(
            'TXT_SUBMIT'=>'<input type="submit" name="submit_survey" style="margin-left:155px;" value="'.$_ARRAYLANG['TXT_SUBMIT'].'"',
            'TXT_CANCEL' => '<input type="reset" name="submit_reset" value="'.$_ARRAYLANG['TXT_CANCEL'].'" style="margin-left:100px;">'
        ));

        $text_row = '';
        while(!$QuestionDatas->EOF) {
            $answerId           = $QuestionDatas->fields['questionId'];
            $InputType          = $QuestionDatas->fields['QuestionType'];
            $TextRowTitle       = $QuestionDatas->fields['Question'];
            $IsCommentable      = $QuestionDatas->fields['isCommentable'];
            $Column_choice      = $QuestionDatas->fields['column_choice'];
            $additional_fields  = $this->_create_additional_input_fields($QuestionDatas);

            if($IsCommentable == "1") {
                $commentBox = "<div style='clear:both;padding-top: 10px;'><label style='vertical-align:top;width:150px;float:left;'>$_ARRAYLANG[TXT_COMMENT]</label><textarea name='comment_".$cou."' rows='6' cols='50' style='width:520px;'></textarea></div>";
            }else {
                $commentBox = '';
            }

            $query = "SELECT id, answer FROM ".DBPREFIX."module_survey_surveyAnswers WHERE
                   question_id='$answerId' ORDER BY id";
            $objResult = $objDatabase->Execute($query);

            $SurveyOptionText = "";
            if(!empty($Column_choice)) {
                $countss = $objResult->RecordCount();
                $col = explode (";", $Column_choice);
                $SurveyOptionText .= "<table width='100%'><tr><td>&nbsp;</td>";
                for($i=0;$i<count($col); $i++) {
                    if(trim($col[$i]) != "")
                        $SurveyOptionText .="<td style='padding-right: 10px; text-align: center;'>".contrexx_raw2xhtml($col[$i])."</td>";
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
                                $SurveyOptionText .="<div class='radio'><label><input type='radio' name='votingoption_$cou' value='".contrexx_raw2xhtml($objResult->fields['id'])."' />".contrexx_raw2xhtml($objResult->fields['answer'])."</label></div>";
                                break;
                            case "2":
                                $SurveyOptionText .="<div class='checkbox'><label><input type='checkbox' name='votingoption_".$cou."[]' value='".contrexx_raw2xhtml($objResult->fields['id'])."' />".contrexx_raw2xhtml($objResult->fields['answer'])."</label></div>";
                                break;
                            case "3":
                                $SurveyOptionText .="<tr><td width='150px;'>".contrexx_raw2xhtml($objResult->fields['answer'])."</td>";
                                for($i=0;$i<count($col); $i++) {
                                    if(trim($col[$i]) != "")
                                        $SurveyOptionText .="<td align='center'><input style='float:none;' type='radio' name='votingoption_".$cou."_".$j."' value='".contrexx_raw2xhtml($objResult->fields['id'])."_".$i."' /></td>";
                                }
                                $SurveyOptionText .="</tr>";
                                $j=$j+1;
                                break;
                            case "4":
                                $SurveyOptionText .="<tr><td width='150px;'>".contrexx_raw2xhtml($objResult->fields['answer'])."</td>";
                                for($i=0;$i<count($col); $i++) {
                                    if(trim($col[$i]) != "")
                                        $SurveyOptionText .="<td align='center'><input style='float:none;' type='checkbox' name='votingoption_".$cou."_".$j."[]' value='".contrexx_raw2xhtml($objResult->fields['id'])."_".$i."' /></td>";
                                }
                                $SurveyOptionText .="</tr>";
                                $j=$j+1;
                                break;
                            case "5":
                                $SurveyOptionText .="<tr> <td> </td> <td><input style='float:left;width:250px;margin-left:150px;' type='text' name='votingoption_$cou' value='' />
                                <input type='hidden' name='votingoptions_".$cou."' value='".contrexx_raw2xhtml($objResult->fields['id'])."' /> </td></tr>";
                                break;
                            case "6":
                                $SurveyOptionText .="<tr>
                                 <td width='144' style='display:block;'>".contrexx_raw2xhtml($objResult->fields['answer'])."</td>
                                 <td><input style='float:left;width:250px;' type='text' name='votingoption_$cou' value='' />
                                 </td></tr>";
                                break;
                            case "7":
                                $text_row = contrexx_raw2xhtml($TextRowTitle);
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
            $quest = contrexx_raw2xhtml($QuestionDatas->fields['Question']);
            $question_wrap = wordwrap($quest,130,"<br />\n");
            if($InputType != 7) {
                $SurveyOptionTexts = $SurveyOptionText;
                $question_wrap = wordwrap($quest,130,"<br />\n");
                if ($InputType == "5") {
                    $question_wrap = wordwrap($quest,130,"<br />\n")."<br>";
                }

            }else {
                $SurveyOptionTexts = $text_row;
                $question_wrap = "";
            }
            $this->_objTpl->setVariable(array(
                    'GRAND_TITLE'        => contrexx_raw2xhtml($QuestionDatas->fields['title']),
                    'SURVEY_TITLE'          => $question_wrap,
                    'SURVEY_OPTIONS_TEXT'   => $SurveyOptionTexts,
                    'SURVEY_COMMENT_BOX'    => $commentBox,
                    'SURVEY_TEXT_ROW'       => $text_row,
                    'TXT_HIDDENFIELD'       => '<input type="hidden" name="Survey_id_'.$cou.'" value="'.contrexx_raw2xhtml($QuestionDatas->fields['questionId']).'"/>'
            ));
            $this->_objTpl->parse('Total_surveys');
            $cou++;
            $QuestionDatas->MoveNext();
        }
        // Place Holders for addtional fields in the database
        if (sizeof($additional_fields)) {
            $this->_objTpl->parse('additional_fields');
            foreach ($additional_fields as $field) {
                list($name, $label, $tag) = $field;
                $this->_objTpl->setVariable(array(
                    'VOTING_ADDITIONAL_INPUT_LABEL' => contrexx_remove_script_tags($label),
                    'VOTING_ADDITIONAL_INPUT'       => contrexx_remove_script_tags($tag),
                    'VOTING_ADDITIONAL_NAME'        => contrexx_remove_script_tags($name)
                ));
                $this->_objTpl->parse('additional_elements');
            }
        }
        else {
            $this->_objTpl->parse('additional_fields');
        }

    }

    /**
     * Get the page title
     *
     * @return string
     */
    public function getPageTitle()
    {
        global $objDatabase;

        $id = 0;
        if (isset($_REQUEST['id'])) {
            $id = contrexx_input2int($_REQUEST['id']);
        }
        $cmd = contrexx_input2raw($_GET['cmd']);

        //Get surveyId by question id
        if ($cmd === 'questionpreview') {
            $surveyQuestions = $objDatabase->Execute('
                SELECT
                        `survey_id`
                    FROM ' . DBPREFIX . 'module_survey_surveyQuestions
                    WHERE
                        `id` = ' . $id
            );
            $id = $surveyQuestions->fields['survey_id'];
            if (!$id) {
                return;
            }
        }

        $filter = 'AND id = ' . $id;
        //Set filter to the home page
        if (in_array($cmd, array('', 'homesurvey'))) {
            $filter = 'AND isHomeBox="1"';
        }

        //Fetch survey group details
        $surveyGroup = $objDatabase->Execute('
            SELECT
                    `id`,
                    `title`,
                    `UserRestriction`
                FROM ' . DBPREFIX . 'module_survey_surveygroup
                WHERE
                    `isActive` != "0" ' . $filter
        );

        //Check user restrictions
        if (in_array($cmd, array('', 'homesurvey', 'surveybyId'))) {
            $email = '';
            if (isset($_POST['additional_email'])) {
                $email = contrexx_input2raw($_POST['additional_email']);
            }

            $isRestricted = $this->checkUserRestriction(
                $surveyGroup->fields['UserRestriction'],
                $surveyGroup->fields['id'],
                $email
            );

            if ($isRestricted === 'yes') {
                return;
            }
        }

        return $surveyGroup->fields['title'];
    }

    /**
     * Show Preview of single question in question overview page
     *
     * @access Authendicated
     */
    function QuestionPreview() {
        global $_ARRAYLANG,$objDatabase;
        $id = isset($_REQUEST['id']) ? contrexx_input2raw($_REQUEST['id']) : '';
        //Query to get the Question and answer details.
        $QuestionDatas = $objDatabase->Execute('SELECT groups.title As title,
                                                        groups.*,
                                                        Questions.id AS questionId,
                                                        Questions.isCommentable,
                                                        groups.isHomeBox,
                                                        Questions.QuestionType,
                                                        Questions.Question,
                                                        Questions.pos,
                                                        Questions.column_choice
                                                FROM '.DBPREFIX.'module_survey_surveygroup AS groups
                                                LEFT JOIN '.DBPREFIX.'module_survey_surveyQuestions AS Questions
                                                ON groups.id = Questions.survey_id
                                                WHERE groups.isActive != "0"
                                                AND Questions.id='.$id);
        $cou = 1;
        if (!$QuestionDatas) {
            return;
        }

        while(!$QuestionDatas->EOF) {
            // This is the additional field information
            $additionalInfo = $QuestionDatas->fields['additional_salutation']."-".
                                    $QuestionDatas->fields['additional_nickname']."-".
                                    $QuestionDatas->fields['additional_forename']."-".
                                    $QuestionDatas->fields['additional_surname']."-".
                                    $QuestionDatas->fields['additional_agegroup']."-".
                                    $QuestionDatas->fields['additional_phone']."-".
                                    $QuestionDatas->fields['additional_street']."-".
                                    $QuestionDatas->fields['additional_zip']."-".
                                    $QuestionDatas->fields['additional_city']."-".
                                    $QuestionDatas->fields['additional_email'];

            $answerId          = $QuestionDatas->fields['questionId'];
            $InputType         = $QuestionDatas->fields['QuestionType'];
            $TextRowTitle      = $QuestionDatas->fields['Question'];
            $IsCommentable     = $QuestionDatas->fields['isCommentable'];
            $Column_choice     = $QuestionDatas->fields['column_choice'];
            $SurvId            = $QuestionDatas->fields['id'];
            $additional_fields = $this->_create_additional_input_fields($QuestionDatas);
            $inputtyp[]        = $InputType;

            if($IsCommentable == "1") {
                $commentBox = "<div style='clear:both;padding-top: 10px;'><label style='vertical-align:top;width:150px;float:left;'>$_ARRAYLANG[TXT_COMMENT]</label><textarea name='comment_".$cou."' rows='6' cols='50' style='width:520px;'></textarea></div>";
            }else {
                $commentBox = '';
            }

            $query = "SELECT id, answer FROM ".DBPREFIX."module_survey_surveyAnswers WHERE
                  question_id='$answerId' ORDER BY id";
            $objResult = $objDatabase->Execute($query);


            $SurveyOptionText = "";
            if(!empty($Column_choice)) {
                //$countss = $objResult->RecordCount();
                $col = explode (";", $Column_choice);
                $SurveyOptionText .= "<table><tr><td>&nbsp;</td>";
                for($i=0;$i<count($col); $i++) {
                    if(trim($col[$i]) != "")
                        $SurveyOptionText .="<td>".contrexx_raw2xhtml($col[$i])."</td>";
                }
                $SurveyOptionText .="</tr>";
            }
            if($InputType == 6) {
                $SurveyOptionText .= "<table>";
            }
            $j        = 1;
            $text_row = '';
            while (!$objResult->EOF) {
                if(trim($objResult->fields['answer']) != "") {
                    if(!empty($InputType)) {
                        switch($InputType) {
                        case "1":
                            $SurveyOptionText .="<div class='radio'><label><input type='radio' name='votingoption_$cou' value='".contrexx_raw2xhtml($objResult->fields['id'])."' />".contrexx_raw2xhtml($objResult->fields['answer'])."</label></div>";
                            break;
                        case "2":
                            $SurveyOptionText .="<div class='checkbox'><label><input type='checkbox' name='votingoption_".$cou."[]' value='".contrexx_raw2xhtml($objResult->fields['id'])."' />".contrexx_raw2xhtml($objResult->fields['answer'])."</label></div>";
                            break;
                        case "3":
                            $SurveyOptionText .="<tr><td width='150px;'>".contrexx_raw2xhtml($objResult->fields['answer'])."</td>";
                            for($i=0;$i<count($col); $i++) {
                                if(trim($col[$i]) != "") {
                                    $SurveyOptionText .="<td align='center'><input style='float:none;' type='radio' name='votingoption_".$cou."_".$j."' value='".contrexx_raw2xhtml($objResult->fields['id'])."_".$i."' /></td>";
                                }
                            }
                            $SurveyOptionText .="</tr>";
                            $j=$j+1;
                            break;
                        case "4":
                            $SurveyOptionText .="<tr><td width='150px;'>".contrexx_raw2xhtml($objResult->fields['answer'])."</td>";
                            for($i=0;$i<count($col); $i++) {
                                if(trim($col[$i]) != "") {
                                    $SurveyOptionText .="<td align='center'><input style='float:none;' type='checkbox' name='votingoption_".$cou."_".$j."[]' value='".contrexx_raw2xhtml($objResult->fields['id'])."_".$i."' /></td>";
                                }
                            }
                            $SurveyOptionText .="</tr>";
                            $j=$j+1;
                            break;
                        case "5":
                            $SurveyOptionText .="<tr> <td width='150px'>&nbsp;</td> <td><input style='float:left;width:250px;margin-left:150px;' type='text' name='votingoption_$cou' value='' />
                            <input type='hidden' name='votingoptions_".$cou."' value='".contrexx_raw2xhtml($objResult->fields['id'])."' /> </td></tr>";
                            break;
                        case "6":
                            $SurveyOptionText .="<tr>
                             <td width='144' style='display:block;'>".contrexx_raw2xhtml($objResult->fields['answer'])."</td>
                             <td><input style='float:left;width:250px;' type='text' name='votingoption_$cou"."_".contrexx_raw2xhtml($objResult->fields['id'])."' value='' />
                             <input type='hidden' name='votingoption_".$cou."[]' value='".contrexx_raw2xhtml($objResult->fields['id'])."' />
                             </td></tr>";
                            break;
                        case "7":
                            $text_row = contrexx_raw2xhtml($TextRowTitle);
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

            $quest          = contrexx_raw2xhtml($QuestionDatas->fields['Question']);
            $question_wrap  = wordwrap($quest,130,"<br />\n");
            if($InputType != 7) {
                $SurveyOptionTexts = $SurveyOptionText;
                $question_wrap = wordwrap($quest,130,"<br />\n");
                if ($InputType == "5") {
                    $question_wrap = wordwrap($quest,130,"<br />\n")."<br>";
                }
            }else {
                $SurveyOptionTexts  = $text_row;
                $question_wrap      = "";
            }
            $this->_objTpl->setVariable(array(
                'GRAND_TITLE'        => contrexx_raw2xhtml($QuestionDatas->fields['title']),
                'SURVEY_TITLE'          => $question_wrap,
                'SURVEY_OPTIONS_TEXT'   => $SurveyOptionTexts,
                'SURVEY_COMMENT_BOX'    => $commentBox,
                'SURVEY_ID'             => $id,
                'SURVEY_TEXT_ROW'       => $text_row,
                'TXT_ADDINFO'           => '<input type="hidden" id="addInfo" name="addInfo" value="'.contrexx_raw2xhtml($additionalInfo).'">',
                'TXT_HIDDENFIELD'       => '<input type="hidden" name="Survey_id_'.$cou.'" value="'.contrexx_raw2xhtml($QuestionDatas->fields['questionId']).'"/>'
            ));
            $this->_objTpl->parse('Total_surveys');
            $cou++;
            $QuestionDatas->MoveNext();
        }
    }


    /**
     * Show Thanks Message Attend this survey
     *
     * @access Authendicated
     */
    function SurveyAttend() {
        global $_ARRAYLANG,$objDatabase;


        $thanksMSG = "";
        if(!empty($_GET['id'])) {
            $query = "SELECT thanksMSG FROM ".DBPREFIX."module_survey_surveygroup WHERE id='".contrexx_input2raw($_GET['id'])."'";
        }else {
            $query = "SELECT thanksMSG FROM ".DBPREFIX."module_survey_surveygroup WHERE isHomeBox='1'";
        }
        $objResult = $objDatabase->Execute($query);
        if (!empty($objResult->fields['thanksMSG'])) {
            $thanksMSG = $objResult->fields['thanksMSG'];
        }

        //Query to get the Question and answer details.
        // Static Place holders for labels
        $this->_objTpl->setVariable(array(
            'THANKS_MSG'=> contrexx_remove_script_tags($thanksMSG),
            'TXT_SURVEY_OK_TXT'=>'<a href="index.php?section=Survey&cmd=activesurveys"><input type="submit" name="submit_survey" value="'.$_ARRAYLANG['TXT_SURVEY_OK_TXT'].'" > </a>'
        ));
    }

    // Javascript function for survey voting page
    function getVotingSurveyJavascript() {
        global $_ARRAYLANG, $objDatabase;

        $TXT_SELECT_SALUTATION_ERR = $_ARRAYLANG['TXT_SELECT_SALUTATION_ERR'];
        $TXT_ENTER_NICKNAME_ERR = $_ARRAYLANG['TXT_ENTER_NICKNAME_ERR'];
        $TXT_ENTER_FORNAME_ERR = $_ARRAYLANG['TXT_ENTER_FORNAME_ERR'];
        $TXT_ENTER_FORNAME_ALFA_ERR = $_ARRAYLANG['TXT_ENTER_FORNAME_ALFA_ERR'];
        $TXT_ENTER_SURNAME_ERR = $_ARRAYLANG['TXT_ENTER_SURNAME_ERR'];
        $TXT_SELECT_AGEGROUP_ERR = $_ARRAYLANG['TXT_SELECT_AGEGROUP_ERR'];
        $TXT_ENTER_TELEPHONE_ERR = $_ARRAYLANG['TXT_ENTER_TELEPHONE_ERR'];
        $TXT_ENTER_VALID_TELEPHONE_ERR = $_ARRAYLANG['TXT_ENTER_VALID_TELEPHONE_ERR'];
        $TXT_ENTER_STREET_ERR = $_ARRAYLANG['TXT_ENTER_STREET_ERR'];
        $TXT_ENTER_ZIP_ERR = $_ARRAYLANG['TXT_ENTER_ZIP_ERR'];
        $TXT_ENTER_VALID_ZIP_ERR = $_ARRAYLANG['TXT_ENTER_VALID_ZIP_ERR'];
        $TXT_ENTER_CITY_ERR = $_ARRAYLANG['TXT_ENTER_CITY_ERR'];
        $TXT_ENTER_EMAIL_ERR = $_ARRAYLANG['TXT_ENTER_EMAIL_ERR'];
        $TXT_ENTER_VALID_EMAIL_ERR = $_ARRAYLANG['TXT_ENTER_VALID_EMAIL_ERR'];

        $javascript = '
    <script language="JavaScript" type="text/javascript">

     function trim(sString){
              while (sString.substring(0,1) == " "){
               sString = sString.substring(1, sString.length);
              }
              while (sString.substring(sString.length-1, sString.length) == " "){
               sString = sString.substring(0,sString.length-1);
              }
         return sString;
        }
        function ltrim(s){
           var l=0;
           while(l < s.length && s[l] == " ")
           {    l++; }
           return s.substring(l, s.length);
        }
        function rtrim(s){
           var r=s.length -1;
           while(r > 0 && s[r] == " ")
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

    function alphanumeric(alphane)
    {
        var numaric = alphane;
        for(var j=0; j<numaric.length; j++)
            {
              var alphaa = numaric.charAt(j);
              var hh = alphaa.charCodeAt(0);
              if((hh > 47 && hh<58) || (hh > 64 && hh<91) || (hh > 96 && hh<123))
              {
              }
            else    {
                 return false;
              }
             }
     return true;
    }

    function alpha(val){
         var iChars = "!@#$%^&*()+=-[]\\\;,/{}|\":<>?0123456789";
          for (var i = 0; i < val.length; i++) {
              if (iChars.indexOf(val.charAt(i)) != -1) {
              return false;
              }
          }
          return true;
    }



    function AdditionalValidate(){
    if (document.getElementById("additional_salutation") != undefined){
        var salutation = document.getElementById("additional_salutation").value;
        if(salutation == 0){
            alert("'.$TXT_SELECT_SALUTATION_ERR.'");
            document.getElementById("additional_salutation").focus();
            document.getElementById("additional_salutation").value="";
            return false;
        }
    }

    if (document.getElementById("additional_nickname") != undefined){
        var nickname = document.getElementById("additional_nickname").value;
        if(trim(nickname) == ""){
            alert("'.$TXT_ENTER_NICKNAME_ERR.'");
            document.getElementById("additional_nickname").focus();
            document.getElementById("additional_nickname").value="";
        return false;
        }
    }

    if (document.getElementById("additional_forename") != undefined){
        var forename = document.getElementById("additional_forename").value;
        if(trim(forename) == ""){
            alert("'.$TXT_ENTER_FORNAME_ERR.'");
            document.getElementById("additional_forename").focus();
            document.getElementById("additional_forename").value="";
            return false;
        }
        if(alpha(forename) == false){
            alert("'.$TXT_ENTER_FORNAME_ALFA_ERR.'");
            document.getElementById("additional_forename").focus();
            document.getElementById("additional_forename").value="";
            return false;
        }
    }

    if (document.getElementById("additional_surname") != undefined){
        var surname = document.getElementById("additional_surname").value;
        if(trim(surname) == ""){
            alert("'.$TXT_ENTER_SURNAME_ERR.'");
            document.getElementById("additional_surname").focus();
            document.getElementById("additional_surname").value="";
            return false;
        }
        if(alpha(surname) == false){
            alert("'.$TXT_ENTER_FORNAME_ALFA_ERR.'");
            document.getElementById("additional_surname").focus();
            document.getElementById("additional_surname").value="";
            return false;
        }
    }

    if (document.getElementById("additional_agegroup") != undefined){
        var agegroup = document.getElementById("additional_agegroup").value;
        if(agegroup == 0){
            alert("'.$TXT_SELECT_AGEGROUP_ERR.'");
            document.getElementById("additional_agegroup").focus();
            document.getElementById("additional_agegroup").value="";
            return false;
        }
    }


    //        if(addInfoArr[5] == 1){
    //            var phone = document.getElementById("additional_phone").value;
    //            if(trim(phone) == ""){
    //                alert("'.$TXT_ENTER_TELEPHONE_ERR.'");
    //                document.getElementById("additional_phone").focus();
    //                document.getElementById("additional_phone").value="";
    //                return false;
    //            }
    //            if(IsNumeric(phone) == false){
    //                alert("'.$TXT_ENTER_VALID_TELEPHONE_ERR.'");
    //                document.getElementById("additional_phone").focus();
    //                document.getElementById("additional_phone").value="";
    //                return false;
    //            }
    //            return true;
    //        }

    if (document.getElementById("additional_street") != undefined){
        var street = document.getElementById("additional_street").value;
        if(trim(street) == ""){
            alert("'.$TXT_ENTER_STREET_ERR.'");
            document.getElementById("additional_street").focus();
            document.getElementById("additional_street").value="";
            return false;
        }
    }


    if (document.getElementById("additional_zip") != undefined){
        var zip = document.getElementById("additional_zip").value;
        if(trim(zip) == ""){
            alert("'.$TXT_ENTER_ZIP_ERR.'");
            document.getElementById("additional_zip").focus();
            document.getElementById("additional_zip").value="";
            return false;
        }
        if(IsNumeric(zip) == false){
            alert("'.$TXT_ENTER_VALID_ZIP_ERR.'");
            document.getElementById("additional_zip").focus();
            document.getElementById("additional_zip").value="";
            return false;
        }
    }


    if (document.getElementById("additional_city") != undefined){
        var city = document.getElementById("additional_city").value;
        if(trim(city) == ""){
            alert("'.$TXT_ENTER_CITY_ERR.'");
            document.getElementById("additional_city").focus();
            document.getElementById("additional_city").value="";
            return false;
        }
        if(alpha(city) == ""){
            alert("'.$TXT_ENTER_FORNAME_ALFA_ERR.'");
            document.getElementById("additional_city").focus();
            document.getElementById("additional_city").value="";
            return false;
        }
    }


    if (document.getElementById("additional_email") != undefined){
        var email = document.getElementById("additional_email").value;
        if(trim(email) == ""){
            alert("'.$TXT_ENTER_EMAIL_ERR.'");
            document.getElementById("additional_email").focus();
            document.getElementById("additional_email").value="";
            return false;
        }
        if(/^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/.test(email) == false) {
            alert("'.$TXT_ENTER_VALID_EMAIL_ERR.'");
            document.getElementById("additional_email").focus();
            document.getElementById("additional_email").value="";
            return false;
        }
    }

    return true;
}





    </script>
    ';
        return $javascript;
    }

}
?>
