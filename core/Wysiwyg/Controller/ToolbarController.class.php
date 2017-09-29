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
 * Specific ToolbarContoller for this Component.
 *
 * Use this to easily create a toolbar configurator
 * @copyright   Cloudrexx AG
 * @author      Nick Brönnimann <nick.broennimann@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_wysiwyg
 * @version     1.0.0
 */

namespace Cx\Core\Wysiwyg\Controller;

/**
 * Specific ToolbarContoller for this Component.
 *
 * Use this to easily create a toolbar configurator
 * @copyright   Cloudrexx AG
 * @author      Nick Brönnimann <nick.broennimann@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_wysiwyg
 * @version     1.0.0
 */
class ToolbarController extends \Cx\Core\Core\Model\Entity\Controller {
    /**
     * This is the default toolbar for either full or small
     * @var     array       $defaultFull
     * @access  protected
     */
    protected $defaultFull = array(
        array('Source','-','NewPage','Templates'),
        array('Cut','Copy','Paste','PasteText','PasteFromWord','-','Scayt'),
        array('Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'),
        array('Bold','Italic','Underline','Strike','-','Subscript','Superscript'),
        array('NumberedList','BulletedList','-','Outdent','Indent', 'Blockquote'),
        array('JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'),
        array('Link','Unlink','Anchor'),
        array('Image','Flash','Table','HorizontalRule','SpecialChar'),
        array('Format'),
        array('TextColor','BGColor'),
        array('ShowBlocks'),
        array('Maximize'),
        array('Div','CreateDiv'),
    );

    /**
     * This is the default toolbar for Bbcode
     * @var     array       $defaultBbcode
     * @access  protected
     */
    protected $defaultBbcode = array(
        array('Source','-','NewPage'),
        array('Undo','Redo','-','Replace','-','SelectAll','RemoveFormat'),
        array('Bold','Italic','Underline','Link','Unlink','SpecialChar'),
    );

    /**
     * This is the default toolbar for frontend editing contend
     * @var     array       $defaultFrontendEditingContent
     * @access  protected
     */
    protected $defaultFrontendEditingContent = array(
        array('Publish','Save','Templates'),
        array('Cut','Copy','Paste','PasteText','PasteFromWord','-','Scayt'),
        array('Undo','Redo','-','Replace','-','SelectAll','RemoveFormat'),
        array('Bold','Italic','Underline','Strike','-','Subscript','Superscript'),
        array('NumberedList','BulletedList','-','Outdent','Indent', 'Blockquote'),
        '/',
        array('JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'),
        array('Link','Unlink','Anchor'),
        array('Image','Flash','Table','HorizontalRule','SpecialChar'),
        array('Format'),
        array('TextColor','BGColor'),
        array('ShowBlocks')
    );

    /**
     * This is the default toolbar for frontend editing title
     * @var     array       $defaultFrontendEditingTitle
     * @access  protected
     */
    protected $defaultFrontendEditingTitle = array(
        array('Publish','Save'),
        array('Cut','Copy','Paste','-','Scayt'),
        array('Undo','Redo')
    );

    /**
     * These are the buttons that are removed by default and are not accessible
     * @var     string      Functions that shall never be available in Cloudrexx
     * @access  protected
     */
    protected $defaultRemovedButtons = 'autoFormat,CommentSelectedRange,UncommentSelectedRange,AutoComplete,Preview,Smiley,Iframe,Styles';

    /**
     * Contains all available types of toolbars
     * @var     array       Contains all the type of toolbars available in Cloudrexx
     * @access  protected
     */
    protected $types = array(
        'small',
        'full',
        'frontendEditingContent',
        'frontendEditingTitle',
        'bbcode'
    );
    /**
     * Contains the connection to the database to change it fast and simple
     * @var     \ADONewConnection   $dbCon  Connection with the Database
     * @access  protected
     */
    protected $dbCon;

