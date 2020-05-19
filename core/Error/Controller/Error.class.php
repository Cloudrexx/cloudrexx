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
 * Create error pages
 *
 * @copyright    CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @package        cloudrexx
 * @subpackage  core_error
 * @version        1.0.0
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Core\Error\Controller;

/**
 * Handles all errors.
 *
 * @copyright    CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @access        public
 * @package        cloudrexx
 * @subpackage  core_error
 * @version        1.0.0
 * @todo        Declare and document class variable 'pageContent'
 */
class Error
{
    /**
     * HTML Template
     * @access private
     */
    var $_objTpl;

    /**
     * Error page constructor
     * @param  string $pageContent Page content
     */
    function __construct($pageContent)
    {
        $this->pageContent = $pageContent;
        $this->_objTpl = new \Cx\Core\Html\Sigma();
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
    }


    /**
     * Set up page containing the error message.
     *
     * This is a synonym for {@link errorHandling()}.
     * @todo Is this deprecated?
     * @return string  Page content
     */
    function getErrorPage()
    {
        return $this->errorHandling();
    }


    /**
     * Set up page containing the error message.
     *
     * Sets up an error page determined by the error ID found in the 'id'
     * field of the $_REQUEST array.
     * @return string  Page content
     * @todo Handle more errors.
     */
    function errorHandling()
    {
        $this->_objTpl->setTemplate($this->pageContent);

        if (!isset($_REQUEST['id'])) {
            $_REQUEST['id'] = "";
        }

        switch ($_REQUEST['id']){
        case '404':
            $errorNo = 404;
            $errorMsg = "Not found";
            break;

        default:
            $errorNo = 404;
            $errorMsg = "Not found";
            break;
        }

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $cx->getResponse()->setCode($errorNo);
        \header($_SERVER['SERVER_PROTOCOL'] . ' ' . $errorNo . ' ' . $errorMsg);

        /*
        100 Continue
        101 Switching Protocols
        200 OK
        201 Created
        202 Accepted
        203 Non-Authoritative Information
        204 No Content
        205 Reset Content
        206 Partial Content
        300 Multiple Choices
        301 Moved Permanently
        302 Moved Temporarily
        303 See Other
        304 Not Modified
        305 Use Proxy
        400 Bad Request
        401 Unauthorised
        402 Payment Required
        403 Forbidden
        404 Not Found
        405 Method Not Allowed
        406 Not Acceptable
        407 Proxy Authentication Required
        408 Request Time-Out
        409 Conflict
        410 Gone
        411 Length Required
        412 Precondition Failed
        413 Request Entity Too Large
        414 Request-URL Too Large
        415 Unsupported Media Type
        500 Server Error
        501 Not Implemented
        502 Bad Gateway
        503 Out of Resources
        504 Gateway Time-Out
        505 HTTP Version not supported
        */

        $this->_objTpl->setVariable(array(
            'ERROR_NUMBER'    => $errorNo,
            'ERROR_MESSAGE'    => $errorMsg
        ));
        return $this->_objTpl->get();
    }
}
