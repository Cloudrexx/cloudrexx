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
 * Media Library
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @version       1.0.1
 * @package     cloudrexx
 * @subpackage  coremodule_media
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Core_Modules\Media\Controller;
/**
 * Media Library
 *
 * LibClass to manage cms media manager
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @access        public
 * @version       1.0.1
 * @package     cloudrexx
 * @subpackage  coremodule_media
 */
class MediaLibrary
{
    protected $sortBy    = 'name';
    protected $sortDesc  = false;

    public $_arrSettings = array();


    // act: newDir
    // creates a new directory through php or ftp
    function _createNewDir($dirName)
    {
        global $_ARRAYLANG, $objTemplate;

        $dirName = \Cx\Lib\FileSystem\FileSystem::replaceCharacters($dirName);
        $status = \Cx\Lib\FileSystem\FileSystem::make_folder($this->path.$dirName);
        if ($status) {
            $this->highlightName[] = $dirName;
            $objTemplate->setVariable('CONTENT_OK_MESSAGE',$_ARRAYLANG['TXT_MEDIA_MSG_NEW_DIR']);
        } else {
            $objTemplate->setVariable('CONTENT_STATUS_MESSAGE',$_ARRAYLANG['TXT_MEDIA_MSG_ERROR_NEW_DIR']);
        }
    }


    // act: preview
    // previews the edited image
    function _previewImage()
    {
        $arr = explode(',', $this->getData);
        $this->_objImage->loadImage($this->path.$this->getFile);
        $this->_objImage->resizeImage($arr[0], $arr[1], $arr[2]);
        $this->_objImage->showNewImage();
    }


    // act: previewSize
    // previews the size of the edite image
    function _previewImageSize()
    {
        $arr = explode(',', $this->getData);
        $this->_objImage->loadImage($this->path.$this->getFile);
        $this->_objImage->resizeImage($arr[0], $arr[1], $arr[2]);

        $time = time();
        $this->_objImage->saveNewImage($this->path.$time);
        $size = @filesize($this->path.$time);

        @unlink($this->path.$time);
        $size = $this->_formatSize($size);

        $width   = strlen($size) * 7 + 10;
        $img     = imagecreate($width, 20);
        $colBody = imagecolorallocate($img, 255, 255, 255);
        ImageFilledRectangle($img, 0, 0, $width, 20, $colBody);
        $colFont = imagecolorallocate($img, 0, 0, 0);
        imagettftext($img, 10, 0, 5, 15, $colFont, self::_getIconPath().'arial.ttf', $size);

        header("Content-type: image/jpeg");
        imagejpeg($img, '', 100);
    }

    /**
     * downloads the media
     *
     * act: download
     */
    function _downloadMediaOLD()
    {
        if (is_file($this->path.$this->getFile)) {
            \Cx\Core\Csrf\Controller\Csrf::redirect($this->webPath.$this->getFile);
            exit;
        }
    }

    /**
     * Send a file for downloading
     *
     */
    function _downloadMedia()
    {
        global $_ARRAYLANG;

        if (self::isIllegalFileName($this->getFile)) { die($_ARRAYLANG['TXT_MEDIA_FILE_DONT_DOWNLOAD']);}
        // The file is already checked (media paths only)
        $file = $this->path.$this->getFile;
        //First, see if the file exists
        if (!is_file($file)) { die("<b>404 File not found!</b>"); }

        $filename = basename($file);
        $file_extension = strtolower(substr(strrchr($filename,"."),1));

        //This will set the Content-Type to the appropriate setting for the file
        switch( $file_extension ) {
            case "pdf": $ctype="application/pdf"; break;
            case "exe": $ctype="application/octet-stream"; break;
            case "zip": $ctype="application/zip"; break;
            case "docx" :
            case "doc": $ctype="application/msword"; break;
            case "xlsx":
            case "xls": $ctype="application/vnd.ms-excel"; break;
            case "ppt": $ctype="application/vnd.ms-powerpoint"; break;
            case "gif": $ctype="image/gif"; break;
            case "png": $ctype="image/png"; break;
            case "jpeg":
            case "jpg": $ctype="image/jpg"; break;
            case "mp3": $ctype="audio/mpeg"; break;
            case "wav": $ctype="audio/x-wav"; break;
            case "mpeg":
            case "mpg":
            case "mpe": $ctype="video/mpeg"; break;
            case "mov": $ctype="video/quicktime"; break;
            case "avi": $ctype="video/x-msvideo"; break;

            //The following are for extensions that shouldn't be downloaded (sensitive stuff, like php files)
            case "phps":
            case "php4":
            case "php5":
            case "php": die("<b>Cannot be used for ". $file_extension ." files!</b>"); break;

            default: $ctype="application/force-download";
        }

        require_once ASCMS_LIBRARY_PATH.'/PEAR/Download.php';
        $dl = new \HTTP_Download(array(
          "file"                  => $file,
          "contenttype"           => $ctype
        ));
        $dl->send();

        exit;
    }


    /**
     * cuts the media -> paste insterts the media
     *
     * act: cut
     */
    function _cutMedia()
    {
        if (isset($_POST['formSelected']) && !empty($_POST['formSelected'])) {
            if (isset($_SESSION['mediaCutFile'])) {
                unset($_SESSION['mediaCutFile']);
            }
            if (isset($_SESSION['mediaCopyFile'])) {
                unset($_SESSION['mediaCopyFile']);
            }
            $_SESSION['mediaCutFile'] = array();
            $_SESSION['mediaCutFile'][] = $this->path;
            $_SESSION['mediaCutFile'][] = $this->webPath;
            $_SESSION['mediaCutFile'][] = $_POST['formSelected'];
        }
    }


