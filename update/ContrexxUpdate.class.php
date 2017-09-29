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

class ContrexxUpdate
{
    public $arrStatusMsg = array('title' => '', 'button' => '', 'msg' => array(), 'error' => array());

    private $objTemplate;
    private $objDatabase;
    private $objJson;
    private $isAuth = false;
    private $lang;
    private $ajax = false;
    private $html = array('content' => '', 'logout' => '', 'navigation' => '', 'dialog' => '');
    private $_defaultLanguage = 'de';

    /**
     * Available languages
     *
     * @var array
     */
    private $_arrAvailableLanguages = array(
        'de' => 'Deutsch',
        //'en' => 'English',
    );

    public function __construct()
    {
        global $_CORELANG, $objDatabase;

        @header('content-type: text/html; charset='.(UPDATE_UTF8 ? 'utf-8' : 'iso-8859-1'));
        $this->_loadLanguage();
        $this->objTemplate = new HTML_Template_Sigma(UPDATE_TPL);
        $this->objTemplate->setErrorHandling(PEAR_ERROR_DIE);
        $this->objTemplate->loadTemplateFile('index.html');
        $this->objTemplate->setGlobalVariable(array(
            'TXT_UPDATE_CONTREXX_UPDATE_SYSTEM' => $_CORELANG['TXT_UPDATE_CONTREXX_UPDATE_SYSTEM'],
            'UPDATE_TPL_PATH'                   => UPDATE_TPL,
            'CHARSET'                           => UPDATE_UTF8 ? 'utf-8' : 'iso-8859-1',
            'JAVASCRIPT'                        => 'javascript_inserting_here',
        ));

        $this->objDatabase = Env::get('db');

        DBG::set_adodb_debug_mode();

        if (!empty($_REQUEST['ajax'])) {
            $this->ajax = true;
            if (!@include_once(UPDATE_LIB.'/PEAR/Services/JSON.php')) {
                die('Unable to load the PEAR JSON library: '.UPDATE_LIB.'/PEAR/Services/JSON.php');
            }
            $this->objJson = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
            $this->parseJsonRequest();
        }
    }

    public function getPage()
    {
        if (!empty($_REQUEST['cmd']) && ($_REQUEST['cmd'] == 'logout')) {
            $this->logout();
        }

        if (isset($_GET['debug_update']) && $_GET['debug_update'] === 'true') {
            \DBG::activate(DBG_PHP | DBG_DB | DBG_LOG);
        }

        if (isset($_POST['doGroup']) && $_POST['doGroup']) {
            if (!empty($_POST['pgUsername']) && !empty($_POST['pgPassword'])) {
                if ($userId = $this->auth($_POST['pgUsername'], $_POST['pgPassword'])) {
                    $_SESSION['contrexx_update']['user_id'] = $userId;
                    $_SESSION['contrexx_update']['step'] = 5;
                    $_SESSION['contrexx_update']['version'] = $_POST['pgCmsVersion'];
                    $_SESSION['contrexx_update']['migrate_lang_ids'] = explode(',', $_POST['pgMigrateLangIds']);
                    $_SESSION['contrexx_update']['copyFilesFinished'] = true;
                    $_SESSION['contrexx_update']['content_stats'] = true;
                    $_SESSION['contrexx_update']['inactive_content_languages_checked'] = true;
                    $_SESSION['contrexx_update']['content_migrated'] = true;
                }
            }
        }

        if ($this->auth() || $this->login()) {
            $this->setStep();
            $this->showStep();
        }

        $this->setPlaceholders();

        if ($this->ajax) {
            die($this->objJson->encode(
                array(
                    'content'    => $this->html['content'],
                    'logout'     => $this->html['logout'],
                    'navigation' => $this->html['navigation'],
                    'dialog'     => $this->html['dialog'],
                    'timeout'    => $this->html['timeout'],
                )
            ));
        }
        return $this->objTemplate->get();
    }

    private function parseJsonRequest()
    {
        $_POST = $this->objJson->decode($this->stripslashes($_REQUEST['ajax']));
        if (!UPDATE_UTF8) {
            $_POST = array_map('utf8_decode', $_POST);
        }
    }

    private function setStep()
    {
        if (empty($_SESSION['contrexx_update']['step'])) {
            $_SESSION['contrexx_update']['step'] = 0;
        }

        if (isset($_POST['updateBack']) && !isset($_POST['updateNext']) && !isset($_POST['skipRequirements'])) {
            $this->setPreviousStep();
        }
    }

    private function showStep()
    {
        switch ($_SESSION['contrexx_update']['step']) {
            case 1:
                $this->showRequirements();
                break;
            case 2:
                $this->showLicense();
                break;
            case 3:
                $this->showInfoAboutLicense();
                break;
            case 4:
                $this->showUpdate();
                break;
            case 5:
                $this->processUpdate();
                break;
            default:
                $this->getOverview();
                break;
        }
    }

    private function setNextStep()
    {
        $_SESSION['contrexx_update']['step'] = $_SESSION['contrexx_update']['step'] + 1;
    }

    private function setPreviousStep()
    {
        $_SESSION['contrexx_update']['step'] = $_SESSION['contrexx_update']['step'] - 1;
    }

    private function setPlaceholders()
    {
        global $_CORELANG;

        $logout = $this->auth() ? '<input name="logout" value="'.$_CORELANG['TXT_UPDATE_LOGOUT'].'" type="button" onclick="window.location.href=\'index.php?cmd=logout\'" />' : '';

        if ($this->ajax) {
            $this->html['content'] = !UPDATE_UTF8 ? utf8_encode($this->html['content']) : $this->html['content'];
            $this->html['logout']  = $logout;
            $this->html['dialog']  = !empty($this->html['dialog'])  ? $this->html['dialog']  : '';
            $this->html['timeout'] = !empty($this->html['timeout']) ? $this->html['timeout'] : '';
        } else {
            $this->objTemplate->setVariable('LOGOUT_BUTTON', $logout);
        }
    }

    private function setNavigation($navigation)
    {
        if ($this->ajax) {
            $this->html['navigation'] = $navigation;
        } else {
            $this->objTemplate->setVariable('NAVIGATION', $navigation);
        }
    }

    private function getLangMenu()
    {
        $menu = '<select class="lang" name="lang" onchange="window.location.href=\'?lang=\'+this.value">';
        foreach ($this->_arrAvailableLanguages as $lang => $desc) {
            $menu .= '<option value="'.$lang.'"'.($lang == $this->lang ? ' selected="selected"' : '').'>'.$desc.'</option>';
        }
        $menu .= '</select>';
        return $menu;
    }

    private function getOverview()
    {
        $arrVersions = $this->getAvailabeVersions();
        $_SESSION['contrexx_update']['countAvailableVersions'] = count($arrVersions);

        if (count($arrVersions) === 1) {
            $updateVersion = key($arrVersions);
            $_POST['updateVersion'] = $updateVersion;
        }

        if (!empty($_POST['updateVersion'])) {
            if (in_array($this->stripslashes($_POST['updateVersion']), array_keys($arrVersions))) {
                $_SESSION['contrexx_update']['version'] = $this->stripslashes($_POST['updateVersion']);
                $this->setNextStep();
                $this->showStep();
            } else {
                $this->getOverviewPage($arrVersions);
            }
        } else {
            $this->getOverviewPage($arrVersions);
        }
    }

