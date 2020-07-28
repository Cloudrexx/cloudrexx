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
 * AliasManager
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @version        1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_alias
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Core_Modules\Alias\Controller;
/**
 * AliasManager
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @version        1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_alias
 * @todo        Edit PHP DocBlocks!
 */
class AliasManager extends \Cx\Core_Modules\Alias\Controller\AliasLib
{
    /**
    * Template object
    *
    * @access private
    * @var object
    */
    var $_objTpl;

    /**
    * Page title
    *
    * @access private
    * @var string
    */
    var $_pageTitle;

    /**
    * Status message
    *
    * @access private
    * @var array
    */
    var $arrStatusMsg = array('ok' => array(), 'error' => array());

    private $act = '';

    /**
    * PHP5 constructor
    *
    * @global \Cx\Core\Html\Sigma
    * @global array
    */
    function __construct()
    {

        parent::__construct();

        $this->_objTpl = new \Cx\Core\Html\Sigma(ASCMS_CORE_MODULE_PATH.'/Alias/View/Template/Backend');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);
    }

    protected function setNavigation()
    {
        global $objTemplate, $_ARRAYLANG;

        $navigation = '<a href="index.php?cmd=Alias" class="' . ($this->act == '' || $this->act == 'delete' ? 'active' : '') . '">' . $_ARRAYLANG['TXT_ALIAS_ALIASES'] . '</a>';
        if (\Permission::checkAccess(78, 'static', true)) {
            $navigation .= '<a href="index.php?cmd=Alias&amp;act=modify" class="' . ($this->act == 'modify' ? 'active' : '') . '">' . $_ARRAYLANG['TXT_ALIAS_ADD_ALIAS'] . '</a>';
        }
        $objTemplate->setVariable('CONTENT_NAVIGATION', $navigation);
    }

    /**
    * Set the backend page
    *
    * @access public
    * @global \Cx\Core\Html\Sigma
    * @global array
    */
    function getPage()
    {
        global $objTemplate, $_ARRAYLANG;

        if (!isset($_REQUEST['act'])) {
            $_REQUEST['act'] = '';
        }

        switch ($_REQUEST['act']) {
            case 'modify':
                \Permission::checkAccess(78, 'static');
                $this->_modifyAlias($_POST);
                break;

            case 'delete':
                \Permission::checkAccess(78, 'static');
                $this->_delete();

            default:
                $this->_list();
                break;
        }

        $this->_pageTitle = $_ARRAYLANG['TXT_OVERVIEW'];

        $objTemplate->setVariable(array(
            'CONTENT_TITLE'                     => $this->_pageTitle,
            'CONTENT_OK_MESSAGE'                => implode("<br />\n", $this->arrStatusMsg['ok']),
            'CONTENT_STATUS_MESSAGE'            => implode("<br />\n", $this->arrStatusMsg['error']),
            'ADMIN_CONTENT'                     => $this->_objTpl->get()
        ));

        $this->act = $_REQUEST['act'];
        $this->setNavigation();
    }

    function _list()
    {
        global $_CORELANG, $_ARRAYLANG, $_CONFIG;

        $showLegacyPagealiases = isset($_GET['legacyPages']) && $_GET['legacyPages'];

        $this->_objTpl->loadTemplateFile('module_alias_list.html');
        $this->_pageTitle = $_ARRAYLANG['TXT_ALIAS_ALIAS_ES'];
        $this->_objTpl->setGlobalVariable(array(
            'TXT_ALIAS_ALIASES' => $_ARRAYLANG['TXT_ALIAS_ALIASES'],
            'TXT_ALIAS_SHOW_LEGACY_PAGE_ALIASES' => $_ARRAYLANG['TXT_ALIAS_SHOW_LEGACY_PAGE_ALIASES'],
            'ALIAS_SHOW_LEGACY_PAGE_ALIASES_CHECKED' => $showLegacyPagealiases ? 'checked="checked"' : '',
            'ALIAS_SHOW_LEGACY_PAGE_VALUE' => intval($showLegacyPagealiases),
        ));

        // show warning message if cloudrexx is running on an IIS webserver and the web.config seems not be be registred in the server configuration
        if (ASCMS_WEBSERVER_SOFTWARE == 'iis') {
            \Env::get('ClassLoader')->loadFile(ASCMS_LIBRARY_PATH.'/PEAR/HTTP/Request2.php');
            $objRequest = new \HTTP_Request2('http://'.$_CONFIG['domainUrl'].ASCMS_PATH_OFFSET.'/'.FWLanguage::getLanguageCodeById(FRONTEND_LANG_ID).'/index.php?section=Error');
            try {
                $objResponse = $objRequest->send();
                if ($objResponse->getStatus() !== 200) {
                    $this->_objTpl->setVariable('TXT_ALIAS_IIS_HTACCESS_NOT_REGISTERED', $_ARRAYLANG['TXT_ALIAS_IIS_HTACCESS_NOT_REGISTERED']);
                    $this->_objTpl->parse('htaccess_warning');
                } else {
                    $this->_objTpl->hideBlock('htaccess_warning');
                }
            } catch (HTTP_Request2_Exception $objException) {
                \DBG::msg($objException->getMessage());
            }
        }

        // Alias search was triggerd
        if(!empty($_GET['term'])){
            $slug = '%' . $_GET['term'] . '%';
            $this->_objTpl->setVariable('ALIAS_SEARCH_TERM', contrexx_input2xhtml($_GET['term']));
        } else {
            $slug = null;
        }

        // search for aliases
        $this->_objTpl->setVariable(array(
            'TXT_SEARCH'        => $_CORELANG['TXT_SEARCH'],
        ));

        $arrAliases = $this->_getAliases($_CONFIG['corePagingLimit'], false, $showLegacyPagealiases, $slug);

        if ($this->hasLegacyPages) {
            $this->_objTpl->touchBlock('alias_show_legacy_pages');
        } else {
            $this->_objTpl->hideBlock('alias_show_legacy_pages');
        }

        $nr = 1;
        if (count($arrAliases)) {
            $this->_objTpl->setVariable(array(
                'TXT_ALIAS_PAGE'                    => $_ARRAYLANG['TXT_ALIAS_PAGE'],
                'TXT_ALIAS_ALIAS'                   => $_ARRAYLANG['TXT_ALIAS_ALIAS'],
                'TXT_ALIAS_FUNCTIONS'               => $_ARRAYLANG['TXT_ALIAS_FUNCTIONS'],
                'TXT_ALIAS_CONFIRM_DELETE_ALIAS'    => $_ARRAYLANG['TXT_ALIAS_CONFIRM_DELETE_ALIAS'],
                'TXT_ALIAS_OPERATION_IRREVERSIBLE'  => $_ARRAYLANG['TXT_ALIAS_OPERATION_IRREVERSIBLE'],
            ));

            $this->_objTpl->setGlobalVariable(array(
                'TXT_ALIAS_DELETE'                  => $_ARRAYLANG['TXT_ALIAS_DELETE'],
                'TXT_ALIAS_MODIFY'                  => $_ARRAYLANG['TXT_ALIAS_MODIFY'],
                'TXT_ALIAS_OPEN_ALIAS_NEW_WINDOW'   => $_ARRAYLANG['TXT_ALIAS_OPEN_ALIAS_NEW_WINDOW'],
            ));

            // this is a dirty hack, to force the CSS class 'rowWarn'
            // on non-existent targets
            $targetURL = '<none>';
            foreach ($arrAliases as $page) {

                $sourceURL = $page->getSlug();

                    $this->_objTpl->setVariable(array(
                        'ALIAS_TARGET_ID'       => $page->getNode()->getId(),
                        'ALIAS_SOURCE_REAL_URL' => 'http://'.$_CONFIG['domainUrl'].ASCMS_PATH_OFFSET.'/'.stripslashes($sourceURL),
                        'ALIAS_SOURCE_URL'      => 'http://'.$_CONFIG['domainUrl'].ASCMS_PATH_OFFSET.'<strong>/'.stripslashes($sourceURL).'</strong>',
                    ));

                    $target = "";
                    $target_title = '';
                    if ($this->_isLocalAliasTarget($page)) {
                        // alias points to a local webpage
                        $targetPage = $this->_fetchTarget($page);
                        if ($targetPage) {
                            $target = $targetPage->getTitle();
                            $targetURL = $this->_getURL($targetPage);
                            $target_title = $target . " (" . $targetURL . $page->getTargetQueryString() . ")";
                        }
                    } else {
                        $target = $page->getTarget();
                        $targetURL = $target;
                        $target_title = $target;
                    }
                    $this->_objTpl->hideBlock('alias_source_not_set');
                    $this->_objTpl->parse('alias_source_list');

                if (\Permission::checkAccess(78, 'static', true)) {
                    $this->_objTpl->touchBlock('alias_functions');
                } else {
                    $this->_objTpl->hideBlock('alias_functions');
                }

                $this->_objTpl->setVariable(array(
                    // if target is local (target != targetURL) and target is empty: class is rowWarn
                    'ALIAS_ROW_CLASS'       => $target != $targetURL && empty($target) && $nr++ ? 'rowWarn ' : 'row'.($nr++ % 2 + 1),
                    'ALIAS_TARGET_TITLE'    => $target_title,
                ));
                $this->_objTpl->parse('aliases_list');
            }

            $this->_objTpl->parse('alias_data');
            $this->_objTpl->hideBlock('alias_no_data');

            $aliasesCount = $this->_getAliasesCount($showLegacyPagealiases, $slug);
            if ($aliasesCount > count($arrAliases)) {
                $this->_objTpl->setVariable('ALIAS_PAGING', '<br />'.getPaging($aliasesCount, !empty($_GET['pos']) ? intval($_GET['pos']) : 0, '&cmd=Alias&legacyPages=' . ($showLegacyPagealiases ? 1 : 0) . (isset($_GET['term']) ? '&term=' . $_GET['term'] : ''), $_ARRAYLANG['TXT_ALIAS_ALIASES']));
            }
        } else {
            $this->_objTpl->setVariable('TXT_ALIAS_NO_ALIASES_MSG', $_ARRAYLANG['TXT_ALIAS_NO_ALIASES_MSG']);

            $this->_objTpl->hideBlock('alias_data');
            $this->_objTpl->parse('alias_no_data');
        }
    }

    /**
     * @todo Use param $page (to update a page without prividing an id per GET)
     */
    function _modifyAlias($array, $page = null)
    {
        global $_ARRAYLANG, $_CONFIG;

        $objCx = \ContrexxJavascript::getInstance();
        $objCx->setVariable(array(
            'regExpUriProtocol'  =>  \FWValidator::REGEX_URI_PROTO,
            'contrexxPathOffset' => ASCMS_PATH_OFFSET,
            'contrexxBaseUrl'    => ASCMS_PROTOCOL . '://' . $_CONFIG['domainUrl'] . ASCMS_PATH_OFFSET,
        ), 'alias');

        $aliasId = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

        // search for existing alias
        if ((!$alias = $this->_getAlias($aliasId))) {
            $alias = $this->_createTemporaryAlias();
            $aliasId = 0;
        }

        // handle form submit
        if (isset($array['alias_save'])) {
            if (!empty($array['alias_target']) && !empty($array['alias_target_protocol'])) {
                $array['alias_target'] = $array['alias_target_protocol'] . $array['alias_target'];
            }

            // set target and -type
            $newtype   = preg_match('/\[\['.\Cx\Core\ContentManager\Model\Entity\Page::NODE_URL_PCRE.'\]\](\S*)?/ix', $array['alias_target']) ? 'local' : 'url';
            $newtarget = !empty($array['alias_target']) ? trim(contrexx_stripslashes($array['alias_target'])) : '';

            // handle existing slugs pointing to the target
            $aliases = array();
            if (!empty($array['alias_aliases']) && is_array($array['alias_aliases'])) {
                $nr = 0;
                foreach ($array['alias_aliases'] as $sourceId => $aliasSource) {
                    if (!empty($aliasSource)) {
                        $aliases[intval($sourceId)] = $aliasSource;
                    }
                    $nr++;
                }
            }

            // delete removed
            $sources = $this->_getAliasesWithSameTarget($alias);
            foreach ($sources as $sourceAlias) {
                if (!isset($aliases[$sourceAlias->getNode()->getId()])) {
                    // alias is no longer listet in POST: delete it
                    $this->_delete($sourceAlias->getNode()->getId());
                }
            }

            // handle enw slugs pointing to the target
            $newaliases = array();
            if (!empty($array['alias_aliases_new']) && is_array($array['alias_aliases_new'])) {
                foreach ($array['alias_aliases_new'] as $id => $newAliasSource) {
                    if (!empty($newAliasSource)) {
                        $newaliases[] = $newAliasSource;
                    }
                }
            }

            // save information
            if (!empty($newtarget)) {
                if (count($aliases) || count($newaliases)) {
                    $error = false;

                    if ($newtype == 'local') {
                        $placeholder = \Cx\Core\Routing\NodePlaceholder::fromPlaceholder($newtarget);
                        // force usage of node ID
                        $newtarget = $placeholder->getPlaceholder(true);
                    }

                    foreach ($aliases as $id=>$slug) {
                        if (($message = $this->_saveAlias($slug, $newtarget, $newtype == 'local', $id)) !== true) {
                            $error = true;
                            if ($message !== false) {
                                $this->arrStatusMsg['error'][] = $message;
                            } else {
                                $this->arrStatusMsg['error'][] = $aliasId ? $_ARRAYLANG['TXT_ALIAS_ALIAS_UPDATE_FAILED'] : $_ARRAYLANG['TXT_ALIAS_ALIAS_ADD_FAILED'];
                                $this->arrStatusMsg['error'][] = $_ARRAYLANG['TXT_ALIAS_RETRY_OPERATION'];
                            }
                            break;
                        }
                    }
                    if (!$error) {
                        foreach ($newaliases as $id=>$slug) {
                            if (($message = $this->_saveAlias($slug, $newtarget, $newtype == 'local')) !== true) {
                                $error = true;
                                if ($message !== false) {
                                    $this->arrStatusMsg['error'][] = $message;
                                } else {
                                    $this->arrStatusMsg['error'][] = $aliasId ? $_ARRAYLANG['TXT_ALIAS_ALIAS_UPDATE_FAILED'] : $_ARRAYLANG['TXT_ALIAS_ALIAS_ADD_FAILED'];
                                    $this->arrStatusMsg['error'][] = $_ARRAYLANG['TXT_ALIAS_RETRY_OPERATION'];
                                }
                                break;
                            }
                        }
                    }
                    if (!$error) {
                        $this->arrStatusMsg['ok'][] = $aliasId ? $_ARRAYLANG['TXT_ALIAS_ALIAS_SUCCESSFULLY_UPDATED'] : $_ARRAYLANG['TXT_ALIAS_ALIAS_SUCCESSFULLY_ADDED'];
                    }
                    $_REQUEST['act'] = ''; // For the navigation
                    return $this->_list();
                } else {
                    $this->arrStatusMsg['error'][] = $_ARRAYLANG['TXT_ALIAS_ONE_ALIAS_REQUIRED_MSG'];
                }
            } else {
                if ($newtype == 'local') {
                    $this->arrStatusMsg['error'][] = $_ARRAYLANG['TXT_ALIAS_PAGE_REQUIRED_MSG'];
                } else {
                    $this->arrStatusMsg['error'][] = $_ARRAYLANG['TXT_ALIAS_URL_REQUIRED_MSG'];
                }
            }
        }

        $mediaBrowser = new \Cx\Core_Modules\MediaBrowser\Model\Entity\MediaBrowser();
        $mediaBrowser->setOptions(array(
            'type' => 'button',
            'views' => 'sitestructure',
            'startview' => 'sitestructure',
        ));
        $mediaBrowser->setCallback('aliasSetUrl');

        // prepare template
        $this->_objTpl->loadTemplateFile('module_alias_modify.html');
        $this->_pageTitle = $aliasId ? $_ARRAYLANG['TXT_ALIAS_MODIFY_ALIAS'] : $_ARRAYLANG['TXT_ALIAS_ADD_ALIAS'];

        $this->_objTpl->setVariable(array(
            'TXT_ALIAS_TARGET_PAGE'             => $_ARRAYLANG['TXT_ALIAS_TARGET_PAGE'],
            'ALIAS_MEDIABROWSER_BUTTON'         => $mediaBrowser->getXHtml($_ARRAYLANG['TXT_ALIAS_BROWSE']),
            'TXT_ALIAS_ALIAS_ES'                => $_ARRAYLANG['TXT_ALIAS_ALIAS_ES'],
            'TXT_ALIAS_DELETE'                  => $_ARRAYLANG['TXT_ALIAS_DELETE'],
            'TXT_ALIAS_CONFIRM_REMOVE_ALIAS'    => $_ARRAYLANG['TXT_ALIAS_CONFIRM_REMOVE_ALIAS'],
            'TXT_ALIAS_ADD_ANOTHER_ALIAS'       => $_ARRAYLANG['TXT_ALIAS_ADD_ANOTHER_ALIAS'],
            'TXT_ALIAS_CANCEL'                  => $_ARRAYLANG['TXT_ALIAS_CANCEL'],
            'TXT_ALIAS_SAVE'                    => $_ARRAYLANG['TXT_ALIAS_SAVE'],
            'TXT_ALIAS_STANDARD_RADIOBUTTON'    => $_ARRAYLANG['TXT_ALIAS_STANDARD_RADIOBUTTON'],
            'TXT_ALIAS_INTERNAL'                => $_ARRAYLANG['TXT_INTERNAL'],
        ));

        $this->_objTpl->setGlobalVariable(array(
            'TXT_ALIAS_DELETE'                  => $_ARRAYLANG['TXT_ALIAS_DELETE'],
            'ALIAS_DOMAIN_URL'                  => 'http://'.$_CONFIG['domainUrl'].ASCMS_PATH_OFFSET.'/',
            'TXT_ALIAS_STANDARD_RADIOBUTTON'    => $_ARRAYLANG['TXT_ALIAS_STANDARD_RADIOBUTTON']
        ));

        $target    = $alias->getTarget();
        $targetURL = '';
        $is_local  = $this->_isLocalAliasTarget($alias);
        if ($is_local) {
            // alias points to a local webpage
            $targetPage = $this->_fetchTarget($alias);
            if ($targetPage) {
                preg_match('/\[\['.\Cx\Core\ContentManager\Model\Entity\Page::NODE_URL_PCRE.'\]\](\S*)?/ix', $target, $matches);
                $targetURL = ASCMS_PROTOCOL . '://' . $_CONFIG['domainUrl'] . ASCMS_PATH_OFFSET . $this->_getURL($targetPage) . $matches[6];
            }
        } else {
            $targetURL = $target;
        }

        $this->_objTpl->setVariable(array(
            'ALIAS_ID'                  => $aliasId,
            'ALIAS_TITLE_TXT'           => $this->_pageTitle,
            'ALIAS_TARGET'              => htmlentities($target, ENT_QUOTES, CONTREXX_CHARSET),
            'ALIAS_TARGET_TEXT'         => htmlentities($targetURL, ENT_QUOTES, CONTREXX_CHARSET),
        ));

        $nr = 0;

        $sources = $this->_getAliasesWithSameTarget($alias);

        if (is_array($sources) && count($sources)) {
            foreach ($sources as $sourceAlias) {
                $url = $sourceAlias->getSlug();
                $this->_objTpl->setVariable(array(
                    'ALIAS_DOMAIN_URL'              => 'http://'.$_CONFIG['domainUrl'].ASCMS_PATH_OFFSET.'/',
                    'ALIAS_ALIAS_ID'                => $sourceAlias->getNode()->getId(),
                    'ALIAS_ALIAS_NR'                => $nr++,
                    'ALIAS_ALIAS_PREFIX'            => '',
                    'ALIAS_ALIAS_URL'               => stripslashes(htmlentities($url, ENT_QUOTES, CONTREXX_CHARSET))
                ));
                $this->_objTpl->parse('alias_list');
            }
        }

        $this->_objTpl->setVariable(array(
            'ALIAS_DOMAIN_URL'              => 'http://'.$_CONFIG['domainUrl'].ASCMS_PATH_OFFSET.'/',
            'ALIAS_ALIAS_PREFIX'            => '_new',
        ));
        $this->_objTpl->parse('alias_list');
    }


    function _delete($aliasId = "")
    {
        global $_ARRAYLANG;

        if ($aliasId == "") {
            $aliasId = !empty($_GET['id']) ? intval($_GET['id']) : 0;
        }

        if ($aliasId) {
            if ($this->_deleteAlias($aliasId)) {
                $this->arrStatusMsg['ok'][] = $_ARRAYLANG['TXT_ALIAS_ALIAS_SUCCESSFULLY_REMOVED'];
            } else {
                $this->arrStatusMsg['error'][] = $_ARRAYLANG['TXT_ALIAS_ALIAS_REMOVE_FAILED'];
                $this->arrStatusMsg['error'][] = $_ARRAYLANG['TXT_ALIAS_RETRY_OPERATION'];
            }
        }
    }
}
