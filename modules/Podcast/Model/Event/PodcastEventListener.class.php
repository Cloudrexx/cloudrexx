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
 * EventListener for Podcast
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_podcast
 */

namespace Cx\Modules\Podcast\Model\Event;
use Cx\Core\MediaSource\Model\Entity\MediaSourceManager;
use Cx\Core\MediaSource\Model\Entity\MediaSource;
use Cx\Core\Event\Model\Entity\DefaultEventListener;

/**
 * Class PodcastEventListener
 * EventListener for Podcast
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_podcast
 */
class PodcastEventListener extends DefaultEventListener {

    public function SearchFindContent($search) {
        $term_db = $search->getTerm();

        //For Podcast
        $podcastQuery = "SELECT id, title, description AS content,
                            MATCH (description,title) AGAINST ('%$term_db%') AS score
                      FROM " . DBPREFIX . "module_podcast_medium
                     WHERE (   description LIKE ('%$term_db%')
                            OR title LIKE ('%$term_db%'))
                       AND status=1";
        $podcastResult = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($search->getResultArray($podcastQuery, 'Podcast', '', 'id=', $search->getTerm()));
        $search->appendResult($podcastResult);

        //For PodcastCategory
        $podcastCategoryQuery = "SELECT tblCat.id, tblCat.title, tblCat.description,
                           MATCH (title, description) AGAINST ('%$term_db%') AS score
                      FROM " . DBPREFIX . "module_podcast_category AS tblCat,
                           " . DBPREFIX . "module_podcast_rel_category_lang AS tblLang
                     WHERE (   title LIKE ('%$term_db%')
                            OR description LIKE ('%$term_db%'))
                       AND tblCat.status=1
                       AND tblLang.category_id=tblCat.id
                       AND tblLang.lang_id=" . FRONTEND_LANG_ID . "";
        $podcastCategoryResult = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($search->getResultArray($podcastCategoryQuery, 'Podcast', '', 'cid=', $search->getTerm()));
        $search->appendResult($podcastCategoryResult);
    }

    public function mediasourceLoad(MediaSourceManager $mediaBrowserConfiguration)
    {
        global $_ARRAYLANG;
        $mediaType = new MediaSource('podcast',$_ARRAYLANG['TXT_FILEBROWSER_PODCAST'],array(
            $this->cx->getWebsiteImagesPodcastPath(),
            $this->cx->getWebsiteImagesPodcastWebPath(),
        ),array(87));
        $mediaBrowserConfiguration->addMediaType($mediaType);
    }
}
