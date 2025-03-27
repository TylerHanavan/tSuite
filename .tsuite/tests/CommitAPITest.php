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

        $entities = [];

        $entity1['repo_id'] = 1;

        $entities[] = $entity1;

        $response = test_curl($properties['endpoint_url'] . "/$uri", $entities, true);
        assertEquals(400, $response['http_code'], "$uri http code mismatch");
        assertEquals('{"status":"failed","error":"No hash provided for entity 0"}', $response['response'], "$uri did not trigger error for empty payload");

        $entity1 = commit_testing_array_add_field_and_test_missing_field_response($properties, $uri, $entity1, 'hash', 'abcdefghi1234567', '{"status":"failed","error":"No branch provided for entity 0"}');
        $entity1 = commit_testing_array_add_field_and_test_missing_field_response($properties, $uri, $entity1, 'branch', 'dev', '{"status":"failed","error":"No date provided for entity 0"}');
        $entity1 = commit_testing_array_add_field_and_test_missing_field_response($properties, $date, $entity1, 'date', '2025-03-27 01:02:03', '{"status":"failed","error":"No message provided for entity 0"}');

        echo "Concluded testing POST $uri (errored second run)\n";
    }

    #[NotATest]
    function commit_testing_array_add_field_and_test_missing_field_response($properties, $uri, $array, $key, $value, $expected_response) {

        $array[$key] = $value;

        $entities = [];

        $entities[] = $array;

        $response = test_curl($properties['endpoint_url'] . "/$uri", $entities, true);
        assertEquals(400, $response['http_code'], "$uri http code mismatch");
        assertEquals($response['response'], $expected_response, "$uri did not trigger error for missing $key");

        return $array;
    }

?>