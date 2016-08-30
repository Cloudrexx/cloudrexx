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


namespace Cx\Core_Modules\Workbench\Controller;

class Toolbox {
    protected $template = null;

    public function __construct(&$language, $mode, &$arguments) {
        $this->template = new \Cx\Core\Html\Sigma(ASCMS_CORE_MODULE_PATH . '/Workbench/View/Template/Backend');
        switch ($mode) {
            case 'yaml':
                \JS::activate('ace');
                \Message::add('YAML toolbox is currently not working.', \Message::CLASS_WARN);
                \Message::add('Implement in '.__METHOD__.' ('.__FILE__.')', \Message::CLASS_WARN);
                $this->template->loadTemplateFile('Yaml.html');
                $res = \Env::get('db')->Execute('SHOW TABLES');
                while (!$res->EOF) {
                    $this->template->setVariable('TABLE', current($res->fields));
                    $this->template->parse('table_option');
                    $res->MoveNext();
                }
                //if (mode = table) {
                $result = $this->loadSql($arguments['table']);
                //} else {
                //$result = sql
                //}
                //$result = $this->sql2Yaml($result);
                $this->template->setVariable(array(
                    'TXT_WORKBENCH_TOOLBOX_YAML_FROM_TABLE' => $language['TXT_WORKBENCH_TOOLBOX_YAML_FROM_TABLE'],
                    'TXT_WORKBENCH_TOOLBOX_YAML_FROM_SQL' => $language['TXT_WORKBENCH_TOOLBOX_YAML_FROM_SQL'],
                    'TXT_WORKBENCH_TOOLBOX_SUBMIT' => $language['TXT_WORKBENCH_TOOLBOX_SUBMIT'],
                    'RESULT' => $result,
                ));
                break;
            case 'components':
                $this->template->loadTemplateFile('Components.html');
                $query = '
                    SELECT
                        `id`,
                        `name`,
                        `is_required`,
                        `is_core`
                    FROM
                        `' . DBPREFIX . 'modules`
                    ORDER BY
                        `name` ASC
                ';
                $res = \Env::get('db')->Execute($query);
                $modules = array();
                while (!$res->EOF) {
                    $fsExists = $this->componentExistsInFileSystem(
                        $res->fields['is_core'],
                        $res->fields['name']
                    );
                    $modules[$res->fields['name']] = array(
                        'id' => $res->fields['id'],
                        'name' => $res->fields['name'],
                        'type' => $res->fields['is_core'],
                        'exists_db' => 'true',
                        'exists_filesystem' => $fsExists,
                        'skeleton_version' => $this->getComponentStyle(
                            $res->fields['is_core'],
                            $res->fields['name']
                        ),
                    );
                    $res->MoveNext();
                }
                foreach (\Env::get('em')->getRepository('Cx\Core\Core\Model\Entity\SystemComponent')->findAll() as $component) {
                    if (isset($modules[$component->getName()])) {
                        continue;
                    }
                    $name = $component->getName();
                    $type = $component->getType();
                    $modules[$component->getName()] = array(
                        'id' => $component->getId(),
                        'name' => $component->getName(),
                        'type' => $component->getType(),
                        'exists_db' => '<span style="color:red;">false</span>',
                        'exists_filesystem' => $this->componentExistsInFileSystem(
                            $type,
                            $name
                        ),
                        'skeleton_version' => '3.1.0',
                    );
                }
                foreach (array(
                    ASCMS_CORE_FOLDER,
                    ASCMS_CORE_MODULE_FOLDER,
                    ASCMS_MODULE_FOLDER,
                ) as $basedir) {
                    $dh = opendir(ASCMS_DOCUMENT_ROOT . $basedir);
                    while ($file = readdir($dh)) {
                        if (substr($file, 0, 1) == '.') {
                            continue;
                        }
                        if (!is_dir(ASCMS_DOCUMENT_ROOT . $basedir . '/' . $file)) {
                            continue;
                        }
                        if (isset($modules[$file])) {
                            continue;
                        }
                        $modules[$file] = array(
                            'id' => '<span style="color:red;">(none)</span>',
                            'name' => $file,
                            'type' => preg_replace('/s/', '', substr(strtolower($basedir), 1)),
                            'exists_db' => '<span style="color:red;">false</span>',
                            'exists_filesystem' => '.' . $basedir . '/' . $file,
                            'skeleton_version' => '<span style="color:red;">&lt;= 2.2.6</span>',
                        );
                    }
                    closedir($dh);
                }
                // add all not-yet-listed components existing in filesystem
                $tableDefinition = array(
                    'fields' => array(
                        'id' => array(
                            'table' => array(
                                'parse' => function($value) {return $value;}
                            ),
                        ),
                        'exists_db' => array(
                            'table' => array(
                                'parse' => function($value) {return $value;}
                            ),
                        ),
                        'skeleton_version' => array(
                            'table' => array(
                                'parse' => function($value) {return $value;}
                            ),
                        ),
                    )
                );
                $table = new \BackendTable(new \Cx\Core_Modules\Listing\Model\Entity\DataSet($modules), $tableDefinition);
                $this->template->setVariable(array(
                    'RECORD_COUNT' => count($modules),
                    'RESULT' => $table->toHtml(),
                ));
                break;
        }
    }

