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
 * Online Desk
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @package     cloudrexx
 * @subpackage  module_shop
 * @todo        Edit PHP DocBlocks!
 */

/**
 * Calendar
 */
function calendar(
    $QuantArray, $AnzahlDropdown, $AnzahlTxT,
    $DatumDesc, $DatumLabel, $ArrayRD, $Anzahl, $quantityLimit, $date='',
    $backgroundcolor='', $legende1='', $legende2='', $legende3='',
    $legende1Color='', $legende2Color='', $legende3Color='', $border='',
    $flagBackend=false
) {
    global $PHP_SELF, $params;
    global $HTTP_POST_VARS, $HTTP_GET_VARS;
    global $calendar_txt;

    if (!isset($calendar_txt)) {
        $calendar_txt = array();
    }
    if (!isset($calendar_txt['german'])) {
        $calendar_txt['german'] = array();
    }
    $calendar_txt['german']['monthes'] = array('', 'Januar', 'Februar', 'M&auml;rz', 'April', 'Mai', 'Juni', 'Juli',
                                            'August', 'September', 'Oktober','November', 'Dezember');
    $calendar_txt['german']['days'] = array('Montag', 'Dienstag', 'Mittwoch', 'Donnerstag','Freitag','Samstag', 'Sonntag');
    $calendar_txt['german']['first_day'] = 0;
    $calendar_txt['german']['misc'] = array('Vorhergehender Monat', 'Folgender Monat', 'Vorhergehender Tag', 'Folgender Tag');

    $param_d = array();
    $param_d['calendar_id'] = 1;
    $param_d['calendar_columns'] = 5;
    $param_d['show_day'] = 1;
    $param_d['show_month'] = 1;
    $param_d['nav_link'] = 1;
    $param_d['link_after_date'] = 1;
    $param_d['link_before_date'] = 0;

    $param_d['link_on_day'] = $PHP_SELF.'?date=%%dd%%';
    $param_d['font_face'] = 'Verdana, Arial, Helvetica';
    $param_d['font_size'] = 10;

    $param_d['bg_color'] = $backgroundcolor;
    $param_d['today_bg_color'] = '#A0C0C0';
    $param_d['font_today_color'] = '#888888';
    $param_d['font_color'] = '#000000';
    $param_d['font_nav_bg_color'] = $border;

    $param_d['font_nav_color'] = '#FFFFFF';
    $param_d['font_header_color'] = '#FFFFFF';
    $param_d['border_color'] = $border;
    $param_d['use_img'] = 1;

    $param_d['lang'] = 'german';
    $param_d['font_highlight_color']= '#FF0000';
    $param_d['bg_highlight_color'] = '#FF0000';
    $param_d['day_mode'] = 0;
    $param_d['time_step'] = 30;
    $param_d['time_start'] = '0:00';
    $param_d['time_stop'] = '24:00';
    $param_d['highlight'] = array();

    $param_d['highlight_type'] = 'highlight';
    $param_d['cell_width'] = 20;
    $param_d['cell_height'] = 20;
    $param_d['short_day_name'] = 1;
    $param_d['link_on_hour'] = $PHP_SELF.'?hour=%%hh%%';

    foreach (array_keys($param_d) as $key) {
        if (isset($params[$key])) {
            $param[$key] = $params[$key];
        } else {
            $param[$key] = $param_d[$key];
        }
    }

// TODO: never used
//    $monthes_name = $calendar_txt[$param['lang']]['monthes'];
    $param['calendar_columns'] = ($param['show_day']) ? 7 : $param['calendar_columns'];

    if ($date == '') {
        $timestamp = time();
    } else {
        $month = substr($date, 4 ,2);
        $day = substr($date, 6, 2);
        $year = substr($date, 0 ,4);
        $timestamp = mktime(0, 0, 0, $month, $day, $year);
    }

    $current_day = date('d', $timestamp);
    $current_month = date('n', $timestamp);
    $current_month_2 = date('m', $timestamp);
    $current_year = date('Y', $timestamp);
    $first_decalage = date('w', mktime(0, 0, 0, $current_month, 1, $current_year));
    $first_decalage = ($first_decalage == 0 ? 7 : $first_decalage);

    $current_day_index = date('w', $timestamp) + $calendar_txt[$param['lang']]['first_day'] - 1;
    $current_day_index = ($current_day_index == -1) ? 7 : $current_day_index;
    $current_day_name = $calendar_txt[$param['lang']]['days'][$current_day_index];
    //$current_month_name = $monthes_name[$current_month];
    $current_month_name = $calendar_txt['german']['monthes'][$current_month];
    $nb_days_month = date("t", $timestamp);

    $today_timestamp = time();
    $today_day = date('d', $today_timestamp);
    $today_month = date('m', $today_timestamp);
    $today_year = date('Y', $today_timestamp);

    $current_timestamp = mktime(0,0,0, $current_month, $current_day, $current_year);
    $first_date = ($current_timestamp > $today_timestamp
        ? "$current_day.$current_month_2.$current_year" : ''
    );

    ### CSS
    $output = '<style type="text/css">'."\n";
    $output .= '<!--'."\n";
    $output .= '    .zero { border-bottom: 0px; }'."\n";
    $output .= '    .calendarNav'.$param['calendar_id'].'     {  font-family: '.$param['font_face'].'; font-size: '.($param['font_size']-1).'px; font-style: normal; background-color: '.$param['bg_color'].'}'."\n";
    $output .= '    .calendarTop'.$param['calendar_id'].'     {  font-family: '.$param['font_face'].'; font-size: '.($param['font_size']+1).'px; font-style: normal; color: '.$param['border_color'].'; font-weight: bold;  background-color: '.$param['bg_color'].'}'."\n";
    $output .= '    .calendarToday'.$param['calendar_id'].' {  font-family: '.$param['font_face'].'; font-size: '.$param['font_size'].'px; font-weight: bold; color: '.$param['font_today_color'].'; background-color: '.$param['today_bg_color'].';}'."\n";
    $output .= '    .calendarFreieTag'.$param['calendar_id'].' {  font-family: '.$param['font_face'].'; font-size: '.$param['font_size'].'px; font-weight: bold; color: '.$param['font_today_color'].'; background-color: '.$legende1Color.';}'."\n";
    $output .= '    .calendarTeils'.$param['calendar_id'].' {  font-family: '.$param['font_face'].'; font-size: '.$param['font_size'].'px; font-weight: bold; color: '.$param['font_today_color'].'; background-color: '.$legende2Color.';}'."\n";
    $output .= '    .calendarReserviert'.$param['calendar_id'].' {  font-family: '.$param['font_face'].'; font-size: '.$param['font_size'].'px; font-weight: bold; color: '.$param['font_today_color'].'; background-color: '.$legende3Color.';}'."\n";
    $output .= '    .calendarDays'.$param['calendar_id'].'     {  width:'.$param['cell_width'].'; height:'.$param['cell_height'].'; font-family: '.$param['font_face'].'; font-size: '.$param['font_size'].'px; font-style: normal; color: '.$param['font_color'].'; background-color: '.$param['bg_color'].'; text-align: center}'."\n";
    $output .= '    .calendarHL'.$param['calendar_id'].'     {  width:'.$param['cell_width'].'; height:'.$param['cell_height'].';font-family: '.$param['font_face'].'; font-size: '.$param['font_size'].'px; font-style: normal; color: '.$param['font_highlight_color'].'; background-color: '.$param['bg_highlight_color'].'; text-align: center}'."\n";
    $output .= '    .calendarHeader'.$param['calendar_id'].'{  font-family: '.$param['font_face'].'; font-size: '.($param['font_size']-1).'px; background-color: '.$param['font_nav_bg_color'].'; color: '.$param['font_nav_color'].';}'."\n";
    $output .= '    .calendarTable'.$param['calendar_id'].' {  background-color: '.$param['bg_color'].'; border: 1px solid '.$param['border_color'].'}'."\n";
    $output .= '-->'."\n";
    $output .= '</style>'."\n";
    $output .= '<script type="text/javascript">'."\n";
    $output .= '// <![CDATA['."\n";
    $output .= 'function SetDate(Datum) {'."\n";
    $output .= '  document.getElementById("CalDate").value=Datum;'."\n";
    $output .= '  changeDropdown(Datum);'."\n";
    $output .= '}'."\n\n";
    $output .= 'function changeDropdown(datum) {'."\n";
    $output .= '  var DayArray = new Object();'."\n";
    $output .=  $QuantArray."\n\n";
    $output .= '  ProdTotal = '.$Anzahl.';'."\n";
    $output .= '  SelectedDatum = datum.split(".");'."\n";
    $output .= '  SelectedTag = parseInt(SelectedDatum[0]);'."\n";
    $output .= '  var verfuegbar = ProdTotal-DayArray[SelectedTag];'."\n".
//'alert("datum: "+datum+", ProdTotal: "+ProdTotal+", SelectedDatum[0]: "+SelectedDatum[0]+", SelectedTag: "+SelectedTag+", ProdTotal: "+ProdTotal+", verfuegbar: "+verfuegbar);'."\n".
        // Previously selected values
        'var quantity = document.getElementById("contactFormField_Quantity").selectedIndex;'."\n".
        'if (quantity == -1) quantity = 0;'."\n";
    $output .= '  document.getElementById("contactFormField_Quantity").options.length = 0;'."\n";
    $output .= '  for(i=1; i <= verfuegbar; i++) {'."\n";
    $output .= '    var newOption = new Option(i,i);'."\n";
    $output .= '    document.getElementById("contactFormField_Quantity").options[document.getElementById("contactFormField_Quantity").options.length] = newOption;'."\n";
    $output .= '  }'."\n".
        'document.getElementById("contactFormField_Quantity").selectedIndex = quantity;'."\n";
    $output .= '}'."\n";
    $output .= '// ]]>'."\n";
    $output .= '</script>'."\n";
    $output .= '<div class="table-responsive"><table  class="table" summary="" cellspacing="0" cellpadding="0" border="0">';
    $output .= '<tr><td class="zero" valign="top">';
    $output .= '<table class="'.
      ($flagBackend ? 'noborder' : 'calendarTable'.$param['calendar_id']).'" '.
      'summary="" border="0" cellpadding="2" cellspacing="1">'."\n";

    if ($param['show_month'] == 1) {
        $output .= '<tr>'."\n";
        $output .= '    <td class="'.
        ($flagBackend ? 'noborder' : 'calendarTop'.$param['calendar_id']).'" '.
        'colspan="'.$param['calendar_columns'].'" align="center">'."\n";

        if ($param['use_img'] ) {
            $output .= '';
        }
        if ( $param['day_mode'] == 1 ) {
            $output .= '        '.$current_day_name.' '.$current_day.' '.$current_month_name.' '.$current_year."\n";
        } else {
            $output .= '        '.$current_month_name.' '.$current_year."\n";
        }
        $output .= '    </td>'."\n";
        $output .= '</tr>'."\n";
    }
    if ($param['show_day'] == 1 && $param['day_mode'] == 0) {
        $output .= '<tr align="center">'."\n";
        $first_day = $calendar_txt[$param['lang']]['first_day'];
        for ($i = $first_day; $i < 7 + $first_day; $i++) {
            $index = ($i >= 7 ? 7 + $i : $i);
            $index = ($i <  0 ? 7 + $i : $i);
            $day_name = ( $param['short_day_name'] == 1 ) ? substr($calendar_txt[$param['lang']]['days'][$index], 0, 1) : $calendar_txt[$param['lang']]['days'][$index];
            $output .= '    <td class="calendarHeader'.$param['calendar_id'].'"><b>'.$day_name.'</b></td>'."\n";
        }

        $output .= '</tr>'."\n";
        $first_decalage = $first_decalage - $calendar_txt[$param['lang']]['first_day'];
        $first_decalage = ( $first_decalage > 7 ) ? $first_decalage - 7 : $first_decalage;
    } else {
        $first_decalage = 0;
    }

    $output .= '<tr align="center">';
    $int_counter = 0;

    if ( $param['day_mode'] == 1 ) {
        list($hour_start, $min_start) = explode(':', $param['time_start']);
        list($hour_end, $min_end) = explode(':', $param['time_stop']);
        $ts_start = ( $hour_start * 60 ) + $min_start;
        $ts_end = ( $hour_end * 60 ) + $min_end;
        $nb_steps = ceil( ($ts_end - $ts_start) / $param['time_step'] );

        for ( $i = 0; $i <= $nb_steps; $i++ ) {
            $current_ts = ($ts_start) + $i * $param['time_step'];
            $current_hour = floor($current_ts / 60);
            $current_min = $current_ts % 60;
            $current_hour = (strlen($current_hour) < 2) ? '0'.$current_hour : $current_hour;
            $current_min = (strlen($current_min) < 2) ? '0'.$current_min : $current_min;

            $highlight_current = ( isset($param['highlight'][date('Ymd', $timestamp).$current_hour.$current_min]) );
            $css_2_use = ( $highlight_current ) ? 'HL' : 'Days';
            $txt_2_use = ( $highlight_current && $param['highlight_type'] == 'text') ? $param['highlight'][date('Ymd', $timestamp).$current_hour.$current_min] : '';

            $output .= '<tr>'."\n";
            if ( $param['link_on_hour'] != '') {
                $output .= '    <td class="calendar'.$css_2_use.$param['calendar_id'].'" width="10%"><a href="'.str_replace('%%hh%%', date('Ymd', $timestamp).$current_hour.$current_min, $param['link_on_hour']).'">'.$current_hour.':'.$current_min.'</a></td>'."\n";
            } else {
                $output .= '    <td class="calendar'.$css_2_use.$param['calendar_id'].'" width="10%">'.$current_hour.':'.$current_min.'</td>'."\n";
            }
            $output .= '    <td class="calendar'.$css_2_use.$param['calendar_id'].'">'.$txt_2_use.'</td>    '."\n";
            $output .= '</tr>'."\n";
        }
    } else {
        for ($i = 1; $i < $first_decalage; $i++) {
            $output .= '<td class="calendarDays'.$param['calendar_id'].'">&nbsp;</td>'."\n";
            $int_counter++;
        }
        for ($i = 1; $i <= $nb_days_month; $i++) {
            $loop_timestamp = mktime(23,59,59, $current_month, $i, $current_year);
            $i_2 = ($i < 10) ? '0'.$i : $i;
            $highlight_current = isset($param['highlight'][date('Ym', $timestamp).$i_2]);

            if ( ($i + $first_decalage) % $param['calendar_columns'] == 2 && $i != 1) {
                $output .= '<tr align="center">'."\n";
                $int_counter = 0;
            }

            $css_2_use = ( $highlight_current ) ? 'HL' : 'Days';
            $txt_2_use = ( $highlight_current && $param['highlight_type'] == 'text') ? '<br>'.$param['highlight'][date('Ym', $timestamp).$i_2] : '';
            $day_link = "<a href=\"javascript:SetDate('$i.$current_month.$current_year')\">$i</a>";

            // Choose the first available date from today
            if (empty($first_date)) {
                if (   $today_timestamp < $loop_timestamp) {
                    if ((   empty($ArrayRD[$current_year][$current_month_2][$i])
                         || $ArrayRD[$current_year][$current_month_2][$i] < $Anzahl)) {
                        $first_date = "$i.$current_month.$current_year";
                    }
                }
            }

            if (   $today_year == $current_year
                && $today_month == $current_month
                && $today_day == $i
                && $param['link_on_day']
            ) {
                // zustand
                if (   isset($ArrayRD[$current_year][$current_month_2][$i])
                    && $ArrayRD[$current_year][$current_month_2][$i] >= $quantityLimit
                ) {
                    if ($ArrayRD[$current_year][$current_month_2][$i] < $Anzahl) {
                        // teilweise
                        $day_class = "calendarTeils".$param['calendar_id'];
                    } else {
                        // reserviert
                        $day_class = "calendarReserviert".$param['calendar_id'];
                        $day_link = $i;
                    }
                } else {
                    // frei
                    $day_class = 'calendarFreieTag'.$param['calendar_id'];
                }
                $output .= '<td class="'.$day_class.'">'.$day_link.''.$txt_2_use.'</td>'."\n";
            } else {
                if (   (   $param['link_after_date'] == 0
                        && $today_timestamp < $loop_timestamp)
                    || (   $param['link_before_date'] == 0
                        && $today_timestamp >= $loop_timestamp)
                ) {
                    $output .= '<td class="calendar'.$css_2_use.$param['calendar_id'].'">'.$i.$txt_2_use.'</td>'."\n";
                } else {
                    // zustand
                    if (   isset($ArrayRD[$current_year][$current_month_2][$i])
                        && $ArrayRD[$current_year][$current_month_2][$i] >= $quantityLimit
                    ) {
                        if ($ArrayRD[$current_year][$current_month_2][$i] < $Anzahl) {
                            // teilweise
                            $day_class = "calendarTeils".$param['calendar_id'];
                        } else {
                            // reserviert
                            $day_class = "calendarReserviert".$param['calendar_id'];
                            $day_link = $i;
                        }
                    } else {
                        // frei
                        $day_class = 'calendarFreieTag'.$param['calendar_id'];
                    }
                    $output .= '<td class="'.$day_class.'">'.$day_link.''.$txt_2_use.'</td>'."\n";
                }
//            } else {
//                $output .= '<td class="calendar'.$css_2_use.$param['calendar_id'].'">'.$i.'</td>'."\n";
            }
            $int_counter++;

            ### Row end
            if (  ($i + $first_decalage) % ($param['calendar_columns'] ) == 1 ) {
                $output .= '</tr>'."\n";
            }
        }
        $cell_missing = $param['calendar_columns'] - $int_counter;

        for ($i = 0; $i < $cell_missing; $i++) {
            $output .= '<td class="calendarDays'.$param['calendar_id'].'">&nbsp;</td>'."\n";
        }
        $output .= '</tr>'."\n";
    }

    if ($param['nav_link'] == 1) {
        // Enable/disable as needed
        $previous_month = date('Ymd', mktime(12,  0, 0, $current_month-1, '01',           $current_year));
        $previous_day   = date('Ymd', mktime(12,  0, 0, $current_month,   $current_day-1, $current_year));
        $next_day       = date('Ymd', mktime( 1, 12, 0, $current_month,   $current_day+1, $current_year));
        $next_month     = date('Ymd', mktime( 1, 12, 0, $current_month+1, '01',           $current_year));
        if ($param['use_img']) {
            // g is back, d is forward one day,
            // gg is back, dd is forward one month.
            // Enable/disable as needed
            $gg = '<img src="..'.ASCMS_MODULE_FOLDER.'/Egov/View/Media/gg.gif" border="0" alt="" />';
            $g  = '<img src="..'.ASCMS_MODULE_FOLDER.'/Egov/View/Media/g.gif" border="0" alt="" />';
            $d  = '<img src="..'.ASCMS_MODULE_FOLDER.'/Egov/View/Media/d.gif" border="0" alt="" />';
            $dd = '<img src="..'.ASCMS_MODULE_FOLDER.'/Egov/View/Media/dd.gif" border="0" alt="" />';
        } else {
            // Enable/disable as needed
            $gg = '&lt;&lt;';
            $g  = '&lt;';
            $d  = '&gt;';
            $dd = '&gt;&gt;';
        }

        if (   $param['link_after_date'] == 0
            && $today_timestamp < mktime(0,0,0, $current_month, $current_day+1, $current_year)
        ) {
            $next_day_link = '&nbsp;';
        } else {
            $next_day_link =
                '<a href="'.$PHP_SELF.
                ($flagBackend
                  ? '?cmd=Egov&amp;act=detail'
                  : '?section=Egov&amp;cmd=detail'
                ).
                '&amp;id='.intval($_REQUEST["id"]).'&amp;date='.$next_day.
                '" title="'.$calendar_txt[$param['lang']]['misc'][3].
                '">'.$d.'</a>'."\n";
        }

        if (   $param['link_before_date'] == 0
            && $today_timestamp > mktime(0,0,0, $current_month, $current_day, $current_year)
        ) {
            $previous_day_link = '&nbsp;';
        } else {
            $previous_day_link =
                '<a href="'.$PHP_SELF.
                ($flagBackend
                  ? '?cmd=Egov&amp;act=detail'
                  : '?section=Egov&amp;cmd=detail'
                ).
                '&amp;id='.intval($_REQUEST["id"]).'&amp;date='.$previous_day.
                '" title="'.$calendar_txt[$param['lang']]['misc'][2].
                '">'.$g.'</a>'."\n";
        }

        if (   $param['link_after_date'] == 0
            && $today_timestamp < mktime(0,0,0, $current_month+1, $current_day, $current_year)
        ) {
            $next_month_link = '&nbsp;';
        } else {
            $next_month_link =
                '<a href="'.$PHP_SELF.
                ($flagBackend
                  ? '?cmd=Egov&amp;act=detail'
                  : '?section=Egov&amp;cmd=detail'
                ).
                '&amp;id='.intval($_REQUEST["id"]).'&amp;date='.$next_month.
                '" title="'.$calendar_txt[$param['lang']]['misc'][1].
                '">'.$dd.'</a>'."\n";
        }

        if (   $param['link_before_date'] == 0
            && $today_timestamp >= mktime(0,0,0, $current_month, 1, $current_year)
        ) {
            $previous_month_link = '&nbsp;';
        } else {
            $previous_month_link =
                '<a href="'.$PHP_SELF.
                ($flagBackend
                  ? '?cmd=Egov&amp;act=detail'
                  : '?section=Egov&amp;cmd=detail'
                ).
                '&amp;id='.intval($_REQUEST["id"]).'&amp;date='.$previous_month.
                '" title="'.$calendar_txt[$param['lang']]['misc'][0].
                '">'.$gg.'</a>'."\n";
        }

        $output .= '<tr>'."\n";
        $output .= '    <td colspan="'.$param['calendar_columns'].'" class="'.
            ($flagBackend ? 'zero' : 'calendarDays'.$param['calendar_id']).
            '">'."\n";
        $output .= '        <table summary="" width="100%" border="0" >';
        $output .= '        <tr>'."\n";
        $output .= '            <td width="25%" align="left" class="'.
            ($flagBackend ? 'zero' : 'calendarDays'.$param['calendar_id']).
            '">'."\n";
        $output .= $previous_month_link;
        $output .= '            </td>'."\n";
        $output .= '            <td width="25%" align="center" class="'.
            ($flagBackend ? 'zero' : 'calendarDays'.$param['calendar_id']).
            '">'."\n";
        $output .= $previous_day_link;
        $output .= '            </td>'."\n";
        $output .= '            <td width="25%" align="center" class="'.
            ($flagBackend ? 'zero' : 'calendarDays'.$param['calendar_id']).
            '">'."\n";
        $output .= $next_day_link;
        $output .= '            </td>'."\n";
        $output .= '            <td width="25%" align="right" class="'.
            ($flagBackend ? 'zero' : 'calendarDays'.$param['calendar_id']).
            '">'."\n";
        $output .= $next_month_link;
        $output .= '            </td>'."\n";
        $output .= '        </tr>';
        $output .= '        </table>';
        $output .= '    </td>'."\n";
        $output .= '</tr>'."\n";

    }
    $output .= '</table>'."\n";
    $output .= '</td>';
    $output .= '<td'.
        ($flagBackend ? ' class="zero"' : '').
        '>&nbsp;&nbsp;</td>';
    $output .= '<td'.
        ($flagBackend ? ' class="zero"' : '').
        ' valign="top">';
    $output .= '<table summary="" cellspacing="2" cellpadding="2" border="0">';
    $output .= '<tr><td style="width: 15px; height: 15px; background-color: '.$legende1Color.'; border: 1px solid '.$border.';"></td><td'.
        ($flagBackend ? ' class="zero"' : '').
        '>'.$legende1.'</td></tr>';
    $output .= '<tr><td style="width: 15px; height: 15px; background-color: '.$legende2Color.'; border: 1px solid '.$border.';"></td><td'.
        ($flagBackend ? ' class="zero"' : '').
        '>'.$legende2.'</td></tr>';
    $output .= '<tr><td style="width: 15px; height: 15px; background-color: '.$legende3Color.'; border: 1px solid '.$border.';"></td><td'.
        ($flagBackend ? ' class="zero"' : '').
        '>'.$legende3.'</td></tr>';
    $output .= '</table>';
    $output .= '<br /><span class="egov-desc">'.$DatumLabel.
      ' <input type="text" name="contactFormField_1000" id="CalDate" value=""  readonly /><br />'.
      $DatumDesc;
    $output .= '<br /><br />'.$AnzahlTxT.': '.$AnzahlDropdown.'</span>';
    $output .= '</td></tr></table></div><br />'."\n";
    $output .= '<script type="text/javascript">'."\n";
    $output .= '// <![CDATA['."\n";
    $output .= 'if (document.getElementById("CalDate").value=="") {'."\n";
    $output .= 'document.getElementById("CalDate").value="'.$first_date.'";'."\n";
    $output .= '}'."\n";
    $output .= 'changeDropdown(document.getElementById("CalDate").value);';
    $output .= "\n// ]]>\n";
    $output .= '</script>'."\n";
    return $output;
}

?>
