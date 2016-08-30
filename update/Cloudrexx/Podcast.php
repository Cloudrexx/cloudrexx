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


require_once (dirname(dirname(dirname(__FILE__))) . '/core/Core/init.php');
init('minimal');
echo podcastUpdate();

function podcastUpdate()
{
    try {
        // Update module name
        \Cx\Lib\UpdateUtil::sql("UPDATE `".DBPREFIX."modules` SET `name` = 'Podcast' WHERE `id` = 35");
        // Update navigation url value
        \Cx\Lib\UpdateUtil::sql("UPDATE `".DBPREFIX."backend_areas` SET `uri` = 'index.php?cmd=Podcast' WHERE `area_id` = 93");
        // Insert entry for component
        \Cx\Lib\UpdateUtil::sql("INSERT INTO `".DBPREFIX."component` (`id`, `name`, `type`) VALUES ('35', 'Podcast', 'module')");
        // Update module name for frontend pages
        \Cx\Lib\UpdateUtil::sql("UPDATE `".DBPREFIX."content_page` SET `module` = 'Podcast' WHERE `module` = 'podcast'");
        // Update the thumbnail path from images/podcast into images/Podcast
        \Cx\Lib\UpdateUtil::sql("UPDATE `".DBPREFIX."module_podcast_medium`
                                        SET `thumbnail` = REPLACE(`thumbnail`, 'images/podcast', 'images/Podcast')
                                        WHERE `thumbnail` LIKE ('".ASCMS_PATH_OFFSET."/images/podcast%')");

    } catch (\Cx\Lib\UpdateException $e) {
        return "Error: $e->sql";
    }

    //Update script for moving the folder
    $imgPath   = ASCMS_DOCUMENT_ROOT . '/images';
    $mediaPath = ASCMS_DOCUMENT_ROOT . '/media';

    try {
        if (file_exists($imgPath . '/podcast') && !file_exists($imgPath . '/Podcast')) {
            \Cx\Lib\FileSystem\FileSystem::makeWritable($imgPath . '/podcast');
            if (!\Cx\Lib\FileSystem\FileSystem::move($imgPath . '/podcast', $imgPath . '/Podcast')) {
                return 'Failed to move the folder from '.$imgPath . '/podcast to '.$imgPath . '/Podcast.';
            }
        }
        if (file_exists($mediaPath . '/podcast') && !file_exists($mediaPath . '/Podcast')) {
            \Cx\Lib\FileSystem\FileSystem::makeWritable($mediaPath . '/podcast');
            if (!\Cx\Lib\FileSystem\FileSystem::move($mediaPath . '/podcast', $mediaPath . '/Podcast')) {
                return 'Failed to move the folder from '.$mediaPath . '/podcast to '.$mediaPath . '/Podcast.';
            }
        }
    } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
        return $e->getMessage();
    }
    return 'Podcast Component database updated successfully';
}
