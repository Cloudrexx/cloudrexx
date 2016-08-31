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
 * Image handling
 *
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  core
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 */

/**
 * Image
 *
 * Includes access methods and data layer.
 * Do not, I repeat, do not access private fields, or even try
 * to access the database directly!
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  core
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 */
class Image
{
    /**
     * The icon URI for the "remove the current image" link
     */
    const ICON_CLEAR_IMAGE_SRC = 'images/modules/hotelcard/clear_image.gif';

    /**
     * The default "no image" URI
     */
    const PATH_NO_IMAGE = 'images/modules/hotelcard/no_image.gif';

    /**
     * Size limit in bytes for images being uploaded or stored
     *
     * A little over 300 KB is okay.
     */
    const MAXIMUM_UPLOAD_FILE_SIZE = 310000;

    /**
     * Thumbnail suffix
     */
    const THUMBNAIL_SUFFIX = '.thumb';

    /**
     * Array of all file extensions accepted as image files
     * @var   array
     */
    private static $arrAcceptedFiletype = array(
        'gif', 'jpg', 'png',
    );

    /**
     * @var     integer         $id               The object ID, PRIMARY
     * @access  private
     */
    private $id = false;

    /**
     * The ordinal number
     * @var     integer         $ord              The ordinal number, PRIMARY
     * @access  private
     */
    private $ord = 0;

    /**
     * @var     integer   $imagetype_key    The image type key
     * @access  private
     */
    private $imagetype_key = false;

    /**
     * @var     integer         $filetype_key    The file type key
     * @access  private
     */
    private $filetype_key = false;

    /**
     * @var     string          $path            The image file path
     * @access  private
     */
    private $path = false;

    /**
     * The image width
     * @var     integer         $width            The image width
     * @access  private
     */
    private $width = false;

    /**
     * The image height
     * @var     integer         $height          The image height
     * @access  private
     */
    private $height = false;


    /**
     * Create an Image
     *
     * Note that the optional $image_id argument *SHOULD NOT* be used when
     * adding the first Image to another object, but only to ensure that
     * additional Images with different ordinals are added to the same ID.
     * @access  public
     * @param   integer       $ord              The ordinal number
     * @param   integer       $image_id          The optional Image ID
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function __construct($ord=0, $image_id=0)
    {
        $this->ord = (empty($ord)      ? 0 : $ord);
        $this->id =  (empty($image_id) ? 0 : $image_id);
    }


    /**
     * Get the ID
     * @return  integer                             The object ID
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function getId()
    {
        return $this->id;
    }
    /**
     * Set the ID -- NOT ALLOWED
     * See {@link Image::makeClone()}
     */

    /**
     * Get the ordinal number
     * @return  integer                              The ordinal number
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function getOrd()
    {
        return $this->ord;
    }
    /**
     * Set the ordinal number
     *
     * Note that this value is non-negative,
     * negative numbers have their sign stripped here.
     * @param   integer          $ord               The ordinal number
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function setOrd($ord)
    {
        $this->ord = abs($ord);
    }

    /**
     * Get the type ID
     * @return  integer                           The type ID
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function getImageTypeKey()
    {
        return $this->imagetype_key;
    }
    /**
     * Set the type ID
     *
     * Any non-positive value or string will be interpreted as NULL
     * @param   integer          $imagetype_key  The type ID
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function setImageTypeKey($imagetype_key)
    {
        $this->imagetype_key = (empty($imagetype_key) ? 'NULL' : $imagetype_key);
    }

    /**
     * Get the file type key
     * @return  integer                              The file type key
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function getFileTypeKey()
    {
        return $this->filetype_key;
    }
    /**
     * Set the file type key
     *
     * Any non-positive value or string will be interpreted as NULL
     * @param   integer          $filetype_key       The file type key
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function setFileTypeKey($filetype_key)
    {
        $this->filetype_key = (empty($filetype_key) ? 'NULL' : $filetype_key);
    }

    /**
     * Get the path
     *
     * Note that the path is stored relative to ASCMS_DOCUMENT_ROOT,
     * with ASCMS_PATH_OFFSET, any path separator following it,
     * and everything before that cut off!
     * @return  string                           The path
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function getPath()
    {
        return $this->path;
    }
    /**
     * Set the path
     *
     * Note that the path is stored relative to ASCMS_DOCUMENT_ROOT,
     * with ASCMS_PATH_OFFSET, any path separator following it,
     * and everything before that cut off!
     * @param   string          $path         The path
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function setPath($path)
    {
// Necessary, as the path may be posted from the backend, with the
// ASCMS_PATH_OFFSET prepended!
        File::path_relative_to_root($path);
// TODO: Use the inverse of self::getThumbnailPath()
        $path = preg_replace('/\.thumb$/', '', $path);
        if ($path == self::PATH_NO_IMAGE) {
            $this->path = '';
        } else {
            $this->path = strip_tags($path);
        }
    }

    /**
     * Get the width
     * @return  integer                              The width
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function getWidth()
    {
        return $this->width;
    }
    /**
     * Set the width
     * @param   integer          $width               The width
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function setWidth($width)
    {
        $this->width = (intval($width) > 0 ? $width : 0);
    }

    /**
     * Get the height
     * @return  integer                              The height
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function getHeight()
    {
        return $this->height;
    }
    /**
     * Set the height
     * @param   integer          $height               The height
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function setHeight($height)
    {
        $this->height = (intval($height) > 0 ? $height : 0);
    }


    /**
     * Determine the Image dimensions
     * @return  boolean               True on success, false otherwise
     */
    function autoSize()
    {
        $size = getimagesize(ASCMS_DOCUMENT_ROOT.'/'.$this->path);
        if ($size && $size[0] && $size[1]) {
            $this->width = $size[0];
            $this->height = $size[1];
            return true;
        }
        return false;
    }


    /**
     * Returns an array with the size of the Image
     *
     * Uses the same indices for width (0), height (1),
     * and the height/width text string (3) as getimagesize().
     * @return  array                     The Image size
     */
    function getSizeArray()
    {
        return array(
            $this->width, $this->height, null,
            'width="'.$this->width.'" height="'.$this->height.'"'
        );
    }


