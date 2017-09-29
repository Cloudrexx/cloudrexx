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
 * Class UpdateController
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_update
 */

namespace Cx\Core_Modules\Update\Controller;

/**
 * Class UpdateController
 *
 * The main Update component
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_update
 */
class UpdateController extends \Cx\Core\Core\Model\Entity\Controller {


    /**
     * pending codeBase changes yml
     * @var string $pendingCodeBaseChangesYml
     */
    protected $pendingCodeBaseChangesYml = 'PendingCodeBaseChanges.yml';

    /**
     * Command Line Interface
     * @var \Symfony\Component\Console\Application | null
     */
    protected $cli = null;

    /**
     * Calculate database delta
     *
     * It will calculate which codeBase database update scripts need to be executed.
     * If the original version number is smaller than new version number,
     * Add each version between those two versions to the delta as non-rollback updates otherwise delta as rollback updates
     *
     * @param integer $oldVersion
     * @param string  $codeBasePath
     */
    public function calculateDbDelta($oldVersion, $codeBasePath) {
        \Cx\Core\Setting\Controller\Setting::init('Config', '', 'Yaml');

        $latestVersion = str_replace('.', '', \Cx\Core\Setting\Controller\Setting::getValue('coreCmsVersion', 'Config'));
        $olderVersion = str_replace('.', '', $oldVersion);

        $versionClassPath = $codeBasePath . '/core_modules/Update/Data/Migrations';
        $versionFiles = array_diff(scandir($versionClassPath), array('..', '.'));


        $versions = array();
        foreach ($versionFiles as $versionFile) {
            $versions[] = substr(str_replace('.php', '', $versionFile), 7);
        }

        $i = 1;
        $isHigherVersion = $olderVersion < $latestVersion;
        if (!$isHigherVersion) {
            rsort($versions);
        }
        foreach ($versions as $version) {
            if (    (   $isHigherVersion
                    &&  (   $version > $olderVersion
                        &&  $version <= $latestVersion
                        )
                    )
                ||
                    (   !$isHigherVersion
                    &&  (   $version <= $olderVersion
                        &&  $version > $latestVersion
                        )
                    )
            ) {
                $delta = new \Cx\Core_Modules\Update\Model\Entity\Delta();
                $rollBack = !$isHigherVersion ? true : false;
                $delta->addCodeBase($version, $rollBack, $i);
                $this->registerDbUpdateHooks($delta);
                $i++;
            }
        }
    }

    /**
     * Register DB Update hooks
     *
     * This saves the calculated delta to /tmp/Update/$pendingCodeBaseChangesYml.
     * It contains a serialized Delta.
     *
     * @staticvar object $deltaRepo
     * @param \Cx\Core_Modules\Update\Model\Entity\Delta $delta
     */
    public function registerDbUpdateHooks(\Cx\Core_Modules\Update\Model\Entity\Delta $delta) {
        static $deltaRepo = null;
        if (!isset($deltaRepo)) {
            $deltaRepo = new \Cx\Core_Modules\Update\Model\Repository\DeltaRepository();
        }

        $deltaRepo->add($delta);
        $deltaRepo->flush();
    }

