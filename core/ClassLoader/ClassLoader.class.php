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
 * Cloudrexx ClassLoader
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_classloader
 */

namespace Cx\Core\ClassLoader;

/**
 * Cloudrexx ClassLoader
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_classloader
 */
class ClassLoader {

    private $basePath;
    private $customizingPath;
    private $legacyClassLoader = null;
    private $cx = null;

    /**
     * Local connection to Memcached server
     * @var \Memcached
     */
    protected $memcached = null;

    /**
     * List of loaded classes and their path
     * in the file system
     * @var array
     */
    protected $classMap = array();

    /**
     * Prefix to be used for class-map-key
     * @var string
     */
    const classMapPrefix = 'CxClassMap_';

    /**
     * Key to be used to identify the cached class map
     * in Memcached server
     * @var string
     */
    protected $classMapKey = '';

    /**
     * To use LegacyClassLoader config.php and set_constants.php must be loaded
     * If they are not present, set $useLegacyAsFallback to false!
     * @param String $basePath Base directory to load files (e.g. ASCMS_DOCUMENT_ROOT)
     * @param boolean $useLegacyAsFallback (optional) Wheter to use LegacyClassLoader too (default) or not
     */
// TODO: Fix ClassCloader instantiation in:
//      update/updates/3.2.0/update.php
//      installer/classloader.inc.php
    public function __construct($cx, $useLegacyAsFallback = true, $customizingPath = null) {
        $this->cx = $cx;
        $this->basePath = $cx->getCodeBaseDocumentRootPath();
        $this->customizingPath = $customizingPath;

        // set key to be used for storing the paths of the loaded classes in memcached
        $this->classMapKey = static::classMapPrefix . md5($this->basePath);

        // Check if there is already an other instance of the Cloudrexx ClassLoader running.
        // If so, we shall unregister it.
        if (class_exists('Env', false)) {
            $oldClassLoader = \Env::get('ClassLoader');
            if ($oldClassLoader) {
                spl_autoload_unregister(array($oldClassLoader, 'autoload'));
            }
        }
        spl_autoload_register(array($this, 'autoload'));

        if ($useLegacyAsFallback) {
            $this->legacyClassLoader = new LegacyClassLoader($this, $cx);
        }
    }

    /**
     * This needs to be public because Doctrine tries to load a class using all
     * registered autoloaders.
     * @param type $name
     * @return type void
     */
    public function autoload($name) {
        // init cache to skip resolving class paths
        $this->initClassMapCache();

        // try to fetch class path from class map cache
        if ($this->loadClassFromClassMapCache($name)) {
            return;
        }

        if ($this->load($name, $path)) {
            return;
        }

        $this->loadLegacy($name);
    }

    /**
     * Try to load a PHP class from cache.
     *
     * @param   $name   string The name of the class to be loaded
     * @return  boolean TRUE if the class specified by $name was loaded from
     *                  cache. Otherwise FALSE
     */
    protected function loadClassFromClassMapCache($name) {
        // try to fetch class path from class map cache
        if (!isset($this->memcached)) {
            return false;
        }

        // fetch the class map from cache (if not yet done)
        if (!isset($this->classMap[$this->classMapKey])) {
            $this->classMap[$this->classMapKey] = array();
            $this->classMap[$this->classMapKey] = $this->memcached->get(
                $this->classMapKey
            );
        }

        if (!isset($this->classMap[$this->classMapKey][$name])) {
            return false;
        }

        $path = $this->classMap[$this->classMapKey][$name];
        require_once($path);

        return true;
    }