    /**
     * Returns an array with the size of the Image thumbnail
     *
     * Uses the same indices for width (0), height (1),
     * and the height/width text string (3) as getimagesize().
     * @return  array                     The Image thumbnail size
     */
    function getSizeArrayThumbnail()
    {
        $width_max = \Cx\Core\ImageType\Controller\ImageType::getWidthThumbnail($this->imagetype_key);
        $height_max = \Cx\Core\ImageType\Controller\ImageType::getHeightThumbnail($this->imagetype_key);
        return self::getScaledSize(
            $this->getSizeArray(), $width_max, $height_max);
    }


    /**
     * Clone the object
     *
     * Note that this does NOT create a copy in any way, but simply clears
     * the object ID.  Upon storing this object, a new ID is created.
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function makeClone()
    {
        $this->id = 0;
    }


    /**
     * Replace the image path for the given object ID and ordinal number.
     *
     * If no object with that ID and ordinal can be found, creates a new one.
     * In that case, the $imagetype_key parameter must be non-empty.
     * @param   integer     $image_id       The object ID
     * @param   integer     $ord            The ordinal number
     * @param   string      $path           The path
     * @param   integer     $imagetype_key  The image type key
     * @param   integer     $width          The optional width, overrides automatic value
     * @param   integer     $ord            The optional height, overrides automatic value
     * @return  boolean                     True on success, false otherwise
     */
    function replace($image_id, $ord, $path, $imagetype_key='', $width=false, $height=false)
    {
//echo("Image::replace($image_id, $ord, $path, $imagetype_key, $width, $height): Entered<br />");
        $objImage = self::getById($image_id, $ord);
        if (!$objImage && empty($imagetype_key)) {
//echo("Image::replace(): Image not found and empty key<br />");
            return false;
        }
        if (!$objImage) $objImage = new Image($ord);

        File::clean_path($path);
        $imageSize = getimagesize(ASCMS_DOCUMENT_ROOT.'/'.$path);
        if ($width === false || $height === false) {
            $width = $imageSize[0];
            $height = $imageSize[1];
//echo("Image::replace(): Image size: $width/$height<br />");
        }
        $path_parts = pathinfo($path);

// TODO:  Debug stuff, remove in release
//        $auto_type = $imageSize[2];
//        if ($auto_type !== strtoupper($path_parts['extension']))
//echo("Image::replace(image_id $image_id, ord $ord, path $path, imagetype_key $imagetype_key, width $width, height $height): Warning: Image extension (".$path_parts['extension'].") mismatch with type ($auto_type)<br />");
// /TODO

        if ($imagetype_key) $objImage->setTypeKey($imagetype_key);
        $objImage->setPath($path);
        $objImage->setFileTypeKey(Filetype::getTypeIdForExtension($path_parts['extension']));
        $objImage->setWidth($width);
        $objImage->setHeight($height);
//echo("Image::replace(): Storing Image<br />");
        if (!$objImage->store()) {
            return false;
        }
        return $objImage->resize();
    }


