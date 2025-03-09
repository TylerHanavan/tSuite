<?php

    include 'Tester.php';

    $repos = do_curl('/api/v1/repo', array(), false);
    if($repos == null || !isset($repos['response'])) {
        echo "No response data /api/repo\n";
        exit();
    }
    $repos_arr = json_decode($repos['response'], true);
    if($repos_arr == null || sizeof($repos_arr) == 0) {
        echo "No repos returned for /api/repo\n";
        exit();
    }
    foreach($repos_arr as $iter_repo) {

        $repo = $iter_repo['name'];
        $repo_url = $iter_repo['url'];
        $download_location = $iter_repo['download_location'];

        $test_location = $download_location . '/.tsuite';

        $repo_settings = do_curl('/api/v1/repo_setting', array('repo_id' => $iter_repo['id']), false);
        if($repo_settings == null || !isset($repo_settings['response'])) {
            echo "No response data /api/v1/repo_setting\n";
            continue;
        }

        $repo_settings_arr = json_decode($repo_settings['response'], true);

        $simplified_repo_settings = array();

        $simplified_repo_settings['DOWNLOAD_LOCATION'] = $download_location;

        foreach($repo_settings_arr as $setting) {
            $simplified_repo_settings[$setting['name']] = $setting['value'];
        }
        
        $branch = null;
        $PAT = null;
        $repo_user = null;
        $test_result_location = null;

        foreach($repo_settings_arr as $setting) {
            if($setting['name'] == 'BRANCH') {
                $branch = $setting['value'];
            } else if($setting['name'] == 'PAT') {
                $PAT = $setting['value'];
            } else if($setting['name'] == 'REPO_USER') {
                $repo_user = $setting['value'];
            } else if($setting['name'] == 'TEST_RESULT_LOCATION') {
                $test_result_location = $setting['value'];
            }
        }

        if($branch == null) {
            echo "Unable to get branch for $repo from /api/v1/repo_setting\n";
            continue;
        }

        if($PAT == null) {
            echo "Unable to get PAT for $repo from /api/v1/repo_setting\n";
            continue;
        }

        if($repo_user == null) {
            echo "Unable to get repo user for $repo from /api/v1/repo_setting\n";
            continue;
        }

        echo "($repo_user/$repo, Branch: $branch)\n";
        
        $git_metadata = pull_git_info($repo, $repo_user, $branch, $PAT);

        $git_metadata = json_decode($git_metadata['response'], true);

        if($git_metadata == null) {
            echo "Unable to pull git metadata\n";
            continue;
        }
        $commit_hash = $git_metadata['sha'] ?? null;
        if($commit_hash == null) {
            echo "Unable to get commit hash\n";
            var_dump($git_metadata);
            continue;
        }
        $commit_data = $git_metadata['commit'] ?? null;
        if($commit_data == null) {
            echo "Unable to get nested commit data\n";
            continue;
        }
        $message = $commit_data['message'] ?? null;
        if($message == null) {
            echo "Unable to get commit message\n";
            continue;
        }
        $commit_author_data = $commit_data['author'] ?? null;
        if($commit_author_data == null) {
            echo "Unable to get nested commit author data\n";
            continue;
        }
        $author = $commit_author_data['name'] ?? null;
        if($author == null) {
            echo "Unable to get commit author\n";
            continue;
        }
        
        echo "Checking if $commit_hash is new for $repo\n";
        if(is_commit_new($repo, $commit_hash)) {

            echo "New commit detected: $commit_hash\n";
            $start_time_download = get_current_time_milliseconds();
            do_git_pull($repo, $branch, $download_location, $repo_user, $PAT);
            $start_time_install = get_current_time_milliseconds();
            
            $testbook_properties = get_testbook_properties($test_location);

            $end_time_install = get_current_time_milliseconds();

            if($testbook_properties == null) {
                echo "Test failed because tSuite could not load testbook\n";
                post_commit($repo, $commit_hash, $message, $author, 2, 0, 0, $start_time_install - $start_time_download, $end_time_install - $start_time_install, ($start_time_install - $start_time_download) + ($end_time_install - $start_time_install));
                continue;
            }

            $start_time_test = get_current_time_milliseconds();
            $tester = new Tester($download_location . '/.tsuite', 'localhost:1347', $simplified_repo_settings, $testbook_properties);
            $test_response = $tester->run_tests();
            $end_time_test = get_current_time_milliseconds();

            $download_duration = $start_time_install - $start_time_download;
            $install_duration = $start_time_test - $start_time_install;
            $test_duration = $end_time_test - $start_time_test;

            $total_tests_passed = 0;
            $total_tests_failed = 0;

            if($test_response['status'] == 'failure') {
                echo "$commit_hash failed its tests\n";
                foreach($test_response['files'] as $file => $file_data) {
                    if($file_data['status'] == 'failure') {
                        echo "$file failed its tests\n";
                        foreach($file_data['tests'] as $test_name => $test_data) {
                            if($test_data['status'] == 'failure') {
                                echo "Test failed: $test_name\n";
                                echo "Reason: " . $test_data['reason'] . "\n";
                                $total_tests_failed++;
                            } else {
                                echo "Test passed: $test_name\n";
                                $total_tests_passed++;
                            }
                        }
                    } else {
                        foreach($file_data['tests'] as $test_name => $test_data) {
                            if($test_data['status'] == 'failure') {
                                echo "Test failed: $test_name\n";
                                echo "Reason: " . $test_data['reason'] . "\n";
                                $total_tests_failed++;
                            } else {
                                echo "Test passed: $test_name\n";
                                $total_tests_passed++;
                            }
                        }
                        echo "$file is passing all tests\n";
                    }
                }
                post_commit($repo, $commit_hash, $message, $author, 1, $total_tests_passed, $total_tests_failed, $download_duration, $install_duration, $test_duration);
            } else {
                echo "$commit_hash is passing all tests\n";
                foreach($test_response['files'] as $file => $file_data) {
                    if($file_data['status'] == 'failure') {
                        echo "$file failed its tests\n";
                        foreach($file_data['tests'] as $test_name => $test_data) {
                            if($test_data['status'] == 'failure') {
                                echo "Test failed: $test_name\n";
                                echo "Reason: " . $test_data['reason'] . "\n";
                                $total_tests_failed++;
                            } else {
                                echo "Test passed: $test_name\n";
                                $total_tests_passed++;
                            }
                        }
                    } else {
                        foreach($file_data['tests'] as $test_name => $test_data) {
                            if($test_data['status'] == 'failure') {
                                echo "Test failed: $test_name\n";
                                echo "Reason: " . $test_data['reason'] . "\n";
                                $total_tests_failed++;
                            } else {
                                echo "Test passed: $test_name\n";
                                $total_tests_passed++;
                            }
                        }
                        echo "$file is passing all tests\n";
                    }
                }
                post_commit($repo, $commit_hash, $message, $author, 0, $total_tests_passed, $total_tests_failed, $download_duration, $install_duration, $test_duration);
            }

            write_to_file($test_result_location . '/' . $commit_hash . '.json', json_encode($test_response, JSON_PRETTY_PRINT), true);

        } else {
            echo "The latest commit is already in the system: $commit_hash\n";
        }
    }

    function get_testbook_properties($test_location) {
        $file = $test_location . '/' . 'testbook.json';
        $testbook_contents = read_flat_file($file);

        $testbook_properties = json_decode($testbook_contents, true);

        return $testbook_properties;
    }

    function pull_git_info($repo, $repo_user, $branch, $pat = null) {
        if($pat == null)
            $api_url = "https://api.github.com/repos/$repo_user/$repo/commits/$branch";
        else
            $api_url = "https://$pat@api.github.com/repos/$repo_user/$repo/commits/$branch";
        $git_metadata = do_github_curl($api_url, array(), false, $repo_user);
        return $git_metadata;
    }

    function is_commit_new($repo, $commit_hash) {
        $repo_id = get_repo_id_from_name($repo);
        if($repo_id == null) {
            echo "Could not get repo_id for $repo\n";
            return false;
        }
        $commits = do_curl('/api/v1/commit', array('repo_id' => $repo_id, 'do_retest_flag' => false), false);
        if($commits == null || !isset($commits['response'])) {
            echo "No response data $repo /api/commit\n";
            return false;
        }
        $commits_arr = json_decode($commits['response'], true);
        if($commits_arr == null || sizeof($commits_arr) == 0) {
            echo "No commits returned for $repo /api/v1/commit\n";
            return true;
        }
        foreach($commits_arr as $commit) {
            if($commit['hash'] == $commit_hash) {
                return false;
            }
        }
        return true;
    }

    function do_git_pull($repo, $branch, $download_location, $username, $token) {
        
        $git_url = "https://$username:$token@github.com/$username/$repo.git";

        echo $git_url .'\n';

        // Change directory and pull from the repository
        $cmd = "cd $download_location && git pull $git_url $branch 2>&1";
        $output = shell_exec($cmd);
        echo $output;
    }

    function get_repo_id_from_name($repo) {
        $repo_response = do_curl('/api/v1/repo', array(), false);
        if($repo_response == null || !isset($repo_response['response'])) {
            echo "get_repo_id_from_name did not return a response for $repo for /api/repo\n";
            return null;
        }
        $repos = json_decode($repo_response['response'], true);
        if($repos == null || sizeof($repos) == 0) {
            return null;
        }

        foreach($repos as $iter_repo) {
            if($iter_repo['name'] == $repo) {
                return $iter_repo['id'];
            }
        }

        echo "get_repo_id_from_name: Could not find repo_id for $repo\n";

        return null;
    }

    function post_commit($repo, $commit_hash, $message, $author, $test_status, $success_tests, $failed_tests, $download_duration, $install_duration, $test_duration) {
        $data = array();
        $repo_id = get_repo_id_from_name($repo);
        if($repo_id == null) {
            echo "Failed to post commit: $commit_hash\n";
            echo "Could not get repo_id for $repo\n";
            return null;
        }
        $data[0] = array('repo_id' => $repo_id, 'hash' => $commit_hash, 'message' => $message, 'author' => $author, 'date' => date('Y-m-d H:i:s'), 'test_status' => $test_status, 'success_tests' => $success_tests, 'failed_tests' => $failed_tests, 'download_duration' => $download_duration, 'install_duration' => $install_duration, 'test_duration' => $test_duration);
        $response = do_curl('/api/v1/commit', $data);

        if($response['http_code'] != 200) {
            echo "Failed to post commit: $commit_hash\n";
        } else {
            echo "Posted commit: $commit_hash\n";
        }

        return $response;
    }

    function get_tsuite_config($tsuite_config_location) {
        $ret_conf = array();
        $config_file_contents = $tsuite_config_location != false ? read_flat_file($tsuite_config_location) : null;
        if($config_file_contents == null) {
            die ("Unable to read config file at: $tsuite_config_location");
        }
        $line = strtok($config_file_contents, "\r\n");

        while($line != false) {
    
            $kv = explode('=', $line);
    
            $ret_conf[$kv[0]] = $kv[1];
    
            $line = strtok("\r\n");
        }

        return $ret_conf;
    }

    function read_flat_file($path) {
        $ret = '';
        if(!file_exists($path))
            throw new Exception("File not found $path");
        $file = fopen($path, "r");
        while(($read = fgets($file)) != null) {
            $ret .= $read;
        }
        fclose($file);
        return $ret;
    }

    function do_curl($uri, $data, $post = true) {

        $url = "localhost:80/$uri";
        
        $ch = curl_init();

        if(!$post) {
            $url .= '?' . http_build_query($data);
        }
        else {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_HEADER, false);

        curl_setopt($ch, CURLOPT_NOBODY, false); // remove body

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $head = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return array('http_code' => $httpCode, 'response' => $head);
    }

    function do_github_curl($uri, $data, $post = true, $username = '') {

        $url = "$uri";
        
        $ch = curl_init();

        if(!$post) {
            $url .= '?' . http_build_query($data);
        }
        else {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_HEADER, false);

        curl_setopt($ch, CURLOPT_NOBODY, false); // remove body

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "User-Agent: $username", // Replace with your own application name
        ));

        $head = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return array('http_code' => $httpCode, 'response' => $head);
    }

    function write_to_file($path, $content, $overwrite = false) {
        $cmd = "mkdir -p " . dirname($path) . "";

        exec($cmd, $output);

        $cmd = "touch $path &";

        exec($cmd, $output);

        if(!$overwrite)
            file_put_contents($path, $content, FILE_APPEND);
        else
            file_put_contents($path, $content);
    }

    function get_current_time_milliseconds() {
        return round(microtime(true) * 1000);
    }

?>