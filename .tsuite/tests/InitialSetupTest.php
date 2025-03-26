<?php

    function test_setup_1($properties) {
        $response = test_curl($properties['endpoint_url'] . '/whatever', [], true);
        assertEquals(200, $response['http_code'], 'http code mismatch');
        $html_response = $response['response'];

        $required_tables = array('commit', 'repo', 'global_setting', 'repo_setting');

        foreach($required_tables as $table) {
            assertStrContains("Trying to create required table $table...", $html_response);
            assertStrContains("Created table $table", $html_response);
        }
    }

?>