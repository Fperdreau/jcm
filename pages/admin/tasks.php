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

// Declare classes
$user = new User($_SESSION['username']);
$AppCron = new AppCron();

$cronOpt = $AppCron->show();
$AppConfig = AppConfig::getInstance();
$result = "
    <div class='page_header'>
    <p class='page_description'>Here you can install, activate or deactivate scheduled tasks and manage their settings.
    Please note that in order to make these tasks running, you must have set a scheduled task pointing to 'cronjobs/run.php'
    either via a Cron AppTable (Unix server) or via the Scheduled Tasks Manager (Windows server)</p>
    </div>    
    
    <section>
        <h2>General settings</h2>
        <div class='section_content'>
            <p>You have the possibility to receive logs by email every time a task is executed.</p>
            <form method='post' action='php/form.php' class='form' id='config_form_site'>
                <input type='hidden' name='config_modify' value='true'/>
                <div class='form-group' style='width: 300px;'>
                    <select name='notify_admin_task'>
                        <option value='{$AppConfig->notify_admin_task}' selected>{$AppConfig->notify_admin_task}</option>
                        <option value='yes'>Yes</option>
                        <option value='no'>No</option>
                    </select>
                    <label>Get notified by email</label>
                </div>
                <input type='submit' name='modify' value='Modify' class='processform'>
                <div class='feedback' id='feedback_site'></div>
            </form>
        </div>
    </section>
    
    <div class='feedback'></div>

    <section>
        <h2>Tasks list</h2>
        <div class='section_content'>
        {$cronOpt}
        </div>
    </section>
";

echo $result;