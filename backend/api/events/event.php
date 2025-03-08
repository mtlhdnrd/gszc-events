<?php
    class Event {
        public $event_id;
        public $name;
        public $date;
        public $location;
        public $status;
        public $busyness;

        public function __construct($event_id, $name, $date, $location, $status, $busyness) {
            $this->event_id = $event_id;
            $this->name = $name;
            $this->date = $date;
            $this->location = $location;
            $this->status = $status;
            $this->busyness = $busyness;
        }
    }