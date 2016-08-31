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
echo mediaDirUpdate();

function mediaDirUpdate() {
    try {
        //update module name
        \Cx\Lib\UpdateUtil::sql("UPDATE `" . DBPREFIX . "modules` SET `name` = 'MediaDir' WHERE `id` = 60");
        //update navigation url
        \Cx\Lib\UpdateUtil::sql("UPDATE `" . DBPREFIX . "backend_areas` SET `uri` = 'index.php?cmd=MediaDir' WHERE `area_id` = 153");
        //Insert component entry
        \Cx\Lib\UpdateUtil::sql("INSERT INTO `" . DBPREFIX . "component` (`id`, `name`, `type`) VALUES ('60', 'MediaDir', 'module')");
        //update module name for frontend pages
        \Cx\Lib\UpdateUtil::sql("UPDATE `" . DBPREFIX . "content_page` SET `module` = 'MediaDir' WHERE `module` = 'mediadir'");
        //update class name
        \Cx\Lib\UpdateUtil::sql("UPDATE `" . DBPREFIX . "module_mediadir_inputfield_types` SET `name` = 'linkGroup' WHERE `name` = 'link_group'");
        \Cx\Lib\UpdateUtil::sql("UPDATE `" . DBPREFIX . "module_mediadir_inputfield_types` SET `name` = 'googleMap' WHERE `name` = 'google_map'");
        \Cx\Lib\UpdateUtil::sql("UPDATE `" . DBPREFIX . "module_mediadir_inputfield_types` SET `name` = 'addStep' WHERE `name` = 'add_step'");
        \Cx\Lib\UpdateUtil::sql("UPDATE `" . DBPREFIX . "module_mediadir_inputfield_types` SET `name` = 'fieldGroup' WHERE `name` = 'field_group'");
        \Cx\Lib\UpdateUtil::sql("UPDATE `" . DBPREFIX . "module_mediadir_inputfield_types` SET `name` = 'productAttributes' WHERE `name` = 'product_attributes'");
        \Cx\Lib\UpdateUtil::sql("UPDATE `" . DBPREFIX . "module_mediadir_inputfield_types` SET `name` = 'googleWeather' WHERE `name` = 'google_weather'");


        //following queries for changing the path from images/mediadir into images/MediaDir
        \Cx\Lib\UpdateUtil::sql("UPDATE `" . DBPREFIX . "module_mediadir_categories`
                                        SET `picture` = REPLACE(`picture`, 'images/mediadir', 'images/MediaDir')
                                        WHERE `picture` LIKE ('" . ASCMS_PATH_OFFSET . "/images/mediadir%')");
        \Cx\Lib\UpdateUtil::sql("UPDATE `" . DBPREFIX . "module_mediadir_forms`
                                        SET `picture` = REPLACE(`picture`, 'images/mediadir', 'images/MediaDir')
                                        WHERE `picture` LIKE ('" . ASCMS_PATH_OFFSET . "/images/mediadir%')");
        \Cx\Lib\UpdateUtil::sql("UPDATE `" . DBPREFIX . "module_mediadir_levels`
                                        SET `picture` = REPLACE(`picture`, 'images/mediadir', 'images/MediaDir')
                                        WHERE `picture` LIKE ('" . ASCMS_PATH_OFFSET . "/images/mediadir%')");
        \Cx\Lib\UpdateUtil::sql("UPDATE `" . DBPREFIX . "module_mediadir_rel_entry_inputfields`
                                        SET `value` = REPLACE(`value`, 'images/mediadir', 'images/MediaDir')
                                        WHERE `value` LIKE ('" . ASCMS_PATH_OFFSET . "/images/mediadir%')");
        \Cx\Lib\UpdateUtil::sql("UPDATE `" . DBPREFIX . "module_mediadir_rel_entry_inputfields_clean1`
                                        SET `value` = REPLACE(`value`, 'images/mediadir', 'images/MediaDir')
                                        WHERE `value` LIKE ('" . ASCMS_PATH_OFFSET . "/images/mediadir%')");
    } catch (\Cx\Lib\UpdateException $e) {
        return "Error: $e->sql";
    }

    $sourcePath = ASCMS_DOCUMENT_ROOT . '/images/mediadir';
    $targetPath = ASCMS_DOCUMENT_ROOT . '/images/MediaDir';
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
    return 'Media Directory Updated Successfully';
}
