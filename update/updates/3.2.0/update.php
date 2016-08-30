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


require_once UPDATE_PATH . '/lib/FRAMEWORK/FileSystem/FileInterface.interface.php';
require_once UPDATE_PATH . '/lib/FRAMEWORK/FileSystem/File.class.php';
require_once UPDATE_PATH . '/lib/FRAMEWORK/FileSystem/FileSystem.class.php';
require_once UPDATE_PATH . '/lib/FRAMEWORK/FileSystem/FileSystemFile.class.php';
require_once UPDATE_PATH . '/lib/FRAMEWORK/FileSystem/FTPFile.class.php';

function executeContrexxUpdate() {
    global $_CORELANG, $_CONFIG, $objDatabase, $objUpdate, $_DBCONFIG;

    /**
     * These are the modules which MUST have new template in order for Cloudrexx
     * to work correctly. CSS definitions for these modules will get updated too.
     */
    $viewUpdateTable = array(
        // E-Mail Marketing
        'newsletter'    => array (
            'version'       => '3.1.0.0',
            'dependencies'    => array (
                'forms',
            ),
        ),

        // Veranstaltungskalender
        'calendar'    => array (
            'version'       => '3.1.0.0',
            'dependencies'  => array (),
        ),

        // Online Shop
        'shop'          => array (
            'version'       => '3.0.0.0',
            'dependencies'  => array (
                'forms',
            ),
        ),

        // Umfragen
        'voting'        => array (
            'version'       => '2.1.0.0',
            'dependencies'  => array (),
        ),

        // Benutzerverwaltung
        'access'        => array (
            'version'       => '2.0.0.0',
            'dependencies'  => array (
                'forms',
                'captcha',
                'uploader',
            ),
        ),

        // Podcast
        'podcast'       => array (
            'version'       => '2.0.0.0',
            'dependencies'  => array (),
        ),

        // Login
        'login'         => array (
            'version'       => '3.0.2.0',
            'dependencies'  => array (
                'forms',
                'captcha',
            ),
        ),

        // Media archives
        'media1'        => array(
            'version'       => '3.0.0.0',
            'dependencies'  => array (),
        ),
        'media2'        => array(
            'version'       => '3.0.0.0',
            'dependencies'  => array (),
        ),
        'media3'        => array(
            'version'       => '3.0.0.0',
            'dependencies'  => array (),
        ),
        'media4'        => array(
            'version'       => '3.0.0.0',
            'dependencies'  => array (),
        ),
    );

    $_SESSION['contrexx_update']['copyFilesFinished'] = !empty($_SESSION['contrexx_update']['copyFilesFinished']) ? $_SESSION['contrexx_update']['copyFilesFinished'] : false;

    // Copy cx files to the root directory
    if (!$_SESSION['contrexx_update']['copyFilesFinished']) {
        if (!loadMd5SumOfOriginalCxFiles()) {
            return false;
        }

        $copyFilesStatus = copyCxFilesToRoot(dirname(__FILE__) . '/cx_files', ASCMS_PATH . ASCMS_PATH_OFFSET);
        if ($copyFilesStatus !== true) {
            if ($copyFilesStatus === 'timeout') {
                setUpdateMsg(1, 'timeout');
            }
            return false;
        }
        if (extension_loaded('apc') && ini_get('apc.enabled')) {
            apc_clear_cache();
        }
        $_SESSION['contrexx_update']['copyFilesFinished'] = true;

        // log modified files
        DBG::msg('MODIFIED FILES:');
        if (isset($_SESSION['contrexx_update']['modified_files'])) {
            DBG::dump($_SESSION['contrexx_update']['modified_files']);
        }

        // we need to stop the script here to force a reinitialization of the update system
        // this is required so that the new constants from config/set_constants.php are loaded
        //setUpdateMsg($_CORELANG['TXT_UPDATE_PROCESS_HALTED'], 'title');
        //setUpdateMsg($_CORELANG['TXT_UPDATE_PROCESS_HALTED_TIME_MSG'].'<br />', 'msg');
        //setUpdateMsg('Installation der neuen Dateien abgeschlossen.<br /><br />', 'msg');
        //setUpdateMsg('<input type="submit" value="'.$_CORELANG['TXT_CONTINUE_UPDATE'].'" name="updateNext" /><input type="hidden" name="processUpdate" id="processUpdate" />', 'button');
        setUpdateMsg(1, 'timeout');
        return false;
    }
    unset($_SESSION['contrexx_update']['copiedCxFilesIndex']);

    /**
     * This needs to be initialized before loading config/doctrine.php
     * Because we overwrite the Gedmo model (so we need to load our model
     * before doctrine loads the Gedmo one)
     */
    require_once(ASCMS_CORE_PATH . '/ClassLoader/ClassLoader.class.php');
    $cl = new \Cx\Core\ClassLoader\ClassLoader(ASCMS_DOCUMENT_ROOT, true);
    Env::set('ClassLoader', $cl);

    FWLanguage::init();

    if (!isset($_SESSION['contrexx_update']['update'])) {
        $_SESSION['contrexx_update']['update'] = array();
    }
    if (!isset($_SESSION['contrexx_update']['update']['done'])) {
        $_SESSION['contrexx_update']['update']['done'] = array();
    }


    /////////////////////
    // UTF-8 MIGRATION //
    /////////////////////
    if (!include_once(dirname(__FILE__) . '/components/core/core.php')) {
        setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_UNABLE_LOAD_UPDATE_COMPONENT'], dirname(__FILE__) . '/components/core/core.php'));
        return false;
    }
    if (!include_once(dirname(__FILE__) . '/components/core/utf8.php')) {
        setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_UNABLE_LOAD_UPDATE_COMPONENT'], dirname(__FILE__) . '/components/core/utf8.php'));
        return false;
    }
    if (!in_array('utf8', ContrexxUpdate::_getSessionArray($_SESSION['contrexx_update']['update']['done']))) {
        $result = _utf8Update();
        if ($result === 'timeout') {
            setUpdateMsg(1, 'timeout');
            return false;
        } elseif (!$result) {
            if (empty($objUpdate->arrStatusMsg['title'])) {
                setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_COMPONENT_BUG'], $_CORELANG['TXT_UTF_CONVERSION']), 'title');
            }
            return false;
        }
        if ($result === 'charset_changed') {
            // write new charset/collation definition to config file
            if (!_writeNewConfigurationFile()) {
                return false;
            }
        }

        $_SESSION['contrexx_update']['update']['done'][] = 'utf8';

        // _utf8Update() might have changed the charset/collation and migrated some tables,
        // therefore, we will force a reinitialization of the update system
        // to ensure that all db-connections are using the proper charset/collation
        \DBG::msg('Changed collation to: '.$_DBCONFIG['collation']);
        \DBG::msg('Force reinitialization of update...');
        setUpdateMsg(1, 'timeout');
        return false;
    }
    /////////////////////


    /////////////////////////////
    // Session Table MIGRATION //
    /////////////////////////////
    $isSessionVariableTableExists = \Cx\Lib\UpdateUtil::table_exist(DBPREFIX.'session_variable');
    if ($isSessionVariableTableExists) {
        createOrAlterSessionVariableTable();
    }
    if (!$isSessionVariableTableExists && $objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.2.0')) {
        if (!migrateSessionTable()) {
            setUpdateMsg('Error in updating session table', 'error');
            return false;
        }
        setUpdateMsg(1, 'timeout');
        return false;
    }

    // Load Doctrine (this must be done after the UTF-8 Migration, because we'll need $_DBCONFIG['charset'] to be set)
    $incDoctrineStatus = require_once(UPDATE_PATH . '/config/doctrine.php');
    Env::set('incDoctrineStatus', $incDoctrineStatus);

    $userData = array(
        'id'   => $_SESSION['contrexx_update']['user_id'],
        'name' => $_SESSION['contrexx_update']['username'],
    );
    $loggableListener = \Env::get('loggableListener');
    $loggableListener->setUsername(json_encode($userData));
    /////////////////////


    if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.0.0')) {
        //////////////////////////////
        // BEGIN: CONTENT MIGRATION //
        //////////////////////////////
        DBG::msg('Installed version: '.$_CONFIG['coreCmsVersion']);
        Env::get('ClassLoader')->loadFile(dirname(__FILE__) . '/ContentMigration.class.php');
        $contentMigration = new \Cx\Update\Cx_3_0_4\ContentMigration();

        // Migrate statistics - this must be done before migrating to the new content architecture
        if (empty($_SESSION['contrexx_update']['content_stats'])) {
            DBG::msg('Migrate stats');
            if ($contentMigration->migrateStatistics()) {
                $_SESSION['contrexx_update']['content_stats'] = true;
                if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                    return false;
                }
            } else {
                return false;
            }
        }

        // Check if there are content of inactive languages.
        // If true then ask if the update system can remove them.
        if (empty($_SESSION['contrexx_update']['inactive_content_languages_checked'])) {
            DBG::msg('Check inactive content languages');
            $arrMigrateLangIds = $contentMigration->getActiveContentLanguageIds();

            if (!isset($_POST['skipMigrateLangIds'])) {
                $result = $contentMigration->getInactiveContentLanguageCheckboxes();

                if (!empty($result)) {
                    setUpdateMsg('Inhaltsseiten von inaktiven Sprache(n) gefunden', 'title');
                    setUpdateMsg('
                        Folgende Sprache(n) sind inaktiv, aber enthalten Inhaltsseiten:<br />
                        ' . $result . '<br />
                        Wählen Sie die inaktiven Sprachen, dessen Inhaltseiten Sie migrieren möchten.<br />
                        Klicken Sie anschliessend auf <b>Update fortsetzen...</b>.<br /><br />
                        <div class="message-alert">
                        <b>Achtung:</b><br />
                        Die Inhaltsseiten der inaktive Sprache(n), welche Sie nicht ausgewählt haben, werden gelöscht.
                        </div>
                    ', 'msg');
                    setUpdateMsg('<input type="submit" value="'.$_CORELANG['TXT_CONTINUE_UPDATE'].'" name="updateNext" /><input type="hidden" name="processUpdate" id="processUpdate" /><input type="hidden" name="skipMigrateLangIds" id="skipMigrateLangIds" />', 'button');
                    return false;
                }
            } else {
                if (!empty($_POST['migrateLangIds'])) {
                    if (is_array($_POST['migrateLangIds'])) {
                        $_POST['migrateLangIds'] = array_filter($_POST['migrateLangIds'], 'intval');
                        if (!empty($_POST['migrateLangIds'])) {
                            $arrMigrateLangIds = array_merge($arrMigrateLangIds, $_POST['migrateLangIds']);
                        }
                    } else {
                        if (intval($_POST['migrateLangIds'])) {
                            $arrMigrateLangIds[] = intval($_POST['migrateLangIds']);
                        }
                    }
                }
            }

            $_SESSION['contrexx_update']['migrate_lang_ids'] = $arrMigrateLangIds;
            $_SESSION['contrexx_update']['inactive_content_languages_checked'] = true;
        }

        if (empty($_SESSION['contrexx_update']['migrate_lang_ids'])) {
            $_SESSION['contrexx_update']['migrate_lang_ids'] = $contentMigration->getActiveContentLanguageIds();
        }
        $contentMigration->arrMigrateLangIds = ContrexxUpdate::_getSessionArray($_SESSION['contrexx_update']['migrate_lang_ids']);
        $contentMigration->migrateLangIds    = implode(',', ContrexxUpdate::_getSessionArray($_SESSION['contrexx_update']['migrate_lang_ids']));

        // Migrate content
        if (empty($_SESSION['contrexx_update']['content_migrated'])) {
            DBG::msg('Migrate content');
            $status = $contentMigration->migrate();

            if ($status === true) {
                $_SESSION['contrexx_update']['content_migrated'] = true;

                // log migrated nodes
                DBG::msg('NODES: catId -> nodeId');
                DBG::dump(ContrexxUpdate::_getSessionArray($_SESSION['contrexx_update']['nodes']));
                unset($_SESSION['contrexx_update']['nodes']);

                // log migrated pages
                DBG::msg('PAGES: catId -> pageId');
                DBG::dump(ContrexxUpdate::_getSessionArray($_SESSION['contrexx_update']['pages']));
                unset($_SESSION['contrexx_update']['pages']);

                if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                    return false;
                }
            } else if ($status === 'timeout') {
                setUpdateMsg(1, 'timeout');
                return false;
            } else {
                return false;
            }
        }

        // Page grouping
        if (empty($_SESSION['contrexx_update']['pages_grouped'])) {
            DBG::msg('Group pages');
            $pageGrouping = $contentMigration->pageGrouping();

            if ($pageGrouping === true) {
                $_SESSION['contrexx_update']['pages_grouped'] = true;
                if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                    return false;
                }
            } else if ($pageGrouping === 'timeout') {
                setUpdateMsg(1, 'timeout');
                return false;
            } else if ($pageGrouping === false) {
                return false;
            } else if (!empty($pageGrouping)) {
                $arrDialogData = array(
                    'similarPages' => $contentMigration->similarPages,
                );

                setUpdateMsg('Inhaltsseiten gruppieren', 'title');
                setUpdateMsg($pageGrouping, 'msg');
                setUpdateMsg('<input type="submit" value="' . $_CORELANG['TXT_UPDATE_NEXT'] . '" name="updateNext" /><input type="hidden" name="processUpdate" id="processUpdate" />', 'button');
                setUpdateMsg($arrDialogData, 'dialog');
                return false;
            }
        }

        // Migrate aliases
        if (empty($_SESSION['contrexx_update']['aliases_migrated'])) {
            DBG::msg('Migrate aliases');
            if ($contentMigration->migrateAliases()) {
                $_SESSION['contrexx_update']['aliases_migrated'] = true;
                if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                    return false;
                }
            } else {
                return false;
            }
        }

        // Migrate blocks
        if (empty($_SESSION['contrexx_update']['blocks_migrated'])) {
            DBG::msg('Migrate blocks');
            if ($contentMigration->migrateBlocks()) {
                $_SESSION['contrexx_update']['blocks_migrated'] = true;
                if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                    return false;
                }
            } else {
                return false;
            }
        }

        // Drop old tables
        if (empty($_SESSION['contrexx_update']['old_tables_dropped'])) {
            DBG::msg('Drop old tables');
            if ($contentMigration->dropOldTables()) {
                $_SESSION['contrexx_update']['old_tables_dropped'] = true;
                if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                    return false;
                }
            }
        }
        ////////////////////////////
        // END: CONTENT MIGRATION //
        ////////////////////////////
    } else {
        ///////////////////////////////////////////
        // BEGIN: UPDATE FOR CONTREXX 3 OR NEWER //
        ///////////////////////////////////////////
        $result = _updateModuleRepository();
        if ($result === false) {
            DBG::msg('unable to update module repository');
            if (empty($objUpdate->arrStatusMsg['title'])) {
                setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_COMPONENT_BUG'], $_CORELANG['TXT_UPDATE_MODULE_REPOSITORY']), 'title');
            }
            return false;
        } else {
            try {
                \Cx\Lib\UpdateUtil::sql('UPDATE `'.DBPREFIX.'log_entry`
                    SET `object_class` = \'Cx\\\\Core\\\\ContentManager\\\\Model\\\\Entity\\\\Page\'
                    WHERE object_class = \'Cx\\\\Model\\\\ContentManager\\\\Page\'');
            } catch (\Cx\Lib\UpdateException $e) {
                return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
            }


            // before an update of module page can be done, the db changes have to be done
            \Cx\Lib\UpdateUtil::table(
                DBPREFIX . 'content_page',
                array(
                    'id'                                 => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                    'node_id'                            => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'id'),
                    'nodeIdShadowed'                     => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'node_id'),
                    'lang'                               => array('type' => 'INT(11)', 'after' => 'nodeIdShadowed'),
                    'type'                               => array('type' => 'VARCHAR(16)', 'after' => 'lang'),
                    'caching'                            => array('type' => 'TINYINT(1)', 'after' => 'type'),
                    'updatedAt'                          => array('type' => 'timestamp', 'after' => 'caching', 'notnull' => false),
                    'updatedBy'                          => array('type' => 'CHAR(40)', 'after' => 'updatedAt'),
                    'title'                              => array('type' => 'VARCHAR(255)', 'after' => 'updatedBy'),
                    'linkTarget'                         => array('type' => 'VARCHAR(16)', 'notnull' => false, 'after' => 'title'),
                    'contentTitle'                       => array('type' => 'VARCHAR(255)', 'after' => 'linkTarget'),
                    'slug'                               => array('type' => 'VARCHAR(255)', 'after' => 'contentTitle'),
                    'content'                            => array('type' => 'longtext', 'after' => 'slug'),
                    'sourceMode'                         => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'content'),
                    'customContent'                      => array('type' => 'VARCHAR(64)', 'notnull' => false, 'after' => 'sourceMode'),
                    'useCustomContentForAllChannels'     => array('type' => 'INT(2)', 'after' => 'customContent', 'notnull' => false),
                    'cssName'                            => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'useCustomContentForAllChannels'),
                    'cssNavName'                         => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'cssName'),
                    'skin'                               => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'cssNavName'),
                    'useSkinForAllChannels'              => array('type' => 'INT(2)', 'after' => 'skin', 'notnull' => false),
                    'metatitle'                          => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'useSkinForAllChannels'),
                    'metadesc'                           => array('type' => 'text', 'after' => 'metatitle'),
                    'metakeys'                           => array('type' => 'text', 'after' => 'metadesc'),
                    'metarobots'                         => array('type' => 'VARCHAR(7)', 'notnull' => false, 'after' => 'metakeys'),
                    'start'                              => array('type' => 'timestamp', 'after' => 'metarobots', 'notnull' => false),
                    'end'                                => array('type' => 'timestamp', 'after' => 'start', 'notnull' => false),
                    'editingStatus'                      => array('type' => 'VARCHAR(16)', 'after' => 'end'),
                    'protection'                         => array('type' => 'INT(11)', 'after' => 'editingStatus'),
                    'frontendAccessId'                   => array('type' => 'INT(11)', 'after' => 'protection'),
                    'backendAccessId'                    => array('type' => 'INT(11)', 'after' => 'frontendAccessId'),
                    'display'                            => array('type' => 'TINYINT(1)', 'after' => 'backendAccessId'),
                    'active'                             => array('type' => 'TINYINT(1)', 'after' => 'display'),
                    'target'                             => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'active'),
                    'module'                             => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'target'),
                    'cmd'                                => array('type' => 'VARCHAR(50)', 'notnull' => true, 'default' => '', 'after' => 'module')
                ),
                array(
                    'node_id'                            => array('fields' => array('node_id','lang'), 'type' => 'UNIQUE'),
                    'IDX_D8E86F54460D9FD7'               => array('fields' => array('node_id'))
                ),
                'InnoDB',
                '',
                array(
                    'node_id' => array(
                        'table'     => DBPREFIX.'content_node',
                        'column'    => 'id',
                        'onDelete'  => 'SET NULL',
                        'onUpdate'  => 'NO ACTION',
                    ),
                )
            );

            if (_convertThemes2Component() === false) {
                if (empty($objUpdate->arrStatusMsg['title'])) {
                    DBG::msg('unable to convert themes to component');
                    setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_COMPONENT_BUG'], $_CORELANG['TXT_UPDATE_CONVERT_TEMPLATES']), 'title');
                }
                return false;
            }

            if (_updateModulePages($viewUpdateTable) === false) {
                if (empty($objUpdate->arrStatusMsg['title'])) {
                    DBG::msg('unable to update module templates');
                    setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_COMPONENT_BUG'], $_CORELANG['TXT_UPDATE_MODULE_TEMPLATES']), 'title');
                }
                return false;
            }/* else {
                if (!in_array('moduleStyles', $_SESSION['contrexx_update']['update']['done'])) {
                    if (_updateCssDefinitions($viewUpdateTable, $objUpdate) === false) {
                        if (empty($objUpdate->arrStatusMsg['title'])) {
                            setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_COMPONENT_BUG'], $_CORELANG['TXT_UPDATE_MODULE_TEMPLATES']), 'title');
                        }
                        return false;
                    }
                    $_SESSION['contrexx_update']['update']['done'][] = 'moduleStyles';
                }
            }*/
        }

        // we are updating from 3.0.0 rc1, rc2, stable or 3.0.0.1
        if (!include_once(dirname(__FILE__) . '/update3.php')) {
            setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_UNABLE_LOAD_UPDATE_COMPONENT'], dirname(__FILE__) . '/update3.php'));
            return false;
        }

        if (!createHtAccess()) {
            $webServerSoftware = !empty($_SERVER['SERVER_SOFTWARE']) && stristr($_SERVER['SERVER_SOFTWARE'], 'apache') ? 'apache' : (stristr($_SERVER['SERVER_SOFTWARE'], 'iis') ? 'iis' : '');
            $file = $webServerSoftware == 'iis' ? 'web.config' : '.htaccess';

            setUpdateMsg('Die Datei \'' . $file . '\' konnte nicht erstellt/aktualisiert werden.');
            return false;
        }

        // Update configuration.php
        if (!_writeNewConfigurationFile()) {
            return false;
        }

        $arrUpdate = $objUpdate->getLoadedVersionInfo();
        $_CONFIG['coreCmsVersion'] = $arrUpdate['cmsVersion'];

        $lupd = new License();
        try {
            $lupd->update(false);
        } catch (\Cx\Lib\UpdateException $e) {
            setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_COMPONENT_BUG'], $_CORELANG['TXT_UPDATE_LICENSE_DATA']), 'title');
            return false;
        }

        return true;
        /////////////////////////////////////////
        // END: UPDATE FOR CONTREXX 3 OR NEWER //
        /////////////////////////////////////////
    }


    ///////////////////////////////////////////
    // CONTINUE UPDATE FOR NON CX 3 VERSIONS //
    ///////////////////////////////////////////

    $arrDirs = array('core_module', 'module');
    $updateStatus = true;

    if (!include_once(dirname(__FILE__) . '/components/core/backendAreas.php')) {
        setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_UNABLE_LOAD_UPDATE_COMPONENT'], dirname(__FILE__) . '/components/core/backendAreas.php'));
        return false;
    } elseif (!include_once(dirname(__FILE__) . '/components/core/modules.php')) {
        setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_UNABLE_LOAD_UPDATE_COMPONENT'], dirname(__FILE__) . '/components/core/modules.php'));
        return false;
    } elseif (!include_once(dirname(__FILE__) . '/components/core/settings.php')) {
        setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_UNABLE_LOAD_UPDATE_COMPONENT'], dirname(__FILE__) . '/components/core/settings.php'));
        return false;
    }

    if (!in_array('coreUpdate', ContrexxUpdate::_getSessionArray($_SESSION['contrexx_update']['update']['done']))) {
        $result = _coreUpdate();
        if ($result === false) {
            if (empty($objUpdate->arrStatusMsg['title'])) {
                setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_COMPONENT_BUG'], $_CORELANG['TXT_UPDATE_CORE_SYSTEM']), 'title');
            }
            return false;
        } else {
            $_SESSION['contrexx_update']['update']['done'][] = 'coreUpdate';
        }
    }


    $missedModules = array();
    if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.0.0')) {
        $missedModules = getMissedModules();
        $conflictedModules = getConflictedModules($missedModules);
        if (!empty($conflictedModules)) {
            $conflictedModulesList = '';
            foreach ($conflictedModules as $moduleName => $moduleTables) {
                $conflictedModulesList = '<li><strong>'.$moduleName.':</strong> '.implode(', ', $moduleTables).'</li>';
            }
            setUpdateMsg($_CORELANG['TXT_CONFLICTED_MODULES_TITLE'], 'title');
            setUpdateMsg($_CORELANG['TXT_CONFLICTED_MODULES_DESCRIPTION'].'<ul>'.$conflictedModulesList.'</ul>', 'msg');
            setUpdateMsg('<input type="submit" value="'.$_CORELANG['TXT_UPDATE_TRY_AGAIN'].'" name="updateNext" /><input type="hidden" name="processUpdate" id="processUpdate" />', 'button');
            return false;
        }
    }
    foreach ($arrDirs as $dir) {
        $dh = opendir(dirname(__FILE__).'/components/'.$dir);
        if ($dh) {
            while (($file = readdir($dh)) !== false) {
                if (!in_array($file, ContrexxUpdate::_getSessionArray($_SESSION['contrexx_update']['update']['done']))) {
                    $fileInfo = pathinfo(dirname(__FILE__).'/components/'.$dir.'/'.$file);

                    if ($fileInfo['extension'] == 'php') {
                        DBG::msg("--------- updating $file ------");

                        if (!include_once(dirname(__FILE__).'/components/'.$dir.'/'.$file)) {
                            setUpdateMsg($_CORELANG['TXT_UPDATE_ERROR'], 'title');
                            setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_UNABLE_LOAD_UPDATE_COMPONENT'], dirname(__FILE__).'/components/'.$dir.'/'.$file));
                            return false;
                        }

                        if (!in_array($fileInfo['filename'], $missedModules)) {
                            $function = '_'.$fileInfo['filename'].'Update';
                            if (function_exists($function)) {
                                $result = $function();
                                if ($result === false) {
                                    if (empty($objUpdate->arrStatusMsg['title'])) {
                                        setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_COMPONENT_BUG'], $file), 'title');
                                    }
                                    return false;
                                } elseif ($result === 'timeout') {
                                    setUpdateMsg(1, 'timeout');
                                    return false;
                                }
                            } else {
                                setUpdateMsg($_CORELANG['TXT_UPDATE_ERROR'], 'title');
                                setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_UPDATE_COMPONENT_CORRUPT'], '.'.$fileInfo['filename'], $file));
                                return false;
                            }
                        } else {
                            $function = '_'.$fileInfo['filename'].'Install';
                            if (function_exists($function)) {
                                $result = $function();
                                if ($result === false) {
                                    if (empty($objUpdate->arrStatusMsg['title'])) {
                                        setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_COMPONENT_BUG'], $file), 'title');
                                    }
                                    return false;
                                } elseif ($result === 'timeout') {
                                    setUpdateMsg(1, 'timeout');
                                    return false;
                                } else {
                                    // fetch module info from components/core/module.php
                                    $arrModule = getModuleInfo($fileInfo['filename']);
                                    if ($arrModule) {
                                        try {
                                            \Cx\Lib\UpdateUtil::sql("INSERT INTO ".DBPREFIX."modules ( `id` , `name` , `description_variable` , `status` , `is_required` , `is_core` , `distributor` ) VALUES ( ".$arrModule['id']." , '".$arrModule['name']."', '".$arrModule['description_variable']."', '".$arrModule['status']."', '".$arrModule['is_required']."', '".$arrModule['is_core']."', 'Comvation AG') ON DUPLICATE KEY UPDATE `id` = `id`");
                                        } catch (\Cx\Lib\UpdateException $e) {
                                            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
                                        }
                                    } else {
                                        DBG::msg('unable to register module '.$fileInfo['filename']);
                                    }
                                }
                            } else {
                                setUpdateMsg($_CORELANG['TXT_UPDATE_ERROR'], 'title');
                                setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_UPDATE_COMPONENT_CORRUPT'], '.'.$fileInfo['filename'], $file));
                                return false;
                            }
                        }
                    }

                    $_SESSION['contrexx_update']['update']['done'][] = $file;
                    setUpdateMsg(1, 'timeout');
                    return false;
                }
            }
        } else {
            setUpdateMsg($_CORELANG['TXT_UPDATE_ERROR'], 'title');
            setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_UNABLE_LOAD_DIR_COMPONENTS'], dirname(__FILE__).'/components/'.$dir));
            return false;
        }

        closedir($dh);
    }

    if (!in_array('coreSettings', ContrexxUpdate::_getSessionArray($_SESSION['contrexx_update']['update']['done']))) {
        $result = _updateSettings();
        if ($result === false) {
            if (empty($objUpdate->arrStatusMsg['title'])) {
                setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_COMPONENT_BUG'], $_CORELANG['TXT_UPDATE_BASIC_CONFIGURATION']), 'title');
            }
            return false;
        } else {
            // update configuration.php (migrate to new format)
            if (!_writeNewConfigurationFile()) {
                return false;
            }
            $_SESSION['contrexx_update']['update']['done'][] = 'coreSettings';

            // till this point the file config/version.php was still loaded upon a request,
            // therefore we must force a new page request here, to ensure that the file config/version.php
            // will not be loaded anylonger. This is essential here, otherwise the old values of config/version.php
            // would screw up the update process
            setUpdateMsg(1, 'timeout');
            return false;
        }
    }

    if (!in_array('coreModules', ContrexxUpdate::_getSessionArray($_SESSION['contrexx_update']['update']['done']))) {
        $result = _updateModules();
        if ($result === false) {
            if (empty($objUpdate->arrStatusMsg['title'])) {
                setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_COMPONENT_BUG'], $_CORELANG['TXT_UPDATE_MODULES']), 'title');
            }
            return false;
        } else {
            $_SESSION['contrexx_update']['update']['done'][] = 'coreModules';
        }
    }

    if (!in_array('coreBackendAreas', ContrexxUpdate::_getSessionArray($_SESSION['contrexx_update']['update']['done']))) {
        $result = _updateBackendAreas();
        if ($result === false) {
            if (empty($objUpdate->arrStatusMsg['title'])) {
                setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_COMPONENT_BUG'], $_CORELANG['TXT_UPDATE_SECURITY_SYSTEM']), 'title');
            }
            return false;
        } else {
            $_SESSION['contrexx_update']['update']['done'][] = 'coreBackendAreas';
        }
    }

    if (!in_array('coreModuleRepository', ContrexxUpdate::_getSessionArray($_SESSION['contrexx_update']['update']['done']))) {
        $result = _updateModuleRepository();
        if ($result === false) {
            DBG::msg('unable to update module repository');
            if (empty($objUpdate->arrStatusMsg['title'])) {
                setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_COMPONENT_BUG'], $_CORELANG['TXT_UPDATE_MODULE_REPOSITORY']), 'title');
            }
            return false;
        } else {
            $_SESSION['contrexx_update']['update']['done'][] = 'coreModuleRepository';
        }
    }

    if (!in_array('convertTemplates', ContrexxUpdate::_getSessionArray($_SESSION['contrexx_update']['update']['done']))) {
        $result = _convertThemes2Component();
        if ($result === false) {
            if (empty($objUpdate->arrStatusMsg['title'])) {
                DBG::msg('unable to convert themes to component');
                setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_COMPONENT_BUG'], $_CORELANG['TXT_UPDATE_CONVERT_TEMPLATES']), 'title');
            }
            return false;
        } else {
            $_SESSION['contrexx_update']['update']['done'][] = 'convertTemplates';
        }
    }

    if (!in_array('moduleTemplates', ContrexxUpdate::_getSessionArray($_SESSION['contrexx_update']['update']['done']))) {
        if (_updateModulePages($viewUpdateTable) === false) {
            if (empty($objUpdate->arrStatusMsg['title'])) {
                DBG::msg('unable to update module templates');
                setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_COMPONENT_BUG'], $_CORELANG['TXT_UPDATE_MODULE_TEMPLATES']), 'title');
            }
            return false;
        } else {
            $_SESSION['contrexx_update']['update']['done'][] = 'moduleTemplates';
        }
    }

    if (!in_array('moduleStyles', ContrexxUpdate::_getSessionArray($_SESSION['contrexx_update']['update']['done']))) {
        if (_updateCssDefinitions($viewUpdateTable, $objUpdate) === false) {
            if (empty($objUpdate->arrStatusMsg['title'])) {
                setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_COMPONENT_BUG'], $_CORELANG['TXT_UPDATE_MODULE_TEMPLATES']), 'title');
            }
            return false;
        } else {
            $_SESSION['contrexx_update']['update']['done'][] = 'moduleStyles';
        }
    }

    if (!in_array('navigations', ContrexxUpdate::_getSessionArray($_SESSION['contrexx_update']['update']['done']))) {
        if (_updateNavigations() === false) {
            if (empty($objUpdate->arrStatusMsg['title'])) {
                setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_COMPONENT_BUG'], $_CORELANG['TXT_UPDATE_NAVIGATIONS']), 'title');
            }
            return false;
        } else {
            $_SESSION['contrexx_update']['update']['done'][] = 'navigations';
        }
    }

    if (!createHtAccess()) {
        $webServerSoftware = !empty($_SERVER['SERVER_SOFTWARE']) && stristr($_SERVER['SERVER_SOFTWARE'], 'apache') ? 'apache' : (stristr($_SERVER['SERVER_SOFTWARE'], 'iis') ? 'iis' : '');
        $file = $webServerSoftware == 'iis' ? 'web.config' : '.htaccess';

        setUpdateMsg('Die Datei \'' . $file . '\' konnte nicht erstellt/aktualisiert werden.');
        return false;
    }

    if (file_exists(ASCMS_DOCUMENT_ROOT.ASCMS_BACKEND_PATH.'/index.php')) {
        \DBG::msg('/cadmin/index.php still exists...');
        // move cadmin index.php if its customized
        if (!loadMd5SumOfOriginalCxFiles()) {
            return false;
        }
        if (!verifyMd5SumOfFile(ASCMS_DOCUMENT_ROOT.ASCMS_BACKEND_PATH.'/index.php', '', false)) {
            \DBG::msg('...and it\'s customized, so let\'s move it to customizing directory');
            // changes, backup modified file
            if (!backupModifiedFile(ASCMS_DOCUMENT_ROOT.ASCMS_BACKEND_PATH.'/index.php')) {
                setUpdateMsg('Die Datei \''.ASCMS_DOCUMENT_ROOT.ASCMS_BACKEND_PATH.'/index.php\' konnte nicht kopiert werden.');
                return false;
            }
        } else {
            \DBG::msg('...but it\'s not customized');
        }
        // no non-backupped changes, can delete
        try {
            \DBG::msg('So let\'s remove it...');
            $cadminIndex = new \Cx\Lib\FileSystem\File(ASCMS_DOCUMENT_ROOT.ASCMS_BACKEND_PATH.'/index.php');
            $cadminIndex->delete();
        } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
            setUpdateMsg('Die Datei \''.ASCMS_DOCUMENT_ROOT.ASCMS_BACKEND_PATH.'/index.php\' konnte nicht gelöscht werden.');
            return false;
        }
    }

    $arrUpdate = $objUpdate->getLoadedVersionInfo();
    $_CONFIG['coreCmsVersion'] = $arrUpdate['cmsVersion'];

    if (!in_array('coreLicense', ContrexxUpdate::_getSessionArray($_SESSION['contrexx_update']['update']['done']))) {
        $lupd = new License();
        try {
            $result = $lupd->update();
        } catch (\Cx\Lib\UpdateException $e) {
            setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_COMPONENT_BUG'], $_CORELANG['TXT_UPDATE_LICENSE_DATA']), 'title');
            return false;
        }
        // ignore error to allow offline installations
        /*if ($result === false) {
            if (empty($objUpdate->arrStatusMsg['title'])) {
                setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_COMPONENT_BUG'], $_CORELANG['TXT_UPDATE_LICENSE_DATA']), 'title');
            }
            return false;
        } else {*/
        $_SESSION['contrexx_update']['update']['done'][] = 'coreLicense';
        //}
    }

    return true;
}

