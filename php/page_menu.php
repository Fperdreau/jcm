<?php
/*
Copyright © 2014, F. Perdreau, Radboud University Nijmegen
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
    $menuhidden = "<div class='menu-section' name='admin_tool' id='menu_admin'>ADMIN</div>";
} else {
    $menuhidden = "";
}
$users_url = "index.php?page=admin_tool&op=users";
$mail_url = "index.php?page=admin_tool&op=mail";
$config_url = "index.php?page=admin_tool&op=config";
$post_url = "index.php?page=admin_tool&op=post";
$tools_url = "index.php?page=admin_tool&op=tools";

echo "
<div class='menu-container'>
    <div class='menu-section' data-url='home'>HOME</div>
    <div class='menu-section' id='menu_pres'>PRESENTATION</div>
    <div class='menu-section' data-url='archives'>ARCHIVES</div>
    <div class='menu-section' data-url='contact'>CONTACT</div>
    $menuhidden
</div>

<div class='addmenu-pres'>
    <div class='addmenu-section' data-url='presentations' data-param='op=new'><span id='addmenu'>New presentation</span></div>
    <div class='addmenu-section' data-url='presentations' data-param='op=wishpick'><span id='addmenu'>Select from wish list</span></div>
    <div class='addmenu-section' data-url='presentations' data-param='op=suggest'><span id='addmenu'>Suggest a paper</span></div>
</div>

<div class='addmenu-admin'>
    <div class='addmenu-section' data-url='admin_tool' data-param='op=config'><span id='addmenu'>Configuration</span></div>
    <div class='addmenu-section' data-url='admin_tool' data-param='op=users'><span id='addmenu'>Manage users</span></div>
    <div class='addmenu-section' data-url='admin_tool' data-param='op=mail'><span id='addmenu'>Send mail</span></div>
    <div class='addmenu-section' data-url='admin_tool' data-param='op=post'><span id='addmenu'>Posts</span></div>
    <div class='addmenu-section' data-url='admin_tool' data-param='op=tools'><span id='addmenu'>Tools</span></div>
</div>

";