<?php

    function handle_api_repo_setting_get($uri_parts, $uri_args) {
        //TODO: Add arguments
        $repos = query('SELECT * FROM repo_setting');
        echo json_encode($repos);
        exit();
    }

    function handle_api_repo_setting_post($uri_parts, $uri_args) {
        $payload = $_POST ?? null;
        if($payload == null || sizeof($payload) == 0) {
            json_error_and_exit('Empty payload');
        }
        $setting_name = $payload['setting_name'] ?? null;
        $setting_value = $payload['setting_value'] ?? null;
        $repo_id = $payload['repo_id'] ?? null;
        if($setting_name == null || $setting_name == '') {
            json_error_and_exit('Missing setting_name');
        }
        if($setting_value == null || $setting_value == '') {
            json_error_and_exit('Missing setting_value');
        }
        if($repo_id == null || $repo_id == '') {
            json_error_and_exit('Missing repo_id');
        }
        $check_query = 'SELECT * FROM repo WHERE id = :repo_id';
        $check_params = array('repo_id' => $repo_id);
        $check_result = query($check_query, $check_params);
        if(!$check_result) {
            json_error_and_exit('repo_id does not exist');
        }
        $check_query = 'SELECT * FROM repo_setting WHERE name = :setting_name AND repo_id = :repo_id';
        $check_params = array('setting_name' => $setting_name, 'repo_id' => $repo_id);
        $check_result = query($check_query, $check_params);
        if($check_result) {
            json_error_and_exit('repo_setting already exists for repo');
        }
        $query = 'INSERT INTO repo_setting (name, value, repo_id) VALUES (:setting_name, :setting_value, :repo_id)';
        $params = array('setting_name' => $setting_name, 'setting_value' => $setting_value, 'repo_id' => $repo_id);
        query($query, $params);
        $new_setting = query($check_query, $check_params);
        echo json_encode($new_setting);
        exit();
    }

    function handle_api_repo_setting_put($uri_parts, $uri_args) {
        $payload = null;
        parse_str(file_get_contents("php://input"), $payload);
        if($payload == null || sizeof($payload) == 0) {
            json_error_and_exit('Empty payload');
        }
        $setting_name = $payload['setting_name'] ?? null;
        $setting_value = $payload['setting_value'] ?? null;
        $repo_id = $payload['repo_id'] ?? null;
        if($setting_name == null || $setting_name == '') {
            json_error_and_exit('Missing setting_name');
        }
        if($setting_value == null || $setting_value == '') {
            json_error_and_exit('Missing setting_value');
        }
        if($repo_id == null || $repo_id == '') {
            json_error_and_exit('Missing repo_id');
        }
        $check_query = 'SELECT * FROM repo WHERE id = :repo_id';
        $check_params = array('repo_id' => $repo_id);
        $check_result = query($check_query, $check_params);
        if(!$check_result) {
            json_error_and_exit('repo_id does not exist');
        }
        $check_query = 'SELECT * FROM repo_setting WHERE name = :setting_name AND repo_id = :repo_id';
        $check_params = array('setting_name' => $setting_name, 'repo_id' => $repo_id);
        $check_result = query($check_query, $check_params);
        if($check_result) {
            $query = 'UPDATE repo_setting SET value = :setting_value WHERE name = :setting_name AND repo_id = :repo_id';
            $params = array('setting_name' => $setting_name, 'setting_value' => $setting_value, 'repo_id' => $repo_id); 
            query($query, $params);
            $new_setting = query($check_query, $check_params);
            echo json_encode($new_setting);
            exit();
        }
        $query = 'INSERT INTO repo_setting (name, value, repo_id) VALUES (:setting_name, :setting_value, :repo_id)';
        $params = array('setting_name' => $setting_name, 'setting_value' => $setting_value, 'repo_id' => $repo_id);
        query($query, $params);
        $new_setting = query($check_query, $check_params);
        echo json_encode($new_setting);
        exit();
    }

?>