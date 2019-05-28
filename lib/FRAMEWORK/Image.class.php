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
 * Image manager
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Paulo M. Santos <pmsantos@astalavista.net>
 * @version     1.0
 * @package     cloudrexx
 * @subpackage  lib_framework
 * @todo        Edit PHP DocBlocks!
 */
use Cx\Core\Core\Controller\Cx;

/**
 * Image manager
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Paulo M. Santos <pmsantos@astalavista.net>
 * @version     1.0
 * @access      public
 * @package     cloudrexx
 * @subpackage  lib_framework
 */
class ImageManager
{
    public $orgImage;
    public $orgImageWidth;
    public $orgImageHeight;
    public $orgImageType;
    public $orgImageFile;

    public $newImage;
    public $newImageWidth;
    public $newImageHeight;
    public $newImageQuality;
    public $newImageType;
    public $newImageFile;
    public $newImagePosX;
    public $newImagePosY;

    public $imageCheck = 1;

    const IMG_TYPE_GIF = 1;
    const IMG_TYPE_JPEG = 2;
    const IMG_TYPE_PNG = 3;


    /**
     * Constructor
     *
     * @access   public
     * @return   void
     */
    function __construct()
    {
        $this->_resetVariables();
    }


    /**
     * Gets serveral image information and saves it in the image variables.
     * This method does not use exceptions because it is called by older methods without catchers.
     *
     * @access  public
     * @param   string  $file  Path and filename of the existing image.
     * @return  bool           True on success, false otherwise.
     */
    public function loadImage($file)
    {
        $this->_resetVariables();
        $this->orgImageFile = $file;
        $this->orgImageType = $this->_isImage($this->orgImageFile);

        if ($this->orgImageType) {
            $getImage             = $this->_getImageSize($this->orgImageFile);
            $this->orgImageWidth  = $getImage[0];
            $this->orgImageHeight = $getImage[1];
            $this->orgImage       = $this->_imageCreateFromFile($this->orgImageFile);

            if ($this->orgImage) return true;

            $this->imageCheck = 0;
            $this->_resetVariables();
            return false;
        }

        $this->imageCheck = 0;
        $this->_resetVariables();
        return false;
    }


    /**
     * Add Background Layer
     *
     * This scales the image to a size that it fits into the rectangle defined by width $width and height $height.
     * Spaces at the edges will be padded with the color $bgColor.
     * @param   array   $bgColor is an array containing 3 values, representing the red, green and blue portion (0-255) of the desired color.
     * @param   integer The width of the rectangle
     * @param   integer The height of the rectangle
     */
    function addBackgroundLayer($bgColor, $width=null, $height=null)
    {
        if (function_exists ("imagecreatetruecolor")) {
            $this->newImage = imagecreatetruecolor($width, $height);
            // GD > 2 check
            if (!$this->newImage) {
                $this->newImage = ImageCreate($this->newImageWidth, $this->newImageHeight);
            }
        } else {
            $this->newImage = ImageCreate($width, $height);
        }
        imagefill($this->newImage, 0, 0,
            imagecolorallocate($this->newImage,
                $bgColor[0], $bgColor[1], $bgColor[2]));
        $ratio = max($this->orgImageWidth / $width, $this->orgImageHeight / $height);
        $scaledWidth = $this->orgImageWidth / $ratio;
        $scaledHeight = $this->orgImageHeight / $ratio;
        $offsetWidth = ($width - $scaledWidth) / 2;
        $offsetHeight = ($height - $scaledHeight) / 2;
        imagecopyresized($this->newImage, $this->orgImage,
            $offsetWidth, $offsetHeight, 0, 0,
            $scaledWidth, $scaledHeight,
            $this->orgImageWidth, $this->orgImageHeight);
        $this->imageCheck = 1;
        $this->newImageQuality = 100;
        $this->newImageType = $this->orgImageType;
    }


