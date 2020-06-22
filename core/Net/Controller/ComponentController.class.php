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
 * Main controller for Net
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_net
 */

namespace Cx\Core\Net\Controller;

/**
 * Main controller for Net
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_net
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {
    /**
     * Holds an instanciated copy of the DomainRepository
     *
     * @var \Cx\Core\Net\Model\Repository\DomainRepository
     */
    protected $domainRepo = null;

    public function getControllerClasses() {
        // Return an empty array here to let the component handler know that there
        // does not exist a backend, nor a frontend controller of this component.
        return array();
    }

    public function preInit(\Cx\Core\Core\Controller\Cx $cx) {
        global $_CONFIG;
        $this->domainRepo = new \Cx\Core\Net\Model\Repository\DomainRepository();
        $_CONFIG['domainUrl'] = $this->domainRepo->getMainDomain()->getName();
        \Env::set('config', $_CONFIG);
    }

    /**
     * Get repository of domains
     *
     * @return  \Cx\Core\Net\Model\Repository\DomainRepository The domain
     *              repository of this website.
     */
    public function getDomainRepository() {
        return $this->domainRepo;
    }

    /**
     * Get the hostname of the website. This is usually the name of the
     * virtual host the website is running on.
     *
     * @return  string The hostname of the website
     */
    public function getHostname() {
        return $this->domainRepo->findOneBy(array('id' => 0))->getName();
    }

    /**
     * Convert idn to ascii Format
     *
     * @param string $name
     *
     * @return string
     */
    public static function convertIdnToAsciiFormat($name) {
        if (empty($name)) {
            return;
        }

        if (!function_exists('idn_to_ascii')) {
            \DBG::msg('Idn is not supported in this system.');
        } else {
            // Test if UTS #46 (http://unicode.org/reports/tr46/) is available.
            // Important: PHP7.2 has deprecated any other use than UTS #46.
            // Therefore after PHP7.2, Cloudrexx requires ICU 4.6 or newer
            // as minimum system requirement
            if (defined('INTL_IDNA_VARIANT_UTS46')) {
                $ascii = idn_to_ascii($name, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            } else {
                $ascii = idn_to_ascii($name);
            }

            // check if conversion was successful
            if (!empty($ascii)) {
                // in case the INTL extension is misconfigured on
                // the server, then the return value of idn_to_ascii()
                // will be empty. in that case let's return the
                // original domain's name
                $name = $ascii;
            }
        }

        return $name;
    }

    /**
     * Convert idn to utf8 format
     *
     * @param string $name
     *
     * @return string
     */
    public static function convertIdnToUtf8Format($name) {
        if (empty($name)) {
            return;
        }

        if (!function_exists('idn_to_utf8')) {
            \DBG::msg('Idn is not supported in this system.');
        } else {
            // Test if UTS #46 (http://unicode.org/reports/tr46/) is available.
            // Important: PHP7.2 has deprecated any other use than UTS #46.
            // Therefore after PHP7.2, Cloudrexx requires ICU 4.6 or newer
            // as minimum system requirement
            if (defined('INTL_IDNA_VARIANT_UTS46')) {
                $utf8 = idn_to_utf8($name, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            } else {
                $utf8 = idn_to_utf8($name);
            }

            // check if conversion was successful
            if (!empty($utf8)) {
                // in case the INTL extension is misconfigured on
                // the server, then the return value of idn_to_utf8()
                // will be empty. in that case let's return the
                // original domain's name
                $name = $utf8;
            }
        }

        return $name;
    }

    /**
     * Get Host by IP address
     *
     * @param string $ip IP address
     *
     * @return string
     */
    public function getHostByAddr($ip)
    {
        $dnsHostnameLookup = \Cx\Core\Setting\Controller\Setting::getValue(
            'dnsHostnameLookup',
            'Config'
        );
        if ($dnsHostnameLookup != 'on') {
            return $ip;
        }

        return gethostbyaddr($ip);
    }
}
