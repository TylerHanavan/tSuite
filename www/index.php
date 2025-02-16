<?php

    include 'sql_helper.php';

    $tsuite_conn = get_database_connection('localhost', 'tsuite_admin', 'password', 'tsuite');

    $request_method = $_SERVER['REQUEST_METHOD']; // GET, POST, PUT, DELETE...
    $request_uri = '/' . ltrim(rtrim($_SERVER['REQUEST_URI'], '/'), '/'); // /example?foo=bar1&foo=bar2 => /example?foo=bar2
    $request_is_https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') == true; // false

    echo "Request Method: $request_method<br />";
    echo "Request is HTTPS: $request_is_https<br />";
    echo "Request URI: $request_uri<br />";

    $uri_path = explode('?', $request_uri)[0];
    $uri_query = explode('?', $request_uri)[1];

    echo "URI Path: $uri_path<br />";
    echo "URI Query: $uri_query<br />";

    $uri_args = array();

    foreach (explode('&', $uri_query) as $arg) {
        $key = explode('=', $arg)[0];
        $value = explode('=', $arg)[1];
        $uri_args[$key] = $value;
        echo "URI Arg: $key => $value<br />";
    }

    $existing_tables = list_tables();

    $required_tables = array('commits');

    foreach($required_tables as $table) {
        if(!in_array($table, $existing_tables)) {
            echo "Table $table does not exist!<br />";
            $create_sql_query = read_flat_file(dirname(__FILE__) . "/sqls/create_table_$table.sql");
            die();
        }
    }

    function read_flat_file($path) {
        $ret = '';
        $file = fopen($path, "r") or die("Unable to open file: $path");
        while(($read = fgets($file)) != null) {
            $ret .= $read;
        }
        fclose($file);
        return $ret;
    }

?>