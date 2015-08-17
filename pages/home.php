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
$db = new AppDb();
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

$result = "
    <div id='content'>
        <section class='news'>
            <h2>News</h2>
            $news
        </section>

        <section>
            <h2>Next Sessions</h2>
        	$futurepres
		</section>

        <section>
            <h2>Wish list</h2>
        	$wishlist
        </section>
    </div>
";

echo json_encode($result);
