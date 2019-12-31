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
 * Class XmlSitemapPageTree
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  core_pagetree
 */

namespace Cx\Core\PageTree;

/**
 * Class XmlSitemapPageTree
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @access      public
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  core_pagetree
 */
class XmlSitemapPageTree extends PageTree {

    private static $strFilePath = '';
    private static $strFileName = 'sitemap.xml';
    private static $strFileNameWithLang = 'sitemap_%s.xml';

    /**
     * Override the constructor from the PageTree
     * @see Cx\Core\PageTree::__construct()
     * @param type $entityManager
     * @param type $license
     * @param type $maxDepth
     * @param type $rootNode
     * @param type $lang
     * @param type $currentPage
     * @param type $skipInvisible
     * @param type $considerLogin
     * @param boolean $skipInactive           Skip inactive pages
     * @param boolean $considerSeoEnabledOnly Consider seo enabled pages alone
     */
    public function __construct($entityManager, $license, $maxDepth = 0, $rootNode = null,
                                $lang = null, $currentPage = null, $skipInvisible = false,
                                $considerLogin = false,
                                $skipInactive = true,
                                $considerSeoEnabledOnly = true
    ) {
        parent::__construct($entityManager, $license, $maxDepth, $rootNode, $lang,
                            $currentPage, $skipInvisible, $considerLogin, $skipInactive, $considerSeoEnabledOnly);
    }

    /**
     * Writes the XML-Sitemap in all langs (if activated in config)
     * @global type $_CONFIG
     * @global type $_CORELANG
     * @return boolean True on success (including deactivated), false otherwise
     */
    public static function write() {
        global $_CONFIG, $_CORELANG;

        if ($_CONFIG['xmlSitemapStatus'] == 'on') {
            $arrActiveLanguages = array();
            foreach (\FWLanguage::getLanguageArray() as $arrLanguage) {
                if ($arrLanguage['frontend'] == 1) {
                    $arrActiveLanguages[$arrLanguage['id']] = $arrLanguage['lang'];
                }
            }

            $arrFailed = array();
            foreach ($arrActiveLanguages as $langId => $langCode) {
                $langSitemap = new static(\Env::get('em'), \Env::get('cx')->getLicense(), 0, null, $langId);
                if (!$langSitemap->writeXML()) {
                    $arrFailed[] = sprintf($_CORELANG['TXT_CORE_XML_SITEMAP_NOT_WRITABLE'], sprintf(self::$strFileNameWithLang, $langCode));
                }
            }

            if (count($arrFailed)) {
                return implode('<br />', $arrFailed);
            }
        }

        return true;
    }

    /**
     * Renders the PageTree element
     * @param type $title
     * @param type $level
     * @param type $hasChilds
     * @param type $lang
     * @param type $path
     * @param type $current
     * @param type $page
     * @return type
     */
    protected function renderElement($title, $level, $hasChilds, $lang, $path, $current, $page) {
        // TODO: setting the proper protocol should be done in Url::fromPage()
        $protocol = '';
        $config = \Env::get('config');
        if ($config['forceProtocolFrontend'] != 'none') {
            $protocol = $config['forceProtocolFrontend'];
        }
        return "\t" . '<url>' .
                "\n\t\t" . '<loc>' . \Cx\Core\Routing\Url::fromPage($page, array(), $protocol)->toString() . '</loc>' .
                "\n\t\t" . '<lastmod>' . $this->getLastModificationDate($page) . '</lastmod>' .
                "\n\t\t" . '<changefreq>' . $this->getChangingFrequency($page) . '</changefreq>' .
                "\n\t\t" . '<priority>0.5</priority>' .
                "\n\t" . '</url>' . "\n";
    }

    public function preRenderLevel($level, $lang, $parentNode) {}

    public function postRenderLevel($level, $lang, $parentNode) {}

    /**
     * PageTree override (unused)
     * @param type $level
     * @param type $hasChilds
     * @param type $lang
     * @param type $page
     * @return string
     */
    protected function postRenderElement($level, $hasChilds, $lang, $page) {
        return '';
    }

    /**
     * Renders the head of the PageTree
     * @param type $lang
     * @return type
     */
    protected function renderHeader($lang) {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
                '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    }

    /**
     * Renders the foot of the PageTree
     * @param type $lang
     * @return string
     */
    protected function renderFooter($lang) {
        return '</urlset>';
    }

    /**
     * PageTree override (unused)
     * @param type $lang
     */
    protected function preRender($lang) {
    }

    /**
     * PageTree override (unused)
     * @param type $lang
     */
    protected function postRender($lang) {
    }


    /**
     * Write sitemap-file
     *
     * @global     object
     * @global     array
     * @param   array An array containing the language ID's of which languages should be included in the sitemap.
     * @param   string  The two letter language code of the selected language used as the virtual language path
     */
    protected function writeXML() {
        $filename = \FWLanguage::getLanguageCodeById($this->lang) ? sprintf(self::$strFileNameWithLang, \FWLanguage::getLanguageCodeById($this->lang)) : self::$strFileName;

        $xml = $this->render();

        try {
            $filePath = \Env::get('cx')->getWebsiteDocumentRootPath();
            $objFile = new \Cx\Lib\FileSystem\File($filePath . self::$strFilePath . '/' . $filename);
            $objFile->touch();
            $objFile->write($xml);
        } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
            \DBG::log($e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Creates the modification-date of a page as a string which can be processed by google. The method uses
     * for module-pages the current date, for normale pages the date of last modification.
     *
     * @param        integer        $intModule: value of the module-field in the database
     * @param        string        $strCmd: value of the cmd-field in the database
     * @param        integer        $intTimestamp: last update of the page as a timestamp
     * @return        string        A date string which can be understood by google
     */
    protected function getLastModificationDate($page) {
        $date = $page->getLastModificationDateTime();
        return $date->format('Y-m-d');
    }

    /**
     * Returns the changing-frequency of the page depending on the database values. If the page is a module
     * page, the frequency is set to 'hourly', for normal pages to 'weekly'.
     *
     * @param        integer        $intModule: value of the module-field in the database
     * @param        string        $strCmd: value of the cmd-field in the database
     * @return        string        true, if the page is a module page. Otherwise false.
     */
    protected function getChangingFrequency($page) {
        return $page->getChangeFrequency();
    }

    protected function init() {

    }

    protected function preRenderElement($level, $hasChilds, $lang, $page) {

    }

    protected function getFullNavigation() {
        return true;
    }
}
