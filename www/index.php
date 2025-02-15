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

?>