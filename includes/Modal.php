<?php

/**
 * Created by PhpStorm.
 * User: Florian
 * Date: 22/03/2017
 * Time: 10:00
 */
class Modal {

    /**
     * Get modal content
     * @param array $post
     * @return bool|string
     */
    public function get_modal(array $post) {
        $controllerName = htmlspecialchars($post['controller']);
        $action = htmlspecialchars($post['action']);
        $params = isset($post['params']) ? explode(',', htmlspecialchars($post['params'])) : array();
        if (class_exists($controllerName, true)) {
            $Controller = new $controllerName();
            if (method_exists($controllerName, $action)) {
                $content = call_user_func_array(array($Controller,$action), $params);
                if (isset($post['section'])) $content['id'] = $post['section'];
                return self::section($content);
            }
        }
        return false;
    }

    /**
     * Set modal content
     * @param array $post
     * @return bool|string
     */
    public function set_modal(array $post) {
        return self::section($post);
    }

    /**
     * Render modal template
     * @param null $content
     * @param $title
     * @return string
     */
    public static function template($content=null, $title=null) {
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
    public static function section(array $data) {
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
    public static function confirmation_box($data) {
        $buttons = "
            <div class='action_btns'>
                <div class='one_half'>
                    <input type='submit' name='cancel' class='fa-angle-double-left' value='Cancel'>
                </div>
                <div class='one_half last'><input type='submit' name='confirmation' value='{$data['button_txt']}'></div>
            </div>";

        $html = "<div class='sys_msg warning confirmation_text'>{$data['text']}</div>";

        return self::section(array(
                    'get_confirmation_box'=> true,
                    'id'=> 'confirmation_box',
                    'content'=> $html,
                    'title'=> 'Delete confirmation',
                    'buttons'=> $buttons
                ));
    }

}