function getMissedModules() {
    global $objUpdate, $_CONFIG;
    $installedModules = array();
    $result = \Cx\Lib\UpdateUtil::sql('SELECT `name`, `description_variable` FROM `'.DBPREFIX.'modules` WHERE `status` = "y" ORDER BY `name` ASC');
    if ($result) {
        while (!$result->EOF) {
            $installedModules[] = $result->fields['name'];
            $result->MoveNext();
        }
    }

    // the egov module is installed but not turned to 'y' in update
    if (   $objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '2.0.3')
        && !$objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '2.0.2')
        && \Cx\Lib\UpdateUtil::table_exist(DBPREFIX . 'module_egov_configuration')) {
        $installedModules[] = 'egov';
    }

    $missedModules = array();
    $potentialMissedModules = array('blog', 'crm', 'calendar', 'directory', 'docsys', 'egov', 'feed', 'forum', 'gallery', 'guestbook', 'livecam', 'market', 'memberdir', 'newsletter', 'podcast', 'shop', 'voting', 'downloads', 'ecard', 'jobs', 'knowledge', 'mediadir');
    foreach ($potentialMissedModules as $module) {
        if (!in_array($module, $installedModules)) {
            $missedModules[] = $module;
        }
    }

    return $missedModules;
}


function getConflictedModules($missedModules) {
    $potentialMissedTables = array(
        'blog' => array(
            DBPREFIX.'module_blog_comments',
            DBPREFIX.'module_blog_categories',
            DBPREFIX.'module_blog_messages',
            DBPREFIX.'module_blog_messages_lang',
            DBPREFIX.'module_blog_message_to_category',
            DBPREFIX.'module_blog_networks',
            DBPREFIX.'module_blog_networks_lang',
            DBPREFIX.'module_blog_settings',
            DBPREFIX.'module_blog_votes',
        ),
        'calendar' => array(
            DBPREFIX.'module_calendar',
            DBPREFIX.'module_calendar_categories',
            DBPREFIX.'module_calendar_form_data',
            DBPREFIX.'module_calendar_form_fields',
            DBPREFIX.'module_calendar_registrations',
            DBPREFIX.'module_calendar_settings',
            DBPREFIX.'module_calendar_style',
        ),
        'directory' => array(
            DBPREFIX.'module_directory_categories',
            DBPREFIX.'module_directory_dir',
            DBPREFIX.'module_directory_inputfields',
            DBPREFIX.'module_directory_levels',
            DBPREFIX.'module_directory_mail',
            DBPREFIX.'module_directory_rel_dir_cat',
            DBPREFIX.'module_directory_rel_dir_level',
            DBPREFIX.'module_directory_settings',
            DBPREFIX.'module_directory_settings_google',
            DBPREFIX.'module_directory_vote',
        ),
        'docsys' => array(
            DBPREFIX.'module_docsys',
            DBPREFIX.'module_docsys_categories',
            DBPREFIX.'module_docsys_entry_category',
        ),
        'egov' => array(
            DBPREFIX.'module_egov_configuration',
            DBPREFIX.'module_egov_orders',
            DBPREFIX.'module_egov_products',
            DBPREFIX.'module_egov_product_calendar',
            DBPREFIX.'module_egov_product_fields',
            DBPREFIX.'module_egov_settings',
        ),
        'feed' => array(
            DBPREFIX.'module_feed_category',
            DBPREFIX.'module_feed_news',
            DBPREFIX.'module_feed_newsml_association',
            DBPREFIX.'module_feed_newsml_categories',
            DBPREFIX.'module_feed_newsml_documents',
            DBPREFIX.'module_feed_newsml_providers',
        ),
        'forum' => array(
            DBPREFIX.'module_forum_access',
            DBPREFIX.'module_forum_categories',
            DBPREFIX.'module_forum_categories_lang',
            DBPREFIX.'module_forum_notification',
            DBPREFIX.'module_forum_postings',
            DBPREFIX.'module_forum_rating',
            DBPREFIX.'module_forum_settings',
            DBPREFIX.'module_forum_statistics',
        ),
        'gallery' => array(
            DBPREFIX.'module_gallery_categories',
            DBPREFIX.'module_gallery_comments',
            DBPREFIX.'module_gallery_language',
            DBPREFIX.'module_gallery_language_pics',
            DBPREFIX.'module_gallery_pictures',
            DBPREFIX.'module_gallery_settings',
            DBPREFIX.'module_gallery_votes',
        ),
        'guestbook' => array(
            DBPREFIX.'module_guestbook',
            DBPREFIX.'module_guestbook_settings',
        ),
        'livecam' => array(
            DBPREFIX.'module_livecam',
            DBPREFIX.'module_livecam_settings',
        ),
        'market' => array(
            DBPREFIX.'module_market',
            DBPREFIX.'module_market_categories',
            DBPREFIX.'module_market_mail',
            DBPREFIX.'module_market_paypal',
            DBPREFIX.'module_market_settings',
            DBPREFIX.'module_market_spez_fields'
        ),
        'memberdir' => array(
            DBPREFIX.'module_memberdir_directories',
            DBPREFIX.'module_memberdir_name',
            DBPREFIX.'module_memberdir_settings',
            DBPREFIX.'module_memberdir_values'
        ),
        'newsletter' => array(
            DBPREFIX.'module_newsletter',
            DBPREFIX.'module_newsletter_access_user',
            DBPREFIX.'module_newsletter_attachment',
            DBPREFIX.'module_newsletter_category',
            DBPREFIX.'module_newsletter_confirm_mail',
            DBPREFIX.'module_newsletter_email_link',
            DBPREFIX.'module_newsletter_email_link_feedback',
            DBPREFIX.'module_newsletter_rel_cat_news',
            DBPREFIX.'module_newsletter_rel_usergroup_newsletter',
            DBPREFIX.'module_newsletter_rel_user_cat',
            DBPREFIX.'module_newsletter_settings',
            DBPREFIX.'module_newsletter_template',
            DBPREFIX.'module_newsletter_tmp_sending',
            DBPREFIX.'module_newsletter_user',
            DBPREFIX.'module_newsletter_user_title',
        ),
        'podcast' => array(
            DBPREFIX.'module_podcast_category',
            DBPREFIX.'module_podcast_medium',
            DBPREFIX.'module_podcast_rel_category_lang',
            DBPREFIX.'module_podcast_rel_medium_category',
            DBPREFIX.'module_podcast_settings',
            DBPREFIX.'module_podcast_template',
        ),
        'shop' => array(
            DBPREFIX.'core_mail_template',
            DBPREFIX.'core_country',
            DBPREFIX.'module_shop_article_group',
            DBPREFIX.'module_shop_attribute',
            DBPREFIX.'module_shop_categories',
            DBPREFIX.'module_shop_countries',
            DBPREFIX.'module_shop_currencies',
            DBPREFIX.'module_shop_customer_group',
            DBPREFIX.'module_shop_discountgroup_count_name',
            DBPREFIX.'module_shop_discountgroup_count_rate',
            DBPREFIX.'module_shop_discount_coupon',
            DBPREFIX.'module_shop_importimg',
            DBPREFIX.'module_shop_lsv',
            DBPREFIX.'module_shop_mail',
            DBPREFIX.'module_shop_mail_content',
            DBPREFIX.'module_shop_manufacturer',
            DBPREFIX.'module_shop_option',
            DBPREFIX.'module_shop_orders',
            DBPREFIX.'module_shop_order_attributes',
            DBPREFIX.'module_shop_order_items',
            DBPREFIX.'module_shop_payment',
            DBPREFIX.'module_shop_payment_processors',
            DBPREFIX.'module_shop_pricelists',
            DBPREFIX.'module_shop_products',
            DBPREFIX.'module_shop_products_downloads',
            DBPREFIX.'module_shop_rel_countries',
            DBPREFIX.'module_shop_rel_customer_coupon',
            DBPREFIX.'module_shop_rel_discount_group',
            DBPREFIX.'module_shop_rel_payment',
            DBPREFIX.'module_shop_rel_product_attribute',
            DBPREFIX.'module_shop_rel_shipper',
            DBPREFIX.'module_shop_shipment_cost',
            DBPREFIX.'module_shop_shipper',
            DBPREFIX.'module_shop_vat',
            DBPREFIX.'module_shop_zones',
        ),
        'voting' => array(
            DBPREFIX.'voting_additionaldata',
            DBPREFIX.'voting_email',
            DBPREFIX.'voting_rel_email_system',
            DBPREFIX.'voting_results',
            DBPREFIX.'voting_system',
        ),
        'downloads' => array(
            DBPREFIX.'module_downloads_category',
            DBPREFIX.'module_downloads_category_locale',
            DBPREFIX.'module_downloads_download',
            DBPREFIX.'module_downloads_download_locale',
            DBPREFIX.'module_downloads_group',
            DBPREFIX.'module_downloads_group_locale',
            DBPREFIX.'module_downloads_rel_download_category',
            DBPREFIX.'module_downloads_rel_download_download',
            DBPREFIX.'module_downloads_rel_group_category',
            DBPREFIX.'module_downloads_settings',
        ),
        'ecard' => array(
            DBPREFIX.'module_ecard_ecards',
            DBPREFIX.'module_ecard_settings',
        ),
        'jobs' => array(
            DBPREFIX.'module_jobs',
            DBPREFIX.'module_jobs_categories',
            DBPREFIX.'module_jobs_location',
            DBPREFIX.'module_jobs_rel_loc_jobs',
            DBPREFIX.'module_jobs_settings',
        ),
        'knowledge' => array(
            DBPREFIX.'module_knowledge_articles',
            DBPREFIX.'module_knowledge_article_content',
            DBPREFIX.'module_knowledge_categories',
            DBPREFIX.'module_knowledge_categories_content',
            DBPREFIX.'module_knowledge_settings',
            DBPREFIX.'module_knowledge_tags',
            DBPREFIX.'module_knowledge_tags_articles',
        ),
        'mediadir' => array(
            DBPREFIX.'module_mediadir_categories',
            DBPREFIX.'module_mediadir_categories_names',
            DBPREFIX.'module_mediadir_comments',
            DBPREFIX.'module_mediadir_entries',
            DBPREFIX.'module_mediadir_forms',
            DBPREFIX.'module_mediadir_form_names',
            DBPREFIX.'module_mediadir_inputfields',
            DBPREFIX.'module_mediadir_inputfield_names',
            DBPREFIX.'module_mediadir_inputfield_types',
            DBPREFIX.'module_mediadir_inputfield_verifications',
            DBPREFIX.'module_mediadir_levels',
            DBPREFIX.'module_mediadir_level_names',
            DBPREFIX.'module_mediadir_mails',
            DBPREFIX.'module_mediadir_mail_actions',
            DBPREFIX.'module_mediadir_masks',
            DBPREFIX.'module_mediadir_order_rel_forms_selectors',
            DBPREFIX.'module_mediadir_rel_entry_categories',
            DBPREFIX.'module_mediadir_rel_entry_inputfields',
            DBPREFIX.'module_mediadir_rel_entry_inputfields_clean1',
            DBPREFIX.'module_mediadir_rel_entry_levels',
            DBPREFIX.'module_mediadir_settings',
            DBPREFIX.'module_mediadir_settings_num_categories',
            DBPREFIX.'module_mediadir_settings_num_entries',
            DBPREFIX.'module_mediadir_settings_num_levels',
            DBPREFIX.'module_mediadir_settings_perm_group_forms',
            DBPREFIX.'module_mediadir_votes'
        ),
    );

    $conflictedModules = array();
    foreach ($missedModules as $module) {
        foreach ($potentialMissedTables[$module] as $table) {
            if (\Cx\Lib\UpdateUtil::table_exist($table)) {
                $result = \Cx\Lib\UpdateUtil::sql('SHOW TABLE STATUS WHERE `Name` = "'.$table.'"');
                if ($result && ($result->RecordCount() > 0) && (strpos($result->fields['Comment'], 'cx3upgrade') === false)) {
                    $conflictedModules[$module][] = $table;
                }
            }
        }
    }

    return $conflictedModules;
}

