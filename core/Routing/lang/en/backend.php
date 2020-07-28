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
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @access      public
 * @package     cloudrexx
 * @subpackage  core_routing
 */
global $_ARRAYLANG;

// Act Variables
$_ARRAYLANG['TXT_CORE_ROUTING'] = 'Redirections';
$_ARRAYLANG['TXT_CORE_ROUTING_ACT_DEFAULT'] = 'Overview';
$_ARRAYLANG['TXT_CORE_ROUTING_ACT_REDIRECT'] = 'Redirections';

$_ARRAYLANG['TXT_CORE_ROUTING_ACT_REWRITERULE'] = 'Rewrite rules';
$_ARRAYLANG['id'] = 'ID';
$_ARRAYLANG['regularExpression'] = 'Regular expression';
$_ARRAYLANG['TXT_CORE_ROUTING_REGULAR_EXPRESSION_TOOLTIP'] = '<a href="http://en.wikipedia.org/wiki/Regular_expression" target="_blank">Regular expression</a> that is used to rewrite the request URL to the target URL. Example: <b>#/shop\.example\.com/#/example.com/en/Shop/#</b>.';
$_ARRAYLANG['orderNo'] = 'Order number';
$_ARRAYLANG['rewriteStatusCode'] = 'Redirection HTTP Statuscode';
$_ARRAYLANG['TXT_CORE_ROUTING_REWRITE_STATUS_CODE_TOOLTIP'] = 'Choose 302 if the request address remains valid, otherwise 301. <a href="https://en.wikipedia.org/wiki/List_of_HTTP_status_codes#3xx_Redirection" target="_blank">More information</a><br />Choose "Intern" if you wan\'t to keep the requested URL in the address bar.';
$_ARRAYLANG['continueOnMatch'] = 'Continue after match';
$_ARRAYLANG['TXT_CORE_ROUTING_CONTINUE_ON_MATCH_TOOLTIP'] = 'Should the next rules (if any) still be processed if this rule matches?';

$_ARRAYLANG['TXT_CORE_ROUTING_TITLE'] = 'Rewrite rules';
$_ARRAYLANG['TXT_CORE_ROUTING_INTRODUCTION'] = 'This functionality allows you to globally redirect requests to your website. This for example allows you to redirect request to another domain to a specific page. Here\'s an explanation of all the options:';
$_ARRAYLANG['TXT_CORE_ROUTING_EXPLANATION'] = 'The rules consist of a <a href="http://en.wikipedia.org/wiki/Regular_expression" target="_blank">regular expression</a>, which is used for simple search and replace on the request URL. All the rules are processed in the order of the order number. If a rule matches and "Continue after match" is set to "No" no more rules are processed. As soon as all rules are processed and at least one matched the redirection is made. The HTTP status code of the last matched rule is used for redirection.';