    private function getOverviewPage($arrVersions)
    {
        global $_CORELANG;

        $this->objTemplate->addBlockfile('CONTENT', 'overview', 'overview.html');
        $this->objTemplate->setVariable('TXT_UPDATE_VERSION_SELECTION', $_CORELANG['TXT_UPDATE_VERSION_SELECTION']);

        if (count($arrVersions) !== 0) {
            $this->objTemplate->setVariable(array(
                'TXT_UPDATE_SELECT_VERSION_MSG' => $_CORELANG['TXT_UPDATE_SELECT_VERSION_MSG'],
                'TXT_UPDATE_AVAILABLE_VERSIONS' => $_CORELANG['TXT_UPDATE_AVAILABLE_VERSIONS'],
            ));
            foreach ($arrVersions as $versionPath => $arrVersion) {
                $this->objTemplate->setVariable(array(
                    'UPDATE_VERSION'         => $this->getLiteralRepresentationOfVersion($arrVersion['cmsVersion']),
                    'UPDATE_VERSION_PATH'    => $versionPath,
                    'UPDATE_VERSION_NAME'    => $arrVersion['cmsName'],
                    'UPDATE_VERSION_EDITION' => $arrVersion['cmsEdition'],
                    'UPDATE_VERSION_CHECKED' => !empty($_SESSION['contrexx_update']['version']) && $_SESSION['contrexx_update']['version'] == $versionPath ? 'checked="checked"' : '',
                ));
                $this->objTemplate->parse('updateVersionList');
            }

            if (isset($_POST['updateVersions'])) {
                $this->objTemplate->setVariable('UPDATE_ERROR_MSG', $_CORELANG['TXT_UPDATE_MUST_SELECT_UPDATE']);
                $this->objTemplate->parse('updateNoVersionSelected');
            } else {
                $this->objTemplate->hideBlock('updateNoVersionSelected');
            }

            $this->objTemplate->parse('updateVersions');
            $this->objTemplate->hideBlock('updateNoVersions');
        } else {
            $this->objTemplate->setVariable('TXT_UPDATE_NO_VERSION_TO_UPGRADE_TO', $_CORELANG['TXT_UPDATE_NO_VERSION_TO_UPGRADE_TO']);
            $this->objTemplate->parse('updateNoVersions');
            $this->objTemplate->hideBlock('updateVersions');
        }

        $this->objTemplate->parse('overview');
        if ($this->ajax) {
            $this->html['content'] = $this->objTemplate->get('overview');
        }
        $this->setNavigation('<input type="submit" value="'.$_CORELANG['TXT_UPDATE_NEXT'].'" name="updateNext" />');
    }

    private function showRequirements()
    {
        global $_CONFIG, $objUpdate;

        $arrUpdate = $this->getLoadedVersionInfo();
        if ($arrUpdate) {
            $arrRequirements = $this->getRequirements($arrUpdate);

            if (isset($_POST['skipRequirements']) && !$arrRequirements['incompatible']) {
                $this->setNextStep();
                $this->showStep();
            } else if (!$this->_isNewerVersion($_CONFIG['coreCmsVersion'], $_SESSION['contrexx_update']['version'])) {
                $_SESSION['contrexx_update']['step'] = 5;
                $this->showStep();
            } else {
                $this->showRequirementsPage($arrRequirements);
            }
        } else {
            $this->setPreviousStep();
            $this->showStep();
        }
    }

    private function showRequirementsPage($arrRequirements)
    {
        global $_CORELANG;

        $this->objTemplate->addBlockfile('CONTENT', 'requirements', 'requirements.html');
        $this->objTemplate->setVariable(array(
            'TXT_UPDATE_SYSTEM_REQUIREMENTS' => $_CORELANG['TXT_UPDATE_SYSTEM_REQUIREMENTS'],
            'TXT_UPDATE_VERSIONS'            => $_CORELANG['TXT_UPDATE_VERSIONS'],
            'TXT_UPDATE_PHP_EXTENSIONS'      => $_CORELANG['TXT_UPDATE_PHP_EXTENSIONS'],
            'TXT_UPDATE_PHP_CONFIGURATIONS'  => $_CORELANG['TXT_UPDATE_PHP_CONFIGURATIONS'],
        ));

        if (!empty($arrRequirements['versions'])) {
            $serverNotices = '';
            foreach ($arrRequirements['versions'] as $arrVersion) {
                $this->objTemplate->setVariable(array(
                    'UPDATE_VERSION_CLASS' => $arrVersion['class'],
                    'UPDATE_VERSION_NAME'  => $arrVersion['name'],
                    'UPDATE_VERSION_VALUE' => $arrVersion['value'],
                ));
                $this->objTemplate->parse('version');
            }
        } else {
            $this->objTemplate->hideBlock('versions');
        }

        if (isset($arrRequirements['phpExtensions']) && count($arrRequirements['phpExtensions'])) {
            $phpExtensions = '';
            foreach ($arrRequirements['phpExtensions'] as $arrPhpExtension) {
                $this->objTemplate->setVariable(array(
                    'UPDATE_PHP_EXTENSION_CLASS' => $arrPhpExtension['class'],
                    'UPDATE_PHP_EXTENSION_NAME'  => $arrPhpExtension['name'],
                    'UPDATE_PHP_EXTENSION_VALUE' => $arrPhpExtension['value'],
                ));
                $this->objTemplate->parse('phpExtension');
            }
        } else {
            $this->objTemplate->hideBlock('phpExtension');
        }

        if (!empty($arrRequirements['phpConfigurations'])) {
            $serverRequirements = '';
            foreach ($arrRequirements['phpConfigurations'] as $arrPhpConfiguration) {
                $this->objTemplate->setVariable(array(
                    'UPDATE_PHP_CONFIGURATION_CLASS' => $arrPhpConfiguration['class'],
                    'UPDATE_PHP_CONFIGURATION_NAME'  => $arrPhpConfiguration['name'],
                    'UPDATE_PHP_CONFIGURATION_VALUE' => $arrPhpConfiguration['value'],
                ));
                $this->objTemplate->parse('phpConfiguration');
            }
        } else {
            $this->objTemplate->hideBlock('phpConfigurations');
        }

        $this->objTemplate->parse('requirements');
        if ($this->ajax) {
            $this->html['content'] = $this->objTemplate->get('requirements');
        }

        $updateBack = '';
        if (isset($_SESSION['contrexx_update']['countAvailableVersions']) && $_SESSION['contrexx_update']['countAvailableVersions'] > 1) {
            $updateBack = '<input type="submit" value="'.$_CORELANG['TXT_UPDATE_BACK'].'" name="updateBack" onclick="try{doUpdate(true)} catch(e){return true;}" /> ';
        }

        $this->setNavigation($updateBack . '<input type="submit" value="'.$_CORELANG['TXT_UPDATE_NEXT'].'" name="skipRequirements" />');
    }