    /**
     * Get the serialized Delta
     *
     * This loads the serialized Delta and calls applyNext() on it
     * until returns false.
     *
     * @return null
     */
    public function applyDelta() {

        //Check if any of the update process is interrupt state, then rollback to old state
        $deltaRepository = new \Cx\Core_Modules\Update\Model\Repository\DeltaRepository();
        $deltas = $deltaRepository->findAll();
        if (empty($deltas)) {
            return;
        }
        asort($deltas);
        //set the website as Offline mode
        \Cx\Core\Setting\Controller\Setting::init('MultiSite', '', 'FileSystem');
        \Cx\Core\Setting\Controller\Setting::set('websiteState', \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_OFFLINE);
        \Cx\Core\Setting\Controller\Setting::update('websiteState');

        $status     = true;
        $yamlFile   = null;
        foreach ($deltas as $delta) {
            $status = $delta->applyNext();
            $delta->setRollback($delta->getRollback() ? false : true);
            $deltaRepository->flush();
            if (!$status) {
                //Rollback to old state
                $this->rollBackDelta();
                //Rollback the codebase changes(settings.php, configuration.php and website codebase in manager and service)
                $yamlFile = $this->cx->getWebsiteTempPath() . '/Update/'. $this->pendingCodeBaseChangesYml;
                if (file_exists($yamlFile)) {
                    $pendingCodeBaseChanges = $this->getUpdateWebsiteDetailsFromYml($yamlFile);
                    $oldCodeBase            = $pendingCodeBaseChanges['PendingCodeBaseChanges']['oldCodeBaseId'];
                    $latestCodeBase         = $pendingCodeBaseChanges['PendingCodeBaseChanges']['latestCodeBaseId'];
                    //Register YamlSettingEventListener
                    \Cx\Core\Config\Controller\ComponentController::registerYamlSettingEventListener($this->cx);
                    //Update codeBase in website
                    $this->updateCodeBase($latestCodeBase, null, $oldCodeBase);
                    //Update website codebase in manager and service
                    \Cx\Core\Setting\Controller\Setting::init('MultiSite', '', 'FileSystem');
                    $websiteName = \Cx\Core\Setting\Controller\Setting::getValue('websiteName', 'MultiSite');
                    $params = array('websiteName' => $websiteName, 'codeBase' => $oldCodeBase);
                    \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnMyServiceServer('updateWebsiteCodeBase', $params);
                }
                break;
            }
        }

        //Remove the folder '/tmp/Update', After the completion of rollback or Non-rollback process
        $tmpUpdateFolderPath = \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteTempPath() . '/Update';
        if (file_exists($tmpUpdateFolderPath)) {
            \Cx\Lib\FileSystem\FileSystem::delete_folder($tmpUpdateFolderPath, true);
        }

        //set the website back to Online mode
        \Cx\Core\Setting\Controller\Setting::set('websiteState', \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_ONLINE);
        \Cx\Core\Setting\Controller\Setting::update('websiteState');
    }

    /**
     * Rollback the delta to make the website as old state
     */
    protected function rollBackDelta() {
        $deltaRepository = new \Cx\Core_Modules\Update\Model\Repository\DeltaRepository();
        $rollBackDeltas = $deltaRepository->findBy(array('rollback' => true));
        rsort($rollBackDeltas);
        foreach ($rollBackDeltas as $rollBackDelta) {
            if (!$rollBackDelta->applyNext()) {
                $websiteName = \Cx\Core\Setting\Controller\Setting::getValue('websiteName', 'MultiSite');
                $params = array('websiteName' => $websiteName, 'emailTemplateKey' => 'notification_update_error_email');
                \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnMyServiceServer('sendUpdateNotification', $params);
                break;
            }
        }
    }

    /**
     * Update CodeBase
     *
     * @param string $newCodeBaseVersion   latest codeBase version
     * @param string $installationRootPath installationRoot path
     * @param string $oldCodeBaseVersion   old codeBase version
     */
    public function updateCodeBase($newCodeBaseVersion, $installationRootPath, $oldCodeBaseVersion = '')
    {
        //change installation root
        $objConfigData = new \Cx\Lib\FileSystem\File(\Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteConfigPath() . '/configuration.php');
        $configData    = $objConfigData->getData();
        if (!\FWValidator::isEmpty($oldCodeBaseVersion)) {
            $matches = array();
            preg_match('/\\$_PATHCONFIG\\[\'ascms_installation_root\'\\] = \'(.*?)\';/', $configData , $matches);
            $installationRootPath = str_replace($newCodeBaseVersion, $oldCodeBaseVersion, $matches[1] );
            $newCodeBaseVersion   = $oldCodeBaseVersion;
        }

        $newConfigData = preg_replace('/\\$_PATHCONFIG\\[\'ascms_installation_root\'\\] = \'.*?\';/', '$_PATHCONFIG[\'ascms_installation_root\'] = \'' . $installationRootPath . '\';', $configData);

        $objConfigData->write($newConfigData);

        //change code base
        \Cx\Core\Setting\Controller\Setting::init('Config', '', 'Yaml');
        \Cx\Core\Setting\Controller\Setting::set('coreCmsVersion', $newCodeBaseVersion);
        \Cx\Core\Setting\Controller\Setting::update('coreCmsVersion');
    }

