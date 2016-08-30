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
 * FolderWidget
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_upload
 */

namespace Cx\Core_Modules\Upload\Controller;

/**
 * FolderWidgetException
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_upload
 */
class FolderWidgetException extends \Exception {}

/**
 * A folder widget (obviously). Use this to display a list of the files contained in a
 * certain folder and let the user modify them (currently only deletion possible).
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_upload
 * @todo does not use filemanager - deleting won't work in folders without the needed permissions
 */
class FolderWidget {
    protected $folder; //the folder we are monitoring
    protected $id; //the unique widget identifier
    protected $isBackendRequest;

    public function setBackendRequest($backendRequest) {
        $this->isBackendRequest = $backendRequest;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function setFolder($folder) {
        $this->folder = $folder;

    }

    /**
     * Gets the XHTML to display the widget.
     * @param string $containerSelector a jQuery selector defining the element
     *                                  where the widget should be put into.
     */
    public function getXhtml($containerSelector, $instanceName)
    {
        \JS::registerJS('core_modules/Upload/js/folderWidget.js');

        $tpl = new \Cx\Core\Html\Sigma(ASCMS_CORE_MODULE_PATH.'/Upload/template/');
        $tpl->setErrorHandling(PEAR_ERROR_DIE);

        $tpl->loadTemplateFile('folderWidget.html');
        $tpl->setVariable('ID', $this->id);

        //from where the combouploader gets the code on an uploader switch
        $cmdOrSection = $this->isBackendRequest ? 'cmd' : 'section';
        $actOrCmd = $this->isBackendRequest ? 'act' : 'cmd';
        $refreshUrl = ($this->isBackendRequest ? ASCMS_ADMIN_WEB_PATH : ASCMS_PATH_OFFSET).\Env::get('virtualLanguageDirectory').'/index.php?'.$cmdOrSection.'=Upload&'.$actOrCmd.'=refreshFolder';
        $deleteUrl = ($this->isBackendRequest ? ASCMS_ADMIN_WEB_PATH : ASCMS_PATH_OFFSET).\Env::get('virtualLanguageDirectory').'/index.php?'.$cmdOrSection.'=Upload&'.$actOrCmd.'=deleteFile';

        \ContrexxJavascript::getInstance()->setVariable(array(
                'refreshUrl' => $refreshUrl,
                'deleteUrl' => $deleteUrl,
                'files' => $this->getFilesJSON(),
                'containerSelector' => $containerSelector,
                'instanceName' => $instanceName
            ),
            'upload/folderWidget_'.$this->id
        );

        return $tpl->get();
    }

    /**
     * @return string json-encoded array of all filenames
     */
    public function getFilesJSON() {
      return json_encode($this->getFiles());
    }

    /**
     * @return array names of all files
     */
    protected function getFiles() {
        $arrFileNames = array();
        //move everything uploaded to target dir
        if(!file_exists($this->folder))
            throw  new FolderWidgetException("could not find my directory '".$this->folder."' to list files");
        $h = opendir($this->folder);
        while(false !== ($f = readdir($h))) {
            // skip folders and thumbnails
            if($f == '.' || $f == '..' || preg_match("/(?:\.(?:thumb_thumbnail|thumb_medium|thumb_large)\.[^.]+$)|(?:\.thumb)$/i", $f))
                continue;

            array_push($arrFileNames, $f);
        }
        closedir($h);
        return $arrFileNames;
    }

    /**
     * Deletes a file in the folder if it exists.
     * @param string $file the file's name
     * @return boolean whether the file was found and deleted
     */
    public function delete($file) {
        $path = $this->folder.'/'.basename($file); //basename to stop '../' jokers;
        if(!file_exists($path))
            return false;
        unlink($path);
    }
}
