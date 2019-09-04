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
 * Main script for Cloudrexx
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_core
 * @link        http://www.cloudrexx.com/ cloudrexx homepage
 * @since       v3.1.0
 */

namespace {

    /* STAGE 1: init.php and calling new \Cx\Core\Core\Controller\Cx */

    /**
     * Wrapper for new \Cx\Core\Core\Controller\Cx()
     *
     * This is necessary, because we cannot use namespaces in index.php
     * in order to catch errors with PHP versions prior to 5.3
     * @param string $mode (optional) One of 'frontend', 'backend', 'cli', 'minimal'
     * @return \Cx\Core\Core\Controller\Cx Instance of Cloudrexx
     */
    function init($mode = null, $checkInstallationStatus = true) {
        return \Cx\Core\Core\Controller\Cx::instanciate($mode,  false, null, false, $checkInstallationStatus);
    }
}

namespace Cx\Core\Core\Controller {
    /**
     * This Exception can be used to abort the execution of the Cx object
     */
    class InstanceException extends \Exception {}

    /**
     * This loads and controls everything
     * @copyright   CLOUDREXX CMS - CLOUDREXX AG
     * @author      Michael Ritter <michael.ritter@comvation.com>
     * @package     cloudrexx
     * @subpackage  core
     * @link        http://www.cloudrexx.com/ cloudrexx homepage
     * @since       v3.1.0
     * @todo Remove all instances of "global" or at least move them to a single place
     */
    class Cx {
        const FOLDER_NAME_IMAGES = '/images';
        const FOLDER_NAME_MEDIA = '/media';
        /**
         * Commandline interface mode
         *
         * In this mode, Cloudrexx is initialized for commandline usage
         * This mode is BETA at this time
         */
        const MODE_COMMAND = 'command';

        /**
         * Frontend mode
         *
         * In this mode, Cloudrexx shows the frontend
         */
        const MODE_FRONTEND = 'frontend';

        /**
         * Backend mode
         *
         * In this mode, Cloudrexx show the administrative backend
         */
        const MODE_BACKEND = 'backend';

        /**
         * Minimal mode
         *
         * In this mode, the whole environment is loaded, but the
         * main template will not be initialized, no component hooks
         * will be executed and the template will not be parsed
         * This mode is BETA at this time
         */
        const MODE_MINIMAL = 'minimal';

        /**
         * Holds references to all currently loaded Cx instances
         *
         * The first one is the normally used one, all others are special.
         * This is a two dimensional array. The first level key is the
         * configuration file path. Each of these entries contains a list of
         * all Cloudrexx instances for this config file.
         * @var array
         */
        protected static $instances = array();

        /**
         * Holds the reference to preferred instance of this class
         *
         * Normally this is the first instance. In special environments (an
         * example would be the environment for the MultiSite component) this
         * may be a different instance.
         * @var Cx
         */
        protected static $preferredInstance = null;

        /**
         * Parsing star time
         * @var array Array in the form array({milliseconds}, {seconds})
         */
        protected $startTime = array();

        /**
         * System mode
         * @var string Mode as string (see constants)
         */
        protected $mode = null;

        /**
         * Main template
         * @var \Cx\Core\Html\Sigma
         */
        protected $template = null;

        /**
         * Database connection handler
         * @var \Cx\Core\Model\Db
         */
        protected $db = null;

        /**
         * Request URL
         * @var \Cx\Core\Routing\Model\Entity\Request
         */
        protected $request = null;

        /**
         * Response object
         * @var \Cx\Core\Routing\Model\Entity\Response
         */
        protected $response = null;

        /**
         * Component handler
         * @var \Cx\Core\Core\Controller\ComponentHandler
         */
        protected $ch = null;

        /**
         * Class auto loader
         * @var \Cx\Core\ClassLoader\ClassLoader
         */
        protected $cl = null;

        /**
         * If null, customizing is deactivated
         * @var string
         */
        protected $customizingPath = null;

        /**
         * If null, page is not resolved yet
         * @var \Cx\Core\ContentManager\Model\Entity\Page
         */
        protected $resolvedPage = null;

        /**
         * Resolver used for page resolving (for the moment frontend mode only)
         * @var \Cx\Core\Routing\Resolver
         */
        protected $resolver = null;

        /**
         * List of available commands in command mode. Key is
         * command name, value is the responsible component.
         * This will be null for all modes except command mode.
         * @var array
         */
        protected $commands = array();

        /**
         * Current language id
         * @var int
         */
        protected $langId = null;

        /**
         * License for this instance
         * @var \Cx\Core_Modules\License\License
         */
        protected $license = null;

        /**
         * Cloudrexx toolbox
         * @todo Update FWSystem
         * @var \FWSystem
         */
        protected $toolbox = null;

        /**
         * Cloudrexx event manager
         * @var \Cx\Core\Event\Controller\EventManager
         */
        protected $eventManager = null;

        /**
         * The folder name of the storage location of the config files (/config).
         * @var string
         */
        const FOLDER_NAME_CONFIG = '/config';

        /**
         * The folder name of the storage location of the core components (/core).
         * Formerly known as ASCMS_CORE_FOLDER.
         * @var string
         */
        const FOLDER_NAME_CORE = '/core';

        /**
         * The folder name used for the temp storage location (/tmp).
         * @var string
         */
        const FOLDER_NAME_TEMP = '/tmp';

        /**
         * The folder name used for the cache storage location in temp (/cache).
         * @var string
         */
        const FOLDER_NAME_CACHE = '/cache';

        /**
         * The folder name used to access the backend of the website (/cadmin).
         * Formerly known as ASCMS_BACKEND_PATH
         * @var string
         */
        const FOLDER_NAME_BACKEND = '/cadmin';

        /**
         * The folder name used to access the command mode of the website (/api).
         * @var string
         */
        const FOLDER_NAME_COMMAND_MODE = '/api';

        /**
         * The folder name used for the customizing storage location (/customizing).
         * @var string
         */
        const FOLDER_NAME_CUSTOMIZING = '/customizing';

        /**
         * The folder name used for the core_modules storage location (/core_modules).
         * Formerly known as ASCMS_CORE_MODULE_FOLDER
         * @var string
         */
        const FOLDER_NAME_CORE_MODULE = '/core_modules';

        /**
         * The folder name used for the lib storage location (/lib).
         * Formerly known as ASCMS_LIBRARY_FOLDER
         * @var string
         */
        const FOLDER_NAME_LIBRARY = '/lib';

        /**
         * The folder name used for the model storage location (/model).
         * Formerly known as ASCMS_MODEL_FOLDER
         * @var string
         */
        const FOLDER_NAME_MODEL = '/model';

        /**
         * The folder name used for the modules storage location (/modules).
         * Formerly known as ASCMS_MODULE_FOLDER
         * @var string
         */
        const FOLDER_NAME_MODULE = '/modules';

        /**
         * The folder name used for the themes (/themes).
         * @var string
         */
        const FOLDER_NAME_THEMES = '/themes';

        /**
         * The folder name used for the application feeds (/feed).
         * @var string
         */
        const FOLDER_NAME_FEED = '/feed';

        /**
         * @var string
         */
        const FOLDER_NAME_PUBLIC_TEMP = '/public';

        /**
         * The webserver's DocumentRoot path.
         * Formerly known as ASCMS_PATH.
         * @var string
         */
        protected $codeBasePath = null;

        /**
         * The offset path from the webserver's DocumentRoot to the
         * location of the Code Base of the Cloudrexx installation.
         * Formerly known as ASCMS_PATH_OFFSET.
         * @var string
         */
        protected $codeBaseOffsetPath = null;

        /**
         * The absolute path to the Code Base of the Cloudrexx installation.
         * Formerly known as ASCMS_DOCUMENT_ROOT.
         * @var string
         */
        protected $codeBaseDocumentRootPath = null;

        /**
         * The absolute path to the storage location of the
         * configuration files (/config) of the Code Base of the
         * Cloudrexx installation.
         * @var string
         */
        protected $codeBaseConfigPath = null;

        /**
         * The absolute path to the core components (/core)
         * of the Code Base of the Cloudrexx installation.
         * Formerly known as ASCMS_CORE_PATH.
         * @var string
         */
        protected $codeBaseCorePath = null;

        /**
         * The offset path to the core components (/core)
         * of the Code Base of the Cloudrexx installation.
         * @var string
         */
        protected $codeBaseCoreWebPath = null;

        /**
         * The absolute path used to access the backend template
         * of the Code Base of the Cloudrexx installation
         * Formerly known as ASCMS_ADMIN_TEMPLATE_PATH
         * @var string
         */
        protected $codeBaseAdminTemplatePath = null;

        /**
         * The offset path used to access the backend template
         * of the Code Base of the Cloudrexx installation.
         * Formerly known as ASCMS_ADMIN_TEMPLATE_WEB_PATH.
         * @var string
         */
        protected $codeBaseAdminTemplateWebPath = null;

        /**
         * The absolute path of the core modules(core_modules) folder
         * of the Code Base of the Cloudrexx installation
         * Formerly known as ASCMS_CORE_MODULE_PATH
         * @var string
         */
        protected $codeBaseCoreModulePath  = null;

        /**
         * The offset path of the core modules(core_modules) folder
         * of the Code Base of the Cloudrexx installation
         * Formerly known as ASCMS_CORE_MODULE_WEB_PATH
         * @var string
         */
        protected $codeBaseCoreModuleWebPath  = null;

        /**
         * The absolute path of the lib folder
         * of the Code Base of the Cloudrexx installation
         * Formerly known as ASCMS_LIBRARY_PATH
         * @var string
         */
        protected $codeBaseLibraryPath  = null;

        /**
         * The absolute path of the FRAMEWORK folder
         * of the Code Base of the Cloudrexx installation
         * Formerly known as ASCMS_FRAMEWORK_PATH
         * @var string
         */
        protected $codeBaseFrameworkPath  = null;

        /**
         * The absolute path of the model folder
         * of the Code Base of the Cloudrexx installation
         * Formerly known as ASCMS_MODEL_PATH
         * @var string
         */
        protected $codeBaseModelPath  = null;

        /**
         * The absolute path of the module folder
         * of the Code Base of the Cloudrexx installation
         * Formerly known as ASCMS_MODULE_PATH
         * @var string
         */
        protected $codeBaseModulePath  = null;

        /**
         * The offset path of the module folder
         * of the Code Base of the Cloudrexx installation
         * Formerly known as ASCMS_MODULE_WEB_PATH
         * @var string
         */
        protected $codeBaseModuleWebPath  = null;

        /**
         * The absolute path to the themes storage location (/themes)
         * of the Code Base of the Cloudrexx installation
         * @var string
         */
        protected $codeBaseThemesPath = null;

        /**
         * The absolute path to the website's data repository.
         * Formerly known as ASCMS_INSTANCE_PATH.
         * @var string
         */
        protected $websitePath = null;

        /**
         * The offset path from the website's data repository to the
         * location of the Cloudrexx installation if it is run in a subdirectory.
         * Formerly known as ASCMS_INSTANCE_OFFSET.
         * @var string
         */
        protected $websiteOffsetPath = null;

        /**
         * The absolute path to the data repository of the Cloudrexx installation.
         * Formerly known as ASCMS_INSTANCE_DOCUMENT_ROOT.
         * @var string
         */
        protected $websiteDocumentRootPath = null;

        /**
         * The absolute path to the storage location of
         * the website's config files  (/config).
         * @var string
         */
        protected $websiteConfigPath = null;

        /**
         * The absolute path to the customizing repository of the website.
         * Formerly known as ASCMS_CUSTOMIZING_PATH.
         * @var string
         */
        protected $websiteCustomizingPath = null;

        /**
         * The offset path from the website's DocumentRoot to the customizing
         * repository of the website.
         * Formerly known as ASCMS_CUSTOMIZING_WEB_PATH.
         * @var string
         */
        protected $websiteCustomizingWebPath = null;

        /**
         * The absolute path to the temp storage location (/tmp)
         * of the associated Data repository of the website.
         * Formerly known as ASCMS_TEMP_PATH.
         * @var string
         */
        protected $websiteTempPath = null;

        /**
         * The offset path to the temp storage location (/tmp)
         * of the associated Data repository of the website.
         * Formerly known as ASCMS_TEMP_WEB_PATH.
         * @var string
         */
        protected $websiteTempWebPath = null;

        /**
         * The absolute path to the themes storage location (/themes)
         * of the associated Data repository of the website.
         * Formerly known as ASCMS_THEMES_PATH.
         * @var string
         */
        protected $websiteThemesPath = null;
        /**
         * The offset path to the themes storage location (/themes)
         * of the associated Data repository of the website.
         * Formerly known as ASCMS_THEMES_WEB_PATH.
         * @var string
         */
        protected $websiteThemesWebPath = null;

        /**
         * The absolute path to the feed storage location (/feed)
         * of the associated Data repository of the website.
         * Formerly known as ASCMS_FEED_PATH.
         * @var string
         */
        protected $websiteFeedPath = null;

        /**
         * Id of the Cx object
         */
        protected $id = 0;

        /**
         * Auto-increment value used for the
         * next Cx object to be instanciated.
         */
        static protected $autoIncrementValueOfId = 0;

        protected $websiteImagesContentPath;
        protected $websiteImagesAttachPath;
        protected $websiteImagesShopPath;
        protected $websiteImagesGalleryPath;
        protected $websiteImagesAccessPath;
        protected $websiteImagesMediaDirPath;
        protected $websiteImagesDownloadsPath;
        protected $websiteImagesCalendarPath;
        protected $websiteImagesPodcastPath;
        protected $websiteImagesBlogPath;
        protected $websiteImagesDataPath;
        protected $websiteMediaForumUploadPath;
        protected $websiteMediaarchive1Path;
        protected $websiteMediaarchive2Path;
        protected $websiteMediaarchive4Path;
        protected $websiteMediaarchive3Path;
        protected $websiteMediaFileSharingPath;
        protected $websiteMediaMarketPath;
        protected $websiteImagesContentWebPath;
        protected $websiteImagesAttachWebPath;
        protected $websiteImagesShopWebPath;
        protected $websiteImagesGalleryWebPath;
        protected $websiteImagesAccessWebPath;
        protected $websiteImagesMediaDirWebPath;
        protected $websiteImagesDownloadsWebPath;
        protected $websiteImagesCalendarWebPath;
        protected $websiteImagesPodcastWebPath;
        protected $websiteImagesBlogWebPath;
        protected $websiteImagesDataWebPath;
        protected $websiteMediaForumUploadWebPath;
        protected $websiteMediaarchive1WebPath;
        protected $websiteMediaarchive2WebPath;
        protected $websiteMediaarchive3WebPath;
        protected $websiteMediaarchive4WebPath;
        protected $websiteMediaFileSharingWebPath;
        protected $websiteMediaMarketWebPath;
        protected $websiteImagesPath;
        protected $websiteImagesWebPath;
        protected $websitePublicTempPath;
        protected $websitePublicTempWebPath;
        protected $websiteImagesCrmPath;
        protected $websiteImagesCrmWebPath;
        protected $websiteImagesCrmProfilePath;
        protected $websiteImagesCrmProfileWebPath;
        protected $websiteMediaCrmPath;
        protected $websiteMediaDirectoryPath;
        protected $websiteMediaDirectoryWebPath;
        protected $websiteImagesAccessProfilePath;
        protected $websiteImagesAccessProfileWebPath;
        protected $websiteImagesAccessPhotoPath;
        protected $websiteImagesAccessPhotoWebPath;

