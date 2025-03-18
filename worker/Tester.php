<?php

    require __DIR__ . '/vendor/autoload.php';

    class Tester {

        private $tsuite_dir;
        private $endpoint_url;
        private $repo_settings;
        private $testbook_properties;
        private $driver_quit_bool;
        private $lock_file;
        private $commit_data;
        private $running_stage;
        private $stages;

        public function __construct($tsuite_dir, $endpoint_url, $repo_settings, $testbook_properties, $lock_file, $commit_data) {

            $this->tsuite_dir = rtrim($tsuite_dir, '/');
            $this->endpoint_url = $endpoint_url;
            $this->repo_settings = $repo_settings;
            $this->testbook_properties = $testbook_properties;
            $this->driver_quit_bool = false;
            $this->lock_file = $lock_file;

            $this->running_stage = null;

            $this->commit_data = $commit_data;

            $this->stages = null;

            register_shutdown_function(array($this, 'registerShutdown'));

        }

        public function registerShutdown() {
            $error = error_get_last();
            if ($error !== null) {
                // Handle fatal errors here
                echo "Fatal error: {$error['message']} in {$error['file']} on line {$error['line']}\n";

                if($this->running_stage != null) {
                    $this->running_stage->set_errored(true);
                    $this->run_tests();
                }

                //post_commit($repo, $commit_hash, $branch, $message, $author, 1, $total_tests_passed, $total_tests_failed, $download_duration, $install_duration, $test_duration);

                // Log the fatal error if necessary
            }
        }

        public function get_selenium_driver() {

            if(!is_selenium_loaded()) {
                echo "Selenium classes are not loaded\n";
                return null;
            }

            if(isset($this->selenium_driver))
                return $this->selenium_driver;

            try {

                $host = 'http://localhost:4444'; // Selenium server URL
                $capabilities = Facebook\WebDriver\Remote\DesiredCapabilities::chrome();
                $chromeOptions = new Facebook\WebDriver\Chrome\ChromeOptions();
    
                $chromeOptions->addArguments(['--headless', '--disable-gpu', '--ignore-certificate-errors']); // Added proper arguments
    
                $capabilities->setCapability(Facebook\WebDriver\Chrome\ChromeOptions::CAPABILITY_W3C, $chromeOptions);
    
                // Increase timeouts
                $driver = Facebook\WebDriver\Remote\RemoteWebDriver::create(
                    $host, 
                    $capabilities, 
                    120 * 1000, // connection timeout in ms
                    150 * 1000  // request timeout in ms
                );
    
                $this->selenium_driver = $driver;
            } catch (Exception $e) {
                echo "Could not start Selenium WebDriver\n";
                return null;
            }

            return $this->selenium_driver;

        }

        public function has_driver_quit() {
            return $this->driver_quit_bool;
        }

        public function quit_driver() {
            if($this->driver_quit_bool) return;

            echo "Driver has been quit\n";
            if($this->get_selenium_driver() != null) $this->get_selenium_driver()->quit();
            $this->driver_quit_bool = true;
        }

        public function run_tests() {
            if($this->stages == null) {
                $this->stages = $this->generate_stages($this->testbook_properties);
    
                if($this->stages == null || count($this->stages) == 0) {
                    return false;
                }
            }

            foreach($this->stages as $stage) {
                if($stage->is_finished()) continue;
                echo "Executing stage: " . $stage->get_name() . "\n";
                $this->running_stage = $stage;
                $stage_response = $this->execute_stage($stage);
                $stage->set_response($stage_response);
            }

            echo "Finished running all tests\n";

            $this->save_execution_data();

        }

        public function write_test_results($commit_hash, $test_results) {
            write_to_file($this->commit_data['test_result_location'] . '/' . $commit_hash . '.json', json_encode($test_results, JSON_PRETTY_PRINT), true);
        }

        public function save_execution_data() {
            $end_time_test = get_current_time_milliseconds();

            $start_time_install = 0;
            $start_time_download = 0;
            $start_time_test = 0;

            $download_duration = $start_time_install - $start_time_download;
            $install_duration = $start_time_test - $start_time_install;
            $test_duration = $end_time_test - $start_time_test;

            $total_tests_passed = 0;
            $total_tests_failed = 0;

            $test_results = [];
            $test_results['status'] = 'failure';
            $test_results['stages'] = [];
            $test_results['files'] = [];

            foreach($this->stages as $stage) {
                $stage_array_to_add = [];

                $stage_array_to_add['status'] = $stage->is_errored() ? 'failure' : 'success';

                $test_results['stages'][$stage->get_slug()] = $stage_array_to_add;
            }

            $this->write_test_results($this->commit_data['commit_hash'], $test_results);

            post_commit(
                $this->commit_data['repo'], 
                $this->commit_data['commit_hash'], 
                $this->commit_data['branch'], 
                $this->commit_data['message'], 
                $this->commit_data['author'], 
                0, 
                $total_tests_passed, 
                $total_tests_failed, 
                $download_duration, 
                $install_duration, 
                $test_duration
            );

            $this->quit_driver();

            /*if($test_response['status'] == 'failure') {
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
                post_commit($repo, $commit_hash, $branch, $message, $author, 1, $total_tests_passed, $total_tests_failed, $download_duration, $install_duration, $test_duration);
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
                        if(isset($file_data) && $file_data['tests'] != null) {
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
                }
                post_commit($repo, $commit_hash, $branch, $message, $author, 0, $total_tests_passed, $total_tests_failed, $download_duration, $install_duration, $test_duration);
            }

            write_to_file($test_result_location . '/' . $commit_hash . '.json', json_encode($test_response, JSON_PRETTY_PRINT), true);*/
        }

        public function execute_stage($stage) {
            if($stage == null) {
                echo "Tester::execute_stage: stage was null\n";
                return null;
            }

            if($stage->get_actions() == null) {
                echo "Tester::execute_stage: stage get_actions was null\n";
                return null;
            }

            $stage_response = [];

            foreach($stage->get_actions() as $action) {
                $stage_response[$action->get_type()] = $this->execute_action($action);
            }

            return $stage_response;

        }

        public function execute_action($action) {
            if($action == null) {
                echo "Tester::execute_action action is null\n";
                return null;
            } 

            $type = $action->get_type();
            $subactions = $action->get_subactions();

            if($subactions == null) {
                echo "Tester::execute_action subactions is null\n";
                return [];
            }
            if($type == null) {
                echo "Tester::execute_action type is null\n";
                return [];
            }

            if($type === 'shell') {
                echo "Executing shell action\n";
                return $this->execute_shell_action($action);
            }
            
            if($type === 'php') {
                echo "Executing php action\n";
                return $this->execute_php_action($action);
            }

            echo "Action type `$type` not recognized\n";

            return null;
        }

        public function execute_shell_action($action) {
            $action_response = [];

            $settings_string = $this->get_repo_settings_command_string();
            foreach($action->get_subactions() as $command) {
                $command_string = rtrim("$settings_string;$command", ';');
                //echo "Running command string:\n$command\n\n";
                $output = shell_exec($command_string);
                if(!isset($response['output']))
                    $response['output'] = array();
                if($output != null && $output != '')
                    $action_response[] = $output;
            }

            return $action_response;
        }

        public function execute_php_action($action) {
            $action_response = [];

            foreach($action->get_subactions() as $php_file) {
                $file = $this->tsuite_dir . '/' . $php_file;

                echo "Time to run $file\n";

                $functions = $this->get_functions_from_file($file);

                if($functions === false) {
                    $action_response['status'] = 'failure';
                    $action_response['files'][$file]['status'] = 'failure';
                    $action_response['files'][$file]['tests'][$function]['status'] = 'failure';
                    $action_response['files'][$file]['tests'][$function]['reason'] = "Syntax error in file $file";
                    echo "Syntax error in file $file\n";
                } else {

                    $count_functions = sizeof($functions);

                    echo "$file has $count_functions functions\n";

                    $action_response['files'][$file]['status'] = 'success';
                
                    $properties = array();
                    $properties['endpoint_url'] = $this->endpoint_url;
                    $properties['selenium'] = $this->get_selenium_driver();
                    $properties['tester'] = $this;

                    ob_start();

                    foreach ($functions as $function) {
                        try {
                            call_user_func_array($function, array(&$properties));
                            $action_response['files'][$file]['tests'][$function]['status'] = 'success';
                            $action_response['files'][$file]['status'] = 'success';
                            if(!$action->get_stage()->is_errored())
                                $action->get_stage()->set_successful(true);
                        } catch (Exception $e) {
                            $action->get_stage()->set_errored(true);
                            $action->get_stage()->set_successful(false);
                            $action_response['status'] = 'failure';
                            $action_response['files'][$file]['status'] = 'failure';
                            $action_response['files'][$file]['tests'][$function]['status'] = 'failure';
                            $action_response['files'][$file]['tests'][$function]['reason'] = $e->getMessage();
                            echo "Unable to call function $function\n" . $e->getMessage() . "\n";
                        } finally {
                            echo "Finished calling $function\n";
                        }
                    }
                        
                }

                if(!isset($action_response['output']))
                    $action_response['output'] = array();
                $output = ob_get_contents();
                if($output != null && $output != '')
                    $action_response['output'][] = "$output\n";

                ob_flush();

            }

            return $action_response;
        }

        public function generate_actions($stage_data) {
            $return_actions = [];

            if(!isset($stage_data['actions']) || $stage_data['actions'] == null) {
                echo "Tester::generate_actions: stage_data['actions'] is not set or null\n";
                return null;
            }

            $actions = $stage_data['actions'];

            for($x = 0; $x < count($actions); $x++) {
                foreach($actions[$x] as $action_type => $subactions) {
    
                    $action = new Action($action_type, $subactions);
                    $return_actions[] = $action;
                }
            }

            return $return_actions;
        }

        public function generate_stage($slug, $stage_title, $stage_data) {

            //TODO: Better handling
            if(!isset($stage_data['actions']) || $stage_data == null)
                return new Stage($slug, $stage_title, []);

            $actions = $this->generate_actions($stage_data);

            $return_stage = new Stage($slug, $stage_title, $actions);

            foreach($actions as $action)
                $action->set_stage($return_stage);

            return $return_stage;

        }

        public function generate_stages($testbook_properties) {

            if($testbook_properties == null) {
                echo "Testbook is null\n";
                return [];
            }

            if(!isset($testbook_properties['stages']) || $testbook_properties['stages'] == null) {
                echo "Testbook stages is null or not set\n";
                return null;
            }

            $stages = [];
            
            foreach($testbook_properties['stages'] as $stage_name => $stage_data) {
                echo "Tester::generate_stages: found $stage_name\n";
                $stage_title = $stage_data['title'];
                $stage_description = $stage_data['description'];

                $stage = $this->generate_stage($stage_name, $stage_title, $stage_data);

                $stages[] = $stage;

            }

            return $stages;

            /*foreach($this->testbook_properties['stages'] as $stage_name => $stage) {
                $stage_title = $stage['title'];
                $stage_description = $stage['description'];
                echo "Running $stage_title:\n";
                //echo "\t$stage_description\n";

                $response['stages'][$stage_name] = array();
                $response['stages'][$stage_name]['status'] = 'success';

                $action_response = null;

                try {
                    $action_response = $this->handleAction($stage['actions']);
                } catch (Exception $e) {
                    echo "Error running stage $stage_name\n";
                    $response['stages'][$stage_name]['status'] = 'failure';
                    continue;
                }

                if(!isset($action_response) || $action_response == null) continue;

                if(isset($action_response['status']) && $action_response['status'] == 'failure') {
                    $response['status'] = 'failure';
                    $response['stages'][$stage_name]['status'] = 'failure';
                }

                if(isset($action_response['output'])) {
                    $response['stages'][$stage_name]['output'] = $action_response['output'];
                }

                if(isset($action_response['stderr'])) {
                    $response['stages'][$stage_name]['stderr'] = $action_response['stderr'];
                }

                if(isset($action_response['files'])) {
                    foreach($action_response['files'] as $file => $data) {
                        $response['files'][$file] = $data;
                    }
                }

            }

            ob_flush();

            $this->quit_driver();*/

            //return $response;

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
            $functions = [];
            
            // Check if the file exists and can be included
            if (!file_exists($file)) {
                echo "File does not exist: $file\n";
                return $functions;  // Return an empty array if file doesn't exist
            }

            // Check if the file has syntax errors
            $syntax_check = shell_exec("php -l " . escapeshellarg($file));
            if (strpos($syntax_check, 'No syntax errors detected') === false) {
                echo "Syntax error in file: $file\n";
                echo $syntax_check;  // Output the error message from the syntax check
                return false;  // Skip this file if there is a syntax error
            }
        
            // Try to include the file
            try {
                include_once $file;
            } catch (Throwable $e) {
                echo "Error including file: " . $e->getMessage() . "\n";
                return $functions;
            } finally {
                //echo "Quitting driver from Tester::get_functions_from_file ($file); first try-catch-finally\n";
                //$this->quit_driver();
            }
        
            // Get all user-defined functions
            $all_functions = get_defined_functions()['user'];
        
            // Use Reflection to check the file where each function is defined
            foreach ($all_functions as $function) {
                try {
                    $ref = new ReflectionFunction($function);
                    if ($ref->getFileName() === realpath($file)) {
                        $functions[] = $function;
                    }
                } catch (ReflectionException $e) {
                    // Handle reflection error if function is invalid
                    echo "Reflection failed for function: $function. Error: " . $e->getMessage() . "\n";
                } finally {
                    //echo "Quitting driver from Tester::get_functions_from_file ($file); second try-catch-finally\n";
                    //$this->quit_driver();
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
                    //echo "Handling `$subaction` action\n";
                    if($subaction == 'shell') {
                        $settings_string = $this->get_repo_settings_command_string();
                        foreach($subaction_array as $command) {
                            $command_string = rtrim("$settings_string;$command", ';');
                            //echo "Running command string:\n$command\n\n";
                            $output = shell_exec($command_string);
                            if(!isset($response['output']))
                                $response['output'] = array();
                            if($output != null && $output != '')
                                $response['output'][] = $output;
                        }
                    }
                    if($subaction == 'php') {
                        foreach($subaction_array as $php_file) {
                            $file = $this->tsuite_dir . '/' . $php_file;
    
                            $functions = $this->get_functions_from_file($file);

                            if($functions === false) {
                                $response['status'] = 'failure';
                                $response['files'][$file]['status'] = 'failure';
                                $response['files'][$file]['tests'][$function]['status'] = 'failure';
                                $response['files'][$file]['tests'][$function]['reason'] = "Syntax error in file $file";
                                echo "Syntax error in file $file\n";
                            } else {

                                $count_functions = sizeof($functions);
        
                                echo "$file has $count_functions functions\n";
        
                                $response['files'][$file]['status'] = 'success';
                            
                                $properties = array();
                                $properties['endpoint_url'] = $this->endpoint_url;
                                $properties['selenium'] = $this->get_selenium_driver();
                                $properties['tester'] = $this;

                                ob_start();
        
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
                                        echo "Unable to call function $function\n" . $e->getMessage() . "\n";
                                    } finally {
                                        echo "Finished calling $function\n";
                                    }
                                }
                                    
                            }

                            if(!isset($response['output']))
                                $response['output'] = array();
                            $output = ob_get_contents();
                            if($output != null && $output != '')
                                $response['output'][] = "$output\n";

                            ob_flush();

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

    function is_selenium_loaded() {
        return class_exists(\Facebook\WebDriver\Chrome\ChromeOptions::class) && class_exists(\Facebook\WebDriver\Remote\RemoteWebDriver::class) && class_exists(\Facebook\WebDriver\Remote\DesiredCapabilities::class);
    }

?>