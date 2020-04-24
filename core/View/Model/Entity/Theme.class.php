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
 * Theme
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Ueli Kramer <ueli.kramer@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_view
 */

namespace Cx\Core\View\Model\Entity;

/**
 * Theme
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Ueli Kramer <ueli.kramer@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_view
 */
class Theme extends \Cx\Core_Modules\Widget\Model\Entity\WidgetParseTarget
{
    private $id = null;
    private $themesname;
    private $foldername;
    private $expert;

    private $defaults = array();
    private $db;
    private $componentData;

    private $configurableLibraries;

    const THEME_TYPE_WEB = 'web';
    const THEME_TYPE_PRINT = 'print';
    const THEME_TYPE_MOBILE = 'mobile';
    const THEME_TYPE_APP = 'app';
    const THEME_TYPE_PDF = 'pdf';

    const THEME_PREVIEW_FILE = '/images/preview.gif'; // path from theme folder
    const THEME_DEFAULT_PREVIEW_FILE = '/core/Core/View/Media/theme_preview.gif'; // path from the document root
    const THEME_COMPONENT_FILE = '/component.yml'; // path from theme folder

    /**
     * @var array The channel enum values
     */
    public static $channels = array(
        'default', // web
        self::THEME_TYPE_MOBILE,
        self::THEME_TYPE_PRINT,
        self::THEME_TYPE_PDF,
        self::THEME_TYPE_APP,
    );

    public function __construct($id = null, $themesname = null, $foldername = null, $expert = 1) {
        $this->db = \Env::get('db');

        $this->setId($id);
        $this->setThemesname($themesname);
        $this->setFoldername($foldername);
        $this->setExpert($expert);
    }

    /**
     * @return string the version number of template
     */
    public function getVersionNumber() {
        if (empty($this->componentData['versions'])) {
            return null;
        }
        $versionInformation = current($this->componentData['versions']);
        $version = $versionInformation['number'];
        if (strpos(".", $version) === false) {
            $version = number_format($version, 1);
        }
        return $version;
    }

    /**
     * @return string the publisher of template
     */
    public function getPublisher() {
        if (empty($this->componentData['publisher'])) {
            return null;
        }
        return $this->componentData['publisher'];
    }

