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
 * knowledgeLib
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Stefan Heinemann <sh@comvation.com>
 * @package     cloudrexx
 * @subpackage  module_knowledge
 */

namespace Cx\Modules\Knowledge\Controller;
\Env::get('ClassLoader')->loadFile(ASCMS_LIBRARY_PATH.'/activecalendar/activecalendar.php');

/**
 * Some basic operations for the knowledge module.
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Stefan Heinemann <sh@comvation.com>
 * @package cloudrexx
 * @subpackage  module_knowledge
 */
class KnowledgeLibrary {
    /**
     * The settings object
     *
     * @var object
     */
    protected $settings;

    /**
     * The articles object
     *
     * @var object
     */
    protected $articles;

    /**
     * The categories object
     *
     * @var object
     */
    protected $categories;

    /**
     * The tags object
     *
     * @var object
     */
    protected $tags;

    /**
     * Initialise the needed objects
     */
    public function __construct() {
        $this->categories = new KnowledgeCategory();
        $this->settings = new KnowledgeSettings();
        $this->articles = new KnowledgeArticles($this->isAllLangsActive());
        $this->tags = new KnowledgeTags();

        $this->_arrLanguages     = $this->createLanguageArray();
    }

    /**
     * Update the global setting
     *
     * @param int $value
     * @throws DatabaseError
     * @global $objDatabase
     */
    protected function updateGlobalSetting($value)
        {
            \Cx\Core\Setting\Controller\Setting::init('Config', 'component', 'Yaml');
            if (isset($value)) {
                if (!\Cx\Core\Setting\Controller\Setting::isDefined('useKnowledgePlaceholders')) {
                    \Cx\Core\Setting\Controller\Setting::add('useKnowledgePlaceholders', $value, 1, \Cx\Core\Setting\Controller\Setting::TYPE_RADIO, '1:TXT_ACTIVATED,0:TXT_DEACTIVATED', 'component');
                } else {
                    \Cx\Core\Setting\Controller\Setting::set('useKnowledgePlaceholders', $value);
                   \Cx\Core\Setting\Controller\Setting::update('useKnowledgePlaceholders');
                }
            }
        }

    /**
     * Return the global setting
     *
     * @return string
     * @throws DatabaseError
     * @global $objDatabase
     * @return mixed
     */
    protected function getGlobalSetting()
    {
            //return the global setting('useKnowledgePlaceholders') value
            \Cx\Core\Setting\Controller\Setting::init('Config', 'component','Yaml');
            return \Cx\Core\Setting\Controller\Setting::getValue('useKnowledgePlaceholders','Config');
    }

    /**
     * Creates an array containing all frontend-languages.
     *
     * Contents:
     * $arrValue[$langId]['short']        =>    For Example: en, de, fr, de-CH, ...
     * $arrValue[$langId]['long']        =>    For Example: 'English', 'Deutsch', 'French', ...
     *
     * @return    array        $arrReturn
     */
    function createLanguageArray() {

        $arrReturn = array();

        foreach (\FWLanguage::getActiveFrontendLanguages() as $frontendLanguage) {
            $arrReturn[$frontendLanguage['id']] = array(
                'short' =>  stripslashes($frontendLanguage['lang']),
                'long'  =>  htmlentities(stripslashes($frontendLanguage['name']),ENT_QUOTES, CONTREXX_CHARSET)
            );
        }

        return $arrReturn;
    }

    /**
     * Tells whether the allLangs setting is active or not
     * @return boolean True if setting is enabled
     */
    public function isAllLangsActive() {
        return $this->settings->get("show_all_langs") == 1;
    }

    /**
     * Returns lang ID based on allLangs setting
     * @return int|null lang ID
     */
    public function getLangId() {
        if ($this->isAllLangsActive()) {
            return null;
        }
        global $_LANGID;
        return $_LANGID;
    }
}
