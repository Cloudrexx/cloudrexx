<?php
global $_ARRAYLANG;

// Let's start with module info:
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE'] = 'MultiSite';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_DESCRIPTION'] = 'MultiSite erlaubt die Erstellung mehrerer unabhängiger Webseiten mit einer einzigen Installation von Contrexx';

// configuration options
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITEPATH'] = 'Websites path';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITEPATH_TOOLTIP'] = 'The Websites path specifies the absolute path in the file system where the data-directories of the websites are stored. Do specify the path without a trailing slash. I.e.: /var/www/websites';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_UNAVAILABLEPREFIXES'] = 'Unavailable website names';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITENAMEMAXLENGTH'] = 'Maximal length of website names';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITENAMEMINLENGTH'] = 'Minimal length of website names';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SENDSETUPERROR'] = 'Send setup error reports';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SENDSETUPERROR_TOOLTIP'] = 'Activate to send error reports of the <i>Website setup process</i> to the email address specified by option <i>Email of administrator</i> in tab <i>Contact Information</i> in section <i>Administration > Global Configuration</i>.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MULTISITEPROTOCOL'] = 'MultiSite API Operation';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MULTISITEPROTOCOLIN'] = 'Protocol on which this server accepts MultiSite API requests';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITEDATABASEPREFIX'] = 'Database prefix for websites';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITEDATABASEPREFIX_TOOLTIP'] = 'Set the database prefix that shall be used to address the databases of the website websites. The database prefix will also be used as the database-name prefix when creating a new website website. The database prefix must not exceed the maximal length of 54 characters and must follow the MySQL Identifier Scheme.
I.e.: cloudrexx_website_';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITEDATABASEUSERPREFIX'] = 'Database user prefix for websites';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITEDATABASEUSERPREFIX_TOOLTIP'] = 'The database user prefix will be used as the username-prefix when adding a new database user for a newly created website website. The database user prefix must not exceed the maximal length of 6 characters.
I.e.: clx_i_';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MODE']='MultiSite operation mode';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_DEFAULTWEBSITEIP']='defaultWebsiteIp';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MODE_TOOLTIP']='Select the mode in which the <i>MultiSite</i> component shall operate in.<br>Modes:<ul><li><b>none:</b> <i>MultiSite</i> functionality is not in use. This Contrexx installation will act as a regular website.</li><li><b>manager:</b> This Contrexx installation shall act as the <i>Website Manager Server</i>.</li><li><b>service:</b> This Contrexx installation shall act as a <i>Website Service Server</i>.</li><li><b>hybrid:</b> This Contrexx installation shall act as the <i>Website Manager Server</i> as well as a <i>Website Service Server</i>.</li></ul>';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_DEFAULTCODEBASE']='Default CodeBase';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_DEFAULTCODEBASE_TOOLTIP']='Specify the path to the <b>CodeBase</b> that shall be used for new <i>Website Websites</i>.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MULTISITEDOMAIN']='Domain of MultiSite system';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MULTISITEDOMAIN_TOOLTIP']='Set the domain that shall be used by the MultiSite system. New Websites will be created as subdomains of the specified domain. I.e.: if this option is set to <b>example.com</b>,then a new Website, called <i>foo</i>, will be accessable through the subdomain <b>foo.example.com</b>.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MARKETINGWEBSITEDOMAIN']='Domain of Marketing Website';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MARKETINGWEBSITEDOMAIN_TOOLTIP']='Set the Domain where the <i>Marketing Website</i> of the MultiSite system is located. Invalid HTTP-Requests to this Contrexx installation will automatically be forwarded to the given Domain.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_DASHBOARDNEWSSRC']='URL to dashboard news RSS feed';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CUSTOMERPANELDOMAIN']='Domain of Customer Panel';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CUSTOMERPANELDOMAIN_TOOLTIP']='Set the Domain where the <i>Customer Pnael</i> of the MultiSite system is located.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_PLESKHOST']='Hostname of Plesk server';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_PLESKLOGIN']='Login to Plesk server';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_PLESKPASSWORD']=' Password to Plesk server';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_PLESKAPIVERSION']='Plesk Api Version';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_PLESKMASTERSUBSCRIPTIONID']='ID of master subscription';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_PLESKMASTERSUBSCRIPTIONID_TOOLTIP']='Specify the <b>ID</b> of the <i>Plesk Subscription</i> by which this Contrexx installation is managed by.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_REGION'] = 'Availability Zone';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CREDENTIALSKEY'] = 'Access Key ID';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CREDENTIALSSECRET'] = 'Secret Access Key';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_VERSION'] = 'AWS API Version';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_TIMETOLIVE'] = 'Resource Record Cache Time to Live(TTL) in Seconds';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_HOSTEDZONEID'] = 'Hosted Zone ID';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_OPTIONS_SET_BY_WEBSITE_MANAGER'] =' - Those options are set by the Website Manager';

