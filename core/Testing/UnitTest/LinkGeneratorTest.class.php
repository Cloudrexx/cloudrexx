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
 * LinkGeneratorTest
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @author      SS4U <ss4u.comvation@gmail.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  core_linkGenerator
 */

namespace Cx\Core\Testing\UnitTest;

/**
 * LinkGeneratorTest
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @author      SS4U <ss4u.comvation@gmail.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  core_linkGenerator
 */
class LinkGeneratorTest extends \Cx\Core\Test\Model\Entity\DoctrineTestCase {
    public function testDummyTestToAvoidPHPUnitWarning() {
    }
/*     protected $nodeId; */

/*     protected function insertFixtures() { */
/*         $root = new \Cx\Core\ContentManager\Model\Entity\Node(); */
/*         $node = new \Cx\Core\ContentManager\Model\Entity\Node(); */
/*         $node->setParent($root); */

/*         $p = new \Cx\Core\ContentManager\Model\Entity\Page(); */
/*         $p2 = new \Cx\Core\ContentManager\Model\Entity\Page(); */

/*         $p->setLang(1); */
/*         $p->setTitle('testpage'); */
/*         $p->setNode($node); */
/*         $p->setUsername('user'); */

/*         $p2->setLang(2); */
/*         $p2->setTitle('testpage2'); */
/*         $p2->setNode($node); */
/*         $p2->setUsername('user'); */

/*         //shouldn't raise a ValidationException */
/*         self::$em->persist($root); */
/*         self::$em->persist($node); */
/*         self::$em->persist($p); */
/*         self::$em->persist($p2); */
/*         self::$em->flush(); */

/*         $this->nodeId = $node->getId(); */
/*     } */

/*     public function testScanning() { */
/*         $this->insertFixtures(); */

/*         $testContent = 'asdf{NODE_'.$this->nodeId.'_1}'; */
/*         $testContent .= ' asdf{NODE_'.$this->nodeId.'_2}'; */

/*         $lg = new LinkGenerator('example.com/offset/'); */
/*         $lg->scan($testContent); */

/*         $ph = $lg->getPlaceholders(); */

/*         $this->assertEquals(2, count($ph)); */
/*         $this->assertArrayHasKey('NODE_'.$this->nodeId.'_1', $ph); */
/*         $this->assertArrayHasKey('NODE_'.$this->nodeId.'_2', $ph); */

/*         $this->assertEquals($this->nodeId, $ph['NODE_'.$this->nodeId.'_1']['nodeid']); */
/*         $this->assertEquals(1, $ph['NODE_'.$this->nodeId.'_1']['lang']); */

/*         $this->continueWithFetching($lg); */
/*     } */

/*     public function continueWithFetching($lg) { */
/*         $lg->fetch(self::$em); */

/*         $ph = $lg->getPlaceholders(); */

/*         $this->assertEquals('example.com/offset/testpage', $ph['NODE_'.$this->nodeId.'_1']); */
/*     } */
}
