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
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_cron
 */

namespace Cx\Core_Modules\Cron\Controller;

/**
 * Specific BackendController for this Component. Use this to easily create a backend view
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_cron
 */
class BackendController extends \Cx\Core\Core\Model\Entity\SystemComponentBackendController {

    /**
     * Template object
     */
    protected $template;


    /**
     * Returns a list of available commands (?act=XY)
     * @return array List of acts
     */
    public function getCommands() {
        return array('settings');
    }

    /**
     * Use this to parse your backend page
     *
     * You will get the template located in /View/Template/{CMD}.html
     * You can access Cx class using $this->cx
     * To show messages, use \Message class
     * @param \Cx\Core\Html\Sigma $template Template for current CMD
     * @param array $cmd CMD separated by slashes
     */
    public function parsePage(\Cx\Core\Html\Sigma $template, array $cmd, &$isSingle = false) {
        // this class inherits from Controller, therefore you can get access to
        // Cx like this:
        $this->cx;
        $this->template = $template;
        $act = $cmd[0];

        $this->connectToController($act);

        \Message::show();
    }

    /**
     * Trigger a controller according the act param from the url
     *
     * @param   string $act
     */
    public function connectToController($act)
    {
        $act = ucfirst($act);
        if (!empty($act)) {
            $controllerName = __NAMESPACE__.'\\'.$act.'Controller';
            if (!$controllerName && !class_exists($controllerName)) {
                return;
            }
            //  instantiate the view specific controller
            $objController = new $controllerName($this->getSystemComponentController(), $this->cx);
        } else {
            // instantiate the default View Controller
            $objController = new DefaultController($this->getSystemComponentController(), $this->cx);
        }
        $objController->parsePage($this->template, array());
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

        switch ($entityClassName) {
            case 'Cx\Core_Modules\Cron\Model\Entity\Job':
                return array(
                    'header'    => $_ARRAYLANG['TXT_CORE_MODULE_CRON_ACT_DEFAULT'],
                    'functions' => array(
                        'add'       => true,
                        'edit'      => true,
                        'delete'    => true,
                        'sorting'   => true,
                        'paging'    => true,
                        'filtering' => false,
                    ),
                    'fields' => array(
                        'id' => array(
                            'showOverview' => false,
                        ),
                        'active' => array(
                            'header' => $_ARRAYLANG['TXT_CORE_MODULE_CRON_ACTIVE'],
                        ),
                        'expression' => array(
                            'header' => $_ARRAYLANG['TXT_CORE_MODULE_CRON_EXPRESSION'],
                        ),
                        'command' => array(
                            'header' => $_ARRAYLANG['TXT_CORE_MODULE_CRON_COMMAND'],
                            'storecallback' => function ($value) {
                                return $value['command'] . ' ' . $value['arguments'];
                            },
                            'formfield' => function ($name, $type, $length, $value, $options) {
                                $field = new \Cx\Core\Html\Model\Entity\HtmlElement('span');
                                $commandSelectOptions = array_keys($this->cx->getCommands());
                                $value = explode(' ', $value, 2);
                                $commandSelect = new \Cx\Core\Html\Model\Entity\DataElement(
                                    $name . '[command]',
                                    isset($value[0]) ? $value[0] : '',
                                    \Cx\Core\Html\Model\Entity\DataElement::TYPE_SELECT,
                                    null,
                                    array_combine(
                                        array_values($commandSelectOptions),
                                        array_values($commandSelectOptions)
                                    )
                                );
                                $commandArguments = new \Cx\Core\Html\Model\Entity\DataElement(
                                    $name . '[arguments]',
                                    isset($value[1]) ? $value[1] : ''
                                );
                                $field->addChild($commandSelect);
                                $field->addChild($commandArguments);
                                return $field;
                            },
                        ),
                        'lastRan' => array(
                            'header' => $_ARRAYLANG['TXT_CORE_MODULE_CRON_LAST_RUN'],
                        ),
                    )
                );
                break;
            default:
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
    }
}
