<?php

    function handle_api_repo_get($uri_parts, $uri_args) {
        $repos = query('SELECT * FROM repo');
        echo json_encode($repos);
        exit();
    }

?>