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
 * Wrapper class for Doctrine YAML Driver
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Däppen <thomas.daeppen@comvation.com>
 * @version     4.0.0
 * @package     cloudrexx
 * @subpackage  core_model
 */

namespace Cx\Core\Model\Controller;

/**
 * Wrapper class for Doctrine YAML Driver
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Däppen <thomas.daeppen@comvation.com>
 * @version     4.0.0
 * @package     cloudrexx
 * @subpackage  core_model
 */
class YamlDriver extends \Doctrine\ORM\Mapping\Driver\YamlDriver
{
    /**
     * @var string Class namespace prefix for custom ENUM type classes
     */
    const ENUM_CLASS_PREFIX = '\Cx\Core\Model\Data\Enum\\';

    /**
     * @var array List of registered custom ENUM type classes
     */
    protected static $enumClasses = array();

    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass($className, \Doctrine\Common\Persistence\Mapping\ClassMetadata $metadata)
    {
        $element = $this->getElement($className, true);
        // Customizing for Cloudrexx: YamlEntity extension
        if ($element['type'] == 'YamlEntity') {
            $metadata->setCustomRepositoryClass(
                isset($element['repositoryClass']) ? $element['repositoryClass'] : null
            );
            $metadata->isMappedSuperclass = true;
        }
        parent::loadMetadataForClass($className, $metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function getElement($className, $raw = false)
    {
        $result = parent::getElement($className);
        if ($raw) {
            return $result;
        }
        if ($result['type'] == 'YamlEntity') {
            $result['type'] = 'entity';
        }
        return $this->handleCustomEnumTypeClasses($className, $result);
    }

    /**
     * Makes ENUM types work in doctrine
     *
     * Turns the following YAML mapping:
     *   Cx\Type\Component\Model\Entity\Example:
     *     type: entity
     *     fields:
     *       example:
     *         type: enum
     *         values: ['foo', 'bar']
     * Into:
     *   Cx\Type\Component\Model\Entity\Example:
     *     type: entity
     *     fields:
     *       example:
     *         type: enum_component_example_example
     *
     * Additionally it creates a custom class for this ENUM in core/Model/Data/Enum/
     * and maps the type to the class.
     *
     * See also: Customizing in lib/doctrine/Doctrine/ORM/Mapping/Driver/YamlDriver.php
     * @param string $className Class name to get metadata for
     * @param string $result Result of the YAML parsing so far
     * @return string Parsed YAML
     */
    protected function handleCustomEnumTypeClasses($className, $result) {
        $classParts = explode('\\', $className);
        if (current($classParts) != 'Cx') {
            return $result;
        }
        if (isset($result['id'])) {
            foreach ($result['id'] as $fieldName=>&$fieldMapping) {
                $this->handleCustomEnumTypeClass(
                    $classParts[2],
                    $classParts[5],
                    $fieldName,
                    $fieldMapping
                );
            }
        }
        if (isset($result['fields'])) {
            foreach ($result['fields'] as $fieldName=>&$fieldMapping) {
                $this->handleCustomEnumTypeClass(
                    $classParts[2],
                    $classParts[5],
                    $fieldName,
                    $fieldMapping
                );
            }
        }
        return $result;
    }

    protected function handleCustomEnumTypeClass($componentName, $entityName, $fieldName, &$fieldMapping) {
        if ($fieldMapping['type'] != 'enum') {
            return;
        }
        $customEnumClassName = static::getEnumClassName(
            $componentName,
            $entityName,
            $fieldName
        );

        // Register custom ENUM type class
        $customTypeName = static::getEnumTypeName(
            $componentName,
            $entityName,
            $fieldName
        );
        $fieldMapping['type'] = $customTypeName;

        // If class is already registered in this request, we abort here
        if (in_array($customEnumClassName, static::$enumClasses)) {
            return;
        }
        static::registerCustomEnumType($customTypeName, $customEnumClassName);

        // If class is already present, we abort here
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $classLoader = $cx->getClassLoader();
        if ($classLoader->classExists($customEnumClassName, false, true)) {
            return;
        }

        $this->createCustomEnumTypeClass(
            $componentName,
            $entityName,
            $fieldName,
            $fieldMapping['values']
        );
    }

    /**
     * Creates a new class for a custom ENUM type
     * @param string $componentName Name of the component
     * @param string $entityName Name of the entity
     * @param string $fieldName Name of the field
     * @param array $values Valid values for the ENUM
     */
    protected function createCustomEnumTypeClass($componentName, $entityName, $fieldName, $values) {
        // Create custom ENUM type class
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $customEnumClass = new \Cx\Core\Html\Sigma(
            $cx->getCodeBaseCorePath() . '/Model/View/Template/Generic'
        );
        $customEnumClass->loadTemplateFile('EnumClass.tpl');
        $customEnumClass->setVariable(array(
            'NAMESPACE' => substr(static::getEnumClassNamespace($componentName, $entityName), 1),
            'CLASS_NAME' => ucfirst($fieldName),
        ));
        $first = true;
        foreach ($values as $value) {
            if ($first) {
                $first = false;
                $customEnumClass->hideBlock('nonfirst');
            } else {
                $customEnumClass->touchBlock('nonfirst');
            }
            $customEnumClass->setVariable('VALUE', $value);
            $customEnumClass->parse('value');
        }

        $customClassFileName = static::getEnumFileName(
            $componentName,
            $entityName,
            $fieldName
        );
        \Cx\Lib\FileSystem\FileSystem::make_folder(
            dirname($customClassFileName), true
        );
        $file = new \Cx\Lib\FileSystem\File($customClassFileName);
        $file->write($customEnumClass->get());
    }

    /**
     * Resolves a database field type to the correct (internal) ENUM type
     * This is used to make schema-tool:update work as expected
     * @param $tableName string Name of the table
     * @param $columnName string Name of the database column
     * @param $field string Database field type
     * @return string Updated database field type
     */
    public static function reverseLookupEnumType($tableName, $columnName, $type) {
        if ($type != 'enum') {
            return $type;
        }
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $className = $em->getEntityNameByTableName($tableName);
        if (!$className) {
            return $type;
        }
        $classParts = explode('\\', $className);
        if (current($classParts) != 'Cx') {
            return $type;
        }
        $metaData = $em->getClassMetadata($className);
        $fieldName = $metaData->getFieldName($columnName);
        $customEnumClassName = static::getEnumClassName($classParts[2], $classParts[5], $fieldName);
        $customTypeName = static::getEnumTypeName($classParts[2], $classParts[5], $fieldName); 
        // If class is already registered in this request, we abort here
        if (in_array($customEnumClassName, static::$enumClasses)) {
            return $customTypeName;
        }
        $cl = $cx->getClassLoader();
        // If class does not exist, we do not have such a type yet
        if (!$cl->classExists($customEnumClassName, false, true)) {
            return $type;
        }
        static::registerCustomEnumType($customTypeName, $customEnumClassName);
        return $customTypeName;
    }

    /**
     * Returns the custom ENUM type namespace
     * @param string $componentName The component name
     * @param string $entityName The entity name
     * @return string The custom ENUM type classe's namespace
     */
    protected static function getEnumClassNamespace($componentName, $entityName) {
        return static::ENUM_CLASS_PREFIX . $componentName . '\\' . $entityName;
    }

    /**
     * Returns the custom ENUM type class name
     * @param string $componentName The component name
     * @param string $entityName The entity name
     * @param string $fieldName The field name
     * @return string The custom ENUM type class name
     */
    protected static function getEnumClassName($componentName, $entityName, $fieldName) {
        return static::getEnumClassNamespace(
            $componentName,
            $entityName
        ) . '\\' .  ucfirst($fieldName);
    }

    /**
     * Returns the custom ENUM type name (Doctrine type name)
     * @param string $componentName The component name
     * @param string $entityName The entity name
     * @param string $fieldName The field name
     * @return string The custom ENUM type name
     */
    protected static function getEnumTypeName($componentName, $entityName, $fieldName) {
        return strtolower(
            'enum_' . $componentName . '_' . $entityName . '_' . $fieldName
        );
    }

    /**
     * Returns the custom ENUM file name
     * @param string $componentName The component name
     * @param string $entityName The entity name
     * @param string $fieldName The field name
     * @return string The custom ENUM file name
     */
    protected static function getEnumFileName($componentName, $entityName, $fieldName) {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        return $cx->getCodeBaseCorePath() .
            '/Model/Data/Enum/' . $componentName . '/' . $entityName .
            '/' . ucfirst($fieldName) . '.class.php';
    }

    /**
     * Returns an array with component, entity and field name
     * @param string $filename Filename to analyze
     * @return array Info array or empty array if filename is not of an ENUM type
     */
    protected static function getInfoFromFileName($filename) {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        if (
            strpos(
                $filename,
                $cx->getCodeBaseCorePath() . '/Model/Data/Enum/'
            ) !== 0
        ) {
            return array();
        }
        $filename = substr(
            $filename,
            strlen($cx->getCodeBaseCorePath() . '/Model/Data/Enum/') - 1
        );
        $parts = explode('/', $filename);
        if (count($parts) != 4) {
            return array();
        }
        if (substr($parts[3], -10) != '.class.php') {
            return array();
        }
        return array(
            'ComponentName' => $parts[1],
            'EntityName' => $parts[2],
            'FieldName' => lcfirst(substr($parts[3], 0, -10)),
        );
    }

    /**
     * Registers a custom ENUM type for Doctrine
     * @param string $customTypeName Doctrine name for the type
     * @param string $customClassName Custom ENUM type class name
     */
    protected static function registerCustomEnumType($customTypeName, $customClassName, $connection = null) {
        if (in_array($customClassName, static::$enumClasses)) {
            return;
        }
        \Doctrine\DBAL\Types\Type::addType(
            $customTypeName,
            substr($customClassName, 1)
        );
        if (!$connection) {
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $connection = $cx->getDb()->getEntityManager()->getConnection();
        }
        $connection->getDatabasePlatform()->registerDoctrineTypeMapping(
            $customTypeName,
            $customTypeName
        );
        static::$enumClasses[] = $customClassName;
    }

    /**
     * Registers all known ENUM types to Doctrine
     * If this method is called before $cx->getDb() has been called
     * you must specify $connection
     * @param \Doctrine\DBAL\Connection $connection (optional) Doctrine connection
     */
    public static function registerKnownEnumTypes($connection = null) {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        // TODO: We should cache $enums, but this method is called before
        // getComponent() can be used (endless recursion)!
        $enums = null;//$cx->getComponent('Cache')->fetch('ClxEnumTypes');
        if (!is_array($enums) || !count($enums)) {
            $directory = new \RecursiveDirectoryIterator(
                $cx->getCodeBaseCorePath() . '/Model/Data/Enum/'
            );
            $iterator = new \RecursiveIteratorIterator($directory);
            $regex = new \RegexIterator(
                $iterator,
                '/^.+\.class\.php$/i',
                \RecursiveRegexIterator::GET_MATCH
            );
            $enums = array();
            foreach ($regex as $file) {
                $enumInfo = static::getInfoFromFileName(current($file));
                if (!count($enumInfo)) {
                    continue;
                }
                $enums[] = array(
                    'TypeName' => static::getEnumTypeName(
                        $enumInfo['ComponentName'],
                        $enumInfo['EntityName'],
                        $enumInfo['FieldName']
                    ),
                    'ClassName' => static::getEnumClassName(
                        $enumInfo['ComponentName'],
                        $enumInfo['EntityName'],
                        $enumInfo['FieldName']
                    ),
                );
            }
            //$cx->getComponent('Cache')->save('ClxEnumTypes', $enums);
        }
        foreach ($enums as $enumInfo) {
            static::registerCustomEnumType(
                $enumInfo['TypeName'],
                $enumInfo['ClassName'],
                $connection
            );
        }
    }
}
