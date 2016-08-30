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
 * CronLinkManager.php
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_linkmanager
 */

// Note! The path below will work if the cron file is moved
// to "customizing/cron" folder. If you intend to trigger the LinkManager cron
// form a other directory, please adjust the path below.
require_once dirname(dirname(dirname(__FILE__))).'/core/Core/init.php';

$cx = init(\Cx\Core\Core\Controller\Cx::MODE_MINIMAL);
//\DBG::activate(DBG_ADODB_ERROR | DBG_PHP | DBG_LOG_FILE);

define('PROCESS_TIME', time());
$em = $cx->getDb()->getEntityManager();
$componentRepo = $em->getRepository('Cx\Core\Core\Model\Entity\SystemComponent');
$linkManager = $componentRepo->findOneBy(array('name'=>'LinkManager'));
foreach (\FWLanguage::getActiveFrontendLanguages() as $lang) {
    $linkCrawlerController = new Cx\Core_Modules\LinkManager\Controller\LinkCrawlerController($linkManager, $cx);
    $linkCrawlerController->loadCrawler($lang['id'], $lang['lang']);
}
