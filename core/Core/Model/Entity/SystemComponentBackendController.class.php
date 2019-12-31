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
 * Backend controller to create a default backend view.
 *
 * Create a subclass of this in order to create a normal backend view
 *
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_core
 * @version     3.1.0
 */

namespace Cx\Core\Core\Model\Entity;

/**
 * Backend controller to create a default backend view.
 *
 * Create a subclass of this in order to create a normal backend view
 *
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_core
 * @version     3.1.0
 */
class SystemComponentBackendController extends Controller {

    /**
     * Default permission
     *
     * @var Cx\Core_Modules\Access\Model\Entity\Permission
     */
    protected $defaultPermission;

    /**
     * Returns a list of available commands (?act=XY)
     * @return array List of acts
     */
    public function getCommands() {
        $cmds = array();
        foreach ($this->getEntityClasses() as $class) {
            if (is_a($class, '')) {
                continue;
            }
            $cmdName = preg_replace('#' . preg_quote($this->getNamespace() . '\\Model\\Entity\\') . '#', '', $class);
            if (is_subclass_of($class, '\Gedmo\Translatable\Translatable')) {
                $cmds[$cmdName] = array('translatable' => true);
            } else {
                $cmds[] = $cmdName;
            }
        }
        $cmds['Settings'] = array('Help');
        return $cmds;
    }

