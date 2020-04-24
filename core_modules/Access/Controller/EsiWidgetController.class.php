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

namespace Cx\Core_Modules\Access\Controller;

/**
 * JsonAdapter Controller to handle EsiWidgets
 * Usage:
 * - Create a subclass that implements parseWidget()
 * - Register it as a Controller in your ComponentController
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage coremodules_widget
 */
class EsiWidgetController extends \Cx\Core_Modules\Widget\Controller\EsiWidgetController {
    /**
     * Parses a widget
     *
     * @param string                                 $name     Widget name
     * @param \Cx\Core\Html\Sigma                    $template WidgetTemplate
     * @param \Cx\Core\Routing\Model\Entity\Response $response Response object
     * @param array                                  $params   Get parameters
     */
    public function parseWidget($name, $template, $response, $params)
    {
        global $_CORELANG;

        if ($name == 'ACCESS_USER_COUNT') {
            $template->setVariable($name, \FWUser::getUserCount());
            return;
        }

        $template->setVariable(
            \Env::get('init')->getComponentSpecificLanguageData(
                'Access',
                true,
                $params['locale']->getId()
            )
        );
        $_CORELANG = \Env::get('init')->getComponentSpecificLanguageData(
            'Core',
            true,
            $params['locale']->getId()
        );
        $template->setVariable($_CORELANG);

        if (preg_match('/^access_logged_(in|out)\d{0,2}/', $name)) {
            \FWUser::parseLoggedInOutBlocks($template);
            return;
        }

        $objAccessBlocks = new AccessBlocks($template);
        //Parse the currently online users
        if ($name == 'access_currently_online_member_list') {
            if (
                \FWUser::showCurrentlyOnlineUsers() &&
                (
                    $template->blockExists('access_currently_online_female_members') ||
                    $template->blockExists('access_currently_online_male_members') ||
                    $template->blockExists('access_currently_online_members')
                )
            ) {
                if ($template->blockExists('access_currently_online_female_members')) {
                    $objAccessBlocks->setCurrentlyOnlineUsers('female');
                }

                if ($template->blockExists('access_currently_online_male_members')) {
                    $objAccessBlocks->setCurrentlyOnlineUsers('male');
                }

                if ($template->blockExists('access_currently_online_members')) {
                    $objAccessBlocks->setCurrentlyOnlineUsers();
                }
            } else {
                $template->hideBlock($name);
            }

            // of the users who's last activity was within 3600s
            // take the one with the lowest last_activity
            $objFWUser = \FWUser::getFWUserObject();
            $filter = array(
                'active'    => true,
                'last_activity' => array(
                    '>' => (time()-3600)
                )
            );
            $objUser = $objFWUser->objUser->getUsers(
                $filter,
                null,
                array('last_activity' => 'asc'),
                null,
                1
            );
            if (!$objUser) {
                return;
            }

            // and user_from_above.last_activity + 3600s = cache timeout
            $cacheTimeout = $objUser->getLastActivityTime() + 3600;
            $dateTime     = new \DateTime('@' . $cacheTimeout);
            $response->setExpirationDate($dateTime);
            return;
        }

        //Parse the last active users
        if ($name == 'access_last_active_member_list') {
            if (
                \FWUser::showLastActivUsers() &&
                (
                    $template->blockExists('access_last_active_female_members') ||
                    $template->blockExists('access_last_active_male_members') ||
                    $template->blockExists('access_last_active_members')
                )
            ) {
                if ($template->blockExists('access_last_active_female_members')) {
                    $objAccessBlocks->setLastActiveUsers('female');
                }

                if ($template->blockExists('access_last_active_male_members')) {
                    $objAccessBlocks->setLastActiveUsers('male');
                }

                if ($template->blockExists('access_last_active_members')) {
                    $objAccessBlocks->setLastActiveUsers();
                }
            } else {
                $template->hideBlock($name);
            }
            return;
        }

        //Parse the latest registered users
        if ($name == 'access_latest_registered_member_list') {
            if (
                \FWUser::showLatestRegisteredUsers() &&
                (
                    $template->blockExists('access_latest_registered_female_members') ||
                    $template->blockExists('access_latest_registered_male_members') ||
                    $template->blockExists('access_latest_registered_members')
                )
            ) {
                if ($template->blockExists('access_latest_registered_female_members')) {
                    $objAccessBlocks->setLatestRegisteredUsers('female');
                }

                if ($template->blockExists('access_latest_registered_male_members')) {
                    $objAccessBlocks->setLatestRegisteredUsers('male');
                }

                if ($template->blockExists('access_latest_registered_members')) {
                    $objAccessBlocks->setLatestRegisteredUsers();
                }
            } else {
                $template->hideBlock($name);
            }
            return;
        }

        //Parse the birthday users
        if ($name === 'access_birthday_member_list') {
            if (
                \FWUser::showBirthdayUsers() &&
                $objAccessBlocks->isSomeonesBirthdayToday() &&
                (
                    $template->blockExists('access_birthday_female_members') ||
                    $template->blockExists('access_birthday_male_members') ||
                    $template->blockExists('access_birthday_members')
                )
            ) {
                if ($template->blockExists('access_birthday_female_members')) {
                    $objAccessBlocks->setBirthdayUsers('female');
                }

                if ($template->blockExists('access_birthday_male_members')) {
                    $objAccessBlocks->setBirthdayUsers('male');
                }

                if ($template->blockExists('access_birthday_members')) {
                    $objAccessBlocks->setBirthdayUsers();
                }
            } else {
                $template->hideBlock($name);
            }
            $dateTime = new \DateTime('tomorrow');
            $response->setExpirationDate($dateTime);
        }

        //Parse the next birthday users
        if ($name == 'access_next_birthday_member_list') {
            if (
                \FWUser::showNextBirthdayUsers() &&
                (
                    $template->blockExists('access_next_birthday_female_members') ||
                    $template->blockExists('access_next_birthday_male_members') ||
                    $template->blockExists('access_next_birthday_members')
                )
            ) {
                if ($template->blockExists('access_next_birthday_female_members')) {
                    $objAccessBlocks->setNextBirthdayUsers('female');
                }

                if ($template->blockExists('access_next_birthday_male_members')) {
                    $objAccessBlocks->setNextBirthdayUsers('male');
                }

                if ($template->blockExists('access_next_birthday_members')) {
                    $objAccessBlocks->setNextBirthdayUsers();
                }

                $dateTime = new \DateTime('tomorrow');
                $response->setExpirationDate($dateTime);
            } else {
                $template->hideBlock($name);
            }
        }
    }
}
