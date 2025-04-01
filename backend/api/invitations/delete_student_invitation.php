<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/config.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/bgszc-events/backend/api_utils.php";

if (validate_request("DELETE", array("invitation_id"))) {

    $invitation_id = intval($_GET["invitation_id"]);

    // --- Check if Invitation Exists ---
    $check_query = "SELECT 1 FROM student_invitations WHERE invitation_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $invitation_id);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows === 0) {
        http_response_code(404); // 404 Not Found
        echo "Invitation with invitation_id $invitation_id not found.";
         $check_stmt->close();
        exit;
    }
    $check_stmt->close();

    // --- Delete from `student_invitations` ---
    $query = "DELETE FROM `student_invitations` WHERE `invitation_id` = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $invitation_id);

    if ($stmt->execute()) {
        http_response_code(204); // 204 No Content - Successful deletion
    } else {
        http_response_code(500);
        echo "Error deleting invitation: " . $stmt->error;
         echo "<img src='https://http.cat/500'>";

    }
    $stmt->close();

} else {
    http_response_code(400);
    echo "Missing required parameters (invitation_id).";
     echo "<img src='https://http.cat/400'>";

}
?>