<?php

    function handle_page_home_get($uri_parts, $uri_args) {

        $repos = query('SELECT * FROM repo');
        echo '<table><tr><th>Name</th><th>URL</th><th>Download Location</th></tr>';
        foreach($repos as $repo) {
            $name = $repo['name'];
            $url = $repo['url'];
            $download_location = $repo['download_location'];

            echo "<tr><td><a href='/repo/$name'>$name</a></td><td><a href='$url'>$url</a></td><td>$download_location</td></tr>";
        }
        echo '</table>';

        if(is_worker_running()) {
            echo '<br /><strong>Worker is running</strong>';
        } else {
            echo '<br /><strong>Worker is not running</strong>';
        }

    }

?>