        /**
         * @var \Cx\Core\MediaSource\Model\Entity\MediaSourceManager
         */
        protected $mediaSourceManager;
        
        /**
         * @var integer The memory limit. Is set in init
         */
        protected $memoryLimit = 48;

        /**
         * @var string The processed data to be sent to the client as response
         */
        protected $endcode;

        /**
         * @var array Contains the preloaded components from preInit and preComponentLoad
         */
        protected $preLoadedComponents = [];

        /**
         * @return string The processed data which is set in finalize
         */
        public function getEndcode()
        {
            return $this->getResponse()->getParsedContent();
        }

        /**
         * This creates instances of this class
         *
         * Normally the first instance is returned. You may set another instance
         * to be the preferred one using the $setAsPreferred argument.
         * @param string $mode (optional) One of the modes listed in constants above
         * @param boolean $forceNew (optional) Wheter to force a new instance or not, default false
         * @param string $configFilePath (optional) The absolute path to a Cloudrexx configuration
         *                               file (configuration.php) that shall be loaded
         *                               instead of the default one.
         * @param boolean $setAsPreferred (optional) Sets this instance as the preferred one for later
         * @return \Cx\Core\Core\Controller\Cx Instance of this class
         */
        public static function instanciate($mode = null, $forceNew = false, $configFilePath = null, $setAsPreferred = false, $checkInstallationStatus = true) {
            // at least one instance exists (for given config file path) AND not forced to create a new one
            if (count(self::$instances) && !$forceNew && count(self::$instances[$configFilePath])) {
                // If no config file path is supplied, return the preferred instance
                if (!$configFilePath) {
                    return self::$preferredInstance;
                }
                // Else return the first instance for given config file path
                reset(self::$instances[$configFilePath]);
                $instance = current(self::$instances[$configFilePath]);
                // Set this instance as the preferred one
                if ($setAsPreferred) {
                    self::$preferredInstance = $instance;
                }
                return $instance;
            }
            new static($mode, $configFilePath, $setAsPreferred, $checkInstallationStatus);
            // Important: We must return the preferred instance (self::$preferredInstance) here,
            //            as it might be possible, that during the instanciation of the above object
            //            an additional instance had been instanciated and had been set as the
            //            preferred instance instead.
            //            Therefore the retured object by 'new static()' from above might already be
            //            outdated and shall be discarded therefore.
            //            The preferred instance (self::$preferredInstance) gets set in this class's
            //            constructor (__construct()).
            return self::$preferredInstance;
        }

        /**
         * Register a \Cx\Core\Core\Controller\Cx compatible object as new instance
         *
         * @param   \Cx\Core\Core\Controller\Cx $cx Instanciated Cx object
         * @param   string $configFilePath The absolute path to a Cloudrexx configuration file (configuration.php).
         * @param   boolean $setAsPreferred Whether or not to set the Cx instance as preferred instance to be used
         */
        public static function registerInstance($cx, $configFilePath = null, $setAsPreferred = false) {
            self::$autoIncrementValueOfId++;
            $cx->setId(self::$autoIncrementValueOfId);

            if (!count(self::$instances) || $setAsPreferred) {
                self::$preferredInstance = $cx;
            }
            if (!isset(self::$instances[$configFilePath])) {
                self::$instances[$configFilePath] = array();
            }
            self::$instances[$configFilePath][] = $cx;
        }

        /* STAGE 2: __construct(), early initializations */

        /**
         * Initializes the Cx class
         * This does everything related to Cloudrexx.
         * @param string $mode (optional) Use constants, one of self::MODE_[FRONTEND|BACKEND|CLI|MINIMAL]
         * @param string $configFilePath The absolute path to a Cloudrexx configuration
         *                               file (configuration.php) that shall be loaded
         *                               instead of the default one.
         * @param   boolean $setAsPreferred Whether or not to set the Cx instance as preferred instance to be used
         */
        protected function __construct($mode = null, $configFilePath = null, $setAsPreferred = false, $checkInstallationStatus = true) {
            // register this new Cx instance
            // will be used by \Cx\Core\Core\Controller\Cx::instanciate()
            self::registerInstance($this, $configFilePath, $setAsPreferred);

            try {
                /**
                 * This starts time measurement
                 * Timer will get stopped in finalize() method
                 */
                $this->startTimer();

                /**
                 * Load config/configuration.php
                 */
                $this->loadConfig($configFilePath);

                /**
                 * Loads the basic configuration ($_CONFIG) from config/settings.php
                 */
                $this->loadSettings();

                /**
                 * Checks if the system has been installed (CONTREXX_INSTALLED).
                 * If not, the user will be redirected to the web-installer.
                 */
                if ($checkInstallationStatus) {
                    $this->checkInstallationStatus();
                }

                /**
                 * Verifies that the basic configuration ($_CONFIG) has bee loaded.
                 * If not, the system will halt.
                 */
                $this->checkBasicConfiguration();

                /**
                 * Sets the path to the customizing directory (/customizing) of the website,
                 * if the associated functionality has been activatd.
                 */
                $this->setCustomizingPath();

                /**
                 * Sets the mode Cloudrexx runs in
                 * One of self::MODE_[FRONTEND|BACKEND|CLI|MINIMAL]
                 */
                $this->setMode($mode);

                /**
                 * Early initializations. Verifies that the system is online (not suspended).
                 * Initializes the ClassLoader, the legacy Environment variables and executes
                 * the preInit-hook-scripts. Finally it verifies the requested HTTP-Host.
                 */
                $this->preInit();

                /**
                 * Defines the core constants (ASCMS_*) of Cloudrexx as defined in config/set_constants.php
                 * and config/SetCustomizableConstants.php.
                 */
                $this->defineLegacyConstants();

                /**
                 * Loads ClassLoader, EventManager and Database connection
                 * For now, this also loads some legacy things like API, AdoDB, Env and InitCMS
                 */
                $this->init();

                /**
                 * In order to make this file customizable, we explicitly
                 * search for a subclass of Cx\Core\Core\Controller\Cx named Cx\Customizing\Core\Cx
                 * If such a class is found, it is loaded and this request will be stopped
                 */
                $this->handleCustomizing();

                /**
                 * Initialize license
                 */
                $this->preComponentLoad();

                /**
                 * Loads all active components
                 */
                $this->loadComponents();
                
                $this->postComponentLoad();

                /**
                 * Initialize request
                 * Request is not initialized for command mode
                 */
                $this->postInit();

                /**
                 * Since we have a valid state now, we can start executing
                 * all of the component's hook methods.
                 * This initializes the main template, executes all hooks
                 * and parses the template.
                 *
                 * This is not executed automaticly in minimal. Invoke it
                 * yourself if necessary and be sure to handle exceptions.
                 *
                 * Command mode is different ;-)
                 */
                if ($this->mode == self::MODE_MINIMAL) {
                    // Legacy:
                    if (!defined('MODULE_INDEX')) {
                        define('MODULE_INDEX', '');
                    }
                    return;
                }
                $this->loadContrexx();
            }

            /**
             * Globally catch InstanceException
             *
             * InstanceException is used to abort the execution of Cx
             *
             * A reason for this might be that a component did initialize
             * an other instance of Cx and does not want the original Cx
             * object to proceed after the newly created instance of Cx
             * has reached its execution end.
             */
            catch (InstanceException $e) {
                return;
            }

            /**
             * Globally catch ShinyException
             *
             * ShinyException is used for user-friendly error handling
             *
             * A usage case might be to perform an authorisation check
             * on model level whereas the overlying component is not
             * aware of such a check and won't handly it therefore.
             */
            catch (\Cx\Core\Error\Model\Entity\ShinyException $e) {
                if ($this->mode != self::MODE_BACKEND) {
                    throw new \Exception($e->getMessage());
                }
                // reset root of Cx\Core\Html\Sigma to backend template path
                $this->template->setRoot($this->codeBaseAdminTemplatePath);
                $this->template->setVariable('ADMIN_CONTENT', $e->getBackendViewMessage());
                $this->setPostContentLoadPlaceholders();
                $this->finalize();
                $this->getResponse()->send();
                die;
            }

            /**
             * Globally catch all exceptions and show offline.html
             *
             * This might have one of the following reasons:
             * 1. CMS is disabled by config
             * 2. Frontend is locked by license
             * 3. An error occured
             *
             * Enable \DBG to see what happened
             */
            catch (\Throwable $e) {
                \header($_SERVER['SERVER_PROTOCOL'] . ' 500 Server Error');
                if (file_exists($this->websiteDocumentRootPath . '/offline.html')) {
                    $offlinePath = $this->websiteDocumentRootPath;
                } else {
                    $offlinePath = $this->codeBaseDocumentRootPath;
                }
                // remove CSRF token
                output_reset_rewrite_vars();
                echo file_get_contents($offlinePath . '/offline.html');
                \DBG::msg('GET:');
                \DBG::dump($_GET);
                \DBG::msg('POST:');
                \DBG::dump($_POST);
                \DBG::msg('COOKIE:');
                \DBG::dump($_COOKIE);
                \DBG::msg('SERVER:');
                \DBG::dump($_SERVER);
                \DBG::msg('Cloudrexx initialization failed! ' . get_class($e) . ': "' . $e->getMessage() . '"');
                \DBG::msg('In file ' . $e->getFile() . ' on Line ' . $e->getLine());
                \DBG::dump($e->getTrace());
                die();
            }
        }

        /**
         * Starts time measurement for page parsing time
         */
        protected function startTimer() {
            if ($this->startTime) {
                return;
            }

            $this->startTime = explode(' ', microtime());
        }

        /**
         * Get the start time
         * 
         * @return array
         */
        public function getStartTime() {
            return $this->startTime;
        }

        /**
         * Stops time measurement and returns page parsing time
         * @return int Time needed to parse page in seconds
         */
        public function stopTimer() {
            $finishTime = explode(' ', microtime());
            return round(((float)$finishTime[0] + (float)$finishTime[1]) - ((float)$this->startTime[0] + (float)$this->startTime[1]), 5);
        }

        /**
         * Load an optional configuration file and sets up the path configuration.
         *
         * Note: The default configuration.php is loaded in index.php in order to
         * load this file from its correct location.
         * @todo Find a way to store configuration by avoiding global variables
         * @global array $_PATHCONFIG Path configuration from /config/configuration.php
         * @global array $_DBCONFIG Database connection details from /config/configuration.php
         */
        protected function loadConfig($configFilePath = null) {
            global $_PATHCONFIG, $_DBCONFIG;

            // load custom configuration file
            if ($configFilePath) {
                \DBG::log('Cx: LoadConfig: '.$configFilePath);
                include_once $configFilePath;
            }

            /**
             * Should we overwrite path configuration?
             */
            $fixPaths = false;
            // path configuration is empty, so yes, we should...
            if (empty($_PATHCONFIG['ascms_root'])) {
                $fixPaths = true;
            } else {
                if (substr(!empty($_GET['__cap']) ? $_GET['__cap'] : '', 0, strlen($_PATHCONFIG['ascms_root_offset'])) != $_PATHCONFIG['ascms_root_offset']) {
                    // URL doesn't seem to start with provided offset
                    $fixPaths = true;
                }
            }
            if ($fixPaths) {
                $this->fixPaths($_PATHCONFIG['ascms_root'], $_PATHCONFIG['ascms_root_offset']);
            }
            if ($fixPaths || empty($_PATHCONFIG['ascms_installation_root'])) {
                $_PATHCONFIG['ascms_installation_root'] = $_PATHCONFIG['ascms_root'];
                $_PATHCONFIG['ascms_installation_offset'] = $_PATHCONFIG['ascms_root_offset'];
            }

            $this->setCodeBaseRepository($_PATHCONFIG['ascms_installation_root'], $_PATHCONFIG['ascms_installation_offset']);
            $this->setWebsiteRepository($_PATHCONFIG['ascms_root'], $_PATHCONFIG['ascms_root_offset']);
        }

        /**
         * Loads basic configuration (settings.php) and set basic PHP behavior
         * such as character-set, timezone, etc.
         *
         * @todo Find a way to store configuration by avoiding global variables
         * @global array $_CONFIG Configuration array from /config/settings.php
         * @global array $_DBCONFIG Configuration array from /config/settings.php
         */
        protected function loadSettings() {
            global $_CONFIG, $_DBCONFIG;

            /**
             * User configuration settings
             *
             * This file is re-created by the CMS itself. It initializes the
             * {@link $_CONFIG[]} global array.
             */
            include_once $this->getWebsiteConfigPath().'/settings.php';

            @ini_set('default_charset', $_CONFIG['coreCharacterEncoding']);

            // Set output url seperator
            @ini_set('arg_separator.output', '&amp;');

            // Set url rewriter tags
            @ini_set('url_rewriter.tags', 'a=href,area=href,frame=src,iframe=src,input=src,form=,fieldset=');

            // Set timezone
            @ini_set('date.timezone', $_CONFIG['timezone']);
        }

        /**
         * Loads legacy constants (set_constants.php / SetCustomizableConstants.php)
         */
        protected function defineLegacyConstants()
        {
            require_once $this->getCodeBaseDocumentRootPath() . '/config/set_constants.php';
        }

        /**
         * Checks if the Cloudrexx installation has been set up yet (CONTEXX_INSTALLED).
         * If not, the user will be redirected (through a HTTP-Location redirect) to
         * the web-installer (/installer).
         */
        protected function checkInstallationStatus() {
            // Check if the system is installed
            if (!defined('CONTREXX_INSTALLED') || !CONTREXX_INSTALLED) {
                header('Location: '.$this->getCodeBaseOffsetPath().'/installer/index.php');
                exit;
            }
        }

        /**
         * Verifies if the basic configuration has been initialized (settings.php).
         * If not, the system will halt.
         *
         * @global array $_CONFIG Configuration array from /config/settings.php
         */
        protected function checkBasicConfiguration() {
            global $_CONFIG;

            if (!isset($_CONFIG)) {
                die('System halted: Unable to load basic configuration!');
            }

            if (empty($_SERVER['SERVER_NAME'])) {
                $_SERVER['SERVER_NAME'] = $_CONFIG['domainUrl'];
            }
        }

        protected function setCustomizingPath() {
            global $_CONFIG;

            // Check if the system is configured with enabled customizings
            if (isset($_CONFIG['useCustomizings']) && $_CONFIG['useCustomizings'] == 'on') {
                $this->customizingPath = $this->getWebsiteCustomizingPath();
            }
        }

