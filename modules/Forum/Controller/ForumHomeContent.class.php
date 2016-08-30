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
 * Forum home content
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_forum
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Modules\Forum\Controller;

/**
 * Forum home content
 *
 * Show Forum Block Content
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_forum
 */
class ForumHomeContent extends ForumLibrary {

    var $_pageContent;
    var $_objTpl;

    /**
     * Constructor php5
     */
    function __construct($pageContent) {
        global $_LANGID;
        $this->_pageContent = $pageContent;
        $this->_objTpl = new \Cx\Core\Html\Sigma('.');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_intLangId = $_LANGID;
        $this->_arrSettings = $this->createSettingsArray();
    }

    /**
     * Constructor php4
     */
    function ForumHomeContent($pageContent) {
        $this->__construct($pageContent);
    }

    /**
     * Fetch latest entries and parse forumtemplate
     *
     * @return string parsed latest entries
     */
    function getContent()
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;
        $this->_objTpl->setTemplate($this->_pageContent,true,true);
        $this->_showLatestEntries($this->_getLatestEntries());
        return $this->_objTpl->get();
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
}
