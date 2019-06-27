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
 * Page
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_contentmanager
 */

namespace Cx\Core\ContentManager\Model\Entity;

use Cx\Core\ContentManager\Model\Repository\PageRepositoryException;
use Doctrine\ORM\EntityManager;

define('FRONTEND_PROTECTION', 1 << 0);
define('BACKEND_PROTECTION',  1 << 1);

/**
 * PageException
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_contentmanager
 */
class PageException extends \Exception {
    protected $userMessage = '';

    public function __construct($message, $userMessage = '', $code = 0, \Exception $previous = null) {
        $this->userMessage = $userMessage;
        parent::__construct($message, $code, $previous);
    }

    final function getUserMessage() {
        return $this->userMessage;
    }
}

/**
 * Page
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_contentmanager
 */
class Page extends \Cx\Core_Modules\Widget\Model\Entity\WidgetParseTarget implements \Serializable
{
    const TYPE_CONTENT = 'content';
    const TYPE_APPLICATION = 'application';
    const TYPE_REDIRECT = 'redirect';
    const TYPE_SYMLINK = 'symlink';
    const TYPE_FALLBACK = 'fallback';
    const TYPE_ALIAS = 'alias';

    /**
     * Prefex used in placeholders for Node-Urls:
     * [[ NODE_(<node_id>|<module>[_<cmd>])[_<lang_id>] ]]
     * @deprecated Use NodePlaceholder::... directly instead
     */
    const PLACEHOLDER_PREFIX = \Cx\Core\Routing\NodePlaceholder::PLACEHOLDER_PREFIX;

    /**
     * Regular expression to match a node-url in placeholder notation
     * @deprecated Use NodePlaceholder::... directly instead
     */
    const NODE_URL_PCRE = \Cx\Core\Routing\NodePlaceholder::NODE_URL_PCRE;

    /**
     * Node Url regular expression back reference
     * index for the whole placeholder
     * @deprecated Use NodePlaceholder::... directly instead
     */
    const NODE_URL_PLACEHOLDER = \Cx\Core\Routing\NodePlaceholder::NODE_URL_PLACEHOLDER;

    /**
     * Node Url regular expression back reference
     * index for the node id
     * @deprecated Use NodePlaceholder::... directly instead
     */
    const NODE_URL_NODE_ID = \Cx\Core\Routing\NodePlaceholder::NODE_URL_NODE_ID;

    /**
     * Node Url regular expression back reference
     * index for the module
     * @deprecated Use NodePlaceholder::... directly instead
     */
    const NODE_URL_MODULE = \Cx\Core\Routing\NodePlaceholder::NODE_URL_MODULE;

    /**
     * Node Url regular expression back reference
     * index for the module cmd
     * @deprecated Use NodePlaceholder::... directly instead
     */
    const NODE_URL_CMD = \Cx\Core\Routing\NodePlaceholder::NODE_URL_CMD;

    /**
     * Node Url regular expression back reference
     * index for the language id
     * @deprecated Use NodePlaceholder::... directly instead
     */
    const NODE_URL_LANG_ID = \Cx\Core\Routing\NodePlaceholder::NODE_URL_LANG_ID;

    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var integer $nodeIdShadowed
     */
    protected $nodeIdShadowed;

    /**
     * @var integer $lang
     */
    protected $lang;

    /**
     * @var string $title
     */
    protected $title;

    /**
     * @var text $content
     */
    protected $content;

    /**
     * Disables the WYSIWYG editor
     * @var boolean $sourceMode
     */
    protected $sourceMode;

    /**
     * @var string $customContent
     */
    protected $customContent;

    /**
     * @var integer $useCustomContentForAllChannels
     */
    protected $useCustomContentForAllChannels;

    /**
     * @var string $applicationTemplate
     */
    protected $applicationTemplate;

    /**
     * @var integer $useCustomApplicationTemplateForAllChannels
     */
    protected $useCustomApplicationTemplateForAllChannels;

    /**
     * @var string $cssName
     */
    protected $cssName;

    /**
     * @var string $metatitle
     */
    protected $metatitle;

    /**
     * @var string $metadesc
     */
    protected $metadesc;

    /**
     * @var string $metakeys
     */
    protected $metakeys;

    /**
     * @var string $metarobots
     */
    protected $metarobots;

    /**
     * @var string $metaimage
     */
    protected $metaimage;

    /**
     * @var date $start
     */
    protected $start;

    /**
     * @var date $end
     */
    protected $end;

    /**
     * @var boolean $editingStatus
     */
    protected $editingStatus;

    /**
     * @var boolean $display
     */
    protected $display;

    /**
     * @var boolean $active
     */
    protected $active;

    /**
     * @var string $target
     */
    protected $target;

    /**
     * @var integer $module
     */
    protected $module;

    /**
     * @var string $cmd
     */
    protected $cmd;

    /**
     * @var Cx\Core\ContentManager\Model\Entity\Node
     */
    protected $node;

    /**
     * @var int $slugSuffix
     */
    protected $slugSuffix = 0;

    /**
     * @var int $slugBase
     */
    protected $slugBase = '';

    /**
     * @var Cx\Core\ContentManager\Model\Entity\Skin
     */
    protected $skin;

    /**
     * @var integer $useSkinForAllChannels
     */
    protected $useSkinForAllChannels;

    /**
     * @var string $type
     */
    protected $type;
    /**
     * @var datetime $updatedAt
     */
    protected $updatedAt;

    /**
     * @var string $slug
     */
    protected $slug;

    /**
     * @var string $contentTitle
     */
    protected $contentTitle;

    /**
     * @var string $linkTarget
     */
    protected $linkTarget;

    /**
     * @var integer $frontendAccessId
     */
    protected $frontendAccessId;

    /**
     * @var integer $backendAccessId
     */
    protected $backendAccessId;

    /**
     * @var integer $protection
     */
    protected $protection;

    /**
     * @var string $cssNavName
     */
    protected $cssNavName;

    /**
     * @var string $updatedBy
     */
    protected $updatedBy;

    /**
     * @var boolean Tells wheter this is a virtual (non DB) page or not
     */
    protected $isVirtual = false;

    public function __construct() {
        //default values
        $this->type = 'content';
        $this->content = '';
        $this->metadesc = '';
        $this->metakeys = '';
        $this->metaimage = '';
        $this->editingStatus = '';
        $this->active = false;
        $this->display = true;
        $this->caching = false;
        $this->sourceMode = false;
        $this->updatedBy = '';

        $this->frontendAccessId = 0;
        $this->protection = 0;
        $this->backendAccessId = 0;

        $this->setUpdatedAtToNow();
    }

