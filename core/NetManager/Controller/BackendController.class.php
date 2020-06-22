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
 * @subpackage  core_netmanager
 */

namespace Cx\Core\NetManager\Controller;

/**
 * Specific BackendController for this Component. Use this to easily create a backend view
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_netmanager
 */
class BackendController extends \Cx\Core\Core\Model\Entity\SystemComponentBackendController {

    /**
     * Returns a list of available commands (?act=XY)
     * @return array List of acts
     */
    public function getCommands() {
        return array();
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
     */
    public function parsePage(\Cx\Core\Html\Sigma $template, array $cmd, &$isSingle = false) {
        $this->parseEntityClassPage($template, 'Cx\Core\Net\Model\Entity\Domain', current($cmd), array(), $isSingle);
    }

    /**
     * Returns all entities of this component which can have an auto-generated view
     *
     * @access protected
     * @return array
     */
    protected function getEntityClassesWithView() {
        // at the moment the view is only used for domain overview
        return array(
            'Cx\Core\Net\Model\Entity\Domain',
        );
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
        switch($entityClassName){
            case 'Cx\Core\Net\Model\Entity\Domain':
                return array(
                    'header'    => $_ARRAYLANG['TXT_CORE_NETMANAGER'],
                    'entityName'    => $_ARRAYLANG['TXT_CORE_NETMANAGER_ENTITY'],
                    'fields'    => array(
                        'name'  => array(
                            'header' => $_ARRAYLANG['TXT_NAME'],
                            'table' => array(
                                'parse' => function($value) {
                                    global $_ARRAYLANG;
                                    static $mainDomainName;
                                    if (empty($mainDomainName)) {
                                        $domainRepository = new \Cx\Core\Net\Model\Repository\DomainRepository();
                                        $mainDomainName = $domainRepository->getMainDomain()->getName();
                                    }
                                    $domainName = contrexx_raw2xhtml(\Cx\Core\Net\Controller\ComponentController::convertIdnToUtf8Format($value));
                                    if($domainName!=contrexx_raw2xhtml($value)) {
                                        $domainName.= ' (' .  contrexx_raw2xhtml($value) . ')';
                                    }
                                    $mainDomainIcon = '';
                                    if ($value == $mainDomainName) {
                                        $mainDomainIcon = ' <img src="'.\Env::get('cx')->getCodeBaseCoreWebPath().'/Core/View/Media/icons/Home.png" title="'.$_ARRAYLANG['TXT_CORE_CONFIG_MAINDOMAINID'].'" />';
                                    }
                                    return $domainName.$mainDomainIcon;
                                },
                            ),
                            'formfield' => function($fieldname, $fieldtype, $fieldlength, $fieldvalue, $fieldoptions) {
                                return \Cx\Core\Net\Controller\ComponentController::convertIdnToUtf8Format($fieldvalue);
                            },
                        ),
                        'id'    => array(
                            'showOverview' => false,
                        ),
                    ),
                    'functions' => array(
                        'add'           => true,
                        'edit'          => false,
                        'allowEdit'    => true,
                        'delete'        => false,
                        'allowDelete'   => true,
                        'sorting'       => true,
                        'paging'        => true,
                        'filtering'     => false,
                        'actions'       => function($rowData, $rowId) {
                            global $_CORELANG;
                            static $mainDomainName;
                            if (empty($mainDomainName)) {
                                $domainRepository = new \Cx\Core\Net\Model\Repository\DomainRepository();
                                $mainDomainName = $domainRepository->getMainDomain()->getName();
                            }

                            preg_match_all('/\d+/', $rowId, $ids, null, 0);

                            // hostname's ID is 0
                            if (!$ids[0][1]) {
                                return '';
                            }

                            $actionIcons = '';
                            $csrfParams = \Cx\Core\Csrf\Controller\Csrf::param();
                            if ($mainDomainName !== $rowData['name']) {
                                $actionIcons = '<a '
                                    . 'href="' . \Env::get('cx')->getWebsiteBackendPath() . '/?cmd=NetManager&amp;editid=' . $rowId . '"'
                                    . 'class="edit" title="Edit entry">'
                                    . '</a>';
                                $actionIcons .= '<a
                                    onclick=" if(confirm(\''.$_CORELANG['TXT_CORE_RECORD_DELETE_CONFIRM'].'\'))'
                                    . 'window.location.replace(\'' . \Env::get('cx')->getWebsiteBackendPath()
                                    . '/?cmd=NetManager&amp;deleteid=' . (empty($ids[0][1])?0:$ids[0][1])
                                    . '&amp;vg_increment_number=' . (empty($ids[0][0])?0:$ids[0][0])
                                    . '&amp;' . $csrfParams . '\');" href="javascript:void(0);"'
                                    . 'class="delete"'
                                    . 'title="Delete entry">
                                    </a>';
                            }
                            return $actionIcons;
                        }
                    )
                );
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
                break;
        }
    }
}