        /**
         * Sets the parameters to the correct path values
         * @param string $documentRoot Document root for this vHost
         * @param string $rootOffset Document root offset for this installation
         */
        protected function fixPaths(&$documentRoot, &$rootOffset) {
            // calculate correct offset path
            // turning '/myoffset/somefile.php' into '/myoffset'
            $rootOffset = '';
            $directories = explode('/', $_SERVER['SCRIPT_NAME']);
            for ($i = 0; $i < count($directories) - 1; $i++) {
                if ($directories[$i] !== '') {
                    $rootOffset .= '/'.$directories[$i];
                }
            }

            // fix wrong offset if another file than index.php was requested
            // turning '/myoffset/core_module/somemodule' into '/myoffset'
            $fileRoot = dirname(dirname(dirname(dirname(__FILE__))));
            $nonOffset = preg_replace('#' . preg_quote($fileRoot) . '#', '', realpath($_SERVER['SCRIPT_FILENAME']));
            $nonOffsetParts = preg_split('#[/\\\\]#', $nonOffset);
            end($nonOffsetParts);
            unset($nonOffsetParts[key($nonOffsetParts)]);
            $nonOffset = implode('/', $nonOffsetParts);
            $rootOffset = preg_replace('#' . preg_quote($nonOffset) . '#', '', $rootOffset);

            // calculate correct document root
            // turning '/var/www/myoffset' into '/var/www'
            $documentRoot = '';
            $arrMatches = array();
            $scriptPath = str_replace('\\', '/', dirname(dirname(__FILE__)));
            if (preg_match("/(.*)(?:\/[\d\D]*){2}$/", $scriptPath, $arrMatches) == 1) {
                $scriptPath = $arrMatches[1];
            }
            if (preg_match("#(.*)". preg_quote($rootOffset) ."#", $scriptPath, $arrMatches) == 1) {
                $documentRoot = $arrMatches[1];
            }

            // fix wrong variable assignment in CLI
            if (empty($documentRoot) && !empty($rootOffset)) {
                $documentRoot = $rootOffset;
                $rootOffset = '';
            }
        }

        /**
         * Set the mode Cloudrexx is used in
         * @param mixed $mode Mode as string or true for front- or false for backend
         */
        protected function setMode($mode) {
            global $_CONFIG;

            if ((!$mode || $mode == 'command') && php_sapi_name() === 'cli') {
                $this->mode = self::MODE_COMMAND;
                return;
            }
            switch ($mode) {
                case self::MODE_BACKEND:
                case self::MODE_FRONTEND:
                case self::MODE_COMMAND:
                case self::MODE_MINIMAL:
                    break;
                default:
                    if ($mode === false) {
                        $mode = self::MODE_BACKEND;
                        break;
                    }
                    $mode = self::MODE_FRONTEND;
                    if (!isset($_GET['__cap'])) {
                        break;
                    }
                    if (preg_match('#^' . $this->getWebsiteOffsetPath() . '(/[a-z]{1,2}(?:-[A-Za-z]{2,4})?)?' . self::FOLDER_NAME_COMMAND_MODE . '(?:[?/\#]|$)#', $_GET['__cap'])) {
                        $this->mode = self::MODE_COMMAND;
                        return;
                    }
                    if (!preg_match('#^' . $this->getWebsiteOffsetPath() . '(/[a-z]{1,2}(?:-[A-Za-z]{2,4})?)?(/admin|' . $this->getBackendFolderName() . ')#', $_GET['__cap'])) {
                        break;
                    }
                    // this does not belong here:
                    if (!preg_match('#^' . $this->getWebsiteBackendPath() . '/#', $_GET['__cap'])) {
                        // do not use \Cx\Core\Csrf\Controller\Csrf::header() here, since ClassLoader is not loaded at this time
// TODO: is this actually the cause of the CSRF missing issue?
                        header('Location: ' . $this->getWebsiteBackendPath() . '/');
                        die();
                    }
                    $mode = self::MODE_BACKEND;
                    break;
            }
            $this->mode = $mode;
            if ($this->request) {
                $this->request->getUrl()->setMode($this->mode);
            }
        }

        /**
         * Early initializations. Verifies that the system is online (not suspended).
         * Initializes the ClassLoader, the legacy Environment variables and executes
         * the preInit-hook-scripts. Finally it verifies the requested HTTP-Host.
         */
        protected function preInit() {
            $this->checkSystemState();
            $this->initClassLoader();
            $this->initLegacyEnv();
            $this->callPreInitHooks();
        }

        /**
         * Check whether the system is running
         * @param   boolean $disableAllModes Set to TRUE to stop the system initialization for any mode (not only {@see self::MODE_FRONTEND}) in case the website has been put into maintenance-mode ($_CONFIG['systemStatus'] = 'off').
         * @throws \Exception
         */
        public function checkSystemState($disableAllModes = false) {
            global $_CONFIG;
            // Check if system is running
            if (   $_CONFIG['systemStatus'] != 'on'
                 && ($this->mode == self::MODE_FRONTEND || $disableAllModes)
            ) {
                throw new \Exception('System disabled by config');
            }
        }

        protected function initClassLoader() {
            /**
             * This needs to be initialized before loading config/doctrine.php
             * Because we overwrite the Gedmo model (so we need to load our model
             * before doctrine loads the Gedmo one)
             */
            if (!class_exists('Cx\Core\ClassLoader\ClassLoader', false)) {
                require_once($this->getCodeBaseCorePath().'/ClassLoader/ClassLoader.class.php');
            }
            $this->cl = new \Cx\Core\ClassLoader\ClassLoader($this, true, $this->customizingPath);
        }

        /**
         * Setting up Env class
         * @global array $_CONFIG Configuration array from /config/settings.php
         * @global array $_FTPCONFIG FTP configuration array from /config/configuration.php
         */
        protected function initLegacyEnv() {
            global $_CONFIG, $_FTPCONFIG;
            /**
             * Environment repository
             */
            if (!class_exists('Env', false)) {
                require_once($this->cl->getFilePath($this->codeBaseCorePath . '/Env.class.php'));
            }
            \Env::set('ClassLoader', $this->cl);
            \Env::set('config', $_CONFIG);
            \Env::set('ftpConfig', $_FTPCONFIG);
        }

        /**
         * Calls pre-init hooks
         * Pre-Init hooks are defined in /config/preInitHooks.yml.
         *
         * @throws \Exception
         */
        protected function callPreInitHooks() {
            try {
                $filename = $this->getWebsiteConfigPath() . '/preInitHooks.yml';
                $objDataSet = \Cx\Core_Modules\Listing\Model\Entity\DataSet::load($filename, false);
                foreach ($objDataSet as $componentDefinition) {
                    $componentController = $this->getComponentControllerByNameAndType($componentDefinition['name'], $componentDefinition['type']);
                    $componentController->preInit($this);
                    // store componentController in preLoadedCompnents, using the name as key
                    $this->preLoadedComponents[$componentDefinition['name']] = $componentController;
                }
            } catch (\Cx\Core_Modules\Listing\Model\Entity\DataSetException $e) {
                throw new \Exception('Error in processing preInit-hooks: '.$e->getMessage());
            }
        }

        /**
         * Calls preComponentLoad-Hooks
         * PreComponentLoad-Hooks are defined in /config/preComponentLoadHooks.yml
         *
         * @throws \Exception
         */
        protected function callPreComponentLoadHooks() {
            try {
                $filename = $this->getWebsiteConfigPath() . '/preComponentLoadHooks.yml';
                $objDataSet = \Cx\Core_Modules\Listing\Model\Entity\DataSet::load($filename, false);
                foreach ($objDataSet as $componentDefinition) {
                    // check if componentController was already loaded in preInit
                    if (array_key_exists($componentDefinition['name'], $this->preLoadedComponents)) {
                        $componentController = $this->preLoadedComponents[$componentDefinition['name']];
                    } else {
                        $componentController = $this->getComponentControllerByNameAndType($componentDefinition['name'], $componentDefinition['type']);
                        $this->preLoadedComponents[$componentDefinition['name']] = $componentController;
                    }
                    $componentController->preComponentLoad();
                }
            } catch (\Cx\Core_Modules\Listing\Model\Entity\DataSetException $e) {
                throw new \Exception('Error in processing preComponentLoad-hooks: '.$e->getMessage());
            }
        }

        /**
         * Get component controller object by given component name and type
         * Calls before the method preInit() and postInit() hooks are called
         *
         * @param string $componentName component name
         * @param string $componentType component type
         *
         * @return \Cx\Core\Core\Controller\SystemComponentController
         */
        protected function getComponentControllerByNameAndType($componentName, $componentType)
        {
            $component = new \Cx\Core\Core\Model\Entity\SystemComponent();
            $component->setName($componentName);
            $component->setType($componentType);
            // Initialize ComponentController of component if available,
            // otherwise initialize the SystemComponentController
            // Implementation taken from method Cx\Core\Core\Model\Repository\SystemComponentRepository::getComponentControllerClassFor()
            // as that method shall not be used at this point to prevent the
            // system (i.e. the Class Loader) from loading the doctine PHP classes.
            if ($this->cl->getFilePath($component->getDirectory(false) . '/Controller/ComponentController.class.php')) {
                $componentControllerClass = $component->getNamespace() . '\\Controller\\ComponentController';
            } else {
                $componentControllerClass = '\\Cx\\Core\\Core\\Model\\Entity\\SystemComponentController';
            }
            return new $componentControllerClass($component, $this);
        }
        
        /**
         * Returns the ComponentController for the given component
         * @deprecated All new classes should have access to $this->getComponent()
         * @param string $name Component name
         * @return \Cx\Core\Core\Model\Entity\SystemComponentController Component main controller
         */
        public function getComponent($name) {
            if (!$this->getDb()) {
                // try to load the component from the preloaded components
                if (isset($this->preLoadedComponents[$name])) {
                    return $this->preLoadedComponents[$name];
                }
                return null;
            }
            $em = $this->getDb()->getEntityManager();
            $componentRepo = $em->getRepository('Cx\Core\Core\Model\Entity\SystemComponent');
            $component = $componentRepo->findOneBy(array('name' => $name));
            return $component;
        }

        /**
         * @param integer $memoryLimit
         */
        public function setMemoryLimit($memoryLimit)
        {
            $this->memoryLimit = $memoryLimit;
        }

        /**
         * This tries to set the memory limit if its lower than the needed memory limit
         */
        protected function tryToSetMemoryLimit() {
            $memoryLimit = array();
            preg_match('/^\d+/', ini_get('memory_limit'), $memoryLimit);
            if (!isset($memoryLimit[0])) {
                return;
            }
            if ($memoryLimit[0] < $this->memoryLimit) {
                ini_set('memory_limit', $this->memoryLimit . 'M');
            }
        }

        /**
         * Check whether the user accessed the correct domain url and protocol
         * @return mixed
         */
        protected function adjustRequest() {
            if ($this->mode == self::MODE_MINIMAL || $this->mode == self::MODE_COMMAND) {
                return;
            }

            $domain = $this->checkDomainUrl();
            $protocol = $this->adjustProtocol();

            // protocol and domain is correct, no redirect
            if ($protocol === null && $domain === null) {
                return null;
            }

            // protocol is correct, use the current protocol for redirect
            if ($protocol === null) {
                $protocol = empty($_SERVER['HTTPS']) ? 'http' : 'https';
            }

            // domain is correct, use the current domain for redirect
            if ($domain === null) {
                $domain = $_SERVER['HTTP_HOST'];
            }

            // redirect to correct domain and protocol
            $url = $protocol . '://' . $domain . $_SERVER['REQUEST_URI'];
            $this->getComponent('Cache')->writeCacheFileForRequest(
                null,
                array(
                    $_SERVER['SERVER_PROTOCOL'] . ' 301 Moved Permanently',
                    'Location' => $url,
                ),
                ''
            );
            \header($_SERVER['SERVER_PROTOCOL'] . ' 301 Moved Permanently');
            \header('Location: ' . $url);
            exit;
        }

        /**
         * Check whether the requested url is correct or not
         * there is a settings option in the general settings section of cloudrexx which allows
         * to force the domain url which is provided
         * @return null|string the correct domain url
         */
        protected function checkDomainUrl() {
            global $_CONFIG;
            if (!isset($_CONFIG['forceDomainUrl']) || $_CONFIG['forceDomainUrl'] == 'off') {
                return null;
            }
            if ($_SERVER['HTTP_HOST'] != $_CONFIG['domainUrl']) {
                return $_CONFIG['domainUrl'];
            }
            return null;
        }

        /**
         * Adjust the protocol to https if https is activated for the current area (frontend|backend)
         * @return null|string the correct protocol
         */
        protected function adjustProtocol() {
            global $_CONFIG;
            // check whether Cloudrexx has to redirect to the correct protocol

            $configOption = 'forceProtocolFrontend';
            if ($this->mode == self::MODE_BACKEND) {
                $configOption = 'forceProtocolBackend';
            }

            if (!isset($_CONFIG[$configOption]) || $_CONFIG[$configOption] == 'none') {
                return null;
            }

            if ($_CONFIG[$configOption] == 'https' && empty($_SERVER['HTTPS'])) {
                return 'https';
            } else if ($_CONFIG[$configOption] == 'http' && !empty($_SERVER['HTTPS'])) {
                return 'http';
            }
            return null;
        }

        /**
         * Loading ClassLoader, EventManager, Env, DB, API and InitCMS
         * (Env, API and InitCMS are deprecated)
         * @todo Remove deprecated elements
         * @todo Remove usage of globals
         * @global type $_DBCONFIG
         * @global type $objDatabase
         */
        protected function init() {
            global $objDatabase, $_DBCONFIG;

            $this->tryToSetMemoryLimit();

            /**
             * Include all the required files.
             * @todo Remove API.php, it should be unnecessary
             */
            $this->cl->loadFile($this->codeBaseCorePath . '/API.php');
            // Temporary fix until all GET operation requests will be replaced by POSTs
            if ($this->mode != self::MODE_BACKEND) {
                \Cx\Core\Csrf\Controller\Csrf::setFrontendMode();
            }

            // Set database connection details
            $objDb = new \Cx\Core\Model\Model\Entity\Db();
            $objDb->setHost($_DBCONFIG['host']);
            $objDb->setName($_DBCONFIG['database']);
            $objDb->setTablePrefix($_DBCONFIG['tablePrefix']);
            $objDb->setDbType($_DBCONFIG['dbType']);
            $objDb->setCharset($_DBCONFIG['charset']);
            $objDb->setCollation($_DBCONFIG['collation']);
            $objDb->setTimezone($_DBCONFIG['timezone']);

            // Set database user details
            $objDbUser = new \Cx\Core\Model\Model\Entity\DbUser();
            $objDbUser->setName($_DBCONFIG['user']);
            $objDbUser->setPassword($_DBCONFIG['password']);

            // Initialize database connection
            $this->db = new \Cx\Core\Model\Db($objDb, $objDbUser, $this->getComponent('Cache')->getCacheDriver());
            $objDatabase = $this->db->getAdoDb();
            \Env::set('db', $objDatabase);

            $em = $this->db->getEntityManager();
            $pageGuard = new \PageGuard($this->db->getAdoDb());
            \Env::set('pageguard', $pageGuard);

            \DBG::set_adodb_debug_mode();

            $this->eventManager = new \Cx\Core\Event\Controller\EventManager($this);
            new \Cx\Core\Event\Controller\ModelEventWrapper($this);
            $this->eventManager->addEvent('preComponent');
            $this->eventManager->addEvent('postComponent');

            //$bla = $em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
            //$bla->findAll();
        }

