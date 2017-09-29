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
     * Returns a list of available commands (?act=XY)
     * @return array List of acts
     */
    public function getCommands()
    {
        return array('Locale', 'Backend');
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
    public function parsePage(\Cx\Core\Html\Sigma $template, array $cmd) {
        global $_ARRAYLANG;

        switch (current($cmd)) {
            case 'Backend':
                if (!empty($_POST)) {
                    $this->updateBackends($_POST);
                }
                // We don't want to parse the entity view
                $this->parseBackendSettings($template);
                return;
                break;
            case 'Locale':
                if (isset($_POST) && isset($_POST['updateLocales'])) {
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
                        'formfield' => function($fieldname, $fieldtype, $fieldlength, $fieldvalue, $fieldoptions) {
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
                        'formfield' => function($fieldname, $fieldtype, $fieldlength, $fieldvalue, $fieldoptions) {
                            global $_CONFIG;

                            $em = $this->cx->getDb()->getEntityManager();
                            // get already active backend languages
                            $backendRepo = $em->getRepository('Cx\Core\Locale\Model\Entity\Backend');
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
     * @return array with options
     */
    protected function getViewGeneratorOptions($entityClassName)
    {
        global $_ARRAYLANG;

        $classNameParts = explode('\\', $entityClassName);
        $classIdentifier = end($classNameParts);

        $langVarName = 'TXT_' . strtoupper($this->getType() . '_' . $this->getName() . '_ACT_' . $classIdentifier);
        if (isset($_ARRAYLANG[$langVarName])) {
            $header = $_ARRAYLANG[$langVarName];
        } else {
            $header = $_ARRAYLANG['TXT_CORE_LOCALE_ACT_DEFAULT'];
        }

        switch ($entityClassName) {
            case 'Cx\Core\Locale\Model\Entity\Locale':
                if (!isset($_GET['order'])) {
                    $_GET['order'] = 'id';
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
                                'parse' => function ($value, $rowData) {
                                    global $_CONFIG;
                                    $radioButton = new \Cx\Core\Html\Model\Entity\DataElement('langDefaultStatus', $rowData['id'], 'input');
                                    $radioButton->setAttribute('type', 'radio');
                                    $radioButton->setAttribute('onchange', 'updateCurrent()');
                                    if ($rowData['id'] == $_CONFIG['defaultLocaleId']) {
                                        $radioButton->setAttribute('checked', 'checked');
                                    }
                                    return $radioButton;
                                },
                            ),
                        ),
                        'fallback' => array(
                            'header' => $_ARRAYLANG['TXT_CORE_LOCALE_FIELD_FALLBACK'],
                            'tooltip' => $_ARRAYLANG['TXT_CORE_LOCALE_FALLBACK_TOOLTIP'],
                            'table' => array(
                                'attributes' => array(
                                    'class' => 'localeFallback',
                                ),
                                'parse' => function ($value, $rowData) {
                                    global $_ARRAYLANG;
                                    $selectedVal = is_object($value) ? $value->getId() : 'NULL';
                                    $em = $this->cx->getDb()->getEntityManager();
                                    $localeRepo = $em->getRepository('Cx\Core\Locale\Model\Entity\Locale');
                                    $locales = $localeRepo->findAll();
                                    // build select for fallbacks
                                    $select = new \Cx\Core\Html\Model\Entity\DataElement(
                                        'fallback[' . $rowData['id'] . ']',
                                        '',
                                        \Cx\Core\Html\Model\Entity\DataElement::TYPE_SELECT
                                    );
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
                        'add' => true,
                        'edit' => true,
                        'delete' => true,
                        'actions' => function($rowData) {
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
                        'sortBy' => array(
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
            default:
                return array(
                    'header' => $header,
                    'functions' => array(
                        'add' => true,
                        'edit' => true,
                        'delete' => true,
                        'sorting' => true,
                        'paging' => true,
                        'filtering' => false,
                    ),
                );
        }
    }

    /**
     * Returns the object to parse a wiew with
     *
     * If you overwrite this and return anything else than string, filter will not work
     * @return string|array|object An entity class name, entity, array of entities or DataSet
     */
    protected function getViewGeneratorParseObjectForEntityClass($entityClassName) {
        if ($entityClassName == 'Cx\Core\Locale\Model\Entity\Locale') {
            $em = $this->cx->getDb()->getEntityManager();
            $localeRepo = $em->getRepository($entityClassName);
            $parseObject = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($localeRepo->findAll());
            foreach ($parseObject as $index => $value) {
                $parseObject->add($index, array('default' => false));
            }
            return $parseObject;
        }
        return $entityClassName;
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
        $localeRepo = $em->getRepository('Cx\Core\Locale\Model\Entity\Locale');
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
}
