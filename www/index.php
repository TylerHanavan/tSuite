<?php

    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);

    include 'sql_helper.php';

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

    $existing_tables = list_tables();

    $required_tables = array('commits', 'repos');

    foreach($required_tables as $table) {
        if(!in_array($table, $existing_tables)) {
            if($debug)
                echo "Table $table does not exist!<br />";
            $create_sql_query = read_flat_file(dirname(__FILE__) . "/sqls/create_table_$table.sql");
            query($create_sql_query);
            die("Unable to create required table: $table");
        }
    }

    if($uri_parts[0] == 'api') {
        if($uri_parts[1] == 'commits') {
            if($request_method == 'GET') {
                if($uri_parts[2] == 'latest') {
                    $commits = query('SELECT * FROM commits ORDER BY id DESC LIMIT 10');
                    echo json_encode($commits);
                    exit();
                }

                $repo = $_GET['repo'] ?? null;
                if($repo == null) {
                    $commits = query('SELECT * FROM commits');
                    echo json_encode($commits);
                } else {
                    $commits = query('SELECT * FROM commits WHERE repo = :repo', array('repo' => $repo));
                    echo json_encode($commits);
                }
                exit();
            }
            if($request_method == 'POST') {
                $entities = $_POST['entities'] ?? null;
                if($entities == null) {
                    json_error_and_exit('Entities null or missing from payload');
                }
                if(sizeof($entities) == 0) {
                    json_error_and_exit('Entities array exists but is empty');
                }
                $counter = 0;
                foreach($entities as $entity) {
                    $repo = $entity['repo'] ?? null;
                    if($repo == null) {
                        json_error_and_exit("No repo provided for entity $counter");
                    }

                    $commit_hash = $entity['commit_hash'] ?? null;
                    if($commit_hash == null) {
                        json_error_and_exit("No commit_hash provided for entity $counter");
                    }

                    $date = $entity['date'] ?? null;
                    if($date == null) {
                        json_error_and_exit("No date provided for entity $counter");
                    }

                    $message = $entity['message'] ?? null;
                    if($message == null) {
                        json_error_and_exit("No message provided for entity $counter");
                    }

                    $author = $entity['author'] ?? null;
                    if($author == null) {
                        json_error_and_exit("No author provided for entity $counter");
                    }

                    $success_tests = $entity['success_tests'] ?? null;
                    $failed_tests = $entity['failed_tests'] ?? null;

                    $test_status = $entity['test_status'] ?? null;

                    $download_duration = $entity['download_duration'] ?? -1;
                    $install_duration = $entity['install_duration'] ?? -1;
                    $test_duration = $entity['test_duration'] ?? -1;

                    $insert_query = "INSERT INTO commits (repo, commit_hash, date, message, author, test_status, success_tests, failed_tests, download_duration, install_duration, test_duration) VALUES (:repo, :commit_hash, :date, :message, :author, :test_status, :success_tests, :failed_tests, :download_duration, :install_duration, :test_duration)";

                    if(!is_int($repo)) {
                        $repo_id = query('SELECT id FROM repos WHERE name = :name', array('name' => $repo));
                        if(sizeof($repo_id) == 0) {
                            json_error_and_exit("Repo $repo not found");
                        }
                        $repo = $repo_id[0]['id'];
                    }

                    $insert_vals = array(
                        'repo' => $repo,
                        'commit_hash' => $commit_hash,
                        'date' => $date,
                        'message' => $message,
                        'author' => $author,
                        'test_status' => $test_status,
                        'success_tests' => $success_tests,
                        'failed_tests' => $failed_tests,
                        'download_duration' => $download_duration,
                        'install_duration' => $install_duration,
                        'test_duration' => $test_duration
                    );

                    $result = query($insert_query, $insert_vals);

                    var_dump($result);
                }

                $counter++;

                echo json_encode(array('status' => 'success'));
                exit();
            }
        }
        if($uri_parts[1] == 'repos') {
            if($request_method == 'GET') {
                $repos = query('SELECT * FROM repos');
                echo json_encode($repos);
                exit();
            }
        }
    }

    if($uri_parts[0] == '') {
        $repos = query('SELECT * FROM repos');
        echo '<table><tr><th>Name</th><th>URL</th><th>Download Location</th><th>Install Location</th></tr>';
        foreach($repos as $repo) {
            $name = $repo['name'];
            $url = $repo['url'];
            $download_location = $repo['download_location'];
            $install_location = $repo['install_location'];

            echo "<tr><td><a href='/repos/$name'>$name</a></td><td><a href='$url'>$url</a></td><td>$download_location</td><td>$install_location</td></tr>";
        }
        echo '</table>';
        exit();
    }

    if($uri_parts[0] == 'repos') {
        if(isset($uri_parts[1]) && sizeof($uri_parts) == 2) {
            $use_name = true;
            if(is_int($uri_parts[1])) {
                $use_name = true;
            }

            if($use_name) {
                $arr = query("SELECT * FROM repos WHERE name = :name", array('name' => $uri_parts[1]));
                if(sizeof($arr) == 0 || $arr == null) {
                    echo "Repo not found!<br />";
                    exit();
                }
            } else {
                $arr = query("SELECT * FROM repos WHERE id = :id", array('id' => $uri_parts[1]));
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

            echo "<strong>Repo</strong>: <a href='/repos/$name'>$name</a><br />";
            echo "<strong>Repo URL</strong>: <a href='$url'>$url</a><br />";
            echo "<strong>Download Location</strong>: $download_location<br />";
            echo "<strong>Install Location</strong>: $install_location<br />";
            echo "<br /><strong>Recent commits</strong>:<br />";

            $commits = query('SELECT * FROM commits WHERE repo = :id ORDER BY id DESC LIMIT 25', array('id' => $id));
            echo '<table style="width:100%;border-collapse:collapse;margin-top:10px"><tr><th>date</th><th>commit_hash</th><th>message</th><th>author</th><th>Test Status</th><th>Tests Passing</th><th>Tests Failing</th><th>Download Duration</th><th>Install Duration</th><th>Test Duration</th><th>Total Duration</th></tr>';
            foreach($commits as $commit) {

                $date = $commit['date'];
                $commit_hash = $commit['commit_hash'];
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
    
                echo "<tr><td>$date</td><td><a href='/repos/$name/commits/$commit_hash'>$commit_hash</a></td><td>$message</td><td>$author</td>$test_status_td$test_status</td><td>$success_tests</td><td>$failed_tests</td><td>$download_duration</td><td>$install_duration</td><td>$test_duration</td><td>$total_duration</td></tr>";
            }
            echo '</table>';
            
            exit();

        }

        if(isset($uri_parts[1]) && sizeof($uri_parts) == 4) {
            // /repos/REPO_NAME/commits/COMMIT_HASH

            if($uri_parts[2] == 'commits') {
                $repo_name = $uri_parts[1];
                $commit_hash = $uri_parts[3];

                $repo = query('SELECT * FROM repos WHERE name = :name', array('name' => $repo_name));
                if(sizeof($repo) == 0) {
                    echo "Repo not found!<br />";
                    exit();
                }

                $repo = $repo[0];
                $repo_id = $repo['id'];
                $repo_download_path = $repo['download_location'];

                $commit = query('SELECT * FROM commits WHERE repo = :repo AND commit_hash = :commit_hash ORDER BY test_status DESC', array('repo' => $repo_id, 'commit_hash' => $commit_hash));
                if(sizeof($commit) == 0) {
                    echo "Commit not found!<br />";
                    exit();
                }

                $commit = $commit[0];

                $date = $commit['date'];
                $commit_hash = $commit['commit_hash'];
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

                echo "<strong>Repo</strong>: <a href='/repos/$repo_name'>$repo_name</a><br />";
                echo "<strong>Commit Hash</strong>: $commit_hash<br />";
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

                $test_result_json = read_flat_file($test_result_file);

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

    function json_error_and_exit($error_msg) {
        echo json_encode(array('status' => 'failed', 'error' => $error_msg));
        exit();
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

?>