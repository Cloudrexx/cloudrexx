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
 * Main controller for Knowledge
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_knowledge
 */

namespace Cx\Modules\Knowledge\Controller;

/**
 * Main controller for Knowledge
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_knowledge
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
        global $subMenuTitle, $_CORELANG, $objTemplate;
        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                $objKnowledge = new Knowledge(\Env::get('cx')->getPage()->getContent());
                \Env::get('cx')->getPage()->setContent($objKnowledge->getPage());
                break;

            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:
                $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
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
        global $knowledgeInterface, $page_template, $themesPages;
        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                // get knowledge content
                \Cx\Core\Setting\Controller\Setting::init('Config', 'component','Yaml');
                if (MODULE_INDEX < 2 && \Cx\Core\Setting\Controller\Setting::getValue('useKnowledgePlaceholders','Config')) {
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

    /**
     * {@inheritdoc}
     */
    public function adjustResponse(
        \Cx\Core\Routing\Model\Entity\Response $response
    ) {
        $page = $response->getPage();
        if (
            !$page ||
            $page->getModule() !== $this->getName() ||
            $page->getCmd() !== 'article'
        ) {
            return;
        }

        $objKnowledge = new Knowledge();
        $pageTitle    = $objKnowledge->getPageTitle();
        if (empty($pageTitle)) {
            return;
        }

        $page->setTitle($pageTitle);
        $page->setContentTitle($pageTitle);
        $page->setMetaTitle($pageTitle);
    }

}
