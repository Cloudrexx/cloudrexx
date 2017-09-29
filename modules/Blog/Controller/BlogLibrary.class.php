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
 * Blog library
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Kaelin <thomas.kaelin@comvation.com>
 * @version     $Id: index.inc.php,v 1.01 $
 * @package     cloudrexx
 * @subpackage  module_blog
 */

namespace Cx\Modules\Blog\Controller;

/**
 * Includes
 */
\Env::get('ClassLoader')->loadFile(ASCMS_LIBRARY_PATH.'/activecalendar/activecalendar.php');

/**
 * Blog library
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Kaelin <thomas.kaelin@comvation.com>
 * @version     $Id: index.inc.php,v 1.00 $
 * @package     cloudrexx
 * @subpackage  module_blog
 */
class BlogLibrary {
    var $_boolInnoDb = false;
    var $_intLanguageId;
    var $_intCurrentUserId;
    var $_arrSettings           = array();
    var $_arrLanguages          = array();

    /**
    * Constructor
    *
    */
    function __construct()
    {
        $this->setDatabaseEngine();
        $this->_arrSettings     = $this->createSettingsArray();
        $this->_arrLanguages    = $this->createLanguageArray();
    }


    /**
     * Reads out the used database engine and sets the local variable.
     *
     */
    function setDatabaseEngine() {
        global $objDatabase;

        $objMetaResult = $objDatabase->Execute('SHOW TABLE STATUS LIKE "'.DBPREFIX.'module_blog_settings"');

        if (preg_match('/.*innodb.*/i', $objMetaResult->fields['Engine'])) {
            $this->_boolInnoDb = true;
        }
    }


