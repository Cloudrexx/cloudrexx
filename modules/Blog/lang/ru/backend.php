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
$_ARRAYLANG['TXT_BLOG_SETTINGS_GENERAL_TITLE'] = "Общие";
$_ARRAYLANG['TXT_BLOG_SETTINGS_GENERAL_INTRODUCTION'] = "Количество знаков в вводной части текста";
$_ARRAYLANG['TXT_BLOG_SETTINGS_GENERAL_INTRODUCTION_HELP'] = "Это значение определяет количество знаков в тексте вступления. Если вы всегда хотите видеть полный текст установите значение равное 0.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_TITLE'] = "Комментарии";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_ALLOW'] = "Позволенные комментарии";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_ALLOW_HELP'] = "Если вы хотите давать вашим посетителям возможность писать примечания к вашим статьям, то вы должны активировать эту опцию.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_ALLOW_ANONYMOUS'] = "Позволен анонимный комментарий";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_ALLOW_ANONYMOUS_HELP'] = "Если нужно позволить незарегестрированным посетителям написать примечания, то вы активируете эту опцию.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_AUTO_ACTIVATE'] = "Автоматическое активирование нового комментария";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_AUTO_ACTIVATE_HELP'] = "С помощью этого переключателя вы можете автоматически активировать новую статью. В другом случае вы должны сделать комментарий для вашей публикации.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_NOTIFICATION'] = "Уведомление о новом комментарии";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_NOTIFICATION_HELP'] = "При активации этой установки,вы получите уведомление на ваш E-Mail";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_TIMEOUT'] = "Время ожидания между комментариями";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_TIMEOUT_HELP'] = "Это значение показывает, через сколько секунд пользователь может разместить следующий комментарий. Стандартное значение 30 секунд.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_EDITOR'] = "Редактор";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_EDITOR_HELP'] = "Определите, какой редактор ваши пользователи могут использовать при комментировании.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_EDITOR_WYSIWYG'] = "WYSIWYG-Редактор";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_EDITOR_TEXTAREA'] = "Тестовое поле";
$_ARRAYLANG['TXT_BLOG_SETTINGS_VOTING_TITLE'] = "Оценка";
$_ARRAYLANG['TXT_BLOG_SETTINGS_VOTING_ALLOW'] = "Позволенный рейтинг";
$_ARRAYLANG['TXT_BLOG_SETTINGS_VOTING_ALLOW_HELP'] = "Если вы даете своим пользователям возможность, оценивать ваши статьи, вы должны активировать эту опцию.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_TAG_TITLE'] = "Ключевые слова";
$_ARRAYLANG['TXT_BLOG_SETTINGS_TAG_HITLIST'] = "Самые используемые ключевые слова списка";
$_ARRAYLANG['TXT_BLOG_SETTINGS_TAG_HITLIST_HELP'] = "Определите здесь количество показываемых ключевых слов в листе.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_TITLE'] = "RSS";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_ACTIVATE'] = "активировать RSS-каналы";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_ACTIVATE_HELP'] = "Если вы в своем блоге оставляете RSS-канал, вы должны активировать эту опцию. Следовательно в папке <pre>feed/</pre> файлы схемы <pre>blog_messages_XX.xml<br />blog_comments_XX.xml<br />blog_category_ID_XX.xml</pre> устанавливаются, причем XX для соответствующей системной языковой краткой формы  и ID для категории ID.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_MESSAGES'] = "Количество сообщений";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_MESSAGES_HELP'] = "Количество сообщений, которые могут содержаться в XML-файле. При этом всегда речь идет о самых актуальных сообщениях.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_COMMENTS'] = "Количество примечаний";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_COMMENTS_HELP'] = "Количество комментариев, которые могут содержаться XML-файле. При этом всегда речь идет о самых актуальных комментариях.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_TITLE'] = "Блог шаблон";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_ACTIVATE'] = "Активировать функцию блога";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_ACTIVATE_HELP'] = "Активируя эту функцию вы позволяете изменять места назначения папок в системе <pre>[[BLOG_FILE]]</pre> durch die Datei <pre>blog.html</pre>. Для этого Вы можете применять нижеупомянутые метки-заполнители.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_MESSAGES'] = "Количество сообщений";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_MESSAGES_HELP'] = "Количество сообщений, которые будут показаны. При этом всегда речь идет о самых актуальных сообщениях.Nachrichten angezeigt.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_USAGE'] = "Применение";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_USAGE_HELP'] = "Какое-либо место назначение в пункте меню \\\"Layout & Designs\\\" в файле <b>blog.html</b> используется в шаблоне Веб дизайна. Этот файл может быть присоеденен с места назначения <b>[[BLOG_FILE]]</b> в любом файле Веб дизайна (index.html, home.html, content.html und sidebar.html) или быть связанным со страницей содержания (Content Manager).<br /><br />В далььнейшем можно переменные <b>Общие</b> вне файла <b>blog.html</b>- применяются для файлов дизайна (index.html, home.html, content.html und sidebar.html).";
$_ARRAYLANG['TXT_BLOG_SETTINGS_SAVE_SUCCESSFULL'] = "Установки успешно сохранены.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_ACTIVE_LANGUAGES'] = "Активированные языки";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_ACTIONS'] = "Выбрать действие";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_NO_CATEGORIES'] = "В данный момент никае категории не имеются в наличии.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_ASSIGNED_MESSAGES'] = "Показать сообщения данной категории";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_MARKED'] = "Выделение";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_SELECT'] = "Выделить все";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DESELECT'] = "Удалить выделенное";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_ACTION'] = "Выбрать действие";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_ACTIVATE'] = "Активировать выделенное";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DEACTIVATE'] = "Деактивировать выделенное";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DELETE'] = "Удалить выделенное";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DELETE_JS'] = "Вы действительно хотите удалить выделенную категорию? Это жействие не возможно будет отменить!";
$_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_NAME'] = "Имя";
$_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_EXTENDED'] = "Расширить";
$_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_LANGUAGES'] = "Языки";
$_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_SUCCESSFULL'] = "Новая категория успешно добавлена.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_ERROR_ACTIVE'] = "Категория не добавлена. Вы должны активировать минимум один язык для новой категории.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_DELETE_TITLE'] = "Редактировать категорию";
$_ARRAYLANG['TXT_BLOG_CATEGORY_DELETE_JS'] = "Вы действительно хотите удалить удалить сообщение?";
$_ARRAYLANG['TXT_BLOG_CATEGORY_DELETE_SUCCESSFULL'] = "Категоря усрешно удалена.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_DELETE_ERROR'] = "Категории с выбранными ID не может быть удалена.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_EDIT_TITLE'] = "Редактировать категорию";
$_ARRAYLANG['TXT_BLOG_CATEGORY_EDIT_ERROR_ID'] = "Категории с даннимы ID не существует. Проверьте денные ID.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_UPDATE_SUCCESSFULL'] = "Категория успешно обновлена.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_UPDATE_ERROR_ACTIVE'] = "Категория не может быть обновлена. Вы должны активировать минимум один язык для категории";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_SUBJECT'] = "Заголовок";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_KEYWORDS'] = "Ключевые слова";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_IMAGE'] = "Изображение сообщения";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_IMAGE_BROWSE'] = "Обзор";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_CATEGORIES'] = "Категории";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_SUCCESSFULL'] = "Новое сообщение успешно добавлено.";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_ERROR_LANGUAGES'] = "Вы должны опубликовать новое сообщение минимум на одном языке.";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_DATE'] = "Публикация";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_HITS'] = "Читатель";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_COMMENT'] = "Комментарий";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_COMMENTS'] = "Комментарий";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_VOTE'] = "Оценка";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_VOTES'] = "Соответствовать";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_UPDATED'] = "Последнее обновление";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_SUBMIT_DELETE_JS'] = "Действительно ли вы хотите удалить все выделенные записи? Это действие невозможно будет отменить!";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_NO_ENTRIES'] = "В данный момент никаких записей в наличии не имеется";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_PAGING'] = "Сообщения";
$_ARRAYLANG['TXT_BLOG_ENTRY_DELETE_TITLE'] = "Сообщение удалено";
$_ARRAYLANG['TXT_BLOG_ENTRY_DELETE_JS'] = "Вы действительно хотите удалить удалить сообщение?";
$_ARRAYLANG['TXT_BLOG_ENTRY_DELETE_SUCCESSFULL'] = "Запись успешно удалена.";
$_ARRAYLANG['TXT_BLOG_ENTRY_DELETE_ERROR_ID'] = "Der Eintrag mit der angegebenen ID existiert nicht. Überprüfen Sie die eingegebene ID.";
$_ARRAYLANG['TXT_BLOG_ENTRY_EDIT_TITLE'] = "Редактировать сообщение";
$_ARRAYLANG['TXT_BLOG_ENTRY_EDIT_ERROR_ID'] = "Сообщения с данным ID не существует. Проверьте введеный ID.";
$_ARRAYLANG['TXT_BLOG_ENTRY_UPDATE_SUCCESSFULL'] = "Сообщение успешно обновлено.";
$_ARRAYLANG['TXT_BLOG_ENTRY_UPDATE_ERROR_LANGUAGES'] = "Вы должны активировать сообщение минимум на одном языке.";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_TITLE'] = "Оценка тему";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_COUNT'] = "Количество оценок";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_AVG'] = "Среднее значение рейтинга";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_STATISTICS'] = "Статистика";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_STATISTICS_NONE'] = "До сих не имеется статистики по этой теме.";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_DETAILS'] = "Оценка";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_DATE'] = "Дата & Время";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_IP'] = "IP-Адрес";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_DELETE_JS'] = "Вы действительно хотите удалить эту оценку?";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_DELETE_SUCCESSFULL'] = "Оценка успешно удалена.";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_SUBMIT_DELETE_JS'] = "Вы действительно хотите удалить все выделенные оценки?";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_NONE'] = "До сих не было составленного комментария по этой теме.";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_LANGUAGE'] = "Язык";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_STATUS'] = "Де-/активировать комментарий";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT'] = "Редактировать комментарий";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_DELETE'] = "Удалить комментарий";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_DELETE_SUCCESSFULL'] = "Комментарий успешно удален.";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_DELETE_JS'] = "Вы действительно хотите удалить комментарий?";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_DELETE_JS_ALL'] = "Вы действительн хотите удалить все выделенные комментарии?";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_ERROR'] = "Комментария с указанным ID не существует. Проверьте введеный ID.";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_USER_STATUS'] = "Статус пользователя";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_USER_STATUS_REGISTERED'] = "Зарегестрированный пользователь";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_USER_STATUS_UNREGISTERED'] = "Не зарегестрированный пользователь";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_USER_NAME'] = "Имя пользователя";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_USER_WWW'] = "Веб-страница";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_UPDATE_SUCCESSFULL'] = "Комментарий успешно обновлен.";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_UPDATE_ERROR'] = "Ваш комментарий не может быть обновлен. Вероятно вы ввеле недопустимое значение.";
$_ARRAYLANG['TXT_BLOG_BLOCK_ERROR_DEACTIVATED'] = "Функциональность блока в данный момент деактивирована. Активируйте ее сначала в установках.";
$_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_TITLE'] = "Общие";
$_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_CALENDAR'] = "Календарь актуального месяца. Дни с сообщениями будут подчеркиваться.";
$_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_CATEGORIES_SELECT'] = "Выподающее меню, которое содержит все существующие категории. Если позволяет фильтрация сообщений для категории.";
$_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_CATEGORIES_LIST'] = "Список со всеми существующими категориями. Если позволяет фильтрация сообщений на категорию.";
$_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_TAGCLOUD'] = "Есть полный Tag-Cloud всех ключевых слов.";
$_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_TAGHITLIST'] = "Есть полный рейтинговый лист для всех ключевых слов.";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_TITLE'] = "Сообщение";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_LINK'] = "Ссылка";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_ROW'] = "Какой-либо из слудующих путей назначения должен использоваться в содержании следующего блока: <pre><!-- BEGIN latestBlogMessages--><br />...<br /><!-- END latestBlogMessages --></pre>";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_ROWCLASS'] = "CSS-Класс для строки таблицы";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_ID'] = "Однозначное ID категории";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_DATE'] = "Дата сообщения";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_POSTEDBY'] = "Текст содержащий дату и пользователя";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_SUBJECT'] = "Заголовок сообщения";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_INTRODUCTION'] = "Короткий текст вступления сообщения";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_CONTENT'] = "Полный текст сообщения";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_AUTHOR_ID'] = "Однозначное ID автора";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_AUTHOR_NAME'] = "Имя автора";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_CATEGORIES'] = "Категории сообщения";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_TAGS'] = "Ключевые слова сообщения";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_COMMENTS'] = "Количество примечаний сообщения";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_VOTING'] = "Средняя оценка сообщения";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_STARS'] = "Рейтинг сообщения звездочками";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_LINK'] = "Ссылка к списку файлов";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_IMAGE'] = "Назначенное изображение";
$_ARRAYLANG['TXT_BLOG_BLOCK_CATEGORY_TITLE'] = "Категории";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_NAME'] = "Название категории";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_COUNT'] = "Количество сообщений в этой категории";
$_ARRAYLANG['TXT_BLOG_BLOCK_TEXT'] = "Текст";
$_ARRAYLANG['TXT_BLOG_BLOCK_CONTENT'] = "Содержание";
$_ARRAYLANG['TXT_BLOG_BLOCK_EXAMPLE'] = "Пример кода";
$_ARRAYLANG['TXT_BLOG_NETWORKS'] = "Сеть";
$_ARRAYLANG['TXT_BLOG_NETWORKS_OVERVIEW_NONE'] = "В данный момент никакие сети не зарегестрированы.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_OVERVIEW_SUBMIT_DELETE'] = "Удалить выделенное";
$_ARRAYLANG['TXT_BLOG_NETWORKS_OVERVIEW_SUBMIT_DELETE_JS'] = "Вы действительно хотите удалить все выделенные сети?";
$_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_TITLE'] = "Добавить сеть";
$_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_NAME'] = "Имя постовщика";
$_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_WWW'] = "URL постовщика";
$_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_SUBMIT'] = "URL для регестрации";
$_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_ICON'] = "Ярлык постовщика";
$_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_BROWSE'] = "Обзор";
$_ARRAYLANG['TXT_BLOG_NETWORKS_INSERT_SUCCESSFULL'] = "Поставщик сети успешно добавлен.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_INSERT_ERROR'] = "Ваш запрос вызвал одну или более ошибок. Поставщик сети не может быть установлен.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_EDIT_TITLE'] = "Редактирование сети";
$_ARRAYLANG['TXT_BLOG_NETWORKS_EDIT_ERROR'] = "Ваш запрос вызвал одну или больее ошибок. Поставщик сети не может быть обновлен.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_UPDATE_SUCCESSFULL'] = "Сеть успешно удалена.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_UPDATE_ERROR'] = "Ваш запрос вызвал одну или больее ошибок. Поставщик сети не может быть обновлен.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_DELETE_TITLE'] = "Удалить сеть";
$_ARRAYLANG['TXT_BLOG_NETWORKS_DELETE_JS'] = "Вы действительно хотите удалить эту сеть?";
$_ARRAYLANG['TXT_BLOG_NETWORKS_DELETE_SUCCESSFULL'] = "Сеть успешно удалена.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_DELETE_ERROR'] = "Сеть с этим ID не была найдена.";
$_ARRAYLANG['TXT_BLOG_LIB_POSTED_BY'] = "прислано от [USER] в [DATE]";
$_ARRAYLANG['TXT_BLOG_LIB_CALENDAR_WEEKDAYS'] = "Вс,Пн,Вт,Ср,Чт,Пт,Сб";
$_ARRAYLANG['TXT_BLOG_LIB_CALENDAR_MONTHS'] = "Январь, Февраль, Март, Апрель, Май, Июнь, Июль, Август, Сентябрь, Октябрь, Ноябрь, Декабрь";
$_ARRAYLANG['TXT_BLOG_LIB_RATING'] = "Оценка";
$_ARRAYLANG['TXT_BLOG_LIB_ALL_CATEGORIES'] = "Все комментарии";
$_ARRAYLANG['TXT_BLOG_LIB_RSS_MESSAGES_TITLE'] = "Блог уведомления";
$_ARRAYLANG['TXT_BLOG_LIB_RSS_COMMENTS_TITLE'] = "Блог комментарий";
?>