    private function showLicense()
    {
        $arrUpdate = $this->getLoadedVersionInfo();
        if ($arrUpdate) {

            if (isset($_POST['updateNext'])) {
                if (empty($_POST['update_license'])) {
                    $_SESSION['contrexx_update']['license_agreement'] = false;
                } else {
                    $_SESSION['contrexx_update']['license_agreement'] = true;
                }
            }

            if (isset($_POST['updateNext']) && (!empty($_POST['update_license']))) {
                $_SESSION['contrexx_update']['license_agreement'] = true;

                $this->setNextStep();
                $this->showStep();
            } else {
                $this->showLicensePage($arrUpdate);
            }
        } else {
            $this->setPreviousStep();
            $this->showStep();
        }
    }

    private function showLicensePage($arrUpdate)
    {
        global $_CORELANG;

        $this->objTemplate->addBlockfile('CONTENT', 'license', 'license.html');

        $licenseFile = UPDATE_UPDATES.'/'.$arrUpdate['cmsVersion'].'/data/contrexx_lizenz_de.txt';
        $license = @file_get_contents($licenseFile);
        $licenseTxt = nl2br(preg_replace('/^([0-9]\.[0-9]?\s[^\n]+)\n$/im', '<strong>\1</strong>', $license));

        $this->objTemplate->setVariable(array(
            'TXT_UPDATE_LICENSE_CONDITIONS' => $_CORELANG['TXT_UPDATE_LICENSE_CONDITIONS'],
            'TXT_UPDATE_ACCEPT_LICENSE'     => $_CORELANG['TXT_UPDATE_ACCEPT_LICENSE'],
            'UPDATE_LICENSE_LICENSE_TXT'    => $licenseTxt,
            'UPDATE_LICENSE_CHECKED'        => !empty($_SESSION['contrexx_update']['license_agreement']) ? 'checked="checked"' : '',
        ));


        $this->objTemplate->parse('license');
        if ($this->ajax) {
            $this->html['content'] = $this->objTemplate->get('license');
        }

        $this->setNavigation('<input type="submit" value="'.$_CORELANG['TXT_UPDATE_BACK'].'" name="updateBack" onclick="try{doUpdate(true)} catch(e){return true;}" /> <input type="submit" value="'.$_CORELANG['TXT_UPDATE_NEXT'].'" name="updateNext" />');
    }

    private function showInfoAboutLicense()
    {
        global $_CONFIG;
        if (isset($_POST['updateNext'])) {
            if (empty($_POST['update_license_info'])) {
                $_SESSION['contrexx_update']['license_info'] = false;
            } else {
                $_SESSION['contrexx_update']['license_info'] = true;
            }
        }

        if ((isset($_POST['updateNext']) && (!empty($_POST['update_license_info']))) || !$this->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.0.0')) {
            $_SESSION['contrexx_update']['license_info'] = true;

            $this->setNextStep();
            $this->showStep();
        } else {
            $this->showInfoAboutLicensePage();
        }
    }

    private function showInfoAboutLicensePage()
    {
        global $_CORELANG, $_CONFIG;

        $this->objTemplate->addBlockfile('CONTENT', 'license_info', 'license_info.html');

        $this->objTemplate->setVariable(array(
            'TXT_UPDATE_LICENSE_MODEL'      => $_CORELANG['TXT_UPDATE_LICENSE_MODEL'],
            'UPDATE_LICENSE_INFO_CHECKED'   => !empty($_SESSION['contrexx_update']['license_info']) ? 'checked="checked"' : '',
            'TXT_UPDATE_ACCEPT_LICENSE_CHANGE'  => $_CORELANG['TXT_UPDATE_ACCEPT_LICENSE_CHANGE'],
            'UPDATE_EDITION'                    => $_CONFIG['coreCmsEdition'],
            'UPDATE_VERSION'                    => $this->getLiteralRepresentationOfVersion($_CONFIG['coreCmsVersion']),
        ));

        if ($_CONFIG['coreCmsEdition'] == 'OpenSource') {
            $this->objTemplate->touchBlock('update_license_info_free');
        } else {
            $this->objTemplate->hideBlock('update_license_info_free');
        }

        $this->objTemplate->parse('license_info');
        if ($this->ajax) {
            $this->html['content'] = $this->objTemplate->get('license_info');
        }

        $this->setNavigation('<input type="submit" value="'.$_CORELANG['TXT_UPDATE_BACK'].'" name="updateBack" onclick="try{doUpdate(true)} catch(e){return true;}" /> <input type="submit" value="'.$_CORELANG['TXT_UPDATE_NEXT'].'" name="updateNext" />');
    }

    private function showUpdate()
    {
        if (isset($_POST['updateNext']) && isset($_POST['processUpdate'])) {
            $this->setNextStep();
            $this->showStep();
        } else {
            if (!empty($_POST['execution_time'])) {
                $this->processUpdate();
            } else {
                $this->showUpdatePage();
            }
        }
    }

    private function showUpdatePage()
    {
        global $_CORELANG;

        $this->objTemplate->addBlockfile('CONTENT', 'start', 'start.html');

        $arrVersion    = $this->getLoadedVersionInfo();
        $updateVersion = $version = $this->getLiteralRepresentationOfVersion($arrVersion['cmsVersion']);

        $this->objTemplate->setVariable(array(
            'TXT_UPDATE_UPDATE_IS_READY' => $_CORELANG['TXT_UPDATE_UPDATE_IS_READY'],
            'UPDATE_VERSION'             => $updateVersion,
        ));
        $this->objTemplate->parse('start');

        if ($this->ajax) {
            $this->html['content'] = $this->objTemplate->get('start');
        }

        $this->setNavigation('<input type="submit" value="'.$_CORELANG['TXT_UPDATE_BACK'].'" name="updateBack" onclick="try{doUpdate(true)} catch(e){return true;}" /> <input type="submit" value="'.$_CORELANG['TXT_UPDATE_START_UPDATE'].'" name="updateNext" /><input type="hidden" name="processUpdate" id="processUpdate" /><input type="hidden" id="checkTimeout" />');
    }