    /**
     * Creates a thumbnail of a picture
     *
     * Note that all "Path" parameters are required to bring along their
     * own trailing slash.
     *
     * @param   string $strPath
     * @param   string $strWebPath
     * @param   string $file
     * @param   int    $maxSize The maximum width or height of the image
     * @param   int    $quality
     * @param string   $thumb_name
     * @param bool     $generateThumbnailByRatio
     *
     * @return bool
     */
    function _createThumb($strPath, $strWebPath, $file, $maxSize=80, $quality=90, $thumb_name='', $generateThumbnailByRatio = false)
    {
        $_objImage = new ImageManager();
        $file      = basename($file);
        $tmpSize   = getimagesize($strPath.$file);
        $factor = 1;
        if ($tmpSize[0] > $tmpSize[1]) {
           $factor = $maxSize / $tmpSize[0];
        } else {
           $factor = $maxSize / $tmpSize[1] ;
        }
        $thumbWidth  = $tmpSize[0] * $factor;
        $thumbHeight = $tmpSize[1] * $factor;
        if (!$_objImage->loadImage($strPath.$file)) return false;

        if ($generateThumbnailByRatio && !$_objImage->resizeImageWithAspectRatio($thumbWidth, $thumbHeight, $quality)) {
            return false;
        } elseif (!$generateThumbnailByRatio && !$_objImage->resizeImage($thumbWidth, $thumbHeight, $quality)) {
            return false;
        }

        if (!(strlen($thumb_name) > 0)) {
            $thumb_name = self::getThumbnailFilename($file);
        }
        if (!$_objImage->saveNewImage($strPath.$thumb_name, false, false)) return false;
        if (!\Cx\Lib\FileSystem\FileSystem::makeWritable($strPath.$thumb_name)) return false;
        return true;
    }


    /**
     * Create a thumbnail of a picture.
     *
     * This is very much like {@link _createThumb()}, but provides more
     * parameters.  Both the width and height of the thumbnail may be
     * specified; the picture will still be scaled to fit within the given
     * sizes while keeping the original width/height ratio.
     * In addition to that, this method tries to delete an existing
     * thumbnail before attempting to write the new one.
     * Note that all "Path" parameters are required to bring along their
     * own trailing slash.
     *
     * @param   string  $strPath        The image file folder
     * @param   string  $strWebPath     The image file web folder
     * @param   string  $file           The image file name
     * @param   integer $maxWidth       The maximum width of the image
     * @param   integer $maxHeight      The maximum height of the image
     * @param   integer $quality        The desired jpeg thumbnail quality
     * @param   string  $thumbNailSuffix  Suffix of the thumbnail. Default is 'thumb'
     * @param   string  $strPathNew     Image file store folder. Default is $strPath
     * @param   string  $strWebPathNew  Image file web store folder. Default is $strWebPath
     * @param   string  $fileNew        Image file store name. Default is $file
     * @return  bool                    True on success, false otherwise.
     */
    function _createThumbWhq(
        $strPath, $strWebPath, $file, $maxWidth=80, $maxHeight=80, $quality=90,
        $thumbNailSuffix='.thumb', $strPathNew=null, $strWebPathNew=null, $fileNew=null
    ) {
        // Do *NOT* strip subfolders from the file name!
        // This would break correct operation in some places (shop)
        // Fix your own code to provide the file name only if you need to.
        //$file      = basename($file);
        if (empty($fileNew))       $fileNew       = $file;
        if (empty($strPathNew))    $strPathNew    = $strPath;
        if (empty($strWebPathNew)) $strWebPathNew = $strWebPath;
        $tmpSize = getimagesize($strPath.$file);
        // reset the ImageManager
        $this->imageCheck = 1;
        $width       = $tmpSize[0];
        $height      = $tmpSize[1];
        $widthRatio  = $width/$maxWidth;
        $heightRatio = $height/$maxHeight;
        $thumbWidth  = 0;
        $thumbHeight = 0;
        if ($widthRatio < $heightRatio) {
            $thumbHeight = $maxHeight;
            $thumbWidth  = $width*$maxHeight/$height;
        } else {
            $thumbWidth  = $maxWidth;
            $thumbHeight = $height*$maxWidth/$width;
        }
        if (!$this->loadImage($strPath.$file)) return false;
        if (!$this->resizeImage($thumbWidth, $thumbHeight, $quality)) return false;
        if (is_file($strPathNew.$fileNew.$thumbNailSuffix)) {
            if (!\Cx\Lib\FileSystem\FileSystem::delete_file($strPathNew.$fileNew.$thumbNailSuffix)) return false;
        }
        if (!$this->saveNewImage($strPathNew.$fileNew.$thumbNailSuffix, false, false)) return false;
        if (!\Cx\Lib\FileSystem\FileSystem::makeWritable($strPathNew.$fileNew.$thumbNailSuffix)) return false;
        return true;
    }


