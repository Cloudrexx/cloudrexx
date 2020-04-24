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
 * EventListener for Gallery
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_gallery
 */

namespace Cx\Modules\Gallery\Model\Event;
use Cx\Core\MediaSource\Model\Entity\MediaSourceManager;
use Cx\Core\MediaSource\Model\Entity\MediaSource;
use Cx\Core\Event\Model\Entity\DefaultEventListener;

/**
 * Class GalleryEventListener
 * EventListener for Gallery
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_gallery
 */
class GalleryEventListener extends DefaultEventListener {

    public static function SearchFindContent($search) {
        $term_db = $search->getTerm();

        //For GalleryCategory
        $categoryQuery = "SELECT tblLang.gallery_id, tblLang.value AS title,
                           MATCH (tblLang.value) AGAINST ('%$term_db%') AS score
                      FROM " . DBPREFIX . "module_gallery_language AS tblLang,
                           " . DBPREFIX . "module_gallery_categories AS tblCat
                     WHERE tblLang.value LIKE ('%$term_db%')
                       AND tblLang.lang_id=" . FRONTEND_LANG_ID . "
                       AND tblLang.gallery_id=tblCat.id
                       AND tblCat.status=1";
        $categoryResult = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($search->getResultArray($categoryQuery, 'Gallery', 'showCat', 'cid=', $search->getTerm()));
        $search->appendResult($categoryResult);

        //For Gallerypicture
        $pictureQuery = "SELECT tblPic.catid AS id, tblLang.name AS title, tblLang.desc AS content,
                     MATCH (tblLang.name,tblLang.desc) AGAINST ('%$term_db%') AS score
                      FROM " . DBPREFIX . "module_gallery_pictures AS tblPic,
                           " . DBPREFIX . "module_gallery_language_pics AS tblLang,
                           " . DBPREFIX . "module_gallery_categories AS tblCat
                     WHERE (tblLang.name LIKE ('%$term_db%') OR tblLang.desc LIKE ('%$term_db%'))
                       AND tblLang.lang_id=" . FRONTEND_LANG_ID . "
                       AND tblLang.picture_id=tblPic.id
                       AND tblPic.status=1
                       AND tblCat.id=tblPic.catid
                       AND tblCat.status=1";
        $pictureResult = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($search->getResultArray($pictureQuery, 'Gallery', 'showCat', 'cid=', $search->getTerm()));
        $search->appendResult($pictureResult);
    }


    public function mediasourceLoad(MediaSourceManager $mediaBrowserConfiguration)
    {
        global $_ARRAYLANG;
        \Env::get('init')->loadLanguageData('Gallery');
        $mediaType = new MediaSource(
            'gallery',
            $_ARRAYLANG['TXT_THUMBNAIL_GALLERY'],
            array(
                $this->cx->getWebsiteImagesGalleryPath(),
                $this->cx->getWebsiteImagesGalleryWebPath()
            ),
            array(12, 67)
        );
        $mediaBrowserConfiguration->addMediaType($mediaType);
    }

}
