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
 * Uploader
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_upload
 */

namespace Cx\Core_Modules\Upload\Controller;

/**
 * Exceptions thrown by uploader
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_upload
 */
class UploaderException extends \Exception {}

/**
 * Base class for all kinds of uploaders.
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_upload
 */
abstract class Uploader
{
    /**
     * @see Uploader::callbackData
     * @var Array callback data as passed to @link Uploader::callbackData
     */
    protected $callbackData;

    /**
     * @see Uploader::setUploadId
     * @var int this upload's id. 1-based.
     */
    protected $uploadId;

    /**
     * @see Uploader::setJsInstanceName()
     * @var string
     */
    protected $jsInstanceName;

    /**
     * Whether we're handling a backend request.
     * @var bool
     */
    protected $isBackendRequest;

    /**
     * user-defined data assigned with the upload.
     */
    protected $data = null;

    /**
     * @see FormUploader::getFrameXHtml()
     * @var string
     */
    protected $redirectUrl = null;

    /**
     * Set up uploader to only allow one single file to be uploaded
     */
    public function restrictUpload2SingleFile()
    {
        if (!isset($_SESSION['upload']['handlers'][$this->uploadId])) {
            $_SESSION['upload']['handlers'][$this->uploadId] = array();
        }
        // limit upload to 1 file at a time
        \ContrexxJavascript::getInstance()->setVariable('restrictUpload2SingleFile', true, "upload/widget_$this->uploadId");
        $_SESSION['upload']['handlers'][$this->uploadId]['singleFileMode'] = true;
    }

    /**
     * @param boolean $backend whether this is a backend request or not
     */
    public function __construct($backend)
    {
       $this->isBackendRequest = $backend;

       //start session if it's not ready yet
       global $sessionObj;
       if(empty($sessionObj)) { //session hasn't been initialized so far
           $sessionObj = \cmsSession::getInstance();
       }
        }
    /**
     * Set a callback to be called when uploading has finished.
     *
     * The callback will be called with the arguments
     * * $tempPath, containing the path to the folder where the files are
     * * $tempWebPath, containing the web path to the folder where the files are
     * * $data, containing the data set by @link Uploader::setData
     * * $uploadId, containing the id of the current upload
     * * $fileInfos, containing an array ( 'originalFileNames' => array ('currentCleanFileName' => 'fileNameGivenWhenUploaded'))
     *     add a key if you want to pass more file informations (e.g. size, mime type) to the callback!
     * * $response, an UploadResponse object.
     *
     * The callback can either return null if he moves the files himself or
     * { <path_string> , <web_path_string> } if the files should be moved
     *
     * @param Array $callbackData {
     *   <classFilePath>,
     *   <className> | <classReference>,
     *   <functionName>
     * }
     * @param boolean $updateSession if a new callback is set, this will update the
     *   session. defaults to true.
     */
    public function setFinishedCallback($callbackData, $updateSession = true)
    {
        $this->callbackData = $callbackData;
        if($updateSession) {
            //write callback to session
            $_SESSION['upload']['handlers'][$this->uploadId]['callback'] = $this->callbackData;
        }
    }

    /**
     * Used by the factory to set the Url where the User is redirected to after a successful upload
     * , relative to cmsRoot, e.g. "index.php?cmd=test". Mainly for iframe-using uploaders.
     * Not all uploaders may need a redirect, chunked uploading happens without redirect for example.
     * Redirection is triggered via @link Uploader::redirect()
     * @param string $url the url, beginning with ASCMS_PATH_OFFSET or ASCMS_ADMIN_WEB_PATH
     * @param boolean $updateSession if a new url is set, this will update the
     *   session. defaults to true.
     */
    public function setRedirectUrl($url, $updateSession = true) {
        if($updateSession)
            $_SESSION['upload']['handlers'][$this->uploadId]['redirect_url'] = $url;

        global $_CONFIG;
        $this->redirectUrl = /*"http://".$_CONFIG['domainUrl'].*/$url;
    }