    /**
     * Resizes the original image to the given dimensions and stores it as a new.
     * This method does not use exceptions because it is called by older methods without catchers.
     *
     * @access  public
     * @param   string   $width    The width of the new image.
     * @param   string   $height   The height of the new image.
     * @param   string   $quality  The quality for the new image.
     * @return  booelan            True on success, false otherwise.
     */
    public function resizeImage($width, $height, $quality)
    {
        if (!$this->imageCheck) return false;

        //Create a new image for given size
        $this->createNewImageForResize($width, $height, $quality);
        if (function_exists('imagecopyresampled')) { //resampled is gd2 only
            imagecopyresampled($this->newImage, $this->orgImage, 0, 0, 0, 0, $this->newImageWidth, $this->newImageHeight, $this->orgImageWidth, $this->orgImageHeight);
        } else {
            imagecopyresized($this->newImage, $this->orgImage, 0, 0, 0, 0, $this->newImageWidth, $this->newImageHeight, $this->orgImageWidth, $this->orgImageHeight);
        }

    if ($this->newImage) {
            return true;
        }
        return false;
    }

    /**
     * create new image for resize
     *
     * @param   string   $width    The width of the new image.
     * @param   string   $height   The height of the new image.
     * @param   string   $quality  The quality for the new image.
     */
    public function createNewImageForResize($width, $height, $quality, $setWhiteBackground = false)
    {
        if ($this->newImage) {
            $this->orgImage       = $this->newImage;
            $this->orgImageWidth  = $this->newImageWidth;
            $this->orgImageHeight = $this->newImageHeight;
        }

        $this->newImageWidth = $width;
        $this->newImageHeight = $height;
        $this->newImageQuality = $quality;
        $this->newImageType = $this->orgImageType;

        if (function_exists('imagecreatetruecolor')) {
            $this->newImage = @imagecreatetruecolor($this->newImageWidth, $this->newImageHeight);
            // GD > 2 check
            if ($this->newImage) {
                if ($setWhiteBackground) {
                    imagefill($this->newImage, 0, 0, imagecolorallocate($this->newImage, 255, 255, 255));
                } else {
                    $this->setTransparency();
                }
            } else {
                $this->newImage = imagecreate($this->newImageWidth, $this->newImageHeight);
            }
        } else {
            $this->newImage = imagecreate($this->newImageWidth, $this->newImageHeight);
        }
    }

    /**
     * Resize the image with aspect ratio and fill the white color in empty area
     *
     * @param   string   $width    The width of the new image.
     * @param   string   $height   The height of the new image.
     * @param   string   $quality  The quality for the new image.
     *
     * @return boolean
     */
    public function resizeImageWithAspectRatio($width, $height, $quality)
    {
        if (!$this->imageCheck) return false;

        $setWhiteBackground = ($width  > $this->orgImageWidth) ? true : false;
        //Create a new image for given size
        $this->createNewImageForResize($width, $height, $quality, $setWhiteBackground);

        $dstX      = ($this->newImageWidth  > $this->orgImageWidth) ? round(($this->newImageWidth - $this->orgImageWidth) / 2) : 0;
        $dstY      = ($this->newImageHeight > $this->orgImageHeight) ? round(($this->newImageHeight - $this->orgImageHeight) / 2) : 0;
        $newWidth  = ($this->newImageWidth  > $this->orgImageWidth) ? $this->orgImageWidth : $this->newImageWidth;
        $newHeight = ($this->newImageHeight > $this->orgImageHeight) ? $this->orgImageHeight : $this->newImageHeight;

        if (function_exists('imagecopyresampled')) { //resampled is gd2 only
            imagecopyresampled($this->newImage, $this->orgImage, $dstX, $dstY, 0, 0, $newWidth, $newHeight, $this->orgImageWidth, $this->orgImageHeight);
        } else {
            imagecopyresized($this->newImage, $this->orgImage, $dstX, $dstY, 0, 0, $newWidth, $newHeight, $this->orgImageWidth, $this->orgImageHeight);
        }

        if ($this->newImage) {
            return true;
        }
        return false;
    }


