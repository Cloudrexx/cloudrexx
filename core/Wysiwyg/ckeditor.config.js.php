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

header("content-type: application/javascript");

// detect system location
$depth = 3;
if (strpos(__FILE__, 'customizing/') !== false) {
    // this files resides within the customizing directory, therefore we'll have to strip
    // out one directory more than usually
    $depth++;
}
if (strpos(__FILE__, 'codeBases/') !== false) {
    // this files resides in a codeBase directory, therefore we'll have to strip
    // out two directory more than usually
    $depth += 2;
}
$contrexx_path = dirname(__FILE__, $depth);

/**
 * @ignore
 */
require_once($contrexx_path . '/core/Core/init.php');
$cx = init('minimal');

$sessionObj = $cx->getComponent('Session')->getSession();
$sessionObj->cmsSessionStatusUpdate('backend');

$pageId = !empty($_GET['pageId']) ? $_GET['pageId'] : null;

//get the main domain
$domainRepository = new \Cx\Core\Net\Model\Repository\DomainRepository();
$mainDomain = $domainRepository->getMainDomain()->getName();

//find the right css files and put it into the wysiwyg
$wysiwyg= $cx->getComponent('Wysiwyg');
\Cx\Core\Setting\Controller\Setting::init('Wysiwyg', 'config', 'Yaml');

$skinId = 0;
if (!empty($pageId) && $pageId != 'new') {
    $em = $cx->getDb()->getEntityManager();
    $pageRepo   = $em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
    $skinId = $pageRepo->find($pageId)->getSkin();
}

$ymlOption = $wysiwyg->getCustomCSSVariables($skinId);

// load language data
$lang = 0;
if (!empty($_GET['locale'])) {
    $locale = $_GET['locale'];
    $lang = \FWLanguage::getLanguageIdByCode($locale);
}
$_ARRAYLANG = \Env::get('init')->getComponentSpecificLanguageData(
    'Wysiwyg',
    false,
    $lang
);

?>
//if the wysiwyg css not defined in the session, then load the css variables and put it into the session
if(!cx.variables.get('css', 'wysiwyg')) {
    cx.variables.set('css', [<?php if (count($ymlOption['css'])) { echo '\'' . implode($ymlOption['css'], '\',\'') . '\''; } ?>], 'wysiwyg');
    cx.variables.set('bodyClass', <?php echo '\'' . $ymlOption['bodyClass'] . '\'' ?>, 'wysiwyg');
    cx.variables.set('bodyId', <?php echo '\'' . $ymlOption['bodyId'] . '\'' ?>, 'wysiwyg');
}

CKEDITOR.scriptLoader.load( '<?php echo $cx->getCodeBaseCoreModuleWebPath().'/MediaBrowser/View/Script/MediaBrowserCkeditorPlugin.js'   ?>' );
CKEDITOR.scriptLoader.load( '<?php echo $cx->getCodeBaseCoreWebPath().'/Wysiwyg/View/Script/ImagePasteCkeditorPlugin.js'; ?>' );
CKEDITOR.editorConfig = function( config )
{
    config.skin = 'moono-lisa';

    config.height = 307;
    config.uiColor = '#ececec';

    <?php if (!empty($_GET['locale'])) echo "config.language = '" . preg_replace('/[^a-z]/', '', $_GET['locale']) . "';";?>

    config.forcePasteAsPlainText = false;
    config.enterMode = CKEDITOR.ENTER_BR;
    config.shiftEnterMode = CKEDITOR.ENTER_P;
    config.startupOutlineBlocks = true;
    config.allowedContent = true;

    config.ignoreEmptyParagraph = false;
    config.protectedSource.push(/<i[^>]*><\/i>/g);
    config.protectedSource.push(/<span[^>]*><\/span>/g);
    config.protectedSource.push(/<a[^>]*><\/a>/g);

    config.ignoreEmptyParagraph = false;
    config.protectedSource.push(/<i[^>]*><\/i>/g);
    config.protectedSource.push(/<span[^>]*><\/span>/g);
    config.protectedSource.push(/<a[^>]*><\/a>/g);

    config.tabSpaces = 4;
    config.baseHref = '<?php echo \Cx\Core\Routing\Url::fromCapturedRequest('', $cx->getWebsiteOffsetPath(), array())->toString(); ?>';
    config.templates_files = [ '' ];
    config.templates_replaceContent = <?php echo \Cx\Core\Setting\Controller\Setting::getValue('replaceActualContents','Wysiwyg')? 'true' : 'false' ?>;

    config.toolbar_Full = config.toolbar_Small = <?php echo $wysiwyg->getToolbar() ?>;

    config.toolbar_BBCode = <?php echo $wysiwyg->getToolbar('bbcode') ?>;

    config.toolbar_FrontendEditingContent = <?php echo $wysiwyg->getToolbar('frontendEditingContent') ?>;

    config.toolbar_FrontendEditingTitle = <?php echo $wysiwyg->getToolbar('frontendEditingTitle') ?>;

    // Allow div's within a's
    CKEDITOR.dtd['a']['div'] = 1;

    //Set the CSS Stuff
    config.contentsCss = cx.variables.get('css', 'wysiwyg');
    config.bodyClass = cx.variables.get('bodyClass', 'wysiwyg');
    config.bodyId = cx.variables.get('bodyId', 'wysiwyg');
    if (
        window.location.pathname == cx.variables.get('cadminPath') + 'Config/Wysiwyg' ||
        window.location.pathname == cx.variables.get('cadminPath') + 'Access/group'
    ) {
        <?php echo $wysiwyg->getRemovedButtons(); ?>;
    }

    // load custom config from Wysiwyg.yml of webdesign template 
    <?php
        try {
            echo $wysiwyg->getCustomWysiwygEditorConfig($skinId, 1) . "\n";
        } catch (\Throwable $t) {
            \DBG::msg($t->getMessage());
        }
    ?>
};

