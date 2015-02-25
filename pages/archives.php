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

@session_start();
require_once($_SESSION['path_to_includes'].'includes.php');
check_login();

$years = Presentations::get_years();

// Select input (Years)
$options = "
<option value='' selected>Select a year</option>
<option value='all'>All</option>";
foreach ($years as $year) {
    $options .= "<option value='$year'>$year</option>";
}

$publist = Presentations::getpublicationlist();
$result = "
    <div id='content'>
        <span id='pagename'>Archives</span>
        <div class='feedback'></div>
            <select name='year' class='archive_select'>
				$options
            </select>
        <div id='archives_list'>
        	$publist
        </div>
	</div>";

echo json_encode($result);