    /**
     * ToolbarController constructor.
     * @param \Cx\Core\Core\Controller\Cx $cx
     */
    public function __construct(\Cx\Core\Core\Model\Entity\SystemComponentController $systemComponentController, \Cx\Core\Core\Controller\Cx $cx) {
        parent::__construct($systemComponentController, $cx);

        // Get the database connection
        $this->dbCon = $cx->getDb()->getAdoDb();
    }

    /**
     * Compile the removed buttons into available functions
     *
     * Compiles the removed buttons into available function based on the given
     * type of toolbar (either small, full, bbcode, frontendEditingContent or
     * frontendEditingTitle) and returns these available functions as a json-
     * array
     * @param string $removedButtons List of functinos which shall be removed
     * @param string $type Type of toolbar (either small, full, bbcode, frontendEditingContent or frontendEditingTitle)
     * @return string $oldSyntax json-array of the available functions
     */
    public function getAsOldSyntax($removedButtons, $type) {
        // create array of removed buttons from comma separated functions
        $removedButtons = explode(',', $removedButtons);
        $type = lcfirst($type);
        // load old syntax of given type
        switch ($type) {
            case 'frontendEditingContent':
                $oldSyntax = $this->defaultFrontendEditingContent;
                break;
            case 'frontendEditingTitle':
                $oldSyntax = $this->defaultFrontendEditingTitle;
                break;
            case 'bbcode':
                $oldSyntax = $this->defaultBbcode;
                break;
            case 'small':
            case 'full':
            default:
                $oldSyntax = $this->defaultFull;
                break;
        }
        // remove the buttons which shall be removed
        foreach (array_keys($oldSyntax) as $key) {
            foreach ($removedButtons as $toRemove) {
                // Skip toolbar breakpoints
                if ($oldSyntax[$key] == '/') {
                    continue;
                }
                if (in_array($toRemove, $oldSyntax[$key])) {
                    $keyToRemove = array_search($toRemove, $oldSyntax[$key]);
                    unset($oldSyntax[$key][$keyToRemove]);
                    $oldSyntax[$key] = array_reverse(array_reverse($oldSyntax[$key]));
                    if (count($oldSyntax[$key]) == 1) {
                        $tmp = array_reverse(array_reverse($oldSyntax[$key]));
                        if ($tmp[0] == '-') {
                            unset($oldSyntax[$key]);
                        }
                    }
                }
            }
        }
        $oldSyntax = array_values($oldSyntax);
        // create a json array once again
        return json_encode($oldSyntax);
    }

    /**
     * Get the template for the toolbar configurator
     *
     * Also registers the necessary css and js files
     * @param   string              $componentRoot          The Path to the template
     * @param   bool                $isDefaultConfiguration Wraps the template
     *                                                      in a form tag
     *                                                      defaults to false
     * @return \Cx\Core\Html\Sigma  $template               The toolbar
     *                                                      configurator template
     */
    public function getToolbarConfiguratorTemplate($componentRoot = '/', $isDefaultConfiguration = false) {
        // load the toolbarconfigurator template
        $template = new \Cx\Core\Html\Sigma($componentRoot . '/View/Template/Backend');
        if ($isDefaultConfiguration) {
            $template->loadTemplateFile('DefaultToolbarConfiguration.html');
            $template->addBlockfile('WYSIWYG_TOOLBAR_CONFIGURATOR', 'ToolbarConfigurator', 'ToolbarConfigurator.html');
        } else {
            $template->loadTemplateFile('ToolbarConfigurator.html');
            $template->touchBlock('ToolbarConfigurator');
        }
        // prepare the js and css files which are needed
        $requiredJsFiles = array(
            'js/fulltoolbareditor',
            'js/abstracttoolbarmodifier',
            'js/toolbarmodifier',
            'js/toolbartextmodifier',
        );
        $ckeditorLibPath = '/lib/ckeditor/samples';
        \JS::registerJS($componentRoot . '/View/Script/Backend.js');
        \JS::registerJS($componentRoot . '/View/Script/ToolbarButtonsRemover.js');
        // register js and css files for the toolbarconfigurator
        
        foreach ($requiredJsFiles as $filename) {
            \JS::registerJS(
                $ckeditorLibPath . '/toolbarconfigurator/' . $filename . '.js'
            );
        }
        \JS::activate('ckeditor');
        \JS::activate('jquery');
        \JS::registerCSS($componentRoot . '/View/Style/Samples.css');
        \JS::registerCSS($componentRoot . '/View/Style/Backend.css');
        if ($isDefaultConfiguration) {
            $buttons = $this->defaultRemovedButtons;
            $scope = 'default';
        } else {
            $buttons = $this->getRemovedButtons(true, true);
            $scope = 'groups';
        }
        \ContrexxJavascript::getInstance()->setVariable(
            'removedButtons',
            $buttons,
            'wysiwyg/' . $scope
        );
        // get the init object to change to te proper language file
        $init = \Env::get('init');
        // get the language file of the Wysiwyg component (this one btw.)
        $_ARRAYLANG = $init->getComponentSpecificLanguageData('Wysiwyg', false, FRONTEND_LANG_ID);
        // replace language variables
        $template->setVariable(array(
            'TXT_WYSIWYG_TOOLBAR_SAVE'  => $_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_SAVE'],
        ));

        // Check if the toolbar configurator needs to be wrapped in a form tag
        if (!$isDefaultConfiguration) {
            $template->hideBlock('wysiwyg_toolbar_store_button');
        }
        $template->setVariable(array(
            'TXT_WYSIWYG_TOOLBAR_CONFIGURATOR'  => $_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_CONFIGURATOR'],
        ));

        $this->getToolbarTranslations();

        return $template;
    }

