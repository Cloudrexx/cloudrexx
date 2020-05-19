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
 * JsonAdapter Controller to handle EsiWidgets
 *
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage coremodules_widget
 */

namespace Cx\Core_Modules\Widget\Controller;

/**
 * Exception for wrong usage of ESI widget
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage coremodules_widget
 */
class EsiWidgetControllerException extends \Exception {}

/**
 * JsonAdapter Controller to handle EsiWidgets
 * Usage:
 * - Create a subclass that implements parseWidget()
 * - Register it as a Controller in your ComponentController
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage coremodules_widget
 */
abstract class EsiWidgetController extends \Cx\Core\Core\Model\Entity\Controller implements \Cx\Core\Json\JsonAdapter {

    /**
     * Holds 
     * @var \Cx\Core\ContentManager\Model\Entity\Page
     */
    protected static $esiParamPage = null;

    /**
     * Returns the internal name used as identifier for this adapter
     * @see \Cx\Core\Json\JsonAdapter::getName()
     * @return string Name of this adapter
     */
    public function getName() {
        return parent::getName() . 'Widget';
    }

    /**
     * Returns all messages as string
     * @see \Cx\Core\Json\JsonAdapter::getMessagesAsString()
     * @return String HTML encoded error messages
     */
    public function getMessagesAsString() {
        return '';
    }

    /**
     * Returns an array of method names accessable from a JSON request
     * @see \Cx\Core\Json\JsonAdapter::getMessagesAsString()
     * @return array List of method names
     */
    public function getAccessableMethods() {
        return array(
            'getWidget',
        );
    }

    /**
     * Returns default permission as object
     * @see \Cx\Core\Json\JsonAdapter::getMessagesAsString()
     * @return Cx\Core_Modules\Access\Model\Entity\Permission Required permission
     */
    public function getDefaultPermissions() {
        return new \Cx\Core_Modules\Access\Model\Entity\Permission(array(), array(), false);
    }

    /**
     * Returns the content of a widget
     * @param array $params JsonAdapter parameters
     * @return array Content in an associative array
     */
    public function getWidget($params) {
        if (
            !isset($params['get']) ||
            !isset($params['get']['name'])
        ) {
            throw new \InvalidArgumentException('Param "name" not set');
        }
        $widget = $this->getComponent('Widget')->getWidget($params['get']['name']);
        $requiredParamsForWidgetsWithContent = array(
            'theme',
            'page',
            'locale',
            'targetComponent',
            'targetEntity',
            'targetId',
            'channel',
        );
        // TODO: We should check at least all ESI params of this widget
        $requiredParams = array();
        if ($widget->getType() == \Cx\Core_Modules\Widget\Model\Entity\Widget::TYPE_BLOCK) {
            $requiredParams = $requiredParamsForWidgetsWithContent;
        }
        foreach ($requiredParams as $requiredParam) {
            if (!isset($params['get'][$requiredParam])) {
                throw new \InvalidArgumentException('Param "' . $requiredParam . '" not set');
            }
        }

        // resolve widget template
        return $this->internalParseWidget($widget, $params);
    }

    /**
     * Parses a widget
     * @param \Cx\Core_Modules\Widget\Model\Entity\Widget $widget The Widget
     * @param array $params Params passed by ESI (/API) request
     * @return array Content in an associative array
     */
    protected function internalParseWidget($widget, $params) {
        $widgetContent = '';

        // ensure that the params can be fetched during internal parsing
        $backupGetParams = $_GET;
        $backupRequestParams = $_REQUEST;
        $_GET = $params['get'];
        $_REQUEST = $params['get'];
        if (isset($params['post'])) {
            $_REQUEST += $params['post'];
        }

        $params = $this->objectifyParams($params);
        if ($widget->getType() != \Cx\Core_Modules\Widget\Model\Entity\Widget::TYPE_BLOCK) {
            $widgetContent = '{' . $params['get']['name'] . '}';
        } else {
            $widgetTemplate = $this->getComponent('Widget')->getWidgetContent(
                $params['get']['name'],
                $params['get']['theme'],
                $params['get']['page'],
                $params['get']['targetComponent'],
                $params['get']['targetEntity'],
                $params['get']['targetId'],
                $params['get']['channel']
            );
            if ($widgetTemplate->blockExists($params['get']['name'])) {
                $widgetContent = $widgetTemplate->getUnparsedBlock(
                    $params['get']['name']
                );
            }
        }
        $widgetTemplate = new \Cx\Core\Html\Sigma();
        \LinkGenerator::parseTemplate($widgetContent);
        $this->cx->parseGlobalPlaceholders($widgetContent);
        $widgetTemplate->setTemplate($widgetContent);

        $targetComponent = $params['get']['targetComponent'];
        $targetEntity = $params['get']['targetEntity'];
        $targetId = $params['get']['targetId'];
        if ($widget->hasCustomParseTarget()) {
            $targetComponent = $widget->getCustomParseTarget()->getComponentController()->getName();
            $targetEntity = (new \ReflectionClass($widget->getCustomParseTarget()))->getShortName();
            $targetId = $widget->getCustomParseTarget()->getId();
        }
        $this->getComponent('Widget')->parseWidgets(
            $widgetTemplate,
            $targetComponent,
            $targetEntity,
            $targetId,
            array($params['get']['name'])
        );
        $this->parseWidget(
            $params['get']['name'],
            $widgetTemplate,
            $params['response'],
            $params['get']
        );
        $_GET = $backupGetParams;
        $_REQUEST = $backupRequestParams;
        $content = $widgetTemplate->get();

        $content = preg_replace('/\\[\\[([A-Z0-9_-]+)\\]\\]/', '{\\1}', $content);
        \LinkGenerator::parseTemplate($content);
        $ls = new \LinkSanitizer(
            $this->cx,
            $this->cx->getWebsiteOffsetPath() . \Env::get('virtualLanguageDirectory') . '/',
            $content
        );
        return array(
            'content' => $ls->replace(),
        );
    }

