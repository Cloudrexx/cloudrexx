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
 * Core Creditcard class
 *
 * @version     3.0.0
 * @since       2.2.0
 * @package     cloudrexx
 * @subpackage  core
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @todo        Test!
 */

/**
 * Creditcard helper methods
 *
 * @version     3.0.0
 * @since       2.2.0
 * @package     cloudrexx
 * @subpackage  core
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @todo        Test!
 */
class Creditcard
{
    /**
     * Database key
     */
    const TEXT_CORE_CREDITCARD_NAME = 'CORE_CREDITCARD_NAME';


    /**
     * Array of all creditcards
     * @var     array
     * @access  private
     * @see     init()
     */
    private static $arrCreditcards = false;


    /**
     * Initialise the static $arrCreditcards array with all creditcards
     * found in the database
     *
     * The array created is of the form
     *  array(
     *    creditcard ID => array(
     *      'id'   => creditcard ID,
     *      'name' => creditcard name,
     *      'ord'  => ordinal value,
     *    ),
     *    ... more ...
     *  )
     * The array is sorted by the ordinal values.
     * @global  ADONewConnection  $objDatabase
     * @return  boolean                     True on success, false otherwise
     */
    function init()
    {
        global $objDatabase;

        $query = "
            SELECT `id`, `name`, `ord`
              FROM ".DBPREFIX."core_creditcard
             ORDER BY `ord` ASC
        ";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return self::errorHandler();
        self::$arrCreditcards = array();
        while (!$objResult->EOF) {
            $id = $objResult->fields['id'];
            self::$arrCreditcards[$id] = array(
                'id'   => $id,
                'name' => $objResult->fields['name'],
                'ord'  => $objResult->fields['ord'],
            );
            $objResult->MoveNext();
        }
        return true;
    }


    /**
     * Returns the array of all creditcards
     * @return  array               The creditcard array on success,
     *                              false otherwise
     * @static
     */
    static function getArray()
    {
        if (empty(self::$arrCreditcards) && self::init())
            return self::$arrCreditcards;
        return false;
    }


    /**
     * Returns the array of all creditcard names indexed by their ID
     * and ordered by the ordinal value
     * @return  array               The creditcard name array on success,
     *                              false otherwise
     * @static
     */
    static function getNameArray()
    {
        static $arrCreditcardNames = false;
        if ($arrCreditcardNames) return $arrCreditcardNames;
//echo("Creditcard::getNameArray():  Initializing<br />");
        if (empty(self::$arrCreditcards)) self::init();
        if (empty(self::$arrCreditcards)) {
//echo("Creditcard::getNameArray():  Failed to initialize<br />");
            return false;
        }
        $arrCreditcardNames = array();
        foreach (self::$arrCreditcards as $id => $arrCreditcard) {
            $arrCreditcardNames[$id] = $arrCreditcard['name'];
        }
//echo("Creditcard::getNameArray():  Made array ".var_export($arrCreditcardNames, true)."<br />");
        return $arrCreditcardNames;
    }


    /**
     * Returns the name of the creditcard selected by its ID
     *
     * If a creditcard with the given ID does not exist, returns the
     * empty string.
     * @param   integer   $creditcard_id  The creditcard ID
     * @return  string                    The creditcard name,
     *                                    or the empty string
     * @static
     */
    static function getNameById($creditcard_id)
    {
        if (empty(self::$arrCreditcards)) self::init();
        return (isset(self::$arrCreditcards[$creditcard_id])
            ? self::$arrCreditcards[$creditcard_id]['name']
            : ''
        );
    }


    /**
     * Returns the ordinal value of the creditcard selected by its ID
     *
     * If a creditcard with the given ID does not exist, returns zero.
     * @param   integer   $creditcard_id  The creditcard ID
     * @return  string                    The ordinal value, or zero
     * @static
     */
    static function getOrdById($creditcard_id)
    {
        if (empty(self::$arrCreditcards)) self::init();
        return (isset(self::$arrCreditcards[$creditcard_id])
            ? self::$arrCreditcards[$creditcard_id]['ord']
            : 0
        );
    }


    /**
     * Returns the HTML dropdown menu code for the active creditcards.
     *
     * Frontend use only.
     * @param   string  $selected_id Optional preselected creditcard ID
     * @param   string  $menuName   Optional name of the menu,
     *                              defaults to "creditcardId"
     * @param   string  $onchange   Optional onchange callback function
     * @return  string              The HTML dropdown menu code
     * @static
     */
    static function getMenu(
        $selected_id='', $menuName='creditcardId', $onchange=''
    ) {
        return Html::getSelect(
            $menuName, self::$arrCreditcards, $selected_id, $onchange
        );
    }


    /**
     * Returns the HTML code for the creditcards dropdown menu options
     *
     * Remembers the last selected ID and the menu options created, so it's
     * very quick to call this again using the same arguments.
     * @param   string  $selected_id       Optional preselected creditcard ID
     * @return  string                    The HTML dropdown menu options code
     * @static
     */
    static function getMenuoptions($selected_id=0)
    {
        static $strMenuoptions = '';
        static $last_selected_id = 0;

        if (empty(self::$arrCreditcards)) {
            $strMenuoptions = '';
            self::init();
        }
        if ($strMenuoptions && $last_selected_id == $selected_id)
            return $strMenuoptions;
        $strMenuoptions = Html::getOptions(self::$arrCreditcards, $selected_id);
        $last_selected_id = $selected_id;
        return $strMenuoptions;
    }


    /**
     * Tries to fix or recreate the database table(s) for the class
     *
     * Should be called whenever there's a problem with the database table.
     * @return  boolean             False.  Always.
     */
    function errorHandler()
    {
        global $objDatabase;

die("Creditcard::errorHandler(): Disabled!<br />");

        $query = "
            DROP TABLE IF EXISTS `".DBPREFIX."core_creditcard`";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return false;

        $query = "
            CREATE TABLE `".DBPREFIX."core_creditcard` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `name` TINYTEXT NOT NULL DEFAULT '',
              `ord` INT UNSIGNED NOT NULL DEFAULT 0,
              PRIMARY KEY (`id`)
            ) ENGINE=MYISAM";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return false;

        // Insert creditcard records from scratch
        $arrCreditcards = array(
            'Barzahlung',
            'American Express',
            'Argencard',
            'Australian BankCard',
            'Bancontact',
            'Bankcard',
            'Cabal',
            'CartaSi',
            'Carte Blanche',
            'Carte Bleue',
            'Chipper',
            'Diners Club',
            'Discover',
            'Dragon',
            'Eftpos',
            'Euro/Mastercard',
            'Greatwall',
            'JCB',
            'Jin Sui',
            'Maestro',
            'NICOS',
            'PIN',
            'Pacific',
            'Peony',
            'Red 6000',
            'Red Compra',
            'Solo',
            'Switch',
            'UC',
            'Visa',
            'Andere Karten',
        );
        foreach ($arrCreditcards as $ord => $creditcard) {
            $objResult = $objDatabase->Execute("
                INSERT INTO `".DBPREFIX."core_creditcard` (
                  `name`, `ord`
                ) VALUES (
                  '".addslashes($creditcard)."',
                  ".($ord * 1000)."
                )");
            if (!$objResult) {
//echo("Creditcard::errorHandler(): Failed to insert Creditcard $creditcard<br />");
                continue;
            }
        }

        // More to come...

        // Always!
        return false;
    }

}

?>