$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITECONTROLLER']='Subscription controller';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_DEFAULTWEBSITESERVICESERVER']='Default Website Service Server';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_DEFAULTMAILSERVICESERVER']='Default Mail Service Server';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_DEFAULTPIMPRODUCT'] = 'Default Product on Sign-Up';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_AFFILIATESYSTEM'] = 'Affiliate System';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_AFFILIATEPAYOUTLIMIT'] = 'Payout limit';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_AFFILIATEPAYOUTLIMIT_TOOLTIP'] = 'The limit to reach before the payout of the affiliate credit can be requested.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_AFFILIATECOOKIELIFETIME']='Cookie Lifetime';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_AFFILIATECOOKIELIFETIME_TOOLTIP']='The lifetime of the affiliate tracking cookie (in days).';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_PLESKWEBSITESSUBSCRIPTIONID']='ID of website subscription';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_PLESKWEBSITESSUBSCRIPTIONID_TOOLTIP']='Specify the <b>ID</b> of the <i>Plesk Subscription</i> by which the databases of the <i>Website Websites</i> shall be managed by.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MANAGERHOSTNAME']='Website Manager hostname';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MANAGERHOSTNAME_TOOLTIP']='Specify the <b>hostname</b> of the <i>Website Manager Server</i> by which this <i>Website Service Server</i> is managed by. The hostname must be set to the <i>Main Domain</i> of the <i>Website Manager Server</i>.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MANAGERSECRETKEY']='Secret Key of Website Manager';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MANAGERINSTALLATIONID']='Installation-ID of Website Manager';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MANAGERHTTPAUTHMETHOD']='HTTP Authentication Method';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MANAGERHTTPAUTHUSERNAME']='HTTP Authentication Username';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MANAGERHTTPAUTHPASSWORD']='HTTP Authentication Password';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITEDATABASEHOST'] = 'Database host for websites';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITEDATABASEHOST_TOOLTIP'] = 'Set the hostname (or IP address) of the database server that shall be used for the databases of the Websites';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SERVICEHOSTNAME'] = 'Website Service hostname';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SERVICESECRETKEY'] = 'Secret Key of Website Service';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SERVICEINSTALLATIONID'] = 'Installation-ID of Website Service';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SERVICEHTTPAUTHMETHOD'] = $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MANAGERHTTPAUTHMETHOD'];
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SERVICEHTTPAUTHUSERNAME'] = $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MANAGERHTTPAUTHUSERNAME'];
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SERVICEHTTPAUTHPASSWORD'] = $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MANAGERHTTPAUTHPASSWORD'];
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITESTATE'] = 'Status';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITENAME']  = 'Website Name';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITEFTPUSER']  = 'Websites FTP user';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CODEBASEREPOSITORY'] = 'Repository for Contrexx Code Bases';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITEFTPPATH'] = 'Websites FTP path';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITEHTTPAUTHMETHOD'] = "HTTP Authentication Method";
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITEHTTPAUTHUSERNAME'] = "HTTP Authentication Username";
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITEHTTPAUTHPASSWORD'] = "HTTP Authentication Password";
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITEUSERID'] = "User ID of Website Owner";
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_TERMSURL'] = "URL to T&Cs"; 
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_TERMSURL_TOOLTIP'] = "The absolute URL (incl. protocol) to the webpage listing the terms & conditions";
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CREATEFTPACCOUNTONSETUP'] = "Create FTP account during website setup";
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_PASSWORDSETUPMETHOD'] = "Password set method";
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_DEFAULTWEBSITETEMPLATE'] = 'Default Website Template for Sign-Up';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_AUTOLOGIN'] = 'Auto Login after sign-up';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_AUTOLOGIN_TOOLTIP'] = 'Automatically log user in after successfull sign-up process and redirect user to backend of newly created website.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_FTPACCOUNTFIXPREFIX'] = 'FTP account name prefix';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_FTPACCOUNTFIXPREFIX_TOOLTIP'] = 'Define a prefix that shall be used for the FTP account name in case the Website name is not a valid FTP account name. I.e.: cx';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_FORCEFTPACCOUNTFIXPREFIX'] = 'Force FTP account name prefix';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_FORCEFTPACCOUNTFIXPREFIX_TOOLTIP'] = 'Prefix all FTP account user names with the prefix defined by option '.$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_FTPACCOUNTFIXPREFIX'];
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUPPORTFAQURL'] = 'URL to FAQ webpage';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUPPORTRECIPIENTMAILADDRESS'] = 'E-Mail recipient address for support requests';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MAXLENGTHFTPACCOUNTNAME'] = 'Max. length of FTP account name';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_PAYREXXACCOUNT'] = 'Payrexx Account';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_PAYREXXFORMID'] = 'Payrexx Form Id';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_PAYREXXAPISECRET'] = 'Payrexx API Secret';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITEBACKUPLOCATION'] = 'Website Backup Location';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITEBACKUPLIMIT']    = 'Website backup limit';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITEBACKUPLIMIT_TOOLTIP'] = 'Specify the website backup limit in Kb\'s. <br /> 0 - No limit';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_LIMIT_ERROR'] = 'The size of requested website is too high. So the website backup is not recommended.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_COPY_LIMIT_ERROR'] = 'This website can not be copied, please visit our <a href="http://support.cloudrexx.com" target="_blank">support page</a>';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_DOMAINBLACKLIST'] = 'Domain blacklist';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_AFFILIATEIDQUERYSTRINGKEY'] = 'Affiliate ID query string key';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_AFFILIATEIDQUERYSTRINGKEY_TOOLTIP'] = 'The query string key that shall be used to track the Affiliate ID in a request.
I.e.: http://www.example.com/?*ref*=&lt;affiliateId&gt;';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_AFFILIATEIDREFERENCEPROFILEATTRIBUTEID'] = 'Affiliate ID (reference) user profile attribute ID';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_AFFILIATEIDREFERENCEPROFILEATTRIBUTEID_TOOLTIP'] = 'ID of profile attribute to store the referenced Affiliate ID on user sign-up';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CONVERSIONTRACKING'] = 'Conversion Tracking';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CONVERSIONTRACKING_TOOLTIP'] = 'Activate to track sign-up conversions';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_TRACKGOOGLECONVERSION'] = 'Track Google Adwords Conversions';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_TRACKGOOGLECONVERSION_TOOLTIP'] = 'Activate to track Google Adwords Conversions for Sign-Ups';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_GOOGLECONVERSIONID'] = 'Google Adwords Conversion Id';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_TRACKFACEBOOKCONVERSION'] = 'Track Facebook Ads Conversions';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_TRACKFACEBOOKCONVERSION_TOOLTIP'] = 'Activate to track Facebook Ads Conversions for Sign-Ups';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_FACEBOOKCONVERSIONID'] = 'Facebook Ads Conversion Id';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_MODE'] = 'Website mode';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_SERVER'] = 'Website Repository';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_SHARED_FOLDER'] = 'Shared folder';

