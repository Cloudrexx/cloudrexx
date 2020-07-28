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
 * @subpackage  core_routing
 */

namespace Cx\Core\Routing\Controller;

/**
 * Specific BackendController for this Component. Use this to easily create a backend view
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_routing
 */
class BackendController extends \Cx\Core\Core\Model\Entity\SystemComponentBackendController
{

    /**
    * Returns a list of available commands (?act=XY)
    * @return array List of acts
    */
    public function getCommands() {
        return array('RewriteRule');
    }

    /**
     * This function returns the ViewGeneration options for a given entityClass
     *
     * @todo This is used as a temporary workaround to set user titles of fieldnames
     * If BackendTable and FormGenerator use a sensful format for getting the
     * fieldname titles (/headers), this can be removed.
     *
     * @access protected
     * @global $_ARRAYLANG
     * @param $entityClassName contains the FQCN from entity
     * @param $dataSetIdentifier if $entityClassName is DataSet, this is used for better partition
     * @return array with options
     */
    protected function getViewGeneratorOptions($entityClassName, $dataSetIdentifier = '') {
        global $_ARRAYLANG;

        $langVarName = 'TXT_' . strtoupper($this->getType() . '_' . $this->getName() . '_ACT_' . $dataSetIdentifier);
        $header = '';
        if (isset($_ARRAYLANG[$langVarName])) {
            $header = $_ARRAYLANG[$langVarName];
        }
        return array(
            'header' => $header,
            'fields' => array(
                'id' => array(
                    'showOverview' => false,
                ),
                'regularExpression' => array(
                    'header' => $_ARRAYLANG['regularExpression'],
                    'tooltip' => $_ARRAYLANG['TXT_CORE_ROUTING_REGULAR_EXPRESSION_TOOLTIP'],
                ),
                'orderNo' => array(
                    'showOverview' => false,
                    'showDetail' => false,
                    'header' => $_ARRAYLANG['orderNo'],
                ),
                'rewriteStatusCode' => array(
                    'header' => $_ARRAYLANG['rewriteStatusCode'],
                    'type' => 'select',
                    'validValues' => array(301=>301, 302=>302, 'intern'=>'Intern'),
                    'tooltip' => $_ARRAYLANG['TXT_CORE_ROUTING_REWRITE_STATUS_CODE_TOOLTIP'],
                ),
                'continueOnMatch' => array(
                    'header' => $_ARRAYLANG['continueOnMatch'],
                    'tooltip' => $_ARRAYLANG['TXT_CORE_ROUTING_CONTINUE_ON_MATCH_TOOLTIP'],
                ),
            ),
            'functions' => array(
                'add'       => true,
                'edit'      => true,
                'delete'    => true,
                'sorting'   => true,
                'paging'    => true,
                'filtering' => false,
                'sortBy' => [
                    'field' => ['orderNo' => SORT_ASC]
                ]
            ),
        );
    }
}