    /**
     * Wrapper for any getTYPEToolbar calls
     *
     * Type must be one of the following: Small, Full, FrontendEditingContent,
     * FrontendEditingTitle or Bbcode
     * @param $type     string  Type of toolbar (Small, Full,
     *                          FrontendEditingContent, FrontendEditingTitle or
     *                          Bbcode)
     * @return string   string  The toolbar with the restricted scope of functions
     */
    public function getToolbar($type) {
        // Get all the toolbar ids of every user group assigned to the user
        $toolbarIds = $this->getToolbarIdsOfUserGroup();
        // Make sure that we do not have any redundant toolbar ids
        $toolbarIds = array_unique($toolbarIds);
        // Load the removedButtons of the given toolbars
        $removedButtons = $this->loadRemovedButtons($toolbarIds);
        // return the merged toolbars
        $mergedButtons = $this->mergeRemovedButtons($removedButtons);
        $toolbar = $this->getAsOldSyntax($mergedButtons, $type);
        return $toolbar;
    }

    /**
     * Get all toolbar ids of the assigned user groups or by the given user groups
     *
     * If the parameter $userGroup is empty the assigned user groups of the
     * current user are used
     * @param   array $userGroups   Array containing the user group ids
     * @return  array $toolbarIds   All the available toolbar ids.
     *                              Be wary though, as this array might be empty
     */
    protected function getToolbarIdsOfUserGroup($userGroups = array()) {
        // populate $groupIds with the given user groups
        $groupIds = $userGroups;
        if (empty($groupIds)) {
            // Load the current user
            $user = \FWUser::getFWUserObject();
            // Login user
            $user->objUser->login(true);
            // Get all assigned user group ids
            $groupIds = $user->objUser->getAssociatedGroupIds();
        }
        // Initiate an empty array of toolbarIds
        $toolbarIds = array();
        // Loop through all user group ids
        foreach ($groupIds as $groupId) {
            $toolbarId = $this->getToolbarByGroupId($groupId);
            // Check if the user group has a toolbar assigned
            if (!empty($toolbarId)) {
                // Store the assigned toolbar id
                $toolbarIds[] = $toolbarId;
            }
        }
        return $toolbarIds;
    }