    /**
     * Redirects to the url previously set by @link Uploader::setRedirectUrl()
     * @throws UploaderException if redirect url is not set
     */
    protected function redirect() {
        if($this->redirectUrl == null)
            throw new UploaderException('tried to redirect without a redirect url set via Uploader::setRedirectUrl()!');
        \Cx\Core\Csrf\Controller\Csrf::header('Location: ' . $this->redirectUrl);
        die();
    }

    /**
     * Each upload has a unique id. Use this function to set it.
     *
     * @param int $id
     */
    public function setUploadId($id)
    {
        $this->uploadId = $id;
        if (!isset($_SESSION['upload']['handlers'][$this->uploadId])) {
            $_SESSION['upload']['handlers'][$this->uploadId] = array();
        }
        if(isset($_SESSION['upload']['handlers'][$this->uploadId]['callback']))
            $this->callbackData = $_SESSION['upload']['handlers'][$this->uploadId]['callback'];
    }

    /**
     * Returns the id of the current upload
     * @return int
     */
    public function getUploadId()
    {
        return $this->uploadId;
    }

    /**
     * Sets the name used to make the uploader's Javascript object accessible.
     * @param string $name
     */
    public function setJsInstanceName($name) {
        $this->jsInstanceName = $name;
    }

    /**
     * Gets the Name that shall be used for this Uploaders Javascript object.
     * @return string
     */
    protected function getJsInstanceName() {
        return $this->jsInstanceName;
    }

    /**
     * Checks whether the Js Instance name is set.
     * @return boolean
     */
    protected function hasJsInstanceName() {
        return !is_null($this->jsInstanceName);
    }

    /**
     * Takes care of setting the Javascript instance if needed.
     * Sets the placeholder JS_INSTANCE_CODE in $tpl.
     * Uploader templates do have to include this placeholder.
     * This is no nice coding, but it saves us a lot of lines and messing with blocks.
     *
     * @param Object $tpl the template
     * @param string $objectName the object name to assign in the JS part
     */
    protected function handleInstanceBusiness($tpl, $objectName) {
        if($this->hasJsInstanceName())
            $tpl->setVariable('JS_INSTANCE_CODE', "cx.instances.set('".$this->getJsInstanceName()."',".$objectName.",'uploader');");
        else
            $tpl->setVariable('JS_INSTANCE_CODE', '//remark: no instance name set');

        $tpl->setVariable('UPLOAD_ID', $this->uploadId);
    }

    /**
     * Used to set user-defined data assigned to this upload.
     *
     * This data is passed as an argument to the callback set by @link Uploader::setFinishedCallback().
     *
     * @param $data the data
     */
    public function setData($data) {
        $this->data = $data;
        //store data to session
        $_SESSION['upload']['handlers'][$this->uploadId]['data'] = $data;
    }

    /**
     * Used to retrieve user-defined data assigned to this upload.
     */
    public function getData() {
        if($this->data != null) { //$data is set, this means it's up to date
            return $this->data;
        }
        else if(isset($_SESSION['upload']['handlers'][$this->uploadId]['data'])) //try to recover data from session
        {
            $this->data = $_SESSION['upload']['handlers'][$this->uploadId]['data'];
            return $this->data; //cache for future gets
        }
        else { //nothing set yet, return null
            return null;
        }
    }

