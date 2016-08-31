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
 * uploadFactory
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_upload
 */

namespace Cx\Core_Modules\Upload\Controller;
/**
 * UploadFactoryException
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_upload
 */
class UploadFactoryException extends \Exception {}

/**
 * Upload Factory. Creates the right upload classes and initializes them.
 * This is a Singleton, use @link UploadFactory::getInstance() to work with.
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_upload
 */
class UploadFactory
{
    //singleton functionality: instance
    static private $instance = null;
    //singleton functionality: instance getter
    static public function getInstance()
    {
        if(null == self::$instance)
            self::$instance = new self;
        return self::$instance;
    }

    /**
     * Holds prefixes of all uploaders enabled & available.
     * @var array
     */
    protected $uploaders = array();
    /**
     * Whether we're in a backed request currently.
     * @var boolean
     */
    protected $isBackendRequest;
    /**
     * Whether the advanced uploaders should be displayed.
     * @var boolean
     */
    protected $isAdvancedUploadingEnabled;

    /**
     * This is a constructor. I thought you would recognize.
     */
    protected function __construct()
    {
        //we need a session, check if it's initialized
        $this->initSession();
        //collect the settings that interest us
        $this->collectSettings();
        //now we can work. determine interesting uploaders
        $this->uploaders = $this->collectEnabledUploaders();
    }

    protected function initSession() {
        global $sessionObj;
        if(empty($sessionObj)) { //session hasn't been initialized so far
            $sessionObj = \cmsSession::getInstance();
        }
        if (!isset($_SESSION['upload'])) {
            $_SESSION['upload'] = array();
            $_SESSION['upload']['handlers'] = array();
        }
    }

    /**
     * Sets $this->isBackendRequest and
     * $this->isAdvancedUploadingEnabled.
     */
    protected function collectSettings() {
        global $objInit;
        global $_CONFIG;
        $this->isBackendRequest = $objInit->mode == "backend";
        if($this->isBackendRequest)
            $this->isAdvancedUploadingEnabled = $_CONFIG['advancedUploadBackend'] == 'on';
        else
            $this->isAdvancedUploadingEnabled = $_CONFIG['advancedUploadFrontend'] == 'on';
    }

    /**
     * Collects a list of enabled Uploaders
     */
    protected function collectEnabledUploaders() {
        $available = $this->collectAvailableUploaders();
        $enabled = array();

        if($this->isAdvancedUploadingEnabled) //all uploaders are wanted
            return $available;
        else if(!in_array('Form',$available)) //we do not have an uploader that is applicable
            throw new UploadFactoryException('advanced uploaders disabled, but no formUploader found!');

        //no advanced uploading, only formUploader wanted
        return array('Form');
    }

    /**
     * Collects a list of available uploaders and returns an array with their prefixes.
     * E.g. plUploader and formUploader result into ['pl','form'].
     *
     * @return array the available uploaders
     * @throws UploadFactoryException if no uploaders are found
     */
    protected function collectAvailableUploaders()
    {
        $uploaders = array();

        //check which modules are installed
        $uploaderFolder = ASCMS_CORE_MODULE_PATH.'/Upload/Controller/';
        $h = opendir($uploaderFolder);
        while(false !== ($f = readdir($h))) {
            $len = strlen($f);
            //'Uploader.class.php' has length 18

            if($len > 18 && substr($f,$len-18) == 'Uploader.class.php') { //it's an uploader class
                array_push($uploaders,(substr($f,0,$len-18)));
            }
        }

        if(count($uploaders) == 0) //no uploaders found. bad.
            throw new UploadFactoryException('could not find any uploaders!');

        return $uploaders;
    }

    /**
     *
     */

    /**
     * Looks at the request parameters and returns the right Upload-derivate.
     * (e.g. jumpUpload, plUpload)
     *
     * @param string $typeHint which type of uploader to return if not specified in request. optional.
     * @return Upload instance of corresponding Upload-derivate
     */
    public function uploaderFromRequest($typeHint = null)
    {
        //determine which type of upload is happening
        $type = '';
        if(isset($_REQUEST['uploadType'])) {
            $type = $_REQUEST['uploadType'];
        }
        else {
            if($typeHint)
                $type=$typeHint;
        }

        //determine the upload id
        $id = 0;
        if(isset($_REQUEST['uploadId'])) {
            $id = intval($_REQUEST['uploadId']);
        }
        //handle invalid ids
        if($id <= 0)
            throw new UploadFactoryException('Invalid upload id.');
        //determine the callback on finishing
        $onFinished = null;

        if(isset($_SESSION['upload']['handlers'][$id]['callback'])) {
            $onFinished = $_SESSION['upload']['handlers'][$id]['callback'];
        }
        else { //no callback found
            throw new UploadFactoryException('No callback specified for id '.$id.'.');
        }

        $theUploader = $this->uploaderFromType($type);
        if(!$theUploader) //wrong type
            throw new UploadFactoryException('Unknown Uploader type "'.$type.'".');

        //the uploader has to know what to do if the download is finished
        $theUploader->setFinishedCallback($onFinished, false);

        $theUploader->setUploadId($id);

        $this->setRedirectUrl($theUploader, $id);

        return $theUploader;
    }