    /**
     * Load the removed button of the given toolbar ids
     *
     * This method loads the removed buttons of the default settings as well.
     * @param   array   $toolbarIds Array containing all ids of the toolbars
     *                              that shall be loaded
     * @return  array               Array containing the removed buttons of
     *                              the given toolbar ids
     */
    protected function loadRemovedButtons(array $toolbarIds) {
        // Initiate an empty removedButtons array
        $removedButtons = array();
        if (empty($toolbarIds)) {
            // Query to load the removed buttons which are specified in the settings
            $defaultRemovedButtonsQuery = '
            SELECT `removed_buttons` FROM `' . DBPREFIX . 'core_wysiwyg_toolbar`
            WHERE `is_default` = 1
            LIMIT 1';
            $defaultRemovedButtonsRes = $this->dbCon->Execute($defaultRemovedButtonsQuery);
            // Check if a default selection has been made
            if ($defaultRemovedButtonsRes) {
                // Fetch the removed buttons
                $defaultRemovedButtons = $defaultRemovedButtonsRes->fields;
                // Check if the removed buttons list is not empty
                if (!empty($defaultRemovedButtons)) {
                    // Add the default removed buttons to the array of removed buttons
                    $removedButtons[] = $defaultRemovedButtons['removed_buttons'];
                }
            }
            return $removedButtons;
        }
        // Loop through each toolbar id
        foreach ($toolbarIds as $toolbarId) {
            $toolbarButtons = $this->getRemovedButtonsByToolbarId($toolbarId);
            if (!empty($toolbarButtons)) {
                // Store the available functions for the current toolbar
                $removedButtons[] = $toolbarButtons;
            }
        }
        return $removedButtons;
    }

    /**
     * Merge the given removed buttons in $removedButtons together
     * @param   array   $removedButtons The removed buttons which shall be
     *                                  merged together
     * @return  string                  The merged buttons. Be wary that this
     *                                  string might be empty
     */
    protected function mergeRemovedButtons(array $removedButtons) {
        $mergedButtons = array();
        // Verify that there is anything to merge at all
        if (empty($removedButtons)) {
            return '';
        }
        // Check if there is more than one list of buttons
        if (count($removedButtons) < 2) {
            // Merging only one list of buttons would be pointless
            $mergedButtons = $removedButtons[0];
        } else {
            // Create arrays out of the removed button strings
            foreach($removedButtons as $key => $removedButtonsList) {
                $removedButtons[$key] = explode(',', $removedButtonsList);
            }
            // Initiate tmpMerged with the array containing the least amount of
            // removed buttons
            $tmpMerged = min($removedButtons);
            // Unset the index
            unset($removedButtons[array_search($tmpMerged, $removedButtons)]);
            // Renumber the array
            $removedButtons = array_reverse($removedButtons);
            // Loop through all remaining lists of removed buttons
            for ($i = 0; $i <= count($removedButtons) - 1; $i += 2) {
                $buttonsOne = $removedButtons[$i];
                // Check if we have more than one list of removed buttons
                // remaining
                if (array_key_exists($i + 1, $removedButtons)) {
                    $buttonsTwo = $removedButtons[$i + 1];
                    // Get the buttons that are definitely removed
                    $mergedButtons = array_intersect($tmpMerged, $buttonsOne, $buttonsTwo);
                } else {
                    // Only one list of removed buttons left
                    $mergedButtons = array_intersect($tmpMerged, $buttonsOne);
                }
            }
        }
        // Combine the merged buttons into a string
        if (is_array($mergedButtons)) {
            $mergedButtons = join(',', $mergedButtons);
        }
        return $mergedButtons;
    }

