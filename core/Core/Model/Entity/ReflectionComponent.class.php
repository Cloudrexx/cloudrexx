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
 * Represents an abstraction of a component
 *
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_core
 * @version     3.1.0
 */

namespace Cx\Core\Core\Model\Entity;

/**
 * ReflectionComponentException
 *
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_core
 * @version     3.1.0
 */
class ReflectionComponentException extends \Exception {}

/**
 * Represents an abstraction of a component
 *
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_core
 * @version     3.1.0
 */
class ReflectionComponent {
    /**
     * List of all available component types
     * @todo Wouldn't it be better to move this to Component class?
     * @var array List of component types
     */
    protected static $componentTypes = array('core', 'core_module', 'module', 'lib');

    /**
     * Name of the component this instance is an abstraction of
     * @var string Component name
     */
    protected $componentName = null;

    /**
     * Type of the component this instance is an abstraction of
     * @var string Component type
     */
    protected $componentType = null;

    /**
     * Fully qualified filename for/of the package file
     * @var string ZIP package filename
     */
    protected $packageFile = null;

    /**
     * Database object
     *
     * @var object
     */
    protected $db = null;

    /**
     * Two different ways to instanciate this are supported:
     * 1. Supply an instance of \Cx\Core\Core\Model\Entity\Component
     * 2. Supply a install package zip filename
     * 3. Supply a component name and type
     * @param mixed $arg1 Either an instance of \Cx\Core\Core\Model\Entity\Component or the name of a component
     * @param string|null $arg2 (only if a component name was supplied as $arg1) Component type (one of core_module, module, core, lib)
     * @throws ReflectionComponentException
     * @throws \BadMethodCallException
     */
    public function __construct($arg1, $arg2 = null) {
        $this->db = \Env::get('cx')->getDb()->getAdoDb();

        if (is_a($arg1, 'Cx\Core\Core\Model\Entity\SystemComponent')) {
            $this->componentName = $arg1->getName();
            $this->componentType = $arg1->getType();
            return;
        }
        $arg1Parts = explode('.', $arg1);
        if (file_exists($arg1) && end($arg1Parts) == 'zip') {
            // clean up tmp dir
            \Cx\Lib\FileSystem\FileSystem::delete_folder(ASCMS_APP_CACHE_FOLDER, true);

            // Uncompress package using PCLZip
            $file = new \PclZip($arg1);
            $list = $file->extract(PCLZIP_OPT_PATH, ASCMS_APP_CACHE_FOLDER);

            // Check for meta.yml, if none: throw Exception
            if (!file_exists(ASCMS_APP_CACHE_FOLDER . '/meta.yml')) {
                throw new ReflectionComponentException('This ain\'t no package file: "' . $arg1 . '"');
            }

            // Read meta info
            $metaTypes = array('core'=>'core', 'core_module'=>'system', 'module'=>'application', 'lib'=>'other');
            $yaml = new \Symfony\Component\Yaml\Yaml();
            $content = file_get_contents(ASCMS_APP_CACHE_FOLDER . '/meta.yml');
            $meta = $yaml->parse($content);
            $type = array_key_exists($meta['DlcInfo']['type'], $metaTypes) ? $meta['DlcInfo']['type'] : 'lib';

            // initialize ReflectionComponent
            $this->packageFile = $arg1;
            $this->componentName = $meta['DlcInfo']['name'];
            $this->componentType = $type;
            return;
        } else if (is_string($arg1) && $arg2 && in_array($arg2, self::$componentTypes)) {
            $this->componentName = $arg1;
            $this->componentType = $arg2;

            // look for the valid component name or legacy
            if (!$this->isValidComponentName($this->componentName) && !$this->isValid()) {
                throw new \BadMethodCallException("Provided component name \"{$this->componentName}\" is invalid. Component name must be written in CamelCase notation.");
            }

            return;
        }
        throw new \BadMethodCallException('Pass a component or zip package filename or specify a component name and type');
    }

