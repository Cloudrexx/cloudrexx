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
 * Voting Module
 *
 * Functions for the Voting
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Leandro Nery <nery@astalavista.net>
 * @author      Ivan Schmid <ivan.schmid@comvation.com>
 * @version       $Id: index.inc.php,v 1.00 $
 * @package     cloudrexx
 * @subpackage  module_voting
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\Voting\Controller;
/**
 * Show current voting
 */
function votingShowCurrent($page_content){
    global $objDatabase, $_CONFIG, $_ARRAYLANG, $_COOKIE;

    $paging = '';

    $objTpl = new \Cx\Core\Html\Sigma('.');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($objTpl);
    $objTpl->setErrorHandling(PEAR_ERROR_DIE);
    $objTpl->setTemplate($page_content);

    if (!isset($_GET['vid'])) {
        $_GET['vid'] = '';
    }

    if (!isset($_POST['votingemail'])) {
        $_POST['votingemail'] = '';
    }

    $votingId = intval($_GET['vid']);
    $msg = '';
    $voted = false;

    if ($_POST["votingoption"]){
        $voteId = intval($_POST["votingoption"]);

        $query="SELECT voting_system_id from ".DBPREFIX."voting_results WHERE id=".$voteId;
        $objResult = $objDatabase->SelectLimit($query, 1);
        if (!$objResult->EOF){
            $votingId = $objResult->fields["voting_system_id"];
        }

        $objVoting = $objDatabase->SelectLimit("SELECT submit_check FROM `".DBPREFIX."voting_system` WHERE `id`=".$votingId, 1);
        if ($objVoting !== false && $objVoting->RecordCount() == 1) {
            if ($objVoting->fields['submit_check'] == 'email') {
                $email = contrexx_addslashes($_POST['votingemail']);
                $objValidator = new \FWValidator();
                if ($objValidator->isEmail($email)) {
                    if (!_alreadyVotedWithEmail($votingId, $email)) {
                        if (($msg = VotingSubmitEmail($votingId, $voteId, $email)) === true) {
                            $msg = '';
                            $voted = true;
                        } else {
                            $msg = $_ARRAYLANG['TXT_VOTING_NONEXISTENT_EMAIL'].'<br /><br />';
                        }
                    } else {
                        $msg = $_ARRAYLANG['TXT_VOTING_ALREADY_VOTED'].'<br /><br />';
                    }
                } else {
                    $msg = $_ARRAYLANG['TXT_VOTING_INVALID_EMAIL_ERROR'].'<br /><br />';
                }
            } else {
                VotingSubmit();
                $voted = true;
            }
        }
    }

    if ($_GET['vid'] != '' && $_GET['act'] != 'delete'){
        $query= "SELECT
            id,                                 status,
            date as datesec,                    question,
            votes,                              submit_check,
            additional_nickname,                additional_forename,
            additional_surname,                 additional_phone,
            additional_street,                  additional_zip,
            additional_city,                    additional_email,
            additional_comment

            FROM ".DBPREFIX."voting_system where id=".intval($_GET['vid']);
    } else {
        $query= "SELECT
            id,                                 status,
            date as datesec,                    question,
            votes,                              submit_check,
            additional_nickname,                additional_forename,
            additional_surname,                 additional_phone,
            additional_street,                  additional_zip,
               additional_city,                    additional_email,
            additional_comment

            FROM ".DBPREFIX."voting_system where status=1";
    }

    $objResult = $objDatabase->Execute($query);

    if ($objResult->RecordCount() == 0) {
        // Only show old records when no voting is set available
       $objTpl->setVariable(array(
                   'VOTING_TITLE'                    => $_ARRAYLANG['TXT_VOTING_NOT_AVAILABLE'],
                   'VOTING_DATE'                    => '',
                'VOTING_OLDER_TEXT'                => '',
                'VOTING_OLDER_DATE'                => '',
                'VOTING_PAGING'                    => '',
                'TXT_DATE'                        => '',
                'TXT_TITLE'                        => '',
                'VOTING_RESULTS_TEXT'            => '',
                'VOTING_RESULTS_TOTAL_VOTES'    => '',
                'VOTING_OLDER_TITLE'            => $_ARRAYLANG['TXT_VOTING_OLDER'],
                'TXT_SUBMIT'                    => '',
            ));

        /** start paging **/
        $query="SELECT id, date as datesec, title, votes FROM ".DBPREFIX."voting_system order by id desc";
        $objResult = $objDatabase->SelectLimit($query, 5);
        $count = $objResult->RecordCount();
        $pos = intval($_GET[pos]);
        if ($count > intval($_CONFIG['corePagingLimit'])){
            $paging= getPaging($count, $pos, "&section=Voting", "<b>".$_ARRAYLANG['TXT_VOTING_ENTRIES']."</b>", true);
        }
        /** end paging **/

        $query="SELECT id, date as datesec, title, votes FROM ".DBPREFIX."voting_system order by id desc ";
        $objResult = $objDatabase->SelectLimit($query, $_CONFIG['corePagingLimit'], $pos);

        while (!$objResult->EOF) {
            $votingid=$objResult->fields['id'];
            $votingTitle=stripslashes($objResult->fields['title']);
            $votingVotes=$objResult->fields['votes'];
            $votingDate=strtotime($objResult->fields['datesec']);
            $votingDate = \Cx\Core\DateTime\Controller\ComponentController::strftime('%A, %e. %B %Y', $votingDate);

            if (($i % 2) == 0) {$class="row2";} else {$class="row1";}
            $objTpl->setVariable(array(
                'VOTING_OLDER_TEXT'        => '<a href="index.php?section=Voting&vid='.$votingid.'" title="'.$votingTitle.'">'.$votingTitle.'</a>',
                'VOTING_OLDER_DATE'        => $votingDate,
                'VOTING_VOTING_ID'        => $votingid,
                'VOTING_LIST_CLASS'        => $class,
                'VOTING_PAGING'            => $paging
            ));
            $objTpl->parse("votingRow");
            $i++;
            $objResult->MoveNext();
        }
    } else {
        if (!$objResult->EOF) {
            $votingId            = $objResult->fields['id'];
            $votingTitle       = stripslashes($objResult->fields['question']);
            $votingVotes       = $objResult->fields['votes'];
            $votingDate           = strtotime($objResult->fields['datesec']);
            $votingDate           = \Cx\Core\DateTime\Controller\ComponentController::strftime('%A, %e. %B %Y', $votingDate);
            $votingStatus       = $objResult->fields['status'];
            $votingMethod       = $objResult->fields['submit_check'];
            $additional_fields = _create_additional_input_fields($objResult);
            $objResult->MoveNext();
        } else {
            errorHandling();
            return false;
        }
        $images = 1;

        $query = "SELECT id, question, votes FROM ".DBPREFIX."voting_results WHERE voting_system_id='$votingId' ORDER BY id";
        $objResult = $objDatabase->Execute($query);

        while (!$objResult->EOF) {
            if ($votingStatus==1 && (($votingMethod == 'email' && !$voted) || ($votingMethod == 'cookie' && $_COOKIE['votingcookie']!='1'))){
                            $votingOptionText .="<div><input type='radio' id='votingoption_".$objResult->fields['id']."' name='votingoption' value='".$objResult->fields['id']."' ".($_POST["votingoption"] == $objResult->fields['id'] ? 'checked="checked"' : '')." /> ";
                            $votingOptionText .= "<label for='votingoption_".$objResult->fields['id']."'>".stripslashes($objResult->fields['question'])."</label></div>";
            }
            $objResult->MoveNext();
        }

        $votingResultText = _vote_result_html($votingId);

        if ($votingStatus==1 && (($votingMethod == 'email' && !$voted) || ($votingMethod == 'cookie' && $_COOKIE['votingcookie']!='1'))){
            $votingVotes        = '';

            if ($votingMethod == 'email') {
                $objTpl->setVariable('VOTING_EMAIL', !empty($_POST['votingemail']) ? htmlentities($_POST['votingemail'], ENT_QUOTES) : '');
                $objTpl->parse('voting_email_input');
            } else {
                if ($objTpl->blockExists('voting_email_input')) {
                    $objTpl->hideBlock('voting_email_input');
                }
            }

            $submitbutton    = '<input type="submit" value="'.$_ARRAYLANG['TXT_SUBMIT'].'" name="Submit" />';
        } else {
            if ($objTpl->blockExists('voting_email_input')) {
                $objTpl->hideBlock('voting_email_input');
            }
            if ($objTpl->blockExists('additional_fields')) {
                $objTpl->hideBlock('additional_fields');
            }



            $votingVotes    = $_ARRAYLANG['TXT_VOTING_TOTAL'].":    ".$votingVotes;
            $submitbutton    ='';
        }


        if (sizeof($additional_fields)){
            $objTpl->parse('additional_fields');
            foreach ($additional_fields as $field) {
                list($name, $label, $tag) = $field;
                $objTpl->setVariable(array(
                    'VOTING_ADDITIONAL_INPUT_LABEL' => $label,
                    'VOTING_ADDITIONAL_INPUT'       => $tag,
                       'VOTING_ADDITIONAL_NAME'        => $name
                ));
                $objTpl->parse('additional_elements');
            }
        }
        else {
            $objTpl->hideBlock('additional_fields');
        }

        $objTpl->setVariable(array(
            'VOTING_MSG'                    => $msg,
            'VOTING_TITLE'                    => $votingTitle,
            'VOTING_DATE'                    => $votingDate,
            'VOTING_OPTIONS_TEXT'            => $votingOptionText,
            'VOTING_RESULTS_TEXT'            => $votingResultText,
            'VOTING_RESULTS_TOTAL_VOTES'    => $votingVotes,
            'VOTING_OLDER_TITLE'            => $_ARRAYLANG['TXT_VOTING_OLDER'],
            'TXT_DATE'                        => $_ARRAYLANG['TXT_DATE'],
            'TXT_TITLE'                        => $_ARRAYLANG['TXT_TITLE'],
            'TXT_VOTES'                        => $_ARRAYLANG['TXT_VOTES'],
            'TXT_SUBMIT'                    => $submitbutton
        ));

        // show other Poll entries

        /** start paging **/
        $query="SELECT id, date as datesec, title, votes FROM ".DBPREFIX."voting_system WHERE id<>$votingId order by id desc";
        $objResult = $objDatabase->SelectLimit($query, 5);
        $count = $objResult->RecordCount();
        $pos = intval($_GET[pos]);
        if ($count>intval($_CONFIG['corePagingLimit'])){
            $paging= getPaging($count, $pos, "&section=Voting", "<b>".$_ARRAYLANG['TXT_VOTING_ENTRIES']."</b>", true);
        }
        /** end paging **/

        $query="SELECT id, date as datesec, title, votes FROM ".DBPREFIX."voting_system WHERE id<>$votingId order by id desc ";

        $objResult = $objDatabase->SelectLimit($query, $_CONFIG['corePagingLimit'], $pos);

        $objTpl->setVariable(array(
            'VOTING_OLDER_TEXT'        => '',
            'VOTING_OLDER_DATE'        => '',
            'VOTING_VOTING_ID'        => '',
            'VOTING_PAGING'            => '',
            'TXT_DATE'                => '',
            'TXT_TITLE'                => ''
        ));

        while (!$objResult->EOF) {
            $votingid=$objResult->fields['id'];
            $votingTitle=stripslashes($objResult->fields['title']);
            $votingVotes=$objResult->fields['votes'];
            $votingDate=strtotime($objResult->fields['datesec']);
            $votingDate = \Cx\Core\DateTime\Controller\ComponentController::strftime('%A, %e. %B %Y', $votingDate);

            if (($i % 2) == 0) {$class="row2";} else {$class="row1";}
            $objTpl->setVariable(array(
                'VOTING_OLDER_TEXT'        => '<a href="index.php?section=Voting&vid='.$votingid.'" title="'.$votingTitle.'">'.$votingTitle.'</a>',
                'VOTING_OLDER_DATE'        => $votingDate,
                'VOTING_VOTING_ID'        => $votingid,
                'VOTING_LIST_CLASS'        => $class,
                'VOTING_PAGING'            => $paging
            ));
            $objTpl->parse("votingRow");
            $i++;
            $objResult->MoveNext();
        }
    }
    return $objTpl->get();

}