        /**
         * Loads a subclass of this class from customizing if available
         * @return null
         */
        protected function handleCustomizing() {
            if (!$this->customizingPath) {
                return;
            }
            if (!file_exists($this->customizingPath.'/core/Core/Controller/Cx')) {
                return;
            }
            // we have to use reflection here, since instanceof does not work if the child is no object
            $myReflection = new \ReflectionClass('\\Cx\\Customizing\\Core\\Controller\\Cx');
            if (!$myReflection->isSubclassOf(get_class($this))) {
                return;
            }
            new \Cx\Customizing\Core\Controller\Cx($this->getMode());
            die();
        }

        /**
         * Initializes request
         */
        protected function postInit() {
            global $_CONFIG;

            // if path configuration was wrong in loadConfig(), Url is not yet initialized
            if (!$this->request) {
                // this makes \Env::get('Resolver')->getUrl() return a sensful result
                $request = !empty($_GET['__cap']) ? $_GET['__cap'] : '';
                $offset = $this->websiteOffsetPath;

                switch ($this->mode) {
                    case self::MODE_FRONTEND:
                    case self::MODE_BACKEND:
                        $this->request = new \Cx\Core\Routing\Model\Entity\Request($_SERVER['REQUEST_METHOD'],
                                                                                   \Cx\Core\Routing\Url::fromCapturedRequest($request, $offset, $_GET));
                        break;
                    case self::MODE_COMMAND:
                    case self::MODE_MINIMAL:
                        try {
                            $this->request = new \Cx\Core\Routing\Model\Entity\Request($_SERVER['REQUEST_METHOD'], \Cx\Core\Routing\Url::fromRequest());
                        } catch (\Cx\Core\Routing\UrlException $e) {}
                        break;
                }
            }
            $this->response = new \Cx\Core\Routing\Model\Entity\Response(
                null,
                200,
                $this->request
            );
            $this->response->setContentType('text/html');
            //call post-init hooks
            $this->ch->callPostInitHooks();
        }

        /**
         * Initialize license and call pre-component-load hook scripts
         * @throws \Cx\Core\Model\DbException
         * @throws \Exception
         */
        protected function preComponentLoad() {
            global $_CONFIG;
            $this->license = \Cx\Core_Modules\License\License::getCached($_CONFIG, $this->getDb()->getAdoDb());
            $this->callPreComponentLoadHooks();
        }

        /**
         * Loads all active components
         */
        protected function loadComponents() {
            $this->ch = new \Cx\Core\Core\Controller\ComponentHandler($this->license, $this->mode == self::MODE_FRONTEND, $this->db->getEntityManager(), $this->preLoadedComponents);
        }

        /**
         * Call post-component-load hook scripts
         */
        protected function postComponentLoad() {
            $this->ch->callPostComponentLoadHooks();
        }

        /* STAGE 3: loadContrexx(), call hook scripts */

        /**
         * Initializes global template, executes all component hook methods
         * and parses the template.
         */
        protected function loadContrexx() {
            // command mode is different
            if ($this->getMode() == static::MODE_COMMAND) {
                global $argv;
                
                // Legacy:
                if (!defined('MODULE_INDEX')) {
                    define('MODULE_INDEX', '');
                }

                try {
                    // cleanup params
                    $params = array();
                    if (isset($argv)) {
                        $params = array_slice($argv, 1);
                        foreach ($params as $key=>$value) {
                            $argParts = explode('=', $value, 2);
                            if (count($argParts) == 2) {
                                $params[$argParts[0]] = $argParts[1];
                                unset($params[$key]);
                            }
                        }
                    } else {
                        $params = preg_replace('#' . $this->getWebsiteOffsetPath() . static::FOLDER_NAME_COMMAND_MODE . '(/)?#', '', $_GET['__cap']);
                        $params = explode('/', $params) + $_GET;
                        unset($params['__cap']);
                    }
                    $params = contrexx_input2raw($params);
                    if (!isset($params['lang']) && isset($params['locale'])) {
                        $params['lang'] = $params['locale'];
                    }
                    if (isset($params['lang'])) {
                        $langId = \FWLanguage::getLanguageIdByCode($params['lang']);
                        if ($langId) {
                            if (!defined('FRONTEND_LANG_ID')) {
                                define('FRONTEND_LANG_ID', $langId);
                            }
                            if (!defined('BACKEND_LANG_ID')) {
                                define('BACKEND_LANG_ID', $langId);
                            }
                            if (!defined('LANG_ID')) {
                                define('LANG_ID', $langId);
                            }
                            $this->getDb()->getTranslationListener()->setTranslatableLocale(
                                \FWLanguage::getLanguageCodeById(FRONTEND_LANG_ID)
                            );
                        }
                    }
                    if (!\Env::get('Resolver')) {
                        $url = $this->getRequest()->getUrl();
                        $url->removeAllParams();
                        $url->setPath('/');
                        $resolver = new \Cx\Core\Routing\Resolver(
                            $url,
                            null,
                            $this->getDb()->getEntityManager(),
                            null,
                            null
                        );
                        \Env::set('Resolver', $resolver);
                    }

                    // parse body arguments:
                    // todo: this does not work for form-data encoded body (boundary...)
                    $input = '';
                    if (php_sapi_name() == 'cli') {
                        $read = array(fopen('php://stdin', 'r'));
                        $write = null;
                        $except = null;
                        if (stream_select($read, $write, $except, 0) === 1) {
                            $input = file_get_contents('php://stdin');
                        }
                    } else {
                        $input = file_get_contents('php://input');
                    }
                    $dataArguments = array();
                    parse_str($input, $dataArguments);
                    $dataArguments = contrexx_input2raw($dataArguments);

                    // find component (defaults to help)
                    $command = current($params);
                    $params = array_slice($params, 1);
                    $this->getCommands($params, true);

                    if (!isset($this->commands[$command])) {
                        http_response_code(400);
                        echo 'Command \'' . $command . '\' does not exist';
                        $command = 'help';
                    }

                    if (!isset($this->commands[$command])) {
                        throw new \Exception(
                            'Command \'' . $command . '\' does not exist or is not accessible'
                        );
                    }

                    $objCommand = $this->commands[$command];

                    // execute command
                    $objCommand->executeCommand($command, $params, $dataArguments);
                    return;
                } catch (\Exception $e) {
                    if (php_sapi_name() != 'cli') {
                        throw $e;
                    }
                    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . PHP_EOL);
                    return;
                }

            }
            // init template
            $this->loadTemplate();                      // Sigma Template

            // @TODO: remove this
            $this->legacyGlobalsHook(1);                // $objUser, $objTemplate, $cl

            // resolve
            $this->preResolve();                        // Call pre resolve hook scripts
            $this->resolve();                           // Resolving, Language
            $this->adjustRequest();                     // Adjust the protocol and the domain

            // @TODO: remove this
            $this->legacyGlobalsHook(2);                // $objInit, $_LANGID, $_CORELANG, $url;
            $this->postResolve();                       // Call post resolve hook scripts

            // load content
            $this->preContentLoad();                    // Call pre content load hook scripts
            $this->loadContent();                       // Init current module
            $this->postContentLoad();                   // Call post content load hook scripts

            $this->setPostContentLoadPlaceholders();    // Set Placeholders

            $this->preFinalize();                       // Call pre finalize hook scripts
            $this->finalize();                          // Set template vars
            $this->postFinalize();                      // Call post finalize hook scripts
            $this->getResponse()->send();               // Send response
        }

        /**
         * Init main template object
         *
         * In backend mode, ASCMS_ADMIN_TEMPLATE_PATH/index.html is opened
         * In all other modes, no file is loaded here
         */
        protected function loadTemplate() {
            $this->template = new \Cx\Core\Html\Sigma(($this->mode == self::MODE_FRONTEND) ? $this->websiteThemesPath : $this->codeBaseAdminTemplatePath);
            $this->template->setErrorHandling(PEAR_ERROR_DIE);
            if ($this->mode == self::MODE_BACKEND) {
                $this->template->loadTemplateFile('Index.html');
                $this->template->addBlockfile('CONTENT_FILE', 'index_content', 'IndexContent.html');
            }
        }

        /**
         * This populates globals for legacy code
         * @todo Avoid this! All this should be part of some components hook
         * @global type $objFWUser
         * @global type $objTemplate
         * @global type $cl
         * @global \InitCMS $objInit
         * @global type $_LANGID
         * @global type $_CORELANG
         * @global \Cx\Core\Routing\Url $url
         * @param int $no Hook number
         */
        protected function legacyGlobalsHook($no) {
            global $objFWUser, $objTemplate, $cl, $objInit, $_LANGID, $_CORELANG, $url;

            switch ($no) {
                case 1:
                    // Request URL
                    $url = $this->request->getUrl();
                    // populate template
                    $objTemplate = $this->template;
                    // populate classloader
                    $cl = $this->cl;
                    break;

                case 2:
                    // Code to set language
                    // @todo: move this to somewhere else
                    // in backend it's in Language->postResolve
                    if ($this->mode == self::MODE_FRONTEND) {
                        $_LANGID = FRONTEND_LANG_ID;
                        $objInit->setFrontendLangId($_LANGID);
                        define('LANG_ID', $_LANGID);

                        // Load interface language data
                        $_CORELANG = $objInit->loadLanguageData('core');
                    }

                    \Env::set('Resolver', $this->resolver);

                    // Resolver code
                    // @todo: move to resolver
                    //expose the virtual language directory to the rest of the cms
                    $virtualLanguageDirectory = $url->getLangDir(true);
                    if (!empty($virtualLanguageDirectory)) {
                        $virtualLanguageDirectory = '/' . $virtualLanguageDirectory;
                    }
                    \Env::set('virtualLanguageDirectory', $virtualLanguageDirectory);
                    // TODO: this constanst used to be located in config/set_constants.php, but needed to be relocated to this very place,
                    // because it depends on Env::get('virtualLanguageDirectory').
                    // Find an other solution; probably best is to replace CONTREXX_SCRIPT_PATH by a prettier method
                    define('CONTREXX_SCRIPT_PATH',
                        $this->codeBaseOffsetPath.
                        \Env::get('virtualLanguageDirectory').
                        '/'.
                        CONTREXX_DIRECTORY_INDEX);
                    break;
            }
        }

        /**
         * Calls pre-resolve hooks
         */
        protected function preResolve() {
            $this->ch->callPreResolveHooks();
        }

        /**
         * Does the resolving
         *
         * For modes other than 'frontend', no actual resolving is done,
         * resolver is just initialized in order to return the correct result
         * for $resolver->getUrl()
         * @todo Implement resolver for backend
         * @todo Is this useful in CLI mode?
         */
        protected function resolve() {
            $this->resolver = new \Cx\Core\Routing\Resolver($this->getRequest()->getUrl(), null, $this->getDb()->getEntityManager(), null, null);
            $this->request->getUrl()->setMode($this->mode);

            if ($this->mode == self::MODE_FRONTEND) {
                $this->resolvedPage = $this->resolver->resolve();
                return;
            }

            global $cmd, $act, $plainCmd;

            // resolve pretty url's
            $path = preg_replace('#^' . $this->getWebsiteOffsetPath() . '(' . $this->getBackendFolderName() . ')?/#', '', $_GET['__cap']);
            if ($path != 'index.php' && $path != '') {
                $path = explode('/', $path, 2);
                if (!isset($_GET['cmd'])) {
                    $_REQUEST['cmd'] = $path[0];
                    $_GET['cmd'] = $_REQUEST['cmd'];
                }
                if (isset($path[1])) {
                    if (substr($path[1], -1, 1) == '/') {
                        $path[1] = substr($path[1], 0, -1);
                    }
                    if (!isset($_GET['act'])) {
                        $_REQUEST['act'] = $path[1];
                        $_GET['act'] = $_REQUEST['act'];
                    }
                }
            }

            $this->resolvedPage = new \Cx\Core\ContentManager\Model\Entity\Page();
            $this->resolvedPage->setVirtual(true);

            if (!isset($plainCmd)) {
                $cmd = isset($_REQUEST['cmd']) ? $_REQUEST['cmd'] : 'Home';
                $act = isset($_REQUEST['act']) ? $_REQUEST['act'] : '';
                $plainCmd = $cmd;
            }
        }

        /**
         * Calls post-resolve hooks
         * @todo Remove usage of globals
         */
        protected function postResolve() {
            $this->getResponse()->setPage($this->getPage());
            $this->ch->callPostResolveHooks();
            $this->ch->callAdjustResponseHooks($this->getResponse());
        }

        /**
         * Calls hooks before content is processed
         * @todo Remove usage of globals
         * @global null $moduleStyleFile
         * @global type $plainCmd
         * @global type $plainSection
         * @global type $themesPages
         * @global type $page_template
         */
        protected function preContentLoad() {
            global $moduleStyleFile, $plainCmd, $plainSection, $themesPages, $page_template;

            $this->ch->callPreContentLoadHooks();

            if ($this->mode == self::MODE_FRONTEND) {
                // Set parse target
                $themeRepo = new \Cx\Core\View\Model\Repository\ThemeRepository();
                $page = $this->getPage();
                $resolvedTheme = $themeRepo->getDefaultTheme(
                    \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_WEB,
                    $page->getLang()
                );
                if ($page->getSkin()) {
                    $theme = $themeRepo->findById($page->getSkin());
                    if ($theme) {
                        $resolvedTheme = $theme;
                    }
                }
                // TODO: Move template initialization to here instead
                $this->template->setParseTarget($resolvedTheme);

                // load content.html template (or customized version)
                $this->template->setTemplate($themesPages['index']);
                $this->template->addBlock('CONTENT_FILE', 'page_template', $page_template);

                // load application content template
                $this->loadContentTemplateOfPage();

                // Set global content variables.
                $pageContent = $this->resolvedPage->getContent();
                $this->parseGlobalPlaceholders($pageContent);
                $pageContent = str_replace('{TITLE}', $this->resolvedPage->getTitle(), $pageContent);
                //replace the {NODE_<ID>_<LANG>}- placeholders
                \LinkGenerator::parseTemplate($pageContent);
                $this->resolvedPage->setContent($pageContent);

                $moduleStyleFile = null;
            } else if ($this->mode == self::MODE_BACKEND) {
                // Skip the nav/language bar for modules which don't make use of either.
                // TODO: Remove language selector for modules which require navigation but bring their own language management.
                if ($this->ch->isLegacyComponent($plainCmd)) {
                    $this->template->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
                }
                $plainSection = $plainCmd;
            }
        }

        /**
         * Set main template placeholders required before parsing the content
         * @todo Does this even make any sense? Couldn't simply everything be set after content parsing?
         * @todo Remove usage of globals
         * @global array $_CONFIG
         * @param string $content
         */
        public function parseGlobalPlaceholders(&$content) {
            global $_CONFIG;

            $content = str_replace('{PAGE_URL}',            htmlspecialchars(\Env::get('init')->getPageUri()), $content);
            $content = str_replace('{PAGE_URL_ENCODED}',    urlencode(\Env::get('init')->getPageUri()->toString()), $content);
            $content = str_replace('{LOGOUT_URL}',          contrexx_raw2xhtml(\Env::get('init')->getUriBy('section', 'logout')),  $content);
            $content = str_replace('{GOOGLE_MAPS_API_KEY}', isset($_CONFIG['googleMapsAPIKey']) ? contrexx_raw2xhtml($_CONFIG['googleMapsAPIKey']) : '', $content);
        }

