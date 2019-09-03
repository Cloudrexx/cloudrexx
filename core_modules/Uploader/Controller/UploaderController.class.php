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
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Tobias Schmoker <tobias.schmoker@comvation.com>
 *              Robin Glauser <robin.glauser@comvation.com>
 * @package     cloudrexx
 * @subpackage  coremodule_uploader
 */

namespace Cx\Core_Modules\Uploader\Controller;

use Cx\Core\Core\Controller\Cx;
use Cx\Lib\FileSystem\FileSystem;

/**
 * UploaderExceptions thrown by uploader
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 */
class UploaderException extends \Exception {

}

define('PLUPLOAD_MOVE_ERR', 103);
define('PLUPLOAD_INPUT_ERR', 101);
define('PLUPLOAD_OUTPUT_ERR', 102);
define('PLUPLOAD_TMPDIR_ERR', 100);
define('PLUPLOAD_TYPE_ERR', 104);
define('PLUPLOAD_UNKNOWN_ERR', 111);
define('PLUPLOAD_SECURITY_ERR', 105);

/**
 * Class UploaderController
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Tobias Schmoker <tobias.schmoker@comvation.com>
 *              Robin Glauser <robin.glauser@comvation.com>
 */
class UploaderController {

    /**
     * Configuration array
     *
     * @var array
     */
    public static $conf;

    /**
     * Error id
     *
     * @var int
     */
    protected static $_error = null;

    /**
     * List of errors
     *
     * @var array
     */
    protected static $_errors = array(
        PLUPLOAD_MOVE_ERR => 'Failed to move uploaded file.',
        PLUPLOAD_INPUT_ERR => 'Failed to open input stream.',
        PLUPLOAD_OUTPUT_ERR => 'Failed to open output stream.',
        PLUPLOAD_TMPDIR_ERR => 'Failed to open temp directory.',
        PLUPLOAD_TYPE_ERR => 'File type not allowed.',
        PLUPLOAD_UNKNOWN_ERR => 'Failed due to unknown error.',
        PLUPLOAD_SECURITY_ERR => 'File didn\'t pass security check.'
    );

    /**
     * Retrieve the error code
     *
     * @return int Error code
     */
    static function getErrorCode() {
        if (!self::$_error) {
            return null;
        }

        if (!isset(self::$_errors[self::$_error])) {
            return PLUPLOAD_UNKNOWN_ERR;
        }

        return self::$_error;
    }

    /**
     * Retrieve the error message
     *
     * @return string Error message
     */
    static function getErrorMessage() {
        if ($code = self::getErrorCode()) {
            return self::$_errors[$code];
        }
        return '';
    }

