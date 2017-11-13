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

function _directoryUpdate() {
    global $objDatabase, $_ARRAYLANG;

    /// 2.0

    $arrColumns = $objDatabase->MetaColumns(DBPREFIX.'module_directory_dir');
    if ($arrColumns === false) {
        setUpdateMsg(sprintf($_ARRAYLANG['TXT_UNABLE_GETTING_DATABASE_TABLE_STRUCTURE'], DBPREFIX.'module_directory_dir'));
        return false;
    }

    $arrNewCols = array(
                'LONGITUDE' => array(
                        'type'      => 'DECIMAL( 18, 15 )',
                        'default'   => '0',
                        'after'     => 'premium',
                    ),
                'LATITUDE'  => array(
                        'type'      => 'DECIMAL( 18, 15 )',
                        'default'   => '0',
                        'after'     => 'longitude',
                    ),
                'ZOOM'      => array(
                        'type'      => 'DECIMAL( 18, 15 )',
                        'default'   => '1',
                        'after'     => 'latitude',
                    ),
                'COUNTRY'   => array(
                        'type'      => 'VARCHAR( 255 )',
                        'default'   => '',
                        'after'     => 'city',
                ));
    foreach ($arrNewCols as $col => $arrAttr) {
        if (!isset($arrColumns[$col])) {
            $query = "ALTER TABLE `".DBPREFIX."module_directory_dir` ADD `".strtolower($col)."` ".$arrAttr['type']." NOT NULL DEFAULT '".$arrAttr['default']."' AFTER `".$arrAttr['after']."`";

            if ($objDatabase->Execute($query) === false) {
                return _databaseError($query, $objDatabase->ErrorMsg());
            }
        }
    }

    $inputColumns = '(`id`, `typ`, `name`, `title`, `active`, `active_backend`, `is_required`, `read_only`, `sort`, `exp_search`, `is_search`)';

    $arrInputs = array(
        69 => "INSERT INTO `".DBPREFIX."module_directory_inputfields` ".$inputColumns." VALUES (69, 13, 'googlemap', 'TXT_DIR_F_GOOGLEMAP', 1, 1, 0, 0, 6, 0, 0)",
        70 => "INSERT INTO `".DBPREFIX."module_directory_inputfields` ".$inputColumns." VALUES (70, 3, 'country', 'TXT_DIR_F_COUNTRY', 1, 1, 1, 0, 1, 0, 0)",
    );

    foreach ($arrInputs as $id => $queryInputs) {
        $query = "SELECT id FROM ".DBPREFIX."module_directory_inputfields WHERE id=".$id;
        $objCheck = $objDatabase->SelectLimit($query, 1);
        if ($objCheck !== false) {
            if ($objCheck->RecordCount() == 0) {
                if ($objDatabase->Execute($queryInputs) === false) {
                    return _databaseError($queryInputs, $objDatabase->ErrorMsg());
                }
            }
        } else {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
    }

    $query = "SELECT setid FROM ".DBPREFIX."module_directory_settings WHERE setname='country'";
    $objCheck = $objDatabase->SelectLimit($query, 1);
    if ($objCheck !== false) {
        if ($objCheck->RecordCount() == 0) {
            $query =    "INSERT INTO `".DBPREFIX."module_directory_settings` ( `setid` , `setname` , `setvalue` ,  `settyp` )
                        VALUES (NULL, 'country', ',Schweiz,Deutschland,Österreich', 0)";
            if ($objDatabase->Execute($query) === false) {
                return _databaseError($query, $objDatabase->ErrorMsg());
            }
        }
    } else {
        return _databaseError($query, $objDatabase->ErrorMsg());
    }

    $query = "ALTER TABLE `".DBPREFIX."module_directory_dir` CHANGE `spez_field_21` `spez_field_21` VARCHAR( 255 ) NOT NULL DEFAULT ''";
    if ($objDatabase->Execute($query) === false) {
        return _databaseError($query, $objDatabase->ErrorMsg());
    }

    $query = "ALTER TABLE `".DBPREFIX."module_directory_dir` CHANGE `spez_field_22` `spez_field_22` VARCHAR( 255 ) NOT NULL DEFAULT ''";
    if ($objDatabase->Execute($query) === false) {
        return _databaseError($query, $objDatabase->ErrorMsg());
    }

    $query = "SELECT setid FROM ".DBPREFIX."module_directory_settings WHERE setname='pagingLimit'";
    $objCheck = $objDatabase->SelectLimit($query, 1);
    if ($objCheck !== false) {
        if ($objCheck->RecordCount() == 0) {
            $query =    "INSERT INTO `".DBPREFIX."module_directory_settings` ( `setid` , `setname` , `setvalue` ,  `settyp` )
                        VALUES (NULL, 'pagingLimit', '20', '1')";
            if ($objDatabase->Execute($query) === false) {
                return _databaseError($query, $objDatabase->ErrorMsg());
            }
        }
    } else {
        return _databaseError($query, $objDatabase->ErrorMsg());
    }

    $query = "SELECT setid FROM ".DBPREFIX."module_directory_settings WHERE setname='googlemap_start_location'";
    $objCheck = $objDatabase->SelectLimit($query, 1);
    if ($objCheck !== false) {
        if ($objCheck->RecordCount() == 0) {
            $query =    "INSERT INTO `".DBPREFIX."module_directory_settings` ( `setid` , `setname` , `setvalue` ,  `settyp` )
                        VALUES (NULL, 'googlemap_start_location', '46:8:1', '1')";
            if ($objDatabase->Execute($query) === false) {
                return _databaseError($query, $objDatabase->ErrorMsg());
            }
        }
    } else {
        return _databaseError($query, $objDatabase->ErrorMsg());
    }


    /// 2.1

    $query = "SELECT setid FROM ".DBPREFIX."module_directory_settings WHERE setname='youtubeWidth'";
    $objCheck = $objDatabase->SelectLimit($query, 1);
    if ($objCheck !== false) {
        if ($objCheck->RecordCount() == 0) {
            $query =    "INSERT INTO `".DBPREFIX."module_directory_settings` ( `setid` , `setname` , `setvalue` ,  `settyp` )
                         VALUES (NULL , 'youtubeWidth', '400', '1')";
            if ($objDatabase->Execute($query) === false) {
                return _databaseError($query, $objDatabase->ErrorMsg());
            }
        }
    } else {
        return _databaseError($query, $objDatabase->ErrorMsg());
    }

    $query = "SELECT setid FROM ".DBPREFIX."module_directory_settings WHERE setname='youtubeHeight'";
    $objCheck = $objDatabase->SelectLimit($query, 1);
    if ($objCheck !== false) {
        if ($objCheck->RecordCount() == 0) {
            $query =    "INSERT INTO `".DBPREFIX."module_directory_settings` ( `setid` , `setname` , `setvalue` ,  `settyp` )
                         VALUES (NULL , 'youtubeHeight', '300', '1')";
            if ($objDatabase->Execute($query) === false) {
                return _databaseError($query, $objDatabase->ErrorMsg());
            }
        }
    } else {
        return _databaseError($query, $objDatabase->ErrorMsg());
    }

    $query = "SELECT id FROM ".DBPREFIX."module_directory_inputfields WHERE name='youtube'";
    $objCheck = $objDatabase->SelectLimit($query, 1);
    if ($objCheck !== false) {
        if ($objCheck->RecordCount() == 0) {
            $query =    "INSERT INTO `".DBPREFIX."module_directory_inputfields` (`id` ,`typ` ,`name` ,`title` ,`active` ,`active_backend` ,`is_required` ,`read_only` ,`sort` ,`exp_search` ,`is_search`)
                         VALUES (NULL , '1', 'youtube', 'TXT_DIRECTORY_YOUTUBE', '0', '0', '0', '0', '0', '0', '0')";
            if ($objDatabase->Execute($query) === false) {
                return _databaseError($query, $objDatabase->ErrorMsg());
            }
        }
    } else {
        return _databaseError($query, $objDatabase->ErrorMsg());
    }

    $arrColumns = $objDatabase->MetaColumns(DBPREFIX.'module_directory_dir');
    if ($arrColumns === false) {
        setUpdateMsg(sprintf($_ARRAYLANG['TXT_UNABLE_GETTING_DATABASE_TABLE_STRUCTURE'], DBPREFIX.'module_directory_dir'));
        return false;
    }

    if (!array_key_exists("YOUTUBE", $arrColumns)) {
        $query = "ALTER TABLE `".DBPREFIX."module_directory_dir` ADD `youtube` MEDIUMTEXT NOT NULL;";
        if ($objDatabase->Execute($query) === false) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
    }

    $query   = "ALTER TABLE `".DBPREFIX."module_directory_dir`
                CHANGE `logo` `logo` VARCHAR(50) NULL,
                CHANGE `map` `map` VARCHAR(255) NULL,
                CHANGE `lokal` `lokal` VARCHAR(255) NULL,
                CHANGE `spez_field_11` `spez_field_11` VARCHAR(255) NULL,
                CHANGE `spez_field_12` `spez_field_12` VARCHAR(255) NULL,
                CHANGE `spez_field_13` `spez_field_13` VARCHAR(255) NULL,
                CHANGE `spez_field_14` `spez_field_14` VARCHAR(255) NULL,
                CHANGE `spez_field_15` `spez_field_15` VARCHAR(255) NULL,
                CHANGE `spez_field_16` `spez_field_16` VARCHAR(255) NULL,
                CHANGE `spez_field_17` `spez_field_17` VARCHAR(255) NULL,
                CHANGE `spez_field_18` `spez_field_18` VARCHAR(255) NULL,
                CHANGE `spez_field_19` `spez_field_19` VARCHAR(255) NULL,
                CHANGE `spez_field_20` `spez_field_20` VARCHAR(255) NULL;";
    if ($objDatabase->Execute($query) === false) {
        return _databaseError($query, $objDatabase->ErrorMsg());
    }


    //delete obsolete table  contrexx_module_directory_access
    try {
        \Cx\Lib\UpdateUtil::drop_table(DBPREFIX.'module_directory_access');
    } catch (\Cx\Lib\UpdateException $e) {
        DBG::trace();
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }

    /********************************
     * EXTENSION:   Fulltext key    *
     * ADDED:       Contrexx v3.0.0 *
     ********************************/
    try {
        $objResult = \Cx\Lib\UpdateUtil::sql('SHOW KEYS FROM `'.DBPREFIX.'module_directory_categories` WHERE  `Key_name` = "directoryindex" and (`Column_name`= "name" OR `Column_name` = "description")');
        if ($objResult && ($objResult->RecordCount() == 0)) {
            \Cx\Lib\UpdateUtil::sql('ALTER TABLE `'.DBPREFIX.'module_directory_categories` ADD FULLTEXT KEY `directoryindex` (`name`, `description`)');
        }
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }



    /**********************************
     * EXTENSION:   Content Migration *
     * ADDED:       Contrexx v3.0.0   *
     **********************************/
    try {
        // migrate content page to version 3.0.1
        $search = array(
        '/(.*)/ms',
        );
        $callback = function($matches) {
            $content = $matches[1];
            if (empty($content)) {
                return $content;
            }

            // add missing placeholder {DIRECTORY_GOOGLEMAP_JAVASCRIPT_BLOCK}
            if (strpos($content, '{DIRECTORY_GOOGLEMAP_JAVASCRIPT_BLOCK}') === false) {
                $content .= "\n{DIRECTORY_GOOGLEMAP_JAVASCRIPT_BLOCK}";
            }

            // move placeholder {DIRECTORY_JAVASCRIPT} to the end of the content page
            $content = str_replace('{DIRECTORY_JAVASCRIPT}', '', $content);
            $content .= "\n{DIRECTORY_JAVASCRIPT}";

            return $content;
        };

        \Cx\Lib\UpdateUtil::migrateContentPageUsingRegexCallback(array('module' => 'directory'), $search, $callback, array('content'), '3.0.1');
    }
    catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }


    return true;
}
