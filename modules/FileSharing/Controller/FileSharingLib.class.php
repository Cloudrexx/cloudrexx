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
 * FileSharing Lib
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_filesharing
 */
namespace Cx\Modules\FileSharing\Controller;
/**
 * FileSharingLib
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_filesharing
 */
abstract class FileSharingLib
{
    /**
     * Init the uploader which is directly included in the webpage
     *
     * @return integer the uploader id
     */
    protected function initUploader()
    {
        \JS::activate('cx'); // the uploader needs the framework

        $uploader = new \Cx\Core_Modules\Uploader\Model\Entity\Uploader(); //create an uploader
        $uploadId = $uploader->getId();
        $uploader->setCallback('fileSharingUploader');
        $uploader->setOptions(array(
            'id'    => 'fileSharing_'.$uploadId,
            'style' => 'display:none;'
        ));

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $session = $cx->getComponent('Session')->getSession();

        $folderWidget   = new \Cx\Core_Modules\MediaBrowser\Model\Entity\FolderWidget($session->getTempPath() . '/' . $uploadId, true);
        $folderWidgetId = $folderWidget->getId();
        $extendedFileInputCode = <<<CODE
<script type="text/javascript">
    cx.ready(function() {
            var field = jQuery('#contactForm #file_upload');
            //called if user clicks on the field
            var inputClicked = function() {
                jQuery('#fileSharing_$uploadId').trigger('click');
                return false;
            };

            jQuery('#fileSharing_$uploadId').hide();
            field.bind('click', inputClicked).removeAttr('disabled');

            jQuery('a.toggle').click(function() {
                jQuery('div.toggle').toggle();
                return false;
            });
    });

    //uploader javascript callback function
    function fileSharingUploader(callback) {
            angular.element('#mediaBrowserfolderWidget_$folderWidgetId').scope().refreshBrowser();
    }
</script>
CODE;

        $this->objTemplate->setVariable(array(
            'UPLOADER_CODE'      => $uploader->getXHtml(),
            'FILE_INPUT_CODE'    => $extendedFileInputCode,
            'FOLDER_WIDGET_CODE' => $folderWidget->getXHtml(),
        ));

        return $uploadId; // return the upload id
    }

    /**
     * @param integer $uploadId the upload id of the active upload
     * @return array
     */
    public static function getTemporaryFilePaths($uploadId)
    {
        $cx  = \Cx\Core\Core\Controller\Cx::instanciate();
        $session = $cx->getComponent('Session')->getSession();

        return array(
            $session->getTempPath() . '/',
            $session->getWebTempPath() . '/',
            $uploadId,
        );
    }

    /**
     * create check code
     *
     * @static
     * @param string $hash the hash of the file
     * @return string the check code
     */
    public static function createCheck($hash)
    {
        return md5(substr($hash, 0, 5));
    }

    /**
     * create the hash code
     *
     * @static
     * @return string the hash code
     */
    public static function createHash()
    {
        $hash = '';
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        for ($i = 0; $i < 10; $i++) {
            $hash .= $chars{rand(0, 62)};
        }
        return $hash;
    }

    /**
     * @static
     * @param integer $fileId
     * @return string the download link
     */
    public static function getDownloadLink($fileId)
    {
        global $objDatabase;
        $objResult = $objDatabase->SelectLimit("SELECT `cmd`, `hash` FROM " . DBPREFIX . "module_filesharing WHERE `id` = ?", 1, 0, array($fileId));

        if ($objResult !== false) {
            $params = array(
                'hash' => $objResult->fields['hash'],
            );
            try {
                $objUrl = \Cx\Core\Routing\Url::fromModuleAndCmd('FileSharing', $objResult->fields['cmd'], FRONTEND_LANG_ID, $params, '', false);
            } catch (\Cx\Core\Routing\UrlException $e) {
                $objUrl = \Cx\Core\Routing\Url::fromModuleAndCmd('FileSharing', '', FRONTEND_LANG_ID, $params);
            }
            return $objUrl->toString();
        } else {
            return false;
        }
    }

    /**
     * @static
     * @param integer $fileId
     * @return string the download link
     */
    public static function getDeleteLink($fileId)
    {
        global $objDatabase;
        $objResult = $objDatabase->SelectLimit("SELECT `cmd`, `hash`, `check` FROM " . DBPREFIX . "module_filesharing WHERE `id` = " . intval($fileId), 1, 0);


        if ($objResult !== false) {
            $params = array(
                'hash' => $objResult->fields['hash'],
                'check' => $objResult->fields['check'],
            );
            try {
                $objUrl = \Cx\Core\Routing\Url::fromModuleAndCmd('FileSharing', $objResult->fields['cmd'], FRONTEND_LANG_ID, $params, '', false);
            } catch (\Cx\Core\Routing\UrlException $e) {
                $objUrl = \Cx\Core\Routing\Url::fromModuleAndCmd('FileSharing', '', FRONTEND_LANG_ID, $params);
            }
            return $objUrl->toString();
        } else {
            return false;
        }
    }

