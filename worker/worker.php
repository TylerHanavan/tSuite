<?php

    $tsuite_config_location = get_tsuite_config_location();

    $_TSUITE_CONFIG = get_tsuite_config($tsuite_config_location);

    $repo = get_config_value('REPO');
    $repo_user = get_config_value('REPO_USER');
    $repo_url = get_config_value('REPO_URL');
    $branch = get_config_value('BRANCH');
    $download_location = get_config_value('DOWNLOAD_LOCATION');
    $install_location = get_config_value('INSTALL_LOCATION');
    $items_to_install = get_config_value('ITEMS_TO_INSTALL');

    echo "Repo: $repo\n";
    echo "Repo URL: $repo_url\n";
    echo "Branch: $branch\n";
    echo "Download Location: $download_location\n";
    echo "Install Location: $install_location\n";
    echo "Items to Install: $items_to_install\n";

    $tick = 0;

    while(true) {
        if($tick++ % 62 == 0) {
            $git_metadata = pull_git_info($repo, $repo_user, $branch);

            echo "Git API Metadata returned for $repo: \n";
            var_dump($git_metadata);

            $git_metadata = json_decode($git_metadata['response'], true);

            if($git_metadata == null) {
                echo "Unable to pull git metadata\n";
                sleep(1);
                continue;
            }
            $commit_hash = $git_metadata['sha'] ?? null;
            if($commit_hash == null) {
                echo "Unable to get commit hash\n";
                sleep(1);
                continue;
            }
            $commit_data = $git_metadata['commit'] ?? null;
            if($commit_data == null) {
                echo "Unable to get nested commit data\n";
                sleep(1);
                continue;
            }
            $message = $commit_data['message'] ?? null;
            if($message == null) {
                echo "Unable to get commit message\n";
                sleep(1);
                continue;
            }
            $commit_author_data = $commit_data['author'] ?? null;
            if($commit_author_data == null) {
                echo "Unable to get nested commit author data\n";
                sleep(1);
                continue;
            }
            $author = $commit_author_data['name'] ?? null;
            if($author == null) {
                echo "Unable to get commit author\n";
                sleep(1);
                continue;
            }
            if(is_commit_new($repo, $commit_hash)) {
                echo "New commit detected: $commit_hash\n";
                do_git_pull($repo, $branch, $download_location, $install_location, $items_to_install);
                post_commit($repo, $commit_hash, $message, $author);
            }
        }
        sleep(1);
    }

    function pull_git_info($repo, $repo_user, $branch) {
        $api_url = "https://api.github.com/repos/$repo_user/$repo/commits/$branch";
        echo 'Querying API URL: ' . $api_url . "\n";
        $git_metadata = do_github_curl($api_url, array(), false, $repo_user);
        echo 'The api url returned the following metadata: ' . "\n";
        var_dump($git_metadata);
        return $git_metadata;
    }

    function is_commit_new($repo, $commit_hash) {
        echo "Checking if $commit_hash is new for $repo\n";
        $repo_id = get_repo_id_from_name($repo);
        if($repo_id == null) {
            echo "Could not get repo_id for $repo\n";
            return false;
        }
        $commits = do_curl('/api/commits', array('repo' => $repo_id));
        if($commits == null || !isset($commits['response'])) {
            echo "No response data $repo /api/commits\n";
            return false;
        }
        $commits = json_decode($commits['response'], true);
        if($commits == null || sizeof($commits) == 0) {
            echo "No commits returned for $repo /api/commits\n";
            return false;
        }
        foreach($commits as $commit) {
            if($commit['commit_hash'] == $commit_hash) {
                return false;
            }
        }
        return true;
    }

    function do_git_pull($repo, $branch, $download_location, $install_location, $items_to_install) {
        $cmd = "cd $download_location && git pull origin $branch";
        $output = shell_exec($cmd);
        echo $output;

        if($items_to_install != null) {
            $items = explode(',', $items_to_install);
            foreach($items as $item) {
                $cmd = "rm -r $install_location/* ; rsync -av $download_location/$item $install_location";
                echo "COMMAND: $cmd\n";
                $output = shell_exec($cmd);
                echo $output;
            }
        }
    }

    function get_repo_id_from_name($repo) {
        $repo_response = do_curl('/api/repos', array(), false);
        if($repo_response == null || !isset($repo_response['response'])) {
            echo "get_repo_id_from_name did not return a response for $repo for /api/repos\n";
            return null;
        }
        var_dump($repo_response);
        $repos = json_decode($repo_response['response'], true);
        if($repos == null || sizeof($repos) == 0) {
            return null;
        }

        foreach($repos as $repo) {
            if($repo['name'] == $repo) {
                return $repo['id'];
            }
        }

        return null;
    }

    function post_commit($repo, $commit_hash, $message, $author) {
        $data = array('entities' => array());
        $data['entities'][0] = array('repo' => $repo, 'commit_hash' => $commit_hash, 'message' => $message, 'author' => $author, 'date' => date('Y-m-d H:i:s'));
        $response = do_curl('/api/commits', $data);
        echo "/api/commits/ payload:\n";
        var_dump($data);
        echo "\n";
        echo "Response:\n";
        var_dump($response);
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

    function get_config_value($key) {
        global $_TSUITE_CONFIG;

        return (isset($_TSUITE_CONFIG[$key]) && !empty($_TSUITE_CONFIG[$key])) ? $_TSUITE_CONFIG[$key] : null;
    }

    function get_tsuite_config_location() {
        return getenv('TSUITE_CONFIG_LOCATION');
    }

    function read_flat_file($path) {
        $ret = '';
        $file = fopen($path, "r") or die("Unable to open file: $path");
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

?>