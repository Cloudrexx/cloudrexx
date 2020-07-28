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
 * Class MediaDirEventListener
 *
 * @copyright   Cloudrexx AG
 * @author      Robin Glauser <robin.glauser@comvation.com>
 * @package     cloudrexx
 */

namespace Cx\Modules\MediaDir\Model\Event;


use Cx\Core\Core\Controller\Cx;
use Cx\Core\MediaSource\Model\Entity\MediaSourceManager;
use Cx\Core\MediaSource\Model\Entity\MediaSource;
use Cx\Core\Event\Model\Entity\DefaultEventListener;

/**
 * Class MediaDirEventListener
 *
 * @copyright   Cloudrexx AG
 * @author      Robin Glauser <robin.glauser@comvation.com>
 * @package     cloudrexx
 */
class MediaDirEventListener extends DefaultEventListener
{

    /**
     * Global search event listener
     * Appends the MediaDir search results to the search object
     *
     * @param array $search
     */
    public function SearchFindContent($search) {
        // note: inclusion check is done in method MediaDirectoryEntry::searchResultsForSearchModule()

        $objEntry = new \Cx\Modules\MediaDir\Controller\MediaDirectoryEntry('MediaDir');
        $result   = new \Cx\Core_Modules\Listing\Model\Entity\DataSet(
            $objEntry->searchResultsForSearchModule($search)
        );
        $search->appendResult($result);
    }

    public function mediasourceLoad(
        MediaSourceManager $mediaBrowserConfiguration
    ) {
        global $_ARRAYLANG;
        \Env::get('init')->loadLanguageData('MediaDir');
        $mediaType = new MediaSource('mediadir',$_ARRAYLANG['TXT_FILEBROWSER_MEDIADIR'], array(
            $this->cx->getWebsiteImagesMediaDirPath(),
            $this->cx->getWebsiteImagesMediaDirWebPath(),
        ),array(153));
        $mediaBrowserConfiguration->addMediaType($mediaType);
    }


}
