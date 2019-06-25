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
 * BackendTable
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core
 */

/**
 * BackendTable
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core
 */
class BackendTable extends HTML_Table {

    /**
     * @var string Fully qualified template file name
     */
    protected $templateFile = '';

    /**
     * @var bool if table is editable
     */
    protected $editable = false;

    /**
     * @var \Cx\Core\Html\Controller\ViewGenerator $viewGenerator instance of
     * ViewGenerator so we can load more than one view
     */
    protected $viewGenerator;

    /**
     * Whether or not the table has a master table header.
     * A master table header is used as a title and is being
     * parsed as TH tags.
     * If no master table header is set, then the column labels
     * will be used as the master table header and are being
     * parsed as TH tags.
     * Otherwise, if a master table header is set, the column labels
     * are being parsed as regular TD tags, but with row class row3.
     */
    protected $hasMasterTableHeader = false;

    /**
     * BackendTable constructor.
     * @param array $attrs        attributes of view generator
     * @param array $options      options of view generator
     * @param string $entityClass class name of entity
     * @param \Cx\Core\Html\Controller\ViewGenerator $viewGenerator instance of ViewGenerator
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    public function __construct($attrs = array(), $options = array(), $entityClass = '', $viewGenerator = null) {
        global $_ARRAYLANG;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();

        $this->viewGenerator = $viewGenerator;
        if (!empty($options['functions']['editable'])) {
            $this->editable = true;
        }
        if (
            !isset($options['template']['table']) || 
            !file_exists($options['template']['table'])
        ) {
            $this->templateFile = $cx->getCodeBaseCorePath().
                '/Html/View/Template/Generic/Table.html';
        } else { 
            $this->templateFile = $options['template']['table'];
        }
               
        if ($attrs instanceof \Cx\Core_Modules\Listing\Model\Entity\DataSet) {
            $this->hasMasterTableHeader = !empty($options['header']);
            // add master table-header-row
            if ($this->hasMasterTableHeader) {
                $this->addRow(array(0 => $options['header']), null, 'th');
            }
            $first = true;
            $row = 1 + $this->hasMasterTableHeader;
            $sortBy     = (    isset($options['functions']['sortBy'])
                            && is_array($options['functions']['sortBy'])
                          )
                          ? $options['functions']['sortBy']
                          : array();
            $sortingKey = !empty($sortBy) && isset($sortBy['sortingKey'])
                          ? $sortBy['sortingKey']
                          : '';
            $sortField  = !empty($sortingKey) && isset($sortBy['field'])
                          ? key($sortBy['field'])
                          : '';
            $component  = !empty($sortBy) && isset($sortBy['component'])
                          ? $sortBy['component']
                          : '';
            $entity     = !empty($sortBy) && isset($sortBy['entity'])
                          ? $sortBy['entity']
                          : '';
            $sortOrder  = !empty($sortBy) && isset($sortBy['sortOrder'])
                          ? $sortBy['sortOrder']
                          : '';
            $pagingPos  = !empty($sortBy) && isset($sortBy['pagingPosition'])
                          ? $sortBy['pagingPosition']
                          : '';
            $status     = (isset($options['functions']['status']) &&
                           is_array($options['functions']['status'])
                          ) ? $options['functions']['status']
                          : array();
            $statusComponent = !empty($status) && isset($status['component'])
                ? $status['component']
                : '';
            $statusEntity = !empty($status) && isset($status['entity'])
                ? $status['entity']
                : '';

            $formGenerator = new \Cx\Core\Html\Controller\FormGenerator($attrs, '', $entityClass, '', $options, 0, null, $this->viewGenerator, true);

            foreach ($attrs as $rowname=>$rows) {
                $col = 0;
                $virtual = $rows['virtual'];
                unset($rows['virtual']);
                if (isset($options['multiActions'])) {
                    $this->setCellContents($row, $col, '<input name="select-' . $rowname . '" value="' . $rowname . '" type="checkbox" />', 'TD', '0', false);
                    $col++;
                }
                foreach ($rows as $header=>$data) {

                    if (!empty($sortingKey) && $header === $sortingKey) {
                        //Add the additional attribute id, for getting the updated sort order after the row sorting
                        $this->updateRowAttributes($row, array('id' => 'sorting' . $entity . '_' . $data), true);
                    }
                    $encode = true;
                    if (
                        isset($options['fields']) &&
                        isset($options['fields'][$header]) &&
                        isset($options['fields'][$header]['showOverview']) &&
                        !$options['fields'][$header]['showOverview']
                    ) {
                        continue;
                    }

                    if (!empty($sortField) && $header === $sortField) {
                        //Add the additional attribute class, to display the updated sort order after the row sorting
                        $this->updateColAttributes($col, array('class' => 'sortBy' . $sortField));
                    }
                    $origHeader = $header;
                    if(isset($options['fields'][$header]['sorting'])) {
                        $sorting = $options['fields'][$header]['sorting'];
                    } else if(isset($options['functions']['sorting'])) {
                        $sorting = $options['functions']['sorting'];
                    }
                    if ($first) {
                        if (isset($options['fields'][$header]['header'])) {
                            $header = $options['fields'][$header]['header'];
                        }
                        if (isset($_ARRAYLANG[$header])) {
                            $header = $_ARRAYLANG[$header];
                        }
                        if (
                            is_array($options['functions']) &&
                            isset($options['functions']['sorting']) &&
                            $options['functions']['sorting'] &&
                            $sorting !== false
                        ) {
                            $order = '';
                            $img = '&uarr;&darr;';
                            $sortParamName = !empty($sortBy) ? $entity . 'Order' : 'order';
                            if (isset($_GET[$sortParamName])) {
                                $supOrder = explode('/', $_GET[$sortParamName]);
                                if (current($supOrder) == $origHeader) {
                                    $order = '/DESC';
                                    $img = '&darr;';
                                    if (count($supOrder) > 1 && $supOrder[1] == 'DESC') {
                                        $order = '';
                                        $img = '&uarr;';
                                    }
                                }
                            }
                            $header = '<a href="' .  \Env::get('cx')->getRequest()->getUrl() . '&' . $sortParamName . '=' . $origHeader . $order . '" style="white-space: nowrap;">' . $header . ' ' . $img . '</a>';
                        }
                        if ($this->hasMasterTableHeader) {
                            $this->setCellContents(1, $col, $header, 'td', 0);
                        } else {
                            $this->setCellContents(0, $col, $header, 'th', 0);
                        }
                    }
                    if (
                        isset($options['fields']) &&
                        isset($options['fields'][$origHeader]) &&
                        isset($options['fields'][$origHeader]['valueCallback']) &&
                        !empty($this->viewGenerator)
                    ) {
                        $valueCallback = $options['fields'][$origHeader]['valueCallback'];
                        $vgId = null;
                        if (
                            isset($options['functions']) &&
                            isset($options['functions']['vg_increment_number'])
                        ) {
                            $vgId = $options['functions']['vg_increment_number'];
                        }
                        try {
                            $data = \Cx\Core\Html\Controller\ViewGenerator::callCallbackByInfo(
                                $valueCallback,
                                array(
                                    'fieldvalue' => $data,
                                    'fieldname' => $origHeader,
                                    'rowData' => $rows,
                                    'fieldoption' => $options['fields'][$origHeader],
                                    'vgId' => $this->viewGenerator->getViewId(),
                                )
                            );
                        } catch (\Exception $e) {
                            \Message::add($e->getMessage(), \Message::CLASS_ERROR);
                        }
                    }

                    if (
                        isset($options['fields'][$origHeader]['editable']) &&
                        $this->editable &&
                        !in_array($origHeader, $status)
                    ) {
                        $data = $formGenerator->getDataElementWithoutType(
                            $origHeader,
                            $origHeader .'-'. $rowname,
                            0,
                            $data,
                            $options,
                            0
                        );

                        $encode = false;
                    }

                    /* We use json to do parse the field function. The 'else if' is for backwards compatibility so you can declare
                    * the function directly without using json. This is not recommended and not working over session */
                    if (
                        isset($options['fields']) &&
                        isset($options['fields'][$origHeader]) &&
                        isset($options['fields'][$origHeader]['table']) &&
                        isset($options['fields'][$origHeader]['table']['parse'])
                    ) {
                        $callback = $options['fields'][$origHeader]['table']['parse'];
                        $vgId = null;
                        if (
                            isset($options['functions']) &&
                            isset($options['functions']['vg_increment_number'])
                        ) {
                            $vgId = $options['functions']['vg_increment_number'];
                        }

                        try {
                            $data = \Cx\Core\Html\Controller\ViewGenerator::callCallbackByInfo(
                                $callback,
                                array(
                                    'data' => $data,
                                    'rows' => $rows,
                                    'options' => $options['fields'][$origHeader],
                                    'vgId' => $vgId,
                                )
                            );
                        } catch (\Exception $e) {
                            \Message::add($e->getMessage(), \Message::CLASS_ERROR);
                        }

                        $encode = false; // todo: this should be set by callback
                    } else if (in_array($origHeader, $status)) {
                        $statusField = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
                        $class = '';
                        if ((boolean)$data) {
                            $class = 'active';
                        }
                        $statusField->setAttributes(
                            array(
                                'class' => 'vg-function-status ' . $class,
                                'data-status-value' => $data,
                                'data-entity-id' => $rowname
                            )
                        );
                        $data = $statusField;
                        $encode = false;
                    } else if (is_object($data) && get_class($data) == 'DateTime') {
                        $data = $data->format(ASCMS_DATE_FORMAT);
                    } else if (isset($options['fields'][$origHeader]) && isset($options['fields'][$origHeader]['type']) && $options['fields'][$origHeader]['type'] == '\Country') {
                        $data = \Cx\Core\Country\Controller\Country::getNameById($data);
                        if (empty($data)) {
                            $data = \Cx\Core\Country\Controller\Country::getNameById(204);
                        }
                    } else if (gettype($data) == 'boolean') {
                        $data = '<i>' . ($data ? $_ARRAYLANG['TXT_YES'] : $_ARRAYLANG['TXT_NO']) . '</i>';
                        $encode = false;
                    } else if ($data === null) {
                        $data = '<i>' . $_ARRAYLANG['TXT_CORE_NONE'] . '</i>';
                        $encode = false;
                    } else if (empty($data)) {
                        $data = '<i>(empty)</i>';
                        $encode = false;
                    }
                    $cellAttrs = array();
                    if (
                        isset($options['fields']) &&
                        isset($options['fields'][$origHeader]['table']) &&
                        isset($options['fields'][$origHeader]['table']['attributes'])
                    ) {
                        $cellAttrs = $options['fields'][$origHeader]['table']['attributes'];
                    }
                    $this->setCellAttributes($row, $col, $cellAttrs);
                    $this->setCellContents($row, $col, $data, 'TD', 0, $encode);
                    $col++;
                }
                if ($this->hasRowFunctions($options['functions'], $virtual)) {
                    if ($first) {
                        $header = 'Functions';
                        if (isset($_ARRAYLANG['TXT_FUNCTIONS'])) {
                            $header = $_ARRAYLANG['TXT_FUNCTIONS'];
                        }
                        if ($this->hasMasterTableHeader) {
                            $this->setCellContents(1, $col, $header, 'td', 0, true);
                        } else {
                            $this->setCellContents(0, $col, $header, 'th', 0, true);
                        }
                    }

                    $this->updateColAttributes($col, array('style' => 'text-align:right;'));
                    if (empty($options['functions']['baseUrl'])) {
                        $options['functions']['baseUrl'] = clone \Env::get('cx')->getRequest()->getUrl();
                    }
                    $this->setCellContents($row, $col, $this->getFunctionsCode($rowname, $rows, $options['functions'], $virtual), 'TD', 0);
                }
                $first = false;
                $row++;
            }
            // adjust colspan of master-table-header-row
            $this->altRowAttributes(1 + $this->hasMasterTableHeader, array('class' => 'row1'), array('class' => 'row2'), true);
            if ($this->hasMasterTableHeader) {
                // now that the number of displayed columns is known:
                $headerColspan = $col;
                // we need to substract one if we have "overall functions"
                $headerColspan -= (int) (
                    isset($options['functions']) &&
                    isset($options['functions']['export']) &&
                    is_array($options['functions']['export'])
                );
                // we need to add one if there's an additional functions row
                $headerColspan += (int) $this->hasRowFunctions(
                    $options['functions']
                );
                $this->updateCellAttributes(
                    0, 
                    0, 
                    array(
                        'colspan' => $headerColspan
                    )
                );

                // prepare overall functions code
                $overallFunctionsCode = $this->getOverallFunctionsCode($options['functions'], $attrs);
                $this->setHeaderContents(0, $col, $overallFunctionsCode);
                $this->updateCellAttributes(0, $col, array('style' => 'text-align:right;'));
                $this->updateRowAttributes(1, array('class' => 'row3'), true);
            }
            // add multi-actions
            if (isset($options['multiActions'])) {
                $multiActionsCode = '
                    <img src="'.$cx->getCodeBaseCoreWebPath().'/Html/View/Media/arrow.gif" width="38" height="22" alt="^" title="^">
                    <a href="#" onclick="jQuery(\'input[type=checkbox]\').prop(\'checked\', true);return false;">' . $_ARRAYLANG['TXT_SELECT_ALL'] . '</a> /
                    <a href="#" onclick="jQuery(\'input[type=checkbox]\').prop(\'checked\', false);return false;">' . $_ARRAYLANG['TXT_DESELECT_ALL'] . '</a>
                    <img alt="-" title="-" src="'.$cx->getCodeBaseCoreWebPath().'/Html/View/Media/strike.gif">
                ';
                $multiActions = array(''=>$_ARRAYLANG['TXT_SUBMIT_SELECT']);
                foreach ($options['multiActions'] as $actionName=>$actionProperties) {
                    $actionTitle = $actionName;
                    if (isset($actionProperties['title'])) {
                        $actionTitle = $actionProperties['title'];
                    } else if (isset($_ARRAYLANG[$actionName])) {
                        $actionTitle = $_ARRAYLANG[$actionName];
                    }
                    if (isset($actionProperties['jsEvent'])) {
                        $actionName = $actionProperties['jsEvent'];
                    }
                    $multiActions[$actionName] = $actionTitle;
                }
                $select = new \Cx\Core\Html\Model\Entity\DataElement(
                    'cxMultiAction',
                    '',
                    \Cx\Core\Html\Model\Entity\DataElement::TYPE_SELECT,
                    null,
                    $multiActions
                );
                // this is not a nice place for this code
                // but we should cleanup this complete class and make
                // it base on templates
                $select->setAttribute(
                    'onchange',
                    '
                        var regex = /([a-zA-Z\/]+):([a-zA-Z\/]+)/;
                        var matches = jQuery(this).val().match(regex);
                        if (!matches) {
                            return false;
                        }
                        var checkboxes = jQuery(this).closest("table").find("input[type=checkbox]");
                        var activeRows = [];
                        checkboxes.filter(":checked").each(function(el) {
                            activeRows.push(jQuery(this).val());
                        });
                        cx.trigger(matches[1], matches[2], activeRows);
                        checkboxes.prop("checked", false);
                        jQuery(this).val("");
                    '
                );
                $this->setCellContents($row, 0, $multiActionsCode . $select, 'TD', 0);
                $this->setCellAttributes($row, 0, array('colspan' => $col + is_array($options['functions'])));
            }
            // adds custom attributes to row
            if (isset($options['rowAttributes'])) {
                $row = 1 + $this->hasMasterTableHeader;
                $callback = $options['rowAttributes'];
                foreach ($attrs as $rowname=>$rows) {
                    $originalAttributes = $this->getRowAttributes($row);
                    $data = $originalAttributes;
                    try {
                        $data = \Cx\Core\Html\Controller\ViewGenerator::callCallbackByInfo(
                            $callback,
                            array(
                                'data' => $rows,
                                'attributes' => $originalAttributes,
                            )
                        );
                    } catch (\Exception $e) {
                        \Message::add($e->getMessage(), \Message::CLASS_ERROR);
                    }
                    $this->updateRowAttributes($row, $data, true);
                    $row++;
                }
            }
            $attrs = array();
        }
        //add the sorting parameters as table attribute
        //if the row sorting functionality is enabled
        $className = 'adminlist';
        if (!empty($sortField)) {
            $className .= ' sortable';
            if (!empty($component)) {
                $attrs['data-component'] = $component;
            }
            if (!empty($entity)) {
                $attrs['data-entity'] = $entity;
            }
            if (!empty($sortOrder)) {
                $attrs['data-order'] = $sortOrder;
            }
            if (!empty($sortField)) {
                $attrs['data-field'] = $sortField;
            }
            if (isset($pagingPos)) {
                $attrs['data-pos'] = $pagingPos;
            }
            $attrs['data-object'] = 'Html';
            $attrs['data-act'] = 'updateOrder';
            if (    isset($sortBy['jsonadapter'])
                &&  !empty($sortBy['jsonadapter']['object'])
                &&  !empty($sortBy['jsonadapter']['act'])
            ) {
                $attrs['data-object'] = $sortBy['jsonadapter']['object'];
                $attrs['data-act']    = $sortBy['jsonadapter']['act'];
            }

            $cx->getComponent('Html')->whitelistParamSet(
                'updateOrder',
                array(),
                array(
                    'component' => $component,
                    'entity' => $entity,
                    'sortField' => $sortField,
                )
            );
        }

        if (!empty($status)) {
            $className .= ' status';
            $attrs['data-status-component'] = $statusComponent;
            $attrs['data-status-entity'] = $statusEntity;
            $attrs['data-status-field'] = $status['field'];

            $attrs['data-status-object'] = 'Html';
            $attrs['data-status-act'] = 'updateStatus';
            if (
                isset($status['jsonadapter']) &&
                !empty($status['jsonadapter']['object']) &&
                !empty($status['jsonadapter']['act'])
            ) {
                $attrs['data-status-object'] = $status['jsonadapter']['object'];
                $attrs['data-status-act']    = $status['jsonadapter']['act'];
            }

            $cx->getComponent('Html')->whitelistParamSet(
                'updateStatus',
                array(),
                array(
                    'component' => $statusComponent,
                    'entity' => $statusEntity,
                    'statusField' => $status['field'],
                )
            );
        }
        parent::__construct(array_merge($attrs, array('class' => $className, 'width' => '100%')));
    }

    /**
     * Override from parent. Added contrexx_raw2xhtml support
     * @param type $row
     * @param type $col
     * @param type $contents
     * @param type $type
     * @param type $body
     * @param type $encode
     * @return type
     */
    function setCellContents($row, $col, $contents, $type = 'TD', $body = 0, $encode = false)
    {
        if ($encode) {
            // 1->n & n->n relations
            $displayedRelationsLimit = 3;
            if (is_object($contents) && $contents instanceof \Doctrine\ORM\PersistentCollection) {
                // EXTRA_LAZY fetched can be sliced (results in a LIMIT)
                $contents = $contents->slice(0, $displayedRelationsLimit + 1);
            }
            if (is_array($contents)) {
                if (count($contents) > $displayedRelationsLimit) {
                    $contents = array_slice($contents, 0, $displayedRelationsLimit);
                    $contents[] = '...';
                }
                $contents = implode(', ', $contents);
            }
            //replaces curly brackets, so they get not parsed with the sigma engine
            $contents = preg_replace(array("/{/","/}/"), array("&#123;","&#125;"), contrexx_raw2xhtml($contents), -1);
        }
        $ret = $this->_adjustTbodyCount($body, 'setCellContents');
        if (PEAR::isError($ret)) {
            return $ret;
        }
        $ret = $this->_tbodies[$body]->setCellContents($row, $col, $contents, $type);
        if (PEAR::isError($ret)) {
            return $ret;
        }
    }

    protected function hasRowFunctions($functions, $virtual = false) {
        if (!is_array($functions)) {
            return false;
        }
        if (isset($functions['actions'])) {
            return true;
        }
        if (!$virtual && isset($functions['edit']) && $functions['edit']) {
            return true;
        }
        if (!$virtual && isset($functions['show']) && $functions['show']) {
            return true;
        }
        if (!$virtual && isset($functions['delete']) && $functions['delete']) {
            return true;
        }
        if (!$virtual && isset($functions['copy']) && $functions['copy']) {
            return true;
        }
        return false;
    }

    protected function getFunctionsCode($rowname, $rowData, $functions, $virtual = false ) {
        global $_ARRAYLANG;

        $baseUrl = $functions['baseUrl'];
        $code = '<span class="functions">';
        $editUrl = \Cx\Core\Html\Controller\ViewGenerator::getVgEditUrl(
            $functions['vg_increment_number'],
            $rowname,
            clone $baseUrl
        );
        $showUrl = clone $baseUrl;
        $params = $editUrl->getParamArray();
        if (isset($functions['sortBy']) && isset($functions['sortBy']['field'])) {
            $editUrl->setParam($functions['sortBy']['field'] . 'Pos', null);
        }
        $editId = '';
        if (!empty($params['editid'])) {
            $editId = $params['editid'] . ',';
            $showId = $params['editid'];
        }
        $editId .= '{' . $functions['vg_increment_number'] . ',' . $rowname . '}';

        /* We use json to do the action callback. So all callbacks are functions in the json controller of the
         * corresponding component. The 'else if' is for backwards compatibility so you can declare the function
         * directly without using json. This is not recommended and not working over session */
        try {
            if (isset($functions['actions']) ) {
                $code .= \Cx\Core\Html\Controller\ViewGenerator::callCallbackByInfo(
                    $functions['actions'],
                    array(
                        'rowData' => $rowData,
                        'editId' => $editId,
                    )
                );
            }
        } catch (\Exception $e) {
            \Message::add($e->getMessage(), \Message::CLASS_ERROR);
        }

        if(!$virtual){
            if (isset($functions['show']) && $functions['show']) {
                $showUrl->setParam('showid', $showId);
                //remove the parameter 'vg_increment_number' from editUrl
                //if the baseUrl contains the parameter 'vg_increment_number
                if (isset($params['vg_increment_number'])) {
                    \Html::stripUriParam($showUrl, 'vg_increment_number');
                }
                $code .= '<a href="' . $showUrl . '" class="show" title="'.$_ARRAYLANG['TXT_CORE_RECORD_SHOW_TITLE'].'"></a>';
            }
            if (isset($functions['copy']) && $functions['copy']) {
                $copyUrl = \Cx\Core\Html\Controller\ViewGenerator::getVgCopyUrl(
                    $functions['vg_increment_number'],
                    $rowname,
                    clone $baseUrl
                );
                //remove the parameter 'vg_increment_number' from actionUrl
                //if the baseUrl contains the parameter 'vg_increment_number'
                $params = $copyUrl->getParamArray();
                if (isset($params['vg_increment_number'])) {
                    \Html::stripUriParam($copyUrl, 'vg_increment_number');
                }
                $code = '<a href="'.$copyUrl.'" class="copy" title="'.$_ARRAYLANG['TXT_CORE_RECORD_COPY_TITLE'].'"></a>';
            }
            if (isset($functions['edit']) && $functions['edit']) {
                //remove the parameter 'vg_increment_number' from editUrl
                //if the baseUrl contains the parameter 'vg_increment_number'
                if (isset($params['vg_increment_number'])) {
                    \Html::stripUriParam($editUrl, 'vg_increment_number');
                }
                $code .= '<a href="' . $editUrl . '" class="edit" title="'.$_ARRAYLANG['TXT_CORE_RECORD_EDIT_TITLE'].'"></a>';
            }
            if (isset($functions['delete']) && $functions['delete']) {
                $deleteUrl = clone $baseUrl;
                $deleteUrl->setParam('deleteid', $rowname);
                $deleteUrl->setParam('vg_increment_number', $functions['vg_increment_number']);
                $deleteUrl.='&csrf='.\Cx\Core\Csrf\Controller\Csrf::code();
                $onclick ='if (confirm(\''.$_ARRAYLANG['TXT_CORE_RECORD_DELETE_CONFIRM'].'\'))'.
                    'window.location.replace(\''.$deleteUrl.'\');';
                if (!empty($functions['onclick']) &&
                    !empty($functions['onclick']['delete'])
                ) {
                    $onclick = $functions['onclick']['delete'].'(\''. $deleteUrl .'\')';
                }
                $_uri = 'javascript:void(0);';
                $code .= '<a onclick="'.$onclick.'" href="'.$_uri.'" class="delete" title="'.$_ARRAYLANG['TXT_CORE_RECORD_DELETE_TITLE'].'"></a>';
            }
        }
        return $code . '</span>';
    }

    /**
     * Returns HTML code for functions regarding all entries
     * @param array $functions Function config
     * @param Object $renderObject Currently rendered object
     * @return string HTML
     */
    protected function getOverallFunctionsCode($functions, $renderObject) {
        if (
            isset($functions['export']) &&
            is_array($functions['export']) &&
            $renderObject instanceof \Cx\Core_Modules\Listing\Model\Entity\DataSet
        ) {
            $_ARRAYLANG = \Env::get('init')->getComponentSpecificLanguageData(
                'Html',
                false
            );
            $adapter = 'Html';
            $method = 'export';
            if (
                isset($functions['export']['jsonadapter']) &&
                isset($functions['export']['jsonadapter']['adapter']) &&
                isset($functions['export']['jsonadapter']['method'])
            ) {
                $adapter = $functions['export']['jsonadapter']['adapter'];
                $method = $functions['export']['jsonadapter']['method'];
            }
            $exportFunc = new \Cx\Core\Html\Model\Entity\HtmlElement('a');
            $exportIcon = new \Cx\Core\Html\Model\Entity\HtmlElement('img');
            $exportIcon->setAttribute('src', '/core/Html/View/Media/export.png');
            $exportIcon->setAttribute('style', 'filter: invert(100%);');
            $exportFunc->addChild($exportIcon);
            $exportFunc->setAttributes(array(
                'data-adapter' => $adapter,
                'data-method' => $method,
                'data-object' => $renderObject->getDataType(),
                'title' => $_ARRAYLANG['TXT_CORE_HTML_EXPORT'],
            ));
            $exportFunc->addClass('vg-export');

            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $cx->getComponent('Html')->whitelistParamSet(
                'export',
                array('type' => $renderObject->getDataType())
            );
            return (string) $exportFunc;
        }
        return '';
    }

    /**
     * Returns an HTML formatted attribute string
     * Use Sigma for parsing
     * @param    array   $attributes
     * @return   string
     * @access   private
     */
    function _getAttrString($attributes)
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $template = new \Cx\Core\Html\Sigma($cx->getCodeBaseCorePath().'/Html/View/Template/Generic/');
        $template->loadTemplateFile('Attribute.html');

        $strAttr = '';

        if (is_array($attributes)) {
            $charset = HTML_Common::charset();
            foreach ($attributes as $key => $value) {
                $template->setVariable(array(
                    'ATTRIBUTE_NAME' => $key,
                    'ATTRIBUTE_VALUE' => htmlspecialchars($value, ENT_COMPAT, $charset),
                ));
                $template->parse('attribute');
            }
        }
        return ' ' . trim($template->get());
    } // end func _getAttrString

    /**
     * This is a soft override of Storage's toHtml()
     * in order to use Sigma for parsing
     */
    protected function parseStorage($template, $storage) {
        for ($i = 0; $i < $storage->_rows; $i++) {
            if (isset($storage->_structure[$i]['attr'])) {
                $template->setVariable('TR_ATTRIBUTES', $this->_getAttrString($storage->_structure[$i]['attr']));
            }
            for ($j = 0; $j < $storage->_cols; $j++) {
                $attr     = '';
                $contents = '';
                $type     = 'td';
                if (isset($storage->_structure[$i][$j]) && $storage->_structure[$i][$j] == '__SPANNED__') {
                    continue;
                }
                if (isset($storage->_structure[$i][$j]['type'])) {
                    $type = (strtolower($storage->_structure[$i][$j]['type']) == 'th' ? 'th' : 'td');
                }
                if (isset($storage->_structure[$i][$j]['attr'])) {
                    $attr = $storage->_structure[$i][$j]['attr'];
                }
                if (isset($storage->_structure[$i][$j]['contents'])) {
                    $contents = $storage->_structure[$i][$j]['contents'];
                }
                if (is_object($contents)) {
                    // changes indent and line end settings on nested tables
                    if (is_subclass_of($contents, 'html_common')) {
                        $contents->setTab($tab . $extraTab);
                        $contents->setTabOffset($storage->_tabOffset + 3);
                        $contents->_nestLevel = $storage->_nestLevel + 1;
                        $contents->setLineEnd($storage->_getLineEnd());
                    }
                    if (method_exists($contents, 'toHtml')) {
                        $contents = $contents->toHtml();
                    } elseif (method_exists($contents, 'toString')) {
                        $contents = $contents->toString();
                    }
                }
                if (is_array($contents)) {
                    $contents = implode(', ', $contents);
                }
                if (isset($storage->_autoFill) && $contents === '') {
                    $contents = $storage->_autoFill;
                }
                $template->setVariable(array(
                    'CELL_ATTRIBUTES' => $this->_getAttrString($attr),
                    'CELL_CONTENTS' => $contents,
                ));
                $template->parse('row_' . $type);
            }
            $template->parse('row');
        }
    }

    /**
     * Returns the table structure as HTML
     * Override in order to use Sigma for parsing
     * @access  public
     * @global  array $_ARRAYLANG array containing the language variables
     * @return  string
     */
    function toHtml()
    {
        global $_ARRAYLANG;

        $strHtml = '';
        $tabs = $this->_getTabs();
        $tab = $this->_getTab();
        $lnEnd = $this->_getLineEnd();
        $tBodyColCounts = array();
        for ($i = 0; $i < $this->_tbodyCount; $i++) {
            $tBodyColCounts[] = $this->_tbodies[$i]->getColCount();
        }
        $tBodyMaxColCount = 0;
        if (count($tBodyColCounts) > 0) {
            $tBodyMaxColCount = max($tBodyColCounts);
        }
        if ($this->_comment) {
            $strHtml .= $tabs . "<!-- $this->_comment -->" . $lnEnd;
        }
        $template = new \Cx\Core\Html\Sigma(dirname($this->templateFile));
        $template->loadTemplateFile(basename($this->templateFile));
        if ($this->getRowCount() > 0 && $tBodyMaxColCount > 0) {
            $template->setVariable('TABLE_ATTRIBUTES', $this->_getAttrString($this->_attributes));
            if (!empty($this->_caption)) {
                $attr = $this->_caption['attr'];
                $contents = $this->_caption['contents'];
                if (is_array($contents)) {
                    $contents = implode(', ', $contents);
                }
                $template->setVariable(array(
                    'CAPTION_ATTRIBUTES' => $this->_getAttrString($attr),
                    'CAPTION_CONTENTS' => $contents,
                ));
                $template->parse('caption');
            }
            if (!empty($this->_colgroup)) {
                foreach ($this->_colgroup as $g => $col) {
                    $attr = $this->_colgroup[$g]['attr'];
                    $contents = $this->_colgroup[$g]['contents'];
                    $template->setVariable(array(
                        'COLGROUP_ATTRIBUTES' => $this->_getAttrString($attr),
                    ));
                    if (!empty($contents)) {
                        $strHtml .= $lnEnd;
                        if (!is_array($contents)) {
                            $contents = array($contents);
                        }
                        foreach ($contents as $a => $colAttr) {
                            $attr = $this->_parseAttributes($colAttr);
                            $template->setVariable(array(
                                'COL_ATTRIBUTES' => $this->_getAttrString($attr),
                            ));
                            $template->parse('col');
                        }
                    }
                    $template->parse('colgroup');
                }
            }
            if ($this->_useTGroups) {
                $tHeadColCount = 0;
                if ($this->_thead !== null) {
                    $tHeadColCount = $this->_thead->getColCount();
                }
                $tFootColCount = 0;
                if ($this->_tfoot !== null) {
                    $tFootColCount = $this->_tfoot->getColCount();
                }
                $maxColCount = max($tHeadColCount, $tFootColCount, $tBodyMaxColCount);
                if ($this->_thead !== null) {
                    $this->_thead->setColCount($maxColCount);
                    if ($this->_thead->getRowCount() > 0) {
                        $template->setVariable(array(
                            'THEAD_ATTRIBUTES' => $this->_getAttrString($this->_thead->_attributes),
                        ));
                        $this->parseStorage($template, $this->_thead);
                        $template->touchBlock('thead_close');
                    }
                }
                if ($this->_tfoot !== null) {
                    $this->_tfoot->setColCount($maxColCount);
                    if ($this->_tfoot->getRowCount() > 0) {
                        $template->setVariable(array(
                            'TFOOT_ATTRIBUTES' => $this->_getAttrString($this->_tfoot->_attributes),
                        ));
                        $this->parseStorage($template, $this->_tfoot);
                        $template->touchBlock('tfoot_close');
                    }
                }
                for ($i = 0; $i < $this->_tbodyCount; $i++) {
                    $this->_tbodies[$i]->setColCount($maxColCount);
                    if ($this->_tbodies[$i]->getRowCount() > 0) {
                        $template->setVariable(array(
                            'TBODY_ATTRIBUTES' => $this->_getAttrString($this->_tbodies[$i]->_attributes),
                        ));
                        $this->parseStorage($template, $this->_tbodies[$i]);
                        $template->touchBlock('tbody_close');
                    }
                }
            } else {
                for ($i = 0; $i < $this->_tbodyCount; $i++) {
                    $this->parseStorage($template, $this->_tbodies[$i]);
                }
            }
        }

        if ($this->editable && $this->viewGenerator) {
            $template->setVariable('HTML_FORM_ACTION', contrexx_raw2xhtml(clone \Env::get('cx')->getRequest()->getUrl()));
            $template->setVariable('HTML_VG_ID', $this->viewGenerator->getViewId());
            $template->setVariable('TXT_HTML_SAVE', $_ARRAYLANG['TXT_SAVE_CHANGES']);

            $template->touchBlock('form_open');
            $template->touchBlock('form_close');
        }

        return $template->get();
    }
}
