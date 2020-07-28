<?php
//
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
// |          Arnaud Limbourg <arnaud@php.net>                            |
// +----------------------------------------------------------------------+
//
// $Id: GraphViz.php 155194 2004-04-02 00:15:06Z datenpunk $
//

require_once 'Image/GraphViz.php';

/**
 * A helper class to translate the data from a nested set table into a
 * GraphViz diagram using PEAR::Image_GraphViz developped by Sebastian Bergmann.
 *
 * Based on DB_NestedSet_TreeMenu to a very large extent.
 *
 * @author       Jason Rust <jrust@rustyparts.com>
 * @author       Arnaud Limbourg <arnaud@php.net>
 * @package      DB_NestedSet
 * @version      $Revision: 155194 $
 */
class DB_NestedSet_GraphViz extends DB_NestedSet_Output
{
    /**
     * @var array The current menu structure
     *
     * @access private
     */
    var $_structTreeMenu = false;

    function DB_NestedSet_GraphViz($params)
    {
        $this->_structTreeMenu = &$this->_createFromStructure($params);
    }

    /**
     * Creates an Image_GraphViz graph based off of the results from getAllNodes() method
     * of the DB_NestedSet class.  The needed parameters are:
     * <li>
     * 'structure' => the result from $nestedSet->getAllNodes(true)
     * 'nodeLabel' => the text to show in the box reprenting the node
     * </li>
     *
     * @access public
     * @return object An Image_GraphViz object
     */
    function &_createFromStructure($params)
    {
        // Basically we go through the array of nodes checking to see
        // if each node has children and if so recursing.  The reason this
        // works is because the data from getAllNodes() is ordered by level
        // so a root node will always be first, and sub children will always
        // be after them.
        if (!isset($params['graphViz'])) {
            $graph = &new Image_GraphViz();
        } else {
            $graph = &$params['graphViz'];
        }

        // always start at level 1
        if (!isset($params['currentLevel'])) {
            $params['currentLevel'] = 1;
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

            $graph->addNode(
                $node['id'],
                array('label' => $node[$params['nodeLabel']],
                      'shape' => 'box'
                )
            );

            // if the node has a parent then we add an arrow from the parent
            // to the child
            if ($node['parent'] != 0) {
                $graph->addEdge(
                    array($node['parent'] => $node['id']),
                    array('label' => $node['edgeLabel'])
                );
            }

            // see if it has children
            if (($node['r'] - 1) != $node['l']) {
                $children = array();
                // harvest all the children
                $tempStructure = $params['structure'];
                foreach ($tempStructure as $childKey => $childNode) {
                    if (!isset($childNode['hit']) &&
                    $childNode['l'] > $node['l'] &&
                    $childNode['r'] < $node['r'] &&
                    $childNode['rootid'] == $node['rootid']) {
                        // important that we assign it by reference here, so that when the child
                        // marks itself 'hit' the parent loops will know
                        $children[] = &$params['structure'][$childKey];
                    }
                }

                $recurseParams = $params;
                $recurseParams['structure'] = $children;
                $recurseParams['graphViz']  = &$graph;
                $recurseParams['currentLevel']++;
                $this->_createFromStructure($recurseParams);
            }
        }

        return $graph;
    }

    /**
     * Outputs the graph as an image
     *
     * @access public
     * @param  string absolute path to the dot command
     * @param  string output image format
     * @return void
     */
    function printTree($dot_command = null, $output_format = 'png')
    {
        if (!is_null($dot_command)) {
            $this->_structTreeMenu->dotCommand = $dot_command;
        }
        $this->_structTreeMenu->image($output_format);
    }
}
?>