<?php declare(strict_types=1);

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
 * Representation of a hashed password
 *
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_model
 */

namespace Cx\Core\Model\Model\Entity;

/**
 * Representation of a hashed password
 *
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_model
 */
class Password {

    /**
     * @var string This password's hash
     */
    protected $hashedPassword;

    /**
     * Initializes this Password instance
     *
     * @param string $hashedPassword This password's hash
     */
    public function __construct(string $hashedPassword) {
        $this->hashedPassword = $hashedPassword;
    }

    /**
     * Creates a Password instance from a plaintext password (/not hashed)
     *
     * @param string $plaintextPassword A password in plain text
     * @throws \Cx\Core\Error\Model\Entity\ShinyException If password does not meet requirements
     * @return Password Password instance based on the given password
     */
    public static function createFromPlaintext(string $plaintextPassword): Password {
        return new static(static::hashPassword($plaintextPassword));
    }

    /**
     * Checks if the given string is valid as a password
     *
     * This method returns nothing on success and throws an exception otherwise.
     * @param string $plaintextPassword A password in plain text
     * @throws \Cx\Core\Error\Model\Entity\ShinyException If password does not meet requirements
     */
    protected static function checkPasswordValidity(string $plaintextPassword): void
    {
        global $_CONFIG, $_CORELANG;

        if (strlen($plaintextPassword) < 6) {
            throw new \Cx\Core\Error\Model\Entity\ShinyException(
                $_CORELANG['TXT_ACCESS_INVALID_PASSWORD']
            );
        }
        if (
            isset($_CONFIG['passwordComplexity']) &&
            $_CONFIG['passwordComplexity'] == 'on'
        ) {
            // Password must contain the following characters: upper, lower
            // case and numbers
            if (
                !preg_match('/[A-Z]+/', $plaintextPassword) ||
                !preg_match('/[a-z]+/', $plaintextPassword) ||
                !preg_match('/[0-9]+/', $plaintextPassword)
            ) {
                throw new \Cx\Core\Error\Model\Entity\ShinyException(
                    $_CORELANG['TXT_ACCESS_INVALID_PASSWORD_WITH_COMPLEXITY']
                );
            }
        }
    }

    /**
     * Generate hash of password with default hash algorithm
     *
     * @param string $plaintextPassword A password in plain text
     * @throws  \Cx\Core\Error\Model\Entity\ShinyException In case the password
     *                                                    hash generation fails
     * @return string The generated hash of the supplied password
     */
    protected static function hashPassword(string $plaintextPassword): string
    {
        static::checkPasswordValidity($plaintextPassword);
        $hash = password_hash($plaintextPassword, \PASSWORD_BCRYPT);
        if ($hash !== false) {
            return $hash;
        }

        throw new \Cx\Core\Error\Model\Entity\ShinyException(
            'Failed to generate a new password hash'
        );
    }

    /**
     * Returns whether the given password matches this Password
     *
     * @param string $plaintextPassword A password in plain text
     * @return bool True if $plaintextPassword matches this Password, false otherwise
     */
    public function matches(string $plaintextPassword): bool {
        // do not allow empty passwords
        if ($plaintextPassword == '') {
            return false;
        }

        // check if password is hashed with legacy algorithm (md5)
        if (
            preg_match('/^[a-f0-9]{32}$/i', $this->hashedPassword) &&
            md5($plaintextPassword) == $this->hashedPassword
        ) {
            return true;
        }

        // verify password
        if (password_verify($plaintextPassword, $this->hashedPassword)) {
            return true;
        }

        return false;
    }

    /**
     * Casts this Password to a string
     *
     * @return string Hashed password
     */
    public function __toString(): string {
        return $this->hashedPassword;
    }
}