    /**
     * Checks $fileCount against $_SESSION[upload][handlers][x][uploadedCount].
     * Takes appropriate action (calls callback if they equal).
     * @param integer $fileCount files in current uploado
     */
    public function handleCallback($fileCount) {
        if($fileCount == 1) { //one file, all done.
            $this->notifyCallback();
        }
        else {
            if(!isset($_SESSION['upload']['handlers'][$this->uploadId]['uploadedCount'])) { //multiple files, first file
                $_SESSION['upload']['handlers'][$this->uploadId]['uploadedCount'] = 1;
            }
            else {
                $count = $_SESSION['upload']['handlers'][$this->uploadId]['uploadedCount'] + 1;
                if($count == $fileCount) { //all files uploaded
                    unset($_SESSION['upload']['handlers'][$this->uploadId]['uploadedCount']);
                    $this->notifyCallback();
                }
                else {
                    $_SESSION['upload']['handlers'][$this->uploadId]['uploadedCount'] = $count;
                }
            }
        }
    }

    /**
     * Notifies the callback. Invoked on upload completion.
     */
    public function notifyCallback()
    {

        //temporary path where files were uploaded
        $tempDir = '/upload_'.$this->uploadId;
        $tempPath = $_SESSION->getTempPath().$tempDir;
        $tempWebPath = $_SESSION->getWebTempPath().$tempDir;

        //we're going to call the callbck, so the data is not needed anymore
        //well... not quite sure. need it again in contact form.
//TODO: code session cleanup properly if time.
        //$this->cleanupCallbackData();

        $classFile = $this->callbackData[0];
        //call the callback, get return code
        if($classFile != null) {
            if(!file_exists($classFile))
                throw new UploaderException("Class file '$classFile' specified for callback does not exist!");
            require_once $this->callbackData[0];
        }

        $originalFileNames = array();
        if (isset($_SESSION['upload']['handlers'][$this->uploadId]['originalFileNames'])) {
            $originalFileNames = $_SESSION['upload']['handlers'][$this->uploadId]['originalFileNames'];
        }
        //various file infos are passed via this array
        $fileInfos = array(
            'originalFileNames' => $originalFileNames
        );

        $response = null;
        //the response data.
        if(isset($_SESSION['upload']['handlers'][$this->uploadId]['response_data']))
            $response = UploadResponse::fromSession($_SESSION['upload']['handlers'][$this->uploadId]['response_data']);
        else
            $response = new UploadResponse();

        $ret = call_user_func(array($this->callbackData[1],$this->callbackData[2]),$tempPath,$tempWebPath,$this->getData(), $this->uploadId, $fileInfos, $response);

        //clean up session: we do no longer need the array with the original file names
        unset($_SESSION['upload']['handlers'][$this->uploadId]['originalFileNames']);
        //same goes for the data
        //if(isset($_SESSION['upload']['handlers'][$this->uploadId]['data']))
// TODO: unset this when closing the uploader dialog, but not before
//            unset($_SESSION['upload']['handlers'][$this->uploadId]['data']);

        if (\Cx\Lib\FileSystem\FileSystem::exists($tempWebPath)) {
            //the callback could have returned a path where he wants the files moved to
            // check that $ret[1] is not empty is VERY important!!!
            if(!is_null($ret) && !empty($ret[1])) { //we need to move the files
                //gather target information
                $path = pathinfo($ret[0]);
                $pathWeb = pathinfo($ret[1]);
                //make sure the target directory is writable
                \Cx\Lib\FileSystem\FileSystem::makeWritable($pathWeb['dirname'].'/'.$path['basename']);

                //revert $path and $pathWeb to whole path instead of pathinfo path for copying
                $path = $path['dirname'].'/'.$path['basename'].'/';
                $pathWeb = $pathWeb['dirname'].'/'.$pathWeb['basename'].'/';

                //trailing slash needed for File-class calls
                $tempPath .= '/';
                $tempWebPath .= '/';

                //move everything uploaded to target dir
                $h = opendir($tempPath);
                $im = new \ImageManager();
                while(false != ($f = readdir($h))) {
                    //skip . and ..
                    if($f == '.' || $f == '..')
                        continue;

                    //TODO: if return value = 'error' => react
                    \Cx\Lib\FileSystem\FileSystem::move($tempWebPath.$f, $pathWeb.$f, true);
                    if($im->_isImage($path.$f)){
                        $im->_createThumb($path, $pathWeb, $f);
                    }
                    $response->increaseUploadedFilesCount();
                }
                closedir($h);
            } else {
// TODO: what now????
            }

            //delete the folder
            \Cx\Lib\FileSystem\FileSystem::delete_folder($tempWebPath, true);
        } else {
// TODO: output error message to user that no files had been uploaded!!!
        }

        $response->uploadFinished();
        $_SESSION['upload']['handlers'][$this->uploadId]['response_data'] = $response->toSessionValue();
    }

