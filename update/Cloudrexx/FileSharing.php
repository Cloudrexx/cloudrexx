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


require_once dirname(dirname(dirname(__FILE__))) . '/core/Core/init.php';
init('minimal');

echo fileSharingUpdate();

function fileSharingUpdate() {
    try {
        //update module name
        \Cx\Lib\UpdateUtil::sql("UPDATE `" . DBPREFIX . "modules` SET `name` = 'FileSharing' WHERE `id` = 68");
        //update navigation url
        \Cx\Lib\UpdateUtil::sql("UPDATE `" . DBPREFIX . "backend_areas` SET `uri` = 'index.php?cmd=media&amp;archive=FileSharing' WHERE `area_id` = 187");
        //Insert component entry
        \Cx\Lib\UpdateUtil::sql("INSERT INTO `" . DBPREFIX . "component` (`id`, `name`, `type`) VALUES ('68', 'FileSharing', 'module')");
        //update module name for frontend pages
        \Cx\Lib\UpdateUtil::sql("UPDATE `" . DBPREFIX . "content_page` SET `module` = 'FileSharing' WHERE `module` = 'filesharing'");
        //update section
        \Cx\Lib\UpdateUtil::sql("UPDATE `" . DBPREFIX . "core_setting` SET `section` = 'FileSharing',`value` = 'on' WHERE
                `section` = 'filesharing' AND `name` = 'permission' AND `group` = 'config'");
    } catch (\Cx\Lib\UpdateException $e) {
        return "Error: $e->sql";
    }

    $sourcePath = ASCMS_DOCUMENT_ROOT . '/media/filesharing';
    $targetPath = ASCMS_DOCUMENT_ROOT . '/media/FileSharing';
    try {
        if (file_exists($sourcePath) && !file_exists($targetPath)) {
            \Cx\Lib\FileSystem\FileSystem::makeWritable($sourcePath);
            if (!\Cx\Lib\FileSystem\FileSystem::move($sourcePath, $targetPath)) {
                return 'Failed to Moved the files from '.$sourcePath.' to '.$targetPath.'.<br>';
            }
        }
    } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
        return $e->getMessage();
    }

    return 'FileSharing updated successfully';
}

?>
