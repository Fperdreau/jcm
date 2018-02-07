<?php
/**
 *
 * @author Florian Perdreau (fp@florianperdreau.fr)
 * @copyright Copyright (C) 2016 Florian Perdreau
 * @license <http://www.gnu.org/licenses/agpl-3.0.txt> GNU Affero General Public License v3
 *
 * This file is part of DropCMS.
 *
 * DropCMS is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * DropCMS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with DropCMS.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace includes;

/**
 * Class Pagination
 * @package Core\HTML
 */
class Pagination
{

    /**
     * Get Pagination
     * @param $tot_rows
     * @param $pp
     * @param $curr_page
     * @param $base_url
     * @return string
     */
    public static function getPaging($tot_rows, $pp, $curr_page, $base_url)
    {
        $paging_info = self::getPagingInfo($tot_rows, $pp, $curr_page, $base_url);
        return self::pagingMenu($paging_info);
    }

    /**
     * Gets paging information
     * @param int $tot_rows : counted rows for query
     * @param int $pp : items per page
     * @param int $curr_page : the current page number
     * @param string $base_url
     * @return array
     */
    public static function getPagingInfo($tot_rows, $pp, $curr_page, $base_url)
    {
        $pages = ($pp>0) ? ceil($tot_rows / $pp):$tot_rows; // calc pages

        $data = array(); // start out array
        $data['si'] = ($curr_page * $pp) - $pp; // what row to start at
        $data['pages'] = $pages;                   // add the pages
        $data['curr_page'] = (int)$curr_page;               // Whats the current page
        $data['curr_url'] = $base_url;
        $data['tot_rows'] = $tot_rows;
        return $data; //return the paging data
    }

    /**
     * Renders pagination menu
     * @param array $paging_info
     * @return string
     */
    public static function pagingMenu($paging_info)
    {
        $content = "";
        if ($paging_info['curr_page'] > 1) {
            $first_url = $paging_info['curr_url'] . '1';
            $prev_url = $paging_info['curr_url'] . ($paging_info['curr_page']-1);
            $content .= "
                <div><a href='{$first_url}' title='Page 1' id='paging_first'>
                <img src='" . URL_TO_IMG . 'first_arrow.png'."'></a></div>
                <div><a href='{$prev_url}' title='" . ($paging_info['curr_page'] - 1) . "'  id='paging_prev'>
                <img src='" . URL_TO_IMG . 'prev.png'."'></a></div>";
        } else {
            $content .= "
                <div style='opacity: 0.5'><img src='" . URL_TO_IMG . 'first_arrow.png' . "'></div>
                <div style='opacity: 0.5'><img src='" . URL_TO_IMG . 'prev.png' . "'></div>";
        }

        //setup starting point
        //$max is equal to number of links shown
        $max = 3;
        if ($paging_info['curr_page'] < $max) {
            $sp = 1;
        } elseif ($paging_info['curr_page'] >= ($paging_info['pages'] - floor($max / 2))) {
            $sp = $paging_info['pages'] - $max + 1;
        } elseif ($paging_info['curr_page'] >= $max) {
            $sp = $paging_info['curr_page'] - floor($max / 2);
        }

        // If the current page >= $max then show link to 1st page
        if ($paging_info['curr_page'] >= $max) {
            $page_url = $paging_info['curr_url'].'1';
            $content .= "<div><a href='$page_url' title='Page 1'>1</a></div>...";
        }

        // Loop though max number of pages shown and show links either side equal to $max / 2
        for ($i = $sp; $i <= ($sp + $max - 1); $i++) {
            if ($i > $paging_info['pages']) {
                continue;
            }

            if ($paging_info['curr_page'] == $i) {
                $content .= "<div id='paging_current'>{$i}</div>";
            } else {
                $page_url = $paging_info['curr_url'].$i;
                $content .= "<div><a href='{$page_url}' title='Page {$i}'>{$i}</a></div>";
            }
        }

        // If the current page is less than say the last page minus $max pages divided by 2
        if ($paging_info['curr_page'] < ($paging_info['pages'] - floor($max / 2))) {
            $page_url = $paging_info['curr_url'].$paging_info['pages'];

            $content .= "
            <div>..</div>
            <div><a href='$page_url' title='{$paging_info['pages']}'>" . $paging_info['pages'] . "</a></div>
            ";
        }

        //<!-- Show last two pages if we're not near them -->
        if ($paging_info['curr_page'] < $paging_info['pages']) {
            $last_url = $paging_info['curr_url'].$paging_info['tot_rows'];
            $next_url = $paging_info['curr_url'].($paging_info['curr_page']+1);
            $content .= "
            <div><a href='{$next_url}' title='Page ". ($paging_info['curr_page'] + 1). "' id='paging_next'><img src='".URL_TO_IMG.'next.png'."'></a></div>
            <div><a href='{$last_url}' title='Page {$paging_info['pages']}' id='paging_last'><img src='".URL_TO_IMG.'last_arrow.png'."'></a></div>
            ";
        } else {
            $content .= "
            <div style='opacity: 0.5'><img src='" . URL_TO_IMG . 'next.png' . "'></div>
            <div style='opacity: 0.5'><img src='" . URL_TO_IMG . 'last_arrow.png' . "'></div>
            ";
        }

        return "<div id='pagingMenu'><div id='paging_nav'>{$content}</div></div>";
    }

    /**
     * Renders select input to select number of products to display per page
     * @param null|string $label: input's label
     * @return string
     */
    public static function pageSelector($label = null)
    {
        $label = (is_null($label)) ? _('Items/Page'):$label;
        $allowed = array(5, 10, 15, 20, 50);
        $options = "";
        foreach ($allowed as $nb) {
            $selected =($nb == $_SESSION['pp'])?'selected':null;
            $options .= "<option value='{$nb}' {$selected}>{$nb}</option>";
        }
        return "
        <div class='pageSelector_container'>
            <form method='post' action='".URL_TO_APP.'collections/update_pp'."'>
                <select name='pp' class='ajax_select'>
                    <option value='' disabled selected class='disabled_select'>{$label}</option>
                    {$options}
                </select>
            </form>
        </div>
        ";
    }

}
