<?php

    function test_GET_resources_while_empty($properties) {
        $tables = ['commit', 'repo', 'repo_setting', 'global_setting'];
        foreach($tables as $table) {
            $uri = "/api/v1/$table";
            $response = test_curl($properties['endpoint_url'] . "/$uri", [], false);
            assertEquals(200, $response['http_code'], "$uri http code mismatch");
    
            assertTrue($response['response'] === '[]', "$uri response length is not an empty array");
    
            echo "Concluded testing GET $uri (empty first run)\n";
        }
    }

?>