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
 * PageTest
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
 * PageTest
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @author      SS4U <ss4u.comvation@gmail.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  core_contentmanager
 */
class PageTest extends \Cx\Core\Test\Model\Entity\DoctrineTestCase
{
    public function testValidation() {
        $nodeRepo = self::$em->getRepository('Cx\Core\ContentManager\Model\Entity\Node');

        $node = new \Cx\Core\ContentManager\Model\Entity\Node();
        $node->setParent($nodeRepo->getRoot());
        $nodeRepo->getRoot()->addChildren($node);

        self::$em->persist($node);
        self::$em->flush();

        $p = new \Cx\Core\ContentManager\Model\Entity\Page();

        $p->setLang(1);
        $p->setTitle('testpage');
        $p->setNode($node);
        $p->setNodeIdShadowed($node->getId());
        $p->setUseCustomContentForAllChannels('');
        $p->setUseCustomApplicationTemplateForAllChannels('');
        $p->setUseSkinForAllChannels('');
        $p->setCmd('should_be_valid');

        //shouldn't raise a ValidationException
        self::$em->persist($node);
        self::$em->persist($p);
        self::$em->flush();
    }

    public function testLoggable() {
        $nodeRepo = self::$em->getRepository('Cx\Core\ContentManager\Model\Entity\Node');

        $n = new \Cx\Core\ContentManager\Model\Entity\Node();

        $n->setParent($nodeRepo->getRoot());
        $nodeRepo->getRoot()->addChildren($n);

        self::$em->persist($n);
        self::$em->flush();

        $p = new \Cx\Core\ContentManager\Model\Entity\Page();

        $p->setLang(1);
        $p->setTitle('testpage');
        $p->setNode($n);
        $p->setNodeIdShadowed($n->getId());
        $p->setUseCustomContentForAllChannels('');
        $p->setUseCustomApplicationTemplateForAllChannels('');
        $p->setUseSkinForAllChannels('');
        $p->setCmd('');


        self::$em->persist($n);
        self::$em->persist($p);
        self::$em->flush();

        //now, create a log
        $p->setTitle('testpage_changed');
        self::$em->persist($p);

        self::$em->flush();

        //now, agiiin
        $p->setTitle('testpage_changed_2');
        self::$em->persist($p);

        self::$em->flush();

        $repo = self::$em->getRepository('Cx\Core\ContentManager\Model\Entity\LogEntry'); // we use default log entry class

        $this->assertEquals('testpage_changed_2',$p->getTitle());
        $repo->revert($p,1);
        $this->assertEquals('testpage',$p->getTitle());
        $repo->revert($p,2);
        $this->assertEquals('testpage_changed',$p->getTitle());
        $repo->revert($p,1);
        $this->assertEquals('testpage',$p->getTitle());
    }

    public function testSlugGeneration() {
        $p = new \Cx\Core\ContentManager\Model\Entity\Page();
        $p->setTitle('test');
        $this->assertEquals('test', $p->getSlug());

        $p = new \Cx\Core\ContentManager\Model\Entity\Page();
        $p->setTitle('test with space');
        $this->assertEquals('test-with-space', $p->getSlug());

        $p = new \Cx\Core\ContentManager\Model\Entity\Page();
        $p->setTitle('test ümläut');
        $this->assertEquals('test-uemlaeut', $p->getSlug());

        $p = new \Cx\Core\ContentManager\Model\Entity\Page();
        $p->setTitle('123');
        $this->assertEquals('123', $p->getSlug());
    }

    public function testImplicitExplicitSlugSetting() {
        $p = new \Cx\Core\ContentManager\Model\Entity\Page();
        $p->setTitle('test');
        $this->assertEquals('test', $p->getSlug());

        $p->setTitle('thisshouldntaffecttheslug');
        $this->assertEquals('test', $p->getSlug());

        $p->setSlug('butThisShould');
        $this->assertEquals('butThisShould', $p->getSlug());
    }

    public function testTargetProperties() {
        $p = new \Cx\Core\ContentManager\Model\Entity\Page();
        $p->setTarget('[[NODE_12]]querystring');
        $this->assertEquals(1, $p->isTargetInternal());
        $this->assertEquals(12, $p->getTargetNodeId());
        $this->assertEquals(0, $p->getTargetLangId());
        $this->assertEquals('querystring', $p->getTargetQueryString());

        $p->setTarget('[[NODE_12_1]]querystring');
        $this->assertEquals(1, $p->isTargetInternal());
        $this->assertEquals(12, $p->getTargetNodeId());
        $this->assertEquals(1, $p->getTargetLangId());
        $this->assertEquals('querystring', $p->getTargetQueryString());

        $p->setTarget('http://www.example.com');
        $this->assertEquals(0, $p->isTargetInternal());
        $this->assertEquals(0, $p->getTargetNodeId());
        $this->assertEquals(0, $p->getTargetLangId());
        $this->assertEquals(null, $p->getTargetQueryString());
    }

