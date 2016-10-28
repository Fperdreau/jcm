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

require('../../includes/boot.php');

// Declare classes
$user = new User($db,$_SESSION['username']);

// Plugins
$plugins = new AppPlugins($db);
$plugin_list = $plugins->show();
$result = "
    <div class='page_header'>
    <h1>Plugins</h1>
    <p class='page_description'>Here you can install, activate or deactivate plugins and manage their settings.
    Your plugins must be located in the 'plugins' directory in order to be automatically loaded by the Journal Club Manager.</p>
    </div>
    
    <div class='feedback'></div>
    <section>
        <h2>Plugins list</h2>
        <div class='section_content'>{$plugin_list}</div>
    </section>
";

echo json_encode($result);
exit;