// settings status messages
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_DEFAULTCODEBASE_SUCCESSFUL_CREATION']      = "Default Code Base has been set successfully";
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_STATUS_CHANGED_SUCCESSFUL']      = "Website state has been set successfully";


// Here come the ACTs:
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ACT_DEFAULT'] = 'Websites';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ACT_STATISTICS'] = 'Statistics';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ACT_NOTIFICATIONS'] = 'Notifications';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ACT_SETTINGS'] = 'Settings';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ACT_SETTINGS_DEFAULT'] = 'Default';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ACT_SETTINGS_EMAIL'] = 'E-mails';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ACT_SETTINGS_WEBSITE_SERVICE_SERVERS'] = 'Website Service Servers';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ACT_SETTINGS_CODEBASES'] = 'Code Bases';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ACT_SETTINGS_WEBSITE_TEMPLATES'] = 'Website Templates';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ACT_SETTINGS_MAIL_SERVICE_SERVERS'] = 'Mail Service Servers ';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ACT_MAINTENANCE'] = 'Maintenance';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ACT_MAINTENANCE_DEFAULT']= 'Domains';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ACT_MAINTENANCE_FTP']= 'FTP';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ACT_AFFILIATE']= 'Affiliate';

// Sign up interface
$_ARRAYLANG['TXT_MULTISITE_TITLE']='Get started with your own website now';
$_ARRAYLANG['TXT_MULTISITE_CLOSE']='Close';
$_ARRAYLANG['TXT_MULTISITE_EMAIL_ADDRESS']='Email Address';
$_ARRAYLANG['TXT_MULTISITE_SITE_ADDRESS']='Site Address';
$_ARRAYLANG['TXT_MULTISITE_SITE_ADDRESS_SCHEME']='The name must contain only lowercase letters (a-z) and numbers and must be at least %1$s characters, but no longer than %2$s characters.';
$_ARRAYLANG['TXT_MULTISITE_SUBMIT_BUTTON']='Create Site';
$_ARRAYLANG['TXT_MULTISITE_ACCEPT_TERMS']='I confirm that I have read, understand and agree to the %s.';
$_ARRAYLANG['TXT_MULTISITE_ACCEPT_TERMS_URL_NAME']='Terms & Conditions';
$_ARRAYLANG['TXT_MULTISITE_EMAIL_INFO']='We\'ll send you an email to this address to activate your account.';
$_ARRAYLANG['TXT_MULTISITE_ORDER_BUTTON']='Order now';

// Sign up status messages
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_ALREADY_EXISTS'] = 'The name %s has been taken already. Please choose an other name for your own site.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_NAME_TOO_SHORT'] = 'Site name must be at least %s characters.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_NAME_TOO_LONG'] = 'Site name can be no longer than %s characters.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_NAME_WRONG_CHARS'] = 'The name must contain only lowercase letters (a-z) and numbers.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_EMAIL_IN_USE'] = 'That email is already registered - %s.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_INVALID_EMAIL'] = 'The entered email address is invalid.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_LOGIN'] = 'Log in';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_CREATED'] = 'Congratulations! Your website %s is ready! Please check your inbox (ended up in spam folder?). We sent an email to you with your credentials and first hints. Good luck.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_CREATION_ERROR'] = 'We were unable to setup your website. A technician will immediately address the issue and will inform you on the email address %s as soon as your website is ready for use.';
$_ARRAYLANG['TXT_MULTISITE_BUILD_WEBSITE_TITLE'] = 'Building your Website..';
$_ARRAYLANG['TXT_MULTISITE_BUILD_SUCCESSFUL_TITLE'] = 'Congratulations!';
$_ARRAYLANG['TXT_MULTISITE_BUILD_ERROR_TITLE'] = 'Oops..';
$_ARRAYLANG['TXT_MULTISITE_BUILD_WEBSITE_MSG'] = 'This might take a few minutes. You will be informed on the supplied email address once your website is online and ready to use.';
$_ARRAYLANG['TXT_MULTISITE_REDIRECT_MSG'] = 'Your website is ready. You are being redirected to the administration interface now...';
// TODO add english translation
$_ARRAYLANG['TXT_MULTISITE_BUILD_WEBSITE_MSG_AUTO_LOGIN'] = 'Vielen Dank für Ihre Anmeldung bei Cloudrexx! Bitte haben Sie etwas Geduld. Es kann bis zu 2 Minuten dauern, bis Ihre Website einsatzbereit ist. Sobald Ihre Website einsatzbereit ist, werden Sie in den Administrationsbereich weitergeleitet. Sie sind automatisch eingeloggt. Das Kennwort wird an %1$s gesendet. Ihre Cloudrexx-Domain lautet %2$s.';
$_ARRAYLANG['TXT_MULTISITE_BUILD_ERROR_MSG'] = 'Unfortunately, the build process of your website failed. If this did happen before, please contact our customer service under %s.';

//Reset FTP password status message
$_ARRAYLANG['TXT_MULTISITE_RESET_FTP_PASS_ERROR_MSG'] = 'Resetting your FTP password failed! Try again.';
$_ARRAYLANG['TXT_MULTISITE_RESET_FTP_PASS_MSG'] = 'Your FTP password has been reset successfully.';

