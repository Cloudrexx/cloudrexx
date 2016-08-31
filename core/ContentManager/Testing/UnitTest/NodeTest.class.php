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
 * NodeTest
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
 * NodeTest
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @author      SS4U <ss4u.comvation@gmail.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  core_contentmanager
 */
class NodeTest extends \Cx\Core\Test\Model\Entity\DoctrineTestCase
{
    public function testPagesByLang() {

        $nodeRepo = self::$em->getRepository('Cx\Core\ContentManager\Model\Entity\Node');

        $node = new \Cx\Core\ContentManager\Model\Entity\Node();
        $node->setParent($nodeRepo->getRoot());
        $nodeRepo->getRoot()->addChildren($node);

        self::$em->persist($node);
        self::$em->flush();

        $p1 = new \Cx\Core\ContentManager\Model\Entity\Page();
        $p2 = new \Cx\Core\ContentManager\Model\Entity\Page();

        $p1->setNode($node);
        $p2->setNode($node);

        $p1->setLang(1);
        $p1->setTitle('testpage');
        $p1->setNodeIdShadowed($node->getId());
        $p1->setUseCustomContentForAllChannels('');
        $p1->setUseCustomApplicationTemplateForAllChannels('');
        $p1->setUseSkinForAllChannels('');
        $p1->setCmd('');
        $p1->setActive(1);

        $p2->setLang(2);
        $p2->setTitle('testpage2');
        $p2->setNodeIdShadowed($node->getId());
        $p2->setUseCustomContentForAllChannels('');
        $p2->setUseCustomApplicationTemplateForAllChannels('');
        $p2->setUseSkinForAllChannels('');
        $p2->setCmd('');
        $p2->setActive(1);

        self::$em->persist($node);
        self::$em->persist($p1);
        self::$em->persist($p2);

        self::$em->flush();
        self::$em->refresh($node); // Refreshes the state of the given entity from the database, overwriting local changes.

        $id = $p1->getId();

        $r = self::$em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
        $p = $r->find($id);

        $pages = $p->getNode()->getPagesByLang();
        $this->assertArrayHasKey(2, $pages);
        $this->assertArrayHasKey(1, $pages);

        $this->assertEquals('testpage', $pages[1]->getTitle());
        $this->assertEquals('testpage2', $pages[2]->getTitle());
    }
}
