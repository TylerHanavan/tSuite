<?php

    /** TODO:
     * Test null payload
     * Test string payload
     */

    function test_repo_post_1($properties) {
        $uri = "/api/v1/repo";

        $response = test_curl($properties['endpoint_url'] . "/$uri", [], true);
        assertEquals(400, $response['http_code'], "$uri http code mismatch");
        assertEquals($response['response'], '{"status":"failed","error":"No entity provided in payload"}', "$uri did not trigger error for empty payload");

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

        echo "Concluded testing POST $uri (errored second run)\n";
    }

?>