<?php

    include 'sql_helper.php';

    $tsuite_conn = get_database_connection('localhost', 'tsuite_admin', 'password', 'tsuite');

    $request_method = $_SERVER['REQUEST_METHOD']; // GET, POST, PUT, DELETE...
    $request_uri = rtrim($_SERVER['REQUEST_URI'], '/'); // /example?foo=bar1&foo=bar2 => /example?foo=bar2
    $request_is_https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') == true; // false

    echo "Request Method: $request_method<br />";
    echo "Request URI: $request_uri<br />";
    echo "Request is HTTPS: $request_is_https<br />";

?>