//To execute sql query
$_ARRAYLANG['TXT_MULTISITE_EXECUTE_QUERY_ON_WEBSITE'] = 'Execute SQL query on Website ';
$_ARRAYLANG['TXT_MULTISITE_EXECUTE_QUERY_ON_ALL_WEBSITES_OF_SERVICE_SERVER'] = 'Execute SQL query on all websites running on service server ';
$_ARRAYLANG['TXT_MULTISITE_EXECUTED_QUERY_COMPLETED'] = 'SqlQuery Execution Completed!';
$_ARRAYLANG['TXT_MULTISITE_EXECUTED_QUERY_FAILED'] = 'SqlQuery Executed failed!';
$_ARRAYLANG['TXT_MULTISITE_QUERY_EXECUTED_ON_WEBSITE'] = 'SqlQuery executed on website : ';
$_ARRAYLANG['TXT_MULTISITE_SQL_QUERY'] = 'SqlQuery';
$_ARRAYLANG['TXT_MULTISITE_SQL_STATUS'] = 'SqlStatus';
$_ARRAYLANG['TXT_MULTISITE_PLEASE_INSERT_QUERY'] = 'Please insert a query!';
$_ARRAYLANG['TXT_MULTISITE_SQL_QUERY_EXECUTED_SUCCESSFULLY'] = 'Query executed successfully.';
$_ARRAYLANG['TXT_MULTISITE_SQL_QUERY_EXECUTED_FAILED'] = 'Query execution failed.';
$_ARRAYLANG['TXT_MULTISITE_FETCH_LICENSE_INFO'] = 'Fetch license information of website:';
$_ARRAYLANG['TXT_MULTISITE_LICENSE_DATA_TITLE'] = 'License data of website ';
$_ARRAYLANG['TXT_MULTISITE_SHOW_LICENSE'] = 'Show License';
$_ARRAYLANG['TXT_MULTISITE_LICENSE_INFO'] = 'License information for the selected website';
$_ARRAYLANG['TXT_MULTISITE_QUERY_IS_EMPTY'] = 'JsonMultiSite : sql query is empty..';
$_ARRAYLANG['TXT_MULTISITE_NO_RECORD_FOUND'] = 'No record found.';

//filter
$_ARRAYLANG['TXT_MULTISITE_SEARCH'] = 'Search';
$_ARRAYLANG['TXT_MULTISITE_FILTER'] = 'Filter';
$_ARRAYLANG['TXT_MULTISITE_ENTER_SEARCH_TERM'] = 'Enter your search term';
$_ARRAYLANG['TXT_MULTISITE_NO_ROWS_AFFECTED'] = " row(s) %s successfully.";

//Multisite website status message
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_CONFIG_TITLE']             = "Multisite configuration data of a website ";
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_CONFIG_FETCH_SUCCESSFUL']  = "Multisite configuration Of the website:%s !";
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_CONFIG_FETCH_FAILED']      = "Failed to %s the multisite configuration of the website: %s !";
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_CONFIG_UPDATE_SUCCESSFUL'] = "Successfully updated the multisite configuration option:";
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_CONFIG_UPDATE_FAILED']     = "Failed to update the multisite configuration option:";
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADD_CONFIG_TITLE'] = 'Add new multisite configuration option: ';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADD_CONFIG'] = 'Add new configuration';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADD_CONFIG_SUCCESSFUL'] = 'Successfully added the multisite configuration option: ';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADD_CONFIG_FAILED'] = 'Failed to add configuration Option due to empty option values!';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADD_CONFIG_OPTION_TOOLTIP'] = 'Option Values must be comma seperated values. <br /> Each option mustbe optionValue:optionName.<br/> For Example Activated => on:Activated';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_DELETE_CONFIG_OPTION'] = 'Do you really want to delete this configuration?';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_CONFIG_DELETE_SUCCESSFUL'] = 'Successfully deleted the multisite configuration option: ';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_CONFIG_DELETE_FAILED'] = 'Failed to delete the multisite configuration option: ';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_CONFIG_ALERT_MESSAGE'] = 'Making a change on the website configuration might break this selected website! You must not alter the configuration unless you know exactly what you are doing!';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADD_CONFIG_EXISTS'] = 'Failed to add configuration : %s .Option already exists!';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_FETCH_LICENSE_FAILED'] = 'Failed to get license information of website: %s !';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_LICENSE_UPDATE_SUCCESS'] = 'The license option  "%s"  was successfully updated!';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_LICENSE_UPDATE_FAILED'] = 'Failed to update The license Option "%s"  !';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADD_WEBSITE_SUCCESS'] = "The Website has been successfully added.";
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADD_WEBSITE_FAILED'] = "Failed to adding a new website.";

//cron mail error message
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CRON_MAIL_CRITERIA_EMPTY'] = "Please provide some conditions to setup the cron mail.";

//website template message
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SETTINGS_WEBSITE_TEMPLATE_HEADER_MSG'] = ' - This section is managed by the Website Manager';

$_ARRAYLANG['TXT_MULTISITE_WEBSITE_BUTTON_VIEW'] = 'View';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_BUTTON_ADMIN_CONSOLE'] = 'admin console';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_BUTTON_DEACTIVATE'] = 'Deactivate';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BUTTON_ACTIVATE'] = 'Activate';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_BUTTON_DELETE'] = 'Delete';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_TITLE_ACCOUNT'] = 'Account';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_TITLE_NAME'] = 'Name';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_TITLE_EMAIL'] = 'E-Mail';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_TITLE_FUNCTIONS'] = 'Functions';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_ADD_ACCOUNT'] = 'Add Account';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_ADD_DOMAIN'] = 'Add domain';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_TITLE_USED_LIMIT'] = 'Used / Limit';
$_ARRAYLANG['TXT_MULTISITE_UNLIMITED'] = 'Unlimited';

//website subscription detail
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_SUBSCRIPTION'] = 'Subscription';

//website subscription detail template message
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_LOGIN_NOACCESS'] = 'Access denied';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_SUBSCRIPTIONID_EMPTY'] = 'Unknown subscription requested.';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_SUBSCRIPTION_NOT_EXISTS'] = 'Unknown subscription requested.';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_PRODUCT_NOT_EXISTS'] = 'Unknown product requested.';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_ORDER_NOT_EXISTS'] = 'Unknown order requested.';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_EXISTS'] = 'Unknown website requested.';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_MULTISITE_USER'] = 'Access denied';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_WEBSITES'] = 'Websites';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_SUBSCRIPTION_PAYMENTS'] = 'Payments';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_PAYREXX_LOGIN_FAILED'] = 'The payment management console is currently not available.';

$_ARRAYLANG['TXT_MULTISITE_NO_WEBSITE_FOUND'] = 'Websites not available';
$_ARRAYLANG['TXT_MULTISITE_NOT_VALID_USER'] = 'Not a multisite user';
$_ARRAYLANG['TXT_MULTISITE_UNKOWN_WEBSITE'] = 'Unknown website requested.';

