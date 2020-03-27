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
 * Command to access doctrine command line tools
 * @author Michael Ritter <michael.ritter@comvation.com>
 */

namespace Cx\Core_Modules\Workbench\Model\Entity;

/**
 * Command to access doctrine command line tools
 * @author Michael Ritter <michael.ritter@comvation.com>
 */
class DbCommand extends Command {
    
    /**
     * Command name
     * @var string
     */
    protected $name = 'db';
    
    /**
     * Command description
     * @var string
     */
    protected $description = 'Allows access to doctrine command line tools and adds some handy shortcuts';
    
    /**
     * Command synopsis
     * @var string
     */
    protected $synopsis = 'workbench(.bat) db [
    update({component type} ({component name}))|
    cleanup|
    doctrine {doctrine syntax}
    diff {table} {column}
]

`update`    Updates the model of a component, a component type or all components (set component type and name to generate YAML files)
`cleanup`   Removes cached files
`doctrine`  Gives access to doctrine command line tools
`diff`      Shows the difference between doctrine\'s schema and the database for a specific column';
    
    /**
     * Command help text
     * @var string
     */
    protected $help = 'Gives access to doctrine command line tools and other db management commands. Use "help" to see what commands are available.';
    
    /**
     * Execute this command
     * @param array $arguments Array of commandline arguments
     */
    public function execute(array $arguments) {
        $arguments = array_slice($arguments, 1);
        
        switch ($arguments[1]) {
            // empty /tmp/workbench
            case 'cleanup':
                $this->cleanup();
                break;
            // update database for component
            case 'up':
            case 'update':
                // empty /tmp/workbench
                $this->cleanup();
                
                // prepare component filter
                $componentFilter = '';
                $componentType = '';
                $componentName = '';
                if (isset($arguments[2])) {
                    switch (strtolower($arguments[2])) {
                        case 'core':
                            $componentType = strtolower('core');
                            $componentFilter .= 'Cx\\Core\\';
                            break;
                        case 'core_module':
                        case 'core_modules':
                            $componentType = strtolower('core_module');
                            $componentFilter .= 'Cx\\Core_Modules\\';
                            break;
                        case 'module':
                        case 'modules':
                            $componentType = strtolower('module');
                            $componentFilter .= 'Cx\\Modules\\';
                            break;
                    }
                    if (isset($arguments[3])) {
                        $componentName = $arguments[3];
                        $componentFilter .= $arguments[3] . '\\';
                    }
                }
                if (!empty($componentFilter)) {
                    $componentFilter = '--filter=' . $componentFilter;
                }
                
                // check for mwb file
                if (!empty($componentType) && !empty($componentName)) {
                    $this->tryYamlGeneration($componentType, $componentName);
                }

                // Clear and disable metadata cache
                $em = $this->cx->getDb()->getEntityManager();
                $config = $em->getConfiguration();
                $config->getMetadataCacheImpl()->deleteAll();
                $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache());

                // doctrine orm:generate-entities --filter="{component filter}" entities
                $doctrineArgs = array('', 'doctrine', 'orm:generate-entities');
                if (!empty($componentFilter)) {
                    $doctrineArgs[] = $componentFilter;
                }
                $doctrineArgs[] = $this->cx->getWebsiteTempPath().'/workbench';
                if ($this->executeDoctrine($doctrineArgs) != 0) {
                    return;
                }
                
                // move entities to component directory and add .class extension
                $modelMovedCompletely = $this->moveModel($this->cx->getWebsiteTempPath().'/workbench/Cx', $this->cx->getWebsiteDocumentRootPath());
                
                // if all files could be moved, cleanup
                // if not: ask if moving should be forced (CAUTION!)
                if (!$modelMovedCompletely) {
                    echo "\r\n".'Not all entity files could be moved to their correct location. This is probably because there\'s an existing file there. ';
                    echo 'The files can be overwritten, but it is recommended, that you diff the changes manually. ';
                    if ($this->interface->yesNo('Would you like to overwrite the files?')) {
                        $modelMovedCompletely = $this->moveModel($this->cx->getWebsiteTempPath().'/workbench/Cx', $this->cx->getWebsiteDocumentRootPath(), true);
                    }
                }
                
                // doctrine orm:generate-repositories --filter="{component filter}" repositories
                $doctrineArgs = array('', 'doctrine', 'orm:generate-repositories');
                if (!empty($componentFilter)) {
                    $doctrineArgs[] = $componentFilter;
                }
                $doctrineArgs[] = $this->cx->getWebsiteTempPath().'/workbench';
                if ($this->executeDoctrine($doctrineArgs) != 0) {
                    return;
                }
                
                // move repositories to component directory and add .class extension
                $modelMovedCompletely = $modelMovedCompletely && $this->moveModel($this->cx->getWebsiteTempPath().'/workbench/Cx', $this->cx->getWebsiteDocumentRootPath());
                
                // if all files could be moved, cleanup
                // if not: ask if moving should be forced (CAUTION!)
                if (!$modelMovedCompletely) {
                    echo "\r\n".'Not all model files could be moved to their correct location. This is probably because there\'s an existing file there. ';
                    echo 'The files can be overwritten, but it is recommended, that you diff the changes manually. ';
                    if ($this->interface->yesNo('Would you like to overwrite the files?')) {
                        $modelMovedCompletely = $this->moveModel($this->cx->getWebsiteTempPath().'/workbench/Cx', $this->cx->getWebsiteDocumentRootPath(), true);
                    }
                    if (!$modelMovedCompletely) {
                        if ($this->interface->yesNo('There are remaining files in tmp/workbench. Should they be removed?')) {
                            $this->cleanup();
                        }
                    }
                } else {
                    $this->cleanup();
                }
                
                // doctrine orm:generate-proxies --filter="{component filter}" repositories
                $doctrineArgs = array('', 'doctrine', 'orm:generate-proxies');
                if (!empty($componentFilter)) {
                    $doctrineArgs[] = $componentFilter;
                }
                if ($this->executeDoctrine($doctrineArgs) != 0) {
                    return;
                }
                
                // doctrine orm:schema-tool:create --dump-sql
                // print queries and ask if those should be executed (CAUTION!)
                $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->cx->getDb()->getEntityManager());
                $metadatas = $this->cx->getDb()->getEntityManager()->getMetadataFactory()->getAllMetadata();
                $queries = $schemaTool->getUpdateSchemaSql($metadatas, true);
                if (count($queries)) {
                    foreach ($queries as $query) {
                        echo $query . "\r\n";
                    }
                    echo 'The above queries were generated for updating the database. Should these be executed on the database? ';
                    if ($this->interface->yesNo('WARNING: Please check the SQL statements carefully and create a database backup before saying yes!')) {
                        $connection = $this->cx->getDb()->getEntityManager()->getConnection();
                        $i = 0;
                        $connection->executeQuery('SET FOREIGN_KEY_CHECKS=0');
                        foreach ($queries as $query) {
                            $query = trim($query);
                            if (empty($query)) {
                                continue;
                            }
                            $connection->executeQuery($query);
                            $i++;
                        }
                        $connection->executeQuery('SET FOREIGN_KEY_CHECKS=1');
                        echo 'Wrote ' . $i . ' queries to DB'."\r\n";
                    }
                }
                // intentionally no break!
                // break

            case 'validate':
                // doctrine orm:validate-schema
                if ($this->validateSchema(isset($arguments[2]) && $arguments[2] == '-v') != 0) {
                    echo 'Your schema is not valid. Please correct this before you proceed';
                    return;
                }
                break;
            case 'doctrine':
                $this->executeDoctrine($arguments);
                break;
            case 'diff':
                $table = $this->cx->getDb()->getDb()->getName() . '.' .  DBPREFIX . $arguments[2];
                $column = $arguments[3];
                $em = $this->cx->getDb()->getEntityManager();
                $sm = $em->getConnection()->getSchemaManager();
                $fromColumn = $this->getColumnFromSchema(
                    $sm->createSchema(),
                    $table,
                    $column
                );
                $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
                $metadatas = $em->getMetadataFactory()->getAllMetadata();
                $toColumn = $this->getColumnFromSchema(
                    $schemaTool->getSchemaFromMetadata($metadatas),
                    $table,
                    $column
                );
                if (trim($fromColumn) == 'NULL' || trim($toColumn) == 'NULL') {
                    $this->interface->show(
                        'Column not found! Specify table without DBPREFIX and ' .
                        'fields as they are named in the database (example: ' .
                        '"foo_bar" instead of "fooBar" or "foobar").'
                    );
                    break;
                }
                $this->interface->diff($fromColumn, $toColumn);
                break;
            case 'help':
            default:
                echo 'Command `' . $this->getName() . "`\r\n" .
                        $this->getDescription() . "\r\n\r\n" .
                        $this->getSynopsis() . "\r\n\r\n" .
                        $this->getHelp() . "\r\n";
                break;
        }
        echo "Done\r\n";
    }

    /**
     * Returns the definition of a specific column from a schema
     * @param \Doctrine\DBAL\Schema\Schema $schema Schema to read from
     * @param string $table Name of the table to fetch (with prefixed database name and DBPREFIX)
     * @param string $column Name of the column to fetch (lowercase without any chars like '_')
     * @return string Doctrine column definition (array dump)
     */
    protected function getColumnFromSchema($schema, $table, $column) {
        $tables = $schema->getTables();
        if (!isset($tables[$table])) {
            return 'NULL';
        }
        $columns = $tables[$table]->getColumns();
        if (!isset($columns[$column])) {
            return 'NULL';
        }
        ob_start();
        var_dump($columns[$column]);
        return ob_get_clean();
    }
    
    public function executeDoctrine(array $arguments) {
        $_SERVER['argv'] = array_slice($arguments, 1);
        
        $cli = new \Symfony\Component\Console\Application('Doctrine Command Line Interface', \Doctrine\Common\Version::VERSION);
        $cli->setCatchExceptions(true);
        $helperSet = $cli->getHelperSet();
        $helpers = array(
            'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($this->cx->getDb()->getEntityManager()),
        );
        foreach ($helpers as $name => $helper) {
            $helperSet->set($helper, $name);
        }
        $cli->addCommands(array(
            // DBAL Commands
            new \Doctrine\DBAL\Tools\Console\Command\RunSqlCommand(),
            new \Doctrine\DBAL\Tools\Console\Command\ImportCommand(),

            // ORM Commands
            new \Doctrine\ORM\Tools\Console\Command\ClearCache\MetadataCommand(),
            new \Doctrine\ORM\Tools\Console\Command\ClearCache\ResultCommand(),
            new \Doctrine\ORM\Tools\Console\Command\ClearCache\QueryCommand(),
            new \Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand(),
            new \Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand(),
            new \Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand(),
            new \Doctrine\ORM\Tools\Console\Command\EnsureProductionSettingsCommand(),
            new \Doctrine\ORM\Tools\Console\Command\ConvertDoctrine1SchemaCommand(),
            new \Doctrine\ORM\Tools\Console\Command\GenerateRepositoriesCommand(),
            new \Doctrine\ORM\Tools\Console\Command\GenerateEntitiesCommand(),
            new \Doctrine\ORM\Tools\Console\Command\GenerateProxiesCommand(),
            new \Doctrine\ORM\Tools\Console\Command\ConvertMappingCommand(),
            new \Doctrine\ORM\Tools\Console\Command\RunDqlCommand(),
            new \Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand(),

        ));
        $cli->setAutoExit(false);
        return $cli->run();
    }
    
    protected function cleanup() {
        \Cx\Lib\FileSystem\FileSystem::delete_folder($this->cx->getWebsiteTempPath().'/workbench', true);
        \Cx\Lib\FileSystem\FileSystem::make_folder($this->cx->getWebsiteTempPath().'/workbench');
    }
    
    protected function moveModel($sourceFolder, $destinationFolder, $force = false) {
        if (!file_exists($sourceFolder)) {
            return true;
        }
        $sourceDirectory = new \RecursiveDirectoryIterator($sourceFolder);
        $sourceDirectoryIterator = new \RecursiveIteratorIterator($sourceDirectory);
        $sourceDirectoryRegexIterator = new \RegexIterator($sourceDirectoryIterator, '/^.+\.php$/i', \RegexIterator::GET_MATCH);
        $retVal = true;
        
        // foreach model class
        foreach ($sourceDirectoryRegexIterator as $sourceFile) {
            // move to correct location and add .class ending if necessary
            $sourceFile = current($sourceFile);
            $sourceFile = str_replace('\\', '/', $sourceFile);
            $parts = explode('/Cx/', $sourceFile);
            $destinationFile = $destinationFolder . '/' . end($parts);
            $destinationFile = preg_replace_callback(
                '#(' . $destinationFolder . '/)(Core(?:_Modules)?|Modules)#',
                function($matches) {
                    return $matches[1] . strtolower($matches[2]);
                },
                $destinationFile
            );
            $destinationFile = preg_replace('/(?!\.class)\.php$/', '.class.php', $destinationFile);
            if (!$force && file_exists($destinationFile)) {
                $retVal = false;
                continue;
            }
            try {
                $objFile = new \Cx\Lib\FileSystem\File($sourceFile);
                $objFile->move($destinationFile, $force);
            } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                throw $e;
            }
            // if the moved file is an entity class
            $contents = file_get_contents($destinationFile);
            if (strpos($destinationFile, '/Model/Entity/')) {
                // and there is no extends statement yet
                $regex = '/(class\s*(:?[a-zA-Z0-9_]*))\s*\{/m';
                if (!preg_match($regex, $contents)) {
                    continue;
                }
                // add extends statement for base entity
                $contents = preg_replace($regex, '$1 extends \\Cx\\Model\\Base\\EntityBase {', $contents);
            }
            $contents = str_replace('private', 'protected', $contents);
            file_put_contents($destinationFile, $contents);
        }
        return $retVal;
    }
    
    protected function validateSchema($verbose = false) {
        $em = $this->cx->getDb()->getEntityManager();

        $validator = new \Doctrine\ORM\Tools\SchemaValidator($em);
        $errors = $validator->validateMapping();

        $exit = 0;
        if ($errors) {
            foreach ($errors AS $className => $errorMessages) {
                echo "[Mapping]  FAIL - The entity-class '" . $className . "' mapping is invalid:\n";
                foreach ($errorMessages AS $errorMessage) {
                    echo '* ' . $errorMessage . "\n";
                }
                echo "\n";
            }
            $exit += 1;
        } else {
            echo '[Mapping]  OK - The mapping files are correct.' . "\n";
        }

        // Check if all needed methods are present
        $fail = false;
        $metaData = $em->getMetadataFactory()->getAllMetadata();
        foreach ($metaData as $classMetaData) {
            // foreach each field there needs to be a set and a get method
            // foreach *:n relation there needs to be an add method on the * side
            $className = $classMetaData->getName();
            $reflectionClass = new \ReflectionClass($className);
            $neededMethods = array();
            foreach ($classMetaData->getFieldNames() as $columnName) {
                $columnNameCC = \Doctrine\Common\Inflector\Inflector::classify(
                    $columnName
                ); 
                $neededMethods[] = 'get' . $columnNameCC;
                if (
                    !$classMetaData->isIdentifier($columnName) ||
                    !$classMetaData->isIdGeneratorIdentity()
                ) {
                    $neededMethods[] = 'set' . $columnNameCC;
                }
            }
            foreach ($classMetaData->getAssociationMappings() as $associationMapping) {
                $columnNameCC = \Doctrine\Common\Inflector\Inflector::classify(
                    $associationMapping['fieldName']
                ); 
                switch ($associationMapping['type']) {
                    case \Doctrine\ORM\Mapping\ClassMetadataInfo::ONE_TO_ONE:
                    case \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE:
                        // check set and get
                        $neededMethods[] = 'get' . $columnNameCC;
                        $neededMethods[] = 'set' . $columnNameCC;
                        break;
                    case \Doctrine\ORM\Mapping\ClassMetadataInfo::ONE_TO_MANY:
                    case \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_MANY:
                        // check set, get and add
                        $columnNameCCSingle = \Doctrine\Common\Inflector\Inflector::singularize(
                            $columnNameCC
                        );
                        $neededMethods[] = 'get' . $columnNameCC;
                        if ($verbose) {
                            if ($columnNameCCSingle != $columnNameCC && $reflectionClass->hasMethod('set' . $columnNameCC)) {
                                $this->interface->show('[Mapping]  INFO - Unused method "' . $className . '->set' . $columnNameCC . '()".');
                            }
                            if ($reflectionClass->hasMethod('set' . $columnNameCCSingle)) {
                                $this->interface->show('[Mapping]  INFO - Unused method "' . $className . '->set' . $columnNameCCSingle . '()".');
                            }
                            if ($reflectionClass->hasMethod('add' . $columnNameCC)) {
                                $this->interface->show('[Mapping]  INFO - Unused method "' . $className . '->add' . $columnNameCC . '()".');
                            }
                        }
                        $neededMethods[] = 'remove' . $columnNameCCSingle;
                        $neededMethods[] = 'add' . $columnNameCCSingle;
                        break;
                    default:
                        var_dump($associationMapping);die();
                        break;
                }
            }
            foreach ($neededMethods as $neededMethod) {
                if (!$reflectionClass->hasMethod($neededMethod)) {
                    $this->interface->show('[Mapping]  FAIL - The entity-method "' . $className . '->' . $neededMethod . '()" is missing.');
                    $fail = true;
                }
            }
        }
        if (!$fail) {
            $this->interface->show('[Mapping]  OK - All necessary methods are present.');
        } else {
            $exit += 4;
        }

        if (!$validator->schemaInSyncWithMetadata()) {
            echo '[Database] FAIL - The database schema is not in sync with the current mapping file.' . "\n";
            $exit += 2;
        } else {
            echo '[Database] OK - The database schema is in sync with the mapping files.' . "\n";
        }
        return $exit;
    }
    
    protected function tryYamlGeneration($componentType, $componentName) {
        $component = new ReflectionComponent($componentName, $componentType);
        if (!$component->exists()) {
            return;
        }
        if (!file_exists($component->getDirectory() . '/Doc')) {
            return;
        }
        $dir = new \RecursiveDirectoryIterator($component->getDirectory() . '/Doc');
        $iterator = new \RecursiveIteratorIterator($dir);
        $regex = new \RegexIterator($iterator, '/^.+\.mwb$/i', \RecursiveRegexIterator::GET_MATCH);
        $mwbFiles = array();
        foreach ($regex as $file) {
            $mwbFiles[] = $file[0];
        }
        spl_autoload_register(array($this, 'mwbExporterAutoload'));
        while (true) {
            if (!count($mwbFiles)) {
                return;
            }
            $this->interface->show('The component has the following MySQL Workbench files:');
            foreach ($mwbFiles as $index=>$file) {
                $fileParts = explode('/', $file);
                $this->interface->show(($index + 1) . ' - ' . end($fileParts));
            }
            $retVal = trim($this->interface->input('Enter the file\'s number in order to generate YAML files for it:'));
            if (empty($retVal) || !isset($mwbFiles[$retVal - 1])) {
                return;
            }
            $mwbFile = $mwbFiles[$retVal - 1];
            $this->generateYamlFromMySqlWorkbenchFile($mwbFile);
            unset($mwbFiles[$retVal - 1]);
            // Refresh metadata
            $yamlDir = $this->cx->getClassLoader()->getFilePath($component->getDirectory(false).'/Model/Yaml');
            if (file_exists($yamlDir)) {
                $this->cx->getDb()->addSchemaFileDirectories(array($yamlDir));
            }
        }
    }

    /**
     * Generate yaml files to component's yaml directory based on file $mwbFile
     *
     * @param string $mwbFile Path to mysql workbench file
     */
    protected function generateYamlFromMySqlWorkbenchFile($mwbFile)
    {
        $setup = array(
            Doctrine2YamlFormatter::CFG_ADD_COMMENT          => false,
            Doctrine2YamlFormatter::CFG_USE_LOGGED_STORAGE   => true,
            Doctrine2YamlFormatter::CFG_INDENTATION          => 2,
            Doctrine2YamlFormatter::CFG_FILENAME             => '%entity-namespace%.%entity%.dcm.%extension%',
            Doctrine2YamlFormatter::CFG_AUTOMATIC_REPOSITORY => true,
            Doctrine2YamlFormatter::CFG_BACKUP_FILE          => false,
        );

        try {
            $tempPath  = $this->cx->getWebsiteTempPath() . '/workbench';
            $outputDir = $tempPath . '/yaml';
            $bootstrap = new \MwbExporter\Bootstrap();
            $formatter = new Doctrine2YamlFormatter('doctrine2-yaml');
            $formatter->setup($setup);
            $bootstrap->export($formatter, $mwbFile, $outputDir, 'file');

            //Move the generated yaml file from tmp to corresponding component
            $this->moveYamlFilesToComponent($tempPath, $outputDir);
        } catch (\Exception $e) {
            \DBG::log($e->getMessage());
        }
    }

    /**
     * Backup the yaml file
     *
     * @param string $sourceDir source directory
     * @param string $destDir   destination directory
     * @param string $fileName  name of the file
     *
     * @return boolean
     */
    public function backupYamlFile($sourceDir, $destDir, $fileName)
    {
        if (empty($sourceDir) || empty($destDir) || empty($fileName)) {
            return false;
        }

        //Check the destination folder exits or not
        if (!\Cx\Lib\FileSystem\FileSystem::exists($destDir)) {
            if (
                !\Cx\Lib\FileSystem\FileSystem::make_folder(
                    $destDir,
                    true
                )
            ) {
                return false;
            }
        }

        //backup the yml file
        try {
            $objFile = new \Cx\Lib\FileSystem\File($sourceDir . '/' . $fileName);
            $objFile->move($destDir . '/' . $fileName, true);
        } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
            return false;
        }

        return true;
    }

    /**
     * Move the created Yaml files into corresponding component
     *
     * @param string $tempWorkbenchPath workbench tmp path
     * @param string $ymlFilePath       yml file path
     *
     * @return null
     */
    public function moveYamlFilesToComponent($tempWorkbenchPath, $ymlFilePath)
    {
        if (!\Cx\Lib\FileSystem\FileSystem::exists($ymlFilePath)) {
            $this->interface->show('Unable to create YAML files.');
            return;
        }

        $first         = true;
        $backupFile    = false;
        $components    = array();
        $errorFiles    = array();
        $em            = $this->cx->getDb()->getEntityManager();
        $componentRepo = $em->getRepository(
            '\Cx\Core\Core\Model\Entity\SystemComponent'
        );
        foreach (glob($ymlFilePath . '/*.yml') as $yamlFile) {
            $fileName  = basename($yamlFile);
            $fileParts = explode('.', $fileName);
            if (count($fileParts) != 8) {
                $errorFiles[] = $fileName;
                continue;
            }
            if (!isset($components[$fileParts[2]])) {
                $components[$fileParts[2]] = $componentRepo->findOneBy(
                    array('name' => $fileParts[2])
                );
                if (!$components[$fileParts[2]]) {
                    $this->interface->show('Component "' . $fileParts[2] . '" not found! Did you name your tables correctly?');
                    $errorFiles[] = $fileName;
                    continue;
                }
            }
            $filePath = $components[$fileParts[2]]->getDirectory() . '/Model/Yaml';
            if (!\Cx\Lib\FileSystem\FileSystem::exists($filePath)) {
                if (
                    !\Cx\Lib\FileSystem\FileSystem::make_folder(
                        $filePath,
                        true
                    )
                ) {
                    $errorFiles[] = $fileName;
                    continue;
                }
            }

            $isFileAlreadyExists = \Cx\Lib\FileSystem\FileSystem::exists(
                $filePath . '/' . $fileName
            );
            if ($first && $isFileAlreadyExists) {
                $first      = false;
                $backupFile = $this->interface->yesNo(
                    'Do you want to backup the existing YAML files?'
                );
            }

            if ($isFileAlreadyExists && $backupFile) {
                $destDir = $tempWorkbenchPath . '/yamlBackup/' .
                    $components[$fileParts[2]]->getName() . '/Model/Yaml';
                if (!$this->backupYamlFile($filePath, $destDir, $fileName)) {
                    $this->interface->show(
                        'Unable to backup the YAML files.'
                    );
                    return;
                }
            }

            try {
                $objFile = new \Cx\Lib\FileSystem\File($ymlFilePath . '/' . $fileName);
                $objFile->move($filePath . '/' . $fileName, true);
            } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                $errorFiles[] = $fileName;
            }
        }

        if (empty($errorFiles)) {
            $this->interface->show('YAML files created successfully.');
            if ($backupFile) {
                $this->interface->show(
                    'The files have been backed-up to ' .
                    $tempWorkbenchPath . '/yamlBackup' . '.'
                );
            }
            return;
        }

        $errorText = count($errorFiles) > 1
            ? "Unable to create the following yml files: \r\n"
            : "Unable to create the yml file ";
        $this->interface->show($errorText . implode("\r\n", $errorFiles));
    }

    /**
     * Auto load register to load lib
     *
     * @param string $class Class name
     */
    public function mwbExporterAutoload($class) {
        if (strpos($class, 'MwbExporter') === 0) {
           $file     = strtr($class, '\\', DIRECTORY_SEPARATOR) . '.php';
           $filePath = $this->cx->getCodeBaseCoreModulePath() . '/Workbench/Lib/'. $file;
           if (file_exists($filePath)) {
               require_once $filePath;
           }
        }
    }
}

