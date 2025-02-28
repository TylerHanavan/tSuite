<?php

    class Tester {
        public function __construct($tsuite_dir, $endpoint_url, $repo_settings) {

            $this->tsuite_dir = rtrim($tsuite_dir, '/');
            $this->endpoint_url = $endpoint_url;
            $this->repo_settings = $repo_settings;

        }

        public function run_tests() {
            
            $response = array();

            $response['status'] = 'success';

            $files = $this->scanDirectoryRecursively($this->tsuite_dir);

            // Remove `.` and `..` from the list
            $files = array_diff($files, ['.', '..']);

            $pre_files_to_process = array();
            $order_file = null;
            $install_file = null;

            foreach($files as $file) {

                if($file == $this->tsuite_dir . '/order') {
                    $order_file = $file;
                    continue;
                }
                if($file == $this->tsuite_dir . '/install') {
                    $install_file = $file;
                    continue;
                }

                if(!str_ends_with($file, 'Test.php')) {
                    continue;
                }
                $pre_files_to_process[] = $file;
            }

            $files_from_order_file = array();

            if($order_file != null) {
                echo "Found order file: $order_file\n";
                $order_file_contents = read_flat_file($order_file);
                foreach(explode("\n", $order_file_contents) as $line) {
                    if(trim($line) == '') {
                        continue;
                    }
                    $line = $this->tsuite_dir . '/' . trim($line);
                    $files_from_order_file[] = $line;
                }
            }

            foreach($pre_files_to_process as $file) {
                if(!in_array($file, $files_from_order_file)) {
                    echo "WARN: Found file not in order file: $file\n";
                    $response['warnings'][] = "Found file not in order file: $file";
                }
            }

            $command_string = '';

            foreach($this->repo_settings as $key => $value) {
                $command_string .= "$key=\"$value\";";
            }
            
            if($install_file != null) {
                echo "Found install file: $install_file\n";
                echo "Here are the commands that the install file will run:\n";
                foreach(explode("\n", read_flat_file($install_file)) as $line) {
                    if(trim($line) == '') {
                        continue;
                    }
                    echo "$line\n";
                    $command_string .= "$line;";
                }
            }

            $command_output = shell_exec($command_string);
            echo "Command output:\n";
            echo "$command_output\n";
            $response['command_output'] = $command_output;

            $files_to_process = array();

            foreach($files_from_order_file as $file) {
                $files_to_process[] = $file;
            }

            foreach($pre_files_to_process as $file) {
                if(!in_array($file, $files_to_process)) {
                    $files_to_process[] = $file;
                }
            }

            foreach($files_to_process as $file) {

                include_once $file;

                echo "$file\n";

                $functions = $this->get_functions_from_file($file);

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

            return $response;

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

    }

    function test_curl($uri, $data, $post = true, $cookie = null) {

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

        if ($use_cookie && !empty($session_cookie)) {
            curl_setopt($ch, CURLOPT_COOKIE, "session_token=$session_token");
        }

        $head = curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return array('http_code' => $httpCode, 'response' => $head);
    }

    function assertEquals($expected, $actual, $message = '') {
        if ($expected !== $actual) {
            throw new Exception("$message / Expected $expected but got $actual");
        }
    }

    function assertTrue($actual, $message = '') {
        if (!$actual) {
            throw new Exception("$message / Expected true but got $actual");
        }
    }

    function assertFalse($actual, $message = '') {
        if ($actual) {
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