    private function processUpdate()
    {
        global $_CORELANG, $_CONFIG, $_ARRAYLANG;

        $this->objTemplate->addBlockfile('CONTENT', 'process', 'process.html');

        if (($return = $this->_loadUpdateLanguage()) !== true) {
            $this->objTemplate->setVariable('UPDATE_ERROR_MSG', $return);
            $this->objTemplate->parse('updateProcessError');
        } elseif (($arrVersions = $this->getAvailabeVersions()) === false || !@include_once(UPDATE_UPDATES . '/' . $_SESSION['contrexx_update']['version'] . '/' . $arrVersions[$_SESSION['contrexx_update']['version']]['script'])) {
            $this->objTemplate->setVariable('UPDATE_ERROR_MSG', $_CORELANG['TXT_UPDATE_UNABLE_TO_START']);
            $this->objTemplate->parse('updateProcessError');
        } else {
            if (!$this->_isNewerVersion($_CONFIG['coreCmsVersion'], $_SESSION['contrexx_update']['version'])) {
                $result = true;
            } else {
                if (!empty($_POST['execution_time'])) {
                    $_SESSION['contrexx_update']['max_execution_time'] = intval($_POST['execution_time']);
                    if (intval($_POST['execution_time']) < 20) {
                        setUpdateMsg('<div class="message-warning">' . $_CORELANG['TXT_UPDATE_EXECUTION_TIME'] . '</div>', 'msg');
                        setUpdateMsg('<input type="submit" value="'.$_CORELANG['TXT_CONTINUE_UPDATE'].'" name="updateNext" /><input type="hidden" name="processUpdate" id="processUpdate" />', 'button');
                        $result = false;
                    } else {
                        die('{"time": ' . intval($_POST['execution_time']) . '}');
                    }
                } else {
                    try {
                        if (!activateDebugging()) {
                            throw new \Exception("The debugging file couldn't be created.");
                        }
                        $result = true;
                    } catch (\Exception $e) {
                        setUpdateMsg($_CORELANG['TXT_UPDATE_DBG_FILE'], 'msg');
                        setUpdateMsg('<input type="submit" value="'.$_CORELANG['TXT_CONTINUE_UPDATE'].'" name="updateNext" /><input type="hidden" name="skipDebugMsg" id="skipDebugMsg" value="skipDebugMsg" />', 'button');
                        if (empty($_POST['skipDebugMsg'])) {
                            $result = false;
                        }
                    }
                    if ($result !== false) {
                        DBG::msg('-------------------------------------------------------------');
                        DBG::msg('CLOUDREXX UPDATE - NEW REQUEST');
                        DBG::msg('Date: ' . date('d.m.Y H:i:s'));
                        DBG::msg('Get-Params:');
                        DBG::dump($_GET);
                        DBG::msg('User: ' . $_SESSION['contrexx_update']['username']);
                        DBG::msg('-------------------------------------------------------------');
                        $result = executeContrexxUpdate();
                    }
                }
            }
            if ($result !== true) {
                if (!empty($this->arrStatusMsg['error'])) {
                    $this->objTemplate->setVariable('UPDATE_ERROR_MSG', implode('<br />', $this->arrStatusMsg['error']));
                    $this->objTemplate->parse('updateProcessError');
                }
                if (empty($this->arrStatusMsg['title'])) {
                    $this->arrStatusMsg['title'] = 'Update Fehler';
                }
                if (empty($this->arrStatusMsg['button'])) {
                    $this->arrStatusMsg['button'] = '<input type="submit" value="'.$_CORELANG['TXT_UPDATE_TRY_AGAIN'].'" name="updateNext" /><input type="hidden" name="processUpdate" id="processUpdate" />';
                }
                if (!empty($this->arrStatusMsg['dialog']) && empty($this->arrStatusMsg['error'])) {
                    $this->objTemplate->hideBlock('processStatus');
                    $dialogContent = implode('<br />', $this->arrStatusMsg['msg']);
                    if (!$this->ajax) {
                        $dialogContent = str_replace(array(
                            '\'',
                            "\r\n",
                        ), array(
                            '\\\'',
                            '',
                        ), $dialogContent);
                    }
                    $this->objTemplate->setVariable('PROCESS_DIALOG_CONTENT', $dialogContent);
                    if ($this->ajax) {
                        $this->html['dialog'] = $this->arrStatusMsg['dialog'];
                        $this->objTemplate->parse('ajaxDialogContent');
                    } else {
                        $this->objTemplate->parse('dialogContent');
                    }
                } else if (!empty($this->arrStatusMsg['timeout']) && empty($this->arrStatusMsg['error'])) {
                    $this->html['timeout'] = $this->arrStatusMsg['timeout'];
                } else {
                    $this->objTemplate->hideBlock('dialogContent');
                    $this->objTemplate->hideBlock('ajaxDialogContent');
                    $this->objTemplate->setVariable(array(
                        'UPDATE_PROCESS_TITLE' => $_CORELANG['TXT_UPDATE_UPDATE_PROCESS'],
                        'UPDATE_STATUS_TITLE'  => $this->arrStatusMsg['title'],
                        'UPDATE_STATUS'        => str_replace('[[SQL_INFO_TITLE]]', $this->arrStatusMsg['title'], implode('<br />', $this->arrStatusMsg['msg'])),
                    ));
                    $this->setNavigation($this->arrStatusMsg['button']);
                }
            } else {
                $this->objTemplate->hideBlock('dialogContent');
                $this->objTemplate->hideBlock('ajaxDialogContent');
                $this->objTemplate->hideBlock('processStatus');
                $urlFrontend = (ASCMS_PATH_OFFSET !== '')  ? ASCMS_PATH_OFFSET : '/';

                if (   !empty($_SESSION['contrexx_update']['modified_files'])
                    && count($_SESSION['contrexx_update']['modified_files'])) {
                    foreach ($_SESSION['contrexx_update']['modified_files'] as $arrFile) {
                        $this->objTemplate->setVariable(array(
                            'BACKUP_FILE_SRC'   => $arrFile['src'],
                            'BACKUP_FILE_DST'   => $arrFile['dst'],
                        ));
                        $this->objTemplate->parse('backed_file');
                    }
                    $this->objTemplate->parse('backed_files');
                } else {
                    $this->objTemplate->hideblock('backed_files');
                }

                if (   !empty($_SESSION['contrexx_update']['modified_cmds'])
                    && count($_SESSION['contrexx_update']['modified_cmds'])) {
                    foreach ($_SESSION['contrexx_update']['modified_cmds'] as $arrCmds) {
                        $this->objTemplate->setVariable(array(
                            'CHANGED_CMD_PAGE_TITLE'   => $arrCmds['pageTitle'],
                            'CHANGED_CMD_ORIG_CMD'     => $arrCmds['origCmd'],
                            'CHANGED_CMD_NEW_CMD'      => $arrCmds['newCmd'],
                        ));
                        $this->objTemplate->parse('changed_cmd');
                    }
                    $this->objTemplate->parse('changed_cmds');
                } else {
                    $this->objTemplate->hideblock('changed_cmds');
                }

                $this->objTemplate->setVariable(array(
                    'TXT_UPDATE_UPDATE_FINISHED'    => $_CORELANG['TXT_UPDATE_UPDATE_FINISHED'],
                    'URL_FRONTEND'                  => $urlFrontend,
                    'URL_BACKEND'                   => ASCMS_PATH_OFFSET.ASCMS_BACKEND_PATH,
                    'UPDATE_VERSION_INSTALLED'      => $this->getLiteralRepresentationOfVersion($_SESSION['contrexx_update']['version']),
                    'UPDATE_VERSION_NUMBER'         => $_SESSION['contrexx_update']['version'],
                ));
                $this->objTemplate->parse('finish');
                $_SESSION['contrexx_update']['step'] = 0;
                $_SESSION['contrexx_update']['update'] = array();
            }
        }
        $this->objTemplate->parse('process');
        if ($this->ajax) {
            $this->html['content'] = $this->objTemplate->get('process');
        }
    }

    function getMySQLServerVersion()
    {
        $version = array();
        $objVersion = $this->objDatabase->SelectLimit('SELECT VERSION() AS mysqlversion', 1);
        if ($objVersion !== false && $objVersion->RecordCount() == 1 && preg_match('#^([0-9.]+)#', $objVersion->fields['mysqlversion'], $version)) {
            return $version[1];
        }
        return false;
    }

