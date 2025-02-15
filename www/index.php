<?php

    include 'sql_helper.php';

    $tsuite_conn = get_database_connection('localhost', 'tsuite_admin', 'password', 'tsuite');

    $request_method = $_SERVER['REQUEST_METHOD']; // GET, POST, PUT, DELETE...
    $request_uri = '/' . ltrim(rtrim($_SERVER['REQUEST_URI'], '/'), '/'); // /example?foo=bar1&foo=bar2 => /example?foo=bar2
    $request_is_https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') == true; // false

    $uri_path = explode('?', $request_uri)[0]; // /example
    $uri_query = explode('?', $request_uri)[1]; //foo=bar1&foo=bar2

    $uri_parts = explode('/', ltrim($uri_path, '/')); // ['', 'example']

    $uri_args = array();

    foreach (explode('&', $uri_query) as $arg) {
        $key = explode('=', $arg)[0];
        $value = explode('=', $arg)[1];
        $uri_args[$key] = $value;
    }

    $debug == false;

    if($uri_args['debug'] == 'true') {
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

                $commits = query('SELECT * FROM commits');
                echo json_encode($commits);
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

                    $insert_query = "INSERT INTO commits (repo, commit_hash, date, message, author) VALUES (:repo, :commit_hash, :date, :message, :author)";

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
                        'author' => $author
                    );

                    query($insert_query, $insert_vals);

                    $counter++;
                }
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
    }

    if($uri_parts[0] == 'repos') {
        if(isset($uri_parts[1]) && $uri_parts[0] !== '') {
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
            echo "<strong>Recent commits</strong>:<br /><br />";

            $commits = query('SELECT * FROM commits WHERE repo = :id', array('id' => $id));
            echo '<table><tr><th>date</th><th>commit_hash</th><th>message</th><th>author</th></tr>';
            foreach($commits as $commit) {

                $date = $commit['date'];
                $commit_hash = $commit['commit_hash'];
                $message = $commit['message'];
                $author = $commit['author'];
    
                echo "<tr><td>$date</td><td>$commit_hash</td><td>$message</td><td>$author</td></tr>";
            }
            echo '</table>';

        }
    }

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