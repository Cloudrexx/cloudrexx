<?php
//
// +----------------------------------------------------------------------+
// | PEAR :: DB_NestedSet_CoolMenu                                        |
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
// | Authors: Andy Crain <apcrain@fuse.net>                               |
// +----------------------------------------------------------------------+
//
// $Id: CoolMenu.php 199215 2005-10-25 21:48:02Z datenpunk $
//

// {{{ DB_NestedSet_CoolMenu:: class

/**
* This class can be used to generate the data to build javascript popup menu
* from a DB_NestedSet node array.
* The Javascript part is done using the freely available CoolMenus
* available at http://www.dhtmlcentral.com/projects/coolmenus/.
* Currently version 4.0 is supported.
* Adapted largely from the DB_NestedSet_TigraMenu class by Daniel Khan.
*
* @author       Andy Crain <apcrain@fuse.net>
* @package      DB_NestedSet
* @access       public
*/
// }}}
class DB_NestedSet_CoolMenu extends DB_NestedSet_Output {
    // {{{{ properties

    /**
    * @var string The default/root name of the current menu.
    * @access private
    */
    var $_menuName = 'CMenu';

    /**
    * @var integer The depth of the current menu.
    * @access private
    */
    var $_levels = 1;

    /**
    * @var integer The level we started at
    * @access private
    */
    var $_levelOffset = false;


    /**
    * @var array The current menu structure
    * @access private
    */
    var $_structCoolMenu = false;

    /**
    * @var array The longest text for each level
    * @access private
    */
    var $_strlenByLevel = array();

    // }}}
    // {{{ DB_NestedSet_CoolMenu

    /**
    * Constructor
    *
    * @param array $params A hash with parameters needed by the class
    * @see _createFromStructure()
    * @return bool
    **/
    function &DB_NestedSet_CoolMenu($params) {
        if(isset($params['menu_id'])) $this->_menuName .= $params['menu_id'];
        $this->_structCoolMenu = $this->_createFromStructure($params);
        return true;
    }

    // }}}
    // {{{ _createFromStructure()



    /**
    * Creates the JavaScript array for CoolMenu
    * Initially this method was introduced for the TreeMenu driver by Jason Rust
    *
    * o 'structure' => the result from $nestedSet->getAllNodes(true)
    * o 'textField' => the field in the table that has the text for node
    * o 'linkField' => the field in the table that has the link for the node
    *
    * @access private
    * @return string The CoolMenu makeMenu() JS methods code.
    */
    function &_createFromStructure($params,$parent=null)
    {
        // Basically we go through the array of nodes checking to see
        // if each node has children and if so recursing.  The reason this
        // works is because the data from getAllNodes() is ordered by level
        // so a root node will always be first, and sub children will always
        // be after them.

        static $rootlevel;

        // variable init
        $coolMenu = '';

        // always start at level 1
        if (!isset($params['currentLevel'])) {
            $params['currentLevel'] = 1;
        }

        if (!isset($rootlevel)) {
            $rootlevel = $params['currentLevel'];
        }

        if (isset($params['coolMenu'])) {
            $coolMenu = $coolMenu.$params['coolMenu'];
        }

        if(!$this->_levelOffset) {
            $this->_levelOffset = $params['currentLevel'];
        }

        if($this->_levels < ($params['currentLevel']- $this->_levelOffset)) {
            $this->_levels = $params['currentLevel'] - $this->_levelOffset;
        }

        // have to use a while loop here because foreach works on a copy of the array and
        // the child nodes are passed by reference during the recursion so that the parent
        // will know when they have been hit.
        reset($params['structure']);
        foreach ($params['structure'] as $key => $node) {
            // see if we've already been here before
            if (isset($node['hit']) || $node['level'] < $params['currentLevel']) {
                continue;
            }

            // mark that we've hit this node
            $params['structure'][$key]['hit'] = $node['hit'] = true;

            // figure out max length for textfields, increase if necessary--NEEDED?
            if (empty($this->_strlenByLevel[$params['currentLevel'] - $this->_levelOffset]) ||
            strlen($node[$params['textField']]) > $this->_strlenByLevel[$params['currentLevel'] - $this->_levelOffset]) {
                $this->_strlenByLevel[$params['currentLevel'] - $this->_levelOffset] = strlen($node[$params['textField']]);
            };

            if($node['rootid'] == $node['id']){
                if ($node[$params['linkField']] == basename($_SERVER['PHP_SELF'])) {
                    $coolMenu = $coolMenu . $this->_menuName . ".makeMenu('item" . $node['id'] . "','','" . $node[$params['textField']] ."','" . $node[$params['linkField']] . "','','','','','','level0highlight_mouseout_style','level0highlight_mouseover_style')\n";
                } else {
                    $coolMenu = $coolMenu . $this->_menuName . ".makeMenu('item" . $node['id'] . "','','" . $node[$params['textField']] ."','" . $node[$params['linkField']] . "')\n";
                }
            } else {
                $coolMenu = $coolMenu . $this->_menuName . ".makeMenu('item" . $node['id'] . "','item" . $parent . "','" . $node[$params['textField']] . "','" . $node[$params['linkField']] . "')\n";
            }

            // see if it has children
            if (($node['r'] - 1) != $node['l']) {
                $children = array();
                // harvest all the children
                $tempStructure = $params['structure'];
                foreach ($tempStructure as $childKey => $childNode) {
                    if (!isset($childNode['hit']) &&
                    $node['rootid'] == $childNode['rootid'] &&
                    $node['l'] < $childNode['l'] &&
                    $node['r'] > $childNode['r'] &&
                    $childNode['level'] > $params['currentLevel']) {
                        // important that we assign it by reference here, so that when the child
                        // marks itself 'hit' the parent loops will know
                        $children[] =& $params['structure'][$childKey];
                    }
                }

                $recurseParams = $params;
                $recurseParams['structure'] = $children;
                $recurseParams['currentLevel']++;
                $coolMenu = $coolMenu.$this->_createFromStructure($recurseParams,$node['id']);
            }
        }
        return $coolMenu;
    }



