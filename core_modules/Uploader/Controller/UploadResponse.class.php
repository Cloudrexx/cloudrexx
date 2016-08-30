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
 * Class UploadResponse
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_uploader
 */

namespace Cx\Core_Modules\Uploader\Controller;

/**
 * UploadResponses result from an upload request.
 * They carry information about problems concerning uploaded files.
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_uploader
 */
class UploadResponse {

    /**
     * @var array array( array( 'status' => 'ok'|'error'..., 'message' => string, 'file' => string ) )
     */
    protected $logs = array();

    /**
     * Define the normal status
     */
    const STATUS_OK = 0;

    /**
     * Define the warning status
     */
    const STATUS_WARNING = 1;

    /**
     * Define the error status
     */
    const STATUS_ERROR = 2;

    /**
     * Define the info status
     */
    const STATUS_INFO = 3;

    /**
     * The worstStatus is indentifying status of the upload files.
     * @var integer
     */
    protected $worstStatus = 0;

    /**
     *
     * @var array
     */
    protected $statusTexts = array(
        self::STATUS_OK => 'ok',
        self::STATUS_WARNING => 'warning',
        self::STATUS_ERROR => 'error',
        self::STATUS_INFO => 'info'
    );

    public function __construct() {

    }

    /**
     * Adds a log message concerning a file to the response.
     *
     * @param string $status  one of UploadResponse::STATUS_(OK|WARNING|ERROR|INFO)
     * @param string $message message
     */
    public function addMessage($status, $message) {
        $this->logs[] = array(
            'status' => $this->statusTexts[$status],
            'message' => $message,
        );
        if ($status > $this->worstStatus) {
            $this->worstStatus = $status;
        }
    }

    /**
     * get the response
     *
     * @return array
     */
    public function getResponse() {
        return array(
            'status' => $this->statusTexts[$this->worstStatus],
            'messages' => $this->logs,
        );
    }

    /**
     * get the worst status
     *
     * @return integer
     */
    public function getWorstStatus() {
        return $this->worstStatus;
    }

}