    /**
     * @inheritDoc
     */
    public function initializeValidators() {
        $this->validators = array(
            'lang' => new \CxValidateInteger(),
            'type' => new \CxValidateString(array('alphanumeric' => true, 'maxlength' => 255)),
            //caching is boolean, not checked
            'title' => new \CxValidateString(array('maxlength' => 255)),
            'customContent' => new \CxValidateString(array('maxlength' => 64)),
            'cssName' => new \CxValidateString(array('maxlength' => 255)),
            'metatitle' => new \CxValidateString(array('maxlength' => 255)),
            'metarobots' => new \CxValidateString(array('maxlength' => 255)),
            'metaimage' => new \CxValidateString(array('maxlength' => 255)),
            //'start' => maybe date? format?
            //'end' => maybe date? format?
            'editingStatus' => new \CxValidateString(array('maxlength' => 16)),
            //display is boolean, not checked
            //active is boolean, not checked
            'target' => new \CxValidateString(array('maxlength' => 255)),
            'module' => new \CxValidateString(array('alphanumeric' => true)),
            'cmd' => new \CxValidateRegexp(array(
                'pattern' => '/^([-a-z0-9_]+|\[\[' .
                    \Cx\Core\Routing\NodePlaceholder::NODE_URL_PCRE .
                    '\]\])$/ix'
            )),
        );
    }

    /**
     * Set id
     *
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set nodeIdShadowed
     *
     * @param integer $nodeIdShadowed
     */
    public function setNodeIdShadowed($nodeIdShadowed)
    {
        $this->nodeIdShadowed = $nodeIdShadowed;
    }

    /**
     * Get nodeIdShadowed
     *
     * @return integer $nodeIdShadowed
     */
    public function getNodeIdShadowed()
    {
        return $this->nodeIdShadowed;
    }

    /**
     * Set lang
     *
     * @param integer $lang
     */
    public function setLang($lang)
    {
        $this->lang = $lang;
    }

    /**
     * Get lang
     *
     * @return integer $lang
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * Set title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $wasEmpty = $this->getSlug() == '';

        $this->title = $title;

        if($wasEmpty)
            $this->refreshSlug();

        if($this->getContentTitle() == '')
            $this->setContentTitle($this->title);
    }

    /**
     * Sets a correct slug based on the current title.
     * The result may need a suffix if titles of pages on sibling nodes
     * result in the same slug.
     */
    protected function refreshSlug() {
        $slug = $this->slugify($this->getTitle());
        $this->setSlug($slug);
    }

    protected function slugify($string) {
        return $this->cx->getComponent('Model')->slugify($string);
    }

    public function nextSlug() {
        $slug = '';
        if (!empty($this->slugBase)) {
            $slug .= $this->slugBase . '-';
        }
        $slug .= ++$this->slugSuffix;
        $this->setSlug($slug, true);
    }

    /**
     * Get title
     *
     * @return string $title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set content
     *
     * @param text $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Set sourceMode
     *
     * @param boolean $sourceMode
     */
    public function setSourceMode($sourceMode)
    {
        $this->sourceMode = $sourceMode;
    }

    /**
     * Get content
     *
     * @return text $content
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Get sourceMode
     *
     * @return boolean $sourceMode
     */
    public function getSourceMode()
    {
        return $this->sourceMode;
    }


    /**
     * Set customContent
     *
     * @param string $customContent
     */
    public function setCustomContent($customContent)
    {
        $this->customContent = $customContent;
    }

    /**
     * Get customContent
     *
     * @return string $customContent
     */
    public function getCustomContent()
    {
        return $this->customContent;
    }

    /**
     * Get useCustomContentForAllChannels
     *
     * @return integer $useCustomContentForAllChannels
     */
    public function setUseCustomContentForAllChannels($useCustomContentForAllChannels)
    {
        $this->useCustomContentForAllChannels = $useCustomContentForAllChannels;
    }

    /**
     * Get useCustomContentForAllChannels
     *
     * @return integer $useCustomContentForAllChannels
     */
    public function getUseCustomContentForAllChannels()
    {
        return $this->useCustomContentForAllChannels;
    }

    /**
     * Set applicationTemplate
     *
     * @param string $applicationTemplate
     */
    public function setApplicationTemplate($applicationTemplate)
    {
        $this->applicationTemplate = $applicationTemplate;
    }

    /**
     * Get applicationTemplate
     *
     * @return string $applicationTemplate
     */
    public function getApplicationTemplate()
    {
        return $this->applicationTemplate;
    }

    /**
     * Get useCustomApplicationTemplateForAllChannels
     *
     * @return integer $useCustomApplicationTemplateForAllChannels
     */
    public function setUseCustomApplicationTemplateForAllChannels($useCustomApplicationTemplateForAllChannels)
    {
        $this->useCustomApplicationTemplateForAllChannels = $useCustomApplicationTemplateForAllChannels;
    }

    /**
     * Get useCustomApplicationTemplateForAllChannels
     *
     * @return integer $useCustomApplicationTemplateForAllChannels
     */
    public function getUseCustomApplicationTemplateForAllChannels()
    {
        return $this->useCustomApplicationTemplateForAllChannels;
    }

    /**
     * Set cssName
     *
     * @param string $cssName
     */
    public function setCssName($cssName)
    {
        $this->cssName = $cssName;
    }

    /**
     * Get cssName
     *
     * @return string $cssName
     */
    public function getCssName()
    {
        return $this->cssName;
    }

    /**
     * Set metatitle
     *
     * @param string $metatitle
     */
    public function setMetatitle($metatitle)
    {
        $this->metatitle = $metatitle;
    }

    /**
     * Get metatitle
     *
     * @return string $metatitle
     */
    public function getMetatitle()
    {
        return $this->metatitle;
    }

    /**
     * Set metadesc
     *
     * @param string $metadesc
     */
    public function setMetadesc($metadesc)
    {
        $this->metadesc = $metadesc;
    }

    /**
     * Get metadesc
     *
     * @return string $metadesc
     */
    public function getMetadesc()
    {
        return $this->metadesc;
    }

    /**
     * Set metakeys
     *
     * @param string $metakeys
     */
    public function setMetakeys($metakeys)
    {
        $this->metakeys = $metakeys;
    }

    /**
     * Get metakeys
     *
     * @return string $metakeys
     */
    public function getMetakeys()
    {
        return $this->metakeys;
    }

    /**
     * Set metarobots
     *
     * @param string $metarobots
     */
    public function setMetarobots($metarobots)
    {
        $this->metarobots = $metarobots;
    }

    /**
     * Get metarobots
     *
     * @return int $metarobots
     */
    public function getMetarobots()
    {
        return empty($this->metarobots) ? 0 : 1;
    }