    /**
     * Get the buttons that shall be removed
     * @param   bool    $buttonsOnly    Only buttons no config.removeButtons
     *                                  prefix
     * @param   bool    $isAccess       If set remove buttons that are disabled
     *                                  by the default configuration
     * @return  string                  Either the list of removed buttons or
     *                                  the proper config string
     */
    public function getRemovedButtons($buttonsOnly = false, $isAccess = false) {
        if ($this->cx->getMode() == 'frontend') {
            return '';
        }
        // Initiate default buttons with the buttons that are not allowed
        $buttons = $this->defaultRemovedButtons;
        // Initiate the default removed buttons as empty string
        $defaultButtons = '';
        // Prepare the query to load the default configuration
        $query = 'SELECT `removed_buttons` FROM `' . DBPREFIX .
                    'core_wysiwyg_toolbar`
                  WHERE `is_default` = 1
                  LIMIT 1';
        $defaultButtonsRes = $this->dbCon->Execute($query);
        // Verify that the query could be executed
        if ($defaultButtonsRes) {
            // Fetch the data
            $defaultButtons = $defaultButtonsRes->fields['removed_buttons'];
            // Check that a default toolbar has been configured
            if (!empty($defaultButtons)) {
                // Rewrite the buttons with the default removed buttons
                $buttons = $defaultButtons;
            }
        }
        // Check if a user group is edited
        if (!empty($_GET['id'])) {
            // Get the group id
            $groupId = intval($_GET['id']);
            $toolbarId = $this->getToolbarByGroupId($groupId);
            $toolbarButtons = $this->getRemovedButtonsByToolbarId($toolbarId);
            if (
                    !empty($toolbarButtons)
                &&  $toolbarButtons != $this->defaultRemovedButtons
            ) {
                $buttons = $toolbarButtons;
            }
        }
        // Used to hide functions that are disabled by the default config
        if ($isAccess && $defaultButtons) {
            // Overwrite the removed buttons from the toolbar to ensure that
            // those functions are still available to enable / disable
            $buttons = $defaultButtons;
        }
        // Used to hide functions that are not allowed to be enabled
        if (!$buttonsOnly) {
            $buttons = 'config.removeButtons = \'' . $buttons . '\'';
        }
        return $buttons;
    }

    /**
     * Get the translations for the toolbar and store it as a variable
     * 
     * This variable is available through the cx javascript framework. 
     * @return void
     */
    protected function getToolbarTranslations() {
        // Get the init object to change to te proper language file
        $init = \Env::get('init');
        $init->_initBackendLanguage();
        // Check if current language is english or not
        if ($init->getBackendLangId() === 2) {
            // Current language is english no need to translate anything
            return;
        }
        // Initiate $translations as empty stdClass object
        $translations = new \stdClass();
        // Get the language file of the Wysiwyg component (this one btw.)
        $_ARRAYLANG = $init->getComponentSpecificLanguageData('Wysiwyg', false, 1);
        // Populate the std object with the translations
        $translations->mode = $_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_MODE'];
        $translations->document = $_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_DOCUMENT'];
        $translations->doctools = $_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_DOCUMENT_TOOLS'];
        $translations->clipboard = $_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_CLIPBOARD'];
        $translations->undo = $_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_UNDO'];
        $translations->find = $_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_FIND'];
        $translations->selection = $_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_SELECTION'];
        $translations->spellchecker = $_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_SPELLCHECKER'];
        $translations->basicstyles = $_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_BASICSTYLES'];
        $translations->cleanup = $_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_CLEANUP'];
        $translations->list = $_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_LIST'];
        $translations->indent = $_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_INDENT'];
        $translations->blocks = $_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_BLOCKS'];
        $translations->align = $_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_ALIGN'];
        $translations->links = $_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_LINKS'];
        $translations->insert = $_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_INSERT'];
        $translations->styles = $_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_STYLES'];
        $translations->colors = $_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_COLORS'];
        $translations->tools = $_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_TOOLS'];
        \ContrexxJavascript::getInstance()->setVariable(
            'toolbarTranslations',
            $translations,
            'toolbarConfigurator'
        );
    }

