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
 * E-Card
 *
 * Send electronic postcards to your friends
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     2.1.0
 * @since       2.1.0
 * @package     cloudrexx
 * @subpackage  module_ecard
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Modules\Ecard\Controller;

/**
 * E-Card
 *
 * Send electronic postcards to your friends
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     2.1.0
 * @since       2.1.0
 * @package     cloudrexx
 * @subpackage  module_ecard
 * @todo        Edit PHP DocBlocks!
 */
class EcardManager
{
    /**
     * @var    \Cx\Core\Html\Sigma
     */
    public $_objTpl;
    public $_pageTitle;
    public $strErrMessage = '';
    public $strOkMessage = '';

    private $act = '';

    /**
     * PHP5 constructor
     *
     * @global object $objTemplate
     * @global array $_ARRAYLANG
     */
    function __construct()
    {
        global $_ARRAYLANG, $objTemplate;

        $this->_objTpl = new \Cx\Core\Html\Sigma(ASCMS_MODULE_PATH.'/Ecard/View/Template/Backend');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);
    }
    private function setNavigation()
    {
        global $objTemplate, $_ARRAYLANG;

        $objTemplate->setVariable(
            'CONTENT_NAVIGATION',
            '<a href="index.php?cmd=Ecard" class="'.($this->act == '' ? 'active' : '').'">'.$_ARRAYLANG['TXT_MOTIVE_SELECTION'].
            '</a><a href="index.php?cmd=Ecard&amp;act=settings" class="'.($this->act == 'settings' ? 'active' : '').'">'.$_ARRAYLANG['TXT_SETTINGS'].'</a>'
        );
    }


    /**
     * Set the backend page
     * @access public
     * @global object $objTemplate
     * @global array $_ARRAYLANG
     */
    function getPage()
    {
        global $objTemplate;

        \Permission::checkAccess(151, 'static');

        $_GET['act'] = (isset($_GET['act'])) ? $_GET['act'] : '';

        switch ($_GET['act']) {
            case 'settings':
                $this->settings();
                break;
            default:
                $this->setMotives();
        }

        $objTemplate->setVariable(array(
            'CONTENT_OK_MESSAGE' => $this->strOkMessage,
            'CONTENT_STATUS_MESSAGE' => $this->strErrMessage,
            'ADMIN_CONTENT' => $this->_objTpl->get(),
            'CONTENT_TITLE' => $this->_pageTitle,
        ));

        $this->act = $_REQUEST['act'];
        $this->setNavigation();

        return $this->_objTpl->get();
    }


    function setMotives()
    {
        global $objDatabase, $_ARRAYLANG;

        \JS::activate('shadowbox');
        $this->_objTpl->loadTemplateFile('module_ecard_overview.html',true,true);
        $this->_pageTitle = $_ARRAYLANG['TXT_MOTIVE_SELECTION'];
        $mediaBrowser = new \Cx\Core_Modules\MediaBrowser\Model\Entity\MediaBrowser();
        $mediaBrowser->setCallback('mbCallback');
        $mediaBrowser->setOptions(
                                array(
                                'type' => 'button',
                                'views' => 'filebrowser',
                                'id' => 'mediabrowser_button',
                                'style' => 'display: none;'
                                )
                            );
        /* Update progress */
        if (!empty($_POST['saveMotives'])) {
            $i = 0;
            $motiveInputArray = $_POST['motiveInputArray'];
            while ($i < 9) {
                $filepath = $motiveInputArray[$i];
                $filename = basename($filepath);
                $query = "
                    UPDATE ".DBPREFIX."module_ecard_settings
                       SET setting_value='".contrexx_addslashes($filename)."'
                     WHERE setting_name='motive_$i'";
                $objResult = $objDatabase->Execute($query);

                /* Create optimized picture for e-card dispatch */
                if ($filepath != '' && file_exists(\Env::get('cx')->getWebsitePath().$filepath)) {
                    $this->resizeMotive(2, \Env::get('cx')->getWebsitePath().$filepath, ASCMS_ECARD_OPTIMIZED_PATH.'/');
                    $this->resizeMotive(1, \Env::get('cx')->getWebsitePath().$filepath, ASCMS_ECARD_THUMBNAIL_PATH.'/');
                }
                ++$i;
            }
            $this->_objTpl->setVariable(array(
                'CONTENT_OK_MESSAGE' => $this->strOkMessage = $_ARRAYLANG['TXT_DATA_SAVED']
            ));
        }

        $this->_objTpl->setGlobalVariable(array(
            'TXT_SAVE' => $_ARRAYLANG['TXT_SAVE'],
            'TXT_DELETE_MOTIVE' => $_ARRAYLANG['TXT_DELETE_MOTIVE'],
            'TXT_PICTURE' => $_ARRAYLANG['TXT_PICTURE'],
            'TXT_PATH' => $_ARRAYLANG['TXT_PATH'],
            'TXT_BROWSE' => $_ARRAYLANG['TXT_BROWSE'],
            'TXT_CHOOSE' => $_ARRAYLANG['TXT_CHOOSE'],
            'TXT_DELETE' => $_ARRAYLANG['TXT_DELETE'],
            'TXT_ECARD_IMAGES' => $_ARRAYLANG['TXT_ECARD_IMAGES'],
            'MEDIABROWSER_BUTTON' => $mediaBrowser->getXHtml(
                                        $_ARRAYLANG['TXT_CORE_CM_BROWSE']
                                     )
        ));

        /* Display progress */
        $query = "
            SELECT `setting_value`
              FROM ".DBPREFIX."module_ecard_settings
             WHERE setting_name LIKE 'motive_%'
             ORDER BY setting_name ASC";
        $objResult = $objDatabase->Execute($query);
        $i = 0;
        /* Create thumbnails */
        while (!$objResult->EOF) {
            $motiveFilename = $objResult->fields['setting_value'];
            $thumbnail = ASCMS_ECARD_THUMBNAIL_WEB_PATH.'/'."no_picture.gif";
            $sourcePath = '';
            if ($motiveFilename != '') {
                $sourcePath = ASCMS_ECARD_OPTIMIZED_WEB_PATH.'/'.$motiveFilename;
                $thumbnail = ASCMS_ECARD_THUMBNAIL_WEB_PATH.'/'.$motiveFilename;
            }
            /* Initialize DATA placeholder */
            $this->_objTpl->setVariable(array(
                'MOTIVE_PATH' => $sourcePath,
                'MOTIVE_THUMB_PATH' => $thumbnail,
                'MOTIVE_ID' => $i++,
            ));
            $this->_objTpl->parse('motiveBlock');
            $objResult->MoveNext();
        }
        $this->_objTpl->replaceBlock('motiveBlock', '', true);
    }


    function settings()
    {
        global $objDatabase, $_ARRAYLANG;

        $this->_objTpl->loadTemplateFile('module_ecard_settings.html',true,true);
        $this->_pageTitle = $_ARRAYLANG['TXT_SETTINGS'];

        /* Initialize variables */
        $_POST['saveSettings'] = isset($_POST['saveSettings']) ? $_POST['saveSettings'] : '';
        $_POST['deleteEcards'] = isset($_POST['deleteEcards']) ? $_POST['deleteEcards'] : '';
        $maxHeight         = 0;
        $maxWidth         = 0;
        $maxWidthThumb     = 0;
        $maxHeightThumb = 0;
        $validdays         = 0;
        $subject         = '';
        $emailText         = '';
        $maxCharacters     = '';
        $maxLines         = '';

        /* Update progress */
        if ($_POST['saveSettings']) {
            foreach ($_POST['settingsArray'] as $settingName => $settingValue) {
                if ($settingName == 'emailText') {$settingValue = nl2br($settingValue);}
                $query = "
                    UPDATE ".DBPREFIX."module_ecard_settings
                       SET setting_value = '" .contrexx_addslashes($settingValue)."'
                     WHERE setting_name = '" .contrexx_addslashes($settingName)."'";
                $objResult = $objDatabase->Execute($query);
                if ($objResult) {
                    $this->_objTpl->setVariable(array(
                        'CONTENT_OK_MESSAGE' => $this->strOkMessage = $_ARRAYLANG['TXT_DATA_SAVED']
                    ));
                }
            }
        }

        /* Delete progress */
        if ($_POST['deleteEcards']) {
            $unlinkOK = "";
            $unlinkFromDB = "";
            $currentDate = mktime();

            $query = "
                SELECT *
                  FROM ".DBPREFIX."module_ecard_ecards";
            $objResult = $objDatabase->Execute($query);
            while (!$objResult->EOF) {
                if ($objResult->fields['date'] + $objResult->fields['TTL'] < $currentDate) {
                    $unvalidEcardsArray[] = array('date' => $objResult->fields['date'], 'TTL' => $objResult->fields['TTL'], 'code' => $objResult->fields['code']);
                }
                $objResult->MoveNext();
            }

            if (!empty($unvalidEcardsArray)) {
                /* Get the right filextension */
                foreach ($unvalidEcardsArray as $value) {
                    $globArray[] = array('ecardWithPath' => glob(ASCMS_ECARD_SEND_ECARDS_PATH.'/'.$value['code'].".*"), 'code' => $value['code']);
                }

                /* Delete the files */
                foreach ($globArray as $filename) {
                    if (unlink($filename['ecardWithPath'][0])) {
                        $unlinkOK = true;
                    } else {
                        $unlinkOK = false;
                    }

                    /* Delete DB records related to files */
                    if ($unlinkOK) {
                        $query = "
                            DELETE FROM ".DBPREFIX."module_ecard_ecards
                             WHERE code = '".$filename['code']."';";
                        $objResult = $objDatabase->Execute($query);
                        if ($objResult) {
                            $unlinkFromDB = true;
                        } else {
                            $unlinkFromDB = false;
                        }
                    }
                }
            }

            if ($unlinkFromDB && $unlinkOK) {
                $this->_objTpl->setVariable(array(
                    'CONTENT_OK_MESSAGE' => $this->strOkMessage = $_ARRAYLANG['TXT_ECARDS_DELETED']
                ));
            } else {
                $this->_objTpl->setVariable(array(
                    'CONTENT_STATUS_MESSAGE' => $this->strErrMessage = $_ARRAYLANG['TXT_ECARDS_NOT_DELETED']
                ));
            }
        }

        /* Display progress */
        $query = "
            SELECT *
              FROM ".DBPREFIX."module_ecard_settings
             WHERE setting_name NOT LIKE 'motive_%'";
        $objResult = $objDatabase->Execute($query);
        while (!$objResult->EOF) {
            switch ($objResult->fields['setting_name']) {
                case 'validdays':
                    $validdays = $objResult->fields['setting_value'];
                    break;
                case 'maxHeight':
                    $maxHeight = $objResult->fields['setting_value'];
                    break;
                case 'maxWidth':
                    $maxWidth = $objResult->fields['setting_value'];
                    break;
                case 'maxWidthThumb':
                    $maxWidthThumb = $objResult->fields['setting_value'];
                    break;
                case 'maxHeightThumb':
                    $maxHeightThumb = $objResult->fields['setting_value'];
                    break;
                case 'subject':
                    $subject = $objResult->fields['setting_value'];
                    break;
                case 'emailText':
                    $emailText = strip_tags($objResult->fields['setting_value']);
                    break;
                case 'maxCharacters':
                    $maxCharacters = $objResult->fields['setting_value'];
                    break;
                case 'maxLines':
                    $maxLines = $objResult->fields['setting_value'];
                    break;
            }

            /* Initialize DATA placeholder */
            $this->_objTpl->setVariable(array(
                'VALID_DAYS' => htmlentities($validdays, ENT_QUOTES, CONTREXX_CHARSET),
                'MAX_WIDTH' => htmlentities($maxWidth, ENT_QUOTES, CONTREXX_CHARSET),
                'MAX_HEIGHT' => htmlentities($maxHeight, ENT_QUOTES, CONTREXX_CHARSET),
                'MAX_WIDTH_THUMB' => htmlentities($maxWidthThumb, ENT_QUOTES, CONTREXX_CHARSET),
                'MAX_HEIGHT_THUMB' => htmlentities($maxHeightThumb, ENT_QUOTES, CONTREXX_CHARSET),
                'SUBJECT' => htmlentities($subject, ENT_QUOTES, CONTREXX_CHARSET),
                'EMAIL_TEXT' => htmlentities($emailText, ENT_QUOTES, CONTREXX_CHARSET),
                'MAX_CHARACTERS' => htmlentities($maxCharacters, ENT_QUOTES, CONTREXX_CHARSET),
                'MAX_LINES' => htmlentities($maxLines, ENT_QUOTES, CONTREXX_CHARSET)
            ));
            $objResult->MoveNext();
        }

        /* Initialize TEXT placeholder */
        $this->_objTpl->setVariable(array(
            'TXT_SAVE' => $_ARRAYLANG['TXT_SAVE'],
            'TXT_SETTINGS' => $_ARRAYLANG['TXT_SETTINGS'],
            'TXT_VALID_TIME_OF_ECARD' => $_ARRAYLANG['TXT_VALID_TIME_OF_ECARD'],
            'TXT_NOTIFICATION_SUBJECT' => $_ARRAYLANG['TXT_NOTIFICATION_SUBJECT'],
            'TXT_NOTIFICATION_CONTENT' => $_ARRAYLANG['TXT_NOTIFICATION_CONTENT'],
            'TXT_TEXTBOX' => $_ARRAYLANG['TXT_TEXTBOX'],
            'TXT_NUMBER_OF_CHARS_ROW' => $_ARRAYLANG['TXT_NUMBER_OF_CHARS_ROW'],
            'TXT_NUMBER_OF_ROWS_MESSAGE' => $_ARRAYLANG['TXT_NUMBER_OF_ROWS_MESSAGE'],
            'TXT_SPECIFICATIONS_MOTIVE' => $_ARRAYLANG['TXT_SPECIFICATIONS_MOTIVE'],
            'TXT_MAX_WIDTH_OF_MOTIVE' => $_ARRAYLANG['TXT_MAX_WIDTH_OF_MOTIVE'],
            'TXT_MAX_HEIGHT_OF_MOTIVE' => $_ARRAYLANG['TXT_MAX_HEIGHT_OF_MOTIVE'],
            'TXT_SPECIFICATIONS_THUMBNAILS' => $_ARRAYLANG['TXT_SPECIFICATIONS_THUMBNAILS'],
            'TXT_MAX_WIDHT_OF_THUMBNAIL' => $_ARRAYLANG['TXT_MAX_WIDHT_OF_THUMBNAIL'],
            'TXT_MAX_HEIGHT_OF_THUMBNAIL' => $_ARRAYLANG['TXT_MAX_HEIGHT_OF_THUMBNAIL'],
            'TXT_SENT_ECARDS' => $_ARRAYLANG['TXT_SENT_ECARDS'],
            'TXT_DELETE_EXPIRED_ECARDS' => $_ARRAYLANG['TXT_DELETE_EXPIRED_ECARDS'],
            'TXT_CLICK_HERE_DELETE_EXPIRED_ECARDS' => $_ARRAYLANG['TXT_CLICK_HERE_DELETE_EXPIRED_ECARDS'],
            'TXT_PLACEHOLDER' => $_ARRAYLANG['TXT_PLACEHOLDER'],
            'TXT_PLACEHOLDER_OVERVIEW_FRONTEND_BACKEND' => $_ARRAYLANG['TXT_PLACEHOLDER_OVERVIEW_FRONTEND_BACKEND'],
            'TXT_ECARD_SALUTATION_RECIPIENT' => $_ARRAYLANG['TXT_ECARD_SALUTATION_RECIPIENT'],
            'TXT_ECARD_NAME_RECIPIENT' => $_ARRAYLANG['TXT_ECARD_NAME_RECIPIENT'],
            'TXT_ECARD_EMAIL_RECIPIENT' => $_ARRAYLANG['TXT_ECARD_EMAIL_RECIPIENT'],
            'TXT_ECARD_NAME_SENDER' => $_ARRAYLANG['TXT_ECARD_NAME_SENDER'],
            'TXT_ECARD_EMAIL_SENDER' => $_ARRAYLANG['TXT_ECARD_EMAIL_SENDER'],
            'TXT_ECARD_VALID_DAYS' => $_ARRAYLANG['TXT_ECARD_VALID_DAYS'],
            'TXT_ECARD_URL' => $_ARRAYLANG['TXT_ECARD_URL'],
            'TXT_PLACEHOLDER_OVERVIEW_FRONTEND' => $_ARRAYLANG['TXT_PLACEHOLDER_OVERVIEW_FRONTEND'],
            'TXT_ECARD_MESSAGE' => $_ARRAYLANG['TXT_ECARD_MESSAGE'],
            'TXT_ECARD_SETTINGS' => $_ARRAYLANG['TXT_ECARD_SETTINGS'],
        ));
    }


    function resizeMotive($type, $sourcePath, $destinationPath)
    {
        global $objDatabase;

        //$type == 1 => resize methode for creating thumbnails
        //$type == 2 => resize methode for creating optimized motives

        /* Initialize variables */
        $motiveFilename = basename($sourcePath);
        $query = "
            SELECT *
              FROM ".DBPREFIX."module_ecard_settings";
        $objResult = $objDatabase->Execute($query);
        /* Get resize values from settings record */
        while (!$objResult->EOF) {
            switch ($objResult->fields['setting_name']) {
                case 'maxHeight':
                    $maxHeight =  $objResult->fields['setting_value'];
                    break;
                case 'maxWidth':
                    $maxWidth =  $objResult->fields['setting_value'];
                    break;
                case 'maxHeightThumb':
                    $maxHeightThumb =  $objResult->fields['setting_value'];
                    break;
                case 'maxWidthThumb':
                    $maxWidthThumb =  $objResult->fields['setting_value'];
                    break;
            }
            $objResult->MoveNext();
        }
        /* Set dimensions for the thumbnails for frontend AND backend view */
        if ($type == 1) {
            $maxHeight = $maxHeightThumb;
            $maxWidth = $maxWidthThumb;
        }
        /* Get file attributes */
        $size = getimagesize($sourcePath);
        $width_org = $size[0];
        $height_org = $size[1];
        /* Set new height / width */
        if ($width_org > $maxWidth || $height_org > $maxHeight) {
            $width_zoom = $maxWidth / $width_org ;
            $height_zoom =  $maxHeight / $height_org;

            if ($width_zoom < $height_zoom) {
                $width_new = $maxWidth;
                $height_new = intval($height_org*$width_zoom);
            } else {
                $height_new = $maxHeight;
                $width_new = intval($width_org*$height_zoom);
            }
        } else {
            $width_new = $width_org;
            $height_new = $height_org;
        }

        /* Resample */
        $motiveOptimizedFile = imagecreatetruecolor($width_new, $height_new);
        /* Save the new file */
        if ($size[2] == 1) {
            //GIF
            $motiveOptimized = imagecreatefromgif($sourcePath);
            imagecopyresampled($motiveOptimizedFile, $motiveOptimized, 0, 0, 0, 0, $width_new, $height_new, $width_org, $height_org);
            imagegif($motiveOptimizedFile, $destinationPath.$motiveFilename);
        } elseif ($size[2] == 2) {
            //JPG
            $motiveOptimized = imagecreatefromjpeg($sourcePath);
            imagecopyresampled($motiveOptimizedFile, $motiveOptimized, 0, 0, 0, 0, $width_new, $height_new, $width_org, $height_org);
            imagejpeg($motiveOptimizedFile, $destinationPath.$motiveFilename);
        } elseif ($size[2] == 3) {
            //PNG
            $motiveOptimized = imagecreatefrompng($sourcePath);
            imagecopyresampled($motiveOptimizedFile, $motiveOptimized, 0, 0, 0, 0, $width_new, $height_new, $width_org, $height_org);
            imagepng($motiveOptimizedFile, $destinationPath.$motiveFilename);
        }
    }
}
