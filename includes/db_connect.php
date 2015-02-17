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

class DB_set {

    public $bdd = NULL;
    public $password = "";
    public $username = "";
    public $host = "";
    public $dbname = "";
    public $dbprefix = "";
    public $apptables = "";

    function __construct() {
        require($_SESSION['path_to_app'].'config/config.php');
        $this->username = $username;
        $this->host = $host;
        $this->dbname = $dbname;
        $this->password = $passw;
        $this->dbprefix = $db_prefix;
        $this->apptables = self::getapptables();
    }

    // Connects to DB and throws exceptions
	public function bdd_connect() {
		$bdd = mysqli_connect($this->host,$this->username,$this->password) or exit(json_encode("<p id='warning'> Failed to connect to the database</p>").mysqli_error($bdd));
		mysqli_select_db($bdd,"$this->dbname") or exit(json_encode("<p id='warning'>Database '$this->dbname' cannot be selected<br/>".mysqli_error($bdd)."</p>"));
		$this->bdd = $bdd;
        return $this->bdd;
	}

    // Get list of tables associated to the application
    public function getapptables() {
        $sql = "SHOW TABLES FROM ".$this->dbname." LIKE '".$this->dbprefix."%'";
        $req = self::send_query($sql);
        $tablelist = array();
        while ($row = mysqli_fetch_array($req)) {
            $tablelist[] = $row[0];
        }
        return $tablelist;
    }

	public function tableExists($table) {
		$sql = 'SHOW COLUMNS FROM '.$table;
		if (self :: send_query($sql,true)) {
			$result = 1;
		} else {
			$result = 0;
		}
		return $result;
	}

    public function send_query($sql,$silent=false) {
        $bdd = self::bdd_connect();
        $req = mysqli_query($bdd,$sql);
        if (false === $req) {
            if ($silent == false) {
                echo json_encode('SQL command: '.$sql.' <br>SQL message: <br>'.mysqli_error($bdd).'<br>');
            }
            return false;
        } else {
            self::bdd_close();
            return $req;
        }
    }

    public function escape_query($query) {
        self::bdd_connect();
        return mysqli_real_escape_string($this->bdd,$query);
        self::bdd_close();
    }

	public function createtable($table_name,$cols_name,$opt = false) {
		if (self::tableExists($table_name) && $opt) {
			// Delete table if it exists
            self::deletetable($table_name);
		}

		if (self::tableExists($table_name) == false) {
			// Create table if it does not exist already
			$sql = 'CREATE TABLE '.$table_name.' ('.$cols_name.')';
            if (self::send_query($sql)) {
            	return true;
            } else {
            	return false;
            }
		}
	}

	public function deletetable($table_name) {
		$sql = 'DROP TABLE '.$table_name;
        return self::send_query($sql);
	}

