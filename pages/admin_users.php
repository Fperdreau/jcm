<?php
/*
Copyright � 2014, Florian Perdreau
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

// Declare classes
$user = new User($db,$_SESSION['username']);

// Manage users
$userlist = $Users->generateuserslist();

$result = "
<h1>Manage Users</h1>
<p class='page_description'>Here you can modify users status and activate, deactivate or delete user accounts.</p>
<section>
    <h2>Users List</h2>
    <div class='feedback'></div>
    <div class='table_container' id='user_list'>
        $userlist
    </div>
</section>";

echo json_encode($result);
exit;
