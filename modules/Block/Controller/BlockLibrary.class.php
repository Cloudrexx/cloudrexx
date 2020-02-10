<?php

/**
 * Cloudrexx
 *
 * @link      https://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2017
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
 * Cx\Modules\Block
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_block
 */

namespace Cx\Modules\Block\Controller;

/**
 * Cx\Modules\Block\Controller\BlockLibraryException
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Manuel Schenk <manuel.schenk@comvation.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_block
 */
class BlockLibraryException extends \Exception
{
}

/**
 * Cx\Modules\Block\Controller\NoCategoryFoundException
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Manuel Schenk <manuel.schenk@comvation.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_block
 */
class NoCategoryFoundException extends BlockLibraryException
{
}

/**
 * Cx\Modules\Block\Controller\BlockLibrary
 *
 * Block library class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @author      Manuel Schenk <manuel.schenk@comvation.com>
 * @access      private
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_block
 * @todo        Edit PHP DocBlocks!
 */
class BlockLibrary
{
    /**
     * Block name prefix
     *
     * @access public
     * @var string
     */
    var $blockNamePrefix = 'BLOCK_';

    /**
     * Block ids
     *
     * @access private
     * @var array
     */
    var $_arrBlocks;

    /**
     * Array of categories
     *
     * @var array
     */
    var $_categories = array();

    /**
     * holds the category dropdown select options
     *
     * @var array of strings: HTML <options>
     */
    var $_categoryOptions = array();

    /**
     * array containing the category names
     *
     * @var array catId => name
     */
    var $_categoryNames = array();

    protected $availableTargeting = array(
        'country',
    );

    /**
     * Constructor
     */
    function __construct()
    {
        if (\Cx\Core\Core\Controller\Cx::instanciate()->getMode() != \Cx\Core\Core\Controller\Cx::MODE_COMMAND) {
            return;
        }
    }


    /**
     * Get blocks
     *
     * Get all blocks
     *
     * @access private
     * @see array blockLibrary::_arrBlocks
     * @return array Array with block ids
     */
    public function getBlocks($catId = 0)
    {
        if (!is_array($this->_arrBlocks)) {
            $em = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager();
            $blockRepo = $em->getRepository('\Cx\Modules\Block\Model\Entity\Block');

            if ($catId != 0) {
                $blocks = $blockRepo->findBy(array('category' => $catId));
            } else {
                $blocks = $blockRepo->findAll();
            }

            $this->_arrBlocks = array();

            foreach ($blocks as $block) {
                $langArr = array();
                $contents = $block->getRelLangContents();
                foreach ($contents as $content) {
                    if ($content->getActive() == 1) {
                        array_push($langArr, $content->getLocale()->getId());
                    }
                }

                $catId = 0;
                $cat = $block->getCategory();
                if ($cat) {
                    $catId = $block->getCategory()->getId();
                }

                $this->_arrBlocks[$block->getId()] = array(
                    'cat' => $catId,
                    'start' => $block->getStart(),
                    'end' => $block->getEnd(),
                    'order' => $block->getOrder(),
                    'random' => $block->getRandom(),
                    'random2' => $block->getRandom2(),
                    'random3' => $block->getRandom3(),
                    'random4' => $block->getRandom4(),
                    'global' => $block->getShowInGlobal(),
                    'active' => $block->getActive(),
                    'direct' => $block->getShowInDirect(),
                    'name' => $block->getName(),
                    'lang' => array_unique($langArr),
                );
            }
        }

        return $this->_arrBlocks;
    }

    /**
     * Store the placeholder settings for a block
     *
     * @param object $block
     * @param int $global
     * @param int $direct
     * @param int $category
     * @param array $globalAssociatedPages
     * @param array $directAssociatedPages
     * @param array $categoryAssociatedPages
     * @return array $relPagesWithoutBlock new page relations without block
     */
    protected function storePlaceholderSettings($block, $global, $direct, $category, $globalAssociatedPages, $directAssociatedPages, $categoryAssociatedPages)
    {
        $relPagesWithoutBlock = array();
        if ($global == 2) {
            $relPagesWithoutBlock = array_merge($relPagesWithoutBlock, $this->storePageAssociations($block, $globalAssociatedPages, 'global'));
        }
        if ($direct == 1) {
            $relPagesWithoutBlock = array_merge($relPagesWithoutBlock, $this->storePageAssociations($block, $directAssociatedPages, 'direct'));
        }
        if ($category == 1) {
            $relPagesWithoutBlock = array_merge($relPagesWithoutBlock, $this->storePageAssociations($block, $categoryAssociatedPages, 'category'));
        }
        return $relPagesWithoutBlock;
    }