    /**
     * Initialize the class map cache
     *
     * This method does initialize the Memcached cache in case
     * it has been enabled as user cache engine.
     *
     * @global  $_CONFIG    array   The basic configuration data. We're not
     *                              using the Setting component here, as this
     *                              method usually gets called before the
     *                              postInit hook.
     */
    protected function initClassMapCache() {
        global $_CONFIG;

        if ($this->memcached) {
            return;
        }

        if (!isset($_CONFIG['cacheDbStatus']) ||
            $_CONFIG['cacheDbStatus'] != 'on'
        ) {
            return;
        }

        if (!isset($_CONFIG['cacheUserCache'])) {
            return;
        }

        // note: we can't use the cache engines constats of Cache component here
        // as the Cache component has not yet been loaded at this stage
        switch ($_CONFIG['cacheUserCache']) {
            case 'memcached':
                // default memcached configuration
                $ip = '127.0.0.1';
                $port = '11211';

                // load stored memcached configuration
                if (!empty($_CONFIG['cacheUserCacheMemcachedConfig'])){
                    $settings = json_decode($_CONFIG['cacheUserCacheMemcachedConfig'], true);
                    $ip = $settings['ip'];
                    $port = $settings['port'];
                }

                $memcachedConfiguration = array('ip' => $ip, 'port' => $port);

                // verify that memcached is installed
                if (!extension_loaded('memcached')) {
                    break;
                }

                // verify that memcached is loaded
                if (!class_exists('\Memcached', false)) {
                    break;
                }

                // connect to memcached server
                $memcached = new \Memcached();
                if (!@$memcached->addServer($memcachedConfiguration['ip'], $memcachedConfiguration['port'])) {
                    break;
                }
                $this->memcached = $memcached;
                break;

            default:
                break;
        }
    }

    /**
     * Flushes cached entries from usercache
     *
     * This does not drop the cache files!
     */
    public function flushCache() {
        if (!$this->memcached) {
            return;
        }
        $this->memcached->delete($this->classMapKey);
    }

    private function load($name, &$resolvedPath) {
        $requestedName = $name;
        if (substr($name, 0, 1) == '\\') {
            $name = substr($name, 1);
        }
        $parts = explode('\\', $name);
        // new classes should be in namespace \Cx\something
        // TODO: Use Composer's class autoloading for libraries
        if (
            !in_array(
                current($parts),
                array(
                    'Cx',
                    'Doctrine',
                    'Gedmo',
                    'DoctrineExtension', // /model/extensions
                    'DoctrineExtensions', // /lib/doctrine/beberlei/doctrineextensions/src
                    'Symfony',
                )
            ) ||
            count($parts) < 2
        ) {
            return false;
        }
        if (substr($name, 0, 8) == 'PHPUnit_') {
            return false;
        }

        $suffix = '.class';
        if ($parts[0] == 'Cx') {
            // Exception for model, its within /model/[entities|events]/cx/model/
            if ($parts[1] == 'Model') {
                $third = 'entities';
                if ($parts[2] == 'Events') {
                    $third = 'events';
                }
                if ($parts[2] == 'Proxies') {
                    $tmpPart = implode('', array_slice($parts, 3));
                    $parts   = array('Cx', 'Model', 'Proxies', $tmpPart);
                    $third = 'proxies';
                    $suffix = '';
                }
                $parts = array_merge(array('Cx', 'Model', $third), $parts);

            // Exception for lib, its within /model/FRAMEWORK/
            } else if ($parts[1] == 'Lib') {
                unset($parts[0]);
                unset($parts[1]);
                $parts = array_merge(array('Cx', 'Lib', 'FRAMEWORK'), $parts);
            }

        // Exception for overwritten gedmo classes, they are within /model/entities/Gedmo
        // This is not ideal, maybe move the classes somewhere
        } else if ($parts[0] == 'Gedmo') {
            $suffix = '';
            $parts = array_merge(array('Cx', 'Lib', 'doctrine'), $parts);
            //$parts = array_merge(array('Cx', 'Model', 'entities'), $parts);
        } else if ($parts[0] == 'DoctrineExtensions') {
            $suffix = '';
            $parts = array_merge(
                array(
                    'Cx',
                    'Lib',
                    'doctrine',
                    'beberlei',
                    strtolower(array_shift($parts)),
                    'src'
                ),
                $parts
            );
        } else if ($parts[0] == 'Doctrine') {
            $suffix = '';
            if ($parts[1] == 'ORM') {
                $parts = array_merge(array('Cx', 'Lib', 'doctrine'), $parts);
            } else {
                $parts = array_merge(array('Cx', 'Lib', 'doctrine', 'vendor', 'doctrine-' . strtolower($parts[1]), 'lib'), $parts);
            }
        } else if ($parts[0] == 'DoctrineExtension') {
            $suffix = '';
            $parts = array_merge(array('Cx', 'Model', 'extensions'), $parts);
        } else if ($parts[0] == 'Symfony') {
            $suffix = '';
            $parts = array_merge(array('Cx', 'Lib', 'doctrine', 'vendor'), $parts);
        }

        // we don't need the Cx part
        unset($parts[0]);
        // core, lib, model, etc. are lowercase by design
        $parts[1] = strtolower($parts[1]);
        // but we need the original class name to find the correct file name
        $className = end($parts);
        unset($parts[count($parts)]);
        reset($parts);
        // find matching path
        $path = '';
        foreach ($parts as $part) {
            $part = '/' . $part;
            if (!is_dir($this->basePath . $path . $part) && (!$this->customizingPath || !is_dir($this->customizingPath . $path . $part))) {
                return false;
            }
            $path .= $part;
        }

        $resolvedPath = $path . '/' . $className . $suffix . '.php';
        if (preg_match('/Exception/', $className) && !$this->loadFile($resolvedPath, $requestedName)) {
            $className = preg_replace('/Exception/', '', $className);
            $resolvedPath = $path . '/' . $className . $suffix . '.php';
        }
        if ($this->loadFile($resolvedPath, $requestedName)) {
            return true;
        } else if ($this->loadFile($path.'/'.$className.'.interface.php', $requestedName)) {
            return true;
        }
        //echo '<span style="color: red;">' . implode('\\', $parts) . '</span>';
        return false;
    }

