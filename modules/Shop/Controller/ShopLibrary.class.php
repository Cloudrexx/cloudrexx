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
 * Shop library
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Ivan Schmid <ivan.schmid@comvation.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     3.0.0
 */

namespace Cx\Modules\Shop\Controller;

/**
 * All the helping hands needed to run the shop
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Ivan Schmid <ivan.schmid@comvation.com>
 * @access      public
 * @package     cloudrexx
 * @subpackage  module_shop
 * @todo        Add a proper constructor that initializes the class with its
 *              various variables, and/or move the appropriate parts to
 *              a pure Shop class.
 * @version     3.0.0
 */
class ShopLibrary
{
    const noPictureName = 'no_picture.gif';
    const thumbnailSuffix = '.thumb';

    /**
     * Payment result constant values
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    const PAYMENT_RESULT_SUCCESS_SILENT = -1;
    const PAYMENT_RESULT_FAIL           =  0;
    const PAYMENT_RESULT_SUCCESS        =  1;
    const PAYMENT_RESULT_CANCEL         =  2;


    const REGISTER_MANDATORY = 'mandatory';
    const REGISTER_OPTIONAL = 'optional';
    const REGISTER_NONE = 'none';


    /**
     * OBSOLETE
     * Set up and send an email from the shop.
     * @static
     * @param   string    $shopMailTo           Recipient mail address
     * @param   string    $shopMailFrom         Sender mail address
     * @param   string    $shopMailFromText     Sender name
     * @param   string    $shopMailSubject      Message subject
     * @param   string    $shopMailBody         Message body
     * @return  boolean                         True if the mail could be sent,
     *                                          false otherwise
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function shopSendmail()//$shopMailTo, $shopMailFrom, $shopMailFromText, $shopMailSubject, $shopMailBody
    {
die("ShopLibrary::shopSendmail(): Obsolete method called");
/*
        global $_CONFIG;

        // replace cr/lf by lf only
        $shopMailBody = preg_replace('/\015\012/', "\012", $shopMailBody);

        $objMail = new \Cx\Core\MailTemplate\Model\Entity\Mail();

        $from = preg_replace('/\015\012/', "\012", $shopMailFrom);
        $fromName = preg_replace('/\015\012/', "\012", $shopMailFromText);
        $objMail->AddReplyTo($_CONFIG['coreAdminEmail']);
        $objMail->SetFrom($from, $fromName);
        $objMail->Subject = $shopMailSubject;
        $objMail->IsHTML(false);
        $objMail->Body = $shopMailBody;
        $objMail->AddAddress($shopMailTo);
        if ($objMail->Send()) {
            return true;
        }

        return false;
 */
    }


    /**
     * OBSOLETE -- see {@see MailTemplate}
     * Pick a mail template from the database
     *
     * Get the selected mail template and associated fields from the database.
     * @static
     * @param   integer $shopTemplateId     The mail template ID
     * @param   integer $lang_id             The language ID
     * @global  ADONewConnection  $objDatabase    Database connection object
     * @return  mixed                       The mail template array on success,
     *                                      false otherwise
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function shopSetMailTemplate()//$shopTemplateId, $lang_id
    {
die("ShopLibrary::shopSetMailTemplate(): Obsolete method called");
/*
        global $objDatabase;

        $query = "
            SELECT from_mail, xsender, subject, message
              FROM ".DBPREFIX."module_shop".MODULE_INDEX."_mail_content
             WHERE tpl_id=$shopTemplateId
               AND lang_id=$lang_id";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult || $objResult->EOF) {
            return false;
        }
        $arrShopMailTemplate = array();
        $arrShopMailTemplate['mail_from'] = $objResult->fields['from_mail'];
        $arrShopMailTemplate['mail_x_sender'] = $objResult->fields['xsender'];
        $arrShopMailTemplate['mail_subject'] = $objResult->fields['subject'];
        $arrShopMailTemplate['mail_body'] = $objResult->fields['message'];
        return $arrShopMailTemplate;
*/
}


    /**
     * Convert the order ID and date to a custom order ID of the form
     * "lastnameYYY", where YYY is the order ID.
     *
     * This method may be customized to meet the needs of any shop owner.
     * The custom order ID may be used for creating user accounts for
     * protected downloads, for example.
     * @param   integer   $order_id       The order ID
     * @return  string                    The custom order ID
     * @global  ADONewConnection  $objDatabase    Database connection object
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getCustomOrderId($order_id)
    {
        global $objDatabase;

        $query = "
            SELECT `customer_id`
              FROM `".DBPREFIX."module_shop".MODULE_INDEX."_orders`
             WHERE `id`=$order_id";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult || $objResult->EOF) {
            return false;
        }
        $objCustomer = Customer::getById($objResult->fields['customer_id']);
        if (!$objCustomer) return false;
        return $objCustomer->lastname().$order_id;
        // Or something along the lines
        //$year = preg_replace('/^\d\d(\d\d).+$/', '$1', $orderDateTime);
        //return "$year-$order_id";
    }


    /**
     * Scale the given image size down to thumbnail size
     *
     * The target thumbnail size is taken from the configuration.
     * The argument and returned arrays use the indices as follows:
     *  array(0 => width, 1 => height)
     * In addition, index 3 of the array returned contains a
     * string with the width and height attribute string, very much like
     * the result of getimagesize().
     * Note that the array argument is passed by reference and its
     * values overwritten for the indices mentioned!
     * @param   array   $arrSize      The original image size array, by reference
     * @return  array                 The scaled down (thumbnail) image size array
     */
    static function scaleImageSizeToThumbnail(&$arrSize)
    {
        $thumbWidthMax = \Cx\Core\Setting\Controller\Setting::getValue('thumbnail_max_width','Shop');
        $thumbHeightMax = \Cx\Core\Setting\Controller\Setting::getValue('thumbnail_max_height','Shop');
        $ratioWidth = $thumbWidthMax/$arrSize[0];
        $ratioHeight = $thumbHeightMax/$arrSize[1];
        if ($ratioWidth > $ratioHeight) {
            $arrSize[0] = intval($arrSize[0]*$ratioHeight);
            $arrSize[1] = $thumbHeightMax;
        } else {
            $arrSize[0] = $thumbWidthMax;
            $arrSize[1] = intval($arrSize[1]*$ratioWidth);
        }
        $arrSize[3] = 'width="'.$arrSize[0].'" height="'.$arrSize[1].'"';
        return $arrSize;
    }


    /**
     * Remove the uniqid part from a file name that was added after
     * uploading the file
     *
     * The file name to be matched should look something like
     *  filename[uniqid].ext
     * Where uniqid is a 13 digit hexadecimal value created by uniqid().
     * This method will then return
     *  filename.ext
     * @param   string    $strFilename    The file name with the uniqid
     * @return  string                    The original file name
     */
    static function stripUniqidFromFilename($strFilename)
    {
        return preg_replace('/\[[0-9a-f]{13}\]/', '', $strFilename);
    }


    /**
     * Moves Product or Category images to the shop image folder if necessary
     * and changes the given file path from absolute to relative to the
     * shop image folder
     *
     * Images outside the shop image folder are copied there and all folder
     * parts are stripped.
     * Images inside the shop image folder are left where they are.
     * The path is changed to represent the new location, relative to the
     * shop image folder.
     * Leading folder separators are removed.
     * The changed path *SHOULD* be stored in the picture field as-is.
     * Examples (suppose the shop image folder ASCMS_SHOP_IMAGES_WEB_PATH
     * is 'images/shop'):
     * /var/www/mydomain/upload/test.jpg becomes images/shop/test.jpg
     * /var/www/mydomain/images/shop/test.jpg becomes images/shop/test.jpg
     * /var/www/mydomain/images/shop/folder/test.jpg becomes images/shop/folder/test.jpg
     * @param   string    $imageFileSource    The absolute image path, by reference
     * @return  boolean                       True on success, false otherwise
     */
    static function moveImage(&$imageFileSource)
    {
        global $_ARRAYLANG;

        $arrMatch = array();
        $shopImageFolderRe = '/^'.preg_quote(\Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteImagesShopWebPath().'/', '/').'/';
        $imageFileTarget = $imageFileSource;
        if (!preg_match($shopImageFolderRe, $imageFileSource))
            $imageFileTarget = \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteImagesShopWebPath() . '/' . basename($imageFileSource);
        // If the image is situated in or below the shop image folder,
        // don't bother to copy it.
        if (!preg_match($shopImageFolderRe, $imageFileSource)) {
            if (file_exists(\Cx\Core\Core\Controller\Cx::instanciate()->getWebsitePath() . $imageFileTarget)
             && preg_match('/(\.\w+)$/', $imageFileSource, $arrMatch)) {
                $imageFileTarget = preg_replace('/\.\w+$/', uniqid().$arrMatch[1], $imageFileTarget);
                \Message::information(sprintf(
                    $_ARRAYLANG['TXT_SHOP_IMAGE_RENAMED_FROM_TO'],
                    basename($imageFileSource), basename($imageFileTarget)
                ));
            }
            if (!copy(\Cx\Core\Core\Controller\Cx::instanciate()->getWebsitePath() . $imageFileSource, \Cx\Core\Core\Controller\Cx::instanciate()->getWebsitePath() . $imageFileTarget)) {
                //the function addError() declaration is missing, but defined so those lines are commented
//                self::addError(
//                    $imageFileSource.': '.
//                    $_ARRAYLANG['TXT_SHOP_COULD_NOT_COPY_FILE']
//                );
                $imageFileSource = false;
                return false;
            }
        }
        // Fix the original, absolute path to relative to the document root
        $imageFileSource = preg_replace($shopImageFolderRe, '', $imageFileTarget);
        return true;
    }


    /**
     * Send a confirmation e-mail with the order data
     *
     * Calls {@see Orders::getSubstitutionArray()}, which en route
     * creates User accounts for individual electronic Products by default.
     * Set $create_accounts to false when sending a copy.
     * @static
     * @param   integer   $order_id         The order ID
     * @param   boolean   $create_accounts  Create User accounts for electronic
     *                                      Products it true
     * @return  boolean                     The Customers' e-mail address
     *                                      on success, false otherwise
     * @access  private
     */
    static function sendConfirmationMail($order_id, $create_accounts=true)
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $orderRepo = $cx->getDb()->getEntityManager()->getRepository(
            'Cx\Modules\Shop\Model\Entity\Order'
        );
        $arrSubstitution =
            $orderRepo->getSubstitutionArray($order_id, $create_accounts);
        $customer_id = $arrSubstitution['CUSTOMER_ID'];
        $objCustomer = Customer::getById($customer_id);
        if (!$objCustomer) {
//die("Failed to get Customer for ID $customer_id");
            return false;
        }
        $arrSubstitution +=
              $objCustomer->getSubstitutionArray()
            + self::getSubstitutionArray()
            + array(
                'TIMESTAMP' =>
                    date(ASCMS_DATE_FORMAT_INTERNATIONAL_DATETIME,
                        // Local time, as configured by $_CONFIG['timezone']
                        date_timestamp_get(date_create())),
                'ROOT_URL' =>
                    \Cx\Core\Routing\Url::fromDocumentRoot()->toString());