    protected function componentExistsInFileSystem(&$type, &$name) {
        $path = ASCMS_MODULE_FOLDER;
        $name = preg_replace('/[0-9]$/', '', $name);
        if ($type === '1' || $type == 'core' || $type == 'core_module') {
            $type = 'core_module';
            $path = ASCMS_CORE_MODULE_FOLDER;
        } else {
            $type = 'module';
        }
        if ($name == 'JsonData') {
            $name = 'Json';
        }
        $path .= '/';
        if (is_dir(ASCMS_CUSTOMIZING_PATH . $path . $name)) {
            return './customizing' . $path . $name;
        } else if (is_dir(ASCMS_DOCUMENT_ROOT . $path . $name)) {
            return '.' . $path . $name;
        }
        if (is_dir(ASCMS_CUSTOMIZING_PATH . $path . ucfirst($name))) {
            $name = ucfirst($name);
            return './customizing' . $path . ucfirst($name);
        } else if (is_dir(ASCMS_DOCUMENT_ROOT . $path . ucfirst($name))) {
            $name = ucfirst($name);
            return '.' . $path . ucfirst($name);
        }
        if ($type == 'core_module') {
            $path = ASCMS_CORE_FOLDER . '/';
            if (is_dir(ASCMS_CUSTOMIZING_PATH . $path . $name)) {
                $type = 'core';
                return './customizing' . $path . ucfirst($name);
            } else if (is_dir(ASCMS_DOCUMENT_ROOT . $path . $name)) {
                $type = 'core';
                return '.' . $path . ucfirst($name);
            }
            if (is_dir(ASCMS_CUSTOMIZING_PATH . $path . ucfirst($name))) {
                $name = ucfirst($name);
                $type = 'core';
                return './customizing' . $path . ucfirst($name);
            } else if (is_dir(ASCMS_DOCUMENT_ROOT . $path . ucfirst($name))) {
                $name = ucfirst($name);
                $type = 'core';
                return '.' . $path . ucfirst($name);
            }
        }
        return '<span style="color:red;">false</span>';
    }

    protected function getComponentStyle($core, $name) {
        if ($this->componentExists($name)) {
            return '3.1.0';
        }
        if ($this->componentExistsInFileSystem($core, $name) !== '<span style="color:red;">false</span>') {
            if (preg_match('/[A-Z]/', $name)) {
                return '3.0.3';
            }
        }
        // if there's a loading exception in legacy component handler
        // return 3.0.0
        $legacyComponentHandler = new \Cx\Core\Core\Controller\LegacyComponentHandler();
        if (
            $legacyComponentHandler->hasExceptionFor(true, 'load', $name) ||
            $legacyComponentHandler->hasExceptionFor(false, 'load', $name)
        ) {
            return '3.0.0';
        }
        return '<span style="color:red;">&lt;= 2.2.6</span>';
    }

    protected function componentExists($name) {
        $componentRepo = \Env::get('em')->getRepository('Cx\Core\Core\Model\Entity\SystemComponent');
        return (bool) $componentRepo->findOneBy(array('name' => $name));
    }

    protected function loadSql($table) {
        $res = \Env::get('db')->Execute('SHOW CREATE TABLE `' . $table . '`');
        return $res->fields['Create Table'];
    }

    protected function sql2Yaml($sql) {
        \DBG::activate(DBG_PHP);
        $em = \Env::get('em');
        $em->getConfiguration()->setMetadataDriverImpl(
    new \Doctrine\ORM\Mapping\Driver\DatabaseDriver(
        $em->getConnection()->getSchemaManager()
    )
);

$cmf = new \Doctrine\ORM\Tools\DisconnectedClassMetadataFactory();
$cmf->setEntityManager($em);
return;
$metadata = $cmf->getMetadataFor('ContrexxContentPage');
//$metadata = $cmf->getAllMetadata();
$exporter = $cme->getExporter('yml', '/path/to/export/yml');
$exporter->setMetadata($metadata);
return $exporter->export();
    }

    public function __toString() {
        return $this->template->get();
    }
}
