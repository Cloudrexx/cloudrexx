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


namespace Cx\Core_Modules\TemplateEditor\Model\Entity;

/**
 * Class OptionSetFileStorage
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Robin Glauser <robin.glauser@cloudrexx.com>
 * @package     contrexx
 * @subpackage  core_module_templateeditor
 */
class OptionSetFileStorage extends \Cx\Model\Base\EntityBase
    implements Storable
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
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     */
    public function retrieve($name)
    {
        $optionSetFilePath = $this->path . '/' . $name . '/options';
        $optionSetOptionsFile = $optionSetFilePath. '/Options.yml';
        $optionSetGroupsFile = $optionSetFilePath. '/Groups.yml';
        $data = $this->retrieveFile($optionSetOptionsFile);
        $data['groups'] = $this->retrieveFile($optionSetGroupsFile, true);
        return $data;
    }

    /**
     * @param  String   $fileName   the file to load including its path
     * @param  Boolean  $isOptional if true, no exception will be thrown if the
     *                                  file is not found
     * @return array                the data from the file
     * @throws \Symfony\Component\Yaml\Exception\ParseException thrown if the file is not found or empty
     */
    protected function retrieveFile($fileName, $isOptional = false){
        $file = $this->cx->getClassLoader()
            ->getFilePath($fileName);
        if (!$file) {
            if ($isOptional) {
                return;
            }
            throw new \Symfony\Component\Yaml\Exception\ParseException(
                "File" . $fileName . 'not found'
            );
        }

        $content = file_get_contents($file);
        if (!$content) {
            if ($isOptional) {
                return;
            }
            throw new \Symfony\Component\Yaml\Exception\ParseException(
                "File" . $fileName . 'is empty'
            );
        }

        try {
            $yaml = new \Symfony\Component\Yaml\Parser();
            return $yaml->parse($content);
        } catch (\Symfony\Component\Yaml\Exception\ParseException $e) {
            preg_match(
                "/line (?P<line>[0-9]+)/", $e->getMessage(), $matches
            );
            throw new \Symfony\Component\Yaml\Exception\ParseException(
                $e->getMessage(), $matches['line']
            );
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
            . '/' . $name . '/options/Options.yml',
            \Symfony\Component\Yaml\Yaml::dump($data->yamlSerialize(), 6)
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
