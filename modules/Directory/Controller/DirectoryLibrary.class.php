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
 * Directory library
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_directory
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Modules\Directory\Controller;

/**
 * Directory library
 *
 * External functions for the directory
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_directory
 */
class DirectoryLibrary
{
    public $path;
    public $fileSize;
    public $webPath;
    public $imagePath;
    public $imageWebPath;
    public $dirLog;
    public $mediaPath;
    public $mediaWebPath;
    public $rssLatestTitle = "Directory News";
    public $rssLatestDescription = "The latest Directory entries";
    public $numCategories = array();
    public $numLevels = array();

    /**
     * category Ids
     *
     * @var array
     */
    public $categoryIds = array();

    public $googleMapStartPoint = array('lat' => 46, 'lon' => 8, 'zoom' => 1);

// TODO:  The following two object variables were declared out of scope.
// Moved here to fix this, but there may be more!
    /**
     * level Ids
     *
     * @var array
     */
    public $levelIds = array();

    /**
     * Categories
     *
     * @var array
     */
    public $getCategories = array('name' => array(), 'parentid' => array());

    /**
     * Levels
     *
     * @var array
     */
    public $getLevels = array('name' => array(), 'parentid' => array(), 'showcategories' => array());

    /**
     * Levels
     *
     * @var array
     */
    public $levels = array(
        'name'           => array(),
        'parentid'       => array(),
        'metadesc'       => array(),
        'metakeys'       => array(),
        'description'    => array(),
        'displayorder'   => array(),
        'status'         => array(),
        'showcategories' => array(),
    );

    /**
     * Categories
     *
     * @var array
     */
    public $categories = array(
        'name'         => array(),
        'parentid'     => array(),
        'metadesc'     => array(),
        'metakeys'     => array(),
        'description'  => array(),
        'displayorder' => array(),
        'status'       => array(),
        'showentries'  => array(),
    );

    public $pageTitle;

    /**
     * Constructor
     */
    function __construct()
    {
    }


    function checkPopular()
    {
        global $objDatabase;

        //get popular days
// TODO: $settings is never set!
//        $populardays = $settings['populardays']['value'];
        $populardays = '';

        //get startdate
        $objResult = $objDatabase->Execute("SELECT popular_date FROM ".DBPREFIX."module_directory_dir LIMIT 1");

        if ($objResult !== false) {
            while(!$objResult->EOF) {
                $startdate = $objResult->fields['popular_date'];
                $objResult->MoveNext();
            }
        }
        $today = mktime(0, 0, 0, date("m")  , date("d"), date("Y"));
        $tempDays = date("d",$startdate);
        $tempMonth = date("m",$startdate);
        $tempYear = date("Y",$startdate);
        $enddate = mktime(0, 0, 0, $tempMonth, $tempDays+$populardays,  $tempYear);
        if ($today >= $enddate) {
            $this->restorePopular();
        }
    }


    function restorePopular()
    {
        global $objDatabase;

        $date = mktime(0, 0, 0, date("m")  , date("d"), date("Y"));
        $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_dir SET popular_hits='0', popular_date='".$date."'");
    }


    /**
    * get hits
    *
    * get hits
    *
    * @access   public
    * @param    string  $id
    * @global   ADONewConnection
    */
    function getHits($id)
    {
        global $objDatabase;

        //get feed data
        $objResult = $objDatabase->Execute("SELECT  hits, lastip, popular_hits FROM ".DBPREFIX."module_directory_dir WHERE status = '1' AND id = '".contrexx_addslashes($id)."'");

        if ($objResult !== false) {
            while(!$objResult->EOF) {
                $hits = $objResult->fields['hits'];
                $popular_hits = $objResult->fields['popular_hits'];
                $lastip = $objResult->fields['lastip'];
                $objResult->MoveNext();
            }
        }

        $hits++;
        $popular_hits++;
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $ip = $cx->getComponent('Stats')->getCounterInstance()->getUniqueUserId();

        //update hits
        if (!checkForSpider() && $lastip != $ip) {
            $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_dir SET
                    hits='".$hits."', popular_hits='".$popular_hits."', lastip='".$ip."' WHERE id='".contrexx_addslashes($id)."'");
        }
    }


    /**
    * get categories
    *
    * get added categories
    *
    * @access   public
    * @param    string    $catId
    * @return    string    $options
    * @global    ADONewConnection
    */
    function getSearchCategories($catId)
    {
        global $objDatabase;

        //get all categories
        $objResultCat = $objDatabase->Execute("SELECT id, parentid, name FROM ".DBPREFIX."module_directory_categories ORDER BY displayorder");

        if ($objResultCat !== false) {
            while(!$objResultCat->EOF) {
                $this->getCategories['name'][$objResultCat->fields['id']] = htmlentities($objResultCat->fields['name'], ENT_QUOTES, CONTREXX_CHARSET);
                $this->getCategories['parentid'][$objResultCat->fields['id']] = $objResultCat->fields['parentid'];
                $objResultCat->MoveNext();
            }
        }

        //make categories dropdown
        $options = "";
        if (!empty($this->getCategories['name'])) {
            foreach($this->getCategories['name'] as $catKey => $catName) {
                $checked = "";
                if ($this->getCategories['parentid'][$catKey] == 0) {
                    if ($catKey == $catId) {
                        $checked = "selected";
                    }
                    $options .= "<option value='".$catKey."' ".$checked.">".$catName."</option>";

                    //get subcategories
                    $options .= $this->getSearchSubcategories($catName, $catKey, $catId);
                }
            }
        }
        return $options;
    }


    /**
    * get added subcategories
    * @access   public
    * @param    string    $catName
    * @param    string    $parentId
    * @param    string    $catId
    * @return    string    $options
    */
    function getSearchSubcategories($catName, $parentId, $catId)
    {
        $category = $catName;
        $subOptions = "";

        //get subcategories
        foreach($this->getCategories['name'] as $catKey => $catName) {
            if ($this->getCategories['parentid'][$catKey] == $parentId) {
                $checked = "";
                if ($catKey == $catId) {
                    $checked = "selected";
                }

                $subOptions .= "<option value='".$catKey."' ".$checked.">".$category." >> ".$catName."</option>";

                //get more subcategories
                $subOptions .= $this->getSearchSubcategories($category." >> ".$catName, $catKey, $catId);
            }
        }

        return $subOptions;
    }


    /**
    * get added categories
    * @access   public
    * @param    string    $catId
    * @return    string    $options
    * @global    ADONewConnection
    */
    function getCategories($id, $type)
    {
        global $objDatabase;

        //get selected levels
        $objResultCat = $objDatabase->Execute("SELECT cat_id FROM ".DBPREFIX."module_directory_rel_dir_cat WHERE dir_id='".$id."'");
        if ($objResultCat !== false) {
            while(!$objResultCat->EOF) {
                $this->categoryIds[] = $objResultCat->fields['cat_id'];
                $objResultCat->MoveNext();
            }
        }


        //get all categories
        $objResultCat = $objDatabase->Execute("SELECT * FROM ".DBPREFIX."module_directory_categories ORDER BY displayorder");

        if ($objResultCat !== false) {
            while(!$objResultCat->EOF) {
                $this->getCategories['name'][$objResultCat->fields['id']] = $objResultCat->fields['name'];
                $this->getCategories['parentid'][$objResultCat->fields['id']] = $objResultCat->fields['parentid'];
                $objResultCat->MoveNext();
            }
        }


        $options = "";

        //make categories dropdown
        foreach($this->getCategories['name'] as $catKey => $catName) {
            if ($this->getCategories['parentid'][$catKey] == 0) {
                if ($type == 1) {
                    if (!in_array($catKey, $this->categoryIds)) {
                        $options .= "<option value='".$catKey."'>".$catName."</option>";
                    }
                } else {
                    if (in_array($catKey, $this->categoryIds)) {
                        $options .= "<option value='".$catKey."'>".$catName."</option>";
                    }
                }

                //get subcategories
                $options .= $this->getSubcategories($catName, $catKey, '&nbsp;&nbsp;&nbsp;', $type);
            }
        }

        return $options;
    }




    /**
    * Get subcategories
    * @access   public
    * @param    string    $catName
    * @param    string    $parentId
    * @param    string    $catId
    * @return    string    $options
    */
    function getSubcategories($catName, $parentId, $spacer, $type)
    {
        $options = '';
        foreach($this->getCategories['name'] as $catKey => $catName) {
            if ($this->getCategories['parentid'][$catKey] == $parentId) {
                if ($type == 1) {
                    if (!in_array($catKey, $this->categoryIds)) {
                        $options .= "<option value='".$catKey."' >".$spacer.$catName."</option>";
                    }
                } else {
                    if (in_array($catKey, $this->categoryIds)) {
                        $options .= "<option value='".$catKey."' >".$catName."</option>";
                    }
                }
                //get more subcategories
                $options .=
                    $this->getSubcategories(
                        $catName, $catKey, $spacer.'&nbsp;&nbsp;&nbsp;', $type
                    );
            }
        }
        return $options;
    }


