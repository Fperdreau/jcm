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

/**
 * Class DbSet
 *
 * Handle communication routines with the database (writing, reading, updating)
 * and environmental information (list of tables associated to the application,...).
 */

class DbSet {

    /**
     * Link to the database
     *
     * @var null
     */
    public $bdd = NULL;

    /**
     * Default settings
     * @var array
     */
    static $default = array(
        "version"=>false,
        "dbname"=>"test",
        "host"=>"localhost",
        "dbprefix"=>"jcm",
        "username"=>"root",
        "passw"=>""
    );

    /**
     * @var
     */
    public $config;

    /**
     * Password
     *
     * @var string
     */
    public $passw = "";

    /**
     * Mysql username
     *
     * @var string
     */
    public $username = "root";

    /**
     * Hostname
     *
     * @var string
     */
    public $host = "localhost";

    /**
     * Database name
     *
     * @var string
     */
    public $dbname = "test";

    /**
     * Tables prefix
     *
     * @var string
     */
    public $dbprefix = "jcm";

    /**
     * List of tables associated to the application
     *
     * @var array|string
     *
     */
    public $apptables;

    /**
     *
     * @var array
     */
    public $tablesname;

    /**
     * Default charset
     * @var string
     */
    public $charset = 'utf8';

    /**
     * Constructor
     */
    function __construct() {
        $this->config = $this->get_config();
        $this->tablesname = array(
            "Presentation" => $this->dbprefix."_presentations",
            "AppConfig" => $this->dbprefix."_config",
            "User" => $this->dbprefix."_users",
            "Session" => $this->dbprefix."_session",
            "Posts" => $this->dbprefix."_post",
            "Media" => $this->dbprefix."_media",
            "Plugins" => $this->dbprefix."_plugins",
            "Crons" => $this->dbprefix."_crons"
        );
    }

    /**
     * Get db config
     * @return bool|array
     */
    public function get_config() {
        $version_file = PATH_TO_CONFIG."config.php";
        if (is_file($version_file)) {
            require $version_file;
            if (!isset($config)) {
                $config['version'] = isset($version) ? $version : "unknown";
                $config['host'] = $host;
                $config['username'] = $username;
                $config['passw'] = $passw;
                $config['dbname'] = $dbname;
                $config['dbprefix'] = str_replace('_','',$db_prefix);
            }
        } else {
            $config = self::$default;
        }
        foreach ($config as $key=>$value) {
            $this->$key = $value;
        }
        return $config;
    }

    /**
     * Connects to DB and throws exceptions
     * @return mysqli|null
     */
    function bdd_connect() {
        $this->bdd = mysqli_connect($this->host,$this->username,$this->passw);
        if (!$this->bdd) {
            $result['status'] = false;
            die(json_encode("<p id='warning'> Failed to connect to the database<br>" . mysqli_error($this->bdd) . "</p>"));
        }

        if (!mysqli_select_db($this->bdd,"$this->dbname")) {
            die(json_encode("<p id='warning'>Database '$this->dbname' cannot be selected<br/>".mysqli_error($this->bdd)."</p>"));
        }

        if (!mysqli_query($this->bdd, "SET NAMES '$this->charset'")) {
            die(json_encode("<p id='warning'>Could not set database charset to '$this->charset'<br/>".mysqli_error($this->bdd)."</p>"));
        }

        return $this->bdd;
	}

    /**
     * Test credentials and throws exceptions
     * @return array
     */
    function testdb($config) {
        $link = @mysqli_connect($config['host'],$config['username'],$config['passw']);
        if (!$link) {
            $result['status'] = false;
            $result['msg'] = "<p id='warning'>Failed to connect to the database<br></p>";
            return $result;
        }

        if (!@mysqli_select_db($link,$config['dbname'])) {
            $result['status'] = false;
            $result['msg'] = "<p id='warning'>Database '".$config['dbname']."' cannot be selected<br/></p>";
            return $result;
        }
        $result['status'] = true;
        $result['msg'] = "<p id='success'>Connected</p>";
        return $result;
    }

    /**
     * @return mixed
     */
    function getCharSet() {
        return $this->bdd->get_charset();
    }

    /**
     * Get list of tables associated to the application
     * @return array
     */
    public function getapptables() {
        $sql = "SHOW TABLES FROM ".$this->dbname." LIKE '".$this->dbprefix."%'";
        $req = self::send_query($sql);
        $table_list = array();
        while ($row = mysqli_fetch_array($req)) {
            $table_list[] = $row[0];
        }
        $this->apptables = $table_list;
        return $this->apptables;
    }

