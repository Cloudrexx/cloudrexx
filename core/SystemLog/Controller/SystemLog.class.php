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
 * SystemLog
 * Class to see SystemLog
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @version        1.0.0
 * @package     cloudrexx
 * @subpackage  core_systemlog
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Core\SystemLog\Controller;
/**
 * Class SystemLog
 * Class to see SystemLog
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @access        public
 * @version        1.0.0
 * @package     cloudrexx
 * @subpackage  core_systemlog
 */
class SystemLog
{
    var $statusMessage = "";

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
    }


    function setNavigation()
    {
        global $_ARRAYLANG, $objTemplate;

        $objTemplate->setVariable(array(
            'CONTENT_TITLE'      => $_ARRAYLANG['TXT_OVERVIEW'],
            'CONTENT_NAVIGATION' => '<a href="index.php?cmd=SystemLog" class="active">'.$_ARRAYLANG['TXT_OVERVIEW'].'</a>',
        ));
    }


    function getLogPage()
    {
        global $objTemplate;

        if (!isset($_GET['act'])) {
            $_GET['act'] = '';
        }

        switch($_GET['act']){
            case "del":
                $this->deleteLog();
                $action = $this->showLogs();
                break;
            case "details":
                $action = $this->showDetails();
                break;
            case "stats":
                $action = $this->showStats();
                break;
            default:
                $action = $this->showLogs();
                break;
        }

        $objTemplate->setVariable(array(
            'CONTENT_STATUS_MESSAGE' => trim($this->statusMessage)
        ));

        $this->act = $_REQUEST['act'];
        $this->setNavigation();
    }


    function deleteLog()
    {
        global $objDatabase;

        if(!empty($_REQUEST['logId'])) {
            if ($objDatabase->Execute("DELETE FROM ".DBPREFIX."log WHERE id=".intval($_REQUEST['logId'])) === false) {
                $this->errorHandling();
                return false;
            }
            return true;
        } else {
            return false;
        }
    }



    /*
    * Show Logs
    *
    */
    function showLogs()
    {
        global $objDatabase, $_ARRAYLANG, $_CONFIG, $objTemplate;

        $objTemplate->addBlockfile('ADMIN_CONTENT', 'log', 'log.html');
        $objTemplate->setVariable(array(
            'TXT_SYSTEM_LOGS'            => $_ARRAYLANG['TXT_SYSTEM_LOGS'],
            'TXT_CONFIRM_DELETE_DATA'    => $_ARRAYLANG['TXT_CONFIRM_DELETE_DATA'],
            'TXT_ACTION_IS_IRREVERSIBLE' => $_ARRAYLANG['TXT_ACTION_IS_IRREVERSIBLE'],
            'TXT_HOSTNAME'               => $_ARRAYLANG['TXT_HOSTNAME'],
            'TXT_IP_ADDRESS'             => $_ARRAYLANG['TXT_IP_ADDRESS'],
            'TXT_USER_NAME'              => $_ARRAYLANG['TXT_USERNAME'],
            'TXT_LOGTIME'                => $_ARRAYLANG['TXT_LOGTIME'],
            'TXT_USERAGENT'              => $_ARRAYLANG['TXT_USERAGENT'],
            'TXT_BROWSERLANGUAGE'        => $_ARRAYLANG['TXT_BROWSERLANGUAGE'],
            'TXT_ACTION'                 => $_ARRAYLANG['TXT_ACTION'],
            'TXT_SEARCH'                 => $_ARRAYLANG['TXT_SEARCH']
        ));

        $objFWUser = \FWUser::getFWUserObject();

        $user = isset($_GET['user'])  ? intval($_GET['user']) : 0;
        $term = isset($_POST['term']) ? contrexx_input2db($_POST['term']) : '';
        $objTemplate->setVariable('LOG_SEARCHTERM', $term);
        $q_search = '';

        if(!empty($term)){
           $q_search = "WHERE log.id LIKE '%$term%'
                       OR log.userid LIKE '%$term%'
                       OR log.useragent LIKE '%$term%'
                       OR log.userlanguage LIKE '%$term%'
                       OR log.remote_addr LIKE '%$term%'
                       OR log.remote_host LIKE '%$term%'
                       OR log.http_via LIKE '%$term%'
                       OR log.http_client_ip LIKE '%$term%'
                       OR log.http_x_forwarded_for LIKE '%$term%'
                       OR log.referer LIKE '%$term%'";
            if ($objUser = $objFWUser->objUser->getUsers(array('username' => "%$term%"))) {
                while (!$objUser->EOF) {
                    $q_search .= ' OR log.userid='.$objUser->getId();
                    $objUser->next();
                }
            }
        } else if (!empty($user)) {
            $q_search = 'WHERE log.userid = '.$user;
        }

        $q = "SELECT log.id AS id,
                     log.userid AS userid,
                     log.datetime AS datetime,
                     log.useragent AS useragent,
                     log.userlanguage AS userlanguage,
                     log.remote_addr AS remote_addr,
                     log.remote_host AS remote_host,
                     log.http_via AS http_via,
                     log.http_client_ip AS http_client_ip,
                     log.http_x_forwarded_for AS http_x_forwarded_for,
                     log.referer AS referer
                FROM ".DBPREFIX."log AS log
                $q_search
                ORDER BY log.id DESC
         ";

        $objResult = $objDatabase->Execute($q);
        if ($objResult === false) {
            $this->errorHandling();
            return false;
        }

        $pos = intval($_GET[pos]);
        $count = $objResult->RecordCount();

        if(!empty($term)) {
            $paging = getPaging($count, $pos, "&cmd=SystemLog&term=$term", "<b>".$_ARRAYLANG['TXT_LOG_ENTRIES']."</b>", true);
        } else {
            $paging = getPaging($count, $pos, "&cmd=SystemLog", "<b>".$_ARRAYLANG['TXT_LOG_ENTRIES']."</b>", true);
        }

        $objResult = $objDatabase->SelectLimit($q, $_CONFIG['corePagingLimit'], $pos);
        if ($objResult === false) {
            $this->errorHandling();
            return false;
        }

        $objTemplate->setVariable(array(
            'LOG_PAGING' => $paging,
            'LOG_TOTAL'  => $count
        ));

        while (!$objResult->EOF) {
            $objUser = $objFWUser->objUser->getUser($objResult->fields['userid']);

            if (($i % 2) == 0) {$class="row1";} else {$class="row2";}
            $objTemplate->setVariable(array(
                'LOG_ROWCLASS'        => $class,
                'LOG_ID'              => $objResult->fields['id'],
                'LOG_USERID'          => $objUser ? $objUser->getId() : 0,
                'LOG_USERNAME'        => $objUser ? htmlentities($objUser->getUsername(), ENT_QUOTES, CONTREXX_CHARSET) : '',
                'LOG_TIME'            => $objResult->fields['datetime'],
                'LOG_USERAGENT'       => $objResult->fields['useragent'],
                'LOG_USERLANGUAGE'    => $objResult->fields['userlanguage'],
                'LOG_REMOTE_ADDR'     => $objResult->fields['remote_addr'],
                'LOG_REMOTE_HOST'     => $objResult->fields['remote_host'],
                'LOG_HTTP_VIA'        => $objResult->fields['http_via'],
                'LOG_CLIENT_IP'       => $objResult->fields['http_client_ip'],
                'LOG_X_FORWARDED_FOR' => $objResult->fields['http_x_forwarded_for'],
                'LOG_REFERER'         => $objResult->fields['referer']
             ));
            $objTemplate->parse("logRow");
            $i++;
            $objResult->MoveNext();
        }
    }




    /**
    * Set the error Message (void())
    *
    * @global    array
    */
    function errorHandling()
    {
        global $_ARRAYLANG;
        $this->statusMessage .= $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR']."<br>";
    }
}