    /**
     * Add transparency to new image
     *
     * Define a color as transparent or add alpha channel,
     * depending on the image file type.
     *
     * @access private
     * @return void
     */
    private function setTransparency()
    {
        switch ($this->orgImageType) {
            case self::IMG_TYPE_GIF:
                $transparentColorIdx = imagecolortransparent($this->orgImage);
                if ($transparentColorIdx >= 0) {
                    //its transparent
                    $transparentColor = imagecolorsforindex($this->orgImage, $transparentColorIdx);
                    $transparentColorIdx = imagecolorallocate($this->newImage, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue']);
                    imagefill($this->newImage, 0, 0, $transparentColorIdx);
                    imagecolortransparent($this->newImage, $transparentColorIdx);
                }
                break;
            case self::IMG_TYPE_PNG:
                imagealphablending($this->newImage, false);
                $colorTransparent = imagecolorallocatealpha($this->newImage, 0, 0, 0, 127);
                imagefill($this->newImage, 0, 0, $colorTransparent);
                imagesavealpha($this->newImage, true);
                break;
        }
    }


    /**
     * Resize and save image
     *
     * Resizes the loaded Image as you wish and saves the new image.
     *
     * @access  public
     * @param   string  $path         Absolute path to the directory where the image is in
     * @param   string  $webPath      Absolute webpath (in the directory root of the webserver)
     * @param   string  $fileName     FileName of the image that needs to be resized
     * @param   string  $width        Width of the new image
     * @param   string  $height       Height of the new image
     * @param   string  $quality      Quality for the new image
     * @param   string  $newPath      Absolute path to the directory where the image is in (leave empty if same as original)
     * @param   string  $newWebPath   Absolute webpath (in the directory root of the webserver, leave empty if same as original)
     * @param   string  $newFileName  FileName of the image that needs to be resized (leave empty to create default $file.'.thumb')
     * @return  bool
     */
    function resizeImageSave(
        $path, $webPath, $fileName, $width, $height, $quality=70,
        $newPath='', $newWebPath='', $newFileName='', $thumbNailSuffix='.thumb'
    ) {
        // If empty, use the original image path for the new image
        if ($newPath == '') $newPath = $path;
        if ($newWebPath == '') $newWebPath = $webPath;
        if ($newFileName == '') $newFileName = $fileName.$thumbNailSuffix;

        $this->_checkTrailingSlash($path);
        $this->_checkTrailingSlash($webPath);
        $this->_checkTrailingSlash($newPath);
        $this->_checkTrailingSlash($newWebPath);

        $this->loadImage($path.$fileName);
        if (!$this->imageCheck) return false;

        $this->newImageWidth = $width;
        $this->newImageHeight = $height;
        $this->newImageQuality = $quality;
        $this->newImageType = $this->orgImageType;

        if (function_exists ('imagecreatetruecolor')) {
            $this->newImage = @imagecreatetruecolor($this->newImageWidth, $this->newImageHeight);
            // GD > 2 check
            if ($this->newImage) {
                $this->setTransparency();
            } else {
                $this->newImage = ImageCreate($this->newImageWidth, $this->newImageHeight);
            }
        } else {
            $this->newImage = ImageCreate($this->newImageWidth, $this->newImageHeight);
        }

        imagecopyresized(
            $this->newImage, $this->orgImage,
            0, 0, 0, 0,
            $this->newImageWidth+1, $this->newImageHeight+1,
            $this->orgImageWidth, $this->orgImageHeight
        );

        return $this->saveNewImage($newPath.$newFileName);
    }


    /**
     * Add a trailing slash to the given path if there's none already
     *
     * The parameter is given by reference.
     * @param   string  $path   The directory path
     * @return  void
     */
    function _checkTrailingSlash(&$path)
    {
        // add directory separator if not already provided
        if (substr($path, -1) != DIRECTORY_SEPARATOR)
            $path .= DIRECTORY_SEPARATOR;
    }


    /**
     * Saves the new image wherever you want
     *
     * @todo    In case the PHP script has no write access to the location set by $this->newImageFile,
     *          the image shall be sent to the output buffer and then be put into the new file
     *          location using the FileSystemFile object.
     * @todo    Check for all usages of this method if argument
     *          $injectTransparency must be set to FALSE.
     * @access  public
     * @param   string    $file             The path for the image file to be written.
     * @param   booelan   $forceOverwrite   Force overwriting existing files if true.
     * @param   booelan   $injectTransparency Whether or not to set transparency
     *                                      info on new image.
     * @return  boolean                     True on success, false otherwise.
     */
    public function saveNewImage($file, $forceOverwrite=false, $injectTransparency = true)
    {
// TODO: Add some sort of diagnostics (Message) here and elsewhere in this class
        if (!$this->imageCheck) return false;
        if (empty($this->newImage)) return false;
        if (file_exists($file)) {
            if (!$forceOverwrite) {
                return false;
            }
            \Cx\Lib\FileSystem\FileSystem::makeWritable($file);
        } else {
            try {
                $objFile = new \Cx\Lib\FileSystem\File($file);
                $objFile->touch();
            } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                \DBG::msg($e->getMessage());
            }
        }
// TODO: Unfortunately, the functions imagegif(), imagejpeg() and imagepng() can't use the Cloudrexx FileSystem wrapper,
//       therefore we need to set the global write access image files.
//       This issue might be solved by using the output-buffer and write the image manually afterwards.
//
//       IMPORTANT: In case something went wrong (see bug #1441) and the path $strPathNew.$strFileNew refers to a directory
//       we must abort the operation here, otherwise we would remove the execution flag on a directory, which would
//       cause to remove any browsing access to the directory.
        if (is_dir($file)) {
            return false;
        }
        \Cx\Lib\FileSystem\FileSystem::chmod($file, 0666);//\Cx\Lib\FileSystem\FileSystem::CHMOD_FILE);

        $this->newImageFile = $file;

        if (
            $injectTransparency &&
            $this->newImageType == self::IMG_TYPE_PNG
        ) {
            $this->setTransparency();
        }

        switch($this->newImageType) {
            case self::IMG_TYPE_GIF:
                $function = 'imagegif';
                if (!function_exists($function)) {
                    $function = 'imagejpeg';
                }
                break;
            case self::IMG_TYPE_JPEG:
                $function = 'imagejpeg';
                break;
            case self::IMG_TYPE_PNG:  // make a jpeg thumbnail, too
                $function = 'imagepng';
                break;
            default:
                return false;
        }


        // Only adjust quality, if it is set.
        if ($this->newImageQuality != '') {
            $function($this->newImage, $this->newImageFile, $this->getQuality());
        } else {
            $function($this->newImage, $this->newImageFile);
        }

        return true;
    }


