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
 * Blog
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Kaelin <thomas.kaelin@comvation.com>
 * @version     $Id: index.inc.php,v 1.01 $
 * @package     cloudrexx
 * @subpackage  module_blog
 */

namespace Cx\Modules\Blog\Controller;

/**
 * BlogAdmin
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Kaelin <thomas.kaelin@comvation.com>
 * @version     $Id: index.inc.php,v 1.00 $
 * @package     cloudrexx
 * @subpackage  module_blog
 */
class Blog extends \Cx\Modules\Blog\Controller\BlogLibrary  {

    var $_objTpl;
    var $_intVotingDaysBeforeExpire = 1;
    var $_strStatusMessage = '';
    var $_strErrorMessage = '';


    /**
    * Constructor   -> Call parent-constructor, set language id and create local template-object
    *
    * @global   integer
    */
    function __construct($strPageContent)
    {
        global $_LANGID;

        parent::__construct();

        $this->_intLanguageId = intval($_LANGID);
        $this->_intCurrentUserId = 0;

        $this->_objTpl = new \Cx\Core\Html\Sigma('.');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);
        $this->_objTpl->setTemplate($strPageContent);
    }


    /**
     * Must be called before the user-id is accessed. Tries to load the user-id from the session.
     *
     */
    function initUserId() {
        $objFWUser = \FWUser::getFWUserObject();
        $this->_intCurrentUserId = $objFWUser->objUser->login() ? $objFWUser->objUser->getId() : 0;
    }


    /**
    * Reads $_GET['cmd'] and selects (depending on the value) an action
    *
    */
    function getPage()
    {
        \Cx\Core\Csrf\Controller\Csrf::add_code();
        if(!isset($_GET['cmd'])) {
            $_GET['cmd'] = '';
        }

        switch ($_GET['cmd']) {
            case 'details':
                $this->showDetails($_GET['id']);
                break;
            case 'search':
                $this->showSearch();
                break;
            case 'cloud':
                $this->showTagCloud();
                break;
            default:
                $this->showEntries();
                break;
        }

        return $this->_objTpl->get();
    }



    /**
     * Shows all existing entries of the blog in descending order.
     *
     * @global  array
     */
    function showEntries() {
        global $_ARRAYLANG;

        /* Start Paging ------------------------------------ */
        $intPos             = (isset($_GET['pos'])) ? intval($_GET['pos']) : 0;
// TODO: Never used
//        $intCount             = $this->countEntries();
        $intPerPage         = intval($this->_arrSettings['blog_block_messages']);
        $strPagingSource    = getPaging($this->countEntries(), $intPos, '&section=Blog', '<b>'.$_ARRAYLANG['TXT_BLOG_FRONTEND_SEARCH_RESULTS'].'</b>', false, $intPerPage);
        $this->_objTpl->setVariable('BLOG_ENTRIES_PAGING', $strPagingSource);
        /* End Paging -------------------------------------- */

        $arrEntries = $this->createEntryArray($this->_intLanguageId, $intPos, $intPerPage);

        foreach ($arrEntries as $intEntryId => $arrEntryValues) {

            $this->_objTpl->setVariable(array(
                'TXT_BLOG_CATEGORIES'   =>  $_ARRAYLANG['TXT_BLOG_FRONTEND_SEARCH_RESULTS_CATEGORIES'],
                'TXT_BLOG_TAGS'         =>  $_ARRAYLANG['TXT_BLOG_FRONTEND_SEARCH_RESULTS_KEYWORDS'],
                'TXT_BLOG_VOTING'       =>  $_ARRAYLANG['TXT_BLOG_FRONTEND_OVERVIEW_VOTING'],
                'TXT_BLOG_VOTING_DO'    =>  $_ARRAYLANG['TXT_BLOG_FRONTEND_OVERVIEW_VOTING_DO'],
                'TXT_BLOG_COMMENTS'     =>  $_ARRAYLANG['TXT_BLOG_FRONTEND_OVERVIEW_COMMENTS'],
            ));

            $this->_objTpl->setVariable(array(
                'BLOG_ENTRIES_ID'           =>  $intEntryId,
                'BLOG_ENTRIES_TITLE'        =>  $arrEntryValues['subject'],
                'BLOG_ENTRIES_POSTED'       =>  $this->getPostedByString($arrEntryValues['user_name'],$arrEntryValues['time_created']),
                'BLOG_ENTRIES_POSTED_ICON'    =>    $this->getPostedByIcon($arrEntryValues['time_created']),
                'BLOG_ENTRIES_CONTENT'      =>  $arrEntryValues['translation'][$this->_intLanguageId]['content'],
                'BLOG_ENTRIES_INTRODUCTION' =>  $this->getIntroductionText($arrEntryValues['translation'][$this->_intLanguageId]['content']),
                'BLOG_ENTRIES_IMAGE'        =>  ($arrEntryValues['translation'][$this->_intLanguageId]['image'] != '') ? '<img src="'.$arrEntryValues['translation'][$this->_intLanguageId]['image'].'" title="'.$arrEntryValues['subject'].'" alt="'.$arrEntryValues['subject'].'" />' : '',
                'BLOG_ENTRIES_VOTING'       =>  '&#216;&nbsp;'.$arrEntryValues['votes_avg'],
                'BLOG_ENTRIES_VOTING_STARS' =>  $this->getRatingBar($intEntryId),
                'BLOG_ENTRIES_COMMENTS'     =>  $arrEntryValues['comments_active'].' '.$_ARRAYLANG['TXT_BLOG_FRONTEND_OVERVIEW_COMMENTS'].'&nbsp;',
                'BLOG_ENTRIES_CATEGORIES'   =>  $this->getCategoryString($arrEntryValues['categories'][$this->_intLanguageId], true),
                'BLOG_ENTRIES_TAGS'         =>  $this->getLinkedTags($arrEntryValues['translation'][$this->_intLanguageId]['tags']),
                'BLOG_ENTRIES_TAGS_ICON'    =>  $this->getTagsIcon(),
                'BLOG_ENTRIES_SPACER'       =>  ($this->_arrSettings['blog_voting_activated'] && $this->_arrSettings['blog_comments_activated']) ? '&nbsp;&nbsp;|&nbsp;&nbsp;' : ''
            ));

            if (!$this->_arrSettings['blog_voting_activated']) {
                $this->_objTpl->hideBlock('showVotingPart');
            }

            if (!$this->_arrSettings['blog_comments_activated']) {
                $this->_objTpl->hideBlock('showCommentPart');
            }

            $this->_objTpl->parse('showBlogEntries');
        }
    }


    /**
     * Shows detail-page (content, voting & comments) for a single message. It checks also for new comments (POST) or votings (GET).
     *
     * @global  array
     * @global  ADONewConnection
     * @global  array
     * @param   integer     $intMessageId: The details of this page will be shown
     */
    function showDetails($intMessageId) {
        global $_CORELANG, $_ARRAYLANG, $objDatabase, $_CONFIG;

        $this->initUserId();
        $intMessageId = intval($intMessageId);

        if ($intMessageId < 1) {
            \Cx\Core\Csrf\Controller\Csrf::header("Location: index.php?section=Blog");
        }

        //Empty form-values
        $strName    = '';
        $strEMail   = '';
        $strWWW     = '';
        $strSubject = '';
        $strComment = '';

        //Check for new votings
        if (isset($_POST['vote'])) {
            $this->addVoting($intMessageId, $_POST['vote']);
        }

        //Check for new comments
        if (isset($_POST['frmAddComment_MessageId'])) {
            $this->addComment();
            if (!empty($this->_strErrorMessage) || (!\FWUser::getFWUserObject()->objUser->login() && !\Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->check())) {
                //Error occured, get previous entered values
                $strName        = htmlentities($_POST['frmAddComment_Name'], ENT_QUOTES, CONTREXX_CHARSET);
                $strEMail       = htmlentities($_POST['frmAddComment_EMail'], ENT_QUOTES, CONTREXX_CHARSET);
                $strWWW         = htmlentities($_POST['frmAddComment_WWW'], ENT_QUOTES, CONTREXX_CHARSET);
                $strSubject     = htmlentities($_POST['frmAddComment_Subject'], ENT_QUOTES, CONTREXX_CHARSET);
                $strComment     = contrexx_stripslashes(html_entity_decode($_POST['frmAddComment_Comment'], ENT_QUOTES, CONTREXX_CHARSET));
            }
        }

        //Count new hit
        $this->addHit($intMessageId);

        //After processing new actions: show page
        $arrEntries = $this->createEntryArray($this->_intLanguageId);

        //Loop over socializing-networks
        $strNetworks = '';
        $arrNetworks = $this->createNetworkArray();

        if (count($arrNetworks) > 0) {
            $strPageUrl = urlencode(
                \Cx\Core\Routing\Url::fromModuleAndCmd(
                    'Blog',
                    'details',
                    '',
                    array(
                        'id' => $intMessageId,
                    )
                )->toString()
            );

            foreach ($arrNetworks as $arrNetworkValues) {
                if (key_exists($this->_intLanguageId, $arrNetworkValues['status'])) {
                    $strUrl = str_replace('[URL]', $strPageUrl, $arrNetworkValues['submit']);
                    $strUrl = str_replace('[SUBJECT]', $arrEntries[$intMessageId]['subject'], $strUrl);

                    $strNetworks .= '<a href="'.$strUrl.'" title="'.$arrNetworkValues['name'].' ('.$arrNetworkValues['www'].')" target="_blank">'.$arrNetworkValues['icon_img'].'</a>&nbsp;';
                }
            }
        }

        //Show message-part
        $this->_objTpl->setVariable(array(
            'BLOG_DETAILS_ID'           =>  $intMessageId,
            'BLOG_DETAILS_TITLE'        =>  $arrEntries[$intMessageId]['subject'],
            'BLOG_DETAILS_POSTED'       =>  $this->getPostedByString($arrEntries[$intMessageId]['user_name'], $arrEntries[$intMessageId]['time_created']),
            'BLOG_DETAILS_POSTED_ICON'    =>  $this->getPostedByIcon($arrEntries[$intMessageId]['time_created']),
            'BLOG_DETAILS_CONTENT'      =>  html_entity_decode($arrEntries[$intMessageId]['translation'][$this->_intLanguageId]['content']),
            'BLOG_DETAILS_IMAGE'        =>  ($arrEntries[$intMessageId]['translation'][$this->_intLanguageId]['image'] != '') ? '<img src="'.$arrEntries[$intMessageId]['translation'][$this->_intLanguageId]['image'].'" title="'.$arrEntries[$intMessageId]['subject'].'" alt="'.$arrEntries[$intMessageId]['subject'].'" />' : '',
            'BLOG_DETAILS_NETWORKS'     =>  $strNetworks
        ));

        //Show voting-part
        if ($this->_arrSettings['blog_voting_activated']) {
            $this->_objTpl->setVariable(array(
                'TXT_VOTING'                =>  $_ARRAYLANG['TXT_BLOG_FRONTEND_OVERVIEW_VOTING'],
                'TXT_VOTING_ACTUAL'         =>  $_ARRAYLANG['TXT_BLOG_FRONTEND_DETAILS_VOTING_ACTUAL'],
                'TXT_VOTING_AVG'            =>  $_ARRAYLANG['TXT_BLOG_FRONTEND_DETAILS_VOTING_AVG'],
                'TXT_VOTING_COUNT'          =>  $_ARRAYLANG['TXT_BLOG_FRONTEND_DETAILS_VOTING_COUNT'],
                'TXT_VOTING_USER'           =>  $_ARRAYLANG['TXT_BLOG_FRONTEND_DETAILS_VOTING_USER'],
            ));

            $this->_objTpl->setVariable(array(
                'BLOG_DETAILS_VOTING_BAR'   =>  $this->getRatingBar($intMessageId),
                'BLOG_DETAILS_VOTING_AVG'   =>  '&#216;&nbsp;'.$arrEntries[$intMessageId]['votes_avg'],
                'BLOG_DETAILS_VOTING_COUNT' =>  $arrEntries[$intMessageId]['votes'],
                'BLOG_DETAILS_VOTING_USER'  =>  ($this->hasUserAlreadyVoted($intMessageId)) ? $this->getUserVotingForMessage($intMessageId) : $this->getVotingBar($intMessageId),
            ));
        } else {
            $this->_objTpl->hideBlock('votingPart');
        }

        //Show comment-part
        if ($this->_arrSettings['blog_comments_activated']) {
            //comments are activated

            $this->_objTpl->setVariable(array(
                'TXT_COMMENTS'              =>  $_ARRAYLANG['TXT_BLOG_FRONTEND_OVERVIEW_COMMENTS'],
                'TXT_COMMENT_ADD'           =>  $_ARRAYLANG['TXT_BLOG_FRONTEND_DETAILS_COMMENT_ADD'],
                'TXT_COMMENT_ADD_NAME'      =>  $_ARRAYLANG['TXT_BLOG_FRONTEND_DETAILS_COMMENT_ADD_NAME'],
                'TXT_COMMENT_ADD_EMAIL'     =>  $_ARRAYLANG['TXT_BLOG_FRONTEND_DETAILS_COMMENT_ADD_EMAIL'],
                'TXT_COMMENT_ADD_WWW'       =>  $_ARRAYLANG['TXT_BLOG_FRONTEND_DETAILS_COMMENT_ADD_WWW'],
                'TXT_COMMENT_ADD_SUBJECT'   =>  $_ARRAYLANG['TXT_BLOG_FRONTEND_DETAILS_COMMENT_ADD_SUBJECT'],
                'TXT_COMMENT_ADD_COMMENT'   =>  $_ARRAYLANG['TXT_BLOG_FRONTEND_DETAILS_COMMENT_ADD_COMMENT'],
                'TXT_COMMENT_ADD_RESET'     =>  $_ARRAYLANG['TXT_BLOG_FRONTEND_DETAILS_COMMENT_ADD_RESET'],
                'TXT_COMMENT_ADD_SUBMIT'    =>  $_ARRAYLANG['TXT_BLOG_FRONTEND_DETAILS_COMMENT_ADD_SUBMIT'],
            ));

            if (\FWUser::getFWUserObject()->objUser->login()) {
                $this->_objTpl->hideBlock('comment_captcha');
            } else {
                $this->_objTpl->setVariable(array(
                    'TXT_COMMENT_CAPTCHA'   => $_CORELANG['TXT_CORE_CAPTCHA'],
                    'COMMENT_CAPTCHA_CODE'  => \Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->getCode(),
                ));
                $this->_objTpl->parse('comment_captcha');
            }

            $this->_objTpl->setVariable(array(
                'BLOG_DETAILS_COMMENTS_JAVASCRIPT'  =>  $this->getJavascript('comments')
            ));
            $objFWUser = \FWUser::getFWUserObject();

            $objCommentsResult = $objDatabase->Execute('SELECT      comment_id,
                                                                    time_created,
                                                                    user_id,
                                                                    user_name,
                                                                    user_mail,
                                                                    user_www,
                                                                    subject,
                                                                    comment
                                                        FROM        '.DBPREFIX.'module_blog_comments
                                                        WHERE       message_id='.$intMessageId.' AND
                                                                    lang_id='.$this->_intLanguageId.' AND
                                                                    is_active="1"
                                                        ORDER BY    time_created ASC, comment_id ASC
                                                    ');

            if ($objCommentsResult->RecordCount() > 0) {
                while (!$objCommentsResult->EOF) {

                    //Get username and avatar
                    $strUserName     = '';
                    $strUserAvatar    = '<img src="'.ASCMS_BLOG_IMAGES_WEB_PATH.'/no_avatar.gif" alt="'.$strUserName.'" />';
                    $objUser = $objFWUser->objUser->getUser($objCommentsResult->fields['user_id']);

                    if ($objCommentsResult->fields['user_id'] == 0 || $objUser === false) {
                        $strUserName     = $objCommentsResult->fields['user_name'];
                    } else {
                        $strUserName     = contrexx_raw2xhtml(\FWUser::getParsedUserTitle($objUser));

                        if ($objUser->getProfileAttribute('picture') != '') {
                            $strUserAvatar    = '<img src="'.ASCMS_ACCESS_PROFILE_IMG_WEB_PATH.'/'.$objUser->getProfileAttribute('picture').'" alt="'.$strUserName.'" />';
                        }
                    }

                    //Parse comment
                    $this->_objTpl->setVariable(array(
                        'BLOG_DETAILS_COMMENT_ID'           =>  $objCommentsResult->fields['comment_id'],
                        'BLOG_DETAILS_COMMENT_TITLE'        =>  htmlentities(stripslashes($objCommentsResult->fields['subject']), ENT_QUOTES, CONTREXX_CHARSET),
                        'BLOG_DETAILS_COMMENT_POSTED'       =>  $this->getPostedByString($strUserName, date(ASCMS_DATE_FORMAT,$objCommentsResult->fields['time_created'])),
                        'BLOG_DETAILS_COMMENT_CONTENT'        =>    \Cx\Core\Wysiwyg\Wysiwyg::prepareBBCodeForOutput($objCommentsResult->fields['comment']),
                        'BLOG_DETAILS_COMMENT_AVATAR'        =>    $strUserAvatar
                    ));

                    $this->_objTpl->parse('showCommentRows');

                    $objCommentsResult->MoveNext();
                }
            } else {
                $this->_objTpl->setVariable('TXT_COMMENTS_NONE_EXISTING', $_ARRAYLANG['TXT_BLOG_FRONTEND_DETAILS_COMMENT_NONE_EXISTING']);
                $this->_objTpl->parse('showNoCommentRows');
            }

            if ($this->_arrSettings['blog_comments_anonymous'] || $this->_intCurrentUserId != 0) {
                //Anonymous comments allowed or user is logged in

                //Fill Add-Comment-Form

                //Determine the desired editor
                if ($this->_arrSettings['blog_comments_editor'] == 'wysiwyg') {
                    $strEditor = new \Cx\Core\Wysiwyg\Wysiwyg('frmAddComment_Comment', $strComment, 'bbcode');
                } else {
                    $strEditor = '<textarea name="frmAddComment_Comment" rows="12" cols="80" class="blogCommentTextarea">'.$strComment.'</textarea>';
                }

                $this->_objTpl->setVariable(array(
                    'BLOG_DETAILS_COMMENT_ADD_MESSAGE_ID'       =>  $intMessageId,
                    'BLOG_DETAILS_COMMENT_ADD_NAME'             =>  ($this->_intCurrentUserId == 0) ? '<input type="text" name="frmAddComment_Name" value="'.$strName.'" class="blogCommentInput" />' : contrexx_raw2xhtml(\FWUser::getParsedUserTitle($objFWUser->objUser)),
                    'BLOG_DETAILS_COMMENT_ADD_EMAIL'            =>  ($this->_intCurrentUserId == 0) ? '<input type="text" name="frmAddComment_EMail" value="'.$strEMail.'" class="blogCommentInput" />' : contrexx_raw2xhtml($objFWUser->objUser->getEmail()),
                    'BLOG_DETAILS_COMMENT_ADD_WWW'              =>  ($this->_intCurrentUserId == 0) ? '<input type="text" name="frmAddComment_WWW" value="'.$strWWW.'" class="blogCommentInput" />' : contrexx_raw2xhtml($objFWUser->objUser->getProfileAttribute('website')),
                    'BLOG_DETAILS_COMMENT_ADD_SUBJECT'          =>  $strSubject,
                    'BLOG_DETAILS_COMMENT_ADD_COMMENT'          =>  $strEditor,
                ));
            } else {
                //Anonymous comments arent allowed and the user isn't logged in -> Hide block!
                $this->_objTpl->setVariable('BLOG_DETAILS_COMMENT_ADD_ERROR', $_ARRAYLANG['TXT_BLOG_FRONTEND_DETAILS_COMMENT_ADD_LOGGED_IN']);
                $this->_objTpl->hideBlock('commentAddPart');
            }
        } else {
            //Comments dectivated - hide comment block
            $this->_objTpl->hideBlock('commentPart');
        }

        //Finally parse info / error messages
        if (empty($this->_strStatusMessage)) {
            $this->_objTpl->hideBlock('showOkay');
        } else {
            $this->_objTpl->setVariable('BLOG_DETAILS_COMMENT_OKAY', $this->_strStatusMessage);
        }

        if (empty($this->_strErrorMessage)) {
            $this->_objTpl->hideBlock('showError');
        } else {
            $this->_objTpl->setVariable('BLOG_DETAILS_COMMENT_ERROR', $this->_strErrorMessage);
        }

    }


    /**
     * Count a new visitor for a message. Increments the field "hit" by one.
     *
     * @global  ADONewConnection
     * @param   integer     $intMessageId: The hit will be counted for this message.
     */
    function addHit($intMessageId) {
        global $objDatabase;

        if (checkForSpider()) {
            return;
        }

        $intMessageId = intval($intMessageId);

        if ($intMessageId > 0 && !$this->hasUserJustCommented() && !$this->hasUserAlreadyVoted($intMessageId)) {
            $objDatabase->Execute(' UPDATE  '.DBPREFIX.'module_blog_messages
                                    SET     hits = hits + 1
                                    WHERE   message_id='.$intMessageId.'
                                    LIMIT   1
                                ');
        }
    }



    /**
     * Insert a new voting for a message into database.
     *
     * @global  ADONewConnection
     * @global  array
     * @param   integer     $intMessageId: The voting will be added to this message.
     * @param   integer     $intVoting: the mark for the value. Can be an integer between 1 (worst) and 10 (best).
     */
    function addVoting($intMessageId, $intVoting) {
        global $objDatabase, $_ARRAYLANG;
        \Cx\Core\Csrf\Controller\Csrf::check_code();

        $intMessageId = intval($intMessageId);
        $intVoting = intval($intVoting);

        //Check for activated function
        if (!$this->_arrSettings['blog_voting_activated']) {
            $this->_strErrorMessage = $_ARRAYLANG['TXT_BLOG_FRONTEND_DETAILS_VOTING_INSERT_ERROR_ACTIVATED'];
            return;
        }

        //Check for previous voting
        if ($this->hasUserAlreadyVoted($intMessageId)) {
            $this->_strErrorMessage = $_ARRAYLANG['TXT_BLOG_FRONTEND_DETAILS_VOTING_INSERT_ERROR_PREVIOUS'];
            return;
        }

        if ($intMessageId > 0 && $intVoting >= 1 && $intVoting <= 10) {
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $userHash = $cx->getComponent(
                'Stats'
            )->getCounterInstance()->getUniqueUserId();
            $objDatabase->Execute(' INSERT INTO '.DBPREFIX.'module_blog_votes
                                    SET message_id = '.$intMessageId.',
                                        time_voted = '.time().',
                                        ip_address = "' . $userHash . '",
                                        vote = "'.$intVoting.'"
                                ');

            setcookie('BlogVoting['.$intMessageId.']', $intVoting, 0, ASCMS_PATH_OFFSET.'/');
            $this->_strStatusMessage = $_ARRAYLANG['TXT_BLOG_FRONTEND_DETAILS_VOTING_INSERT_SUCCESS'];
        }
    }


    /**
     * Insert a new comment for a message into database, if the function is activated. Furthermore, all input values are validated.
     * Sends also the notification mail to the administrator, if it is enabled in options.
     *
     * @global  ADONewConnection
     * @global  array
     * @global  array
     */
    function addComment() {
        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        \Cx\Core\Csrf\Controller\Csrf::check_code();
        $this->initUserId();

        //Check for activated function
        if (!$this->_arrSettings['blog_comments_activated']) {
            $this->_strErrorMessage = $_ARRAYLANG['TXT_BLOG_FRONTEND_DETAILS_COMMENT_INSERT_ERROR_ACTIVATED'];
            return;
        }

        if ($this->hasUserJustCommented()) {
            $this->_strErrorMessage = str_replace('[SECONDS]', intval($this->_arrSettings['blog_comments_timeout']), $_ARRAYLANG['TXT_BLOG_FRONTEND_DETAILS_COMMENT_INSERT_ERROR_TIMEOUT']);
            return;
        }

        //Create validator-object
        $objValidator = new \FWValidator();

        //Get general-input
        $intMessageId   = intval($_POST['frmAddComment_MessageId']);
        $strSubject     = contrexx_addslashes(strip_tags($_POST['frmAddComment_Subject']));
        $strComment     = \Cx\Core\Wysiwyg\Wysiwyg::prepareBBCodeForDb($_POST['frmAddComment_Comment']);

        //Get specified-input
        if ($this->_intCurrentUserId == 0) {
            $intUserId  = 0;
            $strName    = contrexx_addslashes(strip_tags($_POST['frmAddComment_Name']));
            $strEMail   = contrexx_addslashes(strip_tags($_POST['frmAddComment_EMail']));
            $strWWW     = contrexx_addslashes(strip_tags($objValidator->getUrl($_POST['frmAddComment_WWW'])));
        } else {
            $intUserId  = $this->_intCurrentUserId;
            $strName    = '';
            $strEMail   = '';
            $strWWW     = '';
        }

        //Get options
        $intIsActive = intval($this->_arrSettings['blog_comments_autoactivate']);
        $intIsNotification = intval($this->_arrSettings['blog_comments_notification']);

        //Validate general-input
        if ($intMessageId <= 0) {                               $this->_strErrorMessage .= $this->getFormError($_ARRAYLANG['TXT_BLOG_FRONTEND_DETAILS_COMMENT_INSERT_MID']); }
        if (empty($strSubject)) {                               $this->_strErrorMessage .= $this->getFormError($_ARRAYLANG['TXT_BLOG_FRONTEND_DETAILS_COMMENT_ADD_SUBJECT']); }
        if (empty($strComment)) {                               $this->_strErrorMessage .= $this->getFormError($_ARRAYLANG['TXT_BLOG_FRONTEND_DETAILS_COMMENT_ADD_COMMENT']); }

        //Validate specified-input
        if ($this->_intCurrentUserId == 0) {
            if (empty($strName)) {                                  $this->_strErrorMessage .= $this->getFormError($_ARRAYLANG['TXT_BLOG_FRONTEND_DETAILS_COMMENT_ADD_NAME']); }
            if (!$objValidator->isEmail($strEMail)) {               $this->_strErrorMessage .= $this->getFormError($_ARRAYLANG['TXT_BLOG_FRONTEND_DETAILS_COMMENT_ADD_EMAIL']); }
        }

        $captchaCheck = true;
        if (!\FWUser::getFWUserObject()->objUser->login() && !\Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->check()) {
            $captchaCheck = false;
        }

        //Now check error-string
        if (empty($this->_strErrorMessage) && $captchaCheck) {
            //No errors, insert entry
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $userHash = $cx->getComponent(
                'Stats'
            )->getCounterInstance()->getUniqueUserId();
            $objDatabase->Execute(' INSERT INTO '.DBPREFIX.'module_blog_comments
                                    SET     message_id = '.$intMessageId.',
                                            lang_id = '.$this->_intLanguageId.',
                                            is_active = "'.$intIsActive.'",
                                            time_created = '.time().',
                                            ip_address = "' . $userHash . '",
                                            user_id = '.$intUserId.',
                                            user_name = "'.$strName.'",
                                            user_mail = "'.$strEMail.'",
                                            user_www = "'.$strWWW.'",
                                            subject = "'.$strSubject.'",
                                            comment = "'.$strComment.'"
                                ');

            //Set a cookie with the current timestamp. Avoids flooding.
            setcookie('BlogCommentLast', time(), 0, ASCMS_PATH_OFFSET.'/');

            $this->_strStatusMessage = $_ARRAYLANG['TXT_BLOG_FRONTEND_DETAILS_COMMENT_INSERT_SUCCESS'];

            $this->writeCommentRSS();

            if ($intIsNotification) {
                //Send notification to administrator
                $objMail = new \Cx\Core\MailTemplate\Model\Entity\Mail();

                if ($this->_intCurrentUserId > 0) {
                    $objFWUser = \FWUser::getFWUserObject();
                    $strName = htmlentities($objFWUser->objUser->getUsername(), ENT_QUOTES, CONTREXX_CHARSET);
                }

                $strMailSubject = str_replace('[SUBJECT]', $strSubject, $_ARRAYLANG['TXT_BLOG_FRONTEND_DETAILS_COMMENT_INSERT_MAIL_SUBJECT']);
                $strMailBody    = str_replace('[USERNAME]', $strName, $_ARRAYLANG['TXT_BLOG_FRONTEND_DETAILS_COMMENT_INSERT_MAIL_BODY']);
                $strMailBody    = str_replace('[DOMAIN]', ASCMS_PROTOCOL . '://' . $_CONFIG['domainUrl'] . ASCMS_PATH_OFFSET, $strMailBody);
                $strMailBody    = str_replace('[SUBJECT]', $strSubject, $strMailBody);
                $strMailBody    = str_replace('[COMMENT]', $strComment, $strMailBody);

                $objMail->SetFrom($_CONFIG['coreAdminEmail'], $_CONFIG['coreGlobalPageTitle']);
                $objMail->AddAddress($_CONFIG['coreAdminEmail']);
                $objMail->Subject   = $strMailSubject;
                $objMail->IsHTML(false);
                $objMail->Body      = $strMailBody;
                $objMail->Send();

            }
        }
    }


    /**
     * Creates an voting bar (123...10) for a specific message.
     *
     * @global  array
     * @param   integer     $intMessageId: The voting bar will be created for the message with this id.
     * @return  string      HTML-source for the voting bar.
     */
    function getVotingBar($intMessageId) {
        global $_ARRAYLANG;

        \JS::activate("prototype");

        $strReturn = '';
        $intMessageId = intval($intMessageId);

        //Check for valid number
        if ($intMessageId == 0) {
            return '';
        }

        for ($i = 1; $i <= 10; ++$i) {
            //$js   = "window.location='index.php?section=Blog&amp;cmd=details&amp;id=$intMessageId&amp;vote=$i';return false";
            $title= $_ARRAYLANG['TXT_BLOG_FRONTEND_OVERVIEW_VOTING_DO'] . ": $i";
            $strReturn .= '<a href="#" onclick="javascript: vote('.$i.')">';
            $strReturn .= '<img title="'.$_ARRAYLANG['TXT_BLOG_FRONTEND_OVERVIEW_VOTING_DO'].': '.$i.'" alt="'.$_ARRAYLANG['TXT_BLOG_FRONTEND_OVERVIEW_VOTING_DO'].': '.$i.'" src="'.ASCMS_MODULE_WEB_PATH.'/Blog/View/Media/voting/'.$i.'.gif" border="0" />';
            $strReturn .= '</a>';
        }

        $strReturn .= "<form action=\"index.php?section=Blog&amp;cmd=details&amp;id=".$intMessageId."\"
            method=\"post\" id=\"vote_form\" >
            <input type=\"hidden\" value=\"\" name=\"vote\" id=\"vote_value\" />
            </form>";
        $strReturn .= "<script type=\"text/javascript\">
            /* <![CDATA[[ */

            var vote = function(value) {
                $('vote_value').value = parseInt(value);
                $('vote_form').submit();
            }
            /* ]]> */
        </script>";

        return $strReturn;
    }



    /**
     * Check if the current user has already voted for this message.
     *
     * @param   integer     $intMessageId: Check for existing voting of the user for the message with this id.
     * @return  boolean     true, if the user already voted for this topic.
     */
    function hasUserAlreadyVoted($intMessageId) {
        global $objDatabase;

        $intMessageId = intval($intMessageId);

        //Check first the cookie
        if (isset($_COOKIE['BlogVoting'][$intMessageId])) {
            return true;
        }

        //Now check database
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $userHash = $cx->getComponent(
            'Stats'
        )->getCounterInstance()->getUniqueUserId();
        $objVotingResult = $objDatabase->Execute('  SELECT  vote_id
                                                    FROM    '.DBPREFIX.'module_blog_votes
                                                    WHERE   message_id='.$intMessageId.' AND
                                                            ip_address="' . $userHash . '" AND
                                                            time_voted > '.(time() - $this->_intVotingDaysBeforeExpire*24*60*60).'
                                                    LIMIT   1
                                                ');

        if ($objVotingResult->RecordCount() == 1) {
            return true;
        }

        //Nothing found, i guess the user didn't vote before.
        return false;
    }


    /**
     * Check if the current user has already written a comment within the definied timeout-time (settings-value).
     *
     * @return  boolean     true, if the user hast just written a comment before.
     */
    function hasUserJustCommented() {
        global $objDatabase;

        //Check cookie first
        if (isset($_COOKIE['BlogCommentLast'])) {
            $intLastCommentTime = intval($_COOKIE['BlogCommentLast']);

            if (time() < $intLastCommentTime + intval($this->_arrSettings['blog_comments_timeout'])) {
                //The current system-time is smaller than the time in the cookie plus timeout-time, so the user just wrote a comment
                return true;
            }
        }

        //Now check database (make sure the user didn't delete the cookie
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $userHash = $cx->getComponent(
            'Stats'
        )->getCounterInstance()->getUniqueUserId();
        $objCommentResult = $objDatabase->Execute(' SELECT  comment_id
                                                    FROM    '.DBPREFIX.'module_blog_comments
                                                    WHERE   ip_address="' . $userHash . '" AND
                                                            time_created > '.(time() - intval($this->_arrSettings['blog_comments_timeout'])).'
                                                    LIMIT   1
                                                ');

        if ($objCommentResult->RecordCount() == 1) {
            return true;
        }

        //Nothing found, i guess the user didn't comment within within the timeout-period.
        return false;
    }


    /**
     * Returns the voting given by the actual user. You should use "hasUserAlreadyVoted()" first to check for an existing voting.
     *
     * @param   integer     $intMessageId: The voting given by the user for this message will be returned.
     * @return  integer     the voting given by the user.
     */
    function getUserVotingForMessage($intMessageId) {
        global $objDatabase;

        $intMessageId = intval($intMessageId);

        //Check first the cookie
        if (isset($_COOKIE['BlogVoting'][$intMessageId])) {
            return intval($_COOKIE['BlogVoting'][$intMessageId]);
        }

        //Now check database
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $userHash = $cx->getComponent(
            'Stats'
        )->getCounterInstance()->getUniqueUserId();
        $objVotingResult = $objDatabase->Execute('  SELECT  vote
                                                    FROM    '.DBPREFIX.'module_blog_votes
                                                    WHERE   message_id='.$intMessageId.' AND
                                                            ip_address="' . $userHash . '" AND
                                                            time_voted > '.(time() - $this->_intVotingDaysBeforeExpire*24*60*60).'
                                                    LIMIT   1
                                                ');

        if ($objVotingResult->RecordCount() == 1) {
            return intval($objVotingResult->fields['vote']);
        }

        //Nothing found, i guess the user didn't vote before.
        return 0;
    }


    /**
     * Create an error-string for validation of input.
     *
     * @global  array
     * @return  string      Error string for validation of input.
     */
    function getFormError($strFieldName) {
        global $_ARRAYLANG;

        return str_replace('[FIELD]', $strFieldName, $_ARRAYLANG['TXT_BLOG_FRONTEND_DETAILS_COMMENT_INSERT_ERROR']);
    }



    /**
     * Shows the "Tag Cloud"-page for all existing keywords.
     *
     */
    function showTagCloud() {
            $this->_objTpl->setVariable('BLOG_TAG_CLOUD', $this->getTagCloud());
            $this->_objTpl->setVariable('BLOG_TAG_HITLIST', $this->getTagHitlist());
    }



    /**
     * Shows the "Search"-page for the blog-module.
     *
     * @global  array
     * @global  ADONewConnection
     */
    function showSearch() {
        global $_ARRAYLANG, $objDatabase;

        $this->_objTpl->setVariable(array(
            'TXT_SEARCH_MODUS'              =>  $_ARRAYLANG['TXT_BLOG_FRONTEND_SEARCH_MODUS'],
            'TXT_SEARCH_MODUS_KEYWORD'      =>  $_ARRAYLANG['TXT_BLOG_FRONTEND_SEARCH_MODUS_KEYWORD'],
            'TXT_SEARCH_MODUS_DATE'         =>  $_ARRAYLANG['TXT_BLOG_FRONTEND_SEARCH_MODUS_DATE'],
            'TXT_SEARCH_SUBMIT'             =>  $_ARRAYLANG['TXT_BLOG_FRONTEND_SEARCH_SUBMIT'],
            'TXT_SEARCH_RESULTS'            =>  $_ARRAYLANG['TXT_BLOG_FRONTEND_SEARCH_RESULTS']
        ));

        //Maybe the user selected an modus before
        $strSelectedModus = (isset($_GET['mode'])) ? $_GET['mode'] : 'keyword';
        $strSelectedModus = ($strSelectedModus == 'date') ? 'date' : 'keyword';

        //Collect values from GET or POST
        $strKeywordString   = '';
        $intKeywordCategory = 0;

        if (isset($_POST['frmDoSearch_Keyword_String'])) {      $strKeywordString = $_POST['frmDoSearch_Keyword_String'];
        } else if(isset($_GET['term'])) {                       $strKeywordString = $_GET['term']; }

        if (isset($_POST['frmDoSearch_Keyword_Category'])) {    $intKeywordCategory = intval($_POST['frmDoSearch_Keyword_Category']);
        } else if(isset($_GET['category'])) {                   $intKeywordCategory = intval($_GET['category']); }

        //Fill keyword-search-form
        $this->_objTpl->setVariable(array(
            'BLOG_SEARCH_JAVASCRIPT'                =>  $this->getJavascript('search'),
            'BLOG_SEARCH_MODUS_KEYWORD_CHECKED'     =>  ($strSelectedModus == 'keyword') ? 'checked="checked"' : '',
            'BLOG_SEARCH_MODUS_DATE_CHECKED'        =>  ($strSelectedModus == 'date') ? 'checked="checked"' : '',
            'BLOG_SEARCH_MODUS_KEYWORD_STYLE'       =>  ($strSelectedModus == 'keyword') ? 'display: block;' : 'display: none;',
            'BLOG_SEARCH_MODUS_DATE_STYLE'          =>  ($strSelectedModus == 'date') ? 'display: block;' : 'display: none;',
            'BLOG_SEARCH_KEYWORD_STRING'            =>  htmlentities($strKeywordString, ENT_QUOTES, CONTREXX_CHARSET),
            'BLOG_SEARCH_KEYWORD_CATEGORIES'        =>  $this->getCategoryDropDown('frmDoSearch_Keyword_Category',$intKeywordCategory),
        ));

        //Fill date-search-form
        $intYear    = (isset($_GET['yearID']))  ? intval($_GET['yearID'])   : date('Y', time());
        $intMonth   = (isset($_GET['monthID'])) ? intval($_GET['monthID'])  : date('m', time());
        $intDay     = (isset($_GET['dayID']))   ? intval($_GET['dayID'])    : 0;

        $this->_objTpl->setVariable(array(
            'BLOG_SEARCH_DATE_CALENDAR' =>  $this->getCalendar($intYear,$intMonth,$intDay)
        ));


        //Do search if needed
        $arrResults = array();

        if (empty($strKeywordString) && $intKeywordCategory == 0) {
            if ($intYear == 0 || $intMonth == 0 || $intDay == 0) {
                $this->_objTpl->hideBlock('ResultPart');
            } else {
                //Do search for date
                $arrEntries = $this->createEntryArray($this->_intLanguageId);

                $intTimestampStarting = mktime(0,0,0,$intMonth,$intDay,$intYear);
                $intTimestampEnding = mktime(23,59,59,$intMonth,$intDay,$intYear);

                if (count($arrEntries) > 0) {
                    foreach ($arrEntries as $intEntryId => $arrEntryValues) {
                        if ($this->timestampIsInRange($arrEntryValues['time_created_ts'],$intTimestampStarting,$intTimestampEnding)) {
                            $arrResults[count($arrResults)] = $intEntryId;
                        }
                    }
                }
            }
        } else {
            //Do search for keyword
            $arrEntries = $this->createEntryArray($this->_intLanguageId);

            if (count($arrEntries) > 0) {
                foreach ($arrEntries as $intEntryId => $arrEntryValues) {
                    //Check for matches of all keywords
                    if ($this->allKeywordsFound($strKeywordString, $arrEntryValues['subject'], $arrEntryValues['translation'][$this->_intLanguageId]['content'], $arrEntryValues['translation'][$this->_intLanguageId]['tags']) &&
                        $this->categoryMatches($intKeywordCategory, $arrEntryValues['categories'][$this->_intLanguageId]))
                    {
                        $arrResults[count($arrResults)] = $intEntryId;
                    }
                }
            }
        }

        //Show results if any search was done and something has been found
        if (count($arrResults) == 0) {
            $this->_objTpl->setVariable('TXT_SEARCH_RESULTS_NONE', $_ARRAYLANG['TXT_BLOG_FRONTEND_SEARCH_RESULTS_NONE']);
            $this->_objTpl->parse('showNoResults');
        } else {
            foreach ($arrResults as $intEntryId) {
                $this->_objTpl->setVariable(array(
                    'TXT_SEARCH_RESULTS_DATE'       =>  $_ARRAYLANG['TXT_BLOG_FRONTEND_SEARCH_MODUS_DATE'],
                    'TXT_SEARCH_RESULTS_CATEGORY'   =>  $_ARRAYLANG['TXT_BLOG_FRONTEND_SEARCH_RESULTS_CATEGORIES'],
                    'TXT_SEARCH_RESULTS_KEYWORDS'   =>  $_ARRAYLANG['TXT_BLOG_FRONTEND_SEARCH_RESULTS_KEYWORDS']
                ));

                $this->_objTpl->setVariable(array(
                    'BLOG_SEARCH_RESULTS_MID'           =>  $intEntryId,
                    'BLOG_SEARCH_RESULTS_SUBJECT'       =>  $arrEntries[$intEntryId]['subject'],
                    'BLOG_SEARCH_RESULTS_POSTED'        =>  $arrEntries[$intEntryId]['time_created'],
                    'BLOG_SEARCH_RESULTS_CATEGORIES'    =>  $this->getCategoryString($arrEntries[$intEntryId]['categories'][$this->_intLanguageId], true),
                    'BLOG_SEARCH_RESULTS_TAGS'          =>  $this->getLinkedTags($arrEntries[$intEntryId]['translation'][$this->_intLanguageId]['tags']),
                    'BLOG_SEARCH_RESULTS_INTRODUCTION'  =>  html_entity_decode($this->getIntroductionText($arrEntries[$intEntryId]['translation'][$this->_intLanguageId]['content']))
                ));

                $this->_objTpl->parse('showResults');
            }
        }


    }


    /**
     * Shows the "Search"-page for the blog-module.
     *
     * @param   string      $strSearchString: This is the string, which the user has entered. Example: "this is a test".
     * @param   string      $strSubject: This is the subject, which will be checked for the keywords.
     * @param   string      $strContent: This is the content, which will be checked for the keywords.
     * @param   string      $strTags: This is the tag-field, which will be checked for the keywords.
     * @return  boolean     true, if ALL keywords in $strSearchString have been found. A keyword is seperated by an empty char (" ").
     */
    function allKeywordsFound($strSearchString, $strSubject, $strContent, $strTags) {
        $arrSearchWords = explode(' ', htmlentities($strSearchString, ENT_QUOTES, CONTREXX_CHARSET));
        $strContent = strip_tags($strContent);

        foreach ($arrSearchWords as $strSearchWord) {
            if (preg_match('/.*'.$strSearchWord.'.*/i', $strSubject)) { continue; }
            if (preg_match('/.*'.$strSearchWord.'.*/i', $strContent)) { continue; }
            if (preg_match('/.*'.$strSearchWord.'.*/i', $strTags)) { continue; }

            //if we come to this point, the keyword has not be found.
            return false;
        }

        return true;
    }


    /**
     * Checks, if the timestamp in the first parameter ($intTimestamp) is within the range of the 2nd (begin) and 3rd (end).
     *
     * @param   integer     $intTimestamp: This timestamp should be within the range.
     * @param   integer     $intStartingTimestamp: The Unix-timestamp in this parameter defines the beginning of the range.
     * @param   integer     $intEndingTimestamp: The Unix-timestamp in this parameter defines the end of the range.
     * @return  boolean     true, if the timestamp is within the range.
     */
    function timestampIsInRange($intTimestamp, $intStartingTimestamp, $intEndingTimestamp) {
        if ($intStartingTimestamp < $intTimestamp && $intEndingTimestamp > $intTimestamp) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Returns needed javascripts for the forum-module
     *
     * @param   string      $strType: Which Javascript should be returned?
     * @return  string      $strJavaScript
     */
    function getJavascript($strType = '') {
        $strJavaScript = '';

        switch ($strType) {
            case 'comments':
                $strJavaScript = '  <script type="text/javascript" language="JavaScript">
                                    //<![CDATA[
                                        function toggleComment(commentId){
                                            objDiv  = document.getElementById("comment_"+commentId);
                                            objImg  = document.getElementById("comment_"+commentId+"_img");

                                            if (objDiv.style.display == "block") {
                                                objDiv.style.display = "none";
                                                objImg.src = "'.ASCMS_MODULE_WEB_PATH.'/Blog/View/Media/arrow_down.gif";
                                            } else {
                                                objDiv.style.display = "block";
                                                objImg.src = "'.ASCMS_MODULE_WEB_PATH.'/Blog/View/Media/arrow_up.gif";
                                            }
                                         }
                                    //]]>
                                    </script>';
                break;
            case 'search':
                $strJavaScript = '  <script type="text/javascript" language="JavaScript">
                                    //<![CDATA[
                                        function switchModus() {
                                            objRadioKeyword = document.getElementById("searchModus_RadioKeyword");
                                            objDivKeyword   = document.getElementById("searchModus_DivKeyword");
                                            objDivDate      = document.getElementById("searchModus_DivDate");

                                            if (objRadioKeyword.checked == true) {
                                                objDivKeyword.style.display = "block";
                                                objDivDate.style.display = "none";
                                            } else {
                                                objDivKeyword.style.display = "none";
                                                objDivDate.style.display = "block";
                                            }
                                         }
                                    //]]>
                                    </script>';
                break;
        }

        return $strJavaScript;
    }
}
