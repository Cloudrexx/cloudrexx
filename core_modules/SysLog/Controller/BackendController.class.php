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
* System log
* @copyright    CLOUDREXX CMS - CLOUDREXX AG
* @author       Michael Ritter <michael.ritter@comvation.com>
* @package      cloudrexx
* @subpackage   coremodule_syslog
* @version      5.0.0
*/
namespace Cx\Core_Modules\SysLog\Controller;

/**
* Backend for the system log
* @copyright    CLOUDREXX CMS - CLOUDREXX AG
* @author       Michael Ritter <michael.ritter@comvation.com>
* @package      cloudrexx
* @subpackage   coremodule_syslog
* @version      5.0.0
*/
class BackendController extends \Cx\Core\Core\Model\Entity\SystemComponentBackendController {

    /**
     * This component's backend has only the default CMD
     * @return array List of commands
     */
    public function getCommands() {
        return array();
    }

    /**
     * Parses a rudimentary system log backend page
     * @param \Cx\Core\Html\Sigma $template Backend template for this page
     * @param array $cmd Supplied CMD
     */
    public function parsePage(\Cx\Core\Html\Sigma $template, array $cmd, &$isSingle = false) {
        $em = $this->cx->getDb()->getEntityManager();
        $logRepo = $em->getRepository('Cx\Core_Modules\SysLog\Model\Entity\Log');

        // @todo: parse message if no entries (template block exists already)
        $parseObject = $this->getNamespace().'\Model\Entity\Log';

        // set default sorting
        if (!isset($_GET['order'])) {
            $_GET['order'] = 'timestamp/DESC';
        }
        // configure view
        $viewGenerator = new \Cx\Core\Html\Controller\ViewGenerator('Cx\Core_Modules\SysLog\Model\Entity\Log', $this->getAllViewGeneratorOptions());
        $template->setVariable('ENTITY_VIEW', $viewGenerator);
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
            case 'Cx\Core_Modules\SysLog\Model\Entity\Log':
                return array(
                    'functions' => array(
                        'delete' => 'true',
                        'paging' => true,
                        'sorting' => true,
                        'edit' => true,
                    ),
                    'fields' => array(
                        'id' => array(
                            'showOverview' => false,
                        ),
                        'timestamp' => array(
                            'readonly' => true,
                        ),
                        'severity' => array(
                            'readonly' => true,
                            'table' => array(
                                'parse' => function($data, $rows) {
                                    return '<span class="' . contrexx_raw2xhtml(strtolower($data)) . '_background">' . contrexx_raw2xhtml($data) . '</span>';
                                },
                            ),
                        ),
                        'message' => array(
                            'readonly' => true,
                            'table' => array(
                                'parse' => function($data, $rows) {
                                    $url = clone \Cx\Core\Routing\Url::fromRequest();
                                    $url->setMode('backend');
                                    $url->setParam('editid', $rows['id']);
                                    return '<a href="' . $url . '">' . contrexx_raw2xhtml($data) . '</a>';
                                },
                            ),
                        ),
                        'data' => array(
                            'readonly' => true,
                            'showOverview' => false,
                            'type' => 'text',
                        ),
                        'logger' => array(
                            'readonly' => true,
                        ),
                    ),
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