    /**
     * Get columns names
     * @param $tablename
     * @return array
     */
    public function getcolumns($tablename) {
        $sql = "SHOW COLUMNS FROM $tablename";
        $req = self::send_query($sql);
        $keys = array();
        while ($row = mysqli_fetch_array($req)) {
            $keys[] = $row['Field'];
        }
        return $keys;
    }

    /**
     * Check if the table exists
     * @param $table
     * @return int
     */
    public function tableExists($table) {
		$sql = 'SHOW COLUMNS FROM '.$table;
		if (self :: send_query($sql,true)) {
			$result = 1;
		} else {
			$result = 0;
		}
		return $result;
	}

    /**
     * Send query to the database
     * @param $sql
     * @param bool $silent
     * @return bool|mysqli_result
     */
    public function send_query($sql,$silent=false) {
        self::bdd_connect();
        if (!mysqli_set_charset($this->bdd,"utf8")) {
            print('We could not load UTF8 charset');
        }
        $req = mysqli_query($this->bdd,$sql);
        if (false === $req) {
            if ($silent == false) {
                echo json_encode('SQL command: '.$sql.' <br>SQL message: <br>'.mysqli_error($this->bdd).'<br>');
            }
            return false;
        } else {
            self::bdd_close();
            return $req;
        }
    }

    /**
     * Escape query before committing to the db
     * @param $query
     * @return string
     */
    public function escape_query($query) {
        self::bdd_connect();
        return mysqli_real_escape_string($this->bdd,$query);
    }