    /**
     * Delete this object from the database.
     *
     * If the $delete_files parameter is true, the file and thumbnail
     * will be deleted as well
     * @param   boolean       $delete_files   If true, the files are
     *                                        deleted, too
     * @return  boolean                       True on success, false otherwise
     * @global  ADOConnection $objDatabase    Database object
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function delete($delete_files=false)
    {
        global $objDatabase;
//DBG::log("Image::delete($delete_files): ".var_export($this, true)."<br />");
        if ($delete_files && $this->path) {
            File::delete_file($this->path);
            File::delete_file(self::getThumbnailPath($this->path));
        }
        if (!$this->id) return false;
        $objResult = $objDatabase->Execute("
            DELETE FROM ".DBPREFIX."core_image
             WHERE id=$this->id
               AND ord=$this->ord");
        if (!$objResult) return self::errorHandler();
        return true;
    }


    /**
     * Delete the Image objects selected by their ID and optional
     * ordinal number from the database.
     *
     * If you don't specify an ordinal number, this method will delete
     * any Image records with that ID.  Otherwise, only the selected
     * Image will be removed.
     * Deletes any associated files along with the database records.
     * Returns true if the Image ID specified is empty.
     * @todo        Existing thumbnails are deleted along with them.
     * @static
     * @global      mixed       $objDatabase    Database object
     * @param       integer     $image_id       The Image ID
     * @param       mixed       $ord            The optional ordinal number
     * @return      boolean                     True on success, false otherwise
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    static function deleteById($image_id, $ord=false)
    {
        global $objDatabase;

        $image_id = intval($image_id);
        if ($image_id <= 0) return true;
        $arrImages = self::getArrayById($image_id, $ord);
        if (!is_array($arrImages)) return false;

        foreach ($arrImages as $ord_delete => $objImage) {
            if ($ord !== false && $ord != $ord_delete) continue;
            if (!$objImage) continue;
            if (!$objImage->delete(true)) return false;
        }
        return true;
    }


    /**
     * Test whether a record with the ID and ordinal number of this object
     * is already present in the database.
     * @return  boolean                     True if the record exists,
     *                                      false otherwise
     * @global  mixed       $objDatabase    Database object
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function recordExists()
    {
        global $objDatabase;

        if (!$this->id) return false;
        $query = "
            SELECT 1
              FROM ".DBPREFIX."core_image
             WHERE id=$this->id
               AND ord=$this->ord";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return self::errorHandler();
        if ($objResult->EOF) return false;
        return true;
    }


    /**
     * Stores the object in the database.
     *
     * Either updates or inserts the object, depending on the outcome
     * of the call to {@link recordExists()}.
     * @return      boolean     True on success, false otherwise
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function store()
    {
        $result = false;
        if ($this->id && $this->recordExists()) {
            $result = $this->update();
        } else {
            $result = $this->insert();
        }
        return $result;
    }


    /**
     * Update this object in the database.
     * @return      integer                     The Image ID on success,
     *                                          false otherwise
     * @global      mixed       $objDatabase    Database object
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function update()
    {
        global $objDatabase;

        $query = "
            UPDATE ".DBPREFIX."core_image
               SET `imagetype_key`=".
                ($this->imagetype_key
                  ? "'".addslashes($this->imagetype_key)."'"
                  : 'NULL').",
                   `filetype_key`=".
                ($this->filetype_key
                  ? "'".addslashes($this->filetype_key)."'"
                  : 'NULL').",
                   `path`='".addslashes($this->path)."',
                   `width`=".($this->width ? $this->width : 'NULL').",
                   `height`=".($this->height ? $this->height : 'NULL')."
             WHERE `id`=$this->id
               AND `ord`=$this->ord";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return self::errorHandler();
        return $this->id;
    }


    /**
     * Insert this object into the database.
     *
     * If the ordinal value is false or negative, it is fixed to the result of
     * {@see getNextOrd()}.
     * @return      integer                     The Image ID on success,
     *                                          false otherwise
     * @global      mixed       $objDatabase    Database object
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function insert()
    {
        global $objDatabase;

        if ($this->ord === false || $this->ord < 0)
            $this->ord = self::getNextOrd($this->id);
        $query = "
            INSERT INTO ".DBPREFIX."core_image (
                ".($this->id ? '`id`, ' : '').
                "`ord`, `imagetype_key`,
                `filetype_key`, `path`,
                `width`, `height`
            ) VALUES (
                ".($this->id ? "$this->id, " : '').
                ($this->ord ? $this->ord : 0).",
                ".($this->imagetype_key
                  ? "'".addslashes($this->imagetype_key)."'" : 'NULL').",
                ".($this->filetype_key
                  ? "'".addslashes($this->filetype_key)."'" : 'NULL').",
                '".addslashes($this->path)."',
                ".($this->width ? $this->width : 'NULL').",
                ".($this->height ? $this->height : 'NULL')."
            )";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return self::errorHandler();
        if ($this->id == 0) $this->id = $objDatabase->Insert_ID();
//echo("Image::insert(): Inserted ID $this->id<br />");
        return $this->id;
    }


    /**
     * Select an object by ID from the database.
     * @static
     * @param       integer     $id             The object ID
     * @param       integer     $ord            The optional ordinal number,
     *                                          defaults to zero
     * @param       boolean     $thumbnail      If true, thumbnail versions
     *                                          are returned.
     *                                          Defaults to false
     * @return      Image                       The object on success,
     *                                          false otherwise
     * @global      mixed       $objDatabase    Database object
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    static function getById($image_id, $ord=0, $thumbnail=false)
    {
        global $objDatabase;

        if (empty($image_id)) return false;
        // This may not be what you want, but it's your fault in that case
        if (empty($ord)) $ord = 0;
        $query = "
            SELECT `imagetype_key`, `filetype_key`,
                   `path`, `width`, `height`
              FROM ".DBPREFIX."core_image
             WHERE id=$image_id
               AND ord=$ord";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return self::errorHandler();
        if ($objResult->EOF) return false;
        $objImage = new Image($ord, $image_id);
        $objImage->imagetype_key = $objResult->fields['imagetype_key'];
        $objImage->filetype_key = $objResult->fields['filetype_key'];
        $objImage->path = $objResult->fields['path'];
        $objImage->width = $objResult->fields['width'];
        $objImage->height = $objResult->fields['height'];
        if ($thumbnail) {
            $objImage->path = self::getThumbnailPath($objImage->path);
            list ($objImage->width, $objImage->height) =
                $objImage->getSizeArrayThumbnail();
        }
        return $objImage;
    }


    /**
     * Returns an array with all Images for the Image ID given.
     *
     * The array is indexed by the ordinal numbers.  If more than one image
     * is found, the array is sorted by those in ascending order.
     * The result may be limited by specifying the $key parameter.
     * False values are ignored.
     * The returned array looks like
     *  array(
     *    ord => Image,
     *    ... more ...
     *  )
     * @static
     * @param       integer     $image_id       The Image ID
     * @param       boolean     $thumbnail      If true, thumbnail versions
     *                                          are returned.
     *                                          Defaults to false
     * @param       integer     $key            The optional key
     * @return      array                       The Image array on success,
     *                                          false otherwise
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    static function getArrayById(
        $image_id, $thumbnail=false, $key=false
    ) {
        global $objDatabase;

        if (empty($image_id)) return array();
        $query = "
            SELECT `ord`
              FROM ".DBPREFIX."core_image
             WHERE id=$image_id".
               ($key !== false ? " AND `imagetype_key`='".addslashes($key)."'" : '')."
             ORDER BY `ord` ASC";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return self::errorHandler();
        $arrImage = array();
        while (!$objResult->EOF) {
            $ord = $objResult->fields['ord'];
            $objImage = self::getById($image_id, $ord, $thumbnail);
//DBG::log("Image::getArrayById(): objImage:<br />".var_export($objImage, true)."<br />");
            if ($objImage) $arrImage[$ord] = $objImage;
            $objResult->MoveNext();
        }
        return $arrImage;
    }


    /**
     * Creates both a new version of the Image and a new thumbnail
     * according to its Imagetype
     *
     * Note that zero or NULL values are ignored.
     * @return  boolean         True on success, false otherwise.
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function resize()
    {
        global $objDatabase;

        if (!$this->id) {
//DBG::log("Image::resize(): No Image ID! Failed");
            return false;
        }
        // Only applies to files that contain a
        // file name with a known extension
        if (   $this->path == ''
            || !preg_match('/\.(?:jpe?g|gif|png)$/i', $this->path)) {
//DBG::log("Image::resize(): Invalid extension: $this->path");
            return false;
        }
        // Get the thumbnail size for the associated type
        $arrInfo = \Cx\Core\ImageType\Controller\ImageType::getInfoArray($this->imagetype_key);
//DBG::log("resize(): Info: ".var_export($arrInfo, true));
        if ($arrInfo['width_thumb'] || $arrInfo['height_thumb']) {
            if (!self::scale(
                $this->path, self::getThumbnailPath($this->path), true,
                $arrInfo['width_thumb'], $arrInfo['height_thumb'],
                $arrInfo['quality_thumb']))
//DBG::log("Image::resize(): Failed to scale thumbnail");
                return false;
        }
        // Only resize the original if the width and/or height are limited
        if ($arrInfo['width'] || $arrInfo['height']) {
            $path = self::getJpegPath($this->path);
            if (!self::scale(
                $this->path, $path, true,
                $arrInfo['width'], $arrInfo['height'],
                $arrInfo['quality']))
//DBG::log("Image::resize(): Failed to scale myself");
                return false;
            // If the jpeg file name is different from the original name,
            // the original image is deleted to save disk space
            if ($this->path != $path) {
                File::delete_file($this->path);
                File::delete_file(self::getThumbnailPath($this->path));
            }
            // The path *MUST* be updated
            $this->setPath($path);
            // And so *SHOULD* the size
            $size = getimagesize(ASCMS_DOCUMENT_ROOT.'/'.$path);
            if ($size[0] && $size[1]) {
                $this->setWidth($size[0]);
                $this->setHeight($size[1]);
//DBG::log("Image::resize(): New size: {$this->getWidth()} x {$this->getHeight()}");
            }
//DBG::log("Image::resize(): returning result from store()");
            return $this->store();
        }
//DBG::log("Image::resize(): No resizing, finished successfully");
        return true;
    }


    /**
     * Create a new thumbnail for the image object
     *
     * This uses this Images' key to determine the Imagetype and its
     * default thumbnail size.
     * @return  boolean         True on success, false otherwise.
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function makeThumbnail()
    {
        global $objDatabase;

        if (!$this->id) return false;
        // Only try to create thumbs from entries that contain a
        // file name with a known extension
        if (   $this->path == ''
            || !preg_match('/\.(?:jpe?g|gif|png)$/i', $this->path)) return false;
        // Get the thumbnail size for the associated type
        $arrOptions =
            \Cx\Core\ImageType\Controller\ImageType::getThumbnailOptions($this->imagetype_key);
        return self::createThumbnail($this->path,
            $arrOptions['width'], $arrOptions['height'],
            $arrOptions['quality']);
    }


    /**
     * Returns a scaled image size array
     *
     * Uses the same indices for width (0), height (1),
     * and the height/width text string (3) as getimagesize().
     * One of $maxwidth and $maxheight may be zero, in which case it is ignored.
     * The size is then calculated to fit the other while maintaining the
     * ratio.  If both are zero, the original $size is returned unchanged.
     * @param   array       $size         The Image size array
     * @param   integer     $maxwidth     The maximum width
     * @param   integer     $maxheight    The maximum height
     * @return  array                     The scaled size array
     */
    static function getScaledSize($size, $maxwidth, $maxheight)
    {
        if ($maxwidth == 0 && $maxheight == 0) return $size;
        if ($maxwidth == 0) $maxwidth = 1e9;
        if ($maxheight == 0) $maxheight = 1e9;
        $width = $height = null;
        if ($size[0] && $size[1]) {
            $ratio    = $size[0] / $size[1];
            $maxratio = $maxwidth / $maxheight;
            if ($ratio < $maxratio) {
                $width = intval($maxheight*$ratio);
                $height = $maxheight;
            } else {
                $width = $maxwidth;
                $height = intval($maxwidth/$ratio);
            }
        } else {
            $width = $maxwidth;
            $height = $maxheight;
        }
        return array(
            $width, $height, null,
            ' width="'.$width.'" height="'.$height.'"'
        );
    }


