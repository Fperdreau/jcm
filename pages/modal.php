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

/**
 * Modal (popup) window
 */

echo "

<div id='modal' class='modalContainer' style='display:none;'>
    <section class='popupBody' style='display:inline-block'>
        <div class='popupHeader'></div>

        <!-- Sign in section -->
        <div class='modal_section' id='user_login' data-title='Sign In'>
            <form id='login_form' method='post' action='php/form.php'>
                <input type='hidden' name='login' value='true'/>
                <div class='form-group' style='width: 100%;'>
                    <input type='text' name='username' required autocomplete='on'>
                    <label for='username'>Username</label>
                </div>
                <div class='form-group' style='width: 100%;'>
                    <input type='password' name='password' required>
                    <label for='password'>Password</label>
                </div>
                <div class='action_btns'>
                    <div class='one_half'>
                        <input type='submit' id='login_form' value='Log In' class='login'/>
                    </div>
                    <div class='one_half last' style='text-align: right;'>
                        <input type='button' class='gotoregister' value='Sign Up'>
                    </div>
                </div>
            </form>
            <div class='forgot_password'><a href='' class='modal_trigger_changepw'>I forgot my password</a></div>
        </div>

        <!-- Sign up section -->
        <div class='modal_section' id='user_register' data-title='Sign Up'>
            <form id='register_form' method='post' action='php/form.php'>
                <input type='hidden' name='register' value='true'>
                <input type='hidden' name='status' value='member'>
                <div class='form-group' style='width: 100%;'>
                    <input type='text' name='firstname' required autocomplete='on'>
                    <label for='firstname'>First Name</label>
                </div>
                <div class='form-group' style='width: 100%;'>
                    <input type='text' name='lastname' required autocomplete='on'>
                    <label for='lastname'>Last Name</label>
                </div>
                <div class='form-group' style='width: 100%;'>
                    <input type='text' name='username' required autocomplete='on'>
                    <label for='username'>Username</label>
                </div>
                <div class='form-group' style='width: 100%;'>
                    <input type='password' name='password' class='passwordChecker' required>
                    <label for='password'>Password</label>
                </div>
                <div class='form-group' style='width: 100%;'>
                    <input type='password' name='conf_password' required>
                    <label for='conf_password'>Confirm password</label>
                </div>
                <div class='form-group' style='width: 100%;'>
                    <input type='email' name='email' required autocomplete='on'>
                    <label for='email'>Email</label>
                </div>
                <div class='form-group' style='width: 100%;'>
                    <select name='position' id='position' required>
                        <option value='' selected disabled></option>
                        <option value='researcher'>Researcher</option>
                        <option value='post-doc'>Post-doc</option>
                        <option value='phdstudent'>PhD student</option>
                        <option value='master'>Master student</option>
                    </select>   
                    <label class='dropdown'>Position</label>    
                </div>
                <div class='action_btns'>
                    <div class='one_half'><input type='submit' class='back_btn' value='Back'> <i class='fa fa-angle-double-left'></i> </div>
                    <div class='one_half last'><input type='submit' class='register' value='Sign up'></div>
                </div>
            </form>
        </div>

        <!-- Delete account section -->
        <div class='modal_section' id='user_delete' data-title='Delete Profile'>
            <div>Please, confirm your identity.</div>
            <form id='confirmdeleteuser' method='post' action='php/form.php'>
                <div><input type='hidden' name='delete_user' value='true'></div>
                <div class='form-group'>
                    <input type='text' id='del_username' name='username' value='' required autocomplete='off'/>
                    <label for='del_username'>Username</label>
                </div>
                <div class='form-group'>
                    <input type='password' id='del_password' name='password' value='' required autocomplete='off'/>
                    <label for='del_password'>Password</label>
                </div>
                <div class='action_btns'>
                    <input type='submit' class='confirmdeleteuser' value='Delete my account'>
                </div>
            </form>
        </div>

        <!-- Change password section -->
        <div class='modal_section' id='user_changepw' data-title='Change Password'>
            <div class='page_description'>We will send an email to the provided address with further instructions in order to change your password.</div>
            <form id='modal_change_pwd' method='post' action='php/form.php'>
                <input type='hidden' name='change_pw' value='true'>
                <div class='form-group'>
                    <input type='email' name='email' value='' required/>
                    <label for='email'>Email</label>
                </div>
                <div class='action_btns'>
                    <div class='one_half'><a href='' class='btn back_btn'><i class='fa fa-angle-double-left'></i> Back</a></div>
                    <div class='one_half last'><input type='submit' class='processform' value='Send'></div>
                </div>
            </form>
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
        <div class='modal_close'></div>

    </section>

</div>";