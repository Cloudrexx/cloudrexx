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
 * ThemeRepository
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Ueli Kramer <ueli.kramer@comvation.com>
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_view
 */

namespace Cx\Core\View\Model\Repository;

/**
 * ThemeRepository
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Ueli Kramer <ueli.kramer@comvation.com>
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_view
 */
class ThemeRepository
{
    /**
     * @var \ADOConnection database connection
     */
    private $db;

    /**
     * @var array ID=>\Cx\Core\View\Model\Entity\Theme
     */
    protected static $loadedThemes = array();

    public function __construct() {
        $this->db = \Env::get('db');
    }

    /**
     * Get the default theme by device type and language
     * @param string $type the type of output device
     * @param int $languageId the language id
     * @return \Cx\Core\View\Model\Entity\Theme the default theme
     */
    public function getDefaultTheme($type = \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_WEB, $languageId = null) {

        // filter for channel
        switch ($type) {
            case \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_PRINT:
            case \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_MOBILE:
            case \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_APP:
            case \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_PDF:
                $channel = $type;
                break;
            default:
                $channel = 'default';
                break;
        }

        // select theme of default language if no language id has been provided
        if (!isset($languageId)) {
            $languageId = \FWLanguage::getDefaultLangId();
        }

        $criteria = array(
            'language' => $languageId,
            'channel' => $channel
        );
        $frontendRepo = \Cx\Core\Core\Controller\Cx::instanciate()
            ->getDb()
            ->getEntityManager()
            ->getRepository('Cx\Core\View\Model\Entity\Frontend');
        $frontend = $frontendRepo->findOneBy($criteria);
        $themeId = $frontend->getTheme();
        // find theme by id and return it
        if (!empty($themeId)) {
            return $this->findById($themeId);
        }
        return null;
    }

    /**
     * get themes by its subtype
     *
     * @param string $type the sub type of the theme
     *
     * @return array array of themes filtered by the sub type
     */
    public function getThemesBySubType($type = \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_WEB)
    {
        $themes = array();
        foreach($this->findAll() as $theme) {
            $subType = $theme->getSubtype();
            if (
                   $type == $subType
                || (
                       empty($subType)
                    && in_array($type, array(\Cx\Core\View\Model\Entity\Theme::THEME_TYPE_WEB, \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_MOBILE))
                   )
            ) {
                $themes[] = $theme;
            }
        }

        return $themes;
    }

    /**
     * Get a theme by theme id
     * @param int $id the id of the theme
     * @return \Cx\Core\View\Model\Entity\Theme the theme
     */
    public function findById($id) {
        if (isset(static::$loadedThemes[$id])) {
            return static::$loadedThemes[$id];
        }
        $result = $this->db->SelectLimit('SELECT `id`, `themesname`, `foldername`, `expert` FROM `'.DBPREFIX.'skins` WHERE `id` = '.intval($id), 1);
        if ($result !== false && !$result->EOF) {
            static::$loadedThemes[$id] =  $this->getTheme(
                $result->fields['id'],
                $result->fields['themesname'],
                $result->fields['foldername'],
                $result->fields['expert'],
                null
            );
            return static::$loadedThemes[$id];
        }
        return null;
    }

    /**
     * Get multiple themes
     * @param array $crit the criterias
     * @param array $order the order, e.g. array( 'field' => 'ASC|DESC' )
     * @param int $languageId filter by language id
     * @return array theme objects
     */
    public function findBy($crit = array(), $order = array(), $languageId = null) {
        $query = 'SELECT `id`, `themesname`, `foldername`, `expert` FROM `'.DBPREFIX.'skins`';
        if (!empty($crit)) {
            $wheres = array();
            foreach ($crit as $field => $value) {
                $wheres[] = '`'.$field.'` = \''.contrexx_raw2db($value).'\'';
            }

            $query .= ' WHERE ' . implode(' AND ', $wheres);
        }
        if (!empty($order)) {
            $query .= ' ORDER BY ' . implode(',', $order);
        }
        $result = $this->db->Execute($query);
        $themes = array();
        if ($result !== false) {
            while (!$result->EOF) {
                $themes[] = $this->getTheme(
                    $result->fields['id'],
                    $result->fields['themesname'],
                    $result->fields['foldername'],
                    $result->fields['expert'],
                    $languageId
                );
                $result->MoveNext();
            }
        }
        return $themes;
    }