    /**
     * Create a thumbnail of a picture.
     *
     * Both the width and height of the thumbnail may be
     * specified; the picture will still be scaled to fit within the given
     * sizes while keeping the original width/height ratio.
     * In addition to that, this method tries to delete an existing
     * thumbnail before attempting to write the new one.
     * Note that thumbnails are always created as jpeg image files!
     * @param   string  $image_path     The image file path
     * @param   integer $maxWidth       The maximum width of the thumbnail
     * @param   integer $maxHeight      The maximum height of the thumbnail
     * @param   integer $quality        The desired jpeg thumbnail quality
     * @return  bool                    True on success, false otherwise.
     * @static
     */
    static function createThumbnail(
        $image_path, $maxWidth=160, $maxHeight=120, $quality=90
    ) {
        return self::scale(
            $image_path, self::getThumbnailPath($image_path),
            true, $maxWidth, $maxHeight, $quality);
    }


    /**
     * Create a scaled version of a picture.
     *
     * Both the width and height may be specified; the picture will still
     * be scaled to fit within the given sizes while keeping the original
     * width/height ratio.
     * If $force is true, this method tries to delete an existing
     * target image before attempting to write the new one.
     * Note that scaled images are *always* created as jpeg image files!
     * @param   string  $source_path    The source image file path
     * @param   string  $target_path    The target image file path
     * @param   boolean $force          If true, the target image is forced
     *                                  to be overwritten
     * @param   integer $maxWidth       The maximum width of the image
     * @param   integer $maxHeight      The maximum height of the image
     * @param   integer $quality        The desired jpeg thumbnail quality
     * @return  bool                    True on success, false otherwise.
     * @static
     */
    static function scale(
        $source_path, $target_path, $force=false,
        $maxWidth=160, $maxHeight=120, $quality=90
    ) {
        if (empty($source_path) || empty($target_path)) return false;
//DBG::log("Image::scale(): Source path $source_path");
        File::path_relative_to_root($source_path);
//DBG::log("Image::scale(): Fixed Source path $source_path");
        if (!File::exists($source_path)) return false;
        $original_size = getimagesize(ASCMS_DOCUMENT_ROOT.'/'.$source_path);
        $scaled_size = self::getScaledSize(
            $original_size, $maxWidth, $maxHeight);
        $source_image = self::load($source_path);
        if (!$source_image) {
            return false;
        }
        $target_image = false;
        if (function_exists ('imagecreatetruecolor'))
            $target_image = @imagecreatetruecolor($scaled_size[0], $scaled_size[1]);
        if (!$target_image)
            $target_image = ImageCreate($scaled_size[0], $scaled_size[1]);
// Resampling would yield less jaggy results, but blurs too much
//        imagecopyresampled(
        imagecopyresized(
            $target_image, $source_image, 0, 0, 0, 0,
            $scaled_size[0] + 1, $scaled_size[1] + 1,
            $original_size[0] + 1, $original_size[1] + 1
        );
        return self::saveJpeg($target_image, $target_path, $quality, $force);
    }


