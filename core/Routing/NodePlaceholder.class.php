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
 * NodePlaceholder class according to
 * http://www.cloudrexx.com/wiki/index.php/Development_Content#Node-URL_Notation
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @access      public
 * @version     3.1.0
 * @package     cloudrexx
 * @subpackage  core_routing
 */

namespace Cx\Core\Routing;

/**
 * Exception that is thrown if a NodePlaceholder cannot be initialized
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @access      public
 * @version     3.1.0
 * @package     cloudrexx
 * @subpackage  core_routing
 */
class NodePlaceholderException extends \Exception {}

/**
 * NodePlaceholder class according to
 * http://www.cloudrexx.com/wiki/index.php/Development_Content#Node-URL_Notation
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @access      public
 * @version     3.1.0
 * @package     cloudrexx
 * @subpackage  core_routing
 */
class NodePlaceholder {

    /**
     * Prefex used in placeholders for Node-Urls:
     * [[ NODE_(<node_id>|<module>[_<cmd>])[_<lang_id>] ]]
     */
    const PLACEHOLDER_PREFIX = 'NODE_';

    /**
     * Regular expression to match a node-url in placeholder notation
     */
    const NODE_URL_PCRE = '(
        # placeholder prefix
        NODE_
        (?:
            (?:
                # REFERENCE BY NODE-ID
                # node-id
                (\d+)
            |   # REFERENCE BY MODULE & CMD
                # module name
                ([A-Z1-9]+)
                # module cmd (optional)
                (?U)(?:_([-\w]+))?
            )
            # Language-id (optional)
            (?-U)(?:_(\d+))?
        )
     )';

    /**
     * Node Url regular expression back reference
     * index for the whole placeholder
     */
    const NODE_URL_PLACEHOLDER = 1;

    /**
     * Node Url regular expression back reference
     * index for the node id
     */
    const NODE_URL_NODE_ID = 2;

    /**
     * Node Url regular expression back reference
     * index for the module
     */
    const NODE_URL_MODULE = 3;

    /**
     * Node Url regular expression back reference
     * index for the module cmd
     */
    const NODE_URL_CMD = 4;

    /**
     * Node Url regular expression back reference
     * index for the language id
     */
    const NODE_URL_LANG_ID = 5;

    /**
     * Node this placeholder points to
     * @var Cx\Core\ContentManager\Model\Entity\Node
     */
    protected $node;

    /**
     * Language ID this placeholder points to or 0 if none specified
     * @var int
     */
    protected $lang;

    /**
     * Query arguments in the form array($key=>$value)
     * @var array
     */
    protected $arguments;

    /**
     * Create instance from string placeholder ([[NODE_...]] or {NODE_...})
     *
     * @param string $placeholder Any placeholder according to specification
     * @return \Cx\Core\Routing\NodePlaceholder
     * @throws NodePlaceholderException If format is not valid
     */
    public static function fromPlaceholder($placeholder) {
        $placeholder = preg_replace('/\\{/', '[[', $placeholder);
        $placeholder = preg_replace('/\\}/', ']]', $placeholder);
        $matches = array();

        if (!preg_match('/\[\['.self::NODE_URL_PCRE.'\]\](\S*)?/ix', $placeholder, $matches)) {
            throw new NodePlaceholderException('Invalid placeholder format: ' . $placeholder);
        }

        $nodeId      = empty($matches[self::NODE_URL_NODE_ID]) ? 0   : $matches[self::NODE_URL_NODE_ID];
        $module      = empty($matches[self::NODE_URL_MODULE])  ? ''  : $matches[self::NODE_URL_MODULE];
        $cmd         = empty($matches[self::NODE_URL_CMD])     ? ''  : $matches[self::NODE_URL_CMD];
        $langId      = empty($matches[self::NODE_URL_LANG_ID]) ? 0   : $matches[self::NODE_URL_LANG_ID];
        $queryString = empty($matches[6]) ? '' : $matches[6];

        return static::fromInfo($nodeId, $module, $cmd, $langId, $queryString);
    }

    /**
     * Create a placeholder for a page object
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Page to get placeholder for
     * @param array $arguments (optional) Query arguments in the form array($key=>$value)
     * @return \Cx\Core\Routing\NodePlaceholder
     */
    public static function fromPage(\Cx\Core\ContentManager\Model\Entity\Page$page, array $arguments = array()) {
        return new static ($page->getNode(), $page->getLang(), $arguments);
    }

    /**
     * Create a placeholder for a node object
     *
     * This is just a wrapper for the constructor
     * @param \Cx\Core\ContentManager\Model\Entity\Node $node Node to get placeholder for
     * @param int $lang (optional) Language ID or 0, default 0
     * @param array $arguments (optional) Query arguments in the form array($key=>$value)
     * @return \Cx\Core\Routing\NodePlaceholder
     */
    public static function fromNode(\Cx\Core\ContentManager\Model\Entity\Node $node, $lang = 0, array $arguments = array()) {
        return new static($node, $lang, $arguments);
    }

    /**
     * Create a placeholder based on informations in the form provided by Page->cutTarget()
     *
     * Specify at least a Node ID or a module name
     * @param int $nodeId (optional) Node ID
     * @param string $module (optional) Module name
     * @param string $cmd (optional) Module cmd
     * @param int $lang (optional) Language ID or 0
     * @param string $queryString (optional) Query arguments as string
     * @param boolean $ignoreErrors (optional) If this is set to true, a placeholder for an inexistent page can be created
     * @return \Cx\Core\Routing\NodePlaceholder
     * @throws NodePlaceholderException If not enough or unusable info is provided
     */
    public static function fromInfo($nodeId = 0, $module = '', $cmd = '', $lang = 0, $queryString = '', $ignoreErrors = false) {
        if (!$nodeId && empty($module)) {
            throw new NodePlaceholderException('You have to specify at least a node ID or a module name');
        }
        $em = \Env::get('cx')->getDb()->getEntityManager();
        $nodeRepo = $em->getRepository('Cx\Core\ContentManager\Model\Entity\Node');
        $pageRepo = $em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
        if ($nodeId) {
            $node = $nodeRepo->findOneById($nodeId);
        } else {
            $page = $pageRepo->findOneBy(array(
                'module' => $module,
                'cmd' => $cmd,
            ));
            if (!$page) {
                // if ignore errors: create virtual node (virtual nodes do not exist yet)
                throw new NodePlaceholderException('Could not find node for ' . $module . '/' . $cmd);
            }
            $node = $page->getNode();
        }
        if (!$node) {
            // if ignore errors: create virtual node (virtual nodes do not exist yet)
            throw new NodePlaceholderException('Could not find node');
        }
        $arguments = array();
        if (!empty($queryString)) {
            if (substr($queryString, 0, 1) == '?') {
                $queryString = substr($queryString, 1);
            }
            $parts = explode('&', $queryString);
            foreach ($parts as $part) {
                $part = explode('=', $part);
                if (!isset($part[1])) {
                    $part[1] = '';
                }
                $arguments[$part[0]] = $part[1];
            }
        }
        return new static($node, $lang, $arguments);
    }

    /**
     * Creates a new instance
     * @param \Cx\Core\ContentManager\Model\Entity\Node $node Node to create placeholder for
     * @param int $lang Language ID or 0
     * @param array $arguments Query arguments in the form array($key=>$value)
     */
    protected function __construct(\Cx\Core\ContentManager\Model\Entity\Node $node, $lang, array $arguments) {
        $this->node = $node;
        $this->lang = $lang;
        $this->arguments = $arguments;
    }

    /**
     * Returns the Node ID referenced by this placeholder
     * @return int Node ID
     */
    public function getNodeId() {
        return $this->getNode()->getId();
    }

    /**
     * Wheter this placeholder references an application page or not
     * @return boolean True if this placeholder references an application page, false otherwise
     */
    public function hasModule() {
        return $this->getModule() != '';
    }

    /**
     * The module name referenced by this placeholder
     * @return string Module name or empty string
     */
    public function getModule() {
        return $this->getPage()->getModule();
    }

    /**
     * Wheter this placeholder references an application page with a cmd or not
     * @return boolean True if this placeholder references an application page with a cmd, false otherwise
     */
    public function hasCmd() {
        return $this->hasModule() && $this->getCmd() != '';
    }

    /**
     * The module cmd referenced by this placeholder
     * @return string Module cmd or empty string
     */
    public function getCmd() {
        return $this->getPage()->getCmd();
    }

    /**
     * Wheter this placeholder references a specific language or not
     * @return boolean True if a specific language is referenced, false otherwise
     */
    public function hasLang() {
        return (bool) $this->lang;
    }

    /**
     * Language ID referenced by this placeholder
     * @return int Referenced language ID or FRONTEND_LANG_ID
     */
    public function getLangId() {
        if (!$this->lang) {
            return FRONTEND_LANG_ID;
        }
        return $this->lang;
    }

    /**
     * Sets the language to the supplied ID
     * @param int $langId Language ID to set
     */
    public function setLang($langId) {
        $this->lang = $langId;
    }

    /**
     * Removes language from this placeholder
     */
    public function removeLang() {
        $this->setLang(0);
    }

    /**
     * Returns the page referenced by this placeholder
     * @return \Cx\Core\ContentManager\Model\Entity\Page Referenced page
     */
    public function getPage() {
        return $this->getNode()->getPage($this->getLangId());
    }

    /**
     * Returns the node referenced by this placeholder
     * @return \Cx\Core\ContentManager\Model\Entity\Node Referenced node
     */
    public function getNode() {
        return $this->node;
    }

    /**
     * Wheter this placeholder includes query arguments or not
     * @return boolean True if query arguments are included in this placeholder, false otherwise
     */
    public function hasArguments() {
        return (bool) count($this->arguments);
    }

    /**
     * Returns the query arguments included in this placeholder
     * @return array Query arguments array($key=>$value) or empty array
     */
    public function getArguments() {
        return $this->arguments;
    }

    /**
     * Returns the Url pointing to the same location as this placeholder
     * @return \Cx\Core\Routing\Url Url pointing the same location as this placeholder
     */
    public function getUrl() {
        $url = \Cx\Core\Routing\Url::fromNode($this->node, $this->lang);
        $url->setParams($this->arguments);
        return $url;
    }

    /**
     * Returns the placeholder in the format specified in
     * http://www.cloudrexx.com/wiki/index.php/Development_Content#Node-URL_Notation
     * @param boolean $forceNodeId (optional) Wheter to force usage of node ID or not
     * @param boolean $parsedStyle (optional) Wheter to return template parsed format or not
     * @return string String placeholder
     */
    public function getPlaceholder($forceNodeId = false, $parsedStyle = false) {
        // PREFIX
        $placeholder = self::PLACEHOLDER_PREFIX;

        // NODE IDENTIFICATOR
        if ($this->hasModule() && !$forceNodeId) {
            // NODE_MODULE_CMD_LANG
            $placeholder .= $this->getModule();
            if ($this->hasCmd()) {
                $placeholder .= '_' . $this->getCmd();
            }
        } else {
            // NODE_NODEID_LANG
            $placeholder .= $this->getNodeId();
        }

        // LANGUAGE
        if ($this->hasLang()) {
            $placeholder .= '_' . $this->getLangId();
        }

        // COMMON
        $placeholder = strtoupper($placeholder);
        if ($parsedStyle) {
            $placeholder = '{' . $placeholder . '}';
        } else {
            $placeholder = '[[' . $placeholder . ']]';
        }

        // ARGUMENTS
        if ($this->hasArguments()) {
            $parts = array();
            foreach ($this->arguments as $key=>$value) {
                $parts[] = $key . '=' . $value;
            }
            $placeholder .= '?' . implode('&', $parts);
        }
        return $placeholder;
    }

    /**
     * Magig to string method
     * @see NodePlaceholder::getPlaceholder()
     * @return string String placeholder
     */
    public function __toString() {
        return $this->getPlaceholder();
    }
}
