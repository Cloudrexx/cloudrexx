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
 * Data
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Kaelin <thomas.kaelin@comvation.com>
 * @version        $Id: index.inc.php,v 1.00 $
 * @package     cloudrexx
 * @subpackage  module_data
 */
namespace Cx\Modules\Data\Controller;
/**
 * DataAdmin
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Kaelin <thomas.kaelin@comvation.com>
 * @version        $Id: index.inc.php,v 1.00 $
 * @package     cloudrexx
 * @subpackage  module_data
 */
class DataHomeContent extends \Cx\Modules\Data\Controller\DataLibrary  {

    var $_strPageContent;
    var $_objTpl;

    /**
     * Constructor php5
     */
    function __construct($strPageContent) {
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
    function DataHomeContent($strPageContent) {
        $this->__construct($strPageContent);
    }


    /**
     * Checks, if the home content is activated.
     *
     * @return    boolean        true, if the block-functionality is activated in system settings.
     */
    function blockFunktionIsActivated() {
        return (intval($this->_arrSettings['data_block_activated']) == 1);
    }



    /**
     * Check if a keywords occurs in a given text / content.
     *
     * @param    string        $strKeyword: This keyword will be searched in the content (=Needle).
     * @param    string        $strContent: This string will be looked through (=Haystack).
     * @return    boolean        true, if the key occured.
     */
    function searchKeywordInContent($strKeyword, $strContent) {
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
    function fillVariableIfActivated($strNeedle, $strReplace, $strHaystack, $boolActivated) {
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
    function getHomeCalendar() {
        $intYear     = (isset($_GET['yearID']))     ? intval($_GET['yearID'])     : date('Y', time());
        $intMonth     = (isset($_GET['monthID'])) ? intval($_GET['monthID'])     : date('m', time());
        $intDay     = (isset($_GET['dayID']))     ? intval($_GET['dayID'])     : 0;

        return $this->getCalendar($intYear,$intMonth,$intDay);
    }


    /**
     * Returns html-source for an tagcloud.  Just a wrapper-method.
     *
     * @return    string        html-source for the tagcloud.
     */
    function getHomeTagCloud() {
        return $this->getTagCloud();
    }


    /**
     * Returns html-source for an tag-hitlist. Just a wrapper-method.
     *
     * @return    string        html-source for the tag-hitlist.
     */
    function getHomeTagHitlist() {
        return $this->getTagHitlist();
    }


    /**
     * Returns html-source for an category-dropdown.
     *
     * @return    string        html-source for the category-dropdown.
     */
    function getHomeCategoriesSelect() {
        return $this->getCategoryDropDown('frmDoSearch_Keyword_Category', 0, true);
    }


    /**
     * Returns html-source for an category-list.
     *
     * @return    string        html-source for the category-list.
     */
    function getHomeCategoriesList() {
        $strReturn = '<ul class="dataCategoriesList">';

        $arrCategories = $this->createCategoryArray();

        foreach($arrCategories as $intCategoryId => $arrCategoryValues) {
            if($arrCategoryValues[$this->_intLanguageId]['is_active']) {
                $strReturn .= '<li class="dataCategoriesListItem"><a href="index.php?section=Data&amp;cmd=search&amp;category='.$intCategoryId.'">'.$arrCategoryValues[$this->_intLanguageId]['name'].'&nbsp;('.$this->countEntriesOfCategory($intCategoryId).')</a></li>';
            }
        }

        $strReturn .= '</ul>';

        return $strReturn;
    }


    /**
     * Fills the latest entries of the data-module into the data-page.
     *
     * @global     array
     * @return    string        parsed content with latest entries.
     */
    function getLatestEntries() {
        global $_ARRAYLANG;

        $this->_objTpl->setTemplate($this->_strPageContent, true, true);

        //Show latest XX entries
        $arrEntries = $this->createEntryArray($this->_intLanguageId, 0, intval($this->_arrSettings['data_block_messages']));
        if (count($arrEntries) > 0) {
            $intRowClass = 1;

            foreach ($arrEntries as $intEntryId => $arrEntryValues) {
                $this->_objTpl->setVariable(array(
                    'TXT_DATA_ENTRY_CATEGORIES'        =>    $_ARRAYLANG['TXT_DATA_HOME_CATEGORIES'],
                    'TXT_DATA_ENTRY_TAGS'            =>    $_ARRAYLANG['TXT_DATA_HOME_KEYWORDS'],
                    'TXT_DATA_ENTRY_VOTING'            =>    $_ARRAYLANG['TXT_DATA_HOME_VOTING'],
                    'TXT_DATA_ENTRY_COMMENTS'        =>    $_ARRAYLANG['TXT_DATA_HOME_COMMENTS'],
                    'TXT_DATA_ENTRY_LINK'            =>    $_ARRAYLANG['TXT_DATA_HOME_LINK']
                ));

                $this->_objTpl->setVariable(array(
                    'DATA_ENTRY_ROWCLASS'        =>    ($intRowClass % 2 == 0) ? 'row1' : 'row2',
                    'DATA_ENTRY_ID'                =>    $intEntryId,
                    'DATA_ENTRY_DATE'            =>    $arrEntryValues['time_created'],
                    'DATA_ENTRY_AUTHOR_ID'        =>    $arrEntryValues['user_id'],
                    'DATA_ENTRY_AUTHOR_NAME'    =>    $arrEntryValues['user_name'],
                    'DATA_ENTRY_SUBJECT'        =>    $arrEntryValues['subject'],
                    'DATA_ENTRY_POSTED_BY'        =>    $this->getPostedByString($arrEntryValues['user_name'], $arrEntryValues['time_created']),
                    'DATA_ENTRY_INTRODUCTION'    =>    $this->getIntroductionText($arrEntryValues['translation'][$this->_intLanguageId]['content']),
                    'DATA_ENTRY_CONTENT'        =>    $arrEntryValues['translation'][$this->_intLanguageId]['content'],
                    'DATA_ENTRY_CATEGORIES'        =>    $this->getCategoryString($arrEntryValues['categories'][$this->_intLanguageId], true),
                    'DATA_ENTRY_TAGS'            =>    $this->getLinkedTags($arrEntryValues['translation'][$this->_intLanguageId]['tags']),
                    'DATA_ENTRY_COMMENTS'        =>    $arrEntryValues['comments_active'].'&nbsp;'.$_ARRAYLANG['TXT_DATA_HOME_COMMENTS'],
                    'DATA_ENTRY_VOTING'            =>    '&#216;&nbsp;'.$arrEntryValues['votes_avg'],
                    'DATA_ENTRY_VOTING_STARS'    =>    $this->getRatingBar($intEntryId),
                    'DATA_ENTRY_LINK'            =>    '<a href="index.php?section=Data&amp;cmd=details&amp;id='.$intEntryId.'" title="'.$arrEntryValues['subject'].'">'.$_ARRAYLANG['TXT_DATA_HOME_OPEN'].'</a>',
                    'DATA_ENTRY_IMAGE'            =>    ($arrEntryValues['translation'][$this->_intLanguageId]['image'] != '') ? '<img src="'.$arrEntryValues['translation'][$this->_intLanguageId]['image'].'" title="'.$arrEntryValues['subject'].'" alt="'.$arrEntryValues['subject'].'" />' : ''
                ));

                $this->_objTpl->parse('dataBlockEntries');
                ++$intRowClass;
            }
        }

        //Show overview of categories
        $arrCategories = $this->createCategoryArray();

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
                foreach($arrCurrentLanguageCategories as $intCategoryId => $strTranslation) {
                    $this->_objTpl->setVariable(array(
                        'DATA_CATEGORY_ID'        =>    $intCategoryId,
                        'DATA_CATEGORY_NAME'    =>    $strTranslation,
                        'DATA_CATEGORY_COUNT'    =>    $this->countEntriesOfCategory($intCategoryId)
                    ));
                    $this->_objTpl->parse('dataBlockCategories');
                }
            }
        }

        //Also try to fill the other variables (calendar, categories-dropdown, tag-cloud)
        @$this->_objTpl->setVariable('DATA_CALENDAR', $this->getHomeCalendar());
        @$this->_objTpl->setVariable('DATA_CATEGORIES', $this->getCategoryDropDown('frmDoSearch_Keyword_Category', 0, true));
        @$this->_objTpl->setVariable('DATA_TAGCLOUD', $this->getTagCloud());

        return $this->_objTpl->get();
    }


}
