<?php
// +----------------------------------------------------------------------+
// | PEAR :: DB_NestedSet_TreeMenu                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Jason Rust <jrust@rustyparts.com>                           |
// +----------------------------------------------------------------------+
// $Id: TreeMenu.php 204899 2006-01-10 08:41:13Z bate $
require_once ASCMS_LIBRARY_PATH.'/PEAR/HTML/TreeMenu.php';
// {{{ DB_NestedSet_TreeMenu:: class
/**
 * A helper class to translate the data from a nested set table into a HTML_TreeMenu object
 * so that it can be used to create a dynamic tree menu using the PEAR HTML_TreeMenu class.
 *
 * @see docs/TreeMenu_example.php
 * @author Jason Rust <jrust@rustyparts.com>
 * @package DB_NestedSet
 * @version $Revision: 204899 $
 * @access public
 */
// }}}
/**
 * DB_NestedSet_TreeMenu
 *
 * @package
 * @author daniel
 * @copyright Copyright (c) 2004
 * @version $Id: TreeMenu.php 204899 2006-01-10 08:41:13Z bate $
 * @access public
 **/
class DB_NestedSet_TreeMenu extends DB_NestedSet_Output {
    // {{{ properties
    /**
     *
     * @var array The current menu structure
     * @access private
     */
    var $_structTreeMenu = false;

