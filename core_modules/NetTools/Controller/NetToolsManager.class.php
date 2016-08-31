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
 * Net Tools Manager
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_nettools
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Core_Modules\NetTools\Controller;
/**
 * Net Tools Manager
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_nettools
 */
class NetToolsManager extends \Cx\Lib\NetTools {

    var $pageTitle;
    var $strErrMessage = '';
    var $strOkMessage = '';
    var $_objTpl;


    /**
    * constructor
    */
    function netTools(){
       $this->__construct();
    }

    private $act = '';

    /**
    * constructor
    *
    * global    object    $objTemplate
    * global    array    $_ARRAYLANG
    */
    function __construct(){
        global $objTemplate, $_ARRAYLANG;

        $this->_objTpl = new \Cx\Core\Html\Sigma(ASCMS_CORE_MODULE_PATH.'/NetTools/View/Template/Backend');
            \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);
    }
    private function setNavigation()
    {
        global $objTemplate, $_CORELANG, $_ARRAYLANG;

        $objTemplate->setVariable('CONTENT_NAVIGATION', '
            <a href="index.php?cmd=SystemInfo" class="'.($this->act == '' ? 'active' : '').'">'.$_CORELANG['TXT_OVERVIEW'].'</a>
            <a href="index.php?cmd=SystemInfo&amp;act=phpinfo" class="'.($this->act == 'phpinfo' ? 'active' : '').'">'.$_CORELANG['TXT_PHP_INFO'].'</a>
            <a href="index.php?cmd=NetTools&amp;tpl=whois" class="'.($this->act == 'whois' ? 'active' : '').'">'.$_CORELANG['TXT_CORE_WHOIS'].'</a>
            <a href="index.php?cmd=NetTools&amp;tpl=lookup" class="'.($this->act == 'lookup' ? 'active' : '').'">'.$_CORELANG['TXT_LOOKUP'].'</a>
            <a href="index.php?cmd=NetTools&amp;tpl=mxlookup" class="'.($this->act == 'mxlookup' ? 'active' : '').'">'.$_CORELANG['TXT_MX_LOOKUP'].'</a>'.
            (!ini_get("safe_mode") ? '<a href="index.php?cmd=NetTools&amp;tpl=ping" class="'.($this->act == 'ping' ? 'active' : '').'">'.$_CORELANG['TXT_PING'].'</a>' : '').'
            <a href="index.php?cmd=NetTools&amp;tpl=port" class="'.($this->act == 'port' ? 'active' : '').'">'.$_CORELANG['TXT_CHECK_PORT'].'</a>
        ');
    }

    /**
    * Get content
    *
    * Get the content of the requested page
    *
    * @access    public
    * @global    \Cx\Core\Html\Sigma
    * @see    _showRequests(), _showMostViewedPages(), _showSpiders(), _showClients(), _showSearchTerms()
    * @return    mixed    Template content
    */
    function getContent(){
        global $objTemplate;

        if(!isset($_REQUEST['tpl'])){
            $_REQUEST['tpl'] = "";
        }

        switch ($_REQUEST['tpl']){
            case 'whois': // show whois page
                $this->_showWhois();
                break;

            case 'lookup':
                $this->_showLookup();
                break;

            case 'mxlookup':
                $this->_showMXLookup();
                break;

            case 'ping':
                $this->_showPing();
                break;

            case 'port':
                $this->_showPort();
                break;

            default:
                $this->_showWhois();
                break;
        }

        $objTemplate->setVariable(array(
            'CONTENT_TITLE'                => $this->pageTitle,
            'CONTENT_OK_MESSAGE'        => $this->strOkMessage,
            'CONTENT_STATUS_MESSAGE'    => $this->strErrMessage,
            'ADMIN_CONTENT'                => $this->_objTpl->get()
        ));

        $this->act = $_REQUEST['tpl'];
        $this->setNavigation();
    }

    function _showWhois() {
        global $_ARRAYLANG;

        $this->_objTpl->loadTemplateFile('module_nettools_whois.html',true,true);
        $this->pageTitle = $_ARRAYLANG['TXT_WHOIS'];

        // set language variables
        $this->_objTpl->setVariable(array(
            'TXT_WHOIS'                    => $_ARRAYLANG['TXT_WHOIS'],
            'TXT_BACK'                    => $_ARRAYLANG['TXT_BACK'],
            'TXT_WHOIS_TEXT'            => $_ARRAYLANG['TXT_WHOIS_TEXT'],
            'TXT_WHOIS_REQUEST'            => $_ARRAYLANG['TXT_WHOIS_REQUEST']
        ));

        if (isset($_REQUEST['address']) && !empty($_REQUEST['address'])) {
            $address = strip_tags($_REQUEST['address']);

            if ($this->IsIP($address)) {
                $whoisInfo = $this->WhoisIP($address);
            } else {
                $whoisInfo = $this->WhoisDomain($address);
            }

            if (empty($whoisInfo)) {
                $whoisInfo = $_ARRAYLANG['TXT_UNABLE_TO_WHOIS_TARGET'];
            }

            $this->_objTpl->setVariable(array(
                'NETTOOLS_WHOIS_INFO_TEXT'    => "<pre>".$whoisInfo."</pre>",
                'NETTOOLS_WHOIS_ADDRESS'    => $address,
                'NETTOOLS_WHOIS_BACK_LINK'        => "<a href=\"javascript:history.back()\" alt=\"".$_ARRAYLANG['TXT_BACK']."\" title=\"".$_ARRAYLANG['TXT_BACK']."\">".$_ARRAYLANG['TXT_BACK']."</a>"
            ));
            $this->_objTpl->parse('whoisinfo');
        } else {
            $this->_objTpl->setVariable(array(
                'NETTOOLS_WHOIS_ADDRESS'    => $_SERVER['REMOTE_ADDR'],
            ));
            $this->_objTpl->hideBlock('whoisinfo');
        }
    }

    function _showLookup() {
        global $_ARRAYLANG;
        $this->_objTpl->loadTemplateFile('module_nettools_lookup.html',true,true);
        $this->pageTitle = $_ARRAYLANG['TXT_LOOKUP'];

        $this->_objTpl->setVariable(array(
            'TXT_LOOKUP'            => $_ARRAYLANG['TXT_LOOKUP'],
            'TXT_LOOKUP_REQUEST'    => $_ARRAYLANG['TXT_LOOKUP_REQUEST'],
            'TXT_LOOKUP_TEXT'        => $_ARRAYLANG['TXT_LOOKUP_TEXT']
        ));

        if (isset($_REQUEST['address']) && !empty($_REQUEST['address'])) {
            $address = strip_tags($_REQUEST['address']);
            if ($this->IsIP($address)) {
                if ($this->LookupIP($address,$hostname)) {
                    $lookupResult = "".$_ARRAYLANG['TXT_HOSTNAME_OF']." $address: $hostname";
                } else {
                    $lookupResult = $_ARRAYLANG['TXT_LOOKUP_FAILED'];
                }
            } else {
                if ($this->LookupDomain($address,$ip)) {
                    $lookupResult = "".$_ARRAYLANG['TXT_IP_ADDRESS_OF']." $address: $ip";
                } else {
                    $lookupResult = $_ARRAYLANG['TXT_LOOKUP_FAILED'];
                }
            }

            $this->_objTpl->setVariable(array(
                'NETTOOLS_LOOKUP_ADDRESS'    => $address,
                'NETTOOLS_LOOKUP_RESULT'    => $lookupResult
            ));
            $this->_objTpl->parse('lookupinfo');
        } else {
            $this->_objTpl->setVariable(array(
                'NETTOOLS_LOOKUP_ADDRESS'    => $_SERVER['REMOTE_ADDR'],
            ));
            $this->_objTpl->hideBlock('lookupinfo');
        }
    }

    function _showMXLookup() {
        global $_ARRAYLANG;

        $this->_objTpl->loadTemplateFile('module_nettools_mxlookup.html');
        $this->pageTitle = $_ARRAYLANG['TXT_MX_LOOKUP'];

        $this->_objTpl->setVariable(array(
            'TXT_MX_LOOKUP'            => $_ARRAYLANG['TXT_MX_LOOKUP'],
            'TXT_MX_LOOKUP_TEXT'    => $_ARRAYLANG['TXT_MX_LOOKUP_TEXT'],
            'TXT_PREFERENCE'        => $_ARRAYLANG['TXT_PREFERENCE'],
            'TXT_HOSTNAME' => $_ARRAYLANG['TXT_HOSTNAME']
        ));

        if (isset($_REQUEST['address']) && !empty($_REQUEST['address'])) {
            $address = strip_tags($_REQUEST['address']);

            $objMXLookup = new MXLookup();
            if ($objMXLookup->getMailServers($address)) {
                $arrMxRRs = $objMXLookup->arrMXRRs;

                $rowNr = 0;
                foreach ($arrMxRRs as $arrMxRR) {
                    $this->_objTpl->setVariable(array(
                        'NETTOOLS_MX_LOOKUP_PREFERENCE'    => $arrMxRR['PREFERENCE'],
                        'NETTOOLS_MX_LOOKUP_HOST'        => $arrMxRR['EXCHANGE'],
                        'NETTOOLS_MX_LOOKUP_CLASS'        => $rowNr%2 == 0 ? "row2" : "row1"
                    ));
                    $this->_objTpl->parse('mxlookup-list');
                    $rowNr++;
                }
                $this->_objTpl->parse('mxlookup');
                $this->_objTpl->hideBlock('mxlookup-error');
            } else {
                $this->_objTpl->setVariable('NETTOOLS_MX_LOOKUP_ERROR', $objMXLookup->errorMsg);
                $this->_objTpl->hideBlock('mxlookup');
                $this->_objTpl->parse('mxlookup-error');
            }

            $this->_objTpl->setVariable('NETTOOLS_MX_LOOKUP_ADDRESS', $address);
        } else {
            $this->_objTpl->hideBlock('mxlookup');
            $this->_objTpl->hideBlock('mxlookup-error');
        }
    }

    function _showPing() {
        global $_ARRAYLANG;

        $this->_objTpl->loadTemplateFile('module_nettools_ping.html',true,true);
        $this->pageTitle = $_ARRAYLANG['TXT_PING'];

        $this->_objTpl->setVariable(array(
            'TXT_PING'    => $_ARRAYLANG['TXT_PING'],
            'TXT_PING_REQUEST' => $_ARRAYLANG['TXT_PING_REQUEST'],
            'TXT_PING_TEXT'        => $_ARRAYLANG['TXT_PING_TEXT']
        ));

        if (isset($_REQUEST['address']) && !empty($_REQUEST['address'])) {
            $address = strip_tags($_REQUEST['address']);
            $pingMsg = $this->PingHost($address,$err);
            if ($err) {
                $pingResult = $_ARRAYLANG['TXT_INVALID_TARGET'];
            } else {
                if (strlen($pingMsg) == 0) {
                    $pingResult = $_ARRAYLANG['TXT_NO_RESULT'];
                } else {
                    $pingResult = "<pre>".$pingMsg."</pre>";
                }
            }

            $this->_objTpl->setVariable(array(
                'NETTOOLS_PING_ADDRESS'    => $address,
                'NETTOOLS_PING_RESULT'    => $pingResult
            ));
            $this->_objTpl->parse('pinginfo');
        } else {
            $this->_objTpl->setVariable(array(
                'NETTOOLS_PING_ADDRESS'    => $_SERVER['REMOTE_ADDR'],
            ));
            $this->_objTpl->hideBlock('pinginfo');
        }
    }

    function _showPort() {
        global $_ARRAYLANG;

        $this->_objTpl->loadTemplateFile('module_nettools_port.html',true,true);
        $this->pageTitle = $_ARRAYLANG['TXT_CHECK_PORT'];

        $this->_objTpl->setVariable(array(
            'TXT_CHECK_PORT'        => $_ARRAYLANG['TXT_CHECK_PORT'],
            'TXT_CHECK'                => $_ARRAYLANG['TXT_CHECK'],
            'TXT_CHECK_PORT_TEXT'    => $_ARRAYLANG['TXT_CHECK_PORT_TEXT']
        ));

        if (isset($_REQUEST['address']) && !empty($_REQUEST['address'])) {
            $address = substr($_REQUEST['address'],0, strpos($_REQUEST['address'],":"));
            $port = (int) substr($_REQUEST['address'],strpos($_REQUEST['address'],":")+1);

            $result = $this->ProbePort($address, $port, $banner, $err);

            if ($result === 0) {
                $portResult = $_ARRAYLANG['TXT_PORT_IS_OPEN'];
            } elseif ($result === -1) {
                $portResult = $_ARRAYLANG['TXT_INVALID_PORT'].'!';
            } else {
                $portResult = $_ARRAYLANG['TXT_PORT_IS_CLOSED']." ($result)";
            }

            $this->_objTpl->setVariable(array(
                'NETTOOLS_PORT_ADDRESS'    => $_REQUEST['address'],
                'NETTOOLS_PORT_RESULT'    => $portResult
            ));
            $this->_objTpl->parse('portinfo');
        } else {
            $this->_objTpl->setVariable(array(
                'NETTOOLS_PORT_ADDRESS'    => $_SERVER['REMOTE_ADDR'].':80',
            ));
            $this->_objTpl->hideBlock('portinfo');
        }
    }
}
?>
