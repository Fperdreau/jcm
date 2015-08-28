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

require_once('../includes/boot.php');
$last_news = new Posts($db);
$sessions = new Sessions($db);
$presentations = new Presentations($db);

/** @var $news Sessions */
$news = $last_news->show();

/** @var $futurepres Sessions */
$futurepres = $sessions->showfuturesession(4);

/** @var $wishlist Sessions */
$wishlist = $presentations->getwishlist();

// Submission menu
if ((!isset($_SESSION['logok']) || $_SESSION['logok'] == false)) {
    $submitMenu = "";
} else {
    $submitMenu = "
    <div class='submitMenu'>
        <div class='submitMenuSection'>
            <a href='#modal' class='modal_trigger' id='modal_trigger_newpub' rel='leanModal' data-type='submit'>
               Submit</a>
        </div>
        <div class='submitMenuSection'>
            <a href='#modal' class='modal_trigger' id='modal_trigger_newpub' rel='leanModal' data-type='suggest'>
           Make a wish</a>
        </div>
        <div class='submitMenuSection'>
            <a href='#modal' class='modal_trigger' id='modal_trigger_newpub' rel='leanModal' data-type='select'>
           Select a wish</a>
        </div>
    </div>";
}

$result = "
    $submitMenu

    <section>
        <h2>News</h2>
        <div class='news'>
                $news
        </div>
    </section>

    <div class='section_container'>
        <section>
            <h2>Next Sessions</h2>
            <div class='formcontrol'>
                <label>Session to show</label>
                <input type='date' class='selectSession' data-status='false' id='datepicker' name='date'>
            </div>
            <div id='sessionlist'>
                $futurepres
            </div>
        </section>

        <section>
            <h2>Wish list</h2>
            $wishlist
        </section>
    </div>
";

echo json_encode($result);
exit;
