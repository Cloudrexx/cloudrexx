<?php
/**
 * Class AwsController
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_multisite
 */

namespace Cx\Core_Modules\MultiSite\Controller;

/**
 * Reports error during the API request
 */
class AwsRoute53Exception extends DnsControllerException {}

/**
 * Class AwsController
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_multisite
 */

class AwsController extends HostController {

    /**
     * List of the available regions
     *
     * @var array
     */
    protected static $regions = array(
        'us-east-1'      => 'US East (N. Virginia)',
        'us-east-2'      => 'US East (Ohio)',
        'us-west-1'      => 'US West (N. California)',
        'us-west-2'      => 'US West (Oregon)',
        'ca-central-1'   => 'Canada (Central)',
        'eu-west-1'      => 'EU (Ireland)',
        'eu-central-1'   => 'EU (Frankfurt)',
        'eu-west-2'      => 'eu-west-2: EU (London)',
        'ap-northeast-1' => 'Asia Pacific (Tokyo)',
        'ap-northeast-2' => 'Asia Pacific (Seoul)',
        'ap-southeast-1' => 'Asia Pacific (Singapore)',
        'ap-southeast-2' => 'Asia Pacific (Sydney)',
        'ap-south-1'     => 'Asia Pacific (Mumbai)',
        'sa-east-1'      => 'South America (São Paulo)'
    );

    /**
     * Resource record cache time to live in seconds
     *
     * @var integer
     */
    protected $ttl;

    /**
     * Hosted zone ID
     *
     * @var string
     */
    protected $hostedZoneId;

    /**
     * API version to use
     * @deprecated Each AWS API client should define its own version
     * @var string
     */
    protected $version;

    /**
     * AWS API key name
     *
     * @var string
     */
    protected $credentialsSecret;

    /**
     * AWS API key value
     *
     * @var string
     */
    protected $credentialsSecret;

    /**
     * List of AWS client instances, max. 1 per service
     * @var array Key=>value array, key is service name
     */
    protected $awsClients = array();

    /**
     * XAMPP controller for user storage (temporary)
     */
    protected $userStorageController;

    /**
     * XAMPP controller for db storage
     */
    protected $dbController;

