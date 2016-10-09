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

/**
 * Menu
 * @todo: should be automatically built by AppPage class
 */

echo "
<nav>
    <ul>
        <li><a href='index.php?page=home' class='menu-section' id='home'>home</a></li>
        <li><a href='index.php?page=news' class='menu-section' id='news'>news</a></li>
        <li><a href='index.php?page=archives' class='menu-section' id='archives'>archives</a></li>
        <li><a href='index.php?page=contact' class='menu-section' id='contact'>contact</a></li>
        <li><a href='#' class='submenu_trigger' id='addmenu-admin'>admin</a></li>
    </ul>
</nav>

<nav class='submenu' id='addmenu-pres'>
    <ul>
        <li><a href='index.php?page=home&op=new' class='menu-section' id='submission' data-param='op=new'>New presentation</a></li>
        <li><a href='index.php?page=home&op=wishpick' class='menu-section' id='submission' data-param='op=wishpick'>Pick a wish</a></li>
        <li><a href='index.php?page=home&op=suggest' class='menu-section' id='submission' data-param='op=suggest'>Make a wish</a></li>
    </ul>
</nav>

<nav class='submenu' id='addmenu-admin'>
    <ul>
        <li><a href='index.php?page=sessions' class='menu-section' id='sessions'>Sessions</a></li>
        <li><a href='index.php?page=users' class='menu-section' id='users'>Users</a></li>
        <li><a href='index.php?page=email' class='menu-section' id='email'>Mailing</a></li>
        <li><a href='index.php?page=post' class='menu-section' id='post'>Posts</a></li>
        <li><a href='index.php?page=settings' class='menu-section' id='settings'>Settings</a></li>
        <li><a href='index.php?page=pages' class='menu-section' id='pages'>Pages</a></li>
        <li><a href='index.php?page=plugins' class='menu-section' id='plugins'>Plugins</a></li>
        <li><a href='index.php?page=tasks' class='menu-section' id='tasks'>Scheduled Tasks</a></li>
        <li><a href='index.php?page=digest' class='menu-section' id='digest'>Digest</a></li>
        <li><a href='index.php?page=reminder' class='menu-section' id='reminder'>Reminder</a></li>
        <li><a href='index.php?page=assignment' class='menu-section' id='assignment'>Assignments</a></li>
        <li><a href='index.php?page=logs' class='menu-section' id='logs'>System logs</a></li>
    </ul>
</nav>
";
