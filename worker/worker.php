<?php

    $tsuite_config_location = get_tsuite_config_location();

    $_TSUITE_CONFIG = get_tsuite_config($tsuite_config_location);

    $repo = get_config_value('REPO');
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
            do_git_pull($repo, $branch, $download_location, $install_location, $items_to_install);
            post_commit($repo, 'commit_hash', 'message', 'author');
        }
        sleep(1);
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

    function post_commit($repo, $commit_hash, $message, $author) {
        $data = array('entities' => array());
        $data['entities'][0] = array('repo' => $repo, 'commit_hash' => $commit_hash, 'message' => $message, 'author' => $author);
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

?>