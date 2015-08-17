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

if (isset($_SESSION['status']) and ($_SESSION['status'] == "admin" or $_SESSION['status'] == "organizer")) {
    $menuhidden = "<li class='menu-section submenu_trigger' id='addmenu-admin'>ADMIN</li>";
} else {
    $menuhidden = "";
}

if (!empty($_SESSION['status']) && $_SESSION['status'] == "admin") {
    $configmenu = "
    <li class='menu-section' data-url='admin' data-param='op=config'><span id='addmenu'>Configuration</span></li>
    <li class='menu-section' data-url='admin' data-param='op=plugins'><span id='addmenu'>Plugins</span></li>
    <li class='menu-section' data-url='admin' data-param='op=cronjobs'><span id='addmenu'>CronJobs</span></li>";
} else {
    $configmenu = "";
}

echo "
<nav>
    <ul>
        <li class='menu-section' data-url='home'>HOME</li>
        <li class='menu-section submenu_trigger' id='addmenu-pres'>SUBMIT</li>
        <li class='menu-section' data-url='archives'>ARCHIVES</li>
        <li class='menu-section' data-url='contact'>CONTACT</li>
        $menuhidden
    </ul>
</nav>

<nav class='submenu' id='addmenu-pres'>
    <ul>
        <li class='menu-section' data-url='submission' data-param='op=new'><span id='addmenu'>New presentation</span></li>
        <li class='menu-section' data-url='submission' data-param='op=wishpick'><span id='addmenu'>Pick a wish</span></li>
        <li class='menu-section' data-url='submission' data-param='op=suggest'><span id='addmenu'>Make a wish</span></li>
    </ul>
</nav>

<nav class='submenu' id='addmenu-admin'>
    <ul>
        <li class='menu-section' data-url='admin' data-param='op=sessions'><span id='addmenu'>Manage Sessions</span></li>
        <li class='menu-section' data-url='admin' data-param='op=users'><span id='addmenu'>Manage users</span></li>
        <li class='menu-section' data-url='admin' data-param='op=mail'><span id='addmenu'>Send mail</span></li>
        <li class='menu-section' data-url='admin' data-param='op=post'><span id='addmenu'>Posts</span></li>
        $configmenu
    </ul>
</nav>

";
