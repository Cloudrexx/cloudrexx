<?php

/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2016
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
 * SettingsLanguageFile
 *
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_locale
 * @version     5.0.0
 */

namespace Cx\Core\Locale\Model\Entity;

/**
 * SettingsLanguageFile
 *
 * This is used for the backend (/translation) view
 *
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_locale
 * @version     5.0.0
 */
class SettingsLanguageFile extends LanguageFile  {

    /**
     * The source language which defines the language file
     * @var \Cx\Core\Locale\Model\Entity\Language
     */
    protected $destLanguage;

    /**
     * SettingsLanguageFile constructor.
     *
     * Creates new instance of \Cx\Core\Locale\Model\Entity\SettingsLanguageFile
     * Loads component specific language data according to params
     *
     * @param \Cx\Core\Locale\Model\Entity\Language $language Source language
     * @param \Cx\Core\Locale\Model\Entity\Language $language Destination language
     * @param string $componentName Defines the component
     * @param boolean $frontend Defines wether to open the frontend or the backend specific file
     * @param boolean $onlyCustomized Defines wether to load only the customized language placeholders or all
     * @throws \Cx\Core\Locale\Model\Entity\LanguageFileException
     */
    public function __construct(
        $sourceLanguage = '',
        $destLanguage = '',
        $componentName = 'Core',
        $frontend = true,
        $onlyCustomized = true
    ) {
        global $_ARRAYLANG;
        $this->destLanguage = $destLanguage;
        if (!isset($this->destLanguage)) {
            throw new LanguageFileException(
                $_ARRAYLANG['TXT_CORE_LOCALE_LANGUAGEFILE_LANGUAGE_NOT_SET']
            );
        }
        parent::__construct($sourceLanguage, $componentName, $frontend, $onlyCustomized);

        // load component specific language data from init
        $init = \Env::get('init');
        if (!$onlyCustomized) {
            // We do not surround this with try-catch as this must not throw an exception!
            $baseData = $init->getComponentSpecificLanguageDataByCode(
                $this->componentName,
                $frontend,
                'en',
                false
            );
            try {
                $sourceData = $init->getComponentSpecificLanguageDataByCode(
                    $this->componentName,
                    $frontend,
                    $sourceLanguage->getIso1(),
                    false
                );
            } catch(\InitCMSException $e) {
                // Source language is not set: internally change to EN and add message
                return;
            }
            try {
                $destData = $init->getComponentSpecificLanguageDataByCode(
                    $this->componentName,
                    $frontend,
                    $destLanguage->getIso1(),
                    false
                );
            } catch(\InitCMSException $e) {}

            $this->data = array();
            foreach ($baseData as $name=>$value) {
                if (!isset($sourceData[$name])) {
                    $sourceData[$name] = $value;
                }
                if (!isset($destData[$name])) {
                    $destData[$name] = $value;
                }
                $this->data[$name] = array(
                    'id' => $name,
                    'sourceLang' => $sourceData[$name],
                    'destLang' => $destData[$name],
                    'virtual' => true,
                    'initData' => $destData[$name],
                );
            }
            $this->updateLanguageData();
        }
    }

    /**
     * Updates the language data with the placeholders from the yaml file
     */
    public function updateLanguageData() {
        // sort out calls from the parent constructor
        if (!current($this->data) || !isset(current($this->data)['destLang'])) {
            return;
        }
        // update language data
        foreach ($this->placeholders as $name=>$placeholder) {
            $this->data[$placeholder->getName()]['destLang'] = $placeholder->getValue();
        }
    }

    public function getDestLang() {
        return $this->destLanguage;
    }

    /**
     * @param $init
     */
    protected function generatePath($init)
    {
        // set path
        $this->path = $init->arrModulePath[$this->componentName] .
            $this->destLanguage->getIso1() . '/' . $this->mode . '.yaml';
        // rewrite path to customizing
        $this->path = str_replace(
            $this->cx->getCodeBaseDocumentRootPath(),
            $this->cx->getWebsiteCustomizingPath(),
            $this->path
        );
    }

    /**
     * Removes a customized placeholder.
     * @param string $name Placeholder name
     * @param string $oldValue Old/un-customized value
     */
    public function removePlaceholder($name, $oldValue) {
        unset($this->placeholders[$name]);
        $this->data[$name]['destLang'] = $oldValue;
    }

    /**
     * Deletes the customizing file
     */
    public function delete() {
        \Cx\Lib\FileSystem\FileSystem::delete_file($this->getPath());
    }
}
