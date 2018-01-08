<?php
/**
 * File for class Users and User
 *
 * PHP version 5
 *
 * @author Florian Perdreau (fp@florianperdreau.fr)
 * @copyright Copyright (C) 2014 Florian Perdreau
 * @license <http://www.gnu.org/licenses/agpl-3.0.txt> GNU Affero General Public License v3
 *
 * This file is part of Journal Club Manager.
 *
 * Journal Club Manager is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Journal Club Manager is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Journal Club Manager.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace includes;

/**
 * Class Modal
 *
 * Handles templates for modal windows
 */
class Modal
{

    /**
     * Render section
     *
     * @param array $data: section content
     * @return string
     */
    public static function render(array $data)
    {
        return self::section($data);
    }

    /**
     * Get dialog box
     *
     * @param $type
     * @return mixed
     * @throws Exception
     */
    public static function getBox($type)
    {
        $action = $type . 'Box';
        if (method_exists(get_class(), $action)) {
            return self::$action($_POST);
        } else {
            throw new \Exception("'{$action}' method does not exist for class Modal'");
        }
    }

    /**
     * Render modal template
     * @param null $content
     * @param $title
     * @return string
     */
    public static function template($content = null, $title = null)
    {
        return "         
        <div id='modal' class='modalContainer' style='display:none;'>
            <div class='popupBody' style='display:inline-block'>
                {$content}
                <div class='feedback'></div>
            </div>
            <div class='float_buttons_container'>
                <div class='back_btn'></div>
                <div class='modal_close'></div>
            </div>
        </div>
        ";
    }

    /**
     * Render modal section
     * @param array $data
     * @return string
     */
    public static function section(array $data)
    {
        return "
            <section class='modal_section' id='{$data['id']}'>
                <div class='popupHeader'>{$data['title']}</div>
                <div class='popupContent'>{$data['content']}</div>
                <div class='modal_buttons_container'>{$data['buttons']}</div>
            </section>
        ";
    }

    /**
     * Render confirmation box
     * @param $data
     * @return string
     */
    public static function confirmationBox($data)
    {
        $title = !empty($data['title']) ? $data['title'] : "Confirmation";
        $buttons_confirm = !empty($data['button_txt']) ? "
            <input type='submit' name='confirmation' value='{$data['button_txt']}'>
            " : null;

        $buttons = "
                <div class='one_half'>
                    <input type='submit' name='cancel' class='fa-angle-double-left' value='Cancel'>
                </div>
                <div class='one_half last'>{$buttons_confirm}</div>
        ";

        $html = "<div class='confirmation_text'><div class='sys_msg warning'>{$data['text']}</div></div>";

        return self::section(array(
                    'get_confirmation_box'=> true,
                    'id'=> 'confirmation_box',
                    'content'=> $html,
                    'title'=> $title,
                    'buttons'=> $buttons
                ));
    }

    /**
     * Render confirmation box
     * @param $data
     * @return string
     */
    public static function dialogBox($data)
    {
        $buttons = "
                <div class='one_half'>
                    <input type='submit' name='cancel' class='fa-angle-double-left' value='Cancel'>
                </div>
        ";

        $html = "<div class='confirmation_text'>{$data['text']}</div>";

        return self::section(array(
            'get_confirmation_box'=> true,
            'id'=> 'confirmation_box',
            'content'=> $html,
            'title'=> $data['title'],
            'buttons'=> $buttons
        ));
    }

}