    /**
    * Get languages
    * @access   public
    * @param    string    $langId
    * @return    string    $languages
    * @global    ADONewConnection
    */
    function getLanguages($langId)
    {
        global $objDatabase;

        //get all languages
        $objResultLang = $objDatabase->Execute("
            SELECT *
              FROM ".DBPREFIX."module_directory_settings
             WHERE setname='language'");
        $language = '';
        if ($objResultLang && !$objResultLang->EOF) {
            $language = $objResultLang->fields['setvalue'];
        }
        $this->getLanguage = preg_split(
            '/\s*,\s*/', $language,
            null, PREG_SPLIT_NO_EMPTY
        );
        $languages = '';
        foreach($this->getLanguage as $langName) {
            $languages .=
                "<option value='$langName'".
                ($langName == $langId ? 'selected="selected"' : '').
                ">$langName</option>";
        }
        return $languages;
    }


    function getCantons($cantId)
    {
        global $objDatabase;

        //get all languages
        $objResult = $objDatabase->Execute("
            SELECT *
              FROM ".DBPREFIX."module_directory_settings
             WHERE setname='canton'");
        $canton = '';
        if ($objResult !== false && !$objResult->EOF) {
            $canton = $objResult->fields['setvalue'];
        }
        $this->getCantons = preg_split(
            '/\s*,\s*/', $canton,
            null, PREG_SPLIT_NO_EMPTY
        );
        $cantons = '';
        foreach($this->getCantons as $cantName) {
            $cantons .=
                "<option value='$cantName'".
                ($cantName == $cantId ? 'selected="selected"' : '').
                ">$cantName</option>";
        }
        return $cantons;
    }


    /**
     * @param unknown_type $spezId
     * @return unknown
     */
    function getSpezDropdown($spezId, $spezField)
    {
        global $objDatabase;

        //get all languages
        $objResult = $objDatabase->Execute("SELECT * FROM ".DBPREFIX."module_directory_settings WHERE setname = '".$spezField."'");
        if ($objResult !== false) {
            while(!$objResult->EOF) {
                $spez = $objResult->fields['setvalue'];
                $objResult->MoveNext();
            }
        }

        //explode languages
        $tmpArray = explode(",", $spez);

        //make languages dropdown
        $spezial = "";
        foreach($tmpArray as $spezName) {
            $checked = "";
            if ($spezName == $spezId) {
                $checked ="selected";
            }
            $spezial .= "<option value='".$spezName."' $checked>".$spezName."</option>";
        }

        return $spezial;
    }


    /**
     * @param unknown_type $spezId
     * @return unknown
     */
    function getSpezVotes($spezId, $spezField)
    {
        global $objDatabase;

        //get all languages
        $objResult = $objDatabase->Execute("SELECT * FROM ".DBPREFIX."module_directory_settings WHERE setname = '".$spezField."'");
        if ($objResult !== false) {
            while(!$objResult->EOF) {
                $spez = $objResult->fields['setvalue'];
                $objResult->MoveNext();
            }
        }

        //explode languages
        $tmpArray=explode(",", $spez);

        //make languages dropdown
        $spezial = "";
        $i = 0;
        foreach($tmpArray as $spezName) {
            $checked = "";
            $value = "";
            if ($spezId == $i) {
                $checked ="selected";
            }

            if ($i==0 && $spezName=="") {
                $value = "";
            } else {
                $value = $i;
            }

            $spezial .= "<option value='".$value."' $checked>".$spezName."</option>";
            $i++;
        }

        return $spezial;
    }


    /**
    * get platforms
    *
    * get all platforms
    *
    * @access   public
    * @param    string    $osId
    * @return    string    $platforms
    * @global    ADONewConnection
    */
    function getPlatforms($osId)
    {
        global $objDatabase;

        //get all plattforms
        $objResultPlat = $objDatabase->Execute("SELECT setvalue FROM ".DBPREFIX."module_directory_settings WHERE setname = 'platform'");
        if ($objResultPlat !== false) {
            while(!$objResultPlat->EOF) {
                $platforms = $objResultPlat->fields['setvalue'];
                $objResultPlat->MoveNext();
            }
        }

        //explode platforms
        $this->getPlatforms=explode(",", $platforms);

        //make platforms dropdown
        foreach($this->getPlatforms as $osName) {
            $checked = "";
            if ($osName == $osId) {
                $checked ="selected";
            }
            $platforms .= "<option value='".$osName."' $checked>".$osName."</option>";
        }

        return $platforms;
    }

    /**
     * Upload the media files
     *
     * @param string $fileName   name of the media file
     * @param string $path       folder path
     * @param string $uploaderId uploader id
     *
     * @return string $status name of the uploaded file / error
     */
    function uploadMedia($fileName, $path, $uploaderId)
    {
        if (empty($uploaderId) || empty($fileName)) {
            return 'error';
        }

        $cx  = \Cx\Core\Core\Controller\Cx::instanciate();
        $objSession = $cx->getComponent('Session')->getSession();
        $tempPath   = $objSession->getTempPath() .'/' . $uploaderId . '/' . $fileName;
        //Check the uploaded file exists in /tmp folder
        if (!\Cx\Lib\FileSystem\FileSystem::exists($tempPath)) {
            //If the file still exists in the mediaPath then return the filename
            if (\Cx\Lib\FileSystem\FileSystem::exists($this->mediaPath.$path.$fileName)) {
                return $fileName;
            }
            return 'error';
        }

        $info = pathinfo($fileName);
        $exte = $info['extension'];
        $extension = !empty($exte) ? '.' . $exte : '';
        $file = substr($fileName, 0, strlen($fileName) - strlen($extension));
        $rand = rand(10, 99);
        $arrSettings = $this->getSettings();

        if ($arrSettings['encodeFilename']['value'] == 1) {
            $fileName = md5($rand.$file).$extension;
        }

        //Rename the file if the filename already exists
        while (\Cx\Lib\FileSystem\FileSystem::exists($this->mediaPath.$path.$fileName)) {
            $fileName = $file . '_' . time() . $extension;
        }

        $filePath = $this->mediaPath.$path.$fileName;
        if (!\FWValidator::is_file_ending_harmless($filePath)) {
            return 'error';
        }

        //Move the file from /tmp folder into mediaPath and set the permission
        try {
            $objFile = new \Cx\Lib\FileSystem\File($tempPath);
            if ($objFile->move($filePath, false)) {
                $fileObj = new \File();
                $fileObj->setChmod($this->mediaPath, $this->mediaWebPath, $path.$fileName);
                $status = $fileName;
            }
        } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
            \DBG::msg($e->getMessage());
            $status = 'error';
        }

        //make the thumb
        if (($exte == "gif" || $exte == "jpeg" || $exte == "jpg" || $exte == "png") && $path != "uploads/") {
            $this->createThumb($fileName, $path);
        }

        return $status;
    }

    /**
    * Create a thumbnail image
    * @param  string  $fileName   The image filename
    */
    function createThumb($fileName, $filePath)
    {
        //copy image
        $oldFile = $this->mediaPath.$filePath.$fileName;
        $newFile = $this->mediaPath."thumbs/".$fileName;
        $arrSettings = $this->getSettings();
        $arrInfo = getimagesize($oldFile); //ermittelt die Größe des Bildes
        $setSize = $arrSettings['thumbSize']['value'];
        $strType = $arrInfo[2]; //type des Bildes

        if ($arrInfo[0] >= $setSize || $arrInfo[1] >= $setSize) {
            if ($arrInfo[0] <= $arrInfo[1]) {
                $intFactor = $arrInfo[1]/$setSize;
                $intHeight = $setSize;
                $intWidth = $arrInfo[0]/$intFactor;
            } else {
                $intFactor = $arrInfo[0]/$setSize;
                $intResult = $arrInfo[1]/$intFactor;
                if ($intResult > $setSize) {
                    $intHeight = $setSize;
                    $intWidth = $arrInfo[0]/$intFactor;
                } else {
                    $intWidth = $setSize;
                    $intHeight = $arrInfo[1]/$intFactor;
                }
            }
        } else {
            $intWidth = $arrInfo[0];
            $intHeight = $arrInfo[1];
        }

        if (imagetypes() & IMG_GIF) {
            $boolGifEnabled = true;
        }

        if (imagetypes() &  IMG_JPG) {
            $boolJpgEnabled = true;
        }

        if (imagetypes() & IMG_PNG) {
            $boolPngEnabled = true;
        }

        @touch($newFile);



        switch ($strType)
        {
            case 1: //GIF
                if ($boolGifEnabled) {
                    $handleImage1 = ImageCreateFromGif ($oldFile);
                    $handleImage2 = @ImageCreateTrueColor($intWidth,$intHeight);
                    ImageCopyResampled($handleImage2, $handleImage1,0,0,0,0,$intWidth,$intHeight, $arrInfo[0],$arrInfo[1]);
                    ImageGif ($handleImage2, $newFile);

                    ImageDestroy($handleImage1);
                    ImageDestroy($handleImage2);
                }
            break;
            case 2: //JPG
                if ($boolJpgEnabled) {
                    $handleImage1 = ImageCreateFromJpeg($oldFile);
                    $handleImage2 = @ImageCreateTrueColor($intWidth,$intHeight);
                    ImageCopyResampled($handleImage2, $handleImage1,0,0,0,0,$intWidth,$intHeight, $arrInfo[0],$arrInfo[1]);
                    ImageJpeg($handleImage2, $newFile, 95);

                    ImageDestroy($handleImage1);
                    ImageDestroy($handleImage2);
                }
            break;
            case 3: //PNG
                if ($boolPngEnabled) {
                    $handleImage1 = ImageCreateFromPNG($oldFile);
                    ImageAlphaBlending($handleImage1, true);
                    ImageSaveAlpha($handleImage1, true);
                    $handleImage2 = @ImageCreateTrueColor($intWidth,$intHeight);
                    ImageCopyResampled($handleImage2, $handleImage1,0,0,0,0,$intWidth,$intHeight, $arrInfo[0],$arrInfo[1]);
                    ImagePNG($handleImage2, $newFile);

                    ImageDestroy($handleImage1);
                    ImageDestroy($handleImage2);
                }
            break;
        }
    }



    /**
    * create xml
    *
    * create xml
    *
    * @access   public
    * @param    string  $link
    * @return   string  $filename
    */
    function createXML ($link)
    {
        //copy
        $time = time();
        $filename = $time."_".basename($link);
        $rand = rand(10, 99);
        $arrSettings = $this->getSettings();

        if ($arrSettings['encodeFilename']['value'] == 1) {
// TODO: $fileName is neither set nor used!
//            $fileName = md5($rand.$filename)."xml";
            $filename = md5($rand.$filename)."xml";
        }


        if (!@copy($link, $this->mediaPath."ext_feeds/".$filename)) {
            return "error";
        } else {
            //rss class
            $rss = new \XML_RSS($this->mediaPath."ext_feeds/".$filename);
            $rss->parse();
            $content = '';

            foreach($rss->getStructure() as $array)
            {
                $content .= $array;
            }

            //set chmod
            $obj_file = new \File();
            $obj_file->setChmod($this->mediaPath, $this->mediaWebPath, "ext_feeds/".$filename);

            if ($content == '') {
                //del xml
                @unlink($this->mediaPath."ext_feeds/".$filename);
                return "error";
            } else {
                return $filename;
            }
        }
    }



    /**
    * refresh xml
    *
    * refresh ex. xml
    *
    * @access   public
    * @param    string  $id
    * @return   string  $filename
    * @global   ADONewConnection
    * @global   array
    */
    function refreshXML($id)
    {
        global $objDatabase, $_ARRAYLANG;

        //get filename
        $objResult = $objDatabase->Execute("SELECT  id, rss_link, rss_file FROM ".DBPREFIX."module_directory_dir WHERE status = '1' AND id = '".contrexx_addslashes($id)."'");

        if ($objResult !== false) {
            while(!$objResult->EOF) {
                $filename = $objResult->fields['rss_file'];
                $link = $objResult->fields['rss_link'];
                $objResult->MoveNext();
            }
        }

        //del old
        if (file_exists($this->mediaPath."ext_feeds/".$filename)) {
            @unlink($this->mediaPath."ext_feeds/".$filename);
        }

        //copy
        if (!copy($link, $this->mediaPath."ext_feeds/".$filename))
        {
            $this->statusMessage = $_ARRAYLANG['DIRECTORY_NO_NEWS'];
            die;
        }

        //rss class
        $rss = new \XML_RSS($this->mediaPath."ext_feeds/".$filename);
        $rss->parse();
        $content = '';
        foreach($rss->getStructure() as $array)
        {
            $content .= $array;
        }

        $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_dir SET xml_refresh='".mktime(date("G"),  date("i"), date("s"), date("m"), date("d"), date("Y"))."' WHERE id='".$id."'");

        if ($content == '')
        {
            unlink($this->mediaPath."ext_feeds/".$filename);
        }
    }



    /**
    * add feed
    *
    * add new feed
    *
    * @access   public
    * @global   ADONewConnection
    * @global   array
    */
    function addFeed()
    {
        global $objDatabase, $_ARRAYLANG;

// TODO: Never used
//        $arrSettings = $this->getSettings();
        //get post data
// TODO: $file is never set; always true.
//        if ($file != "error") {
// See below!
            $imgUploadId = isset($_POST['imgUploaderId'])
                           ? contrexx_input2raw($_POST['imgUploaderId'])
                           : '';
            $fileUploadId = isset($_POST['fileUploaderId'])
                           ? contrexx_input2raw($_POST['fileUploaderId'])
                           : '';

            $query = "INSERT INTO ".DBPREFIX."module_directory_dir SET ";

            foreach($_POST["inputValue"] as $inputName => $inputValue) {

                switch ($inputName) {
                    case 'lat':
                    case 'lat_fraction':
                    case 'lon':
                    case 'lon_fraction':
                    case 'zoom':
                        continue 2;
                }

                //check links
                if ($inputName == "relatedlinks" || $inputName == "homepage" || $inputName == "link") {
                    if (substr($inputValue, 0,7) != "http://" && $inputValue != "") {
                        $inputValue = "http://".$inputValue;
                    }
                }

                //check rss
                if ($inputName == "rss_link") {

                    //create rss
                    $link = $inputValue;
                    $rss_file = $this->createXML($link);

                    if (substr($inputValue, 0,7) != "http://" && $inputValue != "") {
                        $inputValue = "http://".$inputValue;
                    }

                    if ($rss_file == "error") {
                        $inputValue = "";
                        $rss_file = "";
                    }

                }

                //upload spez pics
                if ($inputName == "logo" ||
                    $inputName == "lokal" ||
                    $inputName == "map" ||
                    $inputName == "spez_field_11" ||
                    $inputName == "spez_field_12" ||
                    $inputName == "spez_field_13" ||
                    $inputName == "spez_field_14" ||
                    $inputName == "spez_field_15" ||
                    $inputName == "spez_field_16" ||
                    $inputName == "spez_field_17" ||
                    $inputName == "spez_field_18" ||
                    $inputName == "spez_field_19" ||
                    $inputName == "spez_field_20") {

                    $fileName   = isset($_POST[$inputName]) ? contrexx_input2raw($_POST[$inputName]) : '';
                    $inputValue = $this->uploadMedia($fileName, 'images/', $imgUploadId);

                    if ($inputValue == "error") {
                        $inputValue = "";
                    }
                }

                //upload spez files and attachment
                if ($inputName == "attachment" ||
                    $inputName == "spez_field_25" ||
                    $inputName == "spez_field_26" ||
                    $inputName == "spez_field_27" ||
                    $inputName == "spez_field_28" ||
                    $inputName == "spez_field_29") {

                    $fileName   = isset($_POST[$inputName]) ? contrexx_input2raw($_POST[$inputName]) : '';
                    $inputValue = $this->uploadMedia($fileName, 'uploads/', $fileUploadId);
                    if ($inputValue == "error") {
                        $inputValue = "";
                    }
                }

                //get author id
                if ($inputName == "addedby") {
                    $objFWUser = \FWUser::getFWUserObject();
                    if ($objFWUser->objUser->login()) {
                        $inputValue = $objFWUser->objUser->getId();
                    } else {
                        $inputValue = $inputValue;
                    }
                }

                $query .= contrexx_strip_tags($inputName)." ='".contrexx_addslashes($inputValue)."', ";
            }

            //get status settings
            $objResult = $objDatabase->Execute("SELECT setvalue FROM ".DBPREFIX."module_directory_settings WHERE setname = 'entryStatus' LIMIT 1");

            if ($objResult !== false) {
                while(!$objResult->EOF) {
                    $entryStatus = $objResult->fields['setvalue'];
                    $objResult->MoveNext();
                }
            }

            $query .=
                "rss_file='".(empty($rss_file) ? '' : $rss_file)."', ".
                "date='".mktime(
                    date("H"), date("i"), date("s"),
                    date("m"), date("d"), date("Y")).
                "', status='".intval($entryStatus).
                "', validatedate='".mktime(
                    date("H"), date("i"), date("s"),
                    date("m"), date("d"), date("Y")).
                "', xml_refresh='".time().
                "', longitude='".
                (   isset($_REQUEST['inputValue']['lon'])
                 && isset($_POST['inputValue']['lon_fraction'])
                  ? intval($_REQUEST['inputValue']['lon']).'.'.
                    intval($_POST['inputValue']['lon_fraction'])
                  : 0).
                "', latitude='".
                (   isset($_REQUEST['inputValue']['lat'])
                 && isset($_REQUEST['inputValue']['lat_fraction'])
                  ? intval($_REQUEST['inputValue']['lat']).'.'.
                    intval($_REQUEST['inputValue']['lat_fraction'])
                  : 0).
                "', zoom='".
                (isset($_REQUEST['inputValue']['zoom'])
                  ? intval($_REQUEST['inputValue']['zoom'])
                  : 0).
                "'";

            //add entry
            $objResult = $objDatabase->query($query);

            if ($objResult !== false) {
                $id = $objDatabase->insert_ID();

                foreach($_POST["selectedCat"] as $inputName => $inputValue) {
                    $query = "INSERT INTO ".DBPREFIX."module_directory_rel_dir_cat SET dir_id='".$id."', cat_id='".$inputValue."'";
                    $objDatabase->query($query);
                }

                if (isset($_POST["selectedLevel"])) {
                    foreach($_POST["selectedLevel"] as $inputName => $inputValue) {
                        $query = "
                            INSERT INTO ".DBPREFIX."module_directory_rel_dir_level
                               SET dir_id='$id', level_id='$inputValue'";
                        $objDatabase->query($query);
                    }
                }

                if ($entryStatus == 1) {
                    $this->confirmEntry_step2($id);
                }

// TODO: $entryName is never set!
// TODO: Use language variable.
//                $this->strOkMessage = "Eintrag".$entryName." erfolgreich erstellt.";
                $this->strOkMessage = "Eintrag erfolgreich erstellt.";
                $status = $id;
                $this->createRSS();
            }

// TODO: See $file above.
//        } else {
//             $status = 'error';
//             $this->strErrMessage = $msg_error;
//        }

        return $status;
    }


    /**
     * confirm entry
     *
     * confirm selected entry
     *
     * @access   public
     * @param    string    $id
     * @global    ADONewConnection
     * @global    array
     */
    function confirmEntry_step2($id)
    {
        global $objDatabase, $_ARRAYLANG;

// TODO: Never used
//        $entryId = $id;

        //update popular
        $objResult = $objDatabase->Execute("
            SELECT popular_date FROM ".DBPREFIX."module_directory_dir LIMIT 1");

        if ($objResult !== false) {
            while(!$objResult->EOF) {
                $date = $objResult->fields['popular_date'];
                $objResult->MoveNext();
            }
        }

        if ($date == "") {
            $date = mktime(date("G"),  date("i"), date("s"), date("m"), date("d"), date("Y"));
        }

        //confirm entry
        $query = "UPDATE ".DBPREFIX."module_directory_dir SET ";
        $query .= "validatedate='".mktime(date("G"),  date("i"), date("s"), date("m"), date("d"), date("Y"))."', popular_date='".$date."', status ='1'  WHERE id='".contrexx_addslashes($id)."'";

        //add entry
        $objResult = $objDatabase->Execute($query);
        if ($objResult !== false) {
            //send mail
            $this->sendMail($id, '');

            //update xml
            $this->createRSS();
            $this->strOkMessage = $_ARRAYLANG['TXT_FEED_SUCCESSFULL_CONFIRM'];
        } else {
            $this->strErrMessage = $_ARRAYLANG['TXT_FEED_CORRUPT_CONFIRM'];
        }
    }


    /**
     * Send a confirmation e-mail to the address specified in the form,
     * if any.
     * @param $id
     * @param unknown_type $email
     * @return unknown
     */
    function sendMail($feedId, $email)
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG, $objInit;

        $feedId = intval($feedId);
        $languageId = null;
        // Get the user ID and entry information
        $objResult = $objDatabase->Execute("
            SELECT addedby, title, language
              FROM ".DBPREFIX."module_directory_dir
             WHERE id='$feedId'");
        if ($objResult && !$objResult->EOF) {
            $userId = $objResult->fields['addedby'];
            $feedTitle = $objResult->fields['title'];
            $languageId = $objResult->fields['language'];
        }
        // Get user data
        if (is_numeric($userId)) {
            $objFWUser = new \FWUser();
            if ($objFWUser->objUser->getUser($userId)) {
                $userMail = $objFWUser->objUser->getEmail();
                $userFirstname = $objFWUser->objUser->getProfileAttribute('firstname');
                $userLastname = $objFWUser->objUser->getProfileAttribute('lastname');
                $userUsername = $objFWUser->objUser->getUsername();
            }
        }

        if (!empty($email)) {
            $sendTo = $email;
            $mailId = 2;
        } else {
// FIXED:  The mail addresses may *both* be empty!
// Adding the entry was sucessful, however.  So we can probably assume
// that it was a success anyway?
// Added:
            if (empty($userMail)) return true;
// ...and a boolean return value below.
            $sendTo = $userMail;
            $mailId = 1;
        }

        //get mail content n title
        $objResult = $objDatabase->Execute("
            SELECT title, content
              FROM ".DBPREFIX."module_directory_mail
             WHERE id='$mailId'");
        if ($objResult && !$objResult->EOF) {
            $subject = $objResult->fields['title'];
            $message = $objResult->fields['content'];
        }

        if ($objInit->mode == 'frontend') {
            $link =
                "http://".$_CONFIG['domainUrl'].CONTREXX_SCRIPT_PATH.
                "?section=Directory&cmd=detail&id=".$feedId;
        } else {
            $link =
                "http://".$_CONFIG['domainUrl'].ASCMS_PATH_OFFSET.'/'.
                    \FWLanguage::getLanguageParameter($languageId, 'lang').'/'.
                    CONTREXX_DIRECTORY_INDEX."?section=Directory&cmd=detail&id=".$feedId;
        }

        // replace placeholders
        $array_1 = array(
            '[[USERNAME]]',
            '[[FIRSTNAME]]',
            '[[LASTNAME]]',
            '[[TITLE]]',
            '[[LINK]]',
            '[[URL]]',
            '[[DATE]]',
        );
        $array_2 = array(
            $userUsername,
            $userFirstname,
            $userLastname,
            $feedTitle,
            $link,
            $_CONFIG['domainUrl'].ASCMS_PATH_OFFSET,
            date(ASCMS_DATE_FORMAT),
        );
        $subject = str_replace($array_1, $array_2, $subject);
        $message = str_replace($array_1, $array_2, $message);
        $sendTo = explode(';', $sendTo);

        $objMail = new \Cx\Core\MailTemplate\Model\Entity\Mail();

        $objMail->SetFrom($_CONFIG['coreAdminEmail'], $_CONFIG['coreAdminName']);
        $objMail->Subject = $subject;
        $objMail->IsHTML(false);
        $objMail->Body = $message;

        foreach($sendTo as $mailAdress) {
            $objMail->ClearAddresses();
            $objMail->AddAddress($mailAdress);
            $objMail->Send();
        }

        return true;
    }


    /**
     * create new rss
     */
    function createRSS()
    {
        //crate latest xml
        $this->createRSSlatest();
    }


    /**
     * Returns the HTML dropdown menu options for the country menu
     * @param   string  $selectedCountryName  The name of the country
     *                                        to be preselected
     * @return  string                        The dropdown menu options
     * @author  Reto Kohli <reto.kohli@comvation.com> -- Fixed
     */
    function getCountryMenuoptions($selectedCountryName)
    {
        global $objDatabase, $_ARRAYLANG;

        $objResult = $objDatabase->Execute("
            SELECT *
              FROM ".DBPREFIX."module_directory_settings
             WHERE setname='country'");
        if (!$objResult || $objResult->EOF)
            return
                '<option value="">'.
                $_ARRAYLANG['TXT_DATABASE_QUERY_ERROR'].
                '</option>';
//echo("getCountryMenuoptions($selectedCountryName):<br />Got countries: ".$objResult->fields['setvalue']."<br />");
        $countries = preg_split(
            '/\s*,\s*/', $objResult->fields['setvalue'],
            null, PREG_SPLIT_NO_EMPTY
        );
        $menuoptions = '';
        foreach ($countries as $countryName) {
            $menuoptions .=
                '<option value="'.$countryName.'"'.
                ($countryName == $selectedCountryName
                    ? ' selected="selected"' : '').
                '>'.$countryName.'</option>';
        }
        return $menuoptions;
    }



    /**
    * create rss latest
    *
    * create new xml latest
    *
    * @access   public
    * @global   array
    * @global   ADONewConnection
    */
    function createRSSlatest()
    {
        global $_CONFIG, $objDatabase;

        //check file
        $obj_file = new \File();
        if (file_exists($this->mediaPath."feeds/directory_latest.xml")) {
            $obj_file->delFile($this->mediaPath, $this->mediaWebPath, "media/feeds/directory_latest.xml");
        }

        $query = "SELECT * FROM ".DBPREFIX."module_directory_settings WHERE setname='latest_xml'";
        $objResult = $objDatabase->Execute($query);
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $limit = $objResult->fields['setvalue'];
                $objResult->MoveNext();
            }
        }

        if ($this->dirLog != "error") {
            //create xml
            \Env::get('ClassLoader')->loadFile(ASCMS_MODULE_PATH.'/Directory/Controller/RssFeed.class.php');
            $objRSS = new rssFeed(0);
            $objRSS->channelTitle = $this->rssLatestTitle;
            $objRSS->channelDescription = $this->rssLatestDescription;
            $objRSS->channelWebmaster = $_CONFIG['coreAdminEmail'];
            $objRSS->newsLimit = $limit;
            $objRSS->channelLink = ASCMS_PROTOCOL."://".$_CONFIG['domainUrl'].ASCMS_PATH_OFFSET."/".CONTREXX_DIRECTORY_INDEX."?section=Directory";

            $objRSS->create();
        }
    }


    function getAuthor($id)
    {
        global $objDatabase, $_ARRAYLANG;

        $userId = contrexx_addslashes($id);
        $author = '';

        if (is_numeric($userId)) {
            $objResultauthor = $objDatabase->Execute("SELECT id, username FROM ".DBPREFIX."access_users WHERE id = '".$userId."'");
            if ($objResultauthor !== false) {
                while (!$objResultauthor->EOF) {
                    $author = $objResultauthor->fields['username'];
                    $objResultauthor->MoveNext();
                }
            }
        } else {
            $author = $userId;
        }

        return $author;
    }


    function getAuthorID($author)
    {
        global $objDatabase, $_ARRAYLANG;

        $objResultauthor = $objDatabase->Execute("SELECT id, username FROM ".DBPREFIX."access_users WHERE username = '".contrexx_addslashes($author)."'");
        if ($objResultauthor !== false) {
            while (!$objResultauthor->EOF) {
                $author = $objResultauthor->fields['id'];
                $objResultauthor->MoveNext();
            }
        }

        return $author;
    }



    function getInputfields($addedby, $action, $id, $area)
    {
        global $objDatabase, $_ARRAYLANG;

        //get settings
        $i=0;
          $width= "300";

        $this->_objTpl->setCurrentBlock('inputfieldsOutput');
        $arrInputfieldsActive = array(
            'name'        => array(),
            'typ'         => array(),
            'read_only'   => array(),
            'title'       => array(),
            'is_required' => array(),
        );
        $arrInputfieldsValue = array();
        $arrSettings = $this->getSettings();

        if ($area == "backend") {
            $where = "active_backend='1'";
        } elseif ($area == "frontend") {
            $where = "active='1'";
        }

        //get inputfields
        if ($action == "add") {
            //get plattforms and languages and user
            $arrInputfieldsValue['language'] =
                $this->getLanguages(
                  (empty($_POST['inputValue']['language'])
                    ? ''
                    : $_POST['inputValue']['language'])
                );
            $arrInputfieldsValue['platform'] =
                $this->getPlatforms(
                  (empty($_POST['inputValue']['platform'])
                    ? ''
                    : $_POST['inputValue']['platform'])
                );
            $arrInputfieldsValue['canton'] =
                $this->getCantons(
                  (empty($_POST['inputValue']['canton'])
                    ? ''
                    : $_POST['inputValue']['canton'])
                );
            $arrInputfieldsValue['country'] =
                $this->getCountryMenuoptions(
                    (empty($_POST['inputValue']['country'])
                       ? '' : $_POST['inputValue']['country'])
                );
            $arrInputfieldsValue['spez_field_21'] =
                $this->getSpezDropdown(
                  (empty($_POST['inputValue']['spez_field_21'])
                    ? ''
                    : $_POST['inputValue']['spez_field_21']),
                  'spez_field_21');
            $arrInputfieldsValue['spez_field_22'] =
                $this->getSpezDropdown(
                  (empty($_POST['inputValue']['spez_field_22'])
                    ? ''
                    : $_POST['inputValue']['spez_field_22']),
                  'spez_field_22');
            $arrInputfieldsValue['spez_field_23'] =
                $this->getSpezVotes(
                  (empty($_POST['inputValue']['spez_field_23'])
                    ? ''
                    : $_POST['inputValue']['spez_field_23']),
                  'spez_field_23');
            $arrInputfieldsValue['spez_field_24'] =
                $this->getSpezVotes(
                  (empty($_POST['inputValue']['spez_field_24'])
                    ? ''
                    : $_POST['inputValue']['spez_field_24']),
                  'spez_field_24');
            $arrInputfieldsValue['addedby'] = $addedby;
        } elseif ($action == "edit" || $action == "confirm") {
            //get file data
            $objResult = $objDatabase->Execute("SELECT * FROM ".DBPREFIX."module_directory_dir WHERE id = ".intval($id));
            if ($objResult !== false) {
                while(!$objResult->EOF) {
                    $arrInputfieldsValue['id'] = $objResult->fields['id'];
                    $arrInputfieldsValue['title'] = $objResult->fields['title'];
                    $arrInputfieldsValue['attachment'] = $objResult->fields['attachment'];
                    $arrInputfieldsValue['rss_link'] = $objResult->fields['rss_link'];
                    $arrInputfieldsValue['link'] = $objResult->fields['link'];
                    $arrInputfieldsValue['date'] = $objResult->fields['date'];
                    $arrInputfieldsValue['description'] = $objResult->fields['description'];
                    $arrInputfieldsValue['relatedlinks'] = $objResult->fields['relatedlinks'];
                    $arrInputfieldsValue['status'] = $objResult->fields['status'];
                    $arrInputfieldsValue['addedby'] = $objResult->fields['addedby'];
                    $arrInputfieldsValue['validatedate'] = $objResult->fields['validatedate'];
                    $arrInputfieldsValue['platform'] = $objResult->fields['platform'];
                    $arrInputfieldsValue['language'] = $objResult->fields['language'];
                    $arrInputfieldsValue['canton'] = $objResult->fields['canton'];
                    $arrInputfieldsValue['searchkeys'] = $objResult->fields['searchkeys'];
                    $arrInputfieldsValue['company_name'] = $objResult->fields['company_name'];
                    $arrInputfieldsValue['street'] = $objResult->fields['street'];
                    $arrInputfieldsValue['zip'] = $objResult->fields['zip'];
                    $arrInputfieldsValue['phone'] = $objResult->fields['phone'];
                    $arrInputfieldsValue['contact'] = $objResult->fields['contact'];
                    $arrInputfieldsValue['hits'] = $objResult->fields['hits'];
                    $arrInputfieldsValue['xml_refresh'] = $objResult->fields['xml_refresh'];
                    $arrInputfieldsValue['city'] = $objResult->fields['city'];
                    $arrInputfieldsValue['information'] = $objResult->fields['information'];
                    $arrInputfieldsValue['fax'] = $objResult->fields['fax'];
                    $arrInputfieldsValue['mobile'] = $objResult->fields['mobile'];
                    $arrInputfieldsValue['mail'] = $objResult->fields['mail'];
                    $arrInputfieldsValue['homepage'] = $objResult->fields['homepage'];
                    $arrInputfieldsValue['industry'] = $objResult->fields['industry'];
                    $arrInputfieldsValue['legalform'] = $objResult->fields['legalform'];
                    $arrInputfieldsValue['conversion'] = $objResult->fields['conversion'];
                    $arrInputfieldsValue['employee'] = $objResult->fields['employee'];
                    $arrInputfieldsValue['foundation'] = $objResult->fields['foundation'];
                    $arrInputfieldsValue['mwst'] = $objResult->fields['mwst'];
                    $arrInputfieldsValue['opening'] = $objResult->fields['opening'];
                    $arrInputfieldsValue['holidays'] = $objResult->fields['holidays'];
                    $arrInputfieldsValue['places'] = $objResult->fields['places'];
                    $arrInputfieldsValue['logo'] = $objResult->fields['logo'];
                    $arrInputfieldsValue['team'] = $objResult->fields['team'];
                    $arrInputfieldsValue['portfolio'] = $objResult->fields['portfolio'];
                    $arrInputfieldsValue['offers'] = $objResult->fields['offers'];
                    $arrInputfieldsValue['concept'] = $objResult->fields['concept'];
                    $arrInputfieldsValue['map'] = $objResult->fields['map'];
                    $arrInputfieldsValue['lokal'] = $objResult->fields['lokal'];

                    $arrInputfieldsValue['longitude'] = $objResult->fields['longitude'];
                    $arrInputfieldsValue['latitude'] = $objResult->fields['latitude'];
                    $arrInputfieldsValue["lon"] = substr($objResult->fields['longitude'], 0, strpos($objResult->fields['longitude'], '.'));
                    $arrInputfieldsValue["lon_fraction"] = substr($objResult->fields['longitude'],      strpos($objResult->fields['longitude'], '.')+1);
                    $arrInputfieldsValue["lat"] = substr($objResult->fields['latitude'],  0, strpos($objResult->fields['latitude'], '.'));
                    $arrInputfieldsValue["lat_fraction"] = substr($objResult->fields['latitude'],      strpos($objResult->fields['latitude'], '.')+1);
                    $arrInputfieldsValue['zoom'] = $objResult->fields['zoom'];
                    $arrInputfieldsValue['country'] = $objResult->fields['country'];

                    $arrInputfieldsValue['spez_field_1'] = $objResult->fields['spez_field_1'];
                    $arrInputfieldsValue['spez_field_2'] = $objResult->fields['spez_field_2'];
                    $arrInputfieldsValue['spez_field_3'] = $objResult->fields['spez_field_3'];
                    $arrInputfieldsValue['spez_field_4'] = $objResult->fields['spez_field_4'];
                    $arrInputfieldsValue['spez_field_5'] = $objResult->fields['spez_field_5'];
                    $arrInputfieldsValue['spez_field_6'] = $objResult->fields['spez_field_6'];
                    $arrInputfieldsValue['spez_field_7'] = $objResult->fields['spez_field_7'];
                    $arrInputfieldsValue['spez_field_8'] = $objResult->fields['spez_field_8'];
                    $arrInputfieldsValue['spez_field_9'] = $objResult->fields['spez_field_9'];
                    $arrInputfieldsValue['spez_field_10'] = $objResult->fields['spez_field_10'];
                    $arrInputfieldsValue['spez_field_11'] = $objResult->fields['spez_field_11'];
                    $arrInputfieldsValue['spez_field_12'] = $objResult->fields['spez_field_12'];
                    $arrInputfieldsValue['spez_field_13'] = $objResult->fields['spez_field_13'];
                    $arrInputfieldsValue['spez_field_14'] = $objResult->fields['spez_field_14'];
                    $arrInputfieldsValue['spez_field_15'] = $objResult->fields['spez_field_15'];
                    $arrInputfieldsValue['spez_field_16'] = $objResult->fields['spez_field_16'];
                    $arrInputfieldsValue['spez_field_17'] = $objResult->fields['spez_field_17'];
                    $arrInputfieldsValue['spez_field_18'] = $objResult->fields['spez_field_18'];
                    $arrInputfieldsValue['spez_field_19'] = $objResult->fields['spez_field_19'];
                    $arrInputfieldsValue['spez_field_20'] = $objResult->fields['spez_field_20'];
                    $arrInputfieldsValue['spez_field_21'] = $objResult->fields['spez_field_21'];
                    $arrInputfieldsValue['spez_field_22'] = $objResult->fields['spez_field_22'];
                    $arrInputfieldsValue['spez_field_23'] = $objResult->fields['spez_field_23'];
                    $arrInputfieldsValue['spez_field_24'] = $objResult->fields['spez_field_24'];
                    $arrInputfieldsValue['spez_field_25'] = $objResult->fields['spez_field_25'];
                    $arrInputfieldsValue['spez_field_26'] = $objResult->fields['spez_field_26'];
                    $arrInputfieldsValue['spez_field_27'] = $objResult->fields['spez_field_27'];
                    $arrInputfieldsValue['spez_field_28'] = $objResult->fields['spez_field_28'];
                    $arrInputfieldsValue['spez_field_29'] = $objResult->fields['spez_field_29'];
                    $arrInputfieldsValue['youtube'] = $objResult->fields['youtube'];
                    $objResult->MoveNext();
                }
            }

            //get plattforms and languages and user
            $arrInputfieldsValue['platform'] = $this->getPlatforms($arrInputfieldsValue['platform']);
            $arrInputfieldsValue['language'] = $this->getLanguages($arrInputfieldsValue['language']);
            $arrInputfieldsValue['canton'] = $this->getCantons($arrInputfieldsValue['canton']);
            $arrInputfieldsValue['country'] = $this->getCountryMenuoptions($arrInputfieldsValue['country']);
            $arrInputfieldsValue['spez_field_21'] = $this->getSpezDropdown($arrInputfieldsValue['spez_field_21'], 'spez_field_21');
            $arrInputfieldsValue['spez_field_22'] = $this->getSpezDropdown($arrInputfieldsValue['spez_field_22'], 'spez_field_22');
            $arrInputfieldsValue['spez_field_23'] = $this->getSpezVotes($arrInputfieldsValue['spez_field_23'], 'spez_field_23');
            $arrInputfieldsValue['spez_field_24'] = $this->getSpezVotes($arrInputfieldsValue['spez_field_24'], 'spez_field_24');
        }
        $objResult = $objDatabase->Execute("SELECT * FROM ".DBPREFIX."module_directory_inputfields WHERE ".$where." ORDER BY sort ASC");
        if ($objResult !== false) {
            while(!$objResult->EOF) {
                $arrInputfieldsActive['name'][$objResult->fields['id']] = $objResult->fields['name'];
                $arrInputfieldsActive['typ'][$objResult->fields['id']] = $objResult->fields['typ'];
                $arrInputfieldsActive['read_only'][$objResult->fields['id']] = $objResult->fields['read_only'];
                $arrInputfieldsActive['title'][$objResult->fields['id']] = $objResult->fields['title'];
                $arrInputfieldsActive['is_required'][$objResult->fields['id']] = $objResult->fields['is_required'];
                $objResult->MoveNext();
            }
        }

        //form action
        if ($arrSettings['levels']['int'] == 1) {
            $formOnSubmit = "selectAll(document.addForm.elements['selectedCat[]']); selectAll(document.addForm.elements['selectedLevel[]']); return CheckFields();";
        } else {
            $formOnSubmit = "selectAll(document.addForm.elements['selectedCat[]']); return CheckFields();";
        }

        $javascript = <<< EOF
<script language="JavaScript" type="text/javascript">
/* <![CDATA[ */
function move(from, dest, add, remove)
{
  if (from.selectedIndex < 0) {
    if (from.options[0] != null) from.options[0].selected = true;
    from.focus();
    return false;
  } else {
    for (i = 0; i < from.length; ++i) {
      if (from.options[i].selected) {
        dest.options[dest.options.length] = new Option(from.options[i].text, from.options[i].value, false, false);
      }
    }
    for (i = from.options.length-1; i >= 0; --i) {
      if (from.options[i].selected) {
        from.options[i] = null;
      }
    }
  }
  disableButtons(from, dest, add, remove);
}

function disableButtons(from, dest, add, remove)
{
  if (from.options.length > 0) {
    add.disabled = 0;
  } else {
    add.disabled = 1;
  }
  if (dest.options.length > 0) {
    remove.disabled = 0;
  } else {
    remove.disabled = 1;
  }
}

function selectAll(control)
{
  for (i = 0; i < control.length; ++i) {
    control.options[i].selected = true;
  }
}

function deselectAll(control)
{
  for (i = 0; i < control.length; ++i) {
    control.options[i].selected = false;
  }
}
EOF;

        //default required fields
        $javascript .= '
function CheckFields() {
  var errorMsg = "";
  with (document.addForm) {
    if (document.getElementsByName(\'selectedCat[]\')[0].value == "") {
      errorMsg = errorMsg + "- '.$_ARRAYLANG['TXT_DIR_CATEGORIE'].'\n";
    }
';
        if ($arrSettings['levels']['int'] == 1) {
            $javascript     .= '
    if (document.getElementsByName(\'selectedLevel[]\')[0].value == "") {
      errorMsg = errorMsg + "- '.$_ARRAYLANG['TXT_LEVEL'].'\n";
    }
';
        }

        $initImgUploader  = false;
        $initFileUploader = false;
        foreach($arrInputfieldsActive['name'] as $inputKey => $inputName) {
            $disabled = "";
            $inputValueField = "";
            $fieldName = isset($_ARRAYLANG[$arrInputfieldsActive['title'][$inputKey]])
                ? $_ARRAYLANG[$arrInputfieldsActive['title'][$inputKey]] : '';

            if ($arrSettings['levels']['int'] == 1) {
                ($i % 2)? $class = "row2" : $class = "row1";
            } else {
                ($i % 2)? $class = "row1" : $class = "row2";
            }

            switch ($arrInputfieldsActive['typ'][$inputKey]) {
                case '1':
                    if ($arrInputfieldsActive['read_only'][$inputKey] == 1) {
                        $disabled = "disabled";
                        $inputValueField =
                            "<input type=\"hidden\" name=\"inputValue[".
                            $inputName."]\" value=\"".$arrInputfieldsValue[$inputName].
                            "\" style=\"width:".$width."px;\" maxlength='250' />";
                    }
                    $value = '';
                    if ($inputName == "addedby") {
                        $value = $this->getAuthor($arrInputfieldsValue[$inputName]);
                        if ($action == "edit") {
                            $authorId = isset($_POST['inputValue'][$inputName])
                                ? $_POST['inputValue'][$inputName] : 0;
                            $value .= $this->getAuthor($authorId);
                        }
                    } elseif (isset($arrInputfieldsValue[$inputName])) {
                        $value = $arrInputfieldsValue[$inputName];
                    }

                    $inputValueField .=
                        "<input type=\"text\" name=\"inputValue[".
                        $inputName.$disabled."]\" value=\"".$value.
                        "\" style=\"width:".$width."px;\" maxlength='250' ".
                        $disabled." />";
                    break;
                case '2':
                    $inputValueField =
                        "<textarea name=\"inputValue[".$inputName.
                        "]\" style=\"width:".$width."px; overflow: auto;\" rows='7'>".
                        (isset($arrInputfieldsValue[$inputName])
                          ? $arrInputfieldsValue[$inputName] : '').
                        "</textarea>";
                    break;
                case '3':
                     $inputValueField =
                        "<select name=\"inputValue[".$inputName.
                        "]\" style=\"width:".($width+2)."px;\">".
                        $arrInputfieldsValue[$inputName]."</select>";
                    break;
                case '4':
                    if ($action !== "add") {
                        if (!file_exists($this->mediaPath."images/".$arrInputfieldsValue[$inputName]) || $arrInputfieldsValue[$inputName] == "" || $arrInputfieldsValue[$inputName] == "no_picture.gif") {
                            $inputValueField =
                                "<img src='".$this->mediaWebPath.
                                "images/no_picture.gif' alt='' /><br /><br />";
                        } else {
                            if ($action !== "confirm") {
                                $inputValueField =
                                    "<img src='".$this->mediaWebPath."thumbs/".
                                    $arrInputfieldsValue[$inputName].
                                    "' alt='' /><br /><input type=\"checkbox\" ".
                                    "value=\"1\" name=\"deleteMedia[".$inputName.
                                    "]\" />".$_ARRAYLANG['TXT_DIR_DEL']."<br /><br />";
                            } else {
                                $inputValueField =
                                    "<img src='".$this->mediaWebPath."thumbs/".
                                    $arrInputfieldsValue[$inputName].
                                    "' alt='' /><br /><input type=\"checkbox\" ".
                                    "value=\"1\" name=\"deleteMedia[".$inputName.
                                    "]\" />".$_ARRAYLANG['TXT_DIR_DEL']."<br /><br />";
                            }
                        }
                    }
                    if ($action !== "confirm") {
                        $initImgUploader = true;
                        $inputValueField .=
                            "<input type=\"text\" name=\"{$inputName}\" id=\"input_{$inputName}\" value style=\"width:".$width."px;\" />";
                        $inputValueField .=
                            "&nbsp;&nbsp;<input type=\"button\" data-id=\"input_{$inputName}\" class=\"imgUpload\"
                            size=\"37\" value=\"Choose File\" />";

                        $inputValueField .=
                            "<input type=\"hidden\" name=\"inputValue[".
                            $inputName."]\" class=\"input_{$inputName}\" value='".
                            $arrInputfieldsValue[$inputName]."' />";
                    }
                    break;
                case '5':
                    $inputValueField .=
                        "<input type=\"text\" name=\"inputValue[".$inputName.
                        "]\" value=\"".$arrInputfieldsValue[$inputName].
                        "\" style=\"width:".$width."px;\" maxlength='250' />";
                    $fieldName = $arrInputfieldsActive['title'][$inputKey];
                    break;
                case '6':
                    $inputValueField =
                        "<textarea name=\"inputValue[".$inputName.
                        "]\" style=\"width:".$width."px; overflow: auto;\" rows='7'>".
                        $arrInputfieldsValue[$inputName]."</textarea>";
                    $fieldName = $arrInputfieldsActive['title'][$inputKey];
                    break;
                case '7':
                    if ($action !== "add") {
                        if (!file_exists($this->mediaPath."images/".$arrInputfieldsValue[$inputName]) || $arrInputfieldsValue[$inputName] == "") {
                            $inputValueField =
                                "<img src='".$this->mediaWebPath.
                                "images/no_picture.gif' alt='' /><br /><br />";
                        } else {
                            if ($action !== "confirm") {
                                $inputValueField =
                                    "<img src='".$this->mediaWebPath."thumbs/".
                                    $arrInputfieldsValue[$inputName].
                                    "' alt='' /><br /><input type=\"checkbox\" ".
                                    "value=\"1\" name=\"deleteMedia[".$inputName.
                                    "]\" />".$_ARRAYLANG['TXT_DIR_DEL']."<br /><br />";
                            } else {
                                $inputValueField =
                                    "<img src='".$this->mediaWebPath."thumbs/".
                                    $arrInputfieldsValue[$inputName].
                                    "' alt='' /><br /><input type=\"checkbox\" ".
                                    "value=\"1\" name=\"deleteMedia[".$inputName.
                                    "]\" />".$_ARRAYLANG['TXT_DIR_DEL']."<br /><br />";
                            }
                        }
                    }
                    if ($action !== "confirm") {
                        $initImgUploader  = true;
                        $inputFieldValue  = !empty($arrInputfieldsValue[$inputName])
                            ? $arrInputfieldsValue[$inputName] : '';
                        $inputValueField .=
                            "<input type=\"text\" name=\"$inputName\" id=\"input_{$inputName}\" value style=\"width:".$width."px;\" />";
                        $inputValueField .=
                            "&nbsp;&nbsp;<input type=\"button\" data-id=\"input_{$inputName}\" class=\"imgUpload\"
                            size=\"37\" value=\"Choose File\" />";

                        $inputValueField .=
                            "<input type=\"hidden\" name=\"inputValue[".
                            $inputName."]\" class=\"input_{$inputName}\" value='".
                            $inputFieldValue ."' />";
                    }
                    $fieldName = $arrInputfieldsActive['title'][$inputKey];
                    break;
                case '8':
                    $inputValueField =
                        "<select name=\"inputValue[".$inputName.
                        "]\" style=\"width:".($width+2)."px;\">".
                        $arrInputfieldsValue[$inputName]."</select>";
                    $fieldName = $arrInputfieldsActive['title'][$inputKey];
                    break;
                case '9':
                    $inputValueField = "<select name=\"inputValue[".$inputName."]\" style=\"width:".($width+2)."px;\">".$arrInputfieldsValue[$inputName]."</select>";
                    $fieldName = $arrInputfieldsActive['title'][$inputKey];
                    break;
                case '10':
                    if ($action !== "add") {
                        if (!file_exists($this->mediaPath."uploads/".$arrInputfieldsValue[$inputName]) || $arrInputfieldsValue[$inputName] == "") {
                            $inputValueField = "-<br /><br />";
                        } else {
                            if ($action !== "confirm") {
                                $inputValueField =
                                    "<a href='".$this->mediaWebPath."uploads/".
                                    $arrInputfieldsValue[$inputName].
                                    "' target='_blank' />".
                                    $arrInputfieldsValue[$inputName].
                                    "</a><br /><input type=\"checkbox\" ".
                                    "value=\"1\" name=\"deleteMedia[".$inputName."]\" />".
                                    $_ARRAYLANG['TXT_DIR_DEL']."<br /><br />";
                            } else {
                                $inputValueField =
                                    "<a href='".$this->mediaWebPath."uploads/".
                                    $arrInputfieldsValue[$inputName].
                                    "' target='_blank' />".
                                    $arrInputfieldsValue[$inputName].
                                    "</a><br /><input type=\"checkbox\" ".
                                    "value=\"1\" name=\"deleteMedia[".$inputName."]\" />".
                                    $_ARRAYLANG['TXT_DIR_DEL']."<br /><br />";
                            }
                        }
                    }
                    if ($action !== "confirm") {
                        $initFileUploader = true;
                        $inputValueField .=
                            "<input type=\"text\" name=\"{$inputName}\"
                            id=\"input_{$inputName}\" value style=\"width:".$width."px;\" />";
                        $inputValueField .=
                            "&nbsp;&nbsp;<input type=\"button\" data-id=\"input_{$inputName}\" class=\"fileUpload\"
                            size=\"37\" value=\"Choose File\" />";

                        $inputValueField .=
                            "<input type=\"hidden\" name=\"inputValue[".
                            $inputName."]\" class=\"input_{$inputName}\" value='".
                            $arrInputfieldsValue[$inputName]."' />";
                    }
                    $fieldName = $arrInputfieldsActive['title'][$inputKey];
                    break;
                case '11':
                     if ($action !== "add") {
                        if (!file_exists($this->mediaPath."uploads/".$arrInputfieldsValue[$inputName]) || $arrInputfieldsValue[$inputName] == "") {
                            $inputValueField = "-<br /><br />";
                        } else {
                            if ($action !== "confirm") {
                                $inputValueField =
                                    "<a href='".$this->mediaWebPath."uploads/".
                                    $arrInputfieldsValue[$inputName].
                                    "' target='_blank' />".
                                    $arrInputfieldsValue[$inputName].
                                    "</a><br /><input type=\"checkbox\" ".
                                    "value=\"1\" name=\"deleteMedia[".$inputName."]\" />".
                                    $_ARRAYLANG['TXT_DIR_DEL']."<br /><br />";
                            } else {
                                $inputValueField =
                                    "<a href='".$this->mediaWebPath."uploads/".
                                    $arrInputfieldsValue[$inputName].
                                    "' target='_blank' />".
                                    $arrInputfieldsValue[$inputName]."</a>";
                            }
                        }
                    }

                    if ($action !== "confirm") {
                        $initFileUploader = true;
                        $inputValueField .=
                            "<input type=\"text\" name=\"{$inputName}\"
                            id=\"input_{$inputName}\" value style=\"width:".$width."px;\" />";
                        $inputValueField .=
                            "&nbsp;&nbsp;<input type=\"button\" data-id=\"input_{$inputName}\" class=\"fileUpload\"
                            size=\"37\" value=\"Choose File\" />";

                        $inputValueField .=
                            "<input type=\"hidden\" name=\"inputValue[".
                            $inputName."]\" class=\"input_{$inputName}\" value='".
                            $arrInputfieldsValue[$inputName]."' />";
                    }
                    break;
                case '12':
                    $inputValueField .=
                        "<input type=\"text\" name=\"inputValue[".$inputName.
                        "]\" value=\"".$arrInputfieldsValue[$inputName].
                        "\" style=\"width:".$width."px;\" maxlength='250' />";
                    break;
                case '13':
                    $inputValueField .=
                        $_ARRAYLANG['TXT_DIR_LON'].
                        ': <input type="text" name="inputValue[lon]" value="'.
                        $arrInputfieldsValue["lon"].
                        '" style="width:22px;" maxlength="3" />'.
                        '.<input type="text" name="inputValue[lon_fraction]" value="'.
                        $arrInputfieldsValue["lon_fraction"].
                        '" style="width:92px;" maxlength="15" /> '.
                        $_ARRAYLANG['TXT_DIR_LAT'].
                        ': <input type="text" name="inputValue[lat]" value="'.
                        $arrInputfieldsValue["lat"].
                        '" style="width:22px;" maxlength="15" />'.
                        '.<input type="text" name="inputValue[lat_fraction]" value="'.
                        $arrInputfieldsValue["lat_fraction"].
                        '" style="width:92px;" maxlength="15" /> '.
                        $_ARRAYLANG['TXT_DIR_ZOOM'].
                        ': <input type="text" name="inputValue[zoom]" value="'.
                        $arrInputfieldsValue["zoom"].
                        '" style="width:15px;" maxlength="2" />'.
                        '<a href="javascript:void(0);" onclick="getAddress();"> '.
                        $_ARRAYLANG['TXT_DIR_SEARCH_ADDRESS'].'</a><br />'.
                        '<span id="geostatus"></span>'.
                        '<div id="gmap" style="margin:2px; border:1px solid;width: 400px; height: 300px;"></div>'.
                        '<div id="loclayer" style="-moz-opacity: 0.85; filter: alpha(opacity=85); background-color: #dedede; padding: 2px; border: 1px solid; width: 198px; height: 42px; position: relative; top: -270px; left: 200px;"></div>';
                    break;
            }

            $required = "";
            if (   $arrInputfieldsActive['is_required'][$inputKey] == 1
                && strtolower($inputName) != 'googlemap') {
                $required = "<font color='red'>*</font>";
                $javascript .= '
if (document.getElementsByName(\'inputValue['.$inputName.']\')[0].value == "") {
  errorMsg = errorMsg + "- '.$fieldName.'\n";
}';
            }

            // initialize variables
            $this->_objTpl->setVariable(array(
                'FIELD_ROW' => $class,
                'FIELD_VALUE' => $inputValueField,
                'FIELD_NAME' => $fieldName,
                'FIELD_REQUIRED' => $required,
                'DIRECTORY_FORM_ONSUBMIT' => $formOnSubmit,
            ));

            $this->_objTpl->parse('inputfieldsOutput');
            $i++;
        }

        $javascript .= '
  }
  if (errorMsg != "") {
      alert ("'.$_ARRAYLANG['TXT_DIR_FILL_ALL'].':\n\n" + errorMsg);
      return false;
  } else {
      return true;
  }
}
    var currentUploadObj;
    $J(document).ready(function(){
       $J(\'.imgUpload\').live(\'click\', function(){
          currentUploadObj = $J(this);
          $J(\'#imageUploader\').trigger(\'click\');
       });
       $J(\'.fileUpload\').live(\'click\', function(){
          currentUploadObj = $J(this);
          $J(\'#fileUploader\').trigger(\'click\');
       });
    });
    function uploaderCallbackJs(path) {
        if (typeof path[0] !== \'undefined\') {
            var data     = path[0].split(\'/\'),
                fileName = data.pop(),
                className = currentUploadObj.attr(\'data-id\');
            $J(\'#\' + className + \'\').val(fileName);
            if ($J(\'.\' + className + \'\').val() == \'\') {
                $J(\'.\' + className + \'\').val(fileName);
            }
        }
    }
/* ]]> */
</script>';

        //initialize the uploader
        if ($initImgUploader) {
            $options = array(
                'id'    => 'imageUploader',
                'style' => 'display:none',
                'allowed-extensions' => array('jpg', 'jpeg', 'png', 'gif'),
                'data-upload-limit'  => 1
            );
            $imgUploaderObj = $this->getUploader('uploaderCallbackJs', $options);
            $this->_objTpl->setVariable(array(
                'DIRECTORY_IMAGE_UPLOADER_CODE' => $imgUploaderObj->getXHtml(),
                'DIRECTORY_IMAGE_UPLOADER_ID'   => $imgUploaderObj->getId()
            ));
        }
        if ($initFileUploader) {
            $options = array(
                'id'    => 'fileUploader',
                'style' => 'display:none',
                'data-upload-limit'  => 1
            );
            $fileUploaderObj = $this->getUploader('uploaderCallbackJs', $options);
            $this->_objTpl->setVariable(array(
                'DIRECTORY_FILE_UPLOADER_CODE' => $fileUploaderObj->getXHtml(),
                'DIRECTORY_FILE_UPLOADER_ID'   => $fileUploaderObj->getId()
            ));
        }
        // initialize variables
        $this->_objTpl->setVariable(
            'DIRECTORY_JAVASCRIPT', $javascript
        );
    }

    /**
     * Get the uploader
     *
     * @param string $callbackJs callback javascript function name
     * @param array  $options    uploader options
     *
     * @return \Cx\Core_Modules\Uploader\Model\Entity\Uploader
     */
    public function getUploader($callbackJs, $options = array())
    {
        $uploader = new \Cx\Core_Modules\Uploader\Model\Entity\Uploader();
        //set instance name so we are able to catch the instance with js
        if (!empty($callbackJs)) {
            $uploader->setCallback($callbackJs);
        }
        if (!empty($options)) {
            $uploader->setOptions($options);
        }
        return $uploader;
    }

    /**
     * check if googlemap is enabled
     * @param string backend or frontend
     * @return boolean isGoogleMapEnabled
     */
    function _isGoogleMapEnabled($what = 'backend') {
        global $objDatabase;
        $what = ($what == 'backend') ? '_backend' : '';
        $query = "    SELECT `active".$what."` as isactive
                    FROM `".DBPREFIX."module_directory_inputfields`
                    WHERE `name` = 'googlemap' AND `typ` = 13";
        $objRS = $objDatabase->SelectLimit($query, 1);
        if ($objRS === false) {
            die(__FILE__.':'.__LINE__.' '.$objDatabase->ErrorMsg());
        }
        return $objRS->fields['isactive'];
    }


    function getSettings()
    {
        global $objDatabase, $_ARRAYLANG;

        //get settings
        $objResult = $objDatabase->Execute("SELECT setname, setvalue, settyp FROM ".DBPREFIX."module_directory_settings");
        if ($objResult !== false) {
            while(!$objResult->EOF) {
                $settings[$objResult->fields['setname']]['value'] = $objResult->fields['setvalue'];
                if ($objResult->fields['settyp'] == 2) {
                    $settings[$objResult->fields['setname']]['int'] = $objResult->fields['setvalue'] ==1 ? "1" : "0";
                    $settings[$objResult->fields['setname']]['boolean'] = $objResult->fields['setvalue'] ==1 ? "true" : "false";
                    $settings[$objResult->fields['setname']]['selected'] = $objResult->fields['setvalue'] ==1 ? "selected" : "";
                    $settings[$objResult->fields['setname']]['disabled'] = $objResult->fields['setvalue'] ==1 ? "" : "disabled";
                    $settings[$objResult->fields['setname']]['checked'] = $objResult->fields['setvalue'] ==1 ? "checked" : "";
                    $settings[$objResult->fields['setname']]['display'] = $objResult->fields['setvalue'] ==1 ? "block" : "none";
                }
                if ($objResult->fields['setname'] == 'googlemap_start_location') {
                    $arrGoogleStartPoint = explode(':', $objResult->fields['setvalue']);
                    $this->googleMapStartPoint = array( 'lat' =>$arrGoogleStartPoint[0],
                                                        'lon' =>$arrGoogleStartPoint[1],
                                                        'zoom' =>$arrGoogleStartPoint[2]);
                }
                $objResult->MoveNext();
            }
        }

        return $settings;
    }


   /**
    * count
    * @access   public
    * @param    string  $id
    */
    function count($lid, $cid)
    {
        global $objDatabase;

        if (empty($cid)) {
            $this->countLevels($lid, $lid);
            $count = $this->countFeeds($this->numLevels[$lid], 'level', $lid);
        } else {
            $this->countCategories($cid, $cid);
            $count = $this->countFeeds($this->numCategories[$cid], 'cat', $lid);
        }

        return intval($count);
    }



    /**
    * Count categories
    * @access   public
    * @param    string  $id
    */
    function countCategories($ckey, $cid)
    {
        global $objDatabase;

        if (!isset($this->numCategories[$ckey])) {
            $this->numCategories[$ckey] = array();
        }
        $this->numCategories[$ckey][] = $cid;
        $objResultCat = $objDatabase->Execute("SELECT id FROM ".DBPREFIX."module_directory_categories WHERE status = 1 AND parentid =".intval($cid));
        if ($objResultCat !== false) {
            while(!$objResultCat->EOF) {
                $this->countCategories($ckey, $objResultCat->fields['id']);
                $objResultCat->MoveNext();
            }
        }
    }


    /**
    * Count levels
    * @access   public
    * @param    string  $id
    */
    function countLevels($lkey, $lid)
    {
        global $objDatabase;

        if (!isset($this->numLevels[$lkey])) {
            $this->numLevels[$lkey] = array();
        }
        $this->numLevels[$lkey][] = $lid;
        $objResultLevel = $objDatabase->Execute("SELECT id FROM ".DBPREFIX."module_directory_levels WHERE status = 1 AND parentid =".intval($lid));
        if ($objResultLevel !== false) {
            while(!$objResultLevel->EOF) {
                $this->countLevels($lkey, $objResultLevel ->fields['id']);
                $objResultLevel->MoveNext();
            }
        }
    }

    /**
    * Count feeds
    * @access   public
    * @param    string  $id
    */
    function countFeeds($array, $type, $level)
    {
        global $objDatabase;

        if ($type == 'level') {
            $query = '
            SELECT
                SUM(1) AS feedCount
            FROM
                `'.DBPREFIX.'module_directory_dir` AS files
                INNER JOIN `'.DBPREFIX.'module_directory_rel_dir_level` AS rel_level ON rel_level.`dir_id`=files.`id`
            WHERE
                (rel_level.`level_id`='.implode(' OR rel_level.`level_id`=', $array).')
                AND `status` !=0';
        } elseif (!empty($level)) {
            $query = '
            SELECT
                SUM(1) AS feedCount
            FROM
                `'.DBPREFIX.'module_directory_dir` AS files
                INNER JOIN `'.DBPREFIX.'module_directory_rel_dir_cat` AS rel_cat ON rel_cat.`dir_id`=files.`id`
                INNER JOIN `'.DBPREFIX.'module_directory_rel_dir_level` AS rel_level USING (`dir_id`)
            WHERE
                (rel_cat.`cat_id`='.implode(' OR rel_cat.`cat_id`=', $array).')
                AND rel_level.`level_id`='.intval($level).'
                AND `status` !=0';
        } else {
            $query = '
            SELECT
                SUM(1) AS feedCount
            FROM
                `'.DBPREFIX.'module_directory_dir` AS files
                INNER JOIN `'.DBPREFIX.'module_directory_rel_dir_cat` AS rel_cat ON rel_cat.`dir_id`=files.`id`
            WHERE
                (rel_cat.`cat_id`='.implode(' OR rel_cat.`cat_id`=', $array).')
                AND `status` !=0';
        }

        $objResultCount = $objDatabase->SelectLimit($query, 1);
        if ($objResultCount !== false) {
            return $objResultCount->fields['feedCount'];
        } else {
            return 0;
        }
    }


    /**
    * update selected file
    * @access   public
    * @global    array
    * @global    ADONewConnection
    * @global    array
    */
    function updateFile($addedby)
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;
        //get post data
        if (isset($_POST['edit_submit'])) {
            $dirId = intval($_POST['edit_id']);
            $query = "UPDATE ".DBPREFIX."module_directory_dir SET ";

            foreach($_POST["inputValue"] as $inputName => $inputValue) {

                switch ($inputName) {
                    case 'lat':
                    case 'lat_fraction':
                    case 'lon':
                    case 'lon_fraction':
                    case 'zoom':
                        continue 2;
                }

                //check links
                if ($inputName == "relatedlinks" || $inputName == "homepage" || $inputName == "link") {
                    if (substr($inputValue, 0,7) != "http://" && $inputValue != "") {
                        $inputValue = "http://".$inputValue;
                    }
                }

                //check rss
                if ($inputName == "rss_link") {
                    $objResultRSS = $objDatabase->SelectLimit("SELECT rss_link, rss_file FROM ".DBPREFIX."module_directory_dir WHERE id = '".$dirId."'",1);
                    $oldRssLink = $objResultRSS->fields['rss_link'];
                    $oldRssFile = $objResultRSS->fields['rss_file'];

                    if ($inputValue != $oldRssLink) {

                        $obj_file = new \File();
                        $obj_file->delFile($this->mediaPath, $this->mediaWebPath, "ext_feeds/".$oldRssFile);

                        //create rss
                        $link = $inputValue;
                        $rss_file = $this->createXML($link);

                        if (substr($inputValue, 0,7) != "http://" && $inputValue != "") {
                            $inputValue = "http://".$inputValue;
                        }

                        if ($rss_file == "error") {
                            $inputValue = "";
                            $rss_file = "";
                        }
                    } else {
                        $inputValue = $oldRssLink;
                        $rss_file = $oldRssLink;
                    }
                }

                //get author id
                if ($inputName == "addedby") {
                    if ($addedby != '') {
                        $inputValue = $addedby;
                    } else {
                        $inputValue = $this->getAuthorID($inputValue);
                    }
                }

                //check pics
                if ($inputName == "logo" ||
                    $inputName == "lokal" ||
                    $inputName == "map" ||
                    $inputName == "spez_field_11" ||
                    $inputName == "spez_field_12" ||
                    $inputName == "spez_field_13" ||
                    $inputName == "spez_field_14" ||
                    $inputName == "spez_field_15" ||
                    $inputName == "spez_field_16" ||
                    $inputName == "spez_field_17" ||
                    $inputName == "spez_field_18" ||
                    $inputName == "spez_field_19" ||
                    $inputName == "spez_field_20") {

                    $fileName = isset($_POST[$inputName]) ? contrexx_input2raw($_POST[$inputName]) : '';
                    if (!empty($fileName) || $_POST['deleteMedia'][$inputName] == 1) {
                        $obj_file = new \File();
                        //thumb
                        if (file_exists($this->mediaPath."thumbs/".$_POST["inputValue"][$inputName])) {
                            $obj_file->delFile($this->mediaPath, $this->mediaWebPath, "thumbs/".$_POST["inputValue"][$inputName]);
                        }

                        //picture
                        if (file_exists($this->mediaPath."images/".$_POST["inputValue"][$inputName]) && $_POST["inputValue"][$inputName] != 'no_picture.gif') {
                            $obj_file->delFile($this->mediaPath, $this->mediaWebPath, "images/".$_POST["inputValue"][$inputName]);
                        }

                        if ($_POST['deleteMedia'][$inputName] != 1) {
                            $imgUploadId = isset($_POST['imgUploaderId'])
                                           ? contrexx_input2raw($_POST['imgUploaderId'])
                                           : '';
                            $inputValue = $this->uploadMedia($fileName, 'images/', $imgUploadId);
                            if ($inputValue == "error") {
                                $inputValue = "";
                            }
                        } else {
                            $inputValue = "";
                        }
                    }
                }

                //check uploads
                $arrSpezialUploadFields = array('attachment', 'spez_field_25', 'spez_field_26', 'spez_field_27', 'spez_field_28', 'spez_field_29');
                if (in_array($inputName, $arrSpezialUploadFields)) {
                    $fileName = isset($_POST[$inputName]) ? contrexx_input2raw($_POST[$inputName]) : '';
                    if (!empty($fileName) || $_POST['deleteMedia'][$inputName] == 1) {
                        $obj_file = new \File();
                        //upload
                        if (file_exists($this->mediaPath."uploads/".$_POST["inputValue"][$inputName])) {
                            $obj_file->delFile($this->mediaPath, $this->mediaWebPath, "uploads/".$_POST["inputValue"][$inputName]);
                        }

                        if ($_POST["deleteMedia"][$inputName] != 1) {
                            $fileUploadId = isset($_POST['fileUploaderId'])
                                           ? contrexx_input2raw($_POST['fileUploaderId'])
                                           : '';
                            $inputValue = $this->uploadMedia($fileName, 'uploads/', $fileUploadId);
                            if ($inputValue == "error") {
                                $inputValue = "";
                            }
                        } else {
                            $inputValue = "";
                        }
                    } else {
                        $inputValue = "";
                    }
                }

                /*
                 * spezial upload fields must be updated only when new file is uploaded or old one is deleted
                 * other input types must be updated unconditionally.
                 */
                if (!in_array($inputName, $arrSpezialUploadFields)) {
                    $query .= contrexx_addslashes($inputName)." ='".contrexx_strip_tags(contrexx_addslashes($inputValue))."', ";
                } else if (in_array($inputName, $arrSpezialUploadFields) && (!empty($_POST[$inputName]) || $_POST['deleteMedia'][$inputName] == 1)) {
                    $query .= contrexx_addslashes($inputName)." ='".contrexx_strip_tags(contrexx_addslashes($inputValue))."', ";
                }
            }

            //get status settings
            $objResult = $objDatabase->Execute("SELECT setvalue FROM ".DBPREFIX."module_directory_settings WHERE setname = 'editFeed_status' LIMIT 1");

            if ($objResult !== false) {
                while(!$objResult->EOF) {
                    $entryStatus = $objResult->fields['setvalue'];
                    $objResult->MoveNext();
                }
            }

            //numbers could be too big for intavl(), use contrexx_addslashes() instead...
            $query .= " premium='".$_POST["premium"]."', status='".intval($entryStatus)."',  validatedate='".mktime("now")."', longitude='".contrexx_addslashes($_REQUEST['inputValue']['lon']).'.'.contrexx_addslashes($_POST['inputValue']['lon_fraction'])."', latitude='".contrexx_addslashes($_REQUEST['inputValue']['lat']).'.'.contrexx_addslashes($_REQUEST['inputValue']['lat_fraction'])."', zoom='".intval($_REQUEST['inputValue']['zoom'])."' WHERE id='".$dirId."'";

            //edit entry
            $objResult = $objDatabase->Execute($query);

            if ($objResult !== false) {

                $objResult = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_directory_rel_dir_cat WHERE dir_id='".$dirId."'");
                $objResult = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_directory_rel_dir_level WHERE dir_id='".$dirId."'");

                foreach($_POST["selectedCat"] as $inputName => $inputValue) {
                    $query = "INSERT INTO ".DBPREFIX."module_directory_rel_dir_cat SET dir_id='".$dirId."', cat_id='".$inputValue."'";
                    $objDatabase->query($query);
                }

                foreach($_POST["selectedLevel"] as $inputName => $inputValue) {
                    $query = "INSERT INTO ".DBPREFIX."module_directory_rel_dir_level SET dir_id='".$dirId."', level_id='".$inputValue."'";
                    $objDatabase->query($query);
                }

                if ($entryStatus == 1) {
// TODO: $id is never set!
                    $this->confirmEntry_step2($id);
                }

                $this->strOkMessage = $_ARRAYLANG['TXT_FEED_SUCCESSFULL_ADDED'];
                $status = $dirId;
                $this->createRSS();
            }

            //update xml
            $this->createRSS();
            return $status;
        }
        return false;
    }


    /**
    * Get/count votes for feeds
    * @access    public
    * @param    string $pageContent
    * @param     string
    */
    function getVotes($id)
    {
        global $objDatabase, $_ARRAYLANG;

        $countVotes = "";
        $averageVotes = "";
        $averageVotesImg = '';

        //count votes
        $objResult = $objDatabase->Execute("SELECT id, vote, count FROM ".DBPREFIX."module_directory_vote WHERE feed_id = '".$id."'");
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $averageVotes = $objResult->fields['vote'];
                $countVotes = $objResult->fields['count'];
                $objResult->MoveNext();
            }
        }

        $averageVotes = $countVotes<1 ? "0" : round($averageVotes/$countVotes, 1);
        $countVotes = $countVotes<1 ? "0" : $countVotes;

        //get stars
        $i = 1;
        for ($x = 1; $x <= 10; $x++) {
            if ($i <= $averageVotes) {
                $averageVotesImg .= '<img src="'.$this->imageWebPath.'/star_on.gif" border="0" alt="" />';
            } else {
                $averageVotesImg .= '<img src="'.$this->imageWebPath.'/star_off.gif" border="0" alt="" />';
            }
            $i++;
        }

        // set variables
        $this->_objTpl->setVariable(array(
            'DIRECTORY_FEED_COUNT_VOTES' => "(".$countVotes." ".$_ARRAYLANG['TXT_DIRECTORY_VOTES']." &Oslash;&nbsp;".$averageVotes.")",
            'DIRECTORY_FEED_AVERAGE_VOTE' => $averageVotesImg,
        ));
    }


    /**
    * get added levels
    * @access   public
    * @param    string    $catId
    * @return    string    $options
    * @global    array
    * @global    ADONewConnection
    */
    function getSearchLevels($levelId)
    {
        global $_CONFIG, $objDatabase;

        //get all categories
        $objResultLevel = $objDatabase->Execute("SELECT id, name, parentid FROM ".DBPREFIX."module_directory_levels ORDER BY displayorder");

        if ($objResultLevel !== false) {
            while(!$objResultLevel->EOF) {
                $this->getLevels['name'][$objResultLevel->fields['id']] = $objResultLevel->fields['name'];
                $this->getLevels['parentid'][$objResultLevel->fields['id']] = $objResultLevel->fields['parentid'];
                $objResultLevel->MoveNext();
            }
        }

        $options = "";
        //make categories dropdown
        foreach($this->getLevels['name'] as $levelKey => $levelName) {
            $checked = "";
            if ($this->getLevels['parentid'][$levelKey] == 0) {
                if ($levelKey == $levelId) {
                    $checked = "selected";
                }
                $options .= "<option value='".$levelKey."' ".$checked.">".$levelName."</option>";

                //get subcategories
                $options .= $this->getSearchSublevels($levelName, $levelKey, $levelId);
            }
        }

        return $options;
    }


    /**
    * get added sublevels
    * @access   public
    * @param    string    $catName
    * @param    string    $parentId
    * @param    string    $catId
    * @return    string    $options
    */
    function getSearchSublevels($levelName, $parentId, $levelId)
    {
        $level = $levelName;
        $subOptions = "";

        //get subcategories
        foreach($this->getLevels['name'] as $levelKey => $levelName) {
            if ($this->getLevels['parentid'][$levelKey] == $parentId) {
                $checked = "";
                if ($levelKey == $levelId) {
                    $checked = "selected";
                }

                $subOptions .= "<option value='".$levelKey."' ".$checked.">".$level." >> ".$levelName."</option>";

                //get more subcategories
                $subOptions .= $this->getSearchSublevels($level." >> ".$levelName, $levelKey, $levelId);
            }
        }
        return $subOptions;
    }


    /**
    * get added levels
    * @access   public
    * @param    string    $catId
    * @return    string    $options
    * @global    array
    * @global    ADONewConnection
    */
    function getLevels($id, $type)
    {
        global $_CONFIG, $objDatabase;

        //get selected levels
        $objResultCat = $objDatabase->Execute("SELECT level_id FROM ".DBPREFIX."module_directory_rel_dir_level WHERE dir_id='".$id."'");
        if ($objResultCat !== false) {
            while(!$objResultCat->EOF) {
                $this->levelIds[] = $objResultCat->fields['level_id'];
                $objResultCat->MoveNext();
            }
        }

        //get all levels
        $objResultCat = $objDatabase->Execute("SELECT id, name, parentid, showcategories FROM ".DBPREFIX."module_directory_levels ORDER BY displayorder");

        if ($objResultCat !== false) {
            while(!$objResultCat->EOF) {
                $this->getLevels['name'][$objResultCat->fields['id']] = $objResultCat->fields['name'];
                $this->getLevels['parentid'][$objResultCat->fields['id']] = $objResultCat->fields['parentid'];
                $this->getLevels['showcategories'][$objResultCat->fields['id']] = $objResultCat->fields['showcategories'];
                $objResultCat->MoveNext();
            }
        }

        $options = "";

        //make levels dropdown
        if (!empty($this->getLevels['name'])) {
            foreach($this->getLevels['name'] as $levelKey => $levelName) {
                if ($this->getLevels['parentid'][$levelKey] == 0) {
                    if ($this->getLevels['showcategories'][$levelKey] == 0 ) {
                        $style = "style='color: #ff0000;'";
                    } else {
                        $style = "";
                    }
                    if ($type == 1) {
// TODO:  $this->levels does not exist!
                        if (!in_array($levelKey, $this->levelIds)) {
                            $options .= "<option value='".$levelKey."' $style>".$levelName."</option>";
                        }
                    } else {
// TODO:  $this->levels does not exist!
                        if (in_array($levelKey, $this->levelIds)) {
                            $options .= "<option value='".$levelKey."' $style>".$levelName."</option>";
                        }
                    }

                    //get sublevels
                    $options .= $this->getSublevels($levelName, $levelKey, $type, '&nbsp;&nbsp;&nbsp;');
                }
            }
        }

        return $options;
    }


    /**
    * get added sublevels
    * @access   public
    * @param    string    $catName
    * @param    string    $parentId
    * @param    string    $catId
    * @return    string    $options
    */
    function getSublevels($levelName, $parentId, $type, $spacer)
    {
        $options = '';
        foreach($this->getLevels['name'] as $levelKey => $levelName) {
            if ($this->getLevels['parentid'][$levelKey] == $parentId) {
                if ($this->getLevels['showcategories'][$levelKey] == 0 ) {
                    $style = "style='color: #ff0000;'";
                } else {
                    $style = "";
                }
                if ($type == 1) {
                    if (!in_array($levelKey, $this->levelIds)) {
                        $options .= "<option value='".$levelKey."' $style>".$spacer.$levelName."</option>";
                    }
                } else {
                    if (in_array($levelKey, $this->levelIds)) {
                        $options .= "<option value='".$levelKey."' $style>".$levelName."</option>";
                    }
                }
                //get more subcategories
                $options .= $this->getSublevels($levelName, $levelKey, $type, $spacer.'&nbsp;&nbsp;&nbsp;');
            }
        }
        return $options;
    }

}

?>
