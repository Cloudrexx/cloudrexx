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

    /**
     * List of callbacks to register for all instances
     * @see parent::setCallbackFunction()
     * @var array
     */
    protected static $callbackPlaceholders = array();

    protected $restoreFileRoot = null;

    /**
     * Target where this instance is parsed into
     * @var \Cx\Core\View\Model\Entity\ParseTarget
     */
    protected $parseTarget = null;

    /**
     * Adds a callback function to the list of callbacks for all instances
     * @param string $name Name of callback to register
     * @param callable $callback Callback to call for all occurences
     */
    public static function addCallbackPlaceholder($name, $callback) {
        static::$callbackPlaceholders[$name] = $callback;
    }

    /**
     * Removes a registered callback
     * @param string $name Name of callback to unregister
     */
    public static function removeCallbackPlaceholder($name) {
        unset(static::$callbackPlaceholders[$name]);
    }

    /**
     * Returns a list of callbacks registered for all instances
     * @return array List of callbacks ($name=>$callable)
     */
    public static function getCallbackPlaceholders() {
        return static::$callbackPlaceholders;
    }

    /**
     * Cx Sigma constructor
     * @param string $root      root directory for templates
     * @param string $cacheRoot directory to cache "prepared" templates in
     * @param \Cx\Core\View\Model\Entity\ParseTarget $parseTarget (optional) Target where this instance will get parsed into
     */
    public function __construct($root = '', $cacheRoot = '', $parseTarget = null) {
        parent::__construct($root, $cacheRoot);
        $this->parseTarget = $parseTarget;
        $this->removeVariablesRegExp = '@' . $this->openingDelimiter . '(' . $this->variablenameRegExp . ')\s*'
            . $this->closingDelimiter . '@sm';
        $this->setErrorHandling(PEAR_ERROR_DIE);

        // Add registered callbacks and ensure we also pass reference to $this
        foreach (static::getCallbackPlaceholders() as $name=>$callback) {
            $this->setCallbackFunction(
                $name,
                function() use ($callback) {
                    $args = func_get_args();
                    array_unshift($args, $this);
                    return call_user_func_array($callback, $args);
                },
                true
            );
        }
    }

    /**
     * Returns this instances parse target (might be null)
     * @return \Cx\Core\View\Model\Entity\ParseTarget Target where this instances will get parsed into (or null)
     */
    public function getParseTarget() {
        return $this->parseTarget;
    }

    /**
     * Sets the parse target after initialization
     * @deprecated Set parse target on initialization
     * @param \Cx\Core\View\Model\Entity\ParseTarget Target where this instances will get parsed into (or null)
     */
    public function setParseTarget($parseTarget) {
        $this->parseTarget = $parseTarget;
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

    /**
     * Triggers an event on setTemplate() and setTemplateFile()
     * @inheritDoc
     */
    function _buildBlocks($string) {
        $evm = \Cx\Core\Core\Controller\Cx::instanciate()->getEvents();
        if (!$evm) {
            return parent::_buildBlocks($string);
        }
        try {
            $isGlobal = strpos($string, '<!-- BEGIN __global__ -->') !== false;
            if ($isGlobal) {
                $string = str_replace(
                    array('<!-- BEGIN __global__ -->', '<!-- END __global__ -->'),
                    '',
                    $string
                );
            }
            $evm->triggerEvent(
                'View.Sigma:loadContent',
                array(
                    'content' => &$string,
                    'template' => $this,
                )
            );
            if ($isGlobal) {
                $string = '<!-- BEGIN __global__ -->' . $string .
                    '<!-- END __global__ -->';
            }
        } catch (\Exception $e) {
            throw $e;
        }
        return parent::_buildBlocks($string);
    }

    /**
     * Triggers an event on setGlobalVariable()
     * @inheritDoc
     */
    function setGlobalVariable($variable, $value = '') {
        $this->internalSetVariables($variable, $value);
        parent::setGlobalVariable($variable, $value);
    }

    /**
     * Triggers an event on setVariable()
     * @inheritDoc
     */
    function setVariable($variable, $value = '') {
        $this->internalSetVariables($variable, $value);
        parent::setVariable($variable, $value);
    }

    /**
     * Triggers events on setVariable() and setGlobalVariable()
     * @param string|array $variable Variable name or key/value array
     * @param string|array $value Value or key/value array for sub-keys
     */
    protected function internalSetVariables(&$variable, &$value = '') {
        $evm = \Cx\Core\Core\Controller\Cx::instanciate()->getEvents();
        if (!$evm) {
            return;
        }
        $variables = array();
        if (is_array($variable)) {
            $variables = $variable;
        } else if (is_array($value)) {
            $variables = $this->_flattenVariables($variable, $value);
        } else {
            $variables = array($variable => $value);
        }
        try {
            foreach ($variables as $key=>&$val) {
                $evm->triggerEvent(
                    'View.Sigma:setVariable',
                    array(
                        'content' => &$val,
                        'template' => $this,
                    )
                );
            }
        } catch (\Exception $e) {
            throw $e;
        }
        $variable = $variables;
        $value = '';
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

        // remove block from parent block
        foreach ($this->_children as &$children) {
            if (isset($children[$block])) {
                unset($children[$block]);
            }
        }

        // Renew variable list without dropping existing callbacks
        // This may lead to too much data in $this->_functions but
        // Sigma simply does str_replace() which never matches.
        $func_bkp = $this->_functions;
        $ret = $this->_buildBlockVariables();
        $this->_functions = $func_bkp + $this->_functions;
        return $ret;
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
            throw new \Exception('Reverse parsing of block "' . $blockName . '" failed');
        }
        return '<!-- BEGIN ' . $blockName . ' -->' .
            preg_replace_callback(
                '/\{__(' . $this->blocknameRegExp . ')__\}/',
                function(array $matches) use ($blockName) {
                    if (substr($matches[1], 0, 9) == 'function_') {
                        $info = $this->_functions[$blockName][substr($matches[1], 9)];
                        return 'func_' . $info['name'] . '(' . implode(',', $info['args']) . ')';
                    }
                    return $this->getUnparsedBlock($matches[1]);
                },
                $this->_blocks[$blockName]
            ) .
            '<!-- END ' . $blockName . ' -->';
    }
}
