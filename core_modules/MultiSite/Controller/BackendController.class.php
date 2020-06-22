<?php
/**
 * Specific BackendController for this Component. Use this to easily create a backend view
 *
 * @copyright   Comvation AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     contrexx
 * @subpackage  modules_skeleton
 */

namespace Cx\Core_Modules\MultiSite\Controller;

/**
 * Class MultiSiteBackendException
 */
class MultiSiteBackendException extends \Exception {}

/**
 * Specific BackendController for this Component. Use this to easily create a backend view
 *
 * @copyright   Comvation AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     contrexx
 * @subpackage  modules_skeleton
 */
class BackendController extends \Cx\Core\Core\Model\Entity\SystemComponentBackendController {
    
    static $dnsRecords = array();
    const MULTISITE_DEFAULT_ACCESS_ID = 183;
    const MULTISITE_COMMUNICATION_MANAGEMENT_ACCESS_ID = 198;
    const MULTISITE_SYSTEM_MANAGEMENT_ACCESS_ID         = 199;
    const MULTISITE_BACKUP_TEMP_DIR  = '/backupzips';
     
    public function __construct(\Cx\Core\Core\Model\Entity\SystemComponentController $systemComponentController, \Cx\Core\Core\Controller\Cx $cx) {
        parent::__construct($systemComponentController, $cx);
        $this->defaultPermission = new \Cx\Core_Modules\Access\Model\Entity\Permission(null, null, true, null, array(self::MULTISITE_DEFAULT_ACCESS_ID), null);
    }

