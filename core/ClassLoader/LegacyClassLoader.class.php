<?php

/**
 * LegacyClassLoader
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      COMVATION Development Team <info@comvation.com>
 * @package     contrexx
 * @subpackage  core_classloader
 */

namespace Cx\Core\ClassLoader;

/**
 * LegacyClassLoader
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      COMVATION Development Team <info@comvation.com>
 * @package     contrexx
 * @subpackage  core_classloader
 */
class LegacyClassLoader {
    private static $instance = null;
    private $tab = 0;
    private $timeUsed = 0;
    private $bytes = 0;
    private $subBytes = 0;
    private $mapTable = array();
    private $cx = null;
    private $extraClassRepositoryFile;
    private $userClassCacheFile;

    public function __construct($classLoader, $cx) {
        self::$instance = $this;
        $this->cx = $cx;
        $this->extraClassRepositoryFile = $this->cx->getCodeBaseCorePath(). '/ClassLoader/Data/LegacyClassCache.dat';
        $this->userClassCacheFile  = $this->cx->getWebsiteTempPath().'/LegacyClassCache.dat';
        if (file_exists($classLoader->getFilePath($this->extraClassRepositoryFile))) {
            $extraClassArr = unserialize(file_get_contents($classLoader->getFilePath($this->extraClassRepositoryFile)));
        }
        if (file_exists($classLoader->getFilePath($this->userClassCacheFile))) {
            $userClassArr = unserialize(file_get_contents($classLoader->getFilePath($this->userClassCacheFile)));
        }
        $this->mapTable = !empty($userClassArr) ? array_merge($extraClassArr, $userClassArr) : $extraClassArr;
    }

