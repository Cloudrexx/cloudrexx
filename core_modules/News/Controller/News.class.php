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
 * News
 *
 * This module will get all the news pages
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_news
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Core_Modules\News\Controller;

/**
 * News
 *
 * This module will get all the news pages
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_news
 */
class News extends \Cx\Core_Modules\News\Controller\NewsLibrary {
    public $newsTitle;
    public $newsText;
    public $newsThumbnail;
    public $arrSettings = array();
    public $_objTpl;
    public $_submitMessage;

    /**
     * Holds the teaser text when displaying details.
     * Accessed via news::getTeaser().
     * @var String $_teaser
     */
    private $_teaser = null;

    /**
     * Initializes the news module by loading the configuration options
     * and initializing the template object with $pageContent.
     *
     * @param  string  News content page
     */
    public function __construct($pageContent)
    {
        parent::__construct();

        $this->_objTpl = new \Cx\Core\Html\Sigma();
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);
        $this->_objTpl->setTemplate($pageContent);
    }

    /**
    * Get page
    *
    * @return string content
    */
    public function getNewsPage()
    {
        if (!isset($_REQUEST['cmd'])) {
            $_REQUEST['cmd'] = '';
        }

        $expirationDate = null;
        switch ($_REQUEST['cmd']) {
        case 'details':
            // cache timeout: this article's end date
            $details  = $this->getDetails($expirationDate);
            $response = \Cx\Core\Core\Controller\Cx::instanciate()->getResponse();
            $response->setExpirationDate($expirationDate);
            return $details;
            break;
        case 'submit':
            return $this->_submit();
            break;
        case 'feed':
            return $this->_showFeed();
            break;
        case 'archive':
            // cache timeout: next start or end date over all articles
            $archive  = $this->getArchive($expirationDate);
            $response = \Cx\Core\Core\Controller\Cx::instanciate()->getResponse();
            $response->setExpirationDate($expirationDate);
            return $archive;
            break;
        case 'topnews':
            // cache timeout: next start or end date over all articles
            $topNews  = $this->getTopNews($expirationDate);
            $response = \Cx\Core\Core\Controller\Cx::instanciate()->getResponse();
            $response->setExpirationDate($expirationDate);
            return $topNews;
            break;
        default:
            if (substr($_REQUEST['cmd'], 0, 7) == 'details') {
                // cache timeout: this article's end date
                $categoryId = intval(substr($_REQUEST['cmd'], 7));
                $details  = $this->getDetails($expirationDate, $categoryId);
                $response = \Cx\Core\Core\Controller\Cx::instanciate()->getResponse();
                $response->setExpirationDate($expirationDate);
                return $details;
            } elseif (substr($_REQUEST['cmd'], 0, 7) == 'archive') {
                // cache timeout: next start or end date over all articles
                $archives = $this->getArchive($expirationDate);
                $response = \Cx\Core\Core\Controller\Cx::instanciate()->getResponse();
                $response->setExpirationDate($expirationDate);
                return $archives;
            } else {
                // cache timeout: next start or end date over all articles
                $headLines = $this->getHeadlines($expirationDate);
                $response  = \Cx\Core\Core\Controller\Cx::instanciate()->getResponse();
                $response->setExpirationDate($expirationDate);
                return $headLines;
            }
            break;
        }
    }

    /**
     * Gets the news details
     *
     * @param  string $expirationDate Expiration date
     * @param  integer $categoryId ID of category to output the latest news
     *                             article from, in case the URL-argument
     *                             newsid is not set and the placeholder
     *                             NEWS_LIST_LATEST is present in the
     *                             application template.
     * @return string parsed content
     */
    private function getDetails(&$expirationDate = null, $categoryId = 0)
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;

        if (!empty($_GET['newsid'])) {
            $newsid = intval($_GET['newsid']);
        } elseif ($this->_objTpl->placeholderExists('NEWS_LIST_LATEST')) {
            try {
                $newsid = $this->getIdOfLatestNewsArticle($categoryId);
            } catch (NewsLibraryException $e) {}
        }

        if (empty($newsid)) {
            header('Location: '.\Cx\Core\Routing\Url::fromModuleAndCmd('News'));
            exit;
        }

        $whereStatus    = '';
        $newsAccess     = \Permission::checkAccess(10, 'static', true);
        $newsPreview    = !empty($_GET['newsPreview']) ? intval($_GET['newsPreview']) : 0;
        $base64Redirect = base64_encode(\Env::get('cx')->getRequest()->getUrl());
        if ($newsPreview && !$newsAccess) {
            \Permission::noAccess($base64Redirect);
        } else if (!$newsAccess) {
            $whereStatus = 'news.status = 1 AND';
        }

