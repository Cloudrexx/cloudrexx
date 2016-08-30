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

define('UPDATE_PATH', dirname(dirname(__FILE__)));
define('UPDATE_TIME', time());
define('UPDATE_TIMEOUT_TIME', UPDATE_TIME + 55);
define('UPDATE_LIB', UPDATE_PATH.'/lib');
define('UPDATE_CORE', UPDATE_PATH.'/core');
define('UPDATE_TPL', 'template/contrexx');
define('UPDATE_LANG', UPDATE_PATH.'/lang');
define('UPDATE_UPDATES', UPDATE_PATH.'/updates');
define('UPDATE_SUPPORT_FORUM_URI', 'http://www.cloudrexx.com/forum/');
define('UPDATE_UTF8', true);
define('UPDATE_TIMEZONE', 'Europe/Zurich');
date_default_timezone_set(UPDATE_TIMEZONE);