    /**
     *
     * @var array Default field mappings
     * @access private
     */
    var $_paramDefaults = array('textField' => 'text',
        'linkField' => 'link',
        'iconField' => 'icon',
        'expandedIconField' => 'expandedIcon',
        'classField' => 'cssClass',
        'expandedField' => 'expanded',
        'linkTargetField' => 'linkTarget',
        'isDynamicField' => 'isDynamic',
        'ensureVisibleField' => 'ensureVisible'
        );
    // }}}
    // {{{ DB_NestedSet_TreeMenu
    /**
     * The constructor
     *
     * @param array $params The config parameters used for building the
     *                          tree.
     * @see _createFromStructure
     * @access public
     * @return void
     */
    function DB_NestedSet_TreeMenu($params) {
        $this->_structTreeMenu = & $this->_createFromStructure($params);
    }
    // }}}
    // {{{ _createFromStructure()
    /**
     * <pre>Creates a HTML_TreeMenu structure based off of the results
     * from getAllNodes() method of the DB_NestedSet class.
     * Note that these parameters may be added to the individual nodes
     * to control their behavior:
     * o 'ensureVisible' => (optional) Whether or not the field should be
     *                          forced as visible creating it such as 'icon'
     *                          or 'expandedIcon'
     * o 'events' => (optional) An array of any events to pass to the
     *                   node when creating it such as 'onclick' or
     *                   'onexpand'
     *
     * @param array $params The configuration parameters.  Available
     *                          params are:
     * o 'structure'            => [REQU] The result from $nestedSet->getAllNodes(true)
     * o 'textField'            => [REQU] The field in the table that has the text for node
     * o 'linkField'            => [REQU] The field in the table that has the link for the node
     * The following params are optional. Please refer to HTML_TreeMenu's manual.
     * The params are equal to the HTML_TreeMenu::Node properties without the 'Field' appended
     * o 'iconField'            => [OPT]
     * o 'expandedIconField'    => [OPT]
     * o 'classField'           => [OPT]
     * o 'expandedField'        => [OPT]
     * o 'linkTargetField'      => [OPT]
     * o 'isDynamicField'       => [OPT]
     * o 'ensureVisibleField'   => [OPT]
     * o 'options' => (optional) An array of any additional options to
     *                    pass to the node when it is created (i.e. icon,
     *                    class).  See HTML_TreeNode for the options)
     * </pre>
     * @access public
     * @return object A HTML_TreeMenu object
     */
    function & _createFromStructure($params) {
        // Basically we go through the array of nodes checking to see
        // if each node has children and if so recursing.  The reason this
        // works is because the data from getAllNodes() is ordered by level
        // so a root node will always be first, and sub children will always
        // be after them.
        if (!isset($params['treeMenu'])) {
            $treeMenu = & new HTML_TreeMenu();
        } else {
            $treeMenu = & $params['treeMenu'];
        }
        // always start at level 1
        if (!isset($params['currentLevel'])) {
            $params['currentLevel'] = 1;
        }
        // Set the default field mappings if not set in userland
        if (!isset($params['defaultsSet'])) {
            $this->_setParamDefaults($params);
        }
        // have to use a while loop here because foreach works on a copy of the array and
        // the child nodes are passed by reference during the recursion so that the parent
        // will know when they have been hit.
        reset($params['structure']);
        foreach ($params['structure'] as $key => $node) {
            // see if we've already been here before
            if (isset($node['hit'])) {
                continue;
            }
            // mark that we've hit this node
            $params['structure'][$key]['hit'] = $node['hit'] = true;

            $tag = array('text' => $node[$params['textField']],
                'link' => $node[$params['linkField']],
                'icon' => isset($node[$params['iconField']]) ? $node[$params['iconField']] : false,
                'expandedIcon' => isset($node[$params['expandedIconField']]) ? $node[$params['expandedIconField']] : false,
                'cssClass' => isset($node[$params['classField']]) ? $node[$params['classField']] : false,
                'expanded' => isset($node[$params['expandedField']]) ? $node[$params['expandedField']] : false,
                'linkTarget' => isset($node[$params['linkTargetField']]) ? $node[$params['linkTargetField']] : false,
                'isDynamic' => isset($node[$params['isDynamicField']]) ? $node[$params['isDynamicField']] : true,
                'ensureVisible' => isset($node[$params['ensureVisibleField']]) ? $node[$params['ensureVisibleField']] : false);

            $options = isset($params['options']) ? array_merge($params['options'], $tag) : $tag;
            
            foreach ($params['options'] as $key => $value) {
            	if ($options[$key] === false) {
            		$options[$key] = $value;
            	}
            }

            $events = isset($node['events']) ? $node['events'] : array();
            $parentNode = & $treeMenu->addItem(new HTML_TreeNode($options, $events));
            // see if it has children
            if (($node['r'] - 1) != $node['l']) {
                $children = array();
                // harvest all the children
                $tempStructure = $params['structure'];
                foreach ($tempStructure as $childKey => $childNode) {
                    if (!isset($childNode['hit']) && $childNode['l'] > $node['l'] && $childNode['r'] < $node['r'] && $childNode['rootid'] == $node['rootid']) {
                        // important that we assign it by reference here, so that when the child
                        // marks itself 'hit' the parent loops will know
                        $children[] = & $params['structure'][$childKey];
                    }
                }

                $recurseParams = $params;
                $recurseParams['structure'] = $children;
                $recurseParams['treeMenu'] = & $parentNode;
                $recurseParams['currentLevel']++;
                $this->_createFromStructure($recurseParams);
            }
        }

        return $treeMenu;
    }
    // }}}
    // {{{ printTree()
    /**
     * Print's the current tree using the output driver
     *
     * @access public
     */
    function printTree() {
        $options = $this->_getOptions('printTree');
        $tree = & new HTML_TreeMenu_DHTML($this->_structTreeMenu, $options);
        $tree->printMenu();
    }
    // }}}
    // {{{ printListbox()
    /**
     * Print's a listbox representing the current tree
     *
     * @access public
     */
    function printListbox() {
        $options = $this->_getOptions('printListbox');
        $listBox = & new HTML_TreeMenu_Listbox($this->_structTreeMenu, $options);
        $listBox->printMenu();
    }
    // }}}
    // {{{ tree_toHTML()
    /**
     * Returns the HTML for the DHTML-menu. This method can be
     * used instead of printMenu() to use the menu system
     * with a template system.
     *
     * @access public
     * @return string The HTML for the menu
     * @Author Emanuel Zueger
     */
    function tree_toHTML() {
        $options = $this->_getOptions('toHTML');
        $tree = & new HTML_TreeMenu_DHTML($this->_structTreeMenu, $options);
        return $tree->toHTML();
    }
    // }}}
    // {{{ listbox_toHTML()
    /**
     * Returns the HTML for the listbox. This method can be
     * used instead of printListbox() to use the menu system
     * with a template system.
     *
     * @access public
     * @return string The HTML for the listbox
     * @author Emanuel Zueger
     */
    function listbox_toHTML() {
        $options = $this->_getOptions('toHTML');
        $listBox = & new HTML_TreeMenu_Listbox($this->_structTreeMenu, $options);
        return $listBox->toHTML();
    }
    // }}}
    // {{{ _setParamDefaults()
    /**
     * DB_NestedSet_TreeMenu::_setParamDefaults()
     *
     * @param  $params Param array passed from userland
     * @return bool True on completion
     * @access private
     */
    function _setParamDefaults(& $params) {
        $defaults = $this->_paramDefaults;
        foreach($defaults AS $fieldName => $fieldAlias) {
            if (!isset($params[$fieldName])) {
                $params[$fieldName] = $fieldAlias;
            }
        }
        $params['defaultsSet'] = true;
        return true;
    }
    // }}}
}

?>
