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


function _media1Update()
{
    global $_ARRAYLANG, $_CORELANG;

    /*    require_once ASCMS_FRAMEWORK_PATH.'/File.class.php';
    $objFile = new File();

    $paths = glob(ASCMS_DOCUMENT_ROOT.'/media/archive*');
    foreach ($paths as $path) {
        $path = "$path/";
        $web_path = preg_replace("#".ASCMS_DOCUMENT_ROOT."/media/#", ASCMS_PATH_OFFSET . '/media/', $path);
        $status = $objFile->delFile($path, $web_path, '.htaccess');

        if ($status == 'error') {
            setUpdateMsg(sprintf($_ARRAYLANG['TXT_SET_WRITE_PERMISSON_TO_DIR_AND_CONTENT'], "<pre>$web_path</pre>", $_CORELANG['TXT_UPDATE_TRY_AGAIN']));
            return false;
        }
        }*/

    try {
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_media_settings',
            array(
                  'name'       => array('type' => 'VARCHAR(50)'),
                  'value'      => array('type' => 'VARCHAR(250)', 'after' => 'name')
                  ),
            array(
                  'name'       => array('fields' => array('name'))
                  ),
            'InnoDB'
        );
        $arrValues = array(
                           array("media1_frontend_changable","off"),
                           array("media2_frontend_changable","off"),
                           array("media3_frontend_changable","off"),
                           array("media4_frontend_changable","off"),
                           array("media1_frontend_managable","off"),
                           array("media2_frontend_managable","off"),
                           array("media3_frontend_managable","off"),
                           array("media4_frontend_managable","off")
                           );

        for($i = 0; $i < count($arrValues); $i++) {
            $rs = \Cx\Lib\UpdateUtil::sql('SELECT 1 FROM '.DBPREFIX.'module_media_settings WHERE name="'.$arrValues[$i][0].'";');
            if($rs->EOF) {
                \Cx\Lib\UpdateUtil::sql('INSERT INTO '.DBPREFIX.'module_media_settings VALUES ("'.$arrValues[$i][0].'","'.$arrValues[$i][1].'")');
            }
        }
    }
    catch (\Cx\Lib\UpdateException $e) {
        // we COULD do something else here..
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }


    $arrContentSites = array(
        'media1', 'media2', 'media3', 'media4',
    );
    // replace source url to image
    foreach ($arrContentSites as $module) {
        try {
            \Cx\Lib\UpdateUtil::migrateContentPage(
                $module,
                '',
                'images/modules/media/_base.gif',
                'core_modules/media/View/Media/_base.gif',
                '3.1.2'
            );
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
    }

    return true;
}
