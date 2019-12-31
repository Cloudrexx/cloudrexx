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
 * Transaction
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_checkout
 */

namespace Cx\Modules\Checkout\Controller;

/**
 * Transaction
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_checkout
 */
class Transaction {

    /**
     * Database object.
     *
     * @access      private
     * @var         ADONewConnection
     */
    private $objDatabase;

    /**
     * Initialize the database object.
     *
     * @access      public
     * @param       ADONewConnection    $objDatabase
     */
    public function __construct($objDatabase)
    {
        $this->objDatabase = $objDatabase;
    }

    /**
     * Add new transaction.
     *
     * @access      public
     * @param       string      $status
     * @param       integer     $invoiceNumber
     * @param       integer     $invoiceCurrency
     * @param       integer     $invoiceAmount
     * @param       string      $contactTitle
     * @param       string      $contactForename
     * @param       string      $contactSurname
     * @param       string      $contactCompany
     * @param       string      $contactStreet
     * @param       string      $contactPostcode
     * @param       string      $contactPlace
     * @param       integer     $contactCountry
     * @param       string      $contactPhone
     * @param       string      $contactEmail
     * @return      integer                         id of inserted record
     * @return      boolean                         status of insertion
     */
    public function add($status, $invoiceNumber, $invoiceCurrency, $invoiceAmount, $contactTitle, $contactForename, $contactSurname, $contactCompany, $contactStreet, $contactPostcode, $contactPlace, $contactCountry, $contactPhone, $contactEmail)
    {
        $objResult = $this->objDatabase->Execute('
            INSERT INTO `'.DBPREFIX.'module_checkout_transactions`
            (
                `time`,
                `status`,
                `invoice_number`,
                `invoice_currency`,
                `invoice_amount`,
                `contact_title`,
                `contact_forename`,
                `contact_surname`,
                `contact_company`,
                `contact_street`,
                `contact_postcode`,
                `contact_place`,
                `contact_country`,
                `contact_phone`,
                `contact_email`
            )
            VALUES
            (
                '.time().',
                "'.contrexx_raw2db($status).'",
                "'.contrexx_raw2db($invoiceNumber).'",
                '.intval($invoiceCurrency).',
                '.intval($invoiceAmount * 100).',
                "'.contrexx_raw2db($contactTitle).'",
                "'.contrexx_raw2db($contactForename).'",
                "'.contrexx_raw2db($contactSurname).'",
                "'.contrexx_raw2db($contactCompany).'",
                "'.contrexx_raw2db($contactStreet).'",
                "'.contrexx_raw2db($contactPostcode).'",
                "'.contrexx_raw2db($contactPlace).'",
                '.intval($contactCountry).',
                "'.contrexx_raw2db($contactPhone).'",
                "'.contrexx_raw2db($contactEmail).'"
            )
        ');

        if ($objResult) {
            return $this->objDatabase->Insert_ID();
        } else {
            return false;
        }
    }

    /**
     * Delete existing transaction.
     *
     * @access      public
     * @param       integer     $id     id of transaction
     * @return      boolean             status of deletion
     */
    public function delete($id)
    {
        $objResult = $this->objDatabase->Execute('DELETE FROM `'.DBPREFIX.'module_checkout_transactions` WHERE `id`='.intval($id));

        if (($objResult) && ($this->objDatabase->Affected_Rows() > 0)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get all countries.
     *
     * @access      public
     * @param       array       $arrIDs             ids of requested transactions
     * @param       integer     $offset             paging offset
     * @param       integer     $limit              paging limit
     * @return      array       $arrTransactions    contains all transactions
     * @return      boolean                         contains false if there are no transactions
     */
    public function get($arrIDs=array(), $offset=0, $limit=0)
    {
        $SQLWhere = '';
        if (is_array($arrIDs) && !empty($arrIDs)) {
            $arrIDs = array_map('intval', $arrIDs);
            $SQLWhere = ' WHERE (`transactions`.`id`='.implode(' OR `transactions`.`id`=', $arrIDs).')';
        }

        $SQLLimit = '';
        if (!empty($offset) && !empty($limit)) {
            $SQLLimit = ' LIMIT '.intval($offset).', '.intval($limit);
        } else if (empty($offset) && !empty($limit)) {
            $SQLLimit = ' LIMIT '.intval($limit);
        }

        $countries = \Cx\Core\Country\Controller\Country::getNameArray();

        $arrTransactions = array();
        $objResult = $this->objDatabase->Execute('
            SELECT
                `transactions`.`id`,
                `transactions`.`time`,
                `transactions`.`status`,
                `transactions`.`invoice_number`,
                `transactions`.`invoice_currency`,
                `transactions`.`invoice_amount`,
                `transactions`.`contact_title`,
                `transactions`.`contact_forename`,
                `transactions`.`contact_surname`,
                `transactions`.`contact_company`,
                `transactions`.`contact_street`,
                `transactions`.`contact_postcode`,
                `transactions`.`contact_place`,
                `countries`.`name` as `contact_country`,
                `transactions`.`contact_phone`,
                `transactions`.`contact_email`
            FROM `'.DBPREFIX.'module_checkout_transactions` as `transactions`
            '.$SQLWhere.'
            ORDER BY `transactions`.`id` DESC'
            .$SQLLimit);


        if ($objResult) {
            $i = 0;
            while (!$objResult->EOF) {
                $arrTransactions[$i] = array();
                $arrTransactions[$i]['id'] = $objResult->fields['id'];
                $arrTransactions[$i]['time'] = $objResult->fields['time'];
                $arrTransactions[$i]['status'] = $objResult->fields['status'];
                $arrTransactions[$i]['invoice_number'] = $objResult->fields['invoice_number'];
                $arrTransactions[$i]['invoice_currency'] = $objResult->fields['invoice_currency'];
                $arrTransactions[$i]['invoice_amount'] = ($objResult->fields['invoice_amount'] / 100);
                $arrTransactions[$i]['contact_title'] = $objResult->fields['contact_title'];
                $arrTransactions[$i]['contact_forename'] = $objResult->fields['contact_forename'];
                $arrTransactions[$i]['contact_surname'] = $objResult->fields['contact_surname'];
                $arrTransactions[$i]['contact_company'] = $objResult->fields['contact_company'];
                $arrTransactions[$i]['contact_street'] = $objResult->fields['contact_street'];
                $arrTransactions[$i]['contact_postcode'] = $objResult->fields['contact_postcode'];
                $arrTransactions[$i]['contact_place'] = $objResult->fields['contact_place'];
                $arrTransactions[$i]['contact_country'] = $countries[$objResult->fields['contact_country']];
                $arrTransactions[$i]['contact_phone'] = $objResult->fields['contact_phone'];
                $arrTransactions[$i]['contact_email'] = $objResult->fields['contact_email'];
                $objResult->MoveNext();
                $i++;
            }
        }

        if (!empty($arrTransactions)) {
            return $arrTransactions;
        } else {
            return false;
        }
    }

    /**
     * Get transaction record count.
     *
     * @access      public
     * @return      array       transaction record count
     * @return      boolean     contains false if there are no transactions
     */
    public function getRecordCount()
    {
        $objResult = $this->objDatabase->Execute('SELECT count(`id`) as `count` FROM `'.DBPREFIX.'module_checkout_transactions`');

        if ($objResult) {
            return intval($objResult->fields['count']);
        } else {
            return false;
        }
    }

    /**
     * Update existing transaction.
     *
     * @access      public
     * @param       integer     $id         id of transaction
     * @param       integer     $status     status of transaction
     * @return      boolean                 status of update
     */
    public function updateStatus($id, $status)
    {
        $objResult = $this->objDatabase->Execute('UPDATE `'.DBPREFIX.'module_checkout_transactions` SET `status`="'.contrexx_raw2db($status).'", `time`='.time().' WHERE `id`='.intval($id));

        if (($objResult) && ($this->objDatabase->Affected_Rows() > 0)) {
            return true;
        } else {
            return false;
        }
    }
}
