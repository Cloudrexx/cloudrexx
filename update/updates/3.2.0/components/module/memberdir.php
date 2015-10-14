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


function _memberdirUpdate() {
    global $objDatabase, $_ARRAYLANG, $_CORELANG;

    try {
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX . 'module_memberdir_directories', array(
                'dirid' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'parentdir' => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0'),
                'active' => array('type' => 'SET(\'1\',\'0\')', 'notnull' => true, 'default' => '1'),
                'name' => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => ''),
                'description' => array('type' => 'TEXT'),
                'displaymode' => array('type' => 'SET(\'0\',\'1\',\'2\')', 'notnull' => true, 'default' => '0'),
                'sort' => array('type' => 'INT(11)', 'notnull' => true, 'default' => '1'),
                'pic1' => array('type' => 'SET(\'1\',\'0\')', 'notnull' => true, 'default' => '0'),
                'pic2' => array('type' => 'SET(\'1\',\'0\')', 'notnull' => true, 'default' => '0'),
                'lang_id' => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '1')
            ), array(
                'memberdir_dir' => array('fields' => array('name', 'description'), 'type' => 'FULLTEXT')
            )
        );
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX . 'module_memberdir_name', array(
                'field' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                'dirid' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                'name' => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => ''),
                'active' => array('type' => 'SET(\'0\',\'1\')', 'notnull' => true, 'default' => ''),
                'lang_id' => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '1')
            )
        );
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX . 'module_memberdir_settings', array(
                'setid' => array('type' => 'INT(4)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'setname' => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => ''),
                'setvalue' => array('type' => 'TEXT'),
                'lang_id' => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '1')
            )
        );
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX . 'module_memberdir_values', array(
                'id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'dirid' => array('type' => 'INT(14)', 'notnull' => true, 'default' => '0'),
                'pic1' => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => ''),
                'pic2' => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => ''),
                '0' => array('type' => 'SMALLINT(5)', 'notnull' => true, 'unsigned' => true, 'default' => '0'),
                '1' => array('type' => 'TEXT'),
                '2' => array('type' => 'TEXT'),
                '3' => array('type' => 'TEXT'),
                '4' => array('type' => 'TEXT'),
                '5' => array('type' => 'TEXT'),
                '6' => array('type' => 'TEXT'),
                '7' => array('type' => 'TEXT'),
                '8' => array('type' => 'TEXT'),
                '9' => array('type' => 'TEXT'),
                '10' => array('type' => 'TEXT'),
                '11' => array('type' => 'TEXT'),
                '12' => array('type' => 'TEXT'),
                '13' => array('type' => 'TEXT'),
                '14' => array('type' => 'TEXT'),
                '15' => array('type' => 'TEXT'),
                '16' => array('type' => 'TEXT'),
                '17' => array('type' => 'TEXT'),
                '18' => array('type' => 'TEXT'),
                'lang_id' => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '1')
            )
        );

        $arrSettings = array(
            'default_listing' => array('1', '1'),
            'max_height' => array('400', '1'),
            'max_width' => array('500', '1')
        );

        foreach ($arrSettings as $key => $arrSetting) {
            if (!\Cx\Lib\UpdateUtil::sql("SELECT 1 FROM `" . DBPREFIX . "module_memberdir_settings` WHERE `setname` = '" . $key . "'")->RecordCount()) {
                \Cx\Lib\UpdateUtil::sql("INSERT INTO `" . DBPREFIX . "module_memberdir_settings`
                    SET `setname`    = '" . $key . "',
                        `setvalue`   = '" . $arrSetting[0] . "',
                        `lang_id`    = '" . $arrSetting[1] . "'
                ");
            }
        }
    } catch (\Cx\Lib\UpdateException $e) {
        // we COULD do something else here..
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }


    if (!\Cx\Lib\FileSystem\FileSystem::makeWritable(ASCMS_MEDIA_PATH . '/memberdir')) {
        setUpdateMsg(sprintf($_ARRAYLANG['TXT_SET_WRITE_PERMISSON_TO_DIR_AND_CONTENT'], ASCMS_MEDIA_PATH . '/memberdir/', $_CORELANG['TXT_UPDATE_TRY_AGAIN']), 'msg');
        return false;
    }

    return true;
}
