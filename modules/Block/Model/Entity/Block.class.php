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
 * Simple "dummy" representation of a block in order to get widget contents
 * This class might later be used as a doctrine entity
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage modules_block
 */

namespace Cx\Modules\Block\Model\Entity;

/**
 * Simple "dummy" representation of a block in order to get widget contents
 * This class might later be used as a doctrine entity
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage modules_block
 */
class Block extends \Cx\Core_Modules\Widget\Model\Entity\WidgetParseTarget {
    /**
     * Block ID
     *
     * @var int
     */
    protected $id;

    /**
     * Block content per language
     * @var array
     */
    protected $content;

    /**
     * Creates a new block entity used for WidgetParseTarget
     * @param int $blockId Block ID
     */
    public function __construct($blockId) {
        $this->id = $blockId;
    }

    /**
     * Returns this block's ID
     * @return int Block ID
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Returns this block's content. Required WidgetParseTarget getter
     * @param int $langId Internal language/locale ID
     * @return string Block content
     */
    public function getContent($langId) {
        if (!isset($this->content[$langId])) {
            $query = '
                SELECT
                    `content`
                FROM
                    `' . DBPREFIX . 'module_block_rel_lang_content`
                WHERE
                    `block_id` = ' . $this->getId() . '
                    AND `lang_id` = ' . $langId . '
            ';
            $result = $this->cx->getDb()->getAdoDb()->execute($query);
            if (!$result) {
                throw new \Exception('Could not fetch content for block #' . $this->getId() . ' and lang #' . $langId);
            }
            $this->content[$langId] = $result->fields['content'];
        }
        return $this->content[$langId];
    }

    /**
     * Returns the name of the attribute which contains content that may contain a widget
     * @param string $widgetName
     * @return string Attribute name
     */
    public function getWidgetContentAttributeName($widgetName) {
        return 'content';
    }
}
