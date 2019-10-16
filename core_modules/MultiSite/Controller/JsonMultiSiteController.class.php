<?php


/**
 * JSON Adapter for Multisite
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Sudhir Parmar <sudhirparmar@cdnsol.com>
 * @version     4.0.0
 * @package     contrexx
 * @subpackage  Multisite
*/

namespace Cx\Core_Modules\MultiSite\Controller;

class MultiSiteJsonException extends \Exception {
    protected $object;
    protected $type = 'danger';

    /**
     * Overwriting the default Exception constructor
     * The default Exception constructor only accepts $message to be a string.
     * We do overwrite this here to also allow $message to be an array
     * that can then be sent back in the JsonData-response.
     */
    public function __construct($message = "", $code = 0, \Exception $previous = null) {
        if (is_array($message)) {
            $msg = $message['message'];
            if (isset($message['object'])) {
                $this->object = $message['object'];
            }
            if (isset($message['type'])) {
                $this->type = $message['type'];
            }
        } else {
            $msg = $message;
        }
        parent::__construct($msg, $code, $previous);
        // overwrite $message to pass exception data to JsonData
        $this->message=$message;
    }
    
    public function getObject() {
        return $this->object;
    }

    public function getType() {
        return $this->type;
    }
}
/**
 * JSON Adapter for Multisite
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Sudhir Parmar <sudhirparmar@cdnsol.com>
 * @version     4.0.0
 * @package     contrexx
 * @subpackage  Multisite
*/
class JsonMultiSiteController extends    \Cx\Core\Core\Model\Entity\Controller 
                              implements \Cx\Core\Json\JsonAdapter {

    /**
     * @var boolean 
     */
    static $isIscRequest = false;
    
    /**
     * Returns the internal name used as identifier for this adapter
     * @return String Name of this adapter
     */
    public function getName() {
        return 'MultiSite';
    }

    /**
     * Returns an array of method names accessable from a JSON request
     * @return array List of method names
     */
    public function getAccessableMethods() {
        $multiSiteProtocol = (\Cx\Core\Setting\Controller\Setting::getValue('multiSiteProtocolIn','MultiSite') == 'mixed')? $this->cx->getRequest()->getUrl()->getProtocol(): \Cx\Core\Setting\Controller\Setting::getValue('multiSiteProtocolIn','MultiSite');
        $permissionCbAuthAnyProtocol = new \Cx\Core_Modules\Access\Model\Entity\Permission(array('http', 'https'), array('post'), false, array(), array(), array($this, 'auth'));
        $permissionCbAuthForcedProtocol = new \Cx\Core_Modules\Access\Model\Entity\Permission(array($multiSiteProtocol), array('post'), false, array(), array(), array($this, 'auth'));
        $permissionCbByModeAnyProtocol = new \Cx\Core_Modules\Access\Model\Entity\Permission(array('http', 'https'), array('post'), false, array(), array(), array($this, 'checkAuthenticationByMode'));
        $permissionCbByModeForcedProtocol = new \Cx\Core_Modules\Access\Model\Entity\Permission(array($multiSiteProtocol), array('post'), false, array(), array(), array($this, 'checkAuthenticationByMode'));
        $permissionCbCheckLicense = new \Cx\Core_Modules\Access\Model\Entity\Permission(array('http', 'https'), array('post'), false, array(), array(), array($this, 'checkGetLicenseAccess'));
        $permissionCbVerifyOwner = new \Cx\Core_Modules\Access\Model\Entity\Permission(array($multiSiteProtocol), array('post'), false, array(), array(), array($this, 'verifyWebsiteOwnerOrIscRequest'));
        $permissionPostRequest = new \Cx\Core_Modules\Access\Model\Entity\Permission(array($multiSiteProtocol), array('post'), false);
        $permissionMultiSiteAccess = new \Cx\Core_Modules\Access\Model\Entity\Permission(array($multiSiteProtocol), array('post'), true, array(), array(183));
        $permissionPostAuthenticated = new \Cx\Core_Modules\Access\Model\Entity\Permission(array($multiSiteProtocol), array('post'), true);
        return array(
            'signup'                => new \Cx\Core_Modules\Access\Model\Entity\Permission(array($multiSiteProtocol), array('post'), false, array(), array(), array($this, 'verifySignupRequest')),
            'email'                 => $permissionPostRequest,
            'address'               => $permissionPostRequest,
            'createWebsite'         => $permissionCbAuthForcedProtocol,
            // protocol workaround as option multiSiteProtocol is not set on WEBSITE
            'createUser'            => $permissionCbAuthAnyProtocol,
            'updateUser'            => $permissionCbAuthAnyProtocol,
            'updateOwnUser'         => $permissionPostAuthenticated,
            'mapDomain'             => $permissionCbAuthAnyProtocol,
            'unMapDomain'           => $permissionCbAuthAnyProtocol,
            'updateDomain'          => $permissionCbAuthAnyProtocol,
            'updateDefaultCodeBase' => $permissionMultiSiteAccess,
            'setWebsiteDetails'     => $permissionCbAuthForcedProtocol,
            'updateWebsiteState'    => $permissionMultiSiteAccess,
            'ping'                  => $permissionCbAuthForcedProtocol,
            'pong'                  => $permissionCbAuthForcedProtocol,
            'setLicense'            => $permissionCbAuthAnyProtocol,
            'setupConfig'           => $permissionCbAuthAnyProtocol,
            'getDefaultWebsiteIp'   => $permissionCbAuthForcedProtocol,
            'resetFtpPassword'      => new \Cx\Core_Modules\Access\Model\Entity\Permission(array('http', 'https'), array('post'), false, array(), array(), array($this, 'checkResetFtpPasswordAccess')),
            'updateServiceServerSetup' => $permissionCbAuthForcedProtocol,
            'destroyWebsite'        => $permissionCbVerifyOwner,
            'executeOnWebsite'      => $permissionCbAuthForcedProtocol,
            'executeOnManager'      => $permissionCbAuthForcedProtocol,
            'generateAuthToken'     => $permissionCbAuthAnyProtocol,
            'executeSql'            => $permissionCbByModeAnyProtocol,
            'removeUser'            => $permissionCbAuthAnyProtocol,
            'setWebsiteTheme'       => $permissionCbAuthAnyProtocol,
            'getFtpUser'            => $permissionCbAuthAnyProtocol,
            'getFtpAccounts'        => $permissionCbAuthAnyProtocol,
            'getLicense'            => $permissionCbCheckLicense,
            'remoteLogin'           => $permissionMultiSiteAccess,
            'editLicense'           => $permissionCbCheckLicense,
            'executeQueryBySession' => $permissionMultiSiteAccess,
            'stopQueryExecution'    => $permissionMultiSiteAccess,
            'modifyMultisiteConfig' => $permissionCbCheckLicense,
            'sendAccountActivation' => new \Cx\Core_Modules\Access\Model\Entity\Permission(array('http', 'https'), array('post'), false, array(), array(), array($this, 'checkSendAccountActivation')),
            'getPayrexxUrl'         => $permissionPostRequest,
            'push'                  => $permissionCbAuthAnyProtocol,
            'websiteBackup'         => $permissionCbAuthForcedProtocol,
            'triggerWebsiteBackup'  => $permissionPostAuthenticated,
            'triggerWebsiteRestore' => $permissionPostAuthenticated,
            'getWebsiteInfo'        => $permissionCbAuthForcedProtocol,
            'deleteWebsiteBackup'   => $permissionCbAuthForcedProtocol,
            'downloadWebsiteBackup' => $permissionCbAuthForcedProtocol,
            'getAllBackupFilesInfo' => $permissionCbAuthForcedProtocol,
            'sendFileToRemoteServer'=> $permissionCbAuthForcedProtocol,
            'updateWebsiteConfig'   => $permissionCbAuthAnyProtocol,
            'updateWebsiteSubscriptionInfo'     => $permissionCbAuthForcedProtocol,
            'websiteRestore'        => $permissionCbAuthForcedProtocol,
            'addNewWebsiteInSubscription' => $permissionCbAuthAnyProtocol,
            'checkUserStatusOnRestore' => $permissionPostAuthenticated,
            'getUserInfoFromBackup'   => $permissionCbAuthAnyProtocol,
            'getAvailableSubscriptionsByUserId' => $permissionPostAuthenticated,
            'websiteLogin'          => $permissionPostAuthenticated,
            'getAdminUsers'         => $permissionCbAuthAnyProtocol,
            'getUser'               => $permissionCbAuthAnyProtocol,
            'getResourceUsageStats' => $permissionCbAuthAnyProtocol,
            'enableMailService'     => $permissionCbVerifyOwner,
            'disableMailService'    => $permissionCbVerifyOwner,
            'resetEmailPassword'    => $permissionCbVerifyOwner,
            'getMailServiceStatus'  => $permissionCbVerifyOwner,
            'createMailServiceAccount' => $permissionCbAuthForcedProtocol,
            'deleteMailServiceAccount' => $permissionCbAuthForcedProtocol,
            'manageSubscription'   => $permissionPostAuthenticated,
            'getPanelAutoLoginUrl' => $permissionPostAuthenticated,
            'mapNetDomain'          => $permissionCbAuthAnyProtocol,
            'unMapNetDomain'        => $permissionCbAuthAnyProtocol,
            'updateNetDomain'       => $permissionCbAuthAnyProtocol,
            'createAdminUser'       => $permissionCbAuthAnyProtocol,
            'payrexxAutoLoginUrl'     => $permissionPostAuthenticated,
            'updateOwnWebsiteState'  => $permissionCbVerifyOwner,
            'getMainDomain'         => $permissionCbAuthAnyProtocol,
            'setMainDomain'         => $permissionCbAuthAnyProtocol,
            'deleteAccount'         => new \Cx\Core_Modules\Access\Model\Entity\Permission(array('http', 'https'), array('post'), true),   
            'isComponentLicensed'  => $permissionCbAuthAnyProtocol,
            'getModuleAdditionalData' => $permissionCbAuthAnyProtocol,
            'changePlanOfMailSubscription' => $permissionCbAuthAnyProtocol,
            'domainManipulation'    => $permissionCbAuthAnyProtocol,
            'isUniqueEmail'         => $permissionCbAuthForcedProtocol,
            'getMailServicePlans'   => $permissionMultiSiteAccess,
            'trackAffiliateId'      => new \Cx\Core_Modules\Access\Model\Entity\Permission(array('http', 'https'), array('get', 'post'), false),
            'checkAvailabilityOfAffiliateId' => $permissionPostAuthenticated,
            'setAffiliate'        => $permissionPostAuthenticated,
            'sendNotificationForPayoutRequest' => $permissionPostAuthenticated,
            'getCodeBaseVersions' => $permissionCbByModeForcedProtocol,
            'getWebsitesByCodeBase' => $permissionCbByModeForcedProtocol,
            'triggerWebsiteUpdate' => $permissionCbByModeForcedProtocol,
            'websiteUpdate' => $permissionCbAuthAnyProtocol,
            'sendUpdateNotification' => $permissionCbAuthAnyProtocol,
            'updateWebsiteCodeBase' => $permissionCbAuthAnyProtocol,
            'copyWebsite' => $permissionPostAuthenticated,
            'getDomainSslCertificate' => $permissionCbAuthAnyProtocol,
            'linkSsl'                 => $permissionCbAuthAnyProtocol,
            'setWebsiteOwner' => $permissionCbAuthAnyProtocol,
            'getWebsiteSize' => $permissionCbAuthAnyProtocol,
            'getServerWebsiteList' => $permissionCbAuthAnyProtocol,
            'checkServerWebsiteAccessedByClient' => $permissionCbAuthAnyProtocol,
            'updateAutomaticCertificates' => $permissionCbAuthAnyProtocol,
            'getServerWebsitePath' => $permissionCbAuthAnyProtocol,
        );  
    }

    /**
     * Returns all messages as string
     * @return String HTML encoded error messages
     */
    public function getMessagesAsString() {
        return '';
    }
    
    /**
     * Returns default permission as object
     * @return Object
     */
    public function getDefaultPermissions() {
        return null;
    }

    /**
     * Check if there is already an account present
     * by the supplied email address.
     * @param array $params supplied arguments from JsonData-request
     * @return void Returns nothing in case the email is not yet registered
     * @throws MultiSiteJsonException An array with further information about the already used email address
     */
    public function email($params) {
        global $_ARRAYLANG;

        if (!isset($params['post']['multisite_email_address'])) {
            return;
        }

        //check email validity
        if (!\FWValidator::isEmail($params['post']['multisite_email_address'])) {
            self::loadLanguageData();
            throw new MultiSiteJsonException(array(
                'object'    => 'email',
                'type'      => 'danger',
                'message'   => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_INVALID_EMAIL'],
            ));
        }

        if (!\User::isUniqueEmail($params['post']['multisite_email_address'])) {
            self::loadLanguageData();

            $loginUrl = \Cx\Core\Routing\Url::fromMagic(ASCMS_PROTOCOL . '://' . \Cx\Core\Setting\Controller\Setting::getValue('customerPanelDomain','MultiSite') . '/')->toString();
            $loginLink = '<a class="alert-link" href="'.$loginUrl.'" target="_blank">'.$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_LOGIN'].'</a>';
            throw new MultiSiteJsonException(array(
                'object'    => 'email',
                'type'      => 'info',
                'message'   => sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_EMAIL_IN_USE'], $loginLink),
            ));
        }
    }

    public static function loadLanguageData() {
        global $_ARRAYLANG, $_CORELANG, $objInit;
        $langData = $objInit->loadLanguageData('MultiSite');
        $_ARRAYLANG = array_merge($_ARRAYLANG, $langData);
        
        $coreLangData = $objInit->loadLanguageData('core');
        $_CORELANG = array_merge($_ARRAYLANG, $coreLangData);
    }

    public function address($params) {
        if (!isset($params['post']['multisite_address'])) {
            return;
        }
        try {
            $websiteName = contrexx_input2raw($params['post']['multisite_address']);
            $website = new \Cx\Core_Modules\MultiSite\Model\Entity\Website(null, $websiteName);
            $website->validate();
        } catch (\Cx\Core_Modules\MultiSite\Model\Entity\WebsiteException $e) {
            throw new MultiSiteJsonException(array(
                'object'    => 'address',
                'type'      => 'warning',
                'message'   => $e->getMessage()
            ));
        }
    }

    /**
     * @param post parameters
     */
    public function signup($params) {
        global $_ARRAYLANG;

        // required to allow the AJAX request send the MultiSiteAffiliate COOKIE
        header('Access-Control-Allow-Credentials: true');

        // abort in case command has been requested without any data
        if (empty($params['post'])) {
            return;
        }

        // activate memory-logging
        $this->activateDebuggingToMemory();

        // Validate address and email before starting with the actual sign up process.
        // Those methods throw an individual exception that can be parsed by the sign up form.
        // Therefore, those shall not be called in the below try/catch block
        $this->address($params);
        $this->email($params);

        try {
            // load text-variables of module MultiSite
            self::loadLanguageData();

            // set website name and website theme
            $websiteName = contrexx_input2raw($params['post']['multisite_address']);
            $websiteThemeId = !empty($params['post']['themeId']) ? contrexx_input2raw($params['post']['themeId']) : null;
            $serviceServerId = !empty($params['post']['serviceServerId']) 
                               ? contrexx_input2int($params['post']['serviceServerId'])
                               : 0;

            // create new user account
            $arrSettings = \User_Setting::getSettings();
            $user = $this->createUser(array('post' => array(
                'active'=> true,
                'email' => $params['post']['multisite_email_address'],
                'groups'=> explode(',', $arrSettings['assigne_to_groups']['value']),
                'newsletter_lists' => array(2),
            )), true);

            $objFWUser = \FWUser::getFWUserObject();
            $objUser = $objFWUser->objUser->getUser(intval($user['userId']));
            if (!$objUser) {
                throw new MultiSiteJsonException("Unable to load user account {$user['userId']}.");
            }

            // create a new CRM Contact and link it to the User account
            \Cx\Modules\Crm\Controller\CrmLibrary::addCrmContactFromAccessUser($objUser);
        
            // TODO: Product ID should be supplied by POST-data.
            //       If not set, then the ID should be taken from a MultiSite configuration option 'defaultProductId'
            $id = isset($params['post']['product_id']) ? contrexx_input2raw($params['post']['product_id']) : \Cx\Core\Setting\Controller\Setting::getValue('defaultPimProduct','MultiSite');

            // create new subscription of selected product
            $renewalOption = isset($params['post']['renewalOption']) ? $params['post']['renewalOption'] : 'monthly';
	    list($renewalUnit, $renewalQuantifier) = self::getProductRenewalUnitAndQuantifier($renewalOption);
            $subscriptionOptions = array(
                'renewalUnit'       => $renewalUnit,
                'renewalQuantifier' => $renewalQuantifier,
                'websiteName'       => $websiteName,
                'customer'          => $objUser,
            );
            
            //pass the website's theme id to subscription option, if $themeId set
            if (!empty($websiteThemeId)) {
                $subscriptionOptions['themeId'] = $websiteThemeId;
            }
            
            //pass the service server id to the subscription option when the $serviceServerId is set
            if (!empty($serviceServerId)) {
                $subscriptionOptions['serviceServerId'] = $serviceServerId;
            }
            
            $transactionReference = "|$id|name|$websiteName|";
            $currency = ComponentController::getUserCurrency($objUser->getCrmUserId());
            $order = \Env::get('em')->getRepository('Cx\Modules\Order\Model\Entity\Order')->createOrder($id, $currency, $objUser, $transactionReference, $subscriptionOptions);
            if (!$order) {
                throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ORDER_FAILED']);
            }

            // Set user's verification state to pending in case the related
            // password-setup-method has been configured.
            if (\Cx\Core\Setting\Controller\Setting::getValue('passwordSetupMethod','MultiSite') == 'auto-with-verification') {
                \DBG::msg('HOTFIX: set verification state to pending on Cloudrexx user..');
                // set state of user account to unverified
                $objUser->setVerification(false);
                $objUser->store();
            }

            // create the website process in the payComplete event
            $order->complete(); 
            
            // fetch the newly build website from the repository
            $websiteRepository = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
            $website   = $websiteRepository->findOneBy(array('name' => $websiteName));

// TODO: remove once setup process works flawlessly
            // send setup protocol anyway
            if (\Cx\Core\Setting\Controller\Setting::getValue('sendSetupError','MultiSite')) {
                $config = \Env::get('config');
                \Cx\Core\MailTemplate\Controller\MailTemplate::init('MultiSite');
                \Cx\Core\MailTemplate\Controller\MailTemplate::send(array(
                    'section' => 'MultiSite',
                    'key' => 'setupError',
                    'to' => $config['coreAdminEmail'],
                    'search' => array(
                        '[[ERROR]]',
                        '[[WEBSITE_NAME]]',
                        '[[CUSTOMER_EMAIL]]',
                        '[[DBG_LOG]]',
                    ),
                    'replace' => array(
                        'SETUP SUCCESSFUL',
                        $websiteName,
                        $params['post']['multisite_email_address'],
                        join("\n", \DBG::getMemoryLogs()),
                    ),
                ));
            }

            $authToken = null;
            $autoLoginUrl = null;
            if (\Cx\Core\Setting\Controller\Setting::getValue('autoLogin','MultiSite')) {
                \DBG::msg('Website: generate auth-token for Cloudrexx user..');
                try {
                    list($ownerUserId, $authToken) = $website->generateAuthToken();
                    $autoLoginUrl = \Cx\Core\Routing\Url::fromMagic(ComponentController::getApiProtocol() . $website->getBaseDn()->getName() . $this->cx->getWebsiteBackendPath() . '/?user-id='.$ownerUserId.'&auth-token='.$authToken);
                } catch (\Cx\Core_Modules\MultiSite\Model\Entity\WebsiteException $e) {
                    \DBG::msg($e->getMessage());
                }
            }

            if ($autoLoginUrl) {
                return array(
                    'status'    => 'success',
                    'message'   => 'auto-login',
                    'loginUrl'  => $autoLoginUrl->toString(),
                );
            } else {
                $websiteLink = '<a href="'.ComponentController::getApiProtocol().$website->getBaseDn()->getName().'" target="_blank">'.$website->getBaseDn()->getName().'</a>';
                return array(
                    'status'    => 'success',
                    'message'   => sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_CREATED'], $websiteLink),
                );
            }
        } catch (\Exception $e) {
            $config = \Env::get('config');
            if (\Cx\Core\Setting\Controller\Setting::getValue('sendSetupError','MultiSite')) {
                \Cx\Core\MailTemplate\Controller\MailTemplate::init('MultiSite');
                \Cx\Core\MailTemplate\Controller\MailTemplate::send(array(
                    'section' => 'MultiSite',
                    'key' => 'setupError',
                    'to' => $config['coreAdminEmail'],
                    'search' => array(
                        '[[ERROR]]',
                        '[[WEBSITE_NAME]]',
                        '[[CUSTOMER_EMAIL]]',
                        '[[DBG_LOG]]',
                    ),
                    'replace' => array(
                        $e->getMessage(),
                        $websiteName,
                        $params['post']['multisite_email_address'],
                        join("\n", \DBG::getMemoryLogs()),
                    ),
                ));
            }
            \DBG::msg($e->getMessage());
            throw new MultiSiteJsonException(array(
                'object'    => 'form',
                'type'      => 'danger',
                'message'   => sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_CREATION_ERROR'], contrexx_raw2xhtml($objUser->getEmail())),
            ));
        }
    }

    /**
     * Manage Subscription 
     * 
     * @param array $params parameters
     * 
     * @return array
     * @throws MultiSiteJsonException
     */
    public function manageSubscription($params) {
        global $_ARRAYLANG;
        
        // abort in case command has been requested without any data
        if (empty($params['post'])) {
            return;
        }
        
        // activate file logging to debug payment handling
        \DBG::deactivate();
        \DBG::activate(DBG_LOG_FILE | DBG_PHP | DBG_LOG);
        \DBG::dump($_REQUEST);
        
        $subscriptionId = isset($params['post']['subscription_id']) ? contrexx_input2raw($params['post']['subscription_id']) : 0;
        $subscriptionType = !\FWValidator::isEmpty($subscriptionId) ? 'UPGRADE' : 'ADD';
        $objUser = \FWUser::getFWUserObject()->objUser;
        $crmContactId = $objUser->getCrmUserId();

        //validate Crm user
        if (\FWValidator::isEmpty($crmContactId)) {
            \DBG::log('Not a valid user');
            return array ('status' => 'error','message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_'.$subscriptionType.'_FAILED']);
        }
        
        try {
            // Load language variables of MultisiteexternalPayment
            self::loadLanguageData();
            
            $productId = isset($params['post']['product_id']) ? contrexx_input2raw($params['post']['product_id']) : 0;
            $websiteName = isset($params['post']['websiteName']) ? contrexx_input2raw($params['post']['websiteName']) : '';
            $renewalOption = isset($params['post']['renewalOption']) ? contrexx_input2raw($params['post']['renewalOption']) : '';
            
            if (   \FWValidator::isEmpty($productId) 
                || \FWValidator::isEmpty($renewalOption)
            ) {
                \DBG::log('Invalid argument supplied.');
                return array ('status' => 'error','message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_'.$subscriptionType.'_FAILED']);
            }
            
            $productRepository = \Env::get('em')->getRepository('Cx\Modules\Pim\Model\Entity\Product');
            $product = $productRepository->findOneBy(array('id' => $productId));
            
            //validate selected product
            if (\FWValidator::isEmpty($product)) {
                \DBG::log('Invalid Product Requested.');
                return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_'.$subscriptionType.'_FAILED']);
            }
            
            list($renewalUnit, $renewalQuantifier) = self::getProductRenewalUnitAndQuantifier($renewalOption);
            $currency = ComponentController::getUserCurrency($crmContactId);
            
            $subscriptionOptions = array(
                'renewalUnit'       => $renewalUnit,
                'renewalQuantifier' => $renewalQuantifier
            );
            
            // upgrade subscription 
            $subscriptionObj = null;
            if (!\FWValidator::isEmpty($subscriptionId)) {
                $subscriptionRepo = \Env::get('em')->getRepository('Cx\Modules\Order\Model\Entity\Subscription');
                $subscriptionObj = $subscriptionRepo->findOneBy(array('id' => $subscriptionId));

                if (!$subscriptionObj) {
                    \DBG::log('Invalid Subscription ID.');
                    return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_'.$subscriptionType.'_FAILED']);
                }
                
                $externalSubscriptionId = !\FWValidator::isEmpty($subscriptionObj->getExternalSubscriptionId()) ? $subscriptionObj->getExternalSubscriptionId() : '';
                $orderObj = $subscriptionObj->getOrder();
                if (!$orderObj) {
                    \DBG::log('Invalid Order for subscription.');
                    return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_'.$subscriptionType.'_FAILED']);
                }
                
                //Verify the owner of the associated Order of the Subscription is actually owned by the currently sign-in user
                if ($crmContactId != $orderObj->getContactId()) {
                     \DBG::log('Access denied Invalid Crm User');
                    return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_'.$subscriptionType.'_FAILED']);
                }
                
                $subscriptionOptions['baseSubscription'] = $subscriptionObj;
                $subscriptionOptions['description']      = $subscriptionObj->getDescription();
            }

            if (!\FWValidator::isEmpty($websiteName)) {
                $transactionReference = "|{$product->getId()}|name|$websiteName|";
                $purpose              = $product->getName() . ' - ' . $websiteName;
            } else {
                $transactionReference = "|{$product->getId()}|owner|{$objUser->getId()}|";
                $purpose              = $product->getName();
            }
           
            $productPrice = $product->getPaymentAmount($renewalUnit, $renewalQuantifier, $currency);
            $credit = 0;
            $externalPaymentCustomerId = $objUser->getProfileAttribute(\Cx\Core\Setting\Controller\Setting::getValue('externalPaymentCustomerIdProfileAttributeId','MultiSite'));
            if (   !\FWValidator::isEmpty($productPrice)
                && !\FWValidator::isEmpty($externalPaymentCustomerId)
            ) {
                
                $instanceName = \Cx\Core\Setting\Controller\Setting::getValue('payrexxAccount','MultiSite');
                $apiSecret    = \Cx\Core\Setting\Controller\Setting::getValue('payrexxApiSecret','MultiSite');
                $payrexx      = new \Payrexx\Payrexx($instanceName, $apiSecret);

                $subscription = new \Payrexx\Models\Request\Subscription();
                $subscription->setUserId($externalPaymentCustomerId);
                $subscription->setPurpose($purpose);
                $subscription->setReferenceId($transactionReference);
                $subscription->setPsp(5);
                
                $calculatedProductPrice = $productPrice;
                if (   !\FWValidator::isEmpty($subscriptionId)
                    && $subscriptionObj
                    && !\FWValidator::isEmpty($subscriptionObj->getPaymentAmount())
                    && !\FWValidator::isEmpty($subscriptionObj->getRenewalDate())
                ) {
                    $today = new \DateTime();
                    $today->setTime(0,0); // modify the time to start time
                    
                    $renewalDate      = $subscriptionObj->getRenewalDate();
                    $renewalStartDate = clone $renewalDate;
                    
                    $oldSubscriptionInterval = \DateInterval::createFromDateString($subscriptionObj->getRenewalQuantifier() .' '. $subscriptionObj->getRenewalUnit());
                    $renewalStartDate->sub($oldSubscriptionInterval);
                    
                    $renewalPeriodDays        = $renewalDate->diff($renewalStartDate)->days;
                    $subscriptionPricePerDay  = $subscriptionObj->getPaymentAmount() / $renewalPeriodDays;
                    $daysLeftInCurrentRenewal = $renewalDate->diff($today)->days;
                    
                    $credit                   = number_format($subscriptionPricePerDay * $daysLeftInCurrentRenewal, 2, '.', '');

                    $calculatedProductPrice   = $productPrice - $credit;
                    // set discount price of first payment period of subscription
                    $subscriptionOptions['oneTimeSalePrice'] = $calculatedProductPrice;
                }
                //update amount deatails in subscription
                $subscription->setAmount($product->getAmountWithVat($calculatedProductPrice) * 100);
                
                $subscription->setCurrency(\Payrexx\Models\Request\Subscription::CURRENCY_CHF);

                list($subscriptionPeriod, $subscriptionInterval, $cancellationInterval)
                        = self::getSubscriptionPeriodAndIntervalsByProduct($product, $renewalUnit, $renewalQuantifier);
                // set payment interval
                $subscription->setPaymentInterval(\DateTimeTools::getDateIntervalAsString($subscriptionInterval));

                // set subscription period
                $subscription->setPeriod(\DateTimeTools::getDateIntervalAsString($subscriptionPeriod));

                // set cancellation period
                $subscription->setCancellationInterval(\DateTimeTools::getDateIntervalAsString($cancellationInterval));

                try {
                    $response = $payrexx->create($subscription);

                    if (   $response
                        && $response instanceof \Payrexx\Models\Response\Subscription
                        && $response->getStatus() == 'active'
                    ) {
                        $newExternalSubscriptionId = $response->getId();
                        // if credit applied, update the subscription price for the next renewal
                        if (!\FWValidator::isEmpty($credit)) {
                            $updateSubscription = new \Payrexx\Models\Request\Subscription();
                            $updateSubscription->setId($newExternalSubscriptionId);
                            $updateSubscription->setAmount($product->getAmountWithVat($productPrice) * 100);
                            $updateSubscription->setCurrency(\Payrexx\Models\Request\Subscription::CURRENCY_CHF);
                            try {
                                $payrexx->update($updateSubscription);
                            } catch (\Payrexx\PayrexxException $e) {
                                \DBG::log('JsonMultiSiteController::manageSubscription() - Could not update the a subscription ID => '. $newExternalSubscriptionId. ' / '. $e->getMessage());
                            }
                        }
                        
                        $transactionReference .= "$newExternalSubscriptionId|";

                        // transaction data needed for OrderPaymentEventListener::postPersist()
                        $subscriptionValidDate = new \DateTime();
                        $subscriptionValidDate->add($subscriptionPeriod);
                        $transactionData = array(
                            'subscription' => array(
                              'id'          => $newExternalSubscriptionId,
                              //'valid_until' => $response->getValidUntil(),//;$subscriptionValidDate->format('Y-m-d')
                              'valid_until' => $subscriptionValidDate->format('Y-m-d')
                          )
                        );
                        
                        // create payment for order
                        ComponentController::createPayrexxPayment($transactionReference, $product->getAmountWithVat($calculatedProductPrice), $transactionData);
                    } else {
                        \DBG::log('JsonMultiSiteController::manageSubscription() - Could not create a subscription.');
                        return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_'.$subscriptionType.'_FAILED']);
                    }
                } catch (\Payrexx\PayrexxException $e) {
                    \DBG::log($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_'.$subscriptionType.'_FAILED'].': '.$e->getMessage());
                    throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_'.$subscriptionType.'_FAILED']);
                }
            }
            
            $order = \Env::get('em')->getRepository('Cx\Modules\Order\Model\Entity\Order')->createOrder($productId, $currency, $objUser, $transactionReference, $subscriptionOptions);
            if (!$order) {
                \DBG::log('Unable to create the order.');
                return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_'.$subscriptionType.'_FAILED']);
            }
            // create the website process in the payComplete event
            $order->complete();
            
            // update the changes to database
            \Env::get('em')->flush();
            
            $newSubscriptionId = 0;
            foreach ($order->getSubscriptions() as $newSubscription) {
                $newSubscriptionId = $newSubscription->getId();
                break; // break the loop after getting the subscription id from the first entry
            }

            // set regular product price for next payment period of subscription
            if (!empty($credit)) {
                $newSubscription->setPaymentAmount($productPrice);
                \Env::get('em')->flush();
            }
    
            return array ('status' => 'success', 'id' => $newSubscriptionId, 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_'.$subscriptionType.'_SUCCESS']);
        } catch (\Exception $e) {
            \DBG::msg($e->getMessage());
            throw new MultiSiteJsonException(array(
                'object'    => 'form',
                'type'      => 'danger',
                'message'   => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SUBSCRIPTION_'.$subscriptionType.'_FAILED'],
            ));
        }
    }
    
    /**
     * Get the subscription period and intervals by given product
     * 
     * @param \Cx\Modules\Pim\Model\Entity\Product $product
     * @param string                               $renewalUnit
     * @param integer                              $renewalQuantifier
     * 
     * @return array returns array 
     *               \DateInterval $subscriptionPeriod => subscription period
     *               \DateInterval $subscriptionInterval => subscription interval
     *               \DateInterval $cancellationInterval => subscriptio cancellation interval
     */
    public static function getSubscriptionPeriodAndIntervalsByProduct(\Cx\Modules\Pim\Model\Entity\Product $product, $renewalUnit, $renewalQuantifier)
    {
        $subscriptionInterval = \DateInterval::createFromDateString($renewalQuantifier .' '. $renewalUnit);
        
        // set subscription period
        $expirationInterval = \DateInterval::createFromDateString("{$product->getExpirationQuantifier()} {$product->getExpirationUnit()}");
        $subscriptionIntervalDateTime = new \DateTime();
        $expirationIntervalDateTime = clone $subscriptionIntervalDateTime;
        $subscriptionIntervalDateTime->add($subscriptionInterval);
        $expirationIntervalDateTime->add($expirationInterval);
        // in case the subscription interval is greater than the subscription period (expiration interval), then we shall extend the subscription period accordingly
        if ($subscriptionIntervalDateTime > $expirationIntervalDateTime) {
            $subscriptionPeriod = $subscriptionInterval;
        } else {
            $subscriptionPeriod = $expirationInterval;
        }

        // set cancellation period
        $cancellationInterval = \DateInterval::createFromDateString("{$product->getCancellationQuantifier()} {$product->getCancellationUnit()}");        
        
        return array($subscriptionPeriod, $subscriptionInterval, $cancellationInterval);
    }
    
    /**
     * Get Product renewalUnit and quantifier by using renewalOption
     * 
     * @param string $renewalOption renewalOption
     * @return array renewalUnit and quantifier
     */
    public static function getProductRenewalUnitAndQuantifier($renewalOption = 'monthly')
    {
        switch ($renewalOption) {
            case 'annually':
                $renewalUnit = \Cx\Modules\Pim\Model\Entity\Product::UNIT_YEAR;
                $renewalQuantifier = 1;
                break;
            case 'biannually':
                $renewalUnit = \Cx\Modules\Pim\Model\Entity\Product::UNIT_YEAR;
                $renewalQuantifier = 2;
                break;
            default:
                $renewalUnit = \Cx\Modules\Pim\Model\Entity\Product::UNIT_MONTH;
                $renewalQuantifier = 1;
                break;
        }
        return array($renewalUnit, $renewalQuantifier);
    }
    /**
     * Creates a new website
     * @param type $params  
     */
    public function createWebsite($params) {
// TODO: what do we actually need the language data for? We should load the language data at the certain place where it is actually being used
        self::loadLanguageData();
        
        $objFWUser   = \FWUser::getFWUserObject();
        $objUser     = $objFWUser->objUser->getUser(contrexx_input2raw($params['post']['userId']));
        $websiteId   = isset($params['post']['websiteId']) ? contrexx_input2raw($params['post']['websiteId']) : '';
        $websiteName = isset($params['post']['websiteName']) ? contrexx_input2raw($params['post']['websiteName']) : '';
        $themeId     = isset($params['post']['themeId']) ? contrexx_input2raw($params['post']['themeId']) : '';
        
        $basepath = \Cx\Core\Setting\Controller\Setting::getValue('websitePath','MultiSite');
        $websiteServiceServer = null;
        if (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite') == ComponentController::MODE_MANAGER) {
            //get default service server
            $websiteServiceServer = ComponentController::getServiceServerByCriteria(array('id' => \Cx\Core\Setting\Controller\Setting::getValue('defaultWebsiteServiceServer','MultiSite')));
        }

        try {
            $objWebsite = new \Cx\Core_Modules\MultiSite\Model\Entity\Website($basepath, $websiteName, $websiteServiceServer, $objUser, false, $themeId);
            \Env::get('em')->persist($objWebsite);
            if ($websiteId) {
                $objWebsite->setId($websiteId);
                $metadata = \Env::get('em')->getClassMetaData(get_class($objWebsite));
                $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
            }
            \Env::get('em')->flush();
            return $objWebsite->setup($params['post']['options']);
        } catch (\Cx\Core_Modules\MultiSite\Model\Entity\WebsiteException $e) {
            throw new MultiSiteJsonException(array(
                'log'       => \DBG::getMemoryLogs(),
                'message'   => $e->getMessage(),
            ));
        }
    }

    public function createUser($params, $throwException = false) {
        try {
            if (empty($params['post'])) {
                throw new MultiSiteJsonException('Invalid arguments specified for command JsonMultiSiteController::createUser.');
            }

            $objUser = new \Cx\Core_Modules\MultiSite\Model\Entity\User();
            $userId = isset($params['post']['userId']) ? contrexx_input2raw($params['post']['userId']) : '';
            if (!empty($userId)) {
                $objFWUser = \FWUser::getFWUserObject();
                $objUserExist = $objFWUser->objUser->getUser($userId);
                if ($objUserExist) {
                    return array(
                        'status' => 'success',
                        'userId' => $objUserExist->getId(),
                        'log' => \DBG::getMemoryLogs(),
                    );
                }
                $objUser->setMultiSiteId($userId);
            }
            $objUser->setEmail(!empty($params['post']['email']) ? contrexx_input2raw($params['post']['email']) : '');
            $objUser->setActiveStatus(!empty($params['post']['active']) ? (bool)$params['post']['active'] : false);
            $objUser->setAdminStatus(!empty($params['post']['admin']) ? (bool)$params['post']['admin'] : false);
            $objUser->setPassword(\User::make_password(8,true));
            
            if (!empty($params['post']['groups'])) {
                $objUser->setGroups($params['post']['groups']);
            }

            if (!empty($params['post']['newsletter_lists'])) {
                $objUser->setSubscribedNewsletterListIDs(array_map('intval', $params['post']['newsletter_lists']));
            }

            if (!$objUser->store()) {
                \DBG::msg('Adding user failed: '.$objUser->getErrorMsg());
                throw new MultiSiteJsonException($objUser->getErrorMsg());
            } else {
                \DBG::msg('User successfully created');
                return array(
                    'status' => 'success',
                    'userId'=> $objUser->getId(),
                    'log'   => \DBG::getMemoryLogs(),
                );
            }
        } catch (\Exception $e) {
            if ($throwException) {
                // used by internal method calls
                throw new MultiSiteJsonException(array(
                    'log'       => \DBG::getMemoryLogs(),
                    'message'   => $e->getMessage(),
                ));
            } else {
                // used by JsonAdapter call to this method
                return array(
                    'status'    => 'error',
                    'log'       => \DBG::getMemoryLogs(),
                    'message'   => $e->getMessage(),
                );
            }
        }
    }

    /**
     * Create admin user
     * 
     * @global type $_ARRAYLANG
     * @param array $params
     * @return array
     * @throws MultiSiteJsonException
     */
    public function createAdminUser($params) {
        global $_ARRAYLANG;
        
        self::loadLanguageData();
        try {
            if (empty($params['post'])) {
                throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_UNKOWN_USER_REQUEST']);
            }

            $data = $params['post'];

            $objUser = new \User();
            //Check email validity
            if (!\FWValidator::isEmail($data['multisite_user_account_email'])) {
                \DBG::log($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_INVALID_EMAIL']);
                throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_INVALID_EMAIL']);
            }

            if (!\User::isUniqueEmail($data['multisite_user_account_email'])) {
                \DBG::log($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_EMAIL_IN_USE']);
                throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_EMAIL_IN_USE']);
            }
            
            // set user account email
            if (isset($data['multisite_user_account_email'])) {
                $objUser->setEmail(trim(contrexx_input2raw($data['multisite_user_account_email'])));
            }
            
            // set profile data
            if (isset($data['multisite_user_profile_attribute'])) {
                $objUser->setProfile(contrexx_input2raw($data['multisite_user_profile_attribute']));
            }
            
            // set new plain text password
            if (!empty($data['multisite_user_account_password'])) {
                $password = contrexx_input2raw($data['multisite_user_account_password']);
                $confirmedPassword = !empty($data['multisite_user_account_password_confirmed']) ? contrexx_input2raw($data['multisite_user_account_password_confirmed']) : '';
                if (!$objUser->setPassword($password, $confirmedPassword)) {
                    \DBG::msg("JsonMultiSiteController (updateUser): Failed to update {$objUser->getId()}: ".join("\n", $objUser->getErrorMsg()));
                    throw new MultiSiteJsonException(array(
                        'object'    => 'password',
                        'type'      => 'danger',
                        'message'   => join("\n", $objUser->getErrorMsg()),
                        'log'       => \DBG::getMemoryLogs(),
                    ));
                }
            }
            
            //Set admin flag of User to true
            $objUser->setAdminStatus(1);
            
            $objUser->setActiveStatus(1);

            if (!$objUser->store()) {
                \DBG::log('Adding Admin user failed: ' . $objUser->getErrorMsg());
                throw new MultiSiteJsonException($objUser->getErrorMsg());
            }
            \DBG::log("JsonMultiSiteController (createAdminUser): User has been successfully added.");
            return array(
                'status' => 'success',
                'log' => \DBG::getMemoryLogs(),
            );
        } catch (\Exception $e) {
            throw new MultiSiteJsonException($e->getMessage());
        }
    }

    /**
     * Update an existing user account
     * @param array $params POST-data based on with the account shall be updated to
     * @return  boolean     TRUE on success, FALSE on failure.
     */
    public function updateUser($params) {
        global $_ARRAYLANG;
        
        if (empty($params['post'])) {
            throw new MultiSiteJsonException(array(
                'object'    => 'form',
                'type'      => 'danger',
                'message'   => 'No data supplied',
            ));
        }

        $objFWUser = \FWUser::getFWUserObject();
        $data = $params['post'];
        
        switch(\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
            case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_MANAGER:
            case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_HYBRID:
            case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_SERVICE:
                $objUser = $objFWUser->objUser->getUser(intval($params['post']['userId']), true);
                break;

            case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_WEBSITE:
                if(isset($params['post']['websiteUserId'])){
                    $websiteUserId = $params['post']['websiteUserId'];
                } else {
                    $websiteUserId = \Cx\Core\Setting\Controller\Setting::getValue('websiteUserId','MultiSite');
                }
                $objUser = $objFWUser->objUser->getUser(intval($websiteUserId), true);
                break;

            default:
                break;
        }

        if (!$objUser) {
            throw new MultiSiteJsonException(array(
                'object'    => 'form',
                'type'      => 'danger',
                'message'   => 'Unknown user account',
                'log'       => \DBG::getMemoryLogs(),
            ));
        }
        
        // set account data
        if (isset($data['multisite_user_account_username'])) {
            $objUser->setUsername(trim(contrexx_input2raw($data['multisite_user_account_username'])));
        }
        if (isset($data['multisite_user_account_email'])) {
            $objUser->setEmail(trim(contrexx_input2raw($data['multisite_user_account_email'])));
        }
        if (isset($data['multisite_user_account_frontend_language'])) {
            $objUser->setFrontendLanguage(intval($data['multisite_user_account_frontend_language']));
        }
        if (isset($data['multisite_user_account_backend_language'])) {
            $objUser->setBackendLanguage(intval($data['multisite_user_account_backend_language']));
        }
        if (isset($data['multisite_user_account_email_access']) && $objUser->isAllowedToChangeEmailAccess()) {
            $objUser->setEmailAccess(contrexx_input2raw($data['multisite_user_account_email_access']));
        }
        if (isset($data['multisite_user_account_profile_access']) && $objUser->isAllowedToChangeProfileAccess()) {
            $objUser->setProfileAccess(contrexx_input2raw($data['multisite_user_account_profile_access']));
        }
        if (isset($data['multisite_user_account_verified'])) {
            $objUser->setVerification((boolean)intval($data['multisite_user_account_verified']));
        }
        if (isset($data['multisite_user_account_restore_key'])) {
            $objUser->setRestoreKey(contrexx_input2raw($data['multisite_user_account_restore_key']));
        }
        if (isset($data['multisite_user_account_restore_key_time'])) {
            $objUser->setRestoreKeyTime(intval($data['multisite_user_account_restore_key_time']), true);
        }

        // set profile data
        if (isset($data['multisite_user_profile_attribute'])) {
            $objUser->setProfile(contrexx_input2raw($data['multisite_user_profile_attribute']));
        }
        // set md5 hashed password
        if (isset($data['multisite_user_md5_password'])) {
            $objUser->setHashedPassword(contrexx_input2raw($data['multisite_user_md5_password']));
        }

        // set new plain text password
        if (!empty($data['multisite_user_account_password'])) {
            $password = contrexx_input2raw($data['multisite_user_account_password']);
            $confirmedPassword = !empty($data['multisite_user_account_password_confirmed']) ? contrexx_input2raw($data['multisite_user_account_password_confirmed']) : '';
            if (!$objUser->setPassword($password, $confirmedPassword)) {
                \DBG::msg("JsonMultiSiteController (updateUser): Failed to update {$objUser->getId()}: ".join("\n", $objUser->getErrorMsg()));
                throw new MultiSiteJsonException(array(
                    'object'    => 'password',
                    'type'      => 'danger',
                    'message'   => join("\n", $objUser->getErrorMsg()),
                    'log'       => \DBG::getMemoryLogs(),
                ));
            }
        }

        if (!$objUser->store()) {
            \DBG::msg("JsonMultiSiteController (updateUser): Failed to update {$objUser->getId()}: ".join("\n", $objUser->getErrorMsg()));
            throw new MultiSiteJsonException(array(
                'object'    => 'form',
                'type'      => 'danger',
                'message'   => join("\n", $objUser->getErrorMsg()),
                'log'       => \DBG::getMemoryLogs(),
            ));
        }
                
        \DBG::msg("JsonMultiSiteController (updateUser): User {$objUser->getId()} successfully updated.");
        return array(
            'status'    => 'success',
            'log'       => \DBG::getMemoryLogs(),
            'message'   => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_USER_PROFILE_UPDATED_SUCCESS'],
            'reload'    => !empty($params['post']['reload']),
        );
    }
    
    /**
     * Verify the owner's email address is isUniqueEmail
     * 
     * @param array $params POST-data based on with the account to verify the email
     * 
     * @return array
     * @throws MultiSiteJsonException
     */
    public function isUniqueEmail($params)
    {
        $data = $params['post'];
        
        if (empty($data)) {
            throw new MultiSiteJsonException(array(
                'object'    => 'form',
                'type'      => 'danger',
                'message'   => 'No data supplied',
            ));
        }
        
        if (!isset($data['currentEmail']) || !isset($data['newEmail'])) {
            throw new MultiSiteJsonException(array(
                'object'    => 'form',
                'type'      => 'danger',
                'message'   => 'Unknown user email id',
            ));
        }
        
        switch(\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
            case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_MANAGER:
            case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_HYBRID:
                $objUser = \FWUser::getFWUserObject()->objUser->getUsers(array('email' => $data['currentEmail']));
                break;
            default:
                break;
        }
        
        if (!$objUser) {
            throw new MultiSiteJsonException(array(
                'object'    => 'form',
                'type'      => 'danger',
                'message'   => 'Unknown user account',
            ));
        }
        
        if (!\User::isUniqueEmail($data['newEmail'], $objUser->getId())) {
            return array('status' => 'error', 'customerPanelDomain' => \Cx\Core\Setting\Controller\Setting::getValue('customerPanelDomain','MultiSite'));
        }
    
        return array('status' => 'success');
    }
    
    /**
     * Update the user account of the signed-in user
     * @param array $params POST-data based on with the account shall be updated to
     * @return  boolean     TRUE on success, FALSE on failure.
     */
    public function updateOwnUser($params) {
        $objFWUser = \FWUser::getFWUserObject();
        $objUser = $objFWUser->objUser;
        if (!$objUser->login()) {
            throw new MultiSiteJsonException(array(
                'object'    => 'form',
                'type'      => 'danger',
                'message'   => 'Operation denied',
            ));
        }

        // NOTE: This command is used in frontend,
        //       Therefore, all users that are logged-in
        //       are allowed to update their profile.
        //       The following permission check has been
        //       deactivated therefore.
        //
        // Only administrators or users with sufficient permissions
        // may update their own account.
        /*if (!\Permission::hasAllAccess() && !\Permission::checkAccess(31, 'static', true)) {
            throw new MultiSiteJsonException(array(
                'object'    => 'form',
                'type'      => 'danger',
                'message'   => 'Operation denied',
            ));
        }*/

        $params['post']['userId'] = $objUser->getId();
        return $this->updateUser($params);
    } 

    /**
     *  callback authentication for verifing secret key and installation id based on mode
     * 
     * @return boolean
     */
    public function auth(array $params = array()) {
        $authenticationValue = isset($params['post']['auth']) ? json_decode($params['post']['auth'], true) : '';

        if (   empty($authenticationValue)
            || !is_array($authenticationValue)
            || !isset($authenticationValue['sender'])
            || !isset($authenticationValue['key'])
        ) {
            return false;
        }
        $config = \Env::get('config');
        $installationId = $config['installationId'];
        switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
            case ComponentController::MODE_MANAGER:
                try {
                    $objWebsiteService = ComponentController::getServiceServerByCriteria(array('hostname' => $authenticationValue['sender']));
                    if (!$objWebsiteService) {
                        return false;
                    }
                    $secretKey = $objWebsiteService->getSecretKey();
                } catch(\Exception $e) {
                    \DBG::msg($e->getMessage());
                    return false;
                }
                break;

            case ComponentController::MODE_SERVICE:
            case ComponentController::MODE_HYBRID:
                //Check if the sender is manager or not
                if ($authenticationValue['sender'] == \Cx\Core\Setting\Controller\Setting::getValue('managerHostname','MultiSite')) {
                    $secretKey = \Cx\Core\Setting\Controller\Setting::getValue('managerSecretKey','MultiSite');
                } else {
                    try {
                        $domainRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Domain');
                        $domain     = $domainRepo->findOneBy(array('name' => $authenticationValue['sender']));
                        if (!$domain || !$domain->getWebsite()) {
                            return false;
                        }
                        $secretKey  = $domain->getWebsite()->getSecretKey();
                    } catch (\Exception $e) {
                        \DBG::msg($e->getMessage());
                        return false;
                    }
                }
                break;
                
            case ComponentController::MODE_WEBSITE:
                $secretKey = \Cx\Core\Setting\Controller\Setting::getValue('serviceSecretKey','MultiSite');
                break;
        }
        
        if (md5($secretKey.$installationId) !== $authenticationValue['key']) {
            \DBG::msg(__METHOD__." failed: installationId=$installationId / authenticationValue={$authenticationValue['key']}");
            return false;
        }

        // register request as intersystem communication request (ISC)
        self::$isIscRequest = true;

        // skip memory-logging for remote-login request
        if (
            isset($params['post']['act']) &&
            $params['post']['act'] == 'generateAuthToken'
        ) {
            return true;
        }

        // skip memory-logging for website modifications
        if (
            isset($params['get']['act']) &&
            $params['get']['act'] == 'setWebsiteDetails'
        ) {
            return true;
        }

        // activate memory-logging
        $this->activateDebuggingToMemory();

        return true;
    }

    /**
     * Callback authentication for verifing signup request
     * 
     * @param array $params
     * @return boolean
     */
    public function verifySignupRequest($params)
    {
        if (isset($params['post']['serviceServerId']) && !$this->auth($params)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * callback authentication for verifing website owner
     * 
     * @return boolean
     */
    public function  verifyWebsiteOwnerOrIscRequest($params)
    {
        //check the authentication for verifing secret key and installation id based on mode
        if ($this->auth($params)) {
            return true;
        }
        
        if (!isset($params['post']['websiteId'])) {
            return false;
        }
        
        //check the website exists or not based on $websiteId
        $website = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website')->findOneBy(array('id' => $params['post']['websiteId']));
        if (!$website) {
            \DBG::log('JsonMultiSiteController::verifyWebsiteOwnerOrIscRequest() failed: Unkown Website-ID: '.$params['post']['websiteId']);
            return false;
        }
        
        //check the user logged in or not
        if (!ComponentController::isUserLoggedIn()) {
            return false;
        }
        
        //check the logged in user is owner of website($websiteId)
        if ($website->getOwner()->getId() == \FWUser::getFWUserObject()->objUser->getId()) {
            return true;
        }
        return false;
    }
    
    /**
     * Callback authentication for checking the user's access permission
     * 
     * @return boolean
     */
    public function checkPermission() 
    {
        if (\Permission::checkAccess(183, 'static', true)) {
            return true;
        }
        
        return false;
    }
    
    /**
     *  Get the Authentication Object
     * 
     * @param String $secretKey
     * @param String $remoteInstallationId
     * 
     * @return json
     */
    public static function getAuthenticationObject($secretKey, $remoteInstallationId) 
    {
        $key = md5($secretKey . $remoteInstallationId);
        
        return json_encode(array(
            'key'     => $key,
            'sender' => ComponentController::$cxMainDomain,
        ));
    }
    /**
     *  Get the auto-generated SecretKey
     * 
     * @return string 
     */
    public static function generateSecretKey(){
        return bin2hex(openssl_random_pseudo_bytes(16));    
    }
    
    /**
     * Map the website domain
     * 
     * @param type $params
     * @return type
     */
    public function mapDomain($params) {
        global $_ARRAYLANG;
        if (   empty($params['post'])
            || empty($params['post']['domainName'])
            || empty($params['post']['auth'])
            || empty($params['post']['componentType'])
            || !isset($params['post']['componentId'])
            || !isset($params['post']['coreNetDomainId'])
        ) {
            throw new MultiSiteJsonException('JsonMultiSiteController::mapDomain() failed: Insufficient mapping information supplied.');
        }

        $authenticationValue = json_decode($params['post']['auth'], true);
        if (empty($authenticationValue) || !is_array($authenticationValue)) {
            throw new MultiSiteJsonException('JsonMultiSiteController::mapDomain() failed: Insufficient mapping information supplied.');
        }
        //check the black listed domains
        if (!self::checkBlackListDomains($params['post']['domainName'])) {
            \DBG::log('JsonMultiSiteController::mapDomain() failed: ' . $params['post']['domainName'] . '(Domain name is black listed).');
            throw new MultiSiteJsonException(sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MAPPING_DOMAIN_FAILED'], $params['post']['domainName']));
        }

        try {
            //Check the domain name is subdomain of the domain or not.
            if ($this->isDomainASubDomainOfMultisiteDomain($params['post']['domainName'])) {
                \DBG::log('JsonMultiSiteController::mapDomain() failed: The domain name is a subdomain of multiSite Domain');
                throw new MultiSiteJsonException(sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MAPPING_DOMAIN_FAILED'], $params['post']['domainName']));
            }
            
            $domainRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Domain');
            
            if ($domainRepo->findOneBy(array('name' => $params['post']['domainName']))) {
                return array(
                    'status' => 'error',
                    'log'    => \DBG::getMemoryLogs(),
                );
            }
            // create a new domain entity that shall be used for the mapping
            $objDomain = new \Cx\Core_Modules\MultiSite\Model\Entity\Domain($params['post']['domainName']);
            
            switch ($params['post']['componentType']) {
                case ComponentController::MODE_SERVICE:
                    // If componentType is MODE_SERVICE, then we are about to
                    // map the domain to a Service Server. Therefore, we have
                    // to fetch the ID of the Service Server the domain shall
                    // be mapped to.
                    $websiteServiceServer = ComponentController::getServiceServerByCriteria(array('hostname' => $authenticationValue['sender']));
                    if (!$websiteServiceServer) {
                        throw new MultiSiteJsonException('JsonMultiSiteController::mapDomain() failed: Unkown Service Server: '.$authenticationValue['sender']);
                    }
                    $componentId = $websiteServiceServer->getId();
                    break;

                case ComponentController::MODE_WEBSITE:
                    switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                        case ComponentController::MODE_MANAGER:
                            // componentId is the ID of a Website that the domain shall be mapped to
                            $componentId = $params['post']['componentId'];
                            $website = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website')->findOneById($componentId);
                            if (!$website) {
                                throw new MultiSiteJsonException('JsonMultiSiteController::mapDomain() failed: Unkown Website-ID: '.$componentId);
                            }
                            break;

                        case ComponentController::MODE_HYBRID:
                        case ComponentController::MODE_SERVICE:
                            // componentId is the ID of the Website the request was made from
                            $objWebsiteDomain = $domainRepo->findOneBy(array('name' => $authenticationValue['sender']));
                            if (!$objWebsiteDomain) {
                                throw new MultiSiteJsonException('JsonMultiSiteController::mapDomain() failed: Unkown Website: '.$authenticationValue['sender']);
                            }
                            $website = $objWebsiteDomain->getWebsite();
                            if (!$website) {
                                throw new MultiSiteJsonException('JsonMultiSiteController::mapDomain() failed: Unkown Website: '.$authenticationValue['sender']);
                            }
                            $componentId = $website->getId();
                            break;

                        default:
                            throw new MultiSiteJsonException('JsonMultiSiteController::mapDomain() failed: Command not available for mode: '.\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite'));
                            break;
                    }

                    $website->mapDomain($objDomain);
// TODO: will this trigger a request to the service server, then update the domains there and then again trigger a request back to the manager and will then end in an infinite loop????
                    \Env::get('em')->persist($website);
                    break;

                case ComponentController::MODE_MANAGER:
                case ComponentController::MODE_HYBRID:
                default:
                    // If componentType is MANAGER or HYBRID, then we are about to map
                    // a Net-domain to the own system. Therefore setting componentId to null 
                    // will reference the domain to be mapped to ourself.
                    // Using NULL instead of 0 is important. As to the database scheme
                    // the domain map/unmap process would not work properly, if we use 0 at this point.
                    $componentId = null;
                    break;
            }

            $objDomain->setComponentType($params['post']['componentType']);
            $objDomain->setComponentId($componentId);
            $objDomain->setCoreNetDomainId($params['post']['coreNetDomainId']);
            \Env::get('em')->persist($objDomain);
            \Env::get('em')->flush();
            return array(
                'status' => 'success',
                'log'    => \DBG::getMemoryLogs(),
            );
        } catch (\Exception $e) {
            throw new MultiSiteJsonException($e->getMessage());
        }
    }
    
    /**
     * Map Net Domain
     * 
     * @param  array $params supplied arguments from JsonData-request
     * 
     * @return array mapNetDomain stats 
     * @throws MultiSiteJsonException
     */
    public function mapNetDomain($params) {
        
        global $_ARRAYLANG;
        
        self::loadLanguageData();
        
        if (empty($params['post']['domainName'])) {
            \DBG::log('JsonMultiSiteController::mapNetDomain() failed: domainName is empty.');
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_UNKNOWN']);
        }
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                
                case ComponentController::MODE_WEBSITE:
                    $domain = new \Cx\Core\Net\Model\Entity\Domain();
                    $domain->setName(contrexx_input2raw($params['post']['domainName']));
                
                    $domainRepository = \Env::get('em')->getRepository('Cx\Core\Net\Model\Entity\Domain');
                    $domainRepository->add($domain);
                    $domainRepository->flush();
                    return array(
                        'status' => 'success'
                    );
                    break;
            }
        } catch (\Exception $e) {
            \DBG::log('JsonMultiSiteController::mapNetDomain() failed:'. $e->getMessage());
            throw new MultiSiteJsonException('JsonMultiSiteController::mapNetDomain() failed:'. $e->getMessage());
        }
    }

    /**
     * UnMap Net Domain
     * 
     * @param  array $params supplied arguments from JsonData-request
     * 
     * @return array unMapNetDomain stats
     * @throws MultiSiteJsonException
     */
    public function unMapNetDomain($params) {
       
        global $_ARRAYLANG;
        
        self::loadLanguageData();
        
        if (empty($params['post']['domainId'])) {
            \DBG::log('JsonMultiSiteController::unMapNetDomain() failed: domainId is empty.');
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_UNKNOWN']);
        }
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                
                case ComponentController::MODE_WEBSITE:
                    $domainRepo = new \Cx\Core\Net\Model\Repository\DomainRepository();
                    $domain = $domainRepo->findOneBy(array('id' => contrexx_input2raw($params['post']['domainId'])));
                    
                    if (!$domain) {
                       \DBG::log('JsonMultiSiteController::unMapNetDomain() failed: Unknown DomainId:'. $params['post']['domainId']);
                       throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_UNKNOWN']);
                    }
                    
                    $domainRepository = \Env::get('em')->getRepository('Cx\Core\Net\Model\Entity\Domain');
                    $domainRepository->remove($domain);
                    $domainRepository->flush();
                    return array(
                        'status' => 'success'
                    );
                    break;
            }
        } catch (\Exception $e) {
            \DBG::dump('JsonMultiSiteController::unMapNetDomain() failed:'. $e->getMessage());
            throw new MultiSiteJsonException('JsonMultiSiteController::unMapNetDomain() failed:'. $e->getMessage());
        }
    }

    
    /**
     * Update Net Domain
     * 
     * @param  array $params supplied arguments from JsonData-request
     * 
     * @return array updateNetDomain stats
     * @throws MultiSiteJsonException
     */
    public function updateNetDomain($params)
    {
        global $_ARRAYLANG;
        
        self::loadLanguageData();
        
        if (empty($params['post']['domainName']) || empty($params['post']['domainId'])) {
            \DBG::log('JsonMultiSiteController::updateNetDomain() failed: domainName or domainId is empty.');
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_UNKNOWN']);
        }
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_WEBSITE:
                    $domainRepo  = \Env::get('em')->getRepository('Cx\Core\Net\Model\Entity\Domain');
                    $objDomain   = $domainRepo->findOneBy(array('id' => $params['post']['domainId']));
                    if (!$objDomain) {
                       \DBG::log('JsonMultiSiteController::updateNetDomain() failed: Unknown DomainId:'. $params['post']['domainId']);
                       throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_UNKNOWN']);
                    }
                    $objDomain->setName(contrexx_input2raw($params['post']['domainName']));
                    $domainRepo->flush();
                    return array(
                        'status' => 'success'
                    );
                    break;
            }
        } catch (\Exception $e) {
            \DBG::dump('JsonMultiSiteController::updateNetDomain() failed:'. $e->getMessage());
            throw new MultiSiteJsonException('JsonMultiSiteController::updateNetDomain() failed:'. $e->getMessage());
        }
    }

    /**
     * Unmap the website domain
     * 
     * @param type $params
     * @return type
     */
    public function unMapDomain($params)
    {
        if (   empty($params['post'])
            || empty($params['post']['domainName'])
            || empty($params['post']['auth'])
            || empty($params['post']['componentType'])
            || !isset($params['post']['componentId'])
            //|| !isset($params['post']['coreNetDomainId'])
        ) {
            throw new MultiSiteJsonException('JsonMultiSiteController::unMapDomain() failed: Insufficient mapping information supplied.');
        }

        $authenticationValue = json_decode($params['post']['auth'], true);
        if (empty($authenticationValue) || !is_array($authenticationValue)) {
            throw new MultiSiteJsonException('JsonMultiSiteController::unMapDomain() failed: Insufficient mapping information supplied.');
        }

        try {
            $website = null;

            $domainRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Domain');
            switch ($params['post']['componentType']) {
                case ComponentController::MODE_SERVICE:
                    // If componentType is MODE_SERVICE, then we are about to
                    // unmap the domain from a Service Server. Therefore, we have
                    // to fetch the ID of the Service Server the domain shall
                    // be unmapped from.
                    $websiteServiceServer = ComponentController::getServiceServerByCriteria(array('hostname' => $authenticationValue['sender']));
                    if (!$websiteServiceServer) {
                        throw new MultiSiteJsonException('JsonMultiSiteController::unMapDomain() failed: Unkown Service Server: '.$authenticationValue['sender']);
                    }
                    $componentId = $websiteServiceServer->getId();
                    break;

                case ComponentController::MODE_WEBSITE:
                    switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                        case ComponentController::MODE_MANAGER:
                            // componentId is the ID of a Website that the domain shall be unmapped from
                            $componentId = $params['post']['componentId'];
                            $website = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website')->findOneById($componentId);
                            if (!$website) {
                                throw new MultiSiteJsonException('JsonMultiSiteController::unMapDomain() failed: Unkown Website-ID: '.$componentId);
                            }
                            break;

                        case ComponentController::MODE_HYBRID:
                        case ComponentController::MODE_SERVICE:
                            // componentId is the ID of the Website the request was made from
                            $objWebsiteDomain = $domainRepo->findOneBy(array('name' => $authenticationValue['sender']));
                            if (!$objWebsiteDomain) {
                                throw new MultiSiteJsonException('JsonMultiSiteController::unMapDomain() failed: Unkown Website: '.$authenticationValue['sender']);
                            }
                            $website = $objWebsiteDomain->getWebsite();
                            if (!$website) {
                                throw new MultiSiteJsonException('JsonMultiSiteController::unMapDomain() failed: Unkown Website: '.$authenticationValue['sender']);
                            }
                            $componentId = $website->getId();
                            break;

                        default:
                            throw new MultiSiteJsonException('JsonMultiSiteController::unMapDomain() failed: Command not available for mode: '.\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite'));
                            break;
                    }
                    break;

                case ComponentController::MODE_MANAGER:
                case ComponentController::MODE_HYBRID:
                default:
                    // If componentType is MANAGER or HYBRID, then we are about to unmap
                    // a Net-domain from the own system. Therefore setting componentId to null 
                    // will reference the domain to be mapped to ourself.
                    $componentId = null;
                    break;
            }
            $critieria = array(
                'name'              => $params['post']['domainName'],
                'componentType'     => $params['post']['componentType'],
                'componentId'       => $componentId,
                'type'              => $params['post']['type'],
            );
            if (isset($params['post']['coreNetDomainId'])) {
                $critieria['coreNetDomainId'] = $params['post']['coreNetDomainId'];
            }
            $objDomain = $domainRepo->findOneBy($critieria);

            if (!$objDomain) {
                throw new MultiSiteJsonException('JsonMultiSiteController::unMapDomain() failed: Domain to remove not found.');
            }

            if ($website && $objDomain->getWebsite() == $website) {
                // Website::unMapDomain() does also call remove() on the entity manager
                $website->unMapDomain($objDomain);
            } else {
                \Env::get('em')->remove($objDomain);
            }
            \Env::get('em')->flush();
            return array(
                'status' => 'success',
                'log'    => \DBG::getMemoryLogs(),
            );
        } catch (\Exception $e) {
            throw new MultiSiteJsonException($e->getMessage());
        }
    }

    /**
     * update the default codeBase
     * 
     * @param array $params
     * 
     * @return string
     */
    public function updateDefaultCodeBase($params) 
    {
        global $_ARRAYLANG;
        self::loadLanguageData();
        if (!empty($params['post'])) {
            \Cx\Core\Setting\Controller\Setting::init('MultiSite', 'websiteSetup','FileSystem');
            \Cx\Core\Setting\Controller\Setting::set('defaultCodeBase',$params['post']['defaultCodeBase']);
            if (\Cx\Core\Setting\Controller\Setting::update('defaultCodeBase')) {
                return $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_DEFAULTCODEBASE_SUCCESSFUL_CREATION'];
            }
        }
    }
    /**
     * update the domain alias
     * 
     * @param array $params
     * 
     * @return string
     */
    public function updateDomain($params) {
        global $_ARRAYLANG;
        if (   empty($params['post'])
            || empty($params['post']['domainName'])
            || empty($params['post']['auth'])
            || empty($params['post']['componentType'])
            || !isset($params['post']['componentId'])
            //|| !isset($params['post']['coreNetDomainId'])
        ) {
            \DBG::dump($params);
            throw new MultiSiteJsonException('JsonMultiSiteController::updateDomain() on '.\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite').' failed: Insufficient mapping information supplied: '.var_export($params, true));
        }

        $authenticationValue = json_decode($params['post']['auth'], true);
        if (empty($authenticationValue) || !is_array($authenticationValue)) {
            \DBG::dump($params);
            throw new MultiSiteJsonException('JsonMultiSiteController::updateDomain() on '.\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite').' failed: Insufficient mapping information supplied.'.var_export($params, true));
        }
        //check the black listed domains
        if (!self::checkBlackListDomains($params['post']['domainName'])) {
            \DBG::log('JsonMultiSiteController::updateDomain() failed: ' . $params['post']['domainName'] . '(Domain name is black listed).');
            throw new MultiSiteJsonException(sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MAPPING_DOMAIN_FAILED'], $params['post']['domainName']));
        }
        
        try {
            //Check the domain name is subdomain of the domain or not.
            if ($this->isDomainASubDomainOfMultisiteDomain($params['post']['domainName'])) {
                \DBG::log('JsonMultiSiteController::updateDomain() failed: The domain name is a subdomain of multiSite Domain');
                throw new MultiSiteJsonException(sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MAPPING_DOMAIN_FAILED'], $params['post']['domainName']));
            }
            
            $website = null;
            
            $domainRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Domain');
            if ($domainRepo->findOneBy(array('name' => $params['post']['domainName']))) {
                return array(
                    'status' => 'error',
                    'log'    => \DBG::getMemoryLogs(),
                );
            }
            switch ($params['post']['componentType']) {
                case ComponentController::MODE_SERVICE:
                    // If componentType is MODE_SERVICE, then we are about to
                    // update the domain of a Service Server. Therefore, we have
                    // to fetch the ID of the Service Server the domain shall
                    // be updated of.
                    $websiteServiceServer = ComponentController::getServiceServerByCriteria(array('hostname' => $authenticationValue['sender']));
                    if (!$websiteServiceServer) {
                        throw new MultiSiteJsonException('JsonMultiSiteController::updateDomain() failed: Unkown Service Server: '.$authenticationValue['sender']);
                    }
                    $componentId = $websiteServiceServer->getId();
                    break;

                case ComponentController::MODE_WEBSITE:
                    switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                        case ComponentController::MODE_MANAGER:
                            // componentId is the ID of a Website that the domain shall be updated of
                            $componentId = $params['post']['componentId'];
                            $website = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website')->findOneById($componentId);
                            if (!$website) {
                                throw new MultiSiteJsonException('JsonMultiSiteController::updateDomain() failed: Unkown Website-ID: '.$componentId);
                            }
                            break;

                        case ComponentController::MODE_HYBRID:
                        case ComponentController::MODE_SERVICE:
                            // componentId is the ID of the Website the request was made from
                            $objWebsiteDomain = $domainRepo->findOneBy(array('name' => $authenticationValue['sender']));
                            if (!$objWebsiteDomain) {
                                throw new MultiSiteJsonException('JsonMultiSiteController::updateDomain() failed: Unkown Website: '.$authenticationValue['sender']);
                            }
                            $website = $objWebsiteDomain->getWebsite();
                            if (!$website) {
                                throw new MultiSiteJsonException('JsonMultiSiteController::updateDomain() failed: Unkown Website: '.$authenticationValue['sender']);
                            }
                            $componentId = $website->getId();
                            break;

                        default:
                            throw new MultiSiteJsonException('JsonMultiSiteController::updateDomain() failed: Command not available for mode: '.\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite'));
                            break;
                    }
                    break;

                case ComponentController::MODE_MANAGER:
                case ComponentController::MODE_HYBRID:
                default:
                    // If componentType is MANAGER or HYBRID, then we are about to update
                    // a Net-domain from the own system. Therefore setting componentId to null 
                    // will reference the domain to be mapped to ourself.
                    $componentId = null;
                    break;
            }
            $critieria = array(
                'componentType'     => $params['post']['componentType'],
                'componentId'       => $componentId,
                'type'              => $params['post']['type'],
            );
            if (isset($params['post']['coreNetDomainId'])) {
                $critieria['coreNetDomainId'] = $params['post']['coreNetDomainId'];
            }
            $objDomain = $domainRepo->findOneBy($critieria);

            if (!$objDomain) {
                throw new MultiSiteJsonException('JsonMultiSiteController::updateDomain() failed: Domain to update not found.');
            }

            $objDomain->setName($params['post']['domainName']);
            \Env::get('em')->flush();
            return array(
                'status' => 'success',
                'log'    => \DBG::getMemoryLogs(),
            );
        } catch (\Exception $e) {
            throw new MultiSiteJsonException($e->getMessage());
        }
    }
    
    /**
     * check the black listed domains
     * 
     * @param string $domainName
     * @return boolean
     */
    public static function checkBlackListDomains($domainName) {
        if (empty($domainName)) {
            return false;
        }
        if (in_array($domainName, array_map('trim', explode(',', \Cx\Core\Setting\Controller\Setting::getValue('domainBlackList','MultiSite'))))) {
            return false;
        }
        return true;
    }

    /**
     * Set the website details
     *
     * @param array $params
     *
     * @return boolean
     * @throws MultiSiteJsonException
     */
    public function setWebsiteDetails($params)
    {
        global $_ARRAYLANG;

        if (   empty($params['post'])
            || (   empty($params['post']['websiteId'])
                && empty($params['post']['websiteName'])
               )
        ) {
            \DBG::msg(
                'JsonMultiSiteController::setWebsiteDetails() failed: Insufficient arguments supplied: ' . var_export($params, true)
            );
            throw new MultiSiteJsonException(
                $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SET_WEBSITE_DETAILS_ERROR']
            );
        }

        $updateDomainMaps = false;
        $mode = \Cx\Core\Setting\Controller\Setting::getValue(
            'mode','MultiSite'
        );
        if ($mode == ComponentController::MODE_WEBSITE) {
            throw new MultiSiteJsonException(
                $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SET_WEBSITE_DETAILS_ERROR']
            );
        }

        try {
            $em      = $this->cx->getDb()->getEntityManager();
            $webRepo = $em->getRepository(
                'Cx\Core_Modules\MultiSite\Model\Entity\Website'
            );
            //find the websites by ID/NAME
            $websiteName = contrexx_input2db($params['post']['websiteName']);
            $websiteId   = contrexx_input2db($params['post']['websiteId']);
            $arguments   = array('website.id' => $websiteId);
            if (!isset($params['post']['websiteId'])) {
                $arguments = array('website.name' => $websiteName);
            }
            $websites = $webRepo->findWebsitesByCriteria($arguments);
            $website  = current($websites);
            if (!$website) {
                \DBG::log(
                    'JsonMultiSiteController::setWebsiteDetails() failed: Website by ID/NAME not found.'
                );
                throw new MultiSiteJsonException(
                    $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SET_WEBSITE_DETAILS_ERROR']
                );
            }

            //Set the website status
            if (isset($params['post']['status'])) {
                $oldStatus = $website->getStatus();
                $website->setStatus(
                    contrexx_input2db($params['post']['status'])
                );
                $newStatus = $website->getStatus();
                if ($oldStatus != $newStatus &&
                    (
                        $oldStatus == \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_DISABLED ||
                        $newStatus == \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_DISABLED
                    )
                ) {
                    $updateDomainMaps = true;
                }
            }
            //Set the website codebase
            if (isset($params['post']['codeBase'])) {
                $website->setCodeBase(
                    contrexx_input2db($params['post']['codeBase'])
                );
            }
            //Set the website owner
            if (   isset($params['post']['userId'])
                && isset($params['post']['email'])
            ) {
                $owner = $em
                    ->getRepository('Cx\Core\User\Model\Entity\User')
                    ->findOneById(contrexx_input2db($params['post']['userId']));
                if (!$owner) {
                    $userDetails = $this->createUser($params, true);
                    $owner = $em
                        ->getRepository('Cx\Core\User\Model\Entity\User')
                        ->findOneById($userDetails['userId']);
                }
                $website->setOwner($owner);
            }
            //Set the website mode and server website
            $oldServerWebsite = $website->getServerWebsite();
            $serverWebsite = null;
            if (    isset($params['post']['serverWebsiteId'])
                &&  isset($params['post']['mode'])
                &&  $params['post']['mode'] == ComponentController::WEBSITE_MODE_CLIENT
            ) {
                $serverWebsite = $webRepo
                    ->findOneById(
                        contrexx_input2db($params['post']['serverWebsiteId'])
                    );
                if (!$serverWebsite) {
                    \DBG::log(
                        'JsonMultiSiteController::setWebsiteDetails() failed: server Website by ID ' .
                        $params['post']['serverWebsiteId'] . ' not found.'
                    );
                    throw new MultiSiteJsonException(
                        $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SET_WEBSITE_DETAILS_ERROR']
                    );
                }
            }
            if (isset($params['post']['mode'])) {
                $website->setMode(contrexx_input2db($params['post']['mode']));
            }
            if (isset($params['post']['serverWebsiteId'])) {
                $website->setServerWebsite($serverWebsite);
            }
            $em->flush();
            $em->getConfiguration()->getResultCacheImpl()->deleteAll();
            if ($updateDomainMaps || $oldServerWebsite != $serverWebsite) {
                $domainRepository = $em->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Domain');
                $domainRepository->exportDomainAndWebsite();
            }
            return true;
        } catch (\Exception $e) {
            \DBG::log($e->getMessage());
            throw new MultiSiteJsonException(
                $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SET_WEBSITE_DETAILS_ERROR']
            );
        }
    }

    /**
     * update Website State
     * 
     * @param array $params
     * 
     */
    public function updateWebsiteState($params) {
        global $_ARRAYLANG;
        self::loadLanguageData();

        if (!empty($params['post'])) {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_MANAGER:
                case ComponentController::MODE_HYBRID:
                    if ($this->setWebsiteDetails($params)){
                        return $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_STATUS_CHANGED_SUCCESSFUL'];
                    }
                    break;

                default:
                    break;
            }
        }
    }

    /**
     * updateOwnWebsiteState
     * 
     * @param  array $params supplied arguments from JsonData-request
     * 
     * @return array
     */
    public function updateOwnWebsiteState($params) 
    {
        global $_ARRAYLANG;
        self::loadLanguageData();
        
        if (\FWValidator::isEmpty($params) || \FWValidator::isEmpty($params['post']['websiteId'])) {
            return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_STATUS_FAILED']);
        }

        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_MANAGER:
                    $website = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website')->findOneById($params['post']['websiteId']);

                    if (!$website) {
                        return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_STATUS_FAILED']);
                    }

                    $status = $website->getStatus() == \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_ONLINE ? 'DEACTIVATED' : 'ACTIVATED';
                    switch ($website->getStatus()) {
                        case \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_ONLINE:
                            $website->setStatus(\Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_OFFLINE);
                            break;
                        case \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_OFFLINE:
                            $website->setStatus(\Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_ONLINE);
                            break;
                        default:
                            return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_STATUS_' . $status . '_FAILED']);
                            break;
                    }

                    \Env::get('em')->flush();
                    return array('status' => 'success', 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_STATUS_' . $status . '_SUCCESSFUL']);
                    break;
            }
        } catch (\Exception $e) {
            \DBG::log($e->getMessage());
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_STATUS_FAILED']);
        }
    }
    
    /**
     * Set the license
     * 
     * @global ADOConnection $objDatabase
     * @param array $params
     * 
     * @return boolean
     * @throws MultiSiteJsonException
     */
    public function setLicense($params) {
        global $objDatabase;

        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_SERVICE:
                    $webRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
                    $website = $webRepo->findOneById($params['post']['websiteId']);
                    $resp = self::executeCommandOnWebsite('setLicense', array('legalComponents' => $params['post']['legalComponents']), $website);                   
                    if ($resp && $resp->data->status == 'success') {
                        return array(
                            'status' => 'success',
                            'log'    => \DBG::getMemoryLogs(),
                        );
                    }
                    break;
                case ComponentController::MODE_WEBSITE:
                    $license = $this->cx->getLicense();
                    $licenseState = isset($params['post']['state']) ? $params['post']['state'] : (isset($params['post']['licenseState']) ? $params['post']['licenseState'] : '');
                    $licenseValidTo = isset($params['post']['validTo']) ? $params['post']['validTo'] : (isset($params['post']['licenseValidTo']) ? $params['post']['licenseValidTo'] : '');
                    $licenseUpdateInterval = isset($params['post']['updateInterval']) ? $params['post']['updateInterval'] : (isset($params['post']['licenseUpdateInterval']) ? $params['post']['licenseUpdateInterval'] : '');
                    $availableComponents = (isset($params['post']['availableComponents']) ? unserialize($params['post']['availableComponents']) : '');
                    $licenseLegalComponents = isset($params['post']['legalComponents'])
                                              ? $params['post']['legalComponents'] 
                                              : (!empty($availableComponents)
                                                ? $availableComponents    
                                                : $license->getAvailableComponents());
                    
                    //If available components is set and not in a proper serialized format, throw error.
                    if (isset($params['post']['availableComponents'])) {
                        if (empty($availableComponents)) {
                            return array('status' => 'error');
                        }
                    }
                    
                    if (!empty($licenseState)) {
                        $license->setState($licenseState);
                    }
                    if (!empty($licenseValidTo)) {
                        $license->setValidToDate($licenseValidTo);
                    }
                    if (!empty($licenseUpdateInterval)) {
                        $license->setUpdateInterval($licenseUpdateInterval);
                    }
                    if (isset($params['post']['dashboardMessages'])) {
                        $dashboardMessages = array();
                        foreach ($params['post']['dashboardMessages'] as $key => $value) {
                            if (!empty($value) && $value != 'undefined') {
                                $lang = !is_string($key) ? \FWLanguage::getLanguageCodeById($key) : $key;
                                $licenseText = isset($value['text']) ? $value['text'] : '';
                                $licenseType = isset($value['type']) ? $value['type'] : '';
                                $licenseLink = isset($value['link']) ? $value['link'] : '';
                                $licenseLinkTarget = isset($value['linkTarget']) ? $value['linkTarget'] : '';
                                $dashboardMessages[$lang] = new \Cx\Core_Modules\License\Message($lang, $licenseText, $licenseType, $licenseLink, $licenseLinkTarget, true);
                            }
                        }
                        $license->setDashboardMessages($dashboardMessages);
                    }
                    if (!empty($licenseLegalComponents)) {
                        $license->setAvailableComponents($licenseLegalComponents);
                        $license->setLegalComponents($licenseLegalComponents);
                    }
                    if (isset($params['post']['isUpgradable'])) {
                        $license->setIsUpgradable($params['post']['isUpgradable']);
                    }
                    if (isset($params['post']['licenseMessage'])) {
                        $licenseMessage = array();
                        foreach ($params['post']['licenseMessage'] as $key => $value) {
                            if (!empty($value) && $value != 'undefined') {
                                $lang = !is_string($key) ? \FWLanguage::getLanguageCodeById($key) : $key;
                                $licenseMessage[$lang] = new \Cx\Core_Modules\License\Message($lang, $value['text']);
                            }
                        }
                        $license->setMessages($licenseMessage);
                    }
                    if (isset($params['post']['licenseGrayzoneMessages'])) {
                        $licenseGrayzoneMessages = array();
                        foreach ($params['post']['licenseGrayzoneMessages'] as $key => $value) {
                            if (!empty($value) && $value != 'undefined') {
                                $lang = !is_string($key) ? \FWLanguage::getLanguageCodeById($key) : $key;
                                $licenseGrayzoneMessages[$lang] = new \Cx\Core_Modules\License\Message($lang, $value['text']);
                            }
                        }
                        $license->setGrayZoneMessages($licenseGrayzoneMessages);
                    }
                    if (isset($params['post']['licenseFailedUpdate'])) {
                        $license->setFirstFailedUpdateTime($params['post']['licenseFailedUpdate']);
                    }
                    if (isset($params['post']['licenseSuccessfulUpdate'])) {
                        $license->setLastSuccessfulUpdateTime($params['post']['licenseSuccessfulUpdate']);
                    }
                    if (isset($params['post']['licenseKey'])) {
                        $license->setLicenseKey($params['post']['licenseKey']);
                    }
                    if (isset($params['post']['installationId'])) {
                        $license->setInstallationId($params['post']['installationId']);
                    }
                    if (isset($params['post']['licensePartnerTitle'])) {
                        $license->getPartner()->setTitle($params['post']['licensePartnerTitle']);
                    }
                    if (isset($params['post']['licensePartnerLastname'])) {
                        $license->getPartner()->setLastname($params['post']['licensePartnerLastname']);
                    }
                    if (isset($params['post']['licensePartnerFirstname'])) {
                        $license->getPartner()->setFirstname($params['post']['licensePartnerFirstname']);
                    }
                    if (isset($params['post']['licensePartnerCompanyname'])) {
                        $license->getPartner()->setCompanyName($params['post']['licensePartnerCompanyname']);
                    }
                    if (isset($params['post']['licensePartnerAddress'])) {
                        $license->getPartner()->setAddress($params['post']['licensePartnerAddress']);
                    }
                    if (isset($params['post']['licensePartnerZip'])) {
                        $license->getPartner()->setZip($params['post']['licensePartnerZip']);
                    }
                    if (isset($params['post']['licensePartnerCity'])) {
                        $license->getPartner()->setCity($params['post']['licensePartnerCity']);
                    }
                    if (isset($params['post']['licensePartnerCountry'])) {
                        $license->getPartner()->setCountry($params['post']['licensePartnerCountry']);
                    }
                    if (isset($params['post']['licensePartnerPhone'])) {
                        $license->getPartner()->setPhone($params['post']['licensePartnerPhone']);
                    }
                    if (isset($params['post']['licensePartnerUrl'])) {
                        $license->getPartner()->setUrl($params['post']['licensePartnerUrl']);
                    }
                    if (isset($params['post']['licensePartnerMail'])) {
                        $license->getPartner()->setMail($params['post']['licensePartnerMail']);
                    }
                    if (isset($params['post']['licenseCustomerTitle'])) {
                        $license->getCustomer()->setTitle($params['post']['licenseCustomerTitle']);
                    }
                    if (isset($params['post']['licenseCustomerLastname'])) {
                        $license->getCustomer()->setLastname($params['post']['licenseCustomerLastname']);
                    }
                    if (isset($params['post']['licenseCustomerFirstname'])) {
                        $license->getCustomer()->setFirstname($params['post']['licenseCustomerFirstname']);
                    }
                    if (isset($params['post']['licenseCustomerCompanyname'])) {
                        $license->getCustomer()->setCompanyName($params['post']['licenseCustomerCompanyname']);
                    }
                    if (isset($params['post']['licenseCustomerAddress'])) {
                        $license->getCustomer()->setAddress($params['post']['licenseCustomerAddress']);
                    }
                    if (isset($params['post']['licenseCustomerZip'])) {
                        $license->getCustomer()->setZip($params['post']['licenseCustomerZip']);
                    }
                    if (isset($params['post']['licenseCustomerCity'])) {
                        $license->getCustomer()->setCity($params['post']['licenseCustomerCity']);
                    }
                    if (isset($params['post']['licenseCustomerCountry'])) {
                        $license->getCustomer()->setCountry($params['post']['licenseCustomerCountry']);
                    }
                    if (isset($params['post']['licenseCustomerPhone'])) {
                        $license->getCustomer()->setPhone($params['post']['licenseCustomerPhone']);
                    }
                    if (isset($params['post']['licenseCustomerUrl'])) {
                        $license->getCustomer()->setUrl($params['post']['licenseCustomerUrl']);
                    }
                    if (isset($params['post']['licenseCustomerMail'])) {
                        $license->getCustomer()->setMail($params['post']['licenseCustomerMail']);
                    }
                    if (isset($params['post']['upgradeUrl'])) {
                        $license->setUpgradeUrl($params['post']['upgradeUrl']);
                    }
                    if (isset($params['post']['licenseCreatedAt'])) {
                        $license->setCreatedAtDate($params['post']['licenseCreatedAt']);
                    }
                    if (isset($params['post']['licenseDomains'])) {
                        $license->setRegisteredDomains(explode(', ',$params['post']['licenseDomains']));
                    }
                    if (isset($params['post']['isUpgradable'])) {
                        $license->setIsUpgradable($params['post']['isUpgradable']);
                    }
                    if (isset($params['post']['licenseGrayzoneTime'])) {
                        $license->setGrayzoneTime($params['post']['licenseGrayzoneTime']);
                    }
                    if (isset($params['post']['licenseLockTime'])) {
                        $license->setFrontendLockTime($params['post']['licenseLockTime']);
                    }
                    if (isset($params['post']['licenseUpdateInterval'])) {
                        $license->setRequestInterval($params['post']['licenseUpdateInterval']);
                    }
                    if (isset($params['post']['coreCmsEdition'])) {
                        $license->setEditionName($params['post']['coreCmsEdition']);
                    }
                    if (isset($params['post']['coreCmsVersion'])) {
                        $license->getVersion()->setNumber($params['post']['coreCmsVersion']);
                    }
                    if (isset($params['post']['coreCmsCodeName'])) {
                        $license->getVersion()->setCodeName($params['post']['coreCmsCodeName']);
                    }
                    if (isset($params['post']['coreCmsStatus'])) {
                        $license->getVersion()->setState($params['post']['coreCmsStatus']);
                    }
                    if (isset($params['post']['coreCmsReleaseDate'])) {
                        $license->getVersion()->setReleaseDate($params['post']['coreCmsReleaseDate']);
                    }
                    if (isset($params['post']['coreCmsName'])) {
                        $license->getVersion()->setName($params['post']['coreCmsName']);
                    }
                    
                    try {
                        $license->save($objDatabase);
                        return array(
                            'status' => 'success',
                            'log'    => \DBG::getMemoryLogs(),
                        );
                    } catch (\Exception $e) {
                        throw new MultiSiteJsonException('Unable to save the setup license'.$e->getMessage());
                    }
                    break;
            }
            return array(
                'status' => 'error',
                'log'    => \DBG::getMemoryLogs(),
            );
        } catch (\Exception $e) {
            throw new MultiSiteJsonException(array(
                'log'       => \DBG::getMemoryLogs(),
                'message'   => 'Unable to setup license: '.$e->getMessage(),
            ));
        }
    }
                
    /**
     * Returns the $isIscRequest value
     * 
     * @return boolean
     */
    public static function isIscRequest() {
        return self::$isIscRequest;
    }
    
    /**
     * Return the status message
     * 
     * @return array
     */
    public function ping() {
        global $_CONFIG;

        // Check the system is in maintenance mode or not
        if ($_CONFIG['systemStatus'] != 'on') {
            return array(
                'status' => 'error',
                'message' => 'Service Server is currently in maintenance mode',
            );
        }

        $pongStartTime = microtime(true);
        $resp = static::executeCommandOnManager('pong');
        if (
            $resp &&
            $resp->status == 'success' &&
            $resp->data->status == 'success'
        ) {
            return array(
                'status' => 'success',
                'pongTime' => round((microtime(true) - $pongStartTime) * 1000),
            );
        }

        return array(
            'status' => 'error',
            'message' => 'Reverse connection failed',
        );
    }
    
    /**
     * Return the status message
     * 
     * @return array
     */
    public function pong() {
        return array('status' => 'success');
    }
    
    public static function executeCommand($host, $command, $params, $secretKey, $installationId, $httpAuth, $files, $async) {
        $params['auth'] = self::getAuthenticationObject($secretKey, $installationId);
        $hostUrl = \Cx\Core_Modules\MultiSite\Controller\ComponentController::getApiProtocol() . $host;
        if ($async) {
            $data = http_build_query($params, null, "&");
            $curl = 'curl -X POST -d "' . $data . '" -k ' . $hostUrl . '/cadmin/index.php"?cmd=JsonData&object=MultiSite&act=' . $command . '" > /dev/null 2>&1 &';
            exec($curl);
        } else {
            $objJsonData = new \Cx\Core\Json\JsonData();
            return $objJsonData->getJson($hostUrl . '/cadmin/index.php?cmd=JsonData&object=MultiSite&act=' . $command, $params, false, '', $httpAuth, $files);
        }
    }

    /**
     * This method will be used by the Website Service/Website
     * to execute commands on the Website Manager
     * Fetch connection data to Manager and pass it to the method executeCommand()
     */
    public static function executeCommandOnManager(
        $command,
        $params = array(),
        $files = array(),
        $async = false
    ) {
        $mode = \Cx\Core\Setting\Controller\Setting::getValue(
            'mode','MultiSite'
        );
        try {
            switch ($mode) {
                case ComponentController::MODE_MANAGER:
                case ComponentController::MODE_HYBRID:
                    \DBG::msg(
                        __METHOD__ . ' (' . $command . '): executing locally on manager (not going to execute through JsonData adapter)'
                    );
                    $config = \Env::get('config');
                    $params['auth'] = json_encode(
                        array('sender' => $config['domainUrl'])
                    );

                    // Get JsonMultiSiteController object
                    $em = \Cx\Core\Core\Controller\Cx::instanciate()
                        ->getDb()->getEntityManager();
                    $componentRepo = $em
                        ->getRepository(
                            'Cx\Core\Core\Model\Entity\SystemComponent'
                        );
                    $component = $componentRepo
                        ->findOneBy(array('name' => 'MultiSite'));
                    $objJsonMultiSite = $component
                        ->getController('JsonMultiSite');
                    $result = $objJsonMultiSite
                        ->$command(array('post' => $params));
                    // Convert $result (which is an array) into an object
                    // as JsonData->getJson (called by self::executeCommand())
                    // would do/return that.
                    return json_decode(
                        json_encode(
                            array('status' => 'success', 'data' => $result)
                        )
                    );
                    break;
                case ComponentController::MODE_SERVICE:
                    $host = \Cx\Core\Setting\Controller\Setting::getValue(
                        'managerHostname','MultiSite'
                    );
                    $installationId = \Cx\Core\Setting\Controller\Setting::getValue(
                        'managerInstallationId','MultiSite'
                    );
                    $secretKey = \Cx\Core\Setting\Controller\Setting::getValue(
                        'managerSecretKey','MultiSite'
                    );
                    $httpAuth = array(
                        'httpAuthMethod'   => \Cx\Core\Setting\Controller\Setting::getValue(
                            'managerHttpAuthMethod','MultiSite'
                        ),
                        'httpAuthUsername' => \Cx\Core\Setting\Controller\Setting::getValue(
                            'managerHttpAuthUsername','MultiSite'
                        ),
                        'httpAuthPassword' => \Cx\Core\Setting\Controller\Setting::getValue(
                            'managerHttpAuthPassword','MultiSite'
                        ),
                    );
                    break;
                case ComponentController::MODE_WEBSITE:
                    $params  = array(
                        'command' => $command,
                        'params' => $params
                    );
                    $command = 'executeOnManager';
                    $host = \Cx\Core\Setting\Controller\Setting::getValue(
                        'serviceHostname','MultiSite'
                    );
                    $installationId = \Cx\Core\Setting\Controller\Setting::getValue(
                        'serviceInstallationId','MultiSite'
                    );
                    $secretKey = \Cx\Core\Setting\Controller\Setting::getValue(
                        'serviceSecretKey','MultiSite'
                    );
                    $httpAuth = array(
                        'httpAuthMethod'   => \Cx\Core\Setting\Controller\Setting::getValue(
                            'serviceHttpAuthMethod','MultiSite'
                        ),
                        'httpAuthUsername' => \Cx\Core\Setting\Controller\Setting::getValue(
                            'serviceHttpAuthUsername','MultiSite'
                        ),
                        'httpAuthPassword' => \Cx\Core\Setting\Controller\Setting::getValue(
                            'serviceHttpAuthPassword','MultiSite'
                        )
                    );
                    break;
            }
            return self::executeCommand(
                $host, $command, $params, $secretKey, $installationId,
                $httpAuth, $files, $async
            );
        } catch (\Exception $e) {
            throw new MultiSiteJsonException($e->getMessage());
        }
    }

    /**
     * This method will be used by a Websites to execute commands on its Website Service
     * Fetch connection data to Service and pass it to the method executeCommand()
     */
    public static function executeCommandOnMyServiceServer($command, $params, $files = array(), $async = false) {
        if (!in_array(\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite'), array(ComponentController::MODE_WEBSITE))) {
            throw new MultiSiteJsonException('Command'.__METHOD__.' is only available in MultiSite-mode WEBSITE.');
        }
        $host = \Cx\Core\Setting\Controller\Setting::getValue('serviceHostname','MultiSite');
        $installationId = \Cx\Core\Setting\Controller\Setting::getValue('serviceInstallationId','MultiSite');
        $secretKey = \Cx\Core\Setting\Controller\Setting::getValue('serviceSecretKey','MultiSite');
        $httpAuth = array(
            'httpAuthMethod' => \Cx\Core\Setting\Controller\Setting::getValue('serviceHttpAuthMethod','MultiSite'),
            'httpAuthUsername' => \Cx\Core\Setting\Controller\Setting::getValue('serviceHttpAuthUsername','MultiSite'),
            'httpAuthPassword' => \Cx\Core\Setting\Controller\Setting::getValue('serviceHttpAuthPassword','MultiSite'),
        );
        return self::executeCommand($host, $command, $params, $secretKey, $installationId, $httpAuth, $files, $async);
    }
    
    /**
     * This method will be used by the Website Manager to execute commands on the Website Service
     * Fetch connection data to Service and pass it to the method executeCommand()
     */
    public static function executeCommandOnServiceServerOfWebsite($command, $params, $website, $files = array(), $async = false) {
        if (!in_array(\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite'), array(ComponentController::MODE_MANAGER))) {
            throw new MultiSiteJsonException('Command'.__METHOD__.' is only available in MultiSite-mode MANAGER.');
        }
        $websiteServiceServer = $website->getWebsiteServiceServer();
        $host = $websiteServiceServer->getHostname();
        $installationId = $websiteServiceServer->getInstallationId();
        $secretKey = $websiteServiceServer->getSecretKey();
        $httpAuth = array(
            'httpAuthMethod' => $websiteServiceServer->getHttpAuthMethod(),
            'httpAuthUsername' => $websiteServiceServer->getHttpAuthUsername(),
            'httpAuthPassword' => $websiteServiceServer->getHttpAuthPassword(),
        );
        return self::executeCommand($host, $command, $params, $secretKey, $installationId, $httpAuth, $files, $async);
    }

    /**
     * This method will be used by the Website Manager to execute commands on a Website Service
     * Fetch connection data to Service and pass it to the method executeCommand():
     */
    public static function executeCommandOnServiceServer($command, $params, $websiteServiceServer, $files = array(), $async = false) {
        if (!in_array(\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite'), array(ComponentController::MODE_MANAGER))) {
            throw new MultiSiteJsonException('Command'.__METHOD__.' is only available in MultiSite-mode MANAGER.');
        }
        $host = $websiteServiceServer->getHostname();
        $installationId = $websiteServiceServer->getInstallationId();
        $secretKey = $websiteServiceServer->getSecretKey();
        $httpAuth = array(
            'httpAuthMethod' => $websiteServiceServer->getHttpAuthMethod(),
            'httpAuthUsername' => $websiteServiceServer->getHttpAuthUsername(),
            'httpAuthPassword' => $websiteServiceServer->getHttpAuthPassword(),
        );
        return self::executeCommand($host, $command, $params, $secretKey, $installationId, $httpAuth, $files, $async);
    }

    /**
     * This method will be used by the Website Service to execute commands on a Website
     * Fetch connection data to Website and pass it to the method executeCommand():
     */
    public static function executeCommandOnWebsite($command, $params, $website, $files = array(), $async = false) {
        if (!in_array(\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite'), array(ComponentController::MODE_MANAGER, ComponentController::MODE_HYBRID, ComponentController::MODE_SERVICE))) {
            throw new MultiSiteJsonException('Command '.__METHOD__.' is only available in MultiSite-mode MODE_MANAGER, HYBRID or SERVICE (running on '.\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite').' '.$website->getName().')');
        }

        // In case mode is Manager, the request shall be routed through the associated Service Server
        if (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite') == ComponentController::MODE_MANAGER) {
            return self::executeCommandOnServiceServerOfWebsite('executeOnWebsite', array('command' => $command, 'params' => $params, 'websiteId' => $website->getId()), $website, $files, $async);
        }

        // JsonData requests shall be made to the FQDN of the Website,
        // as the BaseDn might not yet work as it depends on the DNS synchronization.
        $host = $website->getFqdn()->getName();
        $installationId = $website->getInstallationId();
        $secretKey = $website->getSecretKey();
        $httpAuth = array(
            'httpAuthMethod' => \Cx\Core\Setting\Controller\Setting::getValue('websiteHttpAuthMethod','MultiSite'),
            'httpAuthUsername' => \Cx\Core\Setting\Controller\Setting::getValue('websiteHttpAuthUsername','MultiSite'),
            'httpAuthPassword' => \Cx\Core\Setting\Controller\Setting::getValue('websiteHttpAuthPassword','MultiSite'),
        );
        return self::executeCommand($host, $command, $params, $secretKey, $installationId, $httpAuth, $files, $async);
    }

    public function executeOnManager($params) {
        if (empty($params['post']['command'])) {
            \DBG::dump($params);
            throw new MultiSiteJsonException('JsonMultiSiteController::executeOnManager() on '.\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite').' failed: Insufficient arguments supplied: '.var_export($params, true));
        }

        $passedParams = !empty($params['post']['params']) ? $params['post']['params'] : null;
        
        try {
            // special case for updateUser
            if ($params['post']['command'] == 'updateUser') {
                $authenticationValue = json_decode($params['post']['auth'], true);
                if (empty($authenticationValue) || !is_array($authenticationValue)) {
                    throw new MultiSiteJsonException(__METHOD__.': Insufficient mapping information supplied.');
                }
                $domainRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Domain');
                $domain     = $domainRepo->findOneBy(array('name' => $authenticationValue['sender']));
                if (!$domain || !$domain->getWebsite()) {
                    throw new MultiSiteJsonException(__METHOD__.': Unknown website sender');
                }
                $ownerId = $domain->getWebsite()->getOwner()->getId();
                $passedParams['userId'] = $ownerId;
            }

            $resp = self::executeCommandOnManager($params['post']['command'], $passedParams);
            if ($resp && $resp->status == 'success') {
                return $resp->data;
            } else {
                throw new MultiSiteJsonException(var_export($resp, true));
            }
        } catch (\Exception $e) {
            throw new MultiSiteJsonException('JsonMultiSiteController::executeOnManager() failed: ' . $e->getMessage());
        }
    }

    public function executeOnWebsite($params) {
        if (empty($params['post']['command']) || empty($params['post']['websiteId'])) {
            \DBG::dump($params);
            throw new MultiSiteJsonException('JsonMultiSiteController::executeOnWebsite() on '.\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite').' failed: Insufficient arguments supplied: '.var_export($params, true));
        }
        
        $webRepo  = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
        $website  = $webRepo->findOneById($params['post']['websiteId']);
        if (!$website) {
            throw new MultiSiteJsonException('JsonMultiSiteController::executeOnWebsite() failed: Website by ID '.$params['post']['websiteId'].' not found.');
        }

        $passedParams = !empty($params['post']['params']) ? $params['post']['params'] : null;
        
        try {
            $resp = self::executeCommandOnWebsite($params['post']['command'], $passedParams, $website);
            if ($resp && $resp->status == 'success' && $resp->data->status == 'success') {
                return $resp->data;
            } else {
                if (isset($resp->log)) {
                    \DBG::appendLogs(array_map(function($logEntry) {return '(Website: '.$website->getName().') '.$logEntry;}, $resp->log));
                }
                throw new MultiSiteJsonException($resp && $resp->message ? $resp->message : '');
            }
        } catch (\Exception $e) {
            \DBG::msg(__METHOD__.': ' . $e->getMessage());
            return array(
                'status'    => 'error',
                'log'       => \DBG::getMemoryLogs(),
                'message'   => $e->getMessage(),
            );
        }
    }

    public function generateAuthToken($params) {
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_MANAGER:
                case ComponentController::MODE_HYBRID:
                    if (empty($params['post']['ownerId'])) {
                        return array(
                            'status' => 'error',
                            'log' => \DBG::getMemoryLogs(),
                        );
                    }
                    $websiteUserId = $params['post']['ownerId'];
                    break;
                case ComponentController::MODE_WEBSITE:
                    $websiteUserId = \Cx\Core\Setting\Controller\Setting::getValue('websiteUserId','MultiSite');
                    break;
                default:
                    break;
            }
            $objUser = \FWUser::getFWUserObject()->objUser->getUser(intval($websiteUserId));
            if (!$objUser) {
                throw new MultiSiteJsonException('Unable to load website Owner user account');
            }
            $authToken = $objUser->generateAuthToken();
            if (!$objUser->store()) {
                \DBG::msg('Updating user failed: '.$objUser->getErrorMsg());
                throw new MultiSiteJsonException($objUser->getErrorMsg());
            }
            return array(
                'status'    => 'success',
                'userId'    => $websiteUserId,
                'authToken' => $authToken,
                'log'       => \DBG::getMemoryLogs(),
            );
        } catch (\Exception $e) {
            throw new MultiSiteJsonException(array(
                'log'       => \DBG::getMemoryLogs(),
                'message'   => 'JsonMultiSiteController::generateAuthToken() failed: ' . $e->getMessage(),
            ));
        }
    }
    
    /**
     * setup the config options
     */
    public function setupConfig($params) {
        if (empty($params['post']['coreAdminEmail']) || empty($params['post']['contactFormEmail']) || empty($params['post']['dashboardNewsSrc'])) {
            throw new MultiSiteJsonException('JsonMultiSiteController::setupConfig() on '.\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite').' failed: Insufficient arguments supplied: '.var_export($params, true));
        }
        
        \Cx\Core\Setting\Controller\Setting::init('Config', '','Yaml');
        if (!\Cx\Core\Setting\Controller\Setting::isDefined('defaultLocaleId') 
                && !\Cx\Core\Setting\Controller\Setting::add('defaultLocaleId',  $params['post']['defaultLocaleId'], 13, \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN, '{src:\\Cx\\Core\\Config\\Controller\\Config::getLocales()}', 'site')) {
            throw new MultiSiteJsonException(array(
                'log'       => \DBG::getMemoryLogs(),
                'message'   => "Failed to add Setting entry for defaultLocaleId",
            ));
        }
        if (!\Cx\Core\Setting\Controller\Setting::isDefined('defaultLanguageId') 
                && !\Cx\Core\Setting\Controller\Setting::add('defaultLanguageId', $params['post']['defaultLanguageId'], 9, \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN, '{src:\\Cx\\Core\\Config\\Controller\\Config::getBackendLanguages()}', 'administrationArea')) {
            throw new MultiSiteJsonException(array(
                'log'       => \DBG::getMemoryLogs(),
                'message'   => "Failed to add Setting entry for dashboardNewsSrc",
            ));
        }
        if (!\Cx\Core\Setting\Controller\Setting::isDefined('dashboardNewsSrc') 
                && !\Cx\Core\Setting\Controller\Setting::add('dashboardNewsSrc', $params['post']['dashboardNewsSrc'], 2, \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'component')) {
            throw new MultiSiteJsonException(array(
                'log'       => \DBG::getMemoryLogs(),
                'message'   => "Failed to add Setting entry for dashboardNewsSrc",
            ));
        }
        if (!\Cx\Core\Setting\Controller\Setting::isDefined('coreAdminEmail') 
                && !\Cx\Core\Setting\Controller\Setting::add('coreAdminEmail', $params['post']['coreAdminEmail'], 3, \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'contactInformation')) {
            throw new MultiSiteJsonException(array(
                'log'       => \DBG::getMemoryLogs(),
                'message'   => "Failed to add Setting entry for coreAdminEmail",
            ));
        }
        if (!\Cx\Core\Setting\Controller\Setting::isDefined('contactFormEmail') 
                && !\Cx\Core\Setting\Controller\Setting::add('contactFormEmail', $params['post']['contactFormEmail'], 4, \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'contactInformation')) {
            throw new MultiSiteJsonException(array(
                'log'       => \DBG::getMemoryLogs(),
                'message'   => "Failed to add Setting entry for contactFormEmail",
            ));
        }
        \Cx\Core\Config\Controller\Config::init();
       
        // we must re-initialize the original MultiSite settings of the main installation
        \Cx\Core\Setting\Controller\Setting::init('MultiSite', '', 'FileSystem');
        return array(
            'status' => 'success',
            'log'    => \DBG::getMemoryLogs(),
        );
    }

    public function getDefaultWebsiteIp() {
        if (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite') != ComponentController::MODE_SERVICE) {
            throw new MultiSiteJsonException('JsonMultiSiteController::getDefaultWebsiteIp() failed: Command is only on Website Service Server available.');
        }

        return array(
            'status'            => 'success',
            'defaultWebsiteIp'  => \Cx\Core\Setting\Controller\Setting::getValue('defaultWebsiteIp','MultiSite'),
            'log'               => \DBG::getMemoryLogs(),
        );
    }
    
    /**
     * Check current user is website owner or not
     * 
     * @return boolean
     */
    public static function isWebsiteOwner() {
        //check user logged in or not
        if (!\FWUser::getFWUserObject()->objUser->login()) {
            return false;
        }
        
        //Check the user is website owner or not
        if (\FWUser::getFWUserObject()->objUser->getId() == \Cx\Core\Setting\Controller\Setting::getValue('websiteUserId','MultiSite')) {
            return true;
        }
        return false;
    }
    
    /**
     * Get the Ftp user
     * 
     * @param array $params
     * 
     * @return array
     * @throws MultiSiteJsonException
     */
    public function getFtpUser($params) {
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_SERVICE:
                case ComponentController::MODE_HYBRID:    
                    $authenticationValue = json_decode($params['post']['auth'], true);
                    if (empty($authenticationValue) || !is_array($authenticationValue)) {
                        \DBG::dump($params);
                        throw new MultiSiteJsonException('JsonMultiSiteController::getFtpUser() on ' . \Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite') . ' failed: Insufficient reset information supplied.' . var_export($params, true));
                    }

                    $domainRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Domain');
                    $domain = $domainRepo->findOneBy(array('name' => $authenticationValue['sender']));
                    if (!$domain) {
                        throw new MultiSiteJsonException('JsonMultiSiteController::getFtpUser() failed: Unkown Website: ' . $authenticationValue['sender']);
                    }

                    $website = $domain->getWebsite();
                    if (!$website) {
                        throw new MultiSiteJsonException('JsonMultiSiteController::getFtpUser() failed: Unkown Website: ' . $authenticationValue['sender']);
                    }

                    if ($website->getFtpUser()) {
                        return array(
                            'status'    => 'success',
                            'ftpUser'   => $website->getFtpUser(),
                            'log'       => \DBG::getMemoryLogs(),
                        );
                    }
                    break;
            }
            throw new MultiSiteJsonException('JsonMultiSiteController::getFtpUser() failed: Website Ftp user field is empty.');
        } catch (\Exception $e) {
            throw new MultiSiteJsonException('JsonMultiSiteController::getFtpUser() failed: to get website FTP user: ' . $e->getMessage());
        }
    }
    
    /**
     * Get all Ftp user accounts
     * 
     * @param array $params
     * 
     * @return array
     * @throws MultiSiteJsonException
     */
    public function getFtpAccounts($params) {
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_SERVICE:
                case ComponentController::MODE_HYBRID:
                    $hostingController = \Cx\Core_Modules\MultiSite\Controller\ComponentController::getHostingController();
                    $ftpUserAccounts   = $hostingController->getAllEndUserAccounts(true);
                    if ($ftpUserAccounts) {
                        return array(
                            'status' => 'success',
                            'data'   => $ftpUserAccounts,
                            'log'    => \DBG::getMemoryLogs(),
                        );
                    }
                    break;
            }
            throw new MultiSiteJsonException('JsonMultiSiteController::getFtpAccounts() failed');
        } catch (\Exception $e) {
            throw new MultiSiteJsonException('JsonMultiSiteController::getFtpAccounts() failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Reset the FTP Password
     * 
     * @param array $params
     * 
     * @return array
     * @throws MultiSiteJsonException
     */
    public function resetFtpPassword($params) {
        global $_ARRAYLANG;
        self::loadLanguageData();
        
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_WEBSITE:
                    $response = self::executeCommandOnMyServiceServer('resetFtpPassword', array());
                    if ($response && $response->status == 'success' && $response->data->status == 'success') {
                        return array(
                            'status'    => 'success',
                            'message'   => $_ARRAYLANG['TXT_MULTISITE_RESET_FTP_PASS_MSG'],
                            'password'  => $response->data->password,
                            'log'       => \DBG::getMemoryLogs(),
                        );
                    }
                    if (isset($response->log)) {
                        \DBG::appendLogs(array_map(function($logEntry) {return '(Service) '.$logEntry;}, $response->log));
                    }
                    if (isset($response->message)) {
                        \DBG::msg($response->message);
                    }
                    break;
                case ComponentController::MODE_SERVICE:
                case ComponentController::MODE_HYBRID:
                    $authenticationValue = json_decode($params['post']['auth'], true);
                    if (empty($authenticationValue) || !is_array($authenticationValue)) {
                        \DBG::dump($params);
                        throw new MultiSiteJsonException('JsonMultiSiteController::resetFtpPassword() on ' . \Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite') . ' failed: Insufficient reset information supplied.' . var_export($params, true));
                    }

                    $domainRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Domain');
                    $domain = $domainRepo->findOneBy(array('name' => $authenticationValue['sender']));
                    if (!$domain) {
                        throw new MultiSiteJsonException('JsonMultiSiteController::resetFtpPassword() failed: Unkown Website: ' . $authenticationValue['sender']);
                    }

                    $website = $domain->getWebsite();
                    if (!$website) {
                        throw new MultiSiteJsonException('JsonMultiSiteController::resetFtpPassword() failed: Unkown Website: ' . $authenticationValue['sender']);
                    }
                    
                    $hostingController = ComponentController::getHostingController();
                    
                    $password = \User::make_password(8, true);
                    if ($hostingController->changeEndUserAccountPassword($website->getFtpUser(), $password)) {
                        return array(
                            'status'    => 'success',
                            'password'  => $password,
                            'log'       => \DBG::getMemoryLogs(),
                        );
                    }
                    break;
            }

            return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_RESET_FTP_PASS_ERROR_MSG']);
        } catch (\Exception $e) {
            throw new MultiSiteJsonException('JsonMultiSiteController::resetFtpPassword() failed: Updating FTP password.' . $e->getMessage());
        }
    }
    
    /**
     * Check the Authentication access for resetting the FTP password
     * 
     * @return boolean
     */
    public function checkResetFtpPasswordAccess($params) {
        switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
            case ComponentController::MODE_WEBSITE:
                if (self::isWebsiteOwner()) {
                    return true;
                }
                break;
            case ComponentController::MODE_SERVICE:
            case ComponentController::MODE_HYBRID:
                if ($this->auth($params)) {
                    return true;
                }
                break;
        }
        return false;
    }
        
    /**
     * Check the Authentication access for execute sql query in website
     * 
     * @return boolean
     */
    public function checkAuthenticationByMode($params) {
        switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
            case ComponentController::MODE_MANAGER:
            case ComponentController::MODE_HYBRID:
                if ($this->checkPermission()) {
                    return true;
                }
                break;
            case ComponentController::MODE_SERVICE:
            case ComponentController::MODE_WEBSITE:
                if ($this->auth($params)) {
                    return true;
                }
                break;
        }
        return false;
    }
    
    /**
     * Check the Authentication access for get license
     * 
     * @return boolean
     */
    public function checkGetLicenseAccess($params) {
        switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
            case ComponentController::MODE_MANAGER:
            case ComponentController::MODE_HYBRID:
            case ComponentController::MODE_SERVICE:
                if ($this->checkPermission()) {
                    return true;
                }
                break;
            case ComponentController::MODE_WEBSITE:
                if ($this->auth($params)) {
                    return true;
                }
                break;
        }
        return false;
    }
    /**
     * check authentication access for send account activation email
     * 
     * @return boolean
     */
    public function checkSendAccountActivation($params) {
        switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
            case ComponentController::MODE_MANAGER:
            case ComponentController::MODE_SERVICE:
                if ($this->auth($params)) {
                    return true;
                }
                break;
            case ComponentController::MODE_WEBSITE:
                if ($this->checkPermission()) {
                    return true;
                }
                break;
        }
        return false;
    }
    
    /*
     * Updating setup data in servers
     * 
     * @return boolean
     */
    public function updateServiceServerSetup($params) {

        if (empty($params['post']['setupArray'])) {
            throw new MultiSiteJsonException('JsonMultiSiteController::updateServiceServerSetup(): Updating setup data in server failed due to empty params in post method.');
        }
        
        try {
            \Cx\Core\Setting\Controller\Setting::init('MultiSite', 'setup', 'FileSystem');
            $setupValues = $params['post']['setupArray'];
            foreach($setupValues as $valuesName => $value) {
                \Cx\Core\Setting\Controller\Setting::set($valuesName, $value['value']);
                \Cx\Core\Setting\Controller\Setting::update($valuesName);
            }
        } catch (\Exception $e) {
            throw new MultiSiteJsonException('JsonMultiSiteController::updateServiceServerSetup() failed: Updating setup data in server .' . $e->getMessage());
        }
    }
    
    /**
     * Completely removes an website
     * 
     */
    public function destroyWebsite($params) {
        
        if (empty($params['post']['websiteId'])) {
            throw new MultiSiteJsonException('JsonMultiSiteController (destroyWebsite): failed to destroy the website due to the empty param $websiteId');
        }

        try {
            $webRepo  = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
            $website  = $webRepo->findOneById($params['post']['websiteId']);
            if (!$website) {
                return array(
                    'status'    => 'success',
                    'log'       => \DBG::getMemoryLogs(),
                );            
            }
            \Env::get('em')->remove($website);
            \Env::get('em')->flush();
            return array(
                'status'    => 'success',
                'log'       => \DBG::getMemoryLogs(),
            );
        } catch (\Exception $e) {
            throw new MultiSiteJsonException(array(
                'log'       => \DBG::getMemoryLogs(),
                'message'   => 'JsonMultiSiteController (destroyWebsite): failed to destroy the website.' . $e->getMessage(),
            ));
        }
    }

    /**
     * Set the Website Theme
     * 
     * @global \Cx\Core_Modules\MultiSite\Controller\ADOConnection $objDatabase
     * @param array $params
     * 
     * @return array
     * @throws MultiSiteJsonException
     */
    public function setWebsiteTheme($params) {
        global $objDatabase;

        if (empty($params['post']['themeId'])) {
            throw new MultiSiteJsonException('JsonMultiSiteController (setWebsiteTheme): failed to set the website theme due to the empty param $themeId');
        }

        try {
            $themeId = intval($params['post']['themeId']);
            $em = \Cx\Core\Core\Controller\Cx::instanciate()
                ->getDb()
                ->getEntityManager();
            $frontendRepo = $em->getRepository('Cx\Core\View\Model\Entity\Frontend');
            $frontends = $frontendRepo->findAll();
            foreach ($frontends as $frontend) {
                $frontend->setTheme($themeId);
                $em->persist($frontend);
            }
            $em->flush();
        
            return array(
                'status'    => 'success',
                'log'       => \DBG::getMemoryLogs(),
            );
        } catch (\Exception $e) {
            return array(
                'status' => 'error',
                'message' => 'JsonMultiSiteController (setWebsiteTheme): failed to set the website theme.' . $e->getMessage(),
                'log'    => \DBG::getMemoryLogs(),
            );
        }
    }
    
    /**
     * Executing Sql query
     * 
     * @global $objDatabase
     * @param type $params
     * @return type
     * @throws MultiSiteJsonException
     */
    public function executeSql($params)
    {
        global $objDatabase, $_ARRAYLANG;
        
        //load the multisite language
        self::loadLanguageData();
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_MANAGER:
                case ComponentController::MODE_HYBRID:
                    if (empty($params['post']['query'])) {
                        throw new MultiSiteJsonException('JsonMultiSiteController (executeSql): failed to execute query, the sql query is empty');
                    }
                    $websiteServiceRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
                    //execute sql query on website
                    if (isset($params['post']['mode']) && $params['post']['mode'] == 'website') {
                        $website = $websiteServiceRepo->findOneBy(array('id' => $params['post']['id']));
                        if (empty($website)) {
                            throw new MultiSiteJsonException('JsonMultiSiteController (executeSql): failed to find the website.');
                        }
                        $params['post']['websiteName'] = $website->getFqdn()->getName();
                        $resp = self::executeCommandOnWebsite('executeSql', $params['post'], $website);
                        if ($resp->status == 'success') {
                            if ($resp->data->status) {
                                return array('status' => 'success', 'queryResult' => $resp->data, 'mode' => $params['post']['mode']);
                            }
                            return array('status' => 'error', 'message' => $resp->data->error);
                        }
                        return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_EXECUTED_QUERY_FAILED']);
                    }
                    //execute sql query on all websites running on service server 
                    if (isset($params['post']['mode']) && $params['post']['mode'] == 'service') {
                        $websites = $websiteServiceRepo->findBy(array('websiteServiceServerId' => $params['post']['id']));
                        if (empty($websites)) {
                            throw new MultiSiteJsonException('JsonMultiSiteController (executeSql): failed to find the service server.');
                        }
                        
                        if (!isset($_SESSION['MultiSite'])) {
                            $_SESSION['MultiSite'] = array();
                        }
                        
                        $randomKey = rand();
                        
                        if (!isset($_SESSION['MultiSite']['executeSql'])) {
                            $_SESSION['MultiSite']['executeSql'] = array();
                        }
                        
                        if (!isset($_SESSION['MultiSite']['executeSql'][$randomKey])) {
                            $_SESSION['MultiSite']['executeSql'][$randomKey] = array();
                        }
                        
                        $totalWebsiteCount = 0;
                        
                        foreach ($websites as $website) {
                            if ($website) {
                                $_SESSION['MultiSite']['executeSql'][$randomKey][$website->getId()] = $params['post']['query'];
                                $totalWebsiteCount++;
                            }
                        }
                        $_SESSION['MultiSite']['totalWebsites'] = $totalWebsiteCount;
                        return array('status' => 'success','randomKey' => $randomKey, 'mode' => $params['post']['mode']);
                    }
                    break;

                case ComponentController::MODE_WEBSITE:
                    if (isset($params['post']['query']) && !empty($params['post']['query'])) {
                        $resultSet = array();
                        $querys   = $this->extractSqlQueries($params['post']['query']);
                        foreach ($querys as $key => $query) {
                            switch(true) {
                                case preg_match('/^(SELECT|DESC|SHOW|EXPLAIN|CHECK|OPTIMIZE|REPAIR|ANALYZE|CACHE INDEX)/', (strtoupper($query))):
                                    $objResult = $objDatabase->GetAll($query);
                                    $resultSet[$key] = array(
                                        'query'       => $query,
                                        'resultValue' => (count($objResult) > 0) ? $objResult : $_ARRAYLANG['TXT_MULTISITE_NO_RECORD_FOUND']
                                    );
                                    break;
                                case preg_match('/^(UPDATE|DELETE|REPLACE|INSERT)/', (strtoupper($query))):
                                    $objResult = $objDatabase->Execute($query);
                                    $resultSet[$key] = array(
                                        'query'       => $query,
                                        'resultValue' => ''
                                    );
                                    if ($objResult) {
                                        switch (true) {
                                            case preg_match('/^INSERT/', (strtoupper($query))):
                                                $resultSet[$key]['resultValue'] = $objDatabase->Affected_Rows().sprintf($_ARRAYLANG['TXT_MULTISITE_NO_ROWS_AFFECTED'], 'inserted');
                                                break;
                                            case preg_match('/^DELETE/', (strtoupper($query))):
                                                $resultSet[$key]['resultValue'] = $objDatabase->Affected_Rows().sprintf($_ARRAYLANG['TXT_MULTISITE_NO_ROWS_AFFECTED'], 'deleted');
                                                break;
                                            default:
                                                $resultSet[$key]['resultValue'] = $objDatabase->Affected_Rows().sprintf($_ARRAYLANG['TXT_MULTISITE_NO_ROWS_AFFECTED'], 'affected');
                                                break;
                                        }
                                    }
                                    break;
                                default :
                                    $objResult = $objDatabase->Execute($query);
                                    $resultSet[$key] = array(
                                        'query'       => $query,
                                        'resultValue' => ''
                                    );
                                    break;
                            }
                            if ($objResult !== false) {
                                $resultSet[$key]['queryStatus'] = "okbox";
                            } else {
                                $resultSet[$key]['queryStatus'] = "alertbox";
                            }
                        }
                        return array('status' => true, 'resultSet'=>  contrexx_raw2xhtml($resultSet), 'websiteName' => contrexx_raw2xhtml($params['post']['websiteName']));
                    } else {
                        return array('status' => false, 'error' => $_ARRAYLANG['TXT_MULTISITE_QUERY_IS_EMPTY']);
                    } 
                    break;

                default:
                    break;
            }
        } catch (\Exception $e) {
            throw new MultiSiteJsonException('JsonMultiSiteController (executeSql): failed to execute query' . $e->getMessage());
        }
        return false;
    }

    /**
     * split sql string
     *
     * split the sql string in sql queries
     *
     * @access private
     * @param string $input
     */
    protected function extractSqlQueries($input)
    {
        $input = trim($input);
        $queryStartPos = 0;
        $stringDelimiter = '';
        $isString = false;
        $isComment = false;
        $query = '';
        $arrSqlQueries = array();
        for ($charNr = 0; $charNr < strlen($input); $charNr++) {
            switch (true) {
                case ($isComment): // check if the loop is in a comment
                    if ($input[$charNr] == "\r" || $input[$charNr] == "\n") {
                        $isComment = false;
                        $queryStartPos = $charNr + 1;
                    }
                    break;
                case $isString: // check if the loop is in a string
                    if ($input[$charNr] == $stringDelimiter && ($input[$charNr - 1] != "\\" || $input[$charNr - 2] == "\\")) {
                        $isString = false;
                    }
                    break;
                case ($input[$charNr] == "#" || (!empty($input[$charNr + 1]) && $input[$charNr] . $input[$charNr + 1] == "--")):
                    $isComment = true;
                    break;
                case ($input[$charNr] == '"' || $input[$charNr] == "'" || $input[$charNr] == "`"): // check if this is a string delimiter
                    $isString = true;
                    $stringDelimiter = $input[$charNr];
                    break;
                case ($input[$charNr] == ";" || ($input[$charNr] != ";" && $charNr == strlen($input) - 1)): // end of query reached
                    $charNr++;
                    $query = ltrim(substr($input, $queryStartPos, $charNr - $queryStartPos));
                    array_push($arrSqlQueries, $query);
                    $queryStartPos = $charNr;
                    break;
            }
        }
        return $arrSqlQueries;
    }
    
    /**
     * Execute the Queued Sql Query
     * 
     * @return array
     * @throws MultiSiteJsonException
     */
    public function executeQueryBySession($params) {
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_MANAGER:
                    $sqlQuery = array();
                    $randomKey = isset($params['post']['randomKey']) ? $params['post']['randomKey'] : '';
                    if (!empty($randomKey)) {
                        $websiteRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
                        if (is_object($_SESSION['MultiSite']['executeSql'][$randomKey])) {
                            $sqlQuery = $_SESSION['MultiSite']['executeSql'][$randomKey]->toArray();
                        }
                        if (!isset($_SESSION['MultiSite']) || empty($_SESSION['MultiSite']['executeSql'][$randomKey]) || empty($sqlQuery)) {
                            unset($_SESSION['MultiSite']['executeSql'][$randomKey]);
                            return array('status' => 'error', 'message' => 'There are no more websites in the queue.');
                        }
                        foreach ($_SESSION['MultiSite']['executeSql'][$randomKey] as $websiteId => $query) {
                            $website = $websiteRepo->findOneBy(array('id' => $websiteId));
                            $websiteName = $website->getFqdn()->getName();
                            $resp = self::executeCommandOnWebsite('executeSql', array('query' => $query, 'websiteName' => $websiteName), $website);
                            unset($_SESSION['MultiSite']['executeSql'][$randomKey][$websiteId]);
                            $websitesDone = $_SESSION['MultiSite']['totalWebsites'] - count($_SESSION['MultiSite']['executeSql'][$randomKey]);
                            return array('status' => 'success', 'queryResult' => $resp->data, 'totalWebsites' => $_SESSION['MultiSite']['totalWebsites'], 'websitesDone' => $websitesDone, 'websiteName' => $websiteName, 'randomKey' => $randomKey);
                        }
                    }
                    return array('status' => 'error', 'message' => 'Failed to execute the Query.');
                    break;    
            }
        } catch (\Exception $e) {
            throw new MultiSiteJsonException('JsonMultiSiteController (executeQueryBySession): failed to execute query' . $e->getMessage());
        }
    }
    
    /**
     * Stoping the query execution
     * 
     * @return result array
     */
    public function stopQueryExecution($params) {
        if (!empty($params['post']['sessionRandomKey'])) {
            if (isset($_SESSION['MultiSite']['executeSql'][$params['post']['sessionRandomKey']])) {
                unset($_SESSION['MultiSite']['executeSql'][$params['post']['sessionRandomKey']]);
            }
        }
        return array('status' => 'success', 'message' => 'The Query Execution was Stopped');
    }
    
    /**
     * Fetch the service plans of selected mail service server
     * 
     * @global array $_ARRAYLANG
     * @param array $params
     * 
     * @return array
     * @throws MultiSiteJsonException
     */
    public function getMailServicePlans($params) {
        global $_ARRAYLANG;

        self::loadLanguageData();
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_MANAGER:
                case ComponentController::MODE_HYBRID:
                    if (!isset($params['post']['mailServiceServerId']) && empty($params['post']['mailServiceServerId'])) {
                        \DBG::log('JsonMultiSiteController::getMailServicePlans() on ' . \Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite') . ' failed: Insufficient mapping information supplied: ' . var_export($params, true));
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_NO_MAIL_SERVER_FOUND']);
                    }
                    $mailServiceServerId   = contrexx_input2raw($params['post']['mailServiceServerId']);
                    $mailServiceServerRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\MailServiceServer');
                    $mailServiceServer     = $mailServiceServerRepo->findOneById($mailServiceServerId);
                    if (!$mailServiceServer) {
                        \DBG::log('JsonMultiSiteController::getMailServicePlans() mail service server is not found for supplied mail service id ' . $mailServiceServerId);
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_NO_MAIL_SERVER_FOUND']);
                    }
                    
                    $hostingController = ComponentController::getMailServerHostingController($mailServiceServer);
                    
                    $plans = $hostingController->getAvailableMailDistributionPlans();
                    return array(
                            'status'  => 'success',
                            'result'  => empty($plans) ? $_ARRAYLANG['TXT_MULTISITE_MAIL_SERVICE_PLAN_EMPTY'] : $plans,
                        );                        
                default:
                    break;
            }
            return array(
                        'status' =>'error',
                        'message' => $_ARRAYLANG['TXT_MULTISITE_FAILED_TO_FETCH_MAIL_SERVICE_PLAN']
                    );  
        } catch (\Exception $e) {
            \DBG::log('JsonMultiSiteController::getMailServicePlans() failed: to get service plans from mail service server: ' . $e->getMessage());
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_FAILED_TO_FETCH_MAIL_SERVICE_PLAN']);
        }
    }
    
    /**
     * Track Affiliate Id
     */
    public function trackAffiliateId($params)
    {
        $url = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
        if (!$url) {
            exit;
        }
        
        switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
            case ComponentController::MODE_MANAGER:
            case ComponentController::MODE_HYBRID:
                $affiliateIdQueryStringKey = \Cx\Core\Setting\Controller\Setting::getValue('affiliateIdQueryStringKey','MultiSite');
                $urlParams = \Cx\Core\Routing\Url::params2array($url);
                if (!array_key_exists($affiliateIdQueryStringKey, $urlParams)) {
                    exit;
                }
                $affiliateId = $urlParams[$affiliateIdQueryStringKey];
                if (ComponentController::isValidAffiliateId($affiliateId)) {
                    $cookieLifeTime = \Cx\Core\Setting\Controller\Setting::getValue('affiliateCookieLifetime','MultiSite');
                    setcookie('MultiSiteAffiliateId', $affiliateId, time() + (86400 * $cookieLifeTime), "/", '.' . \Cx\Core\Setting\Controller\Setting::getValue('multiSiteDomain','MultiSite'));
                }
            default:
                break;
        }

        exit;
    }

    /**
     * Fetching License information
     * 
     * @param type $params
     * @return type
     * @throws MultiSiteJsonException
     */
    public function getLicense($params) {
        global $_CORELANG, $_ARRAYLANG;
        
        self::loadLanguageData();
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_MANAGER:
                case ComponentController::MODE_HYBRID:
                    if (!isset($params['post']['websiteId'])) {
                        throw new MultiSiteJsonException('JsonMultiSiteController::getLicense() on '.\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite').' failed: Insufficient mapping information supplied: '.var_export($params, true));
                    }
                    //find User's Website
                    $webRepo   = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
                    $website   = $webRepo->findOneById($params['post']['websiteId']);
                    $params    = array(
                        'websiteId'   => $params['post']['websiteId'],
                        'activeLanguages'   => \FWLanguage::getActiveFrontendLanguages()
                    );
                    $resp = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnWebsite('getLicense', $params, $website);
                    if ($resp->status == 'success' && $resp->data->status == 'success') {
                        return $resp->data;
                    }
                    return array("status" => "error", "message" => sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_FETCH_LICENSE_FAILED'], $website->getFqdn()->getName()));
                    break;

                case ComponentController::MODE_WEBSITE:
                    $license = $this->cx->getLicense();
                    if (!$license) {
                        throw new MultiSiteJsonException('JsonMultiSiteController::getLicense(): on '.\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite').' $license was not set properly');
                    }
                    $dashboardMessages = array();
                    $licenseMessage = array();
                    $licenseGrayzoneMessages = array();
                    $result = array();
                    foreach ($params['post']['activeLanguages'] as $languages) {
                        $lang_id = $languages['id'];
                        $lang_name = $languages['name'];
                        $licensemessageObj = $license->getMessage(false, \FWLanguage::getLanguageCodeById($lang_id), $_CORELANG);
                        $dashboardMessagesObj = $license->getMessage(true, \FWLanguage::getLanguageCodeById($lang_id), $_CORELANG);
                        $licenseGrayzoneMessagesObj = $license->getGrayzoneMessage(\FWLanguage::getLanguageCodeById($lang_id), $_CORELANG);
                        $licenseMessage[] = ($licensemessageObj->getLangCode() == \FWLanguage::getLanguageCodeById($lang_id)) ? array('lang_id' => $lang_id, 'lang_name' => $lang_name,'message'=> $licensemessageObj->getText()) : array('lang_id' => $lang_id, 'lang_name' => $lang_name, 'message' =>'');
                        $dashboardMessages[] = ($dashboardMessagesObj->getLangCode() == \FWLanguage::getLanguageCodeById($lang_id)) ? array('lang_id' => $lang_id, 'lang_name' => $lang_name, 'message' => $dashboardMessagesObj->getText()) : array('lang_id' => $lang_id, 'lang_name' => $lang_name, 'message' =>'');
                        $licenseGrayzoneMessages[] = ($licenseGrayzoneMessagesObj->getLangCode() == \FWLanguage::getLanguageCodeById($lang_id)) ? array('lang_id' => $lang_id, 'lang_name' => $lang_name, 'message' => $licenseGrayzoneMessagesObj->getText()) : array('lang_id' => $lang_id, 'lang_name' => $lang_name, 'message' =>'');
                    }
                    \Cx\Core\Setting\Controller\Setting::init('Config', '', 'Yaml');
                    $configCoreSetting = \Cx\Core\Setting\Controller\Setting::getArray('Config', 'core');
                    if ($configCoreSetting) {
                        $result['installationId'] = array("type" => $configCoreSetting['installationId']['type'], "values" => $configCoreSetting['installationId']['values'], "content" => $configCoreSetting['installationId']['value']);
                    }
                    
                    $licenseConfig = array('license', 'release');
                    foreach ($licenseConfig as $value) {
                        foreach (\Cx\Core\Setting\Controller\Setting::getArray('Config', $value) as $key => $value) {
                            $value['type'] = ($value['type'] == 'text') ? 'textarea' : $value['type'];
                            if (in_array($key, array('licensePartner', 'licenseCustomer'))) {
                                switch ($key) {
                                    case 'licensePartner':
                                        $result['licensePartnerTitle'] = array("type" => $value['type'], "values" => $value['values'], "content" => $license->getPartner()->getTitle());
                                        $result['licensePartnerLastname'] = array("type" => $value['type'], "values" => $value['values'], "content" => $license->getPartner()->getLastname());
                                        $result['licensePartnerFirstname'] = array("type" => $value['type'], "values" => $value['values'], "content" => $license->getPartner()->getFirstname());
                                        $result['licensePartnerCompanyname'] = array("type" => $value['type'], "values" => $value['values'], "content" => $license->getPartner()->getCompanyName());
                                        $result['licensePartnerAddress'] = array("type" => $value['type'], "values" => $value['values'], "content" => $license->getPartner()->getAddress());
                                        $result['licensePartnerZip'] = array("type" => $value['type'], "values" => $value['values'], "content" => $license->getPartner()->getZip());
                                        $result['licensePartnerCity'] = array("type" => $value['type'], "values" => $value['values'], "content" => $license->getPartner()->getCity());
                                        $result['licensePartnerCountry'] = array("type" => $value['type'], "values" => $value['values'], "content" => $license->getPartner()->getCountry());
                                        $result['licensePartnerPhone'] = array("type" => $value['type'], "values" => $value['values'], "content" => $license->getPartner()->getPhone());
                                        $result['licensePartnerUrl'] = array("type" => $value['type'], "values" => $value['values'], "content" => $license->getPartner()->getUrl());
                                        $result['licensePartnerMail'] = array("type" => $value['type'], "values" => $value['values'], "content" => $license->getPartner()->getMail());
                                        break;
                                    case 'licenseCustomer':
                                        $result['licenseCustomerTitle'] = array("type" => $value['type'], "values" => $value['values'], "content" => $license->getCustomer()->getTitle());
                                        $result['licenseCustomerLastname'] = array("type" => $value['type'], "values" => $value['values'], "content" => $license->getCustomer()->getLastname());
                                        $result['licenseCustomerFirstname'] = array("type" => $value['type'], "values" => $value['values'], "content" => $license->getCustomer()->getFirstname());
                                        $result['licenseCustomerCompanyname'] = array("type" => $value['type'], "values" => $value['values'], "content" => $license->getCustomer()->getCompanyName());
                                        $result['licenseCustomerAddress'] = array("type" => $value['type'], "values" => $value['values'], "content" => $license->getCustomer()->getAddress());
                                        $result['licenseCustomerZip'] = array("type" => $value['type'], "values" => $value['values'], "content" => $license->getCustomer()->getZip());
                                        $result['licenseCustomerCity'] = array("type" => $value['type'], "values" => $value['values'], "content" => $license->getCustomer()->getCity());
                                        $result['licenseCustomerCountry'] = array("type" => $value['type'], "values" => $value['values'], "content" => $license->getCustomer()->getCountry());
                                        $result['licenseCustomerPhone'] = array("type" => $value['type'], "values" => $value['values'], "content" => $license->getCustomer()->getPhone());
                                        $result['licenseCustomerUrl'] = array("type" => $value['type'], "values" => $value['values'], "content" => $license->getCustomer()->getUrl());
                                        $result['licenseCustomerMail'] = array("type" => $value['type'], "values" => $value['values'], "content" => $license->getCustomer()->getMail());
                                        break;
                                    default: 
                                        break;
                                }
                            } else {
                                $result[$key] = array("type" => $value['type'], "values" => explode(',', $value['values']));
                            }
                            switch ($key) {
                                case 'licenseKey':
                                case 'licenseState':
                                case 'upgradeUrl':
                                case 'isUpgradable':
                                case 'licenseGrayzoneTime':
                                case 'licenseLockTime':
                                case 'licenseUpdateInterval':
                                case 'licenseFailedUpdate':
                                case 'coreCmsEdition':
                                case 'coreCmsVersion':
                                case 'coreCmsCodeName':
                                case 'coreCmsStatus':
                                case 'coreCmsName':
                                case 'licenseSuccessfulUpdate':
                                    $result[$key]['content'] = $value['value'];
                                    break;
                                case 'licenseCreatedAt':
                                case 'coreCmsReleaseDate':
                                    $result[$key]['content'] = date('d.m.Y', $value['value']);
                                    break;
                                case 'licenseValidTo':
                                    $result[$key]['content'] = date('d.m.Y h:i:s', $value['value']);;
                                    break;
                                case 'licenseMessage':
                                    $result[$key]['content'] = $licenseMessage;
                                    break;
                                case 'licenseDomains':
                                    $result[$key]['content'] = implode(', ', $license->getRegisteredDomains());
                                    break;
                                case 'availableComponents':
                                    $result[$key]['content'] = serialize($license->getLicensedComponentsWithAdditionalData());
                                    break;
                                case 'dashboardMessages':
                                    $result[$key]['content'] = $dashboardMessages;
                                    break;
                                case 'licenseGrayzoneMessages':
                                    $result[$key]['content'] = $licenseGrayzoneMessages;
                                    break;
                                default:
                                    break;
                            }
                        }
                    }
                    if ($result) {
                        return array(
                            'status' => "success",
                            'result' => contrexx_raw2xhtml($result),
                        );
                    }
                    break;

                default:
                    break;
            }
        } catch (\Exception $e) {
            throw new MultiSiteJsonException('JsonMultiSiteController::getLicense() failed: to get License Information: ' . $e->getMessage());
        }
    }

    /**
     * Remove the user
     * 
     * @param  array $params
     * @return boolean true or false
     * @throws MultiSiteJsonException
     */
    public function removeUser($params) {
        global $_ARRAYLANG;
        
        self::loadLanguageData();
        
        if (empty($params['post']) || empty($params['post']['userId'])) {
            \DBG::log($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_UNKOWN_USER_REQUEST']);
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_UNKOWN_USER_REQUEST']);
        }
        
        switch (\Cx\Core\Setting\Controller\Setting::getValue('mode', 'MultiSite')) {
            case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_HYBRID:
            case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_SERVICE:
                $websiteRepository = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
                $website = $websiteRepository->findWebsitesByCriteria(array('user.id' => $params['post']['userId']));
                if ($website) {
                    break;
                }
            case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_WEBSITE:
                $objUser    = \FWUser::getFWUserObject()->objUser->getUser($params['post']['userId']);
                $deleteUser = $objUser ? $objUser->delete() : true;
                if ($deleteUser) {
                    return array(
                        'status' => 'success',
                        'log'    => \DBG::getMemoryLogs(),
                    );
                }
                break;
            default:
                break;
        }
        return array(
            'status' => 'error',
            'log'    => \DBG::getMemoryLogs(),
        );
    }
    
    /**
     * Remote Login to website
     * 
     * @param type $params websiteId
     * 
     * @return array 
     */
    public function remoteLogin($params)
    {
        global $_ARRAYLANG;
        self::loadLanguageData();
        
        $loginType = ($params['post']['loginType'] == 'customerpanel') ? 'customerpanel' : 'website';
        if (!empty($params['post']['websiteId'])) {
           $websiteId = $params['post']['websiteId'];
        }
        if (!empty($params['post']['ownerId'])) {
            $ownerId = $params['post']['ownerId'];
        }
        if (
            (
                $loginType == 'website' &&
                empty($websiteId)
            ) || (
                $loginType == 'customerpanel' &&
                empty($ownerId) &&
                empty($websiteId)
            )
        ) {
            return false;
        }
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_MANAGER:
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_HYBRID:
                    if ($websiteId) {
                        $websiteRepository = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
                        $website   = $websiteRepository->findOneBy(array('id' => $websiteId));
                        if (!$website) {
                            return array('status' => 'error', 'message' => sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_REMOTE_LOGIN_FAILED'], $loginType));
                        }
                        $ownerId = $website->getOwner()->getId();
                    }
                                    
                    if ($loginType == 'customerpanel') {
                        $response = self::executeCommandOnManager('generateAuthToken', array('ownerId' => $ownerId));
                        if ($response->status == 'error' || $response->data->status == 'error') {
                            return array('status' => 'error', 'message' => sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_REMOTE_LOGIN_FAILED'], $loginType));
                        }
                        $successMessage = $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_REMOTE_LOGIN_CUSTOMERPANELDOMAIN_SUCCESS'];
                        $websiteLoginUrl = \Cx\Core\Routing\Url::fromMagic(ComponentController::getApiProtocol() . \Cx\Core\Setting\Controller\Setting::getValue('customerPanelDomain','MultiSite'). '/index.php?section=Login&user-id='.$response->data->userId.'&auth-token='.$response->data->authToken);
                    } else {
                        list($websiteOwnerUserId, $authToken) = $website->generateAuthToken();
                        
                        $websiteName     = $website->getBaseDn()->getName();
                        $websiteLoginUrl = \Cx\Core\Routing\Url::fromMagic(ComponentController::getApiProtocol() . $websiteName . $this->cx->getWebsiteBackendPath() . '/?user-id='.$websiteOwnerUserId.'&auth-token='.$authToken);
                        $successMessage  = sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_REMOTE_LOGIN_SUCCESS'], $websiteName);
                    }
                    return array('status' => 'success', 'message' => $successMessage,'webSiteLoginUrl' => $websiteLoginUrl->toString());
                    break;
                default:
                    break;
            }
        } catch (\Exception $e) {
            throw new MultiSiteJsonException('JsonMultiSiteController::remoteLogin() failed: to get remote website Login Link: ' . $e->getMessage());
        }
    }
    
    /**
     * Edit/Update License data
     * 
     * @param array $params Post values of License
     * @return array
     * @throws MultiSiteJsonException
     */
    public function editLicense($params)
    {
        global $_ARRAYLANG;
        
        self::loadLanguageData();
        $websiteId = $params['post']['websiteId'];
        $licenseOption = $params['post']['licenseLabel'];
        $licenseValue = $params['post']['licenseValue'];
        if (!$websiteId) {
            throw new MultiSiteJsonException('Invalid websiteId for the command JsonMultiSiteController::editLicense.');
        }
        try{
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_MANAGER:
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_HYBRID:
                    $dateFormatArr = array("coreCmsReleaseDate", "licenseValidTo", "licenseCreatedAt");
                    $paramsArray = array(
                        $licenseOption => in_array($licenseOption, $dateFormatArr) ? strtotime($licenseValue) : $licenseValue,
                        'websiteId' => $websiteId
                    );
                    $webRepo     = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
                    $website     = $webRepo->findOneById($websiteId);
                    $resp        = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnWebsite('setLicense', $paramsArray, $website);
                    if (($resp->status == 'success') && ($resp->data->status == 'success')) {
                        return array('status' => 'success', 'data' => $licenseValue, 'message' => sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_LICENSE_UPDATE_SUCCESS'], $licenseOption));
                    }
                    return array('status' => 'error','message' => sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_LICENSE_UPDATE_FAILED'], $licenseOption));
                    break;
                default:
                    break;
            }
        } catch (\Exception $e) {
            throw new MultiSiteJsonException('JsonMultiSiteController::editLicense() failed: to Update License Information of the This Website: ' . $e->getMessage());
        }
    }
    
    /**
     * sending account activation email to the user.
     * 
     * @return type
     * @throws MultiSiteJsonException
     */
    public function sendAccountActivation($params) {
        global $_ARRAYLANG;
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_MANAGER:
                    if (empty($params['post']['ownerEmail']) || empty($params['post']['websiteName'])) {
                        throw new MultiSiteJsonException('JsonMultiSiteController::sendAccountActivation() failed: Insufficient arguments supplied: ' . var_export($params, true));
                    }
                    $objOwner = \FWUser::getFWUserObject()->objUser->getUser(array('email' => $params['post']['ownerEmail'])); 
                    $websiteRepository = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
                    $websiteObj = $websiteRepository->findWebsitesByCriteria(array('user.id' => $objOwner->getId(), 'website.name' => $params['post']['websiteName']));
                    $website    = current($websiteObj);
                    if (!$website) {
                        throw new MultiSiteJsonException('JsonMultiSiteController::sendAccountActivation() failed: Unknown Website-User-Id: ' . $objOwner->getId());
                    }
                    
                    $websiteVerificationUrl = \FWUser::getVerificationLink(true, $objOwner, $website->getBaseDn()->getName());
                    // write mail
                    \Cx\Core\MailTemplate\Controller\MailTemplate::init('MultiSite');
                    \DBG::msg('Website: send Account Activation Email > ADMIN');
                    $info = array(
                        'section'      => 'Multisite',
                        'key'          => 'accountActivation',
                        'to'           => $website->getOwner()->getEmail(),
                        'substitution' => array(
                            'WEBSITE_ACCOUNT_VERIFICATION_URL' => $websiteVerificationUrl,
                            'WEBSITE_ACCOUNT_VERIFICATION_DUE_DATE' => date(ASCMS_DATE_FORMAT_DATE, $website->getOwner()->getRestoreKeyTime()),
                        )
                    );
                    if (!\Cx\Core\MailTemplate\Controller\MailTemplate::send($info)) {
                        throw new MultiSiteJsonException(__METHOD__ . ': Unable to send account activation e-mail to user');
                    }
                    \DBG::msg('Website: Sent Account Activation Email > SUCCESS');
                    return array('status' => 'success', 'date' => date(ASCMS_DATE_FORMAT_DATE, $website->getOwner()->getRestoreKeyTime()));
                    break;
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_SERVICE:
                    if (empty($params['post']['ownerEmail']) || empty($params['post']['websiteName'])) {
                        throw new MultiSiteJsonException('JsonMultiSiteController::sendAccountActivation() failed: Insufficient arguments supplied: ' . var_export($params, true));
                    }
                    
                    $response = self::executeCommandOnManager('sendAccountActivation', array('ownerEmail' => $params['post']['ownerEmail'], 'websiteName' => $params['post']['websiteName']));
                    if ($response->status == 'success' && $response->data->status == 'success') {
                        return array('status' => 'success', 'date' => $response->data->date);
                    }
                    break;
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_WEBSITE:
                    $websiteName = \Cx\Core\Setting\Controller\Setting::getValue('websiteName','MultiSite');
                    $ownerEmail = \FWUser::getFWUserObject()->objUser->getUser(\Cx\Core\Setting\Controller\Setting::getValue('websiteUserId','MultiSite'))->getEmail();
                    if (!empty($ownerEmail) && !empty($websiteName)) {
                        $resp = self::executeCommandOnMyServiceServer('sendAccountActivation', array('ownerEmail' => $ownerEmail, 'websiteName' => $websiteName));
                        self::loadLanguageData();
                        if ($resp->status == 'success' && $resp->data->status == 'success') {
                           return array('status' => $resp->data->status, 'message' => sprintf($_ARRAYLANG['TXT_MULTISITE_ACCOUNT_ACTIVATION_MAIL_RESENT'], '<span class="highlight">'.$ownerEmail.'</span>', '<span class="highlight">'.$resp->data->date.'</span>'));
                        }
                    }
                    break;
                default :
                    break;
            }
            return array('status' => 'error', 'message' => 'JsonMultiSiteController::sendAccountActivation() failed: to Send Account Activation Mail of this Website.');
        } catch (\Exception $e) {
            throw new MultiSiteJsonException('JsonMultiSiteController::sendAccountActivation() failed: to Send Account Activation Mail of this Website: ' . $e->getMessage());
        }
    }
    
    /**
     * Get the Payrexx Url
     * 
     * @param array $params
     * 
     * @return array
     * @throws MultiSiteJsonException
     */
    public function getPayrexxUrl($params) {
        if (!isset($params['post']['product_id'])) {
            throw new MultiSiteJsonException('JsonMultiSiteController::getPayrexxUrl() failed: Insufficient mapping information supplied: ' . var_export($params, true));
        }

        try {
            $productRepository = \Env::get('em')->getRepository('Cx\Modules\Pim\Model\Entity\Product');
            $product = $productRepository->findOneBy(array('id' => $params['post']['product_id']));
            
            if (\FWValidator::isEmpty($product)) {
                return;
            }
            
            $objUser = null;
            if (ComponentController::isUserLoggedIn()) {
                $objUser = \FWUser::getFWUserObject()->objUser;
            }
            
            $productName = $product->getName();
			$renewalOption = isset($params['post']['renewalOption']) ? $params['post']['renewalOption'] : 'monthly';
            list($renewalUnit, $renewalQuantifier) = self::getProductRenewalUnitAndQuantifier($renewalOption);
            $currency = ComponentController::getUserCurrency($objUser ? $objUser->getCrmUserId() : 0);
            $productPrice = $product->getPaymentAmount($renewalUnit, $renewalQuantifier, $currency, true);
            $referenceId = '';
            $purpose     = '';
            if (isset($params['post']['multisite_address']) && !\FWValidator::isEmpty($params['post']['multisite_address'])) {
                $referenceId = "|{$product->getId()}|name|{$params['post']['multisite_address']}|";
                $purpose     = $productName . ' - ' . $params['post']['multisite_address'];
            } elseif ($objUser) {
                $userId      = $objUser->getId();
                $referenceId = "|{$product->getId()}|owner|$userId|";
                $purpose     = $productName;
            } else {
                throw new MultiSiteJsonException('JsonMultiSiteController::getPayrexxUrl() failed: Insufficient mapping information supplied: ' . var_export($params, true));
            }
            
            $instanceName  = \Cx\Core\Setting\Controller\Setting::getValue('payrexxAccount','MultiSite');
            $apiSecret     = \Cx\Core\Setting\Controller\Setting::getValue('payrexxApiSecret','MultiSite');

            $payrexx = new \Payrexx\Payrexx($instanceName, $apiSecret);
            
            $invoice = new \Payrexx\Models\Request\Invoice();
            $invoice->setReferenceId($referenceId);
            $invoice->setTitle($purpose);
            $invoice->setDescription('');
            $invoice->setPurpose($purpose);
            $invoice->setPsp(5);
            $invoice->setAmount($productPrice * 100);
            $invoice->setCurrency(\Payrexx\Models\Request\Invoice::CURRENCY_CHF);
            
            $invoice->addField('email', true, (isset($params['post']['multisite_email_address']) ? $params['post']['multisite_email_address'] : ($objUser ? $objUser->getEmail() : '')));
            
            $invoice->setSubscriptionState(true);

            list($subscriptionPeriod, $subscriptionInterval, $cancellationInterval)
                        = self::getSubscriptionPeriodAndIntervalsByProduct($product, $renewalUnit, $renewalQuantifier);
            
            // set payment interval
            $invoice->setSubscriptionInterval(\DateTimeTools::getDateIntervalAsString($subscriptionInterval));

            // set subscription period
            $invoice->setSubscriptionPeriod(\DateTimeTools::getDateIntervalAsString($subscriptionPeriod));

            // set cancellation period
            $invoice->setSubscriptionCancellationInterval(\DateTimeTools::getDateIntervalAsString($cancellationInterval));

            $response = $payrexx->create($invoice);            
            if ($response instanceof \Payrexx\Models\Response\Invoice && !\FWValidator::isEmpty($response->getLink())) {
                return array('status' => 'success', 'link' => $response->getLink());
            }
        } catch (\Payrexx\PayrexxException $e) {
            throw new MultiSiteJsonException('JsonMultiSiteController::getPayrexxUrl() failed: to get the payrexx url: ' . $e->getMessage());
        }
    }

    /**
     * Fetch delete and Update Multisite Configuration of the selected Website
     * 
     * @param array $params websiteConfigArray
     * 
     * @return array
     * @throws MultiSiteJsonException
     */
    public function modifyMultisiteConfig($params) 
    {
        global $_ARRAYLANG;
        self::loadLanguageData();
        
        $websiteId = isset($params['post']['websiteId']) ? $params['post']['websiteId'] : '';
        $configGroup = isset($params['post']['configGroup']) ? $params['post']['configGroup'] : '';
        $configValue = isset($params['post']['configValue']) ? $params['post']['configValue'] : '';
        $configValues = isset($params['post']['configValues']) ? $params['post']['configValues'] : '';
        $configName = isset($params['post']['configOption']) ? $params['post']['configOption'] : '';
        $configType = isset($params['post']['configType']) ? $params['post']['configType'] : '';
        $operation  = isset($params['post']['operation']) ? $params['post']['operation'] : 'fetch';
        if (!$websiteId) {
            throw new MultiSiteJsonException('Invalid websiteId for the command JsonMultiSiteController::modifyMultisiteConfig.');
        }
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_MANAGER:
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_HYBRID:
                    $webRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
                    $website = $webRepo->findOneById($websiteId);
                    $inputTypes = array(
                                    \Cx\Core\Setting\Controller\Setting::TYPE_TEXT,
                                    \Cx\Core\Setting\Controller\Setting::TYPE_TEXTAREA,
                                    \Cx\Core\Setting\Controller\Setting::TYPE_PASSWORD,
                                    \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN,
                                    \Cx\Core\Setting\Controller\Setting::TYPE_RADIO
                                );
                    $params = ($operation !="fetch") ? array(
                                                            'websiteId' => $websiteId,
                                                            'configGroup' => $configGroup,
                                                            'configOption' => $configName,
                                                            'configValue' => $configValue,
                                                            'configType' => $configType,
                                                            'configValues' => $configValues,
                                                            'operation' => $operation
                                                       ) : array('websiteId' => $websiteId);
                    $resp = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnWebsite('modifyMultisiteConfig', $params, $website);
                    if ($resp->status == 'success' && $resp->data->status) {
                        switch ($resp->data->multisiteConfig) {
                            case 'add':
                            case 'edit':
                            case 'delete':
                                return array('status' => 'success', 'message' => $resp->data->message);
                                break;
                            case 'fetch':
                            default:
                                return array('status' => 'success', 'message' => sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_CONFIG_FETCH_SUCCESSFUL'], $website->getFqdn()->getName()), 'result' => $resp->data->result, 'inputTypes' => $inputTypes);
                                break;
                        }
                    } else {
                        switch ($resp->data->multisiteConfig) {
                            case 'add':
                            case 'edit':
                            case 'delete':
                                return array('status' => 'error', 'message' => $resp->data->message);
                                break;
                            case 'fetch':
                            default:
                                return array('status' => 'error', 'message' => sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_CONFIG_FETCH_FAILED'], $operation, $website->getFqdn()->getName()));
                                break;
                        }
                    }
                    break;
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_WEBSITE:
                    \Cx\Core\Setting\Controller\Setting::init('MultiSite', '', 'FileSystem');
                    switch ($operation) {
                        case 'add':
                            if (!empty($configName) && !empty($configType)) {
                                if (($configType == \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN || $configType == \Cx\Core\Setting\Controller\Setting::TYPE_RADIO)
                                        && empty($configValues)) {
                                    return array('status' => "success", "success"=> false, 'multisiteConfig' => $operation, 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADD_CONFIG_FAILED']);
                                }
                                
                                if (!\Cx\Core\Setting\Controller\Setting::isDefined($configName) 
                                        && \Cx\Core\Setting\Controller\Setting::add($configName, $configValue, 1, $configType, $configValues, $configGroup)) {
                                    return array('status' => "success", "success"=> true, 'multisiteConfig' => $operation, 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADD_CONFIG_SUCCESSFUL'].$configName);
                                }
                                
                                return array('status' => "success", "success"=> false, 'multisiteConfig' => $operation, 'message' => sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADD_CONFIG_EXISTS'], $configName));
                            }
                            return array('status' => "success", "success"=> false, 'multisiteConfig' => $operation, 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADD_CONFIG_FAILED']);
                            break;
                        case 'edit':
                            if (!empty($configName)) {
                                \Cx\Core\Setting\Controller\Setting::set($configName, $configValue);
                                if (\Cx\Core\Setting\Controller\Setting::update($configName)) {
                                    return array('status' => 'success', "success"=> true, 'multisiteConfig' => $operation, 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_CONFIG_UPDATE_SUCCESSFUL'] . $configName);
                                }
                            }
                            return array('status' => 'success', "success"=> false, 'multisiteConfig' => $operation, 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_CONFIG_UPDATE_FAILED'] . $configName);
                            break;
                        case 'delete':
                            if (!empty($configName)) {
                                if (\Cx\Core\Setting\Controller\Setting::delete($configName, $configGroup)) {
                                    return array('status' => 'success', "success"=> true, 'multisiteConfig' => $operation, 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_CONFIG_DELETE_SUCCESSFUL'] . $configName);
                                }  
                            }
                            return array('status' => 'success', "success"=> false, 'multisiteConfig' => $operation, 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_CONFIG_DELETE_FAILED'] . $configName);
                            break;
                        case 'fetch':
                        default:
                            \Cx\Core\Setting\Controller\Setting::init('MultiSite', '', 'FileSystem');
                            $multisiteConfigArray = \Cx\Core\Setting\Controller\Setting::getArray('MultiSite');
                            if ($multisiteConfigArray) {
                                foreach ($multisiteConfigArray as &$configArray) {
                                    $type = $configArray['type'];

                                    switch ($type) {
                                        case \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN:
                                            break;

                                        default:
                                            continue;
                                            break;
                                    }

                                    $matches   = null;
                                    if (!preg_match('/^\{src:([a-z0-9_\\\:]+)\(\)\}$/i', $configArray['values'], $matches)) {
                                        continue;
                                    }
                                    $configArray['values'] = call_user_func($matches[1]);

                                    if ($configArray['name'] != 'website_server') {
                                        continue;
                                    }

                                    $options = explode(',', $configArray['values']);
                                    $options = preg_replace('/^[^:]+:(.*)$/', '\1:\1', $options);
                                    $configArray['values'] = implode(',', $options);
                                }
                                return array('status' => 'success', "success"=> true,'result' => $multisiteConfigArray, 'multisiteConfig' => $operation);
                            }
                            break;
                    }
                    break;
                default:
                    break;
            }
        } catch (\Exception $ex) {
            throw new MultiSiteJsonException('JsonMultiSiteController::modifyMultisiteConfig() failed: to Fetch the Multisite Configuration of the This Website: ' . $e->getMessage());
        }
    }

    /**
     * To set the values to entity.
     * 
     * @param array $param websiteTemplate array
     * 
     * @throws MultiSiteJsonException
     */
    public function push($param) {
	try {
            if (empty($param['post']['dataType']) && empty($param['post']['data'])) {
                    return;
            }
            $objDataSet = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($param['post']['data']);
            $objEntityInterface = new \Cx\Core_Modules\Listing\Model\Entity\EntityInterface();
            $objEntityInterface->setEntityClass($param['post']['dataType']);

            $entity = current($objDataSet->export($objEntityInterface));

            \Env::get('em')->persist($entity);
            $entityObject = \Env::get('em')->getClassMetadata(get_class($entity));  
            $entityObject->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
            \Env::get('em')->flush();
	} catch (\Exception $e) {
            throw new MultiSiteJsonException('JsonMultiSiteController::push() failed: To add / update the repository'. $e->getMessage());
	}
}

    /**
     * To take websiteBackup to specified location in service server
     * 
     * @param array $params website data
     * 
     * @return array
     * @throws MultiSiteJsonException
     */
    public function websiteBackup($params) 
    {
        global $_ARRAYLANG;
        self::loadLanguageData();
        
        if (empty($params) || empty($params['post'])) {
            \DBG::log(__METHOD__.' failed!: '.$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_PARAMS']);
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_FAILED']);
        }
        
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_MANAGER:
                    $serviceServerId = isset($params['post']['serviceServerId'])
                                       ? contrexx_input2int($params['post']['serviceServerId'])
                                       : 0;
                    $websiteServiceServer = ComponentController::getServiceServerByCriteria(array('id' => $serviceServerId));
                    if (!$websiteServiceServer) {
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_SERVICE_SERVER']);
                    }
                    $isWebsiteBackupAllowed = $this->getSystemComponentController()->verifyWebsiteBackupLimit($params['post']['websiteId'], $websiteServiceServer);
                    if (!$isWebsiteBackupAllowed) {
                        $errorMsg = $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_LIMIT_ERROR'];
                        if (isset($params['post']['isCopy']) && $params['post']['isCopy']) {
                            $errorMsg = $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_COPY_LIMIT_ERROR'];
                        }
                        return array(
                            'status'  => 'error',
                            'message' => $errorMsg,
                        );
                    }
                    
                    $resp = self::executeCommandOnServiceServer('websiteBackup', $params['post'], $websiteServiceServer);
                    if (!$resp || $resp->status == 'error' || $resp->data->status == 'error') {
                        if (isset($resp->data)) {
                            if (isset($resp->data->log)) {
                                \DBG::appendLogs(array_map(function($logEntry) use ($websiteServiceServer) {return '('.$websiteServiceServer->gethostname().') '.$logEntry;}, $resp->data->log));
                            }
                            if (isset($resp->data->message)) {
                                \DBG::msg($websiteServiceServer->gethostname() . ': ' . $resp->data->message);
                            }
                        } else {
                            if (isset($resp->log)) {
                                \DBG::appendLogs(array_map(function($logEntry) use ($websiteServiceServer) {return '('.$websiteServiceServer->gethostname().') '.$logEntry;}, $resp->log));
                            }
                            if (isset($resp->message)) {
                                \DBG::msg($websiteServiceServer->gethostname() . ': ' . $resp->message);
                            }
                        }
                        continue;
                    }
                    return $resp->data ? $resp->data : $resp;
                    break;
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_HYBRID:
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_SERVICE:
                    $websiteId         = isset($params['post']['websiteId']) 
                                         ? contrexx_input2int($params['post']['websiteId']) 
                                         : 0;
                    $backupLocation    = !empty($params['post']['backupLocation']) 
                                         ? contrexx_input2raw($params['post']['backupLocation']) 
                                         : \Cx\Core\Setting\Controller\Setting::getValue('websiteBackupLocation', 'MultiSite');
                    $websiteRepository = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
                    $websites          = !empty($websiteId) 
                                         ? $websiteRepository->findBy(array('id' => $websiteId))
                                         : $websiteRepository->findAll();

                    //change the backup location path to absolute location path
                    \Cx\Lib\FileSystem\FileSystem::path_absolute_to_os_root($backupLocation);

                    if (empty($websites)) {
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_NO_WEBSITE_FOUND']);
                    }

                    $archives = array();
                    foreach ($websites as $website) {
                        $archives[] = $this->websiteDataBackup($website, $backupLocation);
                    }

                    return array(
                        'status'  => 'success', 
                        'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_SUCCESS'],
                        'log'     => \DBG::getMemoryLogs(),
                        'backups' => $archives,
                    );
                    break;
                default:
                    break;
            }
        } catch (\Throwable $e) {
            \DBG::log(__METHOD__.' failed!: '.$e->getMessage());
            return array(
                'status'  => 'error',
                'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_FAILED'],
                'log'     => \DBG::getMemoryLogs(),
            );
        }
    }

    /**
     * Website Data Repository backup
     * 
     * @param \Cx\Core_Modules\MultiSite\Model\Entity\Website $website        websiteObject
     * @param string                                          $backupLocation websiteBackupLocation
     * 
     * @return string Base name of backup file
     * @throws MultiSiteJsonException
     */
    protected function websiteDataBackup(\Cx\Core_Modules\MultiSite\Model\Entity\Website $website, $backupLocation)
    {
        $websiteName       = $website->getName();
        $websitePath       = \Cx\Core\Setting\Controller\Setting::getValue('websitePath', 'MultiSite').'/'.$websiteName;
        $websiteBackupPath = $backupLocation . '/' . $website->getName();
        
        try {
            if (!\Cx\Lib\FileSystem\FileSystem::exists($websitePath)) {
                throw new MultiSiteJsonException('Failed to backup the ' .$websiteName. '. Website not exists in the repository!.');
            }

            if (   !\Cx\Lib\FileSystem\FileSystem::exists($backupLocation) 
                && !\Cx\Lib\FileSystem\FileSystem::make_folder($backupLocation)
            ) {
                throw new MultiSiteJsonException('Failed to create the backup location');
            }
            
            \DBG::log('JsonMultiSiteController: Website Info Backup...');
            $this->websiteInfoBackup($website, $websiteBackupPath);
            
            \DBG::log('JsonMultiSiteController: Website Database Backup...');
            $this->websiteDatabaseBackup($websiteBackupPath, $websitePath);
            
            \DBG::log('JsonMultiSiteController: Website Data Repository Backup...');
            $this->websiteRepositoryBackup($websiteBackupPath, $websitePath);
            
            \DBG::log('JsonMultiSiteController: Create Website Zip Archive...');
            return $this->createWebsiteArchive($websiteBackupPath);
        } catch (\Exception $e) {
            throw new MultiSiteJsonException('JsonMultiSiteController::websiteDataBackup() : failed !. : '. $e->getMessage());
        } 
    }
        
    /**
     * Create Website Zip Archive for the given Location
     * 
     * @param string $websiteBackupPath websiteBackupLocation
     * 
     * @return string Base name of backup file
     * @throws MultiSiteJsonException
     */
    protected function createWebsiteArchive($websiteBackupPath) 
    {
        // increase memory_limit to be able to handle huge websites
        ini_set('memory_limit', '1024M');
        $archiveFilePath         = $websiteBackupPath . '_'.date('Y-m-d H-i-s').'.zip';
        $websiteZipArchive       = new \PclZip($archiveFilePath);
        $websiteArchiveFileCount = $websiteZipArchive->add($websiteBackupPath, PCLZIP_OPT_REMOVE_PATH, $websiteBackupPath);

        if ($websiteArchiveFileCount == 0) {
            throw new MultiSiteJsonException(__METHOD__.' : Failed to create Zip Archiev ' . $websiteZipArchive->errorInfo(true));
        }

        $explodeFileCount = $websiteZipArchive->delete(PCLZIP_OPT_BY_PREG, '/\/tmp\/session_*|.ftpaccess$/');
        if ($explodeFileCount == 0) {
            throw new MultiSiteJsonException(__METHOD__.' : Failed to explode .ftpaccess in the  Archiev' . $websiteZipArchive->errorInfo(true));
        }

        //cleanup website Backup Folder
        \Cx\Lib\FileSystem\FileSystem::delete_folder($websiteBackupPath, true);

        return basename($archiveFilePath);
    }

    /**
     * Website Repository Backup
     * 
     * @param string $websiteBackupPath websiteBackup Location
     * @param string $websitePath       websitePath
     * 
     * @throws MultiSiteJsonException
     */
    protected function websiteRepositoryBackup($websiteBackupPath, $websitePath)
    {
        if (!\Cx\Lib\FileSystem\FileSystem::exists($websitePath)) {
            throw new MultiSiteJsonException(
                __METHOD__.' failed! : Failed to copy the website from ' . $websitePath . 'to ' . $websiteBackupPath
            );
        }

        // make sure backup directory exists
        \Cx\Lib\FileSystem\FileSystem::make_folder($websiteBackupPath . '/dataRepository');

        // copy everything except /tmp and any files starting with a dot
        foreach (new \DirectoryIterator($websitePath) as $fileInfo) {
            if ($fileInfo->isDot() || $fileInfo->getFilename() == 'tmp') {
                continue;
            }
            $result = false;
            try {
                if ($fileInfo->isDir()) {
                    $result = \Cx\Lib\FileSystem\FileSystem::copy_folder(
                        $websitePath . '/' . $fileInfo->getFilename(),
                        $websiteBackupPath . '/dataRepository/' . $fileInfo->getFilename(),
                        true
                    );
                } else {
                    $objFile = new \Cx\Lib\FileSystem\File(
                        $websitePath . '/' . $fileInfo->getFilename()
                    );
                    $objFile->copy(
                        $websiteBackupPath . '/dataRepository/' . $fileInfo->getFilename()
                    );
                    $result = true;
                }
            } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                \DBG::msg($e->getMessage());
            }
            if (!$result) {
                throw new MultiSiteJsonException(
                    __METHOD__.' failed! : Failed to copy the website from ' . $websitePath . 'to ' . $websiteBackupPath
                );
            }
        }
    }
    
    /**
     * get website Info data
     * 
     * @param array $params
     * 
     * @return array
     * @throws MultiSiteJsonException
     */
    public function getWebsiteInfo($params)
    {
        global $_ARRAYLANG;
        
        if (empty($params['post']) || !isset($params['post']['websiteId'])) {
            throw new MultiSiteJsonException(__METHOD__.' failed! : '.$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_PARAMS']);
        }
        
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_MANAGER:
                case ComponentController::MODE_HYBRID:
                    $websiteServiceRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
                    $website = $websiteServiceRepo->findOneById($params['post']['websiteId']);
                    if (!$website) {
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_EXISTS']);
                    }
                    
                    $websiteName = $website->getName();
                    $metaInfo    = array();

                    $metaInfo['website'] = array(
                        'websiteName'  => $websiteName,
                        'websiteEmail' => $website->getOwner()->getEmail()
                    );
                    
                    $subscription = ComponentController::getSubscriptionByWebsiteId($website->getId());
                    if ($subscription) {
                        $metaInfo['subscription'] = array(
                            'subscriptionCreatedDate'       => $subscription->getSubscriptionDate() 
                                                               ? $subscription->getSubscriptionDate()->format('Y-m-d H:i:s') 
                                                               : '',
                            'subscriptionExpiredDate'       => $subscription->getExpirationDate() 
                                                               ? $subscription->getExpirationDate()->format('Y-m-d H:i:s') 
                                                               : '',
                            'subscriptionRenewalDate'       => $subscription->getRenewalDate() 
                                                               ? $subscription->getRenewalDate()->format('Y-m-d H:i:s') 
                                                               : '',
                            'subscriptionRenewalUnit'       => $subscription->getRenewalUnit(),
                            'subscriptionRenewalQuantifier' => $subscription->getRenewalQuantifier(),
                            'subscriptionProductId'         => $subscription->getProduct()->getId(),
                            'subscriptionId'                => $subscription->getId()
                        );
                    }
                    return array('status' => 'success', 'websiteInfo' => $metaInfo);
                    break;
                default :
                    break;
            }
        } catch (\Exception $e) {
            throw new MultiSiteJsonException(__METHOD__.' failed! : '. $e->getMessage());
        }
    }
    
    /**
     * Get All Backup Files Info
     * 
     * @return array
     */
    public function getAllBackupFilesInfo($params)
    {
        global $_ARRAYLANG;
        
        $searchTerm = isset($params['post']['searchTerm']) 
                      ? contrexx_input2raw($params['post']['searchTerm'])
                      : '';
        $backupFilesInfo = array();
        
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode', 'MultiSite')) {
                case ComponentController::MODE_MANAGER:
                    $websiteServiceServers = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer')->findAll();
                    if (empty($websiteServiceServers)) {
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_SERVICE_SERVER']);
                    }
                    foreach ($websiteServiceServers as $websiteServiceServer) {
                        $paramsData = array(
                            'searchTerm'      => $searchTerm,
                            'serviceServer'   => $websiteServiceServer->gethostname(),
                            'serviceServerId' => $websiteServiceServer->getId(),
                        );
                        $resp = self::executeCommandOnServiceServer('getAllBackupFilesInfo', $paramsData, $websiteServiceServer);
                        if (!$resp || $resp->status == 'error' || $resp->data->status == 'error') {
                            if (isset($resp->data)) {
                                if (isset($resp->data->log)) {
                                    \DBG::appendLogs(array_map(function($logEntry) use ($websiteServiceServer) {return '('.$websiteServiceServer->gethostname().') '.$logEntry;}, $resp->data->log));
                                }
                                if (isset($resp->data->message)) {
                                    \DBG::msg($websiteServiceServer->gethostname() . ': ' . $resp->data->message);
                                }
                            }
                            continue;
                        }
                        
                        if (!empty($resp->data->backupFilesInfo)) {
                            foreach ($resp->data->backupFilesInfo as $fileInfo) {
                                $fileInfo->serviceServer   = $websiteServiceServer->gethostname();
                                $fileInfo->serviceServerId = $websiteServiceServer->getId();
                                $backupFilesInfo[] = $fileInfo;
                            }
                        }
                    }
                    if (empty($backupFilesInfo)) {
                        return array('status' => 'error', 'message' => 'No backups found!');
                    }
                    break;
                case ComponentController::MODE_HYBRID:
                case ComponentController::MODE_SERVICE:
                    $backupLocation = \Cx\Core\Setting\Controller\Setting::getValue('websiteBackupLocation', 'MultiSite');
                    //change the backup location path to absolute location path
                    \Cx\Lib\FileSystem\FileSystem::path_absolute_to_os_root($backupLocation);

                    if (!\Cx\Lib\FileSystem\FileSystem::exists($backupLocation)) {
                        throw new MultiSiteJsonException('The backup location doesnot exists!.');
                    }

                    $websiteName = $creationDate  = null;
                    foreach (glob($backupLocation.'/*.zip') as $filename) {
                        $websiteInfoArray = $this->getWebsiteInfoFromZip($filename, 'info/meta.yml');
                        $userEmail = isset($websiteInfoArray['website']) && isset($websiteInfoArray['website']['websiteEmail'])
                                     ? $websiteInfoArray['website']['websiteEmail']
                                     : '';

                        list($websiteName, $creationDate) = explode('_', basename($filename, '.zip'));
                        $timeStamp = strtotime(str_replace('-', ':', $creationDate));
                        $backupFilesInfo[] = array(
                            'websiteName'  => $timeStamp ? $websiteName : basename($filename, '.zip'),
                            'creationDate' => $timeStamp ? date('Y-m-d H:i:s', $timeStamp) : '',
                            'userEmailId'  => $userEmail,
                            'serviceServer'=> isset($params['post']['serviceServer'])
                                              ? contrexx_input2raw($params['post']['serviceServer'])
                                              : '',
                            'serviceServerId'=> isset($params['post']['serviceServerId'])
                                              ? contrexx_input2int($params['post']['serviceServerId'])
                                              : 0
                        );
                    }

                    if (!empty($searchTerm)) {
                        $backupFilesInfo = array_filter(
                            $backupFilesInfo,
                            function($el) use ($searchTerm) {
                                return ( strpos($el['websiteName'], $searchTerm) !== false);
                            }
                        );
                    }
                    break;
                default:
                    break;
            }
            return array('status' => 'success', 'backupFilesInfo' => $backupFilesInfo);
        } catch (\Throwable $e) {
            return array(
                'status' => 'error',
                'message' => 'JsonMultiSiteController::getAllBackupFilesInfo() : Failed!'.$e->getMessage(),
                'log'   => \DBG::getMemoryLogs(),
            );
        }
    }
    
    /**
     * Delete the backuped website file from service server
     * 
     * @param array $params
     * @return array
     * @throws MultiSiteJsonException
     */
    public function deleteWebsiteBackup($params)
    {
        global $_ARRAYLANG;

        if (   empty($params) 
            || empty($params['post']) 
            || empty($params['post']['websiteBackupFileName'])
        ) {
            \DBG::log(__METHOD__.' failed! : '.$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_PARAMS']);
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_DELETE_FAILED']);
        }
        
        $websiteBackupFileName = isset($params['post']['websiteBackupFileName']) 
                                 ? contrexx_input2raw($params['post']['websiteBackupFileName']) 
                                 : '';
        $websiteServiceServerId= isset($params['post']['websiteServiceServerId']) 
                                 ? contrexx_input2int($params['post']['websiteServiceServerId']) 
                                 : 0;
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode', 'MultiSite')) {
                case ComponentController::MODE_MANAGER:
                    if (empty($websiteServiceServerId)) {
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_PARAMS']);
                    }
                    
                    $websiteServiceServer = ComponentController::getServiceServerByCriteria(array('id' => $websiteServiceServerId));
                    if (!$websiteServiceServer) {
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_SERVICE_SERVER']);
                    }
                    
                    $response = self::executeCommandOnServiceServer(
                        'deleteWebsiteBackup',
                        array('websiteBackupFileName' => $websiteBackupFileName),
                        $websiteServiceServer
                    );
                    return $response->data ? $response->data : $response;
                    break;
                case ComponentController::MODE_HYBRID:
                case ComponentController::MODE_SERVICE:
                    $backupFileLocation = \Cx\Core\Setting\Controller\Setting::getValue('websiteBackupLocation', 'MultiSite');
                    if (   !\Cx\Lib\FileSystem\FileSystem::exists($backupFileLocation) 
                        || !\Cx\Lib\FileSystem\FileSystem::exists($backupFileLocation.'/'.$websiteBackupFileName)
                    ) {
                        throw new MultiSiteJsonException('The requested file '.$websiteBackupFileName.' doesnot exists.');
                    }

                    if (!\Cx\Lib\FileSystem\FileSystem::delete_file($backupFileLocation.'/'.$websiteBackupFileName)) {
                        throw new MultiSiteJsonException('Failed to delete the backuped website file');
                    }
                    
                    return array('status' => 'success', 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_DELETE_SUCCESS']);
                    break;
                default:
                    break;
            }
        } catch (\Exception $e) {
            \DBG::log(__METHOD__.' failed! : '.$e->getMessage());
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_DELETE_FAILED']);
        }
    }
    
    /**
     * Download website backup file
     * 
     * @param array $params website backup file name
     * 
     * @return array
     * @throws MultiSiteJsonException
     */
    public function downloadWebsiteBackup($params)
    {
        global $_ARRAYLANG;

        if (   empty($params) 
            || empty($params['post']) 
            || empty($params['post']['websiteBackupFileName'])
        ) {
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_PARAMS']);
        }
        
        $backupFileName = isset($params['post']['websiteBackupFileName']) 
                          ? contrexx_input2raw($params['post']['websiteBackupFileName']) 
                          : '';
        $serviceServerId = isset($params['post']['serviceServerId']) 
                           ? contrexx_input2int($params['post']['serviceServerId']) 
                           : 0;
            
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode', 'MultiSite')) {
                case ComponentController::MODE_MANAGER:
                    if (isset($params['post']['moveFileToManager'])) {
                        $tempDir = $this->cx->getWebsiteTempPath() . BackendController::MULTISITE_BACKUP_TEMP_DIR;
                    
                        //cleanup tmp/backups direcory
                        \Cx\Lib\FileSystem\FileSystem::delete_folder($tempDir, true);

                        $files  = $this->getMovedFilesInRemoteServer($tempDir);
                        if (!empty($files)) {
                            return array (
                                'status'      => 'success', 
                                'filePath'    => current($files)
                            );
                        }

                        throw new MultiSiteJsonException('Failed to get the Files from the serviceServer');
                    }
                    
                    $websiteServiceServer = ComponentController::getServiceServerByCriteria(array('id' => $serviceServerId));
                    if (!$websiteServiceServer) {
                        throw new MultiSiteBackendException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_SERVICE_SERVER']);
                    }
                    $resp = self::executeCommandOnServiceServer(
                        'downloadWebsiteBackup', 
                        array(
                            'websiteBackupFileName' => $backupFileName,
                            'serviceServerId'       => $serviceServerId
                        ),
                        $websiteServiceServer
                    );
                    return isset($resp->data) ? $resp->data : $resp;
                    break;
                case ComponentController::MODE_HYBRID:
                case ComponentController::MODE_SERVICE:
                    $backupFileLocation = \Cx\Core\Setting\Controller\Setting::getValue('websiteBackupLocation', 'MultiSite');
                    if (   !\Cx\Lib\FileSystem\FileSystem::exists($backupFileLocation) 
                        || !\Cx\Lib\FileSystem\FileSystem::exists($backupFileLocation.'/'.$backupFileName)
                    ) {
                        throw new MultiSiteJsonException('The requested file '.$backupFileName.' doesnot exists.');
                    }
                    
                    if (empty($serviceServerId)) {
                        return array (
                            'status'      => 'success', 
                            'filePath'    => $backupFileLocation.'/'.$backupFileName
                        );
                    }
                    
                    $resp  = self::executeCommandOnManager(
                        'downloadWebsiteBackup', 
                        array(
                            'websiteBackupFileName' => $backupFileName,
                            'moveFileToManager'     => 1
                        ), 
                        array($backupFileLocation.'/'.$backupFileName)
                    );
                    
                    return isset($resp->data) ? $resp->data : $resp;
                    break;
                default:
                    break;
            }
        } catch (\Exception $e) {
            \DBG::log(__METHOD__.' failed! : '.$e->getMessage());
            throw new MultiSiteJsonException(sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_DOWNLOAD_FAILED'], $backupFileName));
        }  
    }
    
    /**
     * Website info backup
     * 
     * @param \Cx\Core_Modules\MultiSite\Model\Entity\Website $website           Instance of website
     * @param string  $websiteBackupPath websiteBackup Location
     * 
     * @throws MultiSiteJsonException
     */
    protected function websiteInfoBackup(\Cx\Core_Modules\MultiSite\Model\Entity\Website $website, $websiteBackupPath)
    {
        global $_ARRAYLANG;
        
        if (!$website) {
            throw new MultiSiteJsonException(__METHOD__.' : Failed! : ' . $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_PARAMS']);
        }
        
        try {
            $resp = self::executeCommandOnManager('getWebsiteInfo', array('websiteId' => $website->getId()));
            if (!$resp || $resp->status == 'error' || $resp->data->status == 'error') {
               throw new MultiSiteJsonException('Failed to fetch the website info.');
            }

            if (   !\Cx\Lib\FileSystem\FileSystem::exists($websiteBackupPath) 
                && !\Cx\Lib\FileSystem\FileSystem::make_folder($websiteBackupPath)) {
                throw new MultiSiteJsonException('Failed to create the website backup location Folder.');
            }

            if (   !\Cx\Lib\FileSystem\FileSystem::exists($websiteBackupPath.'/info') 
                && !\Cx\Lib\FileSystem\FileSystem::make_folder($websiteBackupPath.'/info')) {
                throw new MultiSiteJsonException('Failed to create the website backup info location Folder');
            }

            $config = \Env::get('config');
            $resp->data->websiteInfo->codeBase =   !\FWValidator::isEmpty($website->getCodeBase())
                                                 ? $website->getCodeBase() 
                                                 : $config['coreCmsVersion'];
            $resp->data->websiteInfo->backupDateTime = date(ASCMS_DATE_FORMAT_DATETIME);
            $file    = new \Cx\Lib\FileSystem\File($websiteBackupPath.'/info/meta.yml');
            $dataSet = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($resp->data->websiteInfo);
            $file->touch();
            $file->write($dataSet->toYaml());
        } catch (\Exception $e) {
            throw new MultiSiteJsonException(__METHOD__.' : Failed! : ' .$e->getMessage());
        }
    }
    
    /**
     * Website Database Backup 
     * 
     * @param string $websiteBackupPath websiteBackup Location
     * @param string $websitePath       websitePath
     * @throws MultiSiteJsonException
     */
    protected function websiteDatabaseBackup($websiteBackupPath, $websitePath) 
    {
        $configFilePath = $websitePath . '/config/configuration.php';
        $output = $error = null;
        
        if (!\Cx\Lib\FileSystem\FileSystem::exists($configFilePath)) {
            throw new MultiSiteJsonException(__METHOD__.' failed!.: Website configuration file is not exists in the website.');
        }
        
        try {
            //Get database configuration details
            list($dbHost, $dbName, $dbUserName, $dbPassword, $dbPrefix) = $this->getWebsiteDatabaseInfo($configFilePath);

            if (   empty($dbHost) 
                || empty($dbUserName) 
                || empty($dbName)
            ) {
                throw new MultiSiteJsonException('database configuration details are not valid.');
            }

            if (   !\Cx\Lib\FileSystem\FileSystem::exists($websiteBackupPath. '/database') 
                && !\Cx\Lib\FileSystem\FileSystem::make_folder($websiteBackupPath . '/database')
            ) {
                throw new MultiSiteJsonException('Failed to create the website databse backup location Folder');
            }
            $sqlFilePath = $websiteBackupPath.'/database/sql_dump.sql';
            $exportDbCommand = 'mysqldump --skip-extended-insert -h '. $dbHost . ' -u ' . $dbUserName . ' -p\'' . $dbPassword . '\' '.$dbName.' > '.$sqlFilePath;

            //create database sql dump for the website
            exec($exportDbCommand, $output, $error);

            if ($error) {
                throw new MultiSiteJsonException('Failed to create a database sql dump for the '. basename($websitePath));
            } 
        } catch (\Exception $e) {
            throw new MultiSiteJsonException(__METHOD__.' : Failed! : ' .$e->getMessage());
        }
    }
    
    /**
     * Get website database configuration
     * 
     * @param string $configFilePath website config file path
     * @return array
     * @throws MultiSiteJsonException
     */
    protected function getWebsiteDatabaseInfo($configFilePath)
    {
        if (!\Cx\Lib\FileSystem\FileSystem::exists($configFilePath)) {
            throw new MultiSiteJsonException(__METHOD__.' failed! : Website configuration file is not exists in the website.');
        }
        
        $config = new \Cx\Lib\FileSystem\File($configFilePath);
        $configData = $config->getData();
        $dbHost = $dbUserName = $dbPassword = $dbName = $dbPrefix = $matches = null;

        if (preg_match('/\\$_DBCONFIG\\[\'host\'\\] = \'(.*?)\'/', $configData, $matches)) {
            $dbHost = $matches[1];
        }
        if (preg_match('/\\$_DBCONFIG\\[\'user\'\\] = \'(.*?)\'/', $configData, $matches)) {
            $dbUserName = $matches[1];
        }
        if (preg_match('/\\$_DBCONFIG\\[\'password\'\\] = \'(.*?)\'/', $configData, $matches)) {
            $dbPassword = $matches[1];
        }
        if (preg_match('/\\$_DBCONFIG\\[\'database\'\\] = \'(.*?)\'/', $configData, $matches)) {
            $dbName = $matches[1];
        }
        if (preg_match('/\\$_DBCONFIG\\[\'tablePrefix\'\\] = \'(.*?)\'/', $configData, $matches)) {
            $dbPrefix = $matches[1];
        }
        
        return array($dbHost, $dbName, $dbUserName, $dbPassword, $dbPrefix);
    }
    
    /**
     * websiteRestore
     * 
     * @param array $params website data
     * @return array
     * @throws MultiSiteJsonException
     */
    public function websiteRestore($params)
    {
        global $_ARRAYLANG;
        self::loadLanguageData();

        if (
            empty($params) ||
            empty($params['post'])
        ) {
            \DBG::log(__METHOD__.' Failed! : '.$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_PARAMS']);
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_RESTORE_FAILED']);
        }

        $websiteName           = isset($params['post']['websiteName']) 
                                 ? contrexx_input2raw($params['post']['websiteName']) 
                                 : '';
        $websiteBackupFileName = isset($params['post']['websiteBackupFileName']) 
                                 ? contrexx_input2raw($params['post']['websiteBackupFileName'])
                                 : '';
        $uploadedBackupFilePath = isset($params['post']['uploadedBackupFilePath']) 
                                 ? contrexx_input2raw($params['post']['uploadedBackupFilePath'])
                                 : '';
        $serviceServerId       = isset($params['post']['serviceServerId']) 
                                 ? contrexx_input2int($params['post']['serviceServerId'])
                                 : 0;
        $selectedUserId        = isset($params['post']['selectedUserId'])
                                 ? contrexx_input2int(($params['post']['selectedUserId']))
                                 : 0;
        $subscriptionId        = isset($params['post']['subscriptionId'])
                                 ? contrexx_input2int(($params['post']['subscriptionId']))
                                 : 0;

        $productId             = isset($params['post']['productId'])
                                 ? contrexx_input2int(($params['post']['productId']))
                                 : 0;
        if (   empty($websiteName)
            || (empty($websiteBackupFileName) && empty($uploadedBackupFilePath))
        ) {
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_PARAMS']);
        }

        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode', 'MultiSite')) {
                case ComponentController::MODE_MANAGER:
                    $websiteServiceServer = ComponentController::getServiceServerByCriteria(array('id' => $serviceServerId));
                    if (!$websiteServiceServer) {
                        throw new MultiSiteException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_SERVICE_SERVER']);
                    }
                    
                    \DBG::log('Forward websiteRestore to ServiceServer');
                    $response = self::executeCommandOnServiceServer('websiteRestore', $params['post'], $websiteServiceServer);

                    if (!isset($response)) {
                        throw new MultiSiteException('Restore failed');
                    }
                    
                    if (isset($response->log)) {
                        \DBG::appendLogs(
                            array_map(
                                function($logEntry) {
                                    return '(Website: '.$websiteName.') '.$logEntry;
                                },
                                $response->log
                            )
                        );
                    }
                    if (isset($response->data) && isset($response->data->log)) {
                        \DBG::appendLogs($response->data->log);
                    }
                    if ($response->status == 'error' || (isset($response->data) && $response->data->status == 'error')) {
                        throw new MultiSiteException('Restore failed');
                    }

                    // init notification mail
                    \Cx\Core\MailTemplate\Controller\MailTemplate::init('MultiSite');
                    $substitution = array();

                    // add info about backup
                    $backupData = array();
                    if (
                        isset($response->data) &&
                        isset($response->data->backup_website_name)
                    ) {
                        $backupData['BACKUP_WEBSITE_NAME'] = $response->data->backup_website_name;
                    }
                    if (
                        isset($response->data) &&
                        isset($response->data->backup_date)
                    ) {
                        $backupData['BACKUP_DATE'] = $response->data->backup_date;
                    }
                    if (
                        isset($response->data) &&
                        isset($response->data->backup_source)
                    ) {
                        $backupData['BACKUP_SOURCE'] = $response->data->backup_source;
                    }
                    if ($backupData) {
                        $substitution['BACKUP'] = array(
                            '0' => $backupData,
                        );
                    }

                    // fetch owner details
                    $objUser = \FWUser::getFWUserObject()->objUser;
                    $user = $objUser->getUser($selectedUserId);
                    if ($user) {
                        $gender = $user->objAttribute->getById($user->getProfileAttribute('gender'));
                        $country = $user->objAttribute->getById('country_' . $user->getProfileAttribute('country'));
                        $substitution['CUSTOMER'] = array(
                            '0' => array(
                                'CUSTOMER_EMAIL' => $user->getEmail(),
                                'CUSTOMER_GENDER' => $gender->getId() ? $gender->getName() : '',
                                'CUSTOMER_FIRSTNAME' => $user->getProfileAttribute('firstname'),
                                'CUSTOMER_LASTNAME' => $user->getProfileAttribute('lastname'),
                                'CUSTOMER_COMPANY' => $user->getProfileAttribute('company'),
                                'CUSTOMER_ZIP' => $user->getProfileAttribute('zip'),
                                'CUSTOMER_CITY' => $user->getProfileAttribute('city'),
                                'CUSTOMER_COUNTRY' => $country->getId() ? $country->getName() : '',
                                'CUSTOMER_PHONE' => $user->getProfileAttribute('phone_office'),
                            ),
                        );
                    }

                    $subscription = null;
                    if ($subscriptionId) {
                        $subscriptionRepo = \Env::get('em')->getRepository('Cx\Modules\Order\Model\Entity\Subscription');
                        $subscription = $subscriptionRepo->findOneBy(array('externalSubscriptionId' => $subscriptionId));
                    }
                    if ($subscription) {
                        $substitution['SUBSCRIPTION'] = array(
                            '0' => array(
                                'SUBSCRIPTION_ID' => $subscription->getId(),
                                'SUBSCRIPTION_NAME' => $subscription->getProduct()->getName(),
                            ),
                        );
                    }

                    $websiteUrl = ComponentController::getApiProtocol().$websiteName.'.'.\Cx\Core\Setting\Controller\Setting::getValue('multiSiteDomain','MultiSite');

                    // send notification mail to admin
                    \DBG::msg(__METHOD__ . ': send notification email > ADMIN');
                    \Cx\Core\MailTemplate\Controller\MailTemplate::send(array(
                        'section' => 'MultiSite',
                        'key' => 'notifyRestoreInfo',
                        'search' => array(
                            '[[MULTISITE_DOMAIN]]',
                            '[[WEBSITE_URL]]',
                            '[[WEBSITE_NAME]]',
                            '[[WEBSITE_SERVICE_SERVER_NAME]]',
                        ),
                        'replace' => array(
                            \Cx\Core\Setting\Controller\Setting::getValue('multiSiteDomain','MultiSite'),
                            $websiteUrl,
                            $websiteName,
                            $websiteServiceServer->getHostname(),
                        ),
                        'substitution' => $substitution,
                    ));
    
                    // return response
                    if (isset($response->data)) {
                        return $response->data;
                    }
                    break;
                case ComponentController::MODE_HYBRID:
                case ComponentController::MODE_SERVICE:
                    $backupLocation = \Cx\Core\Setting\Controller\Setting::getValue('websiteBackupLocation', 'MultiSite');
                    if (
                        !\Cx\Lib\FileSystem\FileSystem::exists($backupLocation) &&
                        !\Cx\Lib\FileSystem\FileSystem::make_folder($backupLocation)
                    ) {
                        \DBG::log(__METHOD__.' Failed! : '.$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_PARAMS']);
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_RESTORE_FAILED']);
                    }

                    $websiteBackupFilePath = \Cx\Core\Setting\Controller\Setting::getValue('websiteBackupLocation', 'MultiSite').'/'.$websiteBackupFileName;

                    if (   !\Cx\Lib\FileSystem\FileSystem::exists($websiteBackupFilePath) 
                        || \Cx\Lib\FileSystem\FileSystem::exists(\Cx\Core\Setting\Controller\Setting::getValue('websitePath', 'MultiSite') . '/' . $websiteName)
                    ) {
                        throw new MultiSiteJsonException('The website datafolder/backup repository ("' . $websiteBackupFilePath . '") doesnot exists!.');
                    }

                    \DBG::log('JsonMultiSiteController: Create a new website on restore..');
                    $this->createNewWebsiteOnRestore($websiteName, $websiteBackupFilePath, $serviceServerId, $selectedUserId, $subscriptionId, $productId);

                    \DBG::log('JsonMultiSiteController: Restore website Database and Repository..');
                    $this->websiteDataRestore($websiteName, $websiteBackupFilePath);

                    \DBG::log('JsonMultiSiteController: Website Info Restore..');
                    $this->websiteInfoRestore($websiteName, $websiteBackupFilePath, $subscriptionId, $productId);

                    $website = $this->getWebsiteByName($websiteName);
                    if (!$website) {
                        throw new MultiSiteJsonException('Failed to get the website.');
                    }

                    $this->updateUserAccountsOnRestore($website, $selectedUserId, false);

                    $websiteInfoArray = $this->getWebsiteInfoFromZip($websiteBackupFilePath, 'info/meta.yml');
                    $backupWebsiteName = '';
                    $backupDate = '';
                    if ($websiteInfoArray['website']['websiteName']) {
                        $backupWebsiteName = $websiteInfoArray['website']['websiteName'];
                    }
                    if ($websiteInfoArray['backupDateTime']) {
                        $backupDate = $websiteInfoArray['backupDateTime'];
                    }

                    return array(
                        'status'    => 'success', 
                        'message'   => sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_RESTORE_SUCCESS'], $websiteName),
                        'websiteUrl'=> ComponentController::getApiProtocol() . $website->getBaseDn()->getName(),
                        'websiteId' => $website->getId(),
                        'backup_website_name' => $backupWebsiteName,
                        'backup_date' => $backupDate,
                        'backup_source' => $websiteBackupFilePath,
                        'log'       => \DBG::getMemoryLogs(),
                    ); 
                    break;
                default:
                    break;
            }
        } catch (\Throwable $e) {
            \DBG::dump($e->getMessage());
            \DBG::log(__METHOD__.' Failed! : '.$e->getMessage());
            return array(
                'status' => 'error',
                'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_RESTORE_FAILED'],
                'log'   => \DBG::getMemoryLogs(),
            );
        }
    }
    
    /**
     * getWebsiteInfoFromZip
     * 
     * @param string $websiteBackupFilePath websiteBackup path
     * @param string $file                  yml file path
     * @return array
     * @throws MultiSiteJsonException
     */
    protected function getWebsiteInfoFromZip($websiteBackupFilePath, $file)
    {
        try {
            $websiteBackupFile = new \PclZip($websiteBackupFilePath);
            $list = $websiteBackupFile->extract(PCLZIP_OPT_BY_NAME, $file, PCLZIP_OPT_EXTRACT_AS_STRING);
            $yaml         = new \Symfony\Component\Yaml\Yaml();
            $content      = isset($list[0]['content']) ? $list[0]['content'] : '';
            $websiteInfo  = $yaml->parse($content, false, true);
            return $websiteInfo;
        } catch (\Exception $e) {
            throw new MultiSiteJsonException(__METHOD__.' failed! : '.$e->getMessage());
        }
        
    }
    
    /**
     * Get website by website name
     * 
     * @param string $websiteName
     * @return mixed boolean | object
     */
    protected function getWebsiteByName($websiteName)
    {
        if (empty($websiteName)) {
            return false;
        }
        
        $websiteRepo   = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
        $website       = $websiteRepo->findOneBy(array('name' => $websiteName));
        if (!$website) {
            return false;
        }
        return $website;
    }
    
    /**
     * createNewWebsite by using Restore Option
     * 
     * @param string  $websiteName           websiteName
     * @param string  $websiteBackupFilePath websiteBackup path
     * @param integer $serviceServerId       service server id
     * @param integer $selectedUserId        userId
     * @param integer $subscriptionId        subscriptionId
     * @param integer $productId             Product Id
     * @throws MultiSiteJsonException
     */
    protected function createNewWebsiteOnRestore($websiteName, $websiteBackupFilePath, $serviceServerId, $selectedUserId, $subscriptionId, $productId)
    {
        global $_ARRAYLANG;
        
        try {
            $websiteInfoArray = $this->getWebsiteInfoFromZip($websiteBackupFilePath, 'info/meta.yml');
            if (empty($websiteInfoArray)) {
                throw new MultiSiteJsonException(__METHOD__.': Failed to get the website Information from file');
            }
            
            $params = array(
                'multisite_address' => $websiteName, 
                'serviceServerId'   => $serviceServerId,
                'selectedUserId'    => $selectedUserId
            );
            
            if ($websiteInfoArray['website']) {
                $params['multisite_email_address'] = $websiteInfoArray['website']['websiteEmail'];
            }

            if (!empty($productId)) {
                $params['product_id'] = $productId;
            }

            if ($websiteInfoArray['subscription'] && empty($productId)) {
                $params['product_id']    = $websiteInfoArray['subscription']['subscriptionProductId'];
                $params['renewalOption'] = $websiteInfoArray['subscription']['subscriptionRenewalUnit'];
            }
            
            if (empty($params['product_id'])) {
                throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_PARAMS']);
            }

            $productRepo = \Env::get('em')->getRepository('Cx\Modules\Pim\Model\Entity\Product');
            $product     = $productRepo->findOneById($params['product_id']);

            if (!$product) {
                throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_WEBSITE_PRODUCT_NOT_EXISTS']);
            }
            
            if (!empty($subscriptionId)) {
                $params['subscriptionId']  = $subscriptionId;
            }
            
            $response = JsonMultiSiteController::executeCommandOnManager('addNewWebsiteInSubscription', $params);

            if (isset($response->log)) {
                \DBG::appendLogs(array_map(function($logEntry) {return '(Manager) '.$logEntry;}, $response->log));
            }
            if (isset($response->data) && isset($response->data->log)) {
                \DBG::appendLogs(array_map(function($logEntry) {return '(Manager) '.$logEntry;}, $response->data->log));
            }
            if ($response->status == 'error' || $response->data->status == 'error') {
                $message = '';
                if (isset($response->message)) {
                    $message = ' (' . $response->message . ')';
                }
                if (isset($response->data->message)) {
                    $message = ' (' . $response->data->message . ')';
                }
                throw new MultiSiteJsonException('Failed to create a website: ' . $websiteName . $message);
            }
        } catch (\Exception $e) {
            throw new MultiSiteJsonException(
                array(
                    'log'       => \DBG::getMemoryLogs(),
                    'message'   => __METHOD__.' Failed! : '.$e->getMessage()
                )
            );
        }
    }
    
    /**
     * Restore website database and data repository
     * 
     * @param string $websiteName           website Name
     * @param string $websiteBackupFilePath website backup path
     * @throws MultiSiteJsonException
     */
    protected function websiteDataRestore($websiteName, $websiteBackupFilePath)
    {
        $websitePath = \Cx\Core\Setting\Controller\Setting::getValue('websitePath', 'MultiSite') . '/' . $websiteName;
        try {
            \DBG::log('JsonMultiSiteController: Extract website database file..');
            $this->extractWebsiteDatabase($websitePath, $websiteBackupFilePath);
            
            \DBG::log('JsonMultiSiteController: Restore website database.. ');
            $this->websiteDatabaseRestore($websitePath);
            
            \DBG::log('JsonMultiSiteController: Restore website data repository files..');
            $this->websiteRepositoryRestore($websitePath, $websiteBackupFilePath);
        } catch (\Exception $e) {
            throw new MultiSiteJsonException(__METHOD__.' Failed! : '.$e->getMessage());
        }
    }
    
    /**
     * Restore the website repository
     * 
     * @param string $websitePath           website path
     * @param string $websiteBackupFilePath website backup path
     * @throws MultiSiteJsonException
     */
    protected function websiteRepositoryRestore($websitePath, $websiteBackupFilePath)
    {
        ini_set('memory_limit', '1024M');
        if (!\Cx\Lib\FileSystem\FileSystem::exists($websiteBackupFilePath)) {
            throw new MultiSiteJsonException(__METHOD__.' failed! : Website Backup file does not exists!.');
        }
        
        $restoreWebsiteFile = new \PclZip($websiteBackupFilePath);
        if ($restoreWebsiteFile->extract(
            // destination path
            PCLZIP_OPT_PATH, $websitePath,
            // do not extract /config folder, except for yaml-files.
            // however, yaml-files of system components must also
            // not get extracted
            PCLZIP_OPT_BY_PREG, '/dataRepository(.(?!config\/(?!(?!Config|MultiSite|Support|DomainRepository)[^\/]+\.yml)))*$/',
            // strip folder dataRepository from extraction
            PCLZIP_OPT_REMOVE_PATH, 'dataRepository',
            // extract any file (and overwrite existing files) independent of
            // its creation time
            PCLZIP_OPT_REPLACE_NEWER
        ) == 0) {
            throw new MultiSiteJsonException(__METHOD__.' failed! : Failed to extract the website repository on restore.');
        }

        // create tmp directory
        \Cx\Lib\FileSystem\FileSystem::make_folder($websitePath . '/tmp');
    }
    
    /**
     * Extract the database folder in website_temp folder
     * 
     * @param string $websitePath           website path
     * @param string $websiteBackupFilePath website backup path
     * @throws MultiSiteJsonException
     */
    protected function extractWebsiteDatabase($websitePath, $websiteBackupFilePath)
    {
        $websiteTempDir = $websitePath . '/website_temp';
        if (   !\Cx\Lib\FileSystem\FileSystem::exists($websiteTempDir)
            && !\Cx\Lib\FileSystem\FileSystem::make_folder($websiteTempDir)
        ) {
            throw new MultiSiteJsonException(__METHOD__.' : Failed!. Unable to create a directory '.$websiteTempDir);
        }

        $restoreWebsiteFile = new \PclZip($websiteBackupFilePath);
        if ($restoreWebsiteFile->extract(PCLZIP_OPT_PATH, $websiteTempDir, PCLZIP_OPT_BY_PREG, '/database/') == 0) {
            throw new MultiSiteJsonException(__METHOD__.' : Failed!. Unable to extract the files in '.$websiteTempDir);
        }
    }
    
    /**
     * Restore the website database
     * 
     * @param string $websitePath           website path
     * @throws MultiSiteJsonException
     */
    protected function websiteDatabaseRestore($websitePath)
    {
        $configFilePath = $websitePath . '/config/configuration.php';
        $websiteTempPath = $websitePath.'/website_temp';
        
        try {
            if (!\Cx\Lib\FileSystem\FileSystem::exists($configFilePath)) {
               throw new MultiSiteJsonException('Website configuration file is not exists in the website.');
            }

            list($dbHost, $dbName, $dbUserName, $dbPassword, $dbPrefix) = $this->getWebsiteDatabaseInfo($configFilePath);
            
            if (empty($dbHost) || empty($dbName) || empty($dbUserName) || empty($dbPassword)) {
                throw new MultiSiteJsonException('Database details are not valid');
            }

            $sqlDumpFile = $websiteTempPath . '/database/sql_dump.sql';
            if (!\Cx\Lib\FileSystem\FileSystem::exists($sqlDumpFile)) {
                throw new MultiSiteJsonException('Database sql file not exists!.');
            }

            $output = $error = null;
            $importDbCommand = 'mysql -h '. $dbHost . ' -u ' . $dbUserName . ' -p\'' . $dbPassword . '\' '.$dbName.' < '.$sqlDumpFile;

            //Execute the database import command
            exec($importDbCommand, $output, $error);

            if ($error) {
                throw new MultiSiteJsonException('Failed to import database on restore.');
            }
            $this->getComponent('Cache')->setCachePrefix($dbName.'.'.$dbPrefix);
            $this->getComponent('Cache')->clearCache(\Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_ENGINE_MEMCACHED);
            $this->getComponent('Cache')->setCachePrefix();
            //cleanup website tempory folder
            if (!\Cx\Lib\FileSystem\FileSystem::delete_folder($websiteTempPath, true)) {
                throw new MultiSiteJsonException('Failed to delete the website temp folder on restore.');
            }
            
        } catch (\Exception $e) {
            throw new MultiSiteJsonException(__METHOD__.' failed! :'.$e->getMessage());
        }
    }
    
    /**
     * Restore the website Info 
     * 
     * @param string  $websiteName           website name
     * @param string  $websiteBackupFilePath website backup path
     * @param integer $subscriptionId        subscriptionId
     * @param integer $productId             Product Id
     * @throws MultiSiteJsonException
     */
    protected function websiteInfoRestore($websiteName, $websiteBackupFilePath, $subscriptionId, $productId)
    {
        global $_ARRAYLANG;
        
        $em          = $this->cx->getDb()->getEntityManager();
        $websiteRepo = $em->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
        $website     = $websiteRepo->findOneBy(array('name' => $websiteName));
        if (!$website) {
            throw new MultiSiteJsonException(__METHOD__.' failed! : '.$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_NOT_EXISTS']);
        }
        
        try {
            $websiteConfigArray = $this->getWebsiteInfoFromZip($websiteBackupFilePath, 'dataRepository/config/Config.yml');
            $websiteInfoArray   = $this->getWebsiteInfoFromZip($websiteBackupFilePath, 'dataRepository/config/MultiSite.yml');
            $websiteMetaArray   = $this->getWebsiteInfoFromZip($websiteBackupFilePath, 'info/meta.yml');
            if (empty($websiteConfigArray) || empty($websiteInfoArray)) {
                throw new MultiSiteJsonException('Failed to fetch the website information from backuped zip file');
            }
            
            $configSettingArray = array();
            foreach ($websiteConfigArray['data'] as $value) {
                $configSettingArray[$value->getName()] = array(
                    'name'  => $value->getName(),
                    'group' => $value->getGroup(),
                    'value' => $value->getValue()
                );
            }

            $websiteConfigInfo = array(
                'websiteMultisite'   => array(
                    'websiteState' => $websiteInfoArray['websiteState']['value'],
                    'websiteUserId' => $websiteInfoArray['websiteUserId']['value']
                ),
                'websiteConfig'      => $configSettingArray
            );

            \DBG::log('JsonMultiSiteController: Restore website net domains..');
            //$this->updateWebsiteNetDomainsOnRestore($website, $websiteBackupFilePath);
            
            \DBG::log('JsonMultiSiteController: Restore website Configurations..');
            $resp = JsonMultiSiteController::executeCommandOnWebsite('updateWebsiteConfig', $websiteConfigInfo, $website);
            if (isset($resp->data) && isset($resp->data->log)) {
                \DBG::appendLogs(array_map(function($logEntry)use($website) {return '(Website: '.$website->getName().') '.$logEntry;}, $resp->data->log));
            }
            if ($resp->status == 'error' || $resp->data->status == 'error') {
                throw new MultiSiteJsonException('Failed to update website configurations.');
            }
            
            if (!empty($websiteMetaArray['codeBase'])) {
                $codeBaseArray = array(
                    'websiteIds' => $website->getId(),
                    'codeBase'   => $websiteMetaArray['codeBase'],
                );
                self::executeCommandOnManager('updateWebsiteCodeBase', $codeBaseArray);
            }

            //Skip to restore subscription for user defined subscription
            if (empty($subscriptionId) && empty($productId)) {
                \DBG::log('JsonMultiSiteController: Restore website subscription details..');
                $this->updateWebsiteSubscriptionDetails($websiteBackupFilePath, $website->getId());
            }
        } catch (\Exception $e) {
            throw new MultiSiteJsonException(__METHOD__.' failed! : '.$e->getMessage());
        }
    }
    
    /**
     * Update website Config details
     * 
     * @param type $params
     * @return array
     * @throws MultiSiteJsonException
     */
    public function updateWebsiteConfig($params)
    {
        global $_ARRAYLANG;

        try {
            if (empty($params) || empty($params['post'])) {
                throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_PARAMS']);
            }

            \DBG::log('JsonMultiSiteController: Restore website multisite configurations..');
            $this->updateWebsiteMultisiteConfigOnRestore($params['post']['websiteMultisite']);
            
            \DBG::log('JsonMultiSiteController: Restore website configurations / settings..');
            $this->updateWebsiteConfigOnRestore($params['post']['websiteConfig']);

            return array(
                'status' => 'success',
                'log'    => \DBG::getMemoryLogs(),
            );
        } catch (\Throwable $e) {
            \DBG::log(__METHOD__.' failed! : '.$e->getMessage());
            return array(
                'status' => 'error', 
                'message' => 'Failed to restore website configurations.',
                'log'    => \DBG::getMemoryLogs(),
            );
        }
    }
    
    /**
     * updateWebsiteNetDomainsOnRestore
     * 
     * @param \Cx\Core_Modules\MultiSite\Model\Entity\Website  $website               websiteObject
     * @param string                                           $websiteBackupFilePath website backup path
     * 
     * @throws MultiSiteJsonException
     */
    protected function updateWebsiteNetDomainsOnRestore(\Cx\Core_Modules\MultiSite\Model\Entity\Website $website, $websiteBackupFilePath)
    {
        $domainRepositoryFile  = 'dataRepository/config/DomainRepository.yml';
        $websiteDomainObjArray = $this->getWebsiteInfoFromZip($websiteBackupFilePath, $domainRepositoryFile);
        if (empty($websiteDomainObjArray)) {
            throw new MultiSiteJsonException('Failed to get the backuped website domains.');
        }
        
        try {
            if (!empty($websiteDomainObjArray['data'])) {
                foreach ($websiteDomainObjArray['data'] as $domain) {
                    $resp = JsonMultiSiteController::executeCommandOnWebsite('mapNetDomain', array('domainName' => $domain->getName()), $website);
                    if (!$resp || $resp->status == 'error' || $resp->data->status == 'error') {
                        throw new MultiSiteJsonException('Failed to restore the website domains.');
                    }
                }
            }
        } catch (\Exception $e) {
            throw new MultiSiteJsonException(__METHOD__.' failed! : '.$e->getMessage());
        }
    }
    
    /**
     * Update website configuration settings
     * 
     * @param array $websiteInfo websiteConfig details
     * @throws MultiSiteJsonException
     */
    protected function updateWebsiteConfigOnRestore($websiteInfo)
    {
        if (empty($websiteInfo)) {
            throw new MultiSiteJsonException(__METHOD__.' : failed!. Website info should not be empty');
        }

        \Cx\Core\Setting\Controller\Setting::init('Config', null, 'Yaml');
        foreach ($websiteInfo as $config) {
            // skip system info
            if (
                in_array(
                    $config['group'],
                    array('license', 'release')
                ) ||
                $config['name'] == 'installationId'
            ) {
                continue;
            }

            // skip special options
            if (
                in_array(
                    $config['name'],
                    array(
                        // setting the main domain is not possible,
                        // as the specified domain most likely does not exist
                        'mainDomainId',
                        // forcing the main domain might result
                        // in unexpected behavior
                        'forceDomainUrl',
                    )
                )
            ) {
                continue;
            }

            // update config
            \Cx\Core\Setting\Controller\Setting::set($config['name'], $config['value']);
        }
        \Cx\Core\Setting\Controller\Setting::updateAll();
    }
    
    /**
     * Update website MultiSite settings
     * 
     * @param array $websiteInfo websiteInfo
     * 
     * @throws MultiSiteJsonException
     */
    protected function updateWebsiteMultisiteConfigOnRestore($websiteInfo)
    {
        if (empty($websiteInfo)) {
            throw new MultiSiteJsonException(__METHOD__.' : failed!. Website info should not be empty');
        }
        
        \Cx\Core\Setting\Controller\Setting::init('MultiSite', 'website', 'FileSystem');
        \Cx\Core\Setting\Controller\Setting::set('websiteState', $websiteInfo['websiteState']);
        \Cx\Core\Setting\Controller\Setting::set('websiteUserId', $websiteInfo['websiteUserId']);
        
        \Cx\Core\Setting\Controller\Setting::update('websiteState');
    }
    
    /**
     * Update website subscription details
     * 
     * @param string  $websiteBackupFilePath website backup path
     * @param integer $websiteId             websiteId
     * 
     * @throws MultiSiteJsonException
     */
    protected function updateWebsiteSubscriptionDetails($websiteBackupFilePath, $websiteId)
    {
        try {
            $websiteInfoArray = $this->getWebsiteInfoFromZip($websiteBackupFilePath, 'info/meta.yml');
            if (   empty($websiteInfoArray) 
                || !isset($websiteInfoArray['subscription'])
            ) {
               throw new MultiSiteJsonException('Subscription details are empty.');
            }
            
            $params = array(
                'websiteId'    => $websiteId,
                'subscription' => $websiteInfoArray['subscription']
            );
            
            //update subscription details
            $response = JsonMultiSiteController::executeCommandOnManager('updateWebsiteSubscriptionInfo', $params);
            if ($response->status == 'error' || $response->data->status == 'error') {
                throw new MultiSiteJsonException('Failed to update subscription details.');
            }
        } catch (\Exception $e) {
            throw new MultiSiteJsonException(__METHOD__. ' failed! : '.$e->getMessage());
        }
    }
    
    /**
     * update subscription details of website
     * 
     * @param type $params
     * @return array
     * @throws MultiSiteJsonException
     */
    public function updateWebsiteSubscriptionInfo($params)
    {
        global $_ARRAYLANG;
        
        if (   empty($params) 
            || empty($params['post']['websiteId'])
            || empty($params['post']['subscription'])
        ) {
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_PARAMS']);
        }
        
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode', 'MultiSite')) {
                case ComponentController::MODE_MANAGER:
                case ComponentController::MODE_HYBRID:
                    $websiteId         = isset($params['post']['websiteId'])
                                         ? contrexx_input2int($params['post']['websiteId'])
                                         : 0;
                    $subscriptionInfo  = isset($params['post']['subscription'])
                                         ? contrexx_input2raw($params['post']['subscription'])
                                         : '';

                    $subscription = ComponentController::getSubscriptionByWebsiteId($websiteId);
                    if (!$subscription) {
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_WEBSITE_SUBSCRIPTION_NOT_EXISTS']);
                    }

                    $subscriptionCreatedDate = isset($subscriptionInfo['subscriptionCreatedDate'])
                                               ? new \DateTime($subscriptionInfo['subscriptionCreatedDate'])
                                               : null;
                    $subscriptionExpiredDate = isset($subscriptionInfo['subscriptionExpiredDate'])
                                               ? new \DateTime($subscriptionInfo['subscriptionExpiredDate'])
                                               : null;
                    $subscriptionRenewalDate = isset($subscriptionInfo['subscriptionRenewalDate'])
                                               ? new \DateTime($subscriptionInfo['subscriptionRenewalDate'])
                                               : null;
                    $subscription->setSubscriptionDate($subscriptionCreatedDate);
                    $subscription->setExpirationDate($subscriptionExpiredDate);
                    $subscription->setRenewalDate($subscriptionRenewalDate);

                    \Env::get('em')->flush();

                    return array('status' => 'success');
                    break;
                default:
                    throw new MultiSiteJsonException('Failed to restore subscription details.');
                    break;
            }
        } catch (\Exception $e) {
            \DBG::log(__METHOD__. ' failed!. '.$e->getMessage());
            throw new MultiSiteJsonException('Failed to restore subscription details.');
        }
    }
    
    /**
     * Add New Website In Subscription
     * 
     * @param array $params
     * @return array
     * @throws MultiSiteJsonException
     */
    public function addNewWebsiteInSubscription($params)
    {
        global $_ARRAYLANG;
        
        $subscriptionId   = isset($params['post']['subscriptionId'])
                            ? contrexx_input2int($params['post']['subscriptionId'])
                            : 0;
        $productId        = isset($params['post']['product_id'])
                            ? contrexx_input2int($params['post']['product_id'])
                            : 0;
        $websiteName      = isset($params['post']['multisite_address'])
                            ? contrexx_input2raw($params['post']['multisite_address'])
                            : '';
        $email            = isset($params['post']['multisite_email_address'])
                            ? contrexx_input2raw($params['post']['multisite_email_address'])
                            : '';
        $serviceServerId  = isset($params['post']['serviceServerId'])
                            ? contrexx_input2int($params['post']['serviceServerId'])
                            : 0;
        $selectedUserId   = isset($params['post']['selectedUserId'])
                            ? contrexx_input2int($params['post']['selectedUserId'])
                            : 0;
        $renewalOption    = isset($params['post']['renewalOption'])
                            ? contrexx_input2int($params['post']['renewalOption'])
                            : \Cx\Modules\Pim\Model\Entity\Product::UNIT_MONTH;
        
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode', 'MultiSite')) {
                case ComponentController::MODE_MANAGER:
                case ComponentController::MODE_HYBRID:
                    if (   (empty($subscriptionId) && empty($productId))
                        || empty($websiteName)
                        || empty($email)
                    ) {
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_PARAMS']);
                    }

                    $objUser = \FWUser::getFWUserObject()->objUser;
                    $multisiteComponentController = $this->getComponent('Multisite');
                    if (!$multisiteComponentController) {
                        throw new MultiSiteJsonException('Failed to create the website in subscription.');
                    }

                    if (!empty($selectedUserId)) {
                        $user = $objUser->getUser($selectedUserId);
                        if (!$user) {
                            throw new MultiSiteJsonException('Failed to fetch the user object from the selected user id '.$selectedUserId);
                        }

                        $response = !empty($subscriptionId)
                                    ? $multisiteComponentController->createNewWebsiteInSubscription($subscriptionId, $websiteName, $user, $serviceServerId)
                                    : $multisiteComponentController->createNewWebsiteByProduct($productId, $websiteName, $user, $serviceServerId, $renewalOption);

                    } else {
                        $user = $objUser->getUsers(array('email' => $email));
                        if (!empty($user)) {
                            throw new MultiSiteJsonException('This email ( '.$email. ' )  already exists!.');
                        }

                        $paramsData = array( 
                            'post' => array(
                                'multisite_address'       => $websiteName, 
                                'serviceServerId'         => $serviceServerId,
                                'multisite_email_address' => $email,
                                'product_id'              => $productId,
                                'renewalOption'           => $renewalOption
                            )
                        );
                        $response = $this->signup($paramsData);
                    }
                    return is_array($response) 
                           ? $response
                           : ($response->data ? $response->data : $response);
                    break;
                default:
                    break;
            }
        } catch (\Exception $e) {
            \DBG::log(__METHOD__.' failed!. : '.$e->getMessage());
            return array(
                'status' => 'error', 
                'message' => 'Failed to create the website in subscription.',
                'log'    => \DBG::getMemoryLogs(),
            );
        }
    }
    
    /**
     * Check user exists or not during restore website
     * 
     * @param array $params
     * @return array
     * @throws MultiSiteJsonException
     */
    public function checkUserStatusOnRestore($params) {
        global $_ARRAYLANG;
        
        if (empty($params) || empty($params['post'])) {
            \DBG::log(__METHOD__.'Failed! : '.$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_PARAMS']);
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_RESTORE_FAILED']);
        }
        
        try {
            $uploadedFilePath      = isset($params['post']['uploadedFilePath'])
                                     ? contrexx_input2raw($params['post']['uploadedFilePath'])
                                     : '';
            if (empty($uploadedFilePath)) {
                throw new MultiSiteException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_PARAMS']);
            }
            
            \Cx\Lib\FileSystem\FileSystem::path_absolute_to_os_root($uploadedFilePath);

            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode', 'MultiSite')) {
                case ComponentController::MODE_MANAGER:
                case ComponentController::MODE_HYBRID:
                    if (!\Cx\Lib\FileSystem\FileSystem::exists($uploadedFilePath)) {
                        throw new MultiSiteJsonException('The website backup zip file doesnot exists!.');
                    }

                    $websiteInfoArray = $this->getWebsiteInfoFromZip($uploadedFilePath, 'info/meta.yml');
                    if (empty($websiteInfoArray)) {
                        throw new MultiSiteJsonException(__METHOD__.': Failed to get the website Information from file');
                    }

                    $userEmailId = isset($websiteInfoArray['website']) && isset($websiteInfoArray['website']['websiteEmail'])
                                   ? $websiteInfoArray['website']['websiteEmail']
                                   : '';
                    if (empty($userEmailId)) {
                        throw new MultiSiteJsonException('User Email not found in the backup file.');
                    }

                    $objUser = \FWUser::getFWUserObject()->objUser->getUsers(array('email' => $userEmailId));
                    return array('status' => 'success' , 'userId' => $objUser ? $objUser->getId() : 0);
                    break;
                default:
                    break;
            }
        } catch (\Exception $e) {
            \DBG::log(__METHOD__. ' Failed: '.$e->getMessage());
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_RESTORE_FAILED']);
        }
    }

    /**
     * Get user Email from website backup file
     *
     * @param array $params Post parameters
     *
     * @return array
     * @throws MultiSiteJsonException
     */
    public function getUserInfoFromBackup($params)
    {
        global $_ARRAYLANG;

        if (empty($params) || empty($params['post'])) {
            \DBG::log(__METHOD__.'Failed! : '.$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_PARAMS']);
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_RESTORE_FAILED']);
        }

        try {
            $uploadedFilePath      = isset($params['post']['uploadedFilePath'])
                                     ? contrexx_input2raw($params['post']['uploadedFilePath'])
                                     : '';
            if (empty($uploadedFilePath)) {
                throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_PARAMS']);
            }

            \Cx\Lib\FileSystem\FileSystem::path_absolute_to_os_root($uploadedFilePath);
            
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode', 'MultiSite')) {
                case ComponentController::MODE_SERVICE:
                case ComponentController::MODE_HYBRID:
                    if (!\Cx\Lib\FileSystem\FileSystem::exists($uploadedFilePath)) {
                        throw new MultiSiteJsonException('The website backup zip file doesnot exists!.');
                    }
                    
                    $websiteInfoArray = $this->getWebsiteInfoFromZip($uploadedFilePath, 'info/meta.yml');
                    if (empty($websiteInfoArray)) {
                        throw new MultiSiteJsonException(__METHOD__.': Failed to get the website Information from file');
                    }

                    $userEmailId = isset($websiteInfoArray['website']) && isset($websiteInfoArray['website']['websiteEmail'])
                                   ? $websiteInfoArray['website']['websiteEmail']
                                   : '';
                    if (empty($userEmailId)) {
                        throw new MultiSiteJsonException('User Email not found in the backup file.');
                    }

                    return array('status' => 'success' , 'userEmail' => $userEmailId);
                    break;
                default:
                    break;
            }
        } catch (\Exception $e) {
            \DBG::log(__METHOD__. ' Failed: '.$e->getMessage());
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_RESTORE_FAILED']);
        }
    }
    
    /**
     * Get available subscriptions by UserId
     * 
     * @param array $params
     * @return array
     * @throws MultiSiteJsonException
     */
    public function getAvailableSubscriptionsByUserId($params)
    {
        global $_ARRAYLANG;
        
        if (empty($params) || empty($params['post'])) {
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_PARAMS']);
        }
        try {
             switch (\Cx\Core\Setting\Controller\Setting::getValue('mode', 'MultiSite')) {
                case ComponentController::MODE_MANAGER:
                case ComponentController::MODE_HYBRID:
                    $userId = isset($params['post']['userId'])
                              ? contrexx_input2int($params['post']['userId'])
                              : 0;
                    $objUser = \FWUser::getFWUserObject()->objUser->getUser($userId);

                    if (!$objUser) {
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_NOT_VALID_USER']);
                    }

                    $crmContactId = $objUser->getCrmUserId();
                    if (!$crmContactId) {
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_NOT_VALID_USER']);
                    }

                    $em = $this->cx->getDb()->getEntityManager();
                    $componentRepo  = $em->getRepository('Cx\Core\Core\Model\Entity\SystemComponent');
                    $subscriptionRepo = $em->getRepository('Cx\Modules\Order\Model\Entity\Subscription');
                    $component      = $componentRepo->findOneBy(array('name' => 'MultiSite'));
                    $freeProductIds = $component->getProductIdsByEntityClass('Website');
                    $filter = array(
                        'contactId'      => $crmContactId,
                        'status'         => 'valid',
                        'excludeProduct' => $freeProductIds
                    );
                    $subscriptions    = $subscriptionRepo->findSubscriptionsBySearchTerm($filter);
                    $subscriptionList = array();
                    if (!empty($subscriptions)) {
                        foreach ($subscriptions as $subscription) {
                            if ($subscription->getState() == \Cx\Modules\Order\Model\Entity\Subscription::STATE_TERMINATED) {
                                continue;
                            }

                            $product = $subscription->getProduct();
                            if (!$product) {
                                continue;
                            }

                            $websiteCollection = $subscription->getProductEntity();
                            if ($websiteCollection instanceof \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteCollection) {
                                $description       = $subscription->getDescription();
                                $websites          = $websiteCollection->getWebsites();
                                if ($websiteCollection->getQuota() <= count($websites)) {
                                    continue;
                                }

                                if (empty($description)) {
                                    $websiteNames = array();
                                    foreach ($websites as $website) {
                                        $websiteNames[] = $website->getName();
                                    }
                                    $description = !empty($websiteNames) ? implode(', ', $websiteNames) : '';
                                }
                                $subscriptionDesc = !empty($description)
                                                    ? $description 
                                                    : $_ARRAYLANG['TXT_MULTISITE_WEBSITE_SUBSCRIPTION'] . ' - #'.$subscription->getId();
                                $subscriptionList[] = $subscription->getId(). ":".$subscriptionDesc;
                            }
                        }
                    }

                    if (!empty($subscriptionList)) {
                        return array('status' => 'success', 'subscriptionsList' => $subscriptionList);
                    }
                    break;
                default:
                    break;
             }
        } catch (\Exception $e) {
            \DBG::log(__METHOD__.' Failed! : '.$e->getMessage());
            return array('status' => 'error');
        }
    }
    
    /**
     * Send a file to remote server
     * 
     * @param array $params
     * 
     * @return array
     * @throws MultiSiteJsonException
     */
    public function sendFileToRemoteServer($params) 
    {
        global $_ARRAYLANG;
        
        if (!isset($params['post'])) {
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_PARAMS']);
        }
        
        try {
            $backupFileName  = isset($params['post']['backupFileName'])
                               ? contrexx_input2raw($params['post']['backupFileName'])
                               : '';
            $serviceServerId = isset($params['post']['serviceServerId'])
                               ? contrexx_input2int($params['post']['serviceServerId'])
                               : 0;
            
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode', 'MultiSite')) {
                case ComponentController::MODE_MANAGER:
                    $websiteServiceServer = ComponentController::getServiceServerByCriteria(array('id' => $serviceServerId));
                    if (!$websiteServiceServer) {
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_SERVICE_SERVER']);
                    }

                    $destinationFolder = $this->cx->getWebsiteTempPath() . BackendController::MULTISITE_BACKUP_TEMP_DIR;
                    $files = $this->getMovedFilesInRemoteServer($destinationFolder);
                    $resp = self::executeCommandOnServiceServer('sendFileToRemoteServer', array('destinationServer' => $params['post']['serviceServerId']), $websiteServiceServer, $files);
                    if (!$resp || $resp->status == 'error' || $resp->data->status == 'error') {
                        throw new MultiSiteJsonException('Failed to send file to destination service server');
                    }

                    //cleanup folder
                    if (\Cx\Lib\FileSystem\FileSystem::exists($destinationFolder)) {
                        \Cx\Lib\FileSystem\FileSystem::delete_folder($destinationFolder, true);
                    }
                    return array('status' => 'success');
                    break;
                case ComponentController::MODE_SERVICE:
                    if (isset($params['post']['destinationServer'])) {
                        $this->getMovedFilesInRemoteServer();
                        return array('status' => 'success');
                    }
                    
                    if (empty($backupFileName) || empty($serviceServerId)) {
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_PARAMS']);
                    }

                    $backupLocation = \Cx\Core\Setting\Controller\Setting::getValue('websiteBackupLocation', 'MultiSite');
                    $filePath       = $backupLocation.'/'.$backupFileName;
                    if (   !\Cx\Lib\FileSystem\FileSystem::exists($backupLocation) 
                        || !\Cx\Lib\FileSystem\FileSystem::exists($filePath)
                    ) {
                        throw new MultiSiteJsonException('The backped file' .$backupFileName.' not exists in service server.');
                    }

                    $resp = self::executeCommandOnManager('sendFileToRemoteServer', array('serviceServerId' => $serviceServerId), array($filePath));
                    if (   !$resp 
                        || $resp->status == 'error' 
                        || $resp->data->status == 'error'
                    ) {
                        throw new MultiSiteJsonException('Failed to send file to destination service server.');
                    }
                    return array('status' => 'success');
                    break;
                default :
                    break;
            }
        
        } catch (\Exception $e) {
            \DBG::log(__METHOD__.' failed!. : '.$e->getMessage());
            return array('status' => 'error');
        }
    }
    
    /**
     * Get all Moved Files in remote server
     * 
     * @param string $destinationFolder destinationFolder name
     * 
     * @return array
     * @throws MultiSiteJsonException
     */
    protected function getMovedFilesInRemoteServer($destinationFolder = null)
    {
        $fileLocation = !empty($destinationFolder) 
                        ? $destinationFolder 
                        : \Cx\Core\Setting\Controller\Setting::getValue('websiteBackupLocation', 'MultiSite');
        
        if (   !\Cx\Lib\FileSystem\FileSystem::exists($fileLocation) 
            && !\Cx\Lib\FileSystem\FileSystem::make_folder($fileLocation)
        ) {
            throw new MultiSiteJsonException(__METHOD__.' :failed!.  Failed to create a folder '.$fileLocation);
        }
        
        $filesArray = array();
        if (empty($_FILES)) {
            throw new MultiSiteJsonException(__METHOD__.' :failed!. Their is no uploaded files.');
        }
        
        foreach ($_FILES as $file) {
            if ($file['error'] != 0) {
                throw new MultiSiteJsonException(__METHOD__.' :failed!. Their is an error in the file.');
            }
            
            if (!move_uploaded_file($file['tmp_name'], $fileLocation.'/'.$file['name'])) {
                throw new MultiSiteJsonException(__METHOD__.' :failed!. Failed to move/copy a file'.$file['name']);
            }

            $filesArray[] = $fileLocation.'/'.$file['name'];
        }
        
        return $filesArray;
    }
    
    /**
     * Trigger a website backup
     * 
     * @param array $params
     * @return array
     * @throws MultiSiteJsonException
     */
    public function triggerWebsiteBackup($params)
    {
        global $_ARRAYLANG;
        
        if (empty($params) || !isset($params['post'])) {
            \DBG::log(__METHOD__.' failed ! : '.$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_PARAMS']);
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_FAILED']);
        }
        
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode', 'MultiSite')) {
                case ComponentController::MODE_MANAGER:
                case ComponentController::MODE_HYBRID:
                    $deleteBackupedWebsiteName = isset($params['post']['websiteBackupFileName']) 
                                                 ? contrexx_input2raw($params['post']['websiteBackupFileName'])
                                                 : '';
                    $serviceServerId           = isset($params['post']['serviceServerId']) 
                                                 ? contrexx_input2int($params['post']['serviceServerId'])
                                                 : 0;

                    //delete the backuped website
                    if (!empty($deleteBackupedWebsiteName)) {
                        $resp = self::executeCommandOnManager(
                            'deleteWebsiteBackup', 
                            array(
                                'websiteBackupFileName' => $deleteBackupedWebsiteName,
                                'websiteServiceServerId'=> $serviceServerId
                                
                            )
                        );
                    } else {
                        $multisiteComponentController = $this->getComponent('Multisite');
                        if (!$multisiteComponentController) {
                            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_FAILED']);
                        }
                        $resp = $multisiteComponentController->executeCommandBackup($params['post']);
                    }
                    return ($resp && isset($resp->data)) ? $resp->data : $resp;
                    break;
                default:
                    break;
            }
        } catch (\Exception $e) {
            \DBG::log(__METHOD__.' Failed! : '. $e->getMessage());
            throw new MultiSiteJsonException($e->getMessage());
        }
    }
    
    /**
     * Trigger websiteRestore
     * 
     * @param array $params
     * @return array
     * @throws MultiSiteJsonException
     */
    public function triggerWebsiteRestore($params)
    {
        global $_ARRAYLANG;
        
        if (empty($params) || !isset($params['post'])) {
            \DBG::log(__METHOD__. ' failed! : '.$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_PARAMS']);
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_RESTORE_FAILED']);
        }
        
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode', 'MultiSite')) {
                case ComponentController::MODE_MANAGER:
                case ComponentController::MODE_HYBRID:
                    $multisiteComponentController = $this->getComponent('Multisite');
                    if (!$multisiteComponentController) {
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_RESTORE_FAILED']);
                    }
                    $resp = $multisiteComponentController->executeCommandRestore($params['post']);
                    return ($resp && isset($resp->data)) ? $resp->data : $resp;
                    break;
                default:
                    break;
            }
        } catch (\Exception $e) {
            \DBG::log(__METHOD__.' Failed! : '. $e->getMessage());
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_RESTORE_FAILED']);
        }
    }
    
    protected function activateDebuggingToMemory() {
        // check if memory logging shall be activated
        switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
            case ComponentController::MODE_MANAGER:
            case ComponentController::MODE_SERVICE:
            case ComponentController::MODE_HYBRID:
                // don't activate memory-logging if debug log shall not be sent to admin
                if (!\Cx\Core\Setting\Controller\Setting::getValue('sendSetupError','MultiSite')) {
                    return;
                }
                break;

            case ComponentController::MODE_WEBSITE:
                // always activate memory-logging for website mode
            default:
                break;
        }

        // activate memory-logging
        if (\DBG::getMode() & DBG_LOG_FILE || \DBG::getMode() & DBG_LOG_FIREPHP) {
            \DBG::deactivate(DBG_LOG_FILE | DBG_LOG_FIREPHP);
        }
        \DBG::deactivate(DBG_LOG);
        if (\DBG::getMode() ^ DBG_PHP || \DBG::getMode() ^ DBG_LOG_MEMORY) {
            \DBG::activate(DBG_PHP | DBG_LOG_MEMORY | DBG_LOG);
        }
    }
    
    /**
     * Create the mail service account
     * 
     * @param array $params
     * 
     * @return boolean
     * 
     * @throws MultiSiteJsonException
     */    
    public function createMailServiceAccount($params)
    {
        global $_ARRAYLANG;
        self::loadLanguageData();

        if (empty($params['post']['websiteId'])) {
            \DBG::log('JsonMultiSiteController::createMailServiceAccount() failed: Insufficient arguments supplied: ' . var_export($params, true));
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_EXISTS']);
        }

        try {
            // check the mode
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_MANAGER:
                case ComponentController::MODE_HYBRID:
                    $website = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website')
                                              ->findOneBy(array('id' => $params['post']['websiteId']));
                    if (!$website) {
                        \DBG::log('JsonException::createMailServiceAccount() failed: Unkown Website-ID: '.$params['post']['websiteId']);
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_EXISTS']);
                    }
                    $defaultMailServiceServer = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\MailServiceServer')
                                                               ->findOneBy(array('id' => \Cx\Core\Setting\Controller\Setting::getValue('defaultMailServiceServer','MultiSite')));

                    $res = $defaultMailServiceServer->createAccount($website);
                    $accountId = $res['subscriptionId'];
                    if ($accountId) {
                        $website->setMailAccountId($accountId);
                        \Env::get('em')->flush();
                        return array('status' => 'success', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_CREATED_MAIL_ACCOUNT_SUCCESSFULLY'], 'pwd' => $res['pwd']);
                    } 
                    break;
                case ComponentController::MODE_WEBSITE:
                case ComponentController::MODE_SERVICE:
                    // forward call to manager server. 
                    $response = self::executeCommandOnManager('createMailServiceAccount', array('websiteId' => $params['post']['websiteId']));
                    if ($response && $response->status == 'success' && $response->data->status == 'success') {
                        return array('status' => 'success', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_CREATED_MAIL_ACCOUNT_SUCCESSFULLY'], 'pwd' => $response->data->pwd);
                    }
                    break;
                default:
                    break;
            }
            return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_CREATE_MAIL_ACCOUNT_FAILED']);
        } catch (\Exception $ex) {
            \DBG::log('JsonMultiSiteController::createMailServiceAccount() failed: To create mail service server'. $ex->getMessage());
            return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_CREATE_MAIL_ACCOUNT_FAILED']);
        }
    }
    
    /**
     * Delete the mail service account
     * 
     * @param array $params
     * 
     * @return boolean
     * 
     * @throws MultiSiteJsonException
     */
    public function deleteMailServiceAccount($params)
    {
        global $_ARRAYLANG;
        self::loadLanguageData();
        
        if (empty($params['post']['websiteId'])) {
            \DBG::log('JsonMultiSiteController::deleteMailServiceAccount() failed: Insufficient arguments supplied: ' . var_export($params, true));
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_EXISTS']);
        }
        try {
            // check the mode
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_MANAGER:
                case ComponentController::MODE_HYBRID:
                    $website = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website')->findOneBy(array('id' => $params['post']['websiteId']));
                    if (!$website) {
                        \DBG::log('JsonException::deleteMailServiceAccount() failed: Unkown Website-ID: '.$params['post']['websiteId']);
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_EXISTS']);
                    }
                    
                    if (
                           self::validateWebsiteForMailService($website) 
                        && $website->getMailServiceServer()->deleteAccount($website->getMailAccountId())
                    ) {
                        //unmap domains which are related to mail service
                        foreach ($website->getDomains() as $domain) {
                            if (   $domain->getType() == \Cx\Core_Modules\MultiSite\Model\Entity\Domain::TYPE_MAIL_DOMAIN
                                || $domain->getType() == \Cx\Core_Modules\MultiSite\Model\Entity\Domain::TYPE_WEBMAIL_DOMAIN
                               ) {
                                $website->removeDomain($domain);
                                \Env::get('em')->remove($domain);
                            }
                        }
                        $website->getMailServiceServer()->removeWebsite($website);
                        $website->setMailAccountId(null);
                        $website->setMailServiceServer(null);
                        \Env::get('em')->flush();
                        return array('status' => 'success', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_DELETED_MAIL_ACCOUNT_SUCCESSFULLY']);
                    }                    
                case ComponentController::MODE_WEBSITE:
                case ComponentController::MODE_SERVICE:
                    // forward call to manager server. 
                    $response = self::executeCommandOnManager('deleteMailServiceAccount', array('websiteId' => $params['post']['websiteId']));
                    if ($response && $response->status == 'success' && $response->data->status == 'success') {
                        return array('status' => 'success', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_DELETED_MAIL_ACCOUNT_SUCCESSFULLY']);
                    }
                    break;
                default:
                    break;
            }
            return array(
                'status' => 'error', 
                'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_DELETE_MAIL_ACCOUNT_FAILED'],
                'log'    => \DBG::getMemoryLogs(),
            );
        } catch (\Exception $e) {
            throw new MultiSiteJsonException(
                array(
                    'log'       => \DBG::getMemoryLogs(),
                    'message'   => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_DELETE_MAIL_ACCOUNT_FAILED']. $e->getMessage()
                )
            );
        }
    }
    
    /**
     * Enable the mail service
     * 
     * @param array $params
     * 
     * @return boolean
     * @throws MultiSiteJsonException
     */    
    public function enableMailService($params)
    {
        global $_ARRAYLANG;
        self::loadLanguageData();

        if (empty($params['post']['websiteId'])) {
            \DBG::log('JsonMultiSiteController::enableMailService() failed: Insufficient arguments supplied: ' . var_export($params, true));
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_EXISTS']);
        }
               
        try {
            // check the mode
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_MANAGER:
                case ComponentController::MODE_HYBRID:
                    $website = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website')->findOneBy(array('id' => $params['post']['websiteId']));

                    if (!$website) {
                        \DBG::log('JsonException::enableMailService() failed: Unkown Website-ID: '.$params['post']['websiteId']);
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_EXISTS']);
                    }
                    $mailServiceServer = $website->getMailServiceServer();
                    if (!$mailServiceServer) {
                        $response = self::executeCommandOnManager('createMailServiceAccount', array('websiteId' => $params['post']['websiteId']));
                        if (!$response || $response->status == 'error' || $response->data->status == 'error') {
                            \DBG::log('JsonException::enableMailService() failed: Unable to create mail service account.');
                            throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_WEBSITE_MAIL_ENABLED_FAILED']);
                        }
                        $pwd = $response->data->pwd;
                        $mailServiceServer = $website->getMailServiceServer();
                    }
                    $hostingController = ComponentController::getMailServerHostingController($mailServiceServer);
                    $status = $hostingController->getMailServiceStatus($website->getMailAccountId());

                    if($status == 'true') {
                        \DBG::log('JsonMultiSiteController::enableMailService() failed: Mail service account was enabled.');
                         return array('status' => 'success', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_MAIL_ENABLED_SUCCESSFULLY'], 'pwd' => '');
                    } else {
                        if (
                               $mailServiceServer && $website->getMailAccountId()
                            && $mailServiceServer->enableService($website)
                        ) {
                            return array('status' => 'success', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_MAIL_ENABLED_SUCCESSFULLY'], 'pwd' => isset($pwd)?$pwd:'');
                        }
                    }
                    break;

                case ComponentController::MODE_WEBSITE:
                case ComponentController::MODE_SERVICE:
                    // forward call to manager server. 
                    $response = self::executeCommandOnManager('enableMailService', array('websiteId' => $params['post']['websiteId']));
                    if ($response && $response->status == 'success' && $response->data->status == 'success') {
                        return array('status' => 'success', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_MAIL_ENABLED_SUCCESSFULLY']);
                    }
                    break;
                default:
                    break;
            }
            return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_MAIL_ENABLED_FAILED']);
        } catch (\Exception $ex) {
            \DBG::log('JsonMultiSiteController::enableMailService() failed: To enable mail service account.'. $ex->getMessage());
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_WEBSITE_MAIL_ENABLED_FAILED']);
        }
    }
    
    /**
     * Disable the mail service
     * 
     * @param array $params
     * 
     * @return boolean
     * @throws MultiSiteJsonException
     */    
    public function disableMailService($params)
    {
        global $_ARRAYLANG;
        self::loadLanguageData();
        
        if (empty($params['post']['websiteId'])) {
            \DBG::log('JsonMultiSiteController::disableMailService() failed: Insufficient arguments supplied: ' . var_export($params, true));
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_EXISTS']);
        }
               
        try {
            // check the mode
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_MANAGER:
                case ComponentController::MODE_HYBRID:
                    $website = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website')->findOneBy(array('id' => $params['post']['websiteId']));
                    if (!$website) {
                        \DBG::log('JsonException::disableMailService() failed: Unkown Website-ID: '.$params['post']['websiteId']);
                        return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_EXISTS']);
                    }
                    
                    if (
                           self::validateWebsiteForMailService($website) 
                        && $website->getMailServiceServer()->disableService($website)
                    ) {
                        return array('status' => 'success', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_MAIL_DISABLED_SUCCESSFULLY']);
                    }
                    break;

                case ComponentController::MODE_WEBSITE:
                case ComponentController::MODE_SERVICE:
                    // forward call to manager server. 
                    $response = self::executeCommandOnManager('disableMailService', array('websiteId' => $params['post']['websiteId']));
                    if ($response && $response->status == 'success' && $response->data->status == 'success') {
                        return array('status' => 'success', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_MAIL_DISABLED_SUCCESSFULLY']);
                    }
                    break;
                default:
                    break;
            }
            return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_MAIL_DISABLED_FAILED']);
        } catch (\Exception $ex) {
            \DBG::log('JsonMultiSiteController::disableMailService() failed: To disable mail service'. $ex->getMessage());
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_WEBSITE_MAIL_DISABLED_FAILED']);
        }
    }
    
    /**
     * Reset the E-Mail Password
     * 
     * @param array $params
     * 
     * @return boolean
     * @throws MultiSiteJsonException
     */    
    public function resetEmailPassword($params)
    {
        global $_ARRAYLANG;
        self::loadLanguageData();
        
        if (empty($params['post']['websiteId'])) {
            \DBG::log('JsonMultiSiteController::resetEmailPassword() failed: Insufficient arguments supplied: ' . var_export($params, true));
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_EXISTS']);
        }
               
        try {
            // check the mode
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_MANAGER:
                case ComponentController::MODE_HYBRID:
                    $website = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website')->findOneBy(array('id' => $params['post']['websiteId']));
                    if (!$website) {
                        \DBG::log('JsonException::disableMailService() failed: Unkown Website-ID: '.$params['post']['websiteId']);
                        return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_EXISTS']);
                    }
                    $mailServiceServer = $website->getMailServiceServer();
                    if (!$mailServiceServer || \FWValidator::isEmpty($website->getMailAccountId())) {
                        throw new MultiSiteJsonException('JsonMultiSiteController::getPanelAutoLoginUrl() failed: Unkown mail service server.');
                    }

                    $hostingController = ComponentController::getMailServerHostingController($mailServiceServer);
                    
                    $password = \User::make_password(8, true);
                    if ($hostingController->changeMailAccountPassword($website->getMailAccountId(), $password)) {
                        return array(
                            'status'    => 'success',
                            'message'   => $_ARRAYLANG['TXT_MULTISITE_RESET_EMAIL_PASS_MSG'],
                            'password'  => $password,
                            'log'       => \DBG::getMemoryLogs(),
                        );
                    }
                    break;

                case ComponentController::MODE_WEBSITE:
                case ComponentController::MODE_SERVICE:
                    // forward call to manager server. 
                    $response = self::executeCommandOnManager('resetEmailPassword', array('websiteId' => $params['post']['websiteId']));
                    if ($response && $response->status == 'success' && $response->data->status == 'success') {
                        return array('status' => 'success', 'message' => $_ARRAYLANG['TXT_MULTISITE_RESET_EMAIL_PASS_MSG']);
                    }
                    break;
                default:
                    break;
            }
            return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_RESET_EMAIL_PASS_ERROR_MSG']);
        } catch (Exception $ex) {
            \DBG::log('JsonMultiSiteController::resetEmailPassword() failed: Updating E-Mail password.'. $ex->getMessage());
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_RESET_EMAIL_PASS_ERROR_MSG']);
        }
    }
    
    /**
     * Get the mail service status
     * 
     * @param array $params
     * 
     * @return string mail service status
     * @throws MultiSiteJsonException
     */    
    public function getMailServiceStatus($params)
    {
        global $_ARRAYLANG;
        self::loadLanguageData();
        
        if (empty($params['post']['websiteId'])) {
            \DBG::log('JsonMultiSiteController::getMailServiceStatus() failed: Insufficient arguments supplied: ' . var_export($params, true));
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_EXISTS']);
        }
               
        try {
            // check the mode
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_MANAGER:
                case ComponentController::MODE_HYBRID:
                    $website = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website')->findOneBy(array('id' => $params['post']['websiteId']));
                    if (!$website) {
                        \DBG::log('JsonException::getMailServiceStatus() failed: Unkown Website-ID: '.$params['post']['websiteId']);
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_EXISTS']);
                    }
                    $mailServiceServer = $website->getMailServiceServer();
                    if ($mailServiceServer && !\FWValidator::isEmpty($website->getMailAccountId())) {
                        $hostingController = ComponentController::getMailServerHostingController($mailServiceServer);

                        $status = $hostingController->getMailServiceStatus($website->getMailAccountId());
                        return array('status' => 'success', 'mailServiceStatus' => ($status == 'true') ? true : false);
                    }
                    break;

                case ComponentController::MODE_WEBSITE:
                case ComponentController::MODE_SERVICE:
                    // forward call to manager server. 
                    $response = self::executeCommandOnManager('getMailServiceStatus', array('websiteId' => $params['post']['websiteId']));
                    if ($response && $response->status == 'success' && $response->data->status == 'success') {
                        return array('status' => 'success', 'mailServiceStatus' => $response->data->mailServiceStatus);
                    }
                    break;
                default:
                    break;
            }
            return array('status' => 'error');
        } catch (\Exception $ex) {
            \DBG::log('JsonMultiSiteController::getMailServiceStatus() failed: To get mail service status.'. $ex->getMessage());
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_EXISTS']);
        }
    }
    
    /**
     * Validate the website to handle it by mail service server
     * 
     * @param \Cx\Core_Modules\MultiSite\Model\Entity\Website $website
     * 
     * @return boolean                True when website is has enough values to handled by mail service
     * @throws MultiSiteJsonException when webiste does not have proper values
     */
    public static function validateWebsiteForMailService(\Cx\Core_Modules\MultiSite\Model\Entity\Website $website) {
        
        $mailServiceServer = $website->getMailServiceServer();
        if (!$mailServiceServer) {
            throw new MultiSiteJsonException('JsonException failed: mail service server is not set.');
        }

        if (!$website->getMailAccountId()) {
            throw new MultiSiteJsonException('JsonException failed: mail account id is not set.');
        }
        
        return true;
    }
    
    /**
     * Get website remote login url
     * 
     * @param array $params websiteId
     * 
     * @return mixed Get website login url or false on website owner id is different from logged user
     */
    public function websiteLogin($params) {
        if (empty($params['post']['websiteId'])) {
            throw new MultiSiteJsonException('JsonMultiSiteController::websiteLogin() failed: Insufficient arguments supplied: ' . var_export($params, true));
        }
        
        $website = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website')->findOneBy(array('id' => $params['post']['websiteId']));
        if (!$website) {
            throw new MultiSiteJsonException('JsonMultiSiteController::websiteLogin() failed: Unkown Website-ID: '.$params['post']['websiteId']);
        }
        
        $userId = \FWUser::getFWUserObject()->objUser->getId();
        if ($website->getOwner()->getId() == $userId) {
            return $this->remoteLogin(array('post' => array('websiteId' => $params['post']['websiteId'])));
        }
        
        return false;
    }
    
    /**
     * get the website admin users and backend group users
     * 
     * @return array users
     */
    public function getAdminUsers() 
    {
        try {
            $adminUsers         = ComponentController::getAllAdminUsers();            
            $objEntityInterface = new \Cx\Core_Modules\Listing\Model\Entity\EntityInterface();
            
            $objDataSet = \Cx\Core_Modules\Listing\Model\Entity\DataSet::import($objEntityInterface, $adminUsers);
            
            if (!\FWValidator::isEmpty($objDataSet)) {                
                return array('status' => 'success', 'users' => $objDataSet->toArray());
            }
            return array('status' => 'error');
        } catch (\Exception $e) {
            throw new MultiSiteJsonException('JsonMultiSiteController::getAdminUsers() failed: To get the Admin users' . $e->getMessage());
        }
    }
    
    /**
     * get user by id
     * 
     * @param  array $params supplied arguments from JsonData-request
     * 
     * @return array
     */
    public function getUser($params){

        $userId = isset($params['post']['id']) ? contrexx_input2raw($params['post']['id']) : '';
        if (\FWValidator::isEmpty($userId)) {
            return;
        }
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_WEBSITE:
                    $userRepo = \Env::get('em')->getRepository('Cx\Core\User\Model\Entity\User');
                    $user = $userRepo->findBy(array('id' => $userId));

                    $objEntityInterface = new \Cx\Core_Modules\Listing\Model\Entity\EntityInterface();

                    $objDataSet = \Cx\Core_Modules\Listing\Model\Entity\DataSet::import($objEntityInterface, $user);

                    if (!\FWValidator::isEmpty($objDataSet)) {
                        return array('status' => 'success', 'user' => $objDataSet->toArray());
                    }
                    return array('status' => 'error');
                    break;
            }
        } catch (\Exception $e) {
            throw new MultiSiteJsonException('JsonMultiSiteController::getUser() failed: To get the user' . $e->getMessage());
        }
    }

    /**
     * This function used to get the resource usage stats
     * 
     * @return array resource usage stats 
     * @throws MultiSiteJsonException
     */
    
    public function getResourceUsageStats() {
        global $_ARRAYLANG, $_CONFIG;
        self::loadLanguageData();
        
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_WEBSITE:
                    $resourcesUsage = array();
                    $modulesArray = array(
                        'Access' => 'AdminUser',
                        'Contact' => 'Form',
                        'Shop' => 'Product',
                        'Crm' => 'Customer'
                    );

                    foreach ($modulesArray as $module => $type) {
                        switch ($module) {
                            case 'Access':
                                $usage = count(ComponentController::getAllAdminUsers());
                                break;
                            
                            case 'Contact':
                                $forms = \Env::get('em')->getRepository('Cx\Core_Modules\Contact\Model\Entity\Form')->findAll();
                                $usage = count($forms);
                                break;

                            case 'Shop':
                                $pagingLimitBkp = $_CONFIG['corePagingLimit'];
                                $_CONFIG['corePagingLimit'] = 10000;
                                $count = 0;
                                $products = \Cx\Modules\Shop\Controller\Products::getByShopParams($count, 0, null, null, null, null, false, false, null, null, true);
                                $_CONFIG['corePagingLimit'] = $pagingLimitBkp;
                                $usage = count($products);
                                break;

                            case 'Crm':
                                $objCrm = new \Cx\Modules\Crm\Controller\CrmManager('crm');
                                $query = $objCrm->getContactsQuery(array('contactSearch' => 2));
                                $usage = $objCrm->countRecordEntries($query);
                                break;
                        }
                        
                        $quotaResult = ComponentController::getModuleAdditionalDataByType($module);
                        
                        $resourceUsageStats[lcfirst($module) . $type] = array(
                            'usage' => $usage ? $usage : 0,
                            'quota' => !empty($quotaResult[$type]) ? $quotaResult[$type] : $_ARRAYLANG['TXT_MULTISITE_UNLIMITED']
                        );
                    }

                    return array('status' => 'success', 'resourceUsageStats' => $resourceUsageStats);
                    break;
            }
            return array('status' => 'error');
        } catch (\Exception $e) {
            throw new MultiSiteJsonException('JsonMultiSiteController::getResourceUsageStats() failed: To get the website Resource Usage Stats' . $e->getMessage());
        }
    }

    /**
     * Create a new auto-login url for Plesk.
     * 
     * @param array $params
     * 
     * @return array login stats
     * @throws MultiSiteJsonException
     */
    public function getPanelAutoLoginUrl($params)
    {
        global $_ARRAYLANG;
        if (\FWValidator::isEmpty($params['post']['websiteId'])) {
            \DBG::msg(__METHOD__ . ' failed: Insufficient arguments supplied:');
            \DBG::dump($params);
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_INVALID_CALL']);
        }
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_MANAGER:
                    $website = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website')->findOneBy(array('id' => $params['post']['websiteId']));
                    if (!$website) {
                        \DBG::msg(__METHOD__ . ' failed: Unkown Website-ID: ' . $params['post']['websiteId']);
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_UNKNOWN_WEBSITE']);
                    }
                    if ($website->getOwner()->getId() != \FWUser::getFWUserObject()->objUser->getId()) {
                        return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_MULTISITE_USER']);
                    }
                    $mailServiceServer = $website->getMailServiceServer();
                    if (!$mailServiceServer || \FWValidator::isEmpty($website->getMailAccountId())) {
                        \DBG::msg(__METHOD__ . ' failed: Unkown mail service server.');
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_UNKOWN_MAIL_SERVICE_SERVER']);
                    }

                    $clientIp = !\FWValidator::isEmpty($_SERVER['HTTP_X_FORWARDED_FOR']) ? trim(end(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']))) : $_SERVER['REMOTE_ADDR'];
                    $hostingController = ComponentController::getMailServerHostingController($mailServiceServer);

                    $pleskLoginUrl = $hostingController->getPanelAutoLoginUrl($website->getMailAccountId(), $clientIp, ComponentController::getApiProtocol() . \Cx\Core\Setting\Controller\Setting::getValue('customerPanelDomain','MultiSite'));
                    
                    if ($pleskLoginUrl) {
                        return array('status' => 'success', 'autoLoginUrl' => $pleskLoginUrl);
                    }
                    return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_LOGIN_PLESK_FAILED']);
                    break;

                default:
                    break;
            }
        } catch (\Exception $e) {
            \DBG::msg(__METHOD__ . ' failed: ' . $e->getMessage());
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_WEBSITE_LOGIN_PLESK_FAILED']);
        }
    }

    /**
     * Get auto-login url for Payrexx.
     * 
     * @return array Payrexx auto-login url stats
     * 
     */
    public function payrexxAutoLoginUrl()
    {
        global $_ARRAYLANG;
        
        if (!ComponentController::isUserLoggedIn()) {
            return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_LOGIN_NOACCESS']);
        }

        $crmContactId = \FWUser::getFWUserObject()->objUser->getCrmUserId();
        if (\FWValidator::isEmpty($crmContactId)) {
            return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_MULTISITE_USER']);
        }

        $profileAttributeId = \Cx\Core\Setting\Controller\Setting::getValue('externalPaymentCustomerIdProfileAttributeId','MultiSite');
        if (\FWValidator::isEmpty($profileAttributeId)) {
            return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_PAYREXX_LOGIN_FAILED']);
        }

        $userId = \FWUser::getFWUserObject()->objUser->getProfileAttribute($profileAttributeId);
        if (\FWValidator::isEmpty($userId)) {
            return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_PAYREXX_LOGIN_FAILED']);
        }

        $instanceName = \Cx\Core\Setting\Controller\Setting::getValue('payrexxAccount','MultiSite');
        $apiSecret = \Cx\Core\Setting\Controller\Setting::getValue('payrexxApiSecret','MultiSite');
        
        try {   
            $payrexx = new \Payrexx\Payrexx($instanceName, $apiSecret);
            $authToken = new \Payrexx\Models\Request\AuthToken();
            $authToken->setUserId($userId);
        
            $response = $payrexx->create($authToken);

            if (   !\FWValidator::isEmpty($response)
                && $response instanceof \Payrexx\Models\Response\AuthToken
                && !\FWValidator::isEmpty($response->getLink())
            ) {
                return array('status' => 'success', 'autoLoginUrl' => $response->getLink());
            }
            return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_PAYREXX_LOGIN_FAILED']);
        } catch (\Payrexx\PayrexxException $e) {
            \DBG::log('JsonMultiSiteController::payrexxAutoLoginUrl() failed:' . $e->getMessage());
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_WEBSITE_PAYREXX_LOGIN_FAILED']);
        }
    }
    
    /**
     * Get the main domain of the Website
     * 
     * @return string main domain of the website
     * @throws MultiSiteJsonException
     */
    public function getMainDomain() {
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_WEBSITE:
                    $domainRepository = new \Cx\Core\Net\Model\Repository\DomainRepository();
                    $mainDomain       = $domainRepository->getMainDomain()->getName();
                    if (!\FWValidator::isEmpty($mainDomain)) {
                        return array('status' => 'success', 'mainDomain' => $mainDomain);
                    }
                break;
            }
            return array('status' => 'error');       
        } catch (\Exception $e) {
            \DBG::log('JsonMultiSiteController::getMainDomain() failed:' . $e->getMessage());
            throw new MultiSiteJsonException('JsonMultiSiteController::getMainDomain() failed: to get the main domain');
        }
        
    }
    
    /**
     * Delete the website Account
     * 
     * @return array
     */
    public function deleteAccount() 
    {
        global $_ARRAYLANG;
        
        try {
            if (!ComponentController::isUserLoggedIn()) {
            \DBG::log($_ARRAYLANG['TXT_MULTISITE_WEBSITE_LOGIN_NOACCESS']);
            return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_USER_ACCOUNT_DELETE_FAILED']);
            }

            $crmContactId = \FWUser::getFWUserObject()->objUser->getCrmUserId();
            if (\FWValidator::isEmpty($crmContactId)) {
                \DBG::log($_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_MULTISITE_USER']);
                return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_USER_ACCOUNT_DELETE_FAILED']);
            }

            $orders = \Env::get('em')->getRepository('Cx\Modules\Order\Model\Entity\Order')->findBy(array('contactId' => $crmContactId));
            if (\FWValidator::isEmpty($orders)) {
                \DBG::log('Their is no order for this user.');
                return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_USER_ACCOUNT_DELETE_FAILED']);
            }
            
            foreach ($orders as $order) {
                $subscriptions = $order->getSubscriptions();
                if (\FWValidator::isEmpty($subscriptions)) {
                    continue;
                }
                foreach ($subscriptions as $subscription) {

                    if ($subscription->getState() != \Cx\Modules\Order\Model\Entity\Subscription::STATE_TERMINATED) {
                        $subscription->terminate();
                    }
                    
                    $productEntity = $subscription->getProductEntity();
                    if (!\FWValidator::isEmpty($productEntity)) {
                        \Env::get('em')->remove($productEntity);
                    }
                }
            }
            
            \Env::get('em')->flush();
            
            //delete currently sign-in user
            if (!\FWUser::getFWUserObject()->objUser->delete(true)) {
                \DBG::log('Failed to delete the user account');
                return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_USER_ACCOUNT_DELETE_FAILED']);
            }
            
            $marketingWebsiteUrl = ComponentController::getApiProtocol().\Cx\Core\Setting\Controller\Setting::getValue('marketingWebsiteDomain','MultiSite');
            return array('status' => 'success', 'marketingWebsiteUrl' => $marketingWebsiteUrl);
        } catch (\Exception $e) {
             \DBG::log('JsonMultiSiteController::deleteAccount() failed:' . $e->getMessage());
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_WEBSITE_USER_ACCOUNT_DELETE_FAILED']);
        }
        
    }
    
    /**
     * Set the Main Domain
     * 
     * @param  array  $params
     * @return string status
     * @throws MultiSiteJsonException
     */
    public function setMainDomain($params) {
        global $_ARRAYLANG, $_CONFIG;
        self::loadLanguageData();
        
        if (empty($params['post']) || !isset($params['post']['mainDomainId'])) {
            \DBG::log('JsonMultiSiteController::setMainDomain() failed: mainDomainId is empty');
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_UNKNOWN']);
        }
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_WEBSITE:
                    \Cx\Core\Setting\Controller\Setting::init('Config', 'site', 'Yaml');
                    \Cx\Core\Setting\Controller\Setting::set('mainDomainId', $params['post']['mainDomainId']);
                    \Cx\Core\Setting\Controller\Setting::update('mainDomainId');
                    if ($_CONFIG['xmlSitemapStatus'] == 'on') {
                        \Cx\Core\PageTree\XmlSitemapPageTree::write();
                    }
                    return array(
                        'status' => 'success'
                    );
                    break;
            }
        } catch (\Exception $e) {
            \DBG::dump('JsonMultiSiteController::setMainDomain() failed:'. $e->getMessage());
            throw new MultiSiteJsonException('JsonMultiSiteController::setMainDomain() failed:'. $e->getMessage());
        }
    }
    
    /**
     * Create|Delete|Rename domain alias or rename subscription in plesk.
     * 
     * @param array $params
     * 
     * @return array
     * @throws MultiSiteJsonException
     */
    public function domainManipulation($params)
    {
        global $_ARRAYLANG;
        self::loadLanguageData();
        
        if (   \FWValidator::isEmpty($params['post']) 
            || \FWValidator::isEmpty($params['post']['websiteName'])
            || \FWValidator::isEmpty($params['post']['command'])
            || \FWValidator::isEmpty($params['post']['domainName'])
        ) {
            \DBG::log('JsonMultiSiteController::domainManipulation() failed: Insufficient arguments supplied: ' . var_export($params, true));
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_EXISTS']);
        }
        
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_MANAGER:
                case ComponentController::MODE_HYBRID:
                    $websiteRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
                    $website = $websiteRepo->findOneBy(array('name' => $params['post']['websiteName']));
                    $methodName = $params['post']['command'];
                    
                    if (!$website) {
                        \DBG::log('JsonMultiSiteController::domainManipulation() failed: Unknown website.');
                        return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_NOT_EXISTS']);
                    }
        
                    // Update domain aliases accordingly
                    try {
                        $aliasParams = $params['post'];
                        $aliasParams['command'] = str_replace(
                            'DomainAlias',
                            'WebDistributionAlias',
                            $params['post']['command']
                        );
                        static::executeCommandOnServiceServer(
                            'updateWebDistributionAlias',
                            $params['post'],
                            $website->getWebsiteServiceServer()
                        );
                    } catch (\Exception $e) {
                        \DBG::log('SYSERROR: Could not update WebDistributionAlias while executing "' . $methodName . '" for website "' . $website->getName() . '"!');
                        throw $e;
                    }

                    // automatically issue self-signed certificate (if supported)
                    try {
                        $certParams = array(
                            'event' => $methodName,
                            'websiteName' => $website->getName(),
                            'domainName' => $params['post']['domainName'],
                        );
                        if (!empty($params['post']['oldDomainName'])) {
                            $certParams['oldDomainName'] = $params['post']['oldDomainName'];
                        }
                        $result = static::executeCommandOnServiceServer(
                            'updateAutomaticCertificates',
                            $certParams,
                            $website->getWebsiteServiceServer()
                        );
                    } catch (\Exception $e) {
                        \DBG::log('SYSERROR: Could not update self-signed certificates while executing "' . $methodName . '" for website "' . $website->getName() . '"!');
                        throw $e;
                    }

                    try {
                        $mailServiceServer = $website->getMailServiceServer();
                        if ( !\FWValidator::isEmpty($mailServiceServer) && !\FWValidator::isEmpty($website->getMailAccountId())) {
                            $hostingController = ComponentController::getMailServerHostingController($mailServiceServer);
                        
                            if (!$hostingController) {
                                \DBG::msg('Failed to get the hosting controller.');
                                throw new MultiSiteJsonException('JsonMultiSiteController::domainManipulation(): Failed to process the method '.$params['post']['command'] . '().');
                            }
                            if ($hostingController instanceof \Cx\Core_Modules\MultiSite\Controller\PleskController) {
                                // webspaceId is a special feature of PleskController
                                // TODO: eliminate webspaceId of PleskController
                                $hostingController->setWebspaceId($website->getMailAccountId());
                            }
                            $methodName = $params['post']['command'];
                            switch ($methodName) {
                                case 'renameDomainAlias':
                                    if (!$hostingController->$methodName($params['post']['oldDomainName'], $params['post']['domainName'])) {
                                        \DBG::msg('Failed to process the method renameDomainAlias().');
                                        throw new MultiSiteJsonException('JsonMultiSiteController::domainManipulation(): Failed to process the method '.$params['post']['command'] . '().');
                                    }
                                    break;
                                case 'deleteDomainAlias':
                                case 'createDomainAlias':
                                    if (!$hostingController->$methodName($params['post']['domainName'])) {
                                        \DBG::msg('Failed to process the method '.$params['post']['command'] . '().');
                                        throw new MultiSiteJsonException('JsonMultiSiteController::domainManipulation(): Failed to process the method '.$params['post']['command'] . '().');
                                    }
                                    break;
                                default :
                                    break;
                            }
                        }
                    } catch (\Exception $e) {
                        \DBG::log('SYSERROR: Could not update mail distribution while executing "' . $methodName . '" for website "' . $website->getName() . '"!');
                        throw $e;
                    }
                    return array('status' => 'success');
                    break;
                    
                case ComponentController::MODE_SERVICE:
                    // forward call to manager server.                     
                    $response = self::executeCommandOnManager('domainManipulation', $params['post']);
                    if ($response && $response->status == 'success' && $response->data->status == 'success') {
                        return array('status' => 'success');
                    }
                    break;
                    
                case ComponentController::MODE_WEBSITE:
                    // forward call to manager server.                     
                    $response = self::executeCommandOnMyServiceServer('domainManipulation', $params['post']);
                    if ($response && $response->status == 'success' && $response->data->status == 'success') {
                        return array('status' => 'success');
                    }
                    break;
                    
                default :
                    break;
            }
            return array('status' => 'error', 'message' => 'JsonMultiSiteController::domainManipulation(): Failed to process the method '.$params['post']['command'] . '().');
        } catch (\Exception $e) {
            \DBG::dump('JsonMultiSiteController::domainManipulation() failed:'. $e->getMessage());
            throw new MultiSiteJsonException('JsonMultiSiteController::domainManipulation() failed:');
        }
    }
    
    /**
     * Updates self-signed certificates for a domain if this is supported by
     * this service server
     * Only executable on service server
     * @todo Handle missing cases
     * @param mixed $params
     * @throws MultiSiteJsonException When executed on a non-service-server
     */
    public function updateAutomaticCertificates($params) {
        if (
            \Cx\Core\Setting\Controller\Setting::getValue(
                'mode',
                'MultiSite'
            ) !== ComponentController::MODE_SERVICE
        ) {
            throw new MultiSiteJsonException(
                'This method can only be used on service server'
            );
        }
        $hostingController = ComponentController::getHostingController();
        if (!$hostingController->canGenerateCertificates()) {
            return array(
                'status' => 'success',
                'comment' => 'auto-certs not supported',
            );
        }

        if (
            empty($params['post']['event']) ||
            empty($params['post']['domain'])
        ) {
            throw new MultiSiteJsonException(
                'Not all necessary params set!'
            );
        }
        try {
            switch ($params['post']['event']) {
                case 'createDomainAlias':
                    return static::executeCommand(
                        'linkSsl',
                        array(
                            'post' => array(
                                'websiteName' => $params['post']['website'],
                                'domainName' => $params['post']['domain'],
                            ),
                        )
                    );
                    break;
                case 'renameDomainAlias':
                    //SslController::removeSslCertificates();
                    //SslController::activateSslCertificate();
                    // do both of the above
                    // is this case ever used?
                    break;
                case 'deleteDomainAlias':
                    return static::executeCommand(
                        'unLinkSsl',
                        array(
                            'post' => array(
                                'websiteName' => $params['post']['website'],
                                'domainName' => $params['post']['domain'],
                            ),
                        )
                    );
                    break;
                case 'renameMailDistribution':
                    // ?
                    break;
            }
        } catch (\Exception $e) {
            throw $e;
        }
        return array(
            'status' => 'success',
            'comment' => 'nothing to do',
        );
    }
    
    /**
     * Get Module AdditionalData
     * 
     * @param array $params
     * 
     * @return array
     * @throws MultiSiteJsonException
     */
    public function getModuleAdditionalData($params) {
        if (\FWValidator::isEmpty($params['post']) || \FWValidator::isEmpty($params['post']['moduleName'])) {
            throw new MultiSiteJsonException('JsonMultiSiteController::getModuleAdditionalData() failed: Insufficient arguments supplied: ' . var_export($params, true));
        }
        
        $moduleName     = isset($params['post']['moduleName']) ? $params['post']['moduleName'] : '';
        $additionalType = isset($params['post']['additionalType']) ? $params['post']['additionalType'] : '';
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_WEBSITE:
                    $additionalData = ComponentController::getModuleAdditionalDataByType($moduleName, $additionalType);
                    if (\FWValidator::isEmpty($additionalData)) {
                        return array('status' => 'error'); 
                    }
                    return array('status' => 'success', 'additionalData' => $additionalData);
                    break;
                default :
                    break;
            }
            
        } catch (\Exception $e) {
            \DBG::dump('JsonMultiSiteController::getModuleAdditionalData() failed:'. $e->getMessage());
            throw new MultiSiteJsonException('JsonMultiSiteController::getModuleAdditionalData() failed: Failed to get the Module additional data.');
        }
    }
    
    /**
     * Change Plan Of MailSubscription
     * 
     * @param array $params
     * 
     * @return array
     * @throws MultiSiteJsonException
     */
    public function changePlanOfMailSubscription($params)
    {
        if (   \FWValidator::isEmpty($params['post']) 
            || \FWValidator::isEmpty($params['post']['planId'])
            || \FWValidator::isEmpty($params['post']['websiteId'])
        ) {
            throw new MultiSiteJsonException('JsonMultiSiteController::changePlanOfMailSubscription() failed: Insufficient arguments supplied: ' . var_export($params, true));
        }
        
        $planId = isset($params['post']['planId']) ? $params['post']['planId'] : '';
        
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_MANAGER:
                case ComponentController::MODE_HYBRID:
                    $website = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website')->findOneBy(array('id' => $params['post']['websiteId']));
                    if (\FWValidator::isEmpty($website)) {
                        \DBG::log('Unkown Website-ID: '.$params['post']['websiteId']);
                        return array('status' => 'error');
                    }
                    
                    $mailServiceServer = $website->getMailServiceServer();
                    $mailAccountId     = $website->getMailAccountId();
                    if (\FWValidator::isEmpty($mailServiceServer) || \FWValidator::isEmpty($mailAccountId)) {
                        \DBG::log('Mailserver/Mail account not valid.');
                        return array('status' => 'error');
                    }
                    
                    $hostingController = ComponentController::getMailServerHostingController($mailServiceServer);
                    
                    if (!$hostingController) {
                        \DBG::log('Failed to Fetch the hosting controller.');
                        return array('status' => 'error');
                    }
                    
                    if (!$hostingController->changeMailDistributionPlan($mailAccountId, $planId)) {
                        \DBG::log('Failed to change plan of the subscription');
                        return array('status' => 'error');
                    }
                    return array('status' => 'success');
                    break;
                    
                case ComponentController::MODE_SERVICE:
                    $response = self::executeCommandOnManager('changePlanOfMailSubscription', $params['post']);                
                    break;
                    
                case ComponentController::MODE_WEBSITE:
                    $response = self::executeCommandOnMyServiceServer('changePlanOfMailSubscription', $params['post']);              
                    break;
                default :
                    break;
            }
            if ($response && $response->status == 'success' && $response->data->status == 'success') {
                return array('status' => 'success');
            }
            return array('status' => 'error');
        } catch (\Exception $e) {
            \DBG::log('JsonMultiSiteController::changePlanOfMailSubscription() failed:'. $e->getMessage());
            throw new MultiSiteJsonException('JsonMultiSiteController::changePlanOfMailSubscription() failed: Failed to change the plan of mail subscription.');
        }
    }
    
    /**
     * Check whether the website component is licensed or not.
     * 
     * @param  array  $params
     * @return string $status
     * @throws MultiSiteJsonException
     */
    public function isComponentLicensed($params) 
    {
        if (empty($params['post']) || !isset($params['post']['component'])) {
            \DBG::log('JsonMultiSiteController::isComponentLicensed() failed: component is empty');
            throw new MultiSiteJsonException('Unknown component requested.');
        }
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_WEBSITE:
                    if((in_array($params['post']['component'], $this->cx->getLicense()->getLegalComponentsList()))) {
                        return array('status' => 'success');
                    }
                    break;
            }
            return array('status' => 'error');
        } catch (\Exception $e) {
            \DBG::dump('JsonMultiSiteController::isComponentLicensed() failed:'. $e->getMessage());
            throw new MultiSiteJsonException('JsonMultiSiteController::isComponentLicensed() failed:'. $e->getMessage());
        }
    }
    
    /**
     * Check whether the domain name is subdomain of multisite domain or not.
     * 
     * @param string $domainName 
     * @return boolean
     */
    public function isDomainASubDomainOfMultisiteDomain($domainName) {
        if (empty($domainName)) {
            return false;
        }
        
        $multiSiteDomain = \Cx\Core\Setting\Controller\Setting::getValue('multiSiteDomain','MultiSite');
        if (preg_match('/'.preg_quote('.'.$multiSiteDomain, '/').'$/i', $domainName)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Set Affiliate to the customer.
     * 
     * @param array $params
     * 
     * @return array
     * @throws MultiSiteJsonException
     */
    public function setAffiliate($params)
    {
        global $_ARRAYLANG;
        
        if (   empty($params['post']) 
            || (   empty($params['post']['affiliateProfileAttributeId'])
                && empty($params['post']['paypalEmailAddress']))
            ) {
            \DBG::msg('JsonMultiSiteController::setAffiliate() failed: Insufficient arguments supplied: ' . var_export($params, true));
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_AFFILIATE_ID_ERROR']);
        }
        
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_MANAGER:
                case ComponentController::MODE_HYBRID:
                    $objUser = \FWUser::getFWUserObject()->objUser;
                    if (isset($params['post']['affiliateProfileAttributeId']) && !empty($params['post']['affiliateProfileAttributeId'])) {
                        $affiliateIdProfileAttributeId = \Cx\Core\Setting\Controller\Setting::getValue('affiliateIdProfileAttributeId','MultiSite');
                        $affiliateId = $objUser->getProfileAttribute($affiliateIdProfileAttributeId);
                        if (!empty($affiliateId)) {
                            \DBG::msg('JsonMultiSiteController::setAffiliate() failed: Already the AffiliateId value present.');
                            return array('status' => 'error', 'type' => 'affiliateId', 'message' => $_ARRAYLANG['TXT_MULTISITE_AFFILIATE_ID_SET_ALREADY']);
                        }

                        $affiliateIdValue = $params['post']['affiliateProfileAttributeId'];
                        if (!self::validateAffiliateId($affiliateIdValue)) {
                            return array('status' => 'error', 'type' => 'affiliateId', 'message' => $_ARRAYLANG['TXT_MULTISITE_AFFILIATE_ID_ERROR']);
                        }
                        $objUser->setProfile(
                            array(
                                $affiliateIdProfileAttributeId => array(0 => $affiliateIdValue)
                            ),
                            true
                        );
                    }
                    if (isset($params['post']['paypalEmailAddress']) && !empty($params['post']['paypalEmailAddress'])) {
                        $paypalEmailAddress = $params['post']['paypalEmailAddress'];
                        if (!\FWValidator::isEmail($paypalEmailAddress)) {
                            return array('status' => 'error', 'type' => 'mail',  'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_INVALID_EMAIL']);                        
                        }
                        $paypalEmailAddressProfileAttribute = \Cx\Core\Setting\Controller\Setting::getValue('payPalProfileAttributeId','MultiSite');
                        $objUser->setProfile(
                            array(
                                $paypalEmailAddressProfileAttribute => array(0 => $paypalEmailAddress)
                            ),
                            true
                        );
                    }
                    if (!$objUser->store()) {
                        \DBG::msg('JsonMultiSiteController::setAffiliate() failed: can not store the AffiliateId.');
                        return array('status' => 'error', 'type' => 'affiliateId', 'message' => $_ARRAYLANG['TXT_MULTISITE_AFFILIATE_ID_ERROR']);
                    }
                    return array('status' => 'success', 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_USER_PROFILE_UPDATED_SUCCESS']);
                    break;
                default :
                    break;
            }
        } catch (\Exception $e) {
            \DBG::msg('JsonMultiSiteController::setAffiliate() failed: ' . $e->getMessage());
            throw new MultiSiteJsonException($e->getMessage());
        }        
    }
    
    /**
     * Check availability of AffiliateId
     * 
     * @param array $params
     * 
     * @return array
     * @throws MultiSiteJsonException
     */    
    public function checkAvailabilityOfAffiliateId($params)
    {
        global $_ARRAYLANG;
        
        if (empty($params['post']) 
            || !isset($params['post']['affiliateProfileAttributeId'])
        ) {
            \DBG::msg('JsonMultiSiteController::checkAvailabilityOfAffiliateId() failed: Insufficient arguments supplied: ' . var_export($params, true));
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_MULTISITE_AFFILIATE_ID_ERROR']);
        }
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_MANAGER:
                case ComponentController::MODE_HYBRID:
                    $affiliateId = $params['post']['affiliateProfileAttributeId'];
                    if (self::validateAffiliateId($affiliateId)) {
                        return array('status' => 'success');
                    }
                    break;
                default:
                    break;
            }
            return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_MULTISITE_AFFILIATE_ID_ERROR']);
        } catch (\Exception $e) {
            \DBG::msg('JsonMultiSiteController::checkAvailabilityOfAffiliateId() failed:' . $e->getMessage());
            throw new MultiSiteJsonException($e->getMessage());
        }
    }
    
    /**
     * Validate the affiliateId
     * 
     * @param string $affiliateId
     * 
     * @return boolean
     * @throws MultiSiteJsonException
     */
    public static function validateAffiliateId($affiliateId)
    {
        global $_ARRAYLANG;
        if (empty($affiliateId)) {
            \DBG::msg('JsonMultiSiteController::validateAffiliateId() failed: Insufficient arguments supplied.');
            return false;
        } 
        if (ComponentController::isValidAffiliateId($affiliateId)) {
            \DBG::msg('JsonMultiSiteController::validateAffiliateId() failed: AffiliateId not available.');
            throw new MultiSiteException($_ARRAYLANG['TXT_MULTISITE_AFFILIATE_ID_NOT_AVAILABLE']);
        }
        // verify that affiliateId complies with naming scheme
        if (preg_match('/[^a-z0-9]/', $affiliateId)) {
            \DBG::msg('JsonMultiSiteController::validateAffiliateId() failed: AffiliateId must contain only lowercase letters (a-z) and numbers.');
            throw new MultiSiteException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_AFFILIATE_ID_WRONG_CHARS']);
        }
        if (strlen($affiliateId) < \Cx\Core\Setting\Controller\Setting::getValue('websiteNameMinLength','MultiSite')) {
            \DBG::msg('JsonMultiSiteController::validateAffiliateId() failed: Affiliate-ID too shorter.');
            throw new MultiSiteException(sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_AFFILIATE_ID_TOO_SHORT'], \Cx\Core\Setting\Controller\Setting::getValue('websiteNameMinLength','MultiSite')));
        }
        if (strlen($affiliateId) > \Cx\Core\Setting\Controller\Setting::getValue('websiteNameMaxLength','MultiSite')) {
            \DBG::msg('JsonMultiSiteController::validateAffiliateId() failed: Affiliate-ID too longer.');
            throw new MultiSiteException(sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_AFFILIATE_ID_TOO_LONG'], \Cx\Core\Setting\Controller\Setting::getValue('websiteNameMaxLength','MultiSite')));
        }
        // verify that AffiliateId is not a blocked word
        $unavailablePrefixesValue = explode(',',\Cx\Core\Setting\Controller\Setting::getValue('unavailablePrefixes','MultiSite'));
        if (in_array($affiliateId, $unavailablePrefixesValue)) {
            \DBG::msg('JsonMultiSiteController::validateAffiliateId() failed: AffiliateId is a blocked word.');
            throw new MultiSiteException($_ARRAYLANG['TXT_MULTISITE_AFFILIATE_ID_BLOCKED']);
        }
        return true;
    }
    
    /**
     * Send the notification mail for payout request
     * 
     * @global array $_ARRAYLANG
     * 
     * @return array
     * @throws MultiSiteJsonException
     */
    public function sendNotificationForPayoutRequest() {
        global $_ARRAYLANG;
        
        try {
            $config = \Env::get('config');
            $objUser = \FWUser::getFWUserObject()->objUser;
            
            //Get the balance amount
            $affiliateCreditRepo = \Env::get('em')->getRepository('\Cx\Core_Modules\MultiSite\Model\Entity\AffiliateCredit');        
            $affiliateTotalCreditAmount = $affiliateCreditRepo->getTotalCreditsAmountByUser($objUser);
            // pay out all affiliate commissions in CHF (=> currently default currency)
            $currencyId = \Cx\Modules\Crm\Controller\CrmLibrary::getDefaultCurrencyId();
            $currencyObj  = \Env::get('em')->getRepository('\Cx\Modules\Crm\Model\Entity\Currency')->findOneById($currencyId);
            $currencyCode = $currencyObj ? $currencyObj->getName() : '';
            
            \Cx\Core\MailTemplate\Controller\MailTemplate::init('MultiSite');
            \Cx\Core\MailTemplate\Controller\MailTemplate::send(array(
                'section' => 'MultiSite',
                'key'     => 'payout_request',
                'to'      => $config['coreAdminEmail'],
                'search'  => array(
                    '[[USER_NAME]]',
                    '[[CREDIT_BALANCE]]',
                    '[[USER_EMAIL]]'
                ),
                'replace' => array(
                    \FWUser::getParsedUserTitle($objUser->getId()),
                    $affiliateTotalCreditAmount . ' ' . $currencyCode,
                    $objUser->getEmail()
                ),
            ));
        } catch (\Exception $e) {
            \DBG::msg($e->getMessage());
            return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_PAYOUT_REQUEST_ERROR']);
        }
        return array('status' => 'success', 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_PAYOUT_REQUEST_SUCCESS']);
    }
    
    /**
     * Get latest codebase and fetch the all websites
     * 
     * @global array $_ARRAYLANG language variable
     * @param  array $params     supplied arguments from JsonData-request
     * 
     * @return array JsonData-response
     * @throws MultiSiteJsonException
     */
    public function getCodeBaseVersions($params) {
        global $_ARRAYLANG;
        try {
            self::loadLanguageData();
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode', 'MultiSite')) {
                case ComponentController::MODE_MANAGER:
                    $websiteServiceServer = ComponentController::getServiceServerByCriteria(array('id' => $params['post']['serviceServerId']));
                    if (!$websiteServiceServer) {
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_SERVICE_SERVER']);
                    }
                    
                    $response = self::executeCommandOnServiceServer('getCodeBaseVersions', array(), $websiteServiceServer);
                    if ($response && $response->status = 'success') {
                        return array('status' => 'success', 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SERVICE_SERVER_CODEBASE_REQUEST_SUCCESS'], 'codeBaseVersions' => $response->data->codeBaseVersions);
                    }
                    break;
                case ComponentController::MODE_SERVICE:
                case ComponentController::MODE_HYBRID:
                    
                    $updateController = $this->getComponent('Update') ? $this->getComponent('Update')->getController('Update') : null;
                    if (!$updateController) {
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_UPDATE_PROCESS_ERROR_MSG']);
                    }
                    $codeBasePath      = \Cx\Core\Setting\Controller\Setting::getValue('codeBaseRepository', 'MultiSite');
                    $codeBaseVersions  = $updateController->getAllCodeBaseVersions($codeBasePath);
                    rsort($codeBaseVersions);
                    return array('codeBaseVersions' => $codeBaseVersions);
                    break;
            }
            return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_GET_CODEBASES_ERROR_MSG']);
        } catch (\Exception $e) {
            \DBG::msg($e->getMessage());
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_GET_CODEBASES_ERROR_MSG']);
        }
    }
    
    /**
     * Triggering the website update process
     * 
     * @global array $_ARRAYLANG language variable
     * @param  array $params     supplied arguments from JsonData-request
     * 
     * @return array JsonData-response
     * @throws MultiSiteJsonException
     */
    public function triggerWebsiteUpdate($params)  {
        global $_ARRAYLANG;
        
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode', 'MultiSite')) {
                case ComponentController::MODE_MANAGER:
                    $websiteServiceServer = ComponentController::getServiceServerByCriteria(array('id' => $params['post']['serviceServerId']));
                    if (!$websiteServiceServer) {
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_SERVICE_SERVER']);
                    }
                    $response = self::executeCommandOnServiceServer('triggerWebsiteUpdate', $params['post'], $websiteServiceServer);
                    if ($response && $response->status == 'success') {
                        return array('status' => 'success', 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_TRIGGERING_WEBSITE_UPDATE_SUCCESS_MSG']);
                    }
                    break;
                case ComponentController::MODE_SERVICE:
                case ComponentController::MODE_HYBRID:
                    if (    empty($params['post']) 
                        ||  empty($params['post']['codeBase']) 
                        ||  empty($params['post']['websites'])
                    ) {
                        \DBG::msg('JsonMultiSiteController::triggerWebsiteUpdate() failed: Insufficient arguments supplied: ' . var_export($params, true));
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_UPDATE_PROCESS_ERROR_MSG']);
                    }
                    $ymlContent = array(
                                    'codeBase' => $params['post']['codeBase'],
                                    'websites' => $params['post']['websites']
                                  );
                    try {
                        $updateController = $this->getComponent('Update') ? $this->getComponent('Update')->getController('Update') : null;
                        if(!$updateController){
                           throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_UPDATE_PROCESS_ERROR_MSG']); 
                        }
                        $folderPath = $this->cx->getWebsiteTempPath() . '/MultiSite';
                        $filePath = $folderPath . '/' . $updateController->getPendingCodeBaseChangesFile();
                        $updateController->storeUpdateWebsiteDetailsToYml($folderPath, $filePath, $ymlContent );
                        
                        // make the asynchronous call
                        $pendingCodeBaseChanges = $updateController->getUpdateWebsiteDetailsFromYml($filePath);
                        $websites = $pendingCodeBaseChanges['PendingCodeBaseChanges']['websites'];
                        $codeBase = $pendingCodeBaseChanges['PendingCodeBaseChanges']['codeBase'];
                        \Cx\Core\Setting\Controller\Setting::init('Config', '', 'Yaml');
                        $params = array('codeBase'     => $codeBase,
                                        'codeBasePath' => \Cx\Core\Setting\Controller\Setting::getValue('codeBaseRepository', 'MultiSite'),
                                        'serviceServerCodeBase' => \Cx\Core\Setting\Controller\Setting::getValue('coreCmsVersion', 'Config')    
                        );
                 
                        $websiteRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
                        $websiteObj  = $websiteRepo->findWebsitesByCriteria(array('in' => array(array('website.id', $websites))));
                        
                        if (!empty($websiteObj)) {
                            //Send the asynchronous call to instances
                            foreach ($websiteObj as $website) {
                                $arg = array();
                                $arg['post']['websiteId'] = $website->getId();
                                $this->websiteBackup($arg);
                                self::executeCommandOnWebsite('websiteUpdate', $params, $website, array(), true);
                            }
                            //Update the Website CodeBase in manager
                            self::executeCommandOnManager('updateWebsiteCodeBase', array('websiteIds' => $websites, 'codeBase' => $codeBase));
                            //Send update status as email to Admin
                            self::executeCommandOnManager('sendUpdateNotification', array('websiteIds' => $websites, 'emailTemplateKey' => 'notification_update_status_email'));
                        }
                        
                    } catch (\Exception $e) {
                        \DBG::log($e->getMessage());
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_UPDATE_PROCESS_ERROR_MSG']);
                    }
                    return array('status' => 'success');
                    break;
            }
            return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_UPDATE_PROCESS_ERROR_MSG']);
        } catch (\Exception $e) {
            \DBG::msg($e->getMessage());
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_UPDATE_PROCESS_ERROR_MSG']);
        }
    }
    
    /**
     * Website Update process
     * 
     * @global array $_ARRAYLANG language variable
     * @param  array $params     supplied arguments from JsonData-request
     * 
     * @return array JsonData-response
     * @throws MultiSiteJsonException
     */
    public function websiteUpdate($params)  {
        global $_ARRAYLANG;
        
        if (
               empty($params['post']) 
            || empty($params['post']['codeBase'])
            || empty($params['post']['codeBasePath'])
        ) {
            \DBG::msg('JsonMultiSiteController::websiteUpdate() failed: Insufficient arguments supplied: ' . var_export($params, true));
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_UPDATE_ERROR_MSG']);
        }
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode', 'MultiSite')) {
                case ComponentController::MODE_WEBSITE:
                    //check the website is already updated one or not
                    $latestCodeBase = contrexx_input2raw($params['post']['codeBase']);
                    \Cx\Core\Setting\Controller\Setting::init('Config', '', 'Yaml');
                    $coreCmsVersion = \Cx\Core\Setting\Controller\Setting::getValue('coreCmsVersion', 'Config');
                    $oldVersion     = empty($coreCmsVersion) ? contrexx_input2raw($params['post']['serviceServerCodeBase']) : $coreCmsVersion;
                    
                    if ($oldVersion === $latestCodeBase) {
                        return;
                    }
                    
                    $updateController = $this->getComponent('Update') ? $this->getComponent('Update')->getController('Update') : null;
                    if (!$updateController) {
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_UPDATE_ERROR_MSG']);
                    }
                    
                    $folderPath = $this->cx->getWebsiteTempPath() . '/Update';
                    $filePath = $folderPath .'/'. $updateController->getPendingCodeBaseChangesFile();
                    $ymlContent = array(
                                        'oldCodeBaseId'    => $oldVersion,
                                        'latestCodeBaseId' => $latestCodeBase
                                      );     
                    $updateController->storeUpdateWebsiteDetailsToYml($folderPath, $filePath, $ymlContent );
                    
                    $installationRootPath = contrexx_input2raw($params['post']['codeBasePath']) . '/' . $latestCodeBase;
                    
                    //set website to offline mode
                    \Cx\Core\Setting\Controller\Setting::init('MultiSite', '', 'FileSystem');
                    \Cx\Core\Setting\Controller\Setting::set('websiteState', \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_OFFLINE);
                    \Cx\Core\Setting\Controller\Setting::update('websiteState');

                    //upadate codeBase
                    $updateController->updateCodeBase($latestCodeBase, $installationRootPath);
                    
                    //create Pending Db Update list in yml using the Delta object
                    $updateController->calculateDbDelta($oldVersion, $installationRootPath);
                    
                    //set website back to online mode
                    \Cx\Core\Setting\Controller\Setting::init('MultiSite', '', 'FileSystem');
                    \Cx\Core\Setting\Controller\Setting::set('websiteState', \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_ONLINE);
                    \Cx\Core\Setting\Controller\Setting::update('websiteState');
                    break;
            }
          
        } catch (\Exception $e) {
            \DBG::log($e->getMessage());
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_UPDATE_ERROR_MSG']);
        }
    }
    
    /**
     * update websiteCodeBase
     * 
     * @global array $_ARRAYLANG language variable
     * @param  array $params     supplied arguments from JsonData-request
     * 
     * @return array JsonData-response
     * @throws MultiSiteJsonException
     */
    public function updateWebsiteCodeBase($params) {
        global $_ARRAYLANG;
        
        if (    empty($params['post']) 
            ||  empty($params['post']['codeBase'])
        ) {
            \DBG::msg('JsonMultiSiteController::updateWebsiteCodeBase() failed: Insufficient arguments supplied: ' . var_export($params, true));
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_UPDATE_CODEBASE_ERROR_MSG']);
        }
        
        try {
            $codeBase = isset($params['post']['codeBase']) ? contrexx_input2raw($params['post']['codeBase']) : '';
            $websiteRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode', 'MultiSite')) {
                case ComponentController::MODE_MANAGER:
                case ComponentController::MODE_HYBRID:
                    if (empty($params['post']['websiteIds'])) {
                        \DBG::msg('JsonMultiSiteController::updateWebsiteCodeBase() failed: Insufficient arguments supplied: ' . var_export($params, true));
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_UPDATE_CODEBASE_ERROR_MSG']);
                    }
                    $websiteIds  = isset($params['post']['websiteIds']) ? contrexx_input2raw($params['post']['websiteIds']) : array();
                    $websiteObj  = $websiteRepo->findWebsitesByCriteria(array('in' => array(array('website.id', $websiteIds))));
                    if (!empty($websiteObj)) {
                        foreach ($websiteObj as $website) {
                            $website->setCodeBase($codeBase);
                        }
                        \Env::get('em')->flush();
                    }
                    return array('status' => 'success');
                    break;
                case ComponentController::MODE_SERVICE:
                    if (empty($params['post']['websiteName'])) {
                        \DBG::msg('JsonMultiSiteController::updateWebsiteCodeBase() failed: Insufficient arguments supplied: ' . var_export($params, true));
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_UPDATE_CODEBASE_ERROR_MSG']);
                    }
                    $websiteName  = isset($params['post']['websiteName']) ? contrexx_input2raw($params['post']['websiteName']) : '';
                    $website      = $websiteRepo->findOneBy(array('name' => $websiteName));
                    $resp = self::executeCommandOnManager('updateWebsiteCodeBase', array('websiteIds' => array($website->getId()), 'codeBase' => $codeBase));
                    if ($resp && $resp->status == 'success') {
                        return array('status' => 'success');
                    }
                    break;
            }
            return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_UPDATE_CODEBASE_ERROR_MSG']);
        } catch (\Exception $e) {
            \DBG::log($e->getMessage());
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_UPDATE_CODEBASE_ERROR_MSG']);
        }
    }
    
    /**
     * Send the website update status notification email to Admin
     * 
     * @global array $_ARRAYLANG language variable
     * @param  array $params     supplied arguments from JsonData-request
     * 
     * @return array JsonData-response
     * @throws MultiSiteJsonException
     */
    public function sendUpdateNotification($params) {
        global $_ARRAYLANG;
        
        if (    empty($params['post']) 
            ||  empty($params['post']['emailTemplateKey'])
        ) {
            \DBG::msg('JsonMultiSiteController::sendUpdateNotification() failed: Insufficient arguments supplied: ' . var_export($params, true));
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_UPDATE_STATUS_ERROR_MSG']);
        }
        
        try {
            $templateKey = isset($params['post']['emailTemplateKey']) ? contrexx_input2raw($params['post']['emailTemplateKey']) : '';
            $websiteRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode', 'MultiSite')) {
                case ComponentController::MODE_MANAGER:
                    if (empty($params['post']['websiteIds'])) {
                        \DBG::msg('JsonMultiSiteController::sendUpdateNotification() failed: Insufficient arguments supplied: ' . var_export($params, true));
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_UPDATE_STATUS_ERROR_MSG']);
                    }
                    $websiteIds  = isset($params['post']['websiteIds']) ? contrexx_input2raw($params['post']['websiteIds']) : array();
                    $websiteObj  = $websiteRepo->findWebsitesByCriteria(array('in' => array(array('website.id', $websiteIds))));
                    if (!empty($websiteObj) && is_array($websiteObj)) {
                        foreach ($websiteObj as $website) {
                            $websiteDetails[] = array(
                                'WEBSITE_CREATION_DATE' => $website->getCreationDate()->format(ASCMS_DATE_FORMAT_INTERNATIONAL_DATETIME),
                                'WEBSITE_NAME'          => $website->getName(),
                                'WEBSITE_MAIL'          => $website->getOwner()->getEmail(),
                                'WEBSITE_DOMAIN'        => $website->getName() . '.' . \Cx\Core\Setting\Controller\Setting::getValue('multiSiteDomain','MultiSite')
                            );
                        }
                        $substitution = array('WEBSITE_LIST' => array(0 => array('WEBSITE_DETAIL' => $websiteDetails)));
                    }
                    //send mail to admin
                    $config    = \Env::get('config');
                    $arrValues = array(
                                    'section'      => 'MultiSite',
                                    'lang_id'      => 1,
                                    'key'          => $templateKey,
                                    'to'           => $config['coreAdminEmail'],
                                    'substitution' => $substitution);
                    if (\Cx\Core\MailTemplate\Controller\MailTemplate::send($arrValues)) {
                        return array('status' => 'success');
                    }
                    break;
                case ComponentController::MODE_SERVICE:
                    if (empty($params['post']['websiteName'])) {
                        \DBG::msg('JsonMultiSiteController::sendUpdateNotification() failed: Insufficient arguments supplied: ' . var_export($params, true));
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_UPDATE_STATUS_ERROR_MSG']);
                    }
                    $websiteName  = isset($params['post']['websiteName']) ? contrexx_input2raw($params['post']['websiteName']) : '';
                    $website      = $websiteRepo->findOneBy(array('name' => $websiteName));
                    $resp = self::executeCommandOnManager('sendUpdateNotification', array('websiteIds' => array($website->getId()), 'emailTemplateKey' => $templateKey));
                    if ($resp && $resp->status == 'success') {
                        return array('status' => 'success');
                    }
                    break;
            }
            return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_UPDATE_STATUS_ERROR_MSG']);
        } catch (\Exception $e) {
            \DBG::log($e->getMessage());
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_UPDATE_STATUS_ERROR_MSG']);
        }
    }
    
    /**
     * Copy website
     * 
     * @global array $_ARRAYLANG language variable
     * @param  array $params     supplied arguments from JsonData-request
     * 
     * @return array JsonData-response
     * @throws MultiSiteJsonException
     */
    public function copyWebsite($params)
    {
        global $_ARRAYLANG;

        $componentController = $this->getSystemComponentController();
        try {
            if ($componentController && !$componentController->isCrmUser()) {
                throw new MultiSiteJsonException(__METHOD__ . ': User has no CRM account!');
            }
            $websiteId = !empty($params['post']['websiteId']) 
                         ? contrexx_input2int($params['post']['websiteId']) 
                         : (!empty($params['get']['websiteId']) 
                            ? contrexx_input2int($params['get']['websiteId']) 
                            : 0);
            if (    empty($params['post'])
                ||  empty($websiteId)
                ||  empty($params['post']['multisite_address'])
            ) {
                throw new MultiSiteJsonException(__METHOD__ . ': Insufficient arguments supplied: ' . var_export($params, true));
            }

            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode', 'MultiSite')) {
                case ComponentController::MODE_MANAGER:
                    $objUser = \FWUser::getFWUserObject()->objUser;
                    $em      = $this->cx->getDb()->getEntityManager();

                    $websiteAddress = isset($params['post']['multisite_address']) ? contrexx_input2raw($params['post']['multisite_address']) : '';
                    $subscriptionId = isset($params['post']['subscriptionId']) ? contrexx_input2int($params['post']['subscriptionId']) : 0;
                    $productId      = isset($params['post']['productId']) ? contrexx_input2int($params['post']['productId']) : 0;
                    if (empty($websiteId)) {
                        throw new MultiSiteJsonException(__METHOD__ . ': Website id cannot be empty');
                    }

                    // verify source website
                    $websiteServiceRepo = $em->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
                    $website            = $websiteServiceRepo->findOneById($websiteId);
                    if (!$website) {
                        throw new MultiSiteJsonException(__METHOD__ . ': Invalid website idsupplied:'. $websiteId);
                    }
                    if ($website->getOwner()->getId() != $objUser->getId()) {
                        throw new MultiSiteJsonException(__METHOD__ . ': Logged in user is not the website owner');
                    }
                    // verify target website address
                    $this->address($params);
                    // verify the subscription and product
                    if (empty($subscriptionId) && empty($productId)) {
                        throw new MultiSiteJsonException(__METHOD__ . ': Subscription id and product id are empty.');
                    }
                    if (!empty($subscriptionId)) {
                        // verify target subscription
                        $subscriptionRepo = $em->getRepository('Cx\Modules\Order\Model\Entity\Subscription');
                        $subscription     = $subscriptionRepo->findOneBy(array('id' => $subscriptionId));

                        if (!$subscription) {
                            throw new MultiSiteJsonException(__METHOD__ . ': Selected subscription does not exist');
                        }
                        //Verify if the signed-in user is the authorized owner of the subscription ordered
                        if ($objUser->getCrmUserId() != $subscription->getOrder()->getContactId()) {
                            throw new MultiSiteJsonException(__METHOD__ . ': Subscription is not valid for the customer');
                        }
                        // check website quota
                        $websiteCollection = $subscription->getProductEntity();
                        if (   !($websiteCollection instanceof \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteCollection)
                            || $websiteCollection->getQuota() <= count($websiteCollection->getWebsites())
                        ) {
                            throw new MultiSiteJsonException(__METHOD__ . ': Invalid Subscription');
                        }
                    }

                    if (!empty($productId)) {
                        $productRepository = $em->getRepository('Cx\Modules\Pim\Model\Entity\Product');
                        $product           = $productRepository->findOneBy(array('id' => $productId));
                        if (!$product) {
                            throw new MultiSiteJsonException(__METHOD__ . ': Selected product does not exist');
                        }
                    }

                    // backup website
                    $backupArguments = array(
                        'serviceServerId' => $website->getWebsiteServiceServerId(),
                        'websiteId'       => $websiteId,
                        'isCopy'          => true,
                    );
                    $websiteBackupResp = self::executeCommandOnManager('websiteBackup', $backupArguments);
                    if ($websiteBackupResp->status == 'error' || $websiteBackupResp->data->status == 'error') {
                        throw new MultiSiteJsonException(__METHOD__ . ': Website backup creation failed : '. $website->getName());
                    }
                    $restoreArguments = array(
                        'websiteName'           => $websiteAddress,
                        'websiteBackupFileName' => current($websiteBackupResp->data->backups),
                        'serviceServerId'       => $website->getWebsiteServiceServerId(),
                        'selectedUserId'        => $objUser->getId(),
                    );
                    if (!empty($subscriptionId)) {
                        $restoreArguments['subscriptionId'] = $subscriptionId;
                    }
                    if (!empty($productId)) {
                        $restoreArguments['productId'] = $productId;
                    }
                    $restoreResp = self::executeCommandOnManager('websiteRestore', $restoreArguments);
                    if ($restoreResp->status == 'success' && $restoreResp->data->status == 'success') {
                        return array(
                            'status'    => 'success', 
                            'message'   => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_COPY_SUCCESS'],
                            'websiteId' => $restoreResp->data->websiteId,
                        );
                    } else {
                        \DBG::msg('(Manager) ' . $restoreResp->message);
                    }
                    // probably obsolete?
                    if (isset($restoreResp) && isset($restoreResp->data) && isset($restoreResp->data->log)) {
                        \DBG::appendLogs(array_map(function($logEntry) {return '(Manager) '.$logEntry;}, $restoreResp->data->log));
                    }

                    break;
            }
        } catch (\Exception $e) {
            \DBG::log($e->getMessage());
        }
        $recipientMailAddress = \Cx\Core\Setting\Controller\Setting::getValue('supportRecipientMailAddress','MultiSite');
        return array(
            'status' => 'error',
            'message' => sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_COPY_ERROR_MSG'], $recipientMailAddress),
        );
    }

    /**
     * Get the websites by codeBase
     * 
     * @global array $_ARRAYLANG language variable
     * @param  array $params     supplied arguments from JsonData-request
     * 
     * @return array JsonData-response
     * @throws MultiSiteJsonException
     */
    public function getWebsitesByCodeBase($params)  
    {
        global $_ARRAYLANG;

        try {
            self::loadLanguageData();
            if (empty($params['post']) || !isset($params['post']['codeBase'])) {
                \DBG::msg('JsonMultiSiteController::getWebsitesByCodeBase() failed: Insufficient arguments supplied: ' . var_export($params, true));
                throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_GET_WEBSITES_ERROR_MSG']);
            }
            $codeBase = isset($params['post']['codeBase']) ? contrexx_input2raw($params['post']['codeBase']) : '';
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode', 'MultiSite')) {
                case ComponentController::MODE_MANAGER:
                    //get the service server
                    $websiteServiceServer = ComponentController::getServiceServerByCriteria(array('id' => $params['post']['serviceServerId']));
                    if (!$websiteServiceServer) {
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_SERVICE_SERVER']);
                    }

                    $response = self::executeCommandOnServiceServer('getWebsitesByCodeBase', array('codeBase' => $codeBase), $websiteServiceServer);
                    if ($response && $response->status = 'success') {
                        return array('status' => 'success', 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_GET_WEBSITES_SUCCESS_MSG'], 'websites' => $response->data->websites);
                    }
                    break;
                case ComponentController::MODE_SERVICE:
                case ComponentController::MODE_HYBRID:
                    //websites
                    $websiteRepo = \Env::get('em')->getRepository('\Cx\Core_Modules\MultiSite\Model\Entity\Website');
                    $websiteObj = $websiteRepo->findWebsitesByCriteria(array('neq' => array(array('website.codeBase', $codeBase)), 'in' => array(array('website.status', array('online')))));
                    \Cx\Core\Setting\Controller\Setting::init('Config', '', 'Yaml');
                    $serviceCodeBase = \Cx\Core\Setting\Controller\Setting::getValue('coreCmsVersion', 'Config');
                    $websites = array();
                    foreach ($websiteObj as $website) {
                        $websiteCodeBase = \FWValidator::isEmpty($website->getCodeBase()) ? $serviceCodeBase : $website->getCodeBase();
                        if ($websiteCodeBase != $codeBase) {
                            $websites[] = array(
                                'id'        => $website->getId(),
                                'name'      => $website->getName(),
                                'codeBase'  => $websiteCodeBase,
                            );
                        }
                    }
                    return array('websites' => $websites);
                    break;
            }
            return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_GET_WEBSITES_ERROR_MSG']);
        } catch (\Exception $e) {
            \DBG::msg($e->getMessage());
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_GET_WEBSITES_ERROR_MSG']);
        }
    }

    /**
     * Fetch the SSL Certificate details from hosting controller
     * 
     * @param array $params supplied arguments from JsonData-request
     * 
     * @return array JsonData-response
     * @throws MultiSiteJsonException
     */
    public function getDomainSslCertificate($params)
    {
        global $_ARRAYLANG;
        
        if (
            empty($params['post']) ||
            empty($params['post']['websiteName']) ||
            empty($params['post']['domainName'])
        ) {
            \DBG::msg('JsonMultiSiteController::getDomainSslCertificate() failed: Insufficient arguments supplied: ' . var_export($params, true));
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_SSL_FAILED']);
        }
        try {                        
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_SERVICE:
                case ComponentController::MODE_HYBRID:
                    $hostingController = \Cx\Core_Modules\MultiSite\Controller\ComponentController::getHostingController();
                    $sslCertificates   = $hostingController->getSSLCertificates(
                        $params['post']['websiteName'],
                        $params['post']['domainName']
                    );
                    if ($sslCertificates) {
                        return array('status' => 'success', 'sslCertificate' => $sslCertificates);
                    }
                    break;
                default :
                    break;
            }
            return array('status' => 'error');
        } catch (\Exception $e) {
            \DBG::msg($e->getMessage());
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_SSL_FAILED']);
        }
    }
    
    /**
     * Link SSL certificate with the domain
     * 
     * @param  array $params     supplied arguments from JsonData-request
     * 
     * @return array JsonData-response
     * @throws MultiSiteJsonException
     */
    public function linkSsl($params)
    {
        global $_ARRAYLANG;

        if (   empty($params['post']) 
            || empty($params['post']['websiteName']) 
            || empty($params['post']['domainName']) 
            || empty($params['post']['certificateName']) 
            || empty($params['post']['privateKey']) 
        ) {
            \DBG::msg('JsonMultiSiteController::linkSsl() failed: Insufficient arguments supplied: ' . var_export($params, true));
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_SSL_FAILED']);
        }
        try {                        
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_SERVICE:
                case ComponentController::MODE_HYBRID:
                    $websiteName = $params['post']['websiteName'];
                    $hostingController = \Cx\Core_Modules\MultiSite\Controller\ComponentController::getHostingController();
                    
                    $siteList = $hostingController->getAllWebDistributionAliases(
                        $websiteName
                    );
                    
                    if (!in_array($params['post']['domainName'], $siteList)) {
                        // this should not happen, WebDistributionAlias should already exist!
                        $siteId = $hostingController->createWebDistributionAlias(
                            $websiteName,
                            $params['post']['domainName']
                        ); 
                    } else {
                        $siteId = array_search($params['post']['domainName'], $siteList);
                        $sslCertificates = $hostingController->getSSLCertificates(
                            $websiteName,
                            $params['post']['domainName']
                        );
                        if (!empty($sslCertificates)) {
                            $hostingController->removeSSLCertificates(
                                $websiteName,
                                $params['post']['domainName'],
                                $sslCertificates
                            );
                        }
                    }
                    
                    $installSslCertificate = $hostingController->installSSLCertificate(
                        $websiteName,
                                                                        $params['post']['certificateName'], 
                                                                        $params['post']['domainName'], 
                                                                        $params['post']['privateKey'],
                                                                        $params['post']['certificate'], 
                        $params['post']['caCertificate']
                    );
                    if ($installSslCertificate) {
                        if ($hostingController->activateSSLCertificate(
                            $websiteName,
                            $params['post']['certificateName'],
                            $siteId
                        )) {
                            return array('status' => 'success');
                        }
                    }
                    break;
                default :
                    break;
            }
            return array(
                'status' => 'error',
                'log'    => \DBG::getMemoryLogs(),
            );
        } catch (\Exception $e) {
            \DBG::msg($e->getMessage());
            return array(
                'status'  => 'error',
                'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_SSL_FAILED'],
                'log'     => \DBG::getMemoryLogs(),
            );
        }
    }

    /**
     * Removes an SSL certificate for a domain
     * 
     * @param  array $params     supplied arguments from JsonData-request
     * 
     * @return array JsonData-response
     * @throws MultiSiteJsonException
     */
    public function unlinkSsl($params) {
        if (
            empty($params['post']) ||
            empty($params['post']['websiteName']) ||
            empty($params['post']['domainName']) 
        ) {
            \DBG::msg('JsonMultiSiteController::unLinkSsl() failed: Insufficient arguments supplied: ' . var_export($params, true));
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_SSL_FAILED']);
        }
        try {                        
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case ComponentController::MODE_SERVICE:
                case ComponentController::MODE_HYBRID:
                    $hostingController = ComponentController::getHostingController();
                    
                    $certificateList = $hostingController->getSslCertificates(
                        $params['post']['websiteName']
                    );
                    
                    if (!in_array($params['post']['domainName'], $certificateList)) {
                        // already deleted, nothing to do
                    } else {
                        $hostingController->removeSSLCertificates(
                            $params['post']['websiteName'],
                            $params['post']['domainName']
                        );

                        if (count($certificateList) == 1) {
                            // our domain was the only one in the list, cleanup distribution
                            $hostingController->deleteWebDistributionAlias(
                                $params['post']['websiteName'],
                                $params['post']['domainName']
                            );
                        }
                    }
                    break;
                default :
                    break;
            }
            return array(
                'status' => 'error',
                'log'    => \DBG::getMemoryLogs(),
            );
        } catch (\Exception $e) {
            \DBG::msg($e->getMessage());
            return array(
                'status'  => 'error',
                'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DOMAIN_SSL_FAILED'],
                'log'     => \DBG::getMemoryLogs(),
            );
        }
    }

    /**
     * Set the user as the website owner
     * 
     * @global array $_ARRAYLANG language variable
     * @param  array $params     supplied arguments from JsonData-request
     * 
     * @return array JsonData-response
     * @throws MultiSiteJsonException
     */
    public function setWebsiteOwner($params) {
        global $_ARRAYLANG;
        
        if (
             empty($params['post']) || empty($params['post']['ownerEmail'])
        ) {
            \DBG::msg('JsonMultiSiteController::setWebsiteOwner() failed: Insufficient arguments supplied: ' . var_export($params, true));
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CHANGE_OWNER_USER_ERROR']);
        }
        
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode', 'MultiSite')) {
                case ComponentController::MODE_WEBSITE:
                    $em             = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager();
                    $em->getConfiguration()->getResultCacheImpl()->deleteAll();
                    $userRepo       = $em->getRepository('Cx\Core\User\Model\Entity\User');
                    $ownerEmail     = !empty($params['post']['ownerEmail']) ? contrexx_input2raw($params['post']['ownerEmail']) : '';
                    $objUser        = $userRepo->findOneBy(array('email' => $ownerEmail));
                    
                    //Check if the new owner  already exists and is an Admin
                    if (    $objUser instanceof \Cx\Core\User\Model\Entity\User 
                        &&  $objUser->getIsAdmin() && $objUser->isBackendGroupUser()
                    ) {
                        $this->updateWebsiteOwnerId($objUser->getId());
                        return array(
                            'userId' => $objUser->getId(),
                            'log'    => \DBG::getMemoryLogs(),
                        );
                    }
                    
                    $accessEventObj = new \Cx\Core_Modules\MultiSite\Model\Event\AccessUserEventListener;
                    $adminUserQuota = $accessEventObj->checkAdminUsersQuota();
                    
                    //If  the quota for  current admin user does not allow to set admin flag for new/existing users 
                    //then we need to remove the admin permission from the previous Admin user.
                    if (    (!$objUser && !$adminUserQuota) 
                        ||  ($objUser && !$objUser->getIsAdmin() && !$objUser->isBackendGroupUser() && !$adminUserQuota)
                    ) {
                        $oldOwner = $userRepo->findOneById(\Cx\Core\Setting\Controller\Setting::getValue('websiteUserId', 'MultiSite'));
                        $oldOwner->setIsAdmin(false);
                        foreach($oldOwner->getGroup() as $group) {
                            if ($group->getType() === 'backend') {
                                $oldOwner->removeGroup($group);
                            }
                        }
                        $em->flush();
                    }

                    //if the user  already exists and is not an Admin, then set the user as Administrator 
                    //else create a new admin user
                    if ($objUser instanceof \Cx\Core\User\Model\Entity\User) {
                        if (!$objUser->getIsAdmin()) {
                            $objUser->setIsAdmin(true);
                        }
                        if (!$objUser->isBackendGroupUser()) {
                            $groupRepo = $em->getRepository('Cx\Core\User\Model\Entity\Group');
                            $group     = $groupRepo->findOneBy(array('type' => 'backend', 'isActive' => true));
                            if ($group) {
                                $objUser->addGroup($group);
                            }
                        }
                        $em->flush();
                    } else {
                        $params['post'] = array(
                            'email' => $ownerEmail,
                            'active'=> 1,
                            'admin' => 1,
                            'groups' => array(1),
                        );
                        $objUser = $this->createUser($params, true);
                    }
                    
                    $userId = ($objUser instanceof \Cx\Core\User\Model\Entity\User) 
                              ? $objUser->getId() 
                              : $objUser['userId'];
                    
                    //Set the new/existing Admin user as website owner
                    if (!empty($userId)) {
                        $this->updateWebsiteOwnerId($userId);
                        return array(
                            'userId' => $userId,
                            'log'    => \DBG::getMemoryLogs(),
                        );
                    }
                    
                    \DBG::msg('Adding user failed: ' . $objUser->getErrorMsg());
                    throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CHANGE_OWNER_USER_ERROR']);
                    break;
            }
        } catch (\Throwable $e) {
            \DBG::log($e->getMessage());
            return array(
                'status'  => 'error',
                'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CHANGE_OWNER_USER_ERROR'].'3',
                'log'     => \DBG::getMemoryLogs(),
            );
        }
    }
    
    /**
     * Set the user as website Owner
     * 
     * @param integer $userId
     * 
     * @return boolean
     */
    public function updateWebsiteOwnerId($userId) {
        if (empty($userId)) {
            return;
        }
        \Cx\Core\Setting\Controller\Setting::init('MultiSite', '','FileSystem');
        \Cx\Core\Setting\Controller\Setting::set('websiteUserId', $userId);
        \Cx\Core\Setting\Controller\Setting::update('websiteUserId');
    }
    
    /**
     * This ensures that the website owner is an admin user of the site and
     * optionally removes all other users
     * @todo removing all other users is not implemented yet
     * @param Website $website Website to update accounts on
     * @param int $ownerUserId New website owner ID
     * @param bool $keepAccountsFromBackup Wheter or not to keep the existing accounts
     * @throws MultiSiteJsonException If user is not found or user account update fails
     * @author Michael Ritter <michael.ritter@cloudrexx.com>
     */
    protected function updateUserAccountsOnRestore($website, $ownerUserId, $keepAccountsFromBackup) {
        // ensure owner is correctly set and exists
        $em             = $this->cx->getDb()->getEntityManager();
        $userRepo       = $em->getRepository('Cx\Core\User\Model\Entity\User');
        $objUser        = $userRepo->findOneBy(array('id' => $ownerUserId));
        
        if (!$objUser) {
            throw new MultiSiteJsonException('updateUserAccountsOnRestore: User not found');
        }
        
        $resp = self::executeCommandOnWebsite(
            'setWebsiteOwner',
            array(
                'ownerEmail' => $objUser->getEmail(),
            ),
            $website
        );

        if (isset($resp->log)) {
            \DBG::dump(__METHOD__ . ': setWebsiteOwner failed');
            \DBG::appendLogs(array_map(function($logEntry) {return '(Website: '.$website->getName().') '.$logEntry;}, $resp->log));
        }
        if (!$resp || $resp->status != 'success') {
            throw new MultiSiteJsonException('updateUserAccountsOnRestore: Failed to set website owner');
        }
        if (isset($resp->data) && isset($resp->data->status) && $resp->data->status == 'error') {
            throw new MultiSiteJsonException($resp->data->message);
        }
        
        if ($keepAccountsFromBackup) {
            return;
        }
        
        // remove unused accounts
        // TODO
    }

    /**
     * Get the server website list from service server by using website owner id
     * 
     * @param array $params supplied arguments from JsonData-request
     * 
     * @return array JsonData-response
     * @throws MultiSiteJsonException
     */
    public function getServerWebsiteList($params)
    {
        global $_ARRAYLANG;

        if (empty($params['post']) || empty($params['post']['websiteName'])) {
            \DBG::msg(
                'JsonMultiSiteController::getWebsiteList() failed: Insufficient arguments supplied: ' . var_export($params, true));
            throw new MultiSiteJsonException(
                $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_GET_WEBSITE_LIST_ERROR']
            );
        }

        $mode = \Cx\Core\Setting\Controller\Setting::getValue(
            'mode', 'MultiSite'
        );
        $availableModes = array(
            ComponentController::MODE_SERVICE,
            ComponentController::MODE_HYBRID
        );
        if (!in_array($mode, $availableModes)) {
            throw new MultiSiteJsonException(
                $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_GET_WEBSITE_LIST_ERROR']
            );
        }

        $authenticationValue = json_decode($params['post']['auth'], true);
        if (empty($authenticationValue) || !is_array($authenticationValue)) {
            throw new MultiSiteJsonException(
                __METHOD__ . ' failed: Insufficient mapping information supplied.'
            );
        }

        try {
            $em = $this->cx->getDb()->getEntityManager();
            $domainRepo = $em
                ->getRepository(
                    'Cx\Core_Modules\MultiSite\Model\Entity\Domain'
                );
            $objWebsiteDomain = $domainRepo
                ->findOneBy(
                    array(
                        'name' => contrexx_input2db(
                            $authenticationValue['sender']
                        )
                    )
                );
            if (!$objWebsiteDomain) {
                throw new MultiSiteJsonException(
                    __METHOD__ . ' failed: Unknown Domain: ' .
                    $authenticationValue['sender']
                );
            }

            $website = $objWebsiteDomain->getWebsite();
            if (!$website) {
                throw new MultiSiteJsonException(
                    __METHOD__ . ' failed: Unknown Website: ' .
                    $authenticationValue['sender']
                );
            }

            $ownerId     = $website->getOwner()->getId();
            $websiteName = contrexx_input2db($params['post']['websiteName']);
            $websiteRepository = $em
                ->getRepository(
                    'Cx\Core_Modules\MultiSite\Model\Entity\Website'
                );
            $args = array(
                'user.id'        => $ownerId,
                'neq'            => array(array('website.name', $websiteName)),
                'website.status' => \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_ONLINE,
                'website.mode'   => ComponentController::WEBSITE_MODE_SERVER
            );
            $websites = $websiteRepository->findWebsitesByCriteria($args);
            if (empty($websites) || !is_array($websites)) {
                return array('websiteList' => array());
            }

            $websiteList = array();
            foreach ($websites as $website) {
                $websiteList[] = $website->getId() . ':' . $website->getName();
            }
            return array('websiteList' => $websiteList);
        } catch (\Exception $e) {
            \DBG::log($e->getMessage());
            throw new MultiSiteJsonException(
                $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_GET_WEBSITE_LIST_ERROR']
            );
        }
    }

    /**
     * Check the server website is accessed by any other client websites
     *
     * @param array $params supplied arguments from JsonData-request
     *
     * @return array JsonData-response
     * @throws MultiSiteJsonException
     */
    public function checkServerWebsiteAccessedByClient($params)
    {
        global $_ARRAYLANG;

        if (empty($params['post']) || empty($params['post']['websiteName'])) {
            \DBG::msg('JsonMultiSiteController::checkServerWebsiteAccessedByClient() failed: Insufficient arguments supplied: ' .
                var_export($params, true)
            );
            throw new MultiSiteJsonException(
                $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CHECK_SERVER_ACCESSED_BY_CLIENT_ERROR']
            );
        }

        $mode = \Cx\Core\Setting\Controller\Setting::getValue(
            'mode', 'MultiSite'
        );
        $availableModes = array(
            ComponentController::MODE_SERVICE,
            ComponentController::MODE_HYBRID
        );
        if (!in_array($mode, $availableModes)) {
            throw new MultiSiteJsonException(
                $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CHECK_SERVER_ACCESSED_BY_CLIENT_ERROR']
            );
        }

        try {
            $em = $this->cx->getDb()->getEntityManager();
            $websiteName = contrexx_input2db($params['post']['websiteName']);
            $websiteRepository = $em
                ->getRepository(
                    'Cx\Core_Modules\MultiSite\Model\Entity\Website'
                );
            $args = array(
                'serverWebsite.name' => $websiteName,
                'website.status'     => \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_ONLINE,
                'website.mode'       => ComponentController::WEBSITE_MODE_CLIENT
            );
            $websites = $websiteRepository->findWebsitesByCriteria($args);
            return array(
                'status' => 'success',
                'isServerAccessByclient' => count($websites) ? true : false
            );
        } catch (\Exception $e) {
            \DBG::log($e->getMessage());
            throw new MultiSiteJsonException(
                $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CHECK_SERVER_ACCESSED_BY_CLIENT_ERROR']
            );
        }
    }

    /**
     * Get the website size (website directory size)
     * 
     * @param array $params post parameters
     * 
     * @return array website size
     * 
     * @throws MultiSiteJsonException
     */
    public function getWebsiteSize($params)
    {
        global $_ARRAYLANG;

        if (empty($params['post']) || empty($params['post']['websiteId'])) {
            \DBG::msg(__METHOD__ . ' failed: Insufficient arguments supplied: ' . var_export($params, true));
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ERROR_GETTING_WEBSITE_SIZE']);
        }

        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode', 'MultiSite')) {
                case ComponentController::MODE_SERVICE:
                case ComponentController::MODE_HYBRID:
                    $websiteId   = contrexx_input2int($params['post']['websiteId']);
                    $websiteRepo = $this->cx->getDb()->getEntityManager()->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
                    $website     = $websiteRepo->findOneBy(array('id' => $websiteId));
                    if (!$website) {
                        throw new MultiSiteJsonException('Website not exists => website id : '. $websiteId);
                    }

                    $websiteBasepath = \Cx\Core\Setting\Controller\Setting::getValue('websitePath','MultiSite') . '/' . $website->getName();
                    if (!\Cx\Lib\FileSystem\FileSystem::exists($websiteBasepath)) {
                        throw new MultiSiteJsonException('Website folder not exists => website name : '. $website->getName());
                    }

                    $output = $error = null;
                    $duCommand = 'cd ' . $websiteBasepath .' && du -c | grep total | cut -f1';
                    exec($duCommand, $output, $error);
                    if ($error) {
                        throw new MultiSiteJsonException('Could not execute system command : '. $duCommand);
                    }
                    return array('status' => 'success', 'size' => isset($output[0]) ? $output[0] : 0);
                    break;
            }
        } catch (Exception $ex) {
            \DBG::log($ex->getMessage());
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ERROR_GETTING_WEBSITE_SIZE']);
        }
    }

    /**
     * Get the server website path
     *
     * @param array $params supplied arguments from JsonData-request
     *
     * @return array JsonData-response
     * @throws MultiSiteJsonException
     */
    public function getServerWebsitePath($params) {
        global $_ARRAYLANG;

        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode', 'MultiSite')) {
                case ComponentController::MODE_SERVICE:
                case ComponentController::MODE_HYBRID:
                    if (empty($params['post']) || empty($params['post']['websiteId'])) {
                        \DBG::msg('JsonMultiSiteController::getServerWebsitePath() failed: Insufficient arguments supplied: ' . var_export($params, true));
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_GET_SERVER_WEBSITE_PATH_ERROR']);
                    }

                    $em = $this->cx->getDb()->getEntityManager();
                    $websiteId = contrexx_input2db($params['post']['websiteId']);
                    $websiteRepository = $em->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
                    $websites = $websiteRepository->findWebsitesByCriteria(array('website.id' => $websiteId, 'website.status' => \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_ONLINE));
                    $website  = current($websites);
                    if (!$website) {
                        \DBG::log('JsonMultiSiteController::getServerWebsitePath() failed: invalid websiteId supplied: ');
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_GET_SERVER_WEBSITE_PATH_ERROR']);
                    }
                    $response = self::executeCommandOnWebsite('getServerWebsitePath', '', $website);
                    if (!$response || $response->status == 'error' || empty($response->data->serverWebsitePath)) {
                        throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_GET_SERVER_WEBSITE_PATH_ERROR']);
                    }
                    return array('serverWebsitePath' => $response->data->serverWebsitePath);
                    break;
                case ComponentController::MODE_WEBSITE:
                    $websitePath = array(
                        'path'             => $this->cx->getWebsitePath(),
                        'documentRootPath' => $this->cx->getWebsiteDocumentRootPath()
                    );
                    return array('status' => 'success', 'serverWebsitePath' => $websitePath);
                    break;
            }
            return array('status' => 'error', 'message' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_GET_SERVER_WEBSITE_PATH_ERROR']);
        } catch (\Exception $e) {
            \DBG::log($e->getMessage());
            throw new MultiSiteJsonException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_GET_SERVER_WEBSITE_PATH_ERROR']);
        }
    }

    public function updateWebDistributionAlias($params) {
        if (
            \Cx\Core\Setting\Controller\Setting::getValue(
                'mode',
                'MultiSite'
            ) !== ComponentController::MODE_SERVICE
        ) {
            throw new MultiSiteJsonException(
                'This method can only be used on service server'
            );
        }

        if (
            empty($params['post']['command']) ||
            empty($params['post']['websiteName']) ||
            empty($params['post']['domainName']) ||
            (
                $params['post']['command'] == 'renameWebDistributionAlias' &&
                empty($params['post']['oldDomainName'])
            )
        ) {
            throw new MultiSiteJsonException(
                'Not all necessary params set!'
            );
        }
        $command = $params['post']['command'];
        $hostingController = ComponentController::getHostingController();
        if (isset($params['post']['oldDomainName'])) {
            $hostingController->$command(
                $params['post']['websiteName'],
                $params['post']['oldDomainName'],
                $params['post']['domainName']
            );
        } else {
            $hostingController->$command(
                $params['post']['websiteName'],
                $params['post']['domainName']
            );
        }
        return array(
            'status' => 'success',
        );
    }
}

