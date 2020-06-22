<?php
/**
 * Class PleskDbController
 *
 * This is the PleskDb Controller class.
 *
 * @copyright   CONTREXX CMS - Comvation AG Thun
 * @author      Sudhir Parmar 
 * @package     contrexx
 * @subpackage  coremodule_MultiSite
 * @version     1.0.0
 */

namespace Cx\Core_Modules\MultiSite\Controller;
/**

 * Reports error during API RPC request

 */
class ApiRequestException extends DbControllerException {}
/**
 * Class PleskDbController
 *
 * Controller clask to call plesk api for database creation,
 * database user creation etc.
 *
 * @copyright   CONTREXX CMS - Comvation AG Thun
 * @author      Sudhir Parmar
 * @package     contrexx
 * @subpackage  coremodule_MultiSite
 * @version     1.0.0
 */
class PleskController extends HostController {
    
    /**
     * hostname for the plesk panel 
     */
    protected $host;
    
    /**
     * login username for the plesk panel 
     */
    protected $login;
    
    /**
     * login password for the plesk panel 
     */
    protected $password;
    
    protected $webspaceId;
    
    /** 
     * Password for the newly created database user
     */
    protected $dbPassword;   
    
    /**
     * Version of the plesk api rpc
     */
    protected $apiVersion;

