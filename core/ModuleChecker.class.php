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
 * Module Checker
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     2.0.0
 * @package     cloudrexx
 * @subpackage  core
 */

namespace Cx\Core
{

    /**
     * Module Checker
     * Checks for installed and activated modules
     *
     * @copyright   CLOUDREXX CMS - CLOUDREXX AG
     * @author      Cloudrexx Development Team <info@cloudrexx.com>
     * @version     2.0.0
     * @package     cloudrexx
     * @subpackage  core
     */
    class ModuleChecker
    {

        /**
         * Entity Manager
         *
         * @access  private
         * @var     EntityManager
         */
        private $em = null;

        /**
         * Database
         *
         * @access  private
         * @var     ADONewConnection
         */
        private $db = null;

        /**
         * ClassLoader
         *
         * @access  private
         * @var     \Cx\Core\ClassLoader\ClassLoader
         */
        private $cl = null;

        /**
         * Names of all core modules
         *
         * @access  private
         * @var     array
         */
        private $arrCoreModules = array();

        /**
         * Names of all modules (except core modules)
         *
         * @access  private
         * @var     array
         */
        private $arrModules = array();

        /**
         * Names of active modules
         *
         * @access  private
         * @var     array
         */
        private $arrActiveModules = array();

        /**
         * Names of installed modules
         *
         * @access  private
         * @var     array
         */
        private $arrInstalledModules = array();

        private static $instance = null;

        public static function getInstance($em, $db, $cl) {
            if (!self::$instance) {
                self::$instance = new static($em, $db, $cl);
            }
            return self::$instance;
        }

        /**
         * Constructor
         *
         * @access  public
         * @param   EntityManager                     $em
         * @param   ADONewConnection                  $db
         * @param   \Cx\Core\ClassLoader\ClassLoader  $cl
         */
        private function __construct($em, $db, $cl){
            $this->em = $em;
            $this->db = $db;
            $this->cl = $cl;

            $this->init();
        }

        /**
         * Initialisation
         *
         * @access  private
         */
        private function init()
        {
            // check the content for installed and used modules
            $arrCmActiveModules = array();
            $arrCmInstalledModules = array();
            $qb = $this->em->createQueryBuilder();
            $qb->add('select', 'p')
                ->add('from', 'Cx\Core\ContentManager\Model\Entity\Page p')
                ->add('where',
// TODO: what is the proper syntax for non-empty values?
// TODO: add additional check for module != NULL
                    $qb->expr()->neq('p.module', $qb->expr()->literal(''))
                );
            $pages = $qb->getQuery()->getResult();
            foreach ($pages as $page) {
                $arrCmInstalledModules[] = $page->getModule();
                if ($page->isActive()) {
                    $arrCmActiveModules[] = $page->getModule();
                }
            }

            $arrCmInstalledModules = array_unique($arrCmInstalledModules);
            $arrCmActiveModules = array_unique($arrCmActiveModules);

            // add static modules
            $arrCmInstalledModules[] = 'Block';
            $arrCmInstalledModules[] = 'Crm';
            $arrCmInstalledModules[] = 'Order';
            $arrCmInstalledModules[] = 'Pim';
            $arrCmInstalledModules[] = 'Support';
            $arrCmActiveModules[] = 'Block';
            $arrCmInstalledModules[] = 'upload';
            $arrCmActiveModules[] = 'upload';

            $objResult = $this->db->Execute('SELECT `name`, `is_core`, `is_required` FROM `'.DBPREFIX.'modules`');
            if ($objResult !== false) {
                while (!$objResult->EOF) {
                    $moduleName = $objResult->fields['name'];

                    if ($moduleName == 'News') {
                        $this->arrModules[] = $moduleName;
                        //$this->arrCoreModules[] = $moduleName;
                        if (in_array($moduleName, $arrCmInstalledModules)) {
                            $this->arrInstalledModules[] = $moduleName;
                            if (in_array($moduleName, $arrCmInstalledModules)) {
                                $this->arrActiveModules[] = $moduleName;
                            }
                        }
                        $objResult->MoveNext();
                        continue;
                    }

                    if (!empty($moduleName)) {
                        $isCore = $objResult->fields['is_core'];

                        if ($isCore == 1) {
                            $this->arrCoreModules[] = $moduleName;
                        } else {
                            $this->arrModules[] = $moduleName;
                        }

                        if ((in_array($moduleName, $arrCmInstalledModules)) &&
                            ($isCore || (!$isCore && is_dir($this->cl->getFilePath(ASCMS_MODULE_PATH.'/'.$moduleName))))
                        ) {
                            $this->arrInstalledModules[] = $moduleName;
                        }

                        if ((in_array($moduleName, $arrCmActiveModules)) &&
                            ($isCore || (!$isCore && is_dir($this->cl->getFilePath(ASCMS_MODULE_PATH.'/'.$moduleName))))
                        ) {
                            $this->arrActiveModules[] = $moduleName;
                        }
                    }

                    $objResult->MoveNext();
                }
            }
        }

        /**
         * Checks if the passed module is a core module.
         *
         * @access  public
         * @param   string      $moduleName
         * @return  boolean
         */
        public function isCoreModule($moduleName)
        {
            // Workaround due to customizing to set News is Module
            // so it could be used as main navigation entry in backend.
            if ($moduleName == 'News') {
                return true;
            }
            return in_array($moduleName, $this->arrCoreModules);
        }

        /**
         * Checks if the passed module is active
         * (application page exists and is active).
         *
         * @access  public
         * @param   string      $moduleName
         * @return  boolean
         */
        public function isModuleActive($moduleName)
        {
            return in_array($moduleName, $this->arrActiveModules);
        }

        /**
         * Checks if the passed module is installed
         * (application page exists).
         *
         * @access  public
         * @param   string      $moduleName
         * @return  boolean
         */
        public function isModuleInstalled($moduleName)
        {
            return in_array($moduleName, $this->arrInstalledModules);
        }

        /**
         * Returns the cloudrexx core modules
         * @return array List of core modules
         */
        public function getCoreModules()
        {
            return $this->arrCoreModules;
        }

        /**
         * Returns the cloudrexx modules
         * @return array List of modules
         */
        public function getModules()
        {
            return $this->arrModules;
        }

        /**
         * Returns the installed cloudrexx modules
         * @return array List of installed modules
         */
        public function getInstalledModules()
        {
            return $this->arrInstalledModules;
        }

        /**
         * Returns the active cloudrexx modules
         * @return array List of active modules
         */
        public function getActiveModules()
        {
            return $this->arrActiveModules;
        }
    }
}
