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
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_html
 */

namespace Cx\Core\Html\Controller;

/**
 * Creates a table view
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_html
 */
class TableGenerator extends \BackendTable
{
    /**
     * Overwrites the constructor to convert the $attr array into a dataset to
     * flip it and show the attributes besides the values.
     *
     * @param $attrs   array attributes and values
     * @param $options array options for view generator
     * @param boolean $readOnly if view is only readable
     */
    public function __construct($attrs = array(), $options = array(), $readOnly = false)
    {
        // Rename Key Fields
        foreach ($attrs as $rowname=>$row) {
            // If the variable is not set, the field should be
            // displayed by default.
            if (
                isset($options['fields'][$rowname]['show']['show']) &&
                !$options['fields'][$rowname]['show']['show']
            ) {
                continue;
            }

            if (
                $readOnly &&
                isset($options['fields']) &&
                isset($options['fields'][$rowname]) &&
                isset($options['fields'][$rowname]['show']) &&
                isset($options['fields'][$rowname]['show']['parse'])
            ) {
                $callback = $options['fields'][$rowname]['show']['parse'];
                if (
                    is_array($callback) &&
                    isset($callback['adapter']) &&
                    isset($callback['method'])
                ) {
                    $json = new \Cx\Core\Json\JsonData();
                    $jsonResult = $json->data(
                        $callback['adapter'],
                        $callback['method'],
                        array(
                            'value' => $row,
                            'entity' => $attrs,
                            'options' => $options['fields'][$rowname],
                        )
                    );
                    if ($jsonResult['status'] == 'success') {
                        $data = $jsonResult["data"];
                    }
                } else if (is_callable($callback)) {
                    $data = $callback(
                        $row,
                        $attrs,
                        $options['fields'][$rowname]
                    );
                }
            } else {
                $data = $row;
            }
            if (!empty($options['fields'][$rowname]['show']['encode'])) {
                $data = $this->encodeCellContent($data);
            }
            $rows[$rowname] = $data;
        }

        // Check if lines exist to avoid unwanted errors
        if (!empty($rows)) {
            $data = new \Cx\Core_Modules\Listing\Model\Entity\DataSet(
                array('key' => array_keys($rows), 'value' => array_values($rows))
            );
        } else {
            $data = new \Cx\Core_Modules\Listing\Model\Entity\DataSet();
        }

        $options['fields']['key']['sorting'] = false;
        $options['fields']['value']['sorting'] = false;
        // After the flip(), the options can no longer be assigned to the rows.
        // But to get the options they are passed with use().
        // Therefore a PHP callback function is used and not a JsonAdapter.
        $options['fields']['key']['table']['parse'] = function ($rowname) use ($options) {
            global $_ARRAYLANG;
            $newRowName = $rowname;

            if (!empty($options['fields'][$rowname]['show']['header'])) {
                $newRowName = $options['fields'][$rowname]['show']['header'];
            } else if (!empty($options['fields'][$rowname]['header'])) {
                $newRowName = $options['fields'][$rowname]['header'];
            } else if (isset($_ARRAYLANG[$rowname])) {
                $newRowName = $_ARRAYLANG[$rowname];
            }
            return $newRowName;
        };
        unset($options['functions']);
        unset($options['multiActions']);
        unset($options['tabs']);

        $data = $data->flip();
        parent::__construct($data, $options, true, null, $readOnly);
    }
}
