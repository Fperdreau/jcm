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

$news = $last_news->show(true);
$futurepres = $sessions->showfuturesession(4);
$wishlist = $presentations->getwishlist(10,true);

// Submission menu
    $submitMenu = "
    <div class='submitMenu'>
        <div class='submitMenuSection'>
            <a href='' class='leanModal' id='modal_trigger_newpub' data-section='submission_form' data-type='submit'>
               <div class='icon_container'>
                    <div class='icon'><img src='" . AppConfig::$site_url.'images/submit.png'. "'></div>
                    <div class='text'>Submit</div>
                </div>
           </a>
        </div>
        <div class='submitMenuSection'>
            <a href='' class='leanModal' id='modal_trigger_newpub' data-section='submission_form' data-type='suggest'>
               <div class='icon_container'>
                    <div class='icon'><img src='" . AppConfig::$site_url.'images/wish.png'. "'></div>
                    <div class='text'>Add a wish</div>
                </div>
            </a>
        </div>
        <div class='submitMenuSection'>
            <a href='' class='leanModal' id='modal_trigger_newpub' data-section='submission_form' data-type='select'>
                <div class='icon_container'>
                    <div class='icon'><img src='" . AppConfig::$site_url.'images/select.png'. "'></div>
                    <div class='text'>Select a wish</div>
                </div>
            </a>
        </div>
    </div>";


$result = "
    $submitMenu

    <section>
        <h2>Last News</h2>
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
