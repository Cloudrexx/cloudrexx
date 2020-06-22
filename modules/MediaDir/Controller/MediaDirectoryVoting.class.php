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
 * Media  Directory Voting Class
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\MediaDir\Controller;
/**
 * Media Directory Voting Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 */
class MediaDirectoryVoting extends MediaDirectoryLibrary
{
    public $strOkMessage;
    public $strErrMessage;

    /**
     * Constructor
     */
    function __construct($name)
    {
        parent::__construct('.', $name);
        parent::getSettings();
    }



    function getVoteJavascript(){
        global $_ARRAYLANG;

        $strOkMessage = $_ARRAYLANG['TXT_MEDIADIR_VOTING_SUCCESSFULL'];
        $strErrMessage = $_ARRAYLANG['TXT_MEDIADIR_VOTING_CORRUPT'];

        $strVoteJavascript  =  <<<EOF

var {$this->moduleNameLC}Vote = function(votes, entry)
{
    cx.jQuery('#voteForm_'+entry).html('<img src="modules/{$this->moduleName}/View/Media/loading.gif" border="0" alt="loading..." />');

    cx.jQuery.get('index.php', {section : "{$this->moduleName}", vote : votes, eid : entry}).success(function(response) {
        var status = response.substr(0,1);
        var votes = response.substr(1);

        if (status == 1) {
            cx.jQuery('#voteForm_'+entry).attr('class', '{$this->moduleNameLC}VotingOk');
            cx.jQuery('#votes_'+entry).attr('class', '{$this->moduleNameLC}NewVote');
            cx.jQuery('#votes_'+entry).html(votes);
            cx.jQuery('#voteForm_'+entry).html('$strOkMessage');
        } else {
            cx.jQuery('#voteForm_'+entry).attr('class', '{$this->moduleNameLC}VotingErr');
            cx.jQuery('#votes_'+entry).html(response);
            cx.jQuery('#voteForm_'+entry).html('$strErrMessage');
        }
    }).error(function() {
        cx.jQuery('#voteForm_'+entry).attr('class', '{$this->moduleNameLC}VotingErr');
        cx.jQuery('#voteForm_'+entry).html('$strErrMessage');
    });
}

EOF;
        return $strVoteJavascript;
    }



    function getVoteForm($objTpl, $intEnrtyId) {
        global $_ARRAYLANG, $objDatabase;

        if($this->arrSettings['settingsAllowVotes'] == 1) {
            $bolGenerateVoteForm = false;

            if($this->arrSettings['settingsVoteOnlyCommunity'] == 1) {
                $objFWUser  = \FWUser::getFWUserObject();
                $objUser    = $objFWUser->objUser;
                if($objUser->login()) {
                    $bolGenerateVoteForm = true;
                }
            } else {
                $bolGenerateVoteForm = true;
            }

            if($bolGenerateVoteForm) {
                $strVoteForm = '<div class="'.$this->moduleNameLC.'VoteForm" id="voteForm_'.$intEnrtyId.'">';

                for ($i=1; $i <= 10; $i++){
                    $strVoteForm .= "<input type=\"button\" onclick=\"".$this->moduleNameLC."Vote(".$i.", ".$intEnrtyId.")\" class=\"'.$this->moduleNameLC.'VoteButton\" value=\"".$i."\" />";
                }

                $strVoteForm .= '</div>';

                $objTpl->setVariable(array(
                    $this->moduleLangVar.'_ENTRY_VOTE_FORM' => $strVoteForm,
                    'TXT_'.$this->moduleLangVar.'_VOTING' => $_ARRAYLANG['TXT_MEDIADIR_VOTING']
                ));
            }
        }
    }



    function getVotes($objTpl, $intEnrtyId) {
        global $_ARRAYLANG, $objDatabase;

        if($this->arrSettings['settingsAllowVotes'] == 1) {
            $objRSGetVotes = $objDatabase->Execute("
                SELECT
                    `vote`
                FROM
                    ".DBPREFIX."module_".$this->moduleTablePrefix."_votes
                WHERE
                    `entry_id` = '".intval($intEnrtyId)."'
            ");

            $intCountVotes = $objRSGetVotes->RecordCount();

            if ($objRSGetVotes !== false) {
                while (!$objRSGetVotes->EOF) {
                    $intSumVotes = $objRSGetVotes->fields['vote']+$intSumVotes;
                    $objRSGetVotes->MoveNext();
                }
            }

            if($intCountVotes > 0) {
                $fltAverageVote = round($intSumVotes/$intCountVotes, 2);
            } else {
                $fltAverageVote = 0;
            }

            $strVotes = '<div class="'.$this->moduleNameLC.'Votes" id="votes_'.$intEnrtyId.'">'.intval($intCountVotes).' '.$_ARRAYLANG['TXT_MEDIADIR_VOTES'].' | '.$_ARRAYLANG['TXT_MEDIADIR_AVERAGE_SYMBOL'].' '.$fltAverageVote.'</div>';

            if($objTpl) {
                $objTpl->setVariable(array(
                    $this->moduleLangVar.'_ENTRY_VOTES' => $strVotes,
                    'TXT_'.$this->moduleLangVar.'_VOTING' => $_ARRAYLANG['TXT_MEDIADIR_VOTING']
                ));
            }

            return $strVotes;
        }
    }



    function saveVote($intEnrtyId, $intVote) {
        global $_ARRAYLANG, $objDatabase;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $strRemoteAddress = contrexx_addslashes(
            $cx->getComponent('Stats')->getCounterInstance()->getUniqueUserId()
        );

        if($this->arrSettings['settingsVoteOnlyCommunity'] == 1) {
            $objFWUser  = \FWUser::getFWUserObject();
            $objUser    = $objFWUser->objUser;
            $intUserId  = intval($objUser->getId());

            $strWhere = "(`added_by`='".$intUserId."')";
        } else {
            $strWhere = "(`ip`='".$strRemoteAddress."')";
        }

        $objCheckVote = $objDatabase->Execute("
            SELECT
                `id`
            FROM
                ".DBPREFIX."module_".$this->moduleTablePrefix."_votes
            WHERE
                $strWhere
            AND
                (`entry_id`='".intval($intEnrtyId)."')
        ");

        $intCount = $objCheckVote->RecordCount();

        if($intCount == 0) {
            $objInsertVote = $objDatabase->Execute("
                INSERT INTO
                    ".DBPREFIX."module_".$this->moduleTablePrefix."_votes
                SET
                    `entry_id`='".intval($intEnrtyId)."',
                    `added_by`='".intval($intUserId)."',
                    `date`='".time()."',
                    `ip`='".$strRemoteAddress."',
                    `vote`='".intval($intVote)."'
            ");

            if($objInsertVote !== false) {
                echo true;
            } else {
                echo false;
            }
        } else {
            echo false;
        }

        $this->refreshVotes($intEnrtyId);

        die();
    }



    function refreshVotes($intEnrtyId) {
        $strVotes = $this->getVotes(false, $intEnrtyId);
        echo $strVotes;
    }



    function restoreVoting($intEnrtyId) {
        global $_ARRAYLANG, $objDatabase;

        $objRestoreVoting = $objDatabase->Execute("
            DELETE FROM
                ".DBPREFIX."module_".$this->moduleTablePrefix."_votes
            WHERE
                `entry_id`='".intval($intEnrtyId)."'
        ");

        if($objRestoreVoting !== false) {
            return true;
        } else {
            return false;
        }
    }
}
