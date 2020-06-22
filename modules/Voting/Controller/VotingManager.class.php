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
 * Class Voting Manager
 *
 * Class for the voting system
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Dev Team <info@cloudrexx.com>
 * @version       1.1
 * @package     cloudrexx
 * @subpackage  module_voting
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\Voting\Controller;
/**
 * Class Voting Manager
 *
 * Class for the voting system
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Dev Team <info@cloudrexx.com>
 * @version       1.1
 * @package     cloudrexx
 * @subpackage  module_voting
 * @todo        Edit PHP DocBlocks!
 */
class VotingManager
{
    var $_objTpl;
    var $strErrMessage = '';
    var $strOkMessage = '';

    private $act = '';

    /**
    * Constructor
    *
    * @param  string
    * @access public
    */
    function __construct()
    {
        global $_ARRAYLANG, $objTemplate;

        $this->_objTpl = new \Cx\Core\Html\Sigma(ASCMS_MODULE_PATH.'/Voting/View/Template/Backend');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);
    }
    private function setNavigation()
    {
        global $objTemplate, $_ARRAYLANG;

        $objTemplate->setVariable(array(
            'CONTENT_TITLE'      => $_ARRAYLANG['TXT_VOTING_MANAGER'],
            'CONTENT_NAVIGATION' => "<a href='?cmd=Voting' class='".($this->act == '' ? 'active' : '')."'>".$_ARRAYLANG['TXT_VOTING_RESULTS']."</a>
                                   <a href='?cmd=Voting&amp;act=add' class='".($this->act == 'add' ? 'active' : '')."'>".$_ARRAYLANG['TXT_VOTING_ADD']."</a>
                                   <a href='?cmd=Voting&amp;act=disablestatus' class='".($this->act == 'disablestatus' ? 'active' : '')."'>".$_ARRAYLANG['TXT_VOTING_DISABLE']."</a>"
           ));
    }


    function getVotingPage()
    {
        global $_ARRAYLANG, $objTemplate;

        if (empty($_GET['act'])) {
            $_GET['act'] = '';
        }

        switch($_GET['act']){
            case 'detail':
                $action = $this->_detail();
                break;
            case "add":
                $action = $this->votingAdd();
            break;
            case "edit":
                $action = $this->votingEdit();
            break;
            case "addsubmit":
                $action = $this->votingAddSubmit();
                if ($action){
                   $action = $this->showCurrent();
                }else{
                   $action = $this->votingAdd();
                }
            break;
            case "editsubmit":
                $action = $this->votingEditSubmit();
                $action = $this->showCurrent();
            break;
            case "changestatus":
                $action = $this->changeStatus();
                $action = $this->showCurrent();
            break;
             case "disablestatus":
                $action = $this->DisableStatus();
                $action = $this->showCurrent();
            break;
            case "delete":
                $action = $this->votingDelete();
                $action = $this->showCurrent();
            break;
            case 'additionalexport':
                $this->export_additional_data();
            break;
            case "code":
                $action = $this->votingCode();
            break;
            default:
                $action = $this->showCurrent();
        }

        $objTemplate->setVariable(array(
            'CONTENT_OK_MESSAGE'        => $this->strOkMessage,
            'CONTENT_STATUS_MESSAGE'    => $this->strErrMessage,
            'ADMIN_CONTENT'                => $this->_objTpl->get()
        ));

        $this->act = $_REQUEST['act'];
        $this->setNavigation();
    }


    function _detail()
    {
        global $objDatabase, $_CONFIG, $_ARRAYLANG;

        $systemId = intval($_REQUEST['id']);
        $count = 0;
        $pos = 0;

        $objVoting = $objDatabase->SelectLimit('SELECT `title`, `question` FROM `'.DBPREFIX.'voting_system` WHERE `id` = '.$systemId, 1);
        if ($objVoting !== false && $objVoting->RecordCount() == 1) {
            $title = $objVoting->fields['title'];
            $question = $objVoting->fields['question'];
        } else {
            return $this->showCurrent();
        }

        if (!empty($_GET['delete'])) {
            $objMail = $objDatabase->Execute('SELECT system_id, voting_id FROM `'.DBPREFIX.'voting_rel_email_system` WHERE email_id = '.intval($_GET['delete']));
            if ($objMail !== false && $objMail->RecordCount() > 0) {
                while (!$objMail->EOF) {
                    $objDatabase->Execute('UPDATE `'.DBPREFIX.'voting_system` SET `votes` = `votes` - 1 WHERE `id` = '.$objMail->fields['system_id']);
                    $objDatabase->Execute('UPDATE `'.DBPREFIX.'voting_results` SET `votes` = `votes` - 1 WHERE `id` = '.$objMail->fields['voting_id'].' AND `voting_system_id` = '.$objMail->fields['system_id']);
                    $objMail->MoveNext();
                }

                $objDatabase->Execute('DELETE FROM `'.DBPREFIX.'voting_rel_email_system` WHERE `email_id`         = '.intval($_GET['delete']));
                $objDatabase->Execute('DELETE FROM `'.DBPREFIX.'voting_email`            WHERE `id`               = '.intval($_GET['delete']));
                $objDatabase->Execute('DELETE FROM `'.DBPREFIX.'voting_additionaldata`   WHERE `voting_system_id` = '.intval($_GET['delete']));
            }
        }

        if (!empty($_GET['verify'])) {
            $objDatabase->Execute('UPDATE `'.DBPREFIX.'voting_email` SET `valid` = \'1\' WHERE `id` = '.intval($_GET['verify']));
        }

        $objCount = $objDatabase->SelectLimit('SELECT COUNT(1) AS votecount FROM `'.DBPREFIX.'voting_rel_email_system` AS s INNER JOIN `'.DBPREFIX.'voting_email` AS e ON e.id=s.email_id WHERE s.system_id='.$systemId.' GROUP BY s.system_id', 1);
        if ($objCount !== false) {
            $count = $objCount->fields['votecount'];
        }

        if (!$count) {
            return $this->showCurrent();
        }

        $this->_objTpl->loadTemplatefile('voting_detail.html');

        if ($count > $_CONFIG['corePagingLimit']) {
            $pos = isset($_GET['pos']) ? intval($_GET['pos']) : 0;
            $paging = getPaging($count, $pos, '&cmd=Voting&act=detail&id='.$systemId, ' E-Mails');
            $this->_objTpl->setVariable('VOTING_PAGING', '<br /><br />'.$paging."<br /><br />\n");
        }

        $this->_objTpl->setVariable(array(
            'VOTING_POS'    => $pos,
            'VOTING_ID'        => $systemId,
            'TXT_VOTING_FUNCTIONS'                    => $_ARRAYLANG['TXT_VOTING_FUNCTIONS'],
            'TXT_VOTING_EMAIL_ADRESSE_OF_QUESTION'    => sprintf($_ARRAYLANG['TXT_VOTING_EMAIL_ADRESSE_OF_QUESTION'], htmlentities($title, ENT_QUOTES).' ('.htmlentities($question, ENT_QUOTES).')'),
            'TXT_VOTING_EMAIL'                        => $_ARRAYLANG['TXT_VOTING_EMAIL'],
            'TXT_VOTING_VALID'                    => $_ARRAYLANG['TXT_VOTING_VALID'],
            'TXT_VOTING_FUNCTIONS'                    => $_ARRAYLANG['TXT_VOTING_FUNCTIONS'],
            'TXT_VOTING_CONFIRM_DELETE_EMAIL'        => $_ARRAYLANG['TXT_VOTING_CONFIRM_DELETE_EMAIL'],
            'TXT_VOTING_CONFIRM_VERIFY_EMAIL'        => $_ARRAYLANG['TXT_VOTING_CONFIRM_VERIFY_EMAIL']
        ));

        $this->_objTpl->setGlobalVariable(array(
            'TXT_VOTING_VERIFY_EMAIL'                => $_ARRAYLANG['TXT_VOTING_VERIFY_EMAIL'],
            'TXT_VOTING_DELETE_EMAIL'                => $_ARRAYLANG['TXT_VOTING_DELETE_EMAIL'],
            'TXT_VOTING_WRITE_EMAIL'                => $_ARRAYLANG['TXT_VOTING_WRITE_EMAIL']
        ));

        $objMails = $objDatabase->SelectLimit('SELECT e.id,e.email,e.valid FROM `'.DBPREFIX.'voting_rel_email_system` AS s INNER JOIN `'.DBPREFIX.'voting_email` AS e ON e.id=s.email_id WHERE s.system_id='.$systemId.' ORDER BY e.email', $_CONFIG['corePagingLimit'], $pos);
        if ($objMails !== false) {
            $row = 1;
            while (!$objMails->EOF) {
                $this->_objTpl->setVariable(array(
                    'VOTING_ROW_NR'        => $row = $row % 2 == 1 ? 2 : 1,
                    'VOTING_EMAIL'        => htmlentities($objMails->fields['email'], ENT_QUOTES),
                    'VOTING_EMAIL_ID'    => $objMails->fields['id'],
                    'VOTING_VALID'    => $objMails->fields['valid'] == '1' ? '<img src="../core/Core/View/Media/icons/check_mark.gif" width="16" height="16" alt="'.$_ARRAYLANG['TXT_VOTING_EMAIL_IS_VAILD'].'" />' : '<img src="../core/Core/View/Media/icons/question_mark.gif" width="16" height="16" alt="'.$_ARRAYLANG['TXT_VOTING_EMAIL_ISNT_VAILD'].'" />'
                ));

                if ($objMails->fields['valid'] == '1') {
                    $this->_objTpl->hideBlock('voting_verify_email');
                } else {
                    $this->_objTpl->touchBlock('voting_verify_email');
                }

                $objMails->MoveNext();

                $this->_objTpl->parse('voting_emails');
            }
            return true;
        }
        return false;
    }


    function showCurrent()
    {
        global $objDatabase, $_ARRAYLANG;

        $this->_objTpl->loadTemplateFile('voting_results.html');

        $query = "SELECT COUNT(1) as `count` FROM ".DBPREFIX."voting_system";
        $objResult = $objDatabase->Execute($query);
        if ($objResult) {
            $totalrows = $objResult->fields['count'];
        }

        $votingId = ((!isset($_GET['act']) || $_GET['act'] != "delete") && isset($_GET['votingid'])) ? intval($_GET['votingid']) : 0;

           $query= "SELECT id, date as datesec, question, votes FROM ".DBPREFIX."voting_system where ".($votingId > 0 ? "id=".$votingId : "status=1");
        $objResult = $objDatabase->SelectLimit($query, 1);
        if ($objResult->RecordCount()==0 && $totalrows==0) {
           \Cx\Core\Csrf\Controller\Csrf::header("Location: ?cmd=Voting&act=add");
           exit;
        } else {
            $votingId=$objResult->fields['id'];
            $votingTitle=stripslashes($objResult->fields['question']);
            $votingVotes=$objResult->fields['votes'];
            $votingDate=strtotime($objResult->fields['datesec']);
            $votingDate = \Cx\Core\DateTime\Controller\ComponentController::strftime('%A, %e. %B %Y', $votingDate);

            $images = 1;
            $query="SELECT id, question, votes FROM ".DBPREFIX."voting_results WHERE voting_system_id='$votingId' ORDER BY id";
            $objResult = $objDatabase->Execute($query);

            $votingResultText = '';
            while (!$objResult->EOF) {
                $votes=intval($objResult->fields['votes']);
                $percentage = 0;
                $imagewidth = 1; //Mozilla Bug if image width=0
                if($votes>0) {
                    $percentage = (round(($votes/$votingVotes)*10000))/100;
                    $imagewidth = round($percentage,0);
                }
                $votingResultText .= stripslashes($objResult->fields['question'])."<br />\n";
                $votingResultText .= "<img src='../core/Core/View/Media/icons/$images.gif' width='$imagewidth%' height=\"10\" alt=\"$votes ".$_ARRAYLANG['TXT_VOTES']." / $percentage %\" />";
                $votingResultText .= "&nbsp;<font size='1'>$votes ".$_ARRAYLANG['TXT_VOTES']." / $percentage %</font><br />\n";
                $objResult->MoveNext();
            }

            $this->_objTpl->setVariable(array(
                'VOTING_TITLE'               => $votingTitle,
                'VOTING_DATE'                => $votingDate,
                'VOTING_RESULTS_TEXT'         => $votingResultText,
                'VOTING_RESULTS_TOTAL_VOTES' => $votingVotes,
                'VOTING_TOTAL_TEXT'          => $_ARRAYLANG['TXT_VOTING_TOTAL'],
                'TXT_DATE'                   => $_ARRAYLANG['TXT_DATE'],
                'TXT_TITLE'                  => $_ARRAYLANG['TXT_TITLE'],
                'TXT_VOTES'                  => $_ARRAYLANG['TXT_VOTES'],
                'TXT_ACTION'                 => $_ARRAYLANG['TXT_ACTION'],
                'TXT_ACTIVATION'             => $_ARRAYLANG['TXT_ACTIVATION'],
                'TXT_CREATE_HTML'             => $_ARRAYLANG['TXT_CREATE_HTML'],
                'TXT_CONFIRM_DELETE_DATA'    => $_ARRAYLANG['TXT_CONFIRM_DELETE_DATA'],
                'TXT_ACTION_IS_IRREVERSIBLE' => $_ARRAYLANG['TXT_ACTION_IS_IRREVERSIBLE'],
                'TXT_EXPORT_ADDITIONAL'      => $_ARRAYLANG['TXT_EXPORT_ADDITIONAL'],
            ));

            $this->_objTpl->setGlobalVariable('TXT_HTML_CODE', $_ARRAYLANG['TXT_HTML_CODE']);

            // show other Voting entries
            $query="SELECT id,status,submit_check, date as datesec, title, votes FROM ".DBPREFIX."voting_system order by id desc";
            $objResult = $objDatabase->Execute($query);

            $i = 0;
            while(!$objResult->EOF) {
                $votingid=$objResult->fields['id'];
                $votingTitle=stripslashes($objResult->fields['title']);
                $votingVotes=$objResult->fields['votes'];
                $votingDate=strtotime($objResult->fields['datesec']);
                $votingDate = \Cx\Core\DateTime\Controller\ComponentController::strftime('%A, %e. %B %Y', $votingDate);
                $votingStatus=$objResult->fields['status'];

                if ($votingStatus==0) {
                     $radio=" onclick=\"Javascript: window.location.replace('index.php?cmd=Voting&amp;".\Cx\Core\Csrf\Controller\Csrf::param()."&amp;act=changestatus&amp;votingid=$votingid');\" />";
                } else {
                     $radio=" checked=\"checked\" />";
                }
                if (($i % 2) == 0) {
                    $class="row1";
                } else {
                    $class="row2";
                }

                $this->_objTpl->setVariable(array(
                    'VOTING_OLDER_TEXT'       => "<a href='?cmd=Voting&amp;votingid=$votingid'>".$votingTitle."</a>",
                    'VOTING_OLDER_DATE'      => $votingDate,
                    'VOTING_OLDER_VOTES'     => ($votingVotes > 0 && $objResult->fields['submit_check'] == 'email') ? '<a href="?cmd=Voting&amp;act=detail&amp;id='.$votingid.'" title="'.$_ARRAYLANG['TXT_VOTING_SHOW_EMAIL_ADRESSES'].'">'.$votingVotes.'</a>' : $votingVotes,
                    'VOTING_ID'              => $votingid,
                    'VOTING_LIST_CLASS'      => $class,
                    'VOTING_RADIO'           => "<input type='radio' name='voting_selected' value='radiobutton'".$radio,
                    'TXT_EXPORT_CSV'         => $_ARRAYLANG['TXT_EXPORT_CSV']
                ));
                $this->_objTpl->parse("votingRow");
                $i++;
                $objResult->MoveNext();
            }
        }
    }
    /**
     * Checks empty entries
     */
    function checkEntryData($var)
    {
        return (trim($var)!="");
    }

    function votingAddSubmit()
    {
        global $objDatabase, $_ARRAYLANG;

        if (empty($_POST['votingquestion']) || empty($_POST['votingname'])) return false;
        $options= explode ("\n", $_POST['votingoptions']);

        if (count(array_filter($options, array($this, 'checkEntryData')))<2) {
            return false;
        }

        $method = isset($_POST['votingRestrictionMethod']) ? contrexx_addslashes($_POST['votingRestrictionMethod']) : 'cookie';

        $query="UPDATE ".DBPREFIX."voting_system set status=0,date=date";
        $objDatabase->Execute($query);
        $query="INSERT INTO ".DBPREFIX."voting_system (
                title,question,status,submit_check,votes,
                additional_nickname,
                additional_forename,
                additional_surname ,
                additional_phone   ,
                additional_street  ,
                additional_zip     ,
                additional_city    ,
                additional_email   ,
                additional_comment
            )
            values (
                '".htmlspecialchars(addslashes($_POST['votingname']), ENT_QUOTES, CONTREXX_CHARSET)."',
                '".htmlspecialchars(addslashes($_POST['votingquestion']), ENT_QUOTES, CONTREXX_CHARSET)."',
                '1',
                '".$method."',
                '0',
                '".($_POST['additional_nickname']=='on'?1:0)."',
                '".($_POST['additional_forename']=='on'?1:0)."',
                '".($_POST['additional_surname' ]=='on'?1:0)."',
                '".($_POST['additional_phone'   ]=='on'?1:0)."',
                '".($_POST['additional_street'  ]=='on'?1:0)."',
                '".($_POST['additional_zip'     ]=='on'?1:0)."',
                '".($_POST['additional_city'    ]=='on'?1:0)."',
                '".($_POST['additional_email'   ]=='on'?1:0)."',
                '".($_POST['additional_comment' ]=='on'?1:0)."'
            )";
        $objDatabase->Execute($query);
        $query = "SELECT MAX(id) as max_id FROM ".DBPREFIX."voting_system";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult->EOF) {
            $latestid=$objResult->fields["max_id"];
        } else {
            $this->errorHandling();
            $this->strErrMessage = $_ARRAYLANG['TXT_SUBMIT_ERROR'];
            return false;
        }

        for ($i=0;$i<count($options);$i++) {
           $query="INSERT INTO ".DBPREFIX."voting_results (voting_system_id,question,votes)  values ($latestid,'".htmlspecialchars(addslashes(trim($options[$i])), ENT_QUOTES, CONTREXX_CHARSET)."',0)";
           if (trim($options[$i])!="") {
                   $objDatabase->Execute($query);
           }
        }
        $this->strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_STORED_SUCCESSFUL'];
        return true;
    }


    function votingEditSubmit()
    {
        global $objDatabase,$_ARRAYLANG;
        $deleted_votes=0;

        if (empty($_POST['votingquestion']) || empty($_POST['votingname'])) {
            return false;
        }

        $options= explode ("\n", $_POST['votingoptions']);
        $optionsid= explode (";", $_POST['votingresults']);
        $looptimes=max(count($options),count($optionsid));
        $method = isset($_POST['votingRestrictionMethod']) ? contrexx_addslashes($_POST['votingRestrictionMethod']) : 'cookie';

        if (count(array_filter($options, array($this, 'checkEntryData')))<2) {
            return false;
        }

        for ($i=0;$i<$looptimes;$i++) {
            if (trim($options[$i])!="") {
                if ($optionsid[$i]!="") {
                    $query="UPDATE ".DBPREFIX."voting_results set question='".htmlspecialchars(addslashes(trim($options[$i])), ENT_QUOTES, CONTREXX_CHARSET)."' WHERE id='".intval($optionsid[$i])."'";
                    $objDatabase->Execute($query);
                } else {
                     $query="INSERT INTO ".DBPREFIX."voting_results (voting_system_id,question,votes) values ('".intval($_POST['votingid'])."','".htmlspecialchars(addslashes(trim($options[$i])), ENT_QUOTES, CONTREXX_CHARSET)."',0)";
                    $objDatabase->Execute($query);
                }
            } elseif ($optionsid[$i]!="") {
                $query="SELECT votes FROM ".DBPREFIX."voting_results WHERE id='".intval($optionsid[$i])."'";
                $objResult = $objDatabase->Execute($query);
                if (!$objResult->EOF) {
                    $deleted_votes = $objResult->fields["votes"];
                }
                $query="DELETE FROM ".DBPREFIX."voting_results WHERE id='".intval($optionsid[$i])."'";
                $objDatabase->Execute($query);
            }
        }
        #print_r($_POST);

        $query="
            UPDATE ".DBPREFIX."voting_system
            SET date                = date,
                title               = '".htmlspecialchars(addslashes($_POST['votingname']), ENT_QUOTES, CONTREXX_CHARSET)."',
                question            = '".htmlspecialchars(addslashes($_POST['votingquestion']), ENT_QUOTES, CONTREXX_CHARSET)."',
                votes               = votes-".$deleted_votes.",
                submit_check        = '".$method."',
                additional_nickname = '".($_POST['additional_nickname']=='on'?1:0)."',
                additional_forename = '".($_POST['additional_forename']=='on'?1:0)."',
                additional_surname  = '".($_POST['additional_surname' ]=='on'?1:0)."',
                additional_phone    = '".($_POST['additional_phone'   ]=='on'?1:0)."',
                additional_street   = '".($_POST['additional_street'  ]=='on'?1:0)."',
                additional_zip      = '".($_POST['additional_zip'     ]=='on'?1:0)."',
                additional_city     = '".($_POST['additional_city'    ]=='on'?1:0)."',
                additional_email    = '".($_POST['additional_email'   ]=='on'?1:0)."',
                additional_comment  = '".($_POST['additional_comment' ]=='on'?1:0)."'

            WHERE id='".intval($_POST['votingid'])."'";
        #print "<pre>$query</pre>";
        if ($objDatabase->Execute($query)) {
            $this->strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_STORED_SUCCESSFUL'];
            return true;
        } else {
            $this->strErrMessage = $_ARRAYLANG['TXT_SUBMIT_ERROR'];
            return false;
        }
    }


    function votingDelete()
    {
        global $objDatabase;

        // Case when deleting the status active, it has to set another
        $query="SELECT id FROM ".DBPREFIX."voting_system WHERE status=1";
        $objResult = $objDatabase->Execute($query);
        if(!$objResult->EOF && $_GET['votingid']==$objResult->fields["id"]) {
            $query="SELECT  MAX(id) as maxid FROM ".DBPREFIX."voting_system WHERE status=0";
            $objResult = $objDatabase->query($query);
            if(!$objResult->EOF) {
               $maxid=$objResult->fields["maxid"];
               if (!is_null($maxid)) {
                       $query="UPDATE ".DBPREFIX."voting_system set status=1,date=date WHERE id=$maxid";
                       $objDatabase->Execute($query);
               }
            }

        }

        $objDatabase->Execute("DELETE FROM `".DBPREFIX."voting_rel_email_system` WHERE system_id=".intval($_GET['votingid']));
        $this->_cleanUpEmails();

          $query="DELETE FROM ".DBPREFIX."voting_system WHERE id=".intval($_GET['votingid'])." ";
        $objDatabase->Execute($query);
          $query="DELETE FROM ".DBPREFIX."voting_results WHERE voting_system_id=".intval($_GET['votingid'])." ";
        $objDatabase->Execute($query);
    }


    function _cleanUpEmails()
    {
        global $objDatabase;

        $arrEmailIds = array();

        $objEmails = $objDatabase->Execute("SELECT e.id FROM ".DBPREFIX."voting_email AS e INNER JOIN ".DBPREFIX."voting_rel_email_system AS s ON s.email_id=e.id");
        if ($objEmails !== false) {
            while (!$objEmails->EOF) {
                array_push($arrEmailIds, $objEmails->fields['id']);
                $objEmails->MoveNext();
            }

            $objDatabase->Execute("DELETE FROM ".DBPREFIX."voting_email".(count($arrEmailIds) > 0 ? " WHERE id!=".implode(' AND id!=', $arrEmailIds) : ''));
        }
    }


    function changeStatus()
    {
        global $objDatabase;

        $query="UPDATE ".DBPREFIX."voting_system set status=0, date=date";
        $objDatabase->Execute($query);
        $query="UPDATE ".DBPREFIX."voting_system set status=1,date=date where id=".intval($_GET['votingid'])." ";
        $objDatabase->Execute($query);
    }


    function DisableStatus()
    {
        global $objDatabase;

        $query="UPDATE ".DBPREFIX."voting_system set status=0, date=date";
        $objDatabase->Execute($query);
    }


    function votingAdd()
    {
        global $_ARRAYLANG;

        $this->_objTpl->loadTemplateFile('voting_add.html');

        $this->_objTpl->setVariable(array(
            'TXT_VOTING_METHOD_OF_RESTRICTION_TXT' => $_ARRAYLANG['TXT_VOTING_METHOD_OF_RESTRICTION_TXT'],
            'TXT_VOTING_COOKIE_BASED'              => $_ARRAYLANG['TXT_VOTING_COOKIE_BASED'],
            'TXT_VOTING_EMAIL_BASED'               => $_ARRAYLANG['TXT_VOTING_EMAIL_BASED'],
            'TXT_VOTING_ADD'                       => $_ARRAYLANG['TXT_VOTING_ADD'],
            'TXT_NAME'                             => $_ARRAYLANG['TXT_NAME'],
            'TXT_VOTING_QUESTION'                  => $_ARRAYLANG['TXT_VOTING_QUESTION'],
            'TXT_VOTING_ADD_OPTIONS'               => $_ARRAYLANG['TXT_VOTING_ADD_OPTIONS'],
            'TXT_STORE'                            => $_ARRAYLANG['TXT_STORE'],
            'TXT_RESET'                            => $_ARRAYLANG['TXT_RESET'],
            'TXT_ADDITIONAL_NICKNAME'              => $_ARRAYLANG['TXT_ADDITIONAL_NICKNAME'],
            'TXT_ADDITIONAL_FORENAME'              => $_ARRAYLANG['TXT_ADDITIONAL_FORENAME'],
            'TXT_ADDITIONAL_SURNAME'               => $_ARRAYLANG['TXT_ADDITIONAL_SURNAME' ],
            'TXT_ADDITIONAL_PHONE'                 => $_ARRAYLANG['TXT_ADDITIONAL_PHONE'   ],
            'TXT_ADDITIONAL_STREET'                => $_ARRAYLANG['TXT_ADDITIONAL_STREET'  ],
            'TXT_ADDITIONAL_ZIP'                   => $_ARRAYLANG['TXT_ADDITIONAL_ZIP'     ],
            'TXT_ADDITIONAL_CITY'                  => $_ARRAYLANG['TXT_ADDITIONAL_CITY'    ],
            'TXT_ADDITIONAL_EMAIL'                 => $_ARRAYLANG['TXT_ADDITIONAL_EMAIL'   ],
            'TXT_ADDITIONAL_COMMENT'               => $_ARRAYLANG['TXT_ADDITIONAL_COMMENT' ],
            'TXT_ADDITIONAL'                       => $_ARRAYLANG['TXT_ADDITIONAL'         ],
        ));
    }


    function votingEdit()
    {
        global $objDatabase, $_ARRAYLANG;

        $this->_objTpl->loadTemplateFile('voting_edit.html');

        $this->_objTpl->setVariable(array(
            'TXT_ADDITIONAL_NICKNAME' => $_ARRAYLANG['TXT_ADDITIONAL_NICKNAME'],
            'TXT_ADDITIONAL_FORENAME' => $_ARRAYLANG['TXT_ADDITIONAL_FORENAME'],
            'TXT_ADDITIONAL_SURNAME'  => $_ARRAYLANG['TXT_ADDITIONAL_SURNAME' ],
            'TXT_ADDITIONAL_PHONE'    => $_ARRAYLANG['TXT_ADDITIONAL_PHONE'   ],
            'TXT_ADDITIONAL_STREET'   => $_ARRAYLANG['TXT_ADDITIONAL_STREET'  ],
            'TXT_ADDITIONAL_ZIP'      => $_ARRAYLANG['TXT_ADDITIONAL_ZIP'     ],
            'TXT_ADDITIONAL_CITY'     => $_ARRAYLANG['TXT_ADDITIONAL_CITY'    ],
            'TXT_ADDITIONAL_EMAIL'    => $_ARRAYLANG['TXT_ADDITIONAL_EMAIL'   ],
            'TXT_ADDITIONAL_COMMENT'  => $_ARRAYLANG['TXT_ADDITIONAL_COMMENT' ],
            'TXT_ADDITIONAL'          => $_ARRAYLANG['TXT_ADDITIONAL'         ],
        ));

        $query="SELECT * FROM ".DBPREFIX."voting_system where id=".intval($_GET['votingid'])." ";
        $objResult = $objDatabase->Execute($query);
        if(!$objResult->EOF) {
            $votingname=stripslashes($objResult->fields["title"]);
            $votingquestion=stripslashes($objResult->fields["question"]);
            $votingid=$objResult->fields["id"];
            $votingmethod = $objResult->fields['submit_check'];

            $additional_nickname = $objResult->fields['additional_nickname'] ;
            $additional_forename = $objResult->fields['additional_forename'] ;
            $additional_surname  = $objResult->fields['additional_surname'] ;
            $additional_phone    = $objResult->fields['additional_phone'] ;
            $additional_street   = $objResult->fields['additional_street'] ;
            $additional_zip      = $objResult->fields['additional_zip'] ;
            $additional_city     = $objResult->fields['additional_city'] ;
            $additional_email    = $objResult->fields['additional_email'] ;
            $additional_comment  = $objResult->fields['additional_comment'];

        }

        $query="SELECT question,id FROM ".DBPREFIX."voting_results WHERE voting_system_id='$votingid' ORDER BY id";
        $objResult = $objDatabase->Execute($query);
        $i=0;
        while (!$objResult->EOF) {
            $votingoptions .= stripslashes($objResult->fields['question'])."\n";
            $voltingresults[$i]=$objResult->fields['id'];
            $i++;
            $objResult->MoveNext();
        }

        $this->_objTpl->setVariable(array(
            'TXT_VOTING_METHOD_OF_RESTRICTION_TXT'    => $_ARRAYLANG['TXT_VOTING_METHOD_OF_RESTRICTION_TXT'],
            'TXT_VOTING_COOKIE_BASED'                => $_ARRAYLANG['TXT_VOTING_COOKIE_BASED'],
            'TXT_VOTING_EMAIL_BASED'                => $_ARRAYLANG['TXT_VOTING_EMAIL_BASED'],
            'VOTING_METHOD_OF_RESTRICTION_COOKIE'    => $votingmethod == 'cookie' ? 'checked="checked"' : '',
            'VOTING_METHOD_OF_RESTRICTION_EMAIL'    => $votingmethod == 'email' ? 'checked="checked"' : '',
            'TXT_VOTING_EDIT'                        => $_ARRAYLANG['TXT_VOTING_EDIT'],
            'TXT_NAME'                                => $_ARRAYLANG['TXT_NAME'],
            'TXT_VOTING_QUESTION'                      => $_ARRAYLANG['TXT_VOTING_QUESTION'],
            'TXT_VOTING_ADD_OPTIONS'                 => $_ARRAYLANG['TXT_VOTING_ADD_OPTIONS'],
            'TXT_STORE'                              => $_ARRAYLANG['TXT_STORE'],
            'TXT_RESET'                             => $_ARRAYLANG['TXT_RESET'],
            'EDIT_NAME'                                => $votingname,
            'EDIT_QUESTION'                            => $votingquestion,
            'EDIT_OPTIONS'                            => $votingoptions,
            'VOTING_ID'                                => $votingid,
            'VOTING_RESULTS'                        => implode($voltingresults,";"),
            'VOTING_FLAG_ADDITIONAL_NICKNAME'       => $additional_nickname ? 'checked="checked"' : '',
            'VOTING_FLAG_ADDITIONAL_FORENAME'       => $additional_forename ? 'checked="checked"' : '',
            'VOTING_FLAG_ADDITIONAL_SURNAME'        => $additional_surname  ? 'checked="checked"' : '',
            'VOTING_FLAG_ADDITIONAL_PHONE'          => $additional_phone    ? 'checked="checked"' : '',
            'VOTING_FLAG_ADDITIONAL_STREET'         => $additional_street   ? 'checked="checked"' : '',
            'VOTING_FLAG_ADDITIONAL_ZIP'            => $additional_zip      ? 'checked="checked"' : '',
            'VOTING_FLAG_ADDITIONAL_CITY'           => $additional_city     ? 'checked="checked"' : '',
            'VOTING_FLAG_ADDITIONAL_EMAIL'          => $additional_email    ? 'checked="checked"' : '',
            'VOTING_FLAG_ADDITIONAL_COMMENT'        => $additional_comment  ? 'checked="checked"' : '',
        ));
    }


    function votingCode()
    {
        global $objDatabase, $_ARRAYLANG;

        $this->_objTpl->loadTemplateFile('voting_code.html');

        $query= "SELECT id,
                        status,
                        date as datesec,
                        question,
                        votes
                   FROM ".DBPREFIX."voting_system
                  WHERE id=".intval($_GET['votingid']);

        $objResult = $objDatabase->Execute($query);
        if (!$objResult->EOF) {
            $votingId=$objResult->fields['id'];
            $votingTitle=stripslashes($objResult->fields['question']);
// TODO: Never used
//            $votingVotes=$objResult->fields['votes'];
            $votingDate=strtotime($objResult->fields['datesec']);
            $votingDate = \Cx\Core\DateTime\Controller\ComponentController::strftime('%A, %e. %B %Y', $votingDate);
// TODO: Never used
//            $votingStatus=$objResult->fields['status'];
        } else {
            $this->errorHandling();
            return false;
        }

        $query="SELECT id, question, votes FROM ".DBPREFIX."voting_results WHERE voting_system_id='$votingId' ORDER BY id";
        $objResult = $objDatabase->Execute($query);

        while (!$objResult->EOF) {
            $votingResultText .= '<input type="radio" name="votingoption" value="'.$objResult->fields['id'].'" />';
            $votingResultText .= $objResult->fields['question']."<br />\n";
            $objResult->MoveNext();
        }

        $submitbutton= '<input type="submit" value="'.$_ARRAYLANG['TXT_SUBMIT'].'" name="Submit" />';

        $this->_objTpl->setVariable(array(
            'VOTING_TITLE'             => htmlentities($votingTitle, ENT_QUOTES, CONTREXX_CHARSET)." - ".$votingDate,
            'VOTING_CODE'              => $_ARRAYLANG['TXT_VOTING_CODE'],
            'VOTING_RESULTS_TEXT'      => htmlentities($votingResultText, ENT_QUOTES, CONTREXX_CHARSET),
            'TXT_SUBMIT'               => htmlentities($submitbutton, ENT_QUOTES, CONTREXX_CHARSET),
            'TXT_SELECT_ALL'           => $_ARRAYLANG['TXT_SELECT_ALL']

        ));
        return true;
    }


    function errorHandling()
    {
        global $_ARRAYLANG;
        $this->strErrMessage.= " ".$_ARRAYLANG['TXT_DATABASE_QUERY_ERROR']." ";
    }


    function export_additional_data()
    {
        global $objDatabase;

        // Figure out which fields we need to export here
        $voting_id = intval($_GET['votingid']);
        $sql = "
            SELECT
                additional_nickname AS nickname,
                additional_forename AS forename,
                additional_surname  AS surname ,
                additional_phone    AS phone   ,
                additional_street   AS street  ,
                additional_zip      AS zip     ,
                additional_city     AS city    ,
                additional_email    AS email   ,
                additional_comment  AS comment
            FROM ".DBPREFIX."voting_system
            WHERE id = $voting_id
        ";
        $res = $objDatabase->Execute($sql);

        $fields = array();
        foreach ($res->fields as $field => $enabled) {
            if ($enabled) $fields[] = $field;
        }

        // Check if we have anything to export at all
        if (!sizeof($fields)) {
            // No export fields defined. Don't do export.
            $_GET['act'] = '';
            $_GET['votingid'] = '';
            return $this->showCurrent();
        }

        // Now select those fields from our table.
        $fields_txt = join(',', $fields);
        #echo "exporting $fields_txt...\n";

        $sql_export = "
            SELECT $fields_txt
            FROM ".DBPREFIX."voting_additionaldata
            WHERE voting_system_id = $voting_id
            ORDER BY date_entered
            ";
        $data = $objDatabase->Execute($sql_export);
        header("Content-Type: text/csv");
        header("Content-Disposition: Attachment; filename=\"export.csv\"");
        while (!$data->EOF) {
            print($this->_format_csv($data->fields) . "\r\n");
            $data->MoveNext();
        }
        exit;
    }


    /**
     * Returns a line suitable to put in a CSV file.
     * @param list array    The list to be put in CSV.
     * @param separator string [optional] Separator, defaults to ";"
     */
    function _format_csv($list, $separator=';')
    {
        // First, fix the data values if they
        // contain newlines or the separator.
        $printable = array();
        foreach ($list as $elem) {
            if (preg_match("/$separator/", $elem) or preg_match('/[\r\n]/', $elem)) {
                $printable[] = '"' . $elem . '"';
            }
            else {
                $printable[] = $elem;
            }
        }
        return join($separator, $printable);
    }
}

?>
