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

        $insert_query = "INSERT INTO repo (name, url, download_location) VALUES (:name, :url, :download_location)";

        $insert_vals = array(
            'name' => $entity['name'],
            'url' => $entity['url'],
            'download_location' => $entity['download_location'],
        );

        $result = query($insert_query, $insert_vals);

        if($result === 1) {
            $response['status'] = 'success';
        } else {
            $response['status'] = 'failure';
            $response['reason'] = 'Unable to insert';
        }

        echo json_encode($response);
        exit();
    }

?>