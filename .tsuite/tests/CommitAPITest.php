<?php

    /** TODO:
     * Test null payload
     * Test string payload
     */

    function test_commit_post_1($properties) {
        $uri = "/api/v1/commit";

        $entities = [];

        // Test missing payload
        $response = test_curl($properties['endpoint_url'] . "/$uri", $entities, true);
        assertEquals(400, $response['http_code'], "$uri http code mismatch");
        assertEquals($response['response'], '{"status":"failed","error":"Resources null or missing from payload"}', "$uri did not trigger error for empty payload");

        $entity1 = [];
        $entities[] = $entity1;

        // Test missing payload
        $response = test_curl($properties['endpoint_url'] . "/$uri", $entities, true);
        assertEquals(400, $response['http_code'], "$uri http code mismatch");
        assertEquals($response['response'], '{"status":"failed","error":"Resources null or missing from payload"}', "$uri did not trigger error for empty payload");

        echo "Concluded testing POST $uri (errored second run)\n";
    }

?>