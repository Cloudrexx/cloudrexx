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
 * @copyright   Cloudrexx AG
 * @author      Robin Glauser <robin.glauser@cloudrexx.com>
 * @package     contrexx
 */

namespace Cx\Core_Modules\TemplateEditor\Model;

use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Class PresetFileStorage
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Robin Glauser <robin.glauser@cloudrexx.com>
 * @package     contrexx
 * @subpackage  core_module_templateeditor
 */
class PresetFileStorage implements Storable
{
    /**
     * @var String
     */
    protected $path;


    /**
     * @param String $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Retrieve a preset
     *
     * @param String $name
     *
     * @return array
     * @throws ParseException
     * @throws PresetRepositoryException
     */
    public function retrieve($name)
    {
        if (!file_exists(
            \Cx\Core\Core\Controller\Cx::instanciate()->getClassLoader()
                ->getFilePath(
                    $this->path . '/options/presets/' . $name . '.yml'
                )
            )
        ) {
            throw new PresetRepositoryException(
                'Preset ' . $name . ' not found.'
            );
        }
        $file = file_get_contents(
            \Cx\Core\Core\Controller\Cx::instanciate()->getClassLoader()
                ->getFilePath(
                    $this->path . '/options/presets/' . $name . '.yml'
                )
        );
        if ($file) {
            try {
                $yaml = new Parser();
                return $yaml->parse($file);
            } catch (ParseException $e) {
                preg_match(
                    "/line (?P<line>[0-9]+)/", $e->getMessage(), $matches
                );
                throw new ParseException($e->getMessage(), $matches['line']);
            }
        } else {
            throw new ParseException("File not found");
        }
    }

    /**
     * Save a preset to disk
     *
     * @param                  $name
     * @param YamlSerializable $data
     *
     * @return bool
     */
    public function persist($name, YamlSerializable $data)
    {
        mkdir($this->path);
        mkdir($this->path . '/options/');
        mkdir($this->path . '/options/presets');
        return file_put_contents(
            $this->path . '/options/presets/' . $name . '.yml',
            Yaml::dump($data->yamlSerialize(), 5)
        );
    }

    /**
     * Get filtered preset list.
     *
     * @return array
     */
    public function getList()
    {
        $list = $this->getPresetList($this->path);
        $list = $this->mergePreset(
            $list,
            $this->getPresetList(
                \Cx\Core\Core\Controller\Cx::instanciate()
                    ->getCodeBaseThemesPath() . '/' . array_reverse(
                    explode('/', $this->path)
                )[0]
            )
        );
        // Move Default to first place
        $key = array_search('Default', $list);
        $new_value = $list[$key];
        unset($list[$key]);
        array_unshift($list, $new_value);
        return $list;
    }

    /**
     * Get preset list.
     *
     * @param $path
     *
     * @return array
     */
    public function getPresetList($path)
    {
        return array_map(
            function ($name) {
                return pathinfo($name, PATHINFO_FILENAME);
            },
            array_filter(glob($path . '/options/presets/*'), 'is_file')
        );
    }

    /**
     * Remove a preset.
     *
     * @param $name
     */
    public function remove($name)
    {
        \Cx\Lib\FileSystem\FileSystem::delete_file(
            $this->path . '/options/presets/' . $name . '.yml'
        );
    }

    /**
     * Merge Preset list
     *
     * @param $list
     * @param $getPresetList
     *
     * @return array
     */
    private function mergePreset($list, $getPresetList)
    {
        $finalArray = $getPresetList;
        foreach ($list as $entry) {
            if (!in_array($entry, $getPresetList)) {
                $finalArray[] = $entry;
            }
        }
        return $finalArray;
    }
}

class PresetRepositoryException extends \Exception
{

}
