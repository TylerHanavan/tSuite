<?php

    function handle_api_setting_get($uri_parts, $uri_args) {
        $repos = query('SELECT * FROM setting');
        echo json_encode($repos);
        exit();
    }

    function handle_api_setting_post($uri_parts, $uri_args) {
        $payload = $_POST ?? null;
        if($payload == null || sizeof($payload) == 0) {
            json_error_and_exit('Empty payload');
        }
        $setting_name = $payload['setting_name'] ?? null;
        $setting_value = $payload['setting_value'] ?? null;
        if($setting_name == null || $setting_name == '') {
            json_error_and_exit('Missing setting_name');
        }
        if($setting_value == null || $setting_value == '') {
            json_error_and_exit('Missing setting_value');
        }
        $check_query = 'SELECT * FROM setting WHERE name = :setting_name';
        $check_params = array('setting_name' => $setting_name);
        $check_result = query($check_query, $check_params);
        if($check_result) {
            json_error_and_exit('Setting already exists');
        }
        $query = 'INSERT INTO setting (name, value) VALUES (:setting_name, :setting_value)';
        $params = array('setting_name' => $setting_name, 'setting_value' => $setting_value);
        query($query, $params);
        $new_setting = query($check_query, $check_params);
        echo json_encode($new_setting);
        exit();
    }

    function handle_api_setting_put($uri_parts, $uri_args) {
        $payload = null;
        parse_str(file_get_contents("php://input"), $payload);
        if($payload == null || sizeof($payload) == 0) {
            json_error_and_exit('Empty payload');
        }
        $setting_name = $payload['setting_name'] ?? null;
        $setting_value = $payload['setting_value'] ?? null;
        if($setting_name == null || $setting_name == '') {
            json_error_and_exit('Missing setting_name');
        }
        if($setting_value == null || $setting_value == '') {
            json_error_and_exit('Missing setting_value');
        }
        $check_query = 'SELECT * FROM setting WHERE name = :setting_name';
        $check_params = array('setting_name' => $setting_name);
        $check_result = query($check_query, $check_params);
        if($check_result) {
            $query = 'UPDATE setting SET value = :setting_value WHERE name = :setting_name';
            $params = array('setting_name' => $setting_name, 'setting_value' => $setting_value);
            query($query, $params);
            $new_setting = query($check_query, $check_params);
            echo json_encode($new_setting);
            exit();
        }
        $query = 'INSERT INTO setting (name, value) VALUES (:setting_name, :setting_value)';
        $params = array('setting_name' => $setting_name, 'setting_value' => $setting_value);
        query($query, $params);
        $new_setting = query($check_query, $check_params);
        echo json_encode($new_setting);
        exit();
    }

?>