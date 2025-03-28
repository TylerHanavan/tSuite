<?php

    /** TODO:
     * Test null payload
     * Test string payload
     */

    function test_repo_post_1($properties) {
        $uri = "/api/v1/repo";

        // Test missing payload
        $response = test_curl($properties['endpoint_url'] . "/$uri", [], true);
        assertEquals(400, $response['http_code'], "$uri http code mismatch");
        assertEquals($response['response'], '{"status":"failed","error":"No entity provided in payload"}', "$uri did not trigger error for empty payload");

        // Test missing fields
        $response = test_curl($properties['endpoint_url'] . "/$uri", ['name' => 'test'], true);
        assertEquals(400, $response['http_code'], "$uri http code mismatch");
        assertEquals($response['response'], '{"status":"failed","error":"Required field `url` is not provided in the payload"}', "$uri did not trigger error for empty payload");

        $response = test_curl($properties['endpoint_url'] . "/$uri", ['url' => 'test'], true);
        assertEquals(400, $response['http_code'], "$uri http code mismatch");
        assertEquals($response['response'], '{"status":"failed","error":"Required field `name` is not provided in the payload"}', "$uri did not trigger error for empty payload");

        $response = test_curl($properties['endpoint_url'] . "/$uri", ['download_location' => 'test'], true);
        assertEquals(400, $response['http_code'], "$uri http code mismatch");
        assertEquals($response['response'], '{"status":"failed","error":"Required field `name` is not provided in the payload"}', "$uri did not trigger error for empty payload");

        $response = test_curl($properties['endpoint_url'] . "/$uri", ['name' => 'test', 'url' => 'test'], true);
        assertEquals(400, $response['http_code'], "$uri http code mismatch");
        assertEquals($response['response'], '{"status":"failed","error":"Required field `download_location` is not provided in the payload"}', "$uri did not trigger error for empty payload");

        $response = test_curl($properties['endpoint_url'] . "/$uri", ['download_location' => 'test', 'url' => 'test'], true);
        assertEquals(400, $response['http_code'], "$uri http code mismatch");
        assertEquals($response['response'], '{"status":"failed","error":"Required field `name` is not provided in the payload"}', "$uri did not trigger error for empty payload");

        $response = test_curl($properties['endpoint_url'] . "/$uri", ['name' => 'test', 'download_location' => 'test'], true);
        assertEquals(400, $response['http_code'], "$uri http code mismatch");
        assertEquals($response['response'], '{"status":"failed","error":"Required field `url` is not provided in the payload"}', "$uri did not trigger error for empty payload");

        // Test empty fields
        $response = test_curl($properties['endpoint_url'] . "/$uri", ['name' => '', 'download_location' => 'test', 'url' => 'test'], true);
        assertEquals(400, $response['http_code'], "$uri http code mismatch");
        assertEquals($response['response'], '{"status":"failed","error":"Required field `name` is empty or null"}', "$uri did not trigger error for empty payload");
        
        $response = test_curl($properties['endpoint_url'] . "/$uri", ['name' => 'test', 'download_location' => '', 'url' => 'test'], true);
        assertEquals(400, $response['http_code'], "$uri http code mismatch");
        assertEquals($response['response'], '{"status":"failed","error":"Required field `download_location` is empty or null"}', "$uri did not trigger error for empty payload");
        
        $response = test_curl($properties['endpoint_url'] . "/$uri", ['name' => 'test', 'download_location' => 'test', 'url' => ''], true);
        assertEquals(400, $response['http_code'], "$uri http code mismatch");
        assertEquals($response['response'], '{"status":"failed","error":"Required field `url` is empty or null"}', "$uri did not trigger error for empty payload");

        echo "Concluded testing POST $uri (errored second run)\n";
    }

    function test_repo_post_2($properties) {
        $uri = "/api/v1/repo";

        $response = test_curl($properties['endpoint_url'] . "/$uri", ['name' => 'tSuite', 'download_location' => '/opt/tsuite/downloads/tSuite', 'url' => 'example.com'], true);
        assertEquals(200, $response['http_code'], "$uri http code mismatch");
        assertEquals('{"status":"success"}', $response['response'], "$uri did not return success response on success");

        $response = test_curl($properties['endpoint_url'] . "/$uri", [], false);
        assertEquals(200, $response['http_code'], "$uri http code mismatch");
        assertEquals('[{"id":1,"name":"tSuite","url":"example.com","download_location":"\/opt\/tsuite\/downloads\/tSuite"}]', $response['response'], "$uri did not return 1 repo");

        $response = test_curl($properties['endpoint_url'] . "/$uri", ['name' => 'vRec', 'download_location' => '/opt/tsuite/downloads/vRec', 'url' => 'vrec.com'], true);
        assertEquals(200, $response['http_code'], "$uri http code mismatch");
        assertEquals('{"status":"success"}', $response['response'], "$uri did not return success response on success");

        $response = test_curl($properties['endpoint_url'] . "/$uri", [], false);
        assertEquals(200, $response['http_code'], "$uri http code mismatch");
        assertEquals('[{"id":1,"name":"tSuite","url":"example.com","download_location":"\/opt\/tsuite\/downloads\/tSuite"},{"id":2,"name":"vRec","url":"vrec.com","download_location":"\/opt\/tsuite\/downloads\/vRec"}]', $response['response'], "$uri did not return 2 repos");

        echo "Concluded testing POST $uri (successful third run)\n";
    }

?>