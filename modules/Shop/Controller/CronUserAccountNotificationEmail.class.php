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
 * User account notification
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @package     cloudrexx
 * @subpackage  module_shop
 * @todo        Edit PHP DocBlocks!
 */

/**
 * Pull generated user accounts from the access_users database table
 * and send notification emails to those customers whose expiration date
 * is only a week or less away.
 * Put this script in a folder within the root directory of the cloudrexx
 * installation, i.e. "/<contrexx_webroot>/scripts" (the folder must be
 * in the same directory as, for example, the "cadmin", "modules" and "themes"
 * folders).
 * Note: If you put this script in folder in a different folder level,
 * you need to change the include path of the configuration file below!
 * Note: The "interests" field is used to mark those accounts which have
 * been processed successfully.  This field must not be used otherwise
 * for autocreated user accounts.  Leave it untouched at all times!
 *
 */

// Since 3.1.0
global $objDatabase;
require_once dirname(dirname(dirname(dirname(__FILE__)))).'/core/Core/init.php';
$cx = init('minimal');
$objDatabase = $cx->getDb()->getAdoDb();
if (!$objDatabase) {
    die("Error: Failed to connect to database");
}

// THIS SCRIPT DOES NOT WORK AS IS!
// (Unknown column 'interests' in 'where clause')

// Test: Clear and insert fresh test data
/*
$query = "
  DELETE FROM ".DBPREFIX."access_users
   WHERE id>1000
";
$objResult = $objDatabase->Execute($query);
if (!$objResult) {
    die("Error: Query failed, code gfdntgedghs\n$query\n");
}
for ($i = 1; $i <= 10; ++$i) {
    $query = "
        INSERT INTO ".DBPREFIX."access_users (
          id, levelid, is_admin, username, password,
          regdate, validity,
          email, firstname, lastname, interests, active
        ) VALUES (
          ".(1000+$i).", 1, 0, 'A-08-8$i', 'asdf',
          '2008-01-".str_pad(13+$i, 2, '0', STR_PAD_LEFT)."', 30,
          'hobi@kobi.com', 'Vori', 'Nachi', '', 1
        )
    ";
    $objResult = $objDatabase->Execute($query);
    if (!$objResult) {
        die("Error: Query failed, code jtzrefgfsdf\n$query\n");
    }
}
*/

/*  Look for generated accounts that are still active, have a limited
    validity, and haven't been notified yet.
    The interests field will be updated (filled in) upon successful
    notification!   */
$query = "
    SELECT * FROM ".DBPREFIX."access_users
     WHERE username LIKE 'A-%'
       AND active=1
       AND expiration!=0
       AND interests=''
";
$objResult = $objDatabase->Execute($query);
if (!$objResult) {
    die("Error: Query failed, code gfdhsrevws\n$query\n");
}

$todayTimestamp = time();
// Notify customers seven days in advance
$notifyLimitTimestamp = $todayTimestamp + 7 * 24 * 60 * 60;

while (!$objResult->EOF) {
    $id = $objResult->fields['id'];
    $endTimestamp = $objResult->fields['expiration'];
    $endDate = date('d.m.Y', $endTimestamp);

/*  Debug
$notifyLimitDate = date('d.m.Y', $notifyLimitTimestamp);
echo("Enddate: $endTimestamp - $endDate, notifydate: $notifyLimitTimestamp - $notifyLimitDate\n");
*/

    // Skip accounts that are valid for longer than the limit
    if ($endTimestamp > $notifyLimitTimestamp) {
        $objResult->MoveNext();
        continue;
    }

    // Send a notification e-mail
    $username = $objResult->fields['username'];
    $email = $objResult->fields['email'];
    $match = null;
    // START: COMPATIBILITY MODE FOR SHOP ACCOUNT SELLING
    if (preg_match('#^shop_customer_[0-9]+\-(.*)$#', $email, $match)) {
        $email = $match[1];
    }
    // END: COMPATIBILITY MODE FOR SHOP ACCOUNT SELLING

    $firstname = $objResult->fields['firstname'];
    $lastname = $objResult->fields['lastname'];
    $subject = "Your account on www.noser.com will expire in seven days";
    $mailbody =
"Dear $firstname $lastname,

This mail has been sent to inform you that your account on www.mydomain.com
will expire on $endDate.
Your account user name: $username
Please visit our website www.mydomain.com if you would like to extend your
account.

Kind regards,

The mydomain Team";
    $headers =
"From: info@mydomain.com\r\n".
"Reply-To: info@mydomain.com\r\n".
"X-Mailer: PHP/".phpversion();

/*  Debug
echo("Prepared mail:
To: $email
Headers: $headers
Subject: $subject
Body: $mailbody

");
*/
    // Send mail to customer
    // Test/debug with: $result = true;
    $result = @mail($email, $subject, $mailbody, $headers);

    // Update user account record.
    // Mark the account if the mail could be sent only.
    if ($result) {
        $query = "
            UPDATE ".DBPREFIX."access_users
               SET interests='notified on ".date('Y-m-d H:i:s')."'
             WHERE id=$id
        ";
        $objResult2 = $objDatabase->Execute($query);
        if (!$objResult2) {
            die("Error: Query failed, code iuhlmvgfhk\n$query\n");
        }
    }
    $objResult->MoveNext();
}

//echo("All done.");
