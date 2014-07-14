<?php
/**
 * Main controller for Knowledge
 * 
 * @copyright   Comvation AG
 * @author      Project Team SS4U <info@comvation.com>
 * @package     contrexx
 * @subpackage  modules_knowledge
 */

namespace Cx\Modules\Knowledge\Controller;

/**
 * Main controller for Knowledge
 * 
 * @copyright   Comvation AG
 * @author      Project Team SS4U <info@comvation.com>
 * @package     contrexx
 * @subpackage  modules_knowledge
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {
    public function getControllerClasses() {
        // Return an empty array here to let the component handler know that there
        // does not exist a backend, nor a frontend controller of this component.
        return array();
    }

     /**
     * Load your component.
     * 
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $subMenuTitle, $_CORELANG, $objTemplate, $page_metatitle;
        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                $objKnowledge = new Knowledge(\Env::get('cx')->getPage()->getContent());
                \Env::get('cx')->getPage()->setContent($objKnowledge->getPage());
                if (!empty($objKnowledge->pageTitle)) {
                    \Env::get('cx')->getPage()->setTitle($objKnowledge->pageTitle);
                    \Env::get('cx')->getPage()->setContentTitle($objKnowledge->pageTitle);
                    \Env::get('cx')->getPage()->setMetaTitle($objKnowledge->pageTitle);
                    $page_metatitle = $objKnowledge->pageTitle;
                }
                break;

            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:
                $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'content_master.html');
                $objTemplate = $this->cx->getTemplate();
                
                if (file_exists($this->cx->getClassLoader()->getFilePath($this->getDirectory() . '/View/Style/backend.css'))) {
                    \JS::registerCSS(substr($this->getDirectory(false, true) . '/View/Style/backend.css', 1));
                }
                
                \Permission::checkAccess(129, 'static');
                $subMenuTitle = $_CORELANG['TXT_KNOWLEDGE'];
                $objKnowledge = new KnowledgeAdmin();
                $objKnowledge->getPage();
                break;
        }
    }

    /**
     * Do something before content is loaded from DB
     * 
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function preContentLoad(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $_CONFIG, $knowledgeInterface, $page_template, $themesPages;
        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                // get knowledge content
                if (MODULE_INDEX < 2 && !empty($_CONFIG['useKnowledgePlaceholders'])) {
                    $knowledgeInterface = new KnowledgeInterface();
                    if (preg_match('/{KNOWLEDGE_[A-Za-z0-9_]+}/i', \Env::get('cx')->getPage()->getContent())) {
                        $knowledgeInterface->parse(\Env::get('cx')->getPage()->getContent());
                    }
                    if (preg_match('/{KNOWLEDGE_[A-Za-z0-9_]+}/i', $page_template)) {
                        $knowledgeInterface->parse($page_template);
                    }
                    if (preg_match('/{KNOWLEDGE_[A-Za-z0-9_]+}/i', $themesPages['index'])) {
                        $knowledgeInterface->parse($themesPages['index']);
                    }
                }
                break;
        }
    }
}