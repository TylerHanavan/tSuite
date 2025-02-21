<?php

    function handle_lib_js_get($file, $uri_parts, $uri_args) {

        if($file == null || $file == '') {
            html_error_and_exit('Invalid file name');
        }

        try {
            $page_content = read_flat_file($_SERVER["DOCUMENT_ROOT"] . '/lib/' . $file);
            if($page_content === false) {
                html_error_and_exit('File not found');
            }
        } catch (Exception $e) {
            html_error_and_exit('File not found');
        }

        header('Content-Type: application/javascript');
        echo $page_content;

        exit();
    }

?>