<?php

    function test_setup_1($properties) {
        $response = test_curl($properties['endpoint_url'] . '/api/v1/commit', [], false);
        assertEquals(200, $response['http_code'], 'http code mismatch');
        $html_response = $response['response'];

        $required_tables = array('commit', 'repo', 'global_setting', 'repo_setting');

        foreach($required_tables as $table) {
            assertStrContains("Trying to create required table $table...", $html_response);
            assertStrContains("Created table $table", $html_response);
        }

        echo "Concluded testing setup\n";
    }

    function test_api_v1_commit_get($properties) {
        $response = test_curl($properties['endpoint_url'] . '/api/v1/commit', [], false);
        assertEquals(200, $response['http_code'], 'http code mismatch');

        assertTrue($response['response'] === '[]', '/api/v1/commit response length is not an empty array');

        echo "Concluded testing GET /api/v1/commit\n";
    }

?>