function _updateModuleRepository() {
    global $_CORELANG, $objUpdate, $objDatabase;

    $count = 0;

    $dh = opendir(dirname(__FILE__) . '/components/core');
    if ($dh) {

        $query = "TRUNCATE TABLE ".DBPREFIX."module_repository";
        if ($objDatabase->Execute($query) === false) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }

        try {
            \Cx\Lib\UpdateUtil::table(
                DBPREFIX.'module_repository',
                array(
                    'id'                 => array('type' => 'INT(6)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true),
                    'moduleid'           => array('type' => 'INT(5)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'id'),
                    'content'            => array('type' => 'mediumtext', 'after' => 'moduleid'),
                    'title'              => array('type' => 'VARCHAR(250)', 'notnull' => true, 'default' => '', 'after' => 'content'),
                    'cmd'                => array('type' => 'VARCHAR(20)', 'notnull' => true, 'default' => '', 'after' => 'title'),
                    'expertmode'         => array('type' => 'SET(\'y\',\'n\')', 'notnull' => true, 'default' => 'n', 'after' => 'cmd'),
                    'parid'              => array('type' => 'INT(5)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'expertmode'),
                    'displaystatus'      => array('type' => 'SET(\'on\',\'off\')', 'notnull' => true, 'default' => 'on', 'after' => 'parid'),
                    'username'           => array('type' => 'VARCHAR(250)', 'notnull' => true, 'default' => '', 'after' => 'displaystatus'),
                    'displayorder'       => array('type' => 'SMALLINT(6)', 'notnull' => true, 'default' => '100', 'after' => 'username')
                ),
                array(
                    'contentid'          => array('fields' => array('id'), 'type' => 'UNIQUE'),
                    'fulltextindex'      => array('fields' => array('title','content'), 'type' => 'FULLTEXT')
                )
            );
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }

        while (($file = readdir($dh)) !== false) {
            if (preg_match('#^repository_([0-9]+)\.php$#', $file, $arrFunction)) {
                if (!in_array($file, ContrexxUpdate::_getSessionArray($_SESSION['contrexx_update']['update']['done']))) {
                    if (function_exists('memory_get_usage')) {
                        if (!checkMemoryLimit()) {
                            return false;
                        }
                    } else {
                        $count++;
                    }

                    if (!include_once(dirname(__FILE__) . '/components/core/' . $file)) {
                        setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_UNABLE_LOAD_UPDATE_COMPONENT'], dirname(__FILE__) . '/components/core/' . $file));
                        return false;
                    }
                    $function = '_updateModuleRepository_' . $arrFunction[1];
                    if (function_exists($function)) {
                        DBG::msg("---------------------- update: calling $function() ---------");
                        $result = $function();
                        if ($result === false) {
                            DBG::msg("---------------------- update: calling $function() failed ---------");
                            if (empty($objUpdate->arrStatusMsg['title'])) {
                                setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_COMPONENT_BUG'], $file), 'title');
                            }
                            return false;
                        }
                    } else {
                        DBG::msg("---------------------- update: calling $function() failed, function does not exist ---------");
                        setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_UPDATE_COMPONENT_CORRUPT'], $_CORELANG['TXT_UPDATE_MODULE_REPOSITORY'], $arrFunction[1]));
                        return false;
                    }

                    $_SESSION['contrexx_update']['update']['done'][] = $file;

                    if ($count == 10) {
                        setUpdateMsg($_CORELANG['TXT_UPDATE_PROCESS_HALTED'], 'title');
                        setUpdateMsg($_CORELANG['TXT_UPDATE_PROCESS_HALTED_RAM_MSG'] . '<br /><br />', 'msg');
                        setUpdateMsg('<input type="submit" value="' . $_CORELANG['TXT_CONTINUE_UPDATE'] . '" name="updateNext" /><input type="hidden" name="processUpdate" id="processUpdate" />', 'button');
                        return false;
                    }
                }
            }
        }
    } else {
        setUpdateMsg($_CORELANG['TXT_UPDATE_UNABLE_LOAD_REPOSITORY_PARTS']);
        return false;
    }

    closedir($dh);

    return true;
}

function _updateModulePages(&$viewUpdateTable) {
    global $objUpdate, $_CONFIG, $objDatabase;

    foreach ($viewUpdateTable as $module=>$data) {
        $version = $data['version'];
        // only update templates if the installed version is older than $version
        if (!$objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], $version)) {
            continue;
        }
        $em = \Env::get('em');
        $pageRepo = $em->getRepository('\Cx\Core\ContentManager\Model\Entity\Page');
        $pages = $pageRepo->findBy(array(
            'module' => $module,
            'type'   => \Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION,
        ));
        $objResult = $objDatabase->Execute('
            SELECT
                `id`
            FROM
                ' . DBPREFIX . 'modules
            WHERE
                `name` LIKE \'' . $module . '\'
        ');
        if ($objResult) {
            if (!$objResult->EOF) {
                $moduleId = $objResult->fields['id'];
            }
        } else {
            return false;
        }
        foreach ($pages as $page) {
            $query = '
                SELECT
                    `content`
                FROM
                    ' . DBPREFIX . 'module_repository
                WHERE
                    `moduleid` = ' . $moduleId . ' AND
                    `cmd` LIKE \'' . $page->getCmd() . '\'
            ';
            $objResult = $objDatabase->Execute($query);
            if (!$objResult) {
                return false;
            }
            if ($objResult->EOF) {
                DBG::msg('unable to load module repository of page with section ' . $module . ' and cmd ' . $page->getCmd());
                continue;
            }
            $page->setContent($objResult->fields['content']);
            $page->setSourceMode(true);
            $page->setUpdatedAtToNow();
            $em->persist($page);
            $em->flush();
        }
    }
    return true;
}

function _updateCssDefinitions(&$viewUpdateTable, $objUpdate) {
    global $objDatabase, $_CORELANG;

    // Find all themes
    $result = $objDatabase->Execute('SELECT `themesname`, `foldername` FROM `' . DBPREFIX . 'skins`');
    if ($result->EOF) {
        \DBG::msg('No themes, really?');
        return false;
    }

    // Find type for theme and update its CSS definitions
    $errorMessages = '';
    while (!$result->EOF) {
        if (!is_dir(ASCMS_THEMES_PATH . '/' . $result->fields['foldername'])) {
            \DBG::msg('Skipping theme "' . $result->fields['themesname'] . '"; No such folder!');
            $errorMessages .= sprintf($_CORELANG['TXT_CSS_UPDATE_MISSING_FOLDER'], $result->fields['themesname']);
            $result->moveNext();
            continue;
        }
        if (preg_match('/print/', $result->fields['themesname'])) {
            $type = 'print';
        } else if (preg_match('/pdf/', $result->fields['themesname'])) {
            $type = 'pdf';
        } else if (preg_match('/mobile/', $result->fields['themesname'])) {
            $type = 'mobile';
        } else if (preg_match('/app/', $result->fields['themesname'])) {
            $type = 'app';
        } else {
            $type = 'standard';
        }
        \DBG::msg('Updating CSS definitions for theme "' . $result->fields['themesname'] . '" (' . $type . ')');
        if (!_updateCssDefinitionsForTemplate($result->fields['foldername'], $type, $viewUpdateTable, $objUpdate)) {
            \DBG::msg('CSS update for theme "' . $result->fields['themesname'] . '" failed');
            $errorMessages .= sprintf($_CORELANG['TXT_UPDATE_THEME_FAILED'], $result->fields['themesname']);
        }
        $result->moveNext();
    }
    if (!empty($errorMessages)) {
        setUpdateMsg('<div class="message-warning">' . $errorMessages . '</div>', 'msg');
        setUpdateMsg('<input type="submit" value="'.$_CORELANG['TXT_CONTINUE_UPDATE'].'" name="updateNext" /><input type="hidden" name="processUpdate" id="processUpdate" />', 'button');
        $_SESSION['contrexx_update']['update']['done'][] = 'moduleStyles';
        return false;
    }
    return true;
}

function _updateCssDefinitionsForTemplate($templatePath, $templateType, &$viewUpdateTable, $objUpdate) {
    global $objUpdate;

    \DBG::msg('Loading new module style definitions');
    $moduleStyles = _readNewCssDefinitions($templateType, $objUpdate->getLoadedVersionInfo());

    if ($moduleStyles === false) {
        return false;
    } else if ($moduleStyles === true) {
        // Skip if no source CSS file was found
        return true;
    }

    \DBG::msg('Calculating new module style definitions');
    $additionalCss = _calculateNewCss($viewUpdateTable, $moduleStyles, $objUpdate);

    if ($additionalCss === false) {
        return false;
    }
    if (empty($additionalCss)) {
        return true;
    }
    $version = $objUpdate->getLoadedVersionInfo();
    $version = $version['cmsVersion'];
    $additionalCss = '/***************************************************/
/* THESE ARE THE CSS MODULE STYLES FOR ' . $version .  '       */
/***************************************************/' . "\r\n\r\n" . $additionalCss;
    \DBG::msg('Writing new module style definitions');
    return _writeNewCss($templatePath, $additionalCss, $objUpdate->getLoadedVersionInfo());
}

/**
 * This reads /updates/{version}/data/modules.css and parses its contents
 * @return mixed Module styles as array({module_name}=>{css}), true if source file was not found or false on error
 */
function _readNewCssDefinitions($templateType, &$arrUpdate) {

    // Read and parse new modules.css
    try {
        $modulesCss = new \Cx\Lib\FileSystem\File(UPDATE_PATH.'/updates/' . $arrUpdate['cmsVersion'] . '/data/' . $templateType . '.css');
        $styleDefinitions = $modulesCss->getData();
    } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
        \DBG::msg($e->getMessage());
        return true;
    }
    // split css by module header comment
    $styleDefinitions = preg_split('#(?:[\s]*)/[\*]*/\n(?:[\s]*)/\*\sCSS (GLOBAL\s)?DEFINITIONS FOR#', $styleDefinitions);
    $moduleStyles = array();
    $matches = array();
    $moduleRegex = '#^ ([A-Z]*)\s?(?:[A-Z ]*)(?:[\s]*)\*/\n(?:[\s]*)/[\*]*/#';
    foreach ($styleDefinitions as $key=>$value) {
        // get module name from header
        if (!preg_match($moduleRegex, $value, $matches)) {
            // not a module
            continue;
        }
        // reconstruct header (this could be done more nicely)
        $moduleStyles[strtolower($matches[1])] = '/***************************************************/
/* CSS DEFINITIONS FOR' . $value;
    }
    \DBG::msg('--- loaded modules css definitions for modules: ---');
    \DBG::dump(array_keys($moduleStyles));
    return $moduleStyles;
}

/**
 * Merges CSS definitions of modules with updated template
 * @return mixed Additional CSS definitions as string or false on error
 */
function _calculateNewCss(&$viewUpdateTable, &$moduleStyles, $objUpdate) {
    global $_CONFIG;

    // Calculate new CSS definitions
    $additionalCss = array ();
    foreach ($viewUpdateTable as $module=>$data) {
        $version = $data['version'];
        $dependencies = $data['dependencies'];
        // only add css if the installed version is older than $version
        if (!$objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], $version)) {
            continue;
        }
        if (!isset($moduleStyles[$module])) {
            \DBG::msg('No style definitions for module "' . $module . '" in this theme type');
            continue;
        }
        if (!isset($additionalCss[$module])) {
            $additionalCss[$module] = $moduleStyles[$module];
        }
        foreach ($dependencies as $module) {
            if (!isset($additionalCss[$module])) {
                $additionalCss[$module] = $moduleStyles[$module];
            }
        }
    }
    \DBG::msg('--- added modules css definitions for modules: ---');
    \DBG::dump(array_keys($additionalCss));
    $additionalCss = implode("\r\n\r\n", $additionalCss);
    return $additionalCss;
}