    // }}}
    // {{{ _buildStyles()

    /**
    * Creates the JavaScript code which sets the styles for each level
    *
    * @access private
    * @param array $options Array of menu style and structure parameters.
    * @return string The CSS styles for the menu.
    */
    function _buildStyles($options)
    {
        $styles = false;
        //loop once for each level defined
        foreach ($options['levels'] as $level_id => $level_array) {
            $styles .= '/*Styles for level ' . $level_id . "*/\n";
            foreach($level_array as $array_type => $values){
                if($array_type == 'properties' && count($values)){
                    continue;
                }
                $styles .= '.level' . $level_id . $array_type . '{';
                foreach($values as $att_key => $att_value){
                    $styles .= $att_key . ':' . $att_value . '; ';
                }
                $styles .= "}\n";
            }
        }
        //this is required by CoolMenu--don't change!
        $styles = "<style type='text/css'>\n/* CoolMenus 4 - default styles - do not edit */\n.clCMAbs{position:absolute; visibility:hidden; left:0; top:0}\n/* CoolMenus 4 - default styles - end */\n" . $styles;

        //get background style too
        $styles .= $this->_buildBackgroundStyle($options['menu']['background_style']);
        $styles .= '</style>';
        return $styles;
    }



    /**
    * Creates the JavaScript code which sets the background style for the entire menu
    *
    * @access private
    * @param array $options Array of menu style parameters, i.e. $options['menu']['background_style'].
    * @return string The CSS style for the menu background.
    */
    function _buildBackgroundStyle($properties)
    {
        $background_style = "/*Background style*/\n.backgroundStyle{";
        foreach($properties as $prop_key => $prop_val){
            $background_style .= $prop_key . ':' . $prop_val . '; ';
        }
        $background_style .= "}\n";
        return $background_style;
    }



    /**
    * Creates the JavaScript code which sets properties for each level defined.
    *
    * @access private
    * @param array $options Array of menu style and structure parameters.
    * @return string The JS defining each menu level, e.g. CMenu3.level[0].height=25...
    */
    function _buildLevelProperties($options)
    {
        $level_properties = false;
        //loop once for each level defined
        foreach ($options['levels'] as $level_id => $level_array) {
            $level_properties .= "\n/*Level " . $level_id . " properties*/\n" . $this->_menuName . ".level[$level_id]=new cm_makeLevel()\n";
            if(count($level_array['mouseout_style'])){
                $level_properties .= $this->_menuName . '.level[' . $level_id . '].regClass="level' . $level_id . 'mouseout_style"' . "\n";
            }
            if(count($level_array['mouseover_style'])){
                $level_properties .= $this->_menuName . '.level[' . $level_id . '].overClass="level' . $level_id . 'mouseover_style"' . "\n";
            }
            if(count($level_array['border_style'])){
                $level_properties .= $this->_menuName . '.level[' . $level_id . '].borderClass="level' . $level_id . 'border_style"' . "\n";
            }
            if(count($level_array['properties'])){
                foreach($level_array['properties'] as $prop_key => $prop_val){
                    $level_properties .= $this->_menuName . ".level[$level_id].$prop_key=$prop_val\n";
                }
            }
        }
        return $level_properties;
    }


    /**
    * Creates the JavaScript code which sets properties for each level defined.
    *
    * @access private
    * @param array $options Array of menu structure parameters ($options['menu']['properties']).
    * @return string The JS defining menu structure, e.g. CMenu3.zIndex=100...
    */
    function _buildMenuProperties($properties)
    {
        // variable init
        $menu_properties = '';
        foreach($properties as $prop_key => $prop_val){
            $menu_properties .= $this->_menuName . ".$prop_key=$prop_val\n";
        }
        $menu_properties .= $this->_menuName . ".barClass=\"backgroundStyle\"\n";
        return "\n/*Menu properties*/\n$menu_properties\n";
    }


    // }}}
    // {{{ printTree()

    /**
    * Print's the current tree using the output driver
    *
    * @access public
    * @param void
    * @return string The complete JS for the menu.
    */
    function printTree()
    {
        if (!$options = $this->_getOptions('printTree')) {
            return PEAR::raiseError("CoolMenu::printTree() needs options. See CoolMenu::setOptions()", NESEO_ERROR_NO_OPTIONS, PEAR_ERROR_TRIGGER, E_USER_ERROR);
        }
        echo $this->_buildStyles($options);
        echo "\n\n<script>\n" . $this->_menuName . "=new makeCM(\"" . $this->_menuName . "\")\n";
        echo $this->_buildMenuProperties($options['menu']['properties']);
        echo $this->_buildLevelProperties($options);
        echo "\n\n";
        echo $this->_structCoolMenu;
        echo "\n\n";
        echo $this->_menuName . ".construct()\n</script>";
    }

    // }}}
}
?>