    /**
     * Returns the proper quality value for creating an image of the
     * current type
     *
     * The value returned is the original value set, except for type PNG.
     * The latter requires the quality to be in the range [0..9] instead
     * of [0..100].
     * @return  integer             The quality, scaled for the current type
     */
    function getQuality()
    {
        switch($this->newImageType) {
            case self::IMG_TYPE_PNG:
                return min(9, intval($this->newImageQuality / 10));
        }
        return $this->newImageQuality;
    }


    /**
     * Outputs the new image in the browser
     * @access   public
     * @return   boolean      True on success, false otherwise
     */
    function showNewImage()
    {
        $this->newImage     = !empty($this->newImage)     ? $this->newImage     : $this->orgImage;
        $this->newImageType = !empty($this->newImageType) ? $this->newImageType : $this->orgImageType;

        if (!$this->imageCheck == 1) return false;
        if (empty($this->newImage)) return false;

        if ($this->newImageType == self::IMG_TYPE_PNG) {
            $this->setTransparency();
        }

        switch($this->newImageType) {
            case self::IMG_TYPE_GIF:
                header("Content-type: image/gif");
                $function = 'imagegif';
                if (!function_exists($function)) {
                    $function = 'imagejpeg';
                }
                break;
            case self::IMG_TYPE_JPEG:
                header("Content-type: image/jpeg");
                $function = 'imagejpeg';
                break;
            case self::IMG_TYPE_PNG:
                header("Content-type: image/png");
                $function = 'imagepng';
                break;
            default:
                return false;
        }
        // Only adjust quality, if it is set.
        if ($this->newImageQuality != '') {
            $function($this->newImage, null, $this->getQuality());
        } else {
            $function($this->newImage);
        }
        return true;
    }


