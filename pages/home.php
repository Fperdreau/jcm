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

$last_news = new Posts($db);
$sessions = new Sessions($db);
$presentations = new Presentations($db);

$news = $last_news->show(true);
$futurepres = $sessions->showfuturesession(4);
$wishlist = $presentations->getwishlist(10,true);
if (isset($_SESSION['logok']) && $_SESSION['logok']) {
    $submitMenu = Presentation::submitMenu('float');
} else {
    $submitMenu = null;
}

$result = "
    $submitMenu

    <section>
        <h2>Last News</h2>
        <div class='section_content'>
            <div class='news'>
                $news
            </div>
        </div>
    </section>

    <div class='section_container'>
        <section>
            <h2>Next Sessions</h2>
            <div class='section_content'>
                <div class='form-group'>
                    <input type='date' class='selectSession' data-status='false' id='datepicker' name='date'>
                    <label>Session to show</label>
                </div>
                <div id='sessionlist'>
                    $futurepres
                </div>
            </div>
        </section>

        <section>
            <h2>Wish list</h2>
            <div class='section_content'>
                $wishlist
            </div>
        </section>
    </div>
";
echo $result;