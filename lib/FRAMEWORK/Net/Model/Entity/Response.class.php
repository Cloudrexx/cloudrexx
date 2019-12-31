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
 * A response to a request
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_net
 * @link        http://www.cloudrexx.com/ cloudrexx homepage
 * @since       v5.0.0
 */

namespace Cx\Lib\Net\Model\Entity;

/**
 * An exception while sending a response
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_net
 * @link        http://www.cloudrexx.com/ cloudrexx homepage
 * @since       v5.0.0
 */
class ResponseException extends \Exception {}

/**
 * A response to a request
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_net
 * @link        http://www.cloudrexx.com/ cloudrexx homepage
 * @since       v5.0.0
 */
class Response {
    /**
     * @var \DateTime Expiration date
     */
    protected $expirationDate;

    /**
     * @var string Content type
     */
    protected $contentType = 'text/plain';

    /**
     * @var Request (optional) Request
     */
    protected $request = null;

    /**
     * @var int Response code
     */
    protected $code;

    /**
     * @var object|callable Parser to parse abstract content
     */
    protected $parser;

    /**
     * @var string Message
     */
    protected $message;

    /**
     * @var mixed Abstract response data
     */
    protected $abstractContent;

    /**
     * @var string Response data
     */
    protected $parsedContent;

    /**
     * @var array List of headers (key=>value)
     */
    protected $headers = array();

    /**
     * Creates a new Response
     * @param mixed $abstract Abstract response data
     * @param int $code (optional) Response code, default is 200
     * @param Request $request (optional) Request object, default is null
     * @param \DateTime $expirationDate (optional) Expire date for this response, default is null
     */
    public function __construct($abstractContent, $code = 200, $request = null, $expirationDate = null) {
        $this->setAbstractContent($abstractContent);
        $this->setCode($code);
        $this->setRequest($request);
        $this->setExpirationDate($expirationDate);
    }

    /**
     * Returns the expiration date for this Response
     * @return \DateTime Expire date
     */
    public function getExpirationDate() {
        return $this->expirationDate;
    }

    /**
     * Sets the expiration date for this Response
     * @param \DateTime $expirationDate Expiration date (can be set to null)
     */
    public function setExpirationDate($expirationDate) {
        $this->expirationDate = $expirationDate;
    }

    /**
     * Returns the content type
     * @return String Content type
     */
    public function getContentType() {
        return $this->contentType;
    }

    /**
     * Sets the content type
     * @param string $contentType Content type
     */
    public function setContentType($contentType) {
        $this->contentType = $contentType;
    }

    /**
     * Returns the Request object for this Response
     * @return \Request Request this Response is for
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * Sets the Request object for this Response
     * @param \Request $request Request this Response is for
     */
    public function setRequest($request) {
        $this->request = $request;
    }

    /**
     * Returns the response code
     * @return int Response code
     */
    public function getCode() {
        return $this->code;
    }

    /**
     * Sets the response code
     * @param int $code Response code
     */
    public function setCode($code) {
        $this->code = $code;
    }

    /**
     * Returns the message
     * Message may be used for user interaction or debugging and is "part" of abstract content
     * @return string Message
     */
    public function getMessage() {
        return $this->message;
    }

    /**
     * Sets the message
     * Message may be used for user interaction or debugging and is "part" of abstract content
     * @param string $message Message for user interaction or debugging
     */
    public function setMessage($message) {
        $this->message = $message;
    }

    /**
     * Returns the abstract response data
     * @return mixed Abstract response data
     */
    public function getAbstractContent() {
        return $this->abstractContent;
    }

    /**
     * Sets abstract response data
     * @param mixed $abstractContent Abstract response data
     */
    public function setAbstractContent($abstractContent) {
        $this->abstractContent = $abstractContent;
    }

    /**
     * Sets the parser
     * This can be an inline function or a class with a method like:
     * string public function(Response $response);
     * Parser needs to return parsed content and set content type
     * @param object|callable $parser Parser to parse abstract content
     */
    public function setParser($parser) {
        $this->parser = $parser;
    }

    /**
     * Returns the parser
     * @return object|callable Parser
     */
    public function getParser() {
        return $this->parser;
    }

    /**
     * Parses this response
     */
    public function parse() {
        if (is_callable($this->getParser())) {
            $parser = $this->getParser();
            $this->setParsedContent($parser($this));
            return;
        }
        $this->setParsedContent($this->getParser()->parse($this));
    }

    /**
     * Returns the parsed response data
     * @return string Parsed response data
     */
    public function getParsedContent() {
        if (empty($this->parsedContent)) {
            $this->parse();
        }
        return $this->parsedContent;
    }

    /**
     * Sets parsed response data
     * @param string $parsedContent Parsed response data
     */
    public function setParsedContent($parsedContent) {
        $this->parsedContent = $parsedContent;
    }

    /**
     * Sets a header
     * For 'Content-Type' or 'Expires' headers please use
     * setContentType() and setExpirationDate()
     * @param string $key Header key
     * @param string $value Header value
     * @throws ResponseException When trying to set 'Content-Type' or 'Expires' header
     */
    public function setHeader($key, $value) {
        if ($key == 'Content-Type') {
            throw new ResponseException('Please use setContentType()');
        } else if ($key == 'Expires') {
            throw new ResponseException('Please use setExpirationDate()');
        }
        if (empty($value)) {
            unset($this->headers[$key]);
        } else {
            $this->headers[$key] = $value;
        }
    }

    /**
     * Returns a header's value
     * @param string $key Header key
     * @throws ResponseException When trying to get a non-set header
     * @return string Header value
     */
    public function getHeader($key) {
        if (!isset($this->headers[$key])) {
            throw new ResponseException('No such header set');
        }
        return $this->headers[$key];
    }

    /**
     * Returns a list of headers
     * Please note that this does not include 'Content-Type' and 'Expires' headers
     * @return array Key=>value type array
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * Sends this response to browser
     * @throws ResponseException If content type is not set
     */
    public function send() {
        if (
            empty($this->getParsedContent()) ||
            empty($this->getContentType())
        ) {
            $this->parse();
        }
        if (empty($this->getContentType())) {
            throw new ResponseException('Content type is not set');
        }
        header('Content-Type: ' . $this->getContentType());
        if ($this->getExpirationDate()) {
            header('Expires: ' . $this->getExpirationDate()->format('r'));
        }
        foreach ($this->getHeaders() as $key=>$value) {
            header($key . ': ' . $value);
        }
        die($this->getParsedContent());
    }
}