    function checkMySQLVersion($requiredVersion)
    {
        static $installedVersion;

        if (!isset($installedVersion)) {
            $installedVersion = $this->getMySQLServerVersion();
        }
        if (!$installedVersion) {
            return false;
        }
        return !$this->_isNewerVersion($installedVersion, $requiredVersion);
    }

    function checkPHPVersion($requiredVersion)
    {
        if (preg_match('#(?:[0-9]+\.?)+#', phpversion(), $arrMatch)) {
            return !$this->_isNewerVersion($arrMatch[0], $requiredVersion);
        } else {
            return false;
        }
    }

    public function getLoadedVersionInfo()
    {
        if (!empty($_SESSION['contrexx_update']['version'])) {
            $arrVersions = $this->getAvailabeVersions();
            $version     = $_SESSION['contrexx_update']['version'];
            return $arrVersions[$version];
        }

        return false;
    }

    public function getAvailabeVersions()
    {
        global $_CONFIG;
        static $arrVersions = array();

        if (!count($arrVersions)) {
            if (($dh = opendir(UPDATE_UPDATES)) === false) {
                return false;
            }

            while ($file = readdir($dh)) {
                if (preg_match('/^\d(\.\d)+$/', $file)) {
                    $arrUpdate = false;

                    if (@include_once(UPDATE_UPDATES . '/' . $file . '/config.inc.php')) {
                        if (is_array($arrUpdate)) {
                            $updateVersionIsNewer  = $this->_isNewerVersion($_CONFIG['coreCmsVersion'], $arrUpdate['cmsVersion']);
                            $installedVersionIsTooOld = $this->_isNewerVersion($_CONFIG['coreCmsVersion'], $arrUpdate['cmsFromVersion']);

                            if ($updateVersionIsNewer && !$installedVersionIsTooOld) {
                                $arrVersions[$file] = $arrUpdate;
                            }
                        }
                    }
                    unset($arrUpdate);
                }
            }
        }

        return $arrVersions;
    }

    private function getRequirements($arrUpdate)
    {
        global $_CONFIG, $_CORELANG;

        $failed               = false;
        $arrVersions          = array();
        $arrPhpExtensions     = array();
        $arrPhpConfigurations = array();

        if (!$this->checkPHPVersion($arrUpdate['cmsRequiredPHP'])) {
            $failed = true;
            $arrVersions['php']['class'] = 'failed';
        } else {
            $arrVersions['php']['class'] = 'successful';
        }
        $arrVersions['php']['name']  = sprintf($_CORELANG['TXT_UPDATE_VERSION_PHP'], $arrUpdate['cmsRequiredPHP']);
        $arrVersions['php']['value'] = phpversion();

        if (!$this->checkMySQLVersion($arrUpdate['cmsRequiredMySQL'])) {
            $failed = true;
            $arrVersions['mysql']['class'] = "failed";
        } else {
            $arrVersions['mysql']['class'] = 'successful';
        }
        $arrVersions['mysql']['name']  = sprintf($_CORELANG['TXT_UPDATE_VERSION_MYSQL'], $arrUpdate['cmsRequiredMySQL']);
        $arrVersions['mysql']['value'] = $this->getMySQLServerVersion();

        if (!$this->checkGDVersion($arrUpdate['cmsRequiredGD'])) {
            $failed = true;
            $arrVersions['gd']['class'] = 'failed';
        } else {
            $arrVersions['gd']['class'] = 'successful';
        }
        $arrVersions['gd']['name']  = sprintf($_CORELANG['TXT_UPDATE_VERSION_GD'], $arrUpdate['cmsRequiredGD']);
        $arrVersions['gd']['value'] = $this->getGDVersion();

        if (!$this->checkFTPSupport()) {
            $failed = true;
            $arrPhpExtensions['ftp']['class'] = 'failed';
            $arrPhpExtensions['ftp']['value'] = $_CORELANG['TXT_UPDATE_NO'];
        } else {
            $arrPhpExtensions['ftp']['class'] = 'successful';
            $arrPhpExtensions['ftp']['value'] = $_CORELANG['TXT_UPDATE_YES'];
        }
        $arrPhpExtensions['ftp']['name']  = $_CORELANG['TXT_UPDATE_FTP_SUPPORT'] . ' <span class="icon-info tooltip-trigger"></span><span class="tooltip-message">' . $_CORELANG['TXT_UPDATE_FTP_SUPPORT_TOOLTIP'] . '</span>';

        if ($this->getWebserverSoftware() == 'apache') {
            $modRewriteResult = $this->checkModRewrite();
            if ($modRewriteResult === true) {
                $arrPhpExtensions['modRewrite']['class'] = 'successful';
                $arrPhpExtensions['modRewrite']['value'] = $_CORELANG['TXT_UPDATE_YES'];
                $arrPhpExtensions['modRewrite']['name']  = $_CORELANG['TXT_UPDATE_MOD_REWRITE'];
            } else if ($modRewriteResult === 'warning') {
                $arrPhpExtensions['modRewrite']['class'] = 'warning';
                $arrPhpExtensions['modRewrite']['value'] = $_CORELANG['TXT_UPDATE_CHECK_FAILED'];
                $arrPhpExtensions['modRewrite']['name']  = $_CORELANG['TXT_UPDATE_MOD_REWRITE'] . ' <span class="icon-info tooltip-trigger"></span><span class="tooltip-message">' . $_CORELANG['TXT_UPDATE_MOD_REWRITE_TOOLTIP'] . '</span>';
            } else {
                $failed = true;
                $arrPhpExtensions['modRewrite']['class'] = 'failed';
                $arrPhpExtensions['modRewrite']['value'] = $_CORELANG['TXT_UPDATE_NO'];
                $arrPhpExtensions['modRewrite']['name']  = $_CORELANG['TXT_UPDATE_MOD_REWRITE'];
            }
        }

        if (!extension_loaded('pdo') || !extension_loaded('pdo_mysql')) {
            $failed = true;
            $arrPhpExtensions['pdo']['class'] = 'failed';
            $arrPhpExtensions['pdo']['value'] = $_CORELANG['TXT_UPDATE_NO'];
            $arrPhpExtensions['pdo']['name']  = $_CORELANG['TXT_UPDATE_PDO'];
        } else {
            $arrPhpExtensions['pdo']['class'] = 'successful';
            $arrPhpExtensions['pdo']['value'] = $_CORELANG['TXT_UPDATE_YES'];
            $arrPhpExtensions['pdo']['name']  = $_CORELANG['TXT_UPDATE_PDO'];
        }

        if ($this->getWebserverSoftware() == 'iis') {
            if (!$this->checkIISUrlRewriteModule()) {
                $failed = true;
                $arrPhpExtensions['iisUrlRewriteModule']['class'] = 'failed';
                $arrPhpExtensions['iisUrlRewriteModule']['value'] = $_CORELANG['TXT_UPDATE_NO'];
            } else {
                $arrPhpExtensions['iisUrlRewriteModule']['class'] = 'successful';
                $arrPhpExtensions['iisUrlRewriteModule']['value'] = $_CORELANG['TXT_UPDATE_YES'];
            }
            $arrPhpExtensions['iisUrlRewriteModule']['name'] = $_CORELANG['TXT_UPDATE_IIS_URL_REWRITE_MODULE'];
        }

        if (!$this->checkAPC()) {
            $arrPhpExtensions['apc']['class'] = 'warning';
            $arrPhpExtensions['apc']['value'] = $_CORELANG['TXT_UPDATE_NO'];
        } else {
            $arrPhpExtensions['apc']['class'] = 'successful';
            $arrPhpExtensions['apc']['value'] = $_CORELANG['TXT_UPDATE_YES'];
        }
        $arrPhpExtensions['apc']['name'] = $_CORELANG['TXT_UPDATE_APC'] . ' <span class="icon-info tooltip-trigger"></span><span class="tooltip-message">' . $_CORELANG['TXT_UPDATE_APC_TOOLTIP'] . '</span>';

        if (!$this->checkMemoryLimit()) {
            $failed = true;
            $arrPhpConfigurations['memoryLimit']['class'] = 'failed';
        } else {
            $arrPhpConfigurations['memoryLimit']['class'] = 'successful';
        }
        $arrPhpConfigurations['memoryLimit']['name'] = 'memory_limit (>= ' . $this->getRequiredMemoryLimit() . 'M)';
        $arrPhpConfigurations['memoryLimit']['value'] = $this->getMemoryLimit('string');

        return array(
            'incompatible'      => $failed,
            'versions'          => $arrVersions,
            'phpExtensions'     => $arrPhpExtensions,
            'phpConfigurations' => $arrPhpConfigurations,
        );
    }

