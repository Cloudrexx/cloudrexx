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
 * Block
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_block
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Modules\Block\Controller;

/**
 * Block
 *
 * block module class
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_block
 */
class Block extends \Cx\Modules\Block\Controller\BlockLibrary
{
    public static function setBlocks(&$content, $page = null)
    {
        $config = \Env::get('config');

        $objBlock = new self();

        if (!is_array($content)) {
            $arrTemplates = array(&$content);
        } else {
            $arrTemplates = &$content;
        }

        $pageId = 0;
        if ($page) {
            $pageId = $page->getId();
        }

        foreach ($arrTemplates as &$template) {
            // Set blocks [[BLOCK_<ID>]]
            if (preg_match_all('/{'.$objBlock->blockNamePrefix.'([0-9]+)}/', $template, $arrMatches)) {
                $objBlock->setBlock($arrMatches[1], $template, $pageId);
            }

            // Set global block [[BLOCK_GLOBAL]]
            if (preg_match('/{'.$objBlock->blockNamePrefix.'GLOBAL}/', $template)) {
                $objBlock->setBlockGlobal($template, $pageId);
            }

            // Set category blocks [[BLOCK_CAT_<ID>]]
            if (preg_match_all('/{'.$objBlock->blockNamePrefix.'CAT_([0-9]+)}/', $template, $arrMatches)) {
                $objBlock->setCategoryBlock($arrMatches[1], $template, $pageId);
            }

            /* Set random blocks [[BLOCK_RANDOMIZER]], [[BLOCK_RANDOMIZER_2]],
                                 [[BLOCK_RANDOMIZER_3]], [[BLOCK_RANDOMIZER_4]] */
            if ($config['blockRandom'] == '1') {
                $placeholderSuffix = '';

                $randomBlockIdx = 1;
                while ($randomBlockIdx <= 4) {
                    if (preg_match('/{'.$objBlock->blockNamePrefix.'RANDOMIZER'.$placeholderSuffix.'}/', $template)) {
                        $objBlock->setBlockRandom($template, $randomBlockIdx, $pageId);
                    }

                    $randomBlockIdx++;
                    $placeholderSuffix = '_'.$randomBlockIdx;
                }
            }
        }
    }


    /**
    * Set block
    *
    * Parse a block
    *
    * @access public
    * @param array $arrBlocks
    * @param string &$code
    * @param int $pageId
    * @see blockLibrary::_setBlock()
    */
    function setBlock($arrBlocks, &$code, $pageId = 0)
    {
        foreach ($arrBlocks as $blockId) {
            $this->_setBlock(intval($blockId), $code, $pageId);
        }
    }


    /**
    * Set category block
    *
    * Parse a category block
    *
    * @access public
    * @param array $arrCategoryBlocks
    * @param string &$code
    * @param int $pageId
    * @see blockLibrary::_setBlock()
    */
    function setCategoryBlock($arrCategoryBlocks, &$code, $pageId = 0)
    {
        foreach ($arrCategoryBlocks as $blockId) {
            $this->_setCategoryBlock(intval($blockId), $code, $pageId);
        }
    }

    /**
    * Set block Random
    *
    * Parse a block Random
    *
    * @access public
    * @param array $arrBlocks
    * @param string &$code
    * @see blockLibrary::_setBlock()
    */
    function setBlockRandom(&$code, $id, $pageId = 0)
    {
        $this->_setBlockRandom($code, $id, $pageId);
    }

    function setBlockGlobal(&$code, $pageId = 0)
    {
        $this->_setBlockGlobal($code, $pageId);
    }
}