    /**
     * Cleans up the session - unsets the callback data stored for this upload
     */
    protected function cleanupCallbackData() {

        unset($_SESSION['upload']['handlers'][$this->uploadId]['callback']);
        $_SESSION->cleanTempPaths();
    }

    /**
     * Implement to handle upload requests.
     *
     * Call @link Uploader::notifyCallback() from this method if the upload is finished.
     */
    abstract public function handleRequest();

    /**
     * Implement to return the XHtml needed to display the uploader.
     *
     * @return string XHtml-Code for the uploader
     */
    abstract public function getXHtml();

    /**
     * Gets the correct upload path
     * Handles the section/cmd naming differences and location of index.php.
     *
     * @param string $type the uploadType to specify
     * @return string the path, uploadId, uploadType, cmd/section and act/cmd set as get parameters.
     */
    protected function getUploadPath($type)
    {
        $uploadPath = '';
        if ($this->isBackendRequest) {
            $uploadPath = ASCMS_ADMIN_WEB_PATH.'/index.php?cmd=Upload&act=upload';
        } else {
            $url = clone \Env::get('cx')->getRequest()->getUrl();
            $url->removeAllParams();
            $url->setParams(array(
                'section' => 'Upload',
                'cmd' => 'upload',
            ));
            $uploadPath = (string) $url;
        }
        $uploadPath .= '&uploadId='.$this->uploadId.'&uploadType='.$type;
        return $uploadPath;
    }

    /**
     * Add a chunk to a file. Creates the file on first chunk, appends else.
     *
     * @param string $fileName upload name
     * @param int $chunk current chunk's number
     * @param int $chunks total chunks
     * @throws UploaderException thrown if upload becomes unusable
     */
    protected function addChunk($fileName, $chunk, $chunks)
    {

        //get a writable directory
        $tempPath = $_SESSION->getTempPath();
        $webTempPath = $_SESSION->getWebTempPath();
        $dirName = 'upload_'.$this->uploadId;

        $targetDir = $tempPath.'/'.$dirName;
        if(!file_exists($targetDir))
            \Cx\Lib\FileSystem\FileSystem::make_folder($webTempPath.'/'.$dirName);

        $cleanupTargetDir = false; // Remove old files
        $maxFileAge = 60 * 60; // Temp file age in seconds

        // 5 minutes execution time
        @set_time_limit(5 * 60);

        // remember the "raw" file name, we want to store all original
        // file names in the session.
        $originalFileName = $fileName;

        // Clean the fileName for security reasons
        // we're using a-zA-Z0-9 instead of \w because of the umlauts.
        // linux excludes them from \w, windows includes them. we do not want different
        // behaviours on different operating systems.
        $fileName = preg_replace('/[^a-zA-Z0-9\._-]+/', '', $fileName);

        //try to retrieve session file name for chunked uploads
        if ($chunk > 0) {
            if(isset($_SESSION['upload']['handlers'][$this->uploadId]['fileName']))
                $fileName = $_SESSION['upload']['handlers'][$this->uploadId]['fileName'];
            else
                throw new UploaderException('Session lost.');
        }
        else { //first chunk, store original file name in session
            $originalFileNames = array();
            if(isset($_SESSION['upload']['handlers'][$this->uploadId]['originalFileNames']))
                $originalFileNames = $_SESSION['upload']['handlers'][$this->uploadId]['originalFileNames'];
            $originalFileNames[$fileName] = $originalFileName;
            $_SESSION['upload']['handlers'][$this->uploadId]['originalFileNames'] = $originalFileNames;
        }

        // Make sure the fileName is unique (for chunked uploads only on first chunk, since we're using the same name)
        if (file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName) && $chunk == 0) {
            $ext = strrpos($fileName, '.');
            $fileName_a = substr($fileName, 0, $ext);
            $fileName_b = substr($fileName, $ext);

            $count = 1;
            while (file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName_a . '_' . $count . $fileName_b))
                $count++;

