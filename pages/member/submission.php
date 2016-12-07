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
if (!isset($_SESSION['logok']) || !$_SESSION['logok']) {
    echo json_encode(AppPage::login_required());
    exit();
}

$username = (isset($_POST['user'])) ? $_POST['user']:$_SESSION['username'];
$user = new User($db, $username);

// Get options
$result = null;
$submit_form = null;
$section_content = null;

if (isset($_POST['op'])) {
    $op = htmlspecialchars($_POST['op']);
    $result = "Oops";
    $date = (!empty($_POST['date'])) ? htmlspecialchars($_POST['date']): false;
// Submit a new presentation
    if ($op == 'edit') {
        if (!empty($_POST['id'])) {
            $id_pres = htmlspecialchars($_POST['id']);
            $Presentation = new Presentation($db,$id_pres);
            $date = $Presentation->date;
        } else {
            $Presentation = false;
        }
        $section_content = Presentation::form($user, false, 'edit', false, $date);

// Suggest a presentation
    } elseif ($op == 'suggest') {
        $section_content = Presentation::form($user, false, "suggest");

// Select from the wish list
    } elseif ($op == 'wishpick') {
        if (!empty($_POST['id'])) {
            $id_pres = htmlspecialchars($_POST['id']);
            $Presentation = new Presentation($db,$id_pres);
        } else {
            $Presentation = false;
        }

        $selectopt = $Presentations->generate_selectwishlist('.submission_container');
        if (!empty($_POST['id']) || !empty($_POST['update'])) { // a wish has been selected
            $submit_form = Presentation::form($user, $Presentation,'submit');
        } else {
            $submit_form = "";
        }

        $section_content['title'] = "Select a wish";
        $section_content['description'] = $Presentation::description('wishpick');
        $section_content['content'] = $selectopt;

// Modify a presentation
    } elseif ($op == 'mod_pub') {
        $Presentation = new Presentation($db, $_POST['id']);
        $section_content = Presentation::form($user, $Presentation, 'submit');
    }
}

// Submission menu
$submitMenu = "
    <div class='submitMenu_fixed'>
        <div class='submitMenuContainer'>
            <div class='submitMenuSection'>
                <a href='" . AppConfig::$site_url . 'index.php?page=submission&op=edit' . "' class='load_content' data-section='submission_form' data-type='submit'>
                   <div class='icon_container'>
                        <div class='icon'><img src='" . AppConfig::$site_url.'images/add_paper.png'. "'></div>
                        <div class='text'>Submit</div>
                    </div>
               </a>
            </div>
            <div class='submitMenuSection'>
                <a href='" . AppConfig::$site_url . 'index.php?page=submission&op=suggest' . "' class='load_content' data-section='submission_form' data-type='suggest'>
                   <div class='icon_container'>
                        <div class='icon'><img src='" . AppConfig::$site_url.'images/wish_paper.png'. "'></div>
                        <div class='text'>Add a wish</div>
                    </div>
                </a>
            </div>
            <div class='submitMenuSection'>
                <a href='" . AppConfig::$site_url . 'index.php?page=submission&op=wishpick' . "' class='load_content' data-section='submission_form' data-type='select'>
                    <div class='icon_container'>
                        <div class='icon'><img src='" . AppConfig::$site_url.'images/select_paper.png'. "'></div>
                        <div class='text'>Select a wish</div>
                    </div>
                </a>
            </div>
        </div>
    </div>";

$form_section = null;
if (!is_null($section_content)) {
    $form_section = $Presentation::format_section($section_content);
}

$result = "
    {$submitMenu}
    <div class='submission_container'>{$form_section}</div> 
";
echo $result;