    /**
     * Store the page associations
     *
     * @param object $block \Cx\Modules\Block\Model\Entity\Block
     * @param array $blockAssociatedPageIds the page ids
     * @param string $placeholder the placeholder
     * @return array $relPagesWithoutBlock new page relations without block
     */
    private function storePageAssociations($block, $blockAssociatedPageIds, $placeholder)
    {
        $em = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager();
        $pageRepo = $em->getRepository('\Cx\Core\ContentManager\Model\Entity\Page');
        $relPageRepo = $em->getRepository('\Cx\Modules\Block\Model\Entity\RelPage');

        $relPagesWithoutBlock = array();
        foreach ($blockAssociatedPageIds as $pageId) {
            if (!$pageId > 0) {
                continue;
            }

            $page = $pageRepo->findOneBy(array('id' => $pageId));
            if (!$page) {
                continue;
            }

            if ($block) {
                $relPage = $relPageRepo->findOneBy(
                    array(
                        'block' => $block,
                        'page' => $page,
                        'placeholder' => $placeholder,
                    )
                );
                if ($relPage) {
                    continue;
                }
            }

            $relPage = new \Cx\Modules\Block\Model\Entity\RelPage();
            $relPage->setPage($page);
            $relPage->setPlaceholder($placeholder);
            if ($block) {
                $relPage->setBlock($block);
            } else {
                array_push($relPagesWithoutBlock, $relPage);
            }

            $em->persist($relPage);
        }

        if ($block) {
            $relPages = $relPageRepo->findBy(
                array(
                    'block' => $block,
                    'placeholder' => $placeholder,
                )
            );
            if ($relPages) {
                foreach ($relPages as $relPage) {
                    if (!in_array($relPage->getPage()->getId(), $blockAssociatedPageIds)) {
                        $em->remove($relPage);
                    }
                }
            }
        }

        return $relPagesWithoutBlock;
    }

    protected function storeBlockContent($block, $arrContent, $arrLangActive)
    {
        $em = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager();
        $relLangContentRepo = $em->getRepository('\Cx\Modules\Block\Model\Entity\RelLangContent');
        $localeRepo = $em->getRepository('\Cx\Core\Locale\Model\Entity\Locale');

        $arrPresentLang = array();
        if ($block) {
            $relLangContents = $block->getRelLangContents();

            foreach ($relLangContents as $relLangContent) {
                $arrPresentLang[] = $relLangContent->getLocale()->getId();
            }
        }

        $relLangContentsWithoutBlock = array();
        foreach ($arrContent as $langId => $content) {
            if (empty($content)) {
                continue;
            }
            $content = preg_replace('/\[\[([A-Z0-9_-]+)\]\]/', '{\\1}', $content);
            $locale = $localeRepo->findOneBy(array('id' => $langId));

            if (in_array($langId, $arrPresentLang)) {
                $relLangContent = $relLangContentRepo->findOneBy(array('block' => $block, 'locale' => $locale));

                $relLangContent->setContent($content);
                $relLangContent->setActive(isset($arrLangActive[$langId]) ? $arrLangActive[$langId] : 0);
            } else {
                $relLangContent = new \Cx\Modules\Block\Model\Entity\RelLangContent();

                $relLangContent->setContent($content);
                $relLangContent->setActive(isset($arrLangActive[$langId]) ? $arrLangActive[$langId] : 0);
                $relLangContent->setLocale($locale);
                if ($block) {
                    $relLangContent->setBlock($block);
                } else {
                    array_push($relLangContentsWithoutBlock, $relLangContent);
                }

                $em->persist($relLangContent);
            }
        }

        if ($block) {
            \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Cache')->clearSsiCachePage(
                'Block',
                'getBlockContent',
                array(
                    'block' => $block->getId(),
                )
            );

            $relLangContents = $block->getRelLangContents();
            if ($relLangContents) {
                foreach ($relLangContents as $relLangContent) {
                    if (!$arrLangActive[$relLangContent->getLocale()->getId()]) {
                        $em->remove($relLangContent);
                    }
                }
            }
        }