    /**
     * Find one theme by provided criterias and sort them in a defined order
     * @param array $crit the criterias
     * @param array $order the order, e.g. array( 'field' => 'ASC|DESC' )
     * @return \Cx\Core\View\Model\Entity\Theme the theme object
     */
    public function findOneBy($crit = array(), $order = array()) {
        return current($this->findBy($crit, $order));
    }

    /**
     * Get all themes as objects with a provided order and by language id
     * @param array $order the order, e.g. array( 'field' => 'ASC|DESC' )
     * @param int $languageId language id
     * @return array theme objects
     */
    public function findAll($order = array(), $languageId = null) {
        $query = 'SELECT `id`, `themesname`, `foldername`, `expert` FROM `'.DBPREFIX.'skins`';
        if (!empty($order)) {
            $query .= ' ORDER BY ' . implode(',', $order);
        }
        $result = $this->db->Execute($query);
        $themes = array();
        if ($result !== false) {
            while (!$result->EOF) {
                $themes[] = $this->getTheme(
                        $result->fields['id'],
                        $result->fields['themesname'],
                        $result->fields['foldername'],
                        $result->fields['expert'],
                        $languageId
                    );
                $result->MoveNext();
            }
        }
        return $themes;
    }

    /**
     * Get themes active themes
     */
    public function getActiveThemes() {

        $frontendRepo = \Cx\Core\Core\Controller\Cx::instanciate()
            ->getDb()
            ->getEntityManager()
            ->getRepository('Cx\Core\View\Model\Entity\Frontend');
        $frontends = $frontendRepo->findAll();

        $themesArray = array();

        foreach ($frontends as $frontend) {
            $themesArray[] = $frontend->getTheme();
        }
        $themesArray = array_unique($themesArray);

        $themes = array();

        foreach ($themesArray as $themeId) {
            $theme = $this->findById($themeId);
            if ($theme) {
                $themes[] = $theme;
            }
        }

        return $themes;
    }

    /**
     * Removes a theme from database
     * @param \Cx\Core\View\Model\Entity\Theme $theme a theme object
     * @return boolean true if the query has been successfully completed
     */
    public function remove($theme) {
        return $this->db->Execute('DELETE FROM `'.DBPREFIX.'skins` WHERE `id` = '.$theme->getId());
    }

    /**
     * Writes the component.yml file with the data defined in component data array
     *
     * @param \Cx\Core\View\Model\Entity\Theme $theme the theme object
     */
    public function saveComponentData(\Cx\Core\View\Model\Entity\Theme $theme) {
        global $_ARRAYLANG;

        if (!file_exists(\Env::get('cx')->getWebsiteThemesPath() . '/' . $theme->getFoldername())) {
            if (!\Cx\Lib\FileSystem\FileSystem::make_folder(\Env::get('cx')->getWebsiteThemesPath() . '/' . $theme->getFoldername())) {
                \Message::add($theme->getFoldername() . " : " . $_ARRAYLANG['TXT_THEME_UNABLE_TO_CREATE']);
            }
        }

        $filePath = \Env::get('cx')->getWebsiteThemesPath() . '/' . $theme->getFoldername() . '/component.yml';
        try {
            $file = new \Cx\Lib\FileSystem\File($filePath);
            $file->touch();

            $yaml = new \Symfony\Component\Yaml\Yaml();
            $file->write(
                $yaml->dump(
                    array('DlcInfo' => $theme->getComponentData())
                )
            );
        } catch (\Exception $e) {
            \DBG::log($e->getMessage());
            throw new $e;
        }
    }