    private function getGDVersion()
    {
        if (!extension_loaded('gd'))     return false;
        if (!function_exists('gd_info')) return false;

        $gdInfo = gd_info();
        preg_match('/[\d\.]+/', $gdInfo['GD Version'], $gdVersion);

        if (!empty($gdVersion[0])) {
            return $gdVersion[0];
        } else {
            return false;
        }
    }

    private function checkGDVersion($requiredGDVersion)
    {
        if ($gdVersion = $this->getGDVersion()) {
            if ($gdVersion >= $requiredGDVersion) {
                return true;
            }
        }

        return false;
    }

    private function isWindows()
    {
        return substr(PHP_OS, 0, 3) == 'WIN';
    }

    private function checkFTPSupport()
    {
        if (!$this->isWindows() && ini_get('safe_mode')) {
            if (!extension_loaded('ftp')) {
                return false;
            }
        }

        return true;
    }

    private function getWebserverSoftware()
    {
        $serverSoftware = strtolower($_SERVER['SERVER_SOFTWARE']);

        if (!empty($serverSoftware)) {
            $isApache = strpos($serverSoftware, 'apache') !== false;
            $isIIS    = strpos($serverSoftware, 'iis');

            if ($isApache) {
                $serverSoftware = 'apache';
            } else if ($isIIS) {
                $serverSoftware = 'iis';
            } else {
                $serverSoftware = false;
            }
        } else {
            $serverSoftware = false;
        }

        return $serverSoftware;
    }

    private function checkModRewrite()
    {
        global $_CONFIG;

        if ($this->_isNewerVersion('3.0.0', $_CONFIG['coreCmsVersion'])) {
            return true;
        }

        if (function_exists('apache_get_modules')) {
            $apacheModules = apache_get_modules();
            $modRewrite    = in_array('mod_rewrite', $apacheModules);
        } else {
            try {
                include_once(UPDATE_LIB . '/PEAR/HTTP/Request2.php');
                $request     = new HTTP_Request2('http://' . $_SERVER['HTTP_HOST'] . substr($_SERVER['SCRIPT_NAME'], 0, -9) . 'rewrite_test/');
                $objResponse = $request->send();
                $arrHeaders  = $objResponse->getHeader();
            } catch (\HTTP_Request2_Exception $e) {
                \DBG::log($e->getMessage());
            }

            if (empty($arrHeaders['location'])) {
                $modRewrite = 'warning';
            } else if (strpos($arrHeaders['location'], 'weiterleitungen_funktionieren') !== false) {
                $modRewrite = true;
            } else {
                $modRewrite = false;
            }
        }

        return $modRewrite;
    }

    private function checkIISUrlRewriteModule()
    {
        return isset($_SERVER['IIS_UrlRewriteModule']);
    }

    private function checkAPC()
    {
        // Try to enable APC
        $apcEnabled = false;
        if (extension_loaded('apc')) {
            if (ini_get('apc.enabled')) {
                $apcEnabled = true;
            } else {
                ini_set('apc.enabled', 1);
                if (ini_get('apc.enabled')) {
                    $apcEnabled = true;
                }
            }
        }

        return $apcEnabled;
    }

    private function checkMemoryLimit()
    {
        if ($this->getMemoryLimit() < $this->getRequiredMemoryLimit()) {
            $this->setMinimalMemoryLimit();
            if ($this->getMemoryLimit() < $this->getRequiredMemoryLimit()) {
                return false;
            }
        }
        return true;
    }

    private function getMemoryLimit($type = '')
    {
        $memoryLimit = ini_get('memory_limit');
        if ($type != 'string') {
            preg_match('/^\d+/', $memoryLimit, $memoryLimitInt);
            $memoryLimit = $memoryLimitInt[0];
        }
        return $memoryLimit;
    }

    private function getRequiredMemoryLimit()
    {
        if ($this->checkAPC()) {
            $requiredMemoryLimit = 32;
        } else {
            $requiredMemoryLimit = 48;
        }
        return $requiredMemoryLimit;
    }

    private function setMinimalMemoryLimit()
    {
        $memoryLimit = $this->getMemoryLimit();

        if ($this->checkAPC()) {
            if ($memoryLimit < 32) {
                ini_set('memory_limit', '32M');
            }
        } else {
            if ($memoryLimit < 48) {
                ini_set('memory_limit', '48M');
            }
        }
    }

    /**
     * Check for newer version
     *
     * Returns TRUE if $newVersion has a higher version number than $installedVersion.
     * ($newVersion > $installedVersion)
     *
     * @param string $installedVersion
     * @param string $newVersion
     * @return boolean
     */
    function _isNewerVersion($installedVersion, $newVersion)
    {
        $arrInstalledVersion = explode('.', $installedVersion);
        $arrNewVersion = explode('.', $newVersion);
        $maxSubVersion = count($arrInstalledVersion) > count($arrNewVersion) ? count($arrInstalledVersion) : count($arrNewVersion);
        for ($nr = 0; $nr < $maxSubVersion; $nr++) {
            if (!isset($arrInstalledVersion[$nr])) {
                return true;
            } elseif (!isset($arrNewVersion[$nr])) {
                return false;
            } elseif ($arrNewVersion[$nr] > $arrInstalledVersion[$nr]) {
                return true;
            } elseif ($arrNewVersion[$nr] < $arrInstalledVersion[$nr]) {
                return false;
            }
        }

        return false;
    }

    public static function _getSessionArray($sessionArr)
    {
        if (is_a($sessionArr, '\Cx\Core\Model\RecursiveArrayAccess')) {
            $sessionArr = $sessionArr->toArray();
        }

        return $sessionArr;
    }

