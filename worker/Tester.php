<?php

    class Tester {
        public function __construct($tsuite_dir, $endpoint_url) {

            $this->tsuite_dir = $tsuite_dir;
            $this->endpoint_url = $endpoint_url;

        }

        public function run_tests() {
            
            $response = array();

            $response['status'] = 'success';

            $files = $this->scanDirectoryRecursively($this->tsuite_dir);

            // Remove `.` and `..` from the list
            $files = array_diff($files, ['.', '..']);

            foreach($files as $file) {

                if(!str_ends_with($file, 'Test.php')) {
                    continue;
                }

                include_once $file;

                $functions = $this->get_functions_from_file($file);

                $response['files'][$file]['status'] = 'success';
            
                foreach ($functions as $function) {
                    try {
                        call_user_func($function);
                        $response['files'][$file]['tests'][$function]['status'] = 'success';
                        $response['files'][$file]['status'] = 'success';
                        $response['files'][$file]['tests'][$function]['reason'] = $e->getMessage();
                    } catch (Exception $e) {
                        echo 'Caught exception: ',  $e->getMessage(), "\n";
                        $response['status'] = 'failure';
                        $response['files'][$file]['status'] = 'failure';
                        $response['files'][$file]['tests'][$function]['status'] = 'failure';
                        $response['files'][$file]['tests'][$function]['reason'] = $e->getMessage();
                    }
                }
            }

            return $response;

        }

        public function test_curl($uri, $data, $post = true) {

            $url = $this->endpoint_url . "/$uri";
            
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

    function assertEquals($expected, $actual, $message = null) {
        if ($expected !== $actual) {
            throw new Exception("Expected $expected but got $actual");
        }
    }

    function assertTrue($actual) {
        if (!$actual) {
            throw new Exception("Expected true but got false");
        }
    }

    function assertFalse($actual) {
        if ($actual) {
            throw new Exception("Expected false but got true");
        }
    }

?>