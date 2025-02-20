<?php declare(strict_types=1); // strict typing

    $conn = null;

    function get_database_connection($servername = 'localhost', $username = 'root', $password = 'password', $database = 'database') {

        global $conn;

        try {

            $conn = new PDO("mysql:host=$servername;dbname=$database", $username, $password);

            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $conn;

        } catch(PDOException $e) {
            echo $e->getMessage();
            return null;
        }
    }

    function list_tables() {
        global $conn;

        if($conn == null)
            $conn = get_database_connection();

        $query = 'SHOW TABLES';
        $stmt = $conn->prepare($query);
        $stmt->execute();

        $arr = $stmt->fetchAll();

        $ret = array();

        for($x = 0; $x < sizeof($arr); $x++) {
            $ret[sizeof($ret)] = $arr[$x][0];
        }

        return $ret;
    }

    function get_table_columns($table) {
        global $conn;

        if($conn == null)
            $conn = get_database_connection();

        $query = "SHOW COLUMNS FROM $table";
        $stmt = $conn->prepare($query);
        $stmt->execute();

        $ret =  $stmt->fetchAll();

        $columns = array();

        $x = 0;

        foreach($ret as $column) {
            $columns[$x++] = $column['Field'];
        }

        return $columns;
    }

    function insert_table_entry($table, $columns, $data) {
        global $conn;

        if($conn == null)
            $conn = get_database_connection();

        $columns_string = '(';
        $values_string = '(';
        $exec_vals = array();

        for($x = 0; $x < sizeof($columns); $x++) {
            $name = $columns[$x];
            if($name == 'id') continue;
            if($x != sizeof($columns) - 1) {
                $columns_string .= "";
                $columns_string .= "$name,";
            } else {
                $columns_string .= "";
                $columns_string .= "$name)";
            }
        }

        for($x = 0; $x < sizeof($columns); $x++) {
            $name = $columns[$x];
            if($name == 'id') continue;
            $val=$data[$name];
            $exec_vals[$name] = $val;
            if($x != sizeof($columns) - 1) {
                $values_string .= ":";
                $values_string .= "$name,";
            } else {
                $values_string .= ":";
                $values_string .= "$name)";
            }
        }

        $query = "INSERT INTO $table $columns_string VALUES $values_string";

        $stmt = $conn->prepare($query);
        $stmt->execute($exec_vals);

        $ret =  $stmt->fetch();

        return $ret;

    }

    function export_table($table) {
        global $conn;

        if($conn == null)
            $conn = get_database_connection();

        $query = "SELECT * FROM $table";
        $stmt = $conn->prepare($query);
        $stmt->execute();

        $result = $stmt->fetchAll();

        $ret = array();

        $x = 0;

        foreach($result as $res) {
            foreach(array_keys($res) as $key) {
                if(((int) $key + 0) != $key)
                    $ret[$x][$key] = $res[$key];
            }
            $x++;
        }

        return json_encode($ret);
    }

    /*
    * UPDATE a TABLE $table with data found in $updates
    *      $updates is an array of arrays
    *      [id = #, [key1 = val1, key2 = val2]]
    *
    */
    function update_table($table, $updates) {
        global $conn;

        if($conn == null)
            $conn = get_database_connection();

        for($x = 0; $x < sizeof($updates); $x++) {
            $update = $updates[$x];
            $id = $update['id'];
            $data = $update['data'];
            $key = $data['key'];
            $val = $data['val'];
            $query = "UPDATE $table SET $key = :v WHERE id = :i";
            $stmt = $conn->prepare($query);
            $stmt->execute(array('v' => $val, 'i' => $id));
        }

    }

    function query($query, $prepared_fields = array()) {
        if($query == null || $query == '')
            return null;

        global $conn;

        if($conn == null)
            $conn = get_database_connection();

        try {
        
            $stmt = $conn->prepare($query);

            // If prepare failed, it will throw an exception
            if (!$stmt) {
                throw new Exception("Query preparation failed.");
            }

            $stmt->execute($prepared_fields);
            
            // If it's an INSERT or UPDATE, return affected rows or inserted ID
            if (strpos(strtoupper($query), 'INSERT') === 0) {
                return $conn->lastInsertId(); // Return the last inserted ID for INSERT queries
            }
        
            if (strpos(strtoupper($query), 'UPDATE') === 0) {
                return $stmt->rowCount(); // Return the number of affected rows for UPDATE queries
            }

            return $stmt->fetchAll();

        } catch (Exception $e) {
            //echo $e->getMessage();
            return false;
        }
    }

?>
