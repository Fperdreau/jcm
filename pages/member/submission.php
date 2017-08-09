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

// Declare classes
if (!Auth::is_logged()) {
    echo json_encode(Page::login_required());
    exit();
}

$username = (isset($_POST['Users'])) ? $_POST['Users']:$_SESSION['username'];
$user = new Users($username);

// Get options
$result = null;
$submit_form = null;
$section_content = null;

if (isset($_POST['op'])) {
    $op = htmlspecialchars($_POST['op']);
    $result = "Oops";
    $date = (!empty($_POST['date'])) ? htmlspecialchars($_POST['date']) : null;
// Submit a new presentation
    if ($op == 'edit') {
        if (!empty($_POST['id'])) {
            $id_pres = htmlspecialchars($_POST['id']);
            $Presentation = new Presentation($id_pres);
            $date = $Presentation->date;
        } else {
            $Presentation = null;
        }
        $section_content = Presentation::form($user, null, 'edit', null, $date);

// Suggest a presentation
    } elseif ($op == 'suggest') {
        $section_content = Suggestion::form($user, null, "suggest");

// Select from the wish list
    } elseif ($op == 'wishpick') {
        if (!empty($_POST['id'])) {
            $id_pres = htmlspecialchars($_POST['id']);
            $Presentation = new Suggestion($id_pres);
            $selectopt = $Presentation->generate_selectwishlist('.submission_container');
        } else {
            $Presentation = null;
            $selectopt = null;
        }

        if (!empty($_POST['id']) || !empty($_POST['update'])) { // a wish has been selected
            $submit_form = Presentation::form($user, $Presentation, 'submit');
        } else {
            $submit_form = "";
        }

        $section_content['title'] = "Select a wish";
        $section_content['description'] = Suggestion::description('wishpick');
        $section_content['content'] = $selectopt;

// Modify a presentation
    } elseif ($op == 'mod_pub') {
        $Presentation = new Presentation($_POST['id']);
        $section_content = Presentation::form($user, $Presentation, 'submit');
    }
}

// Submission menu
$submitMenu = Presentation::submitMenu('body');

$form_section = null;
if (!is_null($section_content)) {
    $form_section = Presentation::format_section($section_content);
}

$result = "
    {$submitMenu}
    <div class='submission_container'>{$form_section}</div> 
";
echo $result;