    /**
     * Handles the upload request.
     *
     * @param array $conf
     *
     * @return array|bool
     */
    static function handleRequest($conf = array()) {

        $cx = Cx::instanciate();
        $session = $cx->getComponent('Session')->getSession();
        // 5 minutes execution time
        @set_time_limit(5 * 60);

        self::$_error = null; // start fresh

        $conf = self::$conf = array_merge(array(
            'file_data_name' => 'file',
            'tmp_dir' => $session->getTempPath(),
            'target_dir' => $session->getTempPath(),
            'cleanup' => true,
            'max_file_age' => 5 * 3600,
            'chunk' => isset($_REQUEST['chunk']) ? intval($_REQUEST['chunk']) : 0,
            'chunks' => isset($_REQUEST['chunks']) ? intval($_REQUEST['chunks']) : 0,
            'fileName' => isset($_REQUEST['name']) ? $_REQUEST['name'] : false,
            'allow_extensions' => false,
            'delay' => 0,
            'cb_sanitizeFileName' => array(__CLASS__, 'sanitizeFileName'),
            'cb_check_file' => false,
                ), $conf);

        try {
            if (!$conf['fileName']) {
                if (!empty($_FILES)) {
                    $conf['fileName'] = $_FILES[$conf['file_data_name']]['name'];
                } else {
                    throw new UploaderException('', PLUPLOAD_INPUT_ERR);
                }
            }

            // Cleanup outdated temp files and folders
            if ($conf['cleanup']) {
                self::cleanup();
            }

            // Fake network congestion
            if ($conf['delay']) {
                usleep($conf['delay']);
            }

            // callback function for sanitizie filename
            if (is_callable($conf['cb_sanitizeFileName'])) {
                $fileName = call_user_func($conf['cb_sanitizeFileName'], $conf['fileName']);
            } else {
                $fileName = $conf['fileName'];
            }

            if ($conf['allow_extensions']) {
                if (is_string($conf['allow_extensions'])) {
                    $conf['allow_extensions'] = explode(',', $conf['allow_extensions']);
                }
                if (!in_array(strtolower(pathinfo($fileName, PATHINFO_EXTENSION)), $conf['allow_extensions'])) {
                    throw new UploaderException('', PLUPLOAD_TYPE_ERR);
                }
            }

            $file_path = rtrim($conf['tmp_dir'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;
            $tmp_path = $file_path . '.part';

            // Write file or chunk to appropriate temp location
            if ($conf['chunks']) {
                self::writeFileTo("$file_path.dir.part" . DIRECTORY_SEPARATOR . $conf['chunk']);

                // Check if all chunks already uploaded
                if ($conf['chunk'] == $conf['chunks'] - 1) {
                    self::writeChunksToFile("$file_path.dir.part", $tmp_path);
                }
            } else {
                self::writeFileTo($tmp_path);
            }

            // Upload complete write a temp file to the final destination
            if (!$conf['chunks'] || $conf['chunk'] == $conf['chunks'] - 1) {
                if (is_callable($conf['cb_check_file']) && !call_user_func($conf['cb_check_file'], $tmp_path)) {
                    @unlink($tmp_path);
                    throw new UploaderException('', PLUPLOAD_SECURITY_ERR);
                }

                $new_path = $conf['target_dir'] . $fileName;
                $new_path_default = $conf['target_dir'] . $fileName;
                $i = 1;
                while (file_exists($new_path)){
                    $new_path = pathinfo($new_path_default, PATHINFO_DIRNAME).'/'. pathinfo($new_path_default, PATHINFO_FILENAME).'_'.$i.'.'.pathinfo($new_path_default, PATHINFO_EXTENSION);
                    $i++;
                }

                \Cx\Lib\FileSystem\FileSystem::move($tmp_path, $new_path, true);

                // verify orientation of images
                $im = new \ImageManager();
                if ($im->_isImage($new_path)) {
                    // Fix an image orientation
                    $im->fixImageOrientation($new_path);
                }

                return array(
                    'name' => $fileName,
                    'path' => $file_path,
                    'size' => filesize($file_path)
                );
            }

            // ok so far
            return true;
        } catch (UploaderException $ex) {
            self::$_error = $ex->getCode();
            return array('error' => $ex->getCode());
        }
    }

    /**
     * Writes either a multipart/form-data message or a binary stream
     * to the specified file.
     *
     * @throws UploaderException In case of error generates exception with the corresponding code
     *
     * @param string $file_path The path to write the file to
     * @param string [$file_data_name='file'] The name of the multipart field
     */
    static function writeFileTo($file_path, $file_data_name = false) {
        if (!$file_data_name) {
            $file_data_name = self::$conf['file_data_name'];
        }

        $base_dir = dirname($file_path);
        if (!file_exists($base_dir) && !@mkdir($base_dir, 0777, true)) {
            throw new UploaderException('', PLUPLOAD_TMPDIR_ERR);
        }

        if (!empty($_FILES) && isset($_FILES[$file_data_name])) {
            if ($_FILES[$file_data_name]['error'] || !is_uploaded_file($_FILES[$file_data_name]['tmp_name'])) {
                throw new UploaderException('', PLUPLOAD_MOVE_ERR);
            }
            move_uploaded_file($_FILES[$file_data_name]['tmp_name'], $file_path);
        } else {
            // Handle binary streams
            if (!$in = @fopen('php://input', 'rb')) {
                throw new UploaderException('', PLUPLOAD_INPUT_ERR);
            }

            if (!$out = @fopen($file_path, 'wb')) {
                throw new UploaderException('', PLUPLOAD_OUTPUT_ERR);
            }

            while ($buff = fread($in, 4096)) {
                fwrite($out, $buff);
            }

            @fclose($out);
            @fclose($in);
        }
    }

    /**
     * Combine chunks from the specified folder into the single file.
     *
     * @throws UploaderException In case of error generates exception with the corresponding code
     *
     * @param string $chunk_dir Temp directory with the chunks
     * @param string $file_path The file to write the chunks to
     */
    static function writeChunksToFile($chunk_dir, $file_path) {
        if (!$out = @fopen($file_path, 'wb')) {
            throw new UploaderException('', PLUPLOAD_OUTPUT_ERR);
        }

        for ($i = 0; $i < self::$conf['chunks']; $i++) {
            $chunk_path = $chunk_dir . DIRECTORY_SEPARATOR . $i;
            if (!file_exists($chunk_path)) {
                throw new UploaderException('', PLUPLOAD_MOVE_ERR);
            }

            if (!$in = @fopen($chunk_path, 'rb')) {
                throw new UploaderException('', PLUPLOAD_INPUT_ERR);
            }

            while ($buff = fread($in, 4096)) {
                fwrite($out, $buff);
            }
            @fclose($in);

            // chunk is not required anymore
            @unlink($chunk_path);
        }
        @fclose($out);

        // Cleanup
        self::rrmdir($chunk_dir);
    }

    /**
     * Send static no caching header
     */
    static function noCacheHeaders() {
        // Make sure this file is not cached (as it might happen on iOS devices, for example)
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
    }

    /**
     * Cleanup method
     */
    protected static function cleanup() {
        // Remove old temp files
        if (file_exists(self::$conf['tmp_dir'])) {
            foreach (glob(self::$conf['tmp_dir'] . '/*.part') as $tmpFile) {
                if (time() - filemtime($tmpFile) < self::$conf['max_file_age']
                ) {
                    continue;
                }
                if (is_dir($tmpFile)) {
                    self::rrmdir($tmpFile);
                } else {
                    @unlink($tmpFile);
                }
            }
        }
    }

    /**
     * Sanitizes the filename by adding a .txt file extension to files with
     * bad extensions and by removing strange characters.
     *
     * @param string $filename The filename to be sanitized
     *
     * @return string The sanitized filename
     */
    public static function sanitizeFileName($filename) {
        $filename = FileSystem::replaceCharacters(filter_var($filename,FILTER_SANITIZE_URL));
        $fileInfo = pathinfo($filename);
        if (empty($filename)){
            $filename = 'file'.date('Y-m-d H:i:s');
        }
        if (!isset($fileInfo['extension'])){
            $filename = $filename.'.txt';
        }
        if (!\FWValidator::is_file_ending_harmless(
            $filename
        )){
            $filename = $filename.'.txt';
        }
        return $filename;
    }

    /**
     * Concise way to recursively remove a directory
     * http://www.php.net/manual/en/function.rmdir.php#108113
     *
     * @param string $dir Directory to remove
     */
    protected static function rrmdir($dir) {
        foreach (glob($dir . '/*') as $file) {
            if (is_dir($file)) {
                self::rrmdir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dir);
    }
}