    /**
     * Resets all object variables.
     *
     * @access   private
     * @return   void
     */
    function _resetVariables()
    {
        $this->orgImage        = '';
        $this->orgImageWidth   = '';
        $this->orgImageHeight  = '';
        $this->orgImageType    = '';
        $this->orgImageFile    = '';

        $this->newImage        = '';
        $this->newImageWidth   = '';
        $this->newImageHeight  = '';
        $this->newImageQuality = 100;
        $this->newImageType    = '';
        $this->newImageFile    = '';
        $this->newImagePosX    = '';
        $this->newImagePosY    = '';
    }


    /**
     * Scale down the original image dimensions to a given maximum pixel size
     *
     * Proportionally scales the width and height, so that both fit within
     * the maximum size.
     * The array returned looks like
     *  array(
     *    'style'   => 'style="height: <scaled_height>px; width: <scaled_width>px;"',
     *    'width'   => original width plus one in pixels,
     *    'height'  => original height pluis one in pixels,
     *  )
     * @todo    Reto asks:  Who wrote this?
     * @todo    Reto asks:  Why is 1 added to the original size?
     * @todo    The $webPath argument is not used.  Remove.
     * @todo    It looks like this method isn't used at all.  Remove.
     * @param   string  $path       The absolute file path
     * @param   string  $webPath    The file path relative to the document root
     * @param   string  $fileName   The file name
     * @param   integer $max        The maximum size for both width and height
     * @return  array               An array of image dimensions on success,
     *                              null otherwise
     * @author  reto.kohli@comvation.com (Added proper documentation, fixed)
     */
    function getImageDim($path, $webPath, $fileName, $max=60)
    {
        $this->_checkTrailingSlash($path);
        if (!is_file($path.$fileName)) return false;
        $size   = getimagesize($path.$fileName);
        $height = $size[1];
        $width  = $size[0];
        $imgdim = null;
        if ($height > $max && $height > $width) {
            $height = $max;
            $ratio  = ($size[1] / $height);
            $width  = ($size[0] / $ratio);
        } else if ($width > $max) {
            $width  = $max;
            $ratio  = ($size[0] / $width);
            $height = ($size[1] / $ratio);
        }
        if ($width > 0 && $height > 0) {
            $imgdim['style']  = 'style="height: '.$height.'px; width:'.$width.'px;"';
            $imgdim['width']  = $size[0]+1;
            $imgdim['height'] = $size[1]+1;
        }
        return $imgdim;
    }


