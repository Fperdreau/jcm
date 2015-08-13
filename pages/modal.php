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

echo "

<div id='modal' class='modalContainer' style='display:none;'>
    <section class='popupBody' style='display:inline-block'>
        <div class='header_title'></div>

        <!-- Sign in section -->
        <div class='modal_section' id='user_login' data-title='Sign In' style='width: 400px;'>
            <form id='login_form'>
                <div class='formcontrol' style='width: 100%;'>
                    <label for='log_username'>Username</label>
                    <input type='text' id='log_username' name='username'>
                </div>
                <div class='formcontrol' style='width: 100%;'>
                    <label for='log_password'>Password</label>
                    <input type='password' id='log_password' name='password'>
                </div>
                <div class='action_btns'>
                    <div class='one_half'>
                        <input type='submit' id='submit' value='Log In' class='login'/>
                    </div>
                    <div class='one_half last' style='text-align: right;'>
                        <input type='button' class='gotoregister' value='Sign Up'>
                    </div>
                </div>
            </form>
            <div class='forgot_password'><a href='' class='modal_trigger_changepw'>I forgot my password</a></div>
        </div>

        <!-- Sign up section -->
        <div class='modal_section' id='user_register' data-title='Sign Up' style='width: 80%'>
            <form id='register_form'>
                <div class='formcontrol' style='width: 100%;'>
                    <label for='firstname'>First Name</label>
                    <input id='firstname' type='text' name='firstname'>
                </div>
                <div class='formcontrol' style='width: 100%;'>
                    <label for='lastname'>Last Name</label>
                    <input id='lastname' type='text' name='lastname'>
                </div>
                <div class='formcontrol' style='width: 100%;'>
                    <label for='username'>Username</label>
                    <input id='username' type='text' name='username'>
                </div>
                <div class='formcontrol' style='width: 100%;'>
                    <label for='password'>Password</label>
                    <input id='password' type='password' name='password'>
                </div>
                <div class='formcontrol' style='width: 100%;'>
                    <label for='conf_password'>Confirm password</label>
                    <input id='conf_password' type='password' name='conf_password'>
                </div>
                <div class='formcontrol' style='width: 100%;'>
                    <label for='email'>Email</label>
                    <input id='email' size='30' type='text' name='email'>
                </div>
                <div class='formcontrol' style='width: 100%;'>
                    <label for='position'>Position</label>
                        <select name='position' id='position'>
                            <option value='researcher'>Researcher</option>
                            <option value='post-doc'>Post-doc</option>
                            <option value='phdstudent'>PhD student</option>
                            <option value='master'>Master student</option>
                        </select>
                </div>
                <div class='action_btns'>
                    <div class='one_half'><input type='submit' class='back_btn' value='Back'> <i class='fa fa-angle-double-left'></i> </div>
                    <div class='one_half last'><input type='submit' class='register' id='submit' value='Sign up'></div>
                </div>
            </form>
        </div>

        <!-- Delete account section -->
        <div class='modal_section' id='user_delete' data-title='Delete Profile'>
            <div>Please, confirm your identity</div>
            <div class='formcontrol'>
                <label for='del_username'>Username</label>
                <input type='text' id='del_username' name='del_username' value=''/></br>
            </div>
            <div class='formcontrol'>
                <label for='del_password'>Password</label><input type='password' id='del_password' name='del_password' value=''/></br>
            </div>
            <div class='action_btns'>
                <div class='one_half last'><a href='' class='btn btn_red' id='confirmdeleteuser'>Delete</a></div>
            </div>
        </div>

        <!-- Change password section -->
        <div class='modal_section' id='user_changepw' data-title='Change Password'>
            <div>We will send an email to the provided address with further instructions in order to change your password.</div>
            <div class='formcontrol' style='width: 100%;'>
            <label for='ch_email'>Email</label><input type='text' id='ch_email' name='ch_email' value=''/></br>
            </div>
            <div class='action_btns'>
                <div class='one_half'><a href='' class='btn back_btn'><i class='fa fa-angle-double-left'></i> Back</a></div>
                <div class='one_half last'><a href='' class='btn btn_red' id='modal_change_pwd'>Change</a></div>
            </div>
        </div>

        <!-- Submission form section -->
        <div class='modal_section' id='submission_form' data-title='Presentation'></div>

        <!-- Delete submission (confirmation) section -->
        <div class='modal_section' id='pub_delete' data-title='Delete Presentation'>
            <div>Do you want to delete this presentation?</div>
            <div class='action_btns'>
                <div class='one_half'><a href='' class='pub_btn pub_back_btn'><i class='fa fa-angle-double-left'></i> Back</a></div>
                <div class='one_half last'><a href='' class='btn btn_red' id='confirm_pubdel'>Delete</a></div>
            </div>
        </div>

        <div class='feedback'></div>

    </section>
    <div class='modal_close'></div>

</div>";
