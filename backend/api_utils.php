<?php
    function validate_request($method, $params) {
        $method_superglobal = array();
        switch ($method) {
            case "GET":
                $method_superglobal = $_GET;
                break;
            case "POST":
                $method_superglobal = $_POST;
                break;
            default:
                return false;
        }

        return $_SERVER["REQUEST_METHOD"] == $method && count($method_superglobal) == count($params) && array_walk($params, function($param) {
            return isset($method_superglobal[$param]);
        });
    }
