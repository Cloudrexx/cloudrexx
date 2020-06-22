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
 * Forum
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Kaelin <thomas.kaelin@comvation.com>
 * @version        $Id: index.inc.php,v 1.00 $
 * @package     cloudrexx
 * @subpackage  module_forum
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Modules\Forum\Controller;

/**
 * Forum
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Kaelin <thomas.kaelin@comvation.com>
 * @version        $Id: index.inc.php,v 1.00 $
 * @package     cloudrexx
 * @subpackage  module_forum
 */
class Forum extends ForumLibrary {

    var $_objTpl;
    var $strError = ''; //errormessage for captcha


    /**
     * Constructor
     *
     * Call parent-constructor, set language id and create local template-object
     * @global    integer
     */
    function __construct($strPageContent)
    {
        global $_LANGID;

        parent::__construct();
        $this->_intLangId = intval($_LANGID);
        $this->_objTpl = new \Cx\Core\Html\Sigma('.');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);
        $this->_objTpl->setTemplate($strPageContent);
    }


    /**
    * Reads $_GET['act'] and selects an action, which prepares
    * the template that is going to be returned
    *
    * @return string HTML-Code
    */
    function getPage()
    {
        if(!isset($_GET['cmd'])) {
            $_GET['cmd'] = '';
        }

        if(!empty($_GET['postId']) && $_GET['act'] == 'rate'){
            return $this->_rate();
        }

        switch ($_GET['cmd']) {
            case 'searchTags':
                $this->_showTags();
                break;
            case 'board':
                $this->showForum($_GET['id']);
                break;
            case 'thread':
                $this->showThread($_GET['id']);
                break;
            case 'cat':
                $this->showCategory($_GET['id']);
                break;
            case 'notification':
                $this->showNotifications();
                break;
            case 'toplist':
                $this->showTopList();
                break;
            case 'cloud':
                $this->showTagCloud();
                break;
            default:
                $this->showForumOverview();
                break;
        }
        return $this->_objTpl->get();
    }


    /**
     * shows the search results for tags
     *
     * @return boolean success
     */
    function _showTags(){
        global $objDatabase, $_CONFIG, $_ARRAYLANG;
        $pos = !empty($_GET['pos']) ? intval($_GET['pos']) : '0';
        $term = contrexx_addslashes($_GET['term']);
        $searchContentToo = !empty($_GET['searchContent']) ? intval($_GET['searchContent']) : '0';

        $this->_objTpl->setVariable(array(
            'FORUM_SEARCH_TERM'                => htmlentities($term, ENT_QUOTES, CONTREXX_CHARSET),
            'FORUM_SEARCH_CONTENT_CHECKED'    => $searchContentToo == 1 ? 'checked="checked"' : '',
        ));

        if(strlen($term) < 3 && !empty($_REQUEST['search'])){
            $this->_objTpl->setVariable('FORUM_ERROR', $_ARRAYLANG['TXT_FORUM_SEARCH_TERM_TOO_SHORT']);
            $this->_objTpl->parse('forumError');
            return false;
        }

        $queryAdd = '';
        if($searchContentToo > 0){
            $queryAdd = " OR content LIKE '%".$term."%'";
        }

        $query = "SELECT count(1) as `cnt`
                    FROM `".DBPREFIX."module_forum_postings`
                    WHERE `keywords` LIKE '%".$term."%'
                    OR `subject` LIKE '%".$term."%' ".$queryAdd;
        $objRS = $objDatabase->Execute($query);
        $count = $objRS->fields['cnt'];
        $query = "SELECT id, thread_id, category_id, subject, content, keywords,
                         MATCH (content,subject,keywords) AGAINST ('%".$term."%') AS score
            FROM `".DBPREFIX."module_forum_postings`
            WHERE `keywords` LIKE '%".$term."%'
            OR `subject` LIKE '%".$term."%' ".$queryAdd. " ORDER BY score DESC";
        $objRS = $objDatabase->SelectLimit($query, $_CONFIG['corePagingLimit'], $pos);
        while(!$objRS->EOF){
            $postId = $objRS->fields['id'];
            $threadId = $objRS->fields['thread_id'];
            $catId = $objRS->fields['category_id'];
            $link = 'index.php?section=Forum&amp;cmd=thread&amp;postid='.$postId.'&amp;id='.$threadId.
                                '&amp;l=1&amp;pos='.$this->_getEditPos($postId, $threadId).'#p'.$postId;

            $subject = $objRS->fields['subject'];
            $content = preg_replace("#\[[^\]]+\]#", "", $objRS->fields['content']);
            $keywords = $objRS->fields['keywords'];

            if(strlen($content) > 60){
                $content = substr($content, 0, 60).'[...]';
            }

            $this->_objTpl->setVariable(array(
                'FORUM_THREAD_SUBJECT'     => $subject,
                'FORUM_THREAD_LINK'         => $link,
                'FORUM_THREAD_KEYWORDS'     => $keywords,
                'FORUM_THREAD_CONTENT'     => $content,
            ));
            $this->_objTpl->parse('threadList');
            $objRS->MoveNext();
        }

        if($count > $_CONFIG['corePagingLimit']){
            $paging = getPaging($count, $pos, '&section=Forum&cmd=searchTags&term='.$term, $_ARRAYLANG['TXT_FORUM_OVERVIEW_THREADS']);
            $this->_objTpl->setVariable('FORUM_SEARCH_PAGING', $paging);
        }
    }

    /**
     * parse the tag cloud and hitlist
     *
     */
    function showTagCloud(){
            $this->_objTpl->setVariable(array(
                'FORUM_TAG_CLOUD'    => $this->getTagCloud(),
                'FORUM_TAG_HITLIST'    => $this->getTagHitlist()
            ));
    }

    /**
     * parse the top lists (most viewed + top rated)
     *
     */
    function showTopList(){
        $this->_parseMostViewed($this->_getMostViewed());
        $this->_parseTopRated($this->_getTopRated());
    }


   /**
    * parse the most viewed entries
    *
    * @param adodb_result_set $objRS
    */
    function _parseMostViewed($objRS){
        while(!$objRS->EOF){
            $postId = $objRS->fields['id'];
            $threadId = $objRS->fields['thread_id'];
            $catId = $objRS->fields['category_id'];
            $link = 'index.php?section=Forum&amp;cmd=thread&amp;postid='.$postId.'&amp;id='.$threadId.
                                '&amp;l=1&amp;pos='.$this->_getEditPos($postId, $threadId).'#p'.$postId;

            $subject = $objRS->fields['subject'];
            $content = $objRS->fields['content'];
            $keywords = $objRS->fields['keywords'];

            if(strlen($content) > 60){
                $content = substr($content, 0, 60).'[...]';
            }

            $this->_objTpl->setVariable(array(
                'FORUM_MOST_VIEWED_SUBJECT'     => $subject,
                'FORUM_MOST_VIEWED_LINK'     => $link,
                'FORUM_MOST_VIEWED_KEYWORDS' => $keywords,
                'FORUM_MOST_VIEWED_CONTENT'     => $content,
                'FORUM_MOST_VIEWED_VIEWS'     => $objRS->fields['views'],
            ));
            $this->_objTpl->parse('mostViewed');
            $objRS->MoveNext();
        }
    }


    /**
     * get the most viewed entries
     *
     * @return adodb_result_set $objRS
     */
    function _getMostViewed(){
        global $objDatabase;
        $query = "    SELECT id, thread_id, category_id, subject, content, keywords, views
                    FROM `".DBPREFIX."module_forum_postings`
                    ORDER BY `views` DESC";
        $objRS = $objDatabase->SelectLimit($query, $this->_topListLimit);
        return $objRS;
    }


    /**
     * parse the top rated entries
     *
     * @param adodb_result_set $objRSS
     */
    function _parseTopRated($objRS){
        while(!$objRS->EOF){
            $postId = $objRS->fields['id'];
            $threadId = $objRS->fields['thread_id'];
            $catId = $objRS->fields['category_id'];
            $link = 'index.php?section=Forum&amp;cmd=thread&amp;postid='.$postId.'&amp;id='.$threadId.
                                '&amp;l=1&amp;pos='.$this->_getEditPos($postId, $threadId).'#p'.$postId;

            $subject = $objRS->fields['subject'];
            $content = $objRS->fields['content'];
            $keywords = $objRS->fields['keywords'];

            if(strlen($content) > 60){
                $content = substr($content, 0, 60).'[...]';
            }

            $this->_objTpl->setVariable(array(
                'FORUM_TOP_RATED_SUBJECT'     => $subject,
                'FORUM_TOP_RATED_LINK'          => $link,
                'FORUM_TOP_RATED_KEYWORDS'     => $keywords,
                'FORUM_TOP_RATED_CONTENT'     => $content,
                'FORUM_TOP_RATED_RATING'     => $objRS->fields['rating'],
            ));
            $this->_objTpl->parse('topRated');
            $objRS->MoveNext();
        }
    }


    /**
     * get the top rated entries
     *
     * @return adodb_result_set $objRS
     */
    function _getTopRated(){
        global $objDatabase;
        $query = "    SELECT id, thread_id, category_id, subject, content, keywords, rating
                    FROM `".DBPREFIX."module_forum_postings`
                    ORDER BY `rating` DESC";
        $objRS = $objDatabase->SelectLimit($query, $this->_topListLimit);
        return $objRS;
    }


    /**
     * checks if a user has already rated a post
     *
     * @param integer $postId
     * @return boolean
     */
    function _hasRated($postId)
    {
        global $objDatabase;

        $objFWUser = \FWUser::getFWUserObject();
        $query = "    DELETE FROM `".DBPREFIX."module_forum_rating`
                    WHERE `time`+".$this->_rateTimeout."  < ".time();
        $objDatabase->Execute($query);

        $query = "    SELECT 1 FROM `".DBPREFIX."module_forum_rating`
                    WHERE `user_id` = ".($objFWUser->objUser->login() ? $objFWUser->objUser->getId() : 0)
                ."    AND `post_id` = ".$postId;
        $objRS = $objDatabase->Execute($query);
        if($objRS->RecordCount() > 0){
            return true;
        } else {
            return false;
        }
    }


    /**
     * updates the rated table to prevent multiple ratings with a timeout
     *
     * @param integer $postId
     * @return boolean
     */
    function _updateRated($postId)
    {
        global $objDatabase;

        $objFWUser = \FWUser::getFWUserObject();
        $query = "    INSERT INTO `".DBPREFIX."module_forum_rating`
                    (`user_id`, `post_id`, `time`) VALUES
                    (".($objFWUser->objUser->login() ? $objFWUser->objUser->getId() : 0).", $postId, ".time().")";
        return $objDatabase->Execute($query);
    }

    /**
     * update the rating for a post
     *
     */
    function _rate()
    {
        global $objDatabase;

        $objFWUser = \FWUser::getFWUserObject();
        if (!$objFWUser->objUser->login()) {
            die('not allowed to vote.');
        }
        $postId = intval($_GET['postId']);

        if($this->_hasRated($postId)){
            die('already voted.');
        }

        if(!$this->_updateRated($postId)){
            die('DB error.');
        }

        if(intval($_GET['value']) == 1){
            $set = "`rating` = `rating` + 1";
        }elseif(intval($_GET['value']) == -1){
            $set = "`rating` = `rating` - 1";
        }
        $query = "    UPDATE `".DBPREFIX."module_forum_postings`
                    SET $set
                    WHERE `id` = $postId";
        $objDatabase->Execute($query);
        header ("Content-type: image/gif");
        //1x1px gif to make onload succeed
        die("\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\xF7\x00\x00\x00\x00\x00\x80\x00\x00\x00\x80\x00\x80\x80\x00\x00\x00\x80\x80\x00\x80\x00\x80\x80\x80\x80\x80\xC0\xC0\xC0\xFF\x00\x00\x00\xFF\x00\xFF\xFF\x00\x00\x00\xFF\xFF\x00\xFF\x00\xFF\xFF\xFF\xFF\xFF\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x33\x00\x00\x66\x00\x00\x99\x00\x00\xCC\x00\x00\xFF\x00\x33\x00\x00\x33\x33\x00\x33\x66\x00\x33\x99\x00\x33\xCC\x00\x33\xFF\x00\x66\x00\x00\x66\x33\x00\x66\x66\x00\x66\x99\x00\x66\xCC\x00\x66\xFF\x00\x99\x00\x00\x99\x33\x00\x99\x66\x00\x99\x99\x00\x99\xCC\x00\x99\xFF\x00\xCC\x00\x00\xCC\x33\x00\xCC\x66\x00\xCC\x99\x00\xCC\xCC\x00\xCC\xFF\x00\xFF\x00\x00\xFF\x33\x00\xFF\x66\x00\xFF\x99\x00\xFF\xCC\x00\xFF\xFF\x33\x00\x00\x33\x00\x33\x33\x00\x66\x33\x00\x99\x33\x00\xCC\x33\x00\xFF\x33\x33\x00\x33\x33\x33\x33\x33\x66\x33\x33\x99\x33\x33\xCC\x33\x33\xFF\x33\x66\x00\x33\x66\x33\x33\x66\x66\x33\x66\x99\x33\x66\xCC\x33\x66\xFF\x33\x99\x00\x33\x99\x33\x33\x99\x66\x33\x99\x99\x33\x99\xCC\x33\x99\xFF\x33\xCC\x00\x33\xCC\x33\x33\xCC\x66\x33\xCC\x99\x33\xCC\xCC\x33\xCC\xFF\x33\xFF\x00\x33\xFF\x33\x33\xFF\x66\x33\xFF\x99\x33\xFF\xCC\x33\xFF\xFF\x66\x00\x00\x66\x00\x33\x66\x00\x66\x66\x00\x99\x66\x00\xCC\x66\x00\xFF\x66\x33\x00\x66\x33\x33\x66\x33\x66\x66\x33\x99\x66\x33\xCC\x66\x33\xFF\x66\x66\x00\x66\x66\x33\x66\x66\x66\x66\x66\x99\x66\x66\xCC\x66\x66\xFF\x66\x99\x00\x66\x99\x33\x66\x99\x66\x66\x99\x99\x66\x99\xCC\x66\x99\xFF\x66\xCC\x00\x66\xCC\x33\x66\xCC\x66\x66\xCC\x99\x66\xCC\xCC\x66\xCC\xFF\x66\xFF\x00\x66\xFF\x33\x66\xFF\x66\x66\xFF\x99\x66\xFF\xCC\x66\xFF\xFF\x99\x00\x00\x99\x00\x33\x99\x00\x66\x99\x00\x99\x99\x00\xCC\x99\x00\xFF\x99\x33\x00\x99\x33\x33\x99\x33\x66\x99\x33\x99\x99\x33\xCC\x99\x33\xFF\x99\x66\x00\x99\x66\x33\x99\x66\x66\x99\x66\x99\x99\x66\xCC\x99\x66\xFF\x99\x99\x00\x99\x99\x33\x99\x99\x66\x99\x99\x99\x99\x99\xCC\x99\x99\xFF\x99\xCC\x00\x99\xCC\x33\x99\xCC\x66\x99\xCC\x99\x99\xCC\xCC\x99\xCC\xFF\x99\xFF\x00\x99\xFF\x33\x99\xFF\x66\x99\xFF\x99\x99\xFF\xCC\x99\xFF\xFF\xCC\x00\x00\xCC\x00\x33\xCC\x00\x66\xCC\x00\x99\xCC\x00\xCC\xCC\x00\xFF\xCC\x33\x00\xCC\x33\x33\xCC\x33\x66\xCC\x33\x99\xCC\x33\xCC\xCC\x33\xFF\xCC\x66\x00\xCC\x66\x33\xCC\x66\x66\xCC\x66\x99\xCC\x66\xCC\xCC\x66\xFF\xCC\x99\x00\xCC\x99\x33\xCC\x99\x66\xCC\x99\x99\xCC\x99\xCC\xCC\x99\xFF\xCC\xCC\x00\xCC\xCC\x33\xCC\xCC\x66\xCC\xCC\x99\xCC\xCC\xCC\xCC\xCC\xFF\xCC\xFF\x00\xCC\xFF\x33\xCC\xFF\x66\xCC\xFF\x99\xCC\xFF\xCC\xCC\xFF\xFF\xFF\x00\x00\xFF\x00\x33\xFF\x00\x66\xFF\x00\x99\xFF\x00\xCC\xFF\x00\xFF\xFF\x33\x00\xFF\x33\x33\xFF\x33\x66\xFF\x33\x99\xFF\x33\xCC\xFF\x33\xFF\xFF\x66\x00\xFF\x66\x33\xFF\x66\x66\xFF\x66\x99\xFF\x66\xCC\xFF\x66\xFF\xFF\x99\x00\xFF\x99\x33\xFF\x99\x66\xFF\x99\x99\xFF\x99\xCC\xFF\x99\xFF\xFF\xCC\x00\xFF\xCC\x33\xFF\xCC\x66\xFF\xCC\x99\xFF\xCC\xCC\xFF\xCC\xFF\xFF\xFF\x00\xFF\xFF\x33\xFF\xFF\x66\xFF\xFF\x99\xFF\xFF\xCC\xFF\xFF\xFF\x21\xF9\x04\x01\x00\x00\x10\x00\x2C\x00\x00\x00\x00\x01\x00\x01\x00\x00\x08\x04\x00\xFF\x05\x04\x00\x3B");
    }



    /**
     * Show all threads of a forum
     *
     * @global  ADONewConnection
     * @global     array
     * @global   Cache
     * @param    integer        $intForumId: The id of the forum which should be shown
     */
    function showForum($intForumId)
    {
        global $objDatabase, $_ARRAYLANG, $_LANGID, $_CORELANG;

        if ($intForumId == 0) {
            //wrong id, redirect
            \Cx\Core\Csrf\Controller\Csrf::header('location: index.php?section=Forum');
            die();
        }

        $objFWUser = \FWUser::getFWUserObject();

        $this->_communityLogin();

        $intCounter = 1;
        $intForumId = intval($intForumId);
        $intThreadId = !empty($_REQUEST['threadid']) ? intval($_REQUEST['threadid']) : 0;
        $pos = !empty($_REQUEST['pos']) ? intval($_REQUEST['pos']) : 0;

        if ($objFWUser->objUser->login()) {
            $this->_objTpl->touchBlock('notificationRow');
        } else{
            $this->_objTpl->hideBlock('notificationRow');
        }

        $_REQUEST['act'] = !empty($_REQUEST['act']) ? $_REQUEST['act'] : '';
        if($_REQUEST['act'] == 'delete'){
            if($this->_checkAuth($intForumId, 'delete')){
                if($this->_deleteThread($intThreadId, $intForumId)){
                    $this->_objTpl->setVariable('TXT_FORUM_SUCCESS', '<br />'.$_ARRAYLANG['TXT_FORUM_DELETED_SUCCESSFULLY']);
                }else{
                    $this->_objTpl->setVariable('TXT_FORUM_ERROR', '<br />'.$_ARRAYLANG['TXT_FORUM_DELETE_FAILED']);
                }
            }else{
                $this->_objTpl->setVariable('TXT_FORUM_ERROR', '<br />'.$_ARRAYLANG['TXT_FORUM_NO_ACCESS']);
                return false;
            }
        }

        $arrThreads = $this->createThreadArray($intForumId, $pos);

        $subject = !empty($_REQUEST['thread_subject']) ? contrexx_stripslashes($_REQUEST['thread_subject']) : '';
        $keywords = !empty($_REQUEST['thread_keywords']) ? contrexx_stripslashes($_REQUEST['thread_keywords']) : '';
        $content = !empty($_REQUEST['thread_message']) ? contrexx_stripslashes($_REQUEST['thread_message']) : '';

        if($this->_arrSettings['wysiwyg_editor'] == 1) { //IF WYSIWIG enabled..
            $strMessageInputHTML = new \Cx\Core\Wysiwyg\Wysiwyg('thread_message', $content, 'bbcode');
        }else{ //plain textarea
            $strMessageInputHTML = '<textarea style="width: 400px; height: 150px;" rows="5" cols="10" name="thread_message">'.htmlentities($content, ENT_QUOTES, CONTREXX_CHARSET).'</textarea>';
        }

        //Initialize the Uploader
        $this->initForumUploader();

        $this->_objTpl->setGlobalVariable(array(
            'FORUM_NAME'                =>    $this->_shortenString($this->_arrTranslations[$intForumId][$this->_intLangId]['name'], $this->_maxStringlength),
            'FORUM_TREE'                =>    $this->_createNavTree($intForumId),
            'FORUM_DROPDOWN'            =>    $this->createForumDD('forum_quickaccess', $intForumId, 'onchange="gotoForum(this);"', ''),
            'FORUM_JAVASCRIPT_GOTO'        =>     $this->getJavascript('goto'),
            'FORUM_JAVASCRIPT_DELETE'    =>     $this->getJavascript('deleteThread'),
            'FORUM_JAVASCRIPT_INSERT_TEXT'    =>     $this->getJavascript('insertText'),
            'TXT_FORUM_ICON'            =>    $_ARRAYLANG['TXT_FORUM_ICON'],
            'TXT_FORUM_CREATE_THREAD'    =>    $_ARRAYLANG['TXT_FORUM_CREATE_THREAD'],
            'TXT_FORUM_NOTIFY_NEW_POSTS' =>    $_ARRAYLANG['TXT_FORUM_NOTIFY_NEW_POSTS'],
            'TXT_FORUM_UPDATE_NOTIFICATION' =>    $_ARRAYLANG['TXT_FORUM_UPDATE_NOTIFICATION'],
            'FORUM_NOTIFICATION_CHECKBOX_CHECKED'    =>    $this->_hasNotification($intThreadId) ? 'checked="checked"' : '',
            'TXT_FORUM_CAPTCHA'      => $_CORELANG['TXT_CORE_CAPTCHA'],
            'FORUM_CAPTCHA_CODE'    =>    \Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->getCode(),
            'FORUM_FORUM_ID'            =>    $intForumId, // the category id via GET
            'FORUM_SUBJECT'                =>    htmlentities($subject, ENT_QUOTES, CONTREXX_CHARSET),
            'FORUM_KEYWORDS'            =>    htmlentities($keywords, ENT_QUOTES, CONTREXX_CHARSET),
            'FORUM_MESSAGE_INPUT'        =>    $strMessageInputHTML,
            'TXT_FORUM_CHOOSE_FILE'      =>    $_ARRAYLANG['TXT_FORUM_CHOOSE_FILE']
        ));

        if ($objFWUser->objUser->login()) {
            $this->_objTpl->hideBlock('captcha');
        } else {
            $this->_objTpl->touchBlock('captcha');
        }

        $this->_setIcons($this->_getIcons());

        $this->_objTpl->setVariable(array(
                'TXT_THREADS_SUBJECTAUTHOR'        =>    $_ARRAYLANG['TXT_FORUM_THREADS_SUBJECTAUTHOR'],
                'TXT_THREADS_LASTTOPIC'            =>    $_ARRAYLANG['TXT_FORUM_OVERVIEW_LASTPOST'],
                'TXT_THREADS_REPLIES'            =>    $_ARRAYLANG['TXT_FORUM_THREADS_REPLIES'],
                'TXT_THREADS_HITS'                =>    $_ARRAYLANG['TXT_FORUM_THREADS_HITS'],
                'TXT_FORUM_ADD_THREAD'            =>    $_ARRAYLANG['TXT_FORUM_ADD_THREAD'],
                'TXT_FORUM_SUBJECT'                =>    $_ARRAYLANG['TXT_FORUM_SUBJECT'],
                'TXT_FORUM_MESSAGE'                =>    $_ARRAYLANG['TXT_FORUM_MESSAGE'],
                'TXT_FORUM_RESET'                =>    $_ARRAYLANG['TXT_FORUM_RESET'],
                'TXT_FORUM_CREATE_THREAD'        =>    $_ARRAYLANG['TXT_FORUM_CREATE_THREAD'],
                'TXT_FORUM_PREVIEW'                =>    $_ARRAYLANG['TXT_FORUM_PREVIEW'],
                'TXT_FORUM_FILE_ATTACHMENT'        =>    $_ARRAYLANG['TXT_FORUM_FILE_ATTACHMENT'],
                'TXT_FORUM_COMMA_SEPARATED_KEYWORDS'    =>    $_ARRAYLANG['TXT_FORUM_COMMA_SEPARATED_KEYWORDS'],
                'TXT_FORUM_KEYWORDS'            =>    $_ARRAYLANG['TXT_FORUM_KEYWORDS'],

        ));

        if(!$this->_checkAuth($intForumId, 'write')){
            $this->_objTpl->hideBlock('addThread');
            $this->_objTpl->hideBlock('addPostAnchor');
        }else{
            $this->_objTpl->touchBlock('addPostAnchor');
        }

        if (count($arrThreads) > 0) {
            if(!$this->_checkAuth($intForumId, 'read')){
                $this->_objTpl->setVariable('TXT_FORUM_ERROR', '<br />'.$_ARRAYLANG['TXT_FORUM_NO_ACCESS']);
                return false;
            }
            $intCounter = 0;
            foreach ($arrThreads as $threadId => $arrValues) {
                $strUserProfileLink = ($arrValues['user_id'] > 0) ? '<a href="index.php?section=Access&amp;cmd=user&amp;id='.$arrValues['user_id'].'">'.$arrValues['user_name'].'</a>': $this->_anonymousName;
                $this->_objTpl->setVariable(array(
                    'FORUM_THREADS_ROWCLASS'        =>    ($intCounter++ % 2) + 1,
                    'FORUM_THREADS_SYMBOL'            =>    '<img title="comment.gif" alt="comment.gif" src="'.ASCMS_MODULE_WEB_PATH.'/Forum/View/Media/comment.gif" border="0" />',
                    'FORUM_THREADS_ICON'            =>    $arrValues['thread_icon'],
                    'FORUM_THREADS_ID'                =>    $arrValues['thread_id'],
                    'FORUM_THREADS_NAME'            =>    $arrValues['subject'],
                    'FORUM_THREADS_AUTHOR'            =>    $strUserProfileLink,
                    'FORUM_THREADS_LASTPOST_DATE'    =>    $arrValues['lastpost_time'],
                    'FORUM_THREADS_LASTPOST_AUTHOR'    =>    $arrValues['lastpost_author'],
                    'FORUM_THREADS_REPLIES'            =>    $arrValues['replies'],
                    'FORUM_THREADS_HITS'            =>    $arrValues['views'],
                ));

                if($this->_checkAuth($intForumId, 'delete')){
                    $this->_objTpl->setVariable('FORUM_THREAD_ID', $intThreadId);
                    $this->_objTpl->touchBlock('deleteThread');
                }else{
                    $this->_objTpl->hideBlock('deleteThread');
                }
                $this->_objTpl->parse('forumThreads');
            }
            $this->_objTpl->setVariable(array(
                'FORUM_THREADS_PAGING'    =>    getPaging($this->_threadCount, $pos, '&section=Forum&cmd=board&id='.$intForumId, $_ARRAYLANG['TXT_FORUM_THREAD'], true, $this->_arrSettings['thread_paging']),
            ));
            $this->_objTpl->hideBlock('forumNoThreads');
        } else {
            //no threads in this board, show message
            $this->_objTpl->setVariable('TXT_FORUM_NO_THREADS', $_ARRAYLANG['TXT_FORUM_NO_THREADS']);
            $this->_objTpl->parse('forumNoThreads');
            $this->_objTpl->hideBlock('forumThreads');
        }

        if(!empty($_REQUEST['create']) && $_REQUEST['create'] == $_ARRAYLANG['TXT_FORUM_CREATE_THREAD']){
            //addthread code
            if(!$this->_checkAuth($intForumId, 'write')){
                $this->_objTpl->setVariable('TXT_FORUM_ERROR', '<br />'.$_ARRAYLANG['TXT_FORUM_NO_ACCESS']);
                $this->_objTpl->hideBlock('addThread');
                return false;
            }

            if (!$objFWUser->objUser->login() && !\Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->check()) {
                return false;
            }

            if(strlen(trim($content)) < $this->_minPostlength){//content check
                $this->_objTpl->setVariable('TXT_FORUM_ERROR', sprintf('<br />'.$_ARRAYLANG['TXT_FORUM_POST_EMPTY'], $this->_minPostlength));
                return false;
            }

            if(false !== ($match = $this->_hasBadWords($content))){
                $this->_objTpl->setVariable('TXT_FORUM_ERROR', sprintf('<br />'.$_ARRAYLANG['TXT_FORUM_BANNED_WORD'], $match[1]));
                return false;
            }
            $fileInfo = $this->_handleUpload('forum_attachment');
            if($fileInfo === false){ //an error occured, the file wasn't properly transferred. exit function to display error set in _handleUpload()
                return false;
            }

            $maxIdQuery = '    SELECT max( thread_id ) as max_thread_id
                            FROM '.DBPREFIX.'module_forum_postings';
            if( ($objRSmaxId = $objDatabase->SelectLimit($maxIdQuery, 1)) !== false){
                $intLastThreadId = $objRSmaxId->fields['max_thread_id'] + 1;
            }else{
                die($objDatabase->ErrorMsg());
            }

            $userId = $objFWUser->objUser->login() ? $objFWUser->objUser->getId() : 0;
            $icon = !empty($_REQUEST['icons']) ? intval($_REQUEST['icons']) : 1;


            $insertQuery = 'INSERT INTO '.DBPREFIX.'module_forum_postings (
                            id,         category_id,         thread_id,             prev_post_id,
                            user_id,     time_created,         time_edited,         is_locked,
                            is_sticky,     views,                 icon,                 subject,
                            keywords,    content,            attachment
                        ) VALUES (
                            NULL, '.    $intForumId.', '.    $intLastThreadId.', 0,
                            '.$userId.', '.time().',         0,                    0,
                            0,             0, '.                $icon.", '".        addslashes($subject)."',
                            '".addslashes($keywords)."' ,'".contrexx_raw2db($content)."' , '".$fileInfo['name']."'
                        )";
            if($objDatabase->Execute($insertQuery) !== false){
                $lastInsertId = $objDatabase->Insert_ID();
                $this->_updateNotification($intLastThreadId);
                $this->_sendNotifications($intLastThreadId, $subject, $content);
                $this->updateViewsNewItem($intForumId, $lastInsertId);
                $pageId = \Cx\Core\Core\Controller\Cx::instanciate()->getPage()->getId();
                $cacheManager = new \Cx\Core_Modules\Cache\Controller\CacheManager();
                $cacheManager->deleteSingleFile($pageId);
            }
            \Cx\Core\Csrf\Controller\Csrf::header('Location: ?section=Forum&cmd=board&id='.$intForumId);
            die();
        }
    }


    /**
     * show thread
     *
     * @param integer $intThreadId
     * @return bool
     */
    function showThread($intThreadId)
    {
        global $objDatabase, $_ARRAYLANG;

        $objFWUser = \FWUser::getFWUserObject();
        $this->_communityLogin();
        $intThreadId = intval($intThreadId);

        if(!empty($_REQUEST['notification_update']) && $_REQUEST['notification_update'] == $_ARRAYLANG['TXT_FORUM_UPDATE_NOTIFICATION']){
            $this->_updateNotification($intThreadId);
        }

        $intCatId = !empty($_REQUEST['category_id']) ? intval($_REQUEST['category_id']) : '0';
        if($intCatId == 0){
            $intCatId = $this->_getCategoryIdFromThread($intThreadId);
        }

        if(empty($intCatId)){
            \Cx\Core\Csrf\Controller\Csrf::header('Location: index.php?section=Forum');
            die();
        }
        if ($objFWUser->objUser->login()) {
            $this->_objTpl->touchBlock('notificationRow');
        } else {
            $this->_objTpl->hideBlock('notificationRow');
        }

        $intPostId = !empty($_REQUEST['postid']) ? intval($_REQUEST['postid']) : 0;
        $intPostId = ($intPostId == 0 && !empty($_REQUEST['post_id'])) ? intval($_REQUEST['post_id']) : $intPostId;
        $this->_objTpl->setVariable('FORUM_EDIT_POST_ID', $intPostId);

        $_REQUEST['act'] = !empty($_REQUEST['act']) ? $_REQUEST['act'] : '';
        if($_REQUEST['act'] == 'delete'){
            if($this->_checkAuth($intCatId, 'delete')){
                if($this->_deletePost($intCatId, $intThreadId, $_REQUEST['postid'])){
                    $this->_objTpl->setVariable('TXT_FORUM_SUCCESS', '<br />'.$_ARRAYLANG['TXT_FORUM_DELETED_SUCCESSFULLY']);
                }else{
                    $this->_objTpl->setVariable('TXT_FORUM_ERROR', '<br />'.$_ARRAYLANG['TXT_FORUM_DELETE_FAILED']);
                }
            }else{
                $this->_objTpl->setVariable('TXT_FORUM_ERROR', '<br />'.$_ARRAYLANG['TXT_FORUM_NO_ACCESS']);
            }
        }

        $pos = !empty($_REQUEST['pos']) ? intval($_REQUEST['pos']) : 0;
        $this->_objTpl->setVariable(array(
            'FORUM_PAGING_POS'    =>    $pos
        ));

        if(!empty($_REQUEST['preview_new'])){
            $pos = $this->_getLastPos($intPostId, $intThreadId);
        }

        if(!empty($_REQUEST['postid'])){
            if($_REQUEST['act'] == 'quote'){
                $pos = $this->_getLastPos($intPostId, $intThreadId);
            }
            if($_REQUEST['act'] == 'edit'){
                $pos = $this->_getEditPos($intPostId, $intThreadId);
            }
        }

        if(!empty($_REQUEST['l']) && $_REQUEST['l'] == 1){
            $pos = $this->_getEditPos($intPostId, $intThreadId);
        }

        $arrPosts = $this->createPostArray($intThreadId, $pos);
        if(!empty($_REQUEST['preview_edit']) && $_REQUEST['post_id'] != 0 && $_REQUEST['act'] != 'quote'){
            $intPostId = intval($intPostId);
            $pos = $this->_getEditPos($intPostId, $intThreadId);
            $arrPosts = $this->createPostArray($intThreadId, $pos);
            $arrPosts[$intPostId]['subject'] = !empty($_REQUEST['subject']) ? contrexx_strip_tags($_REQUEST['subject']) : $_ARRAYLANG['TXT_FORUM_NO_SUBJECT'];
            $arrPosts[$intPostId]['content'] = \Cx\Core\Wysiwyg\Wysiwyg::prepareBBCodeForOutput(contrexx_input2raw($_REQUEST['message']));
        }

        $userId  = $objFWUser->objUser->login() ? $objFWUser->objUser->getId() : 0;
        $icon      = !empty($_REQUEST['icons']) ? intval($_REQUEST['icons']) : 1;


        if($_REQUEST['act'] == 'edit'){
            //submit is an edit
            $arrEditedPost = $this->_getPostingData($intPostId);
            $subject = addcslashes(htmlentities($arrEditedPost['subject'], ENT_QUOTES, CONTREXX_CHARSET), '\\');
            $content = $arrEditedPost['content'];
            $keywords =  addcslashes(htmlentities($arrEditedPost['keywords'], ENT_QUOTES, CONTREXX_CHARSET), '\\');
            $attachment = $arrEditedPost['attachment'];
            $this->_objTpl->setVariable('FORUM_POST_EDIT_USERID', $arrPosts[$intPostId]['user_id']);
            if(!empty($attachment)){
                $this->_objTpl->setVariable('TXT_FORUM_DELETE_ATTACHMENT', sprintf($_ARRAYLANG['TXT_FORUM_DELETE_ATTACHMENT'], $attachment));
            }
            $this->_objTpl->touchBlock('updatePost');
            $this->_objTpl->hideBlock('createPost');
            $this->_objTpl->hideBlock('previewNewPost');
            $this->_objTpl->touchBlock('previewEditPost');
        }else{
            //new post
            if($this->_objTpl->blockExists('delAttachment')){
                $this->_objTpl->hideBlock('delAttachment');
            }
            $subject = !empty($_REQUEST['subject']) ? contrexx_strip_tags($_REQUEST['subject']) : '';
            $content = !empty($_REQUEST['message']) ? contrexx_input2raw(strip_tags($_REQUEST['message'])) : '';
            $keywords = !empty($_REQUEST['keywords']) ? contrexx_strip_tags($_REQUEST['keywords']) : '';
            $attachment = !empty($_REQUEST['attachment']) ? contrexx_strip_tags($_REQUEST['attachment']) : '';
            $this->_objTpl->touchBlock('createPost');
            $this->_objTpl->hideBlock('updatePost');
            $this->_objTpl->touchBlock('previewNewPost');
            $this->_objTpl->hideBlock('previewEditPost');
        }

        if($_REQUEST['act'] == 'quote'){
            $quoteContent = $this->_getPostingData($intPostId);
            $subject = 'RE: '.addcslashes(htmlentities($quoteContent['subject'], ENT_QUOTES, CONTREXX_CHARSET), '\\');
            $content = '[quote='.$arrPosts[$intPostId]['user_name'].']'.strip_tags($quoteContent['content']).'[/quote]';
        }

        $firstPost = current($arrPosts);

        if($this->_arrSettings['wysiwyg_editor'] == 1) { //IF WYSIWIG enabled..
            $strMessageInputHTML = new \Cx\Core\Wysiwyg\Wysiwyg('message', $content, 'bbcode');
        }else{ //plain textarea
            $strMessageInputHTML = '<textarea style="width: 400px; height: 150px;" rows="5" cols="10" name="message">'. contrexx_raw2xhtml($content) .'</textarea>';
        }
        $this->_objTpl->setGlobalVariable(array(
            'FORUM_JAVASCRIPT_GOTO'                 =>    $this->getJavascript('goto'),
            'FORUM_JAVASCRIPT_DELETE'               =>    $this->getJavascript('deletePost'),
            'FORUM_JAVASCRIPT_SCROLLTO'             =>    $this->getJavascript('scrollto'),
            'FORUM_SCROLLPOS'                       =>    !empty($_REQUEST['scrollpos']) ? intval($_REQUEST['scrollpos']) : '0',
            'FORUM_JAVASCRIPT_INSERT_TEXT'          =>    $this->getJavascript('insertText',  array($intCatId, $intThreadId, $firstPost)),
            'FORUM_NAME'                            =>    $this->_shortenString($firstPost['subject'], $this->_maxStringlength),
            'FORUM_TREE'                            =>    $this->_createNavTree($intCatId).'<a title="'.$this->_arrTranslations[$intCatId][$this->_intLangId]['name'].'" href="index.php?section=Forum&amp;cmd=board&amp;id='.$intCatId.'">'.$this->_shortenString($this->_arrTranslations[$intCatId][$this->_intLangId]['name'], $this->_maxStringlength).'</a> > ' ,
            'FORUM_DROPDOWN'                        =>    $this->createForumDD('forum_quickaccess', $intCatId, 'onchange="gotoForum(this);"', ''),
            'TXT_FORUM_COMMA_SEPARATED_KEYWORDS'    =>    $_ARRAYLANG['TXT_FORUM_COMMA_SEPARATED_KEYWORDS'],
            'TXT_FORUM_KEYWORDS'                    =>    $_ARRAYLANG['TXT_FORUM_KEYWORDS'],
            'TXT_FORUM_FILE_ATTACHMENT'             =>    $_ARRAYLANG['TXT_FORUM_FILE_ATTACHMENT'],
            'TXT_FORUM_RATING'                      =>    $_ARRAYLANG['TXT_FORUM_RATING'],
            'TXT_FORUM_ADD_POST'                    =>    $_ARRAYLANG['TXT_FORUM_ADD_POST'],
            'TXT_FORUM_SUBJECT'                     =>    $_ARRAYLANG['TXT_FORUM_SUBJECT'],
            'TXT_FORUM_MESSAGE'                     =>    $_ARRAYLANG['TXT_FORUM_MESSAGE'],
            'TXT_FORUM_RESET'                       =>    $_ARRAYLANG['TXT_FORUM_RESET'],
            'TXT_FORUM_CREATE_POST'                 =>    $_ARRAYLANG['TXT_FORUM_CREATE_POST'],
            'TXT_FORUM_ICON'                        =>    $_ARRAYLANG['TXT_FORUM_ICON'],
            'TXT_FORUM_QUOTE'                       =>    $_ARRAYLANG['TXT_FORUM_QUOTE'],
            'TXT_FORUM_EDIT'                        =>    $_ARRAYLANG['TXT_FORUM_EDIT'],
            'TXT_FORUM_DELETE'                      =>    $_ARRAYLANG['TXT_FORUM_DELETE'],
            'TXT_FORUM_PREVIEW'                     =>    $_ARRAYLANG['TXT_FORUM_PREVIEW'],
            'TXT_FORUM_UPDATE_POST'                 =>    $_ARRAYLANG['TXT_FORUM_UPDATE_POST'],
            'TXT_FORUM_NOTIFY_NEW_POSTS'            =>    $_ARRAYLANG['TXT_FORUM_NOTIFY_NEW_POSTS'],
            'TXT_FORUM_QUICKACCESS'                 =>    $_ARRAYLANG['TXT_FORUM_QUICKACCESS'],
            'TXT_FORUM_UPDATE_NOTIFICATION'         =>    $_ARRAYLANG['TXT_FORUM_UPDATE_NOTIFICATION'],
            'TXT_FORUM_THREAD_ACTION_DESC'          =>    $_ARRAYLANG['TXT_FORUM_THREAD_ACTION_DESC'],
            'TXT_FORUM_THREAD_ACTION_MOVE'          =>    $_ARRAYLANG['TXT_FORUM_THREAD_ACTION_MOVE'],
            'TXT_FORUM_THREAD_ACTION_CLOSE'         =>    $_ARRAYLANG['TXT_FORUM_THREAD_ACTION_CLOSE_'.$firstPost['is_locked']],
            'TXT_FORUM_THREAD_ACTION_STICKY'        =>    $_ARRAYLANG['TXT_FORUM_THREAD_ACTION_STICKY_'.$firstPost['is_sticky']],
            'TXT_FORUM_THREAD_ACTION_DELETE'        =>    $_ARRAYLANG['TXT_FORUM_THREAD_ACTION_DELETE'],
            'TXT_FORUM_CHOOSE_FILE'                 =>    $_ARRAYLANG['TXT_FORUM_CHOOSE_FILE'],
            'FORUM_NOTIFICATION_CHECKBOX_CHECKED'   =>    $this->_hasNotification($intThreadId) ? 'checked="checked"' : '',
            'FORUM_SUBJECT'                         =>    stripslashes($subject),
            'FORUM_KEYWORDS'                        =>    stripslashes($keywords),
            'FORUM_ATTACHMENT_OLDNAME'              =>    $attachment,
            'FORUM_MESSAGE_INPUT'                   =>    $strMessageInputHTML,
            'FORUM_CAPTCHA_CODE'                    =>    \Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->getCode(),
            'FORUM_THREAD_ID'                       =>    $intThreadId,
            'FORUM_CATEGORY_ID'                     =>    $intCatId,
            'FORUM_POSTS_PAGING'                    =>    getPaging($this->_postCount, $pos, '&section=Forum&cmd=thread&id='.$intThreadId, $_ARRAYLANG['TXT_FORUM_OVERVIEW_POSTINGS'], true, $this->_arrSettings['posting_paging']),
        ));

        if ($objFWUser->objUser->login()) {
            $this->_objTpl->hideBlock('captcha');
        } else {
            $this->_objTpl->touchBlock('captcha');
        }

        $this->_setIcons($this->_getIcons());

        if(!$this->_checkAuth($intCatId, 'read')){
            $this->_objTpl->setVariable('TXT_FORUM_ERROR', '<br />'.$_ARRAYLANG['TXT_FORUM_NO_ACCESS']);
            return false;
        }

        $intCounter    = 0;
        foreach ($arrPosts as $postId => $arrValues) {
            $strRating = '<span id="forum_current_rating_'.$postId.'" class="rating_%s">%s</span>';
            if($arrValues['rating'] == 0){
                $class = 'none';
            } elseif ($arrValues['rating'] > 0){
                $class = 'pos';
            } else {
                $class = 'neg';
            }
            $strRating = sprintf($strRating, $class, $arrValues['rating']);
            $strUserProfileLink = ($arrValues['user_id'] > 0) ? '<a title="'.$arrValues['user_name'].'" href="index.php?section=Access&amp;cmd=user&amp;id='.$arrValues['user_id'].'">'.$arrValues['user_name'].'</a>' : $this->_anonymousName;

            $arrAttachment = $this->_getAttachment($arrValues['attachment']);
            $this->_objTpl->setGlobalVariable(array(
                'FORUM_POST_ROWCLASS'            =>    ($intCounter++ % 2) + 1,
            ));

            $quoteLink = "id=".$intThreadId."&act=quote&postid=".$postId;
            $quoteLinkLoggedIn      = "location.href='".\Cx\Core\Csrf\Controller\Csrf::enhanceURI("index.php?section=Forum")."&amp;cmd=thread&amp;".htmlentities($quoteLink)."';";
            $quoteLinkNotLoggedIn   = "location.href='".\Cx\Core\Csrf\Controller\Csrf::enhanceURI("index.php?section=Login")."&amp;redirect=".base64_encode("index.php?section=Forum&cmd=thread&".$quoteLink)."';";
            $this->_objTpl->setVariable(array(
                'FORUM_POST_DATE'                   => $arrValues['time_created'],
                'FORUM_POST_LAST_EDITED'            => ($arrValues['time_edited'] != date(ASCMS_DATE_FORMAT, 0))
                                                        ? $_ARRAYLANG['TXT_FORUM_LAST_EDITED'].$arrValues['time_edited']
                                                        : '',
                'FORUM_USER_ID'                     => $arrValues['user_id'],
                'FORUM_USER_NAME'                   => $strUserProfileLink,
                'FORUM_USER_IMAGE'                  => !empty($arrValues['user_image'])
                                                        ? '<img border="0" width="60" height="60" src="'.$arrValues['user_image'].'" title="'
                                                          .$arrValues['user_name'].'\'s avatar" alt="'.$arrValues['user_name'].'\'s avatar" />'
                                                        : '',
                'FORUM_USER_GROUP'                  => '',
                'FORUM_USER_RANK'                   => '',
                'FORUM_USER_REGISTERED_SINCE'       => '',
                'FORUM_USER_POSTING_COUNT'          => '',
                'FORUM_USER_CONTACTS'               => '',
                'FORUM_POST_NUMBER'                 => '#'.$arrValues['post_number'],
                'FORUM_POST_ICON'                   => $arrValues['post_icon'],
                'FORUM_POST_SUBJECT'                => $arrValues['subject'],
                'FORUM_POST_MESSAGE'                => $arrValues['content'],
                'FORUM_POST_RATING'                 => $strRating,
                'FORUM_POST_ATTACHMENT_LINK'        => $arrAttachment['webpath'],
                'FORUM_POST_ATTACHMENT_FILENAME'    => $arrAttachment['name'],
                'FORUM_POST_ATTACHMENT_ICON'        => $arrAttachment['icon'],
                'FORUM_POST_ATTACHMENT_FILESIZE'    => $arrAttachment['size'],
                'FORUM_QUOTE_ONCLICK'               => $this->_checkAuth($intCatId, 'write')
                                                        ? $quoteLinkLoggedIn
                                                        : $quoteLinkNotLoggedIn,
            ));

            if(!$objFWUser->objUser->login() && !$this->_checkAuth($intCatId, 'write')){
                $button = '<input type="button" value="'.$_ARRAYLANG['TXT_FORUM_CREATE_POST'].'" onclick="location.href=\''.\Cx\Core\Csrf\Controller\Csrf::enhanceURI('index.php?section=Login').'&redirect='.base64_encode($_SERVER['REQUEST_URI']).'\';" />';
                $this->_objTpl->setVariable(array(
                    'FORUM_POST_REPLY_REDIRECT'     =>  $button,
                ));
            }


            $this->_objTpl->setVariable(array(
                'FORUM_POST_ID'         => $postId,
                'FORUM_RATING_POST_ID'     => $postId
                ));
            if($firstPost['is_locked'] != 1 && ($this->_checkAuth($intCatId, 'edit') || ($objFWUser->objUser->login() && $arrValues['user_id'] == $objFWUser->objUser->getId()))) {
                $this->_objTpl->touchBlock('postEdit');
            } else {
                $this->_objTpl->hideBlock('postEdit');
            }

            if($firstPost['is_locked'] != 1 && ($this->_checkAuth($intCatId, 'write') || !$firstPost['is_locked'])){
                $this->_objTpl->touchBlock('postQuote');
            }else{
                $this->_objTpl->hideBlock('postQuote');
            }

            if($this->_checkAuth($intCatId, 'delete') && $arrValues['post_number'] != 1){
                $this->_objTpl->setVariable(array(
                    'FORUM_POST_ID'     => $postId,
                ));
                $this->_objTpl->touchBlock('postDelete');
            }else{
                $this->_objTpl->hideBlock('postDelete');
            }

            if($this->_objTpl->blockExists('rating')){
                if($objFWUser->objUser->login() && !$this->_hasRated($postId)){
                    $this->_objTpl->parse('rating');
                }else{
                    $this->_objTpl->hideBlock('rating');
                }
            }

            if($this->_objTpl->blockExists('attachment')){
                if(!empty($arrValues['attachment'])){
                    $this->_objTpl->parse('attachment');
                } else {
                    $this->_objTpl->hideBlock('attachment');
                }
            }

            $this->_objTpl->parse('forumPosts');
        }

        if(!$this->_checkAuth($intCatId, 'write') || $firstPost['is_locked'] == 1){
            $this->_objTpl->hideBlock('addPost');
            $this->_objTpl->hideBlock('addPostAnchor');
        }else{
            $this->_objTpl->touchBlock('addPostAnchor');
        }
        // initialize the uploader
        $this->initForumUploader();

        //addpost code
        if(!empty($_REQUEST['create']) && $_REQUEST['create'] == $_ARRAYLANG['TXT_FORUM_CREATE_POST']){
            if(!$this->_checkAuth($intCatId, 'write') && $firstPost['is_locked']  != 1){//auth check
                $this->_objTpl->setVariable('TXT_FORUM_ERROR', '<br />'.$_ARRAYLANG['TXT_FORUM_NO_ACCESS']);
                $this->_objTpl->hideBlock('addPost');
                return false;
            }
            if(!$objFWUser->objUser->login() && !\Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->check()) {//captcha check
                return false;
            }
            if(strlen(trim($content)) < $this->_minPostlength){//content check
                $this->_objTpl->setVariable('TXT_FORUM_ERROR', sprintf('<br />'.$_ARRAYLANG['TXT_FORUM_POST_EMPTY'], $this->_minPostlength));
                return false;
            }

            if(false !== ($match = $this->_hasBadWords($content))){
                $this->_objTpl->setVariable('TXT_FORUM_ERROR', sprintf('<br />'.$_ARRAYLANG['TXT_FORUM_BANNED_WORD'], $match[1]));
                return false;
            }
            $fileInfo = $this->_handleUpload('forum_attachment');
            if($fileInfo === false){ //an error occured, the file wasn't properly transferred. exit function to display error set in _handleUpload()
                return false;
            }

            $lastPostIdQuery = '    SELECT max( id ) as last_post_id
                                    FROM '.DBPREFIX.'module_forum_postings
                                    WHERE category_id = '.$intCatId.'
                                    AND      thread_id = '.$intThreadId;
            if( ($objRSmaxId = $objDatabase->SelectLimit($lastPostIdQuery, 1)) !== false){
                $intPrevPostId = $objRSmaxId->fields['last_post_id'];
            }else{
                die('Database error: '.$objDatabase->ErrorMsg());
            }

            $insertQuery = 'INSERT INTO '.DBPREFIX.'module_forum_postings (
                            id,             category_id,    thread_id,            prev_post_id,
                            user_id,         time_created,    time_edited,         is_locked,
                            is_sticky,         rating,         views,                 icon,
                            keywords,        subject,        content,             attachment
                        ) VALUES (
                            NULL, '.        $intCatId.', '.    $intThreadId.', '.$intPrevPostId.',
                            '.$userId.', '.    time().',         0,                     0,
                            0,                   0,        0, '.            $icon.",
                            '$keywords' ,'".$subject."',    '".contrexx_raw2db($content)."', '".$fileInfo['name']."'
                        )";

            if($objDatabase->Execute($insertQuery) !== false){
                $lastInsertId = $objDatabase->Insert_ID();
                $this->updateViewsNewItem($intCatId, $lastInsertId, true);
                $this->_updateNotification($intThreadId);
                $this->_sendNotifications($intThreadId, $subject, $content);
                $pageId = \Cx\Core\Core\Controller\Cx::instanciate()->getPage()->getId();
                $cacheManager = new \Cx\Core_Modules\Cache\Controller\CacheManager();
                $cacheManager->deleteSingleFile($pageId);
            }
            \Cx\Core\Csrf\Controller\Csrf::header('Location: index.php?section=Forum&cmd=thread&id='.$intThreadId.'&pos='.$this->_getLastPos($postId, $intThreadId));
            die();
        }

        if(!empty($_REQUEST['preview_new'])){
            $content = \Cx\Core\Wysiwyg\Wysiwyg::prepareBBCodeForOutput($content);
            if(false !== ($match = $this->_hasBadWords($content))){
                $this->_objTpl->setVariable('TXT_FORUM_ERROR', sprintf('<br />'.$_ARRAYLANG['TXT_FORUM_BANNED_WORD'], $match[1]));
                return false;
            }
            if(strlen(trim($content)) < $this->_minPostlength){//content check
                $this->_objTpl->setVariable('TXT_FORUM_ERROR', sprintf('<br />'.$_ARRAYLANG['TXT_FORUM_POST_EMPTY'], $this->_minPostlength));
                return false;
            }
            $this->_objTpl->setVariable(array(
                'FORUM_POST_ROWCLASS'            =>    ($intCounter++ % 2) + 1,
                'FORUM_POST_DATE'                =>    date(ASCMS_DATE_FORMAT, time()),
                'FORUM_USER_ID'                    =>    $userId,
                'FORUM_USER_NAME'                =>    $objFWUser->objUser->login() ? '<a href="index.php?section=Access&amp;cmd=user&amp;id='.$userId.'" title="'.htmlentities($objFWUser->objUser->getUsername(), ENT_QUOTES, CONTREXX_CHARSET).'">'.htmlentities($objFWUser->objUser->getUsername(), ENT_QUOTES, CONTREXX_CHARSET).'</a>' : $this->_anonymousName,
                'FORUM_USER_IMAGE'                =>    !empty($arrValues['user_image']) ? '<img border="0" width="60" height="60" src="'.$arrValues['user_image'].'" title="'.$arrValues['user_name'].'\'s avatar" alt="'.$arrValues['user_name'].'\'s avatar" />' : '',
                'FORUM_USER_GROUP'                =>    '',
                'FORUM_USER_RANK'                =>    '',

                'FORUM_USER_REGISTERED_SINCE'    =>    '',
                'FORUM_USER_POSTING_COUNT'        =>    '',
                'FORUM_USER_CONTACTS'            =>    '',

                'FORUM_POST_NUMBER'                =>    '#'.($this->_postCount+1),
                'FORUM_POST_ICON'                =>    $this->getThreadIcon($icon),
                'FORUM_POST_SUBJECT'            =>    stripslashes($subject),
                'FORUM_POST_MESSAGE'            =>    $content,
                'FORUM_POST_RATING'                =>    '0',
            ));
            $this->_objTpl->touchBlock('createPost');
            $this->_objTpl->hideBlock('updatePost');
            if($this->_objTpl->blockExists('attachment')){
                $this->_objTpl->hideBlock('attachment');
            }
            $this->_objTpl->hideBlock('postEdit');
            $this->_objTpl->hideBlock('postQuote');
            $this->_objTpl->touchBlock('previewNewPost');
            $this->_objTpl->hideBlock('previewEditPost');
            $this->_objTpl->parse('forumPosts');
        }


        if(!empty($_REQUEST['update']) && $_REQUEST['update'] == $_ARRAYLANG['TXT_FORUM_UPDATE_POST']){
            if(strlen(trim($content)) < $this->_minPostlength){//content size check
                $this->_objTpl->setVariable('TXT_FORUM_ERROR', sprintf('<br />'.$_ARRAYLANG['TXT_FORUM_POST_EMPTY'], $this->_minPostlength));
                return false;
            }
            if (!$this->_checkAuth($intCatId, 'edit') && (!$objFWUser->objUser->login() || $arrValues['user_id'] != $objFWUser->objUser->getId())) {
                $this->_objTpl->setVariable('TXT_FORUM_ERROR', '<br />'.$_ARRAYLANG['TXT_FORUM_NO_ACCESS']);
                $this->_objTpl->hideBlock('postEdit');
                return false;
            }
            if (!$objFWUser->objUser->login() && !\Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->check()) {
                $this->_objTpl->touchBlock('updatePost');
                $this->_objTpl->hideBlock('createPost');
                return false;
            }
            if(false !== ($match = $this->_hasBadWords($content))){
                $this->_objTpl->setVariable('TXT_FORUM_ERROR', sprintf('<br />'.$_ARRAYLANG['TXT_FORUM_BANNED_WORD'], $match[1]));
                return false;
            }
            $fileInfo = $this->_handleUpload('forum_attachment');
            if($fileInfo === false){ //an error occured, the file wasn't properly transferred. exit function to display error set in _handleUpload()
                return false;
            }

            if (empty($_POST['forum_delete_attachment']) &&
                    empty($fileInfo['name']) &&
                    !empty($_REQUEST['forum_attachment_oldname'])){
                $fileInfo['name'] = contrexx_addslashes($_REQUEST['forum_attachment_oldname']);
            } elseif ((
                        !empty($_POST['forum_delete_attachment']) &&
                        $_POST['forum_delete_attachment'] == 1
                    ) || (
                        !empty($_REQUEST['forum_attachment_oldname']) &&
                        $fileInfo['name'] != $_REQUEST['forum_attachment_oldname']
                    )
                ){
                unlink(\Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteMediaForumUploadPath() . '/' . str_replace(array('./', '.\\'), '', $_REQUEST['forum_attachment_oldname']));
            }

            $updateQuery = 'UPDATE '.DBPREFIX.'module_forum_postings SET
                            time_edited = '.mktime().',
                            icon = '.$icon.',
                            subject = \''.$subject.'\',
                            keywords = \''.$keywords.'\',
                            content = \''.contrexx_raw2db($content).'\',
                            attachment = \''.$fileInfo['name'].'\'
                            WHERE id = '.$intPostId;

            if($objDatabase->Execute($updateQuery) !== false){
                $this->updateViews($intThreadId, $intPostId);
                $pageId = \Cx\Core\Core\Controller\Cx::instanciate()->getPage()->getId();
                $cacheManager = new \Cx\Core_Modules\Cache\Controller\CacheManager();
                $cacheManager->deleteSingleFile($pageId);
            }

            \Cx\Core\Csrf\Controller\Csrf::header('Location: index.php?section=Forum&cmd=thread&id='.$intThreadId.'&pos='.$this->_getLastPos($postId, $intThreadId));
            die();
        }

        if(!empty($_REQUEST['preview_edit'])){
            $this->_objTpl->touchBlock('updatePost');
            $this->_objTpl->hideBlock('createPost');
            $this->_objTpl->hideBlock('previewNewPost');
            $this->_objTpl->touchBlock('previewEditPost');
        }

        $hasAccess = false;
        foreach (array('STICKY', 'MOVE', 'CLOSE', 'DELETE') as $action){
            if(!$this->_checkAuth($intCatId, strtolower($action))){
                $this->_objTpl->setVariable('FORUM_THREAD_ACTIONS_DISABLED_'.$action, 'disabled="disabled"');
            }else{
               $hasAccess = true;
            }
        }


        if($this->_objTpl->blockExists('threadActionsSelect')){
            if($userId < 1 || !$hasAccess){
                $this->_objTpl->hideBlock('threadActionsSelect');
            }else{
                $this->_objTpl->touchBlock('threadActionsSelect');
            }
        }


        if(!empty($_REQUEST['action']) && $_REQUEST['action'] == 'move' && !empty($_REQUEST['id'])){
            $thread = intval($_REQUEST['id']);
            $newCat = intval($_REQUEST['moveToThread']);
            $oldCat = $this->_getCategoryIdFromThread($thread);
            $query = "UPDATE `".DBPREFIX."module_forum_postings` SET `category_id` = $newCat WHERE `thread_id` = ".$thread;
            if($objDatabase->Execute($query)){
                $intMovedPosts = $objDatabase->Affected_Rows();

                $query = "SELECT max( `id` ) as `lastid` FROM `".DBPREFIX."module_forum_postings` WHERE `thread_id` = $thread";
                $objRS = $objDatabase->SelectLimit($query, 1);
                $intMovedPostLastId = $objRS->fields['lastid'];

                $query = "SELECT max( `id` ) as `lastid` FROM `".DBPREFIX."module_forum_postings` WHERE `category_id` = $oldCat";
                $objRS = $objDatabase->SelectLimit($query, 1);

                $query = "UPDATE `".DBPREFIX."module_forum_statistics` SET `thread_count` = `thread_count` - 1, `post_count` = `post_count` - $intMovedPosts, `last_post_id` = ".(intval($objRS->fields['lastid']) > 0 ? intval($objRS->fields['lastid']) : 0)." WHERE `category_id` = $oldCat";
                $objDatabase->Execute($query);

                $query = "SELECT `id` FROM `".DBPREFIX."module_forum_postings` WHERE `category_id` = $newCat GROUP BY `time_created` DESC";
                $objRS = $objDatabase->Execute($query);

                $query = "UPDATE `".DBPREFIX."module_forum_statistics` SET `thread_count` = `thread_count` + 1, `post_count` = `post_count` + $intMovedPosts, `last_post_id` = ".$objRS->fields['id']." WHERE `category_id` = $newCat";
                $objDatabase->Execute($query);

                $this->_objTpl->hideBlock('moveForm');
                $this->_objTpl->setVariable(array(
                    'TXT_THREAD_ACTION_'.($success ? 'SUCCESS' : 'ERROR')   => $_ARRAYLANG['TXT_FORUM_THREAD_ACTION_MOVE'.(!$success ? 'UN' : '').'SUCCESSFUL'],
                    'FORUM_CATEGORY_ID'                                     => $intCatId,
                    'FORUM_THREAD_ID'                                       => $intThreadId,
                ));
                \Cx\Core\Csrf\Controller\Csrf::header('Location: index.php?section=Forum&cmd=thread&id='.$thread);
            }
        }

        if(!empty($_GET['a'])){
            $this->_objTpl->setVariable(array(
                'TXT_FORUM_'.($_GET['r'] == 1 ? 'SUCCESS' : 'ERROR')   => '<br />'.$_ARRAYLANG['TXT_FORUM_THREAD_ACTION_'.strtoupper($_GET['a']).'_'.(!$_GET['r'] ? 'UN' : '').'SUCCESSFUL'.$_GET['s']],
            ));
        }

        $success = false;
        if(!empty($_REQUEST['thread_actions'])){
            $action = contrexx_addslashes($_REQUEST['thread_actions']);
            if($this->_checkAuth($intCatId, $action)){
                switch($action){
                    case 'move':
                        $arrForums = $this->createForumArray($this->_intLangId);
                        foreach ($arrForums as $intCatID => $arrThread){
                            $strOptions .= '<option value="'.$intCatID.'" '.($arrThread['level'] == 0 ? 'disabled="disabled"' : '').'>'.(str_repeat('&nbsp;', ($arrThread['level']*2))).$arrThread['name'].'</option>';
                        }
                        $this->_objTpl->setVariable(array(
                            'FORUM_THREADS'    =>    $strOptions,
                        ));
                        $success = true;
                        $suffix = '';
                    break;
                    case 'close':
                        $query = "UPDATE `".DBPREFIX."module_forum_postings` SET `is_locked` = IF(`is_locked` = '0' OR `is_locked` = '', '1', '0') WHERE thread_id = ".intval($_REQUEST['id']);
                        if($objDatabase->Execute($query) !== false){
                            $success = true;
                        }
                        $suffix = '_'.$firstPost['is_locked'];
                    break;

                    case 'sticky':
                        $query = "UPDATE `".DBPREFIX."module_forum_postings` SET `is_sticky` = IF(`is_sticky` = '0' OR `is_sticky` = '', '1', '0') WHERE thread_id = ".intval($_REQUEST['id']);
                        if($objDatabase->Execute($query) !== false){
                            $success = true;
                        }
                        $suffix = '_'.$firstPost['is_sticky'];
                    break;

                    default:
                    break;
                }
                if($action != 'move'){
                    \Cx\Core\Csrf\Controller\Csrf::header('Location: index.php?section=Forum&cmd=thread&id='.$intThreadId.'&a='.$action.'&r='.$success.'&s='.$suffix);
                }
            }else{
                $this->_objTpl->setVariable('TXT_THREAD_ACTION_ERROR', $_ARRAYLANG['TXT_FORUM_NO_ACCESS']);
            }

            $this->_objTpl->parse('threadActions');
            $this->_objTpl->touchBlock('threadActions');
            $this->_objTpl->hideBlock('threadDisplay');
        }else{
            $this->updateViews($intThreadId, $intPostId);
            $this->_objTpl->hideBlock('threadActions');
        }
        return true;
    }

    /**
     * Get page title by thread id
     *
     * @return string
     */
    public function getPageTitle()
    {
        global $_ARRAYLANG;

        $threadId = contrexx_input2int($_GET['id']);
        if (
            empty($_REQUEST['thread_actions']) ||
            $_REQUEST['thread_actions'] !== 'move'
        ) {
            return;
        }

        if (!empty($_REQUEST['category_id'])) {
            $catId = contrexx_input2int($_REQUEST['category_id']);
        } else {
            $catId = $this->_getCategoryIdFromThread($threadId);
        }

        if (!$this->_checkAuth($catId, 'move')) {
            return;
        }

        return $_ARRAYLANG['TXT_FORUM_THREAD_ACTION_MOVE'];
    }

    /**
     * Uploader callback function
     *
     * This is called as soon as uploads have finished.
     *
     * @param string  $tempPath    Path to the temporary directory containing the files at this moment
     * @param string  $tempWebPath Points to the same folder as tempPath, but relative to the webroot
     * @param array   $data        Data given to setData() when creating the uploader
     * @param string  $uploadId    upload id
     * @param array   $fileInfos   uploaded file informations
     * @param object  $response    Upload api response object
     *
     * @return array $tempPath and $tempWebPath
     */
    public static function uploadFinished($tempPath, $tempWebPath, $data, $uploadId, $fileInfos, $response)
    {
        // in case uploader has been restricted to only allow one single file to be
        // uploaded, we'll have to clean up any previously uploaded files
        if (count($fileInfos['name'])) {
            // new files have been uploaded -> remove existing files
            if (\Cx\Lib\FileSystem\FileSystem::exists($tempPath)) {
                foreach (glob($tempPath.'/*') as $file) {
                    if (basename($file) == $fileInfos['name']) {
                        continue;
                    }
                    \Cx\Lib\FileSystem\FileSystem::delete_file($file);
                }
            }
        }
        return array($tempPath, $tempWebPath);
    }

    function _hasNotification($intThreadId)
    {
        global $objDatabase;

        $objFWUser = \FWUser::getFWUserObject();
        if (!$objFWUser->objUser->login()) {
            return false;
        }
        $query = '    SELECT 1 FROM `'.DBPREFIX.'module_forum_notification`
                        WHERE `thread_id` = '.$intThreadId.'
                        AND `user_id` = '.$objFWUser->objUser->getId();
        if(($objRS = $objDatabase->SelectLimit($query, 1)) !== false){
            if($objRS->RecordCount() > 0){
                return true;
            }else{
                return false;
            }
        }else{
            die('Database error: '.$objDatabase->ErrorMsg());
        }
    }

    /**
     * update the notifications table
     *
     * @param integer $intThreadId
     * @return void
     */
    function _updateNotification($intThreadId)
    {
        global $objDatabase;

        $objFWUser = \FWUser::getFWUserObject();
        if(!empty($_REQUEST['notification']) && $_REQUEST['notification'] == 'notify'){
            $query = '    SELECT 1 FROM `'.DBPREFIX.'module_forum_notification`
                        WHERE `thread_id` = '.$intThreadId.'
                        AND `category_id` = 0
                        AND `user_id` = '.($objFWUser->objUser->login() ? $objFWUser->objUser->getId() : 0);
            if(($objRS=$objDatabase->SelectLimit($query, 1)) !== false){
                if($objRS->RecordCount() > 0){
                    $query = '    UPDATE `'.DBPREFIX.'module_forum_notification`
                                  SET `thread_id` = '.$intThreadId.', `user_id` = '.($objFWUser->objUser->login() ? $objFWUser->objUser->getId() : 0).', `is_notified` = \'0\'
                                  WHERE `thread_id` = '.$intThreadId.'
                                  AND `user_id` = '.($objFWUser->objUser->login() ? $objFWUser->objUser->getId() : 0);
                }else{
                    $query = '    INSERT INTO `'.DBPREFIX.'module_forum_notification`
                                  SET `thread_id` = '.$intThreadId.',
                                      `user_id` = '.($objFWUser->objUser->login() ? $objFWUser->objUser->getId() : 0).',
                                      `is_notified` = \'0\'';
                }

            }
        }else{//$_REQUEST['notification'] empty/wrong, remove notification
            $query = '    DELETE FROM `'.DBPREFIX.'module_forum_notification`
                          WHERE `thread_id` = '.$intThreadId.'
                          AND `category_id` = 0
                          AND `user_id` = '.($objFWUser->objUser->login() ? $objFWUser->objUser->getId() : 0);
        }

        if($objDatabase->Execute($query) === false){
            die('Database error: '.$objDatabase->ErrorMsg());
        }
    }


    /**
     * send email notifications
     *
     * @param integer $intThreadId
     * @param string $strSubject subject of the last message in the thread
     * @param string $strContent content of the last message in the thread
     * @return void
     */
    function _sendNotifications($intThreadId, $strSubject, $strContent){
        global $objDatabase, $_CONFIG;

        $arrTempSubcribers = array();
        $arrSubscribers = array();

        $intCategoryId = $this->_getCategoryIdFromThread($intThreadId);

        $mail  = new \Cx\Core\MailTemplate\Model\Entity\Mail();
        $query = '    SELECT `subject`, `user_id` FROM `'.DBPREFIX.'module_forum_postings`
                    WHERE `thread_id` = '.$intThreadId.'
                    AND `prev_post_id` = 0';

        if(($objRS = $objDatabase->SelectLimit($query, 1)) !== false){
            $strFirstPostSubject = $objRS->fields['subject'];
            $strFirstPostAuthor = $this->_getUserName($objRS->fields['user_id']);
        }else{
            die('Database error: '.$objDatabase->ErrorMsg());
        }

        //fetch thread subscribers
        $query = '    SELECT `users`.`username`, `users`.`email`, `users`.`id`
                    FROM `'.DBPREFIX.'access_users` AS `users`
                    INNER JOIN `'.DBPREFIX.'module_forum_notification` AS `notification` ON `users`.`id` = `notification`.`user_id`
                    WHERE `notification`.`thread_id` = '.$intThreadId.'
                    AND `notification`.`category_id` = 0';
        if(($objRS = $objDatabase->Execute($query)) !== false){
            while(!$objRS->EOF){
                $arrTempSubcribers[] = $objRS->fields;
                $objRS->MoveNext();
            }
        }

        //fetch category subscribers
        $query = '    SELECT `users`.`username`, `users`.`email`, `users`.`id`
                    FROM `'.DBPREFIX.'access_users` AS `users`
                    INNER JOIN `'.DBPREFIX.'module_forum_notification` AS `notification` ON `users`.`id` = `notification`.`user_id`
                    WHERE `notification`.`category_id` = '.$intCategoryId.'
                    AND `notification`.`thread_id` = 0';
        if(($objRS = $objDatabase->Execute($query)) !== false){
            while(!$objRS->EOF){
                $arrTempSubcribers[] = $objRS->fields;
                $objRS->MoveNext();
            }
        }

        foreach ($arrTempSubcribers as $entry) {
            if(!in_array($entry, $arrSubscribers)){
                $arrSubscribers[] = $entry;
            }
        }

        if(!empty($arrSubscribers)){
            $mail->IsHTML(false);
            $mail->SetFrom($this->_arrSettings['notification_from_email'], $this->_arrSettings['notification_from_name']);
            $strThreadURL = 'http://'.$_CONFIG['domainUrl'].CONTREXX_SCRIPT_PATH.'?section=Forum&cmd=thread&id='.$intThreadId;
            $arrSearch      = array('[[FORUM_THREAD_SUBJECT]]', '[[FORUM_THREAD_STARTER]]', '[[FORUM_LATEST_SUBJECT]]',    '[[FORUM_LATEST_MESSAGE]]',    '[[FORUM_THREAD_URL]]');
            $arrReplace     = array($strFirstPostSubject,         $strFirstPostAuthor,         $strSubject,                $strContent,                 $strThreadURL);

            $_strMailTemplate = html_entity_decode(str_replace($arrSearch, $arrReplace, $this->_arrSettings['notification_template']));
            $_strMailSubject  = html_entity_decode(str_replace($arrSearch, $arrReplace, $this->_arrSettings['notification_subject']));

            $objFWUser = \FWUser::getFWUserObject();

            foreach ($arrSubscribers as $arrSubscriber) {
                if($objFWUser->objUser->login() && $arrSubscriber['id'] == $objFWUser->objUser->getId()){//creator of the new post/thread doesn't want a notification
                    continue;
                }
                $mail->ClearAddresses();
                $strUsername = htmlentities($arrSubscriber['username'], ENT_QUOTES, CONTREXX_CHARSET);
                $strMailTemplate = str_replace('[[FORUM_USERNAME]]', $strUsername, $_strMailTemplate);
                $strMailSubject  = str_replace('[[FORUM_USERNAME]]', $strUsername, $_strMailSubject);

                $mail->AddAddress($arrSubscriber['email']);
                $mail->Subject = stripslashes(contrexx_strip_tags($strMailSubject));
                $mail->Body    = stripslashes(contrexx_strip_tags($strMailTemplate));
                $mail->Send();
            }
        }
    }


    /**
     * parse the icons into the current template
     *
     * @param array $arrIcons array containing the icons (see $this->_getIcons())
     * @param string $strBlockName name of the block to parse
     */

    function _setIcons($arrIcons, $strBlockName = 'icons')
    {
        $iconPath = ASCMS_MODULE_WEB_PATH.'/Forum/View/Media/thread/';
        $this->_objTpl->setVariable('FORUM_ICON_CHECKED', 'checked="checked"');
        foreach ($arrIcons as $index => $image) {
            $this->_objTpl->setVariable(array(
                'FORUM_ICON_VALUE'     => $index,
                'FORUM_ICON_SRC'    => $iconPath.$image,
                'FORUM_ICON_ALT'    => $iconPath.$image,
                'FORUM_ICON_TITLE'    => $iconPath.$image,
            ));
            $this->_objTpl->parse($strBlockName);
        }
    }

    /**
     * read icons from filesystem
     *
     * @return array $arrDir contains images
     */
    function _getIcons()
    {
        $iconDir = dir(ASCMS_MODULE_PATH.'/Forum/View/Media/thread');
        while (false !== ($entry = $iconDir->read())) {
            if(($index = intval($entry)) > 0 && substr($entry, -4) == '.gif'){
                $arrDir[$index] = $entry;
            }
        }
        return $arrDir;
    }



    /**
     * show category
     *
     * @param integer $intCatId
     * @return void
     */
    function showCategory($intCatId)
    {
        global $objDatabase, $_ARRAYLANG;

        $this->_communityLogin();

        $intCatId = intval($intCatId);
        $pos = !empty($_REQUEST['pos']) ? intval($_REQUEST['pos']) : 0;

        $this->_objTpl->setVariable(array(
            'FORUM_NAME'            =>    $this->_shortenString($this->_arrTranslations[$intCatId][$this->_intLangId]['name'], $this->_maxStringlength),
            'FORUM_TREE'            =>    $this->_createNavTree($intCatId),
            'FORUM_DROPDOWN'        =>    $this->createForumDD('forum_quickaccess', $intCatId, 'onchange="gotoForum(this);"', ''),
            'FORUM_JAVASCRIPT'        =>    $this->getJavascript(),
            'FORUM_JAVASCRIPT_GOTO'    =>     $this->getJavascript('goto'),
        ));

        if ($intCatId != 0) {
            $arrForums = $this->createForumArray($this->_intLangId, $intCatId, 1);
            if (count($arrForums) > 0) {
                $this->_objTpl->setGlobalVariable(array(
                    'TXT_FORUM'                =>    $_ARRAYLANG['TXT_FORUM_OVERVIEW_FORUM'],
                    'TXT_LASTPOST'            =>    $_ARRAYLANG['TXT_FORUM_OVERVIEW_LASTPOST'],
                    'TXT_THREADS'            =>    $_ARRAYLANG['TXT_FORUM_OVERVIEW_THREADS'],
                    'TXT_POSTINGS'            =>    $_ARRAYLANG['TXT_FORUM_OVERVIEW_POSTINGS'],
                    'TXT_FORUM_QUICKACCESS' =>    $_ARRAYLANG['TXT_FORUM_QUICKACCESS'],
                ));
                $intCounter=0;
                foreach ($arrForums as $intKey    => $arrValues) {
                    if ($arrValues['status'] == 1) {
                        $this->_objTpl->setVariable(array(
                            'FORUM_SUBCATEGORY_ROWCLASS'        =>    ($intCounter++ % 2) + 1,
                            'FORUM_SUBCATEGORY_SPACER'            =>    (intval($arrValues['level'])-1)*25,
                            'FORUM_SUBCATEGORY_ICON'            =>    '<img src="modules/Forum/View/Media/comment.gif" alt="comment.gif" border="0" />',
                            'FORUM_SUBCATEGORY_ID'                =>    $arrValues['id'],
                            'FORUM_SUBCATEGORY_NAME'            =>    $arrValues['name'],
                            'FORUM_SUBCATEGORY_DESC'            =>    $arrValues['description'],
                            'FORUM_SUBCATEGORY_LASTPOST_ID'        =>    $arrValues['last_post_id'],
                            'FORUM_SUBCATEGORY_LASTPOST_TITLE'    =>    $arrValues['last_post_str'],
                            'FORUM_SUBCATEGORY_LASTPOST_DATE'    =>    $arrValues['last_post_date'],
                            'FORUM_SUBCATEGORY_THREADS'            =>    $arrValues['thread_count'],
                            'FORUM_SUBCATEGORY_POSTINGS'        =>    $arrValues['post_count'],
                        ));

                        $this->_objTpl->parse('forumSubCategory');
                    }
                }
                $this->_objTpl->setVariable(array(
                    'FORUM_THREADS_PAGING'            =>    getPaging($this->_threadCount, $pos, '&section=Forum&cmd=board&id='.$intCatId, $_ARRAYLANG['TXT_FORUM_OVERVIEW_THREADS'], true, $this->_arrSettings['thread_paging']),
                ));
            } else {
                $this->_objTpl->setVariable('TXT_THREADS_NONE', $_ARRAYLANG['TXT_FORUM_THREADS_NONE']);
            }
        } else {
            \Cx\Core\Csrf\Controller\Csrf::header('location: index.php?section=Forum');
            die();
        }

    }


    /**
     * Show an overview of all available board in the current language
     *
     * @global    array
     */
    function showForumOverview() {
        global $_ARRAYLANG;
        $this->_communityLogin();
        $strJavascriptToggleCode = '<script type="text/javascript" language="javascript">//<![CDATA['."\n";
        $arrForums = $this->createForumArray($this->_intLangId);

        foreach ($arrForums as $id => $forum) {
            if($forum['parent_id'] == 0 && $forum['status']){
                $strJavascriptToggleCode .= "toggleCategory('$id');\n";
            }
        }

        $strJavascriptToggleCode .= '//]]></script>';

        $this->_objTpl->setVariable(array(
            'FORUM_JAVASCRIPT'                 => $this->getJavascript(),
            'FORUM_JAVASCRIPT_TOGGLE_CAT'    => $strJavascriptToggleCode,
        ));

        if (count($arrForums) > 0) {

            $this->_showLatestEntries($this->_getLatestEntries());

            $boolIsFirst    = true;

            $this->_objTpl->setGlobalVariable(array(
                'TXT_FORUM'                =>    $_ARRAYLANG['TXT_FORUM_OVERVIEW_FORUM'],
                'TXT_LASTPOST'            =>    $_ARRAYLANG['TXT_FORUM_OVERVIEW_LASTPOST'],
                'TXT_THREADS'            =>    $_ARRAYLANG['TXT_FORUM_OVERVIEW_THREADS'],
                'TXT_POSTINGS'            =>    $_ARRAYLANG['TXT_FORUM_OVERVIEW_POSTINGS'],
                'FORUM_DROPDOWN'        =>    $this->createForumDD('forum_quickaccess', 0, 'onchange="gotoForum(this);"', ''),
                'FORUM_JAVASCRIPT_GOTO'    =>     $this->getJavascript('goto'),
            ));
            $intCounter     = 0;
            foreach ($arrForums as $intKey    => $arrValues) {
                if ($arrValues['status'] == 1) {
                    if ($arrValues['level'] == 0) {

                        if (!$boolIsFirst) { //the first time we have to intercept the parsing for correct showing of the board-list
                            $this->_objTpl->parse('forumMainCategory');
                        } else {
                            $boolIsFirst = false;
                        }

                        $this->_objTpl->setVariable(array(
                            'FORUM_MAINCATEGORY_ID'            =>    $arrValues['id'],
                            'FORUM_MAINCATEGORY_NAME'        =>    '<span onclick="toggleCategory(\''.$arrValues['id'].'\')">'.$arrValues['name'].'</span>',
                            'FORUM_MAINCATEGORY_NAME_TITLE'    =>    $arrValues['name'],
                            'FORUM_MAINCATEGORY_DESC'        =>    $arrValues['description'],
                        ));
                        $intCounter     = 0;
                    } else {
                        $this->_objTpl->setVariable(array(
                            'FORUM_SUBCATEGORY_ROWCLASS'        =>    ($intCounter++ % 2) + 1,
                            'FORUM_SUBCATEGORY_SPACER'            =>    (intval($arrValues['level'])-1)*25,
                            'FORUM_SUBCATEGORY_ICON'            =>    '<img src="modules/Forum/View/Media/comment.gif" alt="comment.gif" border="0" />',
                            'FORUM_SUBCATEGORY_ID'                =>    $arrValues['id'],
                            'FORUM_SUBCATEGORY_NAME'            =>    $arrValues['name'],
                            'FORUM_SUBCATEGORY_DESC'            =>    $arrValues['description'],
                            'FORUM_SUBCATEGORY_LASTPOST_ID'        =>    $arrValues['last_post_id'],
                            'FORUM_SUBCATEGORY_LASTPOST_TITLE'    =>    $arrValues['last_post_str'],
                            'FORUM_SUBCATEGORY_LASTPOST_DATE'    =>    $arrValues['last_post_date'],
                            'FORUM_SUBCATEGORY_THREADS'            =>    $arrValues['thread_count'],
                            'FORUM_SUBCATEGORY_POSTINGS'        =>    $arrValues['post_count']

                        ));

                        $this->_objTpl->parse('forumSubCategory');
                    }
                }
            }
        } else {
            //no forums in database
        }
    }

    /**
     * show and update notifications
     *
     */
    function showNotifications()
    {
        global $objDatabase, $_ARRAYLANG;

        $this->_communityLogin();

        $this->_objTpl->setVariable(array(
            'TXT_FORUM_THREAD_NOTIFICATION'     => $_ARRAYLANG['TXT_FORUM_THREAD_NOTIFICATION'],
            'TXT_SELECT_ALL'                     => $_ARRAYLANG['TXT_SELECT_ALL'],
            'TXT_DESELECT_ALL'                     => $_ARRAYLANG['TXT_DESELECT_ALL'],
            'TXT_FORUM_NOTIFICATION_HELPTEXT'    => $_ARRAYLANG['TXT_FORUM_NOTIFICATION_HELPTEXT'],
            'TXT_FORUM_NOTIFICATION_SUBMIT'        => $_ARRAYLANG['TXT_FORUM_NOTIFICATION_SUBMIT'],
            'TXT_FORUM_NOTIFICATION_HELPTEXT'    => $_ARRAYLANG['TXT_FORUM_NOTIFICATION_HELPTEXT'],
            'TXT_FORUM_NOTIFICATION_SUBMIT'        => $_ARRAYLANG['TXT_FORUM_NOTIFICATION_SUBMIT'],
            'TXT_FORUM_UNSUBSCRIBED_THREADS'    => $_ARRAYLANG['TXT_FORUM_UNSUBSCRIBED_THREADS'],
            'TXT_FORUM_SUBSCRIBED_THREADS'        => $_ARRAYLANG['TXT_FORUM_SUBSCRIBED_THREADS'],

        ));


        $objFWUser = \FWUser::getFWUserObject();
        if (!$objFWUser->objUser->login()) {
            $this->_objTpl->setVariable('TXT_FORUM_ERROR', '<br />'.$_ARRAYLANG['TXT_FORUM_MUST_BE_AUTHENTICATED']);
            $this->_objTpl->hideBlock('notification');
            return false;
        }

        $this->_objTpl->setVariable('FORUM_JAVASCRIPT_NOTIFICATION', $this->getJavascript('notification'));

        if(isset($_REQUEST['forumNotificationSubmit'])){//drop and update notifications
            $query = "    DELETE FROM `".DBPREFIX."module_forum_notification`
                        WHERE `user_id` = ".$objFWUser->objUser->getId()."
                        AND thread_id = 0";

            if($objDatabase->Execute($query) === false){
                $this->_objTpl->setVariable('TXT_FORUM_ERROR', '<br />Database error: '.$objDatabase->ErrorMsg());
                $this->_objTpl->hideBlock('notification');
                return false;
            }

            foreach ($_REQUEST['subscribed'] as $intCategoryId) {
                $intCategoryId = intval($intCategoryId);
                if($intCategoryId > 0){
                    $query = "    INSERT INTO `".DBPREFIX."module_forum_notification`
                                VALUES ( ".$intCategoryId.", 0, ".$objFWUser->objUser->getId().", '0')";
                    if($objDatabase->Execute($query) === false){
                        $this->_objTpl->setVariable('TXT_FORUM_ERROR', '<br />Database error: '.$objDatabase->ErrorMsg());
                        $this->_objTpl->hideBlock('notification');
                        return false;
                    }
                }
            }
            $this->_objTpl->setVariable('TXT_FORUM_SUCCESS', '<br />'.$_ARRAYLANG['TXT_FORUM_NOTIFICATION_UPDATED']);
        }

        $arrUnsubscribedThreads = $arrForums = $this->createForumArray($this->_intLangId);

        $strOptionsUnsubscribed = $strOptionsSubscribed = '';

        $query = "    SELECT `n`.`category_id`, `l`.`name` , `c`.`status`
                    FROM `".DBPREFIX."module_forum_notification` AS `n`
                    LEFT JOIN ".DBPREFIX."module_forum_categories_lang AS `l` USING ( `category_id` )
                    LEFT JOIN ".DBPREFIX."module_forum_categories AS `c` ON ( `c`.`id` = `n`.`category_id` )
                    WHERE `n`.`user_id` = ".$objFWUser->objUser->getId()."
                    AND `n`.`thread_id` = 0
                    AND `l`.`lang_id` = ".$this->_intLangId."
                    AND `c`.`status` = '1'
                    ORDER BY `c`.`id` ASC";

        if(($objRS = $objDatabase->Execute($query)) === false){
            die('DB error: '.$objDatabase->ErrorMsg());
        }

        while(!$objRS->EOF){
            $arrSubscribedThreads[$objRS->fields['category_id']] = $objRS->fields;
            unset($arrUnsubscribedThreads[$objRS->fields['category_id']]);
            $objRS->MoveNext();
        }

        if(!empty($arrSubscribedThreads)){
            foreach ($arrSubscribedThreads as $intCatID => $arrThread){
                $strOptionsSubscribed .= '<option value="'.$intCatID.'">'.(str_repeat('&nbsp;', ($arrForums[$intCatID]['level']*2))).$arrThread['name'].'</option>';
            }
        }

        if(!empty($arrUnsubscribedThreads)){
            foreach ($arrUnsubscribedThreads as $intCatID => $arrThread){
                $strOptionsUnsubscribed .= '<option value="'.$intCatID.'">'.(str_repeat('&nbsp;', ($arrForums[$intCatID]['level']*2))).$arrThread['name'].'</option>';
            }
        }

        $this->_objTpl->setVariable(array(
            'FORUM_NOTIFICATION_UNSUBSCRIBED'    =>    $strOptionsUnsubscribed,
            'FORUM_NOTIFICATION_SUBSCRIBED'        =>    $strOptionsSubscribed,
        ));
    }


    /**
     * Returns needed javascripts for the forum-module
     *
     * @param     string         $type
     * @return    string        $strJavaScript
     */
    function getJavascript($type = '', $data = '') {
        global $_ARRAYLANG;
        switch($type){
            case 'scrollto':
                $strJavaScript = '
                <script type="text/javascript" language="JavaScript">
                //<![CDATA[
                    function setScrollPos(){
                        if (typeof(window.pageYOffset) != \'undefined\') {
                            offset = window.pageYOffset;
                        } else {
                            offset = document.documentElement.scrollTop;
                        }
                        if(document.getElementById("scrollpos")){
                            document.getElementById("scrollpos").value = offset;
                        }
                    }
                //]]>
                </script>
                ';
                break;
            case 'goto':
                $strJavaScript = '
                            <script type="text/javascript" language="JavaScript">
                            //<![CDATA[
                                function gotoForum(objSelect){
                                    id = objSelect.options[objSelect.selectedIndex].value;
                                    if(id==0){return top.location.href="index.php?section=Forum&'.\Cx\Core\Csrf\Controller\Csrf::param().'";}
                                    if(id.indexOf("_cat") > -1){
                                        return top.location.href="index.php?section=Forum&cmd=cat&'.\Cx\Core\Csrf\Controller\Csrf::param().'&id="+parseInt(id);
                                    }else{
                                        return top.location.href="index.php?section=Forum&cmd=board&'.\Cx\Core\Csrf\Controller\Csrf::param().'&id="+id;
                                    }
                                }
                            //]]>
                            </script>
                        ';
                break;
            case 'deletePost':
                $strJavaScript = '
                            <script type="text/javascript" language="JavaScript">
                            //<![CDATA[
                                function deletePost(thread_id, post_id){
                                    if(confirm("'.$_ARRAYLANG['TXT_FORUM_CONFIRM_DELETE'].'\n'.$_ARRAYLANG['TXT_FORUM_CANNOT_UNDO_OPERATION'].'")){
                                        window.location.href = "?section=Forum&cmd=thread&'.\Cx\Core\Csrf\Controller\Csrf::param().'&id="+thread_id+"&act=delete&postid="+post_id;
                                    }
                                }
                            //]]>
                            </script>
                        ';
                break;
            case 'deleteThread':
                $strJavaScript = '
                            <script type="text/javascript" language="JavaScript">
                            //<![CDATA[
                                function deleteThread(category_id, thread_id){
                                    if(confirm("'.$_ARRAYLANG['TXT_FORUM_CONFIRM_DELETE'].'\n'.$_ARRAYLANG['TXT_FORUM_CANNOT_UNDO_OPERATION'].'")){
                                        window.location.href = "?section=Forum&cmd=board&'.\Cx\Core\Csrf\Controller\Csrf::param().'&id="+category_id+"&act=delete&threadid="+thread_id;
                                    }
                                }
                            //]]>
                            </script>
                        ';
                break;
            case 'notification':
                $strJavaScript = '
                            <script type="text/javascript" language="JavaScript">
                            //<![CDATA[
                                function AddToTheList(from,dest,add,remove){
                                    if(from.selectedIndex < 0){
                                        if(from.options[0] != null){
                                            from.options[0].selected = true;
                                        }
                                        from.focus();
                                        return false;
                                    }else{
                                        for(var i=0; i<from.length; i++){
                                            if (from.options[i].selected){
                                                dest.options[dest.length] = new Option( from.options[i].text, from.options[i].value, false, false);
                                               }
                                        }
                                        for (var i=from.length-1; i>=0; i--){
                                            if (from.options[i].selected){
                                               from.options[i] = null;
                                               }
                                        }
                                    }
                                    disableButtons(from,dest,add,remove);
                                }

                                function RemoveFromTheList(from,dest,add,remove){
                                    if ( dest.selectedIndex < 0){
                                        if (dest.options[0] != null){
                                            dest.options[0].selected = true;
                                        }
                                        dest.focus();
                                        return false;
                                    }else{
                                        for (var i=0; i<dest.options.length; i++){
                                            if (dest.options[i].selected){
                                                from.options[from.options.length] = new Option( dest.options[i].text, dest.options[i].value, false, false);
                                               }
                                        }
                                        for (var i=dest.options.length-1; i>=0; i--){
                                            if (dest.options[i].selected){
                                               dest.options[i] = null;
                                               }
                                        }
                                    }
                                    disableButtons(from,dest,add,remove);
                                }

                                function disableButtons(from,dest,add,remove){
                                    if (from.options.length > 0 ){
                                        add.disabled = 0;
                                    }else{
                                        add.disabled = 1;
                                    }
                                    if (dest.options.length > 0){
                                        remove.disabled = 0;
                                    }else{
                                        remove.disabled = 1;
                                    }
                                }

                                function SelectAllList(CONTROL){
                                    for(var i = 0;i < CONTROL.length;i++){
                                        CONTROL.options[i].selected = true;
                                    }
                                }

                                function DeselectAllList(CONTROL){
                                    for(var i = 0;i < CONTROL.length;i++){
                                        CONTROL.options[i].selected = false;
                                    }
                                }
                            //]]>
                            </script>';
                break;
            case 'insertText':
                $boardId        = $data[0];
                $threadId       = $data[1];
                $firstPost      = $data[2];
                $thanks         = $_ARRAYLANG['TXT_FORUM_RATING_THANKS'];
                $confirmClose   = $_ARRAYLANG['TXT_FORUM_THREAD_ACTION_CLOSE_CONFIRM_'.$firstPost['is_locked']];
                $confirmSticky  = $_ARRAYLANG['TXT_FORUM_THREAD_ACTION_STICKY_CONFIRM_'.$firstPost['is_sticky']];
                $confirmDelete  = $_ARRAYLANG['TXT_FORUM_THREAD_ACTION_DELETE_CONFIRM']."\\n".$_ARRAYLANG['TXT_FORUM_CANNOT_UNDO_OPERATION'];

                $allowedExtensions = str_replace(',', ', ', $this->_arrSettings['allowed_extensions']);
                $csrf          = \Cx\Core\Csrf\Controller\Csrf::param();
                $strJavaScript = <<< EOJS
<script type="text/javascript" language="JavaScript">
//<![CDATA[

    var doAction = function(action){
        switch(action){
         case 'move':
            location.href = 'index.php?section=Forum&$csrf&cmd=thread&thread_actions=move&id=$threadId';
         break;
         case 'close':
            if(confirm('$confirmClose')){
                location.href = 'index.php?section=Forum&$csrf&cmd=thread&thread_actions=close&id=$threadId';
            }
         break;
         case 'delete':
            if(confirm('$confirmDelete')){
                location.href = 'index.php?section=Forum&$csrf&cmd=board&id=$boardId&act=delete&threadid=$threadId';
            }
         break;
         case 'sticky':
            if(confirm('$confirmSticky')){
                location.href = 'index.php?section=Forum&$csrf&cmd=thread&thread_actions=sticky&id=$threadId';
            }
         break;
        }
        try{
            document.getElementsByName('thread_actions')[0].options.selectedIndex=0;
        }catch(e){}
    }

    var ratePost = function(postId, delta, obj){
        var d = document;
        var dl=document.location;
        var abs = dl.protocol+'//'+dl.host+dl.href.split(/index\.php/)[0].split(dl.host)[1]
        var url=abs+'index.php?section=Forum&$csrf&cmd=thread&act=rate&value='+delta+'&postId='+postId;
        var i = d.createElement("img");
        i.src = url;
        i.id = 'tmp_Img';
        d.body.appendChild(i);
        d.body.removeChild(d.getElementById(i.id));
        document.getElementById("forum_current_rating_"+postId).innerHTML=document.getElementById("forum_current_rating_"+postId).innerHTML*1+delta;
        document.getElementById("forum_rating_"+postId).innerHTML="$thanks";
        x=setTimeout('document.getElementById("forum_rating_'+postId+'").parentNode.removeChild(document.getElementById("forum_rating_'+postId+'"))', 2000);
    }

    var showToolTip = function(txt, node, id){
        d=document;
        oTxt=d.createTextNode(txt);
        oDiv=d.createElement('div');
        oDiv.id=id;
        oDiv.appendChild(oTxt);
        node.parentNode.appendChild(oDiv);
    }

    var hideToolTip = function(id){
        document.getElementById(id).parentNode.removeChild(document.getElementById(id));
    }

    var showAllowedExtensions = function(){
        try{
            forumAllowedExtPopUp = window.open('about:blank', 'forumAllowedExtPopUp', 'menubar=1,directories=0,toolbar=1,resizeable=1,location=1,status=1,scrollbars=1,width=600,height=200');
            //IE
            forumAllowedExtPopUp.document.body.innerHTML = '<div>$allowedExtensions</div>';
            //others
            forumAllowedExtPopUp.onload = function(){ //others
                try{
                    forumAllowedExtPopUp.document.body.appendChild(document.createElement('div'));
                    forumAllowedExtPopUp.document.body.childNodes[0].innerHTML = '$allowedExtensions';
                }catch(e){}
            }
        //fallback to alert if all else fails
        }catch(e){ alert('$allowedExtensions'); }
    }

//]]>
</script>
EOJS;

                break;
            default:
                $strJavaScript = '
                            <script type="text/javascript" language="JavaScript">
                            //<![CDATA[
                                function toggleCategory(categoryId){
                                    objDiv     = document.getElementById("maincat_"+categoryId);
                                    objImg     = document.getElementById("maincat_"+categoryId+"_img");

                                    if (objDiv.style.display == "block") {
                                        objDiv.style.display = "none";
                                        objImg.src = "'.ASCMS_MODULE_WEB_PATH.'/Forum/View/Media/arrow_down.gif";
                                    } else {
                                        objDiv.style.display = "block";
                                        objImg.src = "'.ASCMS_MODULE_WEB_PATH.'/Forum/View/Media/arrow_up.gif";
                                    }
                                 }
                            //]]>
                            </script>
                        ';
                break;

        }
        return $strJavaScript;
    }

}
