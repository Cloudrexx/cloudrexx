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


namespace Cx\Lib\OAuth;

global $cl;
$cl->loadFile(ASCMS_LIBRARY_PATH . '/services/Google/Google_Client.php');

/**
 * OAuth class for google authentication
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Ueli Kramer <ueli.kramer@comvation.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  lib_oauth
 */

class Google extends OAuth
{
    /**
     * contains only email address and user id
     * @var the object of the third party library
     */
    private static $google;

    /**
     * contains the sensitive data of the user
     * @var the object of the third party library
     */
    private static $googleplus;

    /**
     * @var the user data of the logged in social media user
     */
    protected static $userdata;

    /**
     * @var array the permissions used to retrieve the needed userdata
     */
    private static $scopes = array(
        'https://www.googleapis.com/auth/plus.me',
        'https://www.googleapis.com/auth/userinfo.email'
    );

    const OAUTH_PROVIDER = 'google';

    /**
     * Login to facebook and get the associated cloudrexx user.
     */
    public function login()
    {
        $client = new \Google_Client();
        $client->setApplicationName('Contrexx Login');
        $client->setClientId($this->applicationData[0]);
        $client->setClientSecret($this->applicationData[1]);
        $client->setRedirectUri(\Cx\Lib\SocialLogin::getLoginUrl(self::OAUTH_PROVIDER));
        $client->setDeveloperKey($this->applicationData[2]);
        $client->setUseObjects(true);
        $client->setApprovalPrompt('auto');
        $client->setScopes(self::$scopes);
        self::$google = new \Google_Oauth2Service($client);
        self::$googleplus = new \Google_PlusService($client);


        if (isset($_GET['code'])) {
            try {
                $client->authenticate();
            } catch (\Google_AuthException $e) {
            }
        }

        if (!$client->getAccessToken()) {
            \Cx\Core\Csrf\Controller\Csrf::header('Location: ' . $client->createAuthUrl());
            exit;
        }

        self::$userdata = $this->getUserData();
        $this->getContrexxUser(self::$userdata['oauth_id']);
    }

    /**
     * Get all the user data from facebook server.
     *
     * @return array
     */
    public function getUserData()
    {
        $userdata = array();
        $userinfo = self::$google->userinfo->get();
        $userprofile = self::$googleplus->people->get('me');
        $userdata['oauth_id'] = $userinfo->id;
        $userdata['email'] = $userinfo->email;
        $userdata['last_name'] = $userprofile->name->familyName;
        $userdata['first_name'] = $userprofile->name->givenName;
        return $userdata;
    }

    /**
     * @static
     * @return array the configuration parameters as language array key
     */
    public static function configParams()
    {
        return $configParams = array(
            'TXT_ACCESS_SOCIALLOGIN_PROVIDER_APP_ID',
            'TXT_ACCESS_SOCIALLOGIN_PROVIDER_SECRET',
            'TXT_ACCESS_SOCIALLOGIN_PROVIDER_API_KEY',
        );
    }
}