/**
 * Writes the new additional CSS definitions to FS and adds style definition
 * to theme
 * @return boolean True on success, false otherwise
 */
function _writeNewCss($templatePath, $newCss, &$arrUpdate) {

    // Write the CSS first
    $filename = 'modules_' . preg_replace('/\./', '_', $arrUpdate['cmsVersion']) . '.css';
    try {
        $objFile = new \Cx\Lib\FileSystem\File(ASCMS_THEMES_PATH . '/' . $templatePath . '/' . $filename);
        $objFile->write($newCss);
    } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
        \DBG::msg($e->getMessage());
        return false;
    }

    // Generate include tag
    $cssInclusion = '<link rel="stylesheet" type="text/css" href="themes/' . $templatePath . '/' . $filename . '" />'."\r\n";

    // Read index.html
    try {
        $objFile = new \Cx\Lib\FileSystem\File(ASCMS_THEMES_PATH . '/' . $templatePath . '/index.html');
        $indexHtml = $objFile->getData();
    } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
        \DBG::msg($e->getMessage());
        return false;
    }

    // Search write position. CSS inclusion tag is added before
    // style placeholder. If the placeholder is not present in
    // index.html the CSS inclusion tag is inserted before </head>
    // Search for style placeholder ({STYLE_FILE})
    if (($pos = strpos($indexHtml, '{STYLE_FILE}')) === false) {
        $matches = array();

        // Search for head end tag
        if (!preg_match('#</head>#', $indexHtml, $matches, PREG_OFFSET_CAPTURE)) {
            \DBG::msg('No style tag or </head> found, skip template');
            return true;
        }
        $pos = $matches[0][1];
    }

    // Finally add the include statement before $pos and write out
    $indexHtml = substr_replace($indexHtml, $cssInclusion, $pos, 0);
    try {
        $objFile = new \Cx\Lib\FileSystem\File(ASCMS_THEMES_PATH . '/' . $templatePath . '/index.html');
        $objFile->write($indexHtml);
    } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
        \DBG::msg($e->getMessage());
        return false;
    }

    // Copy files for frontend login page to theme's directory
    $imagesToCopy = array('facebook_login.png', 'google_login.png', 'twitter_login.png');
    try {
        foreach ($imagesToCopy as $imageToCopy) {
            $src = str_replace('\\', '/', UPDATE_PATH.'/updates/' . $arrUpdate['cmsVersion'] . '/data/images/' . $imageToCopy);
            $dst = str_replace('\\', '/', ASCMS_THEMES_PATH . '/' . $templatePath . '/images/' . $imageToCopy);
            if (file_exists($src)) {
                $File = new \Cx\Lib\FileSystem\File($src);
                $File->copy($dst);
            }
        }
    } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
        \DBG::msg($e->getMessage());
        return false;
    }

    return true;
}

