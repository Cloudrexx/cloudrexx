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
 * TransactionTest
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @author      SS4U <ss4u.comvation@gmail.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_checkout
 */

namespace Cx\Modules\Checkout\Testing\UnitTest;
use Cx\Modules\Checkout\Controller\Transaction;

/**
 * TransactionTest
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @author      SS4U <ss4u.comvation@gmail.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_checkout
 */
class TransactionTest extends \Cx\Core\Test\Model\Entity\DoctrineTestCase {

    private function getTestData() {
        return array(
            'status' => 'waiting',
            'time' => time(),
            'invoice_number' => 789456123,
            'invoice_currency' => 1,
            'invoice_amount' => 123456,
            'contact_title' => 'mister',
            'contact_forename' => 'Hans',
            'contact_surname' => 'Muster',
            'contact_company' => 'Muster AG',
            'contact_street' => 'Musterstrasse 1',
            'contact_postcode' => '1234',
            'contact_place' => 'Musterort',
            'contact_country' => 'Switzerland',
            'contact_phone' => '012 345 67 89',
            'contact_email' => 'info@example.com',
        );
    }

    public function testAddAndGet() {
        $objTransaction = new Transaction(\Env::get('db'));

        $arrInput = $this->getTestData();
        $arrInput['id'] = $objTransaction->add(
            $arrInput['status'],
            $arrInput['invoice_number'],
            $arrInput['invoice_currency'],
            $arrInput['invoice_amount'],
            $arrInput['contact_title'],
            $arrInput['contact_forename'],
            $arrInput['contact_surname'],
            $arrInput['contact_company'],
            $arrInput['contact_street'],
            $arrInput['contact_postcode'],
            $arrInput['contact_place'],
            204,
            $arrInput['contact_phone'],
            $arrInput['contact_email']
        );

        $arrOutput = $objTransaction->get(array($arrInput['id']));

        $this->assertEquals($arrInput, $arrOutput[0]);
    }

    public function testUpdate() {
        $objTransaction = new Transaction(\Env::get('db'));

        $arrInput = $this->getTestData();
        $arrInput['id'] = $objTransaction->add(
            $arrInput['status'],
            $arrInput['invoice_number'],
            $arrInput['invoice_currency'],
            $arrInput['invoice_amount'],
            $arrInput['contact_title'],
            $arrInput['contact_forename'],
            $arrInput['contact_surname'],
            $arrInput['contact_company'],
            $arrInput['contact_street'],
            $arrInput['contact_postcode'],
            $arrInput['contact_place'],
            204,
            $arrInput['contact_phone'],
            $arrInput['contact_email']
        );

        $newStatus = 'confirmed';
        $objTransaction->updateStatus($arrInput['id'], $newStatus);

        $arrOutput = $objTransaction->get(array($arrInput['id']));

        $this->assertEquals($arrOutput[0]['status'], $newStatus);
    }

    public function testDelete() {
        $objTransaction = new Transaction(\Env::get('db'));

        $arrInput = $this->getTestData();
        $arrInput['id'] = $objTransaction->add(
            $arrInput['status'],
            $arrInput['invoice_number'],
            $arrInput['invoice_currency'],
            $arrInput['invoice_amount'],
            $arrInput['contact_title'],
            $arrInput['contact_forename'],
            $arrInput['contact_surname'],
            $arrInput['contact_company'],
            $arrInput['contact_street'],
            $arrInput['contact_postcode'],
            $arrInput['contact_place'],
            204,
            $arrInput['contact_phone'],
            $arrInput['contact_email']
        );

        $objTransaction->delete($arrInput['id']);

        $this->assertFalse($objTransaction->get(array($arrInput['id'])));
    }

}