$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MAXIMUM_QUOTA_REACHED'] = 'You have reached the maximum of %s entities that are included in your current plan.';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_TEMPLATE_FAILED'] = 'Failed to load a website template.';

$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_UPGRADE_PAYMENT_FAILED']  = 'The upgrade of the subscription is getting canceled.';
$_ARRAYLANG['TXT_MULTISITE_SUBSCRIPTION_INVALIDPARAMETERS']  = 'Invalid parameters.please try after sometime.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ORDER_FAILED']  = 'Unable to create the order.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_UPGRADE_SUCCESS']  = 'The upgrade of your subscription was successful.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_UPGRADE_FAILED']  = 'The upgrade of the subscription failed.';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_PRODUCTS_NOT_FOUND']  = 'There are no upgrades available for the selected subscription.';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_INITIALIZING'] = 'Initializing...';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_UPGRADE_INPROGRESS'] = 'Your subscription is being upgraded...';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_ADD_INPROGRESS'] = 'Your subscription is being added...';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_ADD_SUCCESS']  = 'The subscription was successfully added.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_ADD_FAILED']  = 'Failed to add the new subscription.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_ADD_PAYMENT_FAILED']  = 'Adding the new subscription is getting canceled.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_BILLABLE_SUBSCRIPTION']  = 'You are about to place the following order:';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_NEW_ORDER']  = 'New order';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_ORDER']  = 'Order';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_PRICE']  = 'Price';

//website email service
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_ACTIVATE_MAIL_SERVICE'] = 'Activate mail service';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_DEACTIVATE_MAIL_SERVICE'] = 'Deactivate mail service';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_MAIL_ENABLED_SUCCESSFULLY'] = 'Mail service successfully activated.';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_MAIL_DISABLED_SUCCESSFULLY'] = 'Mail service successfully deactivated.';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_MAIL_ENABLED_FAILED'] = 'Mail service activation failed.';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_MAIL_DISABLED_FAILED'] = 'Mail service deactivation failed.';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_OPEN_ADMINISTRATION'] = 'Open administration';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_LOGIN_PLESK_FAILED'] = 'The email management console is currently not available.';
$_ARRAYLANG['TXT_MULTISITE_UNKOWN_MAIL_SERVICE_SERVER'] = 'Email service unavailable';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_CREATE_MAIL_ACCOUNT_FAILED'] = 'Failed to create an email account.';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_DELETE_MAIL_ACCOUNT_FAILED'] = 'Failed to delete the email account.';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_CREATED_MAIL_ACCOUNT_SUCCESSFULLY'] = 'Email account successfully created.';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_DELETED_MAIL_ACCOUNT_SUCCESSFULLY'] = 'Email account successfully removed.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CANCEL'] = 'Cancel';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CLOSE'] = 'Close';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_MAIL_SERVICE_ENABLED'] = 'Mail service is active';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_MAIL_SERVICE_DISABLED'] = 'No mail service';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_EMAIL_INFO_ACCESS_DATA'] = "Credentials";
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_EMAIL_INFO_EMAIL_CONFIG'] = "E-Mail Configuration";
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_EMAIL_INFO_MAIN_MAIL_ACCOUNT'] = "Main-E-Mail-Account";
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_EMAIL_INFO_MAIN_MORE_MAIL_ACCOUNTS'] = "More E-Mail Accounts";
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_EMAIL_INFO_MAIN_OPEN_MANAGER'] = "Open administration";
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_EMAIL_INFO_WEBMAIL'] = "Webmail";
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_EMAIL_INFO_MAIL_SETUP'] = "E-Mail Client Setup";
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_EMAIL_INFO_HOST'] = "Host";
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_EMAIL_SECURE_CONNECTION'] = "Secure connection";
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_EMAIL_INFO_MAIL_SETUP_INFO'] = "You can find instructions to set up your mail client in our <a target='_blank' href=\"https://support.cloudrexx.com/support/solutions/folders/13000001604\">helpdesk</a>";
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_EMAIL_INFO_MAIN_WEBMAIL_HELP'] = "The webmail is accessable over the following url from everywhere with internet access with a modern browser:";
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_EMAIL_INFO_MAIN_USERNAME_HELP'] = "Use the whole E-mail address as the username for the mail account.
You can manage passwords and the other accounts in the administration.";
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_EMAIL_INFO_OUTPUT_SERVER'] = "Outbox server (SMTP)";
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_EMAIL_INFO_INPUT_SERVER'] = "Inbox server (POP3/IMAP)";
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_EMAIL_INFO_OUTPUT_SERVER_INFO'] = "Choose the desired configuration to show the needed data to configure your E-mail client.";



