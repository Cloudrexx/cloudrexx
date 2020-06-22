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
 * Class MediaEventListener
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Robin Glauser <robin.glauser@comvation.com>
 * @package     cloudrexx
 * @subpackage  coremodule_media
 * @version     1.0.0
 */

namespace Cx\Core_Modules\Media\Model\Event;

use Cx\Core\Event\Model\Entity\DefaultEventListener;
use Cx\Core\MediaSource\Model\Entity\MediaSourceManager;
use Cx\Core\MediaSource\Model\Entity\MediaSource;

/**
 * Class MediaEventListener
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Robin Glauser <robin.glauser@comvation.com>
 * @package     cloudrexx
 * @subpackage  coremodule_media
 * @version     1.0.0
 */
class MediaEventListener extends DefaultEventListener
{
    /**
     * Global search event listener
     * Appends the Media search results to the search object
     *
     * @param array $search \Cx\Core_Modules\Search\Controller\Search
     */
    public function SearchFindContent($search) {
        $result   = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($this->getComponent('Media')->getMediaForSearchComponent($search->getTerm()));
        $search->appendResult($result);
    }

    /**
     * @param MediaSourceManager $mediaBrowserConfiguration
     */
    public function mediasourceLoad(
        MediaSourceManager $mediaBrowserConfiguration
    ) {
        global $_ARRAYLANG;
        \Env::get('init')->loadLanguageData('Media');
        for ($i = 1; $i < 5; $i++) {
            $mediaType = new MediaSource('media' . $i,$_ARRAYLANG['TXT_MEDIA_ARCHIVE'] . ' ' . $i, array(
                call_user_func(
                    array($this->cx, 'getWebsiteMediaarchive' . $i . 'Path')
                ),
                call_user_func(
                    array(
                        $this->cx, 'getWebsiteMediaarchive' . $i . 'WebPath'
                    )
                ),
            ),array(7, 39, 38));

            $mediaBrowserConfiguration->addMediaType($mediaType);
        }
    }
}