    /**
     * Store a toolbar configuration
     *
     * Store a toolbar configuration and return the id in case of a new toolbar
     * or 0 in case an existing one has been updated
     * @param string    $toolbar    Toolbar configuration that shall be stored
     * @param int       $toolbarId  Id of the toolbar if an existing one is updated
     * @param bool      $isDefault  Store config as default configuration
     * @return int
     * @throws \Cx\Core\Model\DbException
     */
    public function store($toolbar, $toolbarId = 0, $isDefault = false) {
        // Get the new toolbar as an array
        $newFunctions = json_decode($this->getAsOldSyntax($toolbar, 'full'));
        // Toolbar already exists - editing an existing group
        if ($toolbarId) {
            // Load toolbar
            $currentToolbar = $this->getRemovedButtonsByToolbarId(intval($toolbarId));
            // Assure that the statement did not fail
            if ($currentToolbar) {
                // Prepare the two removed buttons list for comparison
                $firstToolbar = explode(',', $currentToolbar);
                $secondToolbar = explode(',', $toolbar);
                // Create first diff
                $diff = array_diff($firstToolbar, $secondToolbar);
                // Check if the second toolbar is more restricted than the first
                if (count($secondToolbar) > count($firstToolbar)) {
                    $diff = array_diff($secondToolbar, $firstToolbar);
                }
                // Check if the toolbar has been changed
                if ((count($diff) != 0)) {
                    $whereDefault = '';
                    if ($isDefault) {
                        $whereDefault = ' AND `is_default` = 1';
                    }
                    // The toolbar has been modified
                    $query = '
                            UPDATE `' . DBPREFIX . 'core_wysiwyg_toolbar`
                            SET `available_functions` = \'' . json_encode($newFunctions) . '\',
                                `removed_buttons` = \'' . contrexx_input2db($toolbar) . '\'
                            WHERE `id` = ' . intval($toolbarId) . $whereDefault;
                    $this->dbCon->Execute($query);
                }
            }
            return 0;
        } else {
            $columnDefault = $valueDefault = '';
            if ($isDefault) {
                $columnDefault = ', `is_default`';
                $valueDefault = ', 1';
            }
            // Group has currently no special toolbar assigned
            // Store as a new toolbar and get its generated id
            $query = 'INSERT INTO `' . DBPREFIX . 'core_wysiwyg_toolbar`(
                        `available_functions`, `removed_buttons`'
                . $columnDefault . ')
                      VALUES (\'' . json_encode($newFunctions) . '\',
                        \'' . contrexx_input2db($_POST['removedButtons']) . '\''
                . $valueDefault . ')';
            $this->dbCon->Execute($query);
            // Get the id of the new toolbar
            $toolbarId = $this->dbCon->Execute('SELECT LAST_INSERT_ID() AS `id`;')->fields['id'];
            // Return the new toolbar id
            return $toolbarId;
        }
    }

    /**
     * @param $groupId
     * @return string
     */
    public function getToolbarByGroupId($groupId) {
        // Prepare the query to load the user group toolbar
        $query = 'SELECT `toolbar` FROM `' . DBPREFIX . 'access_user_groups`
                      WHERE `group_id` = ' . intval($groupId) . '
                      LIMIT 1';
        $toolbarIdRes = $this->dbCon->Execute($query);
        // Verify that the query could be executed
        if ($toolbarIdRes) {
            // Fetch the toolbar id
            $toolbarId = $toolbarIdRes->fields['toolbar'];
            if (!empty($toolbarId)) {
               return $toolbarId;
            }
        }
        return 0;
    }

    /**
     * @param $toolbarId
     * @return string
     */
    public function getRemovedButtonsByToolbarId($toolbarId) {
        // Prepare the query to get the removed button of the toolbar
        $query = 'SELECT `removed_buttons` FROM `' . DBPREFIX .
            'core_wysiwyg_toolbar`
                  WHERE `id` = ' . intval($toolbarId) . '
                  LIMIT 1';
        $toolbarButtonsRes = $this->dbCon->Execute($query);
        // Verify that the query could be executed
        if ($toolbarButtonsRes) {
            // Fetch the removed buttons
            $toolbarButtons = $toolbarButtonsRes->fields;
            // Verify that there are any removed buttons
            if (!empty($toolbarButtons)) {
                // Rewrite the buttons with the removed buttons of
                // the user group
                $buttons = $toolbarButtons['removed_buttons'];
                return $buttons;
            }
        }
        return $this->defaultRemovedButtons;
    }
}
