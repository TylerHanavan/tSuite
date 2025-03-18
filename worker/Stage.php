<?php

    class Stage {

        private $slug;
        private $name;
        private $actions;
        private $extra_fields;
        private $successful;
        private $errored;
        private $response;

        public function __construct($slug, $name, $actions, $extra_fields = []) {
            $this->slug = $slug;
            $this->name = $name;
            $this->actions = $actions;
            $this->extra_fields = $extra_fields;

            $this->successful = false;
            $this->errored = false;

            $this->response = null;

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
    }

?>