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
 * Forum library
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Kaelin <thomas.kaelin@comvation.com>
 * @version     $Id: index.inc.php,v 1.00 $
 * @package     cloudrexx
 * @subpackage  module_forum
 */

namespace Cx\Modules\Forum\Controller;

/**
 * Forum library
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Kaelin <thomas.kaelin@comvation.com>
 * @version     $Id: index.inc.php,v 1.00 $
 * @package     cloudrexx
 * @subpackage  module_forum
 */
class ForumLibrary
{
    public $_anonymousName         = "Anonym";
    public $_intLangId;
    public $_arrSettings           = array();
    public $_arrLanguages          = array();
    public $_arrTranslations       = array();
    public $_arrIcons;
    public $_threadCount           = 0;
    public $_postCount             = 0;
    public $_arrGroups             = array();
    public $_communityUserGroupId  = array(0);
    public $_anonymousGroupId      = array(0);
    public $_maxStringlength       = 50;
    public $_minPostlength         = 5;
    public $_topListLimit          = 10;
    public $_rateTimeout;

    /**
    * Constructor
    *
    */
    function __construct()
    {
        $this->_arrSettings     = $this->createSettingsArray();
        $this->_arrLanguages    = $this->createLanguageArray();
        $this->_arrTranslations = $this->createTranslationArray();
        $this->_rateTimeout     = 3600*6;
    }

    /**
     * do checks and delete thread
     *
     * @param integer $intThreadId
     * @return bool
     */
    function _deleteThread($intThreadId, $intCatId = 0) {
        global $objDatabase, $_ARRAYLANG;
        $intThreadId = intval($intThreadId);
        $intCatId = intval($intCatId);
        if ($intThreadId < 1) { //something's fishy...
            return false;
        }
        if (!$intCatId) {
            $intCatId = $this->_getCategoryIdFromThread($intThreadId);
        }

        if (!$this->_checkAuth($intCatId, 'delete')) { //check if the user has authorization to delete stuff in this category
            $this->_objTpl->setVariable('TXT_FORUM_ERROR', $_ARRAYLANG['TXT_FORUM_NO_ACCESS']);
            return false;
        }

        //get last post id from stats table
        $query = '  SELECT last_post_id FROM '.DBPREFIX.'module_forum_statistics
                    WHERE category_id = '.$intCatId;
        $objRS = $objDatabase->SelectLimit($query, 1);
        if ($objRS === false) {
            die('Database error: '.$objDatabase->ErrorMsg());
        }
        $last_post_id = $objRS->fields['last_post_id'];

        //get all id's from the thread which is gonna be deleted
        $query = '  SELECT id,attachment FROM '.DBPREFIX.'module_forum_postings
                    WHERE thread_id = '.$intThreadId;
        $objRS = $objDatabase->Execute($query);
        if ($objRS === false) {
            die('Database error: '.$objDatabase->ErrorMsg());
        }
        $deletePostIds = array();
        while(!$objRS->EOF) {
            $deletePostIds[] = $objRS->fields['id'];
            $deleteAttachments[] = $objRS->fields['attachment'];
            $objRS->MoveNext();
        }

        if (empty($deletePostIds)) {
            return false;
        }

        //now compare the fetched ids with the last_post_id from the stats table we retrieved before
        if (in_array($last_post_id, $deletePostIds)) {
            //last_post_id in module_forum_statistics is going to be deleted, get new 'last post id'
            $query = '  SELECT `id` FROM '.DBPREFIX.'module_forum_postings
                        WHERE `category_id` = '.$intCatId.'
                        AND `thread_id` != '.$intThreadId.'
                        ORDER BY `thread_id` DESC, `id` DESC';
            if (($objRS = $objDatabase->SelectLimit($query, 1)) !== false) {
                if ($objRS->RecordCount() == 1) { //another thread found, setting new 'last post id'
                    $new_last_post_id = $objRS->fields['id'];
                } else { //no more threads, this category is empty now, hence we set the 'last post id' to 0
                    $new_last_post_id = 0;
                }
            } else {
                die('Database error: '.$objDatabase->ErrorMsg());
            }
        }

        $query = '  DELETE FROM '.DBPREFIX.'module_forum_postings
                    WHERE thread_id = '.$intThreadId;
        if ($objDatabase->Execute($query) === false) {
            die('Database error: '.$objDatabase->ErrorMsg());
        }

        $filePath = \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteMediaForumUploadPath() . '/';
        foreach ($deleteAttachments as $file) {
            if (!empty($file) && file_exists($filePath . $file)) {
                unlink($filePath . $file);
            }
        }

        $intAffectedRows = $objDatabase->Affected_Rows();
        if (!isset($new_last_post_id)) {
            $query = '  UPDATE '.DBPREFIX.'module_forum_statistics
                        SET     `post_count` = `post_count` - '.$intAffectedRows.',
                                `thread_count` = `thread_count` - 1
                        WHERE   category_id = '.$intCatId;
        } else {
            $query = '  UPDATE '.DBPREFIX.'module_forum_statistics
                        SET     `last_post_id` = '.$new_last_post_id.',
                                `post_count` = `post_count` - '.$intAffectedRows.',
                                `thread_count` = `thread_count` - 1
                        WHERE   category_id = '.$intCatId;
        }

        if ($objDatabase->Execute($query) === false) {
            die('Database error: '.$objDatabase->ErrorMsg());
        }

        $query = '  DELETE FROM `'.DBPREFIX.'module_forum_notification`
                    WHERE `thread_id` = '.$intThreadId;
        if ($objDatabase->Execute($query) === false) {
            die('Database error: '.$objDatabase->ErrorMsg());
        }
//      $objCache = new \Cache();
//      $objCache->deleteAllFiles();
        return true;
    }

