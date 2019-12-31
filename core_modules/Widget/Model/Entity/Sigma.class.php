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
 * Temporary workaround: Sigma that does not remove untouched blocks and variables
 *
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage coremodules_widget
 */

namespace Cx\Core_Modules\Widget\Model\Entity;

/**
 * Temporary workaround: Sigma that does not remove untouched blocks and variables
 *
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage coremodules_widget
 */
class Sigma extends \Cx\Core\Html\Sigma
{

    /**
     * This sets other default values than in the parent class which ensure
     * that unparsed placeholders are not dropped
     */
    function setTemplate($template, $removeUnknownVariables = false, $removeEmptyBlocks = false)
    {
        return parent::setTemplate(
            $template,
            $removeUnknownVariables,
            $removeEmptyBlocks
        );
    }

    /**
     * This sets other default values than in the parent class which ensure
     * that unparsed placeholders are not dropped
     */
    function loadTemplateFile($filename, $removeUnknownVariables = false, $removeEmptyBlocks = false) {
        return parent::loadTemplateFile(
            $filename,
            $removeUnknownVariables,
            $removeEmptyBlocks
        );
    }

    /**
     * This is exactly the same as in the parent class except for the processing
     * of the inner blocks. This re-adds not yet parsed blocks. Changes are
     * marked with "BEGIN CHANGES ... END CHANGES"
     */
    function parse($block = '__global__', $flagRecursion = false, $fakeParse = false)
    {
        static $vars;

        if (!isset($this->_blocks[$block])) {
            return $this->raiseError($this->errorMessage(SIGMA_BLOCK_NOT_FOUND, $block), SIGMA_BLOCK_NOT_FOUND);
        }
        if ('__global__' == $block) {
            $this->flagGlobalParsed = true;
        }
        if (!isset($this->_parsedBlocks[$block])) {
            $this->_parsedBlocks[$block] = '';
        }
        $outer = $this->_blocks[$block];

        if (!$flagRecursion) {
            $vars = array();
        }
        // block is not empty if its local var is substituted
        $empty = true;
        foreach ($this->_blockVariables[$block] as $allowedvar => $v) {
            if (isset($this->_variables[$allowedvar])) {
                $vars[$this->openingDelimiter . $allowedvar . $this->closingDelimiter] = $this->_variables[$allowedvar];
                $empty = false;
                // vital for checking "empty/nonempty" status
                unset($this->_variables[$allowedvar]);
            }
        }

        // processing of the inner blocks
        if (isset($this->_children[$block])) {
            foreach ($this->_children[$block] as $innerblock => $v) {
                $placeholder = $this->openingDelimiter.'__'.$innerblock.'__'.$this->closingDelimiter;

                if (isset($this->_hiddenBlocks[$innerblock])) {
                    // don't bother actually parsing this inner block; but we _have_
                    // to go through its local vars to prevent problems on next iteration
                    $this->parse($innerblock, true, true);
                    unset($this->_hiddenBlocks[$innerblock]);
                    $outer = str_replace($placeholder, '', $outer);

                } else {
                    $this->parse($innerblock, true, $fakeParse);

                    // block is not empty if its inner block is not empty
                    // BEGIN CHANGES
                    // blocks are never empty as we want to keep all blocks
                    //if ('' != $this->_parsedBlocks[$innerblock]) {
                    $empty = false;
                    //}

                    $outer = str_replace(
                        $placeholder,
                        '<!-- BEGIN ' . $innerblock . ' -->' . $this->_parsedBlocks[$innerblock] . '<!-- END ' . $innerblock . ' -->',
                        $outer
                    );
                    // END CHANGES
                    $this->_parsedBlocks[$innerblock] = '';
                }
            }
        }

        // add "global" variables to the static array
        foreach ($this->_globalVariables as $allowedvar => $value) {
            if (isset($this->_blockVariables[$block][$allowedvar])) {
                $vars[$this->openingDelimiter . $allowedvar . $this->closingDelimiter] = $value;
            }
        }
        // if we are inside a hidden block, don't bother
        if (!$fakeParse) {
            if (0 != count($vars) && (!$flagRecursion || !empty($this->_functions[$block]))) {
                $varKeys     = array_keys($vars);
                $varValues   = $this->_options['preserve_data']
                               ? array_map(array(&$this, '_preserveOpeningDelimiter'), array_values($vars))
                               : array_values($vars);
            }

            // check whether the block is considered "empty" and append parsed content if not
            if (!$empty || '__global__' == $block
                || !$this->removeEmptyBlocks || isset($this->_touchedBlocks[$block])
            ) {
                // perform callbacks
                if (!empty($this->_functions[$block])) {
                    foreach ($this->_functions[$block] as $id => $data) {
                        $placeholder = $this->openingDelimiter . '__function_' . $id . '__' . $this->closingDelimiter;
                        // do not waste time calling function more than once
                        if (!isset($vars[$placeholder])) {
                            $args         = array();
                            $preserveArgs = !empty($this->_callback[$data['name']]['preserveArgs']);
                            foreach ($data['args'] as $arg) {
                                $args[] = (empty($varKeys) || $preserveArgs)
                                          ? $arg
                                          : str_replace($varKeys, $varValues, $arg);
                            }
                            if (isset($this->_callback[$data['name']]['data'])) {
                                $res = call_user_func_array($this->_callback[$data['name']]['data'], $args);
                            } else {
                                $res = isset($args[0])? $args[0]: '';
                            }
                            $outer = str_replace($placeholder, $res, $outer);
                            // save the result to variable cache, it can be requested somewhere else
                            $vars[$placeholder] = $res;
                        }
                    }
                }
                // substitute variables only on non-recursive call, thus all
                // variables from all inner blocks get substituted
                if (!$flagRecursion && !empty($varKeys)) {
                    $outer = str_replace($varKeys, $varValues, $outer);
                }

                $this->_parsedBlocks[$block] .= $outer;
                if (isset($this->_touchedBlocks[$block])) {
                    unset($this->_touchedBlocks[$block]);
                }
            }
        }
        return $empty;
    }
}
