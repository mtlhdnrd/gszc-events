<?php
    $conn = new mysqli();
    $conn->connect("localhost", "root", "", "gszc_events") or die("ERROR: Could not connect to database");
    $conn->set_charset("utf8");
    define('OPERATOR_EMAIL', 'admin@example.com');