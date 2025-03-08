<?php

    class Tester {
        public function __construct($tsuite_dir, $endpoint_url, $repo_settings, $testbook_properties) {

            $this->tsuite_dir = rtrim($tsuite_dir, '/');
            $this->endpoint_url = $endpoint_url;
            $this->repo_settings = $repo_settings;
            $this->testbook_properties = $testbook_properties;

        }

        public function run_tests() {
            
            $response = array();

            $response['status'] = 'success';
            $response['stages'] = array();
            $response['files'] = array();

            foreach($this->testbook_properties['stages'] as $stage_name => $stage) {
                $stage_title = $stage['title'];
                $stage_description = $stage['description'];
                echo "Running $stage_title:\n";
                echo "\t$stage_description\n";

                $response['stages'][$stage_name] = array();
                $response['stages'][$stage_name]['status'] = 'success';

                $action_response = $this->handleAction($stage['actions']);

                if(!isset($action_response) || $action_response == null) continue;

                if(isset($action_response['status']) && $action_response['status'] == 'failure') {
                    $response['status'] = 'failure';
                    $response['stages'][$stage_name]['status'] = 'failure';
                }

                foreach($action_response['files'] as $file => $data) {
                    $response['files'][$file] = $data;
                }

            }

            return $response;

        }

        public function get_repo_settings_command_string() {
            $command_string = '';
            foreach($this->repo_settings as $key => $value) {
                if(!isset($key) || $key == '' || $key === '' || $key == null) continue;
                $command_string .= "export $key=\"$value\";";
            }
            return rtrim($command_string, ';');
        }

        public function get_functions_from_file($file) {
            // Include the file
            include_once $file;
    
            // Get all user-defined functions
            $all_functions = get_defined_functions()['user'];
            
            $functions = [];
            
            // Use Reflection to check the file where each function is defined
            foreach ($all_functions as $function) {
                $ref = new ReflectionFunction($function);
                if ($ref->getFileName() === realpath($file)) {
                    $functions[] = $function;
                }
            }
    
            return $functions;
        }

        public function scanDirectoryRecursively($dir) {
            $files = [];
        
            foreach (scandir($dir) as $file) {
                if ($file === '.' || $file === '..') continue; // Skip . and ..
        
                $fullPath = $dir . DIRECTORY_SEPARATOR . $file;
                
                if (is_dir($fullPath)) {
                    // Recursively scan subdirectories
                    $files = array_merge($files, $this->scanDirectoryRecursively($fullPath));
                } else {
                    $files[] = $fullPath; // Add file to results
                }
            }
        
            return $files;
        }

        public function handleAction($actions) {

            $response = array();

            foreach($actions as $action) {
                foreach($action as $subaction => $subaction_array) {
                    echo "Handling `$subaction` action\n";
                    if($subaction == 'shell') {
                        $command_string = $this->get_repo_settings_command_string();
                        foreach($subaction_array as $command) {
                            $command_string = rtrim("$command_string;$command", ';');
                        }
                        echo "Running command string:\n$command_string\n\n";
                        $output = shell_exec($command_string);
                        echo $output;
                    }
                    if($subaction == 'php') {
                        foreach($subaction_array as $php_file) {
                            $file = $this->tsuite_dir . '/' . $php_file;
    
                            $functions = $this->get_functions_from_file($file);

                            $count_functions = sizeof($functions);
    
                            echo "$file has $count_functions functions\n";
    
                            $response['files'][$file]['status'] = 'success';
                        
                            $properties = array();
                            $properties['endpoint_url'] = $this->endpoint_url;
    
                            foreach ($functions as $function) {
                                try {
                                    call_user_func_array($function, array(&$properties));
                                    $response['files'][$file]['tests'][$function]['status'] = 'success';
                                    $response['files'][$file]['status'] = 'success';
                                } catch (Exception $e) {
                                    $response['status'] = 'failure';
                                    $response['files'][$file]['status'] = 'failure';
                                    $response['files'][$file]['tests'][$function]['status'] = 'failure';
                                    $response['files'][$file]['tests'][$function]['reason'] = $e->getMessage();
                                }
                            }
                        }
                    }
                }
            }

            return $response;
        }

    }

    function test_curl($uri, $data, $post = true, $session_cookie = null) {

        $url = $uri;
        
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
        
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);

        if ($session_cookie != null && !empty($session_cookie)) {
            curl_setopt($ch, CURLOPT_COOKIE, "session_token=$session_cookie");
        }

        $head = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return array('http_code' => $httpCode, 'response' => $head);
    }

    function assertEquals($expected, $actual, $message = '') {
        if ($expected !== $actual) {
            if($actual === true) $actual = 'true';
            if($actual === false) $actual = 'false';
            if($expected === true) $expected = 'true';
            if($expected === false) $expected = 'false';
            throw new Exception("$message / Expected $expected but got $actual");
        }
    }

    function assertTrue($actual, $message = '') {
        if (!$actual) {
            if($actual === false)
                throw new Exception("$message / Expected true but got false");
            else
                throw new Exception("$message / Expected true but got $actual");
        }
    }

    function assertFalse($actual, $message = '') {
        if ($actual) {
            if($actual === true)
                throw new Exception("$message / Expected false but got true");
            else
                throw new Exception("$message / Expected false but got $actual");
        }
    }

    function assertStrContains($needle, $haystack, $message = '') {
        if (strpos($haystack, $needle) === false) {
            throw new Exception("$message / Expected string $needle to be in $haystack but it wasn't");
        }
    }

    function assertArrayContains($needle, $haystack, $message = '') {
        if (!in_array($needle, $haystack)) {
            throw new Exception("$message / Expected array to contain $needle but it didn't");
        }
    }

?>