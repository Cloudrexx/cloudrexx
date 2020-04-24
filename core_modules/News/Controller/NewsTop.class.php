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
 * Top news
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Cloudrexx Development Team <info@cloudrexx.com>
 * @version 1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_news
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Core_Modules\News\Controller;

/**
 * Top news
 *
 * Gets all the top news
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Cloudrexx Development Team <info@cloudrexx.com>
 * @access public
 * @version 1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_news
 */
class NewsTop extends \Cx\Core_Modules\News\Controller\NewsLibrary
{
    public $_pageContent;
    public $_objTemplate;
    public $arrSettings = array();

    function __construct($pageContent)
    {
        parent::__construct();
        $this->_pageContent = $pageContent;
        $this->_objTemplate = new \Cx\Core\Html\Sigma('.');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTemplate);
    }

    /**
     * Get top news
     * If there are any news with scheduled publishing $nextUpdateDate will
     * contain the date when the next news changes its publishing state.
     * If there are are no news with scheduled publishing $nextUpdateDate will
     * be null.
     * @param integer $catId    Category id
     * @param integer $langId   Language id
     * @param \DateTime $nextUpdateDate (reference) DateTime of the next change
     * @return string Parsed HTML code
     */
    function getHomeTopNews($catId = 0, $langId = null, &$nextUpdateDate = null)
    {
        global $_CORELANG, $objDatabase;

        $catId= intval($catId);
        $i = 0;

        if (null === $langId) {
            $langId = FRONTEND_LANG_ID;
        }
        $this->_objTemplate->setTemplate($this->_pageContent,true,true);
        if ($this->_objTemplate->blockExists('newsrow')) {
            $this->_objTemplate->setCurrentBlock('newsrow');
        } else {
            return null;
        }

        $newsLimit = intval($this->arrSettings['news_top_limit']);
        if ($newsLimit>50) { //limit to a maximum of 50 news
            $newsLimit=50;
        }

        if ($newsLimit<1) { //do not get any news if 0 was specified as the limit.
            $objResult=false;
        } else {//fetch news
            $objResult = $objDatabase->SelectLimit("
                SELECT DISTINCT(tblN.id) AS id,
                       tblN.`date`,
                       tblN.teaser_image_path,
                       tblN.teaser_image_thumbnail_path,
                       tblN.redirect,
                       tblN.redirect_new_window AS redirectNewWindow,
                       tblN.publisher,
                       tblN.publisher_id,
                       tblN.author,
                       tblN.author_id,
                       tblN.startdate,
                       tblN.enddate,
                       tblL.title AS title,
                       tblL.teaser_text
                  FROM ".DBPREFIX."module_news AS tblN
            INNER JOIN ".DBPREFIX."module_news_locale AS tblL ON tblL.news_id=tblN.id
            INNER JOIN ".DBPREFIX."module_news_rel_categories AS tblC ON tblC.news_id=tblL.news_id
                  WHERE tblN.status=1".
                   ($catId > 0 ? " AND tblC.category_id=$catId" : '')."
                   AND tblN.teaser_only='0'
                   AND tblL.lang_id=". contrexx_input2int($langId) ."
                   AND (startdate<='".date('Y-m-d H:i:s')."' OR startdate='0000-00-00 00:00:00')
                   AND (enddate>='".date('Y-m-d H:i:s')."' OR enddate='0000-00-00 00:00:00')".
                   ($this->arrSettings['news_message_protection'] == '1' && !\Permission::hasAllAccess()
                      ? (($objFWUser = \FWUser::getFWUserObject()) && $objFWUser->objUser->login()
                          ? " AND (frontend_access_id IN (".
                            implode(',', array_merge(array(0), $objFWUser->objUser->getDynamicPermissionIds())).
                            ") OR userid=".$objFWUser->objUser->getId().") "
                          : " AND frontend_access_id=0 ")
                      : '').
                   "ORDER BY
                       (SELECT COUNT(*) FROM ".DBPREFIX."module_news_stats_view WHERE news_id=tblN.id AND time>'".date_format(date_sub(date_create('now'), date_interval_create_from_date_string(intval($this->arrSettings['news_top_days']).' day')), 'Y-m-d H:i:s')."') DESC", $newsLimit);
        }

        $nextUpdateDate = null;
        if ($objResult !== false && $objResult->RecordCount()) {
            while (!$objResult->EOF) {
                if (
                    $objResult->fields['startdate'] != '0000-00-00 00:00:00' &&
                    $objResult->fields['enddate'] != '0000-00-00 00:00:00'
                ) {
                    $startDate = new \DateTime($objResult->fields['startdate']);
                    $endDate = new \DateTime($objResult->field['enddate']);
                    if (
                        $endDate > new \DateTime() &&
                        (
                            !$nextUpdateDate ||
                            $endDate < $nextUpdateDate
                        )
                    ) {
                        $nextUpdateDate = $endDate;
                    }
                    if (
                        $startDate > new \DateTime() &&
                        (
                            !$nextUpdateDate ||
                            $startDate < $nextUpdateDate
                        )
                    ) {
                        $nextUpdateDate = $startDate;
                    }
                }

                $newsid     = $objResult->fields['id'];
                $newstitle  = $objResult->fields['title'];
                $author     = \FWUser::getParsedUserTitle($objResult->fields['author_id'], $objResult->fields['author']);
                $publisher  = \FWUser::getParsedUserTitle($objResult->fields['publisher_id'], $objResult->fields['publisher']);
                $newsCategories  = $this->getCategoriesByNewsId($newsid);
                $newsUrl    = empty($objResult->fields['redirect'])
                                ? \Cx\Core\Routing\Url::fromModuleAndCmd('News', $this->findCmdById('details', self::sortCategoryIdByPriorityId(array_keys($newsCategories), array($catId))), $langId, array('newsid' => $newsid))
                                : $objResult->fields['redirect'];
                $redirectNewWindow = !empty($objResult->fields['redirect']) && !empty($objResult->fields['redirectNewWindow']);
                $htmlLink = self::parseLink($newsUrl, $newstitle, contrexx_raw2xhtml($newstitle), $redirectNewWindow);
                $htmlLinkTitle = self::parseLink($newsUrl, $newstitle, contrexx_raw2xhtml($newstitle), $redirectNewWindow);
                $linkTarget = $redirectNewWindow ? '_blank' : '_self';
                // in case that the message is a stub, we shall just display the news title instead of a html-a-tag with no href target
                if (empty($htmlLinkTitle)) {
                    $htmlLinkTitle = contrexx_raw2xhtml($newstitle);
                }

                list($image, $htmlLinkImage, $imageSource) = self::parseImageThumbnail($objResult->fields['teaser_image_path'],
                                                                                       $objResult->fields['teaser_image_thumbnail_path'],
                                                                                       $newstitle,
                                                                                       $newsUrl);

                $this->_objTemplate->setVariable(array(
                    'NEWS_ID'           => $newsid,
                    'NEWS_CSS'          => 'row'.($i % 2 + 1),
                    'NEWS_LONG_DATE'    => date(ASCMS_DATE_FORMAT, $objResult->fields['date']),
                    'NEWS_DATE'         => date(ASCMS_DATE_FORMAT_DATE, $objResult->fields['date']),
                    'NEWS_TIME'         => date(ASCMS_DATE_FORMAT_TIME, $objResult->fields['date']),
                    'NEWS_TIMESTAMP'    => $objResult->fields['date'],
                    'NEWS_TITLE'        => contrexx_raw2xhtml($newstitle),
                    'NEWS_TEASER'       => $this->arrSettings['news_use_teaser_text'] ? nl2br($objResult->fields['teaser_text']) : '',
                    'NEWS_LINK_TITLE'   => $htmlLinkTitle,
                    'NEWS_LINK'         => $htmlLink,
                    'NEWS_LINK_TARGET'  => $linkTarget,
                    'NEWS_LINK_URL'     => contrexx_raw2xhtml($newsUrl),
                    'NEWS_AUTHOR'       => contrexx_raw2xhtml($author),
                    'NEWS_PUBLISHER'    => contrexx_raw2xhtml($publisher),
                ));

                if (!empty($image)) {
                    $this->_objTemplate->setVariable(array(
                        'NEWS_IMAGE_ID'      => $newsid,
                        'NEWS_IMAGE'         => $image,
                        'NEWS_IMAGE_SRC'     => contrexx_raw2xhtml($imageSource),
                        'NEWS_IMAGE_ALT'     => contrexx_raw2xhtml($newstitle),
                        'NEWS_IMAGE_LINK'    => $htmlLinkImage,
                        'NEWS_IMAGE_LINK_URL'=> contrexx_raw2xhtml($newsUrl),
                    ));

                    if ($this->_objTemplate->blockExists('news_image')) {
                        $this->_objTemplate->parse('news_image');
                    }
                    if ($this->_objTemplate->blockExists('news_no_image')) {
                        $this->_objTemplate->hideBlock('news_no_image');
                    }
                } else {
                    if ($this->_objTemplate->blockExists('news_image')) {
                        $this->_objTemplate->hideBlock('news_image');
                    }
                    if ($this->_objTemplate->blockExists('news_no_image')) {
                        $this->_objTemplate->touchBlock('news_no_image');
                    }
                }

                self::parseImageBlock($this->_objTemplate, $objResult->fields['teaser_image_thumbnail_path'], $newstitle, $newsUrl, 'image_thumbnail');
                self::parseImageBlock($this->_objTemplate, $objResult->fields['teaser_image_path'], $newstitle, $newsUrl, 'image_detail');

                $this->_objTemplate->parseCurrentBlock();
                $i++;
                $objResult->MoveNext();
            }
        } else {
            $this->_objTemplate->hideBlock('newsrow');
        }
        $this->_objTemplate->setVariable("TXT_MORE_NEWS", $_CORELANG['TXT_MORE_NEWS']);
        return $this->_objTemplate->get();
    }
}

