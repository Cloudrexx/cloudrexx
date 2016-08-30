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
    private $db = null;

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
            $meta = $yaml->load($content);
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
        $meta = $yaml->load($content);

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
    function createTablesFromYaml()
    {
        $ymlDirectory = $this->getDirectory(false).'/Model/Yaml';

        $em  = \Env::get('cx')->getDb()->getEntityManager();

        $classes = array();
        foreach (glob($ymlDirectory.'/*.yml') as $yml) {
            $ymlArray  = \Symfony\Component\Yaml\Yaml::load($yml);
            $classes[] = $em->getClassMetadata(key($ymlArray));
        }

        $scm = new \Doctrine\ORM\Tools\SchemaTool($em);
        $scm->createSchema($classes);
    }

    /**
     * Imports table structure from sql
     */
    function importStructureFromSql()
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
     * import component data's from sql
     */
    function importDataFromSql()
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
    function repalceDataInQuery($table, $data, $replacements)
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
     * Create zip install package for this component
     * @param string $path Path to store zip file at
     * @todo add data files (db)
     * @todo create meta.yml
     * @todo allow template files
     * @todo test $customized
     */
    public function pack($path, $customized = false) {

        $pathParts = explode('.', $path);
        if (empty($path) || end($pathParts) != 'zip') {
            throw new ReflectionComponentException('Invalid file name passed. Provide a valid zip file name');
        }

        $websitePath = \Env::get('cx')->getWebsiteDocumentRootPath();
        // Create temp working folder and copy ZIP contents
        $filesystem = new \Cx\Lib\FileSystem\FileSystem();
        // clean up tmp dir
        $filesystem->delete_folder(ASCMS_APP_CACHE_FOLDER, true);
        echo "Copying files ... ";
        $filesystem->make_folder(ASCMS_APP_CACHE_FOLDER . '/DLC_FILES'. SystemComponent::getPathForType($this->componentType), true);

        $cacheComponentFolderPath = ASCMS_APP_CACHE_FOLDER . '/DLC_FILES'. SystemComponent::getPathForType($this->componentType) . '/' . $this->componentName;
        $cacheComponentFolderWebPath = ASCMS_APP_CACHE_FOLDER_WEB_PATH . '/DLC_FILES'. SystemComponent::getPathForType($this->componentType) . '/' . $this->componentName;
        $filesystem->copyDir(
            $this->getDirectory(false),
            preg_replace('#' . $websitePath . '#', '', $this->getDirectory(false)),
            '',
            $cacheComponentFolderPath,
            $cacheComponentFolderWebPath,
            '',
            true
        );
        echo "Done \n";

        if ($customized) {
            // overwrite with contents of $this->getDirectory(true, true)
            echo "Copying customizing files ... ";
            $filesystem->copyDir(
                $this->getDirectory(true, true),
                preg_replace('#' . $websitePath . '#', '', $this->getDirectory(true, true)),
                '',
                $cacheComponentFolderPath,
                $cacheComponentFolderWebPath,
                '',
                true
            );
            echo "Done \n";
        }

        echo "Writing component data (structure & data) ... ";
        $this->writeDatabaseStructureAndData();
        echo "Done \n";

        $componentFolder = $this->getDirectory($customized);
        if (!file_exists($componentFolder . '/meta.yml')) {
            echo "Meta file not exist. \n";
            echo "Creating meta file ... ";
            // Create meta.yml
            $this->writeMetaDataToFile($cacheComponentFolderPath . '/meta.yml');
            echo "Done \n";
        } else {
            echo "Copying additional files ...";
            // Read meta file
            $yaml = new \Symfony\Component\Yaml\Yaml();
            $content = file_get_contents($componentFolder . '/meta.yml');
            $meta = $yaml->load($content);
            if (isset($meta['DlcInfo']['additionalFiles'])) {
                foreach ($meta['DlcInfo']['additionalFiles'] as $additionalFile) {
                    $srcPath = $websitePath. '/'. $additionalFile;
                    if ($filesystem->exists($srcPath)) {
                        if (is_dir($srcPath)) {
                            $filesystem->copyDir(
                                $srcPath,
                                preg_replace('#' . $websitePath . '#', '', $srcPath),
                                '',
                                ASCMS_APP_CACHE_FOLDER . '/DLC_FILES/' . $additionalFile,
                                ASCMS_APP_CACHE_FOLDER_WEB_PATH . '/DLC_FILES/' . $additionalFile,
                                '',
                                true
                            );
                        } else {
                            $folder = dirname($srcPath);
                            $folderPath = preg_replace('#' . $websitePath . '#', '', $folder);
                            $filesystem->make_folder(ASCMS_APP_CACHE_FOLDER . '/DLC_FILES'. $folderPath, true);
                            $filesystem->copy_file($srcPath, ASCMS_APP_CACHE_FOLDER . '/DLC_FILES/'. $additionalFile);
                        }
                    } else {
                        echo "WARNING: File missing - ". $additionalFile;
                    }
                }
            }
            echo "Done \n";
        }
        $filesystem->copy_file($cacheComponentFolderPath . '/meta.yml', ASCMS_APP_CACHE_FOLDER . '/meta.yml');
        $filesystem->copy_file($websitePath . '/core/Core/Data/README.txt', ASCMS_APP_CACHE_FOLDER . '/README.txt');

        echo "Exporting component ... ";
        // Compress
        $file = new \PclZip($path);
        $file->create(ASCMS_APP_CACHE_FOLDER, PCLZIP_OPT_REMOVE_PATH, ASCMS_APP_CACHE_FOLDER);
        echo "Done \n";
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
     * Write db structure and data into a file
     *
     * @global type $_DBCONFIG
     */
    private function writeDatabaseStructureAndData()
    {
        $componentTables = $this->getComponentTables();

        $dataFolder = ASCMS_APP_CACHE_FOLDER . '/DLC_FILES'. SystemComponent::getPathForType($this->componentType) . '/' . $this->componentName . '/Data';
        \Cx\Lib\FileSystem\FileSystem::make_folder($dataFolder);

        // check whether its a doctrine component
        if (!file_exists($this->getDirectory(false)."/Model/Yaml")) {
            $this->writeTableStructureToFile($componentTables, $dataFolder . '/Structure.sql');
        }

        $this->writeTableDataToFile($componentTables, $dataFolder . '/Data.sql');
    }

    /**
     * Write the component data into the file
     *
     * @param type $arrayTables
     * @param type $path
     * @return type
     */
    private function writeTableDataToFile($arrayTables, $path)
    {
        if (empty($path)) {
            return;
        }

        try {
            $objFile = new \Cx\Lib\FileSystem\File($path);
            $objFile->touch();

            // Dump the core data's to the file
            $objFile->append("-- modules".PHP_EOL);
            $table = DBPREFIX .'modules';
            $query = 'SELECT *
                        FROM
                            `'. DBPREFIX .'modules`
                        WHERE
                            `name` = "' . $this->componentName . '"';
            $this->writeTableDataToFileFromQuery($table, $query, $objFile);

            $objFile->append("-- component".PHP_EOL);

            $table = DBPREFIX .'component';
            $query = 'SELECT *
                        FROM
                            `'. DBPREFIX .'component`
                        WHERE
                            `name` = "' . $this->componentName . '"';
            $this->writeTableDataToFileFromQuery($table, $query, $objFile);

            $objFile->append("-- Backend Areas".PHP_EOL);
            $table = DBPREFIX .'backend_areas';
            $query = 'SELECT b.*
                        FROM
                            `'. DBPREFIX .'backend_areas` AS b
                        LEFT JOIN
                            `'. DBPREFIX .'modules` AS m
                        ON
                            m.`id` = b.`module_id`
                        WHERE
                            m.`name` = "' . $this->componentName . '"';
            $this->writeTableDataToFileFromQuery($table, $query, $objFile);

            $objFile->append("-- Access group static ids".PHP_EOL);
            $table = DBPREFIX .'access_group_static_ids';
            $query = 'SELECT a.*
                        FROM
                            `'. DBPREFIX .'access_group_static_ids` AS a
                        LEFT JOIN
                            `'. DBPREFIX .'backend_areas` AS b
                        ON
                            b.`access_id` = a.`access_id`
                        LEFT JOIN
                            `'. DBPREFIX .'modules` AS m
                        ON
                            m.`id` = b.`module_id`
                        WHERE
                            m.`name` = "' . $this->componentName . '"';
            $this->writeTableDataToFileFromQuery($table, $query, $objFile);

            $objFile->append("-- Mail template".PHP_EOL);
            $table = DBPREFIX .'core_mail_template';
            $query = 'SELECT * FROM `'. DBPREFIX .'core_mail_template` WHERE `section` = "'. $this->componentName .'"';
            $this->writeTableDataToFileFromQuery($table, $query, $objFile);

            $objFile->append("-- Mail text".PHP_EOL);
            $table = DBPREFIX .'core_text';
            $query = 'SELECT * FROM `'. DBPREFIX .'core_text` WHERE `section` = "'. $this->componentName .'"';
            $this->writeTableDataToFileFromQuery($table, $query, $objFile);

            $objFile->append("-- Core Settings".PHP_EOL);
            $table = DBPREFIX .'core_setting';
            $query = 'SELECT * FROM `'. DBPREFIX .'core_setting` WHERE `section` = "'. $this->componentName .'"';
            $this->writeTableDataToFileFromQuery($table, $query, $objFile);

            $objFile->append("-- Settings".PHP_EOL);
            $table = DBPREFIX .'settings';
            $query = 'SELECT * FROM `'. DBPREFIX .'settings` WHERE `setname` LIKE "'. $this->componentName .'%"';
            $this->writeTableDataToFileFromQuery($table, $query, $objFile);

            foreach ($arrayTables as $table) {
                $query = 'SELECT * FROM '.$table;
                $this->writeTableDataToFileFromQuery($table, $query, $objFile);
            }

        } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
            \DBG::msg($e->getMessage());
        }
    }

    /**
     * write the database table data's in to the given file object
     *
     * @see self::writeTableDataToFile()
     *
     * @param string $table   Table name
     * @param string $query   query to the records
     * @param object $objFile File object
     *
     * @return null
     */
    private function writeTableDataToFileFromQuery($table, $query, $objFile)
    {
        $fields       = $this->getColumnsFromTable($table);
        $columnString = '`'. implode('`, `', $fields) .'`';

        $tableName = preg_replace('#'. DBPREFIX .'#', 'contrexx_', $table, 1);

        $objResult = $this->db->query($query);
        if ($objResult) {
            while (!$objResult->EOF) {
                $datas = array();
                foreach ($fields as $field) {
                    $data    = str_replace("\r\n", "\\r\\n", addslashes($objResult->fields[$field]));
                    $datas[] = $data;
                }

                $dataString = '\'' . implode('\', \'', $datas) . '\'';

                $dataLine = 'INSERT IGNORE INTO `'.$tableName.'` (' . $columnString . ') VALUES ('. $dataString .');' . PHP_EOL;
                $objFile->append($dataLine);

                $objResult->MoveNext();
            }
        }
    }

    /**
     * Returns the tables column's
     *
     * @see self::writeTableDataToFileFromQuery()
     *
     * @param string $tableName table name
     *
     * @return array Array of table columns
     */
    private function getColumnsFromTable($tableName)
    {
        $fields = array();

        $objCoulmns = $this->db->query('SHOW COLUMNS FROM `' . $tableName . '`');
        while (!$objCoulmns->EOF) {
            $fields[] = $objCoulmns->fields['Field'];
            $objCoulmns->MoveNext();
        }

        return $fields;
    }

    /**
     * Write the table sturctures to the file
     *
     * @param array  $arrayTables Table name to export structure
     * @param string $path        File path
     *
     * @return null
     */
    private function writeTableStructureToFile($arrayTables, $path)
    {
        if (empty($arrayTables) || empty($path)) {
            return;
        }

        try {
            $file = new \Cx\Lib\FileSystem\File($path);
            $file->touch();
            foreach ($arrayTables as $table) {
                $file->append($this->getTableStructure($table));
            }
        } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
            \DBG::msg($e->getMessage());
        }
    }

    /**
     * Writes to file the $table's structure
     *
     * @param string $table The table name
     * @access private
     * @return boolean|string return false when table not exists or return table schema
     */
    private function getTableStructure($table)
    {
        // Structure Header
        $structure  = '';
        $structure .= "-- \n";
        $structure .= "-- Table structure for table `{$table}` \n";
        $structure .= "-- \n\n";

        $tableName = preg_replace('#'. DBPREFIX .'#', 'contrexx_', $table, 1);

        // Dump Structure
        $structure .= 'DROP TABLE IF EXISTS `'.$tableName.'`;'."\n";
        $structure .= "CREATE TABLE `".$tableName."` (\n";
        $objResult  = $this->db->Execute('SHOW FIELDS FROM `'.$table.'`');
        if ( $objResult->RecordCount() == 0 ) {
            return false;
        }
        while(!$objResult->EOF) {
            $structure .= '`'.$objResult->fields['Field'].'` '.$objResult->fields['Type'];
            if ( @strcmp($objResult->fields['Null'],'YES') != 0 ) {
                $structure .= ' NOT NULL';
            } else {
                $structure .= ' NULL';
            }

            if ( !empty($objResult->fields['Default']) || @strcmp($objResult->fields['Null'],'YES') == 0) {
                $structure .= ' DEFAULT '.(is_null($objResult->fields['Default']) ? 'NULL' : "'{$objResult->fields['Default']}'");
            }

            if ( !empty($objResult->fields['Extra']) ) {
                $structure .= ' '.$objResult->fields['Extra'];
            }

            $structure .= ",\n";
            $objResult->MoveNext();
        }

        $structure = preg_replace("/,\n$/", '', $structure);

        // Save all Column Indexes
        $structure .= $this->getSqlKeysTable($table);
        $structure .= "\n)";

        //Save table engine
        $objTableStatus = $this->db->Execute("SHOW TABLE STATUS LIKE '".$table."'");
        if ($objTableStatus) {
            if (!empty($objTableStatus->fields['Engine'])) {
                $structure .= ' ENGINE='.$objTableStatus->fields['Engine'];
            }
            if (!empty($objTableStatus->fields['Auto_increment'])) {
                $structure .= ' AUTO_INCREMENT='.$objTableStatus->fields['Auto_increment'];
            }
        }

        $structure .= ";\n\n-- --------------------------------------------------------\n\n";

        return $structure;
    }

    /**
     * Writes to file the $table's structure
     *
     * @param string $table The table name
     * @access private
     * @return boolean|string return false when table not exists or return table schema
     */
    private function getSqlKeysTable($table)
    {
        $primary = "";
        $unique  = $index = $fulltext = array();

        $objResult = $this->db->Execute("SHOW KEYS FROM `{$table}`");
        if ($objResult->RecordCount() == 0) {
            return false;
        }
        while (!$objResult->EOF) {
            if (($objResult->fields['Key_name'] == 'PRIMARY') && ($objResult->fields['Index_type'] == 'BTREE')) {
                if ( $primary == '' ) {
                    $primary = "  PRIMARY KEY  (`{$objResult->fields['Column_name']}`";
                } else {
                    $primary .= ", `{$objResult->fields['Column_name']}`";
                }
            }
            if (($objResult->fields['Key_name'] != 'PRIMARY') && ($objResult->fields['Non_unique'] == '0') && ($objResult->fields['Index_type'] == 'BTREE')) {
                if ( (!is_array($unique)) || ($unique[$objResult->fields['Key_name']]=="") ) {
                    $unique[$objResult->fields['Key_name']] = "  UNIQUE KEY `{$objResult->fields['Key_name']}` (`{$objResult->fields['Column_name']}`";
                } else {
                    $unique[$objResult->fields['Key_name']] .= ", `{$objResult->fields['Column_name']}`";
                }
            }
            if (($objResult->fields['Key_name'] != 'PRIMARY') && ($objResult->fields['Non_unique'] == '1') && ($objResult->fields['Index_type'] == 'BTREE')) {
                if ( (!is_array($index)) OR ($index[$objResult->fields['Key_name']]=="") ) {
                    $index[$objResult->fields['Key_name']] = "  KEY `{$objResult->fields['Key_name']}` (`{$objResult->fields['Column_name']}`";
                } else {
                    $index[$objResult->fields['Key_name']] .= ", `{$objResult->fields['Column_name']}`";
                }
            }
            if (($objResult->fields['Key_name'] != 'PRIMARY') && ($objResult->fields['Non_unique'] == '1') && ($objResult->fields['Index_type'] == 'FULLTEXT')) {
                if ( (!is_array($fulltext)) || ($fulltext[$objResult->fields['Key_name']]=="") ) {
                    $fulltext[$objResult->fields['Key_name']] = "  FULLTEXT `{$objResult->fields['Key_name']}` (`{$objResult->fields['Column_name']}`";
                } else {
                    $fulltext[$objResult->fields['Key_name']] .= ", `{$objResult->fields['Column_name']}`";
                }
            }
            $objResult->MoveNext();
        }


        $sqlKeyStatement = '';
        // generate primary, unique, key and fulltext
        if ($primary != "") {
            $sqlKeyStatement .= ",\n";
            $primary .= ")";
            $sqlKeyStatement .= $primary;
        }
        foreach ($unique as $keyName => $keyDef) {
            $sqlKeyStatement .= ",\n";
            $keyDef .= ")";
            $sqlKeyStatement .= $keyDef;
        }

        foreach ($index as $keyName => $keyDef) {
            $sqlKeyStatement .= ",\n";
            $keyDef .= ")";
            $sqlKeyStatement .= $keyDef;
        }

        foreach ($fulltext as $keyName => $keyDef) {
            $sqlKeyStatement .= ",\n";
            $keyDef .= ")";
            $sqlKeyStatement .= $keyDef;
        }

        return $sqlKeyStatement;
    }

    /**
     * Write the meta information of the component to the file
     *
     * @param \Cx\Lib\FileSystem\File $file Path to meta file
     */
    private function writeMetaDataToFile($file)
    {
        $publisher = '';
        $query = '
            SELECT
                `distributor`
            FROM
                `'.DBPREFIX.'modules`
            WHERE
                `name` = "' . $this->componentName . '"
            LIMIT 1
        ';
        $result = $this->db->query($query);

        if (!$result->EOF) {
            $publisher = $result->fields['distributor'];
        }

        $content = array(
            'DlcInfo' => array(
                 'name' => $this->componentName,
                 'type' => $this->componentType,
                 'publisher' => $publisher,
                 'dependencies' => null,
                 'versions' => null,
                 'rating' => 0,
                 'downloads' => 0,
                 'price' => 0.0,
                 'pricePer' => 0,
                 'additionalFiles' => array()
            )
        );

        try {
            $file = new \Cx\Lib\FileSystem\File($file);
            $file->touch();
            $file->write(
                    \Symfony\Component\Yaml\Yaml::dump($content, 3)
            );
        } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
            \DBG::msg($e->getMessage());
        }
    }

    /**
     * Creates this component using a skeleton
     */
    public function create() {
        if ($this->exists()) {
            throw new SystemComponentException('Component is already Exists');
        }

        // copy skeleton component
        \Cx\Lib\FileSystem\FileSystem::copy_folder(ASCMS_CORE_PATH.'/Core/Data/Skeleton', $this->getDirectory(false));

        $this->fixNamespaces('Cx\Modules\Skeleton', $this->getDirectory());
        $this->fixLanguagePlaceholders('MODULE_SKELETON', $this->getDirectory());
        $this->fixDocBlocks('modules_skeleton', $this->getDirectory());
        $this->setComponentName($this->getDirectory());

        // activate component
        $this->activate();
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
     * List dependencies from this component to other parts of the system
     * @todo List files for matches (rxqcmv1)
     * @todo Make this work for legacy components too
     * @todo Make this work for zip packages too (rxqcmv1)
     * @return array Returns an array like array({dependency}=>{number_of_times_used})
     */
    public function getDependencies() {
        if ($this->isLegacy()) {
            return array('unknown');
        }
        $dependencies = array();

        $directoryIterator = new \RecursiveDirectoryIterator($this->getDirectory());
        $iterator = new \RecursiveIteratorIterator($directoryIterator);
        $files = new \RegexIterator($iterator, '/^.+\.php$/i', \RegexIterator::GET_MATCH);

        // recursive foreach .php file
        $componentNs = SystemComponent::getBaseNamespaceForType($this->componentType) . '\\' . $this->componentName;
        $matches = array();
        foreach($files as $file) {
            $file = current($file);
            // search for namespaces other than Component's

            $objFile = new \Cx\Lib\FileSystem\File($file);
            $content = $objFile->getData();

            preg_match_all('/(?:[A-Za-z_]+)?\\\\[A-Za-z_\\\\]+/', $content, $matches);
            foreach ($matches[0] as $match) {
                if (substr($match, 0, 1) != '\\') {
                    $match = '\\' . $match;
                }
                $matchBaseNs = substr($match, 0, strlen('\\' . $componentNs));
                if ($matchBaseNs != '\\' . $componentNs && strlen($match) > 2) {
                    if (preg_match('/\\\\r\\\\n/', $match)) {
                        continue;
                    }
                    if (preg_match('/\\\\(?:Doctrine|Gedmo)/', $match)) {
                        $match = '\\Doctrine\\...';
                    }
                    $dependencies[] = preg_replace('/\\\\\\\\/', '\\', $match);
                }
            }
        }

        $dependencies = array_count_values($dependencies);
        arsort($dependencies);
        return $dependencies;
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
                    ''
                );
                $em->persist($page);
            }
            $em->flush();
        }
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
            UPDATE
                `' . DBPREFIX . 'modules`
            SET
                `is_active` = 0,
                `is_licensed` = 0
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

    /**
     * Changes type or name of this component
     *
     * This can move a component to customizing and back
     * @param string $newName New component name
     * @param string $newType New component type, one of 'core', 'core_module' and 'module'
     * @param boolean $customized (optional) Copy/move to customizing folder? Default false
     * @return ReflectionComponent ReflectionComponent for new component
     */
    public function move($newName, $newType, $customized = false) {
        return $this->internalRelocate($newName, $newType, $customized, false);
    }

    /**
     * Generates a copy of this component with name and type specified.
     *
     * Using the third parameter this can be used to copy a component to
     * customizing or the other way
     * @param string $newName New component name
     * @param string $newType New component type, one of 'core', 'core_module' and 'module'
     * @param boolean $customized (optional) Copy/move to customizing folder? Default false
     * @return ReflectionComponent ReflectionComponent for new component (aka "the copy")
     */
    public function copy($newName, $newType, $customized = false) {
        return $this->internalRelocate($newName, $newType, $customized, true);
    }

    /**
     * Fix the namespace of all files of this component
     * @param string $oldBaseNs Base namespace of old component
     * @param string $baseDir Directory in which the recursive replace should be done
     * @return bool
     * @todo Test references update in DB (rxqcmv1)
     */
    public function fixNamespaces($oldBaseNs, $baseDir) {
        // calculate new proper base NS
        $baseNs = SystemComponent::getBaseNamespaceForType($this->componentType) . '\\' . $this->componentName;
        //$baseDir = $this->getDirectory();

        $directoryIterator = new \RecursiveDirectoryIterator($baseDir);
        $iterator = new \RecursiveIteratorIterator($directoryIterator);
        $files = new \RegexIterator($iterator, '/^.+\.php$/i', \RegexIterator::GET_MATCH);

        // recursive foreach .php file
        foreach($files as $file) {
            // prepare data
            $file = current($file);
            //$offsetDir = substr($file, strlen($baseDir));
            //$offsetDir = preg_replace('#/[^/]*$#', '', $offsetDir);
            //$offsetNs = preg_replace('#/#', '\\', $offsetDir);
            $ns = $baseNs;// . $offsetNs;
            $oldNs = $oldBaseNs;// . $offsetNs;


            // file_get_contents()
            $objFile = new \Cx\Lib\FileSystem\File($file);
            $content = $objFile->getData();

            // if "namespace" cannot be found, continue (non class file or legacy one)
            if (!preg_match('/namespace ' . preg_replace('/\\\\/', '\\\\\\', $oldNs) . '/', $content)) {
                continue;
            }

            // replace old NS with new NS (without leading \, be sure to match \ and \\)
            $regexDoubleBackslash = '/' . preg_quote(str_replace('\\', '\\\\', $oldNs) . '\\', '/') . '/';

            $content = preg_replace(
                $regexDoubleBackslash,
                preg_quote(str_replace('\\', '\\\\', $ns)) . '\\\\',
                $content
            );

            $content = preg_replace(
                '/' . preg_quote($oldNs . '\\', '/') . '/',
                $ns . '\\',
                $content
            );
            $objFile->write($content);
        }

        // fix namespaces in DB
        // at the moment, only log_entry stores namespaces so we can simply:
        $em = \Env::get('cx')->getDb()->getEntityManager();
        $query = $em->createQuery('SELECT FROM Cx\Core\ContentManager\Model\Entity\LogEntry l WHERE l.object_class LIKE \'' . $ns . '%\'');
        foreach ($query->getResult() as $log) {
            $object_class = $log->getObjectClass();
            $object_class = preg_replace(
                '/' . preg_quote($oldNs . '\\', '/') . '/',
                $ns . '\\',
                $object_class
            );
            $log->setObjectClass($object_class);
            $em->persist($log);
        }
        $em->flush();

        return true;
    }

    /**
     * Fix the language variables of all files of this component
     * @param string $oldBaseIndex Base language var index of old component
     * @param string $baseDir Directory in which the recursive replace should be done
     */
    public function fixLanguagePlaceholders($oldBaseIndex, $baseDir) {
        $baseIndex = strtoupper($this->componentType . '_' . $this->componentName);

        $directoryIterator = new \RecursiveDirectoryIterator($baseDir);
        $iterator = new \RecursiveIteratorIterator($directoryIterator);
        $files = new \RegexIterator($iterator, '/^.+\.(php|html|js)$/i', \RegexIterator::GET_MATCH);

        // recursive foreach .php, .html and .js file
        foreach($files as $file) {
            // prepare data
            $file = current($file);
            $bi = $baseIndex;
            $oldBi = $oldBaseIndex;


            // file_get_contents()
            $objFile = new \Cx\Lib\FileSystem\File($file);
            $content = $objFile->getData();

            $content = preg_replace(
                '/' . $oldBi . '/',
                preg_quote($bi),
                $content
            );
            echo 'Replace ' . $oldBi . ' by ' . $bi . ' in ' . $file . "\n";

            $objFile->write($content);
        }
    }

    /**
     * Fix the component names in doc blocks of all files of this component
     * @param string $oldComponentIdentifier Old lowercase, underscore separated type and nameBase
     * @param string $baseDir Directory in which the recursive replace should be done
     */
    public function fixDocBlocks($oldComponentIdentifier, $baseDir) {
        $baseIndex = strtolower($this->componentType . '_' . $this->componentName);

        $directoryIterator = new \RecursiveDirectoryIterator($baseDir);
        $iterator = new \RecursiveIteratorIterator($directoryIterator);
        $files = new \RegexIterator($iterator, '/^.+\.(php|html|js)$/i', \RegexIterator::GET_MATCH);

        // recursive foreach .php, .html and .js file
        foreach($files as $file) {
            // prepare data
            $file = current($file);
            $bi = $baseIndex;
            $oldBi = $oldComponentIdentifier;


            // file_get_contents()
            $objFile = new \Cx\Lib\FileSystem\File($file);
            $content = $objFile->getData();

            $content = preg_replace(
                '/' . $oldBi . '/',
                preg_quote($bi),
                $content
            );
            echo 'Replace ' . $oldBi . ' by ' . $bi . ' in ' . $file . "\n";

            $objFile->write($content);
        }
    }

    /**
     * Set the component's name in frontend and backend language files
     * @param string $baseDir Directory in which the recursive replace should be done
     */
    public function setComponentName($baseDir) {
        $componentNamePlaceholder = '{COMPONENT_NAME}';

        $directoryIterator = new \RecursiveDirectoryIterator($baseDir);
        $iterator = new \RecursiveIteratorIterator($directoryIterator);
        $files = new \RegexIterator($iterator, '/^.+(frontend|backend)\.php$/i', \RegexIterator::GET_MATCH);

        // recursive foreach frontend.php and backend.php file
        foreach($files as $file) {
            // prepare data
            $file = current($file);

            // file_get_contents()
            $objFile = new \Cx\Lib\FileSystem\File($file);
            $content = $objFile->getData();

            $content = preg_replace(
                '/'.preg_quote($componentNamePlaceholder).'/',
                preg_quote($this->componentName),
                $content
            );
            echo 'Replace ' . $componentNamePlaceholder . ' by ' . $this->componentName . ' in ' . $file . "\n";

            $objFile->write($content);
        }
    }

    /**
     * Relocates this component (copy or move)
     *
     * This does the following tasks
     * - Remove all DB entries for this component if moved
     * - Relocate the component in filesystem
     * - Fix namespaces of PHP class files
     * - Alter or copy pages
     * - Create DB entries for new component
     * - Activate new component
     * @todo Test copy of pages (rxqcmv1)
     * @param string $newName New component name
     * @param string $newType New component type, one of 'core', 'core_module' and 'module'
     * @param boolean $customized Copy/move to customizing folder?
     * @param boolean $copy Copy or move? True means copy, default is move
     * @return ReflectionComponent New resulting component
     */
    protected function internalRelocate($newName, $newType, $customized, $copy) {
        // create new ReflectionComponent
        $newComponent = new self($newName, $newType);

        if ($newComponent->exists()) {
            throw new SystemComponentException('The target component is already Exists. Please provide different component name or use uninstall command to remove old component..');
        }

        // move or copy pages before removing DB entries
        $em = \Env::get('cx')->getDb()->getEntityManager();
        $pageRepo = $em->getRepository('Cx\\Core\\ContentManager\\Model\\Entity\\Page');
        $pages = $pageRepo->findBy(array(
            'module' => $this->componentName,
        ));
        $migratedNodes = array();
        foreach ($pages as $page) {
            if ($copy) {
                $node =  $page->getNode();
                if (!in_array($node->getId(), $migratedNodes)) {
                   // copy the node and persist changes
                    $newNode = $node->copy();
                    $em->flush();

                    // update module name of the page
                    foreach ($newNode->getPages() as $newPage) {
                        $newPage->setModule($newName);
                        $em->persist($newPage);
                    }
                    $migratedNodes[] = $node->getId();
                }
            } else {
                $page->setModule($newName);
                $em->persist($page);
            }
        }
        $em->flush();

        $this->internalCopyData($newComponent);

        // remove old component from db (component, modules, backend_areas)
        if (!$copy) {
             $this->removeFromDb();
        }

        // copy/move in filesystem (name, type and customizing)
        $newLocation = $newComponent->getDirectory(false, $customized);
        $this->internalFsRelocate($newLocation, $copy);

        // fix namespaces
        $baseDir = ASCMS_DOCUMENT_ROOT;
        if ($copy) {
            $baseDir = $newComponent->getDirectory();
        }
        $newComponent->fixNamespaces(SystemComponent::getBaseNamespaceForType($this->componentType) . '\\' . $this->componentName, $baseDir);
        $newComponent->fixLanguagePlaceholders(strtoupper($this->componentType . '_' . $this->componentName), $baseDir);
        $newComponent->fixDocBlocks(strtolower($this->componentType . '_' . $this->componentName), $baseDir);
        // renaming the component in backend navigation does not yet work
        //$newComponent->setComponentName($baseDir);

        // add new component to db and activate it (component, modules, backend_areas, pages)
        $newComponent->activate();

        return $newComponent;
    }

    /**
     * Moves or copies the filesystem part of this component to another location
     * @param string $destination Destination path
     * @param boolean $copy (optional) Copy or move? True means copy, default is move
     * @return null
     */
    protected function internalFsRelocate($destination, $copy = false) {
        if ($destination == $this->getDirectory()) {
            // nothing to do
            return;
        }

        $status = false;
        if ($copy) {
            $status = \Cx\Lib\FileSystem\FileSystem::copy_folder($this->getDirectory(), $destination);
        } else {
            $status = \Cx\Lib\FileSystem\FileSystem::move($this->getDirectory(), $destination);
        }

        return $status;
    }

    /**
     * Copy table data's using mysql query
     *
     * @param string $table        Table name
     * @param array  $replacements Possible replacements
     * @param string $query        mysql query
     */
    protected function copyDataFromQuery($table, $replacements, $query) {
        $fields    = $this->getColumnsFromTable($table);
        $objResult = $this->db->query($query);

        if ($objResult) {
            while (!$objResult->EOF) {
                $datas = array();
                foreach ($fields as $field) {
                    $datas[$field] = $objResult->fields[$field];
                }

                $sqlQuery = $this->repalceDataInQuery($table, contrexx_raw2db($datas), $replacements);
                $this->db->query($sqlQuery);

                $objResult->MoveNext();
            }
        }
    }

    /**
     * Copy the DB data's to new component name
     *
     * @param object $newComponent Cx\Core\Core\Model\Entity\ReflectionComponent target component name
     */
    protected function internalCopyData($newComponent) {

        // copy module table
        $newModuleId = $this->db->GetOne('SELECT MAX(`id`)+1 FROM `'. DBPREFIX .'modules`');
        $table = DBPREFIX.'modules';
        $query = 'SELECT *
                    FROM
                        `'. DBPREFIX .'modules`
                    WHERE
                        `name` = "' . $this->componentName . '"';
        $replacements = array(
            'id'      => $newModuleId,
            'name'    => $newComponent->getName(),
            'is_core' => $newComponent->getType() == SystemComponent::TYPE_CORE_MODULE ? 1 : 0
        );
        $this->copyDataFromQuery($table, $replacements, $query);

        // copy component table
        $table = DBPREFIX .'component';
        $query = 'SELECT *
                        FROM
                            `'. DBPREFIX .'component`
                        WHERE
                            `name` = "' . $this->componentName . '"';
        $replacements = array(
            'id'   => NULL,
            'type' => $newComponent->getType()
        );
        $this->copyDataFromQuery($table, $replacements, $query);

        // copy backend areas
        $table = DBPREFIX .'backend_areas';
        $query = 'SELECT b.*
                    FROM
                        `'. DBPREFIX .'backend_areas` AS b
                    LEFT JOIN
                        `'. DBPREFIX .'modules` AS m
                    ON
                        m.`id` = b.`module_id`
                    WHERE
                        m.`name` = "' . $this->componentName . '"';
        $replacements = array('module_id' => $newModuleId);
        $this->copyDataFromQuery($table, $replacements, $query);


        $query = 'SELECT `key`, `text_id` FROM `'. DBPREFIX .'core_mail_template` WHERE `section` = "'. $this->componentName .'"';
        $objResult = $this->db->query($query);

        $coreMailTemplatetable = DBPREFIX .'core_mail_template';
        $coreTextTable         = DBPREFIX .'core_text';
        if ($objResult) {
            while (!$objResult->EOF) {
                $newTextId = $this->db->GetOne('SELECT MAX(`text_id`)+1 FROM `'. DBPREFIX .'core_mail_template`');

                $query = 'SELECT * FROM `'. DBPREFIX .'core_mail_template` WHERE `section` = "'. $this->componentName .'" AND `key` = "'. $objResult->fields['key'] .'"';
                $replacements = array(
                    'section' => $newComponent->getName(),
                    'text_id' => $newTextId
                );
                $this->copyDataFromQuery($coreMailTemplatetable, $replacements, $query);

                $query = 'SELECT * FROM `'. DBPREFIX .'core_text` WHERE `section` = "'. $this->componentName .'" AND `text_id` = "'. $objResult->fields['text_id'] .'"';
                $replacements = array('section' => $newComponent->getName(), 'id' => $newTextId);
                $this->copyDataFromQuery($table, $replacements, $query);

                $objResult->MoveNext();
            }
        }

        $table = DBPREFIX .'core_setting';
        $query = 'SELECT * FROM `'. DBPREFIX .'core_setting` WHERE `section` = "'. $this->componentName .'"';
        $replacements = array('section' => $newComponent->getName());
        $this->copyDataFromQuery($table, $replacements, $query);

        $table = DBPREFIX .'settings';
        $query = 'SELECT * FROM `'. DBPREFIX .'settings` WHERE `setname` LIKE "'. $this->componentName .'%"';
        $replacements = array('section' => $newComponent->getName());
        $this->copyDataFromQuery($table, $replacements, $query);

        $componentTables = $this->getComponentTables();
        foreach ($componentTables as $table) {
            $newTable = preg_replace('/(\w)'. $this->componentType .'_'. strtolower($this->componentName) .'_(\w)/', '$1'. $newComponent->getType() .'_'. strtolower($newComponent->getName()) .'_$2', $table);
            $query = 'CREATE TABLE '. $newTable .' LIKE '. $table ;
            $this->db->query($query);
            $query = 'INSERT '. $newTable .' SELECT * FROM '. $table;
            $this->db->query($query);
        }

    }
}