    public function testProtectionProperties() {
        $p = new \Cx\Core\ContentManager\Model\Entity\Page();

        //currently untested because set(Front|Backend)Protection() call static Permission-methods.

        /*        $this->assertEquals(false, $p->isFrontendProtected());
        $this->assertEquals(false, $p->isBackendProtected());

        $p->setFrontendProtection(true);
        $this->assertEquals(true, $p->isFrontendProtected());
        $this->assertEquals(false, $p->isBackendProtected());

        $p->setFrontendProtection(false);
        $p->setBackendProtection(true);
        $this->assertEquals(false, $p->isFrontendProtected());
        $this->assertEquals(true, $p->isBackendProtected());*/
    }

    public function testGetURL() {
        $nodeRepo = self::$em->getRepository('Cx\Core\ContentManager\Model\Entity\Node');

        $n1 = new \Cx\Core\ContentManager\Model\Entity\Node();
        $n1->setParent($nodeRepo->getRoot());
        $nodeRepo->getRoot()->addChildren($n1);

        self::$em->persist($n1);
        self::$em->flush();

        $p1 = new \Cx\Core\ContentManager\Model\Entity\Page();

        $p1->setLang(1);
        $p1->setTitle('testgeturl');
        $p1->setNode($n1);
        $p1->setNodeIdShadowed($n1->getId());
        $p1->setUseCustomContentForAllChannels('');
        $p1->setUseCustomApplicationTemplateForAllChannels('');
        $p1->setUseSkinForAllChannels('');
        $p1->setCmd('');
        $p1->setActive(1);

        self::$em->persist($n1);
        self::$em->persist($p1);
        self::$em->flush();

        $urlWithDomain = $p1->getURL('http://example.com/cms', '?k=v');
        $this->assertEquals('http://example.com/cms/de/testgeturl?k=v', $urlWithDomain);

        $urlWithoutDomain = $p1->getURL('', '?k=v');
        $this->assertEquals('/de/testgeturl?k=v', $urlWithoutDomain);
    }

    public function testTranslate() {

        $nodeRepo = self::$em->getRepository('Cx\Core\ContentManager\Model\Entity\Node');
        $pageRepo = self::$em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');

        $n1 = new \Cx\Core\ContentManager\Model\Entity\Node();
        $n1->setParent($nodeRepo->getRoot());
        $nodeRepo->getRoot()->addChildren($n1);

        $n2 = new \Cx\Core\ContentManager\Model\Entity\Node();
        $n2->setParent($n1);

        self::$em->persist($n1);
        self::$em->persist($n2);
        self::$em->flush();

        $p1 = new \Cx\Core\ContentManager\Model\Entity\Page();
        $p1->setLang(1);
        $p1->setTitle('test translate root');
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
        $p2->setNodeIdShadowed($n1->getId());
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

        self::$em->refresh($n1);
        self::$em->refresh($n2);

        $pageToTranslate = $pageRepo->findOneById($pageId);

        // copy page following redirects
        $page = $pageToTranslate->copyToLang(
                2,
                true,   // includeContent
                true,   // includeModuleAndCmd
                true,   // includeName
                true,   // includeMetaData
                true,   // includeProtection
                true,   // includeEditingStatus
                false,  // followRedirects
                true    // followFallbacks
        );
        $page->setActive(1);

        $pageToTranslate->setupPath(2);

        $page->setNodeIdShadowed($pageToTranslate->getId());

        self::$em->persist($page);
        self::$em->flush();

        $pageId = $page->getId(); // Translated page id

        self::$em->refresh($n1);
        self::$em->refresh($n2);

        $page = $pageRepo->findOneById($pageId); // Translated page

        $this->assertEquals('/test-translate-root/child-page', $page->getPath());
        $this->assertEquals(2, $page->getLang());

        //see if the parent node is really, really there.
        $parentPages = $page->getNode()->getParent()->getPagesByLang();
        $this->assertArrayHasKey(2, $parentPages);
        $this->assertEquals('test translate root', $parentPages[2]->getTitle());
    }
}