    /**
     * @static
     * @param integer $fileId file id
     * @return bool is shared or not
     */
    public static function isShared($fileId = null, $fileSource = null)
    {
        global $objDatabase;
        $fileSource = str_replace(\Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteOffsetPath(), '', $fileSource);
        if ($fileSource != NULL) {
            $objResult = $objDatabase->SelectLimit("SELECT `id` FROM " . DBPREFIX . "module_filesharing WHERE `source` = '" . contrexx_raw2db($fileSource) . "'", 1, -1);
            if ($objResult !== false && $objResult->RecordCount() > 0) {
                $fileId = $objResult->fields["id"];
            }
        }
        return self::getDownloadLink($fileId) && self::getDeleteLink($fileId) && $fileId;
    }

    /**
     * clean up the database and shared files
     * deletes expired files and none existing files
     *
     * @static
     */
    static public function cleanUp()
    {
        global $objDatabase;

        $arrToDelete = array();

        // get all files from database
        $objFiles = $objDatabase->Execute("SELECT `id`, `source`, `expiration_date` FROM " . DBPREFIX . "module_filesharing");
        if ($objFiles !== false) {
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            while (!$objFiles->EOF) {
                // if the file is expired or does not exist
                if (($objFiles->fields["expiration_date"] < date('Y-m-d H:i:s')
                        && $objFiles->fields["expiration_date"] != NULL)
                        || !file_exists($cx->getWebsitePath() . $cx->getWebsiteOffsetPath() . $objFiles->fields["source"])
                ) {
                    $fileExists = file_exists($cx->getWebsitePath() . $cx->getWebsiteOffsetPath() . $objFiles->fields["source"]);
                    // if the file is only expired delete the file from directory
                    if ($fileExists) {
                        \Cx\Lib\FileSystem\FileSystem::delete_file($cx->getWebsitePath() . $cx->getWebsiteOffsetPath() . $objFiles->fields["source"]);
                    }
                    $arrToDelete[] = $objFiles->fields["id"];
                }
                $objFiles->moveNext();
            }
        }
        // delete all expired or not existing files
        if(!empty($arrToDelete)) {
            $objDatabase->Execute("DELETE FROM " . DBPREFIX . "module_filesharing WHERE `id` IN (" . implode(',', $arrToDelete) . ")");
        }
    }

    /**
     * send a mail to the email with the message
     *
     * @static
     * @param integer $uploadId the upload id
     * @param string $subject the subject of the mail for the recipient
     * @param string $email the recipient's mail address
     * @param null|string $message the message for the recipient
     */
    static public function sendMail($uploadId, $subject, $emails, $message = null)
    {
        global $objDatabase, $_CONFIG;

        /**
         * get all file ids from the last upload
         */
        $objResult = $objDatabase->Execute("SELECT `id` FROM " . DBPREFIX . "module_filesharing WHERE `upload_id` = '" . intval($uploadId) . "'");
        if ($objResult !== false && $objResult->RecordCount() > 0) {
            while (!$objResult->EOF) {
            $files[] = $objResult->fields["id"];
                $objResult->MoveNext();
            }
        }

        if (!is_int($uploadId) && empty($files)) {
            $files[] = $uploadId;
        }

        /**
         * init mail data. Mail template, Mailsubject and PhpMailer
         */
        $objMail = $objDatabase->SelectLimit("SELECT `subject`, `content` FROM " . DBPREFIX . "module_filesharing_mail_template WHERE `lang_id` = " . FRONTEND_LANG_ID, 1, -1);
        $content = str_replace(array(']]', '[['), array('}', '{'), $objMail->fields["content"]);

        if (empty($subject))
            $subject = $objMail->fields["subject"];

        $objMail = new \Cx\Core\MailTemplate\Model\Entity\Mail();

        /**
         * Load mail template and parse it
         */
        $objTemplate = new \Cx\Core\Html\Sigma('.');
        $objTemplate->setErrorHandling(PEAR_ERROR_DIE);
        $objTemplate->setTemplate($content);

        $objTemplate->setVariable(array(
            "DOMAIN" => $_CONFIG["domainUrl"],
            'MESSAGE' => $message,
        ));

        if ($objTemplate->blockExists('filesharing_file')) {
            foreach ($files as $file) {
                $objTemplate->setVariable(array(
                    'FILE_DOWNLOAD' => self::getDownloadLink($file),
                ));
                $objTemplate->parse('filesharing_file');
            }
        }

        $objMail->SetFrom($_CONFIG['coreAdminEmail'], $_CONFIG['coreGlobalPageTitle']);

        $objMail->Subject = $subject;
        $objMail->Body = $objTemplate->get();
        foreach($emails as $email){
            $objMail->AddAddress($email);
            $objMail->Send();
            $objMail->ClearAddresses();
        }

    }
}
