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

if (!empty($_POST['id'])) {
    if (isset($_SESSION['username'])) {
        $user = new User($_SESSION['username']);
    } elseif (!empty($_POST['user'])) {
        $user = new User($_POST['user']);
    } else {
        $user = false;
    }

    $Suggestion = new Suggestion();
    $data = $Suggestion->getInfo(htmlspecialchars($_POST['id']));
    $show = $user !== false && (in_array($user->status, array('organizer', 'admin')) || $data['orator'] === $user->username);
    $content = Suggestion::details($data, $show, '#suggestion_container');

} else {
    $content = "Nothing to show here";
}
$content = "<section><div class='section_content' id='suggestion_container'>{$content}</div></section>";

echo $content;