/**
 * This should only be executed when updating from version 2.2.6 or older
 * Fix for the following tickets:
 * http://bugs.contrexx.com/contrexx/ticket/1412
 * http://bugs.contrexx.com/contrexx/ticket/1043
 * @see http://helpdesk.comvation.com/131276-Die-Navigation-meiner-Seite-wird-nicht-mehr-korrekt-angezeigt
 *
 * Adds placeholder {LEVELS_FULL} to all non-empty subnavbars
 * Adds placeholder {LEVELS_BRANCH} to all navbars having a block named 'navigation' but none 'level_1'
 */
function _updateNavigations()
{
    global $objDatabase, $_CORELANG;

    $navbars = array('navbar', 'navbar2', 'navbar3');
    $subnavbars = array('subnavbar', 'subnavbar2', 'subnavbar3');

    // Find all themes
    $result = $objDatabase->Execute('SELECT `themesname`, `foldername` FROM `' . DBPREFIX . 'skins`');
    if ($result->EOF) {
        \DBG::msg('No themes, really?');
        return false;
    }

    // Update navigations for all themes
    $errorMessages = '';
    while (!$result->EOF) {
        if (!is_dir(ASCMS_THEMES_PATH . '/' . $result->fields['foldername'])) {
            \DBG::msg('Skipping theme "' . $result->fields['themesname'] . '"; No such folder!');
            $errorMessages .= '<div class="message-warning">' . sprintf($_CORELANG['TXT_CSS_UPDATE_MISSING_FOLDER'], $result->fields['themesname']) . '</div>';
            $result->moveNext();
            continue;
        }

        \DBG::msg('Updating navigations for theme "' . $result->fields['themesname'] . '" (' . $type . ')');

        // add {LEVELS_FULL} to all non-empty subnavbars
        foreach ($subnavbars as $subnavbar) {
            try {
                $objFile = new \Cx\Lib\FileSystem\File(ASCMS_THEMES_PATH . '/' . $result->fields['foldername'] . '/' . $subnavbar . '.html');
                $content = $objFile->getData();
            } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                \DBG::msg($e->getMessage());
                continue;
            }
            if (trim($content) == '') {
                continue;
            }
            $content = '{LEVELS_FULL}' . "\r\n" . $content;
            try {
                $objFile->write($content);
            } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                \DBG::msg($e->getMessage());
                continue;
            }
            \DBG::msg('Updated file ' . $subnavbar . '.html for theme '  . $result->fields['themesname']);
        }

        // add {LEVELS_BRANCH} to all navbars matching the following criterias:
        // 1. blockExists('navigation')
        // 2. !blockExists('level_1')
        foreach ($navbars as $navbar) {
            try {
                $objFile = new \Cx\Lib\FileSystem\File(ASCMS_THEMES_PATH . '/' . $result->fields['foldername'] . '/' . $navbar . '.html');
                $content = $objFile->getData();
            } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                \DBG::msg($e->getMessage());
                continue;
            }
            if (trim($content) == '') {
                continue;
            }
            $template = new \Cx\Core\Html\Sigma('.');
            $template->setTemplate($content);
            if (!$template->blockExists('navigation')) {
                continue;
            }
            if ($template->blockExists('level_1')) {
                continue;
            }
            $content = '{LEVELS_BRANCH}' . "\r\n" . $content;
            try {
                $objFile->write($content);
            } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                \DBG::msg($e->getMessage());
                continue;
            }
            \DBG::msg('Updated file ' . $navbar . '.html for theme '  . $result->fields['themesname']);
        }

        $result->moveNext();
    }
    if (!empty($errorMessages)) {
        setUpdateMsg($errorMessages, 'msg');
        setUpdateMsg('<input type="submit" value="'.$_CORELANG['TXT_CONTINUE_UPDATE'].'" name="updateNext" /><input type="hidden" name="processUpdate" id="processUpdate" />', 'button');
        $_SESSION['contrexx_update']['update']['done'][] = 'navigations';
        return false;
    }
    return true;
}

