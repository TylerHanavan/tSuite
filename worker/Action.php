<?php

    class Action {

        private $type;
        private $subactions;
        private $stage;
        private $runtime_start;
        private $runtime_end;

        public function __construct($type, $subactions) {
            $this->type = $type;
            $this->subactions = $subactions;

            $this->stage = null;

            $this->runtime_start = -1;
            $this->runtime_end = -1;
        }

        public function get_type() {
            return $this->type;
        }

        public function get_subactions() {
            return $this->subactions;
        }

        public function get_stage() {
            return $this->stage;
        }

        public function set_stage($stage) {
            $this->stage = $stage;
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