        /**
         * This parses the content
         *
         * This cannot be used in mode self::MODE_COMMAND, since content is added to template directly
         * @todo Write a method, that only returns the content, in order to allow usage in CLI mode
         * @todo Remove usage of globals
         * @global type $plainSection
         * @global type $_ARRAYLANG
         */
        protected function loadContent() {
            global $plainSection, $_ARRAYLANG;

            if ($this->mode == self::MODE_COMMAND) {
                return;
            }

            // init module language
            $_ARRAYLANG = \Env::get('init')->loadLanguageData($plainSection);

            // load module
            if (empty($plainSection) && $this->mode != self::MODE_BACKEND) {
                return;
            }

            $this->ch->callPreContentParseHooks();
            $this->ch->loadComponent($this, $plainSection, $this->resolvedPage);

            // This would be a postContentParseHook:
            \Message::show();

            $this->ch->callPostContentParseHooks();
        }

        protected function loadContentTemplateOfPage() {
            global $plainSection;

            try {
                $contentTemplate = self::getContentTemplateOfPage($this->resolvedPage, $plainSection);

                // In case $contentTemplate is empty, do not replace placeholder {APPLICATION_DATA}.
                // In such cases the loaded component does have the opportunity to manulay parse {APPLICATION_DATA} itself.
                if (empty($contentTemplate)) {
                    return;
                }

                $this->resolvedPage->setContent(str_replace('{APPLICATION_DATA}', $contentTemplate, $this->resolvedPage->getContent()));
            } catch (\Exception $e) {
                throw new \Exception('Error Loading the content template:' . $e);
            }
        }

        /**
         * Fetch the application template of a content page.
         * @param \Cx\Core\ContentManager\Model\Entity\Page $page The page object of which to fetch the application template from
         * @param String $component Optional argument to specify the component to load the template from, instead of using the page's module-attribute
         * @param String $themeType Optional argument to specify the output channel
         * @return String The content of the application template
         */
        public static function getContentTemplateOfPage($page, $component = null, $themeType = \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_WEB) {
            $content = static::getContentTemplateOfPageWithoutWidget(
                $page,
                $component,
                $themeType
            );

            // Components should not call this method. Instead they should set
            // the correct template to the page directly. This requires the
            // page to have a Sigma template as content.
            if (static::instanciate()->getComponent('Widget')) {
                $template = new \Cx\Core_Modules\Widget\Model\Entity\Sigma();
                $template->setTemplate($content);
                static::instanciate()->getComponent('Widget')->parseWidgets(
                    $template,
                    'ContentManager',
                    'Page',
                    static::instanciate()->getPage()->getId()
                );
                $content = $template->get();
            }
            return $content;
        }

        /**
         * Fetch the application template of a content page.
         * @param \Cx\Core\ContentManager\Model\Entity\Page $page The page object of which to fetch the application template from
         * @param String $component Optional argument to specify the component to load the template from, instead of using the page's module-attribute
         * @param String $themeType Optional argument to specify the output channel
         * @return String The content of the application template
         */
        public static function getContentTemplateOfPageWithoutWidget($page, $component = null, $themeType = \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_WEB) {
            try {
                $component        = empty($component) ? $page->getModule() : $component;
                $cmd              = !$page->getCmd() ? 'Default' : ucfirst($page->getCmd());
                $customAppTemplate= !$page->getApplicationTemplate() ? $cmd.'.html' : $page->getApplicationTemplate();
                $moduleFolderName = contrexx_isCoreModule($page->getModule()) ? 'core_modules' : 'modules';
                $themeFolderName  = \Env::get('init')->getCurrentThemesPath();

                // use application template for all output channels
                if ($page->getUseCustomApplicationTemplateForAllChannels()) {
                    $themeRepo = new \Cx\Core\View\Model\Repository\ThemeRepository();
                    // Skin is '0' if set to "default"
                    if (!$page->getSkin()) {
                        $theme = $themeRepo->getDefaultTheme(
                            \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_WEB,
                            $page->getLang()
                        );
                    } else {
                        $theme = $themeRepo->findById($page->getSkin());
                    }
                    $themeFolderName = $theme->getFoldername();
                }

                // use default theme in case a custom set theme is no longer available
                if (empty($themeFolderName)) {
                    $themeRepo       = new \Cx\Core\View\Model\Repository\ThemeRepository();
                    $themeFolderName = $themeRepo->getDefaultTheme($themeType, $page->getLang())->getFoldername();
                }

                $cx = \Cx\Core\Core\Controller\Cx::instanciate();
                // load custom application template from page's theme
                $themePath = $cx->getClassLoader()->getFilePath($cx->getWebsiteThemesPath() .'/'.$themeFolderName.'/'.$moduleFolderName.'/'.$component.'/Template/Frontend/'.$customAppTemplate);
                if ($themePath) {
                    return file_get_contents($themePath);
                }

                // load default application template from page's theme
                if ($customAppTemplate != $cmd.'.html') {
                    $themePath = $cx->getClassLoader()->getFilePath($cx->getWebsiteThemesPath() .'/'.$themeFolderName.'/'.$moduleFolderName.'/'.$component.'/Template/Frontend/'.$cmd.'.html');
                    if ($themePath) {
                        return file_get_contents($themePath);
                    }
                }

                // load default application template from component
                $modulePath = $cx->getClassLoader()->getFilePath($cx->getCodeBaseDocumentRootPath() . '/'.$moduleFolderName.'/'.$component.'/View/Template/Frontend/'.$cmd.'.html');
                if ($modulePath) {
                    return file_get_contents($modulePath);
                }
                return;
            } catch (\Exception $e) {
                throw new \Exception('Error fetching the content template:' . $e);
            }
        }


        /**
         * Calls hooks after content was processed
         */
        protected function postContentLoad() {
            $this->ch->callPostContentLoadHooks();
        }

