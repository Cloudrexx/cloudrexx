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
 * Net Tools
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @version       1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_nettools
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Core_Modules\NetTools\Controller;
/**
 * Net Tools
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @version       1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_nettools
 */
class NetTools extends \Cx\Lib\NetTools {

    var $statusMessage;
    var $_objTpl;
    var $langId;

    /**
    * Constructor
    *
    * @param  string
    * @access public
    */
    function NetTools($pageContent)
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

        $this->_objTpl = new \Cx\Core\Html\Sigma();
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);
    }


    /**
    * Get page
    *
    * @access public
    * @return string content
    */
    function getPage()
    {
        if (!isset($_REQUEST['cmd'])) {
            $_REQUEST['cmd'] = '';
        }

        switch($_REQUEST['cmd']) {
        case 'details':
            return $this->_getDetails();
            break;

        case 'submit':
            return $this->_submit();
            break;
        case 'feed':
            return $this->_showFeed();
            break;

        default:
            return $this->_overview();
            break;
        }
    }


    function _overview(){

        global $_ARRAYLANG;

        $this->_objTpl->setTemplate($this->pageContent);

        $term = (isset($_POST['term']) && !empty($_POST['term'])) ? strip_tags($_POST['term']) : "";

        // set language variables
        $this->_objTpl->setVariable(array(
            'TXT_WHOIS'                 => $_ARRAYLANG['TXT_WHOIS'],
            'TXT_BACK'                  => $_ARRAYLANG['TXT_BACK'],
            'TXT_WHOIS_TEXT'            => $_ARRAYLANG['TXT_WHOIS_TEXT'],
            'TXT_WHOIS_REQUEST'         => $_ARRAYLANG['TXT_WHOIS_REQUEST'],
            'TXT_PING'  => $_ARRAYLANG['TXT_PING'],
            'TXT_PING_REQUEST' => $_ARRAYLANG['TXT_PING_REQUEST'],
            'TXT_PING_TEXT'     => $_ARRAYLANG['TXT_PING_TEXT'],
            'TXT_CHECK_PORT'        => $_ARRAYLANG['TXT_CHECK_PORT'],
            'TXT_CHECK'             => $_ARRAYLANG['TXT_CHECK'],
            'TXT_CHECK_PORT_TEXT'   => $_ARRAYLANG['TXT_CHECK_PORT_TEXT'],
            'NETTOOL_TERM'  => $term));

        switch($_GET['tool']) {
        case 'whois':
            $this->_objTpl->setVariable('NETTOOLS_RESULT',$this->_getWhois());
            break;

        case 'ping':
            $this->_objTpl->setVariable('NETTOOLS_RESULT',$this->_getPing());
            break;
        case 'feed':
            return $this->_showFeed();
            break;
        default:
            break;
        }
        return $this->_objTpl->get();
    }




    function _getWhois() {
        global $_ARRAYLANG;

        if (isset($_POST['term']) && !empty($_POST['term'])) {
            $address = strip_tags($_REQUEST['address']);

            if ($this->IsIP($address)) {
                $whoisInfo = $this->WhoisIP($address);
            } else {
                $whoisInfo = $this->WhoisDomain($address);
            }

            if (empty($whoisInfo)) {
                $whoisInfo = $_ARRAYLANG['TXT_UNABLE_TO_WHOIS_TARGET'];
            }
            return "<pre>".$whoisInfo."</pre>".$address;
        }
    }



    function _getPing() {
        global $_ARRAYLANG;


        $this->pageTitle = $_ARRAYLANG['TXT_PING'];



        if (isset($_POST['term']) && !empty($_POST['term'])) {
            $address = strip_tags($_REQUEST['address']);
            $pingMsg = $this->PingMsg($address,$err);
            if ($err) {
                $pingResult = $_ARRAYLANG['TXT_INVALID_TARGET'];
            } else {
                if (strlen($pingMsg) == 0) {
                    $pingResult = $_ARRAYLANG['TXT_NO_RESULT'];
                } else {
                    return "<pre>".$pingMsg."</pre>".$address;
                }
            }


        }
    }



     function _showPort() {
        global $_ARRAYLANG;



        if (isset($_POST['term']) && !empty($_POST['term'])) {
            $address = strip_tags($_REQUEST['address']);
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
                'NETTOOLS_PORT_ADDRESS' => $_REQUEST['address'],
                'NETTOOLS_PORT_RESULT'  => $portResult
            ));
            $this->_objTpl->parse('portinfo');
        } else {

        }
            return "<pre>".$portResult."</pre>".$address;
        }


}
?>
