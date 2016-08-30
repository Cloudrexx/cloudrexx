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

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Cx\Core\Validate\Model\Entity;
/**
 * Description of RegexValidator
 *
 * @author ritt0r
 */
class RegexValidator extends Validator {
    protected $pattern;

    public function __construct($pattern) {
        $this->pattern = $pattern;
    }

    public function isValid($data) {
        return (boolean) preg_match($this->pattern, $data);
    }

    public function getValidatedData($data) {
        if (!$this->isValid($data)) {
            throw new ValidationException('Validation for data failed (' . get_class($this) . ')');
        }
        return $data;
    }

    public function getJavaScriptCode() {
        return '
            if (' . $this->pattern . '.test(jQuery(this).val())) {
                jQuery(this).removeClass(\'error\');
            } else {
                jQuery(this).addClass(\'error\');
            }
        ';
    }
}