//website domain
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADD'] = 'Add';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SAVE'] = 'Save';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_DELETE'] = 'Delete';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_ADD_SUCCESS_MSG'] = 'Domain successfully assigned to website.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_EDIT_SUCCESS_MSG'] = 'Domain successfully renamed.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_DELETE_SUCCESS_MSG'] = 'Domain successfully removed from website.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_ADD_FAILED'] = 'Failed to assign the domain.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_EDIT_FAILED'] = 'Failed to update the domain.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_DELETE_FAILED'] = 'Failed to remove the domain.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_UNKNOWN'] = 'Invalid request.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_SPF'] = 'Anti-spoofing measurements';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_SPF_INFO'] = 'We recommend that you create a <a href="http://www.openspf.org/" target="_blank">Sender Policy Framework</a> (SPF) record for the domain %s. An SPF record is a type of Domain Name Service (DNS) record that identifies which mail servers are permitted to send email on behalf of your domain.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_SPF_SEE'] = 'For further information see';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_SPF_MORE_INFORMATION'] = 'FAQ article regarding SPF';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_SPF_INFO_RECORD'] = 'SPF record for %s:';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_SSL_SUCCESS_MSG'] = 'Successfully installed SSL certificate.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_SSL_FAILED'] = 'SSL certificate installation failed.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_DOMAIN_SSL_CERTIFICATE'] = 'Secure with SSL certificate';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CERTIFICATE_NAME'] = 'Certificate name';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_PRIVATE_KEY'] = 'Private key';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CERTIFICATE'] = 'Certificate';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CA_CERTIFICATE'] = 'CA certificate';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SECURE_DOMAIN_NEW_CERTIFICATE'] = 'Secure domain with new certificate';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_DOMAIN_CERTIFICATE'] = 'This domain is secured using the certificate <strong> %s </strong>.';
//website email
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_EMAIL_EDIT'] = 'E-Mail information';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_EMAIL_INCOMING_MAIL_SERVER'] = 'Incoming mail server';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_EMAIL_OUTGOING_MAIL_SERVER'] = 'Outgoing mail server';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_EMAIL_SMTP'] = 'SMTP';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_EMAIL_POP3'] = 'POP3';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_EMAIL_IMAP'] = 'IMAP';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_EMAIL_WEBMAIL'] = 'Webmail';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_EMAIL_SSL'] = 'SSL';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_EMAIL_USERNAME'] = 'Username';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_EMAIL_PASSWORD'] = 'Password';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_EMAIL_PORT'] = 'Port';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_EMAIL_RESET_PASSWORD'] = 'Reset password';
$_ARRAYLANG['TXT_MULTISITE_RESET_EMAIL_PASS_ERROR_MSG'] = 'Resetting your e-mail password failed Please try again.';
$_ARRAYLANG['TXT_MULTISITE_RESET_EMAIL_PASS_MSG'] = 'Your e-mail password has been successfully resetted.';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_SHOW_EMAIL'] = 'Show e-mail account';

//website admin user
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADMIN_USER_DELETE_SUCCESS']  = 'Successfully deleted the selected administrator account.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADMIN_USER_DELETE_FAILED']   = 'Failed to delete the selected administrator account.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADMIN_USER_ADD_SUCCESS']     = 'The new administrator account has been added successfully.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADMIN_USER_ADD_FAILED']      = 'The creation of the new administrator account failed.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_REMOTE_LOGIN_SUCCESS']       = 'Successfully login to website: %s! <br />The Adminpanel opens in a new window. In case no new window has opened, please click <a href="https://support.cloudrexx.com/support/solutions/articles/5000624941" title="Tutorial" target="_blank"><strong>here</strong></a> for help.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADMIN_USER_EDIT_SUCCESS']    = 'The new administrator account has been edited successfully.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADMIN_USER_EDIT_FAILED']     = 'The editing of the new administrator account failed.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_UNKOWN_USER_REQUEST']        = 'Invalid request.';

$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_EXTERNALPAYMENTCUSTOMERIDPROFILEATTRIBUTEID'] = 'Payment user profile attribute ID';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_EXTERNALPAYMENTCUSTOMERIDPROFILEATTRIBUTEID_TOOLTIP']='The user profile attribute Id used to store the customer ID of the external payment provider.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_AFFILIATEIDPROFILEATTRIBUTEID'] = 'Affiliate ID user profile attribute ID';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_AFFILIATEIDPROFILEATTRIBUTEID_TOOLTIP']='The user profile attribute Id used to store the Affiliate ID.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_CANCEL_LABEL'] = 'Cancel subscription';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_BUTTON_CANCEL'] = 'Cancel';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_CANCEL_CONTENT'] = 'You are about to cancel this subscription. The subscription will be terminated by the %s. After this date, all websites associated to this subscription will no longer be accessible.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_BUTTON_CANCEL'] = 'Cancel subscription';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_CANCELLED_SUCCESS_MSG'] = 'The subscription has been cancelled.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DELETE_TITLE'] = 'Delete website';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DELETE_CONFIRM_ALERT'] = 'Please confirm to delete the website. This operation can\'t be undone. All data of the website will be erased permanently.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DELETE_LOADING_ANIMATION'] = 'The website is being removed...';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DELETE_STATUS_SUCCESS_MSG'] = 'The Website has been deleted successfully.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DELETE_STATUS_ERROR_MSG'] = 'Failed to remove the website.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_MAIN_DOMAIN'] = 'Main domain';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_SELECT_MAIN_DOMAIN'] = 'Select main domain';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_MAIN_DOMAIN_CONTENT'] = 'Please confirm to select the domain <span id="domainName"></span> as the main domain of this website. This will then be the primary address of this website.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SELECT'] = 'Select';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_STATUS_DEACTIVATED_SUCCESSFUL'] = 'Website was successfully deactivated.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_STATUS_ACTIVATED_SUCCESSFUL'] = 'Website was successfully activated.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_STATUS_DEACTIVATED_FAILED'] = 'Failed to deactivate the Website.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_STATUS_ACTIVATED_FAILED'] = 'Failed to activate the Website.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_STATUS_FAILED'] = 'Failed to update the website status.';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_USER_ACCOUNT_DELETE_FAILED'] = 'Failed to delete your account.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_DESCRIPTION'] = 'Description';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_DESCRIPTION_TITLE'] = 'Subscription description';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_DESCRIPTION_CONTENT'] = 'Set a description for this subscription:';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_DESCRIPTION_SUCCESS_MSG'] = 'The description has been set successfully.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_SELECT_SUCCESS_MSG'] = 'The domain %s has successfully been set as new main domain.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_SELECT_FAILED'] = 'Failed to change the main domain of the website.';
//website informations
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_MANAGEMENT'] = 'Management';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_LOADING_TEXT'] = 'Loading...';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAINS'] = 'Domains';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_EMAIL'] = 'E-Mail';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_OFFLINE'] = 'offline';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_DISABLED'] = 'disabled';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_ADMINISTRATORS'] = 'Administrators';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_RESOURCES'] = 'Resources';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_EMAIL'] = 'E-Mail';

