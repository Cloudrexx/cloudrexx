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
 * Backend controller to create the locale backend view.
 *
 * @copyright   Cloudrexx AG
 * @author      Manuel Schenk <manuel.schenk@comvation.com>
 * @author      Nicola Tommasi <nicola.tommasi@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_locale
 * @version     5.0.0
 */

namespace Cx\Core\Locale\Controller;

/**
 * Backend controller to create the locale backend view.
 * @copyright   Cloudrexx AG
 * @author      Manuel Schenk <manuel.schenk@comvation.com>
 * @author      Nicola Tommasi <nicola.tommasi@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_locale
 * @version     5.0.0
 */
class BackendController extends \Cx\Core\Core\Model\Entity\SystemComponentBackendController
{
    /**
     * The language file which is used to parse the placeholder list
     * @var \Cx\Core\Locale\Model\Entity\SettingsLanguageFile
     */
    protected $languageFile;

    /**
     * The doctrine repository of the locale entities
     * @var \Cx\Core\Locale\Model\Repository\LocaleRepository
     */
    protected $localeRepo;

    /**
     * The doctrine repository of the language entities
     * @var \Cx\Core\Locale\Model\Repository\LanguageRepository
     */
    protected $languageRepo;

    /**
     * Returns a list of available commands (?act=XY)
     * @return array List of acts
     */
    public function getCommands()
    {
        $cx = $this->cx;

        // permission for Locale and Language management
        $localeMgmtPermission = new \Cx\Core_Modules\Access\Model\Entity\Permission(array(), array(), true, array(), array(50));

        // permission for frontend variable management
        $variableMgmtPermission = new \Cx\Core_Modules\Access\Model\Entity\Permission(array(), array(), true, array(), array(48));

        // backend variable management shall only be available if component SystemInfo is present
        $variableBackendMgmtPermission = new \Cx\Core_Modules\Access\Model\Entity\Permission(array(), array(), true, array(), array(48),
            function() use ($cx) {
                return in_array('SystemInfo', $cx->getLicense()->getLegalComponentsList());
            }
        );

        return array(
            'Locale'        => array('permission' => $localeMgmtPermission),
            'Backend'       => array('permission' => $localeMgmtPermission),
            // Default is frontend
            'LanguageFile'  => array(
                'permission' => $variableMgmtPermission,
                'Backend' => array('permission' => $variableBackendMgmtPermission),
            ),
        );
    }

    /**
     * Return true here if you want the first tab to be an entity view
     * @return boolean True if overview should be shown, false otherwise
     */
    protected function showOverviewPage()
    {
        return false;
    }

