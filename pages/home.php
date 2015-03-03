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

require_once('../includes/boot.php');
$db = new DbSet();
$last_news = new Posts($db);
$sessions = new Sessions($db);
$presentations = new Presentations($db);

/** @var $news Sessions */
$news = $last_news->show();
/** @var $nextpres Sessions */
$nextpres = $sessions->shownextsession();
/** @var $futurepres Sessions */
$futurepres = $sessions->showfuturesession(4);
/** @var $wishlist Sessions */
$wishlist = $presentations->getwishlist();


if ( !(isset($_SESSION['logok']) && $_SESSION['logok'])) {
    $welcome_msg = "
    <div id='welcome_msg'>
        <p>Welcome to the Journal Club Manager &copy;!</p>
        <p>By <a href='#modal' rel='leanModal' id='modal_trigger_register' class='modal_trigger'>signing up</a> to our website, you will get access to the following
        features:</p>
        <ul>
        <li>Receive information about upcoming events by mail</li>
        <li>Book future sessions for your presentation</li>
        <li>Suggest papers that others could present (the wishlist)</li>
        <li>Manage (edit/delete) your presentation from your personal profile</li>
        <li>Access to the journal club archives</li>
        </ul>
        <p>Enjoy!</p>
        <p>The Journal Club Team</p>
        </div>
    ";
} else {
    $welcome_msg = "";
}

$result = "
    <div id='content'>
        <span id='pagename'>Home</span>
        $welcome_msg
        <div class='section_header'>News</div>
        <div class='section_content'>
            $news
        </div>

        <div class='section_header'>Next Session</div>
        <div class='section_content'>
        	$nextpres
		</div>
        <div class='section_header'>Future sessions</div>
        <div class='section_content' style='font-size: 13px;'>
        	$futurepres
		</div>
        <div class='section_header'>Wish list</div>
        <div class='section_content' style='font-size: 13px;'>
        	$wishlist
        </div>
    </div>
";

echo json_encode($result);
