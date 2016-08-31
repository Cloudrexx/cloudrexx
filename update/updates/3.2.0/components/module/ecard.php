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


function _ecardUpdate() {
    global $objDatabase, $_ARRAYLANG, $_CORELANG;
    try {
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX . 'module_ecard_ecards', array(
                'code' => array('type' => 'VARCHAR(35)', 'notnull' => true, 'default' => '', 'primary' => true),
                'date' => array('type' => 'INT(10)', 'notnull' => true, 'default' => 0, 'unsigned' => true),
                'TTL' => array('type' => 'INT(10)', 'notnull' => true, 'default' => 0, 'unsigned' => true),
                'salutation' => array('type' => 'VARCHAR(100)', 'notnull' => true, 'default' => ''),
                'senderName' => array('type' => 'VARCHAR(100)', 'notnull' => true, 'default' => ''),
                'senderEmail' => array('type' => 'VARCHAR(100)', 'notnull' => true, 'default' => ''),
                'recipientName' => array('type' => 'VARCHAR(100)', 'notnull' => true, 'default' => ''),
                'recipientEmail' => array('type' => 'VARCHAR(100)', 'notnull' => true, 'default' => ''),
                'message' => array('type' => 'TEXT', 'notnull' => true),
            )
        );
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX . 'module_ecard_settings', array(
                'setting_name' => array('type' => 'VARCHAR(100)', 'notnull' => true, 'default' => '', 'primary' => true),
                'setting_value' => array('type' => 'TEXT', 'notnull' => true, 'default' => 0)
            )
        );
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }

    $ins_tpl = "
        INSERT INTO " . DBPREFIX . "module_ecard_settings (setting_name, setting_value)
        VALUES ('%s', '%s')
        ON DUPLICATE KEY UPDATE `setting_name` = `setting_name`
    ";
    $insert_values = array(
        array('maxCharacters', '100'),
        array('maxLines', '50'),
        array('motive_0', 'Bild_001.jpg'),
        array('motive_1', 'Bild_002.jpg'),
        array('motive_2', ''),
        array('motive_3', ''),
        array('motive_4', ''),
        array('motive_5', ''),
        array('motive_6', ''),
        array('motive_7', ''),
        array('motive_8', ''),
        array('maxHeight', '300'),
        array('validdays', '30'),
        array('maxWidth', '300'),
        array('maxHeightThumb', '80'),
        array('maxWidthThumb', '80'),
        array('subject', 'Sie haben eine E-Card erhalten!'),
        array('emailText', "[[ECARD_SENDER_NAME]] hat Ihnen eine E-Card geschickt.<br />\n Sie können diese während den nächsten [[ECARD_VALID_DAYS]] Tagen unter [[ECARD_URL]] abrufen.")
    );

    foreach ($insert_values as $setting) {
        $query = sprintf($ins_tpl, addslashes($setting[0]), addslashes($setting[1]));
        if (!$objDatabase->Execute($query)) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
    }

    /*     * **********************************************
     * BUGFIX:    Set write access to the image dir   *
     * ********************************************** */
    $arrImagePaths = array(
        array(ASCMS_DOCUMENT_ROOT . '/images/modules/ecard', ASCMS_PATH_OFFSET . '/images/modules/ecard'),
        array(ASCMS_ECARD_OPTIMIZED_PATH, ASCMS_ECARD_OPTIMIZED_WEB_PATH),
        array(ASCMS_ECARD_SEND_ECARDS_PATH, ASCMS_ECARD_SEND_ECARDS_WEB_PATH),
        array(ASCMS_ECARD_THUMBNAIL_PATH, ASCMS_ECARD_THUMBNAIL_WEB_PATH)
    );


    foreach ($arrImagePaths as $arrImagePath) {
        if (\Cx\Lib\FileSystem\FileSystem::makeWritable($arrImagePath[0])) {
            if ($mediaDir = @opendir($arrImagePath[0])) {
                while ($file = readdir($mediaDir)) {
                    if ($file != '.' && $file != '..') {
                        if (!\Cx\Lib\FileSystem\FileSystem::makeWritable($arrImagePath[0] . '/' . $file)) {
                            setUpdateMsg(sprintf($_ARRAYLANG['TXT_SET_WRITE_PERMISSON_TO_FILE'], $arrImagePath[0] . '/' . $file, $_CORELANG['TXT_UPDATE_TRY_AGAIN']), 'msg');
                            return false;
                        }
                    }
                }
            } else {
                setUpdateMsg(sprintf($_ARRAYLANG['TXT_SET_WRITE_PERMISSON_TO_DIR_AND_CONTENT'], $arrImagePath[0] . '/', $_CORELANG['TXT_UPDATE_TRY_AGAIN']), 'msg');
                return false;
            }
        } else {
            setUpdateMsg(sprintf($_ARRAYLANG['TXT_SET_WRITE_PERMISSON_TO_DIR_AND_CONTENT'], $arrImagePath[0] . '/', $_CORELANG['TXT_UPDATE_TRY_AGAIN']), 'msg');
            return false;
        }
    }

    return true;
}
