<?php

    function handle_api_commit_get($uri_parts, $uri_args) {
        $do_retest_flag = $_GET['do_retest_flag'] ?? null;

        if(isset($_GET['commit_id']) && $_GET['commit_id'] != null) {
            $commit_id = $_GET['commit_id'];
            $query = 'SELECT * FROM commit WHERE id = :commit_id';
            $params = array('commit_id' => $commit_id);
            if($do_retest_flag != null) {
                $query .= ' AND do_retest_flag = :do_retest_flag';
                $params = array('commit_id' => $commit_id, 'do_retest_flag' => $do_retest_flag);
            }
            $commits = query($query, $params);
            echo json_encode($commits);
            exit();
        }
        if(isset($_GET['repo_id']) && $_GET['repo_id'] != null) {
            $repo_id = $_GET['repo_id'];
            $query = 'SELECT * FROM commit WHERE repo_id = :repo_id';
            $params = array('repo_id' => $repo_id);
            if($do_retest_flag != null) {
                $query .= ' AND do_retest_flag = :do_retest_flag';
                $params = array('repo_id' => $repo_id, 'do_retest_flag' => $do_retest_flag);
            }
            $commits = query($query, $params);
            echo json_encode($commits);
            exit();
        }

        if(isset($_GET['hash']) && $_GET['hash'] != null) {
            $hash = $_GET['hash'];
            $query = 'SELECT * FROM commit WHERE hash = :hash';
            $params = array('hash' => $hash);
            if($do_retest_flag != null) {
                $query .= ' AND do_retest_flag = :do_retest_flag';
                $params = array('hash' => $hash, 'do_retest_flag' => $do_retest_flag);
            }
            $commits = query($query, $params);
            echo json_encode($commits);
            exit();
        }

        $query = 'SELECT * FROM commit';
        $params = array();
        if($do_retest_flag != null) {
            $query .= ' WHERE do_retest_flag = :do_retest_flag';
            $params = array('do_retest_flag' => $do_retest_flag);
        }
        $commits = query($query, $params);
        echo json_encode($commits);
        exit();
    }

    function handle_api_commit_post($uri_parts, $uri_args) {
        $entities = $_POST ?? null;
        if($entities == null) {
            json_error_and_exit('Resources null or missing from payload');
        }
        if(sizeof($entities) == 0) {
            json_error_and_exit('Resources array exists but is empty');
        }
        $response = array();
        $successes = 0;
        $failures = 0;
        $counter = 0;
        
        foreach($entities as $entity) {
            $repo = $entity['repo_id'] ?? null;
            if($repo == null) {
                json_error_and_exit("No repo provided for entity $counter");
            }

            $commit_hash = $entity['hash'] ?? null;
            if($commit_hash == null) {
                json_error_and_exit("No hash provided for entity $counter");
            }

            $branch = $entity['branch'] ?? null;
            if($branch == null) {
                json_error_and_exit("No branch provided for entity $counter");
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

            $insert_query = "INSERT INTO commit (repo_id, hash, branch, date, message, author, test_status, success_tests, failed_tests, download_duration, install_duration, test_duration) VALUES (:repo_id, :hash, :branch, :date, :message, :author, :test_status, :success_tests, :failed_tests, :download_duration, :install_duration, :test_duration)";

            $insert_vals = array(
                'repo_id' => $repo,
                'hash' => $commit_hash,
                'branch' => $branch,
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

            if($result == null || $result === false) {
                $failures++;
                if(!isset($response['failure']))
                    $response['failure'] = array();
                $response['failure'][] = array('position' => $counter, 'entity' => $entity);
            } else {
                $successes++;
                if(!isset($response['success']))
                    $response['success'] = array();
                $response['success'][] = array('position' => $counter, 'entity' => $entity);
            }

            $counter++;
        }

        if($failures > 0) {
            $response['status'] = 'failure';
        } else {
            $response['status'] = 'success';
        }

        echo json_encode($response);
        exit();
    }

    function handle_api_commit_put($uri_parts, $uri_args) {
       $input = file_get_contents('php://input');

        if($input == null || $input == '') {
            json_error_and_exit('Invalid input');
        }

        $entity = json_decode($input, true);

        if($entity == null) {
            json_error_and_exit('Invalid JSON');
        }

        $commit_id = $entity['id'] ?? null;

        if($commit_id == null) {
            json_error_and_exit('No commit ID provided');
        }

        $do_retest_flag = $entity['do_retest_flag'] ?? null;
        
        $query = 'UPDATE commit SET do_retest_flag = :do_retest_flag WHERE id = :commit_id';
        $params = array('do_retest_flag' => $do_retest_flag, 'commit_id' => $commit_id);
        $result = query($query, $params);
        
        if($result == null || $result === false) {
            json_error_and_exit('Failed to update commit');
        }
        echo json_encode(array('status' => 'success'));
        exit();

    }

?>