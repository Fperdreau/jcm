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

require('../includes/boot.php');

$years = $Presentations->get_years();
// Select input (Years)
$options = "
<option value='' selected>Select a year</option>
<option value='all'>All</option>";
foreach ($years as $year) {
    $options .= "<option value='$year'>$year</option>";
}

$publist = $Presentations->getpublicationlist();

$result = "
    <div class='feedback'></div>
        <select name='year' class='archive_select'>
            $options
        </select>
    <div id='archives_list'>
        $publist
    </div>";

echo json_encode($result);
exit;
