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
 * Specific BackendController for this Component. Use this to easily create a backend view
 *
 * @copyright   Cloudrexx AG
 * @author      Sebastian Brand <sebastian.brand@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_wysiwyg
 * @version     1.0.0
 */

namespace Cx\Core\Wysiwyg\Controller;

/**
 * Specific BackendController for this Component. Use this to easily create a backend view
 *
 * @copyright   Cloudrexx AG
 * @author      Sebastian Brand <sebastian.brand@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_wysiwyg
 * @version     1.0.0
 */
class BackendController extends \Cx\Core\Core\Model\Entity\SystemComponentBackendController {
    /**
    * Returns a list of available commands (?act=XY)
    * @return array List of acts
    */
    public function getCommands() {
        $cmds = array();
        foreach ($this->getEntityClasses() as $class) {
            $cmds[] = preg_replace('#' . preg_quote($this->getNamespace() . '\\Model\\Entity\\') . '#', '', $class);
        }
        return $cmds;
    }

    /**
    * Use this to parse your backend page
    *
    * You will get the template located in /View/Template/{CMD}.html
    * You can access Cx class using $this->cx
    * To show messages, use \Message class
    * @param \Cx\Core\Html\Sigma $template Template for current CMD
    * @param array $cmd CMD separated by slashes
    * @global array $_ARRAYLANG Language data
    */
    public function parsePage(\Cx\Core\Html\Sigma $template, array $cmd, &$isSingle = false) {
        global $_ARRAYLANG;

        // Parse entity view generation pages
        $entityClassName = $this->getNamespace() . '\\Model\\Entity\\' . current($cmd);
        if (in_array($entityClassName, $this->getEntityClasses())) {
            $this->parseEntityClassPage($template, $entityClassName, current($cmd));
            return;
        }

        // Not an entity, parse overview or settings
        switch (current($cmd)) {
            case 'Settings':
                \Cx\Core\Setting\Controller\Setting::init('Wysiwyg', 'config', 'Yaml');

                if(isset($_POST) && isset($_POST['bsubmit'])) {
                    \Cx\Core\Setting\Controller\Setting::set('specificStylesheet', isset($_POST['specificStylesheet'])?1:0);
                    \Cx\Core\Setting\Controller\Setting::set('replaceActualContents', isset($_POST['replaceActualContents'])?1:0);
                    \Cx\Core\Setting\Controller\Setting::set(
                        'sortBehaviour',
                        isset($_POST['sortBehaviour']) ? $_POST['sortBehaviour'] : 'custom'
                    );
                    \Cx\Core\Setting\Controller\Setting::storeFromPost();
                }

                $i = 0;
                if (!\Cx\Core\Setting\Controller\Setting::isDefined('specificStylesheet')
                    && !\Cx\Core\Setting\Controller\Setting::add('specificStylesheet', '0', ++$i, \Cx\Core\Setting\Controller\Setting::TYPE_CHECKBOX, '1', 'config')
                ){
                    throw new \Exception("Failed to add new configuration option");
                }
                if (!\Cx\Core\Setting\Controller\Setting::isDefined('replaceActualContents')
                    && !\Cx\Core\Setting\Controller\Setting::add('replaceActualContents', '0', ++$i, \Cx\Core\Setting\Controller\Setting::TYPE_CHECKBOX, '1', 'config')
                ){
                    throw new \Exception("Failed to add new configuration option");
                }
                if (
                    !\Cx\Core\Setting\Controller\Setting::isDefined('sortBehaviour') &&
                    !\Cx\Core\Setting\Controller\Setting::add(
                        'sortBehaviour',
                        'custom',
                        ++$i,
                        \Cx\Core\Setting\Controller\Setting::TYPE_RADIO,
                        'alphabetical:TXT_CORE_WYSIWYG_ALPHABETICAL,custom:TXT_CORE_WYSIWYG_CUSTOM',
                        'config'
                    )
                ) {
                    throw new \Exception('Failed to add new configuration option');
                }

                $tmpl = new \Cx\Core\Html\Sigma();
                \Cx\Core\Setting\Controller\Setting::show(
                    $tmpl,
                    'index.php?cmd=Config&act=Wysiwyg&tpl=Settings',
                    $_ARRAYLANG['TXT_CORE_WYSIWYG'],
                    $_ARRAYLANG['TXT_CORE_WYSIWYG_ACT_SETTINGS'],
                    'TXT_CORE_WYSIWYG_'
                );

                $template->setVariable('WYSIWYG_CONFIG_TEMPLATE', $tmpl->get());
                break;
            case 'Functions':
                $toolbarController = $this->getController('Toolbar');
                // check if the toolbar shall be saved
                if (isset($_POST) && isset($_POST['save'])) {
                    // Get the database connection
                    $dbCon = $this->cx->getDb()->getAdoDb();
                    // Check if there is already a default toolbar
                    $defaultToolbar = $dbCon->Execute('
                        SELECT `id` FROM `' . DBPREFIX . 'core_wysiwyg_toolbar`
                        WHERE `is_default` = 1
                        LIMIT 1');
                    // Check if the query did not fail
                    if (!$defaultToolbar) {
                        throw new \Exception('Failed to check for existing default toolbar!');
                    }
                    // Get the default toolbar id
                    $toolbarId = $defaultToolbar->fields['id'];
                    $toolbarController->store(
                        $_POST['removedButtons'],
                        $toolbarId,
                        true
                    );
                }
                $toolbarConfigurator = $toolbarController->getToolbarConfiguratorTemplate(
                    $this->getDirectory(false, true),
                    true
                );
                // Get the template and replace the placeholder
                $template->setVariable(
                    'WYSIWYG_CONFIG_TEMPLATE',
                    $toolbarConfigurator->get()
                );
                break;
            case '':
            default:
                if ($template->blockExists('overview')) {
                    $template->touchBlock('overview');
                }
                break;
        }
    }

    /**
     * This is called by the default ComponentController and does all the repeating work
     *
     * This loads a template named after current $act and calls parsePage($actTemplate)
     * @todo $this->cx->getTemplate()->setVariable() should not be called here but in Cx class
     * @global array $_ARRAYLANG Language data
     * @global $subMenuTitle
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Resolved page
     */
    public function getPage(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $_ARRAYLANG, $subMenuTitle;
        $subMenuTitle = $_ARRAYLANG['TXT_' . strtoupper($this->getType()) . '_' . strtoupper($this->getName())];

        $cmd = array('');
        if (isset($_GET['act'])) {
            $cmd = explode('/', contrexx_input2raw($_GET['act']));
        } else {
            $cmd[0] = 'Wysiwyg';
        }

        $actTemplate = new \Cx\Core\Html\Sigma($this->getDirectory(true) . '/View/Template/Backend');
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
        $this->parsePage($actTemplate, $cmd);

        // set tabs
        $navigation = new \Cx\Core\Html\Sigma(\Env::get('cx')->getCodeBaseCorePath() . '/Core/View/Template/Backend');
        $navigation->loadTemplateFile('Navigation.html');
        $commands = array_merge($this->getCommands());
        foreach ($commands as $key=>$command) {
            $subnav = array();
            if (is_array($command)) {
                $subnav = array_merge(array(''), $command);
                $command = $key;
            }

            if ($key !== '') {
                if ($cmd[0] == $command) {
                    $navigation->touchBlock('tab_active');
                } else {
                    $navigation->hideBlock('tab_active');
                }
                $act = '&amp;act=' . $command;
                $txt = $command;
                if (empty($command)) {
                    $act = '';
                    $txt = 'DEFAULT';
                }
                $actTxtKey = 'TXT_' . strtoupper($this->getType()) . '_' . strtoupper($this->getName() . '_ACT_' . $txt);
                $actTitle = isset($_ARRAYLANG[$actTxtKey]) ? $_ARRAYLANG[$actTxtKey] : $actTxtKey;
                $navigation->setVariable(array(
                    'HREF' => 'index.php?cmd=' . $this->getName() . $act,
                    'TITLE' => $actTitle,
                ));
                $navigation->parse('tab_entry');
            }

            // subnav
            if ($cmd[0] == $command && count($subnav)) {
                $first = true;
                foreach ($subnav as $subcommand) {
                    if ((!isset($cmd[1]) && $first) || ((isset($cmd[1]) ? $cmd[1] : '') == $subcommand)) {
                        $navigation->touchBlock('subnav_active');
                    } else {
                        $navigation->hideBlock('subnav_active');
                    }
                    $act = '&amp;act=' . $cmd[0] . '/' . $subcommand;
                    $txt = (empty($cmd[0]) ? 'DEFAULT' : $cmd[0]) . '_';
                    if (empty($subcommand)) {
                        $act = '&amp;act=' . $cmd[0] . '/';
                        $txt .= 'DEFAULT';
                    } else {
                        $txt .= strtoupper($subcommand);
                    }
                    $actTxtKey = 'TXT_' . strtoupper($this->getType()) . '_' . strtoupper($this->getName() . '_ACT_' . $txt);
                    $actTitle = isset($_ARRAYLANG[$actTxtKey]) ? $_ARRAYLANG[$actTxtKey] : $actTxtKey;
                    $navigation->setVariable(array(
                        'HREF' => 'index.php?cmd=' . $this->getName() . $act,
                        'TITLE' => $actTitle,
                    ));
                    $navigation->parse('subnav_entry');
                    $first = false;
                }
            }
        }
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

        $sortBy = array('field' => ['order' => SORT_ASC]);
        $order  = array();
        \Cx\Core\Setting\Controller\Setting::init('Wysiwyg', 'config', 'Yaml');
        if (\Cx\Core\Setting\Controller\Setting::getValue('sortBehaviour') === 'alphabetical') {
            $sortBy = array();
            $order  = array('title' => SORT_ASC);
        }

        return array(
            'header' => $_ARRAYLANG['TXT_' . strtoupper($this->getType() . '_' . $this->getName() . '_ACT_' . $classIdentifier)],
            'entityName' => $_ARRAYLANG['TXT_' . strtoupper($this->getType() . '_' . $this->getName() . '_ACT_' . $classIdentifier) . '_ENTITY'],
            'order' => array(
                'overview' => array(
                    'active',
                    'title',
                    'description',
                ),
            ),
            'functions' => array(
                'add'       => true,
                'edit'      => true,
                'delete'    => true,
                'order'     => $order,
                'paging'    => true,
                'filtering' => false,
                'sortBy'    => $sortBy,
            ),
            'fields' => array(
                'id' => array(
                    'showOverview' => false,
                ),
                'title' => array(
                    'header' => $_ARRAYLANG['TXT_' . strtoupper($this->getType() . '_' . $this->getName() . '_ACT_' . $classIdentifier) . '_TITLE'],
                    'table' => array(
                        'parse' => function($data, $rows, $options) {
                            $editUrl = clone \Env::get('cx')->getRequest()->getUrl();
                            $editUrl->setParam('editid', '{' . $options['functions']['vg_increment_number'] . ',' . $rows['id'] . '}');
                            $data = '<a href="' . $editUrl . '" title="'.$data.'">'.$data.'</a>';
                            return $data;
                        },
                    ),
                ),
                'description' => array(
                    'header' => $_ARRAYLANG['TXT_' . strtoupper($this->getType() . '_' . $this->getName() . '_ACT_' . $classIdentifier) . '_DESCRIPTION'],
                ),
                'active' => array(
                    'header' => $_ARRAYLANG['TXT_' . strtoupper($this->getType() . '_' . $this->getName() . '_ACT_' . $classIdentifier) . '_STATE'],
                    'formtext' => $_ARRAYLANG['TXT_' . strtoupper($this->getType() . '_' . $this->getName() . '_ACT_' . $classIdentifier) . '_ACTIVE'],
                    'sorting' => false,
                    'table' => array(
                        'parse' => function($data, $rows) {
                            $img = 'led_red.gif';
                            if ($data) {
                                $img = 'led_green.gif';
                            }
                            $data = '<img src="core/Core/View/Media/icons/'.$img.'" />';
                            return $data;
                        },
                    ),
                ),
                'imagePath' => array(
                    'header' => $_ARRAYLANG['TXT_' . strtoupper($this->getType() . '_' . $this->getName() . '_ACT_' . $classIdentifier) . '_IMAGE_PATH'],
                    'type' => 'image',
                    'showOverview' => false,
                    'options' => array('startmediatype' => 'wysiwyg'),
                ),
                'htmlContent' => array(
                    'header' => $_ARRAYLANG['TXT_' . strtoupper($this->getType() . '_' . $this->getName() . '_ACT_' . $classIdentifier) . '_HTML_CONTENT'],
                    'showOverview' => false,
                    'type' => 'sourcecode',
                    'options' => array('mode' => 'html'),
                ),
                'order' => array(
                    'header' => $_ARRAYLANG['TXT_' . strtoupper($this->getType() . '_' . $this->getName() . '_ACT_' . $classIdentifier) . '_ORDER'],
                    'showOverview' => false,
                    'showDetail' => false,
                ),
            ),
        );
    }
}
