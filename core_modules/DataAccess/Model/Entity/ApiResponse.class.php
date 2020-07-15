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
     * @var string One of STATUS_ERROR, STATUS_OK
     */
    protected $status;

    /**
     * @var int HTTP status code
     */
    protected $statusCode = 0;

    /**
     * @var array Additional MetaData to add to API
     */
    protected $metaData = array();

    /**
     * @var array Two dimensional array: $messages[<type>][] = <messageText>
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
     * @param array $metaData (optional)
     */
    public function __construct(
        string $status = '',
        array $messages = array(),
        array $data = array(),
        array $metaData = array()
    ) {
        $this->request = $this->cx->getRequest();
        $this->status = $status;
        $this->messages = $messages;
        $this->data = $data;
        $this->metaData = $metaData;
    }

    /**
     * Adds a message
     * @param string $type Message type, one of MESSAGE_TYPE_*
     * @param string $text Message
     */
    public function addMessage(string $type, string $text)
    {
        if (!isset($this->messages[$type])) {
            $this->messages[$type] = array();
        }
        $this->messages[$type][] = $text;
    }

    /**
     * Sets response data
     * @param array $data Data for this response
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * Set a array with metadata for the API
     * @param array $metaData Data to pass in metadata field
     */
    public function setMetadata(array $metaData) {
        $this->metaData = $metaData;
    }

    /**
     * Get the array with the metadata
     * @return array Data passed as metadata
     */
    public function getMetadata(): array {
        return $this->metaData;
    }

    /**
     * Sets response status
     *
     * If statusCode is not set yet, it sets it to 200 for OK, 400 for ERROR
     * @param string $status One of STATUS_ERROR, STATUS_OK
     */
    public function setStatus(string $status)
    {
        if ($status == static::STATUS_OK) {
            $this->setStatusCode(200);
        } else {
            $this->setStatusCode(400);
        }
        $this->status = $status;
    }

    /**
     * Sets the HTTP status code
     * @param   int     $statusCode Status code as specified in
     *                              https://tools.ietf.org/html/rfc7231#section-6
     * @param   bool    $replace    Replace the current value if true
     */
    public function setStatusCode(int $statusCode, bool $replace = false)
    {
        if ($this->statusCode > 0 && !$replace) {
            return;
        }
        $this->statusCode = $statusCode;
    }

    /**
     * Returns the HTTP status code for this response
     * @return  int                 Status code as specified in
     *                              https://tools.ietf.org/html/rfc7231#section-6
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Returns the status for this response
     * @return string One of STATUS_ERROR, STATUS_OK
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Returns data of this response
     * @return array Set of data
     */
    public function getData(): array
    {
        $this->data;
    }

    /**
     * Returns a list of messages for this request
     * @param string $type (optional) Limits the result to a type of messages
     * @return array List of messages (of any type, except if $type is provided)
     */
    public function getMessages(string $type = ''): array
    {
        if (empty($type)) {
            return array_merge(
                $this->getMessages(static::MESSAGE_TYPE_SUCCESS),
                $this->getMessages(static::MESSAGE_TYPE_ERROR),
                $this->getMessages(static::MESSAGE_TYPE_INFO)
            );
        }
        return $this->messages[$type] ?? [];
    }

    /**
     * Removes a message
     * @param string $type Message type, one of MESSAGE_TYPE_*
     * @param string $text Message
     * @return boolean True if successful, false if message could not be found
     */
    public function removeMessage(string $type, string $text): bool
    {
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
    public function jsonSerialize(): array
    {
        $this->metaData['request'] = $this->request;
        return array(
            'status' => $this->status,
            'meta' => $this->metaData,
            'messages' => $this->messages,
            'data' => $this->data,
        );
    }

    /**
     * Sets HTTP status code and writes this object to output buffer
     * @param \Cx\Core_Modules\DataAccess\Controller\OutputController $outputModule Output module to use for parsing
     * @param bool  $setStatusCode  Set the HTTP status header if true
     */
    public function send(
        \Cx\Core_Modules\DataAccess\Controller\OutputController $outputModule,
        bool $setStatusCode = true
    ) {
        if ($setStatusCode) {
            http_response_code($this->getStatusCode());
        }
        echo $outputModule->parse($this->jsonSerialize());
    }
}
