<?php

    function handle_api_repo_get($uri_parts, $uri_args) {
        $repos = query('SELECT * FROM repo');
        echo json_encode($repos);
        exit();
    }

    function handle_api_repo_post($uri_parts, $uri_args) {

        $entity = $_POST ?? null;

        if($entity == null) {
            json_error_and_exit('No entity provided in payload');
        }

        if(!is_array($entity)) {
            json_error_and_exit('Provided payload is not an array');
        }

        if(count($entity) == 0) {
            json_error_and_exit("Provided payload is empty");
        }

        $required_fields = ['name', 'url', 'download_location'];

        foreach($required_fields as $field) {
            if(!isset($entity[$field])) {
                json_error_and_exit("Required field `$field` is not provided in the payload");
            }
            if($entity[$field] === '' || $entity[$field] == null) {
                json_error_and_exit("Required field `$field` is empty or null");
            }
        }

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

        echo json_encode($response);
        exit();
    }

?>