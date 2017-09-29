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
 * Wrapper class for \PHPMailer
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_mailtemplate
 */

namespace Cx\Core\MailTemplate\Model\Entity;

/**
 * Wrapper class for \PHPMailer
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_mailtemplate
 */
class Mail extends \PHPMailer
{
    public function __construct($exceptions = false)
    {
        global $_CONFIG;

        parent::__construct($exceptions);

        // set charset to be used for emails
        $this->CharSet = CONTREXX_CHARSET;

        // use email validation algorithm of cloudrexx
        // to validate email addresses
        self::$validator = function ($address) {
            return \FWValidator::isEmail($address);
        };

        // abort in case no custom SMTP server is set
        if (empty($_CONFIG['coreSmtpServer'])) {
            return;
        }

        // abort in case custom SMTP server is non-existant
        $arrSmtp = \SmtpSettings::getSmtpAccount($_CONFIG['coreSmtpServer']);
        if (!$arrSmtp) {
            return;
        }

        // set custom SMTP server
        $this->isSMTP();
        $this->Host = $arrSmtp['hostname'];
        $this->Port = $arrSmtp['port'];

        // abort in case no SMTP authentication is set
        if (empty($arrSmtp['username'])) {
            return;
        }

        // set SMTP authentication
        $this->SMTPAuth = true;
        $this->Username = $arrSmtp['username'];
        $this->Password = $arrSmtp['password'];
    }
}
