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
 * @version        $Id: index.inc.php,v 1.00 $
 * @package     cloudrexx
 * @subpackage  module_blog
 */

namespace Cx\Modules\Blog\Controller;

/**
 * BlogAdmin
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Kaelin <thomas.kaelin@comvation.com>
 * @version        $Id: index.inc.php,v 1.00 $
 * @package     cloudrexx
 * @subpackage  module_blog
 */
class BlogHomeContent extends \Cx\Modules\Blog\Controller\BlogLibrary  {

    var $_strPageContent;
    var $_objTpl;

    /**
     * Constructor php5
     */
    function __construct($strPageContent)
    {
        global $_LANGID;
        parent::__construct();
        $this->_strPageContent = $strPageContent;
        $this->_objTpl = new \Cx\Core\Html\Sigma('.');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_intLanguageId = intval($_LANGID);
        $this->_arrSettings = $this->createSettingsArray();
    }


    /**
     * Constructor php4
     */
    function BlogHomeContent($strPageContent)
    {
        $this->__construct($strPageContent);
    }


    /**
     * Checks, if the home content is activated.
     *
     * @return    boolean        true, if the block-functionality is activated in system settings.
     */
    function blockFunktionIsActivated()
    {
        if (empty($this->_arrSettings['blog_block_activated'])) {
            return false;
        }
        return (intval($this->_arrSettings['blog_block_activated']) == 1);
    }


    /**
     * Check if a keywords occurs in a given text / content.
     *
     * @param    string        $strKeyword: This keyword will be searched in the content (=Needle).
     * @param    string        $strContent: This string will be looked through (=Haystack).
     * @return    boolean        true, if the key occured.
     */
    function searchKeywordInContent($strKeyword, $strContent)
    {
        return preg_match('/\{'.$strKeyword.'\}/mi', $strContent);
    }


    /**
     * Replaces the string $strNeedle in $strHaystack with $strReplace, if $boolActivated is true.
     *
     * @param    string        $strNeedle: This keyword will be searched in the haystack.
     * @param    string        $strReplace: This keyword will replace the original value in $strNeedle.
     * @param    stirng        $strHaystack: This string will be looked through for $strNeedle.
     * @param    boolean        $boolActivated: Only if this parameter is true, the replacement will be done.
     * @return    string        If $boolActivated, the modified $strHaystack, otherwise the original $strHaystack without any changes.
     */
    function fillVariableIfActivated($strNeedle, $strReplace, $strHaystack, $boolActivated)
    {
        if ($boolActivated) {
            return preg_replace('/\{'.$strNeedle.'\}/mi', $strReplace, $strHaystack);
        }
        return $strHaystack;
    }


    /**
     * Returns html-source for an calendar in the month-view.
     *
     * @return    string        html-source for the month-view.
     */
    function getHomeCalendar()
    {
        $intYear  = (isset($_GET['yearID']))  ? intval($_GET['yearID'])  : date('Y', time());
        $intMonth = (isset($_GET['monthID'])) ? intval($_GET['monthID']) : date('m', time());
        $intDay   = (isset($_GET['dayID']))   ? intval($_GET['dayID'])   : 0;
        return $this->getCalendar($intYear,$intMonth,$intDay);
    }


    /**
     * Returns html-source for an tagcloud.  Just a wrapper-method.
     *
     * @return    string        html-source for the tagcloud.
     */
    function getHomeTagCloud()
    {
        return $this->getTagCloud();
    }


    /**
     * Returns html-source for an tag-hitlist. Just a wrapper-method.
     *
     * @return    string        html-source for the tag-hitlist.
     */
    function getHomeTagHitlist()
    {
        return $this->getTagHitlist();
    }


    /**
     * Returns html-source for an category-dropdown.
     *
     * @return    string        html-source for the category-dropdown.
     */
    function getHomeCategoriesSelect()
    {
        return $this->getCategoryDropDown('frmDoSearch_Keyword_Category', 0, true);
    }


    /**
     * Returns html-source for an category-list.
     *
     * @return    string        html-source for the category-list.
     */
    function getHomeCategoriesList()
    {
        $strReturn = '<ul class="blogCategoriesList">';
        $arrCategories = $this->createCategoryArray();
        foreach($arrCategories as $intCategoryId => $arrCategoryValues) {
            if($arrCategoryValues[$this->_intLanguageId]['is_active']) {
                $strReturn .= '<li class="blogCategoriesListItem"><a href="index.php?section=Blog&amp;cmd=search&amp;category='.$intCategoryId.'">'.$arrCategoryValues[$this->_intLanguageId]['name'].'&nbsp;('.$this->countEntriesOfCategory($intCategoryId).')</a></li>';
            }
        }
        $strReturn .= '</ul>';
        return $strReturn;
    }