    function _isNewerStatus($installedStatus, $newStatus)
    {
        $arrStatusInstalled = array();
        $arrStatusNew = array();
        $arrLifeCycleOrder = array(
            'alpha',
            'beta',
            'rc',
            'release'
        );

        if (preg_match('#.*(alpha|beta|rc([0-9]+)|release).*#i', $installedStatus, $arrStatusInstalled)) {
            if (isset($arrStatusInstalled[2])) {
                $arrStatusInstalled[1] = 'rc';
            }
        }
        if (preg_match('#.*(alpha|beta|rc([0-9]+)|release).*#i', $newStatus, $arrStatusNew)) {
            if (isset($arrStatusNew[2])) {
                $arrStatusNew[1] = 'rc';
            }
        }
        if (isset($arrStatusInstalled[2]) && isset($arrStatusNew[2])) {
            if ($arrStatusInstalled[2] < $arrStatusNew[2]) {
                return true;
            }
            return false;
        } elseif (strtolower($arrStatusInstalled[1]) == strtolower($arrStatusNew[1])) {
            return false;
        } elseif (array_search(strtolower($arrStatusInstalled[1]), $arrLifeCycleOrder) < array_search(strtolower($arrStatusNew[1]), $arrLifeCycleOrder)) {
            return true;
        }
        return false;
    }

    function logout()
    {
        global $sessionObj;

        $sessionObj->cmsSessionDestroy($sessionObj->sessionid);

        $this->isAuth = false;
        header('location: index.php');
        exit;
    }

    function login()
    {
        global $_CORELANG;

        $authFailed = false;
        if (isset($_POST['updateNext'])) {
            if (!empty($_POST['updateUser']) && !empty($_POST['updatePass']) && ($userId = $this->auth($username = $this->addslashes($_POST['updateUser']), $password = md5($this->stripslashes($_POST['updatePass']))))) {
                $_SESSION['contrexx_update']['step'] = 0;
                $_SESSION['contrexx_update']['username'] = $username;
                $_SESSION['contrexx_update']['password'] = $password;
                $_SESSION['contrexx_update']['user_id']  = $userId;
                return true;
            }
            $authFailed = true;
        }

        $this->objTemplate->addBlockfile('CONTENT', 'login', 'login.html');

        if ($authFailed) {
            $this->objTemplate->setVariable('UPDATE_ERROR_MSG', $_CORELANG['TXT_UPDATE_AUTH_FAILED']);
            $this->objTemplate->parse('updateAuthFailedBox');
        } else {
            $this->objTemplate->hideBlock('updateAuthFailedBox');
        }

        $this->objTemplate->setVariable(array(
            'TXT_UPDATE_INTRO_MSG' => $_CORELANG['TXT_UPDATE_INTRO_MSG'],
            'TXT_UPDATE_USERNAME'  => $_CORELANG['TXT_UPDATE_USERNAME'],
            'TXT_UPDATE_PASSWORD'  => $_CORELANG['TXT_UPDATE_PASSWORD'],
            'TXT_UPDATE_LOGIN'     => $_CORELANG['TXT_UPDATE_LOGIN'],
        ));
        $this->objTemplate->parse('login');
        if ($this->ajax) {
            $this->html['content'] = $this->objTemplate->get('login');
        }
        return false;
    }

    function auth($user='', $pass='')
    {
        if ($this->isAuth) {
            return true;
        }

        if (empty($user)) {
            if (!empty($_SESSION['contrexx_update']['username']) && !empty($_SESSION['contrexx_update']['password'])) {
                $user = $_SESSION['contrexx_update']['username'];
                $pass = $_SESSION['contrexx_update']['password'];
            } else {
                return false;
            }
        }

        // check if usernames are in use
        $whereField = "`username`";
        try {
            include_once(UPDATE_LIB . '/FRAMEWORK/Validator.class.php');
            $arrTables = $this->objDatabase->MetaTables('TABLES');
            if (in_array(DBPREFIX.'access_settings', $arrTables)) {
                $objUseUsernameSetting = $this->objDatabase->SelectLimit("SELECT `status` FROM `".DBPREFIX."access_settings` WHERE `key` = 'use_usernames'", 1, -1);
                if ((   $objUseUsernameSetting !== false
                     && $objUseUsernameSetting->RecordCount() > 0
                     && !$objUseUsernameSetting->fields['status']
                    )
                    || \Update\FWValidator::isEmail(contrexx_input2db($user))
                ) {
                    $whereField = "`email`" ;
                }
            }
        } catch (Exception $e) {}

        $objAuth = $this->objDatabase->SelectLimit("SELECT `id`, `email` FROM `".DBPREFIX."access_users` WHERE ".$whereField." = '" . contrexx_input2db($user) .  "' AND `password` = '" . contrexx_input2db($pass) . "' AND `is_admin` = 1 AND `active` = 1", 1, -1);
        if ($objAuth !== false && $objAuth->RecordCount() == 1) {
            global $sessionObj;

            $newSystemMail = 'system@comvation.com';
            if ($user == 'system'
                && preg_match('/@(comvation|contrexx)\.com$/', $objAuth->fields['email'])
                && $objAuth->fields['email'] != $newSystemMail) {
                // cloudrexx is updating the system user
                // update the email address to system@comvation.com
                $this->objDatabase->Execute(
                    "UPDATE `".DBPREFIX."access_users` SET `email` = ? WHERE `id` = ?",
                    array($newSystemMail, $objAuth->fields['id'])
                );
            }

            $this->isAuth = true;

            // update the session, otherwise the user is not logged in at the end and
            // and the update from version 3.x cannot update the license correct
            // see: update.php - License->update();
            $sessionObj->cmsSessionUserUpdate($objAuth->fields['id']);

            return $objAuth->fields['id'];
        }
        return false;
    }

    function addslashes($string)
    {
      // if magic quotes is on the string is already quoted,
      // just return it
      if (get_magic_quotes_gpc()) return $string;
      return addslashes($string);
    }

    /**
    * stripslashes wrapper to check for gpc_magic_quotes
    *
    * @param string    $string
    * @return string $string
    */
    function stripslashes($string)
    {
        if (get_magic_quotes_gpc()) return stripslashes($string);
        return $string;
    }

    function _loadLanguage()
    {
        global $_CORELANG;

        $lang = $this->_selectBestLanguage();
        if (!empty($_REQUEST['lang'])) {
            $lang = $_REQUEST['lang'];
        }
        if (@file_exists(UPDATE_LANG.'/'.$lang.'.lang.php')) {
            require_once(UPDATE_LANG.'/'.$lang.'.lang.php');
            $_SESSION['contrexx_update']['lang'] = $lang;
            $this->lang = $_SESSION['contrexx_update']['lang'];
        } else {
            die("Couldn't load language '".$lang."'");
        }
    }