    /**
     * Creates an image from an image file
     * @access   private
     * @param    string   $file   The path of the image
     * @return   resource         The image on success, the empty string otherwise
     */
    function _imageCreateFromFile($file)
    {
        $arrSizeInfo = getimagesize($file);
        if (!is_array($arrSizeInfo)) return false;

        // fetch channels of image
        if (
            !isset($arrSizeInfo['channels']) ||
            $arrSizeInfo['channels'] < 3
        ) {
            $channels = 3;
        } else {
            $channels = $arrSizeInfo['channels'];
        }

        // fetch bits per pixel of image
        if (!isset($arrSizeInfo['bits'])) {
            $bits = 8;
        } else {
            $bits = $arrSizeInfo['bits'];
        }

        $type = $this->_isImage($file);

        // see comments of https://www.php.net/manual/en/function.imagecreatefromjpeg.php
        $fudgeFactor = 2.2;

        // width * height * "fudge factor"
        $potentialRequiredMemory = $arrSizeInfo[0] * $arrSizeInfo[1] * $fudgeFactor;

        // multiply required memory by number of bits and channels per pixel
        // depending on image type
        switch($type) {
            case self::IMG_TYPE_GIF:
                $function = 'imagecreatefromgif';
                break;
            case self::IMG_TYPE_JPEG:
                $function = 'imagecreatefromjpeg';
                $potentialRequiredMemory *= $bits / 8 * $channels;
                break;
            case self::IMG_TYPE_PNG:
                $function = 'imagecreatefrompng';
                $potentialRequiredMemory *= $bits;
                break;
            default:
                return '';
        }

        // round potential required memory to int
        $potentialRequiredMemory = round($potentialRequiredMemory);

        require_once(ASCMS_FRAMEWORK_PATH.'/System.class.php');
        $objSystem = new FWSystem();
        if ($objSystem === false) return false;
        $memoryLimit = $objSystem->getBytesOfLiteralSizeFormat(
            @ini_get('memory_limit'));
        if (empty($memoryLimit)) {
            // set default php memory limit of 8 MBytes
            $memoryLimit = 8 * pow(1024, 2);
        }
        // fetch already used memory
        if (function_exists('memory_get_usage')) {
            $potentialRequiredMemory += memory_get_usage();
        } else {
            // as we are unable to fetch the currently used memory,
            // we do continue our calculation with an expected
            // amount of used memory of 32 MB
            $potentialRequiredMemory += 32 * pow(1024, 2);
        }
        if ($potentialRequiredMemory > $memoryLimit) {
            // try to set a higher memory_limit
            if (   !ini_set('memory_limit', $potentialRequiredMemory)
                || $memoryLimit == $objSystem->getBytesOfLiteralSizeFormat(ini_get('memory_limit')))
                return '';
        }
        return $function($file);
    }


