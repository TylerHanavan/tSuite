<?php

    function test_api_v1_commit_get_1($properties) {
        $uri = '/api/v1/commit';
        $response = test_curl($properties['endpoint_url'] . "/$uri", [], false);
        assertEquals(200, $response['http_code'], "$uri http code mismatch");

        assertTrue($response['response'] === '[]', "$uri response length is not an empty array");

        echo "Concluded testing GET $uri (first run)\n";
    }

    function test_api_v1_repo_get_1($properties) {
        $uri = '/api/v1/repo';
        $response = test_curl($properties['endpoint_url'] . "/$uri", [], false);
        assertEquals(200, $response['http_code'], "$uri http code mismatch");

        assertTrue($response['response'] === '[]', "$uri response length is not an empty array");

        echo "Concluded testing GET $uri (first run)\n";
    }

    function test_api_v1_repo_setting_get_1($properties) {
        $uri = '/api/v1/repo_setting';
        $response = test_curl($properties['endpoint_url'] . "/$uri", [], false);
        assertEquals(200, $response['http_code'], "$uri http code mismatch");

        assertTrue($response['response'] === '[]', "$uri response length is not an empty array");

        echo "Concluded testing GET $uri (first run)\n";
    }

    function test_api_v1_global_setting_get_1($properties) {
        $uri = '/api/v1/global_setting';
        $response = test_curl($properties['endpoint_url'] . "/$uri", [], false);
        assertEquals(200, $response['http_code'], "$uri http code mismatch");

        assertTrue($response['response'] === '[]', "$uri response length is not an empty array");

        echo "Concluded testing GET $uri (first run)\n";
    }

?>