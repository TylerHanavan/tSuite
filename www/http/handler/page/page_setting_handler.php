<?php

    function handle_page_setting_get($repo, $uri_parts, $uri_args) {
        $check_query = 'SELECT * FROM repo WHERE name = :name';
        $check_params = array('name' => $repo);
        $check_result = query($check_query, $check_params);
        if(!$check_result) {
            html_error_and_exit('Repo not found', 404);
        }
        $repo_props = $check_result[0];
        echo "<h1>Page Settings for <a href='/repo/$repo'>$repo</a></h1>";

        $query = 'SELECT * FROM setting WHERE repo_id = :repo_id';
        $params = array('repo_id' => $repo_props['id']);
        $result = query($query, $params);
        if($result && sizeof($result) > 0) {
            echo "<table>";
            echo "<tr><th>Setting</th><th>Value</th></tr>";
            foreach($result as $row) {
                echo "<tr><td>{$row['name']}</td><td>{$row['value']}</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No settings found.</p>";
        }

        exit();
    }

?>