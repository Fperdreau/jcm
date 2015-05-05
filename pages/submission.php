<?php
/*
Copyright © 2014, Florian Perdreau
This file is part of Journal Club Manager.

Journal Club Manager is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Journal Club Manager is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with Journal Club Manager.  If not, see <http://www.gnu.org/licenses/>.
*/

require('../includes/boot.php');
check_login();
global $db, $Presentations;
// Declare classes
$user = new User($db,$_SESSION['username']);

// Get options
$op = htmlspecialchars($_GET['op']);
$result = "Oops";
$date = (!empty($_GET['date'])) ? htmlspecialchars($_GET['date']): false;

// Submit a new presentation
if ($op == 'new') {
    $submit_form = displayform($user,false,'submit',false,$date);
    $result = "
    <div id='content'>
        <div id='pagename'>Submit a presentation</div>
        <p class='page_description'>Book a Journal Club session to present a paper, your research, or a
        methodology topic. <br>
        Fill up the form below, select a date (only available dates are selectable) and it's all done!
        Your submission will be automatically added to our database.<br>
        If you want to edit or delete your submission, you can find it on your <a href='index.php?page=profile'>profile page</a>!</p>
        <div class='section_content' id='submission_form'>
        $submit_form
        </div>
    </div>
    ";

// Suggest a presentation
} elseif ($op == 'suggest') {
    $submit_form = displayform($user,false,"suggest");
    $result = "
    <div id='content'>
        <div id='pagename'>Suggest a wish</div>
        <p class='page_description'>Here you can suggest a paper that somebody else could present at a Journal Club session.
         Fill up the form below and that's it! Your suggestion will immediately appear in the wishlist.<br>
        If you want to edit or delete your submission, you can find it on your <a href='index.php?page=profile'>profile page</a>!</p>
        <div class='section_content' id='submission_form'>
        $submit_form
        </div>
    </div>
    ";

// Select from the wish list
} elseif ($op == 'wishpick') {
    if (!empty($_GET['id'])) {
        $id_pres = htmlspecialchars($_GET['id']);
        $Presentation = new Presentation($db,$id_pres);
    } else {
        $Presentation = false;
    }

    $selectopt = $Presentations->generate_selectwishlist();
    if (!empty($_GET['id']) || !empty($_POST['update'])) { // a wish has been selected
        $submit_form = displayform($user,$Presentation,'update');
    } else {
        $submit_form = "";
    }

    $result = "
    <div id='content'>
        <div id='pagename'>Select a wish</div>
        <p class='page_description'>Here you can choose a suggested paper from the wishlist that you would like to present.<br>
            The form below will be automatically filled up with the data provided by the user who suggested the selected paper.
            Check that all the information is correct and modify it if necessary, choose a date to present and it's done!<br>
            If you want to edit or delete your submission, you can find it on your <a href='index.php?page=profile'>profile page</a>!</p>
        <div class='section_content'>
            $selectopt
            <div id='submission_form' class='wishform'>
            $submit_form
            </div>
        </div>
    </div>
    ";

// Modify a presentation
} elseif ($op == 'mod_pub') {
    $submit_form = displayform($user,$Presentation,'update');
    $result = "
    <div id='content'>
        <div id='pagename'>Modify a presentation</div>
        <p class='page_description'>Here you can modify your submission. Please, check on the information before submitting your presentation</p>
        <div class='section_content' id='submission_form'>
            $submit_form
        </div>
    </div>
    ";
}

echo json_encode($result);
