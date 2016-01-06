<?php

/**
 * Data
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Stefan Heinemann <sh@comvation.com>
 * @version        $Id: index.inc.php,v 1.00 $
 * @package     contrexx
 * @subpackage  module_data
 */

namespace Cx\Modules\Data\Controller;
/**
 * DataBlocks
 *
 * This class parses the Placeholder for Data in the content and layout
 * pages.
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Stefan Heinemann <sh@comvation.com>
 * @version        $Id: index.inc.php,v 1.00 $
 * @package     contrexx
 * @subpackage  module_data
 *
 */
class DataBlocks extends \Cx\Modules\Data\Controller\DataLibrary
{
    public $entryArray = false;
    public $categories = false;
    public $langId;
    public $active = false;
    public $arrCategories = null;
    public $langVars = array();

    /**
     * Constructor for PHP5
     *
     * @param int $lang
     */
    function __construct()
    {
        global $objInit;

        \Cx\Core\Setting\Controller\Setting::init('Config', 'component','Yaml');
        
        if (\Cx\Core\Setting\Controller\Setting::getValue('dataUseModule')) {
            $this->active = true;
        } else {
            return;
        }
        $this->_arrSettings = $this->createSettingsArray();
        $this->_objTpl = new \Cx\Core\Html\Sigma(ASCMS_THEMES_PATH);
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->langVars = $objInit->loadLanguageData('Data');
    }


