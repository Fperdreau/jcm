<?php
/*
Copyright © 2014, F. Perdreau, Radboud University Nijmegen
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

<div id='modal' class='popupContainer' style='display:none;'>
 <header class='popupHeader'>
 <span class='header_title'>Login</span>
 <span class='modal_close'><i class='fa fa-times'></i></span>
 </header>

    <section class='popupBody'>

        <div class='user_login'>
            <form>
                <label for='log_username'>Username</label><input type='text' id='log_username' name='username'/>
                <label for='log_password'>Password</label><input type='password' id='log_password' name='password'/>
                <div class='action_btns'>
                    <div class='one_half'>
                        <input type='submit' id='submit' value='Log In' class='login'/>
                    </div>
                </div>
            </form>
            <div class='forgot_password'><a href='' class='modal_trigger_changepw'>I forgot my password</a></div>
        </div>

        <div class='user_register'>
            <label for='firstname'>First Name</label><input id='firstname' type='text' name='firstname'/>
            <label for='lastname'>Last Name</label><input id='lastname' type='text' name='lastname'/>
            <label for='username'>Username</label><input id='username' type='text' name='username'/>
            <label for='password'>Password</label><input id='password' type='password' name='password'/>
            <label for='conf_password'>Confirm password</label><input id='conf_password' type='password' name='conf_password'/>
            <label for='email'>Email</label><input id='email' size='30' type='text' name='email'/>
            <label for='position'>Position</label>
                <select name='position' id='position'>
                    <option value='researcher'>Researcher</option>
                    <option value='post-doc'>Post-doc</option>
                    <option value='phdstudent'>PhD student</option>
                    <option value='master'>Master student</option>
                </select>
            <div class='action_btns'>
                <div class='one_half'><a href='' class='btn back_btn'><i class='fa fa-angle-double-left'></i> Back</a></div>
                <div class='one_half last'><a href='' class='btn btn_red' id='register'>Sign up</a></div>
            </div>
        </div>

        <div class='user_delete'>
            <label for='del_username'>Username</label><input type='text' id='del_username' name='del_username' value=''/></br>
            <label for='del_password'>Password</label><input type='password' id='del_password' name='del_password' value=''/></br>
            <div class='action_btns'>
                <div class='one_half last'><a href='' class='btn btn_red' id='confirmdeleteuser'>Delete</a></div>
            </div>
        </div>

        <div class='user_changepw'>
            <label for='ch_email'>Email</label><input type='text' id='ch_email' name='ch_email' value=''/></br>
            <div class='action_btns'>
                <div class='one_half'><a href='' class='btn back_btn'><i class='fa fa-angle-double-left'></i> Back</a></div>
                <div class='one_half last'><a href='' class='btn btn_red' id='modal_change_pwd'>Change</a></div>
            </div>
        </div>

        <div class='feedback'></div>
    </section>
</div>

<div id='pub_modal' class='pub_popupContainer' style='display:none;'>
 <header class='popupHeader'>
 <span class='header_title'>Presentation</span>
 <span class='modal_close'><i class='fa fa-times'></i></span>
 </header>

    <section class='popupBody'>

        <div class='publication_form'>
            <div></div>
        </div>

        <div class='pub_delete'>
            <div>Do you want to delete this presentation?</div>
            <div class='action_btns'>
                <div class='one_half'><a href='' class='pub_btn pub_back_btn'><i class='fa fa-angle-double-left'></i> Back</a></div>
                <div class='one_half last'><a href='' class='btn btn_red' id='confirm_pubdel'>Delete</a></div>
            </div>
            <div class='feedback'></div>
        </div>

		<div class='pub_modify'></div>

    </section>
</div>


    ";


