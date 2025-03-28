<?php

    /** TODO:
     * Multiple commits in single POST
     * Test PUT
     * Test GET with query parameters (needs to be implemented in api handler)
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
        $entity1 = commit_testing_array_add_field_and_test_missing_field_response($properties, $uri, $entity1, 'date', '2025-03-27 01:02:03', '{"status":"failed","error":"No message provided for entity 0"}');
        $entity1 = commit_testing_array_add_field_and_test_missing_field_response($properties, $uri, $entity1, 'message', 'test commit', '{"status":"failed","error":"No author provided for entity 0"}');

        echo "Concluded testing POST $uri (errored first run)\n";

        $entity1['author'] = 'developer';

        $entities = [];
        $entities[] = $entity1;

        $response = test_curl($properties['endpoint_url'] . "/$uri", $entities, true);
        assertEquals(200, $response['http_code'], "$uri http code mismatch");
        assertEquals('{"success":[{"position":0,"entity":{"repo_id":"1","hash":"abcdefghi1234567","branch":"dev","date":"2025-03-27 01:02:03","message":"test commit","author":"developer"},"result":"1"}],"status":"success"}', $response['response'], "$uri did not trigger success for successful insert");

        echo "Concluded testing POST $uri (successful second run)\n";
    }

    function test_commit_get_1($properties) {
        $uri = '/api/v1/commit';

        $response = test_curl($properties['endpoint_url'] . "/$uri", [], false);
        assertEquals(200, $response['http_code'], "$uri http code mismatch");
        assertEquals('[{"id":1,"repo_id":1,"hash":"abcdefghi1234567","branch":"dev","date":"2025-03-27 01:02:03","author":"developer","message":"test commit","test_status":null,"success_tests":null,"failed_tests":null,"download_duration":-1,"install_duration":-1,"test_duration":-1,"do_retest_flag":0}]', $response['response'], "$uri did not trigger success for successful insert");

        echo "Concluded testing GET $uri (successful first run)\n";

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