//DBG::log("sendConfirmationMail($order_id, $create_accounts): Subs: ".var_dump($arrSubstitution, true));
        if (empty($arrSubstitution)) return false;
        // Prepared template for order confirmation
        $arrMailTemplate = array(
            'section' => 'Shop',
            'key' => 'order_confirmation',
            'lang_id' => $arrSubstitution['LANG_ID'],
            'to' =>
                $arrSubstitution['CUSTOMER_EMAIL'].','.
                \Cx\Core\Setting\Controller\Setting::getValue('email_confirmation','Shop'),
            'substitution' => &$arrSubstitution,
        );
//DBG::log("sendConfirmationMail($order_id, $create_accounts): Template: ".var_export($arrMailTemplate, true));
//DBG::log("sendConfirmationMail($order_id, $create_accounts): Substitution: ".var_export($arrSubstitution, true));
// NOTE: Creates some XML order file (for customizing)
//        $template = file_get_contents(
//            ASCMS_MODULE_PATH.'/Shop/View/Template/Backend/module_shop_export_orders.xml');
//        \Cx\Core\MailTemplate\Controller\MailTemplate::substitute($template, $arrSubstitution, true);
//        // Strip leftover comments from blocks: "<!---->" or "<!--  -->"
//        $template = preg_replace('/<!--\s*-->/', '', $template);
//        $file = new Cx\Lib\FileSystem\File(
//            ASCMS_DOCUMENT_ROOT.'/orders/'.$order_id.'.xml');
//        //$file->makeWritable(); // Fails on win32
//        $file->write($template);
///
        if (!\Cx\Core\MailTemplate\Controller\MailTemplate::send($arrMailTemplate)) return false;
        return $arrSubstitution['CUSTOMER_EMAIL'];
    }


    /**
     * Returns an array with all register options
     *
     * Keys are the respective class constant values, and the element values
     * are the language entries.
     * @see     getRegisterMenuoptions()
     * @return  array               The array of register options
     */
    static function getRegisterArray()
    {
        global $_ARRAYLANG;

        return array(
            self::REGISTER_MANDATORY => $_ARRAYLANG['TXT_SHOP_REGISTER_MANDATORY'],
            self::REGISTER_OPTIONAL => $_ARRAYLANG['TXT_SHOP_REGISTER_OPTIONAL'],
            self::REGISTER_NONE => $_ARRAYLANG['TXT_SHOP_REGISTER_NONE'],
        );
    }


    /**
     * Returns HTML code for the register menu options
     * @see     getRegisterArray()
     * @param   string    $selected     The optional selected option
     * @return  string                  The HTML options string
     */
    static function getRegisterMenuoptions($selected=null)
    {
        return \Html::getOptions(self::getRegisterArray(), $selected);
    }


    /**
     * Returns an array of values to be substituted
     *
     * Contains the following keys and values:
     *  'SHOP_COMPANY' => The company name (from the settings)
     *  'SHOP_HOMEPAGE' => The shop starting page URL
     * Used primarily for all MailTemplates.
     * Indexed by placeholder names.
     * @return  array           The substitution array
     */
    static function getSubstitutionArray()
    {
        // fetch shop country name
        $countryId = \Cx\Core\Setting\Controller\Setting::getValue('country_id','Shop');
        $country = \Cx\Core\Country\Controller\Country::getNameById($countryId);

        return array(
            'SHOP_COMPANY'  => \Cx\Core\Setting\Controller\Setting::getValue('company','Shop'),
            'SHOP_ADDRESS'  => \Cx\Core\Setting\Controller\Setting::getValue('address','Shop'),
            'SHOP_TELEPHONE'=> \Cx\Core\Setting\Controller\Setting::getValue('telephone','Shop'),
            'SHOP_FAX'      => \Cx\Core\Setting\Controller\Setting::getValue('fax','Shop'),
            'SHOP_COUNTRY'  => $country,
            'SHOP_EMAIL'    => \Cx\Core\Setting\Controller\Setting::getValue('email','Shop'),
            'SHOP_HOMEPAGE' => \Cx\Core\Routing\Url::fromModuleAndCmd(
                'Shop', '', FRONTEND_LANG_ID)->toString(),
        );
    }

    /**
     * Update relation between entity and foreign entity
     *
     * @param $table        string tablename
     * @param $attr         array  entity attr and foreign attr
     * @param $idsInput  array  with user input
     * @param $entityId     int    id of entity
     * @return bool
     */
    static function updateRelation($table, $attr, $idsInput, $entityId)
    {
        global $objDatabase;

        $queryCategories = '
            SELECT `'.$attr['foreign'].'`
              FROM `'.DBPREFIX.'module_shop'.MODULE_INDEX.'_'.$table.'`
              WHERE `'.$attr['entity'].'`='.$entityId;
        $objSelectResult = $objDatabase->Execute($queryCategories);

        $arrIdsDb = array();
        while ($objSelectResult && !$objSelectResult->EOF) {
            $arrIdsDb[] = $objSelectResult->fields[$attr['foreign']];
            $objSelectResult->MoveNext();
        }

        // Get all ids where in
        $newRelationIds = array_diff(
            $idsInput,
            $arrIdsDb
        );

        $deletedRelationIds = array_diff(
            $arrIdsDb,
            $idsInput
        );

        // $newRelationIds[0] is empty if no category is selected.
        if (empty($newRelationIds) || empty($newRelationIds[0])
            && empty($deletedRelationIds)) {
            return true;
        }

        foreach ($newRelationIds as $foreignId) {
            $updateArgs = array($entityId, $foreignId);
            $updateQuery = 'INSERT INTO `' . DBPREFIX.'module_shop'.MODULE_INDEX
                .'_'.$table.'` ('.$attr['entity'].', '.$attr['foreign'].')'
                .' VALUES (?,?)';

            $objResult = $objDatabase->Execute($updateQuery, $updateArgs);
            if (!$objResult) {
                return false;
            }
        }
        foreach ($deletedRelationIds as $foreignId) {
            $updateArgs = array($entityId, $foreignId);
            $updateQuery = 'DELETE FROM `' .
                DBPREFIX . 'module_shop' . MODULE_INDEX . '_'.$table.'`' .
                'WHERE '.$attr['entity'].' = ? AND '.$attr['foreign'].' = ? ';

            $objResult = $objDatabase->Execute($updateQuery, $updateArgs);
            if (!$objResult) {
                return false;
            }
        }

        return true;
    }

}
