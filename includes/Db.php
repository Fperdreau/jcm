<?php
/**
 * File for class AppDb
 *
 * PHP version 5
 *
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

namespace includes;

use includes\Config;
use includes\Logger;

/**
 * Class AppDb
 *
 * Handle communication routines with the database (writing, reading, updating)
 * and environmental information (list of tables associated to the application,...).
 */
class Db
{

    /**
     * Link to the database
     *
     * @var mysqli
     */
    private $bdd = null;

    /**
     * @var array $config
     */
    public $config;

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
     * @var bool
     */
    private $connected = false;

    /**
     * Db instance
     * @var null|Db
     */
    private static $instance = null;

    /**
     * Constructor
     */
    private function __construct()
    {
        // Get DB config
        $this->config = self::getConfig();
        $this->connect();
    }

    /**
     * Factory for Db instance
     * @return Db
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get db config
     * @return array
     */
    public static function getConfig()
    {
        return Config::getInstance()->getAll();
    }

    /**
     * Generate table name
     * @param $name
     * @return string
     */
    public function genName($name)
    {
        return $this->config['dbprefix'] . '_' . strtolower($name);
    }

    /**
     * Connects to DB and throws exceptions
     * @return mysqli|null
     * @throws Exception
     */
    public function connect()
    {

        if (!$this->connected) {
            try {
                if (!$this->bdd = @mysqli_connect(
                    $this->config['host'],
                    $this->config['username'],
                    $this->config['passw']
                )) {
                    throw new Exception("Failed to connect to the database (" . mysqli_connect_error() . ")");
                }
            } catch (Exception $e) {
                $result['status'] = false;
                $result['msg'] = $e->getMessage();
                Logger::getInstance(APP_NAME)->critical($result['msg']);
                die($result['msg']);
            }

            if (!mysqli_select_db($this->bdd, $this->config['dbname'])) {
                $result['msg'] = "Database '" . $this->config['dbname'] . "' cannot be selected<br/>"
                 . mysqli_error($this->bdd);
                Logger::getInstance(APP_NAME)->critical($result['msg']);
                die(json_encode($result['msg']));
            }

            if (!mysqli_query($this->bdd, "SET NAMES '$this->charset'")) {
                $result['msg'] = "Could not set database charset to '$this->charset'<br/>".mysqli_error($this->bdd);
                Logger::getInstance(APP_NAME)->critical($result['msg']);
                die(json_encode($result['msg']));
            }

            $this->connected = true;
        }

        return $this->bdd;
    }

    /**
     * Test credentials and throws exceptions
     * @param array $config
     * @return array
     */
    public static function testdb(array $config)
    {
        $link = @mysqli_connect($config['host'], $config['username'], $config['passw']);
        if (!$link) {
            $result['status'] = false;
            $result['msg'] = "Failed to connect to the database";
            Logger::getInstance(APP_NAME)->critical($result['msg']);
            return $result;
        }

        if (!@mysqli_select_db($link, $config['dbname'])) {
            $result['status'] = false;
            $result['msg'] = "Database '".$config['dbname']."' cannot be selected";
            Logger::getInstance(APP_NAME)->critical($result['msg']);
            return $result;
        }
        $result['status'] = true;
        $result['msg'] = "Connection successful";
        return $result;
    }

    /**
     * @return mixed
     */
    public function getCharSet()
    {
        return $this->bdd->get_charset();
    }

    /**
     * Get list of tables associated to the application
     * @param null|string $id: table name
     * @return string|null|array
     */
    public function getAppTables($id = null)
    {
        $sql = "SHOW TABLES FROM " . $this->config['dbname'] . " LIKE '" . $this->config['dbprefix'] . "%'";
        $req = self::sendQuery($sql);
        $appTables = array();
        while ($row = mysqli_fetch_array($req)) {
            $split = explode('_', $row[0]);
            $tableId = end($split);
            $appTables[$tableId] = $row[0];
        }
        $this->apptables = $appTables;
        if (!is_null($id)) {
            if (key_exists(strtolower($id), $appTables)) {
                return $appTables[strtolower($id)];
            } else {
                return null;
            }
        } else {
            return $appTables;
        }
    }

