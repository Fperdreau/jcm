<?php
/**
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

require('../../includes/boot.php');

// Show an empty form
$user = new User($db,$_SESSION['username']);
$post = new Posts($db);

// Make post selection list
$postlist = $db->getinfo($db->tablesname['Posts'],"postid");
$options = "
    <select class='select_post' data-user='$user->fullname'>
        <option value='' selected>Select a post to modify</option>
    ";
if (!empty($postlist)) {

    foreach ($postlist as $postid) {
        $post = new Posts($db,$postid);
        if ($post->homepage==1) {
            $style = "style='background-color: rgba(207,81,81,.3);'";
        } else {
            $style = "";
        }
        $options .= "<option value='$post->postid' $style><b>$post->date |</b> $post->title</option>";
    }
} else {
    $options .= "<option value='false'>Nohting yet</option>";
}
$options .= "</select>";

$result = "
    <div class='page_header'>
    <h1>News</h1>
    <p class='page_description'>Here you can add a post on the homepage.</p>
    </div>
    
    <div style='display: block; width: 100%;'>
        <div style='display: inline-block'>$options</div>
        <div style='display: inline-block'>or</div>
        <div style='display: inline-block'>
            <input type='button' id='submit' class='post_new' value='Add a new post'/>
        </div>
    </div>
    <section>
        <h2>New post</h2>
        <div class='section_content'>
            <div class='feedback'></div>
            <div class='postcontent'></div>
        </div>
    </section>
    ";

echo json_encode($result);
exit;