    /**
     * Check if the provided string is a valid component name
     * @param  string component name
     * @return boolean True if sring $name is a valid component name
     */
    public function isValidComponentName($name) {
        return preg_match('/^([A-Z][a-z0-9]*)+$/', $name);
    }

    /**
     * Returns the components name
     * @return string Component name
     */
    public function getName() {
        return $this->componentName;
    }

    /**
     * Returns the components type
     * @return string Component type
     */
    public function getType() {
        return $this->componentType;
    }

    /**
     * Tells wheter this component is customized or not
     * @return boolean True if customized (and customizings are active)
     */
    protected function isCustomized() {
        $basepath = ASCMS_DOCUMENT_ROOT . SystemComponent::getPathForType($this->componentType);
        $componentPath = $basepath . '/' . $this->componentName;
        return \Env::get('ClassLoader')->getFilePath($componentPath) != $componentPath;
    }

    /**
     * Returns wheter this component exists or not in the system
     * Note : It not depends the component type
     *
     * @param boolean $allowCustomizing (optional) Set to false if you want to ignore customizings
     * @return boolean True if it exists, false otherwise
     */
    public function exists($allowCustomizing = true) {
        foreach (self::$componentTypes as $componentType) {
            $basepath      = ASCMS_DOCUMENT_ROOT . \Cx\Core\Core\Model\Entity\SystemComponent::getPathForType($componentType);
            $componentPath = $basepath . '/' . $this->componentName;

            if (!$allowCustomizing) {
                if (file_exists($componentPath)) {
                    return true;
                }
            }
            if (\Env::get('cx')->getClassLoader()->getFilePath($componentPath)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns wheter this component installed or not
     *
     * @return boolean True if it exists, false otherwise
     */
    public function isInstalled() {
        $cx = \Env::get('cx');

        $query = '
            SELECT
                `id`
            FROM
                `' . DBPREFIX . 'component`
            WHERE
                `name` = \'' . $this->componentName . '\'
        ';
        $result = $cx->getDb()->getAdoDb()->query($query);
        if ($result && $result->RecordCount()) {
            return true;
        }

        $query = '
            SELECT
                `id`
            FROM
                `' . DBPREFIX . 'modules`
            WHERE
                `name` = \'' . $this->componentName . '\'
        ';
        $result = $cx->getDb()->getAdoDb()->query($query);

        if ($result && $result->RecordCount()) {
            return true;
        }

        return false;
    }

    /**
     * Returns wheter this component is valid or not. A valid component will work as expected
     * @return boolean True if valid, false otherwise
     */
    public function isValid() {
        // file system
        if (!$this->exists()) {
            return false;
        }

        // DB: entry in components or modules
        // DB: entry in backend areas
        // DB: existing page if necessary

        // what else?

        return true;
    }

    /**
     * Tells wheter this is a legacy component or not
     * @return boolean True if its a legacy one, false otherwise
     */
    public function isLegacy() {
        if (!$this->exists()) {
            return false;
        }
        if (file_exists($this->getDirectory() . '/Controller/')) {
            return false;
        }
        return true;
    }

    /**
     * Returns the absolute path to this component's location in the file system
     * @param boolean $allowCustomizing (optional) Set to false if you want to ignore customizings
     * @param boolean $forceCustomized (optional) If true, the directory in customizing folder is returned, default false
     * @return string Path for this component
     */
    public function getDirectory($allowCustomizing = true, $forceCustomized = false) {
        $docRoot = ASCMS_DOCUMENT_ROOT;
        if ($forceCustomized) {
            $allowCustomizing = false;
            $docRoot = ASCMS_CUSTOMIZING_PATH;
        }
        $basepath = $docRoot.SystemComponent::getPathForType($this->componentType);
        $componentPath = $basepath . '/' . $this->componentName;
        if (!$allowCustomizing) {
            return $componentPath;
        }
        return \Env::get('ClassLoader')->getFilePath($componentPath);
    }

    /**
     * Return namespace of the component
     *
     * @return string
     */
    public function getNameSpace() {
        return SystemComponent::getBaseNamespaceForType($this->componentType) . '\\' . $this->componentName;
    }

    /**
     * Installs this component from a zip file (if available)
     * @todo DB stuff (structure and data)
     * @todo check dependency versions
     * @todo activate templates
     */
    public function install() {
        // Check (not already installed (different version), all dependencies installed)
        if (!$this->packageFile) {
            throw new SystemComponentException('Package file not available');
        }
        if (!file_exists(ASCMS_APP_CACHE_FOLDER . '/meta.yml')) {
            throw new ReflectionComponentException('Invalid package file');
        }
        if ($this->exists()) {
            throw new SystemComponentException('Component is already installed');
        }

        $websitePath = \Env::get('cx')->getWebsiteDocumentRootPath();

        // Read meta file
        $yaml = new \Symfony\Component\Yaml\Yaml();
        $content = file_get_contents(ASCMS_APP_CACHE_FOLDER . '/meta.yml');
        $meta = $yaml->parse($content);

        // Check dependencies
        echo "Checking  dependencies ... ";
        foreach ($meta['DlcInfo']['dependencies'] as $dependencyInfo) {
            $dependency = new static($dependencyInfo['name'], $dependencyInfo['type']);
            if (!$dependency->exists()) {
                throw new SystemComponentException('Dependency "' . $dependency->getName() . '" not met');
            }
        }
        echo "Done \n";

        // Copy ZIP contents
        echo "Copying files to installation ... ";
        $filesystem = new \Cx\Lib\FileSystem\FileSystem();
        $filesystem->copyDir(
            ASCMS_APP_CACHE_FOLDER . '/DLC_FILES',
            ASCMS_APP_CACHE_FOLDER_WEB_PATH . '/DLC_FILES',
            '',
            $websitePath,
            '',
            '',
            true
        );
        echo "Done \n";

        // Activate (if type is system or application)
        // TODO: templates need to be activated too!
        if ($this->componentType != 'core' && $this->componentType != 'core_module' && $this->componentType != 'module') {
            return;
        }

        // Copy ZIP contents (also copy meta.yml into component folder if type is system or application)
        try {
            $objFile = new \Cx\Lib\FileSystem\File(ASCMS_APP_CACHE_FOLDER . '/meta.yml');
            $objFile->copy($this->getDirectory(false) . '/meta.yml');
        } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
            \DBG::msg($e->getMessage());
        }

        echo "Importing component data (structure & data) ... ";
        if (!file_exists($this->getDirectory(false)."/Model/Yaml")) {
            $this->importStructureFromSql();
        } else {
            $this->createTablesFromYaml();
        }
        $this->importDataFromSql();
        echo "Done \n";

        // Activate this component
        echo "Activating component ... ";
        $this->activate();
        echo "Done \n";
    }

    /**
     * Import table's from the yml files
     */
    protected function createTablesFromYaml()
    {
        $ymlDirectory = $this->getDirectory(false).'/Model/Yaml';

        $em  = \Env::get('cx')->getDb()->getEntityManager();

        $classes = array();
        foreach (glob($ymlDirectory.'/*.yml') as $yml) {
            $ymlArray  = \Symfony\Component\Yaml\Yaml::parse($yml);
            $classes[] = $em->getClassMetadata(key($ymlArray));
        }

        $scm = new \Doctrine\ORM\Tools\SchemaTool($em);
        $scm->createSchema($classes);
    }

    /**
     * Imports table structure to database from sql dump file
     */
    protected function importStructureFromSql()
    {
        $sqlDump = ASCMS_APP_CACHE_FOLDER . '/DLC_FILES'. SystemComponent::getPathForType($this->componentType) . '/' . $this->componentName . '/Data/Structure.sql';

        $fp = @fopen ($sqlDump, "r");
        if ($fp !== false) {
            while (!feof($fp)) {
                $buffer = fgets($fp);
                if ((substr($buffer,0,1) != "#") && (substr($buffer,0,2) != "--")) {
                    $sqlQuery .= $buffer;
                    if (preg_match("/;[ \t\r\n]*$/", $buffer)) {
                        $sqlQuery = preg_replace('#contrexx_#', DBPREFIX, $sqlQuery, 1);
                        $result = $this->db->Execute($sqlQuery);
                        if ($result === false) {
                            throw new SystemComponentException($sqlQuery .' ('. $this->db->ErrorMsg() .')');
                        }
                        $sqlQuery = '';
                    }
                }
            }
        } else {
            throw new SystemComponentException('File not found : '. $sqlDump);
        }
    }

    /**
     * import component data's to database from sql dump file
     */
    protected function importDataFromSql()
    {
        $sqlDump = ASCMS_APP_CACHE_FOLDER . '/DLC_FILES'. SystemComponent::getPathForType($this->componentType) . '/' . $this->componentName . '/Data/Data.sql';

        if (!file_exists($sqlDump)) {
            return;
        }

        $pattern = '/\s+INTO\s+`?([a-z\\d_]+)`?/i';

        $moduleId = 0;
        $fp = @fopen ($sqlDump, "r");
        if ($fp !== false) {
            while (!feof($fp)) {
                $buffer = fgets($fp);
                if ((substr($buffer,0,1) != "#") && (substr($buffer,0,2) != "--")) {
                    $sqlQuery .= $buffer;
                    if (preg_match("/;[ \t\r\n]*$/", $buffer)) {
                        $sqlQuery = preg_replace('#contrexx_#', DBPREFIX, $sqlQuery, 1);
                        $matches = null;
                        preg_match($pattern, $sqlQuery , $matches , 0);
                        $table = isset($matches[1]) ? $matches[1] : '';

                        switch ($table) {
                             case DBPREFIX.'modules':
                                 $data = $this->getColumnsAndDataFromSql($sqlQuery);
                                 $newModuleId = $this->db->GetOne('SELECT MAX(`id`)+1 FROM `'. DBPREFIX .'modules`');
                                 $replacements = array('id' => $newModuleId);
                                 $sqlQuery = $this->repalceDataInQuery($table, $data, $replacements);
                                 break;
                             case DBPREFIX.'component':
                                 $data = $this->getColumnsAndDataFromSql($sqlQuery);
                                 $replacements = array('id' => NULL);
                                 $sqlQuery = $this->repalceDataInQuery($table, $data, $replacements);
                                 break;
                             case DBPREFIX.'backend_areas':
                                 $data = $this->getColumnsAndDataFromSql($sqlQuery);
                                 $replacements = array('module_id' => $newModuleId);
                                 $sqlQuery = $this->repalceDataInQuery($table, $data, $replacements);
                                 break;
                             default :
                                 break;
                        }

                        $result = $this->db->Execute($sqlQuery);
                        if ($result === false) {
                            throw new SystemComponentException($sqlQuery .' ('. $this->db->ErrorMsg() .')');
                        }
                        $sqlQuery = '';
                    }
                }
            }
        }
    }

    /**
     * replace data in the existing data by the given replacements
     * and return the sql query
     *
     * @param string $table        Table name
     * @param array  $columns      Columns array
     * @param array  $data         Data array
     * @param array  $replacements Replacement data array
     */
    protected function repalceDataInQuery($table, $data, $replacements)
    {
        $data = array_intersect_key($replacements + $data, $data);

        $sql  = 'INSERT INTO `'.$table.'` ';
        $sql .= "SET \n";

        $firstCol = true;
        foreach($data as $column => $data) {
            $value = is_null($data) ? "NULL" : (is_string($data) ? "'$data'" : $data);

            $sql .= '    '.($firstCol ? '' : ',') ."`$column` = $value\n";
            $firstCol = false;
        }

        return $sql;
    }

    /**
     * parse the mysql query and return the columns and data from the given query.
     *
     * @param string $sqlQuery Mysql query
     *
     * @return array
     */
    public function getColumnsAndDataFromSql($sqlQuery)
    {
        $columnAndData = null;
        preg_match_all('/\((.+?)\)/', $sqlQuery, $columnAndData);
        $columnsString = $columnAndData[1][0];
        $dataString    = $columnAndData[1][1];

        $columns = null;
        preg_match_all('/\`(.*?)\`/', $columnsString, $columns);
        $data = null;
        preg_match_all('/\'(.*?)\'/', $dataString, $data);

        return array_combine($columns[1], $data[1]);
    }

    /**
     * Get the component related tables
     *
     * @return array  component related tables
     */
    protected function getComponentTables() {
        global $_DBCONFIG;

        // load tables
        $tblSyntax = DBPREFIX . $this->componentType . '_' . strtolower($this->componentName);
        $objResult = $this->db->query('SHOW TABLES LIKE "'. $tblSyntax .'_%"');

        $componentTables = array();
        while (!$objResult->EOF) {
            $componentTables[] = $objResult->fields['Tables_in_'. $_DBCONFIG['database'] .' ('. $tblSyntax .'_%)'];
            $objResult->MoveNext();
        }

        return $componentTables;
    }

    /**
     * Removes this component
     *
     * This might not work perfectly for legacy components, since there could
     * be files outside the component's directory!
     * Be sure there is no other component relying on this one!
     */
    public function remove() {
        // remove from db
        $this->removeFromDb();

        // if there are no files, quit
        if (!$this->exists()) {
            return;
        }

        // remove from fs
        \Cx\Lib\FileSystem\FileSystem::delete_folder($this->getDirectory(), true);
    }

    /**
     * This adds all necessary DB entries in order to activate this component (if they do not exist)
     * @todo Backend navigation entry (from meta.yml) (rxqcmv1)
     * @todo Pages (from meta.yml) (rxqcmv1)
     */
    public function activate() {
        if (!$this->exists()) {
            throw new \Cx\Core\Core\Controller\ComponentException('No such component: "' . $this->componentName . '" of type "' . $this->componentType . '"');
        }

        $cx = \Env::get('cx');
        $em = $cx->getDb()->getEntityManager();

        // component
        if (!$this->isLegacy()) {
            $componentRepo = $em->getRepository('Cx\\Core\\Core\\Model\\Entity\\SystemComponent');
            if (!$componentRepo->findOneBy(array(
                'name' => $this->componentName,
                'type' => $this->componentType,
            ))) {
                $component = new \Cx\Core\Core\Model\Entity\SystemComponent();
                $component->setName($this->componentName);
                $component->setType($this->componentType);
                $em->persist($component);
                $em->flush();
            }
        }

        // modules
        $distributor = 'Cloudrexx AG';
        $workbenchComponent = new self('Workbench', 'core_module');
        if ($workbenchComponent->exists()) {
            $workbench = new \Cx\Core_Modules\Workbench\Controller\Workbench();
            $distributor = $workbench->getConfigEntry('distributor');
        }
        $query = '
            SELECT
                `id`
            FROM
                `' . DBPREFIX . 'modules`
            WHERE
                `name` = \'' . $this->componentName . '\'
        ';
        $result = $cx->getDb()->getAdoDb()->query($query);
        if (!$result->EOF) {
            $id = $result->fields['id'];
            $query = '
                UPDATE
                    `' . DBPREFIX . 'modules`
                SET
                    `status` = \'y\',
                    `is_required` = ' . ((int) ($this->componentType == 'core')) . ',
                    `is_core` = ' . ((int) ($this->componentType == 'core' || $this->componentType == 'core_module')) . ',
                    `is_active` = 1,
                    `is_licensed` = 1
                WHERE
                    `id` = ' . $id . '
            ';
        } else {
            $query = '
                SELECT
                    `id`
                FROM
                    `' . DBPREFIX . 'modules`
                WHERE
                    `id` >= 900
                ORDER BY
                    `id` DESC
                LIMIT 1
            ';
            $id = 900;
            $result = $cx->getDb()->getAdoDb()->query($query);
            if (!$result->EOF) {
                $id = $result->fields['id'] + 1;
            }
            $query = '
                INSERT INTO
                    `' . DBPREFIX . 'modules`
                    (
                        `id`,
                        `name`,
                        `distributor`,
                        `description_variable`,
                        `status`,
                        `is_required`,
                        `is_core`,
                        `is_active`,
                        `is_licensed`
                    )
                VALUES
                    (
                        ' . $id . ',
                        \'' . $this->componentName . '\',
                        \'' . $distributor . '\',
                        \'TXT_' . strtoupper($this->componentType) . '_' . strtoupper($this->componentName) . '_DESCRIPTION\',
                        \'y\',
                        ' . ((int) ($this->componentType == 'core')) . ',
                        ' . ((int) ($this->componentType == 'core' || $this->componentType == 'core_module')) . ',
                        1,
                        1
                    )
            ';
        }
        $cx->getDb()->getAdoDb()->query($query);

        // backend_areas
        $query = '
            SELECT
                `area_id`
            FROM
                `'.DBPREFIX.'backend_areas`
            WHERE
                `uri` LIKE \'%cmd=' . contrexx_raw2db($this->componentName) . '&%\' OR
                `uri` LIKE \'%cmd=' . contrexx_raw2db($this->componentName) . '\'
        ';
        $result = $cx->getDb()->getAdoDb()->query($query);
        if (!$result->EOF) {
            $query = '
                UPDATE
                    `'.DBPREFIX.'backend_areas`
                SET
                    `module_id` = ' . $id . '
                WHERE
                    `area_id` = ' . $result->fields['area_id'] . '
            ';
        } else {
            $parent = 0;
            if ($this->componentType == 'module') {
                $parent = 2;
            }
            $order_id = 0;
            $query = '
                SELECT
                    `order_id`
                FROM
                    `'.DBPREFIX.'backend_areas`
                WHERE
                    `parent_area_id` = ' . $parent . '
                ORDER BY
                    `order_id` DESC
                LIMIT 1
            ';
            $result = $cx->getDb()->getAdoDb()->query($query);
            if (!$result->EOF) {
                $order_id = $result->fields['order_id'] + 1;
            }
            $access_id = 900;
            $query = '
                SELECT
                    `access_id`
                FROM
                    `'.DBPREFIX.'backend_areas`
                WHERE
                    `access_id` >= 900
                ORDER BY
                    `access_id` DESC
                LIMIT 1
            ';
            $result = $cx->getDb()->getAdoDb()->query($query);
            if (!$result->EOF) {
                $access_id = $result->fields['access_id'] + 1;
            }
            $query = '
                INSERT INTO
                    `'.DBPREFIX.'backend_areas`
                    (
                        `parent_area_id`,
                        `type`,
                        `scope`,
                        `area_name`,
                        `is_active`,
                        `uri`,
                        `target`,
                        `module_id`,
                        `order_id`,
                        `access_id`
                    )
                VALUES
                    (
                        ' . $parent . ',
                        \'navigation\',
                        \'backend\',
                        \'TXT_' . strtoupper($this->componentType) . '_' . strtoupper($this->componentName) . '\',
                        ' . ((int) ($parent == 2)) . ',
                        \'index.php?cmd=' . $this->componentName . '\',
                        \'_self\',
                        ' . $id . ',
                        ' . $order_id . ',
                        ' . $access_id . '
                    )
            ';
        }
        $cx->getDb()->getAdoDb()->query($query);

        // pages (if necessary) from repo (if has existing entry/ies) or empty one
        if ($this->componentType != 'module') {
            // only modules need a frontend page to be active
            return;
        }

        // we will not use modulemanager here in order to be able to replace
        // modulemanager by this in a later release

        $pageRepo = $em->getRepository('\Cx\Core\ContentManager\Model\Entity\Page');

        $pages = $pageRepo->findBy(array(
            'module' => $this->componentName,
            'type'   => \Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION,
        ));

        //Pages already exists so no need of adding pages again
        if (!empty($pages)) {
            return;
        }

        // does the module repository have something for us?
        if (!$this->loadPagesFromModuleRepository($id)) {

            $nodeRepo = $em->getRepository('\Cx\Core\ContentManager\Model\Entity\Node');

            // if not: create an empty page
            $parcat = $nodeRepo->getRoot();
            $newnode = new \Cx\Core\ContentManager\Model\Entity\Node();
            $newnode->setParent($parcat); // replace root node by parent!
            $em->persist($newnode);
            $em->flush();
            $nodeRepo->moveDown($newnode, true); // move to the end of this level
            foreach (\FWLanguage::getActiveFrontendLanguages() as $lang) {
                if ($lang['is_default'] === 'true' || $lang['fallback'] == null) {
                    $type = \Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION;
                } else {
                    $type = \Cx\Core\ContentManager\Model\Entity\Page::TYPE_FALLBACK;
                }
                $page = $pageRepo->createPage(
                    $newnode,
                    $lang['id'],
                    $this->componentName,
                    $type,
                    $this->componentName,
                    '',
                    false,
                    '{APPLICATION_DATA}'
                );
                $em->persist($page);
            }
            $em->flush();
        }

        // Update proxies
        $destPath = $em->getConfiguration()->getProxyDir();
        $metadatas = $em->getMetadataFactory()->getAllMetadata();
        $em->getProxyFactory()->generateProxyClasses($metadatas, $destPath);
    }

    /**
     * Loads pages from module repository
     * @param int $moduleId the module id
     * @return boolean True on success, false if no pages found in repo
     */
    protected function loadPagesFromModuleRepository($moduleId) {
        $cx = \Env::get('cx');
        $em = $cx->getDb()->getEntityManager();

        $id = $moduleId;

        $nodeRepo = $em->getRepository('\Cx\Core\ContentManager\Model\Entity\Node');
        $pageRepo = $em->getRepository('\Cx\Core\ContentManager\Model\Entity\Page');

        $module_name = $this->componentName;

        // get content from repo
        $query = '
            SELECT
                `id`,
                `moduleid`,
                `content`,
                `title`,
                `cmd`,
                `expertmode`,
                `parid`,
                `displaystatus`,
                `username`,
                `displayorder`
            FROM
                `'.DBPREFIX.'module_repository`
            WHERE
                `moduleid` = ' . $id . '
            ORDER BY
                `parid` ASC
        ';
        $objResult = $cx->getDb()->getAdoDb()->query($query);
        if ($objResult->EOF) {
            // no pages
            return false;
        }

        $paridarray = array();
        while (!$objResult->EOF) {
            // define parent node
            $root = false;
            if (isset($paridarray[$objResult->fields['parid']])) {
                $parcat = $paridarray[$objResult->fields['parid']];
            } else {
                $root = true;
                $parcat = $nodeRepo->getRoot();
            }

            // create node
            $newnode = new \Cx\Core\ContentManager\Model\Entity\Node();
            $newnode->setParent($parcat); // replace root node by parent!
            $em->persist($newnode);
            $em->flush();
            $nodeRepo->moveDown($newnode, true); // move to the end of this level
            $paridarray[$objResult->fields['id']] = $newnode;

            // add content to default lang
            // add content to all langs without fallback
            // link content to all langs with fallback
            foreach (\FWLanguage::getActiveFrontendLanguages() as $lang) {
                if ($lang['is_default'] === 'true' || $lang['fallback'] == null) {
                    $page = $pageRepo->createPage(
                        $newnode,
                        $lang['id'],
                        $objResult->fields['title'],
                        \Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION,
                        $module_name,
                        $objResult->fields['cmd'],
                        !$root && $objResult->fields['displaystatus'],
                        $objResult->fields['content']
                    );
                } else {
                    $page = $pageRepo->createPage(
                        $newnode,
                        $lang['id'],
                        $objResult->fields['title'],
                        \Cx\Core\ContentManager\Model\Entity\Page::TYPE_FALLBACK,
                        $module_name,
                        $objResult->fields['cmd'],
                        !$root && $objResult->fields['displaystatus'],
                        ''
                    );
                }
                $em->persist($page);
            }
            $em->flush();
            $objResult->MoveNext();
        }

        return true;
    }

    /**
     * This deactivates the component (does not remove any DB entries, except for pages)
     */
    public function deactivate() {
        $cx = \Env::get('cx');

        // deactivate in modules
        $adoDb = $cx->getDb()->getAdoDb();
        $query = '
            DELETE FROM
                `' . DBPREFIX . 'modules`
            WHERE
                `name` = \'' . $this->componentName . '\'
        ';
        $adoDb->execute($query);

        // remove pages
        $em = $cx->getDb()->getEntityManager();
        $pageRepo = $em->getRepository('Cx\\Core\\ContentManager\\Model\\Entity\\Page');
        $pages = $pageRepo->findBy(array(
            'module' => $this->componentName,
        ));
        foreach ($pages as $page) {
            $em->remove($page);
        }
        // remove component
        $componentRepo = $em->getRepository('Cx\Core\Core\Model\Entity\SystemComponent');
        $component = $componentRepo->findOneBy(
            array(
                'name' => $this->getName(),
                'type' => $this->getType(),
            )
        );
        $em->remove($component->getSystemComponent());
        $em->flush();
    }

    /**
     * This completely removes this component from DB
     * @todo Test removing components tables (including doctrine schema)
     */
    protected function removeFromDb() {
        $cx = \Env::get('cx');

        // component
        $em = $cx->getDb()->getEntityManager();
        $componentRepo = $em->getRepository('Cx\\Core\\Core\\Model\\Entity\\SystemComponent');
        $systemComponent = $componentRepo->findOneBy(array(
            'type' => $this->componentType,
            'name' => $this->componentName,
        ));
        if ($systemComponent) {
            $em->remove($systemComponent->getSystemComponent());
            $em->flush();
        }

        // modules (legacy)
        $adoDb = $cx->getDb()->getAdoDb();
        $query = '
            SELECT
                `id`
            FROM
                `' . DBPREFIX . 'modules`
            WHERE
                `name` = \'' . $this->componentName . '\'
        ';
        $res = $adoDb->execute($query);
        $moduleId = $res->fields['id'];

        if (!empty($moduleId)) {
            $query = '
                DELETE FROM
                    `' . DBPREFIX . 'modules`
                WHERE
                    `id` = \'' . $moduleId . '\'
            ';
            $adoDb->execute($query);

            // backend_areas
            $query = '
                DELETE FROM
                    `' . DBPREFIX . 'backend_areas`
                WHERE
                    `module_id` = \'' . $moduleId . '\'
            ';
            $adoDb->execute($query);
        }

        // module tables (LIKE DBPREFIX . strtolower($moduleName)%)
        $query = '
            SHOW TABLES
            LIKE
                \'' . DBPREFIX . 'module_' . strtolower($this->componentName) . '%\'
        ';
        $result = $adoDb->execute($query);
        while (!$result->EOF) {
            $query = '
                DROP TABLE
                    `' . current($result->fields) . '`
            ';
            $adoDb->execute($query);

            $result->MoveNext();
        }


        $query = 'DELETE FROM `'. DBPREFIX .'core_mail_template` WHERE `section` = "'. $this->componentName .'"';
        $adoDb->execute($query);

        $query = 'DELETE FROM `'. DBPREFIX .'core_text` WHERE `section` = "'. $this->componentName .'"';
        $adoDb->execute($query);

        $query = 'DELETE FROM `'. DBPREFIX .'core_setting` WHERE `section` = "'. $this->componentName .'"';
        $adoDb->execute($query);

        $query = 'DELETE FROM `'. DBPREFIX .'settings` WHERE `setname` LIKE "'. $this->componentName .'%"';
        $adoDb->execute($query);

        // pages
        $this->deactivate();
    }
}
