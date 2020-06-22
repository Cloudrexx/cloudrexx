<?php 
namespace Cx\Core_Modules\MultiSite\Model\Entity;

class WebsiteException extends \Exception {}

class Website extends \Cx\Model\Base\EntityBase {
    
    /**
     * Status online
     */
    const STATE_ONLINE = 'online';
    
    /**
     * Status offline
     */
    const STATE_OFFLINE = 'offline';
    
    /**
     * Status init
     */
    const STATE_INIT = 'init';
    
    /**
     * Status setup
     */
    const STATE_SETUP =  'setup';
    
    /**
     * Status disabled
     */
    const STATE_DISABLED =  'disabled';
        
    protected $basepath = null;
  
    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var string $name
     */
    protected $name;

    /**
     * @var \DateTime $creationDate
     */
    protected $creationDate;

    /**
     * @var string $codeBase
     */
    protected $codeBase;

    /**
     * @var string $language
     */
// TODO: do we still need this??
    protected $language;

    /**
     * @var string $status
     */
    protected $status;
    
    /**
     * @var integer $websiteServiceServerId
     */
    protected $websiteServiceServerId;
    
    /**
     * @var Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer
     */
    protected $websiteServiceServer;
    
    protected $owner;
    
    protected $websiteController;
   
    /**
     * @var string $ipAddress
     */
    protected $ipAddress;

    /**
     * @var string $secretKey
     */
    protected $secretKey;
    
    /**
     * @var string $installationId
     */
    protected $installationId;

    /**
     * @var Cx\Core_Modules\MultiSite\Model\Entity\Domain
     */
    protected $fqdn;
    /**
     * @var Cx\Core_Modules\MultiSite\Model\Entity\Domain
     */
    protected $baseDn;

    /**
     * @var Cx\Core_Modules\MultiSite\Model\Entity\Domain
     */
    protected $mailDn;

    /**
     * @var Cx\Core_Modules\MultiSite\Model\Entity\Domain
     */
    protected $webmailDn;
    
    /**
     * @var Cx\Core_Modules\MultiSite\Model\Entity\Domain
     */
    protected $domainAliases;
    
    /**
     * @var Cx\Core_Modules\MultiSite\Model\Entity\Domain
     */
    protected $domains;
    
    /**
     * @var string $ftpUser
     */
    protected $ftpUser;
    
    /**
     * @var string $themeId
     */
    protected $themeId;
    
    /**
     * @var Cx\Core_Modules\MultiSite\Model\Entity\MailServiceServer
     */
    protected $mailServiceServer;
    
    /**
     * @var integer $mailAccountId
     */
    protected $mailAccountId;
    
    /**
     *
     * @var Cx\Core_Modules\MultiSite\Model\Entity\WebsiteCollection $websiteCollection
     */
    protected $websiteCollection;

    /**
     * @var string $mode
     */
    protected $mode;

    /**
     * @var Cx\Core_Modules\MultiSite\Model\Entity\Website $serverWebsite
     */
    protected $serverWebsite;

    /*
     * Constructor
     * */
    public function __construct($basepath, $name, $websiteServiceServer = null, \User $userObj=null, $lazyLoad = true, $themeId = 0) {
        $this->basepath = $basepath;
        $this->name = $name;
        $this->creationDate = new \DateTime();

        if ($lazyLoad) {
            return true;
        }

        $this->domains = new \Doctrine\Common\Collections\ArrayCollection();      
        $this->language = $userObj->getFrontendLanguage();
        if (!$this->language) {
            $this->language = \FWLanguage::getDefaultLangId();
        }
        $this->status = self::STATE_INIT;
        $this->mode   = \Cx\Core_Modules\MultiSite\Controller\ComponentController::WEBSITE_MODE_STANDALONE;
        $this->websiteServiceServerId = 0;
        $this->installationId = $this->generateInstalationId();
        $this->themeId = $themeId;

        if ($userObj) {
            $em      = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager();
            $objUser = $em->getRepository('\Cx\Core\User\Model\Entity\User')->findOneById($userObj->getId());
            $this->setOwner($objUser);
        }
        
        if ($websiteServiceServer) {
            $this->setWebsiteServiceServer($websiteServiceServer);
        }

        // set IP of Website
        switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
            case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_MANAGER:
                if ($this->id) {
                    break;
                }
                $resp = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnServiceServer('getDefaultWebsiteIp', array(), $this->websiteServiceServer);
                if(!$resp || $resp->status == 'error'){
                    $errMsg = isset($resp->message) ? $resp->message : '';
                    if (isset($resp->log)) {
                        \DBG::appendLogs(array_map(function($logEntry) {return '(Service: '.$this->websiteServiceServer->getLabel().') '.$logEntry;}, $resp->log));
                    }
                    throw new WebsiteException('Unable to fetch defaultWebsiteIp from Service Server: '.$errMsg);    
                }
                if (isset($resp->data->log)) {
                    \DBG::appendLogs(array_map(function($logEntry) {return '(Service: '.$this->websiteServiceServer->getLabel().') '.$logEntry;}, $resp->data->log));
                }
                $this->ipAddress = $resp->data->defaultWebsiteIp;
                break;

            case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_HYBRID:
            case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_SERVICE:
                $this->ipAddress = \Cx\Core\Setting\Controller\Setting::getValue('defaultWebsiteIp','MultiSite');
                break;

            default:
                break;
        }