    /**
     * Set metaimage
     *
     * @param string $metaimage
     */
    public function setMetaimage($metaimage)
    {
        $this->metaimage = $metaimage;
    }

    /**
     * Get metaimage
     *
     * @return string $metaimage
     */
    public function getMetaimage()
    {
        return $this->metaimage;
    }

    /**
     * Set start
     *
     * @param date $start
     */
    public function setStart($start)
    {
        $this->start = $start;
    }

    /**
     * Get start
     *
     * @return date $start
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Set end
     *
     * @param date $end
     */
    public function setEnd($end)
    {
        $this->end = $end;
    }

    /**
     * Get end
     *
     * @return date $end
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Set editingStatus
     *
     * @param boolean $editingStatus
     */
    public function setEditingStatus($editingStatus)
    {
        $this->editingStatus = $editingStatus;
    }

    /**
     * Get editingStatus
     *
     * @return boolean $editingStatus
     */
    public function getEditingStatus()
    {
        return $this->editingStatus;
    }

    /**
     * Set display
     *
     * @param boolean $display
     */
    public function setDisplay($display)
    {
        $this->display = $display;
    }

    /**
     * Get display
     *
     * @return boolean $display
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * Set active
     *
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * Get active
     * @todo Move this method to CM!
     * @return boolean $active
     */
    public function getStatus()
    {
        $status = '';
        if ($this->getType() == self::TYPE_FALLBACK) {
            $fallback_lang = \FWLanguage::getFallbackLanguageIdById($this->getLang());
            if ($fallback_lang !== false && $fallback_lang == 0) {
                $fallback_lang = \FWLanguage::getDefaultLangId();
            }
            if ($fallback_lang) {
                $fallback_page = $this->getNode()->getPage($fallback_lang);
                if ($fallback_page && $fallback_page->isActive()) {
                    $fallback_status = $fallback_page->getStatus();
                    if ($this->isFrontendProtected() && !preg_match('/protected/', $fallback_status)) {
                        $fallback_status .= 'protected ';
                    }
                    return 'fallback ' . $fallback_status;
                }
            }
            $status .= 'fallback broken ';
        } else if ($this->getType() == self::TYPE_REDIRECT) {
            $status .= 'redirection ';
            if (!$this->target) {
                $status .= 'broken ';
            } else if ($this->isTargetInternal()) {
                // this should not be done here!
                $pageRepo = \Env::get('em')->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
                try {
                    $targetPage = $pageRepo->getTargetPage($this);
                    if (!$targetPage->isActive()){
                        throw new PageRepositoryException("Page is not active");
                    }
                } catch (PageRepositoryException $e){
                    $status .= 'broken ';
                }
            }
        } else if ($this->getType() == self::TYPE_SYMLINK) {
            $status .= 'symlink ';
            $pageRepo = \Env::get('em')->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
            try {
                $targetPage = $pageRepo->getTargetPage($this);
                if (!$targetPage->isActive()){
                    throw new PageRepositoryException("Page is not active");
                }
            } catch (PageRepositoryException $e){
                $status .= 'broken ';
            }
        }

        if ($this->getDisplay()) $status .= "active ";
        else $status .= "inactive ";

        if ($this->isFrontendProtected()) $status .= "protected ";
        if ($this->getModule()) {
            if ($this->getModule() == "Home" && $this->getCmd() == '') $status .= "home ";
            else $status .= "application ";
        }
        return $status;
    }

    /**
     * Set status
     *
     * @param boolean $status
     */
    public function setStatus($status)
    {
        // TODO: this needs to be read-only; values are set through active/display setters
        if ($status == "active") {
            $this->active = true;
            $this->display = true;
        }
        elseif ($status == "hidden") {
            $this->active = true;
            $this->display = false;
        }
        else {
            $this->active = false;
            $this->display = false;
        }

    }

    /**
     * Get status
     *
     * @param   boolean $disregardScheduledPublishing
     * @return  boolean $status
     */
    public function getActive($disregardScheduledPublishing = false)
    {
        if (!$disregardScheduledPublishing) {
            $start = $this->getStart();
            $end = $this->getEnd();
            if ((!empty($start) && empty($end) && ($start->getTimestamp() > time())) ||
                (empty($start) && !empty($end) && ($end->getTimestamp() < time())) ||
                (!empty($start) && !empty($end) && !($start->getTimestamp() < time() && $end->getTimestamp() > time()))) {
                return false;
            }
        }
        return $this->active;
    }

    /**
     * Set target
     *
     * @param string $target
     */
    public function setTarget($target)
    {
        $this->target = $target;
    }

    /**
     * Get target
     *
     * @return string $target
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Whether target references an internal page
     * @return boolean
     */
    public function isTargetInternal() {
        //internal targets are formed like [[ NODE_(<node_id>|<module>[_<cmd>])[_<lang_id>] ]]<querystring>
        $matches = array();
        return preg_match('/\[\['.self::NODE_URL_PCRE.'\]\](\S*)?/ix', $this->target, $matches);
    }

    /**
     * Gets the target nodes' id.
     * @return integer id for internal targets, 0 else.
     */
    public function getTargetNodeId() {
        if(!$this->isTargetInternal())
            return 0;

        $c = $this->cutTarget();
        return intval($c['nodeId']);
    }

    protected function cutTarget() {
        $t = $this->getTarget();
        $matches = array();

        if (!preg_match('/\[\['.self::NODE_URL_PCRE.'\]\](\S*)?/ix', $t, $matches)) {
            return array(
                'nodeId'      => null,
                'module'      => null,
                'cmd'         => null,
                'langId'      => null,
                'queryString' => $t,
            );
        }

        $nodeId      = empty($matches[self::NODE_URL_NODE_ID]) ? 0                : $matches[self::NODE_URL_NODE_ID];
        $module      = empty($matches[self::NODE_URL_MODULE])  ? ''               : $matches[self::NODE_URL_MODULE];
        $cmd         = empty($matches[self::NODE_URL_CMD])     ? ''               : $matches[self::NODE_URL_CMD];
        $langId      = empty($matches[self::NODE_URL_LANG_ID]) ? FRONTEND_LANG_ID : $matches[self::NODE_URL_LANG_ID];
        $queryString = empty($matches[6]) ? '' : $matches[6];

        return array(
            'nodeId'      => $nodeId,
            'module'      => $module,
            'cmd'         => $cmd,
            'langId'      => $langId,
            'queryString' => $queryString,
        );
    }

