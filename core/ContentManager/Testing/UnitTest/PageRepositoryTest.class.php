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
 * PageRepositoryTest
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @author      SS4U <ss4u.comvation@gmail.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  core_contentmanager
 */

namespace Cx\Core\ContentManager\Testing\UnitTest;

/**
 * PageRepositoryTest
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @author      SS4U <ss4u.comvation@gmail.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  core_contentmanager
 */
class PageRepositoryTest extends \Cx\Core\Test\Model\Entity\DoctrineTestCase
{
    public function testPagesAtPath() {

        $pageRepo = self::$em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
        $nodeRepo = self::$em->getRepository('Cx\Core\ContentManager\Model\Entity\Node');

        $n1 = new \Cx\Core\ContentManager\Model\Entity\Node();
        $n2 = new \Cx\Core\ContentManager\Model\Entity\Node();
        $n3 = new \Cx\Core\ContentManager\Model\Entity\Node();

        $n1->setParent($nodeRepo->getRoot());
        $nodeRepo->getRoot()->addChildren($n1);
        $n2->setParent($n1);
        $n2->addChildren($n1);
        $n3->setParent($nodeRepo->getRoot());
        $nodeRepo->getRoot()->addChildren($n3);

        self::$em->persist($n1);
        self::$em->persist($n2);
        self::$em->persist($n3);
        self::$em->flush();

        $p1 = new \Cx\Core\ContentManager\Model\Entity\Page();
        $p1->setLang(1);
        $p1->setTitle('rootTitle_1');
        $p1->setNode($n1);
        $p1->setNodeIdShadowed($n1->getId());
        $p1->setUseCustomContentForAllChannels('');
        $p1->setUseCustomApplicationTemplateForAllChannels('');
        $p1->setUseSkinForAllChannels('');
        $p1->setCmd('');
        $p1->setActive(1);

        $p2 = new \Cx\Core\ContentManager\Model\Entity\Page();
        $p2->setLang(2);
        $p2->setTitle('rootTitle_1');
        $p2->setNode($n1);
        $p2->setNodeIdShadowed($n1->getId());
        $p2->setUseCustomContentForAllChannels('');
        $p2->setUseCustomApplicationTemplateForAllChannels('');
        $p2->setUseSkinForAllChannels('');
        $p2->setCmd('');
        $p2->setActive(1);

        $p3 = new \Cx\Core\ContentManager\Model\Entity\Page();
        $p3->setLang(3);
        $p3->setTitle('rootTitle_2');
        $p3->setNode($n1);
        $p3->setNodeIdShadowed($n1->getId());
        $p3->setUseCustomContentForAllChannels('');
        $p3->setUseCustomApplicationTemplateForAllChannels('');
        $p3->setUseSkinForAllChannels('');
        $p3->setCmd('');
        $p3->setActive(1);


        $p4 = new \Cx\Core\ContentManager\Model\Entity\Page();
        $p4->setLang(3);
        $p4->setTitle('childTitle');
        $p4->setNode($n2);
        $p4->setNodeIdShadowed($n2->getId());
        $p4->setUseCustomContentForAllChannels('');
        $p4->setUseCustomApplicationTemplateForAllChannels('');
        $p4->setUseSkinForAllChannels('');
        $p4->setCmd('');
        $p4->setActive(1);

        $p5 = new \Cx\Core\ContentManager\Model\Entity\Page();
        $p5->setLang(1);
        $p5->setTitle('otherRootChild');
        $p5->setNode($n3);
        $p5->setNodeIdShadowed($n3->getId());
        $p5->setUseCustomContentForAllChannels('');
        $p5->setUseCustomApplicationTemplateForAllChannels('');
        $p5->setUseSkinForAllChannels('');
        $p5->setCmd('');
        $p5->setActive(1);


        self::$em->persist($n1);
        self::$em->persist($n2);
        self::$em->persist($n3);

        self::$em->persist($p1);
        self::$em->persist($p2);
        self::$em->persist($p3);
        self::$em->persist($p4);
        self::$em->persist($p5);

        self::$em->flush();

        //make sure we re-fetch a correct state
        self::$em->refresh($n1);
        self::$em->refresh($n2);
        self::$em->refresh($n3);

        //1 level
        $match = $pageRepo->getPagesAtPath('/'. \FWLanguage::getLanguageCodeById(1) .'/rootTitle_1');
        $this->assertEquals('rootTitle_1/',$match['matchedPath']);
        $this->assertInstanceOf('Cx\Core\ContentManager\Model\Entity\Page',$match['page'][1]);
        // $this->assertEquals(array(1,2),$match['lang']);

        //2 levels
        $match = $pageRepo->getPagesAtPath('/'. \FWLanguage::getLanguageCodeById(3) .'/rootTitle_2/childTitle');
        $this->assertEquals('rootTitle_2/childTitle/',$match['matchedPath']);
        $this->assertEquals('',$match['unmatchedPath']);
        $this->assertInstanceOf('Cx\Core\ContentManager\Model\Entity\Page',$match['page'][3]);
        $this->assertEquals(array(3),$match['lang']);

        //3 levels, 2 in tree
        $match = $pageRepo->getPagesAtPath('/'. \FWLanguage::getLanguageCodeById(3) .'/rootTitle_2/childTitle/asdfasdf');
        $this->assertEquals('rootTitle_2/childTitle/',$match['matchedPath']);
        // check unmatched path too
        $this->assertEquals('asdfasdf',$match['unmatchedPath']);
        $this->assertInstanceOf('Cx\Core\ContentManager\Model\Entity\Page',$match['page'][3]);
        $this->assertEquals(array(3),$match['lang']);

        //3 levels, wrong lang from 2nd level
        $match = $pageRepo->getPagesAtPath('/'. \FWLanguage::getLanguageCodeById(1) .'/rootTitle_1/childTitle/asdfasdf');
        $this->assertEquals('rootTitle_1/',$match['matchedPath']);
        $this->assertInstanceOf('Cx\Core\ContentManager\Model\Entity\Page',$match['page'][1]);
        //$this->assertEquals(array(1,2),$match['lang']);

        //inexistant
        $match = $pageRepo->getPagesAtPath('doesNotExist');
        $this->assertEquals(null,$match);

        //exact matching
        $match = $pageRepo->getPagesAtPath('rootTitle_2/childTitle/asdfasdf', null, null, true);
        $this->assertEquals(null,$match);

        //given lang matching
        $match = $pageRepo->getPagesAtPath('/'. \FWLanguage::getLanguageCodeById(1) .'/rootTitle_1', null, 1);
        $this->assertEquals('rootTitle_1/',$match['matchedPath']);
        $this->assertInstanceOf('Cx\Core\ContentManager\Model\Entity\Page',$match['page']);

        //second other child of root node
        $match = $pageRepo->getPagesAtPath('/'. \FWLanguage::getLanguageCodeById(1) .'/otherRootChild', null, 1);
        $this->assertEquals('otherRootChild/',$match['matchedPath']);
        $this->assertInstanceOf('Cx\Core\ContentManager\Model\Entity\Page',$match['page']);
    }

