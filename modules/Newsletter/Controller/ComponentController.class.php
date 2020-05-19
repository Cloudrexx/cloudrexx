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
 * Main controller for Newsletter
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_newsletter
 */

namespace Cx\Modules\Newsletter\Controller;

/**
 * Main controller for Newsletter
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_newsletter
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {
    public function getControllerClasses() {
        // Return an empty array here to let the component handler know that there
        // does not exist a backend, nor a frontend controller of this component.
        return array();
    }

    /**
     * Returns a list of command mode commands provided by this component
     *
     * @return array List of command names
     */
    public function getCommandsForCommandMode()
    {
        return array('Newsletter');
    }

    /**
     * Returns the description for a command provided by this component
     * @param string $command The name of the command to fetch the description from
     * @param boolean $short Wheter to return short or long description
     * @return string Command description
     */
    public function getCommandDescription($command, $short = false) {
        switch ($command) {
            case 'Newsletter':
                $desc = 'Group-based newsletter system';
                if ($short) {
                    return $desc;
                }
                $desc .= PHP_EOL . PHP_EOL . 'autoclean' . "\t" . 'Cleanup unsuccessul registrations';
                return $desc;
                break;
        }
    }

    /**
     * Execute api command
     *
     * @param string $command Name of command to execute
     * @param array  $arguments List of arguments for the command
     * @param array  $dataArguments (optional) List of data arguments for the command
     */
    public function executeCommand($command, $arguments, $dataArguments = array())
    {
        $subcommand = null;
        if (!empty($arguments[0])) {
            $subcommand = $arguments[0];
        }

        // define frontend language
        if (!defined('FRONTEND_LANG_ID')) {
            define('FRONTEND_LANG_ID', 1);
        }

        switch ($command) {
            case 'Newsletter':
                switch ($subcommand) {
                    case 'autoclean':
                        $newsletterLib = new NewsletterLib();
                        $newsletterLib->autoCleanRegisters();
                        break;

                    case 'View':
                        Newsletter::displayInBrowser();
                        // execution is never reached, as Newsletter::displayInBrowser
                        // does generate its own response
                        break;
                }
                break;
            default:
                break;
        }
    }

     /**
     * Load your component.
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $_CORELANG, $objTemplate, $subMenuTitle;
        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                $newsletter = new Newsletter(\Env::get('cx')->getPage()->getContent());
                \Env::get('cx')->getPage()->setContent($newsletter->getPage());
                break;

            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:
                $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
                $objTemplate = $this->cx->getTemplate();

                $subMenuTitle = $_CORELANG['TXT_CORE_EMAIL_MARKETING'];
                $objNewsletter = new NewsletterManager();
                $objNewsletter->getPage();
                break;
        }
    }

    /**
     * Do something after resolving is done
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function postResolve(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $command;
        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                if (Newsletter::isTrackLink()) {
                    //handle link tracker from newsletter, since user should be redirected to the link url
                    /*
                    * Newsletter Module
                    *
                    * Generates no output, requests are answered by a redirect to foreign site
                    *
                    */
                    Newsletter::trackLink();
                    //execution should never reach this point, but let's be safe and call exit anyway
                    exit;
                }
                // regular newsletter request (like subscribing, profile management, etc).
                // must not abort by an exit call here!
                break;
        }
    }

    /**
     * Do something before content is loaded from DB
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function preContentLoad(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $newsletter, $_ARRAYLANG, $page_template, $themesPages, $objInit;
        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                // get Newsletter
                $_ARRAYLANG = array_merge($_ARRAYLANG, $objInit->loadLanguageData('Newsletter'));
                $newsletter = new Newsletter('');
                $content = \Env::get('cx')->getPage()->getContent();
                if (preg_match('/{NEWSLETTER_BLOCK}/', $content)) {
                    $newsletter->setBlock($content);
                }
                if (preg_match('/{NEWSLETTER_BLOCK}/', $page_template)) {
                    $newsletter->setBlock($page_template);
                }
                if (preg_match('/{NEWSLETTER_BLOCK}/', $themesPages['index'])) {
                    $newsletter->setBlock($themesPages['index']);
                }
                break;
        }
    }
}