    /**
     * Get the target pages' language id.
     * @return integer id for set language, 0 if it is not set or external target
     */
    public function getTargetLangId() {
        if(!$this->isTargetInternal())
            return 0;

        $c = $this->cutTarget();
        return intval($c['langId']);
    }

    /**
     * Get the target pages' module name
     * @return mixed module name if set, otherwise NULL
     */
    public function getTargetModule() {
        if(!$this->isTargetInternal())
            return null;

        $c = $this->cutTarget();
        return strtolower($c['module']);
    }

    /**
     * Get the target pages' module cmd
     * @return mixed module cmd if set, otherwise NULL
     */
    public function getTargetCmd() {
        if(!$this->isTargetInternal())
            return null;

        $c = $this->cutTarget();
        return strtolower($c['cmd']);
    }

    /**
     * Gets the target pages' querystring.

     * @return string querystring for internal targets, null else
     */
    public function getTargetQueryString() {
        if(!$this->isTargetInternal())
            return null;

        $c = $this->cutTarget();
        return $c['queryString'];
    }

    /**
     * Set module
     *
     * @param integer $module
     */
    public function setModule($module)
    {
        $this->module = $module;
    }

    /**
     * Get module
     *
     * @return integer $module
     */
    public function getModule()
    {
        if ($this->getType() == self::TYPE_FALLBACK) {
            $fallback = $this->getFallback();
            if (!$fallback) {
                return $this->module;
            }
            return $fallback->getModule();
        } else if ($this->getType() != self::TYPE_APPLICATION) {
            return '';
        }
        return $this->module;
    }

    /**
     * Set cmd
     *
     * @param string $cmd
     */
    public function setCmd($cmd)
    {
        $this->cmd = $cmd;
    }

    /**
     * Get cmd
     *
     * @return string $cmd
     */
    public function getCmd()
    {
        if ($this->getType() == self::TYPE_FALLBACK) {
            $fallback = $this->getFallback();
            if (!$fallback) {
                return $this->cmd;
            }
            return $fallback->getCmd();
        } else if ($this->getType() != self::TYPE_APPLICATION) {
            return '';
        }
        return $this->cmd;
    }

    /**
     * Set node
     *
     * @param Cx\Core\ContentManager\Model\Entity\Node $node
     */
    public function setNode(\Cx\Core\ContentManager\Model\Entity\Node $node)
    {
        //$node->addAssociatedPage($this);
        $this->node = $node;
    }

    /**
     * Get node
     *
     * @return Cx\Core\ContentManager\Model\Entity\Node $node
     */
    public function getNode()
    {
        if (is_int($this->node)) {
            $this->node = \Env::get('em')->getRepository('Cx\Core\ContentManager\Model\Entity\Node')->find($this->node);
        }
        return $this->node;
    }

    /**
     * Set skin
     *
     * @param Cx\Core\ContentManager\Model\Entity\Skin $skin
     */
    public function setSkin($skin)
    {
        $this->skin = $skin;
    }

    /**
     * Get skin
     *
     * @return Cx\Core\ContentManager\Model\Entity\Skin $skin
     */
    public function getSkin()
    {
        return $this->skin;
    }

    /**
     * Set useSkinForAllChannels
     *
     * @return integer $useSkinForAllChannels
     */
    public function setUseSkinForAllChannels($useSkinForAllChannels)
    {
        $this->useSkinForAllChannels = $useSkinForAllChannels;
    }

    /**
     * Get useSkinForAllChannels
     *
     * @return integer $useSkinForAllChannels
     */
    public function getUseSkinForAllChannels()
    {
        return $this->useSkinForAllChannels;
    }

    /**
     * @var boolean $caching
     */
    private $caching;

    /**
     * Set caching
     *
     * @param boolean $caching
     */
    public function setCaching($caching)
    {
        $this->caching = $caching;
    }

    /**
     * Get caching
     *
     * @return boolean $caching
     */
    public function getCaching()
    {
        return $this->caching;
    }

    /**
     * Set type
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get type
     *
     * @return string $type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @prePersist
     * @onFlush
     */
    public function validate()
    {
        // Slug must be unique per language and level of a branch (of the node tree)
        $slugs = array();
        foreach ($this->getNode()->getParent()->getChildren() as $child) {
            // if virtual lang dirs are deactivated
            if (!\Cx\Core\Routing\Url::isVirtualLanguageDirsActive()) {
                if ($this->getLang() == 0) {
                    // check default language for the same slug
                    $page = $child->getPage(\FWLanguage::getDefaultLangId());
                } else {
                    // check lang '0' for the same slug
                    $page = $child->getPage(0);
                }
                if ($page && $page !== $this) {
                    $slugs[] = strtolower($page->getSlug());
                }
            }
            // check pages of the same language
            $page = $child->getPage($this->getLang());
            if ($page && $page !== $this) {
                $slugs[] = strtolower($page->getSlug());
            }
        }
        while ($this->getSlug() == '' || in_array(strtolower($this->getSlug()), $slugs)) {
            $this->nextSlug();
        }
        // Alias slugs must not be equal to an existing file or folder
        if ($this->getType() == self::TYPE_ALIAS) {
            $invalidAliasNames = array(
                'admin',
                'cache',
                'cadmin',
                'config',
                'core',
                'core_modules',
                'customizing',
                'feed',
                'images',
                'installer',
                'lang',
                'lib',
                'media',
                'model',
                'modules',
                'themes',
                'tmp',
                'update',
                'webcam',
                'favicon.ico',
            );
            if (
                in_array($this->getSlug(), $invalidAliasNames) ||
                // check if alias matches language tag regex (de, en-US, i-hak, etc.)
                preg_match('/^[a-z]{1,2}(?:-[A-Za-z]{2,4})?$/', $this->getSlug())
            ) {
                $lang = \Env::get('lang');
                $error = array(
                    'slug' => array($lang['TXT_CORE_CANNOT_USE_AS_ALIAS'])
                );
                throw new \Cx\Model\Base\ValidationException($error);
            }
        }
        //workaround, this method is regenerated each time
        parent::validate();
    }

    public function setUpdatedAtToNow()
    {
        $this->updatedAt = new \DateTime("now");
    }

    /**
     * Set updatedAt
     *
     * @param datetime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Get updatedAt
     *
     * @return datetime $updatedAt
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set updatedBy
     *
     * @param string $updatedBy
     */
    public function setUpdatedBy($updatedBy)
    {
        $this->updatedBy = $updatedBy;
    }

    /**
     * Get updatedBy
     *
     * @return string $updatedBy
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }

    /**
     * Whether page access from frontend is protected.
     * @return boolean
     */
    public function isFrontendProtected() {
        return ($this->protection & FRONTEND_PROTECTION) >= 1;
    }

