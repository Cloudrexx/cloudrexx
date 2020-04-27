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


namespace Cx\Core_Modules\Workbench\Controller;

class SandboxException extends \Exception {}

class Sandbox {
    const MODE_DQL = 'dql';
    const MODE_PHP = 'php';
    protected $mode = self::MODE_DQL;
    protected $template = null;
    protected $code = null;
    protected $result = null;
    protected $errrorHandlerActive = false;

    public function __construct(&$language, $mode, &$arguments) {
        //\DBG::activate(DBG_PHP);
        $this->mode = $mode;
        $this->initialize($arguments);
        $this->execute();
        $this->show($language);
    }

    protected function initialize(&$arguments) {
        switch ($this->mode) {
            case self::MODE_DQL:
                $this->code = 'SELECT p FROM Cx\Core\ContentManager\Model\Entity\Page p WHERE p.id < 10';
                if (!empty($arguments['code'])) {
                    $this->code = contrexx_input2raw($arguments['code']);
                }
                $this->result = '';
                break;
            case self::MODE_PHP:
            default:
                $this->code = 'return $em->getRepository(\'Cx\Core\ContentManager\Model\Entity\Node\')->verify();';
                if (!empty($arguments['code'])) {
                    $this->code = contrexx_input2raw($arguments['code']);
                }
                $this->result = '';
                break;
        }
    }

    protected function execute() {
        switch ($this->mode) {
            case self::MODE_DQL:
                $this->result = '';
                $strQuery = trim($this->code);
                $lister = new \Cx\Core_Modules\Listing\Controller\ListingController(
                    function(&$offset, &$count, &$criteria, &$order) use ($strQuery) {
                        return \Env::get('em')->createQuery($strQuery);
                    }
                );
                try {
                    $table = new \BackendTable($lister->getData(
                        \Cx\Core\Core\Controller\Cx::instanciate()->getRequest()->getParams()
                    ));
                    $this->result = $table->toHtml().$lister;
                } catch (\Exception $e) {
                    $this->result = 'Could not execute query (' . $e->getMessage() . ')!';
                }
                break;
            case self::MODE_PHP:
                $dbgMode = \DBG::getMode();
                try {
                    // This error handler catches all Warnings and Notices and some Strict errors
                    \DBG::activate(DBG_PHP);
                    set_error_handler(array($this, 'phpErrorsAsExceptionsHandler'));
                    $this->errrorHandlerActive = true;
                    // Since DBG catches the rest (E_PARSE) let's use that
                    ob_start();
                    $function = function ($em, $cx) {
                        // The use of eval() is prohibited by the development guidelines of cloudrexx.
                        // However, as the sandbox's purpose is to run code from within the backend
                        // section, we do allow the usage of *eval()* in this very specific case.
                        return eval($this->code);
                    };
                    $dbgContents = ob_get_clean();
                    \DBG::activate($dbgMode);
                    if (!is_callable($function)) {
                        // parse exception
                        throw new SandboxException($dbgContents);
                    }
                    $this->result = var_export($function(\Env::get('em'), \Env::get('cx')), true);
                    restore_error_handler();
                    $this->errrorHandlerActive = false;
                } catch (\Exception $e) {
                    \DBG::activate($dbgMode);
                    restore_error_handler();
                    $this->errrorHandlerActive = false;
                    $this->result = get_class($e) . ': ' . $e->getMessage();
                }
                break;
            default:
                break;
        }
    }

    /**
     * This code does not belong here, but where to put it?
     */
    protected function getEntities() {
        $entities = array();
        $sortedEntities = array(
            'noncx' => array(),
        );
        foreach (get_declared_classes() as $entity) {
            if (!is_subclass_of($entity, '\\Cx\\Model\\Base\\EntityBase')) {
                continue;
            }
            $parts = explode('\\', $entity);
            if ($parts[0] == 'Cx') {
                if ($parts[1] == 'Model' && $parts[2] == 'Proxies') {
                    continue;
                }
                if (!isset($sortedEntities[$parts[1]])) {
                    $sortedEntities[$parts[1]] = array();
                }
                if (!isset($sortedEntities[$parts[1]][$parts[2]])) {
                    $sortedEntities[$parts[1]][$parts[2]] = array();
                }
                end($parts);
                $sortedEntities[$parts[1]][$parts[2]][$entity] = current($parts);
            } else {
                $sortedEntities['noncx'][] = $entity;
            }
        }
        if (!count($sortedEntities['noncx'])) {
            unset($sortedEntities['noncx']);
        }
        $entityList = '<ul>';
        foreach ($sortedEntities as $componentType=>$componentNames) {
            $entityList .= '<li>' . $componentType . '<ul>';
            foreach ($componentNames as $componentName=>$entities) {
                $entityList .= '<li>' . $componentName . '<ul>';
                foreach ($entities as $entityClass=>$entityName) {
                    $entityList .= '<li title="' . $entityClass . '">' . $entityName . '</li>';
                }
                $entityList .= '</ul></li>';
            }
            $entityList .= '</ul></li>';
        }
        $entityList .= '</ul>';
        return $entityList;
    }

    /**
     * @todo: no HTML here!
     */
    protected function show(&$lang) {
        $this->template = new \Cx\Core\Html\Sigma(ASCMS_CORE_MODULE_PATH . '/Workbench/View/Template/Backend');
        $this->template->loadTemplateFile('Sandbox.html');

        switch ($this->mode) {
            case self::MODE_DQL:
                $sideboxTitle = $lang['TXT_WORKBENCH_SANDBOX_ENTITIES'];
                $sideboxContent = $this->getEntities();
                break;
            case self::MODE_PHP:
            default:
                $sideboxTitle = $lang['TXT_WORKBENCH_SANDBOX_VARIABLES'];
                $sideboxContent = '<ul>
                    <li>$cx Contrexx main class</li>
                    <li>$em Doctrine entity manager</li>
                </ul>';
                break;
        }

        $this->template->setVariable(array(
            'FORM_ACTION' => 'index.php?cmd=Workbench&act=sandbox/' . $this->mode,
            'TXT_WORKBENCH_SANDBOX_SUBMIT' => $lang['TXT_WORKBENCH_SANDBOX_SUBMIT'],
            'CODE' => $this->code,
            'RESULT' => $this->result,
            'SIDEBOX_TITLE' => $sideboxTitle,
            'SIDEBOX_CONTENT' => $sideboxContent,
        ));
    }

    public function phpErrorsAsExceptionsHandler($errno, $errstr) {
        if (!$this->errrorHandlerActive) {
            return;
        }
        throw new SandboxException($errstr);
    }

    public function __toString() {
        return $this->template->get();
    }
}
