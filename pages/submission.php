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

require('../includes/boot.php');

// Declare classes
$username = (isset($_GET['user'])) ? $_GET['user']:$_SESSION['username'];
$user = new User($db, $username);

// Get options
$op = htmlspecialchars($_GET['op']);
$result = "Oops";
$date = (!empty($_GET['date'])) ? htmlspecialchars($_GET['date']): false;

// Submit a new presentation
if ($op == 'new') {
    $submit_form = Presentation::displayform($user, false, 'submit', false, $date);
    $result = "
        <p class='page_description'>Book a Journal Club session to present a paper, your research, or a
        methodology topic. <br>
        Fill up the form below, select a date (only available dates are selectable) and it's all done!
        Your submission will be automatically added to our database.<br>
        If you want to edit or delete your submission, you can find it on your <a href='index.php?page=profile'>profile page</a>!</p>
        <section id='submission_form'>
            <h2>Submit a presentation</h2>
            <div class='section_content'>$submit_form</div>
        </section>
    ";

// Suggest a presentation
} elseif ($op == 'suggest') {
    $submit_form = Presentation::displayform($user, false, "suggest");
    $result = "
        <p class='page_description'>Here you can suggest a paper that somebody else could present at a Journal Club session.
         Fill up the form below and that's it! Your suggestion will immediately appear in the wishlist.<br>
        If you want to edit or delete your submission, you can find it on your <a href='index.php?page=profile'>profile page</a>!</p>
        <h2>Suggest a wish</h2>
        <section id='submission_form'>
        <div class='section_content'>$submit_form</div>
        </section>
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
        $submit_form = Presentation::displayform($user, $Presentation,'submit');
    } else {
        $submit_form = "";
    }

    $result = "
        <p class='page_description'>Here you can choose a suggested paper from the wishlist that you would like to present.<br>
            The form below will be automatically filled up with the data provided by the user who suggested the selected paper.
            Check that all the information is correct and modify it if necessary, choose a date to present and it's done!<br>
            If you want to edit or delete your submission, you can find it on your <a href='index.php?page=profile'>profile page</a>!</p>
        <h2>Select a wish</h2>
        <section>
            <div class='section_content'>
            $selectopt
            <div id='submission_form' class='wishform'>
            $submit_form
            </div>
            </div>
        </section>
    ";

// Modify a presentation
} elseif ($op == 'mod_pub') {
    $Presentation = new Presentation($db, $_GET['id']);
    $submit_form = Presentation::displayform($user, $Presentation, 'submit');
    $result = "
        <p class='page_description'>Here you can modify your submission. Please, check on the information before submitting your presentation</p>
        <h2>Modify a presentation</h2>
        <section id='submission_form'>
            $submit_form
        </section>
    ";
}

echo json_encode($result);
exit;