function VotingSubmit(){
    global $objDatabase, $_COOKIE;

    if ($_COOKIE['votingcookie'] != '1') {
        setcookie("votingcookie", '1', time()+3600*24, ASCMS_PATH_OFFSET.'/'); // 1 Day
        $votingOption = intval($_POST["votingoption"]);

        $query="SELECT voting_system_id from ".DBPREFIX."voting_results WHERE id=".$votingOption." ";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult->EOF){
            $voting_id = $objResult->fields["voting_system_id"];
            $query="UPDATE ".DBPREFIX."voting_system set votes=votes+1,date=date WHERE id=".$voting_id ." ";
            $objDatabase->Execute($query);
            $query="UPDATE ".DBPREFIX."voting_results set votes=votes+1 WHERE id=".$votingOption." ";
            $objDatabase->Execute($query);
            _store_additional_data($voting_id);
    }
        \Cx\Core\Csrf\Controller\Csrf::header("Location: ?section=Voting");
    }
}

function _store_additional_data($id){
    global $objDatabase;

    $email = isset($_POST['additional_email']) ? $_POST['additional_email'] : '';

    // Fallback to voting confirmation email. this way the
    // user doesn't have to enter it twice for the stats.
    if ($email == '') {
        $email = $_POST['votingemail'];
    }

    $sql = 'INSERT INTO ' . DBPREFIX . 'voting_additionaldata SET ' .
        "voting_system_id = '". intval($id)                               . "', ".
        "nickname         = '". addslashes($_POST['additional_nickname']) . "', ".
        "forename         = '". addslashes($_POST['additional_forename']) . "', ".
        "surname          = '". addslashes($_POST['additional_surname' ]) . "', ".
        "phone            = '". addslashes($_POST['additional_phone'   ]) . "', ".
        "street           = '". addslashes($_POST['additional_street'  ]) . "', ".
        "zip              = '". addslashes($_POST['additional_zip'     ]) . "', ".
        "city             = '". addslashes($_POST['additional_city'    ]) . "', ".
        "comment          = '". addslashes($_POST['additional_comment' ]) . "', ".
        "email            = '". addslashes($email                       ) . "'  ";
    $objDatabase->Execute($sql);
}