        return $relLangContentsWithoutBlock;
    }

    /**
     * Get GeoIp component controller
     *
     * @return \Cx\Core_Modules\GeoIp\Controller\ComponentController
     */
    public function getGeoIpComponent()
    {
        $componentRepo = \Cx\Core\Core\Controller\Cx::instanciate()
            ->getDb()
            ->getEntityManager()
            ->getRepository('Cx\Core\Core\Model\Entity\SystemComponent');
        $geoIpComponent = $componentRepo->findOneBy(array('name' => 'GeoIp'));
        if (!$geoIpComponent) {
            return null;
        }
        $geoIpComponentController = $geoIpComponent->getSystemComponentController();
        if (!$geoIpComponentController) {
            return null;
        }

        return $geoIpComponentController;
    }

    /**
     * Verify targeting options for the given block Id
     *
     * @param integer $blockId Block id
     *
     * @return boolean True when all targeting options vaild, false otherwise
     */
    public function checkTargetingOptions($blockId)
    {
        $em = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager();
        $blockRepo = $em->getRepository('\Cx\Modules\Block\Model\Entity\Block');
        $block = $blockRepo->findOneBy(array('id' => $blockId));
        $targetingOptions = $block->getTargetingOptions();

        if (empty($targetingOptions)) {
            return true;
        }

        foreach ($targetingOptions as $targetingOption) {
            switch ($targetingOption->getType()) {
                case 'country':
                    if (!$this->checkTargetingCountry($targetingOption->getFilter(), $targetingOption->getValue())) {
                        return false;
                    }
                    break;
                default:
                    break;
            }
        }
        return true;
    }

    /**
     * Check Country targeting option
     *
     * @param string $filter include => client country should exists in given country ids
     *                            exclude => client country should not exists in given country ids
     * @param array $countryIds Country ids to match
     *
     * @return boolean True when targeting country option matching to client country
     *                 False otherwise
     */
    public function checkTargetingCountry($filter, $countryIds)
    {
        // getClient country using GeoIp component
        $geoIpComponentController = $this->getGeoIpComponent();
        if (!$geoIpComponentController) {
            return false;
        }

        $clientRecord = $geoIpComponentController->getClientRecord();
        if (!$clientRecord) {
            return false;
        }
        $clientCountryAlpha2 = $clientRecord->country->isoCode;
        $clientCountryId = \Cx\Core\Country\Controller\Country::getIdByAlpha2($clientCountryAlpha2);

        $isCountryExists = in_array($clientCountryId, $countryIds);
        if ($filter == 'include') {
            return $isCountryExists;
        } else {
            return !$isCountryExists;
        }
    }

    /**
     * Get block
     *
     * Return a block
     *
     * @access private
     * @param integer $id
     * @return mixed content on success, false on failure
     */
    function _getBlock($id)
    {
        $em = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager();
        $blockRepo = $em->getRepository('\Cx\Modules\Block\Model\Entity\Block');
        $block = $blockRepo->findOneBy(array('id' => $id));

        if ($block) {
            $arrContent = array();
            $arrActive = array();

            $relLangContentRepo = $em->getRepository('\Cx\Modules\Block\Model\Entity\RelLangContent');
            $relLangContents = $relLangContentRepo->findBy(array('block' => $block));
            foreach ($relLangContents as $relLangContent) {
                $arrContent[$relLangContent->getLocale()->getId()] = $relLangContent->getContent();
                $arrActive[$relLangContent->getLocale()->getId()] = $relLangContent->getActive();
            }

            $catId = 0;
            $cat = $block->getCategory();
            if ($cat) {
                $catId = $cat->getId();
            }

            return array(
                'cat' => $catId,
                'start' => $block->getStart(),
                'end' => $block->getEnd(),
                'random' => $block->getRandom(),
                'random2' => $block->getRandom2(),
                'random3' => $block->getRandom3(),
                'random4' => $block->getRandom4(),
                'global' => $block->getShowInGlobal(),
                'direct' => $block->getShowInDirect(),
                'category' => $block->getShowInCategory(),
                'active' => $block->getActive(),
                'name' => $block->getName(),
                'wysiwyg_editor' => $block->getWysiwygEditor(),
                'content' => $arrContent,
                'lang_active' => $arrActive,
            );
        }

        return false;
    }

    /**
     * Get the associated pages for a placeholder
     *
     * @param int $blockId block id
     * @param string $placeholder
     * @return array
     */
    function _getAssociatedPageIds($blockId, $placeholder)
    {
        $em = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager();
        $relPageRepo = $em->getRepository('\Cx\Modules\Block\Model\Entity\RelPage');
        $blockRepo = $em->getRepository('\Cx\Modules\Block\Model\Entity\Block');

        $block = $blockRepo->findOneBy(array('id' => $blockId));
        $relPages = $relPageRepo->findBy(array(
            'block' => $block,
            'placeholder' => $placeholder,
        ));

        $arrPageIds = array();
        foreach ($relPages as $relPage) {
            array_push($arrPageIds, $relPage->getPage()->getId());
        }

        return $arrPageIds;
    }

    function _getBlocksForPage($page)
    {
        $em = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager();

        $qb = $em->createQueryBuilder();
        $blocks = $qb->select('
                b.id,
                IDENTITY(b.category) as category,
                b.name,
                b.start,
                b.end,
                b.order,
                b.random,
                b.random2,
                b.random3,
                b.random4,
                b.showInGlobal,
                b.showInDirect,
                b.showInCategory,
                b.active
            ')
            ->from('\Cx\Modules\Block\Model\Entity\Block', 'b')
            ->from('\Cx\Modules\Block\Model\Entity\RelPage', 'rp')
            ->where('b = rp.block')
            ->andWhere('rp.page = :page')
            ->andWhere('rp.placeholder = \'global\'')
            ->groupBy('b.id')
            ->setParameter('page', $page)
            ->getQuery()
            ->getResult();

        $arrBlocks = array();
        foreach ($blocks as $block) {
            $arrBlocks[$block['id']] = array(
                'cat' => $block['category'],
                'start' => $block['start'],
                'end' => $block['end'],
                'order' => $block['order'],
                'random' => $block['random'],
                'random2' => $block['random2'],
                'random3' => $block['random3'],
                'random4' => $block['random4'],
                'global' => $block['showInGlobal'],
                'direct' => $block['showInDirect'],
                'category' => $block['showInCategory'],
                'active' => $block['active'],
                'name' => $block['name'],
            );
        }

        return $arrBlocks;
    }

    function _setBlocksForPage($page, $blockIds)
    {
        $em = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager();
        $blockRepo = $em->getRepository('\Cx\Modules\Block\Model\Entity\Block');
        $relPageRepo = $em->getRepository('\Cx\Modules\Block\Model\Entity\RelPage');

        $relPages = $relPageRepo->findBy(array(
            'page' => $page,
            'placeholder' => 'global',
        ));
        foreach ($relPages as $relPage) {
            $em->remove($relPage);
        }

        $blocks = array();
        foreach ($blockIds as $blockId) {
            $block = $blockRepo->findOneBy(array('id' => $blockId));
            array_push($blocks, $block);

            // block is global and will be shown on all pages, don't need to save the relation
            if ($block->getShowInGlobal() == 1) {
                continue;
            }

            $relPage = new \Cx\Modules\Block\Model\Entity\RelPage();
            $relPage->setBlock($block);
            $relPage->setPage($page);
            $relPage->setPlaceholder('global');
            $em->persist($relPage);

            // if the block was not global till now, make it global
            if ($block->getShowInGlobal() == 0) {
                $block->setShowInGlobal(1);
            }
        }

        $qb = $em->createQueryBuilder();
        $qb->delete('\Cx\Modules\Block\Model\Entity\RelPage', 'rp')
            ->where('rp.placeholder = :placeholder')
            ->andWhere('rp.page = :page')
            ->andWhere($qb->expr()->notIn('rp.block', $blocks))
            ->setParameters(array(
                'placeholder' => 'global',
                'page' => $page,
            ))
            ->getQuery()
            ->getSingleResult();

        foreach ($relPages as $relPage) {
            $em->remove($relPage);
        }
        $em->flush();
    }

    /**
     * Set block
     *
     * Parse the block with the id $id
     *
     * @access private
     * @param object $block
     * @param string &$code
     * @param object $page
     * @global integer
     */
    function _setBlock($block, &$code, $page)
    {
        $now = time();

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $localeRepo = $em->getRepository('\Cx\Core\Locale\Model\Entity\Locale');
        $locale = $localeRepo->findOneBy(array('id' => FRONTEND_LANG_ID));

        $qb = $em->createQueryBuilder();
        $orX = $qb->expr()->orX();
        $qb2 = $em->createQueryBuilder();
        $q = $qb->select('b')
            ->from('\Cx\Modules\Block\Model\Entity\Block', 'b')
            ->from('\Cx\Modules\Block\Model\Entity\RelLangContent', 'rlc')
            ->where('b = :block')
            /*->andWhere(
                $orX->addMultiple(array(
                    'b.showInDirect = 0',
                    $qb2->select('count(rp)')
                        ->from('\Cx\Modules\Block\Model\Entity\Block', 'b')
                        ->from('\Cx\Modules\Block\Model\Entity\RelPage', 'rp')
                        ->where('rp.page = :page')
                        ->andWhere('rp.block = b')
                        ->andWhere('rp.placeholder = \'direct\'')
                        ->setParameter('page', $page)
                        ->getQuery()
                        ->getSingleResult()[1] . ' > 0'
                ))
            )*/
            ->andWhere('rlc.block = b')
            ->andWhere('(rlc.locale = :locale AND rlc.active = 1)');

        // Note: This is a workaround as content panes are no real widgets yet.
        //
        // In case the frontend editing is not in use
        // then we can always load the content pane.
        // The JsonData adapter will then decide if the content pane is active or not.
        // The check for frontend editing is required, as otherwise the frontend editing
        // would inject an empty DIV element in  case the content pane is inactive.
        if ($cx->getComponent('FrontendEditing')->frontendEditingIsActive(false, false)) {
            $q->andWhere('(b.start <= :now OR b.start = 0)')
                ->andWhere('(b.end >= :now OR b.end = 0)')
                ->andWhere('b.active = 1');
        }
        // End of workaround
        $q->setParameters(array(
                'block' => $block,
                'locale' => $locale,
                'now' => $now,
            ));
        $allBlocks = $q->getQuery()->getResult();

        // filter blocks by assigned content page
        $blocks = array();
        foreach ($allBlocks as $block) {
            if ($block->getShowInDirect()) {
                foreach ($block->getRelPages() as $relPage) {
                    if ($relPage->getPlaceholder() != 'direct') {
                        continue;
                    }
                    if ($relPage->getPage()->getId() == $page->getId()) {
                        $blocks[] = array('id' => $block->getId());
                        continue;
                    }
                }
                continue;
            }
            $blocks[] = array('id' => $block->getId());
        }

        $this->replaceBlocks(
            $this->blockNamePrefix . $block->getId(),
            $blocks,
            $page,
            $code
        );
    }

    /**
     * Set category block
     *
     * Parse the category block with the id $id
     *
     * @access private
     * @param integer $id Category ID
     * @param string &$code
     * @param int $pageId
     * @global integer
     */
    function _setCategoryBlock($category, &$code, $page)
    {
        $now = time();

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $localeRepo = $em->getRepository('\Cx\Core\Locale\Model\Entity\Locale');
        $locale = $localeRepo->findOneBy(array('id' => FRONTEND_LANG_ID));

        $qb = $em->createQueryBuilder();
        $orX = $qb->expr()->orX();
        $qb2 = $em->createQueryBuilder();
        $q = $qb->select('b')
            ->from('\Cx\Modules\Block\Model\Entity\Block', 'b')
            ->innerJoin('\Cx\Modules\Block\Model\Entity\RelLangContent', 'rlc', 'WITH', 'rlc.block = b')
            ->where('b.category = :category')
            /*->andWhere(
                $orX->addMultiple(array(
                    'b.showInCategory = 0',
                    $qb2->select('count(rp)')
                        ->from('\Cx\Modules\Block\Model\Entity\RelPage', 'rp')
                        ->where('rp.block = b')
                        ->andWhere('rp.page = :page')
                        ->andWhere('rp.placeholder = \'category\'')
                        ->setParameter('page', $page)
                        ->getDQL()
                        ->getSingleResult()[1] . ' > 0'
                ))
            )*/;

        // Note: This is a workaround as content panes are no real widgets yet.
        //
        // In case the frontend editing is not in use and there
        // is no content pane separator for the category defined,
        // then we can load all content panes into the template at once.
        // The JsonData adapter will then decide which content panes to display and
        // which not.
        // The check for frontend editing is required, as otherwise the frontend editing
        // would inject empty DIV elements for non-active content panes.
        // The check for the content pane separator is required, as otherwise
        // the system would output the separator for each non-active content pane.
        if (
            $cx->getComponent('FrontendEditing')->frontendEditingIsActive(false, false) ||
            $separator != ''
        ) {
            $q->andWhere('b.active = 1')
                ->andWhere('(b.start <= :now OR b.start = 0)')
                ->andWhere('(b.end >= :now OR b.end = 0)');
        }
        // End of workaround

        $q->andWhere('(rlc.locale = :locale AND rlc.active = 1)')
            ->orderBy('b.order')
            ->setParameters(array(
                'category' => $category,
                'now' => $now,
                'locale' => $locale,
            ));
        $allBlocks = $q->getQuery()->getResult();

        // filter blocks by assigned content page
        $blocks = array();
        foreach ($allBlocks as $block) {
            if ($block->getShowInCategory()) {
                foreach ($block->getRelPages() as $relPage) {
                    if ($relPage->getPlaceholder() != 'category') {
                        continue;
                    }
                    if ($relPage->getPage()->getId() == $page->getId()) {
                        $blocks[] = array('id' => $block->getId());
                        continue;
                    }
                }
                continue;
            }
            $blocks[] = array('id' => $block->getId());
        }

        $this->replaceBlocks(
            $this->blockNamePrefix . 'CAT_' . $category->getId(),
            $blocks,
            $page,
            $code,
            $category->getSeperator()
        );
    }

    /**
     * Set block Global
     *
     * Parse the block with the id $id
     *
     * @access private
     * @param integer $id
     * @param string &$code
     */
    function _setBlockGlobal(&$code, $page)
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();

        // fetch separator
        \Cx\Core\Setting\Controller\Setting::init('Block', 'setting');
        $separator = \Cx\Core\Setting\Controller\Setting::getValue('blockGlobalSeperator', 'Block');

        $now = time();

        $localeRepo = $em->getRepository('\Cx\Core\Locale\Model\Entity\Locale');
        $locale = $localeRepo->findOneBy(array('id' => FRONTEND_LANG_ID));

        $qb1 = $em->createQueryBuilder();
        $q1 = $qb1->select('
                b.id AS id,
                rlc.content AS content,
                b.order
            ')
            ->from('\Cx\Modules\Block\Model\Entity\Block', 'b')
            ->innerJoin('\Cx\Modules\Block\Model\Entity\RelLangContent', 'rlc', 'WITH', 'rlc.block = b')
            ->innerJoin('\Cx\Modules\Block\Model\Entity\RelPage', 'rp', 'WITH', 'rp.block = b')
            ->where('b.showInGlobal = 2')
            ->andWhere('rp.page = :page')
            ->andWhere('rlc.locale = :locale')
            ->andWhere('rlc.active = 1')
            ->andWhere('rp.placeholder = \'global\'');

        // Note: This is a workaround as content panes are no real widgets yet.
        //
        // In case the frontend editing is not in use and there
        // is no content pane separator for the global content pane defined,
        // then we can load all content panes into the template at once.
        // The JsonData adapter will then decide which content panes to display and
        // which not.
        // The check for frontend editing is required, as otherwise the frontend editing
        // would inject empty DIV elements for non-active content panes.
        // The check for the content pane separator is required, as otherwise
        // the system would output the separator for each non-active content pane.
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        if (
            $cx->getComponent('FrontendEditing')->frontendEditingIsActive(false, false) ||
            $separator != ''
        ) {
            $q1->andWhere('b.active = 1')
                ->andWhere('(b.start <= :now OR b.start = 0)')
                ->andWhere('(b.end >= :now OR b.end = 0)');
        }
        // End of workaround

        $q1->orderBy('b.order')
            ->setParameters(array(
                'locale' => $locale,
                'page' => $page,
                'now' => $now,
            ));
        $result1 = $q1->getQuery()->getResult();

        $qb2 = $em->createQueryBuilder();
        $q2 = $qb2->select('
                b.id AS id,
                rlc.content AS content,
                b.order
            ')
            ->from('\Cx\Modules\Block\Model\Entity\Block', 'b')
            ->innerJoin('\Cx\Modules\Block\Model\Entity\RelLangContent', 'rlc', 'WITH', 'rlc.block = b')
            ->where('b.showInGlobal = 1')
            ->andWhere('rlc.locale = :locale')
            ->andWhere('rlc.active = 1');

        // Note: This is a workaround as content panes are no real widgets yet.
        //
        // In case the frontend editing is not in use and there
        // is no content pane separator for the global content pane defined,
        // then we can load all content panes into the template at once.
        // The JsonData adapter will then decide which content panes to display and
        // which not.
        // The check for frontend editing is required, as otherwise the frontend editing
        // would inject empty DIV elements for non-active content panes.
        // The check for the content pane separator is required, as otherwise
        // the system would output the separator for each non-active content pane.
        if (
            $cx->getComponent('FrontendEditing')->frontendEditingIsActive(false, false) ||
            $separator != ''
        ) {
            $q2->andWhere('b.active = 1')
                ->andWhere('(b.start <= :now OR b.start = 0)')
                ->andWhere('(b.end >= :now OR b.end = 0)');
        }
        // End of workaround

        $q2->orderBy('b.order')
            ->setParameters(array(
                'locale' => $locale,
                'now' => $now,
            ));
        $result2 = $q2->getQuery()->getResult();

        $blocks = array_unique(array_merge($result1, $result2));
        usort($blocks, 'cmpByOrder');

        $this->replaceBlocks(
            $this->blockNamePrefix . 'GLOBAL',
            $blocks,
            $page,
            $code,
            $separator
        );
    }

    /**
     * Compares two arrays by order attribute
     *
     * @param array $a
     * @param array $b
     * @return int relative position
     */
    function cmpByOrder($a, $b)
    {
        if ($a['order'] == $b['order']) {
            return 0;
        }
        return ($a['order'] < $b['order']) ? -1 : 1;
    }

    /**
     * Set block Random
     *
     * Parse the block with the id $id
     *
     * @access private
     * @param integer $id
     * @param string &$code
     * @global integer
     */
    function _setBlockRandom(&$code, $id, $page)
    {
        // Note: known issue:
        // as content panes are not real widgets, the random block does not properly
        // take into account if a content page is active or not
        // Unfortunately, this issue can't be fixed with the current implementation
        // This issue will be fixed once the content panes have been migrated to widgets

        $now = time();

        $em = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager();
        $localeRepo = $em->getRepository('\Cx\Core\Locale\Model\Entity\Locale');
        $locale = $localeRepo->findOneBy(array('id' => FRONTEND_LANG_ID));

        $qb = $em->createQueryBuilder();
        $qb->select('b.id')
            ->from('\Cx\Modules\Block\Model\Entity\Block', 'b')
            ->from('\Cx\Modules\Block\Model\Entity\RelLangContent', 'rlc')
            ->where('rlc.block = b')
            ->andWhere('(rlc.locale = :locale AND rp.active = 1)')
            ->andWhere('(b.start <= :now OR b.start = 0)')
            ->andWhere('(b.end >= :now OR b.end = 0)')
            ->andWhere('b.active = 1');

        // Get Block Name and Status
        $blockNr = '';
        switch ($id) {
            case '1':
                $qb->andWhere('b.random = 1');
                break;
            case '2':
                $qb->andWhere('b.random2 = 1');
                $blockNr = '_2';
                break;
            case '3':
                $qb->andWhere('b.random3 = 1');
                $blockNr = '_3';
                break;
            case '4':
                $qb->andWhere('b.random4 = 1');
                $blockNr = '_4';
                break;
        }

        $blocks = $qb->setParameters(array(
            'locale' => $locale,
            'now' => $now,
        ))->getQuery()->getResult();

        if (count($blocks) <= 0) {
            return;
        }

        foreach ($blocks as $block) {
            $arrActiveBlocks[] = $block['id'];
        }

        $this->replaceBlocks(
            $this->blockNamePrefix . 'RANDOMIZER' . $blockNr,
            $blocks,
            $page,
            $code,
            '',
            true
        );
    }

    /**
     * Replaces a placeholder with block content
     * @param string $placeholderName Name of placeholder to replace
     * @param array $blocks Fetched blocks from database
     * @param object $page Current page, NULL if no page available
     * @param string $code (by reference) Code to replace placeholder in
     * @param string $separator (optional) Separator used to separate the blocks
     * @param boolean $randomize (optional) Wheter to randomize the blocks or not, default false
     */
    protected function replaceBlocks($placeholderName, $blocks, $page, &$code, $separator = '', $randomize = false)
    {
        // find all block IDs to parse
        if (count($blocks) <= 0) {
            // drop empty placeholders
            $code = str_replace('{' . $placeholderName . '}', '', $code);
            return;
        }
        $blockIds = array();
        foreach ($blocks as $block) {
            if (!$this->checkTargetingOptions($block['id'])) {
                continue;
            }
            $blockIds[] = $block['id'];
        }

        $pageId = 0;
        if ($page) {
            $pageId = $page->getId();
        }

        // parse
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $systemComponentRepo = $em->getRepository('Cx\Core\Core\Model\Entity\SystemComponent');
        $frontendEditingComponent = $systemComponentRepo->findOneBy(array('name' => 'FrontendEditing'));

        if ($randomize) {
            $esiBlockInfos = array();
            foreach ($blockIds as $blockId) {
                $esiBlockInfos[] = array(
                    'Block',
                    'getBlockContent',
                    array(
                        'block' => $blockId,
                        'lang' => \FWLanguage::getLanguageCodeById(FRONTEND_LANG_ID),
                        'page' => $pageId,
                    )
                );
            }
            $blockContent = $cx->getComponent('Cache')->getRandomizedEsiContent(
                $esiBlockInfos
            );
            $frontendEditingComponent->prepareBlock(
                $blockId,
                $blockContent
            );
            $content = $blockContent;
        } else {
            $contentList = array();
            foreach ($blockIds as $blockId) {
                $blockContent = $cx->getComponent('Cache')->getEsiContent(
                    'Block',
                    'getBlockContent',
                    array(
                        'block' => $blockId,
                        'lang' => \FWLanguage::getLanguageCodeById(FRONTEND_LANG_ID),
                        'page' => $pageId,
                    )
                );
                $frontendEditingComponent->prepareBlock(
                    $blockId,
                    $blockContent
                );
                $contentList[] = $blockContent;
            }
            $content = implode($separator, $contentList);
        }

        \Cx\Core\Setting\Controller\Setting::init('Block', 'setting');
        $markParsedBlock = \Cx\Core\Setting\Controller\Setting::getValue('markParsedBlock', 'Block');
        if (!empty($markParsedBlock)) {
            $content = "<!-- start $placeholderName -->$content<!-- end $placeholderName -->";
        }

        $code = str_replace('{' . $placeholderName . '}', $content, $code);
    }

    /**
     * Save the settings associated to the block system
     *
     * @access    private
     * @param    array $arrSettings
     */
    function _saveSettings($arrSettings)
    {
        \Cx\Core\Setting\Controller\Setting::init('Config', 'component', 'Yaml');
        if (isset($arrSettings['blockStatus'])) {
            if (!\Cx\Core\Setting\Controller\Setting::isDefined('blockStatus')) {
                \Cx\Core\Setting\Controller\Setting::add('blockStatus', $arrSettings['blockStatus'], 1,
                    \Cx\Core\Setting\Controller\Setting::TYPE_RADIO, '1:TXT_ACTIVATED,0:TXT_DEACTIVATED', 'component');
            } else {
                \Cx\Core\Setting\Controller\Setting::set('blockStatus', $arrSettings['blockStatus']);
                \Cx\Core\Setting\Controller\Setting::update('blockStatus');
            }
        }
        if (isset($arrSettings['blockRandom'])) {
            if (!\Cx\Core\Setting\Controller\Setting::isDefined('blockRandom')) {
                \Cx\Core\Setting\Controller\Setting::add('blockRandom', $arrSettings['blockRandom'], 1,
                    \Cx\Core\Setting\Controller\Setting::TYPE_RADIO, '1:TXT_ACTIVATED,0:TXT_DEACTIVATED', 'component');
            } else {
                \Cx\Core\Setting\Controller\Setting::set('blockRandom', $arrSettings['blockRandom']);
                \Cx\Core\Setting\Controller\Setting::update('blockRandom');
            }
        }
    }

    /**
     * create the categories dropdown
     *
     * @param array $arrCategories
     * @param array $arrOptions
     * @param integer $level
     * @return string categories as HTML options
     */
    function _getCategoriesDropdown($parent = 0, $catId = 0, $arrCategories = array(), $arrOptions = array(), $level = 0)
    {
        $first = false;
        if (count($arrCategories) == 0) {
            $first = true;
            $level = 0;
            $this->_getCategories();
            $arrCategories = $this->_categories[0]; //first array contains all root categories (parent id 0)
        }

        foreach ($arrCategories as $arrCategory) {
            $this->_categoryOptions[] =
                '<option value="' . $arrCategory['id'] . '" '
                . (
                $parent > 0 && $parent == $arrCategory['id']  //selected if parent specified and id is parent
                    ? 'selected="selected"'
                    : ''
                )
                . (
                ($catId > 0 && in_array($arrCategory['id'], $this->_getChildCategories($catId))) || $catId == $arrCategory['id'] //disable children and self
                    ? 'disabled="disabled"'
                    : ''
                )
                . ' >' // <option>
                . str_repeat('&nbsp;', $level * 4)
                . htmlentities($arrCategory['name'], ENT_QUOTES, CONTREXX_CHARSET)
                . '</option>';

            if (!empty($this->_categories[$arrCategory['id']])) {
                $this->_getCategoriesDropdown($parent, $catId, $this->_categories[$arrCategory['id']], $arrOptions, $level + 1);
            }
        }
        if ($first) {
            return implode("\n", $this->_categoryOptions);
        }
    }

    /**
     * save a block category
     *
     * @param integer $id
     * @param integer $parent
     * @param string $name
     * @param string $seperator
     * @param integer $order
     * @param integer $status
     * @throws NotEnoughArgumentsException
     * @throws NoCategoryFoundException
     * @return integer inserted ID or false on failure
     */
    function _saveCategory($id = 0, $parent = 0, $name, $seperator, $order = 1, $status = 1)
    {
        // check for necessary arguments
        if (empty($name)) {
            throw new NotEnoughArgumentsException('not enough arguments');
        }

        if ($id > 0 && $id == $parent) { //don't allow category to attach to itself
            return false;
        }

        if ($id == 0) { //if new record then set to NULL for auto increment
            $id = 'NULL';
        } else {
            $arrChildren = $this->_getChildCategories($id);
            if (in_array($parent, $arrChildren)) { //don't allow category to be attached to one of it's own children
                return false;
            }
        }
        $name = contrexx_addslashes($name);
        $seperator = contrexx_addslashes($seperator);

        $em = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager();
        $categoryRepo = $em->getRepository('\Cx\Modules\Block\Model\Entity\Category');
        $category = $categoryRepo->findOneBy(array('id' => $id));

        $new = false;
        if (!$category) {
            if ($id > 0) {
                throw new NoCategoryFoundException('no category found');
            }
            $category = new \Cx\Modules\Block\Model\Entity\Category();
            $new = true;
        }

        $parent = $categoryRepo->findOneBy(array('id' => $parent));
        $category->setParent($parent);
        $category->setName($name);
        $category->setSeperator($seperator);
        $category->setOrder($order);
        $category->setStatus($status);

        if ($new) {
            $em->persist($category);
            $em->flush();
            $em->refresh($category);
        } else {
            $em->flush();
        }

        return $category->getId();
    }

    /**
     * return all child caegories of a cateogory
     *
     * @param integer ID of category to get list of children from
     * @param array cumulates the child arrays, internal use
     * @return array IDs of children
     */
    function _getChildCategories($id, &$_arrChildCategories = array())
    {
        if (empty($this->_categories)) {
            $this->_getCategories();
        }
        if (!isset($this->_categories[$id])) {
            return array();
        }
        foreach ($this->_categories[$id] as $cat) {
            if (!empty($this->_categories[$cat['parent']])) {
                $_arrChildCategories[] = $cat['id'];
                $this->_getChildCategories($cat['id'], $_arrChildCategories);
            }

        }
        return $_arrChildCategories;
    }

    /**
     * delete a category by id
     *
     * @param integer $id category id
     * @throws NotEnoughArgumentsException
     * @throws NoCategoryFoundException
     * @return bool success
     */
    function _deleteCategory($id = 0)
    {
        $em = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager();
        $categoryRepo = $em->getRepository('\Cx\Modules\Block\Model\Entity\Category');
        $blockRepo = $em->getRepository('\Cx\Modules\Block\Model\Entity\Block');

        if ($id < 1) {
            throw new NotEnoughArgumentsException('not enough arguments');
        }

        $category = $categoryRepo->findOneBy(array('id' => $id));
        if (!$category) {
            throw new NoCategoryFoundException('no category found');
        }

        $categoryParent = $categoryRepo->findOneBy(array('parent' => $id));
        if ($categoryParent) {
            $categoryParent->setParent(null);
        }

        $blocks = $blockRepo->findBy(array('category' => $category));
        foreach ($blocks as $block) {
            $block->setCategory(null);
        }
        $em->remove($category);

        return true;
    }

    /**
     * fill and/or return the categories array
     *
     * category arrays are put in the array as first dimension elements, with their parent as key, as follows:
     * $this->_categories[$objRS->fields['parent']][] = $objRS->fields;
     *
     * just to make this clear:
     * note that $somearray['somekey'][] = $foo adds $foo to $somearray['somekey'] rather than overwriting it.
     *
     * @param bool force refresh from DB
     * @see blockManager::_parseCategories for parse example
     * @see blockLibrary::_getCategoriesDropdown for parse example
     * @global array
     * @return array all available categories
     */
    function _getCategories($refresh = false)
    {
        global $_ARRAYLANG;

        $em = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager();

        if (!empty($this->_categories) && !$refresh) {
            return $this->_categories;
        }

        $this->_categories = array(0 => array());
        $this->_categoryNames[0] = $_ARRAYLANG['TXT_BLOCK_NONE'];

        $categoryRepo = $em->getRepository('Cx\Modules\Block\Model\Entity\Category');
        $categories = $categoryRepo->findBy(array(), array('order' => 'ASC', 'id' => 'ASC'));

        foreach ($categories as $category) {
            $parentId = 0;
            $parent = $category->getParent();
            if ($parent) {
                $parentId = $parent->getId();
            }
            $this->_categories[$parentId][] = array(
                'id' => $category->getId(),
                'parent' => $parentId,
                'name' => $category->getName(),
                'order' => $category->getOrder(),
                'status' => $category->getStatus(),
                'seperator' => $category->getSeperator(),
            );
            $this->_categoryNames[$category->getId()] = $category->getName();
        }

        return $this->_categories;
    }

    /**
     * Replace the block placeholders by their associated content
     * within a supplied string
     *
     * @todo    Remove once the content panes have been mirated to widgets
     * @param   string  &$content    The content to replace all block placeholders by their content in
     */
    static function replacePlaceholdersInContent(&$content) {
        // functionality is only available in frontend -> abort if we're not in frontend mode
        if (\Cx\Core\Core\Controller\Cx::instanciate()->getMode() != \Cx\Core\Core\Controller\Cx::MODE_FRONTEND) {
            return;
        }

        // only proceed if the block system is active
        $config = \Env::get('config');
        if (!$config['blockStatus']) {
            return;
        }

        // the block assignements are based on content pages, therefore it's
        // necessarily that a content page has been requested and loaded to proceed
        if (!\Cx\Core\Core\Controller\Cx::instanciate()->getPage()) {
            return;
        }

        // finally, replace the placeholders by their associated blocks
        \Cx\Modules\Block\Controller\Block::setBlocks($content, \Cx\Core\Core\Controller\Cx::instanciate()->getPage());
    }
}