    /**
     * Create a cropped version of a picture.
     *
     * Note that cropped images are *always* created as jpeg image files!
     * @param   string  $source_path    The source image file path
     * @param   string  $target_path    The target image file path
     * @param   integer $x1             The left border of the cropped image
     * @param   integer $y1             The top border of the cropped image
     * @param   integer $x2             The right border of the cropped image
     * @param   integer $y2             The bottom border of the cropped image
     * @param   boolean $force          If true, the target image is forced
     *                                  to be overwritten
     * @param   integer $quality        The desired jpeg thumbnail quality
     * @return  bool                    True on success, false otherwise.
     * @static
     */
    static function crop(
        $source_path, $target_path, $x1, $y1, $x2, $y2,
        $force=false, $quality=90
    ) {
//DBG::log("crop($source_path, $target_path, $x1, $y1, $x2, $y2, $force, $quality): Entered");
        File::path_relative_to_root($source_path);
        $xs = $ys = null;
        list($xs, $ys) = getimagesize(ASCMS_DOCUMENT_ROOT.'/'.$source_path);
        // Fix coordinates that are out of range:
        // - Reset negative and too large values to the original size
        if ($x1 < 0) $x1 = 0;
        if ($y1 < 0) $y1 = 0;
        if ($x2 < 0) $x2 = $xs-1;
        if ($y2 < 0) $y2 = $ys-1;
        if ($x1 >= $xs) $x1 = 0;
        if ($y1 >= $ys) $y1 = 0;
        if ($x2 >= $xs) $x2 = $xs-1;
        if ($y2 >= $ys) $y2 = $ys-1;
        // - Flip left and right or top and bottom if the former are greater
        if ($x1 > $x2) { $tmp = $x1; $x1 = $x2; $x2 = $tmp; }
        if ($y1 > $y2) { $tmp = $y1; $y1 = $y2; $y2 = $tmp; }
        // Target size is now at most the original size.
        // Calculate target size
        $xs = $x2 - $x1;
        $ys = $y2 - $y1;

        $source_image = self::load($source_path);
        if (!$source_image) {
            return false;
        }
        $target_image = false;
        if (function_exists('imagecreatetruecolor'))
            $target_image = @imagecreatetruecolor($xs, $ys);
        if (!$target_image)
            $target_image = ImageCreate($xs, $ys);
        imagecopy(
            $target_image, $source_image, 0, 0, $x1, $y1,
// TODO: Verify the correct operation:
//            $xs + 1, $ys + 1
            $xs, $ys
        );
        return self::saveJpeg($target_image, $target_path, $quality, $force);
    }


    /**
     * Takes an image path and returns the corresponding jpeg format file name
     *
     * If the path belongs to a jpeg file already, it is returned unchanged.
     * Note that any rescaled images are created as jpeg image files!
     * @param   string    $image_path     The original image path
     * @return  string                    The thumbnail image path
     */
    static function getJpegPath($image_path)
    {
        $jpeg_path = preg_replace(
            '/(?:\.\w+)?$/', '.jpg', $image_path, 1);
        return $jpeg_path;
    }


    /**
     * Takes an image path and returns the corresponding thumbnail file name
     *
     * If the path belongs to a thumbnail already, it is returned unchanged.
     * Note that any thumbnails are created as jpeg image files!
     * @param   string    $image_path     The original image path
     * @return  string                    The thumbnail image path
     */
    static function getThumbnailPath($image_path)
    {
        if (preg_match(
            '/'.preg_quote(self::THUMBNAIL_SUFFIX.'.jpg', '/').'$/',
            $image_path)) {
//echo("Image::getThumbnailPath(): $image_path is a thumbnail already<br />");
            return $image_path;
        }
        // Insert the thumbnail suffix *before* the original extension, if any
        $thumb_path = preg_replace(
            '/(?:\.\w+)?$/', self::THUMBNAIL_SUFFIX.'.jpg', $image_path, 1);
//echo("Image::getThumbnailPath(): fixed $image_path to $thumb_path<br />");
        return $thumb_path;
    }


    /**
     * Load the image from the given file path
     *
     * Based on the ImageManager methods _imageCreateFromFile() and
     * _isImage()
     * @param   string    $file_path        The image file path
     * @return  resource                    The image resource on success,
     *                                      false otherwise
     */
    static function load($file_path)
    {
        if (!File::exists($file_path)) return false;
        $arrInfo = getimagesize(ASCMS_DOCUMENT_ROOT.'/'.$file_path);
        if (!is_array($arrInfo)) {
//echo("load(): failed to determine image size<br />");
            return false;
        }
        // 1: GIF, 2: JPG, 3: PNG, others are not accepted
        if (   $arrInfo[2] == 1
            && !function_exists('imagecreatefromgif')) return false;
        switch ($arrInfo[2]) {
            case 1:
                $function = 'imagecreatefromgif';
                break;
            case 2:
                $function = 'imagecreatefromjpeg';
                break;
            case 3:
                $function = 'imagecreatefrompng';
                break;
            default:
//echo("load(): unknown file type<br />");
                return false;
        }

        $memoryLimit = FWSystem::getBytesOfLiteralSizeFormat(@ini_get('memory_limit'));
        if (empty($memoryLimit)) {
            $memoryLimit = Image::MAXIMUM_UPLOAD_FILE_SIZE;
        }

        $potentialRequiredMemory = intval(
              $arrInfo[0]
            * $arrInfo[1]
            * ($arrInfo['bits'] / 8)
            * $arrInfo['channels']
            // With this factor, a downsized copy is included, like a thumbnail.
            // Note that this value is an arbitrarily approximated estimation. :)
            * 1.8);
        if (function_exists('memory_get_usage')) {
            $potentialRequiredMemory += memory_get_usage();
        } else {
            // add a default of 3 MB
            $potentialRequiredMemory += 3*pow(1024, 2);
        }

//echo("load(): potentialRequiredMemory $potentialRequiredMemory, memoryLimit $memoryLimit<br />");
        if ($potentialRequiredMemory > $memoryLimit) {
            // try to set a higher memory_limit
            if (!@ini_set('memory_limit', $potentialRequiredMemory))
//echo("load(): failed to set memory limit<br />");
                return false;
        }
//echo("load(): calling $function($file_path)...<br />");
        return $function(ASCMS_DOCUMENT_ROOT.'/'.$file_path);
    }


