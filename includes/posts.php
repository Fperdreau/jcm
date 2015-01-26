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

class posts {
    public $content = "";
    public $date = "";
    public $username = "";
    public $day = "";
    public $time = "";

    public function __construct() {

    }

    public function add_post($new_post,$user_fullname) {
        require_once($_SESSION['path_to_app'].'/includes/db_connect.php');
        require($_SESSION['path_to_app']."/admin/conf/config.php");
        $db_set = new DB_set();
        $bdd = $db_set->bdd_connect();
        $this->content = mysqli_real_escape_string($bdd,$new_post);
        $this->date = date('Y-m-d H:i:s');
        $this->username = $user_fullname;
        $db_set -> addcontent($post_table,"date,post,username","'$this->date','$this->content','$this->username'");
    }

    public function getlastnews() {
        require_once($_SESSION['path_to_app'].'/includes/db_connect.php');
        require($_SESSION['path_to_app']."/admin/conf/config.php");
		
        $db_set = new DB_set();
        $sql = "select date,post,username from $post_table where date = (select max(date) from $post_table)";
        $req = $db_set->send_query($sql);
        $lastnews = mysqli_fetch_array($req);		
		if ($lastnews['post'] != "") {
	        $this->content = htmlspecialchars_decode($lastnews['post']);
	        $this->date = $lastnews['date'];
	        $this->username = $lastnews['username'];
	        $this->day = date('Y-m-d',strtotime($this->date));
	        $this->time = date('H:i',strtotime($this->date));	
			return true;	
		} else {
			return false;
		}
    }

} 