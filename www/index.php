<?php

    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);

    include 'sql_helper.php';

    include 'http/handler/api/api_commit_handler.php';
    include 'http/handler/api/api_repo_handler.php';
    include 'http/handler/api/api_global_setting_handler.php';
    include 'http/handler/api/api_repo_setting_handler.php';

    include 'http/handler/lib/lib_js_handler.php';

    include 'http/handler/page/page_setting_handler.php';
    include 'http/handler/page/page_repo_handler.php';
    include 'http/handler/page/page_home_handler.php';

    $db_host = getenv('TSUITE_DB_HOST');
    $db_user = getenv('TSUITE_DB_USER');
    $db_pass = getenv('TSUITE_DB_PASS');
    $db_name = getenv('TSUITE_DB_NAME');
    
    $tsuite_conn = get_database_connection($db_host, $db_user, $db_pass, $db_name);

    $request_method = $_SERVER['REQUEST_METHOD']; // GET, POST, PUT, DELETE...
    $request_uri = '/' . ltrim(rtrim($_SERVER['REQUEST_URI'], '/'), '/'); // /example?foo=bar1&foo=bar2 => /example?foo=bar2
    $request_is_https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') == true; // false

    $uri_path = explode('?', $request_uri)[0]; // /example

    $uri_query = explode('?', $request_uri); //foo=bar1&foo=bar2
    if(isset($uri_query[1]))
        $uri_query = $uri_query[1];
    else
        $uri_query = '';

    $uri_parts = explode('/', ltrim($uri_path, '/')); // ['', 'example']

    $uri_args = array();

    foreach (explode('&', $uri_query) as $arg) {
        $parts = explode('=', $arg);
        if(sizeof($parts) < 2) {
            continue;
        }
        $key = explode('=', $arg)[0];
        $value = explode('=', $arg)[1];
        $uri_args[$key] = $value;
    }

    $debug = false;

    if(isset($uri_args['debug']) && $uri_args['debug'] == 'true') {
        $debug = true;
    }

    if($debug) {
        echo "URI Path: $uri_path<br />";
        echo "URI Query: $uri_query<br />";
    
        echo "Request Method: $request_method<br />";
        echo "Request is HTTPS: $request_is_https<br />";
        echo "Request URI: $request_uri<br />";
        foreach (explode('&', $uri_query) as $arg) {
            echo "URI Arg: $key => $value<br />";
        }
    }

    $api_routes = array(
        'GET' => array(
            '/api/v1/commit' => 'handle_api_commit_get',
            '/api/v1/repo' => 'handle_api_repo_get',
            '/api/v1/repo_setting' => 'handle_api_repo_setting_get',
            '/api/v1/global_setting' => 'handle_api_global_setting_get',
        ),
        'POST' => array(
            '/api/v1/commit' => 'handle_api_commit_post',
            '/api/v1/repo_setting' => 'handle_api_repo_setting_post',
            '/api/v1/global_setting' => 'handle_api_global_setting_post'
        ),
        'PUT' => array(
            '/api/v1/commit' => 'handle_api_commit_put',
            '/api/v1/repo_setting' => 'handle_api_repo_setting_put',
            '/api/v1/global_setting' => 'handle_api_global_setting_put'
        )
    );

    $page_routes = array(
        'GET' => array(
            '/settings/repo/{repo}' => 'handle_page_repo_setting_get',
            '/settings/global' => 'handle_page_global_setting_get',
            '/' => 'handle_page_home_get',
            '/repo/{repo}' => 'handle_page_repo_get',
            '/repo/{repo}/commit/{hash}' => 'handle_page_repo_commit_get'
        )
    );

    $lib_routes = array(
        'GET' => array(
            '/lib/js/{file}' => 'handle_lib_js_get',
        ),
        'POST' => array(
        ),
        'PUT' => array(
        )
    );

    $existing_tables = list_tables();

    $required_tables = array('commit', 'repo', 'global_setting', 'repo_setting');

    foreach($required_tables as $table) {
        if(!in_array($table, $existing_tables)) {
            if($debug)
                echo "Table $table does not exist!<br />";
            $create_sql_query = read_flat_file(dirname(__FILE__) . "/sqls/create_table_$table.sql");
            $res = query($create_sql_query);
            echo "Trying to create required table $table...<br />";
            if($res) {
                echo "Created table $table<br />";
            }
            else {
                echo "Failed to create table $table<br />";
            }
        }
    }

    $api_routed = route_request($api_routes, $request_method, $uri_path, $uri_parts, $uri_args);
    if(!$api_routed) {
        $lib_routed = route_request($lib_routes, $request_method, $uri_path, $uri_parts, $uri_args);
        if(!$lib_routed) {
            render_default_header();
            render_default_body_start();
            $page_routed = route_request($page_routes, $request_method, $uri_path, $uri_parts, $uri_args);
            render_default_footer();
            render_default_body_end();
        }
    }

    function route_request($api_routes, $request_method, $uri_path, $uri_parts, $uri_args) {
        if(isset($api_routes) && !empty($api_routes))
            if(isset($api_routes[$request_method]))
                if(isset($api_routes[$request_method][$uri_path])) {
                    call_user_func_array($api_routes[$request_method][$uri_path], array('uri_parts' => $uri_parts, 'uri_args' => $uri_args));
                    return true;
                }

        foreach ($api_routes[$request_method] as $route => $handler) {
            // Convert {param} placeholders to regex pattern (match any non-slash characters)
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route);
    
            if (preg_match("#^" . $pattern . "$#", $uri_path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY); // Extract named params
                call_user_func_array($handler, array_merge($params, array('uri_parts' => $uri_parts, 'uri_args' => $uri_args)));
                return true;
            }
        }
    }

    if(!$page_routed) {
        http_response_code(404);
        echo json_encode(array('status' => 'failed', 'error' => '404 Not Found'));
        exit();
    }

    function render_default_header() {
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>tSuite</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
        <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
        <script src="/lib/js/main.js"></script></head><body>';
    }

    function render_default_body_start() {
        echo '<div style="margin: 0 auto; width: 80%;"><h1>tSuite</h1>';
    }

    function render_default_body_end() {
        echo '</div>';
    }

    function render_default_footer() {
        echo '        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
</body></html>';
    }

    function json_error_and_exit($error_msg, $http_code = '400') {
        http_response_code($http_code);
        echo json_encode(array('status' => 'failed', 'error' => $error_msg));
        exit();
    }

    function html_error_and_exit($error_msg, $http_code = '400') {
        http_response_code($http_code);
        echo $error_msg;
        exit();
    }

    function html_error($error_msg, $http_code = '400') {
        http_response_code($http_code);
        echo $error_msg;
    }

    function read_flat_file($path) {
        $ret = '';
        $file = fopen($path, "r");
        if(!$file) {
            throw new Exception("Unable to open file $path");
        }
        while(($read = fgets($file)) != null) {
            $ret .= $read;
        }
        fclose($file);
        return $ret;
    }

    function do_curl($uri, $data, $post = true) {

        $url = "localhost:80/$uri";
        
        $ch = curl_init();

        if(!$post) {
            $url .= '?' . http_build_query($data);
        }
        else {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_HEADER, false);

        curl_setopt($ch, CURLOPT_NOBODY, false); // remove body

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $head = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return array('http_code' => $httpCode, 'response' => $head);
    }

    function get_worker_sys_details() {
        $cmd = "ps -ef | grep worker.php | grep -v grep";
        $output = shell_exec($cmd);
        return $output;
    }

    function is_worker_running() {
        return get_worker_sys_details() != '';
    }

    function format_milliseconds($milliseconds) {
        if ($milliseconds === 'Unknown') {
            return 'Unknown';
        }
        
        if ($milliseconds < 1000) {
            return "{$milliseconds}ms";  // Show as ms
        } elseif ($milliseconds < 60000) {
            return number_format($milliseconds / 1000, 3) . "s"; // Convert to seconds with 2 decimal places
        } else {
            $minutes = floor($milliseconds / 60000);
            $seconds = number_format(($milliseconds % 60000) / 1000, 2);
            return "{$minutes}m {$seconds}s"; // Show minutes & seconds
        }
    }

?>