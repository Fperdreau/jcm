<?php
/**
 * Created by PhpStorm.
 * User: Florian
 * Date: 07/08/2017
 * Time: 10:43
 */

class Template {

    public static function index() {

    }

    /**
     * Render Section
     * @param array $content
     * @param null $id
     * @return string
     */
    public static function section(array $content, $id=null) {

        return "
            <section id='{$id}'>
                <h2>{$content['title']}</h2>
                <div class='section_content'>
                    {$content['body']}
                </div>
            </section>
        ";
    }

}