    /**
     * Load the component data from component.yml file
     *
     * @param \Cx\Core\View\Model\Entity\Theme $theme
     */
    public function loadComponentData(\Cx\Core\View\Model\Entity\Theme &$theme)
    {
        $filePath = $theme->getFilePath('/' . $theme->getFoldername() . \Cx\Core\View\Model\Entity\Theme::THEME_COMPONENT_FILE);
        if ($filePath) {
            try {
                $objYaml = new \Symfony\Component\Yaml\Yaml();
                $objFile = new \Cx\Lib\FileSystem\File($filePath);
                $themeInformation = $objYaml->parse($objFile->getData());
                $theme->setComponentData($themeInformation['DlcInfo']);
            } catch (\Exception $e) {
                \DBG::log($e->getMessage());
            }
        }
    }

    /**
     * Get a theme object with all his attributes
     *
     * Loads the component data from component.yml file or creates one from info.xml
     * or a new one from static array
     * @param int $id the id of a theme, used for delete
     * @param string $themesname the display name of theme
     * @param string $foldername the physical folder name
     * @param int $expert
     * @param int $languageId language id
     * @return \Cx\Core\View\Model\Entity\Theme a theme object
     */
    protected function getTheme($id, $themesname, $foldername, $expert, $languageId = null) {
        $theme = new \Cx\Core\View\Model\Entity\Theme($id, $themesname, $foldername, $expert);


        // select theme of default language if no language id has been provided
        if (!isset($languageId)) {
            $languageId = \FWLanguage::getDefaultLangId();
        }
        // only load frontends with the given language and theme
        $criteria = array(
            'language' => $languageId,
            'theme' => $id
        );

        $frontendRepo = \Cx\Core\Core\Controller\Cx::instanciate()
            ->getDb()
            ->getEntityManager()
            ->getRepository('Cx\Core\View\Model\Entity\Frontend');
        $frontends = $frontendRepo->findBy($criteria);
        // add default theme for every channel
        foreach($frontends as $frontend) {
            $channel = $frontend->getChannel();
            if ($channel == 'default') {
                $channel = \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_WEB;
            }
            $theme->addDefault($channel);
        }

        $themePath = $theme->getFilePath('/'.$foldername);
        if (!file_exists($themePath)) {
            \DBG::log($foldername. ' :Theme folder not Exists');
            return $theme;
        }

        $this->loadComponentData($theme);
        // create a new one if no component.yml exists
        if (!$theme->isComponent()) {
            try {
                $this->convertThemeToComponent($theme);
            } catch (\Exception $ex) {
                \DBG::log($ex->getMessage());
                \DBG::log($theme->getThemesname() .' : Unable to convert theme to component');
            }
            $this->loadComponentData($theme);
        }

        return $theme;
    }

    /**
     * Generate a component.yml for each theme available on the system
     * only used in update process for fixing invalid themes
     */
    public function convertAllThemesToComponent() {
        foreach ($this->findAll() as $theme) {
            if ($theme->isComponent()) {
                continue;
            }
            try {
                $this->convertThemeToComponent($theme);
            } catch (\Exception $ex) {
                \DBG::log($ex->getMessage());
                \DBG::log($theme->getThemesname() .' : Unable to convert theme to component');
            }

        }
    }