    /**
     * Use this to parse your backend page
     *
     * You will get the template located in /View/Template/{CMD}.html
     * You can access Cx class using $this->cx
     * To show messages, use \Message class
     * @param \Cx\Core\Html\Sigma $template Template for current CMD
     * @param array $cmd CMD separated by slashes
     */
    public function parsePage(\Cx\Core\Html\Sigma $template, array $cmd, &$isSingle = false) {
        global $_ARRAYLANG;

        switch (current($cmd)) {
            case 'Backend':
                if (!empty($_POST)) {
                    \Permission::checkAccess(49, 'static');
                    $this->updateBackends($_POST);
                }
                // We don't want to parse the entity view
                $this->parseBackendSettings($template);
                return;
                break;
            case 'Locale':
                if (isset($_POST) && isset($_POST['updateLocales'])) {
                    \Permission::checkAccess(49, 'static');
                    $this->updateLocales($_POST);
                }
                $isEdit = false;
                parent::parsePage($template, $cmd, $isEdit);
                \JS::activate('cx');
                if ($isEdit) { //do not parse blocks and js in edit view
                    \JS::registerJS(substr($this->getDirectory(false, true) . '/View/Script/LocaleEdit.js', 1));
                    break;
                }
                // set js variables
                $cxjs = \ContrexxJavascript::getInstance();
                $cxjs->setVariable('copyTitle', $_ARRAYLANG['TXT_CORE_LOCALE_COPY_TITLE'], 'Locale/Locale');
                $cxjs->setVariable('copyText', $_ARRAYLANG['TXT_CORE_LOCALE_COPY_TEXT'], 'Locale/Locale');
                $cxjs->setVariable('copySuccess', $_ARRAYLANG['TXT_CORE_LOCALE_COPY_SUCCESS'], 'Locale/Locale');
                $cxjs->setVariable('linkTitle', $_ARRAYLANG['TXT_CORE_LOCALE_LINK_TITLE'], 'Locale/Locale');
                $cxjs->setVariable('linkText', $_ARRAYLANG['TXT_CORE_LOCALE_LINK_TEXT'], 'Locale/Locale');
                $cxjs->setVariable('linkSuccess', $_ARRAYLANG['TXT_CORE_LOCALE_LINK_SUCCESS'], 'Locale/Locale');
                $cxjs->setVariable('warningTitle', $_ARRAYLANG['TXT_CORE_LOCALE_WARNING_TITLE'], 'Locale/Locale');
                $cxjs->setVariable('warningText', $_ARRAYLANG['TXT_CORE_LOCALE_WARNING_TEXT'], 'Locale/Locale');
                $cxjs->setVariable('waitTitle', $_ARRAYLANG['TXT_CORE_LOCALE_WAIT_TITLE'], 'Locale/Locale');
                $cxjs->setVariable('waitText', $_ARRAYLANG['TXT_CORE_LOCALE_WAIT_TEXT'], 'Locale/Locale');
                $cxjs->setVariable('yesOption', $_ARRAYLANG['TXT_YES'], 'Locale/Locale');
                $cxjs->setVariable('noOption', $_ARRAYLANG['TXT_NO'], 'Locale/Locale');
                $cxjs->setVariable('langRemovalLabel', $_ARRAYLANG['TXT_CORE_LOCALE_LABEL_LANG_REMOVAL'], 'Locale/Locale');
                $cxjs->setVariable('langRemovalContent', $_ARRAYLANG['TXT_CORE_LOCALE_LANG_REMOVAL_CONTENT'], 'Locale/Locale');
                // register locale js
                \JS::registerJS(substr($this->getDirectory(false, true) . '/View/Script/Locale.js', 1));
                // register locale css
                \JS::registerCSS(substr($this->getDirectory(false, true) . '/View/Style/Locale.css', 1));
                // parse form around entity view
                if ($template->blockExists('form_tag_open') && $template->blockExists('form_tag_close')) {
                    $template->touchBlock('form_tag_open');
                    $template->touchBlock('form_tag_close');
                }
                // parse form actions
                if ($template->blockExists('form_actions')) {
                    $template->touchBlock('form_actions');
                }
                break;
            case 'LanguageFile':
                // activate cx and load neccessary js files
                \JS::activate('cx');
                // set js variables
                $cxjs = \ContrexxJavascript::getInstance();
                $cxjs->setVariable('resetText', $_ARRAYLANG['TXT_CORE_LOCALE_RESET'], 'Locale/LanguageFile');
                $cxjs->setVariable('resetSuccess', $_ARRAYLANG['TXT_CORE_LOCALE_LANGUAGEFILE_RESET_SUCCESS'], 'Locale/LanguageFile');
                $cxjs->setVariable('resetError', $_ARRAYLANG['TXT_CORE_LOCALE_LANGUAGEFILE_RESET_ERROR'], 'Locale/LanguageFile');

                // check which language file is wanted (front- or backend)
                $frontend = !in_array('Backend', $cmd);

                // load the language file's locale
                if (isset($_GET)) {
                    if (isset($_GET['sourceLang'])) {
                        // use language selected by user
                        $sourceLang = contrexx_input2raw($_GET['sourceLang']);
                    }
                    if (isset($_GET['destLang'])) {
                        // use language selected by user
                        $destLang = contrexx_input2raw($_GET['destLang']);
                    }
                }

                if (!isset($sourceLang) || !isset($destLang)) {
                    \Cx\Core\Setting\Controller\Setting::init('Config',null,'Yaml');
                    // use system's default locale
                    $languageId = $frontend ?
                        \Cx\Core\Setting\Controller\Setting::getValue('defaultLocaleId','Config') :
                        \Cx\Core\Setting\Controller\Setting::getValue('defaultLanguageId','Config');
                    if ($frontend) {
                        $languageCode = $this->getLocaleRepo()->find($languageId)->getSourceLanguage()->getIso1();
                    } else {
                        $languageCode = $this->cx->getDb()->getEntityManager()->find('Cx\Core\Locale\Model\Entity\Backend', $languageId)->getIso1();
                    }
                }
                if (!isset($sourceLang)) {
                    $sourceLang = $languageCode;
                }
                if (!isset($destLang)) {
                    $destLang = $languageCode;
                }
                $sourceLang = $this->getLanguageRepository()->find($sourceLang);
                $destLang = $this->getLanguageRepository()->find($destLang);

                // get requested component name
                if (isset($_GET['componentName'])) {
                    $componentName = $_GET['componentName'];
                } else {
                    $componentName = 'Core';
                }

                // verify that we are allowed to alter the language file
                // of the selected component
                if (!$this->isComponentCustomizable($componentName, $frontend ? $this->cx::MODE_FRONTEND : $this->cx::MODE_BACKEND)) {
                    break;
                }

                try {
                    // set language file by source language
                    $this->languageFile = new \Cx\Core\Locale\Model\Entity\SettingsLanguageFile(
                        $sourceLang,
                        $destLang,
                        $componentName,
                        $frontend,
                        false
                    );
                } catch (\Cx\Core\Locale\Model\Entity\LanguageFileException $e) {
                    \Message::add($e->getMessage(), \Message::CLASS_ERROR);
                } catch (\InitCMSException $e) {
                    \Message::add($e->getMessage(), \Message::CLASS_ERROR);
                    // set language file by default source language
                    $sourceLang = $this->getLanguageRepository()->find('en');
                    $this->languageFile = new \Cx\Core\Locale\Model\Entity\SettingsLanguageFile(
                        $sourceLang,
                        $destLang,
                        $componentName,
                        $frontend,
                        false
                    );
                }

                // check if user changed placeholders
                if (isset($_POST['placeholders'])) {
                    $this->updateLanguageFile($_POST['placeholders']);
                }

                // set entity class name (equal to identifier of LanguageFile)
                $entityClassName = 'Cx\Core\Locale\Model\Entity\SettingsLanguageFile';

                // parse view for language file (always single)
                $isSingle = false;
                $this->parseEntityClassPage($template, $entityClassName, $cmd, array(), $isSingle);
                break;
            default:
                parent::parsePage($template, $cmd);
                break;
        }
    }