    /**
     * Whether page access from backend is protected.
     * @return boolean
     */
    public function isBackendProtected() {
        return ($this->protection & BACKEND_PROTECTION) >= 1;
    }

    protected function createAccessId() {
        $accessId = \Permission::createNewDynamicAccessId();
        if($accessId === false)
            throw new PageException('protecting Page failed: Permission system could not create a new dynamic access id');

        return $accessId;
    }

    protected function eraseAccessId($id) {
        \Permission::removeAccess($id, 'dynamic');
    }

    /**
     * Set page protection in frontend.
     * @param boolean $enabled
     */
    public function setFrontendProtection($enabled) {
        if (!\Permission::checkAccess(36, 'static', true))
            return;
        if($enabled) {
            //do nothing if we're already safe.
            if($this->isFrontendProtected())
                return;

            $accessId = $this->createAccessId();
            $this->setFrontendAccessId($accessId);
            $this->protection = $this->protection | FRONTEND_PROTECTION;
        }
        else {
            if ($this->isFrontendProtected()) {
                $accessId = $this->getFrontendAccessId();
                $this->eraseAccessId($accessId);
            }
            $this->setFrontendAccessId(0);
            $this->protection = $this->protection & ~FRONTEND_PROTECTION;
        }
    }

    /**
     * Set page protection in backend.
     * @param boolean $enabled
     */
    public function setBackendProtection($enabled) {
        if (!\Permission::checkAccess(36, 'static', true))
            return;
        if($enabled) {
            //do nothing if we're already safe.
            if($this->isBackendProtected())
                return;

            $accessId = $this->createAccessId();
            $this->setBackendAccessId($accessId);
            $this->protection = $this->protection | BACKEND_PROTECTION;
        }
        else {
            if ($this->isBackendProtected()) {
                $accessId = $this->getBackendAccessId();
                $this->eraseAccessId($accessId);
            }
            $this->setBackendAccessId(0);
            $this->protection = $this->protection & ~BACKEND_PROTECTION;
        }
    }

    /**
     * Alias for getDisplay()
     *
     * @return boolean
     */
    public function isVisible() {
        return $this->display;
    }

    /**
     * Alias for getActive()
     *
     * @param   boolean $disregardScheduledPublishing
     * @return boolean
     */
    public function isActive($disregardScheduledPublishing = false)
    {
        return $this->getActive($disregardScheduledPublishing);
    }

    /**
     * Set slug
     *
     * @param string $slug
     * @param boolean $nextSlugCall set by { @see nextSlug() }
     */
    public function setSlug($slug, $nextSlugCall=false)
    {
        if (!empty($slug)) {
            $this->slug = $this->slugify($slug);

            if(!$nextSlugCall) {
                $this->slugSuffix = 0;
                $this->slugBase = $this->slug;
            }
        }
    }

    /**
     * Get slug
     *
     * @return string $slug
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * DO NOT CALL THIS METHOD! USE copyToLang() OR copyToNode() INSTEAD!
     * Copies data from another Page.
     *
     * @param boolean $includeContent                   Whether to copy content. Defaults to true
     * @param boolean $includeModuleAndCmd              Whether to copy module and cmd. Defaults to true
     * @param boolean $includeName                      Whether to copy title, content title and slug. Defaults to true
     * @param boolean $includeMetaData                  Whether to copy meta data. Defaults to true
     * @param boolean $includeProtection                Whether to copy protection. Defaults to true
     * @param boolean $includeEditingStatus             Copy the editing status if true otherwise not
     * @param boolean $followRedirects                  Whether to return a redirection page or the page its pointing at.
     *                                                  Defaults to false, which returns the redirection page
     * @param boolean $followFallbacks                  Whether to return a fallback page or the page its pointing at.
     *                                                  Defaults to false, witch returns the fallback page
     * @param \Cx\Core\ContentManager\Model\Entity\Page Page to use as target
     * @return \Cx\Core\ContentManager\Model\Entity\Page The copy of $this or null on error
     */
    public function copy(
        $includeContent = true,
        $includeModuleAndCmd = true,
        $includeName = true,
        $includeMetaData = true,
        $includeProtection = true,
        $includeEditingStatus = true,
        $followRedirects = false,
        $followFallbacks = false,
        $page = null
    ) {

        $targetPage = null;
        if ($followRedirects && $this->getType() == self::TYPE_REDIRECT) {
            $targetPage = $this->getTargetNodeId()->getPage($this->getTargetLangId());
        }
        if ($followFallbacks && $this->getType() == self::TYPE_FALLBACK) {
            $fallbackLanguage = \FWLanguage::getFallbackLanguageIdById($this->getLang());
            $targetPage = $this->getNode()->getPage($fallbackLanguage);
        }
        if ($targetPage) {
            return $targetPage->copy(
                $includeContent,
                $includeModuleAndCmd,
                $includeName,
                $includeMetaData,
                $includeProtection,
                $includeEditingStatus,
                $followRedirects,
                $followFallbacks
            );
        }

        if (!$page) {
            $page = new \Cx\Core\ContentManager\Model\Entity\Page();
        }

        if ($includeName) {
            $page->setContentTitle($this->getContentTitle());
            $page->setTitle($this->getTitle());
            $page->setSlug($this->getSlug());
        }

        $newType = $this->getType();
        if ($includeContent) {
            $page->setContent($this->getContent());
        } else {
            $newType = self::TYPE_FALLBACK;
        }

        if($includeModuleAndCmd) {
            $page->setModule($this->getModule());
            $page->setCmd($this->getCmd());
        } else {
            $page->setCmd('');
        }

        if ($includeMetaData) {
            $page->setMetatitle($this->getMetatitle());
            $page->setMetadesc($this->getMetadesc());
            $page->setMetakeys($this->getMetakeys());
            $page->setMetarobots($this->getMetarobots());
            $page->setMetaimage($this->getMetaimage());
        }

        if ($includeEditingStatus) {
            $page->setEditingStatus($this->getEditingStatus());
        }

        $page->setNode($this->getNode());
        $page->setActive($this->getActive());
        $page->setDisplay($this->getDisplay());
        $page->setLang($this->getLang());
        $page->setType($newType);
        $page->setCaching($this->getCaching());
        $page->setCustomContent($this->getCustomContent());
        $page->setCssName($this->getCssName());
        $page->setCssNavName($this->getCssNavName());
        $page->setSkin($this->getSkin());
        $page->setStart($this->getStart());
        $page->setEnd($this->getEnd());
        $page->setTarget($this->getTarget());
        $page->setLinkTarget($this->getLinkTarget());
        $page->setUpdatedBy(\FWUser::getFWUserObject()->objUser->getUsername());

        if ($includeProtection) {
            if (!$this->copyProtection($page, true) ||
                !$this->copyProtection($page, false)) {
                return null;
            }
        }

        return $page;
    }

