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


function _podcastUpdate() {
    global $objDatabase, $_ARRAYLANG, $objUpdate, $_CONFIG;

    if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.0.0')) {
        //move podcast images directory
        $path = ASCMS_DOCUMENT_ROOT . '/images';
        $oldImagesPath = '/content/podcast';
        $newImagesPath = '/podcast';

        if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '1.2.1')) {
            if (   !file_exists($path . $newImagesPath)
                && file_exists($path . $oldImagesPath)
            ) {
                \Cx\Lib\FileSystem\FileSystem::makeWritable($path . $oldImagesPath);
                if (!\Cx\Lib\FileSystem\FileSystem::copy_folder($path . $oldImagesPath, $path . $newImagesPath)) {
                    setUpdateMsg(sprintf($_ARRAYLANG['TXT_UNABLE_TO_MOVE_DIRECTORY'], $path . $oldImagesPath, $path . $newImagesPath));
                    return false;
                }
            }
            \Cx\Lib\FileSystem\FileSystem::makeWritable($path . $newImagesPath);
            \Cx\Lib\FileSystem\FileSystem::makeWritable($path . $newImagesPath . '/youtube_thumbnails');

            //change thumbnail paths
            $query = "UPDATE `" . DBPREFIX . "module_podcast_medium` SET `thumbnail` = REPLACE(`thumbnail`, '/images/content/podcast/', '/images/podcast/')";
            if ($objDatabase->Execute($query) === false) {
                return _databaseError($query, $objDatabase->ErrorMsg());
            }
        }

        //set new default settings
        $query = "UPDATE `" . DBPREFIX . "module_podcast_settings` SET `setvalue` = '50' WHERE `setname` = 'thumb_max_size' AND `setvalue` = ''";
        if ($objDatabase->Execute($query) === false) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
        $query = "UPDATE `" . DBPREFIX . "module_podcast_settings` SET `setvalue` = '85' WHERE `setname` = 'thumb_max_size_homecontent' AND `setvalue` = ''";
        if ($objDatabase->Execute($query) === false) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }



        // only update if installed version is at least a version 2.0.0
        // older versions < 2.0 have a complete other structure of the content page and must therefore completely be reinstalled
        if (!$objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '2.0.0')) {
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

                    // add missing placeholder {PODCAST_JAVASCRIPT}
                    if (strpos($content, '{PODCAST_JAVASCRIPT}') === false) {
                        $content .= "\n{PODCAST_JAVASCRIPT}";
                    }

                    // add missing placeholder {PODCAST_PAGING}
                    if (strpos($content, '{PODCAST_PAGING}') === false) {
                        $content = preg_replace('/(\s+)(<!--\s+END\s+podcast_media\s+-->)/ms', '$1$2$1<div class="noMedium">$1    {PODCAST_PAGING}$1</div>', $content);
                    }

                    return $content;
                };

                \Cx\Lib\UpdateUtil::migrateContentPageUsingRegexCallback(array('module' => 'podcast'), $search, $callback, array('content'), '3.0.1');
            } catch (\Cx\Lib\UpdateException $e) {
                return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
            }
        }
    }

    if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '5.0.0')) {
        try {
            \Cx\Lib\UpdateUtil::table(
                DBPREFIX.'module_podcast_template',
                array(
                    'id'             => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                    'description'    => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'id'),
                    'template'       => array('type' => 'text', 'after' => 'description'),
                    'extensions'     => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'template')
                ),
                array(
                    'description'    => array('fields' => array('description'), 'type' => 'UNIQUE')
                )
            );

    // TODO: ask user to confirm this change
            \Cx\Lib\UpdateUtil::sql("UPDATE `".DBPREFIX."module_podcast_template` SET `template` = '<iframe width=\"[[MEDIUM_WIDTH]]\" height=\"[[MEDIUM_HEIGHT]]\" src=\"[[MEDIUM_URL]]\" frameborder=\"0\" allowfullscreen></iframe>' WHERE `description` = 'YouTube Video'");

            // Update the thumbnail path from images/podcast into images/Podcast
            \Cx\Lib\UpdateUtil::sql("UPDATE `".DBPREFIX."module_podcast_medium`
                                     SET `thumbnail` = REPLACE(`thumbnail`, 'images/podcast', 'images/Podcast')
                                     WHERE `thumbnail` LIKE ('".ASCMS_PATH_OFFSET."/images/podcast%')");
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }

        //Update script for moving the folder
        $imagePath       = ASCMS_DOCUMENT_ROOT . '/images';
        $sourceImagePath = $imagePath . '/podcast';
        $targetImagePath = $imagePath . '/Podcast';

        try {
            \Cx\Lib\UpdateUtil::migrateOldDirectory($sourceImagePath, $targetImagePath);
        } catch (\Exception $e) {
            \DBG::log($e->getMessage());
            setUpdateMsg(sprintf(
                $_ARRAYLANG['TXT_UNABLE_TO_MOVE_DIRECTORY'],
                $sourceImagePath, $targetImagePath
            ));
            return false;
        }

        $mediaPath       = ASCMS_DOCUMENT_ROOT . '/media';
        $sourceMediaPath = $mediaPath . '/podcast';
        $targetMediaPath = $mediaPath . '/Podcast';
        try {
            \Cx\Lib\UpdateUtil::migrateOldDirectory($sourceMediaPath, $targetMediaPath);
        } catch (\Exception $e) {
            \DBG::log($e->getMessage());
            setUpdateMsg(sprintf(
                $_ARRAYLANG['TXT_UNABLE_TO_MOVE_DIRECTORY'],
                $sourceMediaPath, $targetMediaPath
            ));
            return false;
        }

        // migrate path to images and media
        $pathsToMigrate = \Cx\Lib\UpdateUtil::getMigrationPaths();
        $attributes = array(
            'source'    => 'module_podcast_medium',
            'thumbnail' => 'module_podcast_medium',
            'setvalue'  => 'module_podcast_settings',
            'template'  => 'module_podcast_template',
        );
        try {
            foreach ($attributes as $attribute => $table) {
                foreach ($pathsToMigrate as $oldPath => $newPath) {
                    \Cx\Lib\UpdateUtil::migratePath(
                        '`' . DBPREFIX . $table .'`',
                        '`' . $attribute . '`',
                        $oldPath,
                        $newPath
                    );
                }
            }
        } catch (\Cx\Lib\Update_DatabaseException $e) {
            \DBG::log($e->getMessage());
            setUpdateMsg(sprintf(
                $_ARRAYLANG['TXT_UNABLE_TO_MIGRATE_MEDIA_PATH'],
                'Podcast (Podcast)'
            ));
            return false;
        }
    }

    return true;
}
