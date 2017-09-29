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

/**
 * Class DownloadsEventListener
 *
 * @copyright   Cloudrexx AG
 * @author      Robin Glauser <robin.glauser@comvation.com>
 * @package     cloudrexx
 */

namespace Cx\Modules\Downloads\Model\Event;

use Cx\Core\MediaSource\Model\Entity\MediaSourceManager;
use Cx\Core\MediaSource\Model\Entity\MediaSource;
use Cx\Core\Event\Model\Entity\DefaultEventListener;

/**
 * Class DownloadsEventListener
 *
 * @copyright   Cloudrexx AG
 * @author      Robin Glauser <robin.glauser@comvation.com>
 * @package     cloudrexx
 */
class DownloadsEventListener extends DefaultEventListener
{

    public function mediasourceLoad(
        MediaSourceManager $mediaBrowserConfiguration
    ) {
        global $_ARRAYLANG;
        $mediaType = new MediaSource('downloads',$_ARRAYLANG['TXT_FILEBROWSER_DOWNLOADS'],array(
            $this->cx->getWebsiteImagesDownloadsPath(),
            $this->cx->getWebsiteImagesDownloadsWebPath(),
        ),array(141));
        $mediaBrowserConfiguration->addMediaType($mediaType);
    }

    /**
     * Update the category locales
     * while activate/deactivate a language in the Administrative -> Language
     *
     * @param array $eventArgs Arguments for the event
     *
     * @return boolean
     */
    protected function languageStatusUpdate(array $eventArgs) {
        if (empty($eventArgs)) {
            return;
        }

        $defaultLangId = \FWLanguage::getDefaultLangId();
        foreach ($eventArgs['langData'] as $args) {

            $langId = isset($args['langId']) ? $args['langId'] : 0;
            $langStatus = isset($args['status']) ? $args['status'] : 0;

            if (
                empty($langId) ||
                !isset($args['status']) ||
                (
                    !$langStatus &&
                    !$eventArgs['langRemovalStatus']
                )
            ) {
                continue;
            }

            $db = $this->cx->getDb()->getAdoDb();
            // Update the download locales
            if ($langStatus) {
                $downloadQuery = 'INSERT IGNORE INTO `' . DBPREFIX . 'module_downloads_download_locale`
                    (   
                        `lang_id`,
                        `download_id`,
                        `name`,
                        `source`,
                        `source_name`,
                        `file_type`,
                        `description`,
                        `metakeys`
                    )
                    SELECT 
                        ' . $langId . ',
                        `download_id`,
                        `name`,
                        `source`,
                        `source_name`,
                        `file_type`,
                        `description`,
                        `metakeys`
                    FROM `' . DBPREFIX . 'module_downloads_download_locale`
                    WHERE lang_id = ' . $defaultLangId;
            } else {
                $downloadQuery = 'DELETE FROM `' . DBPREFIX . 'module_downloads_download_locale`
                    WHERE lang_id = ' . $langId;
            }
            $db->Execute($downloadQuery);

            // Update the category locales
            if ($langStatus) {
                $categoryQuery = 'INSERT IGNORE INTO `' . DBPREFIX . 'module_downloads_category_locale`
                    (   
                        `lang_id`,
                        `category_id`,
                        `name`,
                        `description`
                    )
                    SELECT 
                        ' . $langId . ',
                        `category_id`,
                        `name`,
                        `description`
                    FROM `' . DBPREFIX . 'module_downloads_category_locale`
                    WHERE lang_id = ' . $defaultLangId;
            } else {
                $categoryQuery = 'DELETE FROM `' . DBPREFIX . 'module_downloads_category_locale`
                    WHERE lang_id = ' . $langId;
            }
            $db->Execute($categoryQuery);

            // Update the group locales
            if ($langStatus) {
                $groupQuery = 'INSERT IGNORE INTO `' . DBPREFIX . 'module_downloads_group_locale`
                    (   
                        `lang_id`,
                        `group_id`,
                        `name`
                    )
                    SELECT 
                        ' . $langId . ',
                        `group_id`,
                        `name`
                    FROM `' . DBPREFIX . 'module_downloads_group_locale`
                    WHERE lang_id = ' . $defaultLangId;
            } else {
                $groupQuery = 'DELETE FROM `' . DBPREFIX . 'module_downloads_group_locale`
                    WHERE lang_id = ' . $langId;
            }
            $db->Execute($groupQuery);
        }
    }


}
