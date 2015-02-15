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

// Page Header
if (!isset($_SESSION['logok']) || !$_SESSION['logok']) {
    $showlogin = "
        <span style='font-size: 16px; color: #FFFFFF;'><a rel='leanModal' id='modal_trigger_login' href='#modal' class='modal_trigger'>Log in</a> | <a rel='leanModal' id='modal_trigger_register' href='#modal' class='modal_trigger'>Sign up</a></span>";
} else {
    $showlogin = "
        <span style='font-size: 16px;' class='menu-section' data-url='profile'>My profile</span> |
        <span style='font-size: 16px;' class='menu-section' id='logout'>Log out</span>";
}

echo "
<div class='header_container'>
    <div id='title'>
        <span id='sitetitle'>$config->sitetitle</span>
        <div style='float: right; margin-right: 10px; margin-top: 20px; height: 20px;' id='welcome'>$showlogin</div>
    </div>
</div>
";
