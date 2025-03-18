<?php

    class Action {

        private $type;
        private $subactions;
        private $stage;

        public function __construct($type, $subactions) {
            $this->type = $type;
            $this->subactions = $subactions;

            $this->stage = null;
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
    }

?>