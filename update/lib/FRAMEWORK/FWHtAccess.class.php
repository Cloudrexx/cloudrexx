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
 * Class FWHtAccess
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     2.1.0
 * @package     cloudrexx
 * @subpackage  lib_framework
 * @todo        Edit PHP DocBlocks!
 */

/**
 * @ignore
 */
require_once UPDATE_LIB.'/PEAR/File/HtAccess.php';
/**
 * Class FWHtAccess
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     2.1.0
 * @package     cloudrexx
 * @subpackage  lib_framework
 */
class FWHtAccess
{
    /**
     * Status if there is a HtAccess file loaded
     * @var boolean
     * @access private
     */
    private $HtAccessLoaded = false;

    /**
     * Status if the rewrite engine has been activated in the loaded HtAccess file.
     * @var boolean
     * @access private
     */
    private $rewriteEngine = false;

    /**
     * Cloudrexx directive sections
     *
     * This list is used to sort the sections within a HtAccess file.
     * @var array
     * @access private
     */
    private $arrSectionOrder = array(
        'contrexx__core_modules__alias',
        'contrexx__core__language'
    );

    /**
     * An array containing all directives of the loaded HtAccess file.
     * The directives of Cloudrexx sections are represented as a whole by one entry which uses the section name as value.
     * @var array
     * @access private
     */
    private $arrDirectives = array();

    /**
     * A multi-dimensional array containing all directives of the Cloudrexx sections.
     * @var array
     * @access private
     */
    private $arrContrexxDirectives = array();

    /**
     * An instance of the PEAR class File_HtAccess.
     * This object provides an improved handling of HtAccess files.
     *
     * @var object
     * @access private
     */
    private $objHtAccess;

    private $document_root;

    private $path_offset;

    /**
     * Constructor
     *
     * Initializes an object of the PEAR class File_HtAccess.
     */
    public function __construct($document_root, $path_offset)
    {
        $this->document_root = $document_root;
        $this->path_offset = $path_offset;
        $this->objHtAccess = new File_HtAccess();
    }