//loading the templates
CKEDITOR.on('instanceReady',function(){
    var loadingTemplates = <?php
        try {
            echo $wysiwyg->getWysiwygTemplates($skinId);
        } catch (\Throwable $t) {
            \DBG::msg($t->getMessage());
        }
    ?>;
    for(var instanceName in CKEDITOR.instances) {
        loadingTemplates.button = CKEDITOR.instances[instanceName].getCommand("templates") //Reference to Template-Button

        // Define Standard-Path
        loadingTemplates.load = (function(){
            if (typeof this.button != 'undefined') {
                this.button.setState(CKEDITOR.TRISTATE_DISABLED) // Disable "Template"-Button
            }
            for(var i=0;i<this.length;i++){
                (function(item){
                    CKEDITOR.addTemplates('default',{
                        // CKeditor does not accept an empty imagesPath
                        // therefore, we have to perform a virtual traversal
                        // using a random folder path
                        imagesPath: cx.variables.get('basePath', 'contrexx') + 'random/..',
                        templates: this
                    });
                }).bind(this)(this[i])
            }
            if (typeof this.button != 'undefined') {
                this.button.setState(CKEDITOR.TRISTATE_ENABLE) // Enable "Template"-Button
            }
        }).bind(loadingTemplates)();
    }

    var translations = cx.variables.get('toolbarTranslations', 'toolbarConfigurator');
    if (translations) {
        cx.jQuery('div.toolbarModifier ul[data-type="table-body"] > li[data-type="group"] > ul > li[data-type="subgroup"] > p > span').each(
            function() {
                if (translations.hasOwnProperty(cx.jQuery(this).text())) {
                    var translation = cx.jQuery(this).text();
                    cx.jQuery(this).text(translations[translation]);
                }
            }
        );
    }
});

// add shadowbox functionality
CKEDITOR.on('dialogDefinition', function (event) {
    var editor = event.editor;
    var dialogDefinition = event.data.definition;

    // only add functionality to image dialog
    if (event.data.name != 'image') {
        return;
    }

    // add Shadowbox option to advanced tab
    var advancedTab = dialogDefinition.getContents( 'advanced' );
    if (advancedTab !== null) {
        // add checkbox
        advancedTab.add({
            type: 'checkbox',
            label: '<?php echo $_ARRAYLANG['TXT_WYSIWYG_MODAL_OPTION_LABEL']; ?>',
            id: 'txtdlgGenShadowbox',
            onClick: function() {
                shadowboxSrc = this.getDialog().getContentElement( 'advanced', 'txtdlgGenShadowboxSrc' ).getElement();
                if (this.getValue()) {
                    shadowboxSrc.setStyle('display', 'block');
                    //if (!this.getDialog().getValueOf('advanced', 'txtdlgGenShadowboxSrc')) {
                        var imageSrc = this.getDialog().getValueOf('info', 'txtUrl');
                        var originalImage = imageSrc.replace(/\.thumb_([^.]+)\.(.{3,4})$/, '.$2').replace(/\.thumb$/,'')
                        this.getDialog().setValueOf('advanced', 'txtdlgGenShadowboxSrc', originalImage);
                    //}
                } else {
                    shadowboxSrc.setStyle('display', 'none');
                }
            },
            setup : function( type, element ) {
                //if ( type == LINK ) {}
                imgRel = element.getAttribute('data-shadowbox');
                if (!imgRel) {
                    this.setValue(false);    
                } else {
                    this.setValue(true);
                }
            },
            commit : function( type, element ) {
                //if ( type == LINK ) {
                if ( this.getValue()) {
                    element.setAttribute('data-shadowbox', this.getDialog().getValueOf('advanced', 'txtdlgGenShadowboxSrc'));
                } else {
                    element.removeAttributes(['data-shadowbox']);
                }
            },
        });
        // add input field for shadowbox source 
        advancedTab.add({
            type: 'text',
            label: '<?php echo $_ARRAYLANG['TXT_WYSIWYG_MODAL_OPTION_SRC']; ?>',
            id: 'txtdlgGenShadowboxSrc',
            style: 'display:none',
            setup : function( type, element ) {
                //if ( type == LINK ) {}
                imgRel = element.getAttribute('data-shadowbox');
                this.setValue(imgRel);
                if (!imgRel) {
                    this.getElement().setStyle('display', 'none');
                } else {
                    this.getElement().setStyle('display', 'block');
                }
            }
        });
    }
});

