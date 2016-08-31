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


require_once dirname(dirname(dirname(__FILE__))) . '/core/Core/init.php';
init('minimal');
echo contentManagerUpdates();

function contentManagerUpdates() {
    //Database migration
    try {
        //update module name
        \Cx\Lib\UpdateUtil::sql("INSERT INTO `".DBPREFIX."modules` (`id`, `name`, `distributor`, `description_variable`, `status`, `is_required`, `is_core`, `is_active`, `is_licensed`) VALUES ('72', 'ContentManager', 'DEV', 'TXT_CONTENTMANAGER_MODULE_DESCRIPTION', 'n', '0', '1', '1', '1')");
        //update navigation url
        \Cx\Lib\UpdateUtil::sql("UPDATE `".DBPREFIX."backend_areas` SET `uri` = 'index.php?cmd=ContentManager&act=new' WHERE `area_id` = 5");
        \Cx\Lib\UpdateUtil::sql("UPDATE `".DBPREFIX."backend_areas` SET `uri` = 'index.php?cmd=ContentManager' WHERE `area_id` = 6");
        \Cx\Lib\UpdateUtil::sql("UPDATE `".DBPREFIX."backend_areas` SET `uri` = 'index.php?cmd=ContentManager' WHERE `area_id` = 161");
        //Alter the content_page table structure
        \Cx\Lib\UpdateUtil::table(
                DBPREFIX . 'content_page', array(
            'id' => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'node_id' => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'id'),
            'nodeIdShadowed' => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'node_id'),
            'lang' => array('type' => 'INT(11)', 'after' => 'nodeIdShadowed'),
            'type' => array('type' => 'VARCHAR(16)', 'after' => 'lang'),
            'caching' => array('type' => 'TINYINT(1)', 'after' => 'type'),
            'updatedAt' => array('type' => 'timestamp', 'after' => 'caching'),
            'updatedBy' => array('type' => 'CHAR(40)', 'after' => 'updatedAt'),
            'title' => array('type' => 'VARCHAR(255)', 'after' => 'updatedBy'),
            'linkTarget' => array('type' => 'VARCHAR(16)', 'notnull' => false, 'after' => 'title'),
            'contentTitle' => array('type' => 'VARCHAR(255)', 'after' => 'linkTarget'),
            'slug' => array('type' => 'VARCHAR(255)', 'after' => 'contentTitle'),
            'content' => array('type' => 'longtext', 'after' => 'slug'),
            'sourceMode' => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'content'),
            'customContent' => array('type' => 'VARCHAR(64)', 'notnull' => false, 'after' => 'sourceMode'),
            'useCustomContentForAllChannels' => array('type' => 'INT(2)', 'notnull' => false, 'after' => 'customContent'),
            'applicationTemplate' => array('type' => 'VARCHAR(100)', 'notnull' => false, 'after' => 'useCustomContentForAllChannels'),
            'useCustomApplicationTemplateForAllChannels' => array('type' => 'TINYINT(2)', 'after' => 'applicationTemplate'),
            'cssName' => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'useCustomApplicationTemplateForAllChannels'),
            'cssNavName' => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'cssName'),
            'skin' => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'cssNavName'),
            'useSkinForAllChannels' => array('type' => 'INT(2)', 'notnull' => false, 'after' => 'skin'),
            'metatitle' => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'useSkinForAllChannels'),
            'metadesc' => array('type' => 'text', 'after' => 'metatitle'),
            'metakeys' => array('type' => 'text', 'after' => 'metadesc'),
            'metarobots' => array('type' => 'VARCHAR(7)', 'notnull' => false, 'after' => 'metakeys'),
            'start' => array('type' => 'timestamp', 'after' => 'metarobots'),
            'end' => array('type' => 'timestamp', 'after' => 'start'),
            'editingStatus' => array('type' => 'VARCHAR(16)', 'after' => 'end'),
            'protection' => array('type' => 'INT(11)', 'after' => 'editingStatus'),
            'frontendAccessId' => array('type' => 'INT(11)', 'after' => 'protection'),
            'backendAccessId' => array('type' => 'INT(11)', 'after' => 'frontendAccessId'),
            'display' => array('type' => 'TINYINT(1)', 'after' => 'backendAccessId'),
            'active' => array('type' => 'TINYINT(1)', 'after' => 'display'),
            'target' => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'active'),
            'module' => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'target'),
            'cmd' => array('type' => 'VARCHAR(50)', 'notnull' => true, 'default' => '', 'after' => 'module'),
                ),
                array(
                    'node_id' => array('fields' => array('node_id', 'lang'), 'type' => 'UNIQUE'),
                    'IDX_D8E86F54460D9FD7' => array('fields' => array('node_id'))
                ),
                'InnoDB',
                '',
                array(
                    'node_id' => array(
                        'table' => DBPREFIX . 'content_node',
                        'column' => 'id',
                        'onDelete' => 'SET NULL',
                        'onUpdate' => 'NO ACTION',
                    ),
                )
        );
    } catch (\Cx\Lib\UpdateException $e) {
        return "Error: $e->sql";
    }

    $virtualComponents = array('Agb', 'Ids', 'Imprint', 'Privacy');
    //migrating custom application template
    $pageRepo   = \Env::get('em')->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
    $themeRepo  = new \Cx\Core\View\Model\Repository\ThemeRepository();

    $pages      = $pageRepo->findBy(array('type' => \Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION));
    foreach ($pages As $page) {
        try {
            //virtual components do not migrating custom application template
            if (in_array(ucfirst($page->getModule()), $virtualComponents)) {
                continue;
            }
            $designTemplateName  = $page->getSkin() ? $themeRepo->findById($page->getSkin())->getFoldername() : $themeRepo->getDefaultTheme()->getFoldername();
            $cmd                 = !$page->getCmd() ? 'Default' : ucfirst($page->getCmd());
            $moduleFolderName    = contrexx_isCoreModule($page->getModule()) ? 'core_modules' : 'modules';

            $themesPath = ASCMS_THEMES_PATH . '/' . $designTemplateName;

            //check common module or core_module folder exists
            if (!file_exists($themesPath . '/' . $moduleFolderName)) {
                \Cx\Lib\FileSystem\FileSystem::make_folder($themesPath . '/' . $moduleFolderName);
            }

            //check module's folder exists
            if (!file_exists($themesPath . '/' . $moduleFolderName . '/' . $page->getModule())) {
                \Cx\Lib\FileSystem\FileSystem::make_folder($themesPath . '/' . $moduleFolderName . '/' . $page->getModule());
            }

            //check module's template folder exists
            if (!file_exists($themesPath . '/' . $moduleFolderName . '/' . $page->getModule() . '/Template')) {
                \Cx\Lib\FileSystem\FileSystem::make_folder($themesPath . '/' . $moduleFolderName . '/' . $page->getModule() . '/Template');
            }

            //check module's Frontend folder exists
            if (!file_exists($themesPath . '/' . $moduleFolderName . '/' . $page->getModule() . '/Template/Frontend')) {
                \Cx\Lib\FileSystem\FileSystem::make_folder($themesPath . '/' . $moduleFolderName . '/' . $page->getModule() . '/Template/Frontend');
            }

            $targetPath = $themesPath . '/' . $moduleFolderName . '/' . $page->getModule() . '/Template/Frontend';
            $applicationTemplateName = getFilename($targetPath, $cmd . '_custom_' . FWLanguage::getLanguageCodeById($page->getLang()));

            if (file_exists($targetPath)) {
                //create a application template file
                $file = new \Cx\Lib\FileSystem\File($targetPath . '/' . $applicationTemplateName);
                $file->write($page->getContent());
            }

            //update application template
            $page->setContent('{APPLICATION_DATA}');
            $page->setApplicationTemplate($applicationTemplateName);
            $page->setUseCustomApplicationTemplateForAllChannels(1);
            \Env::get('em')->persist($page);
            \Env::get('em')->flush();

        } catch (\Exception $e) {
            throw new \Exception('Error :' . $e);
        }
    }

    return 'Application template migrated successfully.';
}

function getFilename($path, $name) {
    if (!file_exists($path . '/' . $name . '.html')) {
        return $name . '.html';
    }

    $suffix = 1;
    while (file_exists($path . '/' . $name . $suffix . '.html')) {
        $suffix++;
    }

    return $name . $suffix . '.html';
}
