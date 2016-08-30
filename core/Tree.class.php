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
 * Content Tree
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  core
 * @deprecated  Use PageTree instead
 * @todo        Edit PHP DocBlocks!
 */

/**
 * This class creates a tree structure as an indexed array object
 * content array provider
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author    Cloudrexx Development Team <info@cloudrexx.com>
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @access    public
 * @version    1.0.0
 * @package     cloudrexx
 * @deprecated  Use PageTree instead
 * @subpackage  core
 */
class ContentTree {

    /**
     * Dev.status / Public methods for Tree class:
     *   1 getTree()               Retrieves an indexed array of the nodes from top to bottom */
    var $table = array();
    var $node = array();
    var $tree = array();
    var $langId = 0;
    var $index = 0;
    var $em = null;

    /**
     * Constructor
     *
     */
    function __construct($langId = null) {
        global $_FRONTEND_LANGID;

        $this->em = \Env::get('em');

        if (!isset($langId)) {
            $langId = $_FRONTEND_LANGID;
        }
        $this->langId = $langId;
        $this->buildTree(/*$this->srcTree*/);
    }

    function convert($page, $alias) {
//TODO: this conversion is a hack. in the final dump, we'll have module names instead of ids in the module attribute.
//TODO: this means we will need to do exactly the opposite conversion (module2id)

        /*$m2i = array();
        $rs = $db->Query('SELECT id, name FROM '.DBPREFIX.'modules');
        if ($rs) {
            while(!$rs->EOF) {
                $m2i[$rs->fields['name']] = $rs->fields['id'];
                $rs->MoveNext();
            }
        }*/
        return array(
            'catname' => $page->getTitle(),
//TODO:
            'catid' => $page->getId(),
//TODO:
            'parcat' => 0,
            'node_id' => $page->getNode()->getId(),
            'displaystatus' => $page->getDisplay(),
            'cmd' => $page->getCmd(),
            'modulename' => $page->getModule(),
            //'moduleid' => $m2i[$page->getModule()],
            'lang' => $page->getLang(),
            'startdate' => $page->getStart(),
            'enddate' => $page->getEnd(),
            'protected' => $page->getProtection(),
            'type'  => $page->getType(),
//TODO:
            'frontend_access_id' => 0,
//TODO:
            'backend_access_id' => 0,
            'alias' => $alias
        );
    }

    function buildTree($node = null, $level = 0, $pathSoFar = '') {
        if (!$node) {
            $node = $this->em->getRepository('Cx\Core\ContentManager\Model\Entity\Node')->getRoot();
        }
        $nodes = $node->getChildren();
        foreach ($nodes as $node) {//$title => $entry) {
            $page = $node->getPage($this->langId);
            if (!$page) {
                continue;
            }
            $alias = $pathSoFar . $page->getSlug();
            $this->tree[$this->index] = $this->convert($page, $alias);
            $this->tree[$this->index]['level'] = $level;
            $this->index++;

            $this->buildTree($node, $level + 1, $alias . '/');
        }

        /*
          $list=$this->table[$parcat];
          foreach( $list AS $key => $data )
          {
          $this->tree[$this->index] =$list[$key];
          $this->tree[$this->index]['level']=$level;
          $this->index++;
          if ((isset($this->table[$key])) AND (($maxlevel>=$level+1) OR ($maxlevel==0)))
          {
          $this->buildTree($key,$maxlevel,$level+1);
          }
          }
         */
    }

    function getTree() {
        // $parcat is the starting parent id
        // optional $maxLevel is the maximum level, set to 0 to show all levels

        return $this->tree;
    }

}