    /**
     * Returns the image type as determined by getimagesize() for the given file.
     *
     * Only accepts web image types (GIF, JPG, or PNG).
     * If the function imagecreatefromgif() is not available, GIF images aren't
     * accepted.
     * False is returned for images/files that are not supported.
     * @access    private
     * @param     string    $file   The file path of the image
     * @return    integer           The file type on success, false otherwise
     */
    function _isImage($file)
    {
        if (is_dir($file)) return false;

// TODO: merge this function with isImage of lib/FRAMEWORK/Image.class.php
        if (class_exists('finfo', false)) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
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

    /**
     * Gets the size of the image
     * @access    private
     * @param     string    $file   The path of the image
     * @return    array             The array as returned by @getimagesize($file)
     *                              on success, false otherwise
     * @todo      This method is completely redundant.  It does exactly the same
     *            as calling @getimagesize($file) directly! - Remove.
     */
    function _getImageSize($file)
    {
        $getImageSize = @getimagesize($file);
        if ($getImageSize) return $getImageSize;
        return false;
    }


    /**
     * Returns the file name for the thumbnail image
     *
     * Replaces the .png extension with .jpg if a thumbnail in PNG format is
     * already present.  This is for backwards compatibility with versions up
     * to 2.2 which did not create PNG thumbnails.
     * Appends the .thumb extension if not already present.
     * @param   string    $file_name        The image file name
     * @return  string                      The thumbnail file name
     */
    static function getThumbnailFilename($file_name)
    {
        $thumbnail = Cx::instanciate()
            ->getMediaSourceManager()
            ->getThumbnailGenerator()
            ->getThumbnailFilename($file_name);
        return $thumbnail;
    }


    /**
     * Rotates the image 90 degrees to the left or right.
     *
     * @access  public
     * @param   float   $angle  Rotation angle, in degrees. The rotation angle is interpreted as the number of degrees to rotate the image anticlockwise.
     * @return  bool            True on success, false otherwise.
     */
    public function rotateImage($angle)
    {
        if ($this->imageCheck == 1) {
            $angle = ($angle <= 360) || ($angle >= 0) ? $angle : 0;
            $this->newImage = imagerotate($this->orgImage, $angle, 0);

            if ($this->newImage) {
                $this->setTransparency();

                $this->newImageWidth = imagesx($this->newImage);
                $this->newImageHeight = imagesy($this->newImage);
                $this->newImageType  = $this->orgImageType;

                return true;
            }

            throw new Exception('Could not rotate image');
        }

        throw new Exception('Is not a valid image');
    }


    /**
     * Crops the image with the given coordinates.
     *
     * @access  public
     * @param   int   $x       X-coordinate for the new image.
     * @param   int   $y       Y-coordinate for the new image.
     * @param   int   $width   Width for the new image.
     * @param   int   $height  Height for the new image.
     * @return  bool           True on success, false otherwise.
     */
    public function cropImage($x, $y, $width, $height)
    {
        if ($this->imageCheck == 1) {
            $this->newImagePosX   = $x;
            $this->newImagePosY   = $y;
            $this->newImageWidth  = $width;
            $this->newImageHeight = $height;
            $this->newImageType   = $this->orgImageType;

            if ($this->newImage) {
                $this->orgImage = $this->newImage;
            }

            if (function_exists('imagecreatetruecolor')) {
                $this->newImage = @imagecreatetruecolor($this->newImageWidth, $this->newImageHeight);
                // GD > 2 check
                if ($this->newImage) {
                    $this->setTransparency();
                } else {
                    $this->newImage = imagecreate($this->newImageWidth, $this->newImageHeight);
                }
            } else {
                $this->newImage = imagecreate($this->newImageWidth, $this->newImageHeight);
            }

            imagecopy (
                $this->newImage,      // Source of new image
                $this->orgImage,      // Source of original image
                0,                    // X-coordinate of new image
                0,                    // Y-coordinate of new image
                $this->newImagePosX,  // X-coordinate of original image
                $this->newImagePosY,  // Y-coordinate of original image
                $this->newImageWidth, // New image width
                $this->newImageHeight // New image height
            );

            if (!empty($this->newImage)) {
                return true;
            }

            throw new Exception('Could not crop image');
        }

        throw new Exception('Is not a valid image');
    }

    /**
     * Fix image orientation based on its Exif-data
     *
     * @param string $filePath File path
     */
    public function fixImageOrientation($filePath)
    {
        if (!function_exists('exif_read_data')) {
            return;
        }

        // The method exif_read_data() reads a header information from an image.
        // It returns an associated array where the array indexes are the header
        // names and the array values are the values associated with those
        // headers.
        // If no data can be returned, exif_read_data() will return FALSE.
        $exif = exif_read_data($filePath);
        if (empty($exif['Orientation'])) {
            return;
        }

        $this->loadImage($filePath);
        // The information from the accelerometer is stored in the orientation
        // field of the Exchangeable Image File Format (Exif) metadata.
        // If the image has rotated, Exif data should have 'Orientation' in its
        // header information. The orientation is one of 3, 6 or 8. Here
        // orientation 3 will rotate 180 degree left, orientation 6 will
        // rotate 90 degree right and orientation 8 will rotate 90 degree left.
        // For more information about the Orientation image data structure
        // property see:
        // http://www.digicamsoft.com/exif22/exif22/html/exif22_24.htm?gInitialPosX=10px&gInitialPosY=10px&gZoomValue=100
        // or check http://www.cipa.jp for the latest Exif standard doc
        try {
            // Note that $this->rotateImage() does rotate the image anticlockwise
            switch ($exif['Orientation']) {
                case 3:
                    $this->rotateImage(180);
                    break;
                case 6:
                    $this->rotateImage(-90);
                    break;
                case 8:
                    $this->rotateImage(90);
                    break;
                default :
                    return;
            }
            $this->saveNewImage($filePath, true);
        } catch (\Exception $ex) {
            \DBG::msg($ex->getMessage());
        }
    }
}