// TODO: add error handler to load the fallback-language version of the news message
//       in case the message doesn't exist in the requested language. But only try load the
//       the message in the fallback-language in case the associated news-detail content page
//       is setup to use the content of the fallback-language
        $query = '
            SELECT
                news.id                 AS id,
                news.userid             AS userid,
                news.redirect           AS redirect,
                news.source             AS source,
                news.changelog          AS changelog,
                news.url1               AS url1,
                news.url2               AS url2,
                news.date               AS date,
                news.publisher          AS publisher,
                news.publisher_id       AS publisherid,
                news.enddate            AS enddate,
                news.author             AS author,
                news.author_id          AS authorid,
                news.changelog          AS changelog,
                news.teaser_image_path  AS newsimage,
                news.enable_related_news AS enableRelatedNews,
                news.enable_tags        AS enableTags,
                news.teaser_image_thumbnail_path AS newsThumbImg,
                news.typeid             AS typeid,
                news.allow_comments     AS commentactive,
                news.frontend_access_id,
                locale.text,
                locale.title            AS title,
                locale.teaser_text
            FROM
                ' . DBPREFIX . 'module_news AS news
            INNER JOIN
                ' . DBPREFIX . 'module_news_locale AS locale
            ON
                news.id = locale.news_id
            WHERE
                ' . $whereStatus . '
                news.id = ' . $newsid . ' AND
                locale.is_active = 1 AND
                locale.lang_id = ' . FRONTEND_LANG_ID . '
        ';
        // ignore time for previews
        if (!$newsPreview) {
            $query .= ' AND
                (
                    news.startdate <= \'' . date('Y-m-d H:i:s') . '\' OR
                    news.startdate="0000-00-00 00:00:00"
                ) AND
                (
                    news.enddate >= \'' . date('Y-m-d H:i:s') . '\' OR
                    news.enddate="0000-00-00 00:00:00"
                )
            ';
        }

        $objResult = $objDatabase->SelectLimit($query, 1);

        if (!$objResult || $objResult->EOF) {
            header('Location: '.\Cx\Core\Routing\Url::fromModuleAndCmd('News'));
            exit;
        }

        if (
            $this->arrSettings['news_message_protection'] == '1' &&
            !\Permission::hasAllAccess()
        ) {
            $validAccessIds = array(0);
            if (
                ($objFWUser = \FWUser::getFWUserObject()) &&
                $objFWUser->objUser->login()
            ) {
                $validAccessIds = array_merge(
                    array(0),
                    $objFWUser->objUser->getDynamicPermissionIds()
                );
            }
            if (
                !in_array(
                    $objResult->fields['frontend_access_id'],
                    $validAccessIds
                )
            ) {
                if ($this->arrSettings['login_redirect'] == '1') {
                    \Permission::noAccess($base64Redirect);
                }
                header(
                    'Location: '.\Cx\Core\Routing\Url::fromModuleAndCmd('News')
                );
                exit;
            }
        }

        $newsCommentActive  = $objResult->fields['commentactive'];
        $lastUpdate         = $objResult->fields['changelog'];
        $text               = $objResult->fields['text'];
        $newsTeaser         = '';
        $redirect           = $objResult->fields['redirect'];
        $source             = contrexx_raw2xhtml($objResult->fields['source']);
        $url1               = $objResult->fields['url1'];
        $url2               = $objResult->fields['url2'];
        $newsLastUpdate     = !empty($lastUpdate)
                               ? $_ARRAYLANG['TXT_LAST_UPDATE'].'<br />'.date(ASCMS_DATE_FORMAT, $lastUpdate)
                               : '';

        if ($objResult->fields['enddate'] != '0000-00-00 00:00:00') {
            $expirationDate = new \DateTime($objResult->fields['enddate']);
        }

        $this->newsTitle = $objResult->fields['title'];
        $newstitle = $this->newsTitle;
        $this->newsText = $text;

        // use main image as METAIMAGE
        if (!empty($objResult->fields['newsimage'])) {
            $this->newsThumbnail = $objResult->fields['newsimage'];
        } else {
            $this->newsThumbnail = $objResult->fields['newsThumbImg'];
        }
        if ($this->arrSettings['news_use_teaser_text']) {
            $newsTeaser = nl2br($objResult->fields['teaser_text']);
            \LinkGenerator::parseTemplate($newsTeaser);
        }

        $newsCategories = $this->getCategoriesByNewsId(
            $newsid,
            array($categoryId)
        );
        // Parse the Category list
        $this->parseCategoryList($this->_objTpl, $newsCategories);

        $this->_objTpl->setVariable(array(
           'NEWS_ID'             => $newsid,
           'NEWS_LONG_DATE'      => date(ASCMS_DATE_FORMAT,$objResult->fields['date']),
           'NEWS_DATE'           => date(ASCMS_DATE_FORMAT_DATE,$objResult->fields['date']),
           'NEWS_TIME'           => date(ASCMS_DATE_FORMAT_TIME,$objResult->fields['date']),
           'NEWS_TIMESTAMP'      => $objResult->fields['date'],
           'NEWS_TITLE'          => $newstitle,
           'NEWS_TEASER_TEXT'    => $newsTeaser,
           'NEWS_LASTUPDATE'     => $newsLastUpdate,
           'NEWS_CATEGORY_NAME'  => implode(', ', contrexx_raw2xhtml($newsCategories)),
           'NEWS_TYPE_ID'        => $objResult->fields['typeid'],
           'NEWS_TYPE_NAME'      => contrexx_raw2xhtml($this->getTypeNameById($objResult->fields['typeid'])),
        ));

        // parse 'combined' external link
        $newsUrl = '';
        if (!empty($url1)) {
            $newsUrl = $_ARRAYLANG['TXT_IMPORTANT_HYPERLINKS'] . '<br />' . $this->getNewsLink($url1) . '<br />';
        }
        if (!empty($url2)) {
            $newsUrl .= $this->getNewsLink($url2).'<br />';
        }
        $this->_objTpl->setVariable(
            'NEWS_URL',
            $newsUrl
        );

        // parse external source
        $newsSourceLink = '';
        $newsSource = '';
        if (!empty($source)) {
            $newsSourceLink = $this->getNewsLink($source);
            $newsSource = $_ARRAYLANG['TXT_NEWS_SOURCE'] . '<br />'. $newsSourceLink . '<br />';
        }
        $this->_objTpl->setVariable(array(
            'TXT_NEWS_SOURCE' => $_ARRAYLANG['TXT_NEWS_SOURCE'],
            'NEWS_SOURCE'     => $newsSource,
            'NEWS_SOURCE_LINK'=> $newsSourceLink,
            'NEWS_SOURCE_SRC' => $source,
        ));
        if ($this->_objTpl->blockExists('news_source')) {
            if (empty($source)) {
                $this->_objTpl->hideBlock('news_source');
            } else {
                $this->_objTpl->touchBlock('news_source');
            }
        }

        // parse external link 1
        $this->_objTpl->setVariable(array(
            'TXT_NEWS_LINK1' =>
                $_ARRAYLANG['TXT_NEWS_LINK1'],
            'NEWS_LINK1_SRC' =>
                $url1,
        ));
        if ($this->_objTpl->blockExists('news_link1')) {
            if (empty($url1)) {
                $this->_objTpl->hideBlock('news_link1');
            } else {
                $this->_objTpl->touchBlock('news_link1');
            }
        }

        // parse external link 2
        $this->_objTpl->setVariable(array(
            'TXT_NEWS_LINK2' =>
                $_ARRAYLANG['TXT_NEWS_LINK2'],
            'NEWS_LINK2_SRC' =>
                $url2,
        ));
        if ($this->_objTpl->blockExists('news_link2')) {
            if (empty($url2)) {
                $this->_objTpl->hideBlock('news_link2');
            } else {
                $this->_objTpl->touchBlock('news_link2');
            }
        }

        // hide teaser container if the use of teasers has been deactivated
        if (
            $this->arrSettings['news_use_teaser_text'] != '1' &&
            $this->_objTpl->blockExists('news_use_teaser_text')
        ) {
            $this->_objTpl->hideBlock('news_use_teaser_text');
        }

        // Parse the news comments count
        $this->parseNewsCommentsCount($this->_objTpl, $newsid, $newsCommentActive);

        // parse author
        self::parseUserAccountData($this->_objTpl, $objResult->fields['authorid'], $objResult->fields['author'], 'news_author');
        // parse publisher
        self::parseUserAccountData($this->_objTpl, $objResult->fields['publisherid'], $objResult->fields['publisher'], 'news_publisher');

        // show comments
        $this->parseMessageCommentForm($this->_objTpl, $newsid, $newstitle, $newsCommentActive);
        $this->parseCommentsOfMessage($this->_objTpl, $newsid, $newsCommentActive);

        // Show related_messages
        $this->parseRelatedMessagesOfMessage(
            $newsid,
            'category',
            array_keys($newsCategories),
            array($categoryId)
        );
        $this->parseRelatedMessagesOfMessage(
            $newsid,
            'type',
            $objResult->fields['typeid'],
            array($categoryId)
        );
        $this->parseRelatedMessagesOfMessage(
            $newsid,
            'publisher',
            $objResult->fields['publisherid'],
            array($categoryId)
        );
        $this->parseRelatedMessagesOfMessage(
            $newsid,
            'author',
            $objResult->fields['authorid'],
            array($categoryId)
        );

        /*
         * save the teaser text.
         * purpose of this: @link news::getTeaser()
         */
        $this->_teaser = contrexx_raw2xhtml($newsTeaser);

        if (    !empty($this->arrSettings['news_use_tags'])
            &&  !empty($objResult->fields['enableTags'])
        ) {
            \JS::registerCss('core_modules/News/View/Style/Tags.css');
            $this->parseNewsTags($this->_objTpl, $newsid, 'news_tag_list', true);
        }

        // parse related news
        if (    !empty($this->arrSettings['use_related_news'])
            &&  !empty($objResult->fields['enableRelatedNews'])
        ) {
            $this->parseRelatedNews(
                $this->_objTpl,
                $newsid,
                array($categoryId)
            );
            \JS::registerCss('core_modules/News/View/Style/RelatedSearch.css');
        }

        if (!empty($objResult->fields['newsimage'])) {
            $this->_objTpl->setVariable(array(
                'NEWS_IMAGE'               => '<img src="'.$objResult->fields['newsimage'].'" alt="'.$newstitle.'" />',
                'NEWS_IMAGE_SRC'           => $objResult->fields['newsimage'],
                'NEWS_IMAGE_ALT'           => $newstitle,
            ));

            if ($this->_objTpl->blockExists('news_image')) {
                $this->_objTpl->parse('news_image');
            }
            if ($this->_objTpl->blockExists('news_no_image')) {
                $this->_objTpl->hideBlock('news_no_image');
            }
        } else {
            if ($this->_objTpl->blockExists('news_image')) {
                $this->_objTpl->hideBlock('news_image');
            }
            if ($this->_objTpl->blockExists('news_no_image')) {
                $this->_objTpl->touchBlock('news_no_image');
            }
        }

        self::parseImageBlock($this->_objTpl, $objResult->fields['newsThumbImg'], $newstitle, '', 'image_thumbnail');
        self::parseImageBlock($this->_objTpl, $objResult->fields['newsimage'], $newstitle, '', 'image_detail');
        //previous next newslink
        if (    !empty($this->arrSettings['use_previous_next_news_link']) 
            &&  $this->_objTpl->blockExists('news_details_previous_next_links')
        ) {
            //Register the RelatedLinks.css for styling the previous and next link
            \JS::registerCss('core_modules/News/View/Style/RelatedLinks.css');
            $this->parseNextAndPreviousLinks(
                $this->_objTpl,
                array($categoryId)
            );
        }

        // The news_text block will be hidden if the news is set to redirect type
        $this->showNewsTextOrRedirectLink($this->_objTpl, $text, $redirect);

        $this->countNewsMessageView($newsid);
        $objResult->MoveNext();

        return $this->_objTpl->get();
    }

    private function countNewsMessageView($newsMessageId)
    {
        global $objDatabase;

        /*
         * count stat if option "top news" is activated
         */
        if (!$this->arrSettings['news_use_top']) {
            return;
        }

        if (checkForSpider()) {
            return;
        }

        $objDatabase->Execute(' DELETE FROM `'.DBPREFIX.'module_news_stats_view`
                                WHERE `time` < "'.date_format(date_sub(date_create('now'), date_interval_create_from_date_string(intval($this->arrSettings['news_top_days']).' days')), 'Y-m-d H:i:s').'"');

        $componentRepo = \Cx\Core\Core\Controller\Cx::instanciate()
                            ->getDb()
                            ->getEntityManager()
                            ->getRepository('Cx\Core\Core\Model\Entity\SystemComponent');
        $statsComponentContoller = $componentRepo->findOneBy(array('name' => 'Stats'));
        if (!$statsComponentContoller) {
            return;
        }
        $uniqueUserId = $statsComponentContoller->getCounterInstance()->getUniqueUserId();

        $query = '
            SELECT 1
            FROM `'.DBPREFIX.'module_news_stats_view`
            WHERE user_sid = "'.$uniqueUserId.'"
              AND news_id  = '.$newsMessageId.'
              AND time     > "'.date_format(date_sub(date_create('now'), date_interval_create_from_date_string('1 day')), 'Y-m-d H:i:s').'"';
        $objResult = $objDatabase->SelectLimit($query);
        if (!$objResult || !$objResult->EOF) {
            return;
        }

        $query = "INSERT INTO ".DBPREFIX."module_news_stats_view
                     SET user_sid = '$uniqueUserId',
                         news_id  = '$newsMessageId'";
        $objDatabase->Execute($query);
    }

    /**
     * Parses a list of news messages that are related to a specific news message
     * (specified by $messageId) by the same relation object. The relation object
     * is specified by its kind ($relatedByKind) and its ID ($relatedKindId).
     * The relation kind can be one of the following:
     * - category ids
     * - type
     * - publisher
     * - author
     *
     * @param   integer News message-ID
     * @param   string  Relation kind
     * @param   integer Relation-ID
     * @global  ADONewConnection
     *
     */
    private function parseRelatedMessagesOfMessage(
        $messageId,
        $relatedByKind,
         $relatedKindId,
         $selectedCategories
    ) {
        global $objDatabase, $_ARRAYLANG;

        static $arrRelatedKinds = array('category', 'type', 'publisher', 'author');

        // abort if no message ID has been supplied
        if (!$messageId) {
            return;
        }

        // abort if relation is unknown
        if (!in_array($relatedByKind, $arrRelatedKinds)) {
            return;
        }

        $relationTemplateBlock = "news_{$relatedByKind}_related_block";
        $imageTemplateBlock = "news_{$relatedByKind}_related_message_image";
        $messageTemplateBlock = "news_{$relatedByKind}_related_message";
        $placeholderPrefix = strtoupper($relatedByKind);
        $i = 0;

        // abort if template block of related messages doesn't exist
        if (!$this->_objTpl->blockExists($relationTemplateBlock)) {
            return false;
        }

        // abort if no ID of the related object has been supplied
        if (empty($relatedKindId)) {
            $this->_objTpl->hideBlock("news_{$relatedByKind}_related_block");
            return;
        }

        $query = '  SELECT  DISTINCT(n.id)      AS newsid,
                            n.userid            AS newsuid,
                            n.date              AS newsdate,
                            n.teaser_image_path,
                            n.teaser_image_thumbnail_path,
                            n.redirect,
                            n.publisher,
                            n.publisher_id,
                            n.author,
                            n.author_id,
                            nl.title            AS newstitle,
                            nl.text NOT REGEXP \'^(<br type="_moz" />)?$\' AS newscontent,
                            nl.teaser_text
                FROM        '.DBPREFIX.'module_news AS n
                INNER JOIN  '.DBPREFIX.'module_news_locale AS nl ON nl.news_id = n.id
                LEFT JOIN  '.DBPREFIX.'module_news_rel_categories AS nc ON nc.news_id=n.id
                WHERE       status = 1
                            AND nl.is_active=1
                            AND nl.lang_id='.FRONTEND_LANG_ID.'
                            '.($relatedByKind == 'category'  ? 'AND nc.category_id IN ('. (is_array($relatedKindId) ? implode(', ', contrexx_input2int($relatedKindId)) : contrexx_input2int($relatedKindId)) .')' : null)
                             .($relatedByKind == 'type'      ? 'AND n.typeid       ='.$relatedKindId : null)
                             .($relatedByKind == 'publisher' ? 'AND n.publisher_id ='.$relatedKindId : null)
                             .($relatedByKind == 'author'    ? 'AND n.authorid     ='.$relatedKindId : null).'
                            AND n.id !='.$messageId.'
                            AND (n.startdate<=\''.date('Y-m-d H:i:s').'\' OR n.startdate="0000-00-00 00:00:00")
                            AND (n.enddate>=\''.date('Y-m-d H:i:s').'\' OR n.enddate="0000-00-00 00:00:00")'
                           .($this->arrSettings['news_message_protection'] == '1' && !\Permission::hasAllAccess() ? (
                                ($objFWUser = \FWUser::getFWUserObject()) && $objFWUser->objUser->login() ?
                                    " AND (frontend_access_id IN (".implode(',', array_merge(array(0), $objFWUser->objUser->getDynamicPermissionIds())).") OR userid = ".$objFWUser->objUser->getId().") "
                                    :   " AND frontend_access_id=0 ")
                                :   '')
                .'ORDER BY newsdate DESC';

        $objResult = $objDatabase->Execute($query);

        // abort if no related messages were found or an error did occur
        if (!$objResult || $objResult->EOF) {
            $this->_objTpl->hideBlock("news_{$relatedByKind}_related_block");
            return;
        }

        while (!$objResult->EOF) {
            $newsid         = $objResult->fields['newsid'];
            $newsCategories = $this->getCategoriesByNewsId(
                $newsid,
                $selectedCategories
            );
            $newstitle      = $objResult->fields['newstitle'];
            $newsUrl        = empty($objResult->fields['redirect'])
                                ? (empty($objResult->fields['newscontent'])
                                    ? ''
                                    : \Cx\Core\Routing\Url::fromModuleAndCmd('News', $this->findCmdById('details', array_keys($newsCategories)), FRONTEND_LANG_ID, array('newsid' => $newsid)))
                                : $objResult->fields['redirect'];

            $redirectNewWindow = !empty($objResult->fields['redirect']) && !empty($objResult->fields['redirectNewWindow']);
            $htmlLink = self::parseLink($newsUrl, $newstitle, contrexx_raw2xhtml('['.$_ARRAYLANG['TXT_NEWS_MORE'].'...]'), $redirectNewWindow);
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
            $author = \FWUser::getParsedUserTitle($objResult->fields['author_id'], $objResult->fields['author']);
            $publisher = \FWUser::getParsedUserTitle($objResult->fields['publisher_id'], $objResult->fields['publisher']);

            $this->_objTpl->setVariable(array(
               'NEWS_'.$placeholderPrefix.'_RELATED_MESSAGE_ID'            => $newsid,
               'NEWS_'.$placeholderPrefix.'_RELATED_MESSAGE_CSS'           => 'row'.($i % 2 + 1),
               'NEWS_'.$placeholderPrefix.'_RELATED_MESSAGE_TEASER'        => $this->arrSettings['news_use_teaser_text'] ? nl2br($objResult->fields['teaser_text']) : '',
               'NEWS_'.$placeholderPrefix.'_RELATED_MESSAGE_TITLE'         => contrexx_raw2xhtml($newstitle),
               'NEWS_'.$placeholderPrefix.'_RELATED_MESSAGE_LONG_DATE'     => date(ASCMS_DATE_FORMAT,$objResult->fields['newsdate']),
               'NEWS_'.$placeholderPrefix.'_RELATED_MESSAGE_DATE'          => date(ASCMS_DATE_FORMAT_DATE, $objResult->fields['newsdate']),
               'NEWS_'.$placeholderPrefix.'_RELATED_MESSAGE_TIME'          => date(ASCMS_DATE_FORMAT_TIME, $objResult->fields['newsdate']),
               'NEWS_'.$placeholderPrefix.'_RELATED_MESSAGE_TIMESTAMP'     => $objResult->fields['newsdate'],
               'NEWS_'.$placeholderPrefix.'_RELATED_MESSAGE_LINK_TITLE'    => $htmlLinkTitle,
               'NEWS_'.$placeholderPrefix.'_RELATED_MESSAGE_LINK'          => $htmlLink,
               'NEWS_'.$placeholderPrefix.'_RELATED_MESSAGE_LINK_TARGET'   => $linkTarget,
               'NEWS_'.$placeholderPrefix.'_RELATED_MESSAGE_LINK_URL'      => contrexx_raw2xhtml($newsUrl),
               'NEWS_'.$placeholderPrefix.'_RELATED_MESSAGE_CATEGORY'      => contrexx_raw2xhtml(implode(', ', $newsCategories)),
// TODO: fetch typename through a newly to be created separate methode
               //'NEWS_'.$placeholderPrefix.'_RELATED_MESSAGE_TYPE'          => ($this->arrSettings['news_use_types'] == 1 ? stripslashes($objResult->fields['typename']) : ''),
               'NEWS_'.$placeholderPrefix.'_RELATED_MESSAGE_PUBLISHER'     => contrexx_raw2xhtml($publisher),
               'NEWS_'.$placeholderPrefix.'_RELATED_MESSAGE_AUTHOR'        => contrexx_raw2xhtml($author),
            ));

            if (!empty($image)) {
                $this->_objTpl->setVariable(array(
                    'NEWS_'.$placeholderPrefix.'_RELATED_MESSAGE_IMAGE'         => $image,
                    'NEWS_'.$placeholderPrefix.'_RELATED_MESSAGE_IMAGE_SRC'     => contrexx_raw2xhtml($imageSource),
                    'NEWS_'.$placeholderPrefix.'_RELATED_MESSAGE_IMAGE_ALT'     => contrexx_raw2xhtml($newstitle),
                    'NEWS_'.$placeholderPrefix.'_RELATED_MESSAGE_IMAGE_LINK'    => $htmlLinkImage,
                ));

                if ($this->_objTpl->blockExists($imageTemplateBlock)) {
                    $this->_objTpl->parse($imageTemplateBlock);
                }
            } else {
                if ($this->_objTpl->blockExists($imageTemplateBlock)) {
                    $this->_objTpl->hideBlock($imageTemplateBlock);
                }
            }

            $this->_objTpl->parse($messageTemplateBlock);
            $i++;
            $objResult->MoveNext();
        }

        $this->_objTpl->setVariable(array(
            'TXT_NEWS_COMMENTS'                                => $_ARRAYLANG['TXT_NEWS_COMMENTS'],
            'TXT_NEWS_DATE'                                    => $_ARRAYLANG['TXT_DATE'],
            'TXT_NEWS_MESSAGE'                                 => $_ARRAYLANG['TXT_NEWS_MESSAGE'],
            'TXT_NEWS_RELATED_MESSAGES_OF_'.$placeholderPrefix => $_ARRAYLANG['TXT_NEWS_RELATED_MESSAGES_OF_'.$placeholderPrefix],
        ));
        $this->_objTpl->parse($relationTemplateBlock);
    }

    /**
     * Gets the list with the headlines
     *
     * @param string $expirationDate Expiration date
     * @return string parsed content
     */
    private function getHeadlines(&$expirationDate = null) {
        global $_CONFIG, $objDatabase, $_ARRAYLANG, $_LANGID;

        // load source code if cmd value is integer
        if ($this->_objTpl->placeholderExists('APPLICATION_DATA')) {
            $page = new \Cx\Core\ContentManager\Model\Entity\Page();
            $page->setVirtual(true);
            $page->setType(\Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION);
            $page->setModule('News');
            // load source code
            $applicationTemplate = \Cx\Core\Core\Controller\Cx::getContentTemplateOfPage($page);
            \LinkGenerator::parseTemplate($applicationTemplate);
            $this->_objTpl->addBlock('APPLICATION_DATA', 'application_data', $applicationTemplate);
        }

        $validToShowList = true;
        $newsCategories  = array();
        $menuCategories  = array();
        $parameters         = array();
        $selectedCat        = '';
        $selectedType       = '';
        $selectedPublisher  = '';
        $selectedAuthor     = '';
        $newsfilter         = '';
        $paging             = '';
        $pos                = 0;
        $i                  = 0;

        if (isset($_GET['pos'])) {
            $pos = intval($_GET['pos']);
        }

        $catFromCmd = !empty($_REQUEST['cmd']) ? explode('-', $_REQUEST['cmd']) : array();
        $catFromReq = !empty($_REQUEST['category']) ? explode(',', $_REQUEST['category']) : array();

        if (!empty($catFromCmd)) {
            $menuCategories = $this->getCatIdsFromNestedSetArray($this->getNestedSetCategories($catFromCmd));
            if ($this->_objTpl->placeholderExists('NEWS_CMD')) {
                $this->_objTpl->setVariable('NEWS_CMD', $_REQUEST['cmd']);
            }
        }

        $newsCategories = $categories = !empty($catFromReq) ? $catFromReq : (!empty($menuCategories) ? $menuCategories : array());
        if ((count($newsCategories) == 1) && $this->categoryExists($newsCategories[0])) {
            $selectedCat = intval($newsCategories[0]);
        }
        if (empty($newsCategories)) {
            $newsCategories[] = $this->nestedSetRootId;
        }
        $newsCategories = $this->getCatIdsFromNestedSetArray($this->getNestedSetCategories($newsCategories));
        if (!empty($newsCategories)) {
            $newsfilter .= ' AND n.`id` IN (SELECT nc.`news_id` FROM `'.DBPREFIX.'module_news_rel_categories` AS nc WHERE nc.`category_id` IN (' . implode(',', $newsCategories) . '))';
        }

        if ($this->_objTpl->placeholderExists('NEWS_CAT_DROPDOWNMENU')) {
            $catMenu =  '<select onchange="this.form.submit()" name="category">'."\n";
            $catMenu .= '<option value="">'.$_ARRAYLANG['TXT_CATEGORY'].'</option>'."\n";
            $catMenu .= $this->getCategoryMenu(
                (!empty($menuCategories) ? $menuCategories : array()),
                array($selectedCat),
                array(),
                false,
                true,
                false
            )."\n";
            $catMenu .= '</select>'."\n";
            $this->_objTpl->setVariable('NEWS_CAT_DROPDOWNMENU', $catMenu);
        }

        //Filter by types
        if($this->arrSettings['news_use_types'] == 1) {
            if (!empty($_REQUEST['type'])) {
                $arrTypes = explode(',', $_REQUEST['type']);
                if (!empty($arrTypes)) {
                    $newsfilter .= ' AND (`n`.`typeid` IN (' . implode(', ', contrexx_input2int($arrTypes)) . '))';
                }
                $selectedType = current($arrTypes);
            }

            if ($this->_objTpl->placeholderExists('NEWS_TYPE_DROPDOWNMENU')) {
                $typeMenu    =  '<select onchange="this.form.submit()" name="type">'."\n";
                $typeMenu    .= '<option value="" selected="selected">'.$_ARRAYLANG['TXT_TYPE'].'</option>'."\n";
                $typeMenu    .= $this->getTypeMenu($selectedType)."\n";
                $typeMenu    .= '</select>'."\n";
                $this->_objTpl->setVariable('NEWS_TYPE_DROPDOWNMENU', $typeMenu);
            }
        }

        //Filter by publisher
        if (!empty($_REQUEST['publisher'])) {
            $parameters['filterPublisher'] = $publisher = contrexx_input2raw($_REQUEST['publisher']);
            $arrPublishers = explode(',', $publisher);
            if (!empty($arrPublishers)) {
                $newsfilter .= ' AND (`n`.`publisher_id` IN (' . implode(', ', contrexx_input2int($arrPublishers)) . '))';
            }
            $selectedPublisher = current($arrPublishers);

        }

        if ($this->_objTpl->placeholderExists('NEWS_PUBLISHER_DROPDOWNMENU')) {
            $publisherMenu    = '<select onchange="window.location=\''.\Cx\Core\Routing\Url::fromModuleAndCmd('News', intval($_REQUEST['cmd'])).'&amp;publisher=\'+this.value" name="publisher">'."\n";
            $publisherMenu   .= '<option value="" selected="selected">'.$_ARRAYLANG['TXT_NEWS_PUBLISHER'].'</option>'."\n";
            $publisherMenu   .= $this->getPublisherMenu($selectedPublisher, $selectedCat)."\n";
            $publisherMenu   .= '</select>'."\n";
            $this->_objTpl->setVariable('NEWS_PUBLISHER_DROPDOWNMENU', $publisherMenu);
        }

        //Filter by Author
        if (!empty($_REQUEST['author'])) {
            $parameters['filterAuthor'] = $author = contrexx_input2raw($_REQUEST['author']);
            $arrAuthors = explode(',', $author);
            if (!empty($arrAuthors)) {
                $newsfilter .= ' AND (`n`.`author_id` IN (' . implode(', ', contrexx_input2int($arrAuthors)) . '))';
            }
            $selectedAuthor = current($arrAuthors);
        }

        if ($this->_objTpl->placeholderExists('NEWS_AUTHOR_DROPDOWNMENU')) {
            $authorMenu    = '<select onchange="this.form.submit()" name="author">'."\n";
            $authorMenu   .= '<option value="" selected="selected">'.$_ARRAYLANG['TXT_NEWS_AUTHOR'].'</option>'."\n";
            $authorMenu   .= $this->getAuthorMenu($selectedAuthor)."\n";
            $authorMenu   .= '</select>'."\n";
            $this->_objTpl->setVariable('NEWS_AUTHOR_DROPDOWNMENU', $authorMenu);
        }
        
        if (!empty($this->arrSettings['news_use_tags'])) {
            \JS::registerCss('core_modules/News/View/Style/Tags.css');
        }

        //Filter by tag
        if (!empty($_REQUEST['tag'])) {
            $searchTag = is_array($_REQUEST['tag'])
                    ? contrexx_input2raw($_REQUEST['tag'])
                    : contrexx_input2raw(array($_REQUEST['tag']));
            $parameters['filterTag[]'] = implode('&filterTag[]=', $searchTag);
            $searchedTag   = $this->getNewsTags(null, $searchTag);
            if ($searchedTag['tagList']) {
                $requestedTagString = implode(' ', $searchedTag['tagList']);
                \Cx\Core\Core\Controller\Cx::instanciate()->getPage()->setMetaTitle($requestedTagString);
            }
            if (!empty($searchedTag['newsIds'])) {
                $this->incrementViewingCount(array_keys($searchedTag['tagList']));
                $newsfilter .= ' AND n.`id` IN ('
                    . implode(',', contrexx_raw2db($searchedTag['newsIds']))
                    . ')';
                foreach ($searchedTag['tagList'] as $tagId => $tagName) {
                    $this->_objTpl->setVariable(array(
                       'NEWS_TAG_FILTER_ID'   => contrexx_raw2xhtml($tagId),
                       'NEWS_TAG_FILTER_NAME' => ucfirst(contrexx_raw2xhtml($tagName))
                    ));
                    break;
                }
                if ($this->_objTpl->blockExists('news_tag_filter_container')) {
                    $this->_objTpl->parse('news_tag_filter_container');
                }
            } else {
                $validToShowList = false;
            }
        }

        $this->_objTpl->setVariable(array(
            'TXT_PERFORM'                   => $_ARRAYLANG['TXT_PERFORM'],
            'TXT_CATEGORY'                  => $_ARRAYLANG['TXT_CATEGORY'],
            'TXT_TYPE'                      => ($this->arrSettings['news_use_types'] == 1 ? $_ARRAYLANG['TXT_TYPE'] : ''),
            'TXT_DATE'                      => $_ARRAYLANG['TXT_DATE'],
            'TXT_TITLE'                     => $_ARRAYLANG['TXT_TITLE'],
            'TXT_NEWS_MESSAGE'              => $_ARRAYLANG['TXT_NEWS_MESSAGE'],
            'TXT_NEWS_HEADLINE'             => $_ARRAYLANG['TXT_NEWS_HEADLINE'],
        ));
        $this->_objTpl->setGlobalVariable(array(
            'TXT_NEWS_MORE'                 => $_ARRAYLANG['TXT_NEWS_MORE'],
            'TXT_NEWS_MORE_INFO'            => $_ARRAYLANG['TXT_NEWS_MORE_INFO'],
        ));

        $selectFields = ',
                                n.userid            AS newsuid,
                                n.date              AS newsdate,
                                n.typeid,
                                n.teaser_image_path,
                                n.teaser_image_thumbnail_path,
                                n.redirect,
                                n.publisher,
                                n.publisher_id,
                                n.author,
                                n.author_id,
                                n.allow_comments    AS commentactive,
                                n.redirect_new_window AS redirectNewWindow,
                                n.enable_tags,
                                n.changelog,
                                n.source,
                                n.url1,
                                n.url2,
                                nl.title            AS newstitle,
                                nl.text NOT REGEXP \'^(<br type="_moz" />)?$\' AS newscontent,
                                nl.text AS text,
                                nl.teaser_text
        ';
        $query = '  SELECT      n.id                AS newsid
                    %s
                    FROM        '.DBPREFIX.'module_news AS n
                    INNER JOIN  '.DBPREFIX.'module_news_locale AS nl ON nl.news_id = n.id
                    WHERE       status = 1
                                AND nl.is_active=1
                                AND nl.lang_id='.FRONTEND_LANG_ID.'
                                AND (n.startdate<=\''.date('Y-m-d H:i:s').'\' OR n.startdate="0000-00-00 00:00:00")
                                AND (n.enddate>=\''.date('Y-m-d H:i:s').'\' OR n.enddate="0000-00-00 00:00:00")
                                '.$newsfilter
                               .($this->arrSettings['news_message_protection'] == '1' && !\Permission::hasAllAccess() ? (
                                    ($objFWUser = \FWUser::getFWUserObject()) && $objFWUser->objUser->login() ?
                                        " AND (frontend_access_id IN (".implode(',', array_merge(array(0), $objFWUser->objUser->getDynamicPermissionIds())).") OR userid = ".$objFWUser->objUser->getId().") "
                                        :   " AND frontend_access_id=0 ")
                                    :   '');

        /***start paging ****/
        $objResult = $objDatabase->Execute(sprintf($query, ''));
        $count = $objResult->RecordCount();

        $category = '';
        if (!empty($_REQUEST['cmd'])) {
            $parameters['filterCategory'] = contrexx_input2raw($_REQUEST['cmd']);
            $category .= '&cmd='.$_REQUEST['cmd'];
        }
        if (!empty($_REQUEST['category'])) {
            $parameters['filterCategory'] = contrexx_input2raw($_REQUEST['category']);
            $category .= '&category='.$_REQUEST['category'];
        }

        $type = '';
        if (!empty($_REQUEST['type'])) {
            $parameters['filterType'] = contrexx_input2raw($_REQUEST['type']);
            $type = '&type='.$selectedType;
        }

        if ($count>intval($_CONFIG['corePagingLimit'])) {
            $paging = getPaging($count, $pos, '&section=News'.$category.$type, $_ARRAYLANG['TXT_NEWS_MESSAGES'], true);
        }
        $this->_objTpl->setVariable('NEWS_PAGING', $paging);
        $expirationDate = $this->getExpirationDate();
        $objResult = $objDatabase->SelectLimit(sprintf($query, $selectFields).' ORDER BY newsdate DESC', $_CONFIG['corePagingLimit'], $pos);
        /*** end paging ***/
        if (    $count>=1
            &&  $validToShowList
        ) {
            while (!$objResult->EOF) {
                $newsid = $parameters['newsid'] = $objResult->fields['newsid'];
                $arrNewsCategories = $this->getCategoriesByNewsId(
                    $newsid,
                    $categories
                );
                $newsUrl        = empty($objResult->fields['redirect'])
                                    ? (empty($objResult->fields['newscontent'])
                                        ? ''
                                        : \Cx\Core\Routing\Url::fromModuleAndCmd(
                                                    'News',
                                                    $this->findCmdById('details', self::sortCategoryIdByPriorityId(array_keys($arrNewsCategories), $categories)),
                                                    FRONTEND_LANG_ID,
                                                    $parameters
                                                )
                                    )
                                    : $objResult->fields['redirect'];

                // Parse all the news placeholders
                $this->parseNewsPlaceholders(
                    $this->_objTpl,
                    $objResult,
                    $newsUrl,
                    '',
                    $categories
                );

                $this->_objTpl->setVariable(array(
                   'NEWS_CSS'            => 'row'.($i % 2 + 1),
                ));

                $this->_objTpl->parse('newsrow');
                $i++;
                $objResult->MoveNext();
            }
            if ($this->_objTpl->blockExists('news_list')) {
                $this->_objTpl->parse('news_list');
            }
            if ($this->_objTpl->blockExists('news_menu')) {
                $this->_objTpl->parse('news_menu');
            }
            if ($this->_objTpl->blockExists('news_status_message')) {
                $this->_objTpl->hideBlock('news_status_message');
            }
        } else {
            $this->_objTpl->setVariable('TXT_NEWS_NO_NEWS_FOUND', $_ARRAYLANG['TXT_NEWS_NO_NEWS_FOUND']);

            if ($this->_objTpl->blockExists('news_status_message')) {
                $this->_objTpl->parse('news_status_message');
            }
            if ($this->_objTpl->blockExists('news_menu')) {
                $this->_objTpl->parse('news_menu');
            }
            if ($this->_objTpl->blockExists('news_list')) {
                $this->_objTpl->hideBlock('news_list');
            }
        }
        return $this->_objTpl->get();
    }


    private function listNews($type)
    {
// TODO: create a method that can be used to parse the message-list of the methods news::getTopNews(), news::getHeadlines()
/*
        switch($type) {
            case 'topnews':
                $order = '  ORDER BY (SELECT COUNT(*)
                            FROM `'.DBPREFIX.'module_news_stats_view`
                            WHERE   `news_id`=n.`id` AND
                                    `time` > "'.date_format(date_sub(date_create('now'), date_interval_create_from_date_string(intval($this->arrSettings['news_top_days']).' days')), 'Y-m-d H:i:s').'" DESC';
                break;

            case 'archive':

            case 'headlines':
            default:
                $order = 'ORDER BY `date` DESC';
                break;
        }

        $accessRestriction = '';
        if (   $this->arrSettings['news_message_protection'] == '1'
            && !\Permission::hasAllAccess()
        ) {
            if (   ($objFWUser = \FWUser::getFWUserObject())
                && $objFWUser->objUser->login()
            ) {
                $accessRestriction = " AND (frontend_access_id IN (".implode(',', array_merge(array(0), $objFWUser->objUser->getDynamicPermissionIds())).") OR userid = ".$objFWUser->objUser->getId().") ";
            } else {
                $accessRestriction = " AND frontend_access_id=0 ";
            }
        }

        $query = '  SELECT      tblNews.id                AS news_id,
                                tblNews.userid            AS news_user_id,
                                tblNews.date              AS news_date,
                                tblNews.teaser_image_path   AS news_teaser_image_path,
                                tblNews.teaser_image_thumbnail_path AS news_teaser_image_thumbnail_path,
                                tblNews.redirect    AS news_redirect,
                                tblNews.publisher   AS news_publisher,
                                tblNews.publisher_id    AS news_publisher_id,
                                tblNews.author  AS news_author,
                                tblNews.author_id   AS news_author_id,

                                tblNewsLocale.title            AS news_title,
                                tblNewsLocale.text             AS news_text,
                                tblNewsLocale.teaser_text       AS news_teaser_text,

                                tblCategoryLocale.name             AS category_name,
                                tblTypeLocale.name            AS type_name

                          FROM '.DBPREFIX.'module_news AS tblNews

                    INNER JOIN '.DBPREFIX.'module_news_locale AS tblNewsLocale
                            ON tblNewsLocale.news_id = tblNews.id

                    INNER JOIN '.DBPREFIX.'module_news_categories_locale AS tblCategoryLocale
                            ON tblCategoryLocale.category_id = tblNews.catid

                    LEFT JOIN '.DBPREFIX.'module_news_types_locale AS tblTypeLocale
                            ON tblTypeLocale.type_id = tblNews.typeid

                    WHERE       tblNews.status = 1
                                AND tblNewsLocale.lang_id = '.FRONTEND_LANG_ID.'
                                AND tblCategoryLocale.lang_id = '.FRONTEND_LANG_ID.'
                                AND tblTypeLocale.lang_id = '.FRONTEND_LANG_ID.'
                                AND (tblNews.startdate <= \''.date('Y-m-d H:i:s').'\' OR tblNews.startdate="0000-00-00 00:00:00")
                                AND (tblNews.enddate >= \''.date('Y-m-d H:i:s').'\' OR tblNews.enddate="0000-00-00 00:00:00")
                                '.$newsfilter
                                $accessRestriction
                    .'ORDER BY    newsdate DESC';



*/
    }


    /**
    * Gets the list with the top news
    *
    * @param string $expirationDate Expiration date
    * @return string parsed content
    */
    private function getTopNews(&$expirationDate = null) {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;

        $newsfilter = '';
        $paging     = '';
        $pos        = 0;
        $i          = 0;

        if (isset($_GET['pos'])) {
            $pos = intval($_GET['pos']);
        }

        $this->_objTpl->setVariable(array(
            'TXT_DATE'              => $_ARRAYLANG['TXT_DATE'],
            'TXT_TITLE'             => $_ARRAYLANG['TXT_TITLE'],
            'TXT_NEWS_MESSAGE'      => $_ARRAYLANG['TXT_NEWS_MESSAGE'],
            'TXT_NEWS_HEADLINE'     => $_ARRAYLANG['TXT_NEWS_HEADLINE'],
        ));
        $this->_objTpl->setGlobalVariable(array(
            'TXT_NEWS_MORE'         => $_ARRAYLANG['TXT_NEWS_MORE'],
            'TXT_NEWS_MORE_INFO'    => $_ARRAYLANG['TXT_NEWS_MORE_INFO'],
        ));

        $query = '  SELECT      n.id                AS newsid,
                                n.userid            AS newsuid,
                                n.date              AS newsdate,
                                n.teaser_image_path,
                                n.teaser_image_thumbnail_path,
                                n.redirect,
                                n.redirect_new_window AS redirectNewWindow,
                                n.publisher,
                                n.publisher_id,
                                n.author,
                                n.author_id,
                                nl.title            AS newstitle,
                                nl.text NOT REGEXP \'^(<br type="_moz" />)?$\' AS newscontent,
                                nl.teaser_text
                    FROM        '.DBPREFIX.'module_news AS n
                    INNER JOIN  '.DBPREFIX.'module_news_locale AS nl ON nl.news_id = n.id
                    WHERE       status = 1
                                AND nl.is_active=1
                                AND nl.lang_id='.FRONTEND_LANG_ID.'
                                AND (n.startdate<=\''.date('Y-m-d H:i:s').'\' OR n.startdate="0000-00-00 00:00:00")
                                AND (n.enddate>=\''.date('Y-m-d H:i:s').'\' OR n.enddate="0000-00-00 00:00:00")
                                '.$newsfilter
                               .($this->arrSettings['news_message_protection'] == '1' && !\Permission::hasAllAccess() ? (
                                    ($objFWUser = \FWUser::getFWUserObject()) && $objFWUser->objUser->login() ?
                                        " AND (frontend_access_id IN (".implode(',', array_merge(array(0), $objFWUser->objUser->getDynamicPermissionIds())).") OR userid = ".$objFWUser->objUser->getId().") "
                                        :   " AND frontend_access_id=0 ")
                                    :   '')
                    .'ORDER BY (SELECT COUNT(*) FROM '.DBPREFIX.'module_news_stats_view WHERE news_id=n.id AND time>"'.date_format(date_sub(date_create('now'), date_interval_create_from_date_string(intval($this->arrSettings['news_top_days']).' day')), 'Y-m-d H:i:s').'") DESC';

        /***start paging ****/
        $objResult = $objDatabase->Execute($query);
        $count = $objResult->RecordCount();
        if ($count>intval($_CONFIG['corePagingLimit'])) {
            $paging = getPaging($count, $pos, '&section=News&cmd=topnews', $_ARRAYLANG['TXT_NEWS_MESSAGES'], true);
        }
        $this->_objTpl->setVariable('NEWS_PAGING', $paging);
        $expirationDate = $this->getExpirationDate();
        $objResult = $objDatabase->SelectLimit($query, $_CONFIG['corePagingLimit'], $pos);
        /*** end paging ***/

        if ($count>=1) {
            while (!$objResult->EOF) {
                $newsid         = $objResult->fields['newsid'];
                $newstitle      = $objResult->fields['newstitle'];
                $newsCategories = $this->getCategoriesByNewsId($newsid);
                $newsUrl        = empty($objResult->fields['redirect'])
                                    ? (empty($objResult->fields['newscontent'])
                                        ? ''
                                        : \Cx\Core\Routing\Url::fromModuleAndCmd('News', $this->findCmdById('details', array_keys($newsCategories)), FRONTEND_LANG_ID, array('newsid' => $newsid)))
                                    : $objResult->fields['redirect'];

                $redirectNewWindow = !empty($objResult->fields['redirect']) && !empty($objResult->fields['redirectNewWindow']);
                $htmlLink = self::parseLink($newsUrl, $newstitle, contrexx_raw2xhtml('[' . $_ARRAYLANG['TXT_NEWS_MORE'] . '...]'), $redirectNewWindow);
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
                $author = \FWUser::getParsedUserTitle($objResult->fields['author_id'], $objResult->fields['author']);
                $publisher = \FWUser::getParsedUserTitle($objResult->fields['publisher_id'], $objResult->fields['publisher']);

                $this->_objTpl->setVariable(array(
                   'NEWS_ID'            => $newsid,
                   'NEWS_CSS'           => 'row'.($i % 2 + 1),
                   'NEWS_TEASER'        => $this->arrSettings['news_use_teaser_text'] ? nl2br($objResult->fields['teaser_text']) : '',
                   'NEWS_TITLE'         => contrexx_raw2xhtml($newstitle),
                   'NEWS_LONG_DATE'     => date(ASCMS_DATE_FORMAT,$objResult->fields['newsdate']),
                   'NEWS_DATE'          => date(ASCMS_DATE_FORMAT_DATE, $objResult->fields['newsdate']),
                   'NEWS_TIME'          => date(ASCMS_DATE_FORMAT_TIME, $objResult->fields['newsdate']),
                   'NEWS_TIMESTAMP'     => $objResult->fields['newsdate'],
                   'NEWS_LINK_TITLE'    => $htmlLinkTitle,
                   'NEWS_LINK'          => $htmlLink,
                   'NEWS_LINK_TARGET'   => $linkTarget,
                   'NEWS_LINK_URL'      => contrexx_raw2xhtml($newsUrl),
                   'NEWS_CATEGORY'      => implode(', ', contrexx_raw2xhtml($newsCategories)),
// TODO: fetch typename from a newly to be created separate methode
                   //'NEWS_TYPE'          => ($this->arrSettings['news_use_types'] == 1 ? stripslashes($objResult->fields['typename']) : ''),
                   'NEWS_PUBLISHER'     => contrexx_raw2xhtml($publisher),
                   'NEWS_AUTHOR'        => contrexx_raw2xhtml($author),
                ));

                if (!empty($image)) {
                    $this->_objTpl->setVariable(array(
                        'NEWS_IMAGE_ID'            => $newsid,
                        'NEWS_IMAGE'               => $image,
                        'NEWS_IMAGE_SRC'           => contrexx_raw2xhtml($imageSource),
                        'NEWS_IMAGE_ALT'           => contrexx_raw2xhtml($newstitle),
                        'NEWS_IMAGE_LINK'          => $htmlLinkImage,
                        'NEWS_IMAGE_LINK_URL'      => contrexx_raw2xhtml($newsUrl),
                    ));

                    if ($this->_objTpl->blockExists('news_image')) {
                        $this->_objTpl->parse('news_image');
                    }
                    if ($this->_objTpl->blockExists('news_no_image')) {
                        $this->_objTpl->hideBlock('news_no_image');
                    }
                } else {
                    if ($this->_objTpl->blockExists('news_image')) {
                        $this->_objTpl->hideBlock('news_image');
                    }
                    if ($this->_objTpl->blockExists('news_no_image')) {
                        $this->_objTpl->touchBlock('news_no_image');
                    }
                }

                self::parseImageBlock($this->_objTpl, $objResult->fields['teaser_image_thumbnail_path'], $newstitle, $newsUrl, 'image_thumbnail');
                self::parseImageBlock($this->_objTpl, $objResult->fields['teaser_image_path'], $newstitle, $newsUrl, 'image_detail');

                $this->_objTpl->parse('newsrow');
                $i++;
                $objResult->MoveNext();
            }
            if ($this->_objTpl->blockExists('news_list')) {
                $this->_objTpl->parse('news_list');
            }
            if ($this->_objTpl->blockExists('news_menu')) {
                $this->_objTpl->parse('news_menu');
            }
            if ($this->_objTpl->blockExists('news_status_message')) {
                $this->_objTpl->hideBlock('news_status_message');
            }
        } else {
            $this->_objTpl->setVariable('TXT_NEWS_NO_NEWS_FOUND', $_ARRAYLANG['TXT_NEWS_NO_NEWS_FOUND']);

            if ($this->_objTpl->blockExists('news_status_message')) {
                $this->_objTpl->parse('news_status_message');
            }
            if ($this->_objTpl->blockExists('news_menu')) {
                $this->_objTpl->parse('news_menu');
            }
            if ($this->_objTpl->blockExists('news_list')) {
                $this->_objTpl->hideBlock('news_list');
            }
        }

        return $this->_objTpl->get();
    }


    private function notifyWebmasterAboutNewlySubmittedNewsMessage($news_id)
    {
        $user_id  = intval($this->arrSettings['news_notify_user']);
        $group_id = intval($this->arrSettings['news_notify_group']);
        $users_in_group = array();

        if ($group_id > 0) {
            $objFWUser = \FWUser::getFWUserObject();

            if ($objGroup = $objFWUser->objGroup->getGroup($group_id)) {
                $users_in_group = $objGroup->getAssociatedUserIds();
            }
        }

        if ($user_id > 0) {
            $users_in_group[] = $user_id;
        }

        // Now we have fetched all user IDs that
        // are to be notified. Now send those emails!
        foreach ($users_in_group as $user_id) {
            $this->sendNotificationEmailAboutNewlySubmittedNewsMessage($user_id, $news_id);
        }
    }

    private function sendNotificationEmailAboutNewlySubmittedNewsMessage($user_id, $news_id)
    {
        global $_ARRAYLANG, $_CONFIG;
        // First, load recipient infos.
        $objFWUser = \FWUser::getFWUserObject();
        $objUser = $objFWUser->objUser->getUser($user_id);

        if (!$objUser) {
            return false;
        }

        $name = \FWUser::getParsedUserTitle($objUser);

        $msg  = $_ARRAYLANG['TXT_NOTIFY_ADDRESS'] . " $name\n\n";
        // Split the message text into lines
        $words = preg_split('/\s+/s', $_ARRAYLANG['TXT_NOTIFY_MESSAGE']);
        $line = '';
        for ($idx = 0; $idx < sizeof($words); $idx++) {
            if (strlen($line . ' ' . $words[$idx]) < 80) {
                // Line not full yet
                if ($line) $line .= ' ' . $words[$idx];
                else       $line =        $words[$idx];
            }
            else {
                // Line is full. add to message and empty.
                $msg .= "$line\n";
                $line = $words[$idx];
            }
        }

        $newsEditLink = \Cx\Core\Routing\Url::fromDocumentRoot(array(
            'cmd' => 'News',
            'act' => 'edit',
            'newsId' => $news_id,
            'validate' => 'true',
        ));
        $newsEditLink->setPath(
            substr(
                \Cx\Core\Core\Controller\Cx::instanciate()->getBackendFolderName(),
                1
            ) .
            '/index.php'
        );
        $newsEditLink->setMode('backend');

        $msg .= "$line\n";
        $msg .= ' ' . $newsEditLink->toString();
        $msg .= "\n\n";
        $msg .= $_CONFIG['coreAdminName'];

        $objMail = new \Cx\Core\MailTemplate\Model\Entity\Mail();

        $objMail->SetFrom($_CONFIG['coreAdminEmail'], $_CONFIG['coreAdminName']);
        $objMail->Subject = $_ARRAYLANG['TXT_NOTIFY_SUBJECT'];
        $objMail->IsHTML(false);
        $objMail->Body = $msg;

        $objMail->AddAddress($objUser->getEmail(), $name);
        $objMail->Send();

        return true;
    }

    /**
    * Get the submit page
    *
    * Get the submit, login or the noaccess page depending on the configuration
    *
    * @global array
    * @global ADONewConnection
    * @see \Cx\Core\Html\Sigma::setTemplate(), ComponentManager::getModules(), Permission::checkAccess()
    * @return string content
    */
    private function _submit()
    {
        global $_ARRAYLANG;

        // redirect to the news overview page in case the submit function has been disabled
        if (!$this->arrSettings['news_submit_news'] == '1') {
            header('Location: '.\Cx\Core\Routing\Url::fromModuleAndCmd('News'));
            exit;
        }

        // check if the currently logged in user is allowed to submit a news message,
        // in case anonymous submitting has been disabled
        if ($this->arrSettings['news_submit_only_community'] == '1') {
            $objFWUser = \FWUser::getFWUserObject();
            if (!$objFWUser->objUser->login()) {
                $link = base64_encode(CONTREXX_DIRECTORY_INDEX.'?'.$_SERVER['QUERY_STRING']);
                header('Location: '.\Cx\Core\Routing\Url::fromModuleAndCmd('Login', '', FRONTEND_LANG_ID, array('redirect' => $link)));
                exit;
            }

            if (!\Permission::checkAccess(61, 'static')) {
                header('Location: '.\Cx\Core\Routing\Url::fromModuleAndCmd('Login', 'noaccess', FRONTEND_LANG_ID));
                exit;
            }
        }

        $newsId = false;
        $msg = '';

        // fetch submitted news message
        list($hasMessageBeenSubmitted, $data) = $this->fetchSubmittedData();
        if ($hasMessageBeenSubmitted) {
            // try to add the submitted news message
            list($newsId, $msg) = $this->storeSubmittedNewsMessage($data);

            if ($newsId) {
                // lets notify the webmaster about the newly submitted message
                $this->notifyWebmasterAboutNewlySubmittedNewsMessage($newsId);

                // show status message about successfully submitted message
                if ($this->_objTpl->blockExists('news_submitted')) {
                    $this->_objTpl->touchBlock('news_submitted');
                }
            }
        }

        // register code for redirect type
        \JS::activate('cx');
        $jsCode = <<<JSCODE
cx.ready(function () {
    if (\$J('.newsTypeRedirect').length > 0) {
        \$J('.newsTypeRedirect').change(function () {
            if (\$J(this).val() == 1) {
                \$J('.newsRedirect').show();
                \$J('.newsRedirectNewWindow').show();
                \$J('.newsContent').hide();
            } else {
                \$J('.newsContent').show();
                \$J('.newsRedirect').hide();
                \$J('.newsRedirectNewWindow').hide();
            }
        });
        if (\$J('input[type=reset]').length > 0) {
            \$J('input[type=reset]').click(function () {
                \$J('.newsContent').show();
                \$J('.newsRedirect').hide();
                \$J('.newsRedirectNewWindow').hide();
            });
        }
    }
});
JSCODE;
        \JS::registerCode($jsCode);

        // set $display to false in case we just added a newly submitted message.
        // setting $display to false will hide the submit form.
        $this->showSubmitForm($data, $display = !$newsId);

        $this->_objTpl->setGlobalVariable(
            array(
                'NEWS_STATUS_MESSAGE' => $msg,
                'NEWS_STATUS_MESSAGE_CSS_CLASS' => $newsId ? 'text-success' : 'text-danger',
                'TXT_CATEGORY_SELECT' => $_ARRAYLANG['TXT_CATEGORY_SELECT']
            )
        );
        return $this->_objTpl->get();
    }

    private function fetchSubmittedData()
    {
        // set default values
        $data['newsText'] = '';
        $data['newsTeaserText'] = '';
        $data['newsTitle'] = '';
        $data['newsRedirect'] = 'http://';
        $data['newsSource'] = 'http://';
        $data['newsUrl1'] = 'http://';
        $data['newsUrl2'] = 'http://';
        $data['newsCat'] = '';
        $data['newsType'] = '';
        $data['newsTypeRedirect'] = 0;
        $data['redirectNewWindow'] = 0;

        if (!isset($_POST['submitNews'])) {
            return array(false, $data);
        }

        $objValidator = new \FWValidator();

        // set POST data
        $data['newsTitle'] = contrexx_input2raw(html_entity_decode($_POST['newsTitle'], ENT_QUOTES, CONTREXX_CHARSET));
        $data['newsTeaserText'] = contrexx_input2raw(html_entity_decode($_POST['newsTeaserText'], ENT_QUOTES, CONTREXX_CHARSET));
        $data['newsRedirect'] = $objValidator->getUrl(contrexx_input2raw(html_entity_decode($_POST['newsRedirect'], ENT_QUOTES, CONTREXX_CHARSET)));
        $data['newsText'] = contrexx_remove_script_tags($this->filterBodyTag(contrexx_input2raw(html_entity_decode($_POST['newsText'], ENT_QUOTES, CONTREXX_CHARSET))));
        $data['newsSource'] = $objValidator->getUrl(contrexx_input2raw(html_entity_decode($_POST['newsSource'], ENT_QUOTES, CONTREXX_CHARSET)));
        $data['newsUrl1'] = $objValidator->getUrl(contrexx_input2raw(html_entity_decode($_POST['newsUrl1'], ENT_QUOTES, CONTREXX_CHARSET)));
        $data['newsUrl2'] = $objValidator->getUrl(contrexx_input2raw(html_entity_decode($_POST['newsUrl2'], ENT_QUOTES, CONTREXX_CHARSET)));
        $data['newsCat']  = !empty($_POST['newsCat']) ? contrexx_input2raw($_POST['newsCat']) : array();
        $data['newsType'] = !empty($_POST['newsType']) ? intval($_POST['newsType']) : 0;
        $data['newsTypeRedirect'] = !empty($_POST['newsTypeRedirect']) ? true : false;
        $data['newsEnableRelatedNews'] = intval(!empty($this->arrSettings['use_related_news']));
        $data['newsRelatedNews'] = !empty($_POST['newsRelatedNews'])
            ? contrexx_input2raw($_POST['newsRelatedNews'])
            : array();
        $data['enableTags'] = !empty($this->arrSettings['news_use_tags']) ? 1 : 0;
        $data['newsTags'] = !empty($_POST['newsTags'])
            ? contrexx_input2raw($_POST['newsTags'])
            : array();
        $data['redirectNewWindow'] = !empty($_POST['redirect_new_window']);

        return array(true, $data);
    }

    private function showSubmitForm($data, $display)
    {
        global $_ARRAYLANG, $_CORELANG;

        if (!$display) {
            if ($this->_objTpl->blockExists('news_submit_form')) {
                $this->_objTpl->hideBlock('news_submit_form');
            }
            return;
        }

        $newsTagId = 'newsTags';
        if ($this->_objTpl->blockExists('news_tags_container')) {
            if (!empty($this->arrSettings['news_use_tags'])) {
                \JS::activate('tag-it');
                \JS::registerCss('core_modules/News/View/Style/Tags.css');
                $this->registerTagJsCode();
                if (    $this->_objTpl->blockExists('news_tags')
                    &&  !empty($data['newsTags'])
                ) {
                    foreach ($data['newsTags'] as $newsTag) {
                        $this->_objTpl->setVariable(array(
                            'NEWS_TAGS' => contrexx_raw2xhtml($newsTag)
                        ));
                        $this->_objTpl->parse('news_tags');
                    }
                }
                $this->_objTpl->touchBlock('news_tags_container');
            } else {
                $this->_objTpl->hideBlock('news_tags_container');
            }
        }

        \JS::activate('chosen');
        $jsCodeCategoryChosen = <<< EOF
\$J(document).ready(function() {
                \$J('#newsCat').chosen();
});
EOF;
        \JS::registerCode($jsCodeCategoryChosen);
        if ($this->_objTpl->blockExists('news_related_news_container')) {
            if (
                !$this->arrSettings['use_related_news'] ||
                !$this->_objTpl->blockExists('news_related_news')
            ) {
                $this->_objTpl->hideBlock('news_related_news_container');
            } else {
                \ContrexxJavascript::getInstance()->setVariable(
                    array(
                        'noResultsMsg' => $_ARRAYLANG['TXT_NEWS_NOT_FOUND'],
                        'langId' => FRONTEND_LANG_ID,
                    ),
                    'News/RelatedSearch'
                );
                \JS::registerJS('core_modules/News/View/Script/RelatedSearch.js');
                \JS::registerCss('core_modules/News/View/Style/RelatedSearch.css');
                if (!empty($data['newsEnableRelatedNews'])) {
                    try {
                        $relatedNews = $this->getRelatedNews(0, $data['newsRelatedNews'], false);
                        // parse related news
                        while (!$relatedNews->EOF) {
                            $relatedNewsTitle = $relatedNews->fields['newstitle'];
                            $relatedNewsTitleShort = strip_tags($relatedNewsTitle);
                            if (strlen($relatedNewsTitleShort) > 35) {
                                $relatedNewsTitleShort = substr($relatedNewsTitleShort, 0, 35) . '...';
                            }
                            $this->_objTpl->setVariable(
                                array(
                                    'NEWS_RELATED_NEWS_ID'          => $relatedNews->fields['newsid'],
                                    'NEWS_RELATED_NEWS_TITLE'       => contrexx_raw2xhtml($relatedNewsTitle),
                                    'NEWS_RELATED_NEWS_TITLE_SHORT' => contrexx_raw2xhtml($relatedNewsTitleShort),
                                )
                            );
                            $relatedNews->MoveNext();
                            $this->_objTpl->parse('news_related_news');
                        }
                    } catch (NewsLibraryException $e) {
                        $this->_objTpl->hideBlock('news_related_new');
                    }
                } else {
                    $this->_objTpl->hideBlock('news_related_new');
                }

                $this->_objTpl->touchBlock('news_related_news_container');
            }
        }

        $this->_objTpl->setVariable(array(
            'TXT_NEWS_MESSAGE'          => $_ARRAYLANG['TXT_NEWS_MESSAGE'],
            'TXT_TITLE'                 => $_ARRAYLANG['TXT_TITLE'],
            'TXT_CATEGORY'              => $_ARRAYLANG['TXT_CATEGORY'],
            'TXT_TYPE'                  => ($this->arrSettings['news_use_types'] == 1 ? $_ARRAYLANG['TXT_TYPE'] : ''),
            'TXT_HYPERLINKS'            => $_ARRAYLANG['TXT_HYPERLINKS'],
            'TXT_EXTERNAL_SOURCE'       => $_ARRAYLANG['TXT_EXTERNAL_SOURCE'],
            'TXT_LINK'                  => $_ARRAYLANG['TXT_LINK'],
            'TXT_NEWS_REDIRECT_LABEL'   => $_ARRAYLANG['TXT_NEWS_REDIRECT_LABEL'],
            'TXT_NEWS_NEWS_CONTENT'     => $_ARRAYLANG['TXT_NEWS_NEWS_CONTENT'],
            'TXT_NEWS_TEASER_TEXT'      => $_ARRAYLANG['TXT_NEWS_TEASER_TEXT'],
            'TXT_SUBMIT_NEWS'           => $_ARRAYLANG['TXT_SUBMIT_NEWS'],
            'TXT_NEWS_REDIRECT'         => $_ARRAYLANG['TXT_NEWS_REDIRECT'],
            'TXT_NEWS_REDIRECT_NEW_WINDOW'       => $_ARRAYLANG['TXT_NEWS_REDIRECT_NEW_WINDOW'],
            'TXT_NEWS_REDIRECT_NEW_WINDOW_HELP'  => $_ARRAYLANG['TXT_NEWS_REDIRECT_NEW_WINDOW_HELP'],
            'TXT_NEWS_NEWS_URL'         => $_ARRAYLANG['TXT_NEWS_NEWS_URL'],
            'TXT_TYPE'                  => $_ARRAYLANG['TXT_TYPE'],
            'TXT_NEWS_INCLUDE_NEWS'              => $_ARRAYLANG['TXT_NEWS_INCLUDE_NEWS'],
            'TXT_NEWS_INCLUDE_RELATED_NEWS_DESC' => $_ARRAYLANG['TXT_NEWS_INCLUDE_RELATED_NEWS_DESC'],
            'TXT_NEWS_SEARCH_INFO'          => $_ARRAYLANG['TXT_NEWS_SEARCH_INFO'],
            'TXT_NEWS_SEARCH_PLACEHOLDER'   => $_ARRAYLANG['TXT_NEWS_SEARCH_PLACEHOLDER'],
            'TXT_NEWS_TAGS'                 => $_ARRAYLANG['TXT_NEWS_TAGS'],
            'NEWS_TEXT'                 => new \Cx\Core\Wysiwyg\Wysiwyg('newsText', $data['newsText'], 'bbcode'),
            'NEWS_CAT_MENU'             => $this->getCategoryMenu(
                $this->nestedSetRootId,
                array($data['newsCat']),
                array(),
                false,
                true,
                false
            ),
            'NEWS_TYPE_MENU'            => ($this->arrSettings['news_use_types'] == 1 ? $this->getTypeMenu($data['newsType']) : ''),
            'NEWS_TITLE'                => contrexx_raw2xhtml($data['newsTitle']),
            'NEWS_SOURCE'               => contrexx_raw2xhtml($data['newsSource']),
            'NEWS_URL1'                 => contrexx_raw2xhtml($data['newsUrl1']),
            'NEWS_URL2'                 => contrexx_raw2xhtml($data['newsUrl2']),
            'NEWS_TEASER_TEXT'          => contrexx_raw2xhtml($data['newsTeaserText']),
            'NEWS_REDIRECT'             => contrexx_raw2xhtml($data['newsRedirect']),
            'NEWS_TAG_ID'               => $newsTagId
        ));

        if ($this->arrSettings['news_use_teaser_text'] != '1' && $this->_objTpl->blockExists('news_use_teaser_text')) {
            $this->_objTpl->hideBlock('news_use_teaser_text');
        }

        if (\FWUser::getFWUserObject()->objUser->login()) {
            if ($this->_objTpl->blockExists('news_submit_form_captcha')) {
                $this->_objTpl->hideBlock('news_submit_form_captcha');
            }
        } else {
            $this->_objTpl->setVariable(array(
                'TXT_NEWS_CAPTCHA'          => $_CORELANG['TXT_CORE_CAPTCHA'],
                'NEWS_CAPTCHA_CODE'         => \Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->getCode(),
            ));
            if ($this->_objTpl->blockExists('news_submit_form_captcha')) {
                $this->_objTpl->parse('news_submit_form_captcha');
            }
        }

        $this->parseCategoryMenu();
        $this->parseNewsTypeMenu();


        if ($this->_objTpl->blockExists('news_submit_form')) {
            $this->_objTpl->parse('news_submit_form');
        }
    }

    private function parseCategoryMenu()
    {
        global $objDatabase;

        if (!$this->_objTpl->blockExists('news_category_menu')) {
            return;
        }

        $objResult = $objDatabase->Execute('SELECT category_id as catid, name FROM '.DBPREFIX.'module_news_categories_locale WHERE lang_id='.FRONTEND_LANG_ID.' ORDER BY name asc');

        if (!$objResult) {
            return;
        }

        while (!$objResult->EOF) {
            $this->_objTpl->setVariable(array(
                'NEWS_CATEGORY_ID'      => $objResult->fields['catid'],
                'NEWS_CATEGORY_TITLE'   => contrexx_raw2xhtml($objResult->fields['name'])
            ));
            $this->_objTpl->parse('news_category_menu');
            $objResult->MoveNext();
        }
    }

    private function parseNewsTypeMenu()
    {
        global $objDatabase;

        if (   !$this->_objTpl->blockExists('news_type_menu')
            || !$this->arrSettings['news_use_types'] == 1
        ) {
            return;
        }

        $objResult = $objDatabase->Execute('SELECT type_id as typeid, name FROM '.DBPREFIX.'module_news_types_locale WHERE lang_id='.FRONTEND_LANG_ID.' ORDER BY name asc');
        if (!$objResult) {
            return;
        }

        while (!$objResult->EOF) {
            $this->_objTpl->setVariable(array(
                'NEWS_TYPE_ID'          => $objResult->fields['typeid'],
                'NEWS_TYPE_TITLE'       => contrexx_raw2xhtml($objResult->fields['name'])

            ));
            $this->_objTpl->parse('news_type_menu');
            $objResult->MoveNext();
        }
    }


    /**
    * Insert a new news message
    * @param    array   Data of news message to store
    * @global   ADONewConnection
    * @global   array
    * @return   array Index 0: true on success - false on failure
    *                 Index 1: status message
    */
    private function storeSubmittedNewsMessage($data)
    {
        global $objDatabase, $_ARRAYLANG;

        $error = '';
        $status = true;

        if (   !\FWUser::getFWUserObject()->objUser->login()
            && !\Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->check()) {
            $status = false;
            $error = $_ARRAYLANG['TXT_CAPTCHA_ERROR'] . '<br />';
        }

        if ((isset($data['newsTypeRedirect'])
            && !$data['newsTypeRedirect'])
            || $data['newsRedirect'] == 'http://') {
            $data['newsRedirect'] = '';
        }

        // check if all mandadory data had been set (title and text or redirect)
        if (   empty($data['newsTitle'])
            ||  empty($data['newsCat'])
            || (  (   empty($data['newsText'])
                   || $data['newsText'] == '&nbsp;'
                   || $data['newsText'] == '<br />')
               && empty($data['newsRedirect']))
        ) {
            $status = false;
            $error .= $_ARRAYLANG['TXT_SET_NEWS_TITLE_AND_TEXT_OR_REDIRECT'].'<br /><br />';
        }

        if (!$status) {
            return array(false, $error);
        }

        $date = time();
        $userId = \FWUser::getFWUserObject()->objUser->getId();
        $userName = \FWUser::getFWUserObject()->objUser->getUsername();

        $enable = intval($this->arrSettings['news_activate_submitted_news']);
        $query = "INSERT INTO `".DBPREFIX."module_news`
            SET `date` = $date,
                `redirect` = '".contrexx_raw2db($data['newsRedirect'])."',
                `source` = '".contrexx_raw2db($data['newsSource'])."',
                `url1` = '".contrexx_raw2db($data['newsUrl1'])."',
                `url2` = '".contrexx_raw2db($data['newsUrl2'])."',
                `typeid` = '".contrexx_raw2db($data['newsType'])."',
                `status` = '$enable',
                `validated` = '$enable',
                `userid` = $userId,
                `author_id` = $userId,
                `author` = '" . contrexx_raw2db($userName) . "',
                `changelog` = '$date',
                `enable_tags`='" . contrexx_raw2db($data['enableTags']) . "',
                `enable_related_news`=" . $data['newsEnableRelatedNews'] . ",
                `redirect_new_window` = '" . $data['redirectNewWindow'] . "',
                # the following are empty defaults for the text fields.
                # text fields can't have a default and we need one in SQL_STRICT_TRANS_MODE

                `teaser_frames` = '',
                `teaser_image_path` = '',
                `teaser_image_thumbnail_path` = ''";

        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
            return array(false, $_ARRAYLANG['TXT_NEWS_SUBMIT_ERROR'].'<br /><br />');
        }

        $ins_id = $objDatabase->Insert_ID();

// TODO: add fail check
        if (    !$this->storeLocalesOfSubmittedNewsMessage($ins_id, $data['newsTitle'], $data['newsText'], $data['newsTeaserText'])
            ||  !$this->manipulateCategories($data['newsCat'], $ins_id)
            ||  !$this->manipulateRelatedNews($data['newsRelatedNews'], $ins_id)
            ||  !$this->manipulateTags($data['newsTags'], $ins_id)
        ) {
            $errorMessage = empty($this->errMsg)
                ? $_ARRAYLANG['TXT_NEWS_SUBMIT_ERROR']
                : implode('<br>', $this->errMsg);
            return array(false, $errorMessage . '<br /><br />');
        }
        if ($enable) {
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $cx->getEvents()->triggerEvent(
                'clearEsiCache',
                array(
                    'Widget',
                    $this->getNewsGlobalPlaceholderNames()
                )
            );
        }

        return array($ins_id, $_ARRAYLANG['TXT_NEWS_SUCCESSFULLY_SUBMITED'].'<br /><br />');
    }

    /**
     * Insert new locales after submit news from frontend
     * @global ADONewConnection
     * @param Integer   $newsId
     * @param String    $title
     * @param String    $text
     * @param String    $teaser_text
     * @return Boolean
     */
    private function storeLocalesOfSubmittedNewsMessage($newsId, $title, $text, $teaser_text)
    {
        global $objDatabase;

        if (empty($newsId)) {
            return false;
        }

        $status = true;
        $arrActiveFrontendLanguages = array_keys(\FWLanguage::getActiveFrontendLanguages());
        foreach ($arrActiveFrontendLanguages as $langId) {
            $query = "INSERT INTO ".DBPREFIX."module_news_locale (`lang_id`, `news_id`, `title`, `text`, `teaser_text`)
                VALUES ("
                    . intval($langId) . ", "
                    . intval($newsId) . ", '"
                    . contrexx_raw2db($title) . "', '"
                    // store text [bbcode] as html in database
                    . \Cx\Core\Wysiwyg\Wysiwyg::prepareBBCodeForDb($text, true) . "', '"
                    . contrexx_raw2db($teaser_text) . "')";
            if (!$objDatabase->Execute($query)) {
                $status = false;
            }
        }

        return $status;
    }



    /**
    * Show feed page
    * @todo Add proper docblock
    * @global array
    * @global integer
    * @return string Template output
    */
    private function _showFeed()
    {
        global $_ARRAYLANG, $_LANGID;

        $documentRoot = \Cx\Core\Routing\Url::fromDocumentRoot();
        $documentRoot->setMode('backend');

        $documentRoot->setPath('feed/news_headlines_' . \FWLanguage::getLanguageParameter($_LANGID, 'lang') . '.xml');
        $rssFeedUrl = $documentRoot->toString();

        $documentRoot->setPath('feed/news_' . \FWLanguage::getLanguageParameter($_LANGID, 'lang') . '.js');
        $jsFeedUrl = $documentRoot->toString();

        $hostname = addslashes(htmlspecialchars(\Env::get('config')['domainUrl'], ENT_QUOTES, CONTREXX_CHARSET));

        $rss2jsCode = <<<RSS2JSCODE
&lt;script language="JavaScript" type="text/javascript"&gt;
&lt;!--
// {$_ARRAYLANG['TXT_NEWS_OPTIONAL_VARS']}
var rssFeedFontColor = '#000000'; // {$_ARRAYLANG['TXT_NEWS_FONT_COLOR']}
var rssFeedFontSize = 8; // {$_ARRAYLANG['TXT_NEWS_FONT_SIZE']}
var rssFeedFont = 'Arial, Verdana'; // {$_ARRAYLANG['TXT_NEWS_FONT']}
var rssFeedLimit = 10; // {$_ARRAYLANG['TXT_NEWS_DISPLAY_LIMIT']}
var rssFeedShowDate = true; // {$_ARRAYLANG['TXT_NEWS_SHOW_NEWS_DATE']}
var rssFeedTarget = '_blank'; // _blank | _parent | _self | _top
var rssFeedContainer = 'news_rss_feeds';
// --&gt;
&lt;/script&gt;
&lt;script type="text/javascript" language="JavaScript" src="$jsFeedUrl"&gt;&lt;/script&gt;
&lt;noscript&gt;
&lt;a href="$rssFeedUrl"&gt;$hostname - {$_ARRAYLANG['TXT_NEWS_SHOW_NEWS']}&lt;/a&gt;
&lt;/noscript&gt;
&lt;div id="news_rss_feeds"&gt;&nbsp;&lt;/div&gt;
RSS2JSCODE;

        $this->_objTpl->setVariable(array(
            'NEWS_HOSTNAME'     => $hostname,
            'NEWS_RSS2JS_CODE'  => $rss2jsCode,
            'NEWS_RSS2JS_URL'   => $jsFeedUrl,
            'NEWS_RSS_FEED_URL' => $rssFeedUrl
        ));
        return $this->_objTpl->get();
    }


    /**
     * Returns Teaser Text if displaying a detail page.
     * Used in index.php to overwrite the meta description.
     *
     * @return String Teaser if displaying detail page, else null
     */
    public function getTeaser()
    {
        return $this->_teaser;
    }

    /**
     * Get a list of all news messages sorted by year and month.
     *
     * @param string $expirationDate Expiration date
     * @return string parsed content
     */
    private function getArchive(&$expirationDate = null)
    {
        global $objDatabase, $_ARRAYLANG;

        $categories = '';
        $selectedCategories = array();
        $i          = 0;
        if ($categories = substr($_REQUEST['cmd'], 7)) {
            $selectedCategories = contrexx_input2int(
                explode('-', $categories)
            );
            $categories = $this->getCatIdsFromNestedSetArray(
                $this->getNestedSetCategories($selectedCategories)
            );
        }

        $monthlyStats   = $this->getMonthlyNewsStats($categories);
        $expirationDate = $this->getExpirationDate();

        if (!empty($monthlyStats)) {
            foreach ($monthlyStats as $key => $value) {
                $this->_objTpl->setVariable(array(
                    'NEWS_ARCHIVE_MONTH_KEY'    => $key,
                    'NEWS_ARCHIVE_MONTH_NAME'   => $value['name'],
                    'NEWS_ARCHIVE_MONTH_COUNT'  => count($value['news']),
                ));
                $this->_objTpl->parse('news_archive_months_list_item');

                foreach ($value['news'] as $news) {
                    $newsid         = $news['id'];
                    $newstitle      = $news['newstitle'];
                    $newsCategories = $this->getCategoriesByNewsId(
                        $newsid,
                        $selectedCategories
                    );
                    $newsCommentActive = $news['commentactive'];
                    $newsUrl        = empty($news['newsredirect'])
                                        ? (empty($news['newscontent'])
                                            ? ''
                                            : \Cx\Core\Routing\Url::fromModuleAndCmd('News', $this->findCmdById('details', self::sortCategoryIdByPriorityId(array_keys($newsCategories), $categories)), FRONTEND_LANG_ID, array('newsid' => $newsid)))
                                        : $news['newsredirect'];

                    $redirectNewWindow = !empty($news['newsredirect']) && !empty($news['redirectNewWindow']);
                    $htmlLink = self::parseLink($newsUrl, $newstitle, contrexx_raw2xhtml('[' . $_ARRAYLANG['TXT_NEWS_MORE'] . '...]'), $redirectNewWindow);
                    $linkTarget = $redirectNewWindow ? '_blank' : '_self';

                    list($image, $htmlLinkImage, $imageSource) = self::parseImageThumbnail($news['teaser_image_path'],
                                                                                           $news['teaser_image_thumbnail_path'],
                                                                                           $newstitle,
                                                                                           $newsUrl);
                    $author = \FWUser::getParsedUserTitle($news['author_id'], $news['author']);
                    $publisher = \FWUser::getParsedUserTitle($news['publisher_id'], $news['publisher']);
                    $objResult = $objDatabase->Execute('SELECT count(`id`) AS `countComments` FROM `'.DBPREFIX.'module_news_comments` WHERE `newsid` = '.$newsid);

                    $this->_objTpl->setVariable(array(
                       'NEWS_ARCHIVE_ID'            => $newsid,
                       'NEWS_ARCHIVE_CSS'           => 'row'.($i % 2 + 1),
                       'NEWS_ARCHIVE_TEASER'        => $this->arrSettings['news_use_teaser_text'] ? nl2br($news['teaser_text']) : '',
                       'NEWS_ARCHIVE_TITLE'         => contrexx_raw2xhtml($newstitle),
                       'NEWS_ARCHIVE_LONG_DATE'     => date(ASCMS_DATE_FORMAT,$news['date']),
                       'NEWS_ARCHIVE_DATE'          => date(ASCMS_DATE_FORMAT_DATE, $news['date']),
                       'NEWS_ARCHIVE_TIME'          => date(ASCMS_DATE_FORMAT_TIME, $news['date']),
                       'NEWS_ARCHIVE_TIMESTAMP'     => $news['date'],
                       'NEWS_ARCHIVE_LINK_TITLE'    => contrexx_raw2xhtml($newstitle),
                       'NEWS_ARCHIVE_LINK'          => $htmlLink,
                       'NEWS_ARCHIVE_LINK_TARGET'   => $linkTarget,
                       'NEWS_ARCHIVE_LINK_URL'      => contrexx_raw2xhtml($newsUrl),
                       'NEWS_ARCHIVE_CATEGORY'      => implode(', ', contrexx_raw2xhtml($newsCategories)),
                       'NEWS_ARCHIVE_AUTHOR'        => contrexx_raw2xhtml($author),
                       'NEWS_ARCHIVE_PUBLISHER'     => contrexx_raw2xhtml($publisher),
                       'NEWS_ARCHIVE_COUNT_COMMENTS'=> contrexx_raw2xhtml($objResult->fields['countComments'].' '.$_ARRAYLANG['TXT_NEWS_COMMENTS']),
                    ));

                    if (!$newsCommentActive || !$this->arrSettings['news_comments_activated']) {
                        if ($this->_objTpl->blockExists('news_archive_comments_count')) {
                            $this->_objTpl->hideBlock('news_archive_comments_count');
                        }
                    }

                    if (!empty($image)) {
                        $this->_objTpl->setVariable(array(
                            'NEWS_ARCHIVE_IMAGE'               => $image,
                            'NEWS_ARCHIVE_IMAGE_SRC'           => contrexx_raw2xhtml($imageSource),
                            'NEWS_ARCHIVE_IMAGE_ALT'           => contrexx_raw2xhtml($newstitle),
                            'NEWS_ARCHIVE_IMAGE_LINK'          => $htmlLinkImage,
                        ));
                        if ($this->_objTpl->blockExists('news_archive_image')) {
                            $this->_objTpl->parse('news_archive_image');
                        }
                    } elseif ($this->_objTpl->blockExists('news_archive_image')) {
                        $this->_objTpl->hideBlock('news_archive_image');
                    }

                    self::parseImageBlock($this->_objTpl, $news['teaser_image_thumbnail_path'], $newstitle, $newsUrl, 'archive_image_thumbnail');
                    self::parseImageBlock($this->_objTpl, $news['teaser_image_path'], $newstitle, $newsUrl, 'archive_image_detail');

                    $this->_objTpl->parse('news_archive_link');
                    $i++;
                }
                $this->_objTpl->setVariable(array(
                    'NEWS_ARCHIVE_MONTH_KEY'    => $key,
                    'NEWS_ARCHIVE_MONTH_NAME'   => $value['name'],
                ));
                $this->_objTpl->parse('news_archive_month_list_item');
            }

            $this->_objTpl->parse('news_archive_months_list');
            $this->_objTpl->parse('news_archive_month_list');
            if ($this->_objTpl->blockExists('news_archive_status_message')) {
                $this->_objTpl->hideBlock('news_archive_status_message');
            }
        } else {
            $this->_objTpl->setVariable('TXT_NEWS_NO_NEWS_FOUND', $_ARRAYLANG['TXT_NEWS_NO_NEWS_FOUND']);

            if ($this->_objTpl->blockExists('news_archive_status_message')) {
                $this->_objTpl->parse('news_archive_status_message');
            }
            $this->_objTpl->hideblock('news_archive_months_list');
            $this->_objTpl->hideBlock('news_archive_month_list');
        }


        return $this->_objTpl->get();
    }

    /**
     * Get a expiration date
     *
     * @return \DateTime \DateTime object for expiration date
     */
    protected function getExpirationDate()
    {
        $objDatabase = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getAdoDb();
        $query       = 'SELECT startdate AS expirationdate
            FROM ' . DBPREFIX . 'module_news as tblStart
                WHERE (
                    tblStart.startdate !="0000-00-00 00:00:00" AND
                    tblStart.startdate >= "'. date('Y-m-d H:i:s') .'"
                )
            UNION
                SELECT enddate AS expirationdate
                    FROM ' . DBPREFIX . 'module_news as tblEnd
                WHERE (
                    tblEnd.enddate !="0000-00-00 00:00:00" AND
                    tblEnd.enddate >= "'. date('Y-m-d H:i:s') .'"
                )
            ORDER BY expirationdate LIMIT 1';

        $result = $objDatabase->Execute($query);

        if (empty($result->fields['expirationdate'])) {
            return;
        }

        return new \DateTime($result->fields['expirationdate']);
    }
}
