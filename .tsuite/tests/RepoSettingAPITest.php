<?php

    /**
     * TODO
     * Test name/value instead of setting_name/setting_value
     */
    function test_repo_setting_post_1($properties) {
        $uri = "/api/v1/repo_setting";

        // Test missing payload
        $response = test_curl($properties['endpoint_url'] . "/$uri", [], true);
        assertEquals(400, $response['http_code'], "$uri http code mismatch");
        assertEquals($response['response'], '{"status":"failed","error":"Empty payload"}', "$uri did not trigger error for empty payload");

        // Test missing setting_value
        $response = test_curl($properties['endpoint_url'] . "/$uri", ['setting_name' => 'test'], true);
        assertEquals(400, $response['http_code'], "$uri http code mismatch");
        assertEquals($response['response'], '{"status":"failed","error":"Missing setting_value"}', "$uri did not trigger error for missing setting_value");
    }

?>