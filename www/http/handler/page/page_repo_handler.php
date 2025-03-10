<?php

    function handle_page_repo_get($repo, $uri_parts, $uri_args) {

        if(isset($uri_parts[1]) && sizeof($uri_parts) == 2) {
            $use_name = true;
            if(is_int($uri_parts[1])) {
                $use_name = true;
            }

            if($use_name) {
                $arr = query("SELECT * FROM repo WHERE name = :name", array('name' => $uri_parts[1]));
                if(sizeof($arr) == 0 || $arr == null) {
                    echo "Repo not found!<br />";
                    return;
                }
            } else {
                $arr = query("SELECT * FROM repo WHERE id = :id", array('id' => $uri_parts[1]));
                if(sizeof($arr) == 0 || $arr == null) {
                    echo "Repo not found!<br />";
                    return;
                }
            }

            $repo = $arr[0];

            $id = $repo['id'];
            $name = $repo['name'];
            $url = $repo['url'];
            $download_location = $repo['download_location'];

            add_additional_navbar(array('title' => $name, 'elements' => array()));

            echo "<strong>Repo</strong>: <a href='/repo/$name'>$name</a><br />";
            echo "<strong>Repo URL</strong>: <a href='$url'>$url</a><br />";
            echo "<strong>Repo Settings URL</strong>: <a href='/settings/repo/$name'>Repo Settings</a><br />";
            echo "<strong>Download Location</strong>: $download_location<br />";
            echo "<br /><strong>Recent commits</strong>:<br />";

            $commits = query('SELECT * FROM commit WHERE repo_id = :id ORDER BY id DESC LIMIT 25', array('id' => $id));
            echo '<table style="width:100%;border-collapse:collapse;margin-top:10px"><tr><th>date</th><th>hash</th><th>branch</th><th>message</th><th>author</th><th>Test Status</th><th>Tests Passing</th><th>Tests Failing</th><th>Download Duration</th><th>Install Duration</th><th>Test Duration</th><th>Total Duration</th><th>Actions</th></tr>';
            foreach($commits as $commit) {

                $id = $commit['id'];
                $date = $commit['date'];
                $commit_hash = $commit['hash'];
                $short_commit_hash = substr($commit_hash, 0, 7);

                $message = $commit['message'];
                $author = $commit['author'];
                $branch = $commit['branch'];
                $test_status = $commit['test_status'] ?? null;
                $success_tests = $commit['success_tests'] ?? 'Unknown';
                $failed_tests = $commit['failed_tests'] ?? 'Unknown';
                $download_duration = $commit['download_duration'] ?? 'Unknown';
                $install_duration = $commit['install_duration'] ?? 'Unknown';
                $test_duration = $commit['test_duration'] ?? 'Unknown';
                $total_duration = 0;

                if($download_duration != 'Unknown') {
                    $total_duration += $download_duration;
                }
                if($install_duration != 'Unknown') {
                    $total_duration += $install_duration;
                }
                if($test_duration != 'Unknown') {
                    $total_duration += $test_duration;
                }

                if($total_duration == 0) {
                    $total_duration = 'Unknown';
                }

                $download_duration = format_milliseconds($download_duration);
                $install_duration = format_milliseconds($install_duration);
                $test_duration = format_milliseconds($test_duration);
                $total_duration = format_milliseconds($total_duration);

                if($test_status === null) {
                    $test_status = 'N/A';
                }

                if($test_status == 0) $test_status = 'Passed';
                if($test_status == 1) $test_status = 'Failed';
                if($test_status == 2) $test_status = 'Could not load testbook';

                $test_status_td = '<td>';

                if($test_status == 'Passed') {
                    $test_status_td = '<td style="background-color:#d4edda;color:#155724;font-weight:bold">';
                }
                if($test_status == 'Failed' || $test_status == 'Could not load testbook') {
                    $test_status_td = '<td style="background-color:#ffebeb;color:#d00;font-weight:bold">';
                }
    
                echo "<tr><td>$date</td><td><a href='/repo/$name/commit/$commit_hash'>$short_commit_hash</a></td><td>$branch</td><td>$message</td><td>$author</td>$test_status_td$test_status</td><td>$success_tests</td><td>$failed_tests</td><td>$download_duration</td><td>$install_duration</td><td>$test_duration</td><td>$total_duration</td><td><p><button class='btn-success commit-retest' commit-id='$id'>Retest</button></p></td></tr>";
            }
            echo '</table>';

        }
        
    }

    function handle_page_repo_commit_get($repo, $hash, $uri_parts, $uri_args) {
        if(isset($uri_parts[1]) && sizeof($uri_parts) == 4) {
            // /repos/REPO_NAME/commit/COMMIT_HASH

            if($uri_parts[2] == 'commit') {
                $repo_name = $uri_parts[1];
                $commit_hash = $uri_parts[3];

                $repo = query('SELECT * FROM repo WHERE name = :name', array('name' => $repo_name));
                if(sizeof($repo) == 0) {
                    echo "Repo not found!<br />";
                    return;
                }

                $repo = $repo[0];
                $repo_id = $repo['id'];
                $repo_download_path = $repo['download_location'];

                $commit = query('SELECT * FROM commit WHERE repo_id = :repo_id AND hash = :hash ORDER BY test_status DESC', array('repo_id' => $repo_id, 'hash' => $commit_hash));
                if(sizeof($commit) == 0) {
                    echo "Commit not found!<br />";
                    return;
                }

                $repo_settings = query('SELECT * FROM repo_setting WHERE repo_id = :repo_id', array('repo_id' => $repo_id));

                $test_result_location = null;

                foreach($repo_settings as $setting) {
                    if($setting['name'] == 'TEST_RESULT_LOCATION') {
                        $test_result_location = $setting['value'];
                    }
                }

                $commit = $commit[0];

                $date = $commit['date'];
                $commit_hash = $commit['hash'];

                $message = $commit['message'];
                $author = $commit['author'];
                $test_status = $commit['test_status'] ?? null;
                $success_tests = $commit['success_tests'] ?? 'Unknown';
                $failed_tests = $commit['failed_tests'] ?? 'Unknown';
                $download_duration = $commit['download_duration'] ?? 'Unknown';
                $install_duration = $commit['install_duration'] ?? 'Unknown';
                $test_duration = $commit['test_duration'] ?? 'Unknown';
                $total_duration = 0;

                if($download_duration != 'Unknown') {
                    $total_duration += $download_duration;
                }
                if($install_duration != 'Unknown') {
                    $total_duration += $install_duration;
                }
                if($test_duration != 'Unknown') {
                    $total_duration += $test_duration;
                }

                if($total_duration == 0) {
                    $total_duration = 'Unknown';
                }

                $download_duration = format_milliseconds($download_duration);
                $install_duration = format_milliseconds($install_duration);
                $test_duration = format_milliseconds($test_duration);
                $total_duration = format_milliseconds($total_duration);

                if($test_status === null) {
                    $test_status = 'N/A';
                }

                if($test_status == 0) $test_status = 'Passed';
                if($test_status == 1) $test_status = 'Failed';
                if($test_status == 2) $test_status = 'Could not load testbook';

                echo "<strong>Repo</strong>: <a href='/repo/$repo_name'>$repo_name</a><br />";
                echo "<strong>Hash</strong>: $commit_hash<br />";
                echo "<strong>Date</strong>: $date<br />";
                echo "<strong>Message</strong>: $message<br />";
                echo "<strong>Author</strong>: $author<br />";
                echo "<strong>Test Status</strong>: $test_status<br />";
                echo "<strong>Tests Passing</strong>: $success_tests<br />";
                echo "<strong>Tests Failing</strong>: $failed_tests<br />";
                echo "<strong>Download Duration</strong>: $download_duration<br />";
                echo "<strong>Install Duration</strong>: $install_duration<br />";
                echo "<strong>Test Duration</strong>: $test_duration<br />";
                echo "<strong>Total Duration</strong>: $total_duration<br />";

                $test_result_file = $test_result_location . "/$commit_hash.json";

                try {
                    $test_result_json = read_flat_file($test_result_file);
                } catch(Exception $e) {
                    echo "Test results not found!<br />";
                    return;
                }
                $test_result = json_decode($test_result_json, true);

                $files = $test_result['files'];

                echo "<br /><strong>Parsed Test Results</strong>:<br /><br />";

                echo '<table style="width:100%;border-collapse:collapse;margin-top:10px"><tr><th>File</th><th>Test Name</th><th>Status</th><th class="reason-th">Reason</th></tr>';

                foreach($files as $file_name => $file_data) {
                    if(!isset($file_data['tests'])) continue;
                    foreach($file_data['tests'] as $function => $data) {
                        $status = $data['status'];
                        $reason = $data['reason'] ?? '';

                        if($status == 'success') {
                            $status = 'Passed';
                        } 
                        if($status == 'failure') {
                            $status = 'Failed';
                        }
                        
                        $test_status_td = $status == 'Passed' ? '<td style="background-color:#d4edda;color:#155724;font-weight:bold">' : '<td style="background-color:#ffebeb;color:#d00;font-weight:bold">';

                        echo "<tr><td>$file_name</td><td>$function</td>$test_status_td$status</td><td class=\"reason-td\">$reason</td></tr>";
                    }
                }
                
                echo '</table>';

                echo '<br /><strong>Stage Output</strong><br />';

                $stages = $test_result['stages'];

                $stage_output = '';

                foreach($stages as $stage => $stage_data) {
                    if(!isset($stage_data['output'])) continue;
                    $stage_output .= "<strong>$stage</strong><br />";
                    foreach($stage_data['output'] as $output_line) {
                        $stage_output .= nl2br($output_line);
                    }
                }

                echo "<pre class='json-pretty-print'>$stage_output</pre>";

                echo '<br /><strong>Pretty-Print Test Results (JSON)</strong><br />';

                $json_pretty_print = json_encode(json_decode($test_result_json), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

                echo "<pre class='json-pretty-print'>$json_pretty_print</pre>";

                echo '<br /><strong>Raw Test Results (JSON)</strong><br />';

                echo "<p>$test_result_json</p>";

            }
        }
    }

?>