    public function autoload($name) {
        $parts = explode('\\', $name);
        // Let doctrine handle it's includes itself
        if (in_array($parts[0], array('Symfony', 'doctrine', 'Doctrine', 'Gedmo', 'DoctrineExtension'))) {
            return;
        // They come from doctrine, there's no need to load these, doctrine does it
        } else if (in_array($name, array('var', 'Column', 'MappedSuperclass', 'Table', 'index', 'Entity', 'Id', 'GeneratedValue'))) {
            return;
        }
        if (substr($name, 0, 8) == 'PHPUnit_') {
            return false;
        }
        /*if ($parts[0] == 'Cx') {
            echo '<b>LegacyClassLoader handling class ' . $name . '</b><br />';
        } else {
            //echo 'LegacyClassLoader handling class ' . $name . '<br />';
        }//*/
        $startTime = microtime(true);
        if (!$this->loadFromCache($name)) {
            // class not in match table, guess path
            // we do not need the namespace, it's probably wrong anyway
            $origName = $name;
            $name = end($parts);
            // start try and error...
            // files in /core
            if ($this->testLoad($this->cx->getCodeBaseCorePath() . '/'.$name.'.class.php', $origName)) { return; }
            // files in /lib
            if ($this->testLoad($this->cx->getCodeBaseLibraryPath() . '/'.$name.'.php', $origName)) { return; }
            // files in /lib/FRAMEWORK/User
            if ($this->testLoad($this->cx->getCodeBaseFrameworkPath() . '/User/'.$name.'.class.php', $origName)) { return; }
            // files in /lib/FRAMEWORK
            if ($this->testLoad($this->cx->getCodeBaseFrameworkPath() . '/'.preg_replace('/FW/', '', $name).'.class.php', $origName)) { return; }
            // files in /lib/PEAR
            $end = preg_split('/_/', $name);
            if ($this->testLoad(
                    $this->cx->getCodeBaseLibraryPath() . '/PEAR/'.
                    preg_replace(
                        '/_/', '/', preg_replace('/PEAR\//', '', $name . '/')).
                    end($end).'.php', $origName)) {
                return;
            }
            // files in /model/entities/Cx/Model/Base
            if ($this->testLoad($this->cx->getCodeBaseModelPath() . '/entities/Cx/Model/Base/' . $name . '.php', $origName)) { return; }

            // core module and module libraries /[core_modules|modules]/{modulename}/lib/{modulename}Lib.class.php
            $moduleName = strtolower(preg_replace('/Library/', '', $name));
            // exception for mediadir
            $moduleName = preg_replace('/mediadirectory/', 'mediadir', $moduleName);

            // core module and module indexes /[core_modules|modules]/{modulename}/[index.class.php|admin.class.php]
            $lowerModuleName = strtolower($name);
            if (\Env::get('init')) {
                if (\Env::get('init')->mode != 'backend') {
                    if ($this->testLoad($this->cx->getCodeBaseCoreModulePath() . '/' . $lowerModuleName . '/index.class.php', $origName)) { return; }
                    if ($this->testLoad($this->cx->getCodeBaseModulePath() . '/' . $lowerModuleName . '/index.class.php', $origName)) { return; }
                } else {
                    if ($this->testLoad($this->cx->getCodeBaseCoreModulePath() . '/' . $lowerModuleName . '/admin.class.php', $origName)) { return; }
                    if ($this->testLoad($this->cx->getCodeBaseModulePath() . '/' . $lowerModuleName . '/admin.class.php', $origName)) { return; }
                }
            }

            if ($this->testLoad($this->cx->getCodeBaseCoreModulePath() . '/' . $moduleName . '/lib/' . $moduleName . 'Lib.class.php', $origName)) { return; }
            if ($this->testLoad($this->cx->getCodeBaseCoreModulePath() . '/' . $moduleName . '/lib/Lib.class.php', $origName)) { return; }
            if ($this->testLoad($this->cx->getCodeBaseCoreModulePath() . '/' . $moduleName . '/lib/lib.class.php', $origName)) { return; }
            if ($this->testLoad($this->cx->getCodeBaseCoreModulePath() . '/' . $moduleName . '/Lib.class.php', $origName)) { return; }
            if ($this->testLoad($this->cx->getCodeBaseModulePath() . '/' . $moduleName . '/lib/' . $moduleName . 'Lib.class.php', $origName)) { return; }
            if ($this->testLoad($this->cx->getCodeBaseModulePath() . '/' . $moduleName . '/lib/Lib.class.php', $origName)) { return; }
            if ($this->testLoad($this->cx->getCodeBaseModulePath() . '/' . $moduleName . '/lib/lib.class.php', $origName)) { return; }
            if ($this->testLoad($this->cx->getCodeBaseModulePath() . '/' . $moduleName . '/Lib.class.php', $origName)) { return; }

            // core module and module model /[core_modules|modules]/{modulename}/lib/
            $moduleName = current(preg_split('/[A-Z]/', lcfirst($name)));
            $nameWithoutModule = substr($name, strlen($moduleName));
            $nameWithoutModuleLowercase = strtolower($nameWithoutModule);
            if ($this->testLoad($this->cx->getCodeBaseCoreModulePath() . '/' . $moduleName . '/lib/' . $name . '.class.php', $origName)) { return; }
            if ($this->testLoad($this->cx->getCodeBaseCoreModulePath() . '/' . $moduleName . '/lib/' . $nameWithoutModule . '.class.php', $origName)) { return; }
            if ($this->testLoad($this->cx->getCodeBaseCoreModulePath() . '/' . $moduleName . '/lib/' . $nameWithoutModuleLowercase . '.class.php', $origName)) { return; }
            if ($this->testLoad($this->cx->getCodeBaseModulePath() . '/' . $moduleName . '/lib/' . $name . '.class.php', $origName)) { return; }
            if ($this->testLoad($this->cx->getCodeBaseModulePath() . '/' . $moduleName . '/lib/' . $nameWithoutModule . '.class.php', $origName)) { return; }
            if ($this->testLoad($this->cx->getCodeBaseModulePath() . '/' . $moduleName . '/lib/' . $nameWithoutModuleLowercase . '.class.php', $origName)) { return; }
            // exception for data module
            if ($this->testLoad($this->cx->getCodeBaseModulePath() . '/' . $moduleName . '/' . $name . '.class.php', $origName)) { return; }

            // core module and module model classes not containing module name
            if ($this->testLoad($this->cx->getCodeBaseModulePath() . '/shop/lib/' . $name . '.class.php', $origName)) { return; }

            // Temporary exceptions
            // exception for filesystem. TEMPORARY!
            if ($this->testLoad($this->cx->getCodeBaseFrameworkPath() . '/File/'.$name.'.class.php', $origName)) {return; }
            // exception for CxJs. TEMPORARAY!
            if ($this->testLoad($this->cx->getCodeBaseFrameworkPath() . '/cxjs/'.$name.'.class.php', $origName)) {return; }
            if ($this->testLoad($this->cx->getCodeBaseFrameworkPath() . '/cxjs/'.$name.'.interface.php', $origName)) {return; }
            if ($this->testLoad($this->cx->getCodeBaseFrameworkPath() . '/cxjs/i18n/'.preg_replace('/JQueryUiI18nProvider/', 'jQueryUi', $name).'.class.php', $origName)) {return; }

            // This is sort of like giving in...
            $this->fallbackLoad($origName, $name);
        }
        $endTime = microtime(true);
        $this->timeUsed += ($endTime - $startTime);
    }