    /**
     * {@inheritdoc}
     */
    public static function initSettings() {
        $settings = array(
            'region' => array(
                'value' => '',
                'type' => \Cx\Core\Setting\Controller\Setting::TYPE_DROPDOWN,
                'values' => '{src:\\'.__CLASS__.'::getRegions()}', 
            ),
            'credentialsKey' => array(
                'value' => null,
            ),
            'credentialsSecret' => array(
                'value' => '',
            ),
            'version' => array(
                'value' => 'latest',
            ),
            'timeToLive' => array(
                'value' => 60,
            ),
            'hostedZoneId' => array(
                'value' => '',
            ),
        );
        $i = 0;
        \Cx\Core\Setting\Controller\Setting::init('MultiSite', 'aws', 'FileSystem');
        foreach ($settings as $name=>$initValues) {
            $i++;
            if (\Cx\Core\Setting\Controller\Setting::getValue($name, 'MultiSite') !== null) {
                continue;
            }
            if (!isset($initValues['type'])) {
                $initValues['type'] = \Cx\Core\Setting\Controller\Setting::TYPE_TEXT;
            }
            if (!isset($initValues['values'])) {
                $initValues['values'] = null;
            }
            if (
                !\Cx\Core\Setting\Controller\Setting::add(
                    $name,
                    $initValues['value'],
                    $i,
                    $initValues['type'],
                    $initValues['values'],
                    'plesk'
                )
            ) {
                throw new MultiSiteException(
                    'Failed to add setting entry "' . $name . '" for ' . __CLASS__
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function fromConfig() {
        return new static(
            \Cx\Core\Setting\Controller\Setting::getValue('credentialsKey', 'MultiSite'),
            \Cx\Core\Setting\Controller\Setting::getValue('credentialsSecret', 'MultiSite'),
            \Cx\Core\Setting\Controller\Setting::getValue('region', 'MultiSite'),
            \Cx\Core\Setting\Controller\Setting::getValue('version', 'MultiSite'),
            \Cx\Core\Setting\Controller\Setting::getValue('timeToLive', 'MultiSite'),
            \Cx\Core\Setting\Controller\Setting::getValue('hostedZoneId', 'MultiSite')
        );
    }

    /**
     * Constructor
     *
     * @param string $credentialsKey    AWS access key ID
     * @param string $credentialsSecret AWS secret access key
     * @param string $region            AWS region
     * @param string $version           AWS version
     * @param integer $ttl              Time to life
     * @param string $hostedZoneId      AWS zone id
     * @throws \Aws\Exception\AwsException When connection fails
     */
    public function __construct(
        $credentialsKey,
        $credentialsSecret,
        $region,
        $version,
        $ttl,
        $hostedZoneId
    ) {
        //Load the AWS SDK
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $cx->getClassLoader()->loadFile(
            $cx->getCodeBaseLibraryPath() . '/Aws/aws.phar'
        );
        $this->ttl = $ttl;
        $this->hostedZoneId = $hostedZoneId;
        $this->version = $version;
        $this->credentialsKey = $credentialsKey;
        $this->credentialsSecret = $credentialsSecret;
    }

    /**
     * Get Regions list
     *
     * @return array
     */
    public static function getRegions()
    {
        return static::$regions;
    }

    /**
     * Set Resource record cache time to live in seconds
     *
     * @param integer $timeToLive
     */
    public function setTimeToLive($timeToLive)
    {
        $this->ttl;
    }

    /**
     * Get timeToLive
     *
     * @return integer
     */
    public function getTimeToLive()
    {
        return $this->ttl;
    }

    /**
     * Get hosted zone ID
     *
     * @return string
     */
    public function getHostedZoneId()
    {
        return $this->hostedZoneId;
    }

    /*******************************/
    /* D N S - C O N T R O L L E R */
    /*******************************/

    /**
     * Add DNS Record
     *
     * @param string  $type   DNS-Record type
     * @param string  $host   DNS-Record host
     * @param string  $value  DNS-Record value
     * @param string  $zone   Name of DNS-Zone
     * @param integer $zoneId Id of Hosted zone
     *
     * @return integer
     * @throws AwsRoute53Exception
     */
    public function addDnsRecord($type = 'A', $host, $value, $zone, $zoneId)
    {
        return $this->manipulateDnsRecord(
            'CREATE',
            $type,
            $host,
            $value,
            $zone,
            $zoneId,
            $this->getTimeToLive()
        );
    }

    /**
     * Update DNS Record
     *
     * @param string  $type     DNS-Record type
     * @param string  $host     DNS-Record host
     * @param string  $value    DNS-Record value
     * @param string  $zone     Name of DNS-Zone
     * @param integer $zoneId   Id of Hosted zone
     * @param integer $recordId DNS record ID
     *
     * @return integer
     * @throws AwsRoute53Exception
     */
    public function updateDnsRecord(
        $type,
        $host,
        $value,
        $zone,
        $zoneId,
        $recordId
    ) {
        return $this->manipulateDnsRecord(
            'UPSERT',
            $type,
            $host,
            $value,
            $zone,
            $zoneId,
            $this->getTimeToLive()
        );
    }

    /**
     * Remove DNS Record
     *
     * @param string  $type     DNS-Record type
     * @param string  $host     DNS-Record host
     * @param integer $recordId DNS record ID
     * @throws AwsRoute53Exception
     */
    public function removeDnsRecord($type, $host, $recordId)
    {
        if (empty($this->getHostedZoneId())) {
            return;
        }
        $dnsRecords = array();
        $this->fetchDnsRecords(
            array(
                'HostedZoneId'    => $this->getHostedZoneId(),
                'MaxItems'        => '1',
                'StartRecordName' => $host,
                'StartRecordType' => $type
            ),
            $dnsRecords
        );
        if (!isset($dnsRecords[$host])) {
            return;
        }
        $dnsRecord = $dnsRecords[$host];
        $this->manipulateDnsRecord(
            'DELETE',
            $dnsRecord['type'],
            $dnsRecord['name'],
            $dnsRecord['value'],
            '',
            $this->getHostedZoneId(),
            $dnsRecord['ttl']
        );
    }

    /**
     * Get DNS Records
     *
     * @throws AwsRoute53Exception
     * @return array
     */
    public function getDnsRecords()
    {
        if (empty($this->getHostedZoneId())) {
            return array();
        }

        $dnsRecords = array();
        $this->fetchDnsRecords(
            array('HostedZoneId' => $this->getHostedZoneId()),
            $dnsRecords
        );
        return $dnsRecords;
    }

    /**
     * {@inheritdoc}
     */
    public function getDnsRecord($name, $pleskId = 0) {
        // @todo
    }

    /**
     * Get Route53 client object
     *
     * @return \Aws\Route53\Route53Client
     */
    protected function getRoute53Client()
    {
        return $this->getAwsClient('Route53', 'us-east-1', 'latest');
    }

    /**
     * Manipulate DNS record
     *
     * @param string                     $action Action value(CREATE|UPSERT|DELETE)
     * @param string                     $type   DNS-Record type
     * @param string                     $host   DNS-Record host
     * @param string                     $value  DNS-Record value
     * @param string                     $zone   Name of DNS-Zone
     * @param integer                    $zoneId Id of Hosted zone
     * @param integer                    $timeToLive Resource Record cache live time
     *
     * @throws AwsRoute53Exception
     * @return integer
     */
    protected function manipulateDnsRecord(
        $action,
        $type,
        $host,
        $value,
        $zone,
        $zoneId,
        $timeToLive
    ) {
        $client = $this->getRoute53Client();
        try {
            // In case the record is a subdomain of the DNS-zone, then
            // we'll have to strip the DNS-zone part from the record.
            // I.e.:
            //      DNS-zone ($zone):   example.com
            //      DNS-record ($host): foo.example.com
            //      strip $host to:     foo
            if (
                $zone &&
                preg_match('/^(.*)\.' . preg_quote($zone) . '$/', $host, $match)
            ) {
                $host = $match[1];
            }
            $client->changeResourceRecordSets(array(
                'ChangeBatch' => array(
                    'Changes' => array(
                        'Action' => $action,
                        'ResourceRecordSet' => array(
                            'Name' => rtrim($host, '.'),
                            'ResourceRecords' => array(
                                array(
                                    'Value' => $value
                                )
                            ),
                            'TTL'  => $timeToLive,
                            'Type' => $type
                        )
                    )
                ),
                'HostedZoneId' => $zoneId
            ));
            return 0;
        } catch (\Aws\Exception\AwsException $e) {
            throw new AwsRoute53Exception($e->getMessage());
        }
    }

    /**
     * Fetch DNS records list
     *
     * @param array                      $options    Parameter details for
     *                                               listResourceRecordSets
     * @param array                      $dnsRecords Array of Resource Recordset
     *
     * @throws AwsRoute53Exception
     */
    protected function fetchDnsRecords($options, &$dnsRecords)
    {
        $client = $this->getRoute53Client();
        try {
            $result = $client->listResourceRecordSets($options);
            if (!isset($result['ResourceRecordSets'])) {
                return;
            }
            foreach ($result['ResourceRecordSets'] as $recordSet) {
                $dnsRecords[$recordSet['Name']] = array(
                    'name'  => $recordSet['Name'],
                    'value' => $recordSet['ResourceRecords'][0]['Value'],
                    'ttl'   => $recordSet['TTL'],
                    'type'  => $recordSet['Type']
                );
            }
            if (!$result['IsTruncated']) {
                return;
            }
            $this->fetchDnsRecords(
                array(
                    'HostedZoneId'    => $this->getHostedZoneId(),
                    'StartRecordName' => $result['NextRecordName'],
                    'StartRecordType' => $result['NextRecordType']
                ),
                $dnsRecords
            );
        } catch (\Aws\Exception\AwsException $e) {
            throw new AwsRoute53Exception($e->getMessage());
        }
    }

    /***********************************************/
    /* U S E R S T O R A G E - C O N T R O L L E R */
    /***********************************************/

    /**
     * {@inheritdoc}
     */
    public function createUserStorage($websiteName, $codeBase = '') {
        // create S3 bucket
        $result = $this->getS3Client()->createBucket(array(
            'Bucket' => $websiteName,
        ));
        if (!is_array($result) || !isset($result['Location'])) {
            \DBG::dump($result);
            throw new AwsControllerException('AWS responded with invalid result');
        }
        $bucketLocation = $result['Location'];

        // create IAM user with access to the bucket
   
        // copy skeleton to the bucket
        // TODO: this is not yet possible, therefore we copy the skeleton to
        // EFS using XamppController for now. See end of this method.

        // update cloudfront to redirect requests to S3
        $updatedConfig = $this->getWebDistributionConfig($websiteName);
        $updatedConfig['DistributionConfig']['Origins']['Quantity']++;
        $updatedConfig['DistributionConfig']['Origins']['Items'][] = array(
            'Id' => 'S3',
            'DomainName' => $bucketLocation,
        );
        foreach (
            array(
                'feed/*',
                'media/*',
                'images/*',
                'tmp/public/*',
                'robots.txt',
                'sitemap_*.xml',
                'themes/*',
            ) as $pattern
        ) {
            $updatedConfig['DistributionConfig']['CacheBehaviors']['Quantity']++;
            $updatedConfig['DistributionConfig']['CacheBehaviors']['Items'][] = array(
                'MinTTL' => 0,
                'TargetOriginId' => $bucketLocation,
                'PathPattern' => $pattern,
                'TrustedSigners' => array(
                    'Enabled' => false,
                    'Quantity' => 0,
                ),
                'ForwardedValues' => array(
                    'QueryString' => false,
                    'Cookies' => array(
                        'Forward' => 'none',
                    ),
                ),
                'ViewerProtocolPolicy' => 'allow-all',
            );
        }
        $result = $this->getCloudFrontClient()->updateDistribution(
            $updatedConfig
        );
        if (!is_array($result) || !isset($result['Distribution'])) {
            \DBG::dump($result);
            throw new AwsControllerException('AWS responded with invalid result');
        }
        $this->cloudFrontCache[$websiteName] = $result;
        /*return array(
            IAM access key name
            IAM access key value
        )*/
        return $this->getUserStorageController()->createUserStorage(
            $websiteName,
            $codeBase
        );
    }

    /**
     * {@inheritdoc}
     */
    public function deleteUserStorage($websiteName) {
        // drop S3 bucket
        $this->getUserStorageController()->deleteUserStorage(
            $websiteName
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createEndUserAccount($userName, $password, $homePath, $subscriptionId) {
        // nothing to do
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function removeEndUserAccount($userName) {
        // nothing to do
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function changeEndUserAccountPassword($userName, $password) {
        // nothing to do
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllEndUserAccounts($extendedData = false) {
        // there ARE no EndUserAccounts
        return array();
    }

    /**
     * Returns the controller to route user storage calls to
     * @return HostController Host controller for user storage calls
     */
    protected function getUserStorageController() {
        if (!$this->userStorageController) {
            $this->userStorageController = XamppController::fromConfig();
        }
        return $this->userStorageController;
    }

    /**
     * Get S3 client object
     *
     * @return \Aws\S3\S3Client
     */
    protected function getS3Client() {
        return $this->getAwsClient('S3', $this->region, 'latest');
    }

    /*******************************************************/
    /* W E B D I S T R I B U T I O N - C O N T R O L L E R */
    /*******************************************************/

    /**
     * {@inheritdoc}
     */
    public function createCustomer(\Cx\Core_Modules\MultiSite\Model\Entity\Customer $customer) {
    }

    /**
     * {@inheritdoc}
     */
    public function createWebDistribution($domain, $documentRoot = 'httpdocs') {
        $webDistributionConfig = array(
            'DistributionConfig' => array(
                'Aliases' => array('Quantity' => 0),
                'CacheBehaviors' => array('Quantity' => 0),
                'Comment' => $domain,
                'Enabled' => true,
                'CallerReference' => $domain,
                'DefaultCacheBehavior' => array(
                    'MinTTL' => 0,
                    'TargetOriginId' => $domain,
                    'TrustedSigners' => array(
                        'Enabled' => false,
                        'Quantity' => 0,
                    ),
                    'ForwardedValues' => array(
                        'QueryString' => true,
                        'Cookies' => array(
                            'Forward' => 'all',
                        ),
                    ),
                    'ViewerProtocolPolicy' => 'allow-all',
                ),
                'DefaultRootObject' => '/',
                'HttpVersion' => 'http2',
                'Origins' => array(
                    'Quantity' => 1,
                    'Items' => array(
                        array(
                            'Id' => $domain,
                            'DomainName' => $domain,
                            'CustomOriginConfig' => array(
                                'HTTPPort' => 80,
                                'HTTPSPort' => 443,
                                'OriginProtocolPolicy' => 'match-viewer',
                                'OriginReadTimeout' => 60,
                            ),
                        ),
                    ),
                ),
                'PriceClass' => 'PriceClass_100',
            )
        );
        $result = json_decode(
            $this->getCloudFrontClient()->createDistribution(
                $webDistributionConfig
            )
        );
        if (
            !$result ||
            empty($result['Distribution']) ||
            empty($result['Distribution']['DomainName'])
        ) {
            \DBG::dump($result);
            throw new AwsControllerException('AWS responded with invalid result');
        }
        $this->cloudFrontCache[$domain] = $result;
        $cfDomain = $result['Distribution']['DomainName'];
        \DBG::msg('CloudFront Domain is "' . $cfDomain . '"');
    }

    /**
     * {@inheritdoc}
     */
    public function renameWebDistribution($oldDomainName, $newDomainName) {
    }

    /**
     * {@inheritdoc}
     */
    public function deleteWebDistribution($domain) {
    }

    /**
     * {@inheritdoc}
     */
    public function getAllWebDistributions() {
    }

    /**
     * {@inheritdoc}
     */
    public function createWebDistributionAlias($mainName, $aliasName) {
    }

    /**
     * {@inheritdoc}
     */
    public function renameWebDistributionAlias($mainName, $oldAliasName, $newAliasName) {
    }

    /**
     * {@inheritdoc}
     */
    public function deleteWebDistributionAlias($mainName, $aliasName) {
    }

    /**
     * {@inheritdoc}
     */
    public function getAllWebDistributionAliases($websiteName = '') {
    }

    /**
     * Get CloudFront client object
     *
     * @return \Aws\CloudFront\CloudFrontClient
     */
    protected function getCloudFrontClient() {
        return $this->getAwsClient('CloudFront', 'us-east-1', 'latest');
    }

    /**
     * Gets the config for a specific web distribution
     * @param string $websiteName Name of website
     * @return array Distribution config
     */
    protected function getWebDistributionConfig($websiteName) {
        if (!isset($this->cloudFrontCache[$websiteName])) {
            $distributions = $this->getAllWebDistributions();
        }
        return $this->cloudFrontCache[$websiteName];
    }

    /*******************************/
    /* S S L - C O N T R O L L E R */
    /*******************************/

    /**
     * {@inheritdoc}
     */
    public function canGenerateCertificates() {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function installSSLCertificate($websiteName, $name, $domain, $certificatePrivateKey, $certificateBody = null, $certificateAuthority = null) {
    }

    /**
     * {@inheritdoc}
     */
    public function getSSLCertificates($websiteName, $domain = '') {
    }

    /**
     * {@inheritdoc}
     */
    public function removeSSLCertificates($websiteName, $domain, $names = array()) {
    }

    /**
     * {@inheritdoc}
     */
    public function activateSSLCertificate($websiteName, $certificateName, $domain) {
    }

    /*****************************/
    /* D B - C O N T R O L L E R */
    /*****************************/

    /**
     * {@inheritdoc}
     */
    public function createDbUser(\Cx\Core\Model\Model\Entity\DbUser $user) {
        $this->getDbController()->createDbUser($user);
    }

    /**
     * {@inheritdoc}
     */
    public function createDb(\Cx\Core\Model\Model\Entity\Db $db, \Cx\Core\Model\Model\Entity\DbUser $user = null) {
        $this->getDbController()->createDb($db, $user);
    }

    /**
     * {@inheritdoc}
     */
    public function grantRightsToDb(\Cx\Core\Model\Model\Entity\DbUser $user, \Cx\Core\Model\Model\Entity\Db $database) {
        $this->getDbController()->grantRightsToDb($user, $database);
    }

    /**
     * {@inheritdoc}
     */
    public function revokeRightsToDb(\Cx\Core\Model\Model\Entity\DbUser $user, \Cx\Core\Model\Model\Entity\Db $database) {
        $this->getDbController()->revokeRightsToDb($user, $database);
    }

    /**
     * {@inheritdoc}
     */
    public function removeDbUser(\Cx\Core\Model\Model\Entity\DbUser $dbUser, \Cx\Core\Model\Model\Entity\Db $db ) {
        $this->getDbController()->removeDbUser($dbUser, $db);
    }

    /**
     * {@inheritdoc}
     */
    public function removeDb(\Cx\Core\Model\Model\Entity\Db $db) {
        $this->getDbController()->removeDb($db);
    }

    /**
     * Returns the controller to route all database calls to
     * @return HostController Host controller for database calls
     */
    protected function getDbController() {
        if (!$this->dbController) {
            $this->dbController = XamppController::fromConfig();
        }
        return $this->dbController;
    }

    /*********************************/
    /* M A I L - C O N T R O L L E R */
    /*********************************/

    /**
     * {@inheritdoc}
     */
    public function enableMailService($subscriptionId) {
        throw new MailControllerException('Method "' . __METHOD__ . '" not yet implemented in "' . __CLASS__ . '"');
    }

    /**
     * {@inheritdoc}
     */
    public function disableMailService($subscriptionId) {
        throw new MailControllerException('Method "' . __METHOD__ . '" not yet implemented in "' . __CLASS__ . '"');
    }

    /**
     * {@inheritdoc}
     */
    public function createMailDistribution($domain, $ipAddress, $subscriptionStatus = 0, $customerId = null, $planId = null) {
        throw new MailControllerException('Method "' . __METHOD__ . '" not yet implemented in "' . __CLASS__ . '"');
    }

    /**
     * {@inheritdoc}
     */
    public function removeMailDistribution($subscriptionId) {
        throw new MailControllerException('Method "' . __METHOD__ . '" not yet implemented in "' . __CLASS__ . '"');
    }

    /**
     * {@inheritdoc}
     */
    public function renameMailDistribution($domain) {
        throw new MailControllerException('Method "' . __METHOD__ . '" not yet implemented in "' . __CLASS__ . '"');
    }

    /**
     * {@inheritdoc}
     */
    public function changeMailDistributionPlan($subscriptionId, $planGuid) {
        throw new MailControllerException('Method "' . __METHOD__ . '" not yet implemented in "' . __CLASS__ . '"');
    }

    /**
     * {@inheritdoc}
     */
    public function createMailAccount($name, $password, $role, $accountId = null) {
        throw new MailControllerException('Method "' . __METHOD__ . '" not yet implemented in "' . __CLASS__ . '"');
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMailAccount($userAccountId) {
        throw new MailControllerException('Method "' . __METHOD__ . '" not yet implemented in "' . __CLASS__ . '"');
    }

    /**
     * {@inheritdoc}
     */
    public function changeMailAccountPassword($userAccountId, $password) {
        throw new MailControllerException('Method "' . __METHOD__ . '" not yet implemented in "' . __CLASS__ . '"');
    }

    /**
     * {@inheritdoc}
     */
    public function createDomainAlias($aliasName) {
        throw new MailControllerException('Method "' . __METHOD__ . '" not yet implemented in "' . __CLASS__ . '"');
    }

    /**
     * {@inheritdoc}
     */
    public function renameDomainAlias($oldAliasName, $newAliasName) {
        throw new MailControllerException('Method "' . __METHOD__ . '" not yet implemented in "' . __CLASS__ . '"');
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDomainAlias($aliasName) {
        throw new MailControllerException('Method "' . __METHOD__ . '" not yet implemented in "' . __CLASS__ . '"');
    }

    /**
     * {@inheritdoc}
     */
    public function getPanelAutoLoginUrl($subscriptionId, $ipAddress, $sourceAddress, $role) {
        throw new MailControllerException('Method "' . __METHOD__ . '" not yet implemented in "' . __CLASS__ . '"');
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableMailDistributionPlans() {
        throw new MailControllerException('Method "' . __METHOD__ . '" not yet implemented in "' . __CLASS__ . '"');
    }

    /**
     * Returns an instance of an AWS service client
     * @param string $service Name of the AWS service
     * @param string $region Region to connect to
     * @param string $version AWS API version to use
     * @return object AWS client
     */
    protected function getAwsClient($service, $region, $version) {
        if (!isset($this->awsClients[$service])) {
            $serviceClass = '\\Aws\\' . $service . '\\' . $service . 'Client';
            $this->awsClients[$service] = new $serviceClass(
                array(
                    'region' => $region,
                    'version' => $version,
                    'credentials' => array(
                        'key' => $this->credentialsKey,
                        'secret' => $this->credentialsSecret,
                    )
                )
            );
        }
        return $this->awsClients[$service];
    }
}
