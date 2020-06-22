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
 * Output controller for cli output
 *
 * @copyright   Cloudrexx AG
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage core_modules_dataaccess
 */

namespace Cx\Core_Modules\DataAccess\Controller;

/**
 * Output controller for cli output
 *
 * @copyright   Cloudrexx AG
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage core_modules_dataaccess
 */
class CliOutputController extends OutputController {

    /**
     * @var Character for top left corner
     */
    const TOP_LEFT_CORNER = '+';

    /**
     * @var Character for right left corner
     */
    const TOP_RIGHT_CORNER = '+';

    /**
     * @var Character for top corners
     */
    const TOP_CORNER = '+';

    /**
     * @var Character for top line
     */
    const TOP = '-';

    /**
     * @var Character for left corners
     */
    const LEFT_CORNER = '+';

    /**
     * @var Character for left line
     */
    const LEFT = '|';

    /**
     * @var Character for line crossings
     */
    const MIDDLE_CORNER = '+';

    /**
     * @var Character for middle horizontal lines
     */
    const MIDDLE_HORIZONTAL = '-';

    /**
     * @var Character for middle vertical lines
     */
    const MIDDLE_VERTICAL = '|';

    /**
     * @var Character for right corners
     */
    const RIGHT_CORNER = '+';

    /**
     * @var Character for right line
     */
    const RIGHT = '|';

    /**
     * @var Character for bottom left corner
     */
    const BOTTOM_LEFT_CORNER = '+';

    /**
     * @var Character for bottom right corner
     */
    const BOTTOM_RIGHT_CORNER = '+';

    /**
     * @var Character for bottom corners
     */
    const BOTTOM_CORNER = '+';

    /**
     * @var Character for bottom line
     */
    const BOTTOM = '-';

    /**
     * @var Character to pad smaller values
     */
    const PAD = ' ';

    /**
     * @var Character before each value
     */
    const BEFORE_VALUE = ' ';

    /**
     * @var Character after each value
     */
    const AFTER_VALUE = ' ';

    /**
     * @var Character for line break
     */
    const LINE_END = "\n";

    /**
     * Returns the cli encoded (/unencoded) data
     * @param array $data Data to encode
     * @return string Encoded data
     */
    public function parse($data) {
        header('Content-Type: text/plain');
        if ($data['status'] == 'error') {
            return 'Error: ' . current($data['messages']['error']) . "\n";
        }
        $data = $data['data'];

        if (!is_array($data)) {
            switch (gettype($data)) {
                default:
                    var_export($data);
                    echo static::LINE_END;
                    return;
            }
        }

        if (!count($data)) {
            return '(Empty set)' . static::LINE_END;
        }
        if ($this->array_depth($data) == 1) {
            $data = array($data);
        }
        return $this->tablify($data);
    }

    /**
     * Detects the depth of an array
     * @param array $array Array to analyze
     * @return int Depth of $array
     */
    protected function array_depth(array $array) {
        $max_depth = 1;

        foreach ($array as $value) {
            if (is_array($value)) {
                $depth = $this->array_depth($value) + 1;

                if ($depth > $max_depth) {
                    $max_depth = $depth;
                }
            }
        }

        return $max_depth;
    }

    /**
     * Creates a human readable table with ASCII art
     * @todo add option to break long lines after some chars
     * @param array Table as array
     * @return string ASCII art table
     */
    protected function tablify($table) {
        $tableString = '';
        $fieldLength = array();

        // pre-calculate table:
        foreach ($table as $row) {
            foreach ($row as $caption=>$value) {
                if (
                    !isset($fieldLength[$caption]) ||
                    $fieldLength[$caption] < strlen($value)
                ) {
                    $fieldLength[$caption] = strlen($value);
                }
                if ($fieldLength[$caption] < strlen($caption)) {
                    $fieldLength[$caption] = strlen($caption);
                }
            }
        }

        // generate table:
        $tableString .= $this->createTableBorderLine($fieldLength, 'top');
        $tableString .= $this->createTableDataLine($fieldLength, array_keys($fieldLength));

        foreach ($table as $row) {
            $tableString .= $this->createTableBorderLine($fieldLength, 'middle');
            $tableString .= $this->createTableDataLine($fieldLength, $row);
        }
        $tableString .= $this->createTableBorderLine($fieldLength, 'bottom');
        return $tableString;
    }

    /**
     * Creates one of the border lines (those without data)
     * @param array $fieldLength List of the size of the longest values for each attribute
     * @param string $position Either 'top', 'middle' or 'bottom'
     * @return string One table line without data
     */
    protected function createTableBorderLine($fieldLength, $position) {
        switch ($position) {
            case 'top':
                $beginCorner = static::TOP_LEFT_CORNER;
                $filler = static::TOP;
                $corner = static::TOP_CORNER;
                $endCorner = static::TOP_RIGHT_CORNER;
                break;
            case 'middle':
                $beginCorner = static::LEFT_CORNER;
                $filler = static::MIDDLE_HORIZONTAL;
                $corner = static::MIDDLE_CORNER;
                $endCorner = static::RIGHT_CORNER;
                break;
            case 'bottom':
                $beginCorner = static::BOTTOM_LEFT_CORNER;
                $filler = static::BOTTOM;
                $corner = static::BOTTOM_CORNER;
                $endCorner = static::BOTTOM_RIGHT_CORNER;
                break;
        }
        return $this->createTableLine(
            $beginCorner,
            $filler,
            $filler,
            $filler,
            $corner,
            $endCorner,
            $fieldLength,
            array()
        );
    }

    /**
     * Creates one of the data lines
     * @param array $fieldLength List of the size of the longest values for each attribute
     * @param array $data Data for this row
     * @return string One table line with data
     */
    protected function createTableDataLine($fieldLength, $data) {
        return $this->createTableLine(
            static::LEFT,
            static::BEFORE_VALUE,
            static::PAD,
            static::AFTER_VALUE,
            static::MIDDLE_VERTICAL,
            static::RIGHT,
            $fieldLength,
            $data
        );
    }

    /**
     * Creates a table line using the supplied signs
     * @param string $begin Character to begin the line with (must be of same length for each row!)
     * @param string $beforeValue Character to insert before value (must be of same length for each row!)
     * @param string $filler Padding character for smaller values (must be just one character!)
     * @param string $afterValue Character to insert after value (must be of same length for each row!)
     * @param string $corner Character to separate values (must be of same length for each row!)
     * @param string $end Character to end the line with (must be of same length for each row!)
     * @param array $length List of the size of the longest values for each attribute
     * @param array $data Data for this row
     */
    protected function createTableLine($begin, $beforeValue, $filler, $afterValue, $corner, $end, $lengths, $data) {
        $line = $begin;
        $i = 0;
        foreach ($lengths as $length) {
            $index = array_keys($data);
            $value = '';
            if (isset($index[$i]) && isset($data[$index[$i]])) {
                $value = $data[$index[$i]];
            }
            $line .= $beforeValue . str_pad($value, $length, $filler, STR_PAD_LEFT) . $afterValue . $corner;
            $i++;
        }
        $line = substr($line, 0, -1) . $end;
        return $line . static::LINE_END;
    }
}