        $this->secretKey = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::generateSecretKey();
        $this->validate();
        $this->codeBase = \Cx\Core\Setting\Controller\Setting::getValue('defaultCodeBase','MultiSite');
        $this->setFqdn();
        $this->setBaseDn();
    }

    public static function loadFromFileSystem($basepath, $name)
    {
        if (!file_exists($basepath.'/'.$name)) {
            throw new WebsiteException('No website found on path ' . $basepath . '/' . $name);
        }

        return new Website($basepath, $name);
    }
    
     /**
     * Set id
     *
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
   /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param \DateTime $creationDate
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;
    }

    /**
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Set codeBase
     *
     * @param string $codeBase
     */
    public function setCodeBase($codeBase)
    {
        $this->codeBase = $codeBase;
    }

    /**
     * Get codeBase
     *
     * @return string $codeBase
     */
    public function getCodeBase()
    {
        return $this->codeBase;
    }

    /**
     * Set language
     *
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * Get language
     *
     * @return string $language
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set status
     *
     * @param integer $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Get status
     *
     * @return integer $status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set websiteServiceServerId
     *
     * @param integer $websiteServiceServerId
     */
    public function setWebsiteServiceServerId($websiteServiceServerId)
    {
        $this->websiteServiceServerId = $websiteServiceServerId;
    }

    /**
     * Get websiteServiceServerId
     *
     * @return integer $websiteServiceServerId
     */
    public function getWebsiteServiceServerId()
    {
        return $this->websiteServiceServerId;
    }
    
    /**
     * Set websiteServiceServer
     *
     * @param Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer $websiteServiceServer
     */
    public function setWebsiteServiceServer(\Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer $websiteServiceServer)
    {
        if (!$websiteServiceServer) {
            return;
        }
        $this->websiteServiceServer = $websiteServiceServer;
        $this->setWebsiteServiceServerId($websiteServiceServer->getId());
    }

    /**
     * Get websiteServiceServer
     *
     * @return Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer $websiteServiceServer
     */
    public function getWebsiteServiceServer()
    {
        return $this->websiteServiceServer;
    }

    /**
     * Set the owner
     * 
     * @param MultiSiteUser $user
     */
    public function setOwner(\Cx\Core\User\Model\Entity\User $user) 
    {
        $this->owner = $user;
    }
    
    /**
     * Get the owner
     * 
     * @return MultiSiteUser
     */
    public function getOwner()
    {
        return $this->owner;
    }
    
    /**
     * Set secretKey
     *
     * @param string $secretKey
     */
    public function setSecretKey($secretKey)
    {
        $this->secretKey = $secretKey;
    }

    /**
     * Get secretKey
     *
     * @return string $secretKey
     */
    public function getSecretKey()
    {
        return $this->secretKey;
    }
    /**
     * Set ipAddress
     *
     * @param string $ipAddress
     */
    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;
    }

    /**
     * Get ipAddress
     *
     * @return string $ipAddress
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * Set themeId
     *
     * @param integer $themeId
     */
    public function setThemeId($themeId)
    {
        $this->themeId = $themeId;
    }

    /**
     * Get themeId
     *
     * @return integer $themeId
     */
    public function getThemeId()
    {
        return $this->themeId;
    }
    
     /**
     * Set installationId
     *
     * @param string $installationId
     */
    public function setInstallationId($installationId)
    {
        $this->installationId = $installationId;
    }

    /**
     * Get installationId
     *
     * @return string $installationId
     */
    public function getInstallationId()
    {
        return $this->installationId;
    }
    
    /**
     * Set the FTP user name
     * 
     * @param string $ftpUser
     */
    public function setFtpUser($ftpUser) {
        $this->ftpUser = $ftpUser;
    }
    
    /**
     * Get the FTP user name
     * 
     * @return string
     */
    public function getFtpUser() {
        return $this->ftpUser;
    }
    
    /**
     * Creates a new website
     */
    public function setup($options) {
        global $_DBCONFIG, $_ARRAYLANG, $_CORELANG;
        
        \DBG::msg('Website::setup()');
        \DBG::dump($options);
        \DBG::msg('change Website::$status from "'.$this->status.'" to "'.self::STATE_SETUP.'"');
        $this->status = self::STATE_SETUP;
        \Env::get('em')->persist($this);
        \Env::get('em')->flush();
        
        $this->websiteController = \Cx\Core_Modules\MultiSite\Controller\ComponentController::getHostingController();
        
        $websiteName = $this->getName();
        $websiteMail = $this->owner->getEmail(); 
        $websiteThemeId = $this->getThemeId(); 
        $dnsTarget = null;

        // language
        $lang = $this->owner->getBackendLangId();
        $langId = \FWLanguage::getLanguageIdByCode($lang);
        
        if ($langId === false) {
            $langId = \FWLanguage::getDefaultLangId();
        }
        $isServiceServer = true;
        //check if the current server is running as the website manager
        if ($this->websiteServiceServer instanceof \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer) {
            \DBG::msg('Website: Forward setup() to Website Service Server');
            $isServiceServer = false;
            //create user account in website service server
            $resp = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnServiceServer('createUser', array('userId' => $this->owner->getId(), 'email'  => $this->owner->getEmail()), $this->websiteServiceServer);
            if(!$resp || $resp->status == 'error'){
                \DBG::dump($resp);
                $errMsg = isset($resp->message) ? $resp->message : '';
                \DBG::dump($errMsg);
                if (isset($resp->log)) {
                    \DBG::appendLogs(array_map(function($logEntry) {return '(Service: '.$this->websiteServiceServer->getLabel().') '.$logEntry;}, $resp->log));
                }
                if (isset($resp->data) && isset($resp->data->log)) {
                    \DBG::appendLogs(array_map(function($logEntry) {return '(Service: '.$this->websiteServiceServer->getLabel().') '.$logEntry;}, $resp->data->log));
                }
                throw new WebsiteException('Problem in creating website owner ');    
            }
            if (isset($resp->data->log)) {
                \DBG::appendLogs(array_map(function($logEntry) {return '(Service: '.$this->websiteServiceServer->getLabel().') '.$logEntry;}, $resp->data->log));
            }
            //create website in website service server
            $params = array(
                'userId'      => $this->owner->getId(),
                'websiteName' => $websiteName,
                'websiteId'   => $this->getId(),
                'options'     => $options,
                'themeId'     => $websiteThemeId
                );
            $resp = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnServiceServer('createWebsite', $params, $this->websiteServiceServer);
            if(!$resp || $resp->status == 'error'){
                $errMsg = isset($resp->message) ? $resp->message : '';
                \DBG::dump($errMsg);
                if (isset($resp->log)) {
                    \DBG::appendLogs(array_map(function($logEntry) {return '(Service: '.$this->websiteServiceServer->getLabel().') '.$logEntry;}, $resp->log));
                }
                throw new WebsiteException('Problem in creating website '.serialize($errMsg));
            }
            if (isset($resp->data->log)) {
                \DBG::appendLogs(array_map(function($logEntry) {return '(Service: '.$this->websiteServiceServer->getLabel().') '.$logEntry;}, $resp->data->log));
            }
            $this->ipAddress = $resp->data->websiteIp;
            $this->codeBase  = $resp->data->codeBase;
            $this->status    = $resp->data->state;
            $this->ftpUser   = $resp->data->ftpUser;
            $ftpAccountPassword  = $resp->data->ftpPassword;
        } else {
            \DBG::msg('Website: setup process..');

            $hostController = \Cx\Core_Modules\MultiSite\Controller\ComponentController::getHostingController();
            $dnsTarget = \Cx\Core\Setting\Controller\Setting::getValue(
                'defaultWebsiteIp',
                'MultiSite'
            );
            $hostController->createWebDistribution(
                $this->getFqdn()->getName(),
                $dnsTarget
            );

            \DBG::msg('Website: setupDatabase..');
            $objDb = new \Cx\Core\Model\Model\Entity\Db($_DBCONFIG);
            $objDbUser = new \Cx\Core\Model\Model\Entity\DbUser();
            $this->setupDatabase($langId, $this->owner, $objDb, $objDbUser);

            \DBG::msg('Website: setupDataFolder..');
            $additionalConfig = $this->setupDataFolder($websiteName);

            \DBG::msg('Website: setupFtpAccount..');
            $ftpAccountPassword = $this->setupFtpAccount($websiteName);

            \DBG::msg('Website: setupConfiguration..');
            $this->setupConfiguration($websiteName, $objDb, $objDbUser);

            \DBG::msg('Website: setupMultiSiteConfig..');
            $this->setupMultiSiteConfig($websiteName);

            \DBG::msg('Website: setupSupportConfig..');
            $this->setupSupportConfig($websiteName);
            
            \DBG::msg('Website: setupLicense..');
            $this->setupLicense($options);

            \DBG::msg('Website: initializeConfig..');
            $this->initializeConfig($additionalConfig);

            \DBG::msg('Website: setupTheme..');
            $this->setupTheme($websiteThemeId);
            
            // \DBG::msg('Website: setupRobotsFile..');
            // $this->setupRobotsFile($websiteName);

            \DBG::msg('Website: createContrexxUser..');
            $this->createContrexxUser();

            \DBG::msg('Website: setup process.. DONE');

            \DBG::msg('Website: Set state to '.self::STATE_ONLINE);
            $this->status = self::STATE_ONLINE;
        }

        \Env::get('em')->persist($this);
        \Env::get('em')->flush();

        if (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite') == \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_WEBSITE) {
            throw new WebsiteException('MultiSite mode was set to Website at the end of setup process. No E-Mail was sent to '.$this->owner->getEmail());
        }
        if (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite') == \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_MANAGER
            || \Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite') == \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_HYBRID
        ) {
            $websiteDomain = $websiteName.'.'.\Cx\Core\Setting\Controller\Setting::getValue('multiSiteDomain','MultiSite');
            $websiteUrl = \Cx\Core_Modules\MultiSite\Controller\ComponentController::getApiProtocol().$websiteName.'.'.\Cx\Core\Setting\Controller\Setting::getValue('multiSiteDomain','MultiSite');

            // set user account password
            $websitePassword = '';
            $websitePasswordUrl = '';
            $websiteVerificationUrl = '';
            
            if (isset($options['initialSignUp']) && $options['initialSignUp']) {
                switch (\Cx\Core\Setting\Controller\Setting::getValue('passwordSetupMethod','MultiSite')) {
                    case 'interactive':
                        \DBG::msg('Website: generate reset password link for Cloudrexx user..');
                        $passwordBlock = 'WEBSITE_PASSWORD_INTERACTIVE';
                        $websitePasswordUrl = $this->generatePasswordRestoreUrl();
                        break;

                    case 'auto-with-verification':
                        \DBG::msg('Website: set verification state to pending on Cloudrexx user..');
                        // set state of user account to unverified
                        $this->owner->setVerified(false);
                        \Env::get('em')->flush();
                        $websiteVerificationUrl = $this->generateVerificationUrl();

                    // important: intentionally no break for this case!

                    case 'auto':
                    default:
                        \DBG::msg('Website: generate password for Cloudrexx user..');
                        $passwordBlock = 'WEBSITE_PASSWORD_AUTO';
                        $websitePassword = $this->generateAccountPassword();
                        break;
                }
                $mailTemplateKey = 'createInstance';
                
            } else {
                $params = \Cx\Core_Modules\MultiSite\Model\Event\AccessUserEventListener::fetchUserData($this->owner);
                switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                    case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_MANAGER:
                        // update user on service server
                        $resp = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnServiceServer('updateUser', $params, $this->websiteServiceServer);
                        if (!$resp || $resp->status == 'error') {
                            $errMsg = isset($resp->message) ? $resp->message : '';
                            if (isset($resp->log)) {
                                \DBG::appendLogs(array_map(function($logEntry) {return '(Service: '.$this->websiteServiceServer->getLabel().') '.$logEntry;}, $resp->log));
                            }
                        }
                        if (isset($resp->data->log)) {
                            \DBG::appendLogs(array_map(function($logEntry) {return '(Service: '.$this->websiteServiceServer->getLabel().') '.$logEntry;}, $resp->data->log));
                        }
                        // update user on newly created website
                        $resp = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnWebsite('updateUser', $params, $this);
                        if(!$resp || $resp->status == 'error'){
                            $errMsg = isset($resp->message) ? $resp->message : '';
                            if (isset($resp->log)) {
                                \DBG::appendLogs(array_map(function($logEntry) {return '(Website: '.$this->getName().') '.$logEntry;}, $resp->log));
                            }
                        }
                        if (isset($resp->data->log)) {
                            \DBG::appendLogs(array_map(function($logEntry) {return '(Website: '.$this->getName().') '.$logEntry;}, $resp->data->log));
                        }
                        break;

                    case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_HYBRID:
                    case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_SERVICE:
                        $resp = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnWebsite('updateUser', $params, $this);
                        if(!$resp || $resp->status == 'error'){
                            $errMsg = isset($resp->message) ? $resp->message : '';
                            if (isset($resp->log)) {
                                \DBG::appendLogs(array_map(function($logEntry) {return '(Website: '.$this->getName().') '.$logEntry;}, $resp->log));
                            }
                        }
                        if (isset($resp->data->log)) {
                            \DBG::appendLogs(array_map(function($logEntry) {return '(Website: '.$this->getName().') '.$logEntry;}, $resp->data->log));
                        }
                        break;
                }
                $mailTemplateKey = 'newWebsiteCreated';
            }

            \DBG::msg('Website: SETUP COMPLETED > OK');
            
            $gender = $_CORELANG['TXT_ACCESS_NOT_SPECIFIED'];
            switch ($this->owner->getUserProfile()->getGender()) {
                case 'gender_male':
                    $gender = $_CORELANG['TXT_ACCESS_MALE'];
                    break;

                case 'gender_female':
                    $gender = $_CORELANG['TXT_ACCESS_FEMALE'];
                    break;
            }
            
            $country = '';
            if ($this->owner->getUserProfile()->getCountry()) {
                $country    = \Cx\Core\Country\Controller\Country::getNameById($this->owner->getUserProfile()->getCountry());
            }
            
            //get subscription by its wbsite id
            $subscription = \Cx\Core_Modules\MultiSite\Controller\ComponentController::getSubscriptionByWebsiteId($this->getId());
            
            $productName = '';
            if ($subscription && $subscription->getProduct()) {
                $productName = $subscription->getProduct()->getName();
            }
            // write mail
            \Cx\Core\MailTemplate\Controller\MailTemplate::init('MultiSite');
            // send ADMIN mail
            \DBG::msg('Website: send notification email > ADMIN');
            \Cx\Core\MailTemplate\Controller\MailTemplate::send(array(
                'section' => 'MultiSite',
                'lang_id' => $langId,
                'key' => 'notifyAboutNewWebsite',
                //'to' => $websiteMail,
                'search' => array(
                    '[[MULTISITE_DOMAIN]]',
                    '[[WEBSITE_DOMAIN]]',
                    '[[WEBSITE_URL]]',
                    '[[WEBSITE_NAME]]',
                    '[[CUSTOMER_EMAIL]]',
                    '[[CUSTOMER_GENDER]]',
                    '[[CUSTOMER_FIRSTNAME]]',
                    '[[CUSTOMER_LASTNAME]]',
                    '[[CUSTOMER_COMPANY]]',
                    '[[CUSTOMER_ZIP]]',
                    '[[CUSTOMER_CITY]]',
                    '[[CUSTOMER_COUNTRY]]',
                    '[[CUSTOMER_PHONE]]',
                    '[[SUBSCRIPTION_NAME]]'),
                'replace' => array(
                    \Cx\Core\Setting\Controller\Setting::getValue('multiSiteDomain','MultiSite'),
                    $websiteDomain,
                    $websiteUrl,
                    $websiteName,
                    $websiteMail,
                    $gender,
                    $this->owner->getUserProfile()->getFirstname(),
                    $this->owner->getUserProfile()->getLastname(),
                    $this->owner->getUserProfile()->getCompany(),
                    $this->owner->getUserProfile()->getZip(),
                    $this->owner->getUserProfile()->getCity(),
                    $country,
                    $this->owner->getUserProfile()->getPhoneOffice(),
                    $productName),
            ));
            
            // send CUSTOMER mail
            if (isset($passwordBlock)) {
                $substitution = array(
                    $passwordBlock => array(
                        '0' => array(
                            'WEBSITE_PASSWORD' => $websitePassword,
                            'WEBSITE_MAIL' => $websiteMail,
                            'WEBSITE_PASSWORD_URL' => $websitePasswordUrl,
                        ),
                    )
                );
            } else {
                $substitution = array();
            }

            $info = array(
                'section' => 'MultiSite',
                'lang_id' => $langId,
                'key' => $mailTemplateKey,
                'to' => $websiteMail,
                'search' => array('[[WEBSITE_DOMAIN]]', '[[WEBSITE_NAME]]', '[[WEBSITE_MAIL]]'),
                'replace' => array($websiteDomain, $websiteName, $websiteMail),
                'substitution' => $substitution
            );
            // If email verification is required,
            // parse related block in notification email.
            if ($websiteVerificationUrl) {
                $info['substitution']['WEBSITE_EMAIL_VERIFICATION'] = array(
                    '0' => array(
                        'WEBSITE_VERIFICATION_URL' => $websiteVerificationUrl,
                    )
                );
            }
            //If $ftpAccountPassword is set, then add related entry to substitution
            if (isset($ftpAccountPassword)) {
                $info['substitution']['WEBSITE_FTP'] = array(
                    '0' => array(
                        'WEBSITE_DOMAIN'       => $websiteDomain,
                        'WEBSITE_FTP_USER'     => $this->ftpUser,
                        'WEBSITE_FTP_PASSWORD' => $ftpAccountPassword
                    )
                );
            }
            \DBG::msg('Website: send notification email > CUSTOMER');
            if (!\Cx\Core\MailTemplate\Controller\MailTemplate::send($info)) {
                throw new WebsiteException(__METHOD__.': Unable to send welcome e-mail to user');
            }
            \DBG::msg('Website: SETUP COMPLETED > ALL DONE');
            return array(
                'status' => 'success',
            );
        }

        \DBG::msg('Website: send setup response to Manager..');
        return array(
            'status'      => 'success',
            'websiteIp'   => $dnsTarget,
            'codeBase'    => $this->codeBase,
            'state'       => $this->status,
            'ftpPassword' => $ftpAccountPassword,
            'ftpUser'     => $this->ftpUser,
            'log'         => \DBG::getMemoryLogs(),
        );
    }

    /**
     * Validate website entity.
     * Checks if the name of the website is valid and unique.
     */
    public function validate()
    {
        global $_ARRAYLANG;

        \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::loadLanguageData();
        $websiteName = $this->getName();

        // verify that name is not a blocked word
        $unavailablePrefixesValue = explode(',',\Cx\Core\Setting\Controller\Setting::getValue('unavailablePrefixes','MultiSite'));
        if (in_array($websiteName, $unavailablePrefixesValue)) {
            throw new WebsiteException(sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_ALREADY_EXISTS'], "<strong>$websiteName</strong>"));
        }

        // verify that name complies with naming scheme
        if (preg_match('/[^a-z0-9]/', $websiteName)) {
            throw new WebsiteException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_NAME_WRONG_CHARS']);
        }
        if (strlen($websiteName) < \Cx\Core\Setting\Controller\Setting::getValue('websiteNameMinLength','MultiSite')) {
            throw new WebsiteException(sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_NAME_TOO_SHORT'], \Cx\Core\Setting\Controller\Setting::getValue('websiteNameMinLength','MultiSite')));
        }
        if (strlen($websiteName) > \Cx\Core\Setting\Controller\Setting::getValue('websiteNameMaxLength','MultiSite')) {
            throw new WebsiteException(sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_NAME_TOO_LONG'], \Cx\Core\Setting\Controller\Setting::getValue('websiteNameMaxLength','MultiSite')));
        }

        // existing website
        $website = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website')->findOneBy(array('name' => $websiteName));
        if ($website && $website != $this) {
            throw new WebsiteException(sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_ALREADY_EXISTS'], "<strong>$websiteName</strong>"));
        }
    }
    
    /*
    * function setupDatabase to create database
    * and populate database with basic data
    * @param $langId language ID of the website
    * */
    protected function setupDatabase($langId, $objUser, $objDb, $objDbUser){
        $objDbUser->setPassword(\User::make_password(8, true));
        $objDbUser->setName(\Cx\Core\Setting\Controller\Setting::getValue('websiteDatabaseUserPrefix','MultiSite').$this->id);      

        $objDb->setHost(\Cx\Core\Setting\Controller\Setting::getValue('websiteDatabaseHost','MultiSite'));
        $objDb->setName(\Cx\Core\Setting\Controller\Setting::getValue('websiteDatabasePrefix','MultiSite').$this->id);

        $websitedb = $this->initDatabase($objDb, $objDbUser);
        if (!$websitedb) {
            throw new WebsiteException('Database could not be created');
        }
        if (!$this->initDbStructure($objUser, $objDbUser, $langId, $websitedb)) {
            throw new WebsiteException('Database structure could not be initialized');
        }
        if (!$this->initDbData($objUser, $objDbUser, $langId, $websitedb)) {
            throw new WebsiteException('Database data could not be initialized');
        }    
    }
    
    /**
     * Create the necessary files and folders for the website
     * This method is executed on ServiceServer only!
     * @param $websiteName name of the website
     * @return array Key=>value array with additional settings for Config.yml
     */
    protected function setupDataFolder($websiteName) {
        // ensure our folder exists
        $hostingController = \Cx\Core_Modules\MultiSite\Controller\ComponentController::getHostingController();
        return $hostingController->createUserStorage($websiteName, $this->codeBase);
    }    
     /*
    * function setupConfiguration to create configuration
    * files
    * @param $website Name name of the website
    * */
    protected function setupConfiguration($websiteName, $objDb, $objDbUser){
        global $_PATHCONFIG;

        // setup base configuration (configuration.php)
        try {
            $newConf = new \Cx\Lib\FileSystem\File(\Cx\Core\Setting\Controller\Setting::getValue('websitePath','MultiSite').'/'.$websiteName . '/config/configuration.php');
            $newConfData = $newConf->getData();
            $installationRootPath = !empty($this->codeBase) ? \Cx\Core\Setting\Controller\Setting::getValue('codeBaseRepository','MultiSite').'/'.$this->codeBase : $_PATHCONFIG['ascms_installation_root'];

            // set database configuration
            $newConfData = preg_replace('/\\$_DBCONFIG\\[\'host\'\\] = \'.*?\';/', '$_DBCONFIG[\'host\'] = \'' .$objDb->getHost() . '\';', $newConfData);
            $newConfData = preg_replace('/\\$_DBCONFIG\\[\'tablePrefix\'\\] = \'.*?\';/', '$_DBCONFIG[\'tablePrefix\'] = \'' .$objDb->getTablePrefix() . '\';', $newConfData);
            $newConfData = preg_replace('/\\$_DBCONFIG\\[\'dbType\'\\] = \'.*?\';/', '$_DBCONFIG[\'dbType\'] = \'' .$objDb->getdbType() . '\';', $newConfData);
            $newConfData = preg_replace('/\\$_DBCONFIG\\[\'charset\'\\] = \'.*?\';/', '$_DBCONFIG[\'charset\'] = \'' .$objDb->getCharset() . '\';', $newConfData);
            $newConfData = preg_replace('/\\$_DBCONFIG\\[\'timezone\'\\] = \'.*?\';/', '$_DBCONFIG[\'timezone\'] = \'' .$objDb->getTimezone() . '\';', $newConfData);
            $newConfData = preg_replace('/\\$_DBCONFIG\\[\'database\'\\] = \'.*?\';/', '$_DBCONFIG[\'database\'] = \'' .$objDb->getName() . '\';', $newConfData);
            $newConfData = preg_replace('/\\$_DBCONFIG\\[\'user\'\\] = \'.*?\';/', '$_DBCONFIG[\'user\'] = \'' . $objDbUser->getName() . '\';', $newConfData);
            $newConfData = preg_replace('/\\$_DBCONFIG\\[\'password\'\\] = \'.*?\';/', '$_DBCONFIG[\'password\'] = \'' . $objDbUser->getPassword() . '\';', $newConfData);
            
            // set path configuration
            $newConfData = preg_replace('/\\$_PATHCONFIG\\[\'ascms_root\'\\] = \'.*?\';/', '$_PATHCONFIG[\'ascms_root\'] = \'' . \Cx\Core\Setting\Controller\Setting::getValue('websitePath','MultiSite').'/'.$websiteName . '\';', $newConfData);
            $newConfData = preg_replace('/\\$_PATHCONFIG\\[\'ascms_installation_root\'\\] = \'.*?\';/', '$_PATHCONFIG[\'ascms_installation_root\'] = \'' . $installationRootPath . '\';', $newConfData);          
                        
            $newConf->write($newConfData);
        } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
            throw new WebsiteException('Unable to setup configuration file: '.$e->getMessage());
        }

        // setup basic configuration (settings.php)
        try {
            $newSettings = new \Cx\Lib\FileSystem\File(\Cx\Core\Setting\Controller\Setting::getValue('websitePath','MultiSite').'/'.$websiteName . '/config/settings.php');
            $settingsData = preg_replace_callback(
                '/(\$_CONFIG\[([\'"])((?:(?!\2).)*)\2\]\s*=\s*([\'"]))(?:(?:(?!\4).)*)(\4;)/',
                function($match) {
                    $originalString = $match[0];
                    $optionString = $match[1];
                    $settingsOption = $match[3];
                    $delimiter = $match[4];
                    $closure = $match[5];
                    $escapedDelimiter = addslashes($delimiter);
                    switch ($settingsOption) {
                        case 'installationId':
                            $value = $this->installationId;
                            break;
                        default:
                            return $originalString;
                            break;
                    }
                    $escapedValue = str_replace($delimiter, $escapedDelimiter, $value);
                    return  $optionString . $escapedValue . $closure;
                },
                $newSettings->getData()
            );
            $newSettings->write($settingsData);
        } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
            throw new WebsiteException('Unable to setup settings file: '.$e->getMessage());
        }
          
    }

    protected function initializeConfig($additionalConfig = array()) {
        try {
            $params = array_merge(
                array(
                    'dashboardNewsSrc' => \Cx\Core\Setting\Controller\Setting::getValue('dashboardNewsSrc','MultiSite'),
                    'coreAdminEmail'   => $this->owner->getEmail(),
                    'contactFormEmail' => $this->owner->getEmail(),
                    // we should migrate this to locales
                    // this only works as long as the website skeleton data does use the same locale/language IDs as the website service and master
                    'defaultLocaleId'  => $this->language,
                    'defaultLanguageId'=> $this->language,
                ),
                $additionalConfig
            );
            $resp = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnWebsite('setupConfig', $params, $this);
            if(!$resp || $resp->status == 'error'){
                $errMsg = isset($resp->message) ? $resp->message : '';
                if (isset($resp->log)) {
                    \DBG::appendLogs(array_map(function($logEntry) {return '(Website: '.$this->getName().') '.$logEntry;}, $resp->log));
                }
                throw new WebsiteException($errMsg);    
            }
            if (isset($resp->data->log)) {
                \DBG::appendLogs(array_map(function($logEntry) {return '(Website: '.$this->getName().') '.$logEntry;}, $resp->data->log));
            }
        } catch (\Exception $e) {
            throw new WebsiteException('Unable to setup config Config.yml on Website: '.$e->getMessage());    
        }
    }

    protected function setupMultiSiteConfig($websiteName)
    {
        $websitePath = \Cx\Core\Setting\Controller\Setting::getValue('websitePath','MultiSite');
        $websiteConfigPath = $websitePath . '/' . $websiteName . \Env::get('cx')->getConfigFolderName();

        $config = \Env::get('config');
        $serviceInstallationId = $config['installationId'];
        $serviceHostname = $config['domainUrl'];
        $websiteHttpAuthMethod   = \Cx\Core\Setting\Controller\Setting::getValue('websiteHttpAuthMethod','MultiSite');
        $websiteHttpAuthUsername = \Cx\Core\Setting\Controller\Setting::getValue('websiteHttpAuthUsername','MultiSite');
        $websiteHttpAuthPassword = \Cx\Core\Setting\Controller\Setting::getValue('websiteHttpAuthPassword','MultiSite');
        
        try {
            \Cx\Core\Setting\Controller\Setting::init('MultiSite', 'config','FileSystem', $websiteConfigPath);
            if (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('mode', \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_WEBSITE, 1,
                \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN, \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_WEBSITE.':'.\Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_WEBSITE, 'config')){
                    throw new WebsiteException("Failed to add Setting entry for MultiSite mode");
            }
            //website group
            \Cx\Core\Setting\Controller\Setting::init('MultiSite', 'website','FileSystem', $websiteConfigPath);
            if (\Cx\Core\Setting\Controller\Setting::getValue('serviceHostname','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('serviceHostname', $serviceHostname, 2,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'website')){
                    throw new WebsiteException("Failed to add Setting entry for Hostname of Website Service");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('serviceSecretKey','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('serviceSecretKey', $this->secretKey, 3,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'website')){
                    throw new WebsiteException("Failed to add Setting entry for SecretKey of Website Service");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('serviceInstallationId','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('serviceInstallationId', $serviceInstallationId, 4,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'website')){
                    throw new WebsiteException("Failed to add Setting entry for InstallationId of Website Service");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('websiteUserId','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('websiteUserId', 0, 5,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'website')){
                    throw new WebsiteException("Failed to add Setting entry for InstallationId of Website User Id");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('serviceHttpAuthMethod','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('serviceHttpAuthMethod', $websiteHttpAuthMethod, 5,
                \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN, 'none:none, basic:basic, digest:digest', 'website')){
                    throw new WebsiteException("Failed to add Setting entry for HTTP Authentication Method of Website Service");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('serviceHttpAuthUsername','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('serviceHttpAuthUsername', $websiteHttpAuthUsername, 6,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'website')){
                    throw new WebsiteException("Failed to add Setting entry for HTTP Authentication Username of Website Service");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('serviceHttpAuthPassword','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('serviceHttpAuthPassword', $websiteHttpAuthPassword, 7,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'website')){
                    throw new WebsiteException("Failed to add Setting entry for HTTP Authentication Password of Website Service");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('websiteState','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('websiteState', $this->status, 8,
                \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN, self::STATE_ONLINE.':'.self::STATE_ONLINE.','.self::STATE_OFFLINE.':'.self::STATE_OFFLINE.','.self::STATE_INIT.':'.self::STATE_INIT.','.self::STATE_SETUP.':'.self::STATE_SETUP, 'website')){
                    throw new WebsiteException("Failed to add website entry for website state");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('websiteName','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('websiteName', $this->name, 9,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'website')){
                    throw new WebsiteException("Failed to add website entry for website name");
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('websiteFtpUser','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('websiteFtpUser', $this->ftpUser, 10,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'website')){
                    throw new WebsiteException("Failed to add website entry for website FTP user");
            }
            $standalone = \Cx\Core_Modules\MultiSite\Controller\ComponentController::WEBSITE_MODE_STANDALONE;
            $client     = \Cx\Core_Modules\MultiSite\Controller\ComponentController::WEBSITE_MODE_CLIENT;
            $server     = \Cx\Core_Modules\MultiSite\Controller\ComponentController::WEBSITE_MODE_SERVER;
            if (\Cx\Core\Setting\Controller\Setting::getValue('website_mode','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('website_mode', 'standalone', 11,
                \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN, $standalone.':'.$standalone.','.$client.':'.$client.','.$server.':'.$server, 'website')
            ) {
                throw new MultiSiteException('Failed to add Setting entry for website mode');
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('website_server','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('website_server', '', 12,
                \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN, '{src:\\Cx\\Core_Modules\\MultiSite\\Controller\\ComponentController::getServerWebsiteList()}', 'website')
            ) {
                throw new MultiSiteException('Failed to add Setting entry for website server');
            }
            if (\Cx\Core\Setting\Controller\Setting::getValue('website_shared_folder','MultiSite') === NULL
                && !\Cx\Core\Setting\Controller\Setting::add('website_shared_folder', $this->cx->getWebsiteImagesWebPath(), 13,
                \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, '', 'website')
            ) {
                throw new MultiSiteException('Failed to add Setting entry for website shared folder');
            }

        } catch (\Exception $e) {
            // we must re-initialize the original MultiSite settings of the main installation
            \Cx\Core\Setting\Controller\Setting::init('MultiSite', '','FileSystem', null, \Cx\Core\Setting\Controller\Setting::REPOPULATE);
            throw new WebsiteException('Error in setting up the MultiSite configuration:'. $e->getMessage());
        }

        // we must re-initialize the original MultiSite settings of the main installation
        \Cx\Core\Setting\Controller\Setting::init('MultiSite', '','FileSystem', null, \Cx\Core\Setting\Controller\Setting::REPOPULATE);
    }

    /**
     * setup support configuration
     * 
     * @param string $websiteName websitename
     * 
     * @throws WebsiteException
     */
    protected function setupSupportConfig($websiteName) {
        $websitePath = \Cx\Core\Setting\Controller\Setting::getValue('websitePath','MultiSite');
        $websiteConfigPath = $websitePath . '/' . $websiteName . \Env::get('cx')->getConfigFolderName();

        $faqUrl = \Cx\Core\Setting\Controller\Setting::getValue('supportFaqUrl','MultiSite');
        $recipientMailAddress = \Cx\Core\Setting\Controller\Setting::getValue('supportRecipientMailAddress','MultiSite');

        try {
            \Cx\Core\Setting\Controller\Setting::init('Support', 'setup', 'Yaml', $websiteConfigPath);
            if (!\Cx\Core\Setting\Controller\Setting::isDefined('faqUrl') && !\Cx\Core\Setting\Controller\Setting::add('faqUrl', $faqUrl, 1, \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'setup')) {
                throw new WebsiteException("Failed to add Setting entry for faq url");
            }
            if (!\Cx\Core\Setting\Controller\Setting::isDefined('recipientMailAddress') && !\Cx\Core\Setting\Controller\Setting::add('recipientMailAddress', $recipientMailAddress, 2, \Cx\Core\Setting\Controller\Setting::TYPE_TEXT, null, 'setup')) {
                throw new WebsiteException("Failed to add Setting entry for recipient mail address");
            }
        } catch (\Exception $e) {
            // we must re-initialize the original MultiSite settings of the main installation
            \Cx\Core\Setting\Controller\Setting::init('MultiSite', '', 'FileSystem', null, \Cx\Core\Setting\Controller\Setting::REPOPULATE);
            throw new WebsiteException('Error in setting up the Support configuration:' . $e->getMessage());
        }
        // we must re-initialize the original MultiSite settings of the main installation
        \Cx\Core\Setting\Controller\Setting::init('MultiSite', '', 'FileSystem', null, \Cx\Core\Setting\Controller\Setting::REPOPULATE);
    }

    /**
     * setup Robots File
     * 
     * @param string $websiteName websitename
     * 
     * @throws WebsiteException
     */
    protected function setupRobotsFile($websiteName) {
        try {
            $codeBaseOfWebsite = !empty($this->codeBase) ? \Cx\Core\Setting\Controller\Setting::getValue('codeBaseRepository','MultiSite').'/'.$this->codeBase  :  \Env::get('cx')->getCodeBaseDocumentRootPath();
            $setupRobotFile = new \Cx\Lib\FileSystem\File($codeBaseOfWebsite . \Env::get('cx')->getCoreModuleFolderName() . '/MultiSite/Data/WebsiteSkeleton/robots.txt');
            $setupRobotFile->copy(\Cx\Core\Setting\Controller\Setting::getValue('websitePath','MultiSite').'/'.$websiteName . '/robots.txt');
        }  catch (\Cx\Lib\FileSystem\FileSystemException $e) {
            throw new WebsiteException('Unable to setup robot file: '.$e->getMessage());
        }
    }

    protected function createContrexxUser()
    {
        $params = array(
            'email' => $this->owner->getEmail(),
            'active'=> 1,
            'admin' => 1,
            // assign user to first user group 
            'groups' => array(1),
        );
        $resp = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnWebsite('createUser', $params, $this);
        if(!$resp || $resp->status == 'error'){
            $errMsg = isset($resp->message) ? $resp->message : '';
            \DBG::dump($resp);
            \DBG::msg($errMsg);
            \DBG::appendLogs(array_map(function($logEntry) {return '(Website: '.$this->getName().') '.$logEntry;}, $resp->log));
            throw new WebsiteException('Unable to create admin user account.');
        }
        if (isset($resp->data->log)) {
            \DBG::appendLogs(array_map(function($logEntry) {return '(Website: '.$this->getName().') '.$logEntry;}, $resp->data->log));
        }
    }

    /**
     * Removes non-activated websites that are older than 60 days
    */
    public function cleanup() {
throw new WebsiteException('implement secret-key algorithm first!');
        $instRepo = \Env::get('em')->getRepository('\Cx\Core_Modules\MultiSite\Model\Entity\Website');
        $websites = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($instRepo->findAll());
        $someTimeAgo = strtotime('60 days ago');
        foreach ($websites as $website) {
            if (!$website->isActivated() && $website->getCreateDate() < $someTimeAgo) {
                $this->removeWebsite($website->getName());
            }
        }
    }
    
    /**
     * Completely removes an website
     */
    public function destroy() {
        global $_DBCONFIG;
        
        \DBG::msg('MultiSite (Website): destroy');
        
        try {
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_MANAGER:
                    // remove the mail service of website
                    if ($this->mailServiceServer && $this->mailAccountId) {
                        $deleteMailService = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnManager('deleteMailServiceAccount', array('websiteId' => $this->id));
                        if (!$deleteMailService || $deleteMailService->status == 'error') {
                            $errorMsg = isset($deleteMailService->message) ? $deleteMailService->message : '';
                            if (isset($deleteMailService->log)) {
                                \DBG::appendLogs(array_map(function($logEntry) {return '(Mail Service: '.$this->mailServiceServer->getLabel().') '.$logEntry;}, $deleteMailService->log));
                            }
                            throw new WebsiteException('Unable to delete the mail service: ' . serialize($errorMsg));
                        }  
                    }
                    $resp = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnServiceServer('destroyWebsite', array('websiteId' => $this->id), $this->websiteServiceServer);
                    if (!$resp || $resp->status == 'error') {
                        $errMsg = isset($resp->message) ? $resp->message : '';
                        if (isset($resp->log)) {
                            \DBG::appendLogs(array_map(function($logEntry) {return '(Service: '.$this->websiteServiceServer->getLabel().') '.$logEntry;}, $resp->log));
                        }
                        throw new WebsiteException('Unable to delete the website: ' . serialize($errMsg));
                    }
                    if (isset($resp->data->log)) {
                        \DBG::appendLogs(array_map(function($logEntry) {return '(Service: '.$this->websiteServiceServer->getLabel().') '.$logEntry;}, $resp->data->log));
                    }
                    break;
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_SERVICE:
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_HYBRID:
                    //remove the FTP Account if there
                    $hostingController = \Cx\Core_Modules\MultiSite\Controller\ComponentController::getHostingController();
                    
                    if ($this->ftpUser) {
                        $ftpAccounts = $hostingController->getAllEndUserAccounts();
                        if (in_array($this->ftpUser, $ftpAccounts)) {
                            if (!$hostingController->removeEndUserAccount($this->ftpUser)) {
                                throw new WebsiteException('Unable to delete the FTP Account');
                            }
                        }
                    }

                    //remove the database and its user
                    $objDb = new \Cx\Core\Model\Model\Entity\Db($_DBCONFIG);
                    $objDb->setHost(\Cx\Core\Setting\Controller\Setting::getValue('websiteDatabaseHost','MultiSite'));
                    $objDb->setName(\Cx\Core\Setting\Controller\Setting::getValue('websiteDatabasePrefix','MultiSite') . $this->id);
                    
                    //remove the database user
                    $objDbUser = new \Cx\Core\Model\Model\Entity\DbUser();
                    $objDbUser->setName(\Cx\Core\Setting\Controller\Setting::getValue('websiteDatabaseUserPrefix','MultiSite') . $this->id);
                    $removedDbUser = $hostingController->removeDbUser($objDbUser, $objDb);

                    //remove the database
                    if ($removedDbUser) {
                        $hostingController->removeDb($objDb);
                    }
                    //remove the website's data repository
                    $hostingController->deleteUserStorage($this->getName());

                    //unmap all the domains
                    foreach ($this->domains as $domain) {
                        \DBG::msg(__METHOD__.': Remove domain '.$domain->getName());
                        \Env::get('em')->remove($domain);
                        \Env::get('em')->getUnitOfWork()->computeChangeSet(\Env::get('em')->getClassMetadata('Cx\Core_Modules\MultiSite\Model\Entity\Domain'), $domain);
                    }

                    // Delete web distribution
                    $hostingController->deleteWebDistribution($this->getName());
                    break;
            }
        } catch (\Exception $e) {
            throw new WebsiteException('Website (destroy): Unable to delete the website' . $e->getMessage());
        }
    }
    
    protected function initDatabase($objDb, $objDbUser)
    {
        //call db controller method to create new db
        $this->websiteController->createDb($objDb, $objDbUser);

        //call core db class to create db connection object
        $dbClass = new \Cx\Core\Model\Db($objDb, $objDbUser, null);
        $websitedb = $dbClass->getAdoDb();       

        return $websitedb;
    }

    protected function initDbStructure($objUser, $objDbUser, $langId, $websitedb) {
        return $this->initDb('structure', $objUser, $objDbUser, $langId, $websitedb);
    }
    
    protected function initDbData($objUser, $objDbUser, $langId, $initDbData) {
        return $this->initDb('data', $objUser, $objDbUser, $langId, $initDbData);
    }
    
    /**
     *
     * @param type $dbPrefix
     * @param type $type
     * @param type $mail
     * @return boolean|string
     * @throws WebsiteException
     */
    protected function initDb($type, $objUser, $objDbUser, $langId, $websitedb) {
        $dumpFilePath = !empty($this->codeBase) ? \Cx\Core\Setting\Controller\Setting::getValue('codeBaseRepository','MultiSite').'/'.$this->codeBase  :  \Env::get('cx')->getCodeBaseDocumentRootPath();
        $fp = @fopen($dumpFilePath.'/installer/data/contrexx_dump_' . $type . '.sql', "r");
        if ($fp === false) {
            throw new WebsiteException('File not found');
        }

        $line = 1;
        if (!isset($_SESSION['MultiSite'])) {
            $_SESSION['MultiSite'] = array();
        }
        if (!isset($_SESSION['MultiSite']['sqlqueries'])) {
            $_SESSION['MultiSite']['sqlqueries'] = array();
        }
        if (!isset($_SESSION['MultiSite']['sqlqueries'][$type])) {
            $_SESSION['MultiSite']['sqlqueries'][$type] = 0;
        }
        $sqlQuery = '';
        $statusMsg = '';
        while (!feof($fp)) {
            if ($_SESSION['MultiSite']['sqlqueries'][$type] >= $line) {
                $line++;
                continue;
            }
            $buffer = fgets($fp);
            if ((substr($buffer,0,1) != "#") && (substr($buffer,0,2) != "--")) {
                $sqlQuery .= $buffer;
                if (preg_match("/;[ \t\r\n]*$/", $buffer)) {
                    // Don't have to replace prefix, because it is in a separate db.
                    // This would be required when using single-database-mode.
                    // Single-database-mode has not yet been implemented.
                    //$sqlQuery = preg_replace($dbPrefixRegexp, '`'.$dbsuffix.'$1`', $sqlQuery);
                    $sqlQuery = preg_replace('#CONSTRAINT(\s)*`([0-9a-z_]*)`(\s)*FOREIGN KEY#', 'CONSTRAINT FOREIGN KEY', $sqlQuery);
                    $sqlQuery = preg_replace('/TYPE=/', 'ENGINE=', $sqlQuery);
                    $result = $websitedb->Execute($sqlQuery);
                    if ($result === false) {
                        $statusMsg .= "<br />".htmlentities($sqlQuery, ENT_QUOTES, 'UTF-8')."<br /> (".$websitedb->ErrorMsg().")<br />";
                        return $statusMsg;
/*                    } else {
                        echo $sqlQuery;*/
                    }
                    $sqlQuery = '';
                }
            }
            $_SESSION['MultiSite']['sqlqueries'][$type] = $line;
            $line++;
        }
        
        if ($type == 'data') {
// TODO: create default user
            // set default language for user
            $result = $websitedb->Execute(
                    'UPDATE `contrexx_access_users`
                        SET `frontend_lang_id` = ' . $langId . ',
                            `backend_lang_id`  = ' . $langId . '
                        WHERE `email` = \'' . $objUser->getEmail() . '\''
            );
            if ($result === false) {
                $statusMsg .= "<br />".htmlentities($sqlQuery, ENT_QUOTES, 'UTF-8')."<br /> (".$websitedb->ErrorMsg().")<br />";
                return $statusMsg;
            }

            // set default language for installation
            $result = $websitedb->Execute('
                    UPDATE
                        `contrexx_languages`
                    SET
                        `is_default` =
                            CASE `id`
                                WHEN ' . $langId . '
                                THEN \'true\'
                                ELSE \'false\'
                            END'
            );
            if ($result === false) {
                $statusMsg .= "<br />".htmlentities($sqlQuery, ENT_QUOTES, 'UTF-8')."<br /> (".$websitedb->ErrorMsg().")<br />";
                return $statusMsg;
            }
        }
        
        global $_DBCONFIG;
        unset($_SESSION['MultiSite']['sqlqueries'][$type]);

        if (empty($statusMsg)) {
            return true;
        } else {
            //echo $statusMsg;
            return $statusMsg;
        }
    }

    function generateInstalationId(){
        $randomHash = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::generateSecretKey();
        $installationId = $randomHash . str_pad(dechex(crc32($randomHash)), 8, '0', STR_PAD_LEFT);    
        return $installationId;
    }

    /**
     * Set Fqdn
     *
     */    
    function setFqdn(){
        $config = \Env::get('config');
        if (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite') == \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_MANAGER) {
            $serviceServerHostname = $this->websiteServiceServer->getHostname();
        } else {
            $serviceServerHostname = $config['domainUrl'];
        }
        $fqdn = new Domain($this->name.'.'.$serviceServerHostname);
        $fqdn->setType(Domain::TYPE_FQDN);
        $fqdn->setComponentType(\Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_WEBSITE);
        $this->mapDomain($fqdn);
        \Env::get('em')->persist($fqdn);
    }
    
    /**
     * get Fqdn
     *
     */    
    public function getFqdn(){
        // fetch FQDN from Domain repository
        if (!$this->fqdn) {
            foreach ($this->domains as $domain) {
                if ($domain->getType() == Domain::TYPE_FQDN) {
                    $this->fqdn = $domain;
                    break;
                }
            }
        }

        return $this->fqdn;
    }   
    
    /**
     * Set BaseDn
     *
     */    
    function setBaseDn(){
        $baseDn = new Domain($this->name.'.'.\Cx\Core\Setting\Controller\Setting::getValue('multiSiteDomain','MultiSite'));
        $baseDn->setType(Domain::TYPE_BASE_DOMAIN);
        $baseDn->setComponentType(\Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_WEBSITE);
        if (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite') == \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_HYBRID) {
            return;
        }
        $this->mapDomain($baseDn);
        \Env::get('em')->persist($baseDn);
    }
    
    /**
     * Get BaseDn
     *
     */    
    public function getBaseDn(){
        // fetch baseDn from Domain repository
        if (!$this->baseDn) {
            foreach ($this->domains as $domain) {
                if ($domain->getType() == Domain::TYPE_BASE_DOMAIN) {
                    $this->baseDn = $domain;
                    break;
                }
            }
        }
        if (!$this->baseDn) {
            return $this->getFqdn();
        }

        return $this->baseDn;
    }
    
    /**
     * Get mailDn
     * 
     * @return $mailDn Cx\Core_Modules\MultiSite\Model\Entity\Domain
     */
    public function getMailDn() {
        // fetch mailDn from Domain repository
        if (!$this->mailDn) {
            foreach ($this->domains as $domain) {
                if ($domain->getType() == Domain::TYPE_MAIL_DOMAIN) {
                    $this->mailDn = $domain;
                    break;
                }
            }
        }

        return $this->mailDn;
    }
    
    /**
     * Set mailDn
     */
    public function setMailDn() {
        $mailDn = new Domain($this->name . '.'. 'mail' . '.' . \Cx\Core\Setting\Controller\Setting::getValue('multiSiteDomain','MultiSite'));
        $mailDn->setType(Domain::TYPE_MAIL_DOMAIN);
        $mailDn->setComponentType(\Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_WEBSITE);
        $mailDn->setComponentId($this->getId());
        $this->mapDomain($mailDn);
        \Env::get('em')->persist($mailDn);
    }
    
    /**
     * Get webmailDn
     * 
     * @return $webmailDn Cx\Core_Modules\MultiSite\Model\Entity\Domain
     */
    public function getWebmailDn() {
        // fetch webmailDn from Domain repository
        if (!$this->webmailDn) {
            foreach ($this->domains as $domain) {
                if ($domain->getType() == Domain::TYPE_WEBMAIL_DOMAIN) {
                    $this->webmailDn= $domain;
                    break;
                }
            }
        }

        return $this->webmailDn;
    }
    
    /**
     * Set webmailDn
     */
    public function setWebmailDn() {
        $webmailDn = new Domain($this->name . '.'. 'webmail' . '.' . \Cx\Core\Setting\Controller\Setting::getValue('multiSiteDomain','MultiSite'));
        $webmailDn->setType(Domain::TYPE_WEBMAIL_DOMAIN);
        $webmailDn->setComponentType(\Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_WEBSITE);
        $webmailDn->setComponentId($this->getId());
        $this->mapDomain($webmailDn);
        \Env::get('em')->persist($webmailDn);
    }
    
    /**
     * Get DomainAliases
     *
     */   
    public function getDomainAliases(){
        // fetch domain aliases from Domain repository
        if (!$this->domainAliases) {
            $this->domainAliases = array();
            foreach ($this->domains as $domain) {
                if ($domain->getType() == Domain::TYPE_EXTERNAL_DOMAIN) {
                    $this->domainAliases[] = $domain;
                }
            }
        }

        return $this->domainAliases;
    }

    /**
     * Get domains
     *
     * @return Doctrine\Common\Collections\Collection $domains
     */
    public function getDomains() {
        return $this->domains;
    }
    
    /**
     * mapDomain
     * 
     * @param Cx\Core_Modules\MultiSite\Model\Entity\Domain $domain
     */  
    public function mapDomain(Domain $domain) {
        $domain->setWebsite($this);
        $this->domains[] = $domain;

        switch ($domain->getType()) {
            case DOMAIN::TYPE_FQDN:
                $this->fqdn = $domain;
                break;

            case DOMAIN::TYPE_BASE_DOMAIN:
                $this->baseDn = $domain;
                break;
            
            case DOMAIN::TYPE_MAIL_DOMAIN:
                $this->mailDn = $domain;
                break;
            
            case DOMAIN::TYPE_WEBMAIL_DOMAIN:
                $this->webmailDn = $domain;
                break;
            
            case DOMAIN::TYPE_EXTERNAL_DOMAIN:
            default:
                $domain->settype(DOMAIN::TYPE_EXTERNAL_DOMAIN);
                $this->domainAliases[] = $domain;
                break;
        }
    }
    
    /**
     * unMapDomain
     *
     * @param Domain $domain
     */  
    public function unMapDomain($domain){
        foreach ($this->getDomains() as $mappedDomain) {
            if($mappedDomain == $domain) {
                \Env::get('em')->remove($domain);
                break;
            }   
        }
    }
    
    /**
     * Set up the license
     * 
     * @param array $options
     * 
     * @return boolean
     */
    public function setupLicense($options)
    {
        $websiteTemplateRepo    = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\WebsiteTemplate');
        $defaultWebsiteTemplate = \Cx\Core\Setting\Controller\Setting::getValue('defaultWebsiteTemplate','MultiSite');
        
        //If the $options['websiteTemplate] is empty, take value from default websiteTemplate
        if (empty($options['websiteTemplate'])) {
            $options['websiteTemplate'] = $defaultWebsiteTemplate;
        }
        
        $websiteTemplate = $websiteTemplateRepo->findOneBy(array('id' => $options['websiteTemplate']));
        
        if (!$websiteTemplate) {
            $websiteTemplate = $websiteTemplateRepo->findOneBy(array('id' => $defaultWebsiteTemplate));
        }
        
        $legalComponents   = $websiteTemplate->getLicensedComponents();
        $dashboardMessages = $websiteTemplate->getLicenseMessage();
        $mailServiceServer = $this->getMailServiceServer();
        
        if (!\FWValidator::isEmpty($mailServiceServer) && !\FWValidator::isEmpty($this->getMailAccountId())) {
            $mailServiceConfig = $mailServiceServer->getConfig();
            $additionalData = null;
            if (!\FWValidator::isEmpty($legalComponents)) {
                foreach ($legalComponents as $legalComponent) {
                    if (isset($legalComponent['MultiSite']) && isset($legalComponent['MultiSite']['Mail'])) {
                        $additionalData = $legalComponent['MultiSite']['Mail'];
                        break;
                    }
                }
            }

            $showMailService   = (   !\FWValidator::isEmpty($additionalData) 
                                  && isset($additionalData['service']) 
                                  && !\FWValidator::isEmpty($additionalData['service']));
            $mailServiceStatus = ($showMailService) ? 'enableMailService' : 'disableMailService';
            $mailServicePlan   = (!\FWValidator::isEmpty($additionalData) && isset($additionalData['plan'])) ? $additionalData['plan'] : null;
            $planId            = (!\FWValidator::isEmpty($mailServiceConfig) 
                                    && isset($mailServiceConfig['planId'][$mailServicePlan])) 
                                        ? $mailServiceConfig['planId'][$mailServicePlan] : null;
            
            $mailServiceStatusResp = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnManager($mailServiceStatus, array('websiteId' => $this->id));
            if ($mailServiceStatusResp && $mailServiceStatusResp->status == 'error' || $mailServiceStatusResp->data->status == 'error') {
                \DBG::log('Failed to '.$mailServiceStatus);
                throw new WebsiteException('Failed to '.$mailServiceStatus);
            }

            if (!\FWValidator::isEmpty($mailServicePlan) && !\FWValidator::isEmpty($planId)) {
                $paramsData = array(
                    'planId'         => $planId,
                    'websiteId'      => $this->id,
                );

                $mailServicePlanResp = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnManager('changePlanOfMailSubscription', $paramsData);
                if ($mailServicePlanResp && $mailServicePlanResp->status == 'error' || $mailServicePlanResp->data->status == 'error') {
                    \DBG::log('Failed to change the plan of the subscription.');
                    throw new WebsiteException('Failed to change the plan of the subscription.');
                }
            }
           
        }
        
        if (!empty($legalComponents)) {
            $validTo = !empty($options['subscriptionExpiration']) ? $options['subscriptionExpiration'] : 2733517333;
            $codeBase = !\FWValidator::isEmpty($websiteTemplate->getCodeBase()) ? $websiteTemplate->getCodeBase() : \Cx\Core\Setting\Controller\Setting::getValue('defaultCodeBase','MultiSite');
            $params = array(
                'websiteId'         => $this->id,
                'legalComponents'   => $legalComponents,
                'state'             => \Cx\Core_Modules\License\License::LICENSE_OK,
                'validTo'           => $validTo,
                'updateInterval'    => 8760,
                'isUpgradable'      => false,
                'dashboardMessages' => $dashboardMessages,
                'coreCmsEdition'    => $websiteTemplate->getName(),
                'coreCmsVersion'    => $codeBase
            );
            //send the JSON Request 'setLicense' command from service to website
            try {
                $resp = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnWebsite('setLicense', $params, $this);
                if ($resp && $resp->status == 'success' && $resp->data->status == 'success') {
                    if (isset($resp->data->log)) {
                        \DBG::appendLogs(array_map(function($logEntry) {return '(Website: '.$this->getName().') '.$logEntry;}, $resp->data->log));
                    }
                    return true;
                } else {
                    if (isset($resp->log)) {
                        \DBG::appendLogs(array_map(function($logEntry) {return '(Website: '.$this->getName().') '.$logEntry;}, $resp->log));
                    }
                    if (isset($resp->message)) {
                        \DBG::msg('(Website: '.$this->getName().') '.serialize($resp->message));
                    }
                    throw new WebsiteException('Unable to setup license: Error in setup license in Website');
                }
            } catch (\Cx\Core_Modules\MultiSite\Controller\MultiSiteJsonException $e) {
                throw new WebsiteException('Unable to setup license: '.$e->getMessage());
            }           
        }        
    }
    
    /**
     * Initialize the language
     */
    public function setupTheme($websiteThemeId) {
        //send the JSON Request 'setWebsiteTheme' command from service to website
        try {
            if (empty($websiteThemeId)) {
                return;
            }
            
            $resp = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnWebsite('setWebsiteTheme', array('themeId' => $websiteThemeId), $this);
            if ($resp && $resp->status == 'success' && $resp->data->status == 'success') {
                if (isset($resp->data->log)) {
                    \DBG::appendLogs(array_map(function($logEntry) {return '(Website: '.$this->getName().') '.$logEntry;}, $resp->data->log));
                }
                return true;
            } else {
                if (isset($resp->log)) {
                    \DBG::appendLogs(array_map(function($logEntry) {return '(Website: '.$this->getName().') '.$logEntry;}, $resp->log));
                } elseif (isset($resp->data) && isset($resp->data->log)) {
                    \DBG::appendLogs(array_map(function($logEntry) {return '(Website: '.$this->getName().') '.$logEntry;}, $resp->data->log));
                }
                throw new WebsiteException('Unable to setup the theme: Error in setting theme in Website');
            }
        } catch (\Cx\Core_Modules\MultiSite\Controller\MultiSiteJsonException $e) {
            throw new WebsiteException('Unable to setup the theme: '.$e->getMessage());
        }        
    }
    
    /**
     * Create the Ftp-Account
     * 
     * @param string $websiteName website's name
     * 
     * @return boolean
     */
    public function setupFtpAccount($websiteName) {
        try {
            if (!\Cx\Core\Setting\Controller\Setting::getValue('createFtpAccountOnSetup','MultiSite')) {
                return false;
            }
            
            //create FTP-Account
            //validate FTP user name if website name doesn't starts with alphabetic letters, add the prefix to website name
            $ftpUser   = (\Cx\Core\Setting\Controller\Setting::getValue('forceFtpAccountFixPrefix','MultiSite')) ? \Cx\Core\Setting\Controller\Setting::getValue('ftpAccountFixPrefix','MultiSite') . $websiteName : 
                         !preg_match('#^[a-z]#i', $websiteName) ? \Cx\Core\Setting\Controller\Setting::getValue('ftpAccountFixPrefix','MultiSite') . $websiteName : $websiteName;

            $maxLengthFtpAccountName = \Cx\Core\Setting\Controller\Setting::getValue('maxLengthFtpAccountName','MultiSite');
            $existingFtpAccounts     = $this->websiteController->getAllEndUserAccounts();
            
            $tmpFtpUser = $ftpUser;
            $flag       = 1;
            $cutOfCount = 1;
            while (
                     (!empty($maxLengthFtpAccountName) && strlen($tmpFtpUser) > $maxLengthFtpAccountName) 
                  || in_array($tmpFtpUser, $existingFtpAccounts)
            ) {
                if (strlen($tmpFtpUser) > $maxLengthFtpAccountName) {
                    $ftpUser = substr($ftpUser, 0, $maxLengthFtpAccountName - $cutOfCount);
                    $cutOfCount++;
                }
                $tmpFtpUser = $ftpUser . $flag;    
                $flag++;
            }
            $ftpUser = $tmpFtpUser;

            $password  = \User::make_password(8, true);
            $accountId = $this->websiteController->createEndUserAccount($ftpUser, $password, \Cx\Core\Setting\Controller\Setting::getValue('websiteFtpPath','MultiSite') . '/' . $websiteName, \Cx\Core\Setting\Controller\Setting::getValue('pleskWebsitesSubscriptionId','MultiSite'));

            if ($accountId) {
                $this->ftpUser = $ftpUser;
                return $password;
            }

            return false;
        } catch (\Exception $e) {
            throw new WebsiteException('Unable to setup ftp account: '.$e->getMessage());
        }    
    }
    
    /**
     * generate password restore url
     * 
     * @return string
     */
    public function generatePasswordRestoreUrl()
    {
        $this->owner->setRestoreKey();
        // hard-coded to 1 day
        $this->owner->setRestoreKeyTime(time() + 86400);
        \Env::get('em')->flush();
        $websitePasswordUrl = \FWUser::getPasswordRestoreLink(false, $this->owner, \Cx\Core\Setting\Controller\Setting::getValue('customerPanelDomain','MultiSite'));
        return $websitePasswordUrl;
    }
    
    /**
     * Generate verification url
     * 
     * @return string
     */
    public function generateVerificationUrl()
    {
        $this->owner->setRestoreKey();
        // hard-coded to 30 days
        $this->owner->setRestoreKeyTime(time() + 86400 * 30);
        \Env::get('em')->flush();
        $websiteVerificationUrl = \FWUser::getVerificationLink(true, $this->owner, $this->baseDn->getName());
        return $websiteVerificationUrl;
    }

    public function generateAuthToken() {
        try {
            $resp = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnWebsite('generateAuthToken', array(), $this);
            if ($resp && $resp->status == 'success' && $resp->data->status == 'success') {
                if (isset($resp->data->log)) {
                    \DBG::appendLogs(array_map(function($logEntry) {return '(Website: '.$this->getName().') '.$logEntry;}, $resp->data->log));
                }
                return array($resp->data->userId, $resp->data->authToken);
            } else {
                if (isset($resp->data) && isset($resp->data->log)) {
                    \DBG::appendLogs(array_map(function($logEntry) {return '(Website: '.$this->getName().') '.$logEntry;}, $resp->data->log));
                }
                throw new WebsiteException('Command generateAuthToken failed');
            }
        } catch (\Cx\Core_Modules\MultiSite\Controller\MultiSiteJsonException $e) {
            throw new WebsiteException('Unable to generate auth token: '.$e->getMessage());
        }  
    }

    /**
     * generate account password
     * 
     * @return string
     */
    public function generateAccountPassword() {
        $newPassword = \User::make_password(8, true);
        $params = array(
            'userId' => $this->owner->getId(),
            'multisite_user_account_password'           => $newPassword,
            'multisite_user_account_password_confirmed' => $newPassword,
        );
        try {
            $resp = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnManager('updateUser', $params);
            if ($resp && $resp->status == 'success' && $resp->data->status == 'success') {
                // do only append logs from executed command, if command was not executed on our own system,
                // otherwise we would re-add our existing log-messages (-> duplicating whole log stack)
                if (   \Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite') != \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_MANAGER
                    && isset($resp->data->log)
                ) {
                    \DBG::appendLogs(array_map(function($logEntry) {return '(Manager) '.$logEntry;}, $resp->data->log));
                }
                return $newPassword;
            } else {
                // do only append logs from executed command, if command was not executed on our own system,
                // otherwise we would re-add our existing log-messages (-> duplicating whole log stack)
                if (   \Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite') != \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_MANAGER
                    && isset($resp->log)
                ) {
                    \DBG::appendLogs(array_map(function($logEntry) {return '(Manager) '.$logEntry;}, $resp->log));
                }
                throw new WebsiteException('Unable to generate account password: Error in generate account password');
            }
        } catch (\Cx\Core_Modules\MultiSite\Controller\MultiSiteJsonException $e) {
            throw new WebsiteException('Unable to generate account password: '.$e->getMessage());
        }  
    }
    
    /**
     * Magic function to get the website name
     * 
     * @return string Website's name
     */
    public function __toString() {
        return $this->name;
    }
    
    /**
     * Get mail service server
     * 
     * @return $mailServiceServer
     */
    public function getMailServiceServer()
    {
        return $this->mailServiceServer;
    }
    
    /**
     * Set mail service server
     * 
     * @param mixed Cx\Core_Modules\MultiSite\Model\Entity\MailServiceServer $mailServiceServer | null
     */
    public function setMailServiceServer($mailServiceServer)
    {
        if (!$mailServiceServer) {
            return;
        }
        $this->mailServiceServer = $mailServiceServer;
    }
    
    /**
     * Get the mail account id
     * 
     * @return integer $mailAccountId
     */
    public function getMailAccountId()
    {
        return $this->mailAccountId;
    }
    
    /**
     * Set the mail account id
     * 
     * @param integer $mailAccountId
     */
    public function setMailAccountId($mailAccountId)
    {
        $this->mailAccountId = $mailAccountId;
    }
    
    /**
     * Get the WebsiteCollection
     * 
     * @return $websiteCollection
     */
    public function getWebsiteCollection()
    {
        return $this->websiteCollection;
    }
    
    /**
     * Set the Website Collection
     * 
     * @param \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteCollection $websiteCollection
     */
    public function setWebsiteCollection(WebsiteCollection $websiteCollection)
    {
        $this->websiteCollection = $websiteCollection;
    }
    
    /**
     * get the website admin and backend group users
     * 
     * @return object $adminUsers return adminusers as objects.
     * 
     * @throws WebsiteException
     */
    public function getAdminUsers() 
    {
        $adminUsers = array();
        
        try {
            $resp = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnWebsite('getAdminUsers', array(), $this);
            if ($resp && $resp->status == 'success' && $resp->data->status == 'success') {
                
                if (\FWValidator::isEmpty($resp->data->users)) {
                    return;
                }
                
// TODO: DataSet must be extended, that it can handle objects
                //because DataSet cannot handle objects, we parse the object to an array
                $json = json_encode($resp->data->users);
                $arr = json_decode($json,true);
                //because the array must be multidimensional for the export function, you must add a level, when its have only one
                if(!is_array(current($arr))){
                    $arr = array($arr);
                }
                
                $objDataSet         = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($arr);
                $users = $objDataSet->toArray();
                if ($objDataSet->size() == 1) {
                    $users = array($users);
                }
                
                $adminUsers = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($users);
                return $adminUsers;
            }
        } catch (\Cx\Core_Modules\MultiSite\Controller\MultiSiteJsonException $e) {
            throw new WebsiteException('Unable get admin users: '.$e->getMessage());
        }
        
    }

    
    /**
     * get the user
     * 
     * @param  integer $id user id
     * @return object  $user return user as objects.
     * 
     * @throws WebsiteException
     */
    public function getUser($id) 
    {
        if(\FWValidator::isEmpty($id)){
            return;
        }
        try {
            $resp = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnWebsite('getUser', array('id' => $id), $this);
            if ($resp && $resp->status == 'success' && $resp->data->status == 'success') {
                
                if (\FWValidator::isEmpty($resp->data->user)) {
                    return;
                }
                $objDataSet         = new \Cx\Core_Modules\Listing\Model\Entity\DataSet(array($resp->data->user));
                $objEntityInterface = new \Cx\Core_Modules\Listing\Model\Entity\EntityInterface();
                $objEntityInterface->setEntityClass('Cx\Core\User\Model\Entity\User');
                $user = $objDataSet->export($objEntityInterface);
                return $user;
            }
        } catch (\Cx\Core_Modules\MultiSite\Controller\MultiSiteJsonException $e) {
            throw new WebsiteException('Unable get user: '.$e->getMessage());
        }
        
    }
    
    
    /**
     * This function used to get the website resource usage stats on website.
     * 
     * @return array website resource usage stats
     * @throws WebsiteException
     */
    public function getResourceUsageStats() {
        try {
            $resp = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnWebsite('getResourceUsageStats', array(), $this);
            if ($resp && $resp->status == 'success' && $resp->data->status == 'success') {
                return $resp->data->resourceUsageStats;
            }
        } catch (\Cx\Core_Modules\MultiSite\Controller\MultiSiteJsonException $e) {
            throw new WebsiteException('Unable get Resource usage stats: ' . $e->getMessage());
        }
    }
    
    /**
     * Return the backend edit link
     */
    public function getEditLink()
    {
        global $_ARRAYLANG;
        
        $websiteDetailLink = '<a href="index.php?cmd=MultiSite&term=' . $this->getId() . '" title="' . $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DETAIL_LINK'] . '"> 
                                    <img 
                                        src = "' . \Env::get('cx')->getCodeBaseCoreModuleWebPath() . '/MultiSite/View/Media/details.gif"
                                        width="16px" height="16px"
                                        alt="' . $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DETAIL_LINK'] . '"
                                    />
                                </a>';
        return '<a href="index.php?cmd=MultiSite&editid={0,'. $this->getId() .'}" title="' . $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_EDIT_LINK'] . '">' 
                . $this->getName() . 
                '</a>' . $websiteDetailLink;
    }
    
    /**
     * Remove or unlink domain from the website
     * 
     * @param \Cx\Core_Modules\MultiSite\Model\Entity\Domain $domain
     */
    public function removeDomain(Domain $domain)
    {
        $this->domains->removeElement($domain);
    }

    /**
     * Set the website mode
     *
     * @param string $mode
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    /**
     * Get the website mode
     *
     * @return string $mode
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * set the server website
     *
     * @param \Cx\Core_Modules\MultiSite\Model\Entity\Website|null $serverWebsite
     */
    public function setServerWebsite($serverWebsite)
    {
        $this->serverWebsite = $serverWebsite;
    }

    /**
     * get the server website
     *
     * @return \Cx\Core_Modules\MultiSite\Model\Entity\Website $serverWebsite
     */
    public function getServerWebsite()
    {
        return $this->serverWebsite;
    }
}