//Overview
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_OVERVIEW']       = 'Go to overview...';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_ADD_NEW_BUTTON'] = 'Add new';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_BUTTON_DETAIL']              = 'Manage';

//Error messages
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MAXIMUM_CUSTOMERS_REACHED'] = 'You have reached the maximum number(%s) of customers.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MAXIMUM_FORMS_REACHED'] = 'You have reached the maximum number(%s) of forms.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MAXIMUM_PRODUCTS_REACHED'] = 'You have reached the maximum number(%s ) of products in the store.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MAXIMUM_ADMINS_REACHED'] = 'You have reached the maximum number(%s) of administrators.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_GO_TO_OVERVIEW'] = 'Go to the overview page';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_OWNER_EMAIL_UNIQUE_ERROR'] = 'The e-mail %1$s is already in use by a user account on %2$s and can therefore not be used.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MAPPING_DOMAIN_FAILED'] = 'Mapping your website to the domain %s is not allowed.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_USER_PROFILE_UPDATED_SUCCESS']= 'Your profile has been updated successfully.';
$_ARRAYLANG['TXT_MULTISITE_INVALID_CALL'] = 'Invalid request';
$_ARRAYLANG['TXT_MULTISITE_UNKNOWN_WEBSITE'] = 'Unkown website';

//mail service
$_ARRAYLANG['TXT_MULTISITE_NO_MAIL_SERVER_FOUND'] = 'Mail service server is not available';
$_ARRAYLANG['TXT_MULTISITE_MAIL_SERVICE_PLAN_INFO'] = 'Available plan on the server: ';
$_ARRAYLANG['TXT_MULTISITE_FAILED_TO_FETCH_MAIL_SERVICE_PLAN'] = 'Unable to fetch server data.';
$_ARRAYLANG['TXT_MULTISITE_MAIL_SERVICE_PLAN_EMPTY'] = 'This mail service server does not contain any plans.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_ENDED_DATE'] = "Ended";
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_UPDATE_SUBSCRIPTION_DOWNGRADE_ERROR'] = 'Downgrade from %1$s to %2$s is not possible';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_USER_CRM_ACCOUNT'] = 'This user is a crm contact, click to open the crm contact.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MODIY_USER_ACCOUNT'] = 'Edit user account';

//Remote Login Customer Panel
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_REMOTE_LOGIN_CUSTOMERPANELDOMAIN_TITLE'] = 'Remote login to customer panel';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_REMOTE_LOGIN_TITLE'] = 'Remote login to %s';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_REMOTE_LOGIN_FAILED'] = 'Remote login to %s failed!';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_REMOTE_LOGIN_CUSTOMERPANELDOMAIN_SUCCESS'] = 'Successfully login to customer panel!';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_EDIT_LINK']   = 'Click to open the website edit page.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DETAIL_LINK'] = 'Click to open the website detail page.';

// Maintenence > FTP
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_FTPUSER'] = 'FTP username';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_FTPPATH'] = 'FTP path';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_SERVICE_SERVER'] = 'Website service server';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ALL'] ='All';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ERROR'] ='Error';

// Maintaine AffiliateId
$_ARRAYLANG['TXT_MULTISITE_AFFILIATE_ID_NOT_AVAILABLE'] = "This Affiliate-ID can't be used. Please set another one.";
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_AFFILIATE_ID_TOO_SHORT'] = 'Affiliate-ID must be at least %s characters.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_AFFILIATE_ID_TOO_LONG'] = 'Affiliate-ID can be no longer than %s characters.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_AFFILIATE_ID_WRONG_CHARS'] = 'The Affiliate-ID must contain only lowercase letters (a-z) and numbers.';
$_ARRAYLANG['TXT_MULTISITE_AFFILIATE_ID_BLOCKED'] = "This Affiliate-ID can't be used. Please set another one.";
$_ARRAYLANG['TXT_MULTISITE_AFFILIATE_ID_ERROR'] = 'Unable to set the Affiliate-ID';
$_ARRAYLANG['TXT_MULTISITE_AFFILIATE_ID_SET_ALREADY'] = 'Unable to change the Affiliate-ID';
$_ARRAYLANG['TXT_MULTISITE_AFFILIATE_ID_SAVED'] = 'Your Affiliate-ID has been set.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_AFFILIATEIDS'] = 'Affiliate IDs';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SETTINGS'] = 'Settings';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_AFFILIATEID'] = 'Affiliate ID';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_USER'] = 'User';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CONTACT'] = 'Contact';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_PAYPAL_EMAIL'] = 'PayPal email';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_REFERRALS'] = 'Referrals';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_PAYPALPROFILEATTRIBUTEID'] = 'PayPal email user profile attribute ID';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_PAYPALPROFILEATTRIBUTEID_TOOLTIP']='ID of profile attribute to store the PayPal email address.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_PAYOUT_REQUEST_ERROR'] = 'Unable to send payout-request.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_PAYOUT_REQUEST_SUCCESS'] = 'Payout-request has been sent successfully.';

