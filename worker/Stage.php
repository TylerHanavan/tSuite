<?php

    class Stage {

        private $slug;
        private $name;
        private $actions;
        private $extra_fields;
        private $successful;
        private $errored;
        private $response;
        private $output;
        private $file_results;
        private $runtime_start;
        private $runtime_end;

        public function __construct($slug, $name, $actions, $extra_fields = []) {
            $this->slug = $slug;
            $this->name = $name;
            $this->actions = $actions;
            $this->extra_fields = $extra_fields;

            $this->successful = false;
            $this->errored = false;

            $this->response = null;
            $this->output = [];
            $this->file_results = [];

            $this->runtime_start = -1;
            $this->runtime_end = -1;

        }

        public function get_slug() {
            return $this->slug;
        }

        public function get_name() {
            return $this->name;
        }

        public function get_actions() {
            return $this->actions;
        }

        public function is_finished() {
            return $this->successful || $this->errored;
        }

        public function is_successful() {
            return $this->successful;
        }

        public function set_successful($bool = true) {
            $this->successful = $bool;
        }

        public function is_errored() {
            return $this->errored;
        }

        public function set_errored($bool = true) {
            $this->errored = $bool;
        }

        public function get_response() {
            return $this->response;
        }

        public function set_response($response) {
            $this->response = $response;
        }

        public function get_output() {
            return $this->output;
        }

        public function set_output($output) {
            $this->output = $output;
        }

        public function add_output($output) {
            for($x = 0; $x < 3; $x++)
                $output = rtrim($output, "\n");
            $this->output[] = "$output\n";
        }

        public function get_file_results() {
            return $this->file_results;
        }

        public function add_file_result($file_name, $result) {
            $this->file_results[$file_name] = $result;
        }

        public function get_runtime_start() {
            return $this->runtime_start;
        }

        public function set_runtime_start($runtime_start) {
            $this->runtime_start = $runtime_start;
        }

        public function get_runtime_end() {
            return $this->runtime_end;
        }

        public function set_runtime_end($runtime_end) {
            $this->runtime_end = $runtime_end;
        }
    }

?>