    /**
     * Try to load the file specified by $path
     *
     * @param   $path   string  Path to the file to be loaded
     * @param   $name   string  Optional filename of the file to be loaded.
     *                          If provided, the path of the file will be
     *                          cached, so that the next time the file will
     *                          be requested by the autoloading functionality
     *                          of PHP, it can be loaded way faster.
     * @return  boolean TRUE, if file was loaded. Otherwise FALSE, if file
     *                  could not be located.
     */
    public function loadFile($path, $name = '') {
        // Try to identify recursive autoloading.
        // If $resolveCount is greater than 1, then the autoloader has been
        // re-called while already performing an other autoload call.
        // Such case indicates that the system got into an infinite recursive
        // autoload loop. To prevent a memory overflow, we must abort the
        // execution in such a case.
        // Note: as the threshold is just 1, one might consider of just using
        // a boolean instead. The latter might just be alright at this point
        // in time. However, as we are not yet sure if there might actually
        // be a legitimate case where a greater count of 1 is valid, we shell
        // keep this variable as an integer as of for now.
        static $resolveCount = 0;
        if ($resolveCount > 1) {
            throw new \Exception('ClassLoader: Recursive loading aborted');
        }

        // before trying to resolve the path, we do increase the resolveCount
        $resolveCount++;

        $path = $this->getFilePath($path);

        // If this point is reached, then the resolving has been completed
        // without calling a recursive call. Therefore, we can reduce the
        // resolveCount by one again.
        $resolveCount--;

        // abort in case the path does not exist
        if (!$path) {
            return false;
        }

        require_once($path);

        // cache updated class map
        if ($name && $this->memcached) {
            if (!isset($this->classMap[$this->classMapKey])) {
                $this->classMap[$this->classMapKey] = array();
            }
            $this->classMap[$this->classMapKey][$name] = $path;
            $this->memcached->set(
                $this->classMapKey,
                $this->classMap[$this->classMapKey]
            );
        }

        return true;
    }

