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

namespace Cx\Core_Modules\License;

class LicenseManager {
    /**
     * @var string
     */
    private $act;
    /**
     * @var \Cx\Core\Html\Sigma
     */
    private $template;
    /**
     * @var array
     */
    private $lang;
    /**
     * @var array
     */
    private $config;
    /**
     * @var License
     */
    private $license;
    /**
     * @var \ADONewConnection
     */
    private $db;

    public function __construct($act, $template, &$_CORELANG, &$_CONFIG, &$objDb) {
        $this->act = $act;
        $this->template = $template;
        $this->lang = $_CORELANG;
        $this->config = $_CONFIG;
        $this->db = $objDb;
        $this->license = License::getCached($_CONFIG, $this->db);
        $this->license->check();
        if ($this->template) {
            $this->template->setVariable('CONTENT_NAVIGATION', '
                <a href="index.php?cmd=License" class="active">'.$_CORELANG['TXT_LICENSE'].'</a>
            ');
        }
    }

    public function getPage($post, &$_CORELANG) {
        $lc = LicenseCommunicator::getInstance($this->config);
        $lc->addJsUpdateCode($this->lang, $this->license, true, false);
        $sm = new \Cx\Core\Config\Controller\Config();
        if (\FWUser::getFWUserObject()->objUser->getAdminStatus()) {
            if (isset($post['save']) && isset($post['licenseKey'])) {
                $license = License::getCached($this->config, $this->db);
                if ($license->checkSum(contrexx_input2db($post['licenseKey']))) {
                    $license->setLicenseKey(contrexx_input2db($post['licenseKey']));
                    // save it before we check it, so we only change the license key
                    $license->save($this->db);
                    $license->check();
                    $this->license = $license;
                }
            } else if (isset($post['update'])) {
                // This is only a backup if javascript is bogus
                try {
                    $lc->update($this->license, $this->config, true, false, $this->lang);
                    $this->license->save($this->db);
                } catch (\Exception $e) {}
            }
        }
        if (!file_exists(ASCMS_TEMP_PATH . '/licenseManager.html')) {
            try {
                $lc->update($this->license, $this->config, true, true, $this->lang);
                $this->license->save($this->db);
            } catch (\Exception $e) {}
        }
        if (file_exists(ASCMS_TEMP_PATH . '/licenseManager.html')) {
            \JS::activate('cx');
            $remoteTemplate = new \Cx\Core\Html\Sigma(ASCMS_TEMP_PATH);
            $remoteTemplate->loadTemplateFile('/licenseManager.html');

            if (isset($_POST['save']) && isset($_POST['licenseKey'])) {
                $remoteTemplate->setVariable('STATUS_TYPE', 'okbox');
                $remoteTemplate->setVariable('STATUS_MESSAGE', $this->lang['TXT_LICENSE_SAVED']);
            } else if (isset($_POST['update'])) {
                $remoteTemplate->setVariable('STATUS_TYPE', 'okbox');
                $remoteTemplate->setVariable('STATUS_MESSAGE', $this->lang['TXT_LICENSE_UPDATED']);
            }

            $remoteTemplate->setVariable($this->lang);

            $this->setLicensePlaceholders($remoteTemplate);

            if ($remoteTemplate->blockExists('legalComponents')) {
                foreach ($this->license->getLegalComponentsList() as $component) {
                    $remoteTemplate->setVariable('LICENSE_LEGAL_COMPONENT', contrexx_raw2xhtml($component));
                    $remoteTemplate->parse('legalComponents');
                }
            }

            if ($remoteTemplate->blockExists('licenseDomain')) {
                foreach ($this->license->getRegisteredDomains() as $domain) {
                    $remoteTemplate->setVariable('LICENSE_DOMAIN', contrexx_raw2xhtml($domain));
                    $remoteTemplate->parse('licenseDomain');
                }
            }

            $message = $this->license->getMessage(false, \FWLanguage::getLanguageCodeById(BACKEND_LANG_ID), $this->lang);
            if (!$sm->isWritable()) {
                $remoteTemplate->setVariable('MESSAGE_TITLE', preg_replace('/<br \/>/', ' ', sprintf($_CORELANG['TXT_SETTINGS_ERROR_NO_WRITE_ACCESS'], \Cx\Core\Config\Controller\Config::getSettingsFile())));
                $remoteTemplate->setVariable('MESSAGE_LINK', '#');
                $remoteTemplate->setVariable('MESSAGE_LINK_TARGET', '_self');
                $remoteTemplate->setVariable('MESSAGE_TYPE', 'alertbox');
            } else {
                if ($message && strlen($message->getText())) {
                    $remoteTemplate->setVariable('MESSAGE_TITLE', contrexx_raw2xhtml($this->getReplacedMessageText($message)));
                    $remoteTemplate->setVariable('MESSAGE_LINK', contrexx_raw2xhtml($message->getLink()));
                    $remoteTemplate->setVariable('MESSAGE_LINK_TARGET', contrexx_raw2xhtml($message->getLinkTarget()));
                    $remoteTemplate->setVariable('MESSAGE_TYPE', contrexx_raw2xhtml($message->getType()));
                } else {
                    if ($remoteTemplate->blockExists('message')) {
                        $remoteTemplate->setVariable('MESSAGE_TYPE', '" style="display:none;');
                    }
                }
            }

            if (\FWUser::getFWUserObject()->objUser->getAdminStatus()) {
                $remoteTemplate->touchBlock('licenseAdmin');
                $remoteTemplate->hideBlock('licenseNotAdmin');
            } else {
                $remoteTemplate->hideBlock('licenseAdmin');
                $remoteTemplate->touchBlock('licenseNotAdmin');
                $remoteTemplate->setVariable('LICENSE_ADMIN_MAIL', contrexx_raw2xhtml($this->config['coreAdminEmail']));
            }

            $this->template->setVariable('ADMIN_CONTENT', $remoteTemplate->get());
        } else {
            $this->template->setVariable('ADMIN_CONTENT', $this->lang['TXT_LICENSE_NO_TEMPLATE']);
        }
    }

    public function getReplacedMessageText($message) {
        $msgTemplate = new \Cx\Core\Html\Sigma();
        $msgTemplate->setTemplate($message->getText());
        $this->setLicensePlaceholders($msgTemplate);
        return $msgTemplate->get();
    }

    public function setLicensePlaceholders($template) {
        $date = $this->license->getValidToDate();
        if ($date) {
            $formattedValidityDate = date(ASCMS_DATE_FORMAT_DATE, $date);
        } else {
            $formattedValidityDate = '';
        }
        $date = $this->license->getCreatedAtDate();
        if ($date) {
            $formattedCreateDate = date(ASCMS_DATE_FORMAT_DATE, $date);
        } else {
            $formattedCreateDate = '';
        }
        $cdate = $this->license->getValidToDate();
        $today = time();
        $difference = $cdate - $today;
        if ($difference < 0) { $difference = 0; }
        $validDayCount = ceil($difference/60/60/24) - 1;
        if ($validDayCount < 0) {
            $validDayCount = 0;
        }
        $template->setVariable(array(
            'LICENSE_STATE' => $this->lang['TXT_LICENSE_STATE_' . $this->license->getState()],
            'LICENSE_EDITION' => contrexx_raw2xhtml($this->license->getEditionName()),
            'INSTALLATION_ID' => contrexx_raw2xhtml($this->license->getInstallationId()),
            'LICENSE_KEY' => contrexx_raw2xhtml($this->license->getLicenseKey()),
            'LICENSE_VALID_TO' => contrexx_raw2xhtml($formattedValidityDate),
            'LICENSE_VALID_DAY_COUNT' => contrexx_raw2xhtml($validDayCount),
            'LICENSE_CREATED_AT' => contrexx_raw2xhtml($formattedCreateDate),
            'LICENSE_REQUEST_INTERVAL' => contrexx_raw2xhtml($this->license->getRequestInterval()),
            'LICENSE_GRAYZONE_DAYS' => contrexx_raw2xhtml($this->license->getGrayzoneTime()),
            'LICENSE_FRONTENT_OFFSET_DAYS' => contrexx_raw2xhtml($this->license->getFrontendLockTime()),
            'LICENSE_UPGRADE_URL' => contrexx_raw2xhtml($this->license->getUpgradeUrl()),

            'LICENSE_PARTNER_TITLE' => contrexx_raw2xhtml($this->license->getPartner()->getTitle()),
            'LICENSE_PARTNER_LASTNAME' => contrexx_raw2xhtml($this->license->getPartner()->getLastname()),
            'LICENSE_PARTNER_FIRSTNAME' => contrexx_raw2xhtml($this->license->getPartner()->getFirstname()),
            'LICENSE_PARTNER_COMPANY' => contrexx_raw2xhtml($this->license->getPartner()->getCompanyName()),
            'LICENSE_PARTNER_ADDRESS' => contrexx_raw2xhtml($this->license->getPartner()->getAddress()),
            'LICENSE_PARTNER_ZIP' => contrexx_raw2xhtml($this->license->getPartner()->getZip()),
            'LICENSE_PARTNER_CITY' => contrexx_raw2xhtml($this->license->getPartner()->getCity()),
            'LICENSE_PARTNER_COUNTRY' => contrexx_raw2xhtml($this->license->getPartner()->getCountry()),
            'LICENSE_PARTNER_PHONE' => contrexx_raw2xhtml($this->license->getPartner()->getPhone()),
            'LICENSE_PARTNER_URL' => contrexx_raw2xhtml($this->license->getPartner()->getUrl()),
            'LICENSE_PARTNER_MAIL' => contrexx_raw2xhtml($this->license->getPartner()->getMail()),

            'LICENSE_CUSTOMER_TITLE' => contrexx_raw2xhtml($this->license->getCustomer()->getTitle()),
            'LICENSE_CUSTOMER_LASTNAME' => contrexx_raw2xhtml($this->license->getCustomer()->getLastname()),
            'LICENSE_CUSTOMER_FIRSTNAME' => contrexx_raw2xhtml($this->license->getCustomer()->getFirstname()),
            'LICENSE_CUSTOMER_COMPANY' => contrexx_raw2xhtml($this->license->getCustomer()->getCompanyName()),
            'LICENSE_CUSTOMER_ADDRESS' => contrexx_raw2xhtml($this->license->getCustomer()->getAddress()),
            'LICENSE_CUSTOMER_ZIP' => contrexx_raw2xhtml($this->license->getCustomer()->getZip()),
            'LICENSE_CUSTOMER_CITY' => contrexx_raw2xhtml($this->license->getCustomer()->getCity()),
            'LICENSE_CUSTOMER_COUNTRY' => contrexx_raw2xhtml($this->license->getCustomer()->getCountry()),
            'LICENSE_CUSTOMER_PHONE' => contrexx_raw2xhtml($this->license->getCustomer()->getPhone()),
            'LICENSE_CUSTOMER_URL' => contrexx_raw2xhtml($this->license->getCustomer()->getUrl()),
            'LICENSE_CUSTOMER_MAIL' => contrexx_raw2xhtml($this->license->getCustomer()->getMail()),

            'VERSION_NUMBER' => contrexx_raw2xhtml($this->license->getVersion()->getNumber()),
            'VERSION_NUMBER_INT' => contrexx_raw2xhtml($this->license->getVersion()->getNumber(true)),
            'VERSION_NAME' => contrexx_raw2xhtml($this->license->getVersion()->getName()),
            'VERSION_CODENAME' => contrexx_raw2xhtml($this->license->getVersion()->getCodeName()),
            'VERSION_STATE' => contrexx_raw2xhtml($this->license->getVersion()->getState()),
            'VERSION_RELEASE_DATE' => contrexx_raw2xhtml($this->license->getVersion()->getReleaseDate()),
        ));

        if ($template->blockExists('upgradable')) {
            if ($this->license->isUpgradable()) {
                $template->touchBlock('upgradable');
            } else {
                $template->hideBlock('upgradable');
            }
        }
    }
}