    public function testGetFromModuleCmdByLang() {
        $nodeRepo = self::$em->getRepository('Cx\Core\ContentManager\Model\Entity\Node');
        $pageRepo = self::$em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');

        $n1 = new \Cx\Core\ContentManager\Model\Entity\Node();

        $n1->setParent($nodeRepo->getRoot());
        $nodeRepo->getRoot()->addChildren($n1);

        self::$em->persist($n1);
        self::$em->flush();

        $p1 = new \Cx\Core\ContentManager\Model\Entity\Page();
        $p1->setLang(1);
        $p1->setTitle('rootTitle_1');
        $p1->setNode($n1);
        $p1->setType(\Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION);
        $p1->setModule('myModule');
        $p1->setCmd('cmd1');
        $p1->setNodeIdShadowed($n1->getId());
        $p1->setUseCustomContentForAllChannels('');
        $p1->setUseCustomApplicationTemplateForAllChannels('');
        $p1->setUseSkinForAllChannels('');
        $p1->setActive(1);

        $p2 = new \Cx\Core\ContentManager\Model\Entity\Page();
        $p2->setLang(2);
        $p2->setTitle('rootTitle_1');
        $p2->setNode($n1);
        $p2->setType(\Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION);
        $p2->setModule('myModule');
        $p2->setCmd('cmd1');
        $p2->setNodeIdShadowed($n1->getId());
        $p2->setUseCustomContentForAllChannels('');
        $p2->setUseCustomApplicationTemplateForAllChannels('');
        $p2->setUseSkinForAllChannels('');
        $p2->setActive(1);

        // French is inactive
        $p3 = new \Cx\Core\ContentManager\Model\Entity\Page();
        $p3->setLang(3);
        $p3->setTitle('rootTitle_2');
        $p3->setNode($n1);
        $p3->setType(\Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION);
        $p3->setModule('myModule');
        $p3->setCmd('cmd2');
        $p3->setNodeIdShadowed($n1->getId());
        $p3->setUseCustomContentForAllChannels('');
        $p3->setUseCustomApplicationTemplateForAllChannels('');
        $p3->setUseSkinForAllChannels('');


        self::$em->persist($n1);

        self::$em->persist($p1);
        self::$em->persist($p2);
        // French is inactive
        //self::$em->persist($p3);

        self::$em->flush();

        //make sure we re-fetch a correct state
        self::$em->refresh($n1);

        //test correct fetching
        $pages = $pageRepo->getFromModuleCmdByLang('myModule');

        $this->assertArrayHasKey(1,$pages);
        $this->assertArrayHasKey(2,$pages);
        //$this->assertArrayHasKey(3,$pages);

        $this->assertInstanceOf('Cx\Core\ContentManager\Model\Entity\Page', $pages[1]);
        $this->assertInstanceOf('Cx\Core\ContentManager\Model\Entity\Page', $pages[2]);
        //$this->assertInstanceOf('Cx\Core\ContentManager\Model\Entity\Page', $pages[3]);

        $this->assertEquals(1, $pages[1]->getLang());
        $this->assertEquals(2, $pages[2]->getLang());
        //$this->assertEquals(3, $pages[3]->getLang());

        //test behaviour on specified cmd
        $pages = $pageRepo->getFromModuleCmdByLang('myModule', 'cmd1');
        $this->assertEquals(2, count($pages));
    }