    /**
     * Returns a list of available commands (?act=XY)
     * @return array List of acts
     */
    public function getCommands() {

        //array structure has to be defined as follows
        //array('XY' => array('access' => $permissionAccess, 'sub_commands' => $subCommandsArray))
        //XY => act value should be defined here.
        //$permissionAccess => array of the permission access Ids should be listed here.
        //$subCommandsArray => array of subcommands should be listed here.
        $systemMgmtPermissionObj = new \Cx\Core_Modules\Access\Model\Entity\Permission(null, null, false, null, array(self::MULTISITE_SYSTEM_MANAGEMENT_ACCESS_ID), null);
        $communicationAndSystemMgmtPermissionObj = new \Cx\Core_Modules\Access\Model\Entity\Permission(null, null, false, null, array(self::MULTISITE_SYSTEM_MANAGEMENT_ACCESS_ID, self::MULTISITE_COMMUNICATION_MANAGEMENT_ACCESS_ID), null);
        switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
            case ComponentController::MODE_SERVICE:
                return array(
                    'Maintenance' => array(
                        'permission' => $systemMgmtPermissionObj,
                        'children'   => array(
                            ''    => array('permission' => $systemMgmtPermissionObj),
                            'Ftp' => array('permission' => $systemMgmtPermissionObj)
                        )
                    ),
                    'statistics' => array(
                        'permission' => $systemMgmtPermissionObj
                    ),
                    'settings'    => array(
                        'permission' => $systemMgmtPermissionObj,
                        'children'   => array(
                            '' => array(
                                'permission' => $systemMgmtPermissionObj,
                            ),
                            'codebases' => array(
                                'permission' => $systemMgmtPermissionObj,
                            ),
                            'website_templates' => array(
                                'permission' => $systemMgmtPermissionObj,
                            )
                        )
                    )
                );
                break;

            case ComponentController::MODE_MANAGER:
                return array(
                    'Affiliate',
                    'Maintenance' => array(
                        'permission' => $systemMgmtPermissionObj,
                        'children'   => array(
                            ''    => array('permission' => $systemMgmtPermissionObj),
                            'Ftp' => array('permission' => $systemMgmtPermissionObj),
                            'BackupsAndRestore' => array('permission' => $systemMgmtPermissionObj)
                        )
                    ),
                    'statistics' => array(
                        'permission' => $systemMgmtPermissionObj
                    ),
                    'notifications' => array(
                        'permission' => $communicationAndSystemMgmtPermissionObj,
                        'children'   => array(
                            '' => array('permission' => $systemMgmtPermissionObj),
                            'emails' => array('permission' => $communicationAndSystemMgmtPermissionObj)
                        )
                    ),
                    'settings'    => array(
                        'permission' => $communicationAndSystemMgmtPermissionObj,
                        'children'   => array(
                            '' => array('permission' => $systemMgmtPermissionObj),
                            'email' => array('permission' => $communicationAndSystemMgmtPermissionObj),
                            'website_templates' => array('permission' => $systemMgmtPermissionObj),
                            'website_service_servers' => array('permission' => $systemMgmtPermissionObj),
                            'mail_service_servers' => array('permission' => $systemMgmtPermissionObj)
                        )
                    )
                );
                break;

            case ComponentController::MODE_HYBRID:
                return array(
                    'Affiliate',
                    'Maintenance' => array(
                        'permission' => $systemMgmtPermissionObj,
                        'children'   => array(
                            '' => array('permission' => $systemMgmtPermissionObj),
                            'Ftp' => array('permission' => $systemMgmtPermissionObj),
                            'BackupsAndRestore' => array('permission' => $systemMgmtPermissionObj)
                        )
                    ),
                    'statistics' => array(
                        'permission' => $systemMgmtPermissionObj
                    ),
                    'notifications' => array(
                        'permission' => $communicationAndSystemMgmtPermissionObj,
                        'children'   => array(
                            '' => array('permission' => $systemMgmtPermissionObj),
                            'emails' => array('permission' => $communicationAndSystemMgmtPermissionObj)
                        )
                    ),
                    'settings'    => array(
                        'permission' => $communicationAndSystemMgmtPermissionObj,
                        'children'   => array(
                            '' => array('permission' => $systemMgmtPermissionObj),
                            'email' => array('permission' => $communicationAndSystemMgmtPermissionObj),
                            'codebases' => array('permission' => $systemMgmtPermissionObj),
                            'website_templates' => array('permission' => $systemMgmtPermissionObj),
                            'mail_service_servers' => array('permission' => $systemMgmtPermissionObj)
                        )
                    )
                );
                break;

            case ComponentController::MODE_NONE:
            case ComponentController::MODE_WEBSITE:
            default:
                return array();
                break;
        }
    }

    /**
     * Use this to parse your backend page
     * 
     * You will get the template located in /View/Template/{CMD}.html
     * You can access Cx class using $this->cx
     * To show messages, use \Message class
     * @param \Cx\Core\Html\Sigma $template Template for current CMD
     * @param array $cmd CMD separated by slashes
     */
    public function parsePage(\Cx\Core\Html\Sigma $template, array $cmd, &$isSingle = false) {
        global $_ARRAYLANG;

        $communicationManagementAccess = \Permission::checkAccess(self::MULTISITE_COMMUNICATION_MANAGEMENT_ACCESS_ID, 'static', true);
        $systemManagementAccess        = \Permission::checkAccess(self::MULTISITE_SYSTEM_MANAGEMENT_ACCESS_ID, 'static', true);

        switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
            case ComponentController::MODE_NONE:
            case ComponentController::MODE_WEBSITE:
                \Permission::noAccess();
                $cmd = array('settings');
                break;

            default:
                break;
        }

        switch (current($cmd)) {
            case 'settings':
                if (!$communicationManagementAccess && !$systemManagementAccess){
                    \Permission::noAccess();
                }
                $this->parseSectionSettings($template, $cmd);
                break;

            case 'notifications':
                if (!$communicationManagementAccess && !$systemManagementAccess){
                    \Permission::noAccess();
                }
                $this->parseSectionNotifications($template, $cmd);
                break;

            case 'statistics':
                \Permission::checkAccess(self::MULTISITE_SYSTEM_MANAGEMENT_ACCESS_ID, 'static');
                $this->parseSectionStatistics($template, $cmd);
                break;

            case 'Maintenance':
                \Permission::checkAccess(self::MULTISITE_SYSTEM_MANAGEMENT_ACCESS_ID, 'static');
                $this->parseSectionMaintenance($template, $cmd);
                break;
                
            case 'Affiliate':
                \Permission::checkAccess(self::MULTISITE_DEFAULT_ACCESS_ID, 'static');
                $this->parseSectionAffiliate($template, $cmd);
                break;
            default:
                $this->parseSectionWebsites($template, $cmd);
                break;
        }
    }

    public function parseSectionSettings(\Cx\Core\Html\Sigma $template, array $cmd) {
        global $_ARRAYLANG;

        $config = \Env::get('config');
        $mode = \Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite');

        if (   \Permission::checkAccess(self::MULTISITE_COMMUNICATION_MANAGEMENT_ACCESS_ID, 'static', true)
            && !\Permission::checkAccess(self::MULTISITE_SYSTEM_MANAGEMENT_ACCESS_ID, 'static', true)
            && (   empty($cmd[1]) 
                || (   !empty($cmd[1]) 
                    && $cmd[1] != 'email'
                   )
                )
           ) {
            \Permission::noAccess();
        }

        if (!empty($cmd[1]) && $cmd[1]=='email') {   
            $template->setVariable(array(
                'TABLE' => \Cx\Core\MailTemplate\Controller\MailTemplate::adminView('MultiSite', 'nonempty', $config['corePagingLimit'], 'settings/email')->get(),
            ));
        } elseif(!empty($cmd[1]) && $cmd[1]=='website_service_servers'){
            //Register backup and restore js
            \JS::registerJS('core_modules/MultiSite/View/Script/BackupAndRestore.js');

            $websiteServiceServers = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer')->findAll();
            if(empty($websiteServiceServers)){
                $websiteServiceServers = new \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer();
            }
            $view = new \Cx\Core\Html\Controller\ViewGenerator(
                $websiteServiceServers,
                array(
                    'header' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ACT_SETTINGS_WEBSITE_SERVICE_SERVERS'],
                    'functions' => array(
                        'edit' => true,
                        'add' => true,
                        'delete' => true,
                        'sorting' => true,
                        'paging' => true,       // cannot be turned off yet
                        'filtering' => false,   // this does not exist yet
                        'actions' => function($rowData) {
                                        if (in_array(\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite'), array(ComponentController::MODE_MANAGER, ComponentController::MODE_HYBRID))) {
                                            $actions  = '';//'<a href="javascript:void(0);" class = "websiteUpdate" data-id = '.$rowData['id'].' title = "update" ></a>';
                                            $actions .= \Cx\Core_Modules\MultiSite\Controller\BackendController::websiteBackup($rowData, true);
                                            $actions .= \Cx\Core_Modules\MultiSite\Controller\BackendController::executeSql($rowData, true);
                                        }
                                        return $actions;
                                    }
                    ),
                    'fields' => array(
                        'id' => array(
                            'showOverview' => false,
                        ),
                        'hostname' => array(
                            'header' => 'Hostname',
                            'table' => array(
                                 'parse' => function($value) {
                                    $websiteServiceServer = ComponentController::getServiceServerByCriteria(array('hostname' => $value));
                                    $pingPongStartTime = microtime(true);
                                    $response   = JsonMultiSiteController::executeCommandOnServiceServer('ping', array(), $websiteServiceServer);
                                    if ($response && $response->status == 'success' && $response->data->status == 'success'){
                                        $pingPongTime = round((microtime(true) - $pingPongStartTime) * 1000);
                                        $pingPongTimeDisplay = $pingPongTime . 'ms';
                                        if (isset($response->data) && isset($response->data->pongTime)) {
                                            $pingTime = $pingPongTime - $response->data->pongTime;
                                            $pingPongTimeDisplay = $pingTime . 'ms / ' . $response->data->pongTime . 'ms';
                                        }
                                        $statusIcon       = '<img src="'. '../core/Core/View/Media/icons/status_green.gif"'. ' alt='."status_green".'/>';
                                        $hostNameStatus   = $statusIcon."&nbsp;".$value."&nbsp;".'<span class="'. 'icon-info tooltip-trigger"'. '></span><span class="'. 'tooltip-message"'. '> Bidirectional communication successfully established (' . $pingPongTimeDisplay . ')</span>';
                                        return $hostNameStatus;
                                    } else {
                                       $statusIcon      = '<img src="'. '../core/Core/View/Media/icons/status_red.gif"'. ' alt='."status_red".'/>';
                                       $hostNameStatus  = $statusIcon."&nbsp;".$value."&nbsp;".'<span class="'. 'icon-info tooltip-trigger"'. '></span><span class="'. 'tooltip-message"'. '>'.$response->data->message.'</span>';
                                       return $hostNameStatus;
                                    }
                                 },
                             ),
                        ),
                        'label' => array(
                            'header' => 'Name',
                        ),
                        'secretKey' => array(
                            'showOverview' => false,
                        ),
                        'installationId' => array(
                            'showOverview' => false,
                        ),
                        'httpAuthMethod' => array(
                            'showOverview' => false,
                        ),
                        'httpAuthUsername' => array(
                            'showOverview' => false,
                        ),
                        'httpAuthPassword' => array(
                            'showOverview' => false,
                        ),
                    ),
                )
            );
                                 
            $cxjs = \ContrexxJavascript::getInstance();
            $cxjs->setVariable(array(
                'selectAll'                 => $_ARRAYLANG['TXT_SELECT_ALL'],
                'deSelectAll'               => $_ARRAYLANG['TXT_DESELECT_ALL'],
                'loadingServiceServerInfo'  => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_LOADING_SERVICE_SERVER_INFO'],
                'triggeringWebsiteUpdate'   => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_TRIGGERING_WEBSITE_UPDATE'],
                'latestCodeBaseVersion'     => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CODEBASE_VERSION'],
                'codeBaseNotExist'          => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CODEBASE_NOT_EXIST'],
                'updateNotAvailable'        => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_UPDATE_NOT_AVAILABLE'],
                'websiteName'               => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITENAME'],
                'codeBase'                  => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ACT_SETTINGS_CODEBASES'],
                'websitesNotExist'          => $_ARRAYLANG['TXT_MULTISITE_NO_WEBSITE_FOUND'],
                'loading'                   => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_LOADING_TEXT']
                ), 'multisite/lang');
            
            $template->setVariable('TABLE', $view->render());
        } elseif (!empty($cmd[1]) && $cmd[1]=='codebases') {
            $codeBasePath   = \Cx\Core\Setting\Controller\Setting::getValue('codeBaseRepository','MultiSite');
            $codebaseScannedDir = array_values(array_diff(scandir($codeBasePath), array('..', '.')));
            $codebaseRepositoryDataArray[] = array(
                'Version_number'  => $config['coreCmsVersion'],
                'default'         => '',
                'Code_Name'       => $config['coreCmsCodeName'],
                'Release_Date'    => date(ASCMS_DATE_FORMAT_DATE, $config['coreCmsReleaseDate']),
                'Path'            => \Env::get('cx')->getCodeBaseDocumentRootPath() 
            );
            
            foreach ($codebaseScannedDir as $value) {
                $configFile = $codeBasePath.'/'.$value.'/installer/config/config.php';
                if (file_exists($configFile)) {
                    $configContents = file_get_contents($codeBasePath.'/'.$value.'/' .$scannedDir[0]. '/installer/config/config.php');
                    if (preg_match_all('/\\$_CONFIG\\[\'(.*?)\'\\]\s+\=\s+\'(.*?)\';/s', $configContents, $matches)) {
                            $configValues = array_combine($matches[1], $matches[2]);
                            $codebaseRepositoryDataArray[] = array(
                                'Version_number' => $configValues['coreCmsVersion'],
                                'default'        => $value,
                                'Code_Name'      => $configValues['coreCmsCodeName'],
                                'Release_Date'   => $configValues['coreCmsReleaseDate'],
                                'Path'           => $codeBasePath.'/'.$value
                                );
                    }
                    
                }          
            }
            
            $codebaseRepositoryDataSet = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($codebaseRepositoryDataArray);
            $codeBase = new \Cx\Core\Html\Controller\ViewGenerator(
                $codebaseRepositoryDataSet,
                array(
                    'header' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ACT_SETTINGS_CODEBASES'],
                    'fields' => array(
                        'Version_number' => array(
                            'header' => 'Version number'
                         ),
                        'default' => array(
                            'header'  => 'Default',
                            'table' => array(
                                'parse' => function($value) {
                                    $checked = ($value == \Cx\Core\Setting\Controller\Setting::getValue('defaultCodeBase','MultiSite')) ? 'checked="checked"' : '';
                                    $content = '<input type = "radio" class="defaultCodeBase" name = "defaultCodeBase" '.$checked.' value ="'.$value.'"/>';
                                    return $content;
                                },
                        ),
                        ),
                        'Code_Name' => array(
                            'header' => 'Code Name',
                        ),
                        'Release_Date' => array(
                            'header' => 'Release Date',
                        ),
                        'Path' => array(
                            'header' => 'Path'
                        ),
                    ),
                )
            );
            $template->setVariable('TABLE', $codeBase->render());
            
        } else if (!empty($cmd[1]) && $cmd[1]=='website_templates') {
            $websiteTemplates = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\WebsiteTemplate')->findAll();
           
            if (empty($websiteTemplates)) {
                $websiteTemplates = new \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteTemplate();
            }
            $hasAccess = in_array($mode, array(ComponentController::MODE_MANAGER, ComponentController::MODE_HYBRID));
            $headerMessage = in_array($mode, array(ComponentController::MODE_SERVICE))? $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_SETTINGS_WEBSITE_TEMPLATE_HEADER_MSG']: '';
            $websiteTemplatesView = new \Cx\Core\Html\Controller\ViewGenerator(
                $websiteTemplates, 
                array(
                    'header' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ACT_SETTINGS_WEBSITE_TEMPLATES'].$headerMessage,
                    'functions' => array(
                        'edit' => $hasAccess,
                        'add' => $hasAccess,
                        'delete' => $hasAccess,
                        'sorting' => true,
                        'paging' => true,       
                        'filtering' => false,   
                    ),
                    'fields' => array(
                        'id' => array(
                            'showOverview' => false,
                        ),
                        'codeBase' => array(
                            'header' => 'codeBase',
                        ),
                        'licensedComponents' => array(
                            'header' => 'licensedComponents',
                        ),
                        'licenseMessage' => array(
                            'header' => 'licenseMessage',
                        ),
                        'websiteServiceServer' => array(
                            'showOverview' => $hasAccess,
                        ),
                    ),
                )
            );
            $template->setVariable('TABLE', $websiteTemplatesView->render());
        } else if (!empty($cmd[1]) && $cmd[1]=='mail_service_servers') {
            $mailServiceServers = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\MailServiceServer')->findAll();
           
            if (empty($mailServiceServers)) {
                $mailServiceServers = new \Cx\Core_Modules\MultiSite\Model\Entity\MailServiceServer();
            }
            $mailServiceServersView = new \Cx\Core\Html\Controller\ViewGenerator(
                $mailServiceServers,
                array(
                    'header' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ACT_SETTINGS_MAIL_SERVICE_SERVERS'],
                    'functions' => array(
                        'edit' => true,
                        'add' => true,
                        'delete' => true,
                        'sorting' => true,
                        'paging' => true,       
                        'filtering' => false,
                        'actions' => (in_array($mode, array(ComponentController::MODE_MANAGER, ComponentController::MODE_HYBRID))) ?
                                        function($rowData) {
                                            return \Cx\Core_Modules\MultiSite\Controller\BackendController::getMailServicePlans($rowData);
                                        } : false,
                    ),
                    'fields' => array(
                        'id' => array(
                            'showOverview' => false,
                        ),
                        'websites' => array(
                            'showOverview' => false,
                        ),
                        'authPassword' => array(
                            'showOverview' => false,
                        ),
                        'authUsername' => array(
                            'showOverview' => false,
                        ),
                        'config' => array(
                            'showOverview' => false,
                        ),
                        'label' => array(
                            'header' => 'Label',
                        ),
                        'type' => array(
                            'header' => 'Type',
                        ),
                        'hostname' => array(
                            'header' => 'HostName',
                        ),
                        'ipAddress' => array(
                            'header' => 'Ip Address',
                        ),
                        'apiVersion' => array(
                            'header' => 'Api Version',
                        ),
                    ),
                )
            );
            $template->setVariable('TABLE', $mailServiceServersView->render());
        } else {
            if (\Permission::checkAccess(self::MULTISITE_SYSTEM_MANAGEMENT_ACCESS_ID, 'static', true)) {
                return $this->settings($template);
            }
        }
    }

    public function parseSectionNotifications(\Cx\Core\Html\Sigma $template, array $cmd) {
        global $_ARRAYLANG;
        if (!empty($cmd[1]) && $cmd[1] == 'emails') {
            $cronMails = \Env::get('em')->getRepository('\Cx\Core_Modules\MultiSite\Model\Entity\CronMail')->findAll();
            if (empty($cronMails)) {
                $cronMails = new \Cx\Core_Modules\MultiSite\Model\Entity\CronMail();
            }

            $cronMailsView = new \Cx\Core\Html\Controller\ViewGenerator(
                $cronMails,
                array(
                    'header' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ACT_NOTIFICATIONS_EMAILS'],
                    'functions' => array(
                        'edit' => true,
                        'add' => true,
                        'delete' => true,
                        'sorting' => true,
                        'paging' => true,
                        'filtering' => false,
                    ),
                )
            );
            $template->setVariable('TABLE', $cronMailsView->render());
        } else {
            $cronMailLog = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\CronMailLog')->findAll();
            $cronMailLogView = new \Cx\Core\Html\Controller\ViewGenerator($cronMailLog,
                array(
                    'header' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_NOTIFICATION_LOGS'],
                    'functions' => array(
                        'edit' => false,
                        'add' => false,
                        'delete' => false,
                        'sorting' => true,
                        'paging' => true,
                        'filtering' => false,
                        'order' => array(
                            'sentDate' => SORT_DESC,
                        ),
                    ),
                    'fields' => array(
                        'id' => array('showOverview' => false),
                        'contactId' => array(
                            'header' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CONTACT'],
                            'table' => array(
                                'parse' => function($contactId) {
                                    $contact = new \Cx\Modules\Crm\Model\Entity\CrmContact();
                                    $contact->load($contactId);
                                    return $contact->customerName . ' ' . $contact->family_name;
                                },
                            ),
                        ),
                        'websiteId' => array(
                            'header' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE'],
                            'table'  => array(
                                'parse' => function($websiteId) {
                                    global $_ARRAYLANG;
                                    if (empty($websiteId)) {
                                        return $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ALL'];
                                    }
                                    $websiteEntity = \Env::get('em')->getRepository('\Cx\Core_Modules\MultiSite\Model\Entity\Website')
                                            ->findOneById($websiteId);
                                    if (!$websiteEntity) {
                                        return;
                                    }
                                    return $websiteEntity->getEditLink();
                                }
                            ),
                        ),
                        'success' => array(
                            'header' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITESTATE'],
                            'table' => array(
                                'parse' => function($value) {
                                    return self::getStatusImageTag($value);
                                }
                            ),
                        ),
                        'sentDate' => array(
                            'header' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_NOTIFICATIONS_SENT'],
                        ),
                        'token' => array('showOverview' => false),
                        'cronMail' => array(
                            'header' => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_EMAIL'],
                            'table' => array(
                                'parse' => function($value) {
                                    global $_ARRAYLANG;
                                    $cronMailEntity = \Env::get('em')->getRepository('\Cx\Core_Modules\MultiSite\Model\Entity\CronMail')
                                                        ->findOneById($value);
                                    if (!$cronMailEntity) {
                                        return;
                                    }
                                    return '<a href="index.php?cmd=MultiSite&amp;act=notifications/emails&amp;editid='
                                        . $cronMailEntity->getId() . '" title="' . $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_NOTIFICATIONS_EMAIL_EDIT'] . '">'
                                        . $cronMailEntity->getMailTemplateKey() . '</a>';
                                }
                            ),
                        ),
                    ),
                )
            );
            $template->setVariable('TABLE', $cronMailLogView->render());
        }
    }

    public function parseSectionStatistics(\Cx\Core\Html\Sigma $template, array $cmd) {
        //dynamic use websites path
        //self::errorHandler();
        //\Cx\Core\Setting\Controller\Setting::init('MultiSite', 'config');
        $websitesPath=\Cx\Core\Setting\Controller\Setting::getValue('websitePath','MultiSite');
        // this a very ugly BETA with no much comment and wrong english in it
        $instRepo = \Env::get('em')->getRepository('\Cx\Core_Modules\MultiSite\Model\Entity\Website');
        $websites = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($instRepo->findAll());
        $html = '
            <p>
                Heute: ' . 
                $instRepo->findWebsitesBetween(
                    $websitesPath,
                    new \DateTime(date('Y-m-d 00:00:00')),
                    new \DateTime(date('Y-m-d 23:59:59'))
                ) . ' Accounts<br />
                Total: ' . $websites->size() . ' Accounts
            </p>
            <p><form>';
        /*$filterTable = new \BackendTable(array('width'=>'100%'));
        $filterTable->setCellContents(0, 0, 'Filter', 'th');
        $filterTable->setCellContents(1, 0, '
            <input type="datetime" class="datetime" name="startdate" />
            <input type="datetime" class="datetime" name="enddate" />
            <input type="submit" class="button" name="submit" />
        ', 'td', 0, false);
        $html .= $filterTable;*/
        $html .= '
            </form></p>
            <table border="1" style="border-collapse: collapse;" cellpadding="2">
                <tr>
                    <th>Jahr</th>
                    <th>Total</th>
                    <th>Jan</th>
                    <th>Feb</th>
                    <th>Mar</th>
                    <th>Apr</th>
                    <th>Mai</th>
                    <th>Jun</th>
                    <th>Jul</th>
                    <th>Aug</th>
                    <th>Sep</th>
                    <th>Oct</th>
                    <th>Nov</th>
                    <th>Dec</th>
                </tr>';
        for ($year = '2013'; $year <= date('Y'); $year++) {
            $html .= '
                <tr>
                    <td>' . $year . '</td>
                    <td>' . 
            $instRepo->findWebsitesBetween(
                $websitesPath,
                new \DateTime(date($year . '-01-01 00:00:00')),
                new \DateTime(date($year . '-12-31 23:59:59'))
            ) . '
                    </td>';
            for ($month = 1; $month <= 12; $month++) {
                if ($month < 10) {
                    $month = '0'.$month;
                }
                $html .= '
                    <td>' . 
                $instRepo->findWebsitesBetween(
                    $websitesPath,
                    new \DateTime(date($year . '-' . $month . '-01 00:00:00')),
                    new \DateTime(date($year . '-' . $month . '-t 23:59:59'))
                ) . '
                    </td>';
            }
            $html .= '
                </tr>';
        }
        $html .= '
            </table>';
        $template->setVariable(array(
            'TABLE' => $html,
        ));
    }
    
    public function parseSectionWebsites(\Cx\Core\Html\Sigma $template, array $cmd) {
        global $_ARRAYLANG;
        
        //Register backup and restore js
        \JS::registerJS('core_modules/MultiSite/View/Script/BackupAndRestore.js');
        
        if (isset($_GET['term']) && !empty($_GET['term'])) {
            $term = contrexx_input2db($_GET['term']);
            $websites = \Env::get('em')->getRepository('\Cx\Core_Modules\MultiSite\Model\Entity\Website')->findWebsitesBySearchTerms($term);
        } else {
            $websites = \Env::get('em')->getRepository('\Cx\Core_Modules\MultiSite\Model\Entity\Website')->findAll();
        }
        $view = new \Cx\Core\Html\Controller\ViewGenerator(
            $websites,
            array(
                'Cx\Core_Modules\MultiSite\Model\Entity\Website' => array(
                    'header' => 'Websites',
                    'functions' => array(
                        'edit' => in_array(\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite'), array(ComponentController::MODE_MANAGER, ComponentController::MODE_HYBRID)),
                        'delete' => in_array(\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite'), array(ComponentController::MODE_MANAGER, ComponentController::MODE_HYBRID)),
                        'sorting' => true,
                        'paging' => true,       // cannot be turned off yet
                        'filtering' => false,   // this does not exist yet
                        'actions' => function($rowData) {
                                        if (in_array(\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite'), array(ComponentController::MODE_MANAGER, ComponentController::MODE_HYBRID))) {
                                            $actions = \Cx\Core_Modules\MultiSite\Controller\BackendController::executeSql($rowData, false);
                                        }
                                        if (in_array(\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite'), array(ComponentController::MODE_MANAGER, ComponentController::MODE_HYBRID))) {
                                            $actions .= \Cx\Core_Modules\MultiSite\Controller\BackendController::showLicense($rowData, false);
                                        }
                                        if (in_array(\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite'), array(ComponentController::MODE_MANAGER, ComponentController::MODE_HYBRID))) {
                                            $actions .= \Cx\Core_Modules\MultiSite\Controller\BackendController::remoteLogin($rowData, false);
                                        }
                                        if (in_array(\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite'), array(ComponentController::MODE_MANAGER, ComponentController::MODE_HYBRID))) {
                                            $domainRepo = \Env::get('em')->getRepository('Cx\Core\Net\Model\Entity\Domain');
                                            $domain     = $domainRepo->findOneBy(array('name' => \Cx\Core\Setting\Controller\Setting::getValue('customerPanelDomain','MultiSite')));
                                            if ($domain) {
                                                $actions .= \Cx\Core_Modules\MultiSite\Controller\BackendController::remoteLogin($rowData, true);
                                            }
                                        }
                                        if (in_array(\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite'), array(ComponentController::MODE_MANAGER, ComponentController::MODE_HYBRID))) {
                                            $actions .= \Cx\Core_Modules\MultiSite\Controller\BackendController::websiteBackup($rowData);
                                        }
                                        if (in_array(\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite'), array(ComponentController::MODE_MANAGER, ComponentController::MODE_HYBRID))) {
                                            $actions .= \Cx\Core_Modules\MultiSite\Controller\BackendController::multiSiteConfig($rowData, false);
                                        }
                                        return $actions;
                                    }
                    ),
                    'fields' => array(
                        'id' => array('header' => 'ID'),
                        'name' => array(
                            'header' => 'TXT_MULTISITE_SITE_ADDRESS',
                            'readonly' => true,
                            'table' => array(
                                'parse' => function($value) {
                                    $websiteUrl = '<a href="'.ComponentController::getApiProtocol() . $value . '.' . \Cx\Core\Setting\Controller\Setting::getValue('multiSiteDomain','MultiSite').'" target="_blank">' . $value . '</a>';
                                    return $websiteUrl;
                                },
                            ),
                        ),
                        'status' => array('header' => 'Status',
                            'table' => array(
                                'parse' => function($value, $arrData) {
                                    // changing a Website's status must only be allowed from within the MANAGER (or HYBRID)
                                    if (!in_array(\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite'), array(ComponentController::MODE_MANAGER, ComponentController::MODE_HYBRID))) {
                                        return $value;
                                    }
                                    $stateOnline = \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_ONLINE;
                                    $stateOffline = \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_OFFLINE;
                                    $stateDisable = \Cx\Core_Modules\MultiSite\Model\Entity\Website::STATE_DISABLED;
                                    $stateOnlineSelected = ($value == $stateOnline) ? 'selected' : '';
                                    $stateOfflineSelected = ($value == $stateOffline) ? 'selected' : '';
                                    $stateDisableSelected = ($value == $stateDisable) ? 'selected' : '';
                                    if ($value == $stateOnline || $value == $stateOffline || $value == $stateDisable) {
                                        $dropDownDisplay = '<select class="changeWebsiteStatus" data-websiteDetails= "'.$arrData['id'].'-'.$arrData['name'].'">'
                                                . '<option value = ' . $stateOnline . ' ' . $stateOnlineSelected . '>' . $stateOnline . '</option>'
                                                . '<option value = ' . $stateOffline . ' ' . $stateOfflineSelected . '>' . $stateOffline . '</option>'
                                                . '<option value = ' . $stateDisable . ' ' . $stateDisableSelected . '>' . $stateDisable . '</option>';
                                        return $dropDownDisplay;
                                    } else {
                                        return $value;
                                    }
                                },
                            )),
                        'language' => array('showOverview' => false),
                        'websiteServiceServerId' => array(
                            'header' => 'Website Service Server',
                            'table' => array(
                                'parse' => function($value) {
                                    try {
                                        $websiteServiceServer = ComponentController::getServiceServerByCriteria(array('id' => $value));
                                        if ($websiteServiceServer) {
                                            return contrexx_raw2xhtml($websiteServiceServer->getLabel().' ('.$websiteServiceServer->getHostname()).')';
                                        }
                                    } catch (\Exception $e) {}
                                    return 'Managed by this system';
                                },
                            ),
                        ),
                        'ipAddress' => array('header' => 'IP Address'),
                        'owner' => array(
                            'header' => 'Owner',
                            'table' => array(
                                'parse' => function($user) {
                                    return \FWUser::getParsedUserLink($user);
                                },
                            ),
                        ),
                        'themeId' => array(
                            'showOverview' => false
                            ),
                        'ftpUser' => array(
                            'header' => 'FTP User'
                            ),
                        'websiteServiceServer' => array(
                            'showOverview' => false
                            ),
                        'domains' => array(
                            'header' => 'Domains',
                            'table'  => array(
                                'parse' => function ($value,$arrayData) {
                                    return $this->getWebsiteDomains($arrayData['id']);
                                }
                            )
                        ),
                        'secretKey' => array(
                            'readonly'      => true,
                            'showOverview'  => false,
                        ),
                        'installationId' => array(
                            'readonly'      => true,
                            'showOverview'  => false,
                        ),
                    ),
                )
            )
        );
        $template->setVariable(array('SEARCH' => $_ARRAYLANG['TXT_MULTISITE_SEARCH'],
                                     'FILTER' => $_ARRAYLANG['TXT_MULTISITE_FILTER'],
                                     'SEARCH_TERM' => $_ARRAYLANG['TXT_MULTISITE_ENTER_SEARCH_TERM'],
                                     'SEARCH_VALUE' => isset($_GET['term']) ? contrexx_input2xhtml($_GET['term']) : '',
                                ));
        if (isset($_GET['editid']) && !empty($_GET['editid'])) {
            $template->hideBlock("website_filter");
        }
        $template->setVariable('TABLE', $view->render());
    }
    
    public function parseSectionMaintenance(\Cx\Core\Html\Sigma $template, array $cmd) 
    {
        $section = isset($cmd[1]) ? $cmd[1] : '';
        switch ($section) {
            case 'Ftp':
                $this->parseSectionFtp($template, $cmd);
                break;
            case 'BackupsAndRestore':
                $this->parseSectionBackupAndRestore($template, $cmd);
                break;

            default:
                $this->parseSectionDomains($template, $cmd);
                break;
        }
    }

    /**
     * Parse the section Affiliate
     * 
     * @param \Cx\Core\Html\Sigma $template Template object
     * @param array               $cmd      Url commands
     */
    public function parseSectionAffiliate(\Cx\Core\Html\Sigma $template, array $cmd)
    {
        $affiliateIds = array();
        
        $affiliateIdProfileAttributeId = \Cx\Core\Setting\Controller\Setting::getValue('affiliateIdProfileAttributeId','MultiSite');
        $objUser = \FWUser::getFWUserObject()->objUser->getUsers(array(
             $affiliateIdProfileAttributeId =>'_'
        ));
        if ($objUser) {
            while (!$objUser->EOF) {
                $affiliateId = $objUser->getProfileAttribute($affiliateIdProfileAttributeId);
                $affiliateIds[] = array(
                    'affiliateId' => $affiliateId,
                    'user'        => $objUser->getId(),
                    'paypal'      => $objUser->getProfileAttribute(\Cx\Core\Setting\Controller\Setting::getValue('payPalProfileAttributeId','MultiSite')),
                    'referrals'   => $affiliateId,
                );
                $objUser->next();
            }
        }
        $dataSet = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($affiliateIds);
        $view    = new \Cx\Core\Html\Controller\ViewGenerator(
            $dataSet,
            array(
                'header' => 'Affiliate IDs',
                'functions' => array(
                  'paging'  => true  
                ),
                'fields' => array(
                    'affiliateId' => array('header' => 'TXT_CORE_MODULE_MULTISITE_AFFILIATEID'),
                    'user'        => array(
                        'header' => 'TXT_CORE_MODULE_MULTISITE_USER',
                        'table' => array(
                            'parse' => function($userId, $arrData) {
                                return \FWUser::getParsedUserLink($userId);
                            }
                        )
                    ),
                    'paypal'      => array('header' => 'TXT_CORE_MODULE_MULTISITE_PAYPAL_EMAIL'),
                    'referrals'   => array(
                        'header' => 'TXT_CORE_MODULE_MULTISITE_REFERRALS',
                        'table' => array(
                            'parse' => function($affiliateId, $arrData) {
                                return \Cx\Core_Modules\MultiSite\Controller\BackendController::getReferralCountByAffiliateId($affiliateId);
                            }
                        ),
                    ),
                ),
            )
        );
        $domainRepository = new \Cx\Core\Net\Model\Repository\DomainRepository();
        $mainDomain       = $domainRepository->getMainDomain()->getName();
        $affiliateUrl     = \Cx\Core\Routing\Url::fromMagic(ASCMS_PROTOCOL . '://' . $mainDomain . \Env::get('cx')->getBackendFolderName() . '/index.php?cmd=JsonData&amp;object=MultiSite&amp;act=trackAffiliateId');
        $template->setVariable(array(
            'MULTISITE_AFFILIATE_ID_LIST'   => $view->render(),
            'MULTISITE_TRACK_AFFILIATE_URL' => $affiliateUrl->toString(true),
        ));
    }
    
    /**
     * Parse the section Ftp
     * List all Ftp accounts
     * 
     * @param \Cx\Core\Html\Sigma $template Template object
     * @param array               $cmd      Commands             
     */
    public function parseSectionFtp(\Cx\Core\Html\Sigma $template, array $cmd)
    {
        $websites = \Env::get('em')->getRepository('\Cx\Core_Modules\MultiSite\Model\Entity\Website')->findAll();
            
        $ftpAccounts = array();
        if (in_array(\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite') , array(ComponentController::MODE_SERVICE, ComponentController::MODE_HYBRID))) {
            $hostingController = \Cx\Core_Modules\MultiSite\Controller\ComponentController::getHostingController();
            $ftpAccountsArr    = $hostingController->getAllEndUserAccounts(true);
            foreach ($ftpAccountsArr as $ftpAccount) {
                $ftpAccounts[$ftpAccount['name']] = array(
                    'path'    => $ftpAccount['path'],
                    'isValid' => $ftpAccount['isValid']
                );
            }
        } else {
            $websiteServiceServers = \Env::get('em')->getRepository('\Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer')->findAll();
            foreach ($websiteServiceServers as $websiteServiceServer) {
                $ftpAccounts[$websiteServiceServer->getId()] = $this->getFtpAccountsFromService($websiteServiceServer);
            }
        }
        
        $websiteArr = $inValidFtp = array();
        foreach ($websites as $website) {
            $ftpStatus = 0;
            $ftpPath   = '';
            $websiteServiceServer = '';

            // get website service server
            if (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite') == ComponentController::MODE_MANAGER) {
                $objWebsiteServiceServer = $website->getWebsiteServiceServer();
                if ($objWebsiteServiceServer) {
                    $websiteServiceServer = $objWebsiteServiceServer->getlabel();
                }
            }

            // get FTP details
            $currentFtpAccounts = (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite') == ComponentController::MODE_MANAGER)
                                    ? ($objWebsiteServiceServer ? $ftpAccounts[$objWebsiteServiceServer->getId()] : array())
                                    : $ftpAccounts;
            
            $arrayFtpKey = array_key_exists($website->getFtpUser(), $currentFtpAccounts) ? $website->getFtpUser() : '';
            if ($arrayFtpKey) {
                $ftpStatus = $currentFtpAccounts[$arrayFtpKey]['isValid'];
                $ftpPath   = $currentFtpAccounts[$arrayFtpKey]['path'];
            }
            $ftpArr = array(
                'status'               => $ftpStatus,
                'name'                 => $website->getName(),
                'ftpUser'              => $website->getFtpUser(),
                'ftpPath'              => $ftpPath,
                'websiteServiceServer' => $websiteServiceServer
            );
            if (!$ftpStatus) {                
                $inValidFtp[] = $ftpArr;
            }
            $websiteArr[] = $ftpArr;
        }
        
        $this->parseFtpAccountsToMaintenanceSection($websiteArr, $template, 'MULTISITE_MAINTENANCE_FTP_TABLE_ALL');
        
        if (!empty($inValidFtp)) {
            $this->parseFtpAccountsToMaintenanceSection($inValidFtp, $template, 'MULTISITE_MAINTENANCE_FTP_TABLE_ERROR');
            $template->touchBlock('ftpTabError');
            $template->touchBlock('ftpTabErrorTable');
        } else {
            $template->hideBlock('ftpTabError');
            $template->hideBlock('ftpTabErrorTable');
        }
    }
    
    /**
     * Parse the Ftp accounts to the maintenance section
     * 
     * @param array               $websiteArray Array variables to parse
     * @param \Cx\Core\Html\Sigma $template     Template object
     * @param string              $placeholder  Placeholder for parsing
     */
    public function parseFtpAccountsToMaintenanceSection($websiteArray, \Cx\Core\Html\Sigma $template, $placeholder)
    {        
        $dataSet = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($websiteArray);
        $view = new \Cx\Core\Html\Controller\ViewGenerator(
            $dataSet,
            array(
                'header' => 'FTP',
                'functions' => array(
                  'paging'  => true  
                ),
                'fields' => array(
                    'name'    => array('header' => 'TXT_CORE_MODULE_MULTISITE_WEBSITENAME'),
                    'ftpUser' => array('header' => 'TXT_CORE_MODULE_MULTISITE_FTPUSER'),
                    'ftpPath' => array('header' => 'TXT_CORE_MODULE_MULTISITE_FTPPATH'),
                    'status'  => array(
                        'header' => 'TXT_CORE_MODULE_MULTISITE_WEBSITESTATE',
                        'table' => array(
                            'parse' => function($value, $arrData) {
                                return self::getStatusImageTag($value);
                            }
                        )
                    ),
                    'websiteServiceServer' => array(
                        'header' => 'TXT_CORE_MODULE_MULTISITE_WEBSITE_SERVICE_SERVER',
                        'showOverview' => \Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite') == ComponentController::MODE_MANAGER,
                    ),
                ),
            )
        );
        $template->setVariable($placeholder, $view->render());
    }
    
    /**
     * Get all Ftp accounts of the given service server
     * 
     * @param \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer $websiteServiceServer
     * 
     * @return array return ftp accounts of service server
     */
    public function getFtpAccountsFromService(\Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer $websiteServiceServer)
    {
        $ftpAccounts     = array();
        $ftpAccountsResp = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnServiceServer('getFtpAccounts', array(), $websiteServiceServer);                    
        if ($ftpAccountsResp && $ftpAccountsResp->status == 'success') {
            foreach ($ftpAccountsResp->data->data as $ftpAccount) {                
                $ftpAccounts[$ftpAccount->name] = array(
                    'path'    => $ftpAccount->path,
                    'isValid' => $ftpAccount->isValid
                );
            }
        }
        
        return $ftpAccounts;
    }

    public function parseSectionDomains(\Cx\Core\Html\Sigma $template, array $cmd)
    {
        $domains = \Env::get('em')->getRepository('\Cx\Core_Modules\MultiSite\Model\Entity\Domain')->findBy(array('componentType' => 'website'));
        self::$dnsRecords = self::getDnsRecords();
        $view = new \Cx\Core\Html\Controller\ViewGenerator(
            $domains,
            array(
                'header' => 'Domains',
                'functions' => array(
                    'edit' => in_array(\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite'), array(ComponentController::MODE_MANAGER, ComponentController::MODE_HYBRID)),
                    'delete' => in_array(\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite'), array(ComponentController::MODE_MANAGER, ComponentController::MODE_HYBRID)),
                    'sorting' => true,
                    'paging' => true, 
                    'filtering' => false,
                ),
                'fields' => array(
                    'id' => array('showOverview' => false),
                    'name' => array(
                        'header' => 'Domain',
                        'readonly' => true,
                    ),
                    'componentId' => array(
                        'readonly'      => true,
                        'showOverview'  => false,
                    ),
                    'componentType' => array(
                        'header'       => 'Type',
                        'readonly'     => true,
                        'table' => array(
                            'parse' => function($value, $arrData) {
                                return ($arrData['type'] == \CX\Core_Modules\MultiSite\Model\Entity\Domain::TYPE_FQDN 
                                            || $arrData['type'] == \Cx\Core_Modules\MultiSite\Model\Entity\Domain::TYPE_MAIL_DOMAIN
                                            || $arrData['type'] == \Cx\Core_Modules\MultiSite\Model\Entity\Domain::TYPE_WEBMAIL_DOMAIN) ? 'A' :
                                        ($arrData['type'] == \CX\Core_Modules\MultiSite\Model\Entity\Domain::TYPE_BASE_DOMAIN ? 'CNAME' : false);
                            },      
                        ),
                    ),
                    'type' => array(
                        'header'        => 'Value',
                        'readonly'      => true,
                        'table' => array(
                            'parse' => function($value, $arrData) {
                                try {
                                    $domainRepo = \Env::get('em')->getRepository('\Cx\Core_Modules\MultiSite\Model\Entity\Domain')->findOneBy(array('id' => $arrData['id']));
                                    $website = $domainRepo->getWebsite();
                                    if ($website) {
                                        return ($value == \CX\Core_Modules\MultiSite\Model\Entity\Domain::TYPE_FQDN) ? $website->getIpAddress() :
                                                ($value == \CX\Core_Modules\MultiSite\Model\Entity\Domain::TYPE_BASE_DOMAIN ? $website->getFqdn()->getName() : 
                                                 ( ($value == \Cx\Core_Modules\MultiSite\Model\Entity\Domain::TYPE_MAIL_DOMAIN || $value == \Cx\Core_Modules\MultiSite\Model\Entity\Domain::TYPE_WEBMAIL_DOMAIN) ? $website->getMailServiceServer()->getIpAddress() : false));
                                    }
                                } catch (\Exception $e) {}
                                return false;
                            },
                        ),
                    ),
                    'coreNetDomainId' => array(
                        'readonly'      => true,
                        'showOverview'  => false,
                    ),
                    'pleskId' => array(
                        'header' => 'DNS status',
                        'showOverview'  => in_array(\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite'), array(ComponentController::MODE_MANAGER, ComponentController::MODE_HYBRID)),
                        'table' => array(
                            'parse' => function($value) {                           
                               $status  = isset(self::$dnsRecords[$value]) ? true : false;
                               if ($status) {
                                   return '<img src="'. '../core/Core/View/Media/icons/led_green.gif"'. ' alt='."status_green".'/>';
                               }
                               return '<img src="'. '../core/Core/View/Media/icons/led_red.gif"'. ' alt='."status_red".'/>';
                            },
                        ),
                    ),
                ),
            )
        );
        $template->setVariable('TABLE', $view->render()); 
    }

    /**
     * Parse the section Backups and Restore
     * List all Backups
     * 
     * @param \Cx\Core\Html\Sigma $template Template object
     * @param array               $cmd      Commands             
     */
    public function parseSectionBackupAndRestore(\Cx\Core\Html\Sigma $template, array $cmd) {
        global $_ARRAYLANG;
        
        //Register backup and restore js
        \JS::registerJS('core_modules/MultiSite/View/Script/BackupAndRestore.js');
        
        \FWUser::getUserLiveSearch(array(
            'minLength' => 3,
            'canCancel' => true,
            'canClear'  => true));
        
        $downloadFile    = isset($_GET['downloadFile'])
                           ? contrexx_input2raw($_GET['downloadFile'])
                           : '';
        $serviceServerId = isset($_GET['serviceId'])
                           ? contrexx_input2int($_GET['serviceId'])
                           : 0;
        if (!empty($downloadFile)) {
            $this->downloadBackup($downloadFile, $serviceServerId);
        }
        
        if (isset($_GET['show_all'])) {
            $term = (isset($_GET['term']) && !empty($_GET['term'])) 
                    ? contrexx_input2raw($_GET['term']) 
                    : '';
            $allBackupsArray = self::getAllBackupFilesInfoAsArray($term);
            $allBackupFilesInfo = !empty($allBackupsArray) ? $allBackupsArray : null;
            $websiteBackupRepositoryDataSet = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($allBackupFilesInfo);
            $backupAndRestore = new \Cx\Core\Html\Controller\ViewGenerator(
                $websiteBackupRepositoryDataSet,
                array(
                    'header' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ACT_MAINTENANCE_BACKUPSANDRESTORE'],
                    'functions' => array(
                        'delete'   => false,
                        'sorting'  => true,
                        'paging'   => true,      
                        'filtering'=> false,
                        'actions'  => array($this, 'parseActionsForBackupAndRestore')
                    ),
                    'fields' => array(
                        'websiteName' => array(
                            'header'  => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITENAME']
                         ),
                        'dateAndTime' => array(
                            'header'  => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_DATE_AND_TIME'],
                        ),
                        'serviceServer' => array(
                            'header'       => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_SERVICE_SERVER'],
                            'showOverview' => (\Cx\Core\Setting\Controller\Setting::getValue('mode', 'MultiSite') == ComponentController::MODE_MANAGER)
                        ),
                        'serviceId'   => array(
                            'readonly'     => true,
                            'showOverview' => false
                        ),
                        'userId'      => array(
                            'readonly'     => true,
                            'showOverview' => false
                        ),
                    ),
                )
            );
            $template->setVariable('TABLE', $backupAndRestore->render());
        }
                    
        // Upload File
        $uploader = new \Cx\Core_Modules\Uploader\Model\Entity\Uploader();
        $uploader->setFinishedCallback(array(
            \Env::get('cx')->getCodeBaseCoreModulePath().'/Multisite/Controller/BackendController.class.php',
            'Cx\Core_Modules\MultiSite\Controller\BackendController',
            'uploadFinished'
        ));
        $uploader->setCallback('websiteRestoreCallbackJs');
        $uploader->setOptions(array(
            'id'                => 'page_target_browse',
            'type'              => 'button',
            'data-upload-limit' => 1,
            'allowed-extensions'=> array('zip')
            )
        );
        
        $cxjs = \ContrexxJavascript::getInstance();
        $cxjs->setVariable(array(
            'serviceServers'               => explode(',', ComponentController::getWebsiteServiceServerList()),
            'websiteRestoreInProgress'     => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_RESTORE_INPROGRESS'],
            'websiteBackupDeleteInProgress'=> $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_DELETE_INPROGRESS'],
            'websiteNameRequired'          => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_NAME_ERROR'],
            'websiteUserRequired'          => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CHOOSE_USER_ERROR'],
            'websiteRestoreConfirm'        => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_RESTORE_CONFIRM'],
            'websiteBackupDeleteConfirm'   => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_DELETE_CONFIRM'],
            'websiteRestoreTitle'          => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_RESTORE'],
            'websiteChooseService'         => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CHOOSE_SERVICE_SERVER'],
            'websiteEnterWebsiteName'      => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ENTER_WEBSITE_NAME'],
            'websiteRestoreCancelButton'   => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_CANCEL'],
            'websiteRestoreButton'         => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_RESTORE_BUTTON'],
        ), 'multisite/lang');
        
        $template->setVariable(array(
            'SEARCH'                                         => $_ARRAYLANG['TXT_MULTISITE_SEARCH'],
            'FILTER'                                         => $_ARRAYLANG['TXT_MULTISITE_FILTER'],
            'SEARCH_TERM'                                    => $_ARRAYLANG['TXT_MULTISITE_ENTER_SEARCH_TERM'],
            'SHOWALL_BACKUPS_BUTTON'                         => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_SHOWALL_BACKUPS_BUTTON'],
            'TXT_CORE_MODULE_MULTISITE_WEBSITE_RESTORE'      => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_RESTORE'],
            'TXT_CORE_MODULE_MULTISITE_CHOOSE_SERVICE_SERVER'=> $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CHOOSE_SERVICE_SERVER'],
            'TXT_CORE_MODULE_MULTISITE_ENTER_WEBSITE_NAME'   => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ENTER_WEBSITE_NAME'],
            'TXT_CORE_MODULE_MULTISITE_CHOOSE_USER'          => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CHOOSE_USER'],
            'TXT_CORE_MODULE_MULTISITE_CREATE_BACKUP_USER'   => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CREATE_BACKUP_USER'],
            'TXT_CORE_MODULE_MULTISITE_CHOOSE_ANOTHER_USER'  => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CHOOSE_ANOTHER_USER'],
            'TXT_CORE_MODULE_MULTISITE_CHOOSE_SUBSCRIPTION'  => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CHOOSE_SUBSCRIPTION'],
            'TXT_MULTISITE_WEBSITE_SUBSCRIPTION'             => $_ARRAYLANG['TXT_MULTISITE_WEBSITE_SUBSCRIPTION'],
            'TXT_CORE_MODULE_MULTISITE_WEBSITE_FIELD_REQUIRED'   => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_FIELD_REQUIRED'],
            'TXT_CORE_MODULE_MULTISITE_CREATE_NEW_SUBSCRIPTION'  => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_CREATE_NEW_SUBSCRIPTION'],
            'TXT_CORE_MODULE_MULTISITE_USE_EXISTING_SUBSCRIPTION'=> $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_USE_EXISTING_SUBSCRIPTION'],
            'TXT_CORE_MODULE_MULTISITE_WEBSITE_NAME_ERROR'   => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_NAME_ERROR'],
            'UPLOADER_CODE'                                  => $uploader->getXHtml($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_UPLOAD_BUTTON']),
            'MULTISITE_DOMAIN'                               => \Cx\Core\Setting\Controller\Setting::getValue('multiSiteDomain','MultiSite'),
        ));
        
    }
    
    /**
     * Parse Actions For BackupAndRestore
     * 
     * @param array $rowData backup data
     * 
     * @return string
     */
    public function parseActionsForBackupAndRestore($rowData)
    {
        $actions = $this->restoreOrDeleteBackupedWebsite($rowData);
        $actions.= $this->downloadBackupWebsite($rowData);
        $actions.= $this->restoreOrDeleteBackupedWebsite($rowData, true);
        return $actions;
    }
    
    /**
     * Download website backup file
     * 
     * @param string  $backupFileName  backupFilename
     * @param integer $serviceServerId serviceServerId
     * 
     * @throws MultiSiteBackendException
     */
    protected function downloadBackup($backupFileName, $serviceServerId = 0) 
    {
        global $_ARRAYLANG;
        
        try {
            if (empty($backupFileName) ) {
                throw new MultiSiteBackendException($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_INVALID_PARAMS']);
            }
            
            $resp = JsonMultiSiteController::executeCommandOnManager(
                'downloadWebsiteBackup', 
                        array(
                            'websiteBackupFileName' => $backupFileName,
                            'serviceServerId'       => $serviceServerId
                        )
            );
        
            if (   !$resp 
                || !$resp->data 
                || !$resp->data->filePath 
            ) {
                throw new MultiSiteBackendException($resp && $resp->data ? $resp->data->message : $resp->message);
            }
            
            //Download website backup file
            $objHTTPDownload = new \HTTP_Download();
            $objHTTPDownload->setFile($resp->data->filePath);
            $objHTTPDownload->setContentDisposition(HTTP_DOWNLOAD_ATTACHMENT, $backupFileName);
            $objHTTPDownload->setContentType('application/zip');
            $objHTTPDownload->send();
            exit();
        } catch (\Exception $e) {
            \DBG::log(__METHOD__.' Failed! : '. $e->getMessage());
            \Message::add(
                sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_DOWNLOAD_FAILED'], $backupFileName),
                \Message::CLASS_ERROR
            );
        } 
    }
    
    /**
     * Upload Finished callback
     * 
     * @param string  $tempPath    temporary uploaded path
     * @param string  $tempWebPath temporary uploaded webpath
     * @param array   $data        file data
     * @param integer $uploadId    upload id
     * @param array   $fileInfos   file info
     * @param array   $response    file response
     * 
     * @return array
     */
    public static function uploadFinished($tempPath, $tempWebPath, $data, $uploadId, $fileInfos, $response)
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        
        switch (\Cx\Core\Setting\Controller\Setting::getValue('mode', 'MultiSite')) {
            case ComponentController::MODE_HYBRID:
                $backupFileLocation = \Cx\Core\Setting\Controller\Setting::getValue('websiteBackupLocation', 'MultiSite');
                \Cx\Lib\FileSystem\FileSystem::path_absolute_to_os_root($backupFileLocation);
                $tempBackupPath    = $backupFileLocation;
                \Cx\Lib\FileSystem\FileSystem::path_relative_to_root($backupFileLocation);
                $tempBackupWebPath = $backupFileLocation;
                break;
            default :
                $tempBackupPath    = $cx->getWebsiteTempPath(). self::MULTISITE_BACKUP_TEMP_DIR;
                $tempBackupWebPath = $cx->getWebsiteTempWebPath() . self::MULTISITE_BACKUP_TEMP_DIR;
                break;
        }
        
        if (!\Cx\Lib\FileSystem\FileSystem::exists($tempBackupPath)) {
            \Cx\Lib\FileSystem\FileSystem::make_folder($tempBackupPath);
        }
        
        return array(
            $tempBackupPath,
            $tempBackupWebPath
        );
    }
    
    /**
     * Get all backup File info as array
     * 
     * @return mixed boolean|array
     */
    public static function getAllBackupFilesInfoAsArray($searchTerm = null)
    {
        try {
            $allBackupFilesInfo = array();
            $params = array('searchTerm' => $searchTerm);
            $resp = JsonMultiSiteController::executeCommandOnManager('getAllBackupFilesInfo', $params);
            if (!$resp || $resp->status == 'error' || $resp->data->status == 'error') {
                throw new MultiSiteBackendException('Failed to fetch the backup website details!');
            }

            if ($resp->data->backupFilesInfo) {
                foreach ($resp->data->backupFilesInfo as $data) {
                    $objUser = null;
                    if (!empty($data->userEmailId)) {
                        $objUser = \FWUser::getFWUserObject()->objUser->getUsers(array('email' => $data->userEmailId));
                    }
                    $allBackupFilesInfo[] = array(
                        'websiteName'   => $data->websiteName,
                        'dateAndTime'   => $data->creationDate, 
                        'serviceServer' => $data->serviceServer,
                        'serviceId'     => $data->serviceServerId,
                        'userId'        => ($objUser) ? $objUser->getId() : 0
                    );
                }
            }
            return $allBackupFilesInfo;
        } catch (\Exception $e) {
            \DBG::log(__METHOD__ . ' failed ! : '. $e->getMessage());
            return false;
        }
    }
    
    /**
     * Set up the page with a list of all Settings  
     * Stores the settings if requested to.
     * @return  boolean             True on success, false otherwise
     */
    static function settings($objTemplate)
    { 
        global $_ARRAYLANG;
        $mode = \Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite');
        $isModeWebsite    = ($mode == ComponentController::MODE_WEBSITE);
        $isFirstTabActive = (isset($_GET['active_tab']) && $_GET['active_tab'] == 1);
        try {
            \Cx\Core\Setting\Controller\Setting::init('MultiSite', null, 'FileSystem');  
            //check form post
            if (isset($_POST)   && !empty($_POST['bsubmit'])) {
                if (isset($_POST['websitePath']))  {
                    $_POST['websitePath']=rtrim($_POST['websitePath'],"/");
                }
                // Tab #4 is tab 'Setup'
                if (isset($_GET['active_tab']) && $_GET['active_tab'] == 4 && ($mode == ComponentController::MODE_MANAGER || ComponentController::MODE_HYBRID)) {
                    \Cx\Core\Setting\Controller\Setting::storeFromPost();
                    $params = array('setupArray' => \Cx\Core\Setting\Controller\Setting::getArray('MultiSite', 'setup'));
                    $webServiceServers = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\WebsiteServiceServer')->findAll();
                    foreach ($webServiceServers as $webServiceServer) {
                        $resp = \Cx\Core_Modules\MultiSite\Controller\JsonMultiSiteController::executeCommandOnServiceServer('updateServiceServerSetup', $params, $webServiceServer);
                        if (!$resp || $resp->status == 'error') {
                            $errMsg = isset($resp->message) ? $resp->message : '';
                            \DBG::dump($errMsg);
                            if (isset($resp->log)) {
                                \DBG::appendLogs($resp->log);
                            }
                            throw new \Cx\Core_Modules\MultiSite\Model\Entity\WebsiteException('Problem in service servers update setup process'.$errMsg);    
                        }
                    }
                } elseif ( $isModeWebsite && $isFirstTabActive) {
                    //get post values
                    $savePostDataError = false;
                    $postWebsiteMode   = isset($_POST['website_mode'])
                        ? contrexx_input2raw($_POST['website_mode']) : '';
                    $postWebsiteServer = isset($_POST['website_server'])
                        ? contrexx_input2raw($_POST['website_server']) : '';
                    //get settings value
                    $websiteMode   = \Cx\Core\Setting\Controller\Setting::getValue(
                        'website_mode', 'MultiSite'
                    );
                    $websiteServer = \Cx\Core\Setting\Controller\Setting::getValue(
                        'website_server', 'MultiSite'
                    );
                    $websiteName   = \Cx\Core\Setting\Controller\Setting::getValue(
                        'websiteName', 'MultiSite'
                    );
                    if (   $postWebsiteMode != $websiteMode
                        || $postWebsiteServer != $websiteServer
                    ) {
                        $response = null;
                        if ($websiteMode == ComponentController::WEBSITE_MODE_SERVER) {
                            $response = JsonMultiSiteController::executeCommandOnMyServiceServer(
                                'checkServerWebsiteAccessedByClient',
                                array('websiteName' => $websiteName)
                            );
                        }
                        if (   $response
                            && (   $response->status == 'error'
                                || $response->data->isServerAccessByclient
                               )
                        ) {
                            $savePostDataError = true;
                        } else {
                            $params = array(
                                'websiteName'     => $websiteName,
                                'mode'            => $postWebsiteMode,
                                'serverWebsiteId' => $postWebsiteServer
                            );
                            $response = JsonMultiSiteController::executeCommandOnManager(
                                'setWebsiteDetails', $params
                            );
                            if (   !$response
                                || $response->status == 'error'
                                || $response->data->status == 'error'
                            ) {
                                $savePostDataError = true;
                            }
                        }
                    }
                    if ($savePostDataError) {
                        \Message::warning(
                            $_ARRAYLANG['TXT_MULTISITE_SETTINGS_UPDATE_WEBSITE_SERVER_AND_MODE_ERROR_MSG']
                        );
                    } else {
                        \Cx\Core\Setting\Controller\Setting::storeFromPost();
                    }
                } else {
                    \Cx\Core\Setting\Controller\Setting::storeFromPost();
                }
            }

            // fetch MultiSite operation mode and set websiteController
            $websiteController = \Cx\Core\Setting\Controller\Setting::getValue('websiteController','MultiSite');

            if (!$isModeWebsite) {
                \Cx\Core\Setting\Controller\Setting::setEngineType('MultiSite', 'FileSystem', 'config');    
                \Cx\Core\Setting\Controller\Setting::show(
                    $objTemplate,
                    'index.php?cmd=MultiSite&act=settings',
                    $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE'],
                    'General',
                    'TXT_CORE_MODULE_MULTISITE_'
                );
            }
            
            if (in_array($mode, array(ComponentController::MODE_MANAGER,ComponentController::MODE_HYBRID))) {
                \Cx\Core\Setting\Controller\Setting::setEngineType('MultiSite', 'FileSystem', 'manager');    
                \Cx\Core\Setting\Controller\Setting::show(
                    $objTemplate,
                    'index.php?cmd=MultiSite&act=settings',
                    $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE'],
                    'Manager',
                    'TXT_CORE_MODULE_MULTISITE_'
                );
            }
          
            if (in_array($mode, array(ComponentController::MODE_MANAGER, ComponentController::MODE_SERVICE, ComponentController::MODE_HYBRID))) {
                \Cx\Core\Setting\Controller\Setting::setEngineType('MultiSite', 'FileSystem', 'server');
                \Cx\Core\Setting\Controller\Setting::show(
                    $objTemplate,
                    'index.php?cmd=MultiSite&act=settings',
                    $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE'],
                    'Server',
                    'TXT_CORE_MODULE_MULTISITE_'
                );
            }

            if (in_array($mode, array(ComponentController::MODE_MANAGER, ComponentController::MODE_SERVICE, ComponentController::MODE_HYBRID))) {
                \Cx\Core\Setting\Controller\Setting::setEngineType('MultiSite', 'FileSystem', 'setup');    
                \Cx\Core\Setting\Controller\Setting::show(
                    $objTemplate,
                    'index.php?cmd=MultiSite&act=settings',
// TODO: The configuration options multiSiteDomain, unavailablePrefixes, websiteNameMaxLength and  websiteNameMinLength must be set remotely by the Website Manager
//       Once implemented, those options must be read-only or not getting listed at all
                    $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE'] .($mode == ComponentController::MODE_SERVICE ? $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_OPTIONS_SET_BY_WEBSITE_MANAGER'] : ''),
                    'Setup',
                    'TXT_CORE_MODULE_MULTISITE_', $mode == ComponentController::MODE_SERVICE ? true : ''
                );
            }
            
            if ($mode == ComponentController::MODE_SERVICE) {
                \Cx\Core\Setting\Controller\Setting::setEngineType('MultiSite', 'FileSystem', 'websiteManager');
                \Cx\Core\Setting\Controller\Setting::show(
                    $objTemplate,
                    'index.php?cmd=MultiSite&act=settings',
                    $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE'],
                    'Website Manager',
                    'TXT_CORE_MODULE_MULTISITE_'
                );
            }
            
           if (in_array($mode, array(ComponentController::MODE_SERVICE, ComponentController::MODE_HYBRID))) {
                \Cx\Core\Setting\Controller\Setting::setEngineType('MultiSite', 'FileSystem', 'websiteSetup');    
                \Cx\Core\Setting\Controller\Setting::show(
                    $objTemplate,
                    'index.php?cmd=MultiSite&act=settings',
                    $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE'],
                    'Website Setup',
                    'TXT_CORE_MODULE_MULTISITE_'
                );
            }
            
            if (in_array($mode, array(ComponentController::MODE_MANAGER, ComponentController::MODE_HYBRID))) {
                $isAffiliateSystemActivated    = \Cx\Core\Setting\Controller\Setting::getValue('affiliateSystem', 'MultiSite');
                $isconversionTrackingActivated = \Cx\Core\Setting\Controller\Setting::getValue('conversionTracking', 'MultiSite');
                
                if ($isAffiliateSystemActivated) {
                    \Cx\Core\Setting\Controller\Setting::setEngineType('MultiSite', 'FileSystem', 'affiliate');
                    \Cx\Core\Setting\Controller\Setting::show(
                        $objTemplate,
                        'index.php?cmd=MultiSite&act=settings',
                        $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE'],
                        'Affiliate',
                        'TXT_CORE_MODULE_MULTISITE_'
                    );
                }
                
                // for tab Conversions
                if ($isconversionTrackingActivated) {
                    \Cx\Core\Setting\Controller\Setting::setEngineType('MultiSite', 'FileSystem', 'conversion');
                    \Cx\Core\Setting\Controller\Setting::show(
                        $objTemplate,
                        'index.php?cmd=MultiSite&act=settings',
                        $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE'],
                        'Conversions',
                        'TXT_CORE_MODULE_MULTISITE_'
                    );
                }
            }

            if (
                in_array(
                    $mode,
                    array(
                        ComponentController::MODE_MANAGER,
                        ComponentController::MODE_SERVICE,
                        ComponentController::MODE_HYBRID
                    )
                ) &&
                in_array($websiteController, array('plesk', 'aws'))
            ) {
                $controllerClass = '\\' . __NAMESPACE__ . '\\' . ucfirst(
                    $websiteController
                ) . 'Controller';
                $controllerClass::initSettings();
                \Cx\Core\Setting\Controller\Setting::setEngineType(
                    'MultiSite',
                    'FileSystem',
                    $websiteController
                );
                \Cx\Core\Setting\Controller\Setting::show(
                    $objTemplate,
                    'index.php?cmd=MultiSite&act=settings',
                    $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE'],
                    ucfirst($websiteController),
                    'TXT_CORE_MODULE_MULTISITE_'
                );
            }

            if ($isModeWebsite) {
                // config section if the MultiSite is run as Website
                \Cx\Core\Setting\Controller\Setting::setEngineType('MultiSite', 'FileSystem', 'website');
                \Cx\Core\Setting\Controller\Setting::show(
                    $objTemplate,
                    'index.php?cmd=MultiSite&act=settings',
                    $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE'],
                    'Website Service',
                    'TXT_CORE_MODULE_MULTISITE_'
                );
                \ContrexxJavascript::getInstance()->setVariable(
                    'websiteMode',
                    ComponentController::WEBSITE_MODE_CLIENT,
                    'multisite/website'
                );
            }
        } catch (\Exception $e) {
            \DBG::msg($e->getMessage());
            \Message::error($e->getMessage());
        }
                    
    }
    
    /**
     * 
     * @global type $_ARRAYLANG
     * @param type $rowData
     * @return type
     * @throws \MultiSiteJsonException
     */
    public function executeSql($rowData, $service = false) {
        global $_ARRAYLANG;
        
        if ($service) {
            $websiteServiceId = $rowData['id'];
            $data = "service:".$rowData['id'];
        } else {
           $websiteId = $rowData['id']; 
           $data = "website:".$rowData['id'];
        }
        
        $webRepo  = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
        if (!empty($websiteId)) {
            $website  = $webRepo->findOneById($websiteId);
            if (!$website) {
                return;
            }
            if (!$website->getFqdn()) {
                return;
            }
            $title = $_ARRAYLANG['TXT_MULTISITE_EXECUTE_QUERY_ON_WEBSITE'].$website->getFqdn()->getName();
        }
        
        if (!empty($websiteServiceId)) {
            $websites = $webRepo->findBy(array('websiteServiceServerId' => $websiteServiceId));
            if (!$websites) {
                return;
            }
            $websiteServiceServer = ComponentController::getServiceServerByCriteria(array('id' => $websiteServiceId));
            $title = $_ARRAYLANG['TXT_MULTISITE_EXECUTE_QUERY_ON_ALL_WEBSITES_OF_SERVICE_SERVER'].$websiteServiceServer->getHostname();
            $websiteId = $websiteServiceId;
        }
        
        $cxjs = \ContrexxJavascript::getInstance();
        $cxjs->setVariable(array('completedMsg' => $_ARRAYLANG['TXT_MULTISITE_EXECUTED_QUERY_COMPLETED'], 
                                 'errorMsg' => $_ARRAYLANG['TXT_MULTISITE_EXECUTED_QUERY_FAILED'],
                                 'queryExecutedWebsite' => $_ARRAYLANG['TXT_MULTISITE_QUERY_EXECUTED_ON_WEBSITE'],
                                 'sqlQuery' => $_ARRAYLANG['TXT_MULTISITE_SQL_QUERY'],
                                 'sqlStatus' => $_ARRAYLANG['TXT_MULTISITE_SQL_STATUS'],
                                 'plsInsertQuery' => $_ARRAYLANG['TXT_MULTISITE_PLEASE_INSERT_QUERY'],
                        ), 'multisite/lang');
        $className = 'executeQuery executeQuery_'.$websiteId;
        $dbEdit = '<a href="javascript:void(0);" class="dbEdit '.$className.'" title="'.$title.'" data-params="'.$data.'"></a>';
        return $dbEdit;
    }
    /**
     * Fetching the license information from the associated website
     * 
     * @global \Cx\Core_Modules\MultiSite\Controller\type $_ARRAYLANG
     * @param type $rowData
     * @return string
     */
    public function showLicense($rowData) {
        global $_ARRAYLANG;

        $websiteId = $rowData['id'];
        $data = "websiteId:" . $rowData['id'];
        $webRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
        if (!empty($websiteId)) {
            $website = $webRepo->findOneById($websiteId);
            if (!$website) {
                return;
            }
            if (!$website->getFqdn()) {
                return;
            }
            $title = $_ARRAYLANG['TXT_MULTISITE_FETCH_LICENSE_INFO'] . $website->getFqdn()->getName();
        }
        $cxjs = \ContrexxJavascript::getInstance();
        $cxjs->setVariable(array(
            'licenseInfo'     => $_ARRAYLANG['TXT_MULTISITE_LICENSE_INFO'],
            'getLicenseTitle' => $_ARRAYLANG['TXT_MULTISITE_LICENSE_DATA_TITLE']
        ), 'multisite/lang');
        $className = 'showLicense_' . $websiteId;
        $showLicense = '<a href="javascript:void(0);" data-websitename="'. $website->getFqdn()->getName() .'" class="showLicense ' . $className . '" title="' . $title . '">';

        return $showLicense;
    }

    /**
     * Remote Login to Website
     * 
     * @param type $rowData arrayData of website
     * 
     * @return string
     */
    public function remoteLogin($rowData, $customerPanelLogin = false)
    {
        global $_ARRAYLANG;
        
        $wesiteId = $rowData['id'];
        $webRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
        if (!empty($wesiteId)) {
            $website = $webRepo->findOneById($wesiteId);
            if (!$website) {
                return;
            }
            if (!$website->getFqdn()) {
                return;
            }
            $remoteLoginType = $customerPanelLogin ? 'customerpanel' : 'website';
            $style           = $customerPanelLogin ? 'customerPanelLogin' : 'remoteWebsiteLogin';
            $title           =  $customerPanelLogin ? $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_REMOTE_LOGIN_CUSTOMERPANELDOMAIN_TITLE'] 
                                                    : sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_REMOTE_LOGIN_TITLE'], $website->getFqdn()->getName());
        }
        
        $websiteRemoteLogin = '<a href="javascript:void(0);" class = "'.$style.'" data-login = "'.$remoteLoginType.'" data-id = "'.$wesiteId.'" title = "'.$title.'" ></a>';
        return $websiteRemoteLogin; 
    }
    
    /**
     * Backup the website
     * 
     * @param array   $rowData arrayData of website
     * @param boolean $serviceServer serviceServer
     * @return string
     */
    public function websiteBackup($rowData, $serviceServer = false) 
    {
        global $_ARRAYLANG;
        
        $id = $rowData['id'];
        if (empty($id)) {
            return;
        }
        
        $websiteRepo   = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
        $resultObj     = ($serviceServer) 
                         ? ComponentController::getServiceServerByCriteria(array('id' => $id))
                         : $websiteRepo->findOneById($id);
        
        if (!$resultObj) {
            return;
        }
        if ($resultObj instanceof \Cx\Core_Modules\MultiSite\Model\Entity\Website && !$resultObj->getFqdn()) {
            return;
        }
        $title  = ($serviceServer) 
                  ? sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_IN_SERVICE_TITLE'], $resultObj->getHostname())
                  : sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_TITLE'], $resultObj->getFqdn()->getName());
        $dataId = ($serviceServer) ? 'service:' . $id : 'website:' . $id;
        $cxjs   = \ContrexxJavascript::getInstance();
        
        $cxjs->setVariable(array(
            'websiteBackupConfirm' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_CONFIRM'],
            'websiteInProgress'    => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_INPROGRESS']
        ), 'multisite/lang');
        
        $websiteBackup = '<a href="javascript:void(0);" class = "websiteBackup" data-params = '.$dataId.' title = "'.$title.'" ></a>';
        return $websiteBackup; 
    }
    
    /**
     * Restore Or Delete the backuped website
     * 
     * @param array $rowData
     * @return mixed boolean | string
     */
    protected function restoreOrDeleteBackupedWebsite($rowData, $deleteBackupedWebsite = false)
    {
        global $_ARRAYLANG;
        
        if (   empty($rowData) 
            || empty($rowData['websiteName'])
            ) {
            return false;
        }
        
        $timeStamp    = strtotime($rowData['dateAndTime']);
        $websiteBackupFileName = isset($rowData['dateAndTime']) &&  $timeStamp 
                                 ? $rowData['websiteName'].'_'.date('Y-m-d H-i-s', $timeStamp)
                                 : $rowData['websiteName'];
        $title      = ($deleteBackupedWebsite) 
                      ? $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_DELETE'] 
                      : $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_RESTORE'];
        $class      = $deleteBackupedWebsite
                      ? 'deleteWebsiteBackup delete' 
                      : 'websiteRestore';
        $userExists = !empty($rowData['userId']) && !$deleteBackupedWebsite 
                      ? 'data-userId = "'.$rowData['userId'].'"' 
                      : '';
        $serviceServerId = !empty($rowData['serviceId'])
                           ? 'data-serviceId ="'.$rowData['serviceId'].'"'
                           : '';
        return '<a href="javascript:void(0);" class="'.$class.'" '.$serviceServerId.' data-backupFile = "'.$websiteBackupFileName.'.zip"  '.$userExists.'  title = "'.$title.'"></a>';
    }
   
    /**
     * Button to show download backup website
     * 
     * @param array $rowData backupData
     * @return mixed boolean | string
     */
    protected function downloadBackupWebsite($rowData)
    {
        global $_ARRAYLANG;
        
        if (   empty($rowData) 
            || empty($rowData['websiteName'])
        ) {
            return false;
        }
        
        $timeStamp    = strtotime($rowData['dateAndTime']);
        $websiteBackupFileName = isset($rowData['dateAndTime']) &&  $timeStamp
                                 ? $rowData['websiteName'].'_'.date('Y-m-d H-i-s', $timeStamp)
                                 : $rowData['websiteName'];
        $title = $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_BACKUP_DOWNLOAD_TITLE'];
        $downloadUrl = \Cx\Core\Core\Controller\Cx::instanciate()->getRequest()->getUrl();
        $downloadUrl->setParams(array(
            'downloadFile' => $websiteBackupFileName.'.zip',
            'serviceId'    => $rowData['serviceId']
            )
        );
        
        return '<a href="'.$downloadUrl.'" class="downloadWebsiteBackup" title = "'.$title.'"></a>';
    }
    
    /**
     * Fetching the plans from the associated mail server
     * 
     * @global array $_ARRAYLANG
     * @param array $rowData
     * @return string $plans
     */
    public function getMailServicePlans($rowData) {
        global $_ARRAYLANG;
        
        $mailServerId = $rowData['id'];
        if (empty($mailServerId)) {
            return false;
        }
        $mailServerRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\MailServiceServer');
        $mailServer = $mailServerRepo->findOneById($mailServerId);
        if (!$mailServer) {
            return false;
        }
        $title = $_ARRAYLANG['TXT_MULTISITE_MAIL_SERVICE_PLAN_INFO'] . $mailServer->getLabel() . ' (' . $mailServer->getHostName() . ')';
        $planIcon = '<a href="javascript:void(0);" class="mailServerPlans mailServerPlans_' . $mailServerId . '" title="' . $title . '">';

        return $planIcon;
    }

    /**
     * Multisite Configuration of the selected website
     * 
     * @param array $rowData websiteData
     * @return string
     */
    public function multiSiteConfig($rowData)
    {
        global $_ARRAYLANG;
        
        $wesiteId = $rowData['id'];
        $webRepo = \Env::get('em')->getRepository('Cx\Core_Modules\MultiSite\Model\Entity\Website');
        if (!empty($wesiteId)) {
            $website = $webRepo->findOneById($wesiteId);
            if (!$website) {
                return;
            }
            if (!$website->getFqdn()) {
                return;
            }
            $title = 'Fetch MultiSite Configuration of a Website: ' . $website->getFqdn()->getName();
        }
        $cxjs = \ContrexxJavascript::getInstance();
        $cxjs->setVariable(array(
            'addNewConfig'        => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADD_CONFIG'],
            'addNewConfigTitle'   => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADD_CONFIG_TITLE'],
            'configOptionTooltip' => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_ADD_CONFIG_OPTION_TOOLTIP'],
            'deleteConfirm'       => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_DELETE_CONFIG_OPTION'],
            'configAlertMessage'  => $_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_CONFIG_ALERT_MESSAGE']
        ), 'multisite/lang');
        $websiteMultiSiteConfig = '<a href="javascript:void(0);"  class = "multiSiteWebsiteConfig" data-id = "' . $wesiteId . '" title = "' . $title . '" data-title ="'.$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_WEBSITE_CONFIG_TITLE'].$website->getFqdn()->getName().'" ></a>';
        return $websiteMultiSiteConfig;
    }
    
    /**
     * get Dns Records
     * 
     * @return array 
     */
    public static function getDnsRecords()
    {
        $hostingController  = ComponentController::getHostingController();
        
        return $hostingController->getDnsRecords();
    }
    

    /**
     * Get All Domains Based On Website
     * 
     * @param integer $websiteId websiteId
     * 
     * @return array 
     */
    public function getWebsiteDomains($websiteId)
    {
        $domainRepo  = \Env::get('em')->getRepository('\Cx\Core_Modules\MultiSite\Model\Entity\Domain');
        $domains     = $domainRepo->findBy(array('componentId' => $websiteId,'componentType' => ComponentController::MODE_WEBSITE));
        $domainArray = array();
        foreach ($domains as $domain) {
            $domainArray[]    = $domain->getName();
        }
        return implode(",<br>", $domainArray);
    }
    
    /**
     * Get the Referral count by Affiliate ID
     * 
     * @staticvar array  $referrals   Holds the referral count
     * @param     string $affiliateId User affiliate id
     * 
     * @return integer Return the Referral count by Affiliate ID
     */
    public static function getReferralCountByAffiliateId($affiliateId)
    {
        static $referrals = null;
        
        if (!isset($referrals)) {
            $referrals = array();
            $affiliateIdReferenceProfileAttributeId = \Cx\Core\Setting\Controller\Setting::getValue('affiliateIdReferenceProfileAttributeId','MultiSite');
            $objUser = \FWUser::getFWUserObject()->objUser->getUsers(array(
                 $affiliateIdReferenceProfileAttributeId =>'_',
                'active' => true,
                'verified' => true
            ));
            if ($objUser) {
                while (!$objUser->EOF) {
                    $userAffiliateId = $objUser->getProfileAttribute($affiliateIdReferenceProfileAttributeId);
                    $referrals[$userAffiliateId] = isset($referrals[$userAffiliateId]) ? $referrals[$userAffiliateId] + 1 : 1;
                    $objUser->next();
                }
            }
        }
        
        return isset($referrals[$affiliateId]) ? $referrals[$affiliateId] : 0;
    }
    
    /**
     * Get the status image tag
     * 
     * @staticvar string $htmlImgTag
     * @param boolean $status
     * 
     * @return string
     */
    private static function getStatusImageTag($status)
    {
        static $htmlImgTag = '<img src="%1$s" alt="%2$s" />';
        
        $src = '../core/Core/View/Media/icons/';
        $src .= $status ? 'led_green.gif' : 'led_red.gif';
        $alt = $status ? 'success' : 'error';
        return sprintf($htmlImgTag, contrexx_raw2xhtml($src), $alt);
    }
}