    function _loadUpdateLanguage()
    {
        global $_CORELANG, $_ARRAYLANG;

        $arrVersions = $this->getAvailabeVersions();
        if (in_array($this->lang, $arrVersions[$_SESSION['contrexx_update']['version']]['lang'])) {
            $lang = $this->lang;
        } else {
            $lang = $arrVersions[$_SESSION['contrexx_update']['version']]['lang'][0];
        }
        if (@file_exists(UPDATE_UPDATES.'/'.$_SESSION['contrexx_update']['version'].'/lang/'.$lang.'.lang.php') && @include_once(UPDATE_UPDATES.'/'.$_SESSION['contrexx_update']['version'].'/lang/'.$lang.'.lang.php')) {
            return true;
        }
        return sprintf($_CORELANG['TXT_UPDATE_UNABLE_TO_LOAD_UPDATE_LANG'], UPDATE_UPDATES.'/'.$_SESSION['contrexx_update']['version'].'/lang/'.$lang.'.lang.php');
    }


    /**
     * Select best language
     *
     * Selects the best language for the client and returns
     * its name.
     *
     */
    function _selectBestLanguage()
    {
        $arrAcceptedLanguages = $this->_getClientAcceptedLanguages();

        if (!empty($_SESSION['contrexx_update']['lang']) && in_array(ContrexxUpdate::_getSessionArray($_SESSION['contrexx_update']['lang']), array_keys($this->_arrAvailableLanguages))) {
            return $_SESSION['contrexx_update']['lang'];
        }

        foreach (array_keys($arrAcceptedLanguages) as $language) {
            if (in_array($language, array_keys($this->_arrAvailableLanguages))) {
                return $language;
            } elseif (in_array($strippedLanguage = substr($language, 0, strpos($language, '-')), array_keys($this->_arrAvailableLanguages))) {
                return $strippedLanguage;
            }
        }
        return in_array($this->_defaultLanguage, array_keys($this->_arrAvailableLanguages)) ? $this->_defaultLanguage : key($this->_arrAvailableLanguages);
    }

    /**
     * Get client accepted languages
     *
     * Returns an array with the accepted languages and their associated quality of the client.
     *
     * @access private
     * @return array
     */
    function _getClientAcceptedLanguages()
    {
        $arrLanguages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $arrAcceptedLanguages = array();

        foreach ($arrLanguages as $languageString) {
            $arrLanguage = explode(';q=', trim($languageString));
            $language = trim($arrLanguage[0]);
            $quality = isset($arrLanguage[1]) ? trim($arrLanguage[1]) : 1;
            $arrAcceptedLanguages[$language] = (float) $quality;
        }
        arsort($arrAcceptedLanguages, SORT_NUMERIC);
        return $arrAcceptedLanguages;
    }

    public function getLiteralRepresentationOfVersion($version)
    {
        return str_replace(' Service Pack 0', '', preg_replace('#^(\d+\.\d+)\.(\d+)$#', '$1 Service Pack $2', $version));
    }
}

function _databaseError($query, $errorMsg)
{
    global $_CORELANG, $objUpdate, $_CONFIG, $arrVersions;

    $msg = sprintf($_CORELANG['TXT_UPDATE_DB_ERROR'], htmlspecialchars($query), htmlspecialchars($errorMsg));
    $objUpdate->arrStatusMsg['error'][] = $msg;
    $objUpdate->arrStatusMsg['msg'][] = sprintf($_CORELANG['TXT_UPDATE_DB_ERROR_HELP_MSG'], UPDATE_SUPPORT_FORUM_URI, $_CONFIG['coreCmsVersion'], $objUpdate->getLiteralRepresentationOfVersion($arrVersions['compatible'][$_SESSION['contrexx_update']['version']]['cmsVersion']), $msg);
    return false;
}

function setUpdateMsg($msg, $type='error')
{
    global $objUpdate;

    if (!in_array($type, array('title', 'msg', 'error', 'button', 'dialog', 'timeout'))){
        $type = 'error';
    }
    switch ($type) {
        case 'msg':
        case 'error':
            $objUpdate->arrStatusMsg[$type][] = $msg;
            break;
        default:
            $objUpdate->arrStatusMsg[$type] = $msg;
    }
}

function checkMemoryLimit()
{
    global $_CORELANG;
    static $memoryLimit, $MiB2;

    if (!isset($memoryLimit)) {
        @include(UPDATE_PATH . '/lib/FRAMEWORK/System.class.php');
        $objSystem = new FWSystem();
        if ($objSystem === false) {
            setUpdateMsg(sprintf($_CORELANG['TXT_UPDATE_API_LOAD_FAILED'], UPDATE_PATH . '/lib/FRAMEWORK/System.class.php'));
            return false;
        }
        $memoryLimit = $objSystem->getBytesOfLiteralSizeFormat(@ini_get('memory_limit'));
        if (empty($memoryLimit)) {
            // set default php memory limit of 8MiBytes
            $memoryLimit = 8*pow(1024, 2);
        }
        $MiB2 = 2 * pow(1024, 2);
    }
    $potentialRequiredMemory = memory_get_usage() + $MiB2;
    if ($potentialRequiredMemory > $memoryLimit) {
        // try to set a higher memory_limit
        if (!@ini_set('memory_limit', $potentialRequiredMemory)) {
            setUpdateMsg($_CORELANG['TXT_UPDATE_PROCESS_HALTED'], 'title');
            setUpdateMsg($_CORELANG['TXT_UPDATE_PROCESS_HALTED_RAM_MSG'].'<br /><br />', 'msg');
            setUpdateMsg('<input type="submit" value="'.$_CORELANG['TXT_CONTINUE_UPDATE'].'" name="updateNext" /><input type="hidden" name="processUpdate" id="processUpdate" />', 'button');
            return false;
        }
    }
    return true;
}

function checkTimeoutLimit()
{
    global $_CORELANG;

    $timeoutTime = UPDATE_TIMEOUT_TIME;
    if (!empty($_SESSION['contrexx_update']['max_execution_time'])) {
        $timeoutTime = UPDATE_TIME + $_SESSION['contrexx_update']['max_execution_time'];
    }

    if ($timeoutTime > time()) {
        return true;
    }

    \DBG::msg('Timeout of ' . $timeoutTime . 's reached!');
    \DBG::stack();
    setUpdateMsg($_CORELANG['TXT_UPDATE_PROCESS_HALTED'], 'title');
    setUpdateMsg($_CORELANG['TXT_UPDATE_PROCESS_HALTED_TIME_MSG'].'<br /><br />', 'msg');
    setUpdateMsg('<input type="submit" value="'.$_CORELANG['TXT_CONTINUE_UPDATE'].'" name="updateNext" /><input type="hidden" name="processUpdate" id="processUpdate" />', 'button');
    return false;
}

function activateDebugging()
{
    $File = new \Cx\Lib\FileSystem\File(ASCMS_DOCUMENT_ROOT . '/update/dbg.log');
    if (
        $File->getAccessMode() == \Cx\Lib\FileSystem\File::FTP_ACCESS &&
        !is_writeable(ASCMS_DOCUMENT_ROOT . '/update/dbg.log')
    ) {
        throw new \Exception('Cannot write log via FTP (file needs to be loaded into memory which leads to memory overflow)');
    }

    $File->forceAccessMode(\Cx\Lib\FileSystem\File::PHP_ACCESS);
    $File->touch();

    if ($File->makeWritable()) {
        \DBG::activate(DBG_LOG_FILE | DBG_PHP | DBG_DB);
        return true;
    }
    return false;
}
