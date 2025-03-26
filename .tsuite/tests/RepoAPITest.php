<?php

    function test_repo_post_1($properties) {
        $uri = "/api/v1/repo";
        $response = test_curl($properties['endpoint_url'] . "/$uri", "test", false);
        assertEquals(400, $response['http_code'], "$uri http code mismatch");

        assertEquals($response['response'] === 'Provided payload is not an array', "$uri response length is not an empty array");

        echo "Concluded testing GET $uri (empty first run)\n";
    }

?>