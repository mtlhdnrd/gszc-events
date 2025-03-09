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
            case "DELETE":
                $method_superglobal = $_GET;
                break;
            default:
                return false;
        }

        $array_all = true;
        foreach($params as $param) {
            $array_all = $array_all && isset($method_superglobal[$param]);
        }

        return $_SERVER["REQUEST_METHOD"] == $method && count($method_superglobal) == count($params) && $array_all;
    }
// api_utils.php (példa)
function validate_request_json($method, $required_params) {
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        return false;
    }

    $request_data = json_decode(file_get_contents('php://input'), true); // JSON dekódolás

    foreach ($required_params as $param) {
        if (!isset($request_data[$param]) || empty($request_data[$param])) {
            return false;
        }
    }

    return true;
}