        /**
         * Set main template placeholders required after content parsing
         * @todo Remove usage of globals
         * @global array $_CONFIG
         * @global type $themesPages
         * @global type $objBanner
         * @global type $_CORELANG
         * @return type
         */
        protected function setPostContentLoadPlaceholders() {
            global $_CONFIG, $themesPages, $objBanner, $_CORELANG;

            if ($this->mode == self::MODE_BACKEND) {
                $formattedVersion = htmlentities(
                    $_CONFIG['coreCmsName'],
                    ENT_QUOTES,
                    CONTREXX_CHARSET
                ) . ' ' .
                htmlentities(
                    str_replace(
                        ' Service Pack 0',
                        '',
                        preg_replace(
                            '#^(\d+\.\d+)\.(\d+)$#',
                            '$1 Service Pack $2',
                            $_CONFIG['coreCmsVersion'])
                    ),
                    ENT_QUOTES,
                    CONTREXX_CHARSET
                ) . ' ' .
                htmlentities(
                    $_CONFIG['coreCmsEdition'],
                    ENT_QUOTES,
                    CONTREXX_CHARSET
                ) . ' ' .
                htmlentities(
                    $_CONFIG['coreCmsStatus'],
                    ENT_QUOTES,
                    CONTREXX_CHARSET
                );
                $this->template->setGlobalVariable(array(
                    'TXT_FRONTEND'              => $_CORELANG['TXT_FRONTEND'],
                    'TXT_UPGRADE'               => $_CORELANG['TXT_UPGRADE'],
                    'TXT_FEEDBACK_AND_HELP'     => $_CORELANG['TXT_FEEDBACK_AND_HELP'],
                    'CONTREXX_VERSION'          => $formattedVersion,
                ));
                $this->template->setVariable(array(
                    'TXT_LOGOUT'                => $_CORELANG['TXT_LOGOUT'],
                    'TXT_PAGE_ID'               => $_CORELANG['TXT_PAGE_ID'],
                    'CONTAINER_BACKEND_CLASS'   => 'backend',
                    'CONTREXX_CHARSET'          => CONTREXX_CHARSET,
                ));
                //show Feedback and help block
                (\Permission::checkAccess(192, 'static', true)) ? $this->template->touchBlock('feedback_help') : $this->template->hideBlock('feedback_help');

                return;
            }

            $objCounter              = null;
            $componentRepo           = $this->getDb()->getEntityManager()->getRepository('Cx\Core\Core\Model\Entity\SystemComponent');
            $statsComponentContoller = $componentRepo->findOneBy(array('name' => 'Stats'));
            if ($statsComponentContoller) {
                $objCounter = $statsComponentContoller->getCounterInstance();
            }

            // set global template variables
            $boolShop = \Cx\Modules\Shop\Controller\Shop::isInitialized();
            $objNavbar = new \Navigation($this->resolvedPage->getId(), $this->resolvedPage);
            $googleAnalyticsId = '';
            if (isset($_CONFIG['googleAnalyticsTrackingId'])) {
                $googleAnalyticsId = contrexx_raw2xhtml(
                    $_CONFIG['googleAnalyticsTrackingId']
                );
            }
            $googleAnalyticsCode = 'window.ga=window.ga||function(){(ga.q=ga.q||[]).push(arguments)};ga.l=+new Date;
                ga(\'create\', \'' . $googleAnalyticsId . '\', \'auto\');
                ' . ($objCounter->arrConfig['exclude_identifying_info']['status'] ? 'ga(\'set\', \'anonymizeIp\', true);' : '') . '
                ga(\'send\', \'pageview\');';
            if (
                \Cx\Core\Setting\Controller\Setting::getValue(
                    'cookieNote',
                    'Config'
                ) == 'on'
            ) {
                $googleAnalyticsCode = ' function cxCookieNoteAccepted() {' .
                    $googleAnalyticsCode . '}';
            }

            $this->template->setVariable(array(
                'CONTENT_TEXT'                   => $this->resolvedPage->getContent(),
                'LOGOUT_URL'                     => contrexx_raw2xhtml(\Env::get('init')->getUriBy('section', 'logout')),
                'PAGE_URL'                       => htmlspecialchars(\Env::get('init')->getPageUri()),
                'PAGE_URL_ENCODED'               => urlencode(\Env::get('init')->getPageUri()->toString()),
                'CURRENT_URL'                    => contrexx_raw2xhtml(\Env::get('init')->getCurrentPageUri()),
                'NAVTREE'                        => $objNavbar->getTrail(),
                'SUBNAVBAR_FILE'                 => $objNavbar->getSubnavigation($themesPages['subnavbar'], $this->license, $boolShop),
                'SUBNAVBAR2_FILE'                => $objNavbar->getSubnavigation($themesPages['subnavbar2'], $this->license, $boolShop),
                'SUBNAVBAR3_FILE'                => $objNavbar->getSubnavigation($themesPages['subnavbar3'], $this->license, $boolShop),
                'NAVBAR_FILE'                    => $objNavbar->getNavigation($themesPages['navbar'], $this->license, $boolShop),
                'NAVBAR2_FILE'                   => $objNavbar->getNavigation($themesPages['navbar2'], $this->license, $boolShop),
                'NAVBAR3_FILE'                   => $objNavbar->getNavigation($themesPages['navbar3'], $this->license, $boolShop),
                'ONLINE_USERS'                   => $objCounter ? $objCounter->getOnlineUsers() : '',
                'VISITOR_NUMBER'                 => $objCounter ? $objCounter->getVisitorNumber() : '',
                'COUNTER'                        => $objCounter ? $objCounter->getCounterTag() : '',
                'BANNER'                         => isset($objBanner) ? $objBanner->getBannerJS() : '',
                'RANDOM'                         => md5(microtime()),
                'TXT_SEARCH'                     => $_CORELANG['TXT_SEARCH'],
                'MODULE_INDEX'                   => MODULE_INDEX,
                'LOGIN_URL'                      => '<a href="' . contrexx_raw2xhtml(\Env::get('init')->getUriBy('section', 'Login')) . '" class="start-frontend-editing">' . $_CORELANG['TXT_FRONTEND_EDITING_LOGIN'] . '</a>',
                'FACEBOOK_LIKE_IFRAME'           => '<div id="fb-root"></div>
                                                    <script type="text/javascript">
                                                        (function(d, s, id) {
                                                            var js, fjs = d.getElementsByTagName(s)[0];
                                                            if (d.getElementById(id)) return;
                                                            js = d.createElement(s); js.id = id;
                                                            js.src = "//connect.facebook.net/'.\FWLanguage::getLanguageCodeById(LANG_ID).'_'.strtoupper(\FWLanguage::getLanguageCodeById(LANG_ID)).'/all.js#xfbml=1";
                                                            fjs.parentNode.insertBefore(js, fjs);
                                                        }(document, \'script\', \'facebook-jssdk\'));
                                                    </script>
                                                    <div class="fb-like" data-href="'.ASCMS_PROTOCOL.'://'.$_CONFIG['domainUrl'].contrexx_raw2xhtml(\Env::get('init')->getCurrentPageUri()).'" data-send="false" data-layout="button_count" data-show-faces="false" data-font="segoe ui"></div>',
                'GOOGLE_PLUSONE'                 => '<div class="g-plusone" data-href="'.ASCMS_PROTOCOL.'://'.$_CONFIG['domainUrl'].contrexx_raw2xhtml(\Env::get('init')->getCurrentPageUri()).'"></div>
                                                    <script type="text/javascript">
                                                        window.___gcfg = {lang: \''.\FWLanguage::getLanguageCodeById(LANG_ID).'\'};

                                                        (function() {
                                                            var po = document.createElement(\'script\'); po.type = \'text/javascript\'; po.async = true;
                                                            po.src = \'https://apis.google.com/js/plusone.js\';
                                                            var s = document.getElementsByTagName(\'script\')[0]; s.parentNode.insertBefore(po, s);
                                                        })();
                                                    </script>',
                'TWITTER_SHARE'                  => '<a href="https://twitter.com/share" class="twitter-share-button"
                                                    data-url="'.ASCMS_PROTOCOL.'://'.$_CONFIG['domainUrl'].contrexx_raw2xhtml(\Env::get('init')->getCurrentPageUri()).'" data-lang="'.\FWLanguage::getLanguageCodeById(LANG_ID).'">Twittern</a>
                                                    <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?\'http\':\'https\';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+\'://platform.twitter.com/widgets.js\';fjs.parentNode.insertBefore(js,fjs);}}(document, \'script\', \'twitter-wjs\');</script>',
                'XING_SHARE'                     => '<div data-type="XING/Share" data-counter="right" data-lang="'.\FWLanguage::getLanguageCodeById(LANG_ID).'"></div>
                                                    <script>
                                                        ;(function (d, s) {
                                                            var x = d.createElement(s),
                                                                s = d.getElementsByTagName(s)[0];
                                                            x.src = "https://www.xing-share.com/js/external/share.js";
                                                            s.parentNode.insertBefore(x, s);
                                                        })(document, "script");
                                                    </script>',
            ));

            // Google Analytics will not be parsed in preview
            if (!isset($_GET['pagePreview']) && !isset($_GET['preview'])) {
                $this->template->setVariable(
                    'GOOGLE_ANALYTICS',
                    '<script>
                        var gaProperty = \'' . $googleAnalyticsId . '\';
                        var disableStr = \'ga-disable-\' + gaProperty;
                        if (document.cookie.indexOf(disableStr + \'=true\') > -1) {
                            window[disableStr] = true;
                        }
                        function gaOptout(successMsg) {
                            document.cookie = disableStr + \'=true; expires=Thu, 31 Dec 2099 23:59:59 UTC; path=/\';
                            window[disableStr] = true;
                            alert(successMsg);
                        }
                        ' . $googleAnalyticsCode . '
                    </script>
                    <script async src=\'https://www.google-analytics.com/analytics.js\'></script>'
                );
            }
        }

        /**
         * Calls hooks before finalize() is called
         */
        protected function preFinalize() {
            $this->ch->callPreFinalizeHooks();
        }

        /**
         * Parses the main template in order to finish request
         * @todo Remove usage of globals
         * @global type $themesPages
         * @global null $moduleStyleFile
         * @global array $_CONFIG
         * @global type $subMenuTitle
         * @global type $_CORELANG
         * @global type $plainCmd
         * @global type $cmd
         */
        protected function finalize() {
            global $themesPages, $moduleStyleFile, $_CONFIG,
                    $subMenuTitle, $_CORELANG, $plainCmd, $cmd;

            if ($this->mode == self::MODE_FRONTEND) {
                $this->parseGlobalPlaceholders($themesPages['sidebar']);

                $this->template->setVariable(array(
                    'SIDEBAR_FILE' => $themesPages['sidebar'],
                    'JAVASCRIPT_FILE' => $themesPages['javascript'],
                    'BUILDIN_STYLE_FILE' => $themesPages['buildin_style'],
                    'JAVASCRIPT_LIGHTBOX' =>
                        '<script type="text/javascript" src="lib/lightbox/javascript/mootools.js"></script>
                        <script type="text/javascript" src="lib/lightbox/javascript/slimbox.js"></script>',
                    'JAVASCRIPT_MOBILE_DETECTOR' =>
                        '<script type="text/javascript" src="lib/mobiledetector.js"></script>',
                ));

                if (!empty($moduleStyleFile))
                    $this->template->setVariable(
                        'STYLE_FILE',
                        "<link rel=\"stylesheet\" href=\"$moduleStyleFile\" type=\"text/css\" media=\"screen, projection\" />"
                    );

                if (!$this->resolvedPage->getUseSkinForAllChannels() && isset($_GET['pdfview']) && intval($_GET['pdfview']) == 1) {
                    $pageTitle  = $this->resolvedPage->getTitle();
                    $extenstion = empty($pageTitle) ? null : '.pdf';
                    $objPDF     = new \Cx\Core_Modules\Pdf\Model\Entity\PdfDocument();
                    $objPDF->SetTitle($pageTitle . $extenstion);
                    $endcode = $this->template->get();
                    $endcode = $this->getComponent(
                        'Cache'
                    )->internalEsiParsing(
                        $endcode
                    );
                    $objPDF->setContent($endcode);
                    $objPDF->Create();
                    exit;
                }

                // fetch the parsed webpage
                $this->template->setVariable('JAVASCRIPT', 'javascript_inserting_here');
                $endcode = $this->template->get();

// TODO: The following code should be moved to ComponentController of Wysiwyg component.
//       To make the following code work in the mentioned controller, we have to
//       refactor the code of this method (finalize()) first. The functionality of \JS
//       should also be moved into a proper ComponentController
                if (strpos($endcode, 'data-shadowbox') !== false) {
                    $jsCode = <<<JSCODE
cx.ready(function() {
    jQuery('img[data-shadowbox]').wrap(function() {
        return jQuery('<a></a>').attr({
            href: jQuery(this).attr('data-shadowbox'),
            class: 'shadowbox'
        });
    })
    if (jQuery('a.shadowbox').length) {
        Shadowbox.setup(jQuery('a.shadowbox'));
    }
});
JSCODE;
                    \JS::registerCode($jsCode);
                    \JS::activate('shadowbox');
                }

                /**
                 * Get all javascripts in the code, replace them with nothing, and register the js file
                 * to the javascript lib. This is because we don't want something twice, and there could be
                 * a theme that requires a javascript, which then could be used by a module too and therefore would
                 * be loaded twice.
                 */
                /* Finds all uncommented script tags, strips them out of the HTML and
                 * stores them internally so we can put them in the placeholder later
                 * (see JS::getCode() below)
                 */
                \JS::findJavascripts($endcode);
                /*
                 * Proposal:  Use this
                 *     $endcode = preg_replace_callback('/<script\s.*?src=(["\'])(.*?)(\1).*?\/?>(?:<\/script>)?/i', array('JS', 'registerFromRegex'), $endcode);
                 * and change JS::registerFromRegex to use index 2
                 */
                // i know this is ugly, but is there another way
                $endcode = str_replace('javascript_inserting_here', \JS::getCode(), $endcode);

                // do a final replacement of all those node-urls ({NODE_<ID>_<LANG>}- placeholders) that haven't been captured earlier
                $endcode = preg_replace('/\\[\\[([A-Z0-9_-]+)\\]\\]/', '{\\1}', $endcode);
                \LinkGenerator::parseTemplate($endcode);

                // remove the meta tag X-UA-Compatible if the user agent ist neighter internet explorer nor chromeframe
                if(!preg_match('/(msie|chromeframe)/i', $_SERVER['HTTP_USER_AGENT'])) {
                    $endcode = preg_replace('/<meta.*?X-UA-Compatible.*?>/i', '', $endcode);
                }

                // replace links from before contrexx 3
                $ls = new \LinkSanitizer(
                    $this,
                    $this->getCodeBaseOffsetPath() . \Env::get('virtualLanguageDirectory') . '/',
                    $endcode
                );
                $this->getResponse()->setParsedContent($ls->replace());
            } else {
                // backend meta navigation
                if ($this->template->blockExists('backend_metanavigation')) {
                    // parse language navigation
                    if ($this->template->blockExists('backend_language_navigation') && $this->template->blockExists('backend_language_navigation_item')) {
                        $backendLanguage = \FWLanguage::getActiveBackendLanguages();
                        if (count($backendLanguage) > 1) {
                            $this->template->setVariable('TXT_LANGUAGE', $_CORELANG['TXT_LANGUAGE']);
                            foreach ($backendLanguage as $language) {
                                $languageUrl = \Env::get('init')->getUriBy('setLang', $language['id']);
                                $this->template->setVariable(array(
                                    'LANGUAGE_URL' => contrexx_raw2xhtml($languageUrl),
                                    'LANGUAGE_NAME' => $language['name'],
                                    'LANGUAGE_CSS' => \Env::get('init')->getBackendLangId() == $language['id'] ? 'active' : '',
                                ));
                                $this->template->parse('backend_language_navigation_item');
                            }
                            $this->template->parse('backend_language_navigation');
                        } else {
                            $this->template->hideBlock('backend_language_navigation');
                        }
                    }

                    $this->template->touchBlock('backend_metanavigation');
                }

                $objAdminNav = new \adminMenu($plainCmd);
                $objAdminNav->getAdminNavbar();
                $this->template->setVariable(array(
                    'SUB_MENU_TITLE' => $subMenuTitle,
                    'FRONTEND_LANG_MENU' => \Env::get('init')->getUserFrontendLangMenu(),
                    'TXT_GENERATED_IN' => $_CORELANG['TXT_GENERATED_IN'],
                    'TXT_SECONDS' => $_CORELANG['TXT_SECONDS'],
                    'TXT_LOGOUT_WARNING' => $_CORELANG['TXT_LOGOUT_WARNING'],
                    'LOGGED_NAME' => htmlentities($this->getUser()->objUser->getProfileAttribute('firstname').' '.$this->getUser()->objUser->getProfileAttribute('lastname'), ENT_QUOTES, CONTREXX_CHARSET),
                    'TXT_LOGGED_IN_AS' => $_CORELANG['TXT_LOGGED_IN_AS'],
                    'TXT_LOG_OUT' => $_CORELANG['TXT_LOG_OUT'],
                // TODO: This function call returns the empty string -- always!  What's the use?
                //    'CONTENT_WYSIWYG_CODE' => get_wysiwyg_code(),
                    // Mind: The module index is not used in any non-module template
                    // for the time being, but is provided for future use and convenience.
                    'MODULE_INDEX' => MODULE_INDEX,
                    // The Shop module for one heavily uses custom JS code that is properly
                    // handled by that class -- finally
                    'JAVASCRIPT' => \JS::getCode(),
                    //CX Placeholders
                    'CX_EDITION'       => $_CONFIG['coreCmsEdition'],
                    'CX_VERSION'       => $_CONFIG['coreCmsVersion'],
                    'CX_CODE_NAME'     => $_CONFIG['coreCmsCodeName'],
                    'CX_STATUS'        => $_CONFIG['coreCmsStatus'],
                    'CX_NAME'          => $_CONFIG['coreCmsName'],
                ));


                // Style parsing
                if (file_exists($this->codeBaseAdminTemplatePath . '/css/'.$cmd.'.css')) {
                    // check if there's a css file in the core section
                    $this->template->setVariable('ADD_STYLE_URL', $this->codeBaseAdminTemplateWebPath .'/css/'.$cmd.'.css');
                    $this->template->parse('additional_style');
                } elseif (file_exists($this->codeBaseModulePath . '/'.$cmd.'/template/backend.css')) {
                    // of maybe in the current module directory
                    $this->template->setVariable('ADD_STYLE_URL', $this->codeBaseModuleWebPath . '/'.$cmd.'/template/backend.css');
                    $this->template->parse('additional_style');
                } elseif (file_exists($this->codeBaseCoreModulePath . '/'.$cmd.'/template/backend.css')) {
                    // or in the core module directory
                    $this->template->setVariable('ADD_STYLE_URL', $this->codeBaseCoreModuleWebPath . '/'.$cmd.'/template/backend.css');
                    $this->template->parse('additional_style');
                } else {
                    $this->template->hideBlock('additional_style');
                }

                /*echo '<pre>';
                print_r($_SESSION);
                /*echo '<b>Overall time: ' . (microtime(true) - $timeAtStart) . 's<br />';
                echo 'Max RAM usage: ' . formatBytes(memory_get_peak_usage()) . '<br />';
                echo 'End RAM usage: ' . formatBytes(memory_get_usage()) . '<br /></b>';*/

                $endcode = $this->template->get();

                // replace links from before contrexx 3
                $ls = new \LinkSanitizer(
                    $this,
                    $this->getCodeBaseOffsetPath() . $this->getBackendFolderName() . '/',
                    $endcode
                );
                $this->getResponse()->setParsedContent($ls->replace());
            }

            \DBG::writeFinishLine($this, false);
        }

        /**
         * Calls hooks after call to finalize()
         */
        protected function postFinalize() {
            $endcode = $this->getResponse()->getParsedContent();
            $this->ch->callPostFinalizeHooks($endcode);
            $this->getResponse()->setParsedContent($endcode);
        }

        /* SETTERS AND GETTERS */

        /**
         * Returns the mode this instance of Cx is in
         * @return string One of 'cli', 'frontend', 'backend', 'minimal'
         */
        public function getMode() {
            return $this->mode;
        }

        /**
         * Returns the request URL
         * @return \Cx\Core\Routing\Model\Entity\Request
         */
        public function getRequest() {
            return $this->request;
        }

        /**
         * Returns the Response object
         * @return \Cx\Core\Routing\Model\Entity\Response Response object
         */
        public function getResponse() {
            return $this->response;
        }

        /**
         * Returns the main template
         * @return \Cx\Core\Html\Sigma Main template
         */
        public function getTemplate() {
            return $this->template;
        }

        /**
         * Returns the resolved page
         *
         * Please note, that this works only if mode is self::MODE_FRONTEND by now
         * If resolving has not taken place yet, null is returned
         * @return \Cx\Core\ContentManager\Model\Entity\Page Resolved page or null
         */
        public function getPage() {
            return $this->resolvedPage;
        }

        /**
         * Returns the current user object
         * @return \FWUser Current user
         */
        public function getUser() {
            return \FWUser::getFWUserObject();
        }

        /**
         * Returns the Cloudrexx event manager instance
         * @return \Cx\Core\Event\Controller\EventManager
         */
        public function getEvents() {
            return $this->eventManager;
        }

        /**
         * Returns the toolbox
         * @return \FWSystem Toolbox
         */
        public function getToolbox() {
            if (!$this->toolbox) {
                $this->toolbox = new \FWSystem();
            }
            return $this->toolbox;
        }

        /**
         * Returns the database connection handler
         * @return \Cx\Core\Model\Db DB connection handler
         */
        public function getDb() {
            return $this->db;
        }

        /**
         * Returns the license for this instance
         * @return \Cx\Core_Modules\License\License
         */
        public function getLicense() {
            return $this->license;
        }

        /**
         * Return ClassLoader instance
         * @return \Cx\Core\ClassLoader\ClassLoader
         */
        public function getClassLoader() {
            return $this->cl;
        }

        public function getComponentHandler() {
            return $this->ch;
        }

        public function getCommands($params = array(), $forceRegen = false) {
            if (count($this->commands) && !$forceRegen) {
                return $this->commands;
            }
            // build command index
            $componentRepo = $this->getDb()->getEntityManager()->getRepository('Cx\Core\Core\Model\Entity\SystemComponent');
            $this->commands = array();
            foreach ($componentRepo->findAll() as $component) {
                foreach ($component->getCommandsForCommandMode() as $cmdKey => $cmdValue) {
                    $command = ($cmdValue && $cmdValue instanceof \Cx\Core_Modules\Access\Model\Entity\Permission) ? $cmdKey : $cmdValue;
                    if (isset($this->commands[$command])) {
                        throw new \Exception('Command \'' . $command . '\' is already in index');
                    }
                    if (!$component->hasAccessToExecuteCommand($command, $params)) {
                        continue;
                    }
                    $this->commands[$command] = $component;
                }
            }
            return $this->commands;
        }

        /**
         * Return the folder name of the storage location of the configuration files (/config).
         * @return string
         */
        public function getConfigFolderName() {
            return self::FOLDER_NAME_CONFIG;
        }

        /**
         * Return the folder name of the storage location of the core components(/core).
         * Formerly known as ASCMS_CORE_FOLDER.
         * @return string
         */
        public function getCoreFolderName() {
            return self::FOLDER_NAME_CORE;
        }

        /**
         * Return the folder name used to access the backend of the website (/cadmin).
         * Formerly known as ASCMS_BACKEND_PATH
         * @return string
         */
        public function getBackendFolderName() {
            return self::FOLDER_NAME_BACKEND;
        }

        /**
         * Return the folder name used for the core_modules storage location (/core_modules).
         * Formerly known as ASCMS_CORE_MODULE_FOLDER.
         * @return string
         */
        public function getCoreModuleFolderName() {
            return self::FOLDER_NAME_CORE_MODULE;
        }

        /**
         * Return the folder name used for the lib storage location (/lib).
         * Formerly known as ASCMS_LIBRARY_FOLDER.
         * @return string
         */
        public function getLibraryFolderName() {
            return self::FOLDER_NAME_LIBRARY;
        }


        /**
         * Return the folder name used for the model storage location (/model).
         * Formerly known as ASCMS_MODEL_FOLDER.
         * @return string
         */
        public function getModelFolderName() {
            return self::FOLDER_NAME_MODEL;
        }


        /**
         * Return the folder name used for the modules storage location (/modules).
         * Formerly known as ASCMS_MODULE_FOLDER.
         * @return string
         */
        public function getModuleFolderName() {
            return self::FOLDER_NAME_MODULE;
        }

        /**
         * Return the folder name used for the themes (/themes).
         * @return string
         */
        public function getThemesFolderName() {
            return self::FOLDER_NAME_THEMES;
        }

        /**
         * Returns a list of system folders
         * Contains all folders that are re-routed to Cloudrexx by .htaccess
         * @return array List of folders relative to website offset path
         */
        public function getSystemFolders() {
            return array(
                $this->getBackendFolderName(),
                $this->getConfigFolderName(),
                $this->getCoreFolderName(),
                $this->getCoreModuleFolderName(),
                static::FOLDER_NAME_CUSTOMIZING,
                static::FOLDER_NAME_FEED,
                static::FOLDER_NAME_IMAGES,
                '/installer',
                '/lang',
                $this->getLibraryFolderName(),
                static::FOLDER_NAME_MEDIA,
                $this->getModelFolderName(),
                $this->getModuleFolderName(),
                $this->getThemesFolderName(),
                static::FOLDER_NAME_TEMP,
                static::FOLDER_NAME_COMMAND_MODE,
            );
        }

        /**
         * Set the path to the location of the website's Code Base in the file system.
         * @param string The base path of the Code Base (webserver's DocumentRoot path).
         * @param string The offset path from the webserver's DocumentRoot to the
         *               location of the Code Base of the Cloudrexx installation.
         */
        public function setCodeBaseRepository($codeBasePath, $codeBaseOffsetPath) {
            $this->codeBasePath                 = $codeBasePath;
            $this->codeBaseOffsetPath           = $codeBaseOffsetPath;
            $this->codeBaseDocumentRootPath     = $this->codeBasePath . $this->codeBaseOffsetPath;
            $this->codeBaseConfigPath           = $this->codeBaseDocumentRootPath . self::FOLDER_NAME_CONFIG;
            $this->codeBaseCorePath             = $this->codeBaseDocumentRootPath . self::FOLDER_NAME_CORE;
            $this->codeBaseCoreWebPath          = $this->codeBaseOffsetPath . self::FOLDER_NAME_CORE;
            $this->codeBaseAdminTemplatePath    = $this->codeBaseCorePath . '/Core/View/Template/Backend';
            $this->codeBaseAdminTemplateWebPath = $this->codeBaseCoreWebPath . '/Core/View/Template/Backend';
            $this->codeBaseCoreModulePath       = $this->codeBaseDocumentRootPath . self::FOLDER_NAME_CORE_MODULE;
            $this->codeBaseCoreModuleWebPath    = $this->codeBaseOffsetPath . self::FOLDER_NAME_CORE_MODULE;
            $this->codeBaseLibraryPath          = $this->codeBaseDocumentRootPath . self::FOLDER_NAME_LIBRARY;
            $this->codeBaseFrameworkPath        = $this->codeBaseLibraryPath . '/FRAMEWORK';
            $this->codeBaseModelPath            = $this->codeBaseDocumentRootPath . self::FOLDER_NAME_MODEL;
            $this->codeBaseModulePath           = $this->codeBaseDocumentRootPath . self::FOLDER_NAME_MODULE;
            $this->codeBaseModuleWebPath        = $this->codeBaseOffsetPath . self::FOLDER_NAME_MODULE;
            $this->codeBaseThemesPath           = $this->codeBaseDocumentRootPath . self::FOLDER_NAME_THEMES;
        }

        /**
         * Return the base path of the Code Base (webserver's DocumentRoot path).
         * Formerly known as ASCMS_PATH.
         * @return string
         */
        public function getCodeBasePath() {
            return $this->codeBasePath;
        }

        /**
         * Return the offset path from the webserver's DocumentRoot to the
         * location of the Code Base of the Cloudrexx installation.
         * Formerly known as ASCMS_PATH_OFFSET.
         * @return string
         */
        public function getCodeBaseOffsetPath() {
            return $this->codeBaseOffsetPath;
        }

        /**
         * Return the absolute path to the Code Base of the Cloudrexx installation.
         * Formerly known as ASCMS_DOCUMENT_ROOT.
         * @return string
         */
        public function getCodeBaseDocumentRootPath() {
            return $this->codeBaseDocumentRootPath;
        }

        /**
         * Return the absolute path to the storage location of the
         * configuration files (/config) of the Code Base of the
         * Cloudrexx installation.
         * @return string
         */
        public function getCodeBaseConfigPath() {
            return $this->codeBaseConfigPath;
        }

        /**
         * Return the absolute path to the core components (/core)
         * of the Code Base of the Cloudrexx installation.
         * Formerly known as ASCMS_CORE_PATH.
         * @return string
         */
        public function getCodeBaseCorePath() {
            return $this->codeBaseCorePath;
        }

        /**
         * Return the offset path to the core components (/core)
         * of the Code Base of the Cloudrexx installation.
         * @return string
         */
        public function getCodeBaseCoreWebPath() {
            return $this->codeBaseCoreWebPath;
        }

        /**
         * Return the absolute path used to access the backend template
         * of the Code Base of the Cloudrexx installation
         * Formerly known as ASCMS_ADMIN_TEMPLATE_PATH
         * @return string
         */
        public function getCodeBaseAdminTemplatePath() {
            return $this->codeBaseAdminTemplatePath;
        }

        /**
         * Return the offset path used to access the backend template
         * of the Code Base of the Cloudrexx installation
         * Formerly known as ASCMS_ADMIN_TEMPLATE_WEB_PATH
         * @return string
         */
        public function getCodeBaseAdminTemplateWebPath() {
            return $this->codeBaseAdminTemplateWebPath;
        }

        /**
         * Return the absolute path of the core modules(core_modules) folder
         * of the Code Base of the Cloudrexx installation
         * Formerly known as ASCMS_CORE_MODULE_PATH
         * @return string
         */
        public function getCodeBaseCoreModulePath() {
            return $this->codeBaseCoreModulePath;
        }

        /**
         * Return the offset path of the core modules(core_modules) folder
         * of the Code Base of the Cloudrexx installation
         * Formerly known as ASCMS_CORE_MODULE_WEB_PATH
         * @return string
         */
        public function getCodeBaseCoreModuleWebPath() {
            return $this->codeBaseCoreModuleWebPath;
        }

        /**
         * The absolute path of the lib folder
         * of the Code Base of the Cloudrexx installation
         * Formerly known as ASCMS_LIBRARY_PATH
         * @return string
         */
        public function getCodeBaseLibraryPath() {
            return $this->codeBaseLibraryPath;
        }
        /**
         * Return the absolute path of the FRAMEWORK folder
         * of the Code Base of the Cloudrexx installation
         * Formerly known as ASCMS_FRAMEWORK_PATH
         * @return string
         */
        public function getCodeBaseFrameworkPath() {
            return $this->codeBaseFrameworkPath;
        }
        /**
         * Return the absolute path of the lib folder
         * of the Code Base of the Cloudrexx installation
         * Formerly known as ASCMS_MODEL_PATH
         * @return string
         */
        public function getCodeBaseModelPath() {
            return $this->codeBaseModelPath;
        }

        /**
         * Return the absolute path of the module folder
         * of the Code Base of the Cloudrexx installation
         * Formerly known as ASCMS_MODULE_PATH
         * @return string
         */
        public function getCodeBaseModulePath() {
            return $this->codeBaseModulePath;
        }

        /**
         * Return the offset path of the module folder
         * of the Code Base of the Cloudrexx installation
         * Formerly known as ASCMS_MODULE_WEB_PATH
         * @return string
         */
        public function getCodeBaseModuleWebPath() {
            return $this->codeBaseModuleWebPath;
        }

        /**
         * Return the absolute path to the themes storage location (/themes)
         * of the Code Base of the Cloudrexx installation
         * @return string
         */
        public function getCodeBaseThemesPath() {
            return $this->codeBaseThemesPath;
        }

        /**
         * Set the path to the location of the website's data repository in the file system.
         * @param string $websitePath The absolute path to the website's data repository.
         * @param string $websiteOffsetPath The offset path from the website's data repository to the
         *               location of the Cloudrexx installation if it is run in a subdirectory.
         */
        public function setWebsiteRepository($websitePath, $websiteOffsetPath) {
            $this->websitePath                  = $websitePath;
            $this->websiteOffsetPath            = $websiteOffsetPath;

            $this->websiteDocumentRootPath      = $this->websitePath . $this->websiteOffsetPath;
            $this->websiteConfigPath            = $this->websiteDocumentRootPath . self::FOLDER_NAME_CONFIG;
            $this->websiteCustomizingPath       = $this->websiteDocumentRootPath . self::FOLDER_NAME_CUSTOMIZING;
            $this->websiteCustomizingWebPath    = $this->websiteOffsetPath       . self::FOLDER_NAME_CUSTOMIZING;
            $this->websiteTempPath              = $this->websiteDocumentRootPath . self::FOLDER_NAME_TEMP;
            $this->websiteTempWebPath           = $this->websiteOffsetPath       . self::FOLDER_NAME_TEMP;
            $this->websiteThemesPath            = $this->websiteDocumentRootPath . self::FOLDER_NAME_THEMES;
            $this->websiteThemesWebPath         = $this->websiteOffsetPath       . self::FOLDER_NAME_THEMES;
            $this->websiteFeedPath              = $this->websiteDocumentRootPath . self::FOLDER_NAME_FEED;
            $this->websiteFeedWebPath           = $this->websiteOffsetPath       . self::FOLDER_NAME_FEED;

            $this->websiteImagesPath            = $this->websiteDocumentRootPath . self::FOLDER_NAME_IMAGES;
            $this->websiteImagesWebPath         = $this->websiteOffsetPath       . self::FOLDER_NAME_IMAGES;

            $this->websiteImagesContentPath     = $this->websiteDocumentRootPath . self::FOLDER_NAME_IMAGES . '/content';
            $this->websiteImagesAttachPath      = $this->websiteDocumentRootPath . self::FOLDER_NAME_IMAGES . '/attach';
            $this->websiteImagesShopPath        = $this->websiteDocumentRootPath . self::FOLDER_NAME_IMAGES . '/Shop';
            $this->websiteImagesGalleryPath     = $this->websiteDocumentRootPath . self::FOLDER_NAME_IMAGES . '/Gallery';
            $this->websiteImagesAccessPath      = $this->websiteDocumentRootPath . self::FOLDER_NAME_IMAGES . '/Access';
            $this->websiteImagesMediaDirPath    = $this->websiteDocumentRootPath . self::FOLDER_NAME_IMAGES . '/MediaDir';
            $this->websiteImagesDownloadsPath   = $this->websiteDocumentRootPath . self::FOLDER_NAME_IMAGES . '/Downloads';
            $this->websiteImagesCalendarPath    = $this->websiteDocumentRootPath . self::FOLDER_NAME_IMAGES . '/Calendar';
            $this->websiteImagesPodcastPath     = $this->websiteDocumentRootPath . self::FOLDER_NAME_IMAGES . '/Podcast';
            $this->websiteImagesBlogPath        = $this->websiteDocumentRootPath . self::FOLDER_NAME_IMAGES . '/Blog';
            $this->websiteImagesCrmPath         = $this->websiteDocumentRootPath . self::FOLDER_NAME_IMAGES . '/Crm';
            $this->websiteImagesDataPath        = $this->websiteDocumentRootPath . self::FOLDER_NAME_IMAGES . '/Data';
            $this->websiteImagesCrmProfilePath  = $this->websiteImagesCrmPath . '/profile';
            $this->websiteImagesAccessProfilePath = $this->websiteImagesAccessPath .'/profile';
            $this->websiteImagesAccessPhotoPath = $this->websiteImagesAccessPath .'/photo';
            $this->websiteMediaForumUploadPath  = $this->websiteDocumentRootPath . self::FOLDER_NAME_MEDIA . '/Forum/upload';
            $this->websiteMediaarchive1Path     = $this->websiteDocumentRootPath . self::FOLDER_NAME_MEDIA . '/archive1';
            $this->websiteMediaarchive2Path     = $this->websiteDocumentRootPath . self::FOLDER_NAME_MEDIA . '/archive2';
            $this->websiteMediaarchive3Path     = $this->websiteDocumentRootPath . self::FOLDER_NAME_MEDIA . '/archive3';
            $this->websiteMediaarchive4Path     = $this->websiteDocumentRootPath . self::FOLDER_NAME_MEDIA . '/archive4';
            $this->websiteMediaFileSharingPath  = $this->websiteDocumentRootPath . self::FOLDER_NAME_MEDIA . '/FileSharing';
            $this->websiteMediaMarketPath       = $this->websiteDocumentRootPath . self::FOLDER_NAME_MEDIA . '/Market';
            $this->websiteMediaCrmPath          = $this->websiteDocumentRootPath . self::FOLDER_NAME_MEDIA . '/Crm';
            $this->websiteMediaDirectoryPath    = $this->websiteDocumentRootPath . self::FOLDER_NAME_MEDIA . '/Directory';

            $this->websiteImagesContentWebPath  = $this->websiteOffsetPath . self::FOLDER_NAME_IMAGES . '/content';
            $this->websiteImagesAttachWebPath   = $this->websiteOffsetPath . self::FOLDER_NAME_IMAGES . '/attach';
            $this->websiteImagesShopWebPath     = $this->websiteOffsetPath . self::FOLDER_NAME_IMAGES . '/Shop';
            $this->websiteImagesGalleryWebPath  = $this->websiteOffsetPath . self::FOLDER_NAME_IMAGES . '/Gallery';
            $this->websiteImagesAccessWebPath   = $this->websiteOffsetPath . self::FOLDER_NAME_IMAGES . '/Access';
            $this->websiteImagesMediaDirWebPath = $this->websiteOffsetPath . self::FOLDER_NAME_IMAGES . '/MediaDir';
            $this->websiteImagesDownloadsWebPath= $this->websiteOffsetPath . self::FOLDER_NAME_IMAGES . '/Downloads';
            $this->websiteImagesCalendarWebPath = $this->websiteOffsetPath . self::FOLDER_NAME_IMAGES . '/Calendar';
            $this->websiteImagesPodcastWebPath  = $this->websiteOffsetPath . self::FOLDER_NAME_IMAGES . '/Podcast';
            $this->websiteImagesBlogWebPath     = $this->websiteOffsetPath . self::FOLDER_NAME_IMAGES . '/Blog';
            $this->websiteImagesCrmWebPath      = $this->websiteOffsetPath . self::FOLDER_NAME_IMAGES . '/Crm';
            $this->websiteImagesDataWebPath     = $this->websiteOffsetPath . self::FOLDER_NAME_IMAGES . '/Data';
            $this->websiteImagesCrmProfileWebPath = $this->websiteImagesCrmWebPath . '/profile';
            $this->websiteImagesAccessProfileWebPath = $this->websiteImagesAccessWebPath . '/profile';
            $this->websiteImagesAccessPhotoWebPath   = $this->websiteImagesAccessWebPath . '/photo';
            $this->websiteMediaForumUploadWebPath    = $this->websiteOffsetPath . self::FOLDER_NAME_MEDIA . '/Forum/upload';
            $this->websiteMediaarchive1WebPath  = $this->websiteOffsetPath . self::FOLDER_NAME_MEDIA . '/archive1';
            $this->websiteMediaarchive2WebPath  = $this->websiteOffsetPath . self::FOLDER_NAME_MEDIA . '/archive2';
            $this->websiteMediaarchive3WebPath  = $this->websiteOffsetPath . self::FOLDER_NAME_MEDIA . '/archive3';
            $this->websiteMediaarchive4WebPath  = $this->websiteOffsetPath . self::FOLDER_NAME_MEDIA . '/archive4';
            $this->websiteMediaFileSharingWebPath=$this->websiteOffsetPath . self::FOLDER_NAME_MEDIA . '/FileSharing';
            $this->websiteMediaMarketWebPath     = $this->websiteOffsetPath . self::FOLDER_NAME_MEDIA . '/Market';
            $this->websiteMediaDirectoryWebPath  = $this->websiteOffsetPath . self::FOLDER_NAME_MEDIA . '/Directory';

            $this->websitePublicTempPath        = $this->websiteTempPath    . self::FOLDER_NAME_PUBLIC_TEMP;
            $this->websitePublicTempWebPath     = $this->websiteTempWebPath . self::FOLDER_NAME_PUBLIC_TEMP;
        }

        /**
         * Return the absolute path to the website's data repository.
         * Formerly known as ASCMS_INSTANCE_PATH.
         * @return string
         */
        public function getWebsitePath() {
            return $this->websitePath;
        }

        /**
         * Return the offset path from the website's data repository to the
         * location of the Cloudrexx installation if it is run in a subdirectory.
         * Formerly known as ASCMS_INSTANCE_OFFSET.
         * @return string
         */
        public function getWebsiteOffsetPath() {
                return $this->websiteOffsetPath;
        }

        /**
         * Return the absolute path to the data repository of the Cloudrexx installation.
         * Formerly known as ASCMS_INSTANCE_DOCUMENT_ROOT.
         * @return string
         */
        public function getWebsiteDocumentRootPath() {
            return $this->websiteDocumentRootPath;
        }

        /**
         * Return the absolute path to the storage location of the website's config files.
         * @return string
         */
        public function getWebsiteConfigPath() {
            return $this->websiteConfigPath;
        }

        /**
         * Return the absolute path to the customizing repository of the website.
         * Formerly known as ASCMS_CUSTOMIZING_PATH.
         * @return string
         */
        public function getWebsiteCustomizingPath() {
            return $this->websiteCustomizingPath;
        }

        /**
         * Return the offset path from the website's DocumentRoot to the customizing
         * repository of the website.
         * Formerly known as ASCMS_CUSTOMIZING_WEB_PATH.
         * @return string
         */
        public function getWebsiteCustomizingWebPath() {
            return $this->websiteCustomizingWebPath;
        }

        /**
         * Return the absolute path to the temp storage location (/tmp)
         * of the associated Data repository of the website.
         * Formerly known as ASCMS_TEMP_PATH.
         * @return string
         */
        public function getWebsiteTempPath() {
            return $this->websiteTempPath;
        }

        /**
         * Return the offset path to the temp storage location (/tmp)
         * of the associated Data repository of the website.
         * Formerly known as ASCMS_TEMP_WEB_PATH.
         * @return string
         */
        public function getWebsiteTempWebPath() {
            return $this->websiteTempWebPath;
        }

        /**
         * Return the absolute path to the temp storage location (/tmp)
         * of the associated Data repository of the website.
         * Formerly known as ASCMS_CACHE_PATH.
         * @return string
         */
        public function getWebsiteCachePath() {
            return $this->websiteTempPath . self::FOLDER_NAME_CACHE;
        }

        /**
         * Return the relative path to the backend of the website (/cadmin).
         * @return string
         */
        public function getWebsiteBackendPath() {
            return $this->websiteOffsetPath . self::FOLDER_NAME_BACKEND;
        }

        /**
         * Return the absolute path to the themes storage location (/themes)
         * of the associated Data repository of the website.
         * Formerly known as ASCMS_THEMES_PATH.
         * @return string
         */
        public function getWebsiteThemesPath() {
            return $this->websiteThemesPath;
        }

        /**
         * Return the offset path to the themes storage location (/themes)
         * of the associated Data repository of the website.
         * Formerly known as ASCMS_THEMES_WEB_PATH.
         * @return string
         */
        public function getWebsiteThemesWebPath() {
            return $this->websiteThemesWebPath;
        }

         /**
         * Return the absolute path to the feed storage location (/feed)
         * of the Code Base of the Cloudrexx installation
         * Formerly known as ASCMS_FEED_PATH
         * @return string
         */
        public function getWebsiteFeedPath() {
            return $this->websiteFeedPath;
        }

         /**
         * Return the offset path to the feed storage location (/feed)
         * of the associated Data repository of the website.
         * Formerly known as ASCMS_FEED_WEB_PATH
         * @return string
         */
        public function getWebsiteFeedWebPath() {
            return $this->websiteFeedWebPath;
        }

        /**
         * @return string
         */
        public function getWebsiteImagesContentPath()
        {
            return $this->websiteImagesContentPath;
        }

        /**
         * @return string
         */
        public function getWebsiteImagesContentWebPath()
        {
            return $this->websiteImagesContentWebPath;
        }

        /**
         * @return string
         */
        public function getWebsiteImagesAccessPath()
        {
            return $this->websiteImagesAccessPath;
        }

        /**
         * @return string
         */
        public function getWebsiteImagesAccessWebPath()
        {
            return $this->websiteImagesAccessWebPath;
        }

        /**
         * @return string
         */
        public function getWebsiteImagesAttachPath()
        {
            return $this->websiteImagesAttachPath;
        }

        /**
         * @return string
         */
        public function getWebsiteImagesAttachWebPath()
        {
            return $this->websiteImagesAttachWebPath;
        }

        /**
         * @return string
         */
        public function getWebsiteImagesBlogPath()
        {
            return $this->websiteImagesBlogPath;
        }

        /**
         * @return string
         */
        public function getWebsiteImagesBlogWebPath()
        {
            return $this->websiteImagesBlogWebPath;
        }

        /**
         * @return string
         */
        public function getWebsiteImagesDataPath()
        {
            return $this->websiteImagesDataPath;
        }

        /**
         * @return string
         */
        public function getWebsiteImagesDataWebPath()
        {
            return $this->websiteImagesDataWebPath;
        }

        /**
         * @return string
         */
        public function getWebsiteImagesCalendarPath()
        {
            return $this->websiteImagesCalendarPath;
        }

        /**
         * @return string
         */
        public function getWebsiteImagesCalendarWebPath()
        {
            return $this->websiteImagesCalendarWebPath;
        }

        /**
         * @return string
         */
        public function getWebsiteImagesDownloadsPath()
        {
            return $this->websiteImagesDownloadsPath;
        }

        /**
         * @return string
         */
        public function getWebsiteImagesDownloadsWebPath()
        {
            return $this->websiteImagesDownloadsWebPath;
        }

        /**
         * @return string
         */
        public function getWebsiteImagesGalleryPath()
        {
            return $this->websiteImagesGalleryPath;
        }

        /**
         * @return string
         */
        public function getWebsiteImagesGalleryWebPath()
        {
            return $this->websiteImagesGalleryWebPath;
        }

        /**
         * @return string
         */
        public function getWebsiteImagesMediaDirPath()
        {
            return $this->websiteImagesMediaDirPath;
        }

        /**
         * @return string
         */
        public function getWebsiteImagesMediaDirWebPath()
        {
            return $this->websiteImagesMediaDirWebPath;
        }

        /**
         * @return string
         */
        public function getWebsiteImagesPodcastPath()
        {
            return $this->websiteImagesPodcastPath;
        }

        /**
         * @return string
         */
        public function getWebsiteImagesPodcastWebPath()
        {
            return $this->websiteImagesPodcastWebPath;
        }

        /**
         * @return string
         */
        public function getWebsiteImagesShopPath()
        {
            return $this->websiteImagesShopPath;
        }

        /**
         * @return string
         */
        public function getWebsiteImagesShopWebPath()
        {
            return $this->websiteImagesShopWebPath;
        }

        /**
         * @return string
         */
        public function getWebsiteMediaarchive1Path()
        {
            return $this->websiteMediaarchive1Path;
        }

        /**
         * @return string
         */
        public function getWebsiteMediaarchive1WebPath()
        {
            return $this->websiteMediaarchive1WebPath;
        }

        /**
         * @return string
         */
        public function getWebsiteMediaForumUploadPath()
        {
            return $this->websiteMediaForumUploadPath;
        }

        /**
         * @return string
         */
        public function getWebsiteMediaForumUploadWebPath()
        {
            return $this->websiteMediaForumUploadWebPath;
        }

        /**
         * @return string
         */
        public function getWebsiteMediaarchive2Path()
        {
            return $this->websiteMediaarchive2Path;
        }

        /**
         * @return string
         */
        public function getWebsiteMediaarchive2WebPath()
        {
            return $this->websiteMediaarchive2WebPath;
        }

        /**
         * @return string
         */
        public function getWebsiteMediaarchive3Path()
        {
            return $this->websiteMediaarchive3Path;
        }

        /**
         * @return string
         */
        public function getWebsiteMediaarchive3WebPath()
        {
            return $this->websiteMediaarchive3WebPath;
        }

        /**
         * @return string
         */
        public function getWebsiteMediaarchive4Path()
        {
            return $this->websiteMediaarchive4Path;
        }

        /**
         * @return string
         */
        public function getWebsiteMediaarchive4WebPath()
        {
            return $this->websiteMediaarchive4WebPath;
        }

        /**
         * Return the absolute path to the media FileSharing location (/FileSharing)
         * of the associated Data repository of the website.
         * Formerly known as ASCMS_FILESHARING_PATH.
         *
         * @return string
         */
        public function getWebsiteMediaFileSharingPath()
        {
            return $this->websiteMediaFileSharingPath;
        }

        /**
         * Return the offset path to the media FileSharing location (/FileSharing)
         * of the associated Data repository of the website.
         * Formerly known as ASCMS_FILESHARING_WEB_PATH.
         *
         * @return string
         */
        public function getWebsiteMediaFileSharingWebPath()
        {
            return $this->websiteMediaFileSharingWebPath;
        }

        /**
         * Return the absolute path to the media Market location (/Market)
         * of the associated Data repository of the website.
         * Formerly known as ASCMS_MARKET_MEDIA_PATH.
         *
         * @return string
         */
        public function getWebsiteMediaMarketPath()
        {
            return $this->websiteMediaMarketPath;
        }

        /**
         * Return the offset path to the media Market location (/Market)
         * of the associated Data repository of the website.
         * Formerly known as ASCMS_MARKET_MEDIA_WEB_PATH.
         *
         * @return string
         */
        public function getWebsiteMediaMarketWebPath()
        {
            return $this->websiteMediaMarketWebPath;
        }

        /**
         * @return string
         */
        public function getWebsiteImagesPath()
        {
            return $this->websiteImagesPath;
        }

        /**
         * @return string
         */
        public function getWebsiteImagesWebPath()
        {
            return $this->websiteImagesWebPath;
        }

        /**
         * @return string
         */
        public function getWebsitePublicTempPath() {
            return $this->websitePublicTempPath;
        }

        /**
         * @return string
         */
        public function getWebsitePublicTempWebPath() {
            return $this->websitePublicTempWebPath;
        }

         /**
         * Return the absolute path to the website's data repository to the
         * location of the /images/Crm
         * @return string
         */
        public function getWebsiteImagesCrmPath() {
            return $this->websiteImagesCrmPath;
        }

        /**
         * Return the offset path from the website's data repository to the
         * location of the /images/Crm
         * @return string
         */
        public function getWebsiteImagesCrmWebPath() {
            return $this->websiteImagesCrmWebPath;
        }

        /**
         * Return the absolute path from the website's data repository to the
         * location of the /images/Crm/profile
         * @return string
         */
        public function getWebsiteImagesCrmProfilePath() {
            return $this->websiteImagesCrmProfilePath;
        }

        /**
         * Return the offset path from the website's data repository to the
         * location of the /images/Crm/profile
         * @return string
         */
        public function getWebsiteImagesCrmProfileWebPath() {
            return $this->websiteImagesCrmProfileWebPath;
        }

        /**
         * Return the absolute path from the website's data repository to the
         * location of the /media/Crm
         * @return string
         */
        public function getWebsiteMediaCrmPath() {
            return $this->websiteMediaCrmPath;
        }

        /**
         * Return the absolute path from the website's data repository to the
         * location of the /media/Directory
         * @return string
         */
        public function getWebsiteMediaDirectoryPath() {
            return $this->websiteMediaDirectoryPath;
        }

        /**
         * Return the absolute path to the data repository of the access profile.
         * Formerly known as ASCMS_ACCESS_PROFILE_IMG_PATH.
         * @return string
         */
        public function getWebsiteImagesAccessProfilePath() {
            return $this->websiteImagesAccessProfilePath;
        }

        /**
         * Return the offset path to the data repository of the access profile.
         * Formerly known as ASCMS_ACCESS_PROFILE_IMG_WEB_PATH.
         * @return string
         */
        public function getWebsiteImagesAccessProfileWebPath() {
            return $this->websiteImagesAccessProfileWebPath;
        }

        /**
         * Return the absolute path to the data repository of the access photo.
         * Formerly known as ASCMS_ACCESS_PHOTO_IMG_PATH.
         * @return string
         */
        public function getWebsiteImagesAccessPhotoPath() {
            return $this->websiteImagesAccessPhotoPath;
        }

        /**
         * Return the offset path to the data repository of the access photo.
         * Formerly known as ASCMS_ACCESS_PHOTO_IMG_WEB_PATH.
         * @return string
         */
        public function getWebsiteImagesAccessPhotoWebPath() {
            return $this->websiteImagesAccessPhotoWebPath;
        }

        /**
         * Return the offset path from the website's data repository to the
         * location of the /media/Downloads
         * @return string
         */
        public function getWebsiteMediaDirectoryWebPath() {
            return $this->websiteMediaDirectoryWebPath;
        }

        /**
         * Set the ID of the object
         *
         * WARNING: Setting the ID manually might break the system!
         *          Only do it in respect to self::$autoIncrementValueOfId.
         *
         * @param int   ID this object shall be identified by
         */
        public function setId($id) {
            $this->id = $id;
        }

        /**
         * @return int
         */
        public function getId() {
            return $this->id;
        }

        public function getInstances() {
            return self::$instances;
        }

        /**
         * Get the instance of the MediaSourceManager
         *
         * @return \Cx\Core\MediaSource\Model\Entity\MediaSourceManager
         */
        public function getMediaSourceManager(){
            if (!$this->mediaSourceManager){
                $this->mediaSourceManager = new \Cx\Core\MediaSource\Model\Entity\MediaSourceManager($this);
            }
            return $this->mediaSourceManager;
        }
    }
}