    /**
     * Saves the image to the path given as a jpeg file
     * @access  public
     * @param   string    $file   The path for the jpeg image file to be written
     * @param   booelan   $force  Force overwriting existing files if true
     * @return  boolean           True on success, false otherwise
     */
    function saveJpeg($image, $path, $quality=90, $force=false)
    {
        if (File::exists($path) && !$force) return false;
        File::delete_file($path);
        if (imagejpeg($image, ASCMS_DOCUMENT_ROOT.'/'.$path, $quality)) {
            return \Cx\Lib\FileSystem\FileSystem::makeWritable($path);
        }
        return false;
    }


    /**
     * NOT IMPLEMENTED
     * Output the image with the path given in the browser
     *
     * This method will not return!
     * @access   public
     * @return   void
    static function showImage($path)
    {
    }
     */


    /**
     * Stores any image files from a post request
     *
     * The files may be uploaded or chosen in the file browser.
     * Each file is moved to the given target path with a uniquid()
     * prepended to the file name; the original name is stored temporarily
     * only in the session.
     * If possible, the corresponding Image object is updated.  If not,
     * a new one is created and stored.
     * The original file name, type, Image ID and ordinal value are stored
     * in the session under $_SESSION['image'][<field_name>].
     * @param   string  $target_folder_path   The target folder path for
     *                                        uploaded images only
     * @return  integer             The Image ID if all images have been
     *                              processed successfully,
     *                              false if errors occurred,
     *                              or the empty string if nothing changed
     */
    static function processPostFiles($target_folder_path)
    {
//DBG::log("Image::processPostFiles($target_folder_path): Entered<br />");
        // Cases:
        // If present, pick the path, ID, ord and type from the session,
        // overwrite with those from the post.
        // - Post with a file upload (remember that this requires a multipart
        //   encoded form):
        //    Fields: id, ord, type, file
        //    - For those with valid file upload parameters (no error):
        //      insert or update the file and image.
        //    - For those with invalid parameters (error):
        //      - If there's no ID, ignore it
        //      - If ID and ord are valid, but the src has been posted empty,
        //        delete the file and image.
        // - Post with image selection from the file browser:
        //    Fields: id, ord, type src
        //    - If the src is present, try to get id and ord
        //    - If the src is valid, either update or insert the image
        //    - if the src is empty, but id and ord are valid,
        //      delete the image and file

        // Collect all posted images from file upload and post
        $arrName = array();
        if (is_array($_FILES)) {
            $arrName = array_keys($_FILES);
        }
        $name = '';
        if (is_array($_POST)) {
            $match = array();
            foreach (array_keys($_POST) as $name) {
                if (!preg_match('/^(\w+)_src$/', $name, $match)) continue;
                if (!in_array($match[1], $arrName))
                    $arrName[] = $match[1];
            }
        }
//DBG::log("Image::processPostFiles($target_folder_path): Made name array ".var_export($arrName, true));

        // Remember paths, so for deleted Images, the file can be removed
        $arrPath = array();
        $arrPathDeleted = array();

        $image_id = ($name && !empty($_SESSION['image'][$name]['id'])
            ? $_SESSION['image'][$name]['id'] : false); // The image ID
//DBG::log("Image::processPostFiles(): Image ID $image_id");
        $objImage = false;
        $result = ''; // No change
//DBG::log("Image::processPostFiles(): Collected image field names: ".var_export($arrName, true)."FILES: ".var_export($_FILES, true)."POST: ".var_export($_POST, true));
        // Process all images found
        foreach ($arrName as $name) {
            // The code analyzer insists that "$changed is never used".
            // I know better -- ignore it.
            $changed = false;
//DBG::log("Image::processPostFiles(): Processing image field name: $name<br />");
            $image_name = false; // The image original name
            $image_src  = false; // The image path
            $image_ord  = false; // The image ordinal value
            $image_type = false; // The image type key
            // Try to get the image object coordinates from the session,
            // in the ['image'][$name] branch, ...
            if (isset($_SESSION['image'][$name]['src']))
                $image_src  = $_SESSION['image'][$name]['src'];
//            if (!empty($_SESSION['image'][$name]['id']))
//                $image_id   = $_SESSION['image'][$name]['id'];
            if (isset($_SESSION['image'][$name]['ord']))
                $image_ord  = $_SESSION['image'][$name]['ord'];
            if (isset($_SESSION['image'][$name]['type']))
                $image_type = $_SESSION['image'][$name]['type'];
            // ...or get them from the post.
            // There may be fields with the name plus suffix
            // These override the session parameters.
            if (isset($_FILES[$name]))        $image_name = $_FILES[$name]['name'];
            if (isset($_POST[$name.'_src']))  $image_src  = $_POST[$name.'_src'];
            if (!empty($_POST[$name.'_id']))  $image_id   = $_POST[$name.'_id'];
            if (isset($_POST[$name.'_ord']))  $image_ord  = $_POST[$name.'_ord'];
            if (isset($_POST[$name.'_type'])) $image_type = $_POST[$name.'_type'];
//DBG::log("Image::processPostFiles(): Got parameters for $name: image_name $image_name, image_src $image_src, image_id $image_id, image_ord $image_ord, image_type $image_type<br />");
            // Upload valid images and update the parameters
            $objImage = self::getById($image_id, $image_ord);
            if (!$objImage) {
//DBG::log("Image::processPostFiles(): Created new Image (ID $image_id, ord $image_ord)<br />");
                $objImage = new Image($image_ord, $image_id);
            }
//            else {
//DBG::log("Image::processPostFiles(): Loaded Image ID $image_id: ".var_export($objImage, true)."<br />") ;
//            }
            // The image original name is only set when uploading images
            if ($image_name) {
//DBG::log("Image::processPostFiles(): Image to be uploaded, deleting old Image<br />");
//                $objImage->delete();
//                $objImage->setPath('');
                // Uploads must go to the target folder
                $image_src = $target_folder_path.'/'.$image_name;
                if (!File::upload_file_http(
                    $name, $image_src,
                    self::MAXIMUM_UPLOAD_FILE_SIZE,
                    Filetype::MIMETYPE_IMAGES_WEB)
                ) {
                    // For failed uploads, do not change anything
//DBG::log("Image::processPostFiles(): Uploading failed<br />");
                    $result = false;
                    // Keep the current Image on failure
                    continue;
                }
                // Remember the path of the previous Image, if any.
                if ($objImage->path) $arrPathDeleted[] = $objImage->path;
//DBG::log("Image::processPostFiles(): Uploading completed successfully<br />");
            }
            // Delete the image if the src has been posted, but is empty
//            if ($objImage->getPath() && $image_src === '') {
            // Delete the image if the src is empty
//DBG::log("Image::processPostFiles(): Path is $objImage->path<br />");
            if (empty($image_src)) {
//DBG::log("Image::processPostFiles(): Deleting $objImage->path<br />");
                unset($_SESSION['image'][$name]);
                // Remember the path of the deleted Image, if any.
                if ($objImage->path) $arrPathDeleted[] = $objImage->path;
// TODO: Records should not have to be deleted if the path is empty
                $objImage->delete();
                continue;
            }
            // Remember the path of the new or existing Image
            $arrPath[] = $image_src;

//DBG::log("Image::processPostFiles(): Valid image ".$objImage->path." (posted: $image_src)");
            // The Image is valid
            if ($image_src != $objImage->path) {
//DBG::log("Image::processPostFiles(): Path has been changed from ".$objImage->path." to $image_src");
                $objImage->setPath($image_src);
                if (File::exists($image_src)) {
//DBG::log("Image::processPostFiles(): File $image_src exists, austosizing...<br />");
                    $objImage->autoSize();
                }
                $changed = true;
            }
            if (   $image_type !== false
                && $image_type != $objImage->imagetype_key) {
//DBG::log("Image::processPostFiles(): Imagetype has been changed from ".$objImage->imagetype_key." to $image_type<br />");
                $objImage->setImagetypeKey($image_type);
                $changed = true;
            }

// TODO: File type
//            $objImage->setFiletypeKey('');

            if ($changed && $objImage->path) {
//DBG::log("Image::processPostFiles(): Image has been changed, storing...<br />");
                if ($objImage->store()) {
//DBG::log("Image::processPostFiles(): Image has been stored successfully<br />");
                    $image_id = $objImage->id;
                    // The original name is never stored with the image, just kept
                    // for reference as long as the session is alive
                    if ($image_name)
                        $_SESSION['image'][$name]['name'] = $image_name;
                    $_SESSION['image'][$name]['src']  = $objImage->path;
                    $_SESSION['image'][$name]['id']   = $image_id;
                    $_SESSION['image'][$name]['ord']  = $objImage->ord;
                    $_SESSION['image'][$name]['type'] = $objImage->imagetype_key;
//DBG::log("Image::processPostFiles(): Successfully stored image $name, ID ".$objImage->id);
                    if ($result === '') $result = true;
//DBG::log("Image::processPostFiles(): Temp result ".var_export($result, true));

                    // Resize and create a thumbnail with the Imagetype settings
                    $objImage->resize();
//DBG::log("Image::processPostFiles(): After resize: ".var_export($objImage, true)."<br />");
                } else {
//DBG::log("Image::processPostFiles(): Failed storing $image_src<br />");
                    $result = false;
                }
            }
//DBG::log("Image::processPostFiles(): Finished: ".var_export($objImage, true)."<br />");
        }

        // Finally, try to delete obsolete image files whose records have
        // been deleted.
        // Mind that the same file could be referenced in more than one record,
        // thus we need to remove those from the paths to be deleted that are.
        $arrPathToDelete = array_diff($arrPathDeleted, $arrPath);
//DBG::log("Image::processPostFiles(): Paths present: ".var_export($arrPath, true));
//DBG::log("Image::processPostFiles(): Paths deleted: ".var_export($arrPathDeleted, true));
//DBG::log("Image::processPostFiles(): Paths difference: ".var_export($arrPathToDelete, true));
        foreach ($arrPathToDelete as $path) {
//DBG::log("Image::processPostFiles(): Deleting file $path");
            // Ignore errors
            File::delete_file($path);
            File::delete_file(self::getThumbnailPath($path));
        }

//DBG::log("Image::processPostFiles(): Result ".var_export($result, true).", image ID $image_id<br />");
        $result = ($result === true ? $image_id : $result);
//DBG::log("Image::processPostFiles(): Result ".var_export($result, true).", image ID $image_id<br />");
//DBG::log("Image::processPostFiles(): Session: ".var_export($_SESSION['image'], true)."<br />");
        return $result;
    }