function loadMd5SumOfOriginalCxFiles()
{
    global $_CORELANG, $_CONFIG, $arrMd5SumsOfCxFiles, $objUpdate;

    if (!isset($_SESSION['contrexx_update']['skipIntegrityCheck'])) {
        $_SESSION['contrexx_update']['skipIntegrityCheck'] = false;
    }

    if ($_CONFIG['coreCmsVersion'] == '3.0.0') {
        try {
            $resultRc1 = \Cx\Lib\UpdateUtil::sql('SELECT `target` FROM `'.DBPREFIX.'backend_areas` WHERE `area_id` = 186');
            $resultRc2 = \Cx\Lib\UpdateUtil::sql('SELECT `order_id` FROM `'.DBPREFIX.'backend_areas` WHERE `area_id` = 2');

            if ($resultRc1->fields['target'] != '_blank') {
                $filename = $_CONFIG['coreCmsVersion'].'_RC1.md5';
            } elseif ($resultRc2->fields['order_id'] != 6) {
                $filename = $_CONFIG['coreCmsVersion'].'_RC2.md5';
            }
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
    } else {
        $filename = $_CONFIG['coreCmsVersion'].'.md5';
    }

    $md5File = UPDATE_PATH.'/md5sums/'.$filename;

    if (!file_exists($md5File)) {
        if (!empty($_POST['skipIntegrityCheck'])) {
            $_SESSION['contrexx_update']['skipIntegrityCheck'] = true;
        }
        if ($_SESSION['contrexx_update']['skipIntegrityCheck']) {
            return true;
        }

        setUpdateMsg('Integritätsprüfung fehlgeschlagen', 'title');
        setUpdateMsg('Die Integritätsprüfung konnte nicht durchgeführt werden, da die installierte Version ('.$_CONFIG['coreCmsVersion'].') unbekannt ist.', 'error');
        setUpdateMsg('Ohne Integritätsprüfung kann das Update System nicht feststellen, ob Ihre Website Erweiterungen zum Standardfunktionsumfang enthält (modifizierte Dateien).', 'msg');
        setUpdateMsg('Es wird empfohlen sich an den <a href="http://www.contrexx.com/support" title="Herstellersupport" target="_blank">Herstellersupport</a> oder Ihrem <i>Contrexx Solution Partner</i> zu wenden, um die Integritätsprüfung von einem Spezialisten vornehmen zu lassen.<br />', 'msg');
        setUpdateMsg('Der Updatevorgang kann ohne Integritätsprüfung fortgesetzt werden, dadurch gehen aber allfällige Erweiterungen zum Standardfunktionsumfang unwiderruflich verloren!', 'msg');
        setUpdateMsg('<strong>Es wird nicht empfohlen den Updatevorgang ohne Integritätsprüfung fortzufahren!</strong><br />', 'msg');
        setUpdateMsg('<input type="checkbox" name="skipIntegrityCheck" id="skipIntegrityCheck" value="1" style="float:left;margin-top:3px;" /><label for="skipIntegrityCheck" style="float:left;width:490px;">Ich bin mir den Konsequenzen bewusst und möchte das Update trotzdem ohne Integritätsprüfung fortfahren.</label><br />', 'msg');

        setUpdateMsg('<input type="submit" value="' . $_CORELANG['TXT_CONTINUE_UPDATE'] . '" name="updateNext" />', 'button');

        return false;
    }

    $arrMd5SumsOfCxFiles = array();
    $list = file($md5File);
    foreach ($list as $entry) {
        list($file, $md5sum, $rawMd5Sum) = explode('|', trim($entry));
        if (!isset($arrMd5SumsOfCxFiles[$file])) {
            $arrMd5SumsOfCxFiles[$file] = array();
        }
        $arrMd5SumsOfCxFiles[$file][] = array(
            'md5_sum'     => $md5sum,
            'raw_md5_sum' => $rawMd5Sum
        );
    }

    return true;
}

function backupModifiedFile($file)
{
    global $_CONFIG;

    $cxFilePath = dirname(substr($file, strlen(ASCMS_DOCUMENT_ROOT)));
    if ($cxFilePath == '/') {
        $cxFilePath = '';
    }

    $customizingPath = ASCMS_DOCUMENT_ROOT.'/customizing'.$cxFilePath;
    \Cx\Lib\FileSystem\FileSystem::make_folder($customizingPath);
    $customizingFile = $customizingPath . '/'. basename($file)."_".$_CONFIG['coreCmsVersion'];

    if (file_exists($customizingFile)) {
        $customizingFile .= '_backup_'.date('d.m.Y');
        $suffix = '';
        $idx = 0;
        while (file_exists($customizingFile.$suffix)) {
            $idx++;
            $suffix = '_'.$idx;
        }

        $customizingFile .= $suffix;
    }

    if (!isset($_SESSION['contrexx_update']['modified_files'])) {
        $_SESSION['contrexx_update']['modified_files'] = array();
    }
    try {
        $objFile = new \Cx\Lib\FileSystem\File($file);
        $objFile->copy($customizingFile);
        $_SESSION['contrexx_update']['modified_files'][] = array(
            'src'   => $cxFilePath . '/' . basename($file),
            'dst'   => substr($customizingFile, strlen(ASCMS_DOCUMENT_ROOT)),
        );
    } catch (\Exception $e) {
        setUpdateMsg('Folgende Datei konnte nicht installiert werden:<br />' . $dstPath);
        setUpdateMsg('Fehler: ' . $e->getMessage());
        setUpdateMsg('<br />Häufigste Ursache dieses Problems ist, dass zur Ausführung dieses Vorgangs die benötigten Schreibrechte nicht vorhanden sind. Prüfen Sie daher, ob die FTP-Konfiguration in der Datei <strong>config/configuration.php</strong> korrekt eingerichtet ist.');
        return false;
    }

    return true;
}


function verifyMd5SumOfFile($file, $newFile, $allowSkip = true)
{
    global $arrMd5SumsOfCxFiles;

    \DBG::msg('Running MD5 comparision for file ' . $file);
    // user wants to skip integrity check
    if ($_SESSION['contrexx_update']['skipIntegrityCheck'] && $allowSkip) {
        \DBG::msg('MD5 comparision skipped');
        return true;
    }

    if (!file_exists($file)) {
        \DBG::msg('MD5 comparision cancelled, file not found (' . $file . ')');
        return true;
    }

    $md5 = md5_file($file);
    $cxFilePath = substr($file, strlen(ASCMS_DOCUMENT_ROOT.'/'));
    \DBG::msg('Cx file path is ' . $cxFilePath);

    // file did not exist in old version,
    // therefore, a check would be non-sense
    if (!isset($arrMd5SumsOfCxFiles[$cxFilePath])) {
        \DBG::msg('MD5 comparision complete, file did not exist in prior versions');
        return true;
    }

    foreach ($arrMd5SumsOfCxFiles[$cxFilePath] as $validMd5Sum) {
        if ($md5 == $validMd5Sum['md5_sum']) {
            \DBG::msg('MD5 comparision complete, MD5 sum matches');
            return true;
        }
    }

    $rawmd5 = md5(preg_replace('/\s/u', '', file_get_contents($file)));
    foreach ($arrMd5SumsOfCxFiles[$cxFilePath] as $validMd5Sum) {
        if ($rawmd5 == $validMd5Sum['raw_md5_sum']) {
            \DBG::msg('MD5 comparision complete, raw MD5 sum matches');
            return true;
        }
    }

    $md5SumOfNewFile = md5_file($newFile);
    if ($md5 == $md5SumOfNewFile) {
        \DBG::msg('MD5 comparision complete, file equals new one');
        return true;
    }

    \DBG::msg('MD5 comparision complete, file has changed');
    return false;
}

function copyCxFilesToRoot($src, $dst)
{
    static $copiedCxFilesIndex = 0;

    $src = str_replace('\\', '/', $src);
    $dst = str_replace('\\', '/', $dst);
    $dir = opendir($src);

    $arrCurrentFolderStructure = array();
    while ($file = readdir($dir)) {
        if (!in_array($file, array('.', '..'))) {
            $arrCurrentFolderStructure[] = $file;
        }
    }
    sort($arrCurrentFolderStructure);

    if (!isset($_SESSION['contrexx_update']['copiedCxFilesTotal'])) {
        $_SESSION['contrexx_update']['copiedCxFilesTotal'] = 0;
    }

    foreach ($arrCurrentFolderStructure as $file) {
        if (!checkMemoryLimit() || !checkTimeoutLimit()) {
            $_SESSION['contrexx_update']['copiedCxFilesIndex'] = $copiedCxFilesIndex;
            return 'timeout';
        }

        $srcPath = $src . '/' . $file;
        $dstPath = $dst . '/' . $file;

        if (is_dir($srcPath)) {
            \Cx\Lib\FileSystem\FileSystem::make_folder($dstPath);
            $status = copyCxFilesToRoot($srcPath, $dstPath);
            if ($status !== true) {
                return $status;
            }
        } else {
            $copiedCxFilesIndex++;

            if (isset($_SESSION['contrexx_update']['copiedCxFilesIndex']) && $copiedCxFilesIndex <= $_SESSION['contrexx_update']['copiedCxFilesIndex']) {
                continue;
            }

            $_SESSION['contrexx_update']['copiedCxFilesTotal'] = $_SESSION['contrexx_update']['copiedCxFilesTotal'] + 1;

            try {

                // rename the file if its exists on customizing
                if (!renameCustomizingFile($dstPath)) {
                    return false;
                }

                if (!verifyMd5SumOfFile($dstPath, $srcPath)) {
                    if (!backupModifiedFile($dstPath)) {
                        return false;
                    }
                }

                $objFile = new \Cx\Lib\FileSystem\File($srcPath);
                $objFile->copy($dstPath, true);
            } catch (\Exception $e) {
                $copiedCxFilesIndex--;
                $_SESSION['contrexx_update']['copiedCxFilesIndex'] = $copiedCxFilesIndex;
                $_SESSION['contrexx_update']['copiedCxFilesTotal'] = $_SESSION['contrexx_update']['copiedCxFilesTotal'] - 1;
                setUpdateMsg('Folgende Datei konnte nicht installiert werden:<br />' . $dstPath);
                setUpdateMsg('Fehler: ' . $e->getMessage());
                setUpdateMsg('<br />Häufigste Ursache dieses Problems ist, dass zur Ausführung dieses Vorgangs die benötigten Schreibrechte nicht vorhanden sind. Prüfen Sie daher, ob die FTP-Konfiguration in der Datei <strong>config/configuration.php</strong> korrekt eingerichtet ist.');
                return false;
            }
        }
    }

    closedir($dir);
    return true;
}

function renameCustomizingFile($file)
{
    global $_CONFIG;

    $cxFilePath = dirname(substr($file, strlen(ASCMS_DOCUMENT_ROOT)));
    if ($cxFilePath == '/') {
        $cxFilePath = '';
    }

    $customizingPath = ASCMS_DOCUMENT_ROOT.'/customizing'.$cxFilePath;

    $customizingFile = $customizingPath . '/'. basename($file);

    if (file_exists($customizingFile)) {
        $customizingFile .= "_".$_CONFIG['coreCmsVersion'];

        $suffix = '';
        $idx = 0;
        while (file_exists($customizingFile.$suffix)) {
            $idx++;
            $suffix = '_'.$idx;
        }

        $customizingFile .= $suffix;
    } else {
        return true;
    }

    try {
        $objFile = new \Cx\Lib\FileSystem\File($file);
        $objFile->move($customizingFile);
    } catch (\Exception $e) {
        setUpdateMsg('Error on renaming customizing file:<br />' . $file);
        setUpdateMsg('Error: ' . $e->getMessage());
        setUpdateMsg('<br />Häufigste Ursache dieses Problems ist, dass zur Ausführung dieses Vorgangs die benötigten Schreibrechte nicht vorhanden sind. Prüfen Sie daher, ob die FTP-Konfiguration in der Datei <strong>config/configuration.php</strong> korrekt eingerichtet ist.');
        return false;
    }

    return true;
}

function createHtAccess()
{
    if (empty($_SESSION['contrexx_update']['htaccess_file_created'])) {
        $webServerSoftware = !empty($_SERVER['SERVER_SOFTWARE']) && stristr($_SERVER['SERVER_SOFTWARE'], 'apache') ? 'apache' : (stristr($_SERVER['SERVER_SOFTWARE'], 'iis') ? 'iis' : '');
        $cl = Env::get('ClassLoader');

        if ($webServerSoftware == 'iis') {
            $cl->loadFile(UPDATE_LIB . '/PEAR/File/HtAccess.php');
            $objHtAccess = new File_HtAccess(ASCMS_DOCUMENT_ROOT . '/web.config');
            $objHtAccess->setAdditional(explode("\n", @file_get_contents(dirname(__FILE__) . '/data/iis_htaccess.tpl')));
            $result = $objHtAccess->save();
            if ($result !== true) {
                return false;
            }
        } else {
            $cl->loadFile(UPDATE_LIB . '/FRAMEWORK/FWHtAccess.class.php');
            $objFWHtAccess = new FWHtAccess(ASCMS_DOCUMENT_ROOT, ASCMS_PATH_OFFSET);
            $result = $objFWHtAccess->loadHtAccessFile('/.htaccess');
            if ($result !== true) {
                return false;
            }
            $htAccessTemplate = getHtAccessTemplate();
            $pathOffset = ASCMS_PATH_OFFSET;
            if (empty($pathOffset)) $pathOffset = '/';
            $htAccessTemplate = str_replace('%PATH_ROOT_OFFSET%', $pathOffset, $htAccessTemplate);
            $objFWHtAccess->setSection('core_routing', explode("\n", $htAccessTemplate));
            $objFWHtAccess->removeSection('core_modules__alias');
            $objFWHtAccess->removeSection('core__language');
            $result = $objFWHtAccess->write();
            if ($result !== true) {
                return false;
            }
        }

        $_SESSION['contrexx_update']['htaccess_file_created'] = true;
    }

    return true;
}

function getHtAccessTemplate()
{
    $htAccessTemplate = @file_get_contents(dirname(__FILE__) . '/data/apache_htaccess.tpl');
    $htAccessPath     = ASCMS_DOCUMENT_ROOT . '/.htaccess';

    if (file_exists($htAccessPath)) {
        $htAccess = @file_get_contents($htAccessPath);
        if (preg_match('/^(\s*)#?RewriteRule\s+\^\(\\\\w\\\\w\\\\\/\)\?\([^\)]*\)\\\\\\/\$\s+\$2\s+\[[^\]]+\]$/m', $htAccess, $matches)) {
            $search  = '#RewriteRule  ^(\w\w\/)?(_meta|admin|cache|cadmin|changelog|config|core|core_modules|customizing|feed|images|installer|lang|lib|media|model|modules|testing|themes|tmp|update|webcam|favicon.ico)\/$ $2 [L,QSA]';
            $replace = str_replace($matches[1], '', $matches[0]);
            $htAccessTemplate = str_replace($search, $replace, $htAccessTemplate);
        }
        if (preg_match('/^(\s*)RewriteRule\s+\^\(\\\\w\\\\w\\\\\/\)\?\([^\)]*\)\(\\\\\/\|\$\)\(\.\*\)\s+\$2\$3\$4\s+\[[^\]]+\]$/m', $htAccess, $matches)) {
            $search  = 'RewriteRule  ^(\w\w\/)?(_meta|admin|cache|cadmin|changelog|config|core|core_modules|customizing|feed|images|installer|lang|lib|media|model|modules|testing|themes|tmp|update|webcam|favicon.ico)(\/|$)(.*) $2$3$4 [L,QSA]';
            $replace = str_replace($matches[1], '', $matches[0]);
            $htAccessTemplate = str_replace($search, $replace, $htAccessTemplate);
        }
    }

    return $htAccessTemplate;
}

function createOrAlterSessionVariableTable()
{
    \Cx\Lib\UpdateUtil::table(
        DBPREFIX.'session_variable',
        array(
            'id'        => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' =>true),
            'parent_id' => array('type' => 'INT(11)', 'notnull' => true, 'after' => 'id'),
            'sessionid' => array('type' => 'VARCHAR(32)', 'notnull' => true, 'default' => '', 'after' => 'parent_id'),
            'lastused'  => array('type' => 'TIMESTAMP', 'notnull' => true, 'default_expr' => 'CURRENT_TIMESTAMP', 'on_update' => 'CURRENT_TIMESTAMP', 'after' => 'sessionid'),
            'key'       => array('type' => 'VARCHAR(100)', 'notnull' => true, 'default' => '', 'after' => 'lastused'),
            'value'     => array('type' => 'TEXT', 'notnull' => false, 'default' => '', 'after' => 'key')
        ),
        array(
            'key_index' => array('fields' => array('parent_id', 'key', 'sessionid'), 'type' => 'UNIQUE')
        )
    );
}

function migrateSessionTable()
{
    global $sessionObj;

    try {
        createOrAlterSessionVariableTable();
        \Cx\Lib\UpdateUtil::sql('TRUNCATE TABLE `'. DBPREFIX .'session_variable`');

        $objResult = \Cx\Lib\UpdateUtil::sql('SELECT
                                                `sessionid`,
                                                `datavalue`
                                              FROM
                                                 `' . DBPREFIX . 'sessions`');
        if ($objResult) {
            while (!$objResult->EOF) {
                $sessionId = $objResult->fields['sessionid'];

                if ($sessionId == $sessionObj->sessionid) {
                    $sessionArray = $_SESSION; // migrate the current state into database.
                } else {
                    $sessionArray = unserializesession($objResult->fields['datavalue']);
                }

                insertSessionArray($sessionId, $sessionArray);
                $objResult->MoveNext();
            }
        }
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'sessions',
            array(
                'sessionid'      => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'primary' => true),
                'remember_me'    => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'sessionid'),
                'startdate'      => array('type' => 'VARCHAR(14)', 'notnull' => true, 'default' => '', 'after' => 'remember_me'),
                'lastupdated'    => array('type' => 'VARCHAR(14)', 'notnull' => true, 'default' => '', 'after' => 'startdate'),
                'status'         => array('type' => 'VARCHAR(20)', 'notnull' => true, 'default' => '', 'after' => 'lastupdated'),
                'user_id'        => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'status'),
            ),
            array(
                'LastUpdated'    => array('fields' => array('lastupdated')),
            )
        );
    } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }

    return true;
}