    /**
     * Load HtAccess file
     *
     * Loads the content of the HtAccess file specified by $filename.
     * The content gets seperated into Cloudrexx related directives and other directives.
     * If the param $prepareFileAccess is set to TRUE, the HtAccess file will be created if it doesn't exist.
     * As well will any write-protection mode on the file be removed.
     *
     * @param string $filename
     * @param boolean $prepareFileAccess
     * @global  array
     * @return mixed    Returns TRUE on success, otherwise a string containing an appropriate error message.
     */
    public function loadHtAccessFile($filename, $prepareFileAccess = true)
    {
        global $_CORELANG;

        $this->objHtAccess->setFile($this->document_root.$filename);
        if ($prepareFileAccess && !$this->prepareFileAccess($filename)) {
            $this->HtAccessLoaded = false;
            return sprintf($_CORELANG['TXT_CORE_HTACCESS_FILE_NOT_WRITABLE'], $this->document_root.$filename);
        }

        if (($result = $this->objHtAccess->load()) === true) {
            $arrAddition = $this->objHtAccess->getAdditional('array');
            $arrSection = array();

            $subSection = false;
            $arrSubSectionList = array();
            $withinSubSection = false;
            $withinSection = false;
            $withinContrexxDirectives = false;

            foreach ($arrAddition as $directive) {
                if (!$withinContrexxDirectives && preg_match('@^\s*#\s*<contrexx>@i', $directive)) {
                    // begin of cloudrexx directives reached
                    // start reading
                    $withinContrexxDirectives = true;
                } elseif ($withinContrexxDirectives && preg_match('@^\s*#\s*</contrexx>@i', $directive)) {
                    // end of cloudrexx directives reached
                    // stop reading
                    $withinContrexxDirectives = false;
                } elseif ($withinContrexxDirectives) {
                    // read directives
                    if (!$withinSection && preg_match('@^\s*#\s*<([a-z][a-z0-9-_]+)>@i', $directive, $arrMatch)) {
                        // begin of a section reached
                        // start parsing section
                        $section= $arrMatch[1];
                        $withinSection = true;
                        $this->arrDirectives[] = 'contrexx__'.$section;
                    } elseif ($withinSection && preg_match('@^\s*#\s*</'.$section.'>@i', $directive)) {
                        // end of section reached
                        // stop parsing section
                        $withinSection = false;
                        $this->arrContrexxDirectives[$section] = $arrSection;
                        $arrSection = array();
                        $arrSubSectionList = array();
                    } elseif ($withinSection) {
                        // parse section
                        if (preg_match('@^\s*#\s*<([a-z][a-z0-9-_]+)>@i', $directive, $arrMatch)) {
                            $newSubSection = $arrMatch[1];

                            if (count($arrSubSectionList)) {
                                // linking the sub section with the parent (sub)section
                                $arrSubSectionList[$subSection][$newSubSection] = array();
                                $arrSubSectionList[$newSubSection] = &$arrSubSectionList[$subSection][$newSubSection];
                            } else {
                                $arrSection[$newSubSection] = array();
                                $arrSubSectionList[$newSubSection] = &$arrSection[$newSubSection];
                            }

                            $subSection = $newSubSection;
                            $withinSubSection = true;
                        } elseif ($withinSubSection && preg_match('@^\s*#\s*</'.$subSection.'>@i', $directive)) {
                            // end of sub section reached
                            // stop parsing sub section
                            unset($arrSubSectionList[$subSection]);
                            $subSection = key($arrSubSectionList);

                            if ($subSection === null) {
                                $withinSubSection = false;
                            }
                        } elseif ($withinSubSection) {
                            // add directive to sub section
                            $arrSubSectionList[$subSection][] = $directive;
                        } else {
                            // add directive to main section
                            $arrSection[] = $directive;
                        }
                    } else {
                        $this->arrContrexxDirectives[] = $directive;
                    }
                } else {
                    if (preg_match('#^\s*RewriteEngine\s+(Off|On)?.*$#i', $directive, $arrEngineStatus) && strtolower($arrEngineStatus[1]) == 'on') {
                        $this->rewriteEngine = true;
                    }
                    $this->arrDirectives[] = $directive;
                }
            }

            if ($withinContrexxDirectives || $withinSection || $withinSubSection) {
                $this->HtAccessLoaded = false;
                return sprintf($_CORELANG['TXT_CORE_INVALID_HTACCESS_FORMAT'], $this->document_root.$filename);
            }

            $this->HtAccessLoaded = true;
            return true;
        }

        $this->arrDirectives = array();
        $this->arrContrexxDirectives = array();

        $this->HtAccessLoaded = false;
        return $result;
    }

    /**
     * Returns either TRUE or FALSE depending on if the HtAccess file had successfully been loaded.
     *
     * @return boolean
     */
    public function isHtAccessFileLoaded()
    {
        return $this->HtAccessLoaded;
    }

    /**
     * Returns either TRUE or FALSE depending on if the Rewrite Engine is activated in the HtAccess file.
     *
     * @return boolean
     */
    public function isRewriteEngineInUse()
    {
        return $this->rewriteEngine;
    }

    /**
     * Get directives of a section
     *
     * Returns all directives that are part of the section specified by $section.
     *
     * @param string $section
     * @return mixed    Returns FALSE if the HtAccess file hasn't been loaded. Otherwise it returns an array containing the HtAccess directives.
     */
    function getSection($section)
    {
        if (!$this->HtAccessLoaded) {
            return false;
        }

        return isset($this->arrContrexxDirectives[$section]) ? $this->arrContrexxDirectives[$section] : array();
    }

    /**
     * Set directives of a section
     *
     * Sets the directives specified by $arrDirectives of the section specified by $section.
     * It also sorts the sections according to the oder in $this->arrSortOrder.
     *
     * @param string $section
     * @param array $arrDirectives
     */
    public function setSection($section, $arrDirectives)
    {
        if (!isset($this->arrContrexxDirectives[$section])) {
            $this->arrDirectives[] = 'contrexx__'.$section;
        }

        $this->arrContrexxDirectives[$section] = $arrDirectives;


        // sort cloudrexx directive sections
        $arrContrexxDirectives = preg_grep('@^('.implode('|', $this->arrSectionOrder).')$@i', $this->arrDirectives);
        $arrContrexxDirectivesKeys = array_keys($arrContrexxDirectives);

        usort($arrContrexxDirectives, array($this, 'sortContrexxSections'));

        $i = 0;
        foreach ($arrContrexxDirectivesKeys as $key) {
            $this->arrDirectives[$key] = $arrContrexxDirectives[$i++];
        }
    }

