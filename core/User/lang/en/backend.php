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
 * This is the english language file for backend mode.
 * This file is included by Cloudrexx and all entries are set as placeholder
 * values for backend ACT template by SystemComponentBackendController
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_user
 */

global $_ARRAYLANG;

// Let's start with module info:
$_ARRAYLANG['TXT_CORE_USER'] = 'User';
$_ARRAYLANG['TXT_CORE_USER_DESCRIPTION'] = 'This is a new module with some sample content to show how to start.';

// Here come the ACTs:
$_ARRAYLANG['TXT_CORE_USER_ACT_DEFAULT'] = 'Users';
$_ARRAYLANG['TXT_CORE_USER_ACT_GROUP'] = 'Groups';
$_ARRAYLANG['TXT_CORE_USER_ACT_SETTINGS'] = 'Settings';
$_ARRAYLANG['TXT_CORE_USER_ACT_SETTINGS_DEFAULT'] = 'Mailing';
$_ARRAYLANG['TXT_CORE_USER_ACT_SETTINGS_HELP'] = 'Help';

// Now our content specific values:
$_ARRAYLANG['TXT_CORE_USER_CONGRATULATIONS'] = 'Overview';
$_ARRAYLANG['TXT_CORE_USER_SUCCESSFUL_CREATION'] = 'This is the Overview/Dashboard of your new Component. More tabs will be generated if you add entities to this component.';
$_ARRAYLANG['TXT_CORE_USER_EXAMPLE_TEMPLATE'] = 'This is the default template for this component, located in View/Template/Backend/Default.html. In order to add entities, place your YAML files in Model/Yaml folder and execute ./workbench.bat db update. Then add a language file entry for your entity.';

$_ARRAYLANG['TXT_CORE_USER_CONGRATULATIONS'] = 'Overview';
$_ARRAYLANG['TXT_CORE_USER_SUCCESSFUL_CREATION'] = 'This is the Overview/Dashboard of your new Component. More tabs will be generated if you add entities to this component.';
$_ARRAYLANG['TXT_CORE_USER_EXAMPLE_TEMPLATE'] = 'This is the default template for this component, located in View/Template/Backend/Default.html. In order to add entities, place your YAML files in Model/Yaml folder and execute ./workbench.bat db update. Then add a language file entry for your entity.';
$_ARRAYLANG['TXT_CORE_USER_EDIT_TITLE'] = 'Edit user account';
$_ARRAYLANG['TXT_CORE_USER_EMAIL_TITLE'] = 'Send e-mail to';
$_ARRAYLANG['id'] = 'ID';
$_ARRAYLANG['isAdmin'] = 'Administrator';
$_ARRAYLANG['email'] = 'E-Mail';
$_ARRAYLANG['regdate'] = 'Registered since';
$_ARRAYLANG['lastActivity'] = 'Last activity';
$_ARRAYLANG['groupId'] = 'ID';
$_ARRAYLANG['isActive'] = 'Status';
$_ARRAYLANG['groupName'] = 'Name';
$_ARRAYLANG['groupDescription'] = 'Description';
$_ARRAYLANG['type'] = 'Typ';
$_ARRAYLANG['username'] = 'Username';

$_ARRAYLANG['picture'] = 'Profile image';
$_ARRAYLANG['gender'] = 'Gender';
$_ARRAYLANG['title'] = 'Salutation';
$_ARRAYLANG['designation'] = 'Designation';
$_ARRAYLANG['firstname'] = 'First name';
$_ARRAYLANG['lastname'] = 'Last name';
$_ARRAYLANG['company'] = 'Company';
$_ARRAYLANG['address'] = 'Address';
$_ARRAYLANG['city'] = 'City';
$_ARRAYLANG['zip'] = 'City';
$_ARRAYLANG['country'] = 'Country';
$_ARRAYLANG['phone_office'] = 'Office phone';
$_ARRAYLANG['phone_private'] = 'Private phone';
$_ARRAYLANG['phone_mobile'] = 'Private phone';
$_ARRAYLANG['phone_fax'] = 'Fax';
$_ARRAYLANG['birthday'] = 'Birthday';
$_ARRAYLANG['website'] = 'Website';
$_ARRAYLANG['profession'] = 'Profession';
$_ARRAYLANG['interests'] = 'Interests';
$_ARRAYLANG['signature'] = 'Signature';
$_ARRAYLANG['active'] = 'Status';
$_ARRAYLANG['expiration'] = 'Expiration';
$_ARRAYLANG['profileAccess'] = 'Privacy';
$_ARRAYLANG['frontendLangId'] = 'Language';
$_ARRAYLANG['passwordConfirmed'] = 'Confirm password';
$_ARRAYLANG['password'] = 'Password';
$_ARRAYLANG['primaryGroup'] = 'Primary User Group';
$_ARRAYLANG['group'] = 'Assigned Groups';
$_ARRAYLANG['newsletter'] = 'Newsletter';
$_ARRAYLANG['moduleSpecificExtensions'] = 'Additional Functions';
$_ARRAYLANG['downloadExtension'] = 'Digital Asset Management';

