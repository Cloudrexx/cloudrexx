<?php
// +----------------------------------------------------------------------+
// | PEAR :: DB_NestedSet_Node                                              |
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
// | Authors: Daniel Khan <dk@webcluster.at>                              |
// +----------------------------------------------------------------------+

// $Id: Node.php 199224 2005-10-25 23:39:53Z datenpunk $


// {{{ DB_NestedSet_Node:: class
/**
* Generic class for node objects
*
* @autor Daniel Khan <dk@webcluster.at>;
* @version $Revision: 199224 $
* @package DB_NestedSet
* @access private
*/

class DB_NestedSet_Node {
    // {{{ constructor
    public function __construct($data) {
        $this->DB_NestedSet_Node($data);
    }
    /**
    * Constructor
    */
    function DB_NestedSet_Node($data) {
        if (!is_array($data) || count($data) == 0) {
            return new PEAR_ERROR($data, NESE_ERROR_PARAM_MISSING);
        }

        $this->setAttr($data);
        return true;
    }
    // }}}
    // {{{ setAttr()
    function setAttr($data) {
        if (!is_array($data) || count($data) == 0) {
            return false;
        }

        foreach ($data as $key => $val) {
            $this->$key = $val;
        }
    }
    // }}}
}
// }}}
?>
