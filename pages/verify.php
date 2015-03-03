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

require('../includes/boot.php');

if (!empty($_GET['hash']) && !empty($_GET['email']) && !empty($_GET['result'])) {
    $hash = htmlspecialchars($_GET['hash']);
    $email = htmlspecialchars($_GET['email']);
    $result = htmlspecialchars($_GET['result']);
    $user = new User();
    $valid = $user -> check_account_activation($hash,$email,$result);
    $result = "
    <div id='content'>
        <div class='section_header'>Activation</div>
        <div class='section_content'>
			<span id='warning'>$valid</span>
    	</div>
    </div>";

	echo json_encode($result);
}