    /**
     * Get the path to a customized version of a file
     *
     * The detection algorithm for a customized version uses the follow
     * precedence:
     *
     * 1. If we are in FRONTEND mode and the file is part of the 'view' layer
     *      (it is located within the 'View' folder of its component), then it
     *      will return the path to the customized version of the file in the
     *      currently active design theme (if it does exist at all).
     *      Note that the folder 'View' is being left out in the design theme as
     *      only files of the 'View' folder can be loaded from the design theme.
     *      I.e.: /themes/default/core_modules/Media/Media/Pdf.png
     *
     * 2. If a customized version exists in the /customizing folder, then the
     *      path to that one will be returned.
     *      I.e.: /customizing/core_modules/Media/View/Media/Pdf.png
     *
     * 3. If a customized version exists in the website data repository, then
     *      that one will be returned.
     *      I.e.: /websites/demo/core_modules/Media/View/Media/Pdf.png
     *
     * 4. Ensure that the original version of the file exists at least (within
     *      the code base repository) and return that one.
     *      I.e.: /core_modules/Media/View/Media/Pdf.png
     *
     * @param   string  $file           The file to return the path from.
     * @param   boolean $isCustomized   If $isCustomized is provided, then it is
     *                                  set to TRUE if a customized version of
     *                                  the file does exist. Otherwise it is set
     *                                  to FALSE.
     * @param   boolean $isWebsite      If $isWebsite is provided, then it is
     *                                  set to TRUE if the file can be located
     *                                  in the website data repository.
     *                                  Otherwise it is set to FALSE.
     * @param   boolean $webPath        Whether or not to return the absolute
     *                                  file system path of the customized file.
     *                                  IMPORTANT: This will cause the algorithm
     *                                  to cut of any URL arguments from the
     *                                  file path like '?foo=bar' or '#foo', to
     *                                  be able to successfully locate dynamic
     *                                  media ressources.
     *                                  Therefore, if the filename or path of
     *                                  $file contains the character '?' or '#',
     *                                  then the result of this method is
     *                                  unknown.
     * @param \Cx\Core\View\Model\Entity\Theme $theme (optional) Theme to get
     *                                  file from. Defaults to current theme (if set)
     * @return  mixed                   Returns the path (either absolute file
     *                                  system path or relativ web path, based
     *                                  on $webPath) to a customized version of
     *                                  the file identified by $file. If no
     *                                  customized version of the file does
     *                                  exist, then FALSE is being returned.
     */
    public function getFilePath($file, &$isCustomized = false, &$isWebsite = false, $webPath = false, $theme = null) {
        // make lookup algorithm work on Windows by replacing backslashes by forward slashes
        $file = preg_replace('#\\\\#', '/', $file);

        // remove any URL arguments from the file path like '?foo=bar' or '#foo'
        if ($webPath) {
            $file = preg_replace('/(\?[^\?]*|#[^#]*)$/', '', $file);
        }

        // using $this->cx->getCodeBaseDocumentRootPath() here instead of $this->basePath
        // makes sure that no matter where the ClassLoader gets initialized,
        // all customized files are always located in the folder /customizing.
        // This means that also the customized files of the installer will be
        // located in the folder /customizing.
        if(strpos($file, $this->cx->getWebsiteDocumentRootPath()) === 0) {
            $file = preg_replace('#^'.preg_quote($this->cx->getWebsiteDocumentRootPath(), '#').'#', '', $file);
        } else {
            $file = preg_replace('#^'.preg_quote($this->cx->getCodeBaseDocumentRootPath(), '#').'#', '', $file);
        }

        // reset variables in case they have been wrongly set already
        $isCustomized = false;
        $isWebsite = false;

        // 1. check if a customized version exists in the currently loaded theme
        $path = $this->getFileFromTheme($file, $webPath, $theme);
        if ($path) return $path;

        // 2. check if a customized version exists in the /customizing folder
        $path = $this->getFileFromCustomizing($file, $webPath, $isCustomized);
        if ($path) return $path;

        // 3. check if a customized version exists in the website's data repository
        $path = $this->getFileFromWebsiteRepository($file, $webPath, $isWebsite);
        if ($path) return $path;

        // 4. check if file exists in a MediaSource (shared repository)
        $path = $this->getFileFromMediaSource($file, $webPath);
        if ($path) return $path;

        // 5. check if original file exists in code base
        if (file_exists($this->basePath.$file)) {
            return ($webPath ? $this->cx->getCodeBaseOffsetPath() : $this->basePath) . $file;
        }

        // lookup of file failed -> file does not exist
        return false;
    }