    /**
     * @return string the description
     */
    public function getDescription() {
        if (empty($this->componentData['description'])) {
            return null;
        }
        return $this->componentData['description'];
    }
    /**
     * @return string the release date of template
     */
    public function getReleasedDate() {
        if (empty($this->componentData['versions'])) {
            return null;
        }
        $versionInformation = current($this->componentData['versions']);
        $releaseDate = isset($versionInformation['releaseDate']) ? $versionInformation['releaseDate'] : time();

        if (preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1]).(0[1-9]|1[0-2]).[0-9]{4}$/", $releaseDate)) {
            $releaseDate = $this->getDateTimestamp($releaseDate);
        }

        $objReleaseDate = new \DateTime(date('Y-m-d', $releaseDate));

        return $objReleaseDate;
    }

    /**
     * @return string the subtype of the theme
     */
    public function getSubtype() {
        if (empty($this->componentData['subtype'])) {
            return null;
        }
        return $this->componentData['subtype'];
    }

    function getDateTimestamp($date) {
        //date format is dd.mm.yyyy
        $date = str_replace(".", "", $date);
        $posYear = 4;
        $posMonth = 2;
        $posDay = 0;

        $year = substr($date, $posYear,4);
        $month = substr($date, $posMonth,2);
        $day = substr($date, $posDay,2);

        $timestamp = mktime(0,0,0,$month,$day,$year);
        return $timestamp;
    }

    /**
     * @return string the extra description includes the names of end devices, where
     * the theme is set as default
     */
    public function getExtra() {
        global $_CORELANG;
        if (in_array(static::THEME_TYPE_WEB, $this->defaults)){
            return ' ('.$_CORELANG['TXT_DEFAULT'].')';
        } elseif (in_array(static::THEME_TYPE_MOBILE, $this->defaults)){
            return ' ('.$_CORELANG['TXT_ACTIVE_MOBILE_TEMPLATE'].')';
        } elseif (in_array(static::THEME_TYPE_PRINT, $this->defaults)){
            return ' ('.$_CORELANG['TXT_THEME_PRINT'].')';
        } elseif (in_array(static::THEME_TYPE_PDF, $this->defaults)) {
            return ' ('.$_CORELANG['TXT_THEME_PDF'].')';
        } elseif (in_array(static::THEME_TYPE_APP, $this->defaults)) {
            return ' ('.$_CORELANG['TXT_APP_VIEW'].')';
        }
        return null;
    }

    /**
     * Returns the array of active languages of theme by given type
     *
     * @param string $type the type of output device
     *
     * @return array array of languages active for this theme
     */
    public function getLanguagesByType($type) {
        switch ($type) {
            case self::THEME_TYPE_PRINT:
            case self::THEME_TYPE_MOBILE:
            case self::THEME_TYPE_APP:
            case self::THEME_TYPE_PDF:
                $channel = $type;
                break;
            default: // web
                $channel = 'default';
                break;
        }

        $languagesWithThisTheme = array();
        $frontendRepo = \Cx\Core\Core\Controller\Cx::instanciate()
            ->getDb()
            ->getEntityManager()
            ->getRepository('Cx\Core\View\Model\Entity\Frontend');
        $criteria = array(
            'theme' => $this->id,
            'channel' => $channel
        );
        $frontends = $frontendRepo->findBy($criteria);
        foreach ($frontends as $frontend) {
            $locale = $frontend->getLocaleRelatedByIso1s();
            $languagesWithThisTheme[$locale->getId()] = $locale->getShortForm();
        }

        return $languagesWithThisTheme;
    }

    /**
     * @return string the language abbreviations of activated languages
     * with this template, separated by comma
     */
    public function getLanguages()
    {
        $languagesWithThisTheme = array();
        $frontendRepo = \Cx\Core\Core\Controller\Cx::instanciate()
            ->getDb()
            ->getEntityManager()
            ->getRepository('Cx\Core\View\Model\Entity\Frontend');
        $criteria = array(
            'theme' => $this->id
        );
        $frontends = $frontendRepo->findBy($criteria);
        foreach($frontends as $frontend) {
            $languagesWithThisTheme[] = $frontend->getLocaleRelatedByIso1s()->getShortForm();
        }
        return implode(', ', $languagesWithThisTheme);
    }

    /**
     * @return array all dependencies (javascript libraries) which cloudrexx should
     * load when showing this template
     */
    public function getDependencies() {
        $dependencies = array();
        if (!isset($this->componentData['dependencies'])) {
            return $dependencies;
        }
        foreach ($this->componentData['dependencies'] as $dependency) {
            $dependencies[$dependency['name']] = array(
                $dependency['minimumVersionNumber'],
                $dependency['maximumVersionNumber']
            );
        }
        return $dependencies;
    }

    /**
     * @param string $type the type of end device
     * @return boolean true if it is set as default, false if not
     */
    public function isDefault($type = null) {
        if (!$type) {
            return !empty($this->defaults);
        }
        return in_array($type, $this->defaults);
    }

    /**
     * Checks whether the template is a valid component with component.yml file
     * @return bool true if a component.yml exists
     */
    public function isComponent() {
        return !empty($this->componentData);
    }

    /**
     * Compares two dependencies so they are loaded in the correct order.
     * @param array $a the dependency A
     * @param array $b the dependency B
     * @return int
     */
    protected function sortDependencies($a, $b) {
        $aName = $a['name'];
        $aVersion = $a['minimumVersionNumber'];
        $bName = $b['name'];
        $bVersion = $b['minimumVersionNumber'];

        $aDependencies =
                isset($this->configurableLibraries[$aName]['versions'][$aVersion]['dependencies']) ?
                    $this->configurableLibraries[$aName]['versions'][$aVersion]['dependencies'] : array();
        $bDependencies =
                isset($this->configurableLibraries[$bName]['versions'][$bVersion]['dependencies']) ?
                    isset($this->configurableLibraries[$bName]['versions'][$bVersion]['dependencies']) : array();

        // b is a dependency of a, b have to be loaded in front of a
        if (isset($aDependencies[$bName])) {
            return 1;
        }
        // a is a dependency of b, a have to be loaded in front of b
        if (isset($bDependencies[$aName])) {
            return -1;
        }
        // a sort is not needed because a and b have no relation
        return 0;
    }

    public function getId() {
        return $this->id;
    }

    public function getThemesname() {
        return $this->themesname;
    }

    public function getFoldername() {
        return $this->foldername;
    }

    public function getExpert() {
        return $this->expert;
    }

    public function getComponentData() {
        return $this->componentData;
    }

    public function setId($id) {
        $this->id = intval($id);
    }

    public function setThemesname($themesname) {
        $this->themesname = $themesname;
    }

    public function setFoldername($foldername) {
        $this->foldername = $foldername;
    }

    public function setExpert($expert) {
        $this->expert = intval($expert);
    }

    public function setComponentData($componentData) {
        $this->componentData = $componentData;
    }

    public function setDependencies($dependencies = array()) {
        $this->configurableLibraries = \JS::getConfigurableLibraries();
        usort($dependencies, array($this, 'sortDependencies'));
        $this->componentData['dependencies'] = $dependencies;
    }

    public function addDefault($type) {
        $this->defaults[] = $type;
    }

    /**
     * Get the themes file path
     *
     * @param type $filePath
     * @return string
     */
    public function getFilePath($filePath)
    {
        if (empty($filePath)) {
            return '';
        }
        $fileSystem = \Cx\Core\Core\Controller\Cx::instanciate()
            ->getMediaSourceManager()
            ->getMediaType('themes')
            ->getFileSystem();
        $file = new \Cx\Core\ViewManager\Model\Entity\ViewManagerFile($filePath, $fileSystem);

        return $fileSystem->getFullPath($file) . $file->getFullName();
    }

    /**
     * Preview image source web path
     *
     * @return string the preview image source web path
     */
    public function getPreviewImage()
    {
        $filePath = $this->getFilePath('/' . $this->getFoldername() . \Cx\Core\View\Model\Entity\Theme::THEME_PREVIEW_FILE);
        if ($filePath && file_exists($filePath)) {
            return $this->cx->getWebsiteThemesWebPath() . '/' . $this->getFoldername() . \Cx\Core\View\Model\Entity\Theme::THEME_PREVIEW_FILE;
        }
        return $this->cx->getCodeBaseOffsetPath(). \Cx\Core\View\Model\Entity\Theme::THEME_DEFAULT_PREVIEW_FILE;
    }

    /**
     * Dummy, we overwrite getContentTemplateForWidget() directly
     * It is necessary to add this method since it's abstract in the parent
     * class.
     * @return string Empty string
     */
    public function getWidgetContentAttributeName($widgetName) { return ''; }

    /**
     * Returns the template in which the widget can be used
     * @param string $widgetName Name of the Widget to get template for
     * @param int $langId Language ID
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Current page
     * @param string $channel Current channel
     * @return \Cx\Core\Html\Sigma Template which may contain the widget
     */
    protected function getContentTemplateForWidget($widgetName, $langId, $page, $channel) {
        // get static files
        $indexFile = $this->getFilePath($this->getFolderName() . '/index.html');
        $sidebarFile = $this->getFilePath($this->getFolderName() . '/sidebar.html');
        // get $contentFile of theme
        $mainController = $this->getComponentController();
        $contentFile = $mainController->getContentTemplateFileFromChannel(
            $channel,
            $mainController->getThemeFromChannel($channel, $page),
            $page
        );

        $template = new \Cx\Core\Html\Sigma();
        $template->loadTemplateFile($indexFile);
        $template->addBlock(
            'CONTENT_FILE',
            'content_file',
            file_get_contents($contentFile)
        );
        if ($template->placeholderExists('SIDEBAR_FILE')) {
            $template->addBlock(
                'SIDEBAR_FILE',
                'sidebar_file',
                file_get_contents($sidebarFile)
            );
        }

        return $template;
    }
}