function VotingSubmitEmail($systemId, $voteId, $email, $emailValidated)
{
    global $objDatabase;

    $query="UPDATE ".DBPREFIX."voting_system set votes=votes+1,date=date WHERE id=".$systemId." ";
    $objDatabase->Execute($query);
    $query="UPDATE ".DBPREFIX."voting_results set votes=votes+1 WHERE id=".$voteId." ";
    $objDatabase->Execute($query);
    _store_additional_data($systemId);

    $objEmail = $objDatabase->SelectLimit("SELECT `id` FROM `".DBPREFIX."voting_email` WHERE `email` = '".$email."'");
    if ($objEmail !== false) {
        if ($objEmail->RecordCount() == 0) {
            if (($arrResponse = _verifyEmail($email)) !== false) {
                if ($arrResponse[0] == 250) {
                    $emailValidated = 1;
                } else {
                    $emailValidated = 0;
                }
            } else {
                //return $_ARRAYLANG['TXT_VOTING_NONEXISTENT_EMAIL'].'<br /><br />';
                $emailValidated = 0;
            }

            if ($objDatabase->Execute("INSERT INTO `".DBPREFIX."voting_email` SET `email` = '".$email."', `valid` = '".$emailValidated."'") !== false) {
                $emailId = $objDatabase->Insert_ID();
            }
        } else {
            $emailId = $objEmail->fields['id'];
        }

        $objDatabase->Execute("INSERT INTO `".DBPREFIX."voting_rel_email_system` (`email_id`, `system_id`, `voting_id`) VALUES (".$emailId.", ".$systemId.", ".$voteId.")");
    }

    return true;
}

