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
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @package     cloudrexx
 * @subpackage  module_blog
 */
$_ARRAYLANG['TXT_BLOG_SETTINGS_GENERAL_TITLE'] = "General";
$_ARRAYLANG['TXT_BLOG_SETTINGS_GENERAL_INTRODUCTION'] = "Number of characters in introdcution";
$_ARRAYLANG['TXT_BLOG_SETTINGS_GENERAL_INTRODUCTION_HELP'] = "This value defines the number of characters used in the introduction. If you want to show the complete text instead of an shortened text you have to use the value 0.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_TITLE'] = "Comments";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_ALLOW'] = "Enable comments";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_ALLOW_HELP'] = "If you want to give your visitor the opportunity to write comments to your messages you must activate this option.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_ALLOW_ANONYMOUS'] = "Allow anonymous comments";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_ALLOW_ANONYMOUS_HELP'] = "If you want to allow unregistered user to write comments you have to activate this option.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_AUTO_ACTIVATE'] = "Activate new comments automatically";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_AUTO_ACTIVATE_HELP'] = "By activating this option new comments will be activated automatically. Otherwise they must be checked and activated by an administrator manually.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_NOTIFICATION'] = "Notification about new comments";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_NOTIFICATION_HELP'] = "If this option is activated, you receive an email when receiving new comments.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_TIMEOUT'] = "Waiting time between two comments";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_TIMEOUT_HELP'] = "This value describes how many seconds must be elapsed between two comments of the same user. This avoids flooding the comment function. We recommend the usage of 30 seconds.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_EDITOR'] = "Editor";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_EDITOR_HELP'] = "Determine which editor your visitors are allowed to use for writing new comments.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_EDITOR_WYSIWYG'] = "WYSIWYG editor";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_EDITOR_TEXTAREA'] = "Textfield";
$_ARRAYLANG['TXT_BLOG_SETTINGS_VOTING_TITLE'] = "Rating";
$_ARRAYLANG['TXT_BLOG_SETTINGS_VOTING_ALLOW'] = "Allow rating";
$_ARRAYLANG['TXT_BLOG_SETTINGS_VOTING_ALLOW_HELP'] = "If you want to give your visitor the opportunity to rate your messages you must activate this option.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_TAG_TITLE'] = "Keywords";
$_ARRAYLANG['TXT_BLOG_SETTINGS_TAG_HITLIST'] = "Keywords within ranking list";
$_ARRAYLANG['TXT_BLOG_SETTINGS_TAG_HITLIST_HELP'] = "Determine the number of shown keywords within the ranking.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_TITLE'] = "RSS";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_ACTIVATE'] = "Activate RSS-feeds";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_ACTIVATE_HELP'] = "If the system should create a RSS-feed of your blog, you have to activate this option. Thus some files with the pattern <pre>blog_messages_XX.xml<br />blog_comments_XX.xml<br />blog_category_ID_XX.xml</pre> will be created in the folder <pre>feed/</pre>. The placeholder XX stands for the shortform of the language, ID for the unique identifier of the category.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_MESSAGES'] = "Number of messages";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_MESSAGES_HELP'] = "Number of messages contained in the XML-file. The system uses always the newest messages.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_COMMENTS'] = "Number of comments";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_COMMENTS_HELP'] = "Number of comments contained in the XML-file. The system uses always the newest comments.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_TITLE'] = "Block template";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_ACTIVATE'] = "Activate block function";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_ACTIVATE_HELP'] = "If you activate this function the placeholder <pre>[[BLOG_FILE]]</pre> will be replaced by the file <pre>blog.html</pre>. You can use the mentioned placeholder in various places.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_MESSAGES'] = "Number of messages";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_MESSAGES_HELP'] = "Number of message which will be showed. The system uses always the newest messages.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_USAGE'] = "Usage";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_USAGE_HELP'] = "All placeholders listed here can be used in your design (\"Layout & Designs\") in the file <b>blog.html</b>. The mentioned file can be included with the placeholder <b>[[BLOG_FILE]]</b> in one of the following files: index.html, home.html, content.html and sidebar.html. It is also possible to use it within any content-page (Content Manager).<br /><br />Furthermore the variables listed under <b>General</b> can also be used outside of <b>blog.html</b> within the design-system (index.html, home.html, content.html und sidebar.html).";
$_ARRAYLANG['TXT_BLOG_SETTINGS_SAVE_SUCCESSFULL'] = "The settings have been updated successfully.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_ACTIVE_LANGUAGES'] = "activated languages";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_ACTIONS'] = "Actions";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_NO_CATEGORIES'] = "There are no categories existing at the moment.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_ASSIGNED_MESSAGES'] = "Show messages in this category";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_MARKED'] = "Selection";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_SELECT'] = "Select everything";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DESELECT'] = "Remove selection";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_ACTION'] = "Select action";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_ACTIVATE'] = "Activate selected items";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DEACTIVATE'] = "Deactivate selected items";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DELETE'] = "Delete selected items";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DELETE_JS'] = "Do you really want to delete all selected categories? This action can not be undone!";
$_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_NAME'] = "Name";
$_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_EXTENDED'] = "Extended";
$_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_LANGUAGES'] = "Languages";
$_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_SUCCESSFULL'] = "The new category has been added successfully.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_ERROR_ACTIVE'] = "Category could not be added. You have to select at least one language for your category.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_DELETE_TITLE'] = "Delete category";
$_ARRAYLANG['TXT_BLOG_CATEGORY_DELETE_JS'] = "Do you really want to delete this category?";
$_ARRAYLANG['TXT_BLOG_CATEGORY_DELETE_SUCCESSFULL'] = "The category has been deleted successfully.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_DELETE_ERROR'] = "The category with the desired ID could not be deleted.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_EDIT_TITLE'] = "Edit category";
$_ARRAYLANG['TXT_BLOG_CATEGORY_EDIT_ERROR_ID'] = "There is no category with such an ID. Please check the entered ID.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_UPDATE_SUCCESSFULL'] = "The category has been updated successfully.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_UPDATE_ERROR_ACTIVE'] = "Category could not be updated. You have to select at least one language for your category.";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_SUBJECT'] = "Title";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_KEYWORDS'] = "Keywords";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_IMAGE'] = "Message picture";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_IMAGE_BROWSE'] = "Browse";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_CATEGORIES'] = "Categories";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_SUCCESSFULL'] = "The new message has been added successfully.";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_ERROR_LANGUAGES'] = "You have to publish the message in at least one language.";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_DATE'] = "Release";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_HITS'] = "Reader";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_COMMENT'] = "Comment";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_COMMENTS'] = "Comments";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_VOTE'] = "Rating";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_VOTES'] = "Votes";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_UPDATED'] = "Last update";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_SUBMIT_DELETE_JS'] = "Do you really want to delete all selected entries? This action can not be undone!";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_NO_ENTRIES'] = "There are no messages existing at the moment.";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_PAGING'] = "Messages";
$_ARRAYLANG['TXT_BLOG_ENTRY_DELETE_TITLE'] = "Delete message";
$_ARRAYLANG['TXT_BLOG_ENTRY_DELETE_JS'] = "Do you really want to delete this message?";
$_ARRAYLANG['TXT_BLOG_ENTRY_DELETE_SUCCESSFULL'] = "The entry has been deleted successfully.";
$_ARRAYLANG['TXT_BLOG_ENTRY_DELETE_ERROR_ID'] = "There is no entry with such an ID. Please check the entered ID.";
$_ARRAYLANG['TXT_BLOG_ENTRY_EDIT_TITLE'] = "Edit message";
$_ARRAYLANG['TXT_BLOG_ENTRY_EDIT_ERROR_ID'] = "There is no message with such an ID. Please check the entered ID.";
$_ARRAYLANG['TXT_BLOG_ENTRY_UPDATE_SUCCESSFULL'] = "The message has been updated successfully.";
$_ARRAYLANG['TXT_BLOG_ENTRY_UPDATE_ERROR_LANGUAGES'] = "You have to activated the message in at least one language.";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_TITLE'] = "Rating of the message";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_COUNT'] = "Number of votes";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_AVG'] = "Avarage rating";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_STATISTICS'] = "Stats";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_STATISTICS_NONE'] = "There are no statistics for this message yet.";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_DETAILS'] = "Rating";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_DATE'] = "Date & time";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_IP'] = "IP address";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_DELETE_JS'] = "Do you really want to delete this vote?";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_DELETE_SUCCESSFULL'] = "The vote has been deleted successfully.";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_SUBMIT_DELETE_JS'] = "Do you really want to delete all selected votes?";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_NONE'] = "There are no comments for this message yet.";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_LANGUAGE'] = "Language";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_STATUS'] = "De-/activate comments";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT'] = "Edit comment";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_DELETE'] = "Delete comments";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_DELETE_SUCCESSFULL'] = "The comment has been deleted successfully.";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_DELETE_JS'] = "Do you really want to delete this comment?";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_DELETE_JS_ALL'] = "Do you really want to delete all selected comments? This action can not be undone!";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_ERROR'] = "There is no comment with such an ID. Please check the entered ID.";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_USER_STATUS'] = "User status";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_USER_STATUS_REGISTERED'] = "Registered user";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_USER_STATUS_UNREGISTERED'] = "Unregistered user";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_USER_NAME'] = "Username";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_USER_WWW'] = "Website";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_UPDATE_SUCCESSFULL'] = "The comment has been updated successfully.";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_UPDATE_ERROR'] = "The comment could not be updated. It seems that you have entered invalid values.";
$_ARRAYLANG['TXT_BLOG_BLOCK_ERROR_DEACTIVATED'] = "The block-functionality is currently deactivated. Please activate it first in the settings.";
$_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_TITLE'] = "General";
$_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_CALENDAR'] = "A calendar uf the current month. Days with blog-messages are highlighted.";
$_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_CATEGORIES_SELECT'] = "A dropdown-menu which contains all existing categories. Allows to show the message filtered by the selected category.";
$_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_CATEGORIES_LIST'] = "A list of all existing categories. Allows to show the message filtered by the selected category.";
$_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_TAGCLOUD'] = "Returns a tagcloud with all keywords.";
$_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_TAGHITLIST'] = "Returns a ranking of the most popular keywords.";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_TITLE'] = "Messages";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_LINK'] = "Link";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_ROW'] = "All following placeholders have to be used within an block: <pre><!-- BEGIN latestBlogMessages--><br />...<br /><!-- END latestBlogMessages --></pre>";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_ROWCLASS'] = "CSS-class for the row";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_ID'] = "Unique ID of the category";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_DATE'] = "Date of message";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_POSTEDBY'] = "Text containing the date and the user";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_SUBJECT'] = "Title of message";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_INTRODUCTION'] = "Short introduction for this message";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_CONTENT'] = "Complete text of the message";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_AUTHOR_ID'] = "Unique ID of the author";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_AUTHOR_NAME'] = "Name of author";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_CATEGORIES'] = "Category of this message";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_TAGS'] = "Keywords of the message";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_COMMENTS'] = "Number of comments";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_VOTING'] = "Avarage rating of the message";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_STARS'] = "Rating of the message in stars";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_LINK'] = "Link to detailpage";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_IMAGE'] = "Assigned picture";
$_ARRAYLANG['TXT_BLOG_BLOCK_CATEGORY_TITLE'] = "Categories";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_NAME'] = "Name of category";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_COUNT'] = "Number of messages in this category";
$_ARRAYLANG['TXT_BLOG_BLOCK_TEXT'] = "Text";
$_ARRAYLANG['TXT_BLOG_BLOCK_CONTENT'] = "Content";
$_ARRAYLANG['TXT_BLOG_BLOCK_EXAMPLE'] = "example code";
$_ARRAYLANG['TXT_BLOG_NETWORKS'] = "Networks";
$_ARRAYLANG['TXT_BLOG_NETWORKS_OVERVIEW_NONE'] = "There are no networks existing at the moment.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_OVERVIEW_SUBMIT_DELETE'] = "Delete selected items";
$_ARRAYLANG['TXT_BLOG_NETWORKS_OVERVIEW_SUBMIT_DELETE_JS'] = "Do you really want to delete all selected networks? This action can not be undone!";
$_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_TITLE'] = "Add network";
$_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_NAME'] = "Name of provider";
$_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_WWW'] = "URL of provider";
$_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_SUBMIT'] = "URL for submission";
$_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_ICON'] = "Icon of the provider";
$_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_BROWSE'] = "Browse";
$_ARRAYLANG['TXT_BLOG_NETWORKS_INSERT_SUCCESSFULL'] = "The network has been added successfully.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_INSERT_ERROR'] = "One or more of your inputs were faulty. The network could not be created.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_EDIT_TITLE'] = "Edit network";
$_ARRAYLANG['TXT_BLOG_NETWORKS_EDIT_ERROR'] = "There is no network with such an ID. Please check the entered ID.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_UPDATE_SUCCESSFULL'] = "The network has been updated successfully.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_UPDATE_ERROR'] = "One or more of your inputs were faulty. The network could not be updated.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_DELETE_TITLE'] = "Delete network";
$_ARRAYLANG['TXT_BLOG_NETWORKS_DELETE_JS'] = "Do you really want to delete this network?";
$_ARRAYLANG['TXT_BLOG_NETWORKS_DELETE_SUCCESSFULL'] = "The network has been deleted successfully.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_DELETE_ERROR'] = "The network with this id could not be found.";
$_ARRAYLANG['TXT_BLOG_LIB_POSTED_BY'] = "posted by [USER] at [DATE]";
$_ARRAYLANG['TXT_BLOG_LIB_CALENDAR_WEEKDAYS'] = "Su,Mo,Tu,We,Th,Fr,Sa";
$_ARRAYLANG['TXT_BLOG_LIB_CALENDAR_MONTHS'] = "January,February,March,April,May,June,July,August,September,October,November,December";
$_ARRAYLANG['TXT_BLOG_LIB_RATING'] = "Rating";
$_ARRAYLANG['TXT_BLOG_LIB_ALL_CATEGORIES'] = "All categories";
$_ARRAYLANG['TXT_BLOG_LIB_RSS_MESSAGES_TITLE'] = "Blog messages";
$_ARRAYLANG['TXT_BLOG_LIB_RSS_COMMENTS_TITLE'] = "Blog comments";
$_ARRAYLANG['TXT_BLOG_SAVE'] = "Save";
?>
