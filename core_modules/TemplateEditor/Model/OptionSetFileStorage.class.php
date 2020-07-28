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


namespace Cx\Core_Modules\TemplateEditor\Model;

use Symfony\Component\Yaml\Exception;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Class FileStorage
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Robin Glauser <robin.glauser@cloudrexx.com>
 * @package     contrexx
 * @subpackage  core_module_templateeditor
 */
class OptionSetFileStorage implements Storable
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
     * @param String $name
     *
     * @return array
     * @throws ParseException
     */
    public function retrieve($name)
    {
        $file = \Cx\Core\Core\Controller\Cx::instanciate()->getClassLoader()->getFilePath(
            $this->path
            . '/' . $name . '/options/options.yml'
        );
        if (!$file) {
            throw new ParseException(
                "File" . $this->path
                . '/' . $name . '/options/options.yml not found'
            );
        }

        $content = file_get_contents($file);
        if (!$content) {
            throw new ParseException(
                "File" . $this->path
                . '/' . $name . '/options/options.yml not found'
            );
        }

        try {
            $yaml = new Parser();
            return $yaml->parse($content);
        } catch (ParseException $e) {
            preg_match(
                "/line (?P<line>[0-9]+)/", $e->getMessage(), $matches
            );
            throw new ParseException($e->getMessage(), $matches['line']);
        }
    }

    /**
     * @param                  $name
     * @param YamlSerializable $data
     *
     * @return bool
     */
    public function persist($name, YamlSerializable $data)
    {
        mkdir($this->path . '/' . $name);
        mkdir($this->path . '/' . $name . '/options');
        return file_put_contents(
            $this->path
            . '/' . $name . '/options/options.yml',
            Yaml::dump($data->yamlSerialize(), 5)
        );
    }

    /**
     * Get list with optionsets
     *
     * @return array
     */
    public function getList()
    {
        return array_filter(glob($this->path . '/'), 'is_dir');
    }

    /**
     * @param $name
     */
    public function remove($name)
    {
    }
}