function setVotingResult($template)
{
    global $objDatabase, $_CONFIG, $_ARRAYLANG;
    $paging="";

    $objTpl = new \Cx\Core\Html\Sigma('.');
    \Cx\Core\Csrf\Controller\Csrf::add_placeholder($objTpl);
    $objTpl->setErrorHandling(PEAR_ERROR_DIE);
    $objTpl->setTemplate($template);

        $query= "SELECT id, status, date as datesec, question, votes FROM ".DBPREFIX."voting_system where status=1";
    $objResult = $objDatabase->SelectLimit($query, 1);


    if (!$objResult->EOF) {
        $votingId=$objResult->fields['id'];
        $votingTitle=stripslashes($objResult->fields['question']);
        $votingVotes=$objResult->fields['votes'];

        $objResult->MoveNext();
    } else {
        return '';
    }
    $votingResultText = _vote_result_html($votingId);


    $votingVotes= $_ARRAYLANG['TXT_VOTING_TOTAL'].":    ".$votingVotes;

    $objTpl->setVariable(array(
        'VOTING_RESULTS_TOTAL_VOTES' => $votingVotes,
        'VOTING_TITLE'               => $votingTitle,
        'VOTING_RESULTS_TEXT'         => $votingResultText
    ));
    $objTpl->parse();
    //$objTpl->parse('voting_result');
    return $objTpl->get();
}

