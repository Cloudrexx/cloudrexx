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


function _livecamUpdate()
{
    global $objDatabase, $objUpdate, $_CONFIG;

    try {
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_livecam',
            array(
                'id'                 => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '1', 'primary' => true),
                'currentImagePath'   => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '/webcam/cam1/current.jpg'),
                'archivePath'        => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '/webcam/cam1/archive/'),
                'thumbnailPath'      => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '/webcam/cam1/thumbs/'),
                'maxImageWidth'      => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '400'),
                'thumbMaxSize'       => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '200'),
                'shadowboxActivate'  => array('type' => 'SET(\'1\',\'0\')', 'notnull' => true, 'default' => '1', 'renamefrom' => 'lightboxActivate'),
                'showFrom'           => array('type' => 'INT(14)', 'notnull' => true, 'default' => '0'),
                'showTill'           => array('type' => 'INT(14)', 'notnull' => true, 'default' => '0')
            )
        );

        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_livecam_settings',
            array(
                'setid'      => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'setname'    => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => ''),
                'setvalue'   => array('type' => 'TEXT')
            )
        );
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }

    $query = "SELECT 1 FROM `".DBPREFIX."module_livecam_settings` WHERE `setname` = 'amount_of_cams'";
    $objResult = $objDatabase->SelectLimit($query, 1);
    if ($objResult !== false) {
        if ($objResult->RecordCount() == 0) {
            $query = "INSERT INTO `".DBPREFIX."module_livecam_settings` (`setname`, `setvalue`) VALUES ('amount_of_cams', '1')";
            if ($objDatabase->Execute($query) === false) {
                return _databaseError($query, $objDatabase->ErrorMsg());
            }
        }
    } else {
        return _databaseError($query, $objDatabase->ErrorMsg());
    }




    /************************************************
    * BUGFIX:   Migrate settings                    *
    * ADDED:    2.1.2                               *
    ************************************************/
    if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '2.0.0')) {
        $arrFormerSettings = array(
            'currentImageUrl'   => '',
            'archivePath'       => '',
            'thumbnailPath'     => ''
        );

        $query = "SELECT 1 FROM `".DBPREFIX."module_livecam` WHERE `id` = 1";
        $objResult = $objDatabase->SelectLimit($query, 1);
        if ($objResult !== false) {
            if ($objResult->RecordCount() == 0) {
                $query = "SELECT `setname`, `setvalue` FROM `".DBPREFIX."module_livecam_settings` WHERE `setname` IN ('".implode("','", array_keys($arrFormerSettings))."')";
                $objResult = $objDatabase->Execute($query);
                if ($objResult !== false) {
                    while (!$objResult->EOF) {
                        $arrFormerSettings[$objResult->fields['setname']] = $objResult->fields['setvalue'];
                        $objResult->MoveNext();
                    }

                    $query = "INSERT INTO `".DBPREFIX."module_livecam` (`id`, `currentImagePath`, `archivePath`, `thumbnailPath`, `maxImageWidth`, `thumbMaxSize`, `shadowboxActivate`) VALUES
                            ('1', '".addslashes($arrFormerSettings['currentImageUrl'])."', '".addslashes($arrFormerSettings['archivePath'])."', '".addslashes($arrFormerSettings['thumbnailPath'])."', '400', '120', '0')";
                    if ($objDatabase->Execute($query) === false) {
                        return _databaseError($query, $objDatabase->ErrorMsg());
                    }
                } else {
                    return _databaseError($query, $objDatabase->ErrorMsg());
                }
            }
        } else {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }

        foreach (array_keys($arrFormerSettings) as $setting) {
            $query = "DELETE FROM `".DBPREFIX."module_livecam_settings` WHERE `setname` = '".$setting."'";
            if ($objDatabase->Execute($query) === false) {
                return _databaseError($query, $objDatabase->ErrorMsg());
            }
        }
    }

    $defaultFrom = mktime(0, 0);
    $defaultTill = mktime(23, 59);
    //set new default settings
    $query = "UPDATE `".DBPREFIX."module_livecam` SET `showFrom`=$defaultFrom, `showTill`=$defaultTill WHERE `showFrom` = '0'";
    if ($objDatabase->Execute($query) === false) {
        return _databaseError($query, $objDatabase->ErrorMsg());
    }




    /************************************************
    * BUGFIX:   Update content page                 *
    * ADDED:    2.1.3                               *
    ************************************************/
    // both spaces in the search and replace pattern are required in that case
    try {
        \Cx\Lib\UpdateUtil::migrateContentPage('livecam', null, ' {LIVECAM_IMAGE_SHADOWBOX}', ' rel="{LIVECAM_IMAGE_SHADOWBOX}"', '2.1.3');
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }

    return true;
}
