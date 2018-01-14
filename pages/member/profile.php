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

use includes\Users;
use includes\Presentation;

// Declare classes
$user = new Users($_SESSION['username']);
$Presentation = new Presentation();
$publication_list = $user->getPublicationList();
$assignments = $user->getAssignments();
$bookmarks = $user->getBookmarks();
$notifStatus = ($user->notification == 1) ? "Yes":"No";
$reminderStatus = ($user->reminder == 1) ? "Yes":"No";
$assignStatus = ($user->assign == 1) ? "Yes":"No";
$result = "

    <div class='section_container'>
            <section>
                <h2>Personal information</h2>
                <div class='section_content'>
                <form method='post' action='php/form.php' class='form' id='profile_persoinfo_form'>
                    <div class='submit_btns'>
                        <input type='submit' name='user_modify' value='Modify' class='processform'/>
                    </div>
                    <div class='form-group'>
                        <input type='text' name='firstname' value='$user->firstname'/>
                        <label for='firstname'>First Name</label>
                    </div>
                    <div class='form-group'>
                        <input type='text' name='lastname' value='$user->lastname'/>
                        <label for='lastname'>Last Name</label>
                    </div><br>
                    <div class='form-group'>
                        <div>$user->status</div>
                        <label for='status'>Status: </label>
                    </div>
                    <div class='form-group'>
                        <div><a href='' class='change_pwd' id='$user->email'>Change my password</a></div>
                        <label for='password'>Password</label>
                    </div>
                    <div class='form-group'>
                        <select name='position'>
                            <option value='$user->position' selected='selected'>$user->position</option>
                            <option value='researcher'>Researcher</option>
                            <option value='postdoc'>Post-doc</option>
                            <option value='phdstudent'>PhD student</option>
                            <option value='master'>Master</option>
                        </select>
                        <label for='position'>Position</label>
                    </div>
                    <div class='form-group'>
                        <div>$user->nbpres submission(s)</div>
                        <label>Presentations: </label>
                    </div>
                    <input type='hidden' name='username' value='$user->username'/>
                    <input type='hidden' name='user_modify' value='true' />
                    <div class='feedback' id='feedback_perso'></div>
                </form>
                </div>
            </section>

        <section>
            <h2>Contact information</h2>
            <div class='section_content'>
            <form method='post' action='php/form.php' class='processform' id='profile_emailinfo_form'>
                <div class='submit_btns'>
                    <input type='submit' name='user_modify' value='Modify' class='processform'/>
                </div>
                <div class='form-group'>
                    <input type='text' name='email' value='$user->email'/>
                    <label for='email'>Email</label>
                </div><br>
                <div class='form-group'>
                    <select name='notification' class='select_opt'>
                        <option value='$user->notification' selected>$notifStatus</option>
                        <option value='1'>Yes</option>
                        <option value='0'>No</option>
                    </select>
                    <label for='notification'>I want to receive Email notifications</label>
                </div>
                <div class='form-group'>
                    <select name='reminder' class='select_opt'>
                        <option value='$user->reminder' selected>$reminderStatus</option>
                        <option value='1'>Yes</option>
                        <option value='0'>No</option>
                    </select>
                    <label for='reminder'>I want to receive reminders</label>
                </div>
                <div class='form-group'>
                    <select name='assign' class='select_opt'>
                        <option value='$user->assign' selected>$assignStatus</option>
                        <option value='1'>Yes</option>
                        <option value='0'>No</option>
                    </select>
                    <label for='reminder'>I want to be assigned as speaker</label>
                </div>
                <input type='hidden' name='user_modify' value='true' />
                <input type='hidden' name='username' value='$user->username'/>
                <div class='feedback' id='feedback_mail'></div>
            </form>
            </div>
        </section>
        
        <section>
            <h2>My availability</h2>
            <div class='section_content'>
            <p class='description'>Select the dates on which you are not available.</p>
            <div class='sys_msg warning'>Selecting a date on which you have been planned as 
            speaker will automatically cancel this presentation!</div>
            <div class='datepicker' id='availabilityCalendar'></div>
            <div class='calendar_legend'>
                <div class='not_available'>I am not available</div>
                <div class='jc_day'>JC day</div>
                <div class='assigned'>I am a speaker</div>
            </div>
            </div>
        </section>

        <section>
            <h2>My submissions</h2>
            <div class='section_content'>$publication_list</div>
        </section>
        
        <section>
            <h2>My assignments</h2>
            <div class='section_content'>{$assignments}</div>
        </section>
        
        <section>
            <h2>My bookmarks</h2>
            <div class='section_content'>{$bookmarks}</div>
        </section>
        
        <div class='plugins'></div>
        
        <section>
            <h2>Delete my account</h2>
            <div class='section_content' id='delete_account_container'>
                <p>You can delete your account by clicking the button below.</p>
                <div class='operation_button user_delete' data-username={$user->username}>Delete my account</div>
                <div class='sys_msg warning'>Deleted accounts cannot be recovered. All your account information will 
                be lost.</div>
            </div>   
        </section>
        
    </div>
    <section>
        <div class='section_content'>
        </div>
    </section>

";

echo $result;
