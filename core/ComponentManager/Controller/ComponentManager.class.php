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
 * Component Manager
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_componentmanager
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Core\ComponentManager\Controller;

class ComponentManagerException extends \Exception {};

/**
 * Component Manager
 *
 * This class manages the CMS Modules
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_componentmanager
 */
class ComponentManager
{
    var $arrayInstalledModules = array();
    var $arrayRemovedModules = array();
    var $langId;
    var $defaultOrderValue = 111;

    private $act = '';

    /**
     * Constructor
     */
    function __construct()
    {
        global $objInit;

        $this->langId = $objInit->userFrontendLangId;
    }
    private function setNavigation()
    {
        global $objTemplate, $_ARRAYLANG;

        $objTemplate->setVariable(array(
            'CONTENT_TITLE'      => $_ARRAYLANG['TXT_MODULE_MANAGER'],
            'CONTENT_NAVIGATION' => "<a href='index.php?cmd=ComponentManager' class='".($this->act == '' ? 'active' : '')."'>".$_ARRAYLANG['TXT_MODULE_MANAGER']."</a>"
                                     //<a href='index.php?cmd=ComponentManager&act=manage' class='".($this->act == 'manage' ? 'active' : '')."'>[".$_ARRAYLANG['TXT_MODULE_ACTIVATION']."]</a>"
        ));
    }


    function getModulesPage()
    {
        global $_ARRAYLANG, $objTemplate;

        $objTemplate->addBlockfile('ADMIN_CONTENT', 'module_manager', 'module_manager.html');

        $objTemplate->setVariable(array(
            'TXT_NAME'                   => $_ARRAYLANG['TXT_NAME'],
            'TXT_CONFIRM_DELETE_DATA'    => $_ARRAYLANG['TXT_CONFIRM_DELETE_DATA'],
            'TXT_ACTION_IS_IRREVERSIBLE' => $_ARRAYLANG['TXT_ACTION_IS_IRREVERSIBLE'],
            'TXT_DESCRIPTION'            => $_ARRAYLANG['TXT_DESCRIPTION'],
            'TXT_STATUS'                 => $_ARRAYLANG['TXT_STATUS'],
            'TXT_INSTALL_MODULE'         => $_ARRAYLANG['TXT_INSTALL_MODULE'],
            'TXT_PROVIDE_MODULE'         => $_ARRAYLANG['TXT_PROVIDE_MODULE'],
            'TXT_REMOVE_MODULE'          => $_ARRAYLANG['TXT_REMOVE_MODULE'],
            'TXT_ACCEPT_CHANGES'         => $_ARRAYLANG['TXT_ACCEPT_CHANGES'],
            'TXT_APPLICATION' => $_ARRAYLANG['TXT_APPLICATION']
        ));

        if (!isset($_GET['act'])) {
            $_GET['act'] = '';
        }

        switch($_GET['act']) {
            case "manage":
                \Permission::checkAccess(51, 'static');
                $this->manageModules();
                break;
            case "edit":
                \Permission::checkAccess(52, 'static');
                $this->modModules();
                $this->showModules();
                break;
            case "changestatus":
                \Permission::checkAccess(23, 'static');
                $this->changeModuleStatus();
                $this->showModules();
                break;
            default:
                \Permission::checkAccess(23, 'static');
                $this->showModules();
                break;
        }

        if (isset($_REQUEST['act'])) {
            $this->act = $_REQUEST['act'];
        } else {
            $this->act = '';
        }
        $this->setNavigation();
    }


    function changeModuleStatus()
    {
        global $objDatabase, $_ARRAYLANG;

        $moduleId = isset($_GET['id']) ? contrexx_input2raw($_GET['id']) : 0;
        $status   = isset($_GET['status']) ? contrexx_input2raw($_GET['status']) : 0;

        $query = "UPDATE
                    `".DBPREFIX."modules`
                  SET
                    `is_active` = '". contrexx_raw2db($status) ."'
                  WHERE
                    `id` = '" . contrexx_raw2db($moduleId) . "'";
        $objResult = $objDatabase->Execute($query);

        if ($objResult) {
            \Message::ok($status ? $_ARRAYLANG['TXT_MODULE_ACTIVATED_SUCCESSFULLY'] : $_ARRAYLANG['TXT_MODULE_DEACTIVATED_SUCCESSFULLY']);
        } else {
            $this->errorHandling();
            return false;
        }
    }

