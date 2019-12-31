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
 * Alias library
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_alias
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Core_Modules\Alias\Controller;
/**
 * Alias library
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_alias
 * @todo        Edit PHP DocBlocks!
 */
class AliasLib
{
    public $_arrAliasTypes = array(
        'local',
        'url'
    );

    public $langId;

    public $_arrConfig = null;

    protected $em = null;

    protected $nodeRepository = null;

    protected $pageRepository = null;

    protected $hasLegacyPages = false;

    function __construct($langId = 0)
    {
        $this->langId = intval($langId) > 0 ? $langId : FRONTEND_LANG_ID;

        $this->em = \Env::get('em');
        $this->nodeRepository = $this->em->getRepository('Cx\Core\ContentManager\Model\Entity\Node');
        $this->pageRepository = $this->em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
    }


    function _getAliases($limit = null, $all = false, $legacyPages = false, $slug = null)
    {
        $pos = !$all && isset($_GET['pos']) ? intval($_GET['pos']) : 0;

        if(!$slug){
            // show all entries
            $aliases = $this->pageRepository->findBy(array(
                'type' => \Cx\Core\ContentManager\Model\Entity\Page::TYPE_ALIAS,
            ), null, null, null, true);
        } else {
            // query builder for filtering entries
            $qb = $this->pageRepository->createQueryBuilder('p');
            $aliases =
                $qb->select('p')
                ->add('where', $qb->expr()->andX(
                    $qb->expr()->eq('p.type', ':type'),
                    $qb->expr()->like('p.slug', ':slug')
                ))->setParameters(array(
                    'type' => \Cx\Core\ContentManager\Model\Entity\Page::TYPE_ALIAS,
                    'slug' => $slug,
                ))->getQuery()->getResult();
        }
        $i = 0;
        $pages = array();
        foreach ($aliases as $page) {
            // skip legacy page aliases if legacy page is disabled
            if (preg_match('/^legacy_page_/', $page->getSlug())) {
                $this->hasLegacyPages = true;
                if (!$legacyPages) continue;
            }
            $i++;
            if ($i < $pos) {
                continue;
            }
            $pages[] = $page;
            if ($limit && count($pages) == $limit) {
                break;
            }
        }

        return $pages;
    }


    function _getAliasesCount($showLegacyPagealiases, $slug)
    {
        return count($this->_getAliases(null, true, $showLegacyPagealiases, $slug));
    }


    function _getAlias($aliasId)
    {
        $crit = array(
            'node' => $aliasId,
        );
        return current($this->pageRepository->findBy($crit, null, null, null, true));
    }


    function _fetchTarget($page)
    {
        try {
            return $this->pageRepository->getTargetPage($page);
        } catch (\Cx\Core\ContentManager\Model\Repository\PageRepositoryException $e) {
            \DBG::log($e->getMessage());
            return null;
        }
    }


    function _isLocalAliasTarget($page)
    {
        return $page->isTargetInternal();
    }


    function _getURL($page)
    {
        return $page->getURL(null, array());
    }

    function _getAliasesWithSameTarget($aliasPage)
    {
        $aliases = array();
        $target  = $aliasPage->getTarget();

        if (!empty($target)) {
            $crit = array(
                'type'   => \Cx\Core\ContentManager\Model\Entity\Page::TYPE_ALIAS,
                'target' => $target,
            );
            $aliases = $this->pageRepository->findBy($crit, null, null, null, true);
        }

        return $aliases;
    }


    function _setAliasTarget(&$arrAlias)
    {
        if ($arrAlias['type'] == 'local') {
            $page = new \Cx\Core\ContentManager\Model\Entity\Page();
            $page->setTarget($arrAlias['url']);
            $target_node_id = $page->getTargetNodeId();
            $target_lang_id = $page->getTargetLangId();
            if (!$target_lang_id) {
                $target_lang_id = $this->langId;
            }
            $crit = array(
                'node' => $target_node_id,
                'lang' => $target_lang_id,
            );
            $page_repo = \Env::get('em')->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
            $targetPage = $page_repo->findBy($crit, null, null, null, true);
            $targetPage = $targetPage[0];
            $targetPath = $page_repo->getPath($targetPage);
            $arrAlias['pageUrl'] = "/".$targetPath;
            $arrAlias['title'] = $targetPage->getContentTitle();
        }
    }


    function _createTemporaryAlias()
    {
        global $objFWUser;

        $page = new \Cx\Core\ContentManager\Model\Entity\Page();
        $page->setLang(0);
        $page->setType(\Cx\Core\ContentManager\Model\Entity\Page::TYPE_ALIAS);
        $page->setCmd('');
        $page->setActive(true);
        //$page->setUsername($objFWUser->objUser->getUsername());
        return $page;
    }


    function _saveAlias($slug, $target, $is_local, $id = '')
    {
        if ($slug == '') {
            return false;
        }

        // is internal target
        if ($is_local) {
            // get target page
            $temp_page = new \Cx\Core\ContentManager\Model\Entity\Page();
            $temp_page->setTarget($target);
            $existing_aliases = $this->_getAliasesWithSameTarget($temp_page);
        }

        if ($id == '') {
            // create new node
            $node = new \Cx\Core\ContentManager\Model\Entity\Node();
            $node->setParent($this->nodeRepository->getRoot());
            $this->em->persist($node);

            // add a page
            $page = $this->_createTemporaryAlias();
            $page->setNode($node);
        } else {
            $node = $this->nodeRepository->find($id);
            if (!$node) {
                return false;
            }
            $pages = $node->getPages(true);
            if (count($pages) != 1) {
                return false;
            }
            $page = $pages->first();
            // we won't change anything on non aliases
            if ($page->getType() != \Cx\Core\ContentManager\Model\Entity\Page::TYPE_ALIAS) {
                return false;
            }
        }

        // set page attributes
        $page->setSlug($slug);
        $page->setTarget($target);
        $page->setTitle($page->getSlug());

        // save
        try {
            $page->validate();
        } catch (\Cx\Core\ContentManager\Model\Entity\PageException $e) {
            return $e->getUserMessage();
        }
        $this->em->persist($page);
        $this->em->flush();
        $this->em->refresh($node);
        $this->em->refresh($page);

        return true;
    }


    function _deleteAlias($aliasId)
    {
        $alias = $this->_getAlias($aliasId);
        if (!is_object($alias)) {
            die ("ALIAS NOT FOUND: ID=".$aliasId);
            return false;
        }
        $this->em->remove($alias->getNode());
        $this->em->remove($alias);
        $this->em->flush();
        return true;
    }
}
