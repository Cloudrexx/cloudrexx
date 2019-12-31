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

namespace Cx\Core_Modules\Workbench\Model\Entity;

/**
 * ReflectionComponentException
 *
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_core
 * @version     3.1.0
 */
class ReflectionComponentException extends \Cx\Core\Core\Model\Entity\ReflectionComponentException {}

/**
 * Represents an abstraction of a component
 *
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_core
 * @version     3.1.0
 */
class ReflectionComponent extends \Cx\Core\Core\Model\Entity\ReflectionComponent {

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
        $filesystem->make_folder(ASCMS_APP_CACHE_FOLDER . '/DLC_FILES'. \Cx\Core\Core\Model\Entity\SystemComponent::getPathForType($this->componentType), true);

        $cacheComponentFolderPath = ASCMS_APP_CACHE_FOLDER . '/DLC_FILES'. \Cx\Core\Core\Model\Entity\SystemComponent::getPathForType($this->componentType) . '/' . $this->componentName;
        $cacheComponentFolderWebPath = ASCMS_APP_CACHE_FOLDER_WEB_PATH . '/DLC_FILES'. \Cx\Core\Core\Model\Entity\SystemComponent::getPathForType($this->componentType) . '/' . $this->componentName;
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
            $meta = $yaml->parse($content);
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

        echo 'Exporting component to "' . $path . '" ... ';
        // Compress
        $file = new \PclZip($path);
        $file->create(ASCMS_APP_CACHE_FOLDER, PCLZIP_OPT_REMOVE_PATH, ASCMS_APP_CACHE_FOLDER);
        echo "Done \n";
    }

    /**
     * Write db structure and data into a file
     *
     * @global type $_DBCONFIG
     */
    protected function writeDatabaseStructureAndData()
    {
        $componentTables = $this->getComponentTables();

        $dataFolder = ASCMS_APP_CACHE_FOLDER . '/DLC_FILES'. \Cx\Core\Core\Model\Entity\SystemComponent::getPathForType($this->componentType) . '/' . $this->componentName . '/Data';
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
    protected function writeTableDataToFile($arrayTables, $path)
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

            $objFile->append("-- Core Setting".PHP_EOL);
            $table = DBPREFIX .'core_setting';
            $query = 'SELECT * FROM `'. DBPREFIX .'core_setting` WHERE `section` = "'. $this->componentName .'"';
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
    protected function writeTableDataToFileFromQuery($table, $query, $objFile)
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
    protected function getColumnsFromTable($tableName)
    {
        $fields = array();

        $objColumns = $this->db->query('SHOW COLUMNS FROM `' . $tableName . '`');
        while (!$objColumns->EOF) {
            $fields[] = $objColumns->fields['Field'];
            $objColumns->MoveNext();
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
    protected function writeTableStructureToFile($arrayTables, $path)
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
     * @access protected
     * @return boolean|string return false when table not exists or return table schema
     */
    protected function getTableStructure($table)
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
     * @access protected
     * @return boolean|string return false when table not exists or return table schema
     */
    protected function getSqlKeysTable($table)
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
    protected function writeMetaDataToFile($file)
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
            throw new \Cx\Core\Core\Model\Entity\SystemComponentException('Component is already Exists');
        }

        // copy skeleton component
        \Cx\Lib\FileSystem\FileSystem::copy_folder(
            ASCMS_CORE_MODULE_PATH.'/Workbench/Data/Skeleton',
            $this->getDirectory(false)
        );

        $this->fixNamespaces('Cx\Modules\Skeleton', $this->getDirectory());
        $this->fixLanguagePlaceholders('MODULE_SKELETON', $this->getDirectory());
        $this->fixDocBlocks('modules_skeleton', $this->getDirectory());
        $this->setComponentName($this->getDirectory());

        // activate component
        $this->activate();
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
        $componentNs = \Cx\Core\Core\Model\Entity\SystemComponent::getBaseNamespaceForType($this->componentType) . '\\' . $this->componentName;
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
        $baseNs = \Cx\Core\Core\Model\Entity\SystemComponent::getBaseNamespaceForType($this->componentType) . '\\' . $this->componentName;
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
        $qb = $em->createQueryBuilder();
        $qb->select('l')
                ->from('Cx\Core\ContentManager\Model\Entity\LogEntry', 'l')
                ->where('l.objectClass LIKE :objectClass')
                ->setParameter('objectClass', $ns . '%');
        foreach ($qb->getQuery()->getResult() as $log) {
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
            throw new \Cx\Core\Core\Model\Entity\SystemComponentException('The target component is already Exists. Please provide different component name or use uninstall command to remove old component..');
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
            'is_core' => $newComponent->getType() == \Cx\Core\Core\Model\Entity\SystemComponent::TYPE_CORE_MODULE ? 1 : 0
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