    /**
     * Returns the image data stored in the session for the given name
     *
     * If no such image is present, returns an image created from the
     * default path given, if any.
     * If the given default image does not exist, returns the Image class
     * default Image.
     * If that fails, too, returns false.
     * @param   string    $name           The image name
     * @return  Image                     The Image
     */
    static function getFromSessionByName($name)
    {
        if (   isset($_SESSION['image'][$name])
            && isset($_SESSION['image'][$name]['id'])) {
//echo("Image::getFromSessionByName($name): Found ".var_export($_SESSION['image'][$name], true)."<br />");
            $objImage = self::getById(
                $_SESSION['image'][$name]['id'],
                $_SESSION['image'][$name]['ord']
            );
            if ($objImage) return $objImage;
//echo("Image::getFromSessionByName($name): Could not get the image<br />");
        }
        return false;
    }


    /**
     * Returns the image data stored in the session for the given key
     *
     * If no such image is present, returns an image created from the
     * default path given, if any.
     * If the given default image does not exist, returns the Image class
     * default Image.
     * If that fails, too, returns false
     * @param   string    $key            The image key
     * @return  Image                     The default Image
     */
    static function getFromSessionByKey($key)
    {
        if (   isset($_SESSION['image'][$key])
            && isset($_SESSION['image'][$key]['id'])) {
//echo("Image::getFromSessionByKey($key): Found ".var_export($_SESSION['image'][$key], true)."<br />");
            $objImage = self::getById(
                $_SESSION['image'][$key]['id'],
                $_SESSION['image'][$key]['ord']
            );
            if ($objImage) return $objImage;
//echo("Image::getFromSessionByKey($key): Could not get the image<br />");
        }
        return false;
    }