    /**
     * Generate a component.yml for one theme available on the system
     *
     * @param \Cx\Core\View\Model\Entity\Theme $theme
     */
    public function convertThemeToComponent(\Cx\Core\View\Model\Entity\Theme $theme) {
        if ($theme->getComponentData()) {
            return;
        }

        $themePath = \Env::get('cx')->getWebsiteThemesPath() . '/' . $theme->getFoldername();
        $infoFile         = null;
        $themeInformation = array('DlcInfo' => array());
        if (file_exists($themePath . '/info.xml')) {
            try {
                // check for old info file
                $infoFile = new \Cx\Lib\FileSystem\File($themePath . '/info.xml');
                $this->xmlParseFile($infoFile);
                $themeInformation['DlcInfo'] = array(
                    'name' => $theme->getThemesname(),
                    'description' => $this->xmlDocument['THEME']['DESCRIPTION']['cdata'],
                    'type' => 'template',
                    'publisher' => $this->xmlDocument['THEME']['AUTHORS']['AUTHOR']['USER']['cdata'],
                    'subtype' => null,
                    'versions' => array(
                        'state' => 'stable',
                        'number' => $this->xmlDocument['THEME']['VERSION']['cdata'],
                        'releaseDate' => '',
                    ),
                );
                unset($this->xmlDocument);
            } catch (\Exception $e) {
                // not critical, ignore
            }
        } else {
            // create new data for new component.yml file
            $themeInformation['DlcInfo'] = array(
                'name' => $theme->getThemesname(),
                'description' => '',
                'type' => 'template',
                'publisher' => 'Cloudrexx AG',
                'subtype' => null,
                'versions' => array(
                    'state' => 'stable',
                    'number' => '1.0.0',
                    'releaseDate' => '',
                ),
            );
        }

        // Add default dependencies
        $themeInformation['DlcInfo']['dependencies'] = array(
            array(
                'name' => 'jquery',
                'type' => 'lib',
                'minimumVersionNumber' => '1.6.1',
                'maximumVersionNumber' => '1.6.1'
            )
        );

        // write components yaml
        $theme->setComponentData($themeInformation['DlcInfo']);
        try {
            $this->saveComponentData($theme);
        } catch (\Exception $e) {
            // could not write new component.yml file, try next time
            throw new $e;
        }
        if ($infoFile) {
            try {
                // delete existing info.xml file
                $infoFile->delete();
            } catch (\Exception $e) {
                // not critical, ignore
            }
        }
    }

    private $xmlDocument;
    private $currentXmlElement;
    private $arrParentXmlElement;

    /**
     * get XML info of specified modulefolder
     * @param string $themes
     */
    protected function xmlParseFile($file)
    {
        // start parsing
        $xmlParser = \xml_parser_create(CONTREXX_CHARSET);
        \xml_set_object($xmlParser, $this);
        \xml_set_element_handler($xmlParser, 'xmlStartTag', 'xmlEndTag');
        \xml_set_character_data_handler($xmlParser, "xmlCharacterDataTag");
        \xml_parse($xmlParser, $file->getData());
        \xml_parser_free($xmlParser);
    }

    /**
     * XML parser start tag
     * @param resource $parser
     * @param string $name
     * @param array $attrs
     */
    protected function xmlStartTag($parser, $name, $attrs)
    {
        if (isset($this->currentXmlElement)) {
            if (!isset($this->currentXmlElement[$name])) {
                $this->currentXmlElement[$name] = array();
                $this->arrParentXmlElement[$name] = &$this->currentXmlElement;
                $this->currentXmlElement = &$this->currentXmlElement[$name];
            } else {
                if (!isset($this->currentXmlElement[$name][0])) {
                    $arrTmp = $this->currentXmlElement[$name];
                    unset($this->currentXmlElement[$name]);// = array();
                    $this->currentXmlElement[$name][0] = $arrTmp;
                }

                array_push($this->currentXmlElement[$name], array());
                $this->arrParentXmlElement[$name] = &$this->currentXmlElement;
                $this->currentXmlElement = &$this->currentXmlElement[$name][count($this->currentXmlElement[$name])-1];
            }

        } else {
            $this->xmlDocument[$name] = array();
            $this->currentXmlElement = &$this->xmlDocument[$name];
        }

        if (count($attrs)>0) {
            foreach ($attrs as $key => $value) {
                $this->currentXmlElement['attrs'][$key] = $value;
            }
        }
    }

    /**
     * XML parser character data tag
     * @param resource $parser
     * @param string $cData
     */
    protected function xmlCharacterDataTag($parser, $cData)
    {
        $cData = trim($cData);
        if (!empty($cData)) {
            if (!isset($this->currentXmlElement['cdata'])) {
                $this->currentXmlElement['cdata'] = $cData;
            } else {
                $this->currentXmlElement['cdata'] .= $cData;
            }
        }
    }

    /**
     * XML parser end tag
     * @param resource $parser
     * @param string $name
     */
    protected function xmlEndTag($parser, $name)
    {
        $this->currentXmlElement = &$this->arrParentXmlElement[$name];
        unset($this->arrParentXmlElement[$name]);
    }
}