    /**
     * Get file path with filename from registered MediaSource filesystems.
     *
     * @param   string  $file       Path of file, it should be located
     *                              in /images, /media or /themes.
     * @param   boolean $webPath    Whether or not to return the absolute file
     *                              path or MediaSource file system path.
     * @return  mixed               Returns absolute file path or MediaSource
     *                              file path or FALSE if none exists.
     */
    public function getFileFromMediaSource($file, $webPath = false) {
        // media source files may only be located in /images, /media or /themes
        $cxClassName = get_class($this->cx);
        if (!preg_match('#^(?:' . preg_quote($cxClassName::FOLDER_NAME_IMAGES, '#') . '|' . preg_quote($cxClassName::FOLDER_NAME_MEDIA, '#') . '|' . preg_quote($cxClassName::FOLDER_NAME_THEMES, '#') . ')/#', $file)) {
            return false;
        }

        // check if Env has been initialized yet
        if (!class_exists('Env', false)) {
            return false;
        }

        // check if InitCMS has been initialized yet 
        $objInit = \Env::get('init');
        if (!$objInit) {
            return false;
        }
        $mediaSourceManager = $this->cx->getMediaSourceManager();
        if (!$mediaSourceManager) {
            return false;
        }

        // check if file exists in any of the registered MediaSource filesystems
        $mediaSourceFile = $mediaSourceManager->getMediaSourceFileFromPath($file);
        if (!$mediaSourceFile) {
            return false;
        }

        if ($webPath) {
            return $file;
        }
        return $mediaSourceFile->getFileSystem()->getFullPath($mediaSourceFile) . $mediaSourceFile->getFullName();
    }

    /**
     * Checks if a customized version of a file exists is the currently loaded
     * design theme and returns its path if it exists.
     *
     * @param   string  $file       Path of file to look for a customized
     *                              version for.
     * @param   boolean $webPath    Whether or not to return the relative web
     *                              path instead of the absolute file system
     *                              path (default).
     * @param \Cx\Core\View\Model\Entity\Theme $theme (optional) Theme to get
     *                              file from. Defaults to current theme (if set)
     * @return  mixed               Path (as string) to customized version of
     *                              file or FALSE if none exists.
     */
    public function getFileFromTheme($file, $webPath = false, $theme = null) {
        // custom themed files are only available in frontend
        if (
            $this->cx->getMode() != \Cx\Core\Core\Controller\Cx::MODE_FRONTEND &&
            !$theme &&
            (
                !$this->cx->getResponse() ||
                !$this->cx->getResponse()->getTheme()
            )
        ) {
            return false;
        }

        // check if file is from view layer (as in MVC)
        if (!preg_match('#^(?:.*/)?(?:core|core_modules|modules)/[^/]+/View/#', $file)) {
            return false;
        }

        // check if Env has been initialized yet
        if (!class_exists('Env', false)) {
            return false;
        }

        // check if frontend theme has been loaded yet
        if (!$theme) {
            $theme = $this->cx->getResponse()->getTheme();
        }
        if (!$theme) {
            return false;
        }
        $currentThemesPath = $theme->getFoldername();
        if (!$currentThemesPath) {
            return false;
        }

        // set up path of custom themed file
        $customThemeFile = '/'. $currentThemesPath . preg_replace('#^(.*/)?(core|core_modules|modules)(/[^/]+/)View/#', '\1\2\3', $file);

        // set up absolute path of custom themed file
        $absoluteCustomThemeFile = $this->cx->getWebsiteThemesPath() . $customThemeFile;

        // check if a custom themed version of the file does exist
        if (!file_exists($absoluteCustomThemeFile)) {
            return false;
        }

        // When the LegacyClassLoader is not initialized you cant load the FWValidator class
        // which is needed for the security check following next
        if (!$this->legacyClassLoader) {
            return false;
        }

        // Checks if the file is a harmless one, because you can upload anything
        // over the ftp which probably not should be executed
        if (!\FWValidator::is_file_ending_harmless($file)) {
            return false;
        }

        // finally, return the path to the custom themed version of the file
        return ($webPath ? $this->cx->getWebsiteThemesWebPath() : $this->cx->getWebsiteThemesPath()) . $customThemeFile;
    }

