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
echo shopUpdates();

function shopUpdates() {
    //Update the database changes
    try {
        //update module name
        \Cx\Lib\UpdateUtil::sql("UPDATE `".DBPREFIX."modules` SET `name` = 'Shop' WHERE `id` = 16");
        //update navigation url
        \Cx\Lib\UpdateUtil::sql("UPDATE `".DBPREFIX."backend_areas` SET `uri` = 'index.php?cmd=Shop' WHERE `area_id` = 13");
        //Insert component entry
        \Cx\Lib\UpdateUtil::sql("INSERT INTO `".DBPREFIX."component` (`id`, `name`, `type`) VALUES ('16', 'Shop', 'module')");
        //update module name for frontend pages
        \Cx\Lib\UpdateUtil::sql("UPDATE `".DBPREFIX."content_page` SET `module` = 'Shop' WHERE `module` = 'shop'");
        //update module name for crm core settings
        \Cx\Lib\UpdateUtil::sql("UPDATE `".DBPREFIX."core_setting` SET `section` = 'Shop' WHERE `section` = 'shop'");
        //update module name for email templates
        \Cx\Lib\UpdateUtil::sql("UPDATE `".DBPREFIX."core_mail_template` SET `section` = 'Shop' WHERE `section` = 'shop'");
        \Cx\Lib\UpdateUtil::sql("UPDATE `".DBPREFIX."core_text` SET `section` = 'Shop' WHERE `section` = 'shop'");
    } catch (\Cx\Lib\UpdateException $e) {
        return "Error: $e->sql";
    }

    //Update script for moving the folder
    $shopImgPath   = ASCMS_DOCUMENT_ROOT . '/images';
    $shopMediaPath = ASCMS_DOCUMENT_ROOT . '/media';

    try {
        if (file_exists($shopImgPath . '/shop') && !file_exists($shopImgPath . '/Shop')) {
            \Cx\Lib\FileSystem\FileSystem::makeWritable($shopImgPath . '/shop');
            if (!\Cx\Lib\FileSystem\FileSystem::move($shopImgPath . '/shop', $shopImgPath . '/Shop')) {
                return 'Failed to move the folder from '.$shopImgPath . '/shop to '.$shopImgPath . '/Shop.';
            }
        }
        if (file_exists($shopMediaPath . '/shop') && !file_exists($shopMediaPath . '/Shop')) {
            \Cx\Lib\FileSystem\FileSystem::makeWritable($shopMediaPath . '/shop');
            if (!\Cx\Lib\FileSystem\FileSystem::move($shopMediaPath . '/shop', $shopMediaPath . '/Shop')) {
                return 'Failed to move the folder from '.$shopMediaPath . '/shop to '.$shopMediaPath . '/Shop.';
            }
        }
    } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
        return $e->getMessage();
    }

    return 'Shop updated successfully.';
}
