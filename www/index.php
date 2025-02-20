<?php

    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);

    include 'sql_helper.php';

    include 'http/handler/api/api_commit_handler.php';
    include 'http/handler/api/api_repo_handler.php';
    include 'http/handler/api/api_setting_handler.php';

    include 'http/handler/page/page_setting_handler.php';

    $tsuite_conn = get_database_connection('localhost', 'tsuite_admin', 'password', 'tsuite');

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
            '/api/v1/setting' => 'handle_api_setting_get',
            '/settings/repo/{repo}' => 'handle_page_setting_get'
        ),
        'POST' => array(
            '/api/v1/commit' => 'handle_api_commit_post',
            '/api/v1/setting' => 'handle_api_setting_post'
        ),
        'PUT' => array(
            '/api/v1/commit',
            '/api/v1/setting' => 'handle_api_setting_put'
        )
    );

    $existing_tables = list_tables();

    $required_tables = array('commit', 'repo', 'global_setting', 'repo_setting');

    foreach($required_tables as $table) {
        if(!in_array($table, $existing_tables)) {
            if($debug)
                echo "Table $table does not exist!<br />";
            $create_sql_query = read_flat_file(dirname(__FILE__) . "/sqls/create_table_$table.sql");
            query($create_sql_query);
            die("Unable to create required table: $table");
        }
    }

    route_request($api_routes, $request_method, $uri_path, $uri_parts, $uri_args);

    function route_request($api_routes, $request_method, $uri_path, $uri_parts, $uri_args) {
        if(isset($api_routes) && !empty($api_routes))
            if(isset($api_routes[$request_method]))
                if(isset($api_routes[$request_method][$uri_path])) {
                    call_user_func_array($api_routes[$request_method][$uri_path], array('uri_parts' => $uri_parts, 'uri_args' => $uri_args));
                    return;
                }

        foreach ($api_routes[$request_method] as $route => $handler) {
            // Convert {param} placeholders to regex pattern (match any non-slash characters)
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route);
    
            if (preg_match("#^" . $pattern . "$#", $uri_path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY); // Extract named params
                call_user_func_array($handler, array_merge($params, array('uri_parts' => $uri_parts, 'uri_args' => $uri_args)));
                return;
            }
        }
    }

    if($uri_parts[0] == '') {
        $repos = query('SELECT * FROM repo');
        echo '<table><tr><th>Name</th><th>URL</th><th>Download Location</th><th>Install Location</th></tr>';
        foreach($repos as $repo) {
            $name = $repo['name'];
            $url = $repo['url'];
            $download_location = $repo['download_location'];
            $install_location = $repo['install_location'];

            echo "<tr><td><a href='/repo/$name'>$name</a></td><td><a href='$url'>$url</a></td><td>$download_location</td><td>$install_location</td></tr>";
        }
        echo '</table>';

        if(is_worker_running()) {
            echo '<br /><strong>Worker is running</strong>';
        } else {
            echo '<br /><strong>Worker is not running</strong>';
        }

        exit();
    }

    if($uri_parts[0] == 'repo') {
        if(isset($uri_parts[1]) && sizeof($uri_parts) == 2) {
            $use_name = true;
            if(is_int($uri_parts[1])) {
                $use_name = true;
            }

            if($use_name) {
                $arr = query("SELECT * FROM repo WHERE name = :name", array('name' => $uri_parts[1]));
                if(sizeof($arr) == 0 || $arr == null) {
                    echo "Repo not found!<br />";
                    exit();
                }
            } else {
                $arr = query("SELECT * FROM repo WHERE id = :id", array('id' => $uri_parts[1]));
                if(sizeof($arr) == 0 || $arr == null) {
                    echo "Repo not found!<br />";
                    exit();
                }
            }

            $repo = $arr[0];

            $id = $repo['id'];
            $name = $repo['name'];
            $url = $repo['url'];
            $download_location = $repo['download_location'];
            $install_location = $repo['install_location'];

            echo "<strong>Repo</strong>: <a href='/repo/$name'>$name</a><br />";
            echo "<strong>Repo URL</strong>: <a href='$url'>$url</a><br />";
            echo "<strong>Download Location</strong>: $download_location<br />";
            echo "<strong>Install Location</strong>: $install_location<br />";
            echo "<br /><strong>Recent commits</strong>:<br />";

            $commits = query('SELECT * FROM commit WHERE repo_id = :id ORDER BY id DESC LIMIT 25', array('id' => $id));
            echo '<table style="width:100%;border-collapse:collapse;margin-top:10px"><tr><th>date</th><th>hash</th><th>message</th><th>author</th><th>Test Status</th><th>Tests Passing</th><th>Tests Failing</th><th>Download Duration</th><th>Install Duration</th><th>Test Duration</th><th>Total Duration</th><th>Actions</th></tr>';
            foreach($commits as $commit) {

                $date = $commit['date'];
                $commit_hash = $commit['hash'];
                $message = $commit['message'];
                $author = $commit['author'];
                $test_status = $commit['test_status'] ?? null;
                $success_tests = $commit['success_tests'] ?? 'Unknown';
                $failed_tests = $commit['failed_tests'] ?? 'Unknown';
                $download_duration = $commit['download_duration'] ?? 'Unknown';
                $install_duration = $commit['install_duration'] ?? 'Unknown';
                $test_duration = $commit['test_duration'] ?? 'Unknown';
                $total_duration = 0;

                if($download_duration != 'Unknown') {
                    $total_duration += $download_duration;
                }
                if($install_duration != 'Unknown') {
                    $total_duration += $install_duration;
                }
                if($test_duration != 'Unknown') {
                    $total_duration += $test_duration;
                }

                if($total_duration == 0) {
                    $total_duration = 'Unknown';
                }

                if($test_status === null) {
                    $test_status = 'N/A';
                }

                if($test_status == 0) $test_status = 'Passed';
                if($test_status == 1) $test_status = 'Failed';

                $test_status_td = $test_status == 'Passed' ? '<td style="background-color:d4edda;color:155724;font-weight:bold">' : '<td style="background-color:ffebeb;color:d00;font-weight:bold">';
    
                echo "<tr><td>$date</td><td><a href='/repo/$name/commit/$commit_hash'>$commit_hash</a></td><td>$message</td><td>$author</td>$test_status_td$test_status</td><td>$success_tests</td><td>$failed_tests</td><td>$download_duration</td><td>$install_duration</td><td>$test_duration</td><td>$total_duration</td><td><p>Actions here</p></td></tr>";
            }
            echo '</table>';
            
            exit();

        }

        if(isset($uri_parts[1]) && sizeof($uri_parts) == 4) {
            // /repos/REPO_NAME/commit/COMMIT_HASH

            if($uri_parts[2] == 'commit') {
                $repo_name = $uri_parts[1];
                $commit_hash = $uri_parts[3];

                $repo = query('SELECT * FROM repo WHERE name = :name', array('name' => $repo_name));
                if(sizeof($repo) == 0) {
                    echo "Repo not found!<br />";
                    exit();
                }

                $repo = $repo[0];
                $repo_id = $repo['id'];
                $repo_download_path = $repo['download_location'];

                $commit = query('SELECT * FROM commit WHERE repo_id = :repo_id AND hash = :hash ORDER BY test_status DESC', array('repo_id' => $repo_id, 'hash' => $commit_hash));
                if(sizeof($commit) == 0) {
                    echo "Commit not found!<br />";
                    exit();
                }

                $commit = $commit[0];

                $date = $commit['date'];
                $commit_hash = $commit['hash'];
                $message = $commit['message'];
                $author = $commit['author'];
                $test_status = $commit['test_status'] ?? null;
                $success_tests = $commit['success_tests'] ?? 'Unknown';
                $failed_tests = $commit['failed_tests'] ?? 'Unknown';
                $download_duration = $commit['download_duration'] ?? 'Unknown';
                $install_duration = $commit['install_duration'] ?? 'Unknown';
                $test_duration = $commit['test_duration'] ?? 'Unknown';
                $total_duration = 0;

                if($download_duration != 'Unknown') {
                    $total_duration += $download_duration;
                }
                if($install_duration != 'Unknown') {
                    $total_duration += $install_duration;
                }
                if($test_duration != 'Unknown') {
                    $total_duration += $test_duration;
                }

                if($total_duration == 0) {
                    $total_duration = 'Unknown';
                }

                if($test_status === null) {
                    $test_status = 'N/A';
                }

                if($test_status == 0) $test_status = 'Passed';
                if($test_status == 1) $test_status = 'Failed';

                echo "<strong>Repo</strong>: <a href='/repo/$repo_name'>$repo_name</a><br />";
                echo "<strong>Hash</strong>: $commit_hash<br />";
                echo "<strong>Date</strong>: $date<br />";
                echo "<strong>Message</strong>: $message<br />";
                echo "<strong>Author</strong>: $author<br />";
                echo "<strong>Test Status</strong>: $test_status<br />";
                echo "<strong>Tests Passing</strong>: $success_tests<br />";
                echo "<strong>Tests Failing</strong>: $failed_tests<br />";
                echo "<strong>Download Duration</strong>: $download_duration<br />";
                echo "<strong>Install Duration</strong>: $install_duration<br />";
                echo "<strong>Test Duration</strong>: $test_duration<br />";
                echo "<strong>Total Duration</strong>: $total_duration<br />";

                $test_result_file = dirname($repo_download_path) . "/test_results/$commit_hash.json";

                try {
                    $test_result_json = read_flat_file($test_result_file);
                } catch(Exception $e) {
                    echo "Test results not found!<br />";
                    exit();
                }
                $test_result = json_decode($test_result_json, true);

                $files = $test_result['files'];

                echo "<br /><strong>Test Results</strong>:<br /><br />";

                echo '<table style="width:100%;border-collapse:collapse;margin-top:10px"><tr><th>File</th><th>Test Name</th><th>Status</th><th>Reason</th></tr>';

                foreach($files as $file_name => $file_data) {
                    foreach($file_data['tests'] as $function => $data) {
                        $status = $data['status'];
                        $reason = $data['reason'] ?? '';

                        if($status == 'success') {
                            $status = 'Passed';
                        } 
                        if($status == 'failure') {
                            $status = 'Failed';
                        }
                        
                        $test_status_td = $status == 'Passed' ? '<td style="background-color:d4edda;color:155724;font-weight:bold">' : '<td style="background-color:ffebeb;color:d00;font-weight:bold">';
    

                        echo "<tr><td>$file_name</td><td>$function</td>$test_status_td$status</td><td>$reason</td></tr>";
                    }
                }
                
                echo '</table>';

                exit();
            }
        }
    }

    http_response_code(404);
    echo json_encode(array('status' => 'failed', 'error' => '404 Not Found'));
    exit();

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

?>