    function getModules()
    {
        global $objDatabase;

        $arrayInstalledModules = array();

        $qb = \Env::get('em')->createQueryBuilder();

        $qb->addSelect('p')
                ->from('Cx\Core\ContentManager\Model\Entity\Page', 'p')
                ->where('p.module IS NOT NULL');
//                ->andWhere($qb->expr()->eq('p.lang', $this->langId));
        $pages   = $qb->getQuery()->getResult();

        foreach ($pages as $page) {
            if (!in_array($page->getModule(), $arrayInstalledModules)) {
                $query = "
                    SELECT id
                    FROM ".DBPREFIX."modules
                    WHERE name='" . $page->getModule() . "'
                ";
                $objResult = $objDatabase->Execute($query);
                if ($objResult) {
                    if (!$objResult->EOF) {
                        $module_id = $objResult->fields['id'];
                    }
                } else {
                    $this->errorHandling();
                    return false;
                }
                $arrayInstalledModules[] = $module_id;
            }
        }

        return $arrayInstalledModules;
    }


    function showModules()
    {
        global $objDatabase, $_CORELANG, $_ARRAYLANG, $objTemplate;

        $objTemplate->setVariable('MODULE_ACTION', 'edit');
        $arrayInstalledModules = $this->getModules();
        $query = '
            SELECT
                m.id,
                m.name,
                m.description_variable,
                m.is_core,
                m.is_required,
                m.is_active,
                m.is_licensed
            FROM
                '.DBPREFIX.'modules AS m
            WHERE
                m.status=\'y\'
            ORDER BY
                m.is_required DESC,
                m.name ASC
        ';
        $i = 0;
        $objResult = $objDatabase->Execute($query);

        $statusLink = '<a href="index.php?cmd=ComponentManager&amp;act=changestatus&amp;id=%d&amp;status=%d"> %s </a>';
        $statusIcon = '<img src="../core/Core/View/Media/icons/%s" alt="" />';

        $moduleLink = '<a href="index.php?cmd=%s"> %s </a>';
        $moduleArchiveLink = '<a href="index.php?cmd=%s&amp;archive=%s"> %s </a>';

        if ($objResult) {
            while (!$objResult->EOF) {
                $class = (++$i % 2 ? 'row1' : 'row2');
                if (   in_array($objResult->fields['id'], $arrayInstalledModules)
                ) {
                    $moduleStatusLink = $objResult->fields['is_active']
                                        ? sprintf($statusLink, (int) $objResult->fields['id'], 0, sprintf($statusIcon, 'led_green.gif'))
                                        : sprintf($statusLink, (int) $objResult->fields['id'], 1, sprintf($statusIcon, 'led_red.gif'));

                    $objTemplate->setVariable(array(
                        'MODULE_REMOVE'  => "<input type='checkbox' name='removeModule[".$objResult->fields['id']."]' value='0' />",
                        'MODULE_INSTALL' => "&nbsp;",
                        'MODULE_STATUS'  => $moduleStatusLink
                    ));
                } else  {
                    $moduleStatusLink = $objResult->fields['is_licensed']
                                        ? ( $objResult->fields['is_active']
                                           ? sprintf($statusLink, $objResult->fields['id'], 0, sprintf($statusIcon, 'led_green.gif'))
                                           : sprintf($statusLink, $objResult->fields['id'], 1, sprintf($statusIcon, 'led_red.gif'))
                                          )
                                        : sprintf($statusLink, $objResult->fields['id'], 1, sprintf($statusIcon, 'led_red.gif'));
                    $objTemplate->setVariable(array(
                        'MODULE_INSTALL' => ($objResult->fields['is_active'] ? "<input type='checkbox' name='installModule[".$objResult->fields['id']."]' value='1' />" : ''),
                        'MODULE_REMOVE'  => "&nbsp;",
                        'MODULE_STATUS'  => $moduleStatusLink
                    ));
                }

                /*
                // core Modules
                if ($db->f('is_core')==1) {
                    $objTemplate->setVariable("MODULE_NAME", $db->f('name')." (core)");
                } else {
                    $objTemplate->setVariable("MODULE_NAME", $db->f('name'));
                }
                */

                if (isset($_CORELANG['TXT_MODULE_' . strtoupper($objResult->fields['name'])])) {
                    $literalName = $_CORELANG['TXT_MODULE_' . strtoupper($objResult->fields['name'])];
                } else if (isset($_CORELANG['TXT_' . strtoupper($objResult->fields['name']) . '_MODULE'])) {
                    $literalName = $_CORELANG['TXT_' . strtoupper($objResult->fields['name']) . '_MODULE'];
                } else if (isset($_CORELANG['TXT_' . strtoupper($objResult->fields['name'])])) {
                    $literalName = $_CORELANG['TXT_' . strtoupper($objResult->fields['name'])];
                } else {
                    $literalName = ucfirst($objResult->fields['name']);
                }

                if (!in_array($objResult->fields['name'], array('Agb', 'Error', 'Home', 'Ids', 'Imprint', 'Login', 'Privacy', 'Search', 'Sitemap'))
                    && (   in_array($objResult->fields['id'], $arrayInstalledModules)
                    )
                ) {
                        switch ($objResult->fields['name']) {
                            case 'Media1':
                            case 'Media2':
                            case 'Media3':
                            case 'Media4':
                                $archiveId   = substr($objResult->fields['name'], 5,1);
                                $literalName = sprintf($moduleArchiveLink, 'Media', 'archive'.$archiveId, $literalName);
                                break;
                            default:
                                $literalName = sprintf($moduleLink, $objResult->fields['name'], $literalName);
                                break;
                        }
                }

                $objTemplate->setVariable('MODULE_NAME', $literalName . ' (' . $objResult->fields['name'] . ')');

                // Required Modules
                if ($objResult->fields['is_required'] == 1) {
                    $class = 'highlighted';
                    $objTemplate->setVariable(array(
                        'MODULE_REQUIRED' => $_ARRAYLANG['TXT_REQUIRED'] . ' ' . (!$objResult->fields['is_licensed'] ? $_ARRAYLANG['TXT_LICENSE_NOT_LICENSED'] : ''),
                        'MODULE_REMOVE'   => '&nbsp;'
                    ));
                } else {
                    $objTemplate->setVariable('MODULE_REQUIRED', $_ARRAYLANG['TXT_OPTIONAL']);
                }

                if (isset($_CORELANG[$objResult->fields['description_variable']])) {
                    $description = $_CORELANG[$objResult->fields['description_variable']];
                } else {
                    $arrLang = $_ARRAYLANG;
                    // load language file
                    \Env::get('init')->loadLanguageData($objResult->fields['name']);
                    if (isset($_ARRAYLANG[$objResult->fields['description_variable']])) {
                        $description = $_ARRAYLANG[$objResult->fields['description_variable']];
                    } else {
                        $description = '';
                    }
                    $_ARRAYLANG = $arrLang;
                }

                if (
                    strpos($description, '.') !== false &&
                    strpos($description, '.') !== (strlen($description) - 1)
                ) {
                    $objTemplate->setVariable(array(
                        'MODULE_DESCRIPTION_CLASS' => 'description',
                    ));
                    $descExtended = substr($description, strpos($description, '.') + 1);
                    $description = substr($description, 0, strpos($description, '.') + 1) .
                        ' <img src="/core/Core/View/Media/AngleDown1x.png" alt=""/><span class="desc-extended"><br />' .
                        $descExtended . '</span>';
                }

                $objTemplate->setVariable(array(
                    'MODULE_ROWCLASS'   => $class . (!$objResult->fields['is_active'] ? ' rowInactive' : ''),
                    'MODULE_DESCRIPTON' => $description,
                    'MODULE_ID'         => $objResult->fields['id']
                ));
                $objTemplate->parse('moduleRow');
                $objResult->MoveNext();
            }
        }
    }