            $fileName = $fileName_a . '_' . $count . $fileName_b;
        }
        //$fileName contains now the name we'll use for the whole upload process, so store it.
        $_SESSION['upload']['handlers'][$this->uploadId]['fileName'] = $fileName;

        // Remove old temp files
        if (is_dir($targetDir) && ($dir = opendir($targetDir))) {
            while (($file = readdir($dir)) !== false) {
                $filePath = $targetDir . DIRECTORY_SEPARATOR . $file;

                // Remove temp files if they are older than the max age
                if (preg_match('/\\.tmp$/', $file) && (filemtime($filePath) < time() - $maxFileAge))
                    @unlink($filePath);
            }

            closedir($dir);
        } else
            throw new UploaderException('Failed to open temp directory.');

        $contentType = '';
        // Look for the content type header
        if (isset($_SERVER["HTTP_CONTENT_TYPE"]))
            $contentType = $_SERVER["HTTP_CONTENT_TYPE"];

        if (isset($_SERVER["CONTENT_TYPE"]))
            $contentType = $_SERVER["CONTENT_TYPE"];

        if (strpos($contentType, "multipart") !== false) {
            if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
                // Open temp file
                $out = fopen($targetDir . DIRECTORY_SEPARATOR . $fileName, $chunk == 0 ? "wb" : "ab");
                if ($out) {
                    // Read binary input stream and append it to temp file
                    $in = fopen($_FILES['file']['tmp_name'], "rb");

                    if ($in) {
                        while ($buff = fread($in, 4096))
                            fwrite($out, $buff);
                    } else
                        throw new UploaderException('Failed to open input stream.');

                    fclose($out);
                    unlink($_FILES['file']['tmp_name']);
                } else
                    throw new UploaderException('Failed to open output stream.');
            } else
                throw new UploaderException('Failed to move uploaded file.');
        } else {
            // Open temp file
            $out = fopen($targetDir . DIRECTORY_SEPARATOR . $fileName, $chunk == 0 ? "wb" : "ab");
            if ($out) {
                // Read binary input stream and append it to temp file
                $in = fopen("php://input", "rb");

                if ($in) {
                    while ($buff = fread($in, 4096))
                        fwrite($out, $buff);
                } else
                    throw new UploaderException('Failed to open input stream.');

                fclose($out);
            } else {
                throw new UploaderException('Failed to open output stream.');
            }
        }

        // Send HTTP header to force the browser to send the next file-chunt
        // through a new connection. File-chunks that are sent through the
        // same connection get dropped by the web-server.
        header('Connection: close');
    }

    protected function addHarmfulFileToResponse($fileName) {
        global $_ARRAYLANG;

        $response = null;
        //the response data.
        if(isset($_SESSION['upload']['handlers'][$this->uploadId]['response_data']))
            $response = UploadResponse::fromSession($_SESSION['upload']['handlers'][$this->uploadId]['response_data']);
        else
            $response = new UploadResponse();

        $response->addMessage(UploadResponse::STATUS_ERROR, $_ARRAYLANG['TXT_CORE_EXTENSION_NOT_ALLOWED'], $fileName);
        $_SESSION['upload']['handlers'][$this->uploadId]['response_data'] = $response->toSessionValue();
    }
}