    /**
     * Create a table
     * @param $table_name
     * @param $cols_name
     * @param bool $opt
     * @return bool
     */
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
		} else {
            return false;
        }
	}

    public function modifyCharSet() {

    }

    /**
     * Drop a table
     * @param $table_name
     * @return bool|mysqli_result
     */
    public function deletetable($table_name) {
		$sql = 'DROP TABLE '.$table_name;
        return self::send_query($sql);
	}

    /**
     * Empty a table
     * @param $table_name
     * @return bool|mysqli_result
     */
    public function clearTable($table_name) {
        $sql = "TRUNCATE TABLE $table_name";
        return self::send_query($sql);
    }

    /**
     * Add a column to the table
     * @param $table_name
     * @param $col_name
     * @param $type
     * @param null $after
     * @return bool
     */
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

    /**
     * Add content (row) to a table
     * @param $table_name
     * @param $content
     * @return bool|mysqli_result
     * @internal param $cols_name
     * @internal param $values
     */
    public function addcontent($table_name,$content) {
        $cols_name = array();
        $values = array();
        foreach ($content as $col=>$value) {
            $cols_name[] = $col;
            $values[] = "'".$this->escape_query($value)."'";
        }
		$sql = 'INSERT INTO '.$table_name.'('.implode(',',$cols_name).') VALUES('.implode(',',$values).')';
        return self::send_query($sql);
    }

    /**
     * Add post request content to a table
     * @param $table_name
     * @param $data
     * @return bool|mysqli_result
     */
    public function addpostcontent($table_name, $data) {
        $sql = "INSERT INTO ".$table_name;
        $fis = array();
        $vas = array();
        foreach($data as $field=>$val) {
            if (!in_array($field,array('submit'))) {
                $fis[] = "`$field`";
                $vas[] = "'".self::escape_query($val)."'";
            }
        }
        $sql .= " (".implode(", ", $fis).") VALUES (".implode(", ", $vas).")";
        return self::send_query($sql);
    }

    /**
     * Update a row with posted content
     * @param $table_name
     * @param $data
     * @param $refcol
     * @param $id
     */
    public function updatepostcontent($table_name, $data, $refcol,$id) {
        foreach($data as $field=>$val) {
            if (!in_array($field,array('submit'))) {
                $vas = "'".self::escape_query($val)."'";
                self::updatecontent($table_name,array($field=>$vas),array($refcol=>$id));
            }
        }
    }

    /**
     * Delete content from the table
     * @param $table_name
     * @param $refcol
     * @param $id
     * @return bool
     */
    public function deletecontent($table_name,$refcol,$id) {
        $refcol = is_array($refcol) ? $refcol:array($refcol);
        $id = is_array($id) ? $id:array($id);

        $nref = count($refcol);
		$cpt = 0;
        $cond = array();
		foreach ($refcol as $ref) {
			$cond[] = "$ref='".$id[$cpt]."'";
			$cpt++;
		}

        $cond = $nref > 1 ? implode(' AND ', $cond) : implode('', $cond);

		$sql = "DELETE FROM $table_name WHERE ".$cond;
        self::send_query($sql);
        return true;
    }

    /**
     * Update a row
     * @param $table_name
     * @param $content
     * @param array $reference
     * @return bool
     * @internal param $cols_name
     * @internal param $value
     * @internal param $refcol
     * @internal param $id
     */
    public function updatecontent($table_name,$content,$reference=array()) {

        # Parse conditions
        $nb_ref = count($reference);
        $cond = array();
        foreach ($reference as $col=>$value) {
            $cond[] = "$col='$value'";
        }
        $cond = $nb_ref > 1 ? implode(' AND ',$cond):implode($cond);

        # Parse columns
        $set = array();
        foreach ($content as $col=>$value) {
            $value = $this->escape_query($value);
            $set[] = "$col='$value'";
        }
        $set = implode(',',$set);

		$sql = "UPDATE $table_name SET $set WHERE $cond";
        if (self::send_query($sql)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Create or update a table
     * @param $tablename
     * @param $tabledata
     * @param bool $overwrite
     * @return bool
     */
    public function makeorupdate($tablename,$tabledata,$overwrite=false) {
        $columns = array();
        $defcolumns = array();
        foreach ($tabledata as $column=>$data) {
            $defcolumns[] = $column;
            if ($column == "primary") {
                $columns[] = "PRIMARY KEY($data)";
            } else {
                $datatype = $data[0];
                $defaut = (isset($data[1])) ? $data[1]:false;
                $col = "`$column` $datatype";
                if ($defaut != false) {
                    $col .= " DEFAULT '$defaut'";
                }
                $columns[] = $col;
            }
        }
        $columndata = implode(',',$columns);

        // If overwrite, then we simply create a new table and drop the previous one
        if ($overwrite || self::tableExists($tablename) == false) {
            self::createtable($tablename,$columndata,$overwrite);
        } else {
            // Get existent columns
            $keys = self::getcolumns($tablename);
            // Add new non existent columns or update previous version
            $prevcolumn = "id";
            foreach ($tabledata as $column=>$data) {
                if ($column !== "primary" && $column != "id") {
                    $datatype = $data[0];
                    $default = (isset($data[1])) ? $data[1]:false;
                    $oldname = (isset($data[2])) ? $data[2]:false;

                    // Change the column's name if asked and if this column exists
                    if ($oldname !== false && in_array($oldname,$keys)) {
                        $sql = "ALTER TABLE $tablename CHANGE $oldname $column $datatype";
                        if ($default !== false) $sql .= " DEFAULT '$default'";
                        self::send_query($sql);
                    // If the column does not exist already, then we simply add it to the table
                    } elseif (!in_array($column,$keys)) {
                        self::addcolumn($tablename,$column,$datatype,$prevcolumn);
                    // Check if the column's data type is consistent with the new version
                    } else {
                        $sql = "ALTER TABLE $tablename MODIFY $column $datatype";
                        if ($default !== false) $sql .= " DEFAULT '$default'";
                        self::send_query($sql);
                    }
                    $prevcolumn = $column;
                }
            }

            // Get updated columns
            $keys = self::getcolumns($tablename);
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

    /**
     * Get primary key of a table
     * @param $tablename
     * @return array
     */
    public function getprimarykeys($tablename) {
        $sql = "SHOW KEYS FROM $tablename WHERE Key_name = 'PRIMARY'";
        $req = self::send_query($sql);
        $keys = array();
        while ($row = mysqli_fetch_assoc($req)) {
            $keys[] = $row['Column_name'];
        }
        return $keys;
    }

    /**
     * Get content from table (given a column and a row(optional))
     * @param $table_name
     * @param $cols_name
     * @param null $refcol
     * @param null $id
     * @param null $op
     * @return array
     */
    public function getinfo($table_name,$cols_name,$refcol = NULL,$id = NULL, $op = NULL) {
		$nref = count($refcol);
        $cond = "";
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

    /**
     * Close connection to the db
     * @return null
     */
    public function bdd_close() {
		mysqli_close($this->bdd);
		$bdd = null;
        return $bdd;
	}
}
