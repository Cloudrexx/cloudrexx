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
 * SocialLogin
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_framework
 */

namespace Cx\Lib;

/**
 * Social Login
 * This class is used to provide a support for social media login.
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Ueli Kramer <ueli.kramer@comvation.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  lib_framework
 */
class SocialLogin
{
    private static $providers = array();

    public function __construct()
    {
        self::$providers = self::getProviders();
    }

    /**
     * Login with a provider.
     * Loads the correct provider class from Lib\OAuth\.. and uses his methods to
     * login to the social platform
     *
     * @param string $provider the chosen oauth provider
     * @return null if the chosen oauth provider does not exist
     */
    public function loginWithProvider($provider)
    {
        if (
            !isset(self::$providers[$provider]) ||
            !self::$providers[$provider]->isActive()
        ) {
            return null;
        }
        self::$providers[$provider]->login();
    }

    /**
     * Get the oauth class of the provider
     *
     * @static
     * @param string $provider the provider name
     * @return null|OAuth
     */
    public static function getClassByProvider($provider)
    {
        $class = '\Cx\Lib\OAuth\\' . ucfirst($provider);
        if (class_exists($class)) {
            return $class;
        }
        return null;
    }

    /**
     * Gets all the providers from the setting db.
     *
     * @static
     * @return array the providers and their data
     */
    public static function getProviders()
    {
        \Cx\Core\Setting\Controller\Setting::init('Access', 'sociallogin');

        $settingProviders = json_decode(\Cx\Core\Setting\Controller\Setting::getValue('providers', 'Access'));
        foreach ($settingProviders as $providerName => $providerData) {
            $class = self::getClassByProvider($providerName);
            if ($class != null) {
                $oauthProvider = new $class;
                $oauthProvider->setApplicationData($providerData->settings);
                $oauthProvider->setActive(isset($providerData->active) ? $providerData->active : false);
                self::$providers[$providerName] = $oauthProvider;
            }
        }
        return self::$providers;
    }

    /**
     * Updates the providers and write changes to the setting db.
     * The provider array has to be two dimensional.
     *
     * array(
     *     ProviderName1 => array(provider_app_id, provider_app_secret),
     *     ProviderName1 => array(provider_app_id, provider_app_secret),
     * )
     *
     * @static
     * @param array $providers the new provider data
     */
    public static function updateProviders($providers)
    {
        \Cx\Core\Setting\Controller\Setting::init('Access', 'sociallogin');
        \Cx\Core\Setting\Controller\Setting::set('providers', json_encode($providers));
        \Cx\Core\Setting\Controller\Setting::update('providers');
    }

    /**
     * Generates the cloudrexx login link to log in with the given provider.
     * This can be used to generate the redirect url.
     *
     * @static
     * @param string $provider the provider name
     * @param string|null $redirect the redirect url
     * @return string
     */
    public static function getLoginUrl($provider, $redirect = null)
    {
        global $_CONFIG, $objInit;
        return ASCMS_PROTOCOL . '://' . $_CONFIG['domainUrl'] . ASCMS_PATH_OFFSET . '/' .
            \FWLanguage::getLanguageCodeById($objInit->getDefaultFrontendLangId()) .
            '/index.php?section=Login&provider=' . $provider . (!empty($redirect) ? '&redirect=' . $redirect : '');
    }

    /**
     * Parse the sociallogin login buttons in the template given.
     *
     * @static
     * @param $objTpl template object to parse
     * @param string $prefix the prefix for the template blocks and variables
     */
    public static function parseSociallogin($objTpl, $prefix = 'login_')
    {
        $arrSettings = \User_Setting::getSettings();
        if (function_exists('curl_init') && $arrSettings['sociallogin']['status'] && !isset($_SESSION['user_id'])) {
            if (!empty($_GET['redirect'])) {
                $_SESSION['redirect'] = $_GET['redirect'];
            }
            $redirect = isset($_SESSION['redirect']) ? $_SESSION['redirect'] : null;
            $socialloginProviders = \Cx\Lib\SocialLogin::getProviders();
            foreach ($socialloginProviders as $provider => $providerData) {
                if (!$objTpl->blockExists($prefix . 'social_networks_' . $provider)) {
                    continue;
                }

                $objTpl->setVariable(strtoupper($prefix) . 'SOCIALLOGIN_' . strtoupper($provider), contrexx_raw2xhtml(\Cx\Lib\SocialLogin::getLoginUrl($provider, $redirect)));
                if ($providerData->isActive()) {
                    $objTpl->touchBlock($prefix . 'social_networks_' . $provider);
                } else {
                    $objTpl->hideBlock($prefix . 'social_networks_' . $provider);
                }
            }
        } else {
            if ($objTpl->blockExists($prefix . 'social_networks')) {
                $objTpl->hideBlock($prefix . 'social_networks');
            }
        }
    }

    /**
     * Hide the sociallogin login buttons in the given template.
     *
     * @static
     * @param $objTpl \Cx\Core\Html\Sigma template object to parse
     * @param string $prefix the prefix for the template blocks and variables
     */
    public static function hideLogin($objTpl, $prefix = 'login_')
    {
        if ($objTpl->blockExists($prefix . 'social_networks')) {
            $objTpl->hideBlock($prefix . 'social_networks');
        }
    }
}
