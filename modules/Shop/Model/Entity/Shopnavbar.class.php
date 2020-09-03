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
 * Shopnavbar
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage modules_shop
 */

namespace Cx\Modules\Shop\Model\Entity;

/**
 * Shopnavbar
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage modules_shop
 */
class Shopnavbar extends \Cx\Core_Modules\Widget\Model\Entity\WidgetParseTarget {

    /**
     * Returns the name of the attribute which contains content that may contain a widget
     * @param string $widgetName
     * @return string Attribute name
     */
    protected function getWidgetContentAttributeName($widgetName) {
        return 'content';
    }

    public function getContent() {
        return file_get_contents('/var/www/html/themes/rexx/shopnavbar.html');
    }
}