    /**
     * copies the media -> paste inserts the media
     *
     * act: copy
     */
    function _copyMedia()
    {
        if (isset($_POST['formSelected']) && !empty($_POST['formSelected'])) {
            if (isset($_SESSION['mediaCutFile'])) {
                unset($_SESSION['mediaCutFile']);
            }
            if (isset($_SESSION['mediaCopyFile'])) {
                unset($_SESSION['mediaCopyFile']);
            }

            $_SESSION['mediaCopyFile'] = array();
            $_SESSION['mediaCopyFile'][] = $this->path;
            $_SESSION['mediaCopyFile'][] = $this->webPath;
            $_SESSION['mediaCopyFile'][] = $_POST['formSelected'];
        }
    }


    /**
     * Inserts the file
     *
     * act: paste
     */
    function _pasteMedia()
    {
        global $_ARRAYLANG, $objTemplate;

        // cut
        if (isset($_SESSION['mediaCutFile']) && !empty($_SESSION['mediaCutFile'])) {
            $check = true;

            foreach ($_SESSION['mediaCutFile'][2] as $name) {
                if ($_SESSION['mediaCutFile'][0] != $this->path) {
                    $obj_file = new \File();

                    if (is_dir($_SESSION['mediaCutFile'][0].$name)) {
                        $this->dirLog=$obj_file->copyDir($_SESSION['mediaCutFile'][0], $_SESSION['mediaCutFile'][1], $name, $this->path, $this->webPath, $name);
                        if ($this->dirLog == "error") {
                            $check = false;
                        } else {
                            $obj_file->delDir($_SESSION['mediaCutFile'][0], $_SESSION['mediaCutFile'][1], $name);
                        }
                    } else {
                        $this->dirLog=$obj_file->copyFile($_SESSION['mediaCutFile'][0], $name, $this->path, $name);
                        if ($this->dirLog == "error") {
                            $check = false;
                        } else {
                            $obj_file->delFile($_SESSION['mediaCutFile'][0], $_SESSION['mediaCutFile'][1], $name);
                        }
                    }

                    $this->highlightName[] = $this->dirLog;
                }
                else
                {
                    $this->highlightName[] = $name;
                }
            }

            if ($check != false) {
                $objTemplate->setVariable('CONTENT_OK_MESSAGE',$_ARRAYLANG['TXT_MEDIA_MSG_CUT']);
                unset($_SESSION['mediaCutFile']);
            } else {
                $objTemplate->setVariable('CONTENT_STATUS_MESSAGE',$_ARRAYLANG['TXT_MEDIA_MSG_ERROR_CUT']);
            }
        }

        // copy
        if (isset($_SESSION['mediaCopyFile']) && !empty($_SESSION['mediaCopyFile']))
        {
            $check = true;

            foreach ($_SESSION['mediaCopyFile'][2] as $name) {
                $obj_file = new \File();

                if (is_dir($_SESSION['mediaCopyFile'][0].$name)) {
                    $this->dirLog=$obj_file->copyDir($_SESSION['mediaCopyFile'][0], $_SESSION['mediaCopyFile'][1], $name, $this->path, $this->webPath, $name);
                    if ($this->dirLog == "error") {
                        $check = false;
                    }
                } else {
                    $this->dirLog=$obj_file->copyFile($_SESSION['mediaCopyFile'][0], $name, $this->path, $name);
                    if ($this->dirLog == "error") {
                        $check = false;
                    }
                }

                $this->highlightName[] = $this->dirLog;
            }

            if ($check != false) {
                $objTemplate->setVariable('CONTENT_OK_MESSAGE',$_ARRAYLANG['TXT_MEDIA_MSG_COPY']);
                unset($_SESSION['mediaCopyFile']);
            } else {
                $objTemplate->setVariable('CONTENT_STATUS_MESSAGE',$_ARRAYLANG['TXT_MEDIA_MSG_ERROR_COPY']);
            }
        }
    }


    // act: delete
    // deletes a file or an directory
    function _deleteMedia()
    {
        if (!empty($this->getFile)) {
            \Message::ok($this->_deleteMedia2($this->getFile));
        } elseif (!empty($_POST['formSelected'])) {
            foreach ($_POST['formSelected'] as $file) {
                $status = $this->_deleteMedia2($file);
            }
            \Message::ok($status);
        }
    }


    function _deleteMedia2($file)
    {
        global $_ARRAYLANG;

        if (self::isIllegalFileName($file)) {
            return $_ARRAYLANG['TXT_MEDIA_FILE_DONT_DELETE'];
        }
        $obj_file = new \File();
        if (is_dir($this->path.$file)) {
            $this->dirLog=$obj_file->delDir($this->path, $this->webPath, $file);
            if ($this->dirLog != "error") {
                $status = $_ARRAYLANG['TXT_MEDIA_MSG_DIR_DELETE'];
            } else {
                $status = $_ARRAYLANG['TXT_MEDIA_MSG_ERROR_DIR'];
            }
         } else {
            if ($this->_isImage($this->path.$file)) {
                $thumb_name = basename(\ImageManager::getThumbnailFilename($this->path . $file));
                if (file_exists($this->path.$thumb_name)) {
                    $this->dirLog=$obj_file->delFile($this->path, $this->webPath, $thumb_name);
                }
            }
            $this->dirLog=$obj_file->delFile($this->path, $this->webPath, $file);
            if ($this->dirLog != "error") {
                $status = $_ARRAYLANG['TXT_MEDIA_MSG_FILE_DELETE'];
            } else {
                $status = $_ARRAYLANG['TXT_MEDIA_MSG_ERROR_FILE'];
            }
        }
        return $status;
    }


