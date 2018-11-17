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

use includes\Template;
use includes\Users;

if (!empty($pageParameters['hash']) && !empty($pageParameters['email']) && !empty($pageParameters['result'])) {
    $hash = htmlspecialchars($pageParameters['hash']);
    $email = htmlspecialchars($pageParameters['email']);
    $result = htmlspecialchars($pageParameters['result']);
    $user = new Users();
    $valid = $user->validateAccount($hash, $email, $result);
    $msg = ($valid['status']) ? "<div class='sys_msg success'>".$valid['msg']."</div>":"<div class='sys_msg warning'>".$valid['msg']."</div>";
    $result = Template::section(array('title'=>"Account Activation", "body"=>$msg));
} else {
    $result = Template::section(array('title'=>"Account Activation", "body"=>"<div class='sys_msg warning'>Incorrect email or hash id.</div>"));
}
echo $result;
