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
 * Response for a request to API
 *
 * @copyright   Cloudrexx AG
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage core_modules_dataaccess
 */

namespace Cx\Core_Modules\DataAccess\Model\Entity;

/**
 * Response for a request to API
 *
 * @copyright   Cloudrexx AG
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage core_modules_dataaccess
 */
class ApiResponse extends \Cx\Model\Base\EntityBase implements \JsonSerializable {

    /**
     * @var string Error status
     */
    const STATUS_ERROR = 'error';

    /**
     * @var string Success status
     */
    const STATUS_OK = 'ok';

    /**
     * @var string Message type success
     */
    const MESSAGE_TYPE_SUCCESS = 'success';

    /**
     * @var string Message type error
     */
    const MESSAGE_TYPE_ERROR = 'error';

    /**
     * @var string Message type info
     */
    const MESSAGE_TYPE_INFO = 'info';

    /**
     * @var \Cx\Core\Routing\Model\Entity\Request Request object
     */
    protected $request;

    /**
     * @var string one of STATUS_ERROR, STATUS_OK
     */
    protected $status;

    /**
     * @var int HTTP status code
     */
    protected $statusCode = 0;

    /**
     * @var two dimensional array: $messages[<type>][] = <messageText>
     */
    protected $messages = array();

    /**
     * @var array of data
     */
    protected $data = array();

    /**
     * Creates an ApiResponse
     *
     * Please note, that you need to set the status before you can send this request!
     * @param string $status (optional) One of STATUS_ERROR, STATUS_OK
     * @param array $messages (optional) two dimensional array: $messages[<type>][] = <messageText>
     * @param array $data (optional) Set of data
     */
    public function __construct($status = '', $messages = array(), $data = array()) {
        $this->request = $this->cx->getRequest();
        $this->status = $status;
        $this->messages = $messages;
        $this->data = $data;
    }

    /**
     * Adds a message
     * @param string $type Message type, one of MESSAGE_TYPE_*
     * @param string $text Message
     */
    public function addMessage($type, $text) {
        if (!isset($this->messages[$type])) {
            $this->messages[$type] = array();
        }
        $this->messages[$type][] = $text;
    }

    /**
     * Sets response data
     * @param array $data Data for this response
     */
    public function setData($data) {
        $this->data = $data;
    }

    /**
     * Sets response status
     *
     * If statusCode is not set yet, it sets it to 200 for OK, 400 for ERROR
     * @param string $status One of STATUS_ERROR, STATUS_OK
     */
    public function setStatus($status) {
        if ($status == static::STATUS_OK) {
            $this->setStatusCode(200);
        } else {
            $this->setStatusCode(400);
        }
        $this->status = $status;
    }

    /**
     * Sets the HTTP status code
     * @param int Status code (as specified in https://tools.ietf.org/html/rfc7231#section-6)
     * @param boolean (optional) Wheter to replace a previously set status code or not (default false)
     */
    public function setStatusCode($statusCode, $replace = false) {
        if ($this->statusCode > 0 && !$replace) {
            return;
        }
        $this->statusCode = $statusCode;
    }

    /**
     * Returns the HTTP status code for this response
     * @return int Status code (as specified in https://tools.ietf.org/html/rfc7231#section-6)
     */
    public function getStatusCode() {
        return $this->statusCode;
    }

    /**
     * Returns the status for this response
     * @return string One of STATUS_ERROR, STATUS_OK
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * Returns data of this response
     * @return array Set of data
     */
    public function getData() {
        $this->data;
    }

    /**
     * Returns a list of messages for this request
     * @param string $type (optional) Limits the result to a type of messages
     * @return array List of messages (of any type, except if $type is provided)
     */
    public function getMessages($type = '') {
        if (empty($type)) {
            return array_merge(
                $this->getMessages(static::MESSAGE_TYPE_SUCCESS),
                $this->getMessages(static::MESSAGE_TYPE_ERROR),
                $this->getMessages(static::MESSAGE_TYPE_INFO)
            );
        }
        return $this->messages[$type];
    }

    /**
     * Removes a message
     * @param string $type Message type, one of MESSAGE_TYPE_*
     * @param string $text Message
     * @return boolean True if successful, false if message could not be found
     */
    public function removeMessage($type, $text) {
        if (!is_array($this->messages[$type])) {
            return false;
        }
        $index = array_search($text, $this->messages[$type]);
        if ($index === false) {
            return false;
        }
        unset($this->messages[$text][$index]);
        return true;
    }

    /**
     * Serializes this object for JSON, we use it for all output modules
     * This is used in order to avoid public member variables
     * @return array Array representation of this object
     */
    public function jsonSerialize() {
        return array(
            'status' => $this->status,
            'meta' => array(
                'request' => $this->request,
            ),
            'messages' => $this->messages,
            'data' => $this->data,
        );
    }

    /**
     * Sets HTTP status code and writes this object to output buffer
     * @param \Cx\Core_Modules\DataAccess\Controller\OutputController $outputModule Output module to use for parsing
     * @param boolean $setStatusCode (optional) Wheter to set HTTP status header (default: true)
     */
    public function send($outputModule, $setStatusCode = true) {
        if ($setStatusCode) {
            http_response_code($this->getStatusCode());
        }
        echo $outputModule->parse($this->jsonSerialize());
    }
}
