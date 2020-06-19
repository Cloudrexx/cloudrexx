<?php

/**
 * Class ComponentController
 *
 * 
 * @copyright   CONTREXX CMS - Comvation AG Thun
 * @author      Ueli Kramer <ueli.kramer@comvation.com>
 * @author      Sudhir Parmar <sudhirparmar@cdnsol.com>
 * @package     contrexx
 * @subpackage  coremodule_multisite
 * @version     1.0.0
 */

namespace Cx\Core_Modules\MultiSite\Controller;

/**
 * Class MultisiteException
 */
class MultiSiteException extends \Exception {}

/**
 * Class ComponentController
 *
 * The main Multisite component
 *
 * @copyright   CONTREXX CMS - Comvation AG Thun
 * @author      Ueli Kramer <ueli.kramer@comvation.com>
 * @author      Sudhir Parmar <sudhirparmar@cdnsol.com>
 * @package     contrexx
 * @subpackage  coremodule_multisite
 * @version     1.0.0
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {
   // const MAX_WEBSITE_NAME_LENGTH = 18; 
    const MODE_NONE = 'none';
    const MODE_MANAGER = 'manager';
    const MODE_SERVICE = 'service';
    const MODE_HYBRID = 'hybrid';
    const MODE_WEBSITE = 'website';
    const WEBSITE_MODE_STANDALONE = 'standalone';
    const WEBSITE_MODE_SERVER = 'server';
    const WEBSITE_MODE_CLIENT = 'client';
    
    /**
     * Main Domain
     *
     * Main Domain of the contrexx installation mentioned in global configuration
     * 
     * @static string
     */
    static $cxMainDomain;
    protected static $webDistributionController = null;
    
    protected $messages = '';
    protected $reminders = array(3, 14);
    protected $db;
    /*
     * Constructor
     */
    public function __construct(\Cx\Core\Core\Model\Entity\SystemComponent $systemComponent, \Cx\Core\Core\Controller\Cx $cx) {
        parent::__construct($systemComponent, $cx);
        //multisite configuration setting
        self::errorHandler();
        
        // add marketing website as valid redirection after logout
        \FWUser::$allowedHosts[] = 'http://'.\Cx\Core\Setting\Controller\Setting::getValue('marketingWebsiteDomain','MultiSite');
        \FWUser::$allowedHosts[] = 'https://'.\Cx\Core\Setting\Controller\Setting::getValue('marketingWebsiteDomain','MultiSite');
    }
    
    /**
     * Get the controller classes
     * 
     * @return array array of the controller classes.
     */
    public function getControllerClasses()
    {
        return array('Backend', 'Frontend', 'Cron', 'JsonMultiSite');
    }
    
    public function getControllersAccessableByJson() {
        return array('JsonMultiSiteController');
    }

    public function getCommandsForCommandMode() {
        return array('MultiSite');
    }

    public function getCommandDescription($command, $short = false) {
        if ($command != 'MultiSite') {
            return '';
        }
        if ($short) {
            return 'Allows the management of the MultiSite manager/service server';
        }
        return "MultiSite <section>
\tLoad MultiSite GUI forms of <section> (Signup / Login / Subscription / etc.)

MultiSite pass <website> [sudo] <command> [<arguments>]
\tExecute <command> on website <website> 
\tIf sudo is specified, then the command <command> will be executed even if the website is not online.

MultiSite Cache flush [<pattern>] [-v] [--exec]
\tFlush memcached cache by optional <pattern>";
    }

    public function executeCommand($command, $arguments, $dataArguments = array())
    {
        $subcommand = null;
        if (!empty($arguments[0])) {
            $subcommand = $arguments[0];
        }
        $pageCmd = $subcommand;
        if (!empty($arguments[1])) {
            $pageCmd .= '_'.$arguments[1];
        }
        if (!empty($arguments[2])) {
            $pageCmd .= '_'.$arguments[2];
        }
        
        \Cx\Core\Setting\Controller\Setting::init('MultiSite', '','FileSystem');
        // allow access only if mode is MODE_MANAGER or MODE_HYBRID
        if (
            php_sapi_name() != 'cli' &&
            !in_array(
                \Cx\Core\Setting\Controller\Setting::getValue(
                    'mode',
                    'MultiSite'
                ),
                array(self::MODE_MANAGER, self::MODE_HYBRID)
            )
        ) {
            return;
        }

        if ($subcommand != 'Cache') {
            // define frontend language
    // TODO: implement multilanguage support for API command
            if (!defined('FRONTEND_LANG_ID')) {
                define('FRONTEND_LANG_ID', 1);
            }

            // load language data of MultiSite component
            JsonMultiSiteController::loadLanguageData();

            // load application template
            $page = new \Cx\Core\ContentManager\Model\Entity\Page();
            $page->setVirtual(true);
            $page->setType(\Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION);
            $page->setCmd($pageCmd);
            $page->setModule('MultiSite');
            $pageContent = \Cx\Core\Core\Controller\Cx::getContentTemplateOfPage($page, null, \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_WEB, false);
            \LinkGenerator::parseTemplate($pageContent, true, new \Cx\Core\Net\Model\Entity\Domain(\Cx\Core\Setting\Controller\Setting::getValue('customerPanelDomain','MultiSite')));
            $objTemplate = new \Cx\Core\Html\Sigma();
            $objTemplate->setTemplate($pageContent);
            $objTemplate->setErrorHandling(PEAR_ERROR_DIE);
        }

        switch ($command) {
            case 'MultiSite':
                switch ($subcommand) {
                    case 'Signup':
                        echo $this->executeCommandSignup($objTemplate, $arguments);
                        break;

                    case 'Login':
                        echo $this->executeCommandLogin($objTemplate);
                        break;

                    case 'User':
                        echo $this->executeCommandUser($objTemplate, $arguments);
                        break;

                    case 'Subscription':
                        echo $this->executeCommandSubscription($objTemplate, $arguments);
                        break;
                        
                    case 'SubscriptionSelection':
                        echo $this->executeCommandSubscriptionSelection($objTemplate, $arguments);
                        break;
                        
                    case 'SubscriptionDetail':
                        echo $this->executeCommandSubscriptionDetail($objTemplate, $arguments);
                        break;

                    case 'SubscriptionAddWebsite':
                        echo $this->executeCommandSubscriptionAddWebsite($objTemplate, $arguments);
                        break;

                    case 'CopyWebsite':
                        echo $this->executeCommandCopyWebsite($objTemplate, $arguments);
                        break;

                    case 'Website':
                        echo $this->executeCommandWebsite($objTemplate, $arguments);
                        break;
                    
                    case 'Domain':
                        echo $this->executeCommandDomain($objTemplate, $arguments);
                        break;
                    
                    case 'Admin':
                        echo $this->executeCommandAdmin($objTemplate, $arguments);
                        break;
                    
                    case 'Payrexx':
                        $this->executeCommandPayrexx();
                        break;
                    
                    case 'Backup':
                        echo $this->executeCommandBackup($arguments);
                        break;
                    
                    case 'Restore':
                        echo $this->executeCommandRestore($arguments);
                        break;
                    
                    case 'Cron':
                        $this->executeCommandCron();
                        break;
                    
                    case 'Email':
                        echo $this->executeCommandEmail($objTemplate, $arguments);
                        break;
                    
                    case 'list':
                        echo $this->executeCommandList($arguments);
                        break;
                    
                    case 'exec':
                        echo $this->executeCommandExec($arguments);
                        break;

                    case 'Cache':
                        echo $this->executeCommandCache($arguments);
                        break;
                    
                    default:
                        break;
                }
                break;
            default:
                break;
        }
    }
    
    /**
     * Api Signup command
     * 
     * @param object $objTemplate Template object \Cx\Core\Html\Sigma
     * @param array  $arguments   Array parameters
     * 
     * @return string
     */
    public function executeCommandSignup($objTemplate, $arguments)
    {
        global $_ARRAYLANG;
        
        $websiteName = isset($arguments['multisite_address']) ? contrexx_input2xhtml($arguments['multisite_address']) : '';
        $domainRepository = new \Cx\Core\Net\Model\Repository\DomainRepository();
        $mainDomain = $domainRepository->getMainDomain()->getName();
        $signUpUrl = \Cx\Core\Routing\Url::fromMagic(ASCMS_PROTOCOL . '://' . $mainDomain . \Env::get('cx')->getBackendFolderName() . '/index.php?cmd=JsonData&object=MultiSite&act=signup');
        $emailUrl = \Cx\Core\Routing\Url::fromMagic(ASCMS_PROTOCOL . '://' . $mainDomain . \Env::get('cx')->getBackendFolderName() . '/index.php?cmd=JsonData&object=MultiSite&act=email');
        $addressUrl = \Cx\Core\Routing\Url::fromMagic(ASCMS_PROTOCOL . '://' . $mainDomain . \Env::get('cx')->getBackendFolderName() . '/index.php?cmd=JsonData&object=MultiSite&act=address');
        $paymentUrl = \Cx\Core\Routing\Url::fromMagic(ASCMS_PROTOCOL . '://' . $mainDomain . \Env::get('cx')->getBackendFolderName() . '/index.php?cmd=JsonData&object=MultiSite&act=getPayrexxUrl');
        $termsUrlValue = preg_replace('/\[\[([A-Z0-9_]*?)\]\]/', '{\\1}' ,\Cx\Core\Setting\Controller\Setting::getValue('termsUrl','MultiSite'));
        \LinkGenerator::parseTemplate($termsUrlValue);
        $termsUrl = '<a href="'.$termsUrlValue.'" target="_blank">'.$_ARRAYLANG['TXT_MULTISITE_ACCEPT_TERMS_URL_NAME'].'</a>';
        $websiteNameMinLength=\Cx\Core\Setting\Controller\Setting::getValue('websiteNameMinLength','MultiSite');
        $websiteNameMaxLength=\Cx\Core\Setting\Controller\Setting::getValue('websiteNameMaxLength','MultiSite');
        if (\Cx\Core\Setting\Controller\Setting::getValue('autoLogin','MultiSite')) {
            $buildWebsiteMsg = $_ARRAYLANG['TXT_MULTISITE_BUILD_WEBSITE_MSG_AUTO_LOGIN'];
        } else {
            $buildWebsiteMsg = $_ARRAYLANG['TXT_MULTISITE_BUILD_WEBSITE_MSG'];
        }
        $objTemplate->setVariable(array(
            'TITLE'                         => $_ARRAYLANG['TXT_MULTISITE_TITLE'],
            'TXT_MULTISITE_CLOSE'           => $_ARRAYLANG['TXT_MULTISITE_CLOSE'],
            'TXT_MULTISITE_EMAIL_ADDRESS'   => $_ARRAYLANG['TXT_MULTISITE_EMAIL_ADDRESS'],
            'TXT_MULTISITE_SITE_ADDRESS'         => $_ARRAYLANG['TXT_MULTISITE_SITE_ADDRESS'],
            'TXT_MULTISITE_SITE_ADDRESS_SCHEME'  => sprintf($_ARRAYLANG['TXT_MULTISITE_SITE_ADDRESS_SCHEME'], $websiteNameMinLength, $websiteNameMaxLength),
            'TXT_MULTISITE_CREATE_WEBSITE'  => $_ARRAYLANG['TXT_MULTISITE_SUBMIT_BUTTON'],
            'TXT_MULTISITE_ORDER_NOW'       => $_ARRAYLANG['TXT_MULTISITE_ORDER_BUTTON'],
            'MULTISITE_PATH'                => ASCMS_PROTOCOL . '://' . $mainDomain . \Env::get('cx')->getWebsiteOffsetPath(),
            'MULTISITE_DOMAIN'              => \Cx\Core\Setting\Controller\Setting::getValue('multiSiteDomain','MultiSite'),
            'POST_URL'                      => '',
            'MULTISITE_ADDRESS_MIN_LENGTH'  => $websiteNameMinLength,
            'MULTISITE_ADDRESS_MAX_LENGTH'  => $websiteNameMaxLength,
            'MULTISITE_ADDRESS'             => $websiteName,
            'MULTISITE_SIGNUP_URL'          => $signUpUrl->toString(),
            'MULTISITE_EMAIL_URL'           => $emailUrl->toString(),
            'MULTISITE_ADDRESS_URL'         => $addressUrl->toString(),
            'MULTISITE_PAYMENT_URL'         => $paymentUrl->toString(),
            'MULTISITE_CONVERSION_TRACK'            => !\FWValidator::isEmpty(\Cx\Core\Setting\Controller\Setting::getValue('conversionTracking', 'MultiSite')),
            'MULTISITE_TRACK_GOOGLE_CONVERSION'     => !\FWValidator::isEmpty(\Cx\Core\Setting\Controller\Setting::getValue('trackGoogleConversion','MultiSite')),
            'MULTISITE_GOOGLE_CONVERSION_ID'        => \Cx\Core\Setting\Controller\Setting::getValue('googleConversionId','MultiSite'),
            'MULTISITE_TRACK_FACEBOOK_CONVERSION'   => !\FWValidator::isEmpty(\Cx\Core\Setting\Controller\Setting::getValue('trackFacebookConversion','MultiSite')),
            'MULTISITE_FACEBOOK_CONVERSION_ID'      => \Cx\Core\Setting\Controller\Setting::getValue('facebookConversionId','MultiSite'),
            'TXT_MULTISITE_ACCEPT_TERMS'    => sprintf($_ARRAYLANG['TXT_MULTISITE_ACCEPT_TERMS'], $termsUrl),
            'TXT_MULTISITE_BUILD_WEBSITE_TITLE' => $_ARRAYLANG['TXT_MULTISITE_BUILD_WEBSITE_TITLE'],
            'TXT_MULTISITE_BUILD_WEBSITE_MSG' => $buildWebsiteMsg,
            'TXT_MULTISITE_REDIRECT_MSG'    => $_ARRAYLANG['TXT_MULTISITE_REDIRECT_MSG'],
            'TXT_MULTISITE_BUILD_SUCCESSFUL_TITLE' => $_ARRAYLANG['TXT_MULTISITE_BUILD_SUCCESSFUL_TITLE'],
            'TXT_MULTISITE_BUILD_ERROR_TITLE' => $_ARRAYLANG['TXT_MULTISITE_BUILD_ERROR_TITLE'],
            'TXT_MULTISITE_BUILD_ERROR_MSG' => $_ARRAYLANG['TXT_MULTISITE_BUILD_ERROR_MSG'],
            'TXT_CORE_MODULE_MULTISITE_INVALID_EMAIL' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_INVALID_EMAIL'],
            'TXT_MULTISITE_ACCEPT_TERMS_ERROR' => $_ARRAYLANG['TXT_MULTISITE_ACCEPT_TERMS_ERROR'],
    // TODO: add configuration option for contact details and replace the hard-coded e-mail address on the next line
            'TXT_MULTISITE_EMAIL_INFO'      => sprintf($_ARRAYLANG['TXT_MULTISITE_EMAIL_INFO'], 'info@cloudrexx.com'),
            'TXT_CORE_MODULE_MULTISITE_LOADING_TEXT' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_LOADING_TEXT'],
            'MULTISITE_SELECTED_THEME_ID'   => isset($_GET['theme-id']) ? contrexx_input2int($_GET['theme-id']) : null,
        ));
        $productId = !empty($arguments['product-id']) ? $arguments['product-id'] : \Cx\Core\Setting\Controller\Setting::getValue('defaultPimProduct','MultiSite');
        if (!empty($productId)) {
            $productRepository = \Env::get('em')->getRepository('Cx\Modules\Pim\Model\Entity\Product');
            $product = $productRepository->findOneBy(array('id' => $productId));
            if ($product) {
                self::parseProductForAddWebsite($objTemplate, $product);
            }
        }
        return $objTemplate->get();
    }
    
    /**
     * Api Login command
     * 
     * @param object $objTemplate Template object \Cx\Core\Html\Sigma
     * @param array  $arguments   Array parameters
     * 
     * @return string
     */
    public function executeCommandLogin($objTemplate)
    {
        global $objInit, $_ARRAYLANG, $_CORELANG;
        
        $langData = $objInit->loadLanguageData('Login');
        $_ARRAYLANG = array_merge($_ARRAYLANG, $langData);
        $langData = $objInit->loadLanguageData('core');
        $_CORELANG = $_ARRAYLANG = array_merge($_ARRAYLANG, $langData);
        $objTemplate->setVariable(array(
            'TITLE'                 => $_ARRAYLANG['TXT_LOGIN_LOGIN'],
            'TXT_LOGIN_PASSWORD'    => $_ARRAYLANG['TXT_LOGIN_PASSWORD'],
            'TXT_LOGIN_USERNAME'    => $_ARRAYLANG['TXT_LOGIN_USERNAME'],
            'TXT_LOGIN_REMEMBER_ME' => $_ARRAYLANG['TXT_CORE_REMEMBER_ME'],
            'TXT_LOGIN_LOGIN'       => $_ARRAYLANG['TXT_LOGIN_LOGIN'],
            'TXT_LOGIN_PASSWORD_LOST'=> $_ARRAYLANG['TXT_LOGIN_PASSWORD_LOST'],
        ));
        
        return $objTemplate->get();
    }
    
    /**
     * Api User command
     * 
     * @param object $objTemplate Template object \Cx\Core\Html\Sigma
     * @param array  $arguments   Array parameters
     * 
     * @return string
     */
    public function executeCommandUser($objTemplate, $arguments)
    {
        // profile attribute labels are stored in core-lang
        global $objInit, $_CORELANG, $_ARRAYLANG;
        $langData = $objInit->loadLanguageData('core');
        $_CORELANG = $_ARRAYLANG = array_merge($_ARRAYLANG, $langData);

        if (!self::isUserLoggedIn()) {
            return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_LOGIN_NOACCESS'];
        }
        $objUser = \FWUser::getFWUserObject()->objUser;

        $isEditMode = (isset($arguments[2]) && $arguments[2] == 'Edit') ? true : false;

        if ($arguments[1] == 'Company') {
            $this->parseCrmInfoForModal($objUser, $objTemplate, $isEditMode);
            $objTemplate->setVariable(array(
                'TXT_CORE_MODULE_MULTISITE_USER_ACCOUNT_INDUSTRY_TYPE' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_USER_ACCOUNT_INDUSTRY_TYPE'],
                'TXT_CORE_MODULE_MULTISITE_USER_ACCOUNT_COMPANY_SIZE'  => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_USER_ACCOUNT_COMPANY_SIZE'],
                'TXT_CORE_MODULE_MULTISITE_USER_ACCOUNT_CUSTOMER_TYPE' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_USER_ACCOUNT_CUSTOMER_TYPE'],
                'TXT_CORE_MODULE_MULTISITE_USER_COMPANY_INFO_TITLE'    => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_USER_COMPANY_INFO_TITLE'],
                'TXT_CORE_MODULE_MULTISITE_PLEASE_SELECT'              => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_PLEASE_SELECT'],
            ));
        } else {
            $blockName = 'multisite_user';
            $placeholderPrefix = strtoupper($blockName).'_';
            $objAccessLib = new \Cx\Core_Modules\Access\Controller\AccessLib($objTemplate);
            $objAccessLib->setModulePrefix($placeholderPrefix);
            $objAccessLib->setAttributeNamePrefix($blockName.'_profile_attribute');
            $objAccessLib->setAccountAttributeNamePrefix($blockName.'_account_');

            $objUser->objAttribute->first();
            while (!$objUser->objAttribute->EOF) {
                $objAttribute = $objUser->objAttribute->getById($objUser->objAttribute->getId());
                $objAccessLib->parseAttribute($objUser, $objAttribute->getId(), 0, $isEditMode, false, false, false, false);
                $objUser->objAttribute->next();
            }
        $objAccessLib->parseAccountAttributes($objUser);
        }
        $objTemplate->setVariable(array(
            'MULTISITE_USER_PROFILE_SUBMIT_URL'         => \Env::get('cx')->getWebsiteBackendPath() . '/index.php?cmd=JsonData&object=MultiSite&act=updateOwnUser',
            'TXT_CORE_MODULE_MULTISITE_LOADING_TEXT'    => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_LOADING_TEXT'],
            'TXT_CORE_MODULE_MULTISITE_SAVE'            => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SAVE'],
            'TXT_CORE_MODULE_MULTISITE_CANCEL'          => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CANCEL']
        ));
        
        return $objTemplate->get();
    }
    
    /**
     * Api Subscription command
     * 
     * @param object $objTemplate Template object \Cx\Core\Html\Sigma
     * @param array  $arguments   Array parameters
     * 
     * @return string
     */
    public function executeCommandSubscription($objTemplate, $arguments) {
        global $_ARRAYLANG;

        $objTemplate->setGlobalVariable($_ARRAYLANG);
        
        if (!self::isUserLoggedIn()) {
            return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_LOGIN_NOACCESS'];
        }

        $crmContactId = \FWUser::getFWUserObject()->objUser->getCrmUserId();
        if (empty($crmContactId)) {
            return $objTemplate->get(); // Return template so "add" button and "go to overview" button are displayed
        }

        //Get the input values
        $status         = isset($arguments['status']) ? contrexx_input2raw($arguments['status']) : '';
        $website_status = isset($arguments['website_status']) ? contrexx_input2raw($arguments['website_status']) : '';
        $excludeProduct = isset($arguments['exclude_product']) ? array_map('contrexx_input2raw', $arguments['exclude_product']) : '';
        $includeProduct = isset($arguments['include_product']) ? array_map('contrexx_input2raw', $arguments['include_product']) : '';
        $searchTerm     = isset($arguments['search']) ? contrexx_input2raw($arguments['search']) : '';
        
        $em = $this->cx->getDb()->getEntityManager();
        //Get the subscriptions based on CRM contact id and other parameters
        $criteria = array(
                        'contactId'       => $crmContactId, 
                        'status'          => $status, 
                        'excludeProduct'  => $excludeProduct, 
                        'includeProduct'  => $includeProduct
                    );
        if (!empty($searchTerm)) {
            $criteria['term']              = $searchTerm;
            $criteria['filterDescription'] = $searchTerm;
        }
        $websiteRepo      = $em->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
        $subscriptionRepo = $em->getRepository('Cx\Modules\Order\Model\Entity\Subscription');
        $subscriptions    = $subscriptionRepo->findSubscriptionsBySearchTerm($criteria);
        
        //parse the Site Details
        if (!empty($subscriptions)) {
            foreach ($subscriptions as $subscription) {
                if ($subscription->getState() == \Cx\Modules\Order\Model\Entity\Subscription::STATE_TERMINATED) {
                    continue;
                }

                $product = $subscription->getProduct();
                if (!$product) {
                    continue;
                }

                $websiteNames      = array();
                $description       = $subscription->getDescription();
                $websiteCollection = $subscription->getProductEntity();
                if (!($websiteCollection instanceof \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteCollection)
                    && !($websiteCollection instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Website)
                ) {
                    continue;
                }
                if (empty($description)) {
                    if (   $websiteCollection instanceof \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteCollection
                        && $websiteCollection->getWebsites()
                    ) {
                        foreach ($websiteCollection->getWebsites() as $website) {
                            $websiteNames[] = $website->getName();
                        }
                        $description = !empty($websiteNames) ? implode(', ', $websiteNames) : '';
                    } elseif ($websiteCollection instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Website) {
                        $description = $websiteCollection->getName();
                    }
                }

                $objTemplate->setGlobalVariable(array(
                    'MULTISITE_SUBSCRIPTION_ID'          => contrexx_raw2xhtml($subscription->getId()),
                    'MULTISITE_SUBSCRIPTION_DESCRIPTION' => contrexx_raw2xhtml($description),
                    'MULTISITE_WEBSITE_PLAN'             => contrexx_raw2xhtml($product->getName()),
                    'MULTISITE_WEBSITE_INVOICE_DATE'     => $subscription->getRenewalDate() ? $subscription->getRenewalDate()->format('d.m.Y') : '',
                    'MULTISITE_WEBSITE_EXPIRE_DATE'      => $subscription->getExpirationDate() ? $subscription->getExpirationDate()->format('d.m.Y') : '',    
                ));

                if ($status == 'valid' && $objTemplate->blockExists('showUpgradeButton')) {
                    $product->isUpgradable() ? $objTemplate->touchBlock('showUpgradeButton') : $objTemplate->hideBlock('showUpgradeButton');
                }

                if ($status != 'expired') {
                    $websites = $websiteRepo->getWebsitesByTermAndSubscription($searchTerm, $subscription->getId());
                    if ($websites) {
                        foreach ($websites as $website) {
                            self::parseWebsiteDetails($objTemplate, $website, $website_status);
                            $objTemplate->parse('showWebsites');
                        }
                    }
                    if ($websiteCollection instanceof \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteCollection) {
                        self::showOrHideBlock($objTemplate, 'showAddWebsiteButton', ($websiteCollection->getQuota() > count($websiteCollection->getWebsites())));
                    }
                } else {
                    $objTemplate->touchBlock('showWebsites');
                }

                $objTemplate->parse('showSiteDetails');
            }
        } else {
            $objTemplate->hideBlock('showSiteTable');
        }
        return $objTemplate->get();
    }
    
    /**
     * Api SubscriptionSelection command
     * 
     * @param object $objTemplate Template object \Cx\Core\Html\Sigma
     * @param array  $arguments   Array parameters
     * 
     * @return string
     */
    public function executeCommandSubscriptionSelection($objTemplate, $arguments)
    {
        global $_ARRAYLANG;

        if (!self::isUserLoggedIn()) {
            return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_LOGIN_NOACCESS'];
        }
        
        $websiteId = isset($arguments['id']) ? $arguments['id'] : 0;
        $subscriptionId = isset($arguments['subscriptionId']) ? $arguments['subscriptionId'] : 0;
        
        $objTemplate->setGlobalVariable($_ARRAYLANG);
        $objUser = \FWUser::getFWUserObject()->objUser;
        $crmContactId = $objUser->getCrmUserId();
        $userId = $objUser->getId();
        if (\FWValidator::isEmpty($crmContactId)) {
            // create a new CRM Contact and link it to the User account
            \Cx\Modules\Crm\Controller\CrmLibrary::addCrmContactFromAccessUser($objUser);
        }
        
        $subscription = null;
        $website      = null;
        $termsUrlValue = preg_replace('/\[\[([A-Z0-9_]*?)\]\]/', '{\\1}' ,\Cx\Core\Setting\Controller\Setting::getValue('termsUrl','MultiSite'));
        \LinkGenerator::parseTemplate($termsUrlValue);
        $termsUrl = '<a href="'.$termsUrlValue.'" target="_blank">'.$_ARRAYLANG['TXT_MULTISITE_ACCEPT_TERMS_URL_NAME'].'</a>';
        
        if (!\FWValidator::isEmpty($subscriptionId)) {
            $subscription = \Env::get('em')->getRepository('Cx\Modules\Order\Model\Entity\Subscription')->findOneBy(array('id' => $subscriptionId));
            
            if ($subscription) {
                $order = $subscription->getOrder();
                if (!$order) {
                    return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_ORDER_NOT_EXISTS'];
                }
                
                //Verify the owner of the associated Order of the Subscription is actually owned by the currently sign-in user
                if ($crmContactId != $order->getContactId()) {
                    return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_MULTISITE_USER'];
                }
                
                if (!\FWValidator::isEmpty($websiteId)) {
                    $websiteServiceRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
                    $website = $websiteServiceRepo->findOneById($websiteId);
                    if (!$website) {
                        return $_ARRAYLANG['TXT_MULTISITE_UNKOWN_WEBSITE'];
                    }

                    if ($website->getOwner()->getId() != $userId) {
                        return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_MULTISITE_USER'];
                    }
                }
            } else {
                return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_SUBSCRIPTION_NOT_EXISTS'];
            }
        }
        $currency = self::getUserCurrency($crmContactId);
        $websiteName = $website instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Website ? $website->getName() : '';
        $products = array();
        $renewalPlan = 'monthly';
        if ($subscription) {
            if ($subscription->getProductEntity() instanceof \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteCollection) {
                $renewalPlan = self::getSubscriptionRenewalPlan($subscription->getRenewalUnit(), $subscription->getRenewalQuantifier());
            }
            $product = $subscription->getProduct();
            if (!$product) {
                return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_PRODUCT_NOT_EXISTS'];
            }

            $productCollection = $product->getUpgrades();
            // cast $productCollection into an array -> this is required as uasort() only works with arrays
            foreach ($productCollection as $product) {
                $products[] = $product;
            }
        } else {
            $products = \Env::get('em')->getRepository('Cx\Modules\Pim\Model\Entity\Product')->findAll();
        }

        uasort($products, function($a, $b) use ($currency) {
// customizing: list subscription Non-Profit always at the end
// TODO: implement some sort of sorting ability to the model collection
            # list Non-Profit at last position
            if ($a->getName() == 'Non-Profit') return 1;
            if ($b->getName() == 'Non-Profit') return -1;

            # list Trial at first position
            //if ($a->getName() == 'Trial') return -1;
            //if ($b->getName() == 'Trial') return 1;
            
            if ($a->getPaymentAmount(\Cx\Modules\Pim\Model\Entity\Product::UNIT_MONTH, 1, $currency) == $b->getPaymentAmount(\Cx\Modules\Pim\Model\Entity\Product::UNIT_MONTH, 1, $currency)) {
                return 0;
            }
            return ($a->getPaymentAmount(\Cx\Modules\Pim\Model\Entity\Product::UNIT_MONTH, 1, $currency) < $b->getPaymentAmount(\Cx\Modules\Pim\Model\Entity\Product::UNIT_MONTH, 1, $currency)) ? -1 : 1;
        });

        if (\FWValidator::isEmpty($products)) {
            return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_PRODUCTS_NOT_FOUND'];
        }
        
        foreach ($products as $product) {
// customizing: do not list Trial and Enterprise product
// TODO: implement some sort of selective product selection in the multisite configuration
            if (
                in_array(
                    $product->getName(),
                    array(
                        'Free',
                        'Solo',
                        'Enterprise',
                        'Partnerbeitrag',
                        'EULA Standard',
                        'EULA Enterprise',
                        'EULA Non-Profit',
                        'Generic App',
                    )
                )
            ) {
                continue;
            }
            $productName = contrexx_raw2xhtml($product->getName());
            $priceMonthly = $product->getPaymentAmount(\Cx\Modules\Pim\Model\Entity\Product::UNIT_MONTH, 1, $currency, true);
            $priceAnnually = number_format($product->getPaymentAmount(\Cx\Modules\Pim\Model\Entity\Product::UNIT_YEAR, 1, $currency, true), 2, '.', "'");
            $priceBiannually = number_format($product->getPaymentAmount(\Cx\Modules\Pim\Model\Entity\Product::UNIT_YEAR, 2, $currency, true), 2, '.', "'");
            $objTemplate->setVariable(array(
                'MULTISITE_WEBSITE_PRODUCT_NAME' => $productName,
                'MULTISITE_WEBSITE_PRODUCT_ATTRIBUTE_ID' => lcfirst($productName),
                'MULTISITE_WEBSITE_PRODUCT_PRICE_MONTHLY' => $priceMonthly,
                'MULTISITE_WEBSITE_PRODUCT_PRICE_ANNUALLY' => (substr($priceAnnually, -3) == '.00' ? substr($priceAnnually, 0 , -3) : $priceAnnually),
                'MULTISITE_WEBSITE_PRODUCT_PRICE_BIANNUALLY' => (substr($priceBiannually, -3) == '.00' ? substr($priceBiannually, 0 , -3) : $priceBiannually),
                'MULTISITE_WEBSITE_PRODUCT_NOTE_PRICE' => $product->getNotePrice(),
                'MULTISITE_WEBSITE_PRODUCT_ID' => $product->getId(),
                'MULTISITE_PRODUCT_TYPE' => $product->getEntityClass() == 'Cx\Core_Modules\MultiSite\Model\Entity\Website' ? 'website' : 'websiteCollection'
            ));
            $objTemplate->parse('showProduct');
        }
        $objTemplate->setVariable(array(
            'MULTISITE_SUBSCRIPTION_ID'             => $subscriptionId,
            'MULTISITE_WEBSITE_NAME'                => $websiteName,
            'MULTISITE_SUBSCRIPTION_RENEWAL_PLAN'   => $renewalPlan,
            'MULTISITE_ACCEPT_TERMS_URL'            => sprintf($_ARRAYLANG['TXT_MULTISITE_ACCEPT_TERMS'], $termsUrl),
            'MULTISITE_IS_USER_HAS_PAYREXX_ACCOUNT' => !\FWValidator::isEmpty($objUser->getProfileAttribute(\Cx\Core\Setting\Controller\Setting::getValue('externalPaymentCustomerIdProfileAttributeId','MultiSite'))) ? 'true' : 'false',
        ));
        return $objTemplate->get();
    }
    
    /**
     * Get the subscription renewal plan
     * 
     * @param string  $unit
     * @param integer $quantifier
     * 
     * @return string
     */
    public static function getSubscriptionRenewalPlan($unit, $quantifier) {
        
        $renewalPlan = 'monthly';
        
        switch ($unit) {
            case \Cx\Modules\Pim\Model\Entity\Product::UNIT_YEAR:
                $renewalPlan = ($quantifier == 1) ? 'annually' : 'biannually';
                break;
        }

        return $renewalPlan;
    }
    
    /**
     * Api SubscriptionDetail command
     * 
     * @param object $objTemplate Template object \Cx\Core\Html\Sigma
     * @param array  $arguments   Array parameters
     * 
     * @return string
     */
    public function executeCommandSubscriptionDetail($objTemplate, $arguments)
    {
        global $_ARRAYLANG;

        $objTemplate->setGlobalVariable($_ARRAYLANG);
        
        $subscriptionId = isset($arguments['id']) ? contrexx_input2raw($arguments['id']) : 0;
        $action         = isset($arguments['action']) ? contrexx_input2raw($arguments['action']) : '';

        if (!self::isUserLoggedIn()) {
            return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_LOGIN_NOACCESS'];
        }

        $crmContactId = \FWUser::getFWUserObject()->objUser->getCrmUserId();
        if (empty($crmContactId)) {
            return ' '; // Do not show subscription detail
        }
        
        if (empty($subscriptionId)) {
            return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_SUBSCRIPTIONID_EMPTY'];
        }

        $subscriptionRepo = \Env::get('em')->getRepository('Cx\Modules\Order\Model\Entity\Subscription');
        $subscriptionObj = $subscriptionRepo->findOneBy(array('id' => $subscriptionId));

        if (!$subscriptionObj) {
            return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_SUBSCRIPTION_NOT_EXISTS'];
        }

        $order = $subscriptionObj->getOrder();

        if (!$order) {
            return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_ORDER_NOT_EXISTS'];
        }

        //Verify the owner of the associated Order of the Subscription is actually owned by the currently sign-in user
        if ($crmContactId != $order->getContactId()) {
            return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_MULTISITE_USER'];
        }
        
        if (!\FWValidator::isEmpty($action)) {
            switch ($action) {
                case 'subscriptionCancel':
                   $subscriptionObj->setState(\Cx\Modules\Order\Model\Entity\Subscription::STATE_CANCELLED);
                    \Env::get('em')->flush();
                    return $this->parseJsonMessage($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_CANCELLED_SUCCESS_MSG'], true);
                    break;
                
                case 'updateDescription':
                    $description = isset($_POST['description'])
                                        ? contrexx_input2raw($_POST['description'])
                                        : '';
                    $subscriptionObj->setDescription($description);
                    \Env::get('em')->flush();
                    return $this->parseJsonMessage($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_DESCRIPTION_SUCCESS_MSG'], true);
                    break;
                default :
                    break;
            }
        }
        
        $product = $subscriptionObj->getProduct();

        if (!$product) {
            return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_PRODUCT_NOT_EXISTS'];
        }

        $subscriptionExpirationDate = $subscriptionObj->getExpirationDate() ? $subscriptionObj->getExpirationDate()->format(ASCMS_DATE_FORMAT_DATE) : '';
        $objTemplate->setVariable(array(
            'MULTISITE_SUBSCRIPTION_ID'      => contrexx_raw2xhtml($subscriptionObj->getId()),
            'MULTISITE_WEBSITE_PRODUCT_NAME' => contrexx_raw2xhtml($product->getName()),
            'MULTISITE_WEBSITE_SUBSCRIPTION_DATE' => $subscriptionObj->getSubscriptionDate() ? contrexx_raw2xhtml($subscriptionObj->getSubscriptionDate()->format('d.m.Y')) : '',
            'MULTISITE_WEBSITE_SUBSCRIPTION_EXPIRATIONDATE' => contrexx_raw2xhtml($subscriptionExpirationDate),
            'MULTISITE_SUBSCRIPTION_DESCRIPTION' => contrexx_raw2xhtml($subscriptionObj->getDescription()),
            'MULTISITE_SUBSCRIPTION_CANCEL_CONTENT' => sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_CANCEL_CONTENT'], $subscriptionExpirationDate),
            'MULTISITE_SUBSCRIPTION_CANCEL_SUBMIT_URL' => '/api/MultiSite/SubscriptionDetail?action=subscriptionCancel&id=' . $subscriptionId,
            'MULTISITE_SUBSCRIPTION_DESCRIPTION_SUBMIT_URL' => '/api/MultiSite/SubscriptionDetail?action=updateDescription&id=' . $subscriptionId,
        ));
        
        $cancelButtonStatus = ($subscriptionObj->getState() !== \Cx\Modules\Order\Model\Entity\Subscription::STATE_CANCELLED);
        self::showOrHideBlock($objTemplate, 'showUpgradeButton', $product->isUpgradable());
        self::showOrHideBlock($objTemplate, 'showSubscriptionCancelButton', $cancelButtonStatus);

        if ($objTemplate->blockExists('showWebsites')) {
            $websiteCollection = $subscriptionObj->getProductEntity();
                if ($websiteCollection instanceof \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteCollection) {
                    foreach ($websiteCollection->getWebsites() as $website) {
                        if (!($website instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Website)) {
                            continue;
                        }
                        self::parseWebsiteDetails($objTemplate, $website);

                        $objTemplate->parse('showWebsites');
                    }
                    self::showOrHideBlock($objTemplate, 'showAddWebsiteButton', ($websiteCollection->getQuota() > count($websiteCollection->getWebsites())));
                } elseif ($websiteCollection instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Website) {
                    self::parseWebsiteDetails($objTemplate, $websiteCollection);
                    self::showOrHideBlock($objTemplate, 'showAddWebsiteButton', false);
                    $objTemplate->parse('showWebsites');
                }
                if(array_key_exists('showWebsites', $objTemplate->_parsedBlocks) && $objTemplate->blockExists('showWebsitesHeader')){
                    $objTemplate->touchBlock('showWebsitesHeader');
                }else{
                    $objTemplate->hideBlock('showWebsitesHeader');
                }
        }

        //payments
        self::showOrHideBlock($objTemplate, 'showPayments', !\FWValidator::isEmpty($subscriptionObj->getExternalSubscriptionId()));

        return $objTemplate->get();
    }
    
    /**
     * Api SubscriptionAddWebsite command
     * 
     * @param object $objTemplate Template object \Cx\Core\Html\Sigma
     * @param array  $arguments   Array parameters
     * 
     * @return string
     */
    public function executeCommandSubscriptionAddWebsite($objTemplate, $arguments)
    {
        global $_ARRAYLANG;
        
        $objTemplate->setGlobalVariable($_ARRAYLANG);

        // Website form submission will be done from post
        $subscriptionId =   !empty($_POST['subscription_id'])
                          ? contrexx_input2int($_POST['subscription_id'])
                          : (isset($arguments['id']) ? contrexx_input2int($arguments['id']) : 0);
        $productId      =   !empty($_POST['product_id'])
                          ? 1//contrexx_input2int($_POST['product_id'])
                          : (isset($arguments['productId']) ? contrexx_input2int($arguments['productId']) : 0);
        $websiteName    =   !empty($_POST['multisite_address'])
                          ? contrexx_input2raw($_POST['multisite_address'])
                          : (isset($arguments['multisite_address']) ? contrexx_input2raw($arguments['multisite_address']) : '');
        $websiteId      = isset($arguments['websiteId']) ? contrexx_input2int($arguments['websiteId']) : 0;
        $isCopyWebsite  = !empty($arguments['copy']);

        if (!self::isUserLoggedIn()) {
            return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_LOGIN_NOACCESS'];
        }

        $crmContactId = \FWUser::getFWUserObject()->objUser->getCrmUserId();
        if (   !$isCopyWebsite
            && \FWValidator::isEmpty($subscriptionId)
            && \FWValidator::isEmpty($productId)
        ) {
            return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_SUBSCRIPTIONID_EMPTY'];
        }

        if ($isCopyWebsite && empty($websiteId)) {
            return '';
        }

        if ($isCopyWebsite) {
            $websiteRepo = $this->cx->getDb()->getEntityManager()->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
            $copyWebsite = $websiteRepo->findOneById($websiteId);
            if (!$copyWebsite) {
                return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_EXISTS'];
            }
            $isWebsiteBackupAllowed = $this->verifyWebsiteBackupLimit($websiteId, $copyWebsite->getWebsiteServiceServer());
            $this->showOrHideBlock(
                $objTemplate,
                'multisite_copy_website_website_limit_error',
                !$isWebsiteBackupAllowed
            );
            $this->showOrHideBlock($objTemplate, 'multisite_subscription_add_website_block', $isWebsiteBackupAllowed);
            if (!$isWebsiteBackupAllowed) {
                return $objTemplate->get();
            }
        }

        if (isset($arguments['saveWebsite'])) {
            $resp = array();
            if ($isCopyWebsite) {
                $copyParams = array(
                    'websiteId'         => $websiteId,
                    'multisite_address' => $websiteName,
                    'subscriptionId'    => $subscriptionId,
                    'productId'         => $productId,
                );
                $resp = JsonMultiSiteController::executeCommandOnManager('copyWebsite', $copyParams);
                if ($resp && $resp->status == 'success') {
                    $resp = array(
                        'status'  => $resp->data->status,
                        'message' => $resp->data->message,
                    );
                } else {
                    $resp = array('status' => 'error');
                }
            } elseif (!\FWValidator::isEmpty($subscriptionId)) {
                $resp = $this->createNewWebsiteInSubscription($subscriptionId, $websiteName);
            } elseif (!\FWValidator::isEmpty($productId)) {
                $resp = $this->createNewWebsiteByProduct($productId, $websiteName, null, 0, \Cx\Modules\Pim\Model\Entity\Product::UNIT_MONTH);
            }

            $responseStatus  = isset($resp['status']) && $resp['status'] == 'success';
            $responseMessage = isset($resp['message']) ? $resp['message'] : '';
            $reload          = isset($resp['reload']) ? $resp['reload'] : '';
            return $this->parseJsonMessage($responseMessage, $responseStatus, $reload);
        } else {
            $domainRepository = new \Cx\Core\Net\Model\Repository\DomainRepository();
            $mainDomain = $domainRepository->getMainDomain()->getName();
            $addressUrl = \Cx\Core\Routing\Url::fromMagic(ASCMS_PROTOCOL . '://' . $mainDomain . \Env::get('cx')->getBackendFolderName() . '/index.php?cmd=JsonData&object=MultiSite&act=address');
            
            $websiteNameMinLength = \Cx\Core\Setting\Controller\Setting::getValue('websiteNameMinLength','MultiSite');
            $websiteNameMaxLength = \Cx\Core\Setting\Controller\Setting::getValue('websiteNameMaxLength','MultiSite');
            
            $queryArguments = array(
                'saveWebsite' => 1,
                'page_reload'  => (isset($_GET['multisite_page_reload']) && $_GET['multisite_page_reload'] == 'reload_page' ? 'reload_page' : ''),
            );

            if ($isCopyWebsite) {
                $queryArguments['copy']      = 1;
                $queryArguments['websiteId'] = $websiteId;
            }
            if ($isCopyWebsite && empty($productId)) {
                $userId = \FWUser::getFWUserObject()->objUser->getId();
                $params = array(
                    'userId' => $userId
                );
                $resp = JsonMultiSiteController::executeCommandOnManager('getAvailableSubscriptionsByUserId', $params);
                if ($resp && $resp->status == 'success' && $resp->data->status == 'success') {
                    foreach ($resp->data->subscriptionsList as $subscription) {
                        $subscriptionListId = $subscriptionListName = '';
                        list($subscriptionListId, $subscriptionListName) = explode(':', $subscription);
                        $objTemplate->setVariable(array(
                            'MULTISITE_SUBSCRIPTION_ID'       => contrexx_raw2xhtml($subscriptionListId),
                            'MULTISITE_SUBSCRIPTION_NAME'     => sprintf(
                                $_ARRAYLANG['TXT_MULTISITE_COPY_SUBSCRIPTION_NAME'],
                                contrexx_raw2xhtml($subscriptionListId),
                                contrexx_raw2xhtml($subscriptionListName)
                            ),
                            'MULTISITE_SUBSCRIPTION_SELECTED' => $subscriptionId == $subscriptionListId ? 'selected="selected"' : '',
                        ));
                        $objTemplate->parse('openSubscriptions');
                    }
                }
                $this->showOrHideBlock($objTemplate, 'multisite_copy_website_subscription_selection', true);
                $this->showOrHideBlock($objTemplate, 'multisite_copy_website_product_selection', false);
                $this->showOrHideBlock($objTemplate, 'multisite_copy_website_subscription_selected', false);
            } else {
                $this->showOrHideBlock($objTemplate, 'multisite_copy_website_subscription_selection', false);
                $this->showOrHideBlock($objTemplate, 'multisite_copy_website_product_selection', !empty($productId));
                $this->showOrHideBlock($objTemplate, 'multisite_copy_website_subscription_selected', true);
            }

            $websiteSubmitUrl = '/api/MultiSite/SubscriptionAddWebsite?' . self::buildHttpQueryString($queryArguments);
            $objTemplate->setVariable(array(
                'MULTISITE_ADD_WEBSITE_URL'       => $websiteSubmitUrl,
                'MULTISITE_CREATE_WEBSITE_BUTTON' => $isCopyWebsite ? $_ARRAYLANG['TXT_MULTISITE_WEBSITE_COPY'] : $_ARRAYLANG['TXT_MULTISITE_SUBMIT_BUTTON'],
                'MULTISITE_ADD_WEBSITE_TITLE'     => $isCopyWebsite
                                                     ? sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_COPY_WEBSITE'], contrexx_raw2xhtml($copyWebsite->getName()))
                                                     : $_ARRAYLANG['TXT_MULTISITE_ADD_NEW_WEBSITE'],
                'MULTISITE_COPY_INFO'             => $isCopyWebsite
                                                     ? sprintf($_ARRAYLANG['TXT_MULTISITE_COPY_INFO'], contrexx_raw2xhtml($copyWebsite->getBaseDn()->getName()))
                                                     : $_ARRAYLANG['TXT_MULTISITE_ADD_NEW_WEBSITE'],
                'MULTISITE_BUILD_WEBSITE_MSG'     => $isCopyWebsite
                                                     ? $_ARRAYLANG['TXT_MULTISITE_WEBSITE_COPY_PROGRESS']
                                                     : $_ARRAYLANG['TXT_MULTISITE_BUILD_WEBSITE_MSG'],
                'MULTISITE_COPY_WEBSITE_ID'       => $isCopyWebsite ? $websiteId : 0,
                'MULTISITE_IS_WEBSITE_COPY'       => $isCopyWebsite ? 1 : 0,
                'MULTISITE_WEBSITE_ADDRESS'       => contrexx_raw2xhtml($websiteName),
                'MULTISITE_SELECTED_SUBSCRIPTION' => $subscriptionId,

                'MULTISITE_DOMAIN'                 => \Cx\Core\Setting\Controller\Setting::getValue('multiSiteDomain','MultiSite'),
                'MULTISITE_RELOAD_PAGE'            => (isset($_GET['multisite_page_reload']) && $_GET['multisite_page_reload'] == 'reload_page' ? 'reload_page' : ''),
                'MULTISITE_ADDRESS_URL'            => $addressUrl->toString(),
                'TXT_MULTISITE_SITE_ADDRESS_INFO'  => sprintf($_ARRAYLANG['TXT_MULTISITE_SITE_ADDRESS_SCHEME'], $websiteNameMinLength, $websiteNameMaxLength),
            ));

            if (!empty($productId)) {
                $productRepository = \Env::get('em')->getRepository('Cx\Modules\Pim\Model\Entity\Product');
                $product = $productRepository->findOneBy(array('id' => $productId));
                if ($product) {
                    self::parseProductForAddWebsite($objTemplate, $product, $crmContactId);
                } else {
                    return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_PRODUCT_NOT_EXISTS'];
                }
            }
            return $objTemplate->get();
        }
    }
    
    /**
     * Api Copy website command
     * 
     * @param object $objTemplate Template object \Cx\Core\Html\Sigma
     * @param array  $arguments   Array parameters
     * 
     * @return string Content for copy website
     * @throws MultiSiteException
     */
    public function executeCommandCopyWebsite($objTemplate, $arguments)
    {
        global $_ARRAYLANG;

        if (!$this->isCrmUser()) {
            return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_MULTISITE_USER'];
        }

        $objTemplate->setGlobalVariable($_ARRAYLANG);

        $websiteId = isset($arguments['id']) ? contrexx_input2int($arguments['id']) : 0;
        if (empty($websiteId)) {
            return '';
        }

        $userId = \Fwuser::getFWUserObject()->objUser->getId();
        $params = array(
            'userId' => $userId
        );
        $resp = JsonMultiSiteController::executeCommandOnManager('getAvailableSubscriptionsByUserId', $params);
        if ($resp && $resp->status == 'success' && $resp->data->status == 'success') {
            foreach ($resp->data->subscriptionsList as $subscription) {
                $subscriptionId = $subscriptionName = '';
                list($subscriptionId, $subscriptionName) = explode(':', $subscription);
                $objTemplate->setVariable(array(
                    'MULTISITE_SUBSCRIPTION_ID'   => contrexx_raw2xhtml($subscriptionId),
                    'MULTISITE_SUBSCRIPTION_NAME' => contrexx_raw2xhtml($subscriptionName),
                ));
                $objTemplate->parse('openSubscriptions');
            }
        }

        $websiteRepo = $this->cx->getDb()->getEntityManager()->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
        $website     = $websiteRepo->findOneById($websiteId);
        if (!$website) {
            throw new MultiSiteException($_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_EXISTS']);
        }
        $websiteNameMinLength = \Cx\Core\Setting\Controller\Setting::getValue('websiteNameMinLength','MultiSite');
        $websiteNameMaxLength = \Cx\Core\Setting\Controller\Setting::getValue('websiteNameMaxLength','MultiSite');

        $domainRepository = new \Cx\Core\Net\Model\Repository\DomainRepository();
        $mainDomain = $domainRepository->getMainDomain()->getName();
        $addressUrl = \Cx\Core\Routing\Url::fromMagic(ASCMS_PROTOCOL . '://' . $mainDomain . $this->cx->getBackendFolderName() . '/index.php?cmd=JsonData&object=MultiSite&act=address');
        $copyUrl    = \Cx\Core\Routing\Url::fromMagic(ASCMS_PROTOCOL . '://' . $mainDomain . $this->cx->getBackendFolderName() . '/index.php?cmd=JsonData&object=MultiSite&act=copyWebsite');

        $objTemplate->setVariable(array(
            'MULTISITE_PATH'                  => ASCMS_PROTOCOL . '://' . $mainDomain . $this->cx->getWebsiteOffsetPath(),
            'MULTISITE_DOMAIN'                => \Cx\Core\Setting\Controller\Setting::getValue('multiSiteDomain','MultiSite'),
            'MULTISITE_ADDRESS_URL'           => $addressUrl->toString(),
            'MULTISITE_COPY_WEBSITE_URL'      => $copyUrl->toString(),
            'MULTISITE_WEBSITE_ID'            => $websiteId,
            'TXT_MULTISITE_SITE_ADDRESS_INFO' => sprintf($_ARRAYLANG['TXT_MULTISITE_SITE_ADDRESS_SCHEME'], $websiteNameMinLength, $websiteNameMaxLength),
            'MULTISITE_COPY_WEBSITE'          => sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_COPY_WEBSITE'], contrexx_raw2xhtml($website->getName())),
        ));
        return $objTemplate->get();
    }

    /**
     * Api Website command
     * 
     * @param object $objTemplate Template object \Cx\Core\Html\Sigma
     * @param array  $arguments   Array parameters
     * 
     * @return string
     */
    public function executeCommandWebsite($objTemplate, $arguments)
    {
        global $_ARRAYLANG;
        $objTemplate->setGlobalVariable($_ARRAYLANG);

        if (!self::isUserLoggedIn()) {
            return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_LOGIN_NOACCESS'];
        }

        $websiteId = isset($arguments['id']) ? contrexx_input2raw($arguments['id']) : '';
        if (empty($websiteId)) {
            return $_ARRAYLANG['TXT_MULTISITE_UNKOWN_WEBSITE'];
        }

        $websiteServiceRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
        $website = $websiteServiceRepo->findOneById($websiteId);
        if (!$website) {
            return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_EXISTS'];
        }
        if($website->getOwner()->getId() != \FWUser::getFWUserObject()->objUser->getId()){
            return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_MULTISITE_USER'];
        }

        //show the frontend
        $status = ($website->getStatus() == \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_ONLINE);
        $statusDisabled = ($website->getStatus() == \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_DISABLED);
        $isTrialWebsite = false;
        
        // check the website is trial or not
        $subscription = \Env::get('em')->getRepository('Cx\Modules\Order\Model\Entity\Subscription')->findOneBy(array('productEntityId' => $websiteId));
        if ($subscription && $objTemplate->blockExists('showUpgradeButton')) {
            $productEntity = $subscription->getProductEntity();
            $product = $subscription->getProduct();
            if ($productEntity instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Website && $product->getName() === 'Trial') {
                $isTrialWebsite = true;
            }
        }

        $objTemplate->setVariable(array(
            'MULTISITE_WEBSITE_FRONTEND_LINK'       => $this->getApiProtocol() . $website->getBaseDn()->getName(),
            'MULTISITE_WEBSITE_DELETE_REDIRECT_URL' => \Cx\Core\Routing\Url::fromModuleAndCmd('MultiSite', 'Subscription')->toString(),
            'MULTISITE_SUBSCRIPTION_ID'             => !\FWValidator::isEmpty($subscription) ? $subscription->getId() : ''
        ));
        self::showOrHideBlock($objTemplate, 'showWebsiteViewButton', $status);
        self::showOrHideBlock($objTemplate, 'showAdminButton', $status);
        self::showOrHideBlock($objTemplate, 'showUpgradeButton', $isTrialWebsite);
        //Show the Website Admin and Backend group users
        if ($objTemplate->blockExists('showWebsiteAdminUsers')) {
            $websiteAdminUsers = $website->getAdminUsers();
            $showOverview = array(
                    'id' => array(
                            'showOverview' => false,
                        ),
                    'username'=> array(
                            'header' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_TITLE_NAME'],
                            'table' => array(
                                'parse' => function($value, $arrData) {
                                    $objUser = new \Cx\Core_Modules\MultiSite\Model\Entity\User();
                                    $objUser->assignRandomUserId();
                                    $objUser->setProfile(array(
                                        'firstname' => array(0 => $arrData['userProfile']['firstname']),
                                        'lastname'  => array(0 => $arrData['userProfile']['lastname']),
                                    ));
                                    return \FWUser::getParsedUserTitle($objUser);
                                },
                            ),
                    ),
                    'email'=> array(
                              'header' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_TITLE_EMAIL']
                        ),
                    'isAdmin' => array(
                            'showOverview' => false,
                        ),
                    'password' => array(
                            'showOverview' => false,
                        ),
                    'authToken' => array(
                            'showOverview' => false,
                        ),
                    'authTokenTimeout' => array(
                            'showOverview' => false,
                        ),
                    'regdate' => array(
                            'showOverview' => false,
                        ),
                    'expiration' => array(
                            'showOverview' => false,
                        ),
                    'validity' => array(
                            'showOverview' => false,
                        ),
                    'lastAuth' => array(
                            'showOverview' => false,
                        ),
                    'lastAuthStatus' => array(
                            'showOverview' => false,
                        ),
                    'lastActivity' => array(
                            'showOverview' => false,
                        ),
                    'emailAccess' => array(
                            'showOverview' => false,
                        ),
                    'frontendLangId' => array(
                            'showOverview' => false,
                        ),
                    'backendLangId' => array(
                            'showOverview' => false,
                        ),
                    'active' => array(
                            'showOverview' => false,
                        ),
                    'verified' => array(
                            'showOverview' => false,
                        ),
                    'primaryGroup' => array(
                            'showOverview' => false,
                        ),
                    'profileAccess' => array(
                            'showOverview' => false,
                        ),
                    'restoreKey' => array(
                            'showOverview' => false,
                        ),
                    'restoreKeyTime' => array(
                            'showOverview' => false,
                        ),
                    'u2uActive' => array(
                            'showOverview' => false,
                        ),
                    'userProfile' => array(
                            'showOverview' => false,
                        ),
                    'group' => array(
                            'showOverview' => false,
                        ),
                );
            //display the admin users using viewgenerator
            if($websiteAdminUsers){
                $view = new \Cx\Core\Html\Controller\ViewGenerator(
                    $websiteAdminUsers,
                    array(
                        'array' => array(
                            'functions' => array(
                                'add' => false,
                                'edit' => false,
                                'delete' => false,
                                'sorting' => false,
                                'baseUrl' => '',
                                'actions' => function($rowData) {
                                    if ($rowData['email'] != \FWUser::getFWUserObject()->objUser->getEmail()) {
                                        return '<a class="entypo-tools" data-user_id= "' . $rowData['id'] . '" data-page= "Edit"></a>  '
                                                . '<a class="entypo-trash" data-user_id= "' . $rowData['id'] . '" data-page= "Delete"></a>';
                                    }
                                },
                            ),
                            'fields' => $showOverview,
                        ),
                    )
                );
                $objTemplate->setVariable('ADMIN_USERS', $view->render());
            }
        }
        
        //show section Domains if component NetManager is licensed on Website.
        $response = JsonMultiSiteController::executeCommandOnWebsite('isComponentLicensed', array('component' => 'NetManager'), $website);
        $showDomainSectionStatus = isset($response->status) && $response->status == 'success' && isset($response->data) && $response->data->status == 'success';
        self::showOrHideBlock($objTemplate, 'showDomainsSection', $showDomainSectionStatus);
        
        //show website base domain and domain aliases
        if ($showDomainSectionStatus && $objTemplate->blockExists('showWebsiteDomains')) {
            $resp = JsonMultiSiteController::executeCommandOnWebsite('getMainDomain', array(), $website);
            $mainDomainName = '';
            if ($resp->status == 'success' && $resp->data->status == 'success') {
                $mainDomainName = $resp->data->mainDomain;
            }
            $domains = array_merge(array($website->getBaseDn()), $website->getDomainAliases());
            foreach ($domains as $domain) {
                if ($domain->getType() == \Cx\Core_Modules\MultiSite\Model\Entity\Domain::TYPE_MAIL_DOMAIN ||
                    $domain->getType() == \Cx\Core_Modules\MultiSite\Model\Entity\Domain::TYPE_WEBMAIL_DOMAIN ) {
                    continue;
                }
                $isBaseDomain = $domain->getType() == \Cx\Core_Modules\MultiSite\Model\Entity\Domain::TYPE_BASE_DOMAIN;
                $domainId     = $isBaseDomain ? $domain->getId() : $domain->getCoreNetDomainId();
                $objTemplate->setVariable(array(
                        'MULTISITE_WEBSITE_DOMAIN'                    => contrexx_raw2xhtml(\Cx\Core\Net\Controller\ComponentController::convertIdnToUtf8Format($domain->getName())),
                        'MULTISITE_WEBSITE_DOMAIN_NAME'               => contrexx_raw2xhtml($domain->getNameWithPunycode()),
                        'MULTISITE_WEBSITE_DOMAIN_ID'                 => contrexx_raw2xhtml($domainId),
                        'MULTISITE_WEBSITE_MAIN_DOMAIN_RADIO_CHECKED' => ($domain->getName() === $mainDomainName) ? 'checked' : '',
                        'MULTISITE_WEBSITE_DOMAIN_SUBMIT_URL'         => '/api/MultiSite/Domain?action=Select&website_id=' . $website->getId() . '&domain_id=' . contrexx_raw2xhtml($domainId) . '&domain_name=' . contrexx_raw2xhtml($domain->getName())
                ));
                // hide the edit/delete icons if the domain is selected as main domain or  base domain.
                $domainActionStatus = !$statusDisabled ? ($domain->getName() !== $mainDomainName && !$isBaseDomain) : false;
                self::showOrHideBlock($objTemplate, 'showWebsiteDomainActions', $domainActionStatus);
                // hide the ssl certificate icon if it is a base domain.
                self::showOrHideBlock($objTemplate, 'showDomainWithSslCertificateAction', $domainActionStatus);
                // hide the spf icon if the domain is the base domain.
                $domainSpfStatus = !$statusDisabled ? (!$isBaseDomain) : false;
                self::showOrHideBlock($objTemplate, 'showWebsiteSpfAction', $domainSpfStatus);
                //hide the selection websiteMainDomain if the website is disabled
                self::showOrHideBlock($objTemplate, 'showWebsiteMainDomain', !$statusDisabled);
                $objTemplate->parse('showWebsiteDomains');
            }
        }
        
        //show the website's domain name
        if ($objTemplate->blockExists('showWebsiteDomainName')) {
            $domain = $website->getBaseDn();
            if ($domain) {
                $objTemplate->setVariable(array(
                    'MULTISITE_WEBSITE_DOMAIN_NAME' => contrexx_raw2xhtml($domain->getName()),
                ));
            }
        }
        
        //show the website's mail service enable|disable button
        $showMailService = false;
        if ($objTemplate->blockExists('activateMailService') && $objTemplate->blockExists('deactivateMailService')) {
            $mailServiceServerStatus = false;
            $additionalDataResp = JsonMultiSiteController::executeCommandOnWebsite('getModuleAdditionalData', array('moduleName' => 'MultiSite', 'additionalType' => 'Mail'), $website);
            $additionalData     = null;
            if ($additionalDataResp->status == 'success' && $additionalDataResp->data->status == 'success') {
                $additionalData = $additionalDataResp->data->additionalData;
            }

            $showMailService = (   !\FWValidator::isEmpty($additionalData)
                                && isset($additionalData->service)
                                && !\FWValidator::isEmpty($additionalData->service));
            if ($website->getMailServiceServer() && !\FWValidator::isEmpty($website->getMailAccountId())) {
                $response = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnManager('getMailServiceStatus', array('websiteId' => $websiteId));
                if (!\FWValidator::isEmpty($response)
                        && $response->status == 'success'
                        && $response->data->status == 'success'
                ) {
                    $mailServiceServerStatus = $response->data->mailServiceStatus;
                }
            }
            self::showOrHideBlock($objTemplate, 'deactivateMailService', $mailServiceServerStatus);
            self::showOrHideBlock($objTemplate, 'openAdministration', $mailServiceServerStatus);
            self::showOrHideBlock($objTemplate, 'activateMailService', !$mailServiceServerStatus);

            $objTemplate->setVariable('MULTISITE_WEBSITE_MAIL_SERVICE_STATUS', $statusDisabled ? ($mailServiceServerStatus
                                                                                                  ? $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_MAIL_SERVICE_ENABLED']
                                                                                                  : $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_MAIL_SERVICE_DISABLED']
                                                                                                 )
                                                                                               : '');

        }
        
        //show the website's resources
        if ($objTemplate->blockExists('showWebsiteResources')) {
            $resourceUsageStats = $website->getResourceUsageStats();
            $objTemplate->setVariable(array(
                'MULTISITE_WEBSITE_ADMIN_USERS_USAGE'   => $resourceUsageStats->accessAdminUser->usage,
                'MULTISITE_WEBSITE_ADMIN_USERS_QUOTA'   => $resourceUsageStats->accessAdminUser->quota,
                'MULTISITE_WEBSITE_CONTACT_FORMS_USAGE' => $resourceUsageStats->contactForm->usage,
                'MULTISITE_WEBSITE_CONTACT_FORMS_QUOTA' => $resourceUsageStats->contactForm->quota,
                'MULTISITE_WEBSITE_SHOP_PRODUCTS_USAGE' => $resourceUsageStats->shopProduct->usage,
                'MULTISITE_WEBSITE_SHOP_PRODUCTS_QUOTA' => $resourceUsageStats->shopProduct->quota,
                'MULTISITE_WEBSITE_CRM_CUSTOMERS_USAGE' => $resourceUsageStats->crmCustomer->usage,
                'MULTISITE_WEBSITE_CRM_CUSTOMERS_QUOTA' => $resourceUsageStats->crmCustomer->quota,
            ));
            $objTemplate->parse('showWebsiteResources');
        }
        $objTemplate->setGlobalVariable(array(
            'MULTISITE_WEBSITE_ID' => contrexx_raw2xhtml($websiteId)
        ));
        
        //hide the website info if the website is disabled
        self::showOrHideBlock($objTemplate, 'showWebsiteInfo', !$statusDisabled);
         //hide the website add user button if the website is disabled
        self::showOrHideBlock($objTemplate, 'showWebsiteAdminAddUser', !$statusDisabled);
        //hide the  add domain button if the website is disabled
        self::showOrHideBlock($objTemplate, 'showWebsiteAddDomain', !$statusDisabled);
        //hide the  website mail service if the website is disabled
        self::showOrHideBlock($objTemplate, 'showWebsiteMailService', !$statusDisabled);
         // show/hide the  website mail service
        self::showOrHideBlock($objTemplate, 'showMailServiceSection', $showMailService);
        
        return $objTemplate->get();
    }
    
    /**
     * Api Domain command
     * 
     * @param object $objTemplate Template object \Cx\Core\Html\Sigma
     * @param array  $arguments   Array parameters
     * 
     * @return string
     */
    public function executeCommandDomain($objTemplate, $arguments) {

        global $_ARRAYLANG;
        $objTemplate->setGlobalVariable($_ARRAYLANG);

        if (!self::isUserLoggedIn()) {
            return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_LOGIN_NOACCESS'];
        }

        $websiteId = isset($arguments['website_id']) ? contrexx_input2raw($arguments['website_id']) : '';
        if (empty($websiteId)) {
            return $_ARRAYLANG['TXT_MULTISITE_UNKOWN_WEBSITE'];
        }

        $websiteServiceRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
        $website = $websiteServiceRepo->findOneById($websiteId);
        if (!$website) {
            return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_EXISTS'];
        }

        if ($website->getOwner()->getId() != \FWUser::getFWUserObject()->objUser->getId()) {
            return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_MULTISITE_USER'];
        }
        
        $loadPageAction   = isset($arguments[1]) ? contrexx_input2raw($arguments[1]) : '';
        $submitFormAction = isset($arguments['action']) ? contrexx_input2raw($arguments['action']) : '';
        $domainId         = isset($arguments['domain_id']) ? contrexx_input2raw($arguments['domain_id']) : '';
        $domainName       = isset($arguments['domain_name']) ? contrexx_input2raw($arguments['domain_name']):'';
        
        //processing form values after submit
        if (!\FWValidator::isEmpty($submitFormAction)) {
            try {
                if (\FWValidator::isEmpty($domainId) && $submitFormAction != 'Add') {
                    return $this->parseJsonMessage($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_UNKNOWN'], false);
                }
                switch ($submitFormAction) {
                    case 'Add':
                        if (\FWValidator::isEmpty($_POST['add_domain'])) {
                            return $this->parseJsonMessage($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_UNKNOWN'], false);
                        }
                        $command = 'mapNetDomain';
                        $params = array(
                            'domainName' => $_POST['add_domain']
                        );
                        break;

                    case 'Edit':
                        if (\FWValidator::isEmpty($_POST['edit_domain'])) {
                            return $this->parseJsonMessage($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_UNKNOWN'], false);
                        }
                        $command = 'updateNetDomain';
                        $params = array(
                            'domainName' => $_POST['edit_domain'],
                            'domainId' => $domainId
                        );
                        break;

                    case 'Delete':
                        $command = 'unMapNetDomain';
                        $params = array(
                            'domainId' => $domainId
                        );
                        break;
                        
                    case 'Select':
                        $command = 'setMainDomain';
                        $params = array(
                          'mainDomainId'   => ($website->getBaseDn()->getName() === $domainName) ? 0 : $domainId
                        );
                        break;
                    
                    case 'Ssl':
                        $certificateName = $domainName;//isset($_POST['certificate_name']) ? contrexx_input2raw($_POST['certificate_name']) : '';
                        $privateKey      = isset($_POST['private_key']) ? contrexx_input2raw($_POST['private_key']) : '';
                        if (empty($certificateName) || empty($privateKey)) {
                            return $this->parseJsonMessage($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_SSL_FAILED'], false);
                        }
                        $command = 'linkSsl';
                        $params = array(
                            'websiteName'     => $website->getName(),
                            'domainName'      => $domainName,
                            'certificateName' => $certificateName,
                            'privateKey'      => $privateKey,
                            'certificate'     => isset($_POST['certificate']) ? contrexx_input2raw($_POST['certificate']) : '',
                            'caCertificate'   => isset($_POST['ca_certificate']) ? contrexx_input2raw($_POST['ca_certificate']) : ''
                        );
                        break;

                    default :
                        return $this->parseJsonMessage($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_UNKNOWN'], false);
                        break;
                }
                if (isset($command) && isset($params)) {
                    if ($submitFormAction == 'Ssl') {
                        $response = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnServiceServer($command, $params, $website->getWebsiteServiceServer());
                        $logPrefix = 'Service: '.$website->getWebsiteServiceServer()->getLabel();
                    } else {                    
                        $response = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnWebsite($command, $params, $website);
                        $logPrefix = 'Website: '.$website->getName();
                    }
                    if ($response && $response->status == 'success' && $response->data->status == 'success') {
                        $message = ($submitFormAction == 'Select')
                                    ? sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_'.strtoupper($submitFormAction).'_SUCCESS_MSG'], contrexx_raw2xhtml($domainName))
                                    : $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_'.strtoupper($submitFormAction).'_SUCCESS_MSG'];

                        return $this->parseJsonMessage($message, true);
                    } else {
\DBG::dump($response);
                        if (isset($response) && isset($response->data) && isset($response->data->log)) {
                            \DBG::appendLogs(array_map(function($logEntry, $prefix) {return '('.$prefix.') '.$logEntry;}, $response->data->log, array_fill(0, count($response->data->log), $logPrefix)));
                        }
                        return $this->parseJsonMessage($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_'.strtoupper($submitFormAction).'_FAILED'], false);
                    }
                }
            } catch (\Exception $e) {
                \DBG::log('Failed to '.$submitFormAction. 'Domain'. $e->getMessage());
                return $this->parseJsonMessage($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_'.strtoupper($submitFormAction).'_FAILED'], false);
            }
        } else {
            if(!empty($domainName) && !empty($domainId)){
                if (($loadPageAction == 'Delete') && $objTemplate->blockExists('showDeleteDomainInfo')) {
                    $objTemplate->setVariable(array(
                        'TXT_MULTISITE_DELETE_DOMAIN_INFO' => sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_REMOVE_INFO'], contrexx_raw2xhtml($domainName)),
                        'MULTISITE_DOMAIN_NAME' => contrexx_raw2xhtml($domainName),
                        'MULTISITE_WEBSITE_DOMAIN_ALIAS_ID' => contrexx_raw2xhtml($domainId)
                    ));
                }

                if (($loadPageAction == 'Edit') && $objTemplate->blockExists('showEditDomainName')) {
                    $objTemplate->setVariable(array(
                        'MULTISITE_DOMAIN_NAME' => contrexx_raw2xhtml($domainName),
                        'MULTISITE_WEBSITE_DOMAIN_ALIAS_ID' => contrexx_raw2xhtml($domainId)
                    ));
                }
                
                if (($loadPageAction == 'Ssl') && $objTemplate->blockExists('showSslCertificateForm')) {
                    $response = JsonMultiSiteController::executeCommandOnServiceServer(
                        'getDomainSslCertificate',
                        array(
                            'websiteName' => $website->getName(),
                            'domainName' => $domainName,
                        ),
                        $website->getWebsiteServiceServer()
                    );
                    $sslCertificate = ($response && $response->status == 'success' && $response->data->status == 'success') ? implode(', ', $response->data->sslCertificate) : '';
                    self::showOrHideBlock($objTemplate, 'showSslCertificate', $sslCertificate);
                    $objTemplate->setVariable(array(
                        'TXT_MULTISITE_DOMAIN_CERTIFICATE' => sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_DOMAIN_CERTIFICATE'], contrexx_raw2xhtml($sslCertificate)),
                    ));
                }
                
                if (($loadPageAction == 'Spf') && $objTemplate->blockExists('showSpfDomainInfo')) {
                    $mailServiceServerStatus = false;

                    if ($website->getMailServiceServer() && !\FWValidator::isEmpty($website->getMailAccountId())) {
                        $response = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnManager('getMailServiceStatus', array('websiteId' => $websiteId));
                        if (!\FWValidator::isEmpty($response)
                                && $response->status == 'success'
                                && $response->data->status == 'success'
                        ) {
                            $mailServiceServerStatus = $response->data->mailServiceStatus;
                        }
                    }
                    
                    $spfText = 'v=spf1 mx a mx:' . $website->getBaseDn()->getName();
                    if($mailServiceServerStatus){
                        $spfText .= ' mx:' . $website->getMailDn()->getName();
                    }
                    
                    $objTemplate->setVariable(array(
                        'TXT_MULTISITE_SPF_DOMAIN_INFO' => sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_SPF_INFO'], $domainName),
                        'TXT_MULTISITE_SPF_DOMAIN_INFO_RECORD' => sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_SPF_INFO_RECORD'], $domainName),
                        'TXT_MULTISITE_SPF_DOMAIN_RECORD' => contrexx_raw2xhtml($spfText),
                    ));
                }
            }

            $objTemplate->setVariable(array(
                'MULTISITE_WEBSITE_DOMAIN_SUBMIT_URL' => '/api/MultiSite/Domain?action=' . $loadPageAction . '&website_id=' . $websiteId . '&domain_id=' . $domainId . '&domain_name=' . $domainName,
            ));

            return $objTemplate->get();
        }
    }
    
    /**
     * Api Email command
     * 
     * @param object $objTemplate Template object \Cx\Core\Html\Sigma
     * @param array  $arguments   Array parameters
     * 
     * @return string
     */
    public function executeCommandEmail($objTemplate, $arguments) {

        global $_ARRAYLANG;
        $objTemplate->setGlobalVariable($_ARRAYLANG);

        if (!self::isUserLoggedIn()) {
            return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_LOGIN_NOACCESS'];
        }

        $websiteId = isset($arguments['website_id']) ? contrexx_input2raw($arguments['website_id']) : '';
        if (empty($websiteId)) {
            return $_ARRAYLANG['TXT_MULTISITE_UNKOWN_WEBSITE'];
        }

        $websiteServiceRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
        $website = $websiteServiceRepo->findOneById($websiteId);
        if (!$website) {
            return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_EXISTS'];
        }

        if ($website->getOwner()->getId() != \FWUser::getFWUserObject()->objUser->getId()) {
            return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_MULTISITE_USER'];
        }
        
        $loadPageAction   = isset($arguments[1]) ? contrexx_input2raw($arguments[1]) : '';
        $submitFormAction = isset($arguments['action']) ? contrexx_input2raw($arguments['action']) : '';
        $password         = isset($arguments['pwd']) ? contrexx_input2raw($arguments['pwd']) : '';

        //processing form values after submit
        if (!\FWValidator::isEmpty($submitFormAction)) {
            try {
                switch ($submitFormAction) {
                    case 'Edit':
                        $command = 'resetEmailPassword';
                        $params = array(
                            'websiteId' => $websiteId,
                        );
                        break;
                    
                    default :
                        return $this->parseJsonMessage($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_UNKNOWN'], false);
                        break;
                }
                if (isset($command) && isset($params)) {
                    $response = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnManager($command, $params);
                    if ($response && $response->status == 'success' && $response->data->status == 'success') {
                        return $this->parseJsonMessage(array('message' => $response->data->message, 'pwd' => $response->data->password), true);
                    } else {
                        return $this->parseJsonMessage($response->data->message, false);
                    }
                }
            } catch (\Exception $e) {
                \DBG::log('Failed to '.$submitFormAction. ' E-Mail'. $e->getMessage());
                return $this->parseJsonMessage($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_'.strtoupper($submitFormAction).'_FAILED'], false);
            }
        } else {
            if (($loadPageAction == 'Edit') && $objTemplate->blockExists('showEditEmailName')) {
                $domains = array_merge(array($website->getBaseDn()), $website->getDomainAliases());
                foreach ($domains as $key => $val) {
                    $domains[$key] = 'info@' . \Cx\Core\Net\Controller\ComponentController::convertIdnToUtf8Format($val->getName());
                }
                $objTemplate->setVariable(array(
                    'MULTISITE_EMAIL_USERNAME' => 'info@' . $website->getMailDn()->getName(),
                    'MULTISITE_EMAIL_SERVER' => $website->getMailDn()->getName(),
                    'MULTISITE_EMAIL_WEBMAIL' => $website->getWebmailDn()->getName(),
                    'MULTISITE_EMAIL_PASSWORD' => empty($password)?'********':base64_decode($password),
                    'MULTISITE_WEBSITE_ID' => contrexx_raw2xhtml($website->getId()),
                    'MULTISITE_EMAIL_ALIAS'=> implode('<br />', $domains),
                ));
            }
            $objTemplate->setVariable(array(
                'MULTISITE_WEBSITE_EMAIL_SUBMIT_URL' => '/api/MultiSite/Email?action=' . $loadPageAction . '&website_id=' . $websiteId,
            ));

            return $objTemplate->get();
        }
    }
    
    /**
     * Api command Admin
     * 
     * @param object $objTemplate Template object \Cx\Core\Html\Sigma
     * @param array  $arguments   Array parameters
     * 
     * @return string
     */
    public function executeCommandAdmin($objTemplate, $arguments)
    {
        global $objInit, $_CORELANG, $_ARRAYLANG;

        $coreLangData   = $objInit->loadLanguageData('core');
        $accessLangData = $objInit->loadLanguageData('Access');
        $_CORELANG = $_ARRAYLANG = array_merge($_ARRAYLANG, $coreLangData, $accessLangData );
        $objTemplate->setGlobalVariable($_ARRAYLANG);
        if (!self::isUserLoggedIn()) {
            return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_LOGIN_NOACCESS'];
        }

        $objUser   = \FWUser::getFWUserObject()->objUser;
        $websiteId = isset($arguments['website_id']) ? contrexx_input2raw($arguments['website_id']) : 0;
        $userId    = isset($arguments['user_id']) ? contrexx_input2raw($arguments['user_id']) : 0;

        if (\FWValidator::isEmpty($websiteId)) {
            return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_EXISTS'];
        }

        $website = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website')->findOneById($websiteId);
        if (!$website) {
            return $_ARRAYLANG['TXT_MULTISITE_UNKOWN_WEBSITE'];
        }

        if ($website->getOwner()->getId() != $objUser->getId()) {
            return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_MULTISITE_USER'];
        }

        $loadPageAction   = isset($arguments[1]) ? contrexx_input2raw($arguments[1]) : '';
        $submitFormAction = isset($arguments['action']) ? contrexx_input2raw($arguments['action']) : '';

        if (!\FWValidator::isEmpty($submitFormAction)) {
            try {
                if (\FWValidator::isEmpty($userId) && $submitFormAction != 'Add') {
                    return $this->parseJsonMessage($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_UNKOWN_USER_REQUEST'], false);
                }

                $successMsg = $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADMIN_USER_' . strtoupper($submitFormAction) . '_SUCCESS'];
                $errorMsg   = $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADMIN_USER_' . strtoupper($submitFormAction) . '_FAILED'];
                $params     = array();
                switch ($submitFormAction) {

                    case 'Edit':
                        $command                                                    = 'updateUser';
                        $params['websiteUserId']                                    = $_POST['adminUser']['id'];
                    case 'Add' :
                        $email = isset($_POST['adminUser']['email']) ? contrexx_input2raw($_POST['adminUser']['email']) : '';
                        if (\FWValidator::isEmpty($email) || !\FWValidator::isEmail($email)) {
                            return $this->parseJsonMessage($_ARRAYLANG['TXT_ACCESS_INVALID_ENTERED_EMAIL_ADDRESS'], false);
                        }
                        if(!isset($command)) {
                            $command                                                = 'createAdminUser';
                        }
                        
                        $params['multisite_user_account_email']                     = $_POST['adminUser']['email'];
                        $params['multisite_user_account_password']                  = contrexx_input2raw($_POST['adminUser']['password']);
                        $params['multisite_user_account_password_confirmed']        = contrexx_input2raw($_POST['adminUser']['confirm_password']);
                        $params['multisite_user_profile_attribute']                 = array(
                            'lastname'  => array(contrexx_input2raw($_POST['adminUser']['userProfile']['lastname'])),
                            'firstname' => array(contrexx_input2raw($_POST['adminUser']['userProfile']['firstname']))
                        );
                        break;
                    case 'Delete':
                        $command = 'removeUser';
                        $params  = array('userId' => $userId);
                        break;
                    default:
                        return $this->parseJsonMessage($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_UNKOWN_USER_REQUEST'], false);
                        break;
                }
                if (isset($command) && isset($params)) {
                    $response = JsonMultiSiteController::executeCommandOnWebsite($command, $params, $website);
                    if ($response && $response->status == 'success' && $response->data->status == 'success') {
                        return $this->parseJsonMessage($successMsg, true);
                    } else {
                        $message = $response->message;
                        if (is_object($response->message) && \FWValidator::isEmpty($message)) {
                            $message = $response->message->message;
                        } else if(is_object($response->data) && \FWValidator::isEmpty($message)) {
                            $message = $response->data->message;
                        }
                        return $this->parseJsonMessage($message, false);
                    }
                }
            } catch (\Exception $e) {
                \DBG::log('Failed to ' . $submitFormAction . 'administrator account' . $e->getMessage());
                return $this->parseJsonMessage($errorMsg, false);
            }
        } else {

            if (!\FWValidator::isEmpty($userId) && $loadPageAction != 'Add') {
                //get admin user from website by id
                $adminUser = current($website->getUser($userId));
                if (\FWValidator::isEmpty($adminUser)) {
                    return $_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_MULTISITE_USER'];
                }
            }

            if ($objTemplate->blockExists('showEditAdminUser')) {
                $objTemplate->setVariable(array(
                    'MULTISITE_ADMIN_USER_FIRSTNAME'=> $adminUser->getUserProfile()->getFirstname(),
                    'MULTISITE_ADMIN_USER_LASTNAME' => $adminUser->getUserProfile()->getLastname(),
                    'MULTISITE_ADMIN_USER_EMAIL'    => $adminUser->getEmail(),
                    'MULTISITE_ADMIN_USER_ID'       => $adminUser->getId()
                ));
            }

            if ($objTemplate->blockExists('showDeleteAdminUser')) {
                $objTemplate->setVariable(array(
                    'MULTISITE_ADMIN_USER_DELETE_CONFIRM' => sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADMIN_USER_DELETE_CONFIRM'], $adminUser->getEmail()),
                    'MULTISITE_ADMIN_USER_ID' => $adminUser->getId()
                ));
            }
            $objTemplate->setVariable(array(
                'MULTISITE_ADMIN_USER_SUBMIT_URL' => '/api/MultiSite/Admin?action=' . $loadPageAction . '&website_id=' . $websiteId . '&user_id=' . $userId,
            ));
        }
        return $objTemplate->get();
    }

    /**
     * Api Payrexx command
     */
    public function executeCommandPayrexx()
    {
        // use file-logging to debug payrexx web-hooks
        \DBG::deactivate();
        \DBG::activate(DBG_LOG_FILE | DBG_PHP | DBG_LOG);
        \DBG::dump($_REQUEST);
        $transaction = isset($_POST['transaction'])
                       ? $_POST['transaction']
                       : (isset($_POST['subscription'])
                           ? $_POST['subscription']
                           : array());
        $invoice = isset($transaction['invoice']) ? $transaction['invoice'] : array();
        $contact = isset($transaction['contact']) ? $transaction['contact'] : array();
        $hasTransaction = isset($_POST['transaction']);
        
        if (
               \FWValidator::isEmpty($transaction)
            || \FWValidator::isEmpty($invoice)
            || \FWValidator::isEmpty($contact)
        ) {
            \DBG::msg(__METHOD__.': insufficient data supplied. Abort execution.');
            return;
        }
        
        //For cancelling the subscription
        $subscriptionId     = $hasTransaction ? (isset($transaction['subscription']) ? $transaction['subscription']['id'] : '')  : $transaction['id'];
        $subscriptionStatus = $hasTransaction ? (isset($transaction['subscription']) ? $transaction['subscription']['status'] : '')  : $transaction['status'];
        $subscriptionEnd    = $hasTransaction
                                ? (isset($transaction['subscription']) && isset($transaction['subscription']['end'])
                                     ? $transaction['subscription']['end']
                                     : ''
                                  )
                                : (isset($transaction['end']) ? $transaction['end'] : '');
        
        if (   !\FWValidator::isEmpty($subscriptionId)
            && !\FWValidator::isEmpty($subscriptionEnd)
            && $subscriptionStatus === \Cx\Modules\Order\Model\Entity\Subscription::STATE_CANCELLED
        ) {
            \DBG::msg(__METHOD__.': subscription has been cancelled');
            $subscriptionRepo = \Env::get('em')->getRepository('Cx\Modules\Order\Model\Entity\Subscription');
            $subscription = $subscriptionRepo->findOneBy(array('externalSubscriptionId' => $subscriptionId));
            if (!\FWValidator::isEmpty($subscription)) {
                // TO-DO:check the payrexx account to confirm whether the subscription is cancelled
                
                $subscription->setExpirationDate(new \DateTime($subscriptionEnd));
                $subscription->setState(\Cx\Modules\Order\Model\Entity\Subscription::STATE_CANCELLED);
                \Env::get('em')->flush();
                return;
            }
        }
        
        // register placed payment
        $invoiceReferId = isset($invoice['referenceId']) ? $invoice['referenceId'] : '';
        $invoiceId      = isset($invoice['paymentRequestId']) ? $invoice['paymentRequestId'] : 0;
        if (\FWValidator::isEmpty($invoiceReferId) || \FWValidator::isEmpty($invoiceId)) {
            \DBG::msg(__METHOD__.': unkown payment. Abort execution.');
            return;
        }
        
        $instanceName  = \Cx\Core\Setting\Controller\Setting::getValue('payrexxAccount','MultiSite');
        $apiSecret     = \Cx\Core\Setting\Controller\Setting::getValue('payrexxApiSecret','MultiSite');

        $payrexx = new \Payrexx\Payrexx($instanceName, $apiSecret);

        $invoiceRequest = new \Payrexx\Models\Request\Invoice();
        $invoiceRequest->setId($invoiceId);

        try {
            $response = $payrexx->getOne($invoiceRequest);
        } catch (\Payrexx\PayrexxException $e) {
            \DBG::msg(__METHOD__.': Fetching payment response failed: ' . $e->getMessage());
            throw new MultiSiteException("Failed to get payment response:". $e->getMessage());
        }
        
        if (   isset($transaction['status']) && ($transaction['status'] === 'confirmed')
            && !\FWValidator::isEmpty($response)
            && $response instanceof \Payrexx\Models\Response\Invoice
            && $response->getStatus() == 'confirmed'
            && $invoice['amount'] == ($response->getAmount() / 100)
            && $invoice['referenceId'] == $response->getReferenceId()
        ) {
            $transactionReference = $invoiceReferId . (!\FWValidator::isEmpty($subscriptionId) ? "$subscriptionId|" : '');
            self::createPayrexxPayment($transactionReference, $invoice['amount'], $transaction);
        }

        \DBG::msg(__METHOD__.': End of command execution reached. Bye..');
    }
    
    /**
     * Api Backup command
     */
    public function executeCommandBackup($arguments)
    {
        global $_ARRAYLANG;
        
        try {
            if (   !isset($arguments['websiteId'])
                && !isset($arguments['serviceServerId'])
            ) {
                throw new MultiSiteException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_PARAMS']);
            }
            
            $websiteId         = isset($arguments['websiteId'])
                                 ? contrexx_input2int($arguments['websiteId'])
                                 : 0;
            $serviceServerId   = isset($arguments['serviceServerId'])
                                 ? contrexx_input2int($arguments['serviceServerId'])
                                 : 0;
            $backupLocation    = isset($arguments['backupLocation'])
                                 ? contrexx_input2raw($arguments['backupLocation'])
                                 : '';
            $responseType      = isset($arguments['responseType'])
                                 ? contrexx_input2raw($arguments['responseType'])
                                 : '';
            
            $params = array();
            if (!empty($websiteId)) {
                $websiteRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
                $website     = $websiteRepo->findOneById($websiteId);
                if (!$website) {
                    throw new MultiSiteException($_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_EXISTS']);
                }
                $params['websiteId']  = $websiteId;
            }
            
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode', 'MultiSite')) {
                case ComponentController::MODE_MANAGER:
                    $websiteServiceServer = !empty($website)
                                            ? $website->getWebsiteServiceServer()
                                            : self::getServiceServerByCriteria(array('id' => $serviceServerId));
                    if (!$websiteServiceServer) {
                        $this->cx->getEvents()->triggerEvent('SysLog/Add', array(
                            'severity' => 'WARNING',
                            'message'  => \sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_NOT_EXISTS'], $website->getName()),
                            'data'     => ' ',
                        ));
                        throw new MultiSiteException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_SERVICE_SERVER']);
                    }
                    $params['serviceServerId'] = $websiteServiceServer->getId();
                    break;
                default:
                    break;
            
            }
            
            $params['backupLocation'] = $backupLocation;
            $resp = JsonMultiSiteController::executeCommandOnManager('websiteBackup', $params);
            return $responseType == 'json' ? $resp : ($resp->status == 'success' ? $resp->data->messsage : $resp->message);

        } catch (\Exception $e) {
            \DBG::log(__METHOD__.' Failed! : '. $e->getMessage());
            return $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_FAILED'];
        }
    }
    
    /**
     * Api Restore command
     */
    public function executeCommandRestore($arguments)
    {
        global $_ARRAYLANG;
        
        try {
            if (   !isset($arguments['restoreWebsiteName'])
                || !isset($arguments['restoreOnServiceServer'])
            ) {
                throw new MultiSiteException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_PARAMS']);
            }
            
            // Restore the selected backupfile to the new website name
            $restoreWebsiteName       = isset($arguments['restoreWebsiteName'])
                                        ? contrexx_input2raw($arguments['restoreWebsiteName'])
                                        : '';
            
            // Restore the website to selected service server / destination service server for restore
            $restoreServiceServerId   = isset($arguments['restoreOnServiceServer'])
                                        ? contrexx_input2int($arguments['restoreOnServiceServer'])
                                        : 0;
            
            // Backuped website file name
            $backupedWebsiteFileName  = isset($arguments['websiteBackupFileName'])
                                        ? contrexx_input2raw($arguments['websiteBackupFileName'])
                                        : '';
            
            // Backuped website zip file in the Service server
            $backupedServiceServerId  = isset($arguments['backupedServiceServer'])
                                        ? contrexx_input2int($arguments['backupedServiceServer'])
                                        : 0;
            
            // Uploaded Zip File path
            $uploadedBackupFilePath   = isset($arguments['uploadedFilePath'])
                                        ? contrexx_input2raw(($arguments['uploadedFilePath']))
                                        : '';
            
            // Selected user Id for restore a website
            $selectedUserId           = isset($arguments['selectedUserId'])
                                        ? contrexx_input2int(($arguments['selectedUserId']))
                                        : 0;
            
            // Selected Subscription Id for restore a website
            $subscriptionId           = isset($arguments['subscriptionId'])
                                        ? contrexx_input2int(($arguments['subscriptionId']))
                                        : 0;
            
            $responseType             = isset($arguments['responseType'])
                                        ? contrexx_input2raw($arguments['responseType'])
                                        : '';
            
            $websiteRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
            $website     = $websiteRepo->findOneBy(array('name' => $restoreWebsiteName));
            if ($website) {
                $this->cx->getEvents()->triggerEvent('SysLog/Add', array(
                    'severity'=> 'WARNING',
                    'message' => sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_ALREADY_EXISTS'], $restoreWebsiteName),
                    'data'    => ' '
                ));
                
                throw new MultiSiteException(\sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_ALREADY_EXISTS'], $restoreWebsiteName));
            }
            
            if (empty($backupedWebsiteFileName) && empty($uploadedBackupFilePath)) {
                throw new MultiSiteException('Website Backup file name is empty.');
            }
            
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode', 'MultiSite')) {
                case ComponentController::MODE_MANAGER:
                    $restoreInServiceServer = self::getServiceServerByCriteria(array('id' => $restoreServiceServerId));
                    if (!$restoreInServiceServer) {
                        throw new MultiSiteException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_SERVICE_SERVER']);
                    }

                    if (empty($uploadedBackupFilePath)) {
                        /*If the backuped service server is differ from destination service server, copy a file from
                          backuped  service server to destination service server*/
                        if (   !empty($backupedServiceServerId)
                            && $backupedServiceServerId != $restoreServiceServerId
                        ) {
                            $backupedServiceServer = self::getServiceServerByCriteria(array('id' => $backupedServiceServerId));
                            if (!$backupedServiceServer) {
                                throw new MultiSiteException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_SERVICE_SERVER']);
                            }

                            $backupServiceServerParams = array(
                                'backupFileName'  => $backupedWebsiteFileName,
                                'serviceServerId' => $restoreInServiceServer->getId()
                            );

                            //Copy a file from  $backupedServiceServer to $restoreInServiceServer
                            $response = JsonMultiSiteController::executeCommandOnServiceServer('sendFileToRemoteServer', $backupServiceServerParams, $backupedServiceServer);
                            if (!$response || $response->status == 'error' || $response->data->status == 'error') {
                                throw new MultiSiteException('Failed to copy/move a file to '.$restoreInServiceServer->getHostName());
                            }
                        }
                    } else {
                        $this->moveUploadedFileToServiceOnRestore($uploadedBackupFilePath, $restoreInServiceServer);
                    }
                default:
                    break;
            }
            
            $params = array(
                'websiteName'           => $restoreWebsiteName,
                'websiteBackupFileName' => !empty($uploadedBackupFilePath)
                                           ? basename($uploadedBackupFilePath)
                                           : $backupedWebsiteFileName,
                'serviceServerId'       => $restoreServiceServerId,
                'selectedUserId'        => $selectedUserId,
                'subscriptionId'        => $subscriptionId
            );
            $resp = JsonMultiSiteController::executeCommandOnManager('websiteRestore', $params);
            if (isset($resp->log)) {
                \DBG::appendLogs(
                    array_map(
                        function($logEntry) {
                            return '(Website: '.$website->getName().') '.$logEntry;
                        },
                        $resp->log
                    )
                );
            }
            return $responseType == 'json' ? $resp : ($resp->status == 'success' ? $resp->data->messsage : $resp->message);
            
        } catch (\Exception $e) {
            \DBG::log(__METHOD__ .' failed! : '. $e->getMessage());
            return $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_RESTORE_FAILED'];
        }
    }
    
    /**
     * Move UploadedFile To Service On Restore
     * 
     * @param string                                                        $filePath             uploaded file path
     * @param \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer  $websiteServiceServer $websiteServiceServerObj
     * 
     * @throws MultiSiteException
     */
    public function moveUploadedFileToServiceOnRestore($filePath, \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer $websiteServiceServer) 
    {
        global $_ARRAYLANG;
        
        if (   empty($filePath)
            || empty($websiteServiceServer)
        ) {
            throw new MultiSiteException(__METHOD__.' : failed!. '.$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_PARAMS']);
        }
        
        \Cx\Lib\FileSystem\FileSystem::path_absolute_to_os_root($filePath);
        
        try {
            if (!\Cx\Lib\FileSystem\FileSystem::exists($filePath)) {
                throw new MultiSiteException('Uploaded Backup File doesnot exists!');
            }

            //Copy a backup file to selected service server
            $resp = JsonMultiSiteController::executeCommandOnServiceServer('sendFileToRemoteServer', array('destinationServer' => $websiteServiceServer->getId()), $websiteServiceServer, array($filePath));
            if (!$resp || $resp->status == 'error' || $resp->data->status == 'error') {
                throw new MultiSiteException('Failed to send a file to '.$websiteServiceServer->getHostname());
            }

            //cleanup temp dir
            if (   \Cx\Lib\FileSystem\FileSystem::exists($filePath)
                && !\Cx\Lib\FileSystem\FileSystem::delete_file($filePath)
            ) {
                throw new MultiSiteException('Unable to delete the file: '. $filePath);
            }

        } catch (\Exception $e) {
            throw new MultiSiteException(__METHOD__ .' failed! : '. $e->getMessage());
        }
    }
    
    /**
     * Api Cron command
     */
    public function executeCommandCron()
    {
        $cron = $this->getController('Cron');
        $cron->sendNotificationMails();
    }
    
    public function executeCommandList($arguments) {
        $em = $this->cx->getDb()->getEntityManager();
        $multiSiteRepo = $em->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
        $websites = $multiSiteRepo->findAll();
        
        foreach ($websites as $website) {
            if (
                (!isset($arguments[1]) || $arguments[1] != '--all') &&
                $website->getStatus() != \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_ONLINE
            ) {
                continue;
            }
            echo $website->getName() . "\n";
        }
    }

    public function executeCommandExec($arguments) {
        if (!isset($arguments[1])) {
            echo 'No valid PHP file supplied' . PHP_EOL;
            return;
        }

        $file = $arguments[1];
        if (!file_exists($file)) {
            echo 'Unable to locate file ' . $file . PHP_EOL;
            return;
        }

        echo 'Going to parse file ' . $file . PHP_EOL;
        echo str_repeat('#', 80) . PHP_EOL;
        if (!(include $file)) {
            echo 'Failed to include file ' . $file . PHP_EOL;
            return;
        }

        echo PHP_EOL . str_repeat('#', 80) . PHP_EOL;
    }

    public function executeCommandCache($arguments) {
        global $_CONFIG;

        if ($arguments[1] != 'flush') {
            return;
        }

        $pattern = '';
        if (!empty($arguments[2])) {
            $pattern = $arguments[2];
        }

        $verbose = false;
        if (in_array('-v', $arguments)) {
            $verbose = true;
        }

        $dryRun = true;
        if (in_array('--exec', $arguments)) {
            $dryRun = false;
        }

        if (
            !isset($_CONFIG['cacheDbStatus']) ||
            $_CONFIG['cacheDbStatus'] != 'on'
        ) {
            return;
        }

        if (!isset($_CONFIG['cacheUserCache'])) {
            return;
        }

        switch ($_CONFIG['cacheUserCache']) {
            case \Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_MEMCACHED:
                // load stored memcached configuration

                // workaround as h1 does not yet have the new cache config option
                if (empty($_CONFIG['cacheUserCacheMemcachedConfig'])) {
                    $_CONFIG['cacheUserCacheMemcachedConfig'] = $_CONFIG['cacheUserCacheMemcacheConfig'];
                }

                if (empty($_CONFIG['cacheUserCacheMemcachedConfig'])) {
                    return;
                }

                $settings = json_decode($_CONFIG['cacheUserCacheMemcachedConfig'], true);
                $ip = $settings['ip'];
                $port = $settings['port'];

                $memcachedConfiguration = array('ip' => $ip, 'port' => $port);

                // verify that memcached is installed
                if (!extension_loaded('memcached')) {
                    dl('memcached');
                    break;
                }

                // verify that memcached is loaded
                if (!class_exists('\Memcached', false)) {
                    break;
                }

                // connect to memcached server
                $memcached = new \Memcached();
                if (!@$memcached->addServer($memcachedConfiguration['ip'], $memcachedConfiguration['port'])) {
                    break;
                }

                // flush cache
                $keys = $memcached->getAllKeys();
                $n = 0;
                foreach ($keys as $key) {
                    if (
                        !empty($pattern) &&
                        !preg_match('/' . $pattern . '/', $key)
                    ) {
                        continue;
                    }

                    if ($verbose) {
                        echo "flush $key\n";
                    }

                    if (!$dryRun) {
                        $memcached->delete($key);
                    }
                    $n++;
                }

                if ($dryRun) {
                    echo 'Dry-run: nothing has been flushed yet' . "\n";
                    echo $n . ' keys would have been dropped from Memcached' . "\n";
                    echo 'Call command with argument --exec to perform the flush command' . "\n";
                } else {
                    echo $n . ' keys dropped from Memcached' . "\n";
                }
                break;

            default:
                break;
        }
    }

    /**
     * Create new payment (handler Payrexx)
     * 
     * @param string $transactionReference
     * @param string $amount
     * @param array  $transactionData
     * 
     * @return null
     */
    public static function createPayrexxPayment($transactionReference, $amount, $transactionData)
    {
        if (\FWValidator::isEmpty($transactionReference) || \FWValidator::isEmpty($amount) || \FWValidator::isEmpty($transactionData)) {
            \DBG::msg(__METHOD__.': insufficient data available to create a new payment');
            return;
        }

        \DBG::msg(__METHOD__.': add new payment');
        
        $payment = new \Cx\Modules\Order\Model\Entity\Payment();
        $payment->setHandler(\Cx\Modules\Order\Model\Entity\Payment::HANDLER_PAYREXX);
        $payment->setAmount($amount);
        $payment->setTransactionReference($transactionReference);
        $payment->setTransactionData($transactionData);
        \Env::get('em')->persist($payment);
        \Env::get('em')->flush();
    }
    
    /**
     * Create new website into the existing subscription
     * 
     * @param integer $subscriptionId  Subscription id
     * @param string  $websiteName     Name of the website
     * @param \User   $userObj         userObj
     * @param integer $serviceServerId serviceServerId
     * 
     * return array return's array that contains array('status' => success | error, 'message' => 'Status message')
     */
    public function createNewWebsiteInSubscription($subscriptionId, $websiteName, $userObj = null, $serviceServerId = 0)
    {
        global $_ARRAYLANG;
        
        try {
            $subscriptionRepo = \Env::get('em')->getRepository('Cx\Modules\Order\Model\Entity\Subscription');
            $subscriptionObj = $subscriptionRepo->findOneBy(array('id' => $subscriptionId));

            //check the subscription is exist
            if (!$subscriptionObj) {
                return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_SUBSCRIPTION_NOT_EXISTS']);
            }

            //get sign-in user crm id!
            $objUser = ($userObj instanceof \User)
                       ? $userObj
                       : \FWUser::getFWUserObject()->objUser;
            
            if (!$objUser) {
                return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_MULTISITE_USER']);
            }
            
            $crmContactId = $objUser->getCrmUserId();
            if (empty($crmContactId)) {
               return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_MULTISITE_USER']);
            }

            $order = $subscriptionObj->getOrder();
            if (!$order) {
                return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_ORDER_NOT_EXISTS']);
            }

            //Verify the owner of the associated Order of the Subscription is actually owned by the currently sign-in user
            if ($crmContactId != $order->getContactId()) {
                return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_MULTISITE_USER']);
            }

            //get website collections
            $websiteCollection = $subscriptionObj->getProductEntity();
            if ($websiteCollection instanceof \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteCollection) {
                if ($websiteCollection->getQuota() <= count($websiteCollection->getWebsites())) {
                    return array('status' => 'error', 'message' => sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MAXIMUM_QUOTA_REACHED'], $websiteCollection->getQuota()));
                }
                //create new website object and add to website
                $website = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website')->initWebsite($websiteName, $objUser, 0, $serviceServerId);
                $websiteCollection->addWebsite($website);
                \Env::get('em')->persist($website);
                // flush $website to database -> subscription will need the ID of $website
                // to properly work
                \Env::get('em')->flush();

                $product = $subscriptionObj->getProduct();
                //check the product
                if (!$product) {
                    return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_PRODUCT_NOT_EXISTS']);
                }
                $productEntityAttributes = $product->getEntityAttributes();
                //pass the website template value
                $options = array(
                    'websiteTemplate'   => $productEntityAttributes['websiteTemplate'],
                    'initialSignUp'     => false,
                );

                if ($subscriptionObj->getExpirationDate()) {
                    $options['subscriptionExpiration'] = $subscriptionObj->getExpirationDate()->getTimestamp();
                }

                //website setup process
                try {
                    $websiteStatus = $website->setup($options);
                    if ($websiteStatus['status'] == 'success') {
                        return array('status' => 'success', 'message' => array('message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADD_WEBSITE_SUCCESS'], 'websiteId' => $website->getId()), 'reload' => (isset($_GET['page_reload']) && $_GET['page_reload'] == 'reload_page' ? true : false));
                    }

                    throw new \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteException('Website setup process not successful');
                } catch (\Cx\Core_Modules\MultiSite\Model\Entity\WebsiteException $e) {
                    return array(
                        'status' => 'error',
                        'message'=> $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADD_WEBSITE_FAILED'],
                        'log'    => \DBG::getMemoryLogs(),
                    );
                }
            }
        } catch (\Exception $e) {
            \DBG::log("Failed to add website:" . $e->getMessage());
            return array(
                'status' => 'error',
                'message'=> $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADD_WEBSITE_FAILED'],
                'log'    => \DBG::getMemoryLogs(),
            );
        }
    }
    
    /**
     * Create new website based on the given product id and website name
     * 
     * @param integer $productId       Product id
     * @param string  $websiteName     Website name
     * @param \User   $userObj         userObj
     * @param integer $serviceServerId serviceServerId
     * @param string  $renewalOption   renewalOption
     * 
     * return array return's array that contains array('status' => success | error, 'message' => 'Status message')
     */
    public function createNewWebsiteByProduct($productId, $websiteName, $userObj = null, $serviceServerId = 0, $renewalOption)
    {
        global $_ARRAYLANG;
        
        try {
            $productRepository = \Env::get('em')->getRepository('Cx\Modules\Pim\Model\Entity\Product');
            $product = $productRepository->findOneBy(array('id' => $productId));
            
            if (!$product) {
                return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_PRODUCT_NOT_EXISTS']);
            }
            
            $objUser = ($userObj instanceof \User)
                       ? $userObj
                       : \FWUser::getFWUserObject()->objUser;
            
            if (!$objUser) {
                return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_MULTISITE_USER']);
            }
            
            //get sign-in user crm id!
            $crmContactId = $objUser->getCrmUserId();

            if (empty($crmContactId)) {
               return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_MULTISITE_USER']);
            }
            
            list($renewalUnit, $renewalQuantifier) = JsonMultiSiteController::getProductRenewalUnitAndQuantifier($renewalOption);
            // create new subscription of selected product
            $subscriptionOptions = array(
                'renewalUnit'       => $renewalUnit,
                'renewalQuantifier' => $renewalQuantifier,
                'websiteName'       => $websiteName,
                'customer'          => $objUser,
                'serviceServerId'   => $serviceServerId
            );
            
            $transactionReference = "|$productId|name|$websiteName|";
            $currency = self::getUserCurrency($crmContactId);
            $order = \Env::get('em')->getRepository('Cx\Modules\Order\Model\Entity\Order')->createOrder($productId, $currency, $objUser, $transactionReference, $subscriptionOptions);
            if (!$order) {
                return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ORDER_FAILED']);
            }
            
            // create the website process in the payComplete event
            $order->complete();
            $websiteRepo = \Env::get('em')->getRepository('\Cx\Core_Modules\MultiSite\Model\Entity\Website');
            $website = $websiteRepo->findOneBy(array('name' => $websiteName));
            if (!\FWValidator::isEmpty($website)) {
                return array(
                    'status' => 'success',
                    'message' => array(
                        'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADD_WEBSITE_SUCCESS'],
                        'websiteId' => $website->getId()
                    ),
                    'reload' => (isset($_GET['page_reload']) && $_GET['page_reload'] == 'reload_page' ? true : false),
                    'log'    => \DBG::getMemoryLogs(),
                );
            }
            
            return array(
                'status' => 'error',
                'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADD_WEBSITE_FAILED'],
                'log'    => \DBG::getMemoryLogs(),
            );
        } catch (Exception $e) {
            \DBG::log("Failed to add website:" . $e->getMessage());
            return array(
                'status' => 'error',
                'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADD_WEBSITE_FAILED'],
                'log'    => \DBG::getMemoryLogs(),
            );
        }
    }
    
    /**
     * Parse the website details to the view page
     * 
     * @param \Cx\Core\Html\Sigma $objTemplate                         Template object
     * @param \Cx\Core_Modules\MultiSite\Model\Entity\Website $website website object
     */
    public function parseWebsiteDetails(\Cx\Core\Html\Sigma $objTemplate, \Cx\Core_Modules\MultiSite\Model\Entity\Website $website, $demandedStatus='')
    {
        $userId = \FWUser::getFWUserObject()->objUser->getId();

        $websiteInitialStatus = array(
            \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_INIT,
            \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_SETUP,
        );
        
        $status = ($website->getStatus() == \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_ONLINE);

        if($demandedStatus == '' || $demandedStatus == $website->getStatus()){
            $websiteBaseDn = $website->getBaseDn();
            $objTemplate->setVariable(array(
                'MULTISITE_WEBSITE_NAME'          => contrexx_raw2xhtml($website->getName()).self::getWebsiteNonOnlineStateAsLiteral($website),
                'MULTISITE_WEBSITE_ID'            => contrexx_raw2xhtml($website->getId()),
                'MULTISITE_WEBSITE_LINK'          => $websiteBaseDn ? contrexx_raw2xhtml(self::getApiProtocol() . $websiteBaseDn->getName()) : '',
                'MULTISITE_WEBSITE_BACKEND_LINK'  => $websiteBaseDn ? contrexx_raw2xhtml(self::getApiProtocol() . $websiteBaseDn->getName()) . '/cadmin' : '',
                'MULTISITE_WEBSITE_FRONTEND_LINK' => $websiteBaseDn ? self::getApiProtocol() . $websiteBaseDn->getName() : '',
                'MULTISITE_WEBSITE_STATE_CLASS'   => $status ? 'active' : (in_array($website->getStatus(), $websiteInitialStatus) ? 'init' : 'inactive'),
            ));

            self::showOrHideBlock($objTemplate, 'websiteLinkActive', $status);
            self::showOrHideBlock($objTemplate, 'websiteLinkInactive', !$status);
            self::showOrHideBlock($objTemplate, 'showAdminButton', ($status && $website->getOwner()->getId() == $userId));
            self::showOrHideBlock($objTemplate, 'showWebsiteLink', $status);
            self::showOrHideBlock($objTemplate, 'showWebsiteName', !$status);
            self::showOrHideBlock($objTemplate, 'showWebsiteViewButton', $status);

            if (in_array($website->getStatus(), $websiteInitialStatus)) {
                self::showOrHideBlock($objTemplate, 'actionButtonsActive', false);
                self::showOrHideBlock($objTemplate, 'websiteInitializing', true);
            }
        }
    }

    /**
     * Return the status of a website as literal
     * I.e. if website::$status is STATE_OFFLINE, it will return ' (offline)'
     *
     * @param   \Cx\Core_Modules\MultiSite\Model\Entity\Website $website
     * @global  array   $_ARRAYLANG
     * @return  string
     */
    public static function getWebsiteNonOnlineStateAsLiteral(\Cx\Core_Modules\MultiSite\Model\Entity\Website $website) {
        global $_ARRAYLANG;

        $status = '';
        switch ($website->getStatus()) {
            case \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_OFFLINE:
                $status = ' ('.$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_OFFLINE'].')';
                break;

            case \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_DISABLED:
                $status = ' ('.$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_DISABLED'].')';
                break;

            default:
                break;
        }

        return $status;
    }

    /**
     * Parse the product details to the view page
     * 
     * @param \Cx\Core\Html\Sigma $objTemplate              Template object
     * @param \Cx\Modules\Pim\Model\Entity\Product $product Product object
     * @param integer $crmContactId crmContactId
     */
    public static function parseProductForAddWebsite(\Cx\Core\Html\Sigma $objTemplate, \Cx\Modules\Pim\Model\Entity\Product $product, $crmContactId = 0)
    {
        $currency = self::getUserCurrency($crmContactId);
        $productPrice = $product->getPaymentAmount(\Cx\Modules\Pim\Model\Entity\Product::UNIT_MONTH, 1, $currency, true);
        if (\FWValidator::isEmpty($productPrice)) {
            self::showOrHideBlock($objTemplate, 'multisite_pay_button', false);
        }
        $objTemplate->setVariable(array(
            'TXT_MULTISITE_PAYMENT_MODE' => !empty($productPrice) ? true : false,
            'PRODUCT_NOTE_ENTITY'     => $product->getNoteEntity(),
            'PRODUCT_NOTE_RENEWAL'    => $product->getNoteRenewal(),
            'PRODUCT_NOTE_UPGRADE'    => $product->getNoteUpgrade(),
            'PRODUCT_NOTE_EXPIRATION' => $product->getNoteExpiration(),
            'PRODUCT_NOTE_PRICE'      => $product->getNotePrice(),
            'PRODUCT_NAME'            => $product->getName(),
            'PRODUCT_PRICE'           => $productPrice,
            'PRODUCT_ORDER_CURRENCY'  => $currency->getName(),
            'PRODUCT_ID'              => $product->getId(),
            'RENEWAL_UNIT'            => isset($_GET['renewalOption']) ? contrexx_raw2xhtml($_GET['renewalOption']) : 'monthly',
        ));
    }
    
    /**
     * returns the formatted query string
     * 
     * @param array $params parameters array
     * 
     * @return string query string
     */
    public static function buildHttpQueryString($params = array())
    {
        $separator   = '';
        $queryString = '';
        foreach($params as $key => $value) {
            $queryString .= $separator . $key . '=' . $value;
            $separator    = '&';
        }
        
        return $queryString;
    }

    /**
     * Show or hide the block based on criteria
     * 
     * @param \Cx\Core\Html\Sigma $objTemplate
     * @param string              $blockName
     * @param boolean             $status
     */
    public static function showOrHideBlock(\Cx\Core\Html\Sigma $objTemplate, $blockName, $status = true) {
        if ($objTemplate->blockExists($blockName)) {
            if ($status) {
                $objTemplate->touchBlock($blockName);
            } else {
                $objTemplate->hideBlock($blockName);
            }
        }
    }
    
    /**
     * Check currently sign-in user
     * 
     * @return boolean
     */
    public static function isUserLoggedIn() {
        \Cx\Core\Session\Model\Entity\Session::getInstance();
        
        $objUser = \FWUser::getFWUserObject()->objUser;
        
        return $objUser->login();
    }
    
    /**
     * Checks whether logged in user is a crm user
     * 
     * @return boolean TRUE on success false otherwise
     */
    public function isCrmUser()
    {
        if (!self::isUserLoggedIn()) {
            return false;
        }

        $objUser = \FWUser::getFWUserObject()->objUser;
        if (!$objUser) {
            return false;
        }

        $crmContactId = $objUser->getCrmUserId();
        if (empty($crmContactId)) {
           return false;
        }

        return true;
    }

    /**
     * Get the Hosting Controller
     * 
     * @return WebDistributionController $hostingController hostingController
     */
    public static function getHostingController()
    {
        if (!isset(static::$webDistributionController)) {
            \Cx\Core\Setting\Controller\Setting::init('MultiSite', '','FileSystem');
            $wDCName = \Cx\Core\Setting\Controller\Setting::getValue(
                'websiteController',
                'MultiSite'
            );
            $wDCClass = '\\' . __NAMESPACE__ . '\\' . ucfirst($wDCName) . 'Controller';
            static::$webDistributionController = $wDCClass::fromConfig();
        }
        return static::$webDistributionController;
    }

    /**
     * Get mail service server hosting controller
     * 
     * @param object \Cx\Core_Modules\MultiSite\Model\Entity\MailServiceServer $mailServiceServer
     * 
     * @return $hostingController
     */
    public static function getMailServerHostingController(\Cx\Core_Modules\MultiSite\Model\Entity\MailServiceServer $mailServiceServer) {
        switch ($mailServiceServer->getType()) {
            case 'plesk':
                $hostingController = new PleskController(
                    $mailServiceServer->getHostname(),
                    $mailServiceServer->getAuthUsername(),
                    $mailServiceServer->getAuthPassword(),
                    null,
                    $mailServiceServer->getApiVersion()
                );
                break;

            case 'xampp':
            default:
                throw new MultiSiteException('Unknown MailController set!');
                break;
        }
        return $hostingController;
    }

    /**
     * Fixes database errors.
     *
     * @return  boolean                 False.  Always.
     * @throws  MultiSiteException
     */
    static function errorHandler()
    {
        global $_CONFIG;
        
        try {
            \Cx\Core\Setting\Controller\Setting::init('MultiSite', '','FileSystem');

            // abort in case the Contrexx installation is in MultiSite website operation mode
            if (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite') == self::MODE_WEBSITE) {
                return false;
            }

            // config group
            \Cx\Core\Setting\Controller\Setting::init('MultiSite', 'config','FileSystem');
            if (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('mode',self::MODE_NONE, 1,
                \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN, self::MODE_NONE.':'.self::MODE_NONE.','.self::MODE_MANAGER.':'.self::MODE_MANAGER.','.self::MODE_SERVICE.':'.self::MODE_SERVICE.','.self::MODE_HYBRID.':'.self::MODE_HYBRID, 'config')){
                    throw new MultiSiteException("Failed to add Setting entry for Database Mode");
            }

            // abort in case MultiSite component is not in use
            if (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite') == self::MODE_NONE) {
                return false;
            }
            
            // server group
            \Cx\Core\Setting\Controller\Setting::init(
                'MultiSite',
                'server',
                'FileSystem'
            );
            if (
                \Cx\Core\Setting\Controller\Setting::getValue(
                    'websiteController',
                    'MultiSite'
                ) === null &&
                !\Cx\Core\Setting\Controller\Setting::add(
                    'websiteController',
                    'xampp',
                    1,
                    \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN,
                    'xampp:XAMPP,plesk:Plesk,aws:Amazon Web Services',
                    'server'
                )
            ) {
                throw new MultiSiteException(
                    'Failed to add Setting entry for Database user website Controller'
                );
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('multiSiteProtocolIn','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('multiSiteProtocolIn','mixed', 2,
                \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN, 'mixed:Allow insecure (HTTP) and secure (HTTPS) connections,http:Allow only insecure (HTTP) connections,https:Allow only secure (HTTPS) connections', 'server')){
                    throw new MultiSiteException("Failed to add Setting entry for Multisite IN Protocol");
            }
            
            // setup group
            \Cx\Core\Setting\Controller\Setting::init('MultiSite', 'setup','FileSystem');
            if (\Cx\Core\Setting\Controller\Setting::getValue('multiSiteProtocol','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('multiSiteProtocol','mixed', 2,
                \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN, 'mixed:Allow insecure (HTTP) and secure (HTTPS) connections,http:Allow only insecure (HTTP) connections,https:Allow only secure (HTTPS) connections', 'setup')){
                    throw new MultiSiteException("Failed to add Setting entry for Multisite Protocol");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('multiSiteDomain','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('multiSiteDomain',$_CONFIG['domainUrl'], 3,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'setup')){
                    throw new MultiSiteException("Failed to add Setting entry for Database multiSite Domain");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('marketingWebsiteDomain','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('marketingWebsiteDomain',$_CONFIG['domainUrl'], 4,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'setup')){
                    throw new MultiSiteException("Failed to add Setting entry for Marketing Website Domain");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('dashboardNewsSrc','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('dashboardNewsSrc', 'http://'.$_CONFIG['domainUrl'].'/feed/news_headlines_de.xml', 5,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'setup')){
                    throw new MultiSiteException("Failed to add Setting entry for dashboardNewsSrc");
            }
// TODO: this should be an existing domain from Cx\Core\Net
            if (\Cx\Core\Setting\Controller\Setting::getValue('customerPanelDomain','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('customerPanelDomain',$_CONFIG['domainUrl'], 5,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'setup')){
                    throw new MultiSiteException("Failed to add Setting entry for Customer Panel Domain");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('unavailablePrefixes','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('unavailablePrefixes', 'account,admin,demo,dev,mail,media,my,staging,test,www', 6,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXTAREA, null, 'setup')){
                    throw new MultiSiteException("Failed to add Setting entry for Unavailable website names");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('websiteNameMaxLength','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('websiteNameMaxLength',80, 7,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'setup')){
                    throw new MultiSiteException("Failed to add Setting entry for Maximal length of website names");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('websiteNameMinLength','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('websiteNameMinLength',4, 8,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'setup')){
                    throw new MultiSiteException("Failed to add Setting entry for Minimal length of website names");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('sendSetupError','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('sendSetupError','0', 9,
                \Cx\Core\Setting\Controller\Setting::TYPE_RADIO, '1:Activated,0:Deactivated', 'setup')){
                    throw new MultiSiteException("Failed to add Setting entry for sendSetupError");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('termsUrl','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('termsUrl','[[NODE_AGB]]', 10,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'setup')){
                    throw new MultiSiteException("Failed to add Setting entry for URL to T&Cs");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('createFtpAccountOnSetup','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('createFtpAccountOnSetup', 0, 11,
                \Cx\Core\Setting\Controller\Setting::TYPE_RADIO, '1:Activated, 0:Deactivated', 'setup')){
                    throw new MultiSiteException("Failed to add Setting entry for Create FTP account during website setup");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('passwordSetupMethod','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('passwordSetupMethod', 'auto', 12,
                \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN, 'auto:Automatically,auto-with-verification:Automatically (with email verification),interactive:Interactive', 'setup')){
                    throw new MultiSiteException("Failed to add Setting entry for Password set method during website setup");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('autoLogin','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('autoLogin', '0', 13,
                \Cx\Core\Setting\Controller\Setting::TYPE_RADIO, '1:Activated, 0:Deactivated', 'setup')){
                    throw new MultiSiteException("Failed to add Setting entry for Auto Login during website setup");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('ftpAccountFixPrefix','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('ftpAccountFixPrefix', 'cx', 14,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'setup')){
                    throw new MultiSiteException("Failed to add Setting entry for ftp account fix prefix during website setup");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('forceFtpAccountFixPrefix','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('forceFtpAccountFixPrefix', 0, 15,
                \Cx\Core\Setting\Controller\Setting::TYPE_RADIO, '1:Activated, 0:Deactivated', 'setup')){
                    throw new MultiSiteException("Failed to add Setting entry for force ftp account fix prefix during website setup");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('supportFaqUrl','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('supportFaqUrl', 'https://www.cloudrexx.com/FAQ', 16,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'setup')){
                    throw new MultiSiteException("Failed to add Setting entry for support faq url during website setup");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('supportRecipientMailAddress','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('supportRecipientMailAddress', $_CONFIG['coreAdminEmail'], 17,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'setup')){
                    throw new MultiSiteException("Failed to add Setting entry for support recipient mail address during website setup");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('maxLengthFtpAccountName','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('maxLengthFtpAccountName', 16, 18,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'setup')){
                    throw new MultiSiteException("Failed to add Setting entry for maximum length for the FTP account name");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('payrexxAccount','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('payrexxAccount', '', 19,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'setup')){
                    throw new MultiSiteException("Failed to add Setting entry for URL to Payrexx form");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('payrexxApiSecret','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('payrexxApiSecret', '', 21,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'setup')){
                    throw new MultiSiteException("Failed to add Setting entry for Payrexx API Secret");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('domainBlackList','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('domainBlackList', self::getAllDomainsName(), 22,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXTAREA, null, 'setup')){
                    throw new MultiSiteException("Failed to add Setting entry for Domain Black list");
            }
            if (   \Cx\Core\Setting\Controller\Setting::getValue('websiteBackupLimit','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('websiteBackupLimit', 0, 23, \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'setup')
            ) {
                throw new MultiSiteException("Failed to add Setting Repository for website Backup Limit");
            }

            // websiteSetup group
            \Cx\Core\Setting\Controller\Setting::init('MultiSite', 'websiteSetup','FileSystem');
            if (\Cx\Core\Setting\Controller\Setting::getValue('websitePath','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('websitePath',\Env::get('cx')->getCodeBaseDocumentRootPath().'/websites', 1,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'websiteSetup')){
                    throw new MultiSiteException("Failed to add Setting entry for websites path");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('defaultCodeBase','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('defaultCodeBase','', 2,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'websiteSetup')){
                    throw new MultiSiteException("Failed to add SettingDb entry for Database Default code base");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('websiteDatabaseHost','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('websiteDatabaseHost','localhost', 3,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'websiteSetup')){
                    throw new MultiSiteException("Failed to add Setting entry for website database host");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('websiteDatabasePrefix','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('websiteDatabasePrefix','cloudrexx_', 4,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'websiteSetup')){
                    throw new MultiSiteException("Failed to add Setting entry for Database prefix for websites");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('websiteDatabaseUserPrefix','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('websiteDatabaseUserPrefix','clx_', 5,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'websiteSetup')){
                    throw new MultiSiteException("Failed to add Setting entry for Database user prefix for websites");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('defaultWebsiteIp','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('defaultWebsiteIp', $_SERVER['SERVER_ADDR'], 6,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'websiteSetup')){
                    throw new MultiSiteException("Failed to add Setting entry for Database user plesk IP");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('websiteHttpAuthMethod','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('websiteHttpAuthMethod', '', 8,
                \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN, 'none:none, basic:basic, digest:digest', 'websiteSetup')){
                    throw new MultiSiteException("Failed to add Setting entry for HTTP Authentication Method of Website");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('websiteHttpAuthUsername','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('websiteHttpAuthUsername', '', 9,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'websiteSetup')){
                    throw new MultiSiteException("Failed to add Setting entry for HTTP Authentication Username of Website");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('websiteHttpAuthPassword','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('websiteHttpAuthPassword', '', 10,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'websiteSetup')){
                    throw new MultiSiteException("Failed to add Setting entry for HTTP Authentication Password of Website");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('codeBaseRepository','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('codeBaseRepository', \Env::get('cx')->getCodeBaseDocumentRootPath() . '/codeBases', 7,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'websiteSetup')){
                    throw new MultiSiteException("Failed to add Setting Repository for Contrexx Code Bases");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('websiteFtpPath','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('websiteFtpPath', '', 11,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'websiteSetup')){
                    throw new MultiSiteException("Failed to add Setting Repository for website FTP path");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('websiteBackupLocation','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('websiteBackupLocation', \Env::get('cx')->getCodeBaseCoreModulePath().'/MultiSite/Data/Backups', 12,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'websiteSetup')){
                    throw new MultiSiteException("Failed to add Setting Repository for website Backup Location");
            }

            // websiteManager group
            \Cx\Core\Setting\Controller\Setting::init('MultiSite', 'websiteManager','FileSystem');
            if (\Cx\Core\Setting\Controller\Setting::getValue('managerHostname','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('managerHostname',$_CONFIG['domainUrl'], 1,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'websiteManager')){
                    throw new MultiSiteException("Failed to add Setting entry for Database Manager Hostname");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('managerSecretKey','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('managerSecretKey','', 2,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'websiteManager')){
                    throw new MultiSiteException("Failed to add Setting entry for Database Manager Secret Key");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('managerInstallationId','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('managerInstallationId','', 3,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'websiteManager')){
                    throw new MultiSiteException("Failed to add Setting entry for Database Manager Installation Id");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('managerHttpAuthMethod','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('managerHttpAuthMethod','', 4,
                \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN, 'none:none, basic:basic, digest:digest', 'websiteManager')){
                    throw new MultiSiteException("Failed to add Setting entry for Database Manager HTTP Authentication Method");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('managerHttpAuthUsername','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('managerHttpAuthUsername','', 5,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'websiteManager')){
                    throw new MultiSiteException("Failed to add Setting entry for Database Manager HTTP Authentication Username");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('managerHttpAuthPassword','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('managerHttpAuthPassword','', 6,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'websiteManager')){
                    throw new MultiSiteException("Failed to add Setting entry for Database Manager HTTP Authentication Password");
            }

            //manager group
            \Cx\Core\Setting\Controller\Setting::init('MultiSite', 'manager','FileSystem');
            if (!\FWValidator::isEmpty(\Env::get('db'))
                && \Cx\Core\Setting\Controller\Setting::getValue('defaultWebsiteServiceServer','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('defaultWebsiteServiceServer', self::getDefaultEntityId('Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer'), 1,
                \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN, '{src:\\'.__CLASS__.'::getWebsiteServiceServerList()}', 'manager') ) {
                   throw new MultiSiteException("Failed to add Setting entry for Default Website Service Server");
            }
            if (!\FWValidator::isEmpty(\Env::get('db'))
                && \Cx\Core\Setting\Controller\Setting::getValue('defaultMailServiceServer','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('defaultMailServiceServer', self::getDefaultEntityId('Cx\Core_Modules\MultiSite\Model\Entity\MailServiceServer'), 2,
                \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN, '{src:\\'.__CLASS__.'::getMailServiceServerList()}', 'manager') ) {
                   throw new MultiSiteException("Failed to add Setting entry for Default mail Service Server");
            }
            if (!\FWValidator::isEmpty(\Env::get('db'))
                && \Cx\Core\Setting\Controller\Setting::getValue('defaultWebsiteTemplate','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('defaultWebsiteTemplate', self::getDefaultEntityId('Cx\Core_Modules\MultiSite\Model\Entity\WebsiteTemplate'), 3,
                \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN, '{src:\\'.__CLASS__.'::getWebsiteTemplateList()}', 'manager')) {
                    throw new MultiSiteException("Failed to add Setting entry for default Website Template");
            }
            if (!\FWValidator::isEmpty(\Env::get('db'))
                && \Cx\Core\Setting\Controller\Setting::getValue('defaultPimProduct','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('defaultPimProduct', self::getDefaultPimProductId(), 4,
                \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN, '{src:\\'.__CLASS__.'::getProductList()}', 'manager') ) {
                   throw new MultiSiteException("Failed to add Setting entry for Product List");
            }
            if (   \Cx\Core\Setting\Controller\Setting::getValue('affiliateSystem', 'MultiSite') === NULL 
                && !\Cx\Core\Setting\Controller\Setting::add('affiliateSystem', '0', 6,
                   \Cx\Core\Setting\Controller\Setting::TYPE_RADIO, '1:Activated, 0:Deactivated', 'manager')) {
                   throw new MultiSiteException("Failed to add Setting entry for Affiliate System");
            }
            if (   \Cx\Core\Setting\Controller\Setting::getValue('conversionTracking', 'MultiSite') === NULL 
                && !\Cx\Core\Setting\Controller\Setting::add('conversionTracking', '0', 7,
                   \Cx\Core\Setting\Controller\Setting::TYPE_RADIO, '1:Activated, 0:Deactivated', 'manager')) {
                   throw new MultiSiteException("Failed to add Setting entry for Conversion Tracking");
            }
            
            if (in_array(\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite'), array(self::MODE_MANAGER, self::MODE_HYBRID))) {
                if (!\FWValidator::isEmpty(\Env::get('db'))) {
                    self::addOrUpdateConfigurationOptionUserProfileAttributeId(
                        'externalPaymentCustomerIdProfileAttributeId', 
                        'MultiSite External Payment Customer ID',
                        5);
                    self::addOrUpdateConfigurationOptionUserProfileAttributeId(
                        'notificationCancelledProfileAttributeId',
                        'Cancelled notification emails user profile attribute ID',
                        6,
                        'manager',
                        false);
                }
                
                //conversion group
                \Cx\Core\Setting\Controller\Setting::init('MultiSite', 'conversion','FileSystem');
                if (\Cx\Core\Setting\Controller\Setting::getValue('trackGoogleConversion','MultiSite') === NULL
                    && !\Cx\Core\Setting\Controller\Setting::add('trackGoogleConversion', '0', 1,
                    \Cx\Core\Setting\Controller\Setting::TYPE_RADIO, '1:Activated, 0:Deactivated', 'conversion')){
                        throw new MultiSiteException("Failed to add Setting entry for Track Google Conversion");
                }
                if (\Cx\Core\Setting\Controller\Setting::getValue('googleConversionId','MultiSite') === NULL
                    && !\Cx\Core\Setting\Controller\Setting::add('googleConversionId', '', 2,
                    \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'conversion')){
                        throw new MultiSiteException("Failed to add Setting entry for Google Conversion Id");
                }
                if (\Cx\Core\Setting\Controller\Setting::getValue('trackFacebookConversion','MultiSite') === NULL
                    && !\Cx\Core\Setting\Controller\Setting::add('trackFacebookConversion', '0', 3,
                    \Cx\Core\Setting\Controller\Setting::TYPE_RADIO, '1:Activated, 0:Deactivated', 'conversion')){
                        throw new MultiSiteException("Failed to add Setting entry for Track Facebook Conversion");
                }
                if (\Cx\Core\Setting\Controller\Setting::getValue('facebookConversionId','MultiSite') === NULL
                    && !\Cx\Core\Setting\Controller\Setting::add('facebookConversionId', '', 4,
                    \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'conversion')){
                        throw new MultiSiteException("Failed to add Setting entry for Facebook Conversion Id");
                }

                //affiliate group
                \Cx\Core\Setting\Controller\Setting::init('MultiSite', 'affiliate','FileSystem');
                if (   \Cx\Core\Setting\Controller\Setting::getValue('affiliateIdQueryStringKey','MultiSite') === NULL
                    && !\Cx\Core\Setting\Controller\Setting::add('affiliateIdQueryStringKey', 'ref', 1, \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, '', 'affiliate')
                ) {
                       throw new MultiSiteException("Failed to add Setting entry for Affiliate ID query string key");
                }
                if (   \Cx\Core\Setting\Controller\Setting::getValue('affiliatePayoutLimit','MultiSite') === NULL
                    && !\Cx\Core\Setting\Controller\Setting::add('affiliatePayoutLimit', '50', 2, \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, '', 'affiliate')
                ) {
                       throw new MultiSiteException("Failed to add Setting entry for Affiliate Payout Limit");
                }
                if (    \Cx\Core\Setting\Controller\Setting::getValue('affiliateCookieLifetime', 'MultiSite') === NULL
                    &&  !\Cx\Core\Setting\Controller\Setting::add('affiliateCookieLifetime', 30, 6, \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, '', 'affiliate')
                ) {
                        throw new MultiSiteException("Failed to add Setting entry for Affiliate cookie life time");
                }
                
                if (!\FWValidator::isEmpty(\Env::get('db'))) {
                    self::addOrUpdateConfigurationOptionUserProfileAttributeId(
                        'affiliateIdProfileAttributeId',
                        'Affiliate ID user profile attribute ID',
                        3,
                        'affiliate');
                    self::addOrUpdateConfigurationOptionUserProfileAttributeId(
                        'affiliateIdReferenceProfileAttributeId',
                        'Affiliate ID (reference) user profile attribute ID',
                        4,
                        'affiliate');
                    self::addOrUpdateConfigurationOptionUserProfileAttributeId(
                        'payPalProfileAttributeId',
                        'PayPal profile attribute ID',
                        5,
                        'affiliate',
                        false);

                }
            }
        } catch (\Exception $e) {
            \DBG::msg($e->getMessage());
        }
        // Always
        return false;
    }

    /**
     * Add or Update the Configuration Option User Profile Attribute id
     * 
     * @param string  $configOptionName    config option name
     * @param string  $attributeName       attribute name
     * @param integer $order               position
     * @param boolean $attributeProtection profile attribute protection
     * 
     * @return boolean
     * @throws MultiSiteException
     */
    public static function addOrUpdateConfigurationOptionUserProfileAttributeId($configOptionName, $attributeName, $order,  $group = 'manager', $attributeProtection = true) {
        if (empty($configOptionName)) {
            return;
        }
        $dbProfileAttributeId      = self::getProfileAttributeIdByConfigOptionName($configOptionName, $attributeName, $attributeProtection);
        $settingProfileAttributeId = \Cx\Core\Setting\Controller\Setting::getValue($configOptionName,'MultiSite');

        if ($settingProfileAttributeId != $dbProfileAttributeId) {
            \Cx\Core\Setting\Controller\Setting::init('MultiSite', $group, 'FileSystem');
            if ($settingProfileAttributeId === null) {
                if (!\Cx\Core\Setting\Controller\Setting::add($configOptionName, $dbProfileAttributeId, $order, \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN, '{src:\\'.__CLASS__.'::getCustomAccessUserProfileAttributes()}', $group)) {
                    throw new MultiSiteException('Failed to add Setting entry for ' . $attributeName);
                }
            } else {
                if (   !(\Cx\Core\Setting\Controller\Setting::set($configOptionName, $dbProfileAttributeId)
                    && \Cx\Core\Setting\Controller\Setting::update($configOptionName))
                ) {
                    throw new MultiSiteException('Failed to update Setting for ' . $attributeName);
                }
            }
        }
    }
    
    /**
     * Register the Event listeners
     */
    public function registerEventListeners() {
        // do not register any Event Listeners in case MultiSite mode is not set
        if (!\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
            return;
        }

        global $objInit, $_ARRAYLANG;
        
        if ($objInit) {
            $langData = $objInit->loadLanguageData('MultiSite');
            $_ARRAYLANG = array_merge($_ARRAYLANG, $langData);
        }
        
        switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
            case self::MODE_MANAGER:
                $this->registerDomainEventListener();
                $this->registerNetDomainEventListener();
                $this->registerWebsiteEventListener();
                $this->registerAccessUserEventListener();
                $this->registerCronMailEventListener();
                $this->registerWebsiteTemplateEventListener();
                $this->registerOrderPaymentEventListener();
                $this->registerWebsiteCollectionEventListener();
                $this->registerOrderSubscriptionEventListener();
                $this->registerOrderOrderEventListener();
                break;

            case self::MODE_HYBRID:
                $this->registerDomainEventListener();
                $this->registerNetDomainEventListener();
                $this->registerWebsiteEventListener();
                $this->registerAccessUserEventListener();
                $this->registerCronMailEventListener();
                $this->registerOrderPaymentEventListener();
                $this->registerWebsiteCollectionEventListener();
                $this->registerOrderSubscriptionEventListener();
                $this->registerOrderOrderEventListener();
                break;

            case self::MODE_SERVICE:
                $this->registerDomainEventListener();
                $this->registerNetDomainEventListener();
                $this->registerWebsiteEventListener();
                $this->registerAccessUserEventListener();
                $this->registerWebsiteCollectionEventListener();
                break;

            case self::MODE_WEBSITE:
                $this->registerNetDomainEventListener();
                $this->registerAccessUserEventListener();
                $this->registerContactFormEventListener();
                $this->registerShopProductEventListener();
                $this->registerCrmCustomerEventListener();
                break;

            default:
                break;
        }

        // set customer panel domain as main domain on website manager
        $this->setCustomerPanelDomainAsMainDomain();
    }

    protected function registerDomainEventListener() {
        $domainEventListener = new \Cx\Core_Modules\MultiSite\Model\Event\DomainEventListener();
        $evm = \Env::get('cx')->getEvents();
        $evm->addModelListener(\Doctrine\ORM\Events::prePersist, 'Cx\\Core_Modules\\MultiSite\\Model\\Entity\\Domain', $domainEventListener);
        $evm->addModelListener(\Doctrine\ORM\Events::postPersist, 'Cx\\Core_Modules\\MultiSite\\Model\\Entity\\Domain', $domainEventListener);
        $evm->addModelListener(\Doctrine\ORM\Events::postRemove, 'Cx\\Core_Modules\\MultiSite\\Model\\Entity\\Domain', $domainEventListener);
        $evm->addModelListener(\Doctrine\ORM\Events::preUpdate, 'Cx\\Core_Modules\\MultiSite\\Model\\Entity\\Domain', $domainEventListener);
    }

    protected function registerNetDomainEventListener() {
        $domainEventListener = new \Cx\Core_Modules\MultiSite\Model\Event\DomainEventListener();
        $evm = \Env::get('cx')->getEvents();
        $evm->addModelListener(\Doctrine\ORM\Events::prePersist, 'Cx\\Core\\Net\\Model\\Entity\\Domain', $domainEventListener);
        $evm->addModelListener(\Doctrine\ORM\Events::postPersist, 'Cx\\Core\\Net\\Model\\Entity\\Domain', $domainEventListener);
        $evm->addModelListener(\Doctrine\ORM\Events::postRemove, 'Cx\\Core\\Net\\Model\\Entity\\Domain', $domainEventListener);
        $evm->addModelListener(\Doctrine\ORM\Events::preUpdate, 'Cx\\Core\\Net\\Model\\Entity\\Domain', $domainEventListener);
    }

    protected function registerWebsiteEventListener() {
        $websiteEventListener = new \Cx\Core_Modules\MultiSite\Model\Event\WebsiteEventListener();
        $evm = \Env::get('cx')->getEvents();
        $evm->addModelListener(\Doctrine\ORM\Events::preUpdate, 'Cx\\Core_Modules\\MultiSite\\Model\\Entity\\Website', $websiteEventListener);
        $evm->addModelListener(\Doctrine\ORM\Events::postUpdate, 'Cx\\Core_Modules\\MultiSite\\Model\\Entity\\Website', $websiteEventListener);
        $evm->addModelListener(\Doctrine\ORM\Events::preRemove, 'Cx\\Core_Modules\\MultiSite\\Model\\Entity\\Website', $websiteEventListener);
    }

    /**
     * @todo    Split up into UserEventListener and AccessUserEventListener
     */
    protected function registerAccessUserEventListener() {
        $accessUserEventListener    = new \Cx\Core_Modules\MultiSite\Model\Event\AccessUserEventListener();
        $evm = \Env::get('cx')->getEvents();
        $evm->addModelListener(\Doctrine\ORM\Events::prePersist, 'User', $accessUserEventListener);
        $evm->addModelListener(\Doctrine\ORM\Events::postPersist, 'User', $accessUserEventListener);
        $evm->addModelListener(\Doctrine\ORM\Events::preUpdate, 'User', $accessUserEventListener);
        $evm->addModelListener(\Doctrine\ORM\Events::preRemove, 'User', $accessUserEventListener);
        $evm->addModelListener(\Doctrine\ORM\Events::postUpdate, 'User', $accessUserEventListener);

        $evm->addModelListener(\Doctrine\ORM\Events::prePersist, 'Cx\\Core_Modules\\MultiSite\\Model\\Entity\\User', $accessUserEventListener);
        $evm->addModelListener(\Doctrine\ORM\Events::postPersist, 'Cx\\Core_Modules\\MultiSite\\Model\\Entity\\User', $accessUserEventListener);
        $evm->addModelListener(\Doctrine\ORM\Events::preUpdate, 'Cx\\Core_Modules\\MultiSite\\Model\\Entity\\User', $accessUserEventListener);
        $evm->addModelListener(\Doctrine\ORM\Events::preRemove, 'Cx\\Core_Modules\\MultiSite\\Model\\Entity\\User', $accessUserEventListener);
        $evm->addModelListener(\Doctrine\ORM\Events::postUpdate, 'Cx\\Core_Modules\\MultiSite\\Model\\Entity\\User', $accessUserEventListener);
    }

    protected function registerCronMailEventListener() {
        $cronMailEventListener = new \Cx\Core_Modules\MultiSite\Model\Event\CronMailEventListener();
        $evm = \Env::get('cx')->getEvents();
        $evm->addModelListener(\Doctrine\ORM\Events::prePersist, 'Cx\\Core_Modules\\MultiSite\\Model\\Entity\\CronMail', $cronMailEventListener);
        $evm->addModelListener(\Doctrine\ORM\Events::preUpdate, 'Cx\\Core_Modules\\MultiSite\\Model\\Entity\\CronMail', $cronMailEventListener);
    }

    protected function registerWebsiteTemplateEventListener() {
        $websiteTemplateEventListener = new \Cx\Core_Modules\MultiSite\Model\Event\WebsiteTemplateEventListener();
        $evm = \Env::get('cx')->getEvents();
        $evm->addModelListener(\Doctrine\ORM\Events::postPersist, 'Cx\\Core_Modules\\MultiSite\\Model\\Entity\\WebsiteTemplate', $websiteTemplateEventListener);
        $evm->addModelListener(\Doctrine\ORM\Events::postUpdate, 'Cx\\Core_Modules\\MultiSite\\Model\\Entity\\WebsiteTemplate', $websiteTemplateEventListener);
    }

    protected function registerContactFormEventListener() {
        $contactFormEventListener = new \Cx\Core_Modules\MultiSite\Model\Event\ContactFormEventListener();
        $evm = \Env::get('cx')->getEvents();
        $evm->addModelListener(\Doctrine\ORM\Events::prePersist, 'Cx\\Core_Modules\\Contact\\Model\\Entity\\Form', $contactFormEventListener);
    }

    protected function registerShopProductEventListener() {
        $shopProductEventListener = new \Cx\Core_Modules\MultiSite\Model\Event\ShopProductEventListener();
        $evm = \Env::get('cx')->getEvents();
        $evm->addModelListener(\Doctrine\ORM\Events::prePersist, 'Cx\\Modules\\Shop\\Controller\\Product', $shopProductEventListener);
    }

    protected function registerCrmCustomerEventListener() {
        $crmCustomerEventListener = new \Cx\Core_Modules\MultiSite\Model\Event\CrmCustomerEventListener();
        $evm = \Env::get('cx')->getEvents();
        $evm->addModelListener(\Doctrine\ORM\Events::prePersist, 'Cx\\Modules\\Crm\\Model\\Entity\\CrmContact', $crmCustomerEventListener);
    }

    protected function registerWebsiteCollectionEventListener() {
        $websiteCollectionEventListener = new \Cx\Core_Modules\MultiSite\Model\Event\WebsiteCollectionEventListener();
        $evm = \Env::get('cx')->getEvents();
        $evm->addModelListener(\Doctrine\ORM\Events::postPersist, 'Cx\\Core_Modules\\MultiSite\\Model\\Entity\\WebsiteCollection', $websiteCollectionEventListener);
        $evm->addModelListener(\Doctrine\ORM\Events::preRemove, 'Cx\\Core_Modules\\MultiSite\\Model\\Entity\\WebsiteCollection', $websiteCollectionEventListener);
    }

    /**
     * @todo Move to Order component?
     */
    protected function registerOrderPaymentEventListener() {
        $orderPaymentEventListener = new \Cx\Core_Modules\MultiSite\Model\Event\OrderPaymentEventListener();
        $evm = \Env::get('cx')->getEvents();
        $evm->addModelListener(\Doctrine\ORM\Events::postPersist, 'Cx\\Modules\\Order\\Model\\Entity\\Payment', $orderPaymentEventListener);
        
    }
    
    protected function registerOrderOrderEventListener() {
        $orderOrderEventListener = new \Cx\Core_Modules\MultiSite\Model\Event\OrderOrderEventListener();
        $evm = \Env::get('cx')->getEvents();
        $evm->addModelListener(\Doctrine\ORM\Events::preUpdate, 'Cx\\Modules\\Order\\Model\\Entity\\Order', $orderOrderEventListener);
        $evm->addModelListener(\Doctrine\ORM\Events::postFlush, 'Cx\\Modules\\Order\\Model\\Entity\\Order', $orderOrderEventListener);
    }
    
    protected function registerOrderSubscriptionEventListener() {
        $orderSubscriptionEventListener = new \Cx\Core_Modules\MultiSite\Model\Event\OrderSubscriptionEventListener();
        $evm = \Env::get('cx')->getEvents();
        $evm->addModelListener(\Doctrine\ORM\Events::preUpdate, 'Cx\\Modules\\Order\\Model\\Entity\\Subscription', $orderSubscriptionEventListener);
        $evm->addModelListener(\Doctrine\ORM\Events::postUpdate,'Cx\\Modules\\Order\\Model\\Entity\\Subscription', $orderSubscriptionEventListener);
        $evm->addModelListener(\Doctrine\ORM\Events::postPersist,'Cx\\Modules\\Order\\Model\\Entity\\Subscription', $orderSubscriptionEventListener);
        $evm->addModelListener(\Doctrine\ORM\Events::postFlush, 'Cx\\Modules\\Order\\Model\\Entity\\Subscription', $orderSubscriptionEventListener);
        $evm->addModelListener('expired',                       'Cx\\Modules\\Order\\Model\\Entity\\Subscription', $orderSubscriptionEventListener);
        $evm->addModelListener('terminated',                    'Cx\\Modules\\Order\\Model\\Entity\\Subscription', $orderSubscriptionEventListener);
        $evm->addModelListener('payComplete',                   'Cx\\Modules\\Order\\Model\\Entity\\Subscription', $orderSubscriptionEventListener);
    }
    
    public function preInit(\Cx\Core\Core\Controller\Cx $cx) {
        global $_CONFIG, $argv;

        /**
         * This gives us the list of classes that are not loaded from codebase
         */
        if (
            isset($_GET['MultiSitePreDeclared']) &&
            (
                $_GET['MultiSitePreDeclared'] == 'showClasses' ||
                $_GET['MultiSitePreDeclared'] == 'showInterfaces' ||
                $_GET['MultiSitePreDeclared'] == 'showFiles'
            )
        ) {
            global $builtinClasses, $builtinInterfaces;
            
            // simulate loading of website in order to get correct result
            $multiSiteRepo = new \Cx\Core_Modules\MultiSite\Model\Repository\FileSystemWebsiteRepository();
            $firstWebsiteName = key($multiSiteRepo->findFirst(\Cx\Core\Setting\Controller\Setting::getValue('websitePath','MultiSite').'/'));
            
            // get declared classes and interfaces without built in ones
            $declaredClasses = array_diff(get_declared_classes(), $builtinClasses);
            $declaredInterfaces = array_diff(get_declared_interfaces(), $builtinInterfaces);
            
            // sort output
            if (!isset($_GET['sort'])) {
                asort($declaredClasses);
                asort($declaredInterfaces);
            }
            
            // fix array indexes
            $declaredClasses = array_values($declaredClasses);
            $declaredInterfaces = array_values($declaredInterfaces);
            
            // output if showClasses or showInterfaces
            if ($_GET['MultiSitePreDeclared'] == 'showClasses') {
                echo implode("\n", $declaredClasses);
                die();
            }
            if ($_GET['MultiSitePreDeclared'] == 'showInterfaces') {
                echo implode("\n", $declaredInterfaces);
                die();
            }
            
            // create list of files
            $declaredFiles = array();
            foreach (array_merge($declaredClasses, $declaredInterfaces) as $class) {
                $reflection = new \ReflectionClass($class);
                $declaredFiles[] = substr(
                    $reflection->getFileName(),
                    strlen($this->cx->getCodeBaseDocumentRootPath()) + 1
                );
            }
            
            // drop duplicates, sort and fix indexes
            $declaredFiles = array_unique($declaredFiles);
            asort($declaredFiles);
            $declaredFiles = array_values($declaredFiles);
            
            // output
            echo implode("\n", $declaredFiles);
            die();
        }

        // Abort in case the request has been made to a unsupported cx-mode
        if (!in_array($cx->getMode(), array($cx::MODE_FRONTEND, $cx::MODE_BACKEND, $cx::MODE_COMMAND, $cx::MODE_MINIMAL))) {
            return;
        }

        // Abort in case this Contrexx installation has not been set up as a Website Service.
        // If the MultiSite module has not been configured, then 'mode' will be set to null.
        switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
            case self::MODE_MANAGER:
                $this->verifyRequest($cx);
                break;

            case self::MODE_HYBRID:
            case self::MODE_SERVICE:
                // In case the deployment was successful,
                // we need to exit this method and proceed
                // with the regular bootstrap process.
                // This case is required by the cx-mode MODE_MINIMAL.
                if ($this->deployWebsiteFromRequest($cx)) {
                    return;
                }
                $this->verifyRequest($cx);
                break;

            case self::MODE_WEBSITE:
                $requestCmd = isset($_REQUEST['cmd']) ? $_REQUEST['cmd'] : null;
                // handle MultiSite-API requests
                if (   $cx->getMode() == $cx::MODE_BACKEND
                    && $requestCmd == 'JsonData'
                ) {
                    // Set domainUrl to requeted website's domain alias.
                    // This is required in case optino 'forceDomainUrl' is set.
                    $_CONFIG['domainUrl'] = $_SERVER['HTTP_HOST'];

                    // MultiSite-API requests shall always be by-passed
                    break;
                }

                // Ignore the website's state if the sudo command is being
                // executed.
                // Otherwise the command will only be executed if the website
                // is currently online.
                if (
                    $cx->getMode() == \Cx\Core\Core\Controller\Cx::MODE_COMMAND &&
                    php_sapi_name() == 'cli' &&
                    count($argv) >= 3 &&
                    $argv[1] == 'sudo'
                ) {
                    array_splice($argv, 1, 1);
                    if ($argv[1] == 'Cache') {
                        // disable the user cache to ensure the cache
                        // command can be executed fail-safe
                        $cacheDriver = new \Doctrine\Common\Cache\ArrayCache();
                        $cx->getComponent('Cache')->setCacheDriver($cacheDriver);
                    }
                    break;
                }

                // deploy website when in online-state and request is a regular http request
                if (\Cx\Core\Setting\Controller\Setting::getValue('websiteState','MultiSite') == \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_ONLINE) {
                    break;
                }

                // show website offline message
                \header($_SERVER['SERVER_PROTOCOL'] . ' 503 Service Unavailable');
                // remove CSRF token
                output_reset_rewrite_vars();
                echo file_get_contents($this->getDirectory() . '/View/Template/Generic/offline.html');
                \DBG::log('Website is currently not online');
                throw new \Cx\Core\Core\Controller\InstanceException();
                break;

            default:
                break;
        }
    }

    protected function verifyRequest($cx) {
        $domainRepository = new \Cx\Core\Net\Model\Repository\DomainRepository();
        $managerDomain = $domainRepository->getMainDomain();
        $customerPanelDomainName = \Cx\Core\Setting\Controller\Setting::getValue('customerPanelDomain','MultiSite');
        $marketingWebsiteDomainName = \Cx\Core\Setting\Controller\Setting::getValue('marketingWebsiteDomain','MultiSite');
        $requestedDomainName = $_SERVER['HTTP_HOST'];

        // Allow access to backend only through Manager domain (-> Main Domain).
        // Other requests will be forwarded to the Marketing Website of MultiSite.
        if (   $cx->getMode() == $cx::MODE_BACKEND
            && $requestedDomainName != $managerDomain->getName()
// TODO: This is a workaround as all JsonData-requests sent from the
//       Customer Panel are also being sent to the Manager Domain.
            && $requestedDomainName != $customerPanelDomainName
        ) {
            header('Location: '.$this->getApiProtocol().$marketingWebsiteDomainName, true, 301);
            exit;
        }
        // Allow access to command-mode only through Manager domain (-> Main Domain) and Customer Panel domain
        // Other requests will be forwarded to the Marketing Website of MultiSite.
        if (   $cx->getMode() == $cx::MODE_COMMAND
            && php_sapi_name() != 'cli'
            && $requestedDomainName != $managerDomain->getName()
            && $requestedDomainName != $customerPanelDomainName
        ) {
            header('Location: '.$this->getApiProtocol().$marketingWebsiteDomainName, true, 301);
            exit;
        }
        // Allow access to command-mode only through Manager domain (-> Main Domain) and Customer Panel domain
        // Other requests will be forwarded to the Marketing Website of MultiSite.
        if (   $cx->getMode() == $cx::MODE_COMMAND
            && php_sapi_name() == 'cli'
        ) {
            global $argv;
            
            if (!isset($argv[1]) || $argv[1] != $this->getName()) {
                return;
            }
            if (!isset($argv[2]) || $argv[2] != 'pass') {
                return;
            }
            if (!isset($argv[3])) {
                echo 'Not enough arguments' . "\n";
                return;
            }
            
            $multiSiteRepo = new \Cx\Core_Modules\MultiSite\Model\Repository\FileSystemWebsiteRepository();
            $website = $multiSiteRepo->findByName(\Cx\Core\Setting\Controller\Setting::getValue('websitePath','MultiSite').'/', $argv[3]);
            
            if (!$website) {
                die('No such website: "' . $argv[3] . '"' . "\n");
            }
            
            array_shift($argv); // MultiSite
            array_shift($argv); // pass
            array_shift($argv); // <websiteName>
            $websiteCx = $this->deployWebsite($cx, $website);
            die();
        }

        // Allow access to frontend only on domain of Marketing Website and Customer Panel.
        // Other requests will be forwarded to the Marketing Website of MultiSite.
        if (   $cx->getMode() == $cx::MODE_FRONTEND
            && !empty($marketingWebsiteDomainName)
            && !empty($customerPanelDomainName)
            && $requestedDomainName != $marketingWebsiteDomainName
            && $requestedDomainName != $customerPanelDomainName
        ) {
            // Redirect by 302 as the request might have been targeted a
            // customer's website of which the DNS propagation did not
            // yet complete. If we would redirect by 301, the browser
            // would cache the redirect and then once the DNS
            // propagation will be completed, the browser won't
            // re-try to access the customer's website.
            header('Location: '.$this->getApiProtocol().$marketingWebsiteDomainName, true, 302);
            exit;
        }

        // In case the Manager domain has been requested,
        // the user will automatically be redirected to the backend.
        if (   $cx->getMode() == $cx::MODE_FRONTEND
            && $customerPanelDomainName != $managerDomain->getName()
            && $requestedDomainName == $managerDomain->getName()
        ) {
            $backendUrl = \Env::get('cx')->getWebsiteBackendPath();
            header('Location: '.$backendUrl);
            exit;
        }
    }

    protected function deployWebsiteFromRequest(\Cx\Core\Core\Controller\Cx $cx) {
        $multiSiteRepo = new \Cx\Core_Modules\MultiSite\Model\Repository\FileSystemWebsiteRepository();

        // remove port information from HTTP_HOST
        if (!isset($_SERVER['HTTP_HOST'])) {
            $_SERVER['HTTP_HOST'] = '';
        }
        $_SERVER['HTTP_HOST'] = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']);

        // dynamic mapping of <website>.cloudrexx.website
        $_SERVER['HTTP_HOST'] = preg_replace('/\.cloudrexx\.website$/i', '.cloudrexx.com', $_SERVER['HTTP_HOST'], 1);

        $website = $multiSiteRepo->findByDomain(\Cx\Core\Setting\Controller\Setting::getValue('websitePath','MultiSite').'/', $_SERVER['HTTP_HOST']);
        if ($website) {
            $this->deployWebsite($cx, $website);
            exit;
        }

        // no website found. Abort website-deployment and let Contrexx process with the regular system initialization (i.e. most likely with the Website Service Website)
        $requestInfo =    isset($_REQUEST['cmd']) && $_REQUEST['cmd'] == 'JsonData'
                       && isset($_REQUEST['object']) && $_REQUEST['object'] == 'MultiSite'
                       && isset($_REQUEST['act'])
                            ? '(API-call: '.$_REQUEST['act'].')'
                            : '';
        \DBG::msg("MultiSite: Loading Website Service...".$requestInfo);
        return false;
    }
    
    protected function deployWebsite($cx, $website) {
        // Recheck the system state of the Website Service Server (1st check
        // has already been performed before executing the preInit-Hooks),
        // but this time also lock the backend in case the system has been
        // put into maintenance mode, as a Website must also not be
        // accessable throuth the backend in case its Website Service Server
        // has activated the maintenance-mode.
        $cx->checkSystemState(true);

        // Ensure the current CX instance is the first one (-> Service Server).
        // This shall prevent an infinite loading loop in case the requested
        // website is still in its initialization (setup) phase in which
        // case the website does not yet have a configuration.php file.
        // The latter would cause to re-load the Service Server again (as
        // fallback). As every new initialization does increment the ID
        // of the CX instance, we can identify such a load loop by an ID
        // higher then 1
        if ($cx->getId() > 1) {
            throw new \Cx\Core\Core\Controller\InstanceException();
        }

        $configFile = \Cx\Core\Setting\Controller\Setting::getValue('websitePath','MultiSite').'/'.$website->getName().'/config/configuration.php';
        $requestInfo =    isset($_REQUEST['cmd']) && $_REQUEST['cmd'] == 'JsonData'
                       && isset($_REQUEST['object']) && $_REQUEST['object'] == 'MultiSite'
                       && isset($_REQUEST['act'])
                            ? '(API-call: '.$_REQUEST['act'].')'
                            : $_SERVER['REQUEST_URI'];
        \DBG::msg("MultiSite: Loading customer Website {$website->getName()}...".$requestInfo);
        \DBG::setLogPrefix($website->getName());
        // set SERVER_NAME to BaseDN of Website
        $_SERVER['SERVER_NAME'] = $website->getName() . '.' . \Cx\Core\Setting\Controller\Setting::getValue('multiSiteDomain','MultiSite');
        \Cx\Core\Core\Controller\Cx::instanciate($cx->getMode(), true, $configFile, true);

        // In cx-mode MODE_MINIMAL we must not abort
        // script execution as the script that initialized
        // the Cx object is most likely going to perform some
        // additional operations after the Cx initialization
        // has finished.
        // To prevent that the bootstrap process of the service
        // server is being proceeded, we must throw an
        // InstanceException here.
        if ($cx->getMode() == $cx::MODE_MINIMAL) {
            throw new \Cx\Core\Core\Controller\InstanceException();
        }
    }

    /**
     * set customer panel domain as main domain on website manager
     *
     * @global  array   $_CONFIG
     * @global  string  $plainCmd
     */
    public function setCustomerPanelDomainAsMainDomain() {
        global $_CONFIG, $plainCmd;

        if (!\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
            return;
        }
        
        $config = \Env::get('config');
        self::$cxMainDomain = $config['domainUrl'];
        
        if (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite') != self::MODE_MANAGER) {
            return;
        }

        if (in_array($plainCmd, array('MultiSite', 'JsonData'))) {
            return;
        }

        // do not set main-domain to customer-panel-domain when having requested the backend
        if ($this->cx->getMode() == \Cx\Core\Core\Controller\Cx::MODE_BACKEND) {
            return;
        }

        $customerPanelDomainName = \Cx\Core\Setting\Controller\Setting::getValue('customerPanelDomain','MultiSite');
        $domainRepository = new \Cx\Core\Net\Model\Repository\DomainRepository();
        $customerPanelDomain = $domainRepository->findOneBy(array('name' => $customerPanelDomainName));
        if ($customerPanelDomain) {
            $config['mainDomainId'] = $customerPanelDomain->getId();
            $config['domainUrl'] = $customerPanelDomain->getName();
            \Env::set('config', $config);
            $_CONFIG = $config;
        }
    }
    
    /**
     * Get the api protocol url
     * 
     * @return string $protocolUrl
     */
    public static function getApiProtocol() {
        switch (\Cx\Core\Setting\Controller\Setting::getValue('multiSiteProtocol','MultiSite')) {
            case 'http':
                $protocolUrl = 'http://';
                break;
            case 'https':
                $protocolUrl = 'https://';
                break;
            case 'mixed':
// TODO: this is a workaround for Websites, as they are not aware of the related configuration option
            default:
                return empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off' ? 'http://' : 'https://';
                break;
        }
        return $protocolUrl;
    }
    
    /**
     * Get the website service servers
     * 
     * @return string serviceServers list
     */
    public static function getWebsiteServiceServerList() {
        $websiteServiceServers = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer')->findAll();
        $dropdownOptions = array();
        foreach ($websiteServiceServers As $serviceServer) {
            $dropdownOptions[] = $serviceServer->getId() . ':' . $serviceServer->getHostname();
        }
        return implode(',', $dropdownOptions);
    }
    
    /**
     * Get the default entity id
     * 
     * @param string $entityClass entityClass
     * 
     * @return integer id
     */
    public static function getDefaultEntityId($entityClass)
    {
        if (empty($entityClass)) {
            return;
        }

        $repository = \Env::get('em')->getRepository($entityClass);
        if ($repository) {
            $defaultEntity = $repository->getFirstEntity();
            if ($defaultEntity) {
                return $defaultEntity->getId();
            }
        }
        return 0;
    }

    /**
     * Get the mail service servers
     * 
     * @return string  mail service servers list
     */
    public static function getMailServiceServerList() {
        $mailServiceServers = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\MailServiceServer')->findAll();
        $dropdownOptions = array();
        foreach ($mailServiceServers as $mailServiceServer) {
            $dropdownOptions[] = $mailServiceServer->getId() . ':' .$mailServiceServer->getLabel(). ' ('.$mailServiceServer->getHostname().')';
        }
        return implode(',', $dropdownOptions);
    }
    
    /**
     * Get the module additional data by its type
     * 
     * @param string $moduleName      name of the module
     * @param string $additionalType  additional type of the module additional data
     * @return mixed array | boolean
     */
    public static function getModuleAdditionalDataByType($moduleName = '', $additionalType = 'quota') {
        global $objDatabase;
        
        if (empty($moduleName) || empty($additionalType)) {
            return;
        }
        
        $objResult = $objDatabase->Execute('SELECT `additional_data` FROM ' . DBPREFIX . 'modules WHERE name= "'. contrexx_raw2db($moduleName) .'"');
        if ($objResult !== false) {
            $options = json_decode($objResult->fields['additional_data'], true);
            if (!empty($options)) {
               return $options[$additionalType];
            }
        }
        
        return false;
    }
    
    /**
     * Shows the all website templates
     * 
     * @access  private
     * @return  string
     */
    public static function getWebsiteTemplateList() {
        $websiteTemplatesObj = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\WebsiteTemplate');
        $websiteTemplates = $websiteTemplatesObj->findAll();
        $display = array();
        foreach ($websiteTemplates as $websiteTemplate) {
            $display[] = $websiteTemplate->getId() .':'. $websiteTemplate->getName();
        }
        return implode(',', $display);
    }

    /**
     * Get the server website list by using website owner id
     * 
     * @return string list of websites seperate by comma
     */
    public static function getServerWebsiteList()
    {
        $mode = \Cx\Core\Setting\Controller\Setting::getValue(
            'mode',
            'MultiSite'
        );
        if ($mode !== self::MODE_WEBSITE) {
            return '';
        }

        $ownerId       = \FWUser::getFWUserObject()->objUser->getId();
        $websiteUserId = \Cx\Core\Setting\Controller\Setting::getValue(
            'websiteUserId',
            'MultiSite'
        );
        if (
                !JsonMultiSiteController::isIscRequest() &&
                $ownerId != $websiteUserId
        ) {
            return '';
        }

        $websiteName = \Cx\Core\Setting\Controller\Setting::getValue(
            'websiteName',
            'MultiSite'
        );
        $response    = JsonMultiSiteController::executeCommandOnMyServiceServer(
            'getServerWebsiteList',
            array('websiteName' => $websiteName)
        );

        if (    !$response
            ||  $response->status === 'error'
            ||  empty($response->data->websiteList)
        ) {
            return '';
        }

        return implode(',', $response->data->websiteList);
    }

    /**
     * Get the product list
     * 
     * @param string $returntype Type of return value (array | dropDownOption)
     * 
     * @return array products
     */
    public static function getProductList($returntype = 'dropDownOption')
    {
        $qb = \Env::get('em')->createQueryBuilder();
        $qb->select('p')
                ->from('\Cx\Modules\Pim\Model\Entity\Product', 'p')
                ->where("p.entityClass = 'Cx\Core_Modules\MultiSite\Model\Entity\Website'")
                ->orWhere("p.entityClass = 'Cx\Core_Modules\MultiSite\Model\Entity\WebsiteCollection'")
                ->orderBy('p.id');
        $products =  $qb->getQuery()->getResult();
        
        $response = null;
        switch ($returntype) {
            case 'array':
                $response = $products;
                break;
            case 'dropDownOption':
            default:
                // Get all products to display in the dropdown.
                $productsList = array();
                foreach ($products as $product) {
                    $productsList[] = $product->getId() . ':' . $product->getName();
                }
                $response = implode(',', $productsList);
        }
        
        return $response;
    }
    
    /**
     * Get default product id
     * 
     * @return int productId
     */
    public static function getDefaultPimProductId()
    {
        $products = self::getProductList('array');
        if (\FWValidator::isEmpty($products)) {
            return 0;
        }
        
        $defaultProduct = current($products);
        if ($defaultProduct) {
            return $defaultProduct->getId();
        }
        return 0;
    }

    /**
     * Get the External Payment Customer Id Profile Attribute Id
     * 
     * @param string  $configOptionName config option name
     * @param string  $attributeName    attribute name
     * @param boolean $protection       write protection for the profile attribute
     * 
     * @return integer attribute id
     * @throws MultiSiteException
     */
    public static function getProfileAttributeIdByConfigOptionName($configOptionName, $attributeName, $protection = true) {
        $objUser = \FWUser::getFWUserObject()->objUser;
        
        $externalPaymentCustomerIdProfileAttributeId = \Cx\Core\Setting\Controller\Setting::getValue($configOptionName,'MultiSite');

        if ($externalPaymentCustomerIdProfileAttributeId) {
            $objProfileAttribute = $objUser->objAttribute->getById($externalPaymentCustomerIdProfileAttributeId);
            if ($objProfileAttribute->getId() != $externalPaymentCustomerIdProfileAttributeId) {
                $externalPaymentCustomerIdProfileAttributeId = false;
            }
        }
        if (!$externalPaymentCustomerIdProfileAttributeId) {
            if (!$attributeName) {
                return;
            }
            $externalIdInDatabase = $objUser->objAttribute->getAttributeIdByName($attributeName);
            if ($externalIdInDatabase) {
                return $externalIdInDatabase;
            }
            $objProfileAttribute = $objUser->objAttribute->getById(0);
            $objProfileAttribute->setNames(array(
                1 => $attributeName,
                2 => $attributeName
            ));
            $objProfileAttribute->setType('text');
            $objProfileAttribute->setParent(0);
            $objProfileAttribute->moveToEnd();
            if ($protection) {
                $objProfileAttribute->setProtection(array());
            }
            if (!$objProfileAttribute->store()) {
                throw new MultiSiteException('Failed to create ' . $attributeName);
            }
            
        }
        return $objProfileAttribute->getId();
    }
    
    /**
     * Used to get all the admin users and backend group users
     * 
     * @return array returns admin users
     */
    public static function getAllAdminUsers()
    {
        // check the mode
        $adminUsers = array();
        switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
            case ComponentController::MODE_WEBSITE:
                $em = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager();
                $userRepo = $em->getRepository('Cx\Core\User\Model\Entity\User');
                $users = $userRepo->findBy(array('isAdmin' => '1', 'active' => '1'));

                
                foreach ($users as $user) {
                    $adminUsers[$user->getId()] = $user;
                }

                $groupRepo = $em->getRepository('Cx\Core\User\Model\Entity\Group');
                $groups = $groupRepo->findBy(array('type' => 'backend'));

                foreach ($groups as $group) {
                    foreach ($group->getUser() as $user) {
                        if (!$user->getActive()) {
                            continue;
                        }
                        if (!array_key_exists($user->getId(), $adminUsers)) {
                            $adminUsers[$user->getId()] = $user;
                        }
                    }
                }
                break;
        }
        return $adminUsers;
    }
    
    /**
     * Get the backend group ids
     * 
     * @return array $backendGroupIds
     */
    public static function getBackendGroupIds() {
        $objFWUser       = \FWUser::getFWUserObject();
        $backendGroupIds = array();
        $objGroup = $objFWUser->objGroup->getGroups(array('type' => \Cx\Core\Core\Controller\Cx::MODE_BACKEND));
        if ($objGroup) {
            while (!$objGroup->EOF) {
                $backendGroupIds[] = $objGroup->getId();
                $objGroup->next();
            }
        }
        return $backendGroupIds;
    }
    
    /**
     * Get all the domains name
     * 
     * @return string $domainNames
     */
    public static function getAllDomainsName() {
        $domainRepo = new \Cx\Core\Net\Model\Repository\DomainRepository();
        $domains = $domainRepo->findAll();
        $domainNames = array();
        foreach ($domains as $domain) {
            $domainNames[] = $domain->getName();
        }
        return implode(',', $domainNames);
    }

    /**
     * Parse the message to the json output.
     * 
     * @param  string  $message message
     * @param  boolean $status  true | false (if status is true returns success json data)
     *                          if status is false returns error message json.
     * @param  boolean $reload  true | false
     * 
     * @return string
     */
    public function parseJsonMessage($message, $status, $reload=false) {
        $json = new \Cx\Core\Json\JsonData();
        if (is_array($message)) {
            $data = $message;
        } else {
            $data['message'] = $message;
        }
        $data['reload'] = $reload;
        
        if ($status) {
            return $json->json(new \Cx\Lib\Net\Model\Entity\Response(array(
                'status' => 'success',
                'data'   => $data,
            )));
        }

        if (!$status) {
            return $json->json(new \Cx\Lib\Net\Model\Entity\Response(array(
                'status'  => 'error',
                'message' => $message,
            )));
        }
    }
    
    /**
     * Post content load hook.
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Resolved page
     */
    public function postContentLoad(\Cx\Core\ContentManager\Model\Entity\Page $page)
    {
        self::showMaintenanceIndicationBar();
        self::loadAccountActivationBar();
        self::loadPoweredByFooter();
        self::loadContactInformationForm($page);
    }

    public function showMaintenanceIndicationBar()
    {
        // TODO add option to configure maintenance status from admin
        return;

        // only show in backend
        if ($this->cx->getMode() != \Cx\Core\Core\Controller\Cx::MODE_BACKEND) {
            return;
        }

        // Don't show account verification notice when in templateeditor
        if (isset($_GET['templateEditor'])) {
            return;
        }

        \JS::registerJS('core_modules/MultiSite/View/Script/MaintenanceIndication.js');
        \JS::registerCSS('core_modules/MultiSite/View/Style/MaintenanceIndicationBackend.css');

        $maintenanceIndicationBar = new \Cx\Core\Html\Sigma($this->cx->getCodeBaseCoreModulePath() . '/MultiSite/View/Template/Backend');
        $maintenanceIndicationBar->loadTemplateFile('MaintenanceIndication.html');

        $objTemplate = $this->cx->getTemplate();
        $objTemplate->_blocks['__global__'] = preg_replace('/<div id="container"[^>]*>/', '\\0' . $maintenanceIndicationBar->get(), $objTemplate->_blocks['__global__']);
    }

    /**
     * Get the account activation bar if user is not verified
     */
    public function loadAccountActivationBar()
    {
        global $_ARRAYLANG;
        
        // only show account-activation-bar if user is signed-in
        if (!\FWUser::getFWUserObject()->objUser->login()) {
            return;
        }

        \Cx\Core\Setting\Controller\Setting::init('MultiSite', '','FileSystem');
        $websiteUserId = \Cx\Core\Setting\Controller\Setting::getValue('websiteUserId','MultiSite');
        if (!$websiteUserId) {
            return;
        }

        $websiteUser = \FWUser::getFWUserObject()->objUser->getUser(\Cx\Core\Setting\Controller\Setting::getValue('websiteUserId','MultiSite'));
        if (!$websiteUser) {
            return;
        }
        
        if ($websiteUser->isVerified()) {
            return;
        }

        // Don't show account verification notice when in templateeditor
        if (isset($_GET['templateEditor'])) {
            return;
        }

        JsonMultiSiteController::loadLanguageData();
        $objTemplate = $this->cx->getTemplate();
        $warning = new \Cx\Core\Html\Sigma($this->cx->getCodeBaseCoreModulePath() . '/MultiSite/View/Template/Backend');
        $warning->loadTemplateFile('AccountActivation.html');

        $dueDate = '<span class="highlight">'.date(ASCMS_DATE_FORMAT_DATE, $websiteUser->getRestoreKeyTime()).'</span>';
        $email = '<span class="highlight">'.contrexx_raw2xhtml($websiteUser->getEmail()).'</span>';
        $reminderMsg = sprintf($_ARRAYLANG['TXT_MULTISITE_ACCOUNT_ACTIVATION_REMINDER'], $email, $dueDate);

        $warning->setVariable(array(
            'MULTISITE_ACCOUNT_ACTIVATION_REMINDER_MSG' => $reminderMsg,
            'TXT_MULTISITE_RESEND_ACTIVATION_CODE'      => $_ARRAYLANG['TXT_MULTISITE_RESEND_ACTIVATION_CODE'],
        ));

        \JS::registerJS('core_modules/MultiSite/View/Script/AccountActivation.js');

        if ($this->cx->getMode() == \Cx\Core\Core\Controller\Cx::MODE_BACKEND) {
            \JS::registerCSS('core_modules/MultiSite/View/Style/AccountActivationBackend.css');
            $objTemplate->_blocks['__global__'] = preg_replace('/<div id="container"[^>]*>/', '\\0' . $warning->get(), $objTemplate->_blocks['__global__']);
        } else {
            \JS::registerCSS('core_modules/MultiSite/View/Style/AccountActivationFrontend.css');
            $objTemplate->_blocks['__global__'] = preg_replace('/<body[^>]*>/', '\\0' . $warning->get(), $objTemplate->_blocks['__global__']);
        }
    }
    
    /**
     * Get the powered by footer content.
     */
    public function loadPoweredByFooter()
    {
        global $_ARRAYLANG;
        
        if (!($this->cx->getMode() == \Cx\Core\Core\Controller\Cx::MODE_FRONTEND)) {
            return;
        }

        // Don't show powered by footer when viewing template in templateeditor
        if (isset($_GET['templateEditor'])) {
            return;
        }

        $loadPoweredFooter = self::getModuleAdditionalDataByType('MultiSite', 'poweredbyfooter');
        
        if (empty($loadPoweredFooter)) {
            return;
        }
        
        if (isset($loadPoweredFooter['show']) && $loadPoweredFooter['show']) {
            $marketingWebsiteDomainName = isset($loadPoweredFooter['marketingWebsiteDomain']) ? $loadPoweredFooter['marketingWebsiteDomain'] : '';
            if (empty($marketingWebsiteDomainName)) {
                return;
            }
            
            $objTemplate = $this->cx->getTemplate();
            $footer = new \Cx\Core\Html\Sigma($this->cx->getCodeBaseCoreModulePath() . '/MultiSite/View/Template/Backend');
            $footer->loadTemplateFile('Footer.html');
            $footer->setVariable(array(
                'MULTISITE_POWERED_BY_FOOTER_LINK' => $marketingWebsiteDomainName,
                'MULTISITE_POWERED_BY_IMG_SRC'     => $this->cx->getCodeBaseCoreWebPath() .'/Core/View/Media/login_contrexx_logo.png',
                'TXT_MULTISITE_POWERED_BY_FOOTER'  => $_ARRAYLANG['TXT_MULTISITE_POWERED_BY_FOOTER'],
            ));

            \JS::registerCSS('core_modules/MultiSite/View/Style/PoweredByFooterFrontend.css');
            $objTemplate->_blocks['__global__'] = preg_replace(array('/<body>/', '/<\/body>/'), array('\\0' . '<div id="preview-content">', $footer->get() .'</div>' . '\\0' ), $objTemplate->_blocks['__global__']);
        }
        
    }
    
    /**
     * load the contact information form
     * 
     * @global array $_ARRAYLANG
     * @return null
     */
    
    public function loadContactInformationForm($page)
    {
       global $_ARRAYLANG;

        //check the mode
        if ($this->cx->getMode() !== \Cx\Core\Core\Controller\Cx::MODE_FRONTEND) {
            return;
        }
        
        switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
            case self::MODE_HYBRID:
            case self::MODE_MANAGER:
                // only show modal contact information modal if user is signed-in
                $objUser = \FWUser::getFWUserObject()->objUser;
                if (!$objUser->login()) {
                    return;
                }

                // do not show modal when trying to unsubscribe from notification mails
                if ($page->getModule() == 'MultiSite' && $page->getCmd() == 'NotificationUnsubscribe') {
                    return;
                }

                // do not show modal contact information modal if all mandatory fields are already set
                if ($objUser->checkMandatoryCompliance()) {
                    return;
                }

                $objTemplate = $this->cx->getTemplate();
                $objContactTpl = new \Cx\Core\Html\Sigma($this->cx->getCodeBaseCoreModulePath() . '/MultiSite/View/Template/Backend');
                $objContactTpl->loadTemplateFile('ContactInformation.html');

                $blockName = 'multisite_user';
                $placeholderPrefix = strtoupper($blockName) . '_';
                $objAccessLib = new \Cx\Core_Modules\Access\Controller\AccessLib($objContactTpl);
                $objAccessLib->setModulePrefix($placeholderPrefix);
                $objAccessLib->setAttributeNamePrefix($blockName . '_profile_attribute');
                $objAccessLib->setAccountAttributeNamePrefix($blockName . '_account_');

                $objUser->objAttribute->first();
                while (!$objUser->objAttribute->EOF) {
                    $objAttribute = $objUser->objAttribute->getById($objUser->objAttribute->getId());
                    $objAccessLib->parseAttribute($objUser, $objAttribute->getId(), 0, true, false, false, false, false);
                    $objUser->objAttribute->next();
                }
                $this->parseCrmInfoForModal($objUser, $objContactTpl);

                $objAccessLib->parseAccountAttributes($objUser);


                \Cx\Core\Setting\Controller\Setting::init('Crm', 'config');
                $objContactTpl->setVariable(array(
                    'TXT_CORE_MODULE_MULTISITE_CONTACT_INFO_TITTLE'             => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CONTACT_INFO_TITTLE'],
                    'TXT_CORE_MODULE_MULTISITE_CONTACT_INFO_CONTENT'            => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CONTACT_INFO_CONTENT'],
                    'TXT_CORE_MODULE_MULTISITE_MANDATORY_FIELDS_REQUIRED_MSG'   => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MANDATORY_FIELDS_REQUIRED_MSG'],
                    'TXT_CORE_MODULE_MULTISITE_LOADING_TEXT'                    => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_LOADING_TEXT'],
                    'TXT_CORE_MODULE_MULTISITE_SAVE'                            => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SAVE'],
                    'TXT_CORE_MODULE_MULTISITE_USER_ACCOUNT_INDUSTRY_TYPE'      => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_USER_ACCOUNT_INDUSTRY_TYPE'],
                    'TXT_CORE_MODULE_MULTISITE_USER_ACCOUNT_COMPANY_SIZE'       => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_USER_ACCOUNT_COMPANY_SIZE'],
                    'TXT_CORE_MODULE_MULTISITE_USER_ACCOUNT_COMPANY_TYP'        => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_USER_ACCOUNT_COMPANY_TYP'],
                    'TXT_CORE_MODULE_MULTISITE_PLEASE_SELECT'                   => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_PLEASE_SELECT'],
                    'TXT_CORE_PHONE'                                            => $_ARRAYLANG['TXT_CORE_PHONE'],
                    'MULTISITE_CONTACT_INFO_SUBMIT_URL'                         => \Env::get('cx')->getWebsiteBackendPath() . '/index.php?cmd=JsonData&object=MultiSite&act=updateOwnUser',
                    'MULTISITE_CUSTOMER_TYPE_ATTRIBUT_ID'                         => \Cx\Core\Setting\Controller\Setting::getValue('user_profile_attribute_customer_type','Crm'),
                    'MULTISITE_INDUSTRY_TYPE_ATTRIBUT_ID'                         => \Cx\Core\Setting\Controller\Setting::getValue('user_profile_attribute_industry_type','Crm'),
                ));
                $objTemplate->_blocks['__global__'] = preg_replace('/<\/body>/', $objContactTpl->get() . '\\0', $objTemplate->_blocks['__global__']);
                break;

            default:
                break;
        }
    }

    /**
     * If the $dropdownView is true, load dropdowns for industry type, company size and customer type out of crm and parse them in the
     * contact data modal template. If the user has already set them, we fill them in for him, so he only needs to change
     * it if he wants to.
     * if the $dropdownView is false, only parse the value of attributes industry type, company size and customer type
     *
     * @access protected
     * @author Adrian Berger <ab@comvation.com>
     *
     * @global <type> $_ARRAYLANG
     * @param objUser $objUser
     * @param object $objContactTpl
     * @param boolean $dropdownView
     */
    protected function parseCrmInfoForModal($objUser, $objContactTpl, $dropdownView = true) {
        global $_ARRAYLANG;

        // initialize crm config, so we can get the values out of it
        \Cx\Core\Setting\Controller\Setting::init('Crm', 'config');

        $crmComponent = new \Cx\Modules\Crm\Controller\CrmManager('crm');
        //get the profile attribute id of industry type, company size and customer type
        $companySizeAttrId  = \Cx\Core\Setting\Controller\Setting::getValue('user_profile_attribute_company_size','Crm');
        $industryTypeAttrId = \Cx\Core\Setting\Controller\Setting::getValue('user_profile_attribute_industry_type','Crm');
        $customerTypeAttrId = \Cx\Core\Setting\Controller\Setting::getValue('user_profile_attribute_customer_type','Crm');

        // get the value of profile attributes industry type, company size and customer type
        $companySizeAttrValue  = $objUser->getProfileAttribute($companySizeAttrId);
        $industryTypeAttrValue = $objUser->getProfileAttribute($industryTypeAttrId);
        $customerTypeAttrValue = $objUser->getProfileAttribute($customerTypeAttrId);

        if ($dropdownView) {
        $objContactTpl->setGlobalVariable(array(
            'CRM_INDUSTRY_DROPDOWN'     => $crmComponent->listIndustryTypes(
                                                $crmComponent->_objTpl,
                                                2,
                                                                $industryTypeAttrValue
                                            ),
            'TXT_CRM_PLEASE_SELECT'     => $_ARRAYLANG['TXT_CRM_COMMENT_DESCRIPTION'],
                'MULTISITE_INDUSTRY_TYPE_POST_NAME' => 'multisite_user_profile_attribute['. $industryTypeAttrId .'][0]',
                'MULTISITE_CUSTOMER_TYPE_POST_NAME' => 'multisite_user_profile_attribute['. $customerTypeAttrId .'][0]',
                'MULTISITE_COMPANY_SIZE_POST_NAME'  => 'multisite_user_profile_attribute['. $companySizeAttrId .'][0]'
        ));

            $crmComponent->getCompanySizeDropDown($objContactTpl, $companySizeAttrValue);

            $crmComponent->getCustomerTypeDropDown($objContactTpl, $customerTypeAttrValue);
        } else {
            $objContactTpl->setGlobalVariable(array(
                'MULTISITE_USER_INDUSTRY_TYPE_VALUE' => contrexx_raw2xhtml($crmComponent->getIndustryTypeNameById($industryTypeAttrValue)),
                'MULTISITE_USER_CUSTOMER_TYPE_VALUE' => contrexx_raw2xhtml($crmComponent->getCustomerTypeNameById($customerTypeAttrValue)),
                'MULTISITE_USER_COMPANY_SIZE_VALUE'  => contrexx_raw2xhtml($crmComponent->getCompanySizeNameById($companySizeAttrValue))
            ));
        }
    }


    /**
     * Get User Currency Object
     * 
     * @param type $crmContactId crmContactId
     * 
     * @return mixed  \Cx\Modules\Crm\Model\Entity\Currency or null
     */
    public static function getUserCurrency($crmContactId = 0)
    {
        $crmCurrencyId = 0;
        
        if (!\FWValidator::isEmpty($crmContactId)) {
            $crmCurrencyId = \Cx\Modules\Crm\Controller\CrmLibrary::getCurrencyIdByCrmId($crmContactId);
        }
        
        $currencyId = !\FWValidator::isEmpty($crmCurrencyId)
                       ? $crmCurrencyId
                       : \Cx\Modules\Crm\Controller\CrmLibrary::getDefaultCurrencyId();
        
        if (\FWValidator::isEmpty($currencyId)) {
            return null;
        }
        
        $currency = \Env::get('em')->getRepository('Cx\Modules\Crm\Model\Entity\Currency')->findOneById($currencyId);
        return $currency;
    }

    public function preFinalize(\Cx\Core\Html\Sigma $template) {
        global $_ARRAYLANG;

        \Env::get('init')->loadLanguageData('MultiSite');

        $this->cx->getTemplate()->setVariable(
            array(
                'MULTISITE_AGB_URL' => \Cx\Core\Setting\Controller\Setting::getValue('termsUrl','MultiSite'),
                'TXT_MULTISITE_ACCEPT_TERMS_URL_NAME' => $_ARRAYLANG['TXT_MULTISITE_ACCEPT_TERMS_URL_NAME'],
            )
        );
    }

    /**
     * Check whether the affiliate id is valid or not
     * 
     * @param string $affiliateId User affiliate id
     * 
     * @return boolean True, when affiliate id is valid false otherwise
     */
    public static function isValidAffiliateId($affiliateId)
    {
        $affiliateIdProfileAttributeId = \Cx\Core\Setting\Controller\Setting::getValue('affiliateIdProfileAttributeId', 'MultiSite');
        $filter = array(
            $affiliateIdProfileAttributeId => $affiliateId
        );
        $objUser = \FWUser::getFWUserObject()->objUser->getUsers($filter);
        if (!$objUser) {
            return false;
        }
        $userExists = false;
        while (!$objUser->EOF) {
            if ($affiliateId == $objUser->getProfileAttribute($affiliateIdProfileAttributeId)) {
                $userExists = true;
            }
            $objUser->next();
        }
        return $userExists;
    }
    
    /**
     * Get the subscriptions count based on the Product for referrals subscribe
     * 
     * @param integer $affiliateId
     * 
     * @return array
     */
    public static function getReferralsSubscriptionIdsBasedOnProduct($affiliateId) {
        $affiliateIdReferenceProfileAttributeId = \Cx\Core\Setting\Controller\Setting::getValue('affiliateIdReferenceProfileAttributeId','MultiSite');
        $objUser = \FWUser::getFWUserObject()->objUser->getUsers(array(
             $affiliateIdReferenceProfileAttributeId => $affiliateId
        ));
        
        $soloSubscriptions = array();
        $nonProfitSubscriptions = array();
        $businessSubscriptions = array();
        
        if (!$objUser) {
            return array();
        }

        $subscriptionRepo = \Env::get('em')->getRepository('\Cx\Modules\Order\Model\Entity\Subscription');
        while (!$objUser->EOF) {
            $userAffiliateId = $objUser->getProfileAttribute($affiliateIdReferenceProfileAttributeId);
            if ($userAffiliateId === $affiliateId) {
                $criteria = array('o.contactId'   => $objUser->getCrmUserId(),
                                  'p.entityClass' => 'Cx\\Core_Modules\\MultiSite\\Model\\Entity\\WebsiteCollection');
                $subscriptions = $subscriptionRepo->getSubscriptionsByCriteria($criteria);
                if (!empty($subscriptions)) {
                    foreach ($subscriptions as $subscription) {
                        $productObj = $subscription->getProduct();
                        switch ($productObj->getName()) {
                            case 'Business':
                                $businessSubscriptions[]  = $subscription->getId();
                                break;
                            case 'Solo':
                                $soloSubscriptions[]      = $subscription->getId();
                                break;
                            case 'Non-Profit':
                                $nonProfitSubscriptions[] = $subscription->getId();
                                break;
                        }
                    }
                }
            }
            $objUser->next();
        }

        if (   empty($soloSubscriptions)
            && empty($nonProfitSubscriptions)
            && empty($businessSubscriptions)
           ) {
            return array();
        }
        
        return array(
            'Solo'       => $soloSubscriptions,
            'Non-Profit' => $nonProfitSubscriptions,
            'Business'   => $businessSubscriptions
        );
    }
    
    /**
     * Get the subscription using the websiteId
     * 
     * @param string $websiteId websiteId
     * @return mixed boolean or subscription
     */
    public static function getSubscriptionByWebsiteId($websiteId) {
        global $_ARRAYLANG;
        
        if (empty($websiteId)) {
            \DBG::log(__METHOD__. ' : '.$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_PARAMS']);
            return false;
        }
        
        $websiteServiceRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
        $website = $websiteServiceRepo->findOneById($websiteId);
        if (!$website) {
            \DBG::log(__METHOD__. ' : '.$_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_EXISTS']);
            return false;
        }

        $websiteCollection = $website->getWebsiteCollection();
        $subscriptionRepo  = \Env::get('em')->getRepository('Cx\Modules\Order\Model\Entity\Subscription');
        $subscriptions     = !empty($websiteCollection)
                             ? $subscriptionRepo->getSubscriptionsByCriteria(array(
                                'in'            => array(
                                                        array('s.productEntityId', array($websiteCollection->getId()))
                                                    ),
                                'p.entityClass' => 'Cx\\Core_Modules\\MultiSite\\Model\\Entity\\WebsiteCollection'
                               ))
                             : $subscriptionRepo->findOneBy(array(
                                'productEntityId' => $websiteId
                               ));

        $subscription = is_array($subscriptions) ? current($subscriptions) : $subscriptions;
        return $subscription;
    }
    
    /**
     * Get custom access user profile attributes for settings dropdown
     * 
     * @return string
     */
    public static function getCustomAccessUserProfileAttributes()
    {
        $customAccessUserProfileAttributes = array();
        
        $userProfileAttributes = \User_Profile_Attribute::getCustomAttributeNameArray();
        foreach ($userProfileAttributes as $id => $name) {
            $customAccessUserProfileAttributes[] = $id . ':' . $name;
        }
        return implode(', ', $customAccessUserProfileAttributes);
    }
    
    /**
     * Get the service server object by id
     * 
     * @param array $criteria criterias
     * 
     * @return mixed boolean|Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer
     */
    public static function getServiceServerByCriteria($criteria = array())
    {
        if (empty($criteria)) {
            return false;
        }
        
        $serviceServerRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer');
        $serviceServer = $serviceServerRepo->findOneBy($criteria);
        if (empty($serviceServer)) {
            return false;
        }
        
        return $serviceServer;
    }

    /**
     * Get the product ids by entity class
     * 
     * @param string $entity namespace of Website or WebsiteCollections
     * 
     * @return array
     */
    public function getProductIdsByEntityClass($entity) {
        $em          = $this->cx->getDb()->getEntityManager();
        $entityClass = 'Cx\\Core_Modules\\MultiSite\\Model\\Entity\\'.$entity;
        $products    = $em->getRepository('\Cx\Modules\Pim\Model\Entity\Product')->findBy(array('entityClass' => $entityClass));
        $productIds  = array();
        
        if ($products) {
            foreach ($products as $product) {
                $productIds[] = $product->getId();
            }
        }
        return $productIds;
    }

    /**
     * Verify the website is available to take backup
     * 
     * @param integer                                                      $websiteId     Website id
     * @param \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer $serviceServer Website service server
     * 
     * @return boolean True when website is available to take backup, false otherwise
     * 
     * @throws MultiSiteException
     */
    public function verifyWebsiteBackupLimit($websiteId, \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer $serviceServer)
    {
        if (!in_array(\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite'), array(self::MODE_MANAGER, self::MODE_HYBRID))) {
            throw  new MultiSiteException(__METHOD__ .' : Support only on mode manager and hybrid');
        }

        if (empty($websiteId)) {
            throw  new MultiSiteException(__METHOD__ .' : Website Id empty');
        }

        $websiteBackupLimit = \Cx\Core\Setting\Controller\Setting::getValue('websiteBackupLimit','MultiSite');
        if (empty($websiteBackupLimit)) {
            return true;
        }

        $resp = JsonMultiSiteController::executeCommandOnServiceServer('getWebsiteSize', array('websiteId' => $websiteId), $serviceServer);
        if (   !$resp
            || $resp->status != 'success'
            || $resp->data->status != 'success'
            || $resp->data->size > $websiteBackupLimit
        ) {
            return false;
        }

        return true;
    }
}