function insertSessionArray($sessionId, $sessionArr, $parentId = 0)
{
    global $objDatabase;

    foreach ($sessionArr as $key => $value) {
        \Cx\Lib\UpdateUtil::sql('INSERT INTO
                                    '. DBPREFIX .'session_variable
                                SET
                                `parent_id` = "'. intval($parentId) .'",
                                `sessionid` = "'. $sessionId .'",
                                `key` = "'. contrexx_input2db($key) .'",
                                `value` = "'. (is_array($value) ? '' : contrexx_input2db(serialize($value)))  .'"
                              ON DUPLICATE KEY UPDATE
                                `value` = "'. (is_array($value) ? '' : contrexx_input2db(serialize($value))) .'"');
        $insertId = $objDatabase->Insert_ID();

        if (is_array($value)) {
            insertSessionArray($sessionId, $value, $insertId);
        }
    }
}

function unserializesession( $data )
{
    if(  strlen( $data) == 0)
    {
        return array();
    }

    // match all the session keys and offsets
    preg_match_all('/(^|;|\})([a-zA-Z0-9_]+)\|/i', $data, $matchesarray, PREG_OFFSET_CAPTURE);

    $returnArray = array();

    $lastOffset = null;
    $currentKey = '';
    foreach ( $matchesarray[2] as $value )
    {
        $offset = $value[1];
        if(!is_null( $lastOffset))
        {
            $valueText = substr($data, $lastOffset, $offset - $lastOffset );
            $returnArray[$currentKey] = unserialize($valueText);
        }
        $currentKey = $value[0];

        $lastOffset = $offset + strlen( $currentKey )+1;
    }

    $valueText = substr($data, $lastOffset );
    $returnArray[$currentKey] = unserialize($valueText);

    return $returnArray;
}

function _convertThemes2Component()
{
    global $objDatabase, $_CORELANG;

    // Find all themes
    $result = $objDatabase->Execute('SELECT `themesname`, `foldername` FROM `' . DBPREFIX . 'skins`');
    if ($result->EOF) {
        \DBG::msg('No themes found!');
        return false;
    }

    $errorMessages = '';
    $themeRepository = new \Cx\Core\View\Model\Repository\ThemeRepository();
    while (!$result->EOF) {
        $themePath = ASCMS_THEMES_PATH . '/' . $result->fields['foldername'];
        if (!is_dir($themePath)) {
            \DBG::msg('Skipping theme "' . $result->fields['themesname'] . '"; No such folder!');
            $errorMessages .= '<div class="message-warning">' . sprintf($_CORELANG['TXT_CSS_UPDATE_MISSING_FOLDER'], $result->fields['themesname']) . '</div>';
            $result->MoveNext();
            continue;
        }

        // create a new one if no component.yml exists
        if (!file_exists($themePath . '/component.yml')) {
            \DBG::msg('Converting theme "' . $result->fields['themesname'] . ' to component');
            $themeRepository->convertThemeToComponent($result->fields['foldername']);
        }

        $result->MoveNext();
    }

    if (!empty($errorMessages)) {
        setUpdateMsg($errorMessages, 'msg');
        setUpdateMsg('<input type="submit" value="'.$_CORELANG['TXT_CONTINUE_UPDATE'].'" name="updateNext" /><input type="hidden" name="processUpdate" id="processUpdate" />', 'button');
        $_SESSION['contrexx_update']['update']['done'][] = 'convertTemplates';
        return false;
    }
    return true;
}

class License {

    public function __construct() {

    }

    public function update($getNew = true) {
        global $documentRoot, $_CONFIG, $objUser, $license, $objDatabase;

        if (@include_once(ASCMS_DOCUMENT_ROOT.'/lib/PEAR/HTTP/Request2.php')) {
            $_GET['force'] = 'true';
            $_GET['silent'] = 'true';
            $documentRoot = ASCMS_DOCUMENT_ROOT;

            $_CONFIG['licenseUpdateInterval'] = 0;
            $_CONFIG['licenseSuccessfulUpdate'] = 0;
            $_CONFIG['licenseState'] = '';
            if ($getNew) {
                $_CONFIG['installationId'] = '';
                $_CONFIG['licenseKey'] = '';
            }

            $objUser = \FWUser::getFWUserObject()->objUser;
            $license = \Cx\Core_Modules\License\License::getCached($_CONFIG, $objDatabase);

            $return = @include_once(ASCMS_DOCUMENT_ROOT.'/core_modules/License/versioncheck.php');
        }

        // we force a version number update. if the license update failed
        // version number will not be upgraded yet:
        \Cx\Lib\UpdateUtil::sql('UPDATE `' . DBPREFIX . 'settings` SET `setvalue` = \'' . $_CONFIG['coreCmsVersion'] . '\' WHERE `setid` = 97');
        $settingsManager = new \settingsManager();
        $settingsManager->writeSettingsFile();

        return ($return === true);
    }
}