    /**
     * This makes object of the given params (if possible)
     * Known params are page, lang, user, theme, channel, country, currency and ref
     * @param array $params Associative array of params
     * @return array Associative array of params
     */
    protected function objectifyParams($params) {
        $possibleGetParams = array(
            'page' => function($pageId) use ($params) {
                $page = null;
                if (!isset(static::$esiParamPage[$pageId])) {
                    $em = $this->cx->getDb()->getEntityManager();
                    $pageRepo = $em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
                    $page = $pageRepo->findOneById($pageId);
                    if (!$page) {
                        return;
                    }
                    if ($page->getType() == \Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION) {
                        // get referrer
                        $fragments = array();
                        if (!empty($params['get']['path'])) {
                            // -> get additional path fragments
                            $fragments = explode(
                                '/',
                                $this->getComponent('Widget')->decode(
                                    $params['get']['path']
                                )
                            );
                        }

                        // get the component
                        $pageComponent = $this->getComponent($page->getModule());
                        // resolve additional path fragments (if any)
                        // Note: This must be done only once. If we would do
                        // this for every ESI-call, each time the resolve()
                        // hook will be called, it would process the previously
                        // resolved page. This would alter the resolved page
                        // for every widget being processed.
                        $pageComponent->resolve($fragments, $page);
                    }

                    // cache resolved page for all other ESI-calls that
                    // will follow
                    static::$esiParamPage[$pageId] = $page;
                }
                
                if (!$page) {
                    $page = static::$esiParamPage[$pageId];
                }

                if ($page->getType() == \Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION) {
                    $headers = $params['response']->getRequest()->getHeaders();
                    // get the component
                    $pageComponent = $this->getComponent($page->getModule());
                    // adjust response
                    $params['response']->setPage($page);
                    $pageComponent->adjustResponse($params['response']);
                }

                return $page;
            },
            'locale' => function($langCode) {
                $em = $this->cx->getDb()->getEntityManager();
                $locale = $em
                    ->getRepository('\Cx\Core\Locale\Model\Entity\Locale')
                    ->findOneByCode($langCode);

                // load component language data
                global $_ARRAYLANG;
                $_ARRAYLANG = array_merge(
                    $_ARRAYLANG,
                    \Env::get('init')->getComponentSpecificLanguageData(
                        parent::getName(),
                        true,
                        $locale->getId()
                    )
                );
                return $locale;
            },
            'user' => function($sessionId) {
                // Abort in case no session-ID has been supplied.
                // Important: non-session-users will have $sessionId
                // set to '0'
                if (empty($sessionId)) {
                    // Verify that no session is active. 
                    // As no session-ID has been supplied to the ESI-request,
                    // no session must be present. Otherwise this is a security
                    // breach and must be stopped.
                    if ($this->getComponent('Session')->isInitialized()) {
                        \DBG::msg(
                            'No session-ID supplied as ESI-argument. ' .
                            'However a session is active. This is prohibited'
                        );
                        throw new EsiWidgetControllerException('Invalid session state!');
                    }

                    // don't initialize a session as non is required
                    return $sessionId;
                }

                // verify session-id param with active session
                if (
                    $this->getComponent('Session')->isInitialized() &&
                    $sessionId != session_id()
                ) {
                    \DBG::log(
                        'Session-ID of ESI-request (' . $sessionId . ') is ' .
                        'different to currently initialized session (' .
                         session_id() . ')'
                    );
                    throw new EsiWidgetControllerException('Unauthorized session access!');
                }

                // select session based on supplied ESI-param
                session_id($sessionId);

                // resume existing session, but don't initialize a new session
                $this->getComponent('Session')->getSession(false);

                return $sessionId;
            },
            'theme' => function($themeId) {
                $themeRepo = new \Cx\Core\View\Model\Repository\ThemeRepository();
                $theme = $themeRepo->findById($themeId);
                $this->cx->getResponse()->setTheme($theme);
                return $theme;
            },
            'channel' => function($channel) {
                return $channel;
            },
            'country' => function($countryCode) {
                // this should return a country object
                return $countryCode;
            },
            'currency' => function($currencyCode) {
                // this should return a currency object
                return $currencyCode;
            },
            'path' => function($base64Path) {
                return $this->getComponent('Widget')->decode(
                    $base64Path
                );
            },
            'query' => function($base64Query) {
                $queryParams = array();
                parse_str(
                    $this->getComponent('Widget')->decode($base64Query),
                    $queryParams
                );
                return $queryParams;
            },
            'ref' => function($originalUrl) use ($params) {
                $headers = $params['response']->getRequest()->getHeaders();
                $originalUrl = str_replace(
                    '$(HTTP_REFERER)',
                    $headers['Referer'],
                    $originalUrl
                );
                return $originalUrl;
            }
        );
        foreach ($possibleGetParams as $possibleParam=>$callback) {
            if (!isset($params['get'][$possibleParam])) {
                continue;
            }
            $params['get'][$possibleParam] = $callback($params['get'][$possibleParam]);
        }
        return $params;
    }

    /**
     * Parses a widget
     * @param string $name Widget name
     * @param \Cx\Core\Html\Sigma Widget template
     * @param \Cx\Core\Routing\Model\Entity\Response $response Current response
     * @param array $params Array of params
     */
    public abstract function parseWidget($name, $template, $response, $params);
}