    /**
     * Remove section specified by $section from the HtAccess file.
     *
     * @param string $section
     */
    public function removeSection($section)
    {
        $this->arrDirectives = preg_grep('@^contrexx__'.$section.'$@i', $this->arrDirectives, PREG_GREP_INVERT);
        unset($this->arrContrexxDirectives[$section]);
    }

    /**
     * Write the HtAccess file
     *
     * @return boolean
     */
    public function write()
    {
        if (!$this->HtAccessLoaded) {
            return false;
        }

        $arrDirectives = array();
        $withinContrexxDirective = false;

        if (!$this->rewriteEngine) {
            $arrDirectives[] = 'RewriteEngine On';
        }

        foreach ($this->arrDirectives as $directive) {
            if (preg_match('@^contrexx__([a-z][a-z0-9-_]+)$@i', $directive, $contrexxDirective)) {
                if (!$withinContrexxDirective) {
                    $arrDirectives[] = "# <contrexx>";
                    $withinContrexxDirective = true;
                }
                $arrDirectives[] = "#\t<{$contrexxDirective[1]}>";
                $arrDirectives = $this->serialize($this->arrContrexxDirectives[$contrexxDirective[1]], $arrDirectives);
                $arrDirectives[] = "#\t</{$contrexxDirective[1]}>";
            } else {
                if ($withinContrexxDirective) {
                    $arrDirectives[] = "# </contrexx>";
                    $withinContrexxDirective = false;
                }
                $arrDirectives[] = $directive;
            }
        }

        if ($withinContrexxDirective) {
            $arrDirectives[] = "# </contrexx>";
            $withinContrexxDirective = false;
        }

        $this->objHtAccess->setAdditional($arrDirectives);
        if ($this->objHtAccess->save() !== true) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Prepare file access
     *
     * Creates the file specified by $path if it doesn't exist
     * and removes the write-protection mode on the file if there's one.
     *
     * @param string $path
     * @return boolean  Returns TRUE if the specified file exists and has no write-protection on it at the end. Returns FALSE if something fails.
     */
    private function prepareFileAccess($path)
    {
        $file = $this->document_root.$path;

        return (
                file_exists($file)
                || \Cx\Lib\FileSystem\FileSystem::touch($file)
            ) && (
                \Cx\Lib\FileSystem\FileSystem::makeWritable($file)
        );
    }

    /**
     * Sort method used by usort() to sort the sections according to the order in $this->arrSortOrder.
     *
     * @param string $a
     * @param string $b
     * @return integer
     */
    private function sortContrexxSections($a, $b)
    {
        return (array_search($a, $this->arrSectionOrder) < array_search($b, $this->arrSectionOrder)) ? -1 : 1;
    }

    /**
     * Serialize a multi-dimensional array into a single-dimensional array
     * @param array $array
     * @param array $return
     * @param integer $level
     * @return array
     */
    private function serialize($array, $return = array(), $level = 2)
    {
        foreach ($array as $section => $element) {
            if (is_array($element)) {
                $return[] = "#".str_repeat("\t", $level)."<$section>";
                $return = $this->serialize($element, $return, $level + 1);
                $return[] = "#".str_repeat("\t", $level)."</$section>";
            } else {
                $return[] = str_repeat("\t", $level).$element;
            }
        }

        return $return;
    }

    /**
     * Check if the System is running on a Apache webserver
     * @return boolean
     */
    public function checkForApacheServer()
    {
        return preg_match('#apache#i', $_SERVER['SERVER_SOFTWARE']);
    }

    /**
     * Check if the Apache modul mod_rewrite is loaded
     * @return boolean
     */
    public function checkForModRewriteModul()
    {
        ob_start();
        phpinfo(INFO_MODULES);
        $phpinfo = ob_get_contents();
        ob_end_clean();

        return preg_match('#mod_rewrite#i', $phpinfo);
    }

}
?>