    /**
     * Do the replacements
     * @param string $data The pages on which the replacement should be done
     * @return string
     */
    function replace($data)
    {
        if ($this->active) {
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    $data[$key] = $this->replace($value);
                }
            } else {
                $matches = array();
                if (preg_match_all('/\{DATA_[A-Z_0-9]+\}/', $data, $matches) > 0) {
                    foreach ($matches[0] as $match) {
                        $data = str_replace($match, $this->getData($match), $data);
                    }
                }
            }
        }
        return $data;
    }


    /**
     * Get the replacement content for the placeholder
     * @param string $placeholder
     * @return string
     */
    function getData($placeholder)
    {
        global $objDatabase;

        \JS::activate("shadowbox", array('players' => array('html', 'iframe')));
        $matter = substr($placeholder, 6, -1);
        if ($matter == "OVERVIEW")  {
            return $this->getOverview();
        }

        // get the data id for the placeholder
        $query = "
            SELECT type, ref_id
              FROM ".DBPREFIX."module_data_placeholders
             WHERE placeholder='$matter'";
        $objRs = $objDatabase->Execute($query);
        if ($objRs && $objRs->RecordCount()) {
            $id = $objRs->fields['ref_id'];
            if ($objRs->fields['type'] == "cat") {
                $this->_arrLanguages = $this->createLanguageArray();
                $this->arrCategories = $this->createCategoryArray();
                if ($this->arrCategories[$id]['action'] == "subcategories") {
                    return $this->getSubcategories($id);
                }
                return $this->getCategory($id);
            } else {
                return $this->getDetail($id);
            }
        }
        return '';
    }


    /**
     * Get the subcategories of a category
     * @param int $id
     * @return string
     */
    function getSubcategories($id)
    {
        $categories = "";
        foreach ($this->arrCategories as $catid => $cat) {
            if ($cat['parent_id'] == $id) {
                if ($cat['active']) {
                    $categories .= $this->getCategory($catid, $id);
                }
            }
        }
        $this->_objTpl->parse("datalist_category");
        return $categories;
    }


    /**
     * Get a category and its entries
     * @param int $id
     * @return string
     */
    function getCategory($id, $parcat=0)
    {
        global $_LANGID;


        if ($this->entryArray == 0) {
            $this->entryArray = $this->createEntryArray();
        }

        if ($parcat == 0) {
            $this->_objTpl->setTemplate($this->adjustTemplatePlaceholders($this->arrCategories[$id]['template']));
        } else {
            $this->_objTpl->setTemplate($this->adjustTemplatePlaceholders($this->arrCategories[$parcat]['template']));
        }

        $lang = $_LANGID;
        $width = $this->arrCategories[$id]['box_width'];
        $height = $this->arrCategories[$id]['box_height'];

        if ($parcat) {
            $this->_objTpl->setVariable("CATTITLE", $this->arrCategories[$id][$_LANGID]['name']);
        }
        if ($this->arrCategories[$id]['action'] == "content") {
            $cmd = $this->arrCategories[$id]['cmd'];
            $url = "index.php?section=Data&amp;cmd=".$cmd;
        } else {
            $url = "index.php?section=Data&amp;act=shadowbox&amp;lang=".$lang;
        }

        foreach ($this->entryArray as $entryId => $entry) {
            if (!$entry['active'] || !$entry['translation'][$_LANGID]['is_active'])
                continue;

            // check date
            if ($entry['release_time'] != 0) {
                if ($entry['release_time'] > time())
                    // too old
                    continue;

                // if it is not endless (0), check if 'now' is past the given date
                if ($entry['release_time_end'] !=0 && time() > $entry['release_time_end'])
                    continue;
            }

            if ($this->categoryMatches($id, $entry['categories'][$_LANGID])) {

                $translation = $entry['translation'][$_LANGID];
                $image = '';
                if (!empty($translation['thumbnail'])) {
                    if ($translation['thumbnail_type'] == 'original') {
                        $image = $translation['thumbnail'];
                    } else {
                        $image = \ImageManager::getThumbnailFilename(
                            $translation['thumbnail']
                        );
                    }
                } else {
                    $path = \ImageManager::getThumbnailFilename(
                        $translation['image']
                    );
                    if (file_exists(ASCMS_PATH.$path)) {
                        $image = $path;
                    }
                }

                if (!empty($image)) {
                    $image = '<img src='.$image.' alt=\"\" style=\"float: left\" />';
                } else {
                    $image = '';
                }
                
                if ($entry['mode'] == "normal") {
                    $href = $url."&amp;id=".$entryId;
                } else {
                    $href = $entry['translation'][$_LANGID]['forward_url'];
                }

                if (!empty($entry['translation'][$_LANGID]['forward_target'])) {
                    $target = "target=\"".$entry['translation'][$_LANGID]['forward_target']."\"";
                } else {
                    $target = "";
                }

                $title = $entry['translation'][$_LANGID]['subject'];
                $content = $this->getIntroductionText($entry['translation'][$_LANGID]['content']);
                $this->_objTpl->setVariable(array(
                    "TITLE"         => $title,
                    "IMAGE"         => $image,
                    "CONTENT"       => $content,
                    "HREF"          => $href,
                    "TARGET"        => $target,
                    "CLASS"         => ($this->arrCategories[$id]['action'] == "overlaybox" && $entry['mode'] == "normal") ? "rel=\"shadowbox;width=".$width.";height=".$height."\"" : "",
                    "TXT_MORE"      => $this->langVars['TXT_DATA_MORE'],
                ));
                if ($parcat) {
                    $this->_objTpl->parse("entry");
                } else {
                    $this->_objTpl->parse("single_entry");
                }
            }
        }
        if ($parcat) {
            $this->_objTpl->parse("category");
        } else {
            $this->_objTpl->parse("datalist_single_category");
        }
        return $this->_objTpl->get();
    }


    /**
     * Get a single entry view
     * @param int $id
     * @return string
     */
    function getDetail($id)
    {
        global $_LANGID;

        if ($this->entryArray === false) {
            $this->entryArray = $this->createEntryArray();
        }

        $entry = $this->entryArray[$id];
        $title = $entry['translation'][$_LANGID]['subject'];
        $content = $this->getIntroductionText($entry['translation'][$_LANGID]['content']);

        $this->_objTpl->setTemplate($this->adjustTemplatePlaceholders($this->_arrSettings['data_template_entry']));

        $translation = $entry['translation'][$_LANGID];
        $image = '';
        if (!empty($translation['thumbnail'])) {
            if ($translation['thumbnail_type'] == 'original') {
                $image = $translation['thumbnail'];
            } else {
                $image = \ImageManager::getThumbnailFilename(
                    $translation['thumbnail']
                );
            }
        } else {
            $path = \ImageManager::getThumbnailFilename(
                $translation['image']
            );
            if (file_exists(ASCMS_PATH.$path)) {
                $image = $path;
            }
        }

        if (!empty($image)) {
            $image = '<img src='.$image.' alt=\"\" style=\"float: left\" />';
        } else {
            $image = '';
        }

        $lang = $_LANGID;
        $width = $this->_arrSettings['data_shadowbox_width'];
        $height = $this->_arrSettings['data_shadowbox_height'];

        if ($entry['mode'] == "normal") {
            if ($this->_arrSettings['data_entry_action'] == "content") {
                $cmd = $this->_arrSettings['data_target_cmd'];
                $url = \Cx\Core\Routing\Url::fromModuleAndCmd('Data', $cmd, '', array('id' => $id));
            } else {
                $url = \Cx\Core\Routing\Url::fromModuleAndCmd('Data', '', '', array('height' => $height, 'width' => $width, 'id' => $id, 'lang' => $lang ));
            }
        } else {
            $url = $entry['translation'][$_LANGID]['forward_url'].'&amp;id='.$id;
        }

        $templateVars = array(
            "TITLE"         => $title,
            "IMAGE"         => $image,
            "CONTENT"       => $content,
            "HREF"          => $url,
            "CLASS"         => ($this->_arrSettings['data_entry_action'] == "overlaybox" && $entry['mode'] =="normal") ? "rel=\"shadowbox;width=".$width.";height=".$height."\"" : "",
            "TXT_MORE"      => $this->langVars['TXT_DATA_MORE']
        );
        $this->_objTpl->setVariable($templateVars);

        $this->_objTpl->parse("datalist_entry");
        return $this->_objTpl->get();
    }


    /**
     * Make the [[PLACEHOLDERS]] to {PLACEHOLDER}
     * @param string $str
     * @return string
     */
    function adjustTemplatePlaceholders($str)
    {
        return preg_replace('/\[\[([A-Z_]+)\]\]/', '{$1}', $str);
    }

}

