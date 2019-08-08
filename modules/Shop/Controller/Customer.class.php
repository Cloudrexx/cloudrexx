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
 * Shop Customer
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  module_shop
 * @todo        Test!
 */

namespace Cx\Modules\Shop\Controller;

/**
 * Customer as used in the Shop.
 *
 * Extends the User class
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class Customer extends \User
{
    /**
     * Creates a Customer
     * @access  public
     * @return  Customer            The Customer
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function __construct()
    {
// TODO: Is that necessary?
        parent::__construct();
//DBG::log("Customer::__construct(): Made new ".get_class());
    }


    /*
     * Authenticate a Customer using his user name and password.
     *
     * Note that this overrides the parent method for testing purposes only
     * and *SHOULD NOT* be used for production.
     * See {@see User::auth()}.
     * @param   string  $username   The user name
     * @param   string  $password   The password
     * @return  Customer    The Customer object on success, false otherwise.
     */
//    function auth($username, $password)
//    {
//        return parent::auth($username, $password);
//        if (!parent::auth($username, $password)) return false;
//        $objUser = FWUser::getFWUserObject()->objUser;
//        $customer_id = $objUser->getId();
//DBG::log("Customer::auth(): This: ".var_export($objUser, true));
//DBG::log("Customer::auth(): Usergroups: ".var_export($objUser->getAssociatedGroupIds(), true));
//        return true;
//    }


    /**
     * Get the ID
     * @return  integer         $id                 Customer ID
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function id()
    {
        return $this->id;
    }

    /**
     * Get or set the user name
     * @param   string    $username       The optional user name
     * @return  string                    The user name
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function username($username=null)
    {
        if (isset($username)) {
            $this->setUsername($username);
        }
        return $this->getUsername();
    }

    /**
     * Get or set the e-mail address
     * @param   string    $email          The optional e-mail address
     * @return  string                    The e-mail address
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function email($email=null)
    {
        if (isset($email)) {
            $this->setEmail($email);
        }
        return $this->getEmail();
    }

    /**
     * Get or set the gender
     * @param   string        $gender     The optional gender
     * @return  string                    The gender
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function gender($gender=null)
    {
        if (isset($gender)) {
            $this->setProfile(array('gender' => array(0 => $gender)));
        }
        return $this->getProfileAttribute('gender');
    }

    /**
     * Get or set the first name
     * @param   string         $firstname   The optional first name
     * @return  string                      The first name
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function firstname($firstname=null)
    {
        if (isset($firstname)) {
            $this->setProfile(array('firstname' => array(0 => $firstname)));
        }
        return $this->getProfileAttribute('firstname');
    }

    /**
     * Get or set the last name
     * @param   string         $lastname    The optional last name
     * @return  string                      The last name
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function lastname($lastname=null)
    {
        if (isset($lastname)) {
            $this->setProfile(array('lastname' => array(0 => $lastname)));
        }
        return $this->getProfileAttribute('lastname');
    }

    /**
     * Get or set the company name
     * @param   string         $company     The optional company name
     * @return  string                      The company name
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function company($company=null)
    {
        if (isset($company)) {
            $this->setProfile(array('company' => array(0 => $company)));
        }
        return $this->getProfileAttribute('company');
    }

    /**
     * Get or set the address
     * @param   string    $address     The optional address (street and number)
     * @return  string                 The address (street and number)
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function address($address=null)
    {
        if (isset($address)) {
            $this->setProfile(array('address' => array(0 => $address)));
        }
        return $this->getProfileAttribute('address');
    }

    /**
     * Get or set the city name
     * @param   string    $city       The optional city name
     * @return  string                The city name
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function city($city=null)
    {
        if (isset($city)) {
            $this->setProfile(array('city' => array(0 => $city)));
        }
        return $this->getProfileAttribute('city');
    }

    /**
     * Get or set the zip code
     * @param   string    $zip        The optional zip code
     * @return  string                The zip code
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function zip($zip=null)
    {
        if (isset($zip)) {
            $this->setProfile(array('zip' => array(0 => $zip)));
        }
        return $this->getProfileAttribute('zip');
    }

    /**
     * Get or set the country ID
     * @param   integer   $country_id     The optional Country ID
     * @return  integer                   The Country ID
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function country_id($country_id=null)
    {
        if (isset($country_id)) {
            $this->setProfile(array('country' => array(0 => $country_id)));
        }
        return $this->getProfileAttribute('country');
    }

    /**
     * Get or set the phone number
     * @param   string    $phone_private  The optional phone number
     * @return  string                    The phone number
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function phone($phone_private=null)
    {
        if (isset($phone_private)) {
            $this->setProfile(array('phone_private' => array(0 => $phone_private)));
        }
        return $this->getProfileAttribute('phone_private');
    }

    /**
     * Get or set the fax number
     * @param   string    $phone_fax      The optional fax number
     * @return  string                    The fax number
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function fax($phone_fax=null)
    {
        if (isset($phone_fax)) {
            $this->setProfile(array('phone_fax' => array(0 => $phone_fax)));
        }
        return $this->getProfileAttribute('phone_fax');
    }

    /**
     * Get or set the company note
     * @param   string    $companynote    The optional company note
     * @return  string                    The company note
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function companynote($companynote=null)
    {
        $index = \Cx\Core\Setting\Controller\Setting::getValue('user_profile_attribute_notes','Shop');
        if (!$index) return null;
        if (isset($companynote)) {
            $this->setProfile(array($index => array(0 => $companynote)));
        }
        return $this->getProfileAttribute($index);
    }

    /**
     * Get or set the reseller status
     * @param   boolean   $is_reseller    The optional reseller status
     * @return  boolean                   True if the customer is a
     *                                    reseller, false otherwise.
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function is_reseller($is_reseller=null)
    {
        // get defined groups in shop
        $group_reseller = \Cx\Core\Setting\Controller\Setting::getValue('usergroup_id_reseller','Shop');
        if (empty($group_reseller)) {
            self::errorHandler();
            $group_reseller = \Cx\Core\Setting\Controller\Setting::getValue('usergroup_id_reseller','Shop');
        }
        $group_customer = \Cx\Core\Setting\Controller\Setting::getValue('usergroup_id_customer','Shop');
                if (empty($group_customer)) {
                    self::errorHandler();
                    $group_customer = \Cx\Core\Setting\Controller\Setting::getValue('usergroup_id_customer','Shop');
                }

        // return the value
        if (!isset($is_reseller)) {
            return (in_array($group_reseller, $this->getAssociatedGroupIds()));
            }

        // clean up associated groups by removing all shop groups from array
        $groups = $this->getAssociatedGroupIds();
        foreach ($groups as $i => $groupId) {
            if (!in_array($groupId, array($group_reseller, $group_customer))) {
                continue;
        }
            unset($groups[$i]);
    }

        // add selected shop group
        if ($is_reseller) {
            $groups[] = $group_reseller;
        } else {
            $groups[] = $group_customer;
        }
        $this->setGroups($groups);
    }

    /**
     * Get or set the Customer group ID
     * @param   integer   $group_id       The optional group ID
     * @return  integer                   The group ID
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function group_id($group_id=null)
    {
        $index = \Cx\Core\Setting\Controller\Setting::getValue('user_profile_attribute_customer_group_id','Shop');
        if (!$index) return false;
        if (isset($group_id)) {
            $this->setProfile(array($index => array(0 => $group_id)));
        }
        return $this->getProfileAttribute($index);
    }

    /**
     * Get or set the active status
     * @param   boolean   $active         The optional active status
     * @return  boolean                   True if the customer is active,
     *                                    false otherwise.
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function active($active=null)
    {
        if (isset($active)) {
            // do not change the status of the currently signed-in user
            if ($this->getId() == \FWUser::getFWUserObject()->objUser->getId()) {
                return $this->getActiveStatus();
            }

            $this->setActiveStatus($active);
        }
        return $this->getActiveStatus();
    }


    /**
     * Get or set the register date
     *
     * Note that this property is not writable in the parent class!
     * However, this is necessary in order to properly migrate old
     * Shop Customers.
     * @param   boolean   $active         The optional active status
     * @return  boolean                   True if the customer is active,
     *                                    false otherwise.
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function register_date($regdate=null)
    {
        if (isset($regdate)) {
            $this->regdate = $regdate;
        }
        return $this->getRegistrationDate();
    }


    /**
     * Delete this Customer from the database
     *
     * Also deletes all of her orders
     * @return  boolean                 True on success, false otherwise
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function delete($deleteOwnAccount=false)
    {
        global $_ARRAYLANG;

        if (!Orders::deleteByCustomerId($this->id)) {
            return \Message::error($_ARRAYLANG['TXT_SHOP_ERROR_CUSTOMER_DELETING_ORDERS']);
        }
        return parent::delete($deleteOwnAccount);
    }


    /**
     * Select a Customer by ID from the database.
     * @static
     * @param   integer     $id     The Customer ID
     * @return  Customer            The Customer object on success,
     *                              null otherwise
     */
    static function getById($id)
    {
        $objCustomer = new Customer();
        $objCustomer = $objCustomer->getUser($id);
        if (!$objCustomer) return null;
//DBG::log("Customer::getById($id): Usergroups: ".var_export($objCustomer->getAssociatedGroupIds(), true));
        return $objCustomer;
    }


    /**
     * Returns a Customer object according to the given criteria
     *
     * This extends the parent method by resolving the "group" index in
     * the $filter array to User IDs.  These are in turn passed to the
     * User class.
     * @param   array   $filter
     * @param   string  $search
     * @param   array   $arrSort
     * @param   array   $arrAttributes
     * @param   integer $limit
     * @param   integer $offset
     * @return  Customer                The Customer object on success,
     *                                  false otherwise
     */
    public function getUsers(
        $filter=null, $search=null, $arrSort=null, $arrAttributes=null,
        $limit=null, $offset=0
    ) {
        if (!empty($filter) && is_array($filter)) {
            if (isset($filter['group']) && is_array($filter['group'])) {
                $arrUserId = array();
                foreach ($filter['group'] as $group_id) {
                    $objGroup = \FWUser::getFWUserObject()->objGroup;
                    $objGroup = $objGroup->getGroup($group_id);
                    if ($objGroup) {
                        $_arrUserId = $objGroup->getAssociatedUserIds();
//DBG::log("Customer::getUsers(): Group ID $group_id, User IDs: ".var_export($_arrUserId, true));
                        $arrUserId = array_merge($arrUserId, $_arrUserId);
//DBG::log("Customer::getUsers(): Merged: ".var_export($arrUserId, true));
                    }
                }
                $filter['id'] = ($arrUserId
                    ? array_unique($arrUserId) : array(0));
                unset($filter['group']);
            }
        }
//DBG::log("Customer::getUsers(): Filter: ".var_export($filter, true));
        if ($this->loadUsers($filter, $search, $arrSort, $arrAttributes, $limit, $offset)) {
            return $this;
        }
        return false;
    }


    /**
     * Returns the unregistered Customer with the given e-mail address
     *
     * Note the implicit contradiction.  Even unregistered Customers
     * are stored in the database and retrieved when they visit the Shop
     * again.  However, such Users are always inactive, and thus cannot
     * log in.  They are identified by their e-mail address and updated with
     * the current data.  That information is needed for processing the order
     * and sending confirmation e-mails.
     * Note that this kind of Customer is limited to the group of final
     * customers.  This implies that no reseller prices are available to
     * unregistered Customers.
     * @param   string  $email    The e-mail address
     * @return  User              The Customer on success, null otherwise
     * @todo    Add the Customer Usergroup to the filter and test that
     */
    static function getUnregisteredByEmail($email)
    {
        global $_ARRAYLANG;

        // Only final customers may be unregistered
        $usergroup_id = \Cx\Core\Setting\Controller\Setting::getValue('usergroup_id_customer','Shop');
        if (!$usergroup_id) {
            \Message::error($_ARRAYLANG['TXT_SHOP_ERROR_USERGROUP_INVALID']);
            \Cx\Core\Csrf\Controller\Csrf::redirect(CONTREXX_DIRECTORY_INDEX.'?section=Shop');
        }
        $objUser = \FWUser::getFWUserObject()->objUser;
        $objUser = $objUser->getUsers(array(
            'email' => $email,
            'active' => false,
// TODO: Verify this:  We must be able to load existing Users!
// Problem: Conflicting e-mail addresses for "new" Customers that exist as Users already.
// Simple solution seems to be to ignore the associated groups.
//            'group_id' => $usergroup_id,
        ));
        if (!$objUser) {
//DBG::log("Customer::getUnregisteredByEmail($email): Found no such unregistered User");
            return null;
        }
//DBG::log("Customer::getUnregisteredByEmail($email): Found unregistered User ID ".$objUser->getId()." (".$objUser->getEmail().")");
        return self::getById($objUser->getId());
    }


    /**
     * Returns the registered Customer with the given e-mail address
     * @param   string  $email    The e-mail address
     * @return  User              The Customer on success, null otherwise
     * @todo    Add the Customer Usergroup to the filter and test that
     */
    static function getRegisteredByEmail($email)
    {
        // Any Customers
        $objUser = \FWUser::getFWUserObject()->objUser;
        $objUser = $objUser->getUsers(array(
            'email' => $email,
            'active' => true,
        ));
        if (!$objUser) {
//DBG::log("Customer::getUnregisteredByEmail($email): Found no such unregistered User");
            return null;
        }
//DBG::log("Customer::getUnregisteredByEmail($email): Found unregistered User ID ".$objUser->getId()." (".$objUser->getEmail().")");
        return self::getById($objUser->getId());
    }


    /**
     * Returns an array of Customer data for MailTemplate substitution
     *
     * The password is no longer available in the session if the confirmation
     * is sent after paying with some external PSP that uses some form of
     * instant payment notification (i.e. PayPal)!
     * In that case, it is *NOT* included in the template produced.
     * Call {@see Shop::sendLogin()} while processing the Order instead.
     * @return    array               The Customer data substitution array
     * @see       MailTemplate::substitute()
     */
    function getSubstitutionArray()
    {
        global $_ARRAYLANG;

// See below.
//        $index_notes = \Cx\Core\Setting\Controller\Setting::getValue('user_profile_attribute_notes','Shop');
//        $index_type = \Cx\Core\Setting\Controller\Setting::getValue('user_attribute_customer_type','Shop');
//        $index_reseller = \Cx\Core\Setting\Controller\Setting::getValue('user_attribute_reseller_status','Shop');
        $gender = strtoupper($this->gender());
        $title = $_ARRAYLANG['TXT_SHOP_TITLE_'.$gender];
        $format_salutation = $_ARRAYLANG['TXT_SHOP_SALUTATION_'.$gender];
        $salutation = sprintf($format_salutation,
            $this->firstname(), $this->lastname(), $this->company(), $title);
        $arrSubstitution = array(
            'CUSTOMER_SALUTATION' => $salutation,
            'CUSTOMER_TITLE' => $title,
            'CUSTOMER_ID' => $this->id(),
            'CUSTOMER_EMAIL' => $this->email(),
            'CUSTOMER_COMPANY' => $this->company(),
            'CUSTOMER_FIRSTNAME' => $this->firstname(),
            'CUSTOMER_LASTNAME' => $this->lastname(),
            'CUSTOMER_ADDRESS' => $this->address(),
            'CUSTOMER_ZIP' => $this->zip(),
            'CUSTOMER_CITY' => $this->city(),
            'CUSTOMER_COUNTRY' => \Cx\Core\Country\Controller\Country::getNameById($this->country_id()),
            'CUSTOMER_PHONE' => $this->phone(),
            'CUSTOMER_FAX' => $this->fax(),
            'CUSTOMER_USERNAME' => $this->username(),
            'CUSTOMER_BIRTHDAY' => date(ASCMS_DATE_FORMAT_DATE, $this->getProfileAttribute('birthday')),
// There are not used in any MailTemplate so far:
//            'CUSTOMER_COUNTRY_ID' => $this->country_id(),
//            'CUSTOMER_NOTE' => $this->getProfileAttribute($index_notes),
//            'CUSTOMER_TYPE' => $this->getProfileAttribute($index_type),
//            'CUSTOMER_RESELLER' => $this->getProfileAttribute($index_reseller),
//            'CUSTOMER_GROUP_ID' => current($this->getAssociatedGroupIds()),
        );
//DBG::log("Login: ".$this->username()."/".$_SESSION['shop']['password']);
        if (!empty($_SESSION['shop']['password'])) {
            $arrSubstitution['CUSTOMER_LOGIN'] = array(0 => array(
                'CUSTOMER_USERNAME' => $this->username(),
                'CUSTOMER_PASSWORD' => $_SESSION['shop']['password'],
            ));
        }
        return $arrSubstitution;
    }


    /**
     * Returns a string representing the name of this customer
     *
     * The format of the string is determined by the optional
     * $format parameter in sprintf() format:
     *  - %1$u : ID
     *  - %2$s : Company
     *  - %3$s : First name
     *  - %4$s : Last name
     * Defaults to '%2$s %3$s %4$s (%1$u)'.
     * The result is trimmed before it is returned.
     * @param   string    $format         The optional format string
     * @return  string                    The Customer name
     */
    function getName($format=null)
    {
        if (!isset($format)) $format = '%2$s %3$s %4$s (%1$u)';
        return trim(sprintf($format,
            $this->id(),
            $this->company(),
            $this->firstname(),
            $this->lastname()
        ));
    }


    /**
     * Returns the HTML dropdown menu code for selecting the gender/title
     *
     * @global  array   $_ARRAYLANG     The language array
     * @param   string  $selected       The optional select tag name attribute
     *                                  value.  Defaults to 'shipPrefix'
     * @param   string  $name           The optional
     * @return  string                  The HTML menu code
     */
    static function getGenderMenu($selected, $name='gender')
    {
        global $_ARRAYLANG;
//die("Customer::getGenderMenu(): HERE");
        $objAttribute = \FWUser::getFWUserObject()->objUser->objAttribute;
        if (!$objAttribute) return null;
        $objAttribute = $objAttribute->getById('gender');
//die("Customer::getGenderMenu(): Attribute: ".var_export($objAttribute, true));
        $arrAttribute = array();
//die("Customer::getGenderMenu(): Attribute Menu: ".var_export($arrAttribute, true));
        foreach ($objAttribute->getChildren() as $attribute_id) {
            $arrAttribute[$attribute_id] =
                $_ARRAYLANG['TXT_SHOP_'.strtoupper($attribute_id)];
        }
//DBG::log("Customer::getGenderMenu(): Attribute Array: ".var_export($arrAttribute, true));
        return \Html::getSelect($name, $arrAttribute, $selected);
    }


    /**
     * Updates the password of the Customer with the given e-mail address
     * @param   string    $email        The e-mail address
     * @param   string    $password     The new password
     * @return  boolean                 True on success, false otherwise
     */
    static function updatePassword($email, $password)
    {
        $objUser = \FWUser::getFWUserObject()->objUser->getUsers(
            array('email' => $email));
        if (!$objUser) return false;
        $objUser->setPassword($password);
        return $objUser->store();
    }


    /**
     * Handles database errors
     *
     * Also migrates old Shop Customers to the User accounts and adds
     * all new settings
     * @return  boolean     false     Always!
     * @throws  Cx\Lib\Update_DatabaseException
     */
    static function errorHandler()
    {
// Customer
        $table_name_old = DBPREFIX."module_shop_customers";
        // If the old Customer table is missing, the migration has completed
        // successfully already
        if (!\Cx\Lib\UpdateUtil::table_exist($table_name_old)) {
            return false;
        }

        // Ensure that the ShopSettings (including \Cx\Core\Setting) and Order tables
        // are ready first!
//DBG::log("Customer::errorHandler(): Adding settings");
        ShopSettings::errorHandler();
//        \Cx\Core\Country\Controller\Country::errorHandler(); // Called by Order::errorHandler();
        Order::errorHandler();
        Discount::errorHandler();

        \Cx\Core\Setting\Controller\Setting::init('Shop', 'config');
        $objUser = \FWUser::getFWUserObject()->objUser;
        // Create new User_Profile_Attributes
        $index_notes = \Cx\Core\Setting\Controller\Setting::getValue('user_profile_attribute_notes','Shop');
        if (!$index_notes) {
//DBG::log("Customer::errorHandler(): Adding notes attribute...");
//            $objProfileAttribute = new \User_Profile_Attribute();
            $objProfileAttribute = $objUser->objAttribute->getById(0);
//DBG::log("Customer::errorHandler(): NEW notes attribute: ".var_export($objProfileAttribute, true));
            $objProfileAttribute->setNames(array(
                1 => 'Notizen',
                2 => 'Notes',
// TODO: Translate
                3 => 'Notes', 4 => 'Notes', 5 => 'Notes', 6 => 'Notes',
            ));
            $objProfileAttribute->setType('text');
            $objProfileAttribute->setMultiline(true);
            $objProfileAttribute->setParent(0);
            $objProfileAttribute->setProtection(array(1));
//DBG::log("Customer::errorHandler(): Made notes attribute: ".var_export($objProfileAttribute, true));
            if (!$objProfileAttribute->store()) {
                throw new \Cx\Lib\Update_DatabaseException(
                   "Failed to create User_Profile_Attribute 'notes'");
            }

            //Re initialize shop setting
            \Cx\Core\Setting\Controller\Setting::init('Shop', 'config');
//DBG::log("Customer::errorHandler(): Stored notes attribute, ID ".$objProfileAttribute->getId());
            if (!(\Cx\Core\Setting\Controller\Setting::set('user_profile_attribute_notes', $objProfileAttribute->getId())
               && \Cx\Core\Setting\Controller\Setting::update('user_profile_attribute_notes'))) {
                throw new \Cx\Lib\Update_DatabaseException(
                   "Failed to update User_Profile_Attribute 'notes' setting");
            }
//DBG::log("Customer::errorHandler(): Stored notes attribute ID setting");
        }

        $index_group = \Cx\Core\Setting\Controller\Setting::getValue('user_profile_attribute_customer_group_id','Shop');
        if (!$index_group) {
//            $objProfileAttribute = new \User_Profile_Attribute();
            $objProfileAttribute = $objUser->objAttribute->getById(0);
            $objProfileAttribute->setNames(array(
                1 => 'Kundenrabattgruppe',
                2 => 'Discount group',
// TODO: Translate
                3 => 'Kundenrabattgruppe', 4 => 'Kundenrabattgruppe',
                5 => 'Kundenrabattgruppe', 6 => 'Kundenrabattgruppe',
            ));
            $objProfileAttribute->setType('text');
            $objProfileAttribute->setParent(0);
            $objProfileAttribute->setProtection(array(1));
            if (!$objProfileAttribute->store()) {
                throw new \Cx\Lib\Update_DatabaseException(
                   "Failed to create User_Profile_Attribute 'notes'");
            }

            //Re initialize shop setting
            \Cx\Core\Setting\Controller\Setting::init('Shop', 'config');
            if (!(\Cx\Core\Setting\Controller\Setting::set('user_profile_attribute_customer_group_id', $objProfileAttribute->getId())
               && \Cx\Core\Setting\Controller\Setting::update('user_profile_attribute_customer_group_id'))) {
                throw new \Cx\Lib\Update_DatabaseException(
                   "Failed to update User_Profile_Attribute 'customer_group_id' setting");
            }
        }

        // For the migration, a temporary flag is needed in the orders table
        // in order to prevent mixing up old and new customer_id values.
        $table_order_name = DBPREFIX."module_shop_orders";
        if (!\Cx\Lib\UpdateUtil::column_exist($table_order_name, 'migrated')) {
            $query = "
                ALTER TABLE `$table_order_name`
                  ADD `migrated` TINYINT(1) unsigned NOT NULL default 0";
            \Cx\Lib\UpdateUtil::sql($query);
        }

        // Create missing UserGroups for customers and resellers
        $objGroup = null;
        $group_id_customer = \Cx\Core\Setting\Controller\Setting::getValue('usergroup_id_customer','Shop');
        if ($group_id_customer) {
            $objGroup = \FWUser::getFWUserObject()->objGroup->getGroup(
                $group_id_customer);
        }
        if (!$objGroup || $objGroup->EOF) {
            $objGroup = \FWUser::getFWUserObject()->objGroup->getGroups(
                array('group_name' => 'Shop Endkunden'));
        }
        if (!$objGroup || $objGroup->EOF) {
            $objGroup = new \UserGroup();
            $objGroup->setActiveStatus(true);
            $objGroup->setDescription('Online Shop Endkunden');
            $objGroup->setName('Shop Endkunden');
            $objGroup->setType('frontend');
        }
//DBG::log("Group: ".var_export($objGroup, true));
        if (!$objGroup) {
            throw new \Cx\Lib\Update_DatabaseException(
               "Failed to create UserGroup for customers");
        }
//DBG::log("Customer::errorHandler(): Made customer usergroup: ".var_export($objGroup, true));
        if (!$objGroup->store() || !$objGroup->getId()) {
            throw new \Cx\Lib\Update_DatabaseException(
                "Failed to store UserGroup for customers");
        }
//DBG::log("Customer::errorHandler(): Stored customer usergroup, ID ".$objGroup->getId());
        \Cx\Core\Setting\Controller\Setting::set('usergroup_id_customer', $objGroup->getId());
        if (!\Cx\Core\Setting\Controller\Setting::update('usergroup_id_customer')) {
            throw new \Cx\Lib\Update_DatabaseException(
               "Failed to store UserGroup ID for customers");
        }
        $group_id_customer = $objGroup->getId();
        $objGroup = null;
        $group_id_reseller = \Cx\Core\Setting\Controller\Setting::getValue('usergroup_id_reseller','Shop');
        if ($group_id_reseller) {
            $objGroup = \FWUser::getFWUserObject()->objGroup->getGroup($group_id_reseller);
        }
        if (!$objGroup || $objGroup->EOF) {
            $objGroup = \FWUser::getFWUserObject()->objGroup->getGroups(
                array('group_name' => 'Shop Wiederverkäufer'));
        }
        if (!$objGroup || $objGroup->EOF) {
            $objGroup = new \UserGroup();
            $objGroup->setActiveStatus(true);
            $objGroup->setDescription('Online Shop Wiederverkäufer');
            $objGroup->setName('Shop Wiederverkäufer');
            $objGroup->setType('frontend');
        }
        if (!$objGroup) {
            throw new \Cx\Lib\Update_DatabaseException(
               "Failed to create UserGroup for resellers");
        }
//DBG::log("Customer::errorHandler(): Made reseller usergroup: ".var_export($objGroup, true));
        if (!$objGroup->store() || !$objGroup->getId()) {
            throw new \Cx\Lib\Update_DatabaseException(
                "Failed to store UserGroup for resellers");
        }
        \Cx\Core\Setting\Controller\Setting::set('usergroup_id_reseller', $objGroup->getId());
        if (!\Cx\Core\Setting\Controller\Setting::update('usergroup_id_reseller')) {
            throw new \Cx\Lib\Update_DatabaseException(
               "Failed to store UserGroup ID for resellers");
        }
        $group_id_reseller = $objGroup->getId();

        $default_lang_id = \FWLanguage::getDefaultLangId();
        $query = "
            SELECT `customer`.`customerid`,
                   `customer`.`prefix`, `customer`.`firstname`,
                   `customer`.`lastname`,
                   `customer`.`company`, `customer`.`address`,
                   `customer`.`city`, `customer`.`zip`,
                   `customer`.`country_id`,
                   `customer`.`phone`, `customer`.`fax`,
                   `customer`.`email`,
                   `customer`.`username`, `customer`.`password`,
                   `customer`.`company_note`,
                   `customer`.`is_reseller`,
                   `customer`.`customer_status`, `customer`.`register_date`,
                   `customer`.`group_id`
              FROM `$table_name_old` AS `customer`
             ORDER BY `customer`.`customerid` ASC";
        $objResult = \Cx\Lib\UpdateUtil::sql($query);
        while (!$objResult->EOF) {
            $old_customer_id = $objResult->fields['customerid'];
            if (empty($objResult->fields['email'])) {
                $objResult->fields['email'] = $objResult->fields['username'];
            }
            $email = $objResult->fields['email'];
            $objUser = \FWUser::getFWUserObject()->objUser->getUsers(
                array('email' => array(0 => $email)));

// TODO: See whether a User with that username (but different e-mail address) exists!
            $objUser_name = \FWUser::getFWUserObject()->objUser->getUsers(
                array('username' => array(
                    0 => $objResult->fields['username'])));
            if ($objUser && $objUser_name) {
                $objUser = $objUser_name;
            }

            $objCustomer = null;
            if ($objUser) {
                $objCustomer = self::getById($objUser->getId());
            }
            if (!$objCustomer) {
                $lang_id = Order::getLanguageIdByCustomerId($old_customer_id);
                if (!$lang_id) $lang_id = $default_lang_id;
                $objCustomer = new Customer();
                if (preg_match('/^(?:frau|mad|mme|signora|miss)/i',
                    $objResult->fields['prefix'])) {
                    $objCustomer->gender('gender_female');
                } elseif (preg_match('/^(?:herr|mon|signore|mister|mr)/i',
                    $objResult->fields['prefix'])) {
                    $objCustomer->gender('gender_male');
//                } else {
// Other "genders", like "family", "thing", or "it" won't be matched
// and are left on "gender_unknown".
//DBG::log("*** Prefix {$objResult->fields['prefix']}, UNKNOWN GENDER!");
                }
//DBG::log("Prefix {$objResult->fields['prefix']}, made gender ".$objCustomer->gender());

                $objCustomer->company($objResult->fields['company']);
                $objCustomer->firstname($objResult->fields['firstname']);
                $objCustomer->lastname($objResult->fields['lastname']);
                $objCustomer->address($objResult->fields['address']);
                $objCustomer->city($objResult->fields['city']);
                $objCustomer->zip($objResult->fields['zip']);
                $objCustomer->country_id($objResult->fields['country_id']);
                $objCustomer->phone($objResult->fields['phone']);
                $objCustomer->fax($objResult->fields['fax']);
                $objCustomer->email($objResult->fields['email']);
                $objCustomer->companynote($objResult->fields['company_note']);
                $objCustomer->active($objResult->fields['customer_status']);
                // Handled by a UserGroup now, see below
                //$objCustomer->setResellerStatus($objResult->fields['is_reseller']);
                $objCustomer->register_date($objResult->fields['register_date']);
                $objCustomer->group_id($objResult->fields['group_id']);
// NOTE: Mind that the User class has been modified to accept e-mail addresses
// as usernames!
                $objCustomer->username($objResult->fields['username']);
                // Copy the md5 hash of the password!
                $objCustomer->setHashedPassword($objResult->fields['password']);
                $objCustomer->setFrontendLanguage($lang_id);
            }
            if ($objResult->fields['is_reseller']) {
                $objCustomer->setGroups(
                    $objCustomer->getAssociatedGroupIds()
                    + array($group_id_reseller));
//DBG::log("Customer::errorHandler(): Added reseller: ".$objCustomer->id());
            } else {
                $objCustomer->setGroups(
                    $objCustomer->getAssociatedGroupIds()
                    + array($group_id_customer));
//DBG::log("Customer::errorHandler(): Added customer: ".$objCustomer->id());
            }
            if (!$objCustomer->store()) {
//DBG::log(var_export($objCustomer, true));
                throw new \Cx\Lib\Update_DatabaseException(
                   "Failed to migrate existing Customer ID ".
                   $old_customer_id.
                   " to Users (Messages: ".
                   join(', ', $objCustomer->error_msg).")");
            }
            // Update the Orders table with the new Customer ID.
            // Note that we use the ambiguous old customer ID that may
            // coincide with a new User ID, so to prevent inconsistencies,
            // migrated Orders are marked as such.
            $query = "
                UPDATE `$table_order_name`
                   SET `customer_id`=".$objCustomer->id().",
                       `migrated`=1
                 WHERE `customer_id`=$old_customer_id
                   AND `migrated`=0";
            \Cx\Lib\UpdateUtil::sql($query);
            // Drop migrated
            $query = "
                DELETE FROM `$table_name_old`
                 WHERE `customerid`=$old_customer_id";
            \Cx\Lib\UpdateUtil::sql($query);
            $objResult->MoveNext();
            if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                return false;
            }
        }

        // Remove the flag, it's no longer needed.
        // (You could also verify that all records have been migrated by
        // querying them with "[...] WHERE `migrated`=0", which *MUST* result
        // in an empty recordset.  This is left as an exercise for the reader.)
        $query = "
            ALTER TABLE `$table_order_name`
             DROP `migrated`";
        \Cx\Lib\UpdateUtil::sql($query);

        \Cx\Lib\UpdateUtil::drop_table($table_name_old);

//DBG::log("Updated Customer table and related stuff");
        // Always
        return false;
    }

}