    /**
     * Clones the protection of this page to another page
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Page to get the same protection as $this
     * @param boolean $frontend Wheter the front- or backend protection should be cloned
     * @return boolean True on success, false otherwise
     */
    public function copyProtection($page, $frontend) {
        if ($frontend) {
            $accessId = $this->getFrontendAccessId();
        } else {
            $accessId = $this->getBackendAccessId();
        }
        $groups = \Permission::getGroupIdsForAccessId($accessId);
        if ($frontend) {
            $page->setFrontendProtection($this->isFrontendProtected());
            $newAccessId = $page->getFrontendAccessId();
        } else {
            $page->setBackendProtection($this->isBackendProtected());
            $newAccessId = $page->getBackendAccessId();
        }
        foreach ($groups as $groupId) {
            if (!\Permission::setAccess($newAccessId, 'dynamic', $groupId)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Creates a copy of this page which belongs to another node.
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Node $destinationNode The other node
     * @param boolean $includeContent                   Whether to copy content. Defaults to true
     * @param boolean $includeModuleAndCmd              Whether to copy module and cmd. Defaults to true
     * @param boolean $includeName                      Whether to copy title, content title and slug. Defaults to true
     * @param boolean $includeMetaData                  Whether to copy meta data. Defaults to true
     * @param boolean $includeProtection                Whether to copy protection. Defaults to true
     * @param boolean $includeEditingStatus             Copy the editing status if true otherwise not
     * @param boolean $followRedirects                  Whether to return a redirection page or the page its pointing at.
     *                                                  Defaults to false, which returns the redirection page
     * @param boolean $followFallbacks                  Whether to return a fallback page or the page its pointing at.
     *                                                  Defaults to false, witch returns the fallback page
     * @param \Cx\Core\ContentManager\Model\Entity\Page Page to use as target
     * @return \Cx\Core\ContentManager\Model\Entity\Page The copy of $this or null on error
     */
    public function copyToNode(
        $destinationNode,
        $includeContent = true,
        $includeModuleAndCmd = true,
        $includeName = true,
        $includeMetaData = true,
        $includeProtection = true,
        $includeEditingStatus = true,
        $followRedirects = false,
        $followFallbacks = false,
        $page = null
    ) {

        $copy = $this->copy(
            $includeContent,
            $includeModuleAndCmd,
            $includeName,
            $includeMetaData,
            $includeProtection,
            $includeEditingStatus,
            $followRedirects,
            $followFallbacks,
            $page
        );
        $copy->setNode($destinationNode);
        $destinationNode->addPage($copy);
        return $copy;
    }

    /**
     * Creates a copy of this page with a different language.
     *
     * @todo Define what to do if destination lang is not the fallback of $this->getLang() (always follow fallbacks or do not copy content)
     * @param integer $destinationLang                  Language ID to set
     * @param boolean $includeContent                   Whether to copy content. Defaults to true.
     * @param boolean $includeModuleAndCmd              Whether to copy module and cmd. Defaults to true.
     * @param boolean $includeName                      Whether to copy title, content title and slug. Defaults to true.
     * @param boolean $includeMetaData                  Whether to copy meta data. Defaults to true.
     * @param boolean $includeProtection                Whether to copy protection. Defaults to true.
     * @param boolean $includeEditingStatus             Copy the editing status if true otherwise false
     * @param boolean $followRedirects                  Whether to return a redirection page or the page its pointing at.
     *                                                  Defaults to false, which returns the redirection page
     * @param boolean $followFallbacks                  Whether to return a fallback page or the page its pointing at.
     *                                                  Defaults to false, witch returns the fallback page
     * @param \Cx\Core\ContentManager\Model\Entity\Page Page to use as target
     * @return \Cx\Core\ContentManager\Model\Entity\Page The copy of $this or null on error
     */
    public function copyToLang(
        $destinationLang,
        $includeContent = true,
        $includeModuleAndCmd = true,
        $includeName = true,
        $includeMetaData = true,
        $includeProtection = true,
        $includeEditingStatus = true,
        $followRedirects = false,
        $followFallbacks = false,
        $page = null
    ) {

        $copy = $this->copy(
            $includeContent,
            $includeModuleAndCmd,
            $includeName,
            $includeMetaData,
            $includeProtection,
            $includeEditingStatus,
            $followRedirects,
            $followFallbacks,
            $page
        );
        $fallback_language = \FWLanguage::getFallbackLanguageIdById($destinationLang);
        if ($fallback_language && !$includeContent) {
            $copy->setType(self::TYPE_FALLBACK);
        } else if ($copy->getType() == self::TYPE_FALLBACK) {
            $copy->setType(self::TYPE_CONTENT);
        }
        $copy->setLang($destinationLang);
        return $copy;
    }

    /**
     * Set contentTitle
     *
     * @param string $contentTitle
     */
    public function setContentTitle($contentTitle)
    {
        $this->contentTitle = $contentTitle;
    }

    /**
     * Get contentTitle
     *
     * @return string $contentTitle
     */
    public function getContentTitle()
    {
        return $this->contentTitle;
    }

    /**
     * Set linkTarget
     *
     * @param string $linkTarget
     */
    public function setLinkTarget($linkTarget)
    {
        $this->linkTarget = $linkTarget;
    }

    /**
     * Get linkTarget
     *
     * @return string $linkTarget
     */
    public function getLinkTarget()
    {
        return $this->linkTarget;
    }

    /**
     * Set frontendAccessId
     *
     * @param integer $frontendAccessId
     */
    public function setFrontendAccessId($frontendAccessId)
    {
        $this->frontendAccessId = $frontendAccessId;
    }

    /**
     * Get frontendAccessId
     *
     * @return integer $frontendAccessId
     */
    public function getFrontendAccessId()
    {
        return $this->frontendAccessId;
    }

    /**
     * Set backendAccessId
     *
     * @param integer $backendAccessId
     */
    public function setBackendAccessId($backendAccessId)
    {
        $this->backendAccessId = $backendAccessId;
    }

    /**
     * Get backendAccessId
     *
     * @return integer $backendAccessId
     */
    public function getBackendAccessId()
    {
        return $this->backendAccessId;
    }

    /**
     * Set protection
     *
     * @param integer $protection
     */
    // DO NOT set protection directly, use setFrontend/BackendProtected instead.
    /*public function setProtection($protection)
      {
      $this->protection = $protection;
      }*/

    /**
     * Get protection
     *
     * @return integer $protection
     */
    public function getProtection()
    {
        return $this->protection;
    }

    /**
     * Whether the Page is intended to exist in DB or not.
     * @return boolean
     */
    public function isVirtual() {
        return $this->isVirtual;
    }

    /**
     * Sets this pages virtual flag
     * @param boolean $virtual
     */
    public function setVirtual($virtual = true) {
        if ($this->isVirtual && !$virtual) {
            throw new PageException('Can not set a virtual page to "non virtual"');
        }
        $this->isVirtual = $virtual;
    }

    /**
     * Copies the content from the other page given.
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page
     * @param boolean $includeThemeOptions (optional) Wheter to adopt theme options as well (default false)
     */
    public function setContentOf($page, $includeThemeOptions = false) {
        $this->isVirtual = true;
        $this->content = $page->getContent();
        $this->module = $page->getModule();
        $this->cmd = $page->getCmd();
        if ($includeThemeOptions) {
            $this->skin = $page->getSkin();
            $this->customContent = $page->getCustomContent();
            $this->applicationTemplate = $page->getApplicationTemplate();
            $this->cssName = $page->getCssName();
        }

        $this->type = $page->getType();
        $this->target = $page->getTarget();
   }

    /**
     * Set cssNavName
     *
     * @param string $cssNavName
     */
    public function setCssNavName($cssNavName)
    {
        $this->cssNavName = $cssNavName;
    }

    /**
     * Get cssNavName
     *
     * @return string $cssNavName
     */
    public function getCssNavName()
    {
        return $this->cssNavName;
    }

    /**
     * Stores changes to the aliases for this page
     * @param Array List of alias slugs
     */
    public function setAlias($data)
    {
        $oldAliasList = $this->getAliases();
        $aliases = array();
        $lib = new \Cx\Core_Modules\Alias\Controller\AliasLib($this->getLang());
        foreach ($oldAliasList as $oldAlias) {
            if (in_array($oldAlias->getSlug(), $data)) {
                // existing alias, ignore;
                $aliases[] = $oldAlias->getSlug();
            } else {
                // deleted alias
                $lib->_deleteAlias($oldAlias->getNode()->getId());
            }
        }
        foreach ($data as $alias) {
            if (!in_array($alias, $aliases)) {
                // new alias
                $lib->_saveAlias($alias, '[[' . self::PLACEHOLDER_PREFIX . $this->getNode()->getId() . '_' . $this->getLang() . ']]', true);
            }
        }
    }

    /**
     * Returns an array of alias pages for a page
     * @return Array<Cx\Core\ContentManager\Model\Entity\Page>
     */
    public function getAliases()
    {
        $aliases = array();
        // find aliases without specified language
        $target = '[[' . self::PLACEHOLDER_PREFIX . $this->getNode()->getId() . ']]';
        $crit1 = array(
            'type' => self::TYPE_ALIAS,
            'target' => $target,
        );

        // find aliases with language specified
        $target = '[[' . self::PLACEHOLDER_PREFIX . $this->getNode()->getId() . '_' . $this->getLang() . ']]';
        $crit2 = array(
            'type' => self::TYPE_ALIAS,
            'target' => $target,
        );

        $pageRepo = \Env::get('em')->getRepository("Cx\Core\ContentManager\Model\Entity\Page");

        // merge both resultsets
        $aliases = array_merge(
                $pageRepo->findBy($crit1, null, null, null, true),
                $pageRepo->findBy($crit2, null, null, null, true)
        );
        return $aliases;
    }

    public function updateFromArray($newData) {
        foreach ($newData as $key => $value) {
            try {
                call_user_func(array($this, "set".ucfirst($key)), $value);
            }
            catch (Exception $e) {
                DBG::log("\r\nskipped ".$key);
            }
        }
    }

    /**
     * Set protection
     *
     * @param integer $protection
     */
    public function setProtection($protection)
    {
        $this->protection = $protection;
    }

    /**
     * Sanitize tree.
     * Translates all missing parent pages in the desired language
     * @param int $targetLang Language ID of the branch to sanitize
     * @return type
     */
    public function setupPath($targetLang) {
        $node  = $this->getNode()->getParent();
        $pages = $node->getPagesByLang();
        $sourceLang = $this->getLang();
        if ($targetLang == $sourceLang) {
            $sourceLang = \FWLanguage::getDefaultLangId();
        }

        if (!empty($pages) && !isset($pages[$targetLang])) {
            $page = $pages[$sourceLang]->copyToLang(
                $targetLang,
                true,   // includeContent
                true,   // includeModuleAndCmd
                true,   // includeName
                true,   // includeMetaData
                true,   // includeProtection
                false,  // includeEditingStatus
                false,  // followRedirects
                true    // followFallbacks
            );
            $page->setDisplay(false);
            \Env::get('em')->persist($page);
            // recursion
            return $pages[$sourceLang]->setupPath($targetLang);
        } else {
            return;
        }
    }

    /**
     * Get a pages' path starting with a slash
     *
     * @return string path, e.g. '/This/Is/It'
     */
    public function getPath() {
        $path = '';
        try {
            $path = $this->getParent()->getPath();
        } catch (PageException $e) {}
        return $path . '/' . $this->getSlug();
    }

    /**
     * Returns "$protocolAndDomainWithPathOffset/link/to/page$params.
     * Notice that there is no trailing slash inserted after the link.
     * If you need one, prepend it to $params.
     * @param string $protocolAndDomain 'http://example.com/cms' - will generate absolute link if left empty
     * @param string $params '?a=b'
     *
     */
    public function getURL($protocolAndDomainWithPathOffset, $params) {
        return \Cx\Core\Routing\Url::fromPage($this, $params);
    }

    /**
     * Returns the page with the same language of the parent node
     * e.g. $this->getNode()->getParent()->getPage($this->lang)
     * @return \Cx\Core\ContentManager\Model\Entity\Page
     * @throws PageException If parent page can not be found
     */
    public function getParent() {
        // virtual pages may not have a node. Throw an exception instead of dying.
        if (!$this->getNode() && $this->isVirtual()) {
            throw new PageException('Virtual page has no node');
        }
        $parentNode = $this->getNode()->getParent();
        if (!$parentNode) {
            throw new PageException('Parent node not found (my page id is ' . $this->getId() . ')');
        }
        $parent = $parentNode->getPage($this->getLang());
        if (!$parent) {
            throw new PageException('Parent page not found (my page id is ' . $this->getId() . ')');
        }
        return $parent;
    }

    /**
     * Returns an array of child pages (pages of the same language of all subnodes)
     * @return array List of page objects
     */
    public function getChildren() {
        $childNodes = $this->getNode()->getChildren();
        $children = array();
        foreach ($childNodes as $childNode) {
            $child = $childNode->getPage($this->getLang());
            if ($child) {
                $children[] = $child;
            }
        }
        return $children;
    }

    /**
     * Returns the fallback page of this page
     * @return \Cx\Core\ContentManager\Model\Entity\Page Fallback page or null if none
     */
    public function getFallback() {
        if ($this->getType() != self::TYPE_FALLBACK) {
            return null;
        }
        $fallbackLanguage = \FWLanguage::getFallbackLanguageIdById($this->getLang());
        if (!$fallbackLanguage) {
            return null;
        }
        return $this->getNode()->getPage($fallbackLanguage);
    }

    /**
     * Returns the DateTime object for the modification time (for use in sitemap or so)
     * @return \DateTime DateTime Object
     */
    public function getLastModificationDateTime() {
        $timestamp = $this->getUpdatedAt();
        // we don't know when the module content is updated, so we "guess"
        if ($this->getModule() != '') {
            $timestamp->setDate(date('Y'), date('m'), date('d'));
        }
        return $timestamp;
    }

    /**
     * Returns the change frequency in XY (for use in sitemap or so)
     * @todo do something more sensful than checking for module
     * @return string 'hourly' or 'weekly'
     */
    public function getChangeFrequency() {
        if ($this->getModule() != '') {
            return 'hourly';
        }
        return 'weekly';
    }

    /**
     * Returns the blocks related to this page
     * @return array
     */
    public function getRelatedBlocks() {
        $blockLib = new \Cx\Modules\Block\Controller\BlockLibrary();
        $blocks = $blockLib->_getBlocksForPageId($this->getId());
        return $blocks;
    }

    /**
     * Sets relations to blocks
     * @param array $relatedBlocks list of block IDs
     */
    public function setRelatedBlocks($relatedBlocks) {
        $blockLib = new \Cx\Modules\Block\Controller\BlockLibrary();
        $blockLib->_setBlocksForPageId($this->getId(), $relatedBlocks);
    }

    /**
     * Returns the current log for this page
     * @return \Cx\Core\ContentManager\Model\Entity\LogEntry Current page log
     */
    public function getVersion() {
        $logRepo = \Env::get('em')->getRepository('Cx\Core\ContentManager\Model\Entity\LogEntry');
        return $logRepo->getLatestLog($this);
    }

    /**
     * Checks whether the page is a draft or not
     * @return bool true if the page is currently a draft
     */
    public function isDraft() {
        return $this->getEditingStatus() != '';
    }

    public function serialize() {
        return serialize(
            array(
                $this->id,
                $this->active,
                $this->backendAccessId,
                $this->caching,
                $this->cmd,
                $this->content,
                $this->contentTitle,
                $this->cssName,
                $this->cssNavName,
                $this->customContent,
                $this->display,
                $this->editingStatus,
                $this->end,
                $this->frontendAccessId,
                $this->isVirtual,
                $this->lang,
                $this->linkTarget,
                $this->metarobots,
                $this->metatitle,
                $this->module,
                $this->node->getId(),
                $this->nodeIdShadowed,
                $this->protection,
                $this->skin,
                $this->slug,
                $this->slugBase,
                $this->slugSuffix,
                $this->sourceMode,
                $this->start,
                $this->target,
                $this->title,
                $this->type,
                $this->updatedAt,
                $this->updatedBy,
                $this->metadesc,
                $this->metakeys,
                $this->metaimage
            )
        );
    }
    public function unserialize($data) {
        $unserialized = unserialize($data);
        $this->id = $unserialized[0];
        $this->active = $unserialized[1];
        $this->backendAccessId = $unserialized[2];
        $this->caching = $unserialized[3];
        $this->cmd = $unserialized[4];
        $this->content = $unserialized[5];
        $this->contentTitle = $unserialized[6];
        $this->cssName = $unserialized[7];
        $this->cssNavName = $unserialized[8];
        $this->customContent = $unserialized[9];
        $this->display = $unserialized[10];
        $this->editingStatus = $unserialized[11];
        $this->end = $unserialized[12];
        $this->frontendAccessId = $unserialized[13];
        $this->isVirtual = $unserialized[14];
        $this->lang = $unserialized[15];
        $this->linkTarget = $unserialized[16];
        $this->metarobots = $unserialized[17];
        $this->metatitle = $unserialized[18];
        $this->module = $unserialized[19];
        $this->node = $unserialized[20];
        $this->nodeIdShadowed = $unserialized[21];
        $this->protection = $unserialized[22];
        $this->skin = $unserialized[23];
        $this->slug = $unserialized[24];
        $this->slugBase = $unserialized[25];
        $this->slugSuffix = $unserialized[26];
        $this->sourceMode = $unserialized[27];
        $this->start = $unserialized[28];
        $this->target = $unserialized[29];
        $this->title = $unserialized[30];
        $this->type = $unserialized[31];
        $this->updatedAt = $unserialized[32];
        $this->updatedBy = $unserialized[33];
        $this->metadesc = $unserialized[34];
        $this->metakeys = $unserialized[35];
        $this->metaimage = $unserialized[36];
    }

    /**
     * {@inheritdoc}
     */
    protected function getContentTemplateForWidget($widgetName, $langId, $page, $channel) {
        $template = parent::getContentTemplateForWidget($widgetName, $langId, $page, $channel);

        // load application template in case page is an application
        if (
            $page->getType() == static::TYPE_APPLICATION &&
            $template->placeholderExists('APPLICATION_DATA')
        ) {
            $contentTemplate =
                \Cx\Core\Core\Controller\Cx::getContentTemplateOfPageWithoutWidget(
                $page,
                null,
                $channel
            );
            $template->addBlock(
                'APPLICATION_DATA',
                'cx_application_data',
                $contentTemplate
            );
        }

        return $template;
    }

    /**
     * Returns the name of the attribute used to parse Widget named $widgetName
     * @return string Attribute name used as getter name
     */
    public function getWidgetContentAttributeName($widgetName) {
        return 'content';
    }

    /**
     * Check whether the page $title already exists under the pages in $parentNode
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Node $parentNode Parent node object
     * @param integer                                   $lang       Language ID
     * @param string                                    $title      Page title
     * @return boolean Return true if the page title exists, otherwise false
     */
    public function titleExists($parentNode, $lang, $title)
    {
        foreach ($parentNode->getChildren() as $childNode) {
            if (
                $childNode->getPage($lang) &&
                $childNode->getPage($lang)->getTitle() == $title
            ) {
                return true;
            }
        }

        return false;
    }
}