//Website Backup and Restore
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_TITLE'] = 'Create a backup of %s ';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_CONFIRM'] = 'Please confirm to proceed with website backup.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_INPROGRESS'] = 'Website backup is in progress...';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_SERVICE_SERVER'] = 'Unknown service server requested.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_NOT_EXISTS'] = 'The website %s does not exists on the service server.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_SUCCESS'] = 'The website was backup-ed successfully.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_FAILED'] = 'Failed to backup the website repository.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_IN_SERVICE_TITLE'] = 'Create a backup for all websites under the %s Service Server.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_PARAMS'] = 'Invalid parameters. Please try again after sometime.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ACT_MAINTENANCE_BACKUPSANDRESTORE'] = 'Backups & Restore';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_RESTORE'] = 'Restore the website';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_RESTORE_INPROGRESS'] = 'Website restore is in Progress...';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_ALREADY_EXISTS'] = 'The given website name(%s) is already in use.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_RESTORE_FAILED'] = 'Failed to restore the website for the given website name.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_FIELD_REQUIRED'] = 'Please fill all the mandatory fields.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_RESTORE_CONFIRM'] = 'Please confirm to proceed website Restore process.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_RESTORE_SUCCESS'] = 'Successfully restored the backup website with the name %s.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_UPLOAD_BUTTON'] = 'Upload File';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CHOOSE_SERVICE_SERVER'] = 'Choose the service server';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ENTER_WEBSITE_NAME'] = 'Enter the Website name';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DATE_AND_TIME'] = 'Date and Time';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_RESTORE_BUTTON'] = 'Restore';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_SHOWALL_BACKUPS_BUTTON'] = 'Show All Backups';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_USER_ALREADY_EXISTS'] = 'This user(%s) already exists, proceed?';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CHOOSE_USER'] = 'Choose User';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CREATE_BACKUP_USER'] = 'Create a user from backup';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CHOOSE_ANOTHER_USER'] = 'Choose another user';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CHOOSE_SUBSCRIPTION'] = 'Choose a subscription';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CREATE_NEW_SUBSCRIPTION'] = 'Create New';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_USE_EXISTING_SUBSCRIPTION'] = 'Use existing';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_NAME_ERROR'] = 'In the domain name, only lowercase letters and numbers may occur.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CHOOSE_USER_ERROR'] = 'Please select the user from user live search.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_DOWNLOAD_TITLE'] = 'Download';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_DOWNLOAD_FAILED'] = 'Failed to download the  <strong> %s </strong>  file.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_CANCEL'] = 'Cancel';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_OK'] = 'Ok';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SERVICE_SERVER'] = 'Service server';

//delete the backuped website
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_DELETE'] = 'Delete the website backup';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_DELETE_CONFIRM'] = 'Please confirm to delete this website backup.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_DELETE_INPROGRESS'] = 'Delete of backup is in progress...';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_DELETE_FAILED'] = 'Failed to delete the website backup.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_DELETE_SUCCESS'] = 'Successfully deleted the website backup.';

// Notification section
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ACT_NOTIFICATIONS_DEFAULT'] = 'Overview';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ACT_NOTIFICATIONS_EMAILS'] = 'E-mails';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_NOTIFICATIONS_SENT'] = 'Sent';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_NOTIFICATIONS_EMAIL_EDIT'] = 'Click to open the email edit page.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE'] = 'Website';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_NOTIFICATION_LOGS'] = 'Notification logs';

//update
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_LOADING_SERVICE_SERVER_INFO'] = 'Fetching the Codebases from the service server';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_TRIGGERING_WEBSITE_UPDATE'] = 'Triggering the website update process';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SERVICE_SERVER_CODEBASE_REQUEST_SUCCESS'] = 'Codebases are successfully fetched from the service server';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_TRIGGERING_WEBSITE_UPDATE_SUCCESS_MSG'] = 'Website Update process triggered successfully';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CODEBASE_VERSION'] = 'CodeBase Version :';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_UPDATE_NOT_AVAILABLE'] = 'Update not available';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CODEBASE_NOT_EXIST'] = 'Codebase does not exists';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_UPDATE_PROCESS_ERROR_MSG'] = 'Unable to trigger the website update process';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_GET_CODEBASES_ERROR_MSG'] = 'Unable to get the codebases';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_GET_WEBSITES_ERROR_MSG'] = 'Unable to get the websites';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_GET_WEBSITES_SUCCESS_MSG'] = 'Websites are successfully fetched from the service server';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_UPDATE_ERROR_MSG'] = 'Failed to update the website';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_UPDATE_STATUS_ERROR_MSG'] = 'Failed to send the update notification email';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_UPDATE_CODEBASE_ERROR_MSG'] = 'Failed to update the website codebase';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SET_WEBSITE_DETAILS_ERROR'] = 'Failed to set the website details';
$_ARRAYLANG['TXT_MULTISITE_SETTINGS_UPDATE_WEBSITE_SERVER_AND_MODE_ERROR_MSG'] = 'Could not update the website repository and website mode.';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_PRODUCT'] = 'Product';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_COPY'] = 'Copy';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_COPY_WEBSITE'] = 'Copy website - %s';
$_ARRAYLANG['TXT_MULTISITE_COPY_NAME'] = 'Name of website copy:';
$_ARRAYLANG['TXT_MULTISITE_COPY_SUBSCRIPTION'] = 'Select a subscription for the new website:';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_CHOOSE_SUBSCRIPTION'] = 'Choose subscription';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_CREATE_TRIAL'] = 'Initialize as trial website';
$_ARRAYLANG['TXT_MULTISITE_COPY_INFO'] = 'You are about to create a copy of the website %s';
$_ARRAYLANG['TXT_MULTISITE_COPY_SUBSCRIPTION_NAME'] = 'Assign to subscription #%s (%s)';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_COPY_ERROR_MSG'] = 'Failed to copy the website';
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_COPY_SUCCESS'] = 'Website successfully copied.';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_COPY_PROGRESS'] = 'Website copy under progress. Please wait!';
$_ARRAYLANG['TXT_MULTISITE_NEW_SUBSCRIPTION'] = 'New subscription';
$_ARRAYLANG['TXT_MULTISITE_WEBSITE_COPY_DESCRIPTION'] = 'The \'copy\' process, copies the selected website data, users and component settings to new website.
Please enter the new \'Website name\' and choose the \'Subscription\' to continue.
If you want to buy a new subscription for the website use \'New subscription\' button.';

//set owner user
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CHANGE_OWNER_USER_ERROR'] = 'Error while switching the owner user';

//get website list
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_GET_WEBSITE_LIST_ERROR']= 'Failed to get the website list.';

//set the website details
$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SET_WEBSITE_DETAILS_ERROR']= 'Failed to set the website details.';

$_ARRAYLANG['TXT_CORE_MODULES_MULTISITE_SEARCH_WEBSITE_OR_DOMAIN'] = 'Website / Domain';
$_ARRAYLANG['TXT_CORE_MODULES_MULTISITE_SEARCH_BUTTON'] = 'Search';