    /**
     * Renames a media file
     */
    function renMedia()
    {
        global $_ARRAYLANG;

        $objFile = new \File();
        // file or dir
        $fileName = !empty($_POST['renName']) ? $_POST['renName'] : 'empty';
        $oldName  =   empty($_POST['oldExt'])
                    ? contrexx_input2raw($_POST['oldName'])
                    : contrexx_input2raw($_POST['oldName'] . '.' . $_POST['oldExt']);

        if (!is_dir($this->path . $oldName)) {
            $ext      =   !empty($_POST['renExt']) && \FWValidator::is_file_ending_harmless($_POST['renName'] . '.' . $_POST['renExt'])
                        ? $_POST['renExt'] : 'txt';
            $fileName = $fileName . '.' . $ext;
        }

        \Cx\Lib\FileSystem\FileSystem::clean_path($fileName);

        $makeCopy = (isset($_POST['mediaInputAsCopy']) && $_POST['mediaInputAsCopy'] == 1);

        if (!$makeCopy) {
            // rename old to new
            if (is_dir($this->path . $oldName)) {
                $result = $objFile->renameDir($this->path, $this->webPath, $oldName, $fileName);
            } else {
                $result = $objFile->renameFile($this->path, $this->webPath, $oldName, $fileName);
            }
        } else {
            // copy old to new
            if (is_dir($this->path . $oldName)) {
                $result = $objFile->copyDir($this->path, $this->webPath, $oldName, $this->path, $this->webPath, $fileName);
            } else {
                $result = $objFile->copyFile($this->path, $oldName, $this->path, $fileName);
            }
        }

        if ($result == 'error') {
            \Message::error($_ARRAYLANG['TXT_MEDIA_MSG_ERROR_EDIT']);
            return;
        } else {
            $_SESSION['media_highlight_name'] = array($result);
            \Message::ok($_ARRAYLANG['TXT_MEDIA_MSG_EDIT']);
        }

        // save image
        $this->_objImage->loadImage($this->path . $result);
        $this->_objImage->saveNewImage($this->path . $result, true);
    }

    /**
     * This method is used for the image preview.
     *
     * @param   array  $arrData  Contains $_GET array.
     * @return  image  On error,
     */
    public function getImage($arrData)
    {
        if (!empty($this->path) && !empty($this->getFile)) {
            // Image loader
            if (!$this->_objImage->loadImage($this->path.$this->getFile)) {
                throw new \Exception('Could not load image');
            }

            // Rotate image
            if (!empty($arrData['d'])) {
                $this->_objImage->rotateImage(intval($arrData['d']));
            }

            // Crop image
            if (isset($arrData['x']) && isset($arrData['y']) && !empty($arrData['w']) && !empty($arrData['h'])) {
                $this->_objImage->cropImage(intval($arrData['x']), intval($arrData['y']), intval($arrData['w']), intval($arrData['h']));
            }

            // Resize image
            if (!empty($arrData['rw']) && !empty($arrData['rh']) && !empty($arrData['q'])) {
                if (!$this->_objImage->resizeImage(intval($arrData['rw']), intval($arrData['rh']), intval($arrData['q']))) {
                    throw new \Exception('Could not resize image');
                }
            }

            // Show edited image
            if (!$this->_objImage->showNewImage()) {
                throw new \Exception('Is not a valid image or image type');
            }

            return;
        }

        throw new \Exception('Path or file is empty');
    }


    /**
     * Edits and saves an image.
     *
     * @param  array  $arrData  Contains $_POST array.
     * @return bool             True on success, false otherwise.
     */
    public function editImage($arrData)
    {
        global $_ARRAYLANG, $objTemplate;

        $objFile = new \File();
        $orgFile = $arrData['orgName'].'.'.$arrData['orgExt'];
        $newName = $arrData['newName'];
        $newFile = $newName.'.'.$arrData['orgExt'];
        \Cx\Lib\FileSystem\FileSystem::clean_path($newFile);

        // If new image name is set, image will be copied. Otherwise, image will be overwritten
        if ($newName != '') {
            $this->fileLog = $objFile->copyFile($this->path, $orgFile, $this->path, $newFile);
            if ($this->fileLog == 'error') {
                throw new \Exception('Could not copy image');
            }
        } else {
            $this->fileLog = $orgFile;
        }

        // Edit image
        if (!empty($this->path) && !empty($this->fileLog)) {
            // Image loader
            if (!$this->_objImage->loadImage($this->path.$this->fileLog)) {
                throw new \Exception('Could not load image');
            }

            // Rotate image
            if (!empty($arrData['d'])) {
                $this->_objImage->rotateImage(intval($arrData['d']));
            }

            // Crop image
            if (isset($arrData['x']) && isset($arrData['y']) && !empty($arrData['w']) && !empty($arrData['h'])) {
                $this->_objImage->cropImage(intval($arrData['x']), intval($arrData['y']), intval($arrData['w']), intval($arrData['h']));
            }

            // Resize image
            if (!empty($arrData['rw']) && !empty($arrData['rh']) && !empty($arrData['q'])) {
                if (!$this->_objImage->resizeImage(intval($arrData['rw']), intval($arrData['rh']), intval($arrData['q']))) {
                    throw new \Exception('Could not resize image');
                }
            }

            // Save new image
            if (!$this->_objImage->saveNewImage($this->path.$this->fileLog, true)) {
                throw new \Exception('Is not a valid image or image type');
            }


            // If no error occured, return true
            return $this->fileLog;
        }

        throw new \Exception('Path or file is empty');
    }