    function modModules()
    {
        global $_ARRAYLANG;

        $reload = false;
        if ($this->installModules()) {
            $installedModules = '';
            foreach (array_keys($this->arrayInstalledModules) as $moduleName) {
                $installedModules .=
                    (empty($installedModules) ? '' : ', ').$moduleName;
            }
            \Message::ok(sprintf($_ARRAYLANG['TXT_MODULES_INSTALLED_SUCCESFULL'], $installedModules));
            $reload = true;
        }
        if ($this->removeModules()) {
            $removedModules = '';
            foreach (array_keys($this->arrayRemovedModules) as $moduleName) {
                $removedModules .=
                    (empty($removedModules) ? '' : ', ').$moduleName;
            }
            \Message::ok(sprintf($_ARRAYLANG['TXT_MODULES_REMOVED_SUCCESSFUL'], $removedModules));
            $reload = true;
        }

        if (!$reload) {
            return;
        }

        \Cx\Core\Csrf\Controller\Csrf::redirect(\Cx\Core\Routing\Url::fromBackend('ComponentManager'));
    }


    function installModules()
    {
        global $objDatabase;
        $em = \Env::get('em');
        $nodeRepo = $em->getRepository('\Cx\Core\ContentManager\Model\Entity\Node');

        //$i = 1;
        if (empty($_POST['installModule']) || !is_array($_POST['installModule'])) {
            return false;
        }
        //$currentTime = time();
        $paridarray = array();
        foreach (array_keys($_POST['installModule']) as $moduleId) {
            $id = intval($moduleId);
            $objResult = $objDatabase->Execute("
                SELECT name
                  FROM ".DBPREFIX."modules
                 WHERE id=$id
            ");
            if ($objResult) {
                if (!$objResult->EOF) {
                    $module_name = $objResult->fields['name'];
                }
            } else {
                $this->errorHandling();
                return false;
            }

            // get content from repo
            $query = "SELECT *
            FROM ".DBPREFIX."module_repository
            WHERE moduleid=$id
            ORDER BY parid ASC";


            $objResult = $objDatabase->Execute($query);
            if ($objResult) {
                while (!$objResult->EOF) {
                    // define parent node
                    $root = false;
                    if (isset($paridarray[$objResult->fields['parid']])) {
                        $parcat = $paridarray[$objResult->fields['parid']];
                    } else {
                        $root = true;
                        $parcat = $nodeRepo->getRoot();
                    }
                    $this->arrayInstalledModules[$module_name] = true;
                    $sourceMode = (!empty($objResult->fields['expertmode']) && ($objResult->fields['expertmode'] == 'y')) ? true : false;

                    // create node
                    $newnode = new \Cx\Core\ContentManager\Model\Entity\Node();
                    $newnode->setParent($parcat); // replace root node by parent!
                    $em->persist($newnode);
                    $em->flush();
                    $nodeRepo->moveDown($newnode, true); // move to the end of this level
                    $paridarray[$objResult->fields['id']] = $newnode;

                    // add content to default lang
                    // add content to all langs without fallback
                    // link content to all langs with fallback
                    foreach (\FWLanguage::getActiveFrontendLanguages() as $lang) {
                        if ($lang['is_default'] === 'true' || $lang['fallback'] == null) {
                            $page = $this->createPage(
                                $newnode,
                                $lang['id'],
                                $objResult->fields['title'],
                                \Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION,
                                $module_name,
                                $objResult->fields['cmd'],
                                !$root && $objResult->fields['displaystatus'],
                                $sourceMode,
                                $objResult->fields['content']
                            );
                        } else {
                            $page = $this->createPage(
                                $newnode,
                                $lang['id'],
                                $objResult->fields['title'],
                                \Cx\Core\ContentManager\Model\Entity\Page::TYPE_FALLBACK,
                                $module_name,
                                $objResult->fields['cmd'],
                                !$root && $objResult->fields['displaystatus'],
                                $sourceMode,
                                ''
                            );
                        }
                        $em->persist($page);
                    }
                    $em->flush();
                    $objResult->MoveNext();
                }
            } else {
                $this->errorHandling();
                return false;
            }
        } // end foreach

        return true;
    }

    private function createPage($parentNode, $lang, $title, $type, $module, $cmd, $display, $sourceMode, $content) {
        $page = new \Cx\Core\ContentManager\Model\Entity\Page();
        $page->setNode($parentNode);
        $page->setNodeIdShadowed($parentNode->getId());
        $page->setLang($lang);
        $page->setTitle($title);
        $page->setType($type);
        $page->setModule($module);
        $page->setCmd($cmd);
        $page->setActive(true);
        $page->setDisplay($display); // pages on root level are not active
        $page->setSourceMode($sourceMode);
        $page->setContent($content);
        $page->setMetatitle($title);
        $page->setMetadesc($title);
        $page->setMetakeys($title);
        $page->setMetarobots('index');
        $page->setMetatitle($title);
        $page->setUpdatedBy(\FWUser::getFWUserObject()->objUser->getUsername());
        return $page;
    }

    function removeModules()
    {
        global $objDatabase;

        if (isset($_POST['removeModule']) && is_array($_POST['removeModule'])) {
            foreach (array_keys($_POST['removeModule']) as $moduleId) {

                $query = "
                    SELECT name
                    FROM ".DBPREFIX."modules
                    WHERE id='" . $moduleId . "'
                ";
                $objResult = $objDatabase->Execute($query);
                if ($objResult) {
                    if (!$objResult->EOF) {
                        $moduleName = $objResult->fields['name'];
                    }
                } else {
                    $this->errorHandling();
                    return false;
                }

                $em = \Env::get('em');
                $pageRepo = $em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
                $pages = $pageRepo->findBy(array(
                    'module' => $moduleName,
                    'lang' => $this->langId,
                ));
                $nodeIds = array();
                foreach ($pages as $page) {
                    $nodeIds[] = $page->getNode()->getId();
                }
                $jd = new \Cx\Core\Json\JsonData();
                $jd->data(
                    'node',
                    'multipleDelete',
                    array(
                        'post' => array(
                            'nodes' => array_unique($nodeIds),
                        ),
                    )
                );
            }
            return true;
        } else {
            return false;
        }

    }


    function manageModules()
    {
        global $objDatabase, $_CORELANG, $_ARRAYLANG, $objTemplate;

        $objTemplate->setVariable("MODULE_ACTION", "manage");
        if (isset($_POST['installModule']) && is_array($_POST['installModule'])) {
            foreach ($_POST['installModule'] as $key => $elem) {
                $id = intval($key);
                $addOnValue = intval($elem);
                $query = "UPDATE ".DBPREFIX."modules SET is_required = ".$addOnValue." WHERE id = ".$id;
                $objDatabase->Execute($query);
            }
        }

        if (isset($_POST['removeModule']) && is_array($_POST['removeModule'])) {
            foreach ($_POST['removeModule'] as $key => $elem) {
                $id = intval($key);
                $addOnValue = intval($elem);
                $query = "UPDATE ".DBPREFIX."modules SET is_required = ".$addOnValue." WHERE id = ".$id;
                $objDatabase->Execute($query);
            }
        }
        $query = "SELECT id,name, description_variable,is_required FROM ".DBPREFIX."modules WHERE id<>0 GROUP BY id";
        $objResult = $objDatabase->Execute($query);
        if ($objResult) {
            $i = 0;
            while (!$objResult->EOF) {
               if ($objResult->fields['is_required'] == 1) {
                    $objTemplate->setVariable(array(
                        'MODULE_REMOVE'  => "<input type='checkbox' name='removeModule[".$objResult->fields['id']."]' value='0' />",
                        'MODULE_INSTALL' => "&nbsp;",
                        'MODULE_STATUS'  => "<img src='../core/Core/View/Media/icons/led_green.gif' alt='' />"
                    ));
                } else {
                    $objTemplate->setVariable(array(
                        'MODULE_INSTALL' => "<input type='checkbox' name='installModule[".$objResult->fields['id']."]' value='1' />",
                        'MODULE_REMOVE'  => "&nbsp;",
                        'MODULE_STATUS'  => "<img src='../core/Core/View/Media/icons/led_red.gif' alt='' />"
                    ));
                }
                if (isset($_CORELANG[$objResult->fields['description_variable']])) {
                    $description = $_CORELANG[$objResult->fields['description_variable']];
                } else {
                    $arrLang = $_ARRAYLANG;
                    // load language file
                    \Env::get('init')->loadLanguageData($objResult->fields['name']);
                    if (isset($_ARRAYLANG[$objResult->fields['description_variable']])) {
                        $description = $_ARRAYLANG[$objResult->fields['description_variable']];
                    } else {
                        $description = '';
                    }
                    $_ARRAYLANG = $arrLang;
                }
                $objTemplate->setVariable(array(
                    'MODULE_ROWCLASS'   => ($i % 2 ? 'row2' : 'row2'),
                    'MODULE_NAME'       => $objResult->fields['name'],
                    'MODULE_DESCRIPTON' => $description,
                    'MODULE_ID'         => $objResult->fields['id']
                ));
                $objTemplate->parse("moduleRow");
                ++$i;
                $objResult->MoveNext();
            }
            return true;
        }
        $this->errorHandling();
        return false;
    }

    function errorHandling() {
        global $_ARRAYLANG;
        \Message::error($_ARRAYLANG['TXT_DATABASE_QUERY_ERROR']);
    }
}
