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

// Declare classes
$user = new User($db,$_SESSION['username']);
$Presentation = new Presentation($db);
$publication_list = $user->getpublicationlist(null);
$assignments = $user->getAssignments();
$notifStatus = ($user->notification == 1) ? "Yes":"No";
$reminderStatus = ($user->reminder == 1) ? "Yes":"No";
$assignStatus = ($user->assign == 1) ? "Yes":"No";
$result = "
    <h1>Hello $user->fullname!</h1>
    <div class='operation_button leanModal' id='user_delete' data-section='user_delete'>Delete my account</div>

    <div class='section_container'>
            <section>
                <h2>Personal information</h2>
                <form method='post' action='' class='form' id='profile_persoinfo_form'>
                    <div class='submit_btns'>
                        <input type='submit' name='user_modify' value='Modify' class='processform'/>
                    </div>
                    <div class='formcontrol'>
                        <label for='firstname'>First Name</label>
                        <input type='text' name='firstname' value='$user->firstname'/>
                    </div>
                    <div class='formcontrol'>
                        <label for='lastname'>Last Name</label>
                        <input type='text' name='lastname' value='$user->lastname'/>
                    </div><br>
                    <div class='formcontrol'>
                        <label for='status'>Status: </label>
                        <div>$user->status</div>
                    </div>
                    <div class='formcontrol'>
                        <label for='password'>Password</label>
                        <div><a href='' class='change_pwd' id='$user->email'>Change my password</a></div>
                    </div>
                    <div class='formcontrol'>
                        <label for='position'>Position</label>
                        <select name='position'>
                        <option value='$user->position' selected='selected'>$user->position</option>
                        <option value='researcher'>Researcher</option>
                        <option value='postdoc'>Post-doc</option>
                        <option value='phdstudent'>PhD student</option>
                        <option value='master'>Master</option>
                        </select>
                    </div>
                    <div class='formcontrol'>
                        <label>Presentations: </label>
                        <div>$user->nbpres submission(s)</div>
                    </div>
                    <input type='hidden' name='username' value='$user->username'/>
                    <input type='hidden' name='user_modify' value='true' />
                    <div class='feedback' id='feedback_perso'></div>
                </form>
            </section>

        <section>
            <h2>Contact information</h2>
            <form method='post' action='' class='processform' id='profile_emailinfo_form'>
                <div class='submit_btns'>
                    <input type='submit' name='user_modify' value='Modify' class='processform'/>
                </div>
                <div class='formcontrol'>
                    <label for='email'>Email</label>
                    <input type='text' name='email' value='$user->email'/>
                </div><br>
                <div class='formcontrol'>
                    <label for='notification'>Email notifications</label>
                    <select name='notification' class='select_opt'>
                        <option value='$user->notification' selected>$notifStatus</option>
                        <option value='1'>Yes</option>
                        <option value='0'>No</option>
                    </select>
                </div><br>
                <div class='formcontrol'>
                    <label for='reminder'>Reminders</label>
                    <select name='reminder' class='select_opt'>
                        <option value='$user->reminder' selected>$reminderStatus</option>
                        <option value='1'>Yes</option>
                        <option value='0'>No</option>
                    </select>
                </div>
                <div class='formcontrol'>
                    <label for='reminder'>Assignment</label>
                    <select name='assign' class='select_opt'>
                        <option value='$user->assign' selected>$assignStatus</option>
                        <option value='1'>Yes</option>
                        <option value='0'>No</option>
                    </select>
                </div>
                <input type='hidden' name='user_modify' value='true' />
                <input type='hidden' name='username' value='$user->username'/>
                <div class='feedback' id='feedback_mail'></div>
            </form>
        </section>

        <section>
            <h2>My submissions</h2>
            $publication_list
        </section>
        
        <section>
            <h2>My assignments</h2>
            {$assignments}
        </section>
        <div class='plugins'></div>
    </div>
";

echo json_encode($result);
exit;