    /**
     * This is called by the default ComponentController and does all the repeating work
     *
     * This loads a template named after current $act and calls parsePage($actTemplate)
     * @todo $this->cx->getTemplate()->setVariable() should not be called here but in Cx class
     * @global array $_ARRAYLANG Language data
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Resolved page
     */
    public function getPage(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $_ARRAYLANG, $subMenuTitle;
        $subMenuTitle = $_ARRAYLANG['TXT_' . strtoupper($this->getType()) . '_' . strtoupper($this->getName())];

        $cmd = array('');
        if (isset($_GET['act'])) {
            $cmd = explode('/', contrexx_input2raw($_GET['act']));
        }

        $actTemplate = new \Cx\Core\Html\Sigma($this->getDirectory(false) . '/View/Template/Backend');
        $filename = $cmd[0] . '.html';
        $testFilename = $cmd[0];
        if (!\Env::get('ClassLoader')->getFilePath($actTemplate->getRoot() . '/' . $filename)) {
            $filename = 'Default.html';
            $testFilename = 'Default';
        }
        foreach ($cmd as $index=>$name) {
            if ($index == 0) {
                continue;
            }

            $testFilename .= $name;
            if (\Env::get('ClassLoader')->getFilePath($actTemplate->getRoot() . '/' . $testFilename . '.html')) {
                $filename = $testFilename . '.html';
            } else {
                break;
            }
        }
        $actTemplate->loadTemplateFile($filename);

        // todo: Messages
        $navigation = $this->parseNavigation($cmd);
        $this->parsePage($actTemplate, $cmd);
        $txt = $cmd[0];
        if (empty($txt)) {
            $txt = 'DEFAULT';
        }

        // default css and js
        if (file_exists($this->cx->getClassLoader()->getFilePath($this->getDirectory(false) . '/View/Style/Backend.css'))) {
            \JS::registerCSS(substr($this->getDirectory(false, true) . '/View/Style/Backend.css', 1));
        }
        if (file_exists($this->cx->getClassLoader()->getFilePath($this->getDirectory(false) . '/View/Script/Backend.js'))) {
            \JS::registerJS(substr($this->getDirectory(false, true) . '/View/Script/Backend.js', 1));
        }

        // finish
        $actTemplate->setGlobalVariable($_ARRAYLANG);
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($actTemplate);
        $page->setContent($actTemplate->get());
        $cachedRoot = $this->cx->getTemplate()->getRoot();
        $this->cx->getTemplate()->setRoot(\Env::get('cx')->getCodeBaseCorePath() . '/Core/View/Template/Backend');
        $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'ContentMaster.html');
        $this->cx->getTemplate()->setRoot($cachedRoot);
        $this->cx->getTemplate()->setVariable(array(
            'CONTENT_NAVIGATION' => $navigation->get(),
            'ADMIN_CONTENT' => $page->getContent(),
            'CONTENT_TITLE' => $_ARRAYLANG['TXT_' . strtoupper($this->getType()) . '_' . strtoupper($this->getName() . '_ACT_' . $txt)],
        ));
    }

    /**
     * Parse the navigation
     *
     * @param array $cmd
     *
     * @return \Cx\Core\Html\Sigma
     */
    public function parseNavigation(&$cmd = array()) {
        // set tabs
        $navigation = new \Cx\Core\Html\Sigma(\Env::get('cx')->getCodeBaseCorePath() . '/Core/View/Template/Backend');
        $navigation->loadTemplateFile('Navigation.html');

        $commands = $this->getCommands();
        if ($this->showOverviewPage()) {
            $commands = array_merge(
                array('' => array('permission' => $this->defaultPermission)),
                $commands
            );
        }
        // make sure first tab is shown if $cmd[0] is empty
        if (empty($cmd[0])) {
            $cmd[0] = reset($commands);
            if (is_array($cmd[0])) {
                $cmd[0] = key($commands);
            }
        }
        $originalCommands = $commands;
        $this->checkAndModifyCmdByPermission($cmd, $commands);
        foreach ($commands as $key => $command) {
            $subNav         = array();
            $currentCommand = is_array($command) ? $key : $command;

            if (is_array($command) && isset($command['children'])) {
                $subNav = array_merge(array('' => array('permission' => $this->defaultPermission)), $command['children']);
            } else {
                if (is_array($command)) {
                    if (array_key_exists('permission', $command)) {
                        unset($command['permission']); // navigation might contain only the permission key, unset it
                    }
                    if (array_key_exists('translatable', $command)) {
                        unset($command['translatable']); // navigation might contain only the translatable key, unset it
                    }
                }
                $subNav = is_array($command) && !empty($command)  ? array_merge(array(''), $command) : array();
            }
            //check the main navigation permission
            if (!$this->hasAccessToCommand(array($currentCommand))) {
                continue;
            }
            //parse the main navigation
            $this->parseCurrentNavItem($navigation, 'tab', $currentCommand, '', $cmd[0] == $currentCommand, 0);

            // subnav
            if ($cmd[0] == $currentCommand && count($subNav)) {
                $first = true;
                foreach ($subNav as $subkey => $subValue) {
                    $subcommand = is_array($subValue) ? $subkey : $subValue;
                    if (!$this->hasAccessToCommand(array($currentCommand, $subcommand))) {
                        continue;
                    }
                    $isActiveSubNav = (!isset($cmd[1]) && $first) || ((isset($cmd[1]) ? $cmd[1] : '') == $subcommand);
                    //parse the subnavigation
                    $this->parseCurrentNavItem($navigation, 'subnav', $subcommand, $currentCommand, $isActiveSubNav, 1);
                    $first = false;
                }
            }
        }
        if (
            isset($originalCommands[current($cmd)]) &&
            isset($originalCommands[current($cmd)]['translatable']) &&
            $originalCommands[current($cmd)]['translatable']
        ) {
            $navigation->setVariable(
                'FRONTEND_LANG_MENU', \Env::get('init')->getUserFrontendLangMenu(true)
            );
        }
        return $navigation;
    }

    /**
     * Check and modify the cmd based on the permission
     *
     * @param array $cmd
     * @param array $currentCommands
     */
    protected function checkAndModifyCmdByPermission(&$cmd, $currentCommands) {
        $command  = array();
        $keys     = array_keys($currentCommands);
        $cmd[1]   = !isset($cmd[1]) ? '' : $cmd[1];
        foreach ($cmd as $cmdKey => $cmdValue) {
            $command[$cmdKey] = $cmdValue;
            while (!$this->hasAccessToCommand($command)) {
                $pos = array_search($cmdValue, $keys);
                if (!isset($keys[$pos + 1])) {
                    \Permission::noAccess();
                    exit();
                }
                $cmdValue = $command[$cmdKey] = $keys[$pos + 1];
            }
            $keys = isset($currentCommands[$cmdValue]['children']) ? array_keys($currentCommands[$cmdValue]['children']) : '';
        }
        $cmd = $command;
    }

    /**
     * Parse the current navigation item
     *
     * @global array $_ARRAYLANG
     *
     * @param \Cx\Core\Html\Sigma $navigation
     * @param string              $blockName
     * @param string              $currentCmd
     * @param string              $mainCmd
     * @param boolean             $isActiveNav
     * @param boolean             $isSubNav
     */
    protected function parseCurrentNavItem(\Cx\Core\Html\Sigma $navigation, $blockName, $currentCmd, $mainCmd, $isActiveNav, $isSubNav) {
        global $_ARRAYLANG;

        if (empty($blockName)) {
            return;
        }

        $isActiveNav ? $navigation->touchBlock($blockName . '_active') : $navigation->hideBlock($blockName . '_active');

        if (empty($isSubNav)) {
            $act = empty($currentCmd) ? '' : '&amp;act=' . $currentCmd;
            $txt = empty($currentCmd) ? 'DEFAULT' : $currentCmd;
        } else {
            $act = '&amp;act=' . $mainCmd . '/' . $currentCmd;
            $txt = (empty($mainCmd) ? 'DEFAULT' : $mainCmd) . '_';
            $txt .= empty($currentCmd) ? 'DEFAULT' : strtoupper($currentCmd);
        }

        $actTxtKey = 'TXT_' . strtoupper($this->getType()) . '_' . strtoupper($this->getName() . '_ACT_' . $txt);
        $actTitle  = isset($_ARRAYLANG[$actTxtKey]) ? $_ARRAYLANG[$actTxtKey] : $actTxtKey;
        $navigation->setVariable(array(
            'HREF' => 'index.php?cmd=' . $this->getName() . $act,
            'TITLE' => $actTitle,
        ));
        $navigation->parse($blockName . '_entry');
    }

    /**
     * Check the access permission based on the command
     *
     * @param array $commands
     *
     * @return boolean
     */
    protected function hasAccessToCommand($commands = array()) {
        $currentCommands = array_merge(array('' => array('permission' => $this->defaultPermission)), $this->getCommands());

        foreach ($commands as $command) {
            $cmd = isset($currentCommands[$command]) ? $currentCommands[$command] : array();
            if (!$this->hasAccess($cmd)) {
                return false;
            }
            unset($cmd['permission']);
            $currentCommands = isset($cmd['children']) ? $cmd['children'] : $cmd;
        }
        return true;
    }

    /**
     * Check the access permission
     *
     * @param array $command
     *
     * @return boolean
     */
    protected function hasAccess($command) {
        $objPermission = is_array($command) && isset($command['permission']) ? $command['permission'] : $this->defaultPermission;
        if ($objPermission instanceof \Cx\Core_Modules\Access\Model\Entity\Permission) {
            if (!$objPermission->hasAccess()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Use this to parse your backend page
     *
     * You will get the template located in /View/Template/{CMD}.html
     * You can access Cx class using $this->cx
     * To show messages, use \Message class
     * @param \Cx\Core\Html\Sigma $template Template for current CMD
     * @param array $cmd CMD separated by slashes
     * @param boolean $isSingle Wether edit view or not
     * @return ?\Cx\Core\Html\Controller\ViewGenerator Used ViewGenerator or null
     */
    public function parsePage(\Cx\Core\Html\Sigma $template, array $cmd, &$isSingle = false) {
        global $_ARRAYLANG;

        // Last entry will be empty if we're on a nav-entry without children
        // or on the first child of a nav-entry.
        // The following code works fine as long as we don't want an entity
        // view on the first child of a nav-entry or introduce a third
        // nav-level. If we want either, we need to refactor getCommands() and
        // parseNavigation().
        $entityName = '';
        if (!empty($cmd) && !empty($cmd[count($cmd) - 1])) {
            $entityName = $cmd[count($cmd) - 1];
        } else if (!empty($cmd)) {
            $entityName = $cmd[0];
        }

        // Parse entity view generation pages
        $entityClassName = $this->getNamespace() . '\\Model\\Entity\\' . $entityName;
        if (in_array($entityClassName, $this->getEntityClasses())) {
            return $this->parseEntityClassPage($template, $entityClassName, $entityName, array(), $isSingle);
        }

        // Not an entity, parse overview or settings
        switch (current($cmd)) {
            case 'Settings':
                if (!isset($cmd[1])) {
                    $cmd[1] = '';
                }
                switch ($cmd[1]) {
                    case '':
                        \Cx\Core\Setting\Controller\Setting::init(
                            $this->getName(),
                            null,
                            'FileSystem',
                            null,
                            \Cx\Core\Setting\Controller\Setting::REPOPULATE
                        );
                        \Cx\Core\Setting\Controller\Setting::storeFromPost();
                        \Cx\Core\Setting\Controller\Setting::setEngineType(
                            $this->getName(),
                            'FileSystem'
                        );
                        \Cx\Core\Setting\Controller\Setting::show(
                            $template,
                            $this->getName() . '/' . implode('/', $cmd),
                            $this->getName(),
                            $_ARRAYLANG[
                                'TXT_' . strtoupper(
                                    $this->getType()
                                ) . '_' . strtoupper(
                                    $this->getName() . '_ACT_' . $cmd[0] . '_DEFAULT'
                                )
                            ],
                            'TXT_' . strtoupper(
                                $this->getType() . '_' . $this->getName()
                            ) . '_'
                        );
                        break;
                    default:
                        if (!$template->blockExists('mailing')) {
                            return null;
                        }
                        $template->setVariable(
                            'MAILING',
                            \Cx\Core\MailTemplate\Controller\MailTemplate::adminView(
                                $this->getName(),
                                'nonempty',
                                $config['corePagingLimit'],
                                'Settings/email'
                            )->get()
                        );
                        break;
                }
                break;
            case '':
            default:
                if ($template->blockExists('overview')) {
                    $template->touchBlock('overview');
                }
                break;
        }
        return null;
    }

    protected function parseEntityClassPage($template, $entityClassName, $classIdentifier, $filter = array(), &$isSingle = false) {
        if (!$template->blockExists('entity_view')) {
            return;
        }
        // this should be moved to view generator
        if (count($filter)) {
            $em = $this->cx->getDb()->getEntityManager();
            $repo = $em->getRepository($entityClassName);
            $entityClassName = $repo->findBy($filter);
        }
        $view = new \Cx\Core\Html\Controller\ViewGenerator(
            $this->getViewGeneratorParseObjectForEntityClass($entityClassName),
            $this->getAllViewGeneratorOptions($entityClassName)
        );
        $renderedContent = $view->render($isSingle);
        $template->setVariable('ENTITY_VIEW', $renderedContent);
        return $view;
    }

    /**
     * Returns the object to parse a view with
     *
     * If you overwrite this and return anything else than string, filter will not work
     * @return string|array|object An entity class name, entity, array of entities or DataSet
     */
    protected function getViewGeneratorParseObjectForEntityClass($entityClassName) {
        return $entityClassName;
    }

    /**
     * Returns all entities of this component which can have an auto-generated view
     *
     * @access protected
     * @return array
     */
    protected function getEntityClassesWithView() {
        return $this->getEntityClasses();
    }

    /**
     * This function returns an array which contains the vgOptions array for all entities
     *
     * @access public
     * @param $dataSetIdentifier
     * @return array
     */
    public function getAllViewGeneratorOptions($dataSetIdentifier = '') {
        $vgOptions = array();
        foreach ($this->getEntityClassesWithView() as $entityClassName) {
            $vgOptions[$entityClassName] = $this->getViewGeneratorOptions($entityClassName, $dataSetIdentifier);
        }
        $vgOptions[''] = $this->getViewGeneratorOptions('', '');
        return $vgOptions;
    }

    /**
     * This function returns the ViewGeneration options for a given entityClass
     *
     * @access protected
     * @global $_ARRAYLANG
     * @param $entityClassName contains the FQCN from entity
     * @param $dataSetIdentifier if $entityClassName is DataSet, this is used for better partition
     * @return array with options
     */
    protected function getViewGeneratorOptions($entityClassName, $dataSetIdentifier = '') {
        global $_ARRAYLANG;

        $classNameParts = explode('\\', $entityClassName);
        $classIdentifier = end($classNameParts);

        $langVarName = 'TXT_' . strtoupper($this->getType() . '_' . $this->getName() . '_ACT_' . $classIdentifier);
        $header = '';
        if (isset($_ARRAYLANG[$langVarName])) {
            $header = $_ARRAYLANG[$langVarName];
        }
        return array(
            'header' => $header,
            'functions' => array(
                'add'       => true,
                'edit'      => true,
                'delete'    => true,
                'sorting'   => true,
                'paging'    => true,
                'filtering' => false,
            ),
        );
    }

    /**
     * Return true here if you want the first tab to be an entity view
     * @return boolean True if overview should be shown, false otherwise
     */
    protected function showOverviewPage() {
        return true;
    }
}
