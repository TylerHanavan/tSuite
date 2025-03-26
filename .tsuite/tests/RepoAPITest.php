<?php

    /** TODO:
     * Test null payload
     * Test string payload
     */

    function test_repo_post_1($properties) {
        $uri = "/api/v1/repo";
        $response = test_curl($properties['endpoint_url'] . "/$uri", [], true);
        assertEquals(400, $response['http_code'], "$uri http code mismatch");

        assertEquals($response['response'] === 'Provided payload is empty', "$uri did not trigger error for empty payload");

        echo "Concluded testing POST $uri (errored second run)\n";
    }

?>