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
 * System log
 * @copyright    CLOUDREXX CMS - CLOUDREXX AG
 * @author       Michael Ritter <michael.ritter@comvation.com>
 * @package      cloudrexx
 * @subpackage   coremodule_syslog
 * @version      5.0.0
 */

namespace Cx\Core_Modules\SysLog\Model\Entity;

/**
 * Entity for system logs
 * @copyright    CLOUDREXX CMS - CLOUDREXX AG
 * @author       Michael Ritter <michael.ritter@comvation.com>
 * @package      cloudrexx
 * @subpackage   coremodule_syslog
 * @version      5.0.0
 */
class Log extends \Cx\Model\Base\EntityBase {

    /**
     * Information message used for debugging only
     * @var string SEVERITY_INFO
     */
    const SEVERITY_INFO = 'INFO';

    /**
     * Warning message, something is not good, but non-fatal
     * @var string SEVERITY_WARNING
     */
    const SEVERITY_WARNING = 'WARNING';

    /**
     * Fatal, the component that logged this cannot do what it should
     * @var string SEVERITY_FATAL
     */
    const SEVERITY_FATAL = 'FATAL';

    /**
     * @var integer $id
     */
    private $id;

    /**
     * @var datetime $timestamp
     */
    private $timestamp;

    /**
     * @var string $severity
     */
    private $severity;

    /**
     * @var string $message
     */
    private $message;

    /**
     * @var string $data
     */
    private $data;

    /**
     * @var string $logger
     */
    private $logger;

    /**
     * Creates a new log entry
     * @param string $severity Use one of the SEVERITY_* constants
     * @param string $message A short message that describes this entry
     * @param string $data Additional debug data
     */
    public function __construct($severity, $message, $data) {
        $this->setSeverity($severity);
        $this->setMessage($message);
        $this->setData($data);
        $this->setTimestamp(new \DateTime());

        $backtrace = debug_backtrace();
        $callingClassName = $backtrace[3]['class'];
        $callingClassNameParts = explode('\\', $callingClassName);
        $callingComponentType = $callingClassNameParts[1];
        $callingComponentName = $callingClassNameParts[2];
        $this->setLogger($callingComponentType . '/' . $callingComponentName);
    }

    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set timestamp
     *
     * @param datetime $timestamp
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * Get timestamp
     *
     * @return datetime $timestamp
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Set severity
     *
     * @param string $severity
     */
    public function setSeverity($severity)
    {
        $this->severity = $severity;
    }

    /**
     * Get severity
     *
     * @return string $severity
     */
    public function getSeverity()
    {
        return $this->severity;
    }

    /**
     * Set message
     *
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * Get message
     *
     * @return string $message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set data
     *
     * @param string $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Get data
     *
     * @return string $data
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set logger
     *
     * @param string $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get logger
     *
     * @return string $logger
     */
    public function getLogger()
    {
        return $this->logger;
    }
}