    /**
     * {@inheritdoc}
     */
    public static function initSettings() {
        $settings = array(
            'pleskHost' => array(
                'value' => 'localhost',
            ),
            'pleskLogin' => array(
                'value' => '',
            ),
            'pleskPassword' => array(
                'value' => '',
                'type' => \Cx\Core\Setting\Controller\Setting::TYPE_PASSWORD,
            ),
            'pleskApiVersion' => array(
                'value' => '',
            ),
            'pleskWebsitesSubscriptionId' => array(
                'value' => 0,
            ),
            'pleskMasterSubscriptionId' => array(
                'value' => 0,
            ),
        );
        $i = 0;
        \Cx\Core\Setting\Controller\Setting::init('MultiSite', 'plesk', 'FileSystem');
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
            \Cx\Core\Setting\Controller\Setting::getValue('pleskHost','MultiSite'),
            \Cx\Core\Setting\Controller\Setting::getValue('pleskLogin','MultiSite'),
            \Cx\Core\Setting\Controller\Setting::getValue('pleskPassword','MultiSite'),
            \Cx\Core\Setting\Controller\Setting::getValue('pleskWebsitesSubscriptionId','MultiSite')
        );
    }
    
    /**
     * Constructor
     */
    public function __construct($host, $login, $password, $webspaceId, $apiVersion = null){
        $this->host       = $host;
        $this->login      = $login;
        $this->password   = $password;
        $this->webspaceId = $webspaceId;
        $this->apiVersion = !\FWValidator::isEmpty($apiVersion) ? $apiVersion : ''; 
    }

    /**
     * function to set webspace id
     * @param $webspaceId webspace id to set
     */
    public function setWebspaceId($webspaceId){
        $this->webspaceId = $webspaceId;    
    }
    
    /**
     * function to get webspace id
     * @return webspace id
     */
    public function getWebspaceId(){
        return $this->webspaceId;
    }

    /**
     * Creates a DB user
     * @param string $name Name for the new user
     */
    public function createDbUser(\Cx\Core\Model\Model\Entity\DbUser $user){
        return $user;
    }

    /**
     * Creates a DB
     * @param string $name Name for the new database
     * @param \Cx\Core\Model\Model\Entity\DbUser $user (optional) Database user to grant rights for this DB, if null is given a new User is created
     * @return \Cx\Core\Model\Model\Entity\Db Abstract representation of the created database
     */
    public function createDb(\Cx\Core\Model\Model\Entity\Db $db, \Cx\Core\Model\Model\Entity\DbUser $user = null){
        $web_id = $this->getWebspaceId();
        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);
        $database = $xmldoc->createElement('database');  
        $packet->appendChild($database);            
        $add_db = $xmldoc->createElement('add-db');	
        $database->appendChild($add_db);
        $webspace_id = $xmldoc->createElement('webspace-id',$web_id);	
        $add_db->appendChild($webspace_id);
        $nameTag = $xmldoc->createElement('name',$db->getName());	
        $type = $xmldoc->createElement('type','mysql'); //type of the database ie postgray, mssql or mysql
        $add_db->appendChild($nameTag);
        $add_db->appendChild($type);
        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->database->{'add-db'}->result;
        $systemError = $response->system->errtext;
        if ('error' == (string)$resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError)?$systemError:$resultNode->errtext);
            throw new ApiRequestException("Error in creating database:{$error} ");
        } else {
            $dbId = (string)$resultNode->id;           
            $db->setId($dbId);
            if ($user !== null) {
                $this->grantRightsToDb($user, $db);// create a db user if $user is not null   
            }
        }
        return $db;
    }

    /**
     * Removes a db user
     * @param \Cx\Core\Model\Model\Entity\DbUser $dbUser User to remove
     * @throws MultiSiteDbException On error
     */
    public function removeDbUser(\Cx\Core\Model\Model\Entity\DbUser $dbUser, \Cx\Core\Model\Model\Entity\Db $db ){
        \DBG::msg("MultiSite (PleskController): Removing Database User.");
       
        $dbUserName = $dbUser->getName();
        $dbId = $this->getDbId($db->getName());
        //if database does not exist return false
        if(empty($dbId)){
            return false;
        }
        $dbUserId   = $this->getDbUserId($dbUserName, $dbId);
        if (!empty($dbUserId)) {
            $xmldoc = $this->getXmlDocument();
            $packet = $this->getRpcPacket($xmldoc);

            $database = $xmldoc->createElement('database');
            $packet->appendChild($database);

            $getDbUsers = $xmldoc->createElement('del-db-user');
            $database->appendChild($getDbUsers);

            $filter = $xmldoc->createElement('filter');
            $getDbUsers->appendChild($filter);

            $id = $xmldoc->createElement('id', $dbUserId);
            $filter->appendChild($id);

            $response = $this->executeCurl($xmldoc);
            $resultNode = $response->{'database'}->{'del-db-user'}->result;
            $responseJson = json_encode($resultNode);
            $respArr = json_decode($responseJson, true);
            $systemError = $response->system->errtext;

            if ('error' == (string) $resultNode->status || $systemError) {
                \DBG::dump($xmldoc->saveXML());
                \DBG::dump($response);
                $error = (isset($systemError) ? $systemError : $resultNode->errtext);
                throw new ApiRequestException("Error in removing database user:{$error} ");
            }
            return $respArr;
        }
        return true;
    }
    
    /**
     * Removes a db
     * @param \Cx\Core\Model\Model\Entity\Db $db Database to remove
     * @throws MultiSiteDbException On error
     */
    public function removeDb(\Cx\Core\Model\Model\Entity\Db $db){
        \DBG::msg("MultiSite (PleskController): Removing Database.");
        
        $dbName = $db->getName();
        $databaseId = $this->getDbId($dbName); //get database id byname
        if ($databaseId) {
            $xmldoc = $this->getXmlDocument();
            $packet = $this->getRpcPacket($xmldoc);
            $database = $xmldoc->createElement('database');
            $packet->appendChild($database);
            $delDb = $xmldoc->createElement('del-db');
            $database->appendChild($delDb);
            $filter = $xmldoc->createElement('filter');
            $delDb->appendChild($filter);
            $dbId = $xmldoc->createElement('id', $databaseId);
            $filter->appendChild($dbId);
            $response = $this->executeCurl($xmldoc);
            $systemError = $response->system->errtext;
            $resultNode = $response->database->{'del-db'}->result;
            $responseJson = json_encode($resultNode);
            $respArr = json_decode($responseJson, true);
            if ('error' == (string) $resultNode->status || $systemError) {
                \DBG::dump($xmldoc->saveXML());
                \DBG::dump($response);
                $error = (isset($systemError) ? $systemError : $resultNode->errtext);
                throw new ApiRequestException("Error in removing database:{$error} ");
            }
            return $respArr;
        }
        
        return false;
    }

    /**
     * get id of a particular db by its name
     * @param $name name of the db we need id
     * @return id of the db
     */
    protected function getDbId($name){
        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);
        $database = $xmldoc->createElement('database');  
        $packet->appendChild($database);            
        $getDb = $xmldoc->createElement('get-db');	
        $database->appendChild($getDb);
        $filter = $xmldoc->createElement('filter');
        $getDb->appendChild($filter);
        $domainId = $this->webspaceId;
        $domainIdTag = $xmldoc->createElement('webspace-id',$domainId);
        $filter->appendChild($domainIdTag);
        //echo $xmldoc->saveXML();die;
        $response = $this->executeCurl($xmldoc);
        $responseJson = json_encode($response->database->{'get-db'});
        $respArr = json_decode($responseJson,true); 
        $systemError = $response->system->errtext;
        $resultNode = $response->database->{'get-db'}->result;
        if ('error' == (string)$resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError)?$systemError:$resultNode->errtext);
            throw new ApiRequestException("Error in getting database ID : {$error} ");
        }
        if (!empty($respArr)) {
            $responseArr = $this->getFormattedResponse($respArr);
            foreach($responseArr as $res) {
                if ($res['name'] == $name) {
                    return $res['id'];
                }
            }
        }
    }
    
    /**
     * get id of a particular db user by its name
     * 
     * @param type $name name of the db user we need id
     * 
     * @return integer id of the db user
     * @throws ApiRequestException
     */
    public function getDbUserId($name, $dbId ) {
        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);       
        
        $database = $xmldoc->createElement('database');
        $packet->appendChild($database);
        
        $getDbUsers = $xmldoc->createElement('get-db-users');
        $database->appendChild($getDbUsers);
        
        $filter = $xmldoc->createElement('filter');
        $getDbUsers->appendChild($filter);
        
        $domainIdTag = $xmldoc->createElement('db-id', $dbId);
        $filter->appendChild($domainIdTag);
        
        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->database->{'get-db-users'}->result;
        $responseJson = json_encode($response->database->{'get-db-users'});
        $respArr = json_decode($responseJson, true); 
        $systemError = $response->system->errtext;
        
        if ('error' == (string) $resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError) ? $systemError : $resultNode->errtext);
            throw new ApiRequestException("Error in getting database User ID : {$error} ");
        }      
        
        $dbUserId    = 0;
        if (!empty($respArr)) {
            $responseArr = $this->getFormattedResponse($respArr);            
            foreach($responseArr as $result) {
                if (isset($result['login']) && $result['login'] == $name) {
                    $dbUserId = $result['id'];
                    break;
                }
            }
        }
        \DBG::dump($dbUserId);
        return $dbUserId;
    }
    
     /**
      * Grants user $user usage rights on database $database
      * @param \Cx\Core\Model\Model\Entity\DbUser $user Database user to grant rights for
      * @param \Cx\Core\Model\Model\Entity\Db $db Database to work on
      * @throws MultiSiteDbException On error
      */
    public function grantRightsToDb(\Cx\Core\Model\Model\Entity\DbUser $user, \Cx\Core\Model\Model\Entity\Db $database){
        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);
        $databaseTag = $xmldoc->createElement('database');  
        $packet->appendChild($databaseTag);            
        $addDbUser = $xmldoc->createElement('add-db-user');
        $databaseTag->appendChild($addDbUser);
        $databaseId = $database->getId();
        $dbId = $xmldoc->createElement('db-id',$databaseId);
        $addDbUser->appendChild($dbId);
        $dbUserName = $user->getName();
        $login = $xmldoc->createElement('login',$dbUserName);
        $addDbUser->appendChild($login);
        $dbpassword = $user->getPassword();
        $password = $xmldoc->createElement('password',htmlentities($dbpassword));
        $addDbUser->appendChild($password);
        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->database->{'add-db-user'}->result;
        $systemError = $response->system->errtext;
        if ('error' == (string)$resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError)?$systemError:$resultNode->errtext);
            throw new ApiRequestException("Error in granting database rights to user:{$error} ");
        }
        return $resultNode;    
    }

    /**
     * Revokes user $user all rights on database $database
     * @param \Cx\Core\Model\Model\Entity\DbUser $user Database user to revoke rights of
     * @param \Cx\Core\Model\Model\Entity\Db $db Database to work on
     * @throws MultiSiteDbException On error
     */
    public function revokeRightsToDb(\Cx\Core\Model\Model\Entity\DbUser $user, \Cx\Core\Model\Model\Entity\Db $database){
        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);
        $databaseTag = $xmldoc->createElement('database');  
        $packet->appendChild($databaseTag);            
        $delDbUser = $xmldoc->createElement('del-db-user');	
        $databaseTag->appendChild($delDbUser);
        $filter = $xmldoc->createElement('filter');	
        $delDbUser->appendChild($filter);
        $dbUserId = $user->getId(); 
        $id = $xmldoc->createElement('id',$dbUserId);	
        $filter->appendChild($id);	
        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->database->{'del-db-user'}->result;
        $errcode = $resultNode->errcode;
        $systemError = $response->system->errtext;
        if ('error' == (string)$resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError)?$systemError:$resultNode->errtext);
            throw new ApiRequestException("Error in revoking database rights from user:{$error} ");
        }
        return $response;	
    }
    
    /**
     * Set database password for new user
     * @param $dbPass password to set for db user
     */
    public function setDbPassword($dbPass){
        $this->dbPassword = $dbPas;
    }

    /**
     * get database password
     * @return dbPassword
     */
    public function getDbPassword(){
        return $this->dbPassword;
    }
    
    /**
     * Prepares CURL to perform the Panel API request
     * @return resource
     */
    protected function curlInit($host, $login, $password)
    {
          $curl = curl_init();

          curl_setopt($curl, CURLOPT_URL, "https://{$host}:8443/enterprise/control/agent.php");
          curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($curl, CURLOPT_POST,           true);
          curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
          curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
          curl_setopt($curl, CURLOPT_HTTPHEADER,

                 array("HTTP_AUTH_LOGIN: {$login}",
                        "HTTP_AUTH_PASSWD: {$password}",
                        "HTTP_PRETTY_PRINT: TRUE",
                        "Content-Type: text/xml")
          );
          return $curl;
    }

    /**
     * Performs a Panel API request, returns raw API response text
     *
     * @return string
     * @throws ApiRequestException
     */
    protected function sendRequest($curl, $packet)
    {
          curl_setopt($curl, CURLOPT_POSTFIELDS, $packet);

          $result = curl_exec($curl);

          if (curl_errno($curl)) {

                 $errmsg  = curl_error($curl);

                 $errcode = curl_errno($curl);

                 curl_close($curl);

                 throw new ApiRequestException($errmsg, $errcode);
          }
          curl_close($curl);
          return $result;
    }

    /**
     * Looks if API responded with correct data
     *
     * @return SimpleXMLElement
     * @throws ApiRequestException
     */
    protected function parseResponse($responseString)
    {
        $xml = new \SimpleXMLElement($responseString);
        if (!is_a($xml, 'SimpleXMLElement')) {
            throw new ApiRequestException("Cannot parse server response: {$response_string}");
        }
        return $xml;
    }

    /**
     * Send request to the plesk api
     * @param $requestXML request packet xml
     * @return SimpleXMLElement
     * @throws ApiRequestException
     */
    protected function executeCurl($requestXML){
        $curl = $this->curlInit($this->host, $this->login, $this->password);
        try {
              $response = $this->sendRequest($curl, $requestXML->saveXML());
              $responseXml = $this->parseResponse($response);
              return $responseXml;
        } catch (ApiRequestException $e) {
              return $e;
              die();
        }	
    }
    
    /**
     * Create a Customer
     * @param \Cx\Core_Modules\MultiSite\Model\Entity\Customer $customer
     * @throws $response array
     */
    public function createCustomer(\Cx\Core_Modules\MultiSite\Model\Entity\Customer $customer){
        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);
        $customerTag = $xmldoc->createElement('customer');
        $packet->appendChild($customerTag);
        $addTag = $xmldoc->createElement('add');
        $customerTag->appendChild($addTag);        
        $genInfo = $xmldoc->createElement('gen_info');
        $addTag->appendChild($genInfo);
        $customerArr = $customer->getCustomerInfo();//need to pass preformatted array on calling
        foreach($customerArr as $key=>$val){
            $customerInfo = $xmldoc->createElement($key,htmlentities($val));
            $genInfo->appendChild($customerInfo);
        }
        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->customer->{'add'}->result;
        $errcode = $resultNode->errcode;
        $systemError = $response->system->errtext;
        if ('error' == (string)$resultNode->status || $systemError){
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError)?$systemError:$resultNode->errtext);
            throw new ApiRequestException("Error in creating Customer: {$error}");
        }
        return $resultNode;	
    }
    
    /**
     * Create a subscription
     * 
     * @param string $domain             domain name
     * @param int    $subscriptionStatus status
     * @param int    $customerId         customer
     * @param int    $planId             plan
     * 
     * @return subscription id
     */
    public function createMailDistribution($domain, $ipAddress, $subscriptionStatus = 0, $customerId = null, $planId = null) 
    {
        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);       
        $webspace = $xmldoc->createElement('webspace');
        $packet->appendChild($webspace);
        $addTag = $xmldoc->createElement('add');
        $webspace->appendChild($addTag);

        /*--gen_setup data Start--*/
        $genSetup = $xmldoc->createElement('gen_setup');
        $addTag->appendChild($genSetup);
        $subscriptionName = $xmldoc->createElement('name', $domain);
        $genSetup->appendChild($subscriptionName);
        $ip = $xmldoc->createElement('ip_address', $ipAddress);
        $genSetup->appendChild($ip);
        $status = $xmldoc->createElement('status', $subscriptionStatus);      
        $genSetup->appendChild($status);
        if ($customerId) {
            $ownerId = $xmldoc->createElement('owner-id', $customerId);
            $genSetup->appendChild($ownerId);
        }
        /*--gen_setup data End--*/
                
        if ($planId) {
            $planId = $xmldoc->createElement('plan-guid', $planId);
            $addTag->appendChild($planId);
        }

        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->webspace->{'add'}->result;
        $systemError = $response->system->errtext;
        if ('error' == (string)$resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError)?$systemError:$resultNode->errtext);
            throw new ApiRequestException("Error in creating Subscription: {$error}");
        }
        return $resultNode->id;	
    }
        
    /**
     * Rename a subscription
     * 
     * @param string $domain domain name
     * 
     * @return subscription id
     */
    public function renameMailDistribution($domain) 
    {
        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);       
        $webspace = $xmldoc->createElement('webspace');
        $packet->appendChild($webspace);
        $setTag = $xmldoc->createElement('set');
        $webspace->appendChild($setTag);

        $filter = $xmldoc->createElement('filter');
        $setTag->appendChild($filter);
        
        $id = $xmldoc->createElement('id', $this->webspaceId);
        $filter->appendChild($id);

        $values = $xmldoc->createElement('values');
        $setTag->appendChild($values);
        
        /*--gen_setup data Start--*/
        $genSetup = $xmldoc->createElement('gen_setup');
        $values->appendChild($genSetup);
        $subscriptionName = $xmldoc->createElement('name', $domain);
        $genSetup->appendChild($subscriptionName);
        /*--gen_setup data End--*/
                
        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->webspace->{'set'}->result;
        $systemError = $response->system->errtext;
        if ('error' == (string)$resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError)?$systemError:$resultNode->errtext);
            throw new ApiRequestException("Error in rename Subscription: {$error}");
        }
        return $resultNode->id;	
    }
    
    /**
     * Remove a subscription
     * 
     * @param int $subscriptionId subcription id
     * 
     * @throws ApiRequestException on error
     * 
     * @return  id 
     */
    function removeMailDistribution($subscriptionId) 
    {
        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);
        $webspace = $xmldoc->createElement('webspace');
        $packet->appendChild($webspace);
        $delTag = $xmldoc->createElement('del');
        $webspace->appendChild($delTag);
        $filter = $xmldoc->createElement('filter');
        $delTag->appendChild($filter);
        
        $id = $xmldoc->createElement('id', $subscriptionId);
        $filter->appendChild($id);
        
        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->webspace->{'del'}->result;
        $systemError = $response->system->errtext;
        if ('error' == (string)$resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError)?$systemError:$resultNode->errtext);
            throw new ApiRequestException("Error in deleting Subscription: {$error}");
        }
        return $resultNode->id;    
    }
    
    /**
     * Get a subscription owner GUID 
     * 
     * @return string subscription owner GUID
     * @throws ApiRequestException on error
     * 
     */
    protected function getSubscriptionOwnerGuid()
    {
        \DBG::msg("MultiSite (PleskController): get subscription owner GUID.");
        if (\FWValidator::isEmpty($this->webspaceId)) {
            return false;
        }

        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);

        $webspace = $xmldoc->createElement('webspace');
        $packet->appendChild($webspace);

        $get = $xmldoc->createElement('get');
        $webspace->appendChild($get);

        $filter = $xmldoc->createElement('filter');
        $get->appendChild($filter);

        $id = $xmldoc->createElement('id', $this->webspaceId);
        $filter->appendChild($id);

        $dataSet = $xmldoc->createElement('dataset');
        $get->appendChild($dataSet);

        $genInfo = $xmldoc->createElement('gen_info');
        $dataSet->appendChild($genInfo);

        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->webspace->{'get'}->result;
        $systemError = $response->system->errtext;
        if ('error' == (string) $resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError) ? $systemError : $resultNode->errtext);
            throw new ApiRequestException("Error in get Subscription GUID: {$error}");
        }
        return $resultNode->data->{'gen_info'}->{'vendor-guid'};
    }

    /**
     * Get an auxilary user login name
     * 
     * @param string $ownerGuid
     * 
     * @return string auxilary user login name
     * @throws ApiRequestException on error
     */
    public function getAuxilaryUserLoginName($ownerGuid, $role)
    {
        \DBG::msg("MultiSite (PleskController): get auxilary user login name: $ownerGuid");
        if (\FWValidator::isEmpty($this->webspaceId) || \FWValidator::isEmpty($ownerGuid)) {
            return false;
        }

        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);

        $user = $xmldoc->createElement('user');
        $packet->appendChild($user);

        $get = $xmldoc->createElement('get');
        $user->appendChild($get);

        $filter = $xmldoc->createElement('filter');
        $get->appendChild($filter);

        $ownerGuidTag = $xmldoc->createElement('owner-guid', $ownerGuid);
        $filter->appendChild($ownerGuidTag);

        $dataSet = $xmldoc->createElement('dataset');
        $get->appendChild($dataSet);

        $genInfo = $xmldoc->createElement('gen-info');
        $dataSet->appendChild($genInfo);

        $roles = $xmldoc->createElement('roles');
        $dataSet->appendChild($roles);

        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->user->{'get'}->result;

        $systemError = $response->system->errtext;
        if ('error' == (string) $resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError) ? $systemError : $resultNode->errtext);
            throw new ApiRequestException("Error in get Auxilary user name : {$error}");
        }

        $responseJson = json_encode($response->user->{'get'});
        $responseArr = json_decode($responseJson, true);
        foreach ($responseArr['result'] as $value) {
            $getInfo = $value['data']['gen-info'];
            if (!isset($value['data']['roles']['name'])) {
                continue;
            }
            if ($value['data']['roles']['name'] != $role) {
                continue;
            }
            if (isset($getInfo['subscription-domain-id']) && $getInfo['subscription-domain-id'] == $this->webspaceId) {
                return $getInfo['login'];
            }
        }
        return false;
    }
    
    /**
     * Get an auxilary user guid
     * 
     * @param string $ownerGuid
     * 
     * @return string auxilary user guid
     * @throws ApiRequestException on error
     */
    public function getAuxilaryUserGuid($ownerGuid)
    {
        \DBG::msg("MultiSite (PleskController): get auxilary user guid: $ownerGuid");
        if (\FWValidator::isEmpty($this->webspaceId) || \FWValidator::isEmpty($ownerGuid)) {
            return false;
        }

        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);

        $user = $xmldoc->createElement('user');
        $packet->appendChild($user);

        $get = $xmldoc->createElement('get');
        $user->appendChild($get);

        $filter = $xmldoc->createElement('filter');
        $get->appendChild($filter);

        $ownerGuidTag = $xmldoc->createElement('owner-guid', $ownerGuid);
        $filter->appendChild($ownerGuidTag);

        $dataSet = $xmldoc->createElement('dataset');
        $get->appendChild($dataSet);

        $genInfo = $xmldoc->createElement('gen-info');
        $dataSet->appendChild($genInfo);

        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->user->{'get'}->result;

        $systemError = $response->system->errtext;
        if ('error' == (string) $resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError) ? $systemError : $resultNode->errtext);
            throw new ApiRequestException("Error in get Auxilary user guid : {$error}");
        }

        $responseJson = json_encode($response->user->{'get'});
        $responseArr = json_decode($responseJson, true);
        foreach ($responseArr['result'] as $value) {
            $getInfo = $value['data']['gen-info'];
            if (isset($getInfo['subscription-domain-id']) && $getInfo['subscription-domain-id'] == $this->webspaceId) {
                return $getInfo['guid'];
            }
        }
        return false;
    }

    /**
     * Change plan of the subscription 
     * 
     * @param integer $subscriptionId subscription Id
     * @param string  $planGuid        planGuid
     * @return integer
     * @throws ApiRequestException
     */
    public function changeMailDistributionPlan($subscriptionId, $planGuid)
    {
        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);
        $webspace = $xmldoc->createElement('webspace');
        $packet->appendChild($webspace);
        $switchSubscription = $xmldoc->createElement('switch-subscription');
        $webspace->appendChild($switchSubscription);
        $filter = $xmldoc->createElement('filter');
        $switchSubscription->appendChild($filter);
        
        $id = $xmldoc->createElement('id', $subscriptionId);
        $filter->appendChild($id);
        
        $planGuidTag = $xmldoc->createElement('plan-guid', $planGuid);
        $switchSubscription->appendChild($planGuidTag);
        
        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->webspace->{'switch-subscription'}->result;
        $systemError = $response->system->errtext;
        if ('error' == (string)$resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError)?$systemError:$resultNode->errtext);
            throw new ApiRequestException("Error in change plan of Subscription: {$error}");
        }
        return $resultNode->id;	
    }
    
    /**
     * Create user account
     * 
     * @param string $name      name 
     * @param string $password  password
     * @param string $role      role of user
     * @param int    $accountId account id
     * 
     * @return integer id
     * @throws ApiRequestException
     */
    public function createMailAccount($name, $password, $role, $accountId = null)
    {
        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);
        $user = $xmldoc->createElement('user');
        $packet->appendChild($user);
        $addTag = $xmldoc->createElement('add');
        $user->appendChild($addTag);
        
        /*--gen-info data Start--*/
        $genInfo = $xmldoc->createElement('gen-info');
        $addTag->appendChild($genInfo);              
        $login = $xmldoc->createElement('login', $name);
        $genInfo->appendChild($login);

        $passwordValue = $xmldoc->createTextNode($password);
        $password = $xmldoc->createElement('passwd');
        $password->appendChild($passwordValue);
        $genInfo->appendChild($password);

        $name = $xmldoc->createElement('name', $name);
        $genInfo->appendChild($name);
        if ($accountId) {
            $accountId = $xmldoc->createElement('subscription-domain-id', $accountId);
            $genInfo->appendChild($accountId);
        }
        /*--gen-info data End--*/
        $roles = $xmldoc->createElement('roles');
        $addTag->appendChild($roles);
        $roleName = $xmldoc->createElement('name', $role);
        $roles->appendChild($roleName);
        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->user->{'add'}->result;
        $systemError = $response->system->errtext;
        if ('error' == (string)$resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError)?$systemError:$resultNode->errtext);
            throw new ApiRequestException("Error in creating user account: {$error}");
        }
        return $resultNode->guid;	
    }
    
    /**
     * Delete user account
     * 
     * @param int $userAccountId user id
     * 
     * @return integer id
     * @throws ApiRequestException
     */
    public function deleteMailAccount($userAccountId) 
    {

        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);
        $user = $xmldoc->createElement('user');
        $packet->appendChild($user);
        $delTag = $xmldoc->createElement('del');
        $user->appendChild($delTag);
        $filter = $xmldoc->createElement('filter');
        $delTag->appendChild($delTag);
        $guid = $xmldoc->createElement('guid', $userAccountId);
        $filter->appendChild($guid);
        
        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->user->{'del'}->result;
        $systemError = $response->system->errtext;
        if ('error' == (string)$resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError)?$systemError:$resultNode->errtext);
            throw new ApiRequestException("Error in deleting user account: {$error}");
        }
        return $resultNode->id;          
    }
    
    /**
     * Change the password from a user account
     * 
     * @param int $userAccountId user id
     * @param string $password
     * 
     * @return id 
     * @throws ApiRequestException
     */
    public function changeMailAccountPassword($userAccountId, $password)
    {
        $this->webspaceId = $userAccountId;
        $guid = null;
        $subscriptionOwnerGuid = $this->getSubscriptionOwnerGuid();
        $guid = !empty($subscriptionOwnerGuid) ? $this->getAuxilaryUserGuid($subscriptionOwnerGuid) : '';
        
        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);

        $user = $xmldoc->createElement('user');
        $packet->appendChild($user);

        $setTag = $xmldoc->createElement('set');
        $user->appendChild($setTag);

        $filterTag = $xmldoc->createElement('filter');
        $setTag->appendChild($filterTag);

        $guid = $xmldoc->createElement('guid', $guid);
        $filterTag->appendChild($guid);

        $valuesTag = $xmldoc->createElement('values');
        $setTag->appendChild($valuesTag);
        
        $genInfo = $xmldoc->createElement('gen-info');
        $valuesTag->appendChild($genInfo);

        $userPasswordValue = $xmldoc->createTextNode($password);
        $userPassword = $xmldoc->createElement('passwd');
        $userPassword->appendChild($userPasswordValue);
        $genInfo->appendChild($userPassword);
        
        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->{'user'}->{'set'}->result;
        $systemError = $response->system->errtext;
        if ('error' == (string)$resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError)?$systemError:$resultNode->errtext);
            throw new ApiRequestException("Error in updating user account: {$error}");
        }
         
        return $resultNode->id;
    }
    
    /**
     * Add DNS records
     * @param string    $type   DNS-Record type
     * @param string    $host   DNS-Record host
     * @param string    $value  DNS-Record value
     * @param string    $zone   Name of DNS-Zone
     * @param integer   $zoneId Id of plesk subscription to add the record to
     */
    public function addDnsRecord($type = 'A', $host, $value, $zone, $zoneId){
        \DBG::msg("MultiSite (PleskController): add DNS-record: $type (type) / $host (host) / $value (value) / $zone (zone) / $zoneId (zone-id)");
        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);
        $dns = $xmldoc->createElement('dns');
        $packet->appendChild($dns);
        $addRec = $xmldoc->createElement('add_rec');
        $dns->appendChild($addRec);
        
        $siteIdTag = $xmldoc->createElement('site-id', $zoneId);
        $addRec->appendChild($siteIdTag);
        
        $recordType = $xmldoc->createElement('type',$type);
        $addRec->appendChild($recordType);

        // In case the record is a subdomain of the DNS-zone, then
        // we'll have to strip the DNS-zone part from the record.
        // I.e.:
        //      DNS-zone ($zone):   example.com
        //      DNS-record ($host): foo.example.com
        //      strip $host to:     foo
        if (strrpos($host, $zone) === strlen(substr($host, 0, -strlen($zone)))) {
            $host = rtrim(substr($host, 0, -strlen($zone)), '.');
        }

        $host = rtrim($host, '.');
        $host = $xmldoc->createElement('host', $host);
        $addRec->appendChild($host);

        $value = $xmldoc->createElement('value', $value);
        $addRec->appendChild($value);
        
        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->dns->{'add_rec'}->result;
        $errcode = $resultNode->errcode;
        $systemError = $response->system->errtext;
        if ('error' == (string)$resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError)?$systemError:$resultNode->errtext);
            throw new ApiRequestException("Error in adding DNS Record: {$error}");
        }
        return intval($resultNode->id);
    }

    /**
     * Remove DNS records
     * @param string    $type       DNS-Record-type
     * @param string    $host       DNS-Record-host
     * @param integer   $recordId   DNS-Record-Id of the plesk subscription
     */
    public function removeDnsRecord($type, $host, $recordId) {
        \DBG::msg("MultiSite (PleskController): remove DNS-record: $type / $host / $recordId");
        if (empty($recordId)) {
            return false;
        }
        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);
        $dns = $xmldoc->createElement('dns');
        $packet->appendChild($dns);       
        $delRec = $xmldoc->createElement('del_rec');
        $dns->appendChild($delRec);

        $filter = $xmldoc->createElement('filter');
        $delRec->appendChild($filter);       

        $id = $xmldoc->createElement('id', $recordId);
        $filter->appendChild($id);

        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->dns->{'del_rec'}->result;
        $errcode = $resultNode->errcode;
        $systemError = $response->system->errtext;
        if ('error' == (string)$resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError)?$systemError:$resultNode->errtext);
            throw new ApiRequestException("Error in deleting DNS Record: {$error}");
        }
        return $response; 
    }

    public function updateDnsRecord($type, $host, $value, $zone, $zoneId, $recordId){
        \DBG::msg("MultiSite (PleskController): update DNS-record: $type (type) / $host (host) / $value (value) / $zone (zone) / $zoneId (zone-id) / $recordId (record-id)");

        if (!$recordId) {
            \DBG::msg("MultiSite (PleskController): None existant DNS-record -> going to add DNS-record");
            return $this->addDnsRecord($type, $host, $value, $zone, $zoneId);
        }

        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);
        $dns = $xmldoc->createElement('dns');
        $packet->appendChild($dns);

        $getRec = $xmldoc->createElement('get_rec');
        $dns->appendChild($getRec);

        $filter = $xmldoc->createElement('filter');
        $getRec->appendChild($filter);       
        
        //$siteIdTag = $xmldoc->createElement('site-id', $zoneId);
        //$filter->appendChild($siteIdTag);

        $id = $xmldoc->createElement('id', $recordId);
        $filter->appendChild($id);
        
        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->dns->{'get_rec'}->result;
        $errcode = $resultNode->errcode;
        $systemError = $response->system->errtext;
        if ('error' == (string)$resultNode->status || $systemError) {
            \DBG::dump($recordId);
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError)?$systemError:$resultNode->errtext);
            throw new ApiRequestException("Error in fetching DNS Record: {$error}");
        }

        $recordType = $resultNode->data->type;
        $recordHost = substr($resultNode->data->host, 0, -1);
        $recordValue = $resultNode->data->value;
        if ($recordType == 'CNAME') {
            $recordValue = substr($recordValue, 0, -1);
        }
        if (   $recordType != $type
            || $recordHost != $host
            || $recordValue != $value
        ) {
            \DBG::msg("MultiSite (PleskController): DNS-record has changed -> going to update DNS-record");
            $this->removeDnsRecord($type, $host, $recordId);
            return $this->addDnsRecord($type, $host, $value, $zone, $zoneId);
        }

        // record is up to date -> return existing record-ID
        return $recordId;
    }
    
    /**
     * Get dom document object with request packet
     * return object (DomDocument)
     */
    protected function getXmlDocument(){
        $xmldoc = new \DomDocument('1.0', 'UTF-8');
        $xmldoc->formatOutput = true;
        return $xmldoc;        
    }
    
    protected function getRpcPacket($xmldoc){
        $packet = $xmldoc->createElement('packet');
        if (!\FWValidator::isEmpty($this->apiVersion)) {
            $packet->setAttribute('version', $this->apiVersion);
        }
        $xmldoc->appendChild($packet);
        return $packet;
    }

    /**
     * {@inheritdoc}
     */
    public function createUserStorage($websiteName, $codeBase = '') {
        // website's data repository
        $codeBaseOfWebsite = \Env::get('cx')->getCodeBaseDocumentRootPath();
        if (!empty($codeBase)) {
            $codeBaseOfWebsite = \Cx\Core\Setting\Controller\Setting::getValue(
                'codeBaseRepository',
                'MultiSite'
            ) . '/' . $codeBase;
        }
        $codeBaseWebsiteSkeletonPath = $codeBaseOfWebsite . \Env::get('cx')->getCoreModuleFolderName() . '/MultiSite/Data/WebsiteSkeleton';

        if(
            !\Cx\Lib\FileSystem\FileSystem::copy_folder(
                $codeBaseWebsiteSkeletonPath,
                \Cx\Core\Setting\Controller\Setting::getValue(
                    'websitePath',
                    'MultiSite'
                ) . '/' . $websiteName
            )
        ) {
            throw new UserStorageControllerException('Unable to setup data folder');
        }
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteUserStorage($websiteName) {
        $storageLocation = \Cx\Core\Setting\Controller\Setting::getValue(
            'websitePath',
            'MultiSite'
        ) . '/' . $websiteName;
        if (!file_exists($storageLocation)) {
            return;
        }
        if (
            !\Cx\Lib\FileSystem\FileSystem::delete_folder(
                $storageLocation,
                true
            )
        ) {
            throw new UserStorageControllerException('Unable to delete the website data repository');
        }
    }
    
    /**
     * Create new FTP Account
     * 
     * @param string  $userName       FTP user name
     * @param string  $password       FTP password
     * @param string  $homePath       FTP accessible path
     * @param integer $subscriptionId webspace id
     * 
     * @return object
     * @throws ApiRequestException
     */
    public function createEndUserAccount($userName, $password, $homePath, $subscriptionId) {
        \DBG::msg("MultiSite (PleskController): Creating Ftp Account.");
        if (empty($userName) || empty($password) || empty($homePath) || empty($subscriptionId)) {
            return;
        }
        
        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);       

        $ftpUser = $xmldoc->createElement('ftp-user');
        $packet->appendChild($ftpUser);

        $addTag = $xmldoc->createElement('add');
        $ftpUser->appendChild($addTag);

        $ftpLogin = $xmldoc->createElement('name', $userName);
        $addTag->appendChild($ftpLogin);

        $ftpPasswordValue = $xmldoc->createTextNode($password);
        $ftpPassword = $xmldoc->createElement('password');
        $ftpPassword->appendChild($ftpPasswordValue);
        $addTag->appendChild($ftpPassword);

        $home = $xmldoc->createElement('home', $homePath);
        $addTag->appendChild($home);

        $permissions = $xmldoc->createElement('permissions');
        $addTag->appendChild($permissions);

        $permissionReadAccess = $xmldoc->createElement('read', true);
        $permissions->appendChild($permissionReadAccess);

        $permissionWriteAccess = $xmldoc->createElement('write', true);
        $permissions->appendChild($permissionWriteAccess);

        $webspaceId = $xmldoc->createElement('webspace-id', $subscriptionId);
        $addTag->appendChild($webspaceId);
        
        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->{'ftp-user'}->{'add'}->result;
        $systemError = $response->system->errtext;
        if ('error' == (string)$resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError) ? $systemError : $resultNode->errtext);
            throw new ApiRequestException("Error in creating Ftp Account: {$error}");
        }
        return $resultNode->id;	
    }
    
    /**
     * Delete the FTP Account
     * 
     * @param string $userName FTP user name
     * 
     * @return object
     * @throws ApiRequestException
     */
    public function removeEndUserAccount($userName) {
        \DBG::msg("MultiSite (PleskController): Deleting Ftp Account.");
        if (empty($userName)) {
            return;
        }
        
        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);       
        $ftpUser = $xmldoc->createElement('ftp-user');
        $packet->appendChild($ftpUser);
        $delTag = $xmldoc->createElement('del');
        $ftpUser->appendChild($delTag);
        $filterTag = $xmldoc->createElement('filter');
        $delTag->appendChild($filterTag);
        $ftpLogin = $xmldoc->createElement('name', $userName);
        $filterTag->appendChild($ftpLogin);
        
        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->{'ftp-user'}->{'del'}->result;
        $systemError = $response->system->errtext;
        if ('error' == (string)$resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError) ? $systemError : $resultNode->errtext);
            throw new ApiRequestException("Error in deleting Ftp Account: {$error}");
        }
        return $response;    
    }
    
    /**
     * Change the FTP Account password
     * 
     * @param string $userName FTP user name
     * @param string $password FTP password
     * 
     * @return object
     * @throws ApiRequestException
     */
    public function changeEndUserAccountPassword($userName, $password) {
        \DBG::msg("MultiSite (PleskController): Changing Ftp Account Password.");
        if (empty($userName) || empty($password)) {
            return;
        }
        
        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);       

        $ftpUser = $xmldoc->createElement('ftp-user');
        $packet->appendChild($ftpUser);

        $setTag = $xmldoc->createElement('set');
        $ftpUser->appendChild($setTag);

        $filterTag = $xmldoc->createElement('filter');
        $setTag->appendChild($filterTag);

        $ftpLogin = $xmldoc->createElement('name', $userName);
        $filterTag->appendChild($ftpLogin);

        $valuesTag = $xmldoc->createElement('values');
        $setTag->appendChild($valuesTag);

        $ftpPasswordValue = $xmldoc->createTextNode($password);
        $ftpPassword = $xmldoc->createElement('password');
        $ftpPassword->appendChild($ftpPasswordValue);
        $valuesTag->appendChild($ftpPassword);
        
        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->{'ftp-user'}->{'set'}->result;
        $systemError = $response->system->errtext;
        if ('error' == (string)$resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError) ? $systemError : $resultNode->errtext);
            throw new ApiRequestException("Error in changing the Ftp Account password: {$error}");
        }
        return $response;    
    }
    
    /**
     * Get All the DNS records
     * 
     * @return array
     * @throws ApiRequestException
     */
    public function getDnsRecords() {
        \DBG::msg("MultiSite (PleskController): get DNS-record: $this->webspaceId");
        if (empty($this->webspaceId)) {
            return false;
        }
        
        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);
        $dns = $xmldoc->createElement('dns');
        $packet->appendChild($dns);       
        $getRec = $xmldoc->createElement('get_rec');
        $dns->appendChild($getRec);

        $filter = $xmldoc->createElement('filter');
        $getRec->appendChild($filter);       

        $id = $xmldoc->createElement('site-id', $this->webspaceId);
        $filter->appendChild($id);

        \DBG::dump($xmldoc->saveXML());
        $response     = $this->executeCurl($xmldoc);
        $resultNode   = $response->dns->{'get_rec'}->result;
        $responseJson = json_encode($response->dns->{'get_rec'});
        $responseArr  = json_decode($responseJson,true); 
        $systemError  = $response->system->errtext;
        if ('error' == (string)$resultNode->status || $systemError) {
            $error = (isset($systemError)?$systemError:$resultNode->errtext);
            throw new ApiRequestException("Error in getting DNS records : {$error} ");
        }      
        
        $resultArray = array();
        if (!empty($responseArr)) {
            foreach($responseArr['result'] as $result) {
                $resultArray[$result['id']] = $result['data']['host'];
            }
            return $resultArray;
        }
    }
    
    /**
     * Disable the DNS Service
     * 
     * @param integer $siteId
     * 
     * @return boolean
     * @throws ApiRequestException
     */
    public function disableDnsService($siteId)
    {
        \DBG::msg("MultiSite (PleskController): Disable DNS Service.");
        if (empty($siteId)) {
            return;
        }
        
        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);       

        $dns = $xmldoc->createElement('dns');
        $packet->appendChild($dns);
        
        $disableTag = $xmldoc->createElement('disable');
        $dns->appendChild($disableTag);

        $filterTag = $xmldoc->createElement('filter');
        $disableTag->appendChild($filterTag);
        
        $site = $xmldoc->createElement('site-id', $siteId);
        $filterTag->appendChild($site);
        
        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->{'dns'}->{'disable'}->result;
        $systemError = $response->system->errtext;
        if ('error' == (string)$resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError) ? $systemError : $resultNode->errtext);
            throw new ApiRequestException("Error in disable DNS service: {$error}");
        }
        return true;
    }
    
    /**
     * Get All FtpAccounts
     * 
     * @param boolean $extendedData Get additional data of the FTP user
     * 
     * @return array
     * @throws ApiRequestException
     */
    public function getAllEndUserAccounts($extendedData = false) {
        
        \DBG::msg("MultiSite (PleskController): get all Ftp Accounts: $this->webspaceId");
        if (empty($this->webspaceId)) {
            return false;
        }
        
        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);       

        $ftpUser = $xmldoc->createElement('ftp-user');
        $packet->appendChild($ftpUser);

        $getTag = $xmldoc->createElement('get');
        $ftpUser->appendChild($getTag);

        $filterTag = $xmldoc->createElement('filter');
        $getTag->appendChild($filterTag);
        
        $webspaceTag = $xmldoc->createElement('webspace-id', $this->webspaceId);
        $filterTag->appendChild($webspaceTag);
        
        $response       = $this->executeCurl($xmldoc);
        $resultNode     = $response->{'ftp-user'}->{'get'}->result;
        $responseJson   = json_encode($response->{'ftp-user'}->{'get'});
        $respArr        = json_decode($responseJson,true); 
        $systemError    = $response->system->errtext;
        if ('error' == (string)$resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError)?$systemError:$resultNode->errtext);
            throw new ApiRequestException("Error in getting Ftp Accounts : {$error} ");
        }      

        if (!empty($respArr)) {
            $resultArr   = array();
            $responseArr = (count($respArr['result']) == count($respArr['result'], COUNT_RECURSIVE)) ? $respArr : $respArr['result'];
            foreach ($responseArr as $result) {
                if ($extendedData) {
                    $websitePath = \Cx\Core\Setting\Controller\Setting::getValue('websitePath','MultiSite') . substr($result['home'], strlen(\Cx\Core\Setting\Controller\Setting::getValue('websiteFtpPath','MultiSite')));
                    
                    $resultArr[$result['id']] = array(
                        'name' => $result['name'],
                        'path' => $result['home'],
                        'isValid' => file_exists($websitePath) ? 1 : 0,
                    );
                } else {
                    $resultArr[$result['id']] = $result['name'];
                }
            }
        }
        return $resultArr;
        
    }
    
    /**
     * Create new domain alias
     * 
     * @param string $aliasName alias name
     * 
     * @return boolean true on success false otherwise
     */
    public function createDomainAlias($aliasName)
    {
        \DBG::msg("MultiSite (PleskController): create domain alias: $this->webspaceId");
        if (empty($this->webspaceId)) {
            return false;
        }
        
        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);
        
        $siteAlias = $xmldoc->createElement('site-alias');
        $packet->appendChild($siteAlias);
        
        $createTag = $xmldoc->createElement('create');
        $siteAlias->appendChild($createTag);
        
        $siteIdTag = $xmldoc->createElement('site-id', $this->webspaceId);
        $createTag->appendChild($siteIdTag);
        
        $nameTag = $xmldoc->createElement('name', $aliasName);
        $createTag->appendChild($nameTag);
        
        $response       = $this->executeCurl($xmldoc);
        $resultNode     = $response->{'site-alias'}->{'create'}->result;
        $systemError = $response->system->errtext;
        if ('error' == (string)$resultNode->status || $systemError){
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError) ? $systemError : $resultNode->errtext);
            throw new ApiRequestException("Error in creating Domain alias: {$error}");
        }
        
        return true;
    }
    
    /**
     * Rename the domain alias
     * 
     * @param string $oldAliasName old alias name
     * @param string $newAliasName new alias name
     * 
     * @return boolean true on success false otherwise
     */
    public function renameDomainAlias($oldAliasName, $newAliasName)
    {
        \DBG::msg("MultiSite (PleskController): rename domain alias");
        if (empty($oldAliasName) || empty($newAliasName)) {
            return false;
        }
        
        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);
        
        $siteAlias = $xmldoc->createElement('site-alias');
        $packet->appendChild($siteAlias);
        
        $renameTag = $xmldoc->createElement('rename');
        $siteAlias->appendChild($renameTag);
        
        $nameTag = $xmldoc->createElement('name', $oldAliasName);
        $renameTag->appendChild($nameTag);
        
        $newNameTag = $xmldoc->createElement('new_name', $newAliasName);
        $renameTag->appendChild($newNameTag);
        
        $response       = $this->executeCurl($xmldoc);
        $resultNode     = $response->{'site-alias'}->{'rename'}->result;
        $systemError = $response->system->errtext;
        if ('error' == (string)$resultNode->status || $systemError){
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError) ? $systemError : $resultNode->errtext);
            throw new ApiRequestException("Error in renaming Domain alias: {$error}");
        }
        
        return true;
    }
    
    /**
     * Remove the domain alias by name
     * 
     * @param string $aliasName alias name to delete
     * 
     * @return boolean true on success false otherwise
     */
    public function deleteDomainAlias($aliasName)
    {
        \DBG::msg("MultiSite (PleskController): delete domain alias");
        if (empty($aliasName)) {
            return false;
        }
        
        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);
        
        $siteAlias = $xmldoc->createElement('site-alias');
        $packet->appendChild($siteAlias);
        
        $deleteTag = $xmldoc->createElement('delete');
        $siteAlias->appendChild($deleteTag);
        
        $filterTag = $xmldoc->createElement('filter');
        $deleteTag->appendChild($filterTag);
        
        $nameTag = $xmldoc->createElement('name', $aliasName);
        $filterTag->appendChild($nameTag);
        
        $response       = $this->executeCurl($xmldoc);
        $resultNode     = $response->{'site-alias'}->{'delete'}->result;
        $systemError = $response->system->errtext;
        if ('error' == (string)$resultNode->status || $systemError){
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError) ? $systemError : $resultNode->errtext);
            throw new ApiRequestException("Error in deleting Domain alias: {$error}");
        }
        
        return true;
    }
    
    /**
     * Get formatted array from the given response array
     * 
     * @param  array $respArr response array. 
     * @return array
     */
    public function getFormattedResponse($respArr) {
        if (count($respArr['result']) == count($respArr['result'], COUNT_RECURSIVE)) {
            return $respArr;
        } else {
            return $respArr['result'];
        }
    }
    
    /**
     * Enable the mail service
     * 
     * @param integer $subscriptionId
     * 
     * @return boolean
     * @throws ApiRequestException
     */
    public function enableMailService($subscriptionId)
    {
        \DBG::msg("MultiSite (PleskController): Enable Mail Service.");
        if (empty($subscriptionId)) {
            return;
        }
        
        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);       

        $mail = $xmldoc->createElement('mail');
        $packet->appendChild($mail);
        
        $enableTag = $xmldoc->createElement('enable');
        $mail->appendChild($enableTag);
        
        $siteId = $xmldoc->createElement('site-id', $subscriptionId);
        $enableTag->appendChild($siteId);
        
        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->{'mail'}->{'enable'}->result;
        $systemError = $response->system->errtext;
        if ('error' == (string)$resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError) ? $systemError : $resultNode->errtext);
            throw new ApiRequestException("Error in enable mail service: {$error}");
        }
        return true;	
    } 

    /**
     * Disable the Mail Service
     * 
     * @param integer $subscriptionId
     * 
     * @return boolean
     * @throws ApiRequestException
     */
    public function disableMailService($subscriptionId)
    {
        \DBG::msg("MultiSite (PleskController): Disable Mail Service.");
        if (empty($subscriptionId)) {
            return;
        }
        
        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);       

        $mail = $xmldoc->createElement('mail');
        $packet->appendChild($mail);
        
        $disableTag = $xmldoc->createElement('disable');
        $mail->appendChild($disableTag);
        
        $siteId = $xmldoc->createElement('site-id', $subscriptionId);
        $disableTag->appendChild($siteId);
        
        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->{'mail'}->{'disable'}->result;
        $systemError = $response->system->errtext;
        if ('error' == (string)$resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError) ? $systemError : $resultNode->errtext);
            throw new ApiRequestException("Error in disable mail service: {$error}");
        }
        return true;
    }

    /**
     * Get the Mail Service status
     * 
     * @param integer $id
     * 
     * @return string Mail Service status
     * @throws ApiRequestException
     */
    public function getMailServiceStatus($id)
    {
        \DBG::msg("MultiSite (PleskController): Get Mail Service status.");
        if (empty($id)) {
            return;
        }
        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);       

        $mail = $xmldoc->createElement('mail');
        $packet->appendChild($mail);
        
        $getPrefs = $xmldoc->createElement('get_prefs');
        $mail->appendChild($getPrefs);
        
        $filter = $xmldoc->createElement('filter');
        $getPrefs->appendChild($filter);
        $siteId = $xmldoc->createElement('site-id', $id);
        $filter->appendChild($siteId);
        
        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->{'mail'}->{'get_prefs'}->result;
        $systemError = $response->system->errtext;
        if ('error' == (string)$resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError) ? $systemError : $resultNode->errtext);
            throw new ApiRequestException("Error in get mail service status: {$error}");
        }
        return $resultNode->prefs->mailservice;	
    }
    
    /**
     * Create a new auto-login url for Plesk.
     * 
     * @param integer $subscriptionId subscription id
     * @param string  $ipAddress      id address
     * @param string  $sourceAddress  source address
     * 
     * @return string $pleskLoginUrl
     * @throws ApiRequestException
     */
    public function getPanelAutoLoginUrl($subscriptionId, $ipAddress, $sourceAddress, $role = 'Mail Admin')
    {
        \DBG::msg("MultiSite (PleskController): get Panel auto login url.");
        
        if (empty($subscriptionId) || empty($ipAddress) || empty($sourceAddress)) {
            return false;
        }
        
        $this->webspaceId = $subscriptionId;
        
        $subscriptionOwnerGuid = $this->getSubscriptionOwnerGuid();
        $loginName = !empty($subscriptionOwnerGuid) ? $this->getAuxilaryUserLoginName($subscriptionOwnerGuid, $role) : '';
        
        if (empty($loginName)) {
            return false;
        }
        
        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);       

        $server = $xmldoc->createElement('server');
        $packet->appendChild($server);
        $createSession = $xmldoc->createElement('create_session');
        $server->appendChild($createSession);
        
        $login = $xmldoc->createElement('login', $loginName);
        $createSession->appendChild($login);
        $data = $xmldoc->createElement('data');
        $createSession->appendChild($data);
        
        $userIp = $xmldoc->createElement('user_ip', base64_encode($ipAddress));
        $data->appendChild($userIp);
        $sourceServer = $xmldoc->createElement('source_server', base64_encode($sourceAddress));
        $data->appendChild($sourceServer);
        
        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->{'server'}->{'create_session'}->result;
        $systemError = $response->system->errtext;
        if ('error' == (string)$resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError) ? $systemError : $resultNode->errtext);
            throw new ApiRequestException("Error in get panel auto login url: {$error}");
        }
        $pleskLoginUrl = "https://{$this->host}:8443/enterprise/rsession_init.php?PHPSESSID=" . $resultNode->id;
        return $pleskLoginUrl;
    }
    
    /**
     * Get the available service plans of mail service server
     * 
     * @return array
     * @throws ApiRequestException
     */
    public function getAvailableMailDistributionPlans() 
    {
        \DBG::msg("MultiSite (PleskController): get available service plans of mail service server.");
       
        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);

        $service = $xmldoc->createElement('service-plan');
        $packet->appendChild($service);
        
        $get = $xmldoc->createElement('get');
        $service->appendChild($get);
        
        $filter = $xmldoc->createElement('filter');
        $get->appendChild($filter); 
        
        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->{'service-plan'}->{'get'}->result;
        $responseJson = json_encode($response->{'service-plan'}->{'get'});
        $responseArr  = json_decode($responseJson, true); 
        
        $systemError = $response->system->errtext;
        if ('error' == (string) $resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError) ? $systemError : $resultNode->errtext);
            throw new ApiRequestException("Error in get service plans of mail service server: {$error}");
        }
        $servicePlans = array();
        foreach ($responseArr['result'] as $resultArr) {
            $servicePlans[$resultArr['name']] = $resultArr['guid'];
        }
        return $servicePlans;
    }
    
    /**
     * Create new site/domain
     * 
     * @param string  $domain          Name of the site/domain to create
     * @param string  $dnsTarget      (reference) Target of the vanity DNS entry
     * 
     * @return boolean true on success otherwise false
     * 
     * @throws ApiRequestException
     */
    public function createWebDistribution($domain, &$dnsTarget)
    {
        // Set vanity URL DNS target to FQDN
        $dnsTarget = $domain;
    }
    
    /**
     * Renaming the site/domain
     * 
     * @param string $oldDomainName old domain name 
     * @param string $newDomainName new domain name 
     * 
     * @return updated site/domain id
     * 
     * @throws ApiRequestException
     */
    public function renameWebDistribution($oldDomainName, $newDomainName)
    {        
    }
    
    /**
     * Remove the site by the domain name.
     * 
     * @param string $domain Domain name to remove
     * 
     * @return boolean true on success false otherwise
     * 
     * @throws ApiRequestException
     */
    public function deleteWebDistribution($domain)
    {
    }
    
    /**
     * Get all the sites under the existing subscription
     * 
     * @return array $siteList Name of all the sites under the subscription
     * 
     * @throws ApiRequestException
     */
    public function getAllWebDistributions() {
    }

    /**
     * {@inheritdoc}
     */
    public function createWebDistributionAlias($mainName, $aliasName) {
        $this->createSubscription($aliasName);
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
        // delete the subscription for this SSL domain
        $this->deleteSubscription($aliasName);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllWebDistributionAliases($websiteName = '') {
        // this is necessary to ensure MultiSite believes we have Aliases
        return $this->getAllSubscriptions();
    }

    /**
     * Internal method to create a subscription, used for SSL
     * @param string $domain Domain of the website
     * @return string Resulting node ID
     * @throws ApiRequestException On API error
     */
    protected function createSubscription($domain) {
        $documentRoot = 'httpdocs';
        \DBG::msg("MultiSite (PleskController): Create new site on existing subscription.");
        if (empty($domain)) {
            return false;
        }

        $allSubscriptions = $this->getAllSubscriptions();
        $existingKey = in_array($domain, $allSubscriptions);
        if ($existingKey !== false) {
            \DBG::msg("MultiSite (PleskController): subscription already exists -> abort");
            return $existingKey;
        }
        \DBG::msg("MultiSite (PleskController): subscription does not yet exists -> create");

        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);
        
        $site = $xmldoc->createElement('site');
        $packet->appendChild($site);
        
        $addTag = $xmldoc->createElement('add');
        $site->appendChild($addTag);
        
        $genSetup = $xmldoc->createElement('gen_setup');
        $addTag->appendChild($genSetup);
        
        $nameTag = $xmldoc->createElement('name', $domain);
        $genSetup->appendChild($nameTag);
        
        $webspaceIdTag = $xmldoc->createElement('webspace-id', $this->getWebspaceId());
        $genSetup->appendChild($webspaceIdTag);
        
        $hostingTag = $xmldoc->createElement('hosting');
        $addTag->appendChild($hostingTag);
        
        $virtualHostTag = $xmldoc->createElement('vrt_hst');
        $hostingTag->appendChild($virtualHostTag);
        
        // www_root
        $propertyWwwRootTag = $xmldoc->createElement('property');
        $virtualHostTag->appendChild($propertyWwwRootTag);
        
        $propertyWwwRootNameTag = $xmldoc->createElement('name', 'www_root');
        $propertyWwwRootTag->appendChild($propertyWwwRootNameTag);
        
        $propertyWwwRootValueTag = $xmldoc->createElement('value', $documentRoot);
        $propertyWwwRootTag->appendChild($propertyWwwRootValueTag);

        // ssl
        $propertySslTag = $xmldoc->createElement('property');
        $virtualHostTag->appendChild($propertySslTag);
        
        $propertySslNameTag = $xmldoc->createElement('name', 'ssl');
        $propertySslTag->appendChild($propertySslNameTag);

        $propertySslValueTag = $xmldoc->createElement('value', true);
        $propertySslTag->appendChild($propertySslValueTag);

	// Note: the setting of additional-settings (and additional-ssl-settings) does not work with plesk.
	// As a workaround the vHost templates have been adjusted (according to
	// https://support.plesk.com/hc/en-us/articles/213367389-How-to-change-the-default-content-for-newly-created-domains-in-Plesk-Hierzu)
	// to already contain the required apache directives. Those have been set as follows:
	//
	// vhost.conf:
	// Include "/home/httpd/vhosts/h1.cloudrexx.com/scripts/apache_h1.cloudrexx.com_HTTP.conf"
	//
	// vhost_ssl.conf:
	// Include "/home/httpd/vhosts/h1.cloudrexx.com/scripts/apache_h1.cloudrexx.com_HTTPS.conf"
	//
	// Additional note:
	// Due to bug #PPPM-9858, the above does not work when creating a new site that points to an existing
	// document root (in our case /home/httpd/vhosts/h1.cloudrexx.com/httpdocs/).
	// As a workaround, the following event handler has been add in plesk to solve this issue:
	//
	// Ereignis: Domain erstellt
	// Prioriät: 50 (normal)
	// Benutzer: root
	// Befehl:   /bin/bash /root/scripts/plesk_eventhandler_skel-cloudrexx.sh $NEW_DOMAIN_NAME
	//
	//
	//
        // additional-settings
        /*$propertyHttpTag = $xmldoc->createElement('property');
        $virtualHostTag->appendChild($propertyHttpTag);
        
        $propertyHttpNameTag = $xmldoc->createElement('name', 'additional-settings');
        $propertyHttpTag->appendChild($propertyHttpNameTag);

        $httpValue = <<<HTTP
Include "/home/httpd/vhosts/h1.cloudrexx.com/scripts/apache_h1.cloudrexx.com_HTTP.conf"
ServerAlias *.MAIN-DOMAIN
HTTP;
        $propertyHttpValueTag = $xmldoc->createElement('value', $httpValue);
        $propertyHttpTag->appendChild($propertyHttpValueTag);*/

        // additional-ssl-settings
        /*$propertyHttpsTag = $xmldoc->createElement('property');
        $virtualHostTag->appendChild($propertyHttpsTag);
        
        $propertyHttpsNameTag = $xmldoc->createElement('name', 'additional-ssl-settings');
        $propertyHttpsTag->appendChild($propertyHttpsNameTag);

        $httpsValue = <<<HTTPS
Include "/home/httpd/vhosts/h1.cloudrexx.com/scripts/apache_h1.cloudrexx.com_HTTPS.conf"
ServerAlias *.MAIN-DOMAIN
HTTPS;
        $propertyHttpsValueTag = $xmldoc->createElement('value', $httpsValue);
        $propertyHttpsTag->appendChild($propertyHttpsValueTag);*/

        // execute request
        

        $response   = $this->executeCurl($xmldoc);
        $resultNode = $response->site->add->result;
        
        $systemError = $response->system->errtext;
        if ('error' == (string) $resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError) ? $systemError : $resultNode->errtext);
            throw new ApiRequestException("Error in creating site on existing subscription: {$error}");
        }
        \DBG::msg("MultiSite (PleskController): subscription created");
        if ($resultNode->id) {
            \DBG::msg("MultiSite (PleskController): configure newly created subscription");
            $this->disableMailService($resultNode->id);
            \DBG::msg("MultiSite (PleskController): mail service disabled on newly created subscription");
            $this->disableDnsService($resultNode->id);
            \DBG::msg("MultiSite (PleskController): dns service disabled on newly created subscription");
        }
        return $resultNode->id;
    }

    /**
     * Internal method to rename a subscription, used for SSL
     * @param string $oldDomainName Old domain of the website
     * @param string $newDomainName New domain of the website
     * @return string Resulting node ID
     * @throws ApiRequestException On API error
     */
    protected function renameSubscription($oldDomainName, $newDomainName) {
        \DBG::msg("MultiSite (PleskController): Renaming the site on existing subscription.");
        if (empty($oldDomainName) || empty($newDomainName)) {
            return false;
        }
        
        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);
        
        $site = $xmldoc->createElement('site');
        $packet->appendChild($site);
        
        $setTag = $xmldoc->createElement('set');
        $site->appendChild($setTag);
        
        $filterTag = $xmldoc->createElement('filter');
        $setTag->appendChild($filterTag);
        
        $nameTag = $xmldoc->createElement('name', $oldDomainName);
        $filterTag->appendChild($nameTag);
        
        $valuesTag = $xmldoc->createElement('values');
        $setTag->appendChild($valuesTag);
        
        $genSetup = $xmldoc->createElement('gen_setup');
        $valuesTag->appendChild($genSetup);
        
        $newDomainNameTag = $xmldoc->createElement('name', $newDomainName);
        $genSetup->appendChild($newDomainNameTag);
        
        $response   = $this->executeCurl($xmldoc);
        $resultNode = $response->site->set->result;
        
        $systemError = $response->system->errtext;
        if ('error' == (string) $resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError) ? $systemError : $resultNode->errtext);
            throw new ApiRequestException("Error in Renaming the site on existing subscription: {$error}");
        }
        return $resultNode->id;
    }

    /**
     * Internal method to delete a subscription, used for SSL
     * @param string $domain Domain of the website
     * @return boolean True on success
     * @throws ApiRequestException On API error
     */
    protected function deleteSubscription($domain) {
        \DBG::msg("MultiSite (PleskController): Removing the site on existing subscription.");
        if (empty($domain)) {
            return false;
        }
        
        $xmldoc  = $this->getXmlDocument();
        $packet  = $this->getRpcPacket($xmldoc); 
        
        $site = $xmldoc->createElement('site');
        $packet->appendChild($site);
        
        $delTag = $xmldoc->createElement('del');
        $site->appendChild($delTag);
        
        $filterTag = $xmldoc->createElement('filter');
        $delTag->appendChild($filterTag);
        
        $nameTag = $xmldoc->createElement('name', $domain);
        $filterTag->appendChild($nameTag);
        
        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->site->del->result;
        
        $systemError = $response->system->errtext;
        if ('error' == (string) $resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError) ? $systemError : $resultNode->errtext);
            throw new ApiRequestException("Error in Removing site on existing subscription: {$error}");
        }
        return true;
    }

    /**
     * Internal method to get a list of subscription, used for SSL
     * @return array Key value array (ID => name)
     * @throws ApiRequestException On API error
     */
    protected function getAllSubscriptions() {
        \DBG::msg("MultiSite (PleskController): Get all sites under the existing subscription.");

        $xmldoc  = $this->getXmlDocument();
        $packet  = $this->getRpcPacket($xmldoc); 

        $site = $xmldoc->createElement('site');
        $packet->appendChild($site);

        $get = $xmldoc->createElement('get');
        $site->appendChild($get); 

        $filter = $xmldoc->createElement('filter');
        $get->appendChild($filter);

        $filterTag = $xmldoc->createElement('parent-id', $this->webspaceId);
        $filter->appendChild($filterTag);

        $dataset = $xmldoc->createElement('dataset');
        $get->appendChild($dataset);

        $genInfo = $xmldoc->createElement('gen_info');
        $dataset->appendChild($genInfo);

        $response = $this->executeCurl($xmldoc);

        $resultNode  = $response->site->get->result;
        $systemError = $response->system->errtext;
        if ('error' == (string) $resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError) ? $systemError : $resultNode->errtext);
            throw new ApiRequestException("Get all sites on existing subscription: {$error}");
        }

        $responseJson = json_encode($response->site->get);
        $respArr      = json_decode($responseJson, true);

        $siteList = array();
        if (!empty($respArr)) {
            $responseArr = isset($respArr['result'][0]) && is_array($respArr['result'][0]) ? $respArr['result'] : $respArr;
            foreach ($responseArr as $result) {
                $siteList[$result['id']] = $result['data']['gen_info']['name'];
            }
        }
        return $siteList;
    }

    /**
     * {@inheritdoc}
     */
    public function canGenerateCertificates() {
        return false;
    }

    /**
     * Install the SSL Certificate for the domain
     * 
     * @param string $name                      Certificate name
     * @param string $domain                    Domain name
     * @param string $certificatePrivateKey     certificate private key
     * @param string $certificateBody           certificate body
     * @param string $certificateAuthority      certificate authority
     */
    public function installSSLCertificate($websiteName, $name, $domain, $certificatePrivateKey, $certificateBody = null, $certificateAuthority = null) {
        \DBG::msg("MultiSite (PleskController): Install the SSL Certificate for the domain.");
        if (    empty($name) 
            ||  empty($domain) 
            ||  empty($certificatePrivateKey)
        ) {
            return false;
        }

        // create a subscription for this SSL domain
        $this->createSubscription($domain);
        
        $xmldoc  = $this->getXmlDocument();
        $packet  = $this->getRpcPacket($xmldoc); 
        
        $certificate = $xmldoc->createElement('certificate');
        $packet->appendChild($certificate);
        
        $install = $xmldoc->createElement('install');
        $certificate->appendChild($install);
        
        $certificateName = $xmldoc->createElement('name', $name);
        $install->appendChild($certificateName);
        
        $webspace = $xmldoc->createElement('webspace', $domain);
        $install->appendChild($webspace);
        
        $content = $xmldoc->createElement('content');
        $install->appendChild($content);
        
        $certificateSigningRequestTag = $xmldoc->createElement('csr', '');
        $content->appendChild($certificateSigningRequestTag);
        
        $certificatePrivateKeyTag = $xmldoc->createElement('pvt', $certificatePrivateKey);
        $content->appendChild($certificatePrivateKeyTag);
        
        if (!empty($certificateBody)) {
            $certificateBodyTag = $xmldoc->createElement('cert', $certificateBody);
            $content->appendChild($certificateBodyTag);
        }

        if (!empty($certificateAuthority)) {
            $certificateAuthorityTag = $xmldoc->createElement('ca', $certificateAuthority);
            $content->appendChild($certificateAuthorityTag);
        }
        
        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->certificate->install->result;
        
        $systemError = $response->system->errtext;
        if ('error' == (string) $resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError) ? $systemError : $resultNode->errtext);
            throw new ApiRequestException("Error in installing SSL Certificate for the domain: {$error}");
        }
        
        return true;
    }

    /**
     * Fetch the SSL Certificate details
     * 
     * @param string $domain domain name
     * 
     * @return array list of certificates
     */
    public function getSSLCertificates($websiteName, $domain = '') {
        \DBG::msg("MultiSite (PleskController): Fetch the SSL Certificate details.");
        
        $xmldoc  = $this->getXmlDocument();
        $packet  = $this->getRpcPacket($xmldoc); 
        
        $certificate = $xmldoc->createElement('certificate');
        $packet->appendChild($certificate);
        
        $getTag = $xmldoc->createElement('get-pool');
        $certificate->appendChild($getTag);

        if (!empty($domain)) {
            $filterTag = $xmldoc->createElement('filter');
            $getTag->appendChild($filterTag);
            
            $domainTag = $xmldoc->createElement('domain-name', $domain);
            $filterTag->appendChild($domainTag);
        }
        
        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->certificate->{'get-pool'}->result;
        
        $systemError = $response->system->errtext;
        if ('error' == (string) $resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError) ? $systemError : $resultNode->errtext);
            \DBG::msg("Error in fetching the SSL Certificate: {$error}");
            return false;
        }
        
        $responseJson = json_encode($resultNode);
        $respArr      = json_decode($responseJson, true);
        $resultArr    = !isset($respArr['certificates']['certificate']) || (count($respArr['certificates']['certificate']) == count($respArr['certificates']['certificate'], COUNT_RECURSIVE)) 
                        ? $respArr['certificates'] : $respArr['certificates']['certificate'];
        
        //store all the certificate names into an array
        $certificateList = array();
        foreach($resultArr as $result) {
            $certificateList[] = $result['name'];
        }
        
        return $certificateList;
    }
    
    /**
     * Remove the SSL Certificates
     * 
     * @param string $domain domain name
     * @param array  $names  certificate names
     */
    public function removeSSLCertificates($websiteName, $domain, $names = array()) {
        \DBG::msg("MultiSite (PleskController): Remove the SSL Certificates.");
        if (!is_array($names) || empty($names) || empty($domain)) {
            return false;
        }
        
        $xmldoc  = $this->getXmlDocument();
        $packet  = $this->getRpcPacket($xmldoc); 
        
        $certificate = $xmldoc->createElement('certificate');
        $packet->appendChild($certificate);
        
        $removeTag = $xmldoc->createElement('remove');
        $certificate->appendChild($removeTag);
        
        $filterTag = $xmldoc->createElement('filter');
        $removeTag->appendChild($filterTag);
        
        foreach ($names as $name) {
            $certificateNameTag = $xmldoc->createElement('name', $name);
            $filterTag->appendChild($certificateNameTag);
        }
        
        $webspaceTag = $xmldoc->createElement('webspace', $domain);
        $removeTag->appendChild($webspaceTag);
        
        $response = $this->executeCurl($xmldoc);
        $resultNode = $response->certificate->remove->result;
        
        $systemError = $response->system->errtext;
        if ('error' == (string) $resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError) ? $systemError : $resultNode->errtext);
            throw new ApiRequestException("Error in removing the SSL Certificate: {$error}");
        }
        
        return true;
    }

    /**
     * Activate the SSL Certificate
     *
     * @param string $certificateName certificate name
     * @param string $siteId    ID of the plesk site
     *
     * @return boolean
     * @throws ApiRequestException
     */
    public function activateSSLCertificate($websiteName, $certificateName, $siteId)
    {
        \DBG::msg('MultiSite (PleskController): Activate the SSL Certificate.');
        if (empty($certificateName) || empty($siteId)) {
            return false;
        }

        $xmldoc = $this->getXmlDocument();
        $packet = $this->getRpcPacket($xmldoc);

        $site = $xmldoc->createElement('site');
        $packet->appendChild($site);

        $set = $xmldoc->createElement('set');
        $site->appendChild($set);

        $filter = $xmldoc->createElement('filter');
        $set->appendChild($filter);

        $domainName = $xmldoc->createElement('id', $siteId);
        $filter->appendChild($domainName);

        $values = $xmldoc->createElement('values');
        $set->appendChild($values);

        $hosting = $xmldoc->createElement('hosting');
        $values->appendChild($hosting);

        $virtualHost = $xmldoc->createElement('vrt_hst');
        $hosting->appendChild($virtualHost);

        $property = $xmldoc->createElement('property');
        $virtualHost->appendChild($property);

        $name = $xmldoc->createElement('name', 'certificate_name');
        $property->appendChild($name);

        $value = $xmldoc->createElement('value', $certificateName);
        $property->appendChild($value);

        $response   = $this->executeCurl($xmldoc);
        $resultNode = $response->site->set->result;

        $systemError = $response->system->errtext;
        if ('error' == (string) $resultNode->status || $systemError) {
            \DBG::dump($xmldoc->saveXML());
            \DBG::dump($response);
            $error = (isset($systemError) ? $systemError : $resultNode->errtext);
            throw new ApiRequestException("Error in activating the SSL Certificate: {$error}");
        }
        
        return true;
    }
}