    // check if is image
    function _isImage($file)
    {
        if (is_dir($file)) return false;

// TODO: merge this function with isImage of lib/FRAMEWORK/Image.class.php
        if (class_exists('finfo', false)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file);

            if (strpos($mimeType, 'image') !== 0) {
                return false;
            }

            $type = substr($mimeType, strpos($mimeType, '/') + 1);
            switch ($type) {
                case 'gif':
                    return 1;
                    break;

                case 'jpeg':
                    return 2;
                    break;

                case 'png':
                    return 3;
                    break;
            }
        }

        if (function_exists('exif_imagetype')) {
            $type = exif_imagetype($file);
        } elseif (function_exists('getimagesize')) {
            $img  = @getimagesize($file);
            if ($img === false) {
                return false;
            }
            $type = $img[2];
        } else {
            return false;
        }

        if ($type >= 1 && $type <= 3) {
            // 1 = gif, 2 = jpg, 3 = png
            return $type;
        } else {
            return false;
        }
    }


    // check for manual input in $_GET['path']
    function _pathCheck($path) {
        $check = false;
        if (!empty($path)) {
            foreach ($this->arrWebPaths as $tmp) {
                if (substr($path, 0, strlen($tmp)) == $tmp && file_exists($this->docRoot.$path)) {
                    $check = true;
                }
            }
        }
        if (empty($path) || $check == false) {
            $path = $this->arrWebPaths[$this->archive];
        }
        if (substr($path, -1) != '/') {
            $path = $path.'/';
        }
        return $path;
    }

    function _sortDirTree($tree)
    {
        $d    = $tree['dir'];
        $f    = $tree['file'];
        $direction = $this->sortDesc ? SORT_DESC : SORT_ASC;

        switch ($this->sortBy) {
            // sort by size
            case 'size':
                @array_multisort($d['size'], $direction, $d['name'], $d['type'], $d['date'], $d['perm'], $d['icon'], $d['path']);
                @array_multisort($f['size'], $direction, $f['name'], $f['type'], $f['date'], $f['perm'], $f['icon'], $f['path']);
                break;
            // sort by type
            case 'type':
                @array_multisort($d['type'], $direction, $d['name'], $d['size'], $d['date'], $d['perm'], $d['icon'], $d['path']);
                @array_multisort($f['type'], $direction, $f['name'], $f['size'], $f['date'], $f['perm'], $f['icon'], $f['path']);
                break;
            //sort by date
            case 'date':
                @array_multisort($d['date'], $direction, $d['name'], $d['size'], $d['type'], $d['perm'], $d['icon'], $d['path']);
                @array_multisort($f['date'], $direction, $f['name'], $f['size'], $f['type'], $f['perm'], $f['icon'], $f['path']);
                break;
            //sort by perm
            case 'perm':
                $direction = !$this->sortDesc ? SORT_DESC : SORT_ASC;
                @array_multisort($d['perm'], $direction, $d['name'], $d['size'], $d['type'], $d['date'], $d['icon'], $d['path']);
                @array_multisort($f['perm'], $direction, $f['name'], $f['size'], $f['type'], $f['date'], $f['icon'], $f['path']);
                break;
            // sort by name
            case 'name':
            default:
                @array_multisort($d['name'], $direction, SORT_NATURAL | SORT_FLAG_CASE, $d['size'], $d['type'], $d['date'], $d['perm'], $d['icon'], $d['path']);
                @array_multisort($f['name'], $direction, SORT_NATURAL | SORT_FLAG_CASE, $f['size'], $f['type'], $f['date'], $f['perm'], $f['icon'], $f['path']);
                break;
        }

        $dirTree['dir']  = $d;
        $dirTree['file'] = $f;
        return $dirTree;
    }


    // designs the sorting icons
    function _sortingIcons()
    {
        $icon         = array(
            'size'    => null,
            'type'    => null,
            'date'    => null,
            'perm'    => null,
            'name'    => null
        );
        $icon1        = '&darr;';     // sort desc
        $icon2        = '&uarr;';     // sort asc
        switch($this->sortBy) {
            case 'size':
                $icon['size'] = $this->sortDesc ? $icon1 : $icon2;
                break;
            case 'type':
                $icon['type'] = $this->sortDesc ? $icon1 : $icon2;
                break;
            case 'date':
                $icon['date'] = $this->sortDesc ? $icon1 : $icon2;
                break;
            case 'perm':
                $icon['perm'] = $this->sortDesc ? $icon1 : $icon2;
                break;
            default:
                $icon['name'] = $this->sortDesc ? $icon1 : $icon2;
        }
        return $icon;
    }


    // designs the sorting class
    function _sortingClass()
    {
        $class         = array(
            'size'    => null,
            'type'    => null,
            'date'    => null,
            'perm'    => null,
            'name'    => null
        );
        $class1        = 'sort';     // sort desc
        $class2        = 'sort';     // sort asc

        switch($this->sortBy) {
            case 'size':
                $class['size'] = $this->sortDesc ? $class1 : $class2;
                break;
            case 'type':
                $class['type'] = $this->sortDesc ? $class1 : $class2;
                break;
            case 'date':
                $class['date'] = $this->sortDesc ? $class1 : $class2;
                break;
            case 'perm':
                $class['perm'] = $this->sortDesc ? $class1 : $class2;
                break;
            default:
                $class['name'] = $this->sortDesc ? $class1 : $class2;
        }
        return $class;
    }

    /**
     * Get the web path to the icon used for displaying the file type of a file
     *
     * @param   string  $file   File of which the related file type icon path shall be returned.
     *                          File must be an absolute file system path or an URL.
     * @param   string  $fileType  (optional) The file type of $file (as file extension). When supplied, the method will skip the file type detection and will run quite faster.
     * @return  string  Web path to the icon.
     */
    public static function getFileTypeIconWebPath($file, $fileType = null)
    {
        $iconPath = \Cx\Core_Modules\Media\Controller\MediaLibrary::_getIconPath() .
                    \Cx\Core_Modules\Media\Controller\MediaLibrary::_getIcon($file, $fileType) . '.png';
        return \Cx\Core\Core\Controller\Cx::instanciate()->getClassLoader()->getWebFilePath($iconPath);
    }

    /**
     * Gets the icon for the file
     *
     * @param string $file      The File Path
     * @param string $fileType  (optional) The File type
     *
     * @return string           The Icon name
     */
    public static function _getIcon($file, $fileType = null)
    {
        $icon = '';
        if (isset($fileType)) {
            $icon = strtoupper($fileType);
        } elseif (is_file($file)) {
            $info = pathinfo($file);
            if (isset($info['extension'])) {
                $icon = strtoupper($info['extension']);
            }
        }

        $arrImageExt        = array('JPEG', 'JPG', 'TIFF', 'GIF', 'BMP', 'PNG');
        $arrVideoExt        = array('3GP', 'AVI', 'DAT', 'FLV', 'FLA', 'M4V', 'MOV', 'MPEG', 'MPG', 'OGG', 'WMV', 'SWF');
        $arrAudioExt        = array('WAV', 'WMA', 'AMR', 'MP3', 'AAC');
        $arrPresentationExt = array('ODP', 'PPT', 'PPTX');
        $arrSpreadsheetExt  = array('CSV', 'ODS', 'XLS', 'XLSX');
        $arrDocumentsExt    = array('DOC', 'DOCX', 'ODT', 'RTF');
        $arrWebDocumentExt  = array('HTML', 'HTM');

        switch (true) {
            case ($icon == 'TXT'):
                $icon = 'Text';
                break;
            case ($icon == 'PDF'):
                $icon = 'Pdf';
                break;
            case in_array($icon, $arrImageExt):
                $icon = 'Image';
                break;
            case in_array($icon, $arrVideoExt):
                $icon = 'Video';
                break;
            case in_array($icon, $arrAudioExt):
                $icon = 'Audio';
                break;
            case in_array($icon, $arrPresentationExt):
                $icon = 'Presentation';
                break;
            case in_array($icon, $arrSpreadsheetExt):
                $icon = 'Spreadsheet';
                break;
            case in_array($icon, $arrDocumentsExt):
                $icon = 'TextDocument';
                break;
            case in_array($icon, $arrWebDocumentExt):
            case \FWValidator::isUri($file):
                $icon = 'WebDocument';
                break;
            default :
                $icon = 'Unknown';
                break;
        }
        if (is_dir($file)) {
            $icon = 'Folder';
        }

        if (!file_exists(self::_getIconPath().$icon.'.png') or !isset($icon)) {
            $icon = '_blank';
        }
        return $icon;
    }

    /**
     * Returns icon's absolute path
     *
     * @return string
     */
    public static function _getIconPath()
    {
        return \Cx\Core\Core\Controller\Cx::instanciate()->getCodeBaseCoreModulePath() . '/Media/View/Media/';
    }

    /**
     * Returns icon's web path
     *
     * @return string
     */
    public static function _getIconWebPath()
    {
        return \Cx\Core\Core\Controller\Cx::instanciate()->getCodeBaseCoreModuleWebPath() . '/Media/View/Media/';
    }

    // gets the filesize
    function _getSize($file)
    {
        if (is_file($file)) {
            if (@filesize($file)) {
                $size = filesize($file);
            }
        }
        if (is_dir($file)) {
            $size = '[folder]';
        }
        (!isset($size) or empty($size)) ? $size = '0' : '';
        return $size;
    }


    // formats the filesize
    function _formatSize($size)
    {
        $multi = 1024;
        $divid = 1000;
        $arrEnd = array(' Byte', ' Bytes', ' KB', ' MB', ' GB');
        if ($size != '[folder]') {
            if ($size >= ($multi * $multi * $multi)) {
                $size = round($size / ($multi * $multi * $multi), 2);
                $end  = 4;
            }
            elseif ($size >= ($multi * $multi)) {
                $size = round($size / ($multi * $multi), 2);
                $end  = 3;
            }
            elseif ($size >= $multi) {
                $size = round($size / $multi, 2);
                $end  = 2;
            }
            elseif ($size < $multi && $size > 1) {
                $size = $size;
                $end  = 1;
            } else {
                $size = $size;
                $end  = 0;
            }
            if ($size >= $divid) {
                $size = round($size / $multi, 2);
                $end  = $end + 1;
            }
            $size = $size.$arrEnd[$end];
        } else {
            $size = '-';
        }
        return $size;
    }


    // gets the filetype
    function _getType($file)
    {
        if (is_file($file)) {
            $info = pathinfo($file);
            $type = strtoupper($info['extension']);
        }
        if (is_dir($file)) {
            $type = '[folder]';
        }
        (!isset($type) or empty($type)) ? $type = '-' : '';
        return $type;
    }


    // formats the filetype
    function _formatType($type)
    {
        global $_ARRAYLANG;

        if ($type != '-' && $type != '[folder]') {
            $type = $type.'-'.$_ARRAYLANG['TXT_MEDIA_FILE'];
        } elseif ($type == '[folder]') {
            $type = $_ARRAYLANG['TXT_MEDIA_FILE_DIRECTORY'];
        }
        return $type;
    }


    // gets the date of the last modification of the file
    function _getDate($file)
    {
        if (@filectime($file)) {
            $date = filectime($file);
        }
        if (!isset($file)) {
            $date = '';
        }
        return $date;
    }


    // formats the date of the last modification of the file
    function _formatDate($date)
    {
        if (!empty($date)) {
            $date = date(ASCMS_DATE_FORMAT_DATETIME, $date);
        } else {
            $date = '-';
        }
        return $date;
    }


    // gets the permission of the file
    function _getPerm($file)
    {
        if (@fileperms($file)) {
            $perm = substr(decoct(fileperms($file)), -4);
        }
        if (!isset($perm)) {
            $perm = '';
        }
        return $perm;
    }


    // formats the permission of the file
    function _formatPerm($perm, $key)
    {
        if (!empty($perm)) {
            $per   = array();
            $per[] = $perm[1];
            $per[] = $perm[2];
            $per[] = $perm[3];

            ($key == 'dir')  ? $perm = 'd'       : '';
            ($key == 'file') ? $perm = '&minus;' : '';
            foreach ($per as $out) {
                switch($out) {
                    case 7:
                        $perm .= ' rwx';
                        break;
                    case 6:
                        $perm .= ' rw&minus;';
                        break;
                    case 5:
                        $perm .= ' r&minus;x';
                        break;
                    case 4:
                        $perm .= ' r&minus;&minus;';
                        break;
                    case 3:
                        $perm .= ' &minus;wx';
                        break;
                    case 2:
                        $perm .= ' &minus;w&minus;';
                        break;
                    case 1:
                        $perm .= ' &minus;&minus;x';
                        break;
                    default:
                        $perm .= ' &minus;&minus;&minus;';
                }
            }
        } else {
            $perm = '-';
        }
        return $perm;
    }


    function _getJavaScriptCodePreview()
    {
        global $_ARRAYLANG;

        \JS::activate('jquery');

        $delete_msg = $_ARRAYLANG['TXT_MEDIA_CONFIRM_DELETE_2'];
        $csrfCode   = \Cx\Core\Csrf\Controller\Csrf::code();
        $code       = <<<END
                    <script type="text/javascript">
                    /* <![CDATA[ */
                        function preview(file, width, height)
                        {
                            var f = file;
                            var w = width + 10;
                            var h = height + 10;
                            var l = (screen.availWidth - width) / 2;
                            var t = (screen.availHeight - 50 - height) / 2;
                            prev  = window.open('', '', "width="+w+", height="+h+", left="+l+", top="+t+", scrollbars=no, toolbars=no, status=no, resizable=yes");
                            prev.document.open();
                            prev.document.write('<html><title>'+f+'<\/title><body style="margin: 5px; padding: 0px;">');
                            prev.document.write('<img src=\"'+f+'\" width='+width+' height='+height+' alt=\"'+f+'\">');
                            prev.document.write('<\/body><\/html>');
                            prev.document.close();
                            prev.focus();
                        }

                        function mediaConfirmDelete(file)
                        {
                            if (confirm('$delete_msg')) {
                                return true;
                            } else {
                                return false;
                            }
                        }

                        /*
                           **  Returns the caret (cursor) position of the specified text field.
                           **  Return value range is 0-oField.length.
                           */
                        function doGetCaretPosition (oField) {
                                var iCaretPos = 0;
                                // IE Support
                                if (document.selection) {
                                        var oSel = document.selection.createRange ();
                                        oSel.moveStart ('character', -oField.value.length);
                                        iCaretPos = oSel.text.length;
                                } else if (oField.selectionStart || oField.selectionStart == '0') {
                                        // Firefox support
                                        iCaretPos = oField.selectionStart;
                                }
                                return (iCaretPos);
                        }

                        /*
                        **  Sets the caret (cursor) position of the specified text field.
                        **  Valid positions are 0-oField.length.
                        */
                        function doSetCaretPosition(oField, pos){
                                if (oField.setSelectionRange) {
                                        oField.setSelectionRange(pos,pos);
                                } else if (oField.createTextRange) {
                                        var range = oField.createTextRange();
                                        range.collapse(true);
                                        range.moveEnd('character', pos);
                                        range.moveStart('character', pos);
                                        range.select();
                                }
                        }

                        \$J(document).ready(function() {

                            \$J('#filename').live('keyup', function(event){
                                pos = doGetCaretPosition(document.getElementById('filename'));
                                \$J(this).val(\$J(this).val().replace(/[^0-9a-zA-Z_\-\. ]/g,'_'));
                                doSetCaretPosition(document.getElementById('filename'), pos);
                                //submit the input value on hitting Enter key to rename action
                                if(event.keyCode == 13) {
                                    var newFileName = \$J('#filename').val();
                                    var oldFileName = \$J('#oldFilename').val();
                                    var actionPath  = \$J('#actionPath').val();
                                    var fileExt     = \$J('#fileExt').val();
                                    if (newFileName != oldFileName && \$J.trim(newFileName) != "") {
                                        actionPath += '&newfile='+newFileName+fileExt;
                                        window.location = actionPath;
                                    } else {
                                        \$J('#filename').focusout();
                                    }
                                }
                                return true;
                            });

                            \$J('.rename_btn').click(function(){
                                if (\$J('#filename').length == 0) {
                                    \$J(this).parent().parent().find('.file_name a').css('display','none');
                                    file_name = "";
                                    file = \$J(this).parent().parent().find('.file_name a').html();
                                    fileSplitLength = file.split('.').length;
                                    isFolder = (\$J(this).parent().parent().find('.file_size').html() == '&nbsp;-') ? 1 : 0;

                                    //Display Filename in input box without file extension (with multi dots in filename)
                                    file_ext = (isFolder != 1 && fileSplitLength > 1) ?
                                                    ("."+file.split('.')[fileSplitLength-1])
                                                    : "";
                                    loop     = (isFolder != 1 && fileSplitLength > 1) ?
                                                    (fileSplitLength - 1)
                                                    : fileSplitLength;

                                    for (i=0; i < loop; i++) {
                                        file_name += i > 0 ? "." : "";
                                        file_name += file.split('.')[i];
                                    }
                                    actionPath = \$J(this).data('actionUrl');

                                    //Rename Form
                                    \$J(this).parent().parent().find('.file_name')
                                    .append('<div id="insertform"><input type="text" id="filename" name="filename" style="padding:0px;" value="'+file_name+'"/>'+file_ext
                                            +'<input type="hidden" value="'+actionPath+'" id="actionPath" name="actionPath" />'
                                            +'<input type="hidden" value="'+file_name+'" id="oldFilename" name="oldFilename" />'
                                            +'<input type="hidden" value="'+file_ext+'" id="fileExt" name="fileExt" /></div>');
                                    \$J("#filename").focus();
                                }
                            });

                            //Hide added form and display file name link on blur
                            \$J("#filename").live('blur',function(){
                                \$J(this).parent().parent().find('a').css('display','block');
                                \$J(this).parent().remove();
                            });
                        });
                    /* ]]> */
                    </script>
END;
        return $code;
    }

    /**
     * Create an array containing all settings of the media-module.
     * Example: $arrSettings[$strSettingName] for the content of $strSettingsName
     * @global  ADONewConnection
     * @return  array       $arrReturn
     */
    function createSettingsArray()
    {
        global $objDatabase;

        $arrReturn = array();
        $objResult = $objDatabase->Execute('SELECT  name,
                                                    value
                                            FROM    '.DBPREFIX.'module_media_settings
                                        ');
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $arrReturn[$objResult->fields['name']] = $objResult->fields['value'];
                $objResult->MoveNext();
            }
        }
        return $arrReturn;
    }

    /**
     * this is called as soon as uploads have finished.
     * takes care of moving them to the right folder
     *
     * @return string the directory to move to
     */
    public static function uploadFinished(
        $tempPath, $tempWebPath, $data, $uploadId, $fileInfos, $response
    ) {
        $path    = $data['path'];
        $webPath = $data['webPath'];

        //we remember the names of the uploaded files here. they are stored in the session afterwards,
        //so we can later display them highlighted.
        $arrFiles = array();

        //rename files, delete unwanted
        $arrFilesToRename = array(); //used to remember the files we need to rename
        $h                = opendir($tempPath);
        if ($h) {
            while (false !== ($file = readdir($h))) {
                //delete potentially malicious files
// TODO: this is probably an overhead, because the uploader might already to this. doesn't it?
                if (!\FWValidator::is_file_ending_harmless($file)) {
                    @unlink($file);
                    continue;
                }

                if (self::isIllegalFileName($file)) {
                    $response->addMessage(
                        \Cx\Core_Modules\Uploader\Controller\UploadResponse::STATUS_ERROR,
                        "You are not able to create the requested file."
                    );
                    \Cx\Lib\FileSystem\FileSystem::delete_file(
                        $tempPath . '/' . $file
                    );
                    continue;
                }

                //skip . and ..
                if ($file == '.' || $file == '..') {
                    continue;
                }

                //clean file name
                $newName = $file;
                \Cx\Lib\FileSystem\FileSystem::clean_path($newName);

                //check if file needs to be renamed
                if (file_exists($path . $newName)) {
                    $info  = pathinfo($newName);
                    $exte  = $info['extension'];
                    $exte  = (!empty($exte)) ? '.' . $exte : '';
                    $part1 = $info['filename'];
                    if (empty($_REQUEST['uploadForceOverwrite'])
                        || !intval(
                            $_REQUEST['uploadForceOverwrite'] > 0
                        )
                    ) {
                        $newName = $part1 . '_' . time() . $exte;
                    }
                }

                //if the name has changed, the file needs to be renamed afterwards
                if ($newName != $file) {
                    $arrFilesToRename[$file] = $newName;
                }

                array_push($arrFiles, $newName);
            }
        }

        //rename files where needed
        foreach ($arrFilesToRename as $oldName => $newName) {
            rename($tempPath . '/' . $oldName, $tempPath . '/' . $newName);
        }

        //remeber the uploaded files
        $files                                    = $_SESSION["media_upload_files_$uploadId"];
        $_SESSION["media_upload_files_$uploadId"] = array_merge(
            $arrFiles, ($files ? $files->toArray() : [])
        );
        /* unwanted files have been deleted, unallowed filenames corrected.
           we can now simply return the desired target path, as only valid
           files are present in $tempPath                                   */

        return array($data['path'], $data['webPath']);
    }

    /**
     * Returns the image settings array.
     *
     * @global  object  $objDatabase       ADONewConnection
     * @return  array   $arrImageSettings
     */
    public function getImageSettings()
    {
        global $objDatabase;

        $query = '
            SELECT `name`, `value`
            FROM `'.DBPREFIX.'settings_image`
        ';
        $objResult = $objDatabase->Execute($query);

        $arrImageSettings = array();
        if ($objResult === false) {
            throw new \Exception($objDatabase->ErrorMsg());
        }
        while (!$objResult->EOF) {
            $arrImageSettings[$objResult->fields['name']] = intval($objResult->fields['value']);
            $objResult->MoveNext();
        }

        return $arrImageSettings;
    }

    /**
     * Check the the file name is illegal or not.
     *
     * @param type $file
     * @return boolean
     */
    public static function isIllegalFileName($file) {
        if (preg_match('#^(\index.php|\.htaccess|\.ftpaccess|\.passwd|web\.config)$#i', $file)) {
            return true;
        }
        return false;
    }

    /**
     * Get files by search term
     *
     * @param string    $path           Path to search files
     * @param string    $searchTerm     Search term
     * @param array     $result         Result files and directory array
     * @param boolean   $recursive      True to search recursive
     *
     * @return array   Files array by given search term
     */
    public function getDirectoryTree($path = '', $searchTerm = '', & $result = array(), $recursive = false)
    {
        if (empty($path)) {
            return array();
        }

        if (!is_dir($path)) {
            return array();
        }

        if (empty($result)) {
            $result = array(
                'dir'  => array(
                    'icon' => array(),
                    'name' => array(),
                    'size' => array(),
                    'type' => array(),
                    'date' => array(),
                    'perm' => array(),
                    'path' => array(),
                ),
                'file' => array(
                    'icon' => array(),
                    'name' => array(),
                    'size' => array(),
                    'type' => array(),
                    'date' => array(),
                    'perm' => array(),
                    'path' => array(),
                ),
            );
        }

        $mediaArray = glob($path . '*');
        foreach ($mediaArray as $media) {
            $mediaName = basename($media);
            if (!\FWSystem::detectUtf8($mediaName)) {
                $mediaName = utf8_encode($mediaName);
            }
            if (!empty($searchTerm) && !preg_match('/'. preg_quote($searchTerm) .'/i', $mediaName)) {
                continue;
            }
            $mediaType = is_dir($media) ? 'dir' : 'file';
            $mediaPath = dirname($media);
            if ($mediaType == 'file' && !$this->isFileValidToShow($mediaPath, $mediaName)) {
                continue;
            }

            $result[$mediaType]['icon'][] = self::getFileTypeIconWebPath($media);
            $result[$mediaType]['name'][] = $mediaName;
            $result[$mediaType]['size'][] = $this->_getSize($media);
            $result[$mediaType]['type'][] = $this->_getType($media);
            $result[$mediaType]['date'][] = $this->_getDate($media);
            $result[$mediaType]['perm'][] = $this->_getPerm($media);
            $result[$mediaType]['path'][] = $mediaPath;
        }
        if ($recursive) {
            foreach (glob($path .'*', GLOB_ONLYDIR | GLOB_MARK) as $dir) {
                $this->getDirectoryTree($dir, $searchTerm, $result, $recursive);
            }
        }

        return $result;
    }

    /**
     * Return whether file is valid to show or not
     *
     * @param string $filePath  Folder path to the file
     * @param string $fileName  File name
     *
     * @return boolean True when file is valid to show, False otherwise
     */
    public function isFileValidToShow($filePath, $fileName)
    {
        if (   empty($filePath)
            || empty($fileName)
            || self::isIllegalFileName($fileName)
        ) {
            return false;
        }

        // check if file is a thumbnail
        if (!preg_match("/(?:\.(?:thumb_thumbnail|thumb_medium|thumb_large)\.[^.]+$)|(?:\.thumb)$/i", $fileName)) {
            return true;
        }

        // check if original image of thumbnail exists
        $originalFileName = preg_replace("/(?:\.(?:thumb_thumbnail|thumb_medium|thumb_large)(\.[^.]+)$)|(?:\.thumb)$/mi", "$1", $fileName);
        if (\Cx\Lib\FileSystem\FileSystem::exists($filePath . '/' . $originalFileName)) {
            return false;
        }

        // check if original image of thumbnail exists, by making the
        // file extension of the image all uppercase
        $fileInfo = pathinfo($originalFileName);
        $originalFileName = $fileInfo['filename'] . '.' . strtoupper($fileInfo['extension']);
        if (\Cx\Lib\FileSystem\FileSystem::exists($filePath . '/' . $originalFileName)) {
            return false;
        }

        // original image of thumbnail does not exists,
        // therefore, we shall drop the orphaned thumbnail image
        \Cx\Lib\FileSystem\FileSystem::delete_file($filePath . '/'. $fileName);
        return false;
    }

    /**
     * Redirect to the page by requested redirect url
     */
    public function handleRedirect()
    {
        if (empty($_REQUEST['redirect'])) {
            return;
        }
        $redirect = \FWUser::getRedirectUrl(urlencode(base64_decode(urldecode($_REQUEST['redirect']))));
        \Cx\Core\Csrf\Controller\Csrf::redirect($redirect);
        exit;
    }
}