$_ARRAYLANG['TXT_CORE_USER_PROFILE'] = 'Profile';
$_ARRAYLANG['TXT_CORE_USER_GROUP_S'] = "User group(s)";
$_ARRAYLANG['TXT_CORE_USER_NONE_SPECIFIED'] = 'None specified';
$_ARRAYLANG['TXT_CORE_USER_GENDER_UNDEFINED'] = 'Not specified';
$_ARRAYLANG['TXT_CORE_USER_GENDER_FEMALE'] = 'Female';
$_ARRAYLANG['TXT_CORE_USER_GENDER_MALE'] = 'Male';

$_ARRAYLANG['TXT_CORE_USER_PASSWORD_TOO_SHORT'] = "Too short";
$_ARRAYLANG['TXT_CORE_USER_PASSWORD_WEAK'] = "Weak";
$_ARRAYLANG['TXT_CORE_USER_PASSWORD_GOOD'] = "Good";
$_ARRAYLANG['TXT_CORE_USER_PASSWORD_STRONG'] = "Strong";
$_ARRAYLANG['TXT_CORE_USER_PASSWORD_INVALID'] = "Invalid";
$_ARRAYLANG['TXT_CORE_USER_PASSWORD_MINIMAL_CHARACTERS'] = "The password must be at least 6 characters long.<br /><br />The complexity requirement can be enabled in the global configuration.";
$_ARRAYLANG['TXT_CORE_USER_PASSWORD_MINIMAL_CHARACTERS_WITH_COMPLEXITY'] = "The password must be at least 6 characters long as well as contain at least one upper and one lower case character and one number.<br /><br />The complexity requirement can be disabled in the global configuration.";

$_ARRAYLANG['TXT_CORE_USER_ADMINISTRATOR'] = "Administrator";
$_ARRAYLANG['TXT_CORE_USER_NO_ADMINISTRATOR'] = "No administrator";

$_ARRAYLANG['TXT_CORE_USER_FILTER'] = 'Filter';
$_ARRAYLANG['TXT_CORE_USER_ALL'] = 'All';
$_ARRAYLANG['TXT_CORE_SELECT_GROUP'] = 'Select group';
$_ARRAYLANG['TXT_CORE_USER_ACCOUNT'] = "User account";
$_ARRAYLANG['TXT_CORE_USER_ROLE'] = "User role";
$_ARRAYLANG['TXT_CORE_USER_STATUS'] = "User status";
$_ARRAYLANG['TXT_CORE_USER_ONLY_CRM'] = "Only CRM";
$_ARRAYLANG['TXT_CORE_USER_ACTIVE'] = "Active";
$_ARRAYLANG['TXT_CORE_USER_INACTIVE'] = "Inactive";
$_ARRAYLANG['TXT_CORE_USER_ADMINISTRATORS'] = "Administrators";
$_ARRAYLANG['TXT_CORE_USER_USERS'] = "Users";
$_ARRAYLANG['TXT_CORE_USER_NO_USER_WITH_SAME_ID'] = "You can't disable your own user account.";
$_ARRAYLANG['TXT_CORE_USER_ADD_DAM_CATEGORY'] = "Add a new, personal category to the Digital Asset Management module which is only accessable by this user.";
$_ARRAYLANG['TXT_CORE_USER_CUSTOMER_TITLE'] = "Customer: %s";
$_ARRAYLANG['TXT_CORE_USER_ACCOUNT_GROUP_DESC'] = "Personal group of the user %s";
$_ARRAYLANG['TXT_CORE_USER_NEW_DAM_CATEGORY_CREATED_TXT'] = "A new category has been created for the user %s in the Digital Asset Management module. The category's name is %s.";
$_ARRAYLANG['TXT_CORE_USER_NOT FOUND'] = 'User could not be found.';
$_ARRAYLANG['TXT_CORE_USER_NOT_BE_SAVED'] = 'User could not be saved.';
$_ARRAYLANG['TXT_CORE_USER_ATTRIBUTE_NOT_FOUND'] = 'Attribute could not be found.';
$_ARRAYLANG['TXT_CORE_USER_DO_NOT_EDIT_USER_ONE'] = 'This user is not allowed to be edited!';