    /**
     * Create an array containing all settings of the blog-module.
     * Example: $arrSettings[$strSettingName] for the content of $strSettingsName
     *
     * @global  ADONewConnection
     * @return  array       $arrReturn
     */
    function createSettingsArray() {
        global $objDatabase;

        $arrReturn = array();

        $objResult = $objDatabase->Execute('SELECT  name,
                                                    value
                                            FROM    '.DBPREFIX.'module_blog_settings
                                        ');
        if($objResult !== false){
            while (!$objResult->EOF) {
                $arrReturn[$objResult->fields['name']] = stripslashes(htmlspecialchars($objResult->fields['value'], ENT_QUOTES, CONTREXX_CHARSET));
                $objResult->MoveNext();
            }
        }
        return $arrReturn;
    }


    /**
     * Creates an array containing all frontend-languages.
     *
     * Contents:
     * $arrValue[$langId]['short']      =>  For Example: en, de, fr, de-CH, ...
     * $arrValue[$langId]['long']       =>  For Example: 'English', 'Deutsch', 'French', ...
     *
     * @return  array       $arrReturn
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
     * Creates an array containg all categories.
     *
     * Contents:
     * $arrCategories[$categoryId][$langId]['name']         =>  Translation for the category with ID = $categoryId for the desired language ($langId).
     * $arrCategories[$categoryId][$langId]['is_active']    =>  Status of the category with ID = $categoryId for the desired language ($langId).
     *
     * @global  ADONewConnection
     * @param   integer     $intStartingIndex: can be used for paging. The value defines, with which row the result should start.
     * @param   integer     $intLimitIndex: can be used for paging. The value defines, how many categories will be returned (starting from $intStartingIndex). If the value is zero, all entries will be returned.
     * @return  array       $arrReturn
     */
    function createCategoryArray($intStartingIndex=0, $intLimitIndex=0) {
        global $objDatabase;

        $arrReturn = array();

        if ($intLimitIndex == 0) {
            $intLimitIndex = $this->countCategories();
        }

        $objResult = $objDatabase->Execute('SELECT  DISTINCT category_id
                                            FROM    '.DBPREFIX.'module_blog_categories
                                            LIMIT   '.$intStartingIndex.','.$intLimitIndex.'
                                        ');
        //Initialize Array
        if ($objResult->RecordCount() > 0) {
            while (!$objResult->EOF) {
                foreach (array_keys($this->_arrLanguages) as $intLangId) {
                    $arrReturn[intval($objResult->fields['category_id'])][$intLangId] =
                        array(
                            'name'      => '',
                            'is_active' => '',
                        );
                }
                $objResult->MoveNext();
            }
        }

        //Fill array if possible
        foreach ($arrReturn as $intCategoryId => $arrLanguages) {
            foreach (array_keys($arrLanguages) as $intLanguageId) {
                $objResult = $objDatabase->Execute('SELECT      is_active,
                                                                name
                                                    FROM        '.DBPREFIX.'module_blog_categories
                                                    WHERE       category_id='.$intCategoryId.' AND
                                                                lang_id='.$intLanguageId.'
                                                    LIMIT       1
                                                ');

                if ($objResult->RecordCount() > 0) {
                    $arrReturn[$intCategoryId][$intLanguageId]['name'] = htmlentities($objResult->fields['name'], ENT_QUOTES, CONTREXX_CHARSET);
                    $arrReturn[$intCategoryId][$intLanguageId]['is_active'] = intval($objResult->fields['is_active']);
                }
            }

        }

        return $arrReturn;
    }


    /**
     * Creates an array containg the latest "$numberOfEntries" entries.
     *
     * Contents:
     * $arrEntries[$entryId]['user_id']                             =>  ID of the User which published this message.
     * $arrEntries[$entryId]['user_name']                           =>  Name of the User which published this message.
     * $arrEntries[$entryId]['time_created']                        =>  When has this entry been published?
     * $arrEntries[$entryId]['time_created_ts']                     =>  When has this entry been published (TIMESTAMP)?
     * $arrEntries[$entryId]['time_edited']                         =>  When has this entry last been edited?
     * $arrEntries[$entryId]['time_edited_ts']                      =>  When has this entry last been edited (TIMESTAMP)?
     * $arrEntries[$entryId]['hits']                                =>  How many visitors had this entry?
     * $arrEntries[$entryId]['comments']                            =>  How many comments has this entry?
     * $arrEntries[$entryId]['comments_active']                     =>  How many activated comments has this entry?
     * $arrEntries[$entryId]['votes']                               =>  How many votes comments has this entry?
     * $arrEntries[$entryId]['votes_avg']                           =>  What is the avarage vote for this entry?
     * $arrEntries[$entryId]['subject']                             =>  The subject of this entry in the currently used language.
     * $arrEntries[$entryId]['categories'][$langId]                 =>  Assigned categories to this entry in the language with langId.
     * $arrEntries[$entryId]['translation'][$langId]['is_active']   =>  Status of entry in the language with the id = langId.
     * $arrEntries[$entryId]['translation'][$langId]['subject']     =>  Subject of entry in the language with the id = langId.
     * $arrEntries[$entryId]['translation'][$langId]['image']       =>  Image of entry in the language with the id = langId.
     * $arrEntries[$entryId]['translation'][$langId]['content']     =>  Content of entry in the language with the id = langId.
     * $arrEntries[$entryId]['translation'][$langId]['tags']        =>  Keywords of entry in the language with the id = langId.
     *
     * @global  ADONewConnection
     * @global  array
     * @param   integer     $intLanguageId: The value defines, if categories of a specific language should be returned. If the value is zero, all languages will be used.
     * @param   integer     $intStartingIndex: can be used for paging. The value defines, with which row the result should start.
     * @param   integer     $intLimitIndex: can be used for paging. The value defines, how many entries will be returned (starting from $intStartingIndex). If the value is zero, all entries will be returned.
     * @return  array       $arrReturn
     */
    function createEntryArray($intLanguageId=0, $intStartingIndex=0, $intLimitIndex=0) {
        global $objDatabase, $_ARRAYLANG;

        $arrReturn = array();

        if (intval($intLanguageId) > 0) {
            $strLanguageJoin  = '   INNER JOIN  '.DBPREFIX.'module_blog_messages_lang   AS blogMessagesLanguage
                                    ON          blogMessages.message_id = blogMessagesLanguage.message_id
                                ';
            $strLanguageWhere = '   WHERE   blogMessagesLanguage.lang_id='.$intLanguageId.' AND
                                            blogMessagesLanguage.is_active="1"
                                ';

        } else {
            $strLanguageJoin = '';
            $strLanguageWhere = '';
        }

        if ($intLimitIndex == 0) {
            $intLimitIndex = $this->countEntries();
        }

        $strQueryWhere = '';

        $objResultMain = $objDatabase->Execute('SELECT      blogMessages.message_id,
                                                        blogMessages.user_id,
                                                        blogMessages.time_created,
                                                        blogMessages.time_edited,
                                                        blogMessages.hits,
                                                        blogMessages.user_id
                                            FROM        '.DBPREFIX.'module_blog_messages        AS blogMessages
                                            '.$strLanguageJoin.'
                                            '.$strLanguageWhere.'
                                            '.$strQueryWhere.'
                                            ORDER BY    time_created DESC
                                            LIMIT       '.$intStartingIndex.','.$intLimitIndex.'
                                        ');

        if ($objResultMain->RecordCount() > 0) {
            $objFWUser = \FWUser::getFWUserObject();

            while (!$objResultMain->EOF) {
                $intMessageId = $objResultMain->fields['message_id'];

                $arrReturn[$intMessageId] = array(  'user_id'           =>  $objResultMain->fields['user_id'],
                                                    'user_name'         =>  $objResultMain->fields['user_id'] && ($objUser = $objFWUser->objUser->getUser($objResultMain->fields['user_id'])) !== false ? htmlentities($objUser->getUsername(), ENT_QUOTES, CONTREXX_CHARSET) : $_ARRAYLANG['TXT_BLOG_ANONYMOUS'],
                                                    'time_created'      =>  date(ASCMS_DATE_FORMAT,$objResultMain->fields['time_created']),
                                                    'time_created_ts'   =>  $objResultMain->fields['time_created'],
                                                    'time_edited'       =>  date(ASCMS_DATE_FORMAT,$objResultMain->fields['time_edited']),
                                                    'time_edited_ts'    =>  $objResultMain->fields['time_edited'],
                                                    'hits'              =>  $objResultMain->fields['hits'],
                                                    'comments'          =>  $this->countComments($intMessageId),
                                                    'comments_active'   =>  $this->countComments($intMessageId,true),
                                                    'votes'             =>  $this->countVotings($intMessageId),
                                                    'votes_avg'         =>  0.0,
                                                    'subject'           =>  '',
                                                    'categories'        =>  array(),
                                                    'translation'       =>  array()
                                                );

                //Get vote-avarage for this entry
                $objVoteResult = $objDatabase->Execute('SELECT  avg(vote)       AS avarageVote
                                                        FROM    '.DBPREFIX.'module_blog_votes
                                                        WHERE   message_id='.$intMessageId.'
                                                    ');
                $arrReturn[$intMessageId]['votes_avg']  = number_format($objVoteResult->fields['avarageVote'], 2, '.', '');

                //Fill the translation-part of the return-array with default values
                foreach (array_keys($this->_arrLanguages) as $intLanguageId) {
                    $arrReturn[$intMessageId]['categories'][$intLanguageId] = array();
                    $arrReturn[$intMessageId]['translation'][$intLanguageId]['is_active']   = 0;
                    $arrReturn[$intMessageId]['translation'][$intLanguageId]['subject']     = '';
                    $arrReturn[$intMessageId]['translation'][$intLanguageId]['content']     = '';
                    $arrReturn[$intMessageId]['translation'][$intLanguageId]['tags']        = '';
                    $arrReturn[$intMessageId]['translation'][$intLanguageId]['image']       = '';
                }

                //Get assigned categories for this entry
                $objCategoryResult = $objDatabase->Execute('SELECT  category_id,
                                                                    lang_id
                                                            FROM    '.DBPREFIX.'module_blog_message_to_category
                                                            WHERE   message_id='.$intMessageId.'
                                                        ');
                while (!$objCategoryResult->EOF) {
                    $arrReturn[$intMessageId]['categories'][$objCategoryResult->fields['lang_id']][$objCategoryResult->fields['category_id']] = true;

                    $objCategoryResult->MoveNext();
                }

                //Get existing translations for the current entry
                $objLangResult = $objDatabase->Execute('SELECT  lang_id,
                                                                    is_active,
                                                                    subject,
                                                                    content,
                                                                    tags,
                                                                    image
                                                            FROM    '.DBPREFIX.'module_blog_messages_lang
                                                            WHERE   message_id='.$intMessageId.'
                                                        ');

                while (!$objLangResult->EOF) {
                    $intLanguageId = $objLangResult->fields['lang_id'];

                    if ( ($intLanguageId == $this->_intLanguageId && !empty($objLangResult->fields['subject'])) ||
                         empty($arrReturn[$intMessageId]['subject']) )
                    {
                        $arrReturn[$intMessageId]['subject'] = htmlentities(stripslashes($objLangResult->fields['subject']), ENT_QUOTES, CONTREXX_CHARSET);
                    }

                    $arrReturn[$intMessageId]['translation'][$intLanguageId]['is_active']   = $objLangResult->fields['is_active'];
                    $arrReturn[$intMessageId]['translation'][$intLanguageId]['subject']     = htmlentities(stripslashes($objLangResult->fields['subject']), ENT_QUOTES, CONTREXX_CHARSET);
                    $arrReturn[$intMessageId]['translation'][$intLanguageId]['content']     = contrexx_raw2xhtml($objLangResult->fields['content']);
                    $arrReturn[$intMessageId]['translation'][$intLanguageId]['tags']        = htmlentities(stripslashes($objLangResult->fields['tags']), ENT_QUOTES, CONTREXX_CHARSET);
                    $arrReturn[$intMessageId]['translation'][$intLanguageId]['image']       = htmlentities(stripslashes($objLangResult->fields['image']), ENT_QUOTES, CONTREXX_CHARSET);

                    $objLangResult->MoveNext();
                }


                $objResultMain->MoveNext();
            }
        }

        return $arrReturn;
    }



    /**
     * Creates an array containing all sozializing networks.
     *
     * Contents:
     * $arrEntries[$intNetworkId]['name']               =>  Name of the service provider.
     * $arrEntries[$intNetworkId]['www']                =>  Link to the service provider.
     * $arrEntries[$intNetworkId]['submit']             =>  Submit-Link for new submissions.
     * $arrEntries[$intNetworkId]['icon']               =>  Icon of the service provider.
     * $arrEntries[$intNetworkId]['icon_img']           =>  Icon of the service provider as am <img>-tag.
     * $arrEntries[$intNetworkId]['status'][$langId]    =>  Activation status of a specific language.
     *
     * @global  ADONewConnection
     * @param   integer     $intStartingIndex: can be used for paging. The value defines, with which row the result should start.
     * @param   integer     $intLimitIndex: can be used for paging. The value defines, how many categories will be returned (starting from $intStartingIndex). If the value is zero, all entries will be returned.
     * @return  array       $arrReturn
     */
    function createNetworkArray($intStartingIndex=0, $intLimitIndex=0) {
        global $objDatabase;

        $arrReturn = array();

        if ($intLimitIndex == 0) {
            $intLimitIndex = $this->countNetworks();
        }

        $objResult = $objDatabase->Execute('SELECT      network_id,
                                                        name,
                                                        url,
                                                        url_link,
                                                        icon
                                            FROM        '.DBPREFIX.'module_blog_networks
                                            ORDER BY    name ASC
                                            LIMIT       '.$intStartingIndex.','.$intLimitIndex.'
                                        ');

        if ($objResult->RecordCount() > 0) {
            while (!$objResult->EOF) {
                $intNetworkId   = intval($objResult->fields['network_id']);
                $strName        = htmlentities(stripslashes($objResult->fields['name']), ENT_QUOTES, CONTREXX_CHARSET);
                $strWWW         = htmlentities(stripslashes($objResult->fields['url']), ENT_QUOTES, CONTREXX_CHARSET);

                $arrReturn[$intNetworkId] = array(  'name'      =>  $strName,
                                                    'www'       =>  $strWWW,
                                                    'submit'    =>  htmlentities(stripslashes($objResult->fields['url_link']), ENT_QUOTES, CONTREXX_CHARSET),
                                                    'icon'      =>  htmlentities(stripslashes($objResult->fields['icon']), ENT_QUOTES, CONTREXX_CHARSET),
                                                    'icon_img'  =>  ($objResult->fields['icon'] != '') ? '<img src="'.$objResult->fields['icon'].'" title="'.$strName.' ('.$strWWW.')" alt="'.$strName.' ('.$strWWW.')" />' : '',
                                                    'status'    =>  array()
                                                );

                $objResult->MoveNext();
            }

            foreach (array_keys($arrReturn) as $intNetworkId) {
                //Initialize the array first
                foreach (array_keys($this->_arrLanguages) as $intLanguageId) {
                    $arrReturn[$intNetworkId]['status'][$intLanguageId] = 0;
                }
                //Now check for active languages
                $objStatusResult = $objDatabase->Execute('  SELECT  lang_id
                                                            FROM    '.DBPREFIX.'module_blog_networks_lang
                                                            WHERE   network_id='.$intNetworkId.'
                                                        ');
                if ($objStatusResult->RecordCount() > 0) {
                    while(!$objStatusResult->EOF) {
                        $arrReturn[$intNetworkId]['status'][$objStatusResult->fields['lang_id']] = 1;
                        $objStatusResult->MoveNext();
                    }
                }
            }
        }

        return $arrReturn;
    }


    /**
     * Returns the allowed maximum element per page. Can be used for paging.
     *
     * @global  array
     * @return  integer     allowed maximum of elements per page.
     */
    function getPagingLimit() {
        global $_CONFIG;

        return intval($_CONFIG['corePagingLimit']);
    }


    /**
     * Counts all existing entries in the database.
     *
     * @global  ADONewConnection
     * @return  integer     number of entries in the database
     */
    function countEntries() {
        global $objDatabase;

        $objEntryResult = $objDatabase->Execute('   SELECT  COUNT(message_id) AS numberOfEntries
                                                    FROM    '.DBPREFIX.'module_blog_messages
                                            ');

        return intval($objEntryResult->fields['numberOfEntries']);
    }



    /**
     * Counts the number of active messages which are assigned to a specific category.
     *
     * @param   integer     $intCategoryId: The assigned messages of this category will be counted.
     */
    function countEntriesOfCategory($intCategoryId) {
        $intCategoryId = intval($intCategoryId);

        if ($intCategoryId > 0) {
            $intNumberOfAssignedMessages = 0;

            $arrEntries = $this->createEntryArray($this->_intLanguageId);
            foreach ($arrEntries as $index => $arrEntryValues) {
                    if (!array_key_exists($intCategoryId, $arrEntryValues['categories'][$this->_intLanguageId])) {
                        unset($arrEntries[$index]);
                    }
            }
            return count($arrEntries);
        }
        return 0;
    }



    /**
     * Counts all existing categories in the database.
     *
     * @global  ADONewConnection
     * @return  integer     number of categories in the database
     */
    function countCategories() {
        global $objDatabase;

        $objCategoryResult = $objDatabase->Execute('SELECT  COUNT(DISTINCT category_id) AS numberOfCategories
                                                    FROM    '.DBPREFIX.'module_blog_categories
                                            ');

        return intval($objCategoryResult->fields['numberOfCategories']);
    }


    /**
     * Counts all votings for a specific entry.
     *
     * @global  ADONewConnection
     * @param   integer     $intMessageId: the votings of the message with this id will be counted.
     * @return  integer     number of votings for the desired entry.
     */
    function countVotings($intMessageId) {
        global $objDatabase;

        $intMessageId = intval($intMessageId);

        $objVotingResult = $objDatabase->Execute('  SELECT  COUNT(vote_id) AS numberOfVotes
                                                    FROM    '.DBPREFIX.'module_blog_votes
                                                    WHERE   message_id='.$intMessageId.'
                                            ');

        return intval($objVotingResult->fields['numberOfVotes']);
    }


    /**
     * Counts all existing networks in the database.
     *
     * @global  ADONewConnection
     * @return  integer     number of networks in the database
     */
    function countNetworks() {
        global $objDatabase;

        $objNetworkResult = $objDatabase->Execute(' SELECT  COUNT(network_id) AS numberOfNetworks
                                                    FROM    '.DBPREFIX.'module_blog_networks
                                            ');

        return intval($objNetworkResult->fields['numberOfNetworks']);
    }



    /**
     * Creates an rating bar (****) for a specific message.
     *
     * @global  ADONewConnection
     * @global  array
     * @param   integer     $intMessageId: The rating bar will be created for the message with this id.
     * @return  string      HTML-source for the rating bar.
     */
    function getRatingBar($intMessageId) {
        global $objDatabase, $_ARRAYLANG;

        $strReturn = '';
        $intMessageId = intval($intMessageId);

        //Check for valid number
        if ($intMessageId == 0) {
            return '';
        }

        $objVoteResult = $objDatabase->Execute('SELECT  avg(vote)       AS avarageVote
                                                FROM    '.DBPREFIX.'module_blog_votes
                                                WHERE   message_id='.$intMessageId.'
                                    ');

        $intNumberOfStars = round($objVoteResult->fields['avarageVote'] / 2) ;

        for ($i = 1; $i <= $intNumberOfStars; ++$i) {
            $strReturn .= '<img title="'.$_ARRAYLANG['TXT_BLOG_LIB_RATING'].'" alt="'.$_ARRAYLANG['TXT_BLOG_LIB_RATING'].'" src="'.ASCMS_MODULE_WEB_PATH.'/Blog/View/Media/star_on.gif" />';
        }

        for ($i = $intNumberOfStars + 1; $i <= 5; ++$i) {
            $strReturn .= '<img title="'.$_ARRAYLANG['TXT_BLOG_LIB_RATING'].'" alt="'.$_ARRAYLANG['TXT_BLOG_LIB_RATING'].'" src="'.ASCMS_MODULE_WEB_PATH.'/Blog/View/Media/star_off.gif" />';
        }

        return $strReturn;
    }


    /**
     * Counts all comments for a specific entry.
     *
     * @global  ADONewConnection
     * @param   integer     $intMessageId: the comments of the message with this id will be counted.
     * @param   boolean     $boolOnlyActive: if this parameter is true, only the "active" comments are counted
     * @return  integer     number of comments for the desired entry.
     */
    function countComments($intMessageId, $boolOnlyActive=false) {
        global $objDatabase;

        $intMessageId = intval($intMessageId);

        $strActiveWhere = '';
        if ($boolOnlyActive) {
            $strActiveWhere = ' AND is_active="1"';
        }

        $objCommentResult = $objDatabase->Execute(' SELECT  COUNT(comment_id) AS numberOfComments
                                                    FROM    '.DBPREFIX.'module_blog_comments
                                                    WHERE   message_id='.$intMessageId.'
                                                    '.$strActiveWhere.'
                                            ');

        return intval($objCommentResult->fields['numberOfComments']);
    }



    /**
     * Creates a "posted by $strUsername on $strDate" string.
     *
     * @global  array
     * @param   string      $strUsername
     * @param   string        $strDate
     * @return  string
     */
    function getPostedByString($strUsername, $strDate) {
        global $_ARRAYLANG;

        $strPostedString = str_replace('[USER]',$strUsername, $_ARRAYLANG['TXT_BLOG_LIB_POSTED_BY']);
        $strPostedString = str_replace('[DATE]',$strDate, $strPostedString);

        return $strPostedString;
    }


    /**
     * Returns an img-Tag for the "posted by" string.
     *
     * @param   string        $strDate
     * @return  string
     */
    function getPostedByIcon($strDate) {
        return '<img src="'.ASCMS_BLOG_IMAGES_WEB_PATH.'/calendar.gif" alt="'.$strDate.'" />';
    }


    /**
     * Returns a string containing the names of all parametered category-id's. Example: "Category 1, Category 2, Category 3".
     *
     * @param   array       $arrCategories: Array containing all id's which should be resolved. Example for the Resolution of Categories 1 .. 3: array(1 => 1, 2 => 1, 3 => 1)
     * @param   boolean     $boolLinked: If it is needed, that the single categories are linked with the search-site, this parameter must be set true.
     * @return  string      String as described in introduction.
     */
    function getCategoryString($arrCategories, $boolLinked=false) {

        $strCategoryString = '';

        if (count($arrCategories) > 0) {
            $arrCategoryNames = $this->createCategoryArray();

            foreach (array_keys($arrCategories) as $intCategoryId) {
                $strCategoryString .= ($boolLinked) ? '<a href="index.php?section=Blog&amp;cmd=search&amp;category='.$intCategoryId.'" title="'.$arrCategoryNames[$intCategoryId][$this->_intLanguageId]['name'].'">' : '';
                $strCategoryString .= $arrCategoryNames[$intCategoryId][$this->_intLanguageId]['name'];
                $strCategoryString .= ($boolLinked) ? '</a>,&nbsp;' : ',&nbsp;';
            }

            $strCategoryString = substr($strCategoryString, 0, -7);
        }

        return $strCategoryString;
    }



    /**
     * Checks, if the category selected by the user corresponds with the assigned categories of an entry.
     *
     * @param   integer     $intSelectedCategory: this category was selected by the user. can also be zero, which means "all categories" selected.
     * @param   array       $arrAssignedCategories: an array containing all categories assigned to an entry.
     * @return  boolean     true, if the entry was assigned to the searched category.
     */
    function categoryMatches($intSelectedCategory, $arrAssignedCategories) {
        $intSelectedCategory = intval($intSelectedCategory);

        if ($intSelectedCategory == 0) {
            return true;
        } else {
            return array_key_exists($intSelectedCategory,$arrAssignedCategories);
        }
    }


    /**
     * Creates an array containing all used tags (keywords) with an calculated number of points. The points depend of the usage-frequency,
     * the number of hits for the assigned topics, voting of the assigned topics and the number of commend of the assigned topics. The
     * array is ordered alphabetically by the keywords.
     *
     * @return  array       Sorted array in the format $arrExample[Keyword] = NumberOfPoints.
     */
    function createKeywordArray()
    {
        $arrKeywords    = array();
        $arrEntries     = $this->createEntryArray($this->_intLanguageId);

        if (count($arrEntries) > 0) {
            //Count total-values first
            $intTotalHits = 1;
            $intTotalComments = 1;

            foreach ($arrEntries as $arrEntryValues) {
                if ($arrEntryValues['translation'][$this->_intLanguageId]['is_active']) {
                    $intTotalHits += $arrEntryValues['hits'];
                    $intTotalComments += $arrEntryValues['comments_active'];
                }
            }

            foreach ($arrEntries as $arrEntryValues) {
                if ($arrEntryValues['translation'][$this->_intLanguageId]['is_active']) {
                    //Calculate the keyword-value first
                    $intKeywordValue = 1;                                                                                       #Base-Value
                    $intKeywordValue = $intKeywordValue + ceil(100 * $arrEntryValues['hits'] / $intTotalHits);                  #Include Hits (More visited = bigger font)
                    $intKeywordValue = $intKeywordValue + ceil((10 + $arrEntryValues['votes']) * $arrEntryValues['votes_avg']); #Include Votes (Better rated = bigger font)
                    $intKeywordValue = $intKeywordValue + ceil(100 * $arrEntryValues['comments_active'] / $intTotalComments);   #Include Comments (More comments = bigger font)

                    $dblDateFactor = 0;
                    if ($arrEntryValues['time_created_ts'] > time() - 7 * 24 * 60 * 60) {
                        $dblDateFactor = 1.0;
                    } elseif ($arrEntryValues['time_created_ts'] > time() - 14 * 24 * 60 * 60) {
                        $dblDateFactor = 0.8;
                    } elseif ($arrEntryValues['time_created_ts'] > time() - 30 * 24 * 60 * 60) {
                        $dblDateFactor = 0.6;
                    } elseif ($arrEntryValues['time_created_ts'] > time() - 90 * 24 * 60 * 60) {
                        $dblDateFactor = 0.4;
                    } elseif ($arrEntryValues['time_created_ts'] > time() - 180 * 24 * 60 * 60) {
                        $dblDateFactor = 0.2;
                    } else {
                        $dblDateFactor = 0.1;
                    }

                    $intKeywordValue = ceil($intKeywordValue * $dblDateFactor); #Include Date (Newer = bigger font)

                    //Split tags
                    $arrEntryTags = explode(',',$arrEntryValues['translation'][$this->_intLanguageId]['tags']);
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
        }

        ksort($arrKeywords);

        return $arrKeywords;
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
            $strReturn = '<ul class="blogTagCloud">';
            $intMinimum = min($arrKeywords);
            $intMaximum = max($arrKeywords);
            $intRange = $intMaximum - $intMinimum;

            foreach ($arrKeywords as $strTag => $intKeywordValue) {
                $strCssClass = '';

                if ($intKeywordValue >= $intMinimum + $intRange * 1.0) {
                    $strCssClass = 'blogTagCloudLargest';
                } else if($intKeywordValue >= $intMinimum + $intRange * 0.75) {
                    $strCssClass = 'blogTagCloudLarge';
                } else if($intKeywordValue >= $intMinimum + $intRange * 0.5) {
                    $strCssClass = 'blogTagCloudMedium';
                } else if($intKeywordValue >= $intMinimum + $intRange * 0.25) {
                    $strCssClass = 'blogTagCloudSmall';
                } else {
                    $strCssClass = 'blogTagCloudSmallest';
                }

                $strReturn .= '<li class="'.$strCssClass.'"><a href="index.php?section=Blog&amp;cmd=search&amp;term='.$strTag.'" title="'.$strTag.'">'.$strTag.'</a></li>';
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
    function getTagHitlist($intNumberOfTags=0) {
        $strReturn      = '';
        $arrKeywords = $this->createKeywordArray();
        arsort($arrKeywords); //Order Descending by Value

        $intNumberOfTags = ($intNumberOfTags == 0) ? intval($this->_arrSettings['blog_tags_hitlist']) : intval($intNumberOfTags);
        $intNumberOfTags = (count($arrKeywords) < $intNumberOfTags) ? count($arrKeywords) : $intNumberOfTags;

        if ($intNumberOfTags > 0) {
            $strReturn = '<ol class="blogTagHitlist">';

            $intTagCounter = 0;
            foreach (array_keys($arrKeywords) as $strTag) {
                $strReturn .= '<li class="blogTagHitlistItem"><a href="index.php?section=Blog&amp;cmd=search&amp;term='.$strTag.'" title="'.$strTag.'">'.$strTag.'</a></li>';
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
     * Returns the HTML-source for a category-select. Is used on the search-page or the home-site.
     *
     * @param   string      $strFieldName: This value will be entered in the "name"-field of the <select>-tag
     * @param   integer     $intSelectedCategory: The category with this id will be marked as "selected".
     * @param   boolean     $boolStandalone: If this parameter is set to true, a javascript is added to the menu => After changing the selection the search is stared.
     * @return  string      HTML-source
     */
    function getCategoryDropDown($strFieldName, $intSelectedCategory=0, $boolStandalone=false) {
        global $_ARRAYLANG;

        $strReturn              = '';
        $strFieldName           = htmlentities($strFieldName, ENT_QUOTES, CONTREXX_CHARSET);
        $intSelectedCategory    = intval($intSelectedCategory);
        $arrCategories          = $this->createCategoryArray();

        $strReturn .= ($boolStandalone) ? '<form method="post" name="frmDoSearch" action="index.php?section=Blog&amp;cmd=search">' : '';
        $strReturn .= ($boolStandalone) ? '<select name="'.$strFieldName.'" onchange="this.form.submit()">' : '<select name="'.$strFieldName.'">';
        $strReturn .= '<option value="0" '.(($intSelectedCategory == 0) ? 'selected="selected"' : '').'>'.$_ARRAYLANG['TXT_BLOG_LIB_ALL_CATEGORIES'].'</option>';

        if (count($arrCategories) > 0) {
            //Collect active categories for the current language
            $arrCurrentLanguageCategories = array();
            foreach($arrCategories as $intCategoryId => $arrLanguageData) {
                if ($arrLanguageData[$this->_intLanguageId]['is_active']) {
                    $arrCurrentLanguageCategories[$intCategoryId] = $arrLanguageData[$this->_intLanguageId]['name'];
                }
            }

            //Sort alphabetic
            asort($arrCurrentLanguageCategories);

            if (count($arrCurrentLanguageCategories)) {
                $strReturn .= '<option value="0">-----</option>';

                foreach($arrCurrentLanguageCategories as $intCategoryId => $strTranslation) {
                    $strReturn .= '<option value="'.$intCategoryId.'" '.(($intSelectedCategory == $intCategoryId) ? 'selected="selected"' : '').'>'.$strTranslation.'&nbsp;('.$this->countEntriesOfCategory($intCategoryId).')</option>';
                }
            }
        }

        $strReturn .= '</select>';
        $strReturn .= ($boolStandalone) ? '</form>' : '';

        return $strReturn;
    }


    /**
     * This function replaces all tags with links to the search-module.
     *
     * @param   string      $strUnlinkedTags: The input String looks something like this: "Keyword 1, Keyword 2, Keyword 3".
     * @return  string      The Keywords are replaced with linked tags, for example: "<a href="index.php?section=Blog&amp;cmd=search&term=Keyword 1">Keyword1</a>, ..."
     */
    function getLinkedTags($strUnlinkedTags) {
        $arrPatterns    = array('/(,?\s?)(([a-z0-9+&;$!.-]+\s?)*)(,?\s?)/i');
        $arrReplace     = array('\1<a href="index.php?section=Blog&amp;cmd=search&amp;term=\2" title="\2">\2</a>\4',);

        return preg_replace($arrPatterns,$arrReplace,$strUnlinkedTags);
    }


    /**
     * Returns an img-Tag for the "tags"-icon.
     *
     * @param   string        $strDate
     * @return  string
     */
    function getTagsIcon() {
        return '<img src="'.ASCMS_BLOG_IMAGES_WEB_PATH.'/tags.gif" alt="Tags" />';
    }


    /**
     * Returns the source-code for a calendar in the "month"-view.
     *
     * @param   integer     $intYear: This year will be selected. If empty it will be used the current year.
     * @param   integer     $intMonth: This month will be selected. If empty it will be used the current month.
     * @param   integer     $intDay: This day will be selected. If empty it will be used the current day.
     * @return  strign      html-source for the calendar.
     */
    function getCalendar($intYear=0, $intMonth=0, $intDay=0) {
        global $_ARRAYLANG;

        $intYear    = intval($intYear);
        $intMonth   = intval($intMonth);
        $intDay     = intval($intDay);

        $objCalendar = new \activeCalendar($intYear, $intMonth, $intDay);
        $objCalendar->setMonthNames(explode(',', $_ARRAYLANG['TXT_BLOG_LIB_CALENDAR_MONTHS']));
        $objCalendar->setDayNames(explode(',', $_ARRAYLANG['TXT_BLOG_LIB_CALENDAR_WEEKDAYS']));
        $objCalendar->setFirstWeekDay(1);
        $objCalendar->enableMonthNav('index.php?section=Blog&amp;cmd=search&amp;mode=date');

        $arrEntriesInPeriod = $this->getEntriesInPeriod(mktime(0,0,0,$intMonth,1,$intYear), mktime(0,0,0,$intMonth,31,$intYear));
        if (count($arrEntriesInPeriod) > 0) {
            foreach ($arrEntriesInPeriod as $intTimeStamp) {
                $objCalendar->setEvent($intYear, $intMonth , date('d',$intTimeStamp), null, 'index.php?section=Blog&amp;cmd=search&amp;mode=date&amp;yearID='.$intYear.'&amp;monthID='.$intMonth.'&amp;dayID='.date('d',$intTimeStamp));
            }
        }

        return $objCalendar->showMonth();
    }



    /**
     * Returns an array containing the timestamps of all entries within a given range.
     * Example: $arrReturn[0] = 1123144112
     *          $arrReturn[1] = 1234316412
     *
     * @param   integer     $intStartingDate: The Unix-timestamp in this parameter defines the beginning of the range.
     * @param   integer     $intEndingDate: The Unix-timestamp in this parameter defines the end of the range.
     * @return  array       Array as described before.
     */
    function getEntriesInPeriod($intStartingTimestamp,$intEndingTimestamp) {
        global $objDatabase;

        $arrEntries = array();

        $intStartingTimestamp   = intval($intStartingTimestamp);
        $intEndingTimestamp     = intval($intEndingTimestamp);

        $objEntryResult = $objDatabase->Execute('   SELECT  time_created
                                                    FROM    '.DBPREFIX.'module_blog_messages
                                                    WHERE   time_created > '.$intStartingTimestamp.' AND
                                                            time_created < '.$intEndingTimestamp.'
                                                ');

        if ($objEntryResult->RecordCount() > 0) {
            while (!$objEntryResult->EOF) {
                $arrEntries[count($arrEntries)] = $objEntryResult->fields['time_created'];
                $objEntryResult->MoveNext();
            }
        }

        return $arrEntries;
    }



    /**
     * The text in the parameter $strFullMessage will be shortened to the introduction-length definied in the settings-array. If
     * the text is smaller than the definied length, nothing is done.
     *
     * @param   string      $strFullMessage: This message will be reduced.
     * @return  string      Reduced message, can be used as introduction text.
     */
    function getIntroductionText($strFullMessage) {
        $strIntroduction    = strip_tags($strFullMessage);
        $intNumberOfChars   = intval($this->_arrSettings['blog_general_introduction']);

        if ($intNumberOfChars > 0 and strlen($strIntroduction) > $intNumberOfChars) {
            $strIntroduction = substr($strIntroduction, 0, $intNumberOfChars);
            $strIntroduction = preg_replace('#&\w{0,4}$#m', '', $strIntroduction);
            $strIntroduction = preg_replace('#\s.{1,5}$#m', ' ', $strIntroduction);
            $strIntroduction .= "&hellip;";
        }

        return $strIntroduction;
    }



    /**
     * Writes RSS feed containing the latest N messages to the feed-directory. This is done for every language seperately.
     *
     * @global  array
     * @global  array
     */
    function writeMessageRSS()
    {
        global $_CONFIG, $_ARRAYLANG;

        if (intval($this->_arrSettings['blog_rss_activated'])) {


            foreach ($this->_arrLanguages as $intLanguageId => $arrLanguageValues) {
                $arrEntries = $this->createEntryArray($intLanguageId, 0, intval($this->_arrSettings['blog_rss_messages']) );

                if (count($arrEntries) > 0) {
                    $objRSSWriter = new \RSSWriter();

                    $objRSSWriter->characterEncoding = CONTREXX_CHARSET;
                    $objRSSWriter->channelTitle = $_CONFIG['coreGlobalPageTitle'].' - '.$_ARRAYLANG['TXT_BLOG_LIB_RSS_MESSAGES_TITLE'];
                    $objRSSWriter->channelLink = \Cx\Core\Routing\Url::fromModuleAndCmd('Blog', '', $intLanguageId)->toString();
                    $objRSSWriter->channelDescription = $_CONFIG['coreGlobalPageTitle'].' - '.$_ARRAYLANG['TXT_BLOG_LIB_RSS_MESSAGES_TITLE'];
                    //Function doesn't exist
                    //$objRSSWriter->channelLanguage = \FWLanguage::getLanguageParameter($intLanguageId, 'lang');
                    $objRSSWriter->channelCopyright = 'Copyright '.date('Y').', http://'.$_CONFIG['domainUrl'];
                    $objRSSWriter->channelWebMaster = $_CONFIG['coreAdminEmail'];

                    $entryUrl = \Cx\Core\Routing\Url::fromModuleAndCmd('Blog', 'details', $intLanguageId);
                    foreach ($arrEntries as $intEntryId => $arrEntryValues) {
                        $entryUrl->setParam('id', $intEntryId);
                        $objRSSWriter->addItem(
                            html_entity_decode($arrEntryValues['subject'], ENT_QUOTES, CONTREXX_CHARSET),
                            contrexx_raw2xhtml($entryUrl->toString()),
                            htmlspecialchars($arrEntryValues['translation'][$intLanguageId]['content'], ENT_QUOTES, CONTREXX_CHARSET),
                            htmlspecialchars($arrEntryValues['user_name'], ENT_QUOTES, CONTREXX_CHARSET),
                            '',
                            '',
                            '',
                            '',
                            $arrEntryValues['time_created_ts'],
                            ''
                        );
                    }

                    $objRSSWriter->xmlDocumentPath = \Env::get('cx')->getWebsiteFeedPath().'/blog_messages_'.$arrLanguageValues['short'].'.xml';
                    $objRSSWriter->write();

                    \Cx\Lib\FileSystem\FileSystem::makeWritable(\Env::get('cx')->getWebsiteFeedPath().'/blog_messages_'.$arrLanguageValues['short'].'.xml');
                }
            }
        }
    }



    /**
     * Writes RSS feed containing the latest N comments to the feed-directory. This is done for every language seperately.
     *
     * @global  array
     * @global  array
     * @global  ADONewConnection
     * @global  FWLanguage
     */
    function writeCommentRSS() {
        global $_CONFIG, $_ARRAYLANG, $objDatabase;

        if (intval($this->_arrSettings['blog_rss_activated'])) {

            $objFWUser = \FWUser::getFWUserObject();

            foreach ($this->_arrLanguages as $intLanguageId => $arrLanguageValues) {
                $objResult = $objDatabase->Execute('SELECT      message_id,
                                                                time_created,
                                                                user_id,
                                                                user_name,
                                                                subject,
                                                                comment
                                                    FROM        '.DBPREFIX.'module_blog_comments
                                                    WHERE       lang_id='.$intLanguageId.' AND
                                                                is_active="1"
                                                    ORDER BY    time_created DESC
                                                    LIMIT       '.intval($this->_arrSettings['blog_rss_comments']).'
                                                ');

                if ($objResult->RecordCount() > 0) {
                    $objRSSWriter = new \RSSWriter();

                    $objRSSWriter->characterEncoding = CONTREXX_CHARSET;
                    $objRSSWriter->channelTitle = $_CONFIG['coreGlobalPageTitle'].' - '.$_ARRAYLANG['TXT_BLOG_LIB_RSS_COMMENTS_TITLE'];
                    $objRSSWriter->channelLink = \Cx\Core\Routing\Url::fromModuleAndCmd(
                        'Blog',
                        '',
                        $intLanguageId
                    )->toString();
                    $objRSSWriter->channelDescription = $_CONFIG['coreGlobalPageTitle'].' - '.$_ARRAYLANG['TXT_BLOG_LIB_RSS_COMMENTS_TITLE'];
                    $objRSSWriter->channelCopyright = 'Copyright '.date('Y').', http://'.$_CONFIG['domainUrl'];
                    //Function doesn't exist
                    //$objRSSWriter->channelLanguage = \FWLanguage::getLanguageParameter($intLanguageId, 'lang');
                    $objRSSWriter->channelWebMaster = $_CONFIG['coreAdminEmail'];

                    $strItemLink = \Cx\Core\Routing\Url::fromModuleAndCmd(
                        'Blog',
                        'details',
                        $intLanguageId,
                        array(
                            'id' => '{ID}#comments',
                        )
                    )->toString();
                    while (!$objResult->EOF) {
                        $strUserName = (intval($objResult->fields['user_id']) > 0 && ($objUser = $objFWUser->objUser->getUser($objResult->fields['user_id'])) !== false) ? htmlentities($objUser->getUsername(), ENT_QUOTES, CONTREXX_CHARSET) : $objResult->fields['user_name'];

                        $objRSSWriter->addItem(
                            htmlspecialchars($objResult->fields['subject'], ENT_QUOTES, CONTREXX_CHARSET),
                            contrexx_raw2xhtml(str_replace('{ID}',$objResult->fields['message_id'],$strItemLink)),
                            htmlspecialchars($objResult->fields['comment'], ENT_QUOTES, CONTREXX_CHARSET),
                            htmlspecialchars($strUserName, ENT_QUOTES, CONTREXX_CHARSET),
                            '',
                            '',
                            '',
                            '',
                            $objResult->fields['time_created'],
                            ''
                        );

                        $objResult->MoveNext();
                    }

                    $objRSSWriter->xmlDocumentPath = \Env::get('cx')->getWebsiteFeedPath().'/blog_comments_'.$arrLanguageValues['short'].'.xml';
                    $objRSSWriter->write();

                    \Cx\Lib\FileSystem\FileSystem::makeWritable(\Env::get('cx')->getWebsiteFeedPath().'/blog_comments_'.$arrLanguageValues['short'].'.xml');
                }
            }
        }
    }


    /**
     * Writes RSS feed containing the latest N messages of each category the feed-directory. This is done for every language seperately.
     *
     * @global  array
     * @global  array
     * @global  FWLanguage
     */
    function writeCategoryRSS() {
        global $_CONFIG, $_ARRAYLANG;

        if (intval($this->_arrSettings['blog_rss_activated'])) {


            $arrCategories = $this->createCategoryArray();

            //Iterate over all languages
            foreach ($this->_arrLanguages as $intLanguageId => $arrLanguageValues) {
                $arrEntries = $this->createEntryArray($intLanguageId);

                //If there exist entries in this language go on, otherwise skip
                if (count($arrEntries) > 0) {

                    //Iterate over all categories
                    foreach ($arrCategories as $intCategoryId => $arrCategoryTranslation) {

                        //If the category is activated in this language, find assigned messages
                        if ($arrCategoryTranslation[$intLanguageId]['is_active']) {

                            $intNumberOfMessages = 0; //Counts found messages for this category

                            $objRSSWriter = new \RSSWriter();
                            $objRSSWriter->characterEncoding = CONTREXX_CHARSET;
                            $objRSSWriter->channelTitle = $_CONFIG['coreGlobalPageTitle'].' - '.$_ARRAYLANG['TXT_BLOG_LIB_RSS_MESSAGES_TITLE'];
                            $objRSSWriter->channelLink = \Cx\Core\Routing\Url::fromModuleAndCmd(
                                'Blog',
                                '',
                                $intLanguageId
                            )->toString();
                            $objRSSWriter->channelDescription = $_CONFIG['coreGlobalPageTitle'].' - '.$_ARRAYLANG['TXT_BLOG_LIB_RSS_MESSAGES_TITLE'].' ('.$arrCategoryTranslation[$intLanguageId]['name'].')';
                            $objRSSWriter->channelCopyright = 'Copyright '.date('Y').', http://'.$_CONFIG['domainUrl'];
                            //Function doesn't exist
                            //$objRSSWriter->channelLanguage = \FWLanguage::getLanguageParameter($intLanguageId, 'lang');
                            $objRSSWriter->channelWebMaster = $_CONFIG['coreAdminEmail'];

                            //Find assigned messages
                            $entryUrl = \Cx\Core\Routing\Url::fromModuleAndCmd('Blog', 'details', $intLanguageId);
                            foreach ($arrEntries as $intEntryId => $arrEntryValues) {
                                if ($this->categoryMatches($intCategoryId, $arrEntryValues['categories'][$intLanguageId])) {
                                    //Message is in category, add to feed
                                    $entryUrl->setParam('id', $intEntryId);
                                    $objRSSWriter->addItem(
                                        html_entity_decode($arrEntryValues['subject'], ENT_QUOTES, CONTREXX_CHARSET),
                                        contrexx_raw2xhtml($entryUrl->toString()),
                                        htmlspecialchars($arrEntryValues['translation'][$intLanguageId]['content'], ENT_QUOTES, CONTREXX_CHARSET),
                                        htmlspecialchars($arrEntryValues['user_name'], ENT_QUOTES, CONTREXX_CHARSET),
                                        '',
                                        '',
                                        '',
                                        '',
                                        $arrEntryValues['time_created_ts'],
                                        ''
                                    );

                                    $intNumberOfMessages++;

                                    //Check for message-limit
                                    if ($intNumberOfMessages >= intval($this->_arrSettings['blog_rss_messages'])) {
                                        break;
                                    }
                                }
                            }

                            $objRSSWriter->xmlDocumentPath = \Env::get('cx')->getWebsiteFeedPath().'/blog_category_'.$intCategoryId.'_'.$arrLanguageValues['short'].'.xml';
                            $objRSSWriter->write();

                            \Cx\Lib\FileSystem\FileSystem::makeWritable(\Env::get('cx')->getWebsiteFeedPath().'/blog_category_'.$intCategoryId.'_'.$arrLanguageValues['short'].'.xml');
                        }

                    }

                }
            }
        }
    }

    /**
     * Get mediabrowser button
     *
     * @param string $buttonValue Value of the button
     * @param string $options     Input button options
     * @param string $callback    Media browser callback function
     *
     * @return null
     */
    public static function getMediaBrowserButton($buttonValue, $options = array(), $callback = '')
    {
        // Mediabrowser
        $mediaBrowser = new \Cx\Core_Modules\MediaBrowser\Model\Entity\MediaBrowser();
        $mediaBrowser->setOptions($options);
        if ($callback) {
            $mediaBrowser->setCallback($callback);
        }

        return $mediaBrowser->getXHtml($buttonValue);
    }
}