function _alreadyVotedWithEmail($voteingId, $email)
{
    global $objDatabase;

    $objEmail = $objDatabase->SelectLimit("SELECT 1 FROM `".DBPREFIX."voting_email` AS e INNER JOIN `".DBPREFIX."voting_rel_email_system` AS s ON s.email_id=e.id WHERE `email` = '".$email."' AND system_id=".$voteingId, 1);
    if ($objEmail !== false) {
        if ($objEmail->RecordCount() == 0) {
            return false;
        }
    }
    return true;
}

function _verifyEmail($email)
{
    if ($arrMxRRs = _getMXHosts($email)) {
        require_once ASCMS_LIBRARY_PATH.'/PEAR/Net/SMTP.php';

        foreach ($arrMxRRs as $arrMxRR) {
            if (!\PEAR::isError($objSMTP = new \Net_SMTP($arrMxRR['EXCHANGE'])) && !\PEAR::isError($objSMTP->connect(2)) && !\PEAR::isError($e = $objSMTP->vrfy($email))) {
                return $objSMTP->getResponse();
            }
        }

        return 0;
    }

    return false;
}

function _getMXHosts($email)
{

    $objMXLookup = new \MXLookup();

    $host = substr($email, strrpos($email, '@') + 1);

    if ($objMXLookup->getMailServers($host)) {
        return $objMXLookup->arrMXRRs;
    } else {
        return false;
    }
}

function _create_additional_input_fields($settings) {
    global $_ARRAYLANG;

    $input_template = '<input name="%name" id="%name" type="%type" />';
    $input_template_textarea = '<textarea name="%name" id="%name" > </textarea>';

    $additionals = array(
        'additional_nickname' => array('text',     $_ARRAYLANG['TXT_ADDITIONAL_NICKNAME']),
        'additional_forename' => array('text',     $_ARRAYLANG['TXT_ADDITIONAL_FORENAME']),
        'additional_surname'  => array('text',     $_ARRAYLANG['TXT_ADDITIONAL_SURNAME' ]),
        'additional_phone'    => array('text',     $_ARRAYLANG['TXT_ADDITIONAL_PHONE'   ]),
        'additional_street'   => array('text',     $_ARRAYLANG['TXT_ADDITIONAL_STREET'  ]),
        'additional_zip'      => array('text',     $_ARRAYLANG['TXT_ADDITIONAL_ZIP'     ]),
        'additional_city'     => array('text',     $_ARRAYLANG['TXT_ADDITIONAL_CITY'    ]),
        'additional_email'    => array('text',     $_ARRAYLANG['TXT_ADDITIONAL_EMAIL'   ]),
        'additional_comment'  => array('textarea', $_ARRAYLANG['TXT_ADDITIONAL_COMMENT' ]),
    );
    $retval = array();
    foreach ($additionals as $name => $data) {
        if (!$settings->fields[$name]) continue;

        list($type, $label) = $data;

        $input_tag =
            str_replace('%name',  $name,
            str_replace('%label', $label,
            str_replace('%type',  $type,
            ($type == 'textarea' ? $input_template_textarea : $input_template)
        )));
        $retval[] = array($name, $label, $input_tag);
    }
    return $retval;
}

/**
 * Returns HTML to display the vote statistics. Requires as Parameter:
 * @param votingId the ID of the voting system entry.
 */
function _vote_result_html($votingId) {
    global $objDatabase, $_ARRAYLANG;
    $images = 1;

    $query     = "SELECT votes FROM ".DBPREFIX."voting_system WHERE id='$votingId'";
    $votes_res = $objDatabase->Execute($query);
    $votingVotes = $votes_res->fields['votes'];

    $query     = "SELECT id, question, votes FROM ".DBPREFIX."voting_results WHERE voting_system_id='$votingId' ORDER BY id";
    $objResult = $objDatabase->Execute($query);

    $out = '';

    while (!$objResult->EOF) {
        $votes=intval($objResult->fields['votes']);
        $percentage = 0;
        $imagewidth = 1; //Mozilla Bug if image width=0
        if($votes>0){
            $percentage = (round(($votes/$votingVotes)*10000))/100;
            $imagewidth = round($percentage,0);
        }

        if($imagewidth>80){
            $imagewidth = 80;
        }

        $out .= "<span class=\"VotingResultTitle\">".stripslashes($objResult->fields['question'])."</span><br />\n";
        $out .= "<img src='modules/Voting/View/Media/$images.gif' width='".$imagewidth."%' height='10' />";
        $out .= "&nbsp;<em>$votes ".$_ARRAYLANG['TXT_VOTES']." / $percentage %</em><br />";
        $objResult->MoveNext();
    }
    return $out;
}

?>
