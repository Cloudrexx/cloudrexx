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
 * Class EsiWidgetController
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_contentmanager
 * @version     1.0.0
 */

namespace Cx\Core\ContentManager\Controller;

/**
 * JsonAdapter Controller to handle EsiWidgets
 * Usage:
 * - Create a subclass that implements parseWidget()
 * - Register it as a Controller in your ComponentController
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_contentmanager
 * @version     1.0.0
 */

class EsiWidgetController extends \Cx\Core_Modules\Widget\Controller\EsiWidgetController {

    /**
     * Parses a widget
     *
     * @param string                                 $name     Widget name
     * @param \Cx\Core\Html\Sigma                    $template Widget Template
     * @param \Cx\Core\Routing\Model\Entity\Response $response Response object
     * @param array                                  $params   Get parameters
     */
    public function parseWidget($name, $template, $response, $params)
    {
        if ($name === 'TXT_CORE_LAST_MODIFIED_PAGE') {
            $arrayLang = \Env::get('init')->getComponentSpecificLanguageData(
                'Core',
                true,
                $params['locale']->getId()
            );
            $template->setVariable(
                $name,
                $arrayLang['TXT_CORE_LAST_MODIFIED_PAGE']
            );
            return;
        }

        $page = $params['page'];
        if (!$page) {
            return;
        }

        switch ($name) {
            case 'TITLE':
            case 'NAVTITLE':
                $widgetValue = contrexx_raw2xhtml($page->getTitle());
                break;

            case 'METAKEYS':
            case 'METADESC':
            case 'METAIMAGE':
                $widgetValue = '';
                if (!$page->getMetarobots()) {
                    break;
                }
            case 'METATITLE':
                $methodName  = 'get' . ucfirst(strtolower($name));
                $widgetValue = contrexx_raw2xhtml($page->$methodName());
                if ($name === 'METAIMAGE' && empty($widgetValue)) {
                    $widgetValue = contrexx_raw2xhtml(
                        \Cx\Core\Setting\Controller\Setting::getValue(
                            'defaultMetaimage',
                            'Config'
                        )
                    );
                }
                break;

            case 'METAIMAGE_WIDTH':
            case 'METAIMAGE_HEIGHT':
                $widgetValue = '';
                if (!$page->getMetarobots()) {
                    break;
                }

                $image = $page->getMetaimage();

                // todo: load image path from MediaSource
                $imagePath = $this->cx->getWebsiteDocumentRootPath() . $image;

                // todo: check if file exists through related
                // method from MediaSource file system
                if (empty($image) || !file_exists($imagePath)) {
                    $image = contrexx_raw2xhtml(
                        \Cx\Core\Setting\Controller\Setting::getValue(
                            'defaultMetaimage',
                            'Config'
                        )
                    );
                    $imagePath = $this->cx->getWebsiteDocumentRootPath() . $image;
                }

                if (!file_exists($imagePath)) {
                    break;
                }
                $imageInfo = getimagesize($imagePath);
                $imageWidth = null; 
                $imageHeight = null;
                if (!empty($imageInfo) &&
                    isset($imageInfo[0]) &&
                    isset($imageInfo[1])
                ) {
                    $imageWidth = $imageInfo[0];
                    $imageHeight = $imageInfo[1];
                }

                if ($name == 'METAIMAGE_WIDTH') {
                    $widgetValue = $imageWidth;
                } else {
                    $widgetValue = $imageHeight;
                }
                
                break;

            case 'METAROBOTS':
                $widgetValue = 'none';
                if ($page->getMetarobots()) {
                    $widgetValue = 'all';
                }
                break;

            case 'CONTENT_TITLE':
                $widgetValue = $page->getContentTitle();
                break;

            case 'CONTENT_TEXT':
                $widgetValue = $page->getContent();
                break;

            case 'CSS_NAME':
                $widgetValue = contrexx_raw2xhtml($page->getCssName());
                break;

            case 'LAST_MODIFIED_PAGE':
                $dateTime     = $this->cx->getComponent('DateTime');
                $modifiedDate = $dateTime->db2user($page->getUpdatedAt());
                $widgetValue  = $modifiedDate->format(ASCMS_DATE_FORMAT_DATE);
                break;

            case 'CANONICAL_LINK':
                $widgetValue = '';

                // fetch canonical-link
                try {
                    $link = $this->getSystemComponentController()->fetchAlreadySetCanonicalLink($response);
                } catch (\Exception $e) {
                    // no Link header set -> page doesn't have a canonical-link
                    break;
                }

                $widgetValue = (string) $link;
                break;
        }
        $template->setVariable($name, $widgetValue);
    }
}
