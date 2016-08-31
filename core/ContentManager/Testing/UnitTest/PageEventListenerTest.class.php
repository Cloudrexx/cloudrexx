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
 * PageEventListenerTest
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
 * PageEventListenerTest
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @author      SS4U <ss4u.comvation@gmail.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  core_contentmanager
 */
class PageEventListenerTest extends \Cx\Core\Test\Model\Entity\DoctrineTestCase
{
    public function testUniqueSlugGeneration() {
        $nodeRepo = self::$em->getRepository('Cx\Core\ContentManager\Model\Entity\Node');

        $root = $nodeRepo->getRoot();

        $n1 = new \Cx\Core\ContentManager\Model\Entity\Node();
        $n1->setParent($root);
        $root->addChildren($n1);

        $n2 = new \Cx\Core\ContentManager\Model\Entity\Node();
        $n2->setParent($root);
        $root->addChildren($n2);

        $n3 = new \Cx\Core\ContentManager\Model\Entity\Node();
        $n3->setParent($root);
        $root->addChildren($n3);

        self::$em->persist($n1);
        self::$em->persist($n2);
        self::$em->persist($n3);
        self::$em->flush();

        $p1 = new \Cx\Core\ContentManager\Model\Entity\Page();
        $p1->setLang(1);
        $p1->setTitle('slug testpage');
        $p1->setNode($n1);
        $p1->setNodeIdShadowed($n1->getId());
        $p1->setUseCustomContentForAllChannels('');
        $p1->setUseCustomApplicationTemplateForAllChannels('');
        $p1->setUseSkinForAllChannels('');
        $p1->setCmd('');
        $p1->setActive(1);

        //provocate a slug conflict
        $p2 = new \Cx\Core\ContentManager\Model\Entity\Page();
        $p2->setLang(1);
        $p2->setTitle('slug testpage');
        $p2->setNode($n2);
        $p2->setNodeIdShadowed($n2->getId());
        $p2->setUseCustomContentForAllChannels('');
        $p2->setUseCustomApplicationTemplateForAllChannels('');
        $p2->setUseSkinForAllChannels('');
        $p2->setCmd('');
        $p2->setActive(1);

        //different language, shouldn't conflict
        $p3 = new \Cx\Core\ContentManager\Model\Entity\Page();
        $p3->setLang(2);
        $p3->setTitle('slug testpage');
        $p3->setNode($n1);
        $p3->setNodeIdShadowed($n1->getId());
        $p3->setUseCustomContentForAllChannels('');
        $p3->setUseCustomApplicationTemplateForAllChannels('');
        $p3->setUseSkinForAllChannels('');
        $p3->setCmd('');
        $p3->setActive(1);

        //provocate another slug conflict
        $p4 = new \Cx\Core\ContentManager\Model\Entity\Page();
        $p4->setLang(1);
        $p4->setTitle('slug testpage');
        $p4->setNode($n3);
        $p4->setNodeIdShadowed($n3->getId());
        $p4->setUseCustomContentForAllChannels('');
        $p4->setUseCustomApplicationTemplateForAllChannels('');
        $p4->setUseSkinForAllChannels('');
        $p4->setCmd('');
        $p4->setActive(1);

        self::$em->persist($n1);
        self::$em->persist($n2);
        self::$em->persist($n3);

        self::$em->persist($p1);
        self::$em->flush();
        self::$em->refresh($n1);

        self::$em->persist($p2);
        self::$em->flush();
        self::$em->refresh($n2);

        self::$em->persist($p3);
        self::$em->flush();
        self::$em->refresh($n1);
        self::$em->refresh($n2);

        self::$em->persist($p4);
        self::$em->flush();
        self::$em->refresh($n3);

        //see whether the listener changed the slug as we expect him to do.
        $this->assertEquals('slug-testpage', $p1->getSlug());
        $this->assertEquals('slug-testpage-1', $p2->getSlug());
        $this->assertEquals('slug-testpage-2', $p4->getSlug());
        //check whether slug uniqueness was checked only language-wide
        $this->assertEquals('slug-testpage', $p3->getSlug());

    }