    /**
     * Creates a new uploader.
     * @param string $type e.g. 'pl'
     * @param integer $id the upload id. optional.
     * @param boolean $restrictUpload2SingleFile This optional parameter can be used to
     *                                           restrict the upload-process to only allow
     *                                           one single file to be uploaded. Defaults
     *                                           to FALSE.
     */
    public function newUploader($type, $id=0, $restrictUpload2SingleFile = false) {
        $theUploader = $this->uploaderFromType($type);
        if(!$theUploader) //invalid $type
            return null;

        if($id == 0) { //no explicitly specified id; create a new one
            //generate a unique upload id & increment the upload id counter
            if(!isset($_SESSION['upload']['currentid']))
                $_SESSION['upload']['currentid'] = 1;
            $curId = $_SESSION['upload']['currentid'];
            $theUploader->setUploadId($curId);
            $_SESSION['upload']['currentid'] = $curId + 1;
        }
        else {
            $theUploader->setUploadId($id);
            $this->setRedirectUrl($theUploader, $id);
        }
        if ($restrictUpload2SingleFile) {
            $theUploader->restrictUpload2SingleFile();
        }

        return $theUploader;
    }

    /**
     * Creates a new Folder Widget instance.
     * @param string $folder The folder whose contents you want to display
     * @return FolderWidget
     */
    public function newFolderWidget($folder) {
      return $this->createFolderWidget($folder);
    }

    /**
     * Gets a folder widget by the id specified in the request.
     * @return FolderWidget
     */
    public function folderWidgetFromRequest()
    {
        $id = intval($_REQUEST['folderWidgetId']);
        if($id>0 && isset($_SESSION['upload']['folder_widgets'][$id]['path'])) {
            $path = $_SESSION['upload']['folder_widgets'][$id]['path'];
            return $this->createFolderWidget($path, $id);
        }
        else {
            throw new UploadFactoryException("No folder path found for widget id '$id'.");
        }
    }

    /**
     * Creates a new FolderWidget and initializes it's properties.
     * @param string folder
     * @paran integer id
     */
    protected function createFolderWidget($folder, $id = 0) {
        $theWidget = new FolderWidget();
        $theWidget->setFolder($folder);
        $theWidget->setBackendRequest($this->isBackendRequest);

        if($id == 0) { //new instance, handle initializing
            $id = 1;
            if(!isset($_SESSION['upload']['folder_widget_current_id'])) {
                $_SESSION['upload']['folder_widget_current_id'] = 1;
            } else {
                $_SESSION['upload']['folder_widget_current_id'] += 1;
                $id = $_SESSION['upload']['folder_widget_current_id'];
            }

            if (!isset($_SESSION['upload']['folder_widgets'])) {
                $_SESSION['upload']['folder_widgets'] = array();
            }
            $_SESSION['upload']['folder_widgets'][$id] = array();
            $_SESSION['upload']['folder_widgets'][$id]['path'] = $folder;
            $theWidget->setId($id);
        }

        return $theWidget;
    }

    /**
     * Checks for set redirect url in session and applicates to $uploader if specified
     * @param Uploader $uploader
     * @param integer $uploadId
     */
    protected function setRedirectUrl($uploader, $uploadId) {
        //some uploads may have a redirect url set
        $key = 'redirect_url';
        $redirectUrl = null;
        if(isset($_SESSION['upload']['handlers'][$uploadId][$key]))
            $redirectUrl = $_SESSION['upload']['handlers'][$uploadId][$key];
        if($redirectUrl) {
            $uploader->setRedirectUrl($redirectUrl);
        }
    }

    /**
     * Creates an Uploader instance from the type given
     *
     * @param string $type 'pl' | 'jump' | 'form' | 'combo' ...
     */
    protected function uploaderFromType($type)
    {
        $theUploader = null;
        switch($type) {
            case 'pl': //pluploader
                $theUploader = new PlUploader($this->isBackendRequest);
                break;

            case 'jump': //jumploader
                $theUploader = new JumpUploader($this->isBackendRequest);
                break;

            case 'form': //html file input
                $theUploader = new FormUploader($this->isBackendRequest);
                break;

            case 'combo': //combined version of all uploaders
                $theUploader = new ComboUploader($this->isBackendRequest);
                $theUploader->setEnabledUploaders($this->uploaders);
                break;

            case 'exposedCombo': //combined version of all uploaders, features modal dialog
                $theUploader = new ExposedComboUploader($this->isBackendRequest);
                $theUploader->setEnabledUploaders($this->uploaders);
                break;

        }
        return $theUploader;
    }
}