    /**
     * Checks if a customized version of a file exists in the customizing
     * folder and returns its path if it exists.
     *
     * @param   string  $file           Path of file to look for a customized
     *                                  version for.
     * @param   boolean $webPath        Whether or not to return the relative
     *                                  web path instead of the absolute file
     *                                  system path (default).
     * @param   boolean $isCustomized   If $isCustomized is provided, then it is
     *                                  set to TRUE if a customized version of
     *                                  the file does exist. Otherwise it is set
     *                                  to FALSE.
     * @return  mixed                   Path (as string) to customized version
     *                                  of file or FALSE if none exists.
     */
    public function getFileFromCustomizing($file, $webPath = false, &$isCustomized = false) {
        // check if customizing functionality is active
        if (!$this->customizingPath) {
            return false;
        }

        // check if customized version of file exists
        if (!file_exists($this->customizingPath.$file)) {
            return false;
        }

        // customized version of file found in customizing-folder
        $isCustomized = true;
        return ($webPath ? $this->cx->getWebsiteOffsetPath() . substr($this->customizingPath, strlen($this->cx->getWebsiteDocumentRootPath())) : $this->customizingPath) . $file;
    }

    /**
     * Checks if a customized version of a file exists in the website data
     * repository and returns its path if it exists.
     *
     * @param   string  $file       Path of file to look for a customized
     *                              version for.
     * @param   boolean $webPath    Whether or not to return the relative web
     *                              path instead of the absolute file system
     *                              path (default).
     * @param   boolean $isWebsite  If $isWebsite is provided, then it is set
     *                              to TRUE if the file can be located in the
     *                              website data repository. Otherwise it is
     *                              set to FALSE.
     * @return  mixed               Path (as string) to customized version of
     *                              file or FALSE if none exists.
     */
    public function getFileFromWebsiteRepository($file, $webPath = false, &$isWebsite = false) {
        // When the LegacyClassLoader is not initialized you cant load the FWValidator class
        // which is needed for the security check following next
        if (!$this->legacyClassLoader) {
            return false;
        }

        // Checks if the file is a harmless one, because you can upload anything
        // over the ftp which probably not should be executed
        if (!\FWValidator::is_file_ending_harmless($file)) {
            return false;
        }

        // check if customized version of file exists
        if (!file_exists($this->cx->getWebsiteDocumentRootPath().$file)) {
            return false;
        }

        // customized version of file found in website's data repository
        $isWebsite = true;
        return ($webPath ? $this->cx->getWebsiteOffsetPath() : $this->cx->getWebsiteDocumentRootPath()) . $file;
    }

    /**
     * Shortcut for {@see getFilePath()} with argument $webPath set to TRUE
     */
    public function getWebFilePath($file, &$isCustomized = false, &$isWebsite = false) {
        return $this->getFilePath($file, $isCustomized, $isWebsite, true);
    }

    private function loadLegacy($name) {
        if ($this->legacyClassLoader) {
            $this->legacyClassLoader->autoload($name);
        }
    }

    /**
     * Tests if a class is available. You may specify if legacy and customizing
     * can be used to load it if necessary.
     * @todo $useCustomizing does not work correctly if legacy is enabled
     * @param string $class Class name to look for
     * @param boolean $useLegacy (optional) Wheter to allow usage of legacy class loader or not (default false)
     * @param boolean $useCustomizing (optional) Wheter to allow usage of customizings or not (default true)
     * @return boolean True if class could be found using the allowed methods, false otherwise
     */
    public function classExists($class, $useLegacy = false, $useCustomizing = true) {
        if ($useLegacy) {
            return class_exists($class) || interface_exists($class, false);
        }
        $legacy = $this->legacyClassLoader;
        $this->legacyClassLoader = null;
        $customizing = $this->customizingPath;
        if (!$useCustomizing) {
            $customizing = null;
        }
        $ret = class_exists($class) || interface_exists($class, false);
        $this->legacyClassLoader = $legacy;
        $this->customizingPath = $customizing;
        return $ret;
    }
}
