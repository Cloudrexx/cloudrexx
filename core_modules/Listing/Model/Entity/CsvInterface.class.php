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
 * Csv interface
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_listing
 */

namespace Cx\Core_Modules\Listing\Model\Entity;

/**
 * Csv interface
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_listing
 */
class CsvInterface implements Exportable, Importable {
    protected $lineEnding;
    protected $separator;

    public function __construct($separator = ',', $lineEnding = "\n") {
        $this->lineEnding = $lineEnding;
        $this->separator = $separator;
    }

    /**
     * @todo Add "" around values containing separator
     * @todo Add support for different charsets, since CSV does not declare which one to use
     * Please note that Excel uses tab as delimiter if charset is unicode and semikolon for ANSI charsets
     * @param type $twoDimensionalArray
     * @return type
     */
    public function export($twoDimensionalArray) {
        $content = '';
        $keys = array_keys(current($twoDimensionalArray));
        unset($keys[array_search('virtual', $keys)]);
        $content .= implode($this->separator, $keys);
        $content .= $this->lineEnding;
        foreach ($twoDimensionalArray as $array) {
            unset($array['virtual']);
            $content .= implode($this->separator, $array);
            $content .= $this->lineEnding;
        }
        return $content;
    }

    /**
     * @todo Add "" around values containing separator
     * @todo Add support for different charsets, since CSV does not declare which one to use
     * Please note that Excel uses tab as delimiter if charset is unicode and semikolon for ANSI charsets
     * @param type $dataAsString
     * @return type
     */
    public function import($dataAsString) {
        $array = array();
        $array = explode($this->lineEnding, $dataAsString);
        foreach ($array as &$line) {
            $line = explode($this->separator, $line);
        }
        return $array;
    }
}
