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
 * Message
 *
 * Handles status messages
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  core
*/

/**
 * Message
 *
 * Handles status messages on single pages or across redirects
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  core
 * @todo        Distinguish between flash and deferred messages
*/
class Message
{
    /**
     * The Message class names
     */
    const CLASS_ERROR = 'error';
    const CLASS_WARN = 'warning';
    const CLASS_INFO = 'information';
    const CLASS_OK = 'ok';

    /**
     * Known message classes
     *
     * Messages are grouped by class and shown in the order used here.
     * Note:  The order here is relevant, because some modules may only
     * use one message class, which must then be the most urgent one!
     * See {@see show_frontend()} for an example.
     * @var   array
     */
    static $message_classes = array(
        self::CLASS_ERROR,
        self::CLASS_WARN,
        self::CLASS_INFO,
        self::CLASS_OK,
    );

    /**
     * Check if there is a session present
     *
     * If a session is present, but has not yet initialized,
     * then the session will be initialized.
     *
     * @return  boolean TRUE if a session is present. Otherwise FALSE.
     */
    protected static function checkForSession() {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $session = $cx->getComponent('Session');
        return (bool) $session->getSession(false);
    }


    /**
     * Clears the messages
     */
    static function clear()
    {
        if (!static::checkForSession()) {
            return;
        }

        unset($_SESSION['messages']);
    }


    /**
     * Returns true if there are messages present
     *
     * The optional $class parameter restricts the test to the given class
     * of messages, so true is only returned iff there is at least one message
     * of that class.
     * @return  string    $class      If set, restrict the test to that message
     *                                class.
     * @return  boolean               True if there are messages,
     *                                false otherwise
     */
    static function have($class=null)
    {
        if (!static::checkForSession()) {
            return false;
        }

        if (empty($class)) {
            return !empty($_SESSION['messages']);
        }
        return !empty($_SESSION['messages'][$class]);
    }


    /**
     * Returns a random message class (for testing purposes only)
     * @return  string      A random message class
     */
    static function random_class()
    {
        return self::$message_classes[rand(0, count(self::$message_classes)-1)];
    }


    /**
     * Saves the current messages on the stack
     *
     * Clears the current messages.
     * If there are no messages, does nothing.
     * Restore the previous state by calling {@see restore}.
     */
    static function save()
    {
        if (!static::checkForSession()) {
            return;
        }

        if (empty($_SESSION['messages'])) {
            return;
        }
        if (empty($_SESSION['messages_stack'])) {
            $_SESSION['messages_stack'] = array();
        }
        $messagesStackTmp = $_SESSION['messages_stack']->toArray();
        array_push($messagesStackTmp, $_SESSION['messages']);
        $_SESSION['messages_stack'] = $messagesStackTmp;
        self::clear();
    }


    /**
     * Restores the messages from the stack, if any.
     *
     * If the stack is empty, clears the current messages.
     */
    static function restore()
    {
        if (!static::checkForSession()) {
            return;
        }

        if (empty($_SESSION['messages_stack'])) {
            self::clear();
            return;
        }
        $messagesStackTmp = $_SESSION['messages_stack']->toArray();
        $_SESSION['messages'] = array_pop($messagesStackTmp);
        $_SESSION['messages_stack'] = $messagesStackTmp;
    }


    /**
     * Adds a message of the given class
     *
     * The optional $class defaults to the CLASS_INFO class constant.
     * May be empty, or one of CLASS_OK, CLASS_INFO, CLASS_WARN,
     * or CLASS_ERROR.
     * @param   string  $message        The message to add
     * @param   string  $class          The optional class.
     *                                  Defaults to CLASS_INFO
     * @author  Reto Kohli <reto.kohli@comvation.com>
     * @static
     */
    static function add($message, $class=self::CLASS_INFO)
    {
        if (!static::checkForSession()) {
            \DBG::log('Message can\'t be used at this point as no session has been initialized yet!');
            return;
        }

        if (empty($_SESSION['messages'])) {
            $_SESSION['messages'] = array();
        }
        if (empty($_SESSION['messages'][$class])) {
            $_SESSION['messages'][$class] = array();
        }

        $_SESSION['messages'][$class][] = $message;
    }


    /**
     * Adds a message of class "ok"
     *
     * Returns true for convenience.
     * @param   string    $message        The message to add
     * @return  boolean                   True
     * @author  Reto Kohli <reto.kohli@comvation.com>
     * @static
     */
    static function ok($message)
    {
        self::add($message, self::CLASS_OK);
        return true;
    }


    /**
     * Adds a message of class "information"
     * @param   string    $message        The message to add
     * @author  Reto Kohli <reto.kohli@comvation.com>
     * @static
     */
    static function information($message)
    {
        self::add($message, self::CLASS_INFO);
    }


    /**
     * Adds a message of class "warning"
     * @param   string    $message        The message to add
     * @author  Reto Kohli <reto.kohli@comvation.com>
     * @static
     */
    static function warning($message)
    {
        self::add($message, self::CLASS_WARN);
    }


