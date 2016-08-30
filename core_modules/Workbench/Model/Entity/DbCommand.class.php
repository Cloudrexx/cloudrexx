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
]';

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
                if (isset($arguments[2])) {
                    switch (strtolower($arguments[2])) {
                        case 'core':
                            $componentFilter .= 'Cx\\Core\\';
                            break;
                        case 'core_module':
                        case 'core_modules':
                            $componentFilter .= 'Cx\\Core_Modules\\';
                            break;
                        case 'module':
                        case 'modules':
                            $componentFilter .= 'Cx\\Modules\\';
                            break;
                    }
                    if (isset($arguments[3])) {
                        $componentFilter .= $arguments[3] . '\\';
                    }
                }
                if (!empty($componentFilter)) {
                    $componentFilter = '--filter=' . $componentFilter;
                }

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
                    echo 'I can overwrite these files for you, but it is recommended, that you diff the changes manually. ';
                    if ($this->interface->yesNo('Would you like me to overwrite the files?')) {
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
                    echo 'I can overwrite these files for you, but it is recommended, that you diff the changes manually. ';
                    if ($this->interface->yesNo('Would you like me to overwrite the files?')) {
                        $modelMovedCompletely = $this->moveModel($this->cx->getWebsiteTempPath().'/workbench/Cx', $this->cx->getWebsiteDocumentRootPath(), true);
                    }
                    if (!$modelMovedCompletely) {
                        if ($this->interface->yesNo('There are remaining files in tmp/workbench. Should I remove them?')) {
                            $this->cleanup();
                        }
                    }
                } else {
                    $this->cleanup();
                }

                // doctrine orm:schema-tool:create --dump-sql
                // print queries and ask if those should be executed (CAUTION!)
                $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->cx->getDb()->getEntityManager());
                $metadatas = $this->cx->getDb()->getEntityManager()->getMetadataFactory()->getAllMetadata();
                $queries = $schemaTool->getUpdateSchemaSql($metadatas);
                foreach ($queries as $query) {
                    echo $query . "\r\n";
                }
                echo 'The above queries were generated for updating the database. Should I execute them on the database? ';
                if ($this->interface->yesNo('WARNING: Please check the SQL statements carefully and create a database backup before saying yes!')) {
                    $connection = $this->cx->getDb()->getEntityManager()->getConnection();
                    $i = 0;
                    foreach ($queries as $query) {
                        $query = trim($query);
                        if (empty($query)) {
                            continue;
                        }
                        $connection->executeQuery($query);
                        $i++;
                    }
                    echo 'Wrote ' . $i . ' queries to DB'."\r\n";
                }

                // doctrine orm:validate-schema
                $this->validateSchema();
                if ($this->validateSchema() != 0) {
                    echo 'Your schema is not valid. Please correct this in before you proceed';
                    return;
                }
                break;
            case 'doctrine':
                $this->executeDoctrine($arguments);
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
            if (strpos($destinationFile, '/Model/Entity/')) {
                $contents = file_get_contents($destinationFile);
                // and there is no extends statement yet
                $regex = '/(class\s*(:?[a-zA-Z0-9_]*))\s*\{/m';
                if (!preg_match($regex, $contents)) {
                    return $retVal;
                }
                // add extends statement for base entity
                $contents = preg_replace($regex, '$1 extends \\Cx\\Model\\Base\\EntityBase {', $contents);
                file_put_contents($destinationFile, $contents);
            }
        }
        return $retVal;
    }

    protected function validateSchema() {
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

        if (!$validator->schemaInSyncWithMetadata()) {
            echo '[Database] FAIL - The database schema is not in sync with the current mapping file.' . "\n";
            $exit += 2;
        } else {
            echo '[Database] OK - The database schema is in sync with the mapping files.' . "\n";
        }
        return $exit;
    }
}