    public function testGetPathToPage() {
        $nodeRepo = self::$em->getRepository('Cx\Core\ContentManager\Model\Entity\Node');

        $n1 = new \Cx\Core\ContentManager\Model\Entity\Node();
        $n1->setParent($nodeRepo->getRoot());
        $nodeRepo->getRoot()->addChildren($n1);
        $n2 = new \Cx\Core\ContentManager\Model\Entity\Node();
        $n2->setParent($n1);
        $n1->addChildren($n2);

        self::$em->persist($n1);
        self::$em->persist($n2);
        self::$em->flush();

        $p1 = new \Cx\Core\ContentManager\Model\Entity\Page();
        $p1->setLang(1);
        $p1->setTitle('root');
        $p1->setNode($n1);
        $p1->setNodeIdShadowed($n1->getId());
        $p1->setUseCustomContentForAllChannels('');
        $p1->setUseCustomApplicationTemplateForAllChannels('');
        $p1->setUseSkinForAllChannels('');
        $p1->setCmd('');
        $p1->setActive(1);

        $p2 = new \Cx\Core\ContentManager\Model\Entity\Page();
        $p2->setLang(1);
        $p2->setTitle('child page');
        $p2->setNode($n2);
        $p2->setNodeIdShadowed($n2->getId());
        $p2->setUseCustomContentForAllChannels('');
        $p2->setUseCustomApplicationTemplateForAllChannels('');
        $p2->setUseSkinForAllChannels('');
        $p2->setCmd('');
        $p2->setActive(1);

        self::$em->persist($n1);
        self::$em->persist($n2);

        self::$em->persist($p1);
        self::$em->persist($p2);

        self::$em->flush();

        $pageId = $p2->getId();

        \Env::get('em')->refresh($n1);

        //make sure we re-fetch a correct state
        self::$em->getRepository('Cx\Core\ContentManager\Model\Entity\Node')->verify();

        $pageRepo = self::$em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');

        $page = $pageRepo->findOneById($pageId);
        $this->assertEquals('root/child-page', $pageRepo->getPath($page));
    }
}