    public function addcolumn($table_name,$col_name,$type,$after=null) {
        // Check if column exists
        $sql = "SELECT $col_name FROM $table_name";
        $colexist = self::send_query($sql,true);
        if (!$colexist) {
            $sql = "ALTER TABLE $table_name ADD COLUMN $col_name $type";
            if (null!=$after) {
                $sql .= " AFTER $after";
            }

            if (self::send_query($sql)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

	public function addcontent($table_name,$cols_name,$values) {
		$sql = 'INSERT INTO '.$table_name.'('.$cols_name.') VALUES('.$values.')';
        return self::send_query($sql);
    }

    public function addpostcontent($table_name, $data) {
        $bdd = self::bdd_connect();
        $sql = "INSERT INTO ".$table_name;
        $fis = array();
        $vas = array();
        foreach($data as $field=>$val) {
            if (!in_array($field,array('submit'))) {
                $fis[] = "`$field`";
                $vas[] = "'".mysqli_real_escape_string($this->$bdd,$val)."'";
            }
        }
        $sql .= " (".implode(", ", $fis).") VALUES (".implode(", ", $vas).")";
        return self::send_query($sql);
    }

    public function updatepostcontent($table_name, $data, $refcol,$id) {
        $bdd = self::bdd_connect();
        foreach($data as $field=>$val) {
            if (!in_array($field,array('submit'))) {
                $vas = "'".mysqli_real_escape_string($bdd,$val)."'";
                self::updatecontent($table_name,$field,$vas,$refcol,$id);
            }
        }
    }

	public function deletecontent($table_name,$refcol,$id) {
		$nref = count($refcol);
		$cpt = 0;
        $cond = array();
		foreach ($refcol as $ref) {
			$cond[] = "$ref=".$id[$cpt];
			$cpt++;
		}

		if ($nref > 1) {
			$cond = implode(' AND ',$cond);
		} else {
			$cond = implode('',$cond);
		}

		$sql = "DELETE FROM $table_name WHERE ".$cond;
        self::send_query($sql);
        return true;
    }

	public function updatecontent($table_name,$cols_name,$value,$refcol,$id) {
		$nref = count($refcol);
		$cpt = 0;
		foreach ($refcol as $ref) {
			$cond[] = "$ref=".$id[$cpt];
			$cpt++;
		}

		if ($nref > 1) {
			$cond = implode(' AND ',$cond);
		} else {
			$cond = implode('',$cond);
		}
        if (is_array($cols_name)) {
            $set = array();
            $cpt = 0;
            foreach ($cols_name as $cols) {
                $val = $value[$cpt];
                $set[]="$cols='$val'";
                $cpt++;
            }
            $set = implode(',',$set);
        } else {
            $set = "$cols_name=$value";
        }

		$sql = "UPDATE $table_name SET $set WHERE ".$cond;
        if (self::send_query($sql)) {
            return true;
        } else {
            return false;
        }
    }

    public function makeorupdate($tablename,$tabledata,$overwrite=false) {
        $columns = array();
        $defcolumns = array();
        foreach ($tabledata as $column=>$data) {
            $defcolumns[] = $column;
            if ($column == "primary") {
                $columns[] = "PRIMARY KEY($data)";
            } else {
                $datatype = $data[0];
                $defaut = $data[1];
                $col = "`$column` $datatype";
                if ($defaut != false) {
                    $col .= " DEFAULT '$defaut'";
                }
                $columns[] = $col;
            }
        }
        $columndata = implode(',',$columns);

        if ($overwrite) {
            // If overwrite, then we simply create a new table and drop the previous one
            self::createtable($tablename,$columndata,$overwrite);
        } else {
            // Get columns names
            $sql = "SHOW COLUMNS FROM $tablename";
            $req = self::send_query($sql);
            $keys = array();
            while ($row = mysqli_fetch_array($req)) {
                $keys[] = $row['Field'];
            }

            // Add new unexistant columns or update previous version
            $prevcolumn = "id";
            foreach ($tabledata as $column=>$data) {
                if ($column !== "primary" && $column != "id") {
                    $datatype = $data[0];
                    $defaut = $data[1];
                    if (!in_array($column,$keys)) {
                        // If the column does not exist already, then we simply add it to the table
                        self::addcolumn($tablename,$column,$datatype,$prevcolumn);
                    } else {
                        // Check if the column's data type is consistent with the new version
                        $sql = "ALTER TABLE $tablename MODIFY $column $datatype";
                        self::send_query($sql);
                    }
                    $prevcolumn = $column;
                }
            }

            // Remove deprecated columns
            foreach ($keys as $key) {
                if (!in_array($key,$defcolumns)) {
                    $sql = "ALTER TABLE $tablename DROP COLUMN $key";
                    self::send_query($sql);
                }
            }
        }
        return true;
    }

    public function getprimarykeys($tablename) {
        $sql = "SHOW KEYS FROM $tablename WHERE Key_name = 'PRIMARY'";
        $req = self::send_query($sql);
        $keys = array();
        while ($row = mysqli_fetch_assoc($req)) {
            $keys[] = $row['Column_name'];
        }
        return $keys;
    }

	public function getinfo($table_name,$cols_name,$refcol = NULL,$id = NULL, $op = NULL) {
		$nref = count($refcol);
        if ($op == NULL && is_array($refcol)) {
            $op = array();
            for ($i=0;$i<$nref;$i++) {
                $op[] = "=";
            }
        } elseif ($op == NULL) {
            $op = "=";
        }

		if ($refcol == NULL) { // Look for column content
			$cond = "";
		} elseif (!is_array($refcol)) {
			$cond = "WHERE $refcol"."$op"."$id";
		} else { // Look for a specific value
			$cpt = 0;
			foreach ($refcol as $ref) {
				$cond[] = "$ref"."$op[$cpt]"."$id[$cpt]";
				$cpt++;
			}

			if ($nref > 1) {
				$cond = implode(' AND ',$cond);
			} else {
				$cond = implode('',$cond);
			}
			$cond = "WHERE ".$cond;
		}

		$sql = "SELECT $cols_name FROM $table_name ".$cond;
        $req = self::send_query($sql);
		$infos = array();
		$nrow = 0;
		while ($row = mysqli_fetch_array($req)) {
			$nrow++;
			$infos[] = $row[$cols_name];
		}

		if ($refcol !=NULL && is_array($infos) && $nrow == 1) {
			$infos = $infos[0];
		}
		return $infos;
	}

	public function bdd_close() {
		mysqli_close($this->bdd);
		$bdd = null;
        return $bdd;
	}
}
?>