    /**
     * do checks and delete post
     *
     * @param integer $intCatId
     * @param integer $intThreadId
     * @param integer $intPostId
     * @return bool true on success
     */
    function _deletePost($intCatId, $intThreadId, $intPostId) {
        global $objDatabase, $_ARRAYLANG;
        if ($intPostId < 1) {
            return false;
        }
        if (!$this->_checkAuth($intCatId, 'delete')) {
            $this->_objTpl->setVariable('TXT_FORUM_ERROR', $_ARRAYLANG['TXT_FORUM_NO_ACCESS']);
            return false;
        }

        //check if post exists
        $query = 'SELECT 1 FROM '.DBPREFIX.'module_forum_postings
                    WHERE id = '.$intPostId;
        if (($objRS = $objDatabase->SelectLimit($query, 1)) !== false) {
            if ($objRS->RecordCount() == 0) {
                return false;
            }
        } else {
            die('Database error: '.$objDatabase->ErrorMsg());
        }

        //check if it's the first post in a thread, warn and exit if true
        $query = '  SELECT 1 FROM '.DBPREFIX.'module_forum_postings
                    WHERE id = '.$intPostId.'
                    AND thread_id = '.$intThreadId.'
                    AND category_id = '.$intCatId.'
                    AND prev_post_id = 0';
        if (($objRS = $objDatabase->SelectLimit($query, 1)) !== false) {
            if ($objRS->RecordCount() == 1) {
                $this->_objTpl->setVariable('TXT_FORUM_ERROR', $_ARRAYLANG['TXT_FORUM_FIRST_POST_IN_THREAD'].' '.$_ARRAYLANG['TXT_FORUM_DELETE_THREAD_INSTEAD']);
                return false;
            }
        } else {
            die('Database error: '.$objDatabase->ErrorMsg());
        }

        //if last post in thread, then update statistics
        $query = '  SELECT last_post_id FROM '.DBPREFIX.'module_forum_statistics
                    WHERE category_id = '.$intCatId;
        $objRS = $objDatabase->SelectLimit($query, 1);
        if ($objRS !== false) {
            $last_post_id = !empty($objRS->fields['last_post_id']) ? $objRS->fields['last_post_id'] : 0;
        }

        if ($last_post_id == $intPostId) {
            $arrPosts = $this->createPostArray($intThreadId, -1);   //fetch all posts from this thread
            end($arrPosts);                         //get second last post, which is now the new last post
            $new_last_post_id = prev($arrPosts);    //and update the statistics table with the new values
            $new_last_post_id = $new_last_post_id['id'];
            $query = '  UPDATE '.DBPREFIX.'module_forum_statistics
                        SET     `last_post_id` = '.$new_last_post_id.',
                                `post_count` = `post_count` - 1
                        WHERE category_id = '.$intCatId;
            if ($objDatabase->Execute($query) === false) {
                die('Database error: '.$objDatabase->ErrorMsg());
            }
        } else { //not last post, only update post_count
            $query = '  UPDATE '.DBPREFIX.'module_forum_statistics
                        SET     `post_count` = `post_count` - 1
                        WHERE category_id = '.$intCatId;
            if ($objDatabase->Execute($query) === false) {
                die('Database error: '.$objDatabase->ErrorMsg());
            }
        }

        //check if any posts are associated with this one (not used yet)
        $query = '  SELECT id, category_id, thread_id, prev_post_id, attachment
                    FROM '.DBPREFIX.'module_forum_postings
                    WHERE prev_post_id = '.$intPostId.'
                    AND thread_id = '.$intThreadId.'
                    AND category_id = '.$intCatId.'
                    AND id != '.($intPostId+1);
        if (($objRS = $objDatabase->Execute($query)) !== false) {
            if ($objRS->RecordCount() > 0) {
                $this->_objTpl->setVariable('TXT_FORUM_ERROR', $_ARRAYLANG['TXT_FORUM_POST_STILL_ASSOCIATED'].' '.$_ARRAYLANG['TXT_FORUM_DELETE_ASSOCIATED_POSTS_FIRST']);
            } else {
                $filePath = \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteMediaForumUploadPath() . '/';
                if (!empty($objRS->fields['attachment']) && file_exists($filePath . $objRS->fields['attachment'])) {
                    unlink($filePath . $objRS->fields['attachment']);
                }
                $query = '  DELETE FROM '.DBPREFIX.'module_forum_postings
                            WHERE id='.$intPostId;
                if ($objDatabase->Execute($query) !== false) {
                    $this->_objTpl->setVariable('TXT_FORUM_SUCCESS', $_ARRAYLANG['TXT_FORUM_ENTRY_SUCCESSFULLY_DELETED']);
                    return true;
                } else {
                    die('Database error: '.$objDatabase->ErrorMsg());
                }
            }
        } else {
            die('Database error: '.$objDatabase->ErrorMsg());
        }
//      $objCache = new \Cache();
//      $objCache->deleteAllFiles();
        return true;
    }

    /**
     * set the community login links
     *
     * @return void
     */
    function _communityLogin()
    {
        global $_ARRAYLANG;

        $objFWUser = \FWUser::getFWUserObject();
        if (!$objFWUser->objUser->login()) {
            $strForumCommunityLinks = ' <a href="'.CONTREXX_SCRIPT_PATH.'?section=Login&amp;redirect='.((isset($_SERVER['REQUEST_URI'])) ? base64_encode($_SERVER['REQUEST_URI'])  : '?section=Forum' ).'"> '.$_ARRAYLANG['TXT_FORUM_LOGIN'].'</a> |
                                        <a href="'.CONTREXX_SCRIPT_PATH.'?section=Access&amp;cmd=signup">'.$_ARRAYLANG['TXT_FORUM_REGISTER'].'</a>';
        } else {
            $strForumCommunityLinks = '<a href="'.CONTREXX_SCRIPT_PATH.'?section=Forum&amp;cmd=notification">'.$_ARRAYLANG['TXT_FORUM_NOTIFICATION'].'</a> | <a href="'.CONTREXX_SCRIPT_PATH.'?section=Access&amp;cmd=settings">'.$_ARRAYLANG['TXT_FORUM_PROFILE'].'</a>
                                    | <a href="'.CONTREXX_SCRIPT_PATH.'?section=logout&amp;redirect='.((isset($_SERVER['REQUEST_URI'])) ? urlencode($_SERVER['REQUEST_URI'])  : '?section=Forum' ).'"> '.$_ARRAYLANG['TXT_FORUM_LOGOUT'].'</a>';
        }
        $this->_objTpl->setVariable('FORUM_COMMUNITY_LINKS', $strForumCommunityLinks);
    }