// hide 'browse'-buttons in case the user is a sole frontend-user
// and is not permitted to access the MediaBrowser or Uploader
if (<?php
        if (\FWUser::getFWUserObject()->objUser->login()) {
            if (\FWUser::getFWUserObject()->objUser->getAdminStatus()) {
                echo 0;
            } else {
                $arrAssociatedGroupIds = \FWUser::getFWUserObject()->objUser->getAssociatedGroupIds();
                foreach ($arrAssociatedGroupIds as $groupId) {
                    $objGroup = \FWUser::getFWUserObject()->objGroup->getGroup($groupId);
                    if ($objGroup) {
                        if ($objGroup->getType() == 'backend') {
                            $isBackendGroup = true;
                            break;
                        }
                    }
                }
                if ($isBackendGroup) {
                    echo 0;
                } else {
                    echo 1;
                }
            }
        } else {
            echo 1;
        }
    ?>) {
    CKEDITOR.on('dialogDefinition', function(ev) {
        var dialogName       = ev.data.name;
        var dialogDefinition = ev.data.definition;

        if (dialogName == 'link') {
            dialogDefinition.getContents('info').remove('browse');
        }

        if (dialogName == 'image') {
            dialogDefinition.getContents('info').remove('browse');
            dialogDefinition.getContents('Link').remove('browse');
        }

        if (dialogName == 'flash') {
            dialogDefinition.getContents('info').remove('browse');
        }
    });
}

// load custom code from Wysiwyg.yml of webdesign template 
<?php
    try {
        echo $wysiwyg->getCustomWysiwygEditorJsCode($skinId) . "\n";
    } catch (\Throwable $t) {
        \DBG::msg($t->getMessage());
    }
?>

//this script will not be executed at the first round (first wysiwyg call)
cx.bind("loadingEnd", function(myArgs) {
    if(myArgs.hasOwnProperty('data')) {
        var data = myArgs['data'];
        if(data.hasOwnProperty('wysiwygCssReload') && (data.wysiwygCssReload).hasOwnProperty('css')) {
            for(var instanceName in CKEDITOR.instances) {
                var is_same = (data.wysiwygCssReload.css).equals(cx.variables.get('css', 'wysiwyg')) && cx.variables.get('css', 'wysiwyg').every(function(element, index) {
                    return element === data.wysiwygCssReload.css[index];
                });
                if(!is_same){
                    //cant set the css on the run, so you must destroy the wysiwyg and recreate it
                    CKEDITOR.instances[instanceName].destroy();
                    cx.variables.set('css', data.wysiwygCssReload.css, 'wysiwyg')
                    cx.variables.set('bodyClass', data.wysiwygCssReload.bodyClass, 'wysiwyg')
                    cx.variables.set('bodyId', data.wysiwygCssReload.bodyId, 'wysiwyg')
                    var config = {
                        customConfig: cx.variables.get('ckeditorconfigpath', 'contentmanager'),
                        toolbar: 'Full',
                        removePlugins: 'bbcode'
                    };
                    CKEDITOR.replace('page[content]', config);
                }
            }
        }
    }
}, "contentmanager");

// attach the .equals method to Array's prototype to call it on any array
Array.prototype.equals = function (array) {
    // if the other array is a falsy value, return
    if (!array) {
        return false;
    }

    // compare lengths - can save a lot of time
    if (this.length != array.length) {
        return false;
    }

    for (var i = 0, l=this.length; i < l; i++) {
        // Check if we have nested arrays
        if (this[i] instanceof Array && array[i] instanceof Array) {
            // recurse into the nested arrays
            if (!this[i].equals(array[i])) {
                return false;
            }
        } else if (this[i] != array[i]) {
            // Warning - two different object instances will never be equal: {x:20} != {x:20}
            return false;
        }
    }
    return true;
};