    /**
     * Fills the latest entries of the blog-module into the blog-page.
     *
     * @global     array
     * @return    string        parsed content with latest entries.
     */
    function getLatestEntries()
    {
        global $_ARRAYLANG;

        $this->_objTpl->setTemplate($this->_strPageContent, true, true);

        //Show latest XX entries
        $arrEntries = $this->createEntryArray($this->_intLanguageId, 0, intval($this->_arrSettings['blog_block_messages']));
        if (count($arrEntries) > 0 && $this->_objTpl->blockExists('blogBlockEntries')) {
            $intRowClass = 1;

            foreach ($arrEntries as $intEntryId => $arrEntryValues) {
                $this->_objTpl->setVariable(array(
                    'TXT_BLOG_ENTRY_CATEGORIES'        =>    $_ARRAYLANG['TXT_BLOG_HOME_CATEGORIES'],
                    'TXT_BLOG_ENTRY_TAGS'            =>    $_ARRAYLANG['TXT_BLOG_HOME_KEYWORDS'],
                    'TXT_BLOG_ENTRY_VOTING'            =>    $_ARRAYLANG['TXT_BLOG_HOME_VOTING'],
                    'TXT_BLOG_ENTRY_COMMENTS'        =>    $_ARRAYLANG['TXT_BLOG_HOME_COMMENTS'],
                    'TXT_BLOG_ENTRY_LINK'            =>    $_ARRAYLANG['TXT_BLOG_HOME_LINK']
                ));

                $this->_objTpl->setVariable(array(
                    'BLOG_ENTRY_ROWCLASS'        =>    ($intRowClass % 2 == 0) ? 'row1' : 'row2',
                    'BLOG_ENTRY_ID'                =>    $intEntryId,
                    'BLOG_ENTRY_DATE'            =>    $arrEntryValues['time_created'],
                    'BLOG_ENTRY_AUTHOR_ID'        =>    $arrEntryValues['user_id'],
                    'BLOG_ENTRY_AUTHOR_NAME'    =>    $arrEntryValues['user_name'],
                    'BLOG_ENTRY_SUBJECT'        =>    $arrEntryValues['subject'],
                    'BLOG_ENTRY_POSTED_BY'        =>    $this->getPostedByString($arrEntryValues['user_name'], $arrEntryValues['time_created']),
                    'BLOG_ENTRY_POSTED_BY_ICON'  =>    $this->getPostedByIcon($arrEntryValues['time_created']),
                    'BLOG_ENTRY_INTRODUCTION'    =>    $this->getIntroductionText($arrEntryValues['translation'][$this->_intLanguageId]['content']),
                    'BLOG_ENTRY_CONTENT'        =>    $arrEntryValues['translation'][$this->_intLanguageId]['content'],
                    'BLOG_ENTRY_CATEGORIES'        =>    $this->getCategoryString($arrEntryValues['categories'][$this->_intLanguageId], true),
                    'BLOG_ENTRY_TAGS'            =>    $this->getLinkedTags($arrEntryValues['translation'][$this->_intLanguageId]['tags']),
                    'BLOG_ENTRY_TAGS_ICON'       =>    $this->getTagsIcon(),
                    'BLOG_ENTRY_COMMENTS'        =>    $arrEntryValues['comments_active'].'&nbsp;'.$_ARRAYLANG['TXT_BLOG_HOME_COMMENTS'],
                    'BLOG_ENTRY_VOTING'            =>    '&#216;&nbsp;'.$arrEntryValues['votes_avg'],
                    'BLOG_ENTRY_VOTING_STARS'    =>    $this->getRatingBar($intEntryId),
                    'BLOG_ENTRY_LINK'            =>    '<a href="index.php?section=Blog&amp;cmd=details&amp;id='.$intEntryId.'" title="'.$arrEntryValues['subject'].'">'.$_ARRAYLANG['TXT_BLOG_HOME_OPEN'].'</a>',
                    'BLOG_ENTRY_IMAGE'            =>    ($arrEntryValues['translation'][$this->_intLanguageId]['image'] != '') ? '<img src="'.$arrEntryValues['translation'][$this->_intLanguageId]['image'].'" title="'.$arrEntryValues['subject'].'" alt="'.$arrEntryValues['subject'].'" />' : ''
                ));

                $this->_objTpl->parse('blogBlockEntries');
                ++$intRowClass;
            }
        }

        //Show overview of categories
        $arrCategories = $this->createCategoryArray();

        if (count($arrCategories) > 0 && $this->_objTpl->blockExists('blogBlockCategories')) {
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
                foreach($arrCurrentLanguageCategories as $intCategoryId => $strTranslation) {
                    $this->_objTpl->setVariable(array(
                        'BLOG_CATEGORY_ID'        =>    $intCategoryId,
                        'BLOG_CATEGORY_NAME'    =>    $strTranslation,
                        'BLOG_CATEGORY_COUNT'    =>    $this->countEntriesOfCategory($intCategoryId)
                    ));
                    $this->_objTpl->parse('blogBlockCategories');
                }
            }
        }

        //Also try to fill the other variables
        if ($this->searchKeywordInContent('BLOG_CALENDAR', $this->_strPageContent))             {    $this->_objTpl->setVariable('BLOG_CALENDAR', $this->getHomeCalendar());                        }
        if ($this->searchKeywordInContent('BLOG_TAG_CLOUD', $this->_strPageContent))             {    $this->_objTpl->setVariable('BLOG_TAG_CLOUD', $this->getTagCloud());                        }
        if ($this->searchKeywordInContent('BLOG_TAG_HITLIST', $this->_strPageContent))             {    $this->_objTpl->setVariable('BLOG_TAG_HITLIST', $this->getHomeTagHitlist());                }
        if ($this->searchKeywordInContent('BLOG_CATEGORIES_SELECT', $this->_strPageContent))     {    $this->_objTpl->setVariable('BLOG_CATEGORIES_SELECT', $this->getHomeCategoriesSelect());    }
        if ($this->searchKeywordInContent('BLOG_CATEGORIES_LIST', $this->_strPageContent))         {    $this->_objTpl->setVariable('BLOG_CATEGORIES_LIST', $this->getHomeCategoriesList());        }

        return $this->_objTpl->get();
    }
}
