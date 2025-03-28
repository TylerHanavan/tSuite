<?php

    /**
     * TODO
     * Test POST name/value instead of setting_name/setting_value
     * Test GET with query parameters (needs to be implemented in api handler)
     * TEST PUT 
     */
    function test_repo_setting_post_1($properties) {
        $uri = "/api/v1/repo_setting";

        // Test missing payload
        $response = test_curl($properties['endpoint_url'] . "/$uri", [], true);
        assertEquals(400, $response['http_code'], "$uri http code mismatch");
        assertEquals('{"status":"failed","error":"Empty payload"}', $response['response'], "$uri did not trigger error for empty payload");

        // Test missing setting_value
        $response = test_curl($properties['endpoint_url'] . "/$uri", ['setting_name' => 'test'], true);
        assertEquals(400, $response['http_code'], "$uri http code mismatch");
        assertEquals('{"status":"failed","error":"Missing setting_value"}', $response['response'], "$uri did not trigger error for missing setting_value");

        // Test missing setting_name
        $response = test_curl($properties['endpoint_url'] . "/$uri", ['setting_value' => 'test'], true);
        assertEquals(400, $response['http_code'], "$uri http code mismatch");
        assertEquals('{"status":"failed","error":"Missing setting_name"}', $response['response'], "$uri did not trigger error for missing setting_name");

        // Test missing repo_id
        $response = test_curl($properties['endpoint_url'] . "/$uri", ['setting_value' => 'test', 'setting_name' => 'test1'], true);
        assertEquals(400, $response['http_code'], "$uri http code mismatch");
        assertEquals('{"status":"failed","error":"Missing repo_id"}', $response['response'], "$uri did not trigger error for missing repo_id");

        // Test bad repo_id (not exists in repo table)
        $response = test_curl($properties['endpoint_url'] . "/$uri", ['setting_value' => 'test', 'setting_name' => 'test1', 'repo_id' => 3], true);
        assertEquals(400, $response['http_code'], "$uri http code mismatch");
        assertEquals('{"status":"failed","error":"repo_id does not exist"}', $response['response'], "$uri did not trigger error for bad repo_id");
        
        echo "Concluded testing POST $uri (errored first run)\n";

        $response = test_curl($properties['endpoint_url'] . "/$uri", ['setting_value' => 'test', 'setting_name' => 'test1', 'repo_id' => 2], true);
        assertEquals(200, $response['http_code'], "$uri http code mismatch");
        assertEquals('[{"id":1,"repo_id":2,"name":"test1","value":"test"}]', $response['response'], "$uri did not get inserted");

        echo "Concluded testing POST $uri (successful second run)\n";
    }

    function test_repo_setting_get_1($properties) {
        $uri = '/api/v1/repo_setting';

        $response = test_curl($properties['endpoint_url'] . "/$uri", [], false);
        assertEquals(200, $response['http_code'], "$uri http code mismatch");
        assertEquals('[{"id":1,"repo_id":2,"name":"test1","value":"test"}]', $response['response'], "$uri did not return the right entity");

        echo "Concluded testing GET $uri (successful first run)\n";
    }

    function test_repo_setting_post_2($properties) {
        $uri = '/api/v1/repo_setting';

        $response = test_curl($properties['endpoint_url'] . "/$uri", ['setting_value' => 'test', 'setting_name' => 'test1', 'repo_id' => 2], true);
        assertEquals(400, $response['http_code'], "$uri http code mismatch");
        assertEquals('{"status":"failed","error":"repo_setting already exists for repo"}', $response['response'], "$uri did not trigger error for repo_setting already exists");
        
        echo "Concluded testing POST $uri (errored third run)\n";

        $response = test_curl($properties['endpoint_url'] . "/$uri", ['setting_value' => 'jest', 'setting_name' => 'test2', 'repo_id' => 2], true);
        assertEquals(200, $response['http_code'], "$uri http code mismatch");
        assertEquals('[{"id":2,"repo_id":2,"name":"test2","value":"jest"}]', $response['response'], "$uri did not get inserted");

        $response = test_curl($properties['endpoint_url'] . "/$uri", ['setting_value' => 'TENten12!@#$%^&*()-_+={[}];:\'",<.>/?\\|~`1234567890', 'setting_name' => 'Alpha_test-3', 'repo_id' => 1], true);
        assertEquals(200, $response['http_code'], "$uri http code mismatch");
        assertEquals('[{"id":3,"repo_id":1,"name":"Alpha_test-3","value":"TENten12!@#$%^&*()-_+={[}];:\'\",<.>\/?\\\|~`1234567890"}]', $response['response'], "$uri did not get inserted");

        echo "Concluded testing POST $uri (successful fourth run)\n";
    }

    function test_repo_setting_get_2($properties) {
        $uri = '/api/v1/repo_setting';

        $response = test_curl($properties['endpoint_url'] . "/$uri", [], false);
        assertEquals(200, $response['http_code'], "$uri http code mismatch");
        assertEquals('[{"id":1,"repo_id":2,"name":"test1","value":"test"},{"id":2,"repo_id":2,"name":"test2","value":"jest"},{"id":3,"repo_id":1,"name":"Alpha_test-3","value":"TENten12!@#$%^&*()-_+={[}];:\'\",<.>\/?\\\|~`1234567890"}]', $response['response'], "$uri did not return the right entity");

        echo "Concluded testing GET $uri (successful second run)\n";
    }

?>