    /**
     * Parses the localization configuration page for backend
     *
     * @param \Cx\Core\Html\Sigma $template Template for cmd Backend
     * @todo: Move setting of options array to getViewGeneratorOptions by giving DataSet an identifier
     */
    public function parseBackendSettings($template) {
        global $_ARRAYLANG;

        // register backend settings js file
        \JS::registerJS(substr($this->getDirectory(false, true) . '/View/Script/BackendSettings.js', 1));

        // register backend settings css file
        \JS::registerCSS(substr($this->getDirectory(false, true) . '/View/Style/BackendSettings.css', 1));

        $allowModification = \Permission::checkAccess(49, 'static', true);

        // simulate entity for view generator
        $simulatedEntity = array(
            1 => array(
                'active' => '',
                'default' => '',
            )
        );
        // set to edit mode
        $_GET['editid'] = '{0,1}';
        // get dataset of the simulated entity
        $dataSet = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($simulatedEntity);
        // set view generator options
        $options = array(
            'array' => array(
                'entityName' => $_ARRAYLANG['TXT_CORE_LOCALE_BACKEND_NAME'],
                'fields' => array(
                    'active' => array(
                        'header' => $_ARRAYLANG['TXT_CORE_LOCALE_ACTIVE_LANGUAGES'],
                        'readonly' => !$allowModification,
                        'formfield' => function($fieldname, $fieldtype, $fieldlength, $fieldvalue, $fieldoptions) use ($allowModification) {
                            global $_ARRAYLANG;

                            $em = $this->cx->getDb()->getEntityManager();
                            // get source languages from repository
                            $languageRepo = $em->getRepository('Cx\Core\Locale\Model\Entity\Language');
                            $criteria = array('source' => true);
                            $sourceLanguages = $languageRepo->findBy($criteria);

                            // build select for active languages
                            $select = new \Cx\Core\Html\Model\Entity\DataElement(
                                'activeLanguages[]',
                                '',
                                \Cx\Core\Html\Model\Entity\DataElement::TYPE_SELECT
                            );
                            if (!$allowModification) {
                                $select->setAttribute('disabled');
                            }
                            $select->setAttribute('multiple');
                            $select->setAttribute('data-placeholder', $_ARRAYLANG['TXT_CORE_LOCALE_BACKEND_SELECT_ACTIVE_LANGUAGES']);
                            // build options for select with source languages
                            foreach ($sourceLanguages as $language) {
                                $option = new \Cx\Core\Html\Model\Entity\HtmlElement('option');
                                $option->setAttribute('value', $language->getIso1());
                                $option->addChild(new \Cx\Core\Html\Model\Entity\TextElement($language));
                                // check if language is already active
                                if ($language->getBackend()) {
                                    $option->setAttribute('selected');
                                }
                                $select->addChild($option);
                            }
                            return $select;
                        }
                    ),
                    'default' => array(
                        'header' => $_ARRAYLANG['TXT_CORE_LOCALE_DEFAULT_LANGUAGE'],
                        'readonly' => !$allowModification,
                        'formfield' => function($fieldname, $fieldtype, $fieldlength, $fieldvalue, $fieldoptions) use ($allowModification) {
                            global $_CONFIG;

                            $em = $this->cx->getDb()->getEntityManager();
                            // get already active backend languages
                            $backendRepo = $em->getRepository('Cx\Core\Locale\Model\Entity\Backend');

                            if (!$allowModification) {
                                return $backendRepo->findOneById($_CONFIG['defaultLanguageId'])->getIso1();
                            }

                            $backendLanguages = $backendRepo->findAll();

                            // build select for default language
                            $select = new \Cx\Core\Html\Model\Entity\DataElement(
                                'defaultLanguage',
                                '',
                                \Cx\Core\Html\Model\Entity\DataElement::TYPE_SELECT
                            );
                            foreach($backendLanguages as $backendLanguage) {
                                $option = new \Cx\Core\Html\Model\Entity\HtmlElement('option');
                                $option->setAttribute('value', $backendLanguage->getId());
                                $option->addChild(new \Cx\Core\Html\Model\Entity\TextElement($backendLanguage->getIso1()));
                                if ($backendLanguage->getId() == $_CONFIG['defaultLanguageId']) {
                                    $option->setAttribute('selected');
                                }
                                $select->addChild($option);
                            }
                            return $select;
                        }
                    ),
                ),
                'functions' => array(
                    'edit' => $allowModification,
                    'formButtons' => $allowModification,
                ),
            ),
        );
        $view = new \Cx\Core\Html\Controller\ViewGenerator(
            $dataSet,
            $options
        );
        $template->setVariable('ENTITY_VIEW', $view->render());
    }