    private function loadFromCache($name) {
        global $objInit;
        if (isset($this->mapTable[$name])) {
            $file = $this->mapTable[$name];
            $ending = explode('/', $file);
            $ending = end($ending);
            if ($objInit && $objInit->mode == 'backend' && $ending == 'index.class.php') {
                return false;
            } else if ((!$objInit || $objInit->mode != 'backend') && $ending == 'admin.class.php') {
                return false;
            }
            $this->loadClass('.'.$file, $name);
            return true;
        }
        return false;
    }

    private function testLoad($path, $name) {
        if (file_exists($path)) {
            $path = substr($path, strlen($this->cx->getCodeBaseDocumentRootPath()));
            if ( ! $this->loadClass($path, $name)) {
                return false;
            }
            try {
                $objFile = new \Cx\Lib\FileSystem\File($this->userClassCacheFile);
                if (!file_exists($this->userClassCacheFile)) $objFile->touch();
                $cacheArr = unserialize(file_get_contents(\Env::get('ClassLoader')->getFilePath($this->userClassCacheFile)));
                $cacheArr[$name] = $path;
                $objFile->write(serialize($cacheArr));
            } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                \DBG::msg($e->getMessage());
            }
            return true;
        }
        return false;
    }

    /**
     * This method won't work for all files at once because max exec time is too short
     * @param type $name
     * @param type $className
     */
    private function fallbackLoad($name, $className) {
        global $_CONFIG;

        //echo $name . '<br />';
        $namespace = substr($name, 0, strlen($name) - strlen($className) - 1);
        $globDirs = array(
            $this->cx->getCodeBaseCoreModulePath(),
            $this->cx->getCodeBaseCorePath(),
            $this->cx->getCodeBaseLibraryPath(),
            $this->cx->getCodeBaseModulePath(),
        );
        $customizingGlobDirs = array(
            $this->cx->getWebsiteCustomizingPath() . $this->cx->getCoreModuleFolderName(),
            $this->cx->getWebsiteCustomizingPath() . $this->cx->getCoreFolderName(),
            $this->cx->getWebsiteCustomizingPath() . $this->cx->getLibraryFolderName(),
            $this->cx->getWebsiteCustomizingPath() . $this->cx->getModelFolderName(),
            $this->cx->getWebsiteCustomizingPath() . $this->cx->getModuleFolderName(),
        );
        if ($_CONFIG['useCustomizings'] == 'on' && file_exists($this->cx->getWebsiteCustomizingPath())) {
            // search in customizing folders first, because we expect the most changes there
            $globDirs = array_merge($customizingGlobDirs, $globDirs);
        }
        $path = false;
        foreach ($globDirs as $dir) {
            $path = $this->searchClass($className, $namespace, $dir);
            if ($path !== false) {
                break;
            }
        }
        if ($path === false) {
            // this class does not exist!
            return;
        }
        $this->testLoad($path, $name);
    }

    private function searchClass($name, $namespace, $path = '') {
        if (empty ($path)) {
            $path = $this->cx->getCodeBaseDocumentRootPath();
        }
        $files = glob($path . '/*.php');
        foreach ($files as $file) {
            $fileParts = explode('/', $file);
            if (substr(end($fileParts), 0, 1) == '!') {
                continue;
            }
            $adminClass = 'admin.class.php';
            $indexClass = 'index.class.php';
            if (!defined('BACKEND_LANG_ID') && substr($file, strlen($file) - strlen($adminClass)) == $adminClass) {
                continue;
            }
            if (defined('BACKEND_LANG_ID') && substr($file, strlen($file) - strlen($indexClass)) == $indexClass) {
                continue;
            }
            $fcontent = file_get_contents($file);
            // match namespace too
            $matches = array();

            
            //if (preg_match('/(?:namespace\s+([\\\\\w]+);[.\n\r]*?)?(?:class|interface)\s+' . $name . '\s+(?:extends|implements)?[\\\\\s\w,\n\t\r]*?\{/', $fcontent, $matches)) {
            if (preg_match('/(?:namespace ([\\\\a-zA-Z0-9_]*);[\w\W]*)?(?:class|interface) ' . $name . '(?:[ \n\r\t])?(?:[a-zA-Z0-9\\\\_ \n\r\t])*\{/', $fcontent, $matches)) {
                if (isset($matches[0]) && (!isset($matches[1]) || $matches[1] == $namespace)) {
                    return $file;
                }
            }
            
        }
        foreach (glob($path.'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
            $dirParts = explode('/', $dir);
            if (substr(end($dirParts), 0, 1) == '!') {
                continue;
            }
            $result = $this->searchClass($name, $namespace, $dir);
            if ($result !== false) {
                return $result;
            }
        }
        return false;
    }

    private function loadClass($path, $name) {
        global $_CONFIG;

        $this->tab++;
        $bytes = memory_get_peak_usage();
        if ($_CONFIG['useCustomizings'] == 'on' && file_exists($this->cx->getWebsiteCustomizingPath() . '/' . $path)) {
            require_once $this->cx->getWebsiteCustomizingPath() . '/' . $path;
        } else {
            require_once $this->cx->getCodeBaseDocumentRootPath() . '/' . $path;
        }
        if ( ! class_exists($name, false) ) {
            return false;
        }
        $bytes = memory_get_peak_usage()-$bytes;
        $this->tab--;
        $ownBytes = '';
        if ($this->tab == 0) {
            //$ownBytes = ' (' . formatBytes($bytes - $this->subBytes) . ')';
            $this->subBytes = 0;
        } else {
            $this->subBytes += $bytes;
        }
        $this->bytes += $bytes;
        //echo '<span style="color:red; margin-left: ' . (20 * $this->tab) . 'px">' . $name . ' from ' . $path . '</span><br />';
        //echo '<span style="color:red; margin-left: ' . (20 * $this->tab) . 'px">' . formatBytes($bytes) . $ownBytes . ' from ' . $name . '</span><br />';
        /*if ($this->tab == 0) {
            echo '<br />';
        }*/
    }

    public static function getInstance() {
        return self::$instance;
    }

    public function getTimeUsed() {
        return $this->timeUsed;
    }

    public function getRamUsed() {
        return $this->bytes;
    }
}
