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
 * Sigma
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_html
 */

namespace Cx\Core\Html;

/**
 * Description of Sigma
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Michael Ritter <michael.ritter@comvation.com>
 * @author Thomas Däppen <thomas.daeppen@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_html
 */
class Sigma extends \HTML_Template_Sigma {

    protected $restoreFileRoot = null;

    public function __construct($root = '', $cacheRoot = '') {
        parent::__construct($root, $cacheRoot);
        $this->removeVariablesRegExp = '@' . $this->openingDelimiter . '(' . $this->variablenameRegExp . ')\s*'
            . $this->closingDelimiter . '@sm';
        $this->setErrorHandling(PEAR_ERROR_DIE);
    }

    function getRoot() {
        return $this->fileRoot;
    }

    function loadTemplateFile($filename, $removeUnknownVariables = true, $removeEmptyBlocks = true) {
        $this->mapCustomizing($filename);
        $return = parent::loadTemplateFile($filename, $removeUnknownVariables, $removeEmptyBlocks);
        $this->unmapCustomizing();
        return $return;
    }

    function addBlockfile($placeholder, $block, $filename) {
        $this->mapCustomizing($filename);
        $return = parent::addBlockfile($placeholder, $block, $filename);
        $this->unmapCustomizing();
        return $return;
    }

    function replaceBlockfile($block, $filename, $keepContent = false) {
        $this->mapCustomizing($filename);
        $return = parent::replaceBlockfile($block, $filename, $keepContent);
        $this->unmapCustomizing();
        return $return;
    }

    function replaceBlock($block, $template, $keepContent = false, $outer = false) {
        if (!$outer) {
            return parent::replaceBlock($block, $template, $keepContent);
        }

        // ensure placeholder is not in $template
        $matches = array();
        if (
            preg_match(
                $this->blockRegExp,
                $template,
                $matches
            ) &&
            $matches[1] == $block
        ) {
            $template = $matches[2];
        }

        // replace block placeholder
        $placeholder = $this->openingDelimiter.'__'.$block.'__'.$this->closingDelimiter;
        foreach ($this->_blocks as $outerBlock=>&$content) {
            $content = str_replace(
                $placeholder,
                $template,
                $content
            );
        }

        // remove block
        $this->_removeBlockData($block, false);

        // Renew variable list
        return $this->_buildBlockVariables();
    }

    /**
     * The customizing mechanism does not apply to method _getCached().
     * Therefore it is not overwritten.
     */
    /** function _getCached($filename, $block = '__global__', $placeholder = '') {} */

    /**
     * Detects if $filename is customized.
     * If so, it causes \HTML_Template_Sigma to load the customized version
     * of the file.
     * @param   string $filename    The filename passed by the overwritten methods of \HTML_Template_Sigma
     */
    protected function mapCustomizing($filename) {
        // check if template is customized
        $filePath = \Env::get('ClassLoader')->getFilePath($this->fileRoot . $filename);
        if ($filePath != $this->fileRoot . $filename) {
            // backup original fileRoot
            $this->restoreFileRoot = $this->fileRoot;

            // point fileRoot to customizing path
            $newFileRoot = substr($filePath, 0, -strlen($filename));
            $this->fileRoot = $newFileRoot;
        }
    }

    /**
     * In case a customized version of a file has been loaded.
     * This method does revert \HTML_Template_Sigma so that is will
     * continue to load regular files without customizings.
     */
    protected function unmapCustomizing() {
        if ($this->restoreFileRoot) {
            $this->fileRoot = $this->restoreFileRoot;
            $this->restoreFileRoot = null;
        }
    }

    /**
     * Check if the given block exist. If not then an error is logged.
     * Otherwise it preserves the block.
     *
     * @param    string      block name
     * @return   integer     SIGMA_OK on success, SIGMA_BLOCK_NOT_FOUND on failure
     */
    function touchBlock($block)
    {
        if (!$this->blockExists($block)) {
            \DBG::log('The SIGMA-Block ' . $block . ' does not exist');
            return SIGMA_BLOCK_NOT_FOUND;
        }
        return parent::touchBlock($block);
    }

    /**
     * Check if the given block exist. If not then an error is logged.
     * Otherwise it hides the block even if it is not "empty".
     *
     * Is somewhat an opposite to touchBlock().
     *
     * @param    string      block name
     * @return   integer     SIGMA_OK on success, SIGMA_BLOCK_NOT_FOUND on failure
     */
    function hideBlock($block)
    {
        if (!$this->blockExists($block)) {
            \DBG::log('The SIGMA-Block ' . $block . ' does not exist');
            return SIGMA_BLOCK_NOT_FOUND;
        }
        return parent::hideBlock($block);
    }

    /**
     * Check if the given block exist. If not then an error is logged.
     * Otherwise it sets the name of the current block: the block where variables are added
     *
     * @param    string      block name
     * @return   integer     SIGMA_OK on success, SIGMA_BLOCK_NOT_FOUND on failure
     */
    function setCurrentBlock($block = '__global__')
    {
        if (!$this->blockExists($block)) {
            \DBG::log('The SIGMA-Block ' . $block . ' does not exist');
            return SIGMA_BLOCK_NOT_FOUND;
        }
        return parent::setCurrentBlock($block);
    }

    /**
     * Check if the given block exist and if it exist the given block is parsed.
     * Otherwise an error is logged.
     *
     * @param    string    block name
     * @param    boolean   true if the function is called recursively (do not set this to true yourself!)
     * @param    boolean   true if parsing a "hidden" block (do not set this to true yourself!)
     * @return   boolean   true if block is not empty
     */
    function parse($block = '__global__', $flagRecursion = false, $fakeParse = false)
    {
        if (!$this->blockExists($block)) {
            \DBG::log('The SIGMA-Block ' . $block . ' does not exist');
            return false;
        }
        return parent::parse($block, $flagRecursion, $fakeParse);
    }

    /**
     * Returns an unparsed block (/as it was delivered)
     * This is useful for "reflection". This is used by ESI parsing.
     * @author Michael Ritter <michael.ritter@cloudrexx.com>
     * @param string $blockName Name of block to return
     * @throws \Exception Thrown if the block does not exist within this template
     * @return string Template content
     */
    function getUnparsedBlock($blockName) {
        if (!isset($this->_blocks[$blockName])) {
            throw new \Exception('Reverse parsing of block failed');
        }
        return '<!-- BEGIN ' . $blockName . ' -->' .
            preg_replace_callback(
                '/\{__(' . $this->blocknameRegExp . ')__\}/',
                function(array $matches) {
                    return $this->getUnparsedBlock($matches[1]);
                },
                $this->_blocks[$blockName]
            ) .
            '<!-- END ' . $blockName . ' -->';
    }
}