    /**
     * This function returns the ViewGeneration options for a given entityClass
     *
     * @access protected
     * @global $_ARRAYLANG
     * @global $_CONFIG
     * @param $entityClassName contains the FQCN from entity
     * @param $dataSetIdentifier if $entityClassName is DataSet, this is used for better partition
     * @return array with options
     */
    protected function getViewGeneratorOptions($entityClassName, $dataSetIdentifier = '') {
        global $_ARRAYLANG;

        $classNameParts = explode('\\', $entityClassName);
        $classIdentifier = end($classNameParts);

        $langVarName = 'TXT_' . strtoupper($this->getType() . '_' . $this->getName() . '_ACT_' . $classIdentifier);
        if (isset($_ARRAYLANG[$langVarName])) {
            $header = $_ARRAYLANG[$langVarName];
        } else {
            $header = $_ARRAYLANG['TXT_CORE_LOCALE_ACT_DEFAULT'];
        }

        $allowModification = \Permission::checkAccess(49, 'static', true);

        switch ($entityClassName) {
            case 'Cx\Core\Locale\Model\Entity\Locale':
                if (!isset($_GET['order'])) {
                    $_GET['order'] = 'id';
                }

                $showAddButton = $allowModification;
                if (
                    $allowModification &&
                    \Cx\Core\Setting\Controller\Setting::getValue(
                        'useVirtualLanguageDirectories',
                        'Config'
                    ) === 'off'
                ) {
                    $showAddButton = false;
                    $languageData  = \Env::get('init')->getComponentSpecificLanguageData(
                        'Config',
                        false
                    );
                    $textElement   = $_ARRAYLANG['TXT_ADMINISTRATION'] . ' > '
                        . $languageData['TXT_SYSTEM_SETTINGS'] . ' > '
                        . $languageData['TXT_SETTINGS_MENU_SYSTEM'] . ' > '
                        . $languageData['TXT_CORE_CONFIG_SITE'];
                    // Set anchor tag to the text
                    $link = new \Cx\Core\Html\Model\Entity\HtmlElement('a');
                    $link->setAttribute(
                        'href',
                        \Cx\Core\Routing\Url::fromBackend('Config')
                    );
                    $link->addChild(new \Cx\Core\Html\Model\Entity\TextElement($textElement));

                    // Set strong tag to the text
                    $strongText = new \Cx\Core\Html\Model\Entity\HtmlElement('strong');
                    $strongText->addChild(
                        new \Cx\Core\Html\Model\Entity\TextElement(
                            $languageData['TXT_CORE_CONFIG_USEVIRTUALLANGUAGEDIRECTORIES']
                        )
                    );
                    \Message::information(sprintf(
                        $_ARRAYLANG['TXT_CORE_LOCALE_ADD_NEW_INFORMATION'],
                        // %1$s
                        $strongText,
                        // %2$s
                        $link
                    ));
                }

                return array(
                    'entityName' => $_ARRAYLANG['TXT_CORE_LOCALE_LOCALE_NAME'],
                    'header' => $_ARRAYLANG['TXT_CORE_LOCALE_ACT_LOCALE'],
                    'fields' => array(
                        'id' => array(
                            'header' => $_ARRAYLANG['TXT_CORE_LOCALE_FIELD_ID'],
                            'table' => array(
                                'attributes' => array(
                                    'class' => 'localeId',
                                ),
                            ),
                        ),
                        'iso1' => array(
                            'header' => $_ARRAYLANG['TXT_CORE_LOCALE_FIELD_ISO1'],
                        ),
                        'label' => array(
                            'header' => $_ARRAYLANG['TXT_CORE_LOCALE_FIELD_LABEL'],
                            'table' => array(
                                'attributes' => array(
                                    'class' => 'localeLabel',
                                ),
                            ),
                        ),
                        'orderNo' => array(
                            'showOverview' => false,
                            'showDetail' => false,
                        ),
                        'country' => array(
                            'header' => $_ARRAYLANG['TXT_CORE_LOCALE_FIELD_COUNTRY'],
                        ),
                        'default' => array(
                            'header' => $_ARRAYLANG['TXT_CORE_LOCALE_FIELD_DEFAULT'],
                            'type' => 'radio',
                            'table' => array(
                                'attributes' => array(
                                    'class' => 'localeDefault',
                                ),
                                'parse' => function ($value, $rowData) use ($allowModification) {
                                    global $_CONFIG, $_ARRAYLANG;

                                    if (!$allowModification) {
                                        return $rowData['id'] == $_CONFIG['defaultLocaleId'] ? $_ARRAYLANG['TXT_CORE_LOCALE_FIELD_DEFAULT'] : '';
                                    }

                                    $radioButton = new \Cx\Core\Html\Model\Entity\DataElement('langDefaultStatus', $rowData['id'], 'input');
                                    $radioButton->setAttribute('type', 'radio');
                                    $radioButton->setAttribute('onchange', 'updateCurrent()');
                                    $radioButton->setAttribute('form', 'localeLocaleList');
                                    if ($rowData['id'] == $_CONFIG['defaultLocaleId']) {
                                        $radioButton->setAttribute('checked', 'checked');
                                    }
                                    return $radioButton;
                                },
                            ),
                            'readonly' => !$allowModification,
                        ),
                        'fallback' => array(
                            'header' => $_ARRAYLANG['TXT_CORE_LOCALE_FIELD_FALLBACK'],
                            'tooltip' => $_ARRAYLANG['TXT_CORE_LOCALE_FALLBACK_TOOLTIP'],
                            'table' => array(
                                'attributes' => array(
                                    'class' => 'localeFallback',
                                ),
                                'parse' => function ($value, $rowData) use ($allowModification) {
                                    global $_ARRAYLANG;

                                    if (!$allowModification) {
                                        return is_object($value) ? $value->getLabel() : $_ARRAYLANG['TXT_CORE_NONE'];
                                    }

                                    $selectedVal = is_object($value) ? $value->getId() : 'NULL';
                                    $locales = $this->getLocaleRepo()->findAll();
                                    // build select for fallbacks
                                    $select = new \Cx\Core\Html\Model\Entity\DataElement(
                                        'fallback[' . $rowData['id'] . ']',
                                        '',
                                        \Cx\Core\Html\Model\Entity\DataElement::TYPE_SELECT
                                    );
                                    $select->setAttribute('form', 'localeLocaleList');
                                    $fallbackOptions = array(
                                        'NULL' => $_ARRAYLANG['TXT_CORE_NONE'],
                                    );
                                    foreach($locales as $locale) {
                                        $fallbackOptions[$locale->getId()] = $locale->getLabel();
                                    }
                                    foreach($fallbackOptions as $optValue => $optText) {
                                        $option = new \Cx\Core\Html\Model\Entity\HtmlElement('option');
                                        $option->setAttribute('value', $optValue);
                                        $option->addChild(new \Cx\Core\Html\Model\Entity\TextElement($optText));
                                        if ($optValue == $selectedVal) {
                                            $option->setAttribute('selected');
                                        }
                                        $select->addChild($option);
                                    }
                                    return $select;
                                },
                            ),
                        ),
                        'sourceLanguage' => array(
                            'header' => $_ARRAYLANG['TXT_CORE_LOCALE_FIELD_SOURCE_LANGUAGE'],
                            'tooltip' => $_ARRAYLANG['TXT_CORE_LOCALE_SOURCE_LANGUAGE_TOOLTIP'],
                            'formfield' => function($fieldname, $fieldtype, $fieldlength, $fieldvalue, $fieldoptions) {
                                // build select for sourceLanguage
                                $select = new \Cx\Core\Html\Model\Entity\DataElement(
                                    $fieldname,
                                    '',
                                    \Cx\Core\Html\Model\Entity\DataElement::TYPE_SELECT
                                );
                                $em = $this->cx->getDb()->getEntityManager();
                                $criteria = array('source' => true);
                                $sourceLangs = $em->getRepository('Cx\Core\Locale\Model\Entity\Language')->findBy($criteria);
                                foreach ($sourceLangs as $lang) {
                                    $option = new \Cx\Core\Html\Model\Entity\HtmlElement('option');
                                    $option->setAttribute('value', $lang->getIso1());
                                    $option->addChild(new \Cx\Core\Html\Model\Entity\TextElement($lang));
                                    if ($fieldvalue == $lang) {
                                        $option->setAttribute('selected');
                                    }
                                    $select->addChild($option);
                                }
                                return $select;
                            }
                        ),
                        'locales' => array(
                            'showOverview' => false,
                            'showDetail' => false,
                        ),
                        'frontends' => array(
                            'showOverview' => false,
                            'showDetail' => false,
                        ),
                    ),
                    'functions' => array(
                        'add' => $showAddButton,
                        'edit' => $allowModification,
                        'delete' => $allowModification,
                        'actions' => !$allowModification ? null : function($rowData) {
                            global $_ARRAYLANG;
                            // parse copy/link functionality only for locales with fallback
                            if (!$rowData['fallback']) {
                                return '';
                            }
                            // add copy link
                            $copyLink = new \Cx\Core\Html\Model\Entity\HtmlElement('a');
                            $copyAttrs = array(
                                'class' => 'copyLink',
                                'href' => 'javascript:copyPages(\'' . $rowData['id'] . '\')',
                                'title' => $_ARRAYLANG['TXT_CORE_LOCALE_ACTION_COPY']
                            );
                            $copyLink->setAttributes($copyAttrs);
                            // add image to link
                            $copyImg = new \Cx\Core\Html\Model\Entity\HtmlElement('img');
                            $copyImgAttrs = array(
                                'src' => '../core/Core/View/Media/icons/copy.gif',
                                'alt' => $_ARRAYLANG['TXT_CORE_LOCALE_ACTION_COPY']
                            );
                            $copyImg->setAttributes($copyImgAttrs);
                            $copyLink->addChild($copyImg);
                            // add linking link
                            $linkLink = new \Cx\Core\Html\Model\Entity\HtmlElement('a');
                            $linkAttrs = array(
                                'class' => 'linkLink',
                                'href' => 'javascript:linkPages(\'' . $rowData['id'] . '\')',
                                'title' => $_ARRAYLANG['TXT_CORE_LOCALE_ACTION_LINK']
                            );
                            $linkLink->setAttributes($linkAttrs);
                            // add image to link
                            $linkImg = new \Cx\Core\Html\Model\Entity\HtmlElement('img');
                            $linkImgAttrs = array(
                                'src' => '../core/Core/View/Media/icons/linkcopy.gif',
                                'alt' => $_ARRAYLANG['TXT_CORE_LOCALE_ACTION_LINK']
                            );
                            $linkImg->setAttributes($linkImgAttrs);
                            $linkLink->addChild($linkImg);
                            return $copyLink . $linkLink;
                        },
                        'sorting'   => true,
                        'sortBy' => !$allowModification ? null : array(
                            'field' => array('orderNo' => SORT_ASC),
                        ),
                        'paging' => false,
                        'filtering' => false,
                    ),
                    'order' => array(
                        'overview' => array(
                            'orderNo',
                            'id',
                            'label',
                            'iso1',
                            'country',
                            'default',
                            'fallback',
                        ),
                        'form' => array(
                            'id',
                            'iso1',
                            'country',
                            'label',
                            'fallback',
                        ),
                    ),
                );
                break;
            case 'Cx\Core_Modules\Listing\Model\Entity\DataSet':
                    return array(
                        $dataSetIdentifier => array(
                            'entityName' => $_ARRAYLANG['TXT_CORE_LOCALE_LANGUAGEFILE_NAME'],
                            'header' => $_ARRAYLANG['TXT_CORE_LOCALE_ACT_LANGUAGEFILE'],
                            'fields' => array(
                                'id' => array(
                                    'filterOptionsField' => function(
                                        $parseObject,
                                        $fieldName,
                                        $elementName
                                    ) {
                                        return $this->createComponentSelect(
                                            'componentName',
                                            $this->languageFile->getComponentName(),
                                            $this->languageFile->getMode()
                                        );
                                    },
                                ),
                                'sourceLang' => array(
                                    'filterOptionsField' => function(
                                        $parseObject,
                                        $fieldName,
                                        $elementName
                                    ) {
                                        return $this->createLanguageSelect(
                                            'sourceLang',
                                            $this->languageFile->getLanguage()->getIso1()
                                        );
                                    },
                                ),
                                'destLang' => array(
                                    'filterOptionsField' => function(
                                        $parseObject,
                                        $fieldName,
                                        $elementName
                                    ) {
                                        return $this->createLanguageSelect(
                                            'destLang',
                                            $this->languageFile->getDestLang()->getIso1()
                                        );
                                    },
                                    'table' => array(
                                        'parse' => function ($value, $rowData) {
                                            $input = new \Cx\Core\Html\Model\Entity\DataElement(
                                                'placeholders[' . $rowData['id'] . ']',
                                                $value
                                            );
                                            $input->addClass('placeholder');
                                            $input->setAttribute('form', 'languageFileSave');
                                            $input->setAttribute('size', '100');
                                            $input->setAttribute(
                                                'onchange',
                                                'var resetFunc = cx.jQuery(this).closest(\'tr\').find(\'a\');if (resetFunc.data(\'init\') != cx.jQuery(this).val()) {resetFunc.show();}'
                                            );
                                            return $input;
                                        },
                                    ),
                                ),
                                'initData' => array(
                                    'showOverview' => false,
                                    'allowFiltering' => false,
                                ),
                            ),
                            'functions' => array(
                                'add' => false,
                                'edit' => false,
                                'delete' => false,
                                'sorting' => false,
                                'paging' => true,
                                'searching' => true,
                                'filtering' => true,
                                'autoHideFiltering' => false,
                                'actions' => function($rowData) {
                                    global $_ARRAYLANG;
                                    $resetLink = new \Cx\Core\Html\Model\Entity\HtmlElement(
                                        'a'
                                    );
                                    $resetLink->setAttribute('src', '#');
                                    $resetLink->setAttribute(
                                        'data-init',
                                        $rowData['initData']
                                    );
                                    $resetLink->setAttribute(
                                        'onclick',
                                        'cx.jQuery(this).closest(\'tr\').find(\'.placeholder\').val(
                                            cx.jQuery(this).data(\'init\')
                                        );cx.jQuery(this).hide();cx.ui.messages.add(\'' . $_ARRAYLANG['TXT_CORE_LOCALE_UNSAVED_CHANGES'] . '\');'
                                    );
                                    $resetLink->setAttribute(
                                        'title',
                                        $_ARRAYLANG['TXT_CORE_LOCALE_RESET']
                                    );
                                    if ($rowData['initData'] == $rowData['destLang']) {
                                        $resetLink->setAttribute(
                                            'style',
                                            'display:none;'
                                        );
                                    }
                                    $resetImg = new \Cx\Core\Html\Model\Entity\HtmlElement(
                                        'img'
                                    );
                                    $resetImg->setAttribute(
                                        'src',
                                        '../core/Core/View/Media/icons/reset.png'
                                    );
                                    $resetLink->addChild($resetImg);
                                    return $resetLink;
                                }
                            ),
                        ),
                    );
                break;
            default:
                return array(
                    'header' => $header,
                    'functions' => array(
                        'add' => $allowModification,
                        'edit' => $allowModification,
                        'delete' => $allowModification,
                        'sorting' => true,
                        'paging' => true,
                        'filtering' => false,
                    ),
                );
        }
    }

    /**
     * Returns the object to parse a view with
     *
     * Returns a LanguageFile object for language file view
     *
     * @return string|array|object An entity class name, entity, array of entities or DataSet
     */
    protected function getViewGeneratorParseObjectForEntityClass($entityClassName) {
        switch ($entityClassName) {
            case 'Cx\Core\Locale\Model\Entity\Locale':
                $em = $this->cx->getDb()->getEntityManager();
                $parseObject = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($this->getLocaleRepo()->findAll());
                foreach ($parseObject as $index => $value) {
                    $parseObject->add($index, array('default' => false));
                }
                return $parseObject;
                break;
            case 'Cx\Core\Locale\Model\Entity\SettingsLanguageFile':
                return $this->languageFile;
            break;
            default:
                return $entityClassName;
            break;
        }
    }

    /**
     * Returns all entities of this component which can have an auto-generated view
     *
     * Adds DataSet to the entity classes with view, which is neccessary
     * to auto-generate the language file view
     *
     * @access protected
     * @return array
     */
    protected function getEntityClassesWithView() {
        $entityClasses = parent::getEntityClassesWithView();
        $entityClasses[] = 'Cx\Core_Modules\Listing\Model\Entity\DataSet';
        return $entityClasses;
    }

    /**
     * Updates the locales
     *
     * Changes the default locale (when neccessary)
     * and updates the fallbacks
     *
     * @param array $post The post data
     */
    protected function updateLocales($post) {
        // check if default locale has changed
        if (
            isset($post['langDefaultStatus']) &&
            \Cx\Core\Setting\Controller\Setting::set(
                'defaultLocaleId',
                intval($post['langDefaultStatus'])
            )
        ) {
            \Cx\Core\Setting\Controller\Setting::update('defaultLocaleId');
        }
        // update fallbacks
        if (!isset($post['fallback'])) {
            return;
        }
        $em = $this->cx->getDb()->getEntityManager();
        $localeRepo = $this->getLocaleRepo();
        foreach ($post['fallback'] as $localeId => $fallbackId) {
            $locale = $localeRepo->find($localeId);
            $fallback = $localeRepo->find($fallbackId);
            $locale->setFallback($fallback);
            $em->persist($locale);
        }
        $em->flush();
    }

    /**
     * Updates the backend languages
     *
     * Changes the default backend language (when neccessary)
     * and adds or/and deletes backend languages
     *
     * @param $post The post data
     * @todo: Add param with entity Language to em->clear after doctrine update
     */
    protected function updateBackends($post) {
        global $_ARRAYLANG;

        if (!isset($post['activeLanguages'])) {
            return;
        }
        $em = $this->cx->getDb()->getEntityManager();
        $backendRepo = $em->getRepository('Cx\Core\Locale\Model\Entity\Backend');
        $langRepo = $em->getRepository('Cx\Core\Locale\Model\Entity\Language');
        // add or/and delete backend languages
        foreach ($post['activeLanguages'] as $activeLanguage) {
            // check if backend entity already exists
            if ($backendRepo->findOneBy(array('iso1' => $activeLanguage))) {
                continue;
            }
            $language = $langRepo->find($activeLanguage);
            $newBackend = new \Cx\Core\Locale\Model\Entity\Backend();
            $newBackend->setIso1($language);
            $em->persist($newBackend);
            // set backend in language entity to show changes instantly
            $language->setBackend($newBackend);
        }
        // check if a backend needs to be deleted
        foreach ($backendRepo->findAll() as $backend) {
            if (
                in_array(
                    $backend->getIso1()->getIso1(),
                    $post['activeLanguages']
                )
            ) {
                continue;
            }
            // delete backend language
            if ($backend->getId() == $post['defaultLanguage']) {
                \Message::add(
                    sprintf(
                        $_ARRAYLANG['TXT_CORE_LOCALE_CANNOT_DELETE_DEFAULT_BACKEND'],
                        $backend
                    )
                );
                continue;
            }
            $em->remove($backend);
        }
        $em->flush();
        $em->clear();

        // check if default language has changed and still exists
        if (
            isset($post['defaultLanguage']) &&
            $backendRepo->find($post['defaultLanguage']) &&
            \Cx\Core\Setting\Controller\Setting::set(
                'defaultLanguageId',
                intval($post['defaultLanguage'])
            )
        ) {
            \Cx\Core\Setting\Controller\Setting::update('defaultLanguageId');
        }
    }

    /**
     * Compares the placeholders from post to the current set placeholders
     * and stores the effectively changed ones
     * @param array $placeholders The placeholders submitted by the user
     */
    protected function updateLanguageFile($placeholders) {
        global $_ARRAYLANG;

        // get old placeholder values
        $init = \Env::get('init');
        $basePlaceholders = $init->getComponentSpecificLanguageDataByCode(
            $this->languageFile->getComponentName(),
            $this->languageFile->getMode() == 'frontend',
            'en',
            false
        );
        $oldPlaceholders = array();
        try {
            $oldPlaceholders = $init->getComponentSpecificLanguageDataByCode(
                $this->languageFile->getComponentName(),
                $this->languageFile->getMode() == 'frontend',
                $this->languageFile->getDestLang()->getIso1(),
                false
            );
        } catch (\InitCMSException $e) {}
        foreach ($basePlaceholders as $name=>$value) {
            if (!isset($oldPlaceholders[$name])) {
                $oldPlaceholders[$name] = $value;
            }
        }

        // check for changed values
        foreach ($placeholders as $name => $value) {
            // ignore line breaks
            $oldValue = str_replace(array("\r", "\n"), '', $oldPlaceholders[$name]);
            $newValue = str_replace(array("\r", "\n"), '', $value);
            if ($oldValue == $newValue) {
                // not changed, remove it if it exists
                $this->languageFile->removePlaceholder($name, $oldPlaceholders[$name]);
                continue;
            }
            // add changed placeholders to language file
            $placeholder = new \Cx\Core\Locale\Model\Entity\Placeholder($name, $value);
            $this->languageFile->addPlaceholder($placeholder);
        }

        // store changed values to the yaml file
        if ($this->languageFile->getPlaceholders()) {
            $this->languageFile->save();
            //inform user about success
            \Message::add(
                $_ARRAYLANG['TXT_CORE_LOCALE_LANGUAGEFILE_SUCCESSFULLY_UPDATED'],
                \Message::CLASS_OK
            );
        } else {
            // no changed placeholder, delete the file (if any)
            $this->languageFile->delete();
            \Message::add(
                $_ARRAYLANG['TXT_CORE_LOCALE_LANGUAGEFILE_NOTHING_CHANGED'],
                \Message::CLASS_INFO
            );
        }

        // drop page and ESI cache
        $this->getComponent('Cache')->clearCache();
    }

    /**
     * Creates a select field with all source languages as options
     * @param string $name Name of the select field
     * @param string $selectedValue Pre-selected value
     * @return \Cx\Core\Html\Model\Entity\DataElement Select field
     */
    protected function createLanguageSelect($name, $selectedValue) {
        // load all source languages
        $em = $this->cx->getDb()->getEntityManager();
        $languageRepo = $em->getRepository('Cx\Core\Locale\Model\Entity\Language');
        $languages = $languageRepo->findBy(
            array('source' => true)
        );

        $validData = array();
        foreach($languages as $language) {
            $validData[$language->getIso1()] = (string) $language;
        }
        return $this->createSelect($name, $validData, $selectedValue);
    }

    /**
     * Creates a select field with all componentss as options
     * @param string $name Name of the select field
     * @param string $selectedValue Pre-selected value
     * @return \Cx\Core\Html\Model\Entity\DataElement Select field
     */
    protected function createComponentSelect($name, $selectedValue, $mode) {
        $em = $this->cx->getDb()->getEntityManager();
        $query = 'SELECT `name` FROM '.DBPREFIX.'component ORDER BY name ASC';
        $stmt = $em->getConnection()->prepare($query);
        $stmt->execute();

        $validData = array();
        foreach($stmt->fetchAll() as $component) {
            // skip components with no language files
            if (!$this->isComponentCustomizable($component['name'], $mode)) {
                continue;
            }
            $componentCtrl = $this->cx->getComponent($component['name']);
            if (!$componentCtrl || !$componentCtrl->isActive()) {
                continue;
            }
            // custom hack for Media1, Media2, Media3, Media4
            if ($component['name'] == 'Media1') {
                $component['name'] = 'Media';
            }
            $validData[$component['name']] = $component['name'];
        }
        return $this->createSelect($name, $validData, $selectedValue);
    }

    /**
     * Creates a select field with the given values as options
     * @param string $name Name of the select field
     * @param array $options Key=>value type array
     * @param string $selectedValue Pre-selected value
     * @return \Cx\Core\Html\Model\Entity\DataElement Select field
     */
    protected function createSelect($name, $options, $selectedValue) {
        // build html select
        $select = new \Cx\Core\Html\Model\Entity\DataElement(
            $name,
            $selectedValue,
            \Cx\Core\Html\Model\Entity\DataElement::TYPE_SELECT,
            null,
            $options
        );
        $select->setAttribute('form', 'vg-0-searchForm');
        $select->addClass('vg-searchSubmit');
        return $select;
    }

    /**
     * Gets the locale repository from the entity manager
     * @return \Cx\Core\Locale\Model\Repository\LocaleRepository
     */
    protected function getLocaleRepo() {
        // return directly if locale repo is already set
        if (isset($this->localeRepo)) {
            return $this->localeRepo;
        }
        // load locale repo from entity manager
        $em = $this->cx->getDb()->getEntityManager();
        $this->localeRepo = $em->getRepository('Cx\Core\Locale\Model\Entity\Locale');
        return $this->localeRepo;
    }

    /**
     * Gets the language repository from the entity manager
     * @return \Cx\Core\Locale\Model\Repository\LanguageRepository
     */
    protected function getLanguageRepository() {
        // return directly if language repo is already set
        if (isset($this->languageRepo)) {
            return $this->languageRepo;
        }
        // load language repo from entity manager
        $em = $this->cx->getDb()->getEntityManager();
        $this->languageRepo = $em->getRepository('Cx\Core\Locale\Model\Entity\Language');
        return $this->languageRepo;
    }

    protected function isComponentCustomizable($component, $mode) {
        // TODO: this should be done dynamically
        if ($mode == $this->cx::MODE_FRONTEND) {
            $skipList = array(
                'Agb',
                'Alias',
                'Cache',
                'Captcha',
                'ComponentManager',
                'Config',
                'ContentManager',
                'ContentWorkflow',
                'Country',
                'Crm',
                'Cron',
                'Csrf',
                'DataAccess',
                'DataSource',
                'DateTime',
                'Error',
                'GeoIp',
                'Home',
                'Html',
                'Ids',
                'Imprint',
                'JavaScript',
                'JsonData',
                'LanguageManager',
                'License',
                'LinkManager',
                'Locale',
                'Media2',
                'Media3',
                'Media4',
                'MediaBrowser',
                'MediaSource',
                'Message',
                'Model',
                'MultiSite',
                'Net',
                'NetManager',
                'NetTools',
                'Order',
                'Pdf',
                'Pim',
                'Routing',
                'Security',
                'Session',
                'Setting',
                'Shell',
                'Sitemap',
                'Stats',
                'Support',
                'Sync',
                'SysLog',
                'SystemInfo',
                'SystemLog',
                'TemplateEditor',
                'Test',
                'User',
                'View',
                'ViewManager',
                'Widget',
                'Workbench',
            );
        } else {
            $skipList = array(
                'Agb',
                'Captcha',
                'ContentManager',
                'Country',
                'Csrf',
                'DataAccess',
                'DataSource',
                'DateTime',
                'Error',
                'FrontendEditing',
                'Home',
                'Ids',
                'Imprint',
                'JavaScript',
                'JsonData',
                'License',
                'Media2',
                'Media3',
                'Media4',
                'MediaSource',
                'Message',
                'Model',
                'Net',
                'Pim',
                'Privacy',
                'Security',
                'Session',
                'Setting',
                'Shell',
                'Sitemap',
                'Sync',
                'SysLog',
                'Test',
                'User',
                'View',
                'Widget',
                'Workbench',
            );
        }
        return !in_array($component, $skipList);
    }
}