    /**
     * Create an array containing all settings of the forum-module. Example: $arrSettings[$strSettingName].
     *
     * @global  ADONewConnection
     * @return  array       $arrReturn
     */
    function createSettingsArray() {
        global $objDatabase;
        $arrReturn = array();

        $objResult = $objDatabase->Execute('
            SELECT name, value
              FROM '.DBPREFIX.'module_forum_settings
        ');
        while (!$objResult->EOF) {
            $arrReturn[$objResult->fields['name']] = stripslashes(htmlspecialchars($objResult->fields['value'], ENT_QUOTES, CONTREXX_CHARSET));
            $objResult->MoveNext();
        }

        $arrReturn['banned_words'] =
            (isset($arrReturn['banned_words'])
                ? explode(',', $arrReturn['banned_words'])
                : ''
            );
        $arrReturn['allowed_extensions'] =
            (isset($arrReturn['allowed_extensions'])
                ? str_replace(array(' ', '\n', '\r'), '', $arrReturn['allowed_extensions'])
                : ''
            );
        return $arrReturn;
    }

    /**
     * checks if the message contains prohibited words
     *
     * @param string $message
     * @return bool
     */
    function _hasBadWords($message)
    {
        $arrMatch = array();
        foreach ($this->_arrSettings['banned_words'] as $regex) {
            $regex = trim($regex);
            if (!empty($regex) && preg_match('#('.$regex.')#i', $message, $arrMatch)) {
                return $arrMatch;
            }
        }
        return false;
    }

    /**
     * Creates the html-source for a tag-cloud with all used keywords.
     *
     * @return  string      html-source for the tag cloud.
     */
    function getTagCloud() {
        $strReturn      = '';
        $arrKeywords = $this->createKeywordArray();

        if (count($arrKeywords) > 0) {
            $strReturn = '<ul class="forumTagCloud">';
            $intMinimum = min($arrKeywords);
            $intMaximum = max($arrKeywords);
            $intRange = $intMaximum - $intMinimum;

            foreach ($arrKeywords as $strTag => $intKeywordValue) {
                $strCssClass = '';

                if ($intKeywordValue >= $intMinimum + $intRange * 1.0) {
                    $strCssClass = 'forumTagCloudLargest';
                } else if ($intKeywordValue >= $intMinimum + $intRange * 0.75) {
                    $strCssClass = 'forumTagCloudLarge';
                } else if ($intKeywordValue >= $intMinimum + $intRange * 0.5) {
                    $strCssClass = 'forumTagCloudMedium';
                } else if ($intKeywordValue >= $intMinimum + $intRange * 0.25) {
                    $strCssClass = 'forumTagCloudSmall';
                } else {
                    $strCssClass = 'forumTagCloudSmallest';
                }

                $strReturn .= '<li class="'.$strCssClass.'"><a href="'.CONTREXX_SCRIPT_PATH.'?section=Forum&amp;cmd=searchTags&amp;term='.$strTag.'" title="'.$strTag.'">'.$strTag.'</a></li>';
            }

            $strReturn .= '</ul>';
        }

        return $strReturn;
    }

    /**
     * Creates the html-source for a tag-hitlist with the $intNumberOfTags-most used keywords.
     *
     * @param   integer     $intNumberOfTags: the hitlist contains less or equals items than this value, depending on the number of keywords used.
     * @return  string      html-source for the tag hitlist.
     */
    function getTagHitlist($intNumberOfTags = 0) {
        $strReturn      = '';
        $arrKeywords = $this->createKeywordArray();
        arsort($arrKeywords); //Order Descending by Value

        $intNumberOfTags = ($intNumberOfTags == 0) ? intval($this->_arrSettings['tag_count']) : intval($intNumberOfTags);
        $intNumberOfTags = (count($arrKeywords) < $intNumberOfTags) ? count($arrKeywords) : $intNumberOfTags;

        if ($intNumberOfTags > 0) {
            $strReturn = '<ol class="forumTagHitlist">';

            $intTagCounter = 0;
            foreach (array_keys($arrKeywords) as $strTag) {
                $strReturn .= '<li class="forumTagHitlistItem"><a href="'.CONTREXX_SCRIPT_PATH.'?section=Forum&amp;cmd=searchTags&amp;term='.$strTag.'" title="'.$strTag.'">'.$strTag.'</a></li>';
                ++$intTagCounter;

                if ($intTagCounter == $intNumberOfTags) {
                    break;
                }
            }

            $strReturn .= '</ol>';
        }

        return $strReturn;
    }

    /**
     * Creates an array containing all used tags (keywords) with an calculated number of points. The points depend of the usage-frequency,
     * the number of hits for the assigned topics, voting of the assigned topics and the number of commend of the assigned topics. The
     * array is ordered alphabetically by the keywords.
     *
     * @return  array       Sorted array in the format $arrExample[Keyword] = NumberOfPoints.
     */
    function createKeywordArray() {
        $arrKeywords    = array();
        $arrEntries     = $this->createPostArray(0, -1);
        if (count($arrEntries) > 0) {
            //Count total-values first
            $intTotalHits = 1;
// Unused
//            $count = 0;
            foreach ($arrEntries as $arrEntryValues) {
                $intTotalHits += $arrEntryValues['views'];
                $ratings[] = $arrEntryValues['rating'];
                $minRating = min($ratings);
                $maxRating = max($ratings);
            }

            foreach ($arrEntries as $arrEntryValues) {
                if (trim($arrEntryValues['keywords']) == '') {
                    continue;
                }
                //Calculate the keyword-value first
                $intKeywordValue = 1;                                                                                       #Base-Value
                $intKeywordValue = $intKeywordValue + ceil(100 * $arrEntryValues['views'] / $intTotalHits);                 #Include Hits (More visited = bigger font)
                $intKeywordValue = $intKeywordValue + ceil(($arrEntryValues['rating']) * ($maxRating - $minRating));    #Include Votes (Better rated = bigger font)

                $dblDateFactor = 0;
                if ($arrEntryValues['timestamp_edited'] > time() - 7 * 24 * 60 * 60) {
                    $dblDateFactor = 1.0;
                } elseif ($arrEntryValues['timestamp_edited'] > time() - 14 * 24 * 60 * 60) {
                    $dblDateFactor = 0.8;
                } elseif ($arrEntryValues['timestamp_edited'] > time() - 30 * 24 * 60 * 60) {
                    $dblDateFactor = 0.6;
                } elseif ($arrEntryValues['timestamp_edited'] > time() - 90 * 24 * 60 * 60) {
                    $dblDateFactor = 0.4;
                } elseif ($arrEntryValues['timestamp_edited'] > time() - 180 * 24 * 60 * 60) {
                    $dblDateFactor = 0.2;
                } else {
                    $dblDateFactor = 0.1;
                }

                $intKeywordValue = ceil($intKeywordValue * $dblDateFactor); #Include Date (Newer = bigger font)

                //Split tags
                $arrEntryTags = explode(',',$arrEntryValues['keywords']);
                foreach($arrEntryTags as $strTag) {
                    $strTag = trim($strTag);
                    if (array_key_exists($strTag,$arrKeywords)) {
                        $arrKeywords[$strTag] += $intKeywordValue;
                    } else {
                        $arrKeywords[$strTag] = $intKeywordValue;
                    }

                }
            }
        }

        ksort($arrKeywords);

        return $arrKeywords;
    }

    /**
     * returns an array containing attachment information
     *
     * @param string $file
     * @return array $arrReturn 'path','webpath','extension', false if attachment doesn't exist in filesystem
     */
    function _getAttachment($file) {
        $file = contrexx_addslashes($file);
        $cx   = \Cx\Core\Core\Controller\Cx::instanciate();
        $path = $cx->getWebsiteMediaForumUploadPath() . '/';
        if (!file_exists($path . $file) || empty($file)) {
            return false;
        }
        $pathinfo = pathinfo($file);
        $icon = \Cx\Core_Modules\Media\Controller\MediaLibrary::_getIconWebPath() . \Cx\Core_Modules\Media\Controller\MediaLibrary::_getIcon($path . $file) . '.png';
        return array(
            'name'      => $file,
            'path'      => $path . $file,
            'webpath'   => $cx->getWebsiteMediaForumUploadWebPath() . '/' . $file,
            'extension' => $pathinfo['extension'],
            'icon'      => $icon,
            'size'      => filesize($path . $file),
        );
    }

    /**
     * handles the upload of a file
     *
     * @param string $inputName name of the HTML input element used to upload the file
     *
     * @return array $uploadedFileInfo array containing the properties for the uploaded file,
     *                                 false when upload has failed
     */
    function _handleUpload($inputName)
    {
        global $_ARRAYLANG;

        $fileName = isset($_POST[$inputName]) ? contrexx_input2raw($_POST[$inputName]) : '';
        if (empty($fileName)) {
            return array(
                    'name'      => '',
                    'path'      => '',
                    'size'      => 0,
                    );
        }

        $uploaderId = isset($_POST['forumUploaderId']) ? contrexx_input2raw($_POST['forumUploaderId']) : '';
        if (empty($uploaderId)) {
            \DBG::log('Uploader id is empty');
            return false;
        }

        //Re-initialize the $sessionObj if it is empty
        $cx  = \Cx\Core\Core\Controller\Cx::instanciate();
        $sessionObj = $cx->getComponent('Session')->getSession();
        $tempPath = $sessionObj->getTempPath() . '/' . $uploaderId . '/' . $fileName;
        if (!\Cx\Lib\FileSystem\FileSystem::exists($tempPath)) {
            return false;
        }

        $cx       = \Cx\Core\Core\Controller\Cx::instanciate();
        $filePath = $cx->getWebsiteMediaForumUploadPath() . '/';
        $pathinfo = pathinfo($fileName);

        $i = 1;
        while (\Cx\Lib\FileSystem\FileSystem::exists($filePath . $fileName)) {
            $fileName = $pathinfo['filename'] . '_' . $i++ . '.' . $pathinfo['extension'];
        }

        if (\Cx\Lib\FileSystem\FileSystem::move($tempPath, $filePath . $fileName, true) === false) {
            $this->_objTpl->setVariable('TXT_FORUM_ERROR', $filePath . $fileName . ': ' . $_ARRAYLANG['TXT_FORUM_UPLOAD_NOT_MOVABLE']);
            return false;
        }

        return array(
            'name'      => contrexx_addslashes($fileName),
            'path'      => $filePath,
            'size'      => filesize($filePath . $fileName),
        );
    }

    /**
     * Creates an array containing all frontend-languages.
     *
     * Contents:
     * $arrValue[$langId]['short']        =>    For Example: en, de, fr, de-CH, ...
     * $arrValue[$langId]['long']        =>    For Example: 'English', 'Deutsch', 'French', ...
     *
     * @return    array        $arrReturn
     */
    function createLanguageArray() {

        $arrReturn = array();

        foreach (\FWLanguage::getActiveFrontendLanguages() as $frontendLanguage) {
            $arrReturn[$frontendLanguage['id']] = array(
                'short' =>  stripslashes($frontendLanguage['lang']),
                'long'  =>  htmlentities(stripslashes($frontendLanguage['name']),ENT_QUOTES, CONTREXX_CHARSET)
            );
        }

        return $arrReturn;
    }

    /**
     * Creates an array containing all translations of the categories. Example: $arrValue[$categoryId][$langId]['name'].
     *
     * @global  ADONewConnection
     * @return  array       $arrReturn
     */
    function createTranslationArray() {
        global $objDatabase;

        $arrReturn = array();

        $objResult = $objDatabase->Execute('SELECT      category_id,
                                                        lang_id,
                                                        name,
                                                        description
                                            FROM        '.DBPREFIX.'module_forum_categories_lang
                                            ORDER BY    category_id ASC
                                        ');

        while (!$objResult->EOF) {
            $arrReturn[$objResult->fields['category_id']][$objResult->fields['lang_id']] = array(   'name'  =>  htmlentities(stripslashes($objResult->fields['name']),ENT_QUOTES, CONTREXX_CHARSET),
                                                                                                    'desc'  =>  htmlentities(stripslashes($objResult->fields['description']),ENT_QUOTES, CONTREXX_CHARSET)
                                                                                                );
            $objResult->MoveNext();
        }

        return $arrReturn;
    }

    /**
     * Create an array containing all "thread-icons". Key of the array is a number: 1.gif -> 1.
     *
     * @return  array       $arrReturn
     */
    function createThreadIconArray() {
        $arrReturn = array();

        $handleDir = dir(ASCMS_MODULE_PATH.'/Forum/View/Media/thread');
        while (true) {
            $strFile = $handleDir->read();
            if ($strFile === false) break;
            if (preg_match('/\.\.?/', $strFile)) continue;
            $arrFileInfos = pathinfo(ASCMS_MODULE_PATH.'/Forum/View/Media/thread'.$strFile);
            if ($arrFileInfos['extension'] == 'gif') {
                $arrReturn[basename($strFile,'.gif')] =
                    '<img src="'.ASCMS_MODULE_WEB_PATH.'/Forum/View/Media/thread'.
                    $strFile.'" border="0" alt="'.$strFile.
                    '" title="'.$strFile.'" />';
            }
        }
        $handleDir->close();
        return $arrReturn;
    }

    /**
     * Returns the <img>-Code for a desired icon.
     *
     * @param   integer     $intIcon: The icon with this "id" (1.gif -> 1) will be return
     * @return  string      <img>-Sourcecode if the id exists, otherwise "nbsp;"
     */
    function getThreadIcon($intIcon) {
        $intIcon = intval($intIcon);

        if (!is_array($this->_arrIcons)) {
            $this->_arrIcons = $this->createThreadIconArray();
        }

        if ($intIcon != 0 && array_key_exists($intIcon,$this->_arrIcons)) {
            return $this->_arrIcons[$intIcon];
        } else {
            return '&nbsp;';
        }
    }

    /**
     * Creates and returns an array containing all forum-information
     *
     * @param   integer     $intLangId: If this param has another value then zero, only the forums for the lang with this id will be loaded
     * @param   integer     $intParCat
     * @param   integer     $intLevel
     * @return  array       $arrForums
     */
    function createForumArray($intLangId = 0, $intParCat = 0, $intLevel = 0) {
        $arrForums = array();
        $this->createForumTree($arrForums, $intParCat, $intLevel, $intLangId);
        return $arrForums;
    }

    /**
     * This is a recursive help-function of "createForumArray()".
     *
     * @global  ADONewConnection
     * @global  array
     * @param   reference   $arrForums: reference to an array. To this array the information are written.
     * @param   integer     $intParCat: the recursive-step starts with this category
     * @param   integer     $intLevel: Current level (0 is base-level)
     * @param   integer     $intLangId: Only forums with this lang-id will be loaded (0 = all languages)
     */
    function createForumTree(&$arrForums, $intParCat=0, $intLevel=0, $intLangId=0) {
        global $objDatabase, $_ARRAYLANG;
        $intParCat  = intval($intParCat);
        $intLevel   = intval($intLevel);
        $intLangId  = intval($intLangId);

        $objResult = $objDatabase->Execute('SELECT      id          AS cId,
                                                        parent_id   AS cParentId,
                                                        order_id    AS cOrderId,
                                                        `status`    AS cStatus
                                            FROM        '.DBPREFIX.'module_forum_categories
                                            WHERE       parent_id = '.$intParCat.'
                                            ORDER BY    order_id ASC'
                                        );
        while (!$objResult->EOF) {
            //Last post information
            if ($intLangId == 0 || array_key_exists($intLangId,$this->_arrTranslations[$objResult->fields['cId']])) {
                if ($intLevel == 0) {
// Unused
//                    $strPostCount   = '';
                    $strLastPost    = '';
                } else {
                    $objSubResult = $objDatabase->Execute(' SELECT  thread_count    AS sThreadCount,
                                                                    post_count      AS sPostCount,
                                                                    last_post_id    AS sLastPostId
                                                            FROM    '.DBPREFIX.'module_forum_statistics
                                                            WHERE   category_id = '.$objResult->fields['cId'].'
                                                            LIMIT   1
                                                        ');

                    $intThreadCount = intval($objSubResult->fields['sThreadCount']);
                    $intPostCount   = intval($objSubResult->fields['sPostCount']);
                    $intLastPost    = intval($objSubResult->fields['sLastPostId']);


                    if ($intLastPost != 0) {
                        //get information about the topic
                        $objSubResult = $objDatabase->Execute(' SELECT  time_created    AS pTimeCreated,
                                                                        subject         AS pSubject
                                                                FROM    '.DBPREFIX.'module_forum_postings
                                                                WHERE   id = '.$intLastPost.'
                                                                LIMIT   1
                                                            ');


                        $strLastPost        = $this->_shortenString($objSubResult->fields['pSubject'], $this->_maxStringlength/2);
                        $strLastPostDate    = date(ASCMS_DATE_FORMAT,$objSubResult->fields['pTimeCreated']);
                    } else {
                        // no last topic, write text into array
                        $strLastPost        = $_ARRAYLANG['TXT_FORUM_NO_POSTINGS'];
                        $strLastPostDate    = '';
                    }
                }

                $arrForums[$objResult->fields['cId']] = array(  'id'                =>  $objResult->fields['cId'],
                                                                'parent_id'         =>  $objResult->fields['cParentId'],
                                                                'level'             =>  $intLevel,
                                                                'status'            =>  $objResult->fields['cStatus'],
                                                                'order_id'          =>  $objResult->fields['cOrderId'],
                                                                'thread_count'      =>  !empty($intThreadCount) ? $intThreadCount : 0,
                                                                'post_count'        =>  !empty($intPostCount) ? $intPostCount : 0,
                                                                'last_post_id'      =>  !empty($intLastPost) ? $intLastPost : 0,
                                                                'last_post_str'     =>  !empty($strLastPost) ? $strLastPost : $_ARRAYLANG['TXT_FORUM_NO_SUBJECT'],
                                                                'last_post_date'    =>  !empty($strLastPostDate) ? $strLastPostDate : '',
                                                                'languages'         =>  $this->_arrTranslations[$objResult->fields['cId']],
                                                                'name'              =>  isset($this->_arrTranslations[$objResult->fields['cId']][$this->_intLangId]) ? $this->_arrTranslations[$objResult->fields['cId']][$this->_intLangId]['name'] : '',
                                                                'description'       =>  isset($this->_arrTranslations[$objResult->fields['cId']][$this->_intLangId]) ? $this->_arrTranslations[$objResult->fields['cId']][$this->_intLangId]['desc'] : '',
                                            );
                $this->createForumTree($arrForums,$objResult->fields['cId'],$intLevel+1);
            }
            $objResult->MoveNext();
        }
    }

    /**
     * create an array containing all posts from the specified thread
     * if the second argument $pos is -1, then all posts are being returned, otherwise
     * it will be limited to the thread_paging setting
     *
     * if $intThreadId = 0 and $pos = -1, then all posts from all threads are returned
     *
     * @param   integer $intThreadId ID of the thread
     * @param   integer $pos position at which the posts will be read from (for paging)
     * @return  array   $arrReturn
     */
    function createPostArray($intThreadId=0, $pos=0)
    {
        global $objDatabase, $_ARRAYLANG;

        $intThreadId = intval($intThreadId);
        $arrReturn = array();

        if ($intThreadId > 0) {
            $WHERE = ' WHERE thread_id='.$intThreadId;
        } elseif ($pos < 0) {
            $WHERE = ' ';
        }

        $objRSCount = $objDatabase->SelectLimit('   SELECT count(1) AS `cnt` FROM '.DBPREFIX.'module_forum_postings '.$WHERE, 1);

        if ($objRSCount !== false) {
            $this->_postCount = $objRSCount->fields['cnt'];
        }
        if ($pos == -1) {
            $this->_arrSettings['posting_paging'] = $this->_postCount+1;
            $pos = 0;
        }

        $objResult = $objDatabase->SelectLimit('SELECT      id,
                                                            category_id,
                                                            thread_id,
                                                            user_id,
                                                            time_created,
                                                            time_edited,
                                                            is_locked,
                                                            is_sticky,
                                                            rating,
                                                            views,
                                                            icon,
                                                            keywords,
                                                            subject,
                                                            content,
                                                            attachment
                                                FROM        '.DBPREFIX.'module_forum_postings
                                                '.$WHERE.'
                                                ORDER BY    prev_post_id, time_created ASC
                                            ', $this->_arrSettings['posting_paging'], $pos);
        $intReplies = $objResult->RecordCount();

        $postNumber=$pos+1;
        while (!$objResult->EOF) {
            $strAuthor = $this->_getUserName($objResult->fields['user_id']);

//            $content = stripslashes($objResult->fields['content']);
            $content = \Cx\Core\Wysiwyg\Wysiwyg::prepareBBCodeForOutput($objResult->fields['content']);

            $arrReturn[$objResult->fields['id']] =  array(  'id'                =>  $objResult->fields['id'],
                                                            'thread_id'         =>  $objResult->fields['thread_id'],
                                                            'category_id'       =>  $objResult->fields['category_id'],
                                                            'user_id'           =>  $objResult->fields['user_id'],
                                                            'user_name'         =>  $strAuthor,
                                                            'time_created'      =>  date(ASCMS_DATE_FORMAT,$objResult->fields['time_created']),
                                                            'time_edited'       =>  date(ASCMS_DATE_FORMAT,$objResult->fields['time_edited']),
                                                            'timestamp_created' =>  $objResult->fields['time_created'],
                                                            'timestamp_edited'  =>  $objResult->fields['time_edited'],
                                                            'is_locked'         =>  intval($objResult->fields['is_locked']),
                                                            'is_sticky'         =>  intval($objResult->fields['is_sticky']),
                                                            'rating'            =>  intval($objResult->fields['rating']),
                                                            'post_icon'         =>  $this->getThreadIcon($objResult->fields['icon']),
                                                            'replies'           =>  $intReplies,
                                                            'views'             =>  intval($objResult->fields['views']),
                                                            'icon'              =>  intval($objResult->fields['icon']),
                                                            'keywords'          =>  htmlspecialchars($objResult->fields['keywords'], ENT_QUOTES, CONTREXX_CHARSET),
                                                            'subject'           =>  (!trim($objResult->fields['subject']) == '') ? htmlspecialchars($objResult->fields['subject'], ENT_QUOTES, CONTREXX_CHARSET) : $_ARRAYLANG['TXT_FORUM_NO_SUBJECT'],
                                                            'content'           =>  $content,
                                                            'attachment'        =>  htmlspecialchars($objResult->fields['attachment'], ENT_QUOTES, CONTREXX_CHARSET),
                                                            'post_number'       =>  $postNumber++,
                                                        );
            $objResult->MoveNext();
        }
        return $arrReturn;
    }

    /**
     * get the post data for a specific posting
     *
     * @param integer $intPostId
     * @return assoc. array containig the post data
     */
    function _getPostingData($intPostId) {
        global $objDatabase;
        $query = '  SELECT * FROM `'.DBPREFIX.'module_forum_postings`
                    WHERE `id` = '.$intPostId;
        if ( ($objRS = $objDatabase->SelectLimit($query, 1)) !== false) {
            return $objRS->fields;
        } else {
            die('DB error: '.$objDatabase->ErrorMsg());
        }
    }

    /**
     * return username by userId
     *
     * @param integer $userId
     * @return string name on success, bool false if empty record
     */
    function _getUserName($userId)
    {
        if ($userId < 1) {
            return $this->_anonymousName;
        }

        $objFWUser = \FWUser::getFWUserObject();
        if (($objUser = $objFWUser->objUser->getUser($userId)) === false) {//no record found for thus $userid
            return $this->_anonymousName;
        }
        return htmlentities($objUser->getUsername(), ENT_QUOTES, CONTREXX_CHARSET);
    }

    /**
     * creates an array with thread information
     *
     * @param integer $intForumId
     * @param integer $pos
     * @return array
     */
    function createThreadArray($intForumId=0, $pos=0)
    {
        global $objDatabase, $_ARRAYLANG;

        $intForumId = intval($intForumId);
        $arrReturn  = array();

        $objFWUser = \FWUser::getFWUserObject();

        $objRSCount = $objDatabase->SelectLimit('SELECT count(1) AS `cnt` FROM '.DBPREFIX.'module_forum_postings
                                                WHERE   prev_post_id=0
                                                AND     category_id='.$intForumId, 1);
        if ($objRSCount !== false) {
            $this->_threadCount = $objRSCount->fields['cnt'];
        }

        $objResult = $objDatabase->SelectLimit('SELECT      id,
                                                            category_id,
                                                            user_id,
                                                            thread_id,
                                                            time_created,
                                                            time_edited,
                                                            is_locked,
                                                            is_sticky,
                                                            views,
                                                            icon,
                                                            subject,
                                                            content
                                                FROM        '.DBPREFIX.'module_forum_postings
                                                WHERE   prev_post_id=0  AND
                                                            category_id='.$intForumId.'
                                                ORDER BY    is_sticky DESC, time_created DESC
                                            ', $this->_arrSettings['thread_paging'], $pos);
        while (!$objResult->EOF) {
            //Count replies
            $objSubResult = $objDatabase->Execute(' SELECT  id
                                                    FROM    '.DBPREFIX.'module_forum_postings
                                                    WHERE   thread_id='.$objResult->fields['thread_id'].'
                                                ');
            $intReplies = intval($objSubResult->RecordCount()-1);

            $strAuthor = $this->_getUserName($objResult->fields['user_id']);


            //Get information about last written answer
            $objSubResult = $objDatabase->SelectLimit(' SELECT  id              AS pId,
                                                                time_created    AS pTime,
                                                                user_id         AS pUserId
                                                    FROM        '.DBPREFIX.'module_forum_postings
                                                    WHERE       thread_id = '.$objResult->fields['thread_id'].'
                                                    ORDER BY    time_created DESC
                                                ', 1);
            if ($objSubResult->RecordCount() == 1) {
                $intLastpostId      = intval($objSubResult->fields['pId']);
                $strLastpostDate    = date(ASCMS_DATE_FORMAT, $objSubResult->fields['pTime']);
                $strLastpostUser    = ($objUser = $objFWUser->objUser->getUser($objSubResult->fields['pUserId'])) ? htmlentities($objUser->getUsername(), ENT_QUOTES, CONTREXX_CHARSET) : $this->_anonymousName;
            } else {
                //There are no replies yet
                $intLastpostId      = intval($objResult->fields['id']);
                $strLastpostDate    = date(ASCMS_DATE_FORMAT,$objResult->fields['time_created']);
                $strLastpostUser    = $strAuthor;
            }

            $arrReturn[$objResult->fields['id']] =  array(  'id'                =>  $objResult->fields['id'],
                                                    'category_id'       =>  $objResult->fields['category_id'],
                                                    'thread_id'         =>  $objResult->fields['thread_id'],
                                                    'user_id'           =>  $objResult->fields['user_id'],
                                                    'user_name'         =>  $strAuthor,
                                                    'time_created'      =>  date(ASCMS_DATE_FORMAT, $objResult->fields['time_created']),
                                                    'time_edited'       =>  date(ASCMS_DATE_FORMAT, $objResult->fields['time_edited']),
                                                    'is_locked'         =>  intval($objResult->fields['is_locked']),
                                                    'is_sticky'         =>  intval($objResult->fields['is_sticky']),
                                                    'thread_icon'       =>  $this->getThreadIcon($objResult->fields['icon']),
                                                    'replies'           =>  $intReplies,
                                                    'views'             =>  intval($objResult->fields['views']),
                                                    'lastpost_id'       =>  $intLastpostId,
                                                    'lastpost_time'     =>  $strLastpostDate,
                                                    'lastpost_author'   =>  $strLastpostUser,
                                                    'subject'           =>  (!trim($objResult->fields['subject']) == '') ? htmlspecialchars($objResult->fields['subject'], ENT_QUOTES, CONTREXX_CHARSET) : $_ARRAYLANG['TXT_FORUM_NO_SUBJECT'],
                                                    'content'           =>  stripslashes($objResult->fields['content'])
                                                );
            $objResult->MoveNext();
        }
        return $arrReturn;
    }

    /**
     * update views of an item
     *
     * @param integer $intThreadId
     * @return bool success
     */
    function updateViews($intThreadId, $postId = 0) {
        global $objDatabase;

        if (checkForSpider()) {
            return true;
        }

        $where = '';
        if ($postId > 0) {
            $where = ' AND id='.intval($postId);
        }

        $query = '  UPDATE `'.DBPREFIX.'module_forum_postings`
                    SET `views` = (`views` + 1)
                    WHERE `thread_id` = '.$intThreadId.
                    $where.' LIMIT 1';



        if ($objDatabase->Execute($query) === false) {
            return false;
// Unreachable
//            echo "DB error in function: updateViews()";
        }
        return true;
    }

    /**
     * update views when adding a new item
     *
     * @param integer $intCatId category ID
     * @param integer $last_post_id last post id of the thread
     * @param bool  $updatePostOnly whether to update only the post count
     * @return bool success
     */
    function updateViewsNewItem($intCatId, $last_post_id, $updatePostOnly = false) {
        global $objDatabase;

        if ($updatePostOnly) {
            $updateQueryStats = "UPDATE `".DBPREFIX."module_forum_statistics` SET `post_count` = `post_count`+1,
                                        `last_post_id` = ".$last_post_id."
                                        WHERE `category_id` = ".$intCatId." LIMIT 1";

        } else {
            $updateQueryStats = "UPDATE `".DBPREFIX."module_forum_statistics` SET `thread_count` = `thread_count`+1,
                                        `post_count` = `post_count`+1,
                                        `last_post_id` = ".$last_post_id."
                                        WHERE `category_id` = ".$intCatId." LIMIT 1";

        }

        if ($objDatabase->Execute($updateQueryStats)) {
            return true;
        }
        return false;
    }

    /**
     * Create the Navtree for the forums
     *
     * @param integer $intForumId
     * @param array $arrForums
     * @return string HTML representation of the generated NavTree
     */
    function _createNavTree($intForumId, $arrForums = null) {
        global $objDatabase, $_ARRAYLANG;
        if (!$arrForums) {
            $arrForums = $this->createForumArray($this->_intLangId);
        }
        $strNavTree = '';
        $pId = $arrForums[$intForumId]['parent_id'];

        $query = "SELECT `id` FROM ".DBPREFIX."module_forum_categories WHERE `parent_id` = 0";
        if (($objRS = $objDatabase->Execute($query)) !== false) {
            while(!$objRS->EOF) {
                $parents[] = $objRS->fields['id'];
                $objRS->MoveNext();
            }
        }
        while($pId > 0) {
            $intForumId = $pId;
            if (in_array($intForumId, $parents)) {
                $strNavTree = '<a href="'.CONTREXX_SCRIPT_PATH.'?section=Forum&amp;cmd=cat&amp;id='.$intForumId.'">'.$this->_shortenString($arrForums[$intForumId]['name'], $this->_maxStringlength)."</a> > \n".$strNavTree;
            } else {
                $strNavTree = '<a href="'.CONTREXX_SCRIPT_PATH.'?section=Forum&amp;cmd=board&amp;id='.$intForumId.'">'.$this->_shortenString($arrForums[$intForumId]['name'], $this->_maxStringlength)."</a> > \n".$strNavTree;
            }
            $pId = $arrForums[$pId]['parent_id'];
        }

        $strNavTree = '<a href="'.CONTREXX_SCRIPT_PATH.'?section=Forum"> '.$_ARRAYLANG['TXT_FORUM_OVERVIEW_FORUM'].' </a> >'."\n".$strNavTree;
        return $strNavTree;
    }

    /**
     * This function create html-source for a dropdown-menu containing all categories / forums
     *
     * @param   string      $strSelectName: name-attribute of the <select>-tag
     * @param   integer     $intSelected: The category / forum with this id will be "selected"
     * @param   string      $strSelectAdds: Additional tags / styles for the <select>-tag
     * @param   string      $strOptionAdds: Additional tags / styles for the <option>-tag
     * @return  string      $strSource: HTML-Source of the dropdown-menu
     */
    function createForumDD($strSelectName,$intSelected=0,$strSelectAdds='', $strOptionAdds='', $useCat = true, $backend = false)
    {
        global $_ARRAYLANG;

        $intSelected = intval($intSelected);
        $arrForums   = $this->createForumArray();
        $strSource   =
            '<select name="'.$strSelectName.'" '.$strSelectAdds." >\n".
            '<option value="0"> --'.$_ARRAYLANG['TXT_FORUM_OVERVIEW_FORUM'].
            '-- </option>';

        if (count($arrForums) > 0) {
            foreach ($arrForums as $arrValues) {
                if (!$arrValues['status'] && !$backend) {//skip non-active
                    continue;
                }
                ($arrValues['id'] == $intSelected) ? $strSelected = ' selected="selected"' : $strSelected = '';

                $strSpacer = '';
                for($i=0; $i<$arrValues['level'];++$i) {
                    $strSpacer .= '&nbsp;&nbsp;&nbsp;&nbsp;';
                }
                if ($arrValues['parent_id'] != 0) {
                    $strSource .= '<option value="'.$arrValues['id'].'" '.$strOptionAdds.$strSelected.'>'.$strSpacer.$this->_shortenString($arrValues['name'], $this->_maxStringlength+10+($arrValues['level'])).'</option>';
                } else {
                    if ($useCat) {
                        $strSource .= '<option value="'.$arrValues['id'].'_cat" '.$strOptionAdds.$strSelected.'>'.$strSpacer.$this->_shortenString($arrValues['name'], $this->_maxStringlength+($arrValues['level'])).'</option>';
                    } else {
                        $strSource .= '<option value="'.$arrValues['id'].'" '.$strOptionAdds.$strSelected.'>'.$strSpacer.$this->_shortenString($arrValues['name'], $this->_maxStringlength+10+($arrValues['level'])).'</option>';
                    }
                }
            }
        }
        $strSource .= '</select>';
        return $strSource;
    }

    /**
     * Get name of category
     *
     * @param integer $intCatId
     * @return array (name, description)
     */
    function _getCategoryName($intCatId)
    {
        global $objDatabase;

        $query = 'SELECT `name`, `description` FROM ".DBPREFIX."module_forum_categories_lang WHERE category_id='.$intCatId
        .' AND lang_id='.$this->_intLangId;
        $objRS = $objDatabase->Execute($query);
        if ($objRS && !$objRS->EOF)
            return array('name' => $objRS->fields['name'], 'description' => $objRS->fields['description']);
        return array('name' => '', 'description' => '');
    }

    /**
     * fetch the latest entries
     *
     * @return array $arrLatestEntries
     */
    function _getLatestEntries()
    {
        global $objDatabase, $_ARRAYLANG;

        $index = 0;
        $arrLatestEntries = array();
        $query = (empty($this->_arrSettings['latest_post_per_thread'])
            ? "SELECT `id` , `category_id` , `thread_id` , `subject` , `user_id` , `time_created`
                 FROM `".DBPREFIX."module_forum_postings`
                ORDER BY if ( `time_edited`, `time_edited`, `time_created` ) DESC"
            : "SELECT `id`, `category_id`, `thread_id`, `subject`, `user_id`, `time_created`
                 FROM `".DBPREFIX."module_forum_postings`
                WHERE `id` IN (
                     SELECT max( `id` )
                     FROM `".DBPREFIX."module_forum_postings`
                     GROUP BY `thread_id`
                     ORDER BY `time_created` DESC
                )
                ORDER BY `time_created` DESC"
        );

        if (($objRS = $objDatabase->SelectLimit($query, $this->_arrSettings['latest_entries_count'])) !== false) {
            $objFWUser = \FWUser::getFWUserObject();

            while(!$objRS->EOF) {
                $arrLatestEntries[$index] = array();
                $arrLatestEntries[$index]['subject'] = !empty($objRS->fields['subject']) ? $objRS->fields['subject'] : $_ARRAYLANG['TXT_FORUM_NO_SUBJECT'];
                $arrLatestEntries[$index]['post_id'] = $objRS->fields['id'];
                $arrLatestEntries[$index]['thread_id'] = $objRS->fields['thread_id'];
                $arrLatestEntries[$index]['cat_id'] = $objRS->fields['category_id'];
                $arrLatestEntries[$index]['user_id'] = $objRS->fields['user_id'];
                $arrLatestEntries[$index]['time'] = $this->_createLatestEntriesDate($objRS->fields['time_created']);
                $query = "  SELECT `categories`.`name` AS `cName`
                            FROM `".DBPREFIX."module_forum_categories_lang` AS `categories`
                            WHERE `category_id` = ".$objRS->fields['category_id']."
                            AND `lang_id` = ".$this->_intLangId;
                if ($objRS->fields['user_id'] > 0 && ($objUser = $objFWUser->objUser->getUser($objRS->fields['user_id']))) {
                    $arrLatestEntries[$index]['username'] = htmlentities($objUser->getUsername(), ENT_QUOTES, CONTREXX_CHARSET);
                } else {
                    $arrLatestEntries[$index]['username'] = $this->_anonymousName;
                }

                if (($objRSNames = $objDatabase->SelectLimit($query, 1)) !== false) {
                    $arrLatestEntries[$index]['category_name']  = $objRSNames->fields['cName'];
                } else {
                    die('DB error: '.$objDatabase->ErrorMsg());
                }

                $query = "  SELECT 1 FROM `".DBPREFIX."module_forum_postings`
                            WHERE `thread_id` = ".$objRS->fields['thread_id'];
                if (($objRSCount = $objDatabase->Execute($query)) !== false) {
                    $arrLatestEntries[$index]['postcount'] = $objRSCount->RecordCount();
                } else {
                    die('DB error: '.$objDatabase->ErrorMsg());
                }
                $objRS->MoveNext();
                $index++;
            }
        } else {
            die('DB error: '.$objDatabase->ErrorMsg());
        }
        return $arrLatestEntries;
    }

    /**
     * prepare date for the lates entries
     *
     * if $date is from today, return time, otherwise date
     *
     * @param int timestamp $date
     * @return formatted date|time
     */
    function _createLatestEntriesDate($date) {
        if (date('d.m.Y', time()) == date('d.m.Y', $date)) {
            return date('H:i:s', $date);
        } else {
            return date('d.m.Y', $date);
        }
    }

    /**
     * show the latest entries
     *
     * @param array $arrLatestEntries latest entries
     * @return void
     */
    function _showLatestEntries($arrLatestEntries) {
        global $_ARRAYLANG;
        $count = min(count($arrLatestEntries), $this->_arrSettings['latest_entries_count']);
        $this->_objTpl->setGlobalVariable(array(
            'TXT_FORUM_THREAD'              => $_ARRAYLANG['TXT_FORUM_THREAD'],
            'TXT_FORUM_OVERVIEW_FORUM'      => $_ARRAYLANG['TXT_FORUM_OVERVIEW_FORUM'],
            'TXT_FORUM_THREAD_STRATER'      => $_ARRAYLANG['TXT_FORUM_THREAD_STRATER'],
            'TXT_FORUM_POST_COUNT'          => $_ARRAYLANG['TXT_FORUM_POST_COUNT'],
            'TXT_FORUM_THREAD_CREATE_DATE'  => $_ARRAYLANG['TXT_FORUM_THREAD_CREATE_DATE'],
            'TXT_FORUM_LATEST_ENTRIES'      => sprintf($_ARRAYLANG['TXT_FORUM_LATEST_ENTRIES'], $count),
        ));
        $rowclass=0;
        foreach ($arrLatestEntries as $entry) {
            $strUserProfileLink = ($entry['user_id'] > 0) ? '<a href="'.CONTREXX_DIRECTORY_INDEX.'?section=Access&amp;cmd=user&amp;id='.$entry['user_id'].'" title="'.$entry['username'].'">'.$entry['username'].'</a>' : $entry['username'] ;
            $this->_objTpl->setVariable(array(
                'FORUM_THREAD'              =>  '<a href="'.CONTREXX_SCRIPT_PATH.'?section=Forum&amp;cmd=thread&amp;postid='.$entry['post_id'].'&amp;l=1&amp;id='.$entry['thread_id'].'#p'.$entry['post_id'].'" title="'.$entry['subject'].'">'.$this->_shortenString($entry['subject'], $this->_maxStringlength).'</a>',
                'FORUM_FORUM_NAME'          =>  '<a href="'.CONTREXX_SCRIPT_PATH.'?section=Forum&amp;cmd=board&amp;id='.$entry['cat_id'].'" title="'.$entry['category_name'].'">'.$this->_shortenString($entry['category_name'], $this->_maxStringlength/2).'</a>',
                'FORUM_THREAD_STARTER'      =>  $strUserProfileLink,
                'FORUM_POST_COUNT'          =>  $entry['postcount'],
                'FORUM_THREAD_CREATE_DATE'  =>  $entry['time'],
                'FORUM_ROWCLASS'            =>  ($rowclass++ % 2) + 1
            ));
            $this->_objTpl->parse('latestPosts');
        }
    }

    /**
     * check for permission
     *
     * @param integer $intCatId
     * @param string|array $mixedMode
     * @return bool hasAccess
     */
    function _checkAuth($intCatId, $mixedMode='read')
    {
        if (\Permission::hasAllAccess()) {
            return true;
        }

        $arrAccess = $this->createAccessArray($intCatId);

        if (is_array($mixedMode)) {
            foreach ($mixedMode as $mode) {
                if ($this->_checkGroupAccess($arrAccess, $mode)) {
                    return true;
                }
            }
        } elseif (is_string($mixedMode)) {
            return $this->_checkGroupAccess($arrAccess, $mixedMode);
        }
        return false;
    }

    function _checkGroupAccess($arrAccess, $mode)
    {
        if (empty($this->_arrGroups)) {
            $objFWUser = \FWUser::getFWUserObject();
            $arrGroups = array(0);
            if ($objFWUser->objUser->login()) {
                $arrGroups = array_merge($arrGroups, $objFWUser->objUser->getAssociatedGroupIds());
            }
            $this->_arrGroups = array_intersect($arrGroups, array_keys($arrAccess));
        }
        foreach ($this->_arrGroups as $group) {
            if (!empty($arrAccess[$group][$mode]) && $arrAccess[$group][$mode] == 1) { //has access
                return true;
            }
        }
        return false; //has no access
    }

    /**
     * Creates an array containing all access rights for a category. The index of the array is the group_id.
     *
     * @param   integer     $intCategoryId: The rights of this category will be returned
     * @return  array       $arrAccess
     */
    function createAccessArray($intCategoryId)
    {
        global $objDatabase, $_ARRAYLANG;

        $intCategoryId  = intval($intCategoryId);
        $arrAccess      = array();

        $objFWUser = \FWUser::getFWUserObject();
        $objGroup = $objFWUser->objGroup->getGroups(array('type' => 'frontend'), array('group_id' => 'asc'));
        while (!$objGroup->EOF) {
            $objSubResult = $objDatabase->SelectLimit(' SELECT  `read`,
                                                                `write`,
                                                                `edit`,
                                                                `delete`,
                                                                `move`,
                                                                `close`,
                                                                `sticky`
                                                        FROM    '.DBPREFIX.'module_forum_access
                                                        WHERE   category_id = '.$intCategoryId.'
                                                        AND     group_id = '.$objGroup->getId(), 1);
            if ($objSubResult->RecordCount() == 1) {
                //there are rights existing for this group
                $arrAccess[$objGroup->getId()] = array( 'name'      =>  htmlentities($objGroup->getName(),ENT_QUOTES, CONTREXX_CHARSET),
                                                        'desc'      =>  htmlentities($objGroup->getDescription(),ENT_QUOTES, CONTREXX_CHARSET),
                                                        'read'      =>  $objSubResult->fields['read'],
                                                        'write'     =>  $objSubResult->fields['write'],
                                                        'edit'      =>  $objSubResult->fields['edit'],
                                                        'delete'    =>  $objSubResult->fields['delete'],
                                                        'move'      =>  $objSubResult->fields['move'],
                                                        'close'     =>  $objSubResult->fields['close'],
                                                        'sticky'    =>  $objSubResult->fields['sticky']
                                                    );
            } else {
                //no rights in database for this group
                $arrAccess[$objGroup->getId()] = array( 'name'      =>  htmlentities($objGroup->getName(),ENT_QUOTES, CONTREXX_CHARSET),
                                                        'desc'      =>  htmlentities($objGroup->getDescription(),ENT_QUOTES, CONTREXX_CHARSET)
                                                    );
            }

            $objGroup->next();
        }


        //anonymous access
        $objSubResult = $objDatabase->SelectLimit(' SELECT      `read`,
                                                                `write`,
                                                                `edit`,
                                                                `delete`,
                                                                `move`,
                                                                `close`,
                                                                `sticky`
                                                        FROM    '.DBPREFIX.'module_forum_access
                                                        WHERE   category_id = '.$intCategoryId.'
                                                        AND     group_id = 0', 1);

        $arrAccess[0] = array(  'name'      =>  $_ARRAYLANG['TXT_FORUM_ANONYMOUS_GROUP_NAME'],
                                'desc'      =>  $_ARRAYLANG['TXT_FORUM_ANONYMOUS_GROUP_DESC'],
                                'read'      =>  $objSubResult->fields['read'],
                                'write'     =>  $objSubResult->fields['write'],
                                'edit'      =>  $objSubResult->fields['edit'],
                                'delete'    =>  $objSubResult->fields['delete'],
                                'move'      =>  $objSubResult->fields['move'],
                                'close'     =>  $objSubResult->fields['close'],
                                'sticky'    =>  $objSubResult->fields['sticky']
                            );
        return $arrAccess;
    }

    /**
     * returns the category ID of the specified thread
     *
     * @param int $intThreadId
     * @return integer on succes, bool false on failure
     */
    function _getCategoryIdFromThread($intThreadId) {
        global $objDatabase;
        $query = '  SELECT category_id
                    FROM '.DBPREFIX.'module_forum_postings
                    WHERE thread_id = '.$intThreadId;

        $objRS = $objDatabase->SelectLimit($query, 1);
        if ($objRS !== false) {
            if ($objRS->RecordCount() == 1) {
                return $objRS->fields['category_id'];
            } else {
                return false;
            }
        } else {
            echo "Database Error:".$objDatabase->ErrorMsg();
            return false;
        }
    }

    /**
     * shorten a string to a custom length
     *
     * @param string $str input string
     * @param integer $maxLength desired maximum length
     * @return string $str shortened string, if longer than specified max length
     */
    function _shortenString($str, $maxLength) {
        if (strlen($str) > $maxLength) {
            return substr($str, 0, $maxLength-3).'...';
        }
        return $str;
    }

    /**
     * return position of the last posting in the thread
     *
     * @param int $intPostId
     * @param int $intThreadId
     * @return int $pos
     */
    function _getLastPos($intPostId, $intThreadId=0) {
        global $objDatabase;
        if ($intThreadId < 1) { //thread ID not supplied, select from DB
            $query = "  SELECT `thread_id` FROM `".DBPREFIX."module_forum_postings`
                        WHERE `id` = ".$intPostId;
            if ( ($objRS = $objDatabase->SelectLimit($query,1)) !== false) {
                $intThreadId = $objRS->fields['thread_id'];
            }
        }
        $query = "  SELECT count(1) AS `cnt` FROM `".DBPREFIX."module_forum_postings`
                    WHERE `thread_id` = ".$intThreadId.'
                    ORDER BY `time_created` ASC';
        if (($objRS = $objDatabase->SelectLimit($query, 1)) !== false) {
            $pos = $objRS->fields['cnt']-1;
            if ($pos < $this->_arrSettings['thread_paging']) { //pos is in the first paging page, return 0
                return 0;
            } else { //not in first page, return position
                $remain = $pos % $this->_arrSettings['thread_paging'];
                $pos -= $remain;
                return $pos;
            }
        }
        return false;
    }

    /**
     * return postition of the selected post to edit
     *
     * @param integer $intPostId
     * @param integer $intThreadId
     * @return unknown
     */
    function _getEditPos($intPostId, $intThreadId)
    {
        global $objDatabase;

        $count = 0;
        $query = "  SELECT `id` FROM `".DBPREFIX."module_forum_postings`
                    WHERE `thread_id` = ".$intThreadId.'
                    ORDER BY `time_created` ASC';
        if (($objRS = $objDatabase->Execute($query)) !== false) {
            while(!$objRS->EOF) {
                if ($objRS->fields['id'] == $intPostId) {//id matched, return position of that post
                    $remain = $count % $this->_arrSettings['thread_paging'];
                    $pos = $count - $remain;
                    if ($pos > 0) {
                        return $pos;
                    }
                    return 0;
                }
                $count++;
                $objRS->MoveNext();
            }
        }
    }

    /**
     * Initialize the uploader
     *
     * @return null
     */
    public function initForumUploader()
    {
        $uploader = new \Cx\Core_Modules\Uploader\Model\Entity\Uploader();
        $uploader->setCallback('forumFileUploaderCallback');

        $options = array(
            'id'                 => 'forum-attachment-uploader',
            'allowed-extensions' => explode(',', $this->_arrSettings['allowed_extensions']),
            'style'              => 'display:none',
            'data-upload-limit'  => 1,
        );

        $uploader->setOptions($options);
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $uploader->setFinishedCallback(array(
            $cx->getCodeBaseModulePath().'/Forum/Controller/Forum.class.php',
            '\Cx\Modules\Forum\Controller\Forum',
            'uploadFinished'
        ));

        $this->_objTpl->setVariable(array(
            'FORUM_UPLOADER'    => $uploader->getXhtml(),
            'FORUM_UPLOADER_ID' => $uploader->getId()
        ));
    }
}
