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
 * Class AccessEventListener
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Robin Glauser <robin.glauser@comvation.com>
 * @package     cloudrexx
 * @subpackage  coremodule_access
 * @version     1.0.0
 */

namespace Cx\Core_Modules\Access\Model\Event;

use Cx\Core\Event\Model\Entity\DefaultEventListener;
use Cx\Core\MediaSource\Model\Entity\MediaSourceManager;
use Cx\Core\MediaSource\Model\Entity\MediaSource;

/**
 * Class AccessEventListener
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Robin Glauser <robin.glauser@comvation.com>
 * @package     cloudrexx
 * @subpackage  coremodule_access
 * @version     1.0.0
 */
class AccessEventListener extends DefaultEventListener
{

    /**
     * @param MediaSourceManager $mediaBrowserConfiguration
     */
    public function mediasourceLoad(
        MediaSourceManager $mediaBrowserConfiguration
    ) {
        global $_ARRAYLANG;
        $mediaType = new MediaSource(
            'access',
            $_ARRAYLANG['TXT_USER_ADMINISTRATION'],
            array(
                $this->cx->getWebsiteImagesAccessPath(),
                $this->cx->getWebsiteImagesAccessWebPath(),
            ),
            array(18)
        );
        $mediaBrowserConfiguration->addMediaType($mediaType);
    }

    /**
     * Update the access user attributes
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

            // Update the access user attributes
            if ($langStatus) {
                $accessAttrQuery = 'INSERT IGNORE INTO `' . DBPREFIX . 'access_user_attribute_name`
                    (   
                        `attribute_id`,
                        `lang_id`,
                        `name`
                    )
                    SELECT 
                        `attribute_id`,
                        ' . $langId . ',
                        `name`
                    FROM `' . DBPREFIX . 'access_user_attribute_name`
                    WHERE lang_id = ' . $defaultLangId;
            } else {
                $accessAttrQuery = 'DELETE FROM `' . DBPREFIX . 'access_user_attribute_name`
                    WHERE lang_id = ' . $langId;
            }
            $this->cx->getDb()->getAdoDb()->Execute($accessAttrQuery);
        }
    }

}
