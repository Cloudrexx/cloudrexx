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

namespace Cx\Core\DateTime\Controller;

/**
 * ComponentControlle for DateTime component
 * This component handles timezones and provides methods for conversion
 *
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @package     contrexx
 * @subpackage  core_datetime
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {

    /**
     * @var \DateTimeZone Database timezone
     */
    protected $databaseTimezone;

    /**
     * @var \DateTimeZone Internal timezone
     */
    protected $internalTimezone;

    /**
     * {@inheritdoc}
     */
    public function getControllerClasses()
    {
        return array('EsiWidget');
    }

    /**
     * {@inheritdoc}
     */
    public function getControllersAccessableByJson()
    {
        return array('EsiWidgetController');
    }

    /**
     * Sets the user's and the database timezone
     * Please note that there's also the user's timezone. Since the user could
     * change (login/logout) during the request, we get it on demand.
     */
    public function postComponentLoad() {
        global $_CONFIG;

        $databaseTimezoneString = $this->cx->getDb()->getDb()->getTimezone();
        $this->databaseTimezone = new \DateTimeZone($databaseTimezoneString);

        $internalTimezoneString = $_CONFIG['timezone'];
        $this->internalTimezone = new \DateTimeZone($internalTimezoneString);
    }

    /**
     * Converts a \DateTime object in DB timezone to internal timezone
     * @param \DateTime $datetime DateTime in database timezone
     * @return \DateTime DateTime in internal timezone
     */
    public function db2intern(\DateTime $datetime) {
        return $datetime->setTimezone($this->internalTimezone);
    }

    /**
     * Converts a \DateTime object in internal timezone to a user's timezone
     * @param \DateTime $datetime DateTime in internal timezone
     * @param \User $user (optional) User object to get timezone of
     * @return \DateTime DateTime in user's timezone
     */
    public function intern2user(\DateTime $datetime, $user = null) {
        $userTimezone = \FWUser::getFWUserObject()->objUser->getTimezone();
        if ($user) {
            $userTimezone = $user->getTimezone();
        }
        return $datetime->setTimezone($userTimezone);
    }

    /**
     * Converts a \DateTime object in user's timezone to internal timezone
     * @param \DateTime $datetime DateTime in user's timezone
     * @return \DateTime DateTime in internal timezone
     */
    public function user2intern(\DateTime $datetime) {
        return $datetime->setTimezone($this->internalTimezone);
    }

    /**
     * Converts a \DateTime object in internal timezone to DB timezone
     * @param \DateTime $datetime DateTime in internal timezone
     * @return \DateTime DateTime in DB timezone
     */
    public function intern2db(\DateTime $datetime) {
        return $datetime->setTimezone($this->databaseTimezone);
    }

    /**
     * Converts a \DateTime object in DB timezone to a user's timezone
     * @param \DateTime $datetime DateTime in database timezone
     * @param \User $user (optional) User object to get timezone of
     * @return \DateTime DateTime in user's timezone
     */
    public function db2user(\DateTime $datetime, $user = null) {
        return $this->intern2user($this->db2intern($datetime), $user);
    }

    /**
     * Converts a \DateTime object in a user's timezone to DB timezone
     * @param \DateTime $datetime DateTime in user's timezone
     * @return \DateTime DateTime in database timezone
     */
    public function user2db(\DateTime $datetime) {
        return $this->intern2db($this->user2intern($datetime));
    }

    /**
     * Returns a \DateTime object in a user's timezone
     * @param string A date/time string. Argument for \DateTime::construct()
     * @param \User $user (optional) User object to get timezone of
     * @return \DateTime DateTime object in user's timezone
     */
    public function createDateTimeForUser($time = '', $user = null) {
        $userTimezone = \FWUser::getFWUserObject()->objUser->getTimezone();
        if ($user) {
            $userTimezone = $user->getTimezone();
        }
        return new \DateTime($time, $userTimezone);
    }

    /**
     * Returns a \DateTime object in DB timezone
     * @param string A date/time string. Argument for \DateTime::construct()
     * @return \DateTime DateTime object in DB timezone
     */
    public function createDateTimeForDb($time = '') {
        return new \DateTime($time, $this->databaseTimezone);
    }

    /**
     * {@inheritdoc}
     */
    public function postInit(\Cx\Core\Core\Controller\Cx $cx)
    {
        $widgetController = $this->getComponent('Widget');

        // register FinalStringWidgets
        $widgets = array(
            'TIME'      => '$strftime(\'%H:%M\')',
            'DATE_YEAR' => '$strftime(\'%Y\')',
            'DATE_MONTH'=> '$strftime(\'%m\')',
            'DATE_DAY'  => '$strftime(\'%d\')',
            'DATE_TIME' => '$strftime(\'%H:%M\')',
            'DATE_TIMESTAMP'    => '$strftime(\'%s\')',
        );
        foreach ($widgets as $widgetName => $func) {
            $widget = new \Cx\Core_Modules\Widget\Model\Entity\FinalStringWidget(
                $this,
                $widgetName,
                $func
            );
            $widgetController->registerWidget($widget);
        }

        // register EsiWidget
        $widget = new \Cx\Core_Modules\Widget\Model\Entity\EsiWidget(
            $this,
            'DATE'
        );
        $widget->setEsiVariable(
            \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_LOCALE
        );
        $widgetController->registerWidget($widget);
    }

    /**
     * Localized version of PHP's strftime() function
     *
     * See PHP documentation of strftime() for the reference of $format.
     *
     * @param   string  $format Format according to http://php.net/strftime
     * @param   integer $timestamp  The timestamp to be formatted (optional)
     * @param   string  $locale The locale to use instead of the currently loaded locale by the system
     * @global  array   $_CORELANG
     * @return  string  Returns a string formatted according format using the
     *                  given timestamp or the current local time if no
     *                  timestamp is given. Month and weekday names and other
     *                  language-dependent strings respect the current set
     *                  locale.
     * @todo    Drop argument $locale. As soon as the Locale component
     *          does provide the ability to load language files, the
     *          argument $locale must be dropped. The Locale component
     *          must itself figure out which language files it must load. 
     * @todo    Migrate code that loads the language files
     *          (InitCMS::loadLangFile()) to use the Locale component
     *          as soon as the Locale provides such a method.
     */
    public static function strftime($format, $timestamp = null, $locale = '') {
        global $_CORELANG;
        if (
            empty($_CORELANG) &&
            !empty($locale)
        ) {
            $sourceLanguage = $locale;
            if (!in_array($sourceLanguage, array('da', 'de', 'en', 'fr', 'it', 'ru'))) {
                $sourceLanguage = 'en';
            }
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $path = $cx->getCodeBaseDocumentRootPath() . '/lang/' . $sourceLanguage . '/frontend.php';
            $isCustomized = false;
            $customizedPath = \Env::get('ClassLoader')->getFilePath($path, $isCustomized);
            if (file_exists($path) || !file_exists($customizedPath)) {
                require $path;
            }
            if ($isCustomized) {
                require $customizedPath;
            }
            $_CORELANG = $_ARRAYLANG;
        }

        if (!$timestamp) {
            $timestamp = time();
        }

        $customFormatHandlers = array(
            //  The abbreviated weekday name according to the current locale
            '%a' => function($time) use ($_CORELANG) {
                $days = explode(',', $_CORELANG['TXT_CORE_DAY_ABBREV3_ARRAY']);
                $dayIdx = strftime('%w', $time);
                return $days[$dayIdx];
            },

            //  The full weekday name according to the current locale
            '%A' => function($time) use ($_CORELANG) {
                $days = explode(',', $_CORELANG['TXT_CORE_DAY_ARRAY']);
                $dayIdx = strftime('%w', $time);
                return $days[$dayIdx];
            },

            //  The abbreviated month name according to the current locale
            '%b' => function($time) use ($_CORELANG) {
                $months = explode(',', $_CORELANG['TXT_CORE_MONTH_ABBREV3_ARRAY']);
                $monthIdx = intval(strftime('%m', $time));
                return $months[$monthIdx];
            },

            // The full month name according to the current locale
            '%B' => function($time) use ($_CORELANG) {
                $months = explode(',', $_CORELANG['TXT_CORE_MONTH_ARRAY']);
                $monthIdx = intval(strftime('%m', $time));
                return $months[$monthIdx];
            },

            // The preferred date and time representation for the current locale
            // TODO: implement
            '%c' => null,

            // Equivalent to %b
            '%h' => function($time) use ($_CORELANG) {
                $months = explode(',', $_CORELANG['TXT_CORE_MONTH_ABBREV3_ARRAY']);
                $monthIdx = intval(strftime('%m', $time));
                return $months[$monthIdx];
            },

            // Some conversion specifiers can be modified by preceding them by
            // the E or O modifier to indicate that an alternative format
            // should be used. The effect of the O modifier is to use
            // alternative numeric symbols (e.g., roman numerals), and that of
            // the E modifier is to use a locale-dependent alternative
            // representation. If the alternative format or specification does
            // not exist for the current locale, the behavior will be as if the
            // unmodified conversion specification were used. 
            // TODO: implement
            '%E' => null,
            '%O' => null,

            // The preferred date representation for the current locale
            // without the time
            // TODO: implement
            '%x' => null,

            // The preferred time representation for the current locale
            // without the date
            // TODO: implement
            '%X' => null,

            //  The date and time in date(1) format
            // TODO: implement
            '%+' => null,
        );

        // escape format characters to be used in preg_replace_callback()
        $formatCharacters = array_keys($customFormatHandlers);
        array_walk(
            $formatCharacters,
            function(&$char) {
                $char = '/' . preg_quote($char) . '/';
            }
        );

        // parse custom format handlers on $format
        $format = preg_replace_callback(
            $formatCharacters,
            function($matches) use ($timestamp, $customFormatHandlers) {
                if (empty($matches[0])) {
                    return;
                }

                $replacement = $customFormatHandlers[$matches[0]];
                if (is_null($replacement)) {
                    \DBG::msg('ESI function $strftime(): Unsupported modifier '. $matches[0]);
                    return;
                }
                if (is_callable($replacement)) {
                    return $replacement($timestamp);
                }
            },
            $format
        );

        // finish parsing of $format by applying PHP's native strftime()
        // function
        return strftime($format, $timestamp);
    }
}