    /**
     * Check if the table exists
     * @param $table
     * @return bool
     */
    public function tableExists($table)
    {
        return $this->sendQuery("SHOW TABLES LIKE '%{$table}%'")->num_rows > 0;
    }

    /**
     * Send query to the database
     * @param $sql
     * @return bool|mysqli_result
     */
    public function sendQuery($sql)
    {
        $req = $this->bdd->query($sql);
        if ($req === false) {
            $msg = "Database Error [{$this->bdd->errno}]: COMMAND [{$sql}]: {$this->bdd->error}";
            Logger::getInstance(APP_NAME, get_called_class())->error($msg);
        }
        return $req;
    }

    /**
     * Escape query before committing to the db
     * @param $query
     * @return string
     */
    public function escapeQuery($query)
    {
        return mysqli_real_escape_string($this->bdd, $query);
    }

    /**
     * Get id of last inserted row
     * @return mixed
     */
    public function getLastId()
    {
        return mysqli_insert_id($this->bdd);
    }

    /**
     * Create a table
     * @param $table_name
     * @param $cols_name
     * @param bool $opt
     * @return bool
     */
    public function createtable($table_name, $cols_name, $opt = false)
    {
        if (self::tableExists($table_name) && $opt) {
            // Delete table if it exists
            self::deletetable($table_name);
        }

        if (self::tableExists($table_name) == false) {
            // Create table if it does not exist already
            $sql = 'CREATE TABLE '.$table_name.' ('. $cols_name .')';
            if (self::sendQuery($sql)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Drop a table
     * @param $table_name
     * @return bool|mysqli_result
     */
    public function deletetable($table_name)
    {
        $sql = 'DROP TABLE '.$table_name;
        return self::sendQuery($sql);
    }

    /**
     * Empty a table
     * @param $table_name
     * @return bool|mysqli_result
     */
    public function clearTable($table_name)
    {
        $sql = "TRUNCATE TABLE $table_name";
        return self::sendQuery($sql);
    }

    /**
     * Add a column to the table
     * @param $table_name
     * @param $col_name
     * @param $type
     * @param null $after
     * @return bool
     */
    public function addColumn($table_name, $col_name, $type, $after = null)
    {
        if (!$this->isColumn($table_name, $col_name)) {
            $sql = "ALTER TABLE {$table_name} ADD COLUMN {$col_name} {$type}";
            if (!is_null($after)) {
                $sql .= " AFTER {$after}";
            }
            return $this->sendQuery($sql);
        } else {
            return false;
        }
    }

    /**
     * Delete column from table
     * @param string $table_name
     * @param string $col_name
     * @return bool|mysqli_result
     */
    public function deleteColumn($table_name, $col_name)
    {
        if ($this->isColumn($table_name, $col_name)) {
            $sql = "ALTER TABLE {$table_name} DROP COLUMN {$col_name}";
            return $this->sendQuery($sql);
        } else {
            return false;
        }
    }

    /**
     * This function check if the specified column exists
     * :param string $table: table name
     * :param string $column: column name
     * :return bool
     */
    public function isColumn($table, $column)
    {
        $cols = $this->getColumns($table);
        return in_array($column, $cols);
    }

    /**
     * Get columns names
     * @param $tablename
     * @return array
     */
    public function getColumns($tablename)
    {
        $sql = "SHOW COLUMNS FROM {$tablename}";
        $req = $this->sendQuery($sql);
        $keys = array();
        while ($row = mysqli_fetch_array($req)) {
            $keys[] = $row['Field'];
        }
        return $keys;
    }

    /**
     * Add content (row) to a table
     * @param $table_name
     * @param $content
     * @return bool|int: failure or Id of inserted row
     */
    public function insert($table_name, $content)
    {
        $cols_name = array();
        $values = array();
        foreach ($content as $col => $value) {
            $cols_name[] = $col;
            $values[] = "'".$this->escapeQuery($value)."'";
        }

        if ($this->sendQuery(
            'INSERT INTO '.$table_name.'('.implode(',', $cols_name).') VALUES('.implode(',', $values).')'
        )) {
            return $this->bdd->insert_id;
        } else {
            return false;
        }
    }

    /**
     * Delete content from the table
     * @param $table_name
     * @param array $id
     * @return bool
     */
    public function delete($table_name, array $id)
    {
        $cpt = 0;
        $cond = array();
        foreach ($id as $col => $value) {
            $cond[] = "$col='".$value."'";
            $cpt++;
        }
        $cond = $cpt > 1 ? implode(' AND ', $cond) : implode('', $cond);

        return $this->sendQuery("DELETE FROM {$table_name} WHERE " . $cond);
    }

    /**
     * Update a row
     * @param string $table_name
     * @param array $content
     * @param array $reference
     * @return bool|mysqli_result
     */
    public function update($table_name, array $content, $reference = array())
    {

        # Parse conditions
        $nb_ref = count($reference);
        $cond = array();
        foreach ($reference as $col => $value) {
            $cond[] = "{$col}='{$value}'";
        }
        $cond = $nb_ref > 1 ? implode(' AND ', $cond) : implode($cond);

        # Parse columns
        $set = array();
        foreach ($content as $col => $value) {
            $value = $this->escapeQuery($value);
            $set[] = "{$col}='{$value}'";
        }
        $set = implode(',', $set);

        return $this->sendQuery("UPDATE {$table_name} SET {$set} WHERE {$cond}");
    }

    /**
     * Create or update a table
     * @param $tablename
     * @param $tabledata
     * @param bool $overwrite
     * @return bool
     */
    public function makeorupdate($tablename, $tabledata, $overwrite = false)
    {
        $columns = array();
        $defcolumns = array();
        foreach ($tabledata as $column => $data) {
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
        $columndata = implode(',', $columns);

        // If overwrite, then we simply create a new table and drop the previous one
        Logger::getInstance(APP_NAME, get_class($this))->info("Checking table '{$tablename}'");

        if ($overwrite || !$this->tableExists($tablename)) {
            Logger::getInstance(APP_NAME, get_called_class())->info("Creating table '{$tablename}'");
            $this->createtable($tablename, $columndata, $overwrite);
        } else {
            Logger::getInstance(APP_NAME, get_called_class())->info("Updating table schema '{$tablename}'");

            // Get existent columns
            $keys = $this->getColumns($tablename);
            // Add new non existent columns or update previous version
            $prevcolumn = "id";
            foreach ($tabledata as $column => $data) {
                if ($column !== "primary" && $column != "id") {
                    $datatype = $data[0];
                    $default = (isset($data[1])) ? $data[1]:false;
                    $oldname = (isset($data[2])) ? $data[2]:false;

                    // Change the column's name if asked and if this column exists
                    if ($oldname !== false && in_array($oldname, $keys)) {
                        $sql = "ALTER TABLE $tablename CHANGE $oldname $column $datatype";
                        if ($default !== false) {
                            $sql .= " DEFAULT '$default'";
                        }
                        $this->sendQuery($sql);
                    // If the column does not exist already, then we simply add it to the table
                    } elseif (!in_array($column, $keys)) {
                        $this->addColumn($tablename, $column, $datatype, $prevcolumn);
                    // Check if the column's data type is consistent with the new version
                    } else {
                        $sql = "ALTER TABLE $tablename MODIFY $column $datatype";
                        if ($default !== false) {
                            $sql .= " DEFAULT '$default'";
                        }
                        $this->sendQuery($sql);
                    }
                    $prevcolumn = $column;
                }
            }

            // Get updated columns
            $keys = $this->getColumns($tablename);
            // Remove deprecated columns
            foreach ($keys as $key) {
                if (!in_array($key, $defcolumns)) {
                    $sql = "ALTER TABLE $tablename DROP COLUMN $key";
                    $this->sendQuery($sql);
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
    public function getprimarykeys($tablename)
    {
        $sql = "SHOW KEYS FROM $tablename WHERE Key_name = 'PRIMARY'";
        $req = self::sendQuery($sql);
        $keys = array();
        while ($row = mysqli_fetch_assoc($req)) {
            $keys[] = $row['Column_name'];
        }
        return $keys;
    }

    /**
     * Get content from table (given a column and a row(optional))
     * @param string $table_name
     * @param array $fields : e.g. array('name','id')
     * @param array $where : e.g. array('city'=>'Paris')
     * @param null|string $opt: options (e.g. "ORDER BY year")
     * @return array : associate array
     */
    public function resultSet($table_name, array $fields, array $where = array(), $opt = null)
    {
        $parsed = self::parse($fields, $where);

        $req = $this->sendQuery("SELECT {$parsed['columns']} FROM {$table_name}" . " {$parsed['cond']}" . " {$opt}");
        $data = array();
        while ($row = $req->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    /**
     * Get content from table (given a column and a row(optional))
     * @param string $table_name
     * @param array $fields : e.g. array('name','id')
     * @param array $where : e.g. array('city'=>'Paris')
     * @param null|string $opt : options (e.g. "ORDER BY year")
     * @return array
     */
    public function single($table_name, array $fields, array $where = array(), $opt = null)
    {
        $parsed = self::parse($fields, $where);

        $req = $this->sendQuery("SELECT {$parsed['columns']} FROM {$table_name}" . " {$parsed['cond']}" . " {$opt}");

        return $req->fetch_assoc();
    }

    /**
     * Parse query arguments
     * @param $fields
     * @param $where
     * @return array
     */
    public static function parse($fields, $where)
    {
        $cols = implode(',', $fields); // format columns name

        // Build query
        $params = null;
        $cond = null;
        if (!empty($where)) {
            $cond = array(); // Condition (e.g.: name=:name)
            foreach ($where as $col => $value) {
                if (is_array($value)) {
                    $thisCond = array();
                    foreach ($value as $item) {
                        $parsedArg = explode(' ', $item);
                        $thisOp = count($parsedArg) > 1 ? $parsedArg[1] : '=';
                        $thisCond[] = $col . $thisOp . "'{$parsedArg[0]}'";
                    }
                    $cond[] = implode(' OR ', $thisCond);
                } else {
                    $parsedArg = explode(' ', $col);
                    $thisOp = count($parsedArg) > 1 ? $parsedArg[1] : '=';
                    $cond[] = $parsedArg[0] . $thisOp . "'{$value}'";
                }
            }
            $cond = "WHERE " . implode(' AND ', $cond);
        } else {
            $cond = null;
        }

        return array('cond'=>$cond, 'columns'=>$cols);
    }

    /**
     * Get content from column
     * @param string $table_name
     * @param string $column_name : column name
     * @param array $where : e.g. array('city'=>'Paris')
     * @param array|null $op : logical operators corresponding to the $where array. e.g. array('=','!=')
     * @param null|string $opt : options (e.g. "ORDER BY year")
     * @return array : associate array
     * @throws Exception
     */
    public function column($table_name, $column_name, array $where = array(), array $op = null, $opt = null)
    {
        // Build query
        if (!empty($where)) {
            $i = 0;
            $cond = array(); // Condition (e.g.: name=:name)
            foreach ($where as $col => $value) {
                $thisOp = ($op == null) ? "=" : $op[$i];
                $cond[] = $col . $thisOp . "'{$value}'";
                $i++;
            }
            $cond = " WHERE ".implode(' AND ', $cond);
        } else {
            $cond = null;
        }

        if (!is_null($opt)) {
            $opt = " " . $opt;
        }
        $sql = "SELECT {$column_name} FROM {$table_name}" . $cond . $opt;
        $req = self::sendQuery($sql);
        if ($req !== false) {
            $data = array();
            while ($row = $req->fetch_assoc()) {
                $data[] = $row[$column_name];
            }
            return $data;
        } else {
            Logger::getInstance(APP_NAME, __CLASS__)->critical("Database error: COMMAND [{$sql}]");
            throw new Exception("Database error: COMMAND [{$sql}]");
        }
    }

    /**
     * Close connection to the db
     * @return null
     */
    public function closeConnection()
    {
        if ($this->connected) {
            mysqli_close($this->bdd);
            $this->bdd = null;
        }
        return $this->bdd;
    }
}