    /**
     * Adds a message of class "error"
     *
     * Returns false for convenience.
     * @param   string    $message        The message to add
     * @return  boolean                   False
     * @author  Reto Kohli <reto.kohli@comvation.com>
     * @static
     */
    static function error($message)
    {
        self::add($message, self::CLASS_ERROR);
        return false;
    }


    /**
     * Shows the collected messages
     *
     * Decides which view to use, and clears the messages
     * @param   \Cx\Core\Html\Sigma   $objTemplateLocal
     *                                              The optional Template
     */
    static function show($objTemplateLocal=null)
    {
//DBG::log("Message::show(): Got messages: ".var_export($_SESSION['messages'], true));
        if (defined('BACKEND_LANG_ID')) {
            self::show_backend($objTemplateLocal);
        } else {
            self::show_frontend($objTemplateLocal);
        }
        self::clear();
    }


    /**
     * Shows the collected messages, if any
     *
     * Backend use only.
     * @param   \Cx\Core\Html\Sigma   $objTemplateLocal
     *                                              The optional Template
     * @todo    Add the missing classes CLASS_INFO and CLASS_WARN
     *          to the backend views
     * @todo    Unify the placeholders, and add the message block
     */
    private static function show_backend($objTemplateLocal=null)
    {
        if (!static::checkForSession()) {
            return;
        }

        if (empty($_SESSION['messages'])) return;

        global $objTemplate;
        if (empty($objTemplateLocal)) $objTemplateLocal = &$objTemplate;

        foreach (self::$message_classes as $class) {
            if (empty($_SESSION['messages'][$class])) {
//DBG::log("Message::show_backend(): No message of class $class");
                continue;
            }
//DBG::log("Message::show_backend(): Got message of class $class: ".var_export($_SESSION['messages'][$class], true));
            $objTemplateLocal->setVariable(array(
// TODO: Unify this placeholder once the type is available:
// Should be "MESSAGE_TEXT", see frontend version
                'CONTENT_'.
                (   $class == self::CLASS_OK
                 || $class == self::CLASS_INFO
                    ? 'OK' : 'STATUS').
                '_MESSAGE' =>
                    join('<br />', $_SESSION['messages'][$class]->toArray()),
// Should be "MESSAGE_CLASS", see frontend version
                'CONTENT_MESSAGE_TYPE' => $class,
            ));
// TODO: Make this unconditional once the block is generally available
            if ($objTemplateLocal->blockExists('messages')) {
                $objTemplateLocal->parse('messages');
            } else {
                // Don't overwrite
                break;
            }
        }
    }


    /**
     * Shows the collected messages, if any
     *
     * Frontend use only.
     * Returns a status according to the message classes:
     *  - null, if no message is present
     *  - false, if any message of class CLASS_ERROR is encountered
     *  - true, if no messages of class CLASS_ERROR are found
     * @param   \Cx\Core\Html\Sigma   $objTemplateLocal
     *                                              The optional Template
     * @return  boolean                             The status
     */
    private static function show_frontend($objTemplateLocal=null)
    {
        if (!static::checkForSession()) {
            return null;
        }

        if (empty($_SESSION['messages'])) return null;

        global $objTemplate;
        if (empty($objTemplateLocal)) $objTemplateLocal = &$objTemplate;

        foreach (self::$message_classes as $class) {
            if (empty($_SESSION['messages'][$class])) continue;
            $objTemplateLocal->setVariable(array(
                'MESSAGE_CLASS' => $class,
                'MESSAGE_TEXT' =>
                    join('<br />', $_SESSION['messages'][$class]->toArray()),
            ));
            if ($objTemplateLocal->blockExists('messages')) {
                $objTemplateLocal->parse('messages');
            } else {
                // If no block can be parsed, leave after the first
                // non-empty message class.
                // Note:  This is why classes are parsed in descending
                // order of severity!
                break;
            }
        }
        // Fail when there are error messages
        if (isset($_SESSION['messages'][self::CLASS_ERROR])) {
            return false;
        }
        return true;
    }


    /**
     * Returns a message string, if any are present
     *
     * Frontend use only.
     * Returns a concatenation of messages by looking for them in the order
     * defined by the $message_classes array.
     * Only messages of the first class found are used.
     * Does not {@see clear()} the messages; either do that yourself if
     * necessary, or call {@see show()} after this.
     * @return  string                The message string, if any,
     *                                or null
     */
    static function get()
    {
        global $objTemplate;

        if (!static::checkForSession()) {
            return null;
        }

        if (empty($_SESSION['messages'])) return null;
        foreach (self::$message_classes as $class) {
            if (empty($_SESSION['messages'][$class])) continue;
            return join('<br />', $_SESSION['messages'][$class]->toArray());
        }
        return null;
    }

    /**
     * Formats variable of an unknown type into array and returns it
     *
     * @param type $var
     * @return array
     */
    static function toArray($var) {
        $return = array();
        if(is_object($var) && method_exists($var, 'toArray')) {
            $return = $var->toArray();
        }
        if(is_array($return)) {
            return $return;
        }
        if(!empty($var)) {
            return array($var);
        }
        return array();
    }

}