    /**
     * Get Doctrine Migration Command Line Interface
     *
     * @return \Symfony\Component\Console\Application
     */
    public function getDoctrineMigrationCli()
    {
        if ($this->cli) {
            return $this->cli;
        }

        $em = \Env::get('em');
        $conn = $em->getConnection();

        $this->cli = new \Symfony\Component\Console\Application('Doctrine Migration Command Line Interface', \Doctrine\Common\Version::VERSION);
        $this->cli->setCatchExceptions(true);
        $helperSet = $this->cli->getHelperSet();
        $helpers = array(
            'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($conn),
            'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($this->cx->getDb()->getEntityManager()),
        );
        foreach ($helpers as $name => $helper) {
            $helperSet->set($helper, $name);
        }

        //custom configuration
        $configuration = new \Doctrine\DBAL\Migrations\Configuration\Configuration($conn);
        $configuration->setName('Doctrine Migration');
        $configuration->setMigrationsNamespace('Cx\Core_Modules\Update\Data\Migrations');
        $configuration->setMigrationsTableName(DBPREFIX . 'migration_versions');
        $configuration->setMigrationsDirectory($this->cx->getCodeBaseCoreModulePath() . '/Update/Data/Migrations');
        $configuration->registerMigrationsFromDirectory($this->cx->getCodeBaseCoreModulePath() . '/Update/Data/Migrations');

        $this->cli->addCommands(array(
            // Migrations Commands
            $this->getDoctrineMigrationCommand('\Cx\Core_Modules\Update\Model\Entity\MigrationsDiffDoctrineCommand', $configuration),
            $this->getDoctrineMigrationCommand('\Doctrine\DBAL\Migrations\Tools\Console\Command\ExecuteCommand', $configuration),
            $this->getDoctrineMigrationCommand('\Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand', $configuration),
            $this->getDoctrineMigrationCommand('\Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand', $configuration),
            $this->getDoctrineMigrationCommand('\Doctrine\DBAL\Migrations\Tools\Console\Command\StatusCommand', $configuration),
            $this->getDoctrineMigrationCommand('\Doctrine\DBAL\Migrations\Tools\Console\Command\VersionCommand', $configuration),
        ));
        $this->cli->setAutoExit(false);

        return $this->cli;
    }


    /**
     * Get the doctrine migrations command as object
     *
     * @param string $migrationCommandNameSpace
     * @param object $configuration
     *
     * @return object doctrine migration command
     */
    protected function getDoctrineMigrationCommand($migrationCommandNameSpace, $configuration) {
        $migrationCommand = new $migrationCommandNameSpace();
        $migrationCommand->setMigrationConfiguration($configuration);
        return $migrationCommand;
    }

    /**
     * Store the website details into the YML file
     *
     * @param string $folderPath
     * @param string $filePath
     * @param array  $ymlContent
     *
     * @return null
     */
    public function storeUpdateWebsiteDetailsToYml($folderPath, $filePath, $ymlContent)
    {
        if (empty($folderPath) || empty($filePath)) {
            return;
        }

        try {
            if (!file_exists($folderPath)) {
                \Cx\Lib\FileSystem\FileSystem::make_folder($folderPath);
            }

            $file = new \Cx\Lib\FileSystem\File($filePath);
            $file->touch();

            $yaml = new \Symfony\Component\Yaml\Yaml();
            $file->write(
                $yaml->dump(
                        array('PendingCodeBaseChanges' => $ymlContent )
                )
            );
        } catch (\Exception $e) {
            \DBG::log($e->getMessage());
        }
    }

    /**
     * Get update websiteDetailsFromYml
     *
     * @param string $file yml file name
     *
     * @return array website details
     */
    public function getUpdateWebsiteDetailsFromYml($file) {
        if (!file_exists($file)) {
            return;
        }
        $objFile = new \Cx\Lib\FileSystem\File($file);
        $yaml = new \Symfony\Component\Yaml\Yaml();
        return $yaml->load($objFile->getData());
    }

    /**
     * getAllCodeBaseVersions
     *
     * @param string $codeBasePath codeBase path
     *
     * @return array codeBase versions
     */
    public function getAllCodeBaseVersions($codeBasePath) {
        //codebase
        $codeBaseVersions   = array();
        $codebaseScannedDir = array_values(array_diff(scandir($codeBasePath), array('..', '.')));
        foreach ($codebaseScannedDir as $value) {
            $configFile = $codeBasePath . '/' . $value . '/installer/config/config.php';
            if (file_exists($configFile)) {
                $configContents = file_get_contents($configFile);
                if (preg_match_all('/\\$_CONFIG\\[\'(.*?)\'\\]\s+\=\s+\'(.*?)\';/s', $configContents, $matches)) {
                    $configValues       = array_combine($matches[1], $matches[2]);
                    $codeBaseVersions[] = $configValues['coreCmsVersion'];
                }
            }
        }
        return $codeBaseVersions;
    }

    /**
     * Get codeBase Changes file
     *
     * @return string $pendingCodeBaseChangesYml
     */
    public function getPendingCodeBaseChangesFile() {
        return $this->pendingCodeBaseChangesYml;
    }
}