    public function testUniqueSlugGenerationWithPersistedNodes() {
        $nodeRepo = self::$em->getRepository('Cx\Core\ContentManager\Model\Entity\Node');

        $root = $nodeRepo->getRoot();

        $n1 = new \Cx\Core\ContentManager\Model\Entity\Node();
        $n1->setParent($root);
        $root->addChildren($n1);
        $n2 = new \Cx\Core\ContentManager\Model\Entity\Node();
        $n2->setParent($root);
        $root->addChildren($n2);

        self::$em->persist($n1);
        self::$em->persist($n2);
        self::$em->flush();

        $p1 = new \Cx\Core\ContentManager\Model\Entity\Page();
        $p1->setLang(1);
        $p1->setTitle('unique slug testpage');
        $p1->setNode($n1);
        $p1->setNodeIdShadowed($n1->getId());
        $p1->setUseCustomContentForAllChannels('');
        $p1->setUseCustomApplicationTemplateForAllChannels('');
        $p1->setUseSkinForAllChannels('');
        $p1->setCmd('');
        $p1->setActive(1);

        self::$em->persist($n1);
        self::$em->persist($n2);
        self::$em->persist($p1);
        self::$em->flush();

        $id = $n2->getId();

        self::$em->refresh($n1);
        self::$em->refresh($n2);

        $node = self::$em->find('Cx\Core\ContentManager\Model\Entity\Node', $id);

        //provocate a slug conflict
        $p2 = new \Cx\Core\ContentManager\Model\Entity\Page();
        $p2->setLang(1);
        $p2->setTitle('unique slug testpage');
        $p2->setNode($node);
        $p2->setNodeIdShadowed($node->getId());
        $p2->setUseCustomContentForAllChannels('');
        $p2->setUseCustomApplicationTemplateForAllChannels('');
        $p2->setUseSkinForAllChannels('');
        $p2->setCmd('');
        $p2->setActive(1);

        //different language, shouldn't conflict
        $p3 = new \Cx\Core\ContentManager\Model\Entity\Page();
        $p3->setLang(2);
        $p3->setTitle('unique slug testpage');
        $p3->setNode($node);
        $p3->setNodeIdShadowed($node->getId());
        $p3->setUseCustomContentForAllChannels('');
        $p3->setUseCustomApplicationTemplateForAllChannels('');
        $p3->setUseSkinForAllChannels('');
        $p3->setCmd('');
        $p3->setActive(1);

        self::$em->persist($p2);
        self::$em->persist($p3);
        self::$em->flush();
        self::$em->refresh($node);

        $newNode = new \Cx\Core\ContentManager\Model\Entity\Node();
        $newNode->setParent($node->getParent());
        $node->getParent()->addChildren($newNode);

        //mixing in a conflict inside the new persists
        $p4 = new \Cx\Core\ContentManager\Model\Entity\Page();
        $p4->setLang(1);
        $p4->setTitle('unique slug testpage');
        $p4->setNode($newNode);
        $p4->setNodeIdShadowed($newNode->getId());
        $p4->setUseCustomContentForAllChannels('');
        $p4->setUseCustomApplicationTemplateForAllChannels('');
        $p4->setUseSkinForAllChannels('');
        $p4->setCmd('');
        $p4->setActive(1);

        self::$em->persist($newNode);
        self::$em->persist($p4);
        self::$em->flush();
        self::$em->refresh($newNode);

        $this->assertEquals('unique-slug-testpage', $p1->getSlug());
        $this->assertEquals('unique-slug-testpage-1', $p2->getSlug());
        $this->assertEquals('unique-slug-testpage', $p3->getSlug());
        $this->assertEquals('unique-slug-testpage-2', $p4->getSlug());
    }

    public function testSlugReleasing() {
        $nodeRepo = self::$em->getRepository('Cx\Core\ContentManager\Model\Entity\Node');

        $root = $nodeRepo->getRoot();

        $n1 = new \Cx\Core\ContentManager\Model\Entity\Node();
        $n1->setParent($root);
        $root->addChildren($n1);
        $n2 = new \Cx\Core\ContentManager\Model\Entity\Node();
        $n2->setParent($root);
        $root->addChildren($n2);

        self::$em->persist($n1);
        self::$em->persist($n2);
        self::$em->flush();

        $p1 = new \Cx\Core\ContentManager\Model\Entity\Page();
        $p1->setLang(1);
        $p1->setTitle('slug release testpage');
        $p1->setNode($n1);
        $p1->setNodeIdShadowed($n1->getId());
        $p1->setUseCustomContentForAllChannels('');
        $p1->setUseCustomApplicationTemplateForAllChannels('');
        $p1->setUseSkinForAllChannels('');
        $p1->setCmd('');
        $p1->setActive(1);

        self::$em->persist($root);
        self::$em->persist($n1);
        self::$em->persist($n2);
        self::$em->persist($p1);
        self::$em->flush();

        $idp1 = $p1->getId();
        $idn2 = $n2->getId();

        self::$em->refresh($n1);
        self::$em->refresh($n2);

        $this->assertEquals('slug-release-testpage', $p1->getSlug());

        $p1 = self::$em->find('Cx\Core\ContentManager\Model\Entity\Page', $idp1);
        $n2 = self::$em->find('Cx\Core\ContentManager\Model\Entity\Node', $idn2);

        //shouldn't provocate a slug conflict, since we delete the other page below
        $p2 = new \Cx\Core\ContentManager\Model\Entity\Page();
        $p2->setLang(1);
        $p2->setTitle('slug release testpage');
        $p2->setNode($n2);
        $p2->setNodeIdShadowed($n2->getId());
        $p2->setUseCustomContentForAllChannels('');
        $p2->setUseCustomApplicationTemplateForAllChannels('');
        $p2->setUseSkinForAllChannels('');
        $p2->setCmd('');
        $p2->setActive(1);

        self::$em->remove($p1);
        self::$em->flush();

        self::$em->persist($p2);
        self::$em->flush();

        $this->assertEquals('slug-release-testpage', $p2->getSlug());
   }
}