    /**
     * Uploads an image file and stores its information in the database
     * @param   string  $upload_field_name  File input field name
     * @param   string  $target_path        Target path, relative to the
     *                                      document root, including the
     *                                      file name
     * @return  integer                     The new image ID on success,
     *                                      false otherwise
     * @author    Reto Kohli <reto.kohli@comvation.com>
     */
    static function uploadAndStore(
        $upload_field_name, &$target_path,
        $image_id=false, $imagetype_key=false, $ord=false)
    {
        // $target_path *SHOULD* be like ASCMS_HOTELCARD_IMAGES_FOLDER.'/folder/name.ext'
        // Strip path offset, if any, from the target path
        $target_path = preg_replace('/^'.preg_quote(ASCMS_PATH_OFFSET, '/').'/', '', $target_path);
        if (!File::upload_file_http(
            $upload_field_name, $target_path,
            self::MAXIMUM_UPLOAD_FILE_SIZE, Filetype::MIMETYPE_IMAGES_WEB)
        ) {
//echo("Image::uploadAndStore($upload_field_name, $target_path, $image_id, $imagetype_key, $ord): Failed to upload<br />");
            return false;
        }
        if ($image_id && $ord === false)
            $ord = self::getNextOrd($image_id, $imagetype_key);
        $objImage = new Image($ord, $image_id);
        $objImage->setPath($target_path);
        $size = getimagesize(ASCMS_DOCUMENT_ROOT.'/'.$target_path);
        $objImage->setWidth($size[0]);
        $objImage->setHeight($size[1]);
        $objImage->setImageTypeKey($imagetype_key);
//echo("Image::uploadAndStore(): Made Image:<br />".var_export($objImage, true)."<br />");
        if (!$objImage->store()) {
//echo("Image::uploadAndStore(): Failed to store<br />");
//            if (!
            File::delete_file($target_path);
//            ) {
//echo("Image::uploadAndStore(): Failed to delete file $target_path<br />");
//            }
            return false;
        }
//echo("Image::uploadAndStore(): Successfully stored<br />");
        if ($imagetype_key) {
            if (!$objImage->resize()) {
                File::delete_file($target_path);
                return false;
            }
        }
        return $objImage->id;
    }


    /**
     * Returns the greatest ordinal value plus one for the image ID
     *
     * If there is no matching one yet, returns 0.
     * If $ord_min is specified and larger than the highest value found,
     * it is returned instead.
     * @param   integer   $image_id         The optional image ID
     * @param   integer   $ord_min          The optional minimum ordinal value
     * @return  integer                     The next ordinal number on success,
     *                                      false otherwise
     */
    static function getNextOrd($image_id, $ord_min=0)
    {
        global $objDatabase;

        $query = "
            SELECT MAX(`ord`) as `ord`
              FROM ".DBPREFIX."core_image
             WHERE `id`=$image_id";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult || $objResult->EOF) return self::errorHandler();
        $ord = $objResult->fields['ord'];
        // This also works for $ord === null
        if ($ord_min > $ord) return $ord_min;
        if ($ord === null) return 0;
        return 1 + $ord;
    }


    /**
     * Handle any error occurring in this class.
     *
     * Tries to fix known problems with the database table.
     * @global  mixed     $objDatabase    Database object
     * @return  boolean                   False.  Always.
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function errorHandler()
    {
        global $objDatabase;

die("Image::errorHandler(): Disabled!");

        $query = "
            ALTER TABLE `".DBPREFIX."core_image`
            CHANGE `image_type_key` `imagetype_key` TINYTEXT NULL DEFAULT NULL COMMENT 'Defaults to NULL, which is an untyped image.',
            CHANGE `file_type_key` `filetype_key` TINYTEXT NULL DEFAULT NULL COMMENT 'File type is unknown if NULL.'";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult)
            die("Image::errorHandler(): Failed to fix core_image table field names<br />");
die("Image::errorHandler(): Fixed core_image table field names<br />");
//die("Image::errorHandler(): Disabled!<br />");

        $arrTables = $objDatabase->MetaTables('TABLES');
        if (in_array(DBPREFIX."core_image", $arrTables)) {
            $query = "DROP TABLE `".DBPREFIX."core_image`";
            $objResult = $objDatabase->Execute($query);
            if (!$objResult) return false;
        }
        // The table doesn't exist
        $query = "
            CREATE TABLE `".DBPREFIX."core_image` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `ord` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Ordinal value allowing multiple images to be stored for the same image ID and type.\nUsed for sorting.\nDefaults to zero.',
              `imagetype_key` TINYTEXT NULL DEFAULT NULL COMMENT 'Defaults to NULL, which is an untyped image.',
              `filetype_key` TINYTEXT NULL DEFAULT NULL COMMENT 'File type is unknown if NULL.',
              `path` TEXT NOT NULL COMMENT 'Path *SHOULD* be relative to the ASCMS_DOCUMENT_ROOT (document root + path offset).\nOmit leading slashes, these will be cut.',
              `width` INT UNSIGNED NULL COMMENT 'Width is unknown if NULL.',
              `height` INT UNSIGNED NULL COMMENT 'Height is unknown if NULL.',
              PRIMARY KEY (`id`, `ord`),
              KEY `image_type` (`imagetype_key`(32)),
              KEY `file_type` (`filetype_key`(32)))
            ENGINE=MyISAM";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return false;

        // More to come...

        return false;
    }

}
