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
check_login();

// Declare classes
$user = new User($db,$_SESSION['username']);
$Presentation = new Presentation($db);
$publication_list = $user->getpublicationlist(null);

$notif_yes_status = 'unchecked';
$notif_no_status = 'unchecked';
if ($user->notification == 0) {
    $notif_no_status = 'checked';
} else {
    $notif_yes_status = 'checked';
}

$rem_yes_status = 'unchecked';
$rem_no_status = 'unchecked';
if ($user->reminder == 0) {
    $rem_no_status = 'checked';
} else {
    $rem_yes_status = 'checked';
}

$result = "
<div id='content'>
    <span id='pagename'>Hello $user->fullname!</span>
    <div class='operation_button'><a rel='leanModal' href='#modal' class='modal_trigger' id='modal_trigger_delete'>Delete my account</a></div>

    <div class='section_page'>
        <div class='half_section section_left'>
            <div class='section_header'>Personal information</div>
            <div class='section_content'>
                <form method='post' action='' class='form' id='profile_persoinfo_form'>
                    <div class='submit_btns'>
                        <input type='submit' name='user_modify' value='Modify' class='profile_persoinfo_form' id='submit'/>
                    </div>
                    <input type='hidden' name='username' value='$user->username'/>
                    <div class='formcontrol' style='width: 35%;'>
                        <label for='firstname'>First Name</label>
                        <input type='text' name='firstname' value='$user->firstname'/>
                    </div>
                    <div class='formcontrol' style='width: 35%;'>
                        <label for='lastname'>Last Name</label>
                        <input type='text' name='lastname' value='$user->lastname'/>
                    </div><br>
                    <div class='formcontrol' style='width: 35%;'>
                        <label for='status'>Status: </label>
                        <div>$user->status</div>
                    </div>
                    <div class='formcontrol' style='width: 35%;'>
                        <label for='password'>Password</label>
                        <div><a href='' class='change_pwd' id='$user->email'>Change my password</a></div>
                    </div>
                    <div class='formcontrol' style='width: 35%;'>
                        <label for='position'>Position</label>
                        <select name='position'>
                        <option value='$user->position' selected='selected'>$user->position</option>
                        <option value='researcher'>Researcher</option>
                        <option value='postdoc'>Post-doc</option>
                        <option value='phdstudent'>PhD student</option>
                        <option value='master'>Master</option>
                        </select>
                    </div>
                    <div class='formcontrol' style='width: 35%;'>
                        <label>Presentations: </label>
                        <div>$user->nbpres submission(s)</div>
                    </div>
                    <input type='hidden' name='user_modify' value='true' />
                    <div class='feedback' id='feedback_perso'></div>
                </form>
            </div>
        </div>

        <div class='half_section section_right'>
            <div class='section_header'>Contact information</div>
            <div class='section_content'>
                <form method='post' action='' class='form' id='profile_emailinfo_form'>
                    <div class='submit_btns'>
                        <input type='submit' name='user_modify' value='Modify' class='profile_emailinfo_form' id='submit'/>
                    </div>
                    <div class='formcontrol' style='width: 90%;'>
                        <label for='email'>Email</label>
                        <input size='40' type='text' name='email' value='$user->email'/>
                    </div><br>
                    <div class='formcontrol' style='width: 90%;'>
                        <label for='notification'>I wish to receive email notifications</label>
                        <div>
                        <input type='radio' name='notification' value='1' $notif_yes_status>Yes</input>
                        <input type='radio' name='notification' value='0' $notif_no_status>No</input>
                        </div>
                    </div><br>
                    <div class='formcontrol' style='width: 90%;'>
                        <label for='reminder'>I wish to receive reminders</label>
                        <div>
                        <input type='radio' name='reminder' value='1' $rem_yes_status>Yes</input>
                        <input type='radio' name='reminder' value='0' $rem_no_status>No</input>
                        </div>
                    </div>
                    <input type='hidden' name='user_modify' value='true' />
                    <input type='hidden' name='username' value='$user->username'/>
                    <div class='feedback' id='feedback_mail'></div>
                </form>
            </div>
        </div>
    </div>

    <div class='section_page'>
        <div class='half_section section_left'>
            <div class='section_header'>My submissions</div>
            <div class='section_content'>$publication_list</div>
        </div>
        <div class='half_section section_right'>
            <div class='plugins'></div>
        </div>
    </div